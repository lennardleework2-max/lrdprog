<?php
/**
 * Standalone CLI test: the paid upload skips records that already have a paydate.
 * No database connection, no $_POST, no real file upload.
 * Run: php tests/test_sales_filepaidupload_skip_paid.php
 *
 * The helper functions are extracted from sales_filepaidupload_ajax.php and eval'd, so the
 * real code is tested (the ajax file itself can't be included - it runs the upload on load).
 */

$source = file_get_contents(__DIR__ . '/../sales_filepaidupload_ajax.php');
foreach (array('paid_upload_is_already_paid', 'paid_upload_already_paid_message') as $fn) {
    if (!preg_match('/^function ' . $fn . '\(.*?^\}/ms', $source, $m)) {
        fwrite(STDERR, "Could not find function $fn in sales_filepaidupload_ajax.php\n");
        exit(1);
    }
    eval($m[0]);
}

$failures = 0;
function check($label, $actual, $expected)
{
    global $failures;
    if ($actual === $expected) {
        echo "PASS  $label\n";
    } else {
        $failures++;
        echo "FAIL  $label\n      expected: " . var_export($expected, true) . "\n      actual:   " . var_export($actual, true) . "\n";
    }
}

// --- paid_upload_is_already_paid ---
check('NULL paydate is unpaid', paid_upload_is_already_paid(array('paydate' => null)), false);
check('empty paydate is unpaid', paid_upload_is_already_paid(array('paydate' => '')), false);
check('zero date is unpaid', paid_upload_is_already_paid(array('paydate' => '0000-00-00')), false);
check('zero datetime is unpaid', paid_upload_is_already_paid(array('paydate' => '0000-00-00 00:00:00')), false);
check('paid via payments is paid', paid_upload_is_already_paid(array('paydate' => '2026-10-01', 'paydetails' => 'Paid via payments')), true);
check('paid via upload is paid', paid_upload_is_already_paid(array('paydate' => '2026-09-15', 'paydetails' => 'Paid via file upload')), true);
check('manual pay date is paid', paid_upload_is_already_paid(array('paydate' => '2026-08-01 00:00:00', 'paydetails' => 'manual')), true);

// --- matching loop, mirroring the tiktok/lazada and shopee branches ---
function process_rows($orderNumbers, $lookup)
{
    $response = array('noMatchFile2' => array());
    $recids_to_pay = array();
    $ordernums_to_sync = array();

    foreach ($orderNumbers as $orderNumber) {
        $rs_checker = isset($lookup[$orderNumber]) ? $lookup[$orderNumber] : null;
        if (empty($rs_checker)) {
            $response["noMatchFile2"][$orderNumber] = array('success' => false, 'ordernum' => $orderNumber,
                'message' => "Sales Order Number: <b>" . $orderNumber . "</b> doesn't exist");
            continue;
        }
        if (paid_upload_is_already_paid($rs_checker)) {
            $response["noMatchFile2"][$orderNumber] = array('success' => false, 'ordernum' => $orderNumber,
                'message' => paid_upload_already_paid_message($orderNumber, $rs_checker));
            continue;
        }
        $recids_to_pay[$rs_checker['recid']] = $rs_checker['recid'];
        if (!empty($rs_checker['file_batchno']) && !empty($rs_checker['platform_upload'])) {
            $ordernums_to_sync[$orderNumber] = $orderNumber;
        }
        $response["noMatchFile2"][$orderNumber] = array('success' => true, 'ordernum' => $orderNumber);
    }
    return array($response, $recids_to_pay, $ordernums_to_sync);
}

$fakeTranfile1 = array(
    'ORD-UNPAID'  => array('recid' => 1, 'ordernum' => 'ORD-UNPAID', 'paydate' => null, 'paydetails' => '', 'file_batchno' => 'b1', 'platform_upload' => 'shopee'),
    'ORD-PARTIAL' => array('recid' => 2, 'ordernum' => 'ORD-PARTIAL', 'paydate' => '', 'paydetails' => '', 'file_batchno' => '', 'platform_upload' => ''),
    'ORD-PAYMENTS'=> array('recid' => 3, 'ordernum' => 'ORD-PAYMENTS', 'paydate' => '2026-10-01', 'paydetails' => 'Paid via payments', 'file_batchno' => 'b1', 'platform_upload' => 'shopee'),
    'ORD-UPLOADED'=> array('recid' => 4, 'ordernum' => 'ORD-UPLOADED', 'paydate' => '2026-09-15 00:00:00', 'paydetails' => 'Paid via file upload', 'file_batchno' => 'b1', 'platform_upload' => 'shopee'),
);

list($response, $recids, $sync) = process_rows(array('ORD-UNPAID', 'ORD-PARTIAL', 'ORD-PAYMENTS', 'ORD-UPLOADED', 'ORD-MISSING'), $fakeTranfile1);

check('only unpaid records are written', array_values($recids), array(1, 2));
check('upld_salesfile sync excludes paid records', array_values($sync), array('ORD-UNPAID'));
check('unpaid row reported as success', $response['noMatchFile2']['ORD-UNPAID']['success'], true);
check('payments-paid row reported as skipped', $response['noMatchFile2']['ORD-PAYMENTS']['success'], false);
check('skip message shows existing paid date',
    $response['noMatchFile2']['ORD-PAYMENTS']['message'],
    'Order Number: <b>ORD-PAYMENTS</b> is already paid (2026-10-01) - skipped');
check('re-upload message trims datetime',
    $response['noMatchFile2']['ORD-UPLOADED']['message'],
    'Order Number: <b>ORD-UPLOADED</b> is already paid (2026-09-15) - skipped');
check('unknown order still reported as missing', $response['noMatchFile2']['ORD-MISSING']['success'], false);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
