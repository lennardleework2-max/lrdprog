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
    $xpage_counter = 0;

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


		$xheader = $pdf->openObject();
        $pdf->saveState();

        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Expenses by Expense Type</b>", 15, 'left' );
            $xtop   -= 15;
            $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']."</b>", 9, 'left' );
            $xtop   -= 15;
            
            $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
            $xtop   -= 15;
        }

        $pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');     
        
	        $xfilter = '';
	        $xorder = '';
	    
	        if(isset($_POST['expense_type']) && !empty($_POST['expense_type'])){
	            $xfilter .= " AND expensetypefile.expense_cde='".$_POST['expense_type']."'";
	        }        

		$pdf->setLineStyle(.5);
        $xleft = 25;

	        $xheader_first_page = $pdf->openObject();
	        $pdf->saveState();

	        if($_POST['txt_output_type'] != 'tab'){
	            if(isset($_POST['expense_type']) && !empty($_POST['expense_type'])){
	                $select_db_filter = "SELECT * FROM expensetypefile WHERE expense_cde='".$_POST['expense_type']."' ";
	                $stmt_main_filter	= $link->prepare($select_db_filter);
	                $stmt_main_filter->execute();
	                $rs_main_filter = $stmt_main_filter->fetch();

	                $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
	                $xtop-=15; 

	                $pdf->ezPlaceData($xleft,$xtop,"<b>Expense Type:</b>",10,'left');
	                $pdf->ezPlaceData($xleft+=80,$xtop,$rs_main_filter['expense_dsc'],10,'left');

	                $xtop-=15;
	            }
	        }else{

	            echo "Expenses by Expense Type\t\n"; // Use \t for column separation and \n for new rows
	            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
	            echo "Date Printed : " . $date_printed . "\t\n";
	            echo "\n"; // Blank line for spacing

	            if(isset($_POST['expense_type']) && !empty($_POST['expense_type'])){
	                $select_db_filter = "SELECT * FROM expensetypefile WHERE expense_cde='".$_POST['expense_type']."' ";
	                $stmt_main_filter	= $link->prepare($select_db_filter);
	                $stmt_main_filter->execute();
	                $rs_main_filter = $stmt_main_filter->fetch();

	                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
	                echo "Expense Type: ".$rs_main_filter['expense_dsc']."\t\n";
	            }
	        }              

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');

	/***header**/
    #region DO YOU LOOP HERE
    $select_db = "SELECT * FROM expensetypefile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    $xcounter_rows = 0;
    $old_item = '';
    while($rs_main = $stmt_main->fetch()){

        $xcounter_rows++;
        $xpage_counter++;  
        
        if($_POST['txt_output_type'] == 'tab'){
            $tab_output =  "Expense Type :\t".$rs_main['expense_dsc']. "\n";
            echo $tab_output;
        }else{
            $pdf->ezPlaceData(25,$xtop-9,"<b>Expense Type:</b>",10 ,'left');
            $pdf->ezPlaceData(105,$xtop-9,$rs_main['expense_dsc'],10 ,'left');
        }

        $pdf->line(25, $xtop-12, 770, $xtop-12); 

        $xtop-=12;
        $xleft = 25;

	        if($_POST['txt_output_type'] == 'tab'){
	            $pdf->ezPlaceData($xleft,$xtop-9,"",9 ,'left');
	            $tab_headers = "Doc. Num.\tVat Type\tExpense Type\tRemarks\tTotal\t\n";
	            echo $tab_headers;
	        }else{
	                $pdf->ezPlaceData($xleft,$xtop-9,"<b>Doc. Num.</b>",10,'left');
	                $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>Vat Type</b>",10,'left');
	                $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Expense Type</b>",10,'left');
	                $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Remarks</b>",10,'left');
	                $pdf->ezPlaceData(768,$xtop-9,"<b>Total</b>",10,'right');
	        }


        $pdf->line(25, $xtop-23, 770, $xtop-23); 
        $xtop-=33;

	        $select_db2 = "SELECT expensefile1.docnum, expensefile1.remarks, expensefile1.trntot,
	                    expensefile1.recid, expensetypefile.expense_dsc, vat_typefile.vat_dsc
	                    FROM expensefile1 LEFT JOIN expensetypefile
	                    ON expensefile1.expense_cde = expensetypefile.expense_cde LEFT JOIN vat_typefile
	                    ON vat_typefile.vat_cde = expensefile1.vat_cde
	                        WHERE expensefile1.expense_cde='".$rs_main['expense_cde']."' ORDER BY expensefile1.recid ASC";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $subtotal = 0;
        $subtotal_itmqty = 0;
        $subtotal_weighted = 0;

        while($rs_main2 = $stmt_main2->fetch()){   

            $xleft = 25;
            $subtotal+=$rs_main2["trntot"];
            $grand_total+=$rs_main2["trntot"];
            // $subtotal_itmqty+=$rs_main2["itmqty"];

            // $grand_total += $rs_main2["purchasesorderfile1_trntot"];


	            if ($_POST['txt_output_type'] !='tab')
	            {
	                $rs_main2["remarks"] = trim_str($rs_main2["remarks"],135,9);
	            }

	            if($_POST['txt_output_type'] == 'tab'){
	                    $tab_output = $rs_main2['docnum'] . "\t" .
	                    $rs_main2["vat_dsc"] . "\t".
	                    $rs_main2["expense_dsc"]. "\t" .
	                    $rs_main2["remarks"]. "\t" .
	                    $rs_main2["trntot"] . "\n";
	                echo $tab_output;
	            }else{

	                $pdf->ezPlaceData($xleft,$xtop,$rs_main2["docnum"],9,"left");
	                $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main2["vat_dsc"],9,"left");
	                $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main2["expense_dsc"],9,"left");
	                $pdf->ezPlaceData($xleft+=80,$xtop,$rs_main2["remarks"],9,"left");
	                $pdf->ezPlaceData(768,$xtop,number_format($rs_main2["trntot"],"2"),9,"right");
	            }

            $xtop -= 15;

            if($xtop <= 60)
            {

                $pdf->ezNewPage();
                $xtop = 530;

	                if($_POST['txt_output_type'] == 'tab'){
	                    $tab_output = $rs_main2['docnum'] . "\t" .
	                    $rs_main2["vat_dsc"] . "\t".
	                    $rs_main2["expense_dsc"]. "\t" .
	                    $rs_main2["remarks"]. "\t" .
	                    $rs_main2["trntot"] . "\n";
	                echo $tab_output;
	                }else if($_POST['txt_output_type'] !='tab'){
    
                    $xheader = $pdf->openObject();
                    $pdf->saveState();

                    $pdf->ezPlaceData(25,$xtop-9,"<b>Expense Type:</b>",10 ,'left');
                    $pdf->ezPlaceData(105,$xtop-9,$rs_main['expense_dsc'],10 ,'left');
              
                    $pdf->line(25, $xtop-12, 770, $xtop-12); 

                    $pdf->setLineStyle(.5);
                    $pdf->line(25, $xtop-12, 770, $xtop-12);    
                    $xtop-=12;                 
                    $xleft =25;

	                    $pdf->ezPlaceData($xleft,$xtop-9,"<b>Doc. Num.</b>",10,'left');
	                    $pdf->ezPlaceData($xleft+=70,$xtop-9,"<b>Vat Type</b>",10,'left');
	                    $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Expense Type</b>",10,'left');
	                    $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Remarks</b>",10,'left');
	                    $pdf->ezPlaceData(768,$xtop-9,"<b>Total</b>",10,'right');
                                   
                    
                    $pdf->line(25, $xtop-23, 770, $xtop-23); 
                    $xtop-=22;                    
    
                    $xleft = 25;
    
                    $pdf->restoreState();
                    $pdf->closeObject();

                    $pdf->addObject($xheader,'add'); 
                }else{
                    $xtop-=23;
                }

                $xtop -= 12;    
          
            }

        }



	        if($_POST['txt_output_type'] == 'tab'){
	            $tab_output =  "\t\t\tSubtotal\t".$subtotal."\n";
	            echo $tab_output;
	        }else{

            $pdf->line(25, $xtop, 770, $xtop); 
            $pdf->ezPlaceData(658,$xtop-9,"<b>Subtotal:</b>",9 ,'left');
            $pdf->ezPlaceData(768,$xtop-9,"<b>".number_format($subtotal,2)."</b>",9 ,'right');
        }

        $xtop-=20;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;            
        }

    }


	    if($_POST['txt_output_type'] == 'tab'){
	        $tab_output =  "\t\t\tGrand Total\t".$grand_total."\n";
	        echo $tab_output;
	    }else{

        $pdf->line(25, $xtop-10, 770, $xtop-10); 
        $pdf->ezPlaceData(645,$xtop-18,"<b>Grand total:</b>",9 ,'left');
        $pdf->ezPlaceData(768,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
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
