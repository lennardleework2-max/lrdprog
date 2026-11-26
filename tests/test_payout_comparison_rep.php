<?php
/**
 * Standalone CLI test for payout_comparison_rep.php.
 * Uses an in-memory SQLite database with fake rows - never touches the real DB, session or $_POST.
 * Run: php tests/test_payout_comparison_rep.php
 */

define('PAYOUT_COMPARISON_LIB_ONLY', true);
require_once __DIR__ . '/../ezpdfclass/class/class.ezpdf.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../payout_comparison_rep.php';

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
$link->exec("CREATE TABLE itemfile (itmcde TEXT, itmdsc TEXT)");
$link->exec("CREATE TABLE tranfile1 (docnum TEXT, trncde TEXT, ordernum TEXT, trndte TEXT)");
$link->exec("CREATE TABLE tranfile2 (recid INTEGER PRIMARY KEY AUTOINCREMENT, docnum TEXT, itmcde TEXT, untprc, untprc_before_payout)");

$link->exec("INSERT INTO itemfile VALUES ('I1','Item One'), ('I2','Item <Two> & Co')");
$link->exec("INSERT INTO tranfile1 VALUES
    ('SAL-1','SAL','ORD-B','2026-04-10'),
    ('SAL-2','SAL','ORD-A','2026-04-10'),
    ('SAL-3','SAL','ORD-C','2026-04-20'),
    ('SAL-4','SAL','ORD-D','2026-04-25'),
    ('SAL-5','SAL','ORD-E','2026-04-26'),
    ('SAL-6','SRT','ORD-F','2026-04-10')");
$link->exec("INSERT INTO tranfile2 (docnum, itmcde, untprc, untprc_before_payout) VALUES
    ('SAL-1','I1', 90.50, 100),
    ('SAL-2','I2', 120,   100),
    ('SAL-3','I1', 50,    50),
    ('SAL-4','I1', 70,    NULL),
    ('SAL-5','I1', 70,    ''),
    ('SAL-5','I2', 70,    '  '),
    ('SAL-6','I1', 10,    20)");

// --- filtering / ordering / difference ---------------------------------------
$all = payout_comparison_fetch_rows($link, '', '', array());
check('NULL, empty and blank untprc_before_payout rows are excluded, non-SAL excluded (3 rows left)', count($all) === 3);
check('ordered by date then order id (ORD-A, ORD-B, ORD-C)', array_column($all, 'ordernum') === array('ORD-A', 'ORD-B', 'ORD-C'));
check('difference = before - after (ORD-B: 100 - 90.50 = 9.50)', $all[1]['before'] === 100.0 && $all[1]['after'] === 90.5 && $all[1]['diff'] === 9.5);
check('negative difference kept (ORD-A: 100 - 120 = -20)', $all[0]['diff'] === -20.0);
check('zero difference kept (ORD-C: 50 - 50 = 0)', $all[2]['diff'] === 0.0);
check('angle brackets stripped from item text', $all[0]['item'] === 'Item Two & Co');

check('Date From only (>= 04-15) -> ORD-C', array_column(payout_comparison_fetch_rows($link, '2026-04-15', '', array()), 'ordernum') === array('ORD-C'));
check('Date To only (<= 04-15) -> ORD-A, ORD-B', array_column(payout_comparison_fetch_rows($link, '', '2026-04-15', array()), 'ordernum') === array('ORD-A', 'ORD-B'));
check('Date range is inclusive (04-10 to 04-10)', count(payout_comparison_fetch_rows($link, '2026-04-10', '2026-04-10', array())) === 2);
check('Item filter I2 -> ORD-A', array_column(payout_comparison_fetch_rows($link, '', '', array('I2')), 'ordernum') === array('ORD-A'));
check('Item filter I1 -> ORD-B, ORD-C', array_column(payout_comparison_fetch_rows($link, '', '', array('I1')), 'ordernum') === array('ORD-B', 'ORD-C'));
check('Multiple items I1 + I2 -> ORD-A, ORD-B, ORD-C', array_column(payout_comparison_fetch_rows($link, '', '', array('I1', 'I2')), 'ordernum') === array('ORD-A', 'ORD-B', 'ORD-C'));
check('Unknown item code -> empty', payout_comparison_fetch_rows($link, '', '', array('ZZ')) === array());
check('Date + multiple items (>= 04-15, I1 + I2) -> ORD-C', array_column(payout_comparison_fetch_rows($link, '2026-04-15', '', array('I1', 'I2')), 'ordernum') === array('ORD-C'));
check('Date + Item filter with no match -> empty', payout_comparison_fetch_rows($link, '2026-04-15', '', array('I2')) === array());

// --- posted item[] cleanup ---------------------------------------------------
check('item codes trimmed, blanks / non-scalars / duplicates dropped', payout_comparison_item_codes(array(' I1 ', '', 'I1', array('x'), 'I2')) === array('I1', 'I2'));
check('single scalar item code -> one-element array', payout_comparison_item_codes('I1') === array('I1'));
check('missing / null item -> empty array (all items)', payout_comparison_item_codes(null) === array() && payout_comparison_item_codes(array()) === array());

// --- totals ----------------------------------------------------------------
$totals = payout_comparison_totals($all);
check('grand total sales upload price = 250', $totals['before'] === 250.0);
check('grand total payout price = 260.5', $totals['after'] === 260.5);
check('grand total difference = -10.5', $totals['diff'] === -10.5);

// --- wrapping --------------------------------------------------------------
$pdf = new Cezpdf('Letter', 'landscape');
$pdf->selectFont(__DIR__ . '/../ezpdfclass/fonts/Helvetica.afm');
check('short text stays on one line', payout_comparison_wrap_lines($pdf, 'Item One', 320, 9) === array('Item One'));
$long = trim(str_repeat('Long item description ', 30));
$lines = payout_comparison_wrap_lines($pdf, $long, 320, 9);
$fits = true;
foreach ($lines as $line) {
    if ($pdf->getTextWidth(9, $line) > 320) {
        $fits = false;
    }
}
check('long text wraps to several lines that all fit the column', count($lines) > 1 && $fits);
$lines = payout_comparison_wrap_lines($pdf, str_repeat('W', 200), 320, 9);
$fits = true;
foreach ($lines as $line) {
    if ($pdf->getTextWidth(9, $line) > 320) {
        $fits = false;
    }
}
check('a single over-long word is hard-split to fit', count($lines) > 1 && $fits);

// --- PDF -------------------------------------------------------------------
$pdf = new Cezpdf('Letter', 'landscape');
payout_comparison_build_pdf($pdf, $all, 'tester', 'today');
$out = $pdf->ezOutput();
check('PDF for the 3 sample rows is a valid PDF, single page', substr($out, 0, 4) === '%PDF' && preg_match_all('#/Type /Page[^s]#', $out) === 1);

$many = array();
for ($i = 1; $i <= 200; $i++) {
    $many[] = array('ordernum' => 'ORD-' . $i, 'item' => 'Long item description number ' . $i . ' ' . str_repeat('word ', 40), 'before' => 100.0, 'after' => 90.0, 'diff' => 10.0);
}
$pdf = new Cezpdf('Letter', 'landscape');
payout_comparison_build_pdf($pdf, $many, 'tester', 'today');
$out = $pdf->ezOutput();
check('PDF with 200 wrapped rows spans multiple pages', substr($out, 0, 4) === '%PDF' && preg_match_all('#/Type /Page[^s]#', $out) > 5);

$pdf = new Cezpdf('Letter', 'landscape');
payout_comparison_build_pdf($pdf, array(), 'tester', 'today');
$out = $pdf->ezOutput();
check('PDF with no rows still builds (No records found.)', substr($out, 0, 4) === '%PDF');

// --- XLS -------------------------------------------------------------------
$sheet = payout_comparison_build_sheet($all, 'tester', 'today')->getActiveSheet();
check('sheet header row', $sheet->getCell('A5')->getValue() === 'Order ID' && $sheet->getCell('E5')->getValue() === 'Difference');
check('sheet first data row', $sheet->getCell('A6')->getValue() === 'ORD-A' && (float)$sheet->getCell('E6')->getValue() === -20.0);
check('sheet grand total row', $sheet->getCell('B9')->getValue() === 'Grand total:' && (float)$sheet->getCell('E9')->getValue() === -10.5);
$leading_zero = payout_comparison_build_sheet(array(array('ordernum' => '00123', 'item' => 'x', 'before' => 1.0, 'after' => 1.0, 'diff' => 0.0)), 'tester', 'today')->getActiveSheet();
check('numeric-looking order id keeps leading zeros', $leading_zero->getCell('A6')->getValue() === '00123');

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
