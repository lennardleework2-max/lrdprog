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

                        <?php

                            $table1 = new pager("Platform Shops" , "mf_platform_shop"  ,$link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;
                            //$table1->customize_function_name = 'sample_func';
                            //$table1->display_only = "Y";

                            $table1->field_code = "platform_shop_id";
                            $table1->field_code_init = "PFS-00001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "platform_shop_title";
                            $table1->table_order_by["type"] = "ASC";


                            //FIELDS  DISPLAY
                            $table1->field_type_dis["platform_id"] = "dropdown_custom";
                            $table1->field_name_dis["platform_id"] = "platform_id";
                            $table1->field_header_dis["platform_id"] = "Platform";
                            $table1->field_dropdown_field_name_value_dis["platform_id"] = "platform_desc";
                            $table1->field_dropdown_field_name_dis["platform_id"] = "platform_id";
                            $table1->field_dropdown_tablename_dis["platform_id"] = "mf_platform";

                            $table1->field_type_dis["platform_shop_title"] = "text";
                            $table1->field_name_dis["platform_shop_title"] = "platform_shop_title";
                            $table1->field_header_dis["platform_shop_title"] = "Shop Title";

                            $table1->field_type_dis["platform_shop_details"] = "text";
                            $table1->field_name_dis["platform_shop_details"] = "platform_shop_details";
                            $table1->field_header_dis["platform_shop_details"] = "Shop Details";


                            //FIELDS  CRUD(create,read,update,delete)
                            $table1->field_type_crud["platform_id"] = "dropdown_custom";
                            $table1->field_dropdown_tablename_crud["platform_id"] = "mf_platform";
                            $table1->field_dropdown_field_name_crud["platform_id"] = "platform_id";
                            $table1->field_dropdown_field_name_value_crud["platform_id"] = "platform_desc";
                            $table1->field_name_crud["platform_id"] = "platform_id";
                            $table1->field_header_crud["platform_id"] = "Platform";
                            $table1->field_is_required["platform_id"] = "Y";
                            $table1->field_is_unique["platform_id"] = "N";
                            $table1->field_dropdown_orderby_field_crud["platform_id"] = "platform_desc";
                            $table1->field_dropdown_filter_crud["platform_id"] = "platform_desc <> 'NONE'";


                            $table1->field_type_crud["platform_shop_title"] = "text";
                            $table1->field_name_crud["platform_shop_title"] = "platform_shop_title";
                            $table1->field_header_crud["platform_shop_title"] = "Shop Title";
                            $table1->field_is_required["platform_shop_title"] = "Y";
                            $table1->field_is_unique["platform_shop_title"] = "N";


                            $table1->field_type_crud["platform_shop_details"] = "text";
                            $table1->field_name_crud["platform_shop_details"] = "platform_shop_details";
                            $table1->field_header_crud["platform_shop_details"] = "Shop Details";
                            $table1->field_is_required["platform_shop_details"] = "N";
                            $table1->field_is_unique["platform_shop_details"] = "N";

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
                            $table1->ua_field1  = "platform_shop_title";
                            $table1->ua_field2  = "platform_shop_id";

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
<script src="pager/pager_js.class.js"> </script>
<?php
require "includes/main_footer.php";
?>
