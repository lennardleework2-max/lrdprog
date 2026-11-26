<?php
/**
 * Customer Payments - shared logic (queries, validation, save, paid-status sync).
 * Used by trn_salespayments_ajax.php and partial_payment_ajax.php, and by tests/test_trn_salespayments.php.
 *
 * One table: every partial_payment row is one payment against one sales record (partial_payment.docnum = tranfile1.docnum).
 * The customer comes from tranfile1.buyer_id. Rows with pay_type NULL were entered from the Sales screen.
 *
 * Balance rules:
 *  - Record balance = trntot - SUM(partial_payment.amount), never below 0.
 *  - A record marked paid outside this module (manual Pay Date / paid upload) has balance 0.
 *  - Checks reduce the balance as soon as they are recorded; deposited_date only tracks "checks on hand".
 *  - When payments fully cover a record, tranfile1.paydate is set with paydetails = CP_PAID_MARK.
 *    Only paydates carrying that mark are ever cleared by this module.
 */

define('CP_PAID_MARK', 'Paid via payments');
define('CP_EPSILON', 0.005);

// one row per SAL record with its balance
function cp_dr_base_sql()
{
    return "SELECT t1.docnum, t1.ordernum, t1.trndte, t1.buyer_id, t1.route_id, t1.salesman_id,
                   COALESCE(t1.trntot, 0) AS trntot,
                   CASE
                       WHEN t1.paydate IS NOT NULL AND t1.paydate <> '' AND COALESCE(t1.paydetails, '') <> '" . CP_PAID_MARK . "' THEN 0
                       WHEN COALESCE(t1.trntot, 0) - COALESCE(pp.paid, 0) > 0 THEN COALESCE(t1.trntot, 0) - COALESCE(pp.paid, 0)
                       ELSE 0
                   END AS balance
            FROM tranfile1 t1
            LEFT JOIN (SELECT docnum, SUM(amount) AS paid FROM partial_payment GROUP BY docnum) pp ON pp.docnum = t1.docnum
            WHERE t1.trncde = 'SAL' AND t1.buyer_id IS NOT NULL AND t1.buyer_id <> ''";
}

// mf_buyers can hold duplicate buyer_id rows, so collapse to one name per id
function cp_buyer_lookup_sql()
{
    return "SELECT buyer_id, MAX(buyer_name) AS buyer_name FROM mf_buyers GROUP BY buyer_id";
}

// payment rows joined to their sales record (gives the customer)
function cp_payment_from_sql()
{
    return " FROM partial_payment pp JOIN tranfile1 t1 ON t1.docnum = pp.docnum AND t1.trncde = 'SAL'";
}

function cp_money($value)
{
    return round((float)$value, 2);
}

function cp_to_db_date($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $date = DateTime::createFromFormat('!m/d/Y', $value);
    if ($date === false || $date->format('m/d/Y') !== $value) {
        return false;
    }
    return $date->format('Y-m-d');
}

function cp_page_limits($page, $size)
{
    $page = max(1, (int)$page);
    $size = min(100, max(1, (int)$size));
    return array($page, $size, ($page - 1) * $size);
}

function cp_age_days($trndte, $today)
{
    $from = strtotime(substr((string)$trndte, 0, 10));
    $to = strtotime($today);
    if ($from === false || $to === false) {
        return null;
    }
    return (int)floor(($to - $from) / 86400);
}

