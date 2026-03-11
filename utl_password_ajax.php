<?php 

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/lx2.pdodb.php";

$xret = array();
$xret["status"] = 1;
$xret["error1"] = '';
$xret["error2"] = '';

if($_POST["event_action"] == "changePass"){

	$select_db='SELECT * FROM users WHERE userdesc=?';
	$stmt	= $link->prepare($select_db);
	$stmt->execute(array($_POST["username_hidden"]));
    $rs = $stmt->fetch();
    
    if(!password_verify($_POST["old_password"],$rs["password"])) {
        $xret["msg"] = "Wrong password.";
        $xret["status"] = 0;
        $xret["error1"] = 1;
    } 

    if($_POST["new_password"] !== $_POST["new_password2"] || (empty($_POST["new_password"]) || empty($_POST["new_password2"]))){
        if($xret["error1"] == 1){
            $xret["msg"] = $xret["msg"]."<br>New passwords do not match.";
        }else if($xret["error1"] !== 1){
            $xret["msg"] = "New passwords do not match.";
        }
        $xret["error2"] = 1;
        $xret["status"] = 0;
    }

    if($xret["status"]==1){
        $arr_record = array();			
        $_POST['new_password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $arr_record['password'] = $_POST['new_password'];
        PDO_UpdateRecord($link,"users",$arr_record,"userdesc = ?",array($_POST["username_hidden"])); 
        $xret["msg"] ="Successfully changed password.";

        $xret["newpass"] = $_POST["new_password"];
    }
}


header('Content-Type: application/json');
echo json_encode($xret);
?>
