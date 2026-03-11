<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";
?>



    <form name='myforms' id="myforms" method="post" target="_self"> 
        <table class='big_table'> 
            <tr colspan=1>
                <td colspan=1 class='td_bl'>
                    <?php
                        require 'includes/main_menu.php';
                    ?>
                </td>
 
                <td colspan=1 class="td_br" id="td_br">

                    <div class="container-fluid mt-2 main_br_div">

                        <?php
                            
                            $table1 = new pager("User Log" , "useractivitylogfile" , $link);

                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            
                            //field code
                            $table1->field_code = "advisorID";
                            $table1->field_code_init = "000000000001";

                            //custom button
                            // $table1->btn_header[0] = "Renew";
                            // $table1->btn_logo[0] = "<i class=\"fas fa-pencil-alt\"></i>";
                            // $table1->btn_function[0] = "jquery()";
                            // $table1->btn_color[0] = " #00cccc";

                            //ORDER
                            $table1->table_order_by["field"] = "recid";
                            $table1->table_order_by["type"] = "DESC";

                            //FIELDS  DISPLAY
                            $table1->field_type_dis[0] = "text";
                            $table1->field_name_dis[0] = "usrname";
                            $table1->field_header_dis[0] = "Username";


                            $table1->field_type_dis[1] = "text";
                            $table1->field_name_dis[1] = "fullname";
                            $table1->field_header_dis[1] = "Full Name";

                            $table1->field_type_dis[2] = "date";
                            $table1->field_name_dis[2] = "usrdte";
                            $table1->field_header_dis[2] = "Date of Actvity";

                            $table1->field_type_dis[3] = "text";
                            $table1->field_name_dis[3] = "usrtim";
                            $table1->field_header_dis[3] = "Time";
                            
                            $table1->field_type_dis[4] = "text";
                            $table1->field_name_dis[4] = "activity";
                            $table1->field_header_dis[4] = "Activity";

                            $table1->field_type_dis[5] = "text";
                            $table1->field_name_dis[5] = "remarks";
                            $table1->field_header_dis[5] = "Remarks";

                            // $table1->field_type_dis[6] = "text";
                            // $table1->field_name_dis[6] = "recid";
                            // $table1->field_header_dis[6] = "recid";

                            //FIELDS  DISPLAY
                            // $table1->field_type_crud[0] = "text";
                            // $table1->field_name_crud[0] = "advisorname";
                            // $table1->field_header_crud[0] = "Employee Name";
                            // $table1->field_is_required[0] = "Y"; 
                            // $table1->field_is_unique[0] = "N";

                            // $table1->field_type_crud[1] = "checkbox";
                            // $table1->field_name_crud[1] = "is_vaccinated";
                            // $table1->field_header_crud[1] = "Vaccinated";
                            // $table1->field_is_required[1] = "N"; 
                            // $table1->field_is_unique[1] = "N";

                            
                            // $table1->field_type_crud[2] = "date";
                            // $table1->field_name_crud[2] = "employee_birthday";
                            // $table1->field_header_crud[2] = "Birhtday";
                            // $table1->field_is_required[2] = "N"; 
                            // $table1->field_is_unique[2] = "N";

                            // $table1->field_type_crud[3] = "number";
                            // $table1->field_name_crud[3] = "salary";
                            // $table1->field_header_crud[3] = "Salary";
                            // $table1->field_is_required[3] = "N"; 
                            // $table1->field_is_unique[3] = "N";
                            // $table1->field_numlimit_crud[3]=100000;

                            // $table1->field_type_crud[4] = "textarea";
                            // $table1->field_name_crud[4] = "address";
                            // $table1->field_header_crud[4] = "Address";
                            // $table1->field_is_required[4] = "Y"; 
                            // $table1->field_is_unique[4] = "Y";

                            //pager
                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 10;
                            
                            //export
                            $table1->show_export = "Y";
                            //search
                            $table1->show_search = "Y";

                            //display only
                            $table1->display_only = "Y";

                            //CRUD
                            $table1->display_table();
                        ?>

                    </div>
                </td>

            </tr>
        </table>
    </form>

    
    <?php 
    
        // pager
        $table1->display_modal();

        //$table1->pager_js();
    ?>



<script src="pager/pager_js.class.js"> </script>
<?php 
include "includes/main_footer.php";
?>

