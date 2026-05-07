<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');

    ob_start();

    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');
    $xreport_title = "List of items";

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $xfilter = '';
    $xfilter2 = '';

    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to']))){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }

    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
    }

    if($is_tab_export){
        output_inventory_adjustment_xls($link, $xfilter, $xfilter2);
        exit;
    }

    $pdf = new Cezpdf("Letter", 'landscape');
    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);

    $page_left = 25;
    $page_right = 770;
    $page_bottom = 60;
    $xtop = 580;
    $xleft = $page_left;
    $row_line_gap = 10;

    $layout = array(
        'page_left' => $page_left,
        'page_right' => $page_right,
        'page_bottom' => $page_bottom,
        'trndte' => array('x' => 25, 'width' => 60, 'align' => 'left', 'header' => array('Tran.', 'Date')),
        'ordernum' => array('x' => 100, 'width' => 120, 'align' => 'left', 'header' => array('Order', 'Num.')),
        'qty' => array('x' => 290, 'width' => 55, 'align' => 'right', 'header' => array('Qty')),
        'uom' => array('x' => 305, 'width' => 45, 'align' => 'left', 'header' => array('UOM')),
        'warehouse' => array('x' => 365, 'width' => 170, 'align' => 'left', 'header' => array('Warehouse')),
        'unitprice' => array('x' => 660, 'width' => 70, 'align' => 'right', 'header' => array('Unit', 'Price')),
        'total' => array('x' => 755, 'width' => 70, 'align' => 'right', 'header' => array('Total'))
    );

    $progname_hidden = '';
    if($_POST['trncde_hidden'] == 'SAL'){
        $progname_hidden = "Sales";
    }
    else if($_POST['trncde_hidden'] == 'SRT'){
        $progname_hidden = "Sales Return";
    }
    else if($_POST['trncde_hidden'] == 'PUR'){
        $progname_hidden = "Purchases";
    }
    else if($_POST['trncde_hidden'] == 'ADJ'){
        $progname_hidden = "Inventory Adjustments";
    }

    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, "<b>".$progname_hidden."</b>", 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, "<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left');
    $xtop -= 15;
    $pdf->setLineStyle(.5);
    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    $select_db = "SELECT * FROM itemfile WHERE true ".str_replace('itemfile.', '', $xfilter);
    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;

    while($rs_main = $stmt_main->fetch(PDO::FETCH_ASSOC)){
        $detail_sql = "SELECT tranfile2.*, tranfile1.trndte, tranfile1.ordernum,
            itemunitmeasurefile.unmdsc AS uom_description,
            warehouse.warehouse_name,
            warehouse_floor.floor_no
            FROM tranfile2
            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
            LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            WHERE tranfile2.itmcde = ? ".$xfilter2."
            AND tranfile1.trncde = ?
            ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_main2 = $link->prepare($detail_sql);
        $stmt_main2->execute(array($rs_main['itmcde'], $_POST['trncde_hidden']));
        $detail_rows = $stmt_main2->fetchAll(PDO::FETCH_ASSOC);

        if(empty($detail_rows)){
            continue;
        }

        $item_desc_header = normalize_item_text($rs_main['itmdsc']);
        render_item_section_header($pdf, $xtop, $item_desc_header, $layout);
        render_detail_header($pdf, $xtop, $layout);

        $subtotal = 0;
        foreach($detail_rows as $rs_main2){
            $ordernum_lines = wrap_str_lines(normalize_item_text(isset($rs_main2['ordernum']) ? $rs_main2['ordernum'] : ''), $layout['ordernum']['width'], 9);
            $uom_display = isset($rs_main2['uom_description']) ? normalize_item_text($rs_main2['uom_description']) : '';
            $uom_lines = wrap_str_lines($uom_display, $layout['uom']['width'], 9);
            $warehouse_display = build_warehouse_display(
                isset($rs_main2['warehouse_name']) ? $rs_main2['warehouse_name'] : '',
                isset($rs_main2['floor_no']) ? $rs_main2['floor_no'] : ''
            );
            $warehouse_lines = wrap_str_lines(normalize_item_text($warehouse_display), $layout['warehouse']['width'], 9);

            $line_count = max(count($ordernum_lines), count($uom_lines), count($warehouse_lines));
            $row_height = 15 + ((max(1, $line_count) - 1) * $row_line_gap);

            if(($xtop - $row_height) <= $page_bottom){
                $pdf->ezNewPage();
                $xtop = 530;
                render_item_section_header($pdf, $xtop, $item_desc_header, $layout);
                render_detail_header($pdf, $xtop, $layout);
            }

            $row_y = $xtop;
            $subtotal += (float)$rs_main2["extprc"];
            $grand_total += (float)$rs_main2["extprc"];

            $trndte_display = '';
            if(isset($rs_main2["trndte"]) && !empty($rs_main2["trndte"])){
                $trndte_display = date("m/d/Y", strtotime($rs_main2["trndte"]));
            }

            $pdf->ezPlaceData($layout['trndte']['x'], $row_y, $trndte_display, 9, $layout['trndte']['align']);

            foreach($ordernum_lines as $idx => $line){
                $pdf->ezPlaceData($layout['ordernum']['x'], $row_y - ($idx * $row_line_gap), $line, 9, $layout['ordernum']['align']);
            }

            $pdf->ezPlaceData($layout['qty']['x'], $row_y, number_format((float)$rs_main2["itmqty"]), 9, $layout['qty']['align']);

            foreach($uom_lines as $idx => $line){
                $pdf->ezPlaceData($layout['uom']['x'], $row_y - ($idx * $row_line_gap), $line, 9, $layout['uom']['align']);
            }

            foreach($warehouse_lines as $idx => $line){
                $pdf->ezPlaceData($layout['warehouse']['x'], $row_y - ($idx * $row_line_gap), $line, 9, $layout['warehouse']['align']);
            }

            $pdf->ezPlaceData($layout['unitprice']['x'], $row_y, number_format((float)$rs_main2["untprc"], 2), 9, $layout['unitprice']['align']);
            $pdf->ezPlaceData($layout['total']['x'], $row_y, number_format((float)$rs_main2["extprc"], 2), 9, $layout['total']['align']);

            $xtop -= $row_height;
        }

        if(($xtop - 20) <= $page_bottom){
            $pdf->ezNewPage();
            $xtop = 530;
            render_item_section_header($pdf, $xtop, $item_desc_header, $layout);
            render_detail_header($pdf, $xtop, $layout);
        }

        $pdf->line($page_left, $xtop, $page_right, $xtop);
        $pdf->ezPlaceData($layout['warehouse']['x'], $xtop - 9, "<b>Subtotal:</b>", 9, 'left');
        $pdf->ezPlaceData($layout['total']['x'], $xtop - 9, "<b>".number_format($subtotal, 2)."</b>", 9, 'right');
        $xtop -= 20;

        if($xtop <= $page_bottom){
            $pdf->ezNewPage();
            $xtop = 530;
        }
    }

    $pdf->line($page_left, $xtop - 10, $page_right, $xtop - 10);
    $pdf->ezPlaceData($layout['warehouse']['x'], $xtop - 18, "<b>Grand total:</b>", 9, 'left');
    $pdf->ezPlaceData($layout['total']['x'], $xtop - 18, "<b>".number_format($grand_total, 2)."</b>", 9, 'right');
    $pdf->line($page_left, $xtop - 10, $page_right, $xtop - 10);
    $pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
    $pdf->ezStream();
    ob_end_flush();

    function output_inventory_adjustment_xls($link, $xfilter, $xfilter2)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xline = "\r\n";
        $delimiter = chr(9);
        $xchunk = '';
        $grandtotal = 0;

        $headers = array(
            'Doc. Num.',
            'Item',
            'Tran. Date',
            'Order Num.',
            'Qty',
            'UOM',
            'Warehouse',
            'Unit Price',
            'Total'
        );

        foreach($headers as $header){
            $xchunk .= $header.$delimiter;
        }
        $xchunk .= $xline;

        $select_db = "SELECT tranfile2.docnum as tranfile2_docnum,
            tranfile1.trndte as tranfile1_trndte,
            tranfile1.ordernum as tranfile1_ordernum,
            tranfile2.itmqty as tranfile2_itmqty,
            tranfile2.untprc as tranfile2_untprc,
            tranfile2.extprc as tranfile2_extprc,
            itemfile.itmdsc as itemfile_itmdsc,
            itemunitmeasurefile.unmdsc as itemunitmeasurefile_unmdsc,
            warehouse.warehouse_name,
            warehouse_floor.floor_no
            FROM tranfile2
            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
            LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
            LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            WHERE true AND tranfile1.trncde='".$_POST['trncde_hidden']."' ".$xfilter.$xfilter2."
            ORDER BY itemfile.itmdsc ASC, tranfile1.trndte ASC, tranfile2.recid ASC";

        $stmt_main = $link->prepare($select_db);
        $stmt_main->execute();

        while($rs_main = $stmt_main->fetch(PDO::FETCH_ASSOC)){
            $trndte_display = '';
            if(isset($rs_main["tranfile1_trndte"]) && !empty($rs_main["tranfile1_trndte"])){
                $trndte_display = date("m/d/Y", strtotime($rs_main["tranfile1_trndte"]));
            }

            $warehouse_display = build_warehouse_display(
                isset($rs_main['warehouse_name']) ? $rs_main['warehouse_name'] : '',
                isset($rs_main['floor_no']) ? $rs_main['floor_no'] : ''
            );

            $grandtotal += (float)$rs_main['tranfile2_extprc'];

            $row = array(
                isset($rs_main['tranfile2_docnum']) ? $rs_main['tranfile2_docnum'] : '',
                xls_safe_text(normalize_item_text(isset($rs_main['itemfile_itmdsc']) ? $rs_main['itemfile_itmdsc'] : '')),
                $trndte_display,
                xls_safe_text(normalize_item_text(isset($rs_main['tranfile1_ordernum']) ? $rs_main['tranfile1_ordernum'] : '')),
                isset($rs_main['tranfile2_itmqty']) ? (string)$rs_main['tranfile2_itmqty'] : '',
                xls_safe_text(normalize_item_text(isset($rs_main['itemunitmeasurefile_unmdsc']) ? $rs_main['itemunitmeasurefile_unmdsc'] : '')),
                xls_safe_text(normalize_item_text($warehouse_display)),
                number_format((float)$rs_main['tranfile2_untprc'], 2),
                number_format((float)$rs_main['tranfile2_extprc'], 2)
            );

            foreach($row as $cell){
                $xchunk .= $cell.$delimiter;
            }
            $xchunk .= $xline;
        }

        $total_row = array('', '', '', '', '', '', 'Grand total:', '', number_format($grandtotal, 2));
        foreach($total_row as $cell){
            $xchunk .= $cell.$delimiter;
        }
        $xchunk .= $xline;

        $xfilename = "inventory_item.xls";

        header("Content-Disposition: attachment; filename=$xfilename");
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Transfer-Encoding: binary");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $xchunk;
    }

    function render_item_section_header($pdf, &$xtop, $item_desc_header, $layout)
    {
        $item_lines = wrap_str_lines($item_desc_header, 700, 10);
        $pdf->ezPlaceData($layout['page_left'], $xtop - 9, "<b>Item:</b>", 10, 'left');

        foreach($item_lines as $idx => $line){
            $pdf->ezPlaceData(55, $xtop - 9 - ($idx * 12), $line, 10, 'left');
        }

        $item_header_height = max(12, count($item_lines) * 12);
        $pdf->line($layout['page_left'], $xtop - $item_header_height, $layout['page_right'], $xtop - $item_header_height);
        $xtop -= $item_header_height;
    }

    function render_detail_header($pdf, &$xtop, $layout)
    {
        $header_top_y = $xtop - 9;
        $line_gap = 9;
        $max_lines = 1;

        foreach(array('trndte', 'ordernum', 'qty', 'uom', 'warehouse', 'unitprice', 'total') as $key){
            $header_lines = $layout[$key]['header'];
            $max_lines = max($max_lines, count($header_lines));
            foreach($header_lines as $idx => $line){
                $pdf->ezPlaceData($layout[$key]['x'], $header_top_y - ($idx * $line_gap), "<b>".$line."</b>", 9, $layout[$key]['align']);
            }
        }

        $header_height = 14 + (($max_lines - 1) * $line_gap);
        $pdf->line($layout['page_left'], $xtop - $header_height, $layout['page_right'], $xtop - $header_height);
        $xtop -= ($header_height + 11);
    }

    function build_warehouse_display($warehouse_name, $floor_no)
    {
        $warehouse_name = trim((string)$warehouse_name);
        $floor_no = trim((string)$floor_no);
        $parts = array();

        if($warehouse_name !== ''){
            $parts[] = $warehouse_name;
        }

        if($floor_no !== ''){
            $parts[] = $floor_no.' floor';
        }

        return trim(implode(' ', $parts));
    }

    function normalize_item_text($string)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        $search = array('Ã¢â‚¬Å“', 'Ã¢â‚¬Â', 'Ã¢â‚¬Ëœ', 'Ã¢â‚¬â„¢', 'Ã¢â‚¬â€œ', 'Ã¢â‚¬â€', 'Ã‚', 'â€œ', 'â€', 'â€˜', 'â€™', 'â€“', 'â€”');
        $replace = array('"', '"', "'", "'", '-', '-', '', '"', '"', "'", "'", '-', '-');
        $string = str_replace($search, $replace, $string);
        $string = preg_replace('/\s+/', ' ', $string);

        return trim($string);
    }

    function xls_safe_text($string)
    {
        $string = (string)$string;

        if(function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        if(function_exists('iconv')){
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if($converted !== false && $converted !== ''){
                $string = $converted;
            }
            else{
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        }
        else{
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return trim($string);
    }

    function wrap_str_lines($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = trim((string)$string);
        if($string === ''){
            return array('');
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

            $line = fit_text_to_width($remaining, $max_wid, $fsize);
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

    function fit_text_to_width($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $limit_wid = max(1, $max_wid);
        $xarr_str = str_split($string);
        $xxstr = '';

        foreach($xarr_str as $value){
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr.$value);
            if($xstr_wid > $limit_wid){
                break;
            }
            $xxstr .= $value;
        }

        return rtrim($xxstr);
    }
?>
