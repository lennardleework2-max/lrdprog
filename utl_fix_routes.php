<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);   
session_start();
require_once("resources/db_init.php") ;
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

//var_dump($_POST['date_from']);
//die();

if ($_POST['date_from']=='')
{
    $_POST['date_from'] = date("Y-m-d", strtotime('01-01-2000'));
}
else {
    $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
}

if ($_POST['date_to']=='')
{
    $_POST['date_to'] = date("Y-m-d", strtotime('01-01-2200'));
}
else {
    $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
}


//$_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

$date_from = $_POST['date_from'];
$date_to = $_POST['date_to'];


//LOOP THROUGH TRANFILE1 fill route_id from mf_routes - route_id
$select_db = "SELECT * FROM tranfile1 WHERE trncde='SAL' AND trndte>='".$date_from."' AND trndte<='".$date_to."'";
$stmt= $link->prepare($select_db);
$stmt->execute();
while($rs = $stmt->fetch()){
    if (isset($rs['buyer_id']) && $rs['buyer_id']!=null){
        $select_db2 = "SELECT * FROM mf_buyers WHERE buyer_id='".$rs['buyer_id']."'";
        $stmt2= $link->prepare($select_db2);
        $stmt2->execute();
        $rs2 = $stmt2->fetch();

        if (isset($rs2['route_id']) && $rs2['route_id']!=null){
            $sql5 = "UPDATE tranfile1 SET route_id='".$rs2['route_id']."' WHERE recid='".$rs['recid']."'";
            $stmt_upd1 = $link->prepare($sql5);
            $stmt_upd1->execute();        
            echo $sql5."</br>";

        }
    }


}

echo $sql5." </br>Routes of tranfile1 updated";

?>