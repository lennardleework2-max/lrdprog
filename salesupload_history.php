<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    header('location: index.php');
}
require_once("resources/db_init.php") ;
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

$recid=$_SESSION['recid'];
$userdesc=$_SESSION['userdesc'];
$password=$_SESSION['password'];

$select_db_syspar='SELECT * FROM syspar';
$stmt_syspar	= $link->prepare($select_db_syspar);
$stmt_syspar->execute();
while($rs_syspar = $stmt_syspar->fetch()){
    $landing_page = $rs_syspar["landing_page"];
    $system_name = $rs_syspar["system_name"];
    $version = $rs_syspar["version"];
    $logo_dir = $rs_syspar["logo_dir"];
    $logo_height = $rs_syspar["logo_height"];
    $logo_width = $rs_syspar["logo_width"];
    // $chk_insurvalcod = $rs_syspar["chk_insurvalcod"];
    // $chk_company = $rs_syspar["chk_company"];
    // $txt_company = $rs_syspar["txt_company"];
}


$_SESSION["logo_dir"]       = $logo_dir;
$_SESSION["logo_height"]    = $logo_height;
$_SESSION["logo_width"]     = $logo_width;

$filename = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']); 

$select_db_crud="SELECT * FROM user_menus WHERE usercode=? AND menprogram=?";
$stmt_crud	= $link->prepare($select_db_crud);
$stmt_crud->execute(array($_SESSION['usercode'], $filename));
$rs_crud = $stmt_crud->fetch();


if(!empty($rs_crud)){

    $add_crud       = $rs_crud["add"];
    $edit_crud      = $rs_crud["edit"];
    $view_crud      = $rs_crud["view"];
    $delete_crud    = $rs_crud["delete"];
    $export_crud    = $rs_crud["export"];

    $_SESSION["add_crud"]    = $rs_crud["add"];
    $_SESSION["edit_crud"]   = $rs_crud["edit"];
    $_SESSION["view_crud"]   = $rs_crud["view"];
    $_SESSION["delete_crud"] = $rs_crud["delete"];
    $_SESSION["export_crud"] = $rs_crud["export"];

    if($filename == "utl_useractivitylog.php"){

        $add_crud       = 1;
        $edit_crud      = 1;
        $view_crud      = 1;
        $delete_crud    = 1;
        $export_crud    = 1;

        $_SESSION["add_crud"]    = 1;
        $_SESSION["edit_crud"]   = 1;
        $_SESSION["view_crud"]   = 1;
        $_SESSION["delete_crud"] = 1;
        $_SESSION["export_crud"] = 1;
    }

}
else if($userdesc == "admin"){
    $add_crud       = 1;
    $edit_crud      = 1;
    $view_crud      = 1;
    $delete_crud    = 1;
    $export_crud    = 1;

    $_SESSION["add_crud"]    = 1;
    $_SESSION["edit_crud"]   = 1;
    $_SESSION["view_crud"]   = 1;
    $_SESSION["delete_crud"] = 1;
    $_SESSION["export_crud"] = 1;
}else{

    $add_crud       = 0;
    $edit_crud      = 0;
    $view_crud      = 0;
    $delete_crud    = 0;
    $export_crud    = 0;

    $_SESSION["add_crud"]    = 0;
    $_SESSION["edit_crud"]   = 0;
    $_SESSION["view_crud"]   = 0;
    $_SESSION["delete_crud"] = 0;
    $_SESSION["export_crud"] = 0;
}




