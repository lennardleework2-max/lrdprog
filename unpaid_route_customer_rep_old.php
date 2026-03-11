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
	
	$xtop = 680;
    $xleft = 30;


    $logoPath = 'images/ryu_motor_logo.jpg';   // relative to project root
    $title    = 'Unpaid Sales Route';

    // Page metrics
    $pw = $pdf->ez['pageWidth'];
    $ph = $pdf->ez['pageHeight'];
    $lm = $pdf->ez['leftMargin'];
    $rm = $pdf->ez['rightMargin'];
    $topY = $ph - 30; // top padding from page edge


    // Left logo
    if (is_file($logoPath)) {
        // x, y (baseline), width; height auto
        $pdf->addJpegFromFile($logoPath, $lm, $topY - 62, 70);
    }

    // Right title (red)
    $pdf->saveState();
    $pdf->setColor(0.85, 0.10, 0.10);               // text color (RGB 0..1)
    $fontSize = 12;
    $tw = $pdf->getTextWidth($fontSize, $title);    // for right-aligning
    $pdf->addText($pw - $rm - $tw, $topY - 8, $fontSize, $title);
    $pdf->restoreState();

    //add the bill to text

    // Thin separator line under header
    $pdf->saveState();
    // $pdf->setLineStyle(0.5);                        // line thickness
    // $pdf->line($lm, $topY - 18, $pw - $rm, $topY - 18);
    $pdf->restoreState();


    /**header**/
    $pdf->ezPlaceData($xleft,$xtop,"<b>BILL TO:</b>",13,'left');
    $xtop-=18;
    $pdf->ezPlaceData($xleft,$xtop,"MOTOHUB",13,'left');
    $xtop-=18;
    $pdf->ezPlaceData($xleft,$xtop,"Palma Gil Brgy. 17B, Davao City",13,'left');
    $xtop-=18;
    $pdf->ezPlaceData($xleft,$xtop,"0917-700-0224",13,'left');

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

        // Draw red header bar with column dividers and centered white labels
        // Use $xtop as the TOP of the bar
        $xtop -= 10;          // spacing before the bar

        // Bar geometry (top anchored at $xtop)
        $barX = $xleft;                         // left edge
        $barW = $pw - $rm - $barX;              // dynamic width based on portrait/landscape
        $barH = 22;                             // header bar height
        $barY = $xtop - $barH;                  // rectangle uses bottom-left origin

        // Column widths (sum equals $barW). Tweaked for portrait.
        $colW0 = (int)round($barW * 0.28); // SAL NO.
        $colW1 = (int)round($barW * 0.24); // DATE
        $colW2 = (int)round($barW * 0.26); // DR NO.
        $colW3 = $barW - ($colW0 + $colW1 + $colW2); // TOTAL (remainder)
        $colW = [$colW0, $colW1, $colW2, $colW3];
        $colX = [$barX, $barX + $colW[0], $barX + $colW[0] + $colW[1], $barX + $colW[0] + $colW[1] + $colW[2]];

        // Fill and border
        $pdf->setColor(0.85, 0.10, 0.10, 'fill');
        $pdf->filledRectangle($barX, $barY, $barW, $barH);
        $pdf->setColor(0, 0, 0, 'stroke');
        $pdf->rectangle($barX, $barY, $barW, $barH);
        // Vertical separators
        $pdf->line($colX[1], $barY, $colX[1], $barY + $barH);
        $pdf->line($colX[2], $barY, $colX[2], $barY + $barH);
        $pdf->line($colX[3], $barY, $colX[3], $barY + $barH);

        // Labels
        $pdf->setColor(1, 1, 1); // white text
        $labelY = $barY + 6;     // baseline inside bar
        $pdf->ezPlaceData($barX + ($colW[0] / 2), $labelY, '<b>SAL NO.</b>', 10, 'center');
        $pdf->ezPlaceData($colX[1] + ($colW[1] / 2), $labelY, '<b>DATE</b>', 10, 'center');
        $pdf->ezPlaceData($colX[2] + ($colW[2] / 2), $labelY, '<b>DR NO.</b>', 10, 'center');
        $pdf->ezPlaceData($colX[3] + ($colW[3] / 2), $labelY, '<b>TOTAL</b>', 10, 'center');

        // Reset text color and cursor
        $pdf->setColor(0, 0, 0);
        $xleft = 25;
        $xtop = $barY - 6; // move below header, keep using $xtop

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';


        // if((isset($_POST['doc_from']) && !empty($_POST['doc_from'])) &&
        // (isset($_POST['doc_to']) && !empty($_POST['doc_to'])) ){

        //     $xfilter .= " AND tranfile1.docnum>='".$_POST['doc_from']."' AND tranfile1.docnum<='".$_POST['doc_to']."'";
        // }

        // else if(isset($_POST['doc_from']) && !empty($_POST['doc_from'])){
        //     $xfilter .= " AND tranfile1.docnum>='".$_POST['doc_from']."'";
        // }

        // else if(isset($_POST['doc_to']) && !empty($_POST['doc_to'])){

        //     $xfilter .= " AND tranfile1.docnum<='".$_POST['doc_to']."'";
        // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (tranfile1.paydate!='' OR tranfile1.paydate IS NOT NULL)";
        // }


    if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }

    if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    if(isset($_POST['cus_add_hidden']) && !empty($_POST['cus_add_hidden'])){
        $xfilter .= " AND tranfile1.buyer_id<='".$_POST['cus_add_hidden']."'";
    }

    //selecting empty salesman
    $select_db_empty_salesman="SELECT * FROM mf_salesman WHERE salesman_name='-None' LIMIT 1";
    $stmt_main_empty_salesman	= $link->prepare($select_db_empty_salesman);
    $stmt_main_empty_salesman->execute();
    $rs_main_empty_salesman = $stmt_main_empty_salesman->fetch();


    $select_db = "SELECT * FROM tranfile1 WHERE true ".$xfilter." AND (paydate IS NULL OR paydate = '') AND salesman_id!='".$rs_main_empty_salesman['salesman_id']."'  "; 
    
    // $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
    // tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid,tranfile1.ordernum as tranfile1_ordernum,
    // customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    // customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON 
    // tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.docnum ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;

    $xtop+=3;
    while($rs_main = $stmt_main->fetch()){    

        $xleft = 30;

        // $grand_total += $rs_main["tranfile1_trntot"];

        // if(isset($rs_main["tranfile1_trndte"]) && !empty($rs_main["tranfile1_trndte"])){
        //     $rs_main["tranfile1_trndte"] = date("m-d-Y",strtotime($rs_main["tranfile1_trndte"]));
        //     $rs_main["tranfile1_trndte"] = str_replace('-','/',$rs_main["tranfile1_trndte"]);
        // }

        // if(isset($rs_main["tranfile1_paydate"]) && !empty($rs_main["tranfile1_paydate"])){
        //     $rs_main["tranfile1_paydate"] = date("m-d-Y",strtotime($rs_main["tranfile1_paydate"]));
        //     $rs_main["tranfile1_paydate"] = str_replace('-','/',$rs_main["tranfile1_paydate"]);
        // }

        // if ($_POST['txt_output_type']=='tab')
		// {
        //     $rs_main["customerfile_cusdsc"] = $rs_main["customerfile_cusdsc"];
        //     $rs_main["tranfile1_shipto"] = $rs_main["tranfile1_shipto"];
        //     $rs_main["tranfile1_paydetails"] = $rs_main["tranfile1_paydetails"];
        //     $rs_main["tranfile1_ordernum"] = $rs_main["tranfile1_ordernum"];
		// }else{
        //     $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],65,9);
        //     $rs_main["tranfile1_paydetails"] = trim_str($rs_main["tranfile1_paydetails"],140,9);
        //     $rs_main["tranfile1_shipto"] = trim_str($rs_main["tranfile1_shipto"],120,9);
        //     $rs_main["tranfile1_ordernum"] = trim_str($rs_main["tranfile1_ordernum"],100,9);
        // }

        // Table row geometry (match header columns)
        $rowH  = 22;
        $rowY  = $xtop - $rowH;                // rectangle origin is bottom-left
        $tblX  = $xleft;                       // same as header left
        $tblW  = $pw - $rm - $tblX;            // same width logic as header

        // Compute column widths/positions (same proportions as header)
        $colW0 = (int)round($tblW * 0.28);     // SAL NO.
        $colW1 = (int)round($tblW * 0.24);     // DATE
        $colW2 = (int)round($tblW * 0.26);     // DR NO.
        $colW3 = $tblW - ($colW0 + $colW1 + $colW2); // TOTAL
        $cX0 = $tblX;                          // left of col 0
        $cX1 = $cX0 + $colW0;                  // left of col 1
        $cX2 = $cX1 + $colW1;                  // left of col 2
        $cX3 = $cX2 + $colW2;                  // left of col 3

        // Draw row border and column separators
        $pdf->setColor(0, 0, 0, 'stroke');
        $pdf->rectangle($tblX, $rowY, $tblW, $rowH);
        $pdf->line($cX1, $rowY, $cX1, $rowY + $rowH);
        $pdf->line($cX2, $rowY, $cX2, $rowY + $rowH);
        $pdf->line($cX3, $rowY, $cX3, $rowY + $rowH);

        // Place text inside cells
        // Format date as mm/dd/yyyy
        $pdf->setColor(0, 0, 0);
        $ty = $rowY + 6;                       // baseline inside row
        $fs = 10;                              // font size
        $trndte_disp = '';
        if (!empty($rs_main["trndte"]) && $rs_main["trndte"] != '0000-00-00') {
            $ts = strtotime($rs_main["trndte"]);
            if ($ts !== false) { $trndte_disp = date('m/d/Y', $ts); }
        }
        $pdf->ezPlaceData($cX0 + ($colW0/2), $ty, $rs_main["docnum"], $fs, 'center');
        $pdf->ezPlaceData($cX1 + ($colW1/2), $ty, $trndte_disp, $fs, 'center');
        $pdf->ezPlaceData($cX2 + ($colW2/2), $ty, $rs_main["ordernum"], $fs, 'center');
        $pdf->ezPlaceData($cX3 + $colW3 - 6,   $ty, number_format($rs_main["trntot"], 2), $fs, 'right');

        // Advance cursor below this row
        $xtop = $rowY - 2;
        

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }
        
    }

       
    $pdf->line(25, $xtop-10, 770, $xtop-10); 
    $pdf->ezPlaceData(700,$xtop-18,"<b>Grand total:</b>",9 ,'right');
    $pdf->ezPlaceData(765,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
   
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
