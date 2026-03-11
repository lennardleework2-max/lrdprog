<?php 
require "includes/main_header.php";


// var_dump($_POST);

// if(isset($_POST["event_action"]) && $_POST["event_action"]=="save"){

//     $select_db_userfile2="SELECT * FROM users WHERE usercode=?";
//     $stmt_userfile2	= $link->prepare($select_db_userfile2);
//     $stmt_userfile2->execute(array($_POST["usercode_access_hidden"]));
//     while($rs_userfile2 = $stmt_userfile2->fetch()){
//         $recid_userfile2 = $rs_userfile2["recid"];
//     }

//     $arr_record_userfile2 = array();			
//     $arr_record_userfile2['full_name']  = $_POST['fullname_access'];
//     PDO_UpdateRecord($link,"users",$arr_record_userfile2,"recid = ?",array($recid_userfile2)); 

//     $select_db_check="SELECT * FROM user_menus WHERE usercode=? AND mencap='Home'";
//     $stmt_check	= $link->prepare($select_db_check);
//     $stmt_check->execute(array($_POST["usercode_access_hidden"]));
//     $rs_check = $stmt_check->fetch();

//     if(empty($rs_check)){

//         $arr_record_usermenu = array();
//         $arr_record_usermenu['usercode'] 	    = $_POST["usercode_access_hidden"];
//         $arr_record_usermenu['mencap'] 	        = "Home";
//         $arr_record_usermenu['menprogram'] 	    = "main.php";
//         $arr_record_usermenu['menlogo'] 	    = "fas fa-home";
//         $arr_record_usermenu['menidx'] 	        = 1.00;
//         $arr_record_usermenu['mennum'] 	        = 0.00;
//         $arr_record_usermenu['mensub'] 	        = '';
//         $arr_record_usermenu['mengrp'] 	        = 1;
//         $arr_record_usermenu['is_removed'] 	    = '';

//         PDO_InsertRecord($link,'user_menus',$arr_record_usermenu, false);

//     }

//     if(isset($_POST["menu"])){

//         $xreturn_u_access = false;

//         foreach ($_POST["menu"] as $key => $value) {

//             if($key == "Users" && (int)$value["value"] == 0){
//                 $xreturn_u_access = true;
//             }
            

//             $username = $_POST["username_access"];
//             $usercode = $_POST["usercode_access_hidden"];

//             if($value['value'] == 0){
//                 $delete_query="DELETE FROM user_menus WHERE mencap=? AND usercode=?";
//                 $xstmt=$link->prepare($delete_query);
//                 $xstmt->execute(array($key,$usercode));
//             }else{


//                 if(isset($value["add"])){
    
//                 }else{
//                     $value["add"] = 0;
//                     $value["edit"] = 0;
//                     $value["delete"] = 0;
//                     $value["view"] = 0;
//                 }

//                 $select_db_menusave_check="SELECT * FROM user_menus WHERE mencap=? AND usercode=?";
//                 $stmt_menusave_check	= $link->prepare($select_db_menusave_check);
//                 $stmt_menusave_check->execute(array($key,$usercode));
//                 $row_menusave_check = $stmt_menusave_check->fetchAll();

//                 if(count($row_menusave_check) == 0){

//                     $select_db_menusave="SELECT * FROM menus WHERE mencap=?";
//                     $stmt_menusave	= $link->prepare($select_db_menusave);
//                     $stmt_menusave->execute(array($key));
//                     while($rs_menusave = $stmt_menusave->fetch()){
                        
//                         $arr_record = array();
//                         $arr_record['mencap'] 	    = $key;
//                         $arr_record['menprogram'] 	= $rs_menusave["menprogram"];
//                         $arr_record['menlogo'] 	    = $rs_menusave["menlogo"];
//                         $arr_record['menidx'] 	    = $rs_menusave["menidx"];
//                         $arr_record['mennum'] 	    = $rs_menusave["mennum"];
//                         $arr_record['mensub'] 	    = $rs_menusave["mensub"];
//                         $arr_record['mengrp'] 	    = $rs_menusave["mengrp"];
//                         $arr_record['is_removed'] 	= $rs_menusave["is_removed"];
//                         $arr_record['add'] 	        = $value["add"];
//                         $arr_record['edit'] 	    = $value["edit"];
//                         $arr_record['delete'] 	    = $value["delete"];
//                         $arr_record['view'] 	    = $value["view"];
//                         $arr_record['usercode'] 	= $usercode;
                
//                         PDO_InsertRecord($link,'user_menus',$arr_record,false);

//                     }
//                 }else{

