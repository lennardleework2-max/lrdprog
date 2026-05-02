<?php
ini_set('memory_limit', -1);
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once('ezpdfclass/class/class.ezpdf.php');
require_once('resources/func_pdf2tab.php');
require_once('vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$tab_file_type = 'xlsx';
$is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] === 'tab');
$export_label = $is_tab_export ? strtoupper($tab_file_type) : 'PDF';

date_default_timezone_set('Asia/Manila');

// Log export activity.
$username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
$username_full_name = '';
if (isset($_SESSION['recid'])) {
    $select_db_session_user = 'SELECT * FROM users WHERE recid=?';
    $stmt_session_user = $link->prepare($select_db_session_user);
    $stmt_session_user->execute(array($_SESSION['recid']));
    $rs_session_user = $stmt_session_user->fetch();
    if ($rs_session_user) {
        $username_full_name = $rs_session_user["full_name"];
    }
}
$xtrndte_log = date("Y-m-d H:i:s");
$xprog_module_log = 'CUSTOMER SALES';
$xactivity_log = $is_tab_export ? 'export_txt' : 'export_pdf';
$xremarks_log = "Exported " . $export_label . " from Customer Sales";
PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

if (!$is_tab_export) {
    ob_start();
    $pdf = new Cezpdf('Letter', 'landscape');
    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(700, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
}

$date_printed = date("F j, Y h:i:s A");
$current_date_sql = date("Y-m-d");
$date_to_sql = $current_date_sql;
$sort_order = 'DESC';
$sort_field = 'total_online_qty';
$sort_field_sql = 'total_online_qty';
$sort_field_label = 'Total Online Qty Sold (Last 30 Days)';

if (isset($_POST['date_to']) && trim((string)$_POST['date_to']) !== '') {
    $parsed_date_to = normalize_report_date($_POST['date_to']);
    if ($parsed_date_to !== '') {
        $date_to_sql = $parsed_date_to;
    }
}

if (isset($_POST['orderby_select'])) {
    $tmp_sort_order = strtoupper(trim((string)$_POST['orderby_select']));
    if ($tmp_sort_order === 'ASC' || $tmp_sort_order === 'DESC') {
        $sort_order = $tmp_sort_order;
    }
}

$sort_field_map = array(
    'item' => array('sql' => 'base.itmdsc', 'label' => 'Item'),
    'tiktok_qty' => array('sql' => 'tiktok_qty', 'label' => 'Tiktok Qty Sold (Last 30 Days)'),
    'lazada_qty' => array('sql' => 'lazada_qty', 'label' => 'Lazada Qty Sold (Last 30 Days)'),
    'shopee_qty' => array('sql' => 'shopee_qty', 'label' => 'Shopee Qty Sold (Last 30 Days)'),
    'total_online_qty' => array('sql' => 'total_online_qty', 'label' => 'Total Online Qty Sold (Last 30 Days)'),
    'ryu_qty' => array('sql' => 'ryu_qty', 'label' => 'RYU Qty Sold (Last 30 Days)'),
    'inventory_ratio' => array('sql' => 'inventory_ratio', 'label' => '30 Days Inventory Ratio'),
    'current_total_stock' => array('sql' => 'current_total_stock', 'label' => 'Current Total Stock'),
    'current_inventory_valuation' => array('sql' => 'current_inventory_valuation', 'label' => 'Current Total Inventory Valuation')
);

if (isset($_POST['sort_field_select'])) {
    $tmp_sort_field = trim((string) $_POST['sort_field_select']);
    if (isset($sort_field_map[$tmp_sort_field])) {
        $sort_field = $tmp_sort_field;
    }
}

$sort_field_sql = $sort_field_map[$sort_field]['sql'];
$sort_field_label = $sort_field_map[$sort_field]['label'];

$window_start_sql = subtract_one_month_same_day($date_to_sql);
$head_window_start = format_report_date($window_start_sql);
$head_date_to = format_report_date($date_to_sql);
$report_user = $username_session !== '' ? $username_session : 'System';

$xtop = 570;
$xleft = 25;
$line_right = 760;
$detail_start_top = 468;
$row_step_single = 16;
$row_step_double = 22;
$item_second_line_offset = 8;
$item_wrap_width = 150;

$col_item = 20;
$col_tiktok = 215;
$col_lazada = 280;
$col_shopee = 345;
$col_total_online = 425;
$col_ryu = 495;
$col_ratio = 580;
$col_current_total_stock = 665;
$col_valuation = 755;

if (!$is_tab_export) {
    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, "<b>Customer Sales Report</b>", 15, 'left');
    $xtop -= 15;
    $header = "<b>Pdf Report by: " . $report_user . " </b>";
    $pdf->ezPlaceData($xleft, $xtop, $header, 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, "30-Day Window : " . $head_window_start . " to " . $head_date_to, 10, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, "Sorted By : " . $sort_field_label . " " . $sort_order, 10, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : ' . $date_printed, 10, 'left');
    $xtop -= 20;

    $pdf->setLineStyle(.5);
    $pdf->line($xleft, $xtop + 6, $line_right, $xtop + 6);
    $pdf->line($xleft, $xtop - 30, $line_right, $xtop - 30);

    $xheader_base_y = $xtop - 8;

    $pdf->ezPlaceData($col_item, $xheader_base_y, "<b>Item</b>", 10, 'left');

    $pdf->ezPlaceData($col_tiktok, $xheader_base_y + 4, "<b>Tiktok Qty</b>", 7, 'right');
    $pdf->ezPlaceData($col_tiktok, $xheader_base_y - 5, "<b>Sold Last</b>", 7, 'right');
    $pdf->ezPlaceData($col_tiktok, $xheader_base_y - 14, "<b>30 Days</b>", 7, 'right');

    $pdf->ezPlaceData($col_lazada, $xheader_base_y + 4, "<b>Lazada Qty</b>", 7, 'right');
    $pdf->ezPlaceData($col_lazada, $xheader_base_y - 5, "<b>Sold Last</b>", 7, 'right');
    $pdf->ezPlaceData($col_lazada, $xheader_base_y - 14, "<b>30 Days</b>", 7, 'right');

    $pdf->ezPlaceData($col_shopee, $xheader_base_y + 4, "<b>Shopee Qty</b>", 7, 'right');
    $pdf->ezPlaceData($col_shopee, $xheader_base_y - 5, "<b>Sold Last</b>", 7, 'right');
    $pdf->ezPlaceData($col_shopee, $xheader_base_y - 14, "<b>30 Days</b>", 7, 'right');

    $pdf->ezPlaceData($col_total_online, $xheader_base_y + 4, "<b>Total Online</b>", 7, 'right');
    $pdf->ezPlaceData($col_total_online, $xheader_base_y - 5, "<b>Qty Sold</b>", 7, 'right');
    $pdf->ezPlaceData($col_total_online, $xheader_base_y - 14, "<b>Last 30 Days</b>", 7, 'right');

    $pdf->ezPlaceData($col_ryu, $xheader_base_y + 4, "<b>RYU Qty</b>", 7, 'right');
    $pdf->ezPlaceData($col_ryu, $xheader_base_y - 5, "<b>Sold Last</b>", 7, 'right');
    $pdf->ezPlaceData($col_ryu, $xheader_base_y - 14, "<b>30 Days</b>", 7, 'right');

    $pdf->ezPlaceData($col_ratio, $xheader_base_y + 4, "<b>30 Days</b>", 7, 'right');
    $pdf->ezPlaceData($col_ratio, $xheader_base_y - 5, "<b>Inventory</b>", 7, 'right');
    $pdf->ezPlaceData($col_ratio, $xheader_base_y - 14, "<b>Ratio</b>", 7, 'right');

    $pdf->ezPlaceData($col_current_total_stock, $xheader_base_y + 4, "<b>Current Total</b>", 7, 'right');
    $pdf->ezPlaceData($col_current_total_stock, $xheader_base_y - 5, "<b>Stock</b>", 7, 'right');

    $pdf->ezPlaceData($col_valuation, $xheader_base_y + 4, "<b>Current Total</b>", 7, 'right');
    $pdf->ezPlaceData($col_valuation, $xheader_base_y - 5, "<b>Inventory</b>", 7, 'right');
    $pdf->ezPlaceData($col_valuation, $xheader_base_y - 14, "<b>Valuation</b>", 7, 'right');

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    $xtop = $detail_start_top;
}

$select_db_base = "SELECT
    itemfile.itmcde,
    itemfile.itmdsc,
    COALESCE(platform_sales.tiktok_qty, 0) AS tiktok_qty,
    COALESCE(platform_sales.lazada_qty, 0) AS lazada_qty,
    COALESCE(platform_sales.shopee_qty, 0) AS shopee_qty,
    COALESCE(platform_sales.ryu_qty, 0) AS ryu_qty,
    COALESCE(all_sales.sold_qty_30, 0) AS sold_qty_30,
    COALESCE(total_stock.current_total_stock, 0) AS current_total_stock,
    COALESCE(current_stock.current_stock, 0) AS current_stock,
    COALESCE((
        SELECT pur_tranfile2.untprc
        FROM tranfile2 pur_tranfile2
        INNER JOIN tranfile1 pur_tranfile1 ON pur_tranfile2.docnum = pur_tranfile1.docnum
        WHERE pur_tranfile2.itmcde = itemfile.itmcde
          AND pur_tranfile1.trncde = 'PUR'
          AND pur_tranfile1.trndte <= '" . $date_to_sql . "'
        ORDER BY pur_tranfile1.trndte DESC, pur_tranfile2.recid DESC
        LIMIT 1
    ), 0) AS latest_cost
FROM itemfile
LEFT JOIN (
    SELECT
        sale_tranfile2.itmcde,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'TIKTOK' THEN sale_tranfile2.stkqty * -1 ELSE 0 END) AS tiktok_qty,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'LAZADA' THEN sale_tranfile2.stkqty * -1 ELSE 0 END) AS lazada_qty,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'SHOPEE' THEN sale_tranfile2.stkqty * -1 ELSE 0 END) AS shopee_qty,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'RYU' THEN sale_tranfile2.stkqty * -1 ELSE 0 END) AS ryu_qty
    FROM tranfile1 sale_tranfile1
    INNER JOIN tranfile2 sale_tranfile2 ON sale_tranfile1.docnum = sale_tranfile2.docnum
    INNER JOIN customerfile ON sale_tranfile1.cuscde = customerfile.cuscde
    WHERE sale_tranfile1.trncde = 'SAL'
      AND sale_tranfile1.trndte >= '" . $window_start_sql . "'
      AND sale_tranfile1.trndte <= '" . $date_to_sql . "'
      AND UPPER(customerfile.cusdsc) IN ('TIKTOK', 'LAZADA', 'SHOPEE', 'RYU')
    GROUP BY sale_tranfile2.itmcde
) platform_sales ON itemfile.itmcde = platform_sales.itmcde
LEFT JOIN (
    SELECT
        ratio_tranfile2.itmcde,
        SUM(ratio_tranfile2.stkqty * -1) AS sold_qty_30
    FROM tranfile1 ratio_tranfile1
    INNER JOIN tranfile2 ratio_tranfile2 ON ratio_tranfile1.docnum = ratio_tranfile2.docnum
    WHERE ratio_tranfile1.trncde = 'SAL'
      AND ratio_tranfile1.trndte >= '" . $window_start_sql . "'
      AND ratio_tranfile1.trndte <= '" . $date_to_sql . "'
    GROUP BY ratio_tranfile2.itmcde
) all_sales ON itemfile.itmcde = all_sales.itmcde
LEFT JOIN (
    SELECT
        all_stock_tranfile2.itmcde,
        SUM(all_stock_tranfile2.stkqty) AS current_total_stock
    FROM tranfile1 all_stock_tranfile1
    LEFT JOIN tranfile2 all_stock_tranfile2 ON all_stock_tranfile1.docnum = all_stock_tranfile2.docnum
    WHERE all_stock_tranfile2.itmcde IS NOT NULL
    GROUP BY all_stock_tranfile2.itmcde
) total_stock ON itemfile.itmcde = total_stock.itmcde
LEFT JOIN (
    SELECT
        stock_tranfile2.itmcde,
        SUM(stock_tranfile2.stkqty) AS current_stock
    FROM tranfile1 stock_tranfile1
    INNER JOIN tranfile2 stock_tranfile2 ON stock_tranfile1.docnum = stock_tranfile2.docnum
    WHERE stock_tranfile1.trndte <= '" . $date_to_sql . "'
    GROUP BY stock_tranfile2.itmcde
) current_stock ON itemfile.itmcde = current_stock.itmcde";

