<?php
session_start();

require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/lx2.pdodb.php";

$xret = array();
$xret["status"] = 1;
$xret["msg"] = "";
$xret["payments"] = array();
$xret["total_paid"] = 0;

function respond_and_exit($payload){
    echo json_encode($payload);
    exit;
}

function normalize_docnum($value){
    return trim((string)$value);
}

function normalize_date_to_db($value){
    $value = trim((string)$value);
    if($value === ""){
        return "";
    }
    $timestamp = strtotime($value);
    if($timestamp === false){
        return false;
    }
    return date("Y-m-d", $timestamp);
}

function get_total_paid($link, $docnum, $exclude_recid = 0){
    if($exclude_recid > 0){
        $select_total = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM partial_payment WHERE docnum=? AND recid!=?";
        $stmt_total = $link->prepare($select_total);
        $stmt_total->execute(array($docnum, $exclude_recid));
    }else{
        $select_total = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM partial_payment WHERE docnum=?";
        $stmt_total = $link->prepare($select_total);
        $stmt_total->execute(array($docnum));
    }
    $rs_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    return floatval($rs_total['total_paid']);
}

function get_partial_payment_date_column($link){
    static $date_column = null;
    if($date_column !== null){
        return $date_column;
    }

    $date_column = "date_paid";
    try{
        $stmt = $link->query("SHOW COLUMNS FROM partial_payment LIKE 'date'");
        $has_date_column = $stmt->fetch(PDO::FETCH_ASSOC);
        if($has_date_column){
            $date_column = "date";
        }
    }catch(Exception $e){
        $date_column = "date_paid";
    }

    return $date_column;
}

function has_partial_payment_check_number_column($link){
    static $has_check_number = null;
    if($has_check_number !== null){
        return $has_check_number;
    }

    $has_check_number = false;
    try{
        $stmt = $link->query("SHOW COLUMNS FROM partial_payment LIKE 'check_number'");
        $has_check_number = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }catch(Exception $e){
        $has_check_number = false;
    }

    return $has_check_number;
}

