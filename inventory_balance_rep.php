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
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    if ($is_tab_export)
	{
		$pdf = new tab_ezpdf('Letter','portrait');
	}
	else
	{
		$pdf = new Cezpdf('Letter','portrait');
	}

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $xtop = 750;
    $xleft = 25;
    $line_right = 585;
    $col_item = 25;
    $col_warehouse = 205;
    $col_balance = 570;
    $item_max_width = 165;
    $warehouse_max_width = 285;

    /**header**/
    $fields_count = 0;
    $fields = '';

        $progname_hidden ='';
        if(isset($_POST['trncde_hidden']) && $_POST['trncde_hidden'] == 'SAL'){
            $progname_hidden = "Sales";
        }
        else if(isset($_POST['trncde_hidden']) && $_POST['trncde_hidden'] == 'SRT'){
            $progname_hidden = "Sales Return";
        }
        else if(isset($_POST['trncde_hidden']) && $_POST['trncde_hidden'] == 'PUR'){
            $progname_hidden = "Purchases";
        }

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Inventory Balance</b>", 15, 'left' );
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, $line_right, $xtop+10);
        $pdf->line($xleft, $xtop-3, $line_right, $xtop-3);

        $xfields_heaeder_counter = 0;

        $pdf->ezPlaceData($col_item,$xtop,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",10,'left');
        $pdf->ezPlaceData($col_balance,$xtop,"<b>Balance</b>",10,'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    $xfilter = '';
    $xfilter2 = '';

    if(isset($_POST['date_search']) && !empty($_POST['date_search'])){
        $_POST['date_search'] = date("Y-m-d", strtotime($_POST['date_search']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_search']."'";
    }

    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND tranfile2.itmcde='".$_POST['item']."'";
    }

    $select_db = "SELECT
                    tranfile2.itmcde,
                    COALESCE(itemfile.itmdsc, tranfile2.itmcde) AS item_display,
                    tranfile2.warcde,
                    tranfile2.warehouse_floor_id,
                    COALESCE(warehouse.warehouse_name, '') AS warehouse_name,
                    COALESCE(warehouse_floor.floor_no, '') AS floor_no,
                    SUM(tranfile2.stkqty) AS location_balance
                FROM tranfile2
                LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
                LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
                LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
                LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
                WHERE true ".$xfilter2.$xfilter."
                GROUP BY
                    tranfile2.itmcde,
                    itemfile.itmdsc,
                    tranfile2.warcde,
                    tranfile2.warehouse_floor_id,
                    warehouse.warehouse_name,
                    warehouse_floor.floor_no
                ORDER BY item_display ASC, location_balance DESC, warehouse.warehouse_name ASC, warehouse_floor.floor_no ASC";

    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute();

    $grand_total = 0;
    $current_item_code = '';
    $current_item_total = 0;
    while($rs_main = $stmt_main->fetch()){

        if($current_item_code !== '' && $current_item_code !== $rs_main['itmcde']){
            if(($xtop - 18) <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 685;
            }

            if($is_tab_export){
                $pdf->ezPlaceData($col_item,$xtop,"",9,"left");
            }
            $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Total:</b>",9,"left");
            $pdf->ezPlaceData($col_balance,$xtop,"<b>".format_report_balance($current_item_total)."</b>",9,"right");
            $grand_total += $current_item_total;
            $xtop -= 24;
        }

        $show_item_name = ($current_item_code !== $rs_main['itmcde']);
        if($show_item_name){
            $current_item_code = $rs_main['itmcde'];
            $current_item_total = 0;
        }

        $item_display = $show_item_name ? $rs_main['item_display'] : '';
        $warehouse_display = build_warehouse_display($rs_main['warehouse_name'], $rs_main['floor_no']);
        $location_balance = (float)$rs_main['location_balance'];

        $item_lines = wrap_report_text($item_display, $item_max_width, 9);
        $warehouse_lines = wrap_report_text($warehouse_display, $warehouse_max_width, 9);
        $line_count = max(count($item_lines), count($warehouse_lines), 1);
        $row_height = 15 + ((max(1, $line_count) - 1) * 10);

        if(($xtop - $row_height) <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 685;
        }

        $row_y = $xtop;
        foreach($item_lines as $item_line_index => $item_line_text){
            if($item_line_text !== '' || ($is_tab_export && $item_line_index === 0)){
                $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), $item_line_text, 9, "left");
            }
        }

        foreach($warehouse_lines as $warehouse_line_index => $warehouse_line_text){
            if($warehouse_line_text !== '' || ($is_tab_export && $warehouse_line_index === 0)){
                $pdf->ezPlaceData($col_warehouse, $row_y - ($warehouse_line_index * 10), $warehouse_line_text, 9, "left");
            }
        }

        $pdf->ezPlaceData($col_balance,$row_y,format_report_balance($location_balance),9,"right");

        $current_item_total += $location_balance;
        $xtop -= $row_height;
    }

    if($current_item_code !== ''){
        if(($xtop - 18) <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 685;
        }

        if($is_tab_export){
            $pdf->ezPlaceData($col_item,$xtop,"",9,"left");
        }
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Total:</b>",9,"left");
        $pdf->ezPlaceData($col_balance,$xtop,"<b>".format_report_balance($current_item_total)."</b>",9,"right");
        $grand_total += $current_item_total;
        $xtop -= 24;
    }
    else
    {
        $pdf->ezPlaceData($col_item,$xtop,"No data found.",9,"left");
        $xtop -= 24;
    }

    if(($xtop - 25) <= 60)
    {
        $pdf->ezNewPage();
        $xtop = 685;
    }

    $pdf->line(25, $xtop-5, $line_right, $xtop-5);
    $xtop -= 18;
    if($is_tab_export){
        $pdf->ezPlaceData($col_item,$xtop,"",9,"left");
    }
    $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Grand total:</b>",9,"left");
    $pdf->ezPlaceData($col_balance,$xtop,"<b>".format_report_balance($grand_total)."</b>",9,"right");

	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

    function wrap_report_text($string,$max_wid,$fsize)
    {
        global $pdf;

        $string = trim((string)$string);
        if($string === ''){
            return array('');
        }

        if(get_class($pdf) == 'tab_ezpdf'){
            return array($string);
        }

        $max_wid -= 5;
        if($pdf->getTextWidth($fsize, $string) <= $max_wid){
            return array($string);
        }

        $wrapped_lines = array();
        $remaining = $string;

        while($remaining !== ''){
            if($pdf->getTextWidth($fsize, $remaining) <= $max_wid){
                $wrapped_lines[] = $remaining;
                break;
            }

            $line = fit_text_to_width($remaining, $max_wid, $fsize);
            if($line === ''){
                $line = substr($remaining, 0, 1);
            }

            $last_space = strrpos($line, ' ');
            if($last_space !== false && $last_space > 0){
                $candidate_line = rtrim(substr($line, 0, $last_space));
                if($candidate_line !== ''){
                    $line = $candidate_line;
                }
            }

            $wrapped_lines[] = rtrim($line);
            $remaining = ltrim(substr($remaining, strlen($line)));
        }

        if(empty($wrapped_lines)){
            $wrapped_lines[] = $string;
        }

        return $wrapped_lines;
    }

    function fit_text_to_width($string,$max_wid,$fsize)
    {
        global $pdf;

        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr = "";
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $max_wid)
            {
                break;
            }
            $xxstr = $xxstr.$value;
        }

        return rtrim($xxstr);
    }

    function build_warehouse_display($warehouse_name, $floor_no)
    {
        $warehouse_name = trim((string)$warehouse_name);
        $floor_no = trim((string)$floor_no);

        $warehouse_display = $warehouse_name;
        if($floor_no !== ''){
            $warehouse_display .= ($warehouse_display !== '' ? ' ' : '') . $floor_no . ' floor';
        }

        return trim($warehouse_display);
    }

    function format_report_balance($value)
    {
        $formatted_balance = number_format((float)$value, 4, '.', ',');
        $formatted_balance = rtrim(rtrim($formatted_balance, '0'), '.');

        if($formatted_balance === '-0'){
            $formatted_balance = '0';
        }

        return $formatted_balance;
    }
?>
