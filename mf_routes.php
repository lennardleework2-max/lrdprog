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

                           

                            $table1 = new pager("Routes" , "mf_routes"  ,$link);

                            

                            $table1->add_crud = $add_crud;

                            $table1->edit_crud = $edit_crud;

                            $table1->delete_crud = $delete_crud;

                            $table1->view_crud = $view_crud;

                            $table1->export_crud = $export_crud;

                            //$table1->customize_function_name = 'sample_func';

                            //$table1->display_only = "Y";



                            $table1->field_code = "route_id";

                            $table1->field_code_init = "ROU-0001";



                            //ORDER (table ORDER BY)

                            $table1->table_order_by["field"] = "route_desc";

                            $table1->table_order_by["type"] = "ASC";





                            //FIELDS  DISPLAY

                            $table1->field_type_dis["route_desc"] = "text";

                            $table1->field_name_dis["route_desc"] = "route_desc";

                            $table1->field_header_dis["route_desc"] = "Route";


                            /*
                            $table1->field_type_dis["commission"] = "number";

                            $table1->field_name_dis["commission"] = "commission";

                            $table1->field_header_dis["commission"] = "Commission in Percent";
                            */




        



                            //FIELDS  CRUD(create,read,update,delete)

                            $table1->field_type_crud["route_desc"] = "text";

                            $table1->field_name_crud["route_desc"] = "route_desc";

                            $table1->field_header_crud["route_desc"] = "Route";

                            $table1->field_is_required["route_desc"] = "Y"; 

                            $table1->field_is_unique["route_desc"] = "Y";




                            /*
                            $table1->field_type_crud["commission"] = "number";

                            $table1->field_name_crud["commission"] = "commission";

                            $table1->field_header_crud["commission"] = "Commission in Percentage";
                            */
                            //$table1->field_is_required["critical_qty"] = "Y"; 

                            //$table1->field_is_unique["critical_qty"] = "N";



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

                            $table1->ua_field1  = "route_desc";

                            $table1->ua_field2  = "route_id";



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



