
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
    $log_module = 'INVENTORY ADJUSTMENT';
    $log_trndte = date('Y-m-d H:i:s');

    // Helper function to get item description
    function invadjust_get_item_desc($link, $itmcde){
        $itmcde = trim((string)$itmcde);
        if($itmcde === ''){
            return '';
        }
        $select = "SELECT itmdsc FROM itemfile WHERE itmcde = ? LIMIT 1";
        $stmt = $link->prepare($select);
        $stmt->execute(array($itmcde));
        $rs = $stmt->fetch();
        return $rs ? trim((string)$rs['itmdsc']) : $itmcde;
    }

    // Helper function to get UOM description from unmcde
    function invadjust_get_uom_desc($link, $unmcde){
        $unmcde = trim((string)$unmcde);
        if($unmcde === ''){
            return '';
        }
        $select = "SELECT unmdsc FROM itemunitmeasurefile WHERE unmcde = ? LIMIT 1";
        $stmt = $link->prepare($select);
        $stmt->execute(array($unmcde));
        $rs = $stmt->fetch();
        return $rs ? trim((string)$rs['unmdsc']) : $unmcde;
    }

    $xret = array();
    $xret["html"] = "";
    $xret["html_mobile"] = "";
    $xret["trntot"] = "";
    $xret["msg"] = "";
    $xret["status"] = 1;
    $xret["retEdit"] = array();
    $xret["error1"] = 0;
    $xret["error2"] = 0;
    $xret["error3"] = 0;
    $xret["error4"] = 0;
    $xret["error5"] = 0;
    $xret["error6"] = 0;

    $current_usercode = '';
    if(isset($_POST['usercode_1']) && trim((string)$_POST['usercode_1']) !== ''){
        $current_usercode = trim((string)$_POST['usercode_1']);
    }else if(isset($_SESSION['usercode']) && trim((string)$_SESSION['usercode']) !== ''){
        $current_usercode = trim((string)$_SESSION['usercode']);
    }

    function invadj_is_invalid_uom($uom_value){
        $uom_value = trim((string)$uom_value);
        return $uom_value === '' || strtolower($uom_value) === 'none';
    }

    function invadj_get_item_conversion($link, $itmcde, $unmcde){
        $itmcde = trim((string)$itmcde);
        $unmcde = trim((string)$unmcde);

        if($itmcde === '' || $unmcde === ''){
            return array('found' => false, 'conversion' => 1);
        }

        if(strtolower($unmcde) === 'pcs'){
            return array('found' => true, 'conversion' => 1);
        }

        $select_conversion = "SELECT conversion FROM itemunitfile WHERE itmcde = ? AND unmcde = ? LIMIT 1";
        $stmt_conversion = $link->prepare($select_conversion);
        $stmt_conversion->execute(array($itmcde, $unmcde));
        $rs_conversion = $stmt_conversion->fetch();

        if($rs_conversion && $rs_conversion['conversion'] !== null && $rs_conversion['conversion'] !== ''){
            return array('found' => true, 'conversion' => (float)$rs_conversion['conversion']);
        }

        return array('found' => false, 'conversion' => 1);
    }

    function invadj_get_latest_base_price($link, $itmcde){
        $itmcde = trim((string)$itmcde);

        if($itmcde === ''){
            return '';
        }

        $date_today = date('Y-m-d');
        $select_latestprice = "SELECT tranfile2.untprc, tranfile2.unmcde
                               FROM tranfile1
                               LEFT JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
                               WHERE tranfile2.itmcde = ?
                               AND tranfile1.trndte <= ?
                               AND tranfile1.trncde = 'PUR'
                               ORDER BY tranfile1.trndte DESC, tranfile1.recid DESC, tranfile2.recid DESC
                               LIMIT 1";
        $stmt_latestprice = $link->prepare($select_latestprice);
        $stmt_latestprice->execute(array($itmcde, $date_today));
        $rs_latestprice = $stmt_latestprice->fetch();

        if(!empty($rs_latestprice) && $rs_latestprice['untprc'] !== '' && $rs_latestprice['untprc'] !== null){
            $base_price = (float)$rs_latestprice['untprc'];
            $conversion_data = invadj_get_item_conversion($link, $itmcde, isset($rs_latestprice['unmcde']) ? $rs_latestprice['unmcde'] : '');

            if($conversion_data['found'] && (float)$conversion_data['conversion'] > 0){
                $base_price = $base_price / (float)$conversion_data['conversion'];
            }

            return $base_price;
        }

        return '';
    }

    function invadj_get_item_pricing($link, $itmcde, $unmcde){
        $pricing = array(
            'base_price' => '',
            'conversion' => 1,
            'has_conversion' => false,
            'unit_price' => 0
        );

        $itmcde = trim((string)$itmcde);
        if($itmcde === ''){
            return $pricing;
        }

        $pricing['base_price'] = invadj_get_latest_base_price($link, $itmcde);

        $conversion_data = invadj_get_item_conversion($link, $itmcde, $unmcde);
        $pricing['conversion'] = $conversion_data['conversion'];
        $pricing['has_conversion'] = $conversion_data['found'];
        $pricing['unit_price'] = (float)$pricing['base_price'] * $pricing['conversion'];

        return $pricing;
    }



     
    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "search_itm" || $_POST["event_action"] == "search_itm")){


        if(empty($_POST['search_itm'])){
            $xret["msg"] = "<b>Item Search </b> cannot be empty.";
            $xret["status"] = 0;
        }

        if($xret["status"] == 1){
            if(isset($_POST['search_itm']) && !empty($_POST['search_itm'])){
                $xret["itm_search"] = $_POST['search_itm'];
            }else{
                $xret["itm_search"] = '';
            }

            $xret["html"] = "<table class='table table striped'>";

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td class='fw-bold'>";
                        $xret["html"] .= "Item";             
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='fw-bold text-center'>";
                        $xret["html"] .= "Action";             
                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";

            $select_itemfile="SELECT * FROM itemfile WHERE itmdsc LIKE ? ORDER BY itmdsc ASC";
            $stmt_itemfile	= $link->prepare($select_itemfile);
            $stmt_itemfile->execute(array('%'.$_POST['search_itm'].'%'));
            while($rs_itemfile = $stmt_itemfile->fetch()){
                $latest_base_price = invadj_get_latest_base_price($link, $rs_itemfile['itmcde']);

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['itmdsc'];
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='text-center'>";
                        $xret["html"] .= "<button type='button' onclick='select_item_modal("
                        . json_encode($rs_itemfile['itmcde']) . ", "
                        . json_encode($rs_itemfile['itmdsc']) . ", "
                        . json_encode($_POST['event_action_itmsearch']) . ", "
                        . json_encode($xret['itm_search']) . ", "
                        . json_encode($latest_base_price)
                        . ")' class='btn btn-primary fw-bold'>";
                            $xret["html"].= "Select";
                        $xret["html"].= "</button>";
                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";
            };

            $xret["html"] .= "</table>";
        }
        echo json_encode($xret);
        return;
    }

    // Fetch item-specific UOMs
    if(isset($_POST["event_action"]) && $_POST["event_action"] == "get_item_uoms"){
        $itmcde = isset($_POST['itmcde']) ? trim($_POST['itmcde']) : '';
        $xret["uoms"] = array();

        if($itmcde !== ''){
            // Get UOMs defined for this item in itemunitfile
            $select_uoms = "SELECT iuf.unmcde, iuf.conversion, iumf.unmdsc
                            FROM itemunitfile iuf
                            LEFT JOIN itemunitmeasurefile iumf ON iuf.unmcde = iumf.unmcde
                            WHERE iuf.itmcde = ?
                            ORDER BY iumf.unmdsc ASC";
            $stmt_uoms = $link->prepare($select_uoms);
            $stmt_uoms->execute(array($itmcde));
            while($rs_uom = $stmt_uoms->fetch()){
                $uom_code = trim((string)$rs_uom['unmcde']);
                if($uom_code === ''){
                    continue;
                }
                $xret["uoms"][] = array(
                    'unmcde' => $uom_code,
                    'unmdsc' => !empty($rs_uom['unmdsc']) ? trim((string)$rs_uom['unmdsc']) : $uom_code,
                    'conversion' => $rs_uom['conversion']
                );
            }
        }

        echo json_encode($xret);
        return;
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "get_item_pricing"){
        $itmcde = isset($_POST['itmcde']) ? trim((string)$_POST['itmcde']) : '';
        $unmcde = isset($_POST['unmcde']) ? trim((string)$_POST['unmcde']) : '';
        $pricing = invadj_get_item_pricing($link, $itmcde, $unmcde);

        $xret["pricing"] = array(
            'base_price' => number_format($pricing['base_price'], 2, '.', ''),
            'conversion' => number_format($pricing['conversion'], 2, '.', ''),
            'has_conversion' => $pricing['has_conversion'] ? '1' : '0',
            'unit_price' => number_format($pricing['unit_price'], 2, '.', '')
        );

        echo json_encode($xret);
        return;
    }


    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "search_itm" || $_POST["event_action"] == "search_itm")){


        if(empty($_POST['search_itm'])){
            $xret["msg"] = "<b>Item Search </b> cannot be empty.";
            $xret["status"] = 0;
        }

        if($xret["status"] == 1){
            $xret["html"] = "<table class='table table striped'>";

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td class='fw-bold'>";
                        $xret["html"] .= "Item";             
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='fw-bold'>";
                        $xret["html"] .= "Inventory Type";             
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='fw-bold text-center'>";
                        $xret["html"] .= "Action";             
                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";

            $select_itemfile="SELECT * FROM itemfile WHERE itmdsc LIKE ? ORDER BY itmdsc ASC";
            $stmt_itemfile	= $link->prepare($select_itemfile);
            $stmt_itemfile->execute(array('%'.$_POST['search_itm'].'%'));
            while($rs_itemfile = $stmt_itemfile->fetch()){


                //get the latest purchase price
                date_default_timezone_set('Asia/Manila');
                $date_today = date("Y-m-d");
                $select_price="SELECT *,tranfile2.untprc as tranfile2_untprc
                 FROM tranfile1 LEFT JOIN tranfile2
                ON tranfile1.docnum = tranfile2.docnum 
                WHERE tranfile2.itmcde='".$rs_itemfile['itmcde']."'
                AND tranfile1.trncde='PUR' 
                AND tranfile1.trndte<='".$date_today."'
                ORDER BY tranfile1.trndte DESC LIMIT 1";
                $stmt_price	= $link->prepare($select_price);
                $stmt_price->execute();
                $rs_price = $stmt_price->fetch();

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['itmdsc'];
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['inventory_type'];
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='text-center'>";
                        $xret["html"].= "<button type='button' onclick='select_item_modal("
                        . json_encode($rs_itemfile['itmcde']) . ","
                        . json_encode($rs_itemfile['itmdsc']) . ","
                        . json_encode($_POST['event_action_itmsearch']) . ","
                        . json_encode($xret['itm_search']) . ","
                        . json_encode(invadj_get_latest_base_price($link, $rs_itemfile['itmcde']))
                        . ")' class='btn btn-primary fw-bold'>";
                            $xret["html"].= "Select";
                        $xret["html"].= "</button>";
                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";
            };

            $xret["html"] .= "</table>";
        }



        if(isset($_POST['search_itm']) && !empty($_POST['search_itm'])){
            $xret["itm_search"] = $_POST['search_itm'];
        }else{
            $xret["itm_search"] = '';
        }

        echo json_encode($xret);
        return;
    }   

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "select_itmprice" || $_POST["event_action"] == "change_itmprice")){

        $select_itemfile="SELECT * FROM itemfile ORDER BY itmdsc ASC LIMIT 1";
        $stmt_itemfile	= $link->prepare($select_itemfile);
        $stmt_itemfile->execute();
        $rs_itemfile = $stmt_itemfile->fetch();

        $xitmcde = $rs_itemfile['itmcde'];

        if($_POST["event_action"] == "change_itmprice"){
            $xitmcde = $_POST['xitmcde'];
        }

        $xret["retEdit"]['xprice'] = '';
        $xret["retEdit"]['xprice'] = invadj_get_latest_base_price($link, $xitmcde);

        echo json_encode($xret);
        return;
    }    

    $trncde = $_POST["trncde"];
    $docnum = $_POST['docnum'];

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "save_exit" || $_POST["event_action"] == "save_new")){

        $docnum = $_POST["docnum_1"];

        $select_docnum='SELECT * FROM tranfile1 WHERE docnum=?';
        $stmt_docnum	= $link->prepare($select_docnum);
        $stmt_docnum->execute(array($docnum));
        $rs_docnum = $stmt_docnum->fetch();

        if(empty($rs_docnum)){

            if(empty($_POST['trndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
            }else{
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record = array();
                $arr_record['docnum'] 	= $docnum;
                // $arr_record['orderby'] 	= $_POST['orderby_1'];
                // $arr_record['shipto'] 	= $_POST['shipto_1'];
                $arr_record['cuscde'] 	= NULL;
                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['trndte'] 	= $_POST['trndte_1'];
                // $arr_record['paydate'] 	= $_POST['paydate_1'];
                // $arr_record['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record['remarks'] 	= $_POST['remarks_1'];
                $arr_record['usercode'] 	= $current_usercode;
                
                $arr_record['trncde'] 	= $trncde;

                PDO_InsertRecord($link,'tranfile1',$arr_record, false);

                // Log activity: add header
	                $log_remarks = useractivitylog_build_insert_docnum_remark($docnum);
	                PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'ADJ', '', '', $log_username, $docnum, '');

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
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_update = array();			
    
                // $arr_record_update['orderby'] 	= $_POST['orderby_1'];
                // $arr_record_update['shipto'] 	= $_POST['shipto_1'];
                $arr_record_update['cuscde'] 	= NULL;
                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['trndte'] 	= $_POST['trndte_1'];
                // $arr_record_update['paydate'] 	= $_POST['paydate_1'];
                // $arr_record_update['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record_update['remarks'] 	= $_POST['remarks_1'];
                $arr_record_update['ordernum'] 	= $_POST['ordernum_1'];
                PDO_UpdateRecord($link,"tranfile1",$arr_record_update,"recid = ?",array($recid),false);

                // Log activity: edit header
	                $log_remarks = useractivitylog_build_header_edit_remark($link, 'ADJ', $docnum, $rs_docnum, $arr_record_update);
	                if($log_remarks !== ''){
	                    PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'ADJ', '', '', $log_username, $docnum, '');
	                }
            }


        }
        
        if($_POST["event_action"] == "save_new"  && $xret["status"] == 1){

            $select_db_docnum='SELECT * FROM tranfile1 WHERE trncde=? ORDER BY docnum DESC LIMIT 1';
            $stmt_docnum	= $link->prepare($select_db_docnum);
            $stmt_docnum->execute(array($_POST['trncde']));
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

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "insert"){

        if(isset($_POST['price_add']) && $_POST['price_add'] !== ''){
            $_POST['price_add'] = str_replace(",","",$_POST['price_add']);
        }
        if(isset($_POST['amount_add']) && $_POST['amount_add'] !== ''){
            $_POST['amount_add'] = str_replace(",","",$_POST['amount_add']);
        }

        $_POST['warcde_add'] = isset($_POST['warcde_add']) ? trim((string)$_POST['warcde_add']) : '';
        $_POST['warehouse_floor_id_add'] = isset($_POST['warehouse_floor_id_add']) ? trim((string)$_POST['warehouse_floor_id_add']) : '';
        $_POST['warehouse_staff_id_add'] = isset($_POST['warehouse_staff_id_add']) ? trim((string)$_POST['warehouse_staff_id_add']) : '';
        $_POST['unmcde_add'] = isset($_POST['unmcde_add']) ? trim((string)$_POST['unmcde_add']) : '';

        $select_check="SELECT * FROM tranfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();

        if(empty($_POST['trndte_1'])){
            $xret["status"] = 0;
            $xret["error1"] = 1;
            $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
        }

        if(!isset($_POST['itmcde_add_hidden']) || empty($_POST['itmcde_add_hidden'])){

            if($xret["error1"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            if(empty($_POST['itmcde_add'])){
                $xret["msg"] .= "<b>Item</b> Cannot Be Empty"; 
            }else{
                $xret["msg"] .= "Invalid <b>Item</b>"; 
            }

            $xret["status"] = 0;
            $xret["error2"] = 1;

        }        

        if(!isset($_POST['itmqty_add']) || empty($_POST['itmqty_add']) || $_POST['itmqty_add'] == '0'){

            if($xret["error1"] == 1 || $xret["error2"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["msg"] .= "<b>Quantity</b> Cannot Be Empty or 0"; 
           

            $xret["status"] = 0;
            $xret["error3"] = 1;

        }

        if(invadj_is_invalid_uom($_POST['unmcde_add'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Unit of Measure</b> must be selected and cannot be None";
            $xret["status"] = 0;
            $xret["error6"] = 1;
        }

        if($_POST['warcde_add'] === ''){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error6"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error4"] = 1;
        }

        if($_POST['warehouse_floor_id_add'] === ''){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error4"] == 1 || $xret["error6"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse Floor</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error5"] = 1;
        }

        if($xret["status"] ==  1){
            if(empty($rs_check)){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_file1 = array();
                $arr_record_file1['docnum'] 	= $_POST["docnum"];
                // $arr_record_file1['orderby'] 	= $_POST['orderby_1'];
                // $arr_record_file1['shipto'] 	= $_POST['shipto_1'];
                $arr_record_file1['cuscde'] 	= NULL;
                $arr_record_file1['trntot'] 	= $_POST['trntot_1'];
                $arr_record_file1['trndte'] 	= $_POST['trndte_1'];
                // $arr_record_file1['paydate'] 	= $_POST['paydate_1'];
                // $arr_record_file1['paydetails'] = $_POST['payment_details_1'];
                $arr_record_file1['remarks'] 	= $_POST['remarks_1'];
                $arr_record_file1['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record_file1['usercode']   = $current_usercode;
                $arr_record_file1['trncde']     = $trncde;
    
                PDO_InsertRecord($link,'tranfile1',$arr_record_file1, false);
    
                $xret["msg"] = "insert_new";
            }else{
                $xret["msg"] = "insert_old";
            }
    
            $pricing = invadj_get_item_pricing($link, $_POST['itmcde_add_hidden'], $_POST['unmcde_add']);
            $stkqty = (float)$_POST['itmqty_add'];
            if($pricing['has_conversion']){
                $stkqty = (float)$_POST['itmqty_add'] * $pricing['conversion'];
            }
            $submitted_unit_price = isset($_POST['price_add']) ? trim((string)$_POST['price_add']) : '';
            $final_unit_price = ($submitted_unit_price !== '') ? (float)$submitted_unit_price : $pricing['unit_price'];
            $computed_total = $final_unit_price * (float)$_POST['itmqty_add'];

            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            $arr_record['stkqty'] 	= $stkqty;
            $arr_record['untprc'] 	= $final_unit_price;
            $arr_record['extprc'] 	= $computed_total;
            $arr_record['warcde'] 	= $_POST['warcde_add'];
            $arr_record['warehouse_floor_id'] = $_POST['warehouse_floor_id_add'];
            $arr_record['warehouse_staff_id'] = $_POST['warehouse_staff_id_add'];
            $arr_record['unmcde']     = $_POST['unmcde_add'];
            $arr_record['trncde']     = 'ADJ';

            PDO_InsertRecord($link,'tranfile2',$arr_record, false);

            // Log activity: add line item
            $log_itmdsc_add = invadjust_get_item_desc($link, $_POST['itmcde_add_hidden']);
            $log_uomdsc_add = invadjust_get_uom_desc($link, isset($_POST['unmcde_add']) ? $_POST['unmcde_add'] : '');
            $log_remarks = $log_username . " added item '" . $log_itmdsc_add . "' qty='" . $_POST['itmqty_add'] . "' uom='" . $log_uomdsc_add . "' in docnum='" . $_POST['docnum'] . "'";
            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'ADJ', '', '', $log_username, $_POST['docnum'], '');

        }


    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_tranfile2="SELECT tranfile2.itmcde as 'itmcde', tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.warcde, tranfile2.warehouse_floor_id, tranfile2.warehouse_staff_id, tranfile2.unmcde, itemfile.itmdsc, itemunitmeasurefile.unmdsc, tranfile2.recid as tranfile2_recid FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde LEFT JOIN itemunitmeasurefile ON itemunitmeasurefile.unmcde = tranfile2.unmcde WHERE tranfile2.recid=?";
        $stmt_tranfile2	= $link->prepare($select_tranfile2);
        $stmt_tranfile2->execute(array($_POST["recid"]));
        $rs_tranfile2 = $stmt_tranfile2->fetch();

        if(!empty($rs_tranfile2["untprc"])){
            $rs_tranfile2["untprc"] = number_format($rs_tranfile2["untprc"],2);
        }
        if($rs_tranfile2["extprc"]){
            $rs_tranfile2["extprc"] = number_format($rs_tranfile2["extprc"],2);
        }

        $retedit_unmcde = isset($rs_tranfile2["unmcde"]) ? trim((string)$rs_tranfile2["unmcde"]) : '';
        $xret["retEdit"] = [
            "itmcde" =>  $rs_tranfile2["itmcde"],
            "itmdsc" =>  $rs_tranfile2["itmdsc"],
            "itmqty" =>  $rs_tranfile2["itmqty"],
            "untprc" =>  $rs_tranfile2["untprc"],
            "extprc" =>  $rs_tranfile2["extprc"],
            "warcde" =>  isset($rs_tranfile2["warcde"]) ? $rs_tranfile2["warcde"] : '',
            "warehouse_floor_id" =>  isset($rs_tranfile2["warehouse_floor_id"]) ? $rs_tranfile2["warehouse_floor_id"] : '',
            "warehouse_staff_id" =>  isset($rs_tranfile2["warehouse_staff_id"]) ? $rs_tranfile2["warehouse_staff_id"] : '',
            "unmcde" =>  $retedit_unmcde,
            "unmdsc" =>  !empty($rs_tranfile2["unmdsc"]) ? trim((string)$rs_tranfile2["unmdsc"]) : $retedit_unmcde,
            "allow_empty_location" =>  (empty($rs_tranfile2["warcde"]) && empty($rs_tranfile2["warehouse_floor_id"])) ? '1' : '0',
            "recid" =>  $rs_tranfile2["tranfile2_recid"]
        ];

        $xret["msg"] = "retEdit";
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "submitEdit"){

        if(!empty($_POST['price_edit'])){
            $_POST['price_edit'] = str_replace(",","",$_POST['price_edit']);
        }
        if(!empty($_POST['amount_edit'])){
            $_POST['amount_edit'] = str_replace(",","",$_POST['amount_edit']);
        }

        $_POST['warcde_edit'] = isset($_POST['warcde_edit']) ? trim((string)$_POST['warcde_edit']) : '';
        $_POST['warehouse_floor_id_edit'] = isset($_POST['warehouse_floor_id_edit']) ? trim((string)$_POST['warehouse_floor_id_edit']) : '';
        $_POST['warehouse_staff_id_edit'] = isset($_POST['warehouse_staff_id_edit']) ? trim((string)$_POST['warehouse_staff_id_edit']) : '';
        $_POST['unmcde_edit'] = isset($_POST['unmcde_edit']) ? trim((string)$_POST['unmcde_edit']) : '';
        $allow_empty_location = isset($_POST['allow_empty_location_edit']) && $_POST['allow_empty_location_edit'] === '1';

        if(empty($_POST['xtrndte_1'])){
            $xret["status"] = 0;
            $xret["error1"] = 1;
            $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
        }

        if(!isset($_POST['itmcde_edit_hidden']) || empty($_POST['itmcde_edit_hidden'])){

            if($xret["error1"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            if(empty($_POST['itmcde_edit'])){
                $xret["msg"] .= "<b>Item</b> Cannot Be Empty"; 
            }else{
                $xret["msg"] .= "Invalid <b>Item</b>"; 
            }

            $xret["status"] = 0;
            $xret["error2"] = 1;

        }

        if(!isset($_POST['itmqty_edit']) || empty($_POST['itmqty_edit']) || $_POST['itmqty_edit'] == '0'){

            if($xret["error1"] == 1 || $xret["error2"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["msg"] .= "<b>Quantity</b> Cannot Be Empty or 0"; 
           
            $xret["status"] = 0;
            $xret["error3"] = 1;

        }

        if(invadj_is_invalid_uom($_POST['unmcde_edit'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Unit of Measure</b> must be selected and cannot be None";
            $xret["status"] = 0;
            $xret["error6"] = 1;
        }

        if($allow_empty_location){
            if(($_POST['warcde_edit'] === '' && $_POST['warehouse_floor_id_edit'] !== '') || ($_POST['warcde_edit'] !== '' && $_POST['warehouse_floor_id_edit'] === '')){

                if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error6"] == 1){
                    $xret["msg"] .= "</br>";
                }

                $xret["msg"] .= "<b>Warehouse</b> and <b>Warehouse Floor</b> must both be filled or both be None";
                $xret["status"] = 0;
                $xret["error4"] = 1;
            }
        }else{
            if($_POST['warcde_edit'] === ''){

                if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error6"] == 1){
                    $xret["msg"] .= "</br>";
                }

                $xret["msg"] .= "<b>Warehouse</b> Cannot Be Empty";
                $xret["status"] = 0;
                $xret["error4"] = 1;
            }

            if($_POST['warehouse_floor_id_edit'] === ''){

                if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error4"] == 1 || $xret["error6"] == 1){
                    $xret["msg"] .= "</br>";
                }

                $xret["msg"] .= "<b>Warehouse Floor</b> Cannot Be Empty";
                $xret["status"] = 0;
                $xret["error5"] = 1;
            }
        }

        if($xret["status"] == 1){
            $pricing = invadj_get_item_pricing($link, $_POST['itmcde_edit_hidden'], $_POST['unmcde_edit']);
            $stkqty = (float)$_POST['itmqty_edit'];
            if($pricing['has_conversion']){
                $stkqty = (float)$_POST['itmqty_edit'] * $pricing['conversion'];
            }
            $submitted_unit_price = isset($_POST['price_edit']) ? trim((string)$_POST['price_edit']) : '';
            $final_unit_price = ($submitted_unit_price !== '') ? (float)$submitted_unit_price : $pricing['unit_price'];
            $computed_total = $final_unit_price * (float)$_POST['itmqty_edit'];

            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_edit_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_edit'];
            $arr_record['stkqty'] 	= $stkqty;
            $arr_record['untprc'] 	= $final_unit_price;
            $arr_record['extprc'] 	= $computed_total;
	            $arr_record['warcde'] 	= $_POST['warcde_edit'];
	            $arr_record['warehouse_floor_id'] = $_POST['warehouse_floor_id_edit'];
	            $arr_record['warehouse_staff_id'] = $_POST['warehouse_staff_id_edit'];
	            $arr_record['unmcde']     = $_POST['unmcde_edit'];

	            $select_log_old = "SELECT * FROM tranfile2 WHERE recid = ? LIMIT 1";
	            $stmt_log_old = $link->prepare($select_log_old);
	            $stmt_log_old->execute(array($_POST['recid']));
	            $log_old_record = $stmt_log_old->fetch();

	            PDO_UpdateRecord($link,"tranfile2",$arr_record,"recid = ?",array($_POST['recid']));

	            // Log activity: edit line item
	            $log_format_number = function($value){
	                $value = trim(str_replace(',', '', (string)$value));
	                if($value === '' || !is_numeric($value)){
	                    return trim((string)$value);
	                }
	                $formatted = number_format((float)$value, 2, '.', '');
	                $formatted = rtrim(rtrim($formatted, '0'), '.');
	                return ($formatted === '-0') ? '0' : $formatted;
	            };
	            $log_get_warehouse_name = function($warcde) use ($link){
	                $warcde = trim((string)$warcde);
	                if($warcde === ''){
	                    return '';
	                }
	                $stmt_lookup = $link->prepare("SELECT warehouse_name FROM warehouse WHERE warcde = ? LIMIT 1");
	                $stmt_lookup->execute(array($warcde));
	                $rs_lookup = $stmt_lookup->fetch();
	                return $rs_lookup ? trim((string)$rs_lookup['warehouse_name']) : $warcde;
	            };
	            $log_get_floor_name = function($warehouse_floor_id) use ($link){
	                $warehouse_floor_id = trim((string)$warehouse_floor_id);
	                if($warehouse_floor_id === ''){
	                    return '';
	                }
	                $stmt_lookup = $link->prepare("SELECT floor_no, floor_name FROM warehouse_floor WHERE warehouse_floor_id = ? LIMIT 1");
	                $stmt_lookup->execute(array($warehouse_floor_id));
	                $rs_lookup = $stmt_lookup->fetch();
	                if(!$rs_lookup){
	                    return $warehouse_floor_id;
	                }
	                if(trim((string)$rs_lookup['floor_no']) !== ''){
	                    return trim((string)$rs_lookup['floor_no']);
	                }
	                return trim((string)$rs_lookup['floor_name']);
	            };
	            $log_get_staff_name = function($warehouse_staff_id) use ($link){
	                $warehouse_staff_id = trim((string)$warehouse_staff_id);
	                if($warehouse_staff_id === ''){
	                    return '';
	                }
	                $stmt_lookup = $link->prepare("SELECT fname, lname FROM warehouse_staff WHERE warehouse_staff_id = ? LIMIT 1");
	                $stmt_lookup->execute(array($warehouse_staff_id));
	                $rs_lookup = $stmt_lookup->fetch();
	                if(!$rs_lookup){
	                    return $warehouse_staff_id;
	                }
	                return trim((string)$rs_lookup['fname'] . ' ' . $rs_lookup['lname']);
	            };
	            $log_old_item_code = isset($log_old_record['itmcde']) ? trim((string)$log_old_record['itmcde']) : '';
	            $log_new_item_code = trim((string)$_POST['itmcde_edit_hidden']);
	            $log_old_item_desc = invadjust_get_item_desc($link, $log_old_item_code);
	            $log_new_item_desc = invadjust_get_item_desc($link, $log_new_item_code);
	            $log_change_parts = array();

	            if((float)(isset($log_old_record['itmqty']) ? $log_old_record['itmqty'] : 0) !== (float)$_POST['itmqty_edit']){
	                $log_change_parts[] = "qty from '" . $log_format_number(isset($log_old_record['itmqty']) ? $log_old_record['itmqty'] : '') . "' to '" . $log_format_number($_POST['itmqty_edit']) . "'";
	            }

	            if(trim((string)(isset($log_old_record['unmcde']) ? $log_old_record['unmcde'] : '')) !== trim((string)$_POST['unmcde_edit'])){
	                $log_change_parts[] = "uom from '" . invadjust_get_uom_desc($link, isset($log_old_record['unmcde']) ? $log_old_record['unmcde'] : '') . "' to '" . invadjust_get_uom_desc($link, isset($_POST['unmcde_edit']) ? $_POST['unmcde_edit'] : '') . "'";
	            }

	            if($log_format_number(isset($log_old_record['untprc']) ? $log_old_record['untprc'] : '') !== $log_format_number($final_unit_price)){
	                $log_change_parts[] = "price from '" . $log_format_number(isset($log_old_record['untprc']) ? $log_old_record['untprc'] : '') . "' to '" . $log_format_number($final_unit_price) . "'";
	            }

	            if(trim((string)(isset($log_old_record['warcde']) ? $log_old_record['warcde'] : '')) !== trim((string)$_POST['warcde_edit'])){
	                $log_change_parts[] = "warehouse from '" . $log_get_warehouse_name(isset($log_old_record['warcde']) ? $log_old_record['warcde'] : '') . "' to '" . $log_get_warehouse_name($_POST['warcde_edit']) . "'";
	            }

	            if(trim((string)(isset($log_old_record['warehouse_floor_id']) ? $log_old_record['warehouse_floor_id'] : '')) !== trim((string)$_POST['warehouse_floor_id_edit'])){
	                $log_change_parts[] = "warehouse floor from '" . $log_get_floor_name(isset($log_old_record['warehouse_floor_id']) ? $log_old_record['warehouse_floor_id'] : '') . "' to '" . $log_get_floor_name($_POST['warehouse_floor_id_edit']) . "'";
	            }

	            if(trim((string)(isset($log_old_record['warehouse_staff_id']) ? $log_old_record['warehouse_staff_id'] : '')) !== trim((string)$_POST['warehouse_staff_id_edit'])){
	                $log_change_parts[] = "warehouse staff from '" . $log_get_staff_name(isset($log_old_record['warehouse_staff_id']) ? $log_old_record['warehouse_staff_id'] : '') . "' to '" . $log_get_staff_name($_POST['warehouse_staff_id_edit']) . "'";
	            }

	            if($log_old_item_code !== $log_new_item_code){
	                $log_remarks = $log_username . " edited item from '" . $log_old_item_desc . "' to '" . $log_new_item_desc . "' in docnum='" . $_POST['docnum'] . "'";
	            }else{
	                $log_remarks = $log_username . " edited item '" . $log_new_item_desc . "' in docnum='" . $_POST['docnum'] . "'";
	            }

	            if(!empty($log_change_parts)){
	                $log_remarks .= ": " . implode(', ', $log_change_parts);
	            }
	            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'ADJ', '', '', $log_username, $_POST['docnum'], '');

            $xret["msg"] = "submitEdit";
        }



    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        // delete
        $delete_id=$_POST['recid'];

        // Log activity: delete line item (capture item details before delete)
        $select_del_record = "SELECT itmcde, docnum FROM tranfile2 WHERE recid = ? LIMIT 1";
        $stmt_del_record = $link->prepare($select_del_record);
        $stmt_del_record->execute(array($delete_id));
        $rs_del_record = $stmt_del_record->fetch();
        if($rs_del_record){
            $log_itmdsc_del = invadjust_get_item_desc($link, $rs_del_record['itmcde']);
            $log_docnum_del = $rs_del_record['docnum'];
            $log_remarks = $log_username . " deleted item '" . $log_itmdsc_del . "' in docnum='" . $log_docnum_del . "'";
            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'delete', $log_fullname, $log_remarks, 0, '', 'ADJ', '', '', $log_username, $log_docnum_del, '');
        }

        $delete_query="DELETE  FROM  tranfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));


    }

    

    $xret["html"] .= "<tr style='font-weight:bold'>";
        $xret["html"] .= "<td>Item</td>";
        $xret["html"] .= "<td>Warehouse</td>";
        $xret["html"] .= "<td>Warehouse Floor</td>";
        $xret["html"] .= "<td style='text-align:right'>Quantity</td>";
        $xret["html"] .= "<td>UOM</td>";
        $xret["html"] .= "<td style='text-align:right'>Price per unit</td>";
        $xret["html"] .= "<td style='text-align:right'>Amount</td>";
        $xret["html"] .= "<td class='text-center'>Action</td>";
    $xret["html"] .= "</tr>";


            $select_salesfile2="SELECT itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.recid as tranfile2_recid,
            warehouse.warehouse_name, warehouse_floor.floor_no, itemunitmeasurefile.unmdsc
            FROM tranfile2
            LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde
            LEFT JOIN warehouse ON warehouse.warcde = tranfile2.warcde
            LEFT JOIN warehouse_floor ON warehouse_floor.warehouse_floor_id = tranfile2.warehouse_floor_id
            LEFT JOIN itemunitmeasurefile ON itemunitmeasurefile.unmcde = tranfile2.unmcde
            WHERE docnum=?";
            $stmt_salesfile2	= $link->prepare($select_salesfile2);
            $stmt_salesfile2->execute(array($docnum));

            $trntot = 0;

            while($rs_salesfile2 = $stmt_salesfile2->fetch()){

                $trntot += $rs_salesfile2['extprc'];

                if(!empty($rs_salesfile2['untprc'])){
                    $rs_salesfile2['untprc'] =  number_format($rs_salesfile2['untprc'],2);
                }
                if(!empty($rs_salesfile2['extprc'])){
                    $rs_salesfile2['extprc'] =  number_format($rs_salesfile2['extprc'],2);
                }
                if(!empty($rs_salesfile2['itmqty'])){
                    $rs_salesfile2['itmqty'] =  number_format($rs_salesfile2['itmqty']);
                }

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>".htmlspecialchars($rs_salesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['warehouse_name']) ? $rs_salesfile2['warehouse_name'] : '',ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['floor_no']) ? $rs_salesfile2['floor_no'] : '',ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['itmqty']."</td>";
                    $xret["html"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['unmdsc']) ? $rs_salesfile2['unmdsc'] : '',ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
                    $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";
                        $xret["html"].= "<div class='dropdown'>";
                            $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                                $xret["html"].= "Action";
                            $xret["html"].= "</button>";
        
                            $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'>";
                                $xret["html"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_salesfile2['tranfile2_recid']."')\">";
                                    $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                                $xret["html"].= "</li>";
            
                                $xret["html"].= "<li onclick=\"salesfile2('delete' , '".$rs_salesfile2['tranfile2_recid']."')\">";
                                    $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                                $xret["html"].= "</li>";
                                
                            $xret["html"].= "</ul>";
                        $xret["html"].= "</div>";
                    $xret["html"].= "</td>";
                $xret["html"] .= "</tr>";

                // FOR MOBILE
                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Item</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars($rs_salesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Warehouse</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['warehouse_name']) ? $rs_salesfile2['warehouse_name'] : '',ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Warehouse Floor</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['floor_no']) ? $rs_salesfile2['floor_no'] : '',ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Quantity</td>";
                    $xret["html_mobile"] .= "<td>".$rs_salesfile2['itmqty']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>UOM</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['unmdsc']) ? $rs_salesfile2['unmdsc'] : '',ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Price per unit</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Amount</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"].= "<td  class='fw-bold' style='text-align:left' data-label='Action'>Action</td>";
                    $xret["html_mobile"].= "<td>";
                        $xret["html_mobile"].= "<div class='dropdown'>";
                            $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                                $xret["html_mobile"].= "Action";
                            $xret["html_mobile"].= "</button>";

                            $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'>";
                                $xret["html_mobile"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_salesfile2['tranfile2_recid']."')\">";
                                    $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                                $xret["html_mobile"].= "</li>";
            
                                $xret["html_mobile"].= "<li onclick=\"salesfile2('delete' , '".$rs_salesfile2['tranfile2_recid']."')\">";
                                    $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                                $xret["html_mobile"].= "</li>";
                                
                            $xret["html_mobile"].= "</ul>";
                        $xret["html_mobile"].= "</div>";
                    $xret["html_mobile"].= "</td>";
                $xret["html_mobile"] .= "</tr>";                
            }

 


    

    $select_trntot='SELECT * FROM tranfile1 WHERE docnum=?';
	$stmt_trntot	= $link->prepare($select_trntot);
	$stmt_trntot->execute(array($_POST["docnum"]));
    $rs_trntot = $stmt_trntot->fetch();

    $arr_record_salesfile1 = array();			
    $arr_record_salesfile1['trntot'] = $trntot;
    if(isset($_POST["event_action"]) && ($_POST["event_action"]  == "insert_new" || $_POST["event_action"]  == "insert_old" || $_POST["event_action"]  == "submitEdit" || $_POST["event_action"]  == "delete" || $_POST["event_action"]  == "insert")){
        PDO_UpdateRecord($link,"tranfile1",$arr_record_salesfile1,"recid = ?",array($rs_trntot["recid"]));  
    }

    
    $xret["trntot"] = number_format($trntot,2);

echo json_encode($xret);
?>
