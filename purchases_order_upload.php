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

// Load allowed suppliers
$allowed_supplier_names = array(
    'Motor Supplier 1',
    'Motor Supplier 2',
    'Motor Supplier 3',
    'Motor Supplier 5'
);
$supplier_options = array();
$placeholders = implode(',', array_fill(0, count($allowed_supplier_names), '?'));
$stmt_supplier = $link->prepare("SELECT suppcde, suppdsc FROM supplierfile WHERE suppdsc IN (".$placeholders.") ORDER BY suppdsc ASC");
$stmt_supplier->execute($allowed_supplier_names);
while($rs_supplier = $stmt_supplier->fetch()){
    $supplier_options[] = array(
        'suppcde' => $rs_supplier['suppcde'],
        'suppdsc' => $rs_supplier['suppdsc']
    );
}
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
                border: 2px dashed #007bff;
                border-radius: 5px;
                text-align: center;
                position: relative;
                margin: 20px auto;
                cursor: pointer;
            }

            .upload-container:hover {
                background-color: #f1f1f1;
            }

            .drop-zone--over {
                background-color: #e8f0fe;
            }

            #file-name {
                margin-top: 10px;
                font-size: 16px;
                color: #555;
            }

            .price-input-row {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                flex-wrap: wrap;
            }

            .price-input-row label {
                flex: 0 0 60%;
                margin-right: 10px;
                word-break: break-word;
            }

            .price-input-row input {
                flex: 0 0 35%;
                max-width: 150px;
            }

            @media (max-width: 576px) {
                .price-input-row {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .price-input-row label {
                    flex: 0 0 100%;
                    margin-bottom: 5px;
                }
                .price-input-row input {
                    flex: 0 0 100%;
                    max-width: 100%;
                    width: 100%;
                }
            }

            /* Loading overlay styles from gen_dashboard.php */
            .po-loading-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.92);
                z-index: 9999;
                justify-content: center;
                align-items: center;
                flex-direction: column;
            }

            .po-loading-overlay.active {
                display: flex;
            }

            .po-loading-box {
                background: #ffffff;
                border-radius: 1rem;
                padding: 2rem 2.5rem;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }

            .po-loading-spinner {
                width: 50px;
                height: 50px;
                border: 4px solid #e9ecef;
                border-top-color: #007bff;
                border-radius: 50%;
                animation: po-spin 1s linear infinite;
                margin: 0 auto 1.5rem;
            }

            @keyframes po-spin {
                to { transform: rotate(360deg); }
            }

            .po-loading-title {
                font-size: 1.25rem;
                font-weight: 700;
                color: #212529;
                margin-bottom: 0.75rem;
            }

            .po-loading-message {
                font-size: 0.95rem;
                color: #6c757d;
                margin-bottom: 1.25rem;
                min-height: 1.5rem;
                transition: opacity 0.15s ease;
            }

            .po-loading-progress-bar {
                background: #e9ecef;
                border-radius: 0.5rem;
                height: 8px;
                width: 100%;
                overflow: hidden;
            }

            .po-loading-progress-fill {
                background: linear-gradient(90deg, #007bff, #0056b3);
                height: 100%;
                width: 0%;
                border-radius: 0.5rem;
                transition: width 0.1s ease;
            }

            .po-loading-percent {
                font-size: 0.85rem;
                color: #6c757d;
                margin-top: 0.5rem;
                font-weight: 600;
            }

            /* Unmatched items list styles */
            .unmatched-items-list {
                max-height: 50vh;
                overflow-y: auto;
            }

            .unmatched-item-row {
                padding: 0.75rem;
                background: #fff8f8;
                border-left: 3px solid #dc3545;
                border-radius: 0 0.25rem 0.25rem 0;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .unmatched-item-row .text-danger {
                display: inline;
                white-space: pre-wrap;
                word-break: break-word;
                font-family: monospace;
                background-color: #fee;
                padding: 2px 4px;
                border-radius: 3px;
            }

            @media (max-width: 576px) {
                .unmatched-items-list {
                    max-height: 40vh;
                }

                .unmatched-item-row {
                    padding: 0.5rem;
                    font-size: 0.9rem;
                }

                #unmatchedModal .modal-dialog {
                    margin: 0.5rem;
                    max-width: calc(100% - 1rem);
                }
            }

            /* Missing prices table responsive styles */
            #missingPricesTable .price-input {
                min-width: 90px;
            }

            #missingPricesTable tbody td {
                vertical-align: middle;
            }

            @media (max-width: 576px) {
                #missingPricesTable {
                    font-size: 0.85rem;
                }

                #missingPricesTable th,
                #missingPricesTable td {
                    padding: 0.5rem 0.4rem;
                }

                #missingPricesTable .price-input {
                    min-width: 70px;
                    font-size: 0.85rem;
                    padding: 0.25rem 0.4rem;
                }

                .modal-xl {
                    max-width: 100%;
                    margin: 0.5rem;
                }
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
                            <div class='d-flex justify-content-center align-items-top col-12 col-md-8 col-lg-6'>
                                <div style='min-height:300px;height:auto;display:flex;flex-direction:column;justify-content:center;width:100%;max-width:600px'>
                                    <div style='background-color:white;' class='shadow rounded-3'>
                                        <div style='border-bottom:2px solid #007bff'>
                                            <div class='m-3'>
                                                <label class="form-check-label fw-bold col-12 text-center text-primary" for="flexCheckDefault" style='font-size:20px'>
                                                    <div>
                                                        <i class="fas fa-upload me-2"></i>Upload Purchase Order
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="m-3">
                                            <div class="alert alert-info" role="alert">
                                                <strong>Instructions:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>Upload an XLS or XLSX file</li>
                                                    <li>Converts Raw File into Purchase Order Transactions </li>
                                                    <li> Items should match item description exactly if not upload will fail</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="m-3">
                                            <label for="ordernum_input" class="mb-1 fw-bold">Order Number:<span class="text-danger">*</span></label>
                                            <input type="text" name="ordernum_input" id="ordernum_input" class="form-control" autocomplete="off" placeholder="Enter order number">
                                        </div>

                                        <div class="m-3">
                                            <label for="supplier_select" class="mb-1 fw-bold">Supplier:<span class="text-danger">*</span></label>
                                            <select name="supplier_select" id="supplier_select" class="form-select">
                                                <option value="">Select Supplier</option>
                                                <?php foreach($supplier_options as $supplier_option): ?>
                                                    <option value="<?php echo htmlspecialchars($supplier_option['suppcde'], ENT_QUOTES); ?>" data-suppdsc="<?php echo htmlspecialchars($supplier_option['suppdsc'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($supplier_option['suppdsc'], ENT_QUOTES); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="m-3">
                                            <label for="remarks_input" class="mb-1 fw-bold">Remarks:</label>
                                            <textarea name="remarks_input" id="remarks_input" class="form-control" rows="2" placeholder="Enter remarks (optional)"></textarea>
                                        </div>

                                        <div class="upload-container" id="drop-zone" style="width:95%;height:200px">
                                            <input type="file" id="xfile" name='xfile' hidden accept=".xls,.xlsx">
                                            <img src="images/upload_cloud.png" style='width:100px;height:100px' alt="">
                                            <p style='margin-top:15px;font-family:arial;font-size:16px'>
                                                Drag and drop a file here or click to select a file.
                                            </p>
                                            <p id="file-name" style='font-size:16px'>No file selected</p>
                                        </div>

                                        <div class='mx-3 mt-1 mb-3 text-center'>
                                            <?php if(!empty($rs_crud) || $userdesc == "admin"): ?>
                                            <button class='btn btn-primary' onclick='processUpload()' style='width:180px;font-weight:bold;font-size:18px' type='button'>
                                                <i class="fas fa-upload me-2"></i>Upload PO
                                            </button>
                                            <?php else: ?>
                                            <div class="alert alert-danger">You do not have permission to upload records.</div>
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
        <input type="hidden" name="hiddenUploadData" id="hiddenUploadData">
        <input type="hidden" name="hiddenParsedItems" id="hiddenParsedItems">
        <input type="hidden" name="hiddenMissingPrices" id="hiddenMissingPrices">
        <input type="hidden" name="txt_output_type" id="txt_output_type">
        <input type="hidden" name="hiddenExportData" id="hiddenExportData">

        <!-- Result Modal -->
        <div class="modal fade modal_alert" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-alert modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Upload Result</h5>
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

        <!-- Unmatched Items Modal -->
        <div class="modal fade" id="unmatchedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="unmatchedModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="unmatchedModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Unmatched Items Found
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Warning:</strong> The following item descriptions could not be matched in the system. Upload has been cancelled.
                        </div>
                        <div id="unmatchedItemsList" class="table-responsive">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Missing Prices Modal -->
        <div class="modal fade" id="missingPricesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="missingPricesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="missingPricesModalLabel">
                            <i class="fas fa-dollar-sign me-2"></i>Missing Prices
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            The following items have no previous purchase price. Please enter the unit cost for each item to continue.
                        </div>
                        <div id="missingPricesList">
                        </div>
                        <div id="missingPricesError" class="alert alert-danger d-none mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="btnSubmitPrices" onclick="submitWithPrices()">
                            <i class="fas fa-check me-1"></i>Submit Prices & Upload
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Upload Modal -->
        <div class="modal fade" id="confirmUploadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmUploadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="confirmUploadModalLabel">
                            <i class="fas fa-check-circle me-2"></i>Confirm Upload
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to upload this purchase order?</p>
                        <hr>
                        <p class="mb-1"><strong>Order Number:</strong> <span id="confirm_ordernum" class="text-primary"></span></p>
                        <p class="mb-1"><strong>Supplier:</strong> <span id="confirm_supplier" class="text-primary"></span></p>
                        <p class="mb-0"><strong>File:</strong> <span id="confirm_file" class="text-primary"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="btnConfirmUpload" onclick="executeUpload()">
                            <i class="fas fa-upload me-1"></i>Confirm Upload
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