?>
<!doctype html>
<html lang="en" style="height:100%;">

    <head>
        <!-- NEEDED TO MMAKE THE SIZE AND FORMAT OF WEBPAGE RIGHT -->
        <meta charset="utf-8">
        <!-- uses device width -->
        <!-- <meta name="viewport" content="width=device-width" /> -->
        
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="images/logo_only.png">

        <!-- PLAIN JQUERY AND JQUERY-UI JS -->
        <script src="js/jquery-3.5.1.min.js"></script>
        <script src="js/jquery-ui/jquery-ui.min.js"></script>

        <!--(independent libraries like JQUERY) / jquery css-->
        <link rel="stylesheet" type="text/css" href="js/jquery-ui/jquery-ui.min.css">
        <link href="js/jquery-ui/jquery-ui.structure.css" rel="stylesheet" >
        <link href="js/jquery-ui/jquery-ui.theme.css" rel="stylesheet" >

        <!-- BOOTSTRAP CSS-->
        <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">

        <!-- HOVER CSS -->
        <link rel="stylesheet" href="css/Hover-master/css/hover.css">

        <!--  FONT AWESOME ICONS -->
        <link rel='stylesheet' href='css/all.min.css'>

        <!-- BOOTSTRAP ICONS -->
        <link rel='stylesheet' href='bootstrap/icons/font/bootstrap-icons.css'>

        <!-- CUSTOM CSS-->
        <link rel="stylesheet" href="css/main.css">

        <style>

            .download_options:hover{
                cursor:pointer;
            }

            #tbody_main_convert tr {
                border: none;       /* Remove borders from the rows */
                outline: none;      /* Remove outlines from the rows */
            }

            #tbody_main_convert td {
                border: none;       /* Remove borders from the cells */
                outline: none;      /* Remove outlines from the cells */
            }

            @media (max-width: 768px) {
                
                #tbody_main_convert{
                    display: none;
                }

                #tbody_main_convert_mobile{
                    display:table-row-group!important
                }

            }        
            
            .back_to_file:hover{
                cursor:pointer;
            }            
        </style>
    </head>
    
    <body style="height:100%;">
        
        <div class="container-fluid">
            <div class='row bg-dark'>
                <div class="col-2 pe-0">
                    <img src="images/logo_horizontal.png" style='height:50px;width:100px;'>
                </div>

                <div class="col-10 text-white" style="text-align:right"> 
                        <ul style="list-style-type:none" class="mb-0">
                            <li style="font-family:arial;font-weight:bold">
                                <?php echo $system_name;?>
                            </li>

                            <li style='font-size:.8rem'>
                                <?php echo $version;?>
                            </li>
                        </ul>
                </div> 
            </div>
        </div>

        <div class="container-fluid">
            <div class='row bg-light' style="height:2.188rem;">
                <div class="col-1 pe-0">
                        <button type="button" class="btn btn-light bg-light menu-toggle" style="height:2.188rem;display:flex;align-items:center;justify-content:center"> 
                            <i class="fas fa-long-arrow-alt-right arrow_toggle" style="font-size:27px"></i>
                        </button>
                </div>

                <div class='col-8 col-sm-10 mx-0 px-0 d-flex justify-content-end' >   
                    <div class="row h-100 secondrow_secondcoloumn">
                        <div class="col-10 col-sm-11 mb-3 mx-0 px-0 text-black" style="font-size:13px;height:2.188rem">
                            <div class="h-50">
                                <?php echo "<b style='margin-right:0.188rem'>".$userdesc."</b>";?>
                            </div>        
                            <?php
                                if(isset($_SESSION['comp_code'])):
                            ?>
                            <div class="h-50" style='display:flex;align-items:flex-end;justify-content:flex-end'>
                            <i class="fas fa-id-card-alt" style='font-size:13px;height:90%;margin-right:0.313rem'></i>Company: <?php echo "<b style='margin-left:0.313rem;margin-right:0.188rem'>".$_SESSION['comp_code']."</b>";?>
                             
                            </div>   
                            <?php endif;?>
                        </div>   

                        <div class="col-2 col-sm-1 px-0 mx-0 h-100" style='padding-top:.3rem'>
                            <i class="far fa-user-circle" style="color:black;font-size:25px;float:left"></i>
                        </div>
                    </div>

                </div> 

                <div class="col-3 col-sm-1 mx-0 px-0 fw-bold"> 
                    <div class="col-11" style="text-align:right">
                        <a href="logout.php" style="color:black">
                            <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" class="bi bi-box-arrow-right logout_icon" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                            </svg>
                            Logout
                        </a>
                    </div>

                </div> 
            </div>
        </div>

        <form name='myforms' id="myforms" style='height: calc(100vh - 99px)' enctype="multipart/form-data" method="post" action=''> 
            <table class='big_table'> 
                <tr colspan=1>

                    <td colspan=1 class='td_bl'>
                                                
                        <?php
                            include 'includes/main_menu.php';
                        ?>
                    </td>
    
                    <td colspan=1 class="td_br" id="td_br">

                        <div class="container-fluid pt-2 main_br_div mx-0 px-0" style='width:100%;height:100%'>
                           <div class="mx-md-5 mx-3 d-flex align-items-center mt-3">
                                <h3 class="me-4">File Convert History</h3>

                                <?php
                                    if(isset($_POST['download_history']) && $_POST['download_history'] == 'view'){
                                        echo "<div style='font-size:20px;color:#4d94ff;font-weight:bold' class='back_to_file' onclick='back_to_conversion()'>BACK TO FILE CONVERSION</div>";
                                    }                           
                                ?>
                           </div>
                       
    
                           <div class="mx-md-5 mx-3">
                            <table class="table table-striped shadow shadow-rounded">
                                        
                                <tbody id='tbody_main_convert'>
                                  
                                </tbody>

                                <tbody id="tbody_main_convert_mobile" style='display:none'>
                                    
                                </tbody>
                        
                            </table>
                            <ul class="pagination">
                                <li class="page-item" onclick="page_click('first_p')">
                                    <span class="page-link" aria-label="Previous" style="display:flex;justify-content:center;align-items:center;height:2.3em;width:2.3em">
                                        <span aria-hidden="true">«</span>
                                    </span>
                                </li>

                                <li class="page-item" onclick="page_click('previous_p')">
                                    <span class="page-link" id="previous_pager" style="display:flex;align-items:center;justify-content:center;height:2.3em;width:6.3em">
                                        Previous
                                    </span>
                                </li>

                                <input type="text" style="width:60px;text-align:center;font-weight:bold;height:2.3em" name="txt_pager_pageno" id="txt_pager_pageno" disabled="">

                                <li class="page-item" style="height:3em;" onclick="page_click('next_p')">
                                    <span class="page-link" id="next_pager" style="display:flex;justify-content:center;align-items:center;height:2.3em;width:6.3em">
                                    Next
                                    </span>
                                </li>

                                <li class="page-item" onclick="page_click('last_p')">
                                    <span class="page-link" aria-label="Next" style="display:flex;justify-content:center;align-items:center;height:2.3em;width:2.3em;">
                                        <span aria-hidden="true">»</span>
                                    </span>
                                </li>
                            </ul>
                           </div>
        
                        </div>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="download_history" id="download_history" value="<?php if(isset($_POST['download_history'])) { echo $_POST['download_history']; }?>">
        </form>
        
        <!-- Modal -->
        <div class="modal fade" id="modal_action" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal_header_dictionary"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label for="">JNT Output</label>                        
                    <input type="text" class="form-control" id="txt_jntoutput" name="txt_jntoutput" autocomplete="off">
                    <label for="" class='mt-3'>Keywords</label>                        
                    <input type="text" class="form-control" id="txt_keywords" name="txt_keywords" autocomplete="off">
                </div>
                <div class="modal-footer">

                    <div id="modal_action_btn_div">
                        <button type="button" class="btn btn-success fw-bold" id="modal_action_btn" style='width:80px' onclick="update_table('add','region_dict')">Save</button>    
                    </div>
                    
                </div>
                </div>
            </div>
        </div>
  
