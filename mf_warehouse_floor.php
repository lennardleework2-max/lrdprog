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

    <script>
        function john(event2, id){
            alert(event2+","+id);
        }
    </script>

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

                        <?php
                            $table1 = new pager("Warehouse Floor" , "warehouse_floor"  ,$link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            $table1->field_code = "warehouse_floor_id";
                            $table1->field_code_init = "WFL-0001";

                            $table1->table_order_by["field"] = "floor_no";
                            $table1->table_order_by["type"] = "ASC";

                            $table1->field_type_dis["warehouse_floor_id"] = "text";
                            $table1->field_name_dis["warehouse_floor_id"] = "warehouse_floor_id";
                            $table1->field_header_dis["warehouse_floor_id"] = "Warehouse Floor ID";

                            $table1->field_type_dis["warehouse_id"] = "dropdown_custom";
                            $table1->field_name_dis["warehouse_id"] = "warehouse_id";
                            $table1->field_header_dis["warehouse_id"] = "Warehouse";
                            $table1->field_dropdown_field_name_dis["warehouse_id"] = "warehouse_id";
                            $table1->field_dropdown_field_name_value_dis["warehouse_id"] = "warehouse_name";
                            $table1->field_dropdown_tablename_dis["warehouse_id"] = "warehouse";

                            $table1->field_type_dis["floor_name"] = "text";
                            $table1->field_name_dis["floor_name"] = "floor_name";
                            $table1->field_header_dis["floor_name"] = "Floor Name";

                            $table1->field_type_dis["floor_no"] = "number";
                            $table1->field_name_dis["floor_no"] = "floor_no";
                            $table1->field_header_dis["floor_no"] = "Floor No";

                            $table1->field_type_crud["warehouse_id"] = "dropdown_custom";
                            $table1->field_name_crud["warehouse_id"] = "warehouse_id";
                            $table1->field_header_crud["warehouse_id"] = "Warehouse";
                            $table1->field_dropdown_field_name_crud["warehouse_id"] = "warehouse_id";
                            $table1->field_dropdown_field_name_value_crud["warehouse_id"] = "warehouse_name";
                            $table1->field_dropdown_tablename_crud["warehouse_id"] = "warehouse";
                            $table1->field_dropdown_orderby_field_crud["warehouse_id"] = "warehouse_name";
                            $table1->field_is_required["warehouse_id"] = "Y";
                            $table1->field_is_unique["warehouse_id"] = "N";

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
<script src="pager/pager_js.class.js"> </script>
<?php 
require "includes/main_footer.php";
?>

