<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

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
    $xprog_module_log = isset($_POST["main_header_hidden"]) ? strtoupper($_POST["main_header_hidden"]) : '';
    $xactivity_log = ($_POST['txt_output_type']=='tab') ? 'export_txt' : 'export_pdf';
    $xremarks_log = "Exported ".(($_POST['txt_output_type']=='tab') ? 'TXT' : 'PDF')." from ".$_POST["main_header_hidden"];
    PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

    ob_start();

    if ($_POST['txt_output_type']=='tab'){
        $pdf = new tab_ezpdf('Letter','landscape');
    }else{
        $pdf = new Cezpdf('Letter','landscape');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(700,15,8,'right','Page {PAGENUM} of {TOTALPAGENUM}',1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $field_map = array();
    $fields = '';
    $fields_count = 0;
    foreach($_POST["fields"] as $arr_key => $arr_val){
        if($fields_count == 0){
            $fields = $arr_val["fname"];
        }else{
            $fields .= ",".$arr_val["fname"];
        }

        if($arr_val["fname"] == $_POST['search_hidden_dd']){
            $search_dd_field = $_POST["search_hidden_dd"];
            $_POST["search_hidden_dd"] = $arr_val["fheader"];
        }

        $field_map[$arr_val["fname"]] = $arr_val;
        $fields_count++;
    }
    $fields .= ",recid";

    $column_widths = array(
        'usrname' => 90,
        'fullname' => 115,
        'usrdte' => 85,
        'usrtim' => 60,
        'activity' => 80,
        'remarks' => 290
    );

    $xleft = 25;
    $xtop = 580;
    $line_right = 770;
    $column_positions = array();
    $current_x = $xleft;
    foreach($_POST["fields"] as $field_config){
        $field_name = $field_config["fname"];
        $column_positions[$field_name] = $current_x;
        $current_x += isset($column_widths[$field_name]) ? $column_widths[$field_name] : 115;
    }

    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, "<b>".$_POST["main_header_hidden"]."</b>", 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, "<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left');
    $xtop -= 15;

    if((isset($_POST["search_hidden_value"]) && !empty($_POST["search_hidden_value"])) && isset($_POST['search_hidden_dd'])){
        $pdf->ezPlaceData($xleft, $xtop, $_POST['search_hidden_dd'].":", 9, 'left');
        $pdf->ezPlaceData($xleft + 75, $xtop, $_POST['search_hidden_value'], 9, 'left');
        $xtop -= 15;
    }

    $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left');
    $xtop -= 18;

    $pdf->setLineStyle(.5);
    $pdf->line($xleft, $xtop+10, $line_right, $xtop+10);
    $pdf->line($xleft, $xtop-3, $line_right, $xtop-3);

    foreach($_POST["fields"] as $field_config){
        $field_name = $field_config["fname"];
        $pdf->ezPlaceData($column_positions[$field_name], $xtop, "<b>".$field_config["fheader"]."</b>", 9, 'left');
    }

    $xtop -= 15;
    $xdata_top = $xtop;

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader,'all');

    $xfilter = '';
    $xorder = '';
    $search_text_input = isset($_POST['search_hidden_value']) ? $_POST['search_hidden_value'] : '';

    if((isset($search_dd_field) && !empty($search_dd_field)) && isset($_POST['search_hidden_value']) &&
       (!isset($_POST["first_load_hidden"]) || $_POST["first_load_hidden"] !== "Y")){

        $xorder = "ORDER BY ".$search_dd_field." ASC";

        if($_POST["search_hidden_type"] == "date"){
            $search_text_input  = (empty($search_text_input)) ? NULL : date("Y-m-d", strtotime($search_text_input));

            if($search_text_input == NULL){
                $xfilter = "";
            }else{
                $xfilter = "AND ".$search_dd_field." LIKE '%".$search_text_input."%'";
            }
        }else{
            $_POST['search_hidden_value'] = (empty($_POST['search_hidden_value'])) ? NULL : $_POST['search_hidden_value'];

            if($_POST['search_hidden_value'] == NULL){
                $xfilter = "";
            }else{
                $xfilter = "AND ".$search_dd_field." LIKE '%".$_POST['search_hidden_value']."%'";
            }
        }
    }else{
        $xorder = "ORDER BY ".$_POST["table_order_field"]." ".$_POST["table_order_type"];
    }

    $select_db_main = "SELECT ".$fields." FROM ".$_POST["tablename_hidden"]." WHERE true ".$xfilter." ".$xorder;

    $stmt_main = $link->prepare($select_db_main);
    $stmt_main->execute();
    while($rs_main = $stmt_main->fetch()){
        $row_lines = array();
        $row_line_count = 1;

        foreach($_POST["fields"] as $field_config){
            $field_name = $field_config["fname"];
            $field_type = $field_config["ftype"];
            $display_value = activity_log_pdf_field_value($rs_main[$field_name], $field_type);
            $column_width = isset($column_widths[$field_name]) ? $column_widths[$field_name] : 115;
            $wrapped_lines = activity_log_pdf_wrap_lines($display_value, $column_width, 9);
            $row_lines[$field_name] = $wrapped_lines;
            $row_line_count = max($row_line_count, count($wrapped_lines));
        }

        $row_height = 15 + (($row_line_count - 1) * 10);
        if(($xtop - $row_height) <= 60){
            $pdf->ezNewPage();
            $xtop = $xdata_top;
        }

        foreach($_POST["fields"] as $field_config){
            $field_name = $field_config["fname"];
            $line_index = 0;
            foreach($row_lines[$field_name] as $line_text){
                $pdf->ezPlaceData($column_positions[$field_name], $xtop - ($line_index * 10), $line_text, 9, 'left');
                $line_index++;
            }
        }

        $xtop -= $row_height;
    }

    $pdf->line($xleft, $xtop-10, $line_right, $xtop-10);
    $pdf->addText($xleft + 5, 15, 8, "Date Printed : ".date("F j, Y, g:i A"), $angle=0, $wordspaceadjust=1);
    $pdf->ezStream();
    ob_end_flush();

    function activity_log_pdf_field_value($value, $field_type)
    {
        if($field_type == "date"){
            if(!empty($value) && $value !== NULL && $value !== "1970-01-01"){
                return str_replace('-', '/', date("m-d-Y", strtotime($value)));
            }
            return '';
        }

        if($field_type == "checkbox"){
            if($value == 1 || $value == "1"){
                return "checked";
            }
            return "unchecked";
        }

        return trim((string)$value);
    }

    function activity_log_pdf_wrap_lines($string, $max_wid, $fsize)
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

            $line = activity_log_pdf_fit_text_to_width($remaining, $max_wid, $fsize);
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

        return empty($wrapped_lines) ? array($string) : $wrapped_lines;
    }

    function activity_log_pdf_fit_text_to_width($string, $max_wid, $fsize)
    {
        global $pdf;

        $xarr_str = str_split((string)$string);
        $xxstr = '';
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr.$value);
            if($xstr_wid > $max_wid){
                break;
            }
            $xxstr .= $value;
        }

        return rtrim($xxstr);
    }
?>
