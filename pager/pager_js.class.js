$(document).ready(function(){

    var element_ajax = $("#search_dd").find('option:selected'); 
    var check_attr = $(element_ajax).attr("data-field_name_search"); 

    if(typeof check_attr !== 'undefined' && check_attr !== false){

        $("#search_dd_field_hidden").val(element_ajax.attr("data-field_name_search"));
        $("#search_dd_field_val_hidden").val(element_ajax.attr("data-field_name_value_search"));
        $("#search_dd_table_hidden").val(element_ajax.attr("data-tablename_search"));

    }

    page_click("first" , 'first_load');

});

function onchange_search_dd(option){

    //return//diables datepicker
    var element = $(option).find('option:selected'); 
    var value_dd_search = element.val();
    var search_data_type = element.attr("data-type-search"); 
    var hidden_value = $(".search_text_input").attr("hidden-value");

    if(search_data_type == "date"){

        var xhtml = `
        <div class='input-group rounded my-2 ms-0 flex-nowrap'>
            <div class='border border-dark border-2 rounded-start clearable-input' style='padding-right:0px;'>
                <input type='text' name='search_text_input' id='search_date_input' class='form-control date_picker search_text_input' data-type="${search_data_type}" autocomplete='off' hidden-value="${hidden_value}" date_picker_recipient="${value_dd_search}_dp_dd" readonly onkeypress='return check_enter(event)'>
            </div>
            <div class='input-group-btn bg-white rounded-end border border-dark border-2 tabbable search_maintable_btn' tabindex='0' onkeypress='return check_enter(event)'>
                <span class='btn btn-default' onclick='page_click(\"search\")'>
                    <i class='fas fa-search'></i>
                </span>
            </div>
        </div>`;

        $("#search_input").html(xhtml);
        $("#searchtype_hidden").val(search_data_type);

        $(".date_picker").datepicker({
            showAnim: "blind", 
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+0",
            inline: true,
            showOn: 'focus',
            showButtonPanel: true,
            closeText: 'Clear', // Text to show for "close" button
            onClose: function () {
                $(this).blur();
                var event = arguments.callee.caller.caller.arguments[0];
                var event_checker = false;
                if(event){
                    event_checker = event.hasOwnProperty('delegateTarget');
                }
                if(event_checker == true){
                    if ($(event.delegateTarget).hasClass('ui-datepicker-close')) {
                    $(this).val('');
                    }
                }
            }
        });


        $("[date_picker_target]").click(function(){
            
            var target_val = $(this).attr("date_picker_target");
            $("[date_picker_recipient="+target_val+"]").val('');
        });

    }
    else if(search_data_type == "dropdown_custom"){
        var xhtml = 
        `<div class='input-group rounded my-2 ms-0'>
            <input type='text' name='search_text_input' class='form-control border border-dark border-2 search_text_input' data-type="${search_data_type}" autocomplete='off' hidden-value="${hidden_value}" onkeypress='return check_enter(event)'>
            <div class='input-group-btn bg-white rounded-end border border-dark border-2 tabbable search_maintable_btn' tabindex='0' onkeypress='return check_enter(event)'>
                <span class='btn btn-default' onclick='page_click(\"search\")'>
                    <i class='fas fa-search'></i>
                </span>
            </div>
        </div>`;

        $("#search_input").html(xhtml);
        $("#searchtype_hidden").val(search_data_type);

    }
    else {
        var xhtml = 
        `<div class='input-group rounded my-2 ms-0'>
            <input type='text' name='search_text_input' class='form-control border border-dark border-2 search_text_input' data-type="${search_data_type}" autocomplete='off' hidden-value="${hidden_value}" onkeypress='return check_enter(event)'>
            <div class='input-group-btn bg-white rounded-end border border-dark border-2 tabbable search_maintable_btn' tabindex='0' onkeypress='return check_enter(event)'>
                <span class='btn btn-default' onclick='page_click(\"search\")'>
                    <i class='fas fa-search'></i>
                </span>
            </div>
        </div>`
        $("#search_input").html(xhtml);
        $("#searchtype_hidden").val(search_data_type);
    }
}


