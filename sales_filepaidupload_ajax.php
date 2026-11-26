<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/stdfunc100.php";
require "resources/lx2.pdodb.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

// TikTok/Lazada: load only the header row, then (once the order-number column is known) only
// that column. Loading every cell of a large "Order details" tab (46 columns x thousands of rows)
// exhausted PHP's memory_limit on the live server and the request died with an HTTP error.
class PaidUploadReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
    public $column = null;

    public function readCell($columnAddress, $row, $worksheetName = '') {
        return $row == 1 || $columnAddress === $this->column;
    }
}

$response = [
    "html" => "",
    "uniqueValues" => [], // Array to store unique values from column R
    "excelOutput" => [], // Array to store unique values from column R
    "items" => [], // Array to store item data with counts and details
    "msg" => "",
    "status" => 1,
    "retEdit" => [],
    "inputHTML" => "",
    "errorMsg" => "",
    "outputHTML" => "",
    "disabledDownload" => false,
    "disabledDownloadBackSlash" => false,
    "noMatchFile2" => [],
    "warningUpload" => 0
];

// TEMPORARY DIAGNOSTIC INSTRUMENTATION - remove once the slow-upload bottleneck is confirmed.
// Writes to its own dedicated log file per upload (not the shared PHP error log).
$t_start = microtime(true);
$t_prev = $t_start;

$paid_upload_log_dir = __DIR__ . '/logs';
if (!is_dir($paid_upload_log_dir)) {
    @mkdir($paid_upload_log_dir, 0777, true);
}
$paid_upload_log_file = $paid_upload_log_dir . '/paid_upload_timing_' . date('Ymd_His') . '_' . substr(microtime(), 2, 6) . '.log';

function paid_upload_log_timing($label, &$t_prev, $t_start) {
    global $paid_upload_log_file;
    $now = microtime(true);
    $line = sprintf('[PAID_UPLOAD_TIMING] %s | +%.3fs stage | %.3fs total' . PHP_EOL, $label, $now - $t_prev, $now - $t_start);
    @file_put_contents($paid_upload_log_file, $line, FILE_APPEND | LOCK_EX);
    $t_prev = $now;
}
paid_upload_log_timing('script start', $t_prev, $t_start);

// DIAGNOSTIC: pinpoint why $_FILES['xfile'] can be entirely missing on some hosts (vs. present
// with an error code). If $_POST is ALSO empty, the whole POST body was rejected before this
// script ran (post_max_size or max_input_time) - if $_POST is populated but $_FILES isn't,
// something is stripping just the file part (WAF/proxy/CDN limit).
@file_put_contents(
    $paid_upload_log_file,
    sprintf(
        '[PAID_UPLOAD_TIMING] request diagnostics | CONTENT_LENGTH=%s | post_max_size=%s | upload_max_filesize=%s | max_input_time=%s | max_execution_time=%s | FILES_keys=%s | POST_keys=%s' . PHP_EOL,
        isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 'n/a',
        ini_get('post_max_size'),
        ini_get('upload_max_filesize'),
        ini_get('max_input_time'),
        ini_get('max_execution_time'),
        implode(',', array_keys($_FILES)) ?: '(none)',
        implode(',', array_keys($_POST)) ?: '(none)'
    ),
    FILE_APPEND | LOCK_EX
);

