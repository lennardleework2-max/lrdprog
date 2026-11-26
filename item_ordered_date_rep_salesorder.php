<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
	require_once("resources/lx2.pdodb.php");
	require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');
    require_once('vendor/autoload.php');

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Writer\Xls;

    ob_start();

    $xreport_title = "List of items";
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    if ($is_tab_export)
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
    $line_left = 25;
    $line_right = 755;
    $col_ordered_date = 25;
    $col_upload_date = 105;
    $col_platform = 185;
    $col_ordered_by = 290;
    $col_unit_price = 560;
    $col_qty = 620;
    $col_uom = 632;
    $col_ext_price = 748;

    /**header**/
    
    //getting header fields
    $fields_count = 0;
    $fields = '';

        $xheader = $pdf->openObject();
        $pdf->saveState();

        if($is_tab_export){
            $pdf->ezPlaceData($xleft, $xtop, '', 10, 'left' );
        }else{
            $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Order by Item (Ordered Date)</b>", 15, 'left' );
            $xtop   -= 15;
            $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']."</b>", 9, 'left' );
            $xtop   -= 15;
            
            $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
            $xtop   -= 15;
        }

        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($xheader,'all');   

        $xfilter = '';
        $xfilter2  = '';
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
    
        $xfilter2 .= " AND DATE(salesorderfile1.file_created_date)>='".$xdate_from_filter."' AND DATE(salesorderfile1.file_created_date)<='".$xdate_to_filter."'";
    
        if(isset($_POST['item']) && !empty($_POST['item'])){
            $xfilter .= " AND itmcde='".$_POST['item']."'";
        }

		$pdf->setLineStyle(.5);
        $xleft = 25;

        $xdate_from_display = $xdate_from_filter;
        $xdate_from_display = new DateTime($xdate_from_display);
        $xdate_from_display = $xdate_from_display->format('m/d/Y');

        $xdate_to_display = $xdate_to_filter;
        $xdate_to_display = new DateTime($xdate_to_display);
        $xdate_to_display = $xdate_to_display->format('m/d/Y');

        $item_filter_desc = '';
        if(isset($_POST['item']) && !empty($_POST['item'])){
            $select_db_filter = "SELECT * FROM itemfile WHERE itmcde='".$_POST['item']."' ";
            $stmt_main_filter	= $link->prepare($select_db_filter);
            $stmt_main_filter->execute();
            $rs_main_filter = $stmt_main_filter->fetch();
            $item_filter_desc = normalize_report_text(isset($rs_main_filter['itmdsc']) ? $rs_main_filter['itmdsc'] : '');
        }

        $report_groups = build_salesorder_item_ordered_date_report_groups($link, $xfilter, $xfilter2);

        if($is_tab_export){
            export_salesorder_item_ordered_date_xls(
                $report_groups,
                $_SESSION['userdesc'],
                $date_printed,
                $xdate_from_display,
                $xdate_to_display,
                $item_filter_desc
            );
            exit;
        }

        $xheader_first_page = $pdf->openObject();
        $pdf->saveState();

        $pdf->ezPlaceData($xleft,$xtop,"<b>FILTER:</b>",10,'left');
        $xtop-=15; 
          
        $pdf->ezPlaceData($xleft,$xtop,"<b>Date From:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,$xdate_from_display,10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Date To:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=50,$xtop,$xdate_to_display,10,'left');
        $pdf->ezPlaceData($xleft+=60,$xtop,"<b>Item:</b>",10,'left');
        $pdf->ezPlaceData($xleft+=45,$xtop,$item_filter_desc,10,'left');

        $xtop-=15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader_first_page,'add');           

	/***header**/

    $grand_total = 0;
    foreach($report_groups as $group){
        $detail_rows = $group['rows'];
        $xtop = draw_salesorder_item_group_header(
            $group['item_desc'],
            $xtop,
            10,
            $col_ordered_date,
            $col_upload_date,
            $col_platform,
            $col_ordered_by,
            $col_unit_price,
            $col_qty,
            $col_uom,
            $col_ext_price,
            $line_left,
            $line_right
        );

        $subtotal = (float)$group['subtotal'];
        $subtotal_itmqty = (float)$group['subtotal_itmqty'];
        $subtotal_weighted = (float)$group['subtotal_weighted'];
        foreach($detail_rows as $detail_row){   
            $platform_lines = wrap_report_text_lines($detail_row["cusdsc"], 100, 9, 22);
            $ordered_by_lines = wrap_report_text_lines($detail_row["orderby"], 230, 9, 36);
            $uom_lines = wrap_report_text_lines($detail_row["unmdsc"], 110, 9, 18);
            $row_line_count = max(count($platform_lines), count($ordered_by_lines), count($uom_lines));
            $row_height = 15 + ((max(1, $row_line_count) - 1) * 10);

            if(($xtop - $row_height) <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 530;
                $xtop = draw_salesorder_item_group_header(
                    $group['item_desc'],
                    $xtop,
                    10,
                    $col_ordered_date,
                    $col_upload_date,
                    $col_platform,
                    $col_ordered_by,
                    $col_unit_price,
                    $col_qty,
                    $col_uom,
                    $col_ext_price,
                    $line_left,
                    $line_right
                );
            }

            $row_y = $xtop;
            $grand_total += (float)$detail_row["extprc"];

            $pdf->ezPlaceData($col_ordered_date,$row_y, $detail_row['ordered_date'],9,"left");
            $pdf->ezPlaceData($col_upload_date,$row_y, $detail_row['trndte'],9,"left");
            foreach($platform_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_platform, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($ordered_by_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_ordered_by, $row_y - ($line_index * 10), $line_text, 9, "left");
            }
            foreach($uom_lines as $line_index => $line_text){
                $pdf->ezPlaceData($col_uom, $row_y - ($line_index * 10), $line_text, 9, "left");
            }

            $pdf->ezPlaceData($col_unit_price,$row_y,number_format((float)$detail_row["untprc"],2),9,"right");
            $pdf->ezPlaceData($col_qty,$row_y,number_format((float)$detail_row["itmqty"],0),9,"right");
            $pdf->ezPlaceData($col_ext_price,$row_y,number_format((float)$detail_row["extprc"],2),9,"right");
            $xtop -= $row_height;

        }

        if(($xtop - 20) <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;
            $xtop = draw_salesorder_item_group_header(
                $group['item_desc'],
                $xtop,
                10,
                $col_ordered_date,
                $col_upload_date,
                $col_platform,
                $col_ordered_by,
                $col_unit_price,
                $col_qty,
                $col_uom,
                $col_ext_price,
                $line_left,
                $line_right
            );
        }

        $pdf->line($line_left, $xtop, $line_right, $xtop);
        $pdf->ezPlaceData($col_ordered_by,$xtop-9,"<b>Weighted Average/Subtotal:</b>",9 ,'left');
        $pdf->ezPlaceData($col_unit_price,$xtop-9,"<b>".number_format($subtotal_weighted,2)."</b>",9 ,'right');
        $pdf->ezPlaceData($col_qty,$xtop-9,"<b>".number_format($subtotal_itmqty,0)."</b>",9 ,'right');
        $pdf->ezPlaceData($col_ext_price,$xtop-9,"<b>".number_format($subtotal,2)."</b>",9 ,'right');

        $xtop-=20;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 530;
        }
   
    }
       
    $pdf->line($line_left, $xtop-10, $line_right, $xtop-10);
    $pdf->ezPlaceData($col_ordered_by,$xtop-18,"<b>Grand total:</b>",9 ,'left');
    $pdf->ezPlaceData($col_ext_price,$xtop-18,"<b>".number_format($grand_total,2)."</b>",9 ,'right');
    $pdf->line($line_left, $xtop-10, $line_right, $xtop-10); 
	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

    function draw_salesorder_item_group_header(
        $item_desc,
        $xtop,
        $item_font_size,
        $col_ordered_date,
        $col_upload_date,
        $col_platform,
        $col_ordered_by,
        $col_unit_price,
        $col_qty,
        $col_uom,
        $col_ext_price,
        $line_left,
        $line_right
    ){
        global $pdf;

        $item_lines = wrap_report_text_lines($item_desc, 680, $item_font_size, 72);
        $item_line_count = count($item_lines);
        $item_y = $xtop - 9;

        $pdf->ezPlaceData(25, $item_y, "<b>Item:</b>", $item_font_size, 'left');
        foreach($item_lines as $line_index => $line_text){
            $pdf->ezPlaceData(55, $item_y - ($line_index * 10), $line_text, $item_font_size, 'left');
        }

        $line_y = $xtop - (12 + (($item_line_count - 1) * 10));
        $pdf->line($line_left, $line_y, $line_right, $line_y);

        $xtop -= 12 + (($item_line_count - 1) * 10);
        $header_y = $xtop - 9;

        $pdf->ezPlaceData($col_ordered_date,$header_y,"<b>Ordered Date</b>",9 ,'left');
        $pdf->ezPlaceData($col_upload_date,$header_y,"<b>Upload Date</b>",9 ,'left');
        $pdf->ezPlaceData($col_platform,$header_y,"<b>Platform</b>",9 ,'left');
        $pdf->ezPlaceData($col_ordered_by,$header_y,"<b>Ordered By</b>",9 ,'left');
        $pdf->ezPlaceData($col_unit_price,$header_y,"<b>Unit Price</b>",9 ,'right');
        $pdf->ezPlaceData($col_qty,$header_y,"<b>Quantity</b>",9 ,'right');
        $pdf->ezPlaceData($col_uom,$header_y,"<b>UOM</b>",9 ,'left');
        $pdf->ezPlaceData($col_ext_price,$header_y,"<b>Extended Price</b>",9 ,'right');
        $pdf->line($line_left, $xtop-12, $line_right, $xtop-12);

        return $xtop - 23;
    }

    function trim_str($string,$max_wid,$fsize)
    {   
        global $pdf;
        if(  get_class($pdf) == 'tab_ezpdf')
        {
            return $string;
        }
        return fit_text_to_width((string)$string, $max_wid - 5, $fsize, true);
    }

    function wrap_report_text_lines($string,$max_wid,$fsize,$tab_max_chars = 48)
    {
        global $pdf;

        $string = normalize_report_text($string);
        if($string === ''){
            return array('');
        }

        if(get_class($pdf) === 'tab_ezpdf'){
            return array(trim_text_by_chars($string, $tab_max_chars));
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

    function trim_text_by_chars($string, $max_chars = 48)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        if(strlen($string) <= $max_chars){
            return $string;
        }

        $cut = rtrim(substr($string, 0, $max_chars));
        $last_space = strrpos($cut, ' ');
        if($last_space !== false && $last_space > 0){
            $cut = rtrim(substr($cut, 0, $last_space));
        }

        return $cut.'...';
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
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr.$value);
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

    function normalize_report_text($string)
    {
        if($string === null){
            return '';
        }

        $string = (string)$string;

        if($string === ''){
            return '';
        }

        if(function_exists('mb_detect_encoding')){
            $encoding = mb_detect_encoding($string, array('UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'), true);
            if($encoding !== false && $encoding !== 'UTF-8'){
                $string = mb_convert_encoding($string, 'UTF-8', $encoding);
            }else if(function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')){
                $string = mb_convert_encoding($string, 'UTF-8', 'Windows-1252');
            }
        }else if(function_exists('iconv')){
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $string);
            if($converted !== false){
                $string = $converted;
            }
        }

        if(function_exists('iconv')){
            $normalized = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
            if($normalized !== false){
                $string = $normalized;
            }
        }

        $search = array('ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“', 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â', 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ', 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢', 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ', 'ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â', 'ÃƒÆ’Ã¢â‚¬Å¡', 'ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ', 'ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â', 'ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“', 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢', 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“', 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â');
        $replace = array('"', '"', "'", "'", '-', '-', '', '"', '"', "'", "'", '-', '-');
        $string = str_replace($search, $replace, $string);
        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $string);
        $string = preg_replace('/\s{2,}/u', ' ', $string);

        return trim($string);
    }

    // XLS-safe text encoding: sanitizes text for XLS output
    // Handles mojibake, special chars, and non-ASCII that can break Excel layout
    function xls_safe_text($string)
    {
        $string = (string)$string;

        if(empty($string)){
            return '';
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

        // Remove tabs, line breaks, and control chars that break format
        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return trim($string);
    }

    function fetch_salesorder_item_ordered_date_rows($link, $itmcde, $xfilter2)
    {
        $detail_rows = array();

        $select_db2 = "SELECT salesorderfile2.itmqty,
                              salesorderfile2.untprc,
                              salesorderfile2.extprc,
                              salesorderfile2.docnum,
                              salesorderfile2.unmcde,
                              salesorderfile1.file_created_date as ordered_date_raw,
                              salesorderfile1.trndte as upload_date_raw,
                              salesorderfile1.orderby,
                              customerfile.cusdsc,
                              itemunitmeasurefile.unmdsc
                       FROM salesorderfile2
                       LEFT JOIN salesorderfile1 ON salesorderfile2.docnum = salesorderfile1.docnum
                       LEFT JOIN customerfile ON salesorderfile1.cuscde = customerfile.cuscde
                       LEFT JOIN itemunitmeasurefile ON salesorderfile2.unmcde = itemunitmeasurefile.unmcde
                       WHERE salesorderfile2.itmcde='".$itmcde."' ".$xfilter2."
                       ORDER BY salesorderfile1.trndte ASC, salesorderfile2.recid ASC";
        $stmt_main2 = $link->prepare($select_db2);
        $stmt_main2->execute();

        while($rs_main2 = $stmt_main2->fetch()){
            $ordered_date = null;
            if(!empty($rs_main2['ordered_date_raw'])){
                $date_file_created = new DateTime($rs_main2['ordered_date_raw']);
                $ordered_date = $date_file_created->format('m/d/Y');
            }

            $upload_date = '';
            if(isset($rs_main2["upload_date_raw"]) && !empty($rs_main2["upload_date_raw"])){
                $upload_date = date("m-d-Y",strtotime($rs_main2["upload_date_raw"]));
                $upload_date = str_replace('-','/',$upload_date);
            }

            $detail_rows[] = array(
                'ordered_date' => normalize_report_text($ordered_date),
                'trndte' => $upload_date,
                'docnum' => isset($rs_main2['docnum']) ? $rs_main2['docnum'] : '',
                'cusdsc' => normalize_report_text(isset($rs_main2['cusdsc']) ? $rs_main2['cusdsc'] : ''),
                'orderby' => normalize_report_text(isset($rs_main2['orderby']) ? $rs_main2['orderby'] : ''),
                'untprc' => (float)$rs_main2['untprc'],
                'itmqty' => (float)$rs_main2['itmqty'],
                'extprc' => (float)$rs_main2['extprc'],
                'unmdsc' => normalize_report_text(isset($rs_main2['unmdsc']) ? $rs_main2['unmdsc'] : '')
            );
        }

        return $detail_rows;
    }

    function build_salesorder_item_ordered_date_report_groups($link, $xfilter, $xfilter2)
    {
        $groups = array();

        $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter." ORDER BY itmdsc ASC";
        $stmt_main = $link->prepare($select_db);
        $stmt_main->execute();

        while($rs_main = $stmt_main->fetch()){
            $detail_rows = fetch_salesorder_item_ordered_date_rows($link, $rs_main['itmcde'], $xfilter2);
            if(empty($detail_rows)){
                continue;
            }

            $subtotal = 0;
            $subtotal_itmqty = 0;
            foreach($detail_rows as $detail_row){
                $subtotal += (float)$detail_row['extprc'];
                $subtotal_itmqty += (float)$detail_row['itmqty'];
            }

            $groups[] = array(
                'item_desc' => normalize_report_text(isset($rs_main['itmdsc']) ? $rs_main['itmdsc'] : ''),
                'rows' => $detail_rows,
                'subtotal' => $subtotal,
                'subtotal_itmqty' => $subtotal_itmqty,
                'subtotal_weighted' => ($subtotal_itmqty != 0) ? ($subtotal / $subtotal_itmqty) : 0
            );
        }

        return $groups;
    }

    function export_salesorder_item_ordered_date_xls($groups, $report_user, $date_printed, $date_from_display, $date_to_display, $item_filter_desc)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Order Item');

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Sales Order by Item (Ordered Date)');
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Pdf Report by: ' . xls_safe_text($report_user));
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', 'Date Printed : ' . xls_safe_text($date_printed));

        $sheet->mergeCells('A5:H5');
        $sheet->setCellValue('A5', 'FILTER:');
        $sheet->mergeCells('A6:B6');
        $sheet->setCellValue('A6', 'Date From: ' . xls_safe_text($date_from_display));
        $sheet->mergeCells('C6:D6');
        $sheet->setCellValue('C6', 'Date To: ' . xls_safe_text($date_to_display));
        $sheet->setCellValue('E6', 'Item:');
        $sheet->mergeCells('F6:H6');
        $sheet->setCellValue('F6', xls_safe_text($item_filter_desc));

        $header_labels = array(
            'Ordered Date',
            'Upload Date',
            'Platform',
            'Ordered By',
            'Unit Price',
            'Quantity',
            'UOM',
            'Extended Price'
        );

        $row_num = 8;
        $grand_total = 0;

        foreach($groups as $group){
            $sheet->setCellValue('A' . $row_num, 'Item:');
            $sheet->mergeCells('B' . $row_num . ':H' . $row_num);
            $sheet->setCellValue('B' . $row_num, xls_safe_text($group['item_desc']));
            $sheet->getStyle('A' . $row_num . ':H' . $row_num)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_num . ':H' . $row_num)->getAlignment()->setWrapText(true);
            $row_num++;

            $sheet->fromArray($header_labels, null, 'A' . $row_num);
            $sheet->getStyle('A' . $row_num . ':H' . $row_num)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row_num . ':H' . $row_num)->getAlignment()->setWrapText(true);
            $row_num++;

            foreach($group['rows'] as $detail_row){
                $sheet->setCellValue('A' . $row_num, xls_safe_text($detail_row['ordered_date']));
                $sheet->setCellValue('B' . $row_num, xls_safe_text($detail_row['trndte']));
                $sheet->setCellValue('C' . $row_num, xls_safe_text($detail_row['cusdsc']));
                $sheet->setCellValue('D' . $row_num, xls_safe_text($detail_row['orderby']));
                $sheet->setCellValue('E' . $row_num, (float)$detail_row['untprc']);
                $sheet->setCellValue('F' . $row_num, (float)$detail_row['itmqty']);
                $sheet->setCellValue('G' . $row_num, xls_safe_text($detail_row['unmdsc']));
                $sheet->setCellValue('H' . $row_num, (float)$detail_row['extprc']);
                $row_num++;
            }

            $sheet->setCellValue('E' . $row_num, 'Weighted Average/Subtotal');
            $sheet->setCellValue('F' . $row_num, (float)$group['subtotal_weighted']);
            $sheet->setCellValue('G' . $row_num, (float)$group['subtotal_itmqty']);
            $sheet->setCellValue('H' . $row_num, (float)$group['subtotal']);
            $sheet->getStyle('E' . $row_num . ':H' . $row_num)->getFont()->setBold(true);
            $grand_total += (float)$group['subtotal'];
            $row_num += 2;
        }

        $sheet->setCellValue('G' . $row_num, 'Grand Total');
        $sheet->setCellValue('H' . $row_num, $grand_total);
        $sheet->getStyle('G' . $row_num . ':H' . $row_num)->getFont()->setBold(true);

        $sheet->getStyle('A1:H6')->getFont()->setBold(true);
        $sheet->getStyle('A1:H' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A8:D' . $row_num)->getAlignment()->setWrapText(true);
        $sheet->getStyle('E8:F' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H8:H' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E8:E' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('F8:F' . $row_num)->getNumberFormat()->setFormatCode('#,##0.####');
        $sheet->getStyle('H8:H' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(16);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="item_ordered_date_rep_salesorder.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
    }


?>
