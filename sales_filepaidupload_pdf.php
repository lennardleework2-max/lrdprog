<?php
    //var_dump($_POST);

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
    $xprog_module_log = 'SALES FILE PAID UPLOAD';
    $xactivity_log = (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".((isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." from Sales File Paid Upload";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

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
    $xheader_check = false;

        $progname_hidden ='';
		$xheader = $pdf->openObject();
        $pdf->saveState();
        
        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Uploaded Sales</b>", 15, 'left' );
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

            echo "Uploaded Sales\t\n"; // Use \t for column separation and \n for new rows
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


    if (isset($_POST['hiddenUploadData'])) {
        $jsonData = $_POST['hiddenUploadData'];
    
        // Step 2: Decode the JSON data into a PHP array
        $decodedData = json_decode($jsonData, true);

        // Sort array to show matched (true) first, then unmatched (false)
        usort($decodedData, function ($a, $b) {
            // Sort true (1) first, then false (0)
            return ($b['success'] === true) - ($a['success'] === true);
        });

        //loop through it 
        foreach ($decodedData as $number => $value) {

            $xleft = 25;

            if($value['success'] == true){
                $status =  'matched';
            }else{
                $status =  'unmatched';
            }
            
            $pdf->ezPlaceData($xleft,$xtop,$value['ordernum'],9,"left");
            $pdf->ezPlaceData($xleft+=140,$xtop,$status,9,"left");

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
                // $xtop -= 10;
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