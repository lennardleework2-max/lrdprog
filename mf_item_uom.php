<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";

$itmcde = '';
if(isset($_POST['itmcde'])){
    $itmcde = trim($_POST['itmcde']);
    $_SESSION['item_uom_context_id'] = $itmcde;
}else if(isset($_SESSION['item_uom_context_id'])){
    $itmcde = trim($_SESSION['item_uom_context_id']);
}
$itmdsc = '';

if($itmcde !== ''){
    $select_db_itm = "SELECT itmdsc FROM itemfile WHERE itmcde=?";
    $stmt_itm = $link->prepare($select_db_itm);
    $stmt_itm->execute(array($itmcde));
    $rs_itm = $stmt_itm->fetch();
    if(!empty($rs_itm)){
        $itmdsc = $rs_itm["itmdsc"];
    }
}

$has_valid_item = ($itmcde !== '' && $itmdsc !== '');
$item_uom_in_use_recids = array();

if($has_valid_item){
    $select_db_item_uom_in_use = "SELECT DISTINCT iuf.recid
                                  FROM itemunitfile iuf
                                  WHERE iuf.itmcde = ?
                                    AND (
                                        EXISTS (
                                            SELECT 1
                                            FROM tranfile2 tf
                                            WHERE tf.itmcde = iuf.itmcde
                                              AND tf.unmcde = iuf.unmcde
                                        )
                                        OR EXISTS (
                                            SELECT 1
                                            FROM purchasesorderfile2 pof2
                                            WHERE pof2.itmcde = iuf.itmcde
                                              AND pof2.unmcde = iuf.unmcde
                                        )
                                        OR EXISTS (
                                            SELECT 1
                                            FROM salesorderfile2 sof2
                                            WHERE sof2.itmcde = iuf.itmcde
                                              AND sof2.unmcde = iuf.unmcde
                                        )
                                    )";
    $stmt_item_uom_in_use = $link->prepare($select_db_item_uom_in_use);
    $stmt_item_uom_in_use->execute(array($itmcde));

    while($row_item_uom_in_use = $stmt_item_uom_in_use->fetch(PDO::FETCH_ASSOC)){
        $item_uom_in_use_recids[] = (string)$row_item_uom_in_use["recid"];
    }
}
?>

    <style>

        .data_table{
            border-collapse:collapse;
            width:100%;
        }

        .data_table tbody tr:nth-child(even){
            background-color:#f5f5f5;
        }

    </style>

    <form name='myforms' id="myforms" method="post" target="_self">

        <table class='big_table'>

            <tr colspan=1>

                <td colspan=1 class='td_bl'>

                    <?php
                        include 'includes/main_menu.php';
                    ?>
                </td>

                <td colspan=1 class="td_br" id="td_br">

                    <div class="container-fluid pt-2 main_br_div">

                        <div class="mb-2">
                            <a href="mf_itemfile.php" class="btn btn-sm btn-outline-primary">Back to Items</a>
                        </div>

                        <?php if(!$has_valid_item): ?>
                            <div class="alert alert-warning">Please choose an item first, then click <b>Unit of Measure</b> from its Action menu.</div>
                        <?php else: ?>

                        <?php
                            $table1 = new pager("Unit of Measure - ".$itmdsc , "itemunitfile"  ,$link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            $table1->field_code = "itmunitcde";
                            $table1->field_code_init = "ITMUNT-000000001";

                            $table1->table_order_by["field"] = "recid";
                            $table1->table_order_by["type"] = "ASC";

                            $table1->table_filter_field = "itmcde";
                            $table1->table_filter_value = $itmcde;

                            // Display fields
                            $table1->field_type_dis["unmcde"] = "dropdown_custom";
                            $table1->field_name_dis["unmcde"] = "unmcde";
                            $table1->field_header_dis["unmcde"] = "Unit of Measure";
                            $table1->field_dropdown_field_name_dis["unmcde"] = "unmcde";
                            $table1->field_dropdown_field_name_value_dis["unmcde"] = "unmdsc";
                            $table1->field_dropdown_tablename_dis["unmcde"] = "itemunitmeasurefile";

                            $table1->field_type_dis["conversion"] = "number";
                            $table1->field_name_dis["conversion"] = "conversion";
                            $table1->field_header_dis["conversion"] = "Conversion";
                            $table1->field_decimal_place_dis["conversion"] = 2;

                            // CRUD fields
                            $table1->field_type_crud["unmcde"] = "dropdown_custom";
                            $table1->field_name_crud["unmcde"] = "unmcde";
                            $table1->field_header_crud["unmcde"] = "Unit of Measure";
                            $table1->field_is_required["unmcde"] = "Y";
                            $table1->field_is_unique["unmcde"] = "N";
                            $table1->field_dropdown_field_name_crud["unmcde"] = "unmcde";
                            $table1->field_dropdown_field_name_value_crud["unmcde"] = "unmdsc";
                            $table1->field_dropdown_tablename_crud["unmcde"] = "itemunitmeasurefile";
                            $table1->field_dropdown_orderby_field_crud["unmcde"] = "unmdsc";

                            $table1->field_type_crud["conversion"] = "text";
                            $table1->field_name_crud["conversion"] = "conversion";
                            $table1->field_header_crud["conversion"] = "Conversion";
                            $table1->field_is_required["conversion"] = "Y";
                            $table1->field_is_unique["conversion"] = "N";

                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 20;

                            $table1->show_export = "Y";
                            $table1->show_search = "Y";

                            // Custom PDF export
                            $table1->exp_pdf = "mf_item_uom_pdf.php";
                            $table1->exp_txt = "mf_item_uom_pdf.php";

                            $table1->alert_del = "Y";
                            $table1->alert_del_logo_dir = $logo_dir;
                            $table1->alert_del_logo_w = $logo_width;
                            $table1->alert_del_logo_h = $logo_height;

                            $table1->ua_field1  = "unmcde";
                            $table1->ua_field2  = "itmunitcde";

                            $table1->display_table();
                        ?>

                        <?php endif; ?>

                    </div>
                </td>

            </tr>
        </table>


    </form>


    <?php
        if($has_valid_item){
            // displays modal outside form to avoid confusion
            $table1->display_modal();
        }
    ?>




<!-- PAGER JS -->
<?php if($has_valid_item): ?>
<script src="pager/pager_js.class.js"></script>
<script>
(function(){
    var itmcde = '<?php echo addslashes($itmcde); ?>';
    var itemUomInUseRecids = <?php echo json_encode($item_uom_in_use_recids); ?>;
    var itemUomInUseAlertMessage = 'Cannot modify, item UOM in use';
    var originalAjaxFunc = window.ajaxFunc;
    var isEditMode = false;

    function isItemUomInUse(recid){
        return itemUomInUseRecids.indexOf(String(recid)) !== -1;
    }

    function applyItemUomActionState(scopeSelector){
        var scope = document.querySelector(scopeSelector);

        if(!scope){
            return;
        }

        itemUomInUseRecids.forEach(function(recid){
            var actionMenus = scope.querySelectorAll("[aria-labelledby='dropdownMenuButton1-" + recid + "']");

            actionMenus.forEach(function(menu){
                menu.querySelectorAll("li").forEach(function(item){
                    var itemText = item.textContent || "";

                    if(itemText.indexOf('Edit') !== -1 || itemText.indexOf('Delete') !== -1){
                        item.style.opacity = '0.5';
                    }
                });
            });
        });
    }

    // Override ajaxFunc to add custom validation
    window.ajaxFunc = function(event, recid, custom_param){
        if((event === 'getEdit' || event === 'delete') && isItemUomInUse(recid)){
            alert(itemUomInUseAlertMessage);
            return false;
        }

        // Intercept insert and submitEdit
        if(event === 'insert' || event === 'submitEdit'){
            var unmcde = $('#unmcde_crudModal').val();
            var conversion = $('#conversion_crudModal').val();
            var currentRecid = (event === 'submitEdit') ? $('#recid_hidden').val() : '';

            // Clear previous error
            $('.error_msg').html('');

            // For insert: show confirmation dialog first
            if(event === 'insert'){
                var confirmed = confirm('Please confirm before saving: the conversion rate will become final and cannot be edited later. Are you sure you want to continue?');
                if(!confirmed){
                    return false;
                }
            }

            // Validate via AJAX (uniqueness + conversion immutability check)
            var validationError = null;
            var ajaxError = false;

            $.ajax({
                url: 'mf_item_uom_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'validate_save',
                    itmcde: itmcde,
                    unmcde: unmcde,
                    conversion: conversion,
                    recid: currentRecid
                },
                async: false,
                success: function(response){
                    if(response && response.valid === false){
                        validationError = response.message || 'Validation failed.';
                    }
                },
                error: function(xhr, status, error){
                    console.error('AJAX Error:', status, error, xhr.responseText);
                    ajaxError = true;
                }
            });

            if(ajaxError){
                $('.error_msg').html('<div class="alert alert-danger">Error validating data. Please try again.</div>');
                return false;
            }

            if(validationError){
                $('.error_msg').html('<div class="alert alert-danger">' + validationError + '</div>');
                return false;
            }

            // Validation passed, proceed with original function
            return originalAjaxFunc(event, recid, custom_param);
        }

        // For getEdit: track that we're entering edit mode
        if(event === 'getEdit'){
            isEditMode = true;
        }

        // For openInsert: track that we're in insert mode
        if(event === 'openInsert'){
            isEditMode = false;
        }

        // For all other events, use original function
        return originalAjaxFunc(event, recid, custom_param);
    };

    // Handle modal display for edit mode restrictions
    $(document).ready(function(){
        applyItemUomActionState('#tbody_main');
        applyItemUomActionState('#tbody_main_mobile');

        ['tbody_main', 'tbody_main_mobile'].forEach(function(targetId){
            var target = document.getElementById(targetId);

            if(!target){
                return;
            }

            var observer = new MutationObserver(function(){
                applyItemUomActionState('#' + targetId);
            });

            observer.observe(target, { childList: true, subtree: true });
        });

        // Listen for modal shown event
        $('#crudModal').on('shown.bs.modal', function(){
            // Find the unmcde dropdown and remove 'pcs' option
            var $dropdown = $('#unmcde_crudModal');
            if($dropdown.length){
                $dropdown.find('option').each(function(){
                    if($(this).text().toLowerCase().trim() === 'pcs'){
                        $(this).remove();
                    }
                });
            }

            // Make conversion field readonly in edit mode
            var $conversionField = $('#conversion_crudModal');
            if($conversionField.length){
                $conversionField.prop('readonly', false);
                $conversionField.css('background-color', '');
                $conversionField.attr('title', '');
            }
        });

        // Reset edit mode flag when modal is hidden
        $('#crudModal').on('hidden.bs.modal', function(){
            isEditMode = false;
            // Reset conversion field styling
            var $conversionField = $('#conversion_crudModal');
            if($conversionField.length){
                $conversionField.prop('readonly', false);
                $conversionField.css('background-color', '');
                $conversionField.attr('title', '');
            }
        });
    });
})();
</script>
<?php endif; ?>
<?php
require "includes/main_footer.php";
?>
