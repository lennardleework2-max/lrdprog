<?php 
require "includes/main_header.php";

// $select_db_salesfile1='SELECT * FROM users WHERE usercode=?';
// $stmt_salesfile1	= $link->prepare($select_db_salesfile1);
// $stmt_salesfile1->execute(array($_POST["usercode_hidden"]));
// while($rs_salesfile1 = $stmt_salesfile1->fetch()){
    
//     $username   = $rs_salesfile1['userdesc'];
//     $full_name  = $rs_salesfile1['full_name'];
//     $password   = $rs_salesfile1['password'];
//     // loop here
// }


if(isset($_POST['recid_hidden']) && !empty($_POST['recid_hidden'])){
    $select_db_docnum1='SELECT * FROM tranfile1 WHERE recid=?';
    $stmt_docnum1	= $link->prepare($select_db_docnum1);
    $stmt_docnum1->execute(array($_POST["recid_hidden"]));

    while($rs_docnum1 = $stmt_docnum1->fetch()){

        if(!empty($rs_docnum1['trndte'])){
            $rs_docnum1['trndte'] = date("m-d-Y",strtotime($rs_docnum1['trndte']));
            $rs_docnum1['trndte'] = str_replace('-','/',$rs_docnum1['trndte']);
        }else{
            $rs_docnum1['trndte'] = NULL;
        }

        if(!empty($rs_docnum1['paydate'])){
            $rs_docnum1['paydate'] = date("m-d-Y",strtotime($rs_docnum1['paydate']));
            $rs_docnum1['paydate'] = str_replace('-','/',$rs_docnum1['paydate']);
        }else{
            $rs_docnum1['paydate'] = NULL;
        }

        if(isset($rs_docnum1['trntot'])){
            $rs_docnum1['trntot'] = number_format($rs_docnum1['trntot'],2);
        }

        $docnum  = $rs_docnum1['docnum'];
        $orderby  = $rs_docnum1['orderby'];
        $trndate  = $rs_docnum1['trndte'];
        $trntot  = $rs_docnum1['trntot'];
        $shipto  = $rs_docnum1['shipto'];
        $cuscde  = $rs_docnum1['cuscde'];
        $suppcde  = $rs_docnum1['suppcde'];
        $paydate  = $rs_docnum1['paydate'];
        $payment_details  = $rs_docnum1['paydetails'];
        $remarks  = $rs_docnum1['remarks'];
        $ordernum  = $rs_docnum1['ordernum'];

    }
}else{
    $select_db_docnum="SELECT * FROM tranfile1  WHERE trncde='".$_POST['trncde_hidden']."' ORDER BY docnum DESC LIMIT 1";
    $stmt_docnum	= $link->prepare($select_db_docnum);
    $stmt_docnum->execute();
    $rs_docnum = $stmt_docnum->fetch();
    
    $docnum  = Lnexts($rs_docnum['docnum']);
    if(empty($rs_docnum)){
        $docnum  = "PUR-00001";
    }
    $trndate = NULL;
    $trntot = '';
    $customer_name = '';
    $suppcde  = '';
    $orderby = '';
    $shipto = '';
    $paydate  = NULL;
    $payment_details  = '';
    $remarks  = '';
    $ordernum = '';
}



