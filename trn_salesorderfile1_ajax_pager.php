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
$xret["status"] = 2;
$xret["msg"] = "";
$xret["html"] = "";
$xret["html_mobile"] = "";
$xret["alert_matched"] = "";
$trncde = $_POST["trncde"];

// var_dump($_POST);

$xwhere = "";
$xorder = " ORDER BY salesorderfile1.docnum DESC";
$search = false;
$needsCustomerJoinForCount = false;
$needsBuyerJoinForCount = false;

    if(isset($_POST["first_load"]) && $_POST["first_load"]!== "first_load"){

        if(isset($_POST['orderby_search_h']) && !empty($_POST['orderby_search_h'])){
            $xwhere = "AND mf_buyers.buyer_name LIKE '%".$_POST['orderby_search_h']."%'";
            $search = true;
            $needsBuyerJoinForCount = true;
        }

        if(isset($_POST['docnum_search_h']) && !empty($_POST['docnum_search_h'])){
            $xwhere .= " AND salesorderfile1.docnum LIKE '%".$_POST['docnum_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['ordernum_file_search_h']) && !empty($_POST['ordernum_file_search_h'])){
            $xwhere .= " AND salesorderfile1.ordernum LIKE '%".$_POST['ordernum_file_search_h']."%'";
            $search = true;
        }

        // if(isset($_POST['ordernum_file_search_h']) && !empty($_POST['ordernum_file_search_h'])){
        //     $xfilter .= " AND salesorderfile1.file_ordernum LIKE'%".$_POST['ordernum_file_search_h']."%'";
        //     $search = true;
        // }

        if((isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])) &&
        (isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])) ){

            $_POST['from_search_h'] = date("Y-m-d", strtotime($_POST['from_search_h']));
            $_POST['to_search_h'] = date("Y-m-d", strtotime($_POST['to_search_h']));

            $xwhere .= " AND salesorderfile1.trndte>='".$_POST['from_search_h']."' AND salesorderfile1.trndte<='".$_POST['to_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])){
            $_POST['from_search_h'] = date("Y-m-d", strtotime($_POST['from_search_h']));
            $xwhere .= " AND salesorderfile1.trndte>='".$_POST['from_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])){
            $_POST['to_search_h'] = date("Y-m-d", strtotime($_POST['to_search_h']));
            $xwhere .= " AND salesorderfile1.trndte<='".$_POST['to_search_h']."'";
            $search = true;
        }

        //ordered_date filter
        if((isset($_POST['from_ordered_search_h']) && !empty($_POST['from_ordered_search_h'])) &&
        (isset($_POST['to_ordered_search_h']) && !empty($_POST['to_ordered_search_h'])) ){

            $_POST['from_ordered_search_h'] = date("Y-m-d", strtotime($_POST['from_ordered_search_h']));
            $_POST['to_ordered_search_h'] = date("Y-m-d", strtotime($_POST['to_ordered_search_h']));

            $to_ordered_search_next = date("Y-m-d", strtotime($_POST['to_ordered_search_h']." +1 day"));
            $xwhere .= " AND salesorderfile1.file_created_date>='".$_POST['from_ordered_search_h']."' AND salesorderfile1.file_created_date<'".$to_ordered_search_next."'";
            $search = true;
        }

        else if(isset($_POST['from_ordered_search_h']) && !empty($_POST['from_ordered_search_h'])){
            $_POST['from_ordered_search_h'] = date("Y-m-d", strtotime($_POST['from_ordered_search_h']));
            $xwhere .= " AND salesorderfile1.file_created_date>='".$_POST['from_ordered_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['to_ordered_search_h']) && !empty($_POST['to_ordered_search_h'])){
            $_POST['to_ordered_search_h'] = date("Y-m-d", strtotime($_POST['to_ordered_search_h']));
            $to_ordered_search_next = date("Y-m-d", strtotime($_POST['to_ordered_search_h']." +1 day"));
            $xwhere .= " AND salesorderfile1.file_created_date<'".$to_ordered_search_next."'";
            $search = true;
        }        

        if(isset($_POST['cusname_search_h']) && !empty($_POST['cusname_search_h'])){
            $xwhere .= " AND customerfile.cusdsc LIKE '%".$_POST['cusname_search_h']."%'";
            $search = true;
            $needsCustomerJoinForCount = true;
        }

        if(isset($_POST['waybill_num_search_h']) && !empty($_POST['waybill_num_search_h'])){
            $xwhere .= " AND salesorderfile1.waybill_number LIKE '%".$_POST['waybill_num_search_h']."%' ";
            $search = true;
        }        

        if(($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])) && ($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h']))){
            $xorder =" ORDER BY salesorderfile1.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h'].", salesorderfile1.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }

        else if($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])){
            $xorder =" ORDER BY salesorderfile1.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h']."";
            $search = true;
        }
        else if($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h'])){
            $xorder =" ORDER BY salesorderfile1.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }
    }

