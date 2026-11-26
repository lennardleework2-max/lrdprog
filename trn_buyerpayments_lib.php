<?php
/**
 * Shop Payments (trn_buyerpayments.php) - shared logic (used by trn_buyerpayments_ajax.php and tests/test_trn_buyerpayments.php).
 *
 * The page still says "Shop", but each "shop" is a RYU customer: customerfile.cusdsc LIKE '%RYU%'.
 * Payments are recorded per customer in `customer_payment` (not per sales record).
 * A customer's sales = tranfile1 SAL records with that cuscde (the whole trntot counts).
 *  - Collected      = RECEIVED (cash/GCash/bank) + CLEARED cheques
 *  - Cheques on hand = HOLD + FOR_DEPOSIT  (not collected yet)
 *  - Returned       = BOUNCED + CLOSED_ACCOUNT (never reduce what is owed)
 *
 * Matching: customer_payment money alone pays the customer's sales, oldest first (trndte, then docnum).
 * tranfile1.paydate is NOT used to decide what is paid.
 * Bridge: a fully covered sale with an empty paydate gets tranfile1.paydate + paydetails = BP_PAID_MARK.
 * Only paydates carrying that mark are ever cleared again; paydates typed by hand, set by the paid upload,
 * or by the old buyer payments are never changed. tranfile2 and trntot are never touched.
 *
 * bp_buyer_sales / bp_buyer_payments / bp_buyer_info / bp_search_buyers / bp_billing_statement stay for
 * Buyer Billing (trn_buyerbilling*.php), which still works per buyer on buyer_payment.
 */

define('BP_CUSTOMER_LIKE', '%RYU%');                   // only customers whose cusdsc matches are listed
define('BP_PAID_MARK', 'Paid via shop payments');
define('BP_EPS', 0.005);
define('BP_PAGE_SIZE', 20);
define('BP_MAX_AMOUNT', 999999999.99);

function bp_methods()
{
    return array('CASH' => 'Cash', 'GCASH' => 'GCash', 'CHEQUE' => 'Cheque', 'BANK' => 'Bank Transfer/Deposit');
}

function bp_cheque_statuses()
{
    return array('HOLD' => 'Hold', 'FOR_DEPOSIT' => 'Date Deposited', 'CLEARED' => 'Cleared', 'BOUNCED' => 'Bounced', 'CLOSED_ACCOUNT' => 'Closed Account');
}

function bp_is_collected($status) { return $status === 'RECEIVED' || $status === 'CLEARED'; }
function bp_is_on_hand($status)   { return $status === 'HOLD' || $status === 'FOR_DEPOSIT'; }
function bp_is_returned($status)  { return $status === 'BOUNCED' || $status === 'CLOSED_ACCOUNT'; }

function bp_money($v) { return round((float)$v, 2); }

function bp_has_paydate($paydate)
{
    $p = (string)$paydate;
    return $p !== '' && substr($p, 0, 10) !== '0000-00-00';
}

function bp_owned_mark($paydetails)
{
    return $paydetails === BP_PAID_MARK;
}

// date a collected payment counts from = the Payment Date typed in the Add Payment form (also for cleared cheques)
function bp_effective_date($p)
{
    return substr((string)$p['pay_date'], 0, 10);
}

/* ---------------------------------------------------------------- matching */

/**
 * Pure oldest-first matching. No DB access.
 * $sales:    rows with docnum, trndte, trntot, paydate, paydetails (any order)
 * $payments: rows with recid, amount, status, pay_date, cleared_date (any order)
 * Returns array('rows' => per-DR result sorted oldest first, 'credit' => unused collected money).
 * Each row: docnum, trndte, trntot, state (no_amount|paid|partial|unpaid), applied, paydate (date to store or '').
 * tranfile1.paydate is ignored here; the payments' money alone decides what is paid.
 */
function bp_allocate($sales, $payments)
{
    usort($sales, function ($a, $b) {
        $c = strcmp(substr((string)$a['trndte'], 0, 10), substr((string)$b['trndte'], 0, 10));
        return $c !== 0 ? $c : strcmp((string)$a['docnum'], (string)$b['docnum']);
    });

    // collected payments in the order the money came in, for "which payment completed this DR"
    $collected = array();
    foreach ($payments as $p) {
        if (bp_is_collected($p['status']) && (float)$p['amount'] > 0) {
            $collected[] = array('amount' => (float)$p['amount'], 'date' => bp_effective_date($p), 'recid' => (int)(isset($p['recid']) ? $p['recid'] : 0));
        }
    }
    usort($collected, function ($a, $b) {
        $c = strcmp($a['date'], $b['date']);
        return $c !== 0 ? $c : $a['recid'] - $b['recid'];
    });

    $money = 0.0;
    foreach ($collected as $c) {
        $money += $c['amount'];
    }
    $money = bp_money($money);

    $rows = array();
    $used = 0.0;        // money already applied to covered DRs
    $pi = 0;            // pointer into $collected
    $running = 0.0;     // cumulative collected up to payment $pi
    $stopped = false;   // strict oldest-first: once a DR can't be fully covered, later DRs wait

    foreach ($sales as $s) {
        $total = bp_money($s['trntot']);
        $row = array('docnum' => $s['docnum'], 'trndte' => substr((string)$s['trndte'], 0, 10), 'trntot' => $total,
                     'state' => 'unpaid', 'applied' => 0.0, 'paydate' => '');

        // tranfile1.paydate is NOT used here: the payments' money alone decides what is paid
        if ($total <= BP_EPS) {          // zero-value DR (cancelled, free, etc.) - nothing to pay
            $row['state'] = 'no_amount';
            $rows[] = $row;
            continue;
        }

        $left = bp_money($money - $used);
        if (!$stopped && $left >= $total - BP_EPS) {
            $used = bp_money($used + $total);
            // find the payment whose money completed this DR
            while ($pi < count($collected) && $running + $collected[$pi]['amount'] < $used - BP_EPS) {
                $running += $collected[$pi]['amount'];
                $pi++;
            }
            $row['state'] = 'paid';
            $row['applied'] = $total;
            $row['paydate'] = $pi < count($collected) ? $collected[$pi]['date'] : '';
        } elseif (!$stopped && $left > BP_EPS) {
            $row['state'] = 'partial';
            $row['applied'] = $left;
            $used = bp_money($used + $left);
            $stopped = true;
        } else {
            $stopped = true;
        }
        $rows[] = $row;
    }

    return array('rows' => $rows, 'credit' => bp_money($money - $used));
}

