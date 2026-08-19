<?php
require "includes/main_header.php";

// Get the filename from POST
$file_name_param = '';
if(isset($_POST['file_name_hidden']) && trim($_POST['file_name_hidden']) !== ''){
    $file_name_param = trim($_POST['file_name_hidden']);
}

// If no filename provided, redirect back to history page
if(empty($file_name_param)){
    header('Location: purchases_order_upload_history.php');
    exit;
}

// Calculate total for this file
$total_extprc = 0;
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");

$stmt_total = $link->prepare("SELECT COALESCE(SUM(extprc), 0) as total_extprc FROM po_upld_history WHERE file_name = ?");
$stmt_total->execute(array($file_name_param));
$total_row = $stmt_total->fetch();
if($total_row){
    $total_extprc = (float)$total_row['total_extprc'];
}
?>
    <style>
        .data_table {
            border-collapse: collapse;
            width: 100%;
            overflow: hidden;
        }

        .data_table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .data_table tbody tr:nth-child(even) {
            background-color: #f5f5f5 !important;
        }

        .data_table td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        .data_table tbody > tr:last-child > td {
            border-bottom: 0;
        }

        .data_table tbody > tr:last-child > td:first-child {
            border-bottom-left-radius: 0.75rem;
        }

        .data_table tbody > tr:last-child > td:last-child {
            border-bottom-right-radius: 0.75rem;
        }

        @media (max-width: 576px) {
            #tbody_main_desktop {
                display: none;
            }
            #tbody_main_mobile {
                display: table-row-group !important;
            }
            .data_table thead {
                display: none;
            }
        }

        @media (min-width: 577px) {
            #tbody_main_mobile {
                display: none;
            }
        }
    </style>

    <form name='myforms' id="myforms">
        <table class='big_table'>
            <tr colspan=1>
                <td colspan=1 class='td_bl'>
                    <?php require 'includes/main_menu.php'; ?>
                </td>

                <td colspan=1 class="td_br">
                    <div class="container-fluid pt-2 main_br_div">

                        <div class="container-fluid my-2">
                            <div class="row">
                                <div class="col-12 col-md-8" style='padding-left:0;margin-left:0;'>
                                    <h2 class='my-2'>Purchases Order Upload Details</h2>
                                    <p class="text-muted mb-1">
                                        <strong>File:</strong>
                                        <span class="text-primary"><?php echo htmlspecialchars($file_name_param, ENT_QUOTES); ?></span>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Total:</strong>
                                        <span class="text-success fw-bold"><?php echo number_format($total_extprc, 2); ?></span>
                                    </p>
                                </div>

                                <div class="col-12 col-md-4 d-flex align-items-center justify-content-md-end flex-wrap gap-2">
                                    <button type="button" class="btn btn-secondary fw-bold" onclick="goBack()">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        <span>Back</span>
                                    </button>

                                    <button type="button" class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#searchModal" onclick="clearSearchFields()">
                                        <i class="fas fa-search"></i>
                                        <span>Search</span>
                                    </button>
                                </div>
                            </div>
                        </div>


                        <table class="shadow data_table" style='border-radius:.75rem!important;width:100%;margin-bottom:1rem' id='po_detail_main_table'>
                            <thead style='border-bottom:2px solid black;'>
                                <tr>
                                    <th scope="col" style='padding: 0.5rem;font-size:16px'>Item Description</th>
                                    <th scope="col" style='padding: 0.5rem;font-size:16px' class="text-end">Unit Price</th>
                                    <th scope="col" style='padding: 0.5rem;font-size:16px' class="text-end">Item Quantity</th>
                                    <th scope="col" style='padding: 0.5rem;font-size:16px' class="text-end">Extended Price</th>
                                </tr>
                            </thead>

                            <tbody id='tbody_main_desktop'>
                            </tbody>

                            <tbody id='tbody_main_mobile' style='display:none'>
                            </tbody>
                        </table>

                        <nav aria-label='Page navigation' id='pager' style='font-size:14px'>
                            <ul class='pagination'>
                                <li class='page-item' onclick="page_click('first_p')">
                                    <span class='page-link' aria-label='Previous' style='display:flex;justify-content:center;align-items:center;height:2.3em;width:2.3em'>
                                        <span aria-hidden='true'>&laquo;</span>
                                    </span>
                                </li>

                                <li class='page-item' onclick="page_click('previous_p')">
                                    <span class='page-link' id='previous_pager' style='display:flex;align-items:center;justify-content:center;height:2.3em;width:6.3em'>
                                        Previous
                                    </span>
                                </li>

                                <input type='text' style='width:60px;text-align:center;font-weight:bold;height:2.3em' name='txt_pager_pageno' id='txt_pager_pageno' disabled>

                                <li class='page-item' onclick="page_click('next_p')">
                                    <span class='page-link' id='next_pager' style='display:flex;justify-content:center;align-items:center;height:2.3em;width:6.3em'>
                                    Next
                                    </span>
                                </li>

                                <li class='page-item' onclick="page_click('last_p')">
                                    <span class='page-link' aria-label='Next' style='display:flex;justify-content:center;align-items:center;height:2.3em;width:2.3em;'>
                                        <span aria-hidden='true'>&raquo;</span>
                                    </span>
                                </li>
                            </ul>
                        </nav>

                        <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'>
                        <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'>

                    </div>
                </td>
            </tr>
        </table>

        <!-- Hidden fields for filename and search -->
        <input type="hidden" name="file_name_hidden" id="file_name_hidden" value="<?php echo htmlspecialchars($file_name_param, ENT_QUOTES); ?>">

        <span id='hidden_search_input'>
            <input type="hidden" name="file_name_filter" id="file_name_filter" value="<?php echo htmlspecialchars($file_name_param, ENT_QUOTES); ?>">
            <input type="hidden" name="itmdsc_search_h" id="itmdsc_search_h" value="<?php if(isset($_POST['itmdsc_search_h'])){echo htmlspecialchars($_POST['itmdsc_search_h'], ENT_QUOTES);} ?>">
        </span>
    </form>

    <!-- SEARCH MODAL -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content fw-bold">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchModalLabel">Search Purchases Order Upload Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="itmdsc_search">Item Description:</label>
                            <input type="text" name="itmdsc_search" id="itmdsc_search" class="form-control" autocomplete="off">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="page_click('search')">Search</button>
                </div>
            </div>
        </div>
    </div>

