<?php
/**
 * Standalone CLI test for trn_buyerpayments_lib.php.
 * Uses an in-memory SQLite database with fake rows - never touches the real DB, session or $_POST.
 * Run: php tests/test_trn_buyerpayments.php
 */

require_once __DIR__ . '/../trn_buyerpayments_lib.php';

$failures = 0;
function check($label, $ok, $detail = '')
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label" . (!$ok && $detail !== '' ? "  [$detail]" : '') . "\n";
    if (!$ok) {
        $failures++;
    }
}

/**
 * Customers (customerfile) and their sales (tranfile1.cuscde). The page calls them "shops"; only cusdsc LIKE '%RYU%' count.
 *  S1 RYU Tiktok        : SAL-A 5,000 Oct 1, SAL-B 3,000 Oct 5, SAL-C 2,000 Oct 9 (+ PUR-1 4,444: not a sale, ignored)
 *  S2 RYU Ñiño Shopee   : SAL-X 1,000 (paydate typed by hand), SAL-Y 1,500, SAL-Z 0 (cancelled)
 *  S5 RYU Lazada        : SAL-O 7,650 already marked paid by the old buyer payments
 *  S3 Other Shop        : SAL-OT 9,999 (not RYU - never shown)
 *  S4 RYU Empty         : no sales
 *  SAL-P 800 has no customer at all
 * tranfile2 is not used any more: a sale belongs to a customer through tranfile1.cuscde only.
 */
