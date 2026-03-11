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




//search for where salesman is  equal to none 


//GET THE salesman_id where it is empty
$select_db_empty_smn = "SELECT * FROM mf_salesman WHERE salesman_name='-None' LIMIT 1";
$stmt_empty_smn= $link->prepare($select_db_empty_smn);
$stmt_empty_smn->execute();
$rs_empty_smn = $stmt_empty_smn->fetch();

//LOOP THROUGH TRANFILE1 WHERE salesman_id is not none
$select_db = "SELECT * FROM tranfile1 WHERE trncde='SAL' AND trndte>='".$date_from."' AND trndte<='".$date_to."' AND salesman_id !='".$rs_empty_smn['salesman_id']."'";
$stmt= $link->prepare($select_db);
$stmt->execute();
while($rs = $stmt->fetch()){


        //select the salesman
        $select_db_smn = "SELECT * FROM mf_salesman WHERE salesman_id='".$rs['salesman_id']."' LIMIT 1";
        $stmt_smn= $link->prepare($select_db_smn);
        $stmt_smn->execute();
        $rs_smn = $stmt_smn->fetch();

        //computed com_pay
        $com_pay = ($rs_smn['commission']/100) * $rs['trntot'];

        $sql5 = "UPDATE tranfile1 SET com_pay=".$com_pay." WHERE recid='".$rs['recid']."'";
        $stmt_upd1 = $link->prepare($sql5);
        $stmt_upd1->execute();  
        
        echo $rs['docnum']. " set com_pay to ".$com_pay."</br>";
        //echo $sql5."</br>";

        
    //}


}

//echo $sql5." </br>Routes of tranfile1 updated";

?>