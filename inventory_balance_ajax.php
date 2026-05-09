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

    if(isset($_POST['date_search']) && !empty($_POST['date_search'])){
        $_POST['date_search']  = (empty($_POST['date_search'])) ? NULL :  date("Y-m-d", strtotime($_POST['date_search']));
        $xfilter2 .= " AND tranfile1.trndte<='".$_POST['date_search']."'";
    }

    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND itemfile.itmcde='".$_POST['item']."'";
    }

    $select_db = "SELECT itemfile.itmcde,
            balance_data.xsum
        FROM itemfile
        LEFT JOIN (
            SELECT tranfile2.itmcde,
                SUM(tranfile2.stkqty) AS xsum
            FROM tranfile2
            LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
            WHERE 1=1 ".$xfilter2."
            GROUP BY tranfile2.itmcde
        ) balance_data ON balance_data.itmcde = itemfile.itmcde
        WHERE true ".$xfilter."
        LIMIT 1";
    $stmt_main	= $link->prepare($select_db);
    $stmt_main->execute();
    while($rs_main = $stmt_main->fetch()){    
        $xret["itm_total"] = $rs_main['xsum'];
    }

$xerror = array();
$xerror["error1"] = "";
$xerror["error2"] = "";
$xerror["error3"] = "";

header('Content-Type: application/json');
echo json_encode($xret);
?>
