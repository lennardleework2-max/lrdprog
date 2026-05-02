<?php
    //echo "<pre>"; var_dump($_POST); echo "</pre>"; die();

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');
    require_once('resources/stdfunc100.php');

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

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Costing(Date)</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;


        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);


        $xfields_heaeder_counter = 0;
        // Keep total column width within printable area (x=25 to x=790).
        $col_docnum = 92;
        $col_ordernum = 96;
        $col_trndte = 58;
        $col_paydate = 58;
        $col_customer = 92;
        $col_shop_item = 104;
        $col_qty = 45;
        $col_price = 73;
        $col_cost = 73;
        $col_profit = 74;
        // Force item/shop text to wrap earlier so it stays visually clear of Qty.
        $shop_item_text_padding = 24;
        $shop_col_x = $xleft + $col_docnum + $col_ordernum + $col_trndte + $col_paydate + $col_customer;

        $header_row1_y = $xtop;
        $header_row2_y = $xtop - 10;
		$pdf->line($xleft, $header_row1_y+10, 790, $header_row1_y+10);
        $pdf->line($xleft, $header_row2_y-4, 790, $header_row2_y-4);

        $xcol = $xleft;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Doc.</b>",10,'left');
        $pdf->ezPlaceData($xcol,$header_row2_y,"<b>Num.</b>",10,'left');

        $xcol += $col_docnum;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Order</b>",10,'left');
        $pdf->ezPlaceData($xcol,$header_row2_y,"<b>Num.</b>",10,'left');

        $xcol += $col_ordernum;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Tran.</b>",10,'left');
        $pdf->ezPlaceData($xcol,$header_row2_y,"<b>Date</b>",10,'left');

        $xcol += $col_trndte;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Paydate</b>",10,'left');
        $pdf->ezPlaceData($xcol,$header_row2_y,"<b>(Sales)</b>",10,'left');

        $xcol += $col_paydate;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Customer</b>",10,'left');
        $pdf->ezPlaceData($xcol,$header_row2_y,"<b>Name</b>",10,'left');

        $xcol += $col_customer;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Shop</b>",10,'left');
        $pdf->ezPlaceData($xcol,$header_row2_y,"<b>Name/Item</b>",10,'left');

        $xcol += $col_shop_item;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Qty</b>",10,'right');

        $xcol += $col_qty;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Price</b>",10,'right');

        $xcol += $col_price;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Cost</b>",10,'right');

        $xcol += $col_cost;
        $pdf->ezPlaceData($xcol,$header_row1_y,"<b>Profit</b>",10,'right');

        $xleft = 25;
		$xtop -= 25;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xfilter_params = array();

    // Paid/Unpaid checkboxes
    $inc_paid = 0;
    $inc_unpaid = 0;
    $inc_paid_smn = 0;
    $inc_unpaid_smn = 0;

    if (isset($_POST['chk_paid']) && $_POST['chk_paid']=='on') {
        $inc_paid = 1;
    }
    if (isset($_POST['chk_unpaid']) && $_POST['chk_unpaid']=='on') {
        $inc_unpaid = 1;
    }
    if (isset($_POST['chk_paid_smn']) && $_POST['chk_paid_smn']=='on') {
        $inc_paid_smn = 1;
    }
    if (isset($_POST['chk_unpaid_smn']) && $_POST['chk_unpaid_smn']=='on') {
        $inc_unpaid_smn = 1;
    }

    // Transaction date filter
    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND tranfile1.trndte>=? AND tranfile1.trndte<=?";
        $xfilter_params[] = $_POST['date_from'];
        $xfilter_params[] = $_POST['date_to'];
    }
    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter .= " AND tranfile1.trndte>=?";
        $xfilter_params[] = $_POST['date_from'];
    }
    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND tranfile1.trndte<=?";
        $xfilter_params[] = $_POST['date_to'];
    }

    // Paydate (Sales) filter
    if((isset($_POST['paydate_date_from']) && !empty($_POST['paydate_date_from'])) &&
    (isset($_POST['paydate_date_to']) && !empty($_POST['paydate_date_to'])) ){
        $_POST['paydate_date_from'] = date("Y-m-d", strtotime($_POST['paydate_date_from']));
        $_POST['paydate_date_to'] = date("Y-m-d", strtotime($_POST['paydate_date_to']));
        $xfilter .= " AND tranfile1.paydate>=? AND tranfile1.paydate<=?";
        $xfilter_params[] = $_POST['paydate_date_from'];
        $xfilter_params[] = $_POST['paydate_date_to'];
    }
    else if(isset($_POST['paydate_date_from']) && !empty($_POST['paydate_date_from'])){
        $_POST['paydate_date_from'] = date("Y-m-d", strtotime($_POST['paydate_date_from']));
        $xfilter .= " AND tranfile1.paydate>=?";
        $xfilter_params[] = $_POST['paydate_date_from'];
    }
    else if(isset($_POST['paydate_date_to']) && !empty($_POST['paydate_date_to'])){
        $_POST['paydate_date_to'] = date("Y-m-d", strtotime($_POST['paydate_date_to']));
        $xfilter .= " AND tranfile1.paydate<=?";
        $xfilter_params[] = $_POST['paydate_date_to'];
    }

    // Paydate (Salesman) filter
    if((isset($_POST['paydate_smn_date_from']) && !empty($_POST['paydate_smn_date_from'])) &&
    (isset($_POST['paydate_smn_date_to']) && !empty($_POST['paydate_smn_date_to'])) ){
        $_POST['paydate_smn_date_from'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_from']));
        $_POST['paydate_smn_date_to'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_to']));
        $xfilter .= " AND tranfile1.paydate_salesman>=? AND tranfile1.paydate_salesman<=?";
        $xfilter_params[] = $_POST['paydate_smn_date_from'];
        $xfilter_params[] = $_POST['paydate_smn_date_to'];
    }
    else if(isset($_POST['paydate_smn_date_from']) && !empty($_POST['paydate_smn_date_from'])){
        $_POST['paydate_smn_date_from'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_from']));
        $xfilter .= " AND tranfile1.paydate_salesman>=?";
        $xfilter_params[] = $_POST['paydate_smn_date_from'];
    }
    else if(isset($_POST['paydate_smn_date_to']) && !empty($_POST['paydate_smn_date_to'])){
        $_POST['paydate_smn_date_to'] = date("Y-m-d", strtotime($_POST['paydate_smn_date_to']));
        $xfilter .= " AND tranfile1.paydate_salesman<=?";
        $xfilter_params[] = $_POST['paydate_smn_date_to'];
    }

    // Shop Name filter
    if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
        $xfilter .= " AND customerfile.cusdsc=?";
        $xfilter_params[] = $_POST['cus_search'];
    }

    // Item filter
    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND tranfile2.itmcde=?";
        $xfilter_params[] = $_POST['item'];
    }

    // Salesman filter
    if(isset($_POST['smn_search']) && !empty($_POST['smn_search'])){
        $xfilter .= " AND tranfile1.salesman_id=?";
        $xfilter_params[] = $_POST['smn_search'];
    }

    // Paid/Unpaid filter for Sales
    if(!($inc_paid == 1 && $inc_unpaid == 1)){
        if($inc_paid == 1){
            $xfilter .= " AND tranfile1.paydate IS NOT NULL";
        }else if($inc_unpaid == 1){
            $xfilter .= " AND tranfile1.paydate IS NULL";
        }
    }

    // Paid/Unpaid filter for Salesman
    if(!($inc_paid_smn == 1 && $inc_unpaid_smn == 1)){
        if($inc_paid_smn == 1){
            $xfilter .= " AND tranfile1.paydate_salesman IS NOT NULL";
        }else if($inc_unpaid_smn == 1){
            $xfilter .= " AND tranfile1.paydate_salesman IS NULL";
        }
    }

    // Order by
    $xorder_by = "ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";

    if(isset($_POST['orderby_route']) && !empty($_POST['orderby_route']) && $_POST['orderby_route'] !='-'){
        $xorder_by = "ORDER BY mf_routes.route_desc ".$_POST['orderby_route'].", tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
    }
    if(isset($_POST['orderby_buyer']) && !empty($_POST['orderby_buyer']) && $_POST['orderby_buyer'] !='-'){
        $xorder_by = "ORDER BY mf_buyers.buyer_name ".$_POST['orderby_buyer'].", tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
    }
    if(isset($_POST['orderby_ordernum']) && !empty($_POST['orderby_ordernum']) && $_POST['orderby_ordernum'] !='-'){
        $xorder_by = "ORDER BY tranfile1.ordernum ".$_POST['orderby_ordernum'].", tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
    }
    if(isset($_POST['orderby_paydate_sales']) && !empty($_POST['orderby_paydate_sales']) && $_POST['orderby_paydate_sales'] !='-'){
        $xorder_by = "ORDER BY tranfile1.paydate ".$_POST['orderby_paydate_sales'].", tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
    }

    // OPTIMIZATION: Single query to get ALL transactions with their items in ONE database call
    // This eliminates the N+1 query problem (no more nested queries)
    $select_all = "SELECT
        tranfile1.docnum as t1_docnum,
        tranfile1.trndte as t1_trndte,
        tranfile1.ordernum as t1_ordernum,
        tranfile1.trntot as t1_trntot,
        tranfile1.paydate as t1_paydate,
        mf_buyers.buyer_name as buyer_buyer_name,
        customerfile.cusdsc as cus_cusdsc,
        tranfile2.recid as t2_recid,
        tranfile2.itmcde as t2_itmcde,
        tranfile2.unmcde as t2_unmcde,
        tranfile2.itmqty as t2_itmqty,
        tranfile2.extprc as t2_extprc,
        itemfile.itmdsc as itm_itmdsc
    FROM tranfile1
    LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
    LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
    LEFT JOIN mf_routes ON tranfile1.route_id = mf_routes.route_id
    LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
    LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
    WHERE tranfile1.trncde=? ".$xfilter."
    ".$xorder_by;

    $stmt_all = $link->prepare($select_all);
    $stmt_all->execute(array_merge(array($_POST['trncde_hidden']), $xfilter_params));
    $all_rows = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    // Collect all unique item codes for batch cost lookup
    $unique_items = array();
    foreach($all_rows as $row) {
        if(!empty($row['t2_itmcde'])) {
            $unique_items[$row['t2_itmcde']] = true;
        }
    }

    // OPTIMIZATION: Batch load ALL unit costs in ONE query instead of per-item queries
    $cost_cache = array();
    if(!empty($unique_items)) {
        $item_list = array_keys($unique_items);
        $placeholders = implode(',', array_fill(0, count($item_list), '?'));

        // Get the latest cost for each item (by recid DESC)
        $cost_query = "SELECT t2.itmcde, t2.unmcde, t2.untprc, t2.recid, t1.trndte
            FROM tranfile2 t2
            INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
            WHERE t2.itmcde IN ($placeholders)
            AND (t1.trncde='ADJ' OR t1.trncde='PUR')
            AND t2.stkqty > 0
            ORDER BY t2.itmcde, t2.recid DESC";

        $stmt_cost = $link->prepare($cost_query);
        $stmt_cost->execute($item_list);

        // Store ALL cost records grouped by item code + unit code (for date-based lookup)
        while($cost_row = $stmt_cost->fetch(PDO::FETCH_ASSOC)) {
            $cost_key = sales_cost_cache_key($cost_row['itmcde'], $cost_row['unmcde']);
            if(!isset($cost_cache[$cost_key])) {
                $cost_cache[$cost_key] = array();
            }
            $cost_cache[$cost_key][] = $cost_row;
        }
    }

    function sales_cost_cache_key($itmcde, $unmcde) {
        return (string)$itmcde . '|' . ($unmcde === NULL ? '__NULL__' : (string)$unmcde);
    }

    // Function to get unit cost from cache (no database query)
    function get_cached_unitcost($itmcde, $unmcde, $trndte, $sal_recid, &$cost_cache) {
        $cost_key = sales_cost_cache_key($itmcde, $unmcde);
        if(!isset($cost_cache[$cost_key]) || empty($cost_cache[$cost_key])) {
            return 0;
        }

        $costs = $cost_cache[$cost_key];

        // If we have a date, find cost where trndte <= sale date
        if(!empty($trndte)) {
            foreach($costs as $c) {
                if(!empty($c['trndte']) && $c['trndte'] <= $trndte) {
                    return $c['untprc'];
                }
            }
        }

        // Fallback: find cost where recid < sale recid
        if(!empty($sal_recid)) {
            foreach($costs as $c) {
                if($c['recid'] < $sal_recid) {
                    return $c['untprc'];
                }
            }
        }

        // Last fallback: return latest cost
        return $costs[0]['untprc'];
    }

    // Group data by docnum for rendering
    $grouped_data = array();
    $doc_order = array();
    foreach($all_rows as $row) {
        $docnum = $row['t1_docnum'];
        if(!isset($grouped_data[$docnum])) {
            $grouped_data[$docnum] = array(
                'header' => array(
                    'docnum' => $row['t1_docnum'],
                    'trndte' => $row['t1_trndte'],
                    'ordernum' => $row['t1_ordernum'],
                    'trntot' => $row['t1_trntot'],
                    'paydate' => $row['t1_paydate'],
                    'buyer_name' => $row['buyer_buyer_name'],
                    'cusdsc' => $row['cus_cusdsc']
                ),
                'items' => array()
            );
            $doc_order[] = $docnum;
        }
        // Only add item if it exists
        if(!empty($row['t2_itmcde'])) {
            $grouped_data[$docnum]['items'][] = array(
                'recid' => $row['t2_recid'],
                'itmcde' => $row['t2_itmcde'],
                'unmcde' => $row['t2_unmcde'],
                'itmqty' => $row['t2_itmqty'],
                'extprc' => $row['t2_extprc'],
                'itmdsc' => $row['itm_itmdsc']
            );
        }
    }

    // Now render PDF - NO MORE DATABASE QUERIES from this point
    $grand_total = 0;
    $price_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;

    foreach($doc_order as $docnum) {
        $doc = $grouped_data[$docnum];
        $header = $doc['header'];

        $xleft = 25;
        $grand_total += $header["trntot"];

        // Store original date for cost lookup
        $original_trndte = $header["trndte"];
        $display_trndte = '';
        if(!empty($header["trndte"])){
            $display_trndte = date("m/d/Y", strtotime($header["trndte"]));
        }
        $display_paydate = '';
        if(!empty($header["paydate"])){
            $display_paydate = date("m/d/Y", strtotime($header["paydate"]));
        }

        $buyer_name = trim((string)$header["buyer_name"]);
        $shop_name = trim((string)$header["cusdsc"]);

        if ($_POST['txt_output_type']=='tab') {
            $display_docnum = $header["docnum"];
            $buyer_lines = array($buyer_name);
            $display_cusdsc = $shop_name;
            $display_ordernum = $header["ordernum"];
        } else {
            $display_docnum = $header["docnum"];
            $buyer_lines = wrap_text_limited($buyer_name, $col_customer - 5, 9, 3);
            $display_cusdsc = trim_str($shop_name, $col_shop_item - $shop_item_text_padding, 9);
            $display_ordernum = trim_str($header["ordernum"], $col_ordernum - 5, 9);
        }

        $buyer_line_count = count($buyer_lines);
        $header_row_height = max(12, (($buyer_line_count - 1) * 10) + 12);

        if(($xtop - (($buyer_line_count - 1) * 10)) <= 60){
            $pdf->ezNewPage();
            $xtop = 505;
        }

        $pdf->ezPlaceData($xleft,$xtop,$display_docnum,9,"left");
        $pdf->ezPlaceData($xleft+=$col_docnum,$xtop,$display_ordernum,9,"left");
        $pdf->ezPlaceData($xleft+=$col_ordernum,$xtop,$display_trndte,9,"left");
        $pdf->ezPlaceData($xleft+=$col_trndte,$xtop,$display_paydate,9,"left");
        $customer_col_x = $xleft + $col_paydate;
        $pdf->ezPlaceData($customer_col_x,$xtop,$buyer_lines[0],9,"left");
        $pdf->ezPlaceData($xleft+=$col_customer,$xtop,$display_cusdsc,9,"left");

        if ($_POST['txt_output_type']!='tab') {
            for($buyer_line_idx = 1; $buyer_line_idx < $buyer_line_count; $buyer_line_idx++) {
                $pdf->ezPlaceData($customer_col_x, $xtop - ($buyer_line_idx * 10), $buyer_lines[$buyer_line_idx], 9, "left");
            }
        }

        $price_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
        $xtop -= $header_row_height;

        // Process items for this document
        foreach($doc['items'] as $item) {
            // Get unit cost from cache (NO database query)
            $trndte = (empty($original_trndte)) ? NULL : date("Y-m-d", strtotime($original_trndte));
            $unit_cost = get_cached_unitcost($item['itmcde'], $item['unmcde'], $trndte, $item['recid'], $cost_cache);
            $cost = $unit_cost * $item["itmqty"];
            $profit = $item["extprc"] - $cost;

            // For tab/xls output, columns must be in order: Doc Num, Order Num, Tran Date, Paydate (Sales), Customer Name, Shop Name/Item, Qty, Price, Cost, Profit
            $xleft = 25;
            $pdf->ezPlaceData($xleft,$xtop,"",9,"left");           // Empty Doc Num
            $pdf->ezPlaceData($xleft+=$col_docnum,$xtop,"",9,"left");       // Empty Order Num
            $pdf->ezPlaceData($xleft+=$col_ordernum,$xtop,"",9,"left");      // Empty Tran Date
            $pdf->ezPlaceData($xleft+=$col_trndte,$xtop,"",9,"left");      // Empty Paydate (Sales)
            $pdf->ezPlaceData($xleft+=$col_paydate,$xtop,"",9,"left");      // Empty Customer Name

            // Wrap long item names
            $itm_max_width = $col_shop_item - $shop_item_text_padding;
            $itm_lines = wrap_text($item["itmdsc"], $itm_max_width, 9);
            $itm_line_count = count($itm_lines);

            // For first line, output item name in column order
            $pdf->ezPlaceData($xleft+=$col_customer,$xtop,$itm_lines[0],9,"left");  // Item name
            $pdf->ezPlaceData($xleft+=$col_shop_item,$xtop,$item["itmqty"],9,"right");  // Quantity
            $pdf->ezPlaceData($xleft+=$col_qty,$xtop,number_format($item["extprc"],"2"),9,"right");  // Price
            $pdf->ezPlaceData($xleft+=$col_price,$xtop,number_format($cost,"2"),9,"right");  // Cost
            $pdf->ezPlaceData($xleft+=$col_cost,$xtop,number_format($profit,"2"),9,"right");  // Profit

            // For additional wrapped lines (PDF only), place them below
            for($idx = 1; $idx < $itm_line_count; $idx++) {
                $xtop -= 10;
                $pdf->ezPlaceData($shop_col_x, $xtop, $itm_lines[$idx], 9, "left");
            }

            $price_tot += $item["extprc"];
            $cost_tot += $cost;
            $profit_tot += $profit;

            $xtop -= max(15, $itm_line_count * 10 + 5);

            if($xtop <= 60){
                $pdf->ezNewPage();
                $xtop = 505;
            }
        }

        $pdf->line(25, $xtop, 790, $xtop);
        $xtop -= 15;
        // TOTAL row - output in column order
        $xleft = 25;
        $pdf->ezPlaceData($xleft,$xtop+5,"",9,"left");           // Empty Doc Num
        $pdf->ezPlaceData($xleft+=$col_docnum,$xtop+5,"",9,"left");       // Empty Order Num
        $pdf->ezPlaceData($xleft+=$col_ordernum,$xtop+5,"",9,"left");      // Empty Tran Date
        $pdf->ezPlaceData($xleft+=$col_trndte,$xtop+5,"",9,"left");      // Empty Paydate (Sales)
        $pdf->ezPlaceData($xleft+=$col_paydate,$xtop+5,"",9,"left");      // Empty Customer Name
        $pdf->ezPlaceData($xleft+=$col_customer,$xtop+5,"<b>TOTAL:</b>",9,"left");  // TOTAL label (Shop Name/Item column)
        $pdf->ezPlaceData($xleft+=$col_shop_item,$xtop+5,"",9,"right");     // Empty Quantity
        $pdf->ezPlaceData($xleft+=$col_qty,$xtop+5,number_format($price_tot,2),9,"right");
        $pdf->ezPlaceData($xleft+=$col_price,$xtop+5,number_format($cost_tot,2),9,"right");
        $pdf->ezPlaceData($xleft+=$col_cost,$xtop+5,number_format($profit_tot,2),9,"right");
        $pdf->line(25, $xtop-=5, 790, $xtop);
        $xtop -= 15;

        if($xtop <= 60){
            $pdf->ezNewPage();
            $xtop = 505;
        }

        $price_gtot += $price_tot;
        $cost_gtot += $cost_tot;
        $profit_gtot += $profit_tot;
    }

    $pdf->line(25, $xtop, 790, $xtop);
    $xtop -= 15;
    // GRAND TOTAL row - output in column order
    $xleft = 25;
    $pdf->ezPlaceData($xleft,$xtop+5,"",9,"left");           // Empty Doc Num
    $pdf->ezPlaceData($xleft+=$col_docnum,$xtop+5,"",9,"left");       // Empty Order Num
    $pdf->ezPlaceData($xleft+=$col_ordernum,$xtop+5,"",9,"left");      // Empty Tran Date
    $pdf->ezPlaceData($xleft+=$col_trndte,$xtop+5,"",9,"left");      // Empty Paydate (Sales)
    $pdf->ezPlaceData($xleft+=$col_paydate,$xtop+5,"",9,"left");      // Empty Customer Name
    $pdf->ezPlaceData($xleft+=$col_customer,$xtop+5,"<b>GRAND TOTAL:</b>",8,"left");  // GRAND TOTAL label (Shop Name/Item column)
    $pdf->ezPlaceData($xleft+=$col_shop_item,$xtop+5,"",9,"right");     // Empty Quantity
    $pdf->ezPlaceData($xleft+=$col_qty,$xtop+5,number_format($price_gtot,2),9,"right");
    $pdf->ezPlaceData($xleft+=$col_price,$xtop+5,number_format($cost_gtot,2),9,"right");
    $pdf->ezPlaceData($xleft+=$col_cost,$xtop+5,number_format($profit_gtot,2),9,"right");
    $pdf->line(25, $xtop-=5, 790, $xtop);


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

    // Wrap text to multiple lines based on max width
    function wrap_text($string, $max_wid, $fsize) {
        global $pdf;

        if(empty($string)) {
            return array('');
        }

        // Check if text fits in one line
        $text_width = $pdf->getTextWidth($fsize, $string);
        if($text_width <= $max_wid) {
            return array($string);
        }

        // Split into words and wrap
        $words = explode(' ', $string);
        $lines = array();
        $current_line = '';

        foreach($words as $word) {
            $test_line = ($current_line == '') ? $word : $current_line . ' ' . $word;
            $test_width = $pdf->getTextWidth($fsize, $test_line);

            if($test_width <= $max_wid) {
                $current_line = $test_line;
            } else {
                if($current_line != '') {
                    $lines[] = $current_line;
                }
                // If single word is too long, just add it anyway
                $current_line = $word;
            }
        }

        // Add remaining text
        if($current_line != '') {
            $lines[] = $current_line;
        }

        return $lines;
    }

    function wrap_text_limited($string, $max_wid, $fsize, $max_lines = 3) {
        global $pdf;

        if(empty($string)) {
            return array('');
        }

        $wrapped_lines = array();
        $base_lines = wrap_text($string, $max_wid, $fsize);

        foreach($base_lines as $base_line) {
            if($pdf->getTextWidth($fsize, $base_line) <= $max_wid) {
                $wrapped_lines[] = $base_line;
                continue;
            }

            $remaining = $base_line;
            while($remaining !== '') {
                $chunk = '';
                $chars = str_split($remaining);

                foreach($chars as $char) {
                    if($chunk !== '' && $pdf->getTextWidth($fsize, $chunk . $char) > $max_wid) {
                        break;
                    }
                    $chunk .= $char;
                }

                if($chunk === '') {
                    $chunk = substr($remaining, 0, 1);
                }

                $wrapped_lines[] = rtrim($chunk);
                $remaining = ltrim(substr($remaining, strlen($chunk)));
            }
        }

        if(count($wrapped_lines) > $max_lines) {
            $overflow_text = implode(' ', array_slice($wrapped_lines, $max_lines - 1));
            $wrapped_lines = array_slice($wrapped_lines, 0, $max_lines - 1);
            $wrapped_lines[] = trim_str($overflow_text, $max_wid, $fsize);
        }

        return empty($wrapped_lines) ? array('') : $wrapped_lines;
    }


?>
