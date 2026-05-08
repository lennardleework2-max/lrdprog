<?php
    //var_dump($_POST);

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL); 

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
		$pdf = new Cezpdf('Letter','landscape');
		$pdf ->selectFont("ezpdfclass_new/fonts/Helvetica.afm");
	}

	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
	

    $filter_array = [];
    
	$xtop = 580;
    $xleft = 25;

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

        $progname_hidden ='';

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Stock Card</b>", 13, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;
 
        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        // $xtop   -= 15;

        $select_db_item = "SELECT * FROM itemfile WHERE itmcde=?";
        $stmt_item	= $link->prepare($select_db_item);
        $stmt_item->execute(array($_POST['item']));
        $rs_item = $stmt_item->fetch();

        if(!empty($rs_item)){
            $item_display = $rs_item["itmdsc"];
        }else{
            $item_display = '';
        }

        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        // $xtop   -= 20; 

        // $pdf->ezPlaceData($xleft,$xtop,"<b>Item:</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=30,$xtop,$item_display,10,'left');
        // $pdf->ezPlaceData($xleft+=475,$xtop,"<b>Beginning Balance:</b>",10,'left');
        // $pdf->ezPlaceData($xleft+=130,$xtop,number_format($rs_balance["xsum"]),10,'right');
        // $xtop-=20;
        // $xleft = 25;

		// $pdf->setLineStyle(.5);
		// $pdf->line($xleft, $xtop+10, 770, $xtop+10);
        // $pdf->line($xleft, $xtop-3, 770, $xtop-3);
        

        $xfields_heaeder_counter = 0;


        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xfilter2 = '';
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


    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){

        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    }
    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter2 .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }
    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }
    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
    }

    //$xfilter2 = '';

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter. "ORDER BY itmdsc";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    while($rs_main = $stmt_main->fetch()){

        $xfilter_balance = '';

        if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
             $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
             $xfilter_balance .= " AND tranfile1.trndte<'".$_POST['date_from']."'";
        }
 
        if(isset($_POST['item']) && !empty($_POST['item'])){
             $xfilter_balance .= " AND tranfile2.itmcde='".$_POST['item']."'";
        }

        $select_db_balance = "SELECT SUM(stkqty) as xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum  WHERE true ".$xfilter_balance." AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
        $stmt_balance	= $link->prepare($select_db_balance);
        $stmt_balance->execute(array($_POST['item']));
        $rs_balance = $stmt_balance->fetch();

        if(empty($_POST['date_from'])){
            $rs_balance['xsum'] = 0;
        }
         
        // $xfilter2.= " AND tranfile2.itmcde='".$rs_main["itmcde"]."'";

        // Count transactions for this item to determine if it should be included
        $select_db_count = "SELECT COUNT(*) as txn_count FROM tranfile2
                            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
                            WHERE true ".$xfilter2." AND tranfile2.itmcde='".$rs_main['itmcde']."'";
        $stmt_count = $link->prepare($select_db_count);
        $stmt_count->execute();
        $rs_count = $stmt_count->fetch();
        $has_transactions = ($rs_count && $rs_count['txn_count'] > 0);

        // Only include items that have transactions
        if(!$has_transactions){
            continue;
        }

        $select_db2 = "SELECT tranfile1.trndte as tranfile1_trndte,
                              tranfile1.trncde as  tranfile1_trncde,
                              tranfile2.docnum as tranfile2_docnum,
                              tranfile2.untprc as tranfile2_untprc,
                              tranfile1.ordernum as tranfile1_ordernum,
                              customerfile.cusdsc as customerfile_cusdsc,
                              supplierfile.suppdsc as supplierfile_suppdsc,
                              tranfile1.orderby as tranfile1_orderby,
                              tranfile2.stkqty as tranfile2_stkqty,
                              mf_buyers.buyer_name as buyer_name
                              FROM tranfile2 LEFT JOIN tranfile1 ON
                               tranfile2.docnum = tranfile1.docnum
                               LEFT JOIN itemfile ON itemfile.itmcde =  tranfile2.itmcde
                               LEFT JOIN supplierfile ON tranfile1.suppcde = supplierfile.suppcde
                               LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
                               LEFT JOIN mf_buyers ON mf_buyers.buyer_id = tranfile1.buyer_id
                               WHERE true ".$xfilter2." AND tranfile2.itmcde='".$rs_main['itmcde']."' ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute();
        $in_total = 0;
        $out_total = 0;
        while($rs_main2 = $stmt_main2->fetch()){

            if($rs_main2["tranfile2_stkqty"] > 0){
                $in_total+=$rs_main2["tranfile2_stkqty"];
            }else{
                $out_total+=($rs_main2["tranfile2_stkqty"] * -1);
            }
        }

        $balance = ($rs_balance[0] + $in_total) - $out_total;
        $nocomma_balance = str_replace(",", "", $balance);
        $filter_array[] = [
            'itmcde' => $rs_main['itmcde'],
            'itmdsc' => $rs_main['itmdsc'],
            'balance' => $nocomma_balance
        ];
    }

    if($_POST['sort_filter'] == 'ASC'){
        usort($filter_array, function($a, $b) {
            // First, sort by balance (ascending)
            $balanceCompare = $a['balance'] <=> $b['balance'];
            if ($balanceCompare !== 0) {
                return $balanceCompare;
            }
        
            // If balances are equal, sort by itmdsc (alphabetically)
            return strcmp($a['itmdsc'], $b['itmdsc']);
        });
    }else{
        usort($filter_array, function($a, $b) {
            // First, sort by balance (descending)
            $balanceCompare = $b['balance'] <=> $a['balance'];
            if ($balanceCompare !== 0) {
                return $balanceCompare;
            }
        
            // If balances are equal, sort alphabetically by itmdsc (ascending)
            return strcmp($a['itmdsc'], $b['itmdsc']);
        });
    }

    foreach($filter_array as $item){
        //echo $item['itmcde'] . "<br>";

        $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter. " AND itmcde='".$item['itmcde']."'";
        $stmt_main	= $link->prepare($select_db);
        $stmt_main->execute();
        while($rs_main = $stmt_main->fetch()){
    
            $xfilter_balance = '';
    
            if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
                 $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
                 $xfilter_balance .= " AND tranfile1.trndte<'".$_POST['date_from']."'";
             }
     
             if(isset($_POST['item']) && !empty($_POST['item'])){
                 $xfilter_balance .= " AND tranfile2.itmcde='".$_POST['item']."'";
             }
    
            //  $xfilter_balance .= " AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
     
             $select_db_balance = "SELECT SUM(stkqty) as  xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum  WHERE true ".$xfilter_balance." AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
             $stmt_balance	= $link->prepare($select_db_balance);
             $stmt_balance->execute(array($_POST['item']));
            // $pdf->ezPlaceData(20,$xtop,$select_db_balance,2,"left");
            // $xtop-=10;
             $rs_balance = $stmt_balance->fetch();
    
            if(empty($_POST['date_from'])){
                $rs_balance['xsum'] = 0;
            }
    


            if ($_POST['txt_output_type'] =='tab')
            {
                $pdf->ezPlaceData(1,$xtop-9,"<b>Item:</b>",9 ,'left');
                $pdf->ezPlaceData(2,$xtop-9,$rs_main['itmdsc'],9 ,'left');
                $pdf->ezPlaceData(560,$xtop-9,"<b>Balance:</b>",9 ,'left');
                $pdf->ezPlaceData(626,$xtop-9,number_format($rs_balance['xsum']),9 ,'right');
            }else{
                $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",9 ,'left');
                $pdf->ezPlaceData(55,$xtop-9,$rs_main['itmdsc'],9 ,'left');
                $pdf->ezPlaceData(560,$xtop-9,"<b>Balance:</b>",9 ,'left');
                $pdf->ezPlaceData(626,$xtop-9,number_format($rs_balance['xsum']),9 ,'right');
            }


            $pdf->line(25, $xtop-14, 770, $xtop-12);

            $xtop-=14;

            // Define column positions with 5px+ gaps between columns
            $col_date = 25;      // Tran. Date
            $col_type = 85;      // Tran. Type (60px width for date)
            $col_docnum = 135;   // Tran. Num (50px width for type)
            $col_docnum_w = 75;  // Width for docnum column
            $col_ordernum = 215; // Order Num
            $col_ordernum_w = 85; // Width for ordernum column
            $col_shop = 305;     // Shop Name/Supplier
            $col_shop_w = 90;    // Width for shop column
            $col_buyer = 400;    // Ordered By
            $col_buyer_w = 95;   // Width for buyer column
            $col_in = 575;       // In
            $col_out = 640;      // Out

            $pdf->ezPlaceData($col_date,$xtop-9,"<b>Tran.</b>",9 ,'left');
            $pdf->ezPlaceData($col_date,$xtop-18,"<b>Date</b>",9 ,'left');
            $pdf->ezPlaceData($col_type,$xtop-9,"<b>Tran.</b>",9,'left');
            $pdf->ezPlaceData($col_type,$xtop-18,"<b>Type</b>",9,'left');
            $pdf->ezPlaceData($col_docnum,$xtop-9,"<b>Tran.</b>",9,'left');
            $pdf->ezPlaceData($col_docnum,$xtop-18,"<b>Num.</b>",9,'left');
            $pdf->ezPlaceData($col_ordernum,$xtop-9,"<b>Order</b>",9 ,'left');
            $pdf->ezPlaceData($col_ordernum,$xtop-18,"<b>Num.</b>",9 ,'left');
            $pdf->ezPlaceData($col_shop,$xtop-9,"<b>Shop Name/</b>",9 ,'left');
            $pdf->ezPlaceData($col_shop,$xtop-18,"<b>Supplier</b>",9 ,'left');
            $pdf->ezPlaceData($col_buyer,$xtop-9,"<b>Ordered</b>",9 ,'left');
            $pdf->ezPlaceData($col_buyer,$xtop-18,"<b>By</b>",9 ,'left');
            $pdf->ezPlaceData($col_in,$xtop-14,"<b>In</b>",9 ,'right');
            $pdf->ezPlaceData($col_out,$xtop-14,"<b>Out</b>",9 ,'right');
            $pdf->line(25, $xtop-22, 770, $xtop-22);
            $xtop-=35;
    
            // $xfilter2.= " AND tranfile2.itmcde='".$rs_main["itmcde"]."'";
    
            $select_db2 = "SELECT tranfile1.trndte as tranfile1_trndte,
                                  tranfile1.trncde as  tranfile1_trncde,
                                  tranfile2.docnum as tranfile2_docnum,
                                  tranfile2.untprc as tranfile2_untprc,
                                  tranfile1.ordernum as tranfile1_ordernum,
                                  customerfile.cusdsc as customerfile_cusdsc,
                                  supplierfile.suppdsc as supplierfile_suppdsc,
                                  tranfile1.orderby as tranfile1_orderby, 
                                  tranfile2.stkqty as tranfile2_stkqty,
                                  mf_buyers.buyer_name as buyer_name
                                   FROM tranfile2 
                                   LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum 
                                   LEFT JOIN itemfile ON itemfile.itmcde =  tranfile2.itmcde 
                                   LEFT JOIN supplierfile ON tranfile1.suppcde = supplierfile.suppcde 
                                   LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde 
                                   LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
                                   WHERE true ".$xfilter2." AND tranfile2.itmcde='".$rs_main['itmcde']."' ORDER BY tranfile1.trndte ASC, tranfile2.recid ASC";
            $stmt_main2	= $link->prepare($select_db2);
            $stmt_main2->execute();
            $grand_total = 0;
            $in_total = 0;
            $out_total = 0;
            // $pdf->ezPlaceData(20,$xtop,$select_db2,2,"left");
            // $xtop-=10;
            while($rs_main2 = $stmt_main2->fetch()){

                $supp_or_cus =  "";

                if(isset($rs_main2["supplierfile_suppdsc"]) && !empty($rs_main2["supplierfile_suppdsc"])){
                    $supp_or_cus = $rs_main2["supplierfile_suppdsc"];
                }else{
                    $supp_or_cus = $rs_main2["customerfile_cusdsc"];
                }

                if(!empty($rs_main2["tranfile1_trndte"]) && $rs_main2["tranfile1_trndte"] !== NULL &&  $rs_main2["tranfile1_trndte"]!=="1970-01-01"){
                    $rs_main2["tranfile1_trndte"] = date("m-d-Y",strtotime($rs_main2["tranfile1_trndte"]));
                    $rs_main2["tranfile1_trndte"] = str_replace('-','/',$rs_main2["tranfile1_trndte"]);
                }else{
                    $rs_main2["tranfile1_trndte"] = NULL;
                }

                // For XLS (tab) export: keep raw values without wrapping
                if (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
                {
                    // XLS: Keep original values unmodified
                    $rs_main2["tranfile1_ordernum"] = $rs_main2["tranfile1_ordernum"];
                    $supp_or_cus = $supp_or_cus;
                    $rs_main2["buyer_name"] = $rs_main2["buyer_name"];

                    $pdf->ezPlaceData($col_date,$xtop,$rs_main2["tranfile1_trndte"],9,"left");
                    $pdf->ezPlaceData($col_type,$xtop,$rs_main2["tranfile1_trncde"],9,"left");
                    $pdf->ezPlaceData($col_docnum,$xtop,$rs_main2["tranfile2_docnum"],9,"left");
                    $pdf->ezPlaceData($col_ordernum,$xtop,$rs_main2["tranfile1_ordernum"],9,"left");
                    $pdf->ezPlaceData($col_shop,$xtop,$supp_or_cus,9,"left");
                    if(empty($rs_main2["buyer_name"])){
                        $rs_main2["buyer_name"] = " ";
                    }
                    $pdf->ezPlaceData($col_buyer,$xtop,$rs_main2["buyer_name"],9,"left");

                    if($rs_main2["tranfile2_stkqty"] > 0){
                        $pdf->ezPlaceData($col_in,$xtop,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                        $pdf->ezPlaceData($col_out,$xtop," ",9,"right");
                        $in_total+=$rs_main2["tranfile2_stkqty"];
                    }else{
                        $rs_main2["tranfile2_stkqty"] = $rs_main2["tranfile2_stkqty"] * - 1;
                        $pdf->ezPlaceData($col_in,$xtop," ",9,"right");
                        $pdf->ezPlaceData($col_out,$xtop,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                        $out_total+=$rs_main2["tranfile2_stkqty"];
                    }

                    $xtop -= 15;

                }else{
                    // PDF: Apply wrapping for long text fields
                    $docnum_lines = wrap_str_pdf($rs_main2["tranfile2_docnum"], $col_docnum_w, 9);
                    $ordernum_lines = wrap_str_pdf($rs_main2["tranfile1_ordernum"], $col_ordernum_w, 9);
                    $shop_lines = wrap_str_pdf($supp_or_cus, $col_shop_w, 9);
                    $buyer_text = empty($rs_main2["buyer_name"]) ? " " : $rs_main2["buyer_name"];
                    $buyer_lines = wrap_str_pdf($buyer_text, $col_buyer_w, 9);

                    // Calculate max lines needed for this row
                    $max_lines = max(
                        count($docnum_lines),
                        count($ordernum_lines),
                        count($shop_lines),
                        count($buyer_lines)
                    );
                    $row_height = 12 + ($max_lines - 1) * 10;

                    // Check if we need a new page before rendering
                    if(($xtop - $row_height - 5) <= 60){
                        $pdf->ezNewPage();
                        $xtop = 483;
                    }

                    // Output each field at the row's top Y position
                    $row_y = $xtop;

                    // Date and Type (single line fields)
                    $pdf->ezPlaceData($col_date,$row_y,$rs_main2["tranfile1_trndte"],9,"left");
                    $pdf->ezPlaceData($col_type,$row_y,$rs_main2["tranfile1_trncde"],9,"left");

                    // Docnum (wrapped)
                    foreach($docnum_lines as $li => $line_text){
                        $pdf->ezPlaceData($col_docnum, $row_y - ($li * 10), $line_text, 9, "left");
                    }

                    // Order num (wrapped)
                    foreach($ordernum_lines as $li => $line_text){
                        $pdf->ezPlaceData($col_ordernum, $row_y - ($li * 10), $line_text, 9, "left");
                    }

                    // Shop/Supplier (wrapped)
                    foreach($shop_lines as $li => $line_text){
                        $pdf->ezPlaceData($col_shop, $row_y - ($li * 10), $line_text, 9, "left");
                    }

                    // Buyer (wrapped)
                    foreach($buyer_lines as $li => $line_text){
                        $pdf->ezPlaceData($col_buyer, $row_y - ($li * 10), $line_text, 9, "left");
                    }

                    // In/Out quantities
                    if($rs_main2["tranfile2_stkqty"] > 0){
                        $pdf->ezPlaceData($col_in,$row_y,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                        $in_total+=$rs_main2["tranfile2_stkqty"];
                    }else{
                        $rs_main2["tranfile2_stkqty"] = $rs_main2["tranfile2_stkqty"] * - 1;
                        $pdf->ezPlaceData($col_out,$row_y,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                        $out_total+=$rs_main2["tranfile2_stkqty"];
                    }

                    $xtop -= $row_height;
                }

                if($xtop <= 60)
                {
                    $pdf->ezNewPage();
                    $xtop = 483;
                }
            }


            
            $pdf->line(25, $xtop, 770, $xtop);

            // Total row with proper spacing from data
            $xtop -= 5;

            if($_POST['txt_output_type'] =='tab'){
                $pdf->ezPlaceData(6,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(7,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(8,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(9,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(10,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData($col_buyer + 50,$xtop-9,"<b>Total:</b>",9 ,'right');
                $pdf->ezPlaceData($col_in,$xtop-9,"<b>".number_format($in_total)."</b>",9 ,'right');
                $pdf->ezPlaceData($col_out,$xtop-9,"<b>".number_format($out_total)."</b>",9 ,'right');
            }else{
                $pdf->ezPlaceData($col_buyer + 50,$xtop-9,"<b>Total:</b>",9 ,'right');
                $pdf->ezPlaceData($col_in,$xtop-9,"<b>".number_format($in_total)."</b>",9 ,'right');
                $pdf->ezPlaceData($col_out,$xtop-9,"<b>".number_format($out_total)."</b>",9 ,'right');
            }

            $xtop -= 18;

            // Balance row with proper spacing from Total
            if($_POST['txt_output_type'] =='tab'){
                $pdf->ezPlaceData(6,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(7,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(8,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(9,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData(10,$xtop-9," ",9 ,'right');
                $pdf->ezPlaceData($col_buyer + 50,$xtop-9,"<b>Balance:</b>",9,'right');
                $balance = ($rs_balance['xsum'] + $in_total) - $out_total;
                $pdf->ezPlaceData($col_out,$xtop-9,"<b>".number_format($balance)."</b>",9 ,'right');
            }else{
                $pdf->ezPlaceData($col_buyer + 50,$xtop-9,"<b>Balance:</b>",9,'right');
                $balance = ($rs_balance['xsum'] + $in_total) - $out_total;
                $pdf->ezPlaceData($col_out,$xtop-9,"<b>".number_format($balance)."</b>",9 ,'right');
            }

            $xtop -= 25;

            if($_POST['txt_output_type'] =='tab'){
                $xtop-=15;
            }


    
            if($xtop <= 70)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }
        }
    }


    

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

    /**
     * Wrap text into multiple lines for PDF output.
     * Handles long strings without spaces by forcibly breaking at max width.
     * @param string $string The text to wrap
     * @param int $max_wid Maximum pixel width for the column
     * @param int $fsize Font size
     * @return array Array of lines
     */
    function wrap_str_pdf($string, $max_wid, $fsize)
    {
        global $pdf;

        // For XLS export, return single-element array with original string
        if(get_class($pdf) == 'tab_ezpdf') {
            return array($string);
        }

        $string = trim((string)$string);
        if($string === '') {
            return array('');
        }

        $max_wid -= 5;
        if($max_wid < 10) $max_wid = 10;

        // If string fits in one line, return it
        if($pdf->getTextWidth($fsize, $string) <= $max_wid) {
            return array($string);
        }

        $wrapped_lines = array();
        $remaining = $string;

        while($remaining !== '') {
            if($pdf->getTextWidth($fsize, $remaining) <= $max_wid) {
                $wrapped_lines[] = $remaining;
                break;
            }

            // Find how much text fits in the width
            $line = '';
            $chars = str_split($remaining);
            foreach($chars as $char) {
                $test = $line . $char;
                if($pdf->getTextWidth($fsize, $test) > $max_wid) {
                    break;
                }
                $line = $test;
            }

            // If nothing fits, force at least one character
            if($line === '' && !empty($chars)) {
                $line = $chars[0];
            }

            // Try to break at space if possible (for text with spaces)
            $last_space = strrpos($line, ' ');
            if($last_space !== false && $last_space > 0) {
                $candidate = rtrim(substr($line, 0, $last_space));
                if($candidate !== '') {
                    $line = $candidate;
                }
            }

            $wrapped_lines[] = rtrim($line);
            $remaining = ltrim(substr($remaining, strlen($line)));
        }

        if(empty($wrapped_lines)) {
            $wrapped_lines[] = $string;
        }

        return $wrapped_lines;
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