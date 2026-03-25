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
                            $table1->field_is_unique["unmcde"] = "Y";
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

                            $table1->alert_del = "N";
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
// Exclude 'pcs' from Unit of Measure dropdown in add/edit modal
$(document).ready(function(){
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
    });
});
</script>
<?php endif; ?>
<?php
require "includes/main_footer.php";
?>

