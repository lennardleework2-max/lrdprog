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
$filter_params = array();

// Helper function to validate date format (mm/dd/yyyy or Y-m-d)
function expensepager_validate_date($value){
    $value = trim((string)$value);
    if($value === ''){
        return '';
    }
    // Accept mm/dd/yyyy format
    if(preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)){
        $date = DateTime::createFromFormat('m/d/Y', $value);
        if($date && $date->format('m/d/Y') === $value){
            return $date->format('Y-m-d');
        }
    }
    // Accept Y-m-d format
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)){
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if($date && $date->format('Y-m-d') === $value){
            return $value;
        }
    }
    return '';
}

// Always check for date filters (even on first_load when coming from dashboard)
// Only skip other filters on first_load for non-date fields
$check_filters = !isset($_POST["first_load"]) || $_POST["first_load"] !== "first_load";

// Always apply date filters when present (for dashboard navigation)
$from_date_valid = '';
$to_date_valid = '';

if(isset($_POST['from_search_h']) && !empty($_POST['from_search_h'])){
    $from_date_valid = expensepager_validate_date($_POST['from_search_h']);
}
if(isset($_POST['to_search_h']) && !empty($_POST['to_search_h'])){
    $to_date_valid = expensepager_validate_date($_POST['to_search_h']);
}

if($from_date_valid !== '' && $to_date_valid !== ''){
    $xfilter .= " AND expensefile1.trndte >= ? AND expensefile1.trndte <= ?";
    $filter_params[] = $from_date_valid;
    $filter_params[] = $to_date_valid;
    $search = true;
}
else if($from_date_valid !== ''){
    $xfilter .= " AND expensefile1.trndte >= ?";
    $filter_params[] = $from_date_valid;
    $search = true;
}
else if($to_date_valid !== ''){
    $xfilter .= " AND expensefile1.trndte <= ?";
    $filter_params[] = $to_date_valid;
    $search = true;
}

// Apply other filters only if not first_load
if($check_filters){
    if(isset($_POST['docnum_search_h']) && !empty($_POST['docnum_search_h'])){
        $xfilter .= " AND expensefile1.docnum LIKE ?";
        $filter_params[] = '%' . $_POST['docnum_search_h'] . '%';
        $search = true;
    }

    if(isset($_POST['vat_type_search_h']) && !empty($_POST['vat_type_search_h'])){
        $xfilter .= " AND vat_typefile.vat_cde = ?";
        $filter_params[] = $_POST['vat_type_search_h'];
        $search = true;
    }

    if(isset($_POST['expense_type_search_h']) && !empty($_POST['expense_type_search_h'])){
        $xfilter .= " AND expensetypefile.expense_cde = ?";
        $filter_params[] = $_POST['expense_type_search_h'];
        $search = true;
    }

    if(isset($_POST['itmdsc_search_h']) && !empty($_POST['itmdsc_search_h'])){
        $xfilter .= " AND expensefile1.docnum IN (SELECT ef2.docnum FROM expensefile2 ef2 INNER JOIN itemfile itm ON ef2.itmcde = itm.itmcde WHERE itm.itmdsc LIKE ?)";
        $filter_params[] = '%' . $_POST['itmdsc_search_h'] . '%';
        $search = true;
    }

    // Handle sorting - validate allowed field names
    $allowed_sort_fields = array('docnum', 'trndte', 'suppcde');
    $allowed_sort_orders = array('Asc', 'Desc', 'asc', 'desc', 'ASC', 'DESC');

    $sort1_field = isset($_POST['sortby_1_field_h']) ? $_POST['sortby_1_field_h'] : '';
    $sort1_order = isset($_POST['sortby_1_order_h']) ? $_POST['sortby_1_order_h'] : '';
    $sort2_field = isset($_POST['sortby_2_field_h']) ? $_POST['sortby_2_field_h'] : '';
    $sort2_order = isset($_POST['sortby_2_order_h']) ? $_POST['sortby_2_order_h'] : '';

    $sort1_valid = ($sort1_field !== 'none' && !empty($sort1_field) && in_array($sort1_field, $allowed_sort_fields) && in_array($sort1_order, $allowed_sort_orders));
    $sort2_valid = ($sort2_field !== 'none' && !empty($sort2_field) && in_array($sort2_field, $allowed_sort_fields) && in_array($sort2_order, $allowed_sort_orders));

    if($sort1_valid && $sort2_valid){
        $xfilter .= " ORDER BY expensefile1." . $sort1_field . " " . $sort1_order . ", expensefile1." . $sort2_field . " " . $sort2_order;
        $search = true;
    }
    else if($sort1_valid){
        $xfilter .= " ORDER BY expensefile1." . $sort1_field . " " . $sort1_order;
        $search = true;
    }
    else if($sort2_valid){
        $xfilter .= " ORDER BY expensefile1." . $sort2_field . " " . $sort2_order;
        $search = true;
    }
}

