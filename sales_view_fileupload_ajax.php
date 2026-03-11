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


    if(isset($_POST["first_load"]) && $_POST["first_load"]!== "first_load"){

        if(isset($_POST['platform_search_h']) && !empty($_POST['platform_search_h'])){

            if($_POST['platform_search_h'] != 'ALL'){
                $xfilter .= " AND upld_salesfile.platform='".$_POST['platform_search_h']."'";
            }
              
            $search = true;
        }

        if(isset($_POST['ordernum_search_h']) && !empty($_POST['ordernum_search_h'])){

            $xfilter .= " AND upld_salesfile.ordernum LIKE '%".$_POST['ordernum_search_h']."%'";
            $search = true;
        }


        if(isset($_POST['date_uploaded_search_h']) && !empty($_POST['date_uploaded_search_h'])){

            $_POST['date_uploaded_search_h'] = date("Y-m-d", strtotime($_POST['date_uploaded_search_h']));

            $xfilter .= " AND DATE(upld_salesfile.datetime_upload)='".$_POST['date_uploaded_search_h']."'";
            $search = true;
        }

        if(isset($_POST['file_batchno_search_h']) && !empty($_POST['file_batchno_search_h'])){

            //$_POST['cod_search_h'] = str_replace(',', '', $_POST['cod_search_h']);

            $xfilter .= " AND upld_salesfile.file_batchno LIKE '%".$_POST['file_batchno_search_h']."%'";
            $search = true;
        }

        if(isset($_POST['paid_search_h']) && !empty($_POST['paid_search_h']) && ($_POST['paid_search_h'] != 'all')){

            if($_POST['paid_search_h'] == 'paid'){
                $xfilter .= " AND upld_salesfile.paydate IS NOT NULL AND upld_salesfile.paydate !=''";
            }else{
                $xfilter .= " AND upld_salesfile.paydate IS NULL OR upld_salesfile.paydate =''";
            }

            $search = true;
        }

        if(isset($_POST['trndte_search_h']) && !empty($_POST['trndte_search_h'])){

            $_POST['trndte_search_h'] = date("Y-m-d", strtotime($_POST['trndte_search_h']));

            $xfilter .= " AND upld_salesfile.trndte = '".$_POST['trndte_search_h']."'";
            $search = true;
        }

        if(isset($_POST['itmcde_desc_search_h']) && !empty($_POST['itmcde_desc_search_h'])){

            $xfilter .= " AND itemfile.itmdsc LIKE '%".$_POST['itmcde_desc_search_h']."%'";
            $search = true;
        }

        if(($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])) && ($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h']))){
            $xfilter.=" ORDER BY upld_salesfile.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h'].", upld_salesfile.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            $search = true;
        }

        else if($_POST['sortby_1_field_h']!=='none' && !empty($_POST['sortby_1_field_h'])){

            if($_POST['sortby_1_field_h'] == "itmdsc"){
                $xfilter.=" ORDER BY itemfile.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h']."";
            }else{
                $xfilter.=" ORDER BY upld_salesfile.".$_POST['sortby_1_field_h']." ".$_POST['sortby_1_order_h']."";
            }
  
            $search = true;
        }
        else if($_POST['sortby_2_field_h']!=='none' && !empty($_POST['sortby_2_field_h'])){

            if($_POST['sortby_2_field_h'] == "itmdsc"){
             $xfilter.=" ORDER BY itemfile.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            }else{
                $xfilter.=" ORDER BY upld_salesfile.".$_POST['sortby_2_field_h']." ".$_POST['sortby_2_order_h']."";
            }


            $search = true;
        }
    }

    if($search == false){
        $xorder =" ORDER BY upld_salesfile.recid DESC";
    }

$select_db_xtotal = "SELECT COUNT(*) as rec_count FROM upld_salesfile LEFT JOIN itemfile ON
upld_salesfile.itmcde_matched = itemfile.itmcde WHERE true ".$xfilter;
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
                        Platform    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        Order Num.   
                    </td>

                    <td class='text-start fw-bold ps-3' style='border-top-right-radius:7.5px'>
                        Date Uploaded    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        File Batch No.    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        Tran. Date    
                    </td>
                    
                    <td class='text-start fw-bold ps-3'>
                        Item Matched    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        Quantity    
                    </td>

                    <td class='text-start fw-bold ps-3'>
                        Unit Price    
                    </td>

                    <td class='text-start fw-bold ps-3' style='border-top-right-radius:7.5px'>
                        Pay Date    
                    </td>
                  </tr>";

//RETURN PAGE NO
$xret["xpageno"] = $xpageno;
$select_db="SELECT * FROM upld_salesfile LEFT JOIN itemfile ON
upld_salesfile.itmcde_matched = itemfile.itmcde
WHERE   
 true ".$xfilter."  ".$xorder."  LIMIT ".$xlimit." OFFSET ".$xoffset;
$xret["sql"] = $select_db;
$stmt	= $link->prepare($select_db);
$stmt->execute();
$xcheck=0;

