<?php
    // ini_set('display_errors', '1');
    // ini_set('display_startup_errors', '1');
    // error_reporting(E_ALL);     

    session_start();
    
    require_once("resources/db_init.php");
    require "resources/connect4.php";
    require "resources/stdfunc100.php";
    require "resources/lx2.pdodb.php";

    $xret = array();
    $xret["html"] = "";
    $xret["html_mobile"] = "";
    $xret["trntot"] = "";
    $xret["msg"] = "";
    $xret["status"] = 1;
    $xret["error1"] = 0;
    $xret["error2"] = 0;
    $xret["error3"] = 0;
    $xret["error4"] = 0;
    $xret["itm_search"] = '';
    $trncde = 'SAL';


    $xret["retEdit"] = array();

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "select_itmprice" || $_POST["event_action"] == "change_itmprice")){

        $select_itemfile="SELECT * FROM itemfile ORDER BY itmdsc ASC LIMIT 1";
        $stmt_itemfile	= $link->prepare($select_itemfile);
        $stmt_itemfile->execute();
        $rs_itemfile = $stmt_itemfile->fetch();

        $xitmcde = $rs_itemfile['itmcde'];

        if($_POST["event_action"] == "change_itmprice"){
            $xitmcde = $_POST['xitmcde'];
        }

        $select_price="SELECT * FROM itemfile WHERE itmcde='".$xitmcde."'";
        $stmt_price	= $link->prepare($select_price);
        $stmt_price->execute();
        $rs_price = $stmt_price->fetch();
        $xret["retEdit"]['xprice'] = '';
        $xret["retEdit"]['xprice'] = $rs_price['untprc'];

        echo json_encode($xret);
        return;
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

                $select_itemfile2="SELECT * FROM itemfile WHERE itmcde='".$rs_itemfile['itmcde']."'";
                $stmt_itemfile2	= $link->prepare($select_itemfile2);
                $stmt_itemfile2->execute();
                $rs_itemfile2 = $stmt_itemfile2->fetch();


                date_default_timezone_set('Asia/Manila');
                $date_today = date('Y-m-d');

                $select_db2 = "SELECT SUM(stkqty) as xsum FROM tranfile2 
                    LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
                    WHERE tranfile2.itmcde='".$rs_itemfile2['itmcde']."' 
                    AND tranfile1.trndte<='".$date_today."'";
                $stmt_main2	= $link->prepare($select_db2);
                $stmt_main2->execute(array());
                $rs2 = $stmt_main2->fetch();

                $currentStock =  $rs2['xsum'];

                //$xret['wholesaleprc'] = $rs_itemfile2['wholesaleprc'];

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['itmdsc'];
                    $xret["html"] .= "</td>";

                    // $xret["html"] .= "<td>";
                    //     $xret["html"] .= $rs_itemfile['inventory_type'];
                    // $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='text-center'>";
                $xret["html"] .= "<button type='button' onclick='select_item_modal("
                . json_encode($rs_itemfile['itmcde']) . ", "
                . json_encode($rs_itemfile['itmdsc']) . ", "
                . json_encode($_POST['event_action_itmsearch']) . ", "
                . json_encode($rs_itemfile2['wholesaleprc']) . ", "
                . json_encode($currentStock) . ", "
                . json_encode($xret['itm_search'])
                . ")' class='btn btn-primary fw-bold select_item'>";

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



    if(!isset($_POST['trncde']) || empty($_POST["trncde"]) ){
       $_POST["trncde"] = '';
    }

    if(!isset($_POST['docnum']) || empty($_POST['docnum']) ){
        $_POST['docnum'] = '';
    }

    if(!isset($_POST['orderby_1']) || empty($_POST['orderby_1']) ){
        $_POST['orderby_1'] = NULL;
    }


    $waybill_num1 = '';
    if(isset($_POST['waybill_num1']) && !empty($_POST['waybill_num1'])){
        $waybill_num1 = $_POST['waybill_num1'];
    }

    if(isset($_POST['docnum_1'])){
        $docnum = $_POST["docnum_1"];
    }else{
        $docnum = $_POST["docnum"];
    }


    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "save_exit" || $_POST["event_action"] == "save_new")){

        $select_docnum='SELECT * FROM tranfile1 WHERE docnum=?';
        $stmt_docnum	= $link->prepare($select_docnum);
        $stmt_docnum->execute(array($docnum));
        $rs_docnum = $stmt_docnum->fetch();

        if(empty($rs_docnum)){

            $select_wb_check="SELECT * FROM tranfile1 WHERE waybill_number=?";
            $stmt_wb_check	= $link->prepare($select_wb_check);
            $stmt_wb_check->execute(array($waybill_num1));
            $rs_wb_check = $stmt_wb_check->fetch();

            if(empty($_POST['trndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Tran. Date</b> cannot be empty. </br>";
                $xret["error1"] = 1;
            }

            if(!empty($rs_wb_check) && !empty($waybill_num1)){
                $xret["status"] = 0;
                $xret["msg"].= "Waybill No. <b>".$rs_wb_check['waybill_number']."</b> already exist";
            }
            
            if($xret["status"] == 1){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record = array();
                $arr_record['docnum'] 	= $docnum;
                $arr_record['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record['salesman_id'] 	= $_POST['sel_salesman_id'];
                $arr_record['shipto'] 	= $_POST['shipto_1'];
                $arr_record['cuscde'] 	= $_POST['cusname_1'];
                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['trndte'] 	= $_POST['trndte_1'];
                $arr_record['paydate'] 	= $_POST['paydate_1'];
                $arr_record['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record['remarks'] 	= $_POST['remarks_1'];
                //$arr_record['waybill_number'] 	= $waybill_num1;
                //$arr_record['order_status'] 	= $_POST['order_status_select1'];
                $arr_record['trncde'] 	= $trncde;
    
                PDO_InsertRecord($link,'tranfile1',$arr_record, false);
    
                if($_POST["event_action"] == "save_exit"){
                    $xret["msg"] = "save_exit";
                }
            }

        }else{

            $select_wb_check="SELECT * FROM tranfile1 WHERE waybill_number=? AND recid!=?";
            $stmt_wb_check	= $link->prepare($select_wb_check);
            $stmt_wb_check->execute(array($waybill_num1,$rs_docnum['recid']));
            $rs_wb_check = $stmt_wb_check->fetch();

            if(empty($_POST['trndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Tran. Date</b> cannot be empty. </br>";
                $xret["error1"] = 1;
            }

            if(!empty($rs_wb_check) && !empty($waybill_num1)){
                $xret["status"] = 0;
                $xret["msg"].= "Waybill No. <b>".$rs_wb_check['waybill_number']."</b> already exist";
            }
            
            if($xret["status"] == 1){


                $xret["msg"] = "edit_exit";

                $recid = $rs_docnum['recid'];
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_update = array();			
                $arr_record_update['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record_update['salesman_id'] 	= $_POST['sel_salesman_id'];
                $arr_record_update['shipto'] 	= $_POST['shipto_1'];
                $arr_record_update['cuscde'] 	= $_POST['cusname_1'];
                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['trndte'] 	= $_POST['trndte_1'];
                $arr_record_update['paydate'] 	= $_POST['paydate_1'];
                $arr_record_update['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record_update['remarks'] 	= $_POST['remarks_1'];
                $arr_record_update['ordernum'] 	= $_POST['ordernum_1'];
                //$arr_record_update['waybill_number'] 	= $waybill_num1;
                //$arr_record_update['order_status'] 	= $_POST['order_status_select1'];
                PDO_UpdateRecord($link,"tranfile1",$arr_record_update,"recid = ?",array($recid),false);


                $docnum_bom_edit = $docnum."-BOM";
                $select_db_upd_bomdate="SELECT * FROM tranfile1 WHERE docnum='".$docnum_bom_edit."'";
                $stmt_upd_bomdate	= $link->prepare($select_db_upd_bomdate);
                $stmt_upd_bomdate->execute();
                while($rs_upd_bomdate = $stmt_upd_bomdate->fetch()){

                    $arr_record_update_bomdate = array();	
                    $arr_record_update_bomdate['trndte'] = $_POST['trndte_1'];
                    PDO_UpdateRecord($link,"tranfile1",$arr_record_update_bomdate,"recid = ?",array($rs_upd_bomdate['recid']),false);
                }       

                $select_update_ord2="SELECT * FROM tranfile2 WHERE docnum=?";
                $stmt_update_ord2	= $link->prepare($select_update_ord2);
                $stmt_update_ord2->execute(array($rs_docnum['docnum']));
                while($rs_update_ord2 = $stmt_update_ord2->fetch()){

                    $arr_record_update2 = array();			
                    //$arr_record_update2['order_status'] 	= $_POST['order_status_select1'];
                    PDO_UpdateRecord($link,"tranfile2",$arr_record_update2,"recid = ?",array($rs_update_ord2['recid']),false);
                }
    
                
                // if(isset($_POST['so_edit']) && !empty($_POST['so_edit'])){

                //     $select_update_getsorecid="SELECT * FROM salesorderfile1 WHERE docnum=? LIMIT 1";
                //     $stmt_update_getsorecid	= $link->prepare($select_update_getsorecid);
                //     $stmt_update_getsorecid->execute(array($_POST['so_edit']));
                //     $rs_update_getsorecid = $stmt_update_getsorecid->fetch();
                    
                //     $arr_updsor = array();
                //     $arr_updsor['salnum'] = $_POST["docnum"];    
                //     PDO_UpdateRecord($link,'salesorderfile1',$arr_updsor,"recid = ?",array($rs_update_getsorecid['recid']),false);  
                // }
                
            }

   
        }
        
        if($_POST["event_action"] == "save_new" && $xret["status"] == 1){

            $select_db_docnum="SELECT * FROM tranfile1 WHERE trncde=? AND docnum NOT LIKE '%BOM%' ORDER BY docnum DESC LIMIT 1";
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

        $select_check="SELECT * FROM tranfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();

        $select_wb_check="SELECT * FROM tranfile1 WHERE waybill_number=?";
        $stmt_wb_check	= $link->prepare($select_wb_check);
        $stmt_wb_check->execute(array($waybill_num1));
        $rs_wb_check = $stmt_wb_check->fetch();

        if(empty($_POST['trndte_1'])){
            $xret["status"] = 0;
            $xret["msg"] = "<b>Tran. Date</b> cannot be empty";
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

        if(!empty($rs_wb_check) && !empty($waybill_num1) && ($rs_check['waybill_number'] != $waybill_num1)){

            if($xret["error1"] == 1 || $xret["error2"] == 1){
                $xret["msg"] .= "</br>";      
            } 
            $xret["status"] = 0;
            $xret["msg"].= "Waybill No. <b>".$rs_wb_check['waybill_number']."</b> already exist";
            $xret["error3"] = 0;
        }

        if(!isset($_POST['itmqty_add']) || empty($_POST['itmqty_add']) || $_POST['itmqty_add'] == '0'){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["msg"] .= "<b>Quantity</b> Cannot Be Empty or 0"; 
           
            $xret["status"] = 0;
            $xret["error4"] = 1;

        }         
        
        if($xret["status"] == 1){
            
            if(empty($rs_check)){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_file1 = array();
                $arr_record_file1['docnum'] 	= $_POST["docnum"];
                $arr_record_file1['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record_file1['salesman_id'] 	= $_POST['sel_salesman_id'];
                $arr_record_file1['shipto'] 	= $_POST['shipto_1'];
                $arr_record_file1['cuscde'] 	= $_POST['cusname_1'];
                $arr_record_file1['trntot'] 	= $_POST['trntot_1'];
                $arr_record_file1['trndte'] 	= $_POST['trndte_1'];
                $arr_record_file1['paydate'] 	= $_POST['paydate_1'];
                $arr_record_file1['paydetails'] = $_POST['payment_details_1'];
                $arr_record_file1['remarks'] 	= $_POST['remarks_1'];
                $arr_record_file1['ordernum'] 	= $_POST['ordernum_1'];
                //$arr_record_file1['waybill_number'] 	= $waybill_num1;
                //$arr_record_file1['order_status'] 	= $_POST['order_status_select1'];
                $arr_record_file1['trncde']     = $trncde;
                PDO_InsertRecord($link,'tranfile1',$arr_record_file1, false);
    
                $xret["msg"] = "insert_new";
            }else{
                $xret["msg"] = "insert_old";
            }
    
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            $arr_record['stkqty'] 	= (int)$_POST['itmqty_add'] * -1;
            $arr_record['untprc'] 	= $_POST['price_add'];
            $arr_record['extprc'] 	= $_POST['amount_add'];
            $arr_record['wholesaleprc'] 	= $_POST['wholesaleprc_add'];
            //$arr_record['order_status'] 	= $_POST['order_status_select1'];
            $arr_record['trncde']     = $trncde;
            $arr_record['so_recid']     = $_POST['xrecid_so_hidden'];

            if(isset($_POST['xrecid_so_hidden']) && !empty($_POST['xrecid_so_hidden'])){

                // $select_order_status="SELECT * FROM salesorderfile2 WHERE recid=? LIMIT 1";
                // $stmt_order_status	= $link->prepare($select_order_status);
                // $stmt_order_status->execute(array($_POST['xrecid_so_hidden']));
                // $rs_order_status = $stmt_order_status->fetch();

                // $select_order_status2="SELECT * FROM salesorderfile1 WHERE docnum=?";
                // $stmt_order_status2	= $link->prepare($select_order_status2);
                // $stmt_order_status2->execute(array($rs_order_status['docnum']));
                // $rs_order_status2 = $stmt_order_status2->fetch(); 

                // $sql1 = "UPDATE salesorderfile1 SET order_status='completed' WHERE recid='".$rs_order_status2['recid']."'";
                // $stmt_upd1 = $link->prepare($sql1);
                // $stmt_upd1->execute();
             
            }

            PDO_InsertRecord($link,'tranfile2',$arr_record, false);
  
            //ADDING BOM
            // $select_check_is_bom="SELECT * FROM itemfile WHERE itmcde='".$_POST['itmcde_add_hidden']."' LIMIT 1";
            // $stmt_check_is_bom	= $link->prepare($select_check_is_bom);
            // $stmt_check_is_bom->execute();
            // $rs_check_is_bom = $stmt_check_is_bom->fetch();

            // if($rs_check_is_bom['is_bom'] == '1'){

            //     $docnum_bom = $_POST['docnum'].'-BOM';

            //     $select_check_chk_bom="SELECT * FROM tranfile1 WHERE docnum='".$docnum_bom."' LIMIT 1";
            //     $stmt_check_chk_bom	= $link->prepare($select_check_chk_bom);
            //     $stmt_check_chk_bom->execute();
            //     $rs_check_chk_bom = $stmt_check_chk_bom->fetch();
    
            //     if(empty($rs_check_chk_bom)){      
                    
            //         $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
    
            //         $arr_record_add_bom = array();
            //         $arr_record_add_bom['docnum'] = $docnum_bom;
            //         $arr_record_add_bom['trncde'] = $trncde;
            //         $arr_record_add_bom['trndte'] = $_POST['trndte_1'];
            //         $arr_record_add_bom['cuscde'] 	= $_POST['cusname_1'];
            //         //$arr_record_add_bom['order_status'] = $_POST['order_status_select1'];
            //         PDO_InsertRecord($link,'tranfile1',$arr_record_add_bom, false);
            //     }                

            //     $itembom 	= $_POST['itmcde_add_hidden'];
                
            //     $select_itembom="SELECT * FROM itembomfile WHERE itmcde='".$itembom."'";
            //     $stmt_itembom	= $link->prepare($select_itembom);
            //     $stmt_itembom->execute();
            //     while($rs_itembom = $stmt_itembom->fetch()){
            //         $arr_record_bom = array();

            //         $arr_record_bom['docnum'] 	= trim($_POST['docnum']).'-BOM';
            //         $arr_record_bom['itmqty'] 	= $_POST['itmqty_add'] * $rs_itembom['itmqty'];
            //         $arr_record_bom['stkqty'] 	= ($_POST['itmqty_add'] * $rs_itembom['itmqty']) * -1;
            //         $arr_record_bom['itmcde'] 	= $rs_itembom['itmcde2'];
            //         $arr_record_bom['bom_item'] 	= $itembom;
            //         $arr_record_bom['bom_linenum'] 	= $_POST['itmqty_add'];
            //         $arr_record_bom['trncde']     = $trncde;
            //         //$arr_record_bom['order_status'] = $_POST['order_status_select1'];
            //         PDO_InsertRecord($link,'tranfile2',$arr_record_bom, false);
            //     };
         
            // }

            // //ADDING THE BOM MATCHING(updating so_recid)
            // if(isset($_POST['so_add']) && !empty($_POST['so_add'])){

            //     $search_bom_match = $_POST['docnum'].'-BOM';
            //     $so_bom_match = $_POST['so_add'].'-BOM';

            //     $select_bomitem2 = "SELECT * FROM salesorderfile2 WHERE docnum=? AND bom_item=?";
            //     $stmt_bomitem2	= $link->prepare($select_bomitem2);
            //     $stmt_bomitem2->execute(array($so_bom_match,$_POST['itmcde_add_hidden']));
            //     while($rs_bomitem2 = $stmt_bomitem2->fetch()){

            //         $select_bomitem="SELECT * FROM tranfile2 WHERE docnum=? AND itmcde=?";
            //         $stmt_bomitem	= $link->prepare($select_bomitem);
            //         $stmt_bomitem->execute(array($search_bom_match,$rs_bomitem2['itmcde']));
            //         while($rs_bomitem = $stmt_bomitem->fetch()){
            //             $arr_record_bom_match = array();
            //             $arr_record_bom_match['so_recid'] 	= $rs_bomitem2['recid'];
            //             PDO_UpdateRecord($link,"tranfile2",$arr_record_bom_match,"recid = ?",array($rs_bomitem['recid']),false);
            //         }
            //     }

            //     //ADD IT WHEN UNMATCH SET order_status to 'pending' OF salesorderfile1
            //     //setting order_status to 'completed'
            //     $select_check_os_check2="SELECT * FROM salesorderfile2 WHERE docnum='".$so_bom_match."' OR docnum='".$_POST['so_add']."'";
            //     $stmt_check_os_check2	= $link->prepare($select_check_os_check2);
            //     $stmt_check_os_check2->execute();
            //     $all_matched = true;
            //     while($rs_check_os_check2 = $stmt_check_os_check2->fetch()){

            //         $select_check_os_check="SELECT * FROM tranfile2 WHERE so_recid='".$rs_check_os_check2['recid']."'";
            //         $stmt_check_os_check	= $link->prepare($select_check_os_check);
            //         $stmt_check_os_check->execute();
            //         $rs_check_os_check = $stmt_check_os_check->fetch();

            //         if(empty($rs_check_os_check)){
            //             $all_matched = false;
            //             break;
            //         }
            //     }        

            //     if($all_matched == true){
            //         $arr_record_so_match = array();
            //         $arr_record_so_match['order_status'] 	= 'completed';
            //         PDO_UpdateRecord($link,"salesorderfile1",$arr_record_so_match,"docnum = ?",array($_POST['so_add']),false);

            //         $bom_check = $_POST['so_add'].'-BOM';

            //         //THIS IS ALSO TO UPDATE THE BOM ITEM
            //         $select_check_upd_os="SELECT * FROM salesorderfile1 WHERE docnum='".$bom_check."'";
            //         $stmt_check_upd_os	= $link->prepare($select_check_upd_os);
            //         $stmt_check_upd_os->execute();
            //         $rs_check_upd_os = $stmt_check_upd_os->fetch();

            //         $arr_record_so_match2 = array();
            //         $arr_record_so_match2['order_status'] 	= 'completed';
            //         PDO_UpdateRecord($link,"salesorderfile1",$arr_record_so_match2,"recid = ?",array($rs_check_upd_os['recid']),false);


            //     }

            // }
        }
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_tranfile2="SELECT tranfile2.wholesaleprc as wholesaleprc, tranfile2.itmcde as 'itmcde', tranfile2.so_recid as 'tranfile2_so_recid', tranfile2.order_status as tranfile2_order_status, tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, itemfile.itmdsc, tranfile2.recid as tranfile2_recid FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE tranfile2.recid=?";
        $stmt_tranfile2	= $link->prepare($select_tranfile2);
        $stmt_tranfile2->execute(array($_POST["recid"]));
        $rs_tranfile2 = $stmt_tranfile2->fetch();

        if(!empty($rs_tranfile2["untprc"])){
            $rs_tranfile2["untprc"] = number_format($rs_tranfile2["untprc"],2);
        }
        if($rs_tranfile2["extprc"]){
            $rs_tranfile2["extprc"] = number_format($rs_tranfile2["extprc"],2);
        }

        if(!empty($rs_tranfile2['tranfile2_so_recid'])){
            $select_chkpo="SELECT * FROM salesorderfile2 WHERE recid='".$rs_tranfile2['tranfile2_so_recid']."'";
            $stmt_chkpo	= $link->prepare($select_chkpo);
            $stmt_chkpo->execute();
            $rs_chkpo = $stmt_chkpo->fetch();
            $matched_so = $rs_chkpo['docnum'];
        }else{
            $matched_so = '';
        }        

        $xret["retEdit"] = [
            "itmcde" =>  $rs_tranfile2["itmcde"],
            "itmdsc" =>  $rs_tranfile2["itmdsc"],
            "itmqty" =>  $rs_tranfile2["itmqty"],
            "untprc" =>  $rs_tranfile2["untprc"],
            "extprc" =>  $rs_tranfile2["extprc"],
            "order_status" =>  $rs_tranfile2["tranfile2_order_status"],
            "wholesaleprc" =>  $rs_tranfile2["wholesaleprc"],
            "so_recid" =>  $rs_tranfile2["tranfile2_so_recid"],
            "matched_so" =>  $matched_so,
            "recid" =>  $rs_tranfile2["tranfile2_recid"]
        ];

        $xret["msg"] = "retEdit";
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "submitEdit"){

        $select_check="SELECT * FROM tranfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();

        $select_wb_check="SELECT * FROM tranfile1 WHERE waybill_number=?";
        $stmt_wb_check	= $link->prepare($select_wb_check);
        $stmt_wb_check->execute(array($waybill_num1));
        $rs_wb_check = $stmt_wb_check->fetch();

        if(!empty($_POST['price_edit'])){
            $_POST['price_edit'] = str_replace(",","",$_POST['price_edit']);
        }
        if(!empty($_POST['amount_edit'])){
            $_POST['amount_edit'] = str_replace(",","",$_POST['amount_edit']);
        }
        if(!empty($_POST['wholesaleprc_edit'])){
            $_POST['wholesaleprc_edit'] = str_replace(",","",$_POST['wholesaleprc_edit']);
        }

        if(empty($_POST['xtrndte_1'])){
            $xret["status"] = 0;
            $xret["error1"] = 1;
            $xret["msg"] = "<b>Upload Date</b> cannot be empty.";
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

        if(!empty($rs_wb_check) && !empty($waybill_num1) && ($rs_check['waybill_number'] != $waybill_num1)){

            if($xret["error1"] == 1 || $xret["error2"] == 1){
                $xret["msg"] .= "</br>";      
            } 
            
            $xret["status"] = 0;
            $xret["msg"].= "Waybill No. <b>".$rs_wb_check['waybill_number']."</b> already exist";
            $xret["error3"] = 0;
        }        

        if(!isset($_POST['itmqty_edit']) || empty($_POST['itmqty_edit']) || $_POST['itmqty_edit'] == '0'){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["msg"] .= "<b>Quantity</b> Cannot Be Empty or 0"; 
           
            $xret["status"] = 0;
            $xret["error4"] = 1;

        }         

        if($xret["status"] == 1){
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_edit_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_edit'];
            $arr_record['stkqty'] 	= $_POST['itmqty_edit'] * -1;
            $arr_record['untprc'] 	= $_POST['price_edit'];
            $arr_record['extprc'] 	= $_POST['amount_edit'];
            $arr_record['wholesaleprc'] 	= $_POST['wholesaleprc_edit'];
            //$arr_record['order_status'] 	= $_POST['order_status_select1'];


            $arr_record['so_recid']     = $_POST['xrecid_so_hidden'];

            //START OF DELETE EDIT
            //DELETE FIRST
            // $is_edit_bom = false;

            // $select_searchbom="SELECT * FROM tranfile2 WHERE recid=? LIMIT 1";
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
    
            //         $delete_query_bom="DELETE FROM tranfile2 WHERE docnum='".$bom_docnum_del."' AND bom_item='".$rs_itembomsearch['itmcde']."' AND trncde='SAL' AND bom_linenum='".$rs_searchbom['itmqty']."'";
            //         $xstmt_bom=$link->prepare($delete_query_bom);
            //         $xstmt_bom->execute();
            //     };

            //     $select_chk_del="SELECT * FROM tranfile2 WHERE docnum='".$bom_docnum_del."'";
            //     $stmt_chk_del	= $link->prepare($select_chk_del);
            //     $stmt_chk_del->execute();
            //     $rs_chk_del = $stmt_chk_del->fetchAll();
        
            //     if(count($rs_chk_del) <= 0){
        
            //         $select_chk_del2="DELETE FROM tranfile1 WHERE docnum='".$bom_docnum_del."'";
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
            // $docnum_bom = $_POST['docnum'].'-BOM';

            // $select_check_chk_bom="SELECT * FROM tranfile1 WHERE docnum='".$docnum_bom."' LIMIT 1";
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
            //         $arr_record_add_bom['trncde'] = $trncde;
            //         $arr_record_add_bom['trndte'] = $_POST['xtrndte_1'];
            //         $arr_record_add_bom['cuscde'] 	= $_POST['cusname_1'];
            //         //$arr_record_add_bom['order_status'] 	= $_POST['order_status_select1'];
            //         PDO_InsertRecord($link,'tranfile1',$arr_record_add_bom, false);
            //     }
            
            // }    

            //THEN INSERT TRANFILE2
            // $select_check_is_bom="SELECT * FROM itemfile WHERE itmcde='".$_POST['itmcde_edit_hidden']."' LIMIT 1";
            // $stmt_check_is_bom	= $link->prepare($select_check_is_bom);
            // $stmt_check_is_bom->execute();
            // $rs_check_is_bom = $stmt_check_is_bom->fetch();

            // if($rs_check_is_bom['is_bom'] == '1'){

            //     $itembom 	= $_POST['itmcde_edit_hidden'];
                
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
            //         $arr_record_bom['trncde']     = $trncde;
            //         $arr_record_bom['stkqty'] 	= ($_POST['itmqty_edit'] * $rs_itembom['itmqty']) * -1;
            //         //$arr_record_bom['order_status'] 	= $_POST['order_status_select1'];
            //         PDO_InsertRecord($link,'tranfile2',$arr_record_bom, false);
            //     };
            // }         
                        
            //UPDATE THE ACTUAL RECORD
            PDO_UpdateRecord($link,"tranfile2",$arr_record,"recid = ?",array($_POST['recid']),false);   
            $xret["msg"] = "submitEdit";
        }

        //MATCHING FOR WITH BOM
        if(isset($_POST['so_edit']) && !empty($_POST['so_edit'])){

            $search_bom_match = $_POST['docnum'].'-BOM';
            $so_bom_match = $_POST['so_edit'].'-BOM';

            $select_bomitem2 = "SELECT * FROM salesorderfile2 WHERE docnum=? AND bom_item=?";
            $stmt_bomitem2	= $link->prepare($select_bomitem2);
            $stmt_bomitem2->execute(array($so_bom_match,$_POST['itmcde_edit_hidden']));
            while($rs_bomitem2 = $stmt_bomitem2->fetch()){

                $select_bomitem="SELECT * FROM tranfile2 WHERE docnum=? AND itmcde=?";
                $stmt_bomitem	= $link->prepare($select_bomitem);
                $stmt_bomitem->execute(array($search_bom_match,$rs_bomitem2['itmcde']));
                while($rs_bomitem = $stmt_bomitem->fetch()){
                    $arr_record_bom_match = array();
                    $arr_record_bom_match['so_recid'] 	= $rs_bomitem2['recid'];
                    PDO_UpdateRecord($link,"tranfile2",$arr_record_bom_match,"recid = ?",array($rs_bomitem['recid']),false);
                }
            }
            
        }else{

            $search_bom_match = $_POST['docnum'].'-BOM';
            $so_bom_match = $_POST['so_edit'].'-BOM';

            $select_bomitem2 = "SELECT * FROM salesorderfile2 WHERE docnum=? AND bom_item=?";
            $stmt_bomitem2	= $link->prepare($select_bomitem2);
            $stmt_bomitem2->execute(array($so_bom_match,$_POST['itmcde_edit_hidden']));
            while($rs_bomitem2 = $stmt_bomitem2->fetch()){

                $select_bomitem="SELECT * FROM tranfile2 WHERE docnum=? AND itmcde=?";
                $stmt_bomitem	= $link->prepare($select_bomitem);
                $stmt_bomitem->execute(array($search_bom_match,$rs_bomitem2['itmcde']));
                while($rs_bomitem = $stmt_bomitem->fetch()){
                    $arr_record_bom_match = array();
                    $arr_record_bom_match['so_recid'] 	= $rs_bomitem2['recid'];
                    PDO_UpdateRecord($link,"tranfile2",$arr_record_bom_match,"recid = ?",array($rs_bomitem['recid']),false);
                }
            }
        }

        //UPDATING THE ORDER STATUS
        if(isset($_POST['so_edit']) && !empty($_POST['so_edit'])){

            //SETTING THE OLD order_status to 'pending'		
            if(isset($_POST['so_edit_hidden']) && !empty($_POST['so_edit_hidden'])){
                
                $sql_os = "UPDATE salesorderfile1 SET order_status='pending' WHERE docnum='".$_POST['so_edit_hidden']."'";
                $stmt_os = $link->prepare($sql_os);
                $stmt_os->execute();

                $bom_check = $_POST['so_edit_hidden'].'-BOM';

                //THIS IS ALSO TO UPDATE THE BOM ITEM
                $select_check_upd_os="SELECT * FROM salesorderfile1 WHERE docnum='".$bom_check."'";
                $stmt_check_upd_os	= $link->prepare($select_check_upd_os);
                $stmt_check_upd_os->execute();
                $rs_check_upd_os = $stmt_check_upd_os->fetch();

                if(!empty($rs_check_upd_os)){
                    $arr_record_so_match2 = array();
                    $arr_record_so_match2['order_status'] 	= 'pending';
                    PDO_UpdateRecord($link,"salesorderfile1",$arr_record_so_match2,"recid = ?",array($rs_check_upd_os['recid']),false);
                }


            }

            $so_bom_match = $_POST['so_edit'].'-BOM';

            //setting the new bom order_status to 'completed'
            $select_check_os_check2="SELECT * FROM salesorderfile2 WHERE docnum='".$so_bom_match."' OR docnum='".$_POST['so_edit']."'";
            $stmt_check_os_check2	= $link->prepare($select_check_os_check2);
            $stmt_check_os_check2->execute();
            $all_matched = true;
            while($rs_check_os_check2 = $stmt_check_os_check2->fetch()){

                $select_check_os_check="SELECT * FROM tranfile2 WHERE so_recid='".$rs_check_os_check2['recid']."'";
                $stmt_check_os_check	= $link->prepare($select_check_os_check);
                $stmt_check_os_check->execute();
                $rs_check_os_check = $stmt_check_os_check->fetch();

                if(empty($rs_check_os_check)){
                    $all_matched = false;
                    break;
                }
            }  

            if($all_matched == true){
                $arr_record_so_match = array();
                $arr_record_so_match['order_status'] 	= 'completed';
                PDO_UpdateRecord($link,"salesorderfile1",$arr_record_so_match,"docnum = ?",array($_POST['so_edit']),false);

                $bom_check = $_POST['so_edit'].'-BOM';

                //THIS IS ALSO TO UPDATE THE BOM ITEM
                $select_check_upd_os="SELECT * FROM salesorderfile1 WHERE docnum='".$bom_check."'";
                $stmt_check_upd_os	= $link->prepare($select_check_upd_os);
                $stmt_check_upd_os->execute();
                $rs_check_upd_os = $stmt_check_upd_os->fetch();

                if(!empty($rs_check_upd_os)){
                    $arr_record_so_match2 = array();
                    $arr_record_so_match2['order_status'] 	= 'completed';
                    PDO_UpdateRecord($link,"salesorderfile1",$arr_record_so_match2,"recid = ?",array($rs_check_upd_os['recid']),false);
                }
            }

        }else{

            if(isset($_POST['so_edit_hidden']) && !empty($_POST['so_edit_hidden'])){

                $sql_os = "UPDATE salesorderfile1 SET order_status='pending' WHERE docnum='".$_POST['so_edit_hidden']."'";
                $stmt_os = $link->prepare($sql_os);
                $stmt_os->execute();

                $bom_edit_os = $_POST['so_edit_hidden'].'-BOM';

                $sql_os2 = "UPDATE salesorderfile1 SET order_status='pending' WHERE docnum='".$bom_edit_os."'";
                $stmt_os2 = $link->prepare($sql_os2);
                $stmt_os2->execute();
            }

        }

      
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        //SETTING THE order_status to 'pending'		
        $select_order_status_checker="SELECT * FROM tranfile2 WHERE recid=? LIMIT 1";
        $stmt_order_status_checker	= $link->prepare($select_order_status_checker);
        $stmt_order_status_checker->execute(array($_POST['recid']));
        $rs_order_status_checker = $stmt_order_status_checker->fetch();

        $select_order_status="SELECT * FROM salesorderfile2 WHERE recid=? LIMIT 1";
        $stmt_order_status	= $link->prepare($select_order_status);
        $stmt_order_status->execute(array($rs_order_status_checker['so_recid']));
        $rs_order_status = $stmt_order_status->fetch();

        $select_order_status2="SELECT * FROM salesorderfile1 WHERE docnum=?";
        $stmt_order_status2	= $link->prepare($select_order_status2);
        $stmt_order_status2->execute(array($rs_order_status['docnum']));
        $rs_order_status2 = $stmt_order_status2->fetch(); 
        
        $sql_os = "UPDATE salesorderfile1 SET order_status='pending' WHERE docnum='".$rs_order_status2['docnum']."'";
        $stmt_os = $link->prepare($sql_os);
        $stmt_os->execute();

        // $bom_check = $rs_order_status2['docnum'].'-BOM';

        // //SO THAT THE BOM IS ALSO CHANGED
        // $sql_os = "UPDATE salesorderfile1 SET order_status='pending' WHERE docnum='".$bom_check."'";
        // $stmt_os = $link->prepare($sql_os);
        // $stmt_os->execute();

        //get itmcde
        // $select_searchbom="SELECT * FROM tranfile2 WHERE recid=? LIMIT 1";
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

        //         $delete_query_bom="DELETE FROM tranfile2 WHERE docnum='".$bom_docnum_del."' AND bom_item='".$rs_itembomsearch['itmcde']."' AND trncde='SAL' AND bom_linenum='".$rs_searchbom['itmqty']."'";
        //         $xstmt_bom=$link->prepare($delete_query_bom);
        //         $xstmt_bom->execute();
        //     };

        // }        


        //ACTUAL DELETE
        $delete_id=$_POST['recid'];
        $delete_query="DELETE  FROM  tranfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));

        // $select_chk_del="SELECT * FROM tranfile2 WHERE docnum='".$bom_docnum_del."'";
        // $stmt_chk_del	= $link->prepare($select_chk_del);
        // $stmt_chk_del->execute();
        // $rs_chk_del = $stmt_chk_del->fetchAll();

        // if(count($rs_chk_del) <= 0){

        //     $select_chk_del2="DELETE  FROM  tranfile1 WHERE docnum='".$bom_docnum_del."'";
        //     $stmt_chk_del2	= $link->prepare($select_chk_del2);
        //     $stmt_chk_del2->execute();
        // }
    
    }

    $xret["html"] .= "<tr style='font-weight:bold'>";
        $xret["html"] .= "<td>Item</td>";
        $xret["html"] .= "<td>Quantity</td>";
        $xret["html"] .= "<td style='text-align:right'>Price</td>";
        $xret["html"] .= "<td style='text-align:right'>Amount</td>";
        $xret["html"] .= "<td style='text-align:right'>Wholesale Price:</td>";
        $xret["html"] .= "<td style='text-align:center'>Matched SO No.</td>";
        $xret["html"] .= "<td class='text-center'>Action</td>";
    $xret["html"] .= "</tr>";  

    $select_salesfile2="SELECT tranfile2.wholesaleprc as wholesaleprc, tranfile2.itmcde as 'tranfile2_itmcde', tranfile2.itmqty as 'tranfile2_itmqty', tranfile2.so_recid as 'so_recid', tranfile2.order_status as tranfile2_order_status, itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.recid as tranfile2_recid
    FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE docnum=?";
    $stmt_salesfile2	= $link->prepare($select_salesfile2);
    $stmt_salesfile2->execute(array($docnum));
    $trntot = 0;

    while($rs_salesfile2 = $stmt_salesfile2->fetch()){

        if(!empty($rs_salesfile2['so_recid'])){
            $select_chkpo="SELECT * FROM salesorderfile2 WHERE recid='".$rs_salesfile2['so_recid']."'";
            $stmt_chkpo	= $link->prepare($select_chkpo);
            $stmt_chkpo->execute();
            $rs_chkpo = $stmt_chkpo->fetch();
            $matched_so = $rs_chkpo['docnum'];
        }else{
            $matched_so = '';
        }

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

        $order_status = "";
        if($rs_salesfile2['tranfile2_order_status'] == "INTRANSIT"){
            $order_status = "In Transit";
        }else if($rs_salesfile2['tranfile2_order_status'] == "SHIPPED"){
            $order_status = "Shipped";
        }
        

        $xret["html"] .= "<tr>";
            $xret["html"] .= "<td>".htmlspecialchars($rs_salesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
            $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['itmqty']."</td>";
            $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
            $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
            $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['wholesaleprc']."</td>";
            $xret["html"] .= "<td style='text-align:center'>".$matched_so."</td>";
            // $xret["html"] .= "<td style='text-align:center'>".$order_status."</td>";
            $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";
                $xret["html"].= "<div class='dropdown'>";
                    $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                        $xret["html"].= "Action";
                    $xret["html"].= "</button>";

                    $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'>";
                        $xret["html"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_salesfile2['tranfile2_recid']."','".$rs_salesfile2['tranfile2_itmcde']."','".$rs_salesfile2['tranfile2_itmqty']."')\">";
                            $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                        $xret["html"].= "</li>";

                        $xret["html"].= "<li onclick=\"salesfile2('delete' , '".$rs_salesfile2['tranfile2_recid']."','','')\">";
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
            $xret["html_mobile"] .= "<td class='fw-bold' style='text-align:right'>Quantity</td>";
            $xret["html_mobile"] .= "<td>".$rs_salesfile2['itmqty']."</td>";
        $xret["html_mobile"] .= "</tr>";

        $xret["html_mobile"] .= "<tr>";
            $xret["html_mobile"] .= "<td class='fw-bold'>Price</td>";
            $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
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
            $xret["html_mobile"] .= "<td class='fw-bold'>Matched SO No.</td>";
            $xret["html_mobile"] .= "<td style='text-align:right'>".$matched_so."</td>";
        $xret["html_mobile"] .= "</tr>";

        // $xret["html_mobile"] .= "<tr>";
        //     $xret["html_mobile"] .= "<td class='fw-bold'>Order Status</td>";
        //     $xret["html_mobile"] .= "<td style='text-align:right'>".$order_status."</td>";
        // $xret["html_mobile"] .= "</tr>";

        $xret["html_mobile"] .= "<tr>";
            $xret["html_mobile"].= "<td  class='fw-bold' style='text-align:left' data-label='Action'>Action</td>";
            $xret["html_mobile"].= "<td>";
                $xret["html_mobile"].= "<div class='dropdown'>";
                    $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                        $xret["html_mobile"].= "Action";
                    $xret["html_mobile"].= "</button>";

                    $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$rs_salesfile2['tranfile2_recid']."'>";
                        $xret["html_mobile"].= "<li onclick=\"salesfile2('getEdit' , '".$rs_salesfile2['tranfile2_recid']."','".$rs_salesfile2['tranfile2_itmcde']."','".$rs_salesfile2['tranfile2_itmqty']."')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                        $xret["html_mobile"].= "</li>";
    
                        $xret["html_mobile"].= "<li onclick=\"salesfile2('delete' , '".$rs_salesfile2['tranfile2_recid']."','','')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                        $xret["html_mobile"].= "</li>";
                        
                    $xret["html_mobile"].= "</ul>";
                $xret["html_mobile"].= "</div>";
            $xret["html_mobile"].= "</td>";
        $xret["html_mobile"] .= "</tr>";        
        
    }
    
    $select_trntot='SELECT * FROM tranfile1 WHERE docnum=?';
	$stmt_trntot	= $link->prepare($select_trntot);
	$stmt_trntot->execute(array($docnum));
    $rs_trntot = $stmt_trntot->fetch();

    $arr_record_salesfile1 = array();			
    $arr_record_salesfile1['trntot'] = $trntot;
    if(isset($_POST["event_action"]) && ($_POST["event_action"]  == "insert_new" || $_POST["event_action"]  == "insert_old" || $_POST["event_action"]  == "submitEdit" || $_POST["event_action"]  == "delete" || $_POST["event_action"]  == "insert")){
        PDO_UpdateRecord($link,"tranfile1",$arr_record_salesfile1,"recid = ?",array($rs_trntot["recid"]));  
    }

    
    $xret["trntot"] = number_format($trntot,2);

echo json_encode($xret);
?>