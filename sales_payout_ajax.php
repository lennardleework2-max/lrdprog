<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// A real TikTok payout export (~60 columns x 18k+ rows, WPS-exported with a full
// 16384-column style declaration) exhausts the default 512M memory_limit even with
// readDataOnly. This is headroom for large files.
ini_set('memory_limit', '1024M');

session_start();

if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    header('location: index.php');
    exit;
}

require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/stdfunc100.php";
require "resources/lx2.pdodb.php";
require 'vendor/autoload.php';

// resources/connect4.php's PDO connection doesn't declare a charset, so it defaults to the
// server's connection charset (not UTF-8 here) - multi-byte header text like "₱" gets
// silently corrupted on read/write without this. Scoped to this file only, not the shared
// connect4.php, to avoid touching other pages' behavior.
$link->exec("SET NAMES utf8mb4");

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

// NOTE: an earlier version of this file used per-column IReadFilter restrictions to cut
// memory use on very wide sheets. Testing against a real large TikTok sample surfaced cases
// where that column restriction caused PhpSpreadsheet/the underlying zip reader to silently
// return empty values for most rows on this environment. Reading full rows (no column
// filter) is the same approach already used by sales_fileupload_ajax.php /
// sales_filepaidupload_ajax.php and did not show that problem, so this file intentionally
// does not use a column-restricted read filter.

function payout_get_cell_value_as_string($cell) {
    if ($cell instanceof \PhpOffice\PhpSpreadsheet\Cell\Cell) {
        $value = $cell->getValue();

        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $value->getPlainText();
        }

        return $value;
    }

    return $cell;
}

// Same "peek at xl/workbook.xml without loading the sheet" approach already used by
// sales_filepaidupload_ajax.php's paid_upload_resolve_active_sheet_name() - avoids loading
// the whole workbook just to find out which sheet is active.
function payout_resolve_active_sheet_name($filePath) {
    try {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return null;
        }
        $xmlContents = $zip->getFromName('xl/workbook.xml');
        $zip->close();

        if ($xmlContents === false) {
            return null;
        }

        $workbookXml = @simplexml_load_string($xmlContents);
        if ($workbookXml === false || !isset($workbookXml->sheets->sheet)) {
            return null;
        }

        $sheetNames = [];
        foreach ($workbookXml->sheets->sheet as $sheetNode) {
            $sheetNames[] = (string) $sheetNode['name'];
        }

        if (empty($sheetNames)) {
            return null;
        }

        $activeTab = 0;
        if (isset($workbookXml->bookViews->workbookView['activeTab'])) {
            $activeTab = (int) $workbookXml->bookViews->workbookView['activeTab'];
        }

        if (!isset($sheetNames[$activeTab])) {
            return null;
        }

        return $sheetNames[$activeTab];
    } catch (\Throwable $e) {
        return null;
    }
}

// Maps each file ordernum to one tranfile1 docnum: exact text match first (spaces count),
// trimmed match only as the fallback. When several records match, the earliest trndte wins,
// then the lowest recid.
function payout_match_orders_to_docnums($orderNumbers, $dbRows) {
    usort($dbRows, function ($a, $b) {
        $cmp = strcmp((string)$a['trndte'], (string)$b['trndte']);
        return $cmp !== 0 ? $cmp : ((int)$a['recid'] - (int)$b['recid']);
    });

    $exact = [];
    $trimmed = [];
    foreach ($dbRows as $row) {
        $key = (string)$row['ordernum'];
        if (!isset($exact[$key])) {
            $exact[$key] = $row['docnum'];
        }
        if (!isset($trimmed[trim($key)])) {
            $trimmed[trim($key)] = $row['docnum'];
        }
    }

    $map = [];
    foreach ($orderNumbers as $ordernum) {
        $ordernum = (string)$ordernum;
        if (isset($exact[$ordernum])) {
            $map[$ordernum] = $exact[$ordernum];
        } elseif (isset($trimmed[trim($ordernum)])) {
            $map[$ordernum] = $trimmed[trim($ordernum)];
        }
    }
    return $map;
}

