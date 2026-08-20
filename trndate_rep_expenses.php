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
        $pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");
	}
	else
	{
		$pdf = new Cezpdf('Letter','landscape');
		$pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");
	}

	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	
	$xtop = 580;
    $xleft = 25;

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';
    $xheader_check = false;

		$xheader = $pdf->openObject();
        $pdf->saveState();
        
            if($_POST['txt_output_type'] == 'tab'){
                $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
            }else{
                $pdf->ezPlaceData($xleft, $xtop,"<b>Expenses by Tran. Date (Summarized)</b>", 15, 'left' );
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
    
        $xfilter .= " AND expensefile1.trndte>='".$xdate_from_filter."' AND expensefile1.trndte<='".$xdate_to_filter."'";
    
        
        
        $xheader_first_page = $pdf->openObject();
        $pdf->saveState();

        $xdate_from_display = $xdate_from_filter;
        $xdate_from_display = new DateTime($xdate_from_display);
        $xdate_from_display = $xdate_from_display->format('m/d/Y');

        $xdate_to_display = $xdate_to_filter;
        $xdate_to_display = new DateTime($xdate_to_display);
        $xdate_to_display = $xdate_to_display->format('m/d/Y');        

        if($_POST['txt_output_type'] != 'tab'){
                $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
                $xtop-=15;

                $pdf->ezPlaceData($xleft,$xtop,"<b>Date From:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,$xdate_from_display,10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Date To:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=50,$xtop,$xdate_to_display,10,'left');
                $xtop-=15;

        }else{

            echo "Expenses by Tran. Date (Summarized)\t\n";
            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n";

                echo "FILTER:\n";
                echo "Date From: ".$xdate_from_display."\t";
                echo "Date To: ".$xdate_to_display."\t\n";

            $tab_headers = "Doc. Num.\tVat Type\tExpense Type\tRemarks\tTotal\t";
            echo $tab_headers;
        }


        $xleft =25;
		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-13, 770, $xtop-13);
        
        $xfields_heaeder_counter = 0;

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=100,$xtop,"<b>Vat Type</b>",10,'left');
            $pdf->ezPlaceData($xleft+=120,$xtop,"<b>Expense Type</b>",10,'left');
            $pdf->ezPlaceData($xleft+=150,$xtop,"<b>Remarks</b>",10,'left');
            $pdf->ezPlaceData($xleft+=273,$xtop,"<b>Total</b>",10,'right');
        }
       

        $xtop-=25;
        // Close the object
        $pdf->restoreState();
        $pdf->closeObject();        

        // Add the object to only the first page
		$pdf->addObject($xheader_first_page,'add');

	/***header**/

    #region DO YOU LOOP HERE


    $select_db="SELECT * FROM expensefile1
                    LEFT JOIN expensetypefile ON expensefile1.expense_cde = expensetypefile.expense_cde
                    LEFT JOIN vat_typefile ON vat_typefile.vat_cde = expensefile1.vat_cde
    WHERE true ".$xfilter." ORDER BY expensefile1.docnum ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    while($rs_main = $stmt_main->fetch()){    

        $xleft = 25;

        $grand_total += $rs_main["trntot"];

        if ($_POST['txt_output_type']=='tab')
		{
            $rs_main["remarks"] = $rs_main["remarks"];
		}else{
            $rs_main["remarks"] = trim_str($rs_main["remarks"],400,9);
        }

        $pdf->ezPlaceData($xleft,$xtop,$rs_main["docnum"],9,"left");
        $pdf->ezPlaceData($xleft+=100,$xtop,$rs_main["vat_dsc"],9,"left");
        $pdf->ezPlaceData($xleft+=120,$xtop,$rs_main["expense_dsc"],9,"left");
        $pdf->ezPlaceData($xleft+=150,$xtop,$rs_main["remarks"],9,"left");
        $pdf->ezPlaceData($xleft+273,$xtop,number_format($rs_main["trntot"],"2"),9,"right");

        $xleft_next = $xleft+273;
        
        $xtop -= 15;

    
        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;

            $xfields_heaeder_counter = 0;

            if($_POST['txt_output_type'] !='tab' && $xheader_check == false){

                $xheader = $pdf->openObject();
                $pdf->saveState();
    
                $xleft =25;
                $pdf->setLineStyle(.5);
                $pdf->line($xleft, $xtop+10, 770, $xtop+10);
                $pdf->line($xleft, $xtop-13, 770, $xtop-13);

                $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=100,$xtop,"<b>Vat Type</b>",10,'left');
                $pdf->ezPlaceData($xleft+=120,$xtop,"<b>Expense Type</b>",10,'left');
                $pdf->ezPlaceData($xleft+=150,$xtop,"<b>Remarks</b>",10,'left');
                $pdf->ezPlaceData($xleft+=273,$xtop,"<b>Total</b>",10,'right');
                $xleft = 25;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'all');   

                $xheader_check = true;
            }
            $xtop -= 25;
        }
        
    }
    
    if ($_POST['txt_output_type']=='tab')
	{
        // Include remarks in the tab-delimited output generation
        $pdf->ezPlaceData(1,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(2,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(3,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(680,$xtop-18,"<b>Grand total:</b>",9 ,'right');
        $pdf->ezPlaceData(765,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
	}else{
        $pdf->line(25, $xtop-10, 770, $xtop-10); 
        $pdf->ezPlaceData($xleft_next-=85,$xtop-18,"<b>Grand total:</b>",9 ,'right');
        $pdf->ezPlaceData($xleft_next+=85,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
    }

       
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