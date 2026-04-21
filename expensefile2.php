<?php 
if(isset($_POST['expense_header_log_action']) && $_POST['expense_header_log_action'] !== ''){
    session_start();
    require_once("resources/db_init.php");
    require "resources/connect4.php";
    require "resources/stdfunc100.php";
    require "resources/lx2.pdodb.php";

    function expensefile2_log_lookup_description($link, $table, $code_field, $desc_field, $code_value){
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

    function expensefile2_log_display_value($value){
        if($value === null){
            return '(blank)';
        }

        $value = trim((string)$value);
        return ($value === '') ? '(blank)' : $value;
    }

    function expensefile2_log_normalize_amount($value){
        $value = str_replace(',', '', trim((string)$value));
        if($value === '' || !is_numeric($value)){
            return '';
        }

        return number_format((float)$value, 2, '.', '');
    }

    function expensefile2_log_amount($value){
        $normalized = expensefile2_log_normalize_amount($value);
        if($normalized === ''){
            return '';
        }

        $formatted = rtrim(rtrim($normalized, '0'), '.');
        return ($formatted === '-0') ? '0' : $formatted;
    }

    function expensefile2_log_normalize_date($value){
        $value = trim((string)$value);
        if($value === ''){
            return '';
        }

        $timestamp = strtotime($value);
        if($timestamp === false){
            return $value;
        }

        return date('Y-m-d', $timestamp);
    }

    $xret = array();
    $xret["status"] = 1;
    $xret["msg"] = "";

    if($_POST['expense_header_log_action'] === 'update_latest_remark'){
        $activity = isset($_POST['activity']) ? trim((string)$_POST['activity']) : '';
        $docnum = isset($_POST['docnum']) ? trim((string)$_POST['docnum']) : '';
        $username_session = useractivitylog_get_session_username();
        $remarks = '';

        $old_expense_cde = isset($_POST['old_expense_cde']) ? trim((string)$_POST['old_expense_cde']) : '';
        $new_expense_cde = isset($_POST['new_expense_cde']) ? trim((string)$_POST['new_expense_cde']) : '';
        $old_vat_cde = isset($_POST['old_vat_cde']) ? trim((string)$_POST['old_vat_cde']) : '';
        $new_vat_cde = isset($_POST['new_vat_cde']) ? trim((string)$_POST['new_vat_cde']) : '';
        $old_trndte = isset($_POST['old_trndte']) ? trim((string)$_POST['old_trndte']) : '';
        $new_trndte = isset($_POST['new_trndte']) ? trim((string)$_POST['new_trndte']) : '';
        $old_trntot = isset($_POST['old_trntot']) ? trim((string)$_POST['old_trntot']) : '';
        $new_trntot = isset($_POST['new_trntot']) ? trim((string)$_POST['new_trntot']) : '';
        $old_remarks = isset($_POST['old_remarks']) ? trim((string)$_POST['old_remarks']) : '';
        $new_remarks = isset($_POST['new_remarks']) ? trim((string)$_POST['new_remarks']) : '';

        $old_expense_name = expensefile2_log_lookup_description($link, 'expensetypefile', 'expense_cde', 'expense_dsc', $old_expense_cde);
        $new_expense_name = expensefile2_log_lookup_description($link, 'expensetypefile', 'expense_cde', 'expense_dsc', $new_expense_cde);

        if($activity === 'add'){
            $remarks = $username_session . " added expense '" . useractivitylog_escape_value(expensefile2_log_display_value($new_expense_name)) . "' amount: '" . useractivitylog_escape_value(expensefile2_log_display_value(expensefile2_log_amount($new_trntot))) . "' in docnum='" . useractivitylog_escape_value($docnum) . "'";
        }else if($activity === 'edit'){
            $log_changes = array();

            if($old_expense_cde !== $new_expense_cde){
                $log_changes[] = "expense name from '" . useractivitylog_escape_value(expensefile2_log_display_value($old_expense_name)) . "' to '" . useractivitylog_escape_value(expensefile2_log_display_value($new_expense_name)) . "'";
            }

            $old_vat_name = expensefile2_log_lookup_description($link, 'vat_typefile', 'vat_cde', 'vat_dsc', $old_vat_cde);
            $new_vat_name = expensefile2_log_lookup_description($link, 'vat_typefile', 'vat_cde', 'vat_dsc', $new_vat_cde);
            if($old_vat_cde !== $new_vat_cde){
                $log_changes[] = "vat type from '" . useractivitylog_escape_value(expensefile2_log_display_value($old_vat_name)) . "' to '" . useractivitylog_escape_value(expensefile2_log_display_value($new_vat_name)) . "'";
            }

            $old_trndte_compare = expensefile2_log_normalize_date($old_trndte);
            $new_trndte_compare = expensefile2_log_normalize_date($new_trndte);
            if($old_trndte_compare !== $new_trndte_compare){
                $log_changes[] = "date from '" . useractivitylog_escape_value(expensefile2_log_display_value($old_trndte_compare)) . "' to '" . useractivitylog_escape_value(expensefile2_log_display_value($new_trndte_compare)) . "'";
            }

            $old_trntot_compare = expensefile2_log_normalize_amount($old_trntot);
            $new_trntot_compare = expensefile2_log_normalize_amount($new_trntot);
            if($old_trntot_compare !== $new_trntot_compare){
                $log_changes[] = "amount from '" . useractivitylog_escape_value(expensefile2_log_display_value(expensefile2_log_amount($old_trntot))) . "' to '" . useractivitylog_escape_value(expensefile2_log_display_value(expensefile2_log_amount($new_trntot))) . "'";
            }

            if($old_remarks !== $new_remarks){
                $log_changes[] = "description from '" . useractivitylog_escape_value(expensefile2_log_display_value($old_remarks)) . "' to '" . useractivitylog_escape_value(expensefile2_log_display_value($new_remarks)) . "'";
            }

            if(!empty($log_changes)){
                $expense_context = ($old_expense_name !== '') ? $old_expense_name : $new_expense_name;
                $remarks = $username_session . " edited expense '" . useractivitylog_escape_value(expensefile2_log_display_value($expense_context)) . "' in docnum='" . useractivitylog_escape_value($docnum) . "': " . implode(', ', $log_changes);
            }
        }

        if($remarks !== '' && $username_session !== '' && $docnum !== ''){
            $select_log = "SELECT recid
                           FROM useractivitylogfile
                           WHERE module = 'EXPENSE'
                           AND activity = ?
                           AND usrname = ?
                           AND docnum = ?
                           ORDER BY recid DESC
                           LIMIT 1";
            $stmt_log = $link->prepare($select_log);
            $stmt_log->execute(array($activity, $username_session, $docnum));
            $rs_log = $stmt_log->fetch();

            if($rs_log && !empty($rs_log['recid'])){
                $update_log = "UPDATE useractivitylogfile SET remarks = ? WHERE recid = ?";
                $stmt_update_log = $link->prepare($update_log);
                $stmt_update_log->execute(array($remarks, $rs_log['recid']));
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($xret);
    exit;
}

require "includes/main_header.php";

$header_usercode = '';
$is_edit_mode = false;

if(isset($_POST['recid_hidden']) && !empty($_POST['recid_hidden'])){
    $is_edit_mode = true;
    $expense_log_initial_exists = true;
    $expense_log_initial_docnum = '';
    $expense_log_initial_trndte = '';
    $expense_log_initial_trntot = '';
    $expense_log_initial_remarks = '';
    $expense_log_initial_vat_cde = '';
    $expense_log_initial_expense_cde = '';

    $select_db_docnum1='SELECT * FROM expensefile1 WHERE recid=?';
    $stmt_docnum1	= $link->prepare($select_db_docnum1);
    $stmt_docnum1->execute(array($_POST["recid_hidden"]));

    while($rs_docnum1 = $stmt_docnum1->fetch()){

        if(!empty($rs_docnum1['trndte'])){
            $rs_docnum1['trndte'] = date("m-d-Y",strtotime($rs_docnum1['trndte']));
            $rs_docnum1['trndte'] = str_replace('-','/',$rs_docnum1['trndte']);
        }else{
            $rs_docnum1['trndte'] = NULL;
        }

        if(isset($rs_docnum1['trntot'])){
            $rs_docnum1['trntot'] = number_format($rs_docnum1['trntot'],2);
        }

        $docnum  = $rs_docnum1['docnum'];
        $trndate  = $rs_docnum1['trndte'];
        $trntot  = $rs_docnum1['trntot'];
        $remarks  = $rs_docnum1['remarks'];
        $vattype  = $rs_docnum1['vat_cde'];
        $expensetype  = $rs_docnum1['expense_cde'];
        $header_usercode = isset($rs_docnum1['usercode']) ? trim((string)$rs_docnum1['usercode']) : '';

        $expense_log_initial_docnum = $rs_docnum1['docnum'];
        $expense_log_initial_trndte = !empty($rs_docnum1['trndte']) ? date("Y-m-d", strtotime($rs_docnum1['trndte'])) : '';
        $expense_log_initial_trntot = isset($rs_docnum1['trntot']) ? str_replace(',', '', $trntot) : '';
        $expense_log_initial_remarks = $remarks;
        $expense_log_initial_vat_cde = $vattype;
        $expense_log_initial_expense_cde = $expensetype;
    }
}else{
    $expense_log_initial_exists = false;
    $expense_log_initial_docnum = '';
    $expense_log_initial_trndte = '';
    $expense_log_initial_trntot = '';
    $expense_log_initial_remarks = '';
    $expense_log_initial_vat_cde = '';
    $expense_log_initial_expense_cde = '';

    $select_db_docnum="SELECT * FROM expensefile1 ORDER BY docnum DESC LIMIT 1";
    $stmt_docnum	= $link->prepare($select_db_docnum);
    $stmt_docnum->execute();
    $rs_docnum = $stmt_docnum->fetch();
    
    $docnum  = Lnexts($rs_docnum['docnum']);
    if(empty($rs_docnum)){
        $docnum  = "EXP-00001";
    }

    date_default_timezone_set('Asia/Manila');

    $trndate = date('m/d/Y');
    $trntot = '';
    $remarks  = '';
    $vattype  = '';
    $expensetype  = '';

}

$session_usercode = '';
if(isset($_SESSION['usercode']) && trim((string)$_SESSION['usercode']) !== ''){
    $session_usercode = trim((string)$_SESSION['usercode']);
}else if(isset($_POST["usercode_hidden"]) && trim((string)$_POST["usercode_hidden"]) !== ''){
    $session_usercode = trim((string)$_POST["usercode_hidden"]);
}

$display_usercode = $is_edit_mode ? $header_usercode : $session_usercode;
$display_userdesc = '';
if($display_usercode !== ''){
    $select_user = "SELECT userdesc FROM users WHERE usercode = ? LIMIT 1";
    $stmt_user = $link->prepare($select_user);
    $stmt_user->execute(array($display_usercode));
    $rs_user = $stmt_user->fetch();
    if(!empty($rs_user) && isset($rs_user['userdesc'])){
        $display_userdesc = $rs_user['userdesc'];
    }
}

?>

        <style>
    
        @media only screen and (min-width: 576px) {

            #btns_useraccess{
                display:flex;
                align-items:center;
                justify-content:flex-end;
            }
        }
        @media only screen and (max-width: 576px) {
            #btns_useraccess{
                justify-content:center;
            }
        }

        @media (max-width: 768px) {
            #file2_table {
                flex: 1 1 auto;
            }
            /* 
            #tbody_main_mobile {
                display: block!important;
            } */

            #tbody_main{
                display: none;
            }

            #tbody_selectpo{
                display: none;      
            }
        }    

        @media screen and (min-width: 576px) {
            #tbody_selectpo_mobile{
                display: none;      
            }
        }
        
        #tbody_main tr{
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }
        
        #tbody_main td {
            border: none;       /* Remove borders from the cells */
            outline: none;      /* Remove outlines from the cells */
        }
        
        #tbody_selectpo tr{
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }

        #tbody_selectpo td{
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }

        .disabled {
            pointer-events: none; /* Prevents clicks */
            opacity: 0.5; /* Grays out the element */
            cursor: not-allowed; /* Changes the cursor */
        }          

        </style>
        <form name='myforms' id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)"> 
            <table class='big_table'> 
                <tr colspan=1>
                    <td colspan=1 class='td_bl'>
                        <?php
                            require 'includes/main_menu.php';
                        ?>
                    </td>

                    <td colspan=1 style="height:100%" class='td_br' id='td_br'>

                        <div class="container-fluid w-100 h-100 d-flex justify-content-center" style='max-width:100vw;flex-wrap:wrap;padding-left:15px;padding-right:15px;box-sizing:border-box'>
                         
                                <table class="bg-white w-75 shadow rounded user_access_tbl my-4"  id="file2_table" style="border-radius: 0.75rem!important;border-collapse:collapse;height:400px;min-width:0">

                                    <tr class="m-1 salesfile1" style="border-bottom:3px solid #cccccc">
                                        <td colspan="3"> 
                                            <div class="row">
                                                <div class="col-md-4 col-6">
                                                    <div class="m-2">
                                                        <h2>Expense</h2>
                                                    </div>
                                                </div>
                                                <div class="col-md-8 col-12 d-flex align-items-center justify-content-end">
                                                    <div class="m-2 row">
                                                        <div class="col-md-3 col-4 d-flex justify-content-start ps-0 pe-1">
                                                                <input type="button" class="btn btn-danger fw-bold" style="width:100px;" value="Back" onclick="back_page()">                                           
                                                        </div>

                                                        <div class="col-md-4 col-4 d-flex justify-content-start ps-0 pe-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:150px;" value="Save and Exit" onclick="salesfile2('save_exit')">
                                                        </div>

                                                        <div class="col-md-5 col-5 d-flex justify-content-start justify-content-md-end ps-md-1 ps-0 pe-1 mt-1 mt-md-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:175px;" value="Save and Add Next" onclick="salesfile2('save_new')">
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </td>
                                    </tr>


                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">

                                        <td>
                                            <div class="row px-2">
                                                <div class="p-3 col-md-4 col-6">
                                                    <label for="" style="font-weight:bold">Doc. Num:</label>
                                                    <input type="text" class="form-control" name="docnum_1" style="font-weight:bold" id="docnum_1" value="<?php echo $docnum;?>" readonly>
                                                </div>
                                    
                                                <div class="p-3 col-md-4 col-6">
                                                    <label for="" style="font-weight:bold">Tran. Date:<span style="color:red">*</span></label>
                                                    <input type="text" class="form-control date_picker"  name="trndte_1" id="trndte_1" value="<?php echo $trndate;?>" readonly>
                                                </div>
                                        
                                                <div class="p-3 col-md-4 col-6 p-md-3 pt-0 p-3" style="<?php echo $visibility_hidden; ?>">
                                                    <label for="" style="font-weight:bold">Amount:</label>
                                                    <input type="text" class="form-control" name="trntot_1" id="trntot_1" value="<?php echo $trntot;?>" autocomplete="off">
                                                </div>
                                            </div>
                                            
                                        </td>                                    
                                    </tr>


                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                           

                                         
                                        <td>
                                            <div class="row px-2">
                                                <div class="p-3 col-md-6 col-6">
                                                    <label for="" style="font-weight:bold">Expense Type:</label>
                                                    <select name="expense_type1" id="expense_type1" class="form-control">
                                                        <?php
                                                            $select_expensetype='SELECT * FROM expensetypefile ORDER BY expense_dsc ASC';
                                                            $stmt_expensetype	= $link->prepare($select_expensetype);
                                                            $stmt_expensetype->execute();
                                                            while($rs_expensetype = $stmt_expensetype->fetch()):
                                                        ?>

                                                        <?php
                                                        $selected = '';
                                                        if($expensetype == $rs_expensetype['expense_cde']){
                                                            $selected = 'selected';
                                                        }
                                                        ?>
                                                        <option value="<?php echo $rs_expensetype['expense_cde'];?>" <?php echo $selected;?>><?php echo $rs_expensetype['expense_dsc'];?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>

                                                <div class="p-3 col-md-6 col-6">
                                                    <label for="" style="font-weight:bold">Vat Type:</label>
                                                    <select name="vat_type1" id="vat_type1" class="form-control">

                                                        <?php
                                                            $select_vatcde='SELECT * FROM vat_typefile ORDER BY vat_dsc ASC';
                                                            $stmt_vatcde	= $link->prepare($select_vatcde);
                                                            $stmt_vatcde->execute();
                                                            while($rs_vatcde = $stmt_vatcde->fetch()):
                                                        ?>

                                                        <?php
                                                        $selected = '';
                                                        if($vattype == $rs_vatcde['vat_cde']){
                                                            $selected = 'selected';
                                                        }
                                                        ?>
                                                        <option value="<?php echo $rs_vatcde['vat_cde'];?>" <?php echo $selected;?>><?php echo $rs_vatcde['vat_dsc'];?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>                                                   
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3">
                                                <label style="font-weight:bold">Remarks:</label>
                                                <textarea name="remarks_1" id="remarks_1" rows="3" class="form-control"><?php echo $remarks; ?></textarea>
                                            </div>
                                        </td>                                  
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3" style="max-width:33.333333%;min-width:260px;">
                                                <div>
                                                    <label for="userdesc_display" style="font-weight:bold">User:</label>
                                                    <input type="text" class="form-control" name="userdesc_display" id="userdesc_display" value="<?php echo htmlspecialchars($display_userdesc, ENT_QUOTES); ?>" readonly>
                                                    <input type="hidden" name="usercode_1" id="usercode_1" value="<?php echo htmlspecialchars($display_usercode, ENT_QUOTES); ?>">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- <tr class="m-1 salesfile1" id="tr_access_data">
                                        <td id="main_chk_div" colspan="3">
                                            <div class='w-100 d-flex justify-content-center'>
                                                <div style='width:90%'>
                                                    <button type='button' class='btn btn-success my-2' value='Add Record' onclick="salesfile2('open_add')">
                                                        <span style='font-weight:bold'>
                                                            Add Record
                                                        </span>
                                                        <i class='fas fa-plus' style='margin-left: 3px'></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class='w-100 d-flex justify-content-center'>
                                                <table class='table table-striped' id='data_table' style='width:90%'>

                                                    <tbody id="tbody_main" class="tbody_main">

                                                    </tbody>

                                                    <tbody id="tbody_main_mobile" class="tbody_main_mobile">

                                                    </tbody>
                                                </table>
                                            </div>                                            
                                        </td>
                                    
                                    </tr> -->
                            
                                </table>
                        </div>

                    </td>

                </tr>
            </table>

            <div class='modal fade' id='alert_modal_system' tabindex='-1'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'> <img src="<?php echo $logo_dir ;?>" style="<?php echo 'width:'.$logo_width.';height:'.$logo_height.';';?>">&nbsp;<?php echo $system_name;?> Says: </h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                    
                        <div class='modal-body alert_modal_body_system' id="alert_modal_body_system">
                            <b>Successfully Saved.</b>
                        </div>

                    </div>
               
                </div>
            </div>

            <!-- HIDDEN -->
            <input type="hidden" name="event_action" id="event_action">
            <input type="hidden" name="multi_itm_select" id="multi_itm_select">
            <input type="hidden" name="multi_itm_select_original" id="multi_itm_select_original">
            <input type="hidden" name="usercode_access_hidden" id="usercode_access_hidden" value="<?php if(isset($_POST["usercode_hidden"])){echo $_POST["usercode_hidden"];}?>">
            <input type="hidden" name='docnum_hidden' id="docnum_hidden" value="<?php echo $docnum; ?>">

            <div class="modal fade" id="insert_modal_sales" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Purchase</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="itmcde_add" id="itmcde_add"  autocomplete="off" placeholder="Enter item to search" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func('add')" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>                            
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Quantity</label>
                                    <input type="text" name="itmqty_add" id="itmqty_add" class="form-control" autocomplete="off" oninput="calcTotal('add')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Price</label>
                                    <input type="text" name="price_add" id="price_add" class="form-control" autocomplete="off" oninput="calcTotal('add')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Amount</label>
                                    <input type="text" name="amount_add" id="amount_add" class="form-control" autocomplete="off" readonly>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Search Purchase Orders</label>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button" onclick="search_po('getData_add','')">Search</button>
                                        </div>
                                        <input type="text" name="po_add" id="po_add" class="form-control" autocomplete="off" readonly>
                                    </div>
                                   
                                </div>
                            </div>

                            <input type="hidden" name="itmcde_add_hidden" id="itmcde_add_hidden">
                            <div class="row m-2">
                                <div class="error_msg_add_modal"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="salesfile2('insert')">Save</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="insert_modal_sales_po" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Match Purchase Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style='overflow-y:auto;max-height:90vh'>
                     <table class='table table-striped'>
                        
                        <tbody name="tbody_selectpo" id="tbody_selectpo">

                        </tbody>

                        <tbody name="tbody_selectpo_mobile" id="tbody_selectpo_mobile">

                        </tbody>


                     </table>
                    </div>
                    <div class="modal-footer" id="modal_footer_po">
                        <button type="button" class="btn btn-primary fw-bold" onclick="return_po()">Done</button>
                    </div>
                    </div>
                </div>
            </div>       
            
            <div class="modal fade view_itm_search" id="view_itm_search" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Search Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class='modal_view_item'>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="itm_view" id="itm_view" placeholder="" autocomplete="off">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func_inmodal()" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            
                            <div class="error_msg_itm_view">
                                 
                            </div>
                        </div>

                        <div class='html_itm_view_data' style='max-height:70vh;overflow:auto'>
                                
                        </div>
                    </div>
                    <div class="modal-footer back_btn_modal_footer">

                    </div>
                    </div>
                </div>
            </div>              

            <div class="modal fade" id="edit_modal_sales" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Edit Purchase</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item</label>

                                    <!-- <input type="text" name="itmcde_edit" id="itmcde_edit" class="form-control" autocomplete="off"> -->
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" name="itmcde_edit" id="itmcde_edit" readonly placeholder="Enter item to search" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func('edit')" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Quantity</label>
                                    <input type="text" name="itmqty_edit" id="itmqty_edit" class="form-control" autocomplete="off" oninput="calcTotal('edit')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Price</label>
                                    <input type="text" name="price_edit" id="price_edit" class="form-control" autocomplete="off" oninput="calcTotal('edit')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Amount</label>
                                    <input type="text" name="amount_edit" id="amount_edit" class="form-control" autocomplete="off" readonly>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Search Expenses</label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button" onclick="search_po_edit('getData_edit','')">Search</button>
                                        </div>
                                        <input type="text" name="po_edit" id="po_edit" class="form-control" autocomplete="off" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="po_edit_hidden" id="po_edit_hidden">
                            <input type="hidden" name="recid_tranfile2_hidden" id="recid_tranfile2_hidden">

                            <input type="hidden" name="itmcde_edit_hidden" id="itmcde_edit_hidden">
                            <div class="row m-2">
                                <div class="error_msg_edit_modal"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="salesfile2('submitEdit')">Save</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="salesfile2_recid_hidden" id="salesfile2_recid_hidden">
            </div>

            <input type="hidden" name="xevent_itmsearch_hidden" id="xevent_itmsearch_hidden">
            <input type="hidden" name="recid_po_hidden" id="recid_po_hidden">

            
            <input type="hidden" name="untprc_hidden" id="untprc_hidden">

            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">
                
            <input type="hidden" name="expense_type_search_h" id="expense_type_search_h" value="<?php if(isset($_POST['expense_type_search_h'])){echo $_POST['expense_type_search_h'];}?>">
            <input type="hidden" name="vat_type_search_h" id="vat_type_search_h" value="<?php if(isset($_POST['vat_type_search_h'])){echo $_POST['vat_type_search_h'];}?>">
            <input type="hidden" name="docnum_search_h" id="docnum_search_h" value="<?php if(isset($_POST['docnum_search_h'])){echo $_POST['docnum_search_h'];}?>">
            <input type="hidden" name="from_search_h" id="from_search_h" value="<?php if(isset($_POST['from_search_h'])){echo $_POST['from_search_h'];}?>">
            <input type="hidden" name="to_search_h" id="to_search_h" value="<?php if(isset($_POST['to_search_h'])){echo $_POST['to_search_h'];}?>">
            <input type="hidden" name="cusname_search_h" id="cusname_search_h" value="<?php if(isset($_POST['cusname_search_h'])){echo $_POST['cusname_search_h'];}?>">
            <input type="hidden" name="unpaid_search_h" id="unpaid_search_h" value="<?php if(isset($_POST['unpaid_search_h'])){echo $_POST['unpaid_search_h'];}?>">
            <input type="hidden" name="sortby_1_order_h" id="sortby_1_order_h" value="<?php if(isset($_POST['sortby_1_order_h'])){echo $_POST['sortby_1_order_h'];}?>">
            <input type="hidden" name="sortby_1_field_h" id="sortby_1_field_h" value="<?php if(isset($_POST['sortby_1_field_h'])){echo $_POST['sortby_1_field_h'];}?>">
            <input type="hidden" name="sortby_2_order_h" id="sortby_2_order_h" value="<?php if(isset($_POST['sortby_2_order_h'])){echo $_POST['sortby_2_order_h'];}?>">
            <input type="hidden" name="sortby_2_field_h" id="sortby_2_field_h" value="<?php if(isset($_POST['sortby_2_field_h'])){echo $_POST['sortby_2_field_h'];}?>">
            <input type="hidden" name="crud_msg_h" id="crud_msg_h" value="<?php if(isset($_POST['crud_msg_h'])){echo $_POST['crud_msg_h'];}?>">
            <input type="hidden" name="crud_msg_h2" id="crud_msg_h2">
            <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php if(isset($_POST['trncde_hidden'])){echo $_POST['trncde_hidden'];}?>">
            <input type="hidden" name="scrolly_hidden" id="scrolly_hidden" value="<?php if(isset($_POST['scrolly_hidden'])){echo $_POST['scrolly_hidden'];}?>">
            <input type="hidden" name="scrolly_hidden2" id="scrolly_hidden2" value="Y">
        </form>

        <script>

        var trncde = $("#trncde_hidden").val();
        var expenseHeaderLogState = <?php echo json_encode(array(
            'exists' => $expense_log_initial_exists,
            'docnum' => $expense_log_initial_docnum,
            'trndte' => $expense_log_initial_trndte,
            'trntot' => $expense_log_initial_trntot,
            'remarks' => $expense_log_initial_remarks,
            'vat_cde' => $expense_log_initial_vat_cde,
            'expense_cde' => $expense_log_initial_expense_cde
        )); ?>;

        $(document).ready(function(){
            var docnum = $("#docnum_hidden").val();
            xdata = "docnum="+docnum+"&trncde="+trncde;
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"expensefile2_ajax.php", 
                    success: function(xdata){  
                        // $("#tbody_main").html(xdata["html"]);
                        // $("#tbody_main_mobile").html(xdata["html_mobile"]);
                    }
            })
        });

        function return_po(){

            $("#insert_modal_sales_po").modal("hide");
            $("#insert_modal_sales").modal("show");

        }

        function return_po_edit(){

            $("#insert_modal_sales_po").modal("hide");
            $("#edit_modal_sales").modal("show");

        }

        function select_item_modal(xitmcde, xitmdsc, xuntprc, xevent_action, xnew_itm){

            if(xevent_action == 'add'){

                $(".error_msg_add_modal").html("");

                $("#itmcde_add_hidden").val(xitmcde);
                $("#itmcde_add").val(xitmdsc);

                //$("#price_add").val(xuntprc);
                var xqty = $("#itmqty_add").val();
                var xtotal = xqty * xuntprc;
                // $("#amount_add").val(xtotal);

                $("#view_itm_search").modal("hide");
                $("#itmcde_add").prop("readonly", true);
                $("#insert_modal_sales").modal("show");
            }else if(xevent_action == 'edit'){

                $(".error_msg_edit_modal").html("");


                $("#itmcde_edit_hidden").val(xitmcde);
                $("#itmcde_edit").val(xitmdsc);

                //$("#price_edit").val(xuntprc);
                var xqty = $("#itmqty_edit").val();
                var xtotal = xqty * xuntprc;
                $("#amount_edit").val(xtotal);

                if(xitmdsc != xnew_itm){
                    
                    $("#po_edit").val('');
                    $("#multi_itm_select").val('');
                    //$("#multi_itm_select_original").val('');

                    $("#po_edit_hidden").val('');
                    //$("#itmcde_edit_hidden").val('');
                    $("#recid_po_hidden").val('');
                }

                $("#view_itm_search").modal("hide");
                $("#edit_modal_sales").modal("show"); 
                
                
            }

            $(".error_msg_itm_view").html("");


        }       
        
        function back_from_view_func(xevent){

            $("#view_itm_search").modal("hide");

            if(xevent == "add"){
                $(".error_msg_add_modal").html("");
                $("#insert_modal_sales").modal("show");
            } else if(xevent == "edit"){
                $(".error_msg_edit_modal").html("");
                $("#edit_modal_sales").modal("show");
            }

        }       


        function search_itm_func_inmodal(){

            var xevent_itmsearch_hidden_val = $("#xevent_itmsearch_hidden").val();


            var search_itm  = $("#itm_view").val();

            xdata = "event_action=search_itm&search_itm="+search_itm+"&event_action_itmsearch="+xevent_itmsearch_hidden_val;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
                success: function(xdata){  

                    if(xdata['status'] == 1){
                        $(".html_itm_view_data").html(xdata['html']);
                        $(".error_msg_itm_view").html('');
                    }else{
                        $(".error_msg_itm_view").html(`
                            <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                        `);

                    }
                    
                }
            })      
        }

        function search_itm_func(xevent){

            if(xevent == 'add'){
                var search_itm  = $("#itmcde_add").val();
                $(".back_btn_modal_footer").html(`<button type="button" class="btn btn-primary" onclick="back_from_view_func('add')">Back</button>`);

            }else if (xevent == 'edit'){
                var search_itm  = $("#itmcde_edit").val();  
                $(".back_btn_modal_footer").html(`<button type="button" class="btn btn-danger" onclick="back_from_view_func('edit')">Back</button>`);      

            }

            $('#xevent_itmsearch_hidden').val(xevent);

            xdata = "event_action=search_itm&search_itm="+search_itm+"&event_action_itmsearch="+xevent;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
                success: function(xdata){  


                    if(xdata['status'] == 0){

                        if(xevent == 'add'){
                            $(".error_msg_add_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);
                        }else if (xevent == 'edit'){    

                            $(".error_msg_edit_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                            `);   
                        }

                    }else{

                        $(".html_itm_view_data").html(xdata['html']);
                        $("#itm_view").val(xdata['itm_search']);
                        if(xevent == 'add'){
                            $("#insert_modal_sales").modal("hide");
                        }else if(xevent == 'edit'){
                            $("#edit_modal_sales").modal("hide");
                        }

                        $(".error_msg_itm_view").html("");
                        $("#view_itm_search").modal("show");
                    }
                }
            })      
        }

        $('#itmcde_edit').on('change', function() {

            xdata = "event_action=change_itmprice&xitmcde="+this.value;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
                success: function(xdata){  
                    //$("#price_edit").val(xdata['retEdit']['xprice']);
                    var xqty = $("#itmqty_edit").val();
                    var xtotal = xqty * xdata['retEdit']['xprice'];
                    $("#amount_edit").val(xtotal);
                }
            })  
        });
        
        
        function search_po(xevent_action,xdocnum,xrecid){

            $("#insert_modal_sales").modal("hide");

            if(xrecid){
                $("#recid_po_hidden").val(xrecid);
            }

            var multi_itm_select = $("#multi_itm_select").val();

            if($('#itmcde_add').is(':disabled')){
                $("#itmcde_add").attr("disabled", false);
                var xdata = $("#insert_modal_sales *").serialize("")+"&event_action="+xevent_action+"";
                $("#itmcde_add").attr("disabled", true);
            }else{
                var xdata = $("#insert_modal_sales *").serialize("")+"&event_action="+xevent_action;    
            }

            xdata = xdata+"&multi_itm_select="+multi_itm_select;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_po.php", 

                    success: function(xdata){
                        
                        if(xevent_action == 'getData_add'){
                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-primary fw-bold" onclick="return_po()">Done</button>`
                            )
                        }else if(xevent_action == 'selectData_add'){
                            $("#po_add").val(xdocnum);
                            $("#insert_modal_sales_po").modal("hide");
                            $("#insert_modal_sales").modal("show");

                            //disable the inputs
                            $('#itmcde_add').attr("disabled", true); 
                            $("#itmqty_add").prop('readonly', true);
                            //$("#price_add").prop('readonly', true);
                            $('.btn_search_item').addClass('disabled');
                        }

                    }
            })
            
        }

        function select_multi_itm(xevent_action,xdocnum,xrecid,itm_checkbox,xuntprc){

            if(xuntprc){

                if(xevent_action == "selectData_add"){
                    //$("#price_add").val(xuntprc);
                    var xqty = $("#itmqty_add").val();
                    var xtotal = xqty * xuntprc;
                    $("#price_add").val(xuntprc);
                    $("#amount_add").val(xtotal);

                }

                $("#untprc_hidden").val(xuntprc);

            }


            var selected_po = $("#po_edit").val();

            if(xevent_action == "selectData_add"){
                var itmqty = $("#itmqty_add").val();
            }else{
                var itmqty = $("#itmqty_edit").val();
            }

            if(xrecid){
                var recid_po_hidden_val = $("#recid_po_hidden").val(xrecid);
            }

            if(itm_checkbox.checked){
                var xchecked = 'check';

            }else{
                var xchecked = 'uncheck';
            }

            var itmcde_add_hidden = $("#itmcde_add_hidden").val();
            var multi_itm_select = $("#multi_itm_select").val();

            var xdata = $("#edit_modal_sales *").serialize("")+"&event_action="+xevent_action+"&selected_po="+selected_po+"&recid_po_hidden="+xrecid;
            xdata = xdata+"&xcheck_action="+xchecked+"&itmcde_add_hidden="+itmcde_add_hidden+"&multi_itm_select="+multi_itm_select+"&itmqty="+itmqty;            

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_po.php", 
                    success: function(xdata){

                        if(xevent_action == 'getData_edit'){

                            var xmulti_docnum_add = $("#po_edit").val();

                            // $("#po_edit").val(xmulti_docnum_add + ","+ xdocnum);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-danger fw-bold" onclick="return_po_edit()">Back</button>`
                            );

                        }else if(xevent_action == 'selectData_edit' || xevent_action == 'deSelectData_edit'){
                            var xmulti_docnum_add = $("#po_edit").val();
                            var xmulti_itm_select = $("#multi_itm_select").val();

                            if(itm_checkbox.checked){

                                if(xmulti_itm_select == ""){
                                    $("#multi_itm_select").val(xrecid);
                                }else{
                                    $("#multi_itm_select").val(xmulti_itm_select + ","+ xrecid);
                                }

                                if(xmulti_docnum_add == ""){
                                    $("#po_edit").val(xdocnum);  
                                }else{
                                    $("#po_edit").val(xmulti_docnum_add + ", "+ xdocnum);  
                                }
                            }else{

                                if(xmulti_docnum_add.includes(',')){
                                    let valuesArray = xmulti_docnum_add.split(',').map(item => item.trim());
                                    let indexToRemove = valuesArray.indexOf(xdocnum);
                                    if (indexToRemove !== -1) {
                                        valuesArray.splice(indexToRemove, 1);
                                    }

                                    // Join the array back into a string
                                    xmulti_docnum_add = valuesArray.join(', ');
                                    $("#po_edit").val(xmulti_docnum_add);
                                }else{
                                    $("#po_edit").val('');
                                }

                                if(xmulti_itm_select.includes(',')){
                                    let valuesArray_recid = xmulti_itm_select.split(',');
                                    valuesArray_recid = valuesArray_recid.filter(value => value !== xrecid);
                                    // Join the array back into a string
                                    xmulti_recid_add = valuesArray_recid.join(',');

                                    $("#multi_itm_select").val(xmulti_recid_add);
                                }else{
                                    $("#multi_itm_select").val('');
                                }
                          
                            }
                        }else if(xevent_action == 'selectData_add'){

                            var xmulti_docnum_add = $("#po_add").val();
                            var xmulti_itm_select = $("#multi_itm_select").val();

                            if(itm_checkbox.checked){

                                if(xmulti_itm_select == ""){
                                    $("#multi_itm_select").val(xrecid);
                                }else{
                                    $("#multi_itm_select").val(xmulti_itm_select + ","+ xrecid);
                                }

                                if(xmulti_docnum_add == ""){
                                    $("#po_add").val(xdocnum);  
                                }else{
                                    $("#po_add").val(xmulti_docnum_add + ", "+ xdocnum);  
                                }
                            }else{

                                if(xmulti_docnum_add.includes(',')){
                                    let valuesArray = xmulti_docnum_add.split(',').map(item => item.trim());
                                    let indexToRemove = valuesArray.indexOf(xdocnum);
                                    if (indexToRemove !== -1) {
                                        valuesArray.splice(indexToRemove, 1);
                                    }

                                    // Join the array back into a string
                                    xmulti_docnum_add = valuesArray.join(', ');
                                    $("#po_add").val(xmulti_docnum_add);
                                }else{
                                    $("#po_add").val('');
                                }

                                if(xmulti_itm_select.includes(',')){
                                    let valuesArray_recid = xmulti_itm_select.split(',');
                                    valuesArray_recid = valuesArray_recid.filter(value => value !== xrecid);
                                    // Join the array back into a string
                                    xmulti_recid_add = valuesArray_recid.join(',');

                                    $("#multi_itm_select").val(xmulti_recid_add);
                                }else{
                                    $("#multi_itm_select").val('');
                                }
                          
                            }

                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                        }

                    }
            })            

        }

        function search_po_edit(xevent_action,xdocnum,xrecid,itm_checkbox){

            $("#edit_modal_sales").modal("hide");

            var selected_po = $("#po_edit").val();
            var recid_tranfile2_hidden = $("#recid_tranfile2_hidden").val();

            //var selected_po_hidden = $("#po_edit_hidden").val();

            if(xrecid){
                var recid_po_hidden_val = $("#recid_po_hidden").val(xrecid);
            }else{
                var recid_po_hidden_val = $("#recid_po_hidden").val();
            }

            if(xevent_action == "selectData_add"){
                var itmqty = $("#itmqty_add").val();
            }else{
                var itmqty = $("#itmqty_edit").val();
            }

            if($('#itmcde_edit').is(':disabled')){
                //enable the itcde para mapass yung data
                $("#itmcde_edit").attr("disabled", false);
                var xdata = $("#edit_modal_sales *").serialize("")+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_po_hidden="+recid_po_hidden_val;
                //disable na again since the data was passed
                $("#itmcde_edit").attr("disabled", true);
            }else{
                var xdata = $("#edit_modal_sales *").serialize("")+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_po_hidden="+recid_po_hidden_val;
            }

            // alert(itmcde_add_hidden);
            var itmcde_add_hidden = $("#itmcde_add_hidden").val();
            var multi_itm_select = $("#multi_itm_select").val();

            var xmulti_select_original =  $("#multi_itm_select_original").val();

            xdata = xdata+"&itmcde_add_hidden="+itmcde_add_hidden+"&multi_itm_select="+multi_itm_select+"&itmqty="+itmqty+"&multi_select_original="+xmulti_select_original;
            
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_po.php", 
                    success: function(xdata){

                        
                        if(xevent_action == 'getData_edit'){

                            var xmulti_docnum_add = $("#po_edit").val();
                            
                            //$("#po_edit").val(xmulti_docnum_add + ","+ xdocnum);

                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-danger fw-bold" onclick="return_po_edit()">Back</button>`
                            );

                        }else if(xevent_action == 'selectData_edit' || xevent_action == 'deSelectData_edit'){
                       
                            if(xevent_action == 'selectData_edit'){
                                $("#po_edit").val(xdocnum);
                                $("#recid_po_hidden").val(xrecid);
                            }else{
                                $("#po_edit").val('');
                                $("#recid_po_hidden").val('');
                            }
                            
                            $("#insert_modal_sales_po").modal("hide");
                            $("#edit_modal_sales").modal("show");

                            $('#itmcde_edit').attr("disabled", true); 
                            $("#itmqty_edit").prop('readonly', true);

                        }

                    }
            })

        }        

        function calcTotal(event){

            if(event == "add"){
                var price=$("#price_add").val();
                var qty=$("#itmqty_add").val();
            }

            if(event == "edit"){
                var price=$("#price_edit").val();
                var qty=$("#itmqty_edit").val();
            }

            price = price.replaceAll(",","");
            qty = qty.replaceAll(",","");

            var amount=(price*qty).toFixed(2);

            if(event == "add"){;
                $("#amount_add").val(amount);
            }
            if(event == "edit"){
                $("#amount_edit").val(amount);
            }
            
        }

        function print_pdf(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "pdf_test.php";
            document.forms.myforms.submit();
        }

        var back_check = false;

        function back_page(){

            if(back_check == false){
                $("#crud_msg_h").val("same_page");
            }

            // var check =   $("#crud_msg_h2").val();
            // if(check == "insert_new"){
            //     $("#crud_msg_h").val("save_exit");
            // }

            localStorage.setItem("scroll_check", "Y");

            document.forms.myforms.target = "_self";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "expensefile1.php";
            document.forms.myforms.submit();
        }

        var save_new = false;

        // $('#itmcde_add').on('change', function() {

        //     xdata = "event_action=change_itmprice&xitmcde="+this.value;

        //     jQuery.ajax({    
        //         data:xdata,
        //         dataType:"json",
        //         type:"post",
        //         url:"trn_purchasefile2_ajax.php", 
        //         success: function(xdata){  
        //             $("#price_add").val(xdata['retEdit']['xprice']);
        //             var xqty = $("#itmqty_add").val();
        //             var xtotal = xqty * xdata['retEdit']['xprice'];
        //             $("#amount_add").val(xtotal);
        //         }
        //     })  
        // });

        // $('#itmcde_edit').on('change', function() {

        //     xdata = "event_action=change_itmprice&xitmcde="+this.value;

        //     jQuery.ajax({    
        //         data:xdata,
        //         dataType:"json",
        //         type:"post",
        //         url:"trn_purchasefile2_ajax.php", 
        //         success: function(xdata){  
        //             $("#price_edit").val(xdata['retEdit']['xprice']);
        //             var xqty = $("#itmqty_edit").val();
        //             var xtotal = xqty * xdata['retEdit']['xprice'];
        //             $("#amount_edit").val(xtotal);
        //         }
        //     })  
        // });           

        function salesfile2(event,recid){

            var docnum = $("#docnum_hidden").val();
            back_check = true;

            switch(event){
                case "save_exit":
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_exit&docnum="+docnum;
                    break;
                case "save_new":
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_new&docnum="+docnum;
                    break;
                case "open_add":

                    //disable the inputs
                    $("#itmcde_add").prop("readonly", false);
                    $('#itmcde_add').attr('disabled', false);
                    $("#itmqty_add").prop('readonly', false);
                    $("#price_add").prop('readonly', false);
                    $('.btn_search_item').removeClass('disabled');
                    $("#untprc_hidden").val('');
                    $("#price_add").val('');
                    $("#amount_add").val('');
                    $("#itmqty_add").val('');
                    $("#itmcde_add").val('');
                    $("#po_add").val('');
                    $("#multi_itm_select").val('');
                    $("#recid_po_hidden").val('');
                    // $("#itmcde_add").val($("#itmcde_add option:first").val());
                    $("#itmcde_add_hidden").val('');
                    $('.error_msg_add_modal').html('');
                    $("#insert_modal_sales").modal("show");

                    // xdata = "event_action=select_itmprice";
                    // jQuery.ajax({    
                    //     data:xdata,
                    //     dataType:"json",
                    //     type:"post",
                    //     url:"trn_purchasefile2_ajax.php", 
                    //     success: function(xdata){  
                    //         $("#price_add").val(xdata['retEdit']['xprice']);

                    //     }
                    // })                      
                    
                    return;
                break;
                case "insert":

                    //remove the disabled muna para mabasa ng serialize
                    $("#itmcde_add").attr("disabled", false);
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&"+$(".salesfile1 *").serialize();
                    $('#itmcde_add').attr("disabled", true);
                    
                    var xmulti_itm_select = $("#multi_itm_select").val();
                    xdata = xdata + "&multi_itm_select="+xmulti_itm_select;
                 
                break;
                case "submitEdit":
                    var recid = $("#salesfile2_recid_hidden").val();
                    var trndte_1_val = $("#trndte_1").val();

                    var xmulti_itm_select = $("#multi_itm_select").val();
                    var xmulti_itm_select_original = $("#multi_itm_select_original").val();
                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&recid="+recid+"&xtrndte_1="+trndte_1_val;
                    xdata = xdata+"&multi_itm_select="+xmulti_itm_select+"&multi_itm_select_original="+xmulti_itm_select_original;

                break;
                case "getEdit": 

                    //disable the inputs
                    $('.btn_search_item').removeClass('disabled');
                    $("#multi_itm_select_original").val('');
                    $("#itmqty_edit").prop('readonly', false);
                    $("#price_edit").prop('readonly', false);
                    $("#untprc_hidden").val('');
                    $("#recid_tranfile2_hidden").val(recid);

                    var xdata = "event_action=getEdit&recid="+recid+"&docnum="+docnum;
                    break;
                case "delete":
                    var xdata = "event_action=delete&recid="+recid+"&docnum="+docnum;
                    break;
                case "insert":
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum;
                break;
            }

            var xrecid_po_hidden_val = $("#recid_po_hidden").val();

            var xdata = xdata+"&trncde="+trncde+"&xrecid_po_hidden="+xrecid_po_hidden_val;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"expensefile2_ajax.php", 
                    success: function(xdata){  

                        if(xdata["status"] == 0){
                            if(event == "insert"){
                                $("#itmcde_add").prop("readonly", false);
                                $('#itmcde_add').attr("disabled", false); 

                                $(".error_msg_add_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);
                            }else if(event == "submitEdit"){
                                $(".error_msg_edit_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);
                            }else{
                                $("#alert_modal_body_system").html(xdata["msg"]);
                                $("#alert_modal_system").modal("show");
                            }

                            return;
                        }

                        if(event == "save_exit" || event == "save_new"){
                            var expenseLogUpdateData = {
                                expense_header_log_action: 'update_latest_remark',
                                activity: expenseHeaderLogState.exists ? 'edit' : 'add',
                                docnum: expenseHeaderLogState.docnum || $("#docnum_hidden").val(),
                                old_expense_cde: expenseHeaderLogState.expense_cde,
                                new_expense_cde: $("#expense_type1").val(),
                                old_vat_cde: expenseHeaderLogState.vat_cde,
                                new_vat_cde: $("#vat_type1").val(),
                                old_trndte: expenseHeaderLogState.trndte,
                                new_trndte: $("#trndte_1").val(),
                                old_trntot: expenseHeaderLogState.trntot,
                                new_trntot: $("#trntot_1").val(),
                                old_remarks: expenseHeaderLogState.remarks,
                                new_remarks: $("#remarks_1").val()
                            };

                            $.ajax({
                                data: expenseLogUpdateData,
                                dataType: "json",
                                type: "post",
                                url: "expensefile2.php",
                                async: false
                            });
                        }
 
                        if(xdata["msg"] == "insert_new"){
                            $("#crud_msg_h2").val("save_exit");
                            $("#crud_msg_h").val("save_exit");
                        }
                        if(xdata["msg"] == "insert_old"){
                            $("#crud_msg_h2").val("edit_exit");
                            $("#crud_msg_h").val("edit_exit");
                        }

                        var crud_msg_h =   $("#crud_msg_h").val();
                        var crud_msg_h2 =   $("#crud_msg_h2").val();

                        if(xdata["msg"] == "save_new_last" || xdata["msg"] == "save_new_same"){

                            $("#docnum_1").attr('value',xdata["new_docnum"]);
                            $("#docnum_hidden").attr('value',xdata["new_docnum"]);
                            // $("#trndte_1").val('');
                            $("#trntot_1").val('');
                            $("#vat_type1").val($("#vat_type1 option:first").val());
                            $("#expense_type1").val($("#expense_type1 option:first").val());
                            $("#orderby_1").val('');
                            $("#shipto_1").val('');
                            $("#remarks_1").val('');
                            $("#ordernum_1").val('');

                            if(xdata["msg"] == "save_new_same"){
                                $("#crud_msg_h").val("same_page");
                            }else{
                                $("#crud_msg_h").val("save_exit");
                            }

                            expenseHeaderLogState = {
                                exists: false,
                                docnum: xdata["new_docnum"] || '',
                                trndte: '',
                                trntot: '',
                                remarks: '',
                                vat_cde: '',
                                expense_cde: ''
                            };
                        }


                        if(xdata["msg"] == "save_exit" || xdata["msg"] == "edit_exit"){

                            localStorage.setItem("scroll_check", "Y");

                            if(crud_msg_h2 == "save_exit"){
                                $("#crud_msg_h").val("save_exit");
                            }else if(crud_msg_h2 == "insert_old"){
                                $("#crud_msg_h").val("edit_exit");
                            }else{
                                $("#crud_msg_h").val(xdata["msg"]);
                            }
                            
                            document.forms.myforms.target = "_self";
                            document.forms.myforms.method = "post";
                            document.forms.myforms.action = "expensefile1.php";
                            document.forms.myforms.submit();
                            return;
                        }


                        if(xdata["msg"] == "retEdit"){
                            $("#itmcde_edit_hidden").val(xdata["retEdit"]["itmcde"]);
                            $("#itmcde_edit").val(xdata["retEdit"]["itmdsc"]);

                            //set multi item select to empty
                            $("#multi_itm_select").val('');
                            $("#multi_itm_select").val(xdata["retEdit"]["matched_po_recid_hidden"]);

                            var xmulti_itm_select_original = $("#multi_itm_select_original").val();
                            if(xmulti_itm_select_original == '' || !xmulti_itm_select_original || xmulti_itm_select_original == null){
     
                                $("#multi_itm_select_original").val(xdata["retEdit"]["matched_po_recid_hidden"]);
                            }

                            $("#price_edit").val(xdata["retEdit"]["untprc"]);
                            $("#amount_edit").val(xdata["retEdit"]["extprc"]);
                            $("#itmqty_edit").val(xdata["retEdit"]["itmqty"]);

                            $("#po_edit").val(xdata["retEdit"]["matched_po"]);
                            $("#po_edit_hidden").val(xdata["retEdit"]["matched_po"]);

                            //recid getting
                            $("#recid_po_hidden").val(xdata["retEdit"]["purnum_recid"]);
                            $(".error_msg_edit_modal").html('');

                            // $("#itmcde_edit option[text=" + xdata["retEdit"]["itmdsc"] +"]").prop("selected", true);
                            // $("#itmcde_edit option:contains("+xdata["retEdit"]["itmdsc"]+")").prop("selected", true);
                            // alert('dsd');
                            // $('#itmcde_edit').val(xdata["retEdit"]["itmdsc"]).prop("selected", true);

                                    //$('#itmcde_edit').text(xdata["retEdit"]["itmdsc"]).prop("selected", true);

                            // $("#itmcde_edit").filter(function() {
                            //     return $(this).text() === xdata["retEdit"]["itmdsc"];
                            // }).prop("selected", true);

                            // var optionsThatContainValue = $("#itmcde_edit").find('option').filter(function() {
                            //     return $(this).text().trim() === xdata["retEdit"]["itmdsc"];
                            // });

                            // optionsThatContainValue.prop("selected", true);

                            $("#salesfile2_recid_hidden").val(xdata["retEdit"]["recid"]);
                            $("#edit_modal_sales").modal("show");
                        }
                        if(xdata["msg"] == "submitEdit"){
                            $("#edit_modal_sales").modal("hide");
                        }

                        $("#insert_modal_sales").modal("hide");

                        $("#tbody_main").html(xdata["html"]);
                        $("#tbody_main_mobile").html(xdata["html_mobile"]);
                        $("#trntot_1").val(xdata["trntot"]);
                    }
            })

    
        }
    
        </script>


<?php
    require "includes/main_footer.php";
?>
