<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    header('location: index.php');
}

require_once("resources/db_init.php") ;
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

$recid=$_SESSION['recid'];
$userdesc=$_SESSION['userdesc'];
$password=$_SESSION['password'];

$select_db_syspar='SELECT * FROM syspar';
$stmt_syspar	= $link->prepare($select_db_syspar);
$stmt_syspar->execute();
while($rs_syspar = $stmt_syspar->fetch()){
    $landing_page = $rs_syspar["landing_page"];
    $system_name = $rs_syspar["system_name"];
    $version = $rs_syspar["version"];
    $logo_dir = $rs_syspar["logo_dir"];
    $logo_height = $rs_syspar["logo_height"];
    $logo_width = $rs_syspar["logo_width"];
    //$chk_insurvalcod = $rs_syspar["chk_insurvalcod"];
    //$chk_company = $rs_syspar["chk_company"];
    //$txt_company = $rs_syspar["txt_company"];


    $chk_insurvalcod = '';
    $chk_company = '';
    $txt_company = '';
}

$_SESSION["logo_dir"]       = $logo_dir;
$_SESSION["logo_height"]    = $logo_height;
$_SESSION["logo_width"]     = $logo_width;

$filename = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']); 

$select_db_crud="SELECT * FROM user_menus WHERE usercode=? AND menprogram=?";
$stmt_crud	= $link->prepare($select_db_crud);
$stmt_crud->execute(array($_SESSION['usercode'], $filename));
$rs_crud = $stmt_crud->fetch();

if(!empty($rs_crud)){

    $add_crud       = $rs_crud["add"];
    $edit_crud      = $rs_crud["edit"];
    $view_crud      = $rs_crud["view"];
    $delete_crud    = $rs_crud["delete"];
    $export_crud    = $rs_crud["export"];

    $_SESSION["add_crud"]    = $rs_crud["add"];
    $_SESSION["edit_crud"]   = $rs_crud["edit"];
    $_SESSION["view_crud"]   = $rs_crud["view"];
    $_SESSION["delete_crud"] = $rs_crud["delete"];
    $_SESSION["export_crud"] = $rs_crud["export"];

    if($filename == "utl_useractivitylog.php"){

        $add_crud       = 1;
        $edit_crud      = 1;
        $view_crud      = 1;
        $delete_crud    = 1;
        $export_crud    = 1;

        $_SESSION["add_crud"]    = 1;
        $_SESSION["edit_crud"]   = 1;
        $_SESSION["view_crud"]   = 1;
        $_SESSION["delete_crud"] = 1;
        $_SESSION["export_crud"] = 1;
    }

}
else if($userdesc == "admin"){
    $add_crud       = 1;
    $edit_crud      = 1;
    $view_crud      = 1;
    $delete_crud    = 1;
    $export_crud    = 1;

    $_SESSION["add_crud"]    = 1;
    $_SESSION["edit_crud"]   = 1;
    $_SESSION["view_crud"]   = 1;
    $_SESSION["delete_crud"] = 1;
    $_SESSION["export_crud"] = 1;
}else{

    $add_crud       = 0;
    $edit_crud      = 0;
    $view_crud      = 0;
    $delete_crud    = 0;
    $export_crud    = 0;

    $_SESSION["add_crud"]    = 0;
    $_SESSION["edit_crud"]   = 0;
    $_SESSION["view_crud"]   = 0;
    $_SESSION["delete_crud"] = 0;
    $_SESSION["export_crud"] = 0;
}

//require 'vendor/autoload.php';
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Font;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\RichText\RichText;
// use PhpOffice\PhpSpreadsheet\RichText\Run;
// use PhpOffice\PhpSpreadsheet\Style\Overflow;


$current_datetime = date("Y-m-d H:i:s");

