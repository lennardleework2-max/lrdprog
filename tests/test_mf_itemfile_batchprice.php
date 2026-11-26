<?php
/**
 * Standalone CLI test for mf_itemfile_batchprice_ajax.php (batch Distributor / Cost / Wholesale / Retail price update).
 * Uses an in-memory SQLite database - no connection to the real database.
 * Run: php tests/test_mf_itemfile_batchprice.php
 */

require __DIR__ . "/../resources/stdfunc100.php";
require __DIR__ . "/../mf_itemfile_batchprice_ajax.php";

$failures = 0;
function check($label, $cond)
{
    global $failures;
    echo ($cond ? "PASS" : "FAIL") . " - " . $label . "\n";
    if(!$cond){ $failures++; }
}

// --- batchprice_build_changes ---
list($c, $e) = batchprice_build_changes(array("distributor" => "50", "retail" => "80.555"), "fixed", false, false);
check("valid prices map to salesprice ids", $e === "" && $c === array("SID-00001" => 50.0, "SID-00004" => 80.56));

list($c, $e) = batchprice_build_changes(array(), "fixed", false, false);
check("nothing ticked is rejected", $e === "Tick at least one price to update." && $c === array());

list($c, $e) = batchprice_build_changes(array("retail" => ""), "fixed", false, false);
check("empty price is rejected", $e === "Enter a price of 0 or more for Retail Price.");

list($c, $e) = batchprice_build_changes(array("retail" => "-1"), "fixed", false, false);
check("negative price is rejected", $e !== "");

list($c, $e) = batchprice_build_changes(array("retail" => "abc"), "fixed", false, false);
check("non-numeric price is rejected", $e !== "");

list($c, $e) = batchprice_build_changes(array("retail" => "0"), "fixed", false, false);
check("zero price is allowed", $e === "" && $c === array("SID-00004" => 0.0));

list($c, $e) = batchprice_build_changes(array("wholesale" => "10"), "fixed", false, false);
check("wholesale maps to SID-00003", $e === "" && $c === array("SID-00003" => 10.0));

list($c, $e) = batchprice_build_changes(array("special" => "10"), "fixed", false, false);
check("unknown price type is rejected", $e === "Invalid price type.");

list($c, $e) = batchprice_build_changes(array("cost" => "10"), "fixed", true, false);
check("cost blocked when hide_cost_price", $e === "You do not have permission to update Cost Price.");

list($c, $e) = batchprice_build_changes(array("distributor" => "10"), "fixed", false, true);
check("distributor blocked when hide_distri_price", $e === "You do not have permission to update Distributor Price.");

list($c, $e) = batchprice_build_changes(array("retail" => "10"), "fixed", true, true);
check("retail allowed even with both restrictions", $e === "");

list($c, $e) = batchprice_build_changes(array("wholesale" => "10"), "fixed", true, true);
check("wholesale allowed even with both restrictions", $e === "");

// --- batchprice_build_changes: percent mode ---
list($c, $e) = batchprice_build_changes(array("retail" => "10", "wholesale" => "-10"), "percent", false, false);
check("percent: +10 and -10 accepted", $e === "" && $c === array("SID-00004" => 10.0, "SID-00003" => -10.0));

list($c, $e) = batchprice_build_changes(array("retail" => "0"), "percent", false, false);
check("percent: 0 rejected", $e === "Enter a percentage (not 0, at least -100) for Retail Price.");

list($c, $e) = batchprice_build_changes(array("retail" => "-101"), "percent", false, false);
check("percent: below -100 rejected", $e !== "");

list($c, $e) = batchprice_build_changes(array("retail" => "-100"), "percent", false, false);
check("percent: -100 accepted", $e === "" && $c === array("SID-00004" => -100.0));

list($c, $e) = batchprice_build_changes(array("retail" => "abc"), "percent", false, false);
check("percent: non-numeric rejected", $e !== "");

list($c, $e) = batchprice_build_changes(array("cost" => "10"), "percent", true, false);
check("percent: cost still blocked when hide_cost_price", $e === "You do not have permission to update Cost Price.");