/* ---------------------------------------------------------------- data access */

// Buyer Billing only: sales / payments per buyer
function bp_buyer_sales($link, $buyer_id)
{
    $stmt = $link->prepare("SELECT docnum, trndte, COALESCE(trntot, 0) AS trntot, paydate, COALESCE(paydetails, '') AS paydetails, ordernum
                            FROM tranfile1 WHERE trncde = 'SAL' AND buyer_id = ?");
    $stmt->execute(array($buyer_id));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function bp_buyer_payments($link, $buyer_id)
{
    $stmt = $link->prepare("SELECT * FROM buyer_payment WHERE buyer_id = ? ORDER BY pay_date DESC, recid DESC");
    $stmt->execute(array($buyer_id));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// a customer's sales: SAL records with that cuscde
function bp_customer_sales($link, $cuscde)
{
    $stmt = $link->prepare("SELECT docnum, trndte, COALESCE(trntot, 0) AS trntot, paydate, COALESCE(paydetails, '') AS paydetails, ordernum
                            FROM tranfile1 WHERE trncde = 'SAL' AND cuscde = ?");
    $stmt->execute(array($cuscde));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function bp_customer_payments($link, $cuscde)
{
    $stmt = $link->prepare("SELECT * FROM customer_payment WHERE cuscde = ? ORDER BY pay_date DESC, recid DESC");
    $stmt->execute(array($cuscde));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function bp_fetch_payment($link, $recid)
{
    $stmt = $link->prepare("SELECT * FROM customer_payment WHERE recid = ?");
    $stmt->execute(array((int)$recid));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

// name of a RYU customer; null when the customer does not exist or is not a RYU customer
function bp_customer_name($link, $cuscde)
{
    $stmt = $link->prepare("SELECT cusdsc FROM customerfile WHERE cuscde = ? AND cusdsc LIKE ?");
    $stmt->execute(array($cuscde, BP_CUSTOMER_LIKE));
    $name = $stmt->fetchColumn();
    return $name === false ? null : (trim((string)$name) !== '' ? $name : $cuscde);
}

/**
 * Writes the matching result to tranfile1: sets paydate on newly covered sales and clears it
 * from sales this module had marked that are no longer covered. Returns number of sales changed.
 */
function bp_sync_customer($link, $cuscde)
{
    $sales = bp_customer_sales($link, $cuscde);
    $result = bp_allocate($sales, bp_customer_payments($link, $cuscde));

    $by_doc = array();
    foreach ($sales as $s) {
        $by_doc[$s['docnum']] = $s;
    }

    $set = $link->prepare("UPDATE tranfile1 SET paydate = ?, paydetails = ? WHERE docnum = ? AND trncde = 'SAL'");
    $clear = $link->prepare("UPDATE tranfile1 SET paydate = NULL, paydetails = '' WHERE docnum = ? AND trncde = 'SAL'");
    $changed = 0;

    foreach ($result['rows'] as $r) {
        if ($r['state'] === 'no_amount') {
            continue;
        }
        $s = $by_doc[$r['docnum']];
        // never change a paydate this module did not set (by hand, Sales Paid Upload, old buyer payments)
        if (bp_has_paydate($s['paydate']) && !bp_owned_mark((string)$s['paydetails'])) {
            continue;
        }
        $current = bp_has_paydate($s['paydate']) ? substr((string)$s['paydate'], 0, 10) : '';
        if ($r['state'] === 'paid') {
            if ($current !== $r['paydate'] || $s['paydetails'] !== BP_PAID_MARK) {
                $set->execute(array($r['paydate'], BP_PAID_MARK, $r['docnum']));
                $changed++;
            }
        } elseif ($current !== '') {     // owned mark but no longer covered
            $clear->execute(array($r['docnum']));
            $changed++;
        }
    }
    return $changed;
}

/* ---------------------------------------------------------------- summaries */

function bp_status_label($sales, $collected)
{
    if ($sales - $collected <= BP_EPS) {
        return 'Paid';
    }
    return $collected <= BP_EPS ? 'Unpaid' : 'Partial';
}

/**
 * Summary of one buyer from its DRs + payments.
 * The payments table is the single source of truth for collected amounts.
 */
function bp_summarize($sales, $payments)
{
    $s = array('sales' => 0.0, 'paid_outside' => 0.0, 'payments_collected' => 0.0, 'on_hand' => 0.0, 'returned' => 0.0,
               'dr_count' => count($sales), 'collected_count' => 0, 'on_hand_count' => 0, 'returned_count' => 0, 'last_collection' => '');
    foreach ($sales as $d) {
        $s['sales'] += (float)$d['trntot'];
    }
    foreach ($payments as $p) {
        $amt = (float)$p['amount'];
        if (bp_is_collected($p['status'])) {
            $s['payments_collected'] += $amt;
            $s['collected_count']++;
            $d = bp_effective_date($p);
            if ($d > $s['last_collection']) {
                $s['last_collection'] = $d;
            }
        } elseif (bp_is_on_hand($p['status'])) {
            $s['on_hand'] += $amt;
            $s['on_hand_count']++;
        } elseif (bp_is_returned($p['status'])) {
            $s['returned'] += $amt;
            $s['returned_count']++;
        }
    }
    foreach (array('sales', 'paid_outside', 'payments_collected', 'on_hand', 'returned') as $k) {
        $s[$k] = bp_money($s[$k]);
    }
    $s['collected'] = $s['payments_collected'];
    $s['outstanding'] = bp_money($s['sales'] - $s['collected']);
    $s['net'] = bp_money($s['outstanding'] - $s['on_hand']);
    $s['credit'] = $s['outstanding'] < -BP_EPS ? -$s['outstanding'] : 0.0;
    $s['status'] = bp_status_label($s['sales'], $s['collected']);
    return $s;
}

/**
 * RYU customer list with balances. A customer is listed only when some amount is not 0
 * (sales, collected, cheques on hand or returned); customers whose records all total 0 are hidden.
 */
function bp_customer_list_sql()
{
    $like = "'" . BP_CUSTOMER_LIKE . "'";
    return "SELECT c.cuscde, c.cusdsc,
                   COALESCE(s.sales, 0) AS sales, COALESCE(s.dr_count, 0) AS dr_count,
                   COALESCE(p.collected, 0) AS collected,
                   COALESCE(p.on_hand, 0) AS on_hand, COALESCE(p.returned, 0) AS returned,
                   COALESCE(s.sales, 0) - COALESCE(p.collected, 0) AS outstanding,
                   p.last_paid
            FROM customerfile c
            LEFT JOIN (SELECT cuscde, SUM(COALESCE(trntot, 0)) AS sales, COUNT(*) AS dr_count
                       FROM tranfile1
                       WHERE trncde = 'SAL' AND cuscde IN (SELECT cuscde FROM customerfile WHERE cusdsc LIKE $like)
                       GROUP BY cuscde) s ON s.cuscde = c.cuscde
            LEFT JOIN (SELECT cuscde,
                              SUM(CASE WHEN status IN ('RECEIVED','CLEARED') THEN amount ELSE 0 END) AS collected,
                              SUM(CASE WHEN status IN ('HOLD','FOR_DEPOSIT') THEN amount ELSE 0 END) AS on_hand,
                              SUM(CASE WHEN status IN ('BOUNCED','CLOSED_ACCOUNT') THEN amount ELSE 0 END) AS returned,
                              MAX(CASE WHEN status IN ('RECEIVED','CLEARED') THEN pay_date END) AS last_paid
                       FROM customer_payment GROUP BY cuscde) p ON p.cuscde = c.cuscde
            WHERE c.cusdsc LIKE $like
              AND (ABS(COALESCE(s.sales, 0)) > " . BP_EPS . "
                   OR COALESCE(p.collected, 0) > " . BP_EPS . "
                   OR COALESCE(p.on_hand, 0) > " . BP_EPS . "
                   OR COALESCE(p.returned, 0) > " . BP_EPS . ")";
}

function bp_status_sql($x = 'x')
{
    return "CASE WHEN $x.outstanding <= " . BP_EPS . " THEN 'Paid' WHEN $x.collected <= " . BP_EPS . " THEN 'Unpaid' ELSE 'Partial' END";
}

function bp_fetch_customers($link, $search, $status, $sort, $pageno, $page_action)
{
    $where = array('1 = 1');
    $params = array();
    if ($search !== '') {
        $where[] = "(x.cusdsc LIKE ? OR x.cuscde LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    $st = bp_status_sql();
    if (in_array($status, array('Paid', 'Partial', 'Unpaid'), true)) {
        $where[] = "$st = ?";
        $params[] = $status;
    }
    $base = "SELECT x.*, $st AS status FROM (" . bp_customer_list_sql() . ") x WHERE " . implode(' AND ', $where);

    $stmt = $link->prepare("SELECT COUNT(*) FROM ($base) y");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $pager = bp_pager($total, $pageno, $page_action, BP_PAGE_SIZE);
    $orders = array(
        'outstanding' => 'outstanding DESC, cusdsc ASC',
        'name'        => 'cusdsc ASC',
        'onhand'      => 'on_hand DESC, outstanding DESC',
    );
    $order = isset($orders[$sort]) ? $orders[$sort] : $orders['outstanding'];

    $stmt = $link->prepare("SELECT * FROM ($base) y ORDER BY $order LIMIT " . BP_PAGE_SIZE . " OFFSET " . (int)$pager['offset']);
    $stmt->execute($params);
    $rows = array();
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array(
            'cuscde' => $r['cuscde'],
            'cusdsc' => trim((string)$r['cusdsc']) !== '' ? $r['cusdsc'] : $r['cuscde'],
            'sales' => bp_money($r['sales']),
            'collected' => bp_money($r['collected']),
            'on_hand' => bp_money($r['on_hand']),
            'returned' => bp_money($r['returned']),
            'outstanding' => bp_money($r['outstanding']),
            'dr_count' => (int)$r['dr_count'],
            'last_paid' => $r['last_paid'] ? substr($r['last_paid'], 0, 10) : '',
            'status' => $r['status'],
        );
    }
    return array('rows' => $rows, 'totalrec' => $total) + $pager;
}

// Totals across all listed RYU customers (KPI cards + status counts). Ignores search/filter on purpose.
function bp_fetch_totals($link)
{
    $st = bp_status_sql();
    $sql = "SELECT COUNT(*) AS shops,
                   COALESCE(SUM(x.sales), 0) AS sales,
                   COALESCE(SUM(x.collected), 0) AS collected,
                   COALESCE(SUM(x.on_hand), 0) AS on_hand,
                   COALESCE(SUM(x.returned), 0) AS returned,
                   COALESCE(SUM(CASE WHEN x.outstanding > 0 THEN x.outstanding ELSE 0 END), 0) AS outstanding,
                   COALESCE(SUM(CASE WHEN x.outstanding - x.on_hand > 0 THEN x.outstanding - x.on_hand ELSE 0 END), 0) AS net,
                   COALESCE(SUM(CASE WHEN $st = 'Paid' THEN 1 ELSE 0 END), 0) AS paid,
                   COALESCE(SUM(CASE WHEN $st = 'Partial' THEN 1 ELSE 0 END), 0) AS partial,
                   COALESCE(SUM(CASE WHEN $st = 'Unpaid' THEN 1 ELSE 0 END), 0) AS unpaid
            FROM (" . bp_customer_list_sql() . ") x";
    $r = $link->query($sql)->fetch(PDO::FETCH_ASSOC);
    $out = array();
    foreach ($r as $k => $v) {
        $out[$k] = in_array($k, array('shops', 'paid', 'partial', 'unpaid'), true) ? (int)$v : bp_money($v);
    }
    return $out;
}

// Same paging rules as trn_salesfile1_ajax_pager.php (first_p / previous_p / next_p / last_p / same / search).
function bp_pager($totalrec, $pageno, $action, $limit)
{
    $maxpage = (int)ceil($totalrec / $limit);
    $pageno = (int)$pageno;
    if ($maxpage === 0) {
        return array('xpageno' => 1, 'maxpage' => 0, 'offset' => 0);
    }
    if ($pageno < 1 || $action === 'search' || $action === 'first_p') {
        $pageno = 1;
    }
    if ($action === 'next_p' && $pageno < $maxpage) {
        $pageno++;
    } elseif ($action === 'previous_p' && $pageno > 1) {
        $pageno--;
    } elseif ($action === 'last_p') {
        $pageno = $maxpage;
    }
    if ($pageno > $maxpage) {
        $pageno = $maxpage;
    }
    return array('xpageno' => $pageno, 'maxpage' => $maxpage, 'offset' => ($pageno - 1) * $limit);
}

/**
 * One page of a buyer's DRs for the pop-up (pure, no DB).
 * $rows: bp_allocate() rows (+ ordernum), oldest first. $filter: All | Unpaid | Paid | auto
 * ('auto' = Unpaid when the buyer has unpaid DRs, else All). $q: part of a DR No. or Order No.
 */
function bp_dr_page($rows, $filter, $q, $pageno, $page_action, $limit = BP_PAGE_SIZE)
{
    $counts = array('All' => count($rows), 'Unpaid' => 0, 'Paid' => 0);
    foreach ($rows as $r) {
        $counts[($r['state'] === 'unpaid' || $r['state'] === 'partial') ? 'Unpaid' : 'Paid']++;
    }
    if ($filter === 'auto' || !isset($counts[$filter])) {
        $filter = $counts['Unpaid'] > 0 ? 'Unpaid' : 'All';
    }
    $q = trim((string)$q);
    $list = array();
    $total_amount = 0.0;
    foreach ($rows as $r) {
        $is_unpaid = $r['state'] === 'unpaid' || $r['state'] === 'partial';
        if (($filter === 'Unpaid' && !$is_unpaid) || ($filter === 'Paid' && $is_unpaid)) {
            continue;
        }
        if ($q !== '' && stripos($r['docnum'], $q) === false && stripos(isset($r['ordernum']) ? $r['ordernum'] : '', $q) === false) {
            continue;
        }
        $list[] = $r;
        $total_amount += $r['trntot'];
    }
    $pager = bp_pager(count($list), $pageno, $page_action, $limit);
    return array('rows' => array_slice($list, $pager['offset'], $limit), 'counts' => $counts, 'filter' => $filter,
                 'totalrec' => count($list), 'total_amount' => bp_money($total_amount)) + $pager;
}

/**
 * One page of a buyer's payments for the History tab (pure, no DB).
 * $payments: newest first (bp_customer_payments). $filter: All | Collected | On hand | Returned.
 * $q: part of the reference no., bank, cheque no. or remarks.
 */
function bp_payment_page($payments, $filter, $q, $pageno, $page_action, $limit = 10)
{
    $group = function ($status) {
        return bp_is_collected($status) ? 'Collected' : (bp_is_on_hand($status) ? 'On hand' : (bp_is_returned($status) ? 'Returned' : ''));
    };
    $counts = array('All' => count($payments), 'Collected' => 0, 'On hand' => 0, 'Returned' => 0);
    foreach ($payments as $p) {
        $g = $group($p['status']);
        if ($g !== '') {
            $counts[$g]++;
        }
    }
    if (!isset($counts[$filter])) {
        $filter = 'All';
    }
    $q = trim((string)$q);
    $list = array();
    $total_amount = 0.0;
    foreach ($payments as $p) {
        if ($filter !== 'All' && $group($p['status']) !== $filter) {
            continue;
        }
        if ($q !== '') {
            $hay = $p['reference_no'] . ' ' . $p['bank_name'] . ' ' . $p['cheque_no'] . ' ' . $p['remarks'];
            if (stripos($hay, $q) === false) {
                continue;
            }
        }
        $list[] = $p;
        $total_amount += (float)$p['amount'];
    }
    $pager = bp_pager(count($list), $pageno, $page_action, $limit);
    return array('rows' => array_slice($list, $pager['offset'], $limit), 'counts' => $counts, 'filter' => $filter,
                 'totalrec' => count($list), 'total_amount' => bp_money($total_amount)) + $pager;
}

/* ---------------------------------------------------------------- billing statement */

define('BP_BILL_MAX_PAYMENTS', 100);

/**
 * Buyer billing statement (pure, no DB). Used by trn_buyerbilling_pdf.php.
 * $sales / $payments: as returned by bp_buyer_sales() / bp_buyer_payments().
 * $opts: as_of (Y-m-d, required), from / to (Y-m-d or ''), mode ('unpaid' = unpaid + partial, 'all'), limit (payments, 1..100).
 * Totals count only records and payments up to as_of. from / to only limit the two lists.
 * A cheque cleared or returned after as_of was still on hand on that date.
 */
function bp_billing_statement($sales, $payments, $opts)
{
    $as_of = $opts['as_of'];
    $from = isset($opts['from']) ? (string)$opts['from'] : '';
    $to = isset($opts['to']) ? (string)$opts['to'] : '';
    $mode = isset($opts['mode']) && $opts['mode'] === 'all' ? 'all' : 'unpaid';
    $limit = max(1, min(BP_BILL_MAX_PAYMENTS, (int)(isset($opts['limit']) ? $opts['limit'] : 20)));
    $in_range = function ($date) use ($from, $to) {
        return ($from === '' || $date >= $from) && ($to === '' || $date <= $to);
    };

    $sales_cut = array();
    $ordernum = array();
    foreach ($sales as $s) {
        if (substr((string)$s['trndte'], 0, 10) <= $as_of) {
            $sales_cut[] = $s;
            $ordernum[$s['docnum']] = isset($s['ordernum']) ? (string)$s['ordernum'] : '';
        }
    }

    $pay_cut = array();
    foreach ($payments as $p) {
        if (substr((string)$p['pay_date'], 0, 10) > $as_of) {
            continue;
        }
        $done = $p['status'] === 'CLEARED' ? $p['cleared_date'] : (bp_is_returned($p['status']) ? $p['returned_date'] : null);
        if (!empty($done) && substr((string)$done, 0, 10) > $as_of) {
            $p['status'] = 'FOR_DEPOSIT';
        }
        $pay_cut[] = $p;
    }

    $alloc = bp_allocate($sales_cut, $pay_cut);
    $sum = bp_summarize($sales_cut, $pay_cut);

    $records = array();
    $records_balance = 0.0;
    foreach ($alloc['rows'] as $r) {
        $open = $r['state'] === 'unpaid' || $r['state'] === 'partial';
        if (($mode === 'unpaid' && !$open) || !$in_range($r['trndte'])) {
            continue;
        }
        $r['ordernum'] = $ordernum[$r['docnum']];
        $r['balance'] = bp_money($r['trntot'] - $r['applied']);
        $records[] = $r;
        $records_balance += $r['balance'];
    }

    usort($pay_cut, function ($a, $b) {
        $c = strcmp(substr((string)$b['pay_date'], 0, 10), substr((string)$a['pay_date'], 0, 10));
        return $c !== 0 ? $c : (int)$b['recid'] - (int)$a['recid'];
    });
    $list = array();
    $list_collected = 0.0;
    foreach ($pay_cut as $p) {
        if (count($list) >= $limit) {
            break;
        }
        if (!$in_range(substr((string)$p['pay_date'], 0, 10))) {
            continue;
        }
        $list[] = $p;
        if (bp_is_collected($p['status'])) {
            $list_collected += (float)$p['amount'];
        }
    }

    return array(
        'billed' => $sum['sales'], 'paid' => $sum['collected'], 'on_hand' => $sum['on_hand'], 'returned' => $sum['returned'],
        'due' => max(0.0, $sum['outstanding']), 'credit' => $sum['credit'],
        'mode' => $mode, 'limit' => $limit,
        'records' => $records, 'records_balance' => bp_money($records_balance),
        'payments' => $list, 'payments_collected' => bp_money($list_collected),
    );
}

function bp_buyer_info($link, $buyer_id)
{
    $stmt = $link->prepare("SELECT buyer_id, buyer_name, buyer_address, buyer_contactnum FROM mf_buyers WHERE buyer_id = ?");
    $stmt->execute(array($buyer_id));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        return null;
    }
    if (trim((string)$r['buyer_name']) === '') {
        $r['buyer_name'] = $r['buyer_id'];
    }
    return $r;
}

// customer picker (type to search): RYU customers only, max 10
function bp_search_customers($link, $q)
{
    $stmt = $link->prepare("SELECT cuscde, cusdsc FROM customerfile
                            WHERE cusdsc LIKE ? AND (cusdsc LIKE ? OR cuscde LIKE ?)
                            ORDER BY cusdsc LIMIT 10");
    $stmt->execute(array(BP_CUSTOMER_LIKE, '%' . $q . '%', '%' . $q . '%'));
    $rows = array();
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array('cuscde' => $r['cuscde'], 'cusdsc' => trim((string)$r['cusdsc']) !== '' ? $r['cusdsc'] : $r['cuscde']);
    }
    return $rows;
}

// Buyer Billing buyer picker (type to search): any buyer in Buyers Profile, max 10
function bp_search_buyers($link, $q)
{
    $stmt = $link->prepare("SELECT buyer_id, buyer_name FROM mf_buyers WHERE buyer_name LIKE ? OR buyer_id LIKE ? ORDER BY buyer_name LIMIT 10");
    $stmt->execute(array('%' . $q . '%', '%' . $q . '%'));
    $rows = array();
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array('buyer_id' => $r['buyer_id'], 'buyer_name' => trim((string)$r['buyer_name']) !== '' ? $r['buyer_name'] : $r['buyer_id']);
    }
    return $rows;
}

// view / add / edit / delete for one page, from user_menus (admin has everything) - same rule as includes/main_header.php
function bp_page_permissions($link, $program)
{
    if ($_SESSION['userdesc'] === 'admin') {
        return array('view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1);
    }
    $stmt = $link->prepare("SELECT `view`, `add`, `edit`, `delete` FROM user_menus WHERE usercode = ? AND menprogram = ? LIMIT 1");
    $stmt->execute(array($_SESSION['usercode'], $program));
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);
    return $rs ? array_map('intval', $rs) : array('view' => 0, 'add' => 0, 'edit' => 0, 'delete' => 0);
}

/* ---------------------------------------------------------------- validation & save */

// "12,500.50" -> 12500.5 ; invalid -> null
function bp_parse_amount($raw)
{
    $s = str_replace(array(',', ' ', "\xE2\x82\xB1"), '', trim((string)$raw));
    if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $s)) {
        return null;
    }
    return bp_money($s);
}

function bp_valid_date($v)
{
    $v = trim((string)$v);
    $d = DateTime::createFromFormat('!Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : false;
}

/**
 * Validates posted payment fields. Returns array(errors => [field => msg], value => cleaned record fields).
 * $existing = the payment being edited (null when adding).
 */
function bp_validate_payment($link, $in, $today, $existing = null)
{
    $e = array();
    $v = array();
    $get = function ($k, $max) use ($in) {
        $s = isset($in[$k]) ? trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)$in[$k])) : '';
        return mb_substr($s, 0, $max, 'UTF-8');
    };

    $v['cuscde'] = $existing ? $existing['cuscde'] : $get('cuscde', 100);
    if ($v['cuscde'] === '' || bp_customer_name($link, $v['cuscde']) === null) {
        $e['shop'] = 'Select a RYU shop from the list.';
    }

    $date = $get('pay_date', 10);
    if ($date === '') {
        $e['pay_date'] = 'Payment date is required.';
    } elseif (!bp_valid_date($date)) {
        $e['pay_date'] = 'Enter a valid date.';
    } elseif ($date > $today) {
        $e['pay_date'] = 'Payment date cannot be in the future.';
    }
    $v['pay_date'] = $date;

    $raw = $get('amount', 30);
    $amount = bp_parse_amount($raw);
    if ($raw === '') {
        $e['amount'] = 'Amount is required.';
    } elseif (substr($raw, 0, 1) === '-') {
        $e['amount'] = 'Amount cannot be negative.';
    } elseif ($amount === null) {
        $e['amount'] = 'Use numbers only, up to 2 decimals (e.g. 12,500.00).';
    } elseif ($amount <= 0) {
        $e['amount'] = 'Amount must be greater than zero.';
    } elseif ($amount > BP_MAX_AMOUNT) {
        $e['amount'] = 'Amount is too large.';
    }
    $v['amount'] = $amount;

    $method = $get('method', 10);
    $methods = bp_methods();
    if (!isset($methods[$method])) {
        $e['method'] = 'Choose a payment method.';
    }
    $v['method'] = $method;

    $v['reference_no'] = $get('reference_no', 50);
    if (($method === 'GCASH' || $method === 'BANK') && $v['reference_no'] === '') {
        $e['reference_no'] = $method === 'GCASH' ? 'GCash reference number is required.' : 'Bank reference / deposit slip no. is required.';
    }

    $remarks_raw = isset($in['remarks']) ? trim((string)$in['remarks']) : '';
    if (mb_strlen($remarks_raw, 'UTF-8') > 200) {
        $e['remarks'] = 'Remarks can be up to 200 characters.';
    }
    $v['remarks'] = $get('remarks', 200);

    $v['bank_name'] = null;
    $v['cheque_no'] = null;
    $v['cheque_date'] = null;
    if ($method === 'CHEQUE') {
        $v['reference_no'] = null;
        $v['bank_name'] = $get('bank_name', 60);
        $v['cheque_no'] = $get('cheque_no', 30);
        $cd = $get('cheque_date', 10);
        $status = $get('status', 20);
        if ($v['bank_name'] === '') {
            $e['bank_name'] = 'Bank name is required.';
        }
        if ($v['cheque_no'] === '') {
            $e['cheque_no'] = 'Cheque number is required.';
        }
        if ($cd === '') {
            $e['cheque_date'] = 'Cheque date is required.';
        } elseif (!bp_valid_date($cd)) {
            $e['cheque_date'] = 'Enter a valid cheque date.';
        }
        $v['cheque_date'] = $cd;
        $statuses = bp_cheque_statuses();
        if (!isset($statuses[$status])) {
            $e['status'] = 'Choose a cheque status.';
        }
        $v['status'] = $status;
        $sd = $get('status_date', 10);
        $sd_err = isset($statuses[$status]) ? bp_status_date_error($status, $sd, $today) : null;
        if ($sd_err !== null) {
            $e['status_date'] = $sd_err;
        }
        $v['status_date'] = bp_status_date_label($status) !== '' ? $sd : null;
        if (!isset($e['shop']) && $v['bank_name'] !== '' && $v['cheque_no'] !== '') {
            $stmt = $link->prepare("SELECT recid FROM customer_payment WHERE cuscde = ? AND method = 'CHEQUE'
                                    AND LOWER(bank_name) = LOWER(?) AND LOWER(cheque_no) = LOWER(?) AND recid <> ?");
            $stmt->execute(array($v['cuscde'], $v['bank_name'], $v['cheque_no'], $existing ? (int)$existing['recid'] : 0));
            if ($stmt->fetchColumn() !== false) {
                $e['cheque_no'] = 'This cheque (' . $v['bank_name'] . ' #' . $v['cheque_no'] . ') is already recorded for this shop.';
            }
        }
    } else {
        $v['status'] = 'RECEIVED';
        $v['status_date'] = null;
        if ($v['reference_no'] === '') {
            $v['reference_no'] = null;
        }
    }
    if ($v['remarks'] === '') {
        $v['remarks'] = null;
    }
    return array('errors' => $e, 'value' => $v);
}

// same customer + date + amount + method + reference already saved (not for cheques - those are blocked above)
function bp_possible_duplicate($link, $v, $exclude_recid)
{
    if ($v['method'] === 'CHEQUE') {
        return false;
    }
    $stmt = $link->prepare("SELECT recid FROM customer_payment WHERE cuscde = ? AND pay_date = ? AND amount = ? AND method = ?
                            AND COALESCE(reference_no, '') = ? AND recid <> ?");
    $stmt->execute(array($v['cuscde'], $v['pay_date'], $v['amount'], $v['method'], (string)$v['reference_no'], (int)$exclude_recid));
    return $stmt->fetchColumn() !== false;
}

// label of the date a cheque status needs (Date Deposited / Cleared / Bounced / Closed Account); '' for Hold
function bp_status_date_label($status)
{
    $labels = array('FOR_DEPOSIT' => 'Date deposited', 'CLEARED' => 'Date cleared', 'BOUNCED' => 'Date returned', 'CLOSED_ACCOUNT' => 'Date returned');
    return isset($labels[$status]) ? $labels[$status] : '';
}

// error for the chosen status date, or null when fine. Any past date up to today is allowed (staff may encode late).
function bp_status_date_error($status, $date, $today)
{
    $label = bp_status_date_label($status);
    if ($label === '') {
        return null;
    }
    if ($date === '') {
        return $label . ' is required.';
    }
    if (!bp_valid_date($date)) {
        return 'Enter a valid ' . strtolower($label) . '.';
    }
    if ($date > $today) {
        return $label . ' cannot be in the future.';
    }
    return null;
}

/**
 * Dates that follow a cheque status. $date = the date the user picked for that status
 * (deposit / cleared / returned); null keeps the saved one, or today.
 */
function bp_status_dates($status, $existing, $today, $date = null)
{
    $dep = $existing && !empty($existing['deposited_date']) ? substr($existing['deposited_date'], 0, 10) : null;
    $out = array('deposited_date' => null, 'cleared_date' => null, 'returned_date' => null);
    if ($status === 'FOR_DEPOSIT') {
        $out['deposited_date'] = $date ? $date : ($dep ? $dep : $today);
    } elseif ($status === 'CLEARED' || bp_is_returned($status)) {
        $col = $status === 'CLEARED' ? 'cleared_date' : 'returned_date';
        $kept = $existing && $existing['status'] === $status && !empty($existing[$col]) ? substr($existing[$col], 0, 10) : null;
        $out[$col] = $date ? $date : ($kept ? $kept : $today);
        // a cheque is deposited on or before it clears / bounces
        $out['deposited_date'] = $dep && $dep <= $out[$col] ? $dep : $out[$col];
    }
    return $out;
}

/**
 * Inserts or updates a payment and re-matches the customer's sales, in one transaction.
 * Returns the payment recid.
 */
function bp_save_payment($link, $v, $usercode, $today, $existing = null)
{
    $link->beginTransaction();
    try {
        $rec = array(
            'pay_date' => $v['pay_date'], 'amount' => $v['amount'], 'method' => $v['method'], 'status' => $v['status'],
            'reference_no' => $v['reference_no'], 'bank_name' => $v['bank_name'], 'cheque_no' => $v['cheque_no'],
            'cheque_date' => $v['cheque_date'], 'remarks' => $v['remarks'],
        );
        $rec += bp_status_dates($v['status'], $existing && $existing['method'] === 'CHEQUE' ? $existing : null, $today,
                                isset($v['status_date']) ? $v['status_date'] : null);
        if ($existing) {
            $rec['updated_by'] = $usercode;
            $rec['updated_at'] = date('Y-m-d H:i:s');
            bp_update($link, $rec, (int)$existing['recid']);
            $recid = (int)$existing['recid'];
        } else {
            $rec['cuscde'] = $v['cuscde'];
            $rec['usercode'] = $usercode;
            $rec['created_at'] = date('Y-m-d H:i:s');
            bp_insert($link, $rec);
            $recid = (int)$link->lastInsertId();
        }
        bp_sync_customer($link, $v['cuscde']);
        $link->commit();
        return $recid;
    } catch (Exception $ex) {
        if ($link->inTransaction()) {
            $link->rollBack();
        }
        throw $ex;
    }
}

// $date = the deposit / cleared / returned date the user picked (null = keep saved one or today)
function bp_set_cheque_status($link, $payment, $status, $usercode, $today, $date = null)
{
    $link->beginTransaction();
    try {
        $rec = array('status' => $status, 'updated_by' => $usercode, 'updated_at' => date('Y-m-d H:i:s')) + bp_status_dates($status, $payment, $today, $date);
        bp_update($link, $rec, (int)$payment['recid']);
        bp_sync_customer($link, $payment['cuscde']);
        $link->commit();
    } catch (Exception $ex) {
        if ($link->inTransaction()) {
            $link->rollBack();
        }
        throw $ex;
    }
}

function bp_delete_payment($link, $payment)
{
    $link->beginTransaction();
    try {
        $link->prepare("DELETE FROM customer_payment WHERE recid = ?")->execute(array((int)$payment['recid']));
        bp_sync_customer($link, $payment['cuscde']);
        $link->commit();
    } catch (Exception $ex) {
        if ($link->inTransaction()) {
            $link->rollBack();
        }
        throw $ex;
    }
}

function bp_insert($link, $rec)
{
    $cols = array_keys($rec);
    $sql = "INSERT INTO customer_payment (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")";
    $link->prepare($sql)->execute(array_values($rec));
}

function bp_update($link, $rec, $recid)
{
    $sets = array();
    foreach (array_keys($rec) as $c) {
        $sets[] = "$c = ?";
    }
    $params = array_values($rec);
    $params[] = $recid;
    $link->prepare("UPDATE customer_payment SET " . implode(', ', $sets) . " WHERE recid = ?")->execute($params);
}

/**
 * What a not-yet-saved payment would do: list of sales that would become paid / partly covered.
 * $draft: amount, status, pay_date, cleared_date (for a cleared cheque); $edit_recid = payment being edited (replaced).
 */
function bp_preview($link, $cuscde, $draft, $edit_recid = 0, $limit = 10, $pageno = 1, $page_action = 'first_p', $filter = 'auto', $q = '')
{
    $sales = bp_customer_sales($link, $cuscde);
    $payments = bp_customer_payments($link, $cuscde);
    $before = bp_allocate($sales, $payments);

    $after_payments = array();
    foreach ($payments as $p) {
        if ((int)$p['recid'] !== (int)$edit_recid) {
            $after_payments[] = $p;
        }
    }
    if ($draft) {       // no amount yet: "after" = current state without the payment being edited
        $after_payments[] = array('recid' => PHP_INT_MAX, 'amount' => $draft['amount'], 'status' => $draft['status'],
                                  'pay_date' => $draft['pay_date'], 'cleared_date' => isset($draft['cleared_date']) ? $draft['cleared_date'] : null);
    }
    $after = bp_allocate($sales, $after_payments);

    $was = array();
    foreach ($before['rows'] as $r) {
        $was[$r['docnum']] = $r;
    }
    $ordernum = array();
    foreach ($sales as $s) {
        $ordernum[$s['docnum']] = (string)$s['ordernum'];
    }
    $changes = array();
    $all = array();         // every sales record, oldest first: state = now, after = with this payment
    $open_count = 0;
    $open_owed = 0.0;
    foreach ($after['rows'] as $r) {
        $w = $was[$r['docnum']];
        $row = array('docnum' => $r['docnum'], 'ordernum' => $ordernum[$r['docnum']], 'trndte' => $r['trndte'], 'trntot' => $r['trntot'],
                     'state' => $w['state'], 'after' => $r['state'], 'applied' => $r['applied'], 'applied_before' => $w['applied']);
        if ($r['state'] !== $w['state'] || abs($r['applied'] - $w['applied']) > BP_EPS) {
            $changes[] = $row;
        }
        if ($w['state'] === 'unpaid' || $w['state'] === 'partial') {
            $open_count++;
            $open_owed += $r['trntot'] - $w['applied'];
        }
        $all[] = $row;
    }
    // filter (All / Unpaid / Paid), search (DocNum / Order No.) and paging
    return array('changes' => $changes, 'open_count' => $open_count, 'open_owed' => bp_money($open_owed),
                 'credit_after' => $after['credit'], 'credit_before' => $before['credit'])
           + bp_dr_page($all, $filter, $q, $pageno, $page_action, $limit);
}
