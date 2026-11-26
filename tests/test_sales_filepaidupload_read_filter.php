<?php
/**
 * Standalone CLI test for the TikTok/Lazada read filter in sales_filepaidupload_ajax.php
 * (load only the header row, then only the order-number column, so large "Order details"
 * tabs no longer exhaust PHP's memory_limit).
 * Run: php tests/test_sales_filepaidupload_read_filter.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// --- Copy of PaidUploadReadFilter from sales_filepaidupload_ajax.php ---
class PaidUploadReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
    public $column = null;

    public function readCell($columnAddress, $row, $worksheetName = '') {
        return $row == 1 || $columnAddress === $this->column;
    }
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

// Same two-step load + first-pass collection the ajax handler does for tiktok/lazada.
function collect_order_numbers_filtered($filePath, $headerName)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $reader->setLoadSheetsOnly('Order details');
    $filter = new PaidUploadReadFilter();
    $reader->setReadFilter($filter);

    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $orderNumberIndex = -999;
    $cellIterator = $sheet->getRowIterator(1)->current()->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(true);
    foreach ($cellIterator as $cell) {
        if (strcasecmp(trim($cell->getValue()), $headerName) === 0) {
            $orderNumberIndex = Coordinate::columnIndexFromString($cell->getColumn());
        }
    }

    $filter->column = Coordinate::stringFromColumnIndex($orderNumberIndex);
    $spreadsheet->disconnectWorksheets();
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $orderNumbers = [];
    foreach ($sheet->getRowIterator(2) as $row) {
        $value = $sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex())->getValue();
        if (!empty($value)) {
            $orderNumbers[] = (string)$value;
        }
    }
    return $orderNumbers;
}

// Child mode: load a file under the caller's memory_limit and report count/first/last order numbers.
if (isset($argv[1]) && $argv[1] === '--load') {
    $orders = collect_order_numbers_filtered($argv[2], 'Order/adjustment ID');
    echo count($orders) . ' ' . $orders[0] . ' ' . end($orders) . "\n";
    exit(0);
}

// --- 1. Real TikTok sample: filtered load returns the same order numbers as a full load ---
$sample = __DIR__ . '/../uploads_sales_test/paid/tiktok_paid_all_income_20250622042340.xlsx';
$fullReader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$fullReader->setReadDataOnly(true);
$fullReader->setLoadSheetsOnly('Order details');
$fullSheet = $fullReader->load($sample)->getActiveSheet();
$sampleHeader = trim($fullSheet->getCell('A1')->getValue());
$expected = [];
foreach ($fullSheet->getRowIterator(2) as $row) {
    $value = $fullSheet->getCell('A' . $row->getRowIndex())->getValue();
    if (!empty($value)) {
        $expected[] = (string)$value;
    }
}
$actual = collect_order_numbers_filtered($sample, $sampleHeader);
check('real TikTok sample has order numbers', count($expected) > 0);
check('real TikTok sample: filtered load matches full load', $actual === $expected);

// --- 2. Large synthetic TikTok file (12,000 rows x 46 columns, order number in column C)
//        loads inside a 64M memory_limit (a full load of this file needs ~180M) ---
$rows = 12000;
$tmp = tempnam(sys_get_temp_dir(), 'tt') . '.xlsx';
$book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$ws = $book->getActiveSheet();
$ws->setTitle('Order details');
$data = [];
$header = [];
for ($c = 1; $c <= 46; $c++) {
    $header[] = 'Col ' . $c;
}
$header[2] = 'Order/adjustment ID';
$data[] = $header;
for ($i = 0; $i < $rows; $i++) {
    $line = array_fill(0, 46, 'filler text ' . $i);
    $line[2] = (string)(580000000000000000 + $i);
    $data[] = $line;
}
$ws->fromArray($data, null, 'A1');
(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($tmp);
$book->disconnectWorksheets();
unset($book, $ws, $data);

// run the load in a fresh process so the writer's memory above doesn't count against it
$cmd = escapeshellarg(PHP_BINARY) . ' -d memory_limit=64M -d xdebug.mode=off '
    . escapeshellarg(__FILE__) . ' --load ' . escapeshellarg($tmp) . ' 2>&1';
$childOutput = trim(shell_exec($cmd));
@unlink($tmp);

check("large file loads under 64M and all $rows order numbers collected (got: $childOutput)",
    $childOutput === $rows . ' 580000000000000000 ' . (580000000000000000 + $rows - 1));

// --- 3. The client's real Oct 2026 export (14k rows x 63 cols, "Order/Adjustment ID" header)
//        loads inside a 128M memory_limit - the old full load died even at 256M ---
$clientFile = __DIR__ . '/../income_20261005114908(UTC+8).xlsx';
if (file_exists($clientFile)) {
    $cmd = escapeshellarg(PHP_BINARY) . ' -d memory_limit=128M -d xdebug.mode=off '
        . escapeshellarg(__FILE__) . ' --load ' . escapeshellarg($clientFile) . ' 2>&1';
    $childOutput = trim(shell_exec($cmd));
    check("client file loads under 128M with order numbers (got: " . substr($childOutput, 0, 120) . ")",
        preg_match('/^\d{4,} \d+ \d+$/', $childOutput) === 1);
} else {
    echo "SKIP: client file not present\n";
}

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
