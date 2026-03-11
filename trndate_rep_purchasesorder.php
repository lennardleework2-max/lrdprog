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
            $pdf->ezPlaceData($xleft, $xtop,"<b>Purchases Order by Tran. Date (Summarized)</b>", 15, 'left' );
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
    
        $xfilter .= " AND purchasesorderfile1.trndte>='".$xdate_from_filter."' AND purchasesorderfile1.trndte<='".$xdate_to_filter."'";
    
    
        if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
            $xfilter .= " AND supplierfile.suppdsc='".$_POST['cus_search']."'";
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
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Supplier:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=55,$xtop,$_POST['cus_search'],10,'left');

                $xtop-=15;

            // }   
        }else{

            echo "Purchases Order by Tran. Date (Summarized)\t\n"; // Use \t for column separation and \n for new rows
            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n"; // Blank line for spacing

            // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
            //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
            //    (isset($_POST['cus_search'])) && !empty($_POST['cus_search'])){
                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
                echo "Date From: ".$xdate_from_display."\t";
                echo "Date To: ".$xdate_to_display."\t";
                echo "Supplier: ".$_POST['cus_search']."\t\n";
            // }           
                
            $tab_headers = "Doc. Num.\tOrder Num.\tTran. Date\tSupplier\tShip To\tPaydate\tPayment Details\tTotal";
            echo $tab_headers;
        }        



        $xleft =25;
		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);

        $xfields_heaeder_counter = 0;

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Order Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Tran. Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Supplier</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
            $pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
            $pdf->ezPlaceData($xleft+=85,$xtop,"<b>Payment Details</b>",10,'left');
            $pdf->ezPlaceData($xleft+=194,$xtop,"<b>Total</b>",10,'right'); 
        }

		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');

	/***header**/

    #region DO YOU LOOP HERE


    
    $select_db="SELECT purchasesorderfile1.shipto as purchasesorderfile1_shipto,purchasesorderfile1.docnum as purchasesorderfile1_docnum,
    purchasesorderfile1.trndte as purchasesorderfile1_trndte,purchasesorderfile1.trntot as purchasesorderfile1_trntot,purchasesorderfile1.orderby as purchasesorderfile1_orderby,purchasesorderfile1.recid as purchasesorderfile1_recid,purchasesorderfile1.ordernum as purchasesorderfile1_ordernum,
    supplierfile.suppdsc as supplierfile_suppdsc, purchasesorderfile1.paydate as purchasesorderfile1_paydate, purchasesorderfile1.paydetails as purchasesorderfile1_paydetails
    FROM purchasesorderfile1 LEFT JOIN supplierfile ON 
    purchasesorderfile1.suppcde = supplierfile.suppcde WHERE true ".$xfilter." ORDER BY purchasesorderfile1.docnum ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
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
            $rs_main["supplierfile_suppdsc"] = trim_str($rs_main["supplierfile_suppdsc"],65,9);
            $rs_main["purchasesorderfile1_shipto"] = trim_str($rs_main["purchasesorderfile1_shipto"],120,9);
            $rs_main["purchasesorderfile1_paydetails"] = trim_str($rs_main["purchasesorderfile1_paydetails"],140,9);
            $rs_main["purchasesorderfile1_ordernum"] = trim_str($rs_main["purchasesorderfile1_ordernum"],100,9);
        }



        $pdf->ezPlaceData($xleft,$xtop,$rs_main["purchasesorderfile1_docnum"],9,"left");
        $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["purchasesorderfile1_ordernum"],9,"left");
        $pdf->ezPlaceData($xleft+=110,$xtop,$rs_main["purchasesorderfile1_trndte"],9,"left");
        $pdf->ezPlaceData($xleft+=80,$xtop,$rs_main["supplierfile_suppdsc"],9,"left");
        $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["purchasesorderfile1_shipto"],9,"left");
        $pdf->ezPlaceData($xleft+=135,$xtop,$rs_main["purchasesorderfile1_paydate"],9,"left");
        $pdf->ezPlaceData($xleft+=85,$xtop,$rs_main["purchasesorderfile1_paydetails"],9,"left");
        $pdf->ezPlaceData($xleft+194,$xtop,number_format($rs_main["purchasesorderfile1_trntot"],"2"),9,"right");

        
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
    
                
                $xfields_heaeder_counter = 0;

                $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Order Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=110,$xtop,"<b>Tran. Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,"<b>Supplier</b>",10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
                $pdf->ezPlaceData($xleft+=135,$xtop,"<b>Paydate</b>",10,'left');
                $pdf->ezPlaceData($xleft+=85,$xtop,"<b>Payment Details</b>",10,'left');
                $pdf->ezPlaceData($xleft+=194,$xtop,"<b>Total</b>",10,'right');

                $xleft = 25;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'all'); 

                $xheader_check = true;
            }
            $xtop -= 15;  
        }
        
    }

    
    if($_POST['txt_output_type'] == 'tab'){
        // Include remarks in the tab-delimited output generation
        $pdf->ezPlaceData(1,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(2,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(3,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(4,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(5,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(6,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(700,$xtop-18,"<b>Grand total:</b>",9 ,'right');
        $pdf->ezPlaceData(765,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
    }else{
        $pdf->line(25, $xtop-10, 770, $xtop-10); 
        $pdf->ezPlaceData(700,$xtop-18,"<b>Grand total:</b>",9 ,'right');
        $pdf->ezPlaceData(765,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
    
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