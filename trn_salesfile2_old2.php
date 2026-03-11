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

        if(!empty($rs_docnum1['jnt_pickedup_date'])){
            $rs_docnum1['jnt_pickedup_date'] = date("m-d-Y",strtotime($rs_docnum1['jnt_pickedup_date']));
            $rs_docnum1['jnt_pickedup_date'] = str_replace('-','/',$rs_docnum1['jnt_pickedup_date']);
        }else{
            $rs_docnum1['jnt_pickedup_date'] = NULL;
        }

        if(!empty($rs_docnum1['jnt_delivered_date'])){
            $rs_docnum1['jnt_delivered_date'] = date("m-d-Y",strtotime($rs_docnum1['jnt_delivered_date']));
            $rs_docnum1['jnt_delivered_date'] = str_replace('-','/',$rs_docnum1['jnt_delivered_date']);
        }else{
            $rs_docnum1['jnt_delivered_date'] = NULL;
        }

        
        if(!empty($rs_docnum1['date_returned'])){
            $rs_docnum1['date_returned'] = date("m-d-Y",strtotime($rs_docnum1['date_returned']));
            $rs_docnum1['date_returned'] = str_replace('-','/',$rs_docnum1['date_returned']);
        }else{
            $rs_docnum1['date_returned'] = NULL;
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
        $payment_details  = $rs_docnum1['paydetails'];
        $remarks  = $rs_docnum1['remarks'];
        $ordernum  = $rs_docnum1['ordernum'];
        //$waybill_number  = $rs_docnum1['waybill_number'];
        //$order_status  = $rs_docnum1['order_status'];
        $jnt_pickedup_date = $rs_docnum1['jnt_pickedup_date'];
        $jnt_delivered_date = $rs_docnum1['jnt_delivered_date'];
        $date_returned = $rs_docnum1['date_returned'];

        $in_transit_selected = '';
        $shipped_selected = '';

        //if($order_status ==  'INTRANSIT'){
            $in_transit_selected = 'selected';
        //}else if($order_status ==  'SHIPPED'){
            $shipped_selected = 'selected';
        //}

    }
}else{
    $select_db_docnum="SELECT * FROM tranfile1  WHERE trncde='".$_POST['trncde_hidden']."' AND docnum NOT LIKE '%BOM%' ORDER BY docnum DESC LIMIT 1";
    $stmt_docnum	= $link->prepare($select_db_docnum);
    $stmt_docnum->execute();
    $rs_docnum = $stmt_docnum->fetch();
    
    $docnum  = Lnexts($rs_docnum['docnum']);
    if(empty($rs_docnum)){
        $docnum  = "SAL-00001";
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
    //$waybill_number  = '';
    $order_status =  '';
    $jnt_pickedup_date = '';
    $in_transit_selected = '';
    $shipped_selected = '';

    $jnt_pickedup_date = NULL;
    $jnt_delivered_date = NULL;
    $date_returned = NULL;
    

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

            #tbody_selectpo{
                display: none;      
            }            

        }    

        
        @media screen and (min-width: 576px) {
            #tbody_selectpo_mobile{
                display: none;      
            }
        }
        

        #tbody_main tr{
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }
        
        #tbody_main td {
            border: none;       /* Remove borders from the cells */
            outline: none;      /* Remove outlines from the cells */
        }
        
        #tbody_selectpo tr{
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }

        #tbody_selectpo td{
            border: none;       /* Remove borders from the rows */
            outline: none;      /* Remove outlines from the rows */
        }

        .disabled {
            pointer-events: none; /* Prevents clicks */
            opacity: 0.5; /* Grays out the element */
            cursor: not-allowed; /* Changes the cursor */
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

                        <div class="container-fluid w-100 h-100 d-flex justify-content-center" style='max-width:100vw;flex-wrap:wrap;padding-left:15px;padding-right:15px;box-sizing:border-box'>
                         
                                <table class="bg-white w-75 shadow rounded user_access_tbl my-4"  id="file2_table" style="border-radius: 0.75rem!important;border-collapse:collapse;height:400px;min-width:0">

                                    <tr class="m-1 salesfile1" style="border-bottom:3px solid #cccccc">
                                        <td colspan="3"> 

                                            <div class="row">

                                                <div class="col-md-4 col-6">
                                                    <div class="m-2">
                                                        <h2>Sales</h2>
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
                                            <div class="row px-2">
                                                <div class="col-md-4 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Doc. Num:</label>
                                                    <input type="text" class="form-control" name="docnum_1" style="font-weight:bold" id="docnum_1" value="<?php echo $docnum;?>" readonly>
                                                </div>
                        
                                                <div class="col-md-4 col-6 p-3">
                                                    <label for="" style="font-weight:bold">Tran. Date:<span style="color:red">*</span></label>
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
                                            <div class="row px-2">
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
                                                    <label for="" style="font-weight:bold">Order Number:</label>
                                                    <input type="text" class="form-control" name="ordernum_1" id="ordernum_1" value="<?php echo $ordernum;?>" autocomplete="off">
                                                </div>                                       
                                
                                                <div class="col-md-4 col-6 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Order By:</label>
                                                    <input type="text" class="form-control" name="orderby_1" id="orderby_1" value="<?php echo $orderby;?>" autocomplete="off">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row px-2">
                                                <div class="p-3">
                                                    <label for="" style="font-weight:bold">Ship To:</label>
                                                    <input type="text" class="form-control" name="shipto_1" id="shipto_1" value="<?php echo $shipto;?>" autocomplete="off">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row px-2">
                                                <!-- <div class="col-md-6 col-12 p-3">
                                                    <label style="font-weight:bold">Waybill Number:<span style='font-weight:normal'>(View Only)</span></label>
                                                    <input type="text" class="form-control" name="waybill_num1" id="waybill_num1" value="<?php echo $waybill_number;?>" readonly>
                                                </div> -->
                
                                                <!-- <div class="col-md-6 col-12 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Order Status:</label>
                                                    <select name="order_status_select1" id="order_status_select1" class='form-select'>
                                                        <option value="INTRANSIT" <?php echo $in_transit_selected;?> >In-Transit</option>
                                                        <option value="SHIPPED" <?php echo $shipped_selected;?>>Shipped</option>
                                                    </select>
                                                </div> -->
                                            </div>
                                        </td>                                    
                                    </tr>                                    

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row px-2">
                                                <div class="col-md-6 col-12 p-3">
                                                    <label style="font-weight:bold">Pay Date:</label>
                                                    <input type="text" class="form-control date_picker" name="paydate_1" id="paydate_1" value="<?php echo $paydate;?>" readonly>
                                                </div>
                                        
                                                <div class="col-md-6 col-12 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Payment Details:</label>
                                                    <input type="text" class="form-control" name="payment_details_1" id="payment_details_1" value="<?php echo $payment_details;?>" autocomplete="off">
                                                </div>
                                            </div>

                                        </td>                                    
                                    </tr>

                                    <!-- <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row px-2">
                                                <div class="col-md-4 col-12 p-3">
                                                    <label style="font-weight:bold">Picked up Date:</label>
                                                    <input type="text" class="form-control" name="jnt_pickedup_date" id="jnt_pickedup_date" value="<?php echo $jnt_pickedup_date;?>" disabled>
                                                </div>

                                                <div class="col-md-4 col-12 p-3">
                                                    <label style="font-weight:bold">Delivered Date:</label>
                                                    <input type="text" class="form-control" name="jnt_delivered_date" id="jnt_delivered_date" value="<?php echo $jnt_delivered_date;?>" disabled>
                                                </div>
                                        
                                                <div class="col-md-4 col-12 p-3">
                                                    <label style="font-weight:bold">Returned Date:</label>
                                                    <input type="text" class="form-control" name="jnt_date_returned" id="jnt_date_returned" value="<?php echo $date_returned;?>" disabled>
                                                </div>
                                            </div>

                                        </td>                                    
                                    </tr> -->

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3">
                                                <label style="font-weight:bold">Remarks:</label>
                                                <textarea name="remarks_1" id="remarks_1" rows="3" class="form-control"><?php echo $remarks; ?></textarea>
                                            </div>
                                        </td>                                  
                                    </tr>



                                    <tr>
                                        <td colspan="3">
                                            <span class="error_msg_span" id="error_msg_span">
                                        </td>
                                    </tr>

                                    <tr class="m-1 salesfile1" id="tr_access_data">
                                        <td id="main_chk_div">

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
                            <h5 class="modal-title" id="exampleModalLabel">Add Sales</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Item</label>
                                    <div class="input-group mb-3">
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

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Search Sales Orders</label>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button" onclick="search_so('getData_add','')">Search</button>
                                        </div>
                                        <input type="text" name="so_add" id="so_add" class="form-control" autocomplete="off" readonly>
                                    </div>
                                   
                                </div>
                            </div>                          


                            <!-- <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Order Status</label>
                                    <select class="form-select" name="order_status_add" id="order_status_add">
                                        <option value="INTRANSIT">In-Transit</option>
                                        <option value="SHIPPED" >Shipped</option>
                                    </select>
                                </div>
                            </div>                             -->

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

            <div class="modal fade" id="insert_modal_sales_po" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Match Sales Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style='overflow-y:auto;max-height:90vh'>
                     <table class='table table-striped'>
                        
                        <tbody name="tbody_selectpo" id="tbody_selectpo">

                        </tbody>

                        <tbody name="tbody_selectpo_mobile" id="tbody_selectpo_mobile">

                        </tbody>


                     </table>
                    </div>
                    <div class="modal-footer" id="modal_footer_po">
                        <button type="button" class="btn btn-danger fw-bold" onclick="return_po()">Back</button>
                    </div>
                    </div>
                </div>
            </div>              

            <div class="modal fade" id="edit_modal_sales" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Edit Sales</h5>
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

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Search Sales Orders</label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button" onclick="search_so_edit('getData_edit','')">Search</button>
                                        </div>
                                        <input type="text" name="so_edit" id="so_edit" class="form-control" autocomplete="off" readonly>
                                    </div>
                                </div>
                            </div>                            
                            
                            <!-- <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Order Status</label>
                                    <select class="form-select" name="order_status_edit" id="order_status_edit">
                                        <option value="INTRANSIT">In-Transit</option>
                                        <option value="SHIPPED" >Shipped</option>
                                    </select>
                                </div>
                            </div>        -->

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
                
                <input type="hidden" name="so_edit_hidden" id="so_edit_hidden">
                <input type="hidden" name="prev_itmcde_hidden_edit" id="prev_itmcde_hidden_edit">
                <input type="hidden" name="prev_itmqty_hidden_edit" id="prev_itmqty_hidden_edit">


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
            <input type="hidden" name="recid_so_hidden" id="recid_so_hidden">

            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">
                

            <input type="hidden" name="waybill_num_search_h" id="waybill_num_search_h" value="<?php if(isset($_POST['waybill_num_search_h'])){echo $_POST['waybill_num_search_h'];}?>">
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
            xdata = "docnum="+docnum+"&trncde="+trncde;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesfile2_ajax.php", 
                    success: function(xdata){  
                     
                        $("#tbody_main").html(xdata["html"]);
                        $("#tbody_main_mobile").html(xdata["html_mobile"]);
                    }

            })
        });

        function return_po(){

            $("#insert_modal_sales_po").modal("hide");
            $("#insert_modal_sales").modal("show");

        }

        function return_po_edit(){

            $("#insert_modal_sales_po").modal("hide");
            $("#edit_modal_sales").modal("show");

        }

        function select_item_modal(xitmcde, xitmdsc, xevent_action, xnew_itm){

            if(xevent_action == 'add'){

                $(".error_msg_add_modal").html("");

                $("#itmcde_add_hidden").val(xitmcde);
                $("#itmcde_add").val(xitmdsc);

                //$("#price_add").val(xuntprc);
                var xqty = $("#itmqty_add").val();
                //var xtotal = xqty * xuntprc;
                //$("#amount_add").val(xtotal);

                $("#view_itm_search").modal("hide");
                $("#itmcde_add").prop("readonly", true);
                $("#insert_modal_sales").modal("show");
            }else if(xevent_action == 'edit'){

                $(".error_msg_edit_modal").html("");

                $("#itmcde_edit_hidden").val(xitmcde);
                $("#itmcde_edit").val(xitmdsc);

                //$("#price_edit").val(xuntprc);
                var xqty = $("#itmqty_edit").val();
                //var xtotal = xqty * xuntprc;
                //      $("#amount_edit").val(xtotal);

                if(xitmdsc != xnew_itm){
                    $("#so_edit").val('');
                    $("#recid_so_hidden").val('');
                }

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
                url:"trn_salesfile2_ajax.php", 
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
                url:"trn_salesfile2_ajax.php", 
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
                url:"trn_salesfile2_ajax.php", 
                success: function(xdata){  
                    $("#price_edit").val(xdata['retEdit']['xprice']);
                    var xqty = $("#itmqty_edit").val();
                    var xtotal = xqty * xdata['retEdit']['xprice'];
                    $("#amount_edit").val(xtotal);
                }
            })  
        });            

        function search_so(xevent_action,xdocnum,xrecid){

            $("#insert_modal_sales").modal("hide");
            var cuscde_val = $("#cusname_1").val();

            if(xrecid){
                $("#recid_so_hidden").val(xrecid);
            }

            if($('#itmcde_add').is(':disabled')){
                $("#itmcde_add").attr("disabled", false);
                var xdata = $("#insert_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action;
                $("#itmcde_add").attr("disabled", true);
            }else{
                var xdata = $("#insert_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action;    
            }

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesfile2_ajax_so.php", 
                    success: function(xdata){
                        
                        if(xevent_action == 'getData_add'){
                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-danger fw-bold" onclick="return_po()">Back</button>`
                            );

                        }else if(xevent_action == 'selectData_add'){

                            $("#so_add").val(xdocnum);
                            $("#insert_modal_sales_po").modal("hide");
                            $("#insert_modal_sales").modal("show");

                            //disable the inputs
                            $('#itmcde_add').attr("disabled", true); 
                            $("#itmqty_add").prop('readonly', true);
                            $("#price_add").prop('readonly', true);
                            $('.btn_search_item').addClass('disabled');
                        }

                    }
            })

        }

        function search_so_edit(xevent_action,xdocnum,xrecid){

            $("#edit_modal_sales").modal("hide");
            var cuscde_val = $("#cusname_1").val();
            var selected_po = $("#so_edit").val();
            var recid_tranfile2_hidden = $("#recid_tranfile2_hidden").val();
            //var selected_po_hidden = $("#po_edit_hidden").val();

            if(xrecid){
                var recid_so_hidden_val = $("#recid_so_hidden").val(xrecid);
            }else{
                var recid_so_hidden_val = $("#recid_so_hidden").val();
            }

            if($('#itmcde_edit').is(':disabled')){
                //enable the itcde para mapass yung data
                var xdata = $("#edit_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_so_hidden="+recid_so_hidden_val;
            }else{
                var xdata = $("#edit_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_so_hidden="+recid_so_hidden_val;
            }

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesfile2_ajax_so.php", 
                    success: function(xdata){
                        
                        if(xevent_action == 'getData_edit'){
                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-danger fw-bold" onclick="return_po_edit()">Back</button>`
                            )
                        }else if(xevent_action == 'selectData_edit' || xevent_action == 'deSelectData_edit'){

                            if(xevent_action == 'selectData_edit'){
                                $("#so_edit").val(xdocnum);
                                $("#recid_so_hidden").val(xrecid);
                            }else{
                                $("#so_edit").val('');
                                $("#recid_so_hidden").val('');
                            }
                            
                            $("#insert_modal_sales_po").modal("hide");
                            $("#edit_modal_sales").modal("show");

                            $("#itmqty_edit").prop('readonly', true);
                            $("#price_edit").prop('readonly', true);

                        }

                    }
            })

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
            document.forms.myforms.action = "trn_salesfile1.php";
            document.forms.myforms.submit();
        }

        var save_new = false;

        // $('#itmcde_add').on('change', function() {

        //     xdata = "event_action=change_itmprice&xitmcde="+this.value;

        //     jQuery.ajax({    
        //         data:xdata,
        //         dataType:"json",
        //         type:"post",
        //         url:"trn_salesfile2_ajax.php", 
        //         success: function(xdata){  
        //             $("#price_add").val(xdata['retEdit']['xprice']);
        //             var xqty = $("#itmqty_add").val();
        //             var xtotal = xqty * xdata['retEdit']['xprice'];
        //             $("#amount_add").val(xtotal);
        //         }
        //     })  
        // });

        // $('#itmcde_edit').on('change', function() {

        //     xdata = "event_action=change_itmprice&xitmcde="+this.value;

        //     jQuery.ajax({    
        //         data:xdata,
        //         dataType:"json",
        //         type:"post",
        //         url:"trn_salesfile2_ajax.php", 
        //         success: function(xdata){  
        //             $("#price_edit").val(xdata['retEdit']['xprice']);
        //             var xqty = $("#itmqty_edit").val();
        //             var xtotal = xqty * xdata['retEdit']['xprice'];
        //             $("#amount_edit").val(xtotal);
        //         }
        //     })  
        // });          


        function salesfile2(event,xrecid,xitmcde,xitmqty){

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
                    $('#itmcde_add').attr('disabled', false);

                    $("#itmqty_add").prop('readonly', false);
                    $("#price_add").prop('readonly', false);
                    $('.btn_search_item').removeClass('disabled');

                    $("#price_add").val('');
                    $("#amount_add").val('');
                    $("#itmqty_add").val('');
                    $("#itmcde_add").val('');
                    $("#so_add").val('');
                    $("#recid_so_hidden").val('');

                    // $("#itmcde_add").val($("#itmcde_add option:first").val());
                    $("#itmcde_add_hidden").val('');
                    $('.error_msg_add_modal').html('');
                    $("#insert_modal_sales").modal("show");

                    // xdata = "event_action=select_itmprice";
                    // jQuery.ajax({    
                    //     data:xdata,
                    //     dataType:"json",
                    //     type:"post",
                    //     url:"trn_salesfile2_ajax.php", 
                    //     success: function(xdata){  
                    //         $("#price_add").val(xdata['retEdit']['xprice']);

                    //     }
                    // })  

                    return;
                break;
                case "insert":
                    $("#itmcde_add").attr("disabled", false);
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&"+$(".salesfile1 *").serialize();
                    $('#itmcde_add').attr("disabled", true); 
                break;
                case "submitEdit":

                    var cusname_1 = $("#cusname_1").val();
                    var orderby_1 = $("#orderby_1").val();
                    //var orderby = $("#order_status_select1").val();
                    var recid = $("#salesfile2_recid_hidden").val();
                    var trndte_1_val = $("#trndte_1").val();
                    var waybill_num1 = $("#waybill_num1").val();
                    var hidden_itmcde_edit = $("#prev_itmcde_hidden_edit").val();
                    var hidden_itmqty_edit = $("#prev_itmqty_hidden_edit").val();
                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&cusname_1="+cusname_1+"&docnum="+docnum+"&recid="+recid+"&orderby_1="+orderby_1+"&xtrndte_1="+trndte_1_val+"&waybill_num1="+waybill_num1+"&hidden_itmcde_edit="+hidden_itmcde_edit+"&hidden_itmqty_edit="+hidden_itmqty_edit;
                break;
                case "getEdit": 
                    $('.btn_search_item').removeClass('disabled');
                    $("#itmqty_edit").prop('readonly', false);
                    $("#price_edit").prop('readonly', false);

                    $("#prev_itmcde_hidden_edit").val(xitmcde);
                    $("#prev_itmqty_hidden_edit").val(xitmqty);


                    $("#recid_tranfile2_hidden").val(xrecid);

                    var xdata = "event_action=getEdit&recid="+xrecid+"&docnum="+docnum;
                    break;
                case "delete":
                    var orderby_1 = $("#orderby_1").val();
                    //var orderby = $("#order_status_select1").val();
                    var recid = $("#salesfile2_recid_hidden").val();
                    var trndte_1_val = $("#trndte_1").val();
                    var waybill_num1 = $("#waybill_num1").val();

                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&recid="+xrecid+"&xtrndte_1="+trndte_1_val+"&waybill_num1="+waybill_num1;

                    let userInput = confirm("Are you sure you want to delete?");

                    //cancelled delete
                    if (!userInput) {
                        return
                    }

                    break;
                case "insert":
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum;
                break;
            }

            var xrecid_so_hidden_val = $("#recid_so_hidden").val();
            var xdata = xdata+"&trncde="+trncde+"&xrecid_so_hidden="+xrecid_so_hidden_val;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_salesfile2_ajax.php", 
                success: function(xdata){  

                        if(xdata["status"] == 0){

                            if(event == "insert"){
                                $("#itmcde_add").prop("readonly", false);
                                $('#itmcde_add').attr("disabled", false); 

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


                            // $("#alert_modal_body_system").html(xdata["msg"]);
                            // $("#alert_modal_system").modal("show");

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
                            $("#waybill_num1").val('');
                            //$("#order_status_select1").val($("#order_status_select1 option:first").val());

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
                            document.forms.myforms.action = "trn_salesfile1.php";
                            document.forms.myforms.submit();
                            return;
                        }

                        if(xdata["msg"] == "retEdit"){

                            $("#itmcde_edit_hidden").val(xdata["retEdit"]["itmcde"]);
                            $("#itmcde_edit").val(xdata["retEdit"]["itmdsc"]);
                            $("#price_edit").val(xdata["retEdit"]["untprc"]);
                            $("#amount_edit").val(xdata["retEdit"]["extprc"]);
                            $("#itmqty_edit").val(xdata["retEdit"]["itmqty"]);

                            $("#so_edit").val(xdata["retEdit"]["matched_so"]);
                            $("#so_edit_hidden").val(xdata["retEdit"]["matched_so"]);

                            //recid getting
                            $("#recid_so_hidden").val(xdata["retEdit"]["so_recid"]);
                            $(".error_msg_edit_modal").html('');
                            // $("#itmcde_edit option[text=" + xdata["retEdit"]["itmdsc"] +"]").prop("selected", true);
                            // $("#itmcde_edit option:contains("+xdata["retEdit"]["itmdsc"]+")").prop("selected", true);

                            // var optionsThatContainValue = $("#itmcde_edit").find('option').filter(function() {
                            //     return $(this).text().trim() === xdata["retEdit"]["itmdsc"];
                            // });

                            // optionsThatContainValue.prop("selected", true);


                            // const order_status_element = document.getElementById('order_status_edit');
                            // for (let option of order_status_element.options) {
                            //     if (option.value === xdata["retEdit"]["order_status"]) {
                            //         option.selected = true;
                            //     }
                            // }

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

