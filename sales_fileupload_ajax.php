<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/stdfunc100.php";
require "resources/lx2.pdodb.php";
require 'vendor/autoload.php';




use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$response = [
    "html" => "",
    "uniqueValues" => [], // Array to store unique values from column R
    "excelOutput" => [], // Array to store unique values from column R
    "items" => [], // Array to store item data with counts and details
    "msg" => "",
    "status" => 1,
    "retEdit" => [],
    "inputHTML" => "",
    "errorMsg" => "",
    "outputHTML" => "",
    "disabledDownload" => false,
    "disabledDownloadBackSlash" => false,
    "noMatchFile2" => [],
    "warningUpload" => 0
];


if ($_FILES['xfile']['error'] === 0) {
    $filePath = $_FILES['xfile']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['xfile']['name'], PATHINFO_EXTENSION));
    
    $reader = null;

    // Choose the appropriate reader based on the file extension
    switch ($fileExtension) {
        case 'xlsx':
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            break;
        case 'csv':
            $fileContent = file_get_contents($filePath);

            // Detect and convert encoding to UTF-8
            $currentEncoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($currentEncoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $currentEncoding);
                file_put_contents($filePath, $fileContent);
            }

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(",");
            $reader->setEnclosure('"');
            $reader->setEscapeCharacter("\\");
            
            break;
        default:
            $response = [
                "msg" => "Unsupported file type",
                "status" => 0
            ];
            echo json_encode($response);
            exit;
    }
    
    try {
        $spreadsheet = $reader->load($filePath);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        $response = [
            "msg" => 'Error loading file: ' . $e->getMessage(),
            "status" => 0
        ];
        echo json_encode($response);
        exit;
    }

    $sheet = $spreadsheet->getActiveSheet();
    $output = "";
    $total_lineitem = 0;

    $nameColumnIndex = null;
    $row = $sheet->getRowIterator(1)->current(); // Get the first row
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(true); // Loop only existing cells

    date_default_timezone_set('Asia/Manila');
    $fileName = $_FILES['xfile']['name'];
    $current_dateTime = date('Y-m-d H:i:s');
    $file_batchno = $_POST['platform_name'].'_'.$fileName.'_'.$current_dateTime;


    $select_db_column_equivalent="SELECT * FROM sales_upload_column";
    $stmt_column_equivalent	= $link->prepare($select_db_column_equivalent);
    $stmt_column_equivalent->execute();
    while($rs_column_equivalent = $stmt_column_equivalent->fetch())
    {

        if($rs_column_equivalent['dest_column_matched'] == "paid_ordernum"){
            continue;
        }
        if(strtolower($_POST['platform_name']) == 'lazada'){
            if($rs_column_equivalent['dest_column_matched'] == "itmqty"){
                continue;
            }
        }

        $requiredHeaders[$rs_column_equivalent['dest_column_matched']] = $rs_column_equivalent[strtolower($_POST['platform_name']).'_column_matching'];
    }
    
    $foundHeaders = [];
    $headerIndexes = [];
    
    foreach ($cellIterator as $cell) {

        $cellValue = trim($cell->getValue());
        if (in_array($cellValue, $requiredHeaders)) {
            $foundHeaders[] = $cellValue;
            $headerIndexes[$cellValue] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        }
    }

    
    
    // Find missing headers
    $missingHeaders = array_diff($requiredHeaders, $foundHeaders);
    
    if (!empty($missingHeaders)) {

        $response["status"] = 0;
        $response["errorMsg"] = "❌ Error: Missing required columns: " . implode(', ', $missingHeaders);

        echo json_encode($response);
        exit;
    }
    

    $orderNumberIndex = -999;
    $trndteIndex = -999;
    $itmcdeIndex = -999;
    $untprcIndex = -999;

    // Now you can use $headerIndexes['creator code'], etc.
    foreach ($cellIterator as $cell) 
    {
        $cellValue = trim($cell->getValue());

        if ($cellValue === $requiredHeaders['ordernum']) {
            $orderNumberIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        }else if ($cellValue === $requiredHeaders['trndte']) {
            $trndteIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        }else if ($cellValue === $requiredHeaders['untprc']) {
            $untprcIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        }else if ($cellValue === $requiredHeaders['itmcde']) {
            $itmcdeIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        }

        if(strtolower($_POST['platform_name']) != 'lazada'){
            if ($cellValue === $requiredHeaders['itmqty']) {
                $itmqtyIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
            }
        }
    } 

    //CHECK IF CONTAINS ANY \\
    // foreach ($sheet->getRowIterator() as $row_checkdbackslash) {
    //     $rowIndex_checker = $row_checkdbackslash->getRowIndex();
        
    //     // Skip the first row which is typically the header
    //     if ($rowIndex_checker == 1) {
    //         continue;
    //     }

    //     // $cellValueR = $sheet->getCellByColumnAndRow(htmlspecialchars_decode($lineItemNameIndex, ENT_QUOTES), $row->getRowIndex())->getValue();
    //     $remarks_checker = getCellValueAsString($sheet->getCellByColumnAndRow($remarksIndex, $row_checkdbackslash->getRowIndex()));
    //     $itemName_checker = getCellValueAsString($sheet->getCellByColumnAndRow($itemNameIndex, $row_checkdbackslash->getRowIndex()));

    //     if (strpos($remarks_checker, '\\\\') !== false || strpos($itemName_checker, '\\\\') !== false) {
    //         $response["disabledDownloadBackSlash"] = true;
    //         $response["status"] = 0;
    //         $response["errorMsg"] = "Cannot Download File Contains \\\\ Please Reupload File without double backslashes!";

    //         echo json_encode($response);
    //         exit;
    //     }
    // }    

   $prevOrderNumber = NULL;
   $prevDocnum = NULL;




    $platform_search = '';
    if(strtolower($_POST['platform_name']) == 'tiktok'){
        $platform_search = 'Tiktok';
    }else if(strtolower($_POST['platform_name']) == 'shopee'){
        $platform_search = 'Shopee';
    }else if(strtolower($_POST['platform_name']) == 'lazada'){
        $platform_search = 'Lazada';
    }

    //CHECK IF EXIST ALREADY BASED ON ORDERNUM
    $select_db_platform="SELECT * FROM customerfile WHERE cusdsc LIKE '%".$platform_search."%' LIMIT 1";
    $stmt_platform	= $link->prepare($select_db_platform);
    $stmt_platform->execute();
    $rs_platform = $stmt_platform->fetch();

   //LOOOP ITERATION FOR TIKTOK
   if(strtolower($_POST['platform_name']) == 'tiktok'){
    foreach($sheet->getRowIterator(3) as $row) {

            //GETS THE VALUE
            $orderNumber = getCellValueAsString($sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex()));
            $trndte = getCellValueAsString($sheet->getCellByColumnAndRow($trndteIndex, $row->getRowIndex()));
            $itmcde = getCellValueAsString($sheet->getCellByColumnAndRow($itmcdeIndex, $row->getRowIndex()));
            $untprc = getCellValueAsString($sheet->getCellByColumnAndRow($untprcIndex, $row->getRowIndex()));
            $itmqty = getCellValueAsString($sheet->getCellByColumnAndRow($itmqtyIndex, $row->getRowIndex()));

            if(empty($orderNumber) &&
               empty($trndte) &&
               empty($itmcde) &&
               empty($untprc) && 
               empty($itmqty)){
                continue;
            }

            //CHECK IF EXIST ALREADY BASED ON ORDERNUM
            $select_db_checker="SELECT * FROM tranfile1 WHERE ordernum='".$orderNumber."' AND file_batchno !='".$file_batchno."'";
            $stmt_checker	= $link->prepare($select_db_checker);
            $stmt_checker->execute();
            $rs_checker = $stmt_checker->fetch();
            if(!empty($rs_checker)){

                $response["noMatchFile2"][$orderNumber]["success"] = false;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> already exist";
                continue;
            }

            //FORMATS trndte
            $trndte = date('Y-m-d', strtotime($trndte));

            //IF ORDER NUMBER IS EQUAL THEN DO SOMETHING
            if($prevOrderNumber != $orderNumber){
                //GETS THE DOCNUM
                $select_db_docnum="SELECT * FROM tranfile1 WHERE trncde='SAL' ORDER BY docnum  DESC LIMIT 1";
                $stmt_docnum	= $link->prepare($select_db_docnum);
                $stmt_docnum->execute();
                $rs_docnum = $stmt_docnum->fetch();
                $docnum  = Lnexts($rs_docnum['docnum']);
                if(empty($rs_docnum)){
                    $docnum  = "SAL-00001";
                }

                //INSERT INTO TRANFILE1
                $arr_add = array();
                $arr_add['docnum'] = $docnum;
                $arr_add['trndte'] = $trndte;
                $arr_add['ordernum'] = $orderNumber;
                $arr_add['trncde'] = 'SAL';
                $arr_add['file_batchno'] = $file_batchno;
                $arr_add['cuscde'] = $rs_platform['cuscde'];
                $arr_add['platform_upload'] = $_POST['platform_name'];
                $arr_add['datetime_upload'] = $current_dateTime;
                $arr_add['can_change_ordernum'] = 'true';
                PDO_InsertRecord($link,'tranfile1',$arr_add, false);
            }else{
                $docnum = $prevDocnum;
            }
                
            //CHECKS IF NOT IT CREATES AN ITEM
            $select_db_buyer_name="SELECT * FROM itemfile WHERE tiktok_itm_sku='".$itmcde."' LIMIT 1";
            $stmt_buyer_name	= $link->prepare($select_db_buyer_name);
            $stmt_buyer_name->execute();
            $rs_buyer_name = $stmt_buyer_name->fetch();
            if(empty($rs_buyer_name)){
                $response["status"] = 0;
                $response["errorMsg"] = "TIKTOK SKU: ".$itmcde." NOT FOUND";

                //ALL OR NOTHING DELETES ALL PREVIOUS INSERTED
                $delete_query="DELETE FROM tranfile1 WHERE file_batchno=?";
                $stmt=$link->prepare($delete_query);
                $stmt->execute(array($file_batchno));

                $delete_query2="DELETE FROM tranfile2 WHERE file_batchno=?";
                $stmt2=$link->prepare($delete_query2);
                $stmt2->execute(array($file_batchno));

                $delete_query3="DELETE FROM upld_salesfile WHERE file_batchno=?";
                $stmt3=$link->prepare($delete_query3);
                $stmt3->execute(array($file_batchno));

                echo json_encode($response);
                exit;
            }

            //getting the total
            $extprc = (int)$itmqty * (float)$untprc;

            //INSERT INTO TRANFILE2
            $arr_add2 = array();
            $arr_add2['docnum'] = $docnum;
            $arr_add2['trndte'] = $trndte;
            $arr_add2['trncde'] = 'SAL';
            $arr_add2['itmcde'] = $rs_buyer_name['itmcde'];
            $arr_add2['file_batchno'] = $file_batchno;
            $arr_add2['untprc'] = $untprc;
            $arr_add2['itmqty'] = $itmqty;
            $arr_add2['stkqty'] =  -1*($itmqty);
            $arr_add2['extprc'] = $extprc;  
            $arr_add2['platform_upload'] = $_POST['platform_name'];
            $arr_add2['datetime_upload'] = $current_dateTime;  
            PDO_InsertRecord($link,'tranfile2',$arr_add2, false);

            //CHECKING HOW MUCH IS IN TRANFILE1 AND HTEN ADDING
            $select_db_extprc_check="SELECT * FROM tranfile1 WHERE docnum='".$docnum."' LIMIT 1";
            $stmt_extprc_check	= $link->prepare($select_db_extprc_check);
            $stmt_extprc_check->execute();
            $rs_extprc_check = $stmt_extprc_check->fetch();

            $arr_record_upd = array();
            $arr_record_upd['trntot'] 	= $rs_extprc_check['trntot'] + $extprc;
            PDO_UpdateRecord($link,"tranfile1",$arr_record_upd,"recid = ?",array($rs_extprc_check['recid']));   
            
            //INSERTS INTO THE RECORD NUMBER
            $arr_add3 = array();
            $arr_add3['docnum'] = $docnum;
            $arr_add3['platform'] = $_POST['platform_name'];
            $arr_add3['file_batchno'] = $file_batchno;
            $arr_add3['trndte'] = $trndte;
            $arr_add3['itmcde_raw'] = $itmcde;
            $arr_add3['itmcde_matched'] = $rs_buyer_name['itmcde'];
            $arr_add3['ordernum'] = $orderNumber;
            $arr_add3['untprc'] = $untprc;
            $arr_add3['itmqty'] = $itmqty;
            $arr_add3['extprc'] = $extprc;   
            $arr_add3['datetime_upload'] = $current_dateTime;   
            PDO_InsertRecord($link,'upld_salesfile',$arr_add3, false);
            
            //gets the orernumber of the previous
            $prevOrderNumber = $orderNumber;
            $prevDocnum = $docnum;

            $response["noMatchFile2"][$orderNumber]["success"] = true;
            $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
            $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> successfully created as sales.";

            

    }    
   }else if(strtolower($_POST['platform_name']) == 'shopee'){
        foreach($sheet->getRowIterator(2) as $row) {

            //GETS THE VALUE
            $orderNumber = getCellValueAsString($sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex()));
            $trndte = getCellValueAsString($sheet->getCellByColumnAndRow($trndteIndex, $row->getRowIndex()));
            $itmcde = getCellValueAsString($sheet->getCellByColumnAndRow($itmcdeIndex, $row->getRowIndex()));
            $cell = $sheet->getCellByColumnAndRow($untprcIndex, $row->getRowIndex());
            $untprc = (float) $cell->getCalculatedValue();
            $itmqty = getCellValueAsString($sheet->getCellByColumnAndRow($itmqtyIndex, $row->getRowIndex()));

            if(empty($orderNumber) &&
               empty($trndte) &&
               empty($itmcde) &&
               empty($itmqty)
               ){
                continue;
            }

            //CHECK IF EXIST ALREADY BASED ON ORDERNUM
            $select_db_checker="SELECT * FROM tranfile1 WHERE ordernum='".$orderNumber."' AND file_batchno !='".$file_batchno."'";
            $stmt_checker	= $link->prepare($select_db_checker);
            $stmt_checker->execute();
            $rs_checker = $stmt_checker->fetch();
            if(!empty($rs_checker)){

                $response["noMatchFile2"][$orderNumber]["success"] = false;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> already exist";
                continue;
            }

            //FORMATS trndte
            $trndte = date('Y-m-d', strtotime($trndte));

            //IF ORDER NUMBER IS EQUAL THEN DO SOMETHING
            if($prevOrderNumber != $orderNumber){

                //GETS THE DOCNUM
                $select_db_docnum="SELECT * FROM tranfile1 WHERE trncde='SAL' ORDER BY docnum  DESC LIMIT 1";
                $stmt_docnum	= $link->prepare($select_db_docnum);
                $stmt_docnum->execute();
                $rs_docnum = $stmt_docnum->fetch();
                $docnum  = Lnexts($rs_docnum['docnum']);
                if(empty($rs_docnum)){
                    $docnum  = "SAL-00001";
                }

                //INSERT INTO TRANFILE1
                $arr_add = array();
                $arr_add['docnum'] = $docnum;
                $arr_add['trndte'] = $trndte;
                $arr_add['ordernum'] = $orderNumber;
                $arr_add['trncde'] = 'SAL';
                $arr_add['file_batchno'] = $file_batchno;
                $arr_add['cuscde'] = $rs_platform['cuscde'];
                $arr_add['platform_upload'] = $_POST['platform_name'];
                $arr_add['datetime_upload'] = $current_dateTime;
                $arr_add['can_change_ordernum'] = 'true';
                PDO_InsertRecord($link,'tranfile1',$arr_add, false);
            }else{
                $docnum = $prevDocnum;
            }
                
            //CHECKS IF NOT IT CREATES AN ITEM
            $select_db_buyer_name="SELECT * FROM itemfile WHERE shopee_itm_sku='".$itmcde."' LIMIT 1";
            $stmt_buyer_name	= $link->prepare($select_db_buyer_name);
            $stmt_buyer_name->execute();
            $rs_buyer_name = $stmt_buyer_name->fetch();
            if(empty($rs_buyer_name)){
                $response["status"] = 0;
                $response["errorMsg"] = "SHOPEE SKU: ".$itmcde." NOT FOUND";

                //ALL OR NOTHING DELETES ALL PREVIOUS INSERTED
                $delete_query="DELETE FROM tranfile1 WHERE file_batchno=?";
                $stmt=$link->prepare($delete_query);
                $stmt->execute(array($file_batchno));

                $delete_query2="DELETE FROM tranfile2 WHERE file_batchno=?";
                $stmt2=$link->prepare($delete_query2);
                $stmt2->execute(array($file_batchno));

                $delete_query3="DELETE FROM upld_salesfile WHERE file_batchno=?";
                $stmt3=$link->prepare($delete_query3);
                $stmt3->execute(array($file_batchno));

                echo json_encode($response);
                exit;
            }

            //getting the total
            $extprc = (int)$itmqty * (float)$untprc;

            //INSERT INTO TRANFILE2
            $arr_add2 = array();
            $arr_add2['docnum'] = $docnum;
            $arr_add2['trndte'] = $trndte;
            $arr_add2['trncde'] = 'SAL';
            $arr_add2['itmcde'] = $rs_buyer_name['itmcde'];
            $arr_add2['file_batchno'] = $file_batchno;
            $arr_add2['untprc'] = $untprc;
            $arr_add2['itmqty'] = $itmqty;
            $arr_add2['stkqty'] =  -1*($itmqty);
            $arr_add2['extprc'] = $extprc;  
            $arr_add2['platform_upload'] = $_POST['platform_name'];
            $arr_add2['datetime_upload'] = $current_dateTime;  
            PDO_InsertRecord($link,'tranfile2',$arr_add2, false);

            //CHECKING HOW MUCH IS IN TRANFILE1 AND HTEN ADDING
            $select_db_extprc_check="SELECT * FROM tranfile1 WHERE docnum='".$docnum."' LIMIT 1";
            $stmt_extprc_check	= $link->prepare($select_db_extprc_check);
            $stmt_extprc_check->execute();
            $rs_extprc_check = $stmt_extprc_check->fetch();

            $arr_record_upd = array();
            $arr_record_upd['trntot'] 	= $rs_extprc_check['trntot'] + $extprc;
            PDO_UpdateRecord($link,"tranfile1",$arr_record_upd,"recid = ?",array($rs_extprc_check['recid']));   
            
            //INSERTS INTO THE RECORD NUMBER
            $arr_add3 = array();
            $arr_add3['docnum'] = $docnum;
            $arr_add3['platform'] = $_POST['platform_name'];
            $arr_add3['file_batchno'] = $file_batchno;
            $arr_add3['trndte'] = $trndte;
            $arr_add3['itmcde_raw'] = $itmcde;
            $arr_add3['itmcde_matched'] = $rs_buyer_name['itmcde'];
            $arr_add3['ordernum'] = $orderNumber;
            $arr_add3['untprc'] = $untprc;
            $arr_add3['itmqty'] = $itmqty;
            $arr_add3['extprc'] = $extprc;   
            $arr_add3['datetime_upload'] = $current_dateTime;   
            PDO_InsertRecord($link,'upld_salesfile',$arr_add3, false);
            
            //gets the orernumber of the previous
            $prevOrderNumber = $orderNumber;
            $prevDocnum = $docnum;

            $response["noMatchFile2"][$orderNumber]["success"] = true;
            $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
            $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> successfully created as sales.";
    } 
   }else if(strtolower($_POST['platform_name']) == 'lazada'){
        foreach($sheet->getRowIterator(2) as $row) {

            //GETS THE VALUE
            $orderNumber = getCellValueAsString($sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex()));
            $trndte = getCellValueAsString($sheet->getCellByColumnAndRow($trndteIndex, $row->getRowIndex()));
            $itmcde = getCellValueAsString($sheet->getCellByColumnAndRow($itmcdeIndex, $row->getRowIndex()));
            $untprc = getCellValueAsString($sheet->getCellByColumnAndRow($untprcIndex, $row->getRowIndex()));

            //each row has only one daw
            $itmqty = 1;

            // Skip empty rows BEFORE processing
            if(empty($orderNumber) &&
               empty($trndte) &&
               empty($itmcde) &&
               empty($untprc)){
                continue;
            }

            //convert to yyy-mm-dd
            $date_format = DateTime::createFromFormat('d M Y H:i', trim($trndte));
            if ($date_format === false) {
                // Try alternative date format (d/m/Y H:i or other common formats)
                $date_format = DateTime::createFromFormat('d/m/Y H:i', trim($trndte));
            }
            if ($date_format === false) {
                // Try without time
                $date_format = DateTime::createFromFormat('d M Y', trim($trndte));
            }
            if ($date_format === false) {
                // Try another common format
                $date_format = DateTime::createFromFormat('Y-m-d H:i:s', trim($trndte));
            }
            if ($date_format === false) {
                // If all parsing attempts fail, provide a clear error message
                $response["status"] = 0;
                $response["errorMsg"] = "Invalid date format in row " . $row->getRowIndex() . ". Date value: '" . $trndte . "'. Expected format: 'd M Y H:i' (e.g., '04 Nov 2025 14:30')";

                echo json_encode($response);
                exit;
            }
            $trndte = $date_format->format('Y-m-d');

            //CHECK IF EXIST ALREADY BASED ON ORDERNUM
            $select_db_checker="SELECT * FROM tranfile1 WHERE ordernum='".$orderNumber."' AND file_batchno !='".$file_batchno."'";
            $stmt_checker	= $link->prepare($select_db_checker);
            $stmt_checker->execute();
            $rs_checker = $stmt_checker->fetch();
            if(!empty($rs_checker)){

                $response["noMatchFile2"][$orderNumber]["success"] = false;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> already exist";
                continue;
            }


            //IF ORDER NUMBER IS EQUAL THEN DO SOMETHING
            if($prevOrderNumber != $orderNumber){

                //GETS THE DOCNUM
                $select_db_docnum="SELECT * FROM tranfile1 WHERE trncde='SAL' ORDER BY docnum  DESC LIMIT 1";
                $stmt_docnum	= $link->prepare($select_db_docnum);
                $stmt_docnum->execute();
                $rs_docnum = $stmt_docnum->fetch();
                $docnum  = Lnexts($rs_docnum['docnum']);
                if(empty($rs_docnum)){
                    $docnum  = "SAL-00001";
                }

                //INSERT INTO TRANFILE1
                $arr_add = array();
                $arr_add['docnum'] = $docnum;
                $arr_add['trndte'] = $trndte;
                $arr_add['ordernum'] = $orderNumber;
                $arr_add['trncde'] = 'SAL';
                $arr_add['file_batchno'] = $file_batchno;
                $arr_add['cuscde'] = $rs_platform['cuscde'];
                $arr_add['platform_upload'] = $_POST['platform_name'];
                $arr_add['datetime_upload'] = $current_dateTime;
                $arr_add['can_change_ordernum'] = 'true';
                PDO_InsertRecord($link,'tranfile1',$arr_add, false);
            }else{
                $docnum = $prevDocnum;
            }
                
            //CHECKS IF NOT IT CREATES AN ITEM
            $select_db_buyer_name="SELECT * FROM itemfile WHERE lazada_itm_sku='".$itmcde."' LIMIT 1";
            $stmt_buyer_name	= $link->prepare($select_db_buyer_name);
            $stmt_buyer_name->execute();
            $rs_buyer_name = $stmt_buyer_name->fetch();
            if(empty($rs_buyer_name)){
                $response["status"] = 0;
                $response["errorMsg"] = "LAZADA SKU: ".$itmcde." NOT FOUND";

                //ALL OR NOTHING DELETES ALL PREVIOUS INSERTED
                $delete_query="DELETE FROM tranfile1 WHERE file_batchno=?";
                $stmt=$link->prepare($delete_query);
                $stmt->execute(array($file_batchno));

                $delete_query2="DELETE FROM tranfile2 WHERE file_batchno=?";
                $stmt2=$link->prepare($delete_query2);
                $stmt2->execute(array($file_batchno));

                $delete_query3="DELETE FROM upld_salesfile WHERE file_batchno=?";
                $stmt3=$link->prepare($delete_query3);
                $stmt3->execute(array($file_batchno));

                echo json_encode($response);
                exit;
            }

            //getting the total
            $extprc = 1 * (float)$untprc;

            //INSERT INTO TRANFILE2
            $arr_add2 = array();
            $arr_add2['docnum'] = $docnum;
            $arr_add2['trndte'] = $trndte;
            $arr_add2['trncde'] = 'SAL';
            $arr_add2['itmcde'] = $rs_buyer_name['itmcde'];
            $arr_add2['file_batchno'] = $file_batchno;
            $arr_add2['untprc'] = $untprc;
            $arr_add2['itmqty'] = $itmqty;
            $arr_add2['stkqty'] =  -1*($itmqty);
            $arr_add2['extprc'] = $extprc;  
            $arr_add2['platform_upload'] = $_POST['platform_name'];
            $arr_add2['datetime_upload'] = $current_dateTime;  
            PDO_InsertRecord($link,'tranfile2',$arr_add2, false);

            //CHECKING HOW MUCH IS IN TRANFILE1 AND HTEN ADDING
            $select_db_extprc_check="SELECT * FROM tranfile1 WHERE docnum='".$docnum."' LIMIT 1";
            $stmt_extprc_check	= $link->prepare($select_db_extprc_check);
            $stmt_extprc_check->execute();
            $rs_extprc_check = $stmt_extprc_check->fetch();

            $arr_record_upd = array();
            $arr_record_upd['trntot'] 	= $rs_extprc_check['trntot'] + $extprc;
            PDO_UpdateRecord($link,"tranfile1",$arr_record_upd,"recid = ?",array($rs_extprc_check['recid']));   
            
            //INSERTS INTO THE RECORD NUMBER
            $arr_add3 = array();
            $arr_add3['docnum'] = $docnum;
            $arr_add3['platform'] = $_POST['platform_name'];
            $arr_add3['file_batchno'] = $file_batchno;
            $arr_add3['trndte'] = $trndte;
            $arr_add3['itmcde_raw'] = $itmcde;
            $arr_add3['itmcde_matched'] = $rs_buyer_name['itmcde'];
            $arr_add3['ordernum'] = $orderNumber;
            $arr_add3['untprc'] = $untprc;
            $arr_add3['itmqty'] = $itmqty;
            $arr_add3['extprc'] = $extprc;   
            $arr_add3['datetime_upload'] = $current_dateTime;   
            PDO_InsertRecord($link,'upld_salesfile',$arr_add3, false);
            
            //gets the orernumber of the previous
            $prevOrderNumber = $orderNumber;
            $prevDocnum = $docnum;

            $response["noMatchFile2"][$orderNumber]["success"] = true;
            $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
            $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> successfully created as sales.";
    } 
   }

    // ----- SAVES THE FILE INTO saleshistory_files ------
    // Target directory where files will be saved


    if($response["status"] == 1){

        $updated_filename = $_POST['platform_name'].'_'.$current_dateTime.'_'.$fileName;

        // Sanitize filename: remove spaces, colons, parentheses, etc.
        $cleanFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $updated_filename);

        //save to salesupload_history
        $arr_add_history = array();
        $arr_add_history['orig_filename'] = $fileName;
        $arr_add_history['saved_filename'] = $cleanFileName;
        $arr_add_history['file_batchno'] = $file_batchno;
        $arr_add_history['date_time'] = $current_dateTime;
        PDO_InsertRecord($link,'salesupload_history',$arr_add_history, false);

        $targetDir = "saleshistory_files/";

        // Get uploaded file details
        $fileName = $_FILES['xfile']['name'];
        $fileTmp  = $_FILES['xfile']['tmp_name'];

        // Final target path
        $targetFile = $targetDir . $cleanFileName;

        // Move uploaded file to target folder
        if (move_uploaded_file($fileTmp, $targetFile)) {
            //echo "File uploaded successfully!";
        } else {
            //echo "Error uploading file.";
        }

        // Log upload activity
        $username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
        $username_full_name = '';
        if(isset($_SESSION['recid'])){
            $select_db_session_user='SELECT * FROM users where recid=?';
            $stmt_session_user = $link->prepare($select_db_session_user);
            $stmt_session_user->execute(array($_SESSION['recid']));
            $rs_session_user = $stmt_session_user->fetch();
            if($rs_session_user){
                $username_full_name = $rs_session_user["full_name"];
            }
        }
        $xtrndte = date("Y-m-d H:i:s");
        $xprog_module = "SALES FILE UPLOAD";
        $xactivity = "upload";
        $xremarks = "Uploaded file: ".$_FILES['xfile']['name']." (Platform: ".strtoupper($_POST['platform_name']).")";
        PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks, 0, '', '', '', '', $username_session, '', $cleanFileName);
    }


} else {

}