try{
    $event_action = isset($_POST["event_action"]) ? $_POST["event_action"] : "";
    $date_column = get_partial_payment_date_column($link);
    $has_check_number_column = has_partial_payment_check_number_column($link);

    // Get list of partial payments for a docnum
    if($event_action == "getList"){
        $docnum = normalize_docnum(isset($_POST['docnum']) ? $_POST['docnum'] : '');

        if($docnum !== ""){
            $check_number_sql = $has_check_number_column ? "check_number" : "'' AS check_number";
            $select_db = "SELECT recid, docnum, amount, `".$date_column."` AS date_paid, ".$check_number_sql." FROM partial_payment WHERE docnum=? ORDER BY `".$date_column."` ASC, recid ASC";
            $stmt = $link->prepare($select_db);
            $stmt->execute(array($docnum));

            $total_paid = 0;
            while($rs = $stmt->fetch(PDO::FETCH_ASSOC)){
                $payment = array();
                $payment['recid'] = $rs['recid'];
                $payment['docnum'] = $rs['docnum'];
                $payment['amount'] = $rs['amount'];
                $payment['check_number'] = isset($rs['check_number']) ? $rs['check_number'] : '';
                $payment['date_paid'] = $rs['date_paid'];
                $payment['date_paid_formatted'] = !empty($rs['date_paid']) ? date("m/d/Y", strtotime($rs['date_paid'])) : '';
                $xret["payments"][] = $payment;
                $total_paid += floatval($rs['amount']);
            }
            $xret["total_paid"] = $total_paid;
        }

        respond_and_exit($xret);
    }

    // Get total paid for a docnum
    if($event_action == "getTotalPaid"){
        $docnum = normalize_docnum(isset($_POST['docnum']) ? $_POST['docnum'] : '');

        if($docnum !== ""){
            $xret["total_paid"] = get_total_paid($link, $docnum);
        }

        respond_and_exit($xret);
    }

    // Get one partial payment for editing
    if($event_action == "getOne"){
        $recid = isset($_POST['recid']) ? intval($_POST['recid']) : 0;

        if($recid <= 0){
            $xret["status"] = 0;
            $xret["msg"] = "Record ID is required.";
            respond_and_exit($xret);
        }

        $check_number_sql = $has_check_number_column ? "check_number" : "'' AS check_number";
        $select_db = "SELECT recid, docnum, amount, `".$date_column."` AS date_paid, ".$check_number_sql." FROM partial_payment WHERE recid=?";
        $stmt = $link->prepare($select_db);
        $stmt->execute(array($recid));
        $rs = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$rs){
            $xret["status"] = 0;
            $xret["msg"] = "Payment not found.";
            respond_and_exit($xret);
        }

        $docnum = normalize_docnum($rs['docnum']);
        $xret["payment"] = array(
            'recid' => $rs['recid'],
            'docnum' => $docnum,
            'amount' => $rs['amount'],
            'check_number' => isset($rs['check_number']) ? $rs['check_number'] : '',
            'date_paid' => $rs['date_paid'],
            'date_paid_formatted' => !empty($rs['date_paid']) ? date("m/d/Y", strtotime($rs['date_paid'])) : ''
        );
        $xret["total_paid_excluding"] = get_total_paid($link, $docnum, $recid);

        respond_and_exit($xret);
    }

    // Insert new partial payment
    if($event_action == "insert"){
        $docnum = normalize_docnum(isset($_POST['docnum']) ? $_POST['docnum'] : '');
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $check_number = trim((string)(isset($_POST['check_number']) ? $_POST['check_number'] : ''));
        $date_paid = isset($_POST['date_paid']) ? $_POST['date_paid'] : '';
        $trntot = isset($_POST['trntot']) ? floatval($_POST['trntot']) : 0;

        if($docnum === ""){
            $xret["status"] = 0;
            $xret["msg"] = "Document number is required.";
            respond_and_exit($xret);
        }

        if($amount <= 0){
            $xret["status"] = 0;
            $xret["msg"] = "Amount must be greater than 0.";
            respond_and_exit($xret);
        }

        if(!$has_check_number_column && $check_number !== ""){
            $xret["status"] = 0;
            $xret["msg"] = "Check number column is missing in the active database.";
            respond_and_exit($xret);
        }

        $date_paid_db = normalize_date_to_db($date_paid);
        if($date_paid_db === false){
            $xret["status"] = 0;
            $xret["msg"] = "Invalid date paid value.";
            respond_and_exit($xret);
        }
        if($date_paid_db === ""){
            $xret["status"] = 0;
            $xret["msg"] = "Date paid is required.";
            respond_and_exit($xret);
        }

        $current_total = get_total_paid($link, $docnum);
        if($trntot > 0 && ($current_total + $amount) > $trntot){
            $xret["status"] = 0;
            $xret["msg"] = "Total partial payments cannot exceed the transaction total.";
            respond_and_exit($xret);
        }

        $link->beginTransaction();

        if($has_check_number_column){
            $insert_qry = "INSERT INTO partial_payment (docnum, amount, check_number, `".$date_column."`) VALUES (?, ?, ?, ?)";
            $stmt_insert = $link->prepare($insert_qry);
            $stmt_insert->execute(array($docnum, $amount, $check_number, $date_paid_db));
        }else{
            $insert_qry = "INSERT INTO partial_payment (docnum, amount, `".$date_column."`) VALUES (?, ?, ?)";
            $stmt_insert = $link->prepare($insert_qry);
            $stmt_insert->execute(array($docnum, $amount, $date_paid_db));
        }

        $link->commit();

        $xret["msg"] = "Partial payment added successfully.";
        respond_and_exit($xret);
    }

    // Update partial payment
    if($event_action == "update"){
        $recid = isset($_POST['recid']) ? intval($_POST['recid']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $check_number = trim((string)(isset($_POST['check_number']) ? $_POST['check_number'] : ''));
        $date_paid = isset($_POST['date_paid']) ? $_POST['date_paid'] : '';
        $trntot = isset($_POST['trntot']) ? floatval($_POST['trntot']) : 0;

        if($recid <= 0){
            $xret["status"] = 0;
            $xret["msg"] = "Record ID is required.";
            respond_and_exit($xret);
        }

        if($amount <= 0){
            $xret["status"] = 0;
            $xret["msg"] = "Amount must be greater than 0.";
            respond_and_exit($xret);
        }

        if(!$has_check_number_column && $check_number !== ""){
            $xret["status"] = 0;
            $xret["msg"] = "Check number column is missing in the active database.";
            respond_and_exit($xret);
        }

        $date_paid_db = normalize_date_to_db($date_paid);
        if($date_paid_db === false){
            $xret["status"] = 0;
            $xret["msg"] = "Invalid date paid value.";
            respond_and_exit($xret);
        }
        if($date_paid_db === ""){
            $xret["status"] = 0;
            $xret["msg"] = "Date paid is required.";
            respond_and_exit($xret);
        }

        $select_docnum = "SELECT docnum FROM partial_payment WHERE recid=?";
        $stmt_docnum = $link->prepare($select_docnum);
        $stmt_docnum->execute(array($recid));
        $rs_docnum = $stmt_docnum->fetch(PDO::FETCH_ASSOC);

        if(!$rs_docnum){
            $xret["status"] = 0;
            $xret["msg"] = "Payment not found.";
            respond_and_exit($xret);
        }

        $docnum = normalize_docnum($rs_docnum['docnum']);
        $current_total = get_total_paid($link, $docnum, $recid);
        if($trntot > 0 && ($current_total + $amount) > $trntot){
            $xret["status"] = 0;
            $xret["msg"] = "Total partial payments cannot exceed the transaction total.";
            respond_and_exit($xret);
        }

        $link->beginTransaction();

        if($has_check_number_column){
            $update_qry = "UPDATE partial_payment SET amount=?, check_number=?, `".$date_column."`=? WHERE recid=?";
            $stmt_update = $link->prepare($update_qry);
            $stmt_update->execute(array($amount, $check_number, $date_paid_db, $recid));
        }else{
            $update_qry = "UPDATE partial_payment SET amount=?, `".$date_column."`=? WHERE recid=?";
            $stmt_update = $link->prepare($update_qry);
            $stmt_update->execute(array($amount, $date_paid_db, $recid));
        }

        $link->commit();

        $xret["msg"] = "Partial payment updated successfully.";
        respond_and_exit($xret);
    }

    // Delete partial payment
    if($event_action == "delete"){
        $recid = isset($_POST['recid']) ? intval($_POST['recid']) : 0;

        if($recid <= 0){
            $xret["status"] = 0;
            $xret["msg"] = "Record ID is required.";
            respond_and_exit($xret);
        }

        $select_db = "SELECT docnum FROM partial_payment WHERE recid=?";
        $stmt = $link->prepare($select_db);
        $stmt->execute(array($recid));
        $rs = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$rs){
            $xret["status"] = 0;
            $xret["msg"] = "Payment not found.";
            respond_and_exit($xret);
        }

        $docnum = normalize_docnum($rs['docnum']);

        $link->beginTransaction();

        $delete_qry = "DELETE FROM partial_payment WHERE recid=?";
        $stmt_delete = $link->prepare($delete_qry);
        $stmt_delete->execute(array($recid));

        $link->commit();

        $xret["msg"] = "Partial payment deleted successfully.";
        respond_and_exit($xret);
    }

    $xret["status"] = 0;
    $xret["msg"] = "Invalid action.";
    respond_and_exit($xret);
}catch(Exception $e){
    if($link->inTransaction()){
        $link->rollBack();
    }
    $xret["status"] = 0;
    $xret["msg"] = "Database error: ".$e->getMessage();
    respond_and_exit($xret);
}
?>

