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

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

    $xheader = $pdf->openObject();
    $pdf->saveState();


    if($_POST['txt_output_type'] == 'tab'){
        $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
    }else{
        $pdf->ezPlaceData($xleft, $xtop,"<b>Purchases Matching</b>", 15, 'left' );
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
    $xfilter2 = '';
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
    $xfilter2 .= " AND purchasesorderfile1.trndte>='".$xdate_from_filter."' AND purchasesorderfile1.trndte<='".$xdate_to_filter."'";

    if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
        $xfilter .= " AND supplierfile.suppdsc='".$_POST['cus_search']."'";
        $xfilter2 .= " AND supplierfile.trndte<='".$_POST['date_to']."'";
    }

    if(isset($_POST['purchase_type']) && !empty($_POST['purchase_type']) && $_POST['purchase_type'] != 'all'){
        $xfilter .= " AND tranfile1.purchase_type='".$_POST['purchase_type']."'";
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
        //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search']) || 
        //    (isset($_POST['purchase_type']) && !empty($_POST['purchase_type']))){
            $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
            $xtop-=15; 
              
            $pdf->ezPlaceData($xleft,$xtop,"<b>Date From:</b>",10,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop,$xdate_from_display,10,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Date To:</b>",10,'left');
            $pdf->ezPlaceData($xleft+=50,$xtop,$xdate_to_display,10,'left');

            $xleft = 25;
            $pdf->ezPlaceData($xleft,$xtop-13,"<b>Platform:</b>",10,'left');
            $pdf->ezPlaceData($xleft+=55,$xtop-13,$_POST['cus_search'],10,'left');
            $pdf->ezPlaceData($xleft+=85,$xtop-13,"<b>Purchase Type:</b>",10,'left');
            $pdf->ezPlaceData($xleft+=90,$xtop-13,$_POST['purchase_type'],10,'left');

            $xtop-=28;
        // }   
    }else{

        echo "Purchases Matching\t\n"; // Use \t for column separation and \n for new rows
        echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
        echo "Date Printed : " . $date_printed . "\t\n";
        echo "\n"; // Blank line for spacing

        // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
        //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
        //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search']) ||
        //    (isset($_POST['purchase_type']) && !empty($_POST['purchase_type']))){
            echo "FILTER:\n"; // Use \t for column separation and \n for new rows
            echo "Date From: ".$xdate_from_display."\t";
            echo "Date To: ".$xdate_to_display."\t\n";
            echo "Platform: ".$_POST['cus_search']."\t";
            echo "Purchase Type: ".$_POST['purchase_type']."\t\n";
        // }           
            
        $tab_headers = "Purchase Num.\tTran. Date\tSupplier\tItem\tUOM\tDelivered\tOrdered\tExcess\tMatched Purchase Order Num./s\n";
        echo $tab_headers;
    }     
    

        $xleft = 25;
        $pdf->setLineStyle(.5);
        $pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Purchase Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Tran. Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=55,$xtop,"<b>Supplier</b>",10,'left');
            $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Item</b>",10,'left');
            $pdf->ezPlaceData($xleft+=115,$xtop,"<b>UOM</b>",10,'left');
            $pdf->ezPlaceData($xleft+=85,$xtop,"<b>Delivered</b>",10,'right');
            $pdf->ezPlaceData($xleft+=50,$xtop,"<b>Ordered</b>",10,'right');
            $pdf->ezPlaceData($xleft+=50,$xtop,"<b>Excess</b>",10,'right');
            $pdf->ezPlaceData($xleft+=35,$xtop,"<b>Matched Purchase Order Num./s</b>",10,'left');
        }

        $xtop -= 15;

        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($xheader_first_page,'add');    


	/***header**/

    #region DO YOU LOOP HERE
    $select_db="SELECT *, tranfile1.trndte as 'trndte', tranfile2.recid as 'tranfile2_recid', COALESCE(itemunitmeasurefile.unmdsc, '') as uom_description FROM tranfile1
                    LEFT JOIN tranfile2
                    ON tranfile2.docnum = tranfile1.docnum
                    LEFT JOIN supplierfile ON
                    tranfile1.suppcde = supplierfile.suppcde
                    LEFT JOIN itemfile ON
                    itemfile.itmcde = tranfile2.itmcde
                    LEFT JOIN itemunitmeasurefile ON
                    tranfile2.unmcde = itemunitmeasurefile.unmcde
                    WHERE true ".$xfilter." AND tranfile2.trncde='PUR' ORDER BY tranfile2.docnum ";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $xmain_count = 1;
    $previous_docnum = '';
    $xarr_checker = array();
    while($rs_main = $stmt_main->fetch()){   

        $xcount_total_itmheight = 0;
        
        if($previous_docnum !== $rs_main["docnum"]){
            $xmain_count = 1;
        }

        $xleft = 25;

        if(isset($rs_main["trndte"]) && !empty($rs_main["trndte"])){
            $rs_main["trndte"] = date("m-d-Y",strtotime($rs_main["trndte"]));
            $rs_main["trndte"] = str_replace('-','/',$rs_main["trndte"]);
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
        $suppdsc = $rs_main["suppdsc"];
        if($xmain_count <= $rs_main3['xcount'] && $xmain_count != 1){
            $docnum = '';
            $trndte = '';
            $suppdsc =  '';
        }

        if($_POST['txt_output_type'] !== 'tab'){
            $pdf->ezPlaceData($xleft,$xtop,$docnum,9,"left");
            $pdf->ezPlaceData($xleft+=75,$xtop,$trndte,9,"left");
            $pdf->ezPlaceData($xleft+=55,$xtop,$suppdsc,9,"left");
        }



        // Define the maximum line width
        $maxLineWidth = 105; // Adjust based on your layout
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

            $pdf->addText($xleft+70, $xtop, $fontSize, $line); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 12 * ($xcounter_item_newline - 1);
        }
        
        $received = '';
        $balance = '';
        $matched_ponum = '';

        // $select_db_mtch="SELECT * FROM purchasesorderfile1
        // LEFT JOIN purchasesorderfile2 ON
        // purchasesorderfile1.docnum = purchasesorderfile2.docnum
        // LEFT JOIN supplierfile ON 
        // purchasesorderfile1.suppcde = supplierfile.suppcde
        //             WHERE true ".$xfilter2." AND tranfile2_recid='".$rs_main['tranfile2_recid']."'";

        $select_db_mtch="SELECT * FROM purchasesorderfile2 WHERE tranfile2_recid='".$rs_main['tranfile2_recid']."'";
        $stmt_main_mtch	= $link->prepare($select_db_mtch);
        $stmt_main_mtch->execute();
        $ordered_arr = array();
        $match_counter = 0;
        $ordered_arr['ordered']='';
        $ordered_arr['po_num']= '';
        while($rs_main_mtch = $stmt_main_mtch->fetch()){   
   
            if($match_counter == 0){
                $ordered_arr['po_num'] = $rs_main_mtch['docnum'];
            }else{
                $ordered_arr['po_num'] .= ', '.$rs_main_mtch['docnum'];
            }

            if (!isset($ordered_arr['ordered']) || !is_numeric($ordered_arr['ordered'])) {
                $ordered_arr['ordered'] = 0; // Initialize to 0 if not numeric
            }

            $ordered_arr['ordered'] += $rs_main_mtch['itmqty'];

            $match_counter++;
        }     
        
        $excess = '';   
        if(!isset($ordered_arr['ordered'])){
            $ordered_arr['ordered'] = '';
        }else{
            $excess = (int)$rs_main['itmqty'] - (int)$ordered_arr['ordered'];
        }

        if(!isset($ordered_arr['po_num'])){
            $ordered_arr['po_num'] = '';
        }


        if ($_POST['txt_output_type'] == 'tab') {

            // Include remarks in the tab-delimited output generation
            $tab_output =
                            $docnum . "\t" .
                            $trndte . "\t" .
                            $suppdsc . "\t" .
                            $rs_main["itmdsc"] . "\t" .
                            $rs_main["uom_description"] . "\t" .
                            $rs_main["itmqty"] . "\t" .
                            $ordered_arr['ordered'] . "\t" .
                            $excess . "\t" .
                            $ordered_arr['po_num'] . "\n";

            echo $tab_output;
        }else{
            // UOM column - wrap if too long
            $uom_max_width = 50;
            $uom_lines = breakTextIntoLines($pdf, $rs_main["uom_description"], $uom_max_width, 9);
            $uom_line_count = count($uom_lines);
            $uom_y_offset = 0;
            foreach ($uom_lines as $uom_idx => $uom_line) {
                $pdf->addText($xleft+185, $xtop+$xcount_total_itmheight - ($uom_idx * 10), 9, $uom_line);
            }
            if($uom_line_count > 1 && ($uom_line_count - 1) * 10 > $xcount_total_itmheight){
                $xcount_total_itmheight = ($uom_line_count - 1) * 10;
            }

            // Delivered, Ordered, Excess columns
            $pdf->ezPlaceData($xleft+=240,$xtop+$xcount_total_itmheight,$rs_main["itmqty"],9,"right");
            $pdf->ezPlaceData($xleft+=50,$xtop+$xcount_total_itmheight,$ordered_arr['ordered'],9,"right");
            $pdf->ezPlaceData($xleft+=50,$xtop+$xcount_total_itmheight,$excess ,9,"right");

            // Define the maximum line width for Matched PO
            $maxLineWidth = 200; // Adjust based on your layout
            $fontSize = 9;

            // Break the text into lines
            $lines = breakTextIntoLines($pdf, $ordered_arr['po_num'], $maxLineWidth, $fontSize);

            $xcounter_item_newline = 0;
            $xchecker = false;
            $xchecker_add = 0;
            foreach ($lines as $line) {

                if($xcounter_item_newline != 0){
                    $xtop -= 12; // Adjust for line spacing
                    $xchecker = true;
                }

                $pdf->addText($xleft+35, $xtop+$xcount_total_itmheight, $fontSize, $line); // Add the line
                $xcounter_item_newline++;
            }    


            // $pdf->ezPlaceData($xleft+=23,$xtop+$xcount_total_itmheight,$ordered_arr['po_num'],8,"left");
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
                
                $pdf->ezPlaceData($xleft,$xtop,"<b>Purchase Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Tran. Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=55,$xtop,"<b>Supplier</b>",10,'left');
                $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Item</b>",10,'left');
                $pdf->ezPlaceData($xleft+=115,$xtop,"<b>UOM</b>",10,'left');
                $pdf->ezPlaceData($xleft+=55,$xtop,"<b>Delivered</b>",10,'right');
                $pdf->ezPlaceData($xleft+=50,$xtop,"<b>Ordered</b>",10,'right');
                $pdf->ezPlaceData($xleft+=50,$xtop,"<b>Excess</b>",10,'right');
                $pdf->ezPlaceData($xleft+=35,$xtop,"<b>Matched Purchase Order Num./s</b>",10,'left');

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