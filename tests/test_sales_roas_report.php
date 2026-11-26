<?php
/**
 * Standalone CLI test for sales_roas_report_ajax.php / sales_roas_report_rep.php.
 * Uses an in-memory SQLite database with fake rows - never touches the real DB, session or $_POST.
 * Run: php tests/test_sales_roas_report.php
 */

define('SALES_ROAS_REPORT_LIB_ONLY', true);
define('SALES_ROAS_REPORT_REP_LIB_ONLY', true);
require_once __DIR__ . '/../ezpdfclass/class/class.ezpdf.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../sales_roas_report_ajax.php';
require_once __DIR__ . '/../sales_roas_report_rep.php';

$failures = 0;
function check($label, $ok)
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label\n";
    if (!$ok) {
        $failures++;
    }
}

// --- fake data -------------------------------------------------------------
$link = new PDO('sqlite::memory:');
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$link->exec("CREATE TABLE mf_platform (platform_id TEXT, platform_desc TEXT)");
$link->exec("CREATE TABLE mf_platform_shop (platform_shop_id TEXT, platform_id TEXT, platform_shop_title TEXT)");
$link->exec("CREATE TABLE tranfile1 (docnum TEXT, trncde TEXT, trndte TEXT)");
$link->exec("CREATE TABLE tranfile2 (recid INTEGER PRIMARY KEY AUTOINCREMENT, docnum TEXT, platform_shop_id TEXT, extprc REAL)");

