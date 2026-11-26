<?php
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once('ezpdfclass/class/class.ezpdf.php');
require_once('resources/func_pdf2tab.php');

function sales_del_upload_pdf_order_results($records) {
    if (!is_array($records)) {
        return array();
    }

    $records = array_values($records);
    $failed = array();
    $successful = array();

    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $ordernum = isset($record['ordernum']) ? trim((string)$record['ordernum']) : '';
        if ($ordernum === '') {
            continue;
        }

        $is_success = isset($record['status']) && $record['status'] === 'SUCCESS';
        $normalized_record = array(
            'ordernum' => $ordernum,
            'matched_salnum' => isset($record['matched_salnum']) ? trim((string)$record['matched_salnum']) : '',
            'status' => $is_success ? 'Success' : 'Failed - No Match'
        );

        if ($is_success) {
            $successful[] = $normalized_record;
        } else {
            $failed[] = $normalized_record;
        }
    }

    // Return failed first, then successful
    return array_merge($failed, $successful);
}

function sales_del_upload_pdf_get_results() {
    if (isset($_POST['hiddenDeleteUploadData']) && trim((string)$_POST['hiddenDeleteUploadData']) !== '') {
        $decoded = json_decode($_POST['hiddenDeleteUploadData'], true);
        if (is_array($decoded)) {
            return sales_del_upload_pdf_order_results($decoded);
        }
    }

    return array();
}

// Log export activity
$username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
$username_full_name = '';
if (isset($_SESSION['recid'])) {
    $select_db_session_user = 'SELECT * FROM users WHERE recid = ?';
    $stmt_session_user = $link->prepare($select_db_session_user);
    $stmt_session_user->execute(array($_SESSION['recid']));
    $rs_session_user = $stmt_session_user->fetch();
    if ($rs_session_user) {
        $username_full_name = $rs_session_user["full_name"];
    }
}

