
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

                    $xret["html"] .= "<td class='text-center'>";
                        $xret["html"] .= "<button type='button' onclick='select_item_modal("
                        . json_encode($rs_itemfile['itmcde']) . ", "
                        . json_encode($rs_itemfile['itmdsc']) . ", "
                        . json_encode($rs_price['tranfile2_untprc']) . ", "
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
                        $xret["html"].= "<button type='button' onclick='select_item_modal(\"".$rs_itemfile['itmcde']."\",\"".htmlspecialchars($rs_itemfile['itmdsc'],ENT_QUOTES)."\",\"".$rs_price['tranfile2_untprc']."\",\"".$_POST['event_action_itmsearch']."\")' class='btn btn-primary fw-bold'>";                        
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

        $select_price="SELECT * FROM itemfile WHERE itmcde='".$xitmcde."'";
        $stmt_price	= $link->prepare($select_price);
        $stmt_price->execute();
        $rs_price = $stmt_price->fetch();
        $xret["retEdit"]['xprice'] = '';
        $xret["retEdit"]['xprice'] = $rs_price['untprc'];

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

        if(!isset($_POST['warcde_add']) || empty($_POST['warcde_add'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error4"] = 1;
        }

        if(!isset($_POST['warehouse_floor_id_add']) || empty($_POST['warehouse_floor_id_add'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error4"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse Floor</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error5"] = 1;
        }

        if(!isset($_POST['warehouse_staff_id_add']) || empty($_POST['warehouse_staff_id_add'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error4"] == 1 || $xret["error5"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse Staff</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error6"] = 1;
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
    
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            $arr_record['stkqty'] 	= $_POST['itmqty_add'];
            $arr_record['untprc'] 	= $_POST['price_add'];
            $arr_record['extprc'] 	= $_POST['amount_add'];
            $arr_record['warcde'] 	= $_POST['warcde_add'];
            $arr_record['warehouse_floor_id'] = $_POST['warehouse_floor_id_add'];
            $arr_record['warehouse_staff_id'] = $_POST['warehouse_staff_id_add'];
            $arr_record['trncde']     = 'ADJ';
    
            PDO_InsertRecord($link,'tranfile2',$arr_record, false);

        }


    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_tranfile2="SELECT tranfile2.itmcde as 'itmcde', tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.warcde, tranfile2.warehouse_floor_id, tranfile2.warehouse_staff_id, itemfile.itmdsc, tranfile2.recid as tranfile2_recid FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE tranfile2.recid=?";
        $stmt_tranfile2	= $link->prepare($select_tranfile2);
        $stmt_tranfile2->execute(array($_POST["recid"]));
        $rs_tranfile2 = $stmt_tranfile2->fetch();

        if(!empty($rs_tranfile2["untprc"])){
            $rs_tranfile2["untprc"] = number_format($rs_tranfile2["untprc"],2);
        }
        if($rs_tranfile2["extprc"]){
            $rs_tranfile2["extprc"] = number_format($rs_tranfile2["extprc"],2);
        }

        $xret["retEdit"] = [
            "itmcde" =>  $rs_tranfile2["itmcde"],
            "itmdsc" =>  $rs_tranfile2["itmdsc"],
            "itmqty" =>  $rs_tranfile2["itmqty"],
            "untprc" =>  $rs_tranfile2["untprc"],
            "extprc" =>  $rs_tranfile2["extprc"],
            "warcde" =>  isset($rs_tranfile2["warcde"]) ? $rs_tranfile2["warcde"] : '',
            "warehouse_floor_id" =>  isset($rs_tranfile2["warehouse_floor_id"]) ? $rs_tranfile2["warehouse_floor_id"] : '',
            "warehouse_staff_id" =>  isset($rs_tranfile2["warehouse_staff_id"]) ? $rs_tranfile2["warehouse_staff_id"] : '',
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

        if(!isset($_POST['warcde_edit']) || empty($_POST['warcde_edit'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error4"] = 1;
        }

        if(!isset($_POST['warehouse_floor_id_edit']) || empty($_POST['warehouse_floor_id_edit'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error4"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse Floor</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error5"] = 1;
        }

        if(!isset($_POST['warehouse_staff_id_edit']) || empty($_POST['warehouse_staff_id_edit'])){

            if($xret["error1"] == 1 || $xret["error2"] == 1 || $xret["error3"] == 1 || $xret["error4"] == 1 || $xret["error5"] == 1){
                $xret["msg"] .= "</br>";
            }

            $xret["msg"] .= "<b>Warehouse Staff</b> Cannot Be Empty";
            $xret["status"] = 0;
            $xret["error6"] = 1;
        }
        
        
        if($xret["status"] == 1){
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_edit_hidden'];
            $arr_record['itmqty'] 	= $_POST['itmqty_edit'];
            $arr_record['stkqty'] 	= $_POST['itmqty_edit'];
            $arr_record['untprc'] 	= $_POST['price_edit'];
            $arr_record['extprc'] 	= $_POST['amount_edit'];
            $arr_record['warcde'] 	= $_POST['warcde_edit'];
            $arr_record['warehouse_floor_id'] = $_POST['warehouse_floor_id_edit'];
            $arr_record['warehouse_staff_id'] = $_POST['warehouse_staff_id_edit'];
    
            PDO_UpdateRecord($link,"tranfile2",$arr_record,"recid = ?",array($_POST['recid']));   
            $xret["msg"] = "submitEdit";
        }



    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        // delete			
        $delete_id=$_POST['recid'];
        $delete_query="DELETE  FROM  tranfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));

    
    }

    

    $xret["html"] .= "<tr style='font-weight:bold'>";
        $xret["html"] .= "<td>Item</td>";
        $xret["html"] .= "<td>Warehouse</td>";
        $xret["html"] .= "<td>Warehouse Floor</td>";
        $xret["html"] .= "<td style='text-align:right'>Quantity</td>";
        $xret["html"] .= "<td style='text-align:right'>Price</td>";
        $xret["html"] .= "<td style='text-align:right'>Amount</td>";
        $xret["html"] .= "<td class='text-center'>Action</td>";
    $xret["html"] .= "</tr>";  


            $select_salesfile2="SELECT itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.recid as tranfile2_recid,
            warehouse.warehouse_name, warehouse_floor.floor_no
            FROM tranfile2
            LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde
            LEFT JOIN warehouse ON warehouse.warcde = tranfile2.warcde
            LEFT JOIN warehouse_floor ON warehouse_floor.warehouse_floor_id = tranfile2.warehouse_floor_id
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
                    $xret["html_mobile"] .= "<td class='fw-bold'>Price</td>";
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