$link->exec("INSERT INTO mf_platform VALUES ('P1','SHOPEE'), ('P2','LAZADA')");
$link->exec("INSERT INTO mf_platform_shop VALUES
    ('PFS-1','P1','RYU Shopee'),
    ('PFS-2','P1','Pro Shopee'),
    ('PFS-3','P2','RYU Lazada')");

$link->exec("INSERT INTO tranfile1 VALUES
    ('SAL-1','SAL','2026-09-10'),
    ('SAL-2','SAL','2026-09-12'),
    ('SAL-3','SAL','2026-09-20'),
    ('SRT-1','SRT','2026-09-11')");
$link->exec("INSERT INTO tranfile2 (docnum, platform_shop_id, extprc) VALUES
    ('SAL-1','PFS-1', 1000.00),
    ('SAL-1','PFS-1', 500.00),
    ('SAL-2','PFS-2', 250.50),
    ('SAL-3','PFS-1', 999.99),
    ('SRT-1','PFS-1', 300.00)");

// --- fetch / aggregation -----------------------------------------------------
$all = sales_roas_report_fetch_shops($link, '', '');
check('every configured shop is returned, even with no sales (3 shops)', count($all) === 3);
check('ordered by platform then shop title (LAZADA before SHOPEE, alphabetical within it)', array_column($all, 'shop_title') === array('RYU Lazada', 'Pro Shopee', 'RYU Shopee'));

$byShop = array();
foreach ($all as $row) {
    $byShop[$row['platform_shop_id']] = $row;
}
check('online sales sums extprc across multiple SAL lines for the same shop (1000 + 500 + 999.99)', abs($byShop['PFS-1']['online_sales'] - 2499.99) < 0.001);
check('non-SAL transactions (SRT) are excluded from online sales', $byShop['PFS-1']['online_sales'] < 2499.99 + 300);
check('a shop with zero matching sales still shows online_sales = 0', $byShop['PFS-3']['online_sales'] === 0.0);
check('Pro Shopee only counts its own line (250.50)', $byShop['PFS-2']['online_sales'] === 250.5);

$ranged = sales_roas_report_fetch_shops($link, '2026-09-15', '');
$byShopRanged = array();
foreach ($ranged as $row) {
    $byShopRanged[$row['platform_shop_id']] = $row;
}
check('Date From filters out earlier SAL rows (RYU Shopee keeps only the 999.99 line)', $byShopRanged['PFS-1']['online_sales'] === 999.99);
check('a shop with no sales in range still appears (Pro Shopee = 0 after 09-15)', $byShopRanged['PFS-2']['online_sales'] === 0.0);

$boundedOut = sales_roas_report_fetch_shops($link, '2026-09-10', '2026-09-10');
$byShopBounded = array();
foreach ($boundedOut as $row) {
    $byShopBounded[$row['platform_shop_id']] = $row;
}
check('Date range is inclusive on both ends (09-10 to 09-10 keeps the 1000+500 lines only)', $byShopBounded['PFS-1']['online_sales'] === 1500.0);

// --- ROAS calc ---------------------------------------------------------------
check('ROAS = valid / spend', sales_roas_report_compute_roas(600, 100) === 6.0);
check('ROAS rounds to 1 decimal', sales_roas_report_compute_roas(100, 30) === 3.3);
check('zero spend -> null (no ratio)', sales_roas_report_compute_roas(500, 0) === null);
check('blank spend -> null', sales_roas_report_compute_roas(500, '') === null);
check('fmt_roas prints "-" for null', sales_roas_report_fmt_roas(null) === '-');
check('fmt_roas prints one decimal', sales_roas_report_fmt_roas(6.0) === '6.0');

// --- row building / totals ---------------------------------------------------
$rows = sales_roas_report_build_rows(
    array('RYU Shopee', '', 'Pro Shopee'),
    array('1000', '', '250.5'),
    array('50', '', ''),
    array('600', '', '0'),
    array('100', '', '0')
);
check('blank shop title rows are skipped (2 rows kept)', count($rows) === 2);
check('missing/blank numeric fields default to 0', $rows[1]['credit'] === 0.0 && $rows[1]['roas'] === null);
check('first row ROAS computed (600/100 = 6.0)', $rows[0]['roas'] === 6.0);

$totals = sales_roas_report_totals($rows);
check('totals sum online across rows (1000 + 250.5)', $totals['online'] === 1250.5);
check('totals sum valid/spend then divide once for overall ROAS, not an average of row ROAS', $totals['valid'] === 600.0 && $totals['spend'] === 100.0 && $totals['roas'] === 6.0);

$emptyTotals = sales_roas_report_totals(array());
check('totals on no rows is all zero with a null ROAS', $emptyTotals['online'] === 0.0 && $emptyTotals['roas'] === null);

// --- PDF ----------------------------------------------------------------------
$pdf = new Cezpdf('Letter', 'landscape');
sales_roas_report_build_pdf($pdf, $rows, $totals, 'tester', 'today', 'Date Range : 09/01/2026 to 09/14/2026');
$out = $pdf->ezOutput();
check('PDF for sample rows is a valid PDF, single page', substr($out, 0, 4) === '%PDF' && preg_match_all('#/Type /Page[^s]#', $out) === 1);

$manyRows = array();
for ($i = 1; $i <= 60; $i++) {
    $manyRows[] = array('shop' => 'Shop ' . $i, 'online' => 100.0, 'credit' => 10.0, 'valid' => 200.0, 'spend' => 40.0, 'roas' => 5.0);
}
$pdf = new Cezpdf('Letter', 'landscape');
sales_roas_report_build_pdf($pdf, $manyRows, sales_roas_report_totals($manyRows), 'tester', 'today', 'Date Range : 09/01/2026 to 09/14/2026');
$out = $pdf->ezOutput();
check('PDF with many rows spans multiple pages', substr($out, 0, 4) === '%PDF' && preg_match_all('#/Type /Page[^s]#', $out) > 1);

$pdf = new Cezpdf('Letter', 'landscape');
sales_roas_report_build_pdf($pdf, array(), $emptyTotals, 'tester', 'today', 'Date Range : 09/01/2026 to 09/14/2026');
$out = $pdf->ezOutput();
check('PDF with no rows still builds (No records found.)', substr($out, 0, 4) === '%PDF');

// --- XLS ------------------------------------------------------------------
$sheet = sales_roas_report_build_sheet($rows, $totals, 'tester', 'today', 'Date Range : 09/01/2026 to 09/14/2026')->getActiveSheet();
check('sheet header row', $sheet->getCell('A6')->getValue() === 'Shop' && $sheet->getCell('F6')->getValue() === 'ROAS');
check('sheet first data row', $sheet->getCell('A7')->getValue() === 'RYU Shopee' && (float)$sheet->getCell('B7')->getValue() === 1000.0 && (float)$sheet->getCell('F7')->getValue() === 6.0);
check('sheet second data row shows "-" for a null ROAS', $sheet->getCell('F8')->getValue() === '-');
check('sheet grand total row', $sheet->getCell('A9')->getValue() === 'Total' && (float)$sheet->getCell('B9')->getValue() === 1250.5 && (float)$sheet->getCell('F9')->getValue() === 6.0);

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
