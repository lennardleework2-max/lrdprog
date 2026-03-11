<?php
session_start();
require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/stdfunc100.php";
require "resources/lx2.pdodb.php";
require 'vendor/autoload.php';





$response = [
    "html" => "",
    "retEdit" => [], // Array to store unique values from column R
    "msg" => "",
    "status" => 1,
    "retEdit" => [],
    "inputHTML" => "",
    "outputHTML" => "",
    "outputHTML_mobile" => "",
    "outputItmHTML" => "",
    "xpageno" => 0
];


if(isset($_POST['xfile_batchno']) && !empty($_POST['xfile_batchno'])){

    $xqry_del = "DELETE FROM tranfile1 WHERE file_batchno='".$_POST['xfile_batchno']."'";
    $xstmt_del = $link->prepare($xqry_del);
    $xstmt_del->execute();
}


$select_db_waybill="SELECT COUNT(*) as rec_count FROM salesupload_history";
$stmt_waybill	= $link->prepare($select_db_waybill);
$stmt_waybill->execute();
$rs_waybill = $stmt_waybill->fetch();

if(isset($_POST['txt_pager_pageno'])){
    $xpageno = $_POST['txt_pager_pageno'];
}else{
    $xpageno = 1;
}

$xrow_limit = 10;
$maxpage = ceil($rs_waybill['rec_count']/$xrow_limit);
$xoffset = 0;


if($xpageno == 0 || $xpageno == 1){
    $xpageno = 1;
    $xoffset = 0;
}

if($rs_waybill['rec_count'] == 0 || empty($rs_waybill['rec_count'])){
    $xpageno = 0;
}else{
    if($_POST['event_action'] == 'next_p'){

        $xoffset = $xpageno * $xrow_limit;
        $xpageno+=1;
    
        if($xpageno > $maxpage){
            $xpageno = $maxpage;
            $xoffset = ($maxpage - 1) * $xrow_limit;
        }
        
    }else if($_POST['event_action'] == 'previous_p'){
    
        if($xpageno == 1){
            $xoffset = 0;
            $xpageno = 1;
        }else{
            $xpageno-=1;
            $xoffset = ($xpageno * $xrow_limit)- $xrow_limit;
        }
    
    }else if($_POST['event_action'] == 'last_p'){
        $xpageno = $maxpage;
        $xoffset = ($maxpage - 1) * $xrow_limit;
    }else if($_POST['event_action'] == 'first_p'){
        $xpageno = 1;
        $xoffset = 0;
    }else if($_POST["event_action"] =="same"){
        $xoffset =  ($xpageno * $xrow_limit) - $xrow_limit ;
    }
    
}

    $response["xpageno"] = $xpageno;

    $select_db_dict = "SELECT * FROM salesupload_history ORDER BY date_time DESC LIMIT ".$xrow_limit." OFFSET ".$xoffset."";

    $response["outputHTML"].="<tr>
                                <td style='font-family:20px;font-weight:bold'>
                                    File Uploaded   
                                </td>
                                
                                <td style='font-family:20px;font-weight:bold'>
                                    Date & Time   
                                </td>

                                <td style='font-family:20px;text-align:center;font-weight:bold'>
                                    Action   
                                </td>
                            </tr>";


$stmt_dict	= $link->prepare($select_db_dict);
$stmt_dict->execute();
$xcount_rows = 0;
while($rs_dict = $stmt_dict->fetch()){

    if($xcount_rows == 0){
        $tr_style = '';
    }else{
        $tr_style = 'border-top:2px solid black'; 
    }

    $response["outputHTML"].="<tr> 
            <td>
                <a style='text-decoration: none;' href='saleshistory_files/".$rs_dict['saved_filename']."'>".$rs_dict['saved_filename']."  </a>
            </td>
            
            <td>
                ".$rs_dict['date_time']."  
            </td>";

            $select_db_chk = "SELECT * FROM tranfile1 WHERE trncde='SAL' AND file_batchno=?";
            $stmt_chk	= $link->prepare($select_db_chk);
            $stmt_chk->execute(array($rs_dict['file_batchno']));
            $rs_chk = $stmt_chk->fetch();

            //check if it exist sa sales
            if(!empty($rs_chk)){
                $response["outputHTML"].="<td style='text-align:center'>
                    <button type='button' class='btn btn-danger fw-bold' onclick='delete_so(\"".$rs_dict['file_batchno']."\")'>Delete Sales </button>
                </td>";
            }else{
                $response["outputHTML"].="<td style='text-align:center'>
                </td>";
            }

    $response["outputHTML"].="</tr>";



    $response["outputHTML_mobile"].="
        <tr style='".$tr_style."'> 
            <td>
                File Uploaded  
            </td>
            <td>
                <a style='text-decoration: none;' href='saleshistory_files/".$rs_dict['saved_filename']."'>".$rs_dict['saved_filename']."  </a>
            </td>
        </tr>

        <tr> 
            <td>
                Date & Time  
            </td>
            <td>
                ".$rs_dict['date_time']."  
            </td>
        </tr>";


        if(!empty($rs_chk)){
            $response["outputHTML_mobile"].="
            <tr> 
                <td>
                    Action  
                </td>
                <td style='text-align:center'>
                    <button type='button' class='btn btn-danger fw-bold' onclick='delete_so(\"".$rs_dict['file_batchno']."\")'>Delete Sales</button>
                </td>
            </tr>";
        }else{
            $response["outputHTML_mobile"].="
            <tr> 
                <td>
                    Action  
                </td>
                <td style='text-align:center'>
                </td>
            </tr>";
        }

        $xcount_rows++;
}




echo json_encode($response);
?>
