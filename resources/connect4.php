<?php

if(function_exists('session_status')) {
    if(session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
} elseif(!isset($_SESSION)) {
    @session_start();
}


try

{


    if(isset(db_init::$dbmode_singledb) && db_init::$dbmode_singledb == "N"){

        $link_appsys = new PDO("mysql:dbname=".db_init::$syspar_db_name.";host=".db_init::$syspar_host."","".db_init::$syspar_username."","".db_init::$syspar_password."");
        $link_appsys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $select_db_conn="SELECT * FROM ".db_init::$syspar_db_tablename." WHERE comp_code=?";

        $comp_code = isset($_SESSION["comp_code"]) ? trim((string)$_SESSION["comp_code"]) : "";
        if($comp_code === ""){
            throw new Exception("Missing session comp_code for multi-db mode");
        }

        $stmt_conn	= $link_appsys->prepare($select_db_conn);
        $stmt_conn->execute(array($comp_code));
        $row_conn = $stmt_conn->fetch();

        if(!empty($row_conn)){

                $auth_dbhost =   $row_conn['db_host'];
                $auth_dbusername = $row_conn['db_username'];
                $auth_dbuserpassword = $row_conn['db_pass'];
                $auth_dbname =$row_conn['db_dbname'];

                $auth_cnstr = "mysql:host=$auth_dbhost; dbname=$auth_dbname";
                $dboptions = array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true);

                $link = new PDO($auth_cnstr, $auth_dbusername, $auth_dbuserpassword, $dboptions);
                $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //$link = new PDO("mysql:dbname=".$auth_dbname.";host=".$auth_dbhost."","".$auth_dbusername."","".$auth_dbuserpassword."");		
        }else{
            echo "Connection Failed";
            die();
        }
        
    }else{
        //$link = new PDO('mysql:dbname=CHANGE THIS;host=localhost','lstuser_lennard', 'lstV@2021');	
        $link = new PDO("mysql:dbname=".db_init::$dbholder_db_name.";host=".db_init::$dbholder_host."","".db_init::$dbholder_username."","".db_init::$dbholder_password."");
        $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
catch(Exception $e)
{          
    error_log("connect4.php: " . $e->getMessage());
    echo "Connection Failed";
    die();
}

?>