function cp_fetch_customers($link, $search, $route_id, $filter, $page, $size)
{
    list($page, $size, $offset) = cp_page_limits($page, $size);

    $where = '';
    $params = array();
    if ($route_id !== '') {
        $where .= " AND d.route_id = ?";
        $params[] = $route_id;
    }
    if ($search !== '') {
        $where .= " AND (b.buyer_name LIKE ? OR d.buyer_id LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $having = '';
    if ($filter === 'balance') {
        $having = " HAVING SUM(d.balance) > " . CP_EPSILON;
    } elseif ($filter === 'paid') {
        $having = " HAVING SUM(d.balance) <= " . CP_EPSILON;
    }

    $grouped = "SELECT d.buyer_id, MAX(b.buyer_name) AS buyer_name,
                       SUM(CASE WHEN d.balance > " . CP_EPSILON . " THEN 1 ELSE 0 END) AS open_drs,
                       SUM(d.balance) AS balance
                FROM (" . cp_dr_base_sql() . ") d
                LEFT JOIN (" . cp_buyer_lookup_sql() . ") b ON b.buyer_id = d.buyer_id
                WHERE 1 = 1" . $where . "
                GROUP BY d.buyer_id" . $having;

    $stmt = $link->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(balance), 0) AS total_balance FROM (" . $grouped . ") x");
    $stmt->execute($params);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $link->prepare($grouped . " ORDER BY MAX(b.buyer_name), d.buyer_id LIMIT " . $size . " OFFSET " . $offset);
    $stmt->execute($params);

    $rows = array();
    while ($rs = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array(
            'buyer_id' => $rs['buyer_id'],
            'buyer_name' => trim((string)$rs['buyer_name']) !== '' ? $rs['buyer_name'] : $rs['buyer_id'],
            'open_drs' => (int)$rs['open_drs'],
            'balance' => cp_money($rs['balance']),
        );
    }

    return array(
        'rows' => $rows,
        'total' => (int)$totals['total'],
        'total_balance' => cp_money($totals['total_balance']),
        'page' => $page,
        'size' => $size,
    );
}

