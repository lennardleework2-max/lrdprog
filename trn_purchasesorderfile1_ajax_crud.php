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
$xret["msg"] = "";



if($_POST["event_action"] == "delete"){

	$delete_id=$_POST['recid'];
	$delete_query="DELETE  FROM purchasesorderfile1 WHERE recid=?";
	$xstmt=$link->prepare($delete_query);
	$xstmt->execute(array($delete_id));
	
}



header('Content-Type: application/json');
echo json_encode($xret);
?>
