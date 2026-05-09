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

        


    
    $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
    tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid, tranfile1.ordernum as tranfile1_ordernum,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON 
    tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    $qty_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;
    // $pdf->ezPlaceData($xleft,$xtop-100,$select_db,2,"left");
    while($rs_main = $stmt_main->fetch()){    

        $xleft = 25;

        $grand_total += $rs_main["tranfile1_trntot"];

        if(isset($rs_main["tranfile1_trndte"]) && !empty($rs_main["tranfile1_trndte"])){
            $rs_main["tranfile1_trndte"] = date("m-d-Y",strtotime($rs_main["tranfile1_trndte"]));
            $rs_main["tranfile1_trndte"] = str_replace('-','/',$rs_main["tranfile1_trndte"]);
        }

        if(isset($rs_main["tranfile1_paydate"]) && !empty($rs_main["tranfile1_paydate"])){
            $rs_main["tranfile1_paydate"] = date("m-d-Y",strtotime($rs_main["tranfile1_paydate"]));
            $rs_main["tranfile1_paydate"] = str_replace('-','/',$rs_main["tranfile1_paydate"]);
        }


        if ($_POST['txt_output_type']=='tab')
		{
            $rs_main["customerfile_cusdsc"] = $rs_main["customerfile_cusdsc"];
            $rs_main["tranfile1_shipto"] = $rs_main["tranfile1_shipto"];
            $rs_main["tranfile1_paydetails"] = $rs_main["tranfile1_paydetails"];
            $rs_main["tranfile1_ordernum"] = $rs_main["tranfile1_ordernum"];
		}else{
            $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],140,9);
            $rs_main["tranfile1_shipto"] = trim_str($rs_main["tranfile1_shipto"],120,9);
            $rs_main["tranfile1_paydetails"] = trim_str($rs_main["tranfile1_paydetails"],140,9);
        }

        $row_col_order = ($_POST['txt_output_type']=='tab') ? $col_order : $pdf_col_order;
        $row_col_date = ($_POST['txt_output_type']=='tab') ? $col_date : $pdf_col_date;
        $row_y = $xtop;
        $ordernum_lines = ($_POST['txt_output_type']=='tab')
            ? array((string)$rs_main["tranfile1_ordernum"])
            : wrap_text_lines(isset($rs_main["tranfile1_ordernum"]) ? $rs_main["tranfile1_ordernum"] : '', 85, 9);

        $pdf->ezPlaceData($col_doc,$row_y,$rs_main["tranfile1_docnum"],9,"left");
        foreach($ordernum_lines as $order_line_index => $order_line_text){
            $pdf->ezPlaceData($row_col_order,$row_y - ($order_line_index * 10), $order_line_text, 9, "left");
        }
        $pdf->ezPlaceData($row_col_date,$row_y,$rs_main["tranfile1_trndte"],9,"left");
        $pdf->ezPlaceData($col_item,$row_y,$rs_main["customerfile_cusdsc"],9,"left");
        // $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["tranfile1_shipto"],9,"left");
        // $pdf->ezPlaceData($xleft+=135,$xtop,$rs_main["tranfile1_paydate"],9,"left");
        // $pdf->ezPlaceData($xleft+=85,$xtop,$rs_main["tranfile1_paydetails"],9,"left");
        // $pdf->ezPlaceData($xleft+215,$xtop,number_format($rs_main["tranfile1_trntot"],"2"),9,"right");

        if($_POST['txt_output_type']!='tab' && count($ordernum_lines) > 1){
            $xtop -= ((count($ordernum_lines) - 1) * 10);
        }

        

        

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }



        // Added LEFT JOINs so both PDF and XLS exports can show UOM and warehouse/floor values without dropping unmatched rows.
        $select_db2="SELECT tranfile2.*, itemfile.itmdsc as itmdsc,
        itemunitmeasurefile.unmdsc as unmdsc,
        TRIM(CONCAT(COALESCE(warehouse.warehouse_name,''), CASE WHEN TRIM(COALESCE(warehouse_floor.floor_no,'')) <> '' THEN CONCAT(' ', TRIM(warehouse_floor.floor_no), ' floor') ELSE '' END)) as warehouse_display
        FROM tranfile2
        LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
        LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
        LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
        LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
        WHERE tranfile2.docnum='".$rs_main['tranfile1_docnum']."'";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        // $pdf->ezPlaceData(15,$xtop-100,$select_db3,8,"left");
        $qty_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
                    $xtop-=12;

        while($rs_main2 = $stmt_main2->fetch()){   

            $item_desc = normalize_item_text(isset($rs_main2["itmdsc"]) ? $rs_main2["itmdsc"] : '');
            $warehouse_display = normalize_item_text(isset($rs_main2["warehouse_display"]) ? $rs_main2["warehouse_display"] : '');
            $uom_desc = normalize_item_text(isset($rs_main2["unmdsc"]) ? $rs_main2["unmdsc"] : '');

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

            // get cost

            // $select_db3="SELECT * FROM tranfile1 LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile1.trndte<='".$rs_main['tranfile1_trndte']."'
            // AND (tranfile1.trncde='ADJ' OR tranfile1.trncde='PUR') AND tranfile2.itmcde='".$rs_main2['itmcde']."' AND tranfile2.itmqty > 0 ORDER BY tranfile1.trndte DESC, tranfile2.recid DESC LIMIT 1";
            // $stmt_main3	= $link->prepare($select_db3);
            // $stmt_main3->execute();
            // $rs_main3 = $stmt_main3->fetch();

            $line_count = max(count($item_lines), count($warehouse_lines), count($uom_lines));
            $row_height = 15 + ((max(1, $line_count) - 1) * 10);

            if(($xtop - $row_height) <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            // $pdf->ezPlaceData($xleft,$xtop,"ITEM:",9,"left");
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
            $pdf->ezPlaceData($col_qty,$row_y,$rs_main2["itmqty"],9,"right");
            $pdf->ezPlaceData($col_unit_price,$row_y,number_format($rs_main2["untprc"],"2"),9,"right");
            // $trndte = (empty($rs_main['tranfile1_trndte'])) ? NULL :  date("Y-m-d", strtotime($rs_main['tranfile1_trndte']));
            // $unit_cost = get_unitcost($rs_main2['itmcde'],$trndte);
            // $cost = $unit_cost * $rs_main2["itmqty"];

            $pdf->ezPlaceData($col_total,$row_y,number_format($rs_main2["extprc"],"2"),9,"right");
            //$profit = $rs_main2["extprc"] - $cost;

            $qty_tot += (float)$rs_main2["itmqty"];
            $cost_tot += (float)$rs_main2["extprc"];
            //$profit_tot+=$profit;

            $xtop -= $row_height;

            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            // $pdf->ezPlaceData(10,$xtop-200,$select_db3,5,"left");
        }
  
        $pdf->line($line_left, $xtop, $line_right, $xtop); 
        $xtop -= 10;
        pad_tab_columns(array($col_doc, $col_order, $col_date, $col_item, $col_uom, $col_unit_price), $xtop, 9);
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>TOTAL:</b>",9,"left");
        $pdf->ezPlaceData($col_qty,$xtop,number_format($qty_tot,0),9,"right");
        $pdf->ezPlaceData($col_total,$xtop,number_format($cost_tot,2),9,"right");
        // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_tot,2),9,"right");
        $xtop -= 10;
        $pdf->line($line_left, $xtop, $line_right, $xtop); 
        $xtop -= 15;

        
        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $qty_gtot += $qty_tot;
        $cost_gtot += $cost_tot;
        $profit_gtot += $profit_tot;
    }

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
