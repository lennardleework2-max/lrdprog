<?php 
require "includes/main_header.php";

$select_db_useraccess1='SELECT * FROM users WHERE usercode=?';
$stmt_useraccess1	= $link->prepare($select_db_useraccess1);
$stmt_useraccess1->execute(array($_POST["usercode_hidden"]));
while($rs_useraccess1 = $stmt_useraccess1->fetch()){
    
    $username   = $rs_useraccess1['userdesc'];
    $full_name  = $rs_useraccess1['full_name'];
    $password   = $rs_useraccess1['password'];
    // loop here
}

?>

        <style>
    
        @media only screen and (min-width: 576px) {

            #btns_useraccess{
                display:flex;
                align-items:center;
                justify-content:flex-end;
            }
        }
        @media only screen and (max-width: 576px) {
            #btns_useraccess{
                justify-content:center;
            }
        }

        /* #main_chk_div{
            transform: scaleY(0);    
            transform-origin: top;
            transition: transform 1s ease;
        }

        .height_trans{
            transform: scaleY(1)!important;
        } */

        /* #tr_access_data{
            transform: scaleY(0);    
            transform-origin: top;
            transition: transform 0.75s ease;
        } */

        #main_chk_div{
            transform: scaleY(0);    
            transform-origin: top;
            transition: transform 0.65s ease;
        }

        .height_trans{
            transform: scaleY(1)!important;
        }

        /* #main_chk_div{
            max-height:0 !important;
            transition: max-height 0.25s ease-in;
        }
        .height_trans{
            max-height:1000px !important;
            transition: max-height 0.25s ease-in;
        } */



        </style>
        <form name='myforms' id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)"> 
            <table class='big_table'> 
                <tr colspan=1>
                    <td colspan=1 class='td_bl'>
                        <?php
                            require 'includes/main_menu.php';
                        ?>
                    </td>

                    <td colspan=1 style="height:100%" class='td_br' id='td_br'>

                        <div class="container-fluid my-4 px-0 h-100 w-100 d-flex align-items-center">
                         
                                <table class="container-fluid bg-white w-75 shadow rounded user_access_tbl" style="border-radius: 0.75rem!important;border-collapse:collapse">

                                    <tr class="m-1" style="border-bottom:3px solid #cccccc">
                                        <td> 
                                            <div class="m-2">
                                                <h2> User Access</h2>
                                            </div>
                                        </td>

                                        <td class="d-flex align-items-center justify-content-end">
                                            <div class="m-2 row">

                                                <div class="col-sm-6 col-12 d-flex justify-content-center justify-content-sm-end mx-0 px-0 my-1 my-sm-0">
                                                    <input type="button" class="btn btn-danger" style="width:100px;" value="Back" onclick="window.location.href='utl_users.php';">
                                                </div>

                                                <div class="col-sm-6 col-12 d-flex justify-content-center mx-0 px-0 my-1 my-sm-0">
                                                    <input type="button" class="btn btn-success ms-1" style="width:100px;" value="Save" onclick="save_click()">
                                                </div>
                                                
                                            </div>
                                        </td>
                                    </tr>

                                    <tr class="m-1 edit_row" style="border-bottom:3px solid #cccccc ">

                                        <td>
                                            <div class="m-3">
                                                <label for="">Username:</label>
                                                <input type="text" class="form-control" name="username_access" id="username_access" value="<?php echo $username;?>" readonly>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="m-3">
                                                <label for="">Full Name:</label>
                                                <input type="text" class="form-control" name="fullname_access" id="fullname_access" value="<?php echo $full_name;?>" autocomplete="off">
                                            </div>
                                        </td>
                                    
                                    </tr>

                                    <tr class="m-1" id="tr_access_data">
                                        <td id="main_chk_div" colspan="2">
                                        </td>
                                    
                                    </tr>
                            
                                </table>
                    
                        </div>
                    </td>
                </tr>
            </table>

            <div class='modal fade' id='alert_save' tabindex='-1'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'> <img src="<?php echo $logo_dir ;?>" style="<?php echo 'width:'.$logo_width.';height:'.$logo_height.';';?>">&nbsp;<?php echo $system_name;?> Says: </h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                    
                        <div class='modal-body'>
                            <b>Successfully Saved.</b>
                        </div>

                    </div>
               
                </div>
            </div>

            <!-- HIDDEN -->
            <input type="hidden" name="event_action" id="event_action">
            <input type="hidden" name="usercode_access_hidden" id="usercode_access_hidden" value="<?php if(isset($_POST["usercode_hidden"])){echo $_POST["usercode_hidden"];}?>">

        </form>

        <script>


        $(document).ready(function(){
            
            var usercode = $("#usercode_access_hidden").val();
            var xdata = "usercode="+usercode;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"utl_user_access_ajax.php", 
                    success: function(xdata){

                        $("#main_chk_div").addClass("height_trans");
                        $("#main_chk_div").html("<div class='m-2 trans_div'>"+xdata["html"]+"</div>");

                        
                    }

            })
        });

        function changeAccess(){

            var usercode = $("#usercode_access_hidden").val();
            var xdata    = "usercode="+usercode

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"utl_user_access_ajax.php", 
                    success: function(xdata){  
                        $("#main_chk_div").hide().html("<div class='m-2'>"+xdata["html"]+"</div>").fadeIn(500);
                    }
                })
        }
        function checkall_sub(mensub){

            var is_checked = $(".main_"+mensub).is(':checked');
            if(is_checked == true){
                $('.sub_'+mensub).prop('checked', true);
                $('.crud_add_'+mensub).prop('checked', true);
                $('.crud_delete_'+mensub).prop('checked', true);
                $('.crud_view_'+mensub).prop('checked', true);
                $('.crud_edit_'+mensub).prop('checked', true);
                $('.crud_export_'+mensub).prop('checked', true);

                
                $('.sub_'+mensub+'2').prop('checked', true);
                $('.crud_add_'+mensub+'2').prop('checked', true);
                $('.crud_delete_'+mensub+'2').prop('checked', true);
                $('.crud_view_'+mensub+'2').prop('checked', true);
                $('.crud_edit_'+mensub+'2').prop('checked', true);
                $('.crud_export_'+mensub+'2').prop('checked', true);

            }else{
                $('.sub_'+mensub).prop('checked', false);
                $('.crud_add_'+mensub).prop('checked', false);
                $('.crud_delete_'+mensub).prop('checked', false);
                $('.crud_view_'+mensub).prop('checked', false);
                $('.crud_edit_'+mensub).prop('checked', false);
                $('.crud_export_'+mensub).prop('checked', false);
                                
                $('.sub_'+mensub+'2').prop('checked', false);
                $('.crud_add_'+mensub+'2').prop('checked', false);
                $('.crud_delete_'+mensub+'2').prop('checked', false);
                $('.crud_view_'+mensub+'2').prop('checked', false);
                $('.crud_edit_'+mensub+'2').prop('checked', false);
                $('.crud_export_'+mensub+'2').prop('checked', false);
            }
        }
        function checkall_sub_sub(mensub,mengrp){

            var x = 0;             
            var is_checked_sub = $(".sub_"+mengrp).is(':checked');
            var minus = $(".main_"+mensub).is(':checked');

            $(".sub_"+mengrp).each(function(){
               var is_checked_sub = $(this).is(':checked');
               if(is_checked_sub == true){
                    x++;
               }
            });

            if(x > 0){
                $(".main_"+mengrp).prop('checked', true);
            }else{

                $(".main_"+mengrp).prop('checked', false);
            }

            var is_checked = $(".main_"+mensub).is(':checked');

            if(is_checked == true){
                $('.sub_'+mensub).prop('checked', true);
                $('.crud_add_'+mensub).prop('checked', true);
                $('.crud_delete_'+mensub).prop('checked', true);
                $('.crud_view_'+mensub).prop('checked', true);
                $('.crud_edit_'+mensub).prop('checked', true);
                $('.crud_export_'+mensub).prop('checked', true);

            }else{
                $('.sub_'+mensub).prop('checked', false);
                $('.crud_add_'+mensub).prop('checked', false);
                $('.crud_delete_'+mensub).prop('checked', false);
                $('.crud_view_'+mensub).prop('checked', false);
                $('.crud_edit_'+mensub).prop('checked', false);
                $('.crud_export_'+mensub).prop('checked', false);
            }
        }
        function checkall_nosub(mencap){

            var is_checked = $(".main_"+mencap).is(':checked');
            if(is_checked == true){

                $('.crud_add_'+mencap).prop('checked', true);
                $('.crud_edit_'+mencap).prop('checked', true);
                $('.crud_delete_'+mencap).prop('checked', true);
                $('.crud_view_'+mencap).prop('checked', true);
                $('.crud_export_'+mencap).prop('checked', true);
            }else{

                $('.crud_add_'+mencap).prop('checked', false);
                $('.crud_edit_'+mencap).prop('checked', false);
                $('.crud_delete_'+mencap).prop('checked', false);
                $('.crud_view_'+mencap).prop('checked', false);
                $('.crud_export_'+mencap).prop('checked', false);
            }
        }
        function checksub(mengrp,mencap){

            var x = 0;             
            var is_checked_sub = $(".sub_"+mencap).is(':checked');

            $(".sub_"+mengrp).each(function(){
               var is_checked = $(this).is(':checked');
               if(is_checked == true){
                    x++;
               }
            });

            if(x>0){
                $('.main_'+mengrp).prop('checked', true);

            }else{
                $('.main_'+mengrp).prop('checked', false);
            }

            if(is_checked_sub == true){
                $('.crud_add_'+mencap).prop('checked', true);
                $('.crud_edit_'+mencap).prop('checked', true);
                $('.crud_delete_'+mencap).prop('checked', true);
                $('.crud_view_'+mencap).prop('checked', true);
                $('.crud_export_'+mencap).prop('checked', true);
            }else{
                $('.crud_add_'+mencap).prop('checked', false);
                $('.crud_edit_'+mencap).prop('checked', false);
                $('.crud_delete_'+mencap).prop('checked', false);
                $('.crud_view_'+mencap).prop('checked', false);
                $('.crud_export_'+mencap).prop('checked', false);
            }


        }
        function checksub_sub(mengrp,mencap,mensub){

            var x = 0;             
            var is_checked_sub = $(".sub_"+mencap).is(':checked');

            $(".sub_"+mengrp).each(function(){
            var is_checked = $(this).is(':checked');
            if(is_checked == true){
                    x++;
            }
            });

            if(x>0){
                $('.main_'+mengrp).prop('checked', true);

            }else{
                $('.main_'+mengrp).prop('checked', false);
            }

            
            if(is_checked_sub == true){
                $('.crud_add_'+mencap).prop('checked', true);
                $('.crud_edit_'+mencap).prop('checked', true);
                $('.crud_delete_'+mencap).prop('checked', true);
                $('.crud_view_'+mencap).prop('checked', true);
                $('.crud_export_'+mencap).prop('checked', true);
            }else{
                $('.crud_add_'+mencap).prop('checked', false);
                $('.crud_edit_'+mencap).prop('checked', false);
                $('.crud_delete_'+mencap).prop('checked', false);
                $('.crud_view_'+mencap).prop('checked', false);
                $('.crud_export_'+mencap).prop('checked', false);
            }


            var x = 0;             
            var is_checked_mengrp = $(".sub_"+mencap).is(':checked');

            $(".sub_"+mensub).each(function(){
            var is_checked = $(this).is(':checked');
                if(is_checked == true){
                        x++;
                }
            });

            if(x>0){
                $('.main_'+mensub).prop('checked', true);

            }else{
                $('.main_'+mensub).prop('checked', false);
            }

            if(is_checked_mengrp == true){
                $('.crud_add_'+mencap).prop('checked', true);
                $('.crud_edit_'+mencap).prop('checked', true);
                $('.crud_delete_'+mencap).prop('checked', true);
                $('.crud_view_'+mencap).prop('checked', true);
                $('.crud_export_'+mencap).prop('checked', true);
            }else{
                $('.crud_add_'+mencap).prop('checked', false);
                $('.crud_edit_'+mencap).prop('checked', false);
                $('.crud_delete_'+mencap).prop('checked', false);
                $('.crud_view_'+mencap).prop('checked', false);
                $('.crud_export_'+mencap).prop('checked', false);
            }



        }
        function checkcrud(level, crud, mencap,mengrp){

            var x=0;
            var is_checked_delete = $(".crud_delete_"+mencap).is(':checked');
            var is_checked_edit = $(".crud_edit_"+mencap).is(':checked');
            var is_checked_view = $(".crud_view_"+mencap).is(':checked');

            if((crud == "edit" || crud == "delete") &&( is_checked_delete == true || is_checked_edit == true)){
                $('.crud_view_'+mencap).prop('checked', true);
            }


            if(crud == "view" && is_checked_view !== true){
                $('.crud_delete_'+mencap).prop('checked', false);
                $('.crud_edit_'+mencap).prop('checked', false);
            }

            $(".crud_"+mencap).each(function(){
               var is_checked = $(this).is(':checked');
               if(is_checked == true){
                    x++;
               }
            });

            if(x>0){
                
                if(level == "sub"){
                    $('.sub_'+mencap).prop('checked', true);
                    $('.main_'+mengrp).prop('checked', true);
                }else{
                    $('.main_'+mencap).prop('checked', true);
                }

            }else{

                if(level == "sub"){

                    $('.sub_'+mencap).prop('checked', false);

                    var sub_x = 0;

                    $(".sub_"+mengrp).each(function(){
                        var is_checked = $(this).is(':checked');
                        if(is_checked == true){
                            sub_x++;
                        }
                    });

                    if(sub_x>0){
                        $('.main_'+mengrp).prop('checked', true);
                    }else{
                        $('.main_'+mengrp).prop('checked', false);
                    }
                    
                }else{
                    $('.main_'+mencap).prop('checked', false);
                }


            }   
        }
        function checkcrud_sub(level, crud, mencap,mengrp,mensub){

            var x=0;
            var is_checked_delete = $(".crud_delete_"+mencap).is(':checked');
            var is_checked_edit = $(".crud_edit_"+mencap).is(':checked');
            var is_checked_view = $(".crud_view_"+mencap).is(':checked');

            if((crud == "edit" || crud == "delete") &&( is_checked_delete == true || is_checked_edit == true)){
                $('.crud_view_'+mencap).prop('checked', true);
            }

            if(crud == "view" && is_checked_view !== true){
                $('.crud_delete_'+mencap).prop('checked', false);
                $('.crud_edit_'+mencap).prop('checked', false);
            }

            $(".crud_"+mencap).each(function(){
            var is_checked = $(this).is(':checked');
            if(is_checked == true){
                    x++;
            }
            });


            if(x>0){
                
                if(level == "sub"){
                    $('.sub_'+mencap).prop('checked', true);
                    $('.main_'+mengrp).prop('checked', true);

                }else{
                    $('.main_'+mencap).prop('checked', true);
                }

            }else{

                if(level == "sub"){

                    $('.sub_'+mencap).prop('checked', false);

                    var sub_x = 0;

                    $(".sub_"+mengrp).each(function(){
                        var is_checked = $(this).is(':checked');
                        if(is_checked == true){
                            sub_x++;
                        }
                    });

                    if(sub_x>0){
                        $('.main_'+mengrp).prop('checked', true);
                    }else{
                        $('.main_'+mengrp).prop('checked', false);
                    }
                    
                }else{
                    $('.main_'+mencap).prop('checked', false);
                }


            }   


            var x = 0;             
            var is_checked_sub = $(".sub_"+mensub).is(':checked');

            $(".sub_"+mensub).each(function(){
               var is_checked_sub = $(this).is(':checked');
               if(is_checked_sub == true){
                    x++;
               }
            });

            if(x > 0){
                $(".main_"+mensub).prop('checked', true);
            }else{

                $(".main_"+mensub).prop('checked', false);
            }
        }

        function save_click(){

            $("#event_action").val("save");
            var username_access = $("#username_access").val() == '';
            var password_access = $("#password_access").val() == '';

            if(username_access == true || password_access == true){
                alert("Please Fill Up The Form");
                return;
            }

            var usercode = $("#usercode_access_hidden").val();
            var fullname_access = $("#fullname_access").val();
            var xdata = $("#main_chk_div *").serialize()+"&usercode="+usercode+"&event_action=save"+"&fullname_access="+fullname_access;

            jQuery.ajax({    
                data:xdata,
                dataType:"json",
                type:"post",
                url:"utl_user_access_ajax_save.php", 
                    success: function(xdata){  

                        if(xdata["return"] == true){
                            window.location.href = "index.php";
                        }else{
                            $("#alert_save").modal("show");
                        }

                        
                    }
            })

    
        }

        
        </script>


<?php
    require "includes/main_footer.php";
?>

