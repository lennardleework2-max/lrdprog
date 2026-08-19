<?php 
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

session_start();
require_once("../resources/lx2.pdodb.php");
require "../resources/db_init.php";
require "../resources/connect4.php";
require "../resources/stdfunc100.php";


$xret = array();
$xret["status"] = 1;

$xerror = array();
$xerror["error1"] = "";
$xerror["error2"] = "";
$xerror["error3"] = "";
$xerror["error4"] = "";
$xret["msg"] = "";

$select_db_session_user='SELECT * FROM users where recid=?';
$stmt_session_user	= $link->prepare($select_db_session_user);
$stmt_session_user->execute(array($_POST["userid"]));
$rs_session_user = $stmt_session_user->fetch();

$username_session = $rs_session_user["userdesc"];
$username_full_name = $rs_session_user["full_name"];

$xtrndte = date("Y-m-d H:i:s");
$ua_field2 = "";
$ua_field1 = "";

// Get module name in CAPS from main_header
$xprog_module = "";
if(isset($_POST['main_header']) && !empty($_POST['main_header'])){
    $xprog_module = strtoupper($_POST['main_header']);
}

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

function itemunitmeasurefile_in_use($link, $unmcde)
{
    $unmcde = trim((string)$unmcde);
    if($unmcde === ''){
        return false;
    }

    $select_reference_record = "SELECT 1 FROM itemunitfile WHERE unmcde = ? LIMIT 1";
    $stmt_reference_record = $link->prepare($select_reference_record);
    $stmt_reference_record->execute(array($unmcde));

    return (bool)$stmt_reference_record->fetch();
}

function pager_item_uom_description($link, $unmcde)
{
    $unmcde = trim((string)$unmcde);
    if($unmcde === ''){
        return '';
    }

    $select_uom_description = "SELECT unmdsc FROM itemunitmeasurefile WHERE unmcde = ? LIMIT 1";
    $stmt_uom_description = $link->prepare($select_uom_description);
    $stmt_uom_description->execute(array($unmcde));
    $rs_uom_description = $stmt_uom_description->fetch();

    return !empty($rs_uom_description["unmdsc"]) ? $rs_uom_description["unmdsc"] : '';
}

function pager_item_description_from_itmcde($link, $itmcde)
{
    $itmcde = trim((string)$itmcde);
    if($itmcde === ''){
        return '';
    }

    $select_item_description = "SELECT itmdsc FROM itemfile WHERE itmcde = ? LIMIT 1";
    $stmt_item_description = $link->prepare($select_item_description);
    $stmt_item_description->execute(array($itmcde));
    $rs_item_description = $stmt_item_description->fetch();

    return !empty($rs_item_description["itmdsc"]) ? $rs_item_description["itmdsc"] : '';
}

function pager_warehouse_name_from_warcde($link, $warcde)
{
    $warcde = trim((string)$warcde);
    if($warcde === ''){
        return '';
    }

    $select_warehouse_name = "SELECT warehouse_name FROM warehouse WHERE warcde = ? LIMIT 1";
    $stmt_warehouse_name = $link->prepare($select_warehouse_name);
    $stmt_warehouse_name->execute(array($warcde));
    $rs_warehouse_name = $stmt_warehouse_name->fetch();

    return !empty($rs_warehouse_name["warehouse_name"]) ? $rs_warehouse_name["warehouse_name"] : '';
}

function pager_log_display_value($value)
{
    if($value === null){
        return '(blank)';
    }

    $value = trim((string)$value);
    return ($value === '') ? '(blank)' : $value;
}

function pager_log_context_label($tablename, $main_header = '')
{
    switch((string)$tablename){
        case 'itemfile':
            return 'item';
        case 'mf_salesman':
            return 'salesman';
        case 'mf_routes':
            return 'route';
        case 'supplierfile':
            return 'supplier';
        case 'customerfile':
            return 'shop name';
        case 'expensetypefile':
            return 'expense type';
        case 'warehouse_staff':
            return 'warehouse staff';
        case 'warehouse':
            return 'warehouse';
        case 'itemunitmeasurefile':
            return 'unit of measure';
    }

    $main_header = trim((string)$main_header);
    if($main_header !== ''){
        return strtolower($main_header);
    }

    return strtolower((string)$tablename);
}

function pager_is_masterfile_table($tablename)
{
    return in_array((string)$tablename, array(
        'itemfile',
        'mf_salesman',
        'mf_routes',
        'supplierfile',
        'customerfile',
        'itemunitmeasurefile',
        'expensetypefile',
        'warehouse_staff'
    ), true);
}

function pager_log_field_label($tablename, $fieldname, $field_label = '')
{
    switch((string)$tablename){
        case 'itemfile':
            if($fieldname === 'itmdsc'){
                return 'description';
            }
            if($fieldname === 'wholesaleprc'){
                return 'wholesale price';
            }
            break;
        case 'mf_salesman':
            if($fieldname === 'salesman_name'){
                return 'name';
            }
            if($fieldname === 'commission'){
                return 'commission percentage';
            }
            break;
        case 'itemunitmeasurefile':
            if($fieldname === 'unmdsc'){
                return 'unit of measure';
            }
            break;
    }

    $field_label = str_replace('_crudModal', '', (string)$field_label);
    $field_label = trim($field_label);
    if($field_label !== ''){
        return strtolower($field_label);
    }

    return strtolower(str_replace('_', ' ', (string)$fieldname));
}

