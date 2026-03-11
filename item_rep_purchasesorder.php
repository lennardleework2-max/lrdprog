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
            $pdf->ezPlaceData($xleft, $xtop,"<b>Purchases Order by Item</b>", 15, 'left' );
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
        $xfilter2  = '';
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
    
        $xfilter2 .= " AND purchasesorderfile1.trndte>='".$xdate_from_filter."' AND purchasesorderfile1.trndte<='".$xdate_to_filter."'";

        if(isset($_POST['item']) && !empty($_POST['item'])){
            $xfilter .= " AND itmcde='".$_POST['item']."'";
        }

		$pdf->setLineStyle(.5);
        $xleft = 25;

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
            //    (isset($_POST['item'])) && !empty($_POST['item'])){

                $select_db_filter = "SELECT * FROM itemfile WHERE itmcde='".$_POST['item']."' ";
                $stmt_main_filter	= $link->prepare($select_db_filter);
                $stmt_main_filter->execute();
                $rs_main_filter = $stmt_main_filter->fetch();


                $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
                $xtop-=15; 
                  
                $pdf->ezPlaceData($xleft,$xtop,"<b>Date From:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,$xdate_from_display,10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Date To:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=50,$xtop,$xdate_to_display,10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Item:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=35,$xtop,$rs_main_filter['itmdsc'],10,'left');

                $xtop-=15;

            // }   
        }else{

            echo "Purchases by Item\t\n"; // Use \t for column separation and \n for new rows
            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n"; // Blank line for spacing

            // if((isset($_POST['date_from']) && !empty($_POST['date_from'])) || 
            //    (isset($_POST['date_to']) && !empty($_POST['date_to'])) || 
            //    (isset($_POST['item'])) && !empty($_POST['item'])){

                $select_db_filter = "SELECT * FROM itemfile WHERE itmcde='".$_POST['item']."' ";
                $stmt_main_filter	= $link->prepare($select_db_filter);
                $stmt_main_filter->execute();
                $rs_main_filter = $stmt_main_filter->fetch();

                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
                echo "Date From: ".$xdate_from_display."\t";
                echo "Date To: ".$xdate_to_display."\t";
                echo "Item: ".$rs_main_filter['itmdsc']."\t\n";
            // }           
                
            // $tab_headers = "Doc. Num.\tOrder Num.\tTran. Date\tSupplier\tShip To\tPaydate\tPayment Details\tTotal";
            // echo $tab_headers;
        }              

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');

	/***header**/
    #region DO YOU LOOP HERE


    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute(array($_POST['item']));
    $grand_total = 0;
    $xcounter_rows = 0;
    $old_item = '';
    while($rs_main = $stmt_main->fetch()){

        $xcounter_rows++;
        $xpage_counter++;  
        
        if($_POST['txt_output_type'] == 'tab'){
            $tab_output =  "Item:\t".$rs_main['itmdsc']. "\n";
            echo $tab_output;
        }else{
            $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');
            $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],10 ,'left');
        }

        $pdf->line(25, $xtop-12, 770, $xtop-12); 

        $xtop-=12;
        $xleft = 25;

        if($_POST['txt_output_type'] == 'tab'){
            $pdf->ezPlaceData($xleft,$xtop-9,"",9 ,'left');

            $tab_headers = "Tran. Date\tOrder Num.\tSupplier\tOrdered By\tUnit Price\tQuantity\tExtended Price\n";
            echo $tab_headers;
        }else{
            $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
            $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Order Num.</b>",9 ,'left');
            $pdf->ezPlaceData($xleft+=115,$xtop-9,"<b>Supplier</b>",9 ,'left');
            $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Ordered By</b>",9 ,'left');
            $pdf->ezPlaceData($xleft+=180,$xtop-9,"<b>Unit Price</b>",9 ,'left');
            $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Quantity</b>",9 ,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Extended Price</b>",9 ,'left');
        }


        $pdf->line(25, $xtop-12, 770, $xtop-12); 
        $xtop-=23;

        $select_db2 = "SELECT * FROM purchasesorderfile2 LEFT JOIN purchasesorderfile1 ON 
                        purchasesorderfile2.docnum = purchasesorderfile1.docnum
                        WHERE itmcde='".$rs_main['itmcde']."' ".$xfilter2." ORDER BY purchasesorderfile1.trndte ASC, purchasesorderfile2.recid ASC";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $subtotal = 0;
        $subtotal_itmqty = 0;
        $subtotal_weighted = 0;

        while($rs_main2 = $stmt_main2->fetch()){   

            $select_db3 = "SELECT * FROM purchasesorderfile1 LEFT JOIN supplierfile ON purchasesorderfile1.suppcde = supplierfile.suppcde WHERE purchasesorderfile1.docnum='".$rs_main2['docnum']."'";
            $stmt_main3	= $link->prepare($select_db3);
            $stmt_main3->execute();
            $rs_main3 = $stmt_main3->fetch();
         
            $xleft = 25;
            $subtotal+=$rs_main2["extprc"];
            $grand_total+=$rs_main2["extprc"];
            $subtotal_itmqty+=$rs_main2["itmqty"];

            // $grand_total += $rs_main2["purchasesorderfile1_trntot"];

            if(isset($rs_main3["trndte"]) && !empty($rs_main3["trndte"])){
                $rs_main3["trndte"] = date("m-d-Y",strtotime($rs_main3["trndte"]));
                $rs_main3["trndte"] = str_replace('-','/',$rs_main3["trndte"]);
            }

            if($_POST['txt_output_type'] == 'tab'){
                    $tab_output = $rs_main3['trndte'] . "\t" .
                    $rs_main3['ordernum'] . "\t" .
                    $rs_main3["suppdsc"] . "\t" .
                    $rs_main3["orderby"] . "\t".
                    $rs_main2["untprc"]. "\t" .
                    $rs_main2["itmqty"]. "\t" .
                    $rs_main2["extprc"] . "\n";
                echo $tab_output;
            }else{
                $pdf->ezPlaceData($xleft,$xtop, $rs_main3['trndte'],9,"left");
                $pdf->ezPlaceData($xleft+=80,$xtop, trim_str($rs_main3['ordernum'],100,9),9,"left");
                $pdf->ezPlaceData($xleft+=115,$xtop,trim_str($rs_main3["suppdsc"],65,9),9,"left");
                $pdf->ezPlaceData($xleft+=85,$xtop,trim_str($rs_main3["orderby"],140,9),9,"left");
                //$pdf->ezPlaceData($xleft+=90,$xtop,$rs_main2["purchasesorderfile1_trndte"],9,"left");
                $pdf->ezPlaceData($xleft+=220,$xtop,number_format($rs_main2["untprc"],2),9,"right");
                $pdf->ezPlaceData($xleft+=80,$xtop,number_format($rs_main2["itmqty"]),9,"right");
                $pdf->ezPlaceData($xleft+=90,$xtop,number_format($rs_main2["extprc"],2),9,"right");
            }


            $xtop -= 15;

            if($xtop <= 60)
            {

                $pdf->ezNewPage();
                $xtop = 530;

                if($_POST['txt_output_type'] == 'tab'){
                    $tab_output = $rs_main3['trndte'] . "\t" .
                    $rs_main3['ordernum'] . "\t" .
                    $rs_main3["suppdsc"] . "\t" .
                    $rs_main3["orderby"] . "\t".
                    $rs_main2["untprc"]. "\t" .
                    $rs_main2["itmqty"]. "\t" .
                    $rs_main2["extprc"] . "\n";
                    echo $tab_output;
                }else if($_POST['txt_output_type'] !='tab'){
    
                    $xheader = $pdf->openObject();
                    $pdf->saveState();

                    $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');
                    $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],10 ,'left');
              
            
                    $pdf->line(25, $xtop-12, 770, $xtop-12); 

                    $pdf->setLineStyle(.5);
                    $pdf->line(25, $xtop-12, 770, $xtop-12);    
                    $xtop-=12;                 
                    $xleft =25;


                    $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
                    $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Order Num.</b>",9 ,'left');
                    $pdf->ezPlaceData($xleft+=115,$xtop-9,"<b>Supplier</b>",9 ,'left');
                    $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Ordered By</b>",9 ,'left');
                    $pdf->ezPlaceData($xleft+=180,$xtop-9,"<b>Unit Price</b>",9 ,'left');
                    $pdf->ezPlaceData($xleft+=85,$xtop-9,"<b>Quantity</b>",9 ,'left');
                    $pdf->ezPlaceData($xleft+=60,$xtop-9,"<b>Extended Price</b>",9 ,'left');
                                   
                    
                    $pdf->line(25, $xtop-12, 770, $xtop-12); 
                    $xtop-=12;                    
    
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

        if($subtotal_itmqty != 0){
            $subtotal_weighted = $subtotal/$subtotal_itmqty;
        }

        if($_POST['txt_output_type'] == 'tab'){
            $tab_output =  "\t\t\tWeighted Average/Subtotal\t".$subtotal_weighted."\t".$subtotal_itmqty."\t".$subtotal."\n";
            echo $tab_output;
        }else{

            $pdf->line(25, $xtop, 770, $xtop); 
            $pdf->ezPlaceData(330,$xtop-9,"<b>Weighted Average/Subtotal:</b>",9 ,'left');
            $pdf->ezPlaceData(525,$xtop-9,number_format($subtotal_weighted,2),9 ,'right');
            $pdf->ezPlaceData(605,$xtop-9,$subtotal_itmqty,9 ,'right');
            $pdf->ezPlaceData(695,$xtop-9,"<b>".number_format($subtotal,2)."</b>",9 ,'right');
        }


        $xtop-=20;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;            
        }

        $old_item = $rs_main['itmcde'];


    }


    if($_POST['txt_output_type'] == 'tab'){
        $tab_output =  "\t\t\t\t\tGrand Total\t".$grand_total."\n";
        echo $tab_output;
    }else{

        $pdf->line(25, $xtop-10, 770, $xtop-10); 
        $pdf->ezPlaceData(580,$xtop-18,"<b>Grand total:</b>",9 ,'left');
        $pdf->ezPlaceData(695,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
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