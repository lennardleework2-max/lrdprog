<?php 
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

session_start();
require_once("resources/lx2.pdodb.php");
require "resources/db_init.php";
require "resources/connect4.php";
require "resources/stdfunc100.php";


$xret = array();
$xret["status"] = 1;
$xret["itm_total"] = '';


$xfilter = '';
$xfilter2 = '';
$xorder = '';

    if(isset($_POST['date_search']) && !empty($_POST['date_search'])){
        $_POST['date_search']  = (empty($_POST['date_search'])) ? NULL :  date("Y-m-d", strtotime($_POST['date_search']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_search']."'";
    }

    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= "AND itmcde='".$_POST['item']."'";
    }


    $select_db = "SELECT * FROM itemfile WHERE true ".$xfilter;
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    $grand_total = 0;
    while($rs_main = $stmt_main->fetch()){    

        // $select_db2 = "SELECT SUM(stkqty) as xsum FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum= tranfile2.docnum WHERE itmcde='".$rs_main['itmcde']."'";
        $select_db2 = "SELECT SUM(stkqty) as xsum, itemfile.itmdsc as itemfile_itmdsc FROM tranfile2 LEFT JOIN tranfile1 ON tranfile1.docnum= tranfile2.docnum LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde WHERE itemfile.itmcde='".$rs_main['itmcde']."' ".$xfilter2."";
        $stmt_main2	= $link->prepare($select_db2);
        $stmt_main2->execute(array());
        $rs2 = $stmt_main2->fetch();

        $item_total =  $rs2['xsum'];

        $xret["itm_total"] =  $item_total;

        
    }

$xerror = array();
$xerror["error1"] = "";
$xerror["error2"] = "";
$xerror["error3"] = "";

header('Content-Type: application/json');
echo json_encode($xret);
?>