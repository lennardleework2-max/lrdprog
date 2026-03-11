<?php
session_start();
require "resources/db_init.php";
require "resources/connect4.php";
require_once("resources/lx2.pdodb.php");

$xret = array();
$is_checked = "";
$xret["html"] = "";


$select_db_useraccess1='SELECT * FROM users WHERE usercode=?';
$stmt_useraccess1	= $link->prepare($select_db_useraccess1);
$stmt_useraccess1->execute(array($_POST["usercode"]));
while($rs_useraccess1 = $stmt_useraccess1->fetch()){
    
    $usercode   = $rs_useraccess1['usercode'];
    $full_name  = $rs_useraccess1['full_name'];
    $password   = $rs_useraccess1['password'];
}



$select_db_useraccess2="SELECT * FROM menus WHERE menidx!='x' ORDER BY menidx";
$stmt_useraccess2	= $link->prepare($select_db_useraccess2);
$stmt_useraccess2->execute();
while($row_useraccess2 = $stmt_useraccess2->fetch()){
    
    if($row_useraccess2["is_removed"] == 1){
        continue;
    }

    $is_checked = "";

    //$select_db_ischecked="SELECT * FROM user_menus WHERE mencap=? AND usercode=?";
    $select_db_ischecked="SELECT * FROM user_menus WHERE mencap='".$row_useraccess2["mencap"]."' AND usercode='".$usercode."'";
    $stmt_ischecked	= $link->prepare($select_db_ischecked);
    $stmt_ischecked->execute();
    $row_ischecked = $stmt_ischecked->fetch();


    if(!empty($row_ischecked)){
        $is_checked = "checked";
    }


    //variables
    $mencap = $row_useraccess2['mencap'];
    $mencap_trim = str_replace(' ', '', $mencap);
    $mencap_trim =  str_replace('.', '', $mencap_trim);

    $mensub = $row_useraccess2['mensub'];
    $is_disabled = $row_useraccess2['is_disabled'];

    $disabled_chckbox = "";
    $checked_checkbox = "";
    
    $hidden_chk_value = 0;
    $chk_value = 1;

    

    if(!empty($is_disabled) && $is_disabled == 1){
        $disabled_chckbox = "disabled";
        $checked_checkbox = "checked";

        $hidden_chk_value = 1;
        $chk_value = 0;
    }

    if(!empty($row_useraccess2["mensub"])){

        $xret["html"].="<div class='form-group m-2 ms-2 ps-0'>";
            $xret["html"].="<div class='form-check col-6'>";
                $xret["html"].= "<input 
                                    type='hidden'
                                    value='".$hidden_chk_value."'
                                    name='menu[".$mencap."][value]'
                                >";
                $xret["html"].= "<input 
                                    type='checkbox' 
                                    value='".$chk_value."'
                                    name='menu[".$mencap."][value]' 
                                    class='form-check-input  main_".$mensub."'
                                    onclick=\"checkall_sub('".$mensub."')\"
                                    ".$is_checked."
                                    ".$disabled_chckbox."
                                    ".$checked_checkbox."
                                    >";
                $xret["html"].= "<label class='form-check-label'>";
                    $xret["html"].= "<b>".$mencap."</b>";
                $xret["html"].= "</label>";
                
            $xret["html"].="</div>";
        $xret["html"].="</div>";
    }else{

        $xret["html"].="<div class='form-group m-2'>";
            $xret["html"].="<div class='form-check col-6'>";
                $xret["html"].= "<input 
                                    type='hidden'
                                    value='".$hidden_chk_value."'
                                    name='menu[".$mencap."][value]'
                                >";
                $xret["html"].= "<input 
                                    type='checkbox' 
                                    value='".$chk_value."'
                                    name='menu[".$mencap."][value]' 
                                    class='form-check-input main_".$mencap_trim."'
                                    onclick=\"checkall_nosub('".$mencap_trim."')\"
                                    ".$is_checked."
                                    ".$disabled_chckbox."
                                    ".$checked_checkbox."
                                    >";
                $xret["html"].= "<label class='form-check-label' style='text-shadow: 0 0 0 #000000;'>";
                    $xret["html"].= $mencap;
                $xret["html"].= "</label>";
                
            $xret["html"].="</div>";
        $xret["html"].="</div>";

        if($mencap == "Home"){
            continue;
        }

        if(empty($row_useraccess2["has_crud"]) || $row_useraccess2["has_crud"]!== "Y" || $row_useraccess2["has_crud"] == "N"){
            continue;
        }

        $is_checked_add = "";
        $is_checked_edit = "";
        $is_checked_delete = "";
        $is_checked_view = "";
        $is_checked_export = "";
        
        $select_db_ischecked_crud="SELECT * FROM user_menus WHERE mencap='".$row_useraccess2["mencap"]."' AND usercode='".$usercode."' ORDER BY mennum ASC";
        $stmt_ischecked_crud	= $link->prepare($select_db_ischecked_crud);
        $stmt_ischecked_crud->execute();
        $row_ischecked_crud = $stmt_ischecked_crud->fetch();

        
        if($row_ischecked_crud["add"] == 1){
            $is_checked_add = "checked";
        }
        if($row_ischecked_crud["edit"] == 1){
            $is_checked_edit = "checked";
        }
        if($row_ischecked_crud["delete"] == 1){
            $is_checked_delete = "checked";
        }
        if($row_ischecked_crud["view"] == 1){
            $is_checked_view = "checked";
        }
        if($row_ischecked_crud["export"] == 1){
            $is_checked_export = "checked";
        }

        $xret["html"].="<div class='row mx-2'>";
            $xret["html"].="<div class='form-check col-12 col-sm-2 col-lg-1 ms-2 ms-sm-2 text-nowrap'>";
                $xret["html"].= "<input 
                    type='hidden'
                    value='".$hidden_chk_value."'
                    name='menu[".$mencap."][add]'
                >";
                $xret["html"].= "<input 
                    type='checkbox' 
                    value='".$chk_value."'
                    name='menu[".$mencap."][add]' 
                    class='form-check-input crud_add_".$mencap_trim." crud_".$mencap_trim."'
                    onclick=\"checkcrud('main','add','".$mencap_trim."')\"
                    ".$is_checked_add."
                >";    
                $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#00b33c;text-shadow: 0 0 0 #00b33c;'>";
                    $xret["html"].= "Add";
                $xret["html"].= "</label>";
            $xret["html"].="</div>";

            $xret["html"].="<div class='form-check col-12 col-lg-1 col-sm-2 ms-2 ms-sm-0'>";

                $xret["html"].= "<input 
                    type='hidden'
                    value='".$hidden_chk_value."'
                    name='menu[".$mencap."][edit]'
                >";

                $xret["html"].= "<input 
                    type='checkbox' 
                    value='".$chk_value."'
                    name='menu[".$mencap."][edit]' 
                    class='form-check-input crud_edit_".$mencap_trim." crud_".$mencap_trim."'
                    onclick=\"checkcrud('main','edit','".$mencap_trim."')\"
                    ".$is_checked_edit."
                >"; 
                $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#008ae6;text-shadow: 0 0 0 #008ae6;'>";
                    $xret["html"].= "Edit";
                $xret["html"].= "</label>";
            $xret["html"].="</div>";

        $xret["html"].="</div>";

        $xret["html"].="<div class='row mx-2'>";

            $xret["html"].="<div class='form-check col-12 col-sm-2 col-lg-1 ms-2 ms-sm-2'>";
                    $xret["html"].= "<input 
                        type='hidden'
                        value='".$hidden_chk_value."'
                        name='menu[".$mencap."][view]'
                    >";

                    $xret["html"].= "<input 
                        type='checkbox' 
                        value='".$chk_value."'
                        name='menu[".$mencap."][view]' 
                        class='form-check-input crud_view_".$mencap_trim." crud_".$mencap_trim."'
                        onclick=\"checkcrud('main','view','".$mencap_trim."')\"
                        ".$is_checked_view."
                    >"; 
                $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#7733ff;text-shadow: 0 0 0 #7733ff;'>";
                    $xret["html"].= "View";
                $xret["html"].= "</label>";
            $xret["html"].="</div>";

            $xret["html"].="<div class='form-check col-12 col-sm-2 col-lg-1 text-nowrap ms-2 ms-sm-0'>";
                $xret["html"].= "<input 
                    type='hidden'
                    value='".$hidden_chk_value."'
                    name='menu[".$mencap."][delete]'
                >";

                $xret["html"].= "<input 
                    type='checkbox' 
                    value='".$chk_value."'
                    name='menu[".$mencap."][delete]' 
                    class='form-check-input crud_delete_".$mencap_trim." crud_".$mencap_trim."'
                    onclick=\"checkcrud('main','delete','".$mencap_trim."')\"
                    ".$is_checked_delete."
                >"; 
                $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#ff3333;text-shadow: 0 0 0 #ff3333;'>";
                    $xret["html"].= "Delete";
                $xret["html"].= "</label>";
            $xret["html"].="</div>";


        $xret["html"].="</div>";


        $xret["html"].="<div class='row mx-2'>";
            $xret["html"].="<div class='form-check col-12 col-sm-2 col-lg-1 ms-2 ms-sm-2 text-nowrap'>";
                    $xret["html"].= "<input 
                        type='hidden'
                        value='".$hidden_chk_value."'
                        name='menu[".$mencap."][export]'
                    >";
                    $xret["html"].= "<input 
                        type='checkbox' 
                        value='".$chk_value."'
                        name='menu[".$mencap."][export]' 
                        class='form-check-input crud_export_".$mencap_trim." crud_".$mencap_trim."'
                        onclick=\"checkcrud('main','export','".$mencap_trim."')\"
                        ".$is_checked_export."
                    >";    
                $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#ff6600;text-shadow: 0 0 0 #ff6600;'>";
                    $xret["html"].= "Export";
                $xret["html"].= "</label>";
            $xret["html"].="</div>";
        $xret["html"].="</div>";
    }

    $select_db_submenu="SELECT * FROM menus WHERE mengrp='".$mensub."' ORDER BY mennum ASC";
    $stmt_submenu	= $link->prepare($select_db_submenu);
    $stmt_submenu->execute();
    while($row_submenu = $stmt_submenu->fetch()){

        $xcheck_mx_2 =0;
        $html_mx_2 = 'mx-2';

        $mencap_submenu = $row_submenu["mencap"];
        $mencap_submenu_trim =  str_replace(' ', '', $mencap_submenu);
        $mencap_submenu_trim =  str_replace('.', '', $mencap_submenu_trim);
        $mengrp_submenu = $row_submenu["mengrp"];
        $is_checked_sub = "";

        $select_db_ischecked_sub="SELECT * FROM user_menus WHERE mengrp='".$mensub."' AND mencap=? AND usercode=?";
        $stmt_ischecked_sub	= $link->prepare($select_db_ischecked_sub);
        $stmt_ischecked_sub->execute(array($row_submenu["mencap"],$usercode));
        $row_ischecked_sub = $stmt_ischecked_sub->fetch();

        if(!empty($row_ischecked_sub)){
            $is_checked_sub = "checked";
        }

        $is_checked_add_sub = "";
        $is_checked_edit_sub = "";
        $is_checked_delete_sub = "";
        $is_checked_view_sub = "";
        $is_checked_export_sub = "";
        $is_checked_hideprice_sub = "";

        $select_db_ischecked_crud_sub="SELECT * FROM user_menus WHERE mencap='".$row_submenu["mencap"]."' AND usercode='".$usercode."'";
        $stmt_ischecked_crud_sub	= $link->prepare($select_db_ischecked_crud_sub);
        $stmt_ischecked_crud_sub->execute();
        $row_ischecked_crud_sub = $stmt_ischecked_crud_sub->fetch();

        
        if($row_ischecked_crud_sub["add"] == 1){
            $is_checked_add_sub = "checked";
        }
        if($row_ischecked_crud_sub["edit"] == 1){
            $is_checked_edit_sub = "checked";
        }
        if($row_ischecked_crud_sub["delete"] == 1){
            $is_checked_delete_sub = "checked";
        }
        if($row_ischecked_crud_sub["view"] == 1){
            $is_checked_view_sub= "checked";
        }
        if($row_ischecked_crud_sub["export"] == 1){
            $is_checked_export_sub= "checked";
        }
        if($row_ischecked_crud_sub["hide_price"] == 1){
            $is_checked_hideprice_sub=  "checked";
        }

        if(empty($row_submenu['mensub'])){    

            $xret["html"].="<div class='form-group m-2 ms-4'>";
                $xret["html"].="<div class='form-check col-6'>";
                    $xret["html"].= "<input 
                                        type='hidden'
                                        value='0'
                                        name='menu[".$mencap_submenu."][value]'
                                    >";
                    $xret["html"].= "<input 
                                        type='checkbox' 
                                        value='1' 
                                        name='menu[".$mencap_submenu."][value]'
                                        class='form-check-input  sub_".$mengrp_submenu." sub_".$mencap_submenu_trim."'
                                        onclick=\"checksub('".$mengrp_submenu."','".$mencap_submenu_trim."')\"
                                        ".$is_checked_sub.">";
                    $xret["html"].= "<label class='form-check-label'>";
                        $xret["html"].= $mencap_submenu;
                    $xret["html"].= "</label>";
                    
                $xret["html"].="</div>";
            $xret["html"].="</div>";

                if((empty($row_submenu["has_crud"]) && $row_submenu["has_crud"]!== "Y") || $row_submenu["has_crud"] == "N"){
                    continue;
                }

            $xret["html"].="<div class='row mx-2'>";
                $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-4'>";
                        $xret["html"].= "<input 
                            type='hidden'
                            value='".$hidden_chk_value."'
                            name='menu[".$mencap_submenu."][add]'
                        >";

                        $xret["html"].= "<input 
                            type='checkbox' 
                            value='".$chk_value."'
                            name='menu[".$mencap_submenu."][add]' 
                            class='form-check-input crud_add_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_add_".$mengrp_submenu."'
                            onclick=\"checkcrud('sub','add','".$mencap_submenu_trim."','".$mengrp_submenu."')\"
                            ".$is_checked_add_sub."
                        >";    
                    $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#00b33c;text-shadow: 0 0 0 #00b33c;'>";
                        $xret["html"].= "Add";
                    $xret["html"].= "</label>";
                $xret["html"].="</div>";

                $xret["html"].="<div class='form-check col-12 col-lg-1 col-sm-2 ms-4 ms-sm-0'>";
                        $xret["html"].= "<input 
                            type='hidden'
                            value='".$hidden_chk_value."'
                            name='menu[".$mencap_submenu."][edit]'
                        >";
                        $xret["html"].= "<input 
                            type='checkbox' 
                            value='".$chk_value."'
                            name='menu[".$mencap_submenu."][edit]' 
                            class='form-check-input crud_edit_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_edit_".$mengrp_submenu."'
                            onclick=\"checkcrud('sub','edit','".$mencap_submenu_trim."','".$mengrp_submenu."')\"
                            ".$is_checked_edit_sub."
                        >"; 
                    $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#008ae6;text-shadow: 0 0 0 #008ae6;'>";
                        $xret["html"].= "Edit";
                    $xret["html"].= "</label>";
                $xret["html"].="</div>";

            $xret["html"].="</div>";

            $xret["html"].="<div class='row mx-2'>";

                $xret["html"].="<div class='form-check col-12 col-lg-1 col-sm-2 ms-4 ms-sm-4'>";
                        $xret["html"].= "<input 
                            type='hidden'
                            value='".$hidden_chk_value."'
                            name='menu[".$mencap_submenu."][view]'
                        >";

                        $xret["html"].= "<input 
                            type='checkbox' 
                            value='".$chk_value."'
                            name='menu[".$mencap_submenu."][view]' 
                            class='form-check-input crud_view_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_view_".$mengrp_submenu."'
                            onclick=\"checkcrud('sub','view','".$mencap_submenu_trim."','".$mengrp_submenu."')\"
                            ".$is_checked_view_sub."
                        >"; 
                    $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#7733ff;text-shadow: 0 0 0 #7733ff;'>";
                        $xret["html"].= "View";
                    $xret["html"].= "</label>";
                $xret["html"].="</div>";

                $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-0'>";
                        $xret["html"].= "<input 
                            type='hidden'
                            value='".$hidden_chk_value."'
                            name='menu[".$mencap_submenu."][delete]'
                        >";
                        $xret["html"].= "<input 
                            type='checkbox' 
                            value='".$chk_value."'
                            name='menu[".$mencap_submenu."][delete]' 
                            class='form-check-input crud_delete_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_delete_".$mengrp_submenu."'
                            onclick=\"checkcrud('sub','delete','".$mencap_submenu_trim."','".$mengrp_submenu."')\"
                            ".$is_checked_delete_sub."
                        >"; 
                    $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#ff3333;text-shadow: 0 0 0 #ff3333;'>";
                        $xret["html"].= "Delete";
                    $xret["html"].= "</label>";
                $xret["html"].="</div>";


            $xret["html"].="</div>";

            $xret["html"].="<div class='row mx-2'>";
                $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-4'>";
                    $xret["html"].= "<input 
                        type='hidden'
                        value='".$hidden_chk_value."'
                        name='menu[".$mencap_submenu."][export]'
                    >";
                    $xret["html"].= "<input 
                        type='checkbox' 
                        value='".$chk_value."'
                        name='menu[".$mencap_submenu."][export]' 
                        class='form-check-input crud_export_".$mencap_submenu_trim."  crud_export_".$mengrp_submenu." crud_".$mencap_submenu_trim."'
                        onclick=\"checkcrud('sub','export','".$mencap_submenu_trim."','".$mengrp_submenu."')\"
                        ".$is_checked_export_sub."
                    >";    
                $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#ff6600;text-shadow: 0 0 0 #ff6600;'>";
                    $xret["html"].= "Export";
                $xret["html"].= "</label>";
                $xret["html"].="</div>";

                if($row_submenu["mencap"] == "Purchases Transaction"){
                    $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-0'>";
                  
                            $xret["html"].= "<input 
                                type='hidden'
                                value='".$hidden_chk_value."'
                                name='menu[".$mencap_submenu."][hide_price]'
                            >";
                            $xret["html"].= "<input 
                                type='checkbox' 
                                value='".$chk_value."'
                                name='menu[".$mencap_submenu."][hide_price]' 
                                class='form-check-input crud_hideprice".$mencap_submenu_trim."  crud_hideprice_".$mengrp_submenu." crud_".$mencap_submenu_trim." crud_hide_price_".$row_submenu['mengrp']."'
                                onclick=\"checkcrud_sub('sub','hide_price','".$mencap_submenu_trim."','".$mengrp_submenu."','".$row_submenu['mengrp']."')\"
                                ".$is_checked_hideprice_sub."
                            >";    
                        $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#003300;text-shadow: 0 0 0 #003300;'>";
                            $xret["html"].= "Hide Price";
                        $xret["html"].= "</label>";
                    $xret["html"].="</div>";
              
                }   
                                
            $xret["html"].="</div>";

          

            // $xcheck_mx_2 = 1;

        }else{

            $checked_checkbox = '';
            $checked_checkbox = '';

            $xret["html"].="<div class='form-group m-2 ms-4'>";
                $xret["html"].="<div class='form-check col-6'>";
                    $xret["html"].= "<input 
                                        type='hidden'
                                        value='".$hidden_chk_value."'
                                        name='menu[".$row_submenu['mencap']."][value]'
                                    >";
                    $xret["html"].= "<input 
                                        type='checkbox' 
                                        value='".$chk_value."'
                                        name='menu[".$row_submenu['mencap']."][value]' 
                                        class='form-check-input  main_".$row_submenu['mensub']." sub_".$row_submenu['mengrp']."'
                                        onclick=\"checkall_sub_sub('".$row_submenu['mensub']."','".$row_submenu['mengrp']."')\"
                                        ".$is_checked_sub."
                                        ".$disabled_chckbox."
                                        ".$checked_checkbox."
                                        >";
                    $xret["html"].= "<label class='form-check-label'>";
                        $xret["html"].= "<b>".$row_submenu['mencap']."</b>";
                    $xret["html"].= "</label>";
                    
                $xret["html"].="</div>";
            $xret["html"].="</div>";

            $select_db_submenu2="SELECT * FROM menus WHERE mengrp='".$row_submenu['mensub']."' AND is_removed!='1' ORDER BY mennum ASC";
            $stmt_submenu2	= $link->prepare($select_db_submenu2);
            $stmt_submenu2->execute();
            while($row_submenu2 = $stmt_submenu2->fetch()){

                $mencap_submenu = $row_submenu2['mencap'];
                $mengrp_submenu = $row_submenu2['mengrp'];
                $mencap_submenu_trim =  str_replace(' ', '', $mencap_submenu);
                $mencap_submenu_trim =  str_replace('.', '', $mencap_submenu_trim);

                // $select_db_ischecked_sub="SELECT * FROM user_menus WHERE mengrp='".$mensub."' AND mencap=? AND usercode=?";
                // $stmt_ischecked_sub	= $link->prepare($select_db_ischecked_sub);
                // $stmt_ischecked_sub->execute(array($row_submenu2["mencap"],$usercode));
                // $row_ischecked_sub = $stmt_ischecked_sub->fetch();
        
                // if(!empty($row_ischecked_sub)){
                //     $is_checked_sub = "checked";
                // }
        
                $is_checked_add_sub = "";
                $is_checked_edit_sub = "";
                $is_checked_delete_sub = "";
                $is_checked_view_sub = "";
                $is_checked_export_sub = "";
                $is_checked_sub2 = "";
                
                $select_db_ischecked_crud_sub="SELECT * FROM user_menus WHERE mencap='".$row_submenu2["mencap"]."' AND usercode='".$usercode."'";
                $stmt_ischecked_crud_sub	= $link->prepare($select_db_ischecked_crud_sub);
                $stmt_ischecked_crud_sub->execute();
                $row_ischecked_crud_sub = $stmt_ischecked_crud_sub->fetch();

                if(!empty($row_ischecked_crud_sub)){
                    $is_checked_sub2 = "checked";
                }
                
                if($row_ischecked_crud_sub["add"] == 1){
                    $is_checked_add_sub = "checked";
                }
                if($row_ischecked_crud_sub["edit"] == 1){
                    $is_checked_edit_sub = "checked";
                }
                if($row_ischecked_crud_sub["delete"] == 1){
                    $is_checked_delete_sub = "checked";
                }
                if($row_ischecked_crud_sub["view"] == 1){
                    $is_checked_view_sub= "checked";
                }
                if($row_ischecked_crud_sub["export"] == 1){
                    $is_checked_export_sub= "checked";
                }
    
                    $xret["html"].="<div class='form-group m-2 ms-5'>";
                        $xret["html"].="<div class='form-check col-6'>";
                            $xret["html"].= "<input 
                                                type='hidden'
                                                value='0'
                                                name='menu[".$mencap_submenu."][value]'
                                            >";
                            $xret["html"].= "<input 
                                                type='checkbox' 
                                                value='1' 
                                                name='menu[".$mencap_submenu."][value]'
                                                class='form-check-input  sub_".$mengrp_submenu." sub_".$mencap_submenu_trim." sub_".$row_submenu['mengrp']."2'
                                                onclick=\"checksub_sub('".$mengrp_submenu."','".$mencap_submenu_trim."','".$row_submenu['mengrp']."')\"
                                                ".$is_checked_sub2.">";
                            $xret["html"].= "<label class='form-check-label'>";
                                $xret["html"].= $row_submenu2['mencap'];
                            $xret["html"].= "</label>";
                            
                        $xret["html"].="</div>";
                    $xret["html"].="</div>";
            
                    if((empty($row_submenu2["has_crud"]) && $row_submenu2["has_crud"]!== "Y") || $row_submenu2["has_crud"] == "N"){
                        continue;
                    }
        
                    $xret["html"].="<div class='row mx-2 ps-4'>";
                        $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-4'>";
                                $xret["html"].= "<input 
                                    type='hidden'
                                    value='".$hidden_chk_value."'
                                    name='menu[".$mencap_submenu."][add]'
                                >";
            
                                $xret["html"].= "<input 
                                    type='checkbox' 
                                    value='".$chk_value."'
                                    name='menu[".$mencap_submenu."][add]' 
                                    class='form-check-input crud_add_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_add_".$mengrp_submenu." crud_add_".$row_submenu['mengrp']."'
                                    onclick=\"checkcrud_sub('sub','add','".$mencap_submenu_trim."','".$mengrp_submenu."','".$row_submenu['mengrp']."')\"
                                    ".$is_checked_add_sub."
                                >";    
                            $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#00b33c;text-shadow: 0 0 0 #00b33c;'>";
                                $xret["html"].= "Add";
                            $xret["html"].= "</label>";
                        $xret["html"].="</div>";
            
                        $xret["html"].="<div class='form-check col-12 col-lg-1 col-sm-2 ms-4 ms-sm-0'>";
                                $xret["html"].= "<input 
                                    type='hidden'
                                    value='".$hidden_chk_value."'
                                    name='menu[".$mencap_submenu."][edit]'
                                >";
                                $xret["html"].= "<input 
                                    type='checkbox' 
                                    value='".$chk_value."'
                                    name='menu[".$mencap_submenu."][edit]' 
                                    class='form-check-input crud_edit_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_edit_".$mengrp_submenu." crud_edit_".$row_submenu['mengrp']."'
                                    onclick=\"checkcrud_sub('sub','edit','".$mencap_submenu_trim."','".$mengrp_submenu."','".$row_submenu['mengrp']."')\"
                                    ".$is_checked_edit_sub."
                                >"; 
                            $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#008ae6;text-shadow: 0 0 0 #008ae6;'>";
                                $xret["html"].= "Edit";
                            $xret["html"].= "</label>";
                        $xret["html"].="</div>";
            
                    $xret["html"].="</div>";
            
                    $xret["html"].="<div class='row mx-2 ps-4'>";
            
                        $xret["html"].="<div class='form-check col-12 col-lg-1 col-sm-2 ms-4 ms-sm-4'>";
                                $xret["html"].= "<input 
                                    type='hidden'
                                    value='".$hidden_chk_value."'
                                    name='menu[".$mencap_submenu."][view]'
                                >";
            
                                $xret["html"].= "<input 
                                    type='checkbox' 
                                    value='".$chk_value."'
                                    name='menu[".$mencap_submenu."][view]' 
                                    class='form-check-input crud_view_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_view_".$mengrp_submenu." crud_view_".$row_submenu['mengrp']."'
                                    onclick=\"checkcrud_sub('sub','view','".$mencap_submenu_trim."','".$mengrp_submenu."','".$row_submenu['mengrp']."')\"
                                    ".$is_checked_view_sub."
                                >"; 
                            $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#7733ff;text-shadow: 0 0 0 #7733ff;'>";
                                $xret["html"].= "View";
                            $xret["html"].= "</label>";
                        $xret["html"].="</div>";
            
                        $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-0'>";
                                $xret["html"].= "<input 
                                    type='hidden'
                                    value='".$hidden_chk_value."'
                                    name='menu[".$mencap_submenu."][delete]'
                                >";
                                $xret["html"].= "<input 
                                    type='checkbox' 
                                    value='".$chk_value."'
                                    name='menu[".$mencap_submenu."][delete]' 
                                    class='form-check-input crud_delete_".$mencap_submenu_trim." crud_".$mencap_submenu_trim." crud_delete_".$mengrp_submenu." crud_delete_".$row_submenu['mengrp']."'
                                    onclick=\"checkcrud_sub('sub','delete','".$mencap_submenu_trim."','".$mengrp_submenu."','".$row_submenu['mengrp']."')\"
                                    ".$is_checked_delete_sub."
                                >"; 
                            $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#ff3333;text-shadow: 0 0 0 #ff3333;'>";
                                $xret["html"].= "Delete";
                            $xret["html"].= "</label>";
                        $xret["html"].="</div>";
            
            
                    $xret["html"].="</div>";
            
                    $xret["html"].="<div class='row mx-2 ps-4'>";
                        $xret["html"].="<div class='form-check text-nowrap col-12 col-lg-1 col-sm-2 ms-4 ms-sm-4'>";
                            $xret["html"].= "<input 
                                type='hidden'
                                value='".$hidden_chk_value."'
                                name='menu[".$mencap_submenu."][export]'
                            >";
                            $xret["html"].= "<input 
                                type='checkbox' 
                                value='".$chk_value."'
                                name='menu[".$mencap_submenu."][export]' 
                                class='form-check-input crud_export_".$mencap_submenu_trim."  crud_export_".$mengrp_submenu." crud_".$mencap_submenu_trim." crud_export_".$row_submenu['mengrp']."'
                                onclick=\"checkcrud_sub('sub','export','".$mencap_submenu_trim."','".$mengrp_submenu."','".$row_submenu['mengrp']."')\"
                                ".$is_checked_export_sub."
                            >";    
                        $xret["html"].= "<label class='form-check-label' style='font-size:16px;color:#ff6600;text-shadow: 0 0 0 #ff6600;'>";
                            $xret["html"].= "Export";
                        $xret["html"].= "</label>";
                    $xret["html"].="</div>";
                $xret["html"].="</div>";

                $is_checked_sub = ""; 
            }
        }



    }


}

echo json_encode($xret);
?>
