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
    $xret["html_mobile"] = "";
    $xret["trntot"] = "";
    $xret["msg"] = "";
    $xret["status"] = 1;
    $xret["error1"] = 0;
    $xret["error2"] = 0;
    $xret["error3"] = 0;
    $xret["error4"] = 0;
    $xret["computed_compay"] = "0.00";
    $xret["commission_percent"] = '';
    $xret["itm_search"] = '';
    $trncde = 'SAL';


    $xret["retEdit"] = array();

    if(isset($_POST['sel_salesman_id']) && !empty($_POST['sel_salesman_id'])){
        $select_db_getsalesman="SELECT * FROM mf_salesman where salesman_id=?";
        $stmt_getsalesman	= $link->prepare($select_db_getsalesman);
        $stmt_getsalesman->execute(array($_POST['sel_salesman_id']));
        $rs_getsalesman = $stmt_getsalesman->fetch();

        if($rs_getsalesman){
            $comm_percent = is_numeric($rs_getsalesman['commission']) ? (float)$rs_getsalesman['commission'] : 0;
            $xret["commission_percent"] = $comm_percent;

            $posted_trntot = isset($_POST['trntot']) ? $_POST['trntot'] : 0;
            $clean_trntot = str_replace(',', '', $posted_trntot);
            $clean_trntot = is_numeric($clean_trntot) ? (float)$clean_trntot : 0;

            $computed_compay = ($comm_percent/100) * $clean_trntot;
            $xret["computed_compay"] = number_format($computed_compay, 2, '.', '');
        }
    }

echo json_encode($xret);
?>
