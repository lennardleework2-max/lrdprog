<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');
    require_once('resources/stdfunc100.php');

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

        // $progname_hidden ='';
        // if($_POST['trncde_hidden'] == 'SAL'){
        //     $progname_hidden = "Sales";
        // }
        // else if($_POST['trncde_hidden'] == 'SRT'){
        //     $progname_hidden = "Sales Return";
        // }
        // else if($_POST['trncde_hidden'] == 'PUR'){
        //     $progname_hidden = "Purchases";
        // }


        $xchecker_1_header = false;
        $xchecker_2_header = false;
        $xchecker_3_header = false;

		$xheader = $pdf->openObject();
        $pdf->saveState();

        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Order by Upload Date (With Item)</b>", 15, 'left' );
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
    
        $xfilter .= " AND salesorderfile1.trndte>='".$xdate_from_filter."' AND salesorderfile1.trndte<='".$xdate_to_filter."'";
    
        if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
            $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
        }

        $xfields_heaeder_counter = 0;

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

            echo "Sales Order by Upload Date (With Item)\t\n"; // Use \t for column separation and \n for new rows
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
                
            $tab_headers = "Doc. Num.\tOrdered Date\tUpload Date\tOrdered By\tPlatform/Item\tQuatity\tUnit Price\tTotal\n";
            echo $tab_headers;
        }     
        
        $xleft =25;
		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);        


        if($_POST['txt_output_type'] != 'tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Ordered Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Upload Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ordered By</b>",10,'left');
            $pdf->ezPlaceData($xleft+=130,$xtop,"<b>Platform/Item</b>",10,'left');
            $pdf->ezPlaceData($xleft+=170,$xtop,"<b>Quantity</b>",10,'right');
            $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Unit Price</b>",10,'right');
            $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Total</b>",10,'right');
        }       
        
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');
	/***header**/

    #region DO YOU LOOP HERE    
    $select_db="SELECT salesorderfile1.file_created_date as 'ordered_date', salesorderfile1.shipto as salesorderfile1_shipto,salesorderfile1.cuscde as salesorderfile1_cuscde,salesorderfile1.docnum as salesorderfile1_docnum,
    salesorderfile1.trndte as salesorderfile1_trndte,salesorderfile1.trntot as salesorderfile1_trntot,salesorderfile1.orderby as salesorderfile1_orderby,salesorderfile1.recid as salesorderfile1_recid,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc,
    customerfile.cusdsc, customerfile.cuscde FROM salesorderfile1 LEFT JOIN customerfile ON 
    salesorderfile1.cuscde = customerfile.cuscde WHERE true ".$xfilter." ORDER BY salesorderfile1.docnum ASC, salesorderfile1.trndte ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    $price_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;
    $old_docnum = '';
    // $pdf->ezPlaceData($xleft,$xtop-100,$select_db,2,"left");
    while($rs_main = $stmt_main->fetch()){ 
        
        if(!empty($rs_main['ordered_date'])){
            $file_created_date = $rs_main['ordered_date'];
            $date_file_created = new DateTime($file_created_date);
            $file_created_date = $date_file_created->format('m/d/Y');
        }else{
            $file_created_date = null;
        } 

        $xleft = 25;

        $grand_total += $rs_main["salesorderfile1_trntot"];

        if(isset($rs_main["salesorderfile1_trndte"]) && !empty($rs_main["salesorderfile1_trndte"])){
            $rs_main["salesorderfile1_trndte"] = date("m-d-Y",strtotime($rs_main["salesorderfile1_trndte"]));
            $rs_main["salesorderfile1_trndte"] = str_replace('-','/',$rs_main["salesorderfile1_trndte"]);
        }



        if ($_POST['txt_output_type']=='tab')
		{
            $rs_main["customerfile_cusdsc"] = $rs_main["customerfile_cusdsc"];
            $rs_main["salesorderfile1_shipto"] = $rs_main["salesorderfile1_shipto"];
		}else{
            $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],155,9);
            $rs_main["salesorderfile1_shipto"] = trim_str($rs_main["salesorderfile1_shipto"],120,9);
        }



        if($_POST['txt_output_type'] == 'tab'){
            $tab_output = $rs_main["salesorderfile1_docnum"] . "\t" .
            $file_created_date . "\t" .
            $rs_main["salesorderfile1_trndte"] . "\t" .
            $rs_main["salesorderfile1_orderby"] . "\t". 
            $rs_main["customerfile_cusdsc"] ."\n";
            echo $tab_output;
        }else{
            $pdf->ezPlaceData($xleft,$xtop,$rs_main["salesorderfile1_docnum"],9,"left");
            $pdf->ezPlaceData($xleft+=60,$xtop,$file_created_date,9,"left");
            $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["salesorderfile1_trndte"],9,"left");
            $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["salesorderfile1_orderby"],9,"left");
            $pdf->ezPlaceData($xleft+=130,$xtop,$rs_main["customerfile_cusdsc"],9,"left");
        }

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;

            if($_POST['txt_output_type'] !='tab' && $xchecker_1_header == false){
                $xheader = $pdf->openObject();
                $pdf->saveState();
    
                $xleft =25;
                $pdf->setLineStyle(.5);
                $pdf->line($xleft, $xtop+10, 770, $xtop+10);
                $pdf->line($xleft, $xtop-3, 770, $xtop-3);
    
                
                $xfields_heaeder_counter = 0;
    
                if($_POST['txt_output_type'] !='tab'){
                    $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Ordered Date</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Upload Date</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ordered By</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=130,$xtop,"<b>Platform/Item</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=170,$xtop,"<b>Quantity</b>",10,'right');
                    $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Unit Price</b>",10,'right');
                    $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Total</b>",10,'right');
                }
    
        
                $xleft = 25;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'all'); 

                $xchecker_1_header = true;
            }

            $xtop -= 15;
        }



        $select_db2="SELECT * FROM salesorderfile2 LEFT JOIN itemfile ON salesorderfile2.itmcde = itemfile.itmcde WHERE salesorderfile2.docnum='".$rs_main['salesorderfile1_docnum']."'";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        // $pdf->ezPlaceData(15,$xtop-100,$select_db3,8,"left");
        $price_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
        $xtop-=12;
        $profit_tot = 0;
        $xcount_itm2 = 0;
        while($rs_main2 = $stmt_main2->fetch()){

            $xleft = 300;
            // Define the maximum line width
            $maxLineWidth = 125; // Adjust based on your layout
            $fontSize = 9;

            // Break the text into lines
            $lines = breakTextIntoLines($pdf, $rs_main2["itmdsc"], $maxLineWidth, $fontSize);

            $xcounter_item_newline = 0;
            $xchecker = false;
            $xchecker_add = 0;
            $xcount_total_itmheight = 0;
            foreach ($lines as $line) {

                if($xcounter_item_newline != 0){
                    $xtop -= 12; // Adjust for line spacing
                    $xchecker = true;
                }

                $pdf->addText(380, $xtop, $fontSize, $line); // Add the line
                $xcounter_item_newline++;
            }

            if($xchecker == true){
                $xcount_total_itmheight = 12 * ($xcounter_item_newline - 1);
            }
        
            if($_POST['txt_output_type'] == 'tab'){
           
                // Include remarks in the tab-delimited output generation
                $tab_output = "\t\t\t\t".
                $rs_main2["itmdsc"]. "\t" .
                $rs_main2["itmqty"]. "\t" .
                $rs_main2["untprc"] . "\t" .
                $rs_main2["extprc"] . "\n";

                echo $tab_output;
            }else{
                $pdf->ezPlaceData($xleft+=235,$xtop+$xcount_total_itmheight,$rs_main2["itmqty"],9,"right");
                $pdf->ezPlaceData($xleft+=90,$xtop+$xcount_total_itmheight,number_format($rs_main2["untprc"],"2"),9,"right");
                $pdf->ezPlaceData($xleft+=90,$xtop+$xcount_total_itmheight,number_format($rs_main2["extprc"],"2"),9,"right");
            }                


            $price_tot+=$rs_main2["untprc"];
            $cost_tot+=$rs_main2["extprc"];
            // $profit_tot+=$profit;

            $xtop -= 15;

            if($xtop <= 60)
            {
                
                $pdf->ezNewPage();
                $xtop = 530;

                if($_POST['txt_output_type'] !='tab' && $xchecker_2_header == false){
    
                    $xheader = $pdf->openObject();
                    $pdf->saveState();
        
                    $xleft =25;
                    $pdf->setLineStyle(.5);
                    $pdf->line($xleft, $xtop+10, 770, $xtop+10);
                    $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        
                    
                    $xfields_heaeder_counter = 0;
    
                    $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Ordered Date</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Upload Date</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ordered By</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=130,$xtop,"<b>Platform/Item</b>",10,'left');
                    $pdf->ezPlaceData($xleft+=170,$xtop,"<b>Quantity</b>",10,'right');
                    $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Unit Price</b>",10,'right');
                    $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Total</b>",10,'right');
            
                    $xleft = 25;
            
                    $pdf->restoreState();
                    $pdf->closeObject();
                    $pdf->addObject($xheader,'all'); 

                    $xchecker_2_header = true;

                }

                $xtop -= 15;
            }
            $old_docnum = $rs_main['salesorderfile1_docnum'];  
            // $pdf->ezPlaceData(10,$xtop-200,$select_db3,5,"left");

            $xcount_itm2++;
        }
  
        $pdf->line(25, $xtop, 770, $xtop); 
        $xtop -= 15;
        $xleft = 0;

        if($_POST['txt_output_type'] == 'tab'){
            // Include remarks in the tab-delimited output generation
            $tab_output =  "\t\t\t\t\t\tTOTAL\t" .
            $cost_tot . "\n";
            echo $tab_output;
        }else{
            $pdf->ezPlaceData($xleft+=605,$xtop+=5,"<b>TOTAL:</b>",9,"left");
            // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($price_tot,2),9,"right");
            $pdf->ezPlaceData($xleft+=110,$xtop,number_format($cost_tot,2),9,"right");
        }        

        // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_tot,2),9,"right");
        $pdf->line(25, $xtop-=5, 770, $xtop); 
        $xtop -= 15;

        
        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;

            if($_POST['txt_output_type'] !='tab' && $xchecker_2_header == false){

                $xheader = $pdf->openObject();
                $pdf->saveState();
    
                $xleft =25;
                $pdf->setLineStyle(.5);
                $pdf->line($xleft, $xtop+10, 770, $xtop+10);
                $pdf->line($xleft, $xtop-3, 770, $xtop-3);
    
                
                $xfields_heaeder_counter = 0;

                $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Ordered Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Upload Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ordered By</b>",10,'left');
                $pdf->ezPlaceData($xleft+=130,$xtop,"<b>Platform/Item</b>",10,'left');
                $pdf->ezPlaceData($xleft+=170,$xtop,"<b>Quantity</b>",10,'right');
                $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Unit Price</b>",10,'right');
                $pdf->ezPlaceData($xleft+=90,$xtop,"<b>Total</b>",10,'right');
        
                $xleft = 25;
        
                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'all'); 

                $xchecker_2_header = true;

            }

            $xtop -= 15;
        }

        $price_gtot += $price_tot;
        $cost_gtot += $cost_tot;
        // $profit_gtot += $profit_tot;
    }

    $pdf->line(25, $xtop, 770, $xtop); 
    $xtop -= 15;
    $xleft = 0;


    if($_POST['txt_output_type'] == 'tab'){
        // Include remarks in the tab-delimited output generation
        $tab_output =  "\t\t\t\t\t\tGRAND TOTAL\t" .
        // $price_gtot . "\t".
        $cost_gtot . "\n";
        echo $tab_output;
    }else{
        $pdf->ezPlaceData($xleft+=585,$xtop+=5,"<b>GRAND TOTAL:</b>",8,"left");
        // $pdf->ezPlaceData($xleft+=137,$xtop,number_format($price_gtot,2),9,"right");
        $pdf->ezPlaceData($xleft+=130,$xtop,number_format($cost_gtot,2),9,"right");
    }    

    // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_gtot,2),9,"right");
    $pdf->line(25, $xtop-=5, 770, $xtop); 

       
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