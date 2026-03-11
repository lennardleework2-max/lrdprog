<?php
    //var_dump($_POST);
    // ini_set('display_errors', '1');
    // ini_set('display_startup_errors', '1');
    // error_reporting(E_ALL);
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass_new/class/class.ezpdf.php');
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
    $xprog_module_log = 'STOCK VALUATION';
    $xactivity_log = (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".((isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." from Stock Valuation";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();


    $xreport_title = "List of items";
		

    if ($_POST['txt_output_type']=='tab')
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
        $pdf->ezPlaceData($xleft, $xtop,"<b>Stock Valuation Report</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." </b>", 9, 'left' );
        $xtop   -= 15;
 
        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        // $xtop   -= 15;

        
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 600, $xtop+10);
        

        $xfields_heaeder_counter = 0;
        
        $pdf->ezPlaceData($xleft,$xtop,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($xleft+=300,$xtop,"<b>Balance</b>",10,'right');
        $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Last Cost</b>",10,'right');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Last Date</b>",10,'right');
        $pdf->ezPlaceData($xleft+=100,$xtop,"<b>Inventory Cost</b>",10,'right');
        $xtop -= 15;
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=395,$xtop,"<b>Purchased</b>",10,'left');
        $pdf->line(25, $xtop-3, 600, $xtop-3);
        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xfilter2 = '';
    $xorder = '';

        if(isset($_POST['date_search']) && !empty($_POST['date_search'])){
            $_POST['date_search']  = (empty($_POST['date_search'])) ? NULL :  date("Y-m-d", strtotime($_POST['date_search']));
            $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_search']."'";
        }

        if(isset($_POST['item']) && !empty($_POST['item'])){
            $xfilter .= " AND itmcde='".$_POST['item']."'";
        }

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter. " ORDER BY itmdsc ASC";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $total_cost = 0;
    while($rs_main = $stmt_main->fetch()){    

        // $select_db2 = "SELECT SUM(stkqty) as xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum= tranfile2.docnum WHERE itmcde='".$rs_main['itmcde']."'";
        $select_db2 = "SELECT SUM(stkqty) as xsum, itemfile.itmdsc as itemfile_itmdsc FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum= tranfile2.docnum LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde WHERE itemfile.itmcde='".$rs_main['itmcde']."' ".$xfilter2."";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute(array());
        $rs2 = $stmt_main2->fetch();

        $item_total =  $rs2['xsum'];
        $item_dsc =  $rs_main['itmdsc'];

        
        if(isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab'){
            //$pdf->ezPlaceData($xleft+=140,$xtop,$item_dsc,9,"left");
        }else{

            $maxLineWidth = 250;
            $fontSize = 9;

            $lines = breakTextIntoLines($pdf, $item_dsc, $maxLineWidth, $fontSize);

            $xcount_total_itmheight = 0;
            $xcounter_item_newline = 0;
            $xchecker = false;
            $xchecker_add = 0;

            foreach ($lines as $line) {
    
                if($xcounter_item_newline != 0){
                    $xtop -= 12; // Adjust for line spacing
                    $xchecker = true;
                }
    
                $pdf->addText(25, $xtop, $fontSize, $line); // Add the line
                $xcounter_item_newline++;
            }
    
            if($xchecker == true){
                $xcount_total_itmheight = 12 * ($xcounter_item_newline - 1);
            }
        }
        $xleft = 25;
        
        if(isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft,$xtop,$item_dsc,9,"left");
        }

        $pdf->ezPlaceData($xleft+=300,$xtop+$xcount_total_itmheight,$item_total,9,"right");
        
        

        /*
        if ($_POST['txt_output_type']=='tab')
		{
            $item_dsc = $item_dsc;

		}else{
            $item_dsc = trim_str($item_dsc,120,9);

        }
            
        $xleft = 25;
        $pdf->ezPlaceData($xleft,$xtop,$item_dsc,9,"left");
        */

        /*
        $select_db3 = "SELECT *,purchasesorderfile1.trndte as po1_trndte FROM purchasesorderfile1 LEFT JOIN 
                       purchasesorderfile2 ON purchasesorderfile1.docnum = purchasesorderfile2.docnum
                       WHERE purchasesorderfile2.itmcde='".$rs_main['itmcde']."' ORDER BY purchasesorderfile1.trndte DESC LIMIT 1";
        */
        
        $select_db3 = "SELECT untprc,tranfile1.trndte as po1_trndte,tranfile1.docnum as xdocnum from tranfile2 LEFT JOIN tranfile1 ON tranfile2.docnum =tranfile1.docnum 
                       WHERE tranfile2.itmcde='".$rs_main['itmcde']."' AND tranfile1.trncde='PUR'  ORDER BY tranfile1.trndte DESC LIMIT 1";


        $stmt_main3	= $link->prepare($select_db3);
        $stmt_main3->execute();
        $rs_main3 = $stmt_main3->fetch();
        
        if(!empty($rs_main3['po1_trndte'])){
            $purchase_date = date('m/d/Y', strtotime($rs_main3['po1_trndte']));
        }else{
            $purchase_date = NULL;
        }
        $untprc = 0;
        if(!empty($rs_main3['untprc'])){
            $untprc =$rs_main3['untprc'];
        }
     
        $pdf->ezPlaceData($xleft+=80,$xtop+$xcount_total_itmheight,number_format($untprc,2),9,"right");
        $pdf->ezPlaceData($xleft+=15,$xtop+$xcount_total_itmheight,$purchase_date,9,"left");
        //$pdf->ezPlaceData($xleft+=60,$xtop+$xcount_total_itmheight,$rs_main3['xdocnum'],9,"left");
        $item_cost = $untprc * $item_total;
        $pdf->ezPlaceData($xleft+=150,$xtop+$xcount_total_itmheight,number_format($item_cost,2),9,"right");
        $total_cost=$total_cost+$item_cost;

        
        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 670;
        }
        
    }

       
     $pdf->line(25, $xtop, 600, $xtop); 
     $pdf->ezPlaceData(450,$xtop-15,"<b>Grand total:</b>",9 ,'right');
     $pdf->ezPlaceData(570,$xtop-15,"<b>".number_format($total_cost,2)."</b>",9 ,'right');
   
    
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

    // Function to break text into lines based on width
    function breakTextIntoLines($pdf, $text, $maxLineWidth, $fontSize) {
        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $lineWidth = $pdf->getTextWidth($fontSize, $testLine);

            if ($lineWidth <= $maxLineWidth) {
                $currentLine = $testLine; // Add the word to the current line
            } else {
                $lines[] = $currentLine; // Save the current line
                $currentLine = $word;    // Start a new line with the word
            }
        }

        // Add the last line
        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    


?>