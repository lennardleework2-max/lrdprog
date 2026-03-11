<?php
include "includes/main_header.php";
?>
        <form name='myforms' style="height:calc(100vh - 85px)">          
            <table class='big_table' style="height:100%"> 
                <tr colspan=1 style="height:100%">
                    <td colspan=1 class='td_bl'>
                        <?php
                        require 'includes/main_menu.php';
                        ?>
                    </td>

                    <td colspan=1 class="td_br" style="height:100%;">
                        <div class="container w-100 h-100">
                            <div class="row h-100 justify-content-center align-items-center">
                                <div class='col-10 col-lg-5 col-md-6 col-xs-6 col-s-6 col-xxl-4 col-xl-4 bg-light border border-light passwordForm my-2'>
                                    <div class="m-3 text-center login_form_header" style="display:flex;align-items:center;justify-content:center;font-size:30px">
                                        <div>
                                            Change Password
                                        </div>
                                         
                                        <div style='margin-bottom:5px'>
                                            <i class="fas fa-lock" style="font-size:20px;margin-left:10px"></i>
                                        </div>
                                    </div>
                                    <div class="m-3 form-group">
                                        <label class='form-label'style="color:black">Old Password</label>
                                        <input type='password' class='form-control user' name='old_password'id='old_password' autocomplete='off'>
                                    </div>

                                    <div class="m-3 form-group">
                                        <label class='form-label'style="color:black">New Password</label>
                                        <input type='password' class='form-control user' name='new_password'id='new_password' autocomplete='off'>
                                    </div>

                                    <div class="m-3 form-group">
                                        <label class='form-label' style="color:black">Repeat New Password</label>
                                        <input type='password' class='form-control pwd' name="new_password2" id='new_password2' autocomplete='off' onkeypress='return check_enter_pass(event)'>
                                    </div>

                                    <div class="m-3 form-group text-center">
                                        <div class='row'>
                                            <div class="col-md-12 col-lg-12">
                                                <input type='button' onclick="ajaxFunc('changePass')" class="btn mt-2 btn-dark btn-md button_login w-100" value="Change Password">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-danger text-center m-3"  style="display:none" id="error_div">
                                        <span class='error_msg' id='error_msg'></span>
                                    </div>

                                    <div class="alert alert-success text-center m-3"  style="display:none" id="success_div">
                                        <span class='success_msg' id='success_msg'></span>
                                    </div>


                                    <div class="m-3 form-group text-center">
                                        <div class='row'>
                                            <div class="col-12">
                                                <p class="my-auto">Insurance Program V5</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- HIDDEN -->
                                    <input type="hidden" name="username_hidden" id="username_hidden" value="<?php echo $userdesc; ?>">
                                    <input type="hidden" name="oldpass_hidden" id="oldpass_hidden" value="<?php echo $password; ?>">
                                </div>
                            </div>
                        </div>
                    </td>

                </tr>
            </table>

        </form> 



        <script>
            function ajaxFunc(event, recid){

                
                switch(event) {
                    case "changePass":
                        var xdata = $(".passwordForm *").serialize()+"&event_action=changePass";
                        break;
                }

                jQuery.ajax({    

                    data:xdata,
                    dataType:"json",
                    type:"post",
                    url:"utl_password_ajax.php", 

                    success: function(xdata){  

                        if(xdata["status"] == 0){
                            $("#success_div").css("display" , "none");
                            $("#error_div").css("display" , "revert");
                            $("#error_msg").html(xdata["msg"]);
                        }
                        else if(xdata["status"] == 1){

                            $("#old_password").val('');
                            $("#new_password").val('');
                            $("#new_password2").val('');
                            $("#oldpass_hidden").val(xdata['newpass']);

                            $("#error_div").css("display" , "none");
                            $("#success_div").css("display" , "revert");
                            $("#success_msg").html(xdata["msg"]);
                        }
                    }
                })
            }
            function check_enter_pass(xevent){
                if (xevent.keyCode === 13) {
                    ajaxFunc('changePass');
                }
            }

        </script>

<?php
include "includes/main_footer.php";
?>

