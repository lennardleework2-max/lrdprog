<?php
if(isset($_POST['warehouse_staff_validate_action']) && $_POST['warehouse_staff_validate_action'] === '1'){
    session_start();
    header('Content-Type: application/json');

    $xret = array(
        'status' => 1,
        'msg' => '',
        'error1' => 0,
        'error2' => 0,
        'error3' => 0,
        'error4' => 0,
        'error5' => 0,
        'error6' => 0,
        'error7' => 0
    );

    $validation_mode = isset($_POST['validation_mode']) ? trim((string)$_POST['validation_mode']) : '';

    $append_error = function($message, $error_key) use (&$xret){
        if($xret['msg'] !== ''){
            $xret['msg'] .= "</br>";
        }

        $xret['msg'] .= $message;
        $xret['status'] = 0;
        if($error_key !== ''){
            $xret[$error_key] = 1;
        }
    };

    $is_invalid_uom = function($uom_value){
        $uom_value = trim((string)$uom_value);
        return $uom_value === '' || strtolower($uom_value) === 'none';
    };

    if($validation_mode === 'add'){
        $trndte = isset($_POST['trndte_1']) ? trim((string)$_POST['trndte_1']) : '';
        $itmcde_hidden = isset($_POST['itmcde_add_hidden']) ? trim((string)$_POST['itmcde_add_hidden']) : '';
        $itmcde_text = isset($_POST['itmcde_add']) ? trim((string)$_POST['itmcde_add']) : '';
        $itmqty = isset($_POST['itmqty_add']) ? trim((string)$_POST['itmqty_add']) : '';
        $unmcde = isset($_POST['unmcde_add']) ? trim((string)$_POST['unmcde_add']) : '';
        $warcde = isset($_POST['warcde_add']) ? trim((string)$_POST['warcde_add']) : '';
        $warehouse_floor_id = isset($_POST['warehouse_floor_id_add']) ? trim((string)$_POST['warehouse_floor_id_add']) : '';
        $warehouse_staff_id = isset($_POST['warehouse_staff_id_add']) ? trim((string)$_POST['warehouse_staff_id_add']) : '';

        if($trndte === ''){
            $append_error("<b>Tran. Date</b> cannot be empty.", 'error1');
        }

        if($itmcde_hidden === ''){
            if($itmcde_text === ''){
                $append_error("<b>Item</b> Cannot Be Empty", 'error2');
            }else{
                $append_error("Invalid <b>Item</b>", 'error2');
            }
        }

        if($itmqty === '' || $itmqty === '0'){
            $append_error("<b>Quantity</b> Cannot Be Empty or 0", 'error3');
        }

        if($is_invalid_uom($unmcde)){
            $append_error("<b>Unit of Measure</b> must be selected and cannot be None", 'error6');
        }

        if($warcde === ''){
            $append_error("<b>Warehouse</b> Cannot Be Empty", 'error4');
        }

        if($warehouse_floor_id === ''){
            $append_error("<b>Warehouse Floor</b> Cannot Be Empty", 'error5');
        }

        if($warehouse_staff_id === ''){
            $append_error("<b>Warehouse Staff</b> Cannot Be Empty", 'error7');
        }
    }else if($validation_mode === 'edit'){
        $trndte = isset($_POST['xtrndte_1']) ? trim((string)$_POST['xtrndte_1']) : '';
        $itmcde_hidden = isset($_POST['itmcde_edit_hidden']) ? trim((string)$_POST['itmcde_edit_hidden']) : '';
        $itmcde_text = isset($_POST['itmcde_edit']) ? trim((string)$_POST['itmcde_edit']) : '';
        $itmqty = isset($_POST['itmqty_edit']) ? trim((string)$_POST['itmqty_edit']) : '';
        $unmcde = isset($_POST['unmcde_edit']) ? trim((string)$_POST['unmcde_edit']) : '';
        $warcde = isset($_POST['warcde_edit']) ? trim((string)$_POST['warcde_edit']) : '';
        $warehouse_floor_id = isset($_POST['warehouse_floor_id_edit']) ? trim((string)$_POST['warehouse_floor_id_edit']) : '';
        $warehouse_staff_id = isset($_POST['warehouse_staff_id_edit']) ? trim((string)$_POST['warehouse_staff_id_edit']) : '';
        $allow_empty_location = isset($_POST['allow_empty_location_edit']) && trim((string)$_POST['allow_empty_location_edit']) === '1';

        if($trndte === ''){
            $append_error("<b>Tran. Date</b> cannot be empty.", 'error1');
        }

        if($itmcde_hidden === ''){
            if($itmcde_text === ''){
                $append_error("<b>Item</b> Cannot Be Empty", 'error2');
            }else{
                $append_error("Invalid <b>Item</b>", 'error2');
            }
        }

        if($itmqty === '' || $itmqty === '0'){
            $append_error("<b>Quantity</b> Cannot Be Empty or 0", 'error3');
        }

        if($is_invalid_uom($unmcde)){
            $append_error("<b>Unit of Measure</b> must be selected and cannot be None", 'error6');
        }

        if($allow_empty_location){
            $filled_location_count = 0;
            if($warcde !== ''){
                $filled_location_count++;
            }
            if($warehouse_floor_id !== ''){
                $filled_location_count++;
            }

            if($filled_location_count !== 0 && $filled_location_count !== 2){
                $append_error("<b>Warehouse</b> and <b>Warehouse Floor</b> must both be filled or both be None", 'error4');
            }
        }else{
            if($warcde === ''){
                $append_error("<b>Warehouse</b> Cannot Be Empty", 'error4');
            }

            if($warehouse_floor_id === ''){
                $append_error("<b>Warehouse Floor</b> Cannot Be Empty", 'error5');
            }
        }

        if($warehouse_staff_id === ''){
            $append_error("<b>Warehouse Staff</b> Cannot Be Empty", 'error7');
        }
    }

    echo json_encode($xret);
    exit;
}

