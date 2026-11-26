<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');

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

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

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
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;
 
        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        // $xtop   -= 15;

        
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        

        $xfields_heaeder_counter = 0;
        
        $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
        $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Order Num.</b>",10,'left');
        $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Buyer Name</b>",10,'left');
        $pdf->ezPlaceData($xleft+=105,$xtop,"<b>Tran. Date</b>",10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Shop Name</b>",10,'left');
        $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Ship To</b>",10,'left');
        $pdf->ezPlaceData($xleft+=105,$xtop,"<b>Paydate</b>",10,'left');
        $pdf->ezPlaceData($xleft+=65,$xtop,"<b>Payment Details</b>",10,'left');
        $pdf->ezPlaceData($xleft+=130,$xtop,"<b>Total</b>",10,'right');

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
    mf_buyers.buyer_name as tranfile1_buyer_name,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON
    tranfile1.cuscde = customerfile.cuscde LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
    WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.trndte ASC, customerfile.cusdsc ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
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


        $buyer_name = trim((string)$rs_main["tranfile1_buyer_name"]);

        if ($_POST['txt_output_type']=='tab')
		{
            $rs_main["customerfile_cusdsc"] = $rs_main["customerfile_cusdsc"];
            $rs_main["tranfile1_ordernum"] = $rs_main["tranfile1_ordernum"];
            $buyer_lines = array(sanitize_tab_text($buyer_name));
            $ship_to_lines = array((string)$rs_main["tranfile1_shipto"]);
            $paydetails_lines = array((string)$rs_main["tranfile1_paydetails"]);
		}else{
            $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],65,9);
            $rs_main["tranfile1_ordernum"] = trim_str($rs_main["tranfile1_ordernum"],100,9);
            $buyer_lines = wrap_text_limited($buyer_name, 100, 9, 3);
            $ship_to_lines = wrap_text_limited((string)$rs_main["tranfile1_shipto"], 100, 9, 2);
            $paydetails_lines = wrap_text_limited((string)$rs_main["tranfile1_paydetails"], 95, 9, 2);
        }

        $row_line_count = max(count($buyer_lines), count($ship_to_lines), count($paydetails_lines));
        $row_height = ($_POST['txt_output_type']=='tab') ? 15 : (12 + ((max(1,$row_line_count) - 1) * 10));

        if(($xtop - (($row_line_count - 1) * 10)) <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $pdf->ezPlaceData($xleft,$xtop,$rs_main["tranfile1_docnum"],9,"left");
        $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["tranfile1_ordernum"],9,"left");
        $buyer_col_x = $xleft+=80;
        $pdf->ezPlaceData($buyer_col_x,$xtop,$buyer_lines[0],9,"left");
        $pdf->ezPlaceData($xleft+=105,$xtop,$rs_main["tranfile1_trndte"],9,"left");
        $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["customerfile_cusdsc"],9,"left");
        $ship_to_col_x = $xleft+=70;
        $pdf->ezPlaceData($ship_to_col_x,$xtop,$ship_to_lines[0],9,"left");
        $pdf->ezPlaceData($xleft+=105,$xtop,$rs_main["tranfile1_paydate"],9,"left");
        $paydetails_col_x = $xleft+=65;
        $pdf->ezPlaceData($paydetails_col_x,$xtop,$paydetails_lines[0],9,"left");
        $pdf->ezPlaceData($xleft+130,$xtop,number_format($rs_main["tranfile1_trntot"],"2"),9,"right");

        for($xline_idx = 1; $xline_idx < count($buyer_lines); $xline_idx++){
            $pdf->ezPlaceData($buyer_col_x,$xtop - ($xline_idx * 10),$buyer_lines[$xline_idx],9,"left");
        }
        for($xline_idx = 1; $xline_idx < count($ship_to_lines); $xline_idx++){
            $pdf->ezPlaceData($ship_to_col_x,$xtop - ($xline_idx * 10),$ship_to_lines[$xline_idx],9,"left");
        }
        for($xline_idx = 1; $xline_idx < count($paydetails_lines); $xline_idx++){
            $pdf->ezPlaceData($paydetails_col_x,$xtop - ($xline_idx * 10),$paydetails_lines[$xline_idx],9,"left");
        }

        $xtop -= $row_height;

    }

       
    $pdf->line(25, $xtop-10, 770, $xtop-10);
    $xleft = 25;
    $pdf->ezPlaceData($xleft,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=70,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=80,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=105,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=60,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=70,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=105,$xtop-18,"",9,"left");
    $pdf->ezPlaceData($xleft+=65,$xtop-18,"<b>Grand total:</b>",9,"left");
    $pdf->ezPlaceData($xleft+130,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9,"right");

   
    $pdf->line(25, $xtop-10, 770, $xtop-10); 
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

    // Wrap text to multiple lines based on max width
    function wrap_text($string, $max_wid, $fsize) {
        global $pdf;

        if(empty($string)) {
            return array('');
        }

        // Check if text fits in one line
        $text_width = $pdf->getTextWidth($fsize, $string);
        if($text_width <= $max_wid) {
            return array($string);
        }

        // Split into words and wrap
        $words = explode(' ', $string);
        $lines = array();
        $current_line = '';

        foreach($words as $word) {
            $test_line = ($current_line == '') ? $word : $current_line . ' ' . $word;
            $test_width = $pdf->getTextWidth($fsize, $test_line);

            if($test_width <= $max_wid) {
                $current_line = $test_line;
            } else {
                if($current_line != '') {
                    $lines[] = $current_line;
                }
                // If single word is too long, just add it anyway
                $current_line = $word;
            }
        }

        // Add remaining text
        if($current_line != '') {
            $lines[] = $current_line;
        }

        return $lines;
    }

    function wrap_text_limited($string, $max_wid, $fsize, $max_lines = 3) {
        global $pdf;

        if(empty($string)) {
            return array('');
        }

        $wrapped_lines = array();
        $base_lines = wrap_text($string, $max_wid, $fsize);

        foreach($base_lines as $base_line) {
            if($pdf->getTextWidth($fsize, $base_line) <= $max_wid) {
                $wrapped_lines[] = $base_line;
                continue;
            }

            $remaining = $base_line;
            while($remaining !== '') {
                $chunk = '';
                $chars = str_split($remaining);

                foreach($chars as $char) {
                    if($chunk !== '' && $pdf->getTextWidth($fsize, $chunk . $char) > $max_wid) {
                        break;
                    }
                    $chunk .= $char;
                }

                if($chunk === '') {
                    $chunk = substr($remaining, 0, 1);
                }

                $wrapped_lines[] = rtrim($chunk);
                $remaining = ltrim(substr($remaining, strlen($chunk)));
            }
        }

        if(count($wrapped_lines) > $max_lines) {
            $overflow_text = implode(' ', array_slice($wrapped_lines, $max_lines - 1));
            $wrapped_lines = array_slice($wrapped_lines, 0, $max_lines - 1);
            $wrapped_lines[] = trim_str($overflow_text, $max_wid, $fsize);
        }

        return empty($wrapped_lines) ? array('') : $wrapped_lines;
    }

    function sanitize_tab_text($string) {
        $string = (string)$string;
        $string = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $string);
        $string = preg_replace('/[ ]{2,}/', ' ', $string);

        return trim($string);
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