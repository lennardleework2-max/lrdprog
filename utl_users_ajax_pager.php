<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require "resources/db_init.php";
require "resources/connect4.php";
require_once("resources/lx2.pdodb.php");
require "resources/stdfunc100.php";

$xret = array();
$xret["status"] = 2;
$xret["msg"] = "";
$xret["html"] = "";
$xret["html_mobile"] = "";

$xfilter = "";
$xorder = "ORDER BY userdesc ASC";

if($_POST["event_action"] == "search" || $_POST["first_load"] == "first_load"){
    $xfilter = " AND ".$_POST["dd_user"]." LIKE '%".$_POST["input_user"]."%'";
    $xorder = "ORDER BY ".$_POST["dd_user"]." ASC";

    $xret["dd_hidden"] = $_POST["dd_user"];
    $xret["input_hidden"] = $_POST["input_user"];
}else{

    if(($_POST["dd_user"] !== $_POST["dd_user_hidden"]) && $_POST["xsearch"] == "search"){
        $_POST["dd_user"] = $_POST["dd_user_hidden"];
    }

    if(($_POST["input_user"] !== $_POST["input_hidden"]) && $_POST["xsearch"] == "search"){
        $_POST["input_user"] = $_POST["input_hidden"];
    }

    if($_POST["xsearch"] == "search"){
        $xfilter = " AND ".$_POST["dd_user"]." LIKE '%".$_POST["input_user"]."%'";
    }
  
}

$select_db_xtotal = "SELECT count(*) as rec_count FROM users WHERE true ".$xfilter;
$stmt_xtotal	= $link->prepare($select_db_xtotal);
$stmt_xtotal->execute();
$rs_xtotal = $stmt_xtotal->fetch();

$xret["sql"] = $select_db_xtotal;

//INITIALIZE PAGE NO.
$xpageno=$_POST['pageno'];

//RETURN TOTAL RECORDS
$xtotalrec=$rs_xtotal['rec_count'];
$xret['totalrec']=$xtotalrec;


$xlimit = 100;
//CALCULATE MAXPAGE
$maxpage = ceil($xtotalrec / $xlimit);
//RETURN MAXPAGE
$xret["maxpage"] = $maxpage;




if ($xtotalrec==0)
{
    $xret["html"] = "<tr><td colspan='3' class='text-center display-5 w-100' style='padding-left:0px !important;'> NO RECORDS<i class='fas fa-search display-6 mx-2'></i></td></tr>";
    $xret["maxpage"]=0;
    $xret["xpageno"] ='';
    echo json_encode($xret);
    return;
}

//CALCULATE OFFSET
if($xpageno == 0 || $xpageno == 1 || empty($xpageno) || $_POST["event_action"] == "search"){
    $xpageno = 1;
    $xoffset = 0;
}
// if($xpageno == 0){
//     $xpageno = 1;
//     $xoffset = 0;
// }
if($_POST["event_action"] == "next_p"){
    if($xpageno == $maxpage){
        //nothing changes
    }else{
        $xpageno++;
    }
    $xoffset =  ($xpageno * $xlimit) - $xlimit ;

}
else if($_POST["event_action"] == "previous_p"){
    if($xpageno==1){
        $xoffset = 0;
    }else{
        $xpageno--;
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }
}
else if($_POST["event_action"] == "first_p"){
    $xpageno=1;
    $xoffset = 0;
}
else if($_POST["event_action"] == "last_p"){
    $xpageno = $maxpage;
    $xoffset =  ($xpageno * $xlimit) - $xlimit ;
}
else if($_POST["event_action"] =="same"){
    if($xpageno > $maxpage){
        $xpageno = $maxpage;
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }else{
        $xoffset =  ($xpageno * $xlimit) - $xlimit ;
    }
}

//RETURN PAGE NO
$xret["xpageno"] = $xpageno;

