<?php
//error_reporting(E_ALL);
//ini_set('display_errors',true);
ini_set('memory_limit',-1);

// DEBUG FLAG: Set to true to enable debug logging, false to disable
// Logs are written to PHP error log (check php_error.log or Apache error.log)
$DEBUG_ROLLING_SALES = false;
    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
	require_once('resources/func_pdf2tab.php');
    require_once('vendor/autoload.php');

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    $xreport_title = "Top Sales Items";

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    if (!$is_tab_export) {
        ob_start();
        $pdf = new Cezpdf('Letter','landscape');
        $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
        $pdf->ezStartPageNumbers(700,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    }

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

    // Rolling 30/60/90-day columns ALWAYS use today's date as the reference (current system date).
    // 30D = today minus 30 days through today
    // 60D = today minus 2 months through today
    // 90D = today minus 3 months through today
    $today_date = date("Y-m-d"); // Always today
    $head_rolling_sales_date = date("m-d-Y"); // For display in header
    $past_30_start = date("Y-m-d", strtotime("-30 days"));
    $past_60_start = date("Y-m-d", strtotime("-2 months"));
    $past_90_start = date("Y-m-d", strtotime("-3 months"));

    // DEBUG: Log rolling date ranges
    if($DEBUG_ROLLING_SALES){
        error_log("=== TOP_SALES_ITEM_PDF DEBUG START ===");
        error_log("[ROLLING_SALES] Today: " . $today_date);
        error_log("[ROLLING_SALES] 30D Window: " . $past_30_start . " to " . $today_date);
        error_log("[ROLLING_SALES] 60D Window: " . $past_60_start . " to " . $today_date);
        error_log("[ROLLING_SALES] 90D Window: " . $past_90_start . " to " . $today_date);
        error_log("[ROLLING_SALES] User Date Filter - From: " . ($date_from_sql ?: 'not set') . " | To: " . ($date_to_sql ?: 'not set'));
    }

    // For cost lookup, use Date To if specified, otherwise today's date
    $latest_cost_date_to = !empty($date_to_sql) ? $date_to_sql : $today_date;

    $pcs_unmcde = '';
    $stmt_pcs = $link->prepare("SELECT unmcde FROM itemunitmeasurefile WHERE LOWER(unmdsc) = 'pcs' LIMIT 1");
    $stmt_pcs->execute();
    $rs_pcs = $stmt_pcs->fetch();
    if($rs_pcs && !empty($rs_pcs['unmcde'])){
        $pcs_unmcde = $rs_pcs['unmcde'];
    }

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



if (!$is_tab_export) {
		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Top Sales Report (by Items)</b>", 15, 'left' );
        $xtop   -= 15;
        $header="<b>Pdf Report by: ".$_SESSION['userdesc']." </b>";

        $pdf->ezPlaceData($xleft, $xtop,$header, 9, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop, "Sales Date Range : ".$head_date_from." to ".$head_date_to, 10, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop, "Rolling Sales As Of : ".$head_rolling_sales_date, 10, 'left' );


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
        $pdf->ezPlaceData($col_qty,$xheader_base_y,"<b>Total Qty</b>",10,'right');
        $pdf->ezPlaceData($col_sales_30,$xheader_base_y,"<b>Sales Qty 30D</b>",9,'right');
        $pdf->ezPlaceData($col_sales_60,$xheader_base_y,"<b>Sales Qty 60D</b>",9,'right');
        $pdf->ezPlaceData($col_sales_90,$xheader_base_y,"<b>Sales Qty 90D</b>",9,'right');
        $pdf->ezPlaceData($col_current_stock,$xheader_base_y,"<b>Current Stock</b>",9,'right');
        $pdf->ezPlaceData($col_cost,$xheader_base_y,"<b>Cost</b>",9,'right');
        $pdf->ezPlaceData($col_current_inventory_valuation,$xheader_base_y,"<b>Current Inventory</b>",7,'right');
        $pdf->ezPlaceData($col_current_inventory_valuation,$xheader_base_y-9,"<b>Valuation</b>",7,'right');
        $pdf->ezPlaceData($col_current_stock_qty,$xheader_base_y,"<b>Current Stock/</b>",7,'right');
        $pdf->ezPlaceData($col_current_stock_qty,$xheader_base_y-9,"<b>Qty</b>",7,'right');


		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');
    }

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


// Rolling sales quantity uses subqueries independent of main date filter - always based on today's date
// Pattern: SELECT SUM(tranfile2.stkqty) * -1 FROM tranfile1 LEFT JOIN tranfile2 WHERE itmcde=X AND trndte>=start AND trndte<=today
$select_db_base="SELECT itemfile.itmdsc as itmdsc,
    main_tranfile2.itmcde as itmcde,
    SUM(main_tranfile2.extprc) as tot_extprc,
    SUM(main_tranfile2.stkqty * -1) as tot_itmqty,
    COALESCE((
        SELECT SUM(t2.stkqty) * -1
        FROM tranfile1 t1
        LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
        WHERE t2.itmcde = main_tranfile2.itmcde
          AND t1.trncde = 'SAL'
          AND t1.trndte >= '".$past_30_start."'
          AND t1.trndte <= '".$today_date."'
    ), 0) AS sales_past_30,
    COALESCE((
        SELECT SUM(t2.itmqty)
        FROM tranfile1 t1
        LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
        WHERE t2.itmcde = main_tranfile2.itmcde
          AND t1.trncde = 'SAL'
          AND t1.trndte >= '".$past_30_start."'
          AND t1.trndte <= '".$today_date."'
    ), 0) AS qty_past_30,
    COALESCE((
        SELECT SUM(t2.stkqty) * -1
        FROM tranfile1 t1
        LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
        WHERE t2.itmcde = main_tranfile2.itmcde
          AND t1.trncde = 'SAL'
          AND t1.trndte >= '".$past_60_start."'
          AND t1.trndte <= '".$today_date."'
    ), 0) AS sales_past_60,
    COALESCE((
        SELECT SUM(t2.stkqty) * -1
        FROM tranfile1 t1
        LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
        WHERE t2.itmcde = main_tranfile2.itmcde
          AND t1.trncde = 'SAL'
          AND t1.trndte >= '".$past_90_start."'
          AND t1.trndte <= '".$today_date."'
    ), 0) AS sales_past_90,
    COALESCE((
        SELECT pur_tranfile2.untprc
        FROM tranfile2 pur_tranfile2
        LEFT JOIN tranfile1 pur_tranfile1 ON pur_tranfile2.docnum=pur_tranfile1.docnum
        WHERE pur_tranfile2.itmcde=main_tranfile2.itmcde
          AND pur_tranfile1.trncde='PUR'
          AND pur_tranfile1.trndte<='".$latest_cost_date_to."'" . ($pcs_unmcde !== '' ? "
          AND pur_tranfile2.unmcde='".$pcs_unmcde."'" : "") . "
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
    base.itmcde,
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



    // DEBUG: Log the generated SQL query
    if($DEBUG_ROLLING_SALES){
        error_log("[ROLLING_SALES] Generated SQL Query:");
        error_log($select_db);
    }

    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    $report_rows = array();

    $debug_sample_count = 0;
    while($rs_main = $stmt_main->fetch()){
        $report_rows[] = $rs_main;
        $grand_total += (float)$rs_main["tot_extprc"];

        // DEBUG: Log first 3 sample items with rolling sales values
        if($DEBUG_ROLLING_SALES && $debug_sample_count < 3){
            error_log("[ROLLING_SALES] Sample Item #" . ($debug_sample_count + 1) . ":");
            error_log("  - Item: " . substr($rs_main["itmdsc"], 0, 50));
            error_log("  - Item Code: " . ($rs_main["itmcde"] ?? 'N/A'));
            error_log("  - Sales Qty 30D: " . $rs_main["sales_past_30"]);
            error_log("  - Sales Qty 60D: " . $rs_main["sales_past_60"]);
            error_log("  - Sales Qty 90D: " . $rs_main["sales_past_90"]);
            error_log("  - Total Amount: " . $rs_main["tot_extprc"]);
            $debug_sample_count++;
        }
    }

    // DEBUG: Log summary
    if($DEBUG_ROLLING_SALES){
        error_log("[ROLLING_SALES] Total rows fetched: " . count($report_rows));
        error_log("[ROLLING_SALES] Grand Total: " . $grand_total);
        error_log("=== TOP_SALES_ITEM_PDF DEBUG END ===");
    }

    // Export to XLSX using PhpSpreadsheet
    if($is_tab_export){
        export_top_sales_item_xlsx(
            $report_rows,
            $_SESSION['userdesc'],
            $head_date_from,
            $head_date_to,
            $head_rolling_sales_date,
            $date_printed,
            $grand_total
        );
        exit;
    }

    // PDF rendering
    foreach($report_rows as $rs_main){
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

    $pdf->ezStream();
    ob_end_flush();

    function trim_str($string,$max_wid,$fsize)
    {
        global $pdf;
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

    function export_top_sales_item_xlsx(
        $rows,
        $report_user,
        $head_date_from,
        $head_date_to,
        $head_latest_sales_date,
        $date_printed,
        $grand_total
    ) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Top Sales Items');

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'Top Sales Report (by Items)');
        $sheet->setCellValue('A2', 'Pdf Report by: ' . $report_user);
        $sheet->setCellValue('A3', 'Sales Date Range : ' . $head_date_from . ' to ' . $head_date_to);
        $sheet->setCellValue('A4', 'Rolling Sales As Of : ' . $head_latest_sales_date);
        $sheet->setCellValue('A5', 'Date Printed : ' . $date_printed);

        $header_row = 7;
        $sheet->fromArray(array(
            'Item',
            'Amount',
            'Total Qty',
            'Sales Qty 30D',
            'Sales Qty 60D',
            'Sales Qty 90D',
            'Current Stock',
            'Cost',
            'Current Inventory Valuation',
            'Current Stock/Qty'
        ), null, 'A' . $header_row);

        $row_num = $header_row + 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $row_num, normalize_item_text($row['itmdsc']));
            $sheet->setCellValue('B' . $row_num, (float) $row['tot_extprc']);
            $sheet->setCellValue('C' . $row_num, (float) $row['tot_itmqty']);
            $sheet->setCellValue('D' . $row_num, (float) $row['sales_past_30']);
            $sheet->setCellValue('E' . $row_num, (float) $row['sales_past_60']);
            $sheet->setCellValue('F' . $row_num, (float) $row['sales_past_90']);
            $sheet->setCellValue('G' . $row_num, (float) $row['current_stock']);
            $sheet->setCellValue('H' . $row_num, (float) $row['latest_cost']);
            $sheet->setCellValue('I' . $row_num, (float) $row['current_inventory_valuation']);
            $sheet->setCellValue('J' . $row_num, (float) $row['current_stock_qty']);
            $row_num++;
        }

        $sheet->setCellValue('A' . $row_num, 'Total');
        $sheet->setCellValue('B' . $row_num, $grand_total);

        $sheet->getStyle('A1:A5')->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':J' . $header_row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':J' . $header_row)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A' . $header_row . ':J' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('B' . ($header_row + 1) . ':B' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('C' . ($header_row + 1) . ':G' . ($row_num - 1))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . ($header_row + 1) . ':I' . ($row_num - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('J' . ($header_row + 1) . ':J' . ($row_num - 1))->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('B' . ($header_row + 1) . ':J' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . ($header_row + 1) . ':A' . $row_num)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A' . $row_num . ':J' . $row_num)->getFont()->setBold(true);

        $sheet->getColumnDimension('A')->setWidth(48);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(22);
        $sheet->getColumnDimension('J')->setWidth(16);
        $sheet->freezePane('A8');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'top_sales_item_report_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }




?>







