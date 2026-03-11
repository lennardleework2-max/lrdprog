<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass_new/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');

    ob_start();

    $xreport_title = "List of items";
		

    if ($_POST['txt_output_type']=='tab')
	{
		$pdf = new tab_ezpdf('Letter','landscape');
	}
	else
	{
		$pdf = new Cezpdf('Letter','portrait');
		$pdf ->selectFont("ezpdfclass_new/fonts/Helvetica.afm");
	}

		
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	
	$xtop = 760;
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

        $select_db_docnum="SELECT *, tranfile1.docnum as tranfile1_docnum,
                                     customerfile.cusdsc as customerfile_cusdsc,
                                     tranfile1.orderby as tranfile1_orderby,
                                     tranfile1.shipto as tranfile1_shipto FROM tranfile1  LEFT
        JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde WHERE tranfile1.recid=".$_POST['recid_hidden']."";
        $stmt_main_docnum	= $link->prepare($select_db_docnum);
        $stmt_main_docnum->execute();
        $rs_main_docnum = $stmt_main_docnum->fetch();


		$xheader = $pdf->openObject();
        $pdf->saveState();

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft, $xtop-=20, "<b>" . $rs_main_docnum['customerfile_cusdsc']. "</b>", 28, 'left');
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"Carreido, San Juan, Manila", 12, 'left' );


        $pdf->ezPlaceData($xleft+=320, $xtop,"DELIVERY RECEIPT: ", 12, 'left' );
        

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+115, $xtop+15,"STRICT 90 DAYS ONLY ", 10, 'left' );
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=130, $xtop,$rs_main_docnum['ordernum'], 12, 'left' );
        $xleft = 25;
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $xtop   -= 25;

        $pdf->setLineStyle(.5);
        $pdf->setStrokeColor(1, 0, 0); // Set line color to red (RGB: 1, 0, 0)
		$pdf->line($xleft, $xtop+10, 590, $xtop+10);
        $pdf->setStrokeColor(0, 0, 0); // Set line color to red (RGB: 1, 0, 0)
        $xtop-=15;

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft, $xtop, 'Date:', 10, 'left' );
        $pdf->ezPlaceData($xleft+=200, $xtop, 'To:', 10, 'left' );
        $pdf->ezPlaceData($xleft+=200, $xtop, 'Ship To:', 10, 'left' );
        $xtop -=13;
        $xleft = 25;

        $formatted_date = date('F j, Y', strtotime($_POST['date_report_dr']));

        if(!isset($_POST['date_report_dr']) || empty($_POST['date_report_dr'])){
            $formatted_date = '';
        }

        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft, $xtop, $formatted_date, 10, 'left' );

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=200, $xtop, 'Name: ', 10, 'left' );
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=35, $xtop, $rs_main_docnum['tranfile1_orderby'], 10, 'left' );

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=165, $xtop, 'Name: ', 10, 'left' );
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=35, $xtop, $rs_main_docnum['tranfile1_orderby'], 10, 'left' );
        $xtop-=13;

        $xleft = 25;



        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=200, $xtop, 'Address: ', 10, 'left' );

        $pdf->setColor(1, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=200, $xtop, 'Shipping Method: ', 10, 'left' );
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=84, $xtop, $_POST['shipping_method_dr'], 10, 'left' );

        // Break the text into lines

        $xtop-=13;
        $lines = breakTextIntoLines($pdf, $rs_main_docnum["tranfile1_shipto"], 250, 13);

        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 12; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText(225, $xtop, 10, $line); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 12 * ($xcounter_item_newline - 1);
        }

        $xtop-=15;

        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $xleft = 25;
        $xtop -= $xcount_total_itmheight;
        //$xtop   -= 20;
        
		$pdf->setLineStyle(.5);
		// $pdf->line($xleft, $xtop+10, 590, $xtop+10);
        // $pdf->line($xleft, $xtop-3, 590, $xtop-3);
        

        $xfields_heaeder_counter = 0;

        // Draw red box
        $pdf->setColor(1, 0, 0); // red

        $xtop-=10;

        $pdf->filledRectangle($xleft, $xtop, 570, 20); // box at ($xleft, $xtop)

        // Set text color (e.g., white for contrast)
        $pdf->setColor(1, 1, 1); // white text

        // Slightly raise text Y position (so it appears *inside* the box and centered)
        $textY = $xtop + 8; // Adjust if needed for better vertical alignment

        $x = $xleft + 50;
        $pdf->ezPlaceData($x, $textY, "<b>Quantity</b>", 10, 'right');

        $x += 55;
        $pdf->ezPlaceData($x, $textY, "<b>Description</b>", 10, 'left');

        $x += 310;
        $pdf->ezPlaceData($x, $textY, "<b>Unit Price</b>", 10, 'right');

        $x += 80;
        $pdf->ezPlaceData($x, $textY, "<b>Total</b>", 10, 'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';

    $footerObject = $pdf->openObject(); // start footer object
    $pdf->saveState();

    $xleft = 25;

    //THIS IS A BOX
    $x = 0;
    $y = 25;
    $width = 570;
    $height = 50;

    // Set stroke (border) color to black
    $pdf->setStrokeColor(0, 0, 0); // black

    // Optional: set line thickness
    $pdf->setLineStyle(1); // 1 point thick line

    // Draw rectangle only (no fill)
    // Syntax: rectangle(x, y, width, height)
    $x = 25;
    $y = 70;
    $width = 320;
    $height = 60;

    $pdf->rectangle($x, $y, $width, $height); // just outline



    // Footer content at bottom center
    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft+=5, 115, 12, 'Bank: ');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=35, 115, 12, 'RCBC');

    $xleft = 25;

    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft+=5, 100, 12, 'Account Name:');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=85, 100, 12, 'One Pacific Global Ventures Corporation');

    $xleft = 25;

    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft+=5, 85, 12, 'Account No:');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=75, 85, 12, '0000007591213241');

    $xleft = 25;



    // Footer content at bottom center
    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft, 50, 10, 'Company: ');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=50, 50, 10, 'One Pacific Global Corp');

    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft+=125, 50, 10, 'Address: ');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=45, 50, 10, 'SteelWorld Tower NS Amoranto cor. Biak na Bato, Quezon City');
    $xleft = 25;

    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft, 35, 10, 'Tel: ');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=20, 35, 10, '09164338621');

    $pdf->setColor(1, 0, 0); // red
    $pdf->addText($xleft+=155, 35, 10, 'Email');
    $pdf->setColor(0, 0, 0); // red
    $pdf->addText($xleft+=35, 35, 10, 'marketing.megaone@gmail.com');

    // Restore and close
    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($footerObject, 'all'); // apply to all pages


    // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    // (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

    //     $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
    //     $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

    //     $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    // }

    // else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
    //     $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
    //     $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    // }

    // else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
    //     $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
    //     $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    // }

    
    // if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
    //     $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
    // }

    // if(isset($_POST['cus_to']) && !empty($_POST['cus_to'])){
    //     $xfilter .= " AND customerfile.cusdsc<='".$_POST['cus_to']."'";
    // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (tranfile1.paydate!='' OR tranfile1.paydate IS NOT NULL)";
        // }




    $select_db="SELECT *, tranfile2.itmqty as tranfile2_itmqty,
                          itemfile.itmdsc as itemfile_itmdsc,
                          tranfile2.untprc as tranfile2_untprc,
                          tranfile2.extprc as tranfile2_extprc
    FROM tranfile2 LEFT JOIN
    itemfile ON tranfile2.itmcde = itemfile.itmcde WHERE tranfile2.docnum='".$rs_main_docnum['tranfile1_docnum']."'";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    while($rs_main = $stmt_main->fetch()){    

        $xleft = 25;

        // $grand_total += $rs_main["tranfile1_trntot"];


        // if ($_POST['txt_output_type']=='tab')
		// {
        //     $rs_main["customerfile_cusdsc"] = $rs_main["customerfile_cusdsc"];
        //     $rs_main["tranfile1_shipto"] = $rs_main["tranfile1_shipto"];
        //     $rs_main["tranfile1_paydetails"] = $rs_main["tranfile1_paydetails"];
        //     $rs_main["tranfile1_ordernum"] = $rs_main["tranfile1_ordernum"];
		// }else{
        //     $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],65,9);
        //     $rs_main["tranfile1_shipto"] = trim_str($rs_main["tranfile1_shipto"],120,9);
        //     $rs_main["tranfile1_paydetails"] = trim_str($rs_main["tranfile1_paydetails"],140,9);
        //     $rs_main["tranfile1_ordernum"] = trim_str($rs_main["tranfile1_ordernum"],100,9);
        // }


        $pdf->ezPlaceData($xleft+=50,$xtop,$rs_main["tranfile2_itmqty"],9,"right");

        $lines = breakTextIntoLines($pdf, $rs_main["itemfile_itmdsc"], 280, 13);

        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 12; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText(130, $xtop, 10, $line); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 9 * ($xcounter_item_newline - 1);
        }


        $pdf->ezPlaceData($xleft+=365,$xtop+$xcount_total_itmheight,number_format($rs_main["tranfile2_untprc"],2),9,"right");
        $pdf->ezPlaceData($xleft+=80,$xtop+$xcount_total_itmheight,number_format($rs_main["tranfile2_extprc"],2),9,"right");

        $pdf->setLineStyle(.5);
        $pdf->setStrokeColor(0.5, 0.5, 0.5); // Light gray (R, G, B)
        $pdf->line(25, $xtop-3, 590, $xtop-3);

        $grand_total +=$rs_main["tranfile2_extprc"];

        
        $xtop -= 15;

        

        if($xtop <= 150)
        {
            $pdf->ezNewPage();
            $xtop = 580;
        }
        
    }

       
    $xleft = 25;
    $xtop+=10;
    $pdf->setStrokeColor(1, 0, 0); // Set line color to red (RGB: 1, 0, 0)
    $pdf->line(25, $xtop-10, 590, $xtop-10); 
    $pdf->ezPlaceData($xleft+=430,$xtop-20,"<b>Total Amount Due:</b>",9 ,'right');
    $pdf->ezPlaceData($xleft+=65,$xtop-20,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
    $xtop-=13;
    $pdf->line(25, $xtop-10, 590, $xtop-10); 
    $pdf->setStrokeColor(0, 0, 0); // Set line color to red (RGB: 1, 0, 0)

    $pdf->setColor(1, 0, 0); // Set line color to red (RGB: 1, 0, 0)
    $pdf->ezPlaceData(520,$xtop-=30,"Thank You for your business!",12,'right');
    $pdf->setColor(0, 0, 0); // Set line color to red (RGB: 1, 0, 0)






	//$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
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