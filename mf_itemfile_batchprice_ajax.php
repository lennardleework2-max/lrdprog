<?php
// Batch price update for mf_itemfile.php: sets Distributor / Cost / Wholesale / Retail price for ALL items.

$batchprice_types = array(
    "distributor" => array("salesprice_id" => "SID-00001", "label" => "Distributor Price"),
    "cost"        => array("salesprice_id" => "SID-00002", "label" => "Cost Price"),
    "wholesale"   => array("salesprice_id" => "SID-00003", "label" => "Wholesale Price"),
    "retail"      => array("salesprice_id" => "SID-00004", "label" => "Retail Price"),
);

// Validates posted values. $posted = array(type => value string) for the ticked price types only.
// $mode "fixed": value is the new price. $mode "percent": value is a % change (negative = decrease).
// Returns array($changes, $error) where $changes = array(salesprice_id => price or percent).
function batchprice_build_changes($posted, $mode, $hide_cost_price, $hide_distri_price)
{
    global $batchprice_types;

    if($mode !== "fixed" && $mode !== "percent"){
        return array(array(), "Invalid update mode.");
    }
    if(!is_array($posted) || empty($posted)){
        return array(array(), "Tick at least one price to update.");
    }

    $changes = array();
    foreach($posted as $type => $price){
        if(!isset($batchprice_types[$type])){
            return array(array(), "Invalid price type.");
        }
        $salesprice_id = $batchprice_types[$type]["salesprice_id"];
        $label = $batchprice_types[$type]["label"];

        if(stdfunc_is_salesprice_restricted($salesprice_id, $hide_cost_price, $hide_distri_price)){
            return array(array(), "You do not have permission to update " . $label . ".");
        }

        $price = trim((string)$price);
        if($mode === "percent"){
            if($price === '' || !is_numeric($price) || (float)$price == 0 || (float)$price < -100){
                return array(array(), "Enter a percentage (not 0, at least -100) for " . $label . ".");
            }
            $changes[$salesprice_id] = (float)$price;
            continue;
        }
        if($price === '' || !is_numeric($price) || (float)$price < 0){
            return array(array(), "Enter a price of 0 or more for " . $label . ".");
        }
        $changes[$salesprice_id] = round((float)$price, 2);
    }

    return array($changes, "");
}

// "fixed": sets each price on every item (updates existing rows, inserts rows for items without one).
// "percent": changes existing prices above 0 by the given %, rounded to 2 decimals; no rows are inserted.
// Returns the number of items in itemfile.
function batchprice_apply($link, $changes, $mode = "fixed")
{
    $stmt_percent = $link->prepare("UPDATE mf_salesitemprice SET price = ROUND(price * (1 + ? / 100.0), 2) WHERE salesprice_id = ? AND price > 0");
    $stmt_update = $link->prepare("UPDATE mf_salesitemprice SET price = ? WHERE salesprice_id = ?");
    $stmt_insert = $link->prepare(
        "INSERT INTO mf_salesitemprice (itmcde, salesprice_id, price)
         SELECT i.itmcde, ?, ? FROM itemfile i
         WHERE i.itmcde <> '' AND NOT EXISTS (
             SELECT 1 FROM mf_salesitemprice s WHERE s.itmcde = i.itmcde AND s.salesprice_id = ?
         )"
    );

    $link->beginTransaction();
    try{
        foreach($changes as $salesprice_id => $price){
            if($mode === "percent"){
                $stmt_percent->execute(array($price, $salesprice_id));
                continue;
            }
            $stmt_update->execute(array($price, $salesprice_id));
            $stmt_insert->execute(array($salesprice_id, $price, $salesprice_id));
        }
        $link->commit();
    }catch(Exception $e){
        $link->rollBack();
        throw $e;
    }

    return (int)$link->query("SELECT COUNT(*) FROM itemfile WHERE itmcde <> ''")->fetchColumn();
}

// Stop here when included by tests.
if(basename($_SERVER["SCRIPT_FILENAME"]) !== basename(__FILE__)){
    return;
}

session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once("resources/lx2.pdodb.php");
require "resources/db_init.php";
require "resources/connect4.php";
require "resources/stdfunc100.php";

function batchprice_respond($success, $message, $code = 200)
{
    http_response_code($code);
    echo json_encode(array("success" => $success, "message" => $message));
    exit;
}

if(!isset($_SESSION["userdesc"], $_SESSION["usercode"])){
    batchprice_respond(false, "Your session has expired. Please sign in again.", 401);
}
if($_SERVER["REQUEST_METHOD"] !== "POST"){
    batchprice_respond(false, "Invalid request.", 400);
}

$can_edit = $_SESSION["userdesc"] === "admin";
if(!$can_edit){
    $stmt_perm = $link->prepare("SELECT `edit` FROM user_menus WHERE usercode = ? AND menprogram = ? LIMIT 1");
    $stmt_perm->execute(array($_SESSION["usercode"], "mf_itemfile.php"));
    $rs_perm = $stmt_perm->fetch();
    $can_edit = $rs_perm && (int)$rs_perm["edit"] === 1;
}
if(!$can_edit){
    batchprice_respond(false, "You do not have permission to edit items.", 403);
}

list($hide_cost_price, $hide_distri_price) = stdfunc_get_price_restrictions($link, "Items");
$posted = isset($_POST["prices"]) && is_array($_POST["prices"]) ? $_POST["prices"] : array();
$mode = isset($_POST["mode"]) ? (string)$_POST["mode"] : "fixed";
list($changes, $error) = batchprice_build_changes($posted, $mode, $hide_cost_price, $hide_distri_price);
if($error !== ""){
    batchprice_respond(false, $error, 422);
}

try{
    $item_count = batchprice_apply($link, $changes, $mode);
}catch(Exception $e){
    error_log("Batch price update failed: " . $e->getMessage());
    batchprice_respond(false, "The prices could not be saved.", 500);
}

$arr_summary = array();
foreach($batchprice_types as $type){
    if(!isset($changes[$type["salesprice_id"]])){
        continue;
    }
    $value = $changes[$type["salesprice_id"]];
    if($mode === "percent"){
        $arr_summary[] = $type["label"] . " " . ($value > 0 ? "+" : "") . rtrim(rtrim(number_format($value, 2, ".", ""), "0"), ".") . "%";
    }else{
        $arr_summary[] = $type["label"] . " = " . number_format($value, 2);
    }
}
$summary = implode(", ", $arr_summary);
$log_action = $mode === "percent" ? "BATCH PRICE % UPDATE" : "BATCH PRICE UPDATE";

$username = $_SESSION["userdesc"];
$stmt_user = $link->prepare("SELECT full_name FROM users WHERE recid = ?");
$stmt_user->execute(array(isset($_SESSION["recid"]) ? $_SESSION["recid"] : 0));
$full_name = (string)$stmt_user->fetchColumn();
PDO_UserActivityLog($link, $username, '', date("Y-m-d H:i:s"), "ITEMS", $log_action, $full_name, $summary . " (" . $item_count . " items)", 0, '', '', '', '', $username, '', '');

batchprice_respond(true, "Updated " . $item_count . " items: " . $summary . ".");
