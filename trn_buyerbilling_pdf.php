<?php
// Buyer Billing Statement PDF (posted from trn_buyerbilling.php). Letter portrait, RYU logo.
require_once('ezpdfclass_new/class/class.ezpdf.php');

define('BB_L', 30);
define('BB_R', 582);
define('BB_TOP', 760);
define('BB_BOTTOM', 50);

// ezpdf: Latin-1 text, '<' starts a tag, one line only; cut to $width points when given
function bb_text($pdf, $value, $size = 9, $width = 0)
{
    $s = trim(preg_replace('/\s+/', ' ', str_replace('<', '(', (string)$value)));
    $conv = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    $s = $conv === false ? $s : $conv;
    if ($width > 0 && $pdf->getTextWidth($size, $s) > $width) {
        while ($s !== '' && $pdf->getTextWidth($size, $s . '...') > $width) {
            $s = substr($s, 0, -1);
        }
        $s .= '...';
    }
    return $s;
}

function bb_money($v) { return number_format((float)$v, 2); }
function bb_mdy($iso) { return $iso ? substr($iso, 5, 2) . '/' . substr($iso, 8, 2) . '/' . substr($iso, 0, 4) : '-'; }
function bb_long($iso) { return date('M d, Y', strtotime($iso)); }

// table header bar; $cols = [label, x, align, width]
function bb_table_head($pdf, $y, $cols)
{
    $pdf->setColor(0.2, 0.2, 0.2);
    $pdf->filledRectangle(BB_L, $y - 4, BB_R - BB_L, 16);
    $pdf->setColor(1, 1, 1);
    foreach ($cols as $c) {
        $pdf->ezPlaceData($c[1], $y, '<b>' . $c[0] . '</b>', 8, $c[2]);
    }
    $pdf->setColor(0, 0, 0);
}

// draws rows, starting a new page (with the header bar again) when the page is full; returns the last y
function bb_table($pdf, $y, $cols, $rows)
{
    bb_table_head($pdf, $y, $cols);
    $pdf->setStrokeColor(0.75, 0.75, 0.75);
    foreach ($rows as $r) {
        $y -= 16;
        if ($y < BB_BOTTOM) {
            $pdf->ezNewPage();
            $y = BB_TOP;
            bb_table_head($pdf, $y, $cols);
            $y -= 16;
        }
        foreach ($cols as $i => $c) {
            $pdf->ezPlaceData($c[1], $y, bb_text($pdf, $r[$i], 9, $c[3]), 9, $c[2]);
        }
        $pdf->line(BB_L, $y - 5, BB_R, $y - 5);
    }
    return $y;
}

// make room for a block of $need points, else go to a new page
function bb_room($pdf, $y, $need)
{
    if ($y - $need < BB_BOTTOM) {
        $pdf->ezNewPage();
        return BB_TOP;
    }
    return $y;
}

/**
 * Draws the whole statement. $buyer: buyer_name, buyer_address, buyer_contactnum.
 * $st: bp_billing_statement() result. $opts: as_of, from, to. Returns the PDF bytes.
 */
