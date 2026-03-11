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

                           

                            $table1 = new pager("Buyers Profile" , "mf_buyers"  ,$link);

                            

                            $table1->add_crud = $add_crud;

                            $table1->edit_crud = $edit_crud;

                            $table1->delete_crud = $delete_crud;

                            $table1->view_crud = $view_crud;

                            $table1->export_crud = $export_crud;



                            //$table1->customize_function_name = 'sample_func';

                            //$table1->display_only = "Y";



                            $table1->field_code = "buyer_id";

                            $table1->field_code_init = "BID-0001";



                            //ORDER (table ORDER BY)

                            $table1->table_order_by["field"] = "buyer_name";

                            $table1->table_order_by["type"] = "ASC";



                            //FIELDS  DISPLAY

                            $table1->field_type_dis["buyer_name"] = "text";

                            $table1->field_name_dis["buyer_name"] = "buyer_name";

                            $table1->field_header_dis["buyer_name"] = "Buyer Name";



                            $table1->field_type_dis["forwarder_name"] = "text";

                            $table1->field_name_dis["forwarder_name"] = "forwarder_name";

                            $table1->field_header_dis["forwarder_name"] = "Forwarder Name:";





                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["buyer_name"] = "text";

                            $table1->field_name_crud["buyer_name"] = "buyer_name";

                            $table1->field_header_crud["buyer_name"] = "Buyer Name:";

                            $table1->field_is_required["buyer_name"] = "Y"; 

                            $table1->field_is_unique["buyer_name"] = "N";

                            

                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["buyer_address"] = "text";

                            $table1->field_name_crud["buyer_address"] = "buyer_address";

                            $table1->field_header_crud["buyer_address"] = "Buyer Address:";

                            $table1->field_is_required["buyer_address"] = "Y"; 

                            $table1->field_is_unique["buyer_address"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["salesman_id"] = "dropdown_custom";

                            $table1->field_dropdown_tablename_crud["salesman_id"] = "mf_salesman";

                            $table1->field_dropdown_field_name_crud["salesman_id"] = "salesman_id";

                            $table1->field_dropdown_field_name_value_crud["salesman_id"] = "salesman_name";

                            $table1->field_name_crud["salesman_id"] = "salesman_id";

                            $table1->field_header_crud["salesman_id"] = "Salesman";

                            $table1->field_is_required["salesman_id"] = "N"; 

                            $table1->field_is_unique["salesman_id"] = "N";

                            $table1->field_dropdown_orderby_field_crud["salesman_id"] = "salesman_name";


                            $table1->field_type_crud["route_id"] = "dropdown_custom";

                            $table1->field_dropdown_tablename_crud["route_id"] = "mf_routes";

                            $table1->field_dropdown_field_name_crud["route_id"] = "route_id";

                            $table1->field_dropdown_field_name_value_crud["route_id"] = "route_desc";

                            $table1->field_name_crud["route_id"] = "route_id";

                            $table1->field_header_crud["route_id"] = "Route";

                            $table1->field_is_required["route_id"] = "N"; 

                            $table1->field_is_unique["route_id"] = "N";

                            $table1->field_dropdown_orderby_field_crud["route_id"] = "route_desc";


                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["buyer_contactnum"] = "text";

                            $table1->field_name_crud["buyer_contactnum"] = "buyer_contactnum";

                            $table1->field_header_crud["buyer_contactnum"] = "Buyer Contact Num:";

                            $table1->field_is_required["buyer_contactnum"] = "Y"; 

                            $table1->field_is_unique["buyer_contactnum"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["forwarder_name"] = "text";

                            $table1->field_name_crud["forwarder_name"] = "forwarder_name";

                            $table1->field_header_crud["forwarder_name"] = "Forwarder Name:";

                            $table1->field_is_required["forwarder_name"] = "Y"; 

                            $table1->field_is_unique["forwarder_name"] = "N";





                            $table1->field_type_crud["forwarder_address"] = "text";

                            $table1->field_name_crud["forwarder_address"] = "forwarder_address";

                            $table1->field_header_crud["forwarder_address"] = "Forwarder Address:";

                            $table1->field_is_required["forwarder_address"] = "Y"; 

                            $table1->field_is_unique["forwarder_address"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["forwarder_contactnum"] = "text";

                            $table1->field_name_crud["forwarder_contactnum"] = "forwarder_contactnum";

                            $table1->field_header_crud["forwarder_contactnum"] = "Forwarder Contact Num:";

                            $table1->field_is_required["forwarder_contactnum"] = "Y"; 

                            $table1->field_is_unique["forwarder_contactnum"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["declared_items"] = "text";

                            $table1->field_name_crud["declared_items"] = "declared_items";

                            $table1->field_header_crud["declared_items"] = "Declared Item:";

                            $table1->field_is_required["declared_items"] = "Y"; 

                            $table1->field_is_unique["declared_items"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["declared_amnt_percent"] = "text";

                            $table1->field_name_crud["declared_amnt_percent"] = "declared_amnt_percent";

                            $table1->field_header_crud["declared_amnt_percent"] = "Declared Amount Percent:(eg:10 whole number)";

                            $table1->field_is_required["declared_amnt_percent"] = "Y"; 

                            $table1->field_is_unique["declared_amnt_percent"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["cargo_company"] = "text";

                            $table1->field_name_crud["cargo_company"] = "cargo_company";

                            $table1->field_header_crud["cargo_company"] = "Cargo Company";

                            $table1->field_is_required["cargo_company"] = "Y"; 

                            $table1->field_is_unique["cargo_company"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["cargo_address"] = "text";

                            $table1->field_name_crud["cargo_address"] = "cargo_address";

                            $table1->field_header_crud["cargo_address"] = "Cargo Company";

                            $table1->field_is_required["cargo_address"] = "Y"; 

                            $table1->field_is_unique["cargo_address"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["cargo_cellnum"] = "text";

                            $table1->field_name_crud["cargo_cellnum"] = "cargo_cellnum";

                            $table1->field_header_crud["cargo_cellnum"] = "Cargo Cellnum";

                            $table1->field_is_required["cargo_cellnum"] = "Y"; 

                            $table1->field_is_unique["cargo_cellnum"] = "N";



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["cargo_name"] = "text";

                            $table1->field_name_crud["cargo_name"] = "cargo_name";

                            $table1->field_header_crud["cargo_name"] = "Cargo Name";

                            $table1->field_is_required["cargo_name"] = "Y"; 

                            $table1->field_is_unique["cargo_name"] = "N";





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

                            $table1->ua_field1  = "buyer_name";

                            $table1->ua_field2  = "buyer_id";



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