$warehouse_options = array();
$stmt_warehouse = $link->prepare("SELECT warcde, warehouse_name FROM warehouse ORDER BY warehouse_name ASC");
$stmt_warehouse->execute();
while($rs_warehouse = $stmt_warehouse->fetch()){
    $warehouse_options[] = array(
        'warcde' => $rs_warehouse['warcde'],
        'warehouse_name' => $rs_warehouse['warehouse_name']
    );
}

$warehouse_floor_map = array();
$stmt_floor = $link->prepare("SELECT warehouse_floor_id, warcde, floor_no, floor_name FROM warehouse_floor ORDER BY floor_no ASC, floor_name ASC, warehouse_floor_id ASC");
$stmt_floor->execute();
while($rs_floor = $stmt_floor->fetch()){
    $floor_warcde = isset($rs_floor['warcde']) ? (string)$rs_floor['warcde'] : '';
    if(!isset($warehouse_floor_map[$floor_warcde])){
        $warehouse_floor_map[$floor_warcde] = array();
    }
    $warehouse_floor_map[$floor_warcde][] = array(
        'warehouse_floor_id' => $rs_floor['warehouse_floor_id'],
        'floor_no' => trim((string)($rs_floor['floor_no'] !== '' ? $rs_floor['floor_no'] : $rs_floor['floor_name']))
    );
}

$warehouse_staff_options = array();
$stmt_staff = $link->prepare("SELECT warehouse_staff_id, fname, lname FROM warehouse_staff ORDER BY fname ASC, lname ASC");
$stmt_staff->execute();
while($rs_staff = $stmt_staff->fetch()){
    $warehouse_staff_options[] = array(
        'warehouse_staff_id' => $rs_staff['warehouse_staff_id'],
        'staff_name' => trim($rs_staff['fname'].' '.$rs_staff['lname'])
    );
}