list($c, $e) = batchprice_build_changes(array("retail" => "10"), "bogus", false, false);
check("invalid mode rejected", $e === "Invalid update mode.");

// --- batchprice_apply (in-memory SQLite) ---
$link = new PDO("sqlite::memory:");
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$link->exec("CREATE TABLE itemfile (itmcde TEXT, itmdsc TEXT)");
$link->exec("CREATE TABLE mf_salesitemprice (recid INTEGER PRIMARY KEY AUTOINCREMENT, itmcde TEXT, salesprice_id TEXT, price REAL)");
$link->exec("INSERT INTO itemfile VALUES ('ITM-0001','Soap'),('ITM-0002','Lotion'),('ITM-0003','Toner'),('','blank')");
$link->exec("INSERT INTO mf_salesitemprice (itmcde, salesprice_id, price) VALUES
    ('ITM-0001','SID-00001',10),('ITM-0001','SID-00003',99),('ITM-0002','SID-00004',20)");

$count = batchprice_apply($link, array("SID-00001" => 50.0, "SID-00004" => 80.0));
check("returns item count (blank itmcde excluded)", $count === 3);

function prices($link, $sid)
{
    $stmt = $link->prepare("SELECT itmcde, price FROM mf_salesitemprice WHERE salesprice_id = ? ORDER BY itmcde");
    $stmt->execute(array($sid));
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
check("distributor set on all items (update + insert)", prices($link, "SID-00001") == array("ITM-0001" => 50, "ITM-0002" => 50, "ITM-0003" => 50));
check("retail set on all items (update + insert)", prices($link, "SID-00004") == array("ITM-0001" => 80, "ITM-0002" => 80, "ITM-0003" => 80));
check("unticked wholesale left untouched", prices($link, "SID-00003") == array("ITM-0001" => 99));
check("cost not created when not ticked", prices($link, "SID-00002") == array());

$rows_before = (int)$link->query("SELECT COUNT(*) FROM mf_salesitemprice")->fetchColumn();
batchprice_apply($link, array("SID-00001" => 55.0));
$rows_after = (int)$link->query("SELECT COUNT(*) FROM mf_salesitemprice")->fetchColumn();
check("re-running does not create duplicate rows", $rows_before === $rows_after);
check("re-running updates the price", prices($link, "SID-00001") == array("ITM-0001" => 55, "ITM-0002" => 55, "ITM-0003" => 55));

batchprice_apply($link, array("SID-00003" => 65.0));
check("wholesale set on all items (update + insert)", prices($link, "SID-00003") == array("ITM-0001" => 65, "ITM-0002" => 65, "ITM-0003" => 65));

// --- batchprice_apply: percent mode ---
$link->exec("DELETE FROM mf_salesitemprice");
$link->exec("INSERT INTO mf_salesitemprice (itmcde, salesprice_id, price) VALUES
    ('ITM-0001','SID-00004',100),('ITM-0002','SID-00004',33.33),('ITM-0003','SID-00004',0),
    ('ITM-0001','SID-00003',200),('ITM-0001','SID-00001',50)");

batchprice_apply($link, array("SID-00004" => 10.0, "SID-00003" => -10.0), "percent");
$retail = prices($link, "SID-00004");
check("percent: 100 +10% = 110", abs($retail["ITM-0001"] - 110) < 0.001);
check("percent: 33.33 +10% = 36.66 (2 decimals)", abs($retail["ITM-0002"] - 36.66) < 0.001);
check("percent: 0 price left unchanged", $retail["ITM-0003"] == 0);
check("percent: -10% decrease 200 -> 180", prices($link, "SID-00003") == array("ITM-0001" => 180));
check("percent: no rows inserted for items without a price", count(prices($link, "SID-00003")) === 1);
check("percent: unticked distributor untouched", prices($link, "SID-00001") == array("ITM-0001" => 50));

echo $failures === 0 ? "\nAll tests passed.\n" : "\n" . $failures . " test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