function fresh_db()
{
    $link = new PDO('sqlite::memory:');
    $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $link->exec("CREATE TABLE tranfile1 (docnum TEXT, trncde TEXT, ordernum TEXT, trndte TEXT, buyer_id TEXT, trntot REAL, paydate TEXT, paydetails TEXT, cuscde TEXT)");
    $link->exec("CREATE TABLE customerfile (cuscde TEXT, cusdsc TEXT)");
    $link->exec("CREATE TABLE mf_buyers (buyer_id TEXT, buyer_name TEXT)");
    $link->exec("CREATE TABLE customer_payment (recid INTEGER PRIMARY KEY AUTOINCREMENT, cuscde TEXT, pay_date TEXT, amount REAL, method TEXT, status TEXT,
                 reference_no TEXT, bank_name TEXT, cheque_no TEXT, cheque_date TEXT, deposited_date TEXT, cleared_date TEXT, returned_date TEXT,
                 remarks TEXT, usercode TEXT, created_at TEXT, updated_by TEXT, updated_at TEXT)");
    $link->exec("INSERT INTO mf_buyers VALUES ('JUAN','Juan Store'), ('ANA','Ana''s Ñiño Mart'), ('NOSALE','No Sales Yet'), ('OLD','Old Buyer')");
    $link->exec("INSERT INTO customerfile VALUES ('S1','RYU Tiktok'), ('S2','RYU Ñiño Shopee'), ('S3','Other Shop'), ('S4','RYU Empty'), ('S5','RYU Lazada')");
    $link->exec("INSERT INTO tranfile1 VALUES
        ('SAL-A','SAL','O-A','2026-10-01',NULL,5000,NULL,'','S1'),
        ('SAL-B','SAL','O-B','2026-10-05',NULL,3000,NULL,'','S1'),
        ('SAL-C','SAL','O-C','2026-10-09',NULL,2000,NULL,'','S1'),
        ('PUR-1','PUR','O-PUR','2026-10-01',NULL,4444,NULL,'','S1'),
        ('SAL-X','SAL','O-X','2026-10-02',NULL,1000,'2026-10-03','CASH','S2'),
        ('SAL-Y','SAL','O-Y','2026-10-04',NULL,1500,NULL,'','S2'),
        ('SAL-Z','SAL','O-Z','2026-10-06',NULL,0,'2026-10-06','CANCELLED','S2'),
        ('SAL-P','SAL','O-P','2026-10-01',NULL,800,NULL,'',NULL),
        ('SAL-O','SAL','O-O','2023-11-17',NULL,7650,'2026-10-21','Paid via buyer payments','S5'),
        ('SAL-OT','SAL','O-OT','2026-10-10',NULL,9999,NULL,'','S3')");
    return $link;
}

function dr($link, $docnum)
{
    $stmt = $link->prepare("SELECT paydate, paydetails FROM tranfile1 WHERE docnum = ?");
    $stmt->execute(array($docnum));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function pay($link, $shop, $fields, $today = '2026-11-19', $existing = null)
{
    $r = bp_validate_payment($link, $fields + array('cuscde' => $shop), $today, $existing);
    if ($r['errors']) {
        throw new Exception('validation: ' . json_encode($r['errors']));
    }
    return bp_save_payment($link, $r['value'], 'TESTER', $today, $existing);
}

function summary($link, $shop)
{
    return bp_summarize(bp_customer_sales($link, $shop), bp_customer_payments($link, $shop));
}

// add a customer with its own sales: $sales = [docnum => [date, amount, paydate, paydetails]]
function add_shop($link, $cuscde, $title, $sales = array())
{
    $link->prepare("INSERT INTO customerfile VALUES (?, ?)")->execute(array($cuscde, $title));
    foreach ($sales as $doc => $s) {
        $link->prepare("INSERT INTO tranfile1 VALUES (?, 'SAL', ?, ?, NULL, ?, ?, ?, ?)")
             ->execute(array($doc, 'O-' . $doc, $s[0], $s[1], isset($s[2]) ? $s[2] : null, isset($s[3]) ? $s[3] : '', $cuscde));
    }
}

/* ---------------- pure matching ---------------- */
$sales = array(
    array('docnum' => 'C', 'trndte' => '2020-01-01', 'trntot' => 30000, 'paydate' => null, 'paydetails' => ''),
    array('docnum' => 'A', 'trndte' => '2016-01-01', 'trntot' => 10000, 'paydate' => null, 'paydetails' => ''),
    array('docnum' => 'B', 'trndte' => '2018-01-01', 'trntot' => 15000, 'paydate' => null, 'paydetails' => ''),
);
$res = bp_allocate($sales, array(array('recid' => 1, 'amount' => 30000, 'status' => 'RECEIVED', 'pay_date' => '2026-11-01', 'cleared_date' => null)));
$states = array();
foreach ($res['rows'] as $r) {
    $states[] = $r['docnum'] . ':' . $r['state'] . ':' . $r['applied'];
}
check('2016 & 2018 paid first, 2020 gets the remaining 5,000', $states === array('A:paid:10000', 'B:paid:15000', 'C:partial:5000'), implode(' ', $states));
check('Paid DRs get the payment date', $res['rows'][0]['paydate'] === '2026-11-01');

$res = bp_allocate($sales, array(array('recid' => 1, 'amount' => 30000, 'status' => 'HOLD', 'pay_date' => '2026-11-01', 'cleared_date' => null)));
check('Cheque on hold pays nothing', $res['rows'][0]['state'] === 'unpaid' && $res['credit'] == 0);

$res = bp_allocate($sales, array(
    array('recid' => 1, 'amount' => 4000, 'status' => 'RECEIVED', 'pay_date' => '2026-10-01', 'cleared_date' => null),
    array('recid' => 2, 'amount' => 7000, 'status' => 'RECEIVED', 'pay_date' => '2026-10-15', 'cleared_date' => null),
));
check('DR completed by the 2nd payment gets the 2nd payment date', $res['rows'][0]['state'] === 'paid' && $res['rows'][0]['paydate'] === '2026-10-15');

$small = array(
    array('docnum' => 'A', 'trndte' => '2026-01-01', 'trntot' => 3000, 'paydate' => null, 'paydetails' => ''),
    array('docnum' => 'B', 'trndte' => '2026-01-02', 'trntot' => 500, 'paydate' => null, 'paydetails' => ''),
);
$res = bp_allocate($small, array(array('recid' => 1, 'amount' => 1000, 'status' => 'RECEIVED', 'pay_date' => '2026-02-01', 'cleared_date' => null)));
check('Strict oldest-first: smaller newer DR waits behind a partly covered older DR', $res['rows'][0]['state'] === 'partial' && $res['rows'][1]['state'] === 'unpaid');

$res = bp_allocate($small, array(array('recid' => 1, 'amount' => 5000, 'status' => 'CLEARED', 'pay_date' => '2026-02-01', 'cleared_date' => '2026-02-05')));
check('Overpayment kept as credit (1,500)', abs($res['credit'] - 1500) < 0.01, (string)$res['credit']);
check('Cleared cheque uses its payment date (from the form), not the cleared date', $res['rows'][0]['paydate'] === '2026-02-01');

$same = array(
    array('docnum' => 'SAL-2', 'trndte' => '2026-01-01', 'trntot' => 100, 'paydate' => null, 'paydetails' => ''),
    array('docnum' => 'SAL-1', 'trndte' => '2026-01-01', 'trntot' => 100, 'paydate' => null, 'paydetails' => ''),
);
$res = bp_allocate($same, array(array('recid' => 1, 'amount' => 100, 'status' => 'RECEIVED', 'pay_date' => '2026-02-01', 'cleared_date' => null)));
check('Same date: lower DR number first', $res['rows'][0]['docnum'] === 'SAL-1' && $res['rows'][0]['state'] === 'paid');

/* ---------------- RYU Tiktok (S1) walkthrough against the fake DB ---------------- */
$link = fresh_db();
check('Customer sales: only SAL records with that cuscde (PUR-1 ignored)', count(bp_customer_sales($link, 'S1')) === 3);
pay($link, 'S1', array('pay_date' => '2026-10-20', 'amount' => '6,000', 'method' => 'CASH'));
$a = dr($link, 'SAL-A');
$b = dr($link, 'SAL-B');
check('Step 1: Sale A marked paid Oct 20 via shop payments', $a['paydate'] === '2026-10-20' && $a['paydetails'] === BP_PAID_MARK, json_encode($a));
check('Step 1: Sale B still unpaid', $b['paydate'] === null);
$s = summary($link, 'S1');
check('Step 1: S1 collected 6,000 / outstanding 4,000 / Partial', $s['collected'] == 6000 && $s['outstanding'] == 4000 && $s['status'] === 'Partial', json_encode($s));

$chq = pay($link, 'S1', array('pay_date' => '2026-11-05', 'amount' => '4000', 'method' => 'CHEQUE', 'bank_name' => 'BDO', 'cheque_no' => '777', 'cheque_date' => '2026-11-10', 'status' => 'HOLD'));
$s = summary($link, 'S1');
check('Step 2: cheque on hold - B/C unchanged, on hand 4,000, net 0', dr($link, 'SAL-B')['paydate'] === null && $s['on_hand'] == 4000 && $s['net'] == 0 && $s['outstanding'] == 4000);

$p = bp_fetch_payment($link, $chq);
bp_set_cheque_status($link, $p, 'CLEARED', 'TESTER', '2026-11-10');
$s = summary($link, 'S1');
check('Step 3a: cheque cleared on Nov 10 - B and C get the cheque payment date Nov 5 (not today), S1 Paid', dr($link, 'SAL-B')['paydate'] === '2026-11-05' && dr($link, 'SAL-C')['paydate'] === '2026-11-05' && $s['status'] === 'Paid');

$p = bp_fetch_payment($link, $chq);
bp_set_cheque_status($link, $p, 'BOUNCED', 'TESTER', '2026-11-12');
$s = summary($link, 'S1');
$p = bp_fetch_payment($link, $chq);
check('Step 3b: cheque bounced - B and C back to unpaid, Sale A stays paid', dr($link, 'SAL-B')['paydate'] === null && dr($link, 'SAL-C')['paydate'] === null && dr($link, 'SAL-A')['paydate'] === '2026-10-20');
check('Step 3b: bounced cheque counted as returned, outstanding back to 4,000', $s['returned'] == 4000 && $s['outstanding'] == 4000 && $s['collected'] == 6000);
check('Step 3b: deposited / returned dates recorded', $p['deposited_date'] === '2026-11-10' && $p['returned_date'] === '2026-11-12', json_encode($p));

/* ---------------- cheque status dates picked by the user (encoded late) ---------------- */
$lk = fresh_db();
$c2 = pay($lk, 'S1', array('pay_date' => '2026-11-01', 'amount' => '5000', 'method' => 'CHEQUE', 'bank_name' => 'BPI', 'cheque_no' => 'D1', 'cheque_date' => '2026-11-03', 'status' => 'HOLD'));
bp_set_cheque_status($lk, bp_fetch_payment($lk, $c2), 'FOR_DEPOSIT', 'TESTER', '2026-11-20', '2026-11-04');
$p = bp_fetch_payment($lk, $c2);
check('For Deposit on a picked date (11/04) while encoding on 11/20', $p['status'] === 'FOR_DEPOSIT' && $p['deposited_date'] === '2026-11-04' && $p['cleared_date'] === null, json_encode($p));
bp_set_cheque_status($lk, $p, 'CLEARED', 'TESTER', '2026-11-20', '2026-11-06');
$p = bp_fetch_payment($lk, $c2);
check('Cleared on a picked date (11/06), deposit date kept (11/04)', $p['cleared_date'] === '2026-11-06' && $p['deposited_date'] === '2026-11-04', json_encode($p));
check('Cleared cheque pays SAL-A with the cheque payment date 11/01', dr($lk, 'SAL-A')['paydate'] === '2026-11-01');
bp_set_cheque_status($lk, $p, 'BOUNCED', 'TESTER', '2026-11-20', '2026-11-08');
$p = bp_fetch_payment($lk, $c2);
check('Bounced on a picked date (11/08): returned date set, cleared date cleared, SAL-A unpaid again',
      $p['returned_date'] === '2026-11-08' && $p['cleared_date'] === null && $p['deposited_date'] === '2026-11-04' && dr($lk, 'SAL-A')['paydate'] === null, json_encode($p));
bp_set_cheque_status($lk, $p, 'CLOSED_ACCOUNT', 'TESTER', '2026-11-20', '2026-11-09');
check('Closed Account uses the picked returned date', bp_fetch_payment($lk, $c2)['returned_date'] === '2026-11-09');
check('Cleared date earlier than the old deposit date pulls the deposit date back', bp_status_dates('CLEARED', array('status' => 'FOR_DEPOSIT', 'deposited_date' => '2026-11-10'), '2026-11-20', '2026-11-05')
      === array('deposited_date' => '2026-11-05', 'cleared_date' => '2026-11-05', 'returned_date' => null));
check('No picked date: today is used', bp_status_dates('CLEARED', null, '2026-11-20')['cleared_date'] === '2026-11-20');
check('Hold has no dates', bp_status_dates('HOLD', array('status' => 'CLEARED', 'deposited_date' => '2026-11-04'), '2026-11-20', '2026-11-05')
      === array('deposited_date' => null, 'cleared_date' => null, 'returned_date' => null));
check('Status date: required', bp_status_date_error('CLEARED', '', '2026-11-20') === 'Date cleared is required.');
check('Status date: not in the future', strpos((string)bp_status_date_error('CLEARED', '2026-11-21', '2026-11-20'), 'future') !== false);
check('Status date: no earliest limit (a very old date is fine)', bp_status_date_error('BOUNCED', '2020-01-01', '2026-11-20') === null);
check('Status date: invalid date rejected', bp_status_date_error('FOR_DEPOSIT', '2026-02-30', '2026-11-20') !== null);
check('Status date: same day as payment and today are both fine', bp_status_date_error('CLEARED', '2026-11-01', '2026-11-20') === null
      && bp_status_date_error('CLEARED', '2026-11-20', '2026-11-20') === null);
check('Status date: Hold needs no date', bp_status_date_error('HOLD', '', '2026-11-20') === null);

// edit the cash payment down to 4,000 -> Sale A no longer covered
$cash = bp_customer_payments($link, 'S1');
$cash_row = null;
foreach ($cash as $c) {
    if ($c['method'] === 'CASH') {
        $cash_row = $c;
    }
}
pay($link, 'S1', array('pay_date' => '2026-10-20', 'amount' => '4000', 'method' => 'CASH'), '2026-11-19', $cash_row);
check('Edit payment down: Sale A un-marked (only 4,000 of 5,000 covered)', dr($link, 'SAL-A')['paydate'] === null);

bp_delete_payment($link, bp_fetch_payment($link, $cash_row['recid']));
$s = summary($link, 'S1');
check('Delete payment: S1 back to Unpaid', $s['status'] === 'Unpaid' && $s['collected'] == 0);

/* ---------------- tranfile1 pay markers do not affect collected totals ---------------- */
pay($link, 'S2', array('pay_date' => '2026-11-01', 'amount' => '1500', 'method' => 'GCASH', 'reference_no' => '1009 882 341'));
$x = dr($link, 'SAL-X');
$z = dr($link, 'SAL-Z');
check('Hand-typed paydate never changed, even though customer_payment now covers that sale', $x['paydate'] === '2026-10-03' && $x['paydetails'] === 'CASH');
check('Cancelled 0-value sale untouched', $z['paydetails'] === 'CANCELLED');
check('paydate ignored for matching: oldest sale (X) takes 1,000, Y only 500 -> Y stays unpaid in Sales', dr($link, 'SAL-Y')['paydate'] === null);
$rows = bp_allocate(bp_customer_sales($link, 'S2'), bp_customer_payments($link, 'S2'))['rows'];
$st = array();
foreach ($rows as $r) {
    $st[] = $r['docnum'] . ':' . $r['state'] . ':' . $r['applied'];
}
check('S2 sale states: X paid, Y partly (500), Z no amount', $st === array('SAL-X:paid:1000', 'SAL-Y:partial:500', 'SAL-Z:no_amount:0'), implode(' ', $st));
$s = summary($link, 'S2');
check('S2: collected comes only from 1,500 GCash in customer_payment', $s['collected'] == 1500 && $s['status'] === 'Partial', json_encode($s));
check('Sale without a shop untouched', dr($link, 'SAL-P')['paydate'] === null);

/* ---------------- paydate set by the old buyer payments is never changed ---------------- */
$s = summary($link, 'S5');
check('S5: sale marked paid by buyer payments still owes 7,650 here (no shop payment yet)', $s['outstanding'] == 7650 && $s['status'] === 'Unpaid', json_encode($s));
bp_sync_customer($link, 'S5');
check('Sync keeps the buyer-payments paydate and mark', dr($link, 'SAL-O')['paydate'] === '2026-10-21' && dr($link, 'SAL-O')['paydetails'] === 'Paid via buyer payments');
pay($link, 'S5', array('pay_date' => '2026-11-19', 'amount' => '7650', 'method' => 'CASH'));
check('Paying S5 in full: Paid, buyer-payments mark still untouched', summary($link, 'S5')['status'] === 'Paid' && dr($link, 'SAL-O')['paydetails'] === 'Paid via buyer payments');

/* ---------------- validation ---------------- */
$v = function ($fields) use ($link) {
    return bp_validate_payment($link, $fields, '2026-11-19')['errors'];
};
$base = array('cuscde' => 'S1', 'pay_date' => '2026-11-19', 'amount' => '100', 'method' => 'CASH');
foreach (array('', '0', '0.00', '-5', 'abc', '12.345', '1e5', '1000000000') as $amt) {
    $e = $v(array('amount' => $amt) + $base);
    check("Amount '$amt' rejected", isset($e['amount']));
}
check('Amount with comma and peso sign accepted', !$v(array('amount' => "\xE2\x82\xB1 12,500.50") + $base));
check('Future date rejected', isset($v(array('pay_date' => '2026-12-25') + $base)['pay_date']));
check('Invalid date rejected', isset($v(array('pay_date' => '2026-02-30') + $base)['pay_date']));
check('Unknown shop rejected', isset($v(array('cuscde' => 'NOPE') + $base)['shop']));
check('Non-RYU shop rejected', isset($v(array('cuscde' => 'S3') + $base)['shop']));
check('Unknown method rejected', isset($v(array('method' => 'BTC') + $base)['method']));
check('GCash needs reference', isset($v(array('method' => 'GCASH') + $base)['reference_no']));
check('Bank needs reference', isset($v(array('method' => 'BANK') + $base)['reference_no']));
$e = $v(array('method' => 'CHEQUE') + $base);
check('Cheque needs bank, number, date, status', isset($e['bank_name'], $e['cheque_no'], $e['cheque_date'], $e['status']));
$chq_base = array('method' => 'CHEQUE', 'bank_name' => 'Landbank', 'cheque_no' => 'F-1', 'cheque_date' => '2026-11-18', 'pay_date' => '2026-11-15') + $base;
check('Form: Cleared cheque needs its cleared date', isset($v(array('status' => 'CLEARED') + $chq_base)['status_date']));
check('Form: Cleared date before the payment date is allowed (no earliest limit)', !isset($v(array('status' => 'CLEARED', 'status_date' => '2026-11-14') + $chq_base)['status_date']));
check('Form: Hold cheque needs no date', !isset($v(array('status' => 'HOLD') + $chq_base)['status_date']));
$r = bp_validate_payment($link, array('status' => 'FOR_DEPOSIT', 'status_date' => '2026-11-16') + $chq_base, '2026-11-19');
check('Form: For Deposit with a late-encoded date is valid and kept', !$r['errors'] && $r['value']['status_date'] === '2026-11-16', json_encode($r['errors']));
$lf = fresh_db();
$fid = pay($lf, 'S1', array('status' => 'CLEARED', 'status_date' => '2026-11-17') + $chq_base);
$fp = bp_fetch_payment($lf, $fid);
check('Form: new cleared cheque saved with the picked cleared date', $fp['cleared_date'] === '2026-11-17' && $fp['deposited_date'] === '2026-11-17', json_encode($fp));
pay($lf, 'S1', array('status' => 'CLEARED', 'status_date' => '2026-11-16') + $chq_base, '2026-11-19', $fp);
check('Form: editing the cleared date updates it', bp_fetch_payment($lf, $fid)['cleared_date'] === '2026-11-16');
check('Cash with no reference/remarks is fine', !$v($base));
check('Remarks over 200 chars rejected', isset($v(array('remarks' => str_repeat('x', 201)) + $base)['remarks']));
$e = $v(array('method' => 'CHEQUE', 'bank_name' => 'bdo', 'cheque_no' => '777', 'cheque_date' => '2026-11-10', 'status' => 'HOLD') + $base);
check('Duplicate cheque (same bank+no, any case) blocked', isset($e['cheque_no']));
$r = bp_validate_payment($link, array('remarks' => "<script>alert(1)</script> O'Neil \"q\" ñ", 'reference_no' => "AB\x01C") + $base, '2026-11-19');
check('Remarks stored as typed (escaping happens on display); control chars stripped', $r['value']['remarks'] === "<script>alert(1)</script> O'Neil \"q\" ñ");

pay($link, 'S4', array('pay_date' => '2026-11-19', 'amount' => '500', 'method' => 'CASH'));
$r = bp_validate_payment($link, array('cuscde' => 'S4', 'pay_date' => '2026-11-19', 'amount' => '500', 'method' => 'CASH'), '2026-11-19');
check('Possible duplicate payment detected', bp_possible_duplicate($link, $r['value'], 0));
$s = summary($link, 'S4');
check('Advance payment with no sales = credit 500', $s['credit'] == 500 && $s['status'] === 'Paid');

/* ---------------- preview ---------------- */
$link2 = fresh_db();
$pv = bp_preview($link2, 'S1', array('amount' => 6000, 'status' => 'RECEIVED', 'pay_date' => '2026-11-19'));
$states = array();
foreach ($pv['changes'] as $c) {
    $states[] = $c['docnum'] . ':' . $c['after'];
}
check('Preview: A paid, B partly covered', $states === array('SAL-A:paid', 'SAL-B:partial'), implode(' ', $states));
$pv = bp_preview($link2, 'S1', array('amount' => 6000, 'status' => 'HOLD', 'pay_date' => '2026-11-19'));
check('Preview: cheque on hold changes nothing', count($pv['changes']) === 0);
$pv = bp_preview($link2, 'S1', array('amount' => 12000, 'status' => 'RECEIVED', 'pay_date' => '2026-11-19'));
check('Preview: overpayment shows 2,000 credit', abs($pv['credit_after'] - 2000) < 0.01);
check('Preview wrote nothing', dr($link2, 'SAL-A')['paydate'] === null);
$pv = bp_preview($link2, 'S1', null);
$docs = array();
foreach ($pv['rows'] as $o) {
    $docs[] = $o['docnum'] . ':' . $o['state'];
}
check('No amount yet: auto = Unpaid, all 3 records oldest first, owed 10,000', $pv['filter'] === 'Unpaid' && $docs === array('SAL-A:unpaid', 'SAL-B:unpaid', 'SAL-C:unpaid') && $pv['open_count'] === 3 && $pv['open_owed'] == 10000, implode(' ', $docs));
check('Panel counts: All 3 / Unpaid 3 / Paid 0', $pv['counts'] === array('All' => 3, 'Unpaid' => 3, 'Paid' => 0), json_encode($pv['counts']));
$pv = bp_preview($link2, 'S1', array('amount' => 6000, 'status' => 'RECEIVED', 'pay_date' => '2026-11-19'));
check('With amount: A unpaid now / paid after, B partly after (1,000)', $pv['rows'][0]['state'] === 'unpaid' && $pv['rows'][0]['after'] === 'paid'
      && $pv['rows'][1]['after'] === 'partial' && abs($pv['rows'][1]['applied'] - 1000) < 0.01);
check('With amount: filter still on Unpaid (counts use the current state)', $pv['counts']['Unpaid'] === 3 && count($pv['rows']) === 3);
$pv = bp_preview($link2, 'S1', null, 0, 2, 1, 'next_p');
check('Panel pages: 2 per page, page 2 has the 3rd record', $pv['xpageno'] === 2 && $pv['maxpage'] === 2 && $pv['offset'] === 2 && count($pv['rows']) === 1 && $pv['rows'][0]['docnum'] === 'SAL-C' && $pv['totalrec'] === 3);
$pv = bp_preview($link2, 'S1', null, 0, 10, 1, 'first_p', 'Unpaid', 'O-B');
check('Panel search by Order No.', count($pv['rows']) === 1 && $pv['rows'][0]['docnum'] === 'SAL-B');
$pv = bp_preview($link2, 'S1', null, 0, 10, 1, 'first_p', 'All', 'sal-c');
check('Panel search by DocNum is case-insensitive', count($pv['rows']) === 1 && $pv['rows'][0]['docnum'] === 'SAL-C' && $pv['total_amount'] == 2000);
$pv = bp_preview($link2, 'S2', null);
check('Hand-paid record still unpaid (no customer_payment yet); zero record not under Unpaid', $pv['open_count'] === 2 && count($pv['rows']) === 2 && $pv['rows'][0]['docnum'] === 'SAL-X' && $pv['rows'][1]['docnum'] === 'SAL-Y');
$pv = bp_preview($link2, 'S2', null, 0, 10, 1, 'first_p', 'Paid');
check('Zero record is under Paid as no_amount', count($pv['rows']) === 1 && $pv['rows'][0]['docnum'] === 'SAL-Z' && $pv['rows'][0]['state'] === 'no_amount' && $pv['counts']['All'] === 3);
pay($link2, 'S1', array('pay_date' => '2026-11-01', 'amount' => '5000', 'method' => 'CASH'));
$pv = bp_preview($link2, 'S1', null, 0, 10, 1, 'first_p', 'Paid');
check('After a 5,000 payment: Paid filter shows only SAL-A', count($pv['rows']) === 1 && $pv['rows'][0]['docnum'] === 'SAL-A' && $pv['counts'] === array('All' => 3, 'Unpaid' => 2, 'Paid' => 1), json_encode($pv['counts']));
$pv = bp_preview($link2, 'S1', null);
check('After a 5,000 payment: auto filter = Unpaid with B and C, owed 5,000', $pv['filter'] === 'Unpaid' && count($pv['rows']) === 2 && $pv['open_owed'] == 5000);

/* ---------------- re-entry of an old payment lands on the old DR ---------------- */
$link4 = fresh_db();
add_shop($link4, 'S6', 'RYU Re-entry', array('SAL-016' => array('2016-03-01', 10000, '2016-03-05', 'CASH'),
                                             'SAL-500' => array('2026-11-01', 10000)));
pay($link4, 'S6', array('pay_date' => '2016-03-05', 'amount' => '10000', 'method' => 'CASH'));
$a = dr($link4, 'SAL-016');
$b = dr($link4, 'SAL-500');
check('Re-entered 2016 payment pays the 2016 sale, not the 2026 sale', $b['paydate'] === null);
check('2016 sale keeps its original hand-typed paydate', $a['paydate'] === '2016-03-05' && $a['paydetails'] === 'CASH');
$s = summary($link4, 'S6');
check('Re-entry shop: owes 10,000, Partial', $s['outstanding'] == 10000 && $s['status'] === 'Partial', json_encode($s));

/* ---------------- shop list, totals, shop picker ---------------- */
$link3 = fresh_db();
pay($link3, 'S1', array('pay_date' => '2026-10-20', 'amount' => '6000', 'method' => 'CASH'));
$list = bp_fetch_customers($link3, '', 'All', 'outstanding', 1, 'first_p');
$ids = array();
foreach ($list['rows'] as $r) {
    $ids[] = $r['cuscde'] . ':' . $r['status'] . ':' . $r['outstanding'];
}
check('Shop list: RYU shops with sales/payments, sorted by outstanding', $ids === array('S5:Unpaid:7650', 'S1:Partial:4000', 'S2:Unpaid:2500'), implode(' ', $ids));
check('Non-RYU shop (Other Shop) and RYU shop with nothing (S4) not listed', !in_array('S3', array_column($list['rows'], 'cuscde'), true)
      && !in_array('S4', array_column($list['rows'], 'cuscde'), true));
check('S1 row: 3 sales records, 10,000 (2-line sale counted once), title shown', $list['rows'][1]['dr_count'] === 3 && $list['rows'][1]['sales'] == 10000
      && $list['rows'][1]['cusdsc'] === 'RYU Tiktok', json_encode($list['rows'][1]));
// SQLite LIKE is case-sensitive for non-ASCII; MySQL utf8_general_ci also ignores case/accents
$list = bp_fetch_customers($link3, 'Ñiño', 'All', 'name', 1, 'first_p');
check('Search by shop name with special characters', count($list['rows']) === 1 && $list['rows'][0]['cuscde'] === 'S2');
check('Search cannot find a non-RYU shop', bp_fetch_customers($link3, 'Other', 'All', 'name', 1, 'first_p')['totalrec'] === 0);
$list = bp_fetch_customers($link3, '', 'Partial', 'name', 1, 'first_p');
check('Status filter Partial', count($list['rows']) === 1 && $list['rows'][0]['cuscde'] === 'S1');
$t = bp_fetch_totals($link3);
check('Totals: RYU sales only 20,150 (not Other Shop 9,999), outstanding 14,150, 3 shops, 1 partial / 2 unpaid',
      $t['sales'] == 20150 && $t['outstanding'] == 14150 && $t['shops'] === 3 && $t['paid'] === 0 && $t['partial'] === 1 && $t['unpaid'] === 2, json_encode($t));
$found = array_column(bp_search_customers($link3, ''), 'cuscde');
sort($found);
check('Shop picker: empty text lists only RYU shops', $found === array('S1', 'S2', 'S4', 'S5'), implode(' ', $found));
check('Shop picker: "tiktok" finds RYU Tiktok', array_column(bp_search_customers($link3, 'tiktok'), 'cuscde') === array('S1'));
check('Shop picker: non-RYU shop not offered', bp_search_customers($link3, 'Other') === array());

/* ---------------- History tab: payments page (filter / search / pager) ---------------- */
$mk = function ($recid, $date, $amount, $method, $status, $ref = null, $bank = null, $chq = null, $remarks = null) {
    return array('recid' => $recid, 'pay_date' => $date, 'amount' => $amount, 'method' => $method, 'status' => $status,
                 'reference_no' => $ref, 'bank_name' => $bank, 'cheque_no' => $chq, 'remarks' => $remarks);
};
$pays = array(   // newest first, as bp_buyer_payments() returns them
    $mk(7, '2026-11-20', 3000, 'CHEQUE', 'HOLD', null, 'BDO', '9001'),
    $mk(6, '2026-11-18', 1200, 'CHEQUE', 'FOR_DEPOSIT', null, 'BPI', '9004'),
    $mk(5, '2026-11-15', 2500, 'CHEQUE', 'CLEARED', null, 'Metrobank', '9003'),
    $mk(4, '2026-11-10', 1500, 'CHEQUE', 'BOUNCED', null, 'BPI', '9002', 'insufficient funds'),
    $mk(3, '2026-11-08', 2000, 'BANK', 'RECEIVED', 'DS-001'),
    $mk(2, '2026-11-05', 4000, 'GCASH', 'RECEIVED', 'GC-001'),
    $mk(1, '2026-11-01', 500, 'CASH', 'RECEIVED', null, null, null, 'paid at store'),
);
$pp = bp_payment_page($pays, 'All', '', 1, 'first_p');
check('History counts: All 7 / Collected 4 / On hand 2 / Returned 1', $pp['counts'] === array('All' => 7, 'Collected' => 4, 'On hand' => 2, 'Returned' => 1), json_encode($pp['counts']));
check('History All: newest first, total 14,700', $pp['rows'][0]['recid'] === 7 && $pp['rows'][6]['recid'] === 1 && $pp['total_amount'] == 14700);
$ids = function ($page) { return array_map(function ($p) { return $p['recid']; }, $page['rows']); };
check('History Collected = received + cleared', $ids(bp_payment_page($pays, 'Collected', '', 1, 'first_p')) === array(5, 3, 2, 1));
check('History On hand = hold + for deposit', $ids(bp_payment_page($pays, 'On hand', '', 1, 'first_p')) === array(7, 6));
$pp = bp_payment_page($pays, 'Returned', '', 1, 'first_p');
check('History Returned = bounced, total 1,500', $ids($pp) === array(4) && $pp['total_amount'] == 1500);
check('History search by cheque no.', $ids(bp_payment_page($pays, 'All', '9003', 1, 'first_p')) === array(5));
check('History search by bank is case-insensitive', $ids(bp_payment_page($pays, 'All', 'bpi', 1, 'first_p')) === array(6, 4));
check('History search by reference', $ids(bp_payment_page($pays, 'All', 'gc-0', 1, 'first_p')) === array(2));
check('History search by remarks', $ids(bp_payment_page($pays, 'All', 'store', 1, 'first_p')) === array(1));
check('History search + filter together', $ids(bp_payment_page($pays, 'Collected', 'bpi', 1, 'first_p')) === array());
$pp = bp_payment_page($pays, 'All', '', 1, 'next_p', 3);
check('History pager: 3 per page, page 2 = payments 4,3,2', $pp['xpageno'] === 2 && $pp['maxpage'] === 3 && $pp['offset'] === 3 && $ids($pp) === array(4, 3, 2));
check('History unknown filter falls back to All', bp_payment_page($pays, 'Bogus', '', 1, 'first_p')['filter'] === 'All');
check('History no payments', bp_payment_page(array(), 'All', '', 1, 'first_p')['totalrec'] === 0);

/* ---------------- shops whose amounts are all 0 are hidden ---------------- */
$link5 = fresh_db();
add_shop($link5, 'S7', 'RYU Zero Sales Only', array('SAL-ZERO' => array('2026-10-10', 0)));
add_shop($link5, 'S8', 'RYU Held Cheque Only');
add_shop($link5, 'S9', 'RYU Bounced Cheque Only');
$link5->exec("INSERT INTO customer_payment (cuscde, pay_date, amount, method, status, bank_name, cheque_no, cheque_date, created_at) VALUES
    ('S8','2026-11-10',3000,'CHEQUE','HOLD','BDO','111','2026-12-01','2026-11-10 09:00:00'),
    ('S9','2026-11-01',1200,'CHEQUE','BOUNCED','BPI','222','2026-11-02','2026-11-01 09:00:00')");
$list = bp_fetch_customers($link5, '', 'All', 'name', 1, 'first_p');
$ids = array_column($list['rows'], 'cuscde');
check('Shop with only zero-value sales is hidden', !in_array('S7', $ids, true), implode(' ', $ids));
check('Shop with no sales but a held cheque is listed', in_array('S8', $ids, true));
check('Shop with no sales but a bounced cheque is listed', in_array('S9', $ids, true));
check('Shop with no records at all is hidden', !in_array('S4', $ids, true));
$t = bp_fetch_totals($link5);
check('Totals count 5 shops (S1, S2, S5, S8, S9)', $t['shops'] === 5 && $list['totalrec'] === 5, json_encode($t));
check('Status counts still add up to the shop count', $t['paid'] + $t['partial'] + $t['unpaid'] === $t['shops'], json_encode($t));

/* ---------------- customer source: customerfile.cusdsc + tranfile1.cuscde ---------------- */
$link8 = fresh_db();
check('Customer sales come from tranfile1.cuscde (no tranfile2 table exists)', array_column(bp_customer_sales($link8, 'S1'), 'docnum') === array('SAL-A', 'SAL-B', 'SAL-C'));
check('Non-SAL record (PUR) of a RYU customer is not a sale', bp_summarize(bp_customer_sales($link8, 'S1'), array())['sales'] == 10000);
check('Customer name from customerfile.cusdsc', bp_customer_name($link8, 'S1') === 'RYU Tiktok');
check('Non-RYU customer has no name here (treated as not found)', bp_customer_name($link8, 'S3') === null && bp_customer_name($link8, 'NOPE') === null);
add_shop($link8, 'S11', 'RYU Old Mark', array('SAL-OS' => array('2026-09-01', 2000, '2026-10-01', 'Paid via shop payments')));
pay($link8, 'S11', array('pay_date' => '2026-11-10', 'amount' => '500', 'method' => 'CASH'));
check('A "Paid via shop payments" record that is not fully paid gets its paydate cleared (it is this page\'s own mark)',
      dr($link8, 'SAL-OS')['paydate'] === null && dr($link8, 'SAL-OS')['paydetails'] === '', json_encode(dr($link8, 'SAL-OS')));
pay($link8, 'S1', array('pay_date' => '2026-11-10', 'amount' => '5000', 'method' => 'CASH'));
check('Paid mark written to tranfile1.paydetails is "Paid via shop payments"', dr($link8, 'SAL-A')['paydetails'] === 'Paid via shop payments'
      && dr($link8, 'SAL-A')['paydate'] === '2026-11-10');
$row = bp_fetch_payment($link8, 2);
check('Payment saved in customer_payment with the cuscde', $row && $row['cuscde'] === 'S1' && $row['amount'] == 5000, json_encode($row));

check('Pager: 45 rows -> 3 pages, next from 1 = 2', bp_pager(45, 1, 'next_p', 20) === array('xpageno' => 2, 'maxpage' => 3, 'offset' => 20));
check('Pager: next on last page stays', bp_pager(45, 3, 'next_p', 20)['xpageno'] === 3);
check('Pager: previous on first stays', bp_pager(45, 1, 'previous_p', 20)['xpageno'] === 1);
check('Pager: last', bp_pager(45, 1, 'last_p', 20) === array('xpageno' => 3, 'maxpage' => 3, 'offset' => 40));
check('Pager: same page clamps after rows disappear', bp_pager(15, 3, 'same', 20)['xpageno'] === 1);
check('Pager: no rows', bp_pager(0, 1, 'same', 20)['maxpage'] === 0);

/* ---------------- many DRs performance ---------------- */
$many = array();
for ($i = 0; $i < 5000; $i++) {
    $many[] = array('docnum' => sprintf('D%05d', $i), 'trndte' => '2020-01-01', 'trntot' => 100, 'paydate' => null, 'paydetails' => '');
}
$t0 = microtime(true);
$res = bp_allocate($many, array(array('recid' => 1, 'amount' => 250050, 'status' => 'RECEIVED', 'pay_date' => '2026-01-01', 'cleared_date' => null)));
$ms = (microtime(true) - $t0) * 1000;
check('5,000 DRs matched in < 200ms (' . round($ms) . 'ms), 2,500 paid + 1 partial', $res['rows'][2499]['state'] === 'paid' && $res['rows'][2500]['state'] === 'partial' && $ms < 200);

/* ---------------- DR tab paging (10,000 DRs for one buyer) ---------------- */
$big = array();
for ($i = 0; $i < 10000; $i++) {
    $big[] = array('docnum' => sprintf('SAL-%06d', $i), 'ordernum' => 'ORD-' . $i, 'trndte' => '2020-01-01', 'trntot' => 100, 'paydate' => null, 'paydetails' => '');
}
$t0 = microtime(true);
$alloc = bp_allocate($big, array(array('recid' => 1, 'amount' => 975050, 'status' => 'RECEIVED', 'pay_date' => '2026-01-01', 'cleared_date' => null)));
foreach ($alloc['rows'] as $k => $r) {
    $alloc['rows'][$k]['ordernum'] = 'ORD-' . (int)substr($r['docnum'], 4);
}
$pg = bp_dr_page($alloc['rows'], 'auto', '', 1, 'first_p');
$ms = (microtime(true) - $t0) * 1000;
check('10,000 DRs: match + first page in < 400ms (' . round($ms) . 'ms)', $ms < 400);
check('auto filter picks Unpaid when there are unpaid DRs', $pg['filter'] === 'Unpaid');
check('Counts: 9,750 paid, 250 unpaid (incl. 1 partly paid)', $pg['counts'] === array('All' => 10000, 'Unpaid' => 250, 'Paid' => 9750), json_encode($pg['counts']));
check('Only 20 rows sent, 13 pages of unpaid', count($pg['rows']) === 20 && $pg['maxpage'] === 13 && $pg['totalrec'] === 250);
check('Unpaid page starts with the partly paid DR (oldest unpaid)', $pg['rows'][0]['state'] === 'partial' && $pg['rows'][0]['docnum'] === 'SAL-009750');
$pg = bp_dr_page($alloc['rows'], 'Paid', '', 1, 'last_p');
check('Paid filter, last page = 488 (9,750 / 20)', $pg['xpageno'] === 488 && count($pg['rows']) === 10);
$pg = bp_dr_page($alloc['rows'], 'All', 'ORD-1234', 1, 'search');
check('Search by Order No.', $pg['totalrec'] >= 1 && $pg['rows'][0]['ordernum'] === 'ORD-1234');
$pg = bp_dr_page($alloc['rows'], 'All', 'sal-000005', 1, 'search');
check('Search by DR No. is case-insensitive', $pg['totalrec'] === 1);
$pg = bp_dr_page(array(), 'auto', '', 1, 'first_p');
check('Buyer with no DRs: All, empty', $pg['filter'] === 'All' && $pg['totalrec'] === 0 && $pg['maxpage'] === 0);
$pg = bp_dr_page($alloc['rows'], 'All', '', 1, 'first_p');
check('Total of the filtered DRs', abs($pg['total_amount'] - 1000000) < 0.01);

/* ---------------- Add Payment panel with 2,000 sales records for one shop ---------------- */
$link6 = fresh_db();
$link6->exec("INSERT INTO customerfile VALUES ('BIG','RYU Big Shop')");
$link6->beginTransaction();
$ins = $link6->prepare("INSERT INTO tranfile1 VALUES (?, 'SAL', ?, '2025-01-01', NULL, 100, NULL, '', 'BIG')");
for ($i = 0; $i < 2000; $i++) {
    $ins->execute(array(sprintf('SAL-%05d', $i), 'ORD-' . $i));
}
$link6->commit();
$t0 = microtime(true);
$pv = bp_preview($link6, 'BIG', array('amount' => 1550, 'status' => 'RECEIVED', 'pay_date' => '2026-11-19'));
$ms = (microtime(true) - $t0) * 1000;
check('Panel, 2,000 records: only 10 rows sent, 200 pages (' . round($ms) . 'ms)', count($pv['rows']) === 10 && $pv['maxpage'] === 200 && $pv['totalrec'] === 2000 && $ms < 400);
check('Panel, 2,000 records: first page = oldest, 15 will be paid, 16th partly', $pv['rows'][0]['docnum'] === 'SAL-00000' && $pv['rows'][9]['after'] === 'paid'
      && count(array_filter($pv['changes'], function ($c) { return $c['after'] === 'paid'; })) === 15);
$pv = bp_preview($link6, 'BIG', null, 0, 10, 1, 'last_p');
check('Panel, 2,000 records: last page = 200 with the newest 10', $pv['xpageno'] === 200 && $pv['rows'][9]['docnum'] === 'SAL-01999');
$pv = bp_preview($link6, 'BIG', null, 0, 10, 1, 'first_p', 'All', 'ORD-1234');
check('Panel, 2,000 records: search finds ORD-1234', $pv['totalrec'] === 1 && $pv['rows'][0]['docnum'] === 'SAL-01234');

/* ---------------- Buyer Billing Statement (bp_billing_statement + PDF) ---------------- */
function bs_sale($doc, $date, $amt) { return array('docnum' => $doc, 'trndte' => $date, 'trntot' => $amt, 'paydate' => null, 'paydetails' => '', 'ordernum' => 'O-' . $doc); }
function bs_pay($recid, $date, $amt, $method, $status, $done = null)
{
    return array('recid' => $recid, 'pay_date' => $date, 'amount' => $amt, 'method' => $method, 'status' => $status,
                 'reference_no' => $method === 'GCASH' ? 'G' . $recid : null, 'bank_name' => $method === 'CHEQUE' ? 'BDO' : null,
                 'cheque_no' => $method === 'CHEQUE' ? '00' . $recid : null,
                 'cleared_date' => $status === 'CLEARED' ? $done : null, 'returned_date' => bp_is_returned($status) ? $done : null);
}
$bs_sales = array(bs_sale('S1', '2026-10-01', 30000), bs_sale('S2', '2026-10-20', 18000), bs_sale('S3', '2026-11-10', 12000),
                  bs_sale('S4', '2026-09-01', 65400), bs_sale('S5', '2026-12-01', 5000));
$bs_pays = array(bs_pay(1, '2026-10-10', 50000, 'CASH', 'RECEIVED'), bs_pay(2, '2026-11-02', 30000, 'GCASH', 'RECEIVED'),
                 bs_pay(3, '2026-11-15', 15000, 'CHEQUE', 'FOR_DEPOSIT'), bs_pay(4, '2026-09-28', 10000, 'CHEQUE', 'BOUNCED', '2026-10-01'),
                 bs_pay(5, '2026-11-20', 5000, 'CHEQUE', 'CLEARED', '2026-11-30'), bs_pay(6, '2026-11-30', 1000, 'CASH', 'RECEIVED'));
$bs = function ($o) use ($bs_sales, $bs_pays) { return bp_billing_statement($bs_sales, $bs_pays, $o + array('as_of' => '2026-11-24')); };

$st = $bs(array());
check('Billing: totals as of 11/24 (record and payment after as-of left out)', $st['billed'] == 125400 && $st['paid'] == 80000 && $st['due'] == 45400,
      json_encode(array($st['billed'], $st['paid'], $st['due'])));
check('Billing: cheque cleared after as-of counts as on hand', $st['on_hand'] == 20000, (string)$st['on_hand']);
check('Billing: default = unpaid + partial, oldest first', array_column($st['records'], 'docnum') === array('S1', 'S2', 'S3') && $st['mode'] === 'unpaid');
check('Billing: partial record shows paid 14,600 and balance 15,400', $st['records'][0]['state'] === 'partial'
      && $st['records'][0]['applied'] == 14600 && $st['records'][0]['balance'] == 15400 && $st['records'][0]['ordernum'] === 'O-S1');
check('Billing: records balance = amount due', $st['records_balance'] == 45400);
check('Billing: payments newest first, default limit 20, later payment left out', array_column($st['payments'], 'recid') === array(5, 3, 2, 1, 4) && $st['limit'] === 20);
check('Billing: cleared-after-as-of cheque listed as on hand', $st['payments'][0]['status'] === 'FOR_DEPOSIT');
check('Billing: listed collected = 80,000 (bounced and on hand not counted)', $st['payments_collected'] == 80000);

$st = $bs(array('mode' => 'all'));
check('Billing: All records = 4 (paid one included)', array_column($st['records'], 'docnum') === array('S4', 'S1', 'S2', 'S3') && $st['records'][0]['state'] === 'paid');
$st = $bs(array('mode' => 'all', 'from' => '2026-10-15'));
check('Billing: From filters records but not totals', array_column($st['records'], 'docnum') === array('S2', 'S3') && $st['due'] == 45400);
$st = $bs(array('from' => '2026-10-01', 'to' => '2026-11-05'));
check('Billing: From/To filters payments', array_column($st['payments'], 'recid') === array(2, 1) && $st['payments_collected'] == 80000);
check('Billing: From/To filters records', array_column($st['records'], 'docnum') === array('S1', 'S2'));
check('Billing: limit 2', array_column($bs(array('limit' => 2))['payments'], 'recid') === array(5, 3));
check('Billing: limit 0 -> 1, 500 -> 100, text -> 1', $bs(array('limit' => 0))['limit'] === 1 && $bs(array('limit' => 500))['limit'] === 100 && $bs(array('limit' => 'abc'))['limit'] === 1);
$st = $bs(array('as_of' => '2026-12-01'));
check('Billing: as of 12/01 the cheque cleared and the later sale counts', $st['paid'] == 86000 && $st['billed'] == 130400 && $st['on_hand'] == 15000);
$st = bp_billing_statement(array(bs_sale('S1', '2026-10-01', 1000)), array(bs_pay(1, '2026-10-02', 1500, 'CASH', 'RECEIVED')), array('as_of' => '2026-11-24'));
check('Billing: overpaid buyer -> due 0, credit 500, no unpaid records', $st['due'] == 0 && $st['credit'] == 500 && $st['records'] === array());
$st = bp_billing_statement(array(), array(), array('as_of' => '2026-11-24'));
check('Billing: buyer with nothing -> all zero', $st['billed'] == 0 && $st['due'] == 0 && $st['records'] === array() && $st['payments'] === array());

$link7 = fresh_db();
$found = bp_search_buyers($link7, 'juan');
check('Buyer search finds Juan Store', count($found) === 1 && $found[0]['buyer_id'] === 'JUAN');
check('Buyer search: empty text lists buyers (max 10)', count(bp_search_buyers($link7, '')) === 4);

// PDF: renders, and long lists go over several pages with "Page x of y"
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../trn_buyerbilling_pdf.php';
$many_sales = array();
for ($i = 0; $i < 150; $i++) {
    $many_sales[] = bs_sale(sprintf('SAL-%04d', $i), '2026-01-01', 100);
}
$many_pays = array();
for ($i = 1; $i <= 100; $i++) {
    $many_pays[] = bs_pay($i, '2026-02-01', 10, $i % 2 ? 'CHEQUE' : 'BANK', $i % 2 ? 'HOLD' : 'RECEIVED');
}
$o = array('as_of' => '2026-11-24', 'from' => '', 'to' => '', 'mode' => 'all', 'limit' => 100);
$buyer = array('buyer_name' => "Ana's Ñiño <Mart>", 'buyer_address' => str_repeat('Very long address ', 20), 'buyer_contactnum' => '');
$bytes = bb_render_pdf($buyer, bp_billing_statement($many_sales, $many_pays, $o), $o, 'Nov 24, 2026 10:30 AM', 'images/ryu_motor_logo.jpg');
check('PDF: renders a PDF', substr($bytes, 0, 5) === '%PDF-');
$text = '';
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $bytes, $streams);
foreach ($streams[1] as $s) {
    $u = @gzuncompress($s);
    $text .= $u === false ? $s : $u;
}
check('PDF: 150 records + 100 payments go over several pages', preg_match('/Page 1 of (\d+)/', $text, $m) === 1 && (int)$m[1] >= 4, isset($m[1]) ? $m[1] : 'no page numbers');
check('PDF: logo embedded', strpos($bytes, '/DCTDecode') !== false);
file_put_contents(sys_get_temp_dir() . '/bb_test_statement.pdf', $bytes);

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);