if($search == false){
    $xorder = " ORDER BY salesorderfile1.docnum DESC";
}

$count_join = "";
if($needsCustomerJoinForCount){
    $count_join .= " LEFT JOIN customerfile ON salesorderfile1.cuscde = customerfile.cuscde ";
}
if($needsBuyerJoinForCount){
    $count_join .= " LEFT JOIN mf_buyers ON salesorderfile1.buyer_id = mf_buyers.buyer_id ";
}

$select_db_xtotal = "SELECT count(*) as rec_count FROM salesorderfile1 ".
                    $count_join.
                    "WHERE true AND trncde='".$trncde."' AND salesorderfile1.docnum NOT LIKE '%BOM%' ".$xwhere;
$stmt_xtotal	= $link->prepare($select_db_xtotal);
$xret["sql"] = $select_db_xtotal;
$stmt_xtotal->execute();
$rs_xtotal = $stmt_xtotal->fetch();
$xret["sql"] = $select_db_xtotal;

//INITIALIZE PAGE NO.
$xpageno = isset($_POST['pageno']) ? (int)$_POST['pageno'] : 1;

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

//RETURN PAGE NO
$xret["xpageno"] = $xpageno;
$select_db="SELECT salesorderfile1.waybill_number as 'so_waybill_number', salesorderfile1.ordernum as 'ordernum', salesorderfile1.file_created_date as 'file_created_date', salesorderfile1.shipto as salesorderfile1_shipto,salesorderfile1.cuscde as salesorderfile1_cuscde,salesorderfile1.docnum as salesorderfile1_docnum,
salesorderfile1.trndte as salesorderfile1_trndte,salesorderfile1.trntot as salesorderfile1_trntot,salesorderfile1.orderby as salesorderfile1_orderby,salesorderfile1.recid as salesorderfile1_recid,
customerfile.recid as customerfile1_recid, customerfile.cusdsc as customerfile_cusdsc,
mf_buyers.buyer_name as buyer_name,
customerfile.cusdsc, customerfile.cuscde FROM salesorderfile1 
LEFT JOIN customerfile ON salesorderfile1.cuscde = customerfile.cuscde 
LEFT JOIN mf_buyers ON salesorderfile1.buyer_id = mf_buyers.buyer_id
WHERE true AND trncde='".$trncde."' AND salesorderfile1.docnum NOT LIKE '%BOM%' ".$xwhere.$xorder." LIMIT ".$xlimit." OFFSET ".$xoffset;
$xret["sql"] = $select_db;
$stmt	= $link->prepare($select_db);
$stmt->execute();
$xcheck=0;

$rows_main = $stmt->fetchAll(PDO::FETCH_ASSOC);
$delete_alert_by_docnum = array();
$should_check_delete_matches = isset($_SESSION['delete_crud']) && $_SESSION['delete_crud'] == 1;

if($should_check_delete_matches && !empty($rows_main)){
    $docnums = array();
    foreach($rows_main as $row_for_docnum){
        if(!empty($row_for_docnum['salesorderfile1_docnum'])){
            $docnums[$row_for_docnum['salesorderfile1_docnum']] = true;
        }
    }

    if(!empty($docnums)){
        $quoted_docnums = array();
        foreach(array_keys($docnums) as $docnum){
            $quoted_docnums[] = $link->quote($docnum);
        }

        $select_matches = "SELECT salesorderfile2.docnum AS so_docnum, tranfile2.docnum AS tran_docnum
                           FROM salesorderfile2
                           INNER JOIN tranfile2 ON tranfile2.so_recid = salesorderfile2.recid
                           WHERE salesorderfile2.docnum IN (".implode(",", $quoted_docnums).")";
        $stmt_matches = $link->prepare($select_matches);
        $stmt_matches->execute();

        while($row_match = $stmt_matches->fetch(PDO::FETCH_ASSOC)){
            $so_docnum = $row_match['so_docnum'];
            $msg = "Cannot delete as ".$so_docnum." is matched with ".$row_match['tran_docnum'];

            if(!isset($delete_alert_by_docnum[$so_docnum])){
                $delete_alert_by_docnum[$so_docnum] = $msg;
            }else{
                $delete_alert_by_docnum[$so_docnum] .= "\n".$msg;
            }
        }
    }
}

