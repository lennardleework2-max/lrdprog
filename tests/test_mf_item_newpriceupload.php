<?php
/**
 * Standalone CLI test for mf_item_newpriceupload_ajax.php (item price upload by SKU).
 * Uses an in-memory SQLite database - no connection to the real database.
 * Run: php tests/test_mf_item_newpriceupload.php
 */

require __DIR__ . "/../mf_item_newpriceupload_ajax.php";

$failures = 0;
function check($label, $cond)
{
    global $failures;
    echo ($cond ? "PASS" : "FAIL") . " - " . $label . "\n";
    if(!$cond){ $failures++; }
}

$price_types = array(
    array("salesprice_id" => "SID-00001", "salesprice_desc" => "Distributor"),
    array("salesprice_id" => "SID-00002", "salesprice_desc" => "Cost Price"),
    array("salesprice_id" => "SID-00003", "salesprice_desc" => "Wholesale"),
    array("salesprice_id" => "SID-00004", "salesprice_desc" => "Retail Price"),
);

// --- newprice_map_headers ---
list($map, $errors) = newprice_map_headers(array("SKU", "Distributor Price", "Cost Price", "Wholesale Price", "Retail Price"), $price_types);
check("all four headers map to salesprice ids", $map === array(
    1 => array("SID-00001", "Distributor Price"),
    2 => array("SID-00002", "Cost Price"),
    3 => array("SID-00003", "Wholesale Price"),
    4 => array("SID-00004", "Retail Price"),
) && $errors === array());

list($map, $errors) = newprice_map_headers(array("SKU", " retail  PRICE ", "Special Price", ""), $price_types);
check("header match is case/space insensitive", $map === array(1 => array("SID-00004", "retail  PRICE")));
check("unknown header reported, blank header ignored", count($errors) === 1 && strpos($errors[0], "Special Price") !== false);

// --- newprice_apply (in-memory SQLite) ---
$link = new PDO("sqlite::memory:");
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$link->exec("CREATE TABLE itemfile (itmcde TEXT, tiktok_itm_sku TEXT, shopee_itm_sku TEXT, lazada_itm_sku TEXT)");
$link->exec("CREATE TABLE mf_salesitemprice (recid INTEGER PRIMARY KEY AUTOINCREMENT, itmcde TEXT, salesprice_id TEXT, price REAL)");
$link->exec("INSERT INTO itemfile VALUES
    ('ITM-0001','T5756','',''),
    ('ITM-0002','','BLITZ051-Forged Carbon',''),
    ('ITM-0003','','','BLITZ027-BLUE'),
    ('ITM-0004','SHARED','',''),
    ('ITM-0005','','SHARED','')");
$link->exec("INSERT INTO mf_salesitemprice (itmcde, salesprice_id, price) VALUES ('ITM-0001','SID-00001',10),('ITM-0001','SID-00004',99)");

function price($link, $itmcde, $sid)
{
    $stmt = $link->prepare("SELECT price FROM mf_salesitemprice WHERE itmcde = ? AND salesprice_id = ?");
    $stmt->execute(array($itmcde, $sid));
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return count($rows) === 1 ? (float)$rows[0] : $rows;
}
function row_count($link)
{
    return (int)$link->query("SELECT COUNT(*) FROM mf_salesitemprice")->fetchColumn();
}

list($col_map) = newprice_map_headers(array("SKU", "Distributor Price", "Cost Price", "Wholesale Price", "Retail Price"), $price_types);

$rows = array(
    array("T5756", 2250, "", "", ""),                       // tiktok match, update existing distributor
    array("blitz051-forged carbon", "1,150", 500, "", ""),  // shopee match (case-insensitive), comma price, insert
    array("BLITZ027-BLUE", 261, "", "", ""),                // lazada match
    array("NOPE-123", 100, "", "", ""),                     // unknown SKU
    array("T5756", "", "", "abc", "-5"),                    // invalid values
    array("", 1, 1, 1, 1),                                  // blank SKU skipped
    array("SHARED", "", "", 77, ""),                        // SKU on two items -> both updated
    array("BLITZ027-BLUE", 164, "", "", ""),                // duplicate SKU -> last wins
);
list($results, $updated) = newprice_apply($link, $rows, $col_map);

check("tiktok SKU updates existing distributor price", price($link, "ITM-0001", "SID-00001") === 2250.0);
check("untouched retail price kept", price($link, "ITM-0001", "SID-00004") === 99.0);
check("shopee SKU match is case-insensitive and inserts missing rows", price($link, "ITM-0002", "SID-00001") === 1150.0 && price($link, "ITM-0002", "SID-00002") === 500.0);
check("blank cells do not create rows", price($link, "ITM-0002", "SID-00003") === array());
check("duplicate SKU row: last value wins", price($link, "ITM-0003", "SID-00001") === 164.0);
check("SKU shared by two items updates both", price($link, "ITM-0004", "SID-00003") === 77.0 && price($link, "ITM-0005", "SID-00003") === 77.0);
check("updated row count excludes failures", $updated === 5);

$messages = array_column($results, "message");
check("unknown SKU reported", in_array("Row 5 NOPE-123: SKU not found in items.", $messages, true));
check("non-numeric price reported", in_array("Row 6 T5756: invalid Wholesale Price 'abc'.", $messages, true));
check("negative price reported", in_array("Row 6 T5756: invalid Retail Price '-5'.", $messages, true));
check("success message lists prices", in_array("Row 3 blitz051-forged carbon: Distributor Price = 1,150.00, Cost Price = 500.00", $messages, true));
check("blank SKU row produces no result", count(preg_grep('/^Row 7/', $messages)) === 0);

// --- itmcde / sku on results (used by the PDF export) ---
$by_sku = array();
foreach($results as $r){
    $by_sku[$r["sku"]] = $r;
}
check("result carries matched itmcde and sku", $by_sku["T5756"]["itmcde"] === "ITM-0001" && $by_sku["blitz051-forged carbon"]["itmcde"] === "ITM-0002");
check("unknown SKU has blank itmcde", $by_sku["NOPE-123"]["itmcde"] === "");
check("shared SKU lists both item codes", $by_sku["SHARED"]["itmcde"] === "ITM-0004, ITM-0005");

// --- newprice_sort_results ---
$sorted = newprice_sort_results(array(
    array("success" => false, "message" => "f1"),
    array("success" => true, "message" => "s1"),
    array("success" => false, "message" => "f2"),
    array("success" => true, "message" => "s2"),
));
check("sort: successes first, failures last, order kept", array_column($sorted, "message") === array("s1", "s2", "f1", "f2"));
check("sort: empty list stays empty", newprice_sort_results(array()) === array());

$before = row_count($link);
newprice_apply($link, array(array("T5756", 2300, "", "", "")), $col_map);
check("re-upload updates without duplicating rows", row_count($link) === $before && price($link, "ITM-0001", "SID-00001") === 2300.0);

// --- restricted price types ---
list($results) = newprice_apply($link, array(array("T5756", 1, 2, "", 3)), $col_map, array("SID-00001", "SID-00002"));
check("restricted distributor not changed", price($link, "ITM-0001", "SID-00001") === 2300.0);
check("restricted cost not created", price($link, "ITM-0001", "SID-00002") === array());
check("unrestricted retail still updated", price($link, "ITM-0001", "SID-00004") === 3.0);
check("restriction reported", in_array("Row 2 T5756: no permission to update Cost Price.", array_column($results, "message"), true));

echo $failures === 0 ? "\nAll tests passed.\n" : "\n" . $failures . " test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
