<?php
ini_set('memory_limit', -1);
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once('ezpdfclass/class/class.ezpdf.php');
require_once('resources/func_pdf2tab.php');

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

ob_start();

if ($is_tab_export) {
    $pdf = new tab_ezpdf('Letter', 'landscape');
} else {
    $pdf = new Cezpdf('Letter', 'landscape');
}

$pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
$pdf->ezStartPageNumbers(700, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);

$date_printed = date("F j, Y h:i:s A");
$current_date_sql = date("Y-m-d");
$date_to_sql = $current_date_sql;
$sort_order = 'DESC';

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

$window_start_sql = subtract_one_month_same_day($date_to_sql);
$head_window_start = format_report_date($window_start_sql);
$head_date_to = format_report_date($date_to_sql);
$report_user = $username_session !== '' ? $username_session : 'System';

$xtop = 570;
$xleft = 25;
$line_right = 760;

$col_item = 20;
$col_tiktok = 280;
$col_lazada = 360;
$col_total_online = 450;
$col_ryu = 530;
$col_ratio = 620;
$col_valuation = 755;

$xheader = $pdf->openObject();
$pdf->saveState();
$pdf->ezPlaceData($xleft, $xtop, "<b>Customer Sales Report</b>", 15, 'left');
$xtop -= 15;
$header = "<b>Pdf Report by: " . $report_user . " </b>";
$pdf->ezPlaceData($xleft, $xtop, $header, 9, 'left');
$xtop -= 15;
$pdf->ezPlaceData($xleft, $xtop, "30-Day Window : " . $head_window_start . " to " . $head_date_to, 10, 'left');
$xtop -= 15;
$pdf->ezPlaceData($xleft, $xtop, "Sorted By : Total Online Qty Sold (Last 30 Days) " . $sort_order, 10, 'left');
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

$pdf->ezPlaceData($col_total_online, $xheader_base_y + 4, "<b>Total Online</b>", 7, 'right');
$pdf->ezPlaceData($col_total_online, $xheader_base_y - 5, "<b>Qty Sold</b>", 7, 'right');
$pdf->ezPlaceData($col_total_online, $xheader_base_y - 14, "<b>Last 30 Days</b>", 7, 'right');

$pdf->ezPlaceData($col_ryu, $xheader_base_y + 4, "<b>RYU Qty</b>", 7, 'right');
$pdf->ezPlaceData($col_ryu, $xheader_base_y - 5, "<b>Sold Last</b>", 7, 'right');
$pdf->ezPlaceData($col_ryu, $xheader_base_y - 14, "<b>30 Days</b>", 7, 'right');

$pdf->ezPlaceData($col_ratio, $xheader_base_y + 4, "<b>30 Days</b>", 7, 'right');
$pdf->ezPlaceData($col_ratio, $xheader_base_y - 5, "<b>Inventory</b>", 7, 'right');
$pdf->ezPlaceData($col_ratio, $xheader_base_y - 14, "<b>Ratio</b>", 7, 'right');

$pdf->ezPlaceData($col_valuation, $xheader_base_y + 4, "<b>Current Total</b>", 7, 'right');
$pdf->ezPlaceData($col_valuation, $xheader_base_y - 5, "<b>Inventory</b>", 7, 'right');
$pdf->ezPlaceData($col_valuation, $xheader_base_y - 14, "<b>Valuation</b>", 7, 'right');

$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($xheader, 'all');

$xtop = 485;