foreach($rows_main as $row_main){

    $xtop_border_action = '';
    if($xcheck !== 0){
        $xtop_border_action = 'border-top:2px solid gray;';
    }

    if(empty($row_main['salesorderfile1_shipto'])){
        $row_main['salesorderfile1_shipto']=  '&nbsp;';
    }
    if(empty($row_main['customerfile_cusdsc'])){
        $row_main['customerfile_cusdsc']=  '&nbsp;';
    }
    if(empty($row_main['salesorderfile1_docnum'])){
        $row_main['salesorderfile1_docnum']=  '&nbsp;';
    }
    if(empty($row_main['buyer_name'])){
        $row_main['buyer_name']=  '&nbsp;';
    }
    if(!empty($row_main['salesorderfile1_trndte'])){
        $row_main['salesorderfile1_trndte'] = date("m-d-Y",strtotime($row_main['salesorderfile1_trndte']));
        $row_main['salesorderfile1_trndte'] = str_replace('-','/',$row_main['salesorderfile1_trndte']);
    }else{
        $row_main['salesorderfile1_trndte'] = '&nbsp;';
    }

    if(!empty($row_main['file_created_date'])){
        $file_created_date = $row_main['file_created_date'];
        $date_file_created = new DateTime($file_created_date);
        $file_created_date = $date_file_created->format('m/d/Y');
    }else{
        $file_created_date ='&nbsp;';
    }


    if(isset($row_main['salesorderfile1_trntot'])){
        $row_main['salesorderfile1_trntot'] = number_format($row_main['salesorderfile1_trntot'],'2');
    }else{
        $row_main['salesorderfile1_trntot'] = '&nbsp;';
    }

    $arr_alert_del2 = '';
    if(isset($delete_alert_by_docnum[$row_main['salesorderfile1_docnum']])){
        $arr_alert_del2 = $delete_alert_by_docnum[$row_main['salesorderfile1_docnum']];
    }

    $xret["alert_matched"] = $arr_alert_del2; 

        // Sanitize and encode the string for safe JavaScript embedding
    $escaped_alert = json_encode($arr_alert_del2);

    $xret["html"] .= "<tr class='tr_striped'>";
        $xret["html"] .= "<td data-label='Username' style='width:80%;font-size:20px;".$xtop_border_action."'>
            <table style='width:100%;
            border-collapse:collapse' id='trn_sales_table'>

                    <tr style='border-right:2px solid gray;'> 
                        <td style='width:175px;padding:0.3rem;font-weight:bold'>
                            ".$row_main['salesorderfile1_docnum']."   
                        </td>

                        <td style='padding:0.3rem'>
                            ".$row_main['customerfile_cusdsc']."
                        </td>

                        <td style='width:200px;text-align:right;padding:0.3rem'>
                        ".$row_main['salesorderfile1_trntot']."
                        </td>
                    </tr>

                    <tr style='border-right:2px solid gray'>
                        <td style='padding:0.3rem;padding:0.3rem'>
                            ".$file_created_date."
                        </td>

                        <td style='padding:0.3rem;padding:0.3rem'>
                            <b>".$row_main['ordernum']."</b>
                        </td>

                        <td colspan='2' style='text-align:left;padding:0.3rem'>
                            ".$row_main['buyer_name']."
                        </td>
                    </tr>

                    <tr style='border-right:2px solid gray'>
                        <td style='padding:0.3rem;padding:0.3rem'>
                            ".$row_main['salesorderfile1_trndte']."
                        </td>

                        <td style='width:300px;padding:0.3rem;text-align:left;padding:0.3rem'>
                            ".$row_main['salesorderfile1_shipto']."
                        </td>

                        <td style='padding:0.3rem;text-align:left;padding:0.3rem;font-weight:bold'>
                           ".$row_main['so_waybill_number']."
                        </td>
                    </tr>
            </table>
        </td>";

        if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){
            $xret["html"].= "<td class='text-center align-middle' data-label='Action' style='".$xtop_border_action."'>";
                $xret["html"].= "<div class='dropdown'>";
                    $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['salesorderfile1_recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:23px'>";
                        $xret["html"].= "Action";
                    $xret["html"].= "</button>";

                    $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['salesorderfile1_recid']."'>";

                    if($_SESSION['edit_crud'] == 1){
                        $xret["html"].= "<li onclick=\"ajaxFunc2('getEdit' , '".$row_main['salesorderfile1_recid']."', 'open_modal_admin')\">";
                            $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Edit</span></a>";
                        $xret["html"].= "</li>";
                    }

                    if($_SESSION['delete_crud'] == 1){

                        if(!empty($arr_alert_del2)){
                            $xret["html"].= "<li  onclick='matched_alert($escaped_alert)' style='pointer-events:auto;opacity:0.5'>";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }else{
                            $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['salesorderfile1_recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }

                    }

                    if($_SESSION['export_crud'] == 1){
                        $xret["html"].= "<li onclick=\"print_file2('".$row_main['salesorderfile1_recid']."')\">";
                            $xret["html"].= "<a class='dropdown-item dd_action' style='color:#e600e6;font-weight:bold;'><i class='bi bi-printer-fill'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Print</span></a>";
                        $xret["html"].= "</li>";
                    }
                        
                    $xret["html"].= "</ul>";
                $xret["html"].= "</div>";
            $xret["html"].= "</td>";
        }
    $xret["html"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Doc. Number</td>";
        $xret["html_mobile"] .= "<td>".$row_main['salesorderfile1_docnum']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Order Number</td>";
        $xret["html_mobile"] .= "<td>".$row_main['ordernum']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Platform Name</td>";
        $xret["html_mobile"] .= "<td>".$row_main['customerfile_cusdsc']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Total</td>";
        $xret["html_mobile"] .= "<td>".$row_main['salesorderfile1_trntot']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Ordered Date</td>";
        $xret["html_mobile"] .= "<td>".$file_created_date."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Tran Date.</td>";
        $xret["html_mobile"] .= "<td>".$row_main['salesorderfile1_trndte']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Order By</td>";
        $xret["html_mobile"] .= "<td>".$row_main['buyer_name']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Ship To</td>";
        $xret["html_mobile"] .= "<td>".$row_main['salesorderfile1_shipto']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Waybill Number</td>";
        $xret["html_mobile"] .= "<td>".$row_main['so_waybill_number']."</td>";
    $xret["html_mobile"] .= "</tr>";        

    if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){
        $xret["html_mobile"] .= "<tr>";
            $xret["html_mobile"] .= "<td style='font-weight:bold'>Action</td>";
            $xret["html_mobile"].= "<td class='text-center align-middle'>";
                $xret["html_mobile"].= "<div class='dropdown'>";
                    $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['salesorderfile1_recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:18px'>";
                        $xret["html_mobile"].= "Action";
                    $xret["html_mobile"].= "</button>";

                    $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['salesorderfile1_recid']."'>";

                    if($_SESSION['edit_crud'] == 1){
                        $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('getEdit' , '".$row_main['salesorderfile1_recid']."', 'open_modal_admin')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Edit</span></a>";
                        $xret["html_mobile"].= "</li>";
                    }

                    if($_SESSION['delete_crud'] == 1){
                        if(!empty($arr_alert_del2)){
                            $xret["html_mobile"].= "<li  onclick='matched_alert($escaped_alert)' style='pointer-events:auto;opacity:0.5'>";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }else{
                            $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['salesorderfile1_recid']."')\">";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }                        
                    }

                    if($_SESSION['export_crud'] == 1){
                        $xret["html_mobile"].= "<li onclick=\"print_file2('".$row_main['salesorderfile1_recid']."')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#e600e6;font-weight:bold;'><i class='bi bi-printer-fill'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Print</span></a>";
                        $xret["html_mobile"].= "</li>";
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
