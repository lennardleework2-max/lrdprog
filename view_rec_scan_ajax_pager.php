<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require "resources/db_init.php";
require "resources/connect4.php";
require_once("resources/lx2.pdodb.php");
require "resources/stdfunc100.php";

$xret = array();
$xret["status"] = 1;
$xret["msg"] = "";
$xret["html"] = "";
$xret["html_mobile"] = "";
$xret["retEdit"] = array();
$xret["noMatchFile2"] = [];
$xret["bill_number_hidden"] = '';
$xret["total_amount_hidden"] = '';
$xret["date_uploaded_hidden"] = '';
$xret['noMatchFile2'] = array();

$xfilter = "";
$search = false;
$xorder = "";

    if(isset($_POST['event_action']) && $_POST['event_action'] == 'matchSalesret'){

        //ALSO THIS WILL BE DONE IN MATCHING IN  
        $select_db_jnt_salesret="SELECT * FROM jnt_salesreturnfile WHERE batch_num=?";
        $stmt_jnt_salesret	= $link->prepare($select_db_jnt_salesret);
        $stmt_jnt_salesret->execute(array($_POST['batch_no']));
    
        $currentDate = date('Y-m-d');

        while($rs_jnt_salesret = $stmt_jnt_salesret->fetch()){

            $srt_waybill_number = 'SRT-'.$rs_jnt_salesret['waybill_number'];

            $select_db_jnt_tranfile1 = "SELECT * FROM tranfile1 WHERE waybill_number=? AND trncde='SRT' ";
            $stmt_jnt_tranfile1	= $link->prepare($select_db_jnt_tranfile1);
            $stmt_jnt_tranfile1->execute(array($srt_waybill_number));
            $rs_jnt_tranfile1 = $stmt_jnt_tranfile1->fetch();
        

            $waybillNumberReturn = $rs_jnt_salesret['waybill_number'];

            if(empty($rs_jnt_tranfile1)){
                $xret['noMatchFile2'][(string)$waybillNumberReturn] = true;
            }else{                

                $arr_jntsalesreturnfile = array();
                $arr_jntsalesreturnfile['usr_scanned'] = 'true';    
                $arr_jntsalesreturnfile['usr_date_scanned'] = $rs_jnt_tranfile1['trndte'];  
                $arr_jntsalesreturnfile['date_matched'] = $currentDate;    
                $arr_jntsalesreturnfile['matched_status'] = $rs_jnt_tranfile1['docnum'];   
                PDO_UpdateRecord($link,'jnt_salesreturnfile',$arr_jntsalesreturnfile,"recid = ?",array($rs_jnt_salesret['recid']),false);
                
                $xret['noMatchFile2'][(string)$waybillNumberReturn] = false;
                $xret['status'] = 0;
            }

        };

        header('Content-Type: application/json');
        echo json_encode($xret);
        return;
    

    }

    if(isset($_POST["first_load"]) && $_POST["first_load"]!== "first_load"){

        // if(isset($_POST['payment_status_search_h']) && !empty($_POST['payment_status_search_h'])){
        //     $xfilter = " AND billpaymentfile1.payment_status='".$_POST['payment_status_search_h']."'";
        //     $search = true;
        // }

        if(isset($_POST['order_status_search_h']) && !empty($_POST['order_status_search_h'])){

            if($_POST['order_status_search_h'] == '(All Matched)'){
                $xfilter .= " AND trn_waybill_orders.order_status LIKE '%SRT%'";
            }else{
                $xfilter .= " AND trn_waybill_orders.order_status = '".$_POST['order_status_search_h']."'";
            }
            
            $search = true;
        }

        if(isset($_POST['waybill_number_search_h']) && !empty($_POST['waybill_number_search_h'])){

            $xfilter .= " AND trn_waybill_orders.waybill_number LIKE '%".$_POST['waybill_number_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['submission_date_search_h']) && !empty($_POST['submission_date_search_h'])){

            $_POST['submission_date_search_h'] = date("Y-m-d", strtotime($_POST['submission_date_search_h']));

            $xfilter .= " AND DATE(trn_waybill_orders.submission_time)='".$_POST['submission_date_search_h']."'";
            $search = true;
        }

        if(isset($_POST['cod_search_h']) && !empty($_POST['cod_search_h'])){

            $_POST['cod_search_h'] = str_replace(',', '', $_POST['cod_search_h']);

            $xfilter .= " AND trn_waybill_orders.cod='".$_POST['cod_search_h']."'";
            $search = true;
        }

        if(isset($_POST['remarks_search_h']) && !empty($_POST['remarks_search_h'])){

            $xfilter .= " AND trn_waybill_orders.remarks LIKE '%".$_POST['remarks_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['receiver_search_h']) && !empty($_POST['receiver_search_h'])){

            $xfilter .= " AND trn_waybill_orders.receiver LIKE '%".$_POST['receiver_search_h']."%'";
            $search = true;
        }

        if(($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])) && ($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h']))){
            $xfilter.=" ORDER BY trn_waybill_orders.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h'].", trn_waybill_orders.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }

        else if($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])){
            $xfilter.=" ORDER BY trn_waybill_orders.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h']."";
            $search = true;
        }
        else if($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h'])){
            $xfilter.=" ORDER BY trn_waybill_orders.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }
    }

    if($search == false){
        $xorder =" ORDER BY trn_waybill_orders.signing_time DESC";
    }

