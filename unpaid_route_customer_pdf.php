<?php

require "includes/main_header.php";
$trncde = "SAL";

?>

    <style>
        @media only screen and (max-width: 768px) {
            #docnum_table{
                width:350px!important;
            }
        }
    </style>


    <form name='myforms' id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)"> 

        <table class='big_table'> 

            <tr colspan=1>
                <td colspan=1 class='td_bl'>
                    <?php
                        require 'includes/main_menu.php';
                    ?>
                </td>

                <td colspan=1 class="td_br" id="td_br">

                    <div class="container-fluid w-100 h-100">

                        <div class="row h-100 w-100 justify-content-center align-items-center">
                            <table style='height:auto;background-color:white;width:40%' id='docnum_table'>
                                <tr>

                                    <td colspan="2" class="text-center py-4">
                                        <h3>Unpaid Sales Route</h3>
                                    </td>

                                </tr>



                                <tr style='height:12.5%'>

                                    <td colspan="1">

                                        <div class="w-100 h-100 d-flex justify-content-end align-items-top pe-1">

                                            <div style="width:80%">

                                                <label for="">Date From:</label>

                                                <input type="text" class="form-control date_picker" name="date_from" id="date_from" autocomplete="off" readonly>

                                            </div>

                                        </div>

                                    </td>

                                    <td colspan="1">

                                        <div class="w-100 h-100 d-flex justify-content-start align-items-top ps-1">

                                            <div style="width:80%">

                                                <label for="">Date To:</label>

                                                <input type="text" class="form-control date_picker" name="date_to" id="date_to" autocomplete="off" readonly>

                                            </div>
                                        </div>
                                    </td>

                                </tr>


                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <label for="" class="my-1">Unpaid Customer :</label>
                                                        <div class="input-group mb-3">
                                                            <input type="text" class="form-control" name="cus_add" id="cus_add"  autocomplete="off" placeholder="Enter item to search" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func('add')" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                                                            </div>
                                                        </div>                            
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </td>
                                </tr>

                                <!-- <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By <b>Route: </b></label>

                                                <select class="form-select" id="orderby_route" name="orderby_route" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>
                                    

                                    </td>


                                </tr> -->