function cp_fetch_summary($link, $buyer_id, $today)
{
    $stmt = $link->prepare("SELECT COUNT(*) AS dr_count,
                                   COALESCE(SUM(d.trntot), 0) AS total_sales,
                                   COALESCE(SUM(d.balance), 0) AS balance,
                                   SUM(CASE WHEN d.balance > " . CP_EPSILON . " THEN 1 ELSE 0 END) AS open_drs,
                                   MIN(CASE WHEN d.balance > " . CP_EPSILON . " THEN d.trndte END) AS oldest_open
                            FROM (" . cp_dr_base_sql() . ") d
                            WHERE d.buyer_id = ?");
    $stmt->execute(array($buyer_id));
    $sum = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $link->prepare("SELECT MAX(buyer_name) AS buyer_name FROM mf_buyers WHERE buyer_id = ?");
    $stmt->execute(array($buyer_id));
    $name = $stmt->fetchColumn();

    // route and salesman of the customer's latest record
    $stmt = $link->prepare("SELECT r.route_desc, s.salesman_name
                            FROM tranfile1 t1
                            LEFT JOIN mf_routes r ON r.route_id = t1.route_id
                            LEFT JOIN mf_salesman s ON s.salesman_id = t1.salesman_id
                            WHERE t1.trncde = 'SAL' AND t1.buyer_id = ?
                            ORDER BY t1.trndte DESC, t1.docnum DESC LIMIT 1");
    $stmt->execute(array($buyer_id));
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $link->prepare("SELECT COALESCE(SUM(pp.amount), 0) AS amt, COUNT(*) AS cnt" . cp_payment_from_sql() . "
                            WHERE t1.buyer_id = ? AND pp.pay_type = 'CHECK' AND pp.deposited_date IS NULL");
    $stmt->execute(array($buyer_id));
    $checks = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_sales = cp_money($sum['total_sales']);
    $balance = cp_money($sum['balance']);

    return array(
        'buyer_id' => $buyer_id,
        'buyer_name' => trim((string)$name) !== '' ? $name : $buyer_id,
        'route' => $latest ? (string)$latest['route_desc'] : '',
        'salesman' => $latest ? (string)$latest['salesman_name'] : '',
        'dr_count' => (int)$sum['dr_count'],
        'open_drs' => (int)$sum['open_drs'],
        'total_sales' => $total_sales,
        'paid' => cp_money($total_sales - $balance),
        'balance' => $balance,
        'checks_amount' => cp_money($checks['amt']),
        'checks_count' => (int)$checks['cnt'],
        'oldest_days' => empty($sum['oldest_open']) ? null : cp_age_days($sum['oldest_open'], $today),
    );
}

function cp_fetch_drs($link, $buyer_id, $open_only, $page, $size, $today)
{
    list($page, $size, $offset) = cp_page_limits($page, $size);

    $where = " WHERE d.buyer_id = ?";
    if ($open_only) {
        $where .= " AND d.balance > " . CP_EPSILON;
    }
    $from = " FROM (" . cp_dr_base_sql() . ") d" . $where;

    $stmt = $link->prepare("SELECT COUNT(*)" . $from);
    $stmt->execute(array($buyer_id));
    $total = (int)$stmt->fetchColumn();

    $stmt = $link->prepare("SELECT d.docnum, d.ordernum, d.trndte, d.trntot, d.balance" . $from .
                           " ORDER BY d.trndte ASC, d.docnum ASC LIMIT " . $size . " OFFSET " . $offset);
    $stmt->execute(array($buyer_id));

    $rows = array();
    while ($rs = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $trntot = cp_money($rs['trntot']);
        $balance = cp_money($rs['balance']);
        $rows[] = array(
            'docnum' => $rs['docnum'],
            'ordernum' => (string)$rs['ordernum'],
            'trndte' => substr((string)$rs['trndte'], 0, 10),
            'trntot' => $trntot,
            'paid' => cp_money($trntot - $balance),
            'balance' => $balance,
            'age_days' => $balance > CP_EPSILON ? cp_age_days($rs['trndte'], $today) : null,
        );
    }

    return array('rows' => $rows, 'total' => $total, 'page' => $page, 'size' => $size);
}

// payment history (all payments, incl. Sales-screen ones) or checks still on hand
function cp_fetch_payments($link, $buyer_id, $checks_only, $page, $size)
{
    list($page, $size, $offset) = cp_page_limits($page, $size);

    $where = " WHERE t1.buyer_id = ?";
    $order = " ORDER BY pp.date_paid DESC, pp.recid DESC";
    if ($checks_only) {
        $where .= " AND pp.pay_type = 'CHECK' AND pp.deposited_date IS NULL";
        $order = " ORDER BY pp.check_date ASC, pp.recid ASC";
    }

    $stmt = $link->prepare("SELECT COUNT(*)" . cp_payment_from_sql() . $where);
    $stmt->execute(array($buyer_id));
    $total = (int)$stmt->fetchColumn();

    $stmt = $link->prepare("SELECT pp.recid, pp.docnum, t1.ordernum, pp.date_paid AS pay_date, pp.pay_type, pp.amount,
                                   pp.check_number, pp.check_date, pp.deposited_date, pp.remarks" .
                           cp_payment_from_sql() . $where . $order . " LIMIT " . $size . " OFFSET " . $offset);
    $stmt->execute(array($buyer_id));

    $rows = array();
    while ($rs = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rs['amount'] = cp_money($rs['amount']);
        $rs['ordernum'] = (string)$rs['ordernum'];
        $rows[] = $rs;
    }

    return array('rows' => $rows, 'total' => $total, 'page' => $page, 'size' => $size);
}

function cp_fetch_payment($link, $payment_id)
{
    $stmt = $link->prepare("SELECT pp.*, t1.buyer_id" . cp_payment_from_sql() . " WHERE pp.recid = ?");
    $stmt->execute(array((int)$payment_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

// records a payment can go to: open records, plus (when editing) the payment's own record with its amount added back
function cp_fetch_open_drs($link, $buyer_id, $payment)
{
    $own_docnum = $payment ? (string)$payment['docnum'] : '';
    $own_amount = $payment ? cp_money($payment['amount']) : 0;

    $stmt = $link->prepare("SELECT d.docnum, d.ordernum, d.trndte, d.balance
                            FROM (" . cp_dr_base_sql() . ") d
                            WHERE d.buyer_id = ? AND (d.balance > " . CP_EPSILON . " OR d.docnum = ?)
                            ORDER BY d.trndte ASC, d.docnum ASC");
    $stmt->execute(array($buyer_id, $own_docnum));

    $rows = array();
    while ($rs = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array(
            'docnum' => $rs['docnum'],
            'ordernum' => (string)$rs['ordernum'],
            'trndte' => substr((string)$rs['trndte'], 0, 10),
            'available' => cp_money($rs['balance'] + ($rs['docnum'] === $own_docnum ? $own_amount : 0)),
        );
    }
    return $rows;
}

/**
 * Validates a posted payment. $data keys: buyer_id, docnum, pay_date, pay_type, amount, check_number, check_date.
 * Returns '' when valid, otherwise the error message. The record balance is checked in cp_save_payment().
 */
function cp_validate_payment($data)
{
    if (trim((string)$data['buyer_id']) === '') {
        return 'Please select a customer.';
    }
    if (trim((string)$data['docnum']) === '') {
        return 'Please choose the record this payment is for.';
    }
    if (empty($data['pay_date'])) {
        return 'Please enter a valid date received.';
    }
    if (!in_array($data['pay_type'], array('CASH', 'CHECK', 'BANK'), true)) {
        return 'Please choose a payment method.';
    }
    if ($data['amount'] <= 0) {
        return 'Amount must be greater than 0.';
    }
    if ($data['pay_type'] === 'CHECK') {
        if (trim((string)$data['check_number']) === '') {
            return 'Check number is required for check payments.';
        }
        if (empty($data['check_date'])) {
            return 'Please enter a valid check date.';
        }
    }
    return '';
}

/**
 * Inserts ($payment_id = 0) or updates one payment row in partial_payment.
 * Throws Exception with a user-facing message when the record is not the customer's or the amount exceeds its balance.
 */
function cp_save_payment($link, $data, $usercode, $payment_id = 0)
{
    $payment_id = (int)$payment_id;
    $link->beginTransaction();
    try {
        $existing = null;
        if ($payment_id > 0) {
            $existing = cp_fetch_payment($link, $payment_id);
            if (!$existing) {
                throw new Exception('Payment not found.');
            }
        }

        $stmt = $link->prepare("SELECT d.balance FROM (" . cp_dr_base_sql() . ") d WHERE d.docnum = ? AND d.buyer_id = ?");
        $stmt->execute(array($data['docnum'], $data['buyer_id']));
        $balance = $stmt->fetchColumn();
        if ($balance === false) {
            throw new Exception('Record ' . $data['docnum'] . ' does not belong to this customer.');
        }
        // when editing, this payment's current amount is part of the record's "paid" already
        $available = cp_money($balance + ($existing && $existing['docnum'] === $data['docnum'] ? $existing['amount'] : 0));
        if ($data['amount'] - $available > CP_EPSILON) {
            throw new Exception('Amount (' . number_format($data['amount'], 2) . ') is more than the balance of ' . $data['docnum'] . ' (' . number_format($available, 2) . ').');
        }

        $record = array(
            'docnum' => $data['docnum'],
            'amount' => cp_money($data['amount']),
            'date_paid' => $data['pay_date'],
            'pay_type' => $data['pay_type'],
            'check_number' => $data['pay_type'] === 'CASH' ? '' : $data['check_number'],
            'check_date' => $data['pay_type'] === 'CHECK' ? $data['check_date'] : null,
            'remarks' => $data['remarks'],
        );

        if ($existing) {
            // cash/bank payments are never "on hand"; a check keeps its deposit status
            if ($data['pay_type'] !== 'CHECK') {
                $record['deposited_date'] = null;
            }
            PDO_UpdateRecord($link, 'partial_payment', $record, "recid = ?", array($payment_id));
        } else {
            $record['usercode'] = $usercode;
            $record['created_at'] = date('Y-m-d H:i:s');
            PDO_InsertRecord($link, 'partial_payment', $record);
            $payment_id = (int)$link->lastInsertId();
        }

        cp_sync_paid_status($link, array($data['docnum'], $existing ? $existing['docnum'] : ''));
        $link->commit();
        return $payment_id;
    } catch (Exception $e) {
        if ($link->inTransaction()) {
            $link->rollBack();
        }
        throw $e;
    }
}

function cp_delete_payment($link, $payment_id)
{
    $payment = cp_fetch_payment($link, $payment_id);
    if (!$payment) {
        return false;
    }
    $link->beginTransaction();
    try {
        $link->prepare("DELETE FROM partial_payment WHERE recid = ?")->execute(array((int)$payment_id));
        cp_sync_paid_status($link, array($payment['docnum']));
        $link->commit();
        return true;
    } catch (Exception $e) {
        if ($link->inTransaction()) {
            $link->rollBack();
        }
        throw $e;
    }
}

function cp_mark_deposited($link, $payment_id, $deposited_date)
{
    $payment = cp_fetch_payment($link, $payment_id);
    if (!$payment || $payment['pay_type'] !== 'CHECK') {
        return false;
    }
    $stmt = $link->prepare("UPDATE partial_payment SET deposited_date = ? WHERE recid = ?");
    $stmt->execute(array($deposited_date, (int)$payment_id));
    return true;
}

/**
 * Sets tranfile1.paydate when payments cover the record total, and clears it (only if this module set it)
 * when they no longer do. Manual pay dates and paid uploads are never touched.
 */
function cp_sync_paid_status($link, $docnums)
{
    $docnums = array_unique(array_filter(array_map('strval', (array)$docnums), 'strlen'));
    if (empty($docnums)) {
        return;
    }

    $stmt_dr = $link->prepare("SELECT COALESCE(trntot, 0) AS trntot, paydate, COALESCE(paydetails, '') AS paydetails
                               FROM tranfile1 WHERE docnum = ? AND trncde = 'SAL'");
    $stmt_paid = $link->prepare("SELECT COALESCE(SUM(amount), 0) AS paid, MAX(date_paid) AS last_date
                                 FROM partial_payment WHERE docnum = ?");
    $stmt_set = $link->prepare("UPDATE tranfile1 SET paydate = ?, paydetails = ? WHERE docnum = ? AND trncde = 'SAL'");
    $stmt_clear = $link->prepare("UPDATE tranfile1 SET paydate = NULL, paydetails = '' WHERE docnum = ? AND trncde = 'SAL'");

    foreach ($docnums as $docnum) {
        $stmt_dr->execute(array($docnum));
        $dr = $stmt_dr->fetch(PDO::FETCH_ASSOC);
        if (!$dr) {
            continue;
        }
        $stmt_paid->execute(array($docnum));
        $paid = $stmt_paid->fetch(PDO::FETCH_ASSOC);

        $has_paydate = !empty($dr['paydate']) && substr((string)$dr['paydate'], 0, 10) !== '0000-00-00';
        $set_by_module = ($dr['paydetails'] === CP_PAID_MARK);
        $fully_paid = (float)$dr['trntot'] > 0 && (float)$paid['paid'] >= (float)$dr['trntot'] - CP_EPSILON;

        if ($fully_paid && (!$has_paydate || $set_by_module)) {
            $stmt_set->execute(array($paid['last_date'], CP_PAID_MARK, $docnum));
        } elseif (!$fully_paid && $has_paydate && $set_by_module) {
            $stmt_clear->execute(array($docnum));
        }
    }
}
