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


//SELECT SALESMAN ID WIHT NONE
$select_db_smn = "SELECT * FROM mf_salesman WHERE salesman_name='-None'";
$stmt_smn= $link->prepare($select_db_smn);
$stmt_smn->execute();
while($rs_smn = $stmt_smn->fetch()){
    $salesman_id_none = $rs_smn['salesman_id'];
}





//LOOP THROUGH TRANFILE1 fill route_id from mf_routes - route_id
$select_db = "SELECT * FROM tranfile1 WHERE true AND trndte>='".$date_from."' AND trndte<='".$date_to."' AND (trncde='SAL' OR trncde='SRT')";
$stmt= $link->prepare($select_db);
$stmt->execute();
while($rs = $stmt->fetch()){


    if($rs['salesman_id'] == '' || $rs['salesman_id'] == null){

        echo "Actually updated docnum".$rs['docnum']."</br>";

        $sql5 = "UPDATE tranfile1 SET salesman_id='".$salesman_id_none."' WHERE recid='".$rs['recid']."'";
        $stmt_upd1 = $link->prepare($sql5);
        $stmt_upd1->execute();     
    }

    // if (isset($rs['buyer_id']) && $rs['buyer_id']!=null){
    //     $select_db2 = "SELECT * FROM mf_buyers WHERE buyer_id='".$rs['buyer_id']."'";
    //     $stmt2= $link->prepare($select_db2);
    //     $stmt2->execute();
    //     $rs2 = $stmt2->fetch();

    //     if (isset($rs2['route_id']) && $rs2['route_id']!=null){
    //         $sql5 = "UPDATE tranfile1 SET route_id='".$rs2['route_id']."' WHERE recid='".$rs['recid']."'";
    //         $stmt_upd1 = $link->prepare($sql5);
    //         $stmt_upd1->execute();        
    //         echo $sql5."</br>";

    //     }
    // }


}

echo " </br>Updated Salesman from '' to '-None'";

?>