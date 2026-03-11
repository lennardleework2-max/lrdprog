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
                           
                            $table1 = new pager("Sales" , "tranfile1"  ,$link);
                            
                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            //$table1->customize_function_name = 'sample_func';
                            //$table1->display_only = "Y";

                            $table1->field_code = "trncde";
                            $table1->field_code_init = "TRN-00001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "recid";
                            $table1->table_order_by["type"] = "ASC";


                            //FIELDS  DISPLAY
                            $table1->field_type_dis["docnum"] = "text";
                            $table1->field_name_dis["docnum"] = "docnum";
                            $table1->field_header_dis["docnum"] = "Doc Num.";

                            $table1->field_type_dis["trndte"] = "date";
                            $table1->field_name_dis["trndte"] = "trndte";
                            $table1->field_header_dis["trndte"] = "Date";

                            $table1->field_type_dis["cuscde"] = "text";
                            $table1->field_name_dis["cuscde"] = "cuscde";
                            $table1->field_header_dis["cuscde"] = "Name";

                            $table1->field_type_dis["orderby"] = "text";
                            $table1->field_name_dis["orderby"] = "orderby";
                            $table1->field_header_dis["orderby"] = "Order By";

                            $table1->field_type_dis["shipto"] = "text";
                            $table1->field_name_dis["shipto"] = "shipto";
                            $table1->field_header_dis["shipto"] = "Ship To:";

                            $table1->field_type_dis["trntot"] = "number";
                            $table1->field_name_dis["trntot"] = "trntot";
                            $table1->field_header_dis["trntot"] = "Total";

        

                            //FIELDS  CRUD(create,read,update,delete)
                            // $table1->field_type_crud["cusdsc"] = "text";
                            // $table1->field_name_crud["cusdsc"] = "cusdsc";
                            // $table1->field_header_crud["cusdsc"] = "Customers";
                            // $table1->field_is_required["cusdsc"] = "Y"; 
                            // $table1->field_is_unique["cusdsc"] = "N";

                        

                            //pager
                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 1000;

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
                            $table1->ua_field1  = "cusdsc";
                            $table1->ua_field2  = "cuscde";

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

