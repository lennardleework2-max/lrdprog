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

	$select_db_delcheck = "SELECT * FROM tranfile1 WHERE recid=?";
	$stmt_delcheck	= $link->prepare($select_db_delcheck);
	$stmt_delcheck->execute(array($_POST['recid']));
	while($rs_delcheck = $stmt_delcheck->fetch()){

		$select_db_delcheck2 = "SELECT * FROM tranfile2 WHERE docnum='".$rs_delcheck['docnum']."'";
		$stmt_delcheck2	= $link->prepare($select_db_delcheck2);
		$stmt_delcheck2->execute();
		while($rs_delcheck2 = $stmt_delcheck2->fetch()){

			$select_db_delcheck3 = "SELECT * FROM purchasesorderfile2 WHERE tranfile2_recid ='".$rs_delcheck2['recid']."'";
			$stmt_delcheck3	= $link->prepare($select_db_delcheck3);
			$stmt_delcheck3->execute();
			while($rs_delcheck3 = $stmt_delcheck3->fetch()){
	
				$arr_delarr3 = array();
				$arr_delarr3['tranfile2_recid'] = '0';    
				PDO_UpdateRecord($link,'purchasesorderfile2',$arr_delarr3,"recid = ?",array($rs_delcheck3['recid']),false);  
	
			}			
		}

	}

	$delete_id=$_POST['recid'];
	$delete_query="DELETE  FROM tranfile1 WHERE recid=?";
	$xstmt=$link->prepare($delete_query);
	$xstmt->execute(array($delete_id));
	
}



header('Content-Type: application/json');
echo json_encode($xret);
?>
