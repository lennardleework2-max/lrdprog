<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');

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
    $xheader_check = false;
    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

	$xheader = $pdf->openObject();
        $pdf->saveState();


        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Matching</b>", 15, 'left' );
            $xtop   -= 15;
            $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']."</b>", 9, 'left' );
            $xtop   -= 15;
            $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
            $xtop   -= 20;
        }        

        $pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

        $xfilter = '';
        $xorder = '';
    
        $xdate_from_filter = "";
        $xdate_to_filter = "";
    
        if(!(isset($_POST['date_from']) && !empty($_POST['date_from']))){
            $xdate_from_filter = "2000-01-01";
        }
    
        if(!(isset($_POST['date_to']) && !empty($_POST['date_to']))){
            $xdate_to_filter = date('Y-m-d');
        }
    
        if($xdate_from_filter == ''){
            $xdate_from_filter = date("Y-m-d", strtotime($_POST['date_from']));
        }
    
        if($xdate_to_filter == ''){
            $xdate_to_filter = date("Y-m-d", strtotime($_POST['date_to']));
        }
    
        $xfilter .= " AND tranfile1.trndte>='".$xdate_from_filter."' AND tranfile1.trndte<='".$xdate_to_filter."'";
    
        if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
            $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
        }           


        $xheader_first_page = $pdf->openObject();
        $pdf->saveState();

        $xdate_from_display = $xdate_from_filter;
        $xdate_from_display = new DateTime($xdate_from_display);
        $xdate_from_display = $xdate_from_display->format('m/d/Y');
    
        $xdate_to_display = $xdate_to_filter;
        $xdate_to_display = new DateTime($xdate_to_display);
        $xdate_to_display = $xdate_to_display->format('m/d/Y');


        if($_POST['txt_output_type'] != 'tab'){
            // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
            //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
            //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search'])){
                $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
                $xtop-=15; 
                  
                $pdf->ezPlaceData($xleft,$xtop,"<b>Date From:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,$xdate_from_display,10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Date To:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=50,$xtop,$xdate_to_display,10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Platform:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=55,$xtop,$_POST['cus_search'],10,'left');

                $xtop-=15;

            // }   
        }else{

            echo "Sales Matching\t\n"; // Use \t for column separation and \n for new rows
            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n"; // Blank line for spacing

            // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
            //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
            //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search'])){
                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
                echo "Date From: ".$xdate_from_display."\t";
                echo "Date To: ".$xdate_to_display."\t";
                echo "Platform: ".$_POST['cus_search']."\t\n";
            // }           
                
            $tab_headers = "Sales Num.\tTran. Date\tPlatform\tItem\tDelivered\tOrdered\tExcess\tMatched Sales Order Num./s\n";
            echo $tab_headers;
        }     
        

        $xleft = 25;
        $pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Sales Number</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Tran. Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Platform</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Item</b>",10,'left');
            $pdf->ezPlaceData($xleft+=140,$xtop,"<b>Delivered</b>",10,'right');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Ordered</b>",10,'right');
            $pdf->ezPlaceData($xleft+=65,$xtop,"<b>Excess</b>",10,'right');
            $pdf->ezPlaceData($xleft+=30,$xtop,"<b>Matched Sales Order Num.</b>",10,'left');
        }

		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');    


	/***header**/

    #region DO YOU LOOP HERE
       $select_db="SELECT *, tranfile1.trndte as 'trndte', 
                          tranfile1.docnum as 'docnum', 
                          tranfile2.itmqty as 'itmqty',
                          customerfile.cusdsc as 'cusdsc',
                          itemfile.itmdsc as 'itmdsc'
     FROM tranfile1 LEFT JOIN tranfile2 ON 
    tranfile1.docnum = tranfile2.docnum LEFT JOIN customerfile ON 
    tranfile1.cuscde = customerfile.cuscde LEFT JOIN itemfile ON 
    tranfile2.itmcde = itemfile.itmcde WHERE true ".$xfilter." AND tranfile1.trncde='SAL' AND tranfile1.docnum NOT LIKE '%BOM%' ORDER BY tranfile2.docnum";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $xmain_count = 1;
    $previous_docnum = '';
    while($rs_main = $stmt_main->fetch()){   


        if($previous_docnum !== $rs_main["docnum"]){
            $xmain_count = 1;
        }

        $xleft = 25;

        if(isset($rs_main["trndte"]) && !empty($rs_main["trndte"])){
            $rs_main["trndte"] = date("m-d-Y",strtotime($rs_main["trndte"]));
            $rs_main["trndte"] = str_replace('-','/',$rs_main["trndte"]);
        }

        $select_db2="SELECT *, salesorderfile2.itmqty as 'itmqty', salesorderfile2.docnum as 'docnum' FROM salesorderfile2 WHERE salesorderfile2.recid='".$rs_main['so_recid']."' LIMIT 1";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $rs_main2 = $stmt_main2->fetch();

        if(!empty($rs_main2)){
            $received = $rs_main2['itmqty'];
            $matched_ponum = $rs_main2['docnum']; 
            $balance = $rs_main["itmqty"] - $received;
        }else{
            $received = '';
            $matched_ponum = ''; 
            $balance = $rs_main["itmqty"];
        }

        $select_db3="SELECT count(*) as 'xcount' from tranfile2 WHERE docnum='".$rs_main['docnum']."'";
        $stmt_main3	= $link->prepare($select_db3);
        $stmt_main3->execute();
        $rs_main3 = $stmt_main3->fetch();

        if ($_POST['txt_output_type']=='tab')
		{
            //$rs_main["itmdsc"] = $rs_main["itmdsc"];
		}else{
            //$rs_main["itmdsc"] = trim_str($rs_main["itmdsc"],100,9);
        }

        $docnum = $rs_main["docnum"];
        $trndte = $rs_main["trndte"];
        $cusdsc = $rs_main["cusdsc"];
        if($xmain_count <= $rs_main3['xcount'] && $xmain_count != 1){
            $docnum = '';
            $trndte = '';
            $cusdsc =  '';
            $file_created_date = '';
        }


        if($_POST['txt_output_type'] == 'tab'){

        }else{
            $pdf->ezPlaceData($xleft,$xtop,$docnum,9,"left");
            $pdf->ezPlaceData($xleft+=80,$xtop,$trndte,9,"left");
            $pdf->ezPlaceData($xleft+=80,$xtop,$cusdsc,9,"left");
        }



        // Define the maximum line width
        $maxLineWidth = 100; // Adjust based on your layout
        $fontSize = 9;

        // Break the text into lines
        $lines = breakTextIntoLines($pdf, $rs_main["itmdsc"], $maxLineWidth, $fontSize);

        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 12; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText($xleft+80, $xtop, $fontSize, $line); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 12 * ($xcounter_item_newline - 1);
        }


        if ($_POST['txt_output_type'] == 'tab') {
        
            // Include remarks in the tab-delimited output generation
            $tab_output = 
                            $docnum . "\t" .
                            $trndte . "\t" .
                            $cusdsc . "\t" .
                            $rs_main["itmdsc"] . "\t" .
                            $rs_main["itmqty"]. "\t" .
                            $received . "\t" .
                            $balance . "\t" .
                            $matched_ponum . "\n";
                            
        
            echo $tab_output;
        }else{
            $pdf->ezPlaceData($xleft+=215,$xtop+$xcount_total_itmheight,$rs_main["itmqty"],9,"right");
            $pdf->ezPlaceData($xleft+=80,$xtop+$xcount_total_itmheight,$received,9,"right");
            $pdf->ezPlaceData($xleft+=65,$xtop+$xcount_total_itmheight,$balance,9,"right");
            $pdf->ezPlaceData($xleft+=40,$xtop+$xcount_total_itmheight,$matched_ponum,9,"left");
            if($xmain_count == $rs_main3['xcount']){
                $pdf->line(25, $xtop-10, 770, $xtop-10); 
                $xtop -= 5;
            }
        }



        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;

            if($_POST['txt_output_type'] !='tab' && $xheader_check == false){

                $xheader = $pdf->openObject();
                $pdf->saveState();
    
                $xleft =25;
                $pdf->setLineStyle(.5);
                $pdf->line($xleft, $xtop+10, 770, $xtop+10);
                $pdf->line($xleft, $xtop-3, 770, $xtop-3);

                $pdf->ezPlaceData($xleft,$xtop,"<b>Sales Number</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Tran. Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Platform</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Item</b>",10,'left');
                $pdf->ezPlaceData($xleft+=140,$xtop,"<b>Delivered</b>",10,'right');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Ordered</b>",10,'right');
                $pdf->ezPlaceData($xleft+=65,$xtop,"<b>Excess</b>",10,'right');
                $pdf->ezPlaceData($xleft+=30,$xtop,"<b>Matched Sales Order Num.</b>",10,'left');

                $xleft = 25;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'all'); 

                $xheader_check = true;
            }

            $xtop -= 15;   
        }

        $xmain_count++;

        $previous_docnum = $rs_main["docnum"];
        
    }

       
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