<?php
/**
 * Standalone CLI test for trn_salespayments_lib.php (one-table design: partial_payment only).
 * Uses an in-memory SQLite database with fake rows - never touches the real DB, session or $_POST.
 * Run: php tests/test_trn_salespayments.php
 */

require_once __DIR__ . '/../resources/lx2.pdodb.php';
require_once __DIR__ . '/../trn_salespayments_lib.php';

$failures = 0;
function check($label, $ok)
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label\n";
    if (!$ok) {
        $failures++;
    }
}

function dr($link, $docnum)
{
    $stmt = $link->prepare("SELECT paydate, paydetails FROM tranfile1 WHERE docnum = ?");
    $stmt->execute(array($docnum));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function count_payments($link)
{
    return (int)$link->query("SELECT COUNT(*) FROM partial_payment")->fetchColumn();
}

function expect_exception($fn)
{
    try {
        $fn();
    } catch (Exception $e) {
        return true;
    }
    return false;
}

// --- fake data -------------------------------------------------------------
$link = new PDO('sqlite::memory:');
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$link->exec("CREATE TABLE tranfile1 (docnum TEXT, trncde TEXT, ordernum TEXT, trndte TEXT, buyer_id TEXT, route_id TEXT, salesman_id TEXT, trntot REAL, paydate TEXT, paydetails TEXT)");
$link->exec("CREATE TABLE partial_payment (recid INTEGER PRIMARY KEY AUTOINCREMENT, docnum TEXT, amount REAL, check_number TEXT, date_paid TEXT,
             pay_type TEXT, check_date TEXT, deposited_date TEXT, remarks TEXT, usercode TEXT, created_at TEXT)");
$link->exec("CREATE TABLE mf_buyers (buyer_id TEXT, buyer_name TEXT)");
$link->exec("CREATE TABLE mf_routes (route_id TEXT, route_desc TEXT)");
$link->exec("CREATE TABLE mf_salesman (salesman_id TEXT, salesman_name TEXT)");

$link->exec("INSERT INTO mf_buyers VALUES ('B1','Alpha Store'), ('B1','Alpha Store'), ('B2','Beta Mart')");
$link->exec("INSERT INTO mf_routes VALUES ('R1','North'), ('R2','South')");
$link->exec("INSERT INTO mf_salesman VALUES ('S1','Pedro')");
$link->exec("INSERT INTO tranfile1 VALUES
    ('D1','SAL','ORD-1','2026-08-01','B1','R1','S1',1000,NULL,''),
    ('D2','SAL','ORD-2','2026-09-01','B1','R1','S1',500,NULL,''),
    ('D3','SAL','ORD-3','2026-07-01','B1','R1','S1',300,'2026-07-05','cash'),
    ('D4','SAL','ORD-4','2026-09-10','B2','R2','S1',200,NULL,''),
    ('R9','SRT','RT-9','2026-09-10','B1','R1','S1',999,NULL,''),
    ('D9','SAL','ORD-9','2026-09-10','','R1','S1',999,NULL,'')");
// old payment entered from the Sales screen: no pay_type, has a check number
$link->exec("INSERT INTO partial_payment (docnum, amount, check_number, date_paid) VALUES ('D1', 100, 'OLD-77', '2026-08-05')");

$today = '2026-09-30';

// --- validation ------------------------------------------------------------
$base = array('buyer_id' => 'B1', 'docnum' => 'D2', 'pay_date' => '2026-09-30', 'pay_type' => 'CASH', 'amount' => 500.0, 'check_number' => '', 'check_date' => '', 'remarks' => '');
check('valid cash payment passes', cp_validate_payment($base) === '');
check('missing customer rejected', cp_validate_payment(array_merge($base, array('buyer_id' => ''))) !== '');
check('missing record (docnum) rejected', cp_validate_payment(array_merge($base, array('docnum' => ''))) !== '');
check('zero amount rejected', cp_validate_payment(array_merge($base, array('amount' => 0.0))) !== '');
check('unknown method rejected', cp_validate_payment(array_merge($base, array('pay_type' => 'GCASH'))) !== '');
check('check without number rejected', cp_validate_payment(array_merge($base, array('pay_type' => 'CHECK', 'check_date' => '2026-11-30'))) !== '');
check('check without check date rejected', cp_validate_payment(array_merge($base, array('pay_type' => 'CHECK', 'check_number' => '123'))) !== '');
check('date 09/30/2026 -> 2026-09-30', cp_to_db_date('09/30/2026') === '2026-09-30');
check('invalid date 13/01/2026 -> false', cp_to_db_date('13/01/2026') === false);

// --- customers / summary / records -----------------------------------------
$list = cp_fetch_customers($link, '', '', 'balance', 1, 10);
check('two customers with balance, SRT and blank buyer excluded', $list['total'] === 2);
check('Alpha balance = 1000 - 100 + 500 (manual-paid D3 = 0)', $list['rows'][0]['buyer_id'] === 'B1' && $list['rows'][0]['balance'] === 1400.0);
check('search "Beta" -> Beta Mart only', array_column(cp_fetch_customers($link, 'Beta', '', 'balance', 1, 10)['rows'], 'buyer_id') === array('B2'));
$paged = cp_fetch_customers($link, '', '', 'all', 2, 1);
check('pager: page 2 of size 1 -> Beta Mart, total 2', $paged['total'] === 2 && array_column($paged['rows'], 'buyer_id') === array('B2'));

$sum = cp_fetch_summary($link, 'B1', $today);
check('summary: sales 1800, balance 1400, paid 400', $sum['total_sales'] === 1800.0 && $sum['balance'] === 1400.0 && $sum['paid'] === 400.0);
check('old Sales-screen check is NOT counted as check on hand', $sum['checks_amount'] === 0.0 && $sum['checks_count'] === 0);
$drs = cp_fetch_drs($link, 'B1', true, 1, 10, $today);
check('open records oldest first: D1 (balance 900), D2 (500)', array_column($drs['rows'], 'balance', 'docnum') === array('D1' => 900.0, 'D2' => 500.0));

$history = cp_fetch_payments($link, 'B1', false, 1, 10);
check('payment history includes the old Sales-screen payment with its docnum', $history['total'] === 1 && $history['rows'][0]['docnum'] === 'D1' && $history['rows'][0]['pay_type'] === null);

// --- record a check on one record ------------------------------------------
$check_pay = array('buyer_id' => 'B1', 'docnum' => 'D1', 'pay_date' => '2026-09-30', 'pay_type' => 'CHECK', 'amount' => 900.0, 'check_number' => 'BDO-1', 'check_date' => '2026-11-30', 'remarks' => 'BDO');
$pid = cp_save_payment($link, $check_pay, 'tester');
$row = cp_fetch_payment($link, $pid);
check('payment row saved with docnum, type, check date, user', $row['docnum'] === 'D1' && $row['pay_type'] === 'CHECK' && $row['check_date'] === '2026-11-30' && $row['usercode'] === 'tester' && $row['buyer_id'] === 'B1');
check('D1 now fully paid -> paydate set with module mark', dr($link, 'D1')['paydate'] === '2026-09-30' && dr($link, 'D1')['paydetails'] === CP_PAID_MARK);
check('manual paydate on D3 untouched', dr($link, 'D3')['paydate'] === '2026-07-05' && dr($link, 'D3')['paydetails'] === 'cash');
$sum = cp_fetch_summary($link, 'B1', $today);
check('check reduces balance on receipt: balance 500', $sum['balance'] === 500.0);
check('check shows as on hand: 900, 1 check', $sum['checks_amount'] === 900.0 && $sum['checks_count'] === 1);
check('checks-on-hand list has the check', cp_fetch_payments($link, 'B1', true, 1, 10)['total'] === 1);

// --- guards ------------------------------------------------------------------
$before = count_payments($link);
check('amount over the record balance throws', expect_exception(function () use ($link, $base) {
    cp_save_payment($link, array_merge($base, array('amount' => 600.0)), 'tester');
}));
check('payment on another customer\'s record throws', expect_exception(function () use ($link, $base) {
    cp_save_payment($link, array_merge($base, array('docnum' => 'D4', 'amount' => 50.0)), 'tester');
}));
check('payment on a manually paid record (balance 0) throws', expect_exception(function () use ($link, $base) {
    cp_save_payment($link, array_merge($base, array('docnum' => 'D3', 'amount' => 10.0)), 'tester');
}));
check('failed saves leave no rows behind', count_payments($link) === $before);

// --- edit ------------------------------------------------------------------
$open = cp_fetch_open_drs($link, 'B1', cp_fetch_payment($link, $pid));
check('edit view: D1 available 900 (its own amount added back), D2 500', array_column($open, 'available', 'docnum') === array('D1' => 900.0, 'D2' => 500.0));
cp_save_payment($link, array_merge($check_pay, array('pay_type' => 'CASH', 'amount' => 400.0)), 'tester', $pid);
$row = cp_fetch_payment($link, $pid);
check('edit to cash 400: row updated, check date cleared', $row['pay_type'] === 'CASH' && (float)$row['amount'] === 400.0 && $row['check_date'] === null && $row['check_number'] === '');
check('D1 no longer fully paid -> module paydate cleared', dr($link, 'D1')['paydate'] === null && dr($link, 'D1')['paydetails'] === '');
check('edit keeps a single row (no duplicates)', count_payments($link) === 2);
cp_save_payment($link, array_merge($check_pay, array('docnum' => 'D2', 'pay_type' => 'CASH', 'amount' => 500.0)), 'tester', $pid);
check('edit can move a payment to another record: D2 paid, D1 back to 900', dr($link, 'D2')['paydetails'] === CP_PAID_MARK && cp_fetch_drs($link, 'B1', true, 1, 10, $today)['rows'][0]['balance'] === 900.0);

// --- mark deposited ---------------------------------------------------------
check('cash payment cannot be marked deposited', cp_mark_deposited($link, $pid, '2026-10-01') === false);
$pid2 = cp_save_payment($link, array_merge($check_pay, array('amount' => 300.0)), 'tester');
check('new check on hand = 300', cp_fetch_summary($link, 'B1', $today)['checks_amount'] === 300.0);
check('mark deposited succeeds (and again with same date)', cp_mark_deposited($link, $pid2, '2026-11-30') && cp_mark_deposited($link, $pid2, '2026-11-30'));
check('deposited check no longer on hand', cp_fetch_summary($link, 'B1', $today)['checks_amount'] === 0.0);

// --- delete -----------------------------------------------------------------
check('delete returns true', cp_delete_payment($link, $pid) === true);
check('deleted payment gone, D2 paydate cleared', cp_fetch_payment($link, $pid) === null && dr($link, 'D2')['paydate'] === null);
check('deleting a missing payment returns false', cp_delete_payment($link, 99999) === false);
check('balance after delete = 1000 - 100 - 300 + 500 = 1100', cp_fetch_summary($link, 'B1', $today)['balance'] === 1100.0);

// --- Sales-screen payments also sync paydate --------------------------------
$link->exec("INSERT INTO partial_payment (docnum, amount, check_number, date_paid) VALUES ('D4', 200, '', '2026-09-20')");
cp_sync_paid_status($link, array('D4'));
check('Sales-screen payments covering the total set paydate', dr($link, 'D4')['paydate'] === '2026-09-20' && dr($link, 'D4')['paydetails'] === CP_PAID_MARK);

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
