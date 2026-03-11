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
    $xret["retEdit"] = array();
    $xret["purchasesDefault"] = array();

    $xhide_price = false;
    //if($_SESSION['hide_price_crud'] == 1 && $_SESSION['userdesc'] !='admin'){
    //    $xhide_price = true;
    //}

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

                $date_today = date('Y-m-d');

                $select_latestprice="SELECT * FROM tranfile1 LEFT JOIN
                                    tranfile2 ON 
                                    tranfile1.docnum = tranfile2.docnum  
                                    WHERE tranfile2.itmcde='".$rs_itemfile['itmcde']."' 
                                    AND tranfile1.trndte<='".$date_today."'
                                    AND tranfile1.trncde='PUR' 
                                    ORDER BY tranfile1.trndte DESC, tranfile1.recid DESC LIMIT 1";
                $stmt_latestprice	= $link->prepare($select_latestprice);
                $stmt_latestprice->execute();
                $rs_latestprice = $stmt_latestprice->fetch();

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['itmdsc'];
                    $xret["html"] .= "</td>";

                    // $xret["html"] .= "<td>";
                    //     $xret["html"] .= $rs_itemfile['inventory_type'];
                    // $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='text-center'>";
                      $xret["html"] .= "<button type='button' onclick='select_item_modal("
                            . json_encode($rs_itemfile['itmcde']) . ","
                            . json_encode($rs_itemfile['itmdsc']) . ","
                            . json_encode($_POST['event_action_itmsearch']) . ","
                            . json_encode($xret['itm_search']) . ","
                            . json_encode($rs_latestprice['untprc'])
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


    if(isset($_POST['docnum'])){
        $docnum = $_POST['docnum'];
    }else{
        $_POST['docnum'] = '';
        $docnum = '';
    }

    $trncde = $_POST["trncde"];


    $select_db_purchasesdf="SELECT * FROM default_purchases WHERE is_selected='1' LIMIT 1";
    $stmt_purchasesdf	= $link->prepare($select_db_purchasesdf);
    $stmt_purchasesdf->execute();
    $rs_purchasesdf = $stmt_purchasesdf->fetch();

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

    if(isset($_POST["event_action"]) && 
        ($_POST["event_action"] == "save_exit" || 
        $_POST["event_action"] == "save_new" ||
        $_POST["event_action"] == "save_qr_exit")){

        $docnum = $_POST["docnum_1"];

        $select_docnum='SELECT * FROM tranfile1 WHERE docnum=?';
        $stmt_docnum	= $link->prepare($select_docnum);
        $stmt_docnum->execute(array($docnum));
        $rs_docnum = $stmt_docnum->fetch();

        $select_qrcheck="SELECT * FROM tranfile1 WHERE po_qr_id='".$_POST['po_order_id']."'";
        $stmt_qrcheck	= $link->prepare($select_qrcheck);
        $stmt_qrcheck->execute();
        $rs_qrcheck = $stmt_qrcheck->fetch();

        if(empty($rs_docnum)){

            if($_POST["event_action"] == "save_qr_exit"){

                if(empty($rs_qrcheck)){
                    $xret["status"] = 0;
                    $xret["msg"] = "Insert an Item first";
                    $xret["error1"] = 1;
                }
            }

            if(empty($_POST['trndte_1'])){

                if($xret["error1"] == 1){
                    $xret["msg"] .="</br>";
                }
                $xret["status"] = 0;
                $xret["error2"] = 1;
                $xret["msg"] .= "<b>Tran. Date</b> cannot be empty.";
            }

            if(isset($_POST['po_order_id']) && !empty($_POST['po_order_id']) && empty($rs_check)){
                if($xret["error1"] == 1 || $xret["error2"] == 1){
                    $xret["msg"] .= "</br>"; 
                }
    
                $select_dbcheck="SELECT * FROM tranfile1 WHERE po_qr_id=? AND docnum !='".$_POST["docnum"]."'";
                $stmt_dbcheck	= $link->prepare($select_dbcheck);
                $stmt_dbcheck->execute(array($_POST['po_order_id']));
                $rs_dbcheck = $stmt_dbcheck->fetch();
    
                if(!empty($rs_dbcheck)){
                    $xret["msg"] .= "Purchase Order ID: <b>".$_POST['po_order_id']."</b> in use. Input a different one."; 
                    $xret["status"] = 0;
                    $xret["error3"] = 1;
                }
    
            }             
            
            if($xret["status"] == 1){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record = array();
                $arr_record['docnum'] 	= $docnum;
                $arr_record['orderby'] 	= $_POST['orderby_1'];
                $arr_record['shipto'] 	= $_POST['shipto_1'];
                $arr_record['suppcde'] 	= $_POST['cusname_1'];
                $arr_record['cuscde'] 	= NULL;
                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['trndte'] 	= $_POST['trndte_1'];
                $arr_record['paydate'] 	= $_POST['paydate_1'];
                $arr_record['paydetails'] 	= $_POST['payment_details_1'];
                //$arr_record['purchase_type'] 	= $_POST['purchase_type_1'];
                $arr_record['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record['remarks'] 	= $_POST['remarks_1'];
                $arr_record['trncde'] 	= $trncde;

                if(isset($_POST['po_order_id']) && !empty($_POST['po_order_id'])){
                    $currentDate = date('Y-m-d');
                    //$arr_record_file1['po_qr_date_scanned']     = $currentDate;
                    $arr_record['po_qr_id']     = $_POST['po_order_id'];
                }
    
                PDO_InsertRecord($link,'tranfile1',$arr_record, false);
    
                if($_POST["event_action"] == "save_exit"){
                    $xret["msg"] = "save_exit";
                }else if($_POST["event_action"] == "save_qr_exit"){
                    $xret["msg"] = "save_qr_exit";
                }
            }

        }else{


            if(empty($_POST['trndte_1'])){
                $xret["status"] = 0;
                $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
                $xret["error1"] = 1;
            }

            if(isset($_POST['po_order_id']) && !empty($_POST['po_order_id'])){
                if($xret["error1"] == 1){
                    $xret["msg"] .= "</br>"; 
                }
    
                $select_dbcheck="SELECT * FROM tranfile1 WHERE po_qr_id=? AND docnum !='".$_POST["docnum"]."'";
                $stmt_dbcheck	= $link->prepare($select_dbcheck);
                $stmt_dbcheck->execute(array($_POST['po_order_id']));
                $rs_dbcheck = $stmt_dbcheck->fetch();
    
                if(!empty($rs_dbcheck)){
                    $xret["msg"] .= "Purchase Order ID: <b>".$_POST['po_order_id']."</b> in use. Input a different one."; 
                    $xret["status"] = 0;
                    $xret["error2"] = 1;
                }
    
            } 

            
            if($xret["status"] == 1){
                $xret["msg"] = "edit_exit";

                $recid = $rs_docnum['recid'];
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_update = array();			
    
                $arr_record_update['orderby'] 	= $_POST['orderby_1'];
                $arr_record_update['shipto'] 	= $_POST['shipto_1'];
                $arr_record_update['suppcde'] 	= $_POST['cusname_1'];
                $arr_record_update['cuscde'] 	= NULL;
                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['trndte'] 	= $_POST['trndte_1'];
                $arr_record_update['paydate'] 	= $_POST['paydate_1'];
                $arr_record_update['paydetails'] 	= $_POST['payment_details_1'];
                //$arr_record_update['purchase_type'] 	= $_POST['purchase_type_1'];
                $arr_record_update['remarks'] 	= $_POST['remarks_1'];
                $arr_record_update['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record_update['po_qr_id'] 	= $_POST['po_order_id'];
                PDO_UpdateRecord($link,"tranfile1",$arr_record_update,"recid = ?",array($recid),false); 

                 
            }

  
        }

        
        if($_POST["event_action"] == "save_new" && $xret["status"] == 1){

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

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "insert" || $_POST["event_action"] == "insert_qr")){

        $select_check="SELECT * FROM tranfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();


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

        if(!isset($_POST['itmqty_add']) || empty($_POST['itmqty_add']) || $_POST['itmqty_add'] == '0'){

            if($xret["error1"] == 1 || $xret["error2"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["msg"] .= "<b>Quantity</b> Cannot Be Empty or 0"; 
           

            $xret["status"] = 0;
            $xret["error3"] = 1;

        }   

        if(isset($_POST['po_order_id']) && !empty($_POST['po_order_id']) && empty($rs_check)){
            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $select_dbcheck="SELECT * FROM tranfile1 WHERE po_qr_id=? AND docnum !='".$_POST["docnum"]."'";
            $stmt_dbcheck	= $link->prepare($select_dbcheck);
            $stmt_dbcheck->execute(array($_POST['po_order_id']));
            $rs_dbcheck = $stmt_dbcheck->fetch();

            if(!empty($rs_dbcheck)){
                $xret["msg"] .= "Purchase Order ID: <b>".$_POST['po_order_id']."</b> in use. Input a different one."; 
                $xret["status"] = 0;
                $xret["error4"] = 1;
            }

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
                $arr_record_file1['cuscde'] 	= NULL;
                $arr_record_file1['trntot'] 	= $_POST['trntot_1'];
                $arr_record_file1['trndte'] 	= $_POST['trndte_1'];
                $arr_record_file1['paydate'] 	= $_POST['paydate_1'];
                $arr_record_file1['paydetails'] = $_POST['payment_details_1'];
                //$arr_record_file1['purchase_type'] 	= $_POST['purchase_type_1'];
                $arr_record_file1['remarks'] 	= $_POST['remarks_1'];
                $arr_record_file1['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record_file1['trncde']     = $trncde;

                if($_POST["event_action"] == "insert_qr"){

                    date_default_timezone_set('Asia/Manila');
                    $currentDate = date('Y-m-d');
                    $arr_record_file1['po_qr_date_scanned'] = $currentDate;
                }

                $arr_record_file1['po_qr_id'] = $_POST['po_order_id'];
                PDO_InsertRecord($link,'tranfile1',$arr_record_file1, false);

                $xret["msg"] = "insert_new";
            }else{
                $xret["msg"] = "insert_old";
            }

            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            $arr_record['stkqty'] 	= $_POST['itmqty_add'];
            $arr_record['untprc'] 	= $_POST['price_add'];
            $arr_record['extprc'] 	= $_POST['amount_add'];
            $arr_record['trncde']     = $trncde;
            $arr_record['purnum_recid']     = $_POST['xrecid_po_hidden'];
            PDO_InsertRecord($link,'tranfile2',$arr_record, false);

            $recid_latest_match = $link->lastInsertId();

            if(isset($_POST['multi_itm_select']) && !empty($_POST['multi_itm_select'])){
        
                $multi_select_array = explode(',', $_POST['multi_itm_select']);
    
                foreach ($multi_select_array as $po2_recid) {
                    // Trim to remove extra spaces, just in case
                    $po2_recid = trim($po2_recid);
    
                    $arr_record_upd_match = array();
                    $arr_record_upd_match['tranfile2_recid'] 	= $recid_latest_match;
                    PDO_UpdateRecord($link,'purchasesorderfile2',$arr_record_upd_match,"recid = ?",array($po2_recid),false);  
                }
                
            }

            $select_dbcheck="SELECT * FROM purchasesorderfile1 WHERE purnum_recid=?";
            $stmt_dbcheck	= $link->prepare($select_dbcheck);
            $stmt_dbcheck->execute(array($_POST["docnum"]));
            $rs_dbcheck = $stmt_dbcheck->fetch();
            if(empty($rs_dbcheck)){

                $xitem_chk_all = true;
                $xcount_itmchk = 0;
                $select_update_ord2="SELECT tranfile2.itmcde as 'itmcde',
                                            tranfile2.itmqty as 'itmqty',
                                            tranfile1.suppcde as 'suppcde'
                                            FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile1.docnum='".$_POST["docnum"]."'";
                $stmt_update_ord2	= $link->prepare($select_update_ord2);
                $stmt_update_ord2->execute();
                while($rs_update_ord2 = $stmt_update_ord2->fetch()){

                    $select_db_por = "SELECT *, purchasesorderfile1.recid as 'po1_recid'  FROM purchasesorderfile1 
                                LEFT JOIN purchasesorderfile2 ON 
                                purchasesorderfile1.docnum = purchasesorderfile2.docnum
                                WHERE purchasesorderfile2.itmcde = '".$rs_update_ord2['itmcde']."'
                                AND purchasesorderfile2.itmqty = '".$rs_update_ord2['itmqty']."' 
                                AND purchasesorderfile1.suppcde = '".$rs_update_ord2['suppcde']."'
                                AND (purchasesorderfile1.purnum_recid= '' || purchasesorderfile1.purnum_recid IS NULL)
                                ORDER BY purchasesorderfile1.trndte ASC LIMIT 1";
                    $stmt_por	= $link->prepare($select_db_por);
                    $stmt_por->execute();
                    $rs_por = $stmt_por->fetch();

                    if(!empty($rs_por) && $xitem_chk_all == true){
                        $xitem_chk_all = true;
                        $xrecid = $rs_por['po1_recid'];
            
                        $xcount_itmchk = 1;
                    }else{
                        $xitem_chk_all = false;
                    }                    
                    
                }
        
                if($xitem_chk_all == true){

                    $arr_updpur = array();
                    $arr_updpur['purnum_recid'] = $_POST['docnum'];    
                    PDO_UpdateRecord($link,'purchasesorderfile1',$arr_updpur,"recid = ?",array($xrecid),false);  
                }           
            }else{

                $xitem_chk_all = true;
                $xcount_itmchk = 0;
                $select_update_ord2="SELECT tranfile2.itmcde as 'itmcde',
                                            tranfile2.itmqty as 'itmqty',
                                            tranfile1.suppcde as 'suppcde'
                                            FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile1.docnum='".$_POST["docnum"]."'";
                $stmt_update_ord2	= $link->prepare($select_update_ord2);
                $stmt_update_ord2->execute();
                while($rs_update_ord2 = $stmt_update_ord2->fetch()){

                    $select_db_por = "SELECT *, purchasesorderfile1.recid as 'po1_recid'  FROM purchasesorderfile1 
                                LEFT JOIN purchasesorderfile2 ON 
                                purchasesorderfile1.docnum = purchasesorderfile2.docnum
                                WHERE purchasesorderfile2.itmcde = '".$rs_update_ord2['itmcde']."'
                                AND purchasesorderfile2.itmqty = '".$rs_update_ord2['itmqty']."' 
                                AND purchasesorderfile1.suppcde = '".$rs_update_ord2['suppcde']."'
                                AND (purchasesorderfile1.purnum != '' || purchasesorderfile1.purnum IS NOT NULL)
                                ORDER BY purchasesorderfile1.trndte ASC LIMIT 1";
                    $stmt_por	= $link->prepare($select_db_por);
                    $stmt_por->execute();
                    $rs_por = $stmt_por->fetch();

                    if(!empty($rs_por) && $xitem_chk_all == true){
                        $xitem_chk_all = true;
                        $xrecid = $rs_por['po1_recid'];
            
                        $xcount_itmchk = 1;
                    }else{
                        $xitem_chk_all = false;
                        $xrecid = $rs_por['po1_recid'];
                    }                    
                }
        
                if($xitem_chk_all == false){

                    $arr_updpur = array();
                    $arr_updpur['purnum'] = $_POST['docnum'];    
                    PDO_UpdateRecord($link,'purchasesorderfile1',$arr_updpur,"recid = ?",array($xrecid),false);  
                }                    
            }              
        }
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_tranfile2="SELECT tranfile2.recid as 'recid', tranfile2.itmcde as 'itmcde', tranfile2.purnum_recid as 'tranfile2_purnum_recid', tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, itemfile.itmdsc, tranfile2.recid as tranfile2_recid FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE tranfile2.recid=?";
        $stmt_tranfile2	= $link->prepare($select_tranfile2);
        $stmt_tranfile2->execute(array($_POST["recid"]));
        $rs_tranfile2 = $stmt_tranfile2->fetch();

        if(!empty($rs_tranfile2["untprc"])){
            $rs_tranfile2["untprc"] = number_format($rs_tranfile2["untprc"],2);
        }
        if($rs_tranfile2["extprc"]){
            $rs_tranfile2["extprc"] = number_format($rs_tranfile2["extprc"],2);
        }


        $select_tranfile2_chk="SELECT * FROM purchasesorderfile2 WHERE tranfile2_recid='".$rs_tranfile2['recid']."'";
        $stmt_tranfile2_chk	= $link->prepare($select_tranfile2_chk);
        $stmt_tranfile2_chk->execute();
        $rs_tranfile2_chk = $stmt_tranfile2_chk->fetchAll();

        if(count($rs_tranfile2_chk) > 0){

            $select_chkpo="SELECT * FROM purchasesorderfile2 WHERE tranfile2_recid='".$rs_tranfile2['recid']."'";
            $stmt_chkpo	= $link->prepare($select_chkpo);
            $stmt_chkpo->execute();
            $checkpo_count = 0;
            $matched_recid_hidden = '';
            $matched_po = '';
            while($rs_chkpo = $stmt_chkpo->fetch()){
                if($checkpo_count == 0){
                    $matched_po = $rs_chkpo['docnum'];
                    $matched_recid_hidden = $rs_chkpo['recid'];
                }else{
                    $matched_po .= ", ".$rs_chkpo['docnum'];
                    $matched_recid_hidden .= ",".$rs_chkpo['recid'];
                }
                $checkpo_count++;
            };


        }else{
            $matched_po = '';
            $matched_recid_hidden = '';
        }

        $xret["retEdit"] = [
            "itmcde" =>  $rs_tranfile2["itmcde"],            
            "itmdsc" =>  $rs_tranfile2["itmdsc"],
            "itmqty" =>  $rs_tranfile2["itmqty"],
            "untprc" =>  $rs_tranfile2["untprc"],
            "extprc" =>  $rs_tranfile2["extprc"],
            "purnum_recid" =>  $rs_tranfile2["tranfile2_purnum_recid"],
            "matched_po" =>  $matched_po,
            "recid" =>  $rs_tranfile2["tranfile2_recid"],
            "matched_po_recid_hidden" =>  $matched_recid_hidden,

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

        if(!isset($_POST['itmqty_edit']) || empty($_POST['itmqty_edit']) || $_POST['itmqty_edit'] == '0'){

            if($xret["error1"] == 1 || $xret["error2"] == 1){
                $xret["msg"] .= "</br>"; 
            }

            $xret["msg"] .= "<b>Quantity</b> Cannot Be Empty or 0"; 
           

            $xret["status"] = 0;
            $xret["error3"] = 1;

        }             

        if($xret['status'] == 1){

            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_edit_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_edit'];
            $arr_record['stkqty'] 	= $_POST['itmqty_edit'];
            $arr_record['untprc'] 	= $_POST['price_edit'];
            $arr_record['extprc'] 	= $_POST['amount_edit'];
            $arr_record['purnum_recid'] 	= $_POST['xrecid_po_hidden'];
            PDO_UpdateRecord($link,"tranfile2",$arr_record,"recid = ?",array($_POST['recid']));   
            $xret["msg"] = "submitEdit";


            if(isset($_POST['multi_itm_select_original']) && !empty($_POST['multi_itm_select_original'])){
                $multi_select_original_array = explode(',', $_POST['multi_itm_select_original']);
            }else{
                $multi_select_original_array = array();
            }


            if(isset($_POST['multi_itm_select']) && !empty($_POST['multi_itm_select'])){
        
                $multi_select_array = explode(',', $_POST['multi_itm_select']);

                foreach ($multi_select_array as $po2_recid) {

                    $po2_recid = trim($po2_recid);

                    if(!in_array($po2_recid,$multi_select_original_array)){
                        $arr_record_upd_match = array();
                        $arr_record_upd_match['tranfile2_recid'] 	= $_POST['recid'];
                        PDO_UpdateRecord($link,'purchasesorderfile2',$arr_record_upd_match,"recid = ?",array($po2_recid),false); 
                    }
    
                }
                
            }  
            
            if(isset($_POST['multi_itm_select_original']) && !empty($_POST['multi_itm_select_original'])){
        
                $multi_select_array = explode(',', $_POST['multi_itm_select']);

                foreach ($multi_select_original_array as $po2_recid_array) {

                    $po2_recid_array = trim($po2_recid_array);

                    if(!in_array($po2_recid_array,$multi_select_array)){
                        $arr_record_upd_match_original = array();
                        $arr_record_upd_match_original['tranfile2_recid'] 	= '';
                        PDO_UpdateRecord($link,'purchasesorderfile2',$arr_record_upd_match_original,"recid = ?",array($po2_recid_array),false); 
                    }
    
                }
                
            }  
            
            $xitem_chk_all = true;
            $xcount_itmchk = 0;
            $select_update_ord2="SELECT tranfile2.itmcde as 'itmcde',
                                        tranfile2.itmqty as 'itmqty',
                                        tranfile1.suppcde as 'suppcde'
                                        FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum WHERE tranfile1.docnum='".$_POST['docnum']."'";
            $stmt_update_ord2	= $link->prepare($select_update_ord2);
            $stmt_update_ord2->execute();
            while($rs_update_ord2 = $stmt_update_ord2->fetch()){
    
                $select_db_por = "SELECT *, purchasesorderfile1.recid as 'po1_recid'  FROM purchasesorderfile1 
                            LEFT JOIN purchasesorderfile2 ON 
                            purchasesorderfile1.docnum = purchasesorderfile2.docnum
                            WHERE purchasesorderfile2.itmcde = '".$rs_update_ord2['itmcde']."'
                            AND purchasesorderfile2.itmqty = '".$rs_update_ord2['itmqty']."' 
                            AND purchasesorderfile1.suppcde = '".$rs_update_ord2['suppcde']."'
                            AND (purchasesorderfile1.purnum_recid= '' || purchasesorderfile1.purnum_recid IS NULL)
                            ORDER BY purchasesorderfile1.trndte ASC LIMIT 1";
                $stmt_por	= $link->prepare($select_db_por);
                $stmt_por->execute();
                $rs_por = $stmt_por->fetch();
    
                if(!empty($rs_por) && $xitem_chk_all == true){
                    $xitem_chk_all = true;
                    $xrecid = $rs_por['po1_recid'];
        
                    $xcount_itmchk = 1;
                }else{
                    $xitem_chk_all = false;
                }                    
                
            }
     
            if($xitem_chk_all == true){
    
                $arr_updpur = array();
                $arr_updpur['purnum'] = $_POST['docnum'];    
                PDO_UpdateRecord($link,'purchasesorderfile1',$arr_updpur,"recid = ?",array($xrecid),false);  
            }                
        }
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        $select_db_delcheck = "SELECT * FROM purchasesorderfile2 WHERE tranfile2_recid ='".$_POST['recid']."'";
        $stmt_delcheck	= $link->prepare($select_db_delcheck);
        $stmt_delcheck->execute();
        while($rs_delcheck = $stmt_delcheck->fetch()){

            $arr_delarr2 = array();
            $arr_delarr2['tranfile2_recid'] = '0';    
            PDO_UpdateRecord($link,'purchasesorderfile2',$arr_delarr2,"recid = ?",array($rs_delcheck['recid']),false);  

        }

        // delete			
        $delete_id=$_POST['recid'];
        $delete_query="DELETE  FROM  tranfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));


    
    }
    
            $xret["html"] .= "<tr style='font-weight:bold'>";
                $xret["html"] .= "<td>Item</td>";
                $xret["html"] .= "<td style='text-align:right'>Quantity</td>";

                if($xhide_price == false){
                    $xret["html"] .= "<td style='text-align:right'>Price</td>";
                    $xret["html"] .= "<td style='text-align:right'>Amount</td>";
                }
                
                $xret["html"] .= "<td style='text-align:center'>Matched PO No./s</td>";
                $xret["html"] .= "<td class='text-center'>Action</td>";
            $xret["html"] .= "</tr>";  

            $select_salesfile2="SELECT tranfile2.recid as 'recid', tranfile2.purnum_recid as 'purnum_recid', itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.recid as tranfile2_recid
            FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE docnum=?";
            $stmt_salesfile2	= $link->prepare($select_salesfile2);
            $stmt_salesfile2->execute(array($docnum));
            $trntot = 0;
            while($rs_salesfile2 = $stmt_salesfile2->fetch()){

                if(!empty($rs_salesfile2['purnum_recid'])){
                    $select_chkpo="SELECT * FROM purchasesorderfile2 WHERE tranfile2_recid='".$rs_salesfile2['recid']."'";
                    $stmt_chkpo	= $link->prepare($select_chkpo);
                    $stmt_chkpo->execute();
                    $po2_counter = 0;
                    $matched_po = '';
                    while($rs_chkpo = $stmt_chkpo->fetch()){

                        if($po2_counter == 0){
                            $matched_po = $rs_chkpo['docnum'];
                        }else{
                            $matched_po .= ', '.$rs_chkpo['docnum'];
                        }

                        $po2_counter++;
                    }

                }else{
                    $matched_po = '';
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

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>".htmlspecialchars($rs_salesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                    $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['itmqty']."</td>";
                    if($xhide_price == false){
                        $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
                        $xret["html"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
                    }

                    $xret["html"] .= "<td style='text-align:center'>".$matched_po."</td>";

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

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Item</td>";
                    $xret["html_mobile"] .= "<td>".htmlspecialchars($rs_salesfile2['itemfile_itmdsc'],ENT_QUOTES)."</td>";
                $xret["html_mobile"] .= "</tr>";
        
                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Quantity</td>";
                    $xret["html_mobile"] .= "<td>".$rs_salesfile2['itmqty']."</td>";
                $xret["html_mobile"] .= "</tr>";

                if($xhide_price == false){

                    $xret["html_mobile"] .= "<tr>";
                        $xret["html_mobile"] .= "<td class='fw-bold'>Price</td>";
                        $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['untprc']."</td>";
                    $xret["html_mobile"] .= "</tr>";
            
                    $xret["html_mobile"] .= "<tr>";
                        $xret["html_mobile"] .= "<td class='fw-bold'>Amount</td>";
                        $xret["html_mobile"] .= "<td style='text-align:right'>".$rs_salesfile2['extprc']."</td>";
                    $xret["html_mobile"] .= "</tr>";
                }

                $xret["html_mobile"] .= "<tr>";
                    $xret["html_mobile"] .= "<td class='fw-bold'>Matched PO No./s</td>";
                    $xret["html_mobile"] .= "<td style='text-align:right'>".$matched_po."</td>";
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