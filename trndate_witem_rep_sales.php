<?php
    //var_dump($_POST);

    error_reporting(E_ALL);
    ini_set('display_errors', '1');


    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');
    require_once('resources/stdfunc100.php');

    ob_start();

    $xreport_title = "List of items";
		

    if ($_POST['txt_output_type']=='tab')
	{
		$pdf = new tab_ezpdf('Letter','landscape');
	}
	else
	{
		$pdf = new Cezpdf('Letter','landscape');

	}

    $pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");

		
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	
	$xtop = 580;
    $xleft = 25;
    $line_left = 25;
    $line_right = 770;
    $col_doc = 25;
    $col_order = 90;
    $col_date = 180;
    $col_item = 255;
    $col_warehouse = 405;
    $col_qty = 585;
    $col_uom = 595;
    $col_unit_price = 685;
    $col_total = 760;
    $pdf_col_order = 105;
    $pdf_col_date = 195;
    $pdf_grand_total_label_x = 470;

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

        // $progname_hidden ='';
        // if($_POST['trncde_hidden'] == 'SAL'){
        //     $progname_hidden = "Sales";
        // }
        // else if($_POST['trncde_hidden'] == 'SRT'){
        //     $progname_hidden = "Sales Return";
        // }
        // else if($_POST['trncde_hidden'] == 'PUR'){
        //     $progname_hidden = "Purchases";
        // }

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Sales</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;
 
        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        // $xtop   -= 15;

		
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($line_left, $xtop+10, $line_right, $xtop+10);
        $pdf->line($line_left, $xtop-3, $line_right, $xtop-3);
        

        $xfields_heaeder_counter = 0;
        
        $header_col_order = ($_POST['txt_output_type']=='tab') ? $col_order : $pdf_col_order;
        $header_col_date = ($_POST['txt_output_type']=='tab') ? $col_date : $pdf_col_date;
        $pdf->ezPlaceData($col_doc,$xtop,"<b>Doc. Num.</b>",10,'left');
        $pdf->ezPlaceData($header_col_order,$xtop,"<b>Order Num.</b>",10,'left');
        $pdf->ezPlaceData($header_col_date,$xtop,"<b>Tran. Date</b>",10,'left');
        $pdf->ezPlaceData($col_item,$xtop,"<b>Shop Name/Item</b>",10,'left');
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",10,'left');
        $pdf->ezPlaceData($col_qty,$xtop,"<b>Qty</b>",10,'right');
        $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",10,'left');
        $pdf->ezPlaceData($col_unit_price,$xtop,"<b>Unit Price</b>",10,'right');
        $pdf->ezPlaceData($col_total,$xtop,"<b>Total</b>",10,'right');
        // $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Profit</b>",10,'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';




    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }

    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    
    if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
        $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
    }

    // if(isset($_POST['cus_to']) && !empty($_POST['cus_to'])){
    //     $xfilter .= " AND customerfile.cusdsc<='".$_POST['cus_to']."'";
    // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (tranfile1.paydate!='' OR tranfile1.paydate IS NOT NULL)";
        // }

        


    
    // OPTIMIZATION: Single query to get all transactions with their items in ONE database call
    // This eliminates the N+1 query problem (previously: 1 main query + N detail queries per transaction)
    $select_db_all = "SELECT
        tranfile1.shipto as tranfile1_shipto,
        tranfile1.cuscde as tranfile1_cuscde,
        tranfile1.docnum as tranfile1_docnum,
        tranfile1.trndte as tranfile1_trndte,
        tranfile1.trntot as tranfile1_trntot,
        tranfile1.orderby as tranfile1_orderby,
        tranfile1.recid as tranfile1_recid,
        tranfile1.ordernum as tranfile1_ordernum,
        customerfile.recid as customerfile1_recid,
        customerfile.cusdsc as customerfile_cusdsc,
        tranfile1.paydate as tranfile1_paydate,
        tranfile1.paydetails as tranfile1_paydetails,
        customerfile.cusdsc,
        customerfile.cuscde,
        tranfile2.recid as t2_recid,
        tranfile2.itmcde as t2_itmcde,
        tranfile2.itmqty as t2_itmqty,
        tranfile2.untprc as t2_untprc,
        tranfile2.extprc as t2_extprc,
        itemfile.itmdsc as itmdsc,
        itemunitmeasurefile.unmdsc as unmdsc,
        TRIM(CONCAT(COALESCE(warehouse.warehouse_name,''), CASE WHEN TRIM(COALESCE(warehouse_floor.floor_no,'')) <> '' THEN CONCAT(' ', TRIM(warehouse_floor.floor_no), ' floor') ELSE '' END)) as warehouse_display
    FROM tranfile1
    LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
    LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
    LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
    LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
    LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
    LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
    WHERE tranfile1.trncde='".$_POST['trncde_hidden']."' ".$xfilter."
    ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";

    $stmt_all = $link->prepare($select_db_all);
    $stmt_all->execute();

    // MEMORY OPTIMIZATION: Stream-process rows instead of loading all into memory
    // Process one document at a time as rows come in (SQL already ordered by docnum)
    $grand_total = 0;
    $qty_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;

    $current_docnum = null;
    $current_header = null;
    $current_items = array();

    // Helper closure to render a single document and return its totals
    $render_document = function($rs_main, $items) use ($pdf, &$xtop, $col_doc, $col_order, $col_date, $col_item, $col_warehouse, $col_qty, $col_uom, $col_unit_price, $col_total, $pdf_col_order, $pdf_col_date, $line_left, $line_right) {
        $xleft = 25;

        $display_trndte = '';
        if(isset($rs_main["trndte"]) && !empty($rs_main["trndte"])){
            $display_trndte = date("m/d/Y", strtotime($rs_main["trndte"]));
        }

        $display_paydate = '';
        if(isset($rs_main["paydate"]) && !empty($rs_main["paydate"])){
            $display_paydate = date("m/d/Y", strtotime($rs_main["paydate"]));
        }

        if ($_POST['txt_output_type']=='tab')
        {
            $display_cusdsc = $rs_main["cusdsc"];
            $display_shipto = $rs_main["shipto"];
            $display_paydetails = $rs_main["paydetails"];
            $display_ordernum = $rs_main["ordernum"];
        }else{
            $display_cusdsc = trim_str($rs_main["cusdsc"],140,9);
            $display_shipto = trim_str($rs_main["shipto"],120,9);
            $display_paydetails = trim_str($rs_main["paydetails"],140,9);
            $display_ordernum = $rs_main["ordernum"];
        }

        $row_col_order = ($_POST['txt_output_type']=='tab') ? $col_order : $pdf_col_order;
        $row_col_date = ($_POST['txt_output_type']=='tab') ? $col_date : $pdf_col_date;
        $row_y = $xtop;
        $ordernum_lines = ($_POST['txt_output_type']=='tab')
            ? array((string)$display_ordernum)
            : wrap_text_lines(isset($display_ordernum) ? $display_ordernum : '', 85, 9);

        $pdf->ezPlaceData($col_doc,$row_y,$rs_main["docnum"],9,"left");
        foreach($ordernum_lines as $order_line_index => $order_line_text){
            $pdf->ezPlaceData($row_col_order,$row_y - ($order_line_index * 10), $order_line_text, 9, "left");
        }
        $pdf->ezPlaceData($row_col_date,$row_y,$display_trndte,9,"left");
        $pdf->ezPlaceData($col_item,$row_y,$display_cusdsc,9,"left");

        if($_POST['txt_output_type']!='tab' && count($ordernum_lines) > 1){
            $xtop -= ((count($ordernum_lines) - 1) * 10);
        }

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $qty_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
        $xtop-=12;

        // Process items for this document
        foreach($items as $item) {

            $item_desc = normalize_item_text(isset($item["itmdsc"]) ? $item["itmdsc"] : '');
            $warehouse_display = normalize_item_text(isset($item["warehouse_display"]) ? $item["warehouse_display"] : '');
            $uom_desc = normalize_item_text(isset($item["unmdsc"]) ? $item["unmdsc"] : '');

            if ($_POST['txt_output_type']=='tab')
            {
                $item_lines = array(xls_safe_text($item_desc));
                $warehouse_lines = array(xls_safe_text($warehouse_display));
                $uom_lines = array(xls_safe_text($uom_desc));
            }else{
                $item_lines = wrap_text_lines($item_desc,140,9);
                $warehouse_lines = wrap_text_lines($warehouse_display,170,9);
                $uom_lines = wrap_text_lines($uom_desc,80,9);
            }

            $line_count = max(count($item_lines), count($warehouse_lines), count($uom_lines));
            $row_height = 15 + ((max(1, $line_count) - 1) * 10);

            if(($xtop - $row_height) <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            $row_y = $xtop;
            pad_tab_columns(array($col_doc, $col_order, $col_date), $row_y, 9);
            foreach($item_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_item, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($warehouse_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_warehouse, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($uom_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_uom, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            $pdf->ezPlaceData($col_qty,$row_y,$item["itmqty"],9,"right");
            $pdf->ezPlaceData($col_unit_price,$row_y,number_format($item["untprc"],"2"),9,"right");

            $pdf->ezPlaceData($col_total,$row_y,number_format($item["extprc"],"2"),9,"right");

            $qty_tot += (float)$item["itmqty"];
            $cost_tot += (float)$item["extprc"];

            $xtop -= $row_height;

            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }
        }

        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 10;
        pad_tab_columns(array($col_doc, $col_order, $col_date, $col_item, $col_uom, $col_unit_price), $xtop, 9);
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>TOTAL:</b>",9,"left");
        $pdf->ezPlaceData($col_qty,$xtop,number_format($qty_tot,0),9,"right");
        $pdf->ezPlaceData($col_total,$xtop,number_format($cost_tot,2),9,"right");
        $xtop -= 10;
        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        return array($qty_tot, $cost_tot, $profit_tot);
    };

    // Stream through results row by row - only keep one document in memory at a time
    while ($row = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
        $docnum = $row['tranfile1_docnum'];

        // When we encounter a new document, render the previous one first
        if ($current_docnum !== null && $current_docnum !== $docnum) {
            // Render previous document
            list($qty_tot, $cost_tot, $profit_tot) = $render_document($current_header, $current_items);
            $qty_gtot += $qty_tot;
            $cost_gtot += $cost_tot;
            $profit_gtot += $profit_tot;
            $grand_total += $current_header["trntot"];

            // Free memory from previous document
            $current_items = array();
        }

        // Start new document or continue current one
        if ($current_docnum !== $docnum) {
            $current_docnum = $docnum;
            $current_header = array(
                'docnum' => $row['tranfile1_docnum'],
                'trndte' => $row['tranfile1_trndte'],
                'trntot' => $row['tranfile1_trntot'],
                'ordernum' => $row['tranfile1_ordernum'],
                'paydate' => $row['tranfile1_paydate'],
                'paydetails' => $row['tranfile1_paydetails'],
                'shipto' => $row['tranfile1_shipto'],
                'cusdsc' => $row['customerfile_cusdsc']
            );
        }

        // Add item if present (tranfile2 data)
        if (!empty($row['t2_recid'])) {
            $current_items[] = array(
                'recid' => $row['t2_recid'],
                'itmcde' => $row['t2_itmcde'],
                'itmqty' => $row['t2_itmqty'],
                'untprc' => $row['t2_untprc'],
                'extprc' => $row['t2_extprc'],
                'itmdsc' => $row['itmdsc'],
                'unmdsc' => $row['unmdsc'],
                'warehouse_display' => $row['warehouse_display']
            );
        }
    }

    // Render the last document
    if ($current_docnum !== null) {
        list($qty_tot, $cost_tot, $profit_tot) = $render_document($current_header, $current_items);
        $qty_gtot += $qty_tot;
        $cost_gtot += $cost_tot;
        $profit_gtot += $profit_tot;
        $grand_total += $current_header["trntot"];
    }

    // Free cursor and remaining memory
    $stmt_all->closeCursor();
    unset($current_items, $current_header, $row);

    $pdf->line($line_left, $xtop, $line_right, $xtop); 
    $xtop -= 10;
    pad_tab_columns(array($col_doc, $col_order, $col_date, $col_item, $col_uom, $col_unit_price), $xtop, 9);
    $pdf->ezPlaceData($pdf_grand_total_label_x,$xtop,"<b>GRAND TOTAL:</b>",8,"left");
    $pdf->ezPlaceData($col_qty,$xtop,number_format($qty_gtot,0),9,"right");
    $pdf->ezPlaceData($col_total,$xtop,number_format($cost_gtot,2),9,"right");
    // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_gtot,2),9,"right");
    $xtop -= 10;
    $pdf->line($line_left, $xtop, $line_right, $xtop); 

       
    // $pdf->line(25, $xtop-10, 770, $xtop-10); 
    // $pdf->ezPlaceData(700,$xtop-18,"<b>Grand total:</b>",9 ,'right');
    // $pdf->ezPlaceData(765,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');

   
    // $pdf->line(25, $xtop-10, 770, $xtop-10); 
	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

    function trim_str($string,$max_wid,$fsize)
    {   
        global $pdf;
        if(  get_class($pdf) == 'tab_ezpdf')
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

    function wrap_text_lines($string,$max_wid,$fsize)
    {
        global $pdf;

        $string = normalize_item_text($string);
        if($string === ''){
            return array('');
        }

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

    //returns dynamic width
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

    // XLS-safe text encoding: sanitizes text for tab-separated XLS output
    function xls_safe_text($string)
    {
        global $pdf;

        $string = (string)$string;
        if(get_class($pdf) != 'tab_ezpdf'){
            return $string;
        }

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


?>
