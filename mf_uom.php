<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";

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
        var pagerAjaxFuncMfUom = window.ajaxFunc;

        window.ajaxFunc = function(event, recid, custom_param){
            if(event === "submitEdit"){
                var originalValue = $("#unmdsc_crudModal").attr("data-value-hidden");
                var currentValue = $("#unmdsc_crudModal").val();

                if(
                    typeof originalValue !== "undefined" &&
                    $.trim(originalValue) !== $.trim(currentValue) &&
                    !confirm("WARNING: Changing the Unit of Measure name will affect other existing records.\n\nAre you sure you want to proceed?")
                ){
                    return;
                }
            }

            return pagerAjaxFuncMfUom(event, recid, custom_param);
        };
    })();

    // Hide Edit/Delete buttons for 'pcs' rows
    function hidePcsButtons(){
        $('.data_table tbody tr').each(function(){
            var $row = $(this);
            // Get the first td which contains the unmdsc value
            var unmdscText = $row.find('td:first span').text().toLowerCase().trim();
            if(unmdscText === 'pcs'){
                // Hide the action buttons (Edit and Delete)
                $row.find('.btn_edit_class, .btn_delete_class').hide();
            }
        });
    }

    // Run on page load and after AJAX updates
    $(document).ready(function(){
        hidePcsButtons();
        // Also run after table refresh (observer for dynamic content)
        var observer = new MutationObserver(function(mutations){
            hidePcsButtons();
        });
        var tableBody = document.querySelector('.data_table tbody');
        if(tableBody){
            observer.observe(tableBody, { childList: true, subtree: true });
        }
    });
</script>
<?php
require "includes/main_footer.php";
?>
