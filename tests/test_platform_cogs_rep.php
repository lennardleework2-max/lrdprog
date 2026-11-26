<?php
/**
 * Standalone CLI test: Platform COGS Report (platform_cogs_rep.php).
 * Loads the report's pure helper functions from source and runs them on in-memory data,
 * plus runs the report's purchase fallback query on in-memory SQLite - never touches the real DB.
 * Run: php tests/test_platform_cogs_rep.php
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
function near($a, $b)
{
    return abs($a - $b) < 0.0001;
}

$src = file_get_contents(__DIR__ . '/../platform_cogs_rep.php');
$names = array('platform_cogs_is_pcs', 'platform_cogs_line_conversion', 'platform_cogs_unit_costs',
    'platform_cogs_build_groups', 'platform_cogs_sort_shops');
$code = '';
foreach ($names as $name) {
    preg_match('/    function ' . $name . '\(.*?\r?\n    \}\r?\n/s', $src, $m);
    check("helper $name found", !empty($m[0]));
    $code .= isset($m[0]) ? $m[0] : '';
}
eval($code);

check('uses Cost price type from mf_salesitemprice', strpos($src, "salesprice_desc = 'Cost'") !== false);
check('pcs code looked up from itemunitmeasurefile', strpos($src, "LOWER(TRIM(unmdsc)) = 'pcs'") !== false);

// ---- purchase fallback query: latest tran date wins, not highest recid ----
preg_match('/\$pur_cost_query = "(.*?)";/s', $src, $q);
check('purchase query found', !empty($q[1]));
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE tranfile1 (docnum TEXT, trncde TEXT, trndte TEXT)");
$db->exec("CREATE TABLE tranfile2 (recid INTEGER, docnum TEXT, itmcde TEXT, unmcde TEXT, untprc REAL)");
$future = date('Y-m-d', strtotime('+5 days'));
$db->exec("INSERT INTO tranfile1 VALUES ('P1','PUR','2026-01-01'), ('P2','PUR','2026-02-01'),
    ('P0','PUR','2025-12-01'), ('PF','PUR','$future'), ('S1','SAL','2026-03-01')");
$db->exec("INSERT INTO tranfile2 VALUES
    (10,'P1','A','PC',4), (11,'P2','A','PC',5), (30,'P0','A','PC',99), (31,'PF','A','PC',88), (12,'S1','A','PC',500),
    (13,'P1','B','BX',100), (14,'P2','B','BX',120), (15,'P1','B','PC',9),
    (16,'P2','C','BX',200),
    (17,'P2','D','PK',50)");
$stmt = $db->prepare(str_replace('$pur_placeholders', '?,?,?,?,?', $q[1]));
$stmt->execute(array(date('Y-m-d'), 'A', 'B', 'C', 'D', 'E'));
$pur_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pcs = 'PC';
$conv_map = array('B|BX' => 10, 'C|BX' => 20, 'Z|BX' => 10);
$cost_cache = array('Z' => '5.00', 'A' => '0');
$costs = platform_cogs_unit_costs(array('A', 'B', 'C', 'D', 'E', 'Z'), $cost_cache, $pur_rows, $pcs, $conv_map);

check('Cost price used when set (Z = 5)', near($costs['Z'], 5));
check('Cost 0 -> latest-dated pcs PUR (A = 5, not backdated 99, future 88 or sale 500)', near($costs['A'], 5));
check('pcs PUR wins over newer box PUR (B = 9)', near($costs['B'], 9));
check('no pcs PUR -> box price / conversion (C = 200/20 = 10)', near($costs['C'], 10));
check('only PUR is in a UOM with no conversion -> 0 (D)', near($costs['D'], 0));
check('no PUR at all -> 0 (E)', near($costs['E'], 0));

// ---- grouping ----
$sal_rows = array(
    array('itmcde' => 'Z', 'itmdsc' => 'Zeta', 'unmcde' => 'BX', 'itmqty' => 10, 'platform_upload' => 'LAZADA', 'platform_shop_title' => 'Shop A'),
    array('itmcde' => 'A', 'itmdsc' => 'Alpha', 'unmcde' => 'PC', 'itmqty' => 3, 'platform_upload' => 'LAZADA', 'platform_shop_title' => 'Shop A'),
    array('itmcde' => 'A', 'itmdsc' => 'Alpha', 'unmcde' => 'PC', 'itmqty' => 2, 'platform_upload' => 'LAZADA', 'platform_shop_title' => 'Shop A'),
    array('itmcde' => 'C', 'itmdsc' => 'Gamma', 'unmcde' => 'BX', 'itmqty' => 1, 'platform_upload' => 'LAZADA', 'platform_shop_title' => ''),
    array('itmcde' => 'Z', 'itmdsc' => 'Zeta', 'unmcde' => 'PC', 'itmqty' => 4, 'platform_upload' => 'SHOPEE', 'platform_shop_title' => 'Shop B'),
    array('itmcde' => 'Z', 'itmdsc' => 'Zeta', 'unmcde' => 'PACK', 'itmqty' => 7, 'platform_upload' => 'SHOPEE', 'platform_shop_title' => 'Shop B'),
    array('itmcde' => 'A', 'itmdsc' => 'Alpha', 'unmcde' => '', 'itmqty' => 1, 'platform_upload' => 'SHOPEE', 'platform_shop_title' => 'Shop B'),
);
$g = platform_cogs_build_groups($sal_rows, $costs, $pcs, $conv_map);
$laz = $g['groups']['LAZADA'];
$shopA = $laz['shops']['Shop A'];

check('box sale converted to pcs: 10 box x 10 = 100 pcs', near($shopA['items']['Z']['qty'], 100));
check('box sale COGS = 100 pcs x 5 = 500', near($shopA['items']['Z']['total'], 500));
check('cost column is per pc', near($shopA['items']['Z']['cost'], 5));
check('same item lines summed (A qty 5, total 25)', near($shopA['items']['A']['qty'], 5) && near($shopA['items']['A']['total'], 25));
check('items sorted by description', array_keys($shopA['items']) === array('A', 'Z'));
check('shop subtotal = 525', near($shopA['total'], 525));
check('blank shop -> No Shop Assigned Yet', isset($laz['shops']['No Shop Assigned Yet']));
check('unassigned shop total = 20 pcs x 10 = 200', near($laz['shops']['No Shop Assigned Yet']['total'], 200));
check('platform total = 725', near($laz['total'], 725));
check('platform qty = 125 pcs', near($laz['qty'], 125));
check('lines with no conversion excluded (PACK + blank UOM)', $g['excluded_count'] === 2);
check('SHOPEE total only counts the pcs line (4 x 5 = 20)', near($g['groups']['SHOPEE']['total'], 20));
check('grand total = 745', near($g['grand_total'], 745));
check('grand qty = 129', near($g['grand_qty'], 129));

$shops = platform_cogs_sort_shops(array('No Shop Assigned Yet' => 1, 'Zed' => 2, 'Abc' => 3));
check('No Shop Assigned Yet sorted last', array_keys($shops) === array('Abc', 'Zed', 'No Shop Assigned Yet'));

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);