function bb_render_pdf($buyer, $st, $opts, $printed, $logo)
{
    $pdf = new Cezpdf('Letter', 'portrait');
    $pdf->selectFont('ezpdfclass_new/fonts/Helvetica.afm');
    $pdf->ezStartPageNumbers(BB_R, 20, 8, 'left', 'Page {PAGENUM} of {TOTALPAGENUM}', 1);
    $L = BB_L; $R = BB_R;
    $red = array(0.8, 0.05, 0.1);

    // printed date on every page
    $foot = $pdf->openObject();
    $pdf->saveState();
    $pdf->setColor(0.4, 0.4, 0.4);
    $pdf->addText($L, 20, 8, 'Printed: ' . $printed);
    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($foot, 'all');

    // header band
    $pdf->setColor($red[0], $red[1], $red[2]);
    $pdf->filledRectangle($L, 700, $R - $L, 70);
    if (is_file($logo)) {
        $pdf->addJpegFromFile($logo, $L, 700, 70, 70);
    }
    $pdf->setColor(1, 1, 1);
    $pdf->ezPlaceData(112, 742, '<b>RYU MOTORONE PH</b>', 18, 'left');
    $pdf->ezPlaceData(112, 722, 'BILLING STATEMENT', 12, 'left');
    $pdf->ezPlaceData($R - 10, 742, '<b>As of: ' . bb_long($opts['as_of']) . '</b>', 11, 'right');
    if ($opts['from'] !== '' || $opts['to'] !== '') {
        $period = ($opts['from'] !== '' ? bb_long($opts['from']) : 'Start') . ' - ' . bb_long($opts['to'] !== '' ? $opts['to'] : $opts['as_of']);
        $pdf->ezPlaceData($R - 10, 722, 'Period: ' . $period, 9, 'right');
    }

    // bill to
    $pdf->setColor(0, 0, 0);
    $y = 675;
    $pdf->ezPlaceData($L, $y, '<b>Bill to:</b>', 10, 'left');
    $pdf->ezPlaceData($L + 60, $y, bb_text($pdf, $buyer['buyer_name'], 10, 280), 10, 'left');
    $pdf->ezPlaceData(380, $y, '<b>Contact:</b>', 10, 'left');
    $pdf->ezPlaceData(430, $y, bb_text($pdf, $buyer['buyer_contactnum'] !== '' ? $buyer['buyer_contactnum'] : '-', 10, 150), 10, 'left');
    $pdf->ezPlaceData($L, $y - 15, '<b>Address:</b>', 10, 'left');
    $pdf->ezPlaceData($L + 60, $y - 15, bb_text($pdf, $buyer['buyer_address'] !== '' ? $buyer['buyer_address'] : '-', 10, $R - $L - 60), 10, 'left');

    // summary boxes
    $y = 600;
    $boxes = array(
        array('TOTAL BILLED', $st['billed'], array(0, 0, 0)),
        array('TOTAL PAID', $st['paid'], array(0.08, 0.5, 0.24)),
        array('CHEQUES ON HAND', $st['on_hand'], array(0.7, 0.33, 0.03)),
        array('AMOUNT DUE', $st['due'], $red),
    );
    $w = ($R - $L - 30) / 4;
    foreach ($boxes as $i => $b) {
        $x = $L + $i * ($w + 10);
        $pdf->setColor(0.95, 0.95, 0.95);
        $pdf->filledRectangle($x, $y, $w, 45);
        $pdf->setColor(0.4, 0.4, 0.4);
        $pdf->ezPlaceData($x + 8, $y + 31, $b[0], 8, 'left');
        $pdf->setColor($b[2][0], $b[2][1], $b[2][2]);
        $pdf->ezPlaceData($x + 8, $y + 10, '<b>P ' . bb_money($b[1]) . '</b>', 14, 'left');
    }
    $pdf->setColor(0, 0, 0);
    $y = 585;
    if ($st['credit'] > 0) {
        $pdf->ezPlaceData($R, $y, 'Advance payment (credit): P ' . bb_money($st['credit']), 8, 'right');
    }

    // records
    $y -= 20;
    $pdf->ezPlaceData($L, $y, '<b>RECORDS</b> (' . ($st['mode'] === 'all' ? 'All' : 'Unpaid + Partial') . ')', 11, 'left');
    $cols = array(array('Date', $L + 4, 'left', 60), array('Record No.', 95, 'left', 88), array('Order No.', 185, 'left', 100),
                  array('Amount', 340, 'right', 0), array('Paid', 420, 'right', 0), array('Balance', 505, 'right', 0), array('Status', 515, 'left', 65));
    $labels = array('paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid', 'no_amount' => 'No amount');
    $rows = array();
    foreach ($st['records'] as $r) {
        $rows[] = array(bb_mdy($r['trndte']), $r['docnum'], $r['ordernum'] !== '' ? $r['ordernum'] : '-',
                        bb_money($r['trntot']), bb_money($r['applied']), bb_money($r['balance']), $labels[$r['state']]);
    }
    if ($rows) {
        $y = bb_table($pdf, $y - 20, $cols, $rows);
        $y = bb_room($pdf, $y, 25);
        $pdf->ezPlaceData(430, $y - 20, '<b>Total balance:</b>', 9, 'right');
        $pdf->ezPlaceData(505, $y - 20, '<b>' . bb_money($st['records_balance']) . '</b>', 9, 'right');
    } else {
        $pdf->ezPlaceData($L, $y - 20, $st['mode'] === 'all' ? 'No records.' : 'No unpaid records.', 9, 'left');
    }

    // payments
    $y = bb_room($pdf, $y - 55, 60);
    $pdf->ezPlaceData($L, $y, '<b>PAST PAYMENTS</b> (last ' . $st['limit'] . ')', 11, 'left');
    $cols = array(array('Date', $L + 4, 'left', 60), array('Method', 95, 'left', 65), array('Ref / Cheque No.', 165, 'left', 165),
                  array('Amount', 400, 'right', 0), array('Status', 430, 'left', 150));
    $methods = bp_methods();
    $statuses = array('RECEIVED' => 'Received', 'CLEARED' => 'Cleared', 'HOLD' => 'Hold (on hand)', 'FOR_DEPOSIT' => 'For Deposit (on hand)',
                      'BOUNCED' => 'Bounced (not counted)', 'CLOSED_ACCOUNT' => 'Closed Account (not counted)');
    $rows = array();
    foreach ($st['payments'] as $p) {
        $ref = $p['method'] === 'CHEQUE' ? trim($p['bank_name'] . ' #' . $p['cheque_no']) : (string)$p['reference_no'];
        $rows[] = array(bb_mdy(substr($p['pay_date'], 0, 10)), $p['method'] === 'BANK' ? 'Bank' : $methods[$p['method']],
                        $ref !== '' ? $ref : '-', bb_money($p['amount']), $statuses[$p['status']]);
    }
    if ($rows) {
        $y = bb_table($pdf, $y - 20, $cols, $rows);
        $y = bb_room($pdf, $y, 25);
        $pdf->ezPlaceData(320, $y - 20, '<b>Total collected:</b>', 9, 'right');
        $pdf->ezPlaceData(400, $y - 20, '<b>' . bb_money($st['payments_collected']) . '</b>', 9, 'right');
    } else {
        $pdf->ezPlaceData($L, $y - 20, 'No payments.', 9, 'left');
    }

    // note
    $y = bb_room($pdf, $y - 60, 20);
    $pdf->setColor(0.4, 0.4, 0.4);
    $pdf->ezPlaceData($L, $y, 'Note: Cheques on hand are not counted as paid until cleared. Bounced cheques do not reduce the amount due.', 8, 'left');
    $pdf->ezPlaceData($L, $y - 12, 'Please settle the amount due at your earliest convenience. Thank you!', 8, 'left');

    return $pdf->ezOutput();
}

