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
    $xret["trntot"] = "";
    $xret["msg"] = "";
    $xret["status"] = 1;
    $xret["retEdit"] = array();

    $trncde = $_POST["trncde"];
    $trncde = 'SRT';
    
    $docnum = $_POST['docnum'];

    if(!isset($_POST['payment_details_1']) && empty($_POST['payment_details_1'])){
        $_POST['payment_details_1'] = '';
    }else{
        $_POST['payment_details_1'] = $_POST['payment_details_1'];
    }

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
                //$arr_record['orderby'] 	= $_POST['orderby_1'];
                $arr_record['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record['shipto'] 	= $_POST['shipto_1'];
                $arr_record['cuscde'] 	= $_POST['cusname_1'];
                $arr_record['trntot'] 	= $_POST['trntot_1'];
                $arr_record['trndte'] 	= $_POST['trndte_1'];
                $arr_record['paydate'] 	= $_POST['paydate_1'];
                $arr_record['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record['remarks'] 	= $_POST['remarks_1'];
                
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
    
                //$arr_record_update['orderby'] 	= $_POST['orderby_1'];
                $arr_record_update['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record_update['shipto'] 	= $_POST['shipto_1'];
                $arr_record_update['cuscde'] 	= $_POST['cusname_1'];
                $_POST['trntot_1'] = str_replace(",","",$_POST['trntot_1']);
                $arr_record_update['trntot'] 	= $_POST['trntot_1'];
                $arr_record_update['trndte'] 	= $_POST['trndte_1'];
                $arr_record_update['paydate'] 	= $_POST['paydate_1'];
                $arr_record_update['paydetails'] 	= $_POST['payment_details_1'];
                $arr_record_update['remarks'] 	= $_POST['remarks_1'];
                $arr_record_update['ordernum'] 	= $_POST['ordernum_1'];
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

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "insert"){

        $select_check="SELECT * FROM tranfile1 WHERE docnum=?";
        $stmt_check	= $link->prepare($select_check);
        $stmt_check->execute(array($_POST["docnum"]));
        $rs_check = $stmt_check->fetch();

        if(empty($_POST['trndte_1'])){
            $xret["status"] = 0;
            $xret["msg"] = "<b>Tran. Date</b> cannot be empty.";
        }else{
            if(empty($rs_check)){
                $_POST['trndte_1']  = (empty($_POST['trndte_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['trndte_1']));
                $_POST['paydate_1']  = (empty($_POST['paydate_1'])) ? NULL :  date("Y-m-d", strtotime($_POST['paydate_1']));
    
                $arr_record_file1 = array();
                $arr_record_file1['docnum'] 	= $_POST["docnum"];
                //$arr_record_file1['orderby'] 	= $_POST['orderby_1'];
                $arr_record_file1['buyer_id'] 	= $_POST['orderby_1'];
                $arr_record_file1['shipto'] 	= $_POST['shipto_1'];
                $arr_record_file1['cuscde'] 	= $_POST['cusname_1'];
                $arr_record_file1['trntot'] 	= $_POST['trntot_1'];
                $arr_record_file1['trndte'] 	= $_POST['trndte_1'];
                $arr_record_file1['paydate'] 	= $_POST['paydate_1'];
                $arr_record_file1['paydetails'] = $_POST['payment_details_1'];
                $arr_record_file1['remarks'] 	= $_POST['remarks_1'];
                $arr_record_file1['ordernum'] 	= $_POST['ordernum_1'];
                $arr_record_file1['trncde']     = $trncde;
    
                PDO_InsertRecord($link,'tranfile1',$arr_record_file1, false);
    
                $xret["msg"] = "insert_new";
            }else{
                $xret["msg"] = "insert_old";
            }
    
            $arr_record = array();
            $arr_record['docnum'] 	= $_POST['docnum'];
            $arr_record['itmcde'] 	= $_POST['itmcde_add'];
            $arr_record['itmqty'] 	= $_POST['itmqty_add'];
            $arr_record['stkqty'] 	= $_POST['itmqty_add'];
            $arr_record['untprc'] 	= $_POST['price_add'];
            $arr_record['extprc'] 	= $_POST['amount_add'];
            $arr_record_file1['trncde']     = $trncde;
    
            PDO_InsertRecord($link,'tranfile2',$arr_record, false);
        }


    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "getEdit"){

        $select_tranfile2="SELECT tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, itemfile.itmdsc, tranfile2.recid as tranfile2_recid FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE tranfile2.recid=?";
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
            "itmdsc" =>  $rs_tranfile2["itmdsc"],
            "itmqty" =>  $rs_tranfile2["itmqty"],
            "untprc" =>  $rs_tranfile2["untprc"],
            "extprc" =>  $rs_tranfile2["extprc"],
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

        $arr_record = array();
        $arr_record['docnum'] 	= $_POST['docnum'];
        $arr_record['itmcde'] 	= $_POST['itmcde_edit'];
        $arr_record['itmqty'] 	= $_POST['itmqty_edit'];
        $arr_record['stkqty'] 	= $_POST['itmqty_edit'];
        $arr_record['untprc'] 	= $_POST['price_edit'];
        $arr_record['extprc'] 	= $_POST['amount_edit'];

        PDO_UpdateRecord($link,"tranfile2",$arr_record,"recid = ?",array($_POST['recid']));   
        $xret["msg"] = "submitEdit";
    }

    if(isset($_POST["event_action"]) && $_POST["event_action"] == "delete"){

        // delete			
        $delete_id=$_POST['recid'];
        $delete_query="DELETE  FROM  tranfile2 WHERE recid=?";
        $xstmt=$link->prepare($delete_query);
        $xstmt->execute(array($delete_id));

    
    }

    

    $xret["html"] .="
    <div class='w-100 d-flex justify-content-center'>
        <div style='width:90%'>
            <button type='button' class='btn btn-success my-2' value='Add Record' onclick='salesfile2(\"open_add\")'>
                <span style='font-weight:bold'>
                    Add Record
                </span>
                <i class='fas fa-plus' style='margin-left: 3px'></i>
            </button>
        </div>
    </div>";

    $xret["html"] .="<div class='w-100 d-flex justify-content-center'>";
        $xret["html"] .= "<table class='table table-striped' style='width:90%'>";
            $xret["html"] .= "<tr style='font-weight:bold'>";
                $xret["html"] .= "<td>Item</td>";
                $xret["html"] .= "<td>Quantity</td>";
                $xret["html"] .= "<td style='text-align:right'>Price</td>";
                $xret["html"] .= "<td style='text-align:right'>Amount</td>";
                $xret["html"] .= "<td class='text-center'>Action</td>";
            $xret["html"] .= "</tr>";  

            $select_salesfile2="SELECT itemfile.itmdsc as itemfile_itmdsc,itemfile.itmcde as itemfile_itmcde, tranfile2.itmqty, tranfile2.untprc, tranfile2.extprc, tranfile2.recid as tranfile2_recid
            FROM tranfile2 LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde WHERE docnum=?";
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
                    $xret["html"] .= "<td>".$rs_salesfile2['itemfile_itmdsc']."</td>";
                    $xret["html"] .= "<td>".$rs_salesfile2['itmqty']."</td>";
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
            }

        $xret["html"] .= "</table>";
    $xret["html"] .= "</div>";

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