<?php
//error_reporting(E_ALL);
//ini_set('display_errors',true);
ini_set('memory_limit',-1);
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');

    $tab_file_type = 'xlsx';
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');
    $export_label = $is_tab_export ? strtoupper($tab_file_type) : 'PDF';

    // Log export activity
    $username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
    $username_full_name = '';
    if(isset($_SESSION['recid'])){
        $select_db_session_user='SELECT * FROM users where recid=?';
        $stmt_session_user = $link->prepare($select_db_session_user);
        $stmt_session_user->execute(array($_SESSION['recid']));
        $rs_session_user = $stmt_session_user->fetch();
        if($rs_session_user){
            $username_full_name = $rs_session_user["full_name"];
        }
    }
    $xtrndte_log = date("Y-m-d H:i:s");
    $xprog_module_log = 'TOP SALES ITEM';
    $xactivity_log = $is_tab_export ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".$export_label." from Top Sales Item";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    $xreport_title = "Top Sales Items";


    if ($is_tab_export)
	{
		$pdf = new tab_ezpdf('Letter','landscape');
	}
	else
	{
		$pdf = new Cezpdf('Letter','landscape');

	}

    $pdf ->selectFont("ezpdfclass/fonts/Helvetica.afm");

	$pdf->ezStartPageNumbers(700,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

	$xtop = 570;
    $xleft = 25;

    /**header**/

    //getting header fields
    $fields_count = 0;
    $fields = '';

    $head_date_from='01-01-2000';
    $head_date_to=date('m-d-Y');
    $xfilter = '';
    $xfilter_current_stock = '';
    $date_from_sql = '';
    $date_to_sql = '';
    $current_stock_qty_sort = 'asc';
    $sales_30_qty_sort = 'asc';
    $sort_mode = 'current_stock_qty';

    if(isset($_POST['current_stock_qty_sort'])){
        $tmp_sort = strtolower(trim($_POST['current_stock_qty_sort']));
        if($tmp_sort === 'asc' || $tmp_sort === 'desc'){
            $current_stock_qty_sort = $tmp_sort;
        }
    }

    if(isset($_POST['sales_30_qty_sort'])){
        $tmp_sort_30 = strtolower(trim($_POST['sales_30_qty_sort']));
        if($tmp_sort_30 === 'asc' || $tmp_sort_30 === 'desc'){
            $sales_30_qty_sort = $tmp_sort_30;
        }
    }

    if(isset($_POST['sort_mode'])){
        $tmp_sort_mode = strtolower(trim($_POST['sort_mode']));
        if($tmp_sort_mode === 'current_stock_qty' || $tmp_sort_mode === 'sales_30_qty'){
            $sort_mode = $tmp_sort_mode;
        }
    }

    if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $date_from_sql = date("Y-m-d", strtotime($_POST['date_from']));

        $head_date_from = date("m-d-Y", strtotime($date_from_sql));

        $xfilter .= " AND main_tranfile1.trndte>='".$date_from_sql."'";
        $xfilter_current_stock .= " AND stock_tranfile1.trndte>='".$date_from_sql."'";
    }

    if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $date_to_sql = date("Y-m-d", strtotime($_POST['date_to']));

        $head_date_to = date("m-d-Y", strtotime($date_to_sql));

        $xfilter .= " AND main_tranfile1.trndte<='".$date_to_sql."'";
        $xfilter_current_stock .= " AND stock_tranfile1.trndte<='".$date_to_sql."'";
    }

    // Anchor rolling 30/60/90-day columns to selected Date To, otherwise to latest SAL trndte.
    $latest_sales_date = $date_to_sql;
    if(empty($latest_sales_date)){
        $select_latest_date = "SELECT MAX(trndte) AS latest_trndte FROM tranfile1 WHERE trncde='SAL'";
        if(!empty($date_from_sql)){
            $select_latest_date .= " AND trndte>='".$date_from_sql."'";
        }
        $stmt_latest_date = $link->prepare($select_latest_date);
        $stmt_latest_date->execute();
        $rs_latest_date = $stmt_latest_date->fetch();
        if($rs_latest_date && !empty($rs_latest_date['latest_trndte'])){
            $latest_sales_date = date("Y-m-d", strtotime($rs_latest_date['latest_trndte']));
        }
    }
    if(empty($latest_sales_date)){
        $latest_sales_date = date("Y-m-d");
    }
    $head_latest_sales_date = date("m-d-Y", strtotime($latest_sales_date));
    $past_30_start = date("Y-m-d", strtotime($latest_sales_date." -29 days"));
    $past_60_start = date("Y-m-d", strtotime($latest_sales_date." -59 days"));
    $past_90_start = date("Y-m-d", strtotime($latest_sales_date." -89 days"));

        $line_right = 760;
    $col_item = 20;
    $col_amount = 200;
    $col_qty = 250;
    $col_sales_30 = 315;
    $col_sales_60 = 380;
    $col_sales_90 = 445;
    $col_current_stock = 520;
    $col_cost = 580;
    $col_current_inventory_valuation = 675;
    $col_current_stock_qty = 748;



		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Top Sales Report (by Items)</b>", 15, 'left' );
        $xtop   -= 15;
        $header="<b>Pdf Report by: ".$_SESSION['userdesc']." </b>";

        $pdf->ezPlaceData($xleft, $xtop,$header, 9, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop, "Sales Date Range : ".$head_date_from." to ".$head_date_to, 10, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop, "Rolling Sales As Of : ".$head_latest_sales_date, 10, 'left' );


        // $pdf->ezPlaceData($xleft, $xtop,$_POST['search_hidden_dd'].":", 9, 'left' );
        // $pdf->ezPlaceData(dynamic_width($_POST['search_hidden_dd'].":",$xleft,3,'cus_left'), $xtop,$_POST['search_hidden_value'], 9, 'left' );
        $xtop   -= 15;


        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+6, $line_right, $xtop+6);
        $pdf->line($xleft, $xtop-22, $line_right, $xtop-22);


                $xfields_heaeder_counter = 0;
        $xheader_base_y = $xtop - 8;

        $pdf->ezPlaceData($col_item,$xheader_base_y,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($col_amount,$xheader_base_y,"<b>Amount</b>",10,'right');
        $pdf->ezPlaceData($col_qty,$xheader_base_y,"<b>Qty</b>",10,'right');
        $pdf->ezPlaceData($col_sales_30,$xheader_base_y,"<b>Sales 30D</b>",9,'right');
        $pdf->ezPlaceData($col_sales_60,$xheader_base_y,"<b>Sales 60D</b>",9,'right');
        $pdf->ezPlaceData($col_sales_90,$xheader_base_y,"<b>Sales 90D</b>",9,'right');
        $pdf->ezPlaceData($col_current_stock,$xheader_base_y,"<b>Current Stock</b>",9,'right');
        $pdf->ezPlaceData($col_cost,$xheader_base_y,"<b>Cost</b>",9,'right');
        $pdf->ezPlaceData($col_current_inventory_valuation,$xheader_base_y,"<b>Current Inventory</b>",7,'right');
        $pdf->ezPlaceData($col_current_inventory_valuation,$xheader_base_y-9,"<b>Valuation</b>",7,'right');
        $pdf->ezPlaceData($col_current_stock_qty,$xheader_base_y,"<b>Current Stock/</b>",7,'right');
        $pdf->ezPlaceData($col_current_stock_qty,$xheader_base_y-9,"<b>Qty</b>",7,'right');


		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    #region DO YOU LOOP


    $xfilter_smn = '';
    $xorder = '';
    $xsal_ret_tot =0;

    if($sort_mode === 'sales_30_qty'){
        $xorder = " ORDER BY base.sales_past_30 ".strtoupper($sales_30_qty_sort).", base.tot_extprc DESC";
    }else{
        $xorder = " ORDER BY current_stock_qty ".strtoupper($current_stock_qty_sort).", base.tot_extprc DESC";
    }


$select_db_base="SELECT itemfile.itmdsc as itmdsc,
    SUM(main_tranfile2.extprc) as tot_extprc,
    SUM(main_tranfile2.itmqty) as tot_itmqty,
    SUM(CASE WHEN main_tranfile1.trndte>='".$past_30_start."' AND main_tranfile1.trndte<='".$latest_sales_date."' THEN main_tranfile2.extprc ELSE 0 END) AS sales_past_30,
    SUM(CASE WHEN main_tranfile1.trndte>='".$past_30_start."' AND main_tranfile1.trndte<='".$latest_sales_date."' THEN main_tranfile2.itmqty ELSE 0 END) AS qty_past_30,
    SUM(CASE WHEN main_tranfile1.trndte>='".$past_60_start."' AND main_tranfile1.trndte<='".$latest_sales_date."' THEN main_tranfile2.extprc ELSE 0 END) AS sales_past_60,
    SUM(CASE WHEN main_tranfile1.trndte>='".$past_90_start."' AND main_tranfile1.trndte<='".$latest_sales_date."' THEN main_tranfile2.extprc ELSE 0 END) AS sales_past_90,
    COALESCE((
        SELECT pur_tranfile2.untprc
        FROM tranfile2 pur_tranfile2
        LEFT JOIN tranfile1 pur_tranfile1 ON pur_tranfile2.docnum=pur_tranfile1.docnum
        WHERE pur_tranfile2.itmcde=main_tranfile2.itmcde
          AND pur_tranfile1.trncde='PUR'
        ORDER BY pur_tranfile1.trndte DESC, pur_tranfile2.recid DESC
        LIMIT 1
    ),0) AS latest_cost,
    COALESCE((
        SELECT SUM(stock_tranfile2.stkqty)
        FROM tranfile2 stock_tranfile2
        LEFT JOIN tranfile1 stock_tranfile1 ON stock_tranfile2.docnum=stock_tranfile1.docnum
        WHERE stock_tranfile2.itmcde=main_tranfile2.itmcde ".$xfilter_current_stock."
    ),0) AS current_stock
    FROM tranfile2 main_tranfile2
    LEFT JOIN tranfile1 main_tranfile1 ON main_tranfile2.docnum=main_tranfile1.docnum
    LEFT JOIN itemfile ON main_tranfile2.itmcde=itemfile.itmcde
    WHERE main_tranfile1.trncde='SAL' ".$xfilter."
    GROUP BY main_tranfile2.itmcde";

$select_db = "SELECT base.itmdsc,
    base.tot_extprc,
    base.tot_itmqty,
    base.sales_past_30,
    base.qty_past_30,
    base.sales_past_60,
    base.sales_past_90,
    base.current_stock,
    base.latest_cost,
    (base.latest_cost * base.current_stock) AS current_inventory_valuation,
    CASE WHEN base.tot_itmqty = 0 THEN 0 ELSE (base.current_stock / base.tot_itmqty) END AS current_stock_qty
    FROM (".$select_db_base.") base".$xorder;

  //  var_dump($select_db);
  //  die();



    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    while($rs_main = $stmt_main->fetch()){

        $xleft = 20;
        $current_stock = (float)$rs_main["current_stock"];
        $latest_cost = (float)$rs_main["latest_cost"];
        $current_inventory_valuation = (float)$rs_main["current_inventory_valuation"];
        $current_stock_qty = (float)$rs_main["current_stock_qty"];

        $item_desc = normalize_item_text($rs_main["itmdsc"]);
        $item_lines = wrap_str_two_lines($item_desc, 120, 9, 20);
        $line_count = count($item_lines);
        $row_height = 15 + (max(1, $line_count) - 1) * 10;
        if(($xtop - $row_height) <= 60){
            $pdf->ezNewPage();
            $xtop = 485;
        }

                $xtop -= 32;
        $row_y = $xtop;
        foreach($item_lines as $item_line_index => $item_line_text){
            $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), $item_line_text, 9, "left");
        }
        if($line_count > 1){
            $xtop -= (($line_count - 1) * 10);
        }
        $pdf->ezPlaceData($col_amount,$row_y,number_format($rs_main["tot_extprc"],"2"),9,"right");
        $pdf->ezPlaceData($col_qty,$row_y,number_format($rs_main["tot_itmqty"],"0"),9,"right");
        $pdf->ezPlaceData($col_sales_30,$row_y,number_format($rs_main["sales_past_30"],"0"),9,"right");
        $pdf->ezPlaceData($col_sales_60,$row_y,number_format($rs_main["sales_past_60"],"0"),9,"right");
        $pdf->ezPlaceData($col_sales_90,$row_y,number_format($rs_main["sales_past_90"],"0"),9,"right");
        $pdf->ezPlaceData($col_current_stock,$row_y,number_format($current_stock,"0"),9,"right");
        $pdf->ezPlaceData($col_cost,$row_y,number_format($latest_cost,"2"),9,"right");
        $pdf->ezPlaceData($col_current_inventory_valuation,$row_y,number_format($current_inventory_valuation,"2"),9,"right");
        $pdf->ezPlaceData($col_current_stock_qty,$row_y,number_format($current_stock_qty,"2"),9,"right");
        $grand_total = $grand_total + $rs_main["tot_extprc"];


        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 485;
        }

    }
        $xleft = 20;
        $pdf->line($xleft, $xtop-22, $line_right, $xtop-22);
        $xtop -= 15;

        $pdf->ezPlaceData($xleft,$xtop,"Total",9,"left");
        $pdf->ezPlaceData($col_amount,$xtop,number_format($grand_total,"2"),9,"right");

    if($is_tab_export){
        $pdf->ezStream($tab_file_type);
    } else {
        $pdf->ezStream();
    }
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

    function wrap_str_two_lines($string,$max_wid,$fsize,$tab_max_chars = 52)
    {
        global $pdf;

        $string = trim((string)$string);
        if($string === ''){
            return array('');
        }

        // Keep full text for tab/xlsx export and let the spreadsheet handle
        // the cell width instead of truncating with ellipses.
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

            $line = fit_text_to_width($remaining, $max_wid, $fsize, false);
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

    function normalize_item_text($string)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        // Fix common mojibake sequences seen in item descriptions.
        $search = array('â€œ', 'â€', 'â€˜', 'â€™', 'â€“', 'â€”', 'Â', '“', '”', '‘', '’', '–', '—');
        $replace = array('"', '"', "'", "'", '-', '-', '', '"', '"', "'", "'", '-', '-');
        $string = str_replace($search, $replace, $string);

        // Keep spacing consistent after replacements.
        $string = preg_replace('/\s+/', ' ', $string);
        return trim($string);
    }

    function fit_text_to_width($string, $max_wid, $fsize, $add_ellipsis = false)
    {
        global $pdf;

        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $limit_wid = $max_wid;
        if($add_ellipsis){
            $limit_wid = $max_wid - $pdf->getTextWidth($fsize, '...');
        }
        if($limit_wid < 1){
            $limit_wid = 1;
        }

        $xarr_str = str_split($string);
        $xxstr = '';
        $xcut = false;
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $limit_wid)
            {
                $xcut = true;
                break;
            }
            $xxstr = $xxstr.$value;
        }

        if($add_ellipsis && $xcut){
            $xxstr = rtrim($xxstr).'...';
        }
        return rtrim($xxstr);
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