<!-- 
                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By <b>Buyer: </b></label>

                                                <select class="form-select" id="orderby_buyer" name="orderby_buyer" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>
                                    

                                    </td>


                                </tr> -->

                                <!-- <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By <b>Order Num. : </b></label>

                                                <select class="form-select" id="orderby_ordernum" name="orderby_ordernum" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>
                                    

                                    </td>


                                </tr> -->


                                <!-- <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By: <b>Paydate (sales)</b></label>

                                                <select class="form-select" id="orderby_paydate_sales" name="orderby_paydate_sales" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>


                                    </td>


                                </tr> -->

                                <!-- <tr style='height:7.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <input type='checkbox' name='chk_paid' id='chk_paid' checked> (Sales)Paid &nbsp &nbsp &nbsp
                                                <input type='checkbox' name='chk_unpaid' id='chk_unpaid' checked> Unpaid
                                            </div>
                                        </div>

                                    </td>

                                </tr> -->

                                <!-- <tr style='height:7.5%'>

                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <input type='checkbox' name='chk_paid_smn' id='chk_paid_smn' checked> (Salesman)Paid &nbsp &nbsp &nbsp
                                                <input type='checkbox' name='chk_unpaid_smn' id='chk_unpaid_smn' checked> Unpaid
                                            </div>
                                        </div>

                                    </td>

                                </tr> -->

                                <tr>

                                    <td colspan="2" class="py-4">



                                        <div class="row d-flex justify-content-center align-items-center">

                                            <div class="col-4">

                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">

                                            </div>

                                        
                                            <!-- <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1"  class="btn btn-primary" value="Export as xls" onclick="exp_txt()">
                                            </div> -->
                                        </div>

                                    </td>

                                </tr>

                            </table>

                        </div>

                    

                    </div>

                </td>
            </tr>

        </table>

        <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php echo $trncde; ?>">

        <input type="hidden" name="txt_output_type" id="txt_output_type">
        
        <input type="hidden" name="xevent_itmsearch_hidden" id="xevent_itmsearch_hidden">

        <input type="hidden" name="cus_add_hidden" id="cus_add_hidden">

        <input type="hidden" name="route_id_hidden" id="route_id_hidden">

        <div class="modal fade view_itm_search" id="view_itm_search" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class='modal_view_item'>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="cus_view" id="cus_view" placeholder="" autocomplete="off">
                            <div class="input-group-append">
                                <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func_inmodal()" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                        
                        <div class="error_msg_itm_view">
                                
                        </div>
                    </div>

                    <div class='html_itm_view_data' style='max-height:70vh;overflow:auto'>
                            
                    </div>
                </div>
                <div class="modal-footer back_btn_modal_footer">

                </div>
                </div>
            </div>
        </div>



    </form>





    <script>


            $(document).ready(function(){
                $('#cus_view').on('keypress', function(event) {
                    if (event.key === 'Enter' || event.keyCode === 13) {
                        search_itm_func_inmodal();
                    }
                });

                $('#cus_add').on('keypress', function(event) {
                    if (event.key === 'Enter' || event.keyCode === 13) {
                        search_itm_func('add');
                    }
                });
            });  
            

            function search_itm_func_inmodal(){

                var xevent_itmsearch_hidden_val = $("#xevent_itmsearch_hidden").val();
                var search_cus  = $("#cus_view").val();

                xdata = "event_action=search_itm&search_cus="+search_cus+"&event_action_itmsearch="+xevent_itmsearch_hidden_val;

                jQuery.ajax({    
                    data:xdata,
                    dataType:"json",
                    type:"post",
                    url:"unpaid_route_customer_pdf_ajax.php", 
                    success: function(xdata){  

                        if(xdata['status'] == 1){
                            $(".html_itm_view_data").html(xdata['html']);
                            $(".error_msg_itm_view").html('');
                        }else{
                            $(".error_msg_itm_view").html(`
                                <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                            `);
                        }
                        
                    }
                })      
            }     
            
            function search_itm_func(xevent){

                if(xevent == 'add'){
                    var search_cus  = $("#cus_add").val();

                    // alert("BRUH"+search_cus);

                    $("#cus_view").val(search_cus);
                    $(".back_btn_modal_footer").html(`<button type="button" class="btn btn-danger" onclick="back_from_view_func('add')">Back</button>`);
                }



                $('#xevent_itmsearch_hidden').val(xevent);

                var date_from = $("#date_from").val();
                var date_to = $("#date_to").val();

                xdata = "event_action=search_itm&search_cus="+search_cus+"&event_action_itmsearch="+xevent+"&date_from="+date_from+"&date_to="+date_to;

                jQuery.ajax({    
                    data:xdata,
                    dataType:"json",
                    type:"post",
                    url:"unpaid_route_customer_pdf_ajax.php", 
                    success: function(xdata){  

                        if(xdata['status'] == 0){
                            if(xevent == 'add'){
                                $(".error_msg_add_modal").html(`
                                        <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                    `);
                            }else if (xevent == 'edit'){    
                                $(".error_msg_edit_modal").html(`
                                        <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);   ;
                            }

                        }else{

                            $(".html_itm_view_data").html(xdata['html']);
                         
                            $(".error_msg_itm_view").html("");
                            $("#view_itm_search").modal("show");
                        }
                    }
                })      
            }  

            function select_item_modal(xevent_action,xbuyer_name, xbuyer_id,xroute_id){

                if(xevent_action == 'select'){

                    $(".error_msg_add_modal").html("");
                    $("#cus_add_hidden").val(xbuyer_id);
                    $("#cus_add").val(xbuyer_name);
                    $("#route_id_hidden").val(xroute_id);
                    $("#cus_add").prop("readonly", true);
                    $("#view_itm_search").modal("hide");
                    // $("#insert_modal_sales").modal("show");
                }

                $(".error_msg_itm_view").html("");


            }

            function exp_pdf(){


                if ($("#cus_add").is("[readonly]")) {

                    $("#txt_output_type").val("");

                    document.forms.myforms.target = "_blank";

                    document.forms.myforms.method = "post";

                    //document.forms.myforms.action = "trndate_rep_sales.php";

                    document.forms.myforms.action = "unpaid_route_customer_rep.php";

                    //document.forms.myforms.action = "var_dump.php";

                    document.forms.myforms.submit();
                }else{
                    alert("Select a buyer first");
                }




            }


            function exp_txt(){


                $("#txt_output_type").val("tab");



                document.forms.myforms.target = "_blank";

                document.forms.myforms.method = "post";

                document.forms.myforms.action = "sales_salesman_pdf.php";

                document.forms.myforms.submit();

            }

    </script>



<?php 

require "includes/main_footer.php";

?>



