<?php
    //var_dump($_POST);

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
        $pdf->ezPlaceData($xleft, $xtop,xls_safe_text("<b>Sales Costing (Item)</b>"), 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,xls_safe_text("<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>"), 9, 'left' );
        $xtop   -= 15;

        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Date Printed : '.$date_printed), 10, 'left' );
        $xtop   -= 15;

		$pdf->setLineStyle(.5);

        $xleft = 25;


		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xfilter2  = '';
    $xorder = '';

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
        $xfilter .= " AND itmcde='".$_POST['item']."'";
    }

    $select_db = "SELECT itmcde, itmdsc FROM itemfile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $item_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    $cost_cache = array();
    $pur_cost_cache = array(); // "itmcde|unmcde" => latest purchase untprc
    $unique_items = array();
    foreach($item_rows as $item_row){
        if(!empty($item_row['itmcde'])){
            $unique_items[$item_row['itmcde']] = true;
        }
    }

    if(!empty($unique_items)){
        $item_list = array_keys($unique_items);
        $placeholders = implode(',', array_fill(0, count($item_list), '?'));

        // Unit cost = item's "Cost" price in mf_salesitemprice (price type from mf_salespricetype)
        $cost_query = "SELECT sip.itmcde, sip.price
            FROM mf_salesitemprice sip
            WHERE sip.salesprice_id = (SELECT salesprice_id FROM mf_salespricetype WHERE salesprice_desc = 'Cost' LIMIT 1)
            AND sip.itmcde IN ($placeholders)";
        $stmt_cost = $link->prepare($cost_query);
        $stmt_cost->execute($item_list);
        $cost_cache = $stmt_cost->fetchAll(PDO::FETCH_KEY_PAIR);

        // Fallback: items with no/zero Cost price use the untprc of their latest purchase (PUR) line
        // dated <= today with the SAME unit of measure (unmcde) as the sale line
        $no_cost_items = array();
        foreach($item_list as $itm){
            if(empty($cost_cache[$itm]) || (float)$cost_cache[$itm] == 0){
                $no_cost_items[] = $itm;
            }
        }
        if(!empty($no_cost_items)){
            $pur_placeholders = implode(',', array_fill(0, count($no_cost_items), '?'));
            // Latest = latest tran date (recid breaks ties); first row per item+UOM wins
            $pur_cost_query = "SELECT t2.itmcde, t2.unmcde, t2.untprc
                FROM tranfile2 t2
                INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
                WHERE t1.trncde = 'PUR' AND t1.trndte <= ? AND t2.itmcde IN ($pur_placeholders)
                ORDER BY t1.trndte DESC, t2.recid DESC";
            $stmt_pur_cost = $link->prepare($pur_cost_query);
            $stmt_pur_cost->execute(array_merge(array(date('Y-m-d')), $no_cost_items));
            while($pur_row = $stmt_pur_cost->fetch(PDO::FETCH_ASSOC)){
                $pur_cost_key = $pur_row['itmcde'].'|'.$pur_row['unmcde'];
                if(!isset($pur_cost_cache[$pur_cost_key])){
                    $pur_cost_cache[$pur_cost_key] = (float)$pur_row['untprc'];
                }
            }
        }
    }

    // Batch-fetch all detail rows once.
    $detail_cache = array();
    if(!empty($unique_items)){
        $item_list = array_keys($unique_items);
        $placeholders = implode(',', array_fill(0, count($item_list), '?'));
        $params = $item_list;
        $params[] = $_POST['trncde_hidden'];
        $detail_query = "SELECT tranfile2.itmcde, tranfile2.docnum, tranfile2.recid, tranfile2.unmcde,
                tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc,
                tranfile1.trndte, customerfile.cusdsc, itemunitmeasurefile.unmdsc AS unmdsc
            FROM tranfile2
            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
            LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            WHERE tranfile2.itmcde IN ($placeholders) ".$xfilter2." AND tranfile1.trncde=?
            ORDER BY tranfile2.itmcde ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_detail = $link->prepare($detail_query);
        $stmt_detail->execute($params);

        while($detail_row = $stmt_detail->fetch(PDO::FETCH_ASSOC)){
            $itmcde = $detail_row['itmcde'];
            if(!isset($detail_cache[$itmcde])){
                $detail_cache[$itmcde] = array();
            }
            $detail_cache[$itmcde][] = $detail_row;
        }
    }

    // "Show items with no cost": list only sold items with no "Cost" price in mf_salesitemprice,
    // without their transactions.
    if(!empty($_POST['show_no_cost'])){
        $pdf->ezPlaceData(25, $xtop-9, xls_safe_text("<b>Items with no cost</b>"), 11, 'left');
        $pdf->line(25, $xtop-12, 770, $xtop-12);
        $xtop -= 27;

        $no_cost_count = 0;
        foreach($item_rows as $rs_main){
            $detail_rows = isset($detail_cache[$rs_main['itmcde']]) ? $detail_cache[$rs_main['itmcde']] : array();
            if(!item_has_no_cost_sale($rs_main['itmcde'], $detail_rows, $cost_cache)){
                continue;
            }
            $no_cost_count++;
            foreach(wrap_text($rs_main['itmdsc'], 700, 10) as $line){
                $pdf->ezPlaceData(25, $xtop, xls_safe_text($line), 10, 'left');
                $xtop -= 14;
                if($xtop <= 60){
                    $pdf->ezNewPage();
                    $xtop = 515;
                }
            }
        }
        if($no_cost_count == 0){
            $pdf->ezPlaceData(25, $xtop, xls_safe_text("No items with no cost."), 10, 'left');
        }

        $pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
        $pdf->ezStream();
        ob_end_flush();
        exit;
    }

    $grand_total_extprc = 0;
    $grand_total_profit = 0;
    $grand_total_cost = 0;
    foreach($item_rows as $rs_main){

        // Get detail rows from pre-fetched cache (no database query needed)
        $detail_rows = isset($detail_cache[$rs_main['itmcde']]) ? $detail_cache[$rs_main['itmcde']] : array();
        if(empty($detail_rows)){
            continue;
        }

        $pdf->ezPlaceData(25,$xtop-9,xls_safe_text("<b>Item:</b>"),10 ,'left');

        // Wrap long item names to multiple lines (max width 700 pixels for header)
        $itm_header_max_width = 700;
        $itm_header_lines = wrap_text($rs_main['itmdsc'], $itm_header_max_width, 10);
        $itm_header_line_count = count($itm_header_lines);
        foreach($itm_header_lines as $idx => $line) {
            $pdf->ezPlaceData(55, $xtop - 9 - ($idx * 12), xls_safe_text($line), 10, 'left');
        }

        $header_offset = max(12, $itm_header_line_count * 12);
        $pdf->line(25, $xtop - $header_offset, 770, $xtop - $header_offset);

        $xtop -= $header_offset;
        $xleft = 25;

        $pdf->ezPlaceData($xleft,$xtop-9,xls_safe_text("<b>Tran. Date</b>"),9 ,'left');
        $pdf->ezPlaceData($xleft+=80,$xtop-9,xls_safe_text("<b>Tran. Num.</b>"),9 ,'left');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Shop Name:</b>"),9 ,'left');
        $pdf->ezPlaceData($xleft+=135,$xtop-9,xls_safe_text("<b>Quantity</b>"),9 ,'right');
        $pdf->ezPlaceData($xleft+=10,$xtop-9,xls_safe_text("<b>UOM</b>"),9 ,'left');
        $pdf->ezPlaceData($xleft+=75,$xtop-9,xls_safe_text("<b>Unit Price</b>"),9 ,'right');

        $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Extended Price</b>"),9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Cost</b>"),9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Profit</b>"),9 ,'right');
        $pdf->line(25, $xtop-12, 770, $xtop-12);
        $xtop-=23;

        $subtotal_extprc = 0;
        $subtotal_cost = 0;
        $subtotal_profit = 0;

        foreach($detail_rows as $rs_main2){

            $xleft = 25;
            $subtotal_extprc+=$rs_main2["extprc"];

            $display_trndte = '';
            if(isset($rs_main2["trndte"]) && !empty($rs_main2["trndte"])){
                $display_trndte = date("m/d/Y",strtotime($rs_main2["trndte"]));
            }

            $pdf->ezPlaceData($xleft,$xtop, $display_trndte,9,"left");
            $pdf->ezPlaceData($xleft+=80,$xtop, xls_safe_text($rs_main2['docnum']),9,"left");
            $pdf->ezPlaceData($xleft+=95,$xtop,xls_safe_text($rs_main2["cusdsc"]),9,"left");
            $pdf->ezPlaceData($xleft+=135,$xtop,number_format($rs_main2["itmqty"]),9,"right");
            $pdf->ezPlaceData($xleft+=10,$xtop,xls_safe_text(isset($rs_main2["unmdsc"]) ? $rs_main2["unmdsc"] : ''),9,"left");
            $pdf->ezPlaceData($xleft+=75,$xtop,number_format($rs_main2["untprc"],2),9,"right");

            $pdf->ezPlaceData($xleft+=95,$xtop,number_format($rs_main2["extprc"],2),9,"right");
            $unit_cost = get_unit_cost($rs_main['itmcde'], $rs_main2['unmcde'], $cost_cache, $pur_cost_cache);
            $cost = $unit_cost * $rs_main2["itmqty"];

            $pdf->ezPlaceData($xleft+=95,$xtop,number_format($cost,2),9,"right");
            $profit = $rs_main2["extprc"] - $cost;
            $pdf->ezPlaceData($xleft+=95,$xtop,number_format($profit,2),9,"right");
            $xtop -= 15;

                $subtotal_cost += $cost;
                $subtotal_profit +=$profit;
            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 530;

                $xheader = $pdf->openObject();
                $pdf->saveState();

                $pdf->ezPlaceData(25,$xtop-9,xls_safe_text("<b>Item:</b>"),10 ,'left');

                // Wrap long item names to multiple lines on new page header
                $itm_newpage_lines = wrap_text($rs_main['itmdsc'], 700, 10);
                $itm_newpage_line_count = count($itm_newpage_lines);
                foreach($itm_newpage_lines as $idx => $line) {
                    $pdf->ezPlaceData(55, $xtop - 9 - ($idx * 12), xls_safe_text($line), 10, 'left');
                }

                $newpage_header_offset = max(12, $itm_newpage_line_count * 12);
                $pdf->line(25, $xtop - $newpage_header_offset, 770, $xtop - $newpage_header_offset);

                $xtop -= $newpage_header_offset;
                $xleft = 25;

                $pdf->ezPlaceData($xleft,$xtop-9,xls_safe_text("<b>Tran. Date</b>"),9 ,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop-9,xls_safe_text("<b>Tran. Num.</b>"),9 ,'left');
                $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Shop Name</b>"),9 ,'left');
                $pdf->ezPlaceData($xleft+=135,$xtop-9,xls_safe_text("<b>Quantity</b>"),9 ,'right');
                $pdf->ezPlaceData($xleft+=10,$xtop-9,xls_safe_text("<b>UOM</b>"),9 ,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop-9,xls_safe_text("<b>Unit Price</b>"),9 ,'right');

                $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Extended Price</b>"),9 ,'right');
                $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Cost</b>"),9 ,'right');
                $pdf->ezPlaceData($xleft+=95,$xtop-9,xls_safe_text("<b>Profit</b>"),9 ,'right');
                $pdf->line(25, $xtop-12, 770, $xtop-12);
                $xtop-=23;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'add');
            }

        }

        $pdf->line(25, $xtop, 770, $xtop);
        $xleft=25;
        // For XLS: output empty cells at earlier column positions to maintain alignment
        if(get_class($pdf) == 'tab_ezpdf'){
            $pdf->ezPlaceData($xleft,$xtop-9,'',9 ,'left');           // Tran. Date
            $pdf->ezPlaceData($xleft+=80,$xtop-9,'',9 ,'left');       // Tran. Num.
            $pdf->ezPlaceData($xleft+=95,$xtop-9,'',9 ,'left');       // Shop Name
            $pdf->ezPlaceData($xleft+=135,$xtop-9,'',9 ,'right');     // Quantity
            $pdf->ezPlaceData($xleft+=10,$xtop-9,'',9 ,'left');       // UOM
            $pdf->ezPlaceData($xleft+=75,$xtop-9,xls_safe_text("<b>Subtotal:</b>"),9 ,'right'); // Unit Price col
            $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_extprc,2)."</b>",9 ,'right');
            $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_cost,2)."</b>",9 ,'right');
            $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_profit,2)."</b>",9 ,'right');
        } else {
            $pdf->ezPlaceData($xleft+=390,$xtop-9,xls_safe_text("<b>Subtotal:</b>"),9 ,'left');
            $pdf->ezPlaceData($xleft+=100,$xtop-9,"<b>".number_format($subtotal_extprc,2)."</b>",9 ,'right');
            $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_cost,2)."</b>",9 ,'right');
            $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_profit,2)."</b>",9 ,'right');
        }

        $xtop-=20;

        $grand_total_extprc +=$subtotal_extprc;
        $grand_total_profit +=$subtotal_profit;
        $grand_total_cost +=  $subtotal_cost;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 515;
        }


    }


    $pdf->line(25, $xtop-10, 770, $xtop-10);
    $xleft=25;
    // For XLS: output empty cells at earlier column positions to maintain alignment
    if(get_class($pdf) == 'tab_ezpdf'){
        $pdf->ezPlaceData($xleft,$xtop-18,'',9 ,'left');           // Tran. Date
        $pdf->ezPlaceData($xleft+=80,$xtop-18,'',9 ,'left');       // Tran. Num.
        $pdf->ezPlaceData($xleft+=95,$xtop-18,'',9 ,'left');       // Shop Name
        $pdf->ezPlaceData($xleft+=135,$xtop-18,'',9 ,'right');     // Quantity
        $pdf->ezPlaceData($xleft+=10,$xtop-18,'',9 ,'left');       // UOM
        $pdf->ezPlaceData($xleft+=75,$xtop-18,xls_safe_text("<b>Grand total:</b>"),9 ,'right'); // Unit Price col
        $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_extprc,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_cost,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_profit,2)."</b>",9 ,'right');
    } else {
        $pdf->ezPlaceData($xleft+=384,$xtop-18,xls_safe_text("<b>Grand total:</b>"),8 ,'left');
        $pdf->ezPlaceData($xleft+=107,$xtop-18,"<b>".number_format($grand_total_extprc,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_cost,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_profit,2)."</b>",9 ,'right');
    }

    $pdf->line(25, $xtop-10, 770, $xtop-10);
	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

    // Unit cost of a sale line: masterfile Cost, else latest purchase untprc in the same UOM, else 0
    function get_unit_cost($itmcde, $unmcde, $cost_cache, $pur_cost_cache)
    {
        $unit_cost = isset($cost_cache[$itmcde]) ? (float)$cost_cache[$itmcde] : 0;
        $pur_cost_key = $itmcde.'|'.$unmcde;
        if($unit_cost == 0 && isset($pur_cost_cache[$pur_cost_key])){
            $unit_cost = $pur_cost_cache[$pur_cost_key];
        }
        return $unit_cost;
    }

    // True when the item was sold but has no "Cost" price in mf_salesitemprice (missing or 0).
    // Purchases are NOT considered here.
    function item_has_no_cost_sale($itmcde, $detail_rows, $cost_cache)
    {
        return !empty($detail_rows) && (!isset($cost_cache[$itmcde]) || (float)$cost_cache[$itmcde] == 0);
    }

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

        $string = xls_safe_text($string);

        if(empty($string)) {
            return array('');
        }

        if(get_class($pdf) == 'tab_ezpdf'){
            return array($string);
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

?>