function pager_log_format_value($value, $field_type)
{
    if($field_type === 'checkbox'){
        if($value === 1 || $value === '1'){
            return 'checked';
        }
        if($value === 0 || $value === '0'){
            return 'unchecked';
        }
    }

    if($field_type === 'date' && $value !== null && $value !== ''){
        return date("m/d/Y", strtotime((string)$value));
    }

    if($field_type === 'number' && $value !== null && $value !== ''){
        $normalized = rtrim(rtrim(number_format((float)$value, 4, '.', ''), '0'), '.');
        return ($normalized === '') ? '0' : $normalized;
    }

    return (string)$value;
}

function pager_log_values_match($old_value, $new_value, $field_type)
{
    if($field_type === 'number'){
        $old_compare = ($old_value === null || $old_value === '') ? '' : number_format((float)$old_value, 4, '.', '');
        $new_compare = ($new_value === null || $new_value === '') ? '' : number_format((float)$new_value, 4, '.', '');
        return $old_compare === $new_compare;
    }

    return (string)$old_value === (string)$new_value;
}

if($_POST["event_action"] == "delete"){
	$delete_id=$_POST['recid'];

	$select_db_delete="SELECT * FROM ".$_POST['tablename']." where recid=?";
	$stmt_delete	= $link->prepare($select_db_delete);
	$stmt_delete->execute(array($delete_id));
    $rs_delete = $stmt_delete->fetch();

    if(!$rs_delete){
        $xret["status"] = 0;
        $xret["msg"] = "Record not found.";
        header('Content-Type: application/json');
        echo json_encode($xret);
        return;
    }

    if(isset($_POST['ua_field1']) && !empty($_POST["ua_field1"])){
        $ua_field1 = $rs_delete["".$_POST['ua_field1'].""];
    }
    if(isset($_POST["ua_field2"]) && !empty($_POST["ua_field2"])){
        $ua_field2 = $rs_delete["".$_POST['ua_field2'].""];
    }

    // Get docnum from record before deleting - use the fieldcode column (e.g., itmcde, cuscde, docnum)
    $xdocnum = "";
    if(!empty($_POST["fieldcode"]) && isset($rs_delete[$_POST['fieldcode']]) && !empty($rs_delete[$_POST['fieldcode']])){
        $xdocnum = $rs_delete[$_POST['fieldcode']];
    }

	    if($_POST['tablename'] == "itemunitmeasurefile"){
	        $unmcde = isset($rs_delete["unmcde"]) ? $rs_delete["unmcde"] : "";

	        if(itemunitmeasurefile_in_use($link, $unmcde)){
	            $xret["status"] = 0;
	            $xret["msg"] = "Unit of measure in use, cannot modify";
	            header('Content-Type: application/json');
	            echo json_encode($xret);
	            return;
	        }
	    }

    try{
        if($_POST['tablename'] == "warehouse"){
            $warcde = isset($rs_delete["warcde"]) ? $rs_delete["warcde"] : "";
            if($warcde === ""){
                throw new Exception("Warehouse code is missing.");
            }

            $link->beginTransaction();

            $delete_movement_query = "DELETE wsm
                                      FROM warehouse_stock_movement wsm
                                      INNER JOIN warehouse_floor wf
                                        ON wf.warehouse_floor_id = wsm.floor_id
                                      WHERE wf.warcde = ?";
            $stmt_delete_movement = $link->prepare($delete_movement_query);
            $stmt_delete_movement->execute(array($warcde));

            $delete_floor_query = "DELETE FROM warehouse_floor WHERE warcde = ?";
            $stmt_delete_floor = $link->prepare($delete_floor_query);
            $stmt_delete_floor->execute(array($warcde));

            $delete_warehouse_query = "DELETE FROM warehouse WHERE recid = ?";
            $stmt_delete_warehouse = $link->prepare($delete_warehouse_query);
            $stmt_delete_warehouse->execute(array($delete_id));

            $link->commit();
        }else{
	        $delete_query="DELETE FROM ".$_POST['tablename']." WHERE recid=?";
	        $xstmt=$link->prepare($delete_query);
	        $xstmt->execute(array($delete_id));
        }
    }catch(Exception $e){
        if($link->inTransaction()){
            $link->rollBack();
        }

        $xret["status"] = 0;
        if($_POST['tablename'] == "warehouse"){
            $xret["msg"] = "Unable to delete warehouse. Please try again.";
        }else{
            $xret["msg"] = "Unable to delete record. It may be linked to other records.";
        }
        header('Content-Type: application/json');
        echo json_encode($xret);
        return;
    }

	    $xactivity = "delete";
	    if(isset($_POST['ua_field1_header_hidden'])){
	        $_POST['ua_field1_header_hidden'] = $_POST['ua_field1_header_hidden'];
	    }else{
	        $_POST['ua_field1_header_hidden'] = '';
	    }
	    if($_POST['tablename'] == "itemunitmeasurefile"){
	        $xremarks = $username_session . " deleted unit of measure '" . pager_log_display_value(isset($rs_delete['unmdsc']) ? $rs_delete['unmdsc'] : '') . "'";
	    }else if($_POST['tablename'] == "itemunitfile"){
	        $del_uom_desc = pager_item_uom_description($link, isset($rs_delete['unmcde']) ? $rs_delete['unmcde'] : '');
	        $del_item_desc = pager_item_description_from_itmcde($link, isset($rs_delete['itmcde']) ? $rs_delete['itmcde'] : '');
	        $xremarks = $username_session . " deleted uom '" . pager_log_display_value($del_uom_desc) . "' in item - '" . pager_log_display_value($del_item_desc) . "'";
	    }else if($_POST['tablename'] == "warehouse"){
	        $xremarks = "Deleted Record In 'Warehouse', Warehouse Name: '" . pager_log_display_value(isset($rs_delete['warehouse_name']) ? $rs_delete['warehouse_name'] : '') . "'";
	    }else if($_POST['tablename'] == "warehouse_floor"){
	        $del_wh_name = pager_warehouse_name_from_warcde($link, isset($rs_delete['warcde']) ? $rs_delete['warcde'] : '');
	        $del_floor_name = isset($rs_delete['floor_name']) ? $rs_delete['floor_name'] : '';
	        $del_floor_no = isset($rs_delete['floor_no']) ? $rs_delete['floor_no'] : '';
	        $xremarks = "Deleted Record In 'Warehouse Floor', warehouse: '" . pager_log_display_value($del_wh_name) . "', floor name: '" . pager_log_display_value($del_floor_name) . "', floor number: '" . pager_log_display_value($del_floor_no) . "'";
	    }else if(pager_is_masterfile_table($_POST['tablename'])){
	        $xremarks = $username_session . " deleted " . pager_log_context_label($_POST['tablename'], $_POST["main_header"]) . " '" . pager_log_display_value($ua_field1) . "'";
	    }else{
	        $xremarks = "Deleted Record In '".$_POST["main_header"]."', ".$_POST['ua_field1_header_hidden'].": '".$ua_field1."' , Record ID: ".$ua_field2;
	    }

	    //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum, $upload_filename);
	    PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks , 0, '', ($_POST['tablename'] == "itemunitmeasurefile" ? 'UOM' : ''), '','',$username_session, $xdocnum, '');

}

