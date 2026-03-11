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
		

    if (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
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
        $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
    }

    //$xfilter2 = '';

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter. "ORDER BY itmdsc";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    while($rs_main = $stmt_main->fetch()){

        $xfilter_balance = '';

        if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
             $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
             $xfilter_balance .= " AND tranfile1.trndte<'".$_POST['date_from']."'";
         }
 
         if(isset($_POST['item']) && !empty($_POST['item'])){
             $xfilter_balance .= " AND tranfile2.itmcde='".$_POST['item']."'";
         }

        //  $xfilter_balance .= " AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
 
         $select_db_balance = "SELECT SUM(stkqty) as  xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum  WHERE true ".$xfilter_balance." AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
         $stmt_balance	= $link->prepare($select_db_balance);
         $stmt_balance->execute(array($_POST['item']));
        // $pdf->ezPlaceData(20,$xtop,$select_db_balance,2,"left");
        // $xtop-=10;
         $rs_balance = $stmt_balance->fetch();

        if(empty($_POST['date_from'])){
            $rs_balance['xsum'] = 0;
        }



        $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",9 ,'left');
        $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],9 ,'left');
        $pdf->ezPlaceData(680,$xtop-9,"<b>Balance:</b>",9 ,'left');
        $pdf->ezPlaceData(765,$xtop-9,number_format($rs_balance['xsum']),9 ,'right');
        $pdf->line(25, $xtop-14, 770, $xtop-12); 

        $xtop-=14;
        $xleft = 25;

        $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>Tran. Type.</b>",9,'left');
        $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>Tran. Num.</b>",10,'left');
        $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>Order Num.</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=125,$xtop-9,"<b>Shop Name/Supplier</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Ordered By</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=120,$xtop-9,"<b>Cost</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>Price</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>In</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=47,$xtop-9,"<b>Out</b>",9 ,'right');
        // $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b></b>",9 ,'left');
        $pdf->line(25, $xtop-12, 770, $xtop-12); 
        $xtop-=23;

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
        $grand_total = 0;
        $in_total = 0;
        $out_total = 0;
        // $pdf->ezPlaceData(20,$xtop,$select_db2,2,"left");
        // $xtop-=10;
        while($rs_main2 = $stmt_main2->fetch()){ 
    
            $xleft = 25;
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
                $rs_main2["tranfile1_trndte"] = NULL;
            }
    
            if (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
            {
    
                $rs_main2["tranfile1_ordernum"] = $rs_main2["tranfile1_ordernum"];
                $supp_or_cus = $supp_or_cus;
                $rs_main2["buyer_name"] = $rs_main2["buyer_name"];
    
            }else{
                $rs_main2["tranfile1_ordernum"] = trim_str($rs_main2["tranfile1_ordernum"],100,9);
                $supp_or_cus = trim_str($supp_or_cus,70,9);
                $rs_main2["buyer_name"] = trim_str($rs_main2["buyer_name"],70,9);
            }

    
            $pdf->ezPlaceData($xleft,$xtop,$rs_main2["tranfile1_trndte"],9,"left");
            $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main2["tranfile1_trncde"],9,"left");
            $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main2["tranfile2_docnum"],9,"left");
            $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main2["tranfile1_ordernum"],9,"left");
            $pdf->ezPlaceData($xleft+=125,$xtop,$supp_or_cus,9,"left");
            $pdf->ezPlaceData($xleft+=95,$xtop,$rs_main2["buyer_name"],9,"left");
            if($rs_main2["tranfile2_stkqty"] > 0){
                $pdf->ezPlaceData($xleft+=120,$xtop,number_format($rs_main2["tranfile2_untprc"],2),9,"right");
            }else{
                $pdf->ezPlaceData($xleft+=190,$xtop,number_format($rs_main2["tranfile2_untprc"],2),9,"right");
            }
            
    
    
            if($rs_main2["tranfile2_stkqty"] > 0){
                $pdf->ezPlaceData($xleft+=140,$xtop,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                $in_total+=$rs_main2["tranfile2_stkqty"];
            }else{
                $rs_main2["tranfile2_stkqty"] = $rs_main2["tranfile2_stkqty"] * - 1;

                $pdf->ezPlaceData($xleft+=117,$xtop,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                $out_total+=$rs_main2["tranfile2_stkqty"];
            }
    
            $xtop -= 15;
    
            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 495;
            }
            
     
            
        }
    
           
        $pdf->line(25, $xtop, 770, $xtop); 
        $pdf->ezPlaceData(675,$xtop-9,"<b>Total:</b>",9 ,'right');
        $pdf->ezPlaceData(716,$xtop-9,"<b>".number_format($in_total)."</b>",9 ,'right');
        $pdf->ezPlaceData(762,$xtop-9,"<b>".number_format($out_total)."</b>",9 ,'right');
    
        $xtop -= 15;
    
        $pdf->ezPlaceData(675,$xtop-9,"<b>Balance:</b>",9,'right');
        $balance = ($rs_balance['xsum'] + $in_total) - $out_total;
        $pdf->ezPlaceData(762,$xtop-9,"<b>".number_format($balance)."</b>",9 ,'right');
        $xtop -= 20;

        if($xtop <= 70)
        {
            $pdf->ezNewPage();
            $xtop = 515;
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