$select_db = "SELECT
    base.itmcde,
    base.itmdsc,
    base.tiktok_qty,
    base.lazada_qty,
    base.shopee_qty,
    (base.tiktok_qty + base.lazada_qty + base.shopee_qty) AS total_online_qty,
    base.ryu_qty,
    base.current_total_stock,
    base.current_stock,
    base.sold_qty_30,
    CASE WHEN base.sold_qty_30 = 0 THEN 0 ELSE (base.current_stock / base.sold_qty_30) END AS inventory_ratio,
    base.latest_cost,
    (base.current_stock * base.latest_cost) AS current_inventory_valuation
FROM (" . $select_db_base . ") base
ORDER BY " . $sort_field_sql . " " . $sort_order . ", base.itmdsc ASC";

$stmt_main = $link->prepare($select_db);
$stmt_main->execute();

$report_rows = array();
$total_tiktok = 0;
$total_lazada = 0;
$total_shopee = 0;
$total_online = 0;
$total_ryu = 0;
$total_current_total_stock = 0;
$total_valuation = 0;

while ($rs_main = $stmt_main->fetch()) {
    $report_rows[] = $rs_main;

    $total_tiktok += (float) $rs_main["tiktok_qty"];
    $total_lazada += (float) $rs_main["lazada_qty"];
    $total_shopee += (float) $rs_main["shopee_qty"];
    $total_online += (float) $rs_main["total_online_qty"];
    $total_ryu += (float) $rs_main["ryu_qty"];
    $total_current_total_stock += (float) $rs_main["current_total_stock"];
    $total_valuation += (float) $rs_main["current_inventory_valuation"];
}

