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

    $select_db_docnum1='SELECT * FROM salesorderfile1 WHERE recid=?';
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
        $paydate  = $rs_docnum1['paydate'];
        // $payment_details  = $rs_docnum1['paydetails'];
        $remarks  = $rs_docnum1['remarks'];
        $order_num  = $rs_docnum1['file_ordernum'];
        $waybill_num  = $rs_docnum1['waybill_number'];
        // $ordernum  = $rs_docnum1['ordernum'];

        $file_created_date = $rs_docnum1['file_created_date'];
        $date_file_created = new DateTime($file_created_date);
        $file_created_date = $date_file_created->format('m/d/Y');
    }
}else{

    $select_db_docnum="SELECT * FROM salesorderfile1  WHERE trncde='".$_POST['trncde_hidden']."'  AND salesorderfile1.docnum NOT LIKE '%BOM%' ORDER BY docnum DESC LIMIT 1";
    $stmt_docnum	= $link->prepare($select_db_docnum);
    $stmt_docnum->execute();
    $rs_docnum = $stmt_docnum->fetch();
    
    $docnum  = Lnexts($rs_docnum['docnum']);
    if(empty($rs_docnum)){
        $docnum  = "SOR-00001";
    }

    date_default_timezone_set('Asia/Manila');

    $trndate = date('m/d/Y');
    $trntot = '';
    $customer_name = '';
    $orderby = '';
    $shipto = '';
    $paydate  = NULL;
    $payment_details  = '';
    $remarks  = '';
    $ordernum = '';
    $file_created_date = NULL;
    $order_num  = '';
    $waybill_num  = '';
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


        @media (max-width: 768px) {
            #file2_table {
                flex: 1 1 auto;
            }
/* 
            #tbody_main_mobile {
                display: block!important;
            } */

            #tbody_main{
                display: none;
            }

        }    
        
        #tbody_main tr {
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }

        #tbody_main td {
            border: none;       /* Remove borders from the cells */
            outline: none;      /* Remove outlines from the cells */
        }

        .btn_search_item:hover{
            cursor:pointer;
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

                    <td colspan=1 style="height:100%" class='td_br' id='td_br'>

                        <div class="h-100 d-flex justify-content-center" style='max-width:100vw;flex-wrap:wrap;padding-left:15px;padding-right:15px;box-sizing:border-box'>
                         
                                <table class="bg-white w-75 shadow rounded user_access_tbl my-4" id="file2_table" style="border-radius: 0.75rem!important;border-collapse:collapse;height:400px;min-width:0">

                                    <tr class="m-1 salesfile1" style="border-bottom:3px solid #cccccc">
                                        <td colspan="3"> 

                                            <div class="row">

                                                <div class="col-md-4 col-6">
                                                    <div class="m-2">
                                                        <h2>Sales Order</h2>
                                                    </div>
                                                </div>

                                                <div class="col-md-8 col-12 d-flex align-items-center justify-content-end">

                                                    <div class="m-2 row">
                                                        <div class="col-md-3 col-4 d-flex justify-content-start ps-0 pe-1">
                                                                <input type="button" class="btn btn-danger fw-bold" style="width:100px;" value="Back" onclick="back_page()">                                           
                                                        </div>

                                                        <div class="col-md-4 col-4 d-flex justify-content-start ps-0 pe-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:150px;" value="Save and Exit" onclick="salesfile2('save_exit')">
                                                        </div>

                                                        <div class="col-md-5 col-5 d-flex justify-content-start justify-content-md-end ps-md-1 ps-0 pe-1 mt-1 mt-md-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:175px;" value="Save and Add Next" onclick="salesfile2('save_new')">
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </td>
                                    </tr>


                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">

                                       <td>
                                            <div class='row mx-2'>
                                                <div class="col-md-4 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Doc. Num:</label>
                                                    <input type="text" class="form-control" name="docnum_1" style="font-weight:bold" id="docnum_1" value="<?php echo $docnum;?>" readonly>
                                                </div>
                                        
                                                <div class="col-md-4 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Upload Date:<span style="color:red">*</span></label>
                                                    <input type="text" class="form-control date_picker"  name="trndte_1" id="trndte_1" value="<?php echo $trndate;?>" readonly>
                                                </div>
                                
                                                <div class="col-md-4 col-6 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Total:</label>
                                                    <input type="text" class="form-control" name="trntot_1" id="trntot_1" value="<?php echo $trntot;?>" autocomplete="off" readonly>
                                                </div>

                                            </div>
                             
                                        </td>                                    
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class='row mx-2'>
                                                <div class="col-md-4 col-6 p-3">                                                    
                                                    <label for="" style="font-weight:bold">Platform:</label>
                                                    <select name="cusname_1" id="cusname_1" class="form-control">
                                                        <?php
                                                            $select_cuscde='SELECT * FROM customerfile ORDER BY cusdsc ASC';
                                                            $stmt_cuscde	= $link->prepare($select_cuscde);
                                                            $stmt_cuscde->execute();
                                                            while($rs_cuscde = $stmt_cuscde->fetch()):
                                                        ?>
                                                        <?php
                                                        $selected = '';
                                                        if($cuscde == $rs_cuscde['cuscde']){
                                                            $selected = 'selected';
                                                        }
                                                        ?>
                                                        <option value="<?php echo $rs_cuscde['cuscde'];?>" <?php echo $selected;?>><?php echo $rs_cuscde['cusdsc'];?></option>
                                                        <?php endwhile; ?>
                                                    </select>                                                        
                                                </div>
                            
                                                <div class="col-md-4 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Order By:</label>
                                                    <input type="text" class="form-control" name="orderby_1" id="orderby_1" value="<?php echo $orderby;?>" autocomplete="off">
                                                </div>

                                                <div class="col-md-4 col-8 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Ordered Date:<span style='font-weight:normal'> (View only)</span></label>
                                                    <input type="text" class="form-control" name="ordered_date_1" id="ordered_date_1" value="<?php echo $file_created_date;?>" autocomplete="off" disabled>
                                                </div>
                                            </div>
                                  
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row mx-2">
                                                <div class="col-md-8 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Ship To:</label>
                                                    <input type="text" class="form-control" name="shipto_1" id="shipto_1" value="<?php echo $shipto;?>" autocomplete="off">
                                                </div>

                                                <div class="col-md-4 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Order Num.<span style='font-weight:normal'> (View only)</span></label>
                                                    <input type="text" class="form-control" name="order_num" id="order_num" value="<?php echo $order_num;?>" autocomplete="off" disabled>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row mx-2">
                                                <div class="col-md-8 col-6 p-3">
                                                    <label style="font-weight:bold">Remarks:</label>
                                                    <textarea name="remarks_1" id="remarks_1" rows="3" class="form-control"><?php echo $remarks; ?></textarea>
                                                </div>

                                                <div class="col-md-4 col-6 p-3">
                                                    <label style="font-weight:bold">Waybill Number:<span style='font-weight:normal'>(View Only)</span></label>
                                                    <input type="text" class="form-control" name="waybillnum" id="waybillnum" value="<?php echo $waybill_num;?>" autocomplete="off" disabled>
                                                </div>                                                
                                            </div>
                                        </td>   
                                        
                                        
                                    </tr>

                                    <tr>
                                        <td>
                                            <span class="error_msg_span" id="error_msg_span">
                                        </td>
                                    </tr>

                                    <tr class="m-1 salesfile1" id="tr_access_data">
                                        <td id="main_chk_div">
                                            <div class='w-100 d-flex justify-content-center'>
                                                <div style='width:100%'>

                                                    <div class='w-100 d-flex justify-content-center'>
                                                        <div style='width:90%'>
                                                            <button type='button' class='btn btn-success my-2' value='Add Record' onclick="salesfile2('open_add')">
                                                                <span style='font-weight:bold'>
                                                                    Add Record
                                                                </span>
                                                                <i class='fas fa-plus' style='margin-left: 3px'></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                    
                                                    <div class='w-100 d-flex justify-content-center'>
                                                        <table class='table table-striped' id='data_table' style='width:90%'>

                                                            <tbody id="tbody_main" class="tbody_main">

                                                            </tbody>

                                                            <tbody id="tbody_main_mobile" class="tbody_main_mobile">

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
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
                            <h5 class="modal-title" id="exampleModalLabel">Add Sales Order</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="itmcde_add" id="itmcde_add"  autocomplete="off" placeholder="Enter item to search" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func('add')" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
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

                            <input type="hidden" name="itmcde_add_hidden" id="itmcde_add_hidden">
                            <div class="row m-2">
                                <div class="error_msg_add_modal"></div>
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
                            <h5 class="modal-title" id="exampleModalLabel">Edit Sales Order</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item</label>

                                    <!-- <input type="text" name="itmcde_edit" id="itmcde_edit" class="form-control" autocomplete="off"> -->
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="itmcde_edit" id="itmcde_edit" readonly placeholder="Enter item to search" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-success btn_search_item" onclick="search_itm_func('edit')" style='color:white;height:100%;border-top-left-radius: 0;border-bottom-left-radius: 0' id="basic-addon2"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
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

                            <input type="hidden" name="itmcde_edit_hidden" id="itmcde_edit_hidden">
                            <div class="row m-2">
                                <div class="error_msg_edit_modal"></div>
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
                                <input type="text" class="form-control" name="itm_view" id="itm_view" placeholder="" autocomplete="off">
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
            
            <input type="hidden" name="xevent_itmsearch_hidden" id="xevent_itmsearch_hidden">

            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">
                
            <input type="hidden" name="orderby_search_h" id="orderby_search_h" value="<?php if(isset($_POST['orderby_search_h'])){echo $_POST['orderby_search_h'];}?>">
            <input type="hidden" name="ordernum_search_h" id="ordernum_search_h" value="<?php if(isset($_POST['ordernum_search_h'])){echo $_POST['ordernum_search_h'];}?>">
            <input type="hidden" name="docnum_search_h" id="docnum_search_h" value="<?php if(isset($_POST['docnum_search_h'])){echo $_POST['docnum_search_h'];}?>">
            <input type="hidden" name="from_search_h" id="from_search_h" value="<?php if(isset($_POST['from_search_h'])){echo $_POST['from_search_h'];}?>">
            <input type="hidden" name="to_search_h" id="to_search_h" value="<?php if(isset($_POST['to_search_h'])){echo $_POST['to_search_h'];}?>">
            <input type="hidden" name="from_ordered_search_h" id="from_ordered_search_h" value="<?php if(isset($_POST['from_ordered_search_h'])){echo $_POST['from_ordered_search_h'];}?>">
            <input type="hidden" name="to_ordered_search_h" id="to_ordered_search_h" value="<?php if(isset($_POST['to_ordered_search_h'])){echo $_POST['to_ordered_search_h'];}?>">            
            <input type="hidden" name="ordernum_file_search_h" id="ordernum_file_search_h" value="<?php if(isset($_POST['ordernum_file_search_h'])){echo $_POST['ordernum_file_search_h'];}?>">
            <input type="hidden" name="waybill_num_search_h" id="waybill_num_search_h" value="<?php if(isset($_POST['waybill_num_search_h'])){echo $_POST['waybill_num_search_h'];}?>">
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
            xdata = "docnum="+docnum+"&trncde="+trncde;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesorderfile2_ajax.php", 
                    success: function(xdata){  


                        if(xdata["matched_checked"] == true){
                            $("#cusname_1").prop("disabled", true);
                            $("#trndte_1").prop("disabled", true);
                        }
                        
                        $("#tbody_main").html(xdata["html"]);
                        $("#tbody_main_mobile").html(xdata["html_mobile"]);
                        
                    }

            })
        });


        function matched_alert(xevent, matched_s){

            if(xevent == "disabled"){
                alert("Cannot edit or delete already matched by "+matched_s);
            }

        }


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
            document.forms.myforms.action = "trn_salesorderfile1.php";
            document.forms.myforms.submit();
        }

        var save_new = false;


        // $('#itmcde_add').on('change', function() {

        //     xdata = "event_action=change_itmprice&xitmcde="+this.value;

        //     jQuery.ajax({    
        //         data:xdata,
        //         dataType:"json",
        //         type:"post",
        //         url:"trn_salesorderfile2_ajax.php", 
        //         success: function(xdata){  
        //             $("#price_add").val(xdata['retEdit']['xprice']);
        //             var xqty = $("#itmqty_add").val();
        //             var xtotal = xqty * xdata['retEdit']['xprice'];
        //             $("#amount_add").val(xtotal);
        //         }
        //     })  
        // });


        function select_item_modal(xitmcde, xitmdsc, xuntprc, xevent_action){

            if(xevent_action == 'add'){

                $(".error_msg_add_modal").html("");

                $("#itmcde_add_hidden").val(xitmcde);
                $("#itmcde_add").val(xitmdsc);

                $("#price_add").val(xuntprc);
                var xqty = $("#itmqty_add").val();
                var xtotal = xqty * xuntprc;
                $("#amount_add").val(xtotal);

                $("#view_itm_search").modal("hide");
                $("#itmcde_add").prop("readonly", true);
                $("#insert_modal_sales").modal("show");
            }else if(xevent_action == 'edit'){

                $(".error_msg_edit_modal").html("");

                $("#itmcde_edit_hidden").val(xitmcde);
                $("#itmcde_edit").val(xitmdsc);

                $("#price_edit").val(xuntprc);
                var xqty = $("#itmqty_edit").val();
                var xtotal = xqty * xuntprc;
                $("#amount_edit").val(xtotal);

                $("#view_itm_search").modal("hide");
                $("#edit_modal_sales").modal("show");   
            }

            $(".error_msg_itm_view").html("");

    
        }

        function back_from_view_func(xevent){

            $("#view_itm_search").modal("hide");

            if(xevent == "add"){
                $(".error_msg_add_modal").html("");
                $("#insert_modal_sales").modal("show");
            } else if(xevent == "edit"){
                $(".error_msg_edit_modal").html("");
                $("#edit_modal_sales").modal("show");
            }

        }

        $(document).ready(function(){
            $('#itm_view').on('keypress', function(event) {
                if (event.key === 'Enter' || event.keyCode === 13) {
                    search_itm_func_inmodal();
                }
            });

            $('#itmcde_add').on('keypress', function(event) {
                if (event.key === 'Enter' || event.keyCode === 13) {
                    search_itm_func('add');
                }
            });
        });

        function search_itm_func_inmodal(){

            var xevent_itmsearch_hidden_val = $("#xevent_itmsearch_hidden").val();


            var search_itm  = $("#itm_view").val();

            xdata = "event_action=search_itm&search_itm="+search_itm+"&event_action_itmsearch="+xevent_itmsearch_hidden_val;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesorderfile2_ajax.php", 
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
                var search_itm  = $("#itmcde_add").val();
                $(".back_btn_modal_footer").html(`<button type="button" class="btn btn-danger" onclick="back_from_view_func('add')">Back</button>`);

            }else if (xevent == 'edit'){
                var search_itm  = $("#itmcde_edit").val();  
                $(".back_btn_modal_footer").html(`<button type="button" class="btn btn-danger" onclick="back_from_view_func('edit')">Back</button>`);      

            }

            $('#xevent_itmsearch_hidden').val(xevent);

            xdata = "event_action=search_itm&search_itm="+search_itm+"&event_action_itmsearch="+xevent;
            
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesorderfile2_ajax.php", 
                success: function(xdata){  

                    if(xdata['status'] == 0){
                        if(xevent == 'add'){
                            $(".error_msg_add_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);
                        }else if (xevent == 'edit'){    
                            $(".error_msg_edit_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                            `);   
                        }

                    }else{

                        $(".html_itm_view_data").html(xdata['html']);
                        $("#itm_view").val(xdata['itm_search']);
                        if(xevent == 'add'){

                            $("#insert_modal_sales").modal("hide");
                        }else if(xevent == 'edit'){
                            $("#edit_modal_sales").modal("hide");
                        }

                        $(".error_msg_itm_view").html("");
                        $("#view_itm_search").modal("show");
                    }



                }
            })      
        }

        $('#itmcde_edit').on('change', function() {

            xdata = "event_action=change_itmprice&xitmcde="+this.value;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesorderfile2_ajax.php", 
                success: function(xdata){  
                    $("#price_edit").val(xdata['retEdit']['xprice']);
                    var xqty = $("#itmqty_edit").val();
                    var xtotal = xqty * xdata['retEdit']['xprice'];
                    $("#amount_edit").val(xtotal);
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

                    $("#itmcde_add").prop("readonly", false);
                    $("#price_add").val('');
                    $("#amount_add").val('');
                    $("#itmqty_add").val('');
                    $("#itmcde_add").val('');
                    $("#itmcde_add_hidden").val('');
                    $(".error_msg_add_modal").html('');
                    $("#insert_modal_sales").modal("show");
                    
                    // $("#itmqty_add").val('');
                    // $("#itmcde_add").val($("#itmcde_add option:first").val());
                    
                    // xdata = "event_action=select_itmprice";

                    // jQuery.ajax({    
                    //     data:xdata,
                    //     dataType:"json",
                    //     type:"post",
                    //     url:"trn_salesorderfile2_ajax.php", 
                    //     success: function(xdata){  
                    //         $("#price_add").val(xdata['retEdit']['xprice']);
                    //         $("#insert_modal_sales").modal("show");
                    //     }
                    // })                    


                    return;
                break;
                case "insert":
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&"+$(".salesfile1 *").serialize();
                break;
                case "submitEdit":
                    var recid = $("#salesfile2_recid_hidden").val();
                    var trndte_1_val = $("#trndte_1").val();
                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&recid="+recid+"&xtrndte_1="+trndte_1_val;
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
                url:"trn_salesorderfile2_ajax.php", 
                success: function(xdata){  

                        if(xdata["status"] == 0){

                            if(event == "insert"){
                                $(".error_msg_add_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);
                            }else if(event == "submitEdit"){
                                $(".error_msg_edit_modal").html(`
                                    <div class='alert alert-danger m-2' role='alert'>${xdata['msg']} </div>
                                `);
                            }else{
                                $("#alert_modal_body_system").html(xdata["msg"]);
                                $("#alert_modal_system").modal("show");
                            }

                            return;

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
                            // $("#trndte_1").val('');
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
                            document.forms.myforms.action = "trn_salesorderfile1.php";
                            document.forms.myforms.submit();
                            return;
                        }

                        if(xdata["msg"] == "retEdit"){
                            
                            $("#itmcde_edit_hidden").val(xdata["retEdit"]["itmcde"]);
                            $("#itmcde_edit").val(xdata["retEdit"]["itmdsc"]);
                            $("#price_edit").val(xdata["retEdit"]["untprc"]);
                            $("#amount_edit").val(xdata["retEdit"]["extprc"]);
                            $("#itmqty_edit").val(xdata["retEdit"]["itmqty"]);
                            // $("#itmcde_edit option[text=" + xdata["retEdit"]["itmdsc"] +"]").prop("selected", true);
                            // $("#itmcde_edit option:contains("+xdata["retEdit"]["itmdsc"]+")").prop("selected", true);

                            // var optionsThatContainValue = $("#itmcde_edit").find('option').filter(function() {
                            //     return $(this).text().trim() === xdata["retEdit"]["itmdsc"];
                            // });

                            // optionsThatContainValue.prop("selected", true);

                            $(".error_msg_edit_modal").html('');

                            $("#salesfile2_recid_hidden").val(xdata["retEdit"]["recid"]);
                            $("#edit_modal_sales").modal("show");
                        }
                        if(xdata["msg"] == "submitEdit"){
                            $("#edit_modal_sales").modal("hide");
                        }

                        $("#insert_modal_sales").modal("hide");

                        $("#tbody_main").html(xdata["html"]);
                        $("#tbody_main_mobile").html(xdata["html_mobile"]);
                        $("#trntot_1").val(xdata["trntot"]);
                }
            })

    
        }
    
        </script>


<?php
    require "includes/main_footer.php";
?>

