<?php
//error_reporting(E_ALL);
//ini_set('display_errors',true);
ini_set('memory_limit',-1);
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');

    $tab_file_type = 'xlsx';
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');
    $export_label = $is_tab_export ? strtoupper($tab_file_type) : 'PDF';

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
    $xprog_module_log = 'TOP SALES PLATFORM';
    $xactivity_log = $is_tab_export ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".$export_label." from Top Sales Platform";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    $xreport_title = "Top Sales Items";


    if ($is_tab_export)
	{
		$pdf = new tab_ezpdf('Letter','portrait');
	}
	else
	{
		$pdf = new Cezpdf('Letter','portrait');

	}

    $pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

	$xtop = 750;
    $xleft = 25;

    /**header**/

    //getting header fields
    $fields_count = 0;
    $fields = '';

    $head_date_from='01-01-2000';
    $head_date_to=date('m-d-Y');
    $xfilter = '';

    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $head_date_from=date("m-d-Y", strtotime($_POST['date_from']));
        $head_date_to=date("m-d-Y", strtotime($_POST['date_to']));

        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));

        $head_date_from=date("m-d-Y", strtotime($_POST['date_from']));

        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }

    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $head_date_to=date("m-d-Y", strtotime($_POST['date_to']));

        $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }



		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Top Sales Report (by Platform)</b>", 15, 'left' );
        $xtop   -= 15;
        $header="<b>Pdf Report by: ".$_SESSION['userdesc']." </b>";

        $pdf->ezPlaceData($xleft, $xtop,$header, 9, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop, "Sales Date Range : ".$head_date_from." to ".$head_date_to, 10, 'left' );


        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        $xtop   -= 15;


        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 600, $xtop+10);
        $pdf->line($xleft, $xtop-3, 600, $xtop-3);


        $xfields_heaeder_counter = 0;

        $pdf->ezPlaceData($xleft,$xtop,"<b>Platform</b>",10,'left');
        $pdf->ezPlaceData($xleft+=400,$xtop,"<b>Amount</b>",10,'right');
        //$pdf->ezPlaceData($xleft+=100,$xtop,"<b>Qty</b>",10,'right');


		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP


    $xfilter_smn = '';
    $xorder = '';
    $xsal_ret_tot =0;



/*
$select_db="SELECT itemfile.itmdsc as itmdsc,sum(extprc) as tot_extprc,sum(itmqty) as tot_itmqty
    from tranfile2
    LEFT JOIN tranfile1 ON tranfile2.docnum=tranfile1.docnum
    LEFT JOIN itemfile ON tranfile2.itmcde=itemfile.itmcde
    WHERE tranfile1.trncde='SAL' ".$xfilter." GROUP BY tranfile2.itmcde ORDER BY tot_extprc DESC";
*/
$select_db="SELECT cusdsc,sum(trntot) as tot_trntot
    from tranfile1
    LEFT JOIN customerfile ON tranfile1.cuscde=customerfile.cuscde
    WHERE tranfile1.trncde='SAL'  ".$xfilter." GROUP BY tranfile1.cuscde ORDER BY tot_trntot DESC";
  //  var_dump($select_db);
  //  die();



    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    while($rs_main = $stmt_main->fetch()){

        $xleft = 25;
        $xtop -= 15;
        //$pdf->ezPlaceData($xleft,$xtop,$rs_main["itmdsc"],9,"left");
        $pdf->ezPlaceData($xleft,$xtop,trim_str($rs_main["cusdsc"],300,9)??'',9,"left");
        $pdf->ezPlaceData($xleft+=400,$xtop,number_format($rs_main["tot_trntot"],"2"),9,"right");
       // $pdf->ezPlaceData($xleft+=100,$xtop,number_format($rs_main["tot_itmqty"],"0"),9,"right");
        $grand_total = $grand_total + $rs_main["tot_trntot"];


        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 685;
        }

    }
        $xleft = 25;
        $pdf->setLineStyle(.5);
        $pdf->line($xleft, $xtop-3, 600, $xtop-3);
        $xtop -= 15;

        $pdf->ezPlaceData($xleft,$xtop,"Total",9,"left");
        $pdf->ezPlaceData($xleft+=400,$xtop,number_format($grand_total,"2"),9,"right");

    if($is_tab_export){
        $pdf->ezStream($tab_file_type);
    } else {
        $pdf->ezStream();
    }
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

