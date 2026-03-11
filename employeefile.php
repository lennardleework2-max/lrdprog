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
                           
                            $table1 = new pager("Sample Name" , "employeefile"  ,$link);
                            
                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            //$table1->customize_function_name = 'sample_func';
                            //$table1->display_only = "Y";

                            $table1->field_code = "advisorID";
                            $table1->field_code_init = "BADMN-0000001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "employee_birthday";
                            $table1->table_order_by["type"] = "DESC";

                            //FIELDS  DISPLAY
                            $table1->field_type_dis["advisorname"] = "text";
                            $table1->field_name_dis["advisorname"] = "advisorname";
                            $table1->field_header_dis["advisorname"] = "Employee Name";
                            $table1->field_font_weight_dis["advisorname"] = "bold";

                            $table1->field_type_dis["employee_birthday"] = "date";
                            $table1->field_name_dis["employee_birthday"] = "employee_birthday";
                            $table1->field_header_dis["employee_birthday"] = "Birhtday";
                            
                            $table1->field_type_dis["position_code"] = "dropdown_custom";
                            $table1->field_name_dis["position_code"] = "position_code";
                            $table1->field_header_dis["position_code"] = "Position";
                            $table1->field_dropdown_field_name_value_dis["position_code"] = "position_desc";
                            $table1->field_dropdown_field_name_dis["position_code"] = "position_code";
                            $table1->field_dropdown_tablename_dis["position_code"] = "mf_positionfile";

                            $table1->field_type_dis["salary"] = "number";
                            $table1->field_name_dis["salary"] = "salary";
                            $table1->field_header_dis["salary"] = "Salary";
                            $table1->field_decimal_place_dis["salary"] = 2;

                            $table1->field_type_dis["is_vaccinated"] = "checkbox";
                            $table1->field_name_dis["is_vaccinated"] = "is_vaccinated";
                            $table1->field_header_dis["is_vaccinated"] = "Vaccinated";

                            $table1->field_type_dis["telnum"] = "number";
                            $table1->field_name_dis["telnum"] = "telnum";
                            $table1->field_header_dis["telnum"] = "Tel Num.";

                            $table1->field_type_dis["address"] = "text";
                            $table1->field_name_dis["address"] = "address";
                            $table1->field_header_dis["address"] = "Address";


                            //FIELDS  CRUD(create,read,update,delete)
                            $table1->field_type_crud["advisorname"] = "text";
                            $table1->field_name_crud["advisorname"] = "advisorname";
                            $table1->field_header_crud["advisorname"] = "Employee";
                            $table1->field_is_required["advisorname"] = "Y"; 
                            $table1->field_is_unique["advisorname"] = "N";

                            $table1->field_type_crud["birthday"] = "date";
                            $table1->field_name_crud["birthday"] = "employee_birthday";
                            $table1->field_header_crud["birthday"] = "Birhtday";
                            $table1->field_is_required["birthday"] = "Y"; 
                            $table1->field_is_unique["birthday"] = "N";

                            $table1->field_type_crud["position_code"] = "dropdown_custom";
                            $table1->field_name_crud["position_code"] = "position_code";
                            $table1->field_header_crud["position_code"] = "Position";
                            $table1->field_dropdown_field_name_crud["position_code"] = "position_code";
                            $table1->field_dropdown_field_name_value_crud["position_code"] = "position_desc";
                            $table1->field_dropdown_tablename_crud["position_code"] = "mf_positionfile";
                            $table1->field_is_required["position_code"] = "Y";
                            $table1->field_is_unique["position_code"] = "N";

                            $table1->field_type_crud["is_vaccinated"] = "checkbox";
                            $table1->field_name_crud["is_vaccinated"] = "is_vaccinated";
                            $table1->field_header_crud["is_vaccinated"] = "Vaccinated";

                            $table1->field_type_crud["telnum"] = "number";
                            $table1->field_name_crud["telnum"] = "telnum";
                            $table1->field_header_crud["telnum"] = "Tel Num.";
                            $table1->field_is_unique["telnum"] = "N";
                            $table1->field_is_required["telnum"] = "N";

                            $table1->field_type_crud["address"] = "text";
                            $table1->field_name_crud["address"] = "address";
                            $table1->field_header_crud["address"] = "Address";
                            $table1->field_is_required["address"] = "N"; 
                            $table1->field_is_unique["address"] = "N";

                            $table1->field_type_crud["salary"] = "number";
                            $table1->field_name_crud["salary"] = "salary";
                            $table1->field_header_crud["salary"] = "Salary";
                            $table1->field_is_required["salary"] = "N"; 
                            $table1->field_is_unique["salary"] = "N";

                            //pager
                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 1;

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
                            $table1->ua_field1  = "advisorname";
                            $table1->ua_field2  = "advisorID";

                            //export settings
                            // $table1->exp_pdf = "var_dump.php";
                            // $table1->exp_txt = "export_page.php";

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

