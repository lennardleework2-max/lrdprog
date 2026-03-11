<?php
    // ini_set('display_errors', '1');
    // ini_set('display_startup_errors', '1');
    // error_reporting(E_ALL);     

    session_start();
    
    require_once("resources/db_init.php");
    require "resources/connect4.php";
    require "resources/stdfunc100.php";
    require "resources/lx2.pdodb.php";

    $xret = array();
    $xret["html"] = "";
    $xret["trntot"] = "";
    $xret["msg"] = "";
    $xret["status"] = 1;
    $xret["retEdit"] = array();
    $xret["price_ret"] = "";

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "add_get")){
        
            $select_salesfile2="SELECT * FROM tranfile1 LEFT JOIN tranfile2
            ON tranfile1.docnum  = tranfile2.docnum
            WHERE tranfile1.trncde='PUR' AND tranfile2.itmcde='".$_POST['itmcde_add']."' ORDER BY tranfile1.trndte DESC LIMIT 1";
            $stmt_salesfile2	= $link->prepare($select_salesfile2);
            $stmt_salesfile2->execute();
            $rs_salesfile2 = $stmt_salesfile2->fetch();
            $xret["price_ret"] = $rs_salesfile2['untprc'];
    }

echo json_encode($xret);
?>