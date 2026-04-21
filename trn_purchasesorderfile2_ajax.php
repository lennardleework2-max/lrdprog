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
    $log_module = 'PURCHASE ORDER';
    $log_trndte = date('Y-m-d H:i:s');

    // Helper function to get item description
    function purchasesorder_get_item_desc($link, $itmcde){
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
    function purchasesorder_get_uom_desc($link, $unmcde){
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
    $xret["uom_labels"] = array();
    $xret["purchasesDefault"] = array();
    $xret["matched_checked"] = false;
    $xret["error1"] = 0;
    $xret["error2"] = 0;
    $xret["error3"] = 0;
    $xret["error4"] = 0;

    $current_usercode = '';
    if(isset($_POST['usercode_1']) && trim((string)$_POST['usercode_1']) !== ''){
        $current_usercode = trim((string)$_POST['usercode_1']);
    }else if(isset($_SESSION['usercode']) && trim((string)$_SESSION['usercode']) !== ''){
        $current_usercode = trim((string)$_SESSION['usercode']);
    }

    function purchasesorder_resolve_untmea($link, $unmcde, $untmea){
        $unmcde = trim((string)$unmcde);
        $untmea = trim((string)$untmea);

        if($untmea !== '' || $unmcde === ''){
            return $untmea;
        }

        $select_uom = "SELECT unmdsc FROM itemunitmeasurefile WHERE unmcde = ? LIMIT 1";
        $stmt_uom = $link->prepare($select_uom);
        $stmt_uom->execute(array($unmcde));
        $rs_uom = $stmt_uom->fetch();

        if(!empty($rs_uom) && isset($rs_uom['unmdsc'])){
            return trim((string)$rs_uom['unmdsc']);
        }

        return '';
    }

    function purchasesorder_get_item_conversion($link, $itmcde, $unmcde){
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

    function purchasesorder_get_latest_base_price($link, $itmcde){
        $itmcde = trim((string)$itmcde);

        if($itmcde === ''){
            return '';
        }

        $date_today = date('Y-m-d');
        $select_getprice = "SELECT tranfile2.untprc, tranfile2.unmcde
                            FROM tranfile2
                            LEFT JOIN tranfile1 ON tranfile2.docnum = tranfile1.docnum
                            WHERE tranfile2.itmcde = ?
                            AND tranfile1.trndte <= ?
                            AND tranfile1.trncde = 'PUR'
                            ORDER BY tranfile1.trndte DESC, tranfile1.recid DESC, tranfile2.recid DESC
                            LIMIT 1";
        $stmt_getprice = $link->prepare($select_getprice);
        $stmt_getprice->execute(array($itmcde, $date_today));
        $rs_getprice = $stmt_getprice->fetch();

        if(!empty($rs_getprice) && $rs_getprice['untprc'] !== '' && $rs_getprice['untprc'] !== null){
            $base_price = (float)$rs_getprice['untprc'];
            $conversion_data = purchasesorder_get_item_conversion($link, $itmcde, isset($rs_getprice['unmcde']) ? $rs_getprice['unmcde'] : '');

            if($conversion_data['found'] && (float)$conversion_data['conversion'] > 0){
                $base_price = $base_price / (float)$conversion_data['conversion'];
            }

            return $base_price;
        }

        return '';
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

                    // $xret["html"] .= "<td class='fw-bold'>";
                    //     $xret["html"] .= "Inventory Type";             
                    // $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='fw-bold text-center'>";
                        $xret["html"] .= "Action";             
                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";

            $select_itemfile="SELECT * FROM itemfile WHERE itmdsc LIKE ? ORDER BY itmdsc ASC";
            $stmt_itemfile	= $link->prepare($select_itemfile);
            $stmt_itemfile->execute(array('%'.$_POST['search_itm'].'%'));
            while($rs_itemfile = $stmt_itemfile->fetch()){

                $latest_base_price = purchasesorder_get_latest_base_price($link, $rs_itemfile['itmcde']);

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['itmdsc'];
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='text-center'>";
                        $xret["html"] .= "<button type='button' onclick='select_item_modal(" 
                            . json_encode($rs_itemfile['itmcde']) . "," 
                            . json_encode($rs_itemfile['itmdsc']) . "," 
                            . json_encode($_POST['event_action_itmsearch']) .","
                            . json_encode($latest_base_price) 
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

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "get_item_uom_labels"){

        $itmcde = isset($_POST["itmcde"]) ? trim((string)$_POST["itmcde"]) : '';
        if($itmcde !== ''){
            $select_item_units = "SELECT unmcde, conversion FROM itemunitfile WHERE itmcde = ?";
            $stmt_item_units = $link->prepare($select_item_units);
            $stmt_item_units->execute(array($itmcde));

            while($rs_item_units = $stmt_item_units->fetch()){
                $item_unmcde = trim((string)$rs_item_units["unmcde"]);
                if($item_unmcde === ''){
                    continue;
                }

                $xret["uom_labels"][$item_unmcde] = array(
                    "conversion" => $rs_item_units["conversion"]
                );
            }
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

        $xret["retEdit"]['xprice'] = purchasesorder_get_latest_base_price($link, $xitmcde);

        echo json_encode($xret);
        return;
    }

    $trncde = $_POST["trncde"];
    $docnum = $_POST['docnum'];

    $rs_purchasesdf = false;
    try{
        $select_db_purchasesdf="SELECT * FROM default_purchases WHERE is_selected='1' LIMIT 1";
        $stmt_purchasesdf	= $link->prepare($select_db_purchasesdf);
        $stmt_purchasesdf->execute();
        $rs_purchasesdf = $stmt_purchasesdf->fetch();
    }catch(PDOException $e){
        $rs_purchasesdf = false;
    }

    if(!empty($rs_purchasesdf)){
        if(!empty($rs_purchasesdf['shipto_default'])){
            $xret["purchasesDefault"]["shipto_default"] = $rs_purchasesdf['shipto_default'];
        }else{
            $xret["purchasesDefault"]["shipto_default"] = '';
        }

        if(!empty($rs_purchasesdf['orderby_default'])){
            $xret["purchasesDefault"]["orderby_default"] = $rs_purchasesdf['orderby_default'];
        }else{
            $xret["purchasesDefault"]["orderby_default"] = '';
        }
    }else{
        $xret["purchasesDefault"]["shipto_default"] = '';
        $xret["purchasesDefault"]["orderby_default"] = '';
    }    

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "save_exit" || $_POST["event_action"] == "save_new")){

        $docnum = $_POST["docnum_1"];

        $select_docnum='SELECT * FROM purchasesorderfile1 WHERE docnum=?';
        $stmt_docnum	= $link->prepare($select_docnum);
        $stmt_docnum->execute(array($docnum));
        $rs_docnum = $stmt_docnum->fetch();

        if(empty($rs_docnum)){

            $select_po_qr_id="SELECT * FROM purchasesorderfile1 WHERE po_qr_id='".$_POST['purchase_order_qr_id_1']."'";
            $stmt_po_qr_id	= $link->prepare($select_po_qr_id);
            $stmt_po_qr_id->execute();
            $rs_po_qr_id = $stmt_po_qr_id->fetch();

            if(!empty($rs_po_qr_id) && !empty($_POST['purchase_order_qr_id_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "Purchase Order ID: <b>".$_POST['purchase_order_qr_id_1']."</b> in use.";
                $xret["error1"] = 1;
            }

            if(empty($_POST['trndte_1'])){

                if($xret["error1"] == 1){
                    $xret["msg"] .= "</br><b>Tran. Date</b> cannot be empty.";
                }else{
                    $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
                }

                $xret["status"] = 0;

            }
            if($xret["status"] == 1){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record = array();
                $arr_record['docnum'] 	= $docnum;
                $arr_record['orderby'] 	= $_POST['orderby_1'];
                $arr_record['shipto'] 	= $_POST['shipto_1'];
                $arr_record['suppcde'] 	= $_POST['cusname_1'];
                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['trndte'] 	= $_POST['trndte_1'];
                $arr_record['paydate'] 	= $_POST['paydate_1'];
                $arr_record['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record['remarks'] 	= $_POST['remarks_1'];
                $arr_record['po_qr_id'] 	= $_POST['purchase_order_qr_id_1'];
                $arr_record['usercode'] 	= $current_usercode;
                $arr_record['trncde'] 	= $trncde;

                PDO_InsertRecord($link,'purchasesorderfile1',$arr_record, false);

                // Log activity: add header
	                $log_remarks = useractivitylog_build_insert_docnum_remark($docnum);
	                PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'POR', '', '', $log_username, $docnum, '');

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
    
                $arr_record_update['orderby'] 	= $_POST['orderby_1'];
                $arr_record_update['shipto'] 	= $_POST['shipto_1'];
                $arr_record_update['suppcde'] 	= $_POST['cusname_1'];
                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['trndte'] 	= $_POST['trndte_1'];
                $arr_record_update['paydate'] 	= $_POST['paydate_1'];
                $arr_record_update['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record_update['remarks'] 	= $_POST['remarks_1'];
                $arr_record_update['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record_update['po_qr_id'] 	= $_POST['purchase_order_qr_id_1'];
                $arr_record_update['usercode'] 	= $current_usercode;
                PDO_UpdateRecord($link,"purchasesorderfile1",$arr_record_update,"recid = ?",array($recid),false);

                // Log activity: edit header
	                $log_remarks = useractivitylog_build_header_edit_remark($link, 'POR', $docnum, $rs_docnum, $arr_record_update);
	                if($log_remarks !== ''){
	                    PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'POR', '', '', $log_username, $docnum, '');
	                }
            }


        }
        
        if($_POST["event_action"] == "save_new" && $xret["status"] == 1){

            $select_db_docnum='SELECT * FROM purchasesorderfile1 WHERE trncde=? ORDER BY docnum DESC LIMIT 1';
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

        $_POST['unmcde_add'] = isset($_POST['unmcde_add']) ? trim((string)$_POST['unmcde_add']) : '';
        $_POST['untmea_add'] = isset($_POST['untmea_add']) ? trim((string)$_POST['untmea_add']) : '';
        $_POST['untmea_add'] = purchasesorder_resolve_untmea($link, $_POST['unmcde_add'], $_POST['untmea_add']);

        $select_check="SELECT * FROM purchasesorderfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();

        if(empty($_POST['trndte_1'])){
            $xret["status"] = 0;
            $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
            $xret["error1"] = 1;
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

        $select_po_qr_id="SELECT * FROM purchasesorderfile1 WHERE po_qr_id='".$_POST['purchase_order_qr_id_1']."' AND docnum!='".$_POST['docnum']."'";
        $stmt_po_qr_id	= $link->prepare($select_po_qr_id);
        $stmt_po_qr_id->execute();
        $rs_po_qr_id = $stmt_po_qr_id->fetch();

        if(!empty($rs_po_qr_id) && !empty($_POST['purchase_order_qr_id_1'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["status"] = 0;
            $xret["msg"] .= "Purchase Order ID: <b>".$_POST['purchase_order_qr_id_1']."</b> in use.";
            $xret["error4"] = 1;
        }
        
        if($xret["status"] == 1){
            if(empty($rs_check)){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_file1 = array();
                $arr_record_file1['docnum'] 	= $_POST["docnum"];
                $arr_record_file1['orderby'] 	= $_POST['orderby_1'];
                $arr_record_file1['shipto'] 	= $_POST['shipto_1'];
                $arr_record_file1['suppcde'] 	= $_POST['cusname_1'];
                $arr_record_file1['trntot'] 	= $_POST['trntot_1'];
                $arr_record_file1['trndte'] 	= $_POST['trndte_1'];
                $arr_record_file1['paydate'] 	= $_POST['paydate_1'];
                $arr_record_file1['paydetails'] = $_POST['payment_details_1'];
                $arr_record_file1['remarks'] 	= $_POST['remarks_1'];
                $arr_record_file1['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record_file1['trncde']     = $trncde;
                $arr_record_file1['po_qr_id'] 	= $_POST['purchase_order_qr_id_1'];
    
                PDO_InsertRecord($link,'purchasesorderfile1',$arr_record_file1, false);
    
                $xret["msg"] = "insert_new";
            }else{
                $xret["msg"] = "insert_old";
            }
    
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            $arr_record['unmcde']   = $_POST['unmcde_add'];
            $arr_record['untmea']   = $_POST['untmea_add'];
            $arr_record['untprc'] 	= $_POST['price_add'];
            $arr_record['extprc'] 	= $_POST['amount_add'];
            $arr_record_file1['trncde']     = $trncde;

            PDO_InsertRecord($link,'purchasesorderfile2',$arr_record, false);

            // Log activity: add line item
            $log_itmdsc_add = purchasesorder_get_item_desc($link, $_POST['itmcde_add_hidden']);
            $log_uomdsc_add = purchasesorder_get_uom_desc($link, isset($_POST['unmcde_add']) ? $_POST['unmcde_add'] : '');
            $log_remarks = $log_username . " added item '" . $log_itmdsc_add . "' qty='" . $_POST['itmqty_add'] . "' uom='" . $log_uomdsc_add . "' price='" . $_POST['price_add'] . "' in docnum='" . $_POST['docnum'] . "'";
            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'POR', '', '', $log_username, $_POST['docnum'], '');
        }


    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_purchasesorderfile2="SELECT purchasesorderfile2.itmcde as 'itmcde', purchasesorderfile2.itmqty, purchasesorderfile2.unmcde, purchasesorderfile2.untmea, purchasesorderfile2.untprc, purchasesorderfile2.extprc, itemfile.itmdsc, itemunitmeasurefile.unmdsc, purchasesorderfile2.recid as purchasesorderfile2_recid FROM purchasesorderfile2 LEFT JOIN itemfile ON itemfile.itmcde = purchasesorderfile2.itmcde LEFT JOIN itemunitmeasurefile ON itemunitmeasurefile.unmcde = purchasesorderfile2.unmcde WHERE purchasesorderfile2.recid=?";
        $stmt_purchasesorderfile2	= $link->prepare($select_purchasesorderfile2);
        $stmt_purchasesorderfile2->execute(array($_POST["recid"]));
        $rs_purchasesorderfile2 = $stmt_purchasesorderfile2->fetch();

        $retedit_unmcde = isset($rs_purchasesorderfile2["unmcde"]) ? trim((string)$rs_purchasesorderfile2["unmcde"]) : '';
        $retedit_untmea = isset($rs_purchasesorderfile2["unmdsc"]) ? trim((string)$rs_purchasesorderfile2["unmdsc"]) : '';
        if($retedit_untmea === ''){
            $retedit_untmea = isset($rs_purchasesorderfile2["untmea"]) ? trim((string)$rs_purchasesorderfile2["untmea"]) : '';
        }

        if(!empty($rs_purchasesorderfile2["untprc"])){
            $rs_purchasesorderfile2["untprc"] = number_format($rs_purchasesorderfile2["untprc"],2);
        }
        if($rs_purchasesorderfile2["extprc"]){
            $rs_purchasesorderfile2["extprc"] = number_format($rs_purchasesorderfile2["extprc"],2);
        }

        $xret["retEdit"] = [
            "itmcde" =>  $rs_purchasesorderfile2["itmcde"],
            "itmdsc" =>  $rs_purchasesorderfile2["itmdsc"],
            "itmqty" =>  $rs_purchasesorderfile2["itmqty"],
            "unmcde" =>  $retedit_unmcde,
            "untmea" =>  $retedit_untmea,
            "untprc" =>  $rs_purchasesorderfile2["untprc"],
            "extprc" =>  $rs_purchasesorderfile2["extprc"],
            "recid" =>  $rs_purchasesorderfile2["purchasesorderfile2_recid"]
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
        $_POST['unmcde_edit'] = isset($_POST['unmcde_edit']) ? trim((string)$_POST['unmcde_edit']) : '';
        $_POST['untmea_edit'] = isset($_POST['untmea_edit']) ? trim((string)$_POST['untmea_edit']) : '';
        $_POST['untmea_edit'] = purchasesorder_resolve_untmea($link, $_POST['unmcde_edit'], $_POST['untmea_edit']);
        
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
        
	        if($xret["status"] == 1){

	            $arr_record = array();
	            $arr_record['docnum'] 	= $_POST['docnum'];
	            $arr_record['itmcde'] 	= $_POST['itmcde_edit_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_edit'];
	            $arr_record['unmcde']   = $_POST['unmcde_edit'];
	            $arr_record['untmea']   = $_POST['untmea_edit'];
	            $arr_record['untprc'] 	= $_POST['price_edit'];
	            $arr_record['extprc'] 	= $_POST['amount_edit'];

	            $select_log_old = "SELECT * FROM purchasesorderfile2 WHERE recid = ? LIMIT 1";
	            $stmt_log_old = $link->prepare($select_log_old);
	            $stmt_log_old->execute(array($_POST['recid']));
	            $log_old_record = $stmt_log_old->fetch();
	    
	            PDO_UpdateRecord($link,"purchasesorderfile2",$arr_record,"recid = ?",array($_POST['recid']),false);

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
	            $log_old_item_code = isset($log_old_record['itmcde']) ? trim((string)$log_old_record['itmcde']) : '';
	            $log_new_item_code = trim((string)$_POST['itmcde_edit_hidden']);
	            $log_old_item_desc = purchasesorder_get_item_desc($link, $log_old_item_code);
	            $log_new_item_desc = purchasesorder_get_item_desc($link, $log_new_item_code);
	            $log_change_parts = array();

	            if((float)(isset($log_old_record['itmqty']) ? $log_old_record['itmqty'] : 0) !== (float)$_POST['itmqty_edit']){
	                $log_change_parts[] = "qty from '" . $log_format_number(isset($log_old_record['itmqty']) ? $log_old_record['itmqty'] : '') . "' to '" . $log_format_number($_POST['itmqty_edit']) . "'";
	            }

	            if(trim((string)(isset($log_old_record['unmcde']) ? $log_old_record['unmcde'] : '')) !== trim((string)$_POST['unmcde_edit'])){
	                $log_change_parts[] = "uom from '" . purchasesorder_get_uom_desc($link, isset($log_old_record['unmcde']) ? $log_old_record['unmcde'] : '') . "' to '" . purchasesorder_get_uom_desc($link, isset($_POST['unmcde_edit']) ? $_POST['unmcde_edit'] : '') . "'";
	            }

	            if($log_format_number(isset($log_old_record['untprc']) ? $log_old_record['untprc'] : '') !== $log_format_number($_POST['price_edit'])){
	                $log_change_parts[] = "price from '" . $log_format_number(isset($log_old_record['untprc']) ? $log_old_record['untprc'] : '') . "' to '" . $log_format_number($_POST['price_edit']) . "'";
	            }

	            if($log_old_item_code !== $log_new_item_code){
	                $log_remarks = $log_username . " edited item from '" . $log_old_item_desc . "' to '" . $log_new_item_desc . "' in docnum='" . $_POST['docnum'] . "'";
	            }else{
	                $log_remarks = $log_username . " edited item '" . $log_new_item_desc . "' in docnum='" . $_POST['docnum'] . "'";
	            }

	            if(!empty($log_change_parts)){
	                $log_remarks .= ": " . implode(', ', $log_change_parts);
	            }
	            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'POR', '', '', $log_username, $_POST['docnum'], '');

            $xret["msg"] = "submitEdit";
        }

    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        // delete
        $delete_id=$_POST['recid'];

        // Log activity: delete line item (capture item details before delete)
        $select_del_record = "SELECT itmcde, docnum FROM purchasesorderfile2 WHERE recid = ? LIMIT 1";
        $stmt_del_record = $link->prepare($select_del_record);
        $stmt_del_record->execute(array($delete_id));
        $rs_del_record = $stmt_del_record->fetch();
        if($rs_del_record){
            $log_itmdsc_del = purchasesorder_get_item_desc($link, $rs_del_record['itmcde']);
            $log_docnum_del = $rs_del_record['docnum'];
            $log_remarks = $log_username . " deleted item '" . $log_itmdsc_del . "' in docnum='" . $log_docnum_del . "'";
            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'delete', $log_fullname, $log_remarks, 0, '', 'POR', '', '', $log_username, $log_docnum_del, '');
        }

        $delete_query="DELETE  FROM  purchasesorderfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));


    }




            $xret["html"] .= "<tr style='font-weight:bold'>";
                $xret["html"] .= "<td>Item</td>";
                $xret["html"] .= "<td style='text-align:right'>Quantity</td>";
                $xret["html"] .= "<td>UOM</td>";
                $xret["html"] .= "<td style='text-align:right'>Price</td>";
                $xret["html"] .= "<td style='text-align:right'>Amount</td>";
                $xret["html"] .= "<td class='text-center'>Action</td>";
            $xret["html"] .= "</tr>";  

            $select_purchasesfile2="SELECT tranfile2_recid as 'tranfile2_recid', itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, purchasesorderfile2.itmqty, purchasesorderfile2.untprc, purchasesorderfile2.extprc, purchasesorderfile2.untmea, purchasesorderfile2.unmcde, purchasesorderfile2.recid as purchasesorderfile2_recid, itemunitmeasurefile.unmdsc
            FROM purchasesorderfile2 LEFT JOIN itemfile ON itemfile.itmcde = purchasesorderfile2.itmcde
            LEFT JOIN itemunitmeasurefile ON itemunitmeasurefile.unmcde = purchasesorderfile2.unmcde
            WHERE docnum=?";
            $stmt_purchasesfile2	= $link->prepare($select_purchasesfile2);
            $stmt_purchasesfile2->execute(array($docnum));

            $trntot = 0;

            while($rs_purchasesfile2 = $stmt_purchasesfile2->fetch()){


                $disabled_btn_matched = '';

                $trntot += $rs_purchasesfile2['extprc'];

                if(!empty($rs_purchasesfile2['untprc'])){
                    $rs_purchasesfile2['untprc'] =  number_format($rs_purchasesfile2['untprc'],2);
                }
                if(!empty($rs_purchasesfile2['extprc'])){
                    $rs_purchasesfile2['extprc'] =  number_format($rs_purchasesfile2['extprc'],2);
                }
                if(!empty($rs_purchasesfile2['itmqty'])){
                    $rs_purchasesfile2['itmqty'] =  number_format($rs_purchasesfile2['itmqty']);
                }

                if(isset($rs_purchasesfile2['tranfile2_recid']) && !empty($rs_purchasesfile2['tranfile2_recid']) && 
                ($rs_purchasesfile2['tranfile2_recid'] != 0 && $rs_purchasesfile2['tranfile2_recid'] != '0')){
                    $disabled_btn_matched='disabled';

                    $select_purchasesfile2_chk="SELECT * FROM tranfile2 WHERE recid='".$rs_purchasesfile2['tranfile2_recid']."' LIMIT 1";
                    $stmt_purchasesfile2_chk	= $link->prepare($select_purchasesfile2_chk);
                    $stmt_purchasesfile2_chk->execute();
                    $rs_purchasesfile2_chk = $stmt_purchasesfile2_chk->fetch();
                }

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>".htmlspecialchars($rs_purchasesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_purchasesfile2['itmqty']."</td>";
                    $xret["html"] .= "<td>".htmlspecialchars(!empty($rs_purchasesfile2['unmdsc']) ? $rs_purchasesfile2['unmdsc'] : $rs_purchasesfile2['untmea'],ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_purchasesfile2['untprc']."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_purchasesfile2['extprc']."</td>";

                    $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";
                    $xret["html"].= "<div class='dropdown'>";

                        if($disabled_btn_matched == ''){
                            $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_purchasesfile2['purchasesorderfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                                $xret["html"].= "Action";
                            $xret["html"].= "</button>";
                        }else{
                            $xret["html"].= "<button  
                                onclick=\"matched_alert('".$disabled_btn_matched."','".$rs_purchasesfile2_chk['docnum']."')\"
                                class='btn btn-primary fw-bold' 
                                type='button' 
                                id='dropdownMenuButton1-".$rs_purchasesfile2['purchasesorderfile2_recid']."'
                                style='pointer-events:auto;opacity:0.5'>";
                                $xret["html"].= "Action";
                            $xret["html"].= "</button>";

                            $xret["matched_checked"] = true;
                        }                  
          
    
                        $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_purchasesfile2['purchasesorderfile2_recid']."'>";
                            $xret["html"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_purchasesfile2['purchasesorderfile2_recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                            $xret["html"].= "</li>";
        
                            $xret["html"].= "<li onclick=\"salesfile2('delete' , '".$rs_purchasesfile2['purchasesorderfile2_recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                            
                        $xret["html"].= "</ul>";
                    $xret["html"].= "</div>";
                $xret["html"].= "</td>";
                $xret["html"] .= "</tr>";

                // FOR MOBILE
                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Item</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars($rs_purchasesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Quantity</td>";
                    $xret["html_mobile"] .= "<td>".$rs_purchasesfile2['itmqty']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>UOM</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars(!empty($rs_purchasesfile2['unmdsc']) ? $rs_purchasesfile2['unmdsc'] : $rs_purchasesfile2['untmea'],ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Price</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_purchasesfile2['untprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Amount</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_purchasesfile2['extprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"].= "<td  class='fw-bold' style='text-align:left' data-label='Action'>Action</td>";
                    $xret["html_mobile"].= "<td>";
                        $xret["html_mobile"].= "<div class='dropdown'>";

                            if($disabled_btn_matched == ''){
                                $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_purchasesfile2['purchasesorderfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                                    $xret["html_mobile"].= "Action";
                                $xret["html_mobile"].= "</button>";
                            }else{
                                $xret["html_mobile"].= "<button  
                                    onclick=\"matched_alert('".$disabled_btn_matched."','".$rs_purchasesfile2_chk['docnum']."')\"
                                    class='btn btn-primary fw-bold' 
                                    type='button' 
                                    id='dropdownMenuButton1-".$rs_purchasesfile2['purchasesorderfile2_recid']."'
                                    style='pointer-events:auto;opacity:0.5'>";
                                    $xret["html_mobile"].= "Action";
                                $xret["html_mobile"].= "</button>";

                                $xret["matched_checked"] = true;
                            } 

                            $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_purchasesfile2['purchasesorderfile2_recid']."'>";
                                $xret["html_mobile"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_purchasesfile2['purchasesorderfile2_recid']."')\">";
                                    $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                                $xret["html_mobile"].= "</li>";
            
                                $xret["html_mobile"].= "<li onclick=\"salesfile2('delete' , '".$rs_purchasesfile2['purchasesorderfile2_recid']."')\">";
                                    $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                                $xret["html_mobile"].= "</li>";
                            $xret["html_mobile"].= "</ul>";
                        $xret["html_mobile"].= "</div>";
                    $xret["html_mobile"].= "</td>";
                $xret["html_mobile"] .= "</tr>";                
            }




    

    $select_trntot='SELECT * FROM purchasesorderfile1 WHERE docnum=?';
	$stmt_trntot	= $link->prepare($select_trntot);
	$stmt_trntot->execute(array($_POST["docnum"]));
    $rs_trntot = $stmt_trntot->fetch();

    $arr_record_purchasesfile1 = array();			
    $arr_record_purchasesfile1['trntot'] = $trntot;
    if(isset($_POST["event_action"]) && ($_POST["event_action"]  == "insert_new" || $_POST["event_action"]  == "insert_old" || $_POST["event_action"]  == "submitEdit" || $_POST["event_action"]  == "delete" || $_POST["event_action"]  == "insert")){
        PDO_UpdateRecord($link,"purchasesorderfile1",$arr_record_purchasesfile1,"recid = ?",array($rs_trntot["recid"]));  
    }

    
    $xret["trntot"] = number_format($trntot,2);

echo json_encode($xret);
?>
