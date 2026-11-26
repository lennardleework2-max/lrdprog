<?php
/**
 * Standalone CLI test: balance_tracker_pdf.php is a copy of trn_salespayments.php with its own ajax
 * and permission, while the original Customer Payments page stays wired to its own files.
 * Reads source files only - never touches the DB, session or $_POST.
 * Run: php tests/test_balance_tracker_pdf.php
 */

$failures = 0;
function check($label, $ok)
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label\n";
    if (!$ok) {
        $failures++;
    }
}

$root = __DIR__ . '/..';
$page = file_get_contents($root . '/balance_tracker_pdf.php');
$ajax = file_get_contents($root . '/balance_tracker_pdf_ajax.php');
$orig_page = file_get_contents($root . '/trn_salespayments.php');
$orig_ajax = file_get_contents($root . '/trn_salespayments_ajax.php');

check('page calls its own ajax', strpos($page, "url: 'balance_tracker_pdf_ajax.php'") !== false);
check('page no longer calls the Customer Payments ajax', strpos($page, 'trn_salespayments_ajax.php') === false);
check('ajax checks the balance_tracker_pdf.php permission', strpos($ajax, "'balance_tracker_pdf.php'") !== false);
check('ajax does not check the Customer Payments permission', strpos($ajax, "'trn_salespayments.php'") === false);
check('ajax shares the balance logic lib', strpos($ajax, 'require_once("trn_salespayments_lib.php")') !== false);

// apart from the renamed references, the copies match the originals line for line
$normalize = function ($src) {
    return str_replace(
        array('balance_tracker_pdf_ajax', 'balance_tracker_pdf.php'),
        array('trn_salespayments_ajax', 'trn_salespayments.php'),
        $src
    );
};
check('page matches trn_salespayments.php apart from the ajax url', $normalize($page) === $orig_page);
check('ajax matches trn_salespayments_ajax.php apart from the renames', $normalize($ajax) === $orig_ajax);

check('original page still calls its own ajax', strpos($orig_page, "url: 'trn_salespayments_ajax.php'") !== false);
check('original ajax still checks its own permission', strpos($orig_ajax, "'trn_salespayments.php'") !== false);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
