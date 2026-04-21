<?php
if(isset($_POST['warehouse_staff_validate_action']) && $_POST['warehouse_staff_validate_action'] === '1'){
    session_start();
    header('Content-Type: application/json');

    $warehouse_staff_id = isset($_POST['warehouse_staff_id']) ? trim((string)$_POST['warehouse_staff_id']) : '';
    if($warehouse_staff_id === ''){
        echo json_encode(array(
            'status' => 0,
            'msg' => 'Warehouse staff is required'
        ));
        exit;
    }

    echo json_encode(array(
        'status' => 1,
        'msg' => ''
    ));
    exit;
}

$_POST['trncde_hidden'] = 'ADJ';

ob_start();
require "trn_invadjfile2_shared.php";
$page_output = ob_get_clean();

$validation_script = <<<'HTML'
<script>
(function(){
    function showWarehouseStaffRequiredModal(){
        $("#alert_modal_body_system").html("Warehouse staff is required");
        $("#alert_modal_system").modal("show");
    }

    function getWarehouseStaffFieldValue(event){
        if(event === "insert"){
            return $.trim($("#warehouse_staff_id_add").val() || "");
        }

        if(event === "submitEdit"){
            return $.trim($("#warehouse_staff_id_edit").val() || "");
        }

        return "";
    }

    function validateWarehouseStaffAndContinue(originalSalesfile2, event, xrecid){
        var warehouseStaffId = getWarehouseStaffFieldValue(event);
        if(warehouseStaffId === ""){
            showWarehouseStaffRequiredModal();
            return false;
        }

        $.ajax({
            data: {
                warehouse_staff_validate_action: "1",
                warehouse_staff_id: warehouseStaffId
            },
            dataType: "json",
            type: "post",
            url: "trn_invadjfile2.php",
            success: function(xdata){
                if(!xdata || String(xdata.status) !== "1"){
                    showWarehouseStaffRequiredModal();
                    return;
                }

                originalSalesfile2(event, xrecid);
            },
            error: function(){
                showWarehouseStaffRequiredModal();
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
