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

	$delete_id = $_POST['recid'];
	$select_db_delete = "SELECT docnum FROM tranfile1 WHERE recid=? LIMIT 1";
	$stmt_delete = $link->prepare($select_db_delete);
	$stmt_delete->execute(array($delete_id));
	$rs_delete = $stmt_delete->fetch();

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

	$delete_query="DELETE  FROM tranfile1 WHERE recid=?";
	$xstmt=$link->prepare($delete_query);
	$xstmt->execute(array($delete_id));

	if($rs_delete){
		$log_username = useractivitylog_get_session_username();
		$log_fullname = useractivitylog_get_session_fullname($link);
		$log_trndte = date('Y-m-d H:i:s');
		$log_remarks = useractivitylog_build_delete_docnum_remark($rs_delete['docnum']);
		PDO_UserActivityLog($link, $log_username, '', $log_trndte, 'PURCHASES', 'delete', $log_fullname, $log_remarks, 0, '', 'PUR', '', '', $log_username, $rs_delete['docnum'], '');
	}
	
}



header('Content-Type: application/json');
echo json_encode($xret);
?>
