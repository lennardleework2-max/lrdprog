<?php 
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

session_start();
require_once("resources/lx2.pdodb.php");
require "resources/db_init.php";
require "resources/connect4.php";
require "resources/stdfunc100.php";


$xret = array();
$xret["status"] = 1;
$xret["items"] = array();

$xfilter = '';
$xfilter2 = '';
$params = array();
$selected_items = array();
foreach(isset($_POST['item']) ? (array)$_POST['item'] : array() as $item_code){
    if(is_scalar($item_code) && trim((string)$item_code) !== ''){
        $selected_items[] = trim((string)$item_code);
    }
}
$selected_items = array_values(array_unique($selected_items));

if(isset($_POST['date_search']) && !empty($_POST['date_search'])){
    $date_sql = date("Y-m-d", strtotime($_POST['date_search']));
    $xfilter2 .= " AND tranfile1.trndte <= ?";
    $params[] = $date_sql;
}

if(empty($selected_items)){
    $xret["status"] = 0;
    header('Content-Type: application/json');
    echo json_encode($xret);
    exit;
}

$item_placeholders = implode(',', array_fill(0, count($selected_items), '?'));
$xfilter .= " AND tranfile2.itmcde IN ($item_placeholders)";
$params = array_merge($params, $selected_items);

$select_db = "SELECT tranfile2.itmcde, COALESCE(itemfile.itmdsc, tranfile2.itmcde) AS itmdsc,
        COALESCE(SUM(tranfile2.stkqty), 0) AS xsum
    FROM tranfile2
    LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
    LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde
    WHERE 1=1 ".$xfilter2.$xfilter."
    GROUP BY tranfile2.itmcde, itemfile.itmdsc
    ORDER BY itmdsc ASC";
$stmt_main = $link->prepare($select_db);
$stmt_main->execute($params);
$rs_all = $stmt_main->fetchAll();

$balances_by_code = array();
foreach($rs_all as $rs_row){
    $balances_by_code[$rs_row['itmcde']] = $rs_row;
}

// Ensure every selected item appears, even if it had no matching transactions (balance 0)
$select_db_desc = "SELECT itmcde, itmdsc FROM itemfile WHERE itmcde IN ($item_placeholders)";
$stmt_desc = $link->prepare($select_db_desc);
$stmt_desc->execute($selected_items);
$desc_by_code = array();
while($rs_desc = $stmt_desc->fetch()){
    $desc_by_code[$rs_desc['itmcde']] = $rs_desc['itmdsc'];
}

foreach($selected_items as $item_code){
    if(isset($balances_by_code[$item_code])){
        $xret["items"][] = array(
            "itmcde" => $item_code,
            "itmdsc" => $balances_by_code[$item_code]['itmdsc'],
            "balance" => $balances_by_code[$item_code]['xsum']
        );
    } else {
        $xret["items"][] = array(
            "itmcde" => $item_code,
            "itmdsc" => isset($desc_by_code[$item_code]) ? $desc_by_code[$item_code] : $item_code,
            "balance" => 0
        );
    }
}

usort($xret["items"], function($a, $b){
    return strcmp($a['itmdsc'], $b['itmdsc']);
});

header('Content-Type: application/json');
echo json_encode($xret);
?>
