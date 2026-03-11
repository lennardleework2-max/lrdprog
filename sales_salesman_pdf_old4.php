<?php
    // var_dump($_POST);
// error_reporting(E_ALL);
// ini_set('display_errors',true);
//ini_set('memory_limit',-1);
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');

    ob_start();

    $xreport_title = "List of items";
	

$inc_paid=0;
$inc_unpaid=0;

   if (isset($_POST['chk_paid']) && $_POST['chk_paid']=='on')
   {
    $inc_paid=1;
   }

   if (isset($_POST['chk_unpaid']) && $_POST['chk_unpaid']=='on')
   {
    
    $inc_unpaid=1;

   }

   /*
echo "<pre>";
   var_dump($inc_paid);
   echo "<pre>";
   var_dump($inc_unpaid);
   die();
*/


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
        $pdf->ezPlaceData($xleft, $xtop,"<b>Salesman Report</b>", 15, 'left' );
        $xtop   -= 15;
        $header="<b>Pdf Report by: ".$_SESSION['userdesc']." </b>";
        if ($inc_paid==1){
            $header.= " (Include Paid Commission)";
        } else {
            $header.= " (Exclude Paid Commission)";
        }
        if ($inc_unpaid==1 ){
            $header.= " (Include Unpaid Commission)";
        } else {
            $header.= " (Exclude Unpaid Commission)";
        }
        $pdf->ezPlaceData($xleft, $xtop,$header, 9, 'left' );
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
        $pdf->ezPlaceData($xleft,$xtop,"<b>Order Num.</b>",10,'left');
        $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Tran. Date</b>",10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Buyer</b>",10,'left');
        $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Route</b>",10,'left');
        $pdf->ezPlaceData($xleft+=55,$xtop,"<b>Status</b>",10,'left');
        //$pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
        //$pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Pay Date</b>",10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Payment Details</b>",10,'left');
        $pdf->ezPlaceData($xleft+=200,$xtop,"<b>Total</b>",10,'right');
        $pdf->ezPlaceData($xleft+=10,$xtop,"<b>Commission Payment</b>",10,'left');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP

    $xfilter = '';
    $xfilter_smn = '';
    $xorder = '';
    $xsal_ret_tot =0;


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

    //(p,u)
    //(p)
    //(u)
    //()

    //1 possibility (P)
    // AND paydate IS NOT NULL

    //2 possibility (U)
    // AND paydate IS NULL

    //3,4 posssibility (P, U) && ()
    //LAHAT

    if(!($inc_paid == 1 && $inc_unpaid == 1)){
        if($inc_paid == 1){
            $xfilter .= " AND paydate IS NOT NULL";
        }else if($inc_unpaid == 1){
            $xfilter .= " AND paydate IS NULL";
        }

    }
    
    if(isset($_POST['smn_search']) && !empty($_POST['smn_search'])){
        $xfilter_smn = " AND tranfile1.salesman_id='".$_POST['smn_search']."'";
    }


    $xorder_by = "";

    if(isset($_POST['orderby_route']) && !empty($_POST['orderby_route']) && $_POST['orderby_route'] !='-'){
        $xorder_by = " ORDER BY mf_routes.route_desc ".$_POST['orderby_route']; 
    }

    if(isset($_POST['orderby_buyer']) && !empty($_POST['orderby_buyer']) && $_POST['orderby_buyer'] !='-'){

        if($xorder_by == ""){
            $xorder_by = " ORDER BY mf_buyers.buyer_name ".$_POST['orderby_buyer']; 
        }else{
            $xorder_by .= ", mf_buyers.buyer_name ".$_POST['orderby_buyer']; 
        }

    }

    if(isset($_POST['orderby_ordernum']) && !empty($_POST['orderby_ordernum']) && $_POST['orderby_ordernum'] !='-'){

        if($xorder_by == ""){
            $xorder_by = " ORDER BY tranfile1.ordernum ".$_POST['orderby_ordernum']; 
        }else{
            $xorder_by .= ", tranfile1.ordernum ".$_POST['orderby_ordernum']; 
        }
    }


        // if(isset($_POST['cus_to']) && !empty($_POST['cus_to'])){
        //     $xfilter .= " AND customerfile.salesman_name<='".$_POST['cus_to']."'";
        // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (tranfile1.paydate!='' OR tranfile1.paydate IS NOT NULL)";
        // }

    $select_db="SELECT tranfile1.ship_status as tranfile1_ship_status, mf_routes.route_desc as mf_routes_route_desc, tranfile1.shipto as tranfile1_shipto,tranfile1.salesman_id as tranfile1_salesman_id,tranfile1.docnum as tranfile1_docnum,
    tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid, tranfile1.ordernum as tranfile1_ordernum,
    mf_salesman.recid as mf_salesman1_recid, mf_salesman.salesman_name as mf_salesman_name, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    mf_salesman.salesman_name, mf_salesman.salesman_id, mf_buyers.buyer_name as mf_buyer_name, mf_salesman.commission as mf_salesman_commission,com_pay FROM tranfile1 
    LEFT JOIN mf_salesman ON tranfile1.salesman_id = mf_salesman.salesman_id 
    LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id 
    LEFT JOIN mf_routes ON tranfile1.route_id = mf_routes.route_id
    WHERE true AND tranfile1.trncde='".$_POST['trncde_hidden']."' ".$xfilter." ".$xfilter_smn." ".$xorder_by;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    $xpre_salesman='**';
    $xpre_salesman_id='**';
    $xcommission = 0;
    $xtot_commission = 0;
    $xrow_comission = 0;

    //showing the sales

    while($rs_main = $stmt_main->fetch()){    


        if($rs_main['tranfile1_salesman_id'] == null){
            //search for where the salesman id is null
            $select_db_empty_salesman="SELECT * FROM mf_salesman WHERE salesman_name='-None' LIMIT 1";
            $stmt_main_empty_salesman	= $link->prepare($select_db_empty_salesman);
            $stmt_main_empty_salesman->execute();
            $rs_main_empty_salesman = $stmt_main_empty_salesman->fetch();
            $rs_main['tranfile1_salesman_id'] =  $rs_main_empty_salesman['salesman_id'];
            $rs_main['mf_salesman_name'] =  $rs_main_empty_salesman['salesman_name'];
        }

        if($rs_main["mf_salesman_name"] != $xpre_salesman || $xpre_salesman == "**"){
            $pdf->ezPlaceData(25,$xtop,"Sales",10,'left');    
            $xtop-=10;
        }




        $com_pay = $rs_main["com_pay"]??'';
        // if ($inc_paid==0 && $com_pay!='' ){
        //     continue;
        // }
        // if ($inc_unpaid==0 && $com_pay=='' ){
        //     continue;
        // }
        
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
           // $rs_main["mf_buyer_name"] = $rs_main["mf_buyer_name"];
            $rs_main["tranfile1_shipto"] = $rs_main["tranfile1_shipto"];
            $rs_main["tranfile1_paydetails"] = $rs_main["tranfile1_paydetails"];
            $rs_main["tranfile1_ordernum"] = $rs_main["tranfile1_ordernum"];
		}else{
          //  $rs_main["mf_buyer_name"] = trim_str($rs_main["mf_buyer_name"],65,9);
            $rs_main["tranfile1_shipto"] = trim_str($rs_main["tranfile1_shipto"],120,9);
            $rs_main["tranfile1_paydetails"] = trim_str($rs_main["tranfile1_paydetails"],140,9);
            $rs_main["tranfile1_ordernum"] = trim_str($rs_main["tranfile1_ordernum"],100,9);
        }

        //echo "<pre>", var_dump($rs_main["tranfile1_ordernum"]), "</pre>";
        //var_dump();

        if ($rs_main["mf_salesman_name"] != $xpre_salesman)
        {
            if ($xpre_salesman !='**'){

                if(isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab'){

                    $pdf->ezPlaceData(1,$xtop,"",9,"left");
                    $pdf->ezPlaceData(2,$xtop,"",9,"left");
                    $pdf->ezPlaceData(3,$xtop,"",9,"left");
                    $pdf->ezPlaceData(4,$xtop,"",9,"left");
                    $pdf->ezPlaceData(5,$xtop,"",9,"left");
                    $pdf->ezPlaceData(6,$xtop,"",9,"left");
                    $pdf->ezPlaceData(435,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
                    $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
                    $smn_com = $subtotal*($xcommission/100);
                    $pdf->ezPlaceData(760,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");

                    // Remove this line completely - don't place Commission text in any position
                    // $pdf->ezPlaceData(450,$xtop-=15,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");

                    // Instead, add empty cells and place Commission text at position 435 (Column I)
                    $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");
                    $pdf->ezPlaceData(2,$xtop,"",9,"left");
                    $pdf->ezPlaceData(3,$xtop,"",9,"left");
                    $pdf->ezPlaceData(4,$xtop,"",9,"left");
                    $pdf->ezPlaceData(5,$xtop,"",9,"left");
                    $pdf->ezPlaceData(6,$xtop,"",9,"left");
                    $pdf->ezPlaceData(435,$xtop,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
                    $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");

                }else{
                    $pdf->ezPlaceData(435,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
                    $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
                    $smn_com = $subtotal*($xcommission/100);
                    $pdf->ezPlaceData(760,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");
                    $pdf->ezPlaceData(435,$xtop-=15,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
                }                


                $xtop -= 15;

                sales_return();

                $xtop -= 15;

                //$pdf->ezPlaceData(650,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");
                $xtot_commission = $xtot_commission + $smn_com;

                // $xtop -= 15;
 
            }

     
            // Clean the value before using it
            $salesman_name = $rs_main["mf_salesman_name"];
            // if ($salesman_name == "-=None" || $salesman_name == "=-None") {
            //     $salesman_name = "-None"; // or "N/A" or whatever you prefer
            // }
            $salesman_name = $rs_main["mf_salesman_name"]; // "-None"
            $salesman_name = "'" . $salesman_name; // Add single quote prefix

            $xtop -= 15;
            $xpre_salesman=$rs_main["mf_salesman_name"];
            $xpre_salesman_id=$rs_main["tranfile1_salesman_id"];
            $xcommission = $rs_main["mf_salesman_commission"];
            $subtotal=0;
        }

        $xleft = 25;
        //$pdf->ezPlaceData($xleft,$xtop,$rs_main["tranfile1_docnum"],9,"left");
        //$pdf->ezPlaceData($xleft,$xtop,"DR #190",9,"left");
        
        $pdf->ezPlaceData($xleft,$xtop,$rs_main["tranfile1_ordernum"]??'',9,"left");
        
        
        //die(); 
        $pdf->ezPlaceData($xleft+=110,$xtop,$rs_main["tranfile1_trndte"],9,"left");
        
        $pdf->ezPlaceData($xleft+=60,$xtop,trim_str($rs_main["mf_buyer_name"],70,9)??'',9,"left");
        $pdf->ezPlaceData($xleft+=80,$xtop,$rs_main["mf_routes_route_desc"],9,"left");
        //$pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["mf_buyer_name"]??'',9,"left");
        $pdf->ezPlaceData($xleft+=55,$xtop,$rs_main["tranfile1_ship_status"],9,"left");
        //$pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["tranfile1_shipto"]??'',9,"left");
        $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["tranfile1_paydate"],9,"left");
        $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["tranfile1_paydetails"]??'',9,"left");
        $pdf->ezPlaceData($xleft+=200,$xtop,number_format($rs_main["tranfile1_trntot"],"2"),9,"right");
        $xrow_comission = $rs_main["tranfile1_trntot"] * ($rs_main['mf_salesman_commission']/100);

        $pdf->ezPlaceData($xleft+=110,$xtop,number_format($xrow_comission,"2"),9,"right");
        //$pdf->ezPlaceData($xleft+=10,$xtop,trim_str($rs_main["com_pay"],100,9)??'',9,"left");

        $subtotal=$subtotal+$rs_main["tranfile1_trntot"];
        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }
        
    }


        if(isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
        {   

            $pdf->ezPlaceData(1,$xtop,"",9,"left");
            $pdf->ezPlaceData(2,$xtop,"",9,"left");
            $pdf->ezPlaceData(3,$xtop,"",9,"left");
            $pdf->ezPlaceData(4,$xtop,"",9,"left");
            $pdf->ezPlaceData(5,$xtop,"",9,"left");
            $pdf->ezPlaceData(6,$xtop,"",9,"left");
            $pdf->ezPlaceData(435,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
            $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
            $smn_com = $subtotal*($xcommission/100);

            $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");
            $pdf->ezPlaceData(2,$xtop,"",9,"left");
            $pdf->ezPlaceData(3,$xtop,"",9,"left");
            $pdf->ezPlaceData(4,$xtop,"",9,"left");
            $pdf->ezPlaceData(5,$xtop,"",9,"left");
            $pdf->ezPlaceData(6,$xtop,"",9,"left");
            $pdf->ezPlaceData(760,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");
            $pdf->ezPlaceData(435,$xtop-=15,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
            $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");
        }else{
            $pdf->ezPlaceData(435,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
            $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
            $smn_com = $subtotal*($xcommission/100);
            $pdf->ezPlaceData(760,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");
            $pdf->ezPlaceData(435,$xtop-=15,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
        }

                $xtop -= 15;

                sales_return();

                $xtop -= 15;
                // $pdf->ezPlaceData(440,$xtop,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
                // $smn_com = $subtotal*($xcommission/100);
                // $pdf->ezPlaceData(650,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");
                $xtot_commission = $xtot_commission + $smn_com;

                $xtop -= 5;
                

    $pdf->line(25, $xtop, 770, $xtop); 
    $xtop -= 15;

    if(isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
    {  
        $pdf->ezPlaceData(1,$xtop,"",9,"left");
        $pdf->ezPlaceData(2,$xtop,"",9,"left");
        $pdf->ezPlaceData(3,$xtop,"",9,"left");
        $pdf->ezPlaceData(4,$xtop,"",9,"left");
        $pdf->ezPlaceData(5,$xtop,"",9,"left");
        $pdf->ezPlaceData(6,$xtop,"",9,"left");
        $pdf->ezPlaceData(570,$xtop,"<b> Total All Commisions:</b>",9 ,'left');
        $pdf->ezPlaceData(760,$xtop,"<b>".number_format($xtot_commission,2)."</b>",9 ,'right');

    }else{
        $pdf->ezPlaceData(570,$xtop,"<b> Total All Commisions:</b>",9 ,'left');
        $pdf->ezPlaceData(760,$xtop,"<b>".number_format($xtot_commission,2)."</b>",9 ,'right');
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


    // function trim_str($string,$max_wid,$fsize)
    // {   
    //     global $pdf;
    //     if(get_class($pdf) == 'tab_ezpdf')
    //     {
    //         return $string; // Don't trim for XLSX output
    //     }
        
    //     // Check if the method exists before calling it
    //     if(!method_exists($pdf, 'getTextWidth')) {
    //         return $string;
    //     }
        
    //     $xarr_str = str_split($string);
    //     $max_wid -= 5;
    //     $xxstr = "";
    //     $xcut = false;
    //     foreach ($xarr_str as $value) {
    //         $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
    //         if($xstr_wid > $max_wid)
    //         {   
    //             $xcut = true;
    //             break;
    //         }
    //         $xxstr = $xxstr.$value;
    //     }
    //     if($xcut)
    //     {   
    //         $xxstr = $xxstr.'...';
    //     }
    //     return $xxstr;
    // }

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

    function sales_return()
    {

        $xfilter = '';
        global $link,$pdf, $xleft, $xtop, $xpre_salesman, $xpre_salesman_id,$xsal_ret_tot, $subtotal;
 

        $select_ret="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.salesman_id as tranfile1_salesman_id,tranfile1.docnum as tranfile1_docnum,
        tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid, tranfile1.ordernum as tranfile1_ordernum,
        mf_salesman.recid as mf_salesman1_recid, mf_salesman.salesman_name as mf_salesman_name, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
        mf_salesman.salesman_name, mf_salesman.salesman_id, mf_buyers.buyer_name as mf_buyer_name, mf_salesman.commission as mf_salesman_commission FROM tranfile1 
        LEFT JOIN mf_salesman ON tranfile1.salesman_id = mf_salesman.salesman_id 
        LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id 
        WHERE true AND trncde='SRT' ".$xfilter." and tranfile1.salesman_id='" .$xpre_salesman_id . "' ORDER BY mf_salesman.salesman_name ASC,tranfile1.trndte ASC";

        //var_dump($select_ret);
        //die();
        $xsal_ret_tot = 0;
        $stmt_ret	= $link->prepare($select_ret);
        $stmt_ret->execute();
        $display_header=true;

        while($rs_ret = $stmt_ret->fetch()){    

           if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            if ($display_header==true)
            {
                $xtop -= 15;
                $xleft = 25;
                $pdf->ezPlaceData($xleft,$xtop,"Sales Return",10,'left');                     
                $display_header=false;
            }

            $xtop -= 15;
            $xleft = 25;

        
            $xtrndte = date("m-d-Y",strtotime($rs_ret["tranfile1_trndte"]));
            $xtrndte = str_replace('-','/',$xtrndte);
        

            //$pdf->ezPlaceData($xleft,$xtop,$rs_ret["tranfile1_docnum"],9,"left");
            
            
            $pdf->ezPlaceData($xleft,$xtop,$rs_ret["tranfile1_ordernum"]??'',9,"left");            
            $pdf->ezPlaceData($xleft+=110,$xtop,$xtrndte,9,"left");
            
            $pdf->ezPlaceData($xleft+=60,$xtop,$rs_ret["mf_buyer_name"]??'',9,"left");
            
            //$pdf->ezPlaceData($xleft+=75,$xtop,$rs_ret["tranfile1_shipto"]??'',9,"left");
            $pdf->ezPlaceData($xleft+=210,$xtop,$rs_ret["tranfile1_paydate"],9,"left");
            $pdf->ezPlaceData($xleft+=75,$xtop,$rs_ret["tranfile1_paydetails"]??'',9,"left");
            $pdf->ezPlaceData($xleft+105,$xtop,number_format($rs_ret["tranfile1_trntot"],"2"),9,"right");

            $xsal_ret_tot = $xsal_ret_tot +$rs_ret["tranfile1_trntot"];

 
        }

        if ($xsal_ret_tot!=0)
        {

            if(isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
            {  
                $xtop -= 15;
                $pdf->ezPlaceData(1,$xtop,"",9,"left");
                $pdf->ezPlaceData(2,$xtop,"",9,"left");
                $pdf->ezPlaceData(3,$xtop,"",9,"left");
                $pdf->ezPlaceData(4,$xtop,"",9,"left");
                $pdf->ezPlaceData(5,$xtop,"",9,"left");
                $pdf->ezPlaceData(6,$xtop,"",9,"left");
                $pdf->ezPlaceData(435,$xtop,"<b>Total Sales Returns for ".$xpre_salesman."</b>",9,"left");
                $pdf->ezPlaceData(650,$xtop,"<b>".number_format($xsal_ret_tot,"2")."</b>",9,"right");

                $xtop -= 15;    
                $subtotal= $subtotal - $xsal_ret_tot;

                $pdf->ezPlaceData(1,$xtop,"",9,"left");
                $pdf->ezPlaceData(2,$xtop,"",9,"left");
                $pdf->ezPlaceData(3,$xtop,"",9,"left");
                $pdf->ezPlaceData(4,$xtop,"",9,"left");
                $pdf->ezPlaceData(5,$xtop,"",9,"left");
                $pdf->ezPlaceData(6,$xtop,"",9,"left");
                $pdf->ezPlaceData(435,$xtop,"<b>Net Sales for ".$xpre_salesman."</b>",9,"left");
                $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
                $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");

            }else{
                $xtop -= 15;
                $pdf->ezPlaceData(435,$xtop,"<b>Total Sales Returns for ".$xpre_salesman."</b>",9,"left");
                $pdf->ezPlaceData(650,$xtop,"<b>".number_format($xsal_ret_tot,"2")."</b>",9,"right");

                $xtop -= 15;    
                $subtotal= $subtotal - $xsal_ret_tot;
                $pdf->ezPlaceData(435,$xtop,"<b>Net Sales for ".$xpre_salesman."</b>",9,"left");
                $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
            }


        }

    }


?>