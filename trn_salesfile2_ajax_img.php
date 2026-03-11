<?php
session_start();
    
require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/stdfunc100.php";
require "resources/lx2.pdodb.php";

$xret["status"] = 1;
$xret["msg"] = "";
$xret["new_filename"] = "";

if($_POST["event_action"] == "submit_image"){


    $select_db_chk="SELECT * FROM tranfile1 WHERE recid=".$_POST['recid_hidden']." LIMIT 1";
    $stmt_chk	= $link->prepare($select_db_chk);
    $stmt_chk->execute();
    $rs_chk = $stmt_chk->fetch();
    if(!($rs_chk["img_filename"] == null || empty($rs_chk["img_filename"]) || $rs_chk["img_filename"] == "")){
        //if it already exist
        $filename = $rs_chk["img_filename"];
        $filepath = 'images_sales/' . $filename;
        if(file_exists($filepath)){
            unlink($filepath);
        }
    }

    // Create unique filename
    $ext = pathinfo($_FILES['xfile_sal']['name'], PATHINFO_EXTENSION);
    $filename = 'sales_' . time() . '.' . $ext;
    $xret["new_filename"] = $filename;
    
    // Upload directory
    $upload_dir = 'images_sales/';
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    // Move file
    if(move_uploaded_file($_FILES['xfile_sal']['tmp_name'], $upload_dir . $filename)){

       //edit tranfile2
       $arr_tranfile1_upd = array();
       $arr_tranfile1_upd['img_filename'] = $filename;
       PDO_UpdateRecord($link,'tranfile1',$arr_tranfile1_upd," recid = ?",array($_POST['recid_hidden']),false);  
    } else {
        // echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
    }



} else if($_POST["event_action"] == "delete_image") {

    $select_db_docnum="SELECT * FROM tranfile1 WHERE recid=".$_POST['recid_hidden']." LIMIT 1";
    $stmt_docnum	= $link->prepare($select_db_docnum);
    $stmt_docnum->execute();
    $rs_docnum = $stmt_docnum->fetch();

    //how to delete the file
    $filename = $rs_docnum["img_filename"];
    $filepath = 'images_sales/' . $filename;
    if(file_exists($filepath)){
        unlink($filepath);
    }

    // delete the image
    $arr_tranfile1_upd = array();
    $arr_tranfile1_upd['img_filename'] = "";
    PDO_UpdateRecord($link,'tranfile1',$arr_tranfile1_upd," recid = ?",array($_POST['recid_hidden']),false);  
}
    
echo json_encode($xret);


?>