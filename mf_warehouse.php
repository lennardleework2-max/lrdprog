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
        function goWarehouseFloors(warcde){
            if(!warcde){
                return;
            }
            var form = document.createElement("form");
            form.method = "POST";
            form.action = "mf_warehouse_floor.php";

            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "warcde";
            input.value = warcde;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
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

                        <?php
                            $warehouse_columns = array();
                            $stmt_warehouse_columns = $link->query("SHOW COLUMNS FROM warehouse");
                            while($rs_warehouse_column = $stmt_warehouse_columns->fetch(PDO::FETCH_ASSOC)){
                                if(isset($rs_warehouse_column["Field"])){
                                    $warehouse_columns[$rs_warehouse_column["Field"]] = true;
                                }
                            }

                            $table1 = new pager("Warehouse" , "warehouse"  ,$link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            $table1->field_code = "warcde";
                            $table1->field_code_init = "WH-0000001";

                            $table1->table_order_by["field"] = isset($warehouse_columns["warehouse_name"]) ? "warehouse_name" : "recid";
                            $table1->table_order_by["type"] = "ASC";

                            // $table1->field_type_dis["warcde"] = "text";
                            // $table1->field_name_dis["warcde"] = "warcde";
                            // $table1->field_header_dis["warcde"] = "Warehouse ID";

                            if(isset($warehouse_columns["warehouse_name"])){
                                $table1->field_type_dis["warehouse_name"] = "text";
                                $table1->field_name_dis["warehouse_name"] = "warehouse_name";
                                $table1->field_header_dis["warehouse_name"] = "Warehouse Name";

                                $table1->field_type_crud["warehouse_name"] = "text";
                                $table1->field_name_crud["warehouse_name"] = "warehouse_name";
                                $table1->field_header_crud["warehouse_name"] = "Warehouse Name";
                                $table1->field_is_required["warehouse_name"] = "Y";
                                $table1->field_is_unique["warehouse_name"] = "N";
                            }

                            if(isset($warehouse_columns["location"])){
                                $table1->field_type_dis["location"] = "text";
                                $table1->field_name_dis["location"] = "location";
                                $table1->field_header_dis["location"] = "Location";

                                $table1->field_type_crud["location"] = "text";
                                $table1->field_name_crud["location"] = "location";
                                $table1->field_header_crud["location"] = "Location";
                                $table1->field_is_required["location"] = "N";
                                $table1->field_is_unique["location"] = "N";
                            }

                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 20;

                            $table1->show_export = "Y";
                            $table1->show_search = "Y";

                            $floors_btn_logo = "&lt;i class=&#39;fas fa-layer-group&#39;&gt;&lt;/i&gt;";
                            $floors_btn_function = "goWarehouseFloors(&#39;{warcde}&#39;)";

                            $table1->btn_header[0] = "Floors";
                            $table1->btn_logo[0] = $floors_btn_logo;
                            $table1->btn_function[0] = $floors_btn_function;
                            $table1->btn_color[0] = "#ff8c00";

                            $table1->alert_del = "Y";
                            $table1->alert_del_logo_dir = $logo_dir;
                            $table1->alert_del_logo_w = $logo_width;
                            $table1->alert_del_logo_h = $logo_height;

                            $table1->ua_field1  = isset($warehouse_columns["warehouse_name"]) ? "warehouse_name" : "warcde";
                            $table1->ua_field2  = "warcde";

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
