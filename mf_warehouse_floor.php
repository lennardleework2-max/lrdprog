<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";

$warehouse_id = isset($_POST['warehouse_id']) ? trim($_POST['warehouse_id']) : '';
$warehouse_name = '';

if($warehouse_id !== ''){
    $select_db_wh = "SELECT warehouse_name FROM warehouse WHERE warehouse_id=?";
    $stmt_wh = $link->prepare($select_db_wh);
    $stmt_wh->execute(array($warehouse_id));
    $rs_wh = $stmt_wh->fetch();
    if(!empty($rs_wh)){
        $warehouse_name = $rs_wh["warehouse_name"];
    }
}

$has_valid_warehouse = ($warehouse_id !== '' && $warehouse_name !== '');
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
                            <a href="mf_warehouse.php" class="btn btn-sm btn-outline-primary">Back to Warehouse</a>
                        </div>

                        <?php if(!$has_valid_warehouse): ?>
                            <div class="alert alert-warning">Please choose a warehouse first, then click <b>Floors</b> from its Action menu.</div>
                        <?php else: ?>

                        <?php
                            $table1 = new pager("Warehouse Floors - ".$warehouse_name , "warehouse_floor"  ,$link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            $table1->field_code = "warehouse_floor_id";
                            $table1->field_code_init = "WHFID-0000001";

                            $table1->table_order_by["field"] = "floor_no";
                            $table1->table_order_by["type"] = "ASC";

                            $table1->table_filter_field = "warehouse_id";
                            $table1->table_filter_value = $warehouse_id;

                            $table1->field_type_dis["floor_name"] = "text";
                            $table1->field_name_dis["floor_name"] = "floor_name";
                            $table1->field_header_dis["floor_name"] = "Floor Name";

                            $table1->field_type_dis["floor_no"] = "number";
                            $table1->field_name_dis["floor_no"] = "floor_no";
                            $table1->field_header_dis["floor_no"] = "Floor No";

                            $table1->field_type_crud["floor_name"] = "text";
                            $table1->field_name_crud["floor_name"] = "floor_name";
                            $table1->field_header_crud["floor_name"] = "Floor Name";
                            $table1->field_is_required["floor_name"] = "N";
                            $table1->field_is_unique["floor_name"] = "N";

                            $table1->field_type_crud["floor_no"] = "number";
                            $table1->field_name_crud["floor_no"] = "floor_no";
                            $table1->field_header_crud["floor_no"] = "Floor No";
                            $table1->field_is_required["floor_no"] = "N";
                            $table1->field_is_unique["floor_no"] = "N";

                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 20;

                            $table1->show_export = "Y";
                            $table1->show_search = "Y";

                            $table1->alert_del = "N";
                            $table1->alert_del_logo_dir = $logo_dir;
                            $table1->alert_del_logo_w = $logo_width;
                            $table1->alert_del_logo_h = $logo_height;

                            $table1->ua_field1  = "floor_name";
                            $table1->ua_field2  = "warehouse_floor_id";

                            $table1->display_table();
                        ?>

                        <?php endif; ?>

                    </div>
                </td>

            </tr>
        </table>


    </form>

    
    <?php 
        if($has_valid_warehouse){
            // displays modal outside form to avoid confusion
            $table1->display_modal();
        }
    ?>


   

<!-- PAGER JS -->   
<?php if($has_valid_warehouse): ?>
<script src="pager/pager_js.class.js"> </script>
<?php endif; ?>
<?php 
require "includes/main_footer.php";
?>
