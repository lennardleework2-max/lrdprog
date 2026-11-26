<?php

    session_start();

    if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
        header('location: index.php');
        exit;
    }

    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');

    function sales_payout_pdf_order_results($records){
        if(!is_array($records)){
            return array();
        }

        $records = array_values($records);
        $failed = array();
        $successful = array();

        foreach($records as $record){
            if(!is_array($record)){
                continue;
            }

            $ordernum = isset($record['ordernum']) ? trim((string)$record['ordernum']) : '';
            if($ordernum === ''){
                continue;
            }

            $is_success = isset($record['success']) && $record['success'] === true;
            $normalized_record = array(
                'ordernum' => $ordernum,
                'success' => $is_success,
                'status_label' => $is_success ? 'Successful' : 'Unsuccessful'
            );

            if($is_success){
                $successful[] = $normalized_record;
            }else{
                $failed[] = $normalized_record;
            }
        }

        return array_merge($failed, $successful);
    }

    function sales_payout_pdf_get_results(){
        if(isset($_SESSION['sales_payout_result_summary']) && is_array($_SESSION['sales_payout_result_summary'])){
            $session_results = sales_payout_pdf_order_results($_SESSION['sales_payout_result_summary']);
            if(!empty($session_results)){
                return $session_results;
            }
        }

        if(isset($_POST['hiddenUploadData']) && trim((string)$_POST['hiddenUploadData']) !== ''){
            $decoded = json_decode($_POST['hiddenUploadData'], true);
            if(is_array($decoded)){
                return sales_payout_pdf_order_results($decoded);
            }
        }

        return array();
    }

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
    $xprog_module_log = 'SALES PAYOUT UPLOAD';
    $xactivity_log = 'export_pdf';
    $xremarks_log = "Exported PDF from Sales Payout Upload";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    $pdf = new Cezpdf('Letter','landscape');

    $pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");

	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

	$xtop = 580;
    $xleft = 25;
    /**header**/

    $xheader_check = false;

    $xheader = $pdf->openObject();
    $pdf->saveState();

    $pdf->ezPlaceData($xleft, $xtop,"<b>Payout Upload Result Summary</b>", 15, 'left' );
    $xtop   -= 15;
    $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']."</b>", 9, 'left' );
    $xtop   -= 15;
    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
    $xtop   -= 20;

    $pdf->restoreState();
	$pdf->closeObject();
	$pdf->addObject($xheader,'all');

    $xheader_first_page = $pdf->openObject();
    $pdf->saveState();

    $xleft =25;
	$pdf->setLineStyle(.5);
	$pdf->line($xleft, $xtop+10, 770, $xtop+10);
    $pdf->line($xleft, $xtop-3, 770, $xtop-3);

    $pdf->ezPlaceData($xleft,$xtop,"<b>Order Number</b>",10,'left');
    $pdf->ezPlaceData($xleft+=140,$xtop,"<b>Status</b>",10,'left');

    $xtop-=15;
    $pdf->restoreState();
    $pdf->closeObject();

    // Add the object to only the first page
    $pdf->addObject($xheader_first_page, 'add');

    $decodedData = sales_payout_pdf_get_results();
    $failed_count = 0;
    foreach($decodedData as $value){
        if(isset($value['success']) && $value['success'] === false){
            $failed_count++;
        }
    }
    $success_count = count($decodedData) - $failed_count;

    $pdf->ezPlaceData(25, $xtop, "Unsuccessful: ".$failed_count."    Successful: ".$success_count."    Total: ".count($decodedData), 9, 'left');
    $xtop -= 20;

    if(empty($decodedData)){
        $pdf->ezPlaceData(25, $xtop, 'No upload results available.', 9, 'left');
    }else{
        foreach ($decodedData as $value) {
            $xleft = 25;
            $status = $value['status_label'];
            // ezPlaceData interprets basic markup tags (as used for the <b> headers above) -
            // strip angle brackets from untrusted uploaded order numbers so they can't inject
            // formatting into the PDF.
            $safe_ordernum = str_replace(array('<', '>'), '', $value['ordernum']);

            $pdf->ezPlaceData($xleft,$xtop,$safe_ordernum,9,"left");
            $pdf->ezPlaceData($xleft+=140,$xtop,$status,9,"left");

            $xtop -= 15;

            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 505;

                if($xheader_check == false){

                    $xheader = $pdf->openObject();
                    $pdf->saveState();

                    $xleft =25;
                    $pdf->setLineStyle(.5);
                    $pdf->line($xleft, $xtop+10+20, 770, $xtop+10+20);
                    $pdf->line($xleft, $xtop-14+30, 770, $xtop-14+30);

                    $pdf->ezPlaceData($xleft,$xtop+20,"<b>Order Number</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=140,$xtop+20,"<b>Status</b>",10,'left');

                    $pdf->restoreState();
                    $pdf->closeObject();
                    $pdf->addObject($xheader,'all');

                    $xheader_check = true;
                }
            }
        }
    }

    $pdf->line(25, $xtop-10, 770, $xtop-10);
	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

?>
