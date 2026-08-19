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

	//delete the image first if meron
	$select_db_chk="SELECT * FROM tranfile1 WHERE recid=".$_POST['recid']." LIMIT 1";
    $stmt_chk	= $link->prepare($select_db_chk);
    $stmt_chk->execute();
    $rs_chk = $stmt_chk->fetch();
    if(!($rs_chk["img_filename"] == null || empty($rs_chk["img_filename"]) || $rs_chk["img_filename"] == "")){
        //if it already exist
        $filename = $rs_chk["img_filename"];
        $filepath = 'images_sales/' . $filename;
        if(file_exists($filepath)){
            unlink($filepath);
        }
    }


	$delete_id=$_POST['recid'];
	$delete_query="DELETE  FROM tranfile1 WHERE recid=?";
	$xstmt=$link->prepare($delete_query);
	$xstmt->execute(array($delete_id));

	if($rs_chk){
		$log_username = useractivitylog_get_session_username();
		$log_fullname = useractivitylog_get_session_fullname($link);
		$log_trndte = date('Y-m-d H:i:s');
		$log_remarks = useractivitylog_build_delete_docnum_remark($rs_chk['docnum']);
		PDO_UserActivityLog($link, $log_username, '', $log_trndte, 'SALES', 'delete', $log_fullname, $log_remarks, 0, '', 'SAL', '', '', $log_username, $rs_chk['docnum'], '');
	}
	
}



header('Content-Type: application/json');
echo json_encode($xret);
?>
