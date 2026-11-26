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

        <?php include "includes/module_bar.php"; ?>

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

        /* paid-upload progress bar (copied/adapted from gen_dashboard.php's simulated loading bar) */
        .paidupload-loading{
            display: none;
            margin: 15px auto 0;
            width: 95%;
            background: #ffffff;
            border: 1px solid rgba(33, 37, 41, 0.1);
            border-radius: 0.95rem;
            padding: 0.9rem 1rem;
            box-shadow: 0 12px 24px rgba(33, 37, 41, 0.08);
        }

        .paidupload-loading.active{
            display: block;
        }

        .paidupload-loading-label{
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-weight: 700;
            color: #212529;
        }

        .paidupload-loading-bar{
            height: 0.5rem;
            border-radius: 999px;
            overflow: hidden;
            background: #e5e7eb;
            margin-top: 0.55rem;
            position: relative;
        }

        .paidupload-loading-bar span{
            display: block;
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #198754 0%, #20c997 100%);
            border-radius: 999px;
            transition: width 0.15s ease-out;
        }

        .paidupload-loading-percent{
            display: inline-block;
            min-width: 3rem;
            text-align: right;
            font-weight: 700;
            color: #198754;
            font-size: 0.95rem;
            margin-left: 0.5rem;
        }

        .paidupload-loading-message{
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            min-height: 1.2em;
            transition: opacity 0.3s ease;
        }

    </style>

    <script>
        function john(event2, id){
            alert(event2+","+id);
        }
    </script>

    <form name='myforms' id="myforms" style='height: calc(100vh - 99px)' enctype="multipart/form-data" method="post" action=''> 
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
                                                            Upload Sales (Paid)
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

                                            <div class="form-check ms-3">
                                                <input class="form-check-input" type="checkbox" value="" id="chk_manual">
                                                <label class="form-check-label chk_manual" for="flexCheckDefault" >
                                                    Include <b>manually</b> created sales transactions
                                                </label>
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

                                            <div class="paidupload-loading" id="paidUploadLoading">
                                                <div class="paidupload-loading-label">
                                                    <i class="fas fa-sync-alt fa-spin"></i>
                                                    Uploading and processing file...
                                                    <span class="paidupload-loading-percent" id="paidUploadLoadingPercent">0%</span>
                                                </div>
                                                <div class="paidupload-loading-bar"><span id="paidUploadLoadingBarFill"></span></div>
                                                <div class="paidupload-loading-message" id="paidUploadLoadingMessage">Preparing your file...</div>
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
        document.forms.myforms.action = "sales_filepaidupload_pdf.php";
        document.forms.myforms.submit();
    }

    function xlsxUpload(){

        $("#txt_output_type").val('tab');

        document.forms.myforms.target = "_blank";
        document.forms.myforms.method = "post";
        document.forms.myforms.action = "sales_filepaidupload_pdf.php";
        document.forms.myforms.submit();
    }  


    // paid-upload progress bar (copied/adapted from gen_dashboard.php's simulated loading bar)
    var paidUploadLoadingBox = document.getElementById('paidUploadLoading');
    var paidUploadLoadingBarFill = document.getElementById('paidUploadLoadingBarFill');
    var paidUploadLoadingPercentText = document.getElementById('paidUploadLoadingPercent');
    var paidUploadLoadingMessageElement = document.getElementById('paidUploadLoadingMessage');
    var paidUploadLoadingProgress = 0;
    var paidUploadLoadingProgressInterval = null;
    var paidUploadLoadingMessageInterval = null;
    var paidUploadLoadingMessageIndex = 0;
    var paidUploadLoadingMessages = [
        'Preparing your file...',
        'Uploading your file...',
        'Reading the spreadsheet...',
        'Matching order numbers...',
        'Almost ready...',
        'Just a little more...'
    ];

    function updatePaidUploadProgress(percent){
        paidUploadLoadingProgress = Math.min(100, Math.max(0, percent));
        paidUploadLoadingBarFill.style.width = paidUploadLoadingProgress + '%';
        paidUploadLoadingPercentText.textContent = Math.round(paidUploadLoadingProgress) + '%';
    }

    function startPaidUploadProgress(){
        paidUploadLoadingProgress = 0;
        updatePaidUploadProgress(0);
        paidUploadLoadingBox.classList.add('active');

        if(paidUploadLoadingProgressInterval){
            clearInterval(paidUploadLoadingProgressInterval);
        }

        paidUploadLoadingMessageIndex = 0;
        paidUploadLoadingMessageElement.textContent = paidUploadLoadingMessages[0];

        if(paidUploadLoadingMessageInterval){
            clearInterval(paidUploadLoadingMessageInterval);
        }

        paidUploadLoadingMessageInterval = setInterval(function(){
            paidUploadLoadingMessageIndex = (paidUploadLoadingMessageIndex + 1) % paidUploadLoadingMessages.length;
            paidUploadLoadingMessageElement.style.opacity = '0';
            setTimeout(function(){
                paidUploadLoadingMessageElement.textContent = paidUploadLoadingMessages[paidUploadLoadingMessageIndex];
                paidUploadLoadingMessageElement.style.opacity = '1';
            }, 150);
        }, 3000);

        // Smooth incremental progress distributed evenly across the loading duration
        paidUploadLoadingProgressInterval = setInterval(function(){
            if(paidUploadLoadingProgress < 20){
                paidUploadLoadingProgress += 0.8;
            }else if(paidUploadLoadingProgress < 40){
                paidUploadLoadingProgress += 0.6;
            }else if(paidUploadLoadingProgress < 60){
                paidUploadLoadingProgress += 0.5;
            }else if(paidUploadLoadingProgress < 75){
                paidUploadLoadingProgress += 0.4;
            }else if(paidUploadLoadingProgress < 85){
                paidUploadLoadingProgress += 0.3;
            }else if(paidUploadLoadingProgress < 95){
                paidUploadLoadingProgress += 0.15;
            }else if(paidUploadLoadingProgress < 98){
                paidUploadLoadingProgress += 0.08;
            }
            if(paidUploadLoadingProgress >= 98){
                paidUploadLoadingProgress = 98;
            }
            updatePaidUploadProgress(paidUploadLoadingProgress);
        }, 100);
    }

    function completePaidUploadProgress(){
        if(paidUploadLoadingProgressInterval){
            clearInterval(paidUploadLoadingProgressInterval);
            paidUploadLoadingProgressInterval = null;
        }

        if(paidUploadLoadingMessageInterval){
            clearInterval(paidUploadLoadingMessageInterval);
            paidUploadLoadingMessageInterval = null;
        }

        updatePaidUploadProgress(100);
        paidUploadLoadingMessageElement.textContent = 'Done!';

        setTimeout(function(){
            paidUploadLoadingBox.classList.remove('active');
            paidUploadLoadingProgress = 0;
            updatePaidUploadProgress(0);
            paidUploadLoadingMessageElement.textContent = paidUploadLoadingMessages[0];
        }, 400);
    }

    function resetPaidUploadProgress(){
        if(paidUploadLoadingProgressInterval){
            clearInterval(paidUploadLoadingProgressInterval);
            paidUploadLoadingProgressInterval = null;
        }
        if(paidUploadLoadingMessageInterval){
            clearInterval(paidUploadLoadingMessageInterval);
            paidUploadLoadingMessageInterval = null;
        }
        paidUploadLoadingProgress = 0;
        updatePaidUploadProgress(0);
        paidUploadLoadingMessageElement.textContent = paidUploadLoadingMessages[0];
        paidUploadLoadingBox.classList.remove('active');
    }

    function upload_waybill(){

        var platform_name = $("#platform_name").val();
        var isChecked = $('#chk_manual').prop('checked');

        if(isChecked == true){
            isChecked = 'checked';
        }else{
            isChecked = '';
        }

        // var policynum = $("#policynum_hidden_upload").val();
        var xdata = new FormData();
        var files = $('#xfile')[0].files;

        console.log('[PAID_UPLOAD] upload_waybill called', {platform_name: platform_name, chk_manual: isChecked, fileCount: files.length, fileName: files.length ? files[0].name : null});

        if(!files.length){
            console.error('[PAID_UPLOAD] no file selected - aborting before ajax call');
            alert('Please choose a file to upload first.');
            return;
        }

        xdata.append('xfile',files[0]);
        xdata.append('event_action', 'process_file');
        xdata.append('platform_name', platform_name);
        xdata.append('chk_manual', isChecked);

        resetPaidUploadProgress();
        startPaidUploadProgress();

        jQuery.ajax({
        data:xdata,
        contentType: false,
        processData: false,
        type:"post",
        dataType: 'json',  // Expect JSON response
        global: false, // suppress the site-wide loading spinner - this page shows its own progress bar
        url:"sales_filepaidupload_ajax.php",
            error: function(jqXHR, textStatus, errorThrown){
                console.error('[PAID_UPLOAD] ajax request failed', {textStatus: textStatus, errorThrown: errorThrown, httpStatus: jqXHR.status, responseText: jqXHR.responseText});
                alert('Upload failed (' + textStatus + '). Check the browser console for details.');
            },
            complete: function(){
                console.log('[PAID_UPLOAD] ajax complete');
                completePaidUploadProgress();
            },
            success: function(xret){

                console.log('[PAID_UPLOAD] ajax success', {status: xret['status'], errorMsg: xret['errorMsg'], noMatchCount: xret['noMatchFile2'] ? Object.keys(xret['noMatchFile2']).length : 0});

                if(xret['status'] == 0){
                    alert(xret["errorMsg"]);
                }else{

                    // Loop through the keys of noMatchFile2
                    var xcounter = 0;
                    var alert_data = "";

                    for (var ordernum_data in xret['noMatchFile2']) {

                        // Create HTML dynamically
                        // Get the value of the current waybillNumber (true or false)
                        var isMatched = xret['noMatchFile2'][ordernum_data]['success'];
                        var ordernum_actual = xret['noMatchFile2'][ordernum_data]['ordernum'];
                        var message = xret['noMatchFile2'][ordernum_data]['message'];         
                    
                        if(xcounter ==  100){
                            alert_data += `<div class='row my-2'>
                                <div class='d-flex align-items-center justify-content-center' style='flex-direction:row'>

                                    <div class='fw-bold'>
                                        Export to see remaining data.... 
                                    </div>
                                </div>
                            </div>`;

                            break;
                        }

                        if (isMatched == false) {

                            alert_data += `<div class='row my-2'>
                                <div class='d-flex align-items-center justify-content-center' style='flex-direction:row'>

                                    <div class='me-3'>
                                        <img style='width:25px;height:auto' src='images/red_x.png'>
                                    </div>

                                    <div>
                                        ${message}
                                    </div>
                                </div>
                        
                            </div>`;
                                

                        } else {

                            alert_data += `<div class='row my-2'>
                                <div class='d-flex align-items-center justify-content-center' style='flex-direction:row'>
                                    <div class='me-3'>
                                        <img style='width:25px;height:auto' src='images/green_check.png'>
                                    </div>
                                    <div>
                                        ${message}
                                    </div>
                                </div>
                            </div>`;
                        }        
                

                        xcounter++;
                    }

                    $(".modal-dialog-alert").addClass('modal-lg');
                    $(".alert_modal_body").html(`${alert_data}`);

                    $(".alert_modal_footer").html(`<div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle fw-bold" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                        Save as <i class="fas fa-file-export"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <li><a class="dropdown-item" onclick="printUpload()">pdf</a></li>
                        <li><a class="dropdown-item" onclick="xlsxUpload()">xlsx</a></li>
                    </ul>
                    </div>`);

                    $(".modal_alert").modal("show");

                    const jsonData = JSON.stringify(xret['noMatchFile2']);
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

