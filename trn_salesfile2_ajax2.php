<?php

    // ini_set('display_errors', '1');

    // ini_set('display_startup_errors', '1');

    // error_reporting(E_ALL);     



    session_start();

    

    require_once("resources/db_init.php");

    require "resources/connect4.php";

    require "resources/stdfunc100.php";

    require "resources/lx2.pdodb.php";





    //if(isset($_POST['event_action']) && $_POST['event_action'] == 'wholesale_add'){





    $xret = array();

        

        //$select_buyer="SELECT * FROM mf_buyers LEFT JOIN mf_salesman ON  WHERE buyer_id='".$_POST['xbuyer']."'";



        $select_buyer="SELECT buyer_address,mf_buyers.salesman_id as salesman_id,salesman_name,mf_buyers.route_id,route_desc FROM mf_buyers 
        LEFT JOIN mf_salesman ON mf_buyers.salesman_id = mf_salesman.salesman_id 
        LEFT JOIN mf_routes ON mf_buyers.route_id = mf_routes.route_id 
        WHERE buyer_id='".$_POST['xbuyer']."'";

        $stmt_buyer	= $link->prepare($select_buyer);

        $stmt_buyer->execute();

        $rs_buyer = $stmt_buyer->fetch();

        //var_dump($rs_buyer);



        $xret['buyer_address'] = $rs_buyer['buyer_address'];

        $xret['salesman_name'] = $rs_buyer['salesman_name'];

        $xret['salesman_id'] = $rs_buyer['salesman_id'];
        $xret['route_desc'] = $rs_buyer['route_desc'];
        $xret['route_id'] = $rs_buyer['route_id'];

        //var_dump($xret);



        /*

        $select_salesman="SELECT * FROM mf_buyers WHERE buyeid='".$_POST['xbuyer']."'";

        $stmt_buyer	= $link->prepare($select_buyer);

        $stmt_buyer->execute();

        $rs_buyer = $stmt_buyer->fetch();

        $xret['buyer_address'] = $rs_buyer['buyer_address'];

        $xret['salesman_id'] = $rs_buyer['salesman_id'];

        */



        echo json_encode($xret);

        return;



    //}

    

?>