$select_db='SELECT * FROM users WHERE true '.$xfilter.' '.$xorder.' LIMIT '.$xlimit.' OFFSET '.$xoffset;
$stmt	= $link->prepare($select_db);
$stmt->execute();
while($row_main = $stmt->fetch()){

    if($row_main['userdesc'] == NULL){
        $row_main['userdesc'] = '&nbsp';
    }

    if($row_main['full_name'] == NULL){
        $row_main['full_name'] = '&nbsp';
    }

    $xret["html"] .= "<tr>";
        $xret["html"] .= "<td data-label='Username'>".$row_main['userdesc']."</td>";
        $xret["html"] .= "<td data-label='Full Name'>".$row_main['full_name']."</td>";

        if($row_main["userdesc"] == "admin" && $_SESSION["userdesc"] == "admin"){
            $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";
                $xret["html"].= "<div class='dropdown'>";
                    $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                        $xret["html"].= "Action";
                    $xret["html"].= "</button>";

                    $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

                        $xret["html"].= "<li onclick=\"ajaxFunc('getEdit' , '".$row_main['recid']."', 'open_modal_admin')\">";
                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                        $xret["html"].= "</li>";

                        if($_SESSION["usercode"] !== $row_main["usercode"]){

                            $xret["html"].= "<li onclick=\"ajaxFunc('delete' , '".$row_main['recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }
                    $xret["html"].= "</ul>";
                $xret["html"].= "</div>";
            $xret["html"].= "</td>";
        }else if($row_main["userdesc"] == "admin" && $_SESSION["userdesc"] !== "admin"){
            $xret["html"].= "<td></td>";
        }else{
            $xret["html"].= "<td class='text-center align-middle' data-label='Action'>";
                $xret["html"].= "<div class='dropdown'>";
                    $xret["html"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                        $xret["html"].= "Action";
                    $xret["html"].= "</button>";

                    $xret["html"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

                        $xret["html"].= "<li onclick=\"ajaxFunc('getEdit' , '".$row_main['recid']."')\">";
                        $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                        $xret["html"].= "</li>";

                        if($_SESSION["usercode"] !== $row_main["usercode"]){

                            $xret["html"].= "<li onclick=\"ajaxFunc('delete' , '".$row_main['recid']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                            $xret["html"].= "</li>";
                        }

                        $xret["html"].= "<li onclick=\"userAccess('".$row_main['userdesc']."', '".$row_main['usercode']."')\">";
                            $xret["html"].= "<a class='dropdown-item dd_action' style='color:#008080;font-weight:bold;'><i class='fas fa-tools'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>User Access</span></a>";
                        $xret["html"].= "</li>";

                        if($_SESSION["userdesc"] == "admin"){

                            $xret["html"].= "<li onclick=\"ajaxFunc('reset_pass_modal' , '".$row_main['recid']."', '".$row_main['userdesc']."')\">";
                                $xret["html"].= "<a class='dropdown-item dd_action' style='color: #7733ff;font-weight:bold'><i class='fas fa-key'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Reset Password</span></a>";
                            $xret["html"].= "</li>";
                        }

                    $xret["html"].= "</ul>";
                $xret["html"].= "</div>";
            $xret["html"].= "</td>";
        }
    $xret["html"] .= "</tr>";

    
    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td data-label='Username' style='font-weight:bold'>Username</td>";
        $xret["html_mobile"] .= "<td data-label='Username'>".$row_main['userdesc']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td data-label='Username' style='font-weight:bold'>Full Name</td>";
        $xret["html_mobile"] .= "<td data-label='Full Name'>".$row_main['full_name']."</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
        $xret["html_mobile"] .= "<td data-label='Action' style='font-weight:bold'>Action</td>";

        if($row_main["userdesc"] == "admin" && $_SESSION["userdesc"] == "admin"){
            $xret["html_mobile"].= "<td class='text-center align-middle' data-label='Action'>";
                $xret["html_mobile"].= "<div class='dropdown'>";
                    $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                        $xret["html_mobile"].= "Action";
                    $xret["html_mobile"].= "</button>";

                    $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

                        $xret["html_mobile"].= "<li onclick=\"ajaxFunc('getEdit' , '".$row_main['recid']."', 'open_modal_admin')\">";
                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                        $xret["html_mobile"].= "</li>";

                        if($_SESSION["usercode"] !== $row_main["usercode"]){

                            $xret["html_mobile"].= "<li onclick=\"ajaxFunc('delete' , '".$row_main['recid']."')\">";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }
                    $xret["html_mobile"].= "</ul>";
                $xret["html_mobile"].= "</div>";
            $xret["html_mobile"].= "</td>";
        }else if($row_main["userdesc"] == "admin" && $_SESSION["userdesc"] !== "admin"){
            $xret["html_mobile"].= "<td></td>";
        }else{
            $xret["html_mobile"].= "<td class='text-center align-middle' data-label='Action'>";
                $xret["html_mobile"].= "<div class='dropdown'>";
                    $xret["html_mobile"].= "<button class='btn btn-primary dropdown-toggle fw-bold' type='button' id='dropdownMenuButton1-".$row_main['recid']."'  data-bs-toggle='dropdown' aria-expanded='false'>";
                        $xret["html_mobile"].= "Action";
                    $xret["html_mobile"].= "</button>";

                    $xret["html_mobile"].= "<ul class='dropdown-menu main_action_dd' id='action_dropdown_data' aria-labelledby='dropdownMenuButton1-".$row_main['recid']."'>";

                        $xret["html_mobile"].= "<li onclick=\"ajaxFunc('getEdit' , '".$row_main['recid']."')\">";
                        $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008ae6;font-weight:bold;'><i class='fas fa-pencil-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Edit</span></a>";
                        $xret["html_mobile"].= "</li>";

                        if($_SESSION["usercode"] !== $row_main["usercode"]){

                            $xret["html_mobile"].= "<li onclick=\"ajaxFunc('delete' , '".$row_main['recid']."')\">";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#ff3333;font-weight:bold;'><i class='fas fa-trash-alt'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Delete</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }

                        $xret["html_mobile"].= "<li onclick=\"userAccess('".$row_main['userdesc']."', '".$row_main['usercode']."')\">";
                            $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#008080;font-weight:bold;'><i class='fas fa-tools'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>User Access</span></a>";
                        $xret["html_mobile"].= "</li>";

                        if($_SESSION["userdesc"] == "admin"){

                            $xret["html_mobile"].= "<li onclick=\"ajaxFunc('reset_pass_modal' , '".$row_main['recid']."', '".$row_main['userdesc']."')\">";
                                $xret["html_mobile"].= "<a class='dropdown-item dd_action' style='color:#802b00'><i class='fas fa-key'></i><span style='margin-left:7px;font-size:17px;font-family:arial'>Reset Password</span></a>";
                            $xret["html_mobile"].= "</li>";
                        }

                    $xret["html_mobile"].= "</ul>";
                $xret["html_mobile"].= "</div>";
            $xret["html_mobile"].= "</td>";
        }
    $xret["html_mobile"] .= "</tr>";
}







header('Content-Type: application/json');
echo json_encode($xret);
?>
