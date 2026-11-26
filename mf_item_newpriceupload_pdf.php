<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once("ezpdfclass/class/class.ezpdf.php");
    require_once("mf_item_newpriceupload_ajax.php");

    if(!isset($_SESSION['userdesc'])){
        header('location: index.php');
        exit;
    }

    // Log export activity
    $username_session = $_SESSION['userdesc'];
    $username_full_name = '';
    if(isset($_SESSION['recid'])){
        $stmt_session_user = $link->prepare('SELECT full_name FROM users WHERE recid = ?');
        $stmt_session_user->execute(array($_SESSION['recid']));
        $username_full_name = (string)$stmt_session_user->fetchColumn();
    }
    PDO_UserActivityLog($link, $username_session, '', date("Y-m-d H:i:s"), 'ITEMS', 'export_pdf', $username_full_name, 'Exported PDF from Item New Price Upload', 0, '', '', '', '', $username_session, '', '');

    $results = isset($_POST['hiddenUploadData']) ? json_decode($_POST['hiddenUploadData'], true) : array();
    $results = newprice_sort_results(is_array($results) ? $results : array());

    // Item descriptions for the item codes in the results
    $itmcdes = array();
    foreach($results as $result){
        if(!empty($result['itmcde'])){
            $itmcdes = array_merge($itmcdes, explode(', ', $result['itmcde']));
        }
    }
    $itmcdes = array_values(array_unique($itmcdes));
    $item_desc = array();
    if(!empty($itmcdes)){
        $stmt_desc = $link->prepare("SELECT itmcde, itmdsc FROM itemfile WHERE itmcde IN (" . implode(',', array_fill(0, count($itmcdes), '?')) . ")");
        $stmt_desc->execute($itmcdes);
        $item_desc = $stmt_desc->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    ob_start();

    $pdf = new Cezpdf('Letter', 'landscape');
    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
    date_default_timezone_set('Asia/Manila');

    $xleft = 25;
    $xtop = 580;

    $pdf->ezPlaceData($xleft, $xtop, "<b>Item New Price Upload</b>", 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, "<b>Pdf Report by: " . $username_session . "</b>", 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : ' . date("F j, Y h:i:s A"), 10, 'left');
    $xtop -= 25;

    // Column header, repeated on every page
    function newprice_pdf_header($pdf, $xtop)
    {
        $pdf->setLineStyle(.5);
        $pdf->line(25, $xtop + 10, 770, $xtop + 10);
        $pdf->line(25, $xtop - 3, 770, $xtop - 3);
        $pdf->ezPlaceData(25, $xtop, "<b>Item Description</b>", 10, 'left');
        $pdf->ezPlaceData(215, $xtop, "<b>SKU</b>", 10, 'left');
        $pdf->ezPlaceData(355, $xtop, "<b>Status</b>", 10, 'left');
        $pdf->ezPlaceData(410, $xtop, "<b>Details</b>", 10, 'left');
        return $xtop - 15;
    }

    // Splits text into lines that fit the given width (by word; very long words are split by character)
    function newprice_pdf_wrap($pdf, $text, $max_width, $font_size)
    {
        $lines = array();
        $line = '';
        foreach(preg_split('/\s+/', trim((string)$text)) as $word){
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if($pdf->getTextWidth($font_size, $candidate) <= $max_width){
                $line = $candidate;
                continue;
            }
            if($line !== ''){
                $lines[] = $line;
            }
            $line = '';
            foreach(str_split($word) as $char){
                if($line !== '' && $pdf->getTextWidth($font_size, $line . $char) > $max_width){
                    $lines[] = $line;
                    $line = '';
                }
                $line .= $char;
            }
        }
        $lines[] = $line;
        return $lines;
    }

    $xtop = newprice_pdf_header($pdf, $xtop);
    $success_count = 0;
    $failed_count = 0;

    foreach($results as $result){
        $is_success = !empty($result['success']);
        $is_success ? $success_count++ : $failed_count++;

        $itmdsc = '-';
        if(!empty($result['itmcde'])){
            $descs = array();
            foreach(explode(', ', $result['itmcde']) as $code){
                $descs[] = isset($item_desc[$code]) ? $item_desc[$code] : $code;
            }
            $itmdsc = implode(', ', $descs);
        }
        $sku = isset($result['sku']) ? $result['sku'] : '';
        $message = isset($result['message']) ? $result['message'] : '';

        $columns = array(
            25  => newprice_pdf_wrap($pdf, $itmdsc, 185, 9),
            215 => newprice_pdf_wrap($pdf, $sku, 135, 9),
            355 => array($is_success ? 'Success' : 'Failed'),
            410 => newprice_pdf_wrap($pdf, $message, 355, 9),
        );
        $line_count = max(array_map('count', $columns));

        // Start a new page if the whole row does not fit
        if($xtop - ($line_count - 1) * 11 <= 60){
            $pdf->ezNewPage();
            $xtop = newprice_pdf_header($pdf, 570);
        }

        foreach($columns as $x => $lines){
            foreach($lines as $i => $line){
                $pdf->ezPlaceData($x, $xtop - $i * 11, $line, 9, 'left');
            }
        }
        $xtop -= ($line_count - 1) * 11 + 15;
    }

    $pdf->line(25, $xtop + 5, 770, $xtop + 5);
    $xtop -= 10;
    $pdf->ezPlaceData(25, $xtop, "<b>Success: " . $success_count . "     Failed: " . $failed_count . "</b>", 10, 'left');

    $pdf->ezStream();
    ob_end_flush();
?>
