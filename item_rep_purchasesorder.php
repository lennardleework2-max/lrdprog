<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    ob_start();

    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');
    if($is_tab_export){
        $pdf = new tab_ezpdf('Letter', 'landscape');
    }else{
        $pdf = new Cezpdf('Letter', 'landscape');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
    $pdf->setLineStyle(.5);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $line_left = 25;
    $line_right = 770;
    $xtop = 580;
    $xleft = 25;

    $col_trndate = 25;
    $col_ordernum = 95;
    $col_supplier = 180;
    $col_orderedby = 325;
    $col_qty = 560;
    $col_uom = 575;
    $col_unitprice = 665;
    $col_total = 760;

    $report_user = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
    $report_title = "Purchases Order by Item";

    if(!$is_tab_export){
        $header_top = $xtop;
        $xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $header_top, "<b>".$report_title."</b>", 15, 'left');
        $header_top -= 15;
        $pdf->ezPlaceData($xleft, $header_top, "<b>Pdf Report by: ".$report_user."</b>", 9, 'left');
        $header_top -= 15;
        $pdf->ezPlaceData($xleft, $header_top, 'Date Printed : '.$date_printed, 10, 'left');
        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($xheader, 'all');
        $xtop = 530;
    }else{
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text($report_title), 15, 'left');
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Pdf Report by: '.$report_user), 9, 'left');
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Date Printed : '.$date_printed), 10, 'left');
        $xtop -= 20;
    }

    $date_from_sql = '2000-01-01';
    $date_to_sql = date('Y-m-d');
    if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $date_from_sql = date('Y-m-d', strtotime($_POST['date_from']));
    }
    if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $date_to_sql = date('Y-m-d', strtotime($_POST['date_to']));
    }

    $item_code = isset($_POST['item']) ? trim((string)$_POST['item']) : '';
    $item_desc_display = '';
    if($item_code !== ''){
        $stmt_item_display = $link->prepare("SELECT itmdsc FROM itemfile WHERE itmcde = ? LIMIT 1");
        $stmt_item_display->execute(array($item_code));
        $rs_item_display = $stmt_item_display->fetch(PDO::FETCH_ASSOC);
        if($rs_item_display && isset($rs_item_display['itmdsc'])){
            $item_desc_display = $rs_item_display['itmdsc'];
        }
    }

    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>FILTER:</b>"), 10, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Date From:</b>"), 10, 'left');
    $pdf->ezPlaceData($xleft + 60, $xtop, xls_safe_text(date('m/d/Y', strtotime($date_from_sql))), 10, 'left');
    $pdf->ezPlaceData($xleft + 120, $xtop, xls_safe_text("<b>Date To:</b>"), 10, 'left');
    $pdf->ezPlaceData($xleft + 170, $xtop, xls_safe_text(date('m/d/Y', strtotime($date_to_sql))), 10, 'left');
    $pdf->ezPlaceData($xleft + 230, $xtop, xls_safe_text("<b>Item:</b>"), 10, 'left');
    $pdf->ezPlaceData($xleft + 265, $xtop, xls_safe_text(normalize_report_text($item_desc_display)), 10, 'left');
    $xtop -= 25;

    $item_sql = "SELECT itemfile.itmcde, itemfile.itmdsc
        FROM itemfile
        WHERE true";
    $item_params = array();
    if($item_code !== ''){
        $item_sql .= " AND itemfile.itmcde = ?";
        $item_params[] = $item_code;
    }
    $item_sql .= " AND EXISTS (
            SELECT 1
            FROM purchasesorderfile2
            INNER JOIN purchasesorderfile1 ON purchasesorderfile1.docnum = purchasesorderfile2.docnum
            WHERE purchasesorderfile2.itmcde = itemfile.itmcde
            AND purchasesorderfile1.trndte >= ?
            AND purchasesorderfile1.trndte <= ?
        )
        ORDER BY itemfile.itmdsc ASC";
    $item_params[] = $date_from_sql;
    $item_params[] = $date_to_sql;

    $stmt_items = $link->prepare($item_sql);
    $stmt_items->execute($item_params);
    $item_rows = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $detail_sql = "SELECT purchasesorderfile2.docnum,
            purchasesorderfile2.itmqty,
            purchasesorderfile2.untprc,
            purchasesorderfile2.extprc,
            purchasesorderfile2.unmcde,
            purchasesorderfile2.recid,
            purchasesorderfile1.trndte,
            purchasesorderfile1.ordernum,
            purchasesorderfile1.orderby,
            supplierfile.suppdsc,
            itemunitmeasurefile.unmdsc AS uom_desc
        FROM purchasesorderfile2
        INNER JOIN purchasesorderfile1 ON purchasesorderfile2.docnum = purchasesorderfile1.docnum
        LEFT JOIN supplierfile ON purchasesorderfile1.suppcde = supplierfile.suppcde
        LEFT JOIN itemunitmeasurefile ON purchasesorderfile2.unmcde = itemunitmeasurefile.unmcde
        WHERE purchasesorderfile2.itmcde = ?
        AND purchasesorderfile1.trndte >= ?
        AND purchasesorderfile1.trndte <= ?
        ORDER BY purchasesorderfile1.trndte ASC, purchasesorderfile2.recid ASC";
    $stmt_details = $link->prepare($detail_sql);

    $grand_total = 0;
    foreach($item_rows as $item_row){
        $stmt_details->execute(array($item_row['itmcde'], $date_from_sql, $date_to_sql));
        $detail_rows = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
        if(empty($detail_rows)){
            continue;
        }

        if(($xtop - 70) <= 60){
            $pdf->ezNewPage();
            $xtop = 530;
        }

        render_item_section_header($item_row['itmdsc'], $xtop);

        $subtotal = 0;
        $subtotal_itmqty = 0;
        foreach($detail_rows as $detail_row){
            $display_trndte = '';
            if(!empty($detail_row['trndte'])){
                $display_trndte = date("m/d/Y", strtotime($detail_row['trndte']));
            }

            $ordernum_lines = wrap_text_to_lines(normalize_report_text($detail_row['ordernum']), 75, 9);
            $supplier_lines = wrap_text_to_lines(normalize_report_text($detail_row['suppdsc']), 135, 9);
            $orderedby_lines = wrap_text_to_lines(normalize_report_text($detail_row['orderby']), 155, 9);
            $uom_lines = wrap_text_to_lines(normalize_report_text($detail_row['uom_desc']), 55, 9);

            $line_count = max(
                count($ordernum_lines),
                count($supplier_lines),
                count($orderedby_lines),
                count($uom_lines)
            );
            $row_height = 15 + ((max(1, $line_count) - 1) * 10);

            if(($xtop - $row_height) <= 60){
                $pdf->ezNewPage();
                $xtop = 530;
                render_item_section_header($item_row['itmdsc'], $xtop);
            }

            $row_y = $xtop - 2;
            $pdf->ezPlaceData($col_trndate, $row_y, xls_safe_text($display_trndte), 9, 'left');
            foreach($ordernum_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_ordernum, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }
            foreach($supplier_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_supplier, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }
            foreach($orderedby_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_orderedby, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }
            foreach($uom_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_uom, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }

            $pdf->ezPlaceData($col_qty, $row_y, format_report_qty($detail_row['itmqty']), 9, 'right');
            $pdf->ezPlaceData($col_unitprice, $row_y, number_format((float)$detail_row['untprc'], 2), 9, 'right');
            $pdf->ezPlaceData($col_total, $row_y, number_format((float)$detail_row['extprc'], 2), 9, 'right');

            $subtotal += (float)$detail_row['extprc'];
            $subtotal_itmqty += (float)$detail_row['itmqty'];
            $grand_total += (float)$detail_row['extprc'];
            $xtop -= $row_height;
        }

        $subtotal_weighted = 0;
        if($subtotal_itmqty != 0){
            $subtotal_weighted = $subtotal / $subtotal_itmqty;
        }

        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 10;
        pad_tab_columns(array($col_trndate, $col_ordernum, $col_supplier), $xtop, 9);
        $pdf->ezPlaceData($col_orderedby, $xtop, xls_safe_text("<b>Weighted Average/Subtotal:</b>"), 9, 'left');
        $pdf->ezPlaceData($col_qty, $xtop, format_report_qty($subtotal_itmqty), 9, 'right');
        $pdf->ezPlaceData($col_uom, $xtop, '', 9, 'left');
        $pdf->ezPlaceData($col_unitprice, $xtop, "<b>".number_format($subtotal_weighted, 2)."</b>", 9, 'right');
        $pdf->ezPlaceData($col_total, $xtop, "<b>".number_format($subtotal, 2)."</b>", 9, 'right');
        $xtop -= 10;
        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 15;
    }

    if($xtop <= 60){
        $pdf->ezNewPage();
        $xtop = 515;
    }

    $pdf->line($line_left, $xtop, $line_right, $xtop);
    $xtop -= 10;
    pad_tab_columns(array($col_trndate, $col_ordernum, $col_supplier, $col_orderedby, $col_qty, $col_uom), $xtop, 9);
    $pdf->ezPlaceData($col_unitprice, $xtop, xls_safe_text("<b>Grand Total:</b>"), 9, 'left');
    $pdf->ezPlaceData($col_total, $xtop, "<b>".number_format($grand_total, 2)."</b>", 9, 'right');
    $xtop -= 10;
    $pdf->line($line_left, $xtop, $line_right, $xtop);

    if(!$is_tab_export){
        $pdf->addText(30, 15, 8, "Date Printed : ".date("F j, Y, g:i A"), 0, 1);
    }
    $pdf->ezStream();
    ob_end_flush();

    function render_item_section_header($item_desc, &$xtop)
    {
        global $pdf, $line_left, $line_right;
        global $col_trndate, $col_ordernum, $col_supplier, $col_orderedby, $col_qty, $col_uom, $col_unitprice, $col_total;

        $item_desc = normalize_report_text($item_desc);
        $pdf->ezPlaceData(25, $xtop - 9, xls_safe_text("<b>Item:</b>"), 10, 'left');

        $item_lines = wrap_text_to_lines($item_desc, 700, 10);
        foreach($item_lines as $line_index => $line_text){
            $pdf->ezPlaceData(55, $xtop - 9 - ($line_index * 12), xls_safe_text($line_text), 10, 'left');
        }

        $header_offset = max(12, count($item_lines) * 12);
        $pdf->line($line_left, $xtop - $header_offset, $line_right, $xtop - $header_offset);
        $xtop -= $header_offset;

        if(get_class($pdf) == 'tab_ezpdf'){
            $pdf->ezPlaceData($col_trndate, $xtop - 9, xls_safe_text("<b>Tran. Date</b>"), 9, 'left');
            $pdf->ezPlaceData($col_ordernum, $xtop - 9, xls_safe_text("<b>Order Num.</b>"), 9, 'left');
            $pdf->ezPlaceData($col_supplier, $xtop - 9, xls_safe_text("<b>Supplier</b>"), 9, 'left');
            $pdf->ezPlaceData($col_orderedby, $xtop - 9, xls_safe_text("<b>Ordered By</b>"), 9, 'left');
            $pdf->ezPlaceData($col_qty, $xtop - 9, xls_safe_text("<b>Quantity</b>"), 9, 'right');
            $pdf->ezPlaceData($col_uom, $xtop - 9, xls_safe_text("<b>UOM</b>"), 9, 'left');
            $pdf->ezPlaceData($col_unitprice, $xtop - 9, xls_safe_text("<b>Unit Price</b>"), 9, 'right');
            $pdf->ezPlaceData($col_total, $xtop - 9, xls_safe_text("<b>Extended Price</b>"), 9, 'right');
            $pdf->line($line_left, $xtop - 12, $line_right, $xtop - 12);
            $xtop -= 23;
            return;
        }

        place_multiline_text($col_trndate, $xtop - 9, array("<b>Tran.</b>", "<b>Date</b>"), 8, 'left', 9);
        place_multiline_text($col_ordernum, $xtop - 9, array("<b>Order</b>", "<b>Num.</b>"), 8, 'left', 9);
        $pdf->ezPlaceData($col_supplier, $xtop - 9, "<b>Supplier</b>", 9, 'left');
        place_multiline_text($col_orderedby, $xtop - 9, array("<b>Ordered</b>", "<b>By</b>"), 8, 'left', 9);
        $pdf->ezPlaceData($col_qty, $xtop - 9, "<b>Quantity</b>", 9, 'right');
        $pdf->ezPlaceData($col_uom, $xtop - 9, "<b>UOM</b>", 9, 'left');
        place_multiline_text($col_unitprice, $xtop - 9, array("<b>Unit</b>", "<b>Price</b>"), 8, 'right', 9);
        place_multiline_text($col_total, $xtop - 9, array("<b>Extended</b>", "<b>Price</b>"), 8, 'right', 9);
        $pdf->line($line_left, $xtop - 20, $line_right, $xtop - 20);
        $xtop -= 29;
    }

    function normalize_report_text($string)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        $search = array('ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ', 'ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â', 'ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“', 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢', 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“', 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â', 'Ãƒâ€š', 'Ã¢â‚¬Å“', 'Ã¢â‚¬Â', 'Ã¢â‚¬Ëœ', 'Ã¢â‚¬â„¢', 'Ã¢â‚¬â€œ', 'Ã¢â‚¬â€');
        $replace = array('"', '"', "'", "'", '-', '-', '', '"', '"', "'", "'", '-', '-');
        $string = str_replace($search, $replace, $string);
        $string = preg_replace('/\s+/', ' ', $string);

        return trim($string);
    }

    function xls_safe_text($string)
    {
        global $pdf;

        $string = (string)$string;
        if(get_class($pdf) != 'tab_ezpdf'){
            return $string;
        }

        $string = normalize_report_text($string);
        if(function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        if(function_exists('iconv')){
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if($converted !== false && $converted !== ''){
                $string = $converted;
            }else{
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        }else{
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return trim($string);
    }

    function wrap_text_to_lines($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = trim((string)$string);
        if($string === ''){
            return array('');
        }

        if(get_class($pdf) == 'tab_ezpdf'){
            return array($string);
        }

        $max_wid -= 5;
        if($pdf->getTextWidth($fsize, $string) <= $max_wid){
            return array($string);
        }

        $wrapped_lines = array();
        $remaining = $string;

        while($remaining !== ''){
            if($pdf->getTextWidth($fsize, $remaining) <= $max_wid){
                $wrapped_lines[] = $remaining;
                break;
            }

            $line = fit_text_width($remaining, $max_wid, $fsize);
            if($line === ''){
                $line = substr($remaining, 0, 1);
            }

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

        return $wrapped_lines;
    }

    function fit_text_width($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr = '';
        foreach($xarr_str as $value){
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr.$value);
            if($xstr_wid > $max_wid){
                break;
            }
            $xxstr .= $value;
        }

        return rtrim($xxstr);
    }

    function place_multiline_text($xpos, $ypos, $lines, $font_size, $align = 'left', $line_gap = 10)
    {
        global $pdf;

        foreach($lines as $index => $line_text){
            $pdf->ezPlaceData($xpos, $ypos - ($index * $line_gap), $line_text, $font_size, $align);
        }
    }

    function pad_tab_columns($positions, $ypos, $font_size = 9)
    {
        global $pdf;

        if(get_class($pdf) !== 'tab_ezpdf'){
            return;
        }

        foreach($positions as $xpos){
            $pdf->ezPlaceData($xpos, $ypos, '', $font_size, 'left');
        }
    }

    function format_report_qty($qty)
    {
        return number_format((float)$qty);
    }
?>
