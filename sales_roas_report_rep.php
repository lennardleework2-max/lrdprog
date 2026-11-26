<?php
// Sales ROAS Report (PDF / XLS export). The rows themselves (Shop + auto Online Sales, already
// computed by sales_roas_report_ajax.php) are posted back in unchanged from the report page's
// form, along with whatever the user typed into Ads Credit / Valid Order Sales / Ads Spend -
// this file only formats and totals what it is given, it does not query the database.

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

function sales_roas_report_to_float($xvalue)
{
    if ($xvalue === null || $xvalue === '') {
        return 0.0;
    }
    return (float)$xvalue;
}

// ROAS = Valid Order Sales / Ads Spend. No spend -> no ratio (null, printed as "-").
function sales_roas_report_compute_roas($xvalid, $xspend)
{
    $xspend = (float)$xspend;
    if ($xspend <= 0) {
        return null;
    }
    return round(((float)$xvalid) / $xspend, 1);
}

function sales_roas_report_fmt_roas($xroas)
{
    return $xroas === null ? '-' : number_format($xroas, 1);
}

// builds row records from the parallel POST arrays (one entry per shop, same index across arrays)
function sales_roas_report_build_rows($shop_titles, $online_sales, $ads_credit, $valid_order_sales, $ads_spend)
{
    $rows = array();
    $xcount = count($shop_titles);

    for ($i = 0; $i < $xcount; $i++) {
        $xshop = isset($shop_titles[$i]) ? trim((string)$shop_titles[$i]) : '';
        if ($xshop === '') {
            continue;
        }

        $xonline = sales_roas_report_to_float(isset($online_sales[$i]) ? $online_sales[$i] : 0);
        $xcredit = sales_roas_report_to_float(isset($ads_credit[$i]) ? $ads_credit[$i] : 0);
        $xvalid = sales_roas_report_to_float(isset($valid_order_sales[$i]) ? $valid_order_sales[$i] : 0);
        $xspend = sales_roas_report_to_float(isset($ads_spend[$i]) ? $ads_spend[$i] : 0);

        $rows[] = array(
            'shop' => $xshop,
            'online' => round($xonline, 2),
            'credit' => round($xcredit, 2),
            'valid' => round($xvalid, 2),
            'spend' => round($xspend, 2),
            'roas' => sales_roas_report_compute_roas($xvalid, $xspend),
        );
    }

    return $rows;
}

function sales_roas_report_totals($rows)
{
    $totals = array('online' => 0, 'credit' => 0, 'valid' => 0, 'spend' => 0);

    foreach ($rows as $row) {
        $totals['online'] += $row['online'];
        $totals['credit'] += $row['credit'];
        $totals['valid'] += $row['valid'];
        $totals['spend'] += $row['spend'];
    }

    $totals['online'] = round($totals['online'], 2);
    $totals['credit'] = round($totals['credit'], 2);
    $totals['valid'] = round($totals['valid'], 2);
    $totals['spend'] = round($totals['spend'], 2);
    $totals['roas'] = sales_roas_report_compute_roas($totals['valid'], $totals['spend']);

    return $totals;
}

function sales_roas_report_draw_columns($pdf, $xtop)
{
    $pdf->line(25, $xtop, 770, $xtop);
    $header_y = $xtop - 9;
    $pdf->ezPlaceData(25, $header_y, "<b>Shop</b>", 9, 'left');
    $pdf->ezPlaceData(260, $header_y, "<b>Online Sales</b>", 9, 'right');
    $pdf->ezPlaceData(400, $header_y, "<b>Ads Credit</b>", 9, 'right');
    $pdf->ezPlaceData(560, $header_y, "<b>Valid Order Sales</b>", 9, 'right');
    $pdf->ezPlaceData(680, $header_y, "<b>Ads Spend</b>", 9, 'right');
    $pdf->ezPlaceData(770, $header_y, "<b>ROAS</b>", 9, 'right');
    $pdf->line(25, $xtop - 12, 770, $xtop - 12);

    return $xtop - 23;
}