//                     $select_db_menusave_crud="SELECT * FROM user_menus WHERE mencap=? AND usercode=?";
//                     $stmt_menusave_crud	= $link->prepare($select_db_menusave_crud);
//                     $stmt_menusave_crud->execute(array($key,$usercode));
//                     while($rs_menusave_crud = $stmt_menusave_crud->fetch()){

//                         $arr_record_crud = array();
//                         $arr_record_crud['add'] 	    = $value["add"];
//                         $arr_record_crud['edit'] 	    = $value["edit"];
//                         $arr_record_crud['delete'] 	    = $value["delete"];
//                         $arr_record_crud['view'] 	    = $value["view"];
//                         PDO_UpdateRecord($link,'user_menus',$arr_record_crud, "recid=?",array($rs_menusave_crud["recid"]),false);
//                     }

//                 }
            


//             }


//         }

//         // if(($_SESSION["usercode"] == $_POST["usercode_access_hidden"]) && $_POST["userlvl_access"] == "User"){

//         //     $select_db_check="SELECT * FROM user_menus WHERE usercode=? AND mencap='Home'";
//         //     $stmt_check	= $link->prepare($select_db_check);
//         //     $stmt_check->execute(array($_POST["usercode_access_hidden"]));
//         //     $rs_check = $stmt_check->fetch();
    
//         //     if(empty($rs_check)){
    
//         //         $arr_record_usermenu = array();
//         //         $arr_record_usermenu['usercode'] 	    = $_POST["usercode_access_hidden"];
//         //         $arr_record_usermenu['mencap'] 	        = "Home";
//         //         $arr_record_usermenu['menprogram'] 	    = "main.php";
//         //         $arr_record_usermenu['menlogo'] 	    = "fas fa-home";
//         //         $arr_record_usermenu['menidx'] 	        = 1.00;
//         //         $arr_record_usermenu['mennum'] 	        = 0.00;
//         //         $arr_record_usermenu['mensub'] 	        = '';
//         //         $arr_record_usermenu['mengrp'] 	        = 1;
//         //         $arr_record_usermenu['is_removed'] 	    = '';
    
//         //         PDO_InsertRecord($link,'user_menus',$arr_record_usermenu, false);
        
//         //         echo "<script type=\"text/javascript\">

//         //                 window.location.href = 'main.php';

                    
//         //         </script>";
    
    
//         //     }else{

//         //         echo "<script type=\"text/javascript\">
//         //                 window.location.href = 'main.php';
//         //         </script>";  
    
//         //     }
    
//         // }

//         if($xreturn_u_access == true && $userdesc !=='admin'){
//             echo "<script type=\"text/javascript\">
//                     window.location.href = 'main.php';
//             </script>";  
//         }
        
//     }


