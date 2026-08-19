<?php
require "includes/main_header.php";
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

        .action-col {
            white-space: nowrap;
            min-width: 170px;
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
                                <div class="col-12 col-md-6" style='padding-left:0;margin-left:0;'>
                                    <h2 class='my-2'>Delete Upload History</h2>
                                </div>

                                <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end">
                                    <button type="button" class="btn btn-dark m-1 fw-bold" data-bs-toggle="modal" data-bs-target="#searchModal" onclick="clearSearchFields()">
                                        <i class="fas fa-search"></i>
                                        <span>Search</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                       
                        <table class="shadow data_table" style='border-radius:.75rem!important;width:100%;margin-bottom:1rem' id='del_history_main_table'>
                            <thead style='border-bottom:2px solid black;'>
                                <tr>
                                    <th scope="col" style='padding: 0.5rem;font-size:18px'>Filename</th>
                                    <th scope="col" style='padding: 0.5rem;font-size:18px'>Date & Time Uploaded</th>
                                    <th scope="col" class="text-center action-col" style='font-size:18px'>Action</th>
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

        <!-- Hidden search fields -->
        <span id='hidden_search_input'>
            <input type="hidden" name="filename_search_h" id="filename_search_h" value="<?php if(isset($_POST['filename_search_h'])){echo htmlspecialchars($_POST['filename_search_h'], ENT_QUOTES);} ?>">
            <input type="hidden" name="from_search_h" id="from_search_h" value="<?php if(isset($_POST['from_search_h'])){echo htmlspecialchars($_POST['from_search_h'], ENT_QUOTES);} ?>">
            <input type="hidden" name="to_search_h" id="to_search_h" value="<?php if(isset($_POST['to_search_h'])){echo htmlspecialchars($_POST['to_search_h'], ENT_QUOTES);} ?>">
        </span>

        <!-- Hidden field for view details -->
        <input type="hidden" name="file_name_hidden" id="file_name_hidden">
    </form>

    <!-- SEARCH MODAL -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content fw-bold">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchModalLabel">Search Delete Upload History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="filename_search">Filename:</label>
                            <input type="text" name="filename_search" id="filename_search" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-6">
                            <label for="from_search">From Date:</label>
                            <input type="text" name="from_search" id="from_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                        <div class="col-6">
                            <label for="to_search">To Date:</label>
                            <input type="text" name="to_search" id="to_search" class="form-control date_picker" autocomplete="off" readonly>
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
        $("#filename_search").val('');
        $("#from_search").val('');
        $("#to_search").val('');
    }

    function page_click(event_action){
        if(event_action == "search"){
            var filename_search = $("#filename_search").val();
            $("#filename_search_h").val(filename_search);

            var from_search = $("#from_search").val();
            $("#from_search_h").val(from_search);

            var to_search = $("#to_search").val();
            $("#to_search_h").val(to_search);
        }

        var pageno = $("#txt_pager_pageno").val();
        var xdata = $("#hidden_search_input *").serialize() + "&pageno=" + pageno + "&event_action=" + event_action;

        jQuery.ajax({
            data: xdata,
            dataType: "json",
            type: "post",
            url: "sales_del_upload_history_ajax.php",

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

    function viewDetails(fileName){
        $("#file_name_hidden").val(fileName);
        document.forms.myforms.target = "_self";
        document.forms.myforms.method = "post";
        document.forms.myforms.action = "sales_del_upload_history2.php";
        document.forms.myforms.submit();
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