if (isset($_FILES['xfile']) && $_FILES['xfile']['error'] === 0) {
    $filePath = $_FILES['xfile']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['xfile']['name'], PATHINFO_EXTENSION));
    
    $reader = null;

    // Choose the appropriate reader based on the file extension
    switch ($fileExtension) {
        case 'xlsx':
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            // Script only ever reads cell values (order numbers) - skipping style/format
            // parsing here is what was costing ~68s on real uploads (see logs/paid_upload_timing_*.log).
            $reader->setReadDataOnly(true);

            // PhpSpreadsheet::load() parses every sheet in the workbook by default, but this
            // script only ever reads certain tabs. Real exports can have several huge extra
            // tabs (e.g. a raw "Income" tab with 10,000+ rows) alongside small ones, which
            // dominated load time. Restrict load() to just the tabs we'll actually read, found
            // by peeking at the small xl/workbook.xml (not the large per-sheet data files).
            //
            // Shopee exports can contain multiple tabs (Summary, Income, Service Fee Details,
            // Adjustment, ...) and which one is "active" varies per export - loading only the
            // active tab caused "Order ID doesn't exist" errors when the active tab wasn't the
            // Income one. Instead, load every tab whose name contains "Income" (case-insensitive,
            // like SQL `%Income%`), so any number of matching sheets get processed.
            if (strtolower($_POST['platform_name']) === 'shopee') {
                $paid_upload_income_sheet_names = paid_upload_resolve_income_sheet_names($filePath);
                if (!empty($paid_upload_income_sheet_names)) {
                    $reader->setLoadSheetsOnly($paid_upload_income_sheet_names);
                }
                @file_put_contents(
                    $paid_upload_log_file,
                    sprintf(
                        '[PAID_UPLOAD_TIMING] income sheet peek | resolved=%s' . PHP_EOL,
                        empty($paid_upload_income_sheet_names) ? '(none found, loading full workbook)' : implode(', ', $paid_upload_income_sheet_names)
                    ),
                    FILE_APPEND | LOCK_EX
                );
            } else {
                // TikTok/Lazada exports can also have several tabs (e.g. TikTok: Order details,
                // Reports, Withdrawal records, Fees explanation), and the tab Excel/Sheets saved
                // as "active" isn't always the one we need - trusting it caused the same
                // "Order ID doesn't exist" failure Shopee had. Search by the known tab name for
                // each platform first, same concept as Shopee's income-tab search, and only fall
                // back to the active-tab bookmark if that name isn't found.
                $paid_upload_active_sheet_name = null;
                if (strtolower($_POST['platform_name']) === 'tiktok') {
                    $paid_upload_active_sheet_name = paid_upload_resolve_sheet_by_name($filePath, 'Order details');
                } elseif (strtolower($_POST['platform_name']) === 'lazada') {
                    $paid_upload_active_sheet_name = paid_upload_resolve_sheet_by_name($filePath, 'Transaction Overview');
                }
                if ($paid_upload_active_sheet_name === null) {
                    $paid_upload_active_sheet_name = paid_upload_resolve_active_sheet_name($filePath);
                }
                if ($paid_upload_active_sheet_name !== null) {
                    $reader->setLoadSheetsOnly($paid_upload_active_sheet_name);
                }
                @file_put_contents(
                    $paid_upload_log_file,
                    sprintf(
                        '[PAID_UPLOAD_TIMING] active sheet peek | resolved=%s' . PHP_EOL,
                        $paid_upload_active_sheet_name === null ? '(failed, loading full workbook)' : $paid_upload_active_sheet_name
                    ),
                    FILE_APPEND | LOCK_EX
                );
            }
            break;
        case 'csv':
            $fileContent = file_get_contents($filePath);

            // Detect and convert encoding to UTF-8
            $currentEncoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($currentEncoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $currentEncoding);
                file_put_contents($filePath, $fileContent);
            }

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(",");
            $reader->setEnclosure('"');
            $reader->setEscapeCharacter("\\");
            
            break;
        default:
            $response = [
                "msg" => "Unsupported file type",
                "status" => 0
            ];
            echo json_encode($response);
            exit;
    }
    
    // DIAGNOSTIC: isolate raw disk I/O speed from library/parsing speed. Reading a ~2MB file
    // should take milliseconds even on slow storage - if this alone is slow, the bottleneck
    // is the host's disk/CPU, not anything PhpSpreadsheet is doing.
    $t_io_start = microtime(true);
    $rawFileContents = file_get_contents($filePath);
    @file_put_contents(
        $paid_upload_log_file,
        sprintf(
            '[PAID_UPLOAD_TIMING] raw file_get_contents (pure disk I/O, no parsing) | %.3fs | %s bytes read' . PHP_EOL,
            microtime(true) - $t_io_start,
            strlen($rawFileContents)
        ),
        FILE_APPEND | LOCK_EX
    );
    unset($rawFileContents);

    $output = "";
    $total_lineitem = 0;

    $select_db_column_equivalent="SELECT * FROM sales_upload_column WHERE platform_desc = ?";
    $stmt_column_equivalent	= $link->prepare($select_db_column_equivalent);
    $stmt_column_equivalent->execute(['SALES-UPLOAD']);
    while($rs_column_equivalent = $stmt_column_equivalent->fetch())
    {
        if($rs_column_equivalent['dest_column_matched'] == "paid_ordernum"){
            $requiredHeaders[$rs_column_equivalent['dest_column_matched']] = $rs_column_equivalent[strtolower($_POST['platform_name']).'_column_matching'];
        }
    }

    // tiktok/lazada order numbers, filled either by the xlsx stream below or by the first pass further down
    $orderNumbersInFile = null;

    if ((strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada') && $fileExtension === 'xlsx') {

        // TikTok "Order details" tabs can be 14,000+ rows x 63 columns (~37MB of sheet XML).
        // PhpSpreadsheet builds every cell even with a read filter (~67s per load, and the
        // header + order-column approach needed two loads), so the host killed the request
        // with an HTTP 503. Streaming the XML and keeping only the order column takes seconds.
        $orderNumbersInFile = paid_upload_stream_order_column($filePath, $paid_upload_active_sheet_name, $requiredHeaders['paid_ordernum']);
        paid_upload_log_timing('tiktok/lazada: after streaming order-number column (xlsx)', $t_prev, $t_start);

        if ($orderNumbersInFile === null) {
            $response["status"] = 0;
            $response["errorMsg"] = "❌ Error: Missing required columns: " . $requiredHeaders['paid_ordernum'];

            echo json_encode($response);
            exit;
        }

    } else {

        $paidUploadReadFilter = null;
        if (strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada') {
            $paidUploadReadFilter = new PaidUploadReadFilter();
            $reader->setReadFilter($paidUploadReadFilter);
        }

        try {
            $spreadsheet = $reader->load($filePath);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $response = [
                "msg" => 'Error loading file: ' . $e->getMessage(),
                "status" => 0
            ];
            echo json_encode($response);
            exit;
        }
        paid_upload_log_timing('after reader->load (spreadsheet parse)', $t_prev, $t_start);

        $sheet = $spreadsheet->getActiveSheet();

        // DIAGNOSTIC: check for a "phantom used range" - a sheet reporting far more rows/columns
        // than it actually has data in, which forces the parser to walk many empty rows regardless
        // of setReadDataOnly (that flag skips style application, not row/cell iteration itself).
        @file_put_contents(
            $paid_upload_log_file,
            sprintf(
                '[PAID_UPLOAD_TIMING] sheet diagnostics | highestRow=%s | highestColumn=%s | sheetCount=%s | uploadedFileSize=%s bytes' . PHP_EOL,
                $sheet->getHighestRow(),
                $sheet->getHighestColumn(),
                $spreadsheet->getSheetCount(),
                filesize($filePath)
            ),
            FILE_APPEND | LOCK_EX
        );

        // DIAGNOSTIC: the active sheet is small, but load() parses ALL sheets in the workbook -
        // check whether one of the OTHER tabs (e.g. a raw-data/summary tab bundled into the export)
        // is much heavier and dominating the parse time.
        $allSheetsDiag = [];
        foreach ($spreadsheet->getAllSheets() as $idx => $otherSheet) {
            $allSheetsDiag[] = sprintf(
                '#%d "%s" %sx%s',
                $idx,
                $otherSheet->getTitle(),
                $otherSheet->getHighestRow(),
                $otherSheet->getHighestColumn()
            );
        }
        @file_put_contents(
            $paid_upload_log_file,
            '[PAID_UPLOAD_TIMING] all-sheets diagnostics | ' . implode(' | ', $allSheetsDiag) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        $row = $sheet->getRowIterator(1)->current(); // Get the first row
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true); // Loop only existing cells

        $foundHeaders = [];

        if(strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada'){
            foreach ($cellIterator as $cell) {

                // case-insensitive: newer TikTok exports say "Order/Adjustment ID", older ones "Order/adjustment ID"
                $cellValue = trim($cell->getValue());
                if (strcasecmp($cellValue, $requiredHeaders['paid_ordernum']) === 0) {
                    $foundHeaders[] = $requiredHeaders['paid_ordernum'];
                }
            }
        }
        // Shopee header detection happens per-sheet later (a workbook can have several
        // "Income"-named tabs), so it's intentionally skipped here and the missing-header
        // check below only applies to tiktok/lazada.

        // Find missing headers (shopee validates its "Income" sheets individually further down)
        if(strtolower($_POST['platform_name']) != 'shopee'){
            $missingHeaders = array_diff($requiredHeaders, $foundHeaders);

            if (!empty($missingHeaders)) {

                $response["status"] = 0;
                $response["errorMsg"] = "❌ Error: Missing required columns: " . implode(', ', $missingHeaders);

                echo json_encode($response);
                exit;
            }
        }
        paid_upload_log_timing('after header detection (sales_upload_column query + header scan)', $t_prev, $t_start);

        $orderNumberIndex = -999;

        if(strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada'){
            foreach ($cellIterator as $cell)
            {
                $cellValue = trim($cell->getValue());

                if (strcasecmp($cellValue, $requiredHeaders['paid_ordernum']) === 0) {
                    $orderNumberIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                }
            }

            // first load only had the header row - reload with just the order-number column
            $paidUploadReadFilter->column = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($orderNumberIndex);
            $spreadsheet->disconnectWorksheets();
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            paid_upload_log_timing('tiktok/lazada: after reload (order-number column only)', $t_prev, $t_start);

        }
        // Shopee's order-number column is detected per-sheet in its processing branch below.
    }


   $prevOrderNumber = NULL;
   $prevDocnum = NULL;

   $fileName = $_FILES['xfile']['name'];
   $current_dateTime = date('Y-m-d H:i:s');
   $file_batchno = $_POST['platform_name'].'_'.$fileName.'_'.$current_dateTime;

   $curent_date = date('Y-m-d');


   //xfilter
   $xfilter =  "";
   $xchecked_manual = true;
   if(!(isset($_POST['chk_manual']) && $_POST['chk_manual'] == 'checked')){
         $xfilter =  " AND platform_upload='".$_POST['platform_name']."'";
         $xchecked_manual = false;
   }

   //LOOOP ITERATION FOR TIKTOK
   if(strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada'){

        //FIRST PASS: collect every order number present in the file (xlsx already did this while streaming)
        if ($orderNumbersInFile === null) {
            $orderNumbersInFile = [];
            foreach($sheet->getRowIterator(2) as $row) {
                $orderNumber = getCellValueAsString($sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex()));
                if(empty($orderNumber)){
                    continue;
                }
                $orderNumbersInFile[] = $orderNumber;
            }
        }
        paid_upload_log_timing('tiktok/lazada: after first pass (collect order numbers, no DB)', $t_prev, $t_start);

        //BATCH LOOKUP: one (chunked) query instead of one query per row
        $tranfile1ByOrdernum = fetch_tranfile1_by_ordernums($link, $orderNumbersInFile, $xfilter);
        paid_upload_log_timing('tiktok/lazada: after batch SELECT lookup', $t_prev, $t_start);

        $recids_to_pay = [];
        $ordernums_to_sync = [];

        //SECOND PASS: build the response using the in-memory lookup
        foreach($orderNumbersInFile as $orderNumber) {

            //CHECK IF IT IS MATCHED
            $rs_checker = isset($tranfile1ByOrdernum[$orderNumber]) ? $tranfile1ByOrdernum[$orderNumber] : null;

            if(empty($rs_checker)){
                $response["noMatchFile2"][$orderNumber]["success"] = false;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = "Sales Order Number: <b>".$orderNumber."</b> doesn't exist";
                continue;
            }else{

                if(paid_upload_is_already_paid($rs_checker)){
                    $response["noMatchFile2"][$orderNumber]["success"] = false;
                    $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                    $response["noMatchFile2"][$orderNumber]["message"] = paid_upload_already_paid_message($orderNumber, $rs_checker);
                    continue;
                }

                $recids_to_pay[$rs_checker['recid']] = $rs_checker['recid'];

                if(!empty($rs_checker['file_batchno']) && !empty($rs_checker['platform_upload'])){
                    $ordernums_to_sync[$orderNumber] = $orderNumber;
                }

                $response["noMatchFile2"][$orderNumber]["success"] = true;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> set to paid.";
            }
        }
        paid_upload_log_timing('tiktok/lazada: after second pass (build response, no DB)', $t_prev, $t_start);

        //BATCH WRITE: mirrors the per-row updates that used to run inside the loop above
        apply_paid_updates($link, $recids_to_pay, $ordernums_to_sync, $curent_date, false);
        paid_upload_log_timing('tiktok/lazada: after batch UPDATE writes', $t_prev, $t_start);

   }else if(strtolower($_POST['platform_name']) == 'shopee'){

        // A Shopee export can have several tabs (Summary, Income, Service Fee Details,
        // Adjustment, ...) and which one is "active" varies per export. Process every
        // tab whose name contains "Income" (case-insensitive, like SQL `%Income%`)
        // instead of only the active one.
        $incomeSheets = [];
        foreach ($spreadsheet->getAllSheets() as $otherSheet) {
            if (stripos($otherSheet->getTitle(), 'income') !== false) {
                $incomeSheets[] = $otherSheet;
            }
        }
        if (empty($incomeSheets)) {
            $incomeSheets[] = $spreadsheet->getActiveSheet();
        }

        // Detect the order-number column per sheet (row 6); a tab without the required
        // header is skipped rather than failing the whole upload.
        $sheetOrderNumberIndex = [];
        foreach ($incomeSheets as $incomeSheet) {
            $headerRow = $incomeSheet->getRowIterator(6)->current();

            foreach ($headerRow->getCellIterator() as $cell) {
                $cellValue = trim($cell->getValue());
                if ($cellValue === $requiredHeaders['paid_ordernum']) {
                    $sheetOrderNumberIndex[$incomeSheet->getTitle()] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                    break;
                }
            }
        }

        if (empty($sheetOrderNumberIndex)) {
            $response["status"] = 0;
            $response["errorMsg"] = "❌ Error: Missing required columns: " . implode(', ', $requiredHeaders);
            echo json_encode($response);
            exit;
        }

        //FIRST PASS: collect every order number present across all matched sheets
        $orderNumbersInFile = [];
        foreach ($incomeSheets as $incomeSheet) {
            if (!isset($sheetOrderNumberIndex[$incomeSheet->getTitle()])) {
                continue;
            }
            $sheetOrderIndex = $sheetOrderNumberIndex[$incomeSheet->getTitle()];

            foreach ($incomeSheet->getRowIterator(7) as $row) {
                $orderNumber = getCellValueAsString($incomeSheet->getCellByColumnAndRow($sheetOrderIndex, $row->getRowIndex()));
                if(empty($orderNumber)){
                    continue;
                }
                $orderNumbersInFile[] = $orderNumber;
            }
        }
        paid_upload_log_timing('shopee: after first pass (collect order numbers across income sheets, no DB)', $t_prev, $t_start);

        //BATCH LOOKUP: one (chunked) query instead of one query per row
        $tranfile1ByOrdernum = fetch_tranfile1_by_ordernums($link, $orderNumbersInFile, $xfilter);
        paid_upload_log_timing('shopee: after batch SELECT lookup', $t_prev, $t_start);

        $recids_to_pay = [];
        $ordernums_to_sync = [];

        //SECOND PASS: build the response using the in-memory lookup
        foreach ($incomeSheets as $incomeSheet) {
            if (!isset($sheetOrderNumberIndex[$incomeSheet->getTitle()])) {
                continue;
            }
            $sheetOrderIndex = $sheetOrderNumberIndex[$incomeSheet->getTitle()];

            foreach ($incomeSheet->getRowIterator(7) as $row) {

                //GETS THE VALUE
                $orderNumber = getCellValueAsString($incomeSheet->getCellByColumnAndRow($sheetOrderIndex, $row->getRowIndex()));

                if(empty($orderNumber)){
                    continue;
                }

                //CHECK IF IT IS MATCHED
                $rs_checker = isset($tranfile1ByOrdernum[$orderNumber]) ? $tranfile1ByOrdernum[$orderNumber] : null;

                if(empty($rs_checker)){
                    $response["noMatchFile2"][$orderNumber]["success"] = false;
                    $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                    $response["noMatchFile2"][$orderNumber]["message"] = "Sales Order Number: <b>".$orderNumber."</b> doesn't exist";
                    continue;
                }else{

                    if(paid_upload_is_already_paid($rs_checker)){
                        $response["noMatchFile2"][$orderNumber]["success"] = false;
                        $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                        $response["noMatchFile2"][$orderNumber]["message"] = paid_upload_already_paid_message($orderNumber, $rs_checker);
                        continue;
                    }

                    $recids_to_pay[$rs_checker['recid']] = $rs_checker['recid'];

                    if(!empty($rs_checker['file_batchno']) && !empty($rs_checker['platform_upload'])){
                        $ordernums_to_sync[$orderNumber] = $orderNumber;
                    }

                    $response["noMatchFile2"][$orderNumber]["success"] = true;
                    $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                    $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> set to paid.";
                }
            }
        }
        paid_upload_log_timing('shopee: after second pass (build response, no DB)', $t_prev, $t_start);

        //BATCH WRITE: mirrors the per-row updates that used to run inside the loop above
        //tranfile1_debug must stay false - PDO_UpdateRecord's debug mode echoes a raw
        //var_dump() of the PDOStatement into the output, which corrupts the JSON response
        //(this was a pre-existing bug, only visible once real order numbers matched).
        apply_paid_updates($link, $recids_to_pay, $ordernums_to_sync, $curent_date, false);
        paid_upload_log_timing('shopee: after batch UPDATE writes', $t_prev, $t_start);
   }

} else {

    $response["status"] = 0;
    if (!isset($_FILES['xfile'])) {
        $response["errorMsg"] = "❌ No file was received by the server. If your file is large, it may exceed the server's upload size limit - please check with your host and try again.";
    } else {
        $response["errorMsg"] = "❌ File upload failed (error code " . $_FILES['xfile']['error'] . "). Please try again.";
    }
}

