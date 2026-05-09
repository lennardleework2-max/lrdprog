<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
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
        $pdf = new tab_ezpdf("Letter",'landscape');
    }else{
        $pdf = new Cezpdf("Letter",'landscape');
    }

	$pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	
	$xtop = 580;
    $xleft = 25;
    $line_left = 25;
    $line_right = 755;
    $col_trndte = 25;
    $col_ordernum = 105;
    $col_shop = 180;
    $col_ordered_by = 265;
    // Rebalanced the right-side columns so large currency values stay visible inside the page margin.
    $col_qty = 450;
    $col_uom = 462;
    $col_warehouse = 505;
    $col_unit_price = 665;
    $col_ext_price = 748;

    /**header**/
    
    $progname_hidden ='';
    if($_POST['trncde_hidden'] == 'SAL'){
        $progname_hidden = "Sales";
    }
    else if($_POST['trncde_hidden'] == 'SRT'){
        $progname_hidden = "Sales Return";
    }
    else if($_POST['trncde_hidden'] == 'PUR'){
        $progname_hidden = "Purchases";
    }

	$xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop,"<b>".$progname_hidden."</b>", 15, 'left' );
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
    $xtop -= 15;

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader,'all');

	/***header**/

    $xfilter = '';
    $xfilter2  = '';

    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

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
        $xfilter .= " AND itmcde='".$_POST['item']."'";
    }

    if($is_tab_export){
        $report_groups = build_item_sales_report_groups($link, $xfilter, $xfilter2, $_POST['trncde_hidden']);
        export_item_sales_xls($report_groups, $progname_hidden, $_SESSION['userdesc'], $date_printed);
        exit;
    }

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter." ORDER BY itmdsc ASC";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    
    while($rs_main = $stmt_main->fetch()){
        $item_desc = normalize_item_text(isset($rs_main['itmdsc']) ? $rs_main['itmdsc'] : '');
        $detail_rows = fetch_item_sales_detail_rows($link, $rs_main['itmcde'], $xfilter2, $_POST['trncde_hidden']);
        if(empty($detail_rows)){
            continue;
        }

        $xtop = draw_item_group_header(
            $item_desc,
            $xtop,
            10,
            $col_trndte,
            $col_ordernum,
            $col_shop,
            $col_ordered_by,
            $col_qty,
            $col_uom,
            $col_warehouse,
            $col_unit_price,
            $col_ext_price,
            $line_left,
            $line_right
        );

        $subtotal = 0;
        $itmqty_total = 0;
    
        foreach($detail_rows as $detail_row){
            $shop_lines = wrap_text_lines($detail_row["cusdsc"], 75, 9, 24);
            $ordered_by_lines = wrap_text_lines($detail_row["buyer_name"], 150, 9, 30);
            $uom_lines = wrap_text_lines($detail_row["uom_description"], 35, 9, 12);
            $warehouse_lines = wrap_text_lines($detail_row["warehouse_display"], 150, 9, 28);
            $row_line_count = max(count($shop_lines), count($ordered_by_lines), count($uom_lines), count($warehouse_lines));
            $row_height = 15 + ((max(1, $row_line_count) - 1) * 10);

            if(($xtop - $row_height) <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 530;
                $xtop = draw_item_group_header(
                    $item_desc,
                    $xtop,
                    10,
                    $col_trndte,
                    $col_ordernum,
                    $col_shop,
                    $col_ordered_by,
                    $col_qty,
                    $col_uom,
                    $col_warehouse,
                    $col_unit_price,
                    $col_ext_price,
                    $line_left,
                    $line_right
                );
            }

            $row_y = $xtop;
            $subtotal += (float)$detail_row["extprc"];
            $itmqty_total += (float)$detail_row["itmqty"];
            $grand_total += (float)$detail_row["extprc"];

            $pdf->ezPlaceData($col_trndte,$row_y, $detail_row['trndte'],9,"left");
            $pdf->ezPlaceData($col_ordernum,$row_y, trim_str($detail_row['ordernum'],80,9),9,"left");

            foreach($shop_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_shop, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($ordered_by_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_ordered_by, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($uom_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_uom, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($warehouse_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_warehouse, $row_y - ($line_index * 10), $line_text, 9, "left");
            }

            $pdf->ezPlaceData($col_qty,$row_y,number_format((float)$detail_row["itmqty"],0),9,"right");
            $pdf->ezPlaceData($col_unit_price,$row_y,number_format((float)$detail_row["untprc"],2),9,"right");
            $pdf->ezPlaceData($col_ext_price,$row_y,number_format((float)$detail_row["extprc"],2),9,"right");
            $xtop -= $row_height;
        }

        if(($xtop - 20) <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;
            $xtop = draw_item_group_header(
                $item_desc,
                $xtop,
                10,
                $col_trndte,
                $col_ordernum,
                $col_shop,
                $col_ordered_by,
                $col_qty,
                $col_uom,
                $col_warehouse,
                $col_unit_price,
                $col_ext_price,
                $line_left,
                $line_right
            );
        }

        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $subtotal_y = $xtop - 9;
        pad_tab_columns(array($col_trndte, $col_ordernum, $col_shop, $col_uom, $col_warehouse, $col_unit_price), $subtotal_y, 9);
        $pdf->ezPlaceData($col_ordered_by,$subtotal_y,"<b>Subtotal:</b>",9 ,'left');
        $pdf->ezPlaceData($col_qty,$subtotal_y,"<b>".number_format($itmqty_total,0)."</b>",9 ,'right');
        $pdf->ezPlaceData($col_ext_price,$subtotal_y,"<b>".number_format($subtotal,2)."</b>",9 ,'right');

        $xtop -= 20;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }
    }

    $pdf->line($line_left, $xtop-10, $line_right, $xtop-10);
    $grand_total_y = $xtop - 18;
    pad_tab_columns(array($col_trndte, $col_ordernum, $col_shop, $col_uom, $col_warehouse, $col_unit_price), $grand_total_y, 9);
    $pdf->ezPlaceData($col_ordered_by,$grand_total_y,"<b>Grand total:</b>",9 ,'left');
    $pdf->ezPlaceData($col_ext_price,$grand_total_y,"<b>".number_format($grand_total,2)."</b>",9 ,'right');

    $pdf->line($line_left, $xtop-10, $line_right, $xtop-10);
	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);

    if($is_tab_export){
        $pdf->ezStream('xls', 'sales_item');
    }else{
        $pdf->ezStream();
    }

    ob_end_flush();

    function draw_item_group_header(
        $item_desc,
        $xtop,
        $item_font_size,
        $col_trndte,
        $col_ordernum,
        $col_shop,
        $col_ordered_by,
        $col_qty,
        $col_uom,
        $col_warehouse,
        $col_unit_price,
        $col_ext_price,
        $line_left,
        $line_right
    ){
        global $pdf;

        $item_lines = wrap_text_lines($item_desc, 630, $item_font_size, 70);
        $item_line_count = count($item_lines);
        $item_y = $xtop - 9;

        pad_tab_columns(array($col_ordernum, $col_shop, $col_ordered_by, $col_qty, $col_uom, $col_warehouse, $col_unit_price, $col_ext_price), $item_y, $item_font_size);
        $pdf->ezPlaceData(25, $item_y, "<b>Item:</b>", $item_font_size, 'left');
        foreach($item_lines as $line_index => $line_text){
            $pdf->ezPlaceData(55, $item_y - ($line_index * 10), $line_text, $item_font_size, 'left');
        }

        $line_y = $xtop - (12 + (($item_line_count - 1) * 10));
        $pdf->line($line_left, $line_y, $line_right, $line_y);

        $xtop -= 12 + (($item_line_count - 1) * 10);
        $xleft = 25;

        $header_y = $xtop - 9;
        $pdf->ezPlaceData($col_trndte,$header_y,"<b>Tran. Date</b>",9 ,'left');
        $pdf->ezPlaceData($col_ordernum,$header_y,"<b>Order Num.</b>",9 ,'left');
        $pdf->ezPlaceData($col_shop,$header_y,"<b>Shop Name</b>",9 ,'left');
        $pdf->ezPlaceData($col_ordered_by,$header_y,"<b>Ordered By</b>",9 ,'left');
        $pdf->ezPlaceData($col_qty,$header_y,"<b>Quantity</b>",9 ,'right');
        $pdf->ezPlaceData($col_uom,$header_y,"<b>UOM</b>",9 ,'left');
        $pdf->ezPlaceData($col_warehouse,$header_y,"<b>Warehouse</b>",9 ,'left');
        $pdf->ezPlaceData($col_unit_price,$header_y,"<b>Unit Price</b>",9 ,'right');
        $pdf->ezPlaceData($col_ext_price,$header_y,"<b>Extended Price</b>",9 ,'right');
        $pdf->line($line_left, $xtop-12, $line_right, $xtop-12);

        return $xtop - 23;
    }

    function trim_str($string,$max_wid,$fsize)
    {
        global $pdf;
        if(get_class($pdf) == 'tab_ezpdf')
        {
            return $string;
        }
        return fit_text_to_width((string)$string, $max_wid - 5, $fsize, true);
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

    function wrap_text_lines($string,$max_wid,$fsize,$tab_max_chars = 48)
    {
        global $pdf;

        $string = normalize_item_text($string);
        if($string === ''){
            return array('');
        }

        if(get_class($pdf) === 'tab_ezpdf'){
            return array(trim_text_by_chars($string, $tab_max_chars));
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

            $line = fit_text_to_width($remaining, $max_wid, $fsize, false);
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

    function trim_text_by_chars($string, $max_chars = 48)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        if(strlen($string) <= $max_chars){
            return $string;
        }

        $cut = rtrim(substr($string, 0, $max_chars));
        $last_space = strrpos($cut, ' ');
        if($last_space !== false && $last_space > 0){
            $cut = rtrim(substr($cut, 0, $last_space));
        }

        return $cut.'...';
    }

    function normalize_item_text($string)
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

    function fit_text_to_width($string, $max_wid, $fsize, $add_ellipsis = false)
    {
        global $pdf;

        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $limit_wid = $max_wid;
        if($add_ellipsis){
            $limit_wid = $max_wid - $pdf->getTextWidth($fsize, '...');
        }
        if($limit_wid < 1){
            $limit_wid = 1;
        }

        $xarr_str = str_split($string);
        $xxstr = '';
        $xcut = false;
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $limit_wid)
            {
                $xcut = true;
                break;
            }
            $xxstr = $xxstr.$value;
        }

        if($add_ellipsis && $xcut){
            $xxstr = rtrim($xxstr).'...';
        }
        return rtrim($xxstr);
    }

    function dynamic_width($xstr_chk, $xleft , $spaces ,$xalign_chk){

        if($xalign_chk == "right"){
            $str_count = strlen($xstr_chk);
            $xleft_new = $xleft + ($str_count * 4.2) - ($spaces * 4.2);
            return $xleft_new+5;
        }else if($xalign_chk == "left"){

            $xleft_new = $xleft + ($spaces * 4.2);
            return $xleft_new;
        }

        else if($xalign_chk == "cus_left"){
            $str_count = strlen($xstr_chk);
            $xleft_new = $xleft + ($str_count * 4.2) + ($spaces * 4.2);
            return $xleft_new;
        }
    }

    function fetch_item_sales_detail_rows($link, $itmcde, $xfilter2, $trncde_hidden)
    {
        $rows = array();

        // Added LEFT JOINs so both PDF and spreadsheet exports can show UOM and warehouse/floor values on the same grouped rows.
        $select_db2 = "SELECT tranfile2.itmqty,
                              tranfile2.untprc,
                              tranfile2.extprc,
                              tranfile1.trndte,
                              tranfile1.orderby,
                              tranfile1.ordernum,
                              customerfile.cusdsc,
                              mf_buyers.buyer_name,
                              itemunitmeasurefile.unmdsc as uom_description,
                              TRIM(CONCAT(COALESCE(warehouse.warehouse_name,''), CASE WHEN TRIM(COALESCE(warehouse_floor.floor_no,'')) <> '' THEN CONCAT(' ', TRIM(warehouse_floor.floor_no), ' floor') ELSE '' END)) as warehouse_display
                       FROM tranfile2
                       LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
                       LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
                       LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
                       LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
                       LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
                       LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
                       WHERE tranfile2.itmcde='".$itmcde."' ".$xfilter2."
                         AND tranfile1.trncde='".$trncde_hidden."'
                       ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_main2 = $link->prepare($select_db2);
        $stmt_main2->execute();

        while($rs_main2 = $stmt_main2->fetch()){
            $trndte = '';
            if(isset($rs_main2["trndte"]) && !empty($rs_main2["trndte"])){
                $trndte = date("m-d-Y",strtotime($rs_main2["trndte"]));
                $trndte = str_replace('-','/',$trndte);
            }

            $rows[] = array(
                'trndte' => $trndte,
                'ordernum' => isset($rs_main2['ordernum']) ? (string)$rs_main2['ordernum'] : '',
                'cusdsc' => normalize_item_text(isset($rs_main2["cusdsc"]) ? $rs_main2["cusdsc"] : ''),
                'buyer_name' => normalize_item_text(isset($rs_main2["buyer_name"]) ? $rs_main2["buyer_name"] : ''),
                'itmqty' => (float)$rs_main2["itmqty"],
                'uom_description' => normalize_item_text(isset($rs_main2["uom_description"]) ? $rs_main2["uom_description"] : ''),
                'warehouse_display' => normalize_item_text(isset($rs_main2["warehouse_display"]) ? $rs_main2["warehouse_display"] : ''),
                'untprc' => (float)$rs_main2["untprc"],
                'extprc' => (float)$rs_main2["extprc"],
            );
        }

        return $rows;
    }

    function build_item_sales_report_groups($link, $xfilter, $xfilter2, $trncde_hidden)
    {
        $groups = array();

        $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter." ORDER BY itmdsc ASC";
        $stmt_main = $link->prepare($select_db);
        $stmt_main->execute();

        while($rs_main = $stmt_main->fetch()){
            $group = array(
                'item_desc' => normalize_item_text(isset($rs_main['itmdsc']) ? $rs_main['itmdsc'] : ''),
                'rows' => array(),
                'subtotal_qty' => 0,
                'subtotal_ext' => 0,
            );

            $group['rows'] = fetch_item_sales_detail_rows($link, $rs_main['itmcde'], $xfilter2, $trncde_hidden);

            foreach($group['rows'] as $detail_row){
                $group['subtotal_qty'] += (float)$detail_row['itmqty'];
                $group['subtotal_ext'] += (float)$detail_row['extprc'];
            }

            if(!empty($group['rows'])){
                $groups[] = $group;
            }
        }

        return $groups;
    }

    function export_item_sales_xls($groups, $report_title, $report_user, $date_printed)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Item');

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

        $filename = 'sales_item.xls';

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
