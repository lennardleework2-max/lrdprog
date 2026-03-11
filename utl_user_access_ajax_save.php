<?php
session_start();
require "resources/db_init.php";
require "resources/connect4.php";
require_once("resources/lx2.pdodb.php");

$xret = array();
$is_checked = "";
$xret["html"] = "";
$xret["return"] = false;



$userdesc = $_SESSION["userdesc"];

if(isset($_POST["event_action"]) && $_POST["event_action"]=="save"){

    $select_db_userfile2="SELECT * FROM users WHERE usercode=?";
    $stmt_userfile2	= $link->prepare($select_db_userfile2);
    $stmt_userfile2->execute(array($_POST["usercode"]));
    while($rs_userfile2 = $stmt_userfile2->fetch()){
        $recid_userfile2 = $rs_userfile2["recid"];
    }

    $arr_record_userfile2 = array();			
    $arr_record_userfile2['full_name']  = $_POST['fullname_access'];
    PDO_UpdateRecord($link,"users",$arr_record_userfile2,"recid = ?",array($recid_userfile2)); 


    if(isset($_POST["menu"])){

        $xreturn_u_access = false;

        foreach ($_POST["menu"] as $key => $value) {

            if($key == "Users" && (int)$value["value"] == 0){
                $xreturn_u_access = true;
            }
            

            $username = $_POST["username_access"];
            $usercode = $_POST["usercode"];

            if($value['value'] == 0){
                $delete_query="DELETE FROM user_menus WHERE mencap=? AND usercode=?";
                $xstmt=$link->prepare($delete_query);
                $xstmt->execute(array($key,$usercode));
            }else{


                if(isset($value["add"])){
    
                }else{
                    $value["add"] = 0;
                    $value["edit"] = 0;
                    $value["delete"] = 0;
                    $value["view"] = 0;
                    $value["export"] = 0;
                    $value["hide_price"] = 0;
                }

                $select_db_menusave_check="SELECT * FROM user_menus WHERE mencap=? AND usercode=?";
                $stmt_menusave_check	= $link->prepare($select_db_menusave_check);
                $stmt_menusave_check->execute(array($key,$usercode));
                $row_menusave_check = $stmt_menusave_check->fetchAll();

                if(count($row_menusave_check) == 0){

                    $select_db_menusave="SELECT * FROM menus WHERE mencap=?";
                    $stmt_menusave	= $link->prepare($select_db_menusave);
                    $stmt_menusave->execute(array($key));
                    while($rs_menusave = $stmt_menusave->fetch()){
                        
                        $arr_record = array();
                        $arr_record['mencap'] 	    = $key;
                        $arr_record['menprogram'] 	= $rs_menusave["menprogram"];
                        $arr_record['menlogo'] 	    = $rs_menusave["menlogo"];
                        $arr_record['menidx'] 	    = $rs_menusave["menidx"];
                        $arr_record['mennum'] 	    = $rs_menusave["mennum"];
                        $arr_record['mensub'] 	    = $rs_menusave["mensub"];
                        $arr_record['mengrp'] 	    = $rs_menusave["mengrp"];
                        $arr_record['is_removed'] 	= $rs_menusave["is_removed"];
                        $arr_record['add'] 	        = $value["add"];
                        $arr_record['edit'] 	    = $value["edit"];
                        $arr_record['delete'] 	    = $value["delete"];
                        $arr_record['view'] 	    = $value["view"];
                        $arr_record['export'] 	    = $value["export"];
                        $arr_record['usercode'] 	= $usercode;
                        if(isset($value["hide_price"])){
                            $arr_record['hide_price'] 	= $value["hide_price"];
                        }
                
                        PDO_InsertRecord($link,'user_menus',$arr_record,false);

                    }
                }else{

                    $select_db_menusave_crud="SELECT * FROM user_menus WHERE mencap=? AND usercode=?";
                    $stmt_menusave_crud	= $link->prepare($select_db_menusave_crud);
                    $stmt_menusave_crud->execute(array($key,$usercode));
                    while($rs_menusave_crud = $stmt_menusave_crud->fetch()){

                        $arr_record_crud = array();
                        $arr_record_crud['add'] 	    = $value["add"];
                        $arr_record_crud['edit'] 	    = $value["edit"];
                        $arr_record_crud['delete'] 	    = $value["delete"];
                        $arr_record_crud['view'] 	    = $value["view"];
                        $arr_record_crud['export'] 	    = $value["export"];
                        if(isset($value["hide_price"])){
                            $arr_record_crud['hide_price'] 	= $value["hide_price"];
                        }
                        PDO_UpdateRecord($link,'user_menus',$arr_record_crud, "recid=?",array($rs_menusave_crud["recid"]),false);
                    }

                }
            


            }


        }
        if($xreturn_u_access == true && $userdesc !=='admin'){
            $xret["return"] = true;
        }
        
    }


}


echo json_encode($xret);
?>
