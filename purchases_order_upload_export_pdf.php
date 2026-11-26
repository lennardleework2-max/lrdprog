<?php
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once('ezpdfclass/class/class.ezpdf.php');
require_once('resources/func_pdf2tab.php');

// Security check
if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body>';
    echo '<h3>Access Denied</h3><p>Please log in to export.</p>';
    echo '</body></html>';
    exit;
}

// Parse export data from hidden field
function po_upload_get_export_data() {
    if(isset($_POST['hiddenExportData']) && trim((string)$_POST['hiddenExportData']) !== ''){
        $decoded = json_decode($_POST['hiddenExportData'], true);
        if(is_array($decoded)){
            return $decoded;
        }
    }
    return null;
}

$exportData = po_upload_get_export_data();

if(!$exportData || empty($exportData['items'])){
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>No Data</title></head><body>';
    echo '<h3>No Data</h3><p>No upload data available for export. Please perform an upload first.</p>';
    echo '<p><a href="purchases_order_upload.php">Go back</a></p>';
    echo '</body></html>';
    exit;
}

// Log export activity
$username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
$username_full_name = '';
if(isset($_SESSION['recid'])){
    $select_db_session_user = 'SELECT * FROM users WHERE recid = ?';
    $stmt_session_user = $link->prepare($select_db_session_user);
    $stmt_session_user->execute(array($_SESSION['recid']));
    $rs_session_user = $stmt_session_user->fetch();
    if($rs_session_user){
        $username_full_name = $rs_session_user["full_name"];
    }
}

$xtrndte_log = date("Y-m-d H:i:s");
$xprog_module_log = 'PURCHASES ORDER UPLOAD';
$xactivity_log = 'export_pdf';
$xremarks_log = "Exported PDF from Purchases Order Upload (Doc: " . $exportData['docnum'] . ")";
PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

ob_start();

$pdf = new Cezpdf('Letter', 'portrait');
$pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
$pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
date_default_timezone_set('Asia/Manila');
$date_printed = date("F j, Y h:i:s A");

// Parse upload date
$date_uploaded_display = date('F j, Y');
if(isset($exportData['date_uploaded']) && !empty($exportData['date_uploaded'])){
    $date_uploaded_display = date('F j, Y', strtotime($exportData['date_uploaded']));
}

// Page layout
$xtop = 750;
$xleft = 25;
$line_right = 585;

// Column positions
$col_item = 25;
$col_qty = 360;
$col_price = 440;
$col_total = 520;

// Column widths for text wrapping
$item_max_width = 320;

// Helper function to wrap text for PDF
function po_pdf_wrap_text($pdf, $string, $max_wid, $fsize) {
    static $cache = [];

    $string = trim((string)$string);
    if($string === ''){
        return array('');
    }

    $cache_key = $max_wid . '|' . $fsize . '|' . $string;
    if(isset($cache[$cache_key])){
        return $cache[$cache_key];
    }

    $max_wid -= 5;
    if($pdf->getTextWidth($fsize, $string) <= $max_wid){
        return $cache[$cache_key] = array($string);
    }

    $wrapped_lines = array();
    $remaining = $string;

    while($remaining !== ''){
        if($pdf->getTextWidth($fsize, $remaining) <= $max_wid){
            $wrapped_lines[] = $remaining;
            break;
        }

        // Fit text to width
        $xarr_str = str_split($remaining);
        $xxstr = "";
        foreach($xarr_str as $value){
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
            if($xstr_wid > $max_wid){
                break;
            }
            $xxstr = $xxstr . $value;
        }
        $line = rtrim($xxstr);

        if($line === ''){
            $line = substr($remaining, 0, 1);
        }

        // Try to break at last space
        $last_space = strrpos($line, ' ');
        if($last_space !== false && $last_space > 0){
            $candidate_line = rtrim(substr($line, 0, $last_space));
            if($candidate_line !== ''){
                $line = $candidate_line;
            }
        }

        $wrapped_lines[] = rtrim($line);
        $remaining = ltrim(substr($remaining, strlen($line)));
    }

    if(empty($wrapped_lines)){
        $wrapped_lines[] = $string;
    }

    return $cache[$cache_key] = $wrapped_lines;
}

// Format number with commas
function po_pdf_format_number($num) {
    if($num === null || $num === ''){
        return '0.00';
    }
    return number_format((float)$num, 2, '.', ',');
}

// Header (repeated on all pages)
$xheader = $pdf->openObject();
$pdf->saveState();

$pdf->ezPlaceData($xleft, $xtop, "<b>Purchases Upload</b>", 15, 'left');
$xtop -= 15;
$pdf->ezPlaceData($xleft, $xtop, "<b>Report by: " . $_SESSION['userdesc'] . "</b>", 9, 'left');
$xtop -= 15;
$pdf->ezPlaceData($xleft, $xtop, 'Date Printed: ' . $date_printed, 10, 'left');
$xtop -= 15;

$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($xheader, 'all');

// First page specific content
$xheader_first_page = $pdf->openObject();
$pdf->saveState();

