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

$header_usercode = '';
$is_edit_mode = false;

if(isset($_POST['recid_hidden']) && !empty($_POST['recid_hidden'])){
    $is_edit_mode = true;
    $select_db_docnum1='SELECT * FROM tranfile1 
     LEFT JOIN mf_buyers ON tranfile1.buyer_id = mf_buyers.buyer_id
     LEFT JOIN mf_salesman ON tranfile1.salesman_id = mf_salesman.salesman_id
     LEFT JOIN mf_routes ON tranfile1.route_id = mf_routes.route_id
     WHERE tranfile1.recid=?';
    $stmt_docnum1	= $link->prepare($select_db_docnum1);
    $stmt_docnum1->execute(array($_POST["recid_hidden"]));

    while($rs_docnum1 = $stmt_docnum1->fetch()){

        if(!empty($rs_docnum1['trndte'])){
            $rs_docnum1['trndte'] = date("m-d-Y",strtotime($rs_docnum1['trndte']));
            $rs_docnum1['trndte'] = str_replace('-','/',$rs_docnum1['trndte']);
        }else{
            $rs_docnum1['trndte'] = NULL;
        }

        if(!empty($rs_docnum1['paydate_salesman'])){
            $rs_docnum1['paydate_salesman'] = date("m-d-Y",strtotime($rs_docnum1['paydate_salesman']));
            $rs_docnum1['paydate_salesman'] = str_replace('-','/',$rs_docnum1['paydate_salesman']);
        }else{
            $rs_docnum1['paydate_salesman'] = NULL;
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
        $orderby  = $rs_docnum1['buyer_name'];
        $trndate  = $rs_docnum1['trndte'];
        $trntot  = $rs_docnum1['trntot'];
        $shipto  = $rs_docnum1['shipto'];
        //$ship_status  = $rs_docnum1['ship_status'];
        $ship_status  = $rs_docnum1['ship_status'];
        $com_pay  = $rs_docnum1['com_pay'];
        $cuscde  = $rs_docnum1['cuscde'];
        $paydate  = $rs_docnum1['paydate'];
        $paydate_salesman  = $rs_docnum1['paydate_salesman'];
        $payment_details  = $rs_docnum1['paydetails'];
        $remarks  = $rs_docnum1['remarks'];
        $ordernum  = $rs_docnum1['ordernum'];
        $salesman_id  = $rs_docnum1['salesman_id'];
        $route_id  = $rs_docnum1['route_id'];
        //$waybill_number  = $rs_docnum1['waybill_number'];
        //$order_status  = $rs_docnum1['order_status'];
        $jnt_pickedup_date = $rs_docnum1['jnt_pickedup_date'];
        $jnt_delivered_date = $rs_docnum1['jnt_delivered_date'];
        $date_returned = $rs_docnum1['date_returned'];
        $buyer_id = $rs_docnum1['buyer_id'];

        $image_loc = $rs_docnum1['img_filename'];

        $in_transit_selected = '';
        $shipped_selected = '';

        //if($order_status ==  'INTRANSIT'){
            $in_transit_selected = 'selected';
        //}else if($order_status ==  'SHIPPED'){
            $shipped_selected = 'selected';
        //}

        $can_change_ordernum = $rs_docnum1['can_change_ordernum'];
        $header_usercode = isset($rs_docnum1['usercode']) ? trim((string)$rs_docnum1['usercode']) : '';

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


    $can_change_ordernum = '';
    $trntot = '';
    $customer_name = '';
    $orderby = '';
    $shipto = '';
    $ship_status = '';
    $com_pay = '';
    $paydate  = NULL;
    $paydate_salesman = NULL;
    $payment_details  = '';
    $remarks  = '';
    $ordernum = '';
    //$waybill_number  = '';
    $order_status =  '';
    $jnt_pickedup_date = '';
    $in_transit_selected = '';
    $shipped_selected = '';
    $salesman_id  = '';
    $route_id  = '';
    $jnt_pickedup_date = NULL;
    $jnt_delivered_date = NULL;
    $date_returned = NULL;
    $buyer_id = '';

    $image_loc = null;

}

$session_usercode = '';
if(isset($_SESSION['usercode']) && trim((string)$_SESSION['usercode']) !== ''){
    $session_usercode = trim((string)$_SESSION['usercode']);
}else if(isset($_POST["usercode_hidden"]) && trim((string)$_POST["usercode_hidden"]) !== ''){
    $session_usercode = trim((string)$_POST["usercode_hidden"]);
}

$display_usercode = $is_edit_mode ? $header_usercode : $session_usercode;
$display_userdesc = '';
if($display_usercode !== ''){
    $select_user = "SELECT userdesc FROM users WHERE usercode = ? LIMIT 1";
    $stmt_user = $link->prepare($select_user);
    $stmt_user->execute(array($display_usercode));
    $rs_user = $stmt_user->fetch();
    if(!empty($rs_user) && isset($rs_user['userdesc'])){
        $display_userdesc = $rs_user['userdesc'];
    }
}

$warehouse_options = array();
$stmt_warehouse = $link->prepare("SELECT warcde, warehouse_name FROM warehouse ORDER BY warehouse_name ASC");
$stmt_warehouse->execute();
while($rs_warehouse = $stmt_warehouse->fetch()){
    $warehouse_options[] = array(
        'warcde' => $rs_warehouse['warcde'],
        'warehouse_name' => $rs_warehouse['warehouse_name']
    );
}

$warehouse_floor_map = array();
$stmt_floor = $link->prepare("SELECT warehouse_floor_id, warcde, floor_no, floor_name FROM warehouse_floor ORDER BY floor_no ASC, floor_name ASC, warehouse_floor_id ASC");
$stmt_floor->execute();
while($rs_floor = $stmt_floor->fetch()){
    $floor_warcde = isset($rs_floor['warcde']) ? (string)$rs_floor['warcde'] : '';
    if(!isset($warehouse_floor_map[$floor_warcde])){
        $warehouse_floor_map[$floor_warcde] = array();
    }
    $warehouse_floor_map[$floor_warcde][] = array(
        'warehouse_floor_id' => $rs_floor['warehouse_floor_id'],
        'floor_no' => trim((string)($rs_floor['floor_no'] !== '' ? $rs_floor['floor_no'] : $rs_floor['floor_name']))
    );
}

$warehouse_staff_options = array();
$stmt_staff = $link->prepare("SELECT warehouse_staff_id, fname, lname FROM warehouse_staff ORDER BY fname ASC, lname ASC");
$stmt_staff->execute();
while($rs_staff = $stmt_staff->fetch()){
    $warehouse_staff_options[] = array(
        'warehouse_staff_id' => $rs_staff['warehouse_staff_id'],
        'staff_name' => trim($rs_staff['fname'].' '.$rs_staff['lname'])
    );
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

                                                <div class="col-md-2 col-6">
                                                    <div class="m-2">
                                                        <h2>Sales</h2>
                                                    </div>
                                                </div>

                                                <div class="col-md-10 col-12 d-flex align-items-center justify-content-end">

                                                    <div class="m-2 row">
                                                        <div class="col-md-1 col-4 d-flex justify-content-start ps-0 pe-1">
                                                                <input type="button" class="btn btn-danger fw-bold" style="width:100px;" value="Back" onclick="back_page()">                                           
                                                        </div>

                                                        <div class="col-md-2 col-4 d-flex justify-content-end">
                                                                <input type="button" class="btn btn-info fw-bold"  data-bs-toggle="modal" data-bs-target="#drModal" style="width:auto;"  value="Print DR">                                           
                                                        </div>

                                                        <div class="col-md-4 col-8 d-flex justify-content-center ms-0 ps-0">
                                                                <input type="button" class="btn btn-info fw-bold" style="width:auto;"  value="Print Declaration Form" onclick="print_declaration()">                                           
                                                        </div>

                                                        <div class="col-md-2 col-4 d-flex justify-content-start ps-0 pe-0">
                                                            <input type="button" class="btn btn-success fw-bold" style="width:150px;" value="Save and Exit" onclick="salesfile2('save_exit')">
                                                        </div>

                                                        <div class="col-md-3 col-5 d-flex justify-content-start justify-content-md-end ps-md-1 ps-0 pe-1 mt-1 mt-md-0">
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
                                    
                                                    <?php

                                                        if($can_change_ordernum == 'true'){
                                                            echo "<label for='' style='font-weight:bold'>Order Number:(Uploaded so uneditable)</label>";
                                                            echo "<input readonly type='text' class='form-control' name='ordernum_1' id='ordernum_1' value='".$ordernum."' autocomplete='off'>";
                                                        }else{
                                                            echo "<label for='' style='font-weight:bold'>Order Number:</label>";
                                                            echo "<input type='text' class='form-control' name='ordernum_1' id='ordernum_1' value='".$ordernum."' autocomplete='off'>";
                                                        }
                                                    
                                                    ?>
                                                    
                                                </div>                                       
                                
                                                <div class="col-md-4 col-6 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Buyer (Order By):</label>
                                                    <select name="orderby_1" id="orderby_1" class="form-control" onchange="orderby_change()">
                             

                                                            <?php
                                                                $select_itemfile='SELECT * FROM mf_buyers ORDER BY buyer_name ASC';
                                                                $stmt_itemfile	= $link->prepare($select_itemfile);
                                                                $stmt_itemfile->execute();
                                                            
                                                                while($rs_itemfile = $stmt_itemfile->fetch()):
                                                            ?>

                                                            <?php
                                                            $selected_orderby = '';
                                                            if($buyer_id == $rs_itemfile['buyer_id']){
                                                                $selected_orderby = 'selected';
                                                            }
                                                            
                                                            ?>

                                                            <option value="<?php echo $rs_itemfile['buyer_id'];?>" <?php echo $selected_orderby;?>><?php echo $rs_itemfile['buyer_name'];?></option>
                                                            <?php endwhile; ?>

                                                            <?php
                                                                $none_selected = "";

                                                                if($buyer_id == null || empty($buyer_id)){
                                                                    $none_selected = "selected";
                                                                }

                                                                echo "<option ".$none_selected." disabled >Not yet set</option>";
                                                            ?>
                                                        </select>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">

                                        <td>
                                            <div class="row px-2">
                                                <div class="col-4 p-3">
                                                    <label for="" style="font-weight:bold">Salesman:</label>
                                                    <select name="sel_salesman_id" id="sel_salesman_id" class="form-control" onchange="fetchSalesmanDetails()">
                                                        <?php
                                                            $select_salesman='SELECT * FROM mf_salesman ORDER BY salesman_name ASC';
                                                            $stmt_salesman	= $link->prepare($select_salesman);
                                                            $stmt_salesman->execute();
                                                            $selected_salesman_commission = '';

                                                            while($rs_salesman = $stmt_salesman->fetch()):
            
                                                                $selected_salesman = '';
                                                                if($salesman_id == $rs_salesman['salesman_id']){
                                                                    $selected_salesman = 'selected';
                                                                    $selected_salesman_commission = is_numeric($rs_salesman['commission']) ? (float)$rs_salesman['commission'] : '';
                                                                }
                                                                $commission_percent = is_numeric($rs_salesman['commission']) ? (float)$rs_salesman['commission'] : null;
                                                                $commission_percent_attr = ($commission_percent === null) ? '' : $commission_percent;
                                                                $commission_display = ($commission_percent === null) ? '' : rtrim(rtrim(number_format($commission_percent, 2, '.', ''), '0'), '.');
                                                                $option_label = $rs_salesman['salesman_name'];
                                                                if(strcasecmp(trim($option_label), '-None') !== 0 && $commission_display !== ''){
                                                                    $option_label .= " - ".$commission_display."%";
                                                                }
                                                            
                                                        ?>

                                                        <option value="<?php echo $rs_salesman['salesman_id'];?>" data-commission="<?php echo $commission_percent_attr;?>" <?php echo $selected_salesman;?>><?php echo $option_label;?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <input type="hidden" name="sel_salesman_commission" id="sel_salesman_commission" value="<?php echo htmlspecialchars($selected_salesman_commission, ENT_QUOTES); ?>">
                                                    
                                                </div>
                                    
                                                <div class="col-8 p-3">
                                                    <label for="" style="font-weight:bold">Commission Payment</label>
                                                    <input type="text" class="form-control" name="txt_com_pay" id="txt_com_pay" value="<?php echo $com_pay;?>" autocomplete="off">
                                                </div>                                               
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">

                                        <td>
                                            <div class="row px-2">
                                                <div class="col-3 p-3">
                                                    <label for="" style="font-weight:bold">Route:</label>
                                                    <select name="sel_route_id" id="sel_route_id" class="form-control">
                                                        <?php
                                                            $select_route='SELECT * FROM mf_routes ORDER BY route_desc ASC';
                                                            $stmt_route	= $link->prepare($select_route);
                                                            $stmt_route->execute();
                                                        
                                                            while($rs_route = $stmt_route->fetch()):
            
                                                                $selected_route = '';
                                                                if($route_id == $rs_route['route_id']){
                                                                    $selected_route = 'selected';
                                                                }
                                                            
                                                        ?>

                                                        <option value="<?php echo $rs_route['route_id'];?>" <?php echo $selected_route;?>><?php echo $rs_route['route_desc'];?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    
                                                </div>
                                    
                                                <div class="col-5 p-3">
                                                    <label for="" style="font-weight:bold">Ship To:</label>
                                                    <input type="text" class="form-control" name="shipto_1" id="shipto_1" value="<?php echo $shipto;?>" autocomplete="off">
                                                </div>                                               
                                                <div class="col-4 p-3">
                                                    <label for="" style="font-weight:bold">Status</label>
                                                    <select name="sel_ship_status" id="sel_ship_status" class="form-control">
                                                        <option value="For Shipping" <?php echo ($ship_status=='For Shipping'?'':'selected'); ?> >For Shipping</option>
                                                        <option value="Shipped" <?php echo ($ship_status=='Shipped'?'selected':''); ?>>Shipped</option>
                                                    </select>
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

                                    <!-- Partial Payment Section -->
                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row px-2">
                                                <div class="col-12 p-3">
                                                    <div class="d-flex align-items-center">
                                                        <label style="font-weight:bold" class="me-3">Partial Payments</label>
                                                        <button type="button" class="btn btn-success btn-sm fw-bold" onclick="openPartialPaymentModal()">
                                                            <i class="fas fa-plus"></i> Add Partial Payment
                                                        </button>
                                                    </div>
                                                    <div id="partial_payment_list" class="mt-2">
                                                        <!-- Partial payments will be loaded here -->
                                                    </div>
                                                    <div id="partial_payment_total" class="mt-2 fw-bold" style="display:none;">
                                                        Total Paid: <span id="total_partial_amount">0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td>
                                            <div class="row px-2">
                                                <div class="col-md-4 col-12 p-3">
                                                    <label style="font-weight:bold">Pay Date: (Sales)</label>
                                                    <input type="text" class="form-control date_picker" name="paydate_1" id="paydate_1" value="<?php echo $paydate;?>" readonly>
                                                </div>
                                        
                                                <div class="col-md-4 col-12 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Pay Details:</label>
                                                    <input type="text" class="form-control" name="payment_details_1" id="payment_details_1" value="<?php echo $payment_details;?>" autocomplete="off">
                                                </div>

                                                <div class="col-md-4 col-12 p-md-3 pt-0 p-3">
                                                    <label for="" style="font-weight:bold">Pay Date: (Salesman)</label>
                                                    <input type="text" class="form-control date_picker" name="paydate_salesman_1" id="paydate_salesman_1" value="<?php echo $paydate_salesman;?>"  readonly>
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

                                    <tr class="m-1 edit_row salesfile1">
                                        <td colspan="3">
                                            <div class="m-3">
                                                <label style="font-weight:bold">Remarks:</label>
                                                <textarea name="remarks_1" id="remarks_1" rows="3" class="form-control"><?php echo $remarks; ?></textarea>
                                            </div>
                                        </td>                                  
                                    </tr>

                                    <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3" style="max-width:33.333333%;min-width:260px;">
                                                <div>
                                                    <label for="userdesc_display" style="font-weight:bold">User:</label>
                                                    <input type="text" class="form-control" name="userdesc_display" id="userdesc_display" value="<?php echo htmlspecialchars($display_userdesc, ENT_QUOTES); ?>" readonly>
                                                    <input type="hidden" name="usercode_1" id="usercode_1" value="<?php echo htmlspecialchars($display_usercode, ENT_QUOTES); ?>">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr  style="border-bottom:3px solid #cccccc ">
                                        <td colspan="3">
                                            <div class="m-3" style=::>
                                                <div>
                                                    <div class="d-flex">
                                                        <label class="fw-bold">Image</label>       
                                                        <button class="btn btn-primary fw-bold" type="button" style="margin-left:auto" data-bs-toggle="modal" data-bs-target="#uploadImageModal" id="openUploadImageModal">Upload Image</button>
                                                    </div>

                                                    <div class="d-flex" style="flex-direction:column" id="image_div" name="image_div">

                                                        <?php 
                                                            if($image_loc !=null && !empty($image_loc) && $image_loc !== ""){

                    
                                                                echo "<img src='images_sales/".$image_loc."' alt='' style='width:250px;height:250px'>";

                                                                echo "<button class='btn btn-danger fw-bold' type='button' style='width:250px; margin-top:20px' onclick='delete_image()'>";
                                                                    echo "Delete Image ";
                                                                    echo "<i class='fas fa-trash'></i>";
                                                                echo "</button>";
                                                            }
                                                        ?>

                                              
                                                        
                                                    </div>
                                                </div>
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

            <div class='modal fade' id='uploadImageModal' tabindex='-1' aria-labelledby='uploadImageModalLabel' aria-hidden='true'>
                <div class='modal-dialog modal-dialog-centered'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='uploadImageModalLabel'>Upload Image</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                        <div class='modal-body'>
                            <div class='mb-3'>
                                <label class='form-label fw-bold' for='xfile_sal'>Choose Image</label>
                                <input class='form-control' type='file' id="xfile_sal" name="xfile_sal" accept='image/*'>
                            </div>
                            <div class='text-center'>
                                <img id='uploadImagePreview' class='img-fluid rounded d-none' alt='Selected image preview'>
                            </div>
                        </div>
                        <div class='modal-footer'>
                            <span class='text-danger small me-auto' id='uploadImageError'></span>
                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                            <button type='button' class='btn btn-primary' id='uploadImageSubmitBtn' onclick="submit_image()">Submit</button>
                        </div>
                    </div>
                </div>
            </div>

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
                                    <label for="">Wholesale Price:</label>
                                    <input type="number" name="wholesaleprc_add" id="wholesaleprc_add" class="form-control" autocomplete="off">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Current Stock:</label>
                                    <input type="text" style='color:red' name="current_stock_add" id="current_stock_add" class="form-control" autocomplete="off" readonly>
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

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="warcde_add">Warehouse</label>
                                    <select name="warcde_add" id="warcde_add" class="form-select">
                                        <option value="">Select Warehouse</option>
                                        <?php foreach($warehouse_options as $warehouse_option): ?>
                                            <option value="<?php echo htmlspecialchars($warehouse_option['warcde'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($warehouse_option['warehouse_name'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="warehouse_floor_id_add">Warehouse Floor</label>
                                    <select name="warehouse_floor_id_add" id="warehouse_floor_id_add" class="form-select">
                                        <option value="">Select Warehouse Floor</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="warehouse_staff_id_add">Warehouse Staff</label>
                                    <select name="warehouse_staff_id_add" id="warehouse_staff_id_add" class="form-select">
                                        <option value="">Select Warehouse Staff</option>
                                        <?php foreach($warehouse_staff_options as $staff_option): ?>
                                            <option value="<?php echo htmlspecialchars($staff_option['warehouse_staff_id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($staff_option['staff_name'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
                                    <label for="">Wholesale Price:</label>
                                    <input type="number" name="wholesaleprc_edit" id="wholesaleprc_edit" class="form-control" autocomplete="off">
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

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="warcde_edit">Warehouse</label>
                                    <select name="warcde_edit" id="warcde_edit" class="form-select">
                                        <option value="">Select Warehouse</option>
                                        <?php foreach($warehouse_options as $warehouse_option): ?>
                                            <option value="<?php echo htmlspecialchars($warehouse_option['warcde'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($warehouse_option['warehouse_name'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="warehouse_floor_id_edit">Warehouse Floor</label>
                                    <select name="warehouse_floor_id_edit" id="warehouse_floor_id_edit" class="form-select">
                                        <option value="">Select Warehouse Floor</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="warehouse_staff_id_edit">Warehouse Staff</label>
                                    <select name="warehouse_staff_id_edit" id="warehouse_staff_id_edit" class="form-select">
                                        <option value="">Select Warehouse Staff</option>
                                        <?php foreach($warehouse_staff_options as $staff_option): ?>
                                            <option value="<?php echo htmlspecialchars($staff_option['warehouse_staff_id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($staff_option['staff_name'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
                            <input type="hidden" name="allow_empty_location_edit" id="allow_empty_location_edit" value="0">
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

            <!-- Partial Payment Modal -->
            <div class="modal fade" id="partial_payment_modal" tabindex="-1" aria-labelledby="partialPaymentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="partialPaymentModalLabel">Add Partial Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Amount</label>
                                    <input type="number" step="0.01" name="partial_amount" id="partial_amount" class="form-control" autocomplete="off" placeholder="Enter payment amount" oninput="updateRemainingAmount()">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Check Number</label>
                                    <input type="text" name="partial_check_number" id="partial_check_number" class="form-control" autocomplete="off" placeholder="Enter check number (optional)">
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Date Paid</label>
                                    <input type="text" name="partial_date_paid" id="partial_date_paid" class="form-control date_picker" autocomplete="off" readonly>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <div class="alert alert-info" id="partial_payment_info">
                                        <strong>Total Amount:</strong> <span id="pp_trntot">0.00</span><br>
                                        <strong>Already Paid:</strong> <span id="pp_already_paid">0.00</span><br>
                                        <strong>Remaining:</strong> <span id="pp_remaining">0.00</span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="partial_payment_recid" id="partial_payment_recid" value="">
                            <input type="hidden" name="pp_trntot_hidden" id="pp_trntot_hidden" value="0">
                            <input type="hidden" name="pp_already_paid_hidden" id="pp_already_paid_hidden" value="0">
                            <div class="row m-2">
                                <div class="error_msg_partial_payment text-danger"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="savePartialPayment()">Save</button>
                        </div>
                    </div>
                </div>
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
            
            <div class="modal fade" id="drModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Print Delivery Reciept</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row m-3">
                            <div class="col-12">
                                <label for="">Date: </label>
                                <input type="text" name="date_report_dr" id="date_report_dr" class="date_picker form-control" autocomplete="off"  readonly>
                            </div>
                        </div>

                        <div class="row m-3">
                            <div class="col-12">
                                <label for="">Shipping Method:</label>
                                <input type="text" name="shipping_method_dr" id="shipping_method_dr" class="form-control" autocomplete="off" >
                            </div>
                        </div>                        
                            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="print_dr()">Print</button>
                        </div>
                    </div>
                </div>
            </div>            
            
                        
            <input type="hidden" name="txt_output_type" id="txt_output_type">

            <input type="hidden" name="xevent_itmsearch_hidden" id="xevent_itmsearch_hidden">
            <input type="hidden" name="recid_so_hidden" id="recid_so_hidden">

            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'  value="<?php if(isset($_POST['txt_pager_totalrec'])){echo $_POST['txt_pager_totalrec'];}?>">
            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'  value="<?php if(isset($_POST['txt_pager_maxpage'])){echo $_POST['txt_pager_maxpage'];}?>" >
            <input type='hidden' name='txt_pager_pageno_h' id='txt_pager_pageno_h'  value="<?php if(isset($_POST['txt_pager_pageno_h'])){echo $_POST['txt_pager_pageno_h'];}?>">    

            <input type="hidden" name="waybill_num_search_h" id="waybill_num_search_h" value="<?php if(isset($_POST['waybill_num_search_h'])){echo $_POST['waybill_num_search_h'];}?>">
            <input type="hidden" name="orderby_search_h" id="orderby_search_h" value="<?php if(isset($_POST['orderby_search_h'])){echo $_POST['orderby_search_h'];}?>">
            <input type="hidden" name="recid_hidden" id="recid_hidden" value="<?php if(isset($_POST['recid_hidden'])){echo $_POST['recid_hidden'];}?>">    
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
            <input type="hidden" name="paid_search_h" id="paid_search_h" value="<?php if(isset($_POST['paid_search_h'])){echo $_POST['paid_search_h'];}?>">
            <input type="hidden" name="paid_salesman_search_h" id="paid_salesman_search_h" value="<?php if(isset($_POST['paid_salesman_search_h'])){echo $_POST['paid_salesman_search_h'];}?>">
            <input type="hidden" name="crud_msg_h" id="crud_msg_h" value="<?php if(isset($_POST['crud_msg_h'])){echo $_POST['crud_msg_h'];}?>">
            <input type="hidden" name="crud_msg_h2" id="crud_msg_h2">
            <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php if(isset($_POST['trncde_hidden'])){echo $_POST['trncde_hidden'];}?>">
            <input type="hidden" name="scrolly_hidden" id="scrolly_hidden" value="<?php if(isset($_POST['scrolly_hidden'])){echo $_POST['scrolly_hidden'];}?>">
            <input type="hidden" name="scrolly_hidden2" id="scrolly_hidden2" value="Y">
            <input type="hidden" name="ordernum_hidden_val" id="ordernum_hidden_val" value="<?php echo $ordernum?>">
        </form>

        <script>

        // Handle preview of the selected image and reset modal state.
        const uploadImageInput = document.getElementById('xfile_sal');
        const uploadImagePreview = document.getElementById('uploadImagePreview');
        const uploadImageError = document.getElementById('uploadImageError');
        const uploadImageModal = document.getElementById('uploadImageModal');

        if (uploadImageInput) {
            uploadImageInput.addEventListener('change', function (event) {
                const files = event.target.files || [];
                const file = files.length > 0 ? files[0] : null;

                if (!file) {
                    if (uploadImagePreview) {
                        uploadImagePreview.src = '#';
                        uploadImagePreview.classList.add('d-none');
                    }
                    if (uploadImageError) {
                        uploadImageError.textContent = '';
                    }
                    return;
                }

                if (!file.type || !file.type.startsWith('image/')) {
                    if (uploadImageError) {
                        uploadImageError.textContent = 'Please select a valid image file.';
                    }
                    uploadImageInput.value = '';
                    if (uploadImagePreview) {
                        uploadImagePreview.src = '#';
                        uploadImagePreview.classList.add('d-none');
                    }
                    return;
                }

                if (uploadImageError) {
                    uploadImageError.textContent = '';
                }

                const reader = new FileReader();
                reader.onload = function (loadEvent) {
                    if (uploadImagePreview) {
                        uploadImagePreview.src = loadEvent.target.result;
                        uploadImagePreview.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        if (uploadImageModal) {
            uploadImageModal.addEventListener('hidden.bs.modal', function () {
                if (uploadImageInput) {
                    uploadImageInput.value = '';
                }
                if (uploadImagePreview) {
                    uploadImagePreview.src = '#';
                    uploadImagePreview.classList.add('d-none');
                }
                if (uploadImageError) {
                    uploadImageError.textContent = '';
                }
            });
        }

        var trncde = $("#trncde_hidden").val();
        var warehouseFloorMap = <?php echo json_encode($warehouse_floor_map); ?>;

        function rebuildFloorOptions(selectId, warcde, selectedFloorId, allowNone){
            var $select = $("#" + selectId);
            if($select.length === 0){
                return;
            }

            var options = allowNone ? "<option value=''>None</option>" : "<option value=''>Select Warehouse Floor</option>";
            var floors = warehouseFloorMap[warcde] || [];

            for(var i = 0; i < floors.length; i++){
                var floor = floors[i];
                var selected = (selectedFloorId && selectedFloorId === floor.warehouse_floor_id) ? " selected" : "";
                options += "<option value='" + floor.warehouse_floor_id + "'" + selected + ">" + floor.floor_no + "</option>";
            }

            $select.html(options);
        }

        function setEditNoneOption(selectId, placeholderText, selectedValue, allowNone){
            var $select = $("#" + selectId);
            if($select.length === 0){
                return;
            }

            $select.find("option[value='']").remove();

            if(allowNone && selectedValue === ""){
                $select.prepend("<option value=''>None</option>");
            }else{
                $select.prepend("<option value=''>" + placeholderText + "</option>");
            }

            $select.val(selectedValue);
        }

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

            });

            $("#warcde_add").on("change", function(){
                rebuildFloorOptions("warehouse_floor_id_add", $(this).val(), "", false);
            });

            $("#warcde_edit").on("change", function(){
                rebuildFloorOptions("warehouse_floor_id_edit", $(this).val(), "", $(this).find("option:selected").text() === "None");
            });
        });

        function return_po(){

            $("#insert_modal_sales_po").modal("hide");
            $("#insert_modal_sales").modal("show");

        }

        function return_po_edit(){

            $("#insert_modal_sales_po").modal("hide");
            $("#edit_modal_sales").modal("show");

        }

        function print_dr(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "dr_rep_sales.php";
            document.forms.myforms.submit();
        }

        function select_item_modal(xitmcde, xitmdsc, xevent_action, wholesale_prc,current_stock, xnew_itm){

            if(xevent_action == 'add'){

                $(".error_msg_add_modal").html("");

                $("#itmcde_add_hidden").val(xitmcde);
                $("#itmcde_add").val(xitmdsc);
                $("#wholesaleprc_add").val(wholesale_prc);
                $("#current_stock_add").val(current_stock);

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
                $("#wholesaleprc_edit").val(wholesale_prc);
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

        function submit_image(){
            // Create a new FormData object
            var formData = new FormData();
            
            // Get the file input element
            var fileInput = document.getElementById('xfile_sal');
            
            // Append the file (get the first file from the input)
            if(fileInput.files.length > 0) {
                formData.append('xfile_sal', fileInput.files[0]);
            }

            //get the value of the recid
            var recid_hidden_val = $("#recid_hidden").val();
            
            // Append other parameters you want to send
            formData.append('recid_hidden', recid_hidden_val);
            formData.append('event_action', 'submit_image');
            
            // Send via AJAX
            $.ajax({
                url: 'trn_salesfile2_ajax_img.php',
                type: 'POST',
                data: formData,
                contentType: false,  // IMPORTANT: Don't set content type
                processData: false,  // IMPORTANT: Don't process the data
                success: function(response) {

                    var result = JSON.parse(response);

                    alert("sucessfully uploaded image");
                    $("#uploadImageModal").modal("hide");


                    var filename = result["new_filename"];

                    var html = `
                            <img src='images_sales/${filename}' alt='' style='width:250px;height:250px'>
                            <button class='btn btn-danger fw-bold' type='button' style='width:250px; margin-top:20px' onclick='delete_image()'>
                                Delete Image 
                                <i class='fas fa-trash'></i>
                            </button>
                        `;

                    $("#image_div").html(html);

                },
                error: function(xhr, status, error) {
                }
            });
        }

        function delete_image(){

            var formData = new FormData();

            var recid_hidden_val = $("#recid_hidden").val();
            formData.append('recid_hidden', recid_hidden_val);
            formData.append('event_action', 'delete_image');
            // Send via AJAX
            $.ajax({
                url: 'trn_salesfile2_ajax_img.php',
                type: 'POST',
                data: formData,
                contentType: false,  // IMPORTANT: Don't set content type
                processData: false,  // IMPORTANT: Don't process the data
                success: function(response) {
                        $("#image_div").html("");
                },
                error: function(xhr, status, error) {
                }
            });
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
            var orderby_1 = document.getElementById("orderby_1").value;

            if($('#itmcde_add').is(':disabled')){
                $("#itmcde_add").attr("disabled", false);
                var xdata = $("#insert_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action+"&orderby_1="+orderby_1;
                $("#itmcde_add").attr("disabled", true);
            }else{
                var xdata = $("#insert_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action+"&orderby_1="+orderby_1;    
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

        function fetchSalesmanDetails() {

            var salesmanDropdown = $('#sel_salesman_id');
            var selectedOption = salesmanDropdown.find('option:selected');
            var salesman_id = salesmanDropdown.val();
            var salesmanLabel = $.trim(selectedOption.text());
            var normalizedLabel = salesmanLabel.toLowerCase();

            if (!salesman_id || selectedOption.length === 0 || selectedOption.prop('disabled') || normalizedLabel === '-none') {
                $('#sel_salesman_commission').val('');
                $("#txt_com_pay").val('');
                return;
            }

            var optionCommission = selectedOption.data('commission');
            if (typeof optionCommission !== "undefined") {
                $('#sel_salesman_commission').val(optionCommission);
            }

            var trntotVal = $('#trntot_1').val() || '0';
            var trntotClean = trntotVal.toString().replace(/,/g, '');
            if (!trntotClean) {
                trntotClean = 0;
            }

            jQuery.ajax({    
                data:{
                    sel_salesman_id: salesman_id,
                    trntot: trntotClean
                },
                dataType:"json",
                type:"post",
                url:"trn_salesfile2_getsalesman_ajax.php", 
                    success: function(xdata){
                        if(typeof xdata["commission_percent"] !== "undefined"){
                            $('#sel_salesman_commission').val(xdata["commission_percent"]);
                        }

                        if(typeof xdata["computed_compay"] !== "undefined"){
                            $("#txt_com_pay").val(xdata["computed_compay"]);
                        }else{
                            $("#txt_com_pay").val('');
                        }

                    }
            })
        }


        $(document).ready(function(){
            // Keep saved commission payment for existing records; auto-compute only when empty.
            if($.trim($("#txt_com_pay").val()) === ''){
                fetchSalesmanDetails();
            }
        });

        function search_so_edit(xevent_action,xdocnum,xrecid){

            $("#edit_modal_sales").modal("hide");
            var cuscde_val = $("#cusname_1").val();
            var selected_po = $("#so_edit").val();
            var recid_tranfile2_hidden = $("#recid_tranfile2_hidden").val();
            //var selected_po_hidden = $("#po_edit_hidden").val();

            //var orderby_1 = $("#orderby_1").val();
            var orderby_1 = document.getElementById("orderby_1").value;

            if(xrecid){
                var recid_so_hidden_val = $("#recid_so_hidden").val(xrecid);
            }else{
                var recid_so_hidden_val = $("#recid_so_hidden").val();
            }

            if($('#itmcde_edit').is(':disabled')){
                //enable the itcde para mapass yung data
                var xdata = $("#edit_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_so_hidden="+recid_so_hidden_val+"&orderby_1="+orderby_1;
            }else{
                var xdata = $("#edit_modal_sales *").serialize("")+"&cuscde="+cuscde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_so_hidden="+recid_so_hidden_val+"&orderby_1="+orderby_1;
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

            var ordernum_hidden_val = $("#ordernum_hidden_val").val();

            switch(event){
                case "save_exit":
             
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_exit&docnum="+docnum+"&ordernum_hidden_val="+ordernum_hidden_val;
                    break;
                case "save_new":
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_new&docnum="+docnum+"&ordernum_hidden_val="+ordernum_hidden_val;
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
                    $("#warcde_add").val('');
                    rebuildFloorOptions("warehouse_floor_id_add", "", "", false);
                    $("#warehouse_staff_id_add").val('');

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
                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&cusname_1="+cusname_1+"&docnum="+docnum+"&recid="+recid+"&orderby_1="+orderby_1+"&xtrndte_1="+trndte_1_val+"&hidden_itmcde_edit="+hidden_itmcde_edit+"&hidden_itmqty_edit="+hidden_itmqty_edit+"&"+$(".salesfile1 *").serialize();
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

                            $("#docnum_1").val(xdata["new_docnum"]);
                            $("#docnum_hidden").val(xdata["new_docnum"]);
                            // $("#trndte_1").val('');
                            $("#trntot_1").val('');
                            $("#cusname_1").val($("#cusname_1 option:first").val());
                            $("#orderby_1").val($("#orderby_1 option:first").val());
                            $("#sel_salesman_id").val($("#sel_salesman_id option:first").val());
                            $("#sel_route_id").val($("#sel_route_id option:first").val());
                            $("#shipto_1").val('');
                            $("#sel_ship_status").val($("#sel_ship_status option:first").val());
                            $("#paydate_1").val('');
                            $("#paydate_salesman_1").val('');
                            $("#payment_details_1").val('');
                            $("#txt_com_pay").val('');
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
                            $("#wholesaleprc_edit").val(xdata["retEdit"]["wholesaleprc"]);
                            $("#allow_empty_location_edit").val(xdata["retEdit"]["allow_empty_location"] || "0");
                            var allowEmptyLocation = (xdata["retEdit"]["allow_empty_location"] || "0") === "1";
                            setEditNoneOption("warcde_edit", "Select Warehouse", xdata["retEdit"]["warcde"], allowEmptyLocation);
                            rebuildFloorOptions("warehouse_floor_id_edit", xdata["retEdit"]["warcde"], xdata["retEdit"]["warehouse_floor_id"], allowEmptyLocation);
                            $("#warehouse_staff_id_edit").val(xdata["retEdit"]["warehouse_staff_id"]);

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

                        if(typeof xdata["trntot"] !== "undefined" && xdata["trntot"] !== null){
                            $("#trntot_1").val(xdata["trntot"]);
                            if($.trim($("#txt_com_pay").val()) === ''){
                                fetchSalesmanDetails();
                            }
                        }
                        if(typeof loadPartialPayments === "function"){
                            loadPartialPayments();
                        }
                }
            })
        }


        function print_declaration(){

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "dr_declaration_form.php";
            document.forms.myforms.submit();
        }



        function orderby_change()
        {
          
            var xbuyer = document.getElementById("orderby_1").value;
            //alert(xbuyer);      
            
            jQuery.ajax({
            url: 'trn_salesfile2_ajax2.php', // Replace with your server-side script URL
            type: 'POST', // Or 'GET' depending on your needs
            dataType:"json",
            data: { xbuyer: xbuyer },
            success: function(xret) {
               // $('#result').html(response); // Display the response from the server
               console.log(xret);
               //alert(xret['salesman_name']);
               jQuery('#shipto_1').val(xret['buyer_address']);

               if(xret['salesman_id'] != "" && xret['salesman_id'] != null){
                jQuery('#sel_salesman_id').val(xret['salesman_id']);
               }

               if(xret['route_id'] != "" && xret['route_id'] != null){
                jQuery('#sel_route_id').val(xret['route_id']);
               }

            },
            error: function(xhr, status, error) {
                // Handle errors
                console.error("An error occurred: " + error);
            }
            });

        }

        // ==================== PARTIAL PAYMENT FUNCTIONS ====================

        // Load partial payments on page load
        $(document).ready(function() {
            loadPartialPayments();

            // Initialize date picker for partial payment
            $('#partial_date_paid').datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true
            });
        });

        function loadPartialPayments() {
            var docnum = $.trim($("#docnum_1").val());
            if (!docnum || docnum == '') {
                $("#partial_payment_list").html('<span class="text-muted">Save the transaction first to add partial payments.</span>');
                $("#partial_payment_total").hide();
                return;
            }

            $.ajax({
                url: "partial_payment_ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    event_action: "getList",
                    docnum: docnum
                },
                success: function(data) {
                    if (data.status == 1) {
                        renderPartialPaymentList(data.payments, data.total_paid);
                    } else {
                        $("#partial_payment_list").html('<span class="text-danger">' + data.msg + '</span>');
                        $("#partial_payment_total").hide();
                    }
                },
                error: function(error) {
                    console.error("Error loading partial payments: " + error);
                    $("#partial_payment_list").html('<span class="text-danger">Unable to load partial payments.</span>');
                    $("#partial_payment_total").hide();
                }
            });
        }

        function renderPartialPaymentList(payments, totalPaid) {
            var html = '';
            if (payments.length > 0) {
                html += '<table class="table table-sm table-bordered">';
                html += '<thead><tr><th>Date Paid</th><th>Check #</th><th>Amount</th><th>Action</th></tr></thead>';
                html += '<tbody>';
                for (var i = 0; i < payments.length; i++) {
                    var payment = payments[i];
                    html += '<tr>';
                    html += '<td>' + payment.date_paid_formatted + '</td>';
                    html += '<td>' + escapeHtml(payment.check_number || '-') + '</td>';
                    html += '<td class="text-end">' + parseFloat(payment.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                    html += '<td class="text-center">';
                    html += '<button type="button" class="btn btn-warning btn-sm me-1" onclick="editPartialPayment(' + payment.recid + ')"><i class="fas fa-edit"></i></button>';
                    html += '<button type="button" class="btn btn-danger btn-sm" onclick="deletePartialPayment(' + payment.recid + ')"><i class="fas fa-trash"></i></button>';
                    html += '</td>';
                    html += '</tr>';
                }
                html += '</tbody>';
                html += '</table>';
                $("#partial_payment_total").show();
                $("#total_partial_amount").text(parseFloat(totalPaid).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            } else {
                html = '<span class="text-muted">No partial payments yet.</span>';
                $("#partial_payment_total").hide();
            }
            $("#partial_payment_list").html(html);
        }

        function parseAmountValue(value) {
            var normalized = (value || '').toString().replace(/,/g, '').trim();
            var parsed = parseFloat(normalized);
            return isNaN(parsed) ? 0 : parsed;
        }

        function escapeHtml(value) {
            return (value || '').toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function setPartialPaymentInfo(trntot, alreadyPaid) {
            var totalAmount = parseAmountValue(trntot);
            var paidAmount = parseAmountValue(alreadyPaid);
            var initialRemaining = totalAmount - paidAmount;

            $("#pp_trntot_hidden").val(totalAmount);
            $("#pp_already_paid_hidden").val(paidAmount);
            $("#pp_trntot").text(totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#pp_already_paid").text(paidAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#pp_remaining").text(initialRemaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            updateRemainingAmount();
        }

        function updateRemainingAmount() {
            var totalAmount = parseAmountValue($("#pp_trntot_hidden").val());
            var paidAmount = parseAmountValue($("#pp_already_paid_hidden").val());
            var currentInput = parseAmountValue($("#partial_amount").val());
            var remaining = totalAmount - paidAmount - currentInput;

            $("#pp_remaining").text(remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            if (remaining < 0) {
                $("#pp_remaining").addClass("text-danger");
            } else {
                $("#pp_remaining").removeClass("text-danger");
            }
        }

        function openPartialPaymentModal() {
            var docnum = $.trim($("#docnum_1").val());
            var trntot = parseAmountValue($("#trntot_1").val());

            // Reset form
            $("#partial_amount").val('');
            $("#partial_check_number").val('');
            $("#partial_payment_recid").val('');
            $(".error_msg_partial_payment").html('');
            $("#partialPaymentModalLabel").text('Add Partial Payment');
            $("#pp_remaining").removeClass("text-danger");

            // Set today's date as default
            var today = new Date();
            var month = String(today.getMonth() + 1).padStart(2, '0');
            var day = String(today.getDate()).padStart(2, '0');
            var year = today.getFullYear();
            $("#partial_date_paid").val(month + '/' + day + '/' + year);

            // Get already paid amount
            $.ajax({
                url: "partial_payment_ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    event_action: "getTotalPaid",
                    docnum: docnum
                },
                success: function(data) {
                    var alreadyPaid = parseFloat(data.total_paid) || 0;
                    setPartialPaymentInfo(trntot, alreadyPaid);

                    $("#partial_payment_modal").modal("show");
                },
                error: function(error) {
                    console.error("Error: " + error);
                    setPartialPaymentInfo(trntot, 0);
                    $("#partial_payment_modal").modal("show");
                }
            });
        }

        function editPartialPayment(recid) {
            var docnum = $.trim($("#docnum_1").val());
            var trntot = parseAmountValue($("#trntot_1").val());

            $.ajax({
                url: "partial_payment_ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    event_action: "getOne",
                    recid: recid
                },
                success: function(data) {
                    if (data.status == 1) {
                        $("#partial_amount").val(data.payment.amount);
                        $("#partial_check_number").val(data.payment.check_number || '');
                        $("#partial_date_paid").val(data.payment.date_paid_formatted);
                        $("#partial_payment_recid").val(data.payment.recid);
                        $("#partialPaymentModalLabel").text('Edit Partial Payment');
                        $(".error_msg_partial_payment").html('');

                        // Get already paid (excluding current payment)
                        var alreadyPaid = parseFloat(data.total_paid_excluding) || 0;
                        $("#pp_remaining").removeClass("text-danger");
                        setPartialPaymentInfo(trntot, alreadyPaid);

                        $("#partial_payment_modal").modal("show");
                    } else {
                        alert(data.msg || "Unable to load partial payment details.");
                    }
                },
                error: function(error) {
                    console.error("Error: " + error);
                }
            });
        }

        function savePartialPayment() {
            var docnum = $.trim($("#docnum_1").val());
            var amount = parseAmountValue($("#partial_amount").val());
            var checkNumber = $.trim($("#partial_check_number").val());
            var datePaid = $("#partial_date_paid").val();
            var recid = $("#partial_payment_recid").val();
            var trntot = parseAmountValue($("#pp_trntot_hidden").val()) || parseAmountValue($("#trntot_1").val());
            var alreadyPaid = parseAmountValue($("#pp_already_paid_hidden").val());

            // Validation
            if (!docnum || docnum == '') {
                $(".error_msg_partial_payment").html('<b>Please save the transaction first before adding partial payments.</b>');
                return;
            }

            if (amount <= 0) {
                $(".error_msg_partial_payment").html('<b>Amount must be greater than 0.</b>');
                return;
            }

            if (!datePaid || datePaid == '') {
                $(".error_msg_partial_payment").html('<b>Date Paid is required.</b>');
                return;
            }

            if (trntot > 0 && (alreadyPaid + amount) > trntot) {
                $(".error_msg_partial_payment").html('<b>Total partial payments cannot exceed the transaction total.</b>');
                return;
            }

            var eventAction = recid ? "update" : "insert";

            $.ajax({
                url: "partial_payment_ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    event_action: eventAction,
                    recid: recid,
                    docnum: docnum,
                    amount: amount,
                    check_number: checkNumber,
                    date_paid: datePaid,
                    trntot: trntot
                },
                success: function(data) {
                    if (data.status == 1) {
                        $("#partial_payment_modal").modal("hide");
                        loadPartialPayments();
                    } else {
                        $(".error_msg_partial_payment").html('<b>' + data.msg + '</b>');
                    }
                },
                error: function(error) {
                    console.error("Error: " + error);
                    $(".error_msg_partial_payment").html('<b>An error occurred. Please try again.</b>');
                }
            });
        }

        function deletePartialPayment(recid) {
            if (!confirm('Are you sure you want to delete this partial payment?')) {
                return;
            }

            $.ajax({
                url: "partial_payment_ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    event_action: "delete",
                    recid: recid
                },
                success: function(data) {
                    if (data.status == 1) {
                        loadPartialPayments();
                    } else {
                        alert(data.msg);
                    }
                },
                error: function(error) {
                    console.error("Error: " + error);
                }
            });
        }

        // ==================== END PARTIAL PAYMENT FUNCTIONS ====================

        </script>


<?php
    require "includes/main_footer.php";
?>