if (basename($_SERVER['SCRIPT_FILENAME']) !== basename(__FILE__)) {
    return;     // included by a test: functions only
}

/* ---------------------------------------------------------------- request */
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("trn_buyerpayments_lib.php");
date_default_timezone_set('Asia/Manila');

function bb_stop($msg)
{
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}

if (!isset($_SESSION['userdesc']) || !isset($_SESSION['usercode'])) {
    bb_stop('Your session has expired. Please log in again.');
}
$perm = bp_page_permissions($link, 'trn_buyerbilling.php');
if ($perm['view'] !== 1) {
    bb_stop('You do not have access to Buyer Billing Statement.');
}

$in = function ($k) { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; };
$opts = array('as_of' => $in('as_of'), 'from' => $in('from'), 'to' => $in('to'), 'mode' => $in('mode'), 'limit' => $in('limit'));
if (!bp_valid_date($opts['as_of'])) {
    bb_stop('Enter a valid As of date.');
}
foreach (array('from', 'to') as $k) {
    if ($opts[$k] !== '' && !bp_valid_date($opts[$k])) {
        bb_stop('Enter a valid ' . ucfirst($k) . ' date.');
    }
}
if ($opts['from'] !== '' && $opts['to'] !== '' && $opts['from'] > $opts['to']) {
    bb_stop('From date must be on or before the To date.');
}

$buyer_id = $in('buyer_id');
$buyer = bp_buyer_info($link, $buyer_id);
if ($buyer === null) {
    bb_stop('Buyer not found.');
}
$buyer['buyer_address'] = trim((string)$buyer['buyer_address']);
$buyer['buyer_contactnum'] = trim((string)$buyer['buyer_contactnum']);

$st = bp_billing_statement(bp_buyer_sales($link, $buyer_id), bp_buyer_payments($link, $buyer_id), $opts);
$bytes = bb_render_pdf($buyer, $st, $opts, date('M d, Y h:i A'), 'images/ryu_motor_logo.jpg');

$file = 'billing_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $buyer_id) . '_' . str_replace('-', '', $opts['as_of']) . '.pdf';
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . strlen($bytes));
echo $bytes;