// }
?>         

    <form name='myforms' id="myforms" method="post" target="_self"> 
            <table class='big_table'> 
                <tr colspan=1>
                    <td colspan=1 class='td_bl'>
                        <?php
                        require 'includes/main_menu.php';
                        ?>
                    </td>

                    <td colspan=1 class="td_br">
                        <div class="container-fluid mt-2 main_br_div">

                            <h2>Users</h2>

                            <div class="container-fluid my-2">
                                <div class="row">
                                    <div class="col-12 col-sm-6 mx-0 px-0">

                                    <button type='button' class="btn btn-success my-2" value="Add Record" data-bs-toggle="modal" data-bs-target="#insertModal">
                                        <span style='font-weight:bold'>
                                            Add Record
                                        </span>

                                        <i class='fas fa-plus' style='margin-left: 3px'></i>
                                    </button>

                                    </div>

                                    <div class='col-12 col-sm-6 mx-0 px-0 d-flex flex-nowrap justify-content-sm-end div_search'>
                                        <select class="form-select ml-0 my-2 dropdown_dd_user" style="width:auto;margin-right:5px" hidden-value="" id="search_dd">
                                            <option value='userdesc'>Username</option>
                                            <option value='full_name'>Full Name</option>
                                        </select>

                                        <span class="overflow-visible" id="search_input" style="width:auto">
                                            <div class="input-group rounded my-2 ms-0 overflow-visible">
                                                <input type="text" class="form-control border border-dark border-2 search_text_input_user" autocomplete="off"  onkeypress="return check_enter_user(event)" hidden-value="">
                                                <div class="input-group-btn bg-white rounded-end border border-dark border-2 d-flex justify-content-center tabbable" tabindex="0" onkeypress="return check_enter_user(event)">
                                                    <span class="btn btn-default" onclick="page_click_user('search')">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </span>
                                    </div>
                                </div>    
                            </div>


                            <table class="table table-striped shadow" id="data_table" style='border-radius:.75rem!important;'>
                                <thead style='border-bottom:2px solid black;'>
                                    <tr>
                                    <th scope="col">Username</th>
                                    <th scope="col">Full Name</th>
                                    <th scope="col" class="text-center">Action</th>                   
                                    </tr>
                                </thead>

                                <tbody class='tbody_users' id='tbody_main'>
                                </tbody>

                                <tbody class='tbody_users_mobile' id='tbody_main_mobile'>
                                </tbody>
                            </table>

                            <nav aria-label='Page navigation' id='pager' style='font-size:14px'>
                                <ul class='pagination'>
                                    <li class='page-item' onclick="page_click_user('first_p')">
                                        <span class='page-link' aria-label='Previous' style='display:flex;justify-content:center;align-items:center;width:60px;height:3em;width:3em'>
                                            <span aria-hidden='true'>&laquo;</span>
                                        </span>
                                    </li>

                                    <li class='page-item' onclick="page_click_user('previous_p')">
                                        <span class='page-link' id='previous_pager' style='display:flex;align-items:center;justify-content:center;height:3em;width:7em'>
                                            Previous
                                        </span>
                                    </li>

                                    <input type='text' style='width:60px;text-align:center;font-weight:bold' name='txt_pager_pageno' id='txt_pager_pageno' disabled>

                                    <li class='page-item' style='height:3em;' onclick="page_click_user('next_p')">
                                        <span class='page-link' id='next_pager' style='display:flex;justify-content:center;align-items:center;height:3em;width:7em'>
                                        Next
                                        </span>
                                    </li>

                                    <li class='page-item' onclick="page_click_user('last_p')">
                                        <span class='page-link' aria-label='Next' style='display:flex;justify-content:center;align-items:center;height:3em;width:3em;'>
                                            <span aria-hidden='true' >&raquo;</span>
                                        </span>
                                    </li>
                                </ul>
                            </nav>

                            <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'>
                            <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'>
    
                        </div>

                    </td>

                </tr>
            </table>
            <!-- HIDDEN -->
            <input type="hidden" name="usercode_hidden" id="usercode_hidden">
    </form> 

    <!-- INSERT MODAL -->
    <div class="modal fade" id="insertModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Insert User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Username</label>
                            <input type="text" name="username_add" id="username_add" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Full Name</label>
                            <input type="text" name="full_name_add" id="full_name_add" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Password</label>
                            <input type="text" name="password_add" id="password_add" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-2">
                        <div class="error_msg"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="ajaxFunc('insert')">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Username</label>
                            <input type="text" name="username_edit" id="username_edit" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12">
                            <label for="">Full Name</label>
                            <input type="text" name="full_name_edit" id="full_name_edit" class="form-control" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-2">
                        <div class="error_msg"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="ajaxFunc('submitEdit')">Save</button>
                </div>

                <!-- HIDDEN -->
                <input type="hidden" id="username_hidden" name="username_hidden">
                <input type="hidden" name="recid_hidden" id="recid_hidden">
            </div>
        </div>
    </div>

    <div class='modal fade' id='reset_pass_modal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'> <img src='images/logo_long.png' style='width:150px;'><?php echo $system_name;?> Says: </h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                
                <div class='modal-body'>
                    <p id='reset_modal_p'></p>
                </div>

                <div class='modal-footer'>
                    <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancel</button>
                    <span id='reset_modal_btn'>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="xsearch_user" id="xsearch_user">

    <script>

        $(document).ready(function(){
            $("#txt_pager_pageno").val("1");
            page_click_user("first_p", "first_load");    
        })


        function ajaxFunc(event ,recid, xextra){

            switch(event) {
                case "delete":
                    var xdata = "event_action=delete&recid="+recid;
                    break;
                case "insert":
                    var xdata = $("#insertModal *").serialize()+"&event_action=insert";
                    break;
                case "getEdit":
                    var xdata = "event_action=getEdit&recid="+recid;

                    if(xextra == "open_modal_admin"){
                        $("#username_edit").attr('disabled','disabled');
                    }

                    break;
                case "submitEdit":
                    var myform = $('#editModal');
                    var disabled = myform.find(':input:disabled').removeAttr('disabled');
                    var xdata = $("#editModal *").serialize()+"&event_action=submitEdit";
                    break;
                case "reset_pass_modal":

                    $("#reset_modal_btn").html("<button type='button' style='width:70px' class='btn btn-primary' onclick=\"ajaxFunc('reset_pass_submit',"+recid+")\">Yes</button>");
                    $("#reset_modal_p").html("Are you sure you want to reset <b>"+xextra+"'s</b> password?</br></br>*Note that the resetted default password is : <b>123</b>");
                    $("#reset_pass_modal").modal("show");
                    return;
                case "reset_pass_submit":
                    var xdata = "event_action="+event+"&recid="+recid;
  
            }

            jQuery.ajax({    

                data:xdata,
                dataType:"json",
                type:"post",
                url:"utl_users_ajax_crud.php", 

                success: function(xdata){  

                    if(event == "reset_pass_submit"){
                        $("#reset_pass_modal").modal("hide");
                    }
                    if(xdata["redirect"] == true){

                        document.forms.myforms.method='POST';
                        document.forms.myforms.target='_self';
                        document.forms.myforms.action='main.php';
                        document.forms.myforms.submit();

                    }
                    else if(xdata["status"] == 0){
                        $(".error_msg").html("<div class='alert alert-danger' role='alert'>"+xdata["msg"]+"</div>")
                    }
                    else if(xdata["status"] == 1){
                        $(".tbody_users").html(xdata["html"]);
                        $('#insertModal').modal('hide');
                        $('#editModal').modal('hide');
                        page_click_user("same");
                    }
                    else if(xdata["status"] == "retEdit"){
                        $("#username_edit").val(xdata["retEdit"]["username"]);
                        $("#full_name_edit").val(xdata["retEdit"]["full_name"]);
                        $("#password_edit").val(xdata["retEdit"]["password"]);

                        //hidden inputs
                        $("#username_hidden").val(xdata["retEdit"]["username"]);
                        $("#recid_hidden").val(xdata["retEdit"]["recid"]);

                        $('#editModal').modal('show');

                    }
                }
            })
        }

        function page_click_user(event_action, xextra){
            var first_load = "";
            if(xextra == "first_load"){
                first_load = "first_load";
            }

            var dd_user = $(".dropdown_dd_user").val();
            var input_user = $(".search_text_input_user").val();

            var dd_user_hidden = $(".dropdown_dd_user").attr("hidden-value"); 
            var input_hidden = $(".search_text_input_user").attr("hidden-value"); 

            var xsearch = $("#xsearch_user").val();
            // console.log(dd_user+','+input_user);
            // alert(dd_user+','+input_user);
            //Pager limit

            var pageno = $("#txt_pager_pageno").val();
            jQuery.ajax({    
                data:{
                    first_load:first_load,
                    xsearch:xsearch,
                    dd_user_hidden:dd_user_hidden,
                    input_hidden:input_hidden,
                    dd_user: dd_user,
                    input_user: input_user,
                    pageno:pageno,
                    event_action:event_action
                },
                dataType:"json",
                type:"post",
                url:"utl_users_ajax_pager.php", 

                success: function(xdata){  

                    $("#txt_pager_totalrec").val(xdata["totalrec"]);
                    $("#txt_pager_pageno").val(xdata["xpageno"]);
                    $("#txt_pager_maxpage").val(xdata["maxpage"]);
                    $("#tbody_main").html(xdata["html"]);
                    $("#tbody_main_mobile").html(xdata["html_mobile"]);

                    if(event_action == "search"){
                        $("#xsearch_user").val("search");
                        $(".search_text_input_user").attr("hidden-value" , xdata["input_hidden"]);
                        $(".dropdown_dd_user").attr("hidden-value" , xdata["dd_hidden"]);
                    }

                }
            })
        }

        function userAccess(username_access,usercode_access){

            $("#usercode_hidden").val(usercode_access);
            document.forms.myforms.method='POST';
            document.forms.myforms.target='_self';
            document.forms.myforms.action='utl_user_access.php';
            document.forms.myforms.submit();

        }

        function check_enter_user(evt) {
            var ASCIICode = (evt.which) ? evt.which : evt.keyCode
            if(ASCIICode == 13){
                page_click_user("search");
            }
        }

        //on close modal remove error message
        $('#insertModal').on('hidden.bs.modal', function () {
            $(".error_msg").html("");
        });
        $('#insertModal').on('hidden.bs.modal', function (e) {
            $("#username_add").val("");
            $("#full_name_add").val("");
            $("#password_add").val("");
        })
        $('#editModal').on('hidden.bs.modal', function () {
            $(".error_msg").html("");
        });

        if (window.matchMedia('(max-width: 576px)').matches)
        {
            $(".td_br").removeClass();
        }

    </script>

<?php 
require "includes/main_footer.php";
?>



