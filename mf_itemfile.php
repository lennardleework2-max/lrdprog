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

        function goItemUnitOfMeasure(itmcde){
            if(!itmcde){
                return;
            }
            var form = document.createElement("form");
            form.method = "POST";
            form.action = "mf_item_uom.php";

            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "itmcde";
            input.value = itmcde;

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
                           
                            $table1 = new pager("Items" , "itemfile"  ,$link);
                            
                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;
                            //$table1->customize_function_name = 'sample_func';
                            //$table1->display_only = "Y";

                            $table1->field_code = "itmcde";
                            $table1->field_code_init = "ITM-0001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "itmdsc";
                            $table1->table_order_by["type"] = "ASC";


                            //FIELDS  DISPLAY
                            $table1->field_type_dis["itemdsc"] = "text";
                            $table1->field_name_dis["itemdsc"] = "itmdsc";
                            $table1->field_header_dis["itemdsc"] = "Items";

                            $table1->field_type_dis["critical_qty"] = "number";
                            $table1->field_name_dis["critical_qty"] = "critical_qty";
                            $table1->field_header_dis["critical_qty"] = "Critical Quantity";

                            $table1->field_type_dis["wholesaleprc"] = "number";
                            $table1->field_name_dis["wholesaleprc"] = "wholesaleprc";
                            $table1->field_header_dis["wholesaleprc"] = "Wholesale Price";

                            $table1->field_type_dis["tiktok_itm_sku"] = "text";
                            $table1->field_name_dis["tiktok_itm_sku"] = "tiktok_itm_sku";
                            $table1->field_header_dis["tiktok_itm_sku"] = "Tiktok Item SKU";

                            $table1->field_type_dis["lazada_itm_sku"] = "text";
                            $table1->field_name_dis["lazada_itm_sku"] = "lazada_itm_sku";
                            $table1->field_header_dis["lazada_itm_sku"] = "Lazada Item SKU";

                            $table1->field_type_dis["shopee_itm_sku"] = "text";
                            $table1->field_name_dis["shopee_itm_sku"] = "shopee_itm_sku";
                            $table1->field_header_dis["shopee_itm_sku"] = "Shopee Item SKU";

                            //FIELDS  CRUD(create,read,update,delete)
                            $table1->field_type_crud["itemdsc"] = "text";
                            $table1->field_name_crud["itemdsc"] = "itmdsc";
                            $table1->field_header_crud["itemdsc"] = "Items";
                            $table1->field_is_required["itemdsc"] = "Y"; 
                            $table1->field_is_unique["itemdsc"] = "Y";

                            $table1->field_type_crud["critical_qty"] = "number";
                            $table1->field_name_crud["critical_qty"] = "critical_qty";
                            $table1->field_header_crud["critical_qty"] = "Critical Quantity";
                            $table1->field_is_required["critical_qty"] = "Y"; 
                            $table1->field_is_unique["critical_qty"] = "N";

                            $table1->field_type_crud["wholesaleprc"] = "text";
                            $table1->field_name_crud["wholesaleprc"] = "wholesaleprc";
                            $table1->field_header_crud["wholesaleprc"] = "Wholesale Price";
                            $table1->field_is_required["wholesaleprc"] = "N"; 
                            $table1->field_is_unique["wholesaleprc"] = "N";

                            $table1->field_type_crud["tiktok_itm_sku"] = "text";
                            $table1->field_name_crud["tiktok_itm_sku"] = "tiktok_itm_sku";
                            $table1->field_header_crud["tiktok_itm_sku"] = "Tiktok Item SKU";
                            $table1->field_is_required["tiktok_itm_sku"] = "N"; 
                            $table1->field_is_unique["tiktok_itm_sku"] = "Y";

                            $table1->field_type_crud["lazada_itm_sku"] = "text";
                            $table1->field_name_crud["lazada_itm_sku"] = "lazada_itm_sku";
                            $table1->field_header_crud["lazada_itm_sku"] = "Lazada Item SKU";
                            $table1->field_is_required["lazada_itm_sku"] = "N"; 
                            $table1->field_is_unique["lazada_itm_sku"] = "N";

                            $table1->field_type_crud["shopee_itm_sku"] = "text";
                            $table1->field_name_crud["shopee_itm_sku"] = "shopee_itm_sku";
                            $table1->field_header_crud["shopee_itm_sku"] = "Shopee Item SKU";
                            $table1->field_is_required["shopee_itm_sku"] = "N"; 
                            $table1->field_is_unique["shopee_itm_sku"] = "N";

                            //pager
                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 20;

                            //export
                            $table1->show_export = "Y";

                            //search
                             $table1->show_search = "Y";

                            //alert
                            $table1->alert_del = "N";
                            $table1->alert_del_logo_dir = $logo_dir;
                            
                            $table1->alert_del_logo_w = $logo_width;
                            $table1->alert_del_logo_h = $logo_height;

                            //user activity log
                            $table1->ua_field1  = "itmdsc";
                            $table1->ua_field2  = "itmcde";

                            // Custom button for Unit of Measure
                            $table1->btn_header[0] = "Unit of Measure";
                            $table1->btn_logo[0] = "<i class='fas fa-ruler'></i>";
                            $table1->btn_function[0] = "goItemUnitOfMeasure('{itmcde}')";
                            $table1->btn_color[0] = "#17a2b8";

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

