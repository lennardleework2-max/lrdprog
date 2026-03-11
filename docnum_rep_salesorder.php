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
                $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Order by Doc. Num (Summarized)</b>", 15, 'left' );
                $xtop   -= 15;
                $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']."</b>", 9, 'left' );
                $xtop   -= 15;
                $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
                $xtop   -= 20;
            }

        $pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');       
        
        
        $xheader_first_page = $pdf->openObject();
        $pdf->saveState();

        if($_POST['txt_output_type'] != 'tab'){
            if((isset($_POST['doc_from']) && !empty($_POST['doc_from'])) || (isset($_POST['doc_to']) && !empty($_POST['doc_to']))){
                $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
                $xtop-=15; 
                  
                $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num. From:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=90,$xtop,$_POST['doc_from'],10,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Doc. Num. To:</b>",10,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop,$_POST['doc_to'],10,'left');
                $xtop-=15;
            }
              
        }else{

            echo "Purchases Order by Doc. Num (Summarized)\t\n"; // Use \t for column separation and \n for new rows
            echo "Pdf Report by: " . $_SESSION['userdesc'] . "\t\n";
            echo "Date Printed : " . $date_printed . "\t\n";
            echo "\n"; // Blank line for spacing

            if((isset($_POST['doc_from']) && !empty($_POST['doc_from'])) || (isset($_POST['doc_to']) && !empty($_POST['doc_to']))){
                echo "FILTER:\n"; // Use \t for column separation and \n for new rows
                echo "Doc. Num. From: ".$_POST['doc_from']."\t";
                echo "Doc. Num. To: ".$_POST['doc_to']."\t\n";
            }          
                
            $tab_headers = "Doc. Num.\tUpload Date\tPlatform\tShip To\tOrder By\tTotal\t";
            echo $tab_headers;
        }


        $xleft =25;
		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 770, $xtop+10);
        $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        
        $xfields_heaeder_counter = 0;

        if($_POST['txt_output_type'] !='tab'){
            $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
            $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Upload Date</b>",10,'left');
            $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Platform</b>",10,'left');
            $pdf->ezPlaceData($xleft+=75,$xtop,"<b>Ship To</b>",10,'left');
            $pdf->ezPlaceData($xleft+=150,$xtop,"<b>Order By</b>",10,'left');
            $pdf->ezPlaceData($xleft+=175,$xtop,"<b>Total</b>",10,'right');
        }
       

        $xtop-=15;
        // Close the object
        $pdf->restoreState();
        $pdf->closeObject();        

        // Add the object to only the first page
		$pdf->addObject($xheader_first_page,'add');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';




        if((isset($_POST['doc_from']) && !empty($_POST['doc_from'])) &&
        (isset($_POST['doc_to']) && !empty($_POST['doc_to'])) ){

            $xfilter .= " AND salesorderfile1.docnum>='".$_POST['doc_from']."' AND salesorderfile1.docnum<='".$_POST['doc_to']."'";
        }

        else if(isset($_POST['doc_from']) && !empty($_POST['doc_from'])){
            $xfilter .= " AND salesorderfile1.docnum>='".$_POST['doc_from']."'";
        }

        else if(isset($_POST['doc_to']) && !empty($_POST['doc_to'])){

            $xfilter .= " AND salesorderfile1.docnum<='".$_POST['doc_to']."'";
        }





    $select_db="SELECT salesorderfile1.shipto as salesorderfile1_shipto,salesorderfile1.cuscde as salesorderfile1_cuscde,salesorderfile1.docnum as salesorderfile1_docnum,
    salesorderfile1.trndte as salesorderfile1_trndte,salesorderfile1.trntot as salesorderfile1_trntot,salesorderfile1.orderby as salesorderfile1_orderby,salesorderfile1.recid as salesorderfile1_recid,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc,
    customerfile.cusdsc, customerfile.cuscde FROM salesorderfile1 LEFT JOIN customerfile ON 
    salesorderfile1.cuscde = customerfile.cuscde WHERE true ".$xfilter." ORDER BY salesorderfile1.docnum ASC";

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    while($rs_main = $stmt_main->fetch()){    

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
        $pdf->ezPlaceData($xleft+=60,$xtop,$rs_main["salesorderfile1_trndte"],9,"left");
        $pdf->ezPlaceData($xleft+=70,$xtop,$rs_main["customerfile_cusdsc"],9,"left");
        $pdf->ezPlaceData($xleft+=75,$xtop,$rs_main["salesorderfile1_shipto"],9,"left");
        $pdf->ezPlaceData($xleft+=150,$xtop,$rs_main["salesorderfile1_orderby"],9,"left");
        $pdf->ezPlaceData($xleft+175,$xtop,number_format($rs_main["salesorderfile1_trntot"],"2"),9,"right");

        $xleft_next = $xleft+175;
        
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
                $pdf->line($xleft, $xtop-3, 770, $xtop-3);

                $pdf->ezPlaceData($xleft,$xtop,"<b>Doc. Num.</b>",10,'left');
                $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Upload Date</b>",10,'left');
                $pdf->ezPlaceData($xleft+=70,$xtop,"<b>Platform</b>",10,'left');
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