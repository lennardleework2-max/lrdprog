<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

$default_unmcde = "UNM-00000001";

try{
    $link->beginTransaction();

    $sql_itemfile = "UPDATE itemfile SET unmcde = NULL";
    $stmt_itemfile = $link->prepare($sql_itemfile);
    $stmt_itemfile->execute(array($default_unmcde));
    $itemfile_count = $stmt_itemfile->rowCount();

    // $sql_tranfile2 = "UPDATE tranfile2 SET unmcde = ?";
    // $stmt_tranfile2 = $link->prepare($sql_tranfile2);
    // $stmt_tranfile2->execute(array($default_unmcde));
    // $tranfile2_count = $stmt_tranfile2->rowCount();


    $sql_tranfile3 = "UPDATE purchasesorderfile2 SET unmcde = ?";
    $stmt_tranfile3 = $link->prepare($sql_tranfile3);
    $stmt_tranfile3->execute(array($default_unmcde));
    $tranfile3_count = $stmt_tranfile3->rowCount();

    $sql_tranfile4 = "UPDATE salesorderfile2 SET unmcde = ?";
    $stmt_tranfile4 = $link->prepare($sql_tranfile4);
    $stmt_tranfile4->execute(array($default_unmcde));
    $tranfile4_count = $stmt_tranfile4->rowCount();

    $link->commit();

    echo "Default UOM applied successfully.<br>";
    echo "itemfile rows updated: ".$itemfile_count."<br>";
    echo "tranfile2 rows updated: ".$tranfile2_count."<br>";
    echo "Assigned value: ".$default_unmcde;
}catch(Exception $e){
    if($link->inTransaction()){
        $link->rollBack();
    }

    echo "Failed to update default UOM.<br>";
    echo $e->getMessage();
}
?>
