<?php
// Buyer Billing Statement: buyer picker (search + picked buyer summary). The PDF itself is trn_buyerbilling_pdf.php.
session_start();

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("trn_buyerpayments_lib.php");

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

function bba_respond($payload)
{
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['userdesc']) || !isset($_SESSION['usercode'])) {
    bba_respond(array('status' => 0, 'msg' => 'Your session has expired. Please log in again.'));
}

try {
    $perm = bp_page_permissions($link, 'trn_buyerbilling.php');
    if ($perm['view'] !== 1) {
        bba_respond(array('status' => 0, 'msg' => 'You do not have access to Buyer Billing Statement.'));
    }
    $action = isset($_POST['event_action']) ? $_POST['event_action'] : '';

    if ($action === 'buyer_search') {
        bba_respond(array('status' => 1, 'rows' => bp_search_buyers($link, trim((string)(isset($_POST['q']) ? $_POST['q'] : '')))));
    }

    // picked buyer: address, contact and what is due today
    if ($action === 'buyer') {
        $buyer_id = trim((string)(isset($_POST['buyer_id']) ? $_POST['buyer_id'] : ''));
        $info = bp_buyer_info($link, $buyer_id);
        if ($info === null) {
            bba_respond(array('status' => 0, 'msg' => 'Buyer not found.'));
        }
        $st = bp_billing_statement(bp_buyer_sales($link, $buyer_id), bp_buyer_payments($link, $buyer_id),
                                   array('as_of' => date('Y-m-d'), 'mode' => 'unpaid', 'limit' => 1));
        bba_respond(array('status' => 1, 'buyer' => $info, 'due' => $st['due'], 'open_count' => count($st['records'])));
    }

    bba_respond(array('status' => 0, 'msg' => 'Unknown action.'));
} catch (Exception $ex) {
    bba_respond(array('status' => 0, 'msg' => 'Something went wrong. Please try again.'));
}