// Separate WHERE filter from ORDER BY for count query
$xfilter_where = $xfilter;
$xfilter_order = "";
$order_pos = strpos($xfilter, ' ORDER BY');
if($order_pos !== false){
    $xfilter_where = substr($xfilter, 0, $order_pos);
    $xfilter_order = substr($xfilter, $order_pos);
}

// Add default order if none specified
if(empty($xfilter_order)){
    $xfilter_order = " ORDER BY expensefile1.docnum DESC, expensefile1.trndte DESC";
}

$select_db_xtotal = "SELECT count(*) as rec_count FROM expensefile1
                    LEFT JOIN expensetypefile ON expensefile1.expense_cde = expensetypefile.expense_cde
                    LEFT JOIN vat_typefile ON vat_typefile.vat_cde = expensefile1.vat_cde
                    WHERE true " . $xfilter_where;
$stmt_xtotal = $link->prepare($select_db_xtotal);
$stmt_xtotal->execute($filter_params);
$rs_xtotal = $stmt_xtotal->fetch();


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
$select_db = "SELECT *, expensefile1.recid as 'expensefile1_recid' FROM expensefile1
                    LEFT JOIN expensetypefile ON expensefile1.expense_cde = expensetypefile.expense_cde
                    LEFT JOIN vat_typefile ON vat_typefile.vat_cde = expensefile1.vat_cde
WHERE true " . $xfilter_where . $xfilter_order . " LIMIT " . (int)$xlimit . " OFFSET " . (int)$xoffset;
$stmt = $link->prepare($select_db);
$stmt->execute($filter_params);
$xcheck = 0;

