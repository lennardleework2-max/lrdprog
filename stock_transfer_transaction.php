<?php 
require "includes/main_header.php";
$trncde = "STT";
$prog_name = "Stock Transfer";
?>         
    <style>
    #trn_sales_table td{
        word-wrap:break-word;
        white-space: normal !important;
    }

    #sales_main_table .tr_striped:nth-child(odd) {  
        background-color: rgba(0, 0, 0, 0.05);
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
                                    <div class="col-6" style='padding-left:0;margin-left:0;'>
                                        <h2 class='my-2'>Stock Transfer</h2>
                                    </div>

                                    <div class="col-6 d-flex align-items-center justify-content-end">
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
                            <table class="shadow" style='border-radius:.75rem!important;width:100%;margin-bottom:1rem' id='sales_main_table'>
                                <thead style='border-bottom:2px solid black;'>
                                    <tr>
                                    <th scope="col" style='width:80%;padding: 0.5rem 0.5rem;font-size:23px'>Stock Transfer</th>
                                    <?php
                                        if($view_crud == 1 && ($edit_crud == 1 || $delete_crud == 1)):
                                    ?>
                                    <th scope="col" class="text-center" style='width:20%;font-size:23px'>Action</th>  
                                    <?php endif;?>                 
                                    </tr>
                                </thead>

                                <tbody class='tbody_sales' id='tbody_main'>
                                </tbody>

                                <tbody class='tbody_sales_mobile' id='tbody_main_mobile'>
                                </tbody>
                            </table>

                            <nav aria-label='Page navigation' id='pager' style='font-size:14px'>
                                <ul class='pagination'>
                                    <li class='page-item' onclick="page_click_sales('first_p')">
                                        <span class='page-link' aria-label='Previous' style='display:flex;justify-content:center;align-items:center;width:60px;height:3em;width:3em'>
                                            <span aria-hidden='true'>&laquo;</span>
                                        </span>
                                    </li>

                                    <li class='page-item' onclick="page_click_sales('previous_p')">
                                        <span class='page-link' id='previous_pager' style='display:flex;align-items:center;justify-content:center;height:3em;width:7em'>
                                            Previous
                                        </span>
                                    </li>

                                    <input type='text' style='width:60px;text-align:center;font-weight:bold' name='txt_pager_pageno' id='txt_pager_pageno' disabled>

                                    <li class='page-item' style='height:3em;' onclick="page_click_sales('next_p')">
                                        <span class='page-link' id='next_pager' style='display:flex;justify-content:center;align-items:center;height:3em;width:7em'>
                                        Next
                                        </span>
                                    </li>

                                    <li class='page-item' onclick="page_click_sales('last_p')">
                                        <span class='page-link' aria-label='Next' style='display:flex;justify-content:center;align-items:center;height:3em;width:3em;'>
                                            <span aria-hidden='true' >&raquo;</span>
                                        </span>
                                    </li>
                                </ul>
                            </nav>

                            <?php } ;?>

                            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
                            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">
                            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
    
                        </div>

                    </td>

                </tr>
            </table>
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

    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content fw-bold">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Search Stock Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-4">
                            <label for="">Doc. Num.</label>
                            <input type="text" name="docnum_search" id="docnum_search" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label for="">From:</label>
                            <input type="text" name="from_search" id="from_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                        <div class="col-4">
                            <label for="">To:</label>
                            <input type="text" name="to_search" id="to_search" class="form-control date_picker" autocomplete="off" readonly>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-8">
                            <label for="">Reference Number:</label>
                            <input type="text" name="ordernum_search" id="ordernum_search" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-4">
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
                                    <option value="ordernum">Reference Num.</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-4">
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
                                    <option value="ordernum">Reference Num.</option>     
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
                    <button type="button" class="btn btn-primary" onclick="page_click_sales('search')">Search</button>
                </div>

                <input type="hidden" id="username_hidden" name="username_hidden">
                <input type="hidden" name="recid_hidden" id="recid_hidden">
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
            var crud_msg_h = $("#crud_msg_h").val();

            first_load_scroll = true;
            
            if(scroll_check == "N"){
                page_click_sales("same");  
            }
            else if(crud_msg_h == "save_exit"){
                page_click_sales("first_p");  
            }
            else if(crud_msg_h == "same_page" || crud_msg_h == "edit_exit"){
                page_click_sales("same", "file2");  
            }
            else if(pageno_h_start !== ""){
                page_click_sales("same");  
            }
            else{
                $("#txt_pager_pageno").val("1");
                page_click_sales("first_p", "first_load");  
            }

        })

        $(window).scroll(function (event){
            var scroll = $(window).scrollTop();
            $("#scrolly_hidden").val(scroll)

        }); 

        function ajaxFunc2(event ,recid, xextra){

            switch(event) {
                case "delete":
                    let userInput = confirm("Are you sure you want to delete?");
                    if (!userInput) {
                        return
                    }  
                    var xdata = "&event_action=delete&recid="+recid;
                    break;
                case "getEdit":
                    $("#recid_hidden").val(recid);
                    document.forms.myforms.target = "_self";
                    document.forms.myforms.method = "post";
                    document.forms.myforms.action = "stock_transfer_transaction_file2.php";
                    document.forms.myforms.submit();
                    break;
            }

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_invadjfile1_ajax_crud.php", 
                success: function(xdata){  
                    page_click_sales("same");
                }
            })
        }

        function next_page(event){
            document.forms.myforms.target = "_self";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "stock_transfer_transaction_file2.php";
            document.forms.myforms.submit();
        }

        function print_file2(recid){
            $("#print_file2_recid_h").val(recid);
            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "invadj_tranfile2_pdf.php";
            document.forms.myforms.submit();
        }

        function page_click_sales(event_action, xextra){
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
                url:"trn_invadjfile1_ajax_pager.php", 

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

                    var scrolly_hidden = $("#scrolly_hidden").val();
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

        if (window.matchMedia('(max-width: 576px)').matches)
        {
            $(".td_br").removeClass();
            $("#sales_main_table").addClass("table table-striped");
        }

    </script>

<?php 
require "includes/main_footer.php";
?>