$select_db_base = "SELECT
    itemfile.itmcde,
    itemfile.itmdsc,
    COALESCE(platform_sales.tiktok_qty, 0) AS tiktok_qty,
    COALESCE(platform_sales.lazada_qty, 0) AS lazada_qty,
    COALESCE(platform_sales.shopee_qty, 0) AS shopee_qty,
    COALESCE(platform_sales.ryu_qty, 0) AS ryu_qty,
    COALESCE(all_sales.sold_qty_30, 0) AS sold_qty_30,
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
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'TIKTOK' THEN sale_tranfile2.itmqty ELSE 0 END) AS tiktok_qty,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'LAZADA' THEN sale_tranfile2.itmqty ELSE 0 END) AS lazada_qty,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'SHOPEE' THEN sale_tranfile2.itmqty ELSE 0 END) AS shopee_qty,
        SUM(CASE WHEN UPPER(customerfile.cusdsc) = 'RYU' THEN sale_tranfile2.itmqty ELSE 0 END) AS ryu_qty
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
        SUM(ratio_tranfile2.itmqty) AS sold_qty_30
    FROM tranfile1 ratio_tranfile1
    INNER JOIN tranfile2 ratio_tranfile2 ON ratio_tranfile1.docnum = ratio_tranfile2.docnum
    WHERE ratio_tranfile1.trncde = 'SAL'
      AND ratio_tranfile1.trndte >= '" . $window_start_sql . "'
      AND ratio_tranfile1.trndte <= '" . $date_to_sql . "'
    GROUP BY ratio_tranfile2.itmcde
) all_sales ON itemfile.itmcde = all_sales.itmcde
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
    (base.tiktok_qty + base.lazada_qty + base.shopee_qty) AS total_online_qty,
    base.ryu_qty,
    base.current_stock,
    base.sold_qty_30,
    CASE WHEN base.sold_qty_30 = 0 THEN 0 ELSE (base.current_stock / base.sold_qty_30) END AS inventory_ratio,
    base.latest_cost,
    (base.current_stock * base.latest_cost) AS current_inventory_valuation
FROM (" . $select_db_base . ") base
ORDER BY total_online_qty " . $sort_order . ", base.itmdsc ASC";

$stmt_main = $link->prepare($select_db);
$stmt_main->execute();

$total_tiktok = 0;
$total_lazada = 0;
$total_online = 0;
$total_ryu = 0;
$total_valuation = 0;

while ($rs_main = $stmt_main->fetch()) {
    $item_desc = normalize_item_text($rs_main["itmdsc"]);
    $item_lines = wrap_str_two_lines($item_desc, 235, 9, 48);
    $row_height = count($item_lines) > 1 ? 25 : 15;

    if (($xtop - $row_height) <= 60) {
        $pdf->ezNewPage();
        $xtop = 485;
    }

    $xtop -= 30;
    $row_y = $xtop;

    $tiktok_qty = (float) $rs_main["tiktok_qty"];
    $lazada_qty = (float) $rs_main["lazada_qty"];
    $total_online_qty = (float) $rs_main["total_online_qty"];
    $ryu_qty = (float) $rs_main["ryu_qty"];
    $inventory_ratio = (float) $rs_main["inventory_ratio"];
    $current_inventory_valuation = (float) $rs_main["current_inventory_valuation"];

    $pdf->ezPlaceData($col_item, $row_y, $item_lines[0] ?? '', 9, "left");
    if (isset($item_lines[1]) && $item_lines[1] !== '') {
        $pdf->ezPlaceData($col_item, $row_y - 10, $item_lines[1], 9, "left");
        $xtop -= 10;
    }

    $pdf->ezPlaceData($col_tiktok, $row_y, number_format($tiktok_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_lazada, $row_y, number_format($lazada_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_total_online, $row_y, number_format($total_online_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_ryu, $row_y, number_format($ryu_qty, 0), 9, "right");
    $pdf->ezPlaceData($col_ratio, $row_y, number_format($inventory_ratio, 2), 9, "right");
    $pdf->ezPlaceData($col_valuation, $row_y, number_format($current_inventory_valuation, 2), 9, "right");

    $total_tiktok += $tiktok_qty;
    $total_lazada += $lazada_qty;
    $total_online += $total_online_qty;
    $total_ryu += $ryu_qty;
    $total_valuation += $current_inventory_valuation;
}

if (($xtop - 25) <= 60) {
    $pdf->ezNewPage();
    $xtop = 485;
}

$pdf->line($xleft, $xtop - 22, $line_right, $xtop - 22);
$xtop -= 15;

$pdf->ezPlaceData($col_item, $xtop, "Total", 9, "left");
$pdf->ezPlaceData($col_tiktok, $xtop, number_format($total_tiktok, 0), 9, "right");
$pdf->ezPlaceData($col_lazada, $xtop, number_format($total_lazada, 0), 9, "right");
$pdf->ezPlaceData($col_total_online, $xtop, number_format($total_online, 0), 9, "right");
$pdf->ezPlaceData($col_ryu, $xtop, number_format($total_ryu, 0), 9, "right");
$pdf->ezPlaceData($col_valuation, $xtop, number_format($total_valuation, 2), 9, "right");

if ($is_tab_export) {
    $pdf->ezStream($tab_file_type);
} else {
    $pdf->ezStream();
}
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
