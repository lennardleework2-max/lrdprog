<?php
// Item New Price Upload: sets mf_salesitemprice prices from an uploaded sheet.
// Sheet layout: column 1 = SKU (matched against itemfile tiktok/shopee/lazada SKU), columns 2+ = price types
// whose headers match mf_salespricetype.salesprice_desc (e.g. "Distributor Price").

// Lowercases, drops the word "price" and extra spaces so "Distributor Price" matches "Distributor".
function newprice_normalize_header($text)
{
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/\bprice\b/', '', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

// $headers = row 1 of the sheet. $price_types = rows of mf_salespricetype (salesprice_id, salesprice_desc).
// Returns array($col_map, $errors) where $col_map = array(colIndex => array(salesprice_id, label)).
function newprice_map_headers($headers, $price_types)
{
    $lookup = array();
    foreach($price_types as $type){
        $lookup[newprice_normalize_header($type["salesprice_desc"])] = $type["salesprice_id"];
    }

    $col_map = array();
    $errors = array();
    foreach($headers as $index => $header){
        if($index === 0 || trim((string)$header) === ''){
            continue;
        }
        $key = newprice_normalize_header($header);
        if(isset($lookup[$key])){
            $col_map[$index] = array($lookup[$key], trim((string)$header));
        }else{
            $errors[] = "Column '" . trim((string)$header) . "' does not match any price type and was ignored.";
        }
    }
    return array($col_map, $errors);
}

// Returns array(lowercased SKU => array(itmcde, ...)) built from all three platform SKU columns.
function newprice_load_sku_map($link)
{
    $sku_map = array();
    $stmt = $link->query("SELECT itmcde, tiktok_itm_sku, shopee_itm_sku, lazada_itm_sku FROM itemfile WHERE itmcde <> ''");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        foreach(array("tiktok_itm_sku", "shopee_itm_sku", "lazada_itm_sku") as $field){
            $sku = strtolower(trim((string)$row[$field]));
            if($sku !== '' && (!isset($sku_map[$sku]) || !in_array($row["itmcde"], $sku_map[$sku], true))){
                $sku_map[$sku][] = $row["itmcde"];
            }
        }
    }
    return $sku_map;
}

// $rows = sheet data rows (without the header). Updates the price if the row exists, otherwise inserts it.
// $restricted_ids = salesprice_ids the user may not change. Returns array(results, updated row count).
function newprice_apply($link, $rows, $col_map, $restricted_ids = array())
{
    $sku_map = newprice_load_sku_map($link);
    $stmt_find = $link->prepare("SELECT recid FROM mf_salesitemprice WHERE itmcde = ? AND salesprice_id = ? LIMIT 1");
    $stmt_update = $link->prepare("UPDATE mf_salesitemprice SET price = ? WHERE recid = ?");
    $stmt_insert = $link->prepare("INSERT INTO mf_salesitemprice (itmcde, salesprice_id, price) VALUES (?, ?, ?)");

    $results = array();
    $updated_rows = 0;

    $link->beginTransaction();
    try{
        foreach($rows as $index => $row){
            $row_label = "Row " . ($index + 2);
            $sku = trim((string)(isset($row[0]) ? $row[0] : ''));
            if($sku === ''){
                continue;
            }
            $row_label .= " " . $sku;

            $sku_key = strtolower($sku);
            if(!isset($sku_map[$sku_key])){
                $results[] = array("success" => false, "itmcde" => "", "sku" => $sku, "message" => $row_label . ": SKU not found in items.");
                continue;
            }
            $itmcde_list = implode(", ", $sku_map[$sku_key]);

            $applied = array();
            foreach($col_map as $col => $type){
                list($salesprice_id, $label) = $type;
                $value = trim(str_replace(',', '', (string)(isset($row[$col]) ? $row[$col] : '')));
                if($value === ''){
                    continue;
                }
                if(!is_numeric($value) || (float)$value < 0){
                    $results[] = array("success" => false, "itmcde" => $itmcde_list, "sku" => $sku, "message" => $row_label . ": invalid " . $label . " '" . $value . "'.");
                    continue;
                }
                if(in_array($salesprice_id, $restricted_ids, true)){
                    $results[] = array("success" => false, "itmcde" => $itmcde_list, "sku" => $sku, "message" => $row_label . ": no permission to update " . $label . ".");
                    continue;
                }

                $price = round((float)$value, 2);
                foreach($sku_map[$sku_key] as $itmcde){
                    $stmt_find->execute(array($itmcde, $salesprice_id));
                    $recid = $stmt_find->fetchColumn();
                    if($recid !== false){
                        $stmt_update->execute(array($price, $recid));
                    }else{
                        $stmt_insert->execute(array($itmcde, $salesprice_id, $price));
                    }
                }
                $applied[] = $label . " = " . number_format($price, 2);
            }

            if(!empty($applied)){
                $updated_rows++;
                $results[] = array("success" => true, "itmcde" => $itmcde_list, "sku" => $sku, "message" => $row_label . ": " . implode(", ", $applied));
            }
        }
        $link->commit();
    }catch(Exception $e){
        $link->rollBack();
        throw $e;
    }

    return array($results, $updated_rows);
}