function ajaxFunc(event ,recid, custom_param){

    var userid = $("#session_userid").val();
    var alert_delete = $("#alert_delete").val();

    if(event == "delete" && alert_delete == "Y" && custom_param !== "modal"){
      $("#delete_modal_btn").html("<button type='button' style='width:70px' class='btn btn-primary' onclick=\"ajaxFunc('"+event+"',"+recid+", 'modal')\">Yes</button>");
      $("#main_delete_modal").modal('show');
      return;
    }

    if(event == "openInsert"){
        $('#crudModal').find(':input[type=text]').val('');  
        $('#crudModal').find(':input[type=number]').val('');  
        $('#crudModal').find('textarea').val('');  
        $('#crudModal').find(':input[type=checkbox]').prop('checked' , false);
        $('#crudModal').find('.select_custom').val($("[data-field-type=dropdown_custom] option:first").val());

        var selected_option_normal = $('.select_normal option:selected');

        if(selected_option_normal == "" && selected_option_normal == null){
            $('#crudModal').find('.select_normal').val($("[data-field-type=dropdown_normal] option:first").val());
        }
        
    }

    var tablename         = $("#tablename_hidden").val();
    var fieldcode         = $("#field_code_hidden").val();
    var fieldcode_init    = $("#field_code_init_hidden").val(); 
    var table_filter_field = $("#table_filter_field_hidden").val();
    var table_filter_value = $("#table_filter_value_hidden").val();

    if((typeof fieldcode_init === 'undefined' || fieldcode_init === null || fieldcode_init === '') &&
       (typeof fieldcode_init !== 'undefined' && fieldcode_init !== null && fieldcode_init !== '')){
        alert("Field Code Init cannot be empty");
        return;
    }

    //field header
    var xcounter_fields = 0;
    let xdata = {}; 

    xdata[xcounter_fields] = [];

    //user activity vairables
    var ua_field1 = $("#ua_field1_hidden").val();
    var ua_field2 = $("#ua_field2_hidden").val();
    var main_header = $("#main_header_hidden").val();
    var ua_field1_header_hidden = $("#ua_field1_header_hidden").val();
    var ua_field1_hidden_modal  = $("#ua_field1_hidden_modal").val();


    //check for custom delete bom is true
    var custom_delete_bom_hidden = $("#custom_delete_bom_hidden").val();

     $(".field_type_hidden").each(function(){

        var field_type = $(this).attr("value");
        var hidden_id   = $(this).attr("hidden_id_crud");

        //INPUT TEXT
        if(field_type == "text"){
            $("#crudModal input[id="+hidden_id+"_crudModal]").each(function(){

                xdata[xcounter_fields] = [
                    {"name" : $(this).attr("name")},
                    {"value" :$("#"+$(this).attr("name")).val()},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique")},
                    {"data-field-type":$(this).attr("data-field-type")}
                ]
                xcounter_fields++;
            }) 

        } 
        //TEXTAREA
        else if(field_type == "textarea"){

            $("#crudModal textarea[id="+hidden_id+"_crudModal]").each(function(){

                xdata[xcounter_fields] = [
                    {"name" : $(this).attr("name")},
                    {"value" :$("#"+$(this).attr("name")).val()},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique")},
                    {"data-field-type":$(this).attr("data-field-type")}
                ]
                xcounter_fields++;
            }) 
        }
        //INPUT NUMBER 
        else if(field_type == "number"){

            $("#crudModal input[id="+hidden_id+"_crudModal]").each(function(){

                xdata[xcounter_fields] = [
                    {"name" : $(this).attr("name")},
                    {"value" :$("#"+$(this).attr("name")).val()},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique")},
                    {"data-field-type":$(this).attr("data-field-type")},
                    {"data-num-limit":$(this).attr("data-num-limit")}
                ]


                xcounter_fields++;
            }) 
        }
        //INPUT DATE
        else if(field_type == "date"){
            $("#crudModal input[id="+hidden_id+"_crudModal]").each(function(){

                xdata[xcounter_fields] = [
                    {"name" : $(this).attr("name")},
                    {"value" :$("#"+$(this).attr("name")).val()},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique")},
                    {"data-field-type":$(this).attr("data-field-type")}
                ]
                xcounter_fields++;
            }) 

        }

        //INPUT CHECKBOX
        else if(field_type == "checkbox"){
            $("#crudModal input[id="+hidden_id+"_crudModal]").each(function(){
                if($(this).prop("checked") == true){
                    var check_value = 1;
                }else{
                    var check_value = 0 ;
                }
                xdata[xcounter_fields] = [

                    {"name" : $(this).attr("name")},
                    {"value" :check_value},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique")},
                    {"data-field-type":$(this).attr("data-field-type")},
                    {"data-field-chkbox-selected-only-crud":$(this).attr("data-field-chkbox-selected-only-crud")},
                    
                ]
                xcounter_fields++;
            }) 
        } 
        //SELECT DROPDOWN
        else if(field_type == "dropdown_custom"){
            $("#crudModal select[id="+hidden_id+"_crudModal]").each(function(){
                xdata[xcounter_fields] = [

                    {"name" : $(this).attr("name")},
                    {"value" :$("#"+$(this).attr("name")).val()},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique") },
                    {"data-field-type":$(this).attr("data-field-type")}
                ]
                xcounter_fields++;
            }) 

        }
        else if(field_type == "dropdown_normal"){
            $("#crudModal select[id="+hidden_id+"_crudModal]").each(function(){
                xdata[xcounter_fields] = [

                    {"name" : $(this).attr("name")},
                    {"value" :$("#"+$(this).attr("name")).val()},
                    {"data-value" :$(this).data("value")},
                    {"data-value-hidden" :$(this).attr("data-value-hidden")},
                    {"data-is-required" :$(this).attr("data-is-required")},
                    {"data-is-unique" :$(this).attr("data-is-unique")},
                    {"data-field-type":$(this).attr("data-field-type")}
                ]
                xcounter_fields++;
            }) 

        }

     });

    switch(event) {
        case "delete":
            var event_action = "delete";
            break;
        case "insert":
            var event_action = "insert";
            break;
        case "openInsert":
            $('#crudModal').find('input:text').val('');  
            $("#crudModal_header").html("Add ");
            $("#crudModal_btn").html("<button type='button' class='btn btn-primary' onclick=\"ajaxFunc('insert')\">Save</button>");
            $("#crudModal").modal("show");
            return;
            break;
        case "getEdit": 
            $("#crudModal_btn").html("<button type='button' class='btn btn-primary' onclick=\"ajaxFunc('submitEdit')\">Save</button>");
            var event_action = "getEdit";
            
            break;
        case "submitEdit":
            var recid_edit = $("#recid_hidden").val();
            var event_action = "submitEdit";
    }      



    jQuery.ajax({    

        data:{
            //user activity log variables
            ua_field1_header_hidden:ua_field1_header_hidden,
            main_header:main_header,
            ua_field1:ua_field1,
            ua_field1_hidden_modal:ua_field1_hidden_modal,
            ua_field2:ua_field2,

            //to check if custom delete is true
            custom_delete_bom_hidden:custom_delete_bom_hidden,

            //standard vairables
            fieldcode_init :fieldcode_init,
            fieldcode : fieldcode,
            event_action:event_action,
            userid:userid,
            recid_edit:recid_edit,
            recid:recid,
            xdata:xdata,
            tablename:tablename,
            table_filter_field:table_filter_field,
            table_filter_value:table_filter_value,

        },
        dataType:"json",
        type:"post",
        url:"pager/pager_ajax.class.php", 

        success: function(xdata){ 

            if(xdata["status"] == 0){
                $(".error_msg").html("<div class='alert alert-danger' role='alert'>"+xdata["msg"]+"</div>")
            }
            else if(xdata["status"] == 1){

                if(event == "delete"){
                    $('#main_delete_modal').modal('hide');
                }
                
                $('#crudModal').modal('hide');
                page_click("same");

            }

            else if(xdata["status"] == "retEdit"){

                $("#crudModal_header").html("Edit ");

                var ua_field1_hidden = $("#ua_field1_hidden").val();
                $('#crudModal').find('input[type="checkbox"]').prop('checked', false);
                

                //loop thorugh edit data
                for (var key in xdata["retEdit"]) {

                    var field_type  = xdata["retEdit"][key].field_type;
                    var field_name  = xdata["retEdit"][key].field_name;
                    var field_value = xdata["retEdit"][key].field_value;
                    
                    if(ua_field1_hidden == field_name){
                        $("#ua_field1_hidden_modal").val(field_value);
                    }

                    if(field_type == "checkbox" && field_value == 1){
                        $("#"+field_name+"_crudModal").prop('checked', true);
                        $("#"+field_name+"_crudModal").attr("data-value-hidden" , field_value)
                    }
                    if(field_type !== "checkbox"){
                        $("#"+field_name+"_crudModal").val(field_value);
                        $("#"+field_name+"_crudModal").attr("data-value-hidden" , field_value)
                    }
                    
                }
                
                //hidden inputs
                $("#recid_hidden").val(xdata["retEdit"]["recid"]);
                
                //open modal
                $('#crudModal').modal('show');

            }
        },
        error: function (request, status, error) {
            alert(request.responseText);
        }
        
    })
}

