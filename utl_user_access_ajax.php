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

require_once("includes/menu_tree.php");
require_once("utl_user_access_lib.php");

// full menu tree (admin view = every menu), ticked from this user's user_menus rows
$menu_access = menu_load_tree($link, '', true, '');

$stmt_user_menus = $link->prepare("SELECT * FROM user_menus WHERE usercode=?");
$stmt_user_menus->execute(array($usercode));
$user_rows = array();
foreach($stmt_user_menus->fetchAll(PDO::FETCH_ASSOC) as $row_user_menu){
    $user_rows[$row_user_menu['mencap']] = $row_user_menu;
}

$xret["html"] = ua_render_nodes($menu_access['modules'], $user_rows);

echo json_encode($xret);
?>