if ($is_tab_export) {
    export_customer_sales_xlsx(
        $report_rows,
        $report_user,
        $head_window_start,
        $head_date_to,
        $sort_field_label,
        $sort_order,
        $date_printed,
        $total_tiktok,
        $total_lazada,
        $total_shopee,
        $total_online,
        $total_ryu,
        $total_current_total_stock,
        $total_valuation
    );
    exit;
}

foreach ($report_rows as $rs_main) {
    $item_desc = normalize_item_text($rs_main["itmdsc"]);
    $item_lines = wrap_str_two_lines($item_desc, $item_wrap_width, 9, 48);
    $has_two_lines = count($item_lines) > 1 && isset($item_lines[1]) && $item_lines[1] !== '';
    $row_height = $has_two_lines ? $row_step_double : $row_step_single;

    if (($xtop - $row_height) <= 60) {
        $pdf->ezNewPage();
        $xtop = $detail_start_top;
    }

    $xtop -= $row_height;
    $row_y = $xtop;

    $tiktok_qty = (float) $rs_main["tiktok_qty"];
    $lazada_qty = (float) $rs_main["lazada_qty"];
    $shopee_qty = (float) $rs_main["shopee_qty"];
    $total_online_qty = (float) $rs_main["total_online_qty"];
    $ryu_qty = (float) $rs_main["ryu_qty"];
    $inventory_ratio = (float) $rs_main["inventory_ratio"];
    $current_total_stock = (float) $rs_main["current_total_stock"];
    $current_inventory_valuation = (float) $rs_main["current_inventory_valuation"];

    $pdf->ezPlaceData($col_item, $row_y, $item_lines[0] ?? '', 8, "left");
    if ($has_two_lines) {
        $pdf->ezPlaceData($col_item, $row_y - $item_second_line_offset, $item_lines[1], 8, "left");
    }

    $pdf->ezPlaceData($col_tiktok, $row_y, number_format($tiktok_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_lazada, $row_y, number_format($lazada_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_shopee, $row_y, number_format($shopee_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_total_online, $row_y, number_format($total_online_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_ryu, $row_y, number_format($ryu_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_ratio, $row_y, number_format($inventory_ratio, 2), 9, "right");
    $pdf->ezPlaceData($col_current_total_stock, $row_y, number_format($current_total_stock, 2), 9, "right");
    $pdf->ezPlaceData($col_valuation, $row_y, number_format($current_inventory_valuation, 2), 9, "right");
}

if (($xtop - 25) <= 60) {
    $pdf->ezNewPage();
    $xtop = $detail_start_top;
}

$pdf->line($xleft, $xtop - 22, $line_right, $xtop - 22);
$xtop -= 15;

$pdf->ezPlaceData($col_item, $xtop, "Total", 9, "left");
$pdf->ezPlaceData($col_tiktok, $xtop, number_format($total_tiktok, 0), 9, "right");
$pdf->ezPlaceData($col_lazada, $xtop, number_format($total_lazada, 0), 9, "right");
$pdf->ezPlaceData($col_shopee, $xtop, number_format($total_shopee, 0), 9, "right");
$pdf->ezPlaceData($col_total_online, $xtop, number_format($total_online, 0), 9, "right");
$pdf->ezPlaceData($col_ryu, $xtop, number_format($total_ryu, 0), 9, "right");
$pdf->ezPlaceData($col_current_total_stock, $xtop, number_format($total_current_total_stock, 2), 9, "right");
$pdf->ezPlaceData($col_valuation, $xtop, number_format($total_valuation, 2), 9, "right");
$pdf->ezStream();
ob_end_flush();

function normalize_report_date($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $formats = array(
        'Y-m-d',
        'Y-m-d H:i:s',
        'm-d-Y',
        'm/d/Y',
        'm-d-Y H:i:s',
        'm/d/Y H:i:s',
        'Y/m/d',
        'Y/m/d H:i:s',
        'd-m-Y',
        'd/m/Y',
        'd-m-Y H:i:s',
        'd/m/Y H:i:s'
    );

    foreach ($formats as $format) {
        $date_obj = DateTime::createFromFormat('!' . $format, $value);
        if ($date_obj instanceof DateTime) {
            $errors = DateTime::getLastErrors();
            if ($errors === false || (empty($errors['warning_count']) && empty($errors['error_count']))) {
                return $date_obj->format('Y-m-d');
            }
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
}

function format_report_date($value)
{
    $normalized_date = normalize_report_date($value);
    if ($normalized_date === '') {
        return '';
    }

    return date('m-d-Y', strtotime($normalized_date));
}

function subtract_one_month_same_day($value)
{
    $normalized_date = normalize_report_date($value);
    if ($normalized_date === '') {
        return '';
    }

    $date_obj = new DateTimeImmutable($normalized_date);
    $year = (int) $date_obj->format('Y');
    $month = (int) $date_obj->format('n');
    $day = (int) $date_obj->format('j');

    $target_month = $month - 1;
    $target_year = $year;
    if ($target_month < 1) {
        $target_month = 12;
        $target_year--;
    }

    $days_in_target_month = cal_days_in_month(CAL_GREGORIAN, $target_month, $target_year);
    $target_day = min($day, $days_in_target_month);

    return sprintf('%04d-%02d-%02d', $target_year, $target_month, $target_day);
}

function wrap_str_two_lines($string, $max_wid, $fsize, $tab_max_chars = 48)
{
    global $pdf;

    $string = trim((string) $string);
    if ($string === '') {
        return array('');
    }

    if (get_class($pdf) === 'tab_ezpdf') {
        return array(trim_text_by_chars($string, $tab_max_chars));
    }

    $max_wid -= 5;
    if ($pdf->getTextWidth($fsize, $string) <= $max_wid) {
        return array($string);
    }

    $line1 = fit_text_to_width($string, $max_wid, $fsize, false);
    $remaining = ltrim(substr($string, strlen($line1)));

    $last_space = strrpos($line1, ' ');
    if ($last_space !== false && $last_space > 0) {
        $line1 = rtrim(substr($line1, 0, $last_space));
        $remaining = ltrim(substr($string, strlen($line1)));
    }

    $line2 = fit_text_to_width($remaining, $max_wid, $fsize, true);
    return array($line1, $line2);
}

function trim_text_by_chars($string, $max_chars = 48)
{
    $string = trim((string) $string);
    if ($string === '') {
        return '';
    }

    if (strlen($string) <= $max_chars) {
        return $string;
    }

    $cut = rtrim(substr($string, 0, $max_chars));
    $last_space = strrpos($cut, ' ');
    if ($last_space !== false && $last_space > 0) {
        $cut = rtrim(substr($cut, 0, $last_space));
    }

    return $cut . '...';
}

function normalize_item_text($string)
{
    $string = trim((string) $string);
    if ($string === '') {
        return '';
    }

    $search = array('Ã¢â‚¬Å“', 'Ã¢â‚¬Â', 'Ã¢â‚¬Ëœ', 'Ã¢â‚¬â„¢', 'Ã¢â‚¬â€œ', 'Ã¢â‚¬â€', 'Ã‚', 'â€œ', 'â€', 'â€˜', 'â€™', 'â€“', 'â€”');
    $replace = array('"', '"', "'", "'", '-', '-', '', '"', '"', "'", "'", '-', '-');
    $string = str_replace($search, $replace, $string);
    $string = preg_replace('/\s+/', ' ', $string);

    return trim($string);
}

function fit_text_to_width($string, $max_wid, $fsize, $add_ellipsis = false)
{
    global $pdf;

    $string = (string) $string;
    if ($string === '') {
        return '';
    }

    $limit_wid = $max_wid;
    if ($add_ellipsis) {
        $limit_wid = $max_wid - $pdf->getTextWidth($fsize, '...');
    }
    if ($limit_wid < 1) {
        $limit_wid = 1;
    }

    $xarr_str = str_split($string);
    $xxstr = '';
    $xcut = false;
    foreach ($xarr_str as $value) {
        $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
        if ($xstr_wid > $limit_wid) {
            $xcut = true;
            break;
        }
        $xxstr = $xxstr . $value;
    }

    if ($add_ellipsis && $xcut) {
        $xxstr = rtrim($xxstr) . '...';
    }

    return $xxstr;
}

function export_customer_sales_xlsx(
    $rows,
    $report_user,
    $head_window_start,
    $head_date_to,
    $sort_field_label,
    $sort_order,
    $date_printed,
    $total_tiktok,
    $total_lazada,
    $total_shopee,
    $total_online,
    $total_ryu,
    $total_current_total_stock,
    $total_valuation
) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Customer Sales');

    $sheet->mergeCells('A1:I1');
    $sheet->setCellValue('A1', 'Customer Sales Report');
    $sheet->setCellValue('A2', 'Pdf Report by: ' . $report_user);
    $sheet->setCellValue('A3', '30-Day Window : ' . $head_window_start . ' to ' . $head_date_to);
    $sheet->setCellValue('A4', 'Sorted By : ' . $sort_field_label . ' ' . $sort_order);
    $sheet->setCellValue('A5', 'Date Printed : ' . $date_printed);

    $header_row = 7;
    $sheet->fromArray(array(
        'Item',
        'Tiktok Qty Sold Last 30 Days',
        'Lazada Qty Sold Last 30 Days',
        'Shopee Qty Sold Last 30 Days',
        'Total Online Qty Sold Last 30 Days',
        'RYU Qty Sold Last 30 Days',
        '30 Days Inventory Ratio',
        'Current Total Stock',
        'Current Total Inventory Valuation'
    ), null, 'A' . $header_row);

    $row_num = $header_row + 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $row_num, normalize_item_text($row['itmdsc']));
        $sheet->setCellValue('B' . $row_num, (float) $row['tiktok_qty']);
        $sheet->setCellValue('C' . $row_num, (float) $row['lazada_qty']);
        $sheet->setCellValue('D' . $row_num, (float) $row['shopee_qty']);
        $sheet->setCellValue('E' . $row_num, (float) $row['total_online_qty']);
        $sheet->setCellValue('F' . $row_num, (float) $row['ryu_qty']);
        $sheet->setCellValue('G' . $row_num, (float) $row['inventory_ratio']);
        $sheet->setCellValue('H' . $row_num, (float) $row['current_total_stock']);
        $sheet->setCellValue('I' . $row_num, (float) $row['current_inventory_valuation']);
        $row_num++;
    }

    $sheet->setCellValue('A' . $row_num, 'Total');
    $sheet->setCellValue('B' . $row_num, $total_tiktok);
    $sheet->setCellValue('C' . $row_num, $total_lazada);
    $sheet->setCellValue('D' . $row_num, $total_shopee);
    $sheet->setCellValue('E' . $row_num, $total_online);
    $sheet->setCellValue('F' . $row_num, $total_ryu);
    $sheet->setCellValue('H' . $row_num, $total_current_total_stock);
    $sheet->setCellValue('I' . $row_num, $total_valuation);

    $sheet->getStyle('A1:A5')->getFont()->setBold(true);
    $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getAlignment()->setWrapText(true);
    $sheet->getStyle('A' . $header_row . ':I' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle('B' . ($header_row + 1) . ':F' . $row_num)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('G' . ($header_row + 1) . ':G' . ($row_num - 1))->getNumberFormat()->setFormatCode('0.00');
    $sheet->getStyle('H' . ($header_row + 1) . ':I' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('B' . ($header_row + 1) . ':I' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A' . ($header_row + 1) . ':A' . $row_num)->getAlignment()->setWrapText(true);
    $sheet->getStyle('A' . $row_num . ':I' . $row_num)->getFont()->setBold(true);

    $sheet->getColumnDimension('A')->setWidth(48);
    $sheet->getColumnDimension('B')->setWidth(16);
    $sheet->getColumnDimension('C')->setWidth(16);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(16);
    $sheet->getColumnDimension('G')->setWidth(18);
    $sheet->getColumnDimension('H')->setWidth(18);
    $sheet->getColumnDimension('I')->setWidth(22);
    $sheet->freezePane('A8');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'customer_sales_report_' . date('Ymd_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}
