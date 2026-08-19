<?php
session_start();

if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    header('location: index.php');
    exit;
}

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

$recid = $_SESSION['recid'];
$userdesc = $_SESSION['userdesc'];
$password = $_SESSION['password'];

$select_db_syspar = 'SELECT * FROM syspar';
$stmt_syspar = $link->prepare($select_db_syspar);
$stmt_syspar->execute();
while($rs_syspar = $stmt_syspar->fetch()){
    $landing_page = $rs_syspar["landing_page"];
    $system_name = $rs_syspar["system_name"];
    $version = $rs_syspar["version"];
    $logo_dir = $rs_syspar["logo_dir"];
    $logo_height = $rs_syspar["logo_height"];
    $logo_width = $rs_syspar["logo_width"];
}

$_SESSION["logo_dir"] = $logo_dir;
$_SESSION["logo_height"] = $logo_height;
$_SESSION["logo_width"] = $logo_width;

$filename = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);

$select_db_crud = "SELECT * FROM user_menus WHERE usercode=? AND menprogram=?";
$stmt_crud = $link->prepare($select_db_crud);
$stmt_crud->execute(array($_SESSION['usercode'], $filename));
$rs_crud = $stmt_crud->fetch();

if(!empty($rs_crud)){
    $add_crud = $rs_crud["add"];
    $edit_crud = $rs_crud["edit"];
    $view_crud = $rs_crud["view"];
    $delete_crud = $rs_crud["delete"];
    $export_crud = $rs_crud["export"];

    $_SESSION["add_crud"] = $rs_crud["add"];
    $_SESSION["edit_crud"] = $rs_crud["edit"];
    $_SESSION["view_crud"] = $rs_crud["view"];
    $_SESSION["delete_crud"] = $rs_crud["delete"];
    $_SESSION["export_crud"] = $rs_crud["export"];
} else if($userdesc == "admin"){
    $add_crud = 1;
    $edit_crud = 1;
    $view_crud = 1;
    $delete_crud = 1;
    $export_crud = 1;

    $_SESSION["add_crud"] = 1;
    $_SESSION["edit_crud"] = 1;
    $_SESSION["view_crud"] = 1;
    $_SESSION["delete_crud"] = 1;
    $_SESSION["export_crud"] = 1;
} else {
    $add_crud = 0;
    $edit_crud = 0;
    $view_crud = 0;
    $delete_crud = 0;
    $export_crud = 0;

    $_SESSION["add_crud"] = 0;
    $_SESSION["edit_crud"] = 0;
    $_SESSION["view_crud"] = 0;
    $_SESSION["delete_crud"] = 0;
    $_SESSION["export_crud"] = 0;
}

