<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    function sales_upload_pdf_order_results($records){
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
                'status_label' => isset($record['status_label']) && trim((string)$record['status_label']) !== ''
                    ? trim((string)$record['status_label'])
                    : ($is_success ? 'Success' : 'Duplicate Records')
            );

            if($is_success){
                $successful[] = $normalized_record;
            }else{
                $failed[] = $normalized_record;
            }
        }

        return array_merge($failed, $successful);
    }

    function sales_upload_pdf_get_results(){
        if(isset($_SESSION['sales_upload_result_summary']) && is_array($_SESSION['sales_upload_result_summary'])){
            $session_results = sales_upload_pdf_order_results($_SESSION['sales_upload_result_summary']);
            if(!empty($session_results)){
                return $session_results;
            }
        }

        if(isset($_POST['hiddenUploadData']) && trim((string)$_POST['hiddenUploadData']) !== ''){
            $decoded = json_decode($_POST['hiddenUploadData'], true);
            if(is_array($decoded)){
                return sales_upload_pdf_order_results($decoded);
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
    $xprog_module_log = 'SALES FILE UPLOAD';
    $xactivity_log = (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".((isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." from Sales File Upload";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    $xreport_title = "Upload Result Summary";
		

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
    $xheader_check = false;

        $progname_hidden ='';
		$xheader = $pdf->openObject();
        $pdf->saveState();
        
	        if($_POST['txt_output_type'] == 'tab'){
	            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
	        }else{
	            $pdf->ezPlaceData($xleft, $xtop,"<b>Upload Result Summary</b>", 15, 'left' );
	            $xtop   -= 15;
	            $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']."</b>", 9, 'left' );
	            $xtop   -= 15;
	            $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
	            $xtop   -= 20;
        }

        $pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');
        
        $xheader_first_page = $pdf->openObject();
        $pdf->saveState();
        $date_uploaded_format = date('m/d/Y');

        
        if($_POST['txt_output_type'] != 'tab'){

            $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
            $xtop-=15; 
                
            $pdf->ezPlaceData($xleft,$xtop,"<b>Date Uploaded:</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,$date_uploaded_format,10,'left');
            $xtop -=20;
              
	        }else{

	            echo "Upload Result Summary\t\n"; // Use \t for column separation and \n for new rows
	            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
	            echo "Date Printed : " . $date_printed . "\t\n";
	            echo "\n"; // Blank line for spacing

	            // if(isset($_POST['output_with_filter']) &&
            // $_POST['output_with_filter'] == 'true'){
                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
                echo "Date Uploaded: ".$date_uploaded_format."\t\n";
            // }

            $tab_headers = "Order Number\tStatus\t";
            echo $tab_headers;
 
        }

        $xleft =25;
		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        
        $xfields_heaeder_counter = 0;

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Order Number</b>",10,'left');
            $pdf->ezPlaceData($xleft+=140,$xtop,"<b>Status</b>",10,'left');
 
        }
       
        $xtop-=15;
        // Close the object
        $pdf->restoreState();
        $pdf->closeObject();

        // Add the object to only the first page
        $pdf->addObject($xheader_first_page, 'add');


	    $decodedData = sales_upload_pdf_get_results();
        $failed_count = 0;
        foreach($decodedData as $value){
            if(isset($value['success']) && $value['success'] === false){
                $failed_count++;
            }
        }
        $success_count = count($decodedData) - $failed_count;

        if($_POST['txt_output_type'] != 'tab'){
            $pdf->ezPlaceData(25, $xtop, "Failed: ".$failed_count."    Success: ".$success_count."    Total: ".count($decodedData), 9, 'left');
            $xtop -= 20;
        }else{
            echo "Failed: ".$failed_count."\t\n";
            echo "Success: ".$success_count."\t\n";
            echo "Total: ".count($decodedData)."\t\n";
            echo "\n";
        }

        if(empty($decodedData)){
            if($_POST['txt_output_type'] != 'tab'){
                $pdf->ezPlaceData(25, $xtop, 'No upload results available.', 9, 'left');
            }else{
                echo "No upload results available.\t\n";
            }
        }else{
            foreach ($decodedData as $value) {
                $xleft = 25;
                $status = isset($value['status_label']) ? $value['status_label'] : ((isset($value['success']) && $value['success'] === true) ? 'Success' : 'Duplicate Records');

                if($_POST['txt_output_type'] == 'tab'){
                    echo $value['ordernum']."\t".$status."\t\n";
                }else{
                    $pdf->ezPlaceData($xleft,$xtop,$value['ordernum'],9,"left");
                    $pdf->ezPlaceData($xleft+=140,$xtop,$status,9,"left");
                }

                $xtop -= 15;

                if($xtop <= 60)
                {
                    $pdf->ezNewPage();
                    $xtop = 505;

                    $xfields_heaeder_counter = 0;

                    if($_POST['txt_output_type'] !='tab' && $xheader_check == false){

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