/**
 * Determine an xlsx file's true "active sheet" name by reading xl/workbook.xml directly,
 * without asking PhpSpreadsheet to parse the (potentially huge) per-sheet data files.
 * Mirrors PhpSpreadsheet's own resolution logic (Reader/Xlsx/WorkbookView.php), except that
 * class silently skips this resolution entirely when readDataOnly=true and always defaults to
 * sheet index 0 instead - reading the raw XML here avoids that gap.
 * Returns the sheet name, or null if it can't be determined for any reason (caller should then
 * fall back to a full, unrestricted load).
 */
function paid_upload_resolve_active_sheet_name($filePath) {
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

/**
 * Returns the workbook's actual sheet name that case-insensitively matches $targetName
 * (trimmed), read directly from xl/workbook.xml (same cheap technique as
 * paid_upload_resolve_active_sheet_name() / paid_upload_resolve_income_sheet_names()).
 * Returns null if no sheet matches or the name list can't be read.
 */
function paid_upload_resolve_sheet_by_name($filePath, $targetName) {
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

        foreach ($workbookXml->sheets->sheet as $sheetNode) {
            $sheetName = (string) $sheetNode['name'];
            if (strcasecmp(trim($sheetName), $targetName) === 0) {
                return $sheetName;
            }
        }

        return null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Returns every sheet name in the workbook containing "income" (case-insensitive substring,
 * like SQL `%Income%`), read directly from xl/workbook.xml so the large per-sheet data files
 * don't need to be parsed just to pick which sheets to load. Returns [] if none match or the
 * name list can't be read (caller should then fall back to loading the whole workbook).
 */
function paid_upload_resolve_income_sheet_names($filePath) {
    try {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }
        $xmlContents = $zip->getFromName('xl/workbook.xml');
        $zip->close();

        if ($xmlContents === false) {
            return [];
        }

        $workbookXml = @simplexml_load_string($xmlContents);
        if ($workbookXml === false || !isset($workbookXml->sheets->sheet)) {
            return [];
        }

        $incomeSheetNames = [];
        foreach ($workbookXml->sheets->sheet as $sheetNode) {
            $sheetName = (string) $sheetNode['name'];
            if (stripos($sheetName, 'income') !== false) {
                $incomeSheetNames[] = $sheetName;
            }
        }

        return $incomeSheetNames;
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Streams one sheet of an xlsx with XMLReader and returns the non-empty values (rows 2+) of the
 * column whose row-1 header case-insensitively matches $headerName, or null if that header
 * isn't found. Only the shared-strings table and the matched column are kept in memory.
 * $sheetName null = first sheet in the workbook.
 */
// BEAVER DEBUG: per-step trace of paid_upload_stream_order_column() into the per-upload timing log
function paid_upload_stream_log($message) {
    global $paid_upload_log_file;
    if (!empty($paid_upload_log_file)) {
        @file_put_contents($paid_upload_log_file, '[PAID_UPLOAD_STREAM] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function paid_upload_stream_order_column($filePath, $sheetName, $headerName) {
    paid_upload_stream_log(sprintf(
        'entry | filePath=%s | sheetName=%s | headerName=%s | PHP=%s | SAPI=%s | xmlreader=%s | zip=%s | cwd=%s',
        $filePath, var_export($sheetName, true), $headerName, PHP_VERSION, PHP_SAPI,
        var_export(extension_loaded('xmlreader'), true), var_export(extension_loaded('zip'), true), getcwd()
    ));

    $zip = new \ZipArchive();
    $zipOpen = $zip->open($filePath);
    paid_upload_stream_log('ZipArchive::open result=' . var_export($zipOpen, true));
    if ($zipOpen !== true) {
        return null;
    }
    $workbookXml = @simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
    $relsXml = @simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
    paid_upload_stream_log('workbook parsed=' . var_export($workbookXml !== false, true) . ' | rels parsed=' . var_export($relsXml !== false, true));
    if ($workbookXml === false || $relsXml === false || !isset($workbookXml->sheets->sheet)) {
        paid_upload_stream_log('RETURN NULL: workbook/rels missing or no <sheets>');
        return null;
    }

    // rel Targets are relative to xl/ ("worksheets/sheet1.xml") or absolute ("/xl/sharedStrings.xml")
    $relTargets = [];
    $sharedStringsPath = 'xl/sharedStrings.xml';
    foreach ($relsXml->Relationship as $rel) {
        $target = (string) $rel['Target'];
        $target = $target[0] === '/' ? ltrim($target, '/') : 'xl/' . $target;
        $relTargets[(string) $rel['Id']] = $target;
        if (substr((string) $rel['Type'], -13) === 'sharedStrings') {
            $sharedStringsPath = $target;
        }
    }
    paid_upload_stream_log('relTargets=' . json_encode($relTargets) . ' | sharedStringsPath=' . $sharedStringsPath);

    $sheetPath = null;
    foreach ($workbookXml->sheets->sheet as $sheetNode) {
        if ($sheetName === null || (string) $sheetNode['name'] === $sheetName) {
            $relId = (string) $sheetNode->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $sheetPath = isset($relTargets[$relId]) ? $relTargets[$relId] : null;
            break;
        }
    }
    if ($sheetPath === null) {
        $sheetNamesDiag = [];
        foreach ($workbookXml->sheets->sheet as $sheetNode) {
            $sheetNamesDiag[] = (string) $sheetNode['name'];
        }
        paid_upload_stream_log('RETURN NULL: sheet not matched | sheets in workbook=' . json_encode($sheetNamesDiag));
        return null;
    }
    paid_upload_stream_log('sheetPath=' . $sheetPath);

    // Entries are read with ZipArchive and handed to XMLReader::XML() instead of
    // XMLReader::open('zip://...'): under Apache (php7 apache2handler) PhpSpreadsheet's reader
    // disables libxml's external loader for the request, so open() of any URI fails with
    // "Unable to open source data" - the header then looked "missing".
    $sharedStringsXml = $zip->getFromName($sharedStringsPath);
    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();

    $sharedStrings = [];
    $xml = new \XMLReader();
    $sharedStringsOpen = $sharedStringsXml !== false && @$xml->XML($sharedStringsXml);
    unset($sharedStringsXml);
    paid_upload_stream_log('sharedStrings open | entry=' . $sharedStringsPath . ' | result=' . var_export($sharedStringsOpen, true)
        . ($sharedStringsOpen ? '' : ' | error_get_last=' . json_encode(error_get_last()) . ' | libxml_errors=' . json_encode(libxml_get_errors())));
    if ($sharedStringsOpen) {
        while ($xml->read()) {
            if ($xml->nodeType === \XMLReader::ELEMENT && $xml->localName === 'si') {
                $sharedStrings[] = $xml->readString(); // concatenates rich-text runs too
            }
        }
        $xml->close();
    }
    paid_upload_stream_log('sharedStrings loaded count=' . count($sharedStrings));

    $xml = new \XMLReader();
    $sheetOpen = $sheetXml !== false && @$xml->XML($sheetXml);
    paid_upload_stream_log('sheet open | entry=' . $sheetPath . ' | bytes=' . ($sheetXml === false ? 'n/a' : strlen($sheetXml)) . ' | result=' . var_export($sheetOpen, true)
        . ($sheetOpen ? '' : ' | error_get_last=' . json_encode(error_get_last()) . ' | libxml_errors=' . json_encode(libxml_get_errors())));
    if (!$sheetOpen) {
        paid_upload_stream_log('RETURN NULL: sheet XML could not be opened');
        return null;
    }

    $orderColumn = null;
    $values = [];
    $rowIndex = 0;
    $colIndex = 0;
    while ($xml->read()) {
        if ($xml->nodeType !== \XMLReader::ELEMENT) {
            continue;
        }
        if ($xml->localName === 'row') {
            $rowIndex = $xml->getAttribute('r') !== null ? (int) $xml->getAttribute('r') : $rowIndex + 1;
            $colIndex = 0;
            if ($rowIndex > 1 && $orderColumn === null) {
                break; // header row done without a match
            }
            continue;
        }
        if ($xml->localName !== 'c') {
            continue;
        }

        // the "r" attribute (e.g. "AB12") is optional - fall back to counting cells in the row
        $ref = $xml->getAttribute('r');
        $colIndex = $ref !== null
            ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(rtrim($ref, '0123456789'))
            : $colIndex + 1;

        if ($rowIndex > 1 && $colIndex !== $orderColumn) {
            continue;
        }

        // value lives in <v> (shared-string index or literal) or <is> (inline string); skip <f> formulas
        $value = '';
        foreach ($xml->expand()->childNodes as $child) {
            if ($child->localName === 'v' || $child->localName === 'is') {
                $value = $child->textContent;
            }
        }
        if ($xml->getAttribute('t') === 's') {
            $value = isset($sharedStrings[(int) $value]) ? $sharedStrings[(int) $value] : '';
        }

        if ($rowIndex === 1) {
            if ($orderColumn === null && strcasecmp(trim($value), $headerName) === 0) {
                $orderColumn = $colIndex;
            }
            paid_upload_stream_log('header cell | col=' . $colIndex . ' | type=' . var_export($xml->getAttribute('t'), true) . ' | value=' . json_encode($value) . ' | matched=' . var_export($orderColumn === $colIndex, true));
        } elseif (!empty($value)) {
            $values[] = $value;
        }
    }
    $xml->close();

    paid_upload_stream_log(sprintf(
        'exit | lastRowIndex=%d | orderColumn=%s | valuesCount=%d | first3=%s',
        $rowIndex, var_export($orderColumn, true), count($values), json_encode(array_slice($values, 0, 3))
    ));
    if ($orderColumn === null) {
        paid_upload_stream_log('RETURN NULL: header "' . $headerName . '" not found in row 1');
    }

    return $orderColumn === null ? null : $values;
}

function fetch_tranfile1_by_ordernums($link, $orderNumbers, $xfilter, $chunk_size = 500) {
    $lookup = [];

    $uniqueOrderNumbers = array_values(array_unique($orderNumbers));

    foreach (array_chunk($uniqueOrderNumbers, $chunk_size) as $chunk) {

        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $select_db_checker = "SELECT * FROM tranfile1 WHERE true " . $xfilter . " AND ordernum IN ($placeholders)";
        $stmt_checker = $link->prepare($select_db_checker);
        $stmt_checker->execute($chunk);

        while ($rs_checker = $stmt_checker->fetch()) {
            $ordernum = $rs_checker['ordernum'];
            // keep the first matching row per ordernum, same as the old per-row ->fetch()
            if (!array_key_exists($ordernum, $lookup)) {
                $lookup[$ordernum] = $rs_checker;
            }
        }
    }

    return $lookup;
}

// a record that already has a paydate (Customer Payments, manual Pay Date or an earlier upload) is left alone
function paid_upload_is_already_paid($rs) {
    $paydate = (string)$rs['paydate'];
    return $paydate !== '' && substr($paydate, 0, 10) !== '0000-00-00';
}

function paid_upload_already_paid_message($orderNumber, $rs) {
    return "Order Number: <b>".$orderNumber."</b> is already paid (".substr((string)$rs['paydate'], 0, 10).") - skipped";
}

function apply_paid_updates($link, $recids_to_pay, $ordernums_to_sync, $curent_date, $tranfile1_debug = false, $chunk_size = 500) {

    if (!empty($recids_to_pay)) {
        foreach (array_chunk(array_values($recids_to_pay), $chunk_size) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));

            $arr_tranfile1_upd = array();
            $arr_tranfile1_upd['paydate'] = $curent_date;
            $arr_tranfile1_upd['paydetails'] = 'Paid via file upload';
            PDO_UpdateRecord($link, 'tranfile1', $arr_tranfile1_upd, "recid IN ($placeholders)", $chunk, $tranfile1_debug);
        }
    }

    if (!empty($ordernums_to_sync)) {
        foreach (array_chunk(array_values($ordernums_to_sync), $chunk_size) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));

            $arr_upld_sales_upd = array();
            $arr_upld_sales_upd['paydate'] = $curent_date;
            PDO_UpdateRecord($link, 'upld_salesfile', $arr_upld_sales_upd, "ordernum IN ($placeholders)", $chunk, false);
        }
    }
}

function getCellValueAsString($cell) {
    // Ensure $cell is an instance of a Cell
    if ($cell instanceof \PhpOffice\PhpSpreadsheet\Cell\Cell) {
        $value = $cell->getValue();

        // Check if the value is a RichText object
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $value->getPlainText();
        }

        // Return raw value if not RichText
        return $value;
    }

    // If $cell is not an object or is not an instance of Cell, return as-is
    return $cell;
}


function parseOrders($input) {
    $xdata = [];

    // Use regular expression to find the order number from the input
    preg_match('/(#\d{4,5})/', $input, $orderNumMatch);
    $ordernum = $orderNumMatch[1] ?? null;  // Capture the order number

    // Use your existing regex to find each order in the input
    preg_match_all('/(\d+) order (.*?)(?=,|$)/', $input, $matches, PREG_SET_ORDER);

    // Iterate through each match to build the xdata array
    foreach ($matches as $match) {
        $quantity = (int)$match[1]; // Extract the quantity
        $name = trim($match[2]);     // Extract the product name

        // Use a unique key for each item by combining order number and name
        $uniqueKey = $name; // or you can use $ordernum . ' ' . $name;

        // Set the xdata array
        $xdata[$uniqueKey]['name'] = $name;
        $xdata[$uniqueKey]['qty'] = $quantity;
        $xdata[$uniqueKey]['ordernum'] = $ordernum; // Add order number
    }

    return $xdata;
}



paid_upload_log_timing('script end (about to json_encode response)', $t_prev, $t_start);

echo json_encode($response);
?>