$default_warcde = 'WHS-0000001';
$default_warehouse_floor_id = 'WHFID-0000002';
?>
<!doctype html>
<html lang="en" style="height:100%;">

    <head>
        <!-- NEEDED TO MMAKE THE SIZE AND FORMAT OF WEBPAGE RIGHT -->
        <meta charset="utf-8">
        <!-- uses device width -->
        <!-- <meta name="viewport" content="width=device-width" /> -->
        
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="images/logo_only.png">

        <!-- PLAIN JQUERY AND JQUERY-UI JS -->
        <script src="js/jquery-3.5.1.min.js"></script>
        <script src="js/jquery-ui/jquery-ui.min.js"></script>

        <!--(independent libraries like JQUERY) / jquery css-->
        <link rel="stylesheet" type="text/css" href="js/jquery-ui/jquery-ui.min.css">
        <link href="js/jquery-ui/jquery-ui.structure.css" rel="stylesheet" >
        <link href="js/jquery-ui/jquery-ui.theme.css" rel="stylesheet" >

        <!-- BOOTSTRAP CSS-->
        <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">

        <!-- HOVER CSS -->
        <link rel="stylesheet" href="css/Hover-master/css/hover.css">

        <!--  FONT AWESOME ICONS -->
        <link rel='stylesheet' href='css/all.min.css'>

        <!-- BOOTSTRAP ICONS -->
        <link rel='stylesheet' href='bootstrap/icons/font/bootstrap-icons.css'>

        <!-- CUSTOM CSS-->
        <link rel="stylesheet" href="css/main.css">

        <style>

            .download_options:hover{
                cursor:pointer;
            }
        </style>
    </head>
    
    <body style="height:100%;">
        
        <div class="container-fluid">
            <div class='row bg-dark'>
                <div class="col-2 pe-0">
                    <img src="images/logo_horizontal.png" style='height:50px;width:100px;'>
                </div>

                <div class="col-10 text-white" style="text-align:right"> 
                        <ul style="list-style-type:none" class="mb-0">
                            <li style="font-family:arial;font-weight:bold">
                                <?php echo $system_name;?>
                            </li>

                            <li style='font-size:.8rem'>
                                <?php echo $version;?>
                            </li>
                        </ul>
                </div> 
            </div>
        </div>

        <div class="container-fluid">
            <div class='row bg-light' style="height:2.188rem;">
                <div class="col-1 pe-0">
                        <button type="button" class="btn btn-light bg-light menu-toggle" style="height:2.188rem;display:flex;align-items:center;justify-content:center"> 
                            <i class="fas fa-long-arrow-alt-right arrow_toggle" style="font-size:27px"></i>
                        </button>
                </div>

                <div class='col-8 col-sm-10 mx-0 px-0 d-flex justify-content-end' >   
                    <div class="row h-100 secondrow_secondcoloumn">
                        <div class="col-10 col-sm-11 mb-3 mx-0 px-0 text-black" style="font-size:13px;height:2.188rem">
                            <div class="h-50">
                                <?php echo "<b style='margin-right:0.188rem'>".$userdesc."</b>";?>
                            </div>        
                            <?php
                                if(isset($_SESSION['comp_code'])):
                            ?>
                            <div class="h-50" style='display:flex;align-items:flex-end;justify-content:flex-end'>
                            <i class="fas fa-id-card-alt" style='font-size:13px;height:90%;margin-right:0.313rem'></i>Company: <?php echo "<b style='margin-left:0.313rem;margin-right:0.188rem'>".$_SESSION['comp_code']."</b>";?>
                             
                            </div>   
                            <?php endif;?>
                        </div>   

                        <div class="col-2 col-sm-1 px-0 mx-0 h-100" style='padding-top:.3rem'>
                            <i class="far fa-user-circle" style="color:black;font-size:25px;float:left"></i>
                        </div>
                    </div>

                </div> 

                <div class="col-3 col-sm-1 mx-0 px-0 fw-bold"> 
                    <div class="col-11" style="text-align:right">
                        <a href="logout.php" style="color:black">
                            <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" class="bi bi-box-arrow-right logout_icon" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                            </svg>
                            Logout
                        </a>
                    </div>

                </div> 
            </div>
        </div>
    <style>

        .data_table{
            border-collapse:collapse;
            width:100%;
        }

        .data_table tbody tr:nth-child(even){
            background-color:#f5f5f5;
        }
    
       /* dropzone containers */
        .upload-container {
            padding: 20px;
            border: 2px dashed #007bff;
            border-radius: 5px;
            text-align: center;
            position: relative;
            margin: 20px auto;
            cursor: pointer; /* Indicates clickable */
        }

        .upload-container:hover {
            background-color: #f1f1f1; /* Visual feedback for hover */
        }

        .drop-zone--over {
            background-color: #e8f0fe; /* Visual feedback for drag over */
        }

        #file-name {
            margin-top: 10px;
            font-size: 16px;
            color: #555;
        }

    </style>

    <script>
        function john(event2, id){
            alert(event2+","+id);
        }
    </script>

    <form name='myforms' id="myforms" style='height: calc(100vh - 99px)' enctype="multipart/form-data" method="post" action=''> 
        <input type="hidden" name="usercode_hidden" id="usercode_hidden" value="<?php echo htmlspecialchars(isset($_SESSION['usercode']) ? $_SESSION['usercode'] : '', ENT_QUOTES); ?>">
        <table class='big_table'>
            <tr colspan=1>

                <td colspan=1 class='td_bl'>
                                            
                    <?php
                        include 'includes/main_menu.php';
                    ?>
                </td>
 
                <td colspan=1 class="td_br" id="td_br">

                    <div class="container-fluid pt-2 main_br_div mx-0 px-0" style='width:100%'>
                        <div class="row m-0 p-0 d-flex justify-content-center">

             
                            <div class='d-flex justify-content-center align-items-top col-sm-12 col-sm-4'>

          

                                    <div style='
                                    min-height:300px;
                                    height:auto;
                                    display:flex;
                                    flex-direction:column;
                                    justify-content:center'>
                                        <div style='
                                        background-color:white;'
                                        class ='shadow rounded-3'>
                                            <div style='border-bottom:2px solid black'>
                                                <div class='m-3'>
                                                    <label class="form-check-label fw-bold col-12 text-center" for="flexCheckDefault"  style='font-size:20px'>
                                                        <div>
                                                            Upload Sales<br>

                                                        </div>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="m-3">
                                                PLATFORM NAME:
                                                <select name="platform_name" id="platform_name" class="form-control">
                                                    <option value="TIKTOK">TIKTOK</option>
                                                    <option value="LAZADA">LAZADA</option>
                                                    <option value="SHOPEE">SHOPEE</option>
                                                </select>
                                            </div>
                                            <div class="m-3">
                                                <label for="warcde" class="mb-1">Warehouse</label>
                                                <select name="warcde" id="warcde" class="form-select">
                                                    <option value="">Select Warehouse</option>
                                                    <?php foreach($warehouse_options as $warehouse_option): ?>
                                                        <option value="<?php echo htmlspecialchars($warehouse_option['warcde'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($warehouse_option['warehouse_name'], ENT_QUOTES); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="m-3">
                                                <label for="warehouse_floor_id" class="mb-1">Warehouse Floor</label>
                                                <select name="warehouse_floor_id" id="warehouse_floor_id" class="form-select">
                                                    <option value="">Select Warehouse Floor</option>
                                                </select>
                                            </div>
                                            <div class="m-3">
                                                <label for="warehouse_staff_id" class="mb-1">Warehouse Staff</label>
                                                <select name="warehouse_staff_id" id="warehouse_staff_id" class="form-select">
                                                    <option value="">Select Warehouse Staff</option>
                                                    <?php foreach($warehouse_staff_options as $staff_option): ?>
                                                        <option value="<?php echo htmlspecialchars($staff_option['warehouse_staff_id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($staff_option['staff_name'], ENT_QUOTES); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="upload-container" id="drop-zone" style="width:95%;height:300px">
                                                <input type="file" id="xfile" name='xfile' hidden  accept=".xlsx,.csv">
                                                <img src="images/upload_cloud.png" style='width:125px;width:125px' alt="">
                                                <p style='margin-top:20px;font-family:arial;font-size:18px'>
                                                    Drag and drop a file here or click to select a file.
                                                </p>
                                                <p id="file-name" style='font-size:18px'>No file selected</p>
                                            </div>

                                            <div class='mx-3 mt-1 mb-3 text-center'>
                                                <!-- <label for="" style='font-size:18px'>Weight(kg)</label>
                                                <input type="text" class='form-control' name='weight_process' id='weight_process' style='width:60%'>
                                                <label for="" style='font-size:18px' class='mt-2'>Insurance Fee</label>
                                                <input type="text" class='form-control' name='insurance_process' id='insurance_process' style='width:60%'> -->
                                                <button class='btn btn-primary' onclick='upload_waybill()' style='width:150px;font-weight:bold;font-size:19px' type='button'>Upload</button>
                                            </div>
                        
                                        </div>
                                    </div>  
                                </div>  

                                <!-- <div class='m-2 d-flex align-items-center col-12 col-sm-4 shadow rounded-3' style='background-color:white;flex-direction:column'>
                                    <h3 style='font-size:25px;margin-top:30px'>Order Management Settings(Export Settings)</h3>
                                    <img src="images/order_management.png" style='width:90%' alt="">
                                </div> -->
                            </div>
                    </div>
                </td>

            </tr>
        </table>

        <input type="hidden" name="event_action" id="event_action">

        <div class="modal fade modal_alert" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-alert">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Avaxsol.com says:</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body alert_modal_body" style='overflow-y:auto;max-height:80vh'>
                    ...
                </div>
                <div class="modal-footer alert_modal_footer">
                    
                    <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Understood</button> -->
                </div>
            </div>
            </div>
        </div>        


        <input type="hidden" name="hiddenUploadData" id="hiddenUploadData">
        <input type="hidden" name="txt_output_type" id="txt_output_type">
    </form>
