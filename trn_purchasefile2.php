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
// // if($hide_price_crud == 1 && $_SESSION['userdesc'] !='admin'){
// //     $visibility_hidden = 'position: absolute;left: -9999px; /* Move it off-screen */';
// // }else
// // {
//     $visibility_hidden = '';
// // }

$header_usercode = '';
$is_edit_mode = false;

if(isset($_POST['recid_hidden']) && !empty($_POST['recid_hidden'])){
    $is_edit_mode = true;
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

        if($hide_price_crud   != 1 || $_SESSION['userdesc'] == 'admin'){
            $visibility_hidden = '';
        }else{
            $visibility_hidden = 'position: absolute;left: -9999px; /* Move it off-screen */';
        }

        $remarks  = $rs_docnum1['remarks'];
        $ordernum  = $rs_docnum1['ordernum'];
        $po_qr_id_view  = $rs_docnum1['po_qr_id'];
        $header_usercode = isset($rs_docnum1['usercode']) ? trim((string)$rs_docnum1['usercode']) : '';


        //to check if its already matched to a purchase order
        $select_db_po_match="SELECT * FROM purchasesorderfile1 WHERE po_qr_id=?";
        $stmt_po_match	= $link->prepare($select_db_po_match);
        $stmt_po_match->execute(array($po_qr_id_view));
        $rs_po_match = $stmt_po_match->fetch();

        if(!empty($rs_po_match)){
            $po_qr_is_readonly  = "readonly";
        }else{
            $po_qr_is_readonly  = "";
        }

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
    $visibility_hidden = '';
    date_default_timezone_set('Asia/Manila');
    $disabled_price = '';
    $trndate = date('m/d/Y');
    $trntot = '';
    $customer_name = '';
    $suppcde  = '';
    $orderby = '';
    $shipto = '';
    $paydate  = NULL;
    $payment_details  = '';
    $remarks  = '';
    $ordernum = '';
    $ptype  = "";
    $po_qr_id_view  = "";
    $po_qr_is_readonly  = "";

    $rs_purchasesdf = false;
    try{
        $select_db_purchasesdf="SELECT * FROM default_purchases WHERE is_selected='1' LIMIT 1";
        $stmt_purchasesdf	= $link->prepare($select_db_purchasesdf);
        $stmt_purchasesdf->execute();
        $rs_purchasesdf = $stmt_purchasesdf->fetch();
    }catch(PDOException $e){
        $rs_purchasesdf = false;
    }

    if(!empty($rs_purchasesdf)){
        if(!empty($rs_purchasesdf['shipto_default'])){
            $shipto = $rs_purchasesdf['shipto_default'];
        }

        if(!empty($rs_purchasesdf['orderby_default'])){
            $orderby = $rs_purchasesdf['orderby_default'];
        }
    }
}

if(isset($_POST['po_qr_id_hidden']) && !empty($_POST['po_qr_id_hidden'])){
    $po_qr_id_view = $_POST['po_qr_id_hidden'];
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

// Fetch all Unit of Measure options
$uom_options = array();
$stmt_uom = $link->prepare("SELECT unmcde, unmdsc FROM itemunitmeasurefile ORDER BY unmdsc ASC");
$stmt_uom->execute();
while($rs_uom = $stmt_uom->fetch()){
    $uom_options[] = array(
        'unmcde' => $rs_uom['unmcde'],
        'unmdsc' => $rs_uom['unmdsc']
    );
}
$default_uom_code = '';
$default_uom_desc = '';
$ordered_uom_options = array();
foreach($uom_options as $uom_option){
    $uom_code = trim((string)$uom_option['unmcde']);
    $uom_desc = strtolower(trim((string)$uom_option['unmdsc']));
    if($default_uom_code === '' && ($uom_desc === 'pcs' || strtolower($uom_code) === 'pcs')){
        $default_uom_code = $uom_code;
        $default_uom_desc = trim((string)$uom_option['unmdsc']);
        array_unshift($ordered_uom_options, $uom_option);
        continue;
    }

    $ordered_uom_options[] = $uom_option;
}

if($default_uom_desc === '' && !empty($ordered_uom_options)){
    $default_uom_desc = trim((string)$ordered_uom_options[0]['unmdsc']);
}

$base_uom_display = strtolower($default_uom_desc) === 'pcs' ? 'pc' : $default_uom_desc;



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

                                <?php if(isset($_POST['po_qr_id_hidden']) && !empty($_POST['po_qr_id_hidden'])):?>
                                
                                <div class="container d-flex justify-content-center align-items-end w-100">
                                    <div class="alert alert-danger" role="alert" style='margin-top:15px'>
                                        No match found for Purchase Order id: <b><?php echo $_POST['po_qr_id_hidden']; ?></b> </br>
                                        Input a Manual Entry 
                                    </div>
                                </div>
                 
                                <?php endif;?>

                                <div class="row d-flex justify-content-center w-100 mt-0 pt-0">
                                    <table class="bg-white w-75 shadow rounded user_access_tbl my-4"  id="file2_table" style="border-radius: 0.75rem!important;border-collapse:collapse;height:400px;min-width:0">

                                        <tr class="m-1 salesfile1" style="border-bottom:3px solid #cccccc">
                                            <td colspan="3"> 

                                                <div class="row">

                                                    <div class="col-md-4 col-6">
                                                        <div class="m-2">
                                                            <h2>Purchases</h2>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-8 col-12 d-flex align-items-center justify-content-end">

                                                        <div class="m-2 row">

                                                            <?php if(isset($_POST['po_qr_id_hidden']) && !empty($_POST['po_qr_id_hidden'])):?>
                                                                <div class="col-md-12 col-12 d-flex justify-content-start justify-content-md-end ps-md-1 ps-0 pe-1 mt-1 mt-md-0">
                                                                    <input type="button" class="btn btn-success fw-bold" style="width:250px;" value="Save and Return to Scanning" onclick="salesfile2('save_qr_exit')">
                                                                </div>

                                                            <?php else:?>
                                                                <div class="col-md-3 col-4 d-flex justify-content-start ps-0 pe-1">
                                                                    <input type="button" class="btn btn-danger fw-bold" style="width:100px;" value="Back" onclick="back_page()">                                           
                                                                </div>

                                                                <div class="col-md-4 col-4 d-flex justify-content-start ps-0 pe-0">
                                                                    <input type="button" class="btn btn-success fw-bold" style="width:150px;" value="Save and Exit" onclick="salesfile2('save_exit')">
                                                                </div>

                                                                <div class="col-md-5 col-5 d-flex justify-content-start justify-content-md-end ps-md-1 ps-0 pe-1 mt-1 mt-md-0">
                                                                    <input type="button" class="btn btn-success fw-bold" style="width:175px;" value="Save and Add Next" onclick="salesfile2('save_new')">
                                                                </div>

                                                            <?php endif;?>


                                                        </div>
                                                    </div>

                                                </div>

                                            </td>
                                        </tr>


                                        <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">

                                            <td>

                                                <div class="row px-2">
                                                    <div class="p-3 col-md-4 col-6">
                                                        <label for="" style="font-weight:bold">Doc. Num:</label>
                                                        <input type="text" class="form-control" name="docnum_1" style="font-weight:bold" id="docnum_1" value="<?php echo $docnum;?>" readonly>
                                                    </div>
                                        
                                                    <div class="p-3 col-md-4 col-6">
                                                        <label for="" style="font-weight:bold">Tran. Date:<span style="color:red">*</span></label>
                                                        <input type="text" class="form-control date_picker"  name="trndte_1" id="trndte_1" value="<?php echo $trndate;?>" readonly>
                                                    </div>
                                            
                                                    <div class="p-3 col-md-4 col-6 p-md-3 pt-0 p-3" >
                                                        <label for="" style="font-weight:bold">Total:</label>
                                                        <input type="text" class="form-control" name="trntot_1" id="trntot_1" value="<?php echo $trntot;?>" autocomplete="off" readonly>
                                                    </div>
                                                </div>
                                                
                                            </td>                                    
                                        </tr>


                                        <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                            

                                            
                                            <td>
                                                <div class="row px-2">
                                                    <div class="p-3 col-md-4 col-6">              
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
                                    
                                                    <div class="p-3 col-md-4 col-6 ">
                                                        <label for="" style="font-weight:bold">Order Number:</label>
                                                        <input type="text" class="form-control" name="ordernum_1" id="ordernum_1" value="<?php echo $ordernum;?>" autocomplete="off">
                                                    </div>                                       
                                        
                                                    <div class="p-3 col-md-4 col-6 p-md-3 pt-0 p-3">
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
                                                <div class="row px-2" style="<?php echo $visibility_hidden; ?>">
                                                    <div class="p-3 col-md-4 col-6">
                                                        <label style="font-weight:bold">Pay Date:</label>
                                                        <input type="text" class="form-control date_picker" name="paydate_1" id="paydate_1" value="<?php echo $paydate;?>" readonly>
                                                    </div>
                                            
                                                    <div class="p-3 col-md-4 col-6">
                                                        <label for="" style="font-weight:bold">Payment Details:</label>
                                                        <input type="text" class="form-control" name="payment_details_1" id="payment_details_1" value="<?php echo $payment_details;?>" autocomplete="off">
                                                    </div>  
                                                    
                                                    <!-- <div class="p-3 col-md-4 col-6">
                                                        <label for="" style="font-weight:bold">Purchase Type:</label>
                                                        <select name="purchase_type_1" id="purchase_type_1" class="form-select">
                                                        <?php

                                                            $ptype_trade = "";
                                                            $ptype_nontrade = "";

                                                            if($ptype == 'trade'){
                                                                $ptype_trade = 'selected';
                                                            }else if($ptype == 'non-trade'){
                                                                $ptype_nontrade = 'selected';
                                                            }

                                                            echo "<option ".$ptype_trade.">trade</option>";
                                                            echo "<option ".$ptype_nontrade.">non-trade</option>";
                                                        ?>
                                                        </select>
                                                    </div>                                                       -->
                                                </div>
                                        
                                            </td>                                    
                                        </tr>

                                        <tr class="m-1 edit_row salesfile1" style="border-bottom:3px solid #cccccc ">
                                            <td colspan="3">
                                                <div class="row px-2">

                                                    <div class="p-3 col-md-8 col-6">
                                                        <label style="font-weight:bold">Remarks:</label>
                                                        <textarea name="remarks_1" id="remarks_1" rows="3" class="form-control"><?php echo $remarks; ?></textarea>
                                                    </div>


                                                    <?php if(isset($_POST['po_qr_id_hidden']) && !empty($_POST['po_qr_id_hidden'])):?>

                                                        <div class="p-3 col-md-4 col-6">
                                                            <label for="" style="font-weight:bold">Purchase Order ID:</label>
                                                            <input type="text" class="form-control" name="po_order_id" id="po_order_id" value="<?php echo $po_qr_id_view;?>" autocomplete="off" readonly>
                                                        </div>

                                                    <?php else:?>

                                                        <div class="p-3 col-md-4 col-6">
                                                            <label for="" style="font-weight:bold">Purchase Order ID:</label>
                                                            <input type="text" class="form-control" name="po_order_id" id="po_order_id" value="<?php echo $po_qr_id_view;?>" autocomplete="off" <?php echo $po_qr_is_readonly ;?> >
                                                        </div>



                                                    <?php endif;?>

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

                                        <tr class="m-1 salesfile1" id="tr_access_data">
                                            <td id="main_chk_div" colspan="3">
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
            <input type="hidden" name="multi_itm_select" id="multi_itm_select">
            <input type="hidden" name="multi_itm_select_original" id="multi_itm_select_original">
            <input type="hidden" name="usercode_access_hidden" id="usercode_access_hidden" value="<?php if(isset($_POST["usercode_hidden"])){echo $_POST["usercode_hidden"];}?>">
            <input type="hidden" name='docnum_hidden' id="docnum_hidden" value="<?php echo $docnum; ?>">

            <div class="modal fade" id="insert_modal_sales" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Purchase</h5>
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
                                    <label for="">Unit of Measure</label>
                                    <select name="unmcde_add" id="unmcde_add" class="form-select" disabled>
                                        <?php if($default_uom_code === ''): ?>
                                            <option value="">Select Unit of Measure</option>
                                        <?php endif; ?>
                                        <?php foreach($ordered_uom_options as $uom_option): ?>
                                            <option value="<?php echo htmlspecialchars($uom_option['unmcde'], ENT_QUOTES); ?>" data-default-label="<?php echo htmlspecialchars($uom_option['unmdsc'], ENT_QUOTES); ?>" <?php echo ($default_uom_code !== '' && $uom_option['unmcde'] === $default_uom_code) ? 'selected' : ''; ?>><?php echo htmlspecialchars($uom_option['unmdsc'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Price per unit</label>
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
                                    <label for="">Search Purchase Orders</label>

                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button" onclick="search_po('getData_add','')">Search</button>
                                        </div>
                                        <input type="text" name="po_add" id="po_add" class="form-control" autocomplete="off" readonly>
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
                                    <label for="warehouse_staff_id_add">Warehouse Staff<span style="color:red">*</span></label>
                                    <select name="warehouse_staff_id_add" id="warehouse_staff_id_add" class="form-select">
                                        <option value="">Select Warehouse Staff</option>
                                        <?php foreach($warehouse_staff_options as $staff_option): ?>
                                            <option value="<?php echo htmlspecialchars($staff_option['warehouse_staff_id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($staff_option['staff_name'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" name="itmcde_add_hidden" id="itmcde_add_hidden">
                            <input type="hidden" name="po_add_hidden" id="po_add_hidden">
                            <div class="row m-2">
                                <div class="error_msg_add_modal"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>

                            <?php if(isset($_POST['po_qr_id_hidden']) && !empty($_POST['po_qr_id_hidden'])):?>
                                <button type="button" class="btn btn-primary" onclick="salesfile2('insert_qr')">Save</button>
                            <?php else:?>
                                <button type="button" class="btn btn-primary" onclick="salesfile2('insert')">Save</button>
                            <?php endif;?>
            
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="insert_modal_sales_po" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Match Purchase Order</h5>
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
                        <button type="button" class="btn btn-primary fw-bold" onclick="return_po()">Done</button>
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

            <div class="modal fade" id="edit_modal_sales" tabindex="-1" aria-hidden="true">
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

                                    <!-- <input type="text" name="itmcde_edit" id="itmcde_edit" class="form-control" autocomplete="off"> -->
                                    <div class="input-group mb-3">
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
                                    <label for="">Unit of Measure</label>
                                    <select name="unmcde_edit" id="unmcde_edit" class="form-select" disabled>
                                        <?php if($default_uom_code === ''): ?>
                                            <option value="">Select Unit of Measure</option>
                                        <?php endif; ?>
                                        <?php foreach($ordered_uom_options as $uom_option): ?>
                                            <option value="<?php echo htmlspecialchars($uom_option['unmcde'], ENT_QUOTES); ?>" data-default-label="<?php echo htmlspecialchars($uom_option['unmdsc'], ENT_QUOTES); ?>" <?php echo ($default_uom_code !== '' && $uom_option['unmcde'] === $default_uom_code) ? 'selected' : ''; ?>><?php echo htmlspecialchars($uom_option['unmdsc'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row m-3">
                                <div class="col-12">
                                    <label for="">Price per unit</label>
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
                                    <label for="">Search Purchase Orders</label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button" onclick="search_po_edit('getData_edit','')">Search</button>
                                        </div>
                                        <input type="text" name="po_edit" id="po_edit" class="form-control" autocomplete="off" readonly>
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
                                    <label for="warehouse_staff_id_edit">Warehouse Staff<span style="color:red">*</span></label>
                                    <select name="warehouse_staff_id_edit" id="warehouse_staff_id_edit" class="form-select">
                                        <option value="">Select Warehouse Staff</option>
                                        <?php foreach($warehouse_staff_options as $staff_option): ?>
                                            <option value="<?php echo htmlspecialchars($staff_option['warehouse_staff_id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($staff_option['staff_name'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <input type="hidden" name="po_edit_hidden" id="po_edit_hidden">
                            <input type="hidden" name="recid_tranfile2_hidden" id="recid_tranfile2_hidden">

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

                <input type="hidden" name="salesfile2_recid_hidden" id="salesfile2_recid_hidden">
            </div>

            <input type="hidden" name="xevent_itmsearch_hidden" id="xevent_itmsearch_hidden">
            <input type="hidden" name="recid_po_hidden" id="recid_po_hidden">

            
            <input type="hidden" name="untprc_hidden" id="untprc_hidden">
            <input type="hidden" name="base_item_price_add_hidden" id="base_item_price_add_hidden">
            <input type="hidden" name="base_item_price_edit_hidden" id="base_item_price_edit_hidden">

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
            <input type="hidden" name="po_qr_id_hidden" id="po_qr_id_hidden" value="<?php if(isset($_POST['po_qr_id_hidden'])){echo $_POST['po_qr_id_hidden'];}?>">

            <input type="hidden" name="qr_scan_counter2" id="qr_scan_counter2" value="<?php if(isset($_POST['qr_scan_counter'])){echo $_POST['qr_scan_counter'];}?>">
            <input type="hidden" name="qr_scan_counter" id="qr_scan_counter">
        </form>

        <script>

        var trncde = $("#trncde_hidden").val();
        var warehouseFloorMap = <?php echo json_encode($warehouse_floor_map); ?>;
        var defaultUomCode = <?php echo json_encode($default_uom_code); ?>;
        var baseUomDisplay = <?php echo json_encode($base_uom_display); ?>;

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

        function showPurchaseDetailModalError(mode, messages){
            var selector = mode === "edit" ? ".error_msg_edit_modal" : ".error_msg_add_modal";
            var messageHtml = Array.isArray(messages) ? messages.join("<br>") : (messages || "");

            $(selector).html(`
                <div class='alert alert-danger m-2' role='alert'>${messageHtml}</div>
            `);
        }

        function clearPurchaseDetailModalError(mode){
            var selector = mode === "edit" ? ".error_msg_edit_modal" : ".error_msg_add_modal";
            $(selector).html("");
        }

        function validatePurchaseLocationFields(mode){
            var suffix = mode === "edit" ? "_edit" : "_add";
            var warcde = ($("#warcde" + suffix).val() || "").trim();
            var warehouseFloorId = ($("#warehouse_floor_id" + suffix).val() || "").trim();
            var warehouseStaffId = ($("#warehouse_staff_id" + suffix).val() || "").trim();
            var allowEmptyLocation = mode === "edit" && $("#allow_empty_location_edit").val() === "1";
            var messages = [];
            var filledLocationCount = 0;

            if(warcde !== ""){
                filledLocationCount++;
            }
            if(warehouseFloorId !== ""){
                filledLocationCount++;
            }
            if(warehouseStaffId !== ""){
                filledLocationCount++;
            }

            if(allowEmptyLocation){
                if(filledLocationCount !== 0 && filledLocationCount !== 3){
                    messages.push("<b>Warehouse</b>, <b>Warehouse Floor</b>, and <b>Warehouse Staff</b> must all be filled or all be None");
                }
            }else{
                if(warcde === ""){
                    messages.push("<b>Warehouse</b> Cannot Be Empty");
                }
                if(warehouseFloorId === ""){
                    messages.push("<b>Warehouse Floor</b> Cannot Be Empty");
                }
                if(warehouseStaffId === ""){
                    messages.push("<b>Warehouse Staff</b> Cannot Be Empty");
                }
            }

            if(messages.length > 0){
                showPurchaseDetailModalError(mode, messages);
                return false;
            }

            clearPurchaseDetailModalError(mode);
            return true;
        }

        function formatUomConversionValue(conversion){
            var numericConversion = Number(conversion);

            if(!isFinite(numericConversion)){
                return "";
            }

            return numericConversion.toString();
        }

        function parseNumericInput(value){
            if(value === null || typeof value === "undefined"){
                return null;
            }

            if(typeof value === "number"){
                return isFinite(value) ? value : null;
            }

            var normalizedValue = value.toString().replaceAll(",", "").trim();
            if(normalizedValue === ""){
                return null;
            }

            var numericValue = Number(normalizedValue);
            return isFinite(numericValue) ? numericValue : null;
        }

        function formatPriceInputValue(value){
            var numericValue = parseNumericInput(value);

            if(numericValue === null){
                return "";
            }

            return numericValue.toFixed(2);
        }

        function buildItemUomMap(uoms){
            var uomMap = {};

            if(!uoms || !uoms.length){
                return uomMap;
            }

            for(var i = 0; i < uoms.length; i++){
                if(uoms[i] && uoms[i].unmcde){
                    uomMap[uoms[i].unmcde] = {
                        conversion: parseNumericInput(uoms[i].conversion),
                        unmdsc: uoms[i].unmdsc || uoms[i].unmcde
                    };
                }
            }

            return uomMap;
        }

        function resetUomOptionLabels(selectSelector){
            $(selectSelector).find("option").each(function(){
                var defaultLabel = $(this).data("default-label");
                if(typeof defaultLabel !== "undefined"){
                    $(this).text(defaultLabel);
                }
                $(this).show();
            });
        }

        function applyItemUomLabels(selectSelector, uoms, filterByItem){
            resetUomOptionLabels(selectSelector);

            var uomMap = {};
            if(uoms && uoms.length){
                for(var i = 0; i < uoms.length; i++){
                    if(uoms[i] && uoms[i].unmcde){
                        uomMap[uoms[i].unmcde] = uoms[i];
                    }
                }
            }

            $(selectSelector).find("option").each(function(){
                var optionValue = $(this).val();
                var defaultLabel = $(this).data("default-label");

                if(typeof defaultLabel === "undefined" || optionValue === ""){
                    return;
                }

                // If filtering by item, hide options not in uomMap (but always show pcs)
                if(filterByItem){
                    if(optionValue === defaultUomCode){
                        $(this).show();
                    } else if(!uomMap[optionValue]){
                        $(this).hide();
                        return;
                    } else {
                        $(this).show();
                    }
                }

                if(
                    uomMap[optionValue] &&
                    uomMap[optionValue].conversion !== null &&
                    uomMap[optionValue].conversion !== ""
                ){
                    var formattedConversion = formatUomConversionValue(uomMap[optionValue].conversion);
                    if(formattedConversion !== ""){
                        $(this).text(defaultLabel + " (" + formattedConversion + " " + baseUomDisplay + ")");
                    }
                }
            });
        }

        function updateItemUomDropdown(selectSelector, itmcde, selectedUomCode, onComplete, preserveSelectedUom){
            var $select = $(selectSelector);
            var strictEditSelection = preserveSelectedUom === true;
            var normalizedSelectedUomCode = $.trim(selectedUomCode || "");

            $select.data("current-itmcde", itmcde || "");
            $select.data("uom-map", {});
            resetUomOptionLabels(selectSelector);
            $select.find("option").show();

            if(typeof selectedUomCode !== "undefined"){
                $select.val(strictEditSelection ? normalizedSelectedUomCode : (normalizedSelectedUomCode || defaultUomCode || ""));
            }else{
                $select.val(defaultUomCode || "");
            }

            syncMatchedPurchaseOrderUomState(selectSelector === "#unmcde_edit" ? "edit" : "add");

            if(!itmcde){
                if(typeof onComplete === "function"){
                    onComplete();
                }
                return;
            }

            $.ajax({
                data: {
                    event_action: "get_item_uoms",
                    itmcde: itmcde
                },
                dataType: "json",
                type: "post",
                url: "trn_purchasefile2_ajax.php",
                success: function(xdata){
                    if($select.data("current-itmcde") !== itmcde){
                        return;
                    }

                    var uoms = xdata["uoms"] || [];
                    $select.data("uom-map", buildItemUomMap(uoms));
                    applyItemUomLabels(selectSelector, uoms, true);

                    var nextUomCode = "";
                    if(strictEditSelection){
                        $select.find("option").each(function(){
                            if($.trim($(this).val()) === normalizedSelectedUomCode){
                                nextUomCode = normalizedSelectedUomCode;
                                return false;
                            }
                        });
                    }else{
                        $select.find("option:visible").each(function(){
                            if($(this).val() !== ""){
                                nextUomCode = $(this).val();
                                return false;
                            }
                        });
                    }
                    var finalUomCode = strictEditSelection ? nextUomCode : (nextUomCode || defaultUomCode || "");
                    $select.find("option").prop("selected", false);
                    (strictEditSelection ? $select.find("option") : $select.find("option:visible")).each(function(){
                        if($.trim($(this).val()) === $.trim(finalUomCode)){
                            $(this).prop("selected", true);
                            return false;
                        }
                    });
                    if(finalUomCode !== ""){
                        $select.val(finalUomCode).trigger("change");
                    }
                    syncMatchedPurchaseOrderUomState(selectSelector === "#unmcde_edit" ? "edit" : "add");

                    if(typeof onComplete === "function"){
                        onComplete();
                    }
                }
            });
        }

        function setInitialUomDropdownState(selectSelector){
            var $select = $(selectSelector);

            $select.data("current-itmcde", "");
            $select.data("uom-map", {});
            resetUomOptionLabels(selectSelector);
            $select.find("option").show();
            $select.val(defaultUomCode || "");
            $select.prop("disabled", true);
        }

        function getSelectedUomConversion(selectSelector){
            var $select = $(selectSelector);
            var selectedUomCode = $select.val();
            var uomMap = $select.data("uom-map") || {};

            if(selectedUomCode === "" || selectedUomCode === defaultUomCode){
                return 1;
            }

            if(
                uomMap[selectedUomCode] &&
                uomMap[selectedUomCode].conversion !== null &&
                uomMap[selectedUomCode].conversion > 0
            ){
                return uomMap[selectedUomCode].conversion;
            }

            return 1;
        }

        function setBaseItemPrice(mode, basePrice){
            var hiddenSelector = mode === "edit" ? "#base_item_price_edit_hidden" : "#base_item_price_add_hidden";
            var numericBasePrice = parseNumericInput(basePrice);

            if(numericBasePrice === null){
                $(hiddenSelector).val("");
                return;
            }

            $(hiddenSelector).val(numericBasePrice);
        }

        function getBaseItemPrice(mode){
            var hiddenSelector = mode === "edit" ? "#base_item_price_edit_hidden" : "#base_item_price_add_hidden";
            return parseNumericInput($(hiddenSelector).val());
        }

        function applyConvertedItemPrice(mode, basePrice){
            var priceSelector = mode === "edit" ? "#price_edit" : "#price_add";
            var selectSelector = mode === "edit" ? "#unmcde_edit" : "#unmcde_add";
            var numericBasePrice = parseNumericInput(basePrice);

            setBaseItemPrice(mode, basePrice);

            if(numericBasePrice === null){
                $(priceSelector).val("");
                calcTotal(mode);
                return;
            }

            var conversion = getSelectedUomConversion(selectSelector);
            var convertedPrice = numericBasePrice * conversion;

            $(priceSelector).val(formatPriceInputValue(convertedPrice));
            calcTotal(mode);
        }

        function refreshItemPrice(mode, basePrice){
            var itmcdeSelector = mode === "edit" ? "#itmcde_edit_hidden" : "#itmcde_add_hidden";
            var itmcde = $(itmcdeSelector).val();

            if(!itmcde){
                return;
            }

            if(typeof basePrice !== "undefined"){
                applyConvertedItemPrice(mode, basePrice);
                return;
            }

            $.ajax({
                data: {
                    event_action: "change_itmprice",
                    xitmcde: itmcde
                },
                dataType: "json",
                type: "post",
                url: "trn_purchasefile2_ajax.php",
                success: function(xdata){
                    applyConvertedItemPrice(mode, xdata["retEdit"] ? xdata["retEdit"]["xprice"] : "");
                }
            });
        }

        $(document).ready(function(){
            var docnum = $("#docnum_hidden").val();
            xdata = "docnum="+docnum+"&trncde="+trncde;
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
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

            $("#unmcde_add").on("change", function(){
                refreshItemPrice("add");
            });

            $("#unmcde_edit").on("change", function(){
                refreshItemPrice("edit");
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

        function hasMatchedPurchaseOrder(mode){
            var matchDocSelector = mode === "edit" ? "#po_edit" : "#po_add";
            return $.trim($("#multi_itm_select").val() || "") !== "" ||
                $.trim($(matchDocSelector).val() || "") !== "";
        }

        function syncMatchedPurchaseOrderUomState(mode){
            var selector = mode === "edit" ? "#unmcde_edit" : "#unmcde_add";
            var itemSelector = mode === "edit" ? "#itmcde_edit_hidden" : "#itmcde_add_hidden";
            var hasItem = $.trim($(itemSelector).val() || "") !== "";

            $(selector).prop("disabled", !hasItem || hasMatchedPurchaseOrder(mode));
        }

        function select_item_modal(xitmcde, xitmdsc, xevent_action, xnew_itm, xuntprc){

            if(xevent_action == 'add'){

                $(".error_msg_add_modal").html("");
                //this updates the latest value based from the date
                //$("#price_add").val(xlatest_price);

                $("#itmcde_add_hidden").val(xitmcde);
                $("#itmcde_add").val(xitmdsc);
                updateItemUomDropdown("#unmcde_add", xitmcde, $("#unmcde_add").val() || defaultUomCode, function(){
                    refreshItemPrice("add", xuntprc);
                });

                $("#view_itm_search").modal("hide");
                $("#itmcde_add").prop("readonly", true);
                $("#insert_modal_sales").modal("show");
            }else if(xevent_action == 'edit'){

                $(".error_msg_edit_modal").html("");

                $("#itmcde_edit_hidden").val(xitmcde);
                $("#itmcde_edit").val(xitmdsc);
                updateItemUomDropdown("#unmcde_edit", xitmcde, $("#unmcde_edit").val() || defaultUomCode, function(){
                    refreshItemPrice("edit", xuntprc);
                });

                if(xitmdsc != xnew_itm){
                    
                    $("#po_edit").val('');
                    $("#multi_itm_select").val('');
                    //$("#multi_itm_select_original").val('');

                    $("#po_edit_hidden").val('');
                    //$("#itmcde_edit_hidden").val('');
                    $("#recid_po_hidden").val('');
                    syncMatchedPurchaseOrderUomState("edit");
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
                url:"trn_purchasefile2_ajax.php", 
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
                $(".back_btn_modal_footer").html(`<button type="button" class="btn btn-primary" onclick="back_from_view_func('add')">Back</button>`);

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
                url:"trn_purchasefile2_ajax.php", 
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
                url:"trn_purchasefile2_ajax.php", 
                success: function(xdata){  
                    //$("#price_edit").val(xdata['retEdit']['xprice']);
                    var xqty = $("#itmqty_edit").val();
                    var xtotal = xqty * xdata['retEdit']['xprice'];
                    $("#amount_edit").val(xtotal);
                }
            })  
        });
        
        
        function search_po(xevent_action,xdocnum,xrecid){

            $("#insert_modal_sales").modal("hide");
            var suppcde_val = $("#cusname_1").val();
            var trndte_1_val = $("#trndte_1").val();


            if(xrecid){
                $("#recid_po_hidden").val(xrecid);
            }

            var multi_itm_select = $("#multi_itm_select").val();
            var shouldRestoreAddUomForSearch = $("#unmcde_add").is(':disabled');

            if(shouldRestoreAddUomForSearch){
                $("#unmcde_add").prop('disabled', false);
            }

            if($('#itmcde_add').is(':disabled')){
                $("#itmcde_add").attr("disabled", false);
                var xdata = $("#insert_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&trndte_1="+encodeURIComponent(trndte_1_val);
                $("#itmcde_add").attr("disabled", true);
            }else{
                var xdata = $("#insert_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&trndte_1="+encodeURIComponent(trndte_1_val);
            }

            if(shouldRestoreAddUomForSearch){
                syncMatchedPurchaseOrderUomState("add");
            }

            xdata = xdata+"&multi_itm_select="+multi_itm_select;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_po.php", 

                    success: function(xdata){
                        
                        if(xevent_action == 'getData_add'){
                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-primary fw-bold" onclick="return_po()">Done</button>`
                            )
                        }else if(xevent_action == 'selectData_add'){
                            $("#po_add").val(xdocnum);
                            $("#insert_modal_sales_po").modal("hide");
                            $("#insert_modal_sales").modal("show");

                            //disable the inputs
                            $('#itmcde_add').attr("disabled", true); 
                            $("#itmqty_add").prop('readonly', true);
                            //$("#price_add").prop('readonly', true);
                            syncMatchedPurchaseOrderUomState("add");
                            $('.btn_search_item').addClass('disabled');
                        }

                    }
            })
            
        }

        function select_multi_itm(xevent_action,xdocnum,xrecid,itm_checkbox,xuntprc){

            if(xuntprc){

                if(xevent_action == "selectData_add"){
                    //$("#price_add").val(xuntprc);
                    var xqty = $("#itmqty_add").val();
                    var xtotal = xqty * xuntprc;
                    $("#price_add").val(xuntprc);
                    $("#amount_add").val(xtotal);

                }

                $("#untprc_hidden").val(xuntprc);

            }


            var suppcde_val = $("#cusname_1").val();
            var selected_po = $("#po_edit").val();
            var trndte_1_val = $("#trndte_1").val();

            if(xevent_action == "selectData_add"){
                var itmqty = $("#itmqty_add").val();
            }else{
                var itmqty = $("#itmqty_edit").val();
            }

            if(xrecid){
                var recid_po_hidden_val = $("#recid_po_hidden").val(xrecid);
            }

            if(itm_checkbox.checked){
                var xchecked = 'check';

            }else{
                var xchecked = 'uncheck';
            }

            var itmcde_add_hidden = $("#itmcde_add_hidden").val();
            var multi_itm_select = $("#multi_itm_select").val();

            // Serialize the correct modal based on event action
            var xdata;
            if(xevent_action == "selectData_add"){
                var shouldRestoreAddUomForMatch = $("#unmcde_add").is(':disabled');
                if(shouldRestoreAddUomForMatch){
                    $("#unmcde_add").prop('disabled', false);
                }
                if($('#itmcde_add').is(':disabled')){
                    $("#itmcde_add").attr("disabled", false);
                    xdata = $("#insert_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&recid_po_hidden="+xrecid+"&trndte_1="+encodeURIComponent(trndte_1_val);
                    $("#itmcde_add").attr("disabled", true);
                }else{
                    xdata = $("#insert_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&recid_po_hidden="+xrecid+"&trndte_1="+encodeURIComponent(trndte_1_val);
                }
                if(shouldRestoreAddUomForMatch){
                    syncMatchedPurchaseOrderUomState("add");
                }
            }else{
                var shouldRestoreEditUomForMatch = $("#unmcde_edit").is(':disabled');
                if(shouldRestoreEditUomForMatch){
                    $("#unmcde_edit").prop('disabled', false);
                }
                if($('#itmcde_edit').is(':disabled')){
                    $("#itmcde_edit").attr("disabled", false);
                    xdata = $("#edit_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&recid_po_hidden="+xrecid+"&trndte_1="+encodeURIComponent(trndte_1_val);
                    $("#itmcde_edit").attr("disabled", true);
                }else{
                    xdata = $("#edit_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&recid_po_hidden="+xrecid+"&trndte_1="+encodeURIComponent(trndte_1_val);
                }
                if(shouldRestoreEditUomForMatch){
                    syncMatchedPurchaseOrderUomState("edit");
                }
            }
            xdata = xdata+"&xcheck_action="+xchecked+"&itmcde_add_hidden="+itmcde_add_hidden+"&multi_itm_select="+multi_itm_select+"&itmqty="+itmqty;            

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_po.php", 
                    success: function(xdata){

                        if(xevent_action == 'getData_edit'){

                            var xmulti_docnum_add = $("#po_edit").val();

                            // $("#po_edit").val(xmulti_docnum_add + ","+ xdocnum);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-danger fw-bold" onclick="return_po_edit()">Back</button>`
                            );

                        }else if(xevent_action == 'selectData_edit' || xevent_action == 'deSelectData_edit'){
                            var xmulti_docnum_add = $("#po_edit").val();
                            var xmulti_itm_select = $("#multi_itm_select").val();

                            if(itm_checkbox.checked){

                                if(xmulti_itm_select == ""){
                                    $("#multi_itm_select").val(xrecid);
                                }else{
                                    $("#multi_itm_select").val(xmulti_itm_select + ","+ xrecid);
                                }

                                if(xmulti_docnum_add == ""){
                                    $("#po_edit").val(xdocnum);  
                                }else{
                                    $("#po_edit").val(xmulti_docnum_add + ", "+ xdocnum);  
                                }
                            }else{

                                if(xmulti_docnum_add.includes(',')){
                                    let valuesArray = xmulti_docnum_add.split(',').map(item => item.trim());
                                    let indexToRemove = valuesArray.indexOf(xdocnum);
                                    if (indexToRemove !== -1) {
                                        valuesArray.splice(indexToRemove, 1);
                                    }

                                    // Join the array back into a string
                                    xmulti_docnum_add = valuesArray.join(', ');
                                    $("#po_edit").val(xmulti_docnum_add);
                                }else{
                                    $("#po_edit").val('');
                                }

                                if(xmulti_itm_select.includes(',')){
                                    let valuesArray_recid = xmulti_itm_select.split(',');
                                    valuesArray_recid = valuesArray_recid.filter(value => value !== xrecid);
                                    // Join the array back into a string
                                    xmulti_recid_add = valuesArray_recid.join(',');

                                    $("#multi_itm_select").val(xmulti_recid_add);
                                }else{
                                    $("#multi_itm_select").val('');
                                }
                          
                            }

                            syncMatchedPurchaseOrderUomState("edit");
                        }else if(xevent_action == 'selectData_add'){

                            var xmulti_docnum_add = $("#po_add").val();
                            var xmulti_itm_select = $("#multi_itm_select").val();
                            var xpo_add_hidden = $("#po_add_hidden").val();

                            if(itm_checkbox.checked){

                                if(xmulti_itm_select == ""){
                                    $("#multi_itm_select").val(xrecid);
                                }else{
                                    $("#multi_itm_select").val(xmulti_itm_select + ","+ xrecid);
                                }

                                // Also update po_add_hidden to track selected PO recids
                                if(xpo_add_hidden == ""){
                                    $("#po_add_hidden").val(xrecid);
                                }else{
                                    $("#po_add_hidden").val(xpo_add_hidden + ","+ xrecid);
                                }

                                if(xmulti_docnum_add == ""){
                                    $("#po_add").val(xdocnum);
                                }else{
                                    $("#po_add").val(xmulti_docnum_add + ", "+ xdocnum);
                                }
                            }else{

                                if(xmulti_docnum_add.includes(',')){
                                    let valuesArray = xmulti_docnum_add.split(',').map(item => item.trim());
                                    let indexToRemove = valuesArray.indexOf(xdocnum);
                                    if (indexToRemove !== -1) {
                                        valuesArray.splice(indexToRemove, 1);
                                    }

                                    // Join the array back into a string
                                    xmulti_docnum_add = valuesArray.join(', ');
                                    $("#po_add").val(xmulti_docnum_add);
                                }else{
                                    $("#po_add").val('');
                                }

                                if(xmulti_itm_select.includes(',')){
                                    let valuesArray_recid = xmulti_itm_select.split(',');
                                    valuesArray_recid = valuesArray_recid.filter(value => value !== xrecid);
                                    // Join the array back into a string
                                    xmulti_recid_add = valuesArray_recid.join(',');

                                    $("#multi_itm_select").val(xmulti_recid_add);
                                }else{
                                    $("#multi_itm_select").val('');
                                }

                                // Also update po_add_hidden when unchecking
                                if(xpo_add_hidden.includes(',')){
                                    let valuesArray_hidden = xpo_add_hidden.split(',');
                                    valuesArray_hidden = valuesArray_hidden.filter(value => value !== xrecid);
                                    $("#po_add_hidden").val(valuesArray_hidden.join(','));
                                }else{
                                    $("#po_add_hidden").val('');
                                }

                            }

                            syncMatchedPurchaseOrderUomState("add");

                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);


                        }

                    }
            })            

        }

        function search_po_edit(xevent_action,xdocnum,xrecid,itm_checkbox){

            $("#edit_modal_sales").modal("hide");

            var suppcde_val = $("#cusname_1").val();
            var selected_po = $("#po_edit").val();
            var recid_tranfile2_hidden = $("#recid_tranfile2_hidden").val();
            var trndte_1_val = $("#trndte_1").val();

            //var selected_po_hidden = $("#po_edit_hidden").val();

            if(xrecid){
                var recid_po_hidden_val = $("#recid_po_hidden").val(xrecid);
            }else{
                var recid_po_hidden_val = $("#recid_po_hidden").val();
            }

            if(xevent_action == "selectData_add"){
                var itmqty = $("#itmqty_add").val();
            }else{
                var itmqty = $("#itmqty_edit").val();
            }

            var shouldRestoreEditUomForSearch = $("#unmcde_edit").is(':disabled');
            if(shouldRestoreEditUomForSearch){
                $("#unmcde_edit").prop('disabled', false);
            }

            if($('#itmcde_edit').is(':disabled')){
                //enable the itcde para mapass yung data
                $("#itmcde_edit").attr("disabled", false);
                var xdata = $("#edit_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_po_hidden="+recid_po_hidden_val+"&trndte_1="+encodeURIComponent(trndte_1_val);
                //disable na again since the data was passed
                $("#itmcde_edit").attr("disabled", true);
            }else{
                var xdata = $("#edit_modal_sales *").serialize("")+"&suppcde="+suppcde_val+"&event_action="+xevent_action+"&selected_po="+selected_po+"&tranfile2_recid_hidden="+recid_tranfile2_hidden+"&recid_po_hidden="+recid_po_hidden_val+"&trndte_1="+encodeURIComponent(trndte_1_val);
            }

            if(shouldRestoreEditUomForSearch){
                syncMatchedPurchaseOrderUomState("edit");
            }

            // alert(itmcde_add_hidden);
            var itmcde_add_hidden = $("#itmcde_add_hidden").val();
            var multi_itm_select = $("#multi_itm_select").val();

            var xmulti_select_original =  $("#multi_itm_select_original").val();

            xdata = xdata+"&itmcde_add_hidden="+itmcde_add_hidden+"&multi_itm_select="+multi_itm_select+"&itmqty="+itmqty+"&multi_select_original="+xmulti_select_original;
            
            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax_po.php", 
                    success: function(xdata){

                        
                        if(xevent_action == 'getData_edit'){

                            var xmulti_docnum_add = $("#po_edit").val();
                            
                            //$("#po_edit").val(xmulti_docnum_add + ","+ xdocnum);

                            $("#tbody_selectpo").html(xdata["html"]);
                            $("#tbody_selectpo_mobile").html(xdata["html_mobile"]);
                            $("#insert_modal_sales_po").modal("show");
                            $("#modal_footer_po").html(
                                ` <button type="button" class="btn btn-danger fw-bold" onclick="return_po_edit()">Back</button>`
                            );

                        }else if(xevent_action == 'selectData_edit' || xevent_action == 'deSelectData_edit'){
                       
                            if(xevent_action == 'selectData_edit'){
                                $("#po_edit").val(xdocnum);
                                $("#recid_po_hidden").val(xrecid);
                            }else{
                                $("#po_edit").val('');
                                $("#recid_po_hidden").val('');
                            }
                            
                            $("#insert_modal_sales_po").modal("hide");
                            $("#edit_modal_sales").modal("show");

                            $('#itmcde_edit').attr("disabled", true); 
                            $("#itmqty_edit").prop('readonly', true);
                            //$("#price_edit").prop('readonly', true);
                            syncMatchedPurchaseOrderUomState("edit");

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

            if(event == "add"){;
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

        // $('#itmcde_add').on('change', function() {

        //     xdata = "event_action=change_itmprice&xitmcde="+this.value;

        //     jQuery.ajax({    
        //         data:xdata,
        //         dataType:"json",
        //         type:"post",
        //         url:"trn_purchasefile2_ajax.php", 
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
        //         url:"trn_purchasefile2_ajax.php", 
        //         success: function(xdata){  
        //             $("#price_edit").val(xdata['retEdit']['xprice']);
        //             var xqty = $("#itmqty_edit").val();
        //             var xtotal = xqty * xdata['retEdit']['xprice'];
        //             $("#amount_edit").val(xtotal);
        //         }
        //     })  
        // });           

        function salesfile2(event,xrecid){

            var docnum = $("#docnum_hidden").val();
            back_check = true;

            switch(event){
                case "save_exit":
                case "save_qr_exit":
                    var po_qr_id_hidden = $("#po_qr_id_hidden").val()
                    var xdata = $(".salesfile1 *").serialize()+"&event_action="+event+"&docnum="+docnum+"&po_qr_id_hidden="+po_qr_id_hidden;
                    break;
                case "save_new":
                    var po_qr_id_hidden = $("#po_qr_id_hidden").val()
                    var xdata = $(".salesfile1 *").serialize()+"&event_action=save_new&docnum="+docnum+"&po_qr_id_hidden="+po_qr_id_hidden;
                    break;
                case "open_add":

                    //disable the inputs
                    $("#itmcde_add").prop("readonly", false);
                    $('#itmcde_add').attr('disabled', false);
                    $("#itmqty_add").prop('readonly', false);
                    $("#price_add").prop('readonly', false);
                    $('.btn_search_item').removeClass('disabled');
                    $("#untprc_hidden").val('');
                    $("#base_item_price_add_hidden").val('');
                    $("#price_add").val('');
                    $("#amount_add").val('');
                    $("#itmqty_add").val('');
                    setInitialUomDropdownState("#unmcde_add");
                    $("#itmcde_add").val('');
                    $("#po_add").val('');
                    $("#po_add_hidden").val('');
                    $("#multi_itm_select").val('');
                    $("#recid_po_hidden").val('');
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
                    //     url:"trn_purchasefile2_ajax.php", 
                    //     success: function(xdata){  
                    //         $("#price_add").val(xdata['retEdit']['xprice']);

                    //     }
                    // })                      
                    
                    return;
                break;
                case "insert":
                case "insert_qr":
                    if(!validatePurchaseLocationFields("add")){
                        return;
                    }

                    //remove the disabled muna para mabasa ng serialize
                    $("#itmcde_add").attr("disabled", false);
                    var shouldRelockAddUomForSubmit = $("#unmcde_add").is(':disabled');
                    if(shouldRelockAddUomForSubmit){
                        $("#unmcde_add").prop('disabled', false);
                    }
                    var xdata  = $("#insert_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&"+$(".salesfile1 *").serialize();
                    $('#itmcde_add').attr("disabled", true);
                    if(shouldRelockAddUomForSubmit){
                        syncMatchedPurchaseOrderUomState("add");
                    }
                    var po_qr_id_hidden = $("#po_qr_id_hidden").val();
                    var xmulti_itm_select = $("#multi_itm_select").val();
                    // Fallback to po_add_hidden if multi_itm_select is empty
                    if(!xmulti_itm_select || xmulti_itm_select === ""){
                        xmulti_itm_select = $("#po_add_hidden").val() || "";
                    }
                    xdata = xdata + "&multi_itm_select=" + encodeURIComponent(xmulti_itm_select) + "&po_qr_id_hidden=" + encodeURIComponent(po_qr_id_hidden || "");
                    xdata = xdata + "&warcde_add=" + encodeURIComponent($("#warcde_add").val() || "");
                    xdata = xdata + "&warehouse_floor_id_add=" + encodeURIComponent($("#warehouse_floor_id_add").val() || "");
                    xdata = xdata + "&warehouse_staff_id_add=" + encodeURIComponent($("#warehouse_staff_id_add").val() || "");
                 
                break;
                case "submitEdit":
                    if(!validatePurchaseLocationFields("edit")){
                        return;
                    }

                    var recid = $("#salesfile2_recid_hidden").val();
                    var trndte_1_val = $("#trndte_1").val();

                    var xmulti_itm_select = $("#multi_itm_select").val();
                    var xmulti_itm_select_original = $("#multi_itm_select_original").val();
                    var shouldRelockEditUomForSubmit = $("#unmcde_edit").is(':disabled');
                    if(shouldRelockEditUomForSubmit){
                        $("#unmcde_edit").prop('disabled', false);
                    }
                    var xdata  = $("#edit_modal_sales *").serialize()+"&event_action="+event+"&docnum="+docnum+"&recid="+recid+"&xtrndte_1="+trndte_1_val;
                    if(shouldRelockEditUomForSubmit){
                        syncMatchedPurchaseOrderUomState("edit");
                    }
                    xdata = xdata+"&multi_itm_select="+xmulti_itm_select+"&multi_itm_select_original="+xmulti_itm_select_original;
                    xdata = xdata + "&warcde_edit=" + encodeURIComponent($("#warcde_edit").val() || "");
                    xdata = xdata + "&warehouse_floor_id_edit=" + encodeURIComponent($("#warehouse_floor_id_edit").val() || "");
                    xdata = xdata + "&warehouse_staff_id_edit=" + encodeURIComponent($("#warehouse_staff_id_edit").val() || "");

                break;
                case "getEdit": 

                    //disable the inputs
                    $('.btn_search_item').removeClass('disabled');
                    $("#multi_itm_select_original").val('');
                    $("#itmqty_edit").prop('readonly', false);
                    $("#price_edit").prop('readonly', false);
                    $("#untprc_hidden").val('');
                    $("#base_item_price_edit_hidden").val('');
                    $("#recid_tranfile2_hidden").val(xrecid);

                    var xdata = "event_action=getEdit&recid="+xrecid+"&docnum="+docnum;
                    break;
                case "delete":

                    let userInput = confirm("Are you sure you want to delete?");

                    //cancelled delete
                    if (!userInput) {
                        return
                    }   
                                        
                    var xdata = "event_action=delete&recid="+xrecid+"&docnum="+docnum;
                    break;
            }

            var xrecid_po_hidden_val = $("#recid_po_hidden").val();

            var xdata = xdata+"&trncde="+trncde+"&xrecid_po_hidden="+xrecid_po_hidden_val;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"trn_purchasefile2_ajax.php", 
                    success: function(xdata){  

                        if(xdata["status"] == 0){
                            if(event == "insert" || event == "insert_qr"){
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
                            //$("#purchase_type_1").val($("#purchase_type_1 option:first").val());
                            $("#orderby_1").val(xdata['purchasesDefault']['orderby_default']);
                            $("#shipto_1").val(xdata['purchasesDefault']['shipto_default']);
                            $("#paydate_1").val('');
                            $("#payment_details_1").val('');
                            $("#remarks_1").val('');
                            $("#ordernum_1").val('');
                            $("#po_order_id").val('');

                            if(xdata["msg"] == "save_new_same"){
                                $("#crud_msg_h").val("same_page");
                            }else{
                                $("#crud_msg_h").val("save_exit");
                            }
                        }


                        if(event == "save_qr_exit"){

                            var qr_scan_counter2 = parseInt($("#qr_scan_counter2").val(), 10); // Convert to integer
                            var new_qr_counter = qr_scan_counter2 + 1;

                            //alert(new_qr_counter);

                            $("#qr_scan_counter").val(new_qr_counter);

                            document.forms.myforms.target = "_self";
                            document.forms.myforms.method = "post";
                            document.forms.myforms.action = "qr_scanner_pur.php";
                            document.forms.myforms.submit();
                            return;
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
                            $("#itmcde_edit_hidden").val(xdata["retEdit"]["itmcde"]);
                            $("#itmcde_edit").val(xdata["retEdit"]["itmdsc"]);

                            //set multi item select to empty
                            $("#multi_itm_select").val('');
                            $("#multi_itm_select").val(xdata["retEdit"]["matched_po_recid_hidden"]);

                            var xmulti_itm_select_original = $("#multi_itm_select_original").val();
                            if(xmulti_itm_select_original == '' || !xmulti_itm_select_original || xmulti_itm_select_original == null){
     
                                $("#multi_itm_select_original").val(xdata["retEdit"]["matched_po_recid_hidden"]);
                            }

                            $("#price_edit").val(xdata["retEdit"]["untprc"]);
                            $("#amount_edit").val(xdata["retEdit"]["extprc"]);
                            $("#itmqty_edit").val(xdata["retEdit"]["itmqty"]);
                            updateItemUomDropdown("#unmcde_edit", xdata["retEdit"]["itmcde"], xdata["retEdit"]["unmcde"], undefined, true);
                            $("#allow_empty_location_edit").val(xdata["retEdit"]["allow_empty_location"] || "0");
                            var allowEmptyLocation = (xdata["retEdit"]["allow_empty_location"] || "0") === "1";
                            setEditNoneOption("warcde_edit", "Select Warehouse", xdata["retEdit"]["warcde"] || "", allowEmptyLocation);
                            rebuildFloorOptions("warehouse_floor_id_edit", xdata["retEdit"]["warcde"] || "", xdata["retEdit"]["warehouse_floor_id"] || "", allowEmptyLocation);
                            $("#warehouse_staff_id_edit").val(xdata["retEdit"]["warehouse_staff_id"] || "");

                            $("#po_edit").val(xdata["retEdit"]["matched_po"]);
                            $("#po_edit_hidden").val(xdata["retEdit"]["matched_po"]);

                            //recid getting
                            $("#recid_po_hidden").val(xdata["retEdit"]["purnum_recid"]);
                            $(".error_msg_edit_modal").html('');

                            // $("#itmcde_edit option[text=" + xdata["retEdit"]["itmdsc"] +"]").prop("selected", true);
                            // $("#itmcde_edit option:contains("+xdata["retEdit"]["itmdsc"]+")").prop("selected", true);
                            // alert('dsd');
                            // $('#itmcde_edit').val(xdata["retEdit"]["itmdsc"]).prop("selected", true);

                                    //$('#itmcde_edit').text(xdata["retEdit"]["itmdsc"]).prop("selected", true);

                            // $("#itmcde_edit").filter(function() {
                            //     return $(this).text() === xdata["retEdit"]["itmdsc"];
                            // }).prop("selected", true);

                            // var optionsThatContainValue = $("#itmcde_edit").find('option').filter(function() {
                            //     return $(this).text().trim() === xdata["retEdit"]["itmdsc"];
                            // });

                            // optionsThatContainValue.prop("selected", true);

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
