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
            $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Order by Upload Date (Summarized)</b>", 15, 'left' );
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
                $pdf->ezPlaceData($xleft+=60,$xtop,$_POST['cus_search'],10,'left');

                $xtop-=15;

            // }   
        }else{

            echo "Sales Order by Upload Date (Summarized)\t\n"; // Use \t for column separation and \n for new rows
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
                
            $tab_headers = "Doc. Num.\tOrdered Date\tUpload Date\tPlatform\tShip To\tOrder By\tTotal";
            echo $tab_headers;
        }        

        
        $xleft =25;
		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Ordered Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Upload Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Platform</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
            $pdf->ezPlaceData($xleft+=150,$xtop,"<b>Order By</b>",10,'left');
            $pdf->ezPlaceData($xleft+=175,$xtop,"<b>Total</b>",10,'right');
        }

		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');

	/***header**/

    #region DO YOU LOOP HERE



    // if(isset($_POST['cus_to']) && !empty($_POST['cus_to'])){
    //     $xfilter .= " AND customerfile.cusdsc<='".$_POST['cus_to']."'";
    // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (salesorderfile1.paydate='' OR salesorderfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (salesorderfile1.paydate!='' OR salesorderfile1.paydate IS NOT NULL)";
        // }

        


    
    $select_db="SELECT salesorderfile1.file_created_date as 'ordered_date', salesorderfile1.shipto as salesorderfile1_shipto,salesorderfile1.cuscde as salesorderfile1_cuscde,salesorderfile1.docnum as salesorderfile1_docnum,
    salesorderfile1.trndte as salesorderfile1_trndte,salesorderfile1.trntot as salesorderfile1_trntot,salesorderfile1.orderby as salesorderfile1_orderby,salesorderfile1.recid as salesorderfile1_recid,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc,
    customerfile.cusdsc, customerfile.cuscde FROM salesorderfile1 LEFT JOIN customerfile ON 
    salesorderfile1.cuscde = customerfile.cuscde WHERE true ".$xfilter." ORDER BY salesorderfile1.trndte ASC, salesorderfile1.docnum ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
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
            $rs_main["customerfile_cusdsc"] = trim_str($rs_main["customerfile_cusdsc"],65,9);
            $rs_main["salesorderfile1_shipto"] = trim_str($rs_main["salesorderfile1_shipto"],120,9);
        }


        $pdf->ezPlaceData($xleft,$xtop,$rs_main["salesorderfile1_docnum"],9,"left");
        $pdf->ezPlaceData($xleft+=60,$xtop,$file_created_date,9,"left");
        $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["salesorderfile1_trndte"],9,"left");
        $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["customerfile_cusdsc"],9,"left");
        $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["salesorderfile1_shipto"],9,"left");
        $pdf->ezPlaceData($xleft+=150,$xtop,$rs_main["salesorderfile1_orderby"],9,"left");
        $pdf->ezPlaceData($xleft+175,$xtop,number_format($rs_main["salesorderfile1_trntot"],"2"),9,"right");

        
        $xleft_next = $xleft+175;
        
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
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Ordered Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Upload Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Platform</b>",10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
                $pdf->ezPlaceData($xleft+=150,$xtop,"<b>Order By</b>",10,'left');
                $pdf->ezPlaceData($xleft+=175,$xtop,"<b>Total</b>",10,'right');

                $xleft = 25;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'all'); 

                $xheader_check = true;
            }

            $xtop -= 15;
        }
        
    }

    if ($_POST['txt_output_type']=='tab')
	{
        // Include remarks in the tab-delimited output generation
        $pdf->ezPlaceData(1,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(2,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(3,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(4,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(5,$xtop-18,"",9 ,'right');
        $pdf->ezPlaceData(700,$xtop-18,"<b>Grand total:</b>",9 ,'right');
        $pdf->ezPlaceData(765,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
	}else{
        $pdf->line(25, $xtop-10, 770, $xtop-10); 
        $pdf->ezPlaceData($xleft_next-=65,$xtop-18,"<b>Grand total:</b>",9 ,'right');
        $pdf->ezPlaceData($xleft_next+=65,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
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