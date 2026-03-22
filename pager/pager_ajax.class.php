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
    $xremarks = "Deleted Record In '".$_POST["main_header"]."', ".$_POST['ua_field1_header_hidden'].": '".$ua_field1."' , Record ID: ".$ua_field2;

    //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum, $upload_filename);
    PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks , 0, '', '', '','',$username_session, $xdocnum, '');

}

else if($_POST["event_action"] == "getEdit"){

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
        $xremarks = "Added Record In '".$_POST["main_header"]."', ".$ua_field_header.": '".$ua_field1."' , Record ID: ".$ua_field2;

        //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum, $upload_filename);
        PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks , 0, '', '', '','',$username_session, $xdocnum, '');
    }
}

else if($_POST["event_action"] == "submitEdit")
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

        //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme, $docnum, $upload_filename);
        PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks , 0, '', '', '','',$username_session, $xdocnum, '');
    }
}

header('Content-Type: application/json');
echo json_encode($xret);
?>
