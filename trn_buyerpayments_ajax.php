<?php
session_start();

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("trn_buyerpayments_lib.php");

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

function bpa_respond($payload)
{
    echo json_encode($payload);
    exit;
}

function bpa_fail($msg, $extra = array())
{
    bpa_respond(array('status' => 0, 'msg' => $msg) + $extra);
}

function bpa_post($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

if (!isset($_SESSION['userdesc']) || !isset($_SESSION['usercode'])) {
    bpa_fail('Your session has expired. Please log in again.');
}

function bpa_log($link, $activity, $remarks)
{
    $user = $_SESSION['userdesc'];
    PDO_UserActivityLog($link, $user, '', date('Y-m-d H:i:s'), 'SHOP PAYMENTS', $activity,
        useractivitylog_get_session_fullname($link), $remarks, 0, '', '', '', '', $user, '', '');
}

function bpa_describe($p)
{
    $methods = bp_methods();
    $d = $methods[$p['method']] . ' ' . number_format((float)$p['amount'], 2) . ' for customer ' . $p['cuscde'] . ' dated ' . $p['pay_date'];
    if ($p['method'] === 'CHEQUE') {
        $d .= ' (Chq ' . $p['bank_name'] . ' #' . $p['cheque_no'] . ', ' . $p['status'] . ')';
    }
    return $d;
}

function bpa_payment_out($p)
{
    return array(
        'recid' => (int)$p['recid'], 'cuscde' => $p['cuscde'], 'pay_date' => substr((string)$p['pay_date'], 0, 10),
        'amount' => bp_money($p['amount']), 'method' => $p['method'], 'status' => $p['status'],
        'reference_no' => (string)$p['reference_no'], 'bank_name' => (string)$p['bank_name'], 'cheque_no' => (string)$p['cheque_no'],
        'cheque_date' => $p['cheque_date'] ? substr($p['cheque_date'], 0, 10) : '',
        'deposited_date' => $p['deposited_date'] ? substr($p['deposited_date'], 0, 10) : '',
        'cleared_date' => $p['cleared_date'] ? substr($p['cleared_date'], 0, 10) : '',
        'returned_date' => $p['returned_date'] ? substr($p['returned_date'], 0, 10) : '',
        'remarks' => (string)$p['remarks'],
    );
}

try {
    $perm = bp_page_permissions($link, 'trn_buyerpayments.php');
    if ($perm['view'] !== 1) {
        bpa_fail('You do not have access to Shop Payments.');
    }
    $action = bpa_post('event_action');
    $today = date('Y-m-d');
    $usercode = $_SESSION['usercode'];

    // RYU customer list (paged like trn_salesfile1) + KPI totals
    if ($action === 'list') {
        $page_action = bpa_post('page_action', 'same');
        $data = bp_fetch_customers($link, bpa_post('search'), bpa_post('status', 'All'), bpa_post('sort', 'outstanding'),
                               (int)bpa_post('pageno', '1'), $page_action);
        // totals scan every RYU customer: only recompute on load / search / after a change, not on every page click
        $data['totals'] = in_array($page_action, array('first_p', 'search', 'same'), true) ? bp_fetch_totals($link) : null;
        bpa_respond(array('status' => 1) + $data);
    }

    // customer picker for the payment form: RYU customers only
    if ($action === 'customer_search') {
        bpa_respond(array('status' => 1, 'rows' => bp_search_customers($link, bpa_post('q'))));
    }

    // one customer: summary only (payments and sales records are loaded page by page with 'payments' / 'drs')
    if ($action === 'customer') {
        $cuscde = bpa_post('cuscde');
        $name = bp_customer_name($link, $cuscde);
        if ($name === null) {
            bpa_fail('Shop not found.');
        }
        $payments = bp_customer_payments($link, $cuscde);
        bpa_respond(array('status' => 1, 'cuscde' => $cuscde, 'cusdsc' => $name,
            'summary' => bp_summarize(bp_customer_sales($link, $cuscde), $payments),
            'payment_count' => count($payments)));
    }

    // one page of a customer's payments (History tab), newest first, filtered / searched
    if ($action === 'payments') {
        $page = bp_payment_page(bp_customer_payments($link, bpa_post('cuscde')), bpa_post('filter', 'All'), bpa_post('q'),
                                (int)bpa_post('pageno', '1'), bpa_post('page_action', 'first_p'));
        $page['rows'] = array_map('bpa_payment_out', $page['rows']);
        bpa_respond(array('status' => 1) + $page);
    }

    // one page of a customer's sales records (all are matched oldest-first, then filtered / searched / paged)
    if ($action === 'drs') {
        $cuscde = bpa_post('cuscde');
        $sales = bp_customer_sales($link, $cuscde);
        $alloc = bp_allocate($sales, bp_customer_payments($link, $cuscde));
        $ordernum = array();
        foreach ($sales as $s) {
            $ordernum[$s['docnum']] = (string)$s['ordernum'];
        }
        foreach ($alloc['rows'] as &$r) {
            $r['ordernum'] = $ordernum[$r['docnum']];
        }
        unset($r);
        bpa_respond(array('status' => 1) + bp_dr_page($alloc['rows'], bpa_post('filter', 'auto'), bpa_post('q'),
                                                      (int)bpa_post('pageno', '1'), bpa_post('page_action', 'first_p')));
    }

    // which sales records a draft payment would mark paid (read-only)
    if ($action === 'preview') {
        $cuscde = bpa_post('cuscde');
        $edit_recid = (int)bpa_post('recid', '0');
        if ($edit_recid > 0) {
            $existing = bp_fetch_payment($link, $edit_recid);
            if (!$existing) {
                bpa_fail('Payment not found.');
            }
            $cuscde = $existing['cuscde'];
        }
        $amount = bp_parse_amount(bpa_post('amount'));
        $method = bpa_post('method');
        $status = $method === 'CHEQUE' ? bpa_post('status') : 'RECEIVED';
        if (bp_customer_name($link, $cuscde) === null) {
            bpa_fail('Shop not found.');
        }
        $pay_date = bp_valid_date(bpa_post('pay_date')) ? bpa_post('pay_date') : $today;
        $draft = ($amount === null || $amount <= 0) ? null    // no amount yet: just list the unpaid sales records
               : array('amount' => $amount, 'status' => $status, 'pay_date' => $pay_date, 'cleared_date' => $status === 'CLEARED' ? $today : null);
        bpa_respond(array('status' => 1, 'has_amount' => $draft ? 1 : 0) + bp_preview($link, $cuscde, $draft, $edit_recid, 10,
                                                                                   (int)bpa_post('pv_pageno', '1'), bpa_post('pv_page_action', 'first_p'),
                                                                                   bpa_post('pv_filter', 'auto'), bpa_post('pv_q')));
    }

    if ($action === 'get_payment') {
        $p = bp_fetch_payment($link, (int)bpa_post('recid'));
        if (!$p) {
            bpa_fail('Payment not found.');
        }
        bpa_respond(array('status' => 1, 'payment' => bpa_payment_out($p), 'cusdsc' => bp_customer_name($link, $p['cuscde'])));
    }

    if ($action === 'save') {
        $recid = (int)bpa_post('recid', '0');
        $existing = null;
        if ($recid > 0) {
            if ($perm['edit'] !== 1) {
                bpa_fail('You do not have permission to edit payments.');
            }
            $existing = bp_fetch_payment($link, $recid);
            if (!$existing) {
                bpa_fail('Payment not found. It may have been deleted.');
            }
        } elseif ($perm['add'] !== 1) {
            bpa_fail('You do not have permission to add payments.');
        }

        $res = bp_validate_payment($link, $_POST, $today, $existing);
        if ($res['errors']) {
            bpa_fail('Please check the highlighted fields.', array('errors' => $res['errors']));
        }
        $v = $res['value'];
        if (bpa_post('confirm_dup') !== '1' && bp_possible_duplicate($link, $v, $recid)) {
            bpa_fail('A ' . bp_methods()[$v['method']] . ' payment of ' . number_format($v['amount'], 2) . ' on ' . date('m/d/Y', strtotime($v['pay_date'])) .
                     ' with the same reference is already recorded for this shop. Save it again anyway?', array('needs_confirm' => 1));
        }
        try {
            $new_id = bp_save_payment($link, $v, $usercode, $today, $existing);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                bpa_fail('This cheque is already recorded for this shop.', array('errors' => array('cheque_no' => 'This cheque is already recorded for this shop.')));
            }
            throw $ex;
        }
        $saved = bp_fetch_payment($link, $new_id);
        if ($existing) {
            bpa_log($link, 'edit', 'Edited payment #' . $new_id . ': ' . bpa_describe($existing) . ' -> ' . bpa_describe($saved));
        } else {
            bpa_log($link, 'add', 'Added payment #' . $new_id . ': ' . bpa_describe($saved));
        }
        bpa_respond(array('status' => 1, 'msg' => 'Payment saved.', 'payment' => bpa_payment_out($saved)));
    }

    if ($action === 'set_status') {
        if ($perm['edit'] !== 1) {
            bpa_fail('You do not have permission to change cheque status.');
        }
        $p = bp_fetch_payment($link, (int)bpa_post('recid'));
        $status = bpa_post('status');
        $statuses = bp_cheque_statuses();
        if (!$p || $p['method'] !== 'CHEQUE') {
            bpa_fail('Cheque not found.');
        }
        if (!isset($statuses[$status])) {
            bpa_fail('Unknown cheque status.');
        }
        // Date Deposited / Cleared / Bounced / Closed Account: the date the user picked (may be earlier than today if encoded late)
        $date = bpa_post('status_date');
        $err = bp_status_date_error($status, $date, $today);
        if ($err !== null) {
            bpa_fail($err, array('errors' => array('status_date' => $err)));
        }
        $date = bp_status_date_label($status) !== '' ? $date : null;
        bp_set_cheque_status($link, $p, $status, $usercode, $today, $date);
        bpa_log($link, 'edit', 'Cheque status #' . $p['recid'] . ' (' . $p['bank_name'] . ' #' . $p['cheque_no'] . ', ' .
                number_format((float)$p['amount'], 2) . ', customer ' . $p['cuscde'] . '): ' . $p['status'] . ' -> ' . $status .
                ($date ? ' on ' . $date : ''));
        bpa_respond(array('status' => 1, 'msg' => 'Cheque status updated.'));
    }

    if ($action === 'delete') {
        if ($perm['delete'] !== 1) {
            bpa_fail('You do not have permission to delete payments.');
        }
        $p = bp_fetch_payment($link, (int)bpa_post('recid'));
        if (!$p) {
            bpa_fail('Payment not found. It may have been deleted already.');
        }
        bp_delete_payment($link, $p);
        bpa_log($link, 'delete', 'Deleted payment #' . $p['recid'] . ': ' . bpa_describe($p));
        bpa_respond(array('status' => 1, 'msg' => 'Payment deleted.'));
    }

    bpa_fail('Invalid action.');
} catch (Exception $e) {
    if (isset($link) && $link->inTransaction()) {
        $link->rollBack();
    }
    error_log('trn_buyerpayments_ajax: ' . $e->getMessage());
    bpa_fail('Something went wrong. Nothing was saved. Please try again.');
}
