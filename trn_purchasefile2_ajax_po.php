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
    $xret["purchasesDefault"] = array();

    if($_POST['event_action'] =='getData_add' || $_POST['event_action'] =='getData_edit' || $_POST['event_action'] == 'selectData_add'){

        $xret['html'] .= "<tr>
                    <td>
                        Doc. No
                    </td>

                    <td>
                        Tran. Date
                    </td>

                    <td>
                        Supplier
                    </td>

                    <td>
                        Item
                    </td>

                    <td style='text-align:right'>
                        Total
                    </td>
                    
                    <td class='text-center'>
                        Action
                    </td>
                </tr>";

        if($_POST['event_action'] =='getData_add' || $_POST['event_action'] =='selectData_add'){
            $itmcde_search = $_POST['itmcde_add_hidden']; 
        } else if($_POST['event_action'] =='getData_edit'){
            $itmcde_search = $_POST['itmcde_edit_hidden']; 
        }

        $select_db_por = "SELECT *, purchasesorderfile2.docnum as 'po2_docnum',
            itemfile.itmdsc as 'po2_itmdsc',
            itemfile.itmcde as 'po2_itmcde',
            supplierfile.suppdsc as 'po2_suppdsc',
            purchasesorderfile2.itmqty as 'po2_itmqty',
            purchasesorderfile1.trndte as 'po1_trndte',
            purchasesorderfile2.recid as 'po2_recid'
            FROM purchasesorderfile1 LEFT JOIN 
                purchasesorderfile2 ON 
                purchasesorderfile1.docnum = purchasesorderfile2.docnum 
            LEFT JOIN itemfile ON
                purchasesorderfile2.itmcde = itemfile.itmcde
            LEFT JOIN supplierfile ON 
                purchasesorderfile1.suppcde = supplierfile.suppcde
                WHERE purchasesorderfile2.itmcde='".$itmcde_search."'
                AND purchasesorderfile1.suppcde = '".$_POST['suppcde']."'
            ORDER BY purchasesorderfile1.trndte ASC, purchasesorderfile2.docnum ASC";
        $stmt_por	= $link->prepare($select_db_por);
        $stmt_por->execute();
        $xcount_por = 0;
        $xtotal_multi_itm_chk = 0;
        $xret["multi_chk_recid"] = array();
        while($rs_por = $stmt_por->fetch()){
            $xchecked_event = '';
            // $chk_disabled_multi_itm = '';
            $xcheck_multi_itm = '';
            // $xcheck_action_disabledchk = true;
            $xret["multi_chk_recid"][$rs_por['po2_recid']] = '';

            if(isset($_POST['xcheck_action']) && $_POST['xcheck_action'] == 'check'){
                if($rs_por['po2_recid'] == $_POST['recid_po_hidden']){
                    $xchecked_event = 'checked';
                    $xtotal_multi_itm_chk += $rs_por['po2_itmqty'];
                    // $xcheck_action_disabledchk = false;
                }
            }else if(isset($_POST['xcheck_action']) && $_POST['xcheck_action'] == 'uncheck'){
                if($rs_por['po2_recid'] == $_POST['recid_po_hidden']){
                    $xchecked_event = '';
                    $xtotal_multi_itm_chk -= $rs_por['po2_itmqty'];
                    // $xcheck_action_disabledchk = false;
                }
            }            

            if(isset($_POST['multi_itm_select']) && !empty($_POST['multi_itm_select'])){
                $destructure_multi_itm = explode(",", $_POST['multi_itm_select']);
                $isPresent = in_array($rs_por["po2_recid"], $destructure_multi_itm);
                if($isPresent){
                    $xcheck_multi_itm = 'checked';
                    $xtotal_multi_itm_chk += $rs_por['po2_itmqty'];
                }
            }

            if(!isset($_POST['itmqty'])){
                $_POST['itmqty'] = 0;
            }

            $xtotal_multi_itm_chk_diff  = (int)$_POST['itmqty'] - $xtotal_multi_itm_chk;

            if(($xtotal_multi_itm_chk >= $_POST['itmqty']) && $_POST['itmqty'] != 0){
                
                //$chk_disabled_multi_itm = 'disabled';

                foreach ($xret["multi_chk_recid"] as $key => &$value) {

                    // if ($key != $rs_por['po2_recid']) {
                    //     $xret["multi_chk_recid"][$key] = 'disabled';
                    // }
                    
                    if(isset($_POST['xcheck_action']) && ($_POST['xcheck_action'] == 'check' || $_POST['xcheck_action'] == 'uncheck')){
                        if($key == $_POST['recid_po_hidden']){
                            $xret["multi_chk_recid"][$key] = '';
                        }
                    }                    

                }                
            }      
                
            $xcount_por++; 
        }
        
        $select_db_por2 = "SELECT *, purchasesorderfile2.docnum as 'po2_docnum',
            purchasesorderfile2.tranfile2_recid as 'tranfile2_recid',
            itemfile.itmdsc as 'po2_itmdsc',
            purchasesorderfile2.untprc as 'po2_untprc',
            itemfile.itmcde as 'po2_itmcde',
            supplierfile.suppdsc as 'po2_suppdsc',
            purchasesorderfile2.itmqty as 'po2_itmqty',
            purchasesorderfile1.trndte as 'po1_trndte',
            purchasesorderfile2.recid as 'po2_recid'
            FROM purchasesorderfile1 LEFT JOIN 
                purchasesorderfile2 ON 
                purchasesorderfile1.docnum = purchasesorderfile2.docnum 
            LEFT JOIN itemfile ON
                purchasesorderfile2.itmcde = itemfile.itmcde
            LEFT JOIN supplierfile ON 
                purchasesorderfile1.suppcde = supplierfile.suppcde
                WHERE purchasesorderfile2.itmcde='".$itmcde_search."'
                AND purchasesorderfile1.suppcde = '".$_POST['suppcde']."'
            ORDER BY purchasesorderfile1.trndte ASC, purchasesorderfile2.docnum ASC";
        $stmt_por2	= $link->prepare($select_db_por2);
        $stmt_por2->execute();
        $xcount_por2 = 0;
        $xtotal_multi_itm_chk2 = 0;
        while($rs_por2 = $stmt_por2->fetch()){
            $xchecked_event2 = '';
            // $chk_disabled_multi_itm2 = '';
            $xcheck_multi_itm2 = '';
            $xcount_itm_tranfile2 = 0;
            $xcount_itm_chk2 = false;
            // $xcheck_action_disabledchk2 = true;

            // $select_db_por3 = "SELECT *, tranfile2.itmqty as 'tranfile2_itmqty' FROM tranfile2 LEFT JOIN
            //                 tranfile1 ON 
            //                 tranfile1.docnum = tranfile2.docnum 
            //                 WHERE tranfile2.purnum_recid='".$rs_por2['po2_recid']."' AND tranfile2.itmcde='".$itmcde_search."'";

            if(isset($rs_por2['tranfile2_recid']) && $rs_por2['tranfile2_recid'] != '0' && !empty($rs_por2['tranfile2_recid'])){

                if(isset($_POST['multi_select_original'])){
                    $selectedItems = explode(',', $_POST['multi_select_original']);
                }else{
                    $selectedItems = array();
                }

                if(($_POST['event_action'] == 'selectData_add' ||  $_POST['event_action'] == 'getData_add') || 
                ($_POST['event_action'] == 'getData_edit' && !in_array($rs_por2['po2_recid'], $selectedItems))){
                    continue;
                }

            }

            $rs_por2['po1_trndte'] = date("m-d-Y",strtotime($rs_por2['po1_trndte']));
            $rs_por2['po1_trndte'] = str_replace('-','/',$rs_por2['po1_trndte']);
            $tr_class2 = '';
            $po_match2 = false;

            if($_POST['event_action'] == 'getData_edit'){

                if($_POST['recid_po_hidden'] == $rs_por2['po2_recid']){
                    $tr_class2 = 'table-success';
                    $po_match2 = true;
                }
        
            }

            if(isset($_POST['xcheck_action']) && $_POST['xcheck_action'] == 'check'){
                if($rs_por2['po2_recid'] == $_POST['recid_po_hidden']){
                    $xchecked_event2 = 'checked';
                    $xtotal_multi_itm_chk2 += $rs_por2['po2_itmqty'];
                    // $xcheck_action_disabledchk2= false;
                }
            }else if(isset($_POST['xcheck_action']) && $_POST['xcheck_action'] == 'uncheck'){
                if($rs_por2['po2_recid'] == $_POST['recid_po_hidden']){
                    $xchecked_event2 = '';
                    $xtotal_multi_itm_chk2 -= $rs_por['po2_itmqty'];
                    // $xcheck_action_disabledchk2 = false;
                }
            }            

            if(isset($_POST['multi_itm_select']) && !empty($_POST['multi_itm_select'])){
                $destructure_multi_itm2 = explode(",", $_POST['multi_itm_select']);
                $isPresent2 = in_array($rs_por2["po2_recid"], $destructure_multi_itm2);
                if($isPresent2){
                    $xcheck_multi_itm2 = 'checked';
                    $xtotal_multi_itm_chk2 += $rs_por2['po2_itmqty'];
                }
            }

            if(!isset($_POST['itmqty'])){
                $_POST['itmqty'] = 0;
            }

            $xtotal_multi_itm_chk_diff2  = (int)$_POST['itmqty'] - $xtotal_multi_itm_chk2;

            // if(($_POST['itmqty'] >= $xtotal_multi_itm_chk) && $_POST['itmqty'] != 0 && ($xcheck_action_disabledchk2 == true)){
            //     $chk_disabled_multi_itm2 = 'disabled';               
            // }

            if(isset($xret["multi_chk_recid"][$rs_por2['po2_recid']])){
                if($xret["multi_chk_recid"][$rs_por2['po2_recid']] == 'disabled'){
                    // $xret["multi_chk_recid"][$rs_por2['po2_recid']] = 'disabled';
                }else{
                    $xret["multi_chk_recid"][$rs_por2['po2_recid']] = '';
                }
                 
            }else{
                $xret["multi_chk_recid"][$rs_por2['po2_recid']] = '';
            }

            if(isset($_POST['xcheck_action']) && ($_POST['xcheck_action'] == 'uncheck' || $_POST['xcheck_action'] == 'check')){
                if($rs_por2['po2_recid'] == $_POST['recid_po_hidden']){
                    if($xchecked_event2 == ''){
                        $xcheck_multi_itm2 = '';
                    }
        
                    if($xchecked_event2 == 'checked'){
                        $xcheck_multi_itm2 = 'checked';
                    }
                }
            }            

            $xret['html'] .= "<tr>

                <td>
                    ".$rs_por2['docnum']."
                </td>

                <td>
                    ".$rs_por2['po1_trndte']."
                </td>

                <td>
                    ".$rs_por2['po2_suppdsc']."
                </td>

                <td>
                    ".htmlspecialchars($rs_por2['po2_itmdsc'],ENT_QUOTES)."
                </td>

                <td style='text-align:right'>
                    ".$rs_por2['po2_itmqty']."
                </td>";

            if($_POST['event_action'] == 'getData_edit' || $_POST['event_action'] == 'selectData_edit'){
          
                    $xret['html'] .= "<td class='text-center'>
                            <div class='form-check'>
                                <input ".$xchecked_event2." ".$xcheck_multi_itm2." class='form-check-input' type='checkbox' onChange='select_multi_itm(\"selectData_edit\",\"".$rs_por2['docnum']."\",\"".$rs_por2['po2_recid']."\",this,\"".$rs_por2['po2_untprc']."\")' value='' id='flexCheckDefault' >
                            </div>
                        </td> 
                    </tr>";
                    
            } else if($_POST['event_action'] == 'getData_add' || $_POST['event_action'] == 'selectData_add'){
                $xret['html'] .= "<td class='text-center'>
                            <div class='form-check d-flex justify-content-center'>
                
                                <input ".$xchecked_event2." ".$xcheck_multi_itm2." class='form-check-input' type='checkbox' onChange='select_multi_itm(\"selectData_add\",\"".$rs_por2['docnum']."\",\"".$rs_por2['po2_recid']."\",this,\"".$rs_por2['po2_untprc']."\")' value='' id='flexCheckDefault' >
                            </div>
                        </td> 
                    </tr>";
            }

            if($xcount_por2 == 0){
                $xret['html_mobile'] .= "

                <tr class='table-dark'>
                    <td>
                        Supplier
                    </td>
                    <td class='fw-bold'>
                        ".$rs_por2['po2_suppdsc']."
                    </td>
                </tr>

                <tr class='table-dark'>
                    <td>
                        Item
                    </td>

                    <td class='fw-bold'>
                        ".htmlspecialchars($rs_por2['po2_itmdsc'],ENT_QUOTES)."
                    </td>
                </tr> ";
            }
            //mobile viewing
                $xret['html_mobile'] .= "<tr>

                    <td>
                        Doc. No.
                    </td>

                    <td>
                        ".$rs_por2['docnum']."
                    </td>
                </tr>

                <tr>
                    <td>
                        Tran. Date.
                    </td>
                    <td>
                        ".$rs_por2['po1_trndte']."
                    </td>    
                </tr>

                <tr>
                    <td>
                        Total
                    </td>
                    <td>
                        ".$rs_por2['po2_itmqty']."
                    </td>    
                </tr>";

            //uncommented but this is for mobile viewing
            if($_POST['event_action'] == 'getData_edit' || $_POST['event_action'] == 'selectData_edit'){

                $xret['html_mobile'] .= "
                <tr>
                    <td>
                        Action
                    </td>
                
                    <td class='text-right justify-content-end'>
                        <div class='form-check d-flex justify-content-center'>
                            <input ".$xchecked_event2." ".$xcheck_multi_itm2." class='form-check-input' type='checkbox' onChange='select_multi_itm(\"selectData_edit\",\"".$rs_por2['docnum']."\",\"".$rs_por2['po2_recid']."\",this,\"".$rs_por2['po2_untprc']."\")' value='' id='flexCheckDefault' >
                        </div>
                    </td> 
                </tr>";              

            } else if($_POST['event_action'] == 'getData_add' || $_POST['event_action'] == 'selectData_add'){
                $xret['html_mobile'] .= "
                    <tr>                
                        <td>
                            Action
                        </td>
                        <td class='text-center d-flex justify-content-end'>
                            <div class='form-check d-flex justify-content-center'>
                                <input ".$xchecked_event2." ".$xcheck_multi_itm2." class='form-check-input' type='checkbox' onChange='select_multi_itm(\"selectData_add\",\"".$rs_por2['docnum']."\",\"".$rs_por2['po2_recid']."\",this,\"".$rs_por2['po2_untprc']."\")' value='' id='flexCheckDefault' >
                            </div>
                        </td> 
                    </tr>";
            }           

                
            $xcount_por2++; 
        }           
    }




 

echo json_encode($xret);
?>