?>

        <style>
    
        @media only screen and (min-width: 576px) {

            #btns_useraccess{
                display:flex;
                align-items:center;
                justify-content:flex-end;
            }
        }
        @media only screen and (max-width: 576px) {
            #btns_useraccess{
                justify-content:center;
            }
        }

        /* #main_chk_div{
            transform: scaleY(0);    
            transform-origin: top;
            transition: transform 1s ease;
        }

        .height_trans{
            transform: scaleY(1)!important;
        } */

        /* #tr_access_data{
            transform: scaleY(0);    
            transform-origin: top;
            transition: transform 0.75s ease;
        } */

        /* #main_chk_div{
            transform: scaleY(0);    
            transform-origin: top;
            transition: transform 0.65s ease;
        }

        .height_trans{
            transform: scaleY(1)!important;
        } */

        /* #main_chk_div{
            max-height:0 !important;
            transition: max-height 0.25s ease-in;
        }
        .height_trans{
            max-height:1000px !important;
            transition: max-height 0.25s ease-in;
        } */



        </style>
        <form name='myforms' id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)"> 
            <table class='big_table'> 
                <tr colspan=1>
                    <td colspan=1 class='td_bl'>
                        <?php
                            require 'includes/main_menu.php';
                        ?>
                    </td>

                    <td colspan=1 style="height:100%" class='td_br' id='td_br'>

                        <div class="container-fluid w-100 h-100 d-flex justify-content-center">
                         
                                <table class="bg-white w-75 shadow rounded user_access_tbl my-4" style="border-radius: 0.75rem!important;border-collapse:collapse;height:400px">

                                    <tr class="m-1 salesfile1" style="border-bottom:3px solid #cccccc">
                                        <td colspan="3"> 

                                            <div class="row">

                                                <div class="col-2">
                                                    <div class="m-2">
                                                        <h2>Purchases</h2>
                                                    </div>
                                                </div>

                                                <div class="col-10 d-flex align-items-center justify-content-end">

                                                    <div class="m-2 row">
                                                        <div class="col-md-3 col-6 d-flex justify-content-end ps-0 pe-1">
                                                                <input type="button" class="btn btn-danger fw-bold" style="width:100px;" value="Back" onclick="back_page()">                                           
                                                        </div>

                                                        <div class="col-md-4 col-6 d-flex justify-content-end ps-0 pe-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:150px;" value="Save and Exit" onclick="salesfile2('save_exit')">
                                                        </div>

                                                        <div class="col-md-5 col-12 d-flex justify-content-start justify-content-md-end ps-1 pe-1 mt-1 mt-md-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:175px;" value="Save and Add Next" onclick="salesfile2('save_new')">
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </td>
                                    </tr>


                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">

                                        <td>
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Doc. Num:</label>
                                                <input type="text" class="form-control" name="docnum_1" style="font-weight:bold" id="docnum_1" value="<?php echo $docnum;?>" readonly>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Tran. Date:<span style="color:red">*</span></label>
                                                <input type="text" class="form-control date_picker"  name="trndte_1" id="trndte_1" value="<?php echo $trndate;?>" readonly>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Total:</label>
                                                <input type="text" class="form-control" name="trntot_1" id="trntot_1" value="<?php echo $trntot;?>" autocomplete="off" readonly>
                                            </div>
                                        </td>                                    
                                    </tr>


                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                           

                                         
                                        <td>
                                            <div class="m-3">
                                       
                                                <label for="" style="font-weight:bold">Supplier:</label>
                                                <select name="cusname_1" id="cusname_1" class="form-control">
                                                    <?php
                                                        $select_cuscde='SELECT * FROM supplierfile ORDER BY suppdsc ASC';
                                                        $stmt_cuscde	= $link->prepare($select_cuscde);
                                                        $stmt_cuscde->execute();
                                                        while($rs_cuscde = $stmt_cuscde->fetch()):
                                                    ?>
                                                    <?php
                                                    $selected = '';
                                                    if($suppcde == $rs_cuscde['suppcde']){
                                                        $selected = 'selected';
                                                    }
                                                    ?>
                                                    <option value="<?php echo $rs_cuscde['suppcde'];?>" <?php echo $selected;?>><?php echo $rs_cuscde['suppdsc'];?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                                    
                                   
                                            </div>
                                        </td>

                                        <td>
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Order Number:</label>
                                                <input type="text" class="form-control" name="ordernum_1" id="ordernum_1" value="<?php echo $ordernum;?>" autocomplete="off">
                                            </div>                                       
                                        </td>

                                        <td>
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Order By:</label>
                                                <input type="text" class="form-control" name="orderby_1" id="orderby_1" value="<?php echo $orderby;?>" autocomplete="off">
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Ship To:</label>
                                                <input type="text" class="form-control" name="shipto_1" id="shipto_1" value="<?php echo $shipto;?>" autocomplete="off">
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="m-3">
                                                <label style="font-weight:bold">Pay Date:</label>
                                                <input type="text" class="form-control date_picker" name="paydate_1" id="paydate_1" value="<?php echo $paydate;?>" readonly>
                                            </div>
                                        </td>

                                        <td colspan="2">
                                            <div class="m-3">
                                                <label for="" style="font-weight:bold">Payment Details:</label>
                                                <input type="text" class="form-control" name="payment_details_1" id="payment_details_1" value="<?php echo $payment_details;?>" autocomplete="off">
                                            </div>
                                        </td>                                    
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3">
                                                <label style="font-weight:bold">Remarks:</label>
                                                <textarea name="remarks_1" id="remarks_1" rows="3" class="form-control"><?php echo $remarks; ?></textarea>
                                            </div>
                                        </td>                                  
                                    </tr>

                                    <tr class="m-1 salesfile1" id="tr_access_data">
                                        <td id="main_chk_div" colspan="3">
                                        </td>
                                    
                                    </tr>
                            
                                </table>
                      
                        </div>

                    </td>

                </tr>
            </table>

            <div class='modal fade' id='alert_modal_system' tabindex='-1'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'> <img src="<?php echo $logo_dir ;?>" style="<?php echo 'width:'.$logo_width.';height:'.$logo_height.';';?>">&nbsp;<?php echo $system_name;?> Says: </h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                    
                        <div class='modal-body alert_modal_body_system' id="alert_modal_body_system">
                            <b>Successfully Saved.</b>
                        </div>

                    </div>
               
                </div>
            </div>

            <!-- HIDDEN -->
            <input type="hidden" name="event_action" id="event_action">
            <input type="hidden" name="usercode_access_hidden" id="usercode_access_hidden" value="<?php if(isset($_POST["usercode_hidden"])){echo $_POST["usercode_hidden"];}?>">
            <input type="hidden" name='docnum_hidden' id="docnum_hidden" value="<?php echo $docnum; ?>">

            <div class="modal fade" id="insert_modal_sales" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Purchase</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item Code</label>
                                    <select name="itmcde_add" id="itmcde_add" class="form-control">
                                        <?php
                                            $select_itemfile='SELECT * FROM itemfile ORDER BY itmdsc ASC';
                                            $stmt_itemfile	= $link->prepare($select_itemfile);
                                            $stmt_itemfile->execute();
                                        
                                            while($rs_itemfile = $stmt_itemfile->fetch()):
                                        ?>
                                        <option value="<?php echo $rs_itemfile['itmcde'];?>"><?php echo $rs_itemfile['itmdsc'];?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Quantity</label>
                                    <input type="text" name="itmqty_add" id="itmqty_add" class="form-control" autocomplete="off" oninput="calcTotal('add')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Price</label>
                                    <input type="text" name="price_add" id="price_add" class="form-control" autocomplete="off" oninput="calcTotal('add')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Amount</label>
                                    <input type="text" name="amount_add" id="amount_add" class="form-control" autocomplete="off" readonly>
                                </div>
                            </div>


                            <div class="row m-2">
                                <div class="error_msg"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="salesfile2('insert')">Save</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="edit_modal_sales" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Edit Purchase</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item</label>
                                    <select name="itmcde_edit" id="itmcde_edit" class="form-control">
                                        <?php
                                            $select_itemfile='SELECT * FROM itemfile ORDER BY itmdsc ASC';
                                            $stmt_itemfile	= $link->prepare($select_itemfile);
                                            $stmt_itemfile->execute();
                                        
                                            while($rs_itemfile = $stmt_itemfile->fetch()):
                                        ?>
                                        <option value="<?php echo $rs_itemfile['itmcde'];?>"><?php echo $rs_itemfile['itmdsc'];?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Quantity</label>
                                    <input type="text" name="itmqty_edit" id="itmqty_edit" class="form-control" autocomplete="off" oninput="calcTotal('edit')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Price</label>
                                    <input type="text" name="price_edit" id="price_edit" class="form-control" autocomplete="off" oninput="calcTotal('edit')">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Amount</label>
                                    <input type="text" name="amount_edit" id="amount_edit" class="form-control" autocomplete="off" readonly>
                                </div>
                            </div>


                            <div class="row m-2">
                                <div class="error_msg"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="salesfile2('submitEdit')">Save</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="salesfile2_recid_hidden" id="salesfile2_recid_hidden">
            </div>


            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">
                
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
            <input type="hidden" name="crud_msg_h2" id="crud_msg_h2">
            <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php if(isset($_POST['trncde_hidden'])){echo $_POST['trncde_hidden'];}?>">
            <input type="hidden" name="scrolly_hidden" id="scrolly_hidden" value="<?php if(isset($_POST['scrolly_hidden'])){echo $_POST['scrolly_hidden'];}?>">
            <input type="hidden" name="scrolly_hidden2" id="scrolly_hidden2" value="Y">
        </form>

        <script>

        var trncde = $("#trncde_hidden").val();

        $(document).ready(function(){
            var docnum = $("#docnum_hidden").val();
            //xdata = "docnum="+docnum+"trncde="+trncde;
            // jomer 5-8-2025
            xdata = {
                'docnum' : docnum,
                'trncde' : trncde
            }
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
                    success: function(xdata){  
                        $("#main_chk_div").html(xdata["html"]);
                    }

            })
        });

        function calcTotal(event){

            if(event == "add"){
                var price=$("#price_add").val();
                var qty=$("#itmqty_add").val();
            }

            if(event == "edit"){
                var price=$("#price_edit").val();
                var qty=$("#itmqty_edit").val();
            }

            price = price.replaceAll(",","");
            qty = qty.replaceAll(",","");

            var amount=(price*qty).toFixed(2);

            if(event == "add"){
                $("#amount_add").val(amount);
            }
            if(event == "edit"){
                $("#amount_edit").val(amount);
            }
            
        }

        function print_pdf(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "pdf_test.php";
            document.forms.myforms.submit();
        }

        var back_check = false;

        function back_page(){

            if(back_check == false){
                $("#crud_msg_h").val("same_page");
            }

            // var check =   $("#crud_msg_h2").val();
            // if(check == "insert_new"){
            //     $("#crud_msg_h").val("save_exit");
            // }

            localStorage.setItem("scroll_check", "Y");

            document.forms.myforms.target = "_self";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "trn_purchasefile1.php";
            document.forms.myforms.submit();
        }

        var save_new = false;


        $('#itmcde_add').on('change', function () {
            var selectedValue = $(this).val(); // gets the value of selected option
            var xdata  = "event_action=add_get&itmcde_add="+selectedValue;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_itm.php", 
                success: function(xdata){  
                    if(xdata["price_ret"]){

                        var itmqty = $("#itmqty_add").val();
                        $("#price_add").val(xdata["price_ret"]);
                        $("#amount_add").val((xdata["price_ret"]* itmqty));
                    }else{
                        $("#price_add").val('');
                        $("#amount_add").val('');
                    }
                }
            })
        });

        $('#itmcde_edit').on('change', function () {
            var selectedValue = $(this).val(); // gets the value of selected option
            var xdata  = "event_action=add_get&itmcde_add="+selectedValue;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_itm.php", 
                success: function(xdata){  
                    if(xdata["price_ret"]){

                        var itmqty = $("#itmqty_edit").val();
                        $("#price_edit").val(xdata["price_ret"]);
                        $("#amount_edit").val((xdata["price_ret"]* itmqty));
                    }else{
                        $("#price_edit").val('');
                        $("#amount_edit").val('');
                    }
                }
            })
        });




        function salesfile2(event,recid){

            var docnum = $("#docnum_hidden").val();
            back_check = true;

            switch(event){
                case "save_exit":
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_exit&docnum="+docnum;
                    break;
                case "save_new":
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_new&docnum="+docnum;
                    break;
                case "open_add":

                    $("#price_add").val('');
                    $("#amount_add").val('');
                    $("#itmqty_add").val('');
                    $("#itmcde_add").val($("#itmcde_add option:first").val());

                    var itmcde_add_val = $("#itmcde_add").val();
                    var xdata  = "event_action=add_get&itmcde_add="+itmcde_add_val;

                    jQuery.ajax({    
                        data:xdata,
                        dataType:"json",
                        type:"post",
                        url:"trn_purchasefile2_ajax_itm.php", 
                        success: function(xdata){  
                            if(xdata["price_ret"]){
                                $("#price_add").val(xdata["price_ret"]);
                            }
                        }
                    })

                    $("#insert_modal_sales").modal("show");
                    return;
                break;
                case "insert":
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&"+$(".salesfile1 *").serialize();
                break;
                case "submitEdit":
                    var recid = $("#salesfile2_recid_hidden").val();
                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&recid="+recid;
                break;
                case "getEdit": 
                    var xdata = "event_action=getEdit&recid="+recid+"&docnum="+docnum;
                    break;
                case "delete":
                    var xdata = "event_action=delete&recid="+recid+"&docnum="+docnum;
                    break;
                case "insert":
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum;
                break;
            }

            var xdata = xdata+"&trncde="+trncde;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
                    success: function(xdata){  

                        if(xdata["status"] == 0){
                            $("#alert_modal_body_system").html(xdata["msg"]);
                            $("#alert_modal_system").modal("show");
                        }
 
                        if(xdata["msg"] == "insert_new"){
                            $("#crud_msg_h2").val("save_exit");
                            $("#crud_msg_h").val("save_exit");
                        }
                        if(xdata["msg"] == "insert_old"){
                            $("#crud_msg_h2").val("edit_exit");
                            $("#crud_msg_h").val("edit_exit");
                        }

                        var crud_msg_h =   $("#crud_msg_h").val();
                        var crud_msg_h2 =   $("#crud_msg_h2").val();

                        if(xdata["msg"] == "save_new_last" || xdata["msg"] == "save_new_same"){

                            $("#docnum_1").attr('value',xdata["new_docnum"]);
                            $("#docnum_hidden").attr('value',xdata["new_docnum"]);
                            $("#trndte_1").val('');
                            $("#trntot_1").val('');
                            $("#cusname_1").val($("#cusname_1 option:first").val());
                            $("#orderby_1").val('');
                            $("#shipto_1").val('');
                            $("#paydate_1").val('');
                            $("#payment_details_1").val('');
                            $("#remarks_1").val('');
                            $("#ordernum_1").val('');

                            if(xdata["msg"] == "save_new_same"){
                                $("#crud_msg_h").val("same_page");
                            }else{
                                $("#crud_msg_h").val("save_exit");
                            }


                        }


                        if(xdata["msg"] == "save_exit" || xdata["msg"] == "edit_exit"){

                            localStorage.setItem("scroll_check", "Y");

                            if(crud_msg_h2 == "save_exit"){
                                $("#crud_msg_h").val("save_exit");
                            }else if(crud_msg_h2 == "insert_old"){
                                $("#crud_msg_h").val("edit_exit");
                            }else{
                                $("#crud_msg_h").val(xdata["msg"]);
                            }

                            
                            document.forms.myforms.target = "_self";
                            document.forms.myforms.method = "post";
                            document.forms.myforms.action = "trn_purchasefile1.php";
                            document.forms.myforms.submit();
                            return;
                        }

                        
                        if(xdata["msg"] == "retEdit"){
                            $("#itmcde_edit").val(xdata["retEdit"]["itmcde"]);  // jomer 5-8-2025
                            $("#price_edit").val(xdata["retEdit"]["untprc"]);
                            $("#amount_edit").val(xdata["retEdit"]["extprc"]);
                            $("#itmqty_edit").val(xdata["retEdit"]["itmqty"]);
                        // $("#itmcde_edit option[text=" + xdata["retEdit"]["itmdsc"] +"]").prop("selected", true);
                            // $("#itmcde_edit option:contains("+xdata["retEdit"]["itmdsc"]+")").prop("selected", true);
                            // alert('dsd');
                            // $('#itmcde_edit').val(xdata["retEdit"]["itmdsc"]).prop("selected", true);

                                    //$('#itmcde_edit').text(xdata["retEdit"]["itmdsc"]).prop("selected", true);

                            // $("#itmcde_edit").filter(function() {
                            //     return $(this).text() === xdata["retEdit"]["itmdsc"];
                            // }).prop("selected", true);

                            var optionsThatContainValue = $("#itmcde_edit").find('option').filter(function() {
                                return $(this).text().trim() === xdata["retEdit"]["itmdsc"];
                            });

                            
                            optionsThatContainValue.prop("selected", true);
                            //console.log(optionsThatContainValue.val());
                            //console.log(xdata["retEdit"]["itmdsc"]);

                            $("#salesfile2_recid_hidden").val(xdata["retEdit"]["recid"]);
                            $("#edit_modal_sales").modal("show");
                        }
                        if(xdata["msg"] == "submitEdit"){
                            $("#edit_modal_sales").modal("hide");
                        }

                        $("#insert_modal_sales").modal("hide");

                        $("#main_chk_div").html(xdata["html"]);
                        $("#trntot_1").val(xdata["trntot"]);
                    }
            })

    
        }
    
        </script>


<?php
    require "includes/main_footer.php";
?>

