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

        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Order Matching</b>", 15, 'left' );
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

        $xdate_from_upload_filter = "";
        $xdate_to_upload_filter = "";

        $xdate_from_ordered_filter = "";
        $xdate_to_ordered_filter = "";
    
        // UPLOADED DATE FITLER
        if(!(isset($_POST['date_from']) && !empty($_POST['date_from']))){
            $xdate_from_upload_filter = "2000-01-01";
        }
    
        if(!(isset($_POST['date_to']) && !empty($_POST['date_to']))){
            $xdate_to_upload_filter = date('Y-m-d');
        }

        // ORDERED DATE FILTER
        if(!(isset($_POST['date_ordered_from']) && !empty($_POST['date_ordered_from']))){
            $xdate_from_ordered_filter = "2000-01-01";
        }
    
        if(!(isset($_POST['date_ordered_to']) && !empty($_POST['date_ordered_to']))){
            $xdate_to_ordered_filter = date('Y-m-d');
        }
    
        //UPLOAD DATE FILTER
        if($xdate_from_upload_filter == ''){
            $xdate_from_upload_filter = date("Y-m-d", strtotime($_POST['date_from']));
        }
    
        if($xdate_to_upload_filter == ''){
            $xdate_to_upload_filter = date("Y-m-d", strtotime($_POST['date_to']));
        }

        //ORERED DATE FILTER
        if($xdate_from_ordered_filter == ''){
            $xdate_from_ordered_filter = date("Y-m-d", strtotime($_POST['date_ordered_from']));
        }
    
        if($xdate_to_ordered_filter == ''){
            $xdate_to_ordered_filter = date("Y-m-d", strtotime($_POST['date_ordered_to']));
        }

        $xfilter .= " AND salesorderfile1.trndte>='".$xdate_from_upload_filter."' AND salesorderfile1.trndte<='".$xdate_to_upload_filter."'";
        $xfilter .= " AND DATE(salesorderfile1.file_created_date)>='".$xdate_from_ordered_filter."' AND DATE(salesorderfile1.file_created_date)<='".$xdate_to_ordered_filter."'";

        if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
            $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
        }

        $xheader_first_page = $pdf->openObject();
        $pdf->saveState();


        // UPLOAD DATE
        $xdate_from_upload_display = $xdate_from_upload_filter;
        $xdate_from_upload_display = new DateTime($xdate_from_upload_display);
        $xdate_from_upload_display = $xdate_from_upload_display->format('m/d/Y');

        $xdate_to_upload_display = $xdate_to_upload_filter;
        $xdate_to_upload_display = new DateTime($xdate_to_upload_display);
        $xdate_to_upload_display = $xdate_to_upload_display->format('m/d/Y');

        //ORDERED DATE
        $xdate_from_ordered_display = $xdate_from_ordered_filter;
        $xdate_from_ordered_display = new DateTime($xdate_from_ordered_display);
        $xdate_from_ordered_display = $xdate_from_ordered_display->format('m/d/Y');

        $xdate_to_ordered_display = $xdate_to_ordered_filter;
        $xdate_to_ordered_display = new DateTime($xdate_to_ordered_display);
        $xdate_to_ordered_display = $xdate_to_ordered_display->format('m/d/Y');


        if($_POST['txt_output_type'] != 'tab'){
            // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
            //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
            //    (isset($_POST['date_ordered_from']) && !empty($_POST['date_ordered_to'])) || 
            //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search'])){
                $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
                $xtop-=15; 
                  
                $pdf->ezPlaceData($xleft,$xtop,"<b>Ordered Date From:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=100,$xtop,$xdate_from_ordered_display,10,'left');
                $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Ordered Date To:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=85,$xtop,$xdate_to_ordered_display,10,'left');

                $xleft = 25;

                $pdf->ezPlaceData($xleft,$xtop-15,"<b>Upload Date From:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=100,$xtop-15,$xdate_from_upload_display,10,'left');
                $pdf->ezPlaceData($xleft+=70,$xtop-15,"<b>Ordered Date To:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=85,$xtop-15,$xdate_to_upload_display,10,'left');

                $pdf->ezPlaceData($xleft+=65,$xtop-15,"<b>Supplier:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=55,$xtop-15,$_POST['cus_search'],10,'left');

                $xtop-=30;

            // }   
        }else{

            echo "Sales Order Matching\t\n"; // Use \t for column separation and \n for new rows
            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n"; // Blank line for spacing

            // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
            //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
            //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search'])){
                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
                echo "Ordered Date From: ".$xdate_from_ordered_display."\t";
                echo "Ordered Date To: ".$xdate_to_ordered_display."\t\n";
                echo "Upload Date From: ".$xdate_from_upload_display."\t";
                echo "Upload Date To: ".$xdate_to_upload_display."\t";
                echo "Supplier: ".$_POST['cus_search']."\t\n";
            // }           
                
            $tab_headers = "SO. Num.\tOrdered Date\tUpload Date\tPlatform\tItem\tPending Orders\tUOM\tShipped Out\tBalance\tMatched Sales Num";
            echo $tab_headers;
        }     
        

        $xleft = 25;
        $pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>SO. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Ordered Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Upload Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Platform</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Item</b>",10,'left');
            $pdf->ezPlaceData($xleft+=140,$xtop,"<b>Pending Orders</b>",10,'right');
            $pdf->ezPlaceData($xleft+=35,$xtop,"<b>UOM</b>",10,'left');
            $pdf->ezPlaceData($xleft+=55,$xtop,"<b>Shipped Out</b>",10,'right');
            $pdf->ezPlaceData($xleft+=50,$xtop,"<b>Balance</b>",10,'right');
            $pdf->ezPlaceData($xleft+=40,$xtop,"<b>Matched Sales Num</b>",10,'left');
        }

		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');


	/***header**/

    #region DO YOU LOOP HERE
    $select_db="SELECT *, salesorderfile1.trndte as 'trndte',
                          salesorderfile1.file_created_date as 'file_created_date',
                          salesorderfile1.docnum as 'docnum',
                          salesorderfile2.itmqty as 'itmqty',
                          salesorderfile2.recid as 'sonum_recid',
                          customerfile.cusdsc as 'cusdsc',
                          itemfile.itmdsc as 'itmdsc',
                          COALESCE(itemunitmeasurefile.unmdsc, '') as 'unmdsc'
     FROM salesorderfile1 LEFT JOIN salesorderfile2 ON
    salesorderfile1.docnum = salesorderfile2.docnum LEFT JOIN customerfile ON
    salesorderfile1.cuscde = customerfile.cuscde LEFT JOIN itemfile ON
    salesorderfile2.itmcde = itemfile.itmcde LEFT JOIN itemunitmeasurefile ON
    salesorderfile2.unmcde = itemunitmeasurefile.unmcde WHERE true ".$xfilter." AND salesorderfile1.docnum NOT LIKE '%-BOM%' ORDER BY salesorderfile2.docnum";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $xmain_count = 1;
    $previous_docnum = '';
    while($rs_main = $stmt_main->fetch()){   


        $file_created_date = $rs_main['file_created_date'];
        $date_file_created = new DateTime($file_created_date);
        $file_created_date = $date_file_created->format('m/d/Y');

        if($previous_docnum !== $rs_main["docnum"]){
            $xmain_count = 1;
        }

        $xleft = 25;

        if(isset($rs_main["trndte"]) && !empty($rs_main["trndte"])){
            $rs_main["trndte"] = date("m-d-Y",strtotime($rs_main["trndte"]));
            $rs_main["trndte"] = str_replace('-','/',$rs_main["trndte"]);
        }

        $select_db2="SELECT *, tranfile2.itmqty as 'itmqty', tranfile2.docnum as 'docnum' FROM tranfile2 WHERE tranfile2.so_recid='".$rs_main['sonum_recid']."' LIMIT 1";
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

        $select_db3="SELECT count(*) as 'xcount' from salesorderfile2 WHERE docnum='".$rs_main['docnum']."'";
        $stmt_main3	= $link->prepare($select_db3);
        $stmt_main3->execute();
        $rs_main3 = $stmt_main3->fetch();

        $docnum = $rs_main["docnum"];
        $trndte = $rs_main["trndte"];
        $cusdsc = $rs_main["cusdsc"];
        if($xmain_count <= $rs_main3['xcount'] && $xmain_count != 1){
            $docnum = '';
            $trndte = '';
            $cusdsc =  '';
            $file_created_date = '';
        }

        $pdf->ezPlaceData($xleft,$xtop,$docnum,9,"left");
        $pdf->ezPlaceData($xleft+=80,$xtop,$file_created_date,9,"left");
        $pdf->ezPlaceData($xleft+=80,$xtop,$trndte,9,"left");
        $pdf->ezPlaceData($xleft+=80,$xtop,$cusdsc,9,"left");

        if($_POST['txt_output_type'] == 'tab'){
        
            $pdf->ezPlaceData($xleft+=80,$xtop,$rs_main["itmdsc"],9,"left");
            $xleft -= 80;
            $xcount_total_itmheight = 0;
        }else{

            // Define the maximum line width
            $maxLineWidth = 120; // Adjust based on your layout
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
        }        
        $pdf->ezPlaceData($xleft+=220,$xtop+$xcount_total_itmheight,$rs_main["itmqty"],9,"right");
        $pdf->ezPlaceData($xleft+=35,$xtop+$xcount_total_itmheight,$rs_main["unmdsc"],9,"left");
        $pdf->ezPlaceData($xleft+=55,$xtop+$xcount_total_itmheight,$received,9,"right");
        $pdf->ezPlaceData($xleft+=50,$xtop+$xcount_total_itmheight,$balance,9,"right");
        $pdf->ezPlaceData($xleft+=40,$xtop+$xcount_total_itmheight,$matched_ponum,9,"left");
        if($xmain_count == $rs_main3['xcount']){
            $pdf->line(25, $xtop-10, 770, $xtop-10); 
            $xtop -= 5;
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

                $pdf->ezPlaceData($xleft,$xtop,"<b>SO. Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Ordered Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Upload Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Platform</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Item</b>",10,'left');
                $pdf->ezPlaceData($xleft+=140,$xtop,"<b>Pending Orders</b>",10,'right');
                $pdf->ezPlaceData($xleft+=35,$xtop,"<b>UOM</b>",10,'left');
                $pdf->ezPlaceData($xleft+=55,$xtop,"<b>Shipped Out</b>",10,'right');
                $pdf->ezPlaceData($xleft+=50,$xtop,"<b>Balance</b>",10,'right');
                $pdf->ezPlaceData($xleft+=40,$xtop,"<b>Matched Sales Num</b>",10,'left');

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