else if($_POST["event_action"] == "getEdit"){

    if($_POST['tablename'] == "itemunitmeasurefile"){
        $select_uom_edit = "SELECT unmcde FROM ".$_POST['tablename']." WHERE recid=?";
        $stmt_uom_edit = $link->prepare($select_uom_edit);
        $stmt_uom_edit->execute(array($_POST["recid"]));
        $rs_uom_edit = $stmt_uom_edit->fetch();

        if($rs_uom_edit && itemunitmeasurefile_in_use($link, $rs_uom_edit["unmcde"])){
            $xret["status"] = 0;
            $xret["msg"] = "Unit of measure in use, cannot modify";
            header('Content-Type: application/json');
            echo json_encode($xret);
            return;
        }
    }
	
	    $xret["retEdit"] = array();
	    $xret["status"] = "retEdit";
    $xcounter_select = 0;
    $fields_select = '';

    foreach($_POST["xdata"] as $key_value_select){

        $fieldname_select = $key_value_select[0]["name"];
        $fieldname_select = str_replace("_crudModal", "",$fieldname_select);

        if($xcounter_select == 0){
            $fields_select = $fieldname_select;
        }else{
            $fields_select .= ",".$fieldname_select;
        }
        $xcounter_select++;
    }
    $fields_select.=",recid";
    
    $retEdit_counter = 0;

    $select_db="SELECT ".$fields_select." FROM ".$_POST['tablename']." WHERE recid=?";
	$stmt	= $link->prepare($select_db);
    $stmt->execute(array($_POST["recid"]));
    while($rs_retEdit = $stmt->fetch()){

        foreach($_POST["xdata"] as $key_value){

            $fieldname = $key_value[0]["name"];
            $fieldname = str_replace("_crudModal", "",$fieldname);
            $field_type = $key_value[6]["data-field-type"];

            if($field_type == "date"){
                if(!empty($rs_retEdit[$fieldname]) && $rs_retEdit[$fieldname] !== NULL &&  $rs_retEdit[$fieldname]!=="1970-01-01"){
                    $rs_retEdit[$fieldname] = date("m-d-Y",strtotime($rs_retEdit[$fieldname]));
                    $rs_retEdit[$fieldname] = str_replace('-','/',$rs_retEdit[$fieldname]);
                }else{
                    $rs_retEdit[$fieldname] = NULL;
                }
            }

            $xret["retEdit"][$retEdit_counter]["field_name"] = $fieldname;
            $xret["retEdit"][$retEdit_counter]["field_value"] = $rs_retEdit[$fieldname];
            $xret["retEdit"][$retEdit_counter]["field_type"] = $key_value[6]["data-field-type"];
            $retEdit_counter++;
        }

        $xret["retEdit"]["recid"] = $_POST["recid"];
    }

}