<script>

    function back_to_conversion(){
        document.forms.myforms.action="trn_fileupload.php";
        document.forms.myforms.target="_self";
        document.forms.myforms.method="post";
        document.forms.myforms.submit();
    }

    function delete_so(xfile_batchno){

        var txt_pager_pageno = $("#txt_pager_pageno").val();
        var xdata = "event_action=same&txt_pager_pageno="+txt_pager_pageno+"&xfile_batchno="+xfile_batchno;

        jQuery.ajax({    
            data:xdata,
            type:"post",
            dataType: 'json',
            url:"salesupload_history_ajax.php", 
            success: function(xret){ 
                $("#tbody_main_convert").html(xret["outputHTML"]);
                $("#tbody_waybill_mobile").html(xret["outputHTML_mobile"]);
                $("#txt_pager_pageno").val(xret["xpageno"]);

                $("#tbody_itm_count").html(xret["outputItmHTML"]);

            }
        })
    }

    $(document).ready(function() {
        var txt_pager_pageno = $("#txt_pager_pageno").val();
        var xdata = "event_action=first_page&txt_pager_pageno="+txt_pager_pageno;

        jQuery.ajax({    
            data:xdata,
            type:"post",
            dataType: 'json',
            url:"salesupload_history_ajax.php", 
            success: function(xret){ 
                $("#tbody_main_convert").html(xret["outputHTML"]);
                $("#tbody_main_convert_mobile").html(xret["outputHTML_mobile"]);
                $("#txt_pager_pageno").val(xret["xpageno"]);

            }
        })
    });

    function page_click(xevent){

        var txt_pager_pageno = $("#txt_pager_pageno").val();
        var xdata = "event_action="+xevent+"&txt_pager_pageno="+txt_pager_pageno;

        jQuery.ajax({    
            data:xdata,
            type:"post",
            dataType: 'json',
            url:"file_convert_history_ajax.php", 
            success: function(xret){ 
                $("#tbody_main_convert").html(xret["outputHTML"]);
                $("#tbody_main_convert_mobile").html(xret["outputHTML_mobile"]);
                $("#txt_pager_pageno").val(xret["xpageno"]);
            }
        })
    }    
</script>

<?php 
require "includes/main_footer.php";
?>