function sales_roas_report_build_pdf($pdf, $rows, $totals, $report_user, $date_printed, $date_label)
{
    $pdf->selectFont(__DIR__."/ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);

    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData(25, 580, "<b>Sales ROAS Report</b>", 15, 'left');
    $pdf->ezPlaceData(25, 565, "<b>Pdf Report by: ".$report_user."</b>", 9, 'left');
    $pdf->ezPlaceData(25, 550, $date_label, 9, 'left');
    $pdf->ezPlaceData(25, 535, 'Date Printed : '.$date_printed, 10, 'left');
    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    if (empty($rows)) {
        $pdf->ezPlaceData(25, 505, 'No records found.', 9, 'left');
        return;
    }

    $xtop = sales_roas_report_draw_columns($pdf, 520);

    foreach ($rows as $row) {
        if (($xtop - 15) <= 60) {
            $pdf->ezNewPage();
            $xtop = sales_roas_report_draw_columns($pdf, 530);
        }

        // ezPlaceData interprets <b>-style markup - strip angle brackets from shop titles
        $safe_shop = str_replace(array('<', '>'), '', $row['shop']);

        $pdf->ezPlaceData(25, $xtop, $safe_shop, 9, 'left');
        $pdf->ezPlaceData(260, $xtop, number_format($row['online'], 2), 9, 'right');
        $pdf->ezPlaceData(400, $xtop, number_format($row['credit'], 2), 9, 'right');
        $pdf->ezPlaceData(560, $xtop, number_format($row['valid'], 2), 9, 'right');
        $pdf->ezPlaceData(680, $xtop, number_format($row['spend'], 2), 9, 'right');
        $pdf->ezPlaceData(770, $xtop, sales_roas_report_fmt_roas($row['roas']), 9, 'right');

        $xtop -= 15;
    }

    if (($xtop - 20) <= 60) {
        $pdf->ezNewPage();
        $xtop = sales_roas_report_draw_columns($pdf, 530);
    }

    $pdf->line(25, $xtop, 770, $xtop);
    $pdf->ezPlaceData(25, $xtop - 9, "<b>Total</b>", 9, 'left');
    $pdf->ezPlaceData(260, $xtop - 9, "<b>".number_format($totals['online'], 2)."</b>", 9, 'right');
    $pdf->ezPlaceData(400, $xtop - 9, "<b>".number_format($totals['credit'], 2)."</b>", 9, 'right');
    $pdf->ezPlaceData(560, $xtop - 9, "<b>".number_format($totals['valid'], 2)."</b>", 9, 'right');
    $pdf->ezPlaceData(680, $xtop - 9, "<b>".number_format($totals['spend'], 2)."</b>", 9, 'right');
    $pdf->ezPlaceData(770, $xtop - 9, "<b>".sales_roas_report_fmt_roas($totals['roas'])."</b>", 9, 'right');
    $pdf->line(25, $xtop - 12, 770, $xtop - 12);
}

function sales_roas_report_build_sheet($rows, $totals, $report_user, $date_printed, $date_label)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Sales ROAS Report');

    $sheet->setCellValue('A1', 'Sales ROAS Report');
    $sheet->setCellValue('A2', 'Pdf Report by: '.$report_user);
    $sheet->setCellValue('A3', $date_label);
    $sheet->setCellValue('A4', 'Date Printed : '.$date_printed);
    $sheet->getStyle('A1:A4')->getFont()->setBold(true);

    $sheet->fromArray(array('Shop', 'Online Sales', 'Ads Credit', 'Valid Order Sales', 'Ads Spend', 'ROAS'), null, 'A6');
    $sheet->getStyle('A6:F6')->getFont()->setBold(true);

    $row_num = 7;
    foreach ($rows as $row) {
        $sheet->setCellValue('A'.$row_num, $row['shop']);
        $sheet->setCellValue('B'.$row_num, $row['online']);
        $sheet->setCellValue('C'.$row_num, $row['credit']);
        $sheet->setCellValue('D'.$row_num, $row['valid']);
        $sheet->setCellValue('E'.$row_num, $row['spend']);
        $sheet->setCellValue('F'.$row_num, $row['roas'] === null ? '-' : $row['roas']);
        $row_num++;
    }

    if (!empty($rows)) {
        $sheet->setCellValue('A'.$row_num, 'Total');
        $sheet->setCellValue('B'.$row_num, $totals['online']);
        $sheet->setCellValue('C'.$row_num, $totals['credit']);
        $sheet->setCellValue('D'.$row_num, $totals['valid']);
        $sheet->setCellValue('E'.$row_num, $totals['spend']);
        $sheet->setCellValue('F'.$row_num, $totals['roas'] === null ? '-' : $totals['roas']);
        $sheet->getStyle('A'.$row_num.':F'.$row_num)->getFont()->setBold(true);
    }

    $sheet->getStyle('B7:E'.$row_num)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('B6:F'.$row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A1:F'.$row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

    $sheet->getColumnDimension('A')->setWidth(28);
    $sheet->getColumnDimension('B')->setWidth(16);
    $sheet->getColumnDimension('C')->setWidth(16);
    $sheet->getColumnDimension('D')->setWidth(18);
    $sheet->getColumnDimension('E')->setWidth(16);
    $sheet->getColumnDimension('F')->setWidth(10);

    return $spreadsheet;
}

// lets tests/test_sales_roas_report.php load the functions above without a session, POST or output
if (defined('SALES_ROAS_REPORT_REP_LIB_ONLY')) {
    return;
}

session_start();

if (!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])) {
    header('location: index.php');
    exit;
}

