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

    // Log export activity
    $username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
    $username_full_name = '';
    if(isset($_SESSION['recid'])){
        $select_db_session_user='SELECT * FROM users where recid=?';
        $stmt_session_user = $link->prepare($select_db_session_user);
        $stmt_session_user->execute(array($_SESSION['recid']));
        $rs_session_user = $stmt_session_user->fetch();
        if($rs_session_user){
            $username_full_name = $rs_session_user["full_name"];
        }
    }
    $xtrndte_log = date("Y-m-d H:i:s");
    $xprog_module_log = 'SALES SALESMAN';
    $xactivity_log = (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".((isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." from Sales Salesman";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    $xreport_title = "List of items";
	

$inc_paid=0;
$inc_unpaid=0;
$inc_paid_smn=0;
$inc_unpaid_smn=0;
$inc_partial_paid=0;

   if (isset($_POST['chk_paid']) && $_POST['chk_paid']=='on')
   {
    $inc_paid=1;
   }

   if (isset($_POST['chk_partial_paid']) && $_POST['chk_partial_paid']=='on')
   {
    $inc_partial_paid=1;
   }

   if (isset($_POST['chk_unpaid']) && $_POST['chk_unpaid']=='on')
   {

    $inc_unpaid=1;

   }



    if (isset($_POST['chk_paid_smn']) && $_POST['chk_paid_smn']=='on')
   {
        $inc_paid_smn=1;
   }

   if (isset($_POST['chk_unpaid_smn']) && $_POST['chk_unpaid_smn']=='on')
   {
        $inc_unpaid_smn=1;
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
            $header.= " (Include Fully Paid)";
        } else {
            $header.= " (Exclude Fully Paid)";
        }
        if ($inc_partial_paid==1){
            $header.= " (Include Partially Paid)";
        } else {
            $header.= " (Exclude Partially Paid)";
        }
        if ($inc_unpaid==1 ){
            $header.= " (Include Unpaid)";
        } else {
            $header.= " (Exclude Unpaid)";
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
        $pdf->ezPlaceData($xleft+=45,$xtop,"<b>Paydetails</b>",10,'left');
        //$pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
        //$pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
        $pdf->ezPlaceData($xleft+=73,$xtop,"<b>Pay Date (Sales)</b>",10,'left');
        $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Pay Date (Salesman)</b>",10,'left');
        $pdf->ezPlaceData($xleft+=170,$xtop,"<b>Total</b>",10,'right');
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


    //paydate from to 
    if((isset($_POST['paydate_date_from']) && !empty($_POST['paydate_date_from'])) &&
    (isset($_POST['paydate_date_to']) && !empty($_POST['paydate_date_to'])) ){

        $_POST['paydate_date_from'] = date("Y-m-d", strtotime($_POST['paydate_date_from']));
        $_POST['paydate_date_to'] = date("Y-m-d", strtotime($_POST['paydate_date_to']));

        $xfilter .= " AND tranfile1.paydate>='".$_POST['paydate_date_from']."' AND tranfile1.paydate<='".$_POST['paydate_date_to']."'";
    }

    else if(isset($_POST['paydate_date_from']) && !empty($_POST['paydate_date_from'])){
        $_POST['paydate_date_from'] = date("Y-m-d", strtotime($_POST['paydate_date_from']));
        $xfilter .= " AND tranfile1.paydate>='".$_POST['paydate_date_from']."'";
    }

    else if(isset($_POST['paydate_date_to']) && !empty($_POST['paydate_date_to'])){
        $_POST['paydate_date_to'] = date("Y-m-d", strtotime($_POST['paydate_date_to']));
        $xfilter .= " AND tranfile1.paydate<='".$_POST['paydate_date_to']."'";
    }


    //paydate salesman
    if((isset($_POST['paydate_smn_date_from']) && !empty($_POST['paydate_smn_date_from'])) &&
    (isset($_POST['paydate_smn_date_to']) && !empty($_POST['paydate_smn_date_to'])) ){

        $_POST['paydate_smn_date_from'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_from']));
        $_POST['paydate_smn_date_to'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_to']));

        $xfilter .= " AND tranfile1.paydate_salesman>='".$_POST['paydate_smn_date_from']."' AND tranfile1.paydate_salesman<='".$_POST['paydate_smn_date_to']."'";
    }

    else if(isset($_POST['paydate_smn_date_from']) && !empty($_POST['paydate_smn_date_from'])){
        $_POST['paydate_smn_date_from'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_from']));
        $xfilter .= " AND tranfile1.paydate_salesman>='".$_POST['paydate_smn_date_from']."'";
    }

    else if(isset($_POST['paydate_smn_date_to']) && !empty($_POST['paydate_smn_date_to'])){
        $_POST['paydate_smn_date_to'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_to']));
        $xfilter .= " AND tranfile1.paydate_salesman<='".$_POST['paydate_smn_date_to']."'";
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

    // Build payment filter conditions based on checkboxes
    // Fully Paid = has paydate
    // Partially Paid = has at least 1 record in partial_payment table
    // Unpaid = no paydate
    // A record can be both Fully Paid AND Partially Paid

    $payment_conditions = array();

    if($inc_paid == 1){
        // Fully paid: has paydate
        $payment_conditions[] = "(tranfile1.paydate IS NOT NULL)";
    }

    if($inc_partial_paid == 1){
        // Partially paid: has at least one record in partial_payment table
        $payment_conditions[] = "(EXISTS (SELECT 1 FROM partial_payment WHERE partial_payment.docnum = tranfile1.docnum))";
    }

    if($inc_unpaid == 1){
        // Unpaid: no paydate AND no partial payments
        $payment_conditions[] = "(tranfile1.paydate IS NULL AND NOT EXISTS (SELECT 1 FROM partial_payment WHERE partial_payment.docnum = tranfile1.docnum))";
    }

    // If any conditions exist, add them with OR logic
    if(count($payment_conditions) > 0){
        $xfilter .= " AND (" . implode(" OR ", $payment_conditions) . ")";
    }else{
        // If no checkboxes selected, show nothing
        $xfilter .= " AND 1=0";
    }


    if(!($inc_paid_smn == 1 && $inc_unpaid_smn == 1)){
        if($inc_paid_smn == 1){
            //$xfilter .= " AND (com_pay IS NOT NULL AND com_pay !=0.00)";
            $xfilter .= " AND paydate_salesman IS NOT NULL";
        }else if($inc_unpaid_smn == 1){
            $xfilter .= " AND paydate_salesman IS NULL";
        }
    }
    
    
    if(isset($_POST['smn_search']) && !empty($_POST['smn_search'])){
        $xfilter_smn = " AND tranfile1.salesman_id='".$_POST['smn_search']."'";
    }


    $xorder_by = " ORDER BY mf_salesman.salesman_id ASC"; //default order by

    if(isset($_POST['orderby_route']) && !empty($_POST['orderby_route']) && $_POST['orderby_route'] !='-'){
        $xorder_by .= ", mf_routes.route_desc ".$_POST['orderby_route']; 
    }

    if(isset($_POST['orderby_buyer']) && !empty($_POST['orderby_buyer']) && $_POST['orderby_buyer'] !='-'){
        $xorder_by .= ", mf_buyers.buyer_name ".$_POST['orderby_buyer']; 
    }

    if(isset($_POST['orderby_ordernum']) && !empty($_POST['orderby_ordernum']) && $_POST['orderby_ordernum'] !='-'){
        $xorder_by .= ", tranfile1.ordernum ".$_POST['orderby_ordernum']; 
    }

    
    if(isset($_POST['orderby_paydate_sales']) && !empty($_POST['orderby_paydate_sales']) && $_POST['orderby_paydate_sales'] !='-'){
        $xorder_by .= ", tranfile1.paydate ".$_POST['orderby_paydate_sales']; 
    }


    // gets the total number of rows
    $count_query = "SELECT COUNT(*) as total_rows FROM tranfile1      
    LEFT JOIN mf_salesman ON tranfile1.salesman_id = mf_salesman.salesman_id      
    LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id      
    LEFT JOIN mf_routes ON tranfile1.route_id = mf_routes.route_id     
    WHERE true AND tranfile1.trncde='".$_POST['trncde_hidden']."' ".$xfilter." ".$xfilter_smn;
    $stmt_count = $link->prepare($count_query);
    $stmt_count->execute();
    while($rs_row_count = $stmt_count->fetch()){
        $table_row_count = (int)$rs_row_count['total_rows'];
    }

    $grand_total = 0;
    $xpre_salesman='**';
    $xpre_salesman_id='**';
    $xcommission = 0;
    $xtot_commission = 0;
    $xrow_comission = 0;
    $xactual_rows_count = 0;
    $xshow_top_sales_header = true;
    $salesman_subtotal = 0;

    //actual query
    $select_db="SELECT tranfile1.paydate_salesman as 'paydate_salesman', tranfile1.ship_status as tranfile1_ship_status, mf_routes.route_desc as mf_routes_route_desc, tranfile1.shipto as tranfile1_shipto,tranfile1.salesman_id as tranfile1_salesman_id,tranfile1.docnum as tranfile1_docnum,
    tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid, tranfile1.ordernum as tranfile1_ordernum,
    mf_salesman.recid as mf_salesman1_recid, mf_salesman.salesman_name as mf_salesman_name, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    mf_salesman.salesman_name, mf_salesman.salesman_id, mf_buyers.buyer_name as mf_buyer_name, mf_salesman.commission as mf_salesman_commission,com_pay FROM tranfile1 
    LEFT JOIN mf_salesman ON tranfile1.salesman_id = mf_salesman.salesman_id 
    LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id 
    LEFT JOIN mf_routes ON tranfile1.route_id = mf_routes.route_id
    WHERE true AND tranfile1.trncde='".$_POST['trncde_hidden']."' ".$xfilter." ".$xfilter_smn." ".$xorder_by;

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $main_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    // Resolve partial payment schema once per request.
    $partial_date_column = "date_paid";
    try{
        $stmt_chk_col = $link->query("SHOW COLUMNS FROM partial_payment LIKE 'date'");
        if($stmt_chk_col->fetch(PDO::FETCH_ASSOC)){
            $partial_date_column = "date";
        }
    }catch(Exception $e){
        $partial_date_column = "date_paid";
    }

    $has_check_number_col = false;
    try{
        $stmt_chk_num = $link->query("SHOW COLUMNS FROM partial_payment LIKE 'check_number'");
        if($stmt_chk_num->fetch(PDO::FETCH_ASSOC)){
            $has_check_number_col = true;
        }
    }catch(Exception $e){
        $has_check_number_col = false;
    }

    // Prefetch partial payments for all docnums to avoid N+1 queries in the main loop.
    $partial_payment_map = array();
    if(count($main_rows) > 0){
        $docnum_lookup = array();
        foreach($main_rows as $row_docnum){
            $docnum_key = isset($row_docnum["tranfile1_docnum"]) ? trim((string)$row_docnum["tranfile1_docnum"]) : "";
            if($docnum_key !== ""){
                $docnum_lookup[$docnum_key] = true;
            }
        }

        $all_docnums = array_keys($docnum_lookup);
        if(count($all_docnums) > 0){
            $check_number_sql = $has_check_number_col ? "check_number" : "'' AS check_number";
            foreach(array_chunk($all_docnums, 500) as $docnum_chunk){
                $placeholders = implode(',', array_fill(0, count($docnum_chunk), '?'));
                $select_partial_bulk = "SELECT docnum, recid, amount, `".$partial_date_column."` AS date_paid, ".$check_number_sql." FROM partial_payment WHERE docnum IN (".$placeholders.") ORDER BY docnum ASC, `".$partial_date_column."` ASC, recid ASC";
                $stmt_partial_bulk = $link->prepare($select_partial_bulk);
                $stmt_partial_bulk->execute($docnum_chunk);

                while($rs_partial_bulk = $stmt_partial_bulk->fetch(PDO::FETCH_ASSOC)){
                    $docnum_key = trim((string)$rs_partial_bulk["docnum"]);
                    if(!isset($partial_payment_map[$docnum_key])){
                        $partial_payment_map[$docnum_key] = array();
                    }
                    $partial_payment_map[$docnum_key][] = $rs_partial_bulk;
                }
            }
        }
    }

    // Cache fallback salesman (-None) once.
    $none_salesman_id = null;
    $none_salesman_name = null;
    $select_db_empty_salesman = "SELECT salesman_id, salesman_name FROM mf_salesman WHERE salesman_name='-None' LIMIT 1";
    $stmt_main_empty_salesman = $link->prepare($select_db_empty_salesman);
    $stmt_main_empty_salesman->execute();
    $rs_main_empty_salesman = $stmt_main_empty_salesman->fetch(PDO::FETCH_ASSOC);
    if($rs_main_empty_salesman){
        $none_salesman_id = $rs_main_empty_salesman["salesman_id"];
        $none_salesman_name = $rs_main_empty_salesman["salesman_name"];
    }

    foreach($main_rows as $rs_main){    

        $xactual_rows_count++;

        if($rs_main['tranfile1_salesman_id'] == null){
            if($none_salesman_id !== null){
                $rs_main['tranfile1_salesman_id'] = $none_salesman_id;
            }
            if($none_salesman_name !== null){
                $rs_main['mf_salesman_name'] = $none_salesman_name;
            }
        }
        
        if($xshow_top_sales_header == true){
            $pdf->ezPlaceData(25,$xtop,"Sales",10,'left');    
            $xtop-=5; 
            $xshow_top_sales_header = false;
        }

        // $xtop-=12;

        //$pdf->ezPlaceData(25,$xtop,"xpre_salesman_id ".$xpre_salesman_id,10,'left');    
        //$pdf->ezPlaceData(200,$xtop,"rs_main id ".$rs_main['tranfile1_salesman_id'],10,'left');    

        // $xtop-=10; 

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

        if(isset($rs_main["paydate_salesman"]) && !empty($rs_main["paydate_salesman"])){
            $rs_main["paydate_salesman"] = date("m-d-Y",strtotime($rs_main["paydate_salesman"]));
            $rs_main["paydate_salesman"] = str_replace('-','/',$rs_main["paydate_salesman"]);
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
            $rs_main["tranfile1_paydetails"] = trim_str($rs_main["tranfile1_paydetails"],95,9);
            $rs_main["tranfile1_ordernum"] = trim_str($rs_main["tranfile1_ordernum"],100,9);
        }

        $xplus_move = 5;

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
                    $pdf->ezPlaceData(440+$xplus_move,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
                    $pdf->ezPlaceData(655+$xplus_move,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
                    //$smn_com = $subtotal*($xcommission/100);
                    $pdf->ezPlaceData(765+$xplus_move,$xtop,"<b>".number_format($salesman_subtotal,"2")."</b>",9,"right");

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
                    $pdf->ezPlaceData(435+$xplus_move,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
                    $pdf->ezPlaceData(650+$xplus_move,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
                    //$smn_com = $subtotal*($xcommission/100);
                    $pdf->ezPlaceData(760+$xplus_move,$xtop,"<b>".number_format($salesman_subtotal,"2")."</b>",9,"right");
                    $pdf->ezPlaceData(435+$xplus_move,$xtop-=15,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
                }                


                $xtop -= 15;

                sales_return($xactual_rows_count,$table_row_count,true,$xpre_salesman_id);

                $xtop -= 15;

                //$pdf->ezPlaceData(650,$xtop,"<b>".number_format($smn_com,"2")."</b>",9,"right");
                $xtot_commission = $xtot_commission + $salesman_subtotal;

                $salesman_subtotal = 0; 

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
        $pdf->ezPlaceData($xleft+=45,$xtop,$rs_main["tranfile1_paydetails"],9,"left");
        //$pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["tranfile1_shipto"]??'',9,"left");
        $pdf->ezPlaceData($xleft+=105,$xtop,$rs_main["tranfile1_paydate"],9,"left");
        $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["paydate_salesman"]??'',9,"left");
        $pdf->ezPlaceData($xleft+=170,$xtop,number_format($rs_main["tranfile1_trntot"],"2"),9,"right");
        $xrow_comission = $rs_main["com_pay"];
        $salesman_subtotal += $xrow_comission;

        $pdf->ezPlaceData($xleft+=110,$xtop,number_format($xrow_comission,"2"),9,"right");
        //$pdf->ezPlaceData($xleft+=10,$xtop,trim_str($rs_main["com_pay"],100,9)??'',9,"left");

        $subtotal=$subtotal+$rs_main["tranfile1_trntot"];
        $xtop -= 15;

        // Check for partial payments and display sub-rows
        $docnum_for_partial = trim((string)$rs_main["tranfile1_docnum"]);
        $trntot_for_partial = floatval($rs_main["tranfile1_trntot"]);

        $partial_payments = isset($partial_payment_map[$docnum_for_partial]) ? $partial_payment_map[$docnum_for_partial] : array();
        $total_partial_paid = 0;
        // If there are partial payments, display them as sub-rows
        if(count($partial_payments) > 0){
            foreach($partial_payments as $pp){
                $pp_check_number_raw = (!empty($pp['check_number'])) ? $pp['check_number'] : 'None';
                // Truncate check number if too long (max 15 chars total display)
                if(strlen($pp_check_number_raw) > 15){
                    $pp_check_number = substr($pp_check_number_raw, 0, 12) . '...';
                }else{
                    $pp_check_number = $pp_check_number_raw;
                }
                $pp_date_paid = '';
                if(!empty($pp['date_paid'])){
                    $pp_date_paid = date("m/d/Y", strtotime($pp['date_paid']));
                }
                $pp_amount = floatval($pp['amount']);
                $total_partial_paid += $pp_amount;

                if($_POST['txt_output_type']=='tab'){
                    // XLS output - columns adjacent to amount
                    $pdf->ezPlaceData(1,$xtop,"",8,"left"); // Empty col A
                    $pdf->ezPlaceData(2,$xtop,"",8,"left"); // Empty col B
                    $pdf->ezPlaceData(3,$xtop,"",8,"left"); // Empty col C
                    $pdf->ezPlaceData(4,$xtop,"",8,"left"); // Empty col D
                    $pdf->ezPlaceData(5,$xtop,"",8,"left"); // Empty col E
                    $pdf->ezPlaceData(420,$xtop,"Check #: ".$pp_check_number,8,"left"); // Col F - moved left
                    $pdf->ezPlaceData(550,$xtop,$pp_date_paid,8,"left"); // Col G - date
                    $pdf->ezPlaceData(655,$xtop,number_format($pp_amount,"2"),8,"right"); // Col H - Amount
                }else{
                    // PDF output - moved check# left for more spacing
                    $pdf->ezPlaceData(420,$xtop,"Check #: ".$pp_check_number,8,"left");
                    $pdf->ezPlaceData(550,$xtop,$pp_date_paid,8,"left");
                    $pdf->ezPlaceData(655,$xtop,number_format($pp_amount,"2"),8,"right");
                }

                $xtop -= 12;

                if($xtop <= 60){
                    $pdf->ezNewPage();
                    $xtop = 515;
                }
            }

            // Remaining Balance row
            $remaining_balance = $trntot_for_partial - $total_partial_paid;

            if($_POST['txt_output_type']=='tab'){
                $pdf->ezPlaceData(1,$xtop,"",8,"left");
                $pdf->ezPlaceData(2,$xtop,"",8,"left");
                $pdf->ezPlaceData(3,$xtop,"",8,"left");
                $pdf->ezPlaceData(4,$xtop,"",8,"left");
                $pdf->ezPlaceData(5,$xtop,"",8,"left");
                $pdf->ezPlaceData(420,$xtop,"<b>Remaining Balance:</b>",8,"left");
                $pdf->ezPlaceData(550,$xtop,"",8,"left");
                $pdf->ezPlaceData(655,$xtop,"<b>".number_format($remaining_balance,"2")."</b>",8,"right");
            }else{
                $pdf->ezPlaceData(420,$xtop,"<b>Remaining Balance:</b>",8,"left");
                $pdf->ezPlaceData(655,$xtop,"<b>".number_format($remaining_balance,"2")."</b>",8,"right");
            }

            $xtop -= 15;

            if($xtop <= 60){
                $pdf->ezNewPage();
                $xtop = 515;
            }
        }

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }
        
    }
   


        if(isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
        {   


            //$smn_com = $subtotal*($xcommission/100);

            $pdf->ezPlaceData(1,$xtop,"",9,"left");
            $pdf->ezPlaceData(2,$xtop,"",9,"left");
            $pdf->ezPlaceData(3,$xtop,"",9,"left");
            $pdf->ezPlaceData(4,$xtop,"",9,"left");
            $pdf->ezPlaceData(5,$xtop,"",9,"left");
            $pdf->ezPlaceData(6,$xtop,"",9,"left");
            $pdf->ezPlaceData(435,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
            $pdf->ezPlaceData(650,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
            $pdf->ezPlaceData(651,$xtop,"<b>".number_format($salesman_subtotal,"2")."</b>",9,"right");  // Changed from 760 to 540

            $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");
            $pdf->ezPlaceData(2,$xtop,"",9,"left");
            $pdf->ezPlaceData(3,$xtop,"",9,"left");
            $pdf->ezPlaceData(4,$xtop,"",9,"left");
            $pdf->ezPlaceData(5,$xtop,"",9,"left");
            $pdf->ezPlaceData(6,$xtop,"",9,"left");
            $pdf->ezPlaceData(435,$xtop,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");

            $pdf->ezPlaceData(1,$xtop-=15,"",9,"left");

        }else{
            
            $pdf->ezPlaceData(435+$xplus_move,$xtop,"<b>Total Sales for ".$xpre_salesman."</b>",9,"left");
            $pdf->ezPlaceData(650+$xplus_move,$xtop,"<b>".number_format($subtotal,"2")."</b>",9,"right");
            //$smn_com = $subtotal*($xcommission/100);
            $pdf->ezPlaceData(760+$xplus_move,$xtop,"<b>".number_format($salesman_subtotal,"2")."</b>",9,"right");
            $pdf->ezPlaceData(435+$xplus_move,$xtop-=15,"<b>Commission at ".number_format($xcommission,"2")." % </b>",9,"left");
        }

        $xtop -= 15;
        sales_return($xactual_rows_count,$table_row_count,false,$xpre_salesman_id);
        $xtop -= 15;
        $xtot_commission = $xtot_commission + $salesman_subtotal;
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
        $pdf->ezPlaceData(7,$xtop,"",9,"left");
        $pdf->ezPlaceData(570,$xtop,"<b> Total All Commisions:</b>",9 ,'left');
        $pdf->ezPlaceData(760,$xtop,"<b>".number_format($xtot_commission,2)."</b>",9 ,'right');

    }else{
        $pdf->ezPlaceData(570+$xplus_move,$xtop,"<b> Total All Commisions:</b>",9 ,'left');
        $pdf->ezPlaceData(760+$xplus_move,$xtop,"<b>".number_format($xtot_commission,2)."</b>",9 ,'right');
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

    function sales_return($passed_row_count, $xtable_row_count,$xinclude,$salesman_row_id)
    {

        $xfilter = '';
        global $link,$pdf, $xleft, $xtop, $xpre_salesman, $xpre_salesman_id,$xsal_ret_tot, $subtotal;


        $select_ret="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.salesman_id as tranfile1_salesman_id,tranfile1.docnum as tranfile1_docnum,
        tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid, tranfile1.ordernum as tranfile1_ordernum,
        mf_salesman.recid as mf_salesman1_recid, mf_salesman.salesman_name as mf_salesman_name, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
        mf_salesman.salesman_name, mf_salesman.salesman_id, mf_buyers.buyer_name as mf_buyer_name, mf_salesman.commission as mf_salesman_commission FROM tranfile1 
        LEFT JOIN mf_salesman ON tranfile1.salesman_id = mf_salesman.salesman_id 
        LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id 
        WHERE true AND trncde='SRT' ".$xfilter." and tranfile1.salesman_id='" .$salesman_row_id . "' ORDER BY mf_salesman.salesman_id ASC,tranfile1.trndte ASC";

        $xsal_ret_tot = 0;
        $stmt_ret	= $link->prepare($select_ret);
        $stmt_ret->execute();
        $display_header=true;

        while($rs_ret = $stmt_ret->fetch()){    

           if($xtop <= 100)
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
            $pdf->ezPlaceData($xleft+=170,$xtop,number_format($rs_ret["tranfile1_trntot"],"2"),9,"right");

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

        if($passed_row_count <= $xtable_row_count && $xinclude==true){
            $pdf->ezPlaceData(25,$xtop,"Sales",10,'left'); 
        }

    }


?>



