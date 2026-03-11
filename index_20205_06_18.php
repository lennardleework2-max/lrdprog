<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// Set session lifetime in seconds (e.g., 1 hour = 3600 seconds)
ini_set('session.gc_maxlifetime', 3600);
//session_set_cookie_params(3600);
session_start();

require_once("resources/db_init.php");
require_once("resources/lx2.pdodb.php");

if(isset(db_init::$dbmode_singledb) && db_init::$dbmode_singledb == 'N'){

    $system_name = '';

    $link = new PDO("mysql:dbname=".db_init::$syspar_db_name.";host=".db_init::$syspar_host."","".db_init::$syspar_username."","".db_init::$syspar_password."");
    $select_db_syspar='SELECT * FROM syspar';
    $stmt_syspar	= $link->prepare($select_db_syspar);
    $stmt_syspar->execute();
    while($rs_syspar = $stmt_syspar->fetch()){

        $system_name = $rs_syspar["system_name"];
    }

}else{
    $link = new PDO("mysql:dbname=".db_init::$dbholder_db_name.";host=".db_init::$dbholder_host."","".db_init::$dbholder_username."","".db_init::$dbholder_password."");
    $select_db_syspar='SELECT * FROM syspar';
    $stmt_syspar	= $link->prepare($select_db_syspar);
    $stmt_syspar->execute();
    while($rs_syspar = $stmt_syspar->fetch()){
        $landing_page = $rs_syspar["landing_page"];
        $system_name = $rs_syspar["system_name"];
    }
    if(empty($landing_page)){
        $landing_page = "";
    }

}