function payout_add_result(&$response, $ordernum, $success, $message) {
    $response['results'][] = [
        'ordernum' => $ordernum,
        'success'  => $success,
        'message'  => $message,
    ];
}

$payout_log_dir = __DIR__ . '/logs';
if (!is_dir($payout_log_dir)) {
    @mkdir($payout_log_dir, 0777, true);
}
$payout_log_file = $payout_log_dir . '/payout_upload_' . date('Ymd_His') . '_' . substr(microtime(), 2, 6) . '.log';

// Counts/timings only - never logs order numbers, prices, or file contents.
function payout_log_line($line) {
    global $payout_log_file;
    @file_put_contents($payout_log_file, '[PAYOUT_UPLOAD] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// BEAVER debug log - counts, docnums and the raw untprc_before_payout value shapes only
// (no order numbers, file contents or user data).
function payout_beaver_log($label, $data = null) {
    @file_put_contents(__DIR__ . '/debug_beaver_payout.log',
        date('Y-m-d H:i:s') . ' [BEAVER_PAYOUT_UPLOAD] ' . $label . ($data === null ? '' : ' ' . json_encode($data)) . PHP_EOL,
        FILE_APPEND | LOCK_EX);
}

payout_beaver_log('---- START upload');
try {
    $beaverCol = $link->query("SHOW COLUMNS FROM tranfile2 LIKE 'untprc_before_payout'")->fetch(PDO::FETCH_ASSOC);
    payout_beaver_log('B1 column definition', $beaverCol ?: 'COLUMN NOT FOUND');
} catch (\Throwable $e) {
    payout_beaver_log('B1 column definition FAILED', $e->getMessage());
}

$response = [
    'status'                 => 1,
    'errorMsg'                => '',
    'matched_order_count'     => 0,
    'unmatched_order_count'   => 0,
    'already_uploaded_order_count' => 0,
    'updated_tranfile2_rows'  => 0,
    'unmatched_ordernums'     => [],
    'results'                 => [],
];

// --- validate platform ---
$platform_name_raw = isset($_POST['platform_name']) ? trim((string)$_POST['platform_name']) : '';
$platform_name = strtoupper($platform_name_raw);
$allowed_platforms = ['TIKTOK', 'LAZADA', 'SHOPEE'];

if (!in_array($platform_name, $allowed_platforms, true)) {
    $response['status'] = 0;
    $response['errorMsg'] = "Invalid platform selected.";
    echo json_encode($response);
    exit;
}

payout_log_line('platform=' . $platform_name);

// Shopee: payout divided by the document's total qty, earliest matching document only.
// Lazada/TikTok: original logic - payout written as-is to every matching document.
$isShopee = ($platform_name === 'SHOPEE');

// --- validate uploaded file ---
if (!isset($_FILES['xfile']) || $_FILES['xfile']['error'] !== 0) {
    $response['status'] = 0;
    $response['errorMsg'] = "No file uploaded, or an upload error occurred.";
    echo json_encode($response);
    exit;
}

$filePath = $_FILES['xfile']['tmp_name'];
$fileExtension = strtolower(pathinfo($_FILES['xfile']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, ['xlsx', 'csv'], true)) {
    $response['status'] = 0;
    $response['errorMsg'] = "Unsupported file type. Please upload a .xlsx or .csv file.";
    echo json_encode($response);
    exit;
}

if ($fileExtension === 'csv') {
    $fileContent = file_get_contents($filePath);
    $currentEncoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($currentEncoding !== false && $currentEncoding !== 'UTF-8') {
        $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $currentEncoding);
        file_put_contents($filePath, $fileContent);
    }
}

// --- mapping lookup: SALES-PAYOUT rows only ---
$select_db_column_equivalent = "SELECT * FROM sales_upload_column WHERE platform_desc = ?";
$stmt_column_equivalent = $link->prepare($select_db_column_equivalent);
$stmt_column_equivalent->execute(['SALES-PAYOUT']);

$requiredHeaders = [];
while ($rs_column_equivalent = $stmt_column_equivalent->fetch()) {
    $requiredHeaders[$rs_column_equivalent['dest_column_matched']] = $rs_column_equivalent[strtolower($platform_name) . '_column_matching'];
}

$orderHeaderText = isset($requiredHeaders['ordernum']) ? trim((string)$requiredHeaders['ordernum']) : '';
$priceHeaderText = isset($requiredHeaders['untprc']) ? trim((string)$requiredHeaders['untprc']) : '';

if ($orderHeaderText === '' || $priceHeaderText === '') {
    $response['status'] = 0;
    $response['errorMsg'] = "Payout column mapping is not configured for this platform. Please contact an administrator.";
    echo json_encode($response);
    exit;
}

// --- determine sheet(s) + header/data row per platform ---
// sheetTargets entries: [sheetName-or-null, headerRow, dataStartRow]
$sheetTargets = [];

try {
    if ($fileExtension === 'xlsx') {
        if ($platform_name === 'SHOPEE') {
            $probeReader = new Xlsx();
            $allSheetNames = $probeReader->listWorksheetNames($filePath);

            $matchedSheets = [];
            foreach ($allSheetNames as $sn) {
                // "Income" (single-sheet export) or "Income - 1", "Income - 2", ... (split export)
                if (preg_match('/^Income(\s*-\s*\d+)?$/i', trim($sn))) {
                    $matchedSheets[] = $sn;
                }
            }

            if (empty($matchedSheets)) {
                $response['status'] = 0;
                $response['errorMsg'] = "No 'Income' or 'Income - N' sheets found in the uploaded Shopee file.";
                echo json_encode($response);
                exit;
            }

            foreach ($matchedSheets as $sn) {
                $sheetTargets[] = [$sn, 6, 7];
            }
        } else {
            $activeSheetName = null;

            // TikTok: the data is always on "Order details" - don't depend on which tab
            // happened to be open when the file was saved (e.g. "Reports").
            if ($platform_name === 'TIKTOK') {
                $probeReader = new Xlsx();
                foreach ($probeReader->listWorksheetNames($filePath) as $sn) {
                    if (strcasecmp(trim($sn), 'Order details') === 0) {
                        $activeSheetName = $sn;
                        break;
                    }
                }
            }

            if ($activeSheetName === null) {
                $activeSheetName = payout_resolve_active_sheet_name($filePath);
            }
            if ($activeSheetName === null) {
                $probeReader = new Xlsx();
                $allSheetNames = $probeReader->listWorksheetNames($filePath);
                $activeSheetName = isset($allSheetNames[0]) ? $allSheetNames[0] : null;
            }

            if ($activeSheetName === null) {
                $response['status'] = 0;
                $response['errorMsg'] = "Could not read any sheet from the uploaded file.";
                echo json_encode($response);
                exit;
            }

            $sheetTargets[] = [$activeSheetName, 1, 2];
        }
    } else {
        // CSV has no sheet concept - Shopee+CSV is a best-effort edge case using the same
        // header/data row convention as its xlsx export.
        $headerRow = ($platform_name === 'SHOPEE') ? 6 : 1;
        $sheetTargets[] = [null, $headerRow, $headerRow + 1];
    }
} catch (\Throwable $e) {
    $response['status'] = 0;
    $response['errorMsg'] = "Unable to read the uploaded file. Please make sure it is a valid, uncorrupted file.";
    echo json_encode($response);
    exit;
}

payout_log_line('sheets_targeted=' . count($sheetTargets));

// For each targeted sheet: load it fully (readDataOnly, one sheet at a time via
// setLoadSheetsOnly to keep other sheets out of memory), find the header columns, then
// read all its data rows in the same pass via the cell iterator.
$sheetColumns = [];
$sheetsMissingHeaders = [];
$uploadedRows = [];
$invalidPriceCount = 0;

foreach ($sheetTargets as $target) {
    list($sheetName, $headerRow, $dataStartRow) = $target;
    $sheetKey = ($sheetName === null) ? '(uploaded file)' : $sheetName;

    try {
        if ($fileExtension === 'xlsx') {
            $reader = new Xlsx();
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly($sheetName);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getSheetByName($sheetName);
        } else {
            $reader = new Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(",");
            $reader->setEnclosure('"');
            $reader->setEscapeCharacter("\\");
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
        }
    } catch (\Throwable $e) {
        $sheetsMissingHeaders[] = $sheetKey;
        continue;
    }

    $orderCol = null;
    $priceCol = null;

    if ($sheet !== null) {
        $rowIterator = $sheet->getRowIterator($headerRow, $headerRow);
        $rowObj = $rowIterator->current();
        if ($rowObj !== null) {
            foreach ($rowObj->getCellIterator() as $cell) {
                $val = trim((string) payout_get_cell_value_as_string($cell));
                if ($val === $orderHeaderText) {
                    $orderCol = $cell->getColumn();
                }
                if ($val === $priceHeaderText) {
                    $priceCol = $cell->getColumn();
                }
            }
        }
    }

    if ($orderCol === null || $priceCol === null) {
        $sheetsMissingHeaders[] = $sheetKey;
        continue;
    }

    // Data is read from the same already-loaded $sheet right away (see below) - reading
    // full rows via the cell iterator, not a column-restricted filter or getCell() lookups
    // by coordinate, since both of those were found to silently drop data on a real large
    // sample file in this environment (see note above the filter classes).
    $highestRow = $sheet->getHighestDataRow();
    if ($highestRow >= $dataStartRow) {
        foreach ($sheet->getRowIterator($dataStartRow, $highestRow) as $dataRowObj) {
            $ordernum = '';
            $rawPrice = null;

            foreach ($dataRowObj->getCellIterator() as $cell) {
                $col = $cell->getColumn();
                if ($col === $orderCol) {
                    // Shopee: kept as-is (not trimmed) - an exact match, spaces included, is tried first.
                    // Lazada/TikTok: trimmed, same as the original logic.
                    $ordernum = (string) payout_get_cell_value_as_string($cell);
                    if (!$isShopee) {
                        $ordernum = trim($ordernum);
                    }
                } elseif ($col === $priceCol) {
                    $rawPrice = payout_get_cell_value_as_string($cell);
                }
            }

            if (trim($ordernum) === '') {
                continue;
            }

            if (is_string($rawPrice)) {
                $rawPrice = trim($rawPrice);
            }

            if ($rawPrice === null || $rawPrice === '' || !is_numeric($rawPrice)) {
                $invalidPriceCount++;
                payout_add_result($response, $ordernum, false, "Order " . $ordernum . ": skipped - the price in this row was not a valid number.");
                continue;
            }

            $uploadedRows[] = ['ordernum' => $ordernum, 'price' => (float)$rawPrice];
        }
    }

    $sheetColumns[$sheetKey] = true;
    unset($spreadsheet);
}

if (empty($sheetColumns)) {
    $response['status'] = 0;
    $response['errorMsg'] = "❌ Error: Missing required columns (\"" . $orderHeaderText . "\" / \"" . $priceHeaderText . "\") in: " . implode(', ', $sheetsMissingHeaders);
    echo json_encode($response);
    exit;
}

payout_log_line('sheets_with_valid_headers=' . count($sheetColumns) . ' sheets_missing_headers=' . count($sheetsMissingHeaders));

if (empty($uploadedRows)) {
    $response['status'] = 0;
    $response['errorMsg'] = "No data rows found in the uploaded file.";
    echo json_encode($response);
    exit;
}

payout_log_line('data_rows_read=' . count($uploadedRows) . ' invalid_price_rows=' . $invalidPriceCount);

// --- group by order number, divide price across duplicate rows ---
$grouped = [];
foreach ($uploadedRows as $r) {
    $grouped[$r['ordernum']][] = $r['price'];
}

$dividedPrice = [];
foreach ($grouped as $ordernum => $prices) {
    $dividedPrice[$ordernum] = round(array_sum($prices) / count($prices), 2);
}

// --- batch match against tranfile1 ---
// $orderToDocnums: ordernum => list of docnums to update.
$orderNumbers = array_map('strval', array_keys($grouped));
$orderToDocnums = [];

if ($isShopee) {
    // one record per ordernum (earliest trndte, then lowest recid).
    // Exact match first (spaces included); trimmed match is only the fallback when there is no exact one.
    $searchValues = array_values(array_unique(array_merge($orderNumbers, array_map('trim', $orderNumbers))));

    $dbRows = [];
    foreach (array_chunk($searchValues, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $link->prepare("SELECT ordernum, docnum, trndte, recid FROM tranfile1 WHERE ordernum IN ($placeholders)");
        $stmt->execute($chunk);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dbRows[$row['recid']] = $row;
        }
    }
    foreach (payout_match_orders_to_docnums($orderNumbers, array_values($dbRows)) as $ordernum => $docnum) {
        $orderToDocnums[$ordernum] = [$docnum];
    }
} else {
    // Lazada/TikTok (original logic): every tranfile1 record with this ordernum.
    foreach (array_chunk($orderNumbers, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $link->prepare("SELECT ordernum, docnum FROM tranfile1 WHERE ordernum IN ($placeholders)");
        $stmt->execute($chunk);
        while ($row = $stmt->fetch()) {
            $orderToDocnums[$row['ordernum']][] = $row['docnum'];
        }
    }
}

// a docnum whose tranfile2 lines already have a non-empty untprc_before_payout went through a payout once - it is locked
// (empty string counts as not paid, so a live column with a '' default can't block every upload)
$allDocnums = [];
foreach ($orderToDocnums as $orderDocnums) {
    foreach ($orderDocnums as $docnum) {
        $allDocnums[$docnum] = true;
    }
}
payout_beaver_log('B2 matched', ['orders_in_file' => count($orderNumbers), 'orders_found_in_tranfile1' => count($orderToDocnums), 'docnums' => count($allDocnums)]);

// Shopee only: total item quantity per docnum - the order's payout is divided by this to get the per-unit price
$docnumQty = [];
foreach ($isShopee ? array_chunk(array_keys($allDocnums), 500) : [] as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
    $stmt = $link->prepare("SELECT docnum, SUM(itmqty) qty FROM tranfile2 WHERE docnum IN ($placeholders) GROUP BY docnum");
    $stmt->execute($chunk);
    while ($row = $stmt->fetch()) {
        $docnumQty[$row['docnum']] = (float)$row['qty'];
    }
}

// B3: how untprc_before_payout is actually stored on the matched docnums' lines
$beaverShapes = ['null' => 0, 'blank' => 0, 'zero' => 0, 'value' => 0];
$beaverSamples = [];
foreach (array_chunk(array_keys($allDocnums), 500) as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
    $stmt = $link->prepare("SELECT untprc_before_payout FROM tranfile2 WHERE docnum IN ($placeholders)");
    $stmt->execute($chunk);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $v = $row['untprc_before_payout'];
        if ($v === null) { $beaverShapes['null']++; }
        elseif (trim((string)$v) === '') { $beaverShapes['blank']++; }
        elseif ((float)$v == 0) { $beaverShapes['zero']++; }
        else { $beaverShapes['value']++; }
        $key = var_export($v, true);
        if (count($beaverSamples) < 5) { $beaverSamples[$key] = true; }
    }
}
payout_beaver_log('B3 untprc_before_payout shapes on matched lines', ['counts' => $beaverShapes, 'distinct_raw_samples' => array_keys($beaverSamples)]);

$alreadyPaidDocnums = [];
foreach (array_chunk(array_keys($allDocnums), 500) as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
    $stmt = $link->prepare("SELECT DISTINCT docnum FROM tranfile2 WHERE NULLIF(TRIM(untprc_before_payout), '') IS NOT NULL AND docnum IN ($placeholders)");
    $stmt->execute($chunk);
    while ($row = $stmt->fetch()) {
        $alreadyPaidDocnums[$row['docnum']] = true;
    }
}

$unmatched = [];
$alreadyUploadedCount = 0;
$zeroQtyCount = 0;
$docnumToPrice = [];

foreach ($orderNumbers as $ordernum) {
    if (!isset($orderToDocnums[$ordernum])) {
        $unmatched[] = $ordernum;
        payout_add_result($response, $ordernum, false, "Order " . $ordernum . ": no matching record found - skipped.");
        continue;
    }

    $rowCountNote = count($grouped[$ordernum]) > 1
        ? " - amount split evenly across " . count($grouped[$ordernum]) . " rows with this same order number in your file"
        : "";

    if (!$isShopee) {
        // Lazada/TikTok (original logic): the payout goes as-is to every not-yet-uploaded matching document
        $docnums = array_filter(array_unique($orderToDocnums[$ordernum]), function ($d) use ($alreadyPaidDocnums) {
            return !isset($alreadyPaidDocnums[$d]);
        });
        if (empty($docnums)) {
            $alreadyUploadedCount++;
            payout_add_result($response, $ordernum, false, "Order " . $ordernum . ": already uploaded - skipped (price not changed).");
            continue;
        }

        foreach ($docnums as $docnum) {
            $docnumToPrice[$docnum] = $dividedPrice[$ordernum];
        }

        payout_add_result(
            $response,
            $ordernum,
            true,
            "Order " . $ordernum . ": price updated to " . number_format($dividedPrice[$ordernum], 2) . " (Document No. " . implode(', ', $docnums) . ")" . $rowCountNote
        );
        continue;
    }

    $docnum = $orderToDocnums[$ordernum][0];
    if (isset($alreadyPaidDocnums[$docnum])) {
        $alreadyUploadedCount++;
        payout_add_result($response, $ordernum, false, "Order " . $ordernum . ": already uploaded - skipped (price not changed).");
        continue;
    }

    $totalQty = isset($docnumQty[$docnum]) ? $docnumQty[$docnum] : 0;
    if ($totalQty <= 0) {
        $zeroQtyCount++;
        payout_add_result($response, $ordernum, false, "Order " . $ordernum . ": total quantity is 0 - skipped (Document No. " . $docnum . ").");
        continue;
    }

    $orderPayout = $dividedPrice[$ordernum];
    $unitPrice = round($orderPayout / $totalQty, 2);
    $docnumToPrice[$docnum] = $unitPrice;

    payout_add_result(
        $response,
        $ordernum,
        true,
        "Order " . $ordernum . ": payout " . number_format($orderPayout, 2) . " / " . (0 + $totalQty) . " qty = " . number_format($unitPrice, 2) . " per unit (Document No. " . $docnum . ")" . $rowCountNote
    );
}

$matchedOrderCount = count($orderNumbers) - count($unmatched) - $alreadyUploadedCount - $zeroQtyCount;
payout_beaver_log('B4 lock check', ['already_paid_docnums' => count($alreadyPaidDocnums), 'unmatched_orders' => count($unmatched), 'already_uploaded_orders' => $alreadyUploadedCount, 'docnums_to_update' => count($docnumToPrice), 'sample_docnums_to_update' => array_slice(array_keys($docnumToPrice), 0, 5)]);
$updatedTranfile2Rows = 0;
$updatedTranfile1DocCount = 0;

// --- bulk update tranfile2 (untprc, then extprc = untprc * itmqty), then resync
// tranfile1.trntot = SUM(tranfile2.extprc) per docnum - all inside one transaction ---
if (!empty($docnumToPrice)) {
    $link->beginTransaction();
    try {
        foreach (array_chunk(array_keys($docnumToPrice), 500) as $chunkDocnums) {
            $caseSql = "CASE docnum ";
            $caseParams = [];
            foreach ($chunkDocnums as $docnum) {
                $caseSql .= "WHEN ? THEN ? ";
                $caseParams[] = $docnum;
                $caseParams[] = $docnumToPrice[$docnum];
            }
            $caseSql .= "ELSE untprc END";

            $inPlaceholders = implode(',', array_fill(0, count($chunkDocnums), '?'));

            // 1) save the old price, then untprc = the divided payout price for this docnum.
            // MySQL evaluates SET assignments left to right, so untprc_before_payout gets the
            // pre-override value. Only rows never paid out (IS NULL) are touched.
            $updateSql = "UPDATE tranfile2 SET untprc_before_payout = untprc, untprc = $caseSql WHERE docnum IN ($inPlaceholders) AND NULLIF(TRIM(untprc_before_payout), '') IS NULL";
            $updateParams = array_merge($caseParams, $chunkDocnums);
            $stmt = $link->prepare($updateSql);
            $stmt->execute($updateParams);
            $updatedTranfile2Rows += $stmt->rowCount();
            payout_beaver_log('B5 tranfile2 update chunk', ['docnums' => count($chunkDocnums), 'rows_updated' => $stmt->rowCount()]);

            // 2) extprc = untprc * itmqty, now that untprc is current (matches how extprc
            // is derived elsewhere in the app, e.g. sales_fileupload_ajax.php's inserts).
            // Rounded to 2dp so a fractional itmqty can't leave more than cents-precision.
            $stmt2 = $link->prepare("UPDATE tranfile2 SET extprc = ROUND(untprc * itmqty, 2) WHERE docnum IN ($inPlaceholders)");
            $stmt2->execute($chunkDocnums);

            // 3) tranfile1.trntot = sum of tranfile2.extprc for the same docnum, rounded to
            // 2dp to avoid float-summation drift beyond cents precision.
            $stmt3 = $link->prepare(
                "UPDATE tranfile1 SET trntot = (
                    SELECT ROUND(COALESCE(SUM(t2.extprc), 0), 2) FROM tranfile2 t2 WHERE t2.docnum = tranfile1.docnum
                 ) WHERE docnum IN ($inPlaceholders)"
            );
            $stmt3->execute($chunkDocnums);
            $updatedTranfile1DocCount += $stmt3->rowCount();
        }
        $link->commit();
        payout_beaver_log('B6 committed', ['tranfile2_rows' => $updatedTranfile2Rows, 'tranfile1_docs' => $updatedTranfile1DocCount]);
    } catch (\Throwable $e) {
        payout_beaver_log('B6 UPDATE FAILED - rolled back', $e->getMessage());
        $link->rollBack();
        $response['status'] = 0;
        $response['errorMsg'] = "A database error occurred while updating payout prices. No changes were saved.";
        echo json_encode($response);
        exit;
    }
}