<script>

    var warehouseFloorMap = <?php echo json_encode($warehouse_floor_map); ?>;
    var defaultWarehouseCode = <?php echo json_encode($default_warcde); ?>;
    var defaultWarehouseFloorId = <?php echo json_encode($default_warehouse_floor_id); ?>;

    function rebuildFloorOptions(selectId, warcde, selectedFloorId, allowNone){
        var $select = $("#" + selectId);
        if($select.length === 0){
            return;
        }

        var options = allowNone ? "<option value=''>None</option>" : "<option value=''>Select Warehouse Floor</option>";
        var floors = warehouseFloorMap[warcde] || [];

        for(var i = 0; i < floors.length; i++){
            var floor = floors[i];
            var selected = (selectedFloorId && selectedFloorId === floor.warehouse_floor_id) ? " selected" : "";
            options += "<option value='" + floor.warehouse_floor_id + "'" + selected + ">" + floor.floor_no + "</option>";
        }

        $select.html(options);
    }

    $(document).ready(function(){
        $("#warcde").on("change", function(){
            rebuildFloorOptions("warehouse_floor_id", $(this).val(), "", false);
        });

        $("#warcde").val(defaultWarehouseCode);
        rebuildFloorOptions("warehouse_floor_id", defaultWarehouseCode, defaultWarehouseFloorId, false);
    });



    function changeParcelName(e, xid){
        const inputValue = e.target.value;

        if (inputValue.length < 20) {
            $(`#span_limit_${xid}`).remove();
            $(`.parcelName_class_${xid}`).css('border', 'none');

            var result = $('.span_exceedlimit').length > 0 ? 1 : 0;
            
            if(result == 0){
                $('#download_btn').prop('disabled', false);
            }
        }
    }

    function updateFileName() {
        fileNameDisplay.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
    }


    function onClickCheck(event,event_action){

        if (event.target.checked) {
            chcked_var = 'checked';
        } else {
            chcked_var = '';
        }

        xdata = "event_action="+event_action+"&chcked_field="+chcked_var;

        jQuery.ajax({    
        data: xdata,
        type:"post",
        dataType: 'json',  // Expect JSON response
        url:"trn_fileupload_ajaxchk.php", 
            success: function(xret){ 

            }
        })
    }

    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('xfile');
    const fileNameDisplay = document.getElementById('file-name');

    // Click the hidden input when the container is clicked
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        updateFileName();
    });

    dropZone.addEventListener('dragover', (event) => {
        event.preventDefault(); // Prevent default behavior (Prevent file from being opened)
        dropZone.classList.add('drop-zone--over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drop-zone--over');
    });

    dropZone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropZone.classList.remove('drop-zone--over');

        if (event.dataTransfer.files.length) {
            fileInput.files = event.dataTransfer.files;
            updateFileName();
        }
    });

    function printUpload(){

        $("#txt_output_type").val('');

        document.forms.myforms.target = "_blank";
        document.forms.myforms.method = "post";
        document.forms.myforms.action = "sales_fileupload_pdf.php";
        document.forms.myforms.submit();
    }

    function xlsxUpload(){

        $("#txt_output_type").val('tab');

        document.forms.myforms.target = "_blank";
        document.forms.myforms.method = "post";
        document.forms.myforms.action = "sales_fileupload_pdf.php";
        document.forms.myforms.submit();
    }  

    function escapeUploadSummaryHtml(value) {
        return (value || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getOrderedUploadResultRecords(response) {
        var responseRecords = Array.isArray(response.upload_results)
            ? response.upload_results
            : Object.values(response.noMatchFile2 || {});
        var failedRecords = [];
        var successfulRecords = [];

        for (var i = 0; i < responseRecords.length; i++) {
            var record = responseRecords[i];
            if (!record) {
                continue;
            }

            if (record.success === false) {
                failedRecords.push(record);
            } else {
                successfulRecords.push(record);
            }
        }

        return failedRecords.concat(successfulRecords);
    }

    function buildUploadResultModalContent(records) {
        var failedCount = 0;
        var rows = [];

        for (var i = 0; i < records.length; i++) {
            var record = records[i] || {};
            var isFailed = record.success === false;
            var ordernum = escapeUploadSummaryHtml(record.ordernum || '');
            var statusLabel = escapeUploadSummaryHtml(record.status_label || (isFailed ? 'Duplicate Records' : 'Success'));

            if (isFailed) {
                failedCount++;
            }

            rows.push(`<tr>
                <td class="fw-semibold">${ordernum}</td>
                <td><span class="badge ${isFailed ? 'bg-danger' : 'bg-success'}">${statusLabel}</span></td>
            </tr>`);
        }

        var successfulCount = records.length - failedCount;
        var html = `<div class="mb-3">
            <div class="fw-bold">Upload Summary</div>
            <div class="small text-muted">Failed records are listed first.</div>
            <div class="small text-muted">Failed: ${failedCount} | Success: ${successfulCount} | Total: ${records.length}</div>
        </div>`;

        if (!records.length) {
            html += `<div class="alert alert-secondary mb-3">No upload result records found.</div>`;
        } else {
            html += `<div class="table-responsive border rounded" style="max-height:55vh; overflow:auto;">
                <table class="table table-sm table-striped table-hover mb-0 align-middle">
                    <thead style="position:sticky; top:0; z-index:1; background:#fff;">
                        <tr>
                            <th style="width:70%;">Order Number</th>
                            <th style="width:30%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>${rows.join('')}</tbody>
                </table>
            </div>`;
        }

        html += `<div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-primary fw-bold" onclick="printUpload()">
                Export All to PDF <i class="fas fa-file-pdf"></i>
            </button>
        </div>`;

        return html;
    }

    function hasRequiredWarehouseSelection() {
        var warcde = ($("#warcde").val() || "").trim();
        var warehouse_floor_id = ($("#warehouse_floor_id").val() || "").trim();

        if (warcde === "" || warehouse_floor_id === "") {
            alert("Please select both Warehouse and Warehouse Floor before uploading.");
            return false;
        }

        return true;
    }


    function upload_waybill(){


        var platform_name = $("#platform_name").val();
        var warcde = $("#warcde").val();
        var warehouse_floor_id = $("#warehouse_floor_id").val();
        var warehouse_staff_id = $("#warehouse_staff_id").val();

        if (!hasRequiredWarehouseSelection()) {
            return;
        }

        // var policynum = $("#policynum_hidden_upload").val();
        var xdata = new FormData();
        var files = $('#xfile')[0].files;
        xdata.append('xfile',files[0]);
        xdata.append('event_action', 'process_file');
        xdata.append('platform_name', platform_name);
        xdata.append('warcde', warcde);
        xdata.append('warehouse_floor_id', warehouse_floor_id);
        xdata.append('warehouse_staff_id', warehouse_staff_id);
        xdata.append('usercode', ($("#usercode_hidden").val() || "").trim());

        jQuery.ajax({    
        data:xdata,
        contentType: false,
        processData: false,
        type:"post",
        dataType: 'json',  // Expect JSON response
        url:"sales_fileupload_ajax.php", 
            success: function(xret){ 

                if(xret['status'] == 0){
                    alert(xret["errorMsg"]);
                }else{
                    var resultRecords = getOrderedUploadResultRecords(xret);
                    var alert_data = buildUploadResultModalContent(resultRecords);

                    $(".modal-dialog-alert").addClass('modal-lg modal-dialog-scrollable');
                    $(".alert_modal_body").html(`${alert_data}`);

                    $(".alert_modal_footer").html(`<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`);

                    $(".modal_alert").modal("show");

                    const jsonData = JSON.stringify(resultRecords);
                    document.getElementById("hiddenUploadData").value = jsonData;

                    
                }            
               
            }
        })        
    }
</script>
<!-- PAGER JS -->   
<script src="pager/pager_js.class.js"> </script>
<?php 
require "includes/main_footer.php";

?>

