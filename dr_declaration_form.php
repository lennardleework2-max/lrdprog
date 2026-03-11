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
                                     mf_buyers.buyer_name as mf_buyers_buyername,
                                     mf_buyers.buyer_address as mf_buyers_buyeraddress,
                                     mf_buyers.buyer_contactnum as mf_buyers_buyer_contactnum,
                                     mf_buyers.declared_amnt_percent as declared_amnt_percent,
                                     mf_buyers.declared_items as declared_items,
                                     mf_buyers.forwarder_name as forwarder_name,
                                     mf_buyers.forwarder_address as forwarder_address,
                                     mf_buyers.forwarder_contactnum as forwarder_contactnum,
                                     mf_buyers.cargo_name as cargo_name,
                                     mf_buyers.cargo_address as cargo_address,
                                     mf_buyers.cargo_company as cargo_company,
                                     mf_buyers.cargo_cellnum as cargo_cellnum,

                                     tranfile1.shipto as tranfile1_shipto FROM tranfile1 LEFT
        JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde LEFT JOIN mf_buyers
        ON tranfile1.buyer_id = mf_buyers.buyer_id WHERE tranfile1.recid=".$_POST['recid_hidden']."";
        $stmt_main_docnum	= $link->prepare($select_db_docnum);
        $stmt_main_docnum->execute();
        $rs_main_docnum = $stmt_main_docnum->fetch();

        $select_db_from="SELECT * FROM mf_declarationform LIMIT 1";
        $stmt_main_from	= $link->prepare($select_db_from);
        $stmt_main_from->execute();
        $rs_main_from = $stmt_main_from->fetch();

        
        $trntot_1 = str_replace(",","",$_POST['trntot_1']);
        $declared_amount = ($rs_main_docnum['declared_amnt_percent']/100) * (float)$trntot_1;

		$xheader = $pdf->openObject();
        $pdf->saveState();

        $pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');


        // Set red color (RGB: 1, 0, 0)
        $pdf->setColor(1, 0, 0);

        //FIRST RED RECTANGLE
        $pdf->setColor(1, 0, 0, 'fill');

        $xtop =720;

        $pdf->filledRectangle(80, $xtop+=30, 510, 25); // Adjust as needed

        // Add logo
        $logoPath = 'images/ryu_motor_logo.jpg';
        $img = imagecreatefromjpeg($logoPath); // use imagecreatefrompng() if it's PNG

        $pdf->addImage($img, 25, 745, 35); // 100 is width; height is auto-scaled

        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=210, $xtop+=7, "<b>RYU MOTORONE PH</b>", 19, 'left');
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $xtop   -= 10;
        $xleft = 25;

        //FIRST ROW
        //GRAY BOX
        // Set red stroke color (outline only)
        $pdf->setColor(1, 0, 0, 'stroke');
        $pdf->setColor( 0.8,  0.8,  0.8, 'fill');
        $pdf->filledRectangle(120, $xtop-58, 470, 53); // Adjust as needed

        $xtop+=5;
        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'To:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-24, $rs_main_docnum['mf_buyers_buyername'], 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-41, 'Address:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-41, $rs_main_docnum['mf_buyers_buyeraddress'], 11, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-60, 'Contact No. :', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-60, $rs_main_docnum['mf_buyers_buyer_contactnum'], 12, 'left' );
        $xtop-=10;

        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop+=54,$xleft, $xtop-=54); //left line 
        $pdf->line(590, $xtop+=54,590, $xtop-=54); //right line 
        $pdf->line(120, $xtop+=54,120, $xtop-=54); //left line

        $xleft =25;
        //FOR Y COORDINATE SPACING
        $xtop-=3;

        //SECOND ROW
        //GRAY BOX
        // Set red stroke color (outline only)
        $pdf->setColor(1, 0, 0, 'stroke');
        //$pdf->rectangle($xleft, $xtop-=30, 200, 0);
        $pdf->setColor(0.8, 0.8, 0.8, 'fill');
        $pdf->filledRectangle(120, $xtop-63, 470, 53); // Adjust as needed

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'From:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-24, $rs_main_from['from_name'], 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-41, 'Address:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-41, $rs_main_from['from_address'], 11, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-60, 'Contact No. :', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-60, $rs_main_from['from_contactnum'], 12, 'left' );
        $xtop-=10;

        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop+=54,$xleft, $xtop-=54); //left line 
        $pdf->line(590, $xtop+=54,590, $xtop-=54); //right line 
        $pdf->line(120, $xtop+=54,120, $xtop-=54); //left line

        $xleft =25;

        //FOR Y COORDINATE SPACING
        $xtop-=3;

        //THIRD ROW
        //GRAY BOX
        // // Set red stroke color (outline only)
        $pdf->setColor(1, 0, 0, 'stroke');
        $pdf->setColor(0.6, 0.6, 0.6, 'fill');
        $pdf->filledRectangle(135 , $xtop-45, 455, 35); // Adjust as needed

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'Declared Amount:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=110, $xtop-24, "PHP ".$declared_amount , 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-40, 'Declared Item:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=110, $xtop-40, $rs_main_docnum['declared_items'], 11, 'left' );
        $xleft = 25;

        $xtop-=10;
        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line

        $pdf->line($xleft, $xtop+=36,$xleft, $xtop-=36); //left line 
        $pdf->line(590, $xtop+=36,590, $xtop-=36); //right line 
        $pdf->line(135, $xtop+=36,135, $xtop-=36); //left line


        $xtop-=3;

        //FOURTH ROW

        //GRAY BOX
        // Set red stroke color (outline only)
        // $pdf->setColor(1, 0, 0, 'stroke');
        // //$pdf->rectangle($xleft, $xtop-=30, 200, 0);
        // $pdf->setColor(0.7, 0.7, 0.7, 'fill');
        // $pdf->filledRectangle(120, $xtop-55, 470, 45); // Adjust as needed

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'Forwarder:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-24, $rs_main_docnum['forwarder_name'], 12, 'left' );
        $xleft = 25;

        $pdf->ezPlaceData($xleft+=6, $xtop-42, 'Address:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-42, $rs_main_docnum['forwarder_address'], 11, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-60, 'Contact No.', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-60, $rs_main_docnum['forwarder_contactnum'], 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-78, 'Received By:', 12, 'left' );
        //$pdf->ezPlaceData($xleft+=95, $xtop-80, $rs_main_docnum['mf_buyers_buyer_contactnum'], 12, 'left' );

        $pdf->ezPlaceData($xleft+=360, $xtop-78, 'Date/Time:', 12, 'left' );
        //$pdf->ezPlaceData($xleft+=80, $xtop-80, $rs_main_docnum['mf_buyers_buyer_contactnum'], 12, 'left' );



        $xtop-=10;
        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop+=72,$xleft, $xtop-=72); //left line 
        $pdf->line(590, $xtop+=72,590, $xtop-=72); //right line 
        $pdf->line(120, $xtop+=72,120, $xtop-=72); //left line

        $xleft =25;

        // *******************
        // SECOND SET
        // *******************

        // Set red color (RGB: 1, 0, 0)
        $pdf->setColor(1, 0, 0);

        //FIRST RED RECTANGLE
        $pdf->setColor(1, 0, 0, 'fill');

        $pdf->filledRectangle(80, $xtop-=70, 510, 25); // Adjust as needed

        // Add logo
        $logoPath = 'images/ryu_motor_logo.jpg';
        $img = imagecreatefromjpeg($logoPath); // use imagecreatefrompng() if it's PNG

        $pdf->addImage($img, 25, 413, 35); // 100 is width; height is auto-scaled

        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $pdf->ezPlaceData($xleft+=210, $xtop+=7, "<b>RYU MOTORONE PH</b>", 19, 'left');
        $pdf->setColor(0, 0, 0); // RGB values as floats: red = 1, green = 0, blue = 0
        $xtop   -= 10;
        $xleft = 25;

        //(SECOND SET)FIRST ROW
        //GRAY BOX
        // Set red stroke color (outline only)
        $pdf->setColor(1, 0, 0, 'stroke');
        $pdf->setColor( 0.8,  0.8,  0.8, 'fill');
        $pdf->filledRectangle(120, $xtop-58, 470, 53); // Adjust as needed

        $xtop+=5;

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'To:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-24, $rs_main_docnum['mf_buyers_buyername'], 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-41, 'Address:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-41, $rs_main_docnum['mf_buyers_buyeraddress'], 11, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-60, 'Contact No. :', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-60, $rs_main_docnum['mf_buyers_buyer_contactnum'], 12, 'left' );
        $xtop-=10;

        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop+=54,$xleft, $xtop-=54); //left line 
        $pdf->line(590, $xtop+=54,590, $xtop-=54); //right line 
        $pdf->line(120, $xtop+=54,120, $xtop-=54); //left line

        $xleft =25;
        //FOR Y COORDINATE SPACING
        $xtop-=3;


        //(SECOND SET)SECOND ROW
        //GRAY BOX
        // Set red stroke color (outline only)
        $pdf->setColor(1, 0, 0, 'stroke');
        //$pdf->rectangle($xleft, $xtop-=30, 200, 0);
        $pdf->setColor(0.8, 0.8, 0.8, 'fill');
        $pdf->filledRectangle(120, $xtop-63, 470, 53); // Adjust as needed

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'From:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-24, $rs_main_from['from_name'], 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-41, 'Address:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-41, $rs_main_from['from_address'], 11, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-60, 'Contact No. :', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-60, $rs_main_from['from_contactnum'], 12, 'left' );

        $xtop-=10;

        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop+=54,$xleft, $xtop-=54); //left line 
        $pdf->line(590, $xtop+=54,590, $xtop-=54); //right line 
        $pdf->line(120, $xtop+=54,120, $xtop-=54); //left line

        $xleft =25;

        //FOR Y COORDINATE SPACING
        $xtop-=3;

        //(SECOND SET)THIRD ROW
        //GRAY BOX
        // // Set red stroke color (outline only)
        $pdf->setColor(1, 0, 0, 'stroke');
        $pdf->setColor(0.6, 0.6, 0.6, 'fill');
        $pdf->filledRectangle(135 , $xtop-45, 455, 35); // Adjust as needed

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'Declared Amount:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=110, $xtop-24, "PHP ".$declared_amount, 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-40, 'Declared Item:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=110, $xtop-40, $rs_main_docnum['declared_items'], 11, 'left' );
        $xleft = 25;

        $xtop-=10;
        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line

        $pdf->line($xleft, $xtop+=36,$xleft, $xtop-=36); //left line 
        $pdf->line(590, $xtop+=36,590, $xtop-=36); //right line 
        $pdf->line(135, $xtop+=36,135, $xtop-=36); //left line


        $xtop-=3;        

        //(SECOND SET) FOURTH ROW

        //GRAY BOX
        // Set red stroke color (outline only)
        // $pdf->setColor(1, 0, 0, 'stroke');
        // //$pdf->rectangle($xleft, $xtop-=30, 200, 0);
        // $pdf->setColor(0.7, 0.7, 0.7, 'fill');
        // $pdf->filledRectangle(120, $xtop-55, 470, 45); // Adjust as needed

        $pdf->setColor(0, 0, 0);
        $pdf->ezPlaceData($xleft+=6, $xtop-24, 'Forwarder:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-24, $rs_main_docnum['forwarder_name'], 12, 'left' );
        $xleft = 25;

        $pdf->ezPlaceData($xleft+=6, $xtop-42, 'Address:', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-42, $rs_main_docnum['forwarder_address'], 11, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-60, 'Contact No.', 12, 'left' );
        $pdf->ezPlaceData($xleft+=95, $xtop-60, $rs_main_docnum['forwarder_contactnum'], 12, 'left' );
        $xleft = 25;
        $pdf->ezPlaceData($xleft+=6, $xtop-78, 'Received By:', 12, 'left' );
        //$pdf->ezPlaceData($xleft+=95, $xtop-80, $rs_main_docnum['mf_buyers_buyer_contactnum'], 12, 'left' );

        $pdf->ezPlaceData($xleft+=360, $xtop-78, 'Date/Time:', 12, 'left' );
        //$pdf->ezPlaceData($xleft+=80, $xtop-80, $rs_main_docnum['mf_buyers_buyer_contactnum'], 12, 'left' );

        $xtop-=10;
        $xtop+=10;
        $xleft = 25;

        //THE LINE BOX
        $pdf->line($xleft, $xtop-=10,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop-=18,590, $xtop); // vertical line
        $pdf->line($xleft, $xtop+=72,$xleft, $xtop-=72); //left line 
        $pdf->line(590, $xtop+=72,590, $xtop-=72); //right line 
        $pdf->line(120, $xtop+=72,120, $xtop-=72); //left line

        $xleft =25;        

        $pdf->ezNewPage();

        $xtop = 800;
        $xleft =25;

        $xtop-=250;

        //FOR CARGO COMPANY
        // Define the maximum line width
        $maxLineWidth = 350; // Adjust based on your layout
        //$fontSize = 40;
        $fontSize = 38;

        // Break the text into lines
        $lines = breakTextIntoLines($pdf, $rs_main_docnum["cargo_company"], $maxLineWidth, $fontSize);

        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 33; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText(80, $xtop, $fontSize, "<b>".$line."</b>"); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 33 * ($xcounter_item_newline - 1);
        }

        //------------------------
        //FOR ADDRESS

        // Define the maximum line width
        $maxLineWidth = 475; // Adjust based on your layout
        //$fontSize = 30;
        $fontSize = 28;

        $xtop = $xtop - 40 - $xcount_total_itmheight;
        

        // Break the text into lines
        $lines = breakTextIntoLines($pdf, $rs_main_docnum["cargo_address"], $maxLineWidth, $fontSize);

        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 28; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText(80, $xtop, $fontSize, $line); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 28 * ($xcounter_item_newline - 1);
        }


        //------------------------
        //FOR CELLPHONE NUMBER

        // Define the maximum line width
        $maxLineWidth = 350; // Adjust based on your layout
        $fontSize = 26; // 29;
        
        $xtop = $xtop - 20 - $xcount_total_itmheight;
        
        $pdf->ezPlaceData(80, $xtop, 'CP', 26, 'left' );

        // Break the text into lines
        $lines = breakTextIntoLines($pdf, $rs_main_docnum["cargo_cellnum"], $maxLineWidth, $fontSize);


        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 30; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText(130, $xtop, $fontSize, $line); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 30 * ($xcounter_item_newline - 1);
        } 
        
        
        //------------------------
        //FOR CARGO NAME

        // Define the maximum line width
        $maxLineWidth = 350; // Adjust based on your layout
        $fontSize = 26; //29;
        
        $xtop = $xtop - 35 - $xcount_total_itmheight;
        
        $pdf->ezPlaceData(80, $xtop, 'SHIP THRU:', 26, 'left' );

        // Break the text into lines
        $lines = breakTextIntoLines($pdf, $rs_main_docnum["cargo_name"], $maxLineWidth, $fontSize);


        $xcounter_item_newline = 0;
        $xchecker = false;
        $xchecker_add = 0;
        $xcount_total_itmheight = 0;
        foreach ($lines as $line) {

            if($xcounter_item_newline != 0){
                $xtop -= 30; // Adjust for line spacing
                $xchecker = true;
            }

            $pdf->addText(240, $xtop, $fontSize, "<b>".$line."</b>"); // Add the line
            $xcounter_item_newline++;
        }

        if($xchecker == true){
            $xcount_total_itmheight = 30 * ($xcounter_item_newline - 1);
        }    


        



	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';

    $footerObject = $pdf->openObject(); // start footer object
    $pdf->saveState();




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