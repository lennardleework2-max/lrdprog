<?php
/**
 * Standalone CLI test for the Salesman filter of sales_salesman_pdf.php (PDF and Excel share it).
 * Loads only the filter function - never touches the DB, session or $_POST.
 * Run: php tests/test_sales_salesman_pdf.php
 */

define('SALES_SALESMAN_PDF_LIB_ONLY', true);
require __DIR__ . '/../sales_salesman_pdf.php';

$failures = 0;
function check($label, $ok)
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label\n";
    if (!$ok) {
        $failures++;
    }
}

check('blank = every salesman (no filter)', sales_salesman_filter_sql('') === '');
check('"all" = every salesman (no filter)', sales_salesman_filter_sql('all') === '');
check('"All" with spaces = every salesman', sales_salesman_filter_sql(' All ') === '');
check('one salesman = filtered to that salesman', sales_salesman_filter_sql('SMN-00003') === " AND tranfile1.salesman_id='SMN-00003'");

// the dropdown on sales_salesman_rep.php offers "All" right after "-None"
$rep = file_get_contents(__DIR__ . '/../sales_salesman_rep.php');
check('dropdown has an All option', strpos($rep, "<option value='all'>All</option>") !== false);
check('All is placed after -None', preg_match("/=== '-None'\)\{\s*echo \"<option value='all'>All<\/option>\";/", $rep) === 1);

// --- customer filter ---
check('blank customer = no filter', sales_customer_filter_sql('') === '');
check('whitespace customer = no filter', sales_customer_filter_sql('  ') === '');
check('one customer = filtered by cuscde', sales_customer_filter_sql('CUS-001') === " AND tranfile1.cuscde='CUS-001'");
check('quote in cuscde is escaped', sales_customer_filter_sql("O'Neil") === " AND tranfile1.cuscde='O''Neil'");
check('rep page has the customer dropdown', strpos($rep, 'id="cus_search" name="cus_search"') !== false);
check('customer dropdown is searchable (Select2)', strpos($rep, "$('#cus_search').select2(") !== false);
check('customer dropdown is below salesman', strpos($rep, 'id="smn_search"') < strpos($rep, 'id="cus_search"'));
$pdf_src = file_get_contents(__DIR__ . '/../sales_salesman_pdf.php');
check('main query + count use customer filter', strpos($pdf_src, "\$xfilter_smn .= sales_customer_filter_sql(") !== false);
check('sales returns use customer filter', strpos(substr($pdf_src, strpos($pdf_src, 'function sales_return(')), 'sales_customer_filter_sql(') !== false);

// --- text fitting (replaces the old char-by-char trim_str loop) ---
$width_calls = 0;
$width_fn = function($s) use (&$width_calls){ $width_calls++; return strlen($s) * 5; }; // 5 units per char

// reference: the old trim_str algorithm, to prove the output is unchanged
function old_trim($string, $max_wid, $width_fn)
{
    $max_wid -= 5;
    $out = '';
    foreach (str_split((string)$string) as $ch) {
        if ($width_fn($out.$ch) > $max_wid) {
            return $out.'...';
        }
        $out .= $ch;
    }
    return $out;
}

check('short text is unchanged', sales_salesman_fit_text('ABC', 100, $width_fn) === 'ABC');
check('null becomes empty string', sales_salesman_fit_text(null, 100, $width_fn) === '');
check('empty stays empty', sales_salesman_fit_text('', 100, $width_fn) === '');

$same = true;
$sample = 'Juan Dela Cruz Trading - Brgy. San Isidro, Quezon City Payment via GCash ref 0012345';
foreach ([10, 20, 37, 70, 95, 120, 500] as $w) {
    for ($len = 0; $len <= strlen($sample); $len += 7) {
        $s = substr($sample, 0, $len);
        if (sales_salesman_fit_text($s, $w, $width_fn) !== old_trim($s, $w, $width_fn)) {
            $same = false;
        }
    }
}
check('long text cut matches old algorithm for many widths/lengths', $same);

$width_calls = 0;
sales_salesman_fit_text(str_repeat('x', 5000), 95, $width_fn);
check("5000-char string needs few width calls ($width_calls)", $width_calls <= 15);

// --- row limit (exports too big for the host are refused with a message) ---
check('limit is 15,000 rows', SALES_SALESMAN_MAX_ROWS === 15000);
check('0 rows can export', sales_salesman_row_limit_message(0) === '');
check('exactly 15,000 rows can export', sales_salesman_row_limit_message(15000) === '');
$msg = sales_salesman_row_limit_message(399867);
check('399,867 rows refused', $msg !== '');
check('message shows the count and limit', strpos($msg, '399,867') !== false && strpos($msg, '15,000') !== false);
check('15,001 rows refused', sales_salesman_row_limit_message(15001) !== '');

// report page pre-checks the count before opening the export
check('report page note matches the limit', strpos($rep, 'limited to ' . number_format(SALES_SALESMAN_MAX_ROWS) . ' records') !== false);
check('report page has the red alert box', strpos($rep, 'id="row_limit_alert"') !== false);
check('PDF and xls buttons both pre-check', substr_count($rep, 'if(!within_row_limit()){') === 2);

$src = file_get_contents(__DIR__ . '/../sales_salesman_pdf.php');
check('count check runs before the main query', strpos($src, 'sales_salesman_row_limit_message((int)') < strpos($src, '$main_rows = $stmt_main->fetchAll'));
check('redundant COUNT query removed', strpos($src, 'COUNT(*) as total_rows') === false);
check('time limit raised for large exports', strpos($src, 'set_time_limit(') !== false);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
