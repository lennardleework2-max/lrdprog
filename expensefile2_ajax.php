<?php
    // ini_set('display_errors', '1');
    // ini_set('display_startup_errors', '1');
    // error_reporting(E_ALL);     

	    session_start();
	    require_once("resources/db_init.php");
	    require "resources/connect4.php";
	    require "resources/stdfunc100.php";
	    require "resources/lx2.pdodb.php";

	    // Activity logging variables
	    $log_username = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
	    $log_fullname = '';
	    if(isset($_SESSION['recid'])){
	        $select_log_user = 'SELECT full_name FROM users WHERE recid = ?';
	        $stmt_log_user = $link->prepare($select_log_user);
	        $stmt_log_user->execute(array($_SESSION['recid']));
	        $rs_log_user = $stmt_log_user->fetch();
	        if($rs_log_user){
	            $log_fullname = $rs_log_user['full_name'];
	        }
	    }
	    $log_module = 'EXPENSE';
	    $log_trndte = date('Y-m-d H:i:s');

	    function expense_lookup_description($link, $table, $code_field, $desc_field, $code_value){
	        $code_value = trim((string)$code_value);
	        if($code_value === ''){
	            return '';
	        }

	        $select_lookup = "SELECT ".$desc_field." FROM ".$table." WHERE ".$code_field." = ? LIMIT 1";
	        $stmt_lookup = $link->prepare($select_lookup);
	        $stmt_lookup->execute(array($code_value));
	        $rs_lookup = $stmt_lookup->fetch();

	        if($rs_lookup && isset($rs_lookup[$desc_field])){
	            return trim((string)$rs_lookup[$desc_field]);
	        }

	        return $code_value;
	    }

	    function expense_display_value($value){
	        if($value === null){
	            return '(blank)';
	        }

	        $value = trim((string)$value);
	        return ($value === '') ? '(blank)' : $value;
	    }

	    function expense_format_log_date($value){
	        if(empty($value)){
	            return '';
	        }

	        return date("m/d/Y", strtotime($value));
	    }

	    function expense_format_log_amount($value){
	        if($value === null || $value === ''){
	            return '';
	        }

	        return number_format((float)$value, 2);
	    }

	    $xret = array();
    $xret["html"] = "";
    $xret["html_mobile"] = "";
    $xret["trntot"] = "";
    $xret["msg"] = "";
    $xret["status"] = 1;
    $xret["error1"] = 0;
    $xret["error2"] = 0;
    $xret["error3"] = 0;
    $xret["retEdit"] = array();

    $current_usercode = '';
    if(isset($_POST['usercode_1']) && trim((string)$_POST['usercode_1']) !== ''){
        $current_usercode = trim((string)$_POST['usercode_1']);
    }else if(isset($_SESSION['usercode']) && trim((string)$_SESSION['usercode']) !== ''){
        $current_usercode = trim((string)$_SESSION['usercode']);
    }

    if(isset($_POST['docnum'])){
        $docnum = $_POST['docnum'];
    }else{
        $_POST['docnum'] = '';
        $docnum = '';
    }

    $trncde = $_POST["trncde"];    
    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "save_exit" || $_POST["event_action"] == "save_new")){

        $docnum = $_POST["docnum_1"];

        $select_docnum='SELECT * FROM expensefile1 WHERE docnum=?';
        $stmt_docnum	= $link->prepare($select_docnum);
        $stmt_docnum->execute(array($docnum));
        $rs_docnum = $stmt_docnum->fetch();

        if(empty($rs_docnum)){

            if(empty($_POST['trndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
            }else{
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));

                $arr_record = array();

                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);

                $arr_record['docnum'] 	= $docnum;
                $arr_record['trndte'] 	= $_POST['trndte_1'];
                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['vat_cde'] 	= $_POST['vat_type1'];
	                $arr_record['expense_cde'] 	= $_POST['expense_type1'];
	                $arr_record['remarks'] 	= $_POST['remarks_1'];
                    $arr_record['usercode'] = $current_usercode;

	                PDO_InsertRecord($link,'expensefile1',$arr_record, false);

	                $log_expense_desc = expense_lookup_description($link, 'expensetypefile', 'expense_cde', 'expense_dsc', $_POST['expense_type1']);
	                $log_remarks = $log_username . " added expense '" . expense_display_value($log_expense_desc) . "' in docnum='" . $docnum . "'";
	                PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'EXP', '', '', $log_username, $docnum, '');
	    
	                if($_POST["event_action"] == "save_exit"){
	                    $xret["msg"] = "save_exit";
                }
            }

        }else{
            if(empty($_POST['trndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
            }else{

                $xret["msg"] = "edit_exit";

                $recid = $rs_docnum['recid'];
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));

                $arr_record_update = array();

                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);

                $arr_record_update['docnum'] 	= $docnum;
                $arr_record_update['trndte'] 	= $_POST['trndte_1'];
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['vat_cde'] 	= $_POST['vat_type1'];
	                $arr_record_update['expense_cde'] 	= $_POST['expense_type1'];
	                $arr_record_update['remarks'] 	= $_POST['remarks_1'];

	                $log_changes = array();
	                $old_expense_desc = expense_lookup_description($link, 'expensetypefile', 'expense_cde', 'expense_dsc', $rs_docnum['expense_cde']);
	                $new_expense_desc = expense_lookup_description($link, 'expensetypefile', 'expense_cde', 'expense_dsc', $_POST['expense_type1']);
	                if((string)$rs_docnum['expense_cde'] !== (string)$_POST['expense_type1']){
	                    $log_changes[] = "expense type from '" . expense_display_value($old_expense_desc) . "' to '" . expense_display_value($new_expense_desc) . "'";
	                }

	                $old_vat_desc = expense_lookup_description($link, 'vat_typefile', 'vat_cde', 'vat_dsc', $rs_docnum['vat_cde']);
	                $new_vat_desc = expense_lookup_description($link, 'vat_typefile', 'vat_cde', 'vat_dsc', $_POST['vat_type1']);
	                if((string)$rs_docnum['vat_cde'] !== (string)$_POST['vat_type1']){
	                    $log_changes[] = "vat type from '" . expense_display_value($old_vat_desc) . "' to '" . expense_display_value($new_vat_desc) . "'";
	                }

	                $old_trndte_log = expense_format_log_date($rs_docnum['trndte']);
	                $new_trndte_log = expense_format_log_date($_POST['trndte_1']);
	                if((string)$rs_docnum['trndte'] !== (string)$_POST['trndte_1']){
	                    $log_changes[] = "tran. date from '" . expense_display_value($old_trndte_log) . "' to '" . expense_display_value($new_trndte_log) . "'";
	                }

	                $old_trntot_log = expense_format_log_amount($rs_docnum['trntot']);
	                $new_trntot_log = expense_format_log_amount($_POST['trntot_1']);
	                $old_trntot_compare = ($rs_docnum['trntot'] === null || $rs_docnum['trntot'] === '') ? '' : number_format((float)$rs_docnum['trntot'], 2, '.', '');
	                $new_trntot_compare = ($_POST['trntot_1'] === null || $_POST['trntot_1'] === '') ? '' : number_format((float)$_POST['trntot_1'], 2, '.', '');
	                if($old_trntot_compare !== $new_trntot_compare){
	                    $log_changes[] = "amount from '" . expense_display_value($old_trntot_log) . "' to '" . expense_display_value($new_trntot_log) . "'";
	                }

	                if((string)$rs_docnum['remarks'] !== (string)$_POST['remarks_1']){
	                    $log_changes[] = "remarks from '" . expense_display_value($rs_docnum['remarks']) . "' to '" . expense_display_value($_POST['remarks_1']) . "'";
	                }

	                PDO_UpdateRecord($link,"expensefile1",$arr_record_update,"recid = ?",array($recid),false); 

	                if(!empty($log_changes)){
	                    $log_expense_context = ($new_expense_desc !== '') ? $new_expense_desc : $old_expense_desc;
	                    $log_remarks = $log_username . " edited expense '" . expense_display_value($log_expense_context) . "' in docnum='" . $docnum . "': " . implode(', ', $log_changes);
	                    PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'EXP', '', '', $log_username, $docnum, '');
	                }
	            }

  
        }

        
        if($_POST["event_action"] == "save_new" && $xret["status"] == 1){

            $select_db_docnum='SELECT * FROM expensefile1 ORDER BY docnum DESC LIMIT 1';
            $stmdt_docnum	= $link->prepare($select_db_docnum);
            $stmt_docnum->execute();
            while($rs_docnum2 = $stmt_docnum->fetch()){
                $xret["new_docnum"]  = Lnexts($rs_docnum2['docnum']);
            }
            $docnum =  $xret["new_docnum"];

            if(empty($rs_docnum)){
                $xret["msg"] = "save_new_last";
            }else{
                $xret["msg"] = "save_new_same";
            }
            
        }

    }


echo json_encode($xret);
?>
