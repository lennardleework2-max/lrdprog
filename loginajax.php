<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require_once('resources/db_init.php');
require_once('resources/lx2.pdodb.php');

$username=$_POST['username'];
$password=$_POST['password'];
$comp_code = $_POST['comp_code'];

$ret=array();
$ret['msg']='';
$ret['user']='';

$ret["error1"] = false;
$ret["error2"] = false;

if(isset(db_init::$dbmode_singledb) && db_init::$dbmode_singledb == 'N'){

    $link_appsys = new PDO("mysql:dbname=".db_init::$syspar_db_name.";host=".db_init::$syspar_host."","".db_init::$syspar_username."","".db_init::$syspar_password."");

    $select_db_sys="SELECT * FROM ".db_init::$syspar_db_tablename." WHERE BINARY comp_code='".$comp_code."'";
    $stmt_sys	= $link_appsys->prepare($select_db_sys);
    $stmt_sys->execute();
    $row_sys = $stmt_sys->fetch();
    
    if(empty($row_sys)){
        $ret["msg"] = "Invalid company code.";
        header('Content-Type: application/json');
        echo json_encode($ret);
        return;
    
    }else{
        $_SESSION["comp_code"] = $comp_code;
        $_SESSION["db_dbname"] = $row_sys["db_dbname"];
    }
    
}

require_once('resources/connect4.php');

$select_db="SELECT * FROM users WHERE BINARY userdesc='".$username."'";
$stmt	= $link->prepare($select_db);
$stmt->execute();
$row = $stmt->fetchAll();

if (count($row) == 0) {

    $ret['msg']="Invalid username or password."; 
    $ret["error1"] = true;
    $xtrndte = date("Y-m-d H:i:s");
    $xactivity = "Login";
    $xremarks = "No such User";
    //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme);
    PDO_UserActivityLog($link, $username, '', $xtrndte, '', $xactivity, $username, $xremarks , 0, '', '', '','',$username);
}

else {

    $select_db="SELECT * FROM users WHERE BINARY userdesc='".$username."'";
    $stmt	= $link->prepare($select_db);
    $stmt->execute();
    while($rs = $stmt->fetch()){
        
        $recid_select=$rs['recid'];
        $userdesc_select=$rs['userdesc'];
        $usercode_select=$rs['usercode'];
        $password_select=$rs['password'];  
        $xfullname = $rs["full_name"];

        if(!password_verify($password,$password_select)){

            $ret['msg']="Invalid username or password.";        
            $ret["error1"] = true;
            $xtrndte = date("Y-m-d H:i:s");
            $xactivity = "Login";
            $xremarks = "Wrong Password";
            //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme);
            PDO_UserActivityLog($link, $username, '', $xtrndte, '', $xactivity, $username, $xremarks , 0, '', '', '','',$username);
        }

        else if(password_verify($password,$password_select)){

            $select_db_syspar='SELECT * FROM syspar';
            $stmt_syspar	= $link->prepare($select_db_syspar);
            $stmt_syspar->execute(array($username));
            $rs_syspar = $stmt_syspar->fetch();

            $select_db_menu="SELECT * FROM user_menus WHERE menprogram=? AND usercode=?";
            $stmt_menu	= $link->prepare($select_db_menu);
            $stmt_menu->execute(array($rs_syspar["landing_page"],$usercode_select));
            $rs_menu = $stmt_menu->fetch();
            if(!empty($rs_menu) || $userdesc_select == "admin"){
                $ret["landing_page"] = $rs_syspar["landing_page"];
                
                $_SESSION['userdesc'] = $userdesc_select;
                $_SESSION['usercode'] = $usercode_select;

                $_SESSION['recid']=$recid_select;
                $_SESSION['password']=$password_select;
    
                $ret['msg']="successful";
                $ret['user']=$userdesc_select;
                $ret['recid']=$recid_select;
    
                $xtrndte = date("Y-m-d H:i:s");
                $xactivity = "Login";
                $xremarks = "Successfull login";
                //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme);
                PDO_UserActivityLog($link, $userdesc_select, '', $xtrndte, '', $xactivity, $xfullname, $xremarks , 0, '', '', '','',$userdesc_select);
            }else{
                $select_db_menucheck="SELECT * FROM user_menus WHERE usercode=? AND menprogram!='' ORDER BY mencap ASC LIMIT 1";
                $stmt_menucheck	= $link->prepare($select_db_menucheck);
                $stmt_menucheck->execute(array($usercode_select));
                $rs_menucheck = $stmt_menucheck->fetch();

                if(!empty($rs_menucheck) || $userdesc_select == "admin"){
                    $ret["landing_page"] = $rs_menucheck["menprogram"];

                    $_SESSION['userdesc'] = $userdesc_select;
                    $_SESSION['usercode'] = $usercode_select;

                    $_SESSION['recid']=$recid_select;
                    $_SESSION['password']=$password_select;
        
                    $ret['msg']="successful";
                    $ret['user']=$userdesc_select;
                    $ret['recid']=$recid_select;
        
                    $xtrndte = date("Y-m-d H:i:s");
                    $xactivity = "Login";
                    $xremarks = "Successfull login";
                    //PDO_UserActivityLog($link, $xusrcde, $xusrname, $xtrndte, $xprog_module, $xactivity, $xfullname, $xremarks , $linenum, $parameter, $trncde, $trndsc, $compname, $xusrnme);
                    PDO_UserActivityLog($link, $userdesc_select, '', $xtrndte, '', $xactivity, $xfullname, $xremarks , 0, '', '', '','',$userdesc_select);
                }else{
                    if($ret["error1"] == true){

                        $ret['msg'] = $ret['msg']."No menus accesible, please contact your administrator.";        
                        $ret["error1"] = true;
                    }else{

                        $ret['msg']="No menus accesible, please contact your administrator.";        
                        $ret["error1"] = true;
                    }
                }
            }
            
        }

    }
}

header('Content-Type: application/json');
echo json_encode($ret);
?>