// Successes first, failures last; keeps the original row order within each group.
function newprice_sort_results($results)
{
    $success = array();
    $failed = array();
    foreach($results as $result){
        if(!empty($result["success"])){
            $success[] = $result;
        }else{
            $failed[] = $result;
        }
    }
    return array_merge($success, $failed);
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
require 'vendor/autoload.php';

function newprice_respond($status, $error_msg, $results = array())
{
    echo json_encode(array("status" => $status, "errorMsg" => $error_msg, "results" => $results));
    exit;
}

if(!isset($_SESSION["userdesc"], $_SESSION["usercode"])){
    newprice_respond(0, "Your session has expired. Please sign in again.");
}

$can_edit = $_SESSION["userdesc"] === "admin";
if(!$can_edit){
    $stmt_perm = $link->prepare("SELECT `add`, `edit` FROM user_menus WHERE usercode = ? AND menprogram = ? LIMIT 1");
    $stmt_perm->execute(array($_SESSION["usercode"], "mf_item_newpriceupload.php"));
    $rs_perm = $stmt_perm->fetch();
    $can_edit = $rs_perm && ((int)$rs_perm["add"] === 1 || (int)$rs_perm["edit"] === 1);
}
if(!$can_edit){
    newprice_respond(0, "You do not have permission to upload item prices.");
}

if(!isset($_FILES["xfile"]) || $_FILES["xfile"]["error"] !== 0){
    newprice_respond(0, "Please choose a file to upload.");
}

$file_name = $_FILES["xfile"]["name"];
$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if($extension !== "xlsx" && $extension !== "csv"){
    newprice_respond(0, "Only .xlsx and .csv files are allowed.");
}

try{
    $reader = $extension === "csv" ? new \PhpOffice\PhpSpreadsheet\Reader\Csv() : new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $sheet_rows = $reader->load($_FILES["xfile"]["tmp_name"])->getActiveSheet()->toArray(null, true, false, false);
}catch(Exception $e){
    newprice_respond(0, "The file could not be read.");
}

if(count($sheet_rows) < 2){
    newprice_respond(0, "The file has no data rows.");
}

$price_types = $link->query("SELECT salesprice_id, salesprice_desc FROM mf_salespricetype")->fetchAll(PDO::FETCH_ASSOC);
list($col_map, $header_errors) = newprice_map_headers(array_shift($sheet_rows), $price_types);
if(empty($col_map)){
    newprice_respond(0, "No price columns matched. Expected headers like Distributor Price, Cost Price, Wholesale Price, Retail Price.");
}

list($hide_cost_price, $hide_distri_price) = stdfunc_get_price_restrictions($link, "Items");
$restricted_ids = array();
foreach($col_map as $type){
    if(stdfunc_is_salesprice_restricted($type[0], $hide_cost_price, $hide_distri_price)){
        $restricted_ids[] = $type[0];
    }
}

try{
    list($results, $updated_rows) = newprice_apply($link, $sheet_rows, $col_map, $restricted_ids);
}catch(Exception $e){
    error_log("Item new price upload failed: " . $e->getMessage());
    newprice_respond(0, "The prices could not be saved.");
}

foreach($header_errors as $error){
    $results[] = array("success" => false, "itmcde" => "", "sku" => "", "message" => $error);
}
$results = newprice_sort_results($results);

$username = $_SESSION["userdesc"];
$stmt_user = $link->prepare("SELECT full_name FROM users WHERE recid = ?");
$stmt_user->execute(array(isset($_SESSION["recid"]) ? $_SESSION["recid"] : 0));
$full_name = (string)$stmt_user->fetchColumn();
PDO_UserActivityLog($link, $username, '', date("Y-m-d H:i:s"), "ITEMS", "NEW PRICE UPLOAD", $full_name, $updated_rows . " rows updated from " . $file_name, 0, '', '', '', '', $username, '', '');

newprice_respond(1, "", $results);
