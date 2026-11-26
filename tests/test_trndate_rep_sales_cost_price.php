<?php
/**
 * Standalone CLI test: Sales Costing reports (trndate_rep_sales_cost.php, item_rep_sales_cost.php)
 * take unit cost from mf_salesitemprice where the price type (mf_salespricetype) is "Cost".
 * If that cost is missing or 0, they fall back to the untprc of the item's latest purchase
 * (tranfile1.trncde = 'PUR', tranfile1.trndte <= today, latest trndte then highest tranfile2.recid).
 * Runs the reports' cost queries against an in-memory SQLite DB - never touches the real DB.
 * Run: php tests/test_trndate_rep_sales_cost_price.php
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

foreach (array('trndate_rep_sales_cost.php', 'item_rep_sales_cost.php') as $file) {
    echo "\n== $file ==\n";
    $src = file_get_contents(__DIR__ . '/../' . $file);
    check('old helper removed', strpos($src, 'get_cached_unitcost') === false);
    check('uses mf_salesitemprice', strpos($src, 'FROM mf_salesitemprice sip') !== false);
    check('reads unit cost from cost cache by item code', strpos($src, "(float)\$cost_cache[") !== false);
    check('purchase fallback keyed by item + UOM', strpos($src, "\$pur_cost_key = \$pur_row['itmcde'].'|'.\$pur_row['unmcde'];") !== false);
    check('purchase fallback ordered by latest tran date', strpos($src, 'ORDER BY t1.trndte DESC, t2.recid DESC') !== false);
    check('first purchase row per item + UOM wins', strpos($src, 'if(!isset($pur_cost_cache[$pur_cost_key]))') !== false);
    check('purchase fallback used only when Cost is 0', strpos($src, 'if($unit_cost == 0 && isset($pur_cost_cache[$pur_cost_key]))') !== false);
    // Pull the exact queries out of the report source and run them.
    preg_match('/\$cost_query = "(.*?)";/s', $src, $m);
    preg_match('/\$pur_cost_query = "(.*?)";/s', $src, $p);
    check('cost query found in source', !empty($m[1]));
    check('purchase fallback query found in source', !empty($p[1]));
    run_cost_query_checks($m[1], $p[1]);
}

echo "\n== Show items with no cost (sales_cost_item_rep.php / item_rep_sales_cost.php) ==\n";
$form_src = file_get_contents(__DIR__ . '/../sales_cost_item_rep.php');
check('form has the "Show items with no cost" checkbox', strpos($form_src, 'name="show_no_cost"') !== false);
$rep_src = file_get_contents(__DIR__ . '/../item_rep_sales_cost.php');
check('report branches on show_no_cost', strpos($rep_src, "if(!empty(\$_POST['show_no_cost']))") !== false);
check('report prints "Items with no cost" heading', strpos($rep_src, 'Items with no cost') !== false);
// Load the report's real helper functions and run them.
preg_match('/function get_unit_cost\(.*?\r?\n    \}\r?\n/s', $rep_src, $f1);
preg_match('/function item_has_no_cost_sale\(.*?\r?\n    \}\r?\n/s', $rep_src, $f2);
check('helper functions found in report', !empty($f1[0]) && !empty($f2[0]));
eval($f1[0] . $f2[0]);
$cost_cache = array('A' => '12.50', 'D' => '0.00');
$pur_cost_cache = array('C|pcs' => 22.0, 'D|pcs' => 30.0);
$sold = array(array('unmcde' => 'pcs'));
check('item with a Cost price is not listed', !item_has_no_cost_sale('A', $sold, $cost_cache));
check('item with no Cost row is listed even if it has a purchase', item_has_no_cost_sale('C', $sold, $cost_cache));
check('item with Cost = 0 is listed even if it has a purchase', item_has_no_cost_sale('D', $sold, $cost_cache));
check('item with no Cost row and no purchase is listed', item_has_no_cost_sale('E', $sold, $cost_cache));
check('item with no sales in range is not listed', !item_has_no_cost_sale('E', array(), $cost_cache));

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);

// Mirrors the report flow: Cost price first, then latest purchase untprc for items with no/zero cost.
function run_cost_query_checks($query, $pur_query)
{
    $db = new PDO('sqlite::memory:');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE mf_salespricetype (salesprice_id INTEGER, salesprice_desc TEXT)");
    $db->exec("CREATE TABLE mf_salesitemprice (itmcde TEXT, salesprice_id INTEGER, price REAL)");
    $db->exec("CREATE TABLE tranfile1 (docnum TEXT, trncde TEXT, trndte TEXT)");
    $db->exec("CREATE TABLE tranfile2 (recid INTEGER, docnum TEXT, itmcde TEXT, unmcde TEXT, untprc REAL)");
    $db->exec("INSERT INTO mf_salespricetype VALUES (1, 'Distributor Price'), (7, 'Cost')");
    $db->exec("INSERT INTO mf_salesitemprice VALUES
        ('A', 1, 999), ('A', 7, 12.5),
        ('B', 7, 40),
        ('C', 1, 55),
        ('D', 7, 0),
        ('Z', 7, 1)");
    $future = date('Y-m-d', strtotime('+5 days'));
    $db->exec("INSERT INTO tranfile1 VALUES
        ('P1', 'PUR', '2026-01-01'),
        ('P2', 'PUR', '2026-02-01'),
        ('P3', 'PUR', '$future'),
        ('P0', 'PUR', '2025-12-01'),
        ('S1', 'SAL', '2026-03-01')");
    $db->exec("INSERT INTO tranfile2 VALUES
        (10, 'P1', 'A', 'pcs', 5),
        (11, 'P1', 'C', 'pcs', 20),
        (12, 'P2', 'C', 'pcs', 22),
        (13, 'P3', 'C', 'pcs', 99),
        (14, 'S1', 'C', 'pcs', 150),
        (17, 'P1', 'C', 'box', 200),
        (18, 'P2', 'C', 'box', 210),
        (15, 'P2', 'D', 'pcs', 30),
        (16, 'P1', '1001', 'pcs', 8),
        (19, 'P0', 'C', 'pcs', 77)");

    $item_list = array('A', 'B', 'C', 'D', 'E', '1001');
    $stmt = $db->prepare(str_replace('$placeholders', implode(',', array_fill(0, count($item_list), '?')), $query));
    $stmt->execute($item_list);
    $cost_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $no_cost_items = array();
    foreach ($item_list as $itm) {
        if (empty($cost_cache[$itm]) || (float)$cost_cache[$itm] == 0) {
            $no_cost_items[] = $itm;
        }
    }
    $stmt = $db->prepare(str_replace('$pur_placeholders', implode(',', array_fill(0, count($no_cost_items), '?')), $pur_query));
    $stmt->execute(array_merge(array(date('Y-m-d')), $no_cost_items));
    $pur_cost_cache = array();
    while ($pur_row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $pur_row['itmcde'] . '|' . $pur_row['unmcde'];
        if (!isset($pur_cost_cache[$key])) {
            $pur_cost_cache[$key] = (float)$pur_row['untprc'];
        }
    }

    // Same per-line lookup as the reports
    $unit = function ($itm, $unm = 'pcs') use ($cost_cache, $pur_cost_cache) {
        $unit_cost = isset($cost_cache[$itm]) ? (float)$cost_cache[$itm] : 0;
        $pur_cost_key = $itm . '|' . $unm;
        if ($unit_cost == 0 && isset($pur_cost_cache[$pur_cost_key])) {
            $unit_cost = $pur_cost_cache[$pur_cost_key];
        }
        return $unit_cost;
    };

    check('A uses Cost price, not Distributor price or purchase', $unit('A') == 12.5);
    check('B uses Cost price', $unit('B') == 40);
    check('C pcs sale uses latest pcs purchase <= today, ignoring future PUR, sales and box lines', $unit('C', 'pcs') == 22);
    check('C box sale uses latest box purchase', $unit('C', 'box') == 210);
    check('backdated PUR entered later (higher recid, older date) does not win', $unit('C', 'pcs') != 77);
    check('C with a UOM never purchased gives 0', $unit('C', 'case') == 0);
    check('D (Cost = 0) falls back to purchase untprc', $unit('D') == 30);
    check('D sold in box but only bought in pcs gives 0', $unit('D', 'box') == 0);
    check('E (no cost, no purchase) gives 0', $unit('E') == 0);
    check('numeric item code 1001 matches', $unit('1001') == 8);
    check('items outside the report are not loaded', !isset($cost_cache['Z']));
    check('line cost = unit cost * qty', $unit('A') * 4 == 50);
}
