<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once('resources/db_init.php');
require_once('resources/connect4.php');
require_once('resources/lx2.pdodb.php');
function respond($success,$message,$code=200){http_response_code($code);echo json_encode(array('success'=>$success,'message'=>$message));exit;}
if(!isset($_SESSION['userdesc'],$_SESSION['password'],$_SESSION['usercode']))respond(false,'Your session has expired. Please sign in again.',401);
if($_SERVER['REQUEST_METHOD']!=='POST'||!isset($_POST['action'])||$_POST['action']!=='save')respond(false,'Invalid request.',400);
$can_edit=$_SESSION['userdesc']==='admin';
if(!$can_edit){$permission_stmt=$link->prepare('SELECT recid FROM user_menus WHERE usercode=? AND menprogram=? LIMIT 1');$permission_stmt->execute(array($_SESSION['usercode'],'critical_pdf.php'));$can_edit=(bool)$permission_stmt->fetch(PDO::FETCH_ASSOC);}
if(!$can_edit)respond(false,'You do not have permission to access this page.',403);
$item_code=isset($_POST['itmcde'])?trim((string)$_POST['itmcde']):'';$remarks=isset($_POST['crt_remarks'])?trim((string)$_POST['crt_remarks']):'';
if($item_code==='')respond(false,'Item code is required.',422);if((function_exists('mb_strlen')?mb_strlen($remarks,'UTF-8'):strlen($remarks))>100)respond(false,'Remarks cannot exceed 100 characters.',422);
$flags=array();foreach(array('crt_minimal_order','crt_offline','crt_online','crt_discontinued') as $field)$flags[$field]=isset($_POST[$field])&&strtoupper(trim((string)$_POST[$field]))==='Y'?'Y':'N';
try{$stmt=$link->prepare('UPDATE itemfile SET crt_minimal_order=?,crt_offline=?,crt_online=?,crt_discontinued=?,crt_remarks=? WHERE itmcde=?');$stmt->execute(array($flags['crt_minimal_order'],$flags['crt_offline'],$flags['crt_online'],$flags['crt_discontinued'],$remarks,$item_code));$exists=$link->prepare('SELECT itmcde FROM itemfile WHERE itmcde=? LIMIT 1');$exists->execute(array($item_code));if(!$exists->fetch())respond(false,'The selected item no longer exists.',404);respond(true,'Critical inventory settings saved.');}catch(Exception $e){error_log('Critical inventory settings save failed: '.$e->getMessage());respond(false,'The settings could not be saved.',500);}
?>
