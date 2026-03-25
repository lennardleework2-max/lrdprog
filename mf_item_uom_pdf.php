<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    // Get item context from session
    $itmcde = isset($_SESSION['item_uom_context_id']) ? $_SESSION['item_uom_context_id'] : '';
    $itmdsc = '';

    if($itmcde !== ''){
        $select_db_itm = "SELECT itmdsc FROM itemfile WHERE itmcde=?";
        $stmt_itm = $link->prepare($select_db_itm);
        $stmt_itm->execute(array($itmcde));
        $rs_itm = $stmt_itm->fetch();
        if(!empty($rs_itm)){
            $itmdsc = $rs_itm["itmdsc"];
        }
    }

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
    $xprog_module_log = "ITEM UNIT OF MEASURE";
    $xactivity_log = ($_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".(($_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." for Item: ".$itmdsc;
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    if ($_POST['txt_output_type']=='tab')
    {
        $pdf = new tab_ezpdf('Letter','portrait');
    }
    else
    {
        $pdf = new Cezpdf('Letter','portrait');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM} of {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $xtop = 750;
    $xleft = 40;

    // Header
    $xheader = $pdf->openObject();
    $pdf->saveState();

    $pdf->ezPlaceData($xleft, $xtop, "<b>ITEM UNIT OF MEASURE CONVERSIONS</b>", 16, 'left');
    $xtop -= 20;

    $pdf->ezPlaceData($xleft, $xtop, "<b>Item Code:</b> ".$itmcde, 11, 'left');
    $xtop -= 15;

    $pdf->ezPlaceData($xleft, $xtop, "<b>Item Description:</b> ".$itmdsc, 11, 'left');
    $xtop -= 15;

    $pdf->ezPlaceData($xleft, $xtop, "<b>Printed by:</b> ".$_SESSION['userdesc'], 9, 'left');
    $xtop -= 15;

    $pdf->ezPlaceData($xleft, $xtop, "<b>Date Printed:</b> ".$date_printed, 9, 'left');
    $xtop -= 20;

    // Table header line
    $pdf->setLineStyle(.5);
    $pdf->line($xleft, $xtop+5, 570, $xtop+5);
    $pdf->line($xleft, $xtop-12, 570, $xtop-12);

    // Column headers
    $pdf->ezPlaceData($xleft, $xtop, "<b>Unit of Measure</b>", 10, 'left');
    $pdf->ezPlaceData(350, $xtop, "<b>Conversion</b>", 10, 'right');

    $xtop -= 25;

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    // Fetch unit conversions for this item
    $select_db_main = "SELECT iuf.unmcde, iuf.conversion, iumf.unmdsc
                       FROM itemunitfile iuf
                       LEFT JOIN itemunitmeasurefile iumf ON iuf.unmcde = iumf.unmcde
                       WHERE iuf.itmcde = ?
                       ORDER BY iuf.recid ASC";
    $stmt_main = $link->prepare($select_db_main);
    $stmt_main->execute(array($itmcde));

    while($rs_main = $stmt_main->fetch()){
        $unmdsc = !empty($rs_main['unmdsc']) ? $rs_main['unmdsc'] : $rs_main['unmcde'];
        $conversion = number_format((float)$rs_main['conversion'], 2);

        $pdf->ezPlaceData($xleft, $xtop, $unmdsc, 10, 'left');
        $pdf->ezPlaceData(350, $xtop, $conversion, 10, 'right');

        $xtop -= 15;

        if($xtop <= 60)
        {
            $pdf->ezNewPage();
            $xtop = 720;
        }
    }

    // Footer line
    $pdf->line($xleft, $xtop, 570, $xtop);
    $pdf->addText($xleft, 15, 8, "Date Printed: ".date("F j, Y, g:i A"), $angle=0, $wordspaceadjust=1);
    $pdf->ezStream();
    ob_end_flush();
?>
