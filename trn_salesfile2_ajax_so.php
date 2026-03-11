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


    if($_POST['event_action'] =='getData_add' || $_POST['event_action'] =='getData_edit'){


        $xret['html'] .= "<tr>
                    <td>
                        Doc. No
                    </td>

                    <td>
                        Tran. Date
                    </td>

                    <td>
                        Order By
                    </td>

                    <td>
                        Platform
                    </td>

                    <td>
                        Item
                    </td>

                    <td>
                        Total
                    </td>

                    <td style='text-align:right'>
                        Undelivered
                    </td>

                    <td class='text-center'>
                        Action
                    </td>
                </tr>";

        if($_POST['event_action'] =='getData_add'){
            $itmcde_search = $_POST['itmcde_add_hidden']; 
        } else if($_POST['event_action'] =='getData_edit'){
            $itmcde_search = $_POST['itmcde_edit_hidden']; 
        }

        $select_db_por = "SELECT *, salesorderfile2.docnum as 'po2_docnum',
            itemfile.itmdsc as 'po2_itmdsc',
            itemfile.itmcde as 'po2_itmcde',
            customerfile.cusdsc as 'po2_cusdsc',
            salesorderfile2.itmqty as 'po2_itmqty',
            salesorderfile1.trndte as 'po1_trndte',
            salesorderfile2.recid as 'po2_recid',
            mf_buyers.buyer_name as 'buyer_name',
            mf_buyers.buyer_id as 'buyer_id'
            FROM salesorderfile1 LEFT JOIN 
                salesorderfile2 ON 
                salesorderfile1.docnum = salesorderfile2.docnum 
            LEFT JOIN itemfile ON
                salesorderfile2.itmcde = itemfile.itmcde
            LEFT JOIN customerfile ON 
                salesorderfile1.cuscde = customerfile.cuscde
            LEFT JOIN mf_buyers ON
                salesorderfile1.buyer_id = mf_buyers.buyer_id
                WHERE salesorderfile2.itmcde='".$itmcde_search."'
                AND salesorderfile1.docnum NOT LIKE '%-BOM%' 
            ORDER BY salesorderfile1.trndte ASC, salesorderfile2.docnum ASC";
        $stmt_por	= $link->prepare($select_db_por);
        $stmt_por->execute();
        $xcount_por = 0;
        while($rs_por = $stmt_por->fetch()){

            $xcount_itm_tranfile2 = 0;
            $xcount_itm_chk = false;
            $select_db_por2 = "SELECT *, tranfile2.itmqty as 'tranfile2_itmqty' FROM tranfile2 LEFT JOIN
                            tranfile1 ON 
                            tranfile1.docnum = tranfile2.docnum 
                            WHERE tranfile2.so_recid='".$rs_por['po2_recid']."' AND tranfile2.itmcde='".$itmcde_search."'";
            $stmt_por2	= $link->prepare($select_db_por2);
            $stmt_por2->execute();
            while($rs_por2 = $stmt_por2->fetch()){
                $xcount_itm_tranfile2 += $rs_por2['tranfile2_itmqty'];
                $xcount_itm_chk = true;
            }


            if($xcount_itm_chk == true){

                //this is only for single matching
                if($_POST['event_action'] == 'getData_edit' && ($_POST['recid_so_hidden'] == $rs_por['po2_recid'])){
                    $undelivered = $rs_por['itmqty'] - $xcount_itm_tranfile2;
                }else{
                    continue;
                }

                //if you want multiple matching allow uncomment this
                // if($xcount_itm_tranfile2 >= $rs_por['itmqty']){
                //     if($_POST['event_action'] == 'getData_edit' && isset($_POST['selected_po']) && ($_POST['selected_po'] == $rs_por['po2_docnum'])){
                //         $undelivered = $rs_por['itmqty'] - $xcount_itm_tranfile2;
                //     }else{
                //         continue;
                //     }
                   
                // }else{
                //     $undelivered = $rs_por['itmqty'] - $xcount_itm_tranfile2;
                // }
            }else{
                $undelivered = $rs_por['po2_itmqty'];
            }

            $rs_por['po1_trndte'] = date("m-d-Y",strtotime($rs_por['po1_trndte']));
            $rs_por['po1_trndte'] = str_replace('-','/',$rs_por['po1_trndte']);
            $tr_class = '';
            $po_match = false;


            if(($_POST['orderby_1'] == $rs_por['buyer_id']) && $_POST['event_action'] == 'getData_add'){
                 $tr_class = 'table-info';
            }

            if($_POST['event_action'] == 'getData_edit'){

                // $select_db_chktf2 = "SELECT * FROM tranfile2 WHERE recid='".$_POST['tranfile2_recid_hidden']."' LIMIT 1";
                // $stmt_chktf2	= $link->prepare($select_db_chktf2);
                // $stmt_chktf2->execute();
                // $rs_chktf2 = $stmt_chktf2->fetch();

                // if(isset($_POST['selected_po']) && 
                // ($_POST['selected_po'] == $rs_por['po2_docnum']) && 
                // $itmcde_search == $rs_chktf2['itmcde']){
                //     $tr_class = 'table-success';
                //     $po_match = true;
                // }

                if($_POST['recid_so_hidden'] == $rs_por['po2_recid']){
                    $tr_class = 'table-success';
                    $po_match = true;
                }
        
            }else{

            }

            $xret['html'] .= "<tr class='".$tr_class."'>

                <td>
                    ".$rs_por['docnum']."
                </td>

                <td>
                    ".$rs_por['po1_trndte']."
                </td>

                <td>
                    ".$rs_por['buyer_name']."
                </td>

                <td>
                    ".$rs_por['po2_cusdsc']."
                </td>

                <td>
                    ".htmlspecialchars($rs_por['po2_itmdsc'],ENT_QUOTES)."
                </td>

                <td style='text-align:right'>
                    ".$rs_por['po2_itmqty']."
                </td>

                <td style='text-align:right'>
                    ".$undelivered."
                </td> ";  

            if($_POST['event_action'] == 'getData_edit'){

                if($po_match == true){
                    $xret['html'] .= "<td class='text-center'>
                            <button type='button' class='btn btn-danger fw-bold' onclick='search_so_edit(\"deSelectData_edit\",\"".$rs_por['docnum']."\",\"".$rs_por['po2_recid']."\")'>Deselect</button>
                        </td> 
                    </tr>";
                }else{
                    $xret['html'] .= "<td class='text-center'>
                            <button type='button' class='btn btn-success fw-bold' onclick='search_so_edit(\"selectData_edit\",\"".$rs_por['docnum']."\",\"".$rs_por['po2_recid']."\")'>Select</button>
                        </td> 
                    </tr>";
                }

            } else if($_POST['event_action'] == 'getData_add'){
                $xret['html'] .= "<td class='text-center'>
                        <button type='button' class='btn btn-success fw-bold' onclick='search_so(\"selectData_add\",\"".$rs_por['docnum']."\",\"".$rs_por['po2_recid']."\")'>Select</button>
                    </td> 
                </tr>";
            }


            if($xcount_por == 0){
                $xret['html_mobile'] .= "

                <tr class='table-dark'>
                    <td>
                        Platform
                    </td>
                    <td class='fw-bold'>
                        ".$rs_por['po2_cusdsc']."
                    </td>
                </tr>

                <tr class='table-dark'>
                    <td>
                        Item
                    </td>

                    <td class='fw-bold'>
                        ".$rs_por['po2_itmdsc']."
                    </td>
                </tr> ";
            }
            //mobile viewing
 
            
                $xret['html_mobile'] .= "<tr class='".$tr_class."'>

                    <td>
                        Doc. No.
                    </td>

                    <td>
                        ".$rs_por['docnum']."
                    </td>
                     
                </tr>

                <tr class='".$tr_class."'>
                    <td>
                        Tran. Date.
                    </td>
                    <td>
                        ".$rs_por['po1_trndte']."
                    </td>    
                </tr>

                <tr class='".$tr_class."'>
                    <td>
                        Ordered By
                    </td>
                    <td>
                        ".$rs_por['buyer_name']."
                    </td>    
                </tr>



                <tr class='".$tr_class."'>
                    <td>
                        Total
                    </td>
                    <td>
                        ".$rs_por['po2_itmqty']."
                    </td>    
                </tr>

                <tr class='".$tr_class."'>
                    <td>
                        Undelivered
                    </td>
                    <td>
                        ".$undelivered."
                    </td>  
                </tr>";


            if($_POST['event_action'] == 'getData_edit'){

                if($po_match == true){
                    $xret['html_mobile'] .= "
                    <tr class='".$tr_class."'>
                        <td>
                            Action
                        </td>
                    
                        <td class='text-center'>
                            <button type='button' class='btn btn-danger fw-bold' onclick='search_so_edit(\"deSelectData_edit\",\"".$rs_por['docnum']."\",\"".$rs_por['po2_recid']."\")'>Deselect</button>
                        </td> 
                    </tr>";
                }else{
                    $xret['html_mobile'] .= "
                    <tr class='".$tr_class."'>
                        <td>
                            Action
                        </td>
                        <td class='text-center'>
                            <button type='button' class='btn btn-success fw-bold' onclick='search_so_edit(\"selectData_edit\",\"".$rs_por['docnum']."\",\"".$rs_por['po2_recid']."\")'>Select</button>
                        </td> 
                    </tr>";
                }

            } else if($_POST['event_action'] == 'getData_add'){
                $xret['html_mobile'] .= "
                    <tr class='".$tr_class."'>                
                        <td>
                            Action
                        </td>

                        <td class='text-center'>
                            <button type='button' class='btn btn-success fw-bold' onclick='search_so(\"selectData_add\",\"".$rs_por['docnum']."\",\"".$rs_por['po2_recid']."\")'>Select</button>
                        </td> 
                    </tr>";
            }           



                
            $xcount_por++; 
        }        
    }




 

echo json_encode($xret);
?>