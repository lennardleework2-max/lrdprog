<?php

require_once("resources/db_init.php");

Class pager extends db_init{

    public $table;
    public $link;
    public $main_header;

    //for display
    public $field_name_dis = array(); 
    public $field_header_dis = array();
    public $field_type_dis = array();  //text,textarea,number,date,checkbox
    public $field_font_weight_dis = array(); //fontweight for display
    public $field_decimal_place_dis = array();

    //for crud
    public $field_name_crud = array();
    public $field_header_crud = array(); 
    public $field_type_crud = array();  //text,textarea,number,date,checkbox
    public $field_numlimit_crud = array();//to limit numbers
    
    //(field/table) options
    public $field_code;
    public $field_code_init;

    public $field_is_required = array();  //required fields
    public $field_is_unique = array();    // unique
    public $table_order_by = array();    //ORDER BY VARIABLES

    //for dropdowns display
    public $field_dropdown_field_name_value_dis = array();
    public $field_dropdown_field_name_dis = array();
    public $field_dropdown_tablename_dis = array();
    


    //for dropdown crud
    public $field_dropdown_field_name_value_crud = array();
    public $field_dropdown_field_name_crud = array();
    public $field_dropdown_tablename_crud = array();
    public $field_dropdown_list_crud = array();
    public $field_dropdown_selected_crud = array();
    public $field_dropdown_orderby_field_crud = array();
    public $table_filter_field;
    public $table_filter_value;
    
    //Pager settings
    public $show_pager; 
    public $pager_xlimit; // LIMIT ROW FETCH

    //alert settings
    public $alert_del;
    public $alert_del_logo_dir;
    public $alert_del_logo_w;
    public $alert_del_logo_h;
    
    //Export Settings
    public $show_export;

    //Search Settings
    public $show_search;

    //custom buttons
    public $btn_header = array();
    public $btn_logo = array();
    public $btn_function = array();
    public $btn_color = array();

    //no crud
    public $display_only;

    //user activity
    public $ua_field1; //DESC
    public $ua_field2; //CODE

    //user access crud
    public $add_crud;
    public $edit_crud;
    public $delete_crud;
    public $view_crud;
    public $export_crud;


    //export settings
    public $exp_pdf;
    public $exp_txt;

    public $customize_function_name; //CUSTOM

    function __construct(string $main_header , string $table , $link){
        $this->main_header  = $main_header;  
        $this->table        =  $table;
        $this->link         =  $link;
    }

    public function display_table(){

        if(!isset($this->table_order_by["field"])){
            $this->table_order_by["field"] = "recid";
        }
        if(!isset($this->table_order_by["type"])){
            $this->table_order_by["type"] = "ASC";
        }

        //VARIABLES FOR LOOPING THROUGH THE FIELD NAME ARRAY
        $fields = "";
        $fields_count = 0;

        foreach($this->field_name_dis as $key_select => $value_select){

            if($fields_count == 0){
                $fields = $value_select;
            }else{
                $fields .= ",".$value_select;
            }

            $fields_count++;
        }
        $fields.=",recid";
        
        //header and add button
        if(($this->display_only !== "Y"|| empty($this->display_only)) && (int)$this->add_crud !== 0){
            echo "<h2>".$this->main_header."</h2>";
        }
        
        echo "<div class='container-fluid my-2'>";

                echo"<div class='row'>";
 
                    echo"<div class='col-12 col-sm-6 mx-0 px-0'>";


                        if(isset($this->display_only) && $this->display_only == "Y"){

                            echo "
                            <div class='row'>
                                <div class='col-auto'>
                                    <h2 class='text-start my-2'>".$this->main_header."</h2>
                                </div>";    

                                if(isset($this->show_export) && $this->show_export == "Y" && (int)$this->export_crud == 1){
                                    echo"<div class='col-auto mx-0 px-0'>
                                            <button type='button' class='btn btn-primary m-2 fw-bold dropdown-toggle' data-bs-toggle='dropdown'>
                                                <span>
                                                    Print
                                                </span>
                                                <i class='bi bi-printer-fill pt-1'></i>
                                            </button>
                                        
                                        <ul class='dropdown-menu' aria-labelledby='dropdown_export'>
                                            <li class='dropdown-item' onclick='print_pdf()'><i class='fas fa-file-pdf'></i><span style='margin-left:7px;font-size:15px;font-family:arial'><b class='dd_text'>Pdf File</b></span></li>
                                            <li class='dropdown-item' onclick='print_txt()'><i class='fas fa-file-alt'></i><span style='margin-left:7px;font-size:15px;font-family:arial'><b class='dd_text'>Txt File</b></span></li>
                                        </ul>                            
                                    </div>";        
                                }
        
                            echo"</div>";
                        }

                        if($this->display_only !== "Y"|| empty($this->display_only)){


                            if((int)$this->add_crud == 1){
                                if(isset($this->customize_function_name) && !empty($this->customize_function_name)){
                                    echo "<button type='button' class='btn btn-success my-2 me-auto fw-bold' value='Add Record' onclick=\"".$this->customize_function_name."('openInsert')\">
                                        <span style='font-weight:bold'>
                                            Add Record
                                        </span>
                                        <i class='fas fa-plus' style='margin-left: 3px'></i>
                                    </button>";
                                }else{

                                    echo "<button type='button' class='btn btn-success my-2 me-auto fw-bold' value='Add Record' onclick=\"ajaxFunc('openInsert')\">
                                        <span style='font-weight:bold'>
                                            Add Record
                                        </span>
                                        <i class='fas fa-plus' style='margin-left: 3px'></i>
                                    </button>";
                        
                                }
                            }else{
                                echo "
                                <div class='row'>
                                    <div class='col-auto'>
                                        <h2 class='text-start my-2'>".$this->main_header."</h2>
                                    </div>";
                            }


                            if(isset($this->show_export) && $this->show_export == "Y" && (int)$this->export_crud == 1){

                                if((int)$this->add_crud == 0){
                                    echo"<div class='col-auto mx-0 px-0'>";
                                }
                                echo"<button type='button' class='btn btn-primary m-2 fw-bold dropdown-toggle' data-bs-toggle='dropdown'>
                                    <span>
                                        Print
                                    </span>
                                    <i class='bi bi-printer-fill pt-1'></i>
                                </button>
                                    
                                <ul class='dropdown-menu' aria-labelledby='dropdown_export'>
                                    <li class='dropdown-item' onclick='print_pdf()'><i class='fas fa-file-pdf'></i><span style='margin-left:7px;font-size:15px;font-family:arial'><b class='dd_text'>Pdf File</b></span></li>
                                    <li class='dropdown-item' onclick='print_txt()'><i class='fas fa-file-alt'></i><span style='margin-left:7px;font-size:15px;font-family:arial'><b class='dd_text'>Txt File</b></span></li>
                                </ul>";     
                                
                                if((int)$this->add_crud == 0){
                                    echo"</div>";
                                }
                            }

                            if((int)$this->add_crud == 0){
                                echo "</div>";
                            }
                            
                        }

                    echo"</div>";

                    $xsearch_counter = 0;

                    echo"<div class='col-12 col-sm-6 mx-0 px-0 d-flex flex-nowrap justify-content-sm-end div_search'>";

                        if(isset($this->show_search) && $this->show_search == "Y" && (int)$this->view_crud == 1){

                            echo "<select class='form-select ml-0 my-2' name='search_dd' style='width:auto;margin-right:5px' id='search_dd' onchange='onchange_search_dd(this)' hidden-value=''>";

                                foreach($this->field_name_dis as $field_name_search_key => $field_name_search_value){

                                    $fieldheader_search_hidden  = $this->field_header_dis[$field_name_search_key];
                                    $field_name_search_hidden   = $this->field_name_dis[$field_name_search_key];
                                    $field_type_search_hidden   = $this->field_type_dis[$field_name_search_key];

                                    if($xsearch_counter == 0){
                                        $field_type_search_hidden_first   = $this->field_type_dis[$field_name_search_key];

                                        //for dropdown
                                        if(isset($this->field_dropdown_field_name_dis[$field_name_search_key])){
                                            $dd_field_name_value_dis_first = $this->field_dropdown_field_name_value_dis[$field_name_search_key];
                                            $dd_field_name_dis_first  = $this->field_dropdown_field_name_dis[$field_name_search_key];
                                            $dd_tablename_dis_first        = $this->field_dropdown_tablename_dis[$field_name_search_key]; 
                                        }else{
                                            $dd_field_name_value_dis_first = '';
                                            $dd_field_name_dis_first  = '';
                                            $dd_tablename_dis_first        = ''; 
                                        }
                                        
                                    }

                                    if(isset($this->field_dropdown_field_name_value_dis[$field_name_search_key])){
                                        $field_dropdown_field_name_value_dis = $this->field_dropdown_field_name_value_dis[$field_name_search_key];
                                    }else{
                                        $field_dropdown_field_name_value_dis = '';
                                    }

                                    if(isset($this->field_dropdown_field_name_dis[$field_name_search_key])){
                                        $field_dropdown_field_name_dis  = $this->field_dropdown_field_name_dis[$field_name_search_key];
                                    }else{
                                        $field_dropdown_field_name_dis = '';
                                    }
                                    
                                    if(isset($this->field_dropdown_tablename_dis[$field_name_search_key])){
                                        $field_dropdown_tablename_dis        = $this->field_dropdown_tablename_dis[$field_name_search_key]; 
                                    }else{
                                        $field_dropdown_tablename_dis='';
                                    }

                                    if($field_type_search_hidden == "checkbox"){
                                        continue;
                                    }

                                    if(!empty($field_dropdown_field_name_dis) && 
                                        !empty($field_dropdown_tablename_dis) 
                                    ){
                                        echo "<option 
                                            value='".$field_name_search_hidden."' 
                                            data-type-search='".$field_type_search_hidden."' 
                                            data-field_name_search='".$field_dropdown_field_name_dis."'
                                            data-field_name_value_search='".$field_dropdown_field_name_value_dis."'
                                            data-tablename_search='".$field_dropdown_tablename_dis."'";
                                            echo ">".$fieldheader_search_hidden."</option>";
                                            
                                    }else{
                                        echo "<option 
                                            value='".$field_name_search_hidden."' 
                                            data-type-search='".$field_type_search_hidden."'";
                                            echo ">".$fieldheader_search_hidden."</option>";
                                    }

                                    if($xsearch_counter == 0){
                                        $field_type_search = $field_type_search_hidden;
                                    }

                                    $xsearch_counter++;
                                }

                            echo "</select>";
                
                            if($field_type_search == "date"){

                                echo "<span id='search_input' style='width:auto'>";
                                    echo"<div class='input-group rounded my-2 ms-0 flex-nowrap'>";
                                    
                                        echo "<div class='border border-dark border-2 rounded-start clearable-input' style='padding-right:0px'>";
                                            echo "<input 
                                                    type='text' 
                                                    name='search_text_input' 
                                                    id='search_date_input' 
                                                    class='form-control date_picker search_text_input' 
                                                    data-type='".$field_type_search."'
                                                    autocomplete='off'
                                                    date_picker_recipient='".$fieldheader_search_hidden."_dp_dd'
                                                    onkeypress='return check_enter(event)'
                                                    readonly>";
                                        echo "</div>";

                                        echo "<div class='input-group-btn bg-white rounded-end border border-dark border-2 tabbable search_maintable_btn'  tabindex='0' onkeypress='return check_enter(event)'>
                                                <span class='btn btn-default' onclick='page_click(\"search\")'>
                                                    <i class='fas fa-search'></i>
                                                </span>";
                                        echo "</div>";
                                    echo"</div>";
                                echo "</span>";     
                            }
                            else if($field_type_search == "checkbox"){
                                
                            }else if($field_type_search == "dropdown_custom" || $field_type_search == "dropdown_normal"){
                                echo "<span id='search_input' class='overflow-visible'  style='width:auto'>";
                                    echo"<div class='input-group rounded my-2 ms-0 overflow-visible'>";
                                        echo "<input 
                                                type='text' 
                                                name='search_text_input' 
                                                class='form-control border border-dark border-2 search_text_input' 
                                                data-type='".$field_type_search."'
                                                autocomplete='off'
                                                onkeypress='return check_enter(event)'>";
                                        echo "<div class='input-group-btn bg-white rounded-end border border-dark border-2 d-flex justify-content-center tabbable search_maintable_btn'  tabindex='0' onkeypress='return check_enter(event)'>
                                                <span class='btn btn-default' onclick='page_click(\"search\")'>
                                                    <i class='fas fa-search'></i>
                                                </span>";
                                        echo "</div>";
                                    echo"</div>";
                                echo "</span>";

                            }else{
                                echo "<span id='search_input' style='width:auto'>";
                                    echo"<div class='input-group rounded my-2 ms-0 overflow-visible'>";
                                        echo "<input
                                                type='text' 
                                                name='search_text_input' 
                                                class='form-control border border-dark border-2 search_text_input' 
                                                data-type='".$field_type_search."'
                                                autocomplete='off'
                                                onkeypress='return check_enter(event)'>";
                                        echo "<div class='input-group-btn bg-white rounded-end border border-dark border-2 d-flex justify-content-center tabbable search_maintable_btn' tabindex='0' onkeypress='return check_enter(event)'>
                                                    <span class='btn btn-default' onclick='page_click(\"search\")'>
                                                        <i class='fas fa-search'></i>
                                                    </span>";
                                        echo "</div>";
                                    echo"</div>";
                                echo "</span>";    
                            }

                            echo"<input type='hidden' value='".$field_type_search."' name='searchtype_hidden' id='searchtype_hidden'>";
                            echo"<input type='hidden' value='".$this->show_pager."' name='searchcheck_hidden' id='searchcheck_hidden'>";

                            echo"<input type='hidden' value='".$dd_field_name_value_dis_first."' name='dd_field_name_value_search' id='dd_field_name_value_search'>";
                            echo"<input type='hidden' value='".$dd_field_name_dis_first."' name='dd_field_name_search' id='dd_field_name_search'>";
                            echo"<input type='hidden' value='".$dd_tablename_dis_first."' name='dd_tablename_search' id='dd_tablename_search'>";
                            
                        }else{
                            $field_type_search_hidden_first = '';
                        }

                    echo"</div>";

                echo"</div>";

        echo "</div>";

        if((int)$this->view_crud == 1){
            echo "<table class='table table-striped shadow rounded' id='data_table' style='border-radius:.75rem!important;'>";

            echo "<thead style='border-bottom:2px solid black;'>";
                echo"<tr>";

            //loop thorugh fields to get selected values
                // foreach($this->field_header_dis  as $key_field_header_dis => $value_field_header_dis){
                //     echo "<th scope='col' class='align-middle'>";
                //         echo $value_field_header_dis;
                //     echo "</th>";
                // }

                foreach($this->field_name_dis  as $key_field_name_key => $value_field_header_value){

                    $field_header = $this->field_header_dis[$key_field_name_key];
                    $field_type = $this->field_type_dis[$key_field_name_key];
                    if(isset($this->field_decimal_place_dis[$key_field_name_key])){
                        $field_decimal = $this->field_decimal_place_dis[$key_field_name_key];
                    }else{
                        $field_decimal = '';
                    }
                  
                    if(!empty($field_decimal)){
                        echo "<th scope='col' style='text-align:right'>";
                            echo $field_header;
                        echo "</th>";
                    }
                    else if($field_type == "checkbox"){
                        echo "<th scope='col' class='text-center'>";
                            echo $field_header;
                        echo "</th>";
                    }else{
                        echo "<th scope='col' class='align-middle'>";
                            echo $field_header;
                        echo "</th>";
                    }


                }

                if(($this->display_only !== "Y"|| empty($this->display_only)) && ((int)$this->edit_crud == 1 || (int)$this->delete_crud == 1)){
                    echo "<th scope='col' class='text-center align-middle'>";
                        echo "Action";
                    echo "</th>";
                }

                echo "</tr>";
            echo "</thead>";

            echo "<tbody id='tbody_main'>";
            echo "</tbody>";

            echo "<tbody id='tbody_main_mobile'>";
            echo "</tbody>";

            echo "</table>";
        }
        //bottom pager display
        if(isset($this->show_pager) && $this->show_pager == "Y" && (int)$this->view_crud == 1){
        
            echo "<nav aria-label='Page navigation' id='pager' style='font-size:14px;text-shadow: 0 0 0 #0066ff;'>
                <ul class='pagination'> 
                    <li class='page-item' onclick=\"page_click('first_p')\">
                        <span class='page-link' aria-label='Previous' style='display:flex;justify-content:center;align-items:center;width:60px;height:3em;width:3em'>
                            <span aria-hidden='true'>&laquo;</span>
                        </span>
                    </li>

                    <li class='page-item' onclick=\"page_click('previous_p')\">
                        <span class='page-link' id='previous_pager' style='display:flex;align-items:center;justify-content:center;height:3em;width:7em'>
                            Previous
                        </span>
                    </li>

                    <input type='text' style='width:60px;text-align:center;font-weight:bold' name='txt_pager_pageno' id='txt_pager_pageno' disabled>

                    <li class='page-item' style='height:3em;' onclick=\"page_click('next_p')\">
                        <span class='page-link' id='next_pager' style='display:flex;justify-content:center;align-items:center;height:3em;width:7em'>
                        Next
                        </span>
                    </li>

                    <li class='page-item' onclick=\"page_click('last_p')\">
                        <span class='page-link' aria-label='Next' style='display:flex;justify-content:center;align-items:center;height:3em;width:3em;'>
                            <span aria-hidden='true' >&raquo;</span>
                        </span>
                    </li>
                    </ul>
            </nav>";

        }else{
            echo "<input type='hidden' name='txt_pager_pageno' id='txt_pager_pageno' disabled>";
        }

            echo"<input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'>";
            echo"<input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'>";

            echo"<table style='display:none' id='display_data_hidden'>";
                echo"<tr>";
                    echo"<td>";

                    //WHAT FIELDS YOU WILL DISPLAY AND THE SETTINGS
                    foreach($this->field_name_dis as $field_name_dis_key => $field_name_dis_value){

                        $fieldheader_dis_hidden  = $this->field_header_dis[$field_name_dis_key];
                        $field_name_dis_hidden    = $this->field_name_dis[$field_name_dis_key];
                        $field_type_dis_hidden   = $this->field_type_dis[$field_name_dis_key];

                        if(isset($this->field_decimal_place_dis[$field_name_dis_key])){
                            $field_decimal_place_dis_hidden   = $this->field_decimal_place_dis[$field_name_dis_key];
                        }
                        else{
                            $field_decimal_place_dis_hidden   = '';
                        }

                        if(isset($this->field_is_required[$field_name_dis_key])){
                            $is_required_dis_hidden  = $this->field_is_required[$field_name_dis_key];
                        }else{
                            $is_required_dis_hidden = '';
                        }

                        if(isset($this->field_is_unique[$field_name_dis_key])){
                            $is_unique_dis_hidden    = $this->field_is_unique[$field_name_dis_key];
                        }else{
                            $is_unique_dis_hidden    = '';    
                        }
                        
                        if(isset($this->field_font_weight_dis[$field_name_dis_key])){
                            $field_font_weight_dis   = $this->field_font_weight_dis[$field_name_dis_key];
                        }else{
                            $field_font_weight_dis   = '';
                        }


                        
        
                        if($field_type_dis_hidden == "text" ||
                            $field_type_dis_hidden == "number" ||
                            $field_type_dis_hidden == "textarea" ||
                            $field_type_dis_hidden == "date" ||
                            $field_type_dis_hidden == "checkbox"
                        ){

                            echo "<input 
                                type='hidden'
                                id='".$field_name_dis_hidden."_displayData'
                                name='fields[".$field_name_dis_hidden."_displayData][fname]' 
                                data-field-type='".$field_type_dis_hidden."'
                                data-field-fw ='".$field_font_weight_dis."'
                                data-field-header ='".$fieldheader_dis_hidden."'
                                data-field-decimal-place ='".$field_decimal_place_dis_hidden."'
                                value='".$field_name_dis_hidden."'>";

                                echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][fheader]' 
                                value='".$fieldheader_dis_hidden."'
                                >";

                                echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][ftype]' 
                                value='".$field_type_dis_hidden."'
                                >";

                                echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][fdecimal]' 
                                value='".$field_decimal_place_dis_hidden."'
                                >";


                            echo"<input 
                                type='hidden' 
                                name='field_type_hidden_dis' 
                                class='field_type_hidden_dis' 
                                value='".$field_type_dis_hidden."'
                                hidden_id_dis='".$field_name_dis_hidden."'
                                >";
                        }

                        else if($field_type_dis_hidden == "dropdown_custom"){

                            $dropdown_field_name_value_dis = (isset($this->field_dropdown_field_name_value_dis[$field_name_dis_key]))?($this->field_dropdown_field_name_value_dis[$field_name_dis_key]) : ""; 
                            $dropdown_field_name_dis       = $this->field_dropdown_field_name_dis[$field_name_dis_key];
                            $dropdown_tablename_dis       = $this->field_dropdown_tablename_dis[$field_name_dis_key];

                            echo "<input 
                                    type='hidden'
                                    id='".$field_name_dis_hidden."_displayData'
                                    name='fields[".$field_name_dis_hidden."_displayData][fname]' 
                                    data-field-type='".$field_type_dis_hidden."'
                                    data-dd-field_name='".$dropdown_field_name_dis."'
                                    data-dd-field_name-value='".$dropdown_field_name_value_dis."'
                                    data-dd-tablename='".$dropdown_tablename_dis."'
                                    data-field-fw ='".$field_font_weight_dis."'
                                    data-field-header ='".$fieldheader_dis_hidden."'
                                    value='".$field_name_dis_hidden."'>";

                            echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][fheader]' 
                                value='".$fieldheader_dis_hidden."'
                                >";

                            echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][ftype]' 
                                value='".$field_type_dis_hidden."'
                                >";
                        
                            echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][f_dd_fieldname_val]' 
                                value='".$dropdown_field_name_value_dis."'
                                >";

                            echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][f_dd_fieldname]' 
                                value='".$dropdown_field_name_dis."'
                                >";

                            echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][f_dd_tablename]' 
                                value='".$dropdown_tablename_dis."'
                                >";

                            echo "<input 
                                type='hidden'
                                name='fields[".$field_name_dis_hidden."_displayData][fdecimal]' 
                                value='".$field_decimal_place_dis_hidden."'
                                >";

                            echo"<input 
                                    type='hidden' 
                                    name='field_type_hidden_dis' 
                                    class='field_type_hidden_dis' 
                                    value='".$field_type_dis_hidden."'
                                    hidden_id_dis='".$field_name_dis_hidden."'
                                >";
                        }
                    
                    
                    }
                    echo"</td>";
                echo"</tr>";
            echo"</table>";

        //HIDDEN 

        if((int)$this->view_crud == 1 && ((int)$this->edit_crud == 1 || (int)$this->delete_crud == 1)){
            $value_crud_count = "Y";
        }else{
            $value_crud_count = "N";
        }

        //for user acces counting crud
        echo "<input type='hidden' name='crud_count' id='crud_count' value='".$value_crud_count."'>";
        //check if search is in use
        echo "<input type='hidden' name='show_search' id='show_search' value='".$this->show_search."'>";

        //check if alert_delete exist
        echo "<input type='hidden' name='alert_delete' id='alert_delete' value='".$this->alert_del."'>";

        //pager html needed
        echo "<input type='hidden' name='pager_xlimit' id='pager_xlimit' value=".$this->pager_xlimit.">";
        echo "<input type='hidden' name='field_code_hidden' id='field_code_hidden' value=".$this->field_code.">";
        echo "<input type='hidden' name='field_code_init_hidden' id='field_code_init_hidden' value=".$this->field_code_init.">";

        //order by
        echo "<input type='hidden' name='table_order_field' id='table_order_field' value='".$this->table_order_by["field"]."'>";
        echo "<input type='hidden' name='table_order_type' id='table_order_type' value='".$this->table_order_by["type"]."'>";

        //table name
        echo "<input type='hidden' name='tablename_hidden' id='tablename_hidden' value='".$this->table."'>";
        echo "<input type='hidden' name='table_filter_field_hidden' id='table_filter_field_hidden' value='".$this->table_filter_field."'>";
        echo "<input type='hidden' name='table_filter_value_hidden' id='table_filter_value_hidden' value='".$this->table_filter_value."'>";


        //display only
        echo "<input type='hidden' name='display_only_hidden' id='display_only_hidden' value='".$this->display_only."'>";

        //get session recid
        echo "<input type='hidden' name='session_userid' id='session_userid' value='".$_SESSION['recid']."'>";

        //user activity log
        echo "<input type='hidden' name='ua_field1_hidden' id='ua_field1_hidden' value='".$this->ua_field1."'>";
        echo "<input type='hidden' name='ua_field2_hidden' id='ua_field2_hidden' value='".$this->ua_field2."'>";
        echo "<input type='hidden' name='main_header_hidden' id='main_header_hidden' value='".$this->main_header."'>";

        //customize
        echo "<input type='hidden' name='customize_function_hidden' id='customize_function_hidden' value='".$this->customize_function_name."'>";


        //to prevent document ready twice
        echo "<input type='hidden' name='xfirst_hidden' id='xfirst_hidden'>";

        //hidden value for search ipnut(this to make sure that user has searched)
        echo "<input type='hidden' name='search_hidden_value' id='search_hidden_value'>";

        //to get type that the user searched for
        echo "<input type='hidden' name='search_hidden_type' id='search_hidden_type' value='".$field_type_search_hidden_first."'>";
        //to get the dropdown after search
        echo "<input type='hidden' name='search_hidden_dd' id='search_hidden_dd'>";

        //to get type that the user searched for
        echo "<input type='hidden' name='first_load_hidden' id='first_load_hidden'>";

        //for search values
        echo "<input type='hidden' name='search_dd_field_hidden' id='search_dd_field_hidden'>";
        echo "<input type='hidden' name='search_dd_field_val_hidden' id='search_dd_field_val_hidden'>";
        echo "<input type='hidden' name='search_dd_table_hidden' id='search_dd_table_hidden'>";

        //for pdf and txt
        echo "<input type='hidden' name='exp_txt_hidden' id='exp_txt_hidden' value='".$this->exp_txt."'>";
        echo "<input type='hidden' name='exp_pdf_hidden' id='exp_pdf_hidden' value='".$this->exp_pdf."'>";
        echo "<input type='hidden' name='txt_output_type' id='txt_output_type'>";

        //making sure the person searched
        echo "<input type='hidden' name='search_determiner_hidden' id='search_determiner_hidden' value=\"";
        if(isset($_POST['search_determiner_hidden'])){ 
            echo $_POST['search_determiner_hidden'];
        }
        echo "\">";

        //custom buttons
        echo "<div id='custom_btn_div'>";
        foreach($this->btn_header as $btn_header_key => $btn_header_value){
            $btn_header   = $this->btn_header[$btn_header_key];
            $btn_color    = $this->btn_color[$btn_header_key];
            $btn_logo     = $this->btn_logo[$btn_header_key];
            $btn_function = $this->btn_function[$btn_header_key];

            $btn_header_esc = htmlspecialchars($btn_header, ENT_QUOTES, 'UTF-8');
            $btn_color_esc = htmlspecialchars($btn_color, ENT_QUOTES, 'UTF-8');
            $btn_logo_esc = htmlspecialchars($btn_logo, ENT_QUOTES, 'UTF-8');
            $btn_function_esc = htmlspecialchars($btn_function, ENT_QUOTES, 'UTF-8');

            echo "<input 
                   type='hidden' 
                   name='".$btn_header_esc."_btn' 
                   id='".$btn_header_esc."_btn' 
                   btn-header='".$btn_header_esc."' 
                   btn-color='".$btn_color_esc."'
                   btn-logo='".$btn_logo_esc."'
                   btn-function='".$btn_function_esc."'
                >";
        }
        echo "<div>";

    } 

    public function display_modal(){

        $select_db_syspar_class = "SELECT * FROM syspar";
        $stmt_syspar_class	= $this->link->prepare($select_db_syspar_class);
        $stmt_syspar_class->execute();
        $row_syspar_class = $stmt_syspar_class->fetch();

        //CRUD MODAL
        echo "<div class='modal fade' id='crudModal' tabindex='-1' aria-labelledby='crudModal_header_main' aria-hidden='true' data-backdrop='false'>";
            echo "<div class='modal-dialog'>";
                echo "<div class='modal-content'>";
                    echo "<div class='modal-header'>";
                        echo "<h5 class='modal-title' id='crudModal_header_main'><span id='crudModal_header'> </span>".$this->main_header."</h5>";
                        echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
                    echo "</div>";
                    echo "<div class='modal-body'>";

                        foreach($this->field_name_crud as $field_name_crud_key => $field_name_crud_value){

                            $fieldheader_crud    = $this->field_header_crud[$field_name_crud_key];
                            $field_name_crud     = $this->field_name_crud[$field_name_crud_key];

                            if(isset($this->field_is_required[$field_name_crud_key])){
                                $is_required    = $this->field_is_required[$field_name_crud_key];
                            }else{
                                $is_required    = '';
                            }
                            
                            if(isset($this->field_is_unique[$field_name_crud_key])){
                                $is_unique = $this->field_is_unique[$field_name_crud_key]; 
                            }else{
                                $is_unique = '';
                            }

                            $data_type = array();
                            $maxlength = '';

                            if(isset($_SESSION['db_dbname'])){
                                $db_check = $_SESSION['db_dbname'];
                            }else{
                                $db_check = db_init::$dbholder_db_name;
                            }

                            //NUMERIC_SCALE used only in decimal to get the maxlength for the tenths and hundreths decimal places
                            $select_db_datatype ="SELECT DATA_TYPE as 'data_type', 
                            CHARACTER_MAXIMUM_LENGTH as 'char_max_length',
                            NUMERIC_SCALE as 'num_scale',
                            NUMERIC_PRECISION as 'num_precision',
                            COLUMN_TYPE  as 'col_type' 
                            FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db_check."' AND TABLE_NAME = '".$this->table."' AND  COLUMN_NAME = ? LIMIT 1";
                            $stmt_datatype	= $this->link->prepare($select_db_datatype);
                            $stmt_datatype->execute(array($field_name_crud));
                            while($row_datatype = $stmt_datatype->fetch()){

                                $data_type[$field_name_crud] = $row_datatype["data_type"];

                                if($data_type[$field_name_crud] == 'varchar' || 
                                   $data_type[$field_name_crud] == 'char' || 
                                   $data_type[$field_name_crud] == 'text' || 
                                   $data_type[$field_name_crud] == 'tinytext' || 
                                   $data_type[$field_name_crud] == 'mediumtext' || 
                                   $data_type[$field_name_crud] == 'longtext'
                                ){
                                    $maxlength  = $row_datatype["char_max_length"];
                                }

                                else if($data_type[$field_name_crud] == 'int' ||
                                   $data_type[$field_name_crud] == 'tinyint' || 
                                   $data_type[$field_name_crud] == 'smallint' || 
                                   $data_type[$field_name_crud] == 'mediumint' || 
                                   $data_type[$field_name_crud] == 'bigint'
                                ){

                                    $col_type  = $row_datatype["col_type"];

                                    if($data_type[$field_name_crud] == 'int'){
                                        $col_type = str_replace("int(", "",$col_type);
                                    }else if($data_type[$field_name_crud] == 'tinyint'){
                                        $col_type = str_replace("tinyint(", "",$col_type);
                                    }else if($data_type[$field_name_crud] == 'smallint'){
                                        $col_type = str_replace("smallint(", "",$col_type);
                                    }else if($data_type[$field_name_crud] == 'mediumint'){
                                        $col_type = str_replace("mediumint(", "",$col_type);
                                    }else if($data_type[$field_name_crud] == 'bigint'){
                                        $col_type = str_replace("bigint(", "",$col_type);
                                    }

                                    $col_type = str_replace(")", "",$col_type);
                                    $num_scale  = -1;
                                    $num_pres   = $col_type;
                                }

                                else if($data_type[$field_name_crud] == 'double' || 
                                    $data_type[$field_name_crud] == 'float' || 
                                    $data_type[$field_name_crud] == 'decimal'
                                ){
                                    $num_scale   = $row_datatype["num_scale"];
                                    $num_pres   = $row_datatype["num_precision"];

                                    $diff = 3;
                                    if($num_scale !== null){
                                        $diff = $num_pres - $num_scale;
                                    }

                                    if($diff == 1 && (int)$num_scale!== 0){
                                        $num_pres-=2;
                                    }

                                    else if(($diff == 2 || $diff == 4) && (int)$num_scale!== 0){
                                        $num_pres-=1;
                                    }

                                    if($num_scale == '1'){
                                        $step_limit = '0.1';
                                        $num_pres-=2;
                                    }else if($num_scale == '2'){
                                        $step_limit = '0.01';
                                        $num_pres-=2;
                                    }else if($num_scale == '3'){
                                        $step_limit = '0.001';
                                        $num_pres-=2;
                                    }else if($num_scale == '4'){
                                        $step_limit = '0.0001';
                                        $num_pres-=2;
                                    }else if($num_scale == '5'){
                                        $step_limit = '0.00001';
                                        $num_pres-=2;
                                    }else if($num_scale == '6'){
                                        $step_limit = '0.000001';
                                        $num_pres-=2;
                                    }else if($num_scale == '7'){
                                        $step_limit = '0.0000001';
                                        $num_pres-=2;
                                    }else if($num_scale == '8'){
                                        $step_limit = '0.00000001';
                                        $num_pres-=2;
                                    }else{
                                        $step_limit = '0.01';
            
                                    }
                                }
                            }
                    
                            $field_type_crud     = $this->field_type_crud[$field_name_crud_key];

                            if($field_name_crud == $this->ua_field1){
                                echo "<input type='hidden' name='ua_field1_header_hidden' id='ua_field1_header_hidden' value='".$fieldheader_crud."'>";
                            }

                            $error_color_crud = "";

                            if($is_required == "Y"){
                                $error_color_crud = " border border-danger border-2";
                            }
                                           
                            if($field_type_crud == "text"){

                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";
                                        if($is_required == "Y"){
                                            echo "
                                                    <div class='container-fluid mx-0 px-0'>
                                                        <div class='row mx-0 px-0'>
                                                            <div class='col-6 mx-0 px-0'>
                                                                ".$fieldheader_crud."
                                                            </div>
                                                            
                                                            <div style='color:red' class='col-6 mx-0 px-0 d-flex justify-content-end'>
                                                                <i>*Required</i>
                                                            </div>
                                                        </div>
                                                        
                                                    </div> 
                                                 ";    
                                        }else{
                                            echo "<label> ".$fieldheader_crud."</label>";    
                                        }
                                        
                                        echo "<input 
                                            type='text' 
                                            name='".$field_name_crud."_crudModal' 
                                            id='".$field_name_crud."_crudModal'
                                            data-value='".$fieldheader_crud."_crudModal'
                                            data-is-required='".$is_required."_crudModal'
                                            data-is-unique='".$is_unique."_crudModal'
                                            data-field-type='".$field_type_crud."'
                                            class='form-control ".$error_color_crud."'
                                            maxlength='".$maxlength."'
                                            autocomplete='off'>";
                                    echo "</div>";
                                echo "</div>";

                                echo"<input 
                                      type='hidden' 
                                      name='field_type_hidden' 
                                      class='field_type_hidden' 
                                      value=".$field_type_crud."
                                      hidden_id_crud='".$field_name_crud."'
                                    >";
                            }
                            else if($field_type_crud == "textarea"){

                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";
                                            if($is_required == "Y"){
                                                echo "
                                                        <div class='container-fluid mx-0 px-0'>
                                                            <div class='row mx-0 px-0'>
                                                                <div class='col-6 mx-0 px-0'>
                                                                    ".$fieldheader_crud."
                                                                </div>
                                                                <div style='color:red' class='col-6 mx-0 px-0 d-flex justify-content-end'>
                                                                    <i>*Required</i>
                                                                </div>
                                                            </div>
                                                            
                                                        </div> 
                                                    ";    
                                            }else{
                                                echo "<label> ".$fieldheader_crud."</label>";    
                                            }
                                        echo "<textarea 
                                            type='text' 
                                            name='".$field_name_crud."_crudModal' 
                                            id='".$field_name_crud."_crudModal'
                                            data-value='".$fieldheader_crud."_crudModal'
                                            data-is-required='".$is_required."_crudModal'
                                            data-is-unique='".$is_unique."_crudModal'
                                            data-field-type='".$field_type_crud."'
                                            class='form-control ".$error_color_crud."' 
                                            maxlength='".$maxlength."'
                                            autocomplete='off'></textarea>";
                                    echo "</div>";
                                echo "</div>";

                                echo"<input 
                                        type='hidden' 
                                        name='field_type_hidden' 
                                        class='field_type_hidden' 
                                        value='".$field_type_crud."'
                                        hidden_id_crud='".$field_name_crud."'
                                    >";
                            }
                            else if($field_type_crud == "number"){

                                if(isset($this->field_numlimit_crud[$field_name_crud_key])){
                                    $field_numlimit_crud = $this->field_numlimit_crud[$field_name_crud_key];   
                                }else{
                                    $field_numlimit_crud = '';
                                }
                                
                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";
                                            if($is_required == "Y"){
                                                echo "
                                                        <div class='container-fluid mx-0 px-0'>
                                                            <div class='row mx-0 px-0'>
                                                                <div class='col-6 mx-0 px-0'>
                                                                    ".$fieldheader_crud."
                                                                </div>
                                                                <div style='color:red' class='col-6 mx-0 px-0 d-flex justify-content-end'>
                                                                    <i>*Required</i>
                                                                </div>
                                                            </div>
                                                        </div> 
                                                    ";    
                                            }else{
                                                echo "<label> ".$fieldheader_crud."</label>";    
                                            }

                                            if(isset($step_limit)){
                                                echo "<input
                                                name='".$field_name_crud."_crudModal' 
                                                id='".$field_name_crud."_crudModal'
                                                data-value='".$fieldheader_crud."_crudModal'
                                                data-is-required='".$is_required."_crudModal'
                                                data-is-unique='".$is_unique."_crudModal'
                                                data-field-type='".$field_type_crud."'
                                                data-num-limit='".$field_numlimit_crud."'
                                                class='form-control ".$error_color_crud."' 
                                                maxlength='".$maxlength."'
                                                step='".$step_limit."'
                                                type='number'
                                                autocomplete='off'
                                                oninput='checkNumbers(this, ".$num_pres." , ".$num_scale.",\"decimal\")'
                                                onkeypress='return invalidChars(event)'
                                                >";
                                            }else if(!isset($step_limit)){

                                                echo "<input 
                                                type='number'
                                                name='".$field_name_crud."_crudModal' 
                                                id='".$field_name_crud."_crudModal'
                                                data-value='".$fieldheader_crud."_crudModal'
                                                data-is-required='".$is_required."_crudModal'
                                                data-is-unique='".$is_unique."_crudModal'
                                                data-field-type='".$field_type_crud."'
                                                data-num-limit='".$field_numlimit_crud."'
                                                class='form-control ".$error_color_crud."' 
                                                maxlength='".$maxlength."'
                                                autocomplete='off'
                                                oninput='checkNumbers(this, ".$num_pres." , ".$num_scale.")'
                                                onkeypress='return onlyNumberKey(event)'
                                                >";
                                            }

                                    echo "</div>";
                                echo "</div>";

                                echo"<input 
                                        type='hidden' 
                                        name='field_type_hidden' 
                                        class='field_type_hidden' 
                                        value='".$field_type_crud."'
                                        hidden_id_crud='".$field_name_crud."'
                                    >";
                            } 
                            else if($field_type_crud == "date"){

                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";
            
                                        if($is_required == "Y"){
                                            echo "
                                                    <div class='container-fluid mx-0 px-0'>
                                                        <div class='row mx-0 px-0'>
                                                            <div class='col-6 mx-0 px-0'>
                                                                ".$fieldheader_crud."
                                                            </div>
                                                            <div style='color:red' class='col-6 mx-0 px-0 d-flex justify-content-end'>
                                                                <i>*Required</i>
                                                            </div>
                                                        </div>            
                                                    </div> 
                                                ";    
                                        }else{
                                            echo "<label> ".$fieldheader_crud."</label>";    
                                        }

                                        echo"<div class='clearable-input' style='width:100%'>";
                                            echo "<input 
                                                type='text' 
                                                name='".$field_name_crud."_crudModal' 
                                                id='".$field_name_crud."_crudModal'
                                                data-value='".$fieldheader_crud."_crudModal'
                                                data-is-required='".$is_required."_crudModal'
                                                data-is-unique='".$is_unique."_crudModal'
                                                class='form-control date_picker ".$error_color_crud."' 
                                                data-field-type='".$field_type_crud."'
                                                autocomplete='off'
                                                value='DATE'
                                                date_picker_recipient='".$field_name_crud."_inputs_dd'
                                                readonly>";
                                        echo"</div>";

                                    echo "</div>";
                                echo "</div>";

                                echo"<input 
                                        type='hidden' 
                                        name='field_type_hidden' 
                                        class='field_type_hidden' 
                                        value='".$field_type_crud."'
                                        hidden_id_crud='".$field_name_crud."'
                                    >";
                            }
                            else if($field_type_crud == "checkbox"){

                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";
                                        
                                        echo "<input 
                                            type='checkbox' 
                                            name='".$field_name_crud."_crudModal' 
                                            id='".$field_name_crud."_crudModal'
                                            data-value='".$fieldheader_crud."_crudModal'
                                            data-is-required='".$is_required."_crudModal'
                                            data-is-unique='".$is_unique."_crudModal'
                                            data-field-type='".$field_type_crud."'
                                            class='form-check-input' 
                                            autocomplete='off'>";
                                        echo "<label class='ms-2'> ".$fieldheader_crud."</label>";
                                    echo "</div>";
                                echo "</div>";

                                echo"<input 
                                        type='hidden' 
                                        name='field_type_hidden' 
                                        class='field_type_hidden' 
                                        value='".$field_type_crud."'
                                        hidden_id_crud='".$field_name_crud."'
                                    >";
                            }
                            else if($field_type_crud == "dropdown_custom"){

                                $dropdown_field_name_value_crud = (isset($this->field_dropdown_field_name_value_crud[$field_name_crud_key]))?($this->field_dropdown_field_name_value_crud[$field_name_crud_key]) : ""; 
                                $dropdown_field_name_crud       = $this->field_dropdown_field_name_crud[$field_name_crud_key];
                                $dropdown_tablename_crud       = $this->field_dropdown_tablename_crud[$field_name_crud_key];

                                // orderby
                                $field_dropdown_orderby_field_crud = (isset($this->field_dropdown_orderby_field_crud[$field_name_crud_key]))?($this->field_dropdown_orderby_field_crud[$field_name_crud_key]) : ""; 

                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";

                                        if($is_required == "Y"){
                                            echo "
                                                    <div class='container-fluid mx-0 px-0'>
                                                        <div class='row mx-0 px-0'>
                                                            <div class='col-6 mx-0 px-0'>
                                                                ".$fieldheader_crud."
                                                            </div>
                                                            <div style='color:red' class='col-6 mx-0 px-0 d-flex justify-content-end'>
                                                                <i>*Required</i>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                ";    
                                        }else{
                                            echo "<label> ".$fieldheader_crud."</label>";    
                                        }

                                        if($dropdown_field_name_value_crud == ""){
                                            $select_db_dd_crud ="SELECT ".$dropdown_field_name_crud." FROM ".$dropdown_tablename_crud;
                                        }else{
                                            $select_db_dd_crud ="SELECT ".$dropdown_field_name_crud.", ".$dropdown_field_name_value_crud." FROM ".$dropdown_tablename_crud;
                                            if ($field_dropdown_orderby_field_crud!=""){
                                                $select_db_dd_crud =$select_db_dd_crud . " ORDER BY ". $field_dropdown_orderby_field_crud;
                                            }
                                        }

                                        
                                        $stmt_dd_crud	= $this->link->prepare($select_db_dd_crud);
                                        $stmt_dd_crud->execute();

                                        echo "<select 
                                                class='form-select ".$error_color_crud." select_custom'
                                                name='".$field_name_crud."_crudModal' 
                                                id='".$field_name_crud."_crudModal'
                                                data-value='".$fieldheader_crud."_crudModal'
                                                data-is-required='".$is_required."_crudModal'
                                                data-is-unique='".$is_unique."_crudModal'
                                                data-field-type='".$field_type_crud."'>";

                                        while($rs_dd_crud = $stmt_dd_crud->fetch()){

                                            if($dropdown_field_name_value_crud == ''){
                                                echo "<option value='".$rs_dd_crud["".$dropdown_field_name_crud.""]."'>".$rs_dd_crud["".$dropdown_field_name_crud.""]."</option>";
                                            }else{
                                                echo "<option value='".$rs_dd_crud["".$dropdown_field_name_crud.""]."'>".$rs_dd_crud["".$dropdown_field_name_value_crud.""]."</option>";
                                            }
                                        }
                                        echo "</select>";
                                    echo "</div>";
                                echo "</div>";

                                echo"<input 
                                        type='hidden' 
                                        name='field_type_hidden' 
                                        class='field_type_hidden' 
                                        value='".$field_type_crud."'
                                        hidden_id_crud='".$field_name_crud."'
                                    >";
                            }
                            else if($field_type_crud == "dropdown_normal"){

                                $field_dropdown_list_crud_array = $this->field_dropdown_list_crud[$field_name_crud_key];

                                echo "<div class='row m-3' id='crudModal_values'>";
                                    echo "<div class='col-12'>";

                                        if($is_required == "Y"){
                                            echo "
                                                    <div class='container-fluid mx-0 px-0'>
                                                        <div class='row mx-0 px-0'>
                                                            <div class='col-6 mx-0 px-0'>
                                                                ".$fieldheader_crud."
                                                            </div>
                                                            <div style='color:red' class='col-6 mx-0 px-0 d-flex justify-content-end'>
                                                                <i>*Required</i>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                ";    
                                        }else{
                                            echo "<label> ".$fieldheader_crud."</label>";    
                                        }

                                            echo "<select 
                                            class='form-select ".$error_color_crud." select_normal'
                                            name='".$field_name_crud."_crudModal' 
                                            id='".$field_name_crud."_crudModal'
                                            data-value='".$fieldheader_crud."_crudModal'
                                            data-is-required='".$is_required."_crudModal'
                                            data-is-unique='".$is_unique."_crudModal'
                                            data-field-type='".$field_type_crud."'>";

                                            $selected_option   = $this->field_dropdown_selected_crud[$field_name_crud_key];

                                            foreach($field_dropdown_list_crud_array as $key => $value){
                                                $selected = "";

                                                if($selected_option == $value){
                                                    $selected = "selected";
                                                }
                                                echo"<option ".$selected.">".$value."</option>";
                                            }
                                            echo"</select>";

                                        echo"<input 
                                        type='hidden' 
                                        name='field_type_hidden' 
                                        class='field_type_hidden' 
                                        value='".$field_type_crud."'
                                        hidden_id_crud='".$field_name_crud."'
                                        >";

                                    echo "</div>";
                                echo "</div>";
                            }
                            
                        }
    
                        echo "<div class='row m-2'>";
                            echo "<div class='error_msg'></div>";
                        echo "</div>";

                        echo "<input type='hidden' name='recid_hidden' id='recid_hidden'>";
                        echo "<input type='hidden' name='ua_field1_hidden_modal' id='ua_field1_hidden_modal'>";

                    echo "</div>";
                    echo "<div class='modal-footer'>";
                        echo "<button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Close</button>";
                        echo "<div id='crudModal_btn'></div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

        //ALERT DELETE MODAL
        echo"<div class='modal fade' id='main_delete_modal' tabindex='-1'>
            <div class='modal-dialog'>
                <div class='modal-content'>";

                        if(!isset($this->alert_del_logo_dir) || $this->alert_del_logo_dir == "N"){
                            echo"<div class='modal-header'>
                                <h5 class='modal-title'>".$row_syspar_class['system_name']." Says: </h5>
                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                            </div>";
                        }else{
                            echo"<div class='modal-header'>
                                <h5 class='modal-title'> <img src='".$this->alert_del_logo_dir."' style='width:".$this->alert_del_logo_w.";height:".$this->alert_del_logo_h.";'> &nbsp;".$row_syspar_class['system_name']." Says: </h5>
                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                            </div>";
                        }
                    
                    echo"
                    <div class='modal-body'>
                        <p>Are you sure you want to delete?</p>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancel</button>
                        <span id='delete_modal_btn'>
                        </span>
                    </div>
                </div>
            </div>
        </div>";
    }

}

?>
