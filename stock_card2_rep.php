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

    $select_db = "SELECT itmcde, itmdsc FROM itemfile WHERE true ".$xfilter." ORDER BY itmdsc";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $item_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);
    $report_items_by_code = array();

    if(!empty($item_rows)){
        $item_codes = array();
        foreach($item_rows as $item_row){
            $item_codes[] = $item_row['itmcde'];
        }

        $opening_balances = array();
        if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
            $balance_placeholders = implode(',', array_fill(0, count($item_codes), '?'));
            $select_db_balance = "SELECT tranfile2.itmcde, SUM(tranfile2.stkqty) AS xsum
                FROM tranfile2
                LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
                WHERE tranfile1.trndte < ?
                  AND tranfile2.itmcde IN (".$balance_placeholders.")
                GROUP BY tranfile2.itmcde";
            $stmt_balance = $link->prepare($select_db_balance);
            $stmt_balance->execute(array_merge(array($_POST['date_from']), $item_codes));
            while($rs_balance = $stmt_balance->fetch(PDO::FETCH_ASSOC)){
                $opening_balances[$rs_balance['itmcde']] = (float)$rs_balance['xsum'];
            }
        }

        $txn_placeholders = implode(',', array_fill(0, count($item_codes), '?'));
        $select_db2 = "SELECT tranfile2.itmcde AS item_code,
                tranfile1.trndte as tranfile1_trndte,
                tranfile1.trncde as tranfile1_trncde,
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
            LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde
            LEFT JOIN supplierfile ON tranfile1.suppcde = supplierfile.suppcde
            LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
            LEFT JOIN mf_buyers ON mf_buyers.buyer_id = tranfile1.buyer_id
            WHERE tranfile2.itmcde IN (".$txn_placeholders.") ".$xfilter2."
            ORDER BY tranfile2.itmcde ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_main2 = $link->prepare($select_db2);
        $stmt_main2->execute($item_codes);

        $transactions_by_item = array();
        $in_totals = array();
        $out_totals = array();
        while($rs_main2 = $stmt_main2->fetch(PDO::FETCH_ASSOC)){
            $supp_or_cus = "";
            if(isset($rs_main2["supplierfile_suppdsc"]) && !empty($rs_main2["supplierfile_suppdsc"])){
                $supp_or_cus = $rs_main2["supplierfile_suppdsc"];
            }else{
                $supp_or_cus = $rs_main2["customerfile_cusdsc"];
            }

            if(!empty($rs_main2["tranfile1_trndte"]) && $rs_main2["tranfile1_trndte"] !== NULL && $rs_main2["tranfile1_trndte"] !== "1970-01-01"){
                $rs_main2["tranfile1_trndte"] = date("m-d-Y",strtotime($rs_main2["tranfile1_trndte"]));
                $rs_main2["tranfile1_trndte"] = str_replace('-','/',$rs_main2["tranfile1_trndte"]);
            }else{
                $rs_main2["tranfile1_trndte"] = NULL;
            }

            $item_code = $rs_main2['item_code'];
            $qty_value = (float)$rs_main2["tranfile2_stkqty"];
            if(!isset($transactions_by_item[$item_code])){
                $transactions_by_item[$item_code] = array();
                $in_totals[$item_code] = 0;
                $out_totals[$item_code] = 0;
            }

            if($qty_value > 0){
                $in_totals[$item_code] += $qty_value;
            }else{
                $out_totals[$item_code] += ($qty_value * -1);
            }

            $transactions_by_item[$item_code][] = array(
                'tranfile1_trndte' => $rs_main2["tranfile1_trndte"],
                'tranfile1_trncde' => (string)$rs_main2["tranfile1_trncde"],
                'tranfile2_docnum' => (string)$rs_main2["tranfile2_docnum"],
                'tranfile2_untprc' => (float)$rs_main2["tranfile2_untprc"],
                'tranfile1_ordernum' => (string)$rs_main2["tranfile1_ordernum"],
                'supp_or_cus' => (string)$supp_or_cus,
                'buyer_name' => (string)$rs_main2["buyer_name"],
                'tranfile2_stkqty' => $qty_value
            );
        }

        foreach($item_rows as $item_row){
            $item_code = $item_row['itmcde'];
            if(empty($transactions_by_item[$item_code])){
                continue;
            }

            $opening_balance = (isset($_POST['date_from']) && !empty($_POST['date_from']) && isset($opening_balances[$item_code])) ? (float)$opening_balances[$item_code] : 0;
            $in_total = isset($in_totals[$item_code]) ? (float)$in_totals[$item_code] : 0;
            $out_total = isset($out_totals[$item_code]) ? (float)$out_totals[$item_code] : 0;
            $ending_balance = ($opening_balance + $in_total) - $out_total;

            $report_items_by_code[$item_code] = array(
                'itmcde' => $item_code,
                'itmdsc' => $item_row['itmdsc'],
                'balance' => $opening_balance,
                'transactions' => $transactions_by_item[$item_code],
                'in_total' => $in_total,
                'out_total' => $out_total,
                'ending_balance' => $ending_balance
            );

            $filter_array[] = array(
                'itmcde' => $item_code,
                'itmdsc' => $item_row['itmdsc'],
                'balance' => $ending_balance
            );
        }
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
        if(!isset($report_items_by_code[$item['itmcde']])){
            continue;
        }

        $report_item = $report_items_by_code[$item['itmcde']];
        $starting_balance = $report_item['balance'];
        $item_balance = $report_item['ending_balance'];

        if ($_POST['txt_output_type'] =='tab')
        {
            // XLS: Item header row - columns 1-2 for item, 7-8 for starting balance
            $pdf->ezPlaceData(1,$xtop-9,"<b>Item:</b>",9 ,'left');
            $pdf->ezPlaceData(2,$xtop-9,xls_safe_text($report_item['itmdsc']),9 ,'left');
            $pdf->ezPlaceData(3,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(4,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(5,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(6,$xtop-9,"<b>Balance:</b>",9 ,'right');
            $pdf->ezPlaceData(7,$xtop-9," ",9 ,'right');
            $pdf->ezPlaceData(8,$xtop-9,number_format($starting_balance),9 ,'right');
        }else{
            $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",9 ,'left');
            $pdf->ezPlaceData(55,$xtop-9,$report_item['itmdsc'],9 ,'left');
            $pdf->ezPlaceData(560,$xtop-9,"<b>Balance:</b>",9 ,'left');
            $pdf->ezPlaceData(626,$xtop-9,number_format($starting_balance),9 ,'right');
        }


        $pdf->line(25, $xtop-14, 770, $xtop-12);

        $xtop-=14;

        // Define column positions for PDF (pixel positions) and XLS (column indices)
        if ($_POST['txt_output_type'] =='tab') {
            // XLS: Use column indices (1-based)
            $col_date = 1;       // Tran. Date
            $col_type = 2;       // Tran. Type
            $col_docnum = 3;     // Tran. Num
            $col_ordernum = 4;   // Order Num
            $col_shop = 5;       // Shop Name/Supplier
            $col_buyer = 6;      // Ordered By
            $col_in = 7;         // In
            $col_out = 8;        // Out

            // XLS: Single-line headers
            $pdf->ezPlaceData($col_date,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
            $pdf->ezPlaceData($col_type,$xtop-9,"<b>Tran. Type</b>",9,'left');
            $pdf->ezPlaceData($col_docnum,$xtop-9,"<b>Tran. Num.</b>",9,'left');
            $pdf->ezPlaceData($col_ordernum,$xtop-9,"<b>Order Num.</b>",9 ,'left');
            $pdf->ezPlaceData($col_shop,$xtop-9,"<b>Shop Name/Supplier</b>",9 ,'left');
            $pdf->ezPlaceData($col_buyer,$xtop-9,"<b>Ordered By</b>",9 ,'left');
            $pdf->ezPlaceData($col_in,$xtop-9,"<b>In</b>",9 ,'right');
            $pdf->ezPlaceData($col_out,$xtop-9,"<b>Out</b>",9 ,'right');
            $xtop-=15;
        } else {
            // PDF: Use pixel positions
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

            // PDF: Two-line headers
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
        }

        $in_total = 0;
        $out_total = 0;
        foreach($report_item['transactions'] as $rs_main2){
            if (isset($_POST['txt_output_type']) && $_POST['txt_output_type']=='tab')
            {
                $pdf->ezPlaceData($col_date,$xtop,$rs_main2["tranfile1_trndte"],9,"left");
                $pdf->ezPlaceData($col_type,$xtop,$rs_main2["tranfile1_trncde"],9,"left");
                $pdf->ezPlaceData($col_docnum,$xtop,xls_safe_text($rs_main2["tranfile2_docnum"]),9,"left");
                $pdf->ezPlaceData($col_ordernum,$xtop,xls_safe_text($rs_main2["tranfile1_ordernum"]),9,"left");
                $pdf->ezPlaceData($col_shop,$xtop,xls_safe_text($rs_main2["supp_or_cus"]),9,"left");
                $buyer_name = empty($rs_main2["buyer_name"]) ? " " : $rs_main2["buyer_name"];
                $pdf->ezPlaceData($col_buyer,$xtop,xls_safe_text($buyer_name),9,"left");

                if($rs_main2["tranfile2_stkqty"] > 0){
                    $pdf->ezPlaceData($col_in,$xtop,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                    $pdf->ezPlaceData($col_out,$xtop," ",9,"right");
                    $in_total += $rs_main2["tranfile2_stkqty"];
                }else{
                    $qty_out = $rs_main2["tranfile2_stkqty"] * -1;
                    $pdf->ezPlaceData($col_in,$xtop," ",9,"right");
                    $pdf->ezPlaceData($col_out,$xtop,number_format($qty_out),9,"right");
                    $out_total += $qty_out;
                }

                $xtop -= 15;

            }else{
                $docnum_lines = wrap_str_pdf($rs_main2["tranfile2_docnum"], $col_docnum_w, 9);
                $ordernum_lines = wrap_str_pdf($rs_main2["tranfile1_ordernum"], $col_ordernum_w, 9);
                $shop_lines = wrap_str_pdf($rs_main2["supp_or_cus"], $col_shop_w, 9);
                $buyer_text = empty($rs_main2["buyer_name"]) ? " " : $rs_main2["buyer_name"];
                $buyer_lines = wrap_str_pdf($buyer_text, $col_buyer_w, 9);

                $max_lines = max(
                    count($docnum_lines),
                    count($ordernum_lines),
                    count($shop_lines),
                    count($buyer_lines)
                );
                $row_height = 12 + ($max_lines - 1) * 10;

                if(($xtop - $row_height - 5) <= 60){
                    $pdf->ezNewPage();
                    $xtop = 483;
                }

                $row_y = $xtop;
                $pdf->ezPlaceData($col_date,$row_y,$rs_main2["tranfile1_trndte"],9,"left");
                $pdf->ezPlaceData($col_type,$row_y,$rs_main2["tranfile1_trncde"],9,"left");

                foreach($docnum_lines as $li => $line_text){
                    $pdf->ezPlaceData($col_docnum, $row_y - ($li * 10), $line_text, 9, "left");
                }
                foreach($ordernum_lines as $li => $line_text){
                    $pdf->ezPlaceData($col_ordernum, $row_y - ($li * 10), $line_text, 9, "left");
                }
                foreach($shop_lines as $li => $line_text){
                    $pdf->ezPlaceData($col_shop, $row_y - ($li * 10), $line_text, 9, "left");
                }
                foreach($buyer_lines as $li => $line_text){
                    $pdf->ezPlaceData($col_buyer, $row_y - ($li * 10), $line_text, 9, "left");
                }

                if($rs_main2["tranfile2_stkqty"] > 0){
                    $pdf->ezPlaceData($col_in,$row_y,number_format($rs_main2["tranfile2_stkqty"]),9,"right");
                    $in_total += $rs_main2["tranfile2_stkqty"];
                }else{
                    $qty_out = $rs_main2["tranfile2_stkqty"] * -1;
                    $pdf->ezPlaceData($col_out,$row_y,number_format($qty_out),9,"right");
                    $out_total += $qty_out;
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

        $xtop -= 5;

        if($_POST['txt_output_type'] =='tab'){
            $pdf->ezPlaceData(1,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(2,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(3,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(4,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(5,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(6,$xtop-9,"<b>Total:</b>",9 ,'right');
            $pdf->ezPlaceData(7,$xtop-9,"<b>".number_format($in_total)."</b>",9 ,'right');
            $pdf->ezPlaceData(8,$xtop-9,"<b>".number_format($out_total)."</b>",9 ,'right');
        }else{
            $pdf->ezPlaceData($col_buyer + 50,$xtop-9,"<b>Total:</b>",9 ,'right');
            $pdf->ezPlaceData($col_in,$xtop-9,"<b>".number_format($in_total)."</b>",9 ,'right');
            $pdf->ezPlaceData($col_out,$xtop-9,"<b>".number_format($out_total)."</b>",9 ,'right');
        }

        $xtop -= 18;

        if($_POST['txt_output_type'] =='tab'){
            $pdf->ezPlaceData(1,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(2,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(3,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(4,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(5,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(6,$xtop-9,"<b>Balance:</b>",9,'right');
            $pdf->ezPlaceData(7,$xtop-9," ",9 ,'right');
            $pdf->ezPlaceData(8,$xtop-9,"<b>".number_format($item_balance)."</b>",9 ,'right');
        }else{
            $pdf->ezPlaceData($col_buyer + 50,$xtop-9,"<b>Balance:</b>",9,'right');
            $pdf->ezPlaceData($col_out,$xtop-9,"<b>".number_format($item_balance)."</b>",9 ,'right');
        }

        $xtop -= 25;

        if($_POST['txt_output_type'] =='tab'){
            $pdf->ezPlaceData(1,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(2,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(3,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(4,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(5,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(6,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(7,$xtop-9," ",9 ,'left');
            $pdf->ezPlaceData(8,$xtop-9," ",9 ,'left');
            $xtop-=15;
        }

        if($xtop <= 70)
        {
            $pdf->ezNewPage();
            $xtop = 515;
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

    // XLS-safe text encoding: sanitizes text for tab-separated XLS output
    // Handles mojibake, special chars, and non-ASCII that can break Excel layout
    function xls_safe_text($string)
    {
        global $pdf;

        $string = (string)$string;
        if(get_class($pdf) != 'tab_ezpdf'){
            return $string;
        }

        // Try to fix encoding issues first
        if(function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        // Transliterate to ASCII to prevent layout-breaking chars in XLS
        if(function_exists('iconv')){
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if($converted !== false && $converted !== ''){
                $string = $converted;
            } else {
                // Fallback: strip all non-printable-ASCII
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        } else {
            // No iconv available: strip all non-printable-ASCII
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        // Remove tabs, line breaks, and control chars that break TSV format
        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return trim($string);
    }


?>