$select_db_xtotal = "SELECT COUNT(*) as rec_count FROM trn_waybill_orders WHERE true ".$xfilter;
$stmt_xtotal	= $link->prepare($select_db_xtotal);
$xret["sql"] = $select_db_xtotal;
$stmt_xtotal->execute();
$rs_xtotal = $stmt_xtotal->fetch();
$xret["sql"] = $select_db_xtotal;

//INITIALIZE PAGE NO.
$xpageno=$_POST['pageno'];

//RETURN TOTAL RECORDS
$xtotalrec=$rs_xtotal['rec_count'];
$xret['totalrec']=$xtotalrec;

$xlimit = 20;
//CALCULATE MAXPAGE
$maxpage = ceil($xtotalrec / $xlimit);
//RETURN MAXPAGE
$xret["maxpage"] = $maxpage;

if ($xtotalrec==0)
{
    $xret["html"] = "<tr><td colspan='3' class='text-center display-5 w-100' style='padding-left:0px !important;background-color: rgba(0, 0, 0, 0.05);'> NO RECORDS<i class='fas fa-search display-6 mx-2'></i></td></tr>";
    $xret["maxpage"]=0;
    $xret["xpageno"] ='';
    echo json_encode($xret);
    return;
}

//CALCULATE OFFSET
if($xpageno == 0 || $xpageno == 1 || empty($xpageno) || $_POST["event_action"] == "search"){
    $xpageno = 1;
    $xoffset = 0;
}
// if($xpageno == 0){
//     $xpageno = 1;p
//     $xoffset = 0;
// }
if($_POST["event_action"] == "next_p"){
    if($xpageno == $maxpage){
        //nothing changes
    }else{
        $xpageno++;
    }
    $xoffset =  ($xpageno * $xlimit) - $xlimit ;

}
else if($_POST["event_action"] == "previous_p"){
    if($xpageno==1){
        $xoffset = 0;
    }else{
        $xpageno--;
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }
}
else if($_POST["event_action"] == "first_p"){
    $xpageno=1;
    $xoffset = 0;
}
else if($_POST["event_action"] == "last_p"){
    $xpageno = $maxpage;
    $xoffset =  ($xpageno * $xlimit) - $xlimit ;
}
else if($_POST["event_action"] =="same"){
    if($xpageno > $maxpage){
        $xpageno = $maxpage;
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }else{
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }
}