$xtrndte_log = date("Y-m-d H:i:s");
$xprog_module_log = 'SALES DELETE UPLOAD';
$xactivity_log = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') ? 'export_xls' : 'export_pdf';
$xremarks_log = "Exported " . ((isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') ? 'XLS' : 'PDF') . " from Sales Delete Upload";
PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

ob_start();

$xreport_title = "Delete Upload Result Summary";

if (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') {
    $pdf = new tab_ezpdf('Letter', 'landscape');
} else {
    $pdf = new Cezpdf('Letter', 'landscape');
}

$pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
$pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
date_default_timezone_set('Asia/Manila');
$date_printed = date("F j, Y h:i:s A");

$xtop = 580;
$xleft = 25;

$fields_count = 0;
$fields = '';
$xheader_check = false;

$progname_hidden = '';
$xheader = $pdf->openObject();
$pdf->saveState();

if (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') {
    $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left');
} else {
    $pdf->ezPlaceData($xleft, $xtop, "<b>Delete Upload Result Summary</b>", 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, "<b>Report by: " . $_SESSION['userdesc'] . "</b>", 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : ' . $date_printed, 10, 'left');
    $xtop -= 20;
}

$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($xheader, 'all');

// Calculate summary counts BEFORE rendering headers
$decodedData = sales_del_upload_pdf_get_results();
$failed_count = 0;
foreach ($decodedData as $value) {
    if (isset($value['status']) && $value['status'] !== 'Success') {
        $failed_count++;
    }
}
$success_count = count($decodedData) - $failed_count;
$total_count = count($decodedData);

$xheader_first_page = $pdf->openObject();
$pdf->saveState();
$date_uploaded_format = date('m/d/Y');

if (!isset($_POST['txt_output_type']) || $_POST['txt_output_type'] != 'tab') {
    $pdf->ezPlaceData($xleft, $xtop, "<b>FILTER:</b>", 10, 'left');
    $xtop -= 15;

    $pdf->ezPlaceData($xleft, $xtop, "<b>Date Processed:</b>", 10, 'left');
    $pdf->ezPlaceData($xleft + 85, $xtop, $date_uploaded_format, 10, 'left');
    $xtop -= 20;

    // Summary above table header (PDF)
    $pdf->ezPlaceData($xleft, $xtop, "Failed: " . $failed_count . "    Success: " . $success_count . "    Total: " . $total_count, 9, 'left');
    $xtop -= 20;
} else {
    echo "Delete Upload Result Summary\t\n";
    echo "Report by: " . $_SESSION['userdesc'] . "\t\n";
    echo "Date Printed : " . $date_printed . "\t\n";
    echo "\n";

    echo "FILTER:\n";
    echo "Date Processed: " . $date_uploaded_format . "\t\n";
    echo "\n";

    // Summary above table header (XLS)
    echo "Failed: " . $failed_count . "  Success: " . $success_count . "  Total: " . $total_count . "\t\n";
    echo "\n";

    $tab_headers = "Order Number\tMatched SAL#\tStatus\t\n";
    echo $tab_headers;
}

$xleft = 25;
$pdf->setLineStyle(.5);
$pdf->line($xleft, $xtop + 10, 770, $xtop + 10);
$pdf->line($xleft, $xtop - 3, 770, $xtop - 3);

$xfields_heaeder_counter = 0;

if (!isset($_POST['txt_output_type']) || $_POST['txt_output_type'] != 'tab') {
    $pdf->ezPlaceData($xleft, $xtop, "<b>Order Number</b>", 10, 'left');
    $pdf->ezPlaceData($xleft + 200, $xtop, "<b>Matched SAL#</b>", 10, 'left');
    $pdf->ezPlaceData($xleft + 400, $xtop, "<b>Status</b>", 10, 'left');
}

$xtop -= 15;

$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($xheader_first_page, 'add');

if (empty($decodedData)) {
    if (!isset($_POST['txt_output_type']) || $_POST['txt_output_type'] != 'tab') {
        $pdf->ezPlaceData(25, $xtop, 'No delete upload results available.', 9, 'left');
    } else {
        echo "No delete upload results available.\t\n";
    }
} else {
    foreach ($decodedData as $value) {
        $xleft = 25;
        $ordernum = isset($value['ordernum']) ? $value['ordernum'] : '';
        $matched_salnum = isset($value['matched_salnum']) && trim((string)$value['matched_salnum']) !== '' ? $value['matched_salnum'] : 'N/A';
        $status = isset($value['status']) ? $value['status'] : 'Failed - No Match';

        if (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') {
            echo $ordernum . "\t" . $matched_salnum . "\t" . $status . "\t\n";
        } else {
            $pdf->ezPlaceData($xleft, $xtop, $ordernum, 9, "left");
            $pdf->ezPlaceData($xleft + 200, $xtop, $matched_salnum, 9, "left");
            $pdf->ezPlaceData($xleft + 400, $xtop, $status, 9, "left");
        }

        $xtop -= 15;

        if ($xtop <= 60) {
            $pdf->ezNewPage();
            $xtop = 505;

            $xfields_heaeder_counter = 0;

            if ((!isset($_POST['txt_output_type']) || $_POST['txt_output_type'] != 'tab') && $xheader_check == false) {
                $xheader = $pdf->openObject();
                $pdf->saveState();

                $xleft = 25;
                $pdf->setLineStyle(.5);
                $pdf->line($xleft, $xtop + 10 + 20, 770, $xtop + 10 + 20);
                $pdf->line($xleft, $xtop - 14 + 30, 770, $xtop - 14 + 30);

                $pdf->ezPlaceData($xleft, $xtop + 20, "<b>Order Number</b>", 10, 'left');
                $pdf->ezPlaceData($xleft + 200, $xtop + 20, "<b>Matched SAL#</b>", 10, 'left');
                $pdf->ezPlaceData($xleft + 400, $xtop + 20, "<b>Status</b>", 10, 'left');

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader, 'all');

                $xheader_check = true;
            }
        }
    }
}

$pdf->line(25, $xtop - 10, 770, $xtop - 10);
$pdf->addText(30, 15, 8, "Date Printed : " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
$pdf->ezStream();
ob_end_flush();
?>