$current_datetime = date("Y-m-d H:i:s");
?>
<!doctype html>
<html lang="en" style="height:100%;">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="images/logo_only.png">

        <script src="js/jquery-3.5.1.min.js"></script>
        <script src="js/jquery-ui/jquery-ui.min.js"></script>

        <link rel="stylesheet" type="text/css" href="js/jquery-ui/jquery-ui.min.css">
        <link href="js/jquery-ui/jquery-ui.structure.css" rel="stylesheet">
        <link href="js/jquery-ui/jquery-ui.theme.css" rel="stylesheet">

        <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/Hover-master/css/hover.css">
        <link rel='stylesheet' href='css/all.min.css'>
        <link rel='stylesheet' href='bootstrap/icons/font/bootstrap-icons.css'>
        <link rel="stylesheet" href="css/main.css">

        <style>
            .upload-container {
                padding: 20px;
                border: 2px dashed #dc3545;
                border-radius: 5px;
                text-align: center;
                position: relative;
                margin: 20px auto;
                cursor: pointer;
            }

            .upload-container:hover {
                background-color: #fff5f5;
            }

            .drop-zone--over {
                background-color: #ffe0e0;
            }

            #file-name {
                margin-top: 10px;
                font-size: 16px;
                color: #555;
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
                            <?php echo htmlspecialchars($system_name, ENT_QUOTES); ?>
                        </li>
                        <li style='font-size:.8rem'>
                            <?php echo htmlspecialchars($version, ENT_QUOTES); ?>
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

                <div class='col-8 col-sm-10 mx-0 px-0 d-flex justify-content-end'>
                    <div class="row h-100 secondrow_secondcoloumn">
                        <div class="col-10 col-sm-11 mb-3 mx-0 px-0 text-black" style="font-size:13px;height:2.188rem">
                            <div class="h-50">
                                <?php echo "<b style='margin-right:0.188rem'>".htmlspecialchars($userdesc, ENT_QUOTES)."</b>"; ?>
                            </div>
                            <?php if(isset($_SESSION['comp_code'])): ?>
                            <div class="h-50" style='display:flex;align-items:flex-end;justify-content:flex-end'>
                                <i class="fas fa-id-card-alt" style='font-size:13px;height:90%;margin-right:0.313rem'></i>Company: <?php echo "<b style='margin-left:0.313rem;margin-right:0.188rem'>".htmlspecialchars($_SESSION['comp_code'], ENT_QUOTES)."</b>"; ?>
                            </div>
                            <?php endif; ?>
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

    <form name='myforms' id="myforms" style='height: calc(100vh - 99px)' enctype="multipart/form-data" method="post" action=''>
        <input type="hidden" name="usercode_hidden" id="usercode_hidden" value="<?php echo htmlspecialchars(isset($_SESSION['usercode']) ? $_SESSION['usercode'] : '', ENT_QUOTES); ?>">
        <table class='big_table'>
            <tr colspan=1>
                <td colspan=1 class='td_bl'>
                    <?php include 'includes/main_menu.php'; ?>
                </td>

                <td colspan=1 class="td_br" id="td_br">
                    <div class="container-fluid pt-2 main_br_div mx-0 px-0" style='width:100%'>
                        <div class="row m-0 p-0 d-flex justify-content-center">
                            <div class='d-flex justify-content-center align-items-top col-sm-12 col-sm-4'>
                                <div style='min-height:300px;height:auto;display:flex;flex-direction:column;justify-content:center'>
                                    <div style='background-color:white;' class='shadow rounded-3'>
                                        <div style='border-bottom:2px solid #dc3545'>
                                            <div class='m-3'>
                                                <label class="form-check-label fw-bold col-12 text-center text-danger" for="flexCheckDefault" style='font-size:20px'>
                                                    <div>
                                                        <i class="fas fa-trash-alt me-2"></i>Delete Sales by Order Number
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="m-3">
                                            <div class="alert alert-warning" role="alert">
                                                <strong>Instructions:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>Upload an XLS or XLSX file</li>
                                                    <li>Column A should contain order numbers</li>
                                                    <li>Row 1 is the header (skipped)</li>
                                                    <li>Row 2 onwards will be processed</li>
                                                    <li>Only Sales transactions will be matched and deleted</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="upload-container" id="drop-zone" style="width:95%;height:250px">
                                            <input type="file" id="xfile" name='xfile' hidden accept=".xls,.xlsx">
                                            <img src="images/upload_cloud.png" style='width:125px;width:125px' alt="">
                                            <p style='margin-top:20px;font-family:arial;font-size:18px'>
                                                Drag and drop a file here or click to select a file.
                                            </p>
                                            <p id="file-name" style='font-size:18px'>No file selected</p>
                                        </div>

                                        <div class='mx-3 mt-1 mb-3 text-center'>
                                            <?php if($delete_crud == 1): ?>
                                            <button class='btn btn-danger' onclick='processDeleteUpload()' style='width:180px;font-weight:bold;font-size:19px' type='button'>
                                                <i class="fas fa-trash-alt me-2"></i>Process Delete
                                            </button>
                                            <?php else: ?>
                                            <div class="alert alert-danger">You do not have permission to delete records.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <input type="hidden" name="event_action" id="event_action">
        <input type="hidden" name="hiddenDeleteUploadData" id="hiddenDeleteUploadData">
        <input type="hidden" name="txt_output_type" id="txt_output_type">

        <div class="modal fade modal_alert" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-alert">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Delete Upload Result</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body alert_modal_body" style='overflow-y:auto;max-height:80vh'>
                        ...
                    </div>
                    <div class="modal-footer alert_modal_footer">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="confirmDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete Upload
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone.
                        </div>
                        <p>Are you sure you want to process this delete upload?</p>
                        <p class="mb-0">This action should only be continued if the uploaded file is correct.</p>
                        <hr>
                        <p class="mb-0"><strong>Selected file:</strong> <span id="confirm_file_name" class="text-primary"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>No, Go Back
                        </button>
                        <button type="button" class="btn btn-danger" id="btnConfirmDelete" onclick="executeDeleteUpload()">
                            <i class="fas fa-trash-alt me-1"></i>Yes, Process Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