while($row_main = $stmt->fetch()){

    $xtop_border_action = '';
    if($xcheck !== 0){
        $xtop_border_action = 'border-top:2px solid gray;';
    }

    if(!empty($row_main['datetime_upload'])){
        $row_main['datetime_upload'] = DateTime::createFromFormat('Y-m-d H:i:s', $row_main['datetime_upload'])->format('m/d/Y');
    }else{
        $row_main['datetime_upload'] = '&nbsp;';
    }

    $converted_trndte = date('m/d/Y', strtotime($row_main['trndte']));

    if(!empty($row_main['paydate']) && $row_main['paydate'] != null){
        $converted_paydate = date('m/d/Y', strtotime($row_main['paydate']));
    }else{
        $converted_paydate = null;
    }



        $xret["html"] .= "
                    <tr class='tr_striped'>
                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['platform']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['ordernum']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['datetime_upload']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['file_batchno']."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$converted_trndte."  
                        </td>

                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['itmdsc']."  
                        </td>
                        
                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['itmqty']."  
                        </td>
                        
                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$row_main['untprc']."  
                        </td>
                        
                        <td style='padding:0.3rem;text-align:left' class='ps-3'>
                            ".$converted_paydate."  
                        </td>";                 

    $xret["html"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Platform</td>";
        $xret["html_mobile"] .= "<td>".$row_main['platform']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Ordernum</td>";
        $xret["html_mobile"] .= "<td>".$row_main['ordernum']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Date Uploaded</td>";
        $xret["html_mobile"] .= "<td>".$row_main['datetime_upload']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>File Batch No.</td>";
        $xret["html_mobile"] .= "<td>".$row_main['file_batchno']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Tran. Date</td>";
        $xret["html_mobile"] .= "<td>".$converted_trndte."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Item</td>";
        $xret["html_mobile"] .= "<td>".$row_main['itmcde_matched']."</td>";
    $xret["html_mobile"] .= "</tr>";
    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Quantity</td>";
        $xret["html_mobile"] .= "<td>".$row_main['itmqty']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Unit Price</td>";
        $xret["html_mobile"] .= "<td>".$row_main['untprc']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Pay Date</td>";
        $xret["html_mobile"] .= "<td>".$converted_paydate."</td>";
    $xret["html_mobile"] .= "</tr>";


    if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){

        // $xret["html_mobile"] .= "<tr style='width:100%'>";
        //     $xret["html_mobile"] .= "<td style='font-weight:bold;text-align:left;wdith:100%'>Action</td>";
        //     $xret["html_mobile"].= "<td style='width:100%'>";
        //         $xret["html_mobile"].= "<div class='dropdown'>";
        //             $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:18px'>";
        //                 $xret["html_mobile"].= "Action";
        //             $xret["html_mobile"].= "</button>";

        //             $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

        //             if($_SESSION['edit_crud'] == 1){
        //                 $xret["html_mobile"].= "<li onclick=\"viewDetailed('".$row_main['waybill_number']."')\">";
        //                     $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#8c1aff;font-weight:bold;'><i class='far fa-eye'></i></i><span style='margin-left:7px;font-size:16px;font-family:arial'>View</span></a>";
        //                 $xret["html_mobile"].= "</li>";
        //             }

        //             $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('matchSalesret' , '".$row_main['recid']."', '".$row_main['waybill_number']."')\">";
        //                 $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color: #00b3b3;font-weight:bold;'><i class='fas fa-exchange-alt'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Match With Sales Return Upload</span></a>";
        //             $xret["html_mobile"].= "</li>";

        //             // $xret["html_mobile"].= "<li onclick=\"print_complete_pdf('".$row_main['waybill_number']."')\">";
        //             //     $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff8000;font-weight:bold;'><i class='fas fa-file-pdf'></i><span style='margin-left:7px;font-size:16px;font-family:arial'>Print PDF</span></a>";
        //             // $xret["html_mobile"].= "</li>";

        //             if($_SESSION['delete_crud'] == 1){
        //                 // if(!empty($arr_alert_del2)){
        //                 //     $xret["html"].= "<li  onclick='matched_alert($escaped_alert)' style='pointer-events:auto;opacity:0.5'>";
        //                 //         $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
        //                 //     $xret["html"].= "</li>";
        //                 // }else{
        //                 //     $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$row_main['purchasesorderfile1_recid']."')\">";
        //                 //         $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
        //                 //     $xret["html"].= "</li>";
        //                 // }  
        //             }

        //             if($_SESSION['export_crud'] == 1){
        //                 // $xret["html_mobile"].= "<li onclick=\"print_file2('".$row_main['purchasesorderfile1_recid']."')\">";
        //                 //     $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#e600e6;font-weight:bold;'><i class='bi bi-printer-fill'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Print</span></a>";
        //                 // $xret["html_mobile"].= "</li>";
        //             }
                        
        //             $xret["html_mobile"].= "</ul>";
        //         $xret["html_mobile"].= "</div>";
        //     $xret["html_mobile"].= "</td>";
        // $xret["html_mobile"] .= "</tr>";    
    }
    
    $xcheck++;
}







header('Content-Type: application/json');
echo json_encode($xret);
?>
