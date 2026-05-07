<?php
    //var_dump($_POST);

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL); 

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    ob_start();

    $xreport_title = "List of items";
		

    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab');

    if ($is_tab_export)
	{
		$pdf = new tab_ezpdf('Letter','landscape');
	}
	else
	{
		$pdf = new Cezpdf('Letter','landscape');
		$pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");
	}

	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	
	$xtop = 580;
    $xleft = 25;
    $page_content_top = 520;
    $line_right = 770;
    $pdf_columns = array(
        'date' => array('x' => 25, 'width' => 55, 'align' => 'left', 'header' => 'Tran. Date'),
        'type' => array('x' => 85, 'width' => 45, 'align' => 'left', 'header' => 'Tran. Type.'),
        'tran_num' => array('x' => 135, 'width' => 70, 'align' => 'left', 'header' => 'Tran. Num.'),
        'order_num' => array('x' => 210, 'width' => 65, 'align' => 'left', 'header' => 'Order Num.'),
        'shop' => array('x' => 280, 'width' => 120, 'align' => 'left', 'header' => 'Shop Name/Supplier'),
        'buyer' => array('x' => 405, 'width' => 90, 'align' => 'left', 'header' => 'Ordered By'),
        'cost' => array('x' => 570, 'width' => 70, 'align' => 'right', 'header' => 'Cost per/unt'),
        'price' => array('x' => 645, 'width' => 70, 'align' => 'right', 'header' => 'Price per/unt'),
        'in' => array('x' => 695, 'width' => 45, 'align' => 'right', 'header' => 'In'),
        'out' => array('x' => 745, 'width' => 45, 'align' => 'right', 'header' => 'Out')
    );

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

        $progname_hidden ='';


		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Stock Card</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;
 
        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        // $xtop   -= 15;

        $select_db_item = "SELECT * FROM itemfile WHERE itmcde=?";
        $stmt_item	= $link->prepare($select_db_item);
        $stmt_item->execute(array($_POST['item']));
        $rs_item = $stmt_item->fetch();


        if(!empty($rs_item)){
            $item_display = $rs_item["itmdsc"];
        }else{
            $item_display = '';
        }

        
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        // $xtop   -= 20; 

        // $pdf->ezPlaceData($xleft,$xtop,"<b>Item:</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=30,$xtop,$item_display,10,'left');
        // $pdf->ezPlaceData($xleft+=475,$xtop,"<b>Beginning Balance:</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=130,$xtop,number_format($rs_balance["xsum"]),10,'right');
        // $xtop-=20;
        // $xleft = 25;

		// $pdf->setLineStyle(.5);
		// $pdf->line($xleft, $xtop+10, 770, $xtop+10);
        // $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        

        $xfields_heaeder_counter = 0;

        


        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');
        $xtop = $page_content_top;

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';




        // if((isset($_POST['doc_from']) && !empty($_POST['doc_from'])) &&
        // (isset($_POST['doc_to']) && !empty($_POST['doc_to'])) ){

        //     $xfilter .= " AND tranfile1.docnum>='".$_POST['doc_from']."' AND tranfile1.docnum<='".$_POST['doc_to']."'";
        // }

        // else if(isset($_POST['doc_from']) && !empty($_POST['doc_from'])){
        //     $xfilter .= " AND tranfile1.docnum>='".$_POST['doc_from']."'";
        // }

        // else if(isset($_POST['doc_to']) && !empty($_POST['doc_to'])){

        //     $xfilter .= " AND tranfile1.docnum<='".$_POST['doc_to']."'";
        // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (tranfile1.paydate!='' OR tranfile1.paydate IS NOT NULL)";
        // }


    $xfilter2 = '';
    $date_from_sql = '';
    $date_to_sql = '';

    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

        $date_from_sql = date("Y-m-d", strtotime($_POST['date_from']));
        $date_to_sql = date("Y-m-d", strtotime($_POST['date_to']));

        $xfilter2 .= " AND tranfile1.trndte>='".$date_from_sql."' AND tranfile1.trndte<='".$date_to_sql."'";
    }
    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $date_from_sql = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter2 .= " AND tranfile1.trndte>='".$date_from_sql."'";
    }

    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $date_to_sql = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte<='".$date_to_sql."'";
    }
    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
    }

    //$xfilter2 = '';

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter. "ORDER BY itmdsc";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $report_items = array();
    while($rs_main = $stmt_main->fetch()){

        $xfilter_balance = '';

        if(!empty($date_from_sql)){
             $xfilter_balance .= " AND tranfile1.trndte<'".$date_from_sql."'";
         }
 
         if(isset($_POST['item']) && !empty($_POST['item'])){
             $xfilter_balance .= " AND tranfile2.itmcde='".$_POST['item']."'";
         }

        //  $xfilter_balance .= " AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
 
         $select_db_balance = "SELECT SUM(stkqty) as  xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum  WHERE true ".$xfilter_balance." AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
         $stmt_balance	= $link->prepare($select_db_balance);
         $stmt_balance->execute();
        // $pdf->ezPlaceData(20,$xtop,$select_db_balance,2,"left");
        // $xtop-=10;
         $rs_balance = $stmt_balance->fetch();

        if(empty($date_from_sql)){
            $rs_balance['xsum'] = 0;
        }

        // $xfilter2.= " AND tranfile2.itmcde='".$rs_main["itmcde"]."'";

        $select_db2 = "SELECT tranfile1.trndte as tranfile1_trndte, 
                              tranfile1.trncde as  tranfile1_trncde, 
                              tranfile2.docnum as tranfile2_docnum,
                              tranfile2.untprc as tranfile2_untprc,
                              tranfile1.ordernum as tranfile1_ordernum,
                              customerfile.cusdsc as customerfile_cusdsc,
                              supplierfile.suppdsc as supplierfile_suppdsc,
                              tranfile1.orderby as tranfile1_orderby,
                              tranfile2.stkqty as tranfile2_stkqty,
                              mf_buyers.buyer_name as buyer_name   
                              FROM tranfile2 LEFT JOIN tranfile1 ON
                               tranfile2.docnum = tranfile1.docnum 
                               LEFT JOIN itemfile ON itemfile.itmcde =  tranfile2.itmcde 
                               LEFT JOIN supplierfile ON tranfile1.suppcde = supplierfile.suppcde 
                               LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde 
                               LEFT JOIN mf_buyers ON mf_buyers.buyer_id = tranfile1.buyer_id
                               WHERE true ".$xfilter2." AND tranfile2.itmcde='".$rs_main['itmcde']."' ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $transactions = array();
        $in_total = 0;
        $out_total = 0;
        // $pdf->ezPlaceData(20,$xtop,$select_db2,2,"left");
        // $xtop-=10;
        while($rs_main2 = $stmt_main2->fetch()){ 
            $supp_or_cus =  "";
    
            if(isset($rs_main2["supplierfile_suppdsc"]) && !empty($rs_main2["supplierfile_suppdsc"])){
                $supp_or_cus = $rs_main2["supplierfile_suppdsc"];
            }else{
                $supp_or_cus = $rs_main2["customerfile_cusdsc"];
            }
    
            if(!empty($rs_main2["tranfile1_trndte"]) && $rs_main2["tranfile1_trndte"] !== NULL &&  $rs_main2["tranfile1_trndte"]!=="1970-01-01"){
                $rs_main2["tranfile1_trndte"] = date("m-d-Y",strtotime($rs_main2["tranfile1_trndte"]));
                $rs_main2["tranfile1_trndte"] = str_replace('-','/',$rs_main2["tranfile1_trndte"]);
            }else{
                $rs_main2["tranfile1_trndte"] = '';
            }

            $qty_value = (float)$rs_main2["tranfile2_stkqty"];
            if($qty_value > 0){
                $in_total += $qty_value;
            }else if($qty_value < 0){
                $out_total += ($qty_value * -1);
            }

            $transactions[] = array(
                'tranfile1_trndte' => (string)$rs_main2["tranfile1_trndte"],
                'tranfile1_trncde' => (string)$rs_main2["tranfile1_trncde"],
                'tranfile2_docnum' => (string)$rs_main2["tranfile2_docnum"],
                'tranfile1_ordernum' => (string)$rs_main2["tranfile1_ordernum"],
                'supp_or_cus' => (string)$supp_or_cus,
                'buyer_name' => (string)$rs_main2["buyer_name"],
                'tranfile2_untprc' => (float)$rs_main2["tranfile2_untprc"],
                'tranfile2_stkqty' => $qty_value
            );
        }

        if(empty($transactions)){
            continue;
        }

        $opening_balance = isset($rs_balance['xsum']) ? (float)$rs_balance['xsum'] : 0;
        $report_items[] = array(
            'itmdsc' => $rs_main['itmdsc'],
            'balance' => $opening_balance,
            'transactions' => $transactions,
            'in_total' => $in_total,
            'out_total' => $out_total,
            'ending_balance' => ($opening_balance + $in_total) - $out_total
        );
    }

    foreach($report_items as $report_item){
        if($xtop <= 120){
            $pdf->ezNewPage();
            $xtop = $page_content_top;
        }

        draw_stock_card_section_header(
            $report_item['itmdsc'],
            $report_item['balance'],
            $xtop,
            $pdf_columns,
            $is_tab_export,
            $line_right
        );

        foreach($report_item['transactions'] as $transaction_row){
            if($is_tab_export){
                $pdf->ezPlaceData($pdf_columns['date']['x'],$xtop,$transaction_row["tranfile1_trndte"],9,"left");
                $pdf->ezPlaceData($pdf_columns['type']['x'],$xtop,$transaction_row["tranfile1_trncde"],9,"left");
                $pdf->ezPlaceData($pdf_columns['tran_num']['x'],$xtop,$transaction_row["tranfile2_docnum"],9,"left");
                $pdf->ezPlaceData($pdf_columns['order_num']['x'],$xtop,$transaction_row["tranfile1_ordernum"],9,"left");
                $pdf->ezPlaceData($pdf_columns['shop']['x'],$xtop,$transaction_row["supp_or_cus"],9,"left");
                $pdf->ezPlaceData($pdf_columns['buyer']['x'],$xtop,$transaction_row["buyer_name"],9,"left");

                if($transaction_row["tranfile2_stkqty"] > 0){
                    $pdf->ezPlaceData($pdf_columns['cost']['x'],$xtop,number_format($transaction_row["tranfile2_untprc"],2),9,"right");
                    $pdf->ezPlaceData($pdf_columns['in']['x'],$xtop,number_format($transaction_row["tranfile2_stkqty"]),9,"right");
                }else if($transaction_row["tranfile2_stkqty"] < 0){
                    $pdf->ezPlaceData($pdf_columns['price']['x'],$xtop,number_format($transaction_row["tranfile2_untprc"],2),9,"right");
                    $pdf->ezPlaceData($pdf_columns['out']['x'],$xtop,number_format($transaction_row["tranfile2_stkqty"] * -1),9,"right");
                }

                $xtop -= 15;

                if($xtop <= 60){
                    $pdf->ezNewPage();
                    $xtop = $page_content_top;
                    draw_stock_card_section_header(
                        $report_item['itmdsc'],
                        $report_item['balance'],
                        $xtop,
                        $pdf_columns,
                        $is_tab_export,
                        $line_right
                    );
                }
                continue;
            }

            $row_lines = array(
                'date' => wrap_pdf_lines($transaction_row["tranfile1_trndte"], $pdf_columns['date']['width'], 9, 2),
                'type' => wrap_pdf_lines($transaction_row["tranfile1_trncde"], $pdf_columns['type']['width'], 9, 2),
                'tran_num' => wrap_pdf_lines($transaction_row["tranfile2_docnum"], $pdf_columns['tran_num']['width'], 9, 3),
                'order_num' => wrap_pdf_lines($transaction_row["tranfile1_ordernum"], $pdf_columns['order_num']['width'], 9, 3),
                'shop' => wrap_pdf_lines($transaction_row["supp_or_cus"], $pdf_columns['shop']['width'], 9, 3),
                'buyer' => wrap_pdf_lines($transaction_row["buyer_name"], $pdf_columns['buyer']['width'], 9, 3)
            );

            $max_row_lines = 1;
            foreach($row_lines as $line_group){
                $max_row_lines = max($max_row_lines, count($line_group));
            }

            $row_height = 15 + (($max_row_lines - 1) * 10);
            if(($xtop - $row_height) <= 60){
                $pdf->ezNewPage();
                $xtop = $page_content_top;
                draw_stock_card_section_header(
                    $report_item['itmdsc'],
                    $report_item['balance'],
                    $xtop,
                    $pdf_columns,
                    $is_tab_export,
                    $line_right
                );
            }

            foreach($row_lines['date'] as $line_index => $line_text){
                $pdf->ezPlaceData($pdf_columns['date']['x'],$xtop - ($line_index * 10),$line_text,9,"left");
            }
            foreach($row_lines['type'] as $line_index => $line_text){
                $pdf->ezPlaceData($pdf_columns['type']['x'],$xtop - ($line_index * 10),$line_text,9,"left");
            }
            foreach($row_lines['tran_num'] as $line_index => $line_text){
                $pdf->ezPlaceData($pdf_columns['tran_num']['x'],$xtop - ($line_index * 10),$line_text,9,"left");
            }
            foreach($row_lines['order_num'] as $line_index => $line_text){
                $pdf->ezPlaceData($pdf_columns['order_num']['x'],$xtop - ($line_index * 10),$line_text,9,"left");
            }
            foreach($row_lines['shop'] as $line_index => $line_text){
                $pdf->ezPlaceData($pdf_columns['shop']['x'],$xtop - ($line_index * 10),$line_text,9,"left");
            }
            foreach($row_lines['buyer'] as $line_index => $line_text){
                $pdf->ezPlaceData($pdf_columns['buyer']['x'],$xtop - ($line_index * 10),$line_text,9,"left");
            }

            if($transaction_row["tranfile2_stkqty"] > 0){
                $pdf->ezPlaceData($pdf_columns['cost']['x'],$xtop,number_format($transaction_row["tranfile2_untprc"],2),9,"right");
                $pdf->ezPlaceData($pdf_columns['in']['x'],$xtop,number_format($transaction_row["tranfile2_stkqty"]),9,"right");
            }else if($transaction_row["tranfile2_stkqty"] < 0){
                $pdf->ezPlaceData($pdf_columns['price']['x'],$xtop,number_format($transaction_row["tranfile2_untprc"],2),9,"right");
                $pdf->ezPlaceData($pdf_columns['out']['x'],$xtop,number_format($transaction_row["tranfile2_stkqty"] * -1),9,"right");
            }

            $xtop -= $row_height;
        }

        if(($xtop - 30) <= 60){
            $pdf->ezNewPage();
            $xtop = $page_content_top;
            draw_stock_card_section_header(
                $report_item['itmdsc'],
                $report_item['balance'],
                $xtop,
                $pdf_columns,
                $is_tab_export,
                $line_right
            );
        }

        $pdf->line(25, $xtop, $line_right, $xtop);
        $pdf->ezPlaceData(635,$xtop-12,"<b>Total:</b>",9,'right');
        $pdf->ezPlaceData($pdf_columns['in']['x'],$xtop-12,"<b>".number_format($report_item['in_total'])."</b>",9,'right');
        $pdf->ezPlaceData($pdf_columns['out']['x'],$xtop-12,"<b>".number_format($report_item['out_total'])."</b>",9,'right');

        $xtop -= 24;

        $pdf->ezPlaceData(635,$xtop-12,"<b>Balance:</b>",9,'right');
        $pdf->ezPlaceData($pdf_columns['out']['x'],$xtop-12,"<b>".number_format($report_item['ending_balance'])."</b>",9,'right');
        $xtop -= 30;

        if($xtop <= 70){
            $pdf->ezNewPage();
            $xtop = $page_content_top;
        }
    }

    

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

    function draw_stock_card_section_header($item_desc, $balance, &$xtop, $pdf_columns, $is_tab_export, $line_right)
    {
        global $pdf;

        $item_label_y = $xtop - 9;
        $item_line_height = 10;
        $item_lines = array((string)$item_desc);

        if(!$is_tab_export){
            $item_lines = wrap_pdf_lines($item_desc, 560, 9, 3);
        }

        $pdf->ezPlaceData(25,$item_label_y,"<b>Item:</b>",9,'left');
        foreach($item_lines as $line_index => $line_text){
            $pdf->ezPlaceData(55,$item_label_y - ($line_index * $item_line_height),$line_text,9,'left');
        }
        $pdf->ezPlaceData(635,$item_label_y,"<b>Balance:</b>",9,'left');
        $pdf->ezPlaceData(760,$item_label_y,number_format($balance),9,'right');

        $item_bottom_y = $item_label_y - ((count($item_lines) - 1) * $item_line_height);
        $item_separator_y = $item_bottom_y - 5;
        $pdf->line(25, $item_separator_y, $line_right, $item_separator_y);

        $header_top_y = $item_separator_y - 14;
        $max_header_lines = 1;
        $header_rows = array();
        foreach($pdf_columns as $column_key => $column_config){
            if($is_tab_export){
                $header_rows[$column_key] = array($column_config['header']);
            }else{
                $header_rows[$column_key] = wrap_pdf_lines($column_config['header'], $column_config['width'], 9, 3);
            }
            $max_header_lines = max($max_header_lines, count($header_rows[$column_key]));
        }

        foreach($pdf_columns as $column_key => $column_config){
            foreach($header_rows[$column_key] as $line_index => $line_text){
                $pdf->ezPlaceData(
                    $column_config['x'],
                    $header_top_y - ($line_index * $item_line_height),
                    "<b>".$line_text."</b>",
                    9,
                    $column_config['align']
                );
            }
        }

        $header_separator_y = $header_top_y - (($max_header_lines - 1) * $item_line_height) - 4;
        $pdf->line(25, $header_separator_y, $line_right, $header_separator_y);
        $xtop = $header_separator_y - 12;
    }

    function wrap_pdf_lines($string, $max_wid, $fsize, $max_lines = 3)
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

        while($remaining !== '' && count($wrapped_lines) < $max_lines){
            if($pdf->getTextWidth($fsize, $remaining) <= $max_wid){
                $wrapped_lines[] = $remaining;
                $remaining = '';
                break;
            }

            $line = fit_text_to_width($remaining, $max_wid, $fsize, false);
            if($line === ''){
                $line = substr($remaining, 0, 1);
            }

            $break_point = strrpos($line, ' ');
            if($break_point !== false && $break_point > 0){
                $line = rtrim(substr($line, 0, $break_point));
            }

            $wrapped_lines[] = rtrim($line);
            $remaining = ltrim(substr($remaining, strlen($line)));
        }

        if($remaining !== '' && !empty($wrapped_lines)){
            $last_index = count($wrapped_lines) - 1;
            $wrapped_lines[$last_index] = fit_text_to_width($wrapped_lines[$last_index], $max_wid, $fsize, true);
        }

        return $wrapped_lines;
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


?>
