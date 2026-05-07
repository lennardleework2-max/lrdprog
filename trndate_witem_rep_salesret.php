<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
	require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');
    require_once('resources/stdfunc100.php');
    require_once('vendor/autoload.php');

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    $xreport_title = "List of items";

    if (!$is_tab_export) {
        ob_start();
		$pdf = new Cezpdf('Letter','landscape');
	}

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    if (!$is_tab_export) {
        $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
        $pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);
    }

	$xtop = 580;
    $xleft = 25;

    /**header**/

    //getting header fields
    $fields_count = 0;
    $fields = '';

    // Column positions for PDF - adjusted to fit UOM and Warehouse columns
    $col_docnum = 25;
    $col_ordernum = 80;
    $col_trndate = 165;
    $col_cusitem = 230;
    $col_qty = 380;
    $col_uom = 395;
    $col_warehouse = 520;
    $col_unitprice = 635;
    $col_total = 745;
    $line_right = 760;

    if (!$is_tab_export) {
		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Sales Return</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;

        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, $line_right, $xtop+10);
        $pdf->line($xleft, $xtop-3, $line_right, $xtop-3);

        $xfields_heaeder_counter = 0;

        $pdf->ezPlaceData($col_docnum,$xtop,"<b>Doc. Num.</b>",9,'left');
        $pdf->ezPlaceData($col_ordernum,$xtop,"<b>Order Num.</b>",9,'left');
        $pdf->ezPlaceData($col_trndate,$xtop,"<b>Tran. Date</b>",9,'left');
        $pdf->ezPlaceData($col_cusitem,$xtop,"<b>Cus./Item</b>",9,'left');
        $pdf->ezPlaceData($col_qty,$xtop,"<b>Qty</b>",9,'right');
        $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",9,'left');
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",9,'left');
        $pdf->ezPlaceData($col_unitprice,$xtop,"<b>Unit Price</b>",9,'right');
        $pdf->ezPlaceData($col_total,$xtop,"<b>Total</b>",9,'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');
    }

	/***header**/

    #region DO YOU LOOP HERE

    $xfilter = '';
    $xorder = '';

    if((isset($_POST['date_from']) && !empty($_POST['date_from'])) &&
    (isset($_POST['date_to']) && !empty($_POST['date_to'])) ){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."' AND tranfile1.trndte<='".$_POST['date_to']."'";
    }
    else if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
    }
    else if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
    }

    if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
        $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
    }

    // Fetch all report data first
    $select_db="SELECT tranfile1.shipto as tranfile1_shipto,tranfile1.cuscde as tranfile1_cuscde,tranfile1.docnum as tranfile1_docnum,
    tranfile1.trndte as tranfile1_trndte,tranfile1.trntot as tranfile1_trntot,tranfile1.orderby as tranfile1_orderby,tranfile1.recid as tranfile1_recid, tranfile1.ordernum as tranfile1_ordernum,
    customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc, tranfile1.paydate as tranfile1_paydate, tranfile1.paydetails as tranfile1_paydetails,
    customerfile.cusdsc, customerfile.cuscde FROM tranfile1 LEFT JOIN customerfile ON
    tranfile1.cuscde = customerfile.cuscde WHERE true AND trncde='".$_POST['trncde_hidden']."' ".$xfilter." ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC";

    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute();

    $report_data = array();
    $grand_total = 0;
    $price_gtot = 0;
    $cost_gtot = 0;

    while($rs_main = $stmt_main->fetch()){
        $grand_total += $rs_main["tranfile1_trntot"];

        $trndte_display = '';
        if(isset($rs_main["tranfile1_trndte"]) && !empty($rs_main["tranfile1_trndte"])){
            $trndte_display = date("m-d-Y",strtotime($rs_main["tranfile1_trndte"]));
            $trndte_display = str_replace('-','/',$trndte_display);
        }

        // Updated query to join itemunitmeasurefile, warehouse, and warehouse_floor
        $select_db2="SELECT tranfile2.*, itemfile.itmdsc,
            COALESCE(itemunitmeasurefile.unmdsc, '') as uom_desc,
            COALESCE(warehouse.warehouse_name, '') as warehouse_name,
            COALESCE(warehouse_floor.floor_no, '') as floor_no
            FROM tranfile2
            LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
            LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            WHERE tranfile2.docnum='".$rs_main['tranfile1_docnum']."'";
        $stmt_main2 = $link->prepare($select_db2);
        $stmt_main2->execute();

        $items = array();
        $price_tot = 0;
        $cost_tot = 0;

        while($rs_main2 = $stmt_main2->fetch()){
            // Build warehouse display string
            $warehouse_display = '';
            if(!empty($rs_main2['warehouse_name'])){
                $warehouse_display = $rs_main2['warehouse_name'];
                if(!empty($rs_main2['floor_no'])){
                    $warehouse_display .= ' ' . $rs_main2['floor_no'] . ' floor';
                }
            }

            $items[] = array(
                'itmdsc' => $rs_main2['itmdsc'],
                'itmqty' => $rs_main2['itmqty'],
                'uom_desc' => $rs_main2['uom_desc'],
                'warehouse_display' => $warehouse_display,
                'untprc' => $rs_main2['untprc'],
                'extprc' => $rs_main2['extprc']
            );

            $price_tot += $rs_main2["untprc"];
            $cost_tot += $rs_main2["extprc"];
        }

        $report_data[] = array(
            'docnum' => $rs_main['tranfile1_docnum'],
            'ordernum' => $rs_main['tranfile1_ordernum'],
            'trndte' => $trndte_display,
            'cusdsc' => $rs_main['customerfile_cusdsc'],
            'items' => $items,
            'price_tot' => $price_tot,
            'cost_tot' => $cost_tot
        );

        $price_gtot += $price_tot;
        $cost_gtot += $cost_tot;
    }

    // Export to Excel using PhpSpreadsheet
    if($is_tab_export){
        export_salesret_trndate_witem_xlsx(
            $report_data,
            $_SESSION['userdesc'],
            $date_printed,
            $price_gtot,
            $cost_gtot
        );
        exit;
    }

    // PDF rendering
    foreach($report_data as $doc_row){
        $xleft = 25;

        // Trim strings for PDF display
        $cusdsc_display = trim_str($doc_row["cusdsc"], 100, 9);
        $ordernum_display = trim_str($doc_row["ordernum"], 70, 9);

        $pdf->ezPlaceData($col_docnum, $xtop, $doc_row["docnum"], 9, "left");
        $pdf->ezPlaceData($col_ordernum, $xtop, $ordernum_display, 9, "left");
        $pdf->ezPlaceData($col_trndate, $xtop, $doc_row["trndte"], 9, "left");
        $pdf->ezPlaceData($col_cusitem, $xtop, $cusdsc_display, 9, "left");

        if($xtop <= 60){
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $xtop -= 12;

        foreach($doc_row['items'] as $item_row){
            // Calculate row height based on longest wrapped text
            $itmdsc_lines = wrap_str_lines($item_row['itmdsc'], 100, 9);
            $warehouse_lines = wrap_str_lines($item_row['warehouse_display'], 90, 9);
            $max_lines = max(count($itmdsc_lines), count($warehouse_lines));
            $row_height = 12 + (max(1, $max_lines) - 1) * 10;

            // Check if we need a new page
            if(($xtop - $row_height) <= 60){
                $pdf->ezNewPage();
                $xtop = 515;
            }

            $row_y = $xtop;

            // Render item description with wrapping
            foreach($itmdsc_lines as $line_idx => $line_text){
                $pdf->ezPlaceData($col_cusitem, $row_y - ($line_idx * 10), $line_text, 9, "left");
            }

            // Qty (right-aligned)
            $pdf->ezPlaceData($col_qty, $row_y, $item_row["itmqty"], 9, "right");

            // UOM (left-aligned)
            $uom_display = trim_str($item_row['uom_desc'], 50, 9);
            $pdf->ezPlaceData($col_uom, $row_y, $uom_display, 9, "left");

            // Warehouse with wrapping
            foreach($warehouse_lines as $line_idx => $line_text){
                $pdf->ezPlaceData($col_warehouse, $row_y - ($line_idx * 10), $line_text, 9, "left");
            }

            // Unit Price (right-aligned)
            $pdf->ezPlaceData($col_unitprice, $row_y, number_format($item_row["untprc"], 2), 9, "right");

            // Total (right-aligned)
            $pdf->ezPlaceData($col_total, $row_y, number_format($item_row["extprc"], 2), 9, "right");

            // Adjust xtop based on row height
            $xtop -= $row_height;
        }

        // Document subtotal line
        // Note: Unit Price is intentionally blank on Total rows (it's a price field, not summable)
        $pdf->line(25, $xtop, $line_right, $xtop);
        $xtop -= 15;
        $pdf->ezPlaceData($col_warehouse, $xtop + 5, "<b>TOTAL:</b>", 9, "left");
        $pdf->ezPlaceData($col_total, $xtop + 5, number_format($doc_row['cost_tot'], 2), 9, "right");
        $pdf->line(25, $xtop, $line_right, $xtop);
        $xtop -= 15;

        if($xtop <= 60){
            $pdf->ezNewPage();
            $xtop = 515;
        }
    }

    // Grand total line
    // Note: Unit Price is intentionally blank on Grand Total row (it's a price field, not summable)
    $pdf->line(25, $xtop, $line_right, $xtop);
    $xtop -= 15;
    $pdf->ezPlaceData($col_warehouse - 30, $xtop + 5, "<b>GRAND TOTAL:</b>", 8, "left");
    $pdf->ezPlaceData($col_total, $xtop + 5, number_format($cost_gtot, 2), 9, "right");
    $pdf->line(25, $xtop, $line_right, $xtop);

    $pdf->addText(30, 15, 8, "Date Printed : " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
    $pdf->ezStream();
    ob_end_flush();

    function trim_str($string, $max_wid, $fsize)
    {
        global $pdf, $is_tab_export;
        if($is_tab_export){
            return $string;
        }
        $string = (string)$string;
        if($string === ''){
            return '';
        }
        $xarr_str = str_split($string);
        $max_wid -= 5;
        $xxstr = "";
        $xcut = false;
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr.$value);
            if($xstr_wid > $max_wid){
                $xcut = true;
                break;
            }
            $xxstr = $xxstr.$value;
        }
        if($xcut){
            $xxstr = $xxstr.'...';
        }
        return $xxstr;
    }

    // Wrap text into multiple lines for PDF
    function wrap_str_lines($string, $max_wid, $fsize)
    {
        global $pdf, $is_tab_export;

        $string = trim((string)$string);
        if($string === ''){
            return array('');
        }

        if($is_tab_export){
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
            if($xstr_wid > $limit_wid){
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
    function dynamic_width($xstr_chk, $xleft, $spaces, $xalign_chk){
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

    // Excel export function
    function export_salesret_trndate_witem_xlsx(
        $report_data,
        $report_user,
        $date_printed,
        $price_gtot,
        $cost_gtot
    ) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Return Date Item');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Sales Return');
        $sheet->setCellValue('A2', 'Report by: ' . $report_user . ' (Summarized)');
        $sheet->setCellValue('A3', 'Date Printed : ' . $date_printed);

        $header_row = 5;
        $sheet->fromArray(array(
            'Doc. Num.',
            'Order Num.',
            'Tran. Date',
            'Customer/Item',
            'Qty',
            'UOM',
            'Warehouse',
            'Unit Price',
            'Total'
        ), null, 'A' . $header_row);

        $row_num = $header_row + 1;

        foreach ($report_data as $doc_row) {
            // Document header row
            $sheet->setCellValue('A' . $row_num, $doc_row['docnum']);
            $sheet->setCellValue('B' . $row_num, $doc_row['ordernum']);
            $sheet->setCellValue('C' . $row_num, $doc_row['trndte']);
            $sheet->setCellValue('D' . $row_num, $doc_row['cusdsc']);
            $row_num++;

            // Item rows
            foreach ($doc_row['items'] as $item) {
                $sheet->setCellValue('D' . $row_num, $item['itmdsc']);
                $sheet->setCellValue('E' . $row_num, (float) $item['itmqty']);
                $sheet->setCellValue('F' . $row_num, $item['uom_desc']);
                $sheet->setCellValue('G' . $row_num, $item['warehouse_display']);
                $sheet->setCellValue('H' . $row_num, (float) $item['untprc']);
                $sheet->setCellValue('I' . $row_num, (float) $item['extprc']);
                $row_num++;
            }

            // Subtotal row (Unit Price intentionally blank - it's a price field, not summable)
            $sheet->setCellValue('G' . $row_num, 'TOTAL:');
            $sheet->setCellValue('I' . $row_num, $doc_row['cost_tot']);
            $sheet->getStyle('G' . $row_num . ':I' . $row_num)->getFont()->setBold(true);
            $row_num++;
        }

        // Grand total row (Unit Price intentionally blank - it's a price field, not summable)
        $sheet->setCellValue('G' . $row_num, 'GRAND TOTAL:');
        $sheet->setCellValue('I' . $row_num, $cost_gtot);
        $sheet->getStyle('G' . $row_num . ':I' . $row_num)->getFont()->setBold(true);

        // Styling
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':I' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('H' . ($header_row + 1) . ':I' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E' . ($header_row + 1) . ':E' . $row_num)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . ($header_row + 1) . ':D' . $row_num)->getAlignment()->setWrapText(true);
        $sheet->getStyle('G' . ($header_row + 1) . ':G' . $row_num)->getAlignment()->setWrapText(true);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->freezePane('A6');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'salesret_trndate_witem_report_' . date('Ymd_His') . '.xlsx';

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
