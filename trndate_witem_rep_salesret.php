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
	
	$xtop = 580;
    $xleft = 25;

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
        $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Return</b>", 15, 'left' );
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
        $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Tran. Date</b>",10,'left');
        $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Cus./Item</b>",10,'left');
        $pdf->ezPlaceData($xleft+=195,$xtop,"<b>Quantity</b>",10,'right');
        $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Unit Price</b>",10,'right');
        $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Total</b>",10,'right');
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


        if ($_POST['txt_output_type']=='tab')
		{
            $rs_main["customerfile_cusdsc"] = $rs_main["customerfile_cusdsc"];
            $rs_main["tranfile1_shipto"] = $rs_main["tranfile1_shipto"];
            $rs_main["tranfile1_paydetails"] = $rs_main["tranfile1_paydetails"];
            $rs_main["tranfile1_ordernum"] = $rs_main["tranfile1_ordernum"];
		}else{
            $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],155,9);
            $rs_main["tranfile1_shipto"] = trim_str($rs_main["tranfile1_shipto"],120,9);
            $rs_main["tranfile1_paydetails"] = trim_str($rs_main["tranfile1_paydetails"],140,9);
            $rs_main["tranfile1_ordernum"] = trim_str($rs_main["tranfile1_ordernum"],100,9);
        }

        $pdf->ezPlaceData($xleft,$xtop,$rs_main["tranfile1_docnum"],9,"left");
        $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["tranfile1_ordernum"],9,"left");
        $pdf->ezPlaceData($xleft+=110,$xtop,$rs_main["tranfile1_trndte"],9,"left");
        $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["customerfile_cusdsc"],9,"left");
        // $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["tranfile1_shipto"],9,"left");
        // $pdf->ezPlaceData($xleft+=135,$xtop,$rs_main["tranfile1_paydate"],9,"left");
        // $pdf->ezPlaceData($xleft+=85,$xtop,$rs_main["tranfile1_paydetails"],9,"left");
        // $pdf->ezPlaceData($xleft+215,$xtop,number_format($rs_main["tranfile1_trntot"],"2"),9,"right");

        


        

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }



        $select_db2="SELECT * FROM tranfile2 LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde WHERE tranfile2.docnum='".$rs_main['tranfile1_docnum']."'";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        // $pdf->ezPlaceData(15,$xtop-100,$select_db3,8,"left");
        $price_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
                    $xtop-=12;
        while($rs_main2 = $stmt_main2->fetch()){   

            if ($_POST['txt_output_type']=='tab')
            {
                $rs_main2["itmdsc"] = $rs_main2["itmdsc"];
            }else{
                $rs_main2["itmdsc"] = trim_str($rs_main2["itmdsc"],155,9);

            }

            // get cost

            // $select_db3="SELECT * FROM tranfile1 LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile1.trndte<='".$rs_main['tranfile1_trndte']."'
            // AND (tranfile1.trncde='ADJ' OR tranfile1.trncde='PUR') AND tranfile2.itmcde='".$rs_main2['itmcde']."' AND tranfile2.itmqty > 0 ORDER BY tranfile1.trndte DESC, tranfile2.recid DESC LIMIT 1";
            // $stmt_main3	= $link->prepare($select_db3);
            // $stmt_main3->execute();
            // $rs_main3 = $stmt_main3->fetch();

            $xleft = 275;

            // $pdf->ezPlaceData($xleft,$xtop,"ITEM:",9,"left");
            $pdf->ezPlaceData($xleft+=192,$xtop,$rs_main2["itmqty"],9,"right");
            $pdf->ezPlaceData(275,$xtop,$rs_main2["itmdsc"],9,"left");
            $pdf->ezPlaceData($xleft+=112,$xtop,number_format($rs_main2["untprc"],"2"),9,"right");
            // $trndte = (empty($rs_main['tranfile1_trndte'])) ? NULL :  date("Y-m-d", strtotime($rs_main['tranfile1_trndte']));
            // $unit_cost = get_unitcost($rs_main2['itmcde'],$trndte);
            // $cost = $unit_cost * $rs_main2["itmqty"];

            $pdf->ezPlaceData($xleft+=110,$xtop,number_format($rs_main2["extprc"],"2"),9,"right");
            $profit = $rs_main2["extprc"] - $cost;

            $price_tot+=$rs_main2["untprc"];
            $cost_tot+=$rs_main2["extprc"];
            $profit_tot+=$profit;

            $xtop -= 15;

            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            // $pdf->ezPlaceData(10,$xtop-200,$select_db3,5,"left");
        }
  
        $pdf->line(25, $xtop, 770, $xtop); 
        $xtop -= 15;
        $xleft = 0;
        $pdf->ezPlaceData($xleft+=470,$xtop+=5,"<b>TOTAL:</b>",9,"left");
        $pdf->ezPlaceData($xleft+=110,$xtop,number_format($price_tot,2),9,"right");
        $pdf->ezPlaceData($xleft+=110,$xtop,number_format($cost_tot,2),9,"right");
        // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_tot,2),9,"right");
        $pdf->line(25, $xtop-=5, 770, $xtop); 
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

    $pdf->line(25, $xtop, 770, $xtop); 
    $xtop -= 15;
    $xleft = 0;
    $pdf->ezPlaceData($xleft+=443,$xtop+=5,"<b>GRAND TOTAL:</b>",8,"left");
    $pdf->ezPlaceData($xleft+=137,$xtop,number_format($price_gtot,2),9,"right");
    $pdf->ezPlaceData($xleft+=110,$xtop,number_format($cost_gtot,2),9,"right");
    // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_gtot,2),9,"right");
    $pdf->line(25, $xtop-=5, 770, $xtop); 

       
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