else if($_POST["event_action"] == "insert")
{

    $arr_record_data = array();
    if(isset($_POST["unique_key"])){
        $unique_key_array = array();
        parse_str($_POST["unique_key"] , $unique_key_array);
    }

    foreach($_POST["xdata"] as $key_value){

        $fieldname              = $key_value[0]["name"];
        $fieldvalue             = $key_value[1]["value"];
        $field_datavalue        = $key_value[2]["data-value"];
        $field_is_required      = $key_value[4]["data-is-required"];
        $field_is_unique        = $key_value[5]["data-is-unique"];
        $field_type             = $key_value[6]["data-field-type"];

        $fieldname              = str_replace("_crudModal", "",$fieldname);
        $field_datavalue        = str_replace("_crudModal", "",$field_datavalue);
        $field_is_required      = str_replace("_crudModal", "",$field_is_required);
        $field_is_unique        = str_replace("_crudModal", "",$field_is_unique);


        if($field_type == "date"){
            $fieldvalue  = (empty($fieldvalue))   ? NULL :  date("Y-m-d", strtotime($fieldvalue));
        }

        if($field_type == "checkbox"){
            $field_checkbox_selected_only_crud = $key_value[7]["data-field-chkbox-selected-only-crud"];
        }else{
            $field_checkbox_selected_only_crud = '';
        }
        
        if($field_is_unique == "Y"){

            $select_db_unique="SELECT ".$fieldname." FROM ".$_POST['tablename']." WHERE ".$fieldname."=?";
            $stmt_unique	= $link->prepare($select_db_unique);
            $stmt_unique->execute(array($fieldvalue));
            $rs_unique = $stmt_unique->fetchAll();

            if(
                ($xerror["error1"] == true || $xerror["error2"] == true || $xerror["error3"] == true) && 
                (count($rs_unique) > 0)
            ){
                
                $xret["msg"] .= "</br>".$field_datavalue." in use.";
                $xret["status"] = 0;
                $xerror["error1"] = true;
                
            }
            else if(
                ($xerror["error1"] !== true || $xerror["error2"] !== true || $xerror["error3"] !==true) && 
                (count($rs_unique) > 0)
            ){
                $xret["msg"] = $field_datavalue." in use.";
                $xret["status"] = 0;
                $xerror["error1"] = true;
            }

        }

        if(
            (empty($fieldvalue)) && 
            ($xerror["error1"] == true || $xerror["error2"] == true || $xerror["error3"] == true) && 
            ($field_is_required == "Y")
        ){
            $xret["msg"] .= "</br> ".$field_datavalue." is required.";
            $xret["status"] = 0;
            $xerror["error2"] = true;
        }else if(
            (empty($fieldvalue)) && 
            ($xerror["error1"] !== true && $xerror["error2"] !== true && $xerror["error3"] !== true) && 
            ($field_is_required == "Y")
        ){
            $xret["msg"] = "".$field_datavalue." is required.";
            $xret["status"] = 0;
            $xerror["error2"] = true;
        }

        if($field_checkbox_selected_only_crud == "1"){

            $select_db_chkbox_one="SELECT * FROM ".$_POST['tablename']." WHERE ".$fieldname."='1'";
            $stmt_chkbox_one	= $link->prepare($select_db_chkbox_one);
            $stmt_chkbox_one->execute();
            $rs_chkbox_one = $stmt_chkbox_one->fetch();

            if(!empty($rs_chkbox_one)){
                if($fieldvalue == 1){
                    if($xerror["error1"] == true || $xerror["error2"] == true || $xerror["error3"] == true){
                        $xret["msg"] .= "</br>";
                    }

                    $xret["msg"] .= "Only one ".$field_datavalue." can be selected";


                    $xerror["error4"] = true;
                    $xret["status"] = 0;
                }
            }
        }


        if($field_type == "number"){
            if(!empty($key_value[7]["data-num-limit"])){
                if(
                    ($fieldvalue >= $key_value[7]["data-num-limit"]) && 
                    ($xerror["error1"] == true|| $xerror["error2"] == true || $xerror["error3"] == true)
                ){
                    $xret["msg"] .= "</br>".$field_datavalue.": ".$fieldvalue." (number) entered is too large.";
                    $xret["status"] = 0;
                    $xerror["error3"] = true;
                }

                else if(
                    ($fieldvalue >= $key_value[7]["data-num-limit"]) && 
                    ($xerror["error1"] !== true && $xerror["error2"] !== true && $xerror["error3"] !== true)
                ){
                    $xret["msg"] = $field_datavalue.": ".$fieldvalue." (number) entered is too large.";
                    $xret["status"] = 0;
                    $xerror["error3"] = true;
                }

            }

            if($fieldvalue == '' && ($field_is_required !== "Y")){
                $fieldvalue = NULL;
            }

        }

        $arr_record_data[$fieldname] 	= $fieldvalue;

        if($_POST["ua_field1"] == $fieldname){
            $ua_field1 =  $fieldvalue;
            $ua_field_header = $field_datavalue;
        }else{
            $ua_field_header = '';
        }
    }

    if($xret["status"] == 1){

        if(
            isset($_POST["table_filter_field"]) &&
            isset($_POST["table_filter_value"]) &&
            preg_match('/^[a-zA-Z0-9_]+$/', $_POST["table_filter_field"]) &&
            $_POST["table_filter_field"] !== '' &&
            $_POST["table_filter_value"] !== ''
        ){
            $arr_record_data[$_POST["table_filter_field"]] = $_POST["table_filter_value"];
        }

        if($_POST["tablename"] == "warehouse_floor"){
            if(
                (!isset($arr_record_data["warcde"]) || $arr_record_data["warcde"] === "" || $arr_record_data["warcde"] === NULL) &&
                isset($_SESSION["warehouse_floor_context_id"]) &&
                $_SESSION["warehouse_floor_context_id"] !== ""
            ){
                $arr_record_data["warcde"] = $_SESSION["warehouse_floor_context_id"];
            }

            if(!isset($arr_record_data["warcde"]) || $arr_record_data["warcde"] === "" || $arr_record_data["warcde"] === NULL){
                $xret["status"] = 0;
                $xret["msg"] = "Warehouse context is missing. Please go back to Warehouse and click Floors again.";
            }
        }

        if($xret["status"] != 1){
            header('Content-Type: application/json');
            echo json_encode($xret);
            return;
        }

        $fieldcode_innit  = "";

        if(!empty($_POST["fieldcode"])){

            if(!empty($_POST["fieldcode_init"])){
                $select_db_fieldcode="SELECT ".$_POST['fieldcode']." FROM ".$_POST['tablename']." ORDER BY ".$_POST['fieldcode']." DESC LIMIT 1";
                $stmt_fieldcode	= $link->prepare($select_db_fieldcode);
                $stmt_fieldcode->execute(array($fieldvalue));
                while($rs_fieldcode = $stmt_fieldcode->fetch()){
                    if(!empty($rs_fieldcode[$_POST["fieldcode"]])){
                        $fieldcode_innit = lNexts($rs_fieldcode[$_POST["fieldcode"]]);
                    }
                };

                if($fieldcode_innit == ""){
                    $fieldcode_innit = $_POST["fieldcode_init"];
                }

            }else{
                $fieldcode_innit = $_POST["fieldcode_init"];
            }

            $arr_record_data[$_POST["fieldcode"]] = $fieldcode_innit;
            if(!empty($_POST["ua_field2"])){
                $ua_field2 = $fieldcode_innit;
            }

        }

        PDO_InsertRecord($link,$_POST["tablename"],$arr_record_data, false);

        // Get the docnum - use the fieldcode value (e.g., itmcde, cuscde, docnum)
        $xdocnum = "";
        // First try to use the generated fieldcode value
        if(!empty($fieldcode_innit)){
            $xdocnum = $fieldcode_innit;
        }
        // If no fieldcode, try to get from the newly inserted record using the fieldcode column name
        else if(!empty($_POST["fieldcode"])){
            $last_insert_id = $link->lastInsertId();
            if($last_insert_id){
                $select_db_newrec = "SELECT ".$_POST['fieldcode']." FROM ".$_POST['tablename']." WHERE recid=?";
                $stmt_newrec = $link->prepare($select_db_newrec);
                $stmt_newrec->execute(array($last_insert_id));
                $rs_newrec = $stmt_newrec->fetch();
                if($rs_newrec && isset($rs_newrec[$_POST['fieldcode']]) && !empty($rs_newrec[$_POST['fieldcode']])){
                    $xdocnum = $rs_newrec[$_POST['fieldcode']];
                }
            }
        }

	        $xactivity = "add";
	        if($_POST['tablename'] == "itemunitmeasurefile"){
	            $xremarks = $username_session . " added unit of measure '" . pager_log_display_value(isset($arr_record_data['unmdsc']) ? $arr_record_data['unmdsc'] : '') . "'";
	        }else if($_POST['tablename'] == "itemunitfile"){
	            $add_uom_desc = pager_item_uom_description($link, isset($arr_record_data['unmcde']) ? $arr_record_data['unmcde'] : '');
	            $add_item_desc = pager_item_description_from_itmcde($link, isset($arr_record_data['itmcde']) ? $arr_record_data['itmcde'] : '');
	            $xremarks = $username_session . " added uom '" . pager_log_display_value($add_uom_desc) . "' in item - '" . pager_log_display_value($add_item_desc) . "'";
	        }else if($_POST['tablename'] == "warehouse"){
	            $xremarks = "Added Record In 'Warehouse', : warehouse name: '" . pager_log_display_value(isset($arr_record_data['warehouse_name']) ? $arr_record_data['warehouse_name'] : '') . "', location: '" . pager_log_display_value(isset($arr_record_data['location']) ? $arr_record_data['location'] : '') . "'";
	        }else if($_POST['tablename'] == "warehouse_floor"){
	            $add_wh_name = pager_warehouse_name_from_warcde($link, isset($arr_record_data['warcde']) ? $arr_record_data['warcde'] : '');
	            $add_floor_name = isset($arr_record_data['floor_name']) ? $arr_record_data['floor_name'] : '';
	            $add_floor_no = isset($arr_record_data['floor_no']) ? $arr_record_data['floor_no'] : '';
	            $xremarks = "Added Record In 'Warehouse Floor', : warehouse: '" . pager_log_display_value($add_wh_name) . "', floor name: '" . pager_log_display_value($add_floor_name) . "', floor number: '" . pager_log_display_value($add_floor_no) . "'";
	        }else if(pager_is_masterfile_table($_POST['tablename'])){
	            $xremarks = $username_session . " added " . pager_log_context_label($_POST['tablename'], $_POST["main_header"]) . " '" . pager_log_display_value($ua_field1) . "'";
	        }else{
	            $xremarks = "Added Record In '".$_POST["main_header"]."', ".$ua_field_header.": '".$ua_field1."' , Record ID: ".$ua_field2;
	        }

	        //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum, $upload_filename);
	        PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks , 0, '', ($_POST['tablename'] == "itemunitmeasurefile" ? 'UOM' : ''), '','',$username_session, $xdocnum, '');
	    }
}