$pdf->ezPlaceData($xleft, $xtop, 'Date Uploaded: ' . $date_uploaded_display, 10, 'left');
$xtop -= 20;

$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($xheader_first_page, 'add');

// Purchase order header info
$pdf->setLineStyle(.5);
$pdf->line($xleft, $xtop + 5, $line_right, $xtop + 5);
$xtop -= 5;

$pdf->ezPlaceData($xleft, $xtop, "<b>Ordernum:</b>", 10, 'left');
$pdf->ezPlaceData($xleft + 80, $xtop, $exportData['ordernum'], 10, 'left');
$xtop -= 15;

$pdf->ezPlaceData($xleft, $xtop, "<b>Docnum:</b>", 10, 'left');
$pdf->ezPlaceData($xleft + 80, $xtop, $exportData['docnum'], 10, 'left');
$xtop -= 15;

$remarks_display = isset($exportData['remarks']) && trim($exportData['remarks']) !== '' ? $exportData['remarks'] : '-';
$pdf->ezPlaceData($xleft, $xtop, "<b>Remarks:</b>", 10, 'left');
$pdf->ezPlaceData($xleft + 80, $xtop, $remarks_display, 10, 'left');
$xtop -= 15;

$pdf->ezPlaceData($xleft, $xtop, "<b>Supplier:</b>", 10, 'left');
$pdf->ezPlaceData($xleft + 80, $xtop, $exportData['suppdsc'], 10, 'left');
$xtop -= 20;

// Table header
$pdf->setLineStyle(.5);
$pdf->line($xleft, $xtop + 10, $line_right, $xtop + 10);
$pdf->line($xleft, $xtop - 3, $line_right, $xtop - 3);

$pdf->ezPlaceData($col_item, $xtop, "<b>Item</b>", 10, 'left');
$pdf->ezPlaceData($col_qty, $xtop, "<b>Qty</b>", 10, 'right');
$pdf->ezPlaceData($col_price, $xtop, "<b>Price</b>", 10, 'right');
$pdf->ezPlaceData($col_total, $xtop, "<b>Total</b>", 10, 'right');

$xtop -= 15;

// Items
$items = $exportData['items'];

foreach($items as $item){
    $itmdsc = isset($item['itmdsc']) ? $item['itmdsc'] : '';
    $itmqty = isset($item['itmqty']) ? floatval($item['itmqty']) : 0;
    $untprc = isset($item['untprc']) ? floatval($item['untprc']) : 0;
    $extprc = isset($item['extprc']) ? floatval($item['extprc']) : ($itmqty * $untprc);

    // Wrap long item names
    $item_lines = po_pdf_wrap_text($pdf, $itmdsc, $item_max_width, 9);
    $line_count = max(count($item_lines), 1);
    $row_height = 15 + (($line_count - 1) * 10);

    // Check if we need a new page
    if(($xtop - $row_height) <= 60){
        $pdf->ezNewPage();
        $xtop = 700;

        // Repeat table header on new page
        $pdf->setLineStyle(.5);
        $pdf->line($xleft, $xtop + 10, $line_right, $xtop + 10);
        $pdf->line($xleft, $xtop - 3, $line_right, $xtop - 3);

        $pdf->ezPlaceData($col_item, $xtop, "<b>Item</b>", 10, 'left');
        $pdf->ezPlaceData($col_qty, $xtop, "<b>Qty</b>", 10, 'right');
        $pdf->ezPlaceData($col_price, $xtop, "<b>Price</b>", 10, 'right');
        $pdf->ezPlaceData($col_total, $xtop, "<b>Total</b>", 10, 'right');

        $xtop -= 15;
    }

    $row_y = $xtop;

    // Print wrapped item lines
    foreach($item_lines as $line_index => $line_text){
        if($line_text !== ''){
            $pdf->ezPlaceData($col_item, $row_y - ($line_index * 10), $line_text, 9, 'left');
        }
    }

    // Print qty, price, total on first line
    $pdf->ezPlaceData($col_qty, $row_y, po_pdf_format_number($itmqty), 9, 'right');
    $pdf->ezPlaceData($col_price, $row_y, po_pdf_format_number($untprc), 9, 'right');
    $pdf->ezPlaceData($col_total, $row_y, po_pdf_format_number($extprc), 9, 'right');

    $xtop -= $row_height;
}

// Order total line
if(($xtop - 30) <= 60){
    $pdf->ezNewPage();
    $xtop = 700;
}

$pdf->setLineStyle(.5);
$pdf->line($xleft, $xtop - 5, $line_right, $xtop - 5);
$xtop -= 20;

$pdf->ezPlaceData($col_price, $xtop, "<b>Total:</b>", 10, 'right');
$pdf->ezPlaceData($col_total, $xtop, "<b>" . po_pdf_format_number($exportData['trntot']) . "</b>", 10, 'right');

$pdf->addText(30, 15, 8, "Date Printed : " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
$pdf->ezStream();
ob_end_flush();
?>
