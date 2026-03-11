<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
	require_once('ezpdfclass/class/class.ezpdf.php');

    ob_start();

    $xreport_title = "List of items";
		
	$pdf = new Cezpdf("Letter",'landscape');
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
        
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 15;

		$pdf->setLineStyle(.5);
		// $pdf->line($xleft, $xtop+10, 770, $xtop+10);
        // $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        

        // $xfields_heaeder_counter = 0;
        
        // $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Tran. Date</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=100,$xtop,"<b>Customer</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=85,$xtop,"<b>Payment Details</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=215,$xtop,"<b>Total</b>",10,'right');

        $xleft = 25;


		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xfilter2  = '';
    $xorder = '';





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

    
    
    // $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
    // tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid,
    // customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    // customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON 
    // tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.docnum ASC";

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute(array($_POST['item']));
    $grand_total = 0;
    
    while($rs_main = $stmt_main->fetch()){    

   


        
        $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');
        $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],10 ,'left');
        $pdf->line(25, $xtop-12, 770, $xtop-12); 

        $xtop-=12;
        $xleft = 25;

        $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Order Num.</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=115,$xtop-9,"<b>Shop Name</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Ordered By</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=180,$xtop-9,"<b>Unit Price</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Quantity</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Extended Price</b>",9 ,'left');
        $pdf->line(25, $xtop-12, 770, $xtop-12); 
        $xtop-=23;

 


        $select_db2 = "SELECT * FROM tranfile2 LEFT JOIN tranfile1 ON tranfile2.docnum= tranfile1.docnum WHERE itmcde='".$rs_main['itmcde']."' ".$xfilter2." AND tranfile1.trncde='".$_POST['trncde_hidden']."' ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $subtotal = 0;
        $itmqty_total = 0;
    
        while($rs_main2 = $stmt_main2->fetch()){   

            $select_db3 = "SELECT * FROM tranfile1 
                           LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde 
                           LEFT JOIN mf_buyers ON 
                           tranfile1.buyer_id = mf_buyers.buyer_id
                           WHERE tranfile1.docnum='".$rs_main2['docnum']."'";
            $stmt_main3	= $link->prepare($select_db3);
            $stmt_main3->execute();
            $rs_main3 = $stmt_main3->fetch();

            // $pdf->ezPlaceData(625,$xtop,$select_db3,9,"right");

            $xleft = 25;
            $subtotal+=$rs_main2["extprc"];
            $itmqty_total+=$rs_main2["itmqty"];
            $grand_total+=$rs_main2["extprc"];

            // $grand_total += $rs_main2["tranfile1_trntot"];

            if(isset($rs_main3["trndte"]) && !empty($rs_main3["trndte"])){
                $rs_main3["trndte"] = date("m-d-Y",strtotime($rs_main3["trndte"]));
                $rs_main3["trndte"] = str_replace('-','/',$rs_main3["trndte"]);
            }

            $pdf->ezPlaceData($xleft,$xtop, $rs_main3['trndte'],9,"left");
            $pdf->ezPlaceData($xleft+=80,$xtop, trim_str($rs_main3['ordernum'],100,9),9,"left");
            $pdf->ezPlaceData($xleft+=115,$xtop,trim_str($rs_main3["cusdsc"],65,9),9,"left");
            $pdf->ezPlaceData($xleft+=85,$xtop,trim_str($rs_main3["buyer_name"],140,9),9,"left");
            //$pdf->ezPlaceData($xleft+=90,$xtop,$rs_main2["tranfile1_trndte"],9,"left");
            $pdf->ezPlaceData($xleft+=220,$xtop,number_format($rs_main2["untprc"],2),9,"right");
            $pdf->ezPlaceData($xleft+=80,$xtop,number_format($rs_main2["itmqty"]),9,"right");
            $pdf->ezPlaceData($xleft+=90,$xtop,number_format($rs_main2["extprc"],2),9,"right");
            $xtop -= 15;

                
            if($xtop <= 60)
            {



                $pdf->ezNewPage();
                $xtop = 530;

                $xheader = $pdf->openObject();
                $pdf->saveState();
        
                $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');
                $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],10 ,'left');
                $pdf->line(25, $xtop-12, 770, $xtop-12); 
        
                $xtop-=12;
                $xleft = 25;
        
                $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Order Num.</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=115,$xtop-9,"<b>Shop Name</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Ordered By</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=180,$xtop-9,"<b>Unit Price</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Quantity</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Extended Price</b>",9 ,'left');
                $pdf->line(25, $xtop-12, 770, $xtop-12); 
                $xtop-=23;
        
        
                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'add');
            }

        }

        


        $pdf->line(25, $xtop, 770, $xtop); 
        $pdf->ezPlaceData(480,$xtop-9,"<b>Subtotal:</b>",9 ,'left');
        $pdf->ezPlaceData(605,$xtop-9,"<b>".number_format($itmqty_total,0)."</b>",9 ,'right');
        $pdf->ezPlaceData(695,$xtop-9,"<b>".number_format($subtotal,2)."</b>",9 ,'right');

        $xtop-=20;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
            // $xheader = $pdf->openObject();
            // $pdf->saveState();
    
            // $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');
            // $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],10 ,'left');
            // $pdf->line(25, $xtop-12, 770, $xtop-12); 
    
            // $xtop-=12;
            // $xleft = 25;
    
            // $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
            // $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Order Num.</b>",9 ,'left');
            // $pdf->ezPlaceData($xleft+=115,$xtop-9,"<b>Customer</b>",9 ,'left');
            // $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Ordered By</b>",9 ,'left');
            // $pdf->ezPlaceData($xleft+=180,$xtop-9,"<b>Unit Price</b>",9 ,'left');
            // $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Quantity</b>",9 ,'left');
            // $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Extended Price</b>",9 ,'left');
            // $pdf->line(25, $xtop-12, 770, $xtop-12); 
            // $xtop-=23;
    
    
            // $pdf->restoreState();
            // $pdf->closeObject();
            // $pdf->addObject($xheader,'all');
        }

        
    }

       
    $pdf->line(25, $xtop-10, 770, $xtop-10); 
    $pdf->ezPlaceData(580,$xtop-18,"<b>Grand total:</b>",9 ,'left');
    $pdf->ezPlaceData(695,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');

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