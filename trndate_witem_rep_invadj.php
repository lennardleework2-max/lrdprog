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

    $tab_file_type = 'xlsx';
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    if (!$is_tab_export) {
        ob_start();
    }

    $xreport_title = "List of items";
		

    if (!$is_tab_export) {
		$pdf = new Cezpdf('Letter','landscape');
        $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
	}

		
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

	$xtop = 580;
    $xleft = 25;

    // Column positions for PDF layout (Tran. Date and Item as separate columns)
    $col_docnum = 25;
    $col_ordernum = 80;
    $col_trndte = 150;
    $col_item = 215;
    $item_max_width = 125; // Max width for item text wrapping
    $col_qty = 345;
    $col_uom = 360;
    $col_warehouse = 450;
    $col_unitprice = 600;
    $col_total = 720;

    /**header**/

    //getting header fields
    $fields_count = 0;
    $fields = '';

    if (!$is_tab_export) {
        $pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Inventory Adjustments</b>", 15, 'left' );
        $xtop   -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop   -= 15;

        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop   -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, 760, $xtop+10);
        $pdf->line($xleft, $xtop-3, 760, $xtop-3);


        $xfields_heaeder_counter = 0;

        $pdf->ezPlaceData($col_docnum,$xtop,"<b>Doc. Num.</b>",10,'left');
        $pdf->ezPlaceData($col_ordernum,$xtop,"<b>Order Num.</b>",10,'left');
        $pdf->ezPlaceData($col_trndte,$xtop,"<b>Tran. Date</b>",10,'left');
        $pdf->ezPlaceData($col_item,$xtop,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($col_qty,$xtop,"<b>Qty</b>",10,'right');
        $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",10,'left');
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",10,'left');
        $pdf->ezPlaceData($col_unitprice,$xtop,"<b>Unit Price</b>",10,'right');
        $pdf->ezPlaceData($col_total,$xtop,"<b>Total</b>",10,'right');

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

    // Export to Excel if tab export is selected
    if ($is_tab_export) {
        export_invadj_trndate_witem_xlsx($link, $xfilter, $_POST['trncde_hidden'], $_SESSION['userdesc'], $date_printed);
        exit;
    }

    // if(isset($_POST['cus_search']) && !empty($_POST['cus_search'])){
    //     $xfilter .= " AND customerfile.cusdsc='".$_POST['cus_search']."'";
    // }

    // if(isset($_POST['cus_to']) && !empty($_POST['cus_to'])){
    //     $xfilter .= " AND customerfile.cusdsc<='".$_POST['cus_to']."'";
    // }


        // if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount'] == 'all_amount'){
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'unpaid'){
        //     $xfilter .= " AND (tranfile1.paydate='' OR tranfile1.paydate IS NULL)";
        // }
        // else if(isset($_POST['radio_amount']) && !empty($_POST['radio_amount']) && $_POST['radio_amount']== 'paid'){
        //     $xfilter .= " AND (tranfile1.paydate!='' OR tranfile1.paydate IS NOT NULL)";
        // }

        


    
    // Optimized: Single query with joins instead of nested queries per document
    $combined_sql = "SELECT tranfile2.*, itemfile.itmdsc,
        itemunitmeasurefile.unmdsc AS uom_description,
        warehouse.warehouse_name,
        warehouse_floor.floor_no,
        tranfile1.docnum as tranfile1_docnum, tranfile1.trndte as tranfile1_trndte,
        tranfile1.ordernum as tranfile1_ordernum, tranfile1.trntot as tranfile1_trntot
        FROM tranfile2
        LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
        LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
        LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
        LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
        LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
        WHERE tranfile1.trncde = ? ".$xfilter."
        ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
    $stmt_combined = $link->prepare($combined_sql);
    $stmt_combined->execute(array($_POST['trncde_hidden']));
    $all_rows = $stmt_combined->fetchAll(PDO::FETCH_ASSOC);

    // Group rows by document number
    $grouped_data = array();
    foreach($all_rows as $row){
        $docnum = $row['tranfile1_docnum'];
        if(!isset($grouped_data[$docnum])){
            $grouped_data[$docnum] = array(
                'docnum' => $row['tranfile1_docnum'],
                'trndte' => $row['tranfile1_trndte'],
                'ordernum' => $row['tranfile1_ordernum'],
                'trntot' => $row['tranfile1_trntot'],
                'items' => array()
            );
        }
        $grouped_data[$docnum]['items'][] = $row;
    }

    $grand_total = 0;
    $price_gtot = 0;
    $cost_gtot = 0;
    $profit_gtot = 0;

    foreach($grouped_data as $docnum => $doc_data){    

        $xleft = 25;

        $grand_total += $doc_data["trntot"];

        $trndte_display = '';
        if(isset($doc_data["trndte"]) && !empty($doc_data["trndte"])){
            $trndte_display = date("m/d/Y", strtotime($doc_data["trndte"]));
        }

        $ordernum_display = trim_str($doc_data["ordernum"], 100, 9);


        $pdf->ezPlaceData($col_docnum, $xtop, $doc_data["docnum"], 9, "left");
        $pdf->ezPlaceData($col_ordernum, $xtop, $ordernum_display, 9, "left");
        $pdf->ezPlaceData($col_trndte, $xtop, $trndte_display, 9, "left");

        


        

        if($xtop <= 60){
            $pdf->ezNewPage();
            $xtop = 515;
        }

        $price_tot = 0;
        $cost_tot = 0;
        $profit_tot = 0;
        $xtop -= 12;

        // Iterate through pre-fetched items for this document
        foreach($doc_data['items'] as $rs_main2){

            // Build warehouse display: "warehouse_name floor_no floor"
            $warehouse_display = '';
            if (!empty($rs_main2['warehouse_name'])) {
                $warehouse_display = $rs_main2['warehouse_name'];
                if (!empty($rs_main2['floor_no'])) {
                    $warehouse_display .= ' ' . $rs_main2['floor_no'] . ' floor';
                }
            }

            // Get UOM description
            $uom_display = isset($rs_main2['uom_description']) ? $rs_main2['uom_description'] : '';

            if (!$is_tab_export) {
                // PDF output with text wrapping for Item column
                $item_desc = normalize_item_text($rs_main2["itmdsc"]);
                $item_lines = wrap_str_lines($item_desc, $item_max_width, 9);
                $line_count = count($item_lines);

                // Calculate row height based on wrapped lines
                $row_height = 15 + (max(1, $line_count) - 1) * 10;

                // Check if we need a new page before this row
                if(($xtop - $row_height) <= 60){
                    $pdf->ezNewPage();
                    $xtop = 515;
                }

                $row_y = $xtop;

                // Place each line of wrapped item text
                foreach($item_lines as $item_line_index => $item_line_text){
                    $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), $item_line_text, 9, "left");
                }

                // Place other columns at the first line position
                $pdf->ezPlaceData($col_qty,$row_y,$rs_main2["itmqty"],9,"right");
                $pdf->ezPlaceData($col_uom,$row_y,$uom_display,9,"left");
                $pdf->ezPlaceData($col_warehouse,$row_y,fit_text_to_width($warehouse_display, 140, 9, false),9,"left");
                $pdf->ezPlaceData($col_unitprice,$row_y,number_format($rs_main2["untprc"],"2"),9,"right");
                $pdf->ezPlaceData($col_total,$row_y,number_format($rs_main2["extprc"],"2"),9,"right");

                // Move xtop down by the row height
                $xtop -= $row_height;
            }

            $profit = $rs_main2["extprc"];
            $cost_tot+=$rs_main2["extprc"];
            $profit_tot+=$profit;

            if (!$is_tab_export && $xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }
        }
  
        if (!$is_tab_export) {
            $pdf->line(25, $xtop, 760, $xtop);
            $xtop -= 15;
            $xleft = 0;
            $pdf->ezPlaceData($col_warehouse,$xtop+=5,"<b>TOTAL:</b>",9,"left");
            $pdf->ezPlaceData($col_unitprice,$xtop,number_format($price_tot,2),9,"right");
            $pdf->ezPlaceData($col_total,$xtop,number_format($cost_tot,2),9,"right");
            $pdf->line(25, $xtop-=5, 760, $xtop);
            $xtop -= 15;


            if($xtop <= 60)
            {
                $pdf->ezNewPage();
                $xtop = 515;
            }
        }

        $price_gtot += $price_tot;
        $cost_gtot += $cost_tot;
    }

    if (!$is_tab_export) {
        $pdf->line(25, $xtop, 760, $xtop);
        $xtop -= 15;
        $xleft = 0;
        $pdf->ezPlaceData($col_warehouse - 50,$xtop+=5,"<b>GRAND TOTAL:</b>",8,"left");
        $pdf->ezPlaceData($col_unitprice,$xtop,number_format($price_gtot,2),9,"right");
        $pdf->ezPlaceData($col_total,$xtop,number_format($cost_gtot,2),9,"right");
        $pdf->line(25, $xtop-=5, 760, $xtop);

        $pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
        $pdf->ezStream();
        ob_end_flush();
    }

    function trim_str($string,$max_wid,$fsize)
    {
        global $pdf, $is_tab_export;
        if($is_tab_export)
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

    // Normalize item text - fix encoding issues
    function normalize_item_text($string)
    {
        $string = trim((string)$string);
        if($string === ''){
            return '';
        }

        // Keep spacing consistent
        $string = preg_replace('/\s+/', ' ', $string);
        return trim($string);
    }

    // Wrap text into multiple lines for PDF
    function wrap_str_lines($string, $max_wid, $fsize)
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

            // Try to break at a space for better readability
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

    // Fit text to a specific width
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

    // Excel export function for Inventory Adjustments Date Item report
    function export_invadj_trndate_witem_xlsx($link, $xfilter, $trncde, $report_user, $date_printed)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inv Adj Date Item');

        // Header info
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Inventory Adjustments (Summarized)');
        $sheet->setCellValue('A2', 'Report by: ' . $report_user);
        $sheet->setCellValue('A3', 'Date Printed: ' . $date_printed);

        // Column headers
        $header_row = 5;
        $sheet->fromArray(array(
            'Doc. Num.',
            'Order Num.',
            'Tran. Date',
            'Item',
            'Qty',
            'UOM',
            'Warehouse',
            'Unit Price',
            'Total'
        ), null, 'A' . $header_row);

        // Optimized: Single query with joins instead of nested queries per document
        $combined_sql = "SELECT tranfile2.*, itemfile.itmdsc,
            itemunitmeasurefile.unmdsc AS uom_description,
            warehouse.warehouse_name,
            warehouse_floor.floor_no,
            tranfile1.docnum as tranfile1_docnum, tranfile1.trndte as tranfile1_trndte,
            tranfile1.ordernum as tranfile1_ordernum
            FROM tranfile2
            LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde = itemunitmeasurefile.unmcde
            LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
            LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
            WHERE tranfile1.trncde = ? ".$xfilter."
            ORDER BY tranfile1.docnum ASC, tranfile1.trndte ASC, tranfile2.recid ASC";
        $stmt_combined = $link->prepare($combined_sql);
        $stmt_combined->execute(array($trncde));
        $all_rows = $stmt_combined->fetchAll(PDO::FETCH_ASSOC);

        // Group rows by document number
        $grouped_data = array();
        foreach($all_rows as $row){
            $docnum = $row['tranfile1_docnum'];
            if(!isset($grouped_data[$docnum])){
                $grouped_data[$docnum] = array(
                    'docnum' => $row['tranfile1_docnum'],
                    'trndte' => $row['tranfile1_trndte'],
                    'ordernum' => $row['tranfile1_ordernum'],
                    'items' => array()
                );
            }
            $grouped_data[$docnum]['items'][] = $row;
        }

        $row_num = $header_row + 1;
        $price_gtot = 0;
        $cost_gtot = 0;

        foreach($grouped_data as $docnum => $doc_data){
            $trndte_display = '';
            if(!empty($doc_data["trndte"])){
                $trndte_display = date("m/d/Y", strtotime($doc_data["trndte"]));
            }

            $price_tot = 0;
            $cost_tot = 0;
            $first_item = true;

            // Iterate through pre-fetched items for this document
            foreach($doc_data['items'] as $rs_main2){
                // Build warehouse display
                $warehouse_display = '';
                if (!empty($rs_main2['warehouse_name'])) {
                    $warehouse_display = $rs_main2['warehouse_name'];
                    if (!empty($rs_main2['floor_no'])) {
                        $warehouse_display .= ' ' . $rs_main2['floor_no'] . ' floor';
                    }
                }

                $uom_display = isset($rs_main2['uom_description']) ? $rs_main2['uom_description'] : '';

                // Only show doc/order/date on first item row
                if($first_item){
                    $sheet->setCellValue('A' . $row_num, $doc_data['docnum']);
                    $sheet->setCellValue('B' . $row_num, $doc_data['ordernum']);
                    $sheet->setCellValue('C' . $row_num, $trndte_display);
                    $first_item = false;
                }

                $sheet->setCellValue('D' . $row_num, $rs_main2['itmdsc']);
                $sheet->setCellValue('E' . $row_num, (float)$rs_main2['itmqty']);
                $sheet->setCellValue('F' . $row_num, $uom_display);
                $sheet->setCellValue('G' . $row_num, $warehouse_display);
                $sheet->setCellValue('H' . $row_num, (float)$rs_main2['untprc']);
                $sheet->setCellValue('I' . $row_num, (float)$rs_main2['extprc']);

                $cost_tot += $rs_main2['extprc'];
                $row_num++;
            }

            // Subtotal row
            if($cost_tot > 0){
                $sheet->setCellValue('G' . $row_num, 'TOTAL:');
                $sheet->setCellValue('I' . $row_num, $cost_tot);
                $sheet->getStyle('G' . $row_num . ':I' . $row_num)->getFont()->setBold(true);
                $row_num++;
            }

            $cost_gtot += $cost_tot;
        }

        // Grand total row
        $sheet->setCellValue('G' . $row_num, 'GRAND TOTAL:');
        $sheet->setCellValue('I' . $row_num, $cost_gtot);
        $sheet->getStyle('G' . $row_num . ':I' . $row_num)->getFont()->setBold(true);

        // Styling
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':I' . $row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('E' . ($header_row + 1) . ':E' . $row_num)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . ($header_row + 1) . ':I' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E' . ($header_row + 1) . ':E' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H' . ($header_row + 1) . ':I' . $row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D' . ($header_row + 1) . ':D' . $row_num)->getAlignment()->setWrapText(true);
        $sheet->getStyle('G' . ($header_row + 1) . ':G' . $row_num)->getAlignment()->setWrapText(true);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->freezePane('A6');

        // Clear output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'invadj_date_item_report_' . date('Ymd_His') . '.xlsx';

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