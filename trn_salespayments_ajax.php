<?php
session_start();

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("trn_salespayments_lib.php");

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

function cp_respond($payload)
{
    echo json_encode($payload);
    exit;
}

function cp_fail($message)
{
    cp_respond(array('status' => 0, 'msg' => $message));
}

if (!isset($_SESSION['userdesc']) || !isset($_SESSION['usercode'])) {
    cp_fail('Your session has expired. Please log in again.');
}

// same permission source as the page (user_menus row for trn_salespayments.php, admin has everything)
function cp_permissions($link)
{
    if ($_SESSION['userdesc'] === 'admin') {
        return array('view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1);
    }
    $stmt = $link->prepare("SELECT `view`, `add`, `edit`, `delete` FROM user_menus WHERE usercode = ? AND menprogram = ? LIMIT 1");
    $stmt->execute(array($_SESSION['usercode'], 'trn_salespayments.php'));
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);
    return $rs ? array_map('intval', $rs) : array('view' => 0, 'add' => 0, 'edit' => 0, 'delete' => 0);
}

function cp_post($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function cp_log($link, $activity, $remarks)
{
    $username = $_SESSION['userdesc'];
    PDO_UserActivityLog($link, $username, '', date('Y-m-d H:i:s'), 'CUSTOMER PAYMENTS', $activity,
        useractivitylog_get_session_fullname($link), $remarks, 0, '', '', '', '', $username, '', '');
}

try {
    $perm = cp_permissions($link);
    if ((int)$perm['view'] !== 1) {
        cp_fail('You do not have access to Customer Payments.');
    }

    $action = cp_post('event_action');
    $today = date('Y-m-d');
    $size = (int)cp_post('size', '10');

    if ($action === 'customers') {
        $data = cp_fetch_customers($link, cp_post('search'), cp_post('route_id'), cp_post('filter', 'balance'), (int)cp_post('page', '1'), $size);
        cp_respond(array('status' => 1) + $data);
    }

    $buyer_id = cp_post('buyer_id');

    if ($action === 'account') {
        if ($buyer_id === '') {
            cp_fail('Please select a customer.');
        }
        cp_respond(array('status' => 1, 'summary' => cp_fetch_summary($link, $buyer_id, $today)));
    }

    if ($action === 'drs') {
        $data = cp_fetch_drs($link, $buyer_id, cp_post('open_only', '1') === '1', (int)cp_post('page', '1'), $size, $today);
        cp_respond(array('status' => 1) + $data);
    }

    if ($action === 'payments' || $action === 'checks') {
        $data = cp_fetch_payments($link, $buyer_id, $action === 'checks', (int)cp_post('page', '1'), $size);
        cp_respond(array('status' => 1) + $data);
    }

    if ($action === 'open_drs') {
        $payment_id = (int)cp_post('payment_id', '0');
        $payment = null;
        if ($payment_id > 0) {
            $payment = cp_fetch_payment($link, $payment_id);
            if (!$payment) {
                cp_fail('Payment not found.');
            }
            $buyer_id = $payment['buyer_id'];
        }
        cp_respond(array('status' => 1, 'payment' => $payment, 'drs' => cp_fetch_open_drs($link, $buyer_id, $payment)));
    }

    if ($action === 'save') {
        $payment_id = (int)cp_post('payment_id', '0');
        if ($payment_id > 0 ? (int)$perm['edit'] !== 1 : (int)$perm['add'] !== 1) {
            cp_fail('You do not have permission to save payments.');
        }

        $data = array(
            'buyer_id' => $buyer_id,
            'docnum' => cp_post('docnum'),
            'pay_date' => cp_to_db_date(cp_post('pay_date')),
            'pay_type' => strtoupper(cp_post('pay_type')),
            'amount' => cp_money(str_replace(',', '', cp_post('amount', '0'))),
            'check_number' => cp_post('check_number'),
            'check_date' => cp_to_db_date(cp_post('check_date')),
            'remarks' => cp_post('remarks'),
        );

        $error = cp_validate_payment($data);
        if ($error !== '') {
            cp_fail($error);
        }

        try {
            $saved_id = cp_save_payment($link, $data, $_SESSION['usercode'], $payment_id);
        } catch (Exception $e) {
            cp_fail($e->getMessage());
        }

        cp_log($link, $payment_id > 0 ? 'edit' : 'add', $_SESSION['userdesc'] . ($payment_id > 0 ? ' edited' : ' recorded') .
            " payment #" . $saved_id . " for buyer '" . $buyer_id . "' amount " . number_format($data['amount'], 2) .
            " (" . $data['pay_type'] . ") on docnum '" . $data['docnum'] . "'");
        cp_respond(array('status' => 1, 'msg' => 'Payment saved.', 'payment_id' => $saved_id));
    }

    if ($action === 'delete') {
        if ((int)$perm['delete'] !== 1) {
            cp_fail('You do not have permission to delete payments.');
        }
        $payment_id = (int)cp_post('payment_id', '0');
        $payment = cp_fetch_payment($link, $payment_id);
        if (!$payment) {
            cp_fail('Payment not found.');
        }
        cp_delete_payment($link, $payment_id);
        cp_log($link, 'delete', $_SESSION['userdesc'] . " deleted payment #" . $payment_id . " for buyer '" . $payment['buyer_id'] .
            "' amount " . number_format($payment['amount'], 2) . " on docnum '" . $payment['docnum'] . "'");
        cp_respond(array('status' => 1, 'msg' => 'Payment deleted.'));
    }

    if ($action === 'deposit') {
        if ((int)$perm['edit'] !== 1) {
            cp_fail('You do not have permission to update payments.');
        }
        $payment_id = (int)cp_post('payment_id', '0');
        $deposited = cp_to_db_date(cp_post('deposited_date'));
        if (empty($deposited)) {
            cp_fail('Please enter a valid deposit date.');
        }
        if (!cp_mark_deposited($link, $payment_id, $deposited)) {
            cp_fail('Check payment not found.');
        }
        cp_log($link, 'edit', $_SESSION['userdesc'] . " marked check payment #" . $payment_id . " as deposited on " . $deposited);
        cp_respond(array('status' => 1, 'msg' => 'Check marked as deposited.'));
    }

    cp_fail('Invalid action.');
} catch (Exception $e) {
    error_log('trn_salespayments_ajax: ' . $e->getMessage());
    // 42S22 = unknown column / 42S02 = unknown table: the database update has not been applied
    if ($e instanceof PDOException && in_array($e->getCode(), array('42S22', '42S02'), true)) {
        cp_fail('The database is missing the Customer Payments columns. Please apply the partial_payment update. (' . $e->getMessage() . ')');
    }
    cp_fail('Something went wrong. Please try again.');
}
