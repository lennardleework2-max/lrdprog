<?php
/**
 * Standalone CLI test for paid_upload_stream_order_column() in sales_filepaidupload_ajax.php
 * (TikTok/Lazada xlsx: stream the sheet XML instead of PhpSpreadsheet::load(), which took ~67s
 * per load on a 14k-row TikTok "Order details" tab and got the request killed with HTTP 503).
 * Run: php tests/test_sales_filepaidupload_stream_order_column.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

// Load the real function from the ajax file (it can't be required - the file runs the upload).
$source = file_get_contents(__DIR__ . '/../sales_filepaidupload_ajax.php');
foreach (['paid_upload_stream_log', 'paid_upload_stream_order_column'] as $fn) {
    preg_match('/^function ' . $fn . '\(.*?^}\s*$/ms', $source, $m);
    eval($m[0]);
}

$failures = 0;

function check($label, $condition)
{
    global $failures;
    if ($condition) {
        echo "PASS: $label\n";
    } else {
        echo "FAIL: $label\n";
        $failures++;
    }
}

$tmpDir = sys_get_temp_dir();

// --- 1. PhpSpreadsheet-written workbook: target tab isn't first, mixed-case header, gaps, numbers ---
$book = new Spreadsheet();
$book->getActiveSheet()->setTitle('Reports')->fromArray([['Order/adjustment ID'], ['WRONG-TAB']]);
$details = $book->createSheet()->setTitle('Order details');
$details->fromArray([
    ['Type', 'Order/Adjustment ID', 'Amount'],
    ['Order', '580000000000000001', 10],
    ['Order', null, 20],                       // empty order cell - skipped
    ['Order', '580000000000000003', 30],
    ['Order', '580000000000000003', 30],       // duplicates kept (lookup dedupes later)
]);
$details->setCellValue('B7', '580000000000000007');  // gap row 6
$details->setCellValue('B8', 12345);                 // numeric cell
$details->setCellValue('C9', '=1+1');                // formula row without an order number
$book->createSheet()->setTitle('Withdrawal records');

$file1 = $tmpDir . '/paid_stream_test1.xlsx';
(new XlsxWriter($book))->save($file1);

$values = paid_upload_stream_order_column($file1, 'Order details', 'Order/adjustment ID');
check('named sheet: reads only the order column, skips empties', $values === [
    '580000000000000001', '580000000000000003', '580000000000000003', '580000000000000007', '12345',
]);
check('null sheet name falls back to first sheet', paid_upload_stream_order_column($file1, null, 'Order/adjustment ID') === ['WRONG-TAB']);
check('missing header returns null', paid_upload_stream_order_column($file1, 'Order details', 'Order Number') === null);
check('unknown sheet returns null', paid_upload_stream_order_column($file1, 'Nope', 'Order/adjustment ID') === null);
check('non-xlsx file returns null', paid_upload_stream_order_column(__FILE__, null, 'x') === null);

// --- 2. Same result as PhpSpreadsheet on a larger generated sheet ---
$book = new Spreadsheet();
$sheet = $book->getActiveSheet()->setTitle('Order details');
$rows = [['Settlement', 'Order/adjustment ID', 'Fee']];
for ($i = 1; $i <= 2000; $i++) {
    $rows[] = ['S' . $i, ($i % 97 === 0) ? null : 'ORD' . str_pad($i, 8, '0', STR_PAD_LEFT), $i * 1.5];
}
$sheet->fromArray($rows);
$file2 = $tmpDir . '/paid_stream_test2.xlsx';
(new XlsxWriter($book))->save($file2);

$expected = [];
$loaded = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load($file2)->getActiveSheet();
foreach ($loaded->getRowIterator(2) as $row) {
    $v = $loaded->getCell('B' . $row->getRowIndex())->getValue();
    if (!empty($v)) {
        $expected[] = $v;
    }
}
check('matches PhpSpreadsheet on 2000 rows', paid_upload_stream_order_column($file2, 'Order details', 'Order/adjustment ID') === $expected);

// --- 3. Hand-built xlsx: inline strings, no "r" attributes, absolute rel targets, rich text ---
$file3 = $tmpDir . '/paid_stream_test3.xlsx';
@unlink($file3);
$zip = new ZipArchive();
$zip->open($file3, ZipArchive::CREATE);
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Order details" sheetId="1" r:id="rId1"/></sheets></workbook>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="/xl/worksheets/data.xml" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"/><Relationship Id="rId2" Target="/xl/strings.xml" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings"/></Relationships>');
$zip->addFromString('xl/strings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><r><t>Order/</t></r><r><t>adjustment ID </t></r></si><si><t>SS-1</t></si></sst>');
$zip->addFromString('xl/worksheets/data.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
    . '<row><c t="inlineStr"><is><t>Type</t></is></c><c t="s"><v>0</v></c></row>'
    . '<row><c t="inlineStr"><is><t>x</t></is></c><c t="s"><v>1</v></c></row>'
    . '<row><c t="inlineStr"><is><t>x</t></is></c><c t="inlineStr"><is><t>INL-2</t></is></c></row>'
    . '<row><c t="inlineStr"><is><t>INL-WRONG-COL</t></is></c></row>'
    . '<row><c/><c t="str"><f>A1</f><v>FORMULA-4</v></c></row>'
    . '</sheetData></worksheet>');
$zip->close();
check('inline strings / no r attrs / absolute targets / rich-text header',
    paid_upload_stream_order_column($file3, 'Order details', 'Order/adjustment ID') === ['SS-1', 'INL-2', 'FORMULA-4']);

// --- 4. The real TikTok export that caused the 503 (skipped if not present) ---
$realFile = __DIR__ . '/../income_20261005114908(UTC+8).xlsx';
if (is_file($realFile)) {
    $t = microtime(true);
    $values = paid_upload_stream_order_column($realFile, 'Order details', 'Order/adjustment ID');
    $elapsed = microtime(true) - $t;
    echo sprintf("      real TikTok file: %d order values in %.2fs, peak memory %.1fMB\n", is_array($values) ? count($values) : -1, $elapsed, memory_get_peak_usage(true) / 1048576);
    check('real TikTok file: header found, rows read', is_array($values) && count($values) > 10000);
    check('real TikTok file: under 20s (was ~67s x2)', $elapsed < 20);
} else {
    echo "SKIP: real TikTok file not present\n";
}

// --- 5. Apache condition: libxml external loader disabled (what PhpSpreadsheet does under
//        apache2handler) made XMLReader::open('zip://...') fail -> "Missing required columns" ---
if (PHP_VERSION_ID < 80000) {
    $prevLoader = libxml_disable_entity_loader(true);
    check('works with libxml external loader disabled (Apache upload)',
        paid_upload_stream_order_column($file3, 'Order details', 'Order/adjustment ID') === ['SS-1', 'INL-2', 'FORMULA-4']);
    libxml_disable_entity_loader($prevLoader);
}

@unlink($file1);
@unlink($file2);
@unlink($file3);

echo $failures === 0 ? "\nALL TESTS PASSED\n" : "\n$failures TEST(S) FAILED\n";
exit($failures === 0 ? 0 : 1);