?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel='stylesheet' href='css/all.min.css' >
    <link rel="shortcut icon" href="images/logo_short.png">


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@100&display=swap" rel="stylesheet">

    <style>

    /* all tags */
    * {
        margin:0px;
        padding:0px;
    }

    body{
        background-color:#f2f2f2;    
        overflow-x: hidden;
    }

    i{
        font-size:1rem;
    }

    /*inputs css */
    #comp_code,#user:focus,#pwd:focus {
        outline:none
    }
    
    #span_lock:hover{
        opacity:0.5;
        cursor:pointer
    }


    .main_input_icon{
        font-size:15px;
        text-align:center;
        display:inline-block;  
    }


    .input_sub{
        width:90%;
        margin-left:5px;
        border-style:none;
        display:inline-block;    
        border:none;
    }

    .main_input{
        display:flex;
        align-items:center;
    }

    .program_txt{
        color:black;
        font-size:30px;
        font-family: 'Raleway', sans-serif;
        font-weight:bold;
    }



    html,body{
        min-height:100vh;
    }

    @media only screen and (max-width: 408px){
        img{
            max-width: 93%;
            width: 93%;
            max-height: 73px;
        }
    }
    </style>

    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/jquery-ui/jquery-ui.min.js"></script>
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>   
    <form method='post' action='logout_btn.php' target='_self' style='display:flex;align-items:center;justify-content:center;height:100vh' name='myform'>
  
        <table style="width:100%;">
            <tr>
                <td style='display:flex;justify-content:center;'>
                    <span class="program_txt" style="text-align:center"><?php echo $system_name;?> </span>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="row h-100 justify-content-center align-items-center">
                        <div class='col-10 col-sm-8 col-md-5 col-lg-5 col-xl-4 col-xxl-4 bg-light shadow-lg mb-2'>
                            <div class="m-3 text-center login_form_header">
                                <p style="font-weight:bold;font-size:25px;">Login</p>
                            </div>



                            <!-- If has multiple DBS -->
                            <?php  if(isset(db_init::$dbmode_singledb) && db_init::$dbmode_singledb == "N"):?>

                                <div class="m-3 form-group">
                                    <label class='form-label' style="color:black">Company Code:</label>
                                    <div class='form-control main_input'>
                                        <div class='main_input_icon'>
                                            <i class="fas fa-id-card-alt"></i>
                                        </div>
                                        <input type='text' name='comp_code'id='comp_code' class='input_sub' autocomplete='off'>
                                    </div>
                                </div>

                            <?php endif;?>

                            <div class="m-3 form-group">
                                <label class='form-label'style="color:black">Username:</label>
                                <div class='form-control main_input'>
                                    <div class='main_input_icon'>
                                        <i class="far fa-user"></i>
                                    </div>
       
                                    <input type='text' name='user' id='user' class="input_sub" autocomplete='off'>
                                </div>
                                
                            </div>

                            <div class="m-3 form-group">
                                <label class='form-label' style="color:black">Password: </label>

                                <div class='form-control main_input'>
                                    <span id='span_lock' class="main_input_icon" onclick="toggle_lock()" style='overflow: visible'>
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type='password' name="pwd" id='pwd' class="input_sub" autocomplete='off'>
                                </div>
                                
                            </div>

                            <div class="m-3 form-group text-center">
                                <div class='row'>
                                    <div class="col-md-12 col-lg-12">
                                        <input type='button' onclick='login()' class="btn mt-2 btn-dark btn-md w-100 fw-bold" id="btn_login "value="Login">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-danger text-center m-3"  style="display:none" id="div_msg" role="alert">
                                <span class='span_msg' id='span_msg'></span>
                            </div>



                            <!-- <div class="m-3 form-group text-center">
                                <div class='row'>
                                    <div class="col-12">
                                        <p class="my-auto"><?php echo $system_name; ?></p>
                                    </div>
                                </div>
                            </div> -->
                            <!-- 
                            <div class="m-3" style='display:flex;align-items:flex-end;justify-content:center'>
                                <center>
                                    <img src="images/logo_long.png" id="logo_top_login" style="height:40px;">
                                </center>
                            </div> -->
                        </div>
                    </div>
                </td>
            </tr>

            <!-- <tr>
                <td>
            
                    <center style='margin-top:10px'>
                        <img src="images/logo_long.png" id="logo_top_login" style="height:50px;">
                    </center>


                </td>
            
            </tr> -->
        
        </table>






        <!-- HIDDEN -->
        <input type="hidden" name="login_hidden" id="login_hidden">

    </form>


    
    <script>

        $("#pwd").keyup(function(event) {
            if (event.keyCode === 13) {
                login();
            }
        });

        var unlocked = false;

        function toggle_lock(){

            if(unlocked == false){

                unlocked = true;
                $("#span_lock").html("<i class='fas fa-lock-open'></i>");
                $("#pwd").attr("type" ,"input");

            }
            else if(unlocked == true){

                unlocked = false;
                $("#span_lock").html("<i class='fas fa-lock'></i>");
                $("#pwd").attr("type" ,"password");
            }
        }

        function login(){

            var userLO = $("#user").val();
            var passLO = $("#pwd").val();

            if ($( "#comp_code" ) && $( "#comp_code" ).length ) {
                var compLO =$("#comp_code").val();
            }else{
                var compLO = '';
            }

            jQuery.ajax({

                data:'username='+userLO+
                '&password='+passLO+
                '&comp_code='+compLO,
                dataType:'json',
                type:'post',
                url:'loginajax.php',
                success: function(xdata){
                    if (xdata['msg']=="successful"){

                        var landing_page = xdata["landing_page"];

                        document.forms.myform.method='POST';
                        document.forms.myform.target='_self';
                        document.forms.myform.action=landing_page;
                        document.forms.myform.submit();
                    } 

                    else if(xdata['msg']!=="successful"){

                        var ret=xdata['msg'];
                        $("#div_msg").css("display" ,"revert");
                        jQuery('.span_msg').html(ret);
                    
                    }

                },
                error: function(xhr, textStatus, errorThrown) {
                    alert('Error: ' + errorThrown);
                }
            })

        }

    </script>    
</body>
</html>
