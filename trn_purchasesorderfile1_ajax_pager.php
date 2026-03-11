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

$trncde = $_POST["trncde"];

// var_dump($_POST);

$xfilter = "";
$search = false;

    if(isset($_POST["first_load"]) && $_POST["first_load"]!== "first_load"){
        if(isset($_POST['orderby_search_h']) && !empty($_POST['orderby_search_h'])){
            $xfilter = "AND purchasesorderfile1.orderby LIKE '%".$_POST['orderby_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['docnum_search_h']) && !empty($_POST['docnum_search_h'])){
            $xfilter .= " AND purchasesorderfile1.docnum LIKE '%".$_POST['docnum_search_h']."%'";
            $search = true;
        }

        if((isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])) &&
        (isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])) ){

            $_POST['from_search_h'] = date("Y-m-d", strtotime($_POST['from_search_h']));
            $_POST['to_search_h'] = date("Y-m-d", strtotime($_POST['to_search_h']));

            $xfilter .= " AND purchasesorderfile1.trndte>='".$_POST['from_search_h']."' AND purchasesorderfile1.trndte<='".$_POST['to_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])){
            $_POST['from_search_h'] = date("Y-m-d", strtotime($_POST['from_search_h']));
            $xfilter .= " AND purchasesorderfile1.trndte>='".$_POST['from_search_h']."'";
            $search = true;
        }

        else if(isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])){
            $_POST['to_search_h'] = date("Y-m-d", strtotime($_POST['to_search_h']));
            $xfilter .= " AND purchasesorderfile1.trndte<='".$_POST['to_search_h']."'";
            $search = true;
        }

        if(isset($_POST['cusname_search_h']) && !empty($_POST['cusname_search_h'])){
            $xfilter .= " AND supplierfile.suppdsc LIKE '%".$_POST['cusname_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['ordernum_search_h']) && !empty($_POST['ordernum_search_h'])){
            $xfilter .= " AND purchasesorderfile1.ordernum LIKE '%".$_POST['ordernum_search_h']."%'";
            $search = true;
        }


        if(($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])) && ($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h']))){
            $xfilter.=" ORDER BY purchasesorderfile1.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h'].", purchasesorderfile1.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }

        else if($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])){
            $xfilter.=" ORDER BY purchasesorderfile1.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h']."";
            $search = true;
        }
        else if($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h'])){
            $xfilter.=" ORDER BY purchasesorderfile1.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }
    }

    if($search == false){
        $xfilter =" ORDER BY purchasesorderfile1.docnum DESC, purchasesorderfile1.trndte DESC";
    }

$select_db_xtotal = "SELECT count(*) as rec_count FROM purchasesorderfile1 LEFT JOIN supplierfile ON purchasesorderfile1.suppcde = supplierfile.suppcde WHERE true AND trncde='".$trncde."' ".$xfilter;
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

//RETURN PAGE NO
$xret["xpageno"] = $xpageno;
$select_db="SELECT purchasesorderfile1.shipto as purchasesorderfile1_shipto,purchasesorderfile1.suppcde as purchasesorderfile1_suppcde,purchasesorderfile1.docnum as purchasesorderfile1_docnum,
purchasesorderfile1.trndte as purchasesorderfile1_trndte,purchasesorderfile1.trntot as purchasesorderfile1_trntot,purchasesorderfile1.orderby as purchasesorderfile1_orderby,purchasesorderfile1.recid as purchasesorderfile1_recid,
supplierfile.recid as supplierfile1_recid, supplierfile.suppcde as supplierfile_suppcde, purchasesorderfile1.ordernum as purchasesorderfile1_ordernum, purchasesorderfile1.paydate as purchasesorderfile1_paydate,
purchasesorderfile1.paydetails as purchasesorderfile1_paydetails, purchasesorderfile1.po_qr_id as purchases_orderfile1_po_qr_id,    
supplierfile.suppdsc as supplierfile_suppdsc,
supplierfile.suppcde, supplierfile.suppcde FROM purchasesorderfile1 LEFT JOIN supplierfile ON 
purchasesorderfile1.suppcde = supplierfile.suppcde WHERE true AND trncde='".$trncde."' ".$xfilter." LIMIT ".$xlimit." OFFSET ".$xoffset;
$xret["sql"] = $select_db;
$stmt	= $link->prepare($select_db);
$stmt->execute();
$xcheck=0;