<!-- Loading Overlay -->
<div class="po-loading-overlay" id="poLoadingOverlay">
    <div class="po-loading-box">
        <div class="po-loading-spinner"></div>
        <div class="po-loading-title">Processing Upload</div>
        <div class="po-loading-message" id="poLoadingMessage">Validating file...</div>
        <div class="po-loading-progress-bar">
            <div class="po-loading-progress-fill" id="poLoadingProgressFill"></div>
        </div>
        <div class="po-loading-percent" id="poLoadingPercent">0%</div>
    </div>
</div>

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

    var isProcessingUpload = false;
    var pendingUploadData = null;

    // Loading overlay variables
    var loadingOverlay = document.getElementById('poLoadingOverlay');
    var loadingMessage = document.getElementById('poLoadingMessage');
    var loadingProgressFill = document.getElementById('poLoadingProgressFill');
    var loadingPercent = document.getElementById('poLoadingPercent');
    var loadingProgress = 0;
    var loadingProgressInterval = null;
    var loadingMessageInterval = null;
    var loadingMessageIndex = 0;
    var loadingMessages = [
        'Validating file...',
        'Reading spreadsheet data...',
        'Matching item descriptions...',
        'Looking up prices...',
        'Processing records...',
        'Almost there...'
    ];

    function updateLoadingProgress(percent) {
        loadingProgress = Math.min(100, Math.max(0, percent));
        loadingProgressFill.style.width = loadingProgress + '%';
        loadingPercent.textContent = Math.round(loadingProgress) + '%';
    }

    function startLoadingProgress() {
        loadingProgress = 0;
        updateLoadingProgress(0);
        loadingOverlay.classList.add('active');

        if (loadingProgressInterval) {
            clearInterval(loadingProgressInterval);
        }

        // Start message rotation
        loadingMessageIndex = 0;
        loadingMessage.textContent = loadingMessages[0];

        if (loadingMessageInterval) {
            clearInterval(loadingMessageInterval);
        }

        loadingMessageInterval = setInterval(function() {
            loadingMessageIndex = (loadingMessageIndex + 1) % loadingMessages.length;
            loadingMessage.style.opacity = '0';
            setTimeout(function() {
                loadingMessage.textContent = loadingMessages[loadingMessageIndex];
                loadingMessage.style.opacity = '1';
            }, 150);
        }, 2500);

        // Smooth incremental progress
        loadingProgressInterval = setInterval(function() {
            if (loadingProgress < 20) {
                loadingProgress += 0.8;
            } else if (loadingProgress < 40) {
                loadingProgress += 0.6;
            } else if (loadingProgress < 60) {
                loadingProgress += 0.5;
            } else if (loadingProgress < 75) {
                loadingProgress += 0.4;
            } else if (loadingProgress < 85) {
                loadingProgress += 0.3;
            } else if (loadingProgress < 95) {
                loadingProgress += 0.15;
            } else if (loadingProgress < 98) {
                loadingProgress += 0.08;
            }
            // Cap at 98% and wait for actual completion
            if (loadingProgress >= 98) {
                loadingProgress = 98;
            }
            updateLoadingProgress(loadingProgress);
        }, 100);
    }

    function completeLoadingProgress() {
        if (loadingProgressInterval) {
            clearInterval(loadingProgressInterval);
            loadingProgressInterval = null;
        }

        if (loadingMessageInterval) {
            clearInterval(loadingMessageInterval);
            loadingMessageInterval = null;
        }

        // Animate to 100% then hide
        updateLoadingProgress(100);
        loadingMessage.textContent = 'Done!';

        setTimeout(function() {
            loadingOverlay.classList.remove('active');
            loadingProgress = 0;
            updateLoadingProgress(0);
            loadingMessage.textContent = loadingMessages[0];
        }, 400);
    }

    function resetLoadingProgress() {
        if (loadingProgressInterval) {
            clearInterval(loadingProgressInterval);
            loadingProgressInterval = null;
        }
        if (loadingMessageInterval) {
            clearInterval(loadingMessageInterval);
            loadingMessageInterval = null;
        }
        loadingProgress = 0;
        updateLoadingProgress(0);
        loadingMessage.textContent = loadingMessages[0];
        loadingOverlay.classList.remove('active');
    }

    function processUpload() {
        var ordernum = $.trim($('#ordernum_input').val());
        var suppcde = $.trim($('#supplier_select').val());
        var suppdsc = $('#supplier_select option:selected').data('suppdsc') || '';

        // Validation
        if (ordernum === '') {
            alert('Please enter an order number.');
            $('#ordernum_input').focus();
            return;
        }

        if (suppcde === '') {
            alert('Please select a supplier.');
            $('#supplier_select').focus();
            return;
        }

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

        // BEAVER DEBUG: Log file details for debugging
        beaverLog('VALIDATE', 'File details:', {
            name: files[0].name,
            size: files[0].size,
            type: files[0].type,
            lastModified: files[0].lastModified,
            lastModifiedDate: new Date(files[0].lastModified).toISOString()
        });

        // Show confirmation modal
        $('#confirm_ordernum').text(ordernum);
        $('#confirm_supplier').text(suppdsc);
        $('#confirm_file').text(fileName);

        var $btnConfirm = $('#btnConfirmUpload');
        $btnConfirm.prop('disabled', false);
        $btnConfirm.html('<i class="fas fa-upload me-1"></i>Confirm Upload');

        $('#confirmUploadModal').modal('show');
    }

    // BEAVER DEBUG: Set to true to enable console debug logging
    var BEAVER_DEBUG = true;

    function beaverLog(step, message, data) {
        if (!BEAVER_DEBUG) return;
        var logPrefix = '[BEAVER ' + step + ']';
        if (data !== undefined) {
            console.log(logPrefix, message, data);
        } else {
            console.log(logPrefix, message);
        }
    }

    function executeUpload() {
        if (isProcessingUpload) {
            return;
        }

        var files = $('#xfile')[0].files;
        if (files.length === 0) {
            $('#confirmUploadModal').modal('hide');
            alert('Please select a file to upload.');
            return;
        }

        isProcessingUpload = true;
        var $btnConfirm = $('#btnConfirmUpload');
        $btnConfirm.prop('disabled', true);
        $btnConfirm.html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');

        // Hide confirmation modal and show loading overlay
        $('#confirmUploadModal').modal('hide');
        startLoadingProgress();

        var xdata = new FormData();
        xdata.append('xfile', files[0]);
        xdata.append('event_action', 'validate_upload');
        xdata.append('ordernum', $.trim($('#ordernum_input').val()));
        xdata.append('suppcde', $.trim($('#supplier_select').val()));
        xdata.append('suppdsc', $('#supplier_select option:selected').data('suppdsc') || '');
        xdata.append('remarks', $.trim($('#remarks_input').val()));
        xdata.append('usercode', $.trim($('#usercode_hidden').val()));

        // BEAVER DEBUG: Log FormData values before AJAX (excluding file binary)
        beaverLog('STEP-1', 'Pre-AJAX FormData:', {
            event_action: 'validate_upload',
            ordernum: $.trim($('#ordernum_input').val()),
            suppcde: $.trim($('#supplier_select').val()),
            suppdsc: $('#supplier_select option:selected').data('suppdsc') || '',
            remarks: $.trim($('#remarks_input').val()).substring(0, 50), // truncate for safety
            usercode: $.trim($('#usercode_hidden').val()),
            file_name: files[0].name,
            file_size: files[0].size,
            file_type: files[0].type,
            file_lastModified: files[0].lastModified,
            file_lastModifiedDate: new Date(files[0].lastModified).toISOString()
        });

        jQuery.ajax({
            data: xdata,
            contentType: false,
            processData: false,
            type: 'post',
            dataType: 'json',
            url: 'purchases_order_upload_ajax.php',
            success: function(xret) {
                // BEAVER DEBUG: Log success response
                beaverLog('STEP-2', 'AJAX Success Response:', {
                    status: xret.status,
                    errorMsg: xret.errorMsg || '(none)',
                    debug_step: xret.debug_step || '(none)',
                    debug_info: xret.debug_info || '(none)',
                    has_unmatched: (xret.unmatched_items && xret.unmatched_items.length > 0),
                    has_missing_prices: (xret.missing_prices && xret.missing_prices.length > 0)
                });

                if (xret.status === 0) {
                    // Error - hide loading and show alert
                    resetLoadingProgress();
                    beaverLog('STEP-2a', 'Status 0 - Error:', xret.errorMsg);
                    alert(xret.errorMsg || 'An error occurred.');
                } else if (xret.status === 2) {
                    // Unmatched items found - hide loading and show modal
                    resetLoadingProgress();
                    beaverLog('STEP-2b', 'Status 2 - Unmatched items:', xret.unmatched_items);

                    // CRITICAL FIX: Clear the file input to force user to reselect after editing
                    // This prevents ERR_UPLOAD_FILE_CHANGED when user edits and re-uploads
                    clearFileInput();

                    showUnmatchedModal(xret.unmatched_items || [], xret.skipped_reasons || {});
                } else if (xret.status === 3) {
                    // Missing prices - hide loading and show modal for user input
                    resetLoadingProgress();
                    beaverLog('STEP-2c', 'Status 3 - Missing prices:', xret.missing_prices);
                    pendingUploadData = xret;
                    showMissingPricesModal(xret.missing_prices || []);
                } else {
                    // Success - complete loading animation then show result
                    beaverLog('STEP-2d', 'Status 1 - Success:', {docnum: xret.docnum, item_count: xret.item_count});
                    completeLoadingProgress();
                    setTimeout(function() {
                        showSuccessModal(xret);
                        clearForm();
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                resetLoadingProgress();
                // BEAVER DEBUG: Log AJAX error details
                beaverLog('STEP-3', 'AJAX Error:', {
                    http_status: xhr.status,
                    status_text: xhr.statusText,
                    ready_state: xhr.readyState,
                    error: error,
                    response_text_length: xhr.responseText ? xhr.responseText.length : 0
                });

                var errorMsg = 'An error occurred while processing the file.';
                var isFileChangedError = false;

                // Detect ERR_UPLOAD_FILE_CHANGED - happens when file was modified externally
                // Symptoms: status 0, readyState 0, no response text
                if (xhr.status === 0 && xhr.readyState === 0 && !xhr.responseText) {
                    beaverLog('STEP-3e', 'Detected possible ERR_UPLOAD_FILE_CHANGED (stale file reference)');
                    isFileChangedError = true;
                    errorMsg = 'The file has been modified since you selected it.\n\nPlease click the upload area to reselect your updated file, then click "Upload PO" again.';
                    // Clear the file input to force reselection
                    clearFileInput();
                } else if (xhr.responseText) {
                    // BEAVER DEBUG: Log raw response (first 2000 chars for safety)
                    beaverLog('STEP-3a', 'Raw server response (first 2000 chars):', xhr.responseText.substring(0, 2000));

                    try {
                        var resp = JSON.parse(xhr.responseText);
                        beaverLog('STEP-3b', 'Parsed JSON response:', resp);
                        if (resp.errorMsg) {
                            errorMsg = resp.errorMsg;
                        }
                        if (resp.debug_step) {
                            beaverLog('STEP-3c', 'Server debug_step:', resp.debug_step);
                        }
                    } catch(e) {
                        console.error('[BEAVER] JSON parse error:', e.message);
                        console.error('[BEAVER] Server response:', xhr.responseText);
                        errorMsg = 'Server error. Please check the console for details.';
                    }
                } else {
                    beaverLog('STEP-3d', 'No response text received');
                }
                alert(errorMsg);
            },
            complete: function() {
                beaverLog('STEP-4', 'AJAX Complete');
                isProcessingUpload = false;
                $btnConfirm.prop('disabled', false);
                $btnConfirm.html('<i class="fas fa-upload me-1"></i>Confirm Upload');
            }
        });
    }

    function escapeHtmlWithLineBreaks(value) {
        // First escape HTML, then convert newlines to <br> for display
        var escaped = escapeHtml(value);
        return escaped.replace(/\r\n/g, '<br>').replace(/\n/g, '<br>').replace(/\r/g, '<br>');
    }

    function showUnmatchedModal(unmatchedItems, skippedReasons) {
        skippedReasons = skippedReasons || {};

        var html = '<div class="unmatched-items-list">';
        html += '<p class="fw-bold mb-2">Items (' + unmatchedItems.length + '):</p>';

        for (var i = 0; i < unmatchedItems.length; i++) {
            var itemValue = unmatchedItems[i];
            var reason = skippedReasons[itemValue] || 'no item match';

            html += '<div class="unmatched-item-row mb-2">';
            html += '<div class="d-flex flex-column">';
            html += '<div>';
            html += '<span>\'</span>';
            html += '<span class="text-danger fw-bold" style="white-space:pre-wrap;word-break:break-word;">' + escapeHtmlWithLineBreaks(itemValue) + '</span>';
            html += '<span>\'</span>';
            html += '</div>';
            html += '<div class="text-muted small mt-1">';
            html += '<i class="fas fa-info-circle me-1"></i>' + escapeHtml(reason);
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        html += '</div>';

        // Add helpful tips at the bottom
        html += '<div class="alert alert-info mt-3 mb-0">';
        html += '<strong><i class="fas fa-lightbulb me-1"></i>Tips:</strong>';
        html += '<ul class="mb-0 mt-2">';
        html += '<li>Item descriptions must match exactly (case-insensitive)</li>';
        html += '<li>Check for extra spaces, special characters, or line breaks</li>';
        html += '<li>Ensure quantity values are greater than zero</li>';
        html += '</ul>';
        html += '</div>';

        // Add reselect file note
        html += '<div class="alert alert-warning mt-3 mb-0">';
        html += '<strong><i class="fas fa-redo me-1"></i>To upload again:</strong>';
        html += '<ol class="mb-0 mt-2">';
        html += '<li>Close this popup</li>';
        html += '<li>Edit your Excel file to fix the items above</li>';
        html += '<li>Save your Excel file</li>';
        html += '<li><strong>Click the upload area to reselect your updated file</strong></li>';
        html += '<li>Click "Upload PO" again</li>';
        html += '</ol>';
        html += '</div>';

        $('#unmatchedItemsList').html(html);
        // Update modal title with count
        $('#unmatchedModalLabel').html('<i class="fas fa-exclamation-triangle me-2"></i>Unmatched Items (' + unmatchedItems.length + ')');
        $('#unmatchedModal').modal('show');
    }

    function showMissingPricesModal(missingPrices) {
        var html = '<div class="table-responsive">';
        html += '<table class="table table-sm table-bordered align-middle mb-0" id="missingPricesTable">';
        html += '<thead class="table-light">';
        html += '<tr>';
        html += '<th style="min-width:200px;">Item</th>';
        html += '<th style="width:130px;" class="text-center">Price</th>';
        html += '<th style="width:100px;" class="text-end">Quantity</th>';
        html += '<th style="width:130px;" class="text-end">Total</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        for (var i = 0; i < missingPrices.length; i++) {
            var item = missingPrices[i];
            var qty = parseFloat(item.itmqty) || 0;
            html += '<tr>';
            html += '<td>' + escapeHtml(item.itmdsc) + '</td>';
            html += '<td>';
            html += '<input type="number" class="form-control form-control-sm price-input text-end" ';
            html += 'id="price_' + i + '" ';
            html += 'data-itmcde="' + escapeHtml(item.itmcde) + '" ';
            html += 'data-index="' + i + '" ';
            html += 'data-qty="' + qty + '" ';
            html += 'step="0.01" min="0" placeholder="0.00" ';
            html += 'oninput="updateRowTotal(' + i + ')" ';
            html += 'onchange="updateRowTotal(' + i + ')">';
            html += '</td>';
            html += '<td class="text-end">' + formatNumber(qty) + '</td>';
            html += '<td class="text-end"><span id="rowTotal_' + i + '">0.00</span></td>';
            html += '</tr>';
        }

        html += '</tbody>';
        html += '<tfoot class="table-light">';
        html += '<tr class="fw-bold">';
        html += '<td colspan="3" class="text-end">Grand Total:</td>';
        html += '<td class="text-end"><span id="grandTotal">0.00</span></td>';
        html += '</tr>';
        html += '</tfoot>';
        html += '</table>';
        html += '</div>';

        $('#missingPricesList').html(html);
        $('#missingPricesError').addClass('d-none').text('');
        $('#missingPricesModal').modal('show');
    }

    function updateRowTotal(index) {
        var $input = $('#price_' + index);
        var price = parseFloat($input.val()) || 0;
        var qty = parseFloat($input.data('qty')) || 0;
        var total = price * qty;

        $('#rowTotal_' + index).text(formatNumber(total));

        // Update grand total
        updateGrandTotal();

        // Remove invalid class if value is valid
        if (price >= 0 && $input.val() !== '') {
            $input.removeClass('is-invalid');
        }
    }

    function updateGrandTotal() {
        var grandTotal = 0;
        $('.price-input').each(function() {
            var price = parseFloat($(this).val()) || 0;
            var qty = parseFloat($(this).data('qty')) || 0;
            grandTotal += (price * qty);
        });
        $('#grandTotal').text(formatNumber(grandTotal));
    }

    function submitWithPrices() {
        var isValid = true;
        var prices = {};
        var errorMessages = [];

        $('.price-input').each(function() {
            var $input = $(this);
            var itmcde = $input.data('itmcde');
            var value = $.trim($input.val());

            if (value === '') {
                isValid = false;
                errorMessages.push('Please enter a price for all items.');
                $input.addClass('is-invalid');
                return false;
            }

            var numValue = parseFloat(value);
            if (isNaN(numValue) || numValue < 0) {
                isValid = false;
                errorMessages.push('Please enter valid numeric prices (0 or greater).');
                $input.addClass('is-invalid');
                return false;
            }

            $input.removeClass('is-invalid');
            prices[itmcde] = numValue;
        });

        if (!isValid) {
            $('#missingPricesError').removeClass('d-none').text(errorMessages[0]);
            return;
        }

        // Proceed with upload including prices
        var $btnSubmit = $('#btnSubmitPrices');
        $btnSubmit.prop('disabled', true);
        $btnSubmit.html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');

        // Hide modal and show loading overlay
        $('#missingPricesModal').modal('hide');
        startLoadingProgress();

        var files = $('#xfile')[0].files;
        var xdata = new FormData();
        xdata.append('xfile', files[0]);
        xdata.append('event_action', 'process_upload');
        xdata.append('ordernum', $.trim($('#ordernum_input').val()));
        xdata.append('suppcde', $.trim($('#supplier_select').val()));
        xdata.append('suppdsc', $('#supplier_select option:selected').data('suppdsc') || '');
        xdata.append('remarks', $.trim($('#remarks_input').val()));
        xdata.append('usercode', $.trim($('#usercode_hidden').val()));
        xdata.append('manual_prices', JSON.stringify(prices));

        jQuery.ajax({
            data: xdata,
            contentType: false,
            processData: false,
            type: 'post',
            dataType: 'json',
            url: 'purchases_order_upload_ajax.php',
            success: function(xret) {
                if (xret.status === 0) {
                    resetLoadingProgress();
                    alert(xret.errorMsg || 'An error occurred.');
                } else if (xret.status === 2) {
                    resetLoadingProgress();
                    showUnmatchedModal(xret.unmatched_items || [], xret.skipped_reasons || {});
                } else {
                    completeLoadingProgress();
                    setTimeout(function() {
                        showSuccessModal(xret);
                        clearForm();
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                resetLoadingProgress();
                beaverLog('PRICES-ERR', 'AJAX Error in submitWithPrices:', {
                    http_status: xhr.status,
                    ready_state: xhr.readyState,
                    error: error
                });

                var errorMsg = 'An error occurred while processing the file.';

                // Detect ERR_UPLOAD_FILE_CHANGED
                if (xhr.status === 0 && xhr.readyState === 0 && !xhr.responseText) {
                    beaverLog('PRICES-ERR', 'Detected stale file reference');
                    errorMsg = 'The file has been modified since you selected it.\n\nPlease click the upload area to reselect your file, then click "Upload PO" again.';
                    clearFileInput();
                } else if (xhr.responseText) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.errorMsg) {
                            errorMsg = resp.errorMsg;
                        }
                    } catch(e) {
                        // Use generic message
                    }
                }
                alert(errorMsg);
            },
            complete: function() {
                $btnSubmit.prop('disabled', false);
                $btnSubmit.html('<i class="fas fa-check me-1"></i>Submit Prices & Upload');
            }
        });
    }

    function showSuccessModal(data) {
        var html = '<div class="mb-3">';
        html += '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><strong>Success!</strong> Purchase order uploaded successfully.</div>';
        html += '<div class="row">';
        html += '<div class="col-6 mb-2"><strong>Document Number:</strong></div><div class="col-6 mb-2">' + escapeHtml(data.docnum) + '</div>';
        html += '<div class="col-6 mb-2"><strong>Order Number:</strong></div><div class="col-6 mb-2">' + escapeHtml(data.ordernum) + '</div>';
        html += '<div class="col-6 mb-2"><strong>Supplier:</strong></div><div class="col-6 mb-2">' + escapeHtml(data.suppdsc) + '</div>';
        html += '<div class="col-6 mb-2"><strong>Total Amount:</strong></div><div class="col-6 mb-2">' + escapeHtml(formatNumber(data.trntot)) + '</div>';
        html += '<div class="col-6 mb-2"><strong>Items Uploaded:</strong></div><div class="col-6 mb-2">' + escapeHtml(data.item_count) + '</div>';
        html += '</div></div>';

        var maxDisplayRows = 100;
        var totalItems = data.items ? data.items.length : 0;
        var displayCount = Math.min(totalItems, maxDisplayRows);

        if (data.items && data.items.length > 0) {
            // Show note if more than 100 rows
            if (totalItems > maxDisplayRows) {
                html += '<div class="alert alert-info py-2 mb-2"><i class="fas fa-info-circle me-1"></i>';
                html += 'Showing first ' + maxDisplayRows + ' rows only. Export the full result to view all ' + totalItems + ' rows.</div>';
            }

            html += '<div class="table-responsive border rounded" style="max-height:40vh; overflow:auto;">';
            html += '<table class="table table-sm table-striped table-hover mb-0 align-middle">';
            html += '<thead style="position:sticky; top:0; z-index:1; background:#fff;">';
            html += '<tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th></tr>';
            html += '</thead><tbody>';

            // Only display up to maxDisplayRows
            for (var i = 0; i < displayCount; i++) {
                var item = data.items[i];
                html += '<tr>';
                html += '<td>' + escapeHtml(item.itmdsc) + '</td>';
                html += '<td class="text-end">' + escapeHtml(item.itmqty) + '</td>';
                html += '<td class="text-end">' + escapeHtml(formatNumber(item.untprc)) + '</td>';
                html += '<td class="text-end">' + escapeHtml(formatNumber(item.extprc)) + '</td>';
                html += '</tr>';
            }

            html += '</tbody></table></div>';

            // Export buttons
            html += '<div class="d-flex justify-content-end mt-3 gap-2">';
            html += '<button type="button" class="btn btn-success fw-bold" onclick="exportPOUploadXls()">';
            html += '<i class="fas fa-file-excel me-1"></i>Export XLS</button>';
            html += '<button type="button" class="btn btn-danger fw-bold" onclick="exportPOUploadPdf()">';
            html += '<i class="fas fa-file-pdf me-1"></i>Export PDF</button>';
            html += '</div>';
        }

        // Store full export data in hidden field (includes all items, not just displayed ones)
        var exportData = {
            docnum: data.docnum,
            ordernum: data.ordernum,
            suppdsc: data.suppdsc,
            remarks: data.remarks || '',
            trntot: data.trntot,
            item_count: data.item_count,
            items: data.items || [],
            date_uploaded: new Date().toISOString()
        };
        document.getElementById('hiddenExportData').value = JSON.stringify(exportData);

        $('.alert_modal_body').html(html);
        $('.alert_modal_footer').html('<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>');
        $('.modal_alert').modal('show');
    }

    function exportPOUploadXls() {
        $('#txt_output_type').val('tab');
        document.forms.myforms.target = '_blank';
        document.forms.myforms.method = 'post';
        document.forms.myforms.action = 'purchases_order_upload_export_xls.php';
        document.forms.myforms.submit();
    }

    function exportPOUploadPdf() {
        $('#txt_output_type').val('');
        document.forms.myforms.target = '_blank';
        document.forms.myforms.method = 'post';
        document.forms.myforms.action = 'purchases_order_upload_export_pdf.php';
        document.forms.myforms.submit();
    }

    function formatNumber(num) {
        if (num === null || num === undefined || num === '') {
            return '0.00';
        }
        return parseFloat(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function clearForm() {
        $('#ordernum_input').val('');
        $('#supplier_select').val('');
        $('#remarks_input').val('');
        clearFileInput();
        pendingUploadData = null;
    }

    // Clear file input to force user to reselect file after editing
    // This prevents ERR_UPLOAD_FILE_CHANGED when user edits and re-uploads
    function clearFileInput() {
        var fileInput = document.getElementById('xfile');
        // Reset the file input value
        fileInput.value = '';
        // For older browsers, clone and replace the input if value reset doesn't work
        if (fileInput.files && fileInput.files.length > 0) {
            try {
                // Create a new DataTransfer to clear files (modern browsers)
                var dt = new DataTransfer();
                fileInput.files = dt.files;
            } catch(e) {
                // Fallback for browsers that don't support DataTransfer
                var parent = fileInput.parentNode;
                var newInput = fileInput.cloneNode(true);
                newInput.value = '';
                parent.replaceChild(newInput, fileInput);
                // Rebind the change event
                newInput.addEventListener('change', function() {
                    updateFileName();
                });
            }
        }
        updateFileName();
        beaverLog('FILE-CLEAR', 'File input cleared - user must reselect file');
    }
</script>

<script src="pager/pager_js.class.js"></script>
<?php require "includes/main_footer.php"; ?>