function page_click(xbtn , xcheck ,xfieldnum){

    if(xbtn == "search"){
        var search_dd_hidden = $("#search_dd").val();
        $("#search_hidden_dd").val(search_dd_hidden);

        var search_data_type_hidden = $("#search_dd option:selected").attr("data-type-search");
        $("#search_hidden_type").val(search_data_type_hidden);
        $("#search_determiner_hidden").val("search");

        var element_ajax = $("#search_dd").find('option:selected'); 
        var check_attr = $(element_ajax).attr("data-field_name_search"); 

        if(typeof check_attr !== 'undefined' && check_attr !== false){

            $("#search_dd_field_hidden").val(element_ajax.attr("data-field_name_search"));
            $("#search_dd_field_val_hidden").val(element_ajax.attr("data-field_name_value_search"));
            $("#search_dd_table_hidden").val(element_ajax.attr("data-tablename_search"));

        }

    }

    var field_name = $("#search_dd_field_hidden").val();
    var field_name_value = $("#search_dd_field_val_hidden").val();
    var tablename_search = $("#search_dd_table_hidden").val();
    var search_determiner_hidden = $("#search_determiner_hidden").val();
    
    if(xcheck == "first_load" || search_determiner_hidden !== "search"){
        var first_load = "Y";
        $("#first_load_hidden").val("Y");
    }else{
        var first_load = "N";
        $("#first_load_hidden").val("");
    }

    //search variables
    var search_dd = $("#search_dd").val();
    var search_text_input = $(".search_text_input").val();
    var element_ajax = $("#search_dd").find('option:selected'); 
    var search_data_type = $("#search_hidden_type").val();
    
    //hidden input value
    var search_text_input_hidden = $(".search_text_input").attr("hidden-value");
    var search_text_input_hidden_dd = $("#search_dd").attr("hidden-value");


    //custom button
    xdata_btn = {};
    xcounter_fields_btn = 0;
    $("#custom_btn_div").children('input').each(function(){

        xdata_btn[xcounter_fields_btn] = [
            {"btn-header" : $(this).attr("btn-header")},
            {"btn-color" :$(this).attr("btn-color")},
            {"btn-logo" :$(this).attr("btn-logo")},
            {"btn-function" :$(this).attr("btn-function") },
            {"btn-parameter" :$(this).attr("btn-parameter") }
        ]
        xcounter_fields_btn++;
    }) 

    //check if search is in use or not
    var search_hidden = $("#show_search").val();

    //check if display only
    var display_only = $("#display_only_hidden").val();

    
    //check for custom delete bom is true
    var custom_delete_bom_hidden = $("#custom_delete_bom_hidden").val();

    //variables
    var tablename  = $("#tablename_hidden").val();
    var table_filter_field = $("#table_filter_field_hidden").val();
    var table_filter_value = $("#table_filter_value_hidden").val();
    var xcounter_fields = 0;
    //Pager limit
    var xlimit     = $("#pager_xlimit").val(); 
    let xfields = {}; 

    //number of fields
    var field_num = 0;

    //customize
    var cus_function_name = $("#customize_function_hidden").val();

    xfields[xcounter_fields] = [];
    xfields[xcounter_fields]["field"] = [];
    $(".field_type_hidden_dis").each(function(){

        var field_type = $(this).attr("value");
        var hidden_id =$(this).attr("hidden_id_dis");

        //INPUT TEXT
        if(field_type  == "text" || 
            field_type == "date" ||
            field_type == "number" ||
            field_type == "textarea" ||
            field_type == "checkbox"
        ){
            $("#display_data_hidden input[id="+hidden_id+"_displayData]").each(function(){

                xfields[xcounter_fields] = [
                    {"name" : $(this).attr("name")},
                    {"data-field-type" :$(this).attr("data-field-type")},
                    {"data-field-fw":$(this).attr("data-field-fw")},
                    {"data-field-header":$(this).attr("data-field-header")},
                    {"data-field-decimal-place":$(this).attr("data-field-decimal-place")},
                    {"data-field-chkbox-true-val":$(this).attr("data-field-chkbox-true-val")},
                    {"data-field-chkbox-false-val":$(this).attr("data-field-chkbox-false-val")},
                ]

            }) 

        } 
        //SELECT DROPDOWN
        else if(field_type == "dropdown_custom"){

            $("#display_data_hidden input[id="+hidden_id+"_displayData]").each(function(){

                xfields[xcounter_fields] = [

                    {"name" : $(this).attr("name")},
                    {"data-field-type":$(this).attr("data-field-type")},
                    {"data-field-fw":$(this).attr("data-field-fw")},
                    {"data-field-header":$(this).attr("data-field-header")},
                    {"data-dd-field_name":$(this).attr("data-dd-field_name")},
                    {"data-dd-field_name-value":$(this).attr("data-dd-field_name-value")},
                    {"data-dd-tablename":$(this).attr("data-dd-tablename")}
                   
                ]
            }) 

        }

        xcounter_fields++;
        field_num++;
    });


    field_num+=1;


    var crud_count = $("#crud_count").val();
    if(crud_count !== "Y"){
        field_num-=1;
    }

    //order by
    var table_order_field = $("#table_order_field").val();
    var table_order_type  = $("#table_order_type").val();


    var pageno = $("#txt_pager_pageno").val();

    jQuery.ajax({    

        data:{
              //search settings
              search_text_input:search_text_input,
              search_data_type:search_data_type,
              field_name:field_name,
              field_name_value:field_name_value,
              tablename_search:tablename_search,
              search_hidden:search_hidden,
              search_dd:search_dd,
              search_text_input_hidden:search_text_input_hidden,
              search_text_input_hidden_dd:search_text_input_hidden_dd,

              //custom button vairiables
              xdata_btn:xdata_btn,

              //check if display only
              display_only:display_only,

              //check if it is first load
              first_load:first_load,

              //check if delete is custom bom
              custom_delete_bom_hidden: custom_delete_bom_hidden,

              //no of fields
              field_num:field_num,

              //customize
              cus_function_name:cus_function_name,

              //pager vairables
              xlimit:xlimit,
              tablename:tablename,
              table_filter_field:table_filter_field,
              table_filter_value:table_filter_value,
              xfields:xfields,
              event_action:xbtn,
              table_order_field:table_order_field,
              table_order_type:table_order_type,
              pageno:pageno,
              },
        dataType:"json",
        type:"post",
        url:"pager/pager_ajax.pager.php", 

        success: function(xdata){ 
            
            //pager variables
            $("#txt_pager_totalrec").val(xdata["totalrec"]);
            $("#txt_pager_pageno").val(xdata["xpageno"]);
            $("#txt_pager_maxpage").val(xdata["maxpage"]);

            //search hidden values
            $(".search_text_input").attr("hidden-value" , xdata["hidden_value_search"]);
            $("#search_dd").attr("hidden-value" , xdata["hidden_value_search_dd"]);
            $("#search_hidden_value").val(xdata["hidden_value_search"]);

            //putting data to table
            $("#tbody_main").html(xdata["html"]);
            $("#tbody_main_mobile").html(xdata["html_mobile"]);

        }
    })
}


//export functions
function print_txt(){

    var exp_txt = $("#exp_txt_hidden").val();
    $("#txt_output_type").val('tab');
    var location_submit = "prog_pdf.php";
    if(exp_txt){
        location_submit = exp_txt;
    }

    document.forms.myforms.method="post";
    document.forms.myforms.target="_blank";
    document.forms.myforms.action=location_submit;
    document.forms.myforms.submit();
}
function print_pdf(){

    var exp_pdf = $("#exp_pdf_hidden").val();
    $("#txt_output_type").val('');
    var location_submit = "prog_pdf.php";
    if(exp_pdf){
        location_submit = exp_pdf;
    }

    document.forms.myforms.method="post";
    document.forms.myforms.target="_blank";
    document.forms.myforms.action=location_submit;
    document.forms.myforms.submit();
}

function alert_delete_bom(xmsg){
    alert(xmsg);
}