while($row_main = $stmt->fetch()){


    $xtop_border_action = '';
    if($xcheck !== 0){
        $xtop_border_action = 'border-top:2px solid gray;';
    }


    if(!empty($row_main['purchasesorderfile1_paydate'])){
        $row_main['purchasesorderfile1_paydate'] = date("m-d-Y",strtotime($row_main['purchasesorderfile1_paydate']));
        $row_main['purchasesorderfile1_paydate'] = str_replace('-','/',$row_main['purchasesorderfile1_paydate']);
    }else{
        $row_main['purchasesorderfile1_paydate'] = '&nbsp;';
    }    

    if(empty($row_main['purchasesorderfile1_shipto'])){
        $row_main['purchasesorderfile1_shipto']=  '&nbsp;';
    }
    if(empty($row_main['customerfile_cusdsc'])){
        $row_main['customerfile_cusdsc']=  '&nbsp;';
    }
    if(empty($row_main['purchasesorderfile1_docnum'])){
        $row_main['purchasesorderfile1_docnum']=  '&nbsp;';
    }
    if(empty($row_main['purchasesorderfile1_orderby'])){
        $row_main['purchasesorderfile1_orderby']=  '&nbsp;';
    }
    if(!empty($row_main['purchasesorderfile1_trndte'])){
        $row_main['purchasesorderfile1_trndte'] = date("m-d-Y",strtotime($row_main['purchasesorderfile1_trndte']));
        $row_main['purchasesorderfile1_trndte'] = str_replace('-','/',$row_main['purchasesorderfile1_trndte']);
    }else{
        $row_main['purchasesorderfile1_trndte'] = '&nbsp;';
    }

    


    if(isset($row_main['purchasesorderfile1_trntot'])){
        $row_main['purchasesorderfile1_trntot'] = number_format($row_main['purchasesorderfile1_trntot'],'2');
    }else{
        $row_main['purchasesorderfile1_trntot'] = '&nbsp;';
    }


    $arr_alert_del2 = '';
    $select_db2="SELECT * FROM purchasesorderfile2 WHERE docnum='".$row_main['purchasesorderfile1_docnum']."'";
    $stmt2	= $link->prepare($select_db2);
    $stmt2->execute();
    $xcounter3 = 0;
    while($row_main2 = $stmt2->fetch()){

        $select_db3="SELECT * FROM tranfile2 WHERE recid='".$row_main2['tranfile2_recid']."'";
        $stmt3	= $link->prepare($select_db3);
        $stmt3->execute();

        while($row_main3 = $stmt3->fetch()){

            if($xcounter3 == 0){
                $arr_alert_del2.= "Cannot delete as ".$row_main2['docnum']." is matched with ".$row_main3['docnum'];
            }else{
                $arr_alert_del2.= "\nCannot delete as ".$row_main2['docnum']." is matched with ".$row_main3['docnum'];
            }
            
            $xcounter3++;
        }   
    }     


    if(empty($row_main['purchases_orderfile1_po_qr_id'])){
        $row_main['purchases_orderfile1_po_qr_id'] = "No PO ID Yet";
    }

    // Sanitize and encode the string for safe JavaScript embedding
    $escaped_alert = json_encode($arr_alert_del2);

    $xret["html"] .= "<tr class='tr_striped'>";
        $xret["html"] .= "<td data-label='Username' style='width:80%;font-size:20px;".$xtop_border_action."'>
            <table style='width:100%;
            table-layout:fixed;
            border-collapse:collapse' id='trn_purchases_table'>

                    <tr style='border-right:2px solid gray;'> 
                        <td style='width:175px;padding:0.3rem;font-weight:bold'>
                            ".$row_main['purchasesorderfile1_docnum']."   
                        </td>

                        <td style='max-width:200px;padding:0.3rem;font-weight:bold'>
                            ".$row_main['purchasesorderfile1_ordernum']."   
                        </td>

                        <td style='padding:0.3rem'>
                            ".$row_main['supplierfile_suppdsc']."
                        </td>

                        <td style='width:200px;text-align:right;padding:0.3rem'>
                            ".$row_main['purchasesorderfile1_trntot']."
                        </td>
                    </tr>

                    <tr style='border-right:2px solid gray'>
                        <td style='padding:0.3rem;padding:0.3rem'>
                            ".$row_main['purchasesorderfile1_trndte']."
                        </td>

                        <td colspan='3' style='text-align:left;padding:0.3rem'>
                            ".$row_main['purchasesorderfile1_orderby']."
                        </td>
                    </tr>

                    <tr>
                        <td colspan='1'  style='max-width:200px;padding:0.3rem'>
                            ".$row_main['purchasesorderfile1_paydate']."
                        </td>
                        <td>
                            ".$row_main['purchases_orderfile1_po_qr_id']."
                        </td>

                        <td colspan='2' style='padding:0.3rem;text-align:center;border-right:2px solid gray;padding:0.3rem'>
                            ".$row_main['purchasesorderfile1_shipto']."
                        </td>
                    </tr>

            </table>
        </td>";


        if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){
            $xret["html"].= "<td class='text-center align-middle' data-label='Action' style='".$xtop_border_action."'>";
                $xret["html"].= "<div class='dropdown'>";
                    $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['purchasesorderfile1_recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:23px'>";
                        $xret["html"].= "Action";
                    $xret["html"].= "</button>";

                    $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['purchasesorderfile1_recid']."'>";

                    if($_SESSION['edit_crud'] == 1){
                        $xret["html"].= "<li onclick=\"ajaxFunc2('getEdit' , '".$row_main['purchasesorderfile1_recid']."', 'open_modal_admin')\">";
                            $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Edit</span></a>";
                        $xret["html"].= "</li>";
                    }

                    if($_SESSION['delete_crud'] == 1){

                        if(!empty($arr_alert_del2)){
                            $xret["html"].= "<li  onclick='matched_alert($escaped_alert)' style='pointer-events:auto;opacity:0.5'>";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }else{
                            $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['purchasesorderfile1_recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }                        
                    }

                    if($_SESSION['export_crud'] == 1){
                        $xret["html"].= "<li onclick=\"print_file2('".$row_main['purchasesorderfile1_recid']."')\">";
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
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_docnum']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Order Number</td>";
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_ordernum']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Supplier</td>";
        $xret["html_mobile"] .= "<td>".$row_main['supplierfile_suppdsc']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Total</td>";
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_trntot']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Tran Date.</td>";
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_trndte']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Order By</td>";
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_orderby']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Pay Date</td>";
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_paydate']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Ship To</td>";
        $xret["html_mobile"] .= "<td>".$row_main['purchasesorderfile1_shipto']."</td>";
    $xret["html_mobile"] .= "</tr>";    


    if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){

        $xret["html_mobile"] .= "<tr>";
            $xret["html_mobile"] .= "<td style='font-weight:bold;text-align:left'>Action</td>";
            $xret["html_mobile"].= "<td>";
                $xret["html_mobile"].= "<div class='dropdown'>";
                    $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['purchasesorderfile1_recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:18px'>";
                        $xret["html_mobile"].= "Action";
                    $xret["html_mobile"].= "</button>";

                    $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['purchasesorderfile1_recid']."'>";

                    if($_SESSION['edit_crud'] == 1){
                        $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('getEdit' , '".$row_main['purchasesorderfile1_recid']."', 'open_modal_admin')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Edit</span></a>";
                        $xret["html_mobile"].= "</li>";
                    }

                    if($_SESSION['delete_crud'] == 1){
                        if(!empty($arr_alert_del2)){
                            $xret["html"].= "<li  onclick='matched_alert($escaped_alert)' style='pointer-events:auto;opacity:0.5'>";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }else{
                            $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['purchasesorderfile1_recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }  
                    }

                    if($_SESSION['export_crud'] == 1){
                        $xret["html_mobile"].= "<li onclick=\"print_file2('".$row_main['purchasesorderfile1_recid']."')\">";
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
