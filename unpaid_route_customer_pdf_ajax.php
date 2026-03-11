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
    $xret["itm_search"] = '';
    $trncde = 'SAL';


    $xret["retEdit"] = array();

    if(isset($_POST["event_action"]) && ($_POST["event_action"] == "search_itm" || $_POST["event_action"] == "search_itm")){

        if($xret["status"] == 1){

            if(isset($_POST['search_itm']) && !empty($_POST['search_itm'])){
                $xret["itm_search"] = $_POST['search_itm'];
            }else{
                $xret["itm_search"] = '';
            }

            $xret["html"] = "<table class='table table striped'>";

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td class='fw-bold'>";
                        $xret["html"] .= "Customer Name";             
                    $xret["html"] .= "</td>";

                    $xret["html"] .= "<td class='fw-bold text-center'>";
                        $xret["html"] .= "Action";             
                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";

            $xfilter = "";

            if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
                $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
                $xfilter .= " AND tranfile1.trndte>='".$_POST['date_from']."'";
            }
            
            if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
                $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
                $xfilter .= " AND tranfile1.trndte<='".$_POST['date_to']."'";
            }

            //filtering item
            if(isset($_POST['search_cus']) && !empty($_POST['search_cus'])){
                $xfilter .= " AND mf_buyers.buyer_name LIKE '%".$_POST['search_cus']."%'";
            }

            

            //select the empty salesman
            $select_db3 = "SELECT * FROM mf_salesman WHERE salesman_name='-None'";
            $stmt3= $link->prepare($select_db3);
            $stmt3->execute();
            $rs3 = $stmt3->fetch();

            //select the empty route
            $select_db_route = "SELECT * FROM mf_routes WHERE route_desc='-'";
            $stmt_route= $link->prepare($select_db_route);
            $stmt_route->execute();
            $rs_route = $stmt_route->fetch();

            $select_itemfile="SELECT *, mf_buyers.buyer_id as 'mf_buyers_buyer_id', mf_buyers.buyer_name as 'mf_buyers_buyer_name', tranfile1.route_id as 'route_id'
            FROM tranfile1 
            LEFT JOIN mf_buyers 
            ON tranfile1.buyer_id = mf_buyers.buyer_id 
            WHERE true ".$xfilter." AND (tranfile1.paydate IS NULL or tranfile1.paydate ='') AND tranfile1.salesman_id !='".$rs3['salesman_id']."' AND tranfile1.route_id !='".$rs_route['route_id']."' AND tranfile1.buyer_id IS NOT NULL GROUP BY tranfile1.buyer_id";
            $stmt_itemfile	= $link->prepare($select_itemfile);
            $stmt_itemfile->execute();
            while($rs_itemfile = $stmt_itemfile->fetch()){

                $xret["html"] .= "<tr>";
                    $xret["html"] .= "<td>";
                        $xret["html"] .= $rs_itemfile['mf_buyers_buyer_name'];
                    $xret["html"] .= "</td>";
                    $xret["html"] .= "<td class='text-center'>";
                                        // Use a single-quoted HTML attribute so the json_encode() double quotes are safe
                            $xret["html"] .= '<button type="button" onclick=\'select_item_modal('
                            . json_encode('select') . ', '
                            . json_encode($rs_itemfile['mf_buyers_buyer_name']) . ', '
                            . json_encode($rs_itemfile['mf_buyers_buyer_id']). ','
                            . json_encode($rs_itemfile['route_id'])
                            . ')\' class="btn btn-primary fw-bold select_item">Select</button>';

                    $xret["html"] .= "</td>";
                $xret["html"] .= "</tr>";
            };

            $xret["html"] .= "</table>";
        }





        echo json_encode($xret);
        return;
    }     




echo json_encode($xret);
?>