$xret["html"] .= "<tr style='height:40px' class='tr_striped'>
                    <td class='text-start fw-bold ps-3' style='border-top-left-radius:7.5px'>
                        Waybill Number    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        Receiver    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        COD    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        Remarks    
                    </td>
                    
                    <td class='text-start fw-bold ps-3'>
                        Submission Date    
                    </td>

                    <td class='text-start fw-bold ps-3' style='border-top-right-radius:7.5px'>
                        Order Status    
                    </td>
                  </tr>";

//RETURN PAGE NO
$xret["xpageno"] = $xpageno;
$select_db="SELECT * FROM trn_waybill_orders WHERE true ".$xfilter."  ".$xorder."  LIMIT ".$xlimit." OFFSET ".$xoffset;
$xret["sql"] = $select_db;
$stmt	= $link->prepare($select_db);
$stmt->execute();
$xcheck=0;

while($row_main = $stmt->fetch()){

    $xtop_border_action = '';
    if($xcheck !== 0){
        $xtop_border_action = 'border-top:2px solid gray;';
    }

    // if(!empty($row_main['date_uploaded'])){
    //     $row_main['date_uploaded'] = date("m-d-Y",strtotime($row_main['date_uploaded']));
    //     $row_main['date_uploaded'] = str_replace('-','/',$row_main['date_uploaded']);
    // }else{
    //     $row_main['date_uploaded'] = '&nbsp;';
    // }    

    if(!empty($row_main['submission_time'])){
        $row_main['submission_time'] = DateTime::createFromFormat('Y-m-d H:i:s', $row_main['submission_time'])->format('m/d/Y');
    }else{
        $row_main['submission_time'] = '&nbsp;';
    }

        $xret["html"] .= "
                    <tr class='tr_striped'>
                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['waybill_number']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['receiver']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['cod']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['remarks']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['submission_time']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['order_status']."  
                        </td>";                 

        // if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){
        //     $xret["html"].= "<td class='text-center align-middle py-2' data-label='Action'>";
        //         $xret["html"].= "<div class='dropdown'>";
        //             $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:16px'>";
        //                 $xret["html"].= "Action";
        //             $xret["html"].= "</button>";

        //             $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

        //             if($_SESSION['edit_crud'] == 1){
        //                 $xret["html"].= "<li onclick=\"viewDetailed('".$row_main['waybill_number']."')\">";
        //                     $xret["html"].= "<a class='dropdown-item dd_action' style='color:#8c1aff;font-weight:bold;'><i class='far fa-eye'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>View</span></a>";
        //                 $xret["html"].= "</li>";
        //             }

        //             // $xret["html"].= "<li onclick=\"ajaxFunc2('matchSalesret' , '".$row_main['recid']."', '".$row_main['waybill_number']."')\">";
        //             //     $xret["html"].= "<a class='dropdown-item dd_action' style='color: #00b3b3;font-weight:bold;'><i class='fas fa-exchange-alt'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Match With Sales Return Upload</span></a>";
        //             // $xret["html"].= "</li>";

        //             // $xret["html"].= "<li onclick=\"print_complete_pdf('".$row_main['waybill_number']."')\">";
        //             //     $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff8000;font-weight:bold;'><i class='fas fa-file-pdf'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Print PDF</span></a>";
        //             // $xret["html"].= "</li>";
                    
        //             if($_SESSION['delete_crud'] == 1){
     
        //                 // $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['recid']."')\">";
        //                 //     $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Delete</span></a>";
        //                 // $xret["html"].= "</li>";
                                        
        //             }
        //             $xret["html"].= "</ul>";
        //         $xret["html"].= "</div>";
        //     $xret["html"].= "</td>";
        // }

    $xret["html"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr style='width:100%'>";
        $xret["html_mobile"] .= "<td style='font-weight:bold;width:100%'>Waybill Number</td>";
        $xret["html_mobile"] .= "<td style='width:100%'>".$row_main['waybill_number']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr style='width:100%'>";
        $xret["html_mobile"] .= "<td style='font-weight:bold;width:100%'>Receiver</td>";
        $xret["html_mobile"] .= "<td style='width:100%'>".$row_main['receiver']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr style='width:100%'>";
        $xret["html_mobile"] .= "<td style='font-weight:bold;width:100%'>COD</td>";
        $xret["html_mobile"] .= "<td style='width:100%'>".$row_main['cod']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr style='width:100%'>";
        $xret["html_mobile"] .= "<td style='font-weight:bold;width:100%'>Remarks</td>";
        $xret["html_mobile"] .= "<td style='width:100%'>".$row_main['remarks']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr style='width:100%'>";
        $xret["html_mobile"] .= "<td style='font-weight:bold;width:100%'>Submission Date</td>";
        $xret["html_mobile"] .= "<td style='width:100%'>".$row_main['submission_time']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr style='width:100%'>";
        $xret["html_mobile"] .= "<td style='font-weight:bold;width:100%'>Order Status</td>";
        $xret["html_mobile"] .= "<td style='width:100%'>".$row_main['order_status']."</td>";
    $xret["html_mobile"] .= "</tr>";

    if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){

        $xret["html_mobile"] .= "<tr style='width:100%'>";
            $xret["html_mobile"] .= "<td style='font-weight:bold;text-align:left;wdith:100%'>Action</td>";
            $xret["html_mobile"].= "<td style='width:100%'>";
                $xret["html_mobile"].= "<div class='dropdown'>";
                    $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:18px'>";
                        $xret["html_mobile"].= "Action";
                    $xret["html_mobile"].= "</button>";

                    $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

                    if($_SESSION['edit_crud'] == 1){
                        $xret["html_mobile"].= "<li onclick=\"viewDetailed('".$row_main['waybill_number']."')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#8c1aff;font-weight:bold;'><i class='far fa-eye'></i></i><span style='margin-left:7px;font-size:16px;font-family:arial'>View</span></a>";
                        $xret["html_mobile"].= "</li>";
                    }

                    $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('matchSalesret' , '".$row_main['recid']."', '".$row_main['waybill_number']."')\">";
                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color: #00b3b3;font-weight:bold;'><i class='fas fa-exchange-alt'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Match With Sales Return Upload</span></a>";
                    $xret["html_mobile"].= "</li>";

                    // $xret["html_mobile"].= "<li onclick=\"print_complete_pdf('".$row_main['waybill_number']."')\">";
                    //     $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff8000;font-weight:bold;'><i class='fas fa-file-pdf'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Print PDF</span></a>";
                    // $xret["html_mobile"].= "</li>";

                    if($_SESSION['delete_crud'] == 1){
                        // if(!empty($arr_alert_del2)){
                        //     $xret["html"].= "<li  onclick='matched_alert($escaped_alert)' style='pointer-events:auto;opacity:0.5'>";
                        //         $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                        //     $xret["html"].= "</li>";
                        // }else{
                        //     $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['purchasesorderfile1_recid']."')\">";
                        //         $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                        //     $xret["html"].= "</li>";
                        // }  
                    }

                    if($_SESSION['export_crud'] == 1){
                        // $xret["html_mobile"].= "<li onclick=\"print_file2('".$row_main['purchasesorderfile1_recid']."')\">";
                        //     $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#e600e6;font-weight:bold;'><i class='bi bi-printer-fill'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Print</span></a>";
                        // $xret["html_mobile"].= "</li>";
                    }
                        
                    $xret["html_mobile"].= "</ul>";
                $xret["html_mobile"].= "</div>";
            $xret["html_mobile"].= "</td>";
        $xret["html_mobile"] .= "</tr>";    
    }
    
    $xcheck++;
}







header('Content-Type: application/json');
echo json_encode($xret);
?>
