<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
	require_once('ezpdfclass/class/class.ezpdf.php');

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
    $xprog_module_log = 'TRANSACTION';
    $xactivity_log = 'export_pdf';
    $xremarks_log = "Exported PDF from Transaction";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

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


		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>".$_POST["progname_hidden"]."</b>", 15, 'left' );
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
        $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Shop Name</b>",10,'left');
        $pdf->ezPlaceData($xleft+=100,$xtop,"<b>Tran. Date</b>",10,'left');
        $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
        $pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
        $pdf->ezPlaceData($xleft+=85,$xtop,"<b>Payment Details</b>",10,'left');
        $pdf->ezPlaceData($xleft+=215,$xtop,"<b>Total</b>",10,'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';

    if(isset($_POST["first_load_hidden"]) && $_POST["first_load_hidden"]!== "first_load"){

        if(isset($_POST['orderby_search_h']) && !empty($_POST['orderby_search_h'])){
            $xfilter = "AND tranfile1.orderby LIKE '%".$_POST['orderby_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['docnum_search_h']) && !empty($_POST['docnum_search_h'])){
            $xfilter .= " AND tranfile1.docnum LIKE '%".$_POST['docnum_search_h']."%'";
            $search = true;
        }

        if((isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])) &&
        (isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])) ){

            $_POST['from_search_h'] = date("Y-m-d", strtotime($_POST['from_search_h']));
            $_POST['to_search_h'] = date("Y-m-d", strtotime($_POST['to_search_h']));

            $xfilter .= " AND tranfile1.trndte>='".$_POST['from_search_h']."' AND tranfile1.trndte<='".$_POST['to_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])){
            $_POST['from_search_h'] = date("Y-m-d", strtotime($_POST['from_search_h']));
            $xfilter .= " AND tranfile1.trndte>='".$_POST['from_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])){
            $_POST['to_search_h'] = date("Y-m-d", strtotime($_POST['to_search_h']));
            $xfilter .= " AND tranfile1.trndte<='".$_POST['to_search_h']."'";
            $search = true;
        }

        if(isset($_POST['cusname_search_h']) && !empty($_POST['cusname_search_h'])){
            $xfilter .= " AND customerfile.cusdsc LIKE '%".$_POST['cusname_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['unpaid_search_h']) && (int)$_POST['unpaid_search_h'] == 1){
            $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
            $search = true;
        }

        if(($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])) && ($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h']))){
            $xfilter.=" ORDER BY tranfile1.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h'].", tranfile1.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }

        else if($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])){
            $xfilter.=" ORDER BY tranfile1.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h']."";
            $search = true;
        }
        else if($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h'])){
            $xfilter.=" ORDER BY tranfile1.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }

    }else{
        $xfilter =" ORDER BY tranfile1.docnum ASC";
    }
    
    $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
    tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON 
    tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter."";
    $select_db_main ="SELECT * FROM tranfile1";

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


        $pdf->ezPlaceData($xleft,$xtop,$rs_main["tranfile1_docnum"],9,"left");
        $pdf->ezPlaceData($xleft+=90,$xtop,trim_str($rs_main["customerfile_cusdsc"],94,9),9,"left");
        $pdf->ezPlaceData($xleft+=100,$xtop,$rs_main["tranfile1_trndte"],9,"left");
        $pdf->ezPlaceData($xleft+=75,$xtop,trim_str($rs_main["tranfile1_shipto"],120,9),9,"left");
        $pdf->ezPlaceData($xleft+=135,$xtop,$rs_main["tranfile1_paydate"],9,"left");
        $pdf->ezPlaceData($xleft+=85,$xtop,trim_str($rs_main["tranfile1_paydetails"],140,9),9,"left");
        $pdf->ezPlaceData($xleft+215,$xtop,number_format($rs_main["tranfile1_trntot"],"2"),9,"right");

        
        $xtop -= 15;

        

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }
        
    }

       
    $pdf->line(25, $xtop-10, 770, $xtop-10); 
    $pdf->ezPlaceData(660,$xtop-18,"<b>Grand total:</b>",9 ,'right');
    $pdf->ezPlaceData(725,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');

   
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