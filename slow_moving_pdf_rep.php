<?php

session_start();

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("ezpdfclass/class/class.ezpdf.php");
require_once("resources/func_pdf2tab.php");
require_once('vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set('Asia/Manila');

// function slow_moving_pdf_deny_access()
// {
//     if (!headers_sent()) {
//         header('Location: index.php');
//     }
//     exit;
// }

function slow_moving_pdf_fail_request($message)
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

function slow_moving_pdf_has_export_access($link, $usercode, $userdesc, $permission_filename)
{
    if ($userdesc === 'admin') {
        return true;
    }

    $select_db_crud = "SELECT view, export FROM user_menus WHERE usercode=? AND menprogram=? LIMIT 1";
    $stmt_crud = $link->prepare($select_db_crud);
    $stmt_crud->execute(array($usercode, $permission_filename));
    $rs_crud = $stmt_crud->fetch();

    if (empty($rs_crud)) {
        return false;
    }

    return ((int)$rs_crud['view'] === 1 && (int)$rs_crud['export'] === 1);
}

function slow_moving_pdf_text($value)
{
    $value = trim((string)$value);
    $value = preg_replace('/[\r\n\t]+/', ' ', $value);
    return $value;
}

function slow_moving_pdf_format_date($value, $empty_text = 'No sales yet')
{
    if (empty($value)) {
        return $empty_text;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $empty_text;
    }

    return date('m/d/Y', $timestamp);
}

function slow_moving_pdf_format_stock($value)
{
    return number_format((float)$value, 2);
}

function slow_moving_pdf_use_font($pdf, $is_bold = false)
{
    if ($is_bold) {
        $pdf->selectFont("ezpdfclass/fonts/Helvetica-Bold.afm");
        return;
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
}

function slow_moving_pdf_add_text_left($pdf, $x, $y, $text, $size, $is_bold = false)
{
    slow_moving_pdf_use_font($pdf, $is_bold);
    $pdf->addText($x, $y, $size, slow_moving_pdf_text($text));
}

function slow_moving_pdf_add_text_center($pdf, $x, $y, $width, $text, $size, $is_bold = false)
{
    slow_moving_pdf_use_font($pdf, $is_bold);
    $text = slow_moving_pdf_text($text);
    $text_width = $pdf->getTextWidth($size, $text);
    $text_x = $x + (($width - $text_width) / 2);

    if ($text_x < ($x + 3)) {
        $text_x = $x + 3;
    }

    $pdf->addText($text_x, $y, $size, $text);
}

function slow_moving_pdf_add_text_right($pdf, $x, $y, $width, $text, $size, $is_bold = false)
{
    slow_moving_pdf_use_font($pdf, $is_bold);
    $text = slow_moving_pdf_text($text);
    $text_width = $pdf->getTextWidth($size, $text);
    $text_x = ($x + $width) - $text_width - 6;

    if ($text_x < ($x + 3)) {
        $text_x = $x + 3;
    }

    $pdf->addText($text_x, $y, $size, $text);
}

function slow_moving_pdf_render_page_header($pdf, $date_printed)
{
    $left = 30;
    $right = 582;
    $top = 742;

    $pdf->setColor(0, 0, 0);
    slow_moving_pdf_add_text_left($pdf, $left, $top, 'Slow Moving Report', 15, true);
    slow_moving_pdf_add_text_left($pdf, $left, $top - 18, 'Date Printed : ' . $date_printed, 9, false);
    $pdf->setLineStyle(.5);
    $pdf->line($left, $top - 30, $right, $top - 30);

    return $top - 48;
}

function slow_moving_pdf_draw_table_header($pdf, $x, $y, $item_width, $last_sale_width, $latest_purchase_width, $current_stock_width, $row_height)
{
    $last_sale_x = $x + $item_width;
    $latest_purchase_x = $last_sale_x + $last_sale_width;
    $current_stock_x = $latest_purchase_x + $latest_purchase_width;
    $row_y = $y - $row_height;

    $pdf->setColor(0.96, 0.82, 0.82, 'fill');
    $pdf->filledRectangle($x, $row_y, $item_width + $last_sale_width + $latest_purchase_width + $current_stock_width, $row_height);
    $pdf->setColor(0, 0, 0, 'stroke');
    $pdf->rectangle($x, $row_y, $item_width + $last_sale_width + $latest_purchase_width + $current_stock_width, $row_height);
    $pdf->line($last_sale_x, $row_y, $last_sale_x, $row_y + $row_height);
    $pdf->line($latest_purchase_x, $row_y, $latest_purchase_x, $row_y + $row_height);
    $pdf->line($current_stock_x, $row_y, $current_stock_x, $row_y + $row_height);

    $text_y = $row_y + 8;
    slow_moving_pdf_add_text_center($pdf, $x, $text_y, $item_width, 'ITEM', 10, true);
    slow_moving_pdf_add_text_center($pdf, $last_sale_x, $text_y, $last_sale_width, 'LAST SALE', 10, true);
    slow_moving_pdf_add_text_center($pdf, $latest_purchase_x, $text_y, $latest_purchase_width, 'LATEST PURCHASE', 10, true);
    slow_moving_pdf_add_text_center($pdf, $current_stock_x, $text_y, $current_stock_width, 'CURRENT STOCK', 10, true);

    return $row_y;
}

function slow_moving_pdf_draw_detail_row($pdf, $x, $y, $item_width, $last_sale_width, $latest_purchase_width, $current_stock_width, $item_lines, $last_sale, $latest_purchase, $current_stock)
{
    $last_sale_x = $x + $item_width;
    $latest_purchase_x = $last_sale_x + $last_sale_width;
    $current_stock_x = $latest_purchase_x + $latest_purchase_width;

    $pdf->setColor(0, 0, 0, 'stroke');

    // Fixed offset from previous row to first text line (like top_sales_item_pdf.php)
    $base_offset = 15;
    $line_spacing = 10;
    $text_y = $y - $base_offset;

    // Render each line of the item name
    $line_count = count($item_lines);
    foreach ($item_lines as $line_index => $line_text) {
        slow_moving_pdf_add_text_left($pdf, $x + 3, $text_y - ($line_index * $line_spacing), $line_text, 9, false);
    }

    // Other columns align with first line
    slow_moving_pdf_add_text_center($pdf, $last_sale_x, $text_y, $last_sale_width, $last_sale, 9, false);
    slow_moving_pdf_add_text_center($pdf, $latest_purchase_x, $text_y, $latest_purchase_width, $latest_purchase, 9, false);
    slow_moving_pdf_add_text_right($pdf, $current_stock_x, $text_y, $current_stock_width, slow_moving_pdf_format_stock($current_stock), 9, false);

    // Calculate actual row height and return new Y position
    $actual_row_height = $base_offset + (max(1, $line_count) - 1) * $line_spacing;
    return $y - $actual_row_height;
}

function slow_moving_pdf_wrap_text($pdf, $string, $max_wid, $fsize)
{
    $string = trim((string)$string);
    if ($string === '') {
        return array('');
    }

    $max_wid -= 5;
    if ($pdf->getTextWidth($fsize, $string) <= $max_wid) {
        return array($string);
    }

    $wrapped_lines = array();
    $remaining = $string;

    while ($remaining !== '') {
        if ($pdf->getTextWidth($fsize, $remaining) <= $max_wid) {
            $wrapped_lines[] = $remaining;
            break;
        }

        $line = slow_moving_pdf_fit_text_to_width($pdf, $remaining, $max_wid, $fsize, false);
        if ($line === '') {
            $line = substr($remaining, 0, 1);
        }

        $last_space = strrpos($line, ' ');
        if ($last_space !== false && $last_space > 0) {
            $candidate_line = rtrim(substr($line, 0, $last_space));
            if ($candidate_line !== '') {
                $line = $candidate_line;
            }
        }

        $wrapped_lines[] = rtrim($line);
        $remaining = ltrim(substr($remaining, strlen($line)));
    }

    if (empty($wrapped_lines)) {
        $wrapped_lines[] = $string;
    }

    return $wrapped_lines;
}

function slow_moving_pdf_fit_text_to_width($pdf, $string, $max_wid, $fsize, $add_ellipsis = false)
{
    $string = (string)$string;
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
    return rtrim($xxstr);
}

function slow_moving_pdf_calculate_row_height($line_count, $base_height = 22, $line_spacing = 10)
{
    return $base_height + (max(1, $line_count) - 1) * $line_spacing;
}

function slow_moving_pdf_normalize_item_text($string)
{
    $string = trim((string)$string);
    if ($string === '') {
        return '';
    }

    $search = array('Ã¢â‚¬Å"', 'Ã¢â‚¬Â', 'Ã¢â‚¬Ëœ', 'Ã¢â‚¬â„¢', 'Ã¢â‚¬â€œ', 'Ã¢â‚¬â€', 'Ã‚', 'â€œ', 'â€', 'â€˜', 'â€™', 'â€"', 'â€"');
    $replace = array('"', '"', "'", "'", '-', '-', '', '"', '"', "'", "'", '-', '-');
    $string = str_replace($search, $replace, $string);
    $string = preg_replace('/\s+/', ' ', $string);

    return trim($string);
}

function slow_moving_pdf_export_xlsx($rows, $date_printed)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Slow Moving Items');

    $sheet->mergeCells('A1:D1');
    $sheet->setCellValue('A1', 'Slow Moving Report');
    $sheet->setCellValue('A2', 'Date Printed : ' . $date_printed);

    $header_row = 4;
    $sheet->fromArray(array(
        'Item',
        'Last Sale',
        'Latest Purchase',
        'Current Stock'
    ), null, 'A' . $header_row);

    $row_num = $header_row + 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $row_num, slow_moving_pdf_normalize_item_text($row['itmdsc']));
        $sheet->setCellValue('B' . $row_num, $row['last_sale_display']);
        $sheet->setCellValue('C' . $row_num, $row['latest_purchase_display']);
        $sheet->setCellValue('D' . $row_num, (float)$row['current_stock']);
        $row_num++;
    }

    $sheet->getStyle('A1:A2')->getFont()->setBold(true);
    $sheet->getStyle('A' . $header_row . ':D' . $header_row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $header_row . ':D' . $header_row)->getAlignment()->setWrapText(true);
    $sheet->getStyle('A' . $header_row . ':D' . ($row_num - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle('D' . ($header_row + 1) . ':D' . ($row_num - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('D' . ($header_row + 1) . ':D' . ($row_num - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('B' . ($header_row + 1) . ':C' . ($row_num - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A' . ($header_row + 1) . ':A' . ($row_num - 1))->getAlignment()->setWrapText(true);

    $sheet->getColumnDimension('A')->setWidth(60);
    $sheet->getColumnDimension('B')->setWidth(18);
    $sheet->getColumnDimension('C')->setWidth(18);
    $sheet->getColumnDimension('D')->setWidth(18);
    $sheet->freezePane('A5');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'slow_moving_report_' . date('Ymd_His') . '.xlsx';

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

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     slow_moving_pdf_deny_access();
// }

if (
    !isset($_SESSION['userdesc']) ||
    !isset($_SESSION['password']) ||
    !isset($_SESSION['usercode'])
) {
   // slow_moving_pdf_deny_access();
}

try {
    if (!slow_moving_pdf_has_export_access($link, $_SESSION['usercode'], $_SESSION['userdesc'], 'slow_moving_pdf.php')) {
        //slow_moving_pdf_deny_access();
    }

    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] === 'tab');
    $export_label = $is_tab_export ? 'XLSX' : 'PDF';

    $date_printed = date("F j, Y h:i:s A");

    // Log export activity
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
    $xprog_module_log = 'SLOW MOVING REPORT';
    $xactivity_log = $is_tab_export ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported " . $export_label . " from Slow Moving Report";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    // Stock subquery (from gen_dashboard_data.php)
    $stock_subquery = "
        SELECT
            itmcde,
            COALESCE(SUM(stkqty), 0) AS current_stock
        FROM tranfile2
        GROUP BY itmcde
    ";

    // Slow moving SQL query (from gen_dashboard_data.php, without LIMIT)
    $slow_moving_sql = "
        SELECT
            itemfile.itmdsc,
            stock.current_stock,
            last_sales.last_sale_date,
            last_purchases.last_purchase_date
        FROM itemfile
        INNER JOIN (
            {$stock_subquery}
        ) AS stock
            ON stock.itmcde = itemfile.itmcde
           AND stock.current_stock > 0
        INNER JOIN (
            SELECT
                tranfile2.itmcde,
                MAX(tranfile1.trndte) AS last_purchase_date
            FROM tranfile1
            INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            WHERE tranfile1.trncde = 'PUR'
            GROUP BY tranfile2.itmcde
        ) AS last_purchases
            ON last_purchases.itmcde = itemfile.itmcde
        LEFT JOIN (
            SELECT
                tranfile2.itmcde,
                MAX(tranfile1.trndte) AS last_sale_date
            FROM tranfile1
            INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            WHERE tranfile1.trncde = 'SAL'
            GROUP BY tranfile2.itmcde
        ) AS last_sales
            ON last_sales.itmcde = itemfile.itmcde
        ORDER BY
            CASE WHEN last_sales.last_sale_date IS NULL THEN 0 ELSE 1 END ASC,
            last_sales.last_sale_date ASC,
            stock.current_stock DESC
    ";

    $slow_moving_stmt = $link->prepare($slow_moving_sql);
    $slow_moving_stmt->execute();
    $slow_moving_items = $slow_moving_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process rows and format dates
    $report_rows = array();
    foreach ($slow_moving_items as $item) {
        $report_rows[] = array(
            'itmdsc' => $item['itmdsc'],
            'current_stock' => (float)$item['current_stock'],
            'last_sale_display' => slow_moving_pdf_format_date($item['last_sale_date'], 'No sales yet'),
            'latest_purchase_display' => slow_moving_pdf_format_date($item['last_purchase_date'], 'No purchase yet')
        );
    }

    // Export to XLSX if requested
    if ($is_tab_export) {
        slow_moving_pdf_export_xlsx($report_rows, $date_printed);
        exit;
    }

    // PDF export
    ob_start();

    $pdf = new Cezpdf('Letter', 'portrait');
    slow_moving_pdf_use_font($pdf, false);
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM} of {TOTALPAGENUM}', 1);

    $page_y = slow_moving_pdf_render_page_header($pdf, $date_printed);
    $table_x = 30;
    $item_width = 250;
    $last_sale_width = 100;
    $latest_purchase_width = 100;
    $current_stock_width = 102;
    $row_height = 22;
    $bottom_limit = 55;

    $page_y = slow_moving_pdf_draw_table_header($pdf, $table_x, $page_y, $item_width, $last_sale_width, $latest_purchase_width, $current_stock_width, $row_height);

    if (empty($report_rows)) {
        $page_y -= 10;
        slow_moving_pdf_add_text_left($pdf, $table_x + 2, $page_y, 'No slow moving items found.', 11, false);
    } else {
        $item_font_size = 9;
        $item_wrap_width = $item_width - 10; // Leave some padding

        foreach ($report_rows as $row) {
            // Normalize and wrap item text
            $item_text = slow_moving_pdf_normalize_item_text($row['itmdsc']);
            $item_lines = slow_moving_pdf_wrap_text($pdf, $item_text, $item_wrap_width, $item_font_size);
            $line_count = count($item_lines);

            // Calculate dynamic row height based on line count
            $dynamic_row_height = slow_moving_pdf_calculate_row_height($line_count, $row_height, 10);

            // Check if we need a new page
            if (($page_y - $dynamic_row_height) < $bottom_limit) {
                $pdf->ezNewPage();
                $page_y = slow_moving_pdf_render_page_header($pdf, $date_printed);
                $page_y = slow_moving_pdf_draw_table_header($pdf, $table_x, $page_y, $item_width, $last_sale_width, $latest_purchase_width, $current_stock_width, $row_height);
            }

            $page_y = slow_moving_pdf_draw_detail_row(
                $pdf,
                $table_x,
                $page_y,
                $item_width,
                $last_sale_width,
                $latest_purchase_width,
                $current_stock_width,
                $item_lines,
                $row['last_sale_display'],
                $row['latest_purchase_display'],
                $row['current_stock']
            );
        }
    }

    $pdf->ezStream();
    ob_end_flush();
} catch (Exception $e) {
    error_log('Unable to generate slow moving report.');
    slow_moving_pdf_fail_request('Unable to generate report.');
}

?>