<script>
    function escapeHtml(value) {
        return (value || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updateFileName() {
        var fileInput = document.getElementById('xfile');
        var fileNameDisplay = document.getElementById('file-name');
        fileNameDisplay.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
    }

    var dropZone = document.getElementById('drop-zone');
    var fileInput = document.getElementById('xfile');

    dropZone.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        updateFileName();
    });

    dropZone.addEventListener('dragover', function(event) {
        event.preventDefault();
        dropZone.classList.add('drop-zone--over');
    });

    dropZone.addEventListener('dragleave', function() {
        dropZone.classList.remove('drop-zone--over');
    });

    dropZone.addEventListener('drop', function(event) {
        event.preventDefault();
        dropZone.classList.remove('drop-zone--over');

        if (event.dataTransfer.files.length) {
            fileInput.files = event.dataTransfer.files;
            updateFileName();
        }
    });

    function buildDeleteResultModalContent(data) {
        var html = '<div class="mb-3">';
        html += '<div class="fw-bold mb-2">Delete Upload Summary</div>';
        html += '<div class="small text-muted mb-2">Failed records are listed first.</div>';
        html += '<div class="row">';
        html += '<div class="col-6 col-md-3 mb-2"><div class="card bg-primary text-white text-center p-2"><div class="small">Total Processed</div><div class="fs-4">' + escapeHtml(data.total_processed) + '</div></div></div>';
        html += '<div class="col-6 col-md-3 mb-2"><div class="card bg-success text-white text-center p-2"><div class="small">Success</div><div class="fs-4">' + escapeHtml(data.total_success) + '</div></div></div>';
        html += '<div class="col-6 col-md-3 mb-2"><div class="card bg-warning text-dark text-center p-2"><div class="small">No Match</div><div class="fs-4">' + escapeHtml(data.total_no_match) + '</div></div></div>';
        html += '<div class="col-6 col-md-3 mb-2"><div class="card bg-secondary text-white text-center p-2"><div class="small">Skipped Blank</div><div class="fs-4">' + escapeHtml(data.total_skipped) + '</div></div></div>';
        html += '</div></div>';

        if (data.results && data.results.length > 0) {
            html += '<div class="table-responsive border rounded" style="max-height:45vh; overflow:auto;">';
            html += '<table class="table table-sm table-striped table-hover mb-0 align-middle">';
            html += '<thead style="position:sticky; top:0; z-index:1; background:#fff;">';
            html += '<tr><th style="width:40%;">Order Number</th><th style="width:30%;">Matched SAL#</th><th style="width:30%;">Status</th></tr>';
            html += '</thead><tbody>';

            for (var i = 0; i < data.results.length; i++) {
                var record = data.results[i];
                var isSuccess = record.status === 'SUCCESS';
                var icon = isSuccess
                    ? '<i class="fas fa-check-circle text-success me-1"></i>'
                    : '<i class="fas fa-times-circle text-danger me-1"></i>';
                var statusText = isSuccess ? 'Success' : 'Failed - No Match';
                var badgeClass = isSuccess ? 'bg-success' : 'bg-danger';
                html += '<tr>';
                html += '<td class="fw-semibold">' + escapeHtml(record.ordernum) + '</td>';
                html += '<td>' + escapeHtml(record.matched_salnum || 'N/A') + '</td>';
                html += '<td>' + icon + '<span class="badge ' + badgeClass + '">' + escapeHtml(statusText) + '</span></td>';
                html += '</tr>';
            }

            html += '</tbody></table></div>';
        } else {
            html += '<div class="alert alert-secondary mb-3">No records processed.</div>';
        }

        // Export buttons
        if (data.results && data.results.length > 0) {
            html += '<div class="d-flex justify-content-end mt-3 gap-2">';
            html += '<button type="button" class="btn btn-primary fw-bold" onclick="exportDeleteResultPdf()">';
            html += 'Export as PDF <i class="fas fa-file-pdf"></i></button>';
            html += '<button type="button" class="btn btn-success fw-bold" onclick="exportDeleteResultXls()">';
            html += 'Export as XLS <i class="fas fa-file-excel"></i></button>';
            html += '</div>';
        }

        return html;
    }

    var isProcessingDelete = false;

    function processDeleteUpload() {
        var files = $('#xfile')[0].files;
        if (files.length === 0) {
            alert('Please select a file to upload.');
            return;
        }

        var fileName = files[0].name;
        var fileNameLower = fileName.toLowerCase();
        if (!fileNameLower.endsWith('.xls') && !fileNameLower.endsWith('.xlsx')) {
            alert('Please upload an XLS or XLSX file.');
            return;
        }

        // Show the file name in the confirmation modal
        $('#confirm_file_name').text(fileName);

        // Reset button state before showing modal
        var $btnConfirm = $('#btnConfirmDelete');
        $btnConfirm.prop('disabled', false);
        $btnConfirm.html('<i class="fas fa-trash-alt me-1"></i>Yes, Process Delete');

        // Show confirmation modal
        $('#confirmDeleteModal').modal('show');
    }

    function executeDeleteUpload() {
        // Prevent duplicate submissions
        if (isProcessingDelete) {
            return;
        }

        var files = $('#xfile')[0].files;
        if (files.length === 0) {
            $('#confirmDeleteModal').modal('hide');
            alert('Please select a file to upload.');
            return;
        }

        // Set processing flag and disable button
        isProcessingDelete = true;
        var $btnConfirm = $('#btnConfirmDelete');
        $btnConfirm.prop('disabled', true);
        $btnConfirm.html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');

        var xdata = new FormData();
        xdata.append('xfile', files[0]);
        xdata.append('event_action', 'process_delete');
        xdata.append('usercode', ($('#usercode_hidden').val() || '').trim());

        jQuery.ajax({
            data: xdata,
            contentType: false,
            processData: false,
            type: 'post',
            dataType: 'json',
            url: 'sales_del_upload_ajax.php',
            success: function(xret) {
                // Hide confirmation modal
                $('#confirmDeleteModal').modal('hide');

                if (xret.status === 0) {
                    alert(xret.errorMsg || 'An error occurred.');
                } else {
                    var modalContent = buildDeleteResultModalContent(xret);

                    $('.modal-dialog-alert').addClass('modal-lg modal-dialog-scrollable');
                    $('.alert_modal_body').html(modalContent);
                    $('.alert_modal_footer').html('<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>');
                    $('.modal_alert').modal('show');

                    // Store data for export
                    var exportData = JSON.stringify(xret.results || []);
                    document.getElementById('hiddenDeleteUploadData').value = exportData;

                    // Clear file input
                    $('#xfile').val('');
                    updateFileName();
                }
            },
            error: function(xhr, status, error) {
                // Hide confirmation modal
                $('#confirmDeleteModal').modal('hide');

                // Try to parse response for better error message
                var errorMsg = 'An error occurred while processing the file.';
                if (xhr.responseText) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.errorMsg) {
                            errorMsg = resp.errorMsg;
                        }
                    } catch(e) {
                        // If response is not JSON, log it for debugging
                        console.error('Server response:', xhr.responseText);
                        errorMsg = 'Server error. Please check the console for details.';
                    }
                }
                alert(errorMsg);
            },
            complete: function() {
                // Reset processing flag and button state
                isProcessingDelete = false;
                $btnConfirm.prop('disabled', false);
                $btnConfirm.html('<i class="fas fa-trash-alt me-1"></i>Yes, Process Delete');
            }
        });
    }

    function exportDeleteResultPdf() {
        $('#txt_output_type').val('');
        document.forms.myforms.target = '_blank';
        document.forms.myforms.method = 'post';
        document.forms.myforms.action = 'sales_del_upload_pdf.php';
        document.forms.myforms.submit();
    }

    function exportDeleteResultXls() {
        $('#txt_output_type').val('tab');
        document.forms.myforms.target = '_blank';
        document.forms.myforms.method = 'post';
        document.forms.myforms.action = 'sales_del_upload_pdf.php';
        document.forms.myforms.submit();
    }
</script>

<script src="pager/pager_js.class.js"></script>
<?php require "includes/main_footer.php"; ?>
