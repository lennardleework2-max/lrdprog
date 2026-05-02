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
        $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Costing (Item)</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;

        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
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

    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute(array($_POST['item']));
    $item_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    $cost_cache = array();
    $unique_items = array();
    foreach($item_rows as $item_row){
        if(!empty($item_row['itmcde'])){
            $unique_items[$item_row['itmcde']] = true;
        }
    }

    if(!empty($unique_items)){
        $item_list = array_keys($unique_items);
        $placeholders = implode(',', array_fill(0, count($item_list), '?'));
        $cost_query = "SELECT t2.itmcde, t2.unmcde, t2.untprc, t2.recid, t1.trndte
            FROM tranfile2 t2
            INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
            WHERE t2.itmcde IN ($placeholders)
            AND (t1.trncde='ADJ' OR t1.trncde='PUR')
            AND t2.stkqty > 0
            ORDER BY t2.itmcde, t2.recid DESC";
        $stmt_cost = $link->prepare($cost_query);
        $stmt_cost->execute($item_list);

        while($cost_row = $stmt_cost->fetch(PDO::FETCH_ASSOC)){
            $cost_key = item_sales_cost_cache_key($cost_row['itmcde'], $cost_row['unmcde']);
            if(!isset($cost_cache[$cost_key])){
                $cost_cache[$cost_key] = array();
            }
            $cost_cache[$cost_key][] = $cost_row;
        }
    }

    $grand_total_extprc = 0;
    $grand_total_profit = 0;
    $grand_total_cost = 0;
    foreach($item_rows as $rs_main){

        $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');

        // Wrap long item names to multiple lines (max width 700 pixels for header)
        $itm_header_max_width = 700;
        $itm_header_lines = wrap_text($rs_main['itmdsc'], $itm_header_max_width, 10);
        $itm_header_line_count = count($itm_header_lines);
        foreach($itm_header_lines as $idx => $line) {
            $pdf->ezPlaceData(55, $xtop - 9 - ($idx * 12), $line, 10, 'left');
        }

        $header_offset = max(12, $itm_header_line_count * 12);
        $pdf->line(25, $xtop - $header_offset, 770, $xtop - $header_offset);

        $xtop -= $header_offset;
        $xleft = 25;

        $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Tran. Num.</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Shop Name:</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=135,$xtop-9,"<b>Quantity</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=10,$xtop-9,"<b>UOM</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=75,$xtop-9,"<b>Unit Price</b>",9 ,'right');

        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Extended Price</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Cost</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Profit</b>",9 ,'right');
        $pdf->line(25, $xtop-12, 770, $xtop-12);
        $xtop-=23;

        // Optimized: Join tranfile2 with tranfile1 and customerfile in one query (no nested queries)
        $select_db2 = "SELECT tranfile2.*, tranfile1.trndte, tranfile1.ordernum, customerfile.cusdsc, itemunitmeasurefile.unmdsc as unmdsc
            FROM tranfile2
            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
            LEFT JOIN customerfile ON tranfile1.cuscde = customerfile.cuscde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            WHERE tranfile2.itmcde=? ".$xfilter2." AND tranfile1.trncde=?
            ORDER BY tranfile1.trndte ASC";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute(array($rs_main['itmcde'], $_POST['trncde_hidden']));
        $subtotal_extprc = 0;
        $subtotal_cost = 0;
        $subtotal_profit = 0;

        while($rs_main2 = $stmt_main2->fetch()){

            $xleft = 25;
            $subtotal_extprc+=$rs_main2["extprc"];

            $display_trndte = '';
            if(isset($rs_main2["trndte"]) && !empty($rs_main2["trndte"])){
                $display_trndte = date("m/d/Y",strtotime($rs_main2["trndte"]));
            }

            $pdf->ezPlaceData($xleft,$xtop, $display_trndte,9,"left");
            $pdf->ezPlaceData($xleft+=80,$xtop, $rs_main2['docnum'],9,"left");
            $pdf->ezPlaceData($xleft+=95,$xtop,$rs_main2["cusdsc"],9,"left");
            $pdf->ezPlaceData($xleft+=135,$xtop,number_format($rs_main2["itmqty"]),9,"right");
            $pdf->ezPlaceData($xleft+=10,$xtop,isset($rs_main2["unmdsc"]) ? $rs_main2["unmdsc"] : '',9,"left");
            $pdf->ezPlaceData($xleft+=75,$xtop,number_format($rs_main2["untprc"],2),9,"right");

            $pdf->ezPlaceData($xleft+=95,$xtop,number_format($rs_main2["extprc"],2),9,"right");
            $trndte  = (empty($rs_main2['trndte'])) ? NULL :  date("Y-m-d", strtotime($rs_main2['trndte']));
            $unit_cost = item_sales_cost_get_cached_unitcost($rs_main['itmcde'], isset($rs_main2['unmcde']) ? $rs_main2['unmcde'] : NULL, $trndte, $rs_main2['recid'], $cost_cache);
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

                $pdf->ezPlaceData(25,$xtop-9,"<b>Item:</b>",10 ,'left');

                // Wrap long item names to multiple lines on new page header
                $itm_newpage_lines = wrap_text($rs_main['itmdsc'], 700, 10);
                $itm_newpage_line_count = count($itm_newpage_lines);
                foreach($itm_newpage_lines as $idx => $line) {
                    $pdf->ezPlaceData(55, $xtop - 9 - ($idx * 12), $line, 10, 'left');
                }

                $newpage_header_offset = max(12, $itm_newpage_line_count * 12);
                $pdf->line(25, $xtop - $newpage_header_offset, 770, $xtop - $newpage_header_offset);

                $xtop -= $newpage_header_offset;
                $xleft = 25;

                $pdf->ezPlaceData($xleft,$xtop-9,"<b>Tran. Date</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=80,$xtop-9,"<b>Tran. Num.</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Shop Name</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=135,$xtop-9,"<b>Quantity</b>",9 ,'right');
                $pdf->ezPlaceData($xleft+=10,$xtop-9,"<b>UOM</b>",9 ,'left');
                $pdf->ezPlaceData($xleft+=75,$xtop-9,"<b>Unit Price</b>",9 ,'right');

                $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Extended Price</b>",9 ,'right');
                $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Cost</b>",9 ,'right');
                $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>Profit</b>",9 ,'right');
                $pdf->line(25, $xtop-12, 770, $xtop-12);
                $xtop-=23;

                $pdf->restoreState();
                $pdf->closeObject();
                $pdf->addObject($xheader,'add');
            }

        }

        $pdf->line(25, $xtop, 770, $xtop);
        $xleft=25;
        $pdf->ezPlaceData($xleft+=390,$xtop-9,"<b>Subtotal:</b>",9 ,'left');
        $pdf->ezPlaceData($xleft+=100,$xtop-9,"<b>".number_format($subtotal_extprc,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_cost,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($xleft+=95,$xtop-9,"<b>".number_format($subtotal_profit,2)."</b>",9 ,'right');

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
    $pdf->ezPlaceData($xleft+=384,$xtop-18,"<b>Grand total:</b>",8 ,'left');
    $pdf->ezPlaceData($xleft+=107,$xtop-18,"<b>".number_format($grand_total_extprc,2)."</b>",9 ,'right');
    $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_cost,2)."</b>",9 ,'right');
    $pdf->ezPlaceData($xleft+=95,$xtop-18,"<b>".number_format($grand_total_profit,2)."</b>",9 ,'right');

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

    function item_sales_cost_cache_key($itmcde, $unmcde)
    {
        return (string)$itmcde . '|' . ($unmcde === NULL ? '__NULL__' : (string)$unmcde);
    }

    function item_sales_cost_get_cached_unitcost($itmcde, $unmcde, $trndte, $sal_recid, $cost_cache)
    {
        $cost_key = item_sales_cost_cache_key($itmcde, $unmcde);
        if(!isset($cost_cache[$cost_key]) || empty($cost_cache[$cost_key])){
            return 0;
        }

        $costs = $cost_cache[$cost_key];

        if(!empty($trndte)){
            foreach($costs as $cost_row){
                if(!empty($cost_row['trndte']) && $cost_row['trndte'] <= $trndte){
                    return $cost_row['untprc'];
                }
            }
        }

        if(!empty($sal_recid)){
            foreach($costs as $cost_row){
                if($cost_row['recid'] < $sal_recid){
                    return $cost_row['untprc'];
                }
            }
        }

        return $costs[0]['untprc'];
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

?>