require_once('ezpdfclass/class/class.ezpdf.php');
require_once('vendor/autoload.php');

date_default_timezone_set('Asia/Manila');
$date_printed = date("F j, Y h:i:s A");

$date_from_raw = isset($_POST['date_from']) ? trim((string)$_POST['date_from']) : '';
$date_to_raw = isset($_POST['date_to']) ? trim((string)$_POST['date_to']) : '';
$date_label = 'Date Range : '.($date_from_raw !== '' ? $date_from_raw : '...').' to '.($date_to_raw !== '' ? $date_to_raw : '...');

$shop_titles = (isset($_POST['shop_title']) && is_array($_POST['shop_title'])) ? $_POST['shop_title'] : array();
$online_sales = (isset($_POST['online_sales']) && is_array($_POST['online_sales'])) ? $_POST['online_sales'] : array();
$ads_credit = (isset($_POST['ads_credit']) && is_array($_POST['ads_credit'])) ? $_POST['ads_credit'] : array();
$valid_order_sales = (isset($_POST['valid_order_sales']) && is_array($_POST['valid_order_sales'])) ? $_POST['valid_order_sales'] : array();
$ads_spend = (isset($_POST['ads_spend']) && is_array($_POST['ads_spend'])) ? $_POST['ads_spend'] : array();

$rows = sales_roas_report_build_rows($shop_titles, $online_sales, $ads_credit, $valid_order_sales, $ads_spend);
$totals = sales_roas_report_totals($rows);

if (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') {
    $spreadsheet = sales_roas_report_build_sheet($rows, $totals, $_SESSION['userdesc'], $date_printed, $date_label);

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sales_roas_report.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new Xls($spreadsheet);
    $writer->save('php://output');
    exit;
}

ob_start();

$pdf = new Cezpdf('Letter', 'landscape');
sales_roas_report_build_pdf($pdf, $rows, $totals, $_SESSION['userdesc'], $date_printed, $date_label);
$pdf->addText(30, 15, 8, "Date Printed : ".date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
$pdf->ezStream();

ob_end_flush();