<script>
    $(document).ready(function(){
        page_click("first_p");
    });

    function clearSearchFields(){
        $("#itmdsc_search").val('');
    }

    function goBack(){
        window.location.href = "purchases_order_upload_history.php";
    }

    function page_click(event_action){
        if(event_action == "search"){
            var itmdsc_search = $("#itmdsc_search").val();
            $("#itmdsc_search_h").val(itmdsc_search);
        }

        var pageno = $("#txt_pager_pageno").val();
        var xdata = $("#hidden_search_input *").serialize() + "&pageno=" + pageno + "&event_action=" + event_action;

        jQuery.ajax({
            data: xdata,
            dataType: "json",
            type: "post",
            url: "purchases_order_upload_history2_ajax.php",

            success: function(xdata){
                $("#txt_pager_totalrec").val(xdata["totalrec"]);
                $("#txt_pager_pageno").val(xdata["xpageno"]);
                $("#txt_pager_maxpage").val(xdata["maxpage"]);
                $("#tbody_main_desktop").html(xdata["html"]);
                $("#tbody_main_mobile").html(xdata["html_mobile"]);

                if(event_action == "search"){
                    $("#searchModal").modal("hide");
                }
            },
            error: function(xhr, status, error){
                console.error("Error loading data:", error);
            }
        });
    }

    function check_enter(evt) {
        var ASCIICode = (evt.which) ? evt.which : evt.keyCode;
        if(ASCIICode == 13){
            page_click("search");
        }
    }
</script>

<?php
require "includes/main_footer.php";
?>