while($row_main = $stmt->fetch()){

    $xtop_border_action = '';
    if($xcheck !== 0){
        $xtop_border_action = 'border-top:2px solid gray;';
    }

    // Escape values for HTML output
    $display_docnum = empty($row_main['docnum']) ? '&nbsp;' : htmlspecialchars($row_main['docnum'], ENT_QUOTES, 'UTF-8');
    $display_remarks = htmlspecialchars($row_main['remarks'] ?? '', ENT_QUOTES, 'UTF-8');
    if(empty($display_remarks)){
        $display_remarks = '&nbsp;';
    }

    if(!empty($row_main['trndte'])){
        $display_trndte = date("m/d/Y", strtotime($row_main['trndte']));
    }else{
        $display_trndte = '&nbsp;';
    }

    if(isset($row_main['trntot'])){
        $display_trntot = number_format($row_main['trntot'], 2);
    }else{
        $display_trntot = '&nbsp;';
    }

    if(isset($_SESSION['hide_price_crud']) && $_SESSION['userdesc'] != "admin" && $_SESSION['hide_price_crud'] == 1){
        $display_trntot = '&nbsp;';
    }

    $recid_safe = (int)$row_main['expensefile1_recid'];

    $xret["html"] .= "<tr class='tr_striped'>";
        $xret["html"] .= "<td data-label='Username' style='width:80%;font-size:20px;".$xtop_border_action."'>
            <table style='width:100%;
            table-layout:fixed;
            border-collapse:collapse' id='trn_sales_table'>

                    <tr style='border-right:2px solid gray;'>
                        <td style='width:175px;padding:0.3rem;font-weight:bold'>
                            ".$display_docnum."
                        </td>

                        <td style='padding:0.3rem'>
                            ".$display_trndte."
                        </td>

                        <td style='width:200px;text-align:right;padding:0.3rem'>
                            ".$display_trntot."
                        </td>
                    </tr>

                    <tr style='border-right:2px solid gray'>
                        <td colspan='3' style='padding:0.3rem;text-align:left'>
                            ".$display_remarks."
                        </td>
                    </tr>

            </table>
        </td>";

        if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){
            $xret["html"].= "<td class='text-center align-middle' data-label='Action' style='".$xtop_border_action."'>";
                $xret["html"].= "<div class='dropdown'>";
                    $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$recid_safe."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:23px'>";
                        $xret["html"].= "Action";
                    $xret["html"].= "</button>";

                    $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$recid_safe."'>";
                        if($_SESSION['edit_crud'] == 1){
                            $xret["html"].= "<li onclick=\"ajaxFunc2('getEdit' , '".$recid_safe."', 'open_modal_admin')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Edit</span></a>";
                            $xret["html"].= "</li>";
                        }

                        if($_SESSION['delete_crud'] == 1){
                            $xret["html"].= "<li onclick=\"ajaxFunc2('delete' , '".$recid_safe."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }

                        if($_SESSION['export_crud'] == 1){
                            // $xret["html"].= "<li onclick=\"print_file2('".$recid_safe."')\">";
                            //     $xret["html"].= "<a class='dropdown-item dd_action' style='color:#e600e6;font-weight:bold;'><i class='bi bi-printer-fill'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Print</span></a>";
                            // $xret["html"].= "</li>";
                        }

                    $xret["html"].= "</ul>";
                $xret["html"].= "</div>";
            $xret["html"].= "</td>";
        }
    $xret["html"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Doc. Number</td>";
        $xret["html_mobile"] .= "<td>".$display_docnum."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Tran. Date</td>";
        $xret["html_mobile"] .= "<td>".$display_trndte."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Total:</td>";
        $xret["html_mobile"] .= "<td>".$display_trntot."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td style='font-weight:bold'>Remarks</td>";
        $xret["html_mobile"] .= "<td>".$display_remarks."</td>";
    $xret["html_mobile"] .= "</tr>";

        if($_SESSION['view_crud'] == 1 && ($_SESSION['edit_crud'] == 1 || $_SESSION['delete_crud'] == 1)){

            $xret["html_mobile"] .= "<tr>";
                $xret["html_mobile"] .= "<td style='font-weight:bold;text-align:left'>Action</td>";
                $xret["html_mobile"].= "<td>";
                    $xret["html_mobile"].= "<div class='dropdown'>";
                        $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$recid_safe."'  data-bs-toggle='dropdown' aria-expanded='false' style='font-size:18px'>";
                            $xret["html_mobile"].= "Action";
                        $xret["html_mobile"].= "</button>";

                        $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$recid_safe."'>";

                        if($_SESSION['edit_crud'] == 1){
                            $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('getEdit' , '".$recid_safe."', 'open_modal_admin')\">";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Edit</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }

                        if($_SESSION['delete_crud'] == 1){
                            $xret["html_mobile"].= "<li onclick=\"ajaxFunc2('delete' , '".$recid_safe."')\">";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:23px;font-family:arial'>Delete</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }

                        if($_SESSION['export_crud'] == 1){

                            // $xret["html_mobile"].= "<li onclick=\"print_file2('".$recid_safe."')\">";
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
