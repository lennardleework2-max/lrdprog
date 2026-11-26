<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";

$used_unmcde_list = array();
$select_used_unmcde = "SELECT DISTINCT unmcde FROM itemunitfile WHERE unmcde IS NOT NULL AND unmcde <> ''";
$stmt_used_unmcde = $link->prepare($select_used_unmcde);
$stmt_used_unmcde->execute();
while($rs_used_unmcde = $stmt_used_unmcde->fetch()){
    $used_unmcde_list[] = $rs_used_unmcde["unmcde"];
}

$uom_in_use_recids = array();
$select_uom_references = "SELECT recid, unmcde FROM itemunitmeasurefile";
$stmt_uom_references = $link->prepare($select_uom_references);
$stmt_uom_references->execute();
while($rs_uom_reference = $stmt_uom_references->fetch()){
    if(in_array($rs_uom_reference["unmcde"], $used_unmcde_list, true)){
        $uom_in_use_recids[(string)$rs_uom_reference["recid"]] = true;
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

                        <?php

                            $table1 = new pager("Unit of Measure" , "itemunitmeasurefile"  ,$link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            $table1->field_code = "unmcde";
                            $table1->field_code_init = "UNM-00000001";

                            $table1->table_order_by["field"] = "unmdsc";
                            $table1->table_order_by["type"] = "ASC";

                            //FIELDS DISPLAY
                            $table1->field_type_dis["unmdsc"] = "text";
                            $table1->field_name_dis["unmdsc"] = "unmdsc";
                            $table1->field_header_dis["unmdsc"] = "Unit of Measure";

                            //FIELDS CRUD(create,read,update,delete)
                            $table1->field_type_crud["unmdsc"] = "text";
                            $table1->field_name_crud["unmdsc"] = "unmdsc";
                            $table1->field_header_crud["unmdsc"] = "Unit of Measure";
                            $table1->field_is_required["unmdsc"] = "Y";
                            $table1->field_is_unique["unmdsc"] = "Y";

                            //pager
                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 20;

                            //export
                            $table1->show_export = "Y";

                            //search
                            $table1->show_search = "Y";

                            //alert
                            $table1->alert_del = "Y";
                            $table1->alert_del_logo_dir = $logo_dir;
                            $table1->alert_del_logo_w = $logo_width;
                            $table1->alert_del_logo_h = $logo_height;

                            //user activity log
                            $table1->ua_field1  = "unmdsc";
                            $table1->ua_field2  = "unmcde";

                            //CRUD
                            $table1->display_table();
                        ?>

                    </div>
                </td>

            </tr>
        </table>


    </form>


    <?php
        // displays modal outside form to avoid confusion
        $table1->display_modal();
    ?>



<!-- PAGER JS -->
<script src="pager/pager_js.class.js"></script>
<script>
(function(){
    var originalAjaxFunc = window.ajaxFunc;
    var isEditMode = false;
    var lockedUomRecids = <?php echo json_encode($uom_in_use_recids); ?>;

    function isInUseUomRecid(recid){
        if(!recid){
            return false;
        }
        return lockedUomRecids[String(recid)] === true;
    }

    function styleLockedUomActions(){
        $('#tbody_main, #tbody_main_mobile').find("button.dropdown-toggle[id^='dropdownMenuButton1-']").each(function(){
            var $button = $(this);
            var buttonId = $button.attr('id') || '';
            var recid = buttonId.replace('dropdownMenuButton1-', '');
            var $dropdown = $button.closest('.dropdown');

            $dropdown.find('ul.main_action_dd > li').each(function(){
                var $item = $(this);
                var itemText = $.trim($item.text()).toLowerCase();
                var $link = $item.find('a.dropdown-item');

                if(itemText === 'edit' || itemText === 'delete'){
                    if(isInUseUomRecid(recid)){
                        $item.css('opacity', '0.5');
                        $link.css({
                            opacity: '0.5',
                            pointerEvents: 'none'
                        });
                        $item.attr('data-uom-in-use-action', itemText);
                    }else{
                        $item.css('opacity', '');
                        $link.css({
                            opacity: '',
                            pointerEvents: ''
                        });
                        $item.removeAttr('data-uom-in-use-action');
                    }
                }
            });
        });
    }

    window.ajaxFunc = function(event, recid, custom_param){
        if(event === 'getEdit' && isInUseUomRecid(recid)){
            alert('Unit of measure in use');
            return false;
        }

        if(event === 'delete' && isInUseUomRecid(recid)){
            alert('Unit of measure in use');
            return false;
        }

        // Intercept insert and submitEdit
        if(event === 'insert' || event === 'submitEdit'){
            var unmdsc = $('#unmdsc_crudModal').val();
            var currentRecid = (event === 'submitEdit') ? $('#recid_hidden').val() : '';

            if(event === 'submitEdit' && isInUseUomRecid(currentRecid)){
                alert('Unit of measure in use');
                $('#crudModal').modal('hide');
                return false;
            }

            // Clear previous error
            $('.error_msg').html('');

            // For insert: show confirmation dialog first
            if(event === 'insert'){
                var confirmed = confirm('Please confirm before saving: this Unit of Measure cannot be deleted later. Are you sure you want to continue?');
                if(!confirmed){
                    return false;
                }
            }

            // Validate via AJAX for insert and edit
            if(event === 'insert' || event === 'submitEdit'){
                var validationError = null;
                var ajaxError = false;

                $.ajax({
                    url: 'mf_uom_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'validate_save',
                        unmdsc: unmdsc,
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
        // Listen for modal shown event
        $('#crudModal').on('shown.bs.modal', function(){
            // Make UOM name field readonly in edit mode
            var $unmdscField = $('#unmdsc_crudModal');
            if($unmdscField.length){
                if(isEditMode){
                    $unmdscField.prop('readonly', false);
                    $unmdscField.css('background-color', '');
                    $unmdscField.attr('title', '');
                } else {
                    // Insert mode: ensure field is editable
                    $unmdscField.prop('readonly', false);
                    $unmdscField.css('background-color', '');
                    $unmdscField.attr('title', '');
                }
            }
        });

        // Reset edit mode flag when modal is hidden
        $('#crudModal').on('hidden.bs.modal', function(){
            isEditMode = false;
            // Reset field styling
            var $unmdscField = $('#unmdsc_crudModal');
            if($unmdscField.length){
                $unmdscField.prop('readonly', false);
                $unmdscField.css('background-color', '');
                $unmdscField.attr('title', '');
            }
        });
    });

    // Protect 'pcs' row from editing/deleting
    $(document).ready(function(){
        document.addEventListener('click', function(e){
            var lockedAction = $(e.target).closest('#tbody_main .main_action_dd > li[data-uom-in-use-action], #tbody_main_mobile .main_action_dd > li[data-uom-in-use-action]');

            if(!lockedAction.length){
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            if(typeof e.stopImmediatePropagation === 'function'){
                e.stopImmediatePropagation();
            }
            alert('Unit of measure in use');
            return false;
        }, true);

        // Function to style pcs row buttons
        function stylePcsButtons(){
            $('#tbody_main tr').each(function(){
                var $row = $(this);
                var unmdscText = $row.find('td:first span').text().toLowerCase().trim();
                var $btn = $row.find('.dropdown-toggle');

                if(unmdscText === 'pcs'){
                    // Gray out the button and disable dropdown
                    $btn.removeClass('btn-primary').addClass('btn-secondary');
                    $btn.removeAttr('data-bs-toggle');
                    $btn.attr('data-pcs-protected', 'true');
                }
            });
        }

        // Style buttons after AJAX loads content
        $(document).ajaxComplete(function(){
            stylePcsButtons();
            styleLockedUomActions();
        });

        // Initial styling
        stylePcsButtons();
        styleLockedUomActions();

        // Show alert when clicking protected pcs button
        $(document).on('click', '#tbody_main .dropdown-toggle[data-pcs-protected="true"]', function(e){
            e.preventDefault();
            e.stopPropagation();
            alert('pcs cannot be edited');
            return false;
        });

        $(document).on('click', '#tbody_main .main_action_dd > li[data-uom-in-use-action], #tbody_main_mobile .main_action_dd > li[data-uom-in-use-action]', function(e){
            e.preventDefault();
            e.stopPropagation();
            alert('Unit of measure in use');
            return false;
        });
    });
})();
</script>
<?php
require "includes/main_footer.php";
?>
