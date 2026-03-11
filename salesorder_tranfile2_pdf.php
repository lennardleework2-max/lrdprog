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
    $xprog_module_log = 'SALES ORDER';
    $xactivity_log = 'export_pdf';
    $xremarks_log = "Exported PDF from Sales Order";
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
        $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Order</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;
 
        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        // $xtop   -= 15;

        
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;


        

        $xfields_heaeder_counter = 0;

        $select_db_file1="SELECT salesorderfile1.docnum as tranfile_docnum, customerfile.cusdsc as customerfile_cusdsc, 
        salesorderfile1.shipto as tranfile_shipto,salesorderfile1.trndte as tranfile_trndte,salesorderfile1.orderby as tranfile_orderby,
        salesorderfile1.trntot as salesorderfile1_trntot
        FROM salesorderfile1 LEFT JOIN customerfile ON salesorderfile1.cuscde = customerfile.cuscde WHERE salesorderfile1.recid=?";
        $stmt_file1	= $link->prepare($select_db_file1);
        $stmt_file1->execute(array($_POST['print_file2_recid_h']));
        while($rs_file1 = $stmt_file1->fetch()){


            if(!empty($rs_file1['tranfile_trndte']) && $rs_file1['tranfile_trndte'] !== NULL &&  $rs_file1['tranfile_trndte']!=="1970-01-01"){
                $rs_file1['tranfile_trndte'] = date("m-d-Y",strtotime($rs_file1['tranfile_trndte']));
                $rs_file1['tranfile_trndte'] = str_replace('-','/',$rs_file1['tranfile_trndte']);
            }else{
                $rs_file1['tranfile_trndte'] = NULL;
            }
            

            $docnum_file1     = $rs_file1['tranfile_docnum'];
            $cusdsc     = $rs_file1['customerfile_cusdsc'];
            $orderby    = $rs_file1['tranfile_orderby'];
            $shipto     = $rs_file1['tranfile_shipto'];
            $trntot     = $rs_file1['salesorderfile1_trntot'];
            $trndte     = $rs_file1['tranfile_trndte'];
            //$ordernum     = $rs_file1['salesorderfile1_ordernum'];
        }

        $pdf->ezPlaceData($xleft,$xtop,"<b>Docnum:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=50,$xtop,$docnum_file1,10,'left');
        $pdf->ezPlaceData($xleft+=115,$xtop,"<b>Platform:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=55,$xtop,$cusdsc,10,'left');
        $xtop-=15;
        $xleft = 25;
        $pdf->ezPlaceData($xleft,$xtop,"<b>Date Ordered:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=75,$xtop,$trndte,10,'left');
        $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Ship To:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=50,$xtop,trim_str($shipto, 100,10),10,'left');
        $xtop-=15;
        $xleft = 25;
        $pdf->ezPlaceData($xleft,$xtop,"<b>Order By:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=55,$xtop,$orderby,10,'left');
        $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Total:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=90,$xtop,number_format($trntot,"2"),10,'right');
        // $xtop-=15;
        // $xleft = 25;


        $xtop-=30;
        $xleft = 25;
        $pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        $pdf->ezPlaceData($xleft,$xtop,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($xleft+=170,$xtop,"<b>Quantity</b>",10,'left');
        $pdf->ezPlaceData($xleft+=100,$xtop,"<b>Price</b>",10,'left');
        $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Amount</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=85,$xtop,"<b>Payment Details</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=215,$xtop,"<b>Total</b>",10,'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    
    $select_db_file2="SELECT itemfile.itmdsc as itemfile_itmdsc,salesorderfile2.itmqty as salesorderfile2_itmqty,salesorderfile2.untprc as salesorderfile2_untprc,
    salesorderfile2.extprc as salesorderfile2_extprc FROM salesorderfile2 LEFT JOIN itemfile ON 
    salesorderfile2.itmcde = itemfile.itmcde WHERE true AND salesorderfile2.docnum=?";
    $stmt_main_file2	= $link->prepare($select_db_file2);
    $stmt_main_file2->execute(array($docnum_file1));
    while($rs_file2 = $stmt_main_file2->fetch()){    

        $xleft = 25;
        //$pdf->ezPlaceData($xleft,$xtop,trim_str($rs_file2["itemfile_itmdsc"],155,9),9,"left");
        $maxLineWidth = 200;
        $fontSize = 9;
        
        $lines = breakTextIntoLines($pdf, $rs_file2["itemfile_itmdsc"], $maxLineWidth, $fontSize);
        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight  = 0;
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

        $pdf->ezPlaceData($xleft+=207,$xtop+$xcount_total_itmheight,number_format($rs_file2["salesorderfile2_itmqty"]),9,"right");
        $pdf->ezPlaceData($xleft+=88,$xtop+$xcount_total_itmheight,number_format($rs_file2["salesorderfile2_untprc"],2),9,"right");
        $pdf->ezPlaceData($xleft+=87,$xtop+$xcount_total_itmheight,number_format($rs_file2["salesorderfile2_extprc"],2),9,"right");

        
        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }
        
    }

    $pdf->line(25, $xtop-10, 770, $xtop-10); 
    $pdf->ezPlaceData(337,$xtop-18,"<b>Grand total:</b>",9 ,'right');
    $pdf->ezPlaceData(407,$xtop-18,"<b>".number_format($trntot,"2")."</b>",9 ,'right');

   
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