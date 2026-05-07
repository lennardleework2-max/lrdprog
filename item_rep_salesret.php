<?php
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
    use PhpOffice\PhpSpreadsheet\Writer\Xls;

    ob_start();

    $xreport_title = "List of items";

    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    if($is_tab_export){
        $pdf = new tab_ezpdf('Letter', 'landscape');
    } else {
        $pdf = new Cezpdf("Letter", 'landscape');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $xtop = 580;
    $xleft = 25;
    $line_left = 25;
    $line_right = 770;

    // Column positions for new layout
    $col_trndate = 25;
    $col_ordernum = 90;
    $col_shopname = 175;
    $col_orderedby = 280;
    $col_qty = 405;
    $col_uom = 415;
    $col_warehouse = 470;
    $col_unitprice = 620;
    $col_extprice = 760;

    $progname_hidden = '';
    if($_POST['trncde_hidden'] == 'SAL'){
        $progname_hidden = "Sales";
    } else if($_POST['trncde_hidden'] == 'SRT'){
        $progname_hidden = "Sales Return";
    } else if($_POST['trncde_hidden'] == 'PUR'){
        $progname_hidden = "Purchases";
    }

    $report_user = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';

    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>".$progname_hidden."</b>"), 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Pdf Report by: ".$report_user." (Summarized)</b>"), 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Date Printed : '.$date_printed), 10, 'left');
    $xtop -= 15;
    $pdf->setLineStyle(.5);
    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    $xfilter = '';
    $xfilter2 = '';

    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
       (isset($_POST['date_to']) && !empty($_POST['date_to']))){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    } else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    } else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
    }

    if($is_tab_export){
        $report_groups = build_salesret_report_groups($link, $xfilter, $xfilter2, $_POST['trncde_hidden']);
        export_salesret_xls($report_groups, $progname_hidden, $report_user, $date_printed);
        exit;
    }

    // Query items that have sales return records in tranfile2
    $select_items = "SELECT itemfile.itmcde, itemfile.itmdsc
        FROM itemfile
        WHERE true ".$xfilter."
        AND EXISTS (
            SELECT 1
            FROM tranfile1
            LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            WHERE tranfile1.trncde = '".$_POST['trncde_hidden']."'
            AND tranfile2.itmcde = itemfile.itmcde ".$xfilter2."
        )
        ORDER BY itemfile.itmdsc ASC";

    $stmt_items = $link->prepare($select_items);
    $stmt_items->execute();
    $item_rows = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Prepare detail query with UOM and Warehouse joins
    $detail_sql = "SELECT tranfile2.docnum,
            tranfile2.itmqty,
            tranfile2.untprc,
            tranfile2.extprc,
            tranfile2.unmcde,
            tranfile2.recid,
            tranfile1.trndte,
            tranfile1.ordernum,
            customerfile.cusdsc,
            mf_buyers.buyer_name,
            itemunitmeasurefile.unmdsc AS uom_desc,
            warehouse.warehouse_name,
            warehouse_floor.floor_no
        FROM tranfile2
        LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
        LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
        LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
        LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
        LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
        LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
        WHERE tranfile2.itmcde = ? ".$xfilter2."
        AND tranfile1.trncde = ?
        ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
    $stmt_details = $link->prepare($detail_sql);

    $grand_total = 0;

    foreach($item_rows as $item_row){
        $stmt_details->execute(array($item_row['itmcde'], $_POST['trncde_hidden']));
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
        foreach($detail_rows as $detail_row){
            $display_trndte = '';
            if(!empty($detail_row['trndte'])){
                $display_trndte = date("m/d/Y", strtotime($detail_row['trndte']));
            }

            $ordernum_lines = wrap_text_to_lines(normalize_report_text($detail_row['ordernum']), 75, 9);
            $shopname_lines = wrap_text_to_lines(normalize_report_text($detail_row['cusdsc']), 95, 9);
            $buyer_lines = wrap_text_to_lines(normalize_report_text($detail_row['buyer_name']), 115, 9);
            $uom_lines = wrap_text_to_lines(normalize_report_text($detail_row['uom_desc']), 45, 9);
            $warehouse_lines = wrap_text_to_lines(build_warehouse_display($detail_row['warehouse_name'], $detail_row['floor_no']), 100, 9);

            $line_count = max(
                count($ordernum_lines),
                count($shopname_lines),
                count($buyer_lines),
                count($uom_lines),
                count($warehouse_lines)
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
            foreach($shopname_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_shopname, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }
            foreach($buyer_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_orderedby, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }

            $pdf->ezPlaceData($col_qty, $row_y, format_qty($detail_row['itmqty']), 9, 'right');

            foreach($uom_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_uom, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }
            foreach($warehouse_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_warehouse, $row_y - ($line_index * 10), xls_safe_text($line_text), 9, 'left');
            }

            $pdf->ezPlaceData($col_unitprice, $row_y, number_format((float)$detail_row['untprc'], 2), 9, 'right');
            $pdf->ezPlaceData($col_extprice, $row_y, number_format((float)$detail_row['extprc'], 2), 9, 'right');

            $subtotal += (float)$detail_row['extprc'];
            $grand_total += (float)$detail_row['extprc'];
            $xtop -= $row_height;
        }

        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 10;
        pad_tab_columns(array($col_trndate, $col_ordernum, $col_shopname, $col_orderedby, $col_qty, $col_uom, 100), $xtop, 9);
        $pdf->ezPlaceData($col_warehouse, $xtop, xls_safe_text("<b>Subtotal:</b>"), 9, 'left');
        $pdf->ezPlaceData($col_extprice, $xtop, "<b>".number_format($subtotal, 2)."</b>", 9, 'right');
        $xtop -= 10;
        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 15;

        if($xtop <= 60){
            $pdf->ezNewPage();
            $xtop = 515;
        }
    }

    if($xtop <= 60){
        $pdf->ezNewPage();
        $xtop = 515;
    }

    $pdf->line($line_left, $xtop, $line_right, $xtop);
    $xtop -= 10;
    pad_tab_columns(array($col_trndate, $col_ordernum, $col_shopname, $col_orderedby, $col_qty, $col_uom, 100), $xtop, 9);
    $pdf->ezPlaceData($col_warehouse, $xtop, xls_safe_text("<b>Grand Total:</b>"), 9, 'left');
    $pdf->ezPlaceData($col_extprice, $xtop, "<b>".number_format($grand_total, 2)."</b>", 9, 'right');
    $xtop -= 10;
    $pdf->line($line_left, $xtop, $line_right, $xtop);

    $pdf->addText(30, 15, 8, "Date Printed : ".date("F j, Y, g:i A"), 0, 1);
    $pdf->ezStream();
    ob_end_flush();

    function render_item_section_header($item_desc, &$xtop)
    {
        global $pdf, $line_left, $line_right;
        global $col_trndate, $col_ordernum, $col_shopname, $col_orderedby, $col_qty, $col_uom, $col_warehouse, $col_unitprice, $col_extprice;

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
            $pdf->ezPlaceData($col_shopname, $xtop - 9, xls_safe_text("<b>Shop Name</b>"), 9, 'left');
            $pdf->ezPlaceData($col_orderedby, $xtop - 9, xls_safe_text("<b>Ordered By</b>"), 9, 'left');
            $pdf->ezPlaceData($col_qty, $xtop - 9, xls_safe_text("<b>Qty</b>"), 9, 'right');
            $pdf->ezPlaceData($col_uom, $xtop - 9, xls_safe_text("<b>UOM</b>"), 9, 'left');
            $pdf->ezPlaceData($col_warehouse, $xtop - 9, xls_safe_text("<b>Warehouse</b>"), 9, 'left');
            $pdf->ezPlaceData($col_unitprice, $xtop - 9, xls_safe_text("<b>Unit Price</b>"), 9, 'right');
            $pdf->ezPlaceData($col_extprice, $xtop - 9, xls_safe_text("<b>Ext. Price</b>"), 9, 'right');
            $pdf->line($line_left, $xtop - 12, $line_right, $xtop - 12);
            $xtop -= 23;
            return;
        }

        place_multiline_text($col_trndate, $xtop - 9, array("<b>Tran.</b>", "<b>Date</b>"), 8, 'left', 9);
        place_multiline_text($col_ordernum, $xtop - 9, array("<b>Order</b>", "<b>Num.</b>"), 8, 'left', 9);
        place_multiline_text($col_shopname, $xtop - 9, array("<b>Shop</b>", "<b>Name</b>"), 8, 'left', 9);
        place_multiline_text($col_orderedby, $xtop - 9, array("<b>Ordered</b>", "<b>By</b>"), 8, 'left', 9);
        $pdf->ezPlaceData($col_qty, $xtop - 9, "<b>Qty</b>", 9, 'right');
        $pdf->ezPlaceData($col_uom, $xtop - 9, "<b>UOM</b>", 9, 'left');
        $pdf->ezPlaceData($col_warehouse, $xtop - 9, "<b>Warehouse</b>", 9, 'left');
        place_multiline_text($col_unitprice, $xtop - 9, array("<b>Unit</b>", "<b>Price</b>"), 8, 'right', 9);
        place_multiline_text($col_extprice, $xtop - 9, array("<b>Ext.</b>", "<b>Price</b>"), 8, 'right', 9);
        $pdf->line($line_left, $xtop - 20, $line_right, $xtop - 20);
        $xtop -= 29;
    }

    function normalize_report_text($string)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        $search = array('Ã¢â‚¬Å"', 'Ã¢â‚¬Â', 'Ã¢â‚¬Ëœ', 'Ã¢â‚¬â„¢', 'Ã¢â‚¬â€œ', 'Ã¢â‚¬â€', 'Ã‚', 'â€œ', 'â€', 'â€˜', 'â€™', 'â€"', 'â€"');
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
            } else {
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        } else {
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

    function build_warehouse_display($warehouse_name, $floor_no)
    {
        $warehouse_name = normalize_report_text($warehouse_name);
        $floor_no = trim((string)$floor_no);

        if($warehouse_name !== '' && $floor_no !== ''){
            return $warehouse_name.' '.$floor_no.' floor';
        }

        if($warehouse_name !== ''){
            return $warehouse_name;
        }

        if($floor_no !== ''){
            return $floor_no.' floor';
        }

        return '';
    }

    function format_qty($qty)
    {
        $qty = (float)$qty;
        $formatted = number_format($qty, 2, '.', ',');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        if($formatted === '-0'){
            $formatted = '0';
        }
        return $formatted;
    }

    function build_salesret_report_groups($link, $xfilter, $xfilter2, $trncde_hidden)
    {
        $groups = array();

        $select_items = "SELECT itemfile.itmcde, itemfile.itmdsc
            FROM itemfile
            WHERE true ".$xfilter."
            AND EXISTS (
                SELECT 1
                FROM tranfile1
                LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
                WHERE tranfile1.trncde = '".$trncde_hidden."'
                AND tranfile2.itmcde = itemfile.itmcde ".$xfilter2."
            )
            ORDER BY itemfile.itmdsc ASC";

        $stmt_items = $link->prepare($select_items);
        $stmt_items->execute();

        $detail_sql = "SELECT tranfile2.docnum,
                tranfile2.itmqty,
                tranfile2.untprc,
                tranfile2.extprc,
                tranfile2.unmcde,
                tranfile2.recid,
                tranfile1.trndte,
                tranfile1.ordernum,
                customerfile.cusdsc,
                mf_buyers.buyer_name,
                itemunitmeasurefile.unmdsc AS uom_desc,
                warehouse.warehouse_name,
                warehouse_floor.floor_no
            FROM tranfile2
            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
            LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
            LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
            LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            WHERE tranfile2.itmcde = ? ".$xfilter2."
            AND tranfile1.trncde = ?
            ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_details = $link->prepare($detail_sql);

        while($item_row = $stmt_items->fetch()){
            $group = array(
                'item_desc' => normalize_report_text(isset($item_row['itmdsc']) ? $item_row['itmdsc'] : ''),
                'rows' => array(),
                'subtotal_qty' => 0,
                'subtotal_ext' => 0,
            );

            $stmt_details->execute(array($item_row['itmcde'], $trncde_hidden));

            while($detail_row = $stmt_details->fetch()){
                $trndte = '';
                if(!empty($detail_row['trndte'])){
                    $trndte = date("m/d/Y", strtotime($detail_row['trndte']));
                }

                $warehouse_display = build_warehouse_display(
                    isset($detail_row['warehouse_name']) ? $detail_row['warehouse_name'] : '',
                    isset($detail_row['floor_no']) ? $detail_row['floor_no'] : ''
                );

                $group['rows'][] = array(
                    'trndte' => $trndte,
                    'ordernum' => isset($detail_row['ordernum']) ? (string)$detail_row['ordernum'] : '',
                    'cusdsc' => normalize_report_text(isset($detail_row['cusdsc']) ? $detail_row['cusdsc'] : ''),
                    'buyer_name' => normalize_report_text(isset($detail_row['buyer_name']) ? $detail_row['buyer_name'] : ''),
                    'itmqty' => (float)$detail_row['itmqty'],
                    'uom_description' => normalize_report_text(isset($detail_row['uom_desc']) ? $detail_row['uom_desc'] : ''),
                    'warehouse_display' => $warehouse_display,
                    'untprc' => (float)$detail_row['untprc'],
                    'extprc' => (float)$detail_row['extprc'],
                );

                $group['subtotal_qty'] += (float)$detail_row['itmqty'];
                $group['subtotal_ext'] += (float)$detail_row['extprc'];
            }

            if(!empty($group['rows'])){
                $groups[] = $group;
            }
        }

        return $groups;
    }

    function export_salesret_xls($groups, $report_title, $report_user, $date_printed)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Return Item');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', $report_title);
        $sheet->setCellValue('A2', 'Pdf Report by: ' . $report_user . ' (Summarized)');
        $sheet->setCellValue('A3', 'Date Printed : ' . $date_printed);

        $row_num = 5;
        $grand_total = 0;
        $header_labels = array(
            'Tran. Date',
            'Order Num.',
            'Shop Name',
            'Ordered By',
            'Quantity',
            'UOM',
            'Warehouse',
            'Unit Price',
            'Extended Price'
        );

        foreach($groups as $group){
            $sheet->mergeCells('A' . $row_num . ':I' . $row_num);
            $sheet->setCellValue('A' . $row_num, 'Item: ' . $group['item_desc']);
            $sheet->getStyle('A' . $row_num)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_num)->getAlignment()->setWrapText(true);
            $row_num++;

            $sheet->fromArray($header_labels, null, 'A' . $row_num);
            $sheet->getStyle('A' . $row_num . ':I' . $row_num)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_num . ':I' . $row_num)->getAlignment()->setWrapText(true);
            $row_num++;

            foreach($group['rows'] as $detail_row){
                $sheet->setCellValue('A' . $row_num, $detail_row['trndte']);
                $sheet->setCellValue('B' . $row_num, $detail_row['ordernum']);
                $sheet->setCellValue('C' . $row_num, $detail_row['cusdsc']);
                $sheet->setCellValue('D' . $row_num, $detail_row['buyer_name']);
                $sheet->setCellValue('E' . $row_num, (float)$detail_row['itmqty']);
                $sheet->setCellValue('F' . $row_num, $detail_row['uom_description']);
                $sheet->setCellValue('G' . $row_num, $detail_row['warehouse_display']);
                $sheet->setCellValue('H' . $row_num, (float)$detail_row['untprc']);
                $sheet->setCellValue('I' . $row_num, (float)$detail_row['extprc']);
                $row_num++;
            }

            $sheet->setCellValue('D' . $row_num, 'Subtotal:');
            $sheet->setCellValue('E' . $row_num, (float)$group['subtotal_qty']);
            $sheet->setCellValue('I' . $row_num, (float)$group['subtotal_ext']);
            $sheet->getStyle('D' . $row_num . ':I' . $row_num)->getFont()->setBold(true);
            $grand_total += (float)$group['subtotal_ext'];
            $row_num += 2;
        }

        $sheet->setCellValue('D' . $row_num, 'Grand total:');
        $sheet->setCellValue('I' . $row_num, $grand_total);
        $sheet->getStyle('D' . $row_num . ':I' . $row_num)->getFont()->setBold(true);

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1:I' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('C1:G' . $row_num)->getAlignment()->setWrapText(true);
        $sheet->getStyle('E6:E' . $row_num)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H6:I' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E6:E' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H6:I' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(28);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(16);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'salesret_item.xls';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
    }
?>
