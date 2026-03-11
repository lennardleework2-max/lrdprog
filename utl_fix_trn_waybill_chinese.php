<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);   
session_start();
require_once("resources/db_init.php") ;
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");

$_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
$_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

$date_from = $_POST['date_from'];
$date_to = $_POST['date_to'];



$select_db = "SELECT * FROM trn_waybill_orders 
              WHERE true 
              AND DATE(submission_time) >= '".$date_from."' 
              AND DATE(submission_time) <= '".$date_to."' 
              AND order_status REGEXP '[^a-zA-Z0-9 !\"#$%&\'()*+,-./:;<=>?@[\\\\]^_`{|}~]'";
              
echo $select_db;
$stmt= $link->prepare($select_db);
$stmt->execute();

while($rs = $stmt->fetch()){

    echo "</br>FOUND".$rs['waybill_number'];

    $sql1 = "UPDATE trn_waybill_orders SET order_status='Headquarters Scheduling To Outlets' WHERE recid='".$rs['recid']."'";
    $stmt_upd1 = $link->prepare($sql1);
    $stmt_upd1->execute();



}



?>