<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);   
session_start();
require_once("resources/db_init.php") ;
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

$_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
$_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));

$date_from = $_POST['date_from'];
$date_to = $_POST['date_to'];

//LOOP THROUGH TRANFILE1 IF IT DOESNT EXIST THEN INSERT IT INTO mf_buyers
$select_db = "SELECT * FROM tranfile1 WHERE true AND trndte>='".$date_from."' AND trndte<='".$date_to."'";
$stmt= $link->prepare($select_db);
$stmt->execute();
while($rs = $stmt->fetch()){

    $select_db2 = "SELECT * FROM mf_buyers WHERE buyer_name='".$rs['orderby']."'";
    $stmt2= $link->prepare($select_db2);
    $stmt2->execute();
    $rs2 = $stmt2->fetch();

    //GETTING THE BUYER_ID
    $select_db3 = "SELECT * FROM mf_buyers ORDER BY recid DESC LIMIT 1";
    $stmt3= $link->prepare($select_db3);
    $stmt3->execute();
    $rs3 = $stmt3->fetch();

    if(!empty($rs3)){
        $buyer_id = LNexts($rs3["buyer_id"]);
    }else{
        $buyer_id = 'BID-0001';
    }

    //echo $select_db2."</br>";

    if(empty($rs2)){

        //echo "</br>HOY";

        $select_db4 = "INSERT INTO mf_buyers (buyer_id, buyer_name) VALUES ('".$buyer_id."', '".$rs['orderby']."')";
        $stmt4 = $link->prepare($select_db4);
        $stmt4->execute();


        $sql5 = "UPDATE tranfile1 SET buyer_id='".$buyer_id."' WHERE recid='".$rs['recid']."'";
        $stmt_upd1 = $link->prepare($sql5);
        $stmt_upd1->execute();

    }else{

        $sql5 = "UPDATE tranfile1 SET buyer_id='".$rs2['buyer_id']."' WHERE recid='".$rs['recid']."'";
        $stmt_upd1 = $link->prepare($sql5);
        $stmt_upd1->execute();
    }



    echo $sql5."</br>";


}


//LOOP THROUGH TRANFILE1 IF IT DOESNT EXIST THEN INSERT IT INTO mf_buyers
// $select_db5 = "SELECT * FROM tranfile1 WHERE true AND trndte>='".$date_from."' AND trndte<='".$date_to."'";
// $stmt5= $link->prepare($select_db5);
// $stmt5->execute();
// while($rs5 = $stmt5->fetch()){

//     $select_db2 = "SELECT * FROM mf_buyers WHERE buyer_name='".$rs['orderby']."'";
//     $stmt2= $link->prepare($select_db2);
//     $stmt2->execute();
//     $rs2 = $stmt2->fetch();

//     //GETTING THE BUYER_ID
//     $select_db3 = "SELECT * FROM mf_buyers ORDER BY recid DESC LIMIT 1";
//     $stmt3= $link->prepare($select_db3);
//     $stmt3->execute();
//     $rs3 = $stmt3->fetch();

//     if(!empty($rs3)){
//         $buyer_id = LNexts($rs3["buyer_id"]);
//     }else{
//         $buyer_id = 'BID-0001';
//     }

//     //echo $select_db2."</br>";

//     if(empty($rs2)){

//         //echo "</br>HOY";

//         $select_db4 = "INSERT INTO mf_buyers (buyer_id, buyer_name) VALUES ('".$buyer_id."', '".$rs['orderby']."')";
//         $stmt4 = $link->prepare($select_db4);
//         $stmt4->execute();

//         //echo $select_db4."</br>";
//     }

//     $sql1 = "UPDATE trn_waybill_orders SET order_status='Headquarters Scheduling To Outlets' WHERE recid='".$rs['recid']."'";
//     $stmt_upd1 = $link->prepare($sql1);
//     $stmt_upd1->execute();


// }


?>