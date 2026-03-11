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
$xret["status"] = 1;
$xret["error1"] = '';
$xret["redirect"] = false;
$xret["msg"] = "";

if($_POST["event_action"] == "reset_pass_submit"){

    $arr_record = array();			
    //password is 123
    $arr_record['password'] = '$2y$10$anPsOZQUEkOXUlRXRG2rPeISh2hLw7IhJCjBLZCb4QwEbMfz0CNvW';
	PDO_UpdateRecord($link,"users",$arr_record,"recid = ?",array($_POST["recid"])); 
}


if($_POST["event_action"] == "delete"){
	$delete_id=$_POST['recid'];
	$delete_query="DELETE  FROM users WHERE recid=?";
	$xstmt=$link->prepare($delete_query);
	$xstmt->execute(array($delete_id));
}

else if($_POST["event_action"] == "getEdit"){

    $select_db2='SELECT * FROM users WHERE recid=?';
	$stmt2	= $link->prepare($select_db2);
	$stmt2->execute(array($_POST["recid"]));
    $rs = $stmt2->fetch();
    $xret["retEdit"] = [
        "username" => $rs["userdesc"],
        "full_name" => $rs["full_name"],
        "password" => $rs["password"],
        "recid" => $rs["recid"]
    ];

    $xret["status"] = "retEdit";

}

else if($_POST["event_action"] == "insert")
{

    $select_db="SELECT * FROM users where userdesc=?";
    $stmt	= $link->prepare($select_db);
    $stmt->execute(array($_POST["username_add"]));
    $row = $stmt->fetchAll();

    if(empty($_POST["username_add"]) || empty($_POST["password_add"])){
        $xret["status"] = 0;
        $xret["msg"] = "Please fill out the fields";
        $xret["error1"] = true;
    }
    
    if(count($row) > 0){
        $xret["status"] = 0;
        if($xret["error1"] == true){
            $xret["msg"] =  $xret["msg"]."<br> Username taken";
        }else{
            $xret["msg"] ="Username taken";
        }
       
    }

    else if($xret["status"] == 1){

        $select_db_usercde="SELECT usercode FROM users ORDER BY usercode DESC LIMIT 1";
        $stmt_usercde	= $link->prepare($select_db_usercde);
        $stmt_usercde->execute();
        while($row_usercde = $stmt_usercde->fetch()){
            $usrcde = LNexts($row_usercde["usercode"]);
        };

        if(empty($usrcde)){
            $usrcde = "USR-00001";
        }

        $hashed_password = password_hash($_POST["password_add"], PASSWORD_DEFAULT);

        $arr_record = array();
        $arr_record['usercode'] 	    = $usrcde;
        $arr_record['userdesc'] 	= $_POST["username_add"];
        $arr_record['full_name'] 	= $_POST["full_name_add"];
        $arr_record['password'] 	= $hashed_password;
        PDO_InsertRecord($link,'users',$arr_record, false);


    }
}
else if($_POST["event_action"] == "submitEdit")
{

    $select_db="SELECT * FROM users where userdesc=?";
    $stmt	= $link->prepare($select_db);
    $stmt->execute(array($_POST["username_edit"]));
    $row = $stmt->fetch();

    if(empty($_POST["username_edit"])){
        $xret["status"] = 0;
        $xret["msg"] = "Please enter a Username";
        $xret["error1"] = true;
    }

    if($_POST["username_hidden"]==$_POST["username_edit"]){}
    else if(!empty($row)){
        $xret["status"] = 0;
        if($xret["error1"] == true){
            $xret["msg"] =  $xret["msg"]."<br> Username taken";
        }else{
            $xret["msg"] ="Username taken";
        }

    }
    
    if($xret["status"] == 1){
        $arr_record = array();
        $arr_record['userdesc'] 	= $_POST["username_edit"];
        $arr_record['full_name'] 	= $_POST["full_name_edit"];
        PDO_UpdateRecord($link,"users",$arr_record,"recid = ?",array($_POST["recid_hidden"]));   
    }
}


header('Content-Type: application/json');
echo json_encode($xret);
?>
