<?php
/**
 * Standalone CLI test for the iPad/mobile layout of critical_pdf.php.
 * Renders the real page against stubbed includes and an in-memory SQLite
 * itemfile (no real database), then checks the markup the card layout relies on.
 * Run: php tests/test_critical_pdf_mobile_layout.php [output.html]
 * Passing an output path also saves the rendered page (for screenshots).
 */

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/critical_pdf_layout_' . getmypid();
@mkdir($tmp . '/includes', 0777, true);

// Stub header: fake DB + real CSS/JS from the local server so the page can be previewed
file_put_contents($tmp . '/includes/main_header.php', <<<'PHP'
<?php
$link = new PDO('sqlite::memory:');
$link->exec("CREATE TABLE itemfile (itmcde TEXT, itmdsc TEXT, crt_minimal_order TEXT, crt_offline TEXT, crt_online TEXT, crt_discontinued TEXT, crt_remarks TEXT)");
$ins = $link->prepare('INSERT INTO itemfile VALUES (?,?,?,?,?,?,?)');
$ins->execute(['ITM 001', 'Alpha Widget', 'Y', 'N', 'Y', 'N', 'Order monthly']);
$ins->execute(['ITM002', 'Beta <Gadget> & Co', 'N', 'N', 'N', 'Y', '']);
for ($i = 3; $i <= 20; $i++) $ins->execute([sprintf('ITM%03d', $i), "Item number $i with a fairly long description", 'N', 'Y', 'N', 'N', '']);
?><!DOCTYPE html><html><head><base href="http://localhost/lrdprog/"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css"><link rel="stylesheet" href="bootstrap/icons/font/bootstrap-icons.css"><link rel="stylesheet" href="css/main.css">
<script src="js/jquery-3.5.1.min.js"></script></head><body>
PHP);
file_put_contents($tmp . '/includes/main_menu.php', '<style>.td_bl{width:0;padding:0}</style>'); // collapsed menu, as in includes/main_menu.php
file_put_contents($tmp . '/includes/main_footer.php', '</body></html>');
copy($root . '/critical_pdf.php', $tmp . '/critical_pdf.php');

$cwd = getcwd();
chdir($tmp);
ob_start();
include $tmp . '/critical_pdf.php';
$html = ob_get_clean();
chdir($cwd);
array_map('unlink', glob($tmp . '/includes/*'));
@rmdir($tmp . '/includes');
@unlink($tmp . '/critical_pdf.php');
@rmdir($tmp);

if (isset($argv[1])) file_put_contents($argv[1], $html);

$failures = 0;
function check($label, $condition)
{
    global $failures;
    echo ($condition ? 'PASS: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) $failures++;
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
$xp = new DOMXPath($dom);
$rows = $xp->query("//tr[contains(@class,'critical-setting-row')]");

check('One card/row rendered per item', $rows->length === 20);

$labels_ok = true;
foreach ($rows as $row) {
    $toggle_labels = [];
    foreach ($xp->query(".//label[contains(@class,'flag-toggle')]", $row) as $label) {
        $has_input = $xp->query(".//input[contains(@class,'critical-flag')]", $label)->length === 1;
        $toggle_labels[] = $has_input ? trim($xp->query(".//span[@class='flag-label']", $label)->item(0)->textContent) : '';
    }
    if ($toggle_labels !== ['Minimal Order', 'Offline', 'Online', 'Discontinued']) $labels_ok = false;
    if ($xp->query(".//label[.//span[@class='remarks-label'] and .//input[contains(@class,'critical-remarks')]]", $row)->length !== 1) $labels_ok = false;
}
check('Every item has 4 tappable labeled toggles (label wraps its switch) in order', $labels_ok);
check('Every item has a labeled remarks box', $labels_ok);

$first = $rows->item(0);
check('Saved flag values still render as checked/unchecked',
    $xp->query(".//input[@data-field='crt_minimal_order' and @checked]", $first)->length === 1
    && $xp->query(".//input[@data-field='crt_offline' and @checked]", $first)->length === 0);
check('Saved remarks still render', $xp->query(".//input[contains(@class,'critical-remarks')]", $first)->item(0)->getAttribute('value') === 'Order monthly');
check('Item code with a space is kept intact', $first->getAttribute('data-item-code') === 'ITM 001');

$second = $rows->item(1);
check('Discontinued item keeps its red highlight class', strpos($second->getAttribute('class'), 'is-discontinued') !== false);
check('Item names are HTML-escaped', strpos($html, 'Beta &lt;Gadget&gt; &amp; Co') !== false);

check('Search box width is no longer fixed inline (goes full width on phones)',
    strpos($xp->query("//div[contains(@class,'critical-settings-search')]")->item(0)->getAttribute('style'), 'max-width') === false);
check('Card layout media query covers iPad widths (<1200px)', strpos($html, '@media(max-width:1199.98px)') !== false);
check('Remarks Enter key saves instead of submitting the form', strpos($html, "e.key==='Enter'") !== false);
check('Unsaved changes are flagged', strpos($html, "addClass('is-dirty')") !== false && strpos($html, "removeClass('is-dirty')") !== false);

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
