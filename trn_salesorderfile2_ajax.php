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
    $log_module = 'SALES ORDER';
    $log_trndte = date('Y-m-d H:i:s');

    // Helper function to get item description
    function salesorder_get_item_desc($link, $itmcde){
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
    function salesorder_get_uom_desc($link, $unmcde){
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
    $xret["itm_search"] = '';
    $xret["retEdit"] = array();
    $xret["uom_labels"] = array();

    $xret["matched_checked"] = false;
    $xret["error1"] = 0;
    $xret["error2"] = 0;
    $xret["error3"] = 0;

    function salesorder_get_item_conversion($link, $itmcde, $unmcde){
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

                date_default_timezone_set('Asia/Manila');
                $date_today = date('Y-m-d');

                $select_db2 = "SELECT SUM(stkqty) as xsum FROM tranfile2 
                    LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
                    WHERE tranfile2.itmcde='".$rs_itemfile['itmcde']."' 
                    AND tranfile1.trndte<='".$date_today."'";
                $stmt_main2	= $link->prepare($select_db2);
                $stmt_main2->execute(array());
                $rs2 = $stmt_main2->fetch();

                $currentStock =  $rs2['xsum'];

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['itmdsc'];
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td>";
                        //$xret["html"] .= $rs_itemfile['inventory_type'];
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='text-center'>";
                        //$xret["html"].= "<button type='button' onclick='select_item_modal(\"".$rs_itemfile['itmcde']."\",\"".htmlspecialchars($rs_itemfile['itmdsc'],ENT_QUOTES)."\",\"".$rs_itemfile['untprc']."\",\"".$_POST['event_action_itmsearch']."\")' class='btn btn-primary fw-bold'>";
                        //$xret["html"].= "<button type='button' onclick='select_item_modal(\"".$rs_itemfile['itmcde']."\",\"".htmlspecialchars($rs_itemfile['itmdsc'],ENT_QUOTES)."\",\"".$_POST['event_action_itmsearch']."\")' class='btn btn-primary fw-bold'>";
                        $xret["html"] .= "<button type='button' onclick='select_item_modal(" 
                            . json_encode($rs_itemfile['itmcde']) . "," 
                            . json_encode($rs_itemfile['itmdsc']) . "," 
                            . json_encode($currentStock) . "," 
                            . json_encode($rs_itemfile['wholesaleprc']). ","
                            . json_encode($_POST['event_action_itmsearch']) 
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



    $trncde = $_POST["trncde"];
    $docnum = $_POST['docnum'];

    $date_time_today = date('Y-m-d H:i:s');

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "save_exit" || $_POST["event_action"] == "save_new")){

        $docnum = $_POST["docnum_1"];

        $select_docnum='SELECT * FROM salesorderfile1 WHERE docnum=?';
        $stmt_docnum	= $link->prepare($select_docnum);
        $stmt_docnum->execute(array($docnum));
        $rs_docnum = $stmt_docnum->fetch();

        if(empty($rs_docnum)){
 
            if(empty($_POST['xtrndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Upload. Date</b> cannot be empty.";
            }else{
                $_POST['xtrndte_1']  = (empty($_POST['xtrndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['xtrndte_1']));
                // $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record = array();
                $arr_record['docnum'] 	= $docnum;
                //$arr_record['orderby'] 	= $_POST['orderby_1'];
                $arr_record['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record['ordernum'] 	= $_POST['order_num_1'];
                $arr_record['shipto'] 	= $_POST['shipto_1'];
                if(isset($_POST['xcusname_1']) && !empty($_POST['xcusname_1']) && $_POST['xcusname_1'] != null){
                    $arr_record['cuscde'] 	= $_POST['xcusname_1'];
                }

                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['trndte'] 	= $_POST['xtrndte_1'];
                $arr_record['remarks'] 	= $_POST['remarks_1'];
                $arr_record['trncde'] 	= $trncde;
                $arr_record['order_status'] 	= $_POST['order_status1'];
                $arr_record['file_created_date'] = $date_time_today;
                PDO_InsertRecord($link,'salesorderfile1',$arr_record, false);

                // Log activity: add header
	                $log_remarks = useractivitylog_build_insert_docnum_remark($docnum);
	                PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'SOR', '', '', $log_username, $docnum, '');

                if($_POST["event_action"] == "save_exit"){
                    $xret["msg"] = "save_exit";
                }
            }

        }else{

            if(empty($_POST['xtrndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Upload. Date</b> cannot be empty.";
            }else{
                $xret["msg"] = "edit_exit";

                $recid = $rs_docnum['recid'];
                $_POST['xtrndte_1']  = (empty($_POST['xtrndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['xtrndte_1']));
                // $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_update = array();			
    
                //$arr_record_update['orderby'] 	= $_POST['orderby_1'];
                $arr_record_update['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record_update['ordernum'] 	= $_POST['order_num_1'];
                $arr_record_update['shipto'] 	= $_POST['shipto_1'];

                if(isset($_POST['xcusname_1']) && !empty($_POST['xcusname_1']) && $_POST['xcusname_1'] != null){
                    $arr_record_update['cuscde'] 	= $_POST['xcusname_1'];
                }

                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['trndte'] 	= $_POST['xtrndte_1'];
                // $arr_record_update['paydate'] 	= $_POST['paydate_1'];
                // $arr_record_update['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record_update['remarks'] 	= $_POST['remarks_1'];
                $arr_record_update['order_status'] 	= $_POST['order_status1'];
                PDO_UpdateRecord($link,"salesorderfile1",$arr_record_update,"recid = ?",array($recid),false);

                // Log activity: edit header
	                $log_remarks = useractivitylog_build_header_edit_remark($link, 'SOR', $docnum, $rs_docnum, $arr_record_update);
	                if($log_remarks !== ''){
	                    PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'SOR', '', '', $log_username, $docnum, '');
	                }
            }
        }

        if($_POST["event_action"] == "save_new" && $xret["status"] == 1){

            $select_db_docnum="SELECT * FROM salesorderfile1 WHERE trncde=? AND salesorderfile1.docnum NOT LIKE '%BOM%' ORDER BY docnum DESC LIMIT 1";
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

        $select_check="SELECT * FROM salesorderfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();

        if(empty($_POST['xtrndte_1'])){
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


        
        if($xret["status"] == 1){
            if(empty($rs_check)){
                $_POST['xtrndte_1']  = (empty($_POST['xtrndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['xtrndte_1']));
                // $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_file1 = array();
                $arr_record_file1['docnum'] 	= $_POST["docnum"];
                //$arr_record_file1['orderby'] 	= $_POST['orderby_1'];
                $arr_record_file1['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record_file1['ordernum'] 	= $_POST['order_num_1'];
                $arr_record_file1['shipto'] 	= $_POST['shipto_1'];

                if(isset($_POST['xcusname_1']) && !empty($_POST['xcusname_1']) && $_POST['xcusname_1'] != null){
                    $arr_record_file1['cuscde'] 	= $_POST['xcusname_1'];
                }
                $arr_record_file1['trntot'] 	= $_POST['trntot_1'];
                $arr_record_file1['trndte'] 	= $_POST['xtrndte_1'];
                $arr_record_file1['remarks'] 	= $_POST['remarks_1'];
                $arr_record_file1['trncde']     = $trncde;
                $arr_record_file1['order_status'] 	= $_POST['order_status1'];
                $arr_record_file1['file_created_date'] = $date_time_today;

                PDO_InsertRecord($link,'salesorderfile1',$arr_record_file1, false);
    
                $xret["msg"] = "insert_new";
            }else{
                $xret["msg"] = "insert_old";
            }
    
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            // $arr_record['stkqty'] 	= $_POST['itmqty_add'] * -1;
            $arr_record['unmcde']   = isset($_POST['unmcde_add']) ? $_POST['unmcde_add'] : '';
            $arr_record['untprc'] 	= $_POST['price_add'];
            $arr_record['extprc'] 	= $_POST['amount_add'];
            $arr_record['wholesaleprc'] 	= $_POST['wholesaleprc_add'];
            $arr_record_file1['trncde']     = $trncde;
            PDO_InsertRecord($link,'salesorderfile2',$arr_record, false);

            // Log activity: add line item
            $log_itmdsc_add = salesorder_get_item_desc($link, $_POST['itmcde_add_hidden']);
            $log_uomdsc_add = salesorder_get_uom_desc($link, isset($_POST['unmcde_add']) ? $_POST['unmcde_add'] : '');
            $log_remarks = $log_username . " added item '" . $log_itmdsc_add . "' qty='" . $_POST['itmqty_add'] . "' uom='" . $log_uomdsc_add . "' price='" . $_POST['price_add'] . "' in docnum='" . $_POST['docnum'] . "'";
            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'add', $log_fullname, $log_remarks, 0, '', 'SOR', '', '', $log_username, $_POST['docnum'], '');

            //ADDING BOM
            // $select_check_is_bom="SELECT * FROM itemfile WHERE itmcde='".$_POST['itmcde_add_hidden']."' LIMIT 1";
            // $stmt_check_is_bom	= $link->prepare($select_check_is_bom);
            // $stmt_check_is_bom->execute();
            // $rs_check_is_bom = $stmt_check_is_bom->fetch();

            // if($rs_check_is_bom['is_bom'] == '1'){

            //     $docnum_bom = $_POST['docnum'].'-BOM';

            //     $select_check_chk_bom="SELECT * FROM salesorderfile1 WHERE docnum='".$docnum_bom."' LIMIT 1";
            //     $stmt_check_chk_bom	= $link->prepare($select_check_chk_bom);
            //     $stmt_check_chk_bom->execute();
            //     $rs_check_chk_bom = $stmt_check_chk_bom->fetch();
    
            //     if(empty($rs_check_chk_bom)){      

            //         $_POST['xtrndte_1']  = (empty($_POST['xtrndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['xtrndte_1']));
    
            //         $arr_record_add_bom = array();
            //         $arr_record_add_bom['docnum'] = $docnum_bom;
            //         $arr_record_add_bom['trndte'] = $_POST['xtrndte_1'];
            //         $arr_record_add_bom['cuscde'] 	= $_POST['xcusname_1'];
            //         $arr_record_add_bom['trncde'] 	= 'SOR';
            //         $arr_record_add_bom['file_created_date'] = $date_time_today;
            //         $arr_record_add_bom['order_status'] 	= $_POST['order_status1'];
            //         PDO_InsertRecord($link,'salesorderfile1',$arr_record_add_bom, false);
            //     }                

            //     $itembom 	= $_POST['itmcde_add_hidden'];
                
            //     $select_itembom="SELECT * FROM itembomfile WHERE itmcde='".$itembom."'";
            //     $stmt_itembom	= $link->prepare($select_itembom);
            //     $stmt_itembom->execute();

            //     while($rs_itembom = $stmt_itembom->fetch()){
            //         $arr_record_bom = array();

            //         $arr_record_bom['docnum'] 	= trim($_POST['docnum']).'-BOM';
            //         $arr_record_bom['itmqty'] 	= $_POST['itmqty_add'] * $rs_itembom['itmqty'];
            //         $arr_record_bom['itmcde'] 	= $rs_itembom['itmcde2'];
            //         $arr_record_bom['bom_item'] 	= $itembom;
            //         $arr_record_bom['bom_linenum'] 	= $_POST['itmqty_add'];
            //         $arr_record_bom['trncde'] 	= 'SOR';
            //         PDO_InsertRecord($link,'salesorderfile2',$arr_record_bom, false);
            //     };
         
            // }            


        }


    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_salesorderfile2="SELECT salesorderfile2.wholesaleprc as wholesaleprc, salesorderfile2.itmcde as 'itmcde', salesorderfile2.itmqty, salesorderfile2.unmcde, salesorderfile2.untprc, salesorderfile2.extprc, itemfile.itmdsc, itemunitmeasurefile.unmdsc, salesorderfile2.recid as salesorderfile2_recid FROM salesorderfile2 LEFT JOIN itemfile ON itemfile.itmcde = salesorderfile2.itmcde LEFT JOIN itemunitmeasurefile ON itemunitmeasurefile.unmcde = salesorderfile2.unmcde WHERE salesorderfile2.recid=?";
        $stmt_salesorderfile2	= $link->prepare($select_salesorderfile2);
        $stmt_salesorderfile2->execute(array($_POST["recid"]));
        $rs_salesorderfile2 = $stmt_salesorderfile2->fetch();

        if(!empty($rs_salesorderfile2["untprc"])){
            $rs_salesorderfile2["untprc"] = number_format($rs_salesorderfile2["untprc"],2);
        }
        if($rs_salesorderfile2["extprc"]){
            $rs_salesorderfile2["extprc"] = number_format($rs_salesorderfile2["extprc"],2);
        }

        $retedit_unmcde = isset($rs_salesorderfile2["unmcde"]) ? trim((string)$rs_salesorderfile2["unmcde"]) : '';
        $xret["retEdit"] = [
            "itmcde" =>  $rs_salesorderfile2["itmcde"],
            "itmdsc" =>  $rs_salesorderfile2["itmdsc"],
            "itmqty" =>  $rs_salesorderfile2["itmqty"],
            "unmcde" =>  $retedit_unmcde,
            "unmdsc" =>  !empty($rs_salesorderfile2["unmdsc"]) ? trim((string)$rs_salesorderfile2["unmdsc"]) : $retedit_unmcde,
            "untprc" =>  $rs_salesorderfile2["untprc"],
            "extprc" =>  $rs_salesorderfile2["extprc"],
            "recid" =>  $rs_salesorderfile2["salesorderfile2_recid"],
            "wholesaleprc" =>  $rs_salesorderfile2["wholesaleprc"],
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
            // $arr_record['stkqty'] 	= $_POST['itmqty_edit'] * -1;
            $arr_record['unmcde']   = isset($_POST['unmcde_edit']) ? $_POST['unmcde_edit'] : '';
            $arr_record['untprc'] 	= $_POST['price_edit'];
            $arr_record['extprc'] 	= $_POST['amount_edit'];
            $arr_record['wholesaleprc'] 	= $_POST['wholesaleprc_edit'];

            //BOM FOR EDIT START DELETE FIRST
            //START OF DELETE EDIT
            //DELETE FIRST
            $is_edit_bom = false;

            // $select_searchbom="SELECT * FROM salesorderfile2 WHERE recid=? LIMIT 1";
            // $stmt_searchbom	= $link->prepare($select_searchbom);
            // $stmt_searchbom->execute(array($_POST['recid']));
            // $rs_searchbom = $stmt_searchbom->fetch();
    
            // $select_itmsearch="SELECT * FROM itemfile WHERE itmcde='".$rs_searchbom['itmcde']."' LIMIT 1";
            // $stmt_itmsearch	= $link->prepare($select_itmsearch);
            // $stmt_itmsearch->execute();
            // $rs_itmsearch = $stmt_itmsearch->fetch();            

            // if($rs_itmsearch['is_bom'] == '1'){

            //     $select_itembomsearch="SELECT * FROM itembomfile WHERE itmcde='".$rs_itmsearch['itmcde']."' LIMIT 1";
            //     $stmt_itembomsearch	= $link->prepare($select_itembomsearch);
            //     $stmt_itembomsearch->execute();
    
            //     $bom_docnum_del =$rs_searchbom['docnum'].'-BOM';
    
            //     while($rs_itembomsearch = $stmt_itembomsearch->fetch()){
    
            //         $delete_query_bom="DELETE FROM salesorderfile2 WHERE docnum='".$bom_docnum_del."' AND bom_item='".$rs_itembomsearch['itmcde']."' AND trncde='SOR' AND bom_linenum='".$rs_searchbom['itmqty']."'";
            //         $xstmt_bom=$link->prepare($delete_query_bom);
            //         $xstmt_bom->execute();
            //     };

            //     $select_chk_del="SELECT * FROM salesorderfile2 WHERE docnum='".$bom_docnum_del."'";
            //     $stmt_chk_del	= $link->prepare($select_chk_del);
            //     $stmt_chk_del->execute();
            //     $rs_chk_del = $stmt_chk_del->fetchAll();
        
            //     if(count($rs_chk_del) <= 0){
        
            //         $select_chk_del2="DELETE FROM salesorderfile1 WHERE docnum='".$bom_docnum_del."'";
            //         $stmt_chk_del2	= $link->prepare($select_chk_del2);
            //         $result = $stmt_chk_del2->execute();
            //         if (!$result) {
            //             print_r($stmt_chk_del2->errorInfo());
            //         }
                    
            //     }  
                
            //     $is_edit_bom = true;
                
            // }     
            
            //NOW WE ADD THE `UPDATED` DATA
            //WE START WITH TRANFILE1


            $docnum_bom = $_POST['docnum'].'-BOM';

            // $select_check_chk_bom="SELECT * FROM salesorderfile1 WHERE docnum='".$docnum_bom."' LIMIT 1";
            // $stmt_check_chk_bom	= $link->prepare($select_check_chk_bom);
            // $stmt_check_chk_bom->execute();
            // $rs_check_chk_bom = $stmt_check_chk_bom->fetch();

            // if(empty($rs_check_chk_bom)){      

            //     $select_itmsearch2="SELECT * FROM itemfile WHERE itmcde='".$_POST['itmcde_edit_hidden']."' LIMIT 1";
            //     $stmt_itmsearch2	= $link->prepare($select_itmsearch2);
            //     $stmt_itmsearch2->execute();
            //     $rs_itmsearch2 = $stmt_itmsearch2->fetch();     

            //     if($rs_itmsearch2['is_bom'] == '1'){

            //         $_POST['xtrndte_1']  = (empty($_POST['xtrndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['xtrndte_1']));


            //         $arr_record_add_bom = array();
            //         $arr_record_add_bom['docnum'] = $docnum_bom;
            //         $arr_record_add_bom['trncde'] = 'SOR';
            //         $arr_record_add_bom['trndte'] = $_POST['xtrndte_1'];
            //         $arr_record_add_bom['cuscde'] 	= $_POST['xcusname_1'];
            //         $arr_record_add_bom['file_created_date'] = $date_time_today;
            //         PDO_InsertRecord($link,'salesorderfile1',$arr_record_add_bom, false);
            //     }
            
            // }     
            
            //THEN INSERT TRANFILE2
            // $select_check_is_bom="SELECT * FROM itemfile WHERE itmcde='".$_POST['itmcde_edit_hidden']."' LIMIT 1";
            // $stmt_check_is_bom	= $link->prepare($select_check_is_bom);
            // $stmt_check_is_bom->execute();
            // $rs_check_is_bom = $stmt_check_is_bom->fetch();

            // if($rs_check_is_bom['is_bom'] == '1'){

            //     $itembom = $_POST['itmcde_edit_hidden'];
                
            //     $select_itembom="SELECT * FROM itembomfile WHERE itmcde='".$itembom."'";
            //     $stmt_itembom	= $link->prepare($select_itembom);
            //     $stmt_itembom->execute();
            //     while($rs_itembom = $stmt_itembom->fetch()){
            //         $arr_record_bom = array();

            //         $arr_record_bom['docnum'] 	= trim($_POST['docnum']).'-BOM';
            //         $arr_record_bom['itmqty'] 	= $_POST['itmqty_edit'] * $rs_itembom['itmqty'];
            //         $arr_record_bom['itmcde'] 	= $rs_itembom['itmcde2'];
            //         $arr_record_bom['bom_item'] 	= $itembom;
            //         $arr_record_bom['bom_linenum'] 	= $_POST['itmqty_edit'];
            //         $arr_record_bom['trncde']     = 'SOR';
            //         PDO_InsertRecord($link,'salesorderfile2',$arr_record_bom, false);
            //     };
	            // }                 
	    
	            $select_log_old = "SELECT * FROM salesorderfile2 WHERE recid = ? LIMIT 1";
	            $stmt_log_old = $link->prepare($select_log_old);
	            $stmt_log_old->execute(array($_POST['recid']));
	            $log_old_record = $stmt_log_old->fetch();

	            PDO_UpdateRecord($link,"salesorderfile2",$arr_record,"recid = ?",array($_POST['recid']),false);

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
	            $log_old_item_desc = salesorder_get_item_desc($link, $log_old_item_code);
	            $log_new_item_desc = salesorder_get_item_desc($link, $log_new_item_code);
	            $log_change_parts = array();

	            if((float)(isset($log_old_record['itmqty']) ? $log_old_record['itmqty'] : 0) !== (float)$_POST['itmqty_edit']){
	                $log_change_parts[] = "qty from '" . $log_format_number(isset($log_old_record['itmqty']) ? $log_old_record['itmqty'] : '') . "' to '" . $log_format_number($_POST['itmqty_edit']) . "'";
	            }

	            if(trim((string)(isset($log_old_record['unmcde']) ? $log_old_record['unmcde'] : '')) !== trim((string)$_POST['unmcde_edit'])){
	                $log_change_parts[] = "uom from '" . salesorder_get_uom_desc($link, isset($log_old_record['unmcde']) ? $log_old_record['unmcde'] : '') . "' to '" . salesorder_get_uom_desc($link, isset($_POST['unmcde_edit']) ? $_POST['unmcde_edit'] : '') . "'";
	            }

	            if($log_format_number(isset($log_old_record['untprc']) ? $log_old_record['untprc'] : '') !== $log_format_number($_POST['price_edit'])){
	                $log_change_parts[] = "price per unit from '" . $log_format_number(isset($log_old_record['untprc']) ? $log_old_record['untprc'] : '') . "' to '" . $log_format_number($_POST['price_edit']) . "'";
	            }

	            if($log_old_item_code !== $log_new_item_code){
	                $log_remarks = $log_username . " edited item from '" . $log_old_item_desc . "' to '" . $log_new_item_desc . "' in docnum='" . $_POST['docnum'] . "'";
	            }else{
	                $log_remarks = $log_username . " edited item '" . $log_new_item_desc . "' in docnum='" . $_POST['docnum'] . "'";
	            }

	            if(!empty($log_change_parts)){
	                $log_remarks .= ": " . implode(', ', $log_change_parts);
	            }
	            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'edit', $log_fullname, $log_remarks, 0, '', 'SOR', '', '', $log_username, $_POST['docnum'], '');

            $xret["msg"] = "submitEdit";
        }



    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        //DELETING BOM ALSO
        // $select_searchbom="SELECT * FROM salesorderfile2 WHERE recid=? LIMIT 1";
        // $stmt_searchbom	= $link->prepare($select_searchbom);
        // $stmt_searchbom->execute(array($_POST['recid']));
        // $rs_searchbom = $stmt_searchbom->fetch();

        // $select_itmsearch="SELECT * FROM itemfile WHERE itmcde='".$rs_searchbom['itmcde']."' LIMIT 1";
        // $stmt_itmsearch	= $link->prepare($select_itmsearch);
        // $stmt_itmsearch->execute();
        // $rs_itmsearch = $stmt_itmsearch->fetch();

        // $bom_docnum_del = $rs_searchbom['docnum'].'-BOM';

        // if($rs_itmsearch['is_bom'] == '1'){

        //     $select_itembomsearch="SELECT * FROM itembomfile WHERE itmcde='".$rs_itmsearch['itmcde']."' LIMIT 1";
        //     $stmt_itembomsearch	= $link->prepare($select_itembomsearch);
        //     $stmt_itembomsearch->execute();

        //     while($rs_itembomsearch = $stmt_itembomsearch->fetch()){

        //         $delete_query_bom="DELETE FROM salesorderfile2 WHERE docnum='".$bom_docnum_del."' AND bom_item='".$rs_itembomsearch['itmcde']."' AND trncde='SOR' AND bom_linenum='".$rs_searchbom['itmqty']."'";
        //         $xstmt_bom=$link->prepare($delete_query_bom);
        //         $xstmt_bom->execute();
        //     };

        // }  

        // delete
        $delete_id=$_POST['recid'];

        // Log activity: delete line item (capture item details before delete)
        $select_del_record = "SELECT itmcde, docnum FROM salesorderfile2 WHERE recid = ? LIMIT 1";
        $stmt_del_record = $link->prepare($select_del_record);
        $stmt_del_record->execute(array($delete_id));
        $rs_del_record = $stmt_del_record->fetch();
        if($rs_del_record){
            $log_itmdsc_del = salesorder_get_item_desc($link, $rs_del_record['itmcde']);
            $log_docnum_del = $rs_del_record['docnum'];
            $log_remarks = $log_username . " deleted item '" . $log_itmdsc_del . "' in docnum='" . $log_docnum_del . "'";
            PDO_UserActivityLog($link, $log_username, '', $log_trndte, $log_module, 'delete', $log_fullname, $log_remarks, 0, '', 'SOR', '', '', $log_username, $log_docnum_del, '');
        }

        $delete_query="DELETE  FROM  salesorderfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));

        // $select_chk_del="SELECT * FROM salesorderfile2 WHERE docnum='".$bom_docnum_del."'";
        // $stmt_chk_del	= $link->prepare($select_chk_del);
        // $stmt_chk_del->execute();
        // $rs_chk_del = $stmt_chk_del->fetchAll();

        // if(count($rs_chk_del) <= 0){

        //     $select_chk_del2="DELETE  FROM  salesorderfile1 WHERE docnum='".$bom_docnum_del."'";
        //     $stmt_chk_del2	= $link->prepare($select_chk_del2);
        //     $stmt_chk_del2->execute();
        // }
            
        
    }


            $xret["html"] .= "<tr style='font-weight:bold'>";
                $xret["html"] .= "<td>Item</td>";
                $xret["html"] .= "<td style='text-align:right'>Quantity</td>";
                $xret["html"] .= "<td>UOM</td>";
                $xret["html"] .= "<td style='text-align:right'>Price</td>";
                $xret["html"] .= "<td style='text-align:right'>Amount</td>";
                $xret["html"] .= "<td style='text-align:right'>Wholesale Price</td>";
                $xret["html"] .= "<td class='text-center'>Action</td>";
            $xret["html"] .= "</tr>";  

            $select_salesfile2="SELECT salesorderfile2.wholesaleprc as wholesaleprc, salesorderfile2.docnum as salesorderfile2_docnum, itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, salesorderfile2.itmqty, salesorderfile2.untprc, salesorderfile2.extprc, salesorderfile2.recid as salesorderfile2_recid, itemunitmeasurefile.unmdsc
            FROM salesorderfile2 LEFT JOIN itemfile ON itemfile.itmcde = salesorderfile2.itmcde
            LEFT JOIN itemunitmeasurefile ON itemunitmeasurefile.unmcde = salesorderfile2.unmcde
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

                $select_chkmatch="SELECT * FROM tranfile2 WHERE so_recid='".$rs_salesfile2['salesorderfile2_recid']."'";
                $stmt_chkmatch	= $link->prepare($select_chkmatch);
                $stmt_chkmatch->execute(array($docnum));
                $rs_chkmatch = $stmt_chkmatch->fetch();


                $action_disabled = '';
                if(!empty($rs_chkmatch)){
                $action_disabled = 'disabled';
                }


                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>".htmlspecialchars($rs_salesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['itmqty']."</td>";
                    $xret["html"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['unmdsc']) ? $rs_salesfile2['unmdsc'] : '',ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['wholesaleprc']."</td>";
                    $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";

                            $xret["html"].= "<div class='dropdown'>";

                            if($action_disabled == ''){
                                $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_salesfile2['salesorderfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                                    $xret["html"].= "Action";
                                $xret["html"].= "</button>";
                        
                            }else{

                                $xret["matched_checked"] = true;

                                $xret["html"].= "<button  
                                    onclick=\"matched_alert('".$action_disabled."','".$rs_chkmatch['docnum']."')\"
                                    class='btn btn-primary fw-bold' 
                                    type='button' 
                                    id='dropdownMenuButton1-".$rs_salesfile2['salesorderfile2_recid']."'
                                    style='pointer-events:auto;opacity:0.5'>";
                                    $xret["html"].= "Action";
                                $xret["html"].= "</button>";
                            }

        
                            $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_salesfile2['salesorderfile2_recid']."'>";
                                $xret["html"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_salesfile2['salesorderfile2_recid']."')\">";
                                    $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                                $xret["html"].= "</li>";
            
                                $xret["html"].= "<li onclick=\"salesfile2('delete' , '".$rs_salesfile2['salesorderfile2_recid']."')\">";
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
                    $xret["html_mobile"] .= "<td class='fw-bold'>Quantity</td>";
                    $xret["html_mobile"] .= "<td>".$rs_salesfile2['itmqty']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Price</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>UOM</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars(isset($rs_salesfile2['unmdsc']) ? $rs_salesfile2['unmdsc'] : '',ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Amount</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Wholesale Price:</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['wholesaleprc']."</td>";
                $xret["html_mobile"] .= "</tr>";

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"].= "<td  class='fw-bold' style='text-align:left' data-label='Action'>Action</td>";
                    $xret["html_mobile"].= "<td>";
                        $xret["html_mobile"].= "<div class='dropdown'>";

                            if($action_disabled == ''){
                                $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_salesfile2['salesorderfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                                    $xret["html_mobile"].= "Action";
                                $xret["html_mobile"].= "</button>";
                            }else{

                                $xret["matched_checked"] = true;
                                
                                $xret["html_mobile"].= "<button  
                                    onclick=\"matched_alert('".$action_disabled."','".$rs_chkmatch['docnum']."')\"
                                    class='btn btn-primary fw-bold' 
                                    type='button' 
                                    id='dropdownMenuButton1-".$rs_salesfile2['salesorderfile2_recid']."'
                                    style='pointer-events:auto;opacity:0.5'>";
                                    $xret["html_mobile"].= "Action";
                                $xret["html_mobile"].= "</button>";
                            }                        

                            $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_salesfile2['salesorderfile2_recid']."'>";
                                $xret["html_mobile"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_salesfile2['salesorderfile2_recid']."')\">";
                                    $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                                $xret["html_mobile"].= "</li>";
            
                                $xret["html_mobile"].= "<li onclick=\"salesfile2('delete' , '".$rs_salesfile2['salesorderfile2_recid']."')\">";
                                    $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                                $xret["html_mobile"].= "</li>";
                                
                            $xret["html_mobile"].= "</ul>";
                        $xret["html_mobile"].= "</div>";
                    $xret["html_mobile"].= "</td>";
                $xret["html_mobile"] .= "</tr>";
     
            }

    $select_trntot='SELECT * FROM salesorderfile1 WHERE docnum=?';
	$stmt_trntot	= $link->prepare($select_trntot);
	$stmt_trntot->execute(array($_POST["docnum"]));
    $rs_trntot = $stmt_trntot->fetch();

    $arr_record_salesfile1 = array();			
    $arr_record_salesfile1['trntot'] = $trntot;
    if(isset($_POST["event_action"]) && ($_POST["event_action"]  == "insert_new" || $_POST["event_action"]  == "insert_old" || $_POST["event_action"]  == "submitEdit" || $_POST["event_action"]  == "delete" || $_POST["event_action"]  == "insert")){
        PDO_UpdateRecord($link,"salesorderfile1",$arr_record_salesfile1,"recid = ?",array($rs_trntot["recid"]));  
    }

    
    $xret["trntot"] = number_format($trntot,2);

echo json_encode($xret);
?>