else if($_POST["event_action"] == "submitEdit")
{

	    $arr_record_data = array();
	    $rs_editcode_before = null;
        $edit_field_changes = array();

    if($_POST['tablename'] == "itemunitmeasurefile"){
        $select_uom_submit = "SELECT unmcde FROM ".$_POST['tablename']." WHERE recid=?";
        $stmt_uom_submit = $link->prepare($select_uom_submit);
        $stmt_uom_submit->execute(array($_POST["recid_edit"]));
        $rs_uom_submit = $stmt_uom_submit->fetch();

        if($rs_uom_submit && itemunitmeasurefile_in_use($link, $rs_uom_submit["unmcde"])){
            $xret["status"] = 0;
            $xret["msg"] = "Unit of measure in use, cannot modify";
            header('Content-Type: application/json');
            echo json_encode($xret);
            return;
        }
    }

	    if(isset($_POST["unique_key"])){
	        $unique_key_array = array();
	        parse_str($_POST["unique_key"] , $unique_key_array);
	    }

	    if($_POST['tablename'] == "itemunitmeasurefile" || $_POST['tablename'] == "itemunitfile" || $_POST['tablename'] == "warehouse_floor"){
	        $select_db_before_edit = "SELECT * FROM ".$_POST['tablename']." WHERE recid=?";
	        $stmt_before_edit = $link->prepare($select_db_before_edit);
	        $stmt_before_edit->execute(array($_POST["recid_edit"]));
	        $rs_editcode_before = $stmt_before_edit->fetch();
	    }

    foreach($_POST["xdata"] as $key_value){

        $fieldname              = $key_value[0]["name"];
        $fieldvalue             = $key_value[1]["value"];
        $field_datavalue        = $key_value[2]["data-value"];
        if(isset($key_value[3]["data-value-hidden"])){
            $field_datavalue_hidden = $key_value[3]["data-value-hidden"];
        }else{
            $field_datavalue_hidden = '';
        }

        $field_is_required      = $key_value[4]["data-is-required"];
        $field_is_unique        = $key_value[5]["data-is-unique"];
        $field_type             = $key_value[6]["data-field-type"];

        $fieldname = str_replace("_crudModal", "",$fieldname);
        $field_datavalue = str_replace("_crudModal", "",$field_datavalue);
        $field_is_required = str_replace("_crudModal", "",$field_is_required);
        $field_is_unique = str_replace("_crudModal", "",$field_is_unique);

        if($field_type == "date"){
            $fieldvalue  = (empty($fieldvalue))   ? NULL :  date("Y-m-d", strtotime($fieldvalue));
            $field_datavalue_hidden  = (empty($field_datavalue_hidden))   ? NULL :  date("Y-m-d", strtotime($field_datavalue_hidden));
        }

        if($field_type == "checkbox"){
            $field_checkbox_selected_only_crud = $key_value[7]["data-field-chkbox-selected-only-crud"];
        }else{
            $field_checkbox_selected_only_crud = '';
        }        

        if(($field_is_unique == "Y") && ($field_datavalue_hidden !== $fieldvalue)){

            $select_db_unique="SELECT ".$fieldname." FROM ".$_POST['tablename']." WHERE ".$fieldname."=?";
            $stmt_unique	= $link->prepare($select_db_unique);
            $stmt_unique->execute(array($fieldvalue));
            $rs_unique = $stmt_unique->fetchAll();

            if(
                ($xerror["error1"] == true || $xerror["error2"] == true || $xerror["error3"] == true) && 
                (count($rs_unique) > 0)
            ){
                
                $xret["msg"] .= "</br>".$field_datavalue." in use.";
                $xret["status"] = 0;
                $xerror["error1"] = true;
                
            }
            else if(
                ($xerror["error1"] !== true && $xerror["error2"] !== true && $xerror["error3"] !== true) && 
                (count($rs_unique) > 0)
            ){
                $xret["msg"] = $field_datavalue." in use.";
                $xret["status"] = 0;
                $xerror["error1"] = true;
            }

        }

        if(
            (empty($fieldvalue)) && 
            ($xerror["error1"] == true || $xerror["error2"] == true || $xerror["error3"] == true) && 
            ($field_is_required == "Y")
        ){
            $xret["msg"] .= "</br> ".$field_datavalue." is required.";
            $xret["status"] = 0;
            $xerror["error2"] = true;
        }else if(
            (empty($fieldvalue)) && 
            ($xerror["error1"] !== true && $xerror["error2"] !== true && $xerror["error3"] !== true) && 
            ($field_is_required == "Y")
        ){
            $xret["msg"] = "".$field_datavalue." is required.";
            $xret["status"] = 0;
            $xerror["error2"] = true;
        }

        if($field_checkbox_selected_only_crud == "1"){

            $select_db_chkbox_one="SELECT * FROM ".$_POST['tablename']." WHERE ".$fieldname."='1' LIMIT 1";
            $stmt_chkbox_one	= $link->prepare($select_db_chkbox_one);
            $stmt_chkbox_one->execute();
            $rs_chkbox_one = $stmt_chkbox_one->fetch();

            if(!empty($rs_chkbox_one)){
                if($fieldvalue == 1 && ($_POST["recid_edit"]!=$rs_chkbox_one['recid'])){
                    if($xerror["error1"] == true || $xerror["error2"] == true || $xerror["error3"] == true){
                        $xret["msg"] .= "</br>";
                    }

                    $xret["msg"] .= "Only one ".$field_datavalue." can be selected";


                    $xerror["error4"] = true;
                    $xret["status"] = 0;
                }
            }
        }        



        if($field_type == "number"){
            if(!empty($key_value[7]["data-num-limit"])){
                if(
                    ($fieldvalue >= $key_value[7]["data-num-limit"]) && 
                    ($xerror["error1"] == true|| $xerror["error2"] == true || $xerror["error3"] == true)
                ){
                    $xret["msg"] .= "</br>".$field_datavalue." ".$fieldvalue." (number) entered is too large.";
                    $xret["status"] = 0;
                    $xerror["error3"] = true;
                }

                else if(
                    ($fieldvalue >= $key_value[7]["data-num-limit"]) && 
                    ($xerror["error1"] !== true && $xerror["error2"] !== true && $xerror["error3"] !== true)
                ){
                    $xret["msg"] = $field_datavalue." ".$fieldvalue." (number) entered is too large.";
                    $xret["status"] = 0;
                    $xerror["error3"] = true;
                }

            }

            if($fieldvalue == '' && ($field_is_required !== "Y")){
                $fieldvalue = NULL;
            }

        }

	        if($_POST["ua_field1"] == $fieldname){
	            $ua_field1 =  $fieldvalue;
	            $ua_field_header = $field_datavalue;
	        }

            if(
                $_POST['tablename'] !== "itemunitmeasurefile" &&
                !pager_log_values_match($field_datavalue_hidden, $fieldvalue, $field_type)
            ){
                $edit_field_changes[] = pager_log_field_label($_POST['tablename'], $fieldname, $field_datavalue)
                    . " from '" . pager_log_display_value(pager_log_format_value($field_datavalue_hidden, $field_type)) . "' to '"
                    . pager_log_display_value(pager_log_format_value($fieldvalue, $field_type)) . "'";
            }

	        $fieldname = str_replace("_crudModal", "",$fieldname);
	        $arr_record_data[$fieldname] 	= $fieldvalue;

    }

    if($xret["status"] == 1){

        PDO_UpdateRecord($link,$_POST["tablename"],$arr_record_data,"recid = ?",array($_POST["recid_edit"]),false);

        $select_db_editcode="SELECT * FROM ".$_POST['tablename']." where recid=?";
        $stmt_editcode	= $link->prepare($select_db_editcode);
        $stmt_editcode->execute(array($_POST["recid_edit"]));
        $rs_editcode = $stmt_editcode->fetch();

        if(isset($_POST["ua_field2"]) &&  !empty($_POST['ua_field2'])){
            $ua_field2 = $rs_editcode["".$_POST['ua_field2'].""];
        }

        // Get docnum from the edited record - use the fieldcode column (e.g., itmcde, cuscde, docnum)
        $xdocnum = "";
        if(!empty($_POST["fieldcode"]) && isset($rs_editcode[$_POST['fieldcode']]) && !empty($rs_editcode[$_POST['fieldcode']])){
            $xdocnum = $rs_editcode[$_POST['fieldcode']];
        }

	        $ua_field1_old = $_POST["ua_field1_hidden_modal"];

	        $xactivity = "edit";
	        $xremarks = "Updated Record In '".$_POST["main_header"]."', FROM: '".$ua_field1_old."' TO: '".$ua_field1."' , Record ID: ".$ua_field2;
	        $should_log_activity = true;
	        if($_POST['tablename'] == "itemunitmeasurefile"){
	            $uom_changes = array();
	            $old_unmdsc = isset($rs_editcode_before['unmdsc']) ? $rs_editcode_before['unmdsc'] : '';
	            $new_unmdsc = isset($rs_editcode['unmdsc']) ? $rs_editcode['unmdsc'] : '';
	            if((string)$old_unmdsc !== (string)$new_unmdsc){
	                $uom_changes[] = "unit of measure from '" . pager_log_display_value($old_unmdsc) . "' to '" . pager_log_display_value($new_unmdsc) . "'";
	            }

	            if(!empty($uom_changes)){
	                $xremarks = $username_session . " edited " . implode(', ', $uom_changes);
	            }else{
	                $should_log_activity = false;
	            }
	        }else if($_POST['tablename'] == "itemunitfile"){
                $old_uom_description = pager_item_uom_description($link, isset($rs_editcode_before['unmcde']) ? $rs_editcode_before['unmcde'] : '');
                $new_uom_description = pager_item_uom_description($link, isset($rs_editcode['unmcde']) ? $rs_editcode['unmcde'] : '');
                $old_conversion = isset($rs_editcode_before['conversion']) ? $rs_editcode_before['conversion'] : '';
                $new_conversion = isset($rs_editcode['conversion']) ? $rs_editcode['conversion'] : '';
                $edit_item_desc = pager_item_description_from_itmcde($link, isset($rs_editcode['itmcde']) ? $rs_editcode['itmcde'] : '');

                $uom_name_changed = ((string)$old_uom_description !== (string)$new_uom_description);
                $conversion_changed = !pager_log_values_match($old_conversion, $new_conversion, 'number');

                if($uom_name_changed && $conversion_changed){
                    // CASE 3: Both UOM name and conversion changed
                    $xremarks = $username_session . " updated uom from '" . pager_log_display_value($old_uom_description) . "' to '" . pager_log_display_value($new_uom_description) . "', conversion from " . pager_log_display_value(pager_log_format_value($old_conversion, 'number')) . " to " . pager_log_display_value(pager_log_format_value($new_conversion, 'number')) . " in item - '" . pager_log_display_value($edit_item_desc) . "'";
                }else if($uom_name_changed){
                    // CASE 1: Only UOM name changed
                    $xremarks = $username_session . " updated uom from '" . pager_log_display_value($old_uom_description) . "' to '" . pager_log_display_value($new_uom_description) . "' in item - '" . pager_log_display_value($edit_item_desc) . "'";
                }else if($conversion_changed){
                    // CASE 2: Only conversion changed
                    $xremarks = $username_session . " edited uom '" . pager_log_display_value($new_uom_description) . "' from conversion: " . pager_log_display_value(pager_log_format_value($old_conversion, 'number')) . " to conversion: " . pager_log_display_value(pager_log_format_value($new_conversion, 'number')) . " in item - '" . pager_log_display_value($edit_item_desc) . "'";
                }else{
                    $should_log_activity = false;
                }
	        }else if($_POST['tablename'] == "warehouse_floor"){
                $edit_wh_name = pager_warehouse_name_from_warcde($link, isset($rs_editcode['warcde']) ? $rs_editcode['warcde'] : '');
                $old_floor_name = isset($rs_editcode_before['floor_name']) ? $rs_editcode_before['floor_name'] : '';
                $new_floor_name = isset($rs_editcode['floor_name']) ? $rs_editcode['floor_name'] : '';
                $old_floor_no = isset($rs_editcode_before['floor_no']) ? $rs_editcode_before['floor_no'] : '';
                $new_floor_no = isset($rs_editcode['floor_no']) ? $rs_editcode['floor_no'] : '';

                $floor_name_changed = ((string)$old_floor_name !== (string)$new_floor_name);
                $floor_no_changed = ((string)$old_floor_no !== (string)$new_floor_no);

                $floor_changes = array();
                if($floor_name_changed){
                    $floor_changes[] = "floor name from '" . pager_log_display_value($old_floor_name) . "' to '" . pager_log_display_value($new_floor_name) . "'";
                }
                if($floor_no_changed){
                    $floor_changes[] = "floor number from '" . pager_log_display_value($old_floor_no) . "' to '" . pager_log_display_value($new_floor_no) . "'";
                }

                if(!empty($floor_changes)){
                    $xremarks = "Updated Record In 'Warehouse Floor', warehouse: '" . pager_log_display_value($edit_wh_name) . "', " . implode(', ', $floor_changes);
                }else{
                    $should_log_activity = false;
                }
	        }else if(!empty($edit_field_changes)){
                $xremarks = $username_session . " updated " . pager_log_context_label($_POST['tablename'], $_POST["main_header"]) . ": " . implode(', ', $edit_field_changes);
            }else{
                $should_log_activity = false;
	        }

	        //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum, $upload_filename);
	        if($should_log_activity){
	            PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks , 0, '', ($_POST['tablename'] == "itemunitmeasurefile" ? 'UOM' : ''), '','',$username_session, $xdocnum, '');
	        }
	    }
}

header('Content-Type: application/json');
echo json_encode($xret);
?>