$response['matched_order_count'] = $matchedOrderCount;
$response['unmatched_order_count'] = count($unmatched);
$response['already_uploaded_order_count'] = $alreadyUploadedCount;
$response['updated_tranfile2_rows'] = $updatedTranfile2Rows;
$response['updated_tranfile1_trntot_count'] = $updatedTranfile1DocCount;
$response['unmatched_ordernums'] = array_values($unmatched);

// failed entries first, same convention as the existing upload pages' result ordering
usort($response['results'], function ($a, $b) {
    $aFail = $a['success'] ? 1 : 0;
    $bFail = $b['success'] ? 1 : 0;
    return $aFail - $bFail;
});

payout_log_line('matched=' . $matchedOrderCount . ' unmatched=' . count($unmatched) . ' already_uploaded=' . $alreadyUploadedCount . ' tranfile2_rows_updated=' . $updatedTranfile2Rows . ' tranfile1_trntot_updated=' . $updatedTranfile1DocCount);

// --- audit log ---
$username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
$username_full_name = '';
if (isset($_SESSION['recid'])) {
    $stmt_u = $link->prepare('SELECT * FROM users where recid=?');
    $stmt_u->execute([$_SESSION['recid']]);
    $rs_u = $stmt_u->fetch();
    if ($rs_u) {
        $username_full_name = $rs_u['full_name'];
    }
}

$xremarks = "Uploaded payout file: " . $_FILES['xfile']['name'] . " (Platform: " . $platform_name . "), matched=" . $matchedOrderCount . ", unmatched=" . count($unmatched) . ", already uploaded=" . $alreadyUploadedCount . ", tranfile2 rows updated=" . $updatedTranfile2Rows;
PDO_UserActivityLog($link, $username_session, '', date('Y-m-d H:i:s'), 'SALES PAYOUT UPLOAD', 'upload', $username_full_name, $xremarks, 0, '', '', '', '', $username_session, '', $_FILES['xfile']['name']);

// so sales_payout_pdf.php can export the results without needing them re-posted
$_SESSION['sales_payout_result_summary'] = $response['results'];
$_SESSION['sales_payout_result_platform'] = $platform_name;
$_SESSION['sales_payout_result_generated_at'] = date('Y-m-d H:i:s');

echo json_encode($response);
