<?php 
require "includes/main_header.php";
$trncde = "POR";
$prog_name = "Purchases Order";
?>         
    <style>
    /* #trn_purchases_table tr td{
        border-style:solid;
        border-width:1px;
    } */

    #trn_purchases_table td{
        word-wrap:break-word;
        white-space: normal !important;
    }

    #data_table .tr_striped:nth-child(odd) {  
        background-color: rgba(0, 0, 0, 0.05);
        /* box-shadow: 10px 10px 10px rgba(0, 0, 0, 0.5); */
    }  

    @media only screen and (max-width: 576px) {

        #tbody_main{
            display:none;
        }

        #tbody_main_mobile{
            display:block !important;
        }
    }

    
    @media screen and (max-width: 576px) {
        #data_table tr td:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }
    }


    </style>
    <form name='myforms' id="myforms"> 
            <table class='big_table'> 
                <tr colspan=1>
                    <td colspan=1 class='td_bl'>
                        <?php
                        require 'includes/main_menu.php';
                        ?>
                    </td>

                    <td colspan=1 class="td_br">
                        <div class="container-fluid mt-2 main_br_div">

                            <div class="container-fluid my-2">
                                <div class="row">
                                    <div class="col-5" style='padding-left:0;margin-left:0;'>
                                        <h2 class='my-2'>View Sales File Upload</h2>
                                    </div>

                                    <div class="col-7  d-flex align-items-center justify-content-end">
                                        <button type="button" class="btn btn-dark m-1 fw-bold" data-bs-toggle="modal" data-bs-target="#searchModal" onclick="erase()">
                                            <i class="fas fa-search"></i>
                                            <span>Search</span> 
                                        </button>

                                    </div>
                                </div>    
                            </div>

                            <?php
                                if($view_crud == 1){
                            ?>
                            <table class="shadow" style='border-radius:.75rem!important;width:100%;margin-bottom:1rem' id='data_table'>
                                <tbody class='tbody_purchases' id='tbody_main'>
                                </tbody>

                                <tbody class='tbody_purchases_mobile' id='tbody_main_mobile'>
                                </tbody>
                            </table>


                            <nav aria-label='Page navigation' id='pager' style='font-size:14px'>
                                <ul class='pagination'>
                                    <li class='page-item' onclick="page_click_purchases('first_p')">
                                        <span class='page-link' aria-label='Previous' style='display:flex;justify-content:center;align-items:center;width:60px;height:3em;width:3em'>
                                            <span aria-hidden='true'>&laquo;</span>
                                        </span>
                                    </li>

                                    <li class='page-item' onclick="page_click_purchases('previous_p')">
                                        <span class='page-link' id='previous_pager' style='display:flex;align-items:center;justify-content:center;height:3em;width:7em'>
                                            Previous
                                        </span>
                                    </li>

                                    <input type='text' style='width:60px;text-align:center;font-weight:bold' name='txt_pager_pageno' id='txt_pager_pageno' disabled>

                                    <li class='page-item' style='height:3em;' onclick="page_click_purchases('next_p')">
                                        <span class='page-link' id='next_pager' style='display:flex;justify-content:center;align-items:center;height:3em;width:7em'>
                                        Next
                                        </span>
                                    </li>

                                    <li class='page-item' onclick="page_click_purchases('last_p')">
                                        <span class='page-link' aria-label='Next' style='display:flex;justify-content:center;align-items:center;height:3em;width:3em;'>
                                            <span aria-hidden='true' >&raquo;</span>
                                        </span>
                                    </li>
                                </ul>
                            </nav>
                            <?php }; ?>
                            
                            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
                            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">
                            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
    
                        </div>

                    </td>

                </tr>
            </table>
            <!-- HIDDEN -->
            <input type="hidden" name="recid_hidden" id="recid_hidden">
            <input type="hidden" name="userc idden" id="usercode_hidden">
            <span id='hidden_search_input'>

                <input type="hidden" name="platform_search_h" id="platform_search_h" value="<?php if(isset($_POST['platform_search_h'])){echo $_POST['platform_search_h'];}?>">
                <input type="hidden" name="ordernum_search_h" id="ordernum_search_h" value="<?php if(isset($_POST['ordernum_search_h'])){echo $_POST['ordernum_search_h'];}?>">
                <input type="hidden" name="date_uploaded_search_h" id="date_uploaded_search_h" value="<?php if(isset($_POST['date_uploaded_search_h'])){echo $_POST['date_uploaded_search_h'];}?>">
                <input type="hidden" name="file_batchno_search_h" id="file_batchno_search_h" value="<?php if(isset($_POST['file_batchno_search_h'])){echo $_POST['file_batchno_search_h'];}?>">
                <input type="hidden" name="trndte_search_h" id="trndte_search_h" value="<?php if(isset($_POST['trndte_search_h'])){echo $_POST['trndte_search_h'];}?>">
                <input type="hidden" name="itmcde_desc_search_h" id="itmcde_desc_search_h" value="<?php if(isset($_POST['itmcde_desc_search_h'])){echo $_POST['itmcde_desc_search_h'];}?>">
                <input type="hidden" name="paid_search_h" id="paid_search_h" value="<?php if(isset($_POST['paid_search_h'])){echo $_POST['paid_search_h'];}?>">

                <input type="hidden" name="sortby_1_order_h" id="sortby_1_order_h" value="<?php if(isset($_POST['sortby_1_order_h'])){echo $_POST['sortby_1_order_h'];}?>">
                <input type="hidden" name="sortby_1_field_h" id="sortby_1_field_h" value="<?php if(isset($_POST['sortby_1_field_h'])){echo $_POST['sortby_1_field_h'];}?>">
                <input type="hidden" name="sortby_2_order_h" id="sortby_2_order_h" value="<?php if(isset($_POST['sortby_2_order_h'])){echo $_POST['sortby_2_order_h'];}?>">
                <input type="hidden" name="sortby_2_field_h" id="sortby_2_field_h" value="<?php if(isset($_POST['sortby_2_field_h'])){echo $_POST['sortby_2_field_h'];}?>">
                <input type="hidden" name="crud_msg_h" id="crud_msg_h" value="<?php if(isset($_POST['crud_msg_h'])){echo $_POST['crud_msg_h'];}?>">
                <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php echo $trncde; ?>">
                <input type="hidden" name="progname_hidden" id="progname_hidden" value="<?php echo $prog_name; ?>">
                <input type="hidden" name="scrolly_hidden" id="scrolly_hidden" value="<?php if(isset($_POST['scrolly_hidden'])){echo $_POST['scrolly_hidden'];}?>">
                <input type="hidden" name="scrolly_hidden3" id="scrolly_hidden3">
                <input type="hidden" name="first_load_hidden" id="first_load_hidden">
                <input type="hidden" name="print_file2_recid_h" id="print_file2_recid_h">

                <input type="hidden" id="hiddenUploadData" name="hiddenUploadData" value="">
                <input type="hidden" name="txt_output_type" id="txt_output_type" value="">
                <input type="hidden" name="bill_number_hidden" id="bill_number_hidden">
                <input type="hidden" name="upload_date_hidden" id="upload_date_hidden">
                <input type="hidden" name="total_amount_hidden" id="total_amount_hidden">

                <!-- FOR PDF/XLSX UPLOAD -->
                <input type="hidden" name="output_heading" id="output_heading" value="Sales Return Upload Matching">
                <input type="hidden" name="output_with_filter" id="output_with_filter" value="false">

                <!--FOR VIEWING PAGE 2 -->
                <input type="hidden" name="batch_no_hidden_view" id="batch_no_hidden_view" value="<?php if(isset($_POST['batch_no_hidden_view'])){echo $_POST['batch_no_hidden_view'];}?>">
                
            </span>
            
    </form> 

    <!-- INSERT MODAL -->
    <div class="modal fade" id="insertModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Insert User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Username</label>
                            <input type="text" name="username_add" id="username_add" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Full Name</label>
                            <input type="text" name="full_name_add" id="full_name_add" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Password</label>
                            <input type="text" name="password_add" id="password_add" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-2">
                        <div class="error_msg"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="ajaxFunc2('insert')">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Accounts Receivable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for=""><b>Payment Status</b><span style='color:red'>*</span></label>
                            <select name="payment_status_edit" id="payment_status_edit" class="form-select">
                                    <option></option>
                                    <option>sent</option>
                                    <option>confirmed</option>
                                    <option>returned</option>
                            </select>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Bill Number</label>
                            <input type="text" name="bill_number_edit" id="bill_number_edit" class="form-control" autocomplete="off" readonly>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Date Uploaded</label>
                            <input type="text" name="date_uploaded_edit" id="date_uploaded_edit" class="form-control date_picker" autocomplete="off" readonly disabled>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for=""><b>Date Confirmed</b><span style='color:red'>*</span></label>
                            <input type="text" name="date_confirmed_edit" id="date_confirmed_edit" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="" style="font-weight:normal">Uploaded Amount</label>
                            <input type="number" name="computed_total_edit" id="computed_total_edit" class="form-control" autocomplete="off" disabled>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for=""><b>Encoded Total</b><span style='color:red'>*</span></label>
                            <input type="number" name="encoded_total_edit" id="encoded_total_edit" class="form-control" autocomplete="off">
                        </div>
                    </div>


                    <div class="row m-2">
                        <div class="error_msg"></div>
                    </div>
                </div>
                <div class="modal-footer modal_edit_footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="ajaxFunc2('submitEdit')">Save</button>
                </div>

                <!-- HIDDEN -->
                <input type="hidden" id="username_hidden" name="username_hidden">
                <input type="hidden" name="recid_hidden" id="recid_hidden">
            </div>
        </div>
    </div>

    <!-- SEARCH MODAL -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content fw-bold">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Search Encoded Orders from JNT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row m-3">
                        <div class="col-md-4 col-6">
                            <label for="">Platform</label>
                            <select name="platform_search" id="platform_search" class="form-control">
                                    <option></option>
                                    <option>TIKTOK</option>
                                    <option>SHOPEE</option>
                                    <option>LAZADA</option>
                                    <option>ALL</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-6">
                            <label for="">Order Number:</label>
                            <input type="text" name="ordernum_search" id="ordernum_search" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-md-4 col-6">
                            <label for="">Date Uploaded:</label>
                            <input type="text" name="date_uploaded_search" id="date_uploaded_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-md-4 col-6">
                            <label for="">File Batch Number:</label>
                            <input type="text" name="file_batchno_search" id="file_batchno_search" class="form-control" autocomplete="off">
                        </div>

                        <div class="col-md-4 col-6">
                            <label for="">Tran. Date:</label>
                            <input type="text" name="trndte_search" id="trndte_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>

                        <div class="col-md-4 col-6">
                            <label for="">Item:</label>
                            <input type="text" name="itmcde_desc_search" id="itmcde_desc_search" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-md-4 col-6">
                            <label for="">Paid:</label>
                            <select name="paid_search" id="paid_search" class="form-control">
                                <option value="all">All</option>
                                <option value="paid">Paid</option>
                                <option value="not_paid">Not Paid</option>      
                            </select>
                        </div>
                    </div>                    


                    <div class="row m-3">
                        <div class="col-md-4 col-6">
                            <div>
                                <label for="">Sort By</label>
                                <select name="sortby_1_order" id="sortby_1_order" class="form-control">
                                    <option value="Asc">Ascending</option>
                                    <option value="Desc">Descending</option>
                                </select>

                                <select name="sortby_1_field" id="sortby_1_field" class="form-control mt-2">
                                    <option value="platform">Platform</option>
                                    <option value="file_batchno">File Batch No.</option>
                                    <option value="ordernum">Order No.</option>
                                    <option value="trndte">Tran. Date</option>
                                    <option value="itmdsc">Item Description</option>
                                    <option value="untprc">Price</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 col-6">
                            <div>
                                <label for="">Sort By</label>
                                <select name="sortby_2_order" id="sortby_2_order" class="form-control">
                                    <option value="Asc">Ascending</option>
                                    <option value="Desc">Descending</option>
                                </select>

                                <select name="sortby_1_field" id="sortby_1_field" class="form-control mt-2">
                                    <option value="platform">Platform</option>
                                    <option value="file_batchno">File Batch No.</option>
                                    <option value="ordernum">Order No.</option>
                                    <option value="trndte">Tran. Date</option>
                                    <option value="itmdsc">Item Description</option>
                                    <option value="untprc">Price</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row m-2">
                        <div class="error_msg"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="page_click_purchases('search')">Search</button>
                </div>
                <input type="hidden" id="username_hidden" name="username_hidden">
                <input type="hidden" name="recid_hidden" id="recid_hidden">
            </div>
        </div>
    </div>



    <div class='modal fade' id='reset_pass_modal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'> <img src='images/logo_long.png' style='width:150px;'><?php echo $system_name;?> Says: </h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                
                <div class='modal-body'>
                    <p id='reset_modal_p'></p>
                </div>

                <div class='modal-footer'>
                    <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancel</button>
                    <span id='reset_modal_btn'>
                    </span>
                </div>
            </div>
        </div>
    </div>



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

    <input type="hidden" name="xsearch_user" id="xsearch_user">
    <script>

        var trncde = $("#trncde_hidden").val();
        var first_load_scroll = false;

        $(document).ready(function(){

            var scroll_check = localStorage.getItem("scroll_check");
            var pageno_h_start = $("#txt_pager_pageno_h").val();
            var pageno_start = $("#txt_pager_pageno").val();
            var crud_msg_h = $("#crud_msg_h").val();

            
            first_load_scroll = true;
            
            if(scroll_check == "N"){
                page_click_purchases("same");  
            }
            else if(crud_msg_h == "save_exit"){
                page_click_purchases("last_p");  
            }
            else if(crud_msg_h == "same_page" || crud_msg_h == "edit_exit"){
                page_click_purchases("same", "file2");  
            }
            else if(pageno_h_start !== ""){
                page_click_purchases("same");  
            }
            else{
                $("#txt_pager_pageno").val("1");
                page_click_purchases("first_p", "first_load");  
            }
        })

        $(window).scroll(function (event){
            var scroll = $(window).scrollTop();
            $("#scrolly_hidden").val(scroll)
        }); 

        function ajaxFunc2(xevent_action ,recid, xextra){

            switch(xevent_action) {

                case "matchSalesret":
                    jQuery.ajax({    

                        data:{
                            event_action:xevent_action,
                            batch_no:xextra
                        },
                        dataType:"json",
                        type:"post",
                        url:"sales_view_fileupload_ajax.php", 
                        success: function(xdata){  

                            //var xdata_parsed = JSON.parse(xdata);

                            if(xdata['status'] == 0){

                                var alert_data = '';

                                // Loop through the keys of noMatchFile2
                                for (var waybillNumber in xdata['noMatchFile2']) {

                                    // Create HTML dynamically
                                    // Get the value of the current waybillNumber (true or false)
                                    var isMatched = xdata['noMatchFile2'][waybillNumber];

                                    // Get the value of the current waybillNumber (true or false)
                                    var isMatched = xdata['noMatchFile2'][waybillNumber];
                                    if (isMatched === true) {

                                        alert_data += `<div class='row my-2'>
                                            <div class='d-flex align-items-center justify-content-center' style='flex-direction:row'>

                                                <div class='me-3'>
                                                    <img style='width:25px;height:auto' src='images/red_x.png'>
                                                </div>

                                                <div>
                                                    cannot find waybill number: <b>${waybillNumber}</b> match to sales return 
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
                                                    Waybill number: <b>${waybillNumber}</b> successfully matched to sales return.
                                                </div>
                                            </div>
                                        </div>`;
                                    }

                                    
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

                                const jsonData = JSON.stringify(xdata['noMatchFile2']);
                                document.getElementById("hiddenUploadData").value = jsonData;
                 

                            }else{
                                var alert_data = `
                                <div class='row my-2'>
                                    <div class='d-flex align-items-center justify-content-center' style='flex-direction:row'>

                                        <div class='me-3'>
                                            <img style='width:25px;height:auto' src='images/green_check.png'>
                                        </div>

                                        <div>
                                            All data <b> matched succesfully</b>. 
                                        </div>
                                    </div>

                                </div>`;

                                $(".alert_modal_footer").html('');
                                $(".modal-dialog-alert").removeClass('modal-lg');
                                $(".alert_modal_body").html(`${alert_data}`);
                                $(".modal_alert").modal("show");
                            }
                        }
                    })

                    break;
                case "getEdit":

                    var xdata = "event_action="+xevent_action+"&recid="+recid;
                    jQuery.ajax({    

                        data:xdata,
                        dataType:"json",
                        type:"post",
                        url:"sales_view_fileupload_ajax.php", 
                        success: function(xdata){  

                            if(xevent_action == 'getEdit'){

                                $("#bill_number_edit").val(xdata['retEdit']['bill_number']);
                                $("#date_uploaded_edit").val(xdata['retEdit']['date_uploaded']);
                                $("#date_confirmed_edit").val(xdata['retEdit']['confirmed_date']);
                                $("#computed_total_edit").val(xdata['retEdit']['computed_total']);
                                $("#encoded_total_edit").val(xdata['retEdit']['encoded_total']);

                                var payment_status_edit = xdata['retEdit']['payment_status'];
                                var recid_edit = xdata['retEdit']['recid'];

                                $(`#payment_status_edit option:contains(${payment_status_edit})`).prop('selected', true);

                                $(".modal_edit_footer").html(`
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick=\"ajaxFunc2('submitEdit','${recid_edit}')\">Save</button>`);

                                $("#editModal").modal('show');

                            }
                            
                        }
                    })

                break;

                case "submitEdit":

                    var date_confirmed =  $("#date_confirmed_edit").val();
                    var encoded_total_edit =  $("#encoded_total_edit").val();
                    if((date_confirmed == '' || date_confirmed == null) || (encoded_total_edit == "" || encoded_total_edit == null)){
                        alert("Please check required fields!");
                        return;
                    }

                var pageno = $("#txt_pager_pageno").val();
                var xdata = $("#editModal *").serialize()+"&event_action="+xevent_action+"&recid="+recid+"&pageno="+pageno;
                    jQuery.ajax({    

                        data:xdata,
                        dataType:"json",
                        type:"post",
                        url:"sales_view_fileupload_ajax.php", 
                        success: function(xdata){  

                            if(xevent_action == "submitEdit"){

                                $("#txt_pager_totalrec").val(xdata["totalrec"]);
                                $("#txt_pager_pageno").val(xdata["xpageno"]);
                                $("#txt_pager_pageno_h").val(xdata["xpageno"]);
                                $("#txt_pager_maxpage").val(xdata["maxpage"]);

                                $("#tbody_main").html(xdata["html"]);
                                $("#tbody_main_mobile").html(xdata["html_mobile"]);
                                $("#editModal").modal('hide');
                            }
                            
                        }
                    })
                break;
            }
        }

        function matched_alert(xmsg){
            alert(xmsg);
        } 

        function printUpload(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "ar_upload_pdf.php";
            document.forms.myforms.submit();
        }

        function xlsxUpload(){

            $("#txt_output_type").val('tab');

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "complete_ar_upload_pdf.php";
            document.forms.myforms.submit();
        }  

        function viewDetailed(xbatchnum){

            $("#batch_no_hidden_view").val(xbatchnum)
            document.forms.myforms.target = "_self";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "view_salesret2_matching.php";
            document.forms.myforms.submit();
        }

        function print_complete_pdf(xbatchnum){

            $("#batch_no_hidden_view").val(xbatchnum)
            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "complete_srt_upload_pdf.php";
            document.forms.myforms.submit();
        }
 

        function next_page(event){

            document.forms.myforms.target = "_self";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "trn_purchasesorderfile2.php";
            document.forms.myforms.submit();
        }

        function print_file2(recid){

            $("#print_file2_recid_h").val(recid);

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "purchasesorder_tranfile2_pdf.php";
            document.forms.myforms.submit();
        }

        function print_pdf(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            //document.forms.myforms.action = "var_dump.php";
            document.forms.myforms.action = "tranfile1_pdf.php";
            document.forms.myforms.submit();
        }

        function print_txt(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            //document.forms.myforms.action = "var_dump.php";
            document.forms.myforms.action = "tranfile1_txt.php";
            document.forms.myforms.submit();
        }


        function page_click_purchases(event_action, xextra){
            var first_load = "";
            if(xextra == "first_load"){
                first_load = "first_load";
                $("#first_load_hidden").val("first_load");
            }

            if(xextra == "file2"){
                var pageno_h = $("#txt_pager_pageno_h").val();
                $("#txt_pager_pageno").val(pageno_h);
            }

            if(event_action == "search"){

                $("#first_load_hidden").val("");

                var platform_search = $("#platform_search").val();
                $("#platform_search_h").attr('value', platform_search);

                var ordernum_search = $("#ordernum_search").val();
                $("#ordernum_search_h").attr('value', ordernum_search);

                var date_uploaded_search = $("#date_uploaded_search").val();
                $("#date_uploaded_search_h").attr('value', date_uploaded_search);

                var file_batchno_search = $("#file_batchno_search").val();
                $("#file_batchno_search_h").attr('value', file_batchno_search);

                var trndte_search = $("#trndte_search").val();
                $("#trndte_search_h").attr('value', trndte_search);

                var itmcde_desc_search = $("#itmcde_desc_search").val();
                $("#itmcde_desc_search_h").attr('value', itmcde_desc_search);

                var paid_search = $("#paid_search").val();
                $("#paid_search_h").attr('value', paid_search);


                var sortby_1_order = $("#sortby_1_order").val();
                $("#sortby_1_order_h").val(sortby_1_order);

                var sortby_1_field = $("#sortby_1_field").val();
                $("#sortby_1_field_h").val(sortby_1_field);

                var sortby_2_order = $("#sortby_2_order").val();
                $("#sortby_2_order_h").val(sortby_2_order);

                var sortby_2_field = $("#sortby_2_field").val();
                $("#sortby_2_field_h").val(sortby_2_field);
                
            }

            var pageno = $("#txt_pager_pageno").val();
            var xdata= $("#hidden_search_input *").serialize()+"&first_load="+first_load+"&pageno="+pageno+"&event_action="+event_action;
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"sales_view_fileupload_ajax.php", 

                success: function(xdata){  

                    $("#txt_pager_totalrec").val(xdata["totalrec"]);
                    $("#txt_pager_pageno").val(xdata["xpageno"]);
                    $("#txt_pager_pageno_h").val(xdata["xpageno"]);
                    $("#txt_pager_maxpage").val(xdata["maxpage"]);

                    $("#tbody_main").html(xdata["html"]);
                    $("#tbody_main_mobile").html(xdata["html_mobile"]);

                    if(event_action == "search"){
                        $("#xsearch_user").val("search");
                        $(".search_text_input_user").attr("hidden-value" , xdata["input_hidden"]);
                        $(".dropdown_dd_user").attr("hidden-value" , xdata["dd_hidden"]);
                        $("#searchModal").modal("hide");
                    }

                    var pageno_h_start = $("#txt_pager_pageno_h").val();
                    var pageno_start = $("#txt_pager_pageno").val();
                    var crud_msg_h = $("#crud_msg_h").val();

                    var scrolly_hidden = $("#scrolly_hidden").val();
                    var scrolly_hidden3 = $("#scrolly_hidden3").val();

                    var scroll_check = localStorage.getItem("scroll_check");

                    if(scroll_check == "Y"){

                        window.scrollTo(0, scrolly_hidden);
                        localStorage.setItem("scroll_check", "N");
                    }

                }
            })
        }
        
        function erase(){

            $("#platform_search").val($("#platform_search option:first").val());
            $("#ordernum_search").val('');
            $("#date_uploaded_search").val('');
            $("#file_batchno_search").val('');
            $("#trndte_search").val('');
            $("#itmcde_desc_search").val('');
            $("#paid_search").val($("#paid_search option:first").val());
       
            $("#sortby_1_order").val($("#sortby_1_order option:first").val());
            $("#sortby_1_field").val($("#sortby_1_field option:first").val());
            $("#sortby_2_order").val($("#sortby_2_order option:first").val());
            $("#sortby_2_field").val($("#sortby_2_field option:first").val());

        }

        function userAccess(username_access,usercode_access){

            $("#usercode_hidden").val(usercode_access);
            document.forms.myforms.method='POST';
            document.forms.myforms.target='_self';
            document.forms.myforms.action='utl_user_access.php';
            document.forms.myforms.submit();

        }

        function check_enter_user(evt) {
            var ASCIICode = (evt.which) ? evt.which : evt.keyCode
            if(ASCIICode == 13){
                page_click_purchases("search");
            }
        }

        //on close modal remove error message
        $('#insertModal').on('hidden.bs.modal', function () {
            $(".error_msg").html("");
        });
        $('#insertModal').on('hidden.bs.modal', function (e) {
            $("#username_add").val("");
            $("#full_name_add").val("");
            $("#password_add").val("");
        })
        $('#editModal').on('hidden.bs.modal', function () {
            $(".error_msg").html("");
        });

        if (window.matchMedia('(max-width: 576px)').matches)
        {
            $(".td_br").removeClass();
            $("#data_table").addClass('table table-striped');
        }

    </script>

<?php 
require "includes/main_footer.php";
?>



