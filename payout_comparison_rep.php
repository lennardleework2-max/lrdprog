<?php
    // Payout Comparison report (PDF / XLS): sales upload price (tranfile2.untprc_before_payout)
    // vs payout price (tranfile2.untprc) per sales order line. Filters come from payout_comparison.php.

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Writer\Xls;

    // Only lines that went through a payout (untprc_before_payout NULL or empty = not included).
    // $date_from / $date_to are Y-m-d or '' and filter tranfile1.trndte; $itmcdes is an array of item codes (empty = all items).
    function payout_comparison_fetch_rows($link, $date_from, $date_to, $itmcdes)
    {
        $sql = "SELECT tranfile1.ordernum,
                       itemfile.itmdsc,
                       tranfile2.untprc_before_payout,
                       tranfile2.untprc
                FROM tranfile2
                INNER JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
                LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
                WHERE tranfile1.trncde = 'SAL'
                  AND NULLIF(TRIM(tranfile2.untprc_before_payout), '') IS NOT NULL";
        $params = array();

        if($date_from !== ''){
            $sql .= " AND tranfile1.trndte >= ?";
            $params[] = $date_from;
        }
        if($date_to !== ''){
            $sql .= " AND tranfile1.trndte <= ?";
            $params[] = $date_to;
        }
        if(!empty($itmcdes)){
            $sql .= " AND tranfile2.itmcde IN (".implode(',', array_fill(0, count($itmcdes), '?')).")";
            $params = array_merge($params, $itmcdes);
        }
        $sql .= " ORDER BY tranfile1.trndte ASC, tranfile1.ordernum ASC, tranfile2.recid ASC";

        $stmt = $link->prepare($sql);
        $stmt->execute($params);

        $rows = array();
        while($rs = $stmt->fetch(PDO::FETCH_ASSOC)){
            $before = (float)$rs['untprc_before_payout'];
            $after = (float)$rs['untprc'];
            $rows[] = array(
                'ordernum' => payout_comparison_clean_text($rs['ordernum']),
                'item' => payout_comparison_clean_text($rs['itmdsc']),
                'before' => $before,
                'after' => $after,
                'diff' => round($before - $after, 2),
            );
        }

        return $rows;
    }

    // Posted item[] (or a single value) -> trimmed, unique, non-empty item codes.
    function payout_comparison_item_codes($input)
    {
        $codes = array();
        foreach((is_array($input) ? $input : array($input)) as $code){
            if(is_scalar($code) && trim((string)$code) !== ''){
                $codes[] = trim((string)$code);
            }
        }

        return array_values(array_unique($codes));
    }

    // ezPlaceData parses <b>-style markup, so angle brackets are stripped from data text.
    function payout_comparison_clean_text($string)
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(array('<', '>'), '', (string)$string)));
    }

    function payout_comparison_totals($rows)
    {
        $totals = array('before' => 0, 'after' => 0, 'diff' => 0);
        foreach($rows as $row){
            $totals['before'] += $row['before'];
            $totals['after'] += $row['after'];
            $totals['diff'] += $row['diff'];
        }

        return array_map(function($value){ return round($value, 2); }, $totals);
    }

    function payout_comparison_wrap_lines($pdf, $string, $max_wid, $fsize)
    {
        $lines = array();
        $current = '';

        foreach(explode(' ', $string) as $word){
            // hard-split a single word that is wider than the column
            while(strlen($word) > 1 && $pdf->getTextWidth($fsize, $word) > $max_wid){
                $cut = strlen($word) - 1;
                while($cut > 1 && $pdf->getTextWidth($fsize, substr($word, 0, $cut)) > $max_wid){
                    $cut--;
                }
                if($current !== ''){
                    $lines[] = $current;
                    $current = '';
                }
                $lines[] = substr($word, 0, $cut);
                $word = substr($word, $cut);
            }

            $candidate = ($current === '') ? $word : $current.' '.$word;
            if($current === '' || $pdf->getTextWidth($fsize, $candidate) <= $max_wid){
                $current = $candidate;
            }else{
                $lines[] = $current;
                $current = $word;
            }
        }
        $lines[] = $current;

        return $lines;
    }

    // Column x positions: text columns are left aligned, price columns are right aligned.
    function payout_comparison_draw_columns($pdf, $xtop)
    {
        $pdf->line(25, $xtop, 755, $xtop);
        $header_y = $xtop - 9;
        $pdf->ezPlaceData(25, $header_y, "<b>Order ID</b>", 9, 'left');
        $pdf->ezPlaceData(150, $header_y, "<b>Item</b>", 9, 'left');
        $pdf->ezPlaceData(570, $header_y, "<b>Sales Upload Price</b>", 9, 'right');
        $pdf->ezPlaceData(660, $header_y, "<b>Payout Price</b>", 9, 'right');
        $pdf->ezPlaceData(755, $header_y, "<b>Difference</b>", 9, 'right');
        $pdf->line(25, $xtop - 12, 755, $xtop - 12);

        return $xtop - 23;
    }

    function payout_comparison_build_pdf($pdf, $rows, $report_user, $date_printed)
    {
        $pdf->selectFont(__DIR__."/ezpdfclass/fonts/Helvetica.afm");
        $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);

        $xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData(25, 580, "<b>Payout Comparison</b>", 15, 'left');
        $pdf->ezPlaceData(25, 565, "<b>Pdf Report by: ".$report_user."</b>", 9, 'left');
        $pdf->ezPlaceData(25, 550, 'Date Printed : '.$date_printed, 10, 'left');
        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($xheader, 'all');

        if(empty($rows)){
            $pdf->ezPlaceData(25, 520, 'No records found.', 9, 'left');
            return;
        }

        $xtop = payout_comparison_draw_columns($pdf, 535);

        foreach($rows as $row){
            // item column runs from x=150 and must end before the Sales Upload Price header (~x=480)
            $item_lines = payout_comparison_wrap_lines($pdf, $row['item'], 320, 9);
            $row_height = 15 + ((count($item_lines) - 1) * 10);

            if(($xtop - $row_height) <= 60){
                $pdf->ezNewPage();
                $xtop = payout_comparison_draw_columns($pdf, 530);
            }

            $pdf->ezPlaceData(25, $xtop, $row['ordernum'], 9, 'left');
            foreach($item_lines as $line_index => $line_text){
                $pdf->ezPlaceData(150, $xtop - ($line_index * 10), $line_text, 9, 'left');
            }
            $pdf->ezPlaceData(570, $xtop, number_format($row['before'], 2), 9, 'right');
            $pdf->ezPlaceData(660, $xtop, number_format($row['after'], 2), 9, 'right');
            $pdf->ezPlaceData(755, $xtop, number_format($row['diff'], 2), 9, 'right');

            $xtop -= $row_height;
        }

        if(($xtop - 20) <= 60){
            $pdf->ezNewPage();
            $xtop = payout_comparison_draw_columns($pdf, 530);
        }

        $totals = payout_comparison_totals($rows);
        $pdf->line(25, $xtop, 755, $xtop);
        $pdf->ezPlaceData(150, $xtop - 9, "<b>Grand total:</b>", 9, 'left');
        $pdf->ezPlaceData(570, $xtop - 9, "<b>".number_format($totals['before'], 2)."</b>", 9, 'right');
        $pdf->ezPlaceData(660, $xtop - 9, "<b>".number_format($totals['after'], 2)."</b>", 9, 'right');
        $pdf->ezPlaceData(755, $xtop - 9, "<b>".number_format($totals['diff'], 2)."</b>", 9, 'right');
        $pdf->line(25, $xtop - 12, 755, $xtop - 12);
    }

    function payout_comparison_build_sheet($rows, $report_user, $date_printed)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payout Comparison');

        $sheet->setCellValue('A1', 'Payout Comparison');
        $sheet->setCellValue('A2', 'Pdf Report by: '.$report_user);
        $sheet->setCellValue('A3', 'Date Printed : '.$date_printed);
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);

        $sheet->fromArray(array('Order ID', 'Item', 'Sales Upload Price', 'Payout Price', 'Difference'), null, 'A5');
        $sheet->getStyle('A5:E5')->getFont()->setBold(true);

        $row_num = 6;
        foreach($rows as $row){
            // explicit string so order ids that look numeric keep their leading zeros
            $sheet->setCellValueExplicit('A'.$row_num, $row['ordernum'], DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$row_num, $row['item']);
            $sheet->setCellValue('C'.$row_num, $row['before']);
            $sheet->setCellValue('D'.$row_num, $row['after']);
            $sheet->setCellValue('E'.$row_num, $row['diff']);
            $row_num++;
        }

        if(!empty($rows)){
            $totals = payout_comparison_totals($rows);
            $sheet->setCellValue('B'.$row_num, 'Grand total:');
            $sheet->setCellValue('C'.$row_num, $totals['before']);
            $sheet->setCellValue('D'.$row_num, $totals['after']);
            $sheet->setCellValue('E'.$row_num, $totals['diff']);
            $sheet->getStyle('B'.$row_num.':E'.$row_num)->getFont()->setBold(true);
        }

        $sheet->getStyle('C6:E'.$row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('C5:E'.$row_num)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B6:B'.$row_num)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:E'.$row_num)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);

        return $spreadsheet;
    }

    // Lets tests/test_payout_comparison_rep.php load the functions above without a DB, session or output.
    if(defined('PAYOUT_COMPARISON_LIB_ONLY')){
        return;
    }

    session_start();

    if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
        header('location: index.php');
        exit;
    }

    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('vendor/autoload.php');

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    // date picker sends MM/DD/YYYY
    $date_from = !empty($_POST['date_from']) ? date("Y-m-d", strtotime($_POST['date_from'])) : '';
    $date_to = !empty($_POST['date_to']) ? date("Y-m-d", strtotime($_POST['date_to'])) : '';
    $itmcdes = payout_comparison_item_codes(isset($_POST['item']) ? $_POST['item'] : array());

    // BEAVER debug log - filters, counts and docnums only
    $beaver_log = function($label, $data = null){
        @file_put_contents(__DIR__.'/debug_beaver_payout.log',
            date('Y-m-d H:i:s').' [BEAVER_PAYOUT_REPORT] '.$label.($data === null ? '' : ' '.json_encode($data)).PHP_EOL,
            FILE_APPEND | LOCK_EX);
    };
    $beaver_log('---- START report');
    $beaver_log('R1 raw input', array('date_from' => isset($_POST['date_from']) ? $_POST['date_from'] : null, 'date_to' => isset($_POST['date_to']) ? $_POST['date_to'] : null, 'item' => isset($_POST['item']) ? $_POST['item'] : null));
    $beaver_log('R2 cleaned filters', array('date_from' => $date_from, 'date_to' => $date_to, 'itmcdes' => $itmcdes));
    try {
        $beaver_log('R3 column definition', $link->query("SHOW COLUMNS FROM tranfile2 LIKE 'untprc_before_payout'")->fetch(PDO::FETCH_ASSOC));
        $beaver_log('R4 tranfile2 value shapes', $link->query("SELECT COUNT(*) total,
                SUM(untprc_before_payout IS NULL) is_null,
                SUM(untprc_before_payout IS NOT NULL AND TRIM(untprc_before_payout) = '') is_blank,
                SUM(NULLIF(TRIM(untprc_before_payout), '') IS NOT NULL) has_value
            FROM tranfile2")->fetch(PDO::FETCH_ASSOC));
        $beaver_log('R5 lines with a payout value, by trncde / trndte range', $link->query("SELECT t1.trncde, COUNT(*) line_count, MIN(t1.trndte) min_trndte, MAX(t1.trndte) max_trndte, MIN(t1.docnum) sample_docnum
            FROM tranfile2 t2 INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
            WHERE NULLIF(TRIM(t2.untprc_before_payout), '') IS NOT NULL
            GROUP BY t1.trncde")->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {
        $beaver_log('R3-R5 diagnostics FAILED', $e->getMessage());
    }

    $rows = payout_comparison_fetch_rows($link, $date_from, $date_to, $itmcdes);
    $beaver_log('R6 report rows returned', count($rows));

    if(isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab'){
        $spreadsheet = payout_comparison_build_sheet($rows, $_SESSION['userdesc'], $date_printed);

        while(ob_get_level() > 0){
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="payout_comparison.xls"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    ob_start();

    $pdf = new Cezpdf('Letter', 'landscape');
    payout_comparison_build_pdf($pdf, $rows, $_SESSION['userdesc'], $date_printed);
    $pdf->addText(30, 15, 8, "Date Printed : ".date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
    $pdf->ezStream();

    ob_end_flush();
?>