function getCellValueAsString($cell) {
    // Ensure $cell is an instance of a Cell
    if ($cell instanceof \PhpOffice\PhpSpreadsheet\Cell\Cell) {
        $value = $cell->getValue();

        // Check if the value is a RichText object
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $value->getPlainText();
        }

        // Return raw value if not RichText
        return $value;
    }

    // If $cell is not an object or is not an instance of Cell, return as-is
    return $cell;
}


function parseOrders($input) {
    $xdata = [];

    // Use regular expression to find the order number from the input
    preg_match('/(#\d{4,5})/', $input, $orderNumMatch);
    $ordernum = $orderNumMatch[1] ?? null;  // Capture the order number

    // Use your existing regex to find each order in the input
    preg_match_all('/(\d+) order (.*?)(?=,|$)/', $input, $matches, PREG_SET_ORDER);

    // Iterate through each match to build the xdata array
    foreach ($matches as $match) {
        $quantity = (int)$match[1]; // Extract the quantity
        $name = trim($match[2]);     // Extract the product name

        // Use a unique key for each item by combining order number and name
        $uniqueKey = $name; // or you can use $ordernum . ' ' . $name;

        // Set the xdata array
        $xdata[$uniqueKey]['name'] = $name;
        $xdata[$uniqueKey]['qty'] = $quantity;
        $xdata[$uniqueKey]['ordernum'] = $ordernum; // Add order number
    }

    return $xdata;
}



echo json_encode($response);
?>
