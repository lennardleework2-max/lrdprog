<?php
    //var_dump($_POST);

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
    $user_desc = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
	
	$xtop = 580;
    $xleft = 25;
    $line_left = 25;
    $line_right = 770;

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

    // Column order: Doc. Num., Order Num, Tran. Date, Supp./Item, Warehouse,
    // Qty, UOM, Unit Price, Total.
    $col_docnum = 25;
    $col_ordernum = 95;
    $col_trndate = 175;
    $col_suppitem = 245;
    $col_warehouse = 390;
    $col_qty = 595;
    $col_uom = 610;
    $col_unitprice = 705;
    $col_total = 765;
    $col_total_label = $col_uom;

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Purchases</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$user_desc." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;

        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($line_left, $xtop+10, $line_right, $xtop+10);
        $pdf->line($line_left, $xtop-12, $line_right, $xtop-12);

        $xfields_heaeder_counter = 0;

        if ($_POST['txt_output_type']=='tab')
        {
            $pdf->ezPlaceData($col_docnum,$xtop,"<b>Doc. Num.</b>",9,'left');
            $pdf->ezPlaceData($col_ordernum,$xtop,"<b>Order Num.</b>",9,'left');
            $pdf->ezPlaceData($col_trndate,$xtop,"<b>Tran. Date</b>",9,'left');
            $pdf->ezPlaceData($col_suppitem,$xtop,"<b>Supp./Item</b>",9,'left');
            $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",9,'left');
            $pdf->ezPlaceData($col_qty,$xtop,"<b>Qty</b>",9,'right');
            $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",9,'left');
            $pdf->ezPlaceData($col_unitprice,$xtop,"<b>Unit Price</b>",9,'right');
            $pdf->ezPlaceData($col_total,$xtop,"<b>Total</b>",9,'right');
            $xtop -= 15;
        }
        else
        {
            place_multiline_text($col_docnum, $xtop, array("<b>Doc.</b>", "<b>Num.</b>"), 8, 'left', 9);
            place_multiline_text($col_ordernum, $xtop, array("<b>Order</b>", "<b>Num.</b>"), 8, 'left', 9);
            place_multiline_text($col_trndate, $xtop, array("<b>Tran.</b>", "<b>Date</b>"), 8, 'left', 9);
            $pdf->ezPlaceData($col_suppitem,$xtop,"<b>Supp./Item</b>",9,'left');
            $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",9,'left');
            $pdf->ezPlaceData($col_qty,$xtop,"<b>Qty</b>",9,'right');
            $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",9,'left');
            place_multiline_text($col_unitprice, $xtop, array("<b>Unit</b>", "<b>Price</b>"), 8, 'right', 9);
            $pdf->ezPlaceData($col_total,$xtop,"<b>Total</b>",9,'right');
            $xtop -= 24;
        }

        $xleft = 25;

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
        $xfilter .= " AND supplierfile.suppdsc='".$_POST['cus_search']."'";
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
    supplierfile.suppdsc as supplierfile_suppdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails
     FROM tranfile1 LEFT JOIN supplierfile ON 
    tranfile1.suppcde = supplierfile.suppcde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC";

    $stmt_main	= $link->prepare($select_db);
    // $pdf->ezPlaceData($xleft,$xtop-100,$select_db,1,"left");
    $stmt_main->execute();
    $grand_total = 0;
    $price_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;
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


        $supplier_lines = ($_POST['txt_output_type']=='tab')
            ? array((string)$rs_main["supplierfile_suppdsc"])
            : wrap_text_to_lines($rs_main["supplierfile_suppdsc"], 135, 9);
        $ordernum_lines = ($_POST['txt_output_type']=='tab')
            ? array((string)$rs_main["tranfile1_ordernum"])
            : wrap_text_to_lines($rs_main["tranfile1_ordernum"], 70, 9);
        $main_line_count = max(count($supplier_lines), count($ordernum_lines));
        $main_row_height = 15 + ((max(1, $main_line_count) - 1) * 10);

        if(($xtop - $main_row_height) <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $row_y = $xtop;
        $pdf->ezPlaceData($col_docnum,$row_y,$rs_main["tranfile1_docnum"],9,"left");
        foreach($ordernum_lines as $line_index => $line_text){
            $pdf->ezPlaceData($col_ordernum,$row_y - ($line_index * 10),$line_text,9,"left");
        }
        $pdf->ezPlaceData($col_trndate,$row_y,$rs_main["tranfile1_trndte"],9,"left");
        foreach($supplier_lines as $line_index => $line_text){
            $pdf->ezPlaceData($col_suppitem,$row_y - ($line_index * 10),$line_text,9,"left");
        }

        if($_POST['txt_output_type']!='tab' && $main_line_count > 1){
            $xtop -= (($main_line_count - 1) * 10);
        }

        


        

        $select_db2="SELECT tranfile2.*, itemfile.itmdsc,
            itemunitmeasurefile.unmdsc as uom_desc,
            warehouse.warehouse_name,
            warehouse_floor.floor_no
            FROM tranfile2
            LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
            LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            WHERE tranfile2.docnum='".$rs_main['tranfile1_docnum']."'";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        // $pdf->ezPlaceData(15,$xtop-100,$select_db3,8,"left");
        $price_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
        $xtop -= 12;
        while($rs_main2 = $stmt_main2->fetch()){

            // Build warehouse display value: warehouse_name + floor_no + "floor"
            $warehouse_display = '';
            if (!empty($rs_main2["warehouse_name"])) {
                $warehouse_display = $rs_main2["warehouse_name"];
                if (!empty($rs_main2["floor_no"])) {
                    $warehouse_display .= ' ' . $rs_main2["floor_no"] . ' floor';
                }
            } elseif (!empty($rs_main2["floor_no"])) {
                $warehouse_display = $rs_main2["floor_no"] . ' floor';
            }

            // UOM display value
            $uom_display = !empty($rs_main2["uom_desc"]) ? $rs_main2["uom_desc"] : '';

            if ($_POST['txt_output_type']=='tab')
            {
                $item_lines = array((string)$rs_main2["itmdsc"]);
                $warehouse_lines = array($warehouse_display);
                $uom_lines = array($uom_display);
            }else{
                $item_lines = wrap_text_to_lines($rs_main2["itmdsc"], 140, 9);
                $warehouse_lines = wrap_text_to_lines($warehouse_display, 165, 9);
                $uom_lines = wrap_text_to_lines($uom_display, 70, 9);
            }

            $max_lines = max(count($item_lines), count($warehouse_lines), count($uom_lines));
            $row_height = 15 + ((max(1, $max_lines) - 1) * 10);

            if (($xtop - $row_height) <= 60) {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            $row_y = $xtop;
            pad_tab_columns(array($col_docnum, $col_ordernum, $col_trndate), $row_y, 9);
            foreach ($item_lines as $line_idx => $line_text) {
                $pdf->ezPlaceData($col_suppitem, $row_y - ($line_idx * 10), $line_text, 9, "left");
            }
            foreach ($warehouse_lines as $line_idx => $line_text) {
                $pdf->ezPlaceData($col_warehouse, $row_y - ($line_idx * 10), $line_text, 9, "left");
            }
            foreach ($uom_lines as $line_idx => $line_text) {
                $pdf->ezPlaceData($col_uom, $row_y - ($line_idx * 10), $line_text, 9, "left");
            }
            $pdf->ezPlaceData($col_qty, $row_y, $rs_main2["itmqty"], 9, "right");
            $pdf->ezPlaceData($col_unitprice, $row_y, number_format($rs_main2["untprc"], "2"), 9, "right");
            $pdf->ezPlaceData($col_total, $row_y, number_format($rs_main2["extprc"], "2"), 9, "right");

            $price_tot += $rs_main2["untprc"];
            $cost_tot += $rs_main2["extprc"];

            $xtop -= $row_height;

            if ($xtop <= 60) {
                $pdf->ezNewPage();
                $xtop = 515;
            }
        }
  
        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $xtop -= 10;
        pad_tab_columns(array($col_docnum, $col_ordernum, $col_trndate, $col_suppitem, $col_warehouse, $col_qty), $xtop, 9);
        $pdf->ezPlaceData($col_total_label, $xtop, "<b>TOTAL:</b>", 9, "left");
        $pdf->ezPlaceData($col_unitprice, $xtop, number_format($price_tot, 2), 9, "right");
        $pdf->ezPlaceData($col_total, $xtop, number_format($cost_tot, 2), 9, "right");
        $xtop -= 10;
        $pdf->line($line_left, $xtop, $line_right, $xtop); 
        $xtop -= 15;

        
        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $price_gtot += $price_tot;
        $cost_gtot += $cost_tot;
        $profit_gtot += $profit_tot;
    }

    if($xtop <= 60)
    {
        $pdf->ezNewPage();
        $xtop = 515;
    }

    $pdf->line($line_left, $xtop, $line_right, $xtop);
    $xtop -= 10;
    pad_tab_columns(array($col_docnum, $col_ordernum, $col_trndate, $col_suppitem, $col_warehouse, $col_qty), $xtop, 9);
    $pdf->ezPlaceData($col_total_label, $xtop, "<b>GRAND TOTAL:</b>", 8, "left");
    $pdf->ezPlaceData($col_unitprice, $xtop, number_format($price_gtot, 2), 9, "right");
    $pdf->ezPlaceData($col_total, $xtop, number_format($cost_gtot, 2), 9, "right");
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
        $xarr_str = str_split($string);
        $max_wid -= 5;
        $xxstr = "";
        $xcut = false;
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $max_wid)
            {
                $xcut = true;
                break;
            }
            $xxstr = $xxstr.$value;
        }
        if($xcut)
        {
            $xxstr = $xxstr.'...';
        }
        return $xxstr;
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

    function place_multiline_text($xpos, $ypos, $lines, $font_size, $align = 'left', $line_gap = 10)
    {
        global $pdf;

        foreach($lines as $index => $line_text){
            $pdf->ezPlaceData($xpos, $ypos - ($index * $line_gap), $line_text, $font_size, $align);
        }
    }

    /**
     * Wraps text into multiple lines based on max pixel width.
     * Does NOT truncate with "..." - instead wraps to additional lines.
     * Similar to wrap_str_two_lines() in top_sales_item_pdf.php
     *
     * @param string $string The text to wrap
     * @param int $max_wid Maximum width in pixels
     * @param int $fsize Font size
     * @return array Array of wrapped lines
     */
    function wrap_text_to_lines($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = trim((string)$string);
        if ($string === '') {
            return array('');
        }

        // For tab export, return single line
        if (get_class($pdf) == 'tab_ezpdf') {
            return array($string);
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

            // Fit as much text as possible within the width
            $line = fit_text_width($remaining, $max_wid, $fsize);
            if ($line === '') {
                // At minimum, take one character to avoid infinite loop
                $line = substr($remaining, 0, 1);
            }

            // Try to break at a word boundary (last space)
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

    /**
     * Fits text to a maximum width without adding ellipsis.
     * Returns the longest substring that fits within max_wid.
     *
     * @param string $string The text to fit
     * @param int $max_wid Maximum width in pixels
     * @param int $fsize Font size
     * @return string The fitted text
     */
    function fit_text_width($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr = '';
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
            if ($xstr_wid > $max_wid) {
                break;
            }
            $xxstr = $xxstr . $value;
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


?>
