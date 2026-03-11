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
                                    <div class="col-2" style='padding-left:0;margin-left:0;'>
                                        <h2 class='my-2'>Purchases Order</h2>
                                    </div>

                                    <div class="col-10  d-flex align-items-center justify-content-end">
                                        <?php
                                            if($add_crud == 1):
                                        ?>
                                            <button type='button' class="btn btn-success my-2" value="Add Record" onclick="next_page('add')">
                                                <span style='font-weight:bold'>
                                                    Add Record
                                                </span>
                                                <i class='fas fa-plus' style='margin-left: 3px'></i>
                                            </button>
                                        <?php endif;  ?>

                                        <?php
                                            if($export_crud == 1):
                                        ?>
                                            <!-- <button type='button' class='btn btn-primary m-2 fw-bold dropdown-toggle' data-bs-toggle='dropdown'>
                                                <span>
                                                    Print
                                                </span>
                                                <i class='bi bi-printer-fill pt-1'></i>
                                            </button>

                                            <ul class='dropdown-menu' aria-labelledby='dropdown_export'>
                                                <li class='dropdown-item' onclick='print_pdf()'><i class='fas fa-file-pdf'></i><span style='margin-left:7px;font-size:15px;font-family:arial'><b class='dd_text'>Pdf File</b></span></li>
                                                <li class='dropdown-item' onclick='print_txt()'><i class='fas fa-file-alt'></i><span style='margin-left:7px;font-size:15px;font-family:arial'><b class='dd_text'>Txt File</b></span></li>
                                            </ul>   -->
                                        <?php endif;  ?>

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
                                <thead style='border-bottom:2px solid black;'>
                                    <tr>
                                    <th scope="col" style='width:80%;padding: 0.5rem 0.5rem;font-size:23px'>Purchases Order</th>
                                    <?php
                                        if($view_crud == 1 && ($edit_crud == 1 || $delete_crud == 1)):
                                    ?>
                                    <th scope="col" class="text-center" style='width:20%;font-size:23px'>Action</th>  
                                    <?php endif;?>                 
                                    </tr>
                                </thead>

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
            <input type="hidden" name="usercode_hidden" id="usercode_hidden">
            <span id='hidden_search_input'>
                <input type="hidden" name="orderby_search_h" id="orderby_search_h" value="<?php if(isset($_POST['orderby_search_h'])){echo $_POST['orderby_search_h'];}?>">
                <input type="hidden" name="ordernum_search_h" id="ordernum_search_h" value="<?php if(isset($_POST['ordernum_search_h'])){echo $_POST['ordernum_search_h'];}?>">
                <input type="hidden" name="docnum_search_h" id="docnum_search_h" value="<?php if(isset($_POST['docnum_search_h'])){echo $_POST['docnum_search_h'];}?>">
                <input type="hidden" name="from_search_h" id="from_search_h" value="<?php if(isset($_POST['from_search_h'])){echo $_POST['from_search_h'];}?>">
                <input type="hidden" name="to_search_h" id="to_search_h" value="<?php if(isset($_POST['to_search_h'])){echo $_POST['to_search_h'];}?>">
                <input type="hidden" name="cusname_search_h" id="cusname_search_h" value="<?php if(isset($_POST['cusname_search_h'])){echo $_POST['cusname_search_h'];}?>">
                <input type="hidden" name="unpaid_search_h" id="unpaid_search_h" value="<?php if(isset($_POST['unpaid_search_h'])){echo $_POST['unpaid_search_h'];}?>">
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
                    <h5 class="modal-title" id="exampleModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Username</label>
                            <input type="text" name="username_edit" id="username_edit" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Full Name</label>
                            <input type="text" name="full_name_edit" id="full_name_edit" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-2">
                        <div class="error_msg"></div>
                    </div>
                </div>
                <div class="modal-footer">
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
                    <h5 class="modal-title" id="exampleModalLabel">Search purchases Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-md-4 col-6">
                            <label for="">Doc. Num.</label>
                            <input type="text" name="docnum_search" id="docnum_search" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-md-4 col-6">
                            <label for="">From:</label>
                            <input type="text" name="from_search" id="from_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                        <div class="col-md-4 col-6">
                            <label for="">To:</label>
                            <input type="text" name="to_search" id="to_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-md-3 col-6">
                            <label for="">Supplier:</label>
                            <input type="text" name="cusname_search" id="cusname_search" class="form-control" autocomplete="off">
                        </div>

                        <div class="col-md-3 col-6">
                            <label for="">Order Number:</label>
                            <input type="text" name="ordernum_search" id="ordernum_search" class="form-control" autocomplete="off">
                        </div>

                        <div class="col-md-6 col-6">
                            <label for="">Ordered By:</label>
                            <input type="text" name="orderby_search" id="orderby_search" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3" style='display:none'>
                        <div class="col-12">
                            <input class="form-check-input" type="checkbox" name="unpaid_search" id="unpaid_search">
                            <label class="form-check-label" for="flexCheckChecked">
                                Unpaid
                            </label>
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
                                    <option value="none">None</option>
                                    <option value="docnum">Doc. No.</option>
                                    <option value="trndte">Tran. Date</option>
                                    <option value="suppcde">Supplier</option>
                                    <option value="ordernum">Ordered Num.</option>
                                    <option value="orderby">Ordered By</option>
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

                                <select name="sortby_2_field" id="sortby_2_field" class="form-control mt-2">
                                    <option value="none">None</option>
                                    <option value="docnum">Doc. No.</option>
                                    <option value="trndte">Tran. Date</option>
                                    <option value="suppcde">Supplier</option>
                                    <option value="ordernum">Ordered Num.</option>     
                                    <option value="orderby">Ordered By</option>
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

                <!-- HIDDEN -->
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

        function ajaxFunc2(event ,recid, xextra){

            switch(event) {
                case "delete":
                    var xdata = "&event_action=delete&recid="+recid;

                    let userInput = confirm("Are you sure you want to delete?");

                    //cancelled delete
                    if (!userInput) {
                        return
                    } 

                    break;
                case "getEdit":

                    $("#recid_hidden").val(recid);
                    document.forms.myforms.target = "_self";
                    document.forms.myforms.method = "post";
                    document.forms.myforms.action = "trn_purchasesorderfile2.php";
                    document.forms.myforms.submit();
                    break;
            }

            jQuery.ajax({    

                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasesorderfile1_ajax_crud.php", 

                success: function(xdata){  
                    page_click_purchases("same");
                }
            })
        }

        function matched_alert(xmsg){
            alert(xmsg);
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
            //document.forms.myforms.action = "var_dump.php";
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

                var orderby_search = $("#orderby_search").val();
                $("#orderby_search_h").attr('value', orderby_search);

                var ordernum_search = $("#ordernum_search").val();
                $("#ordernum_search_h").val(ordernum_search);

                var docnum_search = $("#docnum_search").val();
                $("#docnum_search_h").val(docnum_search);

                var from_search = $("#from_search").val();
                $("#from_search_h").val(from_search);
 
                var to_search = $("#to_search").val();
                $("#to_search_h").val(to_search);

                var cusname_search = $("#cusname_search").val();
                $("#cusname_search_h").val(cusname_search);

                var unpaid_search = $("#unpaid_search").is(":checked");

                if(unpaid_search == true){
                    $("#unpaid_search_h").val(1);
                }else{
                    $("#unpaid_search_h").val(0);
                }

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
            var xdata = xdata+"&trncde="+trncde;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasesorderfile1_ajax_pager.php", 

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
            $("#orderby_search").val('');
            $("#docnum_search").val('');
            $("#from_search").val('');
            $("#to_search").val('');
            $("#cusname_search").val('');
            $("#ordernum_search").val('');
            $("#unpaid_search").prop("checked" ,false);
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



