<?php
    // ini_set('display_errors', '1');
    // ini_set('display_startup_errors', '1');
    // error_reporting(E_ALL);

    session_start();

    require_once("../resources/db_init.php");
    require "../resources/connect4.php";
    require "../resources/stdfunc100.php";
    require "../resources/lx2.pdodb.php";

    if(!function_exists('itemfile_in_use')){
        function itemfile_in_use($link, $itmcde){
            $itmcde = trim((string)$itmcde);
            if($itmcde === ''){
                return false;
            }

            $tables_to_check = array(
                'purchasesorderfile2' => 'itmcde',
                'salesorderfile2' => 'itmcde',
                'tranfile2' => 'itmcde'
            );

            foreach($tables_to_check as $table_name => $column_name){
                $select_check = "SELECT 1 FROM ".$table_name." WHERE ".$column_name." = ? LIMIT 1";
                $stmt_check = $link->prepare($select_check);
                $stmt_check->execute(array($itmcde));

                if($stmt_check->fetch()){
                    return true;
                }
            }

            return false;
        }
    }

    if(!function_exists('customerfile_in_use')){
        function customerfile_in_use($link, $cuscde){
            $cuscde = trim((string)$cuscde);
            if($cuscde === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM salesorderfile1 WHERE cuscde = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($cuscde));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('mf_buyers_in_use')){
        function mf_buyers_in_use($link, $buyer_id){
            $buyer_id = trim((string)$buyer_id);
            if($buyer_id === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM tranfile1 WHERE buyer_id = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($buyer_id));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('mf_salesman_in_use')){
        function mf_salesman_in_use($link, $salesman_id){
            $salesman_id = trim((string)$salesman_id);
            if($salesman_id === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM tranfile1 WHERE salesman_id = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($salesman_id));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('mf_routes_in_use')){
        function mf_routes_in_use($link, $route_id){
            $route_id = trim((string)$route_id);
            if($route_id === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM tranfile1 WHERE route_id = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($route_id));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('supplierfile_in_use')){
        function supplierfile_in_use($link, $suppcde){
            $suppcde = trim((string)$suppcde);
            if($suppcde === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM tranfile1 WHERE suppcde = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($suppcde));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('warehouse_in_use')){
        function warehouse_in_use($link, $warcde){
            $warcde = trim((string)$warcde);
            if($warcde === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM warehouse_floor WHERE warcde = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($warcde));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('warehouse_floor_in_use')){
        function warehouse_floor_in_use($link, $warehouse_floor_id){
            $warehouse_floor_id = trim((string)$warehouse_floor_id);
            if($warehouse_floor_id === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM tranfile2 WHERE warehouse_floor_id = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($warehouse_floor_id));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('expensetypefile_in_use')){
        function expensetypefile_in_use($link, $expense_cde){
            $expense_cde = trim((string)$expense_cde);
            if($expense_cde === ''){
                return false;
            }

            $select_check = "SELECT 1 FROM expensefile1 WHERE expense_cde = ? LIMIT 1";
            $stmt_check = $link->prepare($select_check);
            $stmt_check->execute(array($expense_cde));

            if($stmt_check->fetch()){
                return true;
            }

            return false;
        }
    }

    if(!function_exists('pager_current_db_name')){
        function pager_current_db_name()
        {
            if(isset($_SESSION['db_dbname']) && $_SESSION['db_dbname'] !== ''){
                return $_SESSION['db_dbname'];
            }

            if(isset(db_init::$dbholder_db_name) && db_init::$dbholder_db_name !== ''){
                return db_init::$dbholder_db_name;
            }

            return '';
        }
    }

    if(!function_exists('itemunitmeasurefile_in_use')){
        function itemunitmeasurefile_in_use($link, $unmcde){
            $unmcde = trim((string)$unmcde);
            if($unmcde === ''){
                return false;
            }

            $select_reference_record = "SELECT 1 FROM itemunitfile WHERE unmcde = ? LIMIT 1";
            $stmt_reference_record = $link->prepare($select_reference_record);
            $stmt_reference_record->execute(array($unmcde));

            return (bool)$stmt_reference_record->fetch();
        }
    }

    if(!function_exists('extract_pager_field_name')){
        function extract_pager_field_name($raw_name){
            if(!is_string($raw_name)){
                return '';
            }

            if(preg_match('/^fields\[(.+?)_displayData\]\[fname\]$/', $raw_name, $matches)){
                return $matches[1];
            }

            return trim(remove_xfields($raw_name, "fname"));
        }
    }

    if(!function_exists('render_pager_btn_function')){
        function render_pager_btn_function($template, $row){
            if(!is_string($template) || $template === ''){
                return '';
            }

            return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) use ($row){
                $field_name = $matches[1];
                if(isset($row[$field_name])){
                    return addslashes((string)$row[$field_name]);
                }
                return '';
            }, $template);
        }
    }

    if(!function_exists('extract_pager_btn_placeholders')){
        function extract_pager_btn_placeholders($buttons){
            $fields = array();

            if(!is_array($buttons)){
                return $fields;
            }

            foreach($buttons as $button){
                if(!isset($button[3]["btn-function"]) || !is_string($button[3]["btn-function"])){
                    continue;
                }

                if(preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $button[3]["btn-function"], $matches)){
                    foreach($matches[1] as $field_name){
                        $field_name = trim($field_name);
                        if($field_name !== '' && !in_array($field_name, $fields, true)){
                            $fields[] = $field_name;
                        }
                    }
                }
            }

            return $fields;
        }
    }

/*
    $stmt	= $link->prepare("select * from itemfile order by itmdsc");
    $stmt->execute();
    while($row = $stmt->fetch()){
        echo "<pre>";
        var_dump($row['itmdsc']);
    }
    die();
*/

    $fields_arr = array();

    foreach($_POST["xfields"] as $key_select => $value_select){
        $name_select = extract_pager_field_name($value_select[0]["name"]);

        if($name_select !== ""){
            $fields_arr[] = $name_select;
        }
    }

    $btn_placeholder_fields = extract_pager_btn_placeholders(isset($_POST["xdata_btn"]) ? $_POST["xdata_btn"] : array());
    foreach($btn_placeholder_fields as $placeholder_field){
        if(!in_array($placeholder_field, $fields_arr, true)){
            $fields_arr[] = $placeholder_field;
        }
    }

    if(!in_array("recid", $fields_arr)){
        $fields_arr[] = "recid";
    }

    // Add itmcde for itemfile table to check if item is in use
    if($_POST["tablename"] == "itemfile" && !in_array("itmcde", $fields_arr)){
        $fields_arr[] = "itmcde";
    }

    // Add cuscde for customerfile table to check if customer is in use
    if($_POST["tablename"] == "customerfile" && !in_array("cuscde", $fields_arr)){
        $fields_arr[] = "cuscde";
    }

    // Add buyer_id for mf_buyers table to check if buyer is in use
    if($_POST["tablename"] == "mf_buyers" && !in_array("buyer_id", $fields_arr)){
        $fields_arr[] = "buyer_id";
    }

    // Add salesman_id for mf_salesman table to check if salesman is in use
    if($_POST["tablename"] == "mf_salesman" && !in_array("salesman_id", $fields_arr)){
        $fields_arr[] = "salesman_id";
    }

    // Add route_id for mf_routes table to check if route is in use
    if($_POST["tablename"] == "mf_routes" && !in_array("route_id", $fields_arr)){
        $fields_arr[] = "route_id";
    }

    // Add suppcde for supplierfile table to check if supplier is in use
    if($_POST["tablename"] == "supplierfile" && !in_array("suppcde", $fields_arr)){
        $fields_arr[] = "suppcde";
    }

    // Add warcde for warehouse table to check if warehouse has floors
    if($_POST["tablename"] == "warehouse" && !in_array("warcde", $fields_arr)){
        $fields_arr[] = "warcde";
    }

    // Add warehouse_floor_id for warehouse_floor table to check if floor is in use
    if($_POST["tablename"] == "warehouse_floor" && !in_array("warehouse_floor_id", $fields_arr)){
        $fields_arr[] = "warehouse_floor_id";
    }

    // Add expense_cde for expensetypefile table to check if expense type is in use
    if($_POST["tablename"] == "expensetypefile" && !in_array("expense_cde", $fields_arr)){
        $fields_arr[] = "expense_cde";
    }

    $fields = implode(",", $fields_arr);

    //TO CHANGE
    $xlimit = $_POST["xlimit"];

    //INIITALIZING XRET
    $xret = array();
    $xret["html"] = '';
    $xret["html_mobile"] = '';
    $xret["html_search"] = '';
    $filter =  '';
    $search_text_input = '';

    if($_POST["event_action"] == "search" ||
       (isset($_POST["search_text_input_hidden"]) && $_POST["search_text_input_hidden"] == $_POST["search_text_input"])
    ){
        $search_text_input = $_POST["search_text_input"];
        $xret["hidden_value_search"] = $_POST['search_text_input'];
    }else{

        if(!isset($_POST["search_text_input_hidden"])){
            $_POST["search_text_input_hidden"] = "";
        }
        $search_text_input = $_POST["search_text_input_hidden"];
        $xret["hidden_value_search"] = $_POST['search_text_input_hidden'];
    }

    $search_text_input_dd = '';

    if($_POST["event_action"] == "search" ||
    (isset($_POST["search_text_input_hidden_dd"]) && $_POST["search_text_input_hidden_dd"] == $_POST["search_dd"])
    ){
        $search_text_input_dd = $_POST["search_dd"];
        $xret["hidden_value_search_dd"] = $_POST['search_dd'];
    }else{

        if(!isset($_POST["search_text_input_hidden_dd"])){
            $_POST["search_text_input_hidden_dd"] = "";
        }
        $search_text_input_dd = $_POST["search_text_input_hidden_dd"];
        $xret["hidden_value_search_dd"] = $_POST['search_text_input_hidden_dd'];
    }

    $fixed_filter = "";
    if(isset($_POST["table_filter_field"]) && isset($_POST["table_filter_value"])){
        $table_filter_field = trim($_POST["table_filter_field"]);
        $table_filter_value = (string)$_POST["table_filter_value"];

        if(
            $table_filter_field !== "" &&
            $table_filter_value !== "" &&
            preg_match('/^[a-zA-Z0-9_]+$/', $table_filter_field)
        ){
            $fixed_filter = " AND ".$_POST["tablename"].".".$table_filter_field." = ".$link->quote($table_filter_value);
        }
    }
//ar_dump('step 1');
    if(
        (isset($_POST["search_hidden"]) && $_POST["search_hidden"] == "Y") &&
        (!isset($_POST["first_load"]) || $_POST["first_load"] !== "Y")
    ){
        if($_POST["search_data_type"] == "checkbox"){
        }else if($_POST["search_data_type"] == "date"){

            $search_text_input  = (empty($search_text_input))   ? NULL :  date("Y-m-d", strtotime($search_text_input));

            if($search_text_input == NULL){
                $filter = "";
            }else{
                $filter = "AND ".$search_text_input_dd." LIKE '%".$search_text_input."%'";
            }

        }
        else if($_POST["search_data_type"] == "dropdown_custom"){

            $dropdown_field_name_value_search = (isset($_POST["field_name_value"]))?($_POST["field_name_value"]) : "";
            $dropdown_field_name_search       = $_POST["field_name"];
            $dropdown_tablename_search        = $_POST["tablename_search"];
            $dropdown_txt_value               = $search_text_input;

            if($dropdown_field_name_value_search !== ""){

                $select_db_xtotal="SELECT count(*) as rec_count FROM ".$_POST["tablename"]." INNER JOIN ".$dropdown_tablename_search
                ." ON ".$_POST["tablename"].'.'.$dropdown_field_name_search.'  = '.$dropdown_tablename_search.'.'.$dropdown_field_name_search."
                    WHERE true ".$fixed_filter." AND ".$dropdown_field_name_value_search." LIKE '%".$dropdown_txt_value."%'";
            }else{
                $select_db_xtotal = "SELECT count(*) as rec_count FROM ".$_POST['tablename']." WHERE true ".$fixed_filter." AND ".$dropdown_field_name_search." LIKE '%".$dropdown_txt_value."%'";
            }

        }
        else{

            $search_text_input  = (empty($search_text_input))   ? NULL :  $search_text_input;

            if($search_text_input == NULL){
                $filter = "";
            }else{
                $filter = "AND ".$search_text_input_dd." LIKE '%".$search_text_input."%'";
            }
        }
    }


    if(!isset($_POST["search_data_type"]) || $_POST["search_data_type"] !== "dropdown_custom"){
        $select_db_xtotal="SELECT count(*) as rec_count FROM ".$_POST['tablename']." WHERE true ".$fixed_filter." ".$filter."";
    }else{

        $dropdown_field_name_value_search = (isset($_POST["field_name_value"]))?($_POST["field_name_value"]) : "";
        $dropdown_field_name_search       = $_POST["field_name"];
        $dropdown_tablename_search        = $_POST["tablename_search"];
        $dropdown_txt_value               = $search_text_input;

        if($dropdown_field_name_value_search !== ""){

            $select_db_xtotal="SELECT count(*) as rec_count FROM ".$_POST["tablename"]." INNER JOIN ".$dropdown_tablename_search
            ." ON ".$_POST["tablename"].'.'.$dropdown_field_name_search.'  = '.$dropdown_tablename_search.'.'.$dropdown_field_name_search."
                WHERE true ".$fixed_filter." AND ".$dropdown_field_name_value_search." LIKE '%".$dropdown_txt_value."%'";
        }else{
            $select_db_xtotal = "SELECT count(*) as rec_count FROM ".$_POST['tablename']." WHERE true ".$fixed_filter." AND ".$dropdown_field_name_search." LIKE '%".$dropdown_txt_value."%'";
        }

    }

    $stmt_xtotal	= $link->prepare($select_db_xtotal);
    $stmt_xtotal->execute();
    $rs_xtotal = $stmt_xtotal->fetch();

    //INITIALIZE PAGE NO.
    $xpageno=$_POST['pageno'];

    //RETURN TOTAL RECORDS
    $xtotalrec=$rs_xtotal['rec_count'];
    $xret['totalrec']=$xtotalrec;

    //CALCULATE MAXPAGE
    $maxpage = ceil($xtotalrec / $xlimit);
    //RETURN MAXPAGE
    $xret["maxpage"] = $maxpage;


    if ($xtotalrec==0)
    {
        $xret["html"] = "<tr><td colspan=".$_POST['field_num']." class='text-center display-5 w-100' style='padding-left:0px !important;'> NO RECORDS<i class='fas fa-search display-6 mx-2'></i></td></tr>";
        $xret["html_mobile"] = "<tr><td colspan=".$_POST['field_num']." class='text-center display-5 w-100' style='padding-left:0px !important;'> NO RECORDS<i class='fas fa-search display-6 mx-2'></i></td></tr>";
        $xret["maxpage"]=0;
        $xret["xpageno"] ='';
        echo json_encode($xret);
        return;
    }

    //CALCULATE OFFSET
    if($xpageno == 0 || $xpageno == 1 || empty($xpageno) || $_POST["event_action"] == "search"){
        $xpageno = 1;
        $xoffset = 0;
    }
    if($_POST["event_action"] == "next_p"){
        if($xpageno == $maxpage){
            //nothing changes
        }else{
            $xpageno++;
        }
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;

    }
    else if($_POST["event_action"] == "previous_p"){
        if($xpageno==1){
            $xoffset = 0;
        }else{
            $xpageno--;
            $xoffset =  ($xpageno * $xlimit) - $xlimit ;
        }
    }
    else if($_POST["event_action"] == "first_p"){
        $xpageno=1;
        $xoffset = 0;
    }
    else if($_POST["event_action"] == "last_p"){
        $xpageno = $maxpage;
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }
    else if($_POST["event_action"] =="same"){
        if($xpageno > $maxpage){
            $xpageno = $maxpage;
            $xoffset =  ($xpageno * $xlimit) - $xlimit ;
        }else{
            $xoffset =  ($xpageno * $xlimit) - $xlimit ;
        }
    }

    //RETURN PAGE NO
    $xret["xpageno"] = $xpageno;

    //initializing filter
    $filter = "";
    $order_filter = "";

    if(
        (isset($_POST["search_hidden"]) && $_POST["search_hidden"] == "Y") &&
        (!isset($_POST["first_load"]) || $_POST["first_load"] !== "Y")
    ){

        $filter_order = "ORDER BY  ".$search_text_input_dd." ASC";

        if($_POST["search_data_type"] == "checkbox"){
        }else if($_POST["search_data_type"] == "date"){

            $search_text_input  = (empty($search_text_input)) ? NULL :  date("Y-m-d", strtotime($search_text_input));

            if($search_text_input == NULL){
                $filter = "";
            }else{
                $filter = "AND ".$search_text_input_dd." LIKE '%".$search_text_input."%'";
            }

        }
        else if($_POST["search_data_type"] == "dropdown_custom"){

            $fields_search_arr = array();

            foreach($_POST["xfields"] as $key_select => $value_select_search){
                $value_select_name = extract_pager_field_name($value_select_search[0]["name"]);

                if($value_select_name !== ""){
                    $fields_search_arr[] = $_POST["tablename"].".".$value_select_name;
                }
            }

            foreach($btn_placeholder_fields as $placeholder_field){
                $placeholder_select = $_POST["tablename"].".".$placeholder_field;
                if(!in_array($placeholder_select, $fields_search_arr, true)){
                    $fields_search_arr[] = $placeholder_select;
                }
            }

            if(!in_array($_POST["tablename"].".recid", $fields_search_arr)){
                $fields_search_arr[] = $_POST["tablename"].".recid";
            }

            $fields_search = implode(",", $fields_search_arr);


            $dropdown_field_name_value_search = (isset($_POST["field_name_value"]))?($_POST["field_name_value"]) : "";
            $dropdown_field_name_search       = $_POST["field_name"];
            $dropdown_tablename_search        = $_POST["tablename_search"];
            $dropdown_txt_value               = $search_text_input;

            if($dropdown_field_name_value_search !== ""){

                $select_db_fields="SELECT ".$fields_search." FROM ".$_POST["tablename"]." INNER JOIN ".$dropdown_tablename_search
                ." ON ".$_POST["tablename"].'.'.$dropdown_field_name_search.'  = '.$dropdown_tablename_search.'.'.$dropdown_field_name_search."
                    WHERE true ".$fixed_filter." AND ".$dropdown_field_name_value_search." LIKE '%".$dropdown_txt_value."%'"." ORDER BY ".$dropdown_tablename_search.".".$dropdown_field_name_value_search." ASC LIMIT ".$xlimit." OFFSET ".$xoffset;
            }else{
                $select_db_fields = "SELECT ".$fields." FROM ".$_POST['tablename']." WHERE true ".$fixed_filter." AND ".$dropdown_field_name_search." LIKE '%".$dropdown_txt_value."%' ORDER BY ".$dropdown_field_name_search." ASC";
            }

        }
        else{
            $search_text_input  = (empty($search_text_input))   ? NULL :  $search_text_input;

            if($search_text_input == NULL){
                $filter = "";
            }else{
                $filter = "AND ".$search_text_input_dd." LIKE '%".$search_text_input."%'";
            }
        }

    }else{
        $filter_order = "ORDER BY ".$_POST["table_order_field"]." ".$_POST["table_order_type"];
    }
    
//ar_dump($filter);

    if(!isset($_POST["search_data_type"]) || $_POST["search_data_type"] !== "dropdown_custom"){
        $select_db_fields="SELECT ".$fields." FROM ".$_POST['tablename']."  WHERE true ".$fixed_filter." ".$filter." ".$filter_order." LIMIT ".$xlimit." OFFSET ".$xoffset;
    }else if($_POST["search_data_type"] == "dropdown_custom" && (isset($_POST["first_load"]) && $_POST["first_load"] == "Y")){
        $fields_search_arr = array();

        foreach($_POST["xfields"] as $key_select => $value_select_search){
            $value_select_name = extract_pager_field_name($value_select_search[0]["name"]);

            if($value_select_name !== ""){
                $fields_search_arr[] = $_POST["tablename"].".".$value_select_name;
            }
        }

        foreach($btn_placeholder_fields as $placeholder_field){
            $placeholder_select = $_POST["tablename"].".".$placeholder_field;
            if(!in_array($placeholder_select, $fields_search_arr, true)){
                $fields_search_arr[] = $placeholder_select;
            }
        }

        if(!in_array($_POST["tablename"].".recid", $fields_search_arr)){
            $fields_search_arr[] = $_POST["tablename"].".recid";
        }

        $fields_search = implode(",", $fields_search_arr);

        $dropdown_field_name_value_search = (isset($_POST["field_name_value"]))?($_POST["field_name_value"]) : "";
        $dropdown_field_name_search       = $_POST["field_name"];
        $dropdown_tablename_search        = $_POST["tablename_search"];
        $dropdown_txt_value               = '';

        if($dropdown_field_name_value_search !== ""){

            $select_db_fields="SELECT ".$fields_search." FROM ".$_POST["tablename"]." INNER JOIN ".$dropdown_tablename_search
            ." ON ".$_POST["tablename"].'.'.$dropdown_field_name_search.'  = '.$dropdown_tablename_search.'.'.$dropdown_field_name_search."
                WHERE true ".$fixed_filter." AND ".$dropdown_field_name_value_search." LIKE '%".$dropdown_txt_value."%'"." ORDER BY ".$_POST["tablename"].".".$_POST["table_order_field"]." ".$_POST["table_order_type"]." LIMIT ".$xlimit." OFFSET ".$xoffset;
        }else{
            $select_db_fields = "SELECT ".$fields." FROM ".$_POST['tablename']." WHERE true ".$fixed_filter." AND ".$dropdown_field_name_search." LIKE '%".$dropdown_txt_value."%' ORDER BY ".$dropdown_field_name_search." ASC";
        }
    }
//var_dump($select_db_fields);
    $stmt	= $link->prepare($select_db_fields);
    $stmt->execute();

    $can_view_rows = false;
    if(isset($_SESSION["view_crud"])){
        $can_view_rows = ((int)$_SESSION["view_crud"] === 1);
    }else if(isset($_POST["view_crud"])){
        $can_view_rows = ((int)$_POST["view_crud"] === 1);
    }

    while($row = $stmt->fetch()){
        if(!$can_view_rows){
            break;
        }

        $xret["html"] .= "<tr>";

            foreach($_POST["xfields"] as $xfields_arr_key => $xfields_arr_val){

                $field_name = extract_pager_field_name($xfields_arr_val[0]["name"]);
                $field_type = $xfields_arr_val[1]["data-field-type"];
                if($field_type !== "dropdown_custom"){
                    $field_decimal_place = $xfields_arr_val[4]["data-field-decimal-place"];
                }else{
                    $field_decimal_place = '';
                }
                
                $field_fw = (
                                !isset($xfields_arr_val[2]["data-field-fw"]) ||
                                empty($xfields_arr_val[2]["data-field-fw"]) ||
                                $xfields_arr_val[2]["data-field-fw"] == "normal" ||
                                $xfields_arr_val[2]["data-field-fw"] == "none"
                                ) ? "normal" : $xfields_arr_val[2]["data-field-fw"];


                if($field_type == "date"){

                    if(!empty($row[$field_name]) && $row[$field_name] !== NULL &&  $row[$field_name]!=="1970-01-01"){
                        $row[$field_name] = date("m-d-Y",strtotime($row[$field_name]));
                        $row[$field_name] = str_replace('-','/',$row[$field_name]);
                    }else{
                        $row[$field_name] = NULL;
                    }

                }

                if($field_type == "checkbox"){

                    if($row["".$field_name.""] == 0){
                        $xret["html"] .= "<td data-label='".$xfields_arr_val[3]["data-field-header"]."' style='text-align:center'>";
                            $xret["html"].= "<input type='checkbox' class='form-check-input' style='opacity:1' disabled>";
                        $xret["html"] .= "</td>";
                    }else{
                        $xret["html"] .= "<td data-label='".$xfields_arr_val[3]["data-field-header"]."' style='text-align:center'>";
                            $xret["html"].= "<input type='checkbox' class='form-check-input' style='opacity:1' checked disabled>";
                        $xret["html"] .= "</td>";
                    }

                }

                if($row[$field_name] == NULL){
                    $row[$field_name] = '&nbsp';
                }

                if($field_type == "dropdown_custom"){

                    $dropdown_field_name = $xfields_arr_val[4]["data-dd-field_name"];
                    $dropdown_field_name_value = (isset($xfields_arr_val[5]["data-dd-field_name-value"]))?($xfields_arr_val[5]["data-dd-field_name-value"]) : "";
                    $dropdown_tablename       = $xfields_arr_val[6]["data-dd-tablename"];

                    if($dropdown_field_name_value !== ""){
                        $select_db_dd="SELECT ".$dropdown_field_name_value.", ".$dropdown_field_name." FROM ".$dropdown_tablename." where ".$dropdown_field_name."='".$row["".$dropdown_field_name.""]."'";
                    }else{
                        $select_db_dd="SELECT ".$dropdown_field_name." FROM ".$_POST["tablename"]." WHERE ".$dropdown_field_name." LIKE '%".$row["".$dropdown_field_name.""]."%'";
                    }

                    $stmt_dd	= $link->prepare($select_db_dd);
                    $stmt_dd->execute();

                    while($rs_dd = $stmt_dd->fetch()){

                        if($dropdown_field_name_value !== ""){
                            $row[$field_name] = $rs_dd[$dropdown_field_name_value];
                        }
                        else{
                            $row[$field_name] = $rs_dd[$dropdown_field_name];
                        }

                    }

                }

                if($field_type !== "checkbox"){

                    if(!empty($field_decimal_place)){
                        $xret["html"] .= "<td style='font-weight:".$field_fw.";text-align:right' data-label='".$xfields_arr_val[3]["data-field-header"]."'>";
                            $xret["html"].= "<span>".number_format((float)$row["".$field_name.""],$field_decimal_place)."</span>";
                        $xret["html"] .= "</td>";
                    }else{
                        $xret["html"] .= "<td style='font-weight:".$field_fw."' data-label='".$xfields_arr_val[3]["data-field-header"]."'>";
                            $xret["html"].= "<span>".$row["".$field_name.""]."</span>";
                        $xret["html"] .= "</td>";
                    }

                }

            }

	            if(($_POST["display_only"] !== "Y" || empty($_POST["display_only"])) && ((int)$_SESSION["edit_crud"] == 1 || (int)$_SESSION["delete_crud"] == 1)){

                $uom_record_in_use = false;
                $uom_edit_in_use_message = "Unit of measure in use, cannot edit";
                $uom_delete_in_use_message = "Unit of measure in use, cannot delete";
                if($_POST["tablename"] == "itemunitmeasurefile" && isset($row['unmcde'])){
                    $uom_record_in_use = itemunitmeasurefile_in_use($link, $row['unmcde']);
                }

	                $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";
	                    $xret["html"].= "<div class='dropdown'>";
	                        $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row['recid']."'  data-bs-toggle='dropdown' aria-expanded='false' data-uom-in-use='".($uom_record_in_use ? "1" : "0")."'".($uom_record_in_use ? " style='opacity:0.5;'" : "").">";
	                            $xret["html"].= "Action";
	                        $xret["html"].= "</button>";

	                        $xret["html"].= "<ul class='dropdown-menu main_action_dd' aria-labelledby='dropdownMenuButton1-".$row['recid']."'>";

		                            if((int)$_SESSION["edit_crud"] == 1){
		                                if($uom_record_in_use){
		                                    $xret["html"].= "<li onclick=\"alert('".$uom_edit_in_use_message."')\">";
		                                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;opacity:0.5;pointer-events:none;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
		                                    $xret["html"].= "</li>";
		                                }else if(!empty($_POST["cus_function_name"])){
	                                    $xret["html"].= "<li onclick=\"".$_POST['cus_function_name']."('getEdit' , '".$row['recid']."')\">";
	                                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
	                                    $xret["html"].= "</li>";
	                                }else{
	                                    $xret["html"].= "<li onclick=\"ajaxFunc('getEdit' , '".$row['recid']."')\">";
	                                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
	                                    $xret["html"].= "</li>";
	                                }
	                            }

		                            if((int)$_SESSION["delete_crud"] == 1){
		                                // Check if record is in use
		                                $record_in_use = $uom_record_in_use;
		                                $in_use_message = $uom_delete_in_use_message;

	                                if(!$record_in_use && $_POST["tablename"] == "itemfile" && isset($row['itmcde'])){
	                                    $record_in_use = itemfile_in_use($link, $row['itmcde']);
	                                    $in_use_message = "Cannot delete, item in use";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "customerfile" && isset($row['cuscde'])){
	                                    $record_in_use = customerfile_in_use($link, $row['cuscde']);
	                                    $in_use_message = "Cannot delete, customer in use";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "mf_buyers" && isset($row['buyer_id'])){
	                                    $record_in_use = mf_buyers_in_use($link, $row['buyer_id']);
	                                    $in_use_message = "Cannot delete, buyer in use";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "mf_salesman" && isset($row['salesman_id'])){
	                                    $record_in_use = mf_salesman_in_use($link, $row['salesman_id']);
	                                    $in_use_message = "Cannot delete, salesman in use";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "mf_routes" && isset($row['route_id'])){
	                                    $record_in_use = mf_routes_in_use($link, $row['route_id']);
	                                    $in_use_message = "Cannot delete, route in use";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "supplierfile" && isset($row['suppcde'])){
	                                    $record_in_use = supplierfile_in_use($link, $row['suppcde']);
	                                    $in_use_message = "Cannot delete, supplier in use";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "warehouse" && isset($row['warcde'])){
	                                    $record_in_use = warehouse_in_use($link, $row['warcde']);
	                                    $in_use_message = "Cannot delete, warehouse still has floors";
	                                }
	                                if(!$record_in_use && $_POST["tablename"] == "expensetypefile" && isset($row['expense_cde'])){
	                                    $record_in_use = expensetypefile_in_use($link, $row['expense_cde']);
	                                    $in_use_message = "Cannot delete, expense type in use";
	                                }

	                                if($record_in_use){
	                                    $xret["html"].= "<li onclick=\"alert('".$in_use_message."')\">";
	                                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;opacity:0.5;pointer-events:none;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
	                                    $xret["html"].= "</li>";
	                                }else{
                                    if(!empty($_POST["cus_function_name"])){
                                        $xret["html"].= "<li onclick=\"".$_POST['cus_function_name']."('delete' , '".$row['recid']."')\">";
                                    }else{
                                        $xret["html"].= "<li onclick=\"ajaxFunc('delete' , '".$row['recid']."')\">";
                                    }

                                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                                    $xret["html"].= "</li>";
                                }
                            }

                            if(isset($_POST["xdata_btn"])){
                                foreach($_POST["xdata_btn"] as $xdata_btn_key => $xdata_btn_value){
                                    $btn_header = $xdata_btn_value[0]["btn-header"];
                                    $btn_color  = $xdata_btn_value[1]["btn-color"];
                                    $btn_logo = $xdata_btn_value[2]["btn-logo"];
                                    $btn_function = $xdata_btn_value[3]["btn-function"];
                                    $btn_function_render = render_pager_btn_function($btn_function, $row);

                                    $xret["html"].= "<li onclick=\"$btn_function_render\">";
                                        $xret["html"].= "<a class='dropdown-item' style='color:".$btn_color."'>".$btn_logo."<span style='margin-left:7px;font-size:17px;font-family:arial'>".$btn_header."</span></a>";
                                    $xret["html"].= "</li>";
                                }
                            }

                        $xret["html"].= "</ul>";
                    $xret["html"].= "</div>";
                $xret["html"].= "</td>";
            }

        $xret["html"] .= "</tr>";

        $xhtml_mobile_counter = 0;

        foreach($_POST["xfields"] as $xfields_arr_key => $xfields_arr_val){
            $xstyle = ''; 
            $xhtml_mobile_counter++;

            if(((int)$_SESSION["edit_crud"] !== 1 && (int)$_SESSION["delete_crud"] !== 1) && ($xhtml_mobile_counter == $_POST["field_num"])){
                $xstyle = 'border-bottom:2px solid black;';
            }

            

            $xret["html_mobile"] .= "<tr style='".$xstyle."'>";

            $field_name = extract_pager_field_name($xfields_arr_val[0]["name"]);
            $field_type = $xfields_arr_val[1]["data-field-type"];

            $field_fw = (
                        !isset($xfields_arr_val[2]["data-field-fw"]) ||
                        empty($xfields_arr_val[2]["data-field-fw"]) ||
                        $xfields_arr_val[2]["data-field-fw"] == "normal" ||
                        $xfields_arr_val[2]["data-field-fw"] == "none"
                        ) ? "normal" : $xfields_arr_val[2]["data-field-fw"];

            $xret["html_mobile"] .= "<td style='font-weight:bold;'>";
                $xret["html_mobile"].= "<span>".$xfields_arr_val[3]["data-field-header"]."</span>";
            $xret["html_mobile"] .= "</td>";

            if($field_type == "date"){

                if(!empty($row[$field_name]) && $row[$field_name] !== NULL &&  $row[$field_name]!=="1970-01-01"){
                    $row[$field_name] = date("m-d-Y",strtotime($row[$field_name]));
                    $row[$field_name] = str_replace('-','/',$row[$field_name]);
                }else{
                    $row[$field_name] = NULL;
                }

            }

            if($field_type == "checkbox"){

                if($row["".$field_name.""] == 0){
                    $xret["html_mobile"] .= "<td data-label='".$xfields_arr_val[3]["data-field-header"]."'>";
                        $xret["html_mobile"].= "<input type='checkbox' class='form-check-input' style='opacity:1' disabled>";
                    $xret["html_mobile"] .= "</td>";
                }else{
                    $xret["html_mobile"] .= "<td data-label='".$xfields_arr_val[3]["data-field-header"]."'>";
                        $xret["html_mobile"].= "<input type='checkbox' class='form-check-input' style='opacity:1' checked disabled>";
                    $xret["html_mobile"] .= "</td>";
                }

            }


            if($row[$field_name] == NULL){
                $row[$field_name] = '&nbsp';
            }

            if($field_type == "dropdown_custom"){

                $dropdown_field_name = $xfields_arr_val[4]["data-dd-field_name"];
                $dropdown_field_name_value = (isset($xfields_arr_val[5]["data-dd-field_name-value"]))?($xfields_arr_val[5]["data-dd-field_name-value"]) : "";
                $dropdown_tablename       = $xfields_arr_val[6]["data-dd-tablename"];

                if($dropdown_field_name_value !== ""){
                    $select_db_dd="SELECT ".$dropdown_field_name_value.", ".$dropdown_field_name." FROM ".$dropdown_tablename." where ".$dropdown_field_name."='".$row["".$dropdown_field_name.""]."'";
                }else{
                    $select_db_dd="SELECT ".$dropdown_field_name." FROM ".$_POST["tablename"]." WHERE ".$dropdown_field_name." LIKE '%".$row["".$dropdown_field_name.""]."%'";
                }
                
                $xret["sql"] = $select_db_dd;

                $stmt_dd	= $link->prepare($select_db_dd);
                $stmt_dd->execute();

                while($rs_dd = $stmt_dd->fetch()){

                    if($dropdown_field_name_value !== ""){
                        $row[$field_name] = $rs_dd[$dropdown_field_name_value];
                    }
                    else{
                        $row[$field_name] = $rs_dd[$dropdown_field_name];
                    }

                }

            }

            if($field_type !== "checkbox"){

                if(!empty($field_decimal_place)){
                    $xret["html_mobile"] .= "<td style='font-weight:".$field_fw.";text-align:right' data-label='".$xfields_arr_val[3]["data-field-header"]."'>";
                        $xret["html_mobile"].= "<span>".number_format((float)$row["".$field_name.""],$field_decimal_place)."</span>";
                    $xret["html_mobile"] .= "</td>";
                }else{
                    $xret["html_mobile"] .= "<td style='font-weight:".$field_fw."' data-label='".$xfields_arr_val[3]["data-field-header"]."'>";
                        $xret["html_mobile"].= "<span>".$row["".$field_name.""]."</span>";
                    $xret["html_mobile"] .= "</td>";
                }
            }

            $xret["html_mobile"] .= "</tr>";

        }

	        if(($_POST["display_only"] !== "Y"|| empty($_POST["display_only"])) && ((int)$_SESSION["edit_crud"] == 1 || (int)$_SESSION["delete_crud"] == 1)){

                $uom_record_in_use_mobile = false;
                $uom_edit_in_use_message_mobile = "Unit of measure in use, cannot edit";
                $uom_delete_in_use_message_mobile = "Unit of measure in use, cannot delete";
                if($_POST["tablename"] == "itemunitmeasurefile" && isset($row['unmcde'])){
                    $uom_record_in_use_mobile = itemunitmeasurefile_in_use($link, $row['unmcde']);
                }

	            $xret["html_mobile"] .= "<tr>";
	                $xret["html_mobile"] .= "<td style='font-weight:bold;' class='align-middle'>";
                    $xret["html_mobile"].= "<span>Action</span>";
                $xret["html_mobile"] .= "</td>";

	                $xret["html_mobile"].= "<td class='text-center align-middle' data-label='Action'>";
	                    $xret["html_mobile"].= "<div class='dropdown'>";
	                        $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row['recid']."'  data-bs-toggle='dropdown' aria-expanded='false' data-uom-in-use='".($uom_record_in_use_mobile ? "1" : "0")."'".($uom_record_in_use_mobile ? " style='opacity:0.5;'" : "").">";
	                            $xret["html_mobile"].= "Action";
	                        $xret["html_mobile"].= "</button>";

	                        $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' aria-labelledby='dropdownMenuButton1-".$row['recid']."'>";

		                            if((int)$_SESSION["edit_crud"] == 1){
		                                if($uom_record_in_use_mobile){
		                                    $xret["html_mobile"].= "<li onclick=\"alert('".$uom_edit_in_use_message_mobile."')\">";
		                                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;opacity:0.5;pointer-events:none;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
		                                    $xret["html_mobile"].= "</li>";
		                                }else if(!empty($_POST["cus_function_name"])){
	                                    $xret["html_mobile"].= "<li onclick=\"".$_POST['cus_function_name']."('getEdit' , '".$row['recid']."')\">";
	                                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
	                                    $xret["html_mobile"].= "</li>";
	                                }else{
	                                    $xret["html_mobile"].= "<li onclick=\"ajaxFunc('getEdit' , '".$row['recid']."')\">";
	                                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
	                                    $xret["html_mobile"].= "</li>";
	                                }
	                            }
		                            if((int)$_SESSION["delete_crud"] == 1){
		                                // Check if record is in use
		                                $record_in_use_mobile = $uom_record_in_use_mobile;
		                                $in_use_message_mobile = $uom_delete_in_use_message_mobile;

	                                if(!$record_in_use_mobile && $_POST["tablename"] == "itemfile" && isset($row['itmcde'])){
	                                    $record_in_use_mobile = itemfile_in_use($link, $row['itmcde']);
	                                    $in_use_message_mobile = "Cannot delete, item in use";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "customerfile" && isset($row['cuscde'])){
	                                    $record_in_use_mobile = customerfile_in_use($link, $row['cuscde']);
	                                    $in_use_message_mobile = "Cannot delete, customer in use";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "mf_buyers" && isset($row['buyer_id'])){
	                                    $record_in_use_mobile = mf_buyers_in_use($link, $row['buyer_id']);
	                                    $in_use_message_mobile = "Cannot delete, buyer in use";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "mf_salesman" && isset($row['salesman_id'])){
	                                    $record_in_use_mobile = mf_salesman_in_use($link, $row['salesman_id']);
	                                    $in_use_message_mobile = "Cannot delete, salesman in use";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "mf_routes" && isset($row['route_id'])){
	                                    $record_in_use_mobile = mf_routes_in_use($link, $row['route_id']);
	                                    $in_use_message_mobile = "Cannot delete, route in use";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "supplierfile" && isset($row['suppcde'])){
	                                    $record_in_use_mobile = supplierfile_in_use($link, $row['suppcde']);
	                                    $in_use_message_mobile = "Cannot delete, supplier in use";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "warehouse" && isset($row['warcde'])){
	                                    $record_in_use_mobile = warehouse_in_use($link, $row['warcde']);
	                                    $in_use_message_mobile = "Cannot delete, warehouse still has floors";
	                                }
	                                if(!$record_in_use_mobile && $_POST["tablename"] == "expensetypefile" && isset($row['expense_cde'])){
	                                    $record_in_use_mobile = expensetypefile_in_use($link, $row['expense_cde']);
	                                    $in_use_message_mobile = "Cannot delete, expense type in use";
	                                }

	                                if($record_in_use_mobile){
	                                    $xret["html_mobile"].= "<li onclick=\"alert('".$in_use_message_mobile."')\">";
	                                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;opacity:0.5;pointer-events:none;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
	                                    $xret["html_mobile"].= "</li>";
	                                }else{
                                    if(!empty($_POST["cus_function_name"])){
                                        $xret["html_mobile"].= "<li onclick=\"".$_POST['cus_function_name']."('delete' , '".$row['recid']."')\">";
                                    }else{
                                        $xret["html_mobile"].= "<li onclick=\"ajaxFunc('delete' , '".$row['recid']."')\">";
                                    }

                                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                                    $xret["html_mobile"].= "</li>";
                                }
                            }

                            if(isset($_POST["xdata_btn"])){
                                foreach($_POST["xdata_btn"] as $xdata_btn_key => $xdata_btn_value){
                                    $btn_header = $xdata_btn_value[0]["btn-header"];
                                    $btn_color  = $xdata_btn_value[1]["btn-color"];
                                    $btn_logo = $xdata_btn_value[2]["btn-logo"];
                                    $btn_function = $xdata_btn_value[3]["btn-function"];
                                    $btn_function_render = render_pager_btn_function($btn_function, $row);

                                    $xret["html_mobile"].= "<li onclick=\"$btn_function_render\">";
                                        $xret["html_mobile"].= "<a class='dropdown-item' style='color:".$btn_color."'>".$btn_logo."<span style='margin-left:7px;font-size:17px;font-family:arial'>".$btn_header."</span></a>";
                                    $xret["html_mobile"].= "</li>";
                                }
                            }

                        $xret["html_mobile"].= "</ul>";
                    $xret["html_mobile"].= "</div>";
                $xret["html_mobile"].= "</td>";
            $xret["html_mobile"] .= "</tr>";
        }

    };
    

echo json_encode($xret);
?>
