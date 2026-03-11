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

		$xheader = $pdf->openObject();
        $pdf->saveState();


        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Purchases Order</b>", 15, 'left' );
            $xtop   -= 15;
            $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
            $xtop   -= 15;
            
            $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
            $xtop   -= 20;
        }

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        
        $xfields_heaeder_counter = 0;

        if($_POST['txt_output_type'] == 'tab'){

            echo "Purchases Order\t\n"; // Use \t for column separation and \n for new rows
            echo "Pdf Report by: " . $_SESSION['userdesc'] . " (Summarized)\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n"; // Blank line for spacing
                
            $tab_headers = "Doc. Num.\tOrder Num.\tTran. Date\tSupp./Item\tQuantity\tUnit Price\tTotal\n";
            echo $tab_headers;
        }else{
            $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Order Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Tran. Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Supp./Item</b>",10,'left');
            $pdf->ezPlaceData($xleft+=195,$xtop,"<b>Quantity</b>",10,'right');
            $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Unit Price</b>",10,'right');
            $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Total</b>",10,'right');
        }        
        

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';


    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $xfilter .= " AND purchasesorderfile1.trndte>='".$_POST['date_from']."' AND purchasesorderfile1.trndte<='".$_POST['date_to']."'";
    }

    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter .= " AND purchasesorderfile1.trndte>='".$_POST['date_from']."'";
    }

    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND purchasesorderfile1.trndte<='".$_POST['date_to']."'";
    }

    
    if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
        $xfilter .= " AND supplierfile.suppdsc='".$_POST['cus_search']."'";
    }

        
    
    $select_db="SELECT purchasesorderfile1.shipto as purchasesorderfile1_shipto,purchasesorderfile1.docnum as purchasesorderfile1_docnum,
    purchasesorderfile1.trndte as purchasesorderfile1_trndte,purchasesorderfile1.trntot as purchasesorderfile1_trntot,purchasesorderfile1.orderby as purchasesorderfile1_orderby,purchasesorderfile1.recid as purchasesorderfile1_recid, purchasesorderfile1.ordernum as purchasesorderfile1_ordernum,
    supplierfile.suppdsc as supplierfile_suppdsc, purchasesorderfile1.paydate as purchasesorderfile1_paydate, purchasesorderfile1.paydetails as purchasesorderfile1_paydetails
     FROM purchasesorderfile1 LEFT JOIN supplierfile ON 
    purchasesorderfile1.suppcde = supplierfile.suppcde WHERE true ".$xfilter." ORDER BY purchasesorderfile1.docnum ASC, purchasesorderfile1.trndte ASC";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    $price_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;
    $old_docnum = '';
    while($rs_main = $stmt_main->fetch()){
        

        $xleft = 25;

        $grand_total += $rs_main["purchasesorderfile1_trntot"];

        if(isset($rs_main["purchasesorderfile1_trndte"]) && !empty($rs_main["purchasesorderfile1_trndte"])){
            $rs_main["purchasesorderfile1_trndte"] = date("m-d-Y",strtotime($rs_main["purchasesorderfile1_trndte"]));
            $rs_main["purchasesorderfile1_trndte"] = str_replace('-','/',$rs_main["purchasesorderfile1_trndte"]);
        }

        if(isset($rs_main["purchasesorderfile1_paydate"]) && !empty($rs_main["purchasesorderfile1_paydate"])){
            $rs_main["purchasesorderfile1_paydate"] = date("m-d-Y",strtotime($rs_main["purchasesorderfile1_paydate"]));
            $rs_main["purchasesorderfile1_paydate"] = str_replace('-','/',$rs_main["purchasesorderfile1_paydate"]);
        }


        if ($_POST['txt_output_type']=='tab')
		{
            $rs_main["supplierfile_suppdsc"] = $rs_main["supplierfile_suppdsc"];
            $rs_main["purchasesorderfile1_shipto"] = $rs_main["purchasesorderfile1_shipto"];
            $rs_main["purchasesorderfile1_paydetails"] = $rs_main["purchasesorderfile1_paydetails"];
            $rs_main["purchasesorderfile1_ordernum"] = $rs_main["purchasesorderfile1_ordernum"];
		}else{
            $rs_main["supplierfile_suppdsc"] = trim_str($rs_main["supplierfile_suppdsc"],155,9);
            $rs_main["purchasesorderfile1_shipto"] = trim_str($rs_main["purchasesorderfile1_shipto"],120,9);
            $rs_main["purchasesorderfile1_paydetails"] = trim_str($rs_main["purchasesorderfile1_paydetails"],140,9);
            $rs_main["purchasesorderfile1_ordernum"] = trim_str($rs_main["purchasesorderfile1_ordernum"],100,9);
        }



        if($_POST['txt_output_type'] == 'tab'){

        }else{
            $pdf->ezPlaceData($xleft,$xtop,$rs_main["purchasesorderfile1_docnum"],9,"left");
            $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["purchasesorderfile1_ordernum"],9,"left");
            $pdf->ezPlaceData($xleft+=110,$xtop,$rs_main["purchasesorderfile1_trndte"],9,"left");
            $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["supplierfile_suppdsc"],9,"left");
        }

        // $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["purchasesorderfile1_shipto"],9,"left");
        // $pdf->ezPlaceData($xleft+=135,$xtop,$rs_main["purchasesorderfile1_paydate"],9,"left");
        // $pdf->ezPlaceData($xleft+=85,$xtop,$rs_main["purchasesorderfile1_paydetails"],9,"left");
        // $pdf->ezPlaceData($xleft+215,$xtop,number_format($rs_main["purchasesorderfile1_trntot"],"2"),9,"right");

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }


        $select_db2="SELECT *,purchasesorderfile2.untprc as 'untprc' FROM purchasesorderfile2 LEFT JOIN itemfile ON purchasesorderfile2.itmcde = itemfile.itmcde WHERE purchasesorderfile2.docnum='".$rs_main['purchasesorderfile1_docnum']."'";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        // $pdf->ezPlaceData(15,$xtop-100,$select_db3,8,"left");
        $price_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
                    $xtop-=12;
        while($rs_main2 = $stmt_main2->fetch()){   

            if ($_POST['txt_output_type']=='tab')
            {
                //$rs_main2["itmdsc"] = $rs_main2["itmdsc"];
            }else{
                //$rs_main2["itmdsc"] = trim_str($rs_main2["itmdsc"],155,9);

            }


            $xleft = 275;

            // $pdf->ezPlaceData($xleft,$xtop,"ITEM:",9,"left");
            if($_POST['txt_output_type'] == 'tab'){

            }else{
                $pdf->ezPlaceData($xleft+=192,$xtop,$rs_main2["itmqty"],9,"right");
            }

            // Define the maximum line width
            $maxLineWidth = 100; // Adjust based on your layout
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

                $pdf->addText(275, $xtop, $fontSize, $line); // Add the line
                $xcounter_item_newline++;
            }

            if($xchecker == true){
                $xcount_total_itmheight = 12 * ($xcounter_item_newline - 1);
            }

            if($_POST['txt_output_type'] == 'tab'){

                if($old_docnum == $rs_main['purchasesorderfile1_docnum'] && $old_docnum !=''){
                    // Include remarks in the tab-delimited output generation
                    $tab_output = "\t\t\t".
                              $rs_main2["itmdsc"]. "\t" .
                              $rs_main2["itmqty"]. "\t" .
                              $rs_main2["untprc"] . "\t" .
                              $rs_main2["extprc"] . "\n";
                }else{
                    // Include remarks in the tab-delimited output generation
                    $tab_output = $rs_main["purchasesorderfile1_docnum"] . "\t" .
                              $rs_main["purchasesorderfile1_ordernum"] . "\t" .
                              $rs_main["purchasesorderfile1_trndte"] . "\t" .
                              $rs_main["supplierfile_suppdsc"] . "\n\t\t\t".
                              $rs_main2["itmdsc"]. "\t" .
                              $rs_main2["itmqty"]. "\t" .
                              $rs_main2["untprc"] . "\t" .
                              $rs_main2["extprc"] . "\n";
                }
            
                echo $tab_output;
            }else{
                $pdf->ezPlaceData($xleft+=112,$xtop+$xcount_total_itmheight,number_format($rs_main2["untprc"],"2"),9,"right");
                $pdf->ezPlaceData($xleft+=110,$xtop+$xcount_total_itmheight,number_format($rs_main2["extprc"],"2"),9,"right");
            }    



            // $profit = $rs_main2["extprc"] - $cost;

            $price_tot+=$rs_main2["untprc"];
            $cost_tot+=$rs_main2["extprc"];
            // $profit_tot+=$profit;

            $xtop -= 15;

            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }

            $old_docnum = $rs_main['purchasesorderfile1_docnum'];  
            // $pdf->ezPlaceData(10,$xtop-200,$select_db3,5,"left");
        }
  
        $pdf->line(25, $xtop, 770, $xtop); 
        $xtop -= 15;
        $xleft = 0;

        if($_POST['txt_output_type'] == 'tab'){
            // Include remarks in the tab-delimited output generation
            $tab_output =  "\t\t\t\t\tTOTAL\t" .
            $cost_tot . "\n";
            echo $tab_output;
        }else{
            $pdf->ezPlaceData($xleft+=580,$xtop+=5,"<b>TOTAL:</b>",9,"left");
            // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($price_tot,2),9,"right");
            $pdf->ezPlaceData($xleft+=110,$xtop,number_format($cost_tot,2),9,"right");
        }

        // $pdf->ezPlaceData($xleft+=110,$xtop,number_format($profit_tot,2),9,"right");
        $pdf->line(25, $xtop-=5, 770, $xtop); 
        $xtop -= 15;

        
        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $price_gtot += $price_tot;
        $cost_gtot += $cost_tot;
        $profit_gtot += $profit_tot;

      
    }

    $pdf->line(25, $xtop, 770, $xtop); 
    $xtop -= 15;
    $xleft = 0;

    if($_POST['txt_output_type'] == 'tab'){
        // Include remarks in the tab-delimited output generation
        $tab_output =  "\t\t\t\t\tGRAND TOTAL\t" .
        // $price_gtot . "\t".
        $cost_gtot . "\n";
        echo $tab_output;
    }else{
        $pdf->ezPlaceData($xleft+=560,$xtop+=5,"<b>GRAND TOTAL:</b>",8,"left");
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