$_POST['trncde_hidden'] = 'ADJ';

ob_start();
require "trn_invadjfile2_shared.php";
$page_output = ob_get_clean();

$validation_script = <<<'HTML'
<script>
(function(){
    function getErrorContainer(event){
        if(event === "insert"){
            return $(".error_msg_add_modal");
        }

        if(event === "submitEdit"){
            return $(".error_msg_edit_modal");
        }

        return $();
    }

    function clearValidationContainer(event){
        var $container = getErrorContainer(event);
        if(!$container.length){
            return;
        }

        $container.html("");
    }

    function showValidationMessage(event, message){
        var $container = getErrorContainer(event);
        if(!$container.length){
            return;
        }

        $container.html("<div class='alert alert-danger m-2' role='alert'>" + message + "</div>");
    }

    function buildValidationRequest(event){
        if(event === "insert"){
            return {
                warehouse_staff_validate_action: "1",
                validation_mode: "add",
                trndte_1: $("#trndte_1").val(),
                itmcde_add_hidden: $("#itmcde_add_hidden").val(),
                itmcde_add: $("#itmcde_add").val(),
                itmqty_add: $("#itmqty_add").val(),
                unmcde_add: $("#unmcde_add").val(),
                warcde_add: $("#warcde_add").val(),
                warehouse_floor_id_add: $("#warehouse_floor_id_add").val(),
                warehouse_staff_id_add: $("#warehouse_staff_id_add").val()
            };
        }

        if(event === "submitEdit"){
            return {
                warehouse_staff_validate_action: "1",
                validation_mode: "edit",
                xtrndte_1: $("#trndte_1").val(),
                itmcde_edit_hidden: $("#itmcde_edit_hidden").val(),
                itmcde_edit: $("#itmcde_edit").val(),
                itmqty_edit: $("#itmqty_edit").val(),
                unmcde_edit: $("#unmcde_edit").val(),
                warcde_edit: $("#warcde_edit").val(),
                warehouse_floor_id_edit: $("#warehouse_floor_id_edit").val(),
                warehouse_staff_id_edit: $("#warehouse_staff_id_edit").val(),
                allow_empty_location_edit: $("#allow_empty_location_edit").val()
            };
        }

        return {};
    }

    function validateWarehouseStaffAndContinue(originalSalesfile2, event, xrecid){
        var requestData = buildValidationRequest(event);
        clearValidationContainer(event);

        $.ajax({
            data: requestData,
            dataType: "json",
            type: "post",
            url: "trn_invadjfile2.php",
            success: function(xdata){
                if(!xdata || String(xdata.status) !== "1"){
                    showValidationMessage(event, (xdata && xdata.msg) ? xdata.msg : "<b>Warehouse Staff</b> Cannot Be Empty");
                    return;
                }

                clearValidationContainer(event);
                originalSalesfile2(event, xrecid);
            },
            error: function(){
                showValidationMessage(event, "<b>Warehouse Staff</b> Cannot Be Empty");
            }
        });

        return false;
    }

    $(function(){
        if(typeof window.salesfile2 !== "function"){
            return;
        }

        var originalSalesfile2 = window.salesfile2;
        window.salesfile2 = function(event, xrecid){
            if(event === "insert" || event === "submitEdit"){
                return validateWarehouseStaffAndContinue(originalSalesfile2, event, xrecid);
            }

            return originalSalesfile2(event, xrecid);
        };
    });
})();
</script>
HTML;

if(stripos($page_output, '</body>') !== false){
    $page_output = str_ireplace('</body>', $validation_script . "\n</body>", $page_output);
}else{
    $page_output .= "\n" . $validation_script;
}

echo $page_output;
?>
