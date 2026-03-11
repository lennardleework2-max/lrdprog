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


    $select_db_column_equivalent="SELECT * FROM sales_upload_column";
    $stmt_column_equivalent	= $link->prepare($select_db_column_equivalent);
    $stmt_column_equivalent->execute();
    while($rs_column_equivalent = $stmt_column_equivalent->fetch())
    {
        if($rs_column_equivalent['dest_column_matched'] == "paid_ordernum"){
            $requiredHeaders[$rs_column_equivalent['dest_column_matched']] = $rs_column_equivalent[strtolower($_POST['platform_name']).'_column_matching'];
        }
    }
    
    $foundHeaders = [];
    $headerIndexes = [];

    if(strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada'){
        foreach ($cellIterator as $cell) {

            $cellValue = trim($cell->getValue());
            if (in_array($cellValue, $requiredHeaders)) {
                $foundHeaders[] = $cellValue;
                $headerIndexes[$cellValue] = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
            }
        }
    }else if(strtolower($_POST['platform_name']) == 'shopee'){
        // ONLY PROCESS ROW 6
        $row = $sheet->getRowIterator(6)->current();
        $cellIterator = $row->getCellIterator();

        foreach ($cellIterator as $cell) {
            $colIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());

            $cellValue = trim($cell->getValue());
            if (in_array($cellValue, $requiredHeaders)) {
                $foundHeaders[] = $cellValue;
                $headerIndexes[$cellValue] = $colIndex;
            }
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

    if(strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada'){
        foreach ($cellIterator as $cell) 
        {
            $cellValue = trim($cell->getValue());

            if ($cellValue === $requiredHeaders['paid_ordernum']) {
                $orderNumberIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
            }
        } 
        
    }else if(strtolower($_POST['platform_name']) == 'shopee'){
        // Process only row 6, starting from column 1
        $row = $sheet->getRowIterator(6)->current();
        $cellIterator = $row->getCellIterator();

        foreach ($cellIterator as $cell) 
        {
            $colIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());

            $cellValue = trim($cell->getValue());

            if ($cellValue === $requiredHeaders['paid_ordernum']) {
                $orderNumberIndex = $colIndex;
            }
        }
    }


   $prevOrderNumber = NULL;
   $prevDocnum = NULL;

   $fileName = $_FILES['xfile']['name'];
   $current_dateTime = date('Y-m-d H:i:s');
   $file_batchno = $_POST['platform_name'].'_'.$fileName.'_'.$current_dateTime;

   $curent_date = date('Y-m-d');


   //xfilter
   $xfilter =  "";
   $xchecked_manual = true;
   if(!(isset($_POST['chk_manual']) && $_POST['chk_manual'] == 'checked')){
         $xfilter =  " AND platform_upload='".$_POST['platform_name']."'";
         $xchecked_manual = false;
   }

   //LOOOP ITERATION FOR TIKTOK
   if(strtolower($_POST['platform_name']) == 'tiktok' || strtolower($_POST['platform_name']) == 'lazada'){
        foreach($sheet->getRowIterator(2) as $row) {

            //GETS THE VALUE
            $orderNumber = getCellValueAsString($sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex()));

            if(empty($orderNumber)){
                continue;
            }

            //CHECK IF IT IS MATCHED
            $select_db_checker="SELECT * FROM tranfile1 WHERE true ".$xfilter." AND ordernum='".$orderNumber."'";
            $stmt_checker	= $link->prepare($select_db_checker);
            $stmt_checker->execute();
            $rs_checker = $stmt_checker->fetch();

            if(empty($rs_checker)){
                $response["noMatchFile2"][$orderNumber]["success"] = false;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = "Sales Order Number: <b>".$orderNumber."</b> doesn't exist";
                continue;
            }else{

                //update tranfile1
                $arr_tranfile1_upd = array();
                $arr_tranfile1_upd['paydate'] = $curent_date;
                $arr_tranfile1_upd['paydetails'] = 'Paid via file upload';
                PDO_UpdateRecord($link,'tranfile1',$arr_tranfile1_upd," recid = ?",array($rs_checker['recid']),false);  
                
                $select_db_chk="SELECT * FROM tranfile1 WHERE recid='".$rs_checker['recid']."'";
                $stmt_chk	= $link->prepare($select_db_chk);
                $stmt_chk->execute();
                $rs_chk = $stmt_chk->fetch();

                if(!empty($rs_chk['file_batchno']) && !empty($rs_chk['platform_upload'])){
                    //update upld_salesfile
                    $arr_upld_sales_upd = array();
                    $arr_upld_sales_upd['paydate'] = $curent_date;
                    PDO_UpdateRecord($link,'upld_salesfile',$arr_upld_sales_upd," ordernum = ?",array($orderNumber),false);  
                }

                $response["noMatchFile2"][$orderNumber]["success"] = true;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> set to paid.";
            }
        }    
   }else if(strtolower($_POST['platform_name']) == 'shopee'){
        foreach($sheet->getRowIterator(7) as $row) {

            //GETS THE VALUE
            $orderNumber = getCellValueAsString($sheet->getCellByColumnAndRow($orderNumberIndex, $row->getRowIndex()));

            if(empty($orderNumber)){
                continue;
            }
            
            //CHECK IF IT IS MATCHED
            $select_db_checker="SELECT * FROM tranfile1 WHERE true ".$xfilter." AND ordernum='".$orderNumber."'";
            $stmt_checker	= $link->prepare($select_db_checker);
            $stmt_checker->execute();
            $rs_checker = $stmt_checker->fetch();

            if(empty($rs_checker)){
                $response["noMatchFile2"][$orderNumber]["success"] = false;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = "Sales Order Number: <b>".$orderNumber."</b> doesn't exist";
                continue;
            }else{

                //update tranfile1
                $arr_tranfile1_upd = array();
                $arr_tranfile1_upd['paydate'] = $curent_date;
                $arr_tranfile1_upd['paydetails'] = 'Paid via file upload';
                PDO_UpdateRecord($link,'tranfile1',$arr_tranfile1_upd," recid = ?",array($rs_checker['recid']),true);  

                $select_db_chk="SELECT * FROM tranfile1 WHERE recid='".$rs_checker['recid']."'";
                $stmt_chk	= $link->prepare($select_db_chk);
                $stmt_chk->execute();
                $rs_chk = $stmt_chk->fetch();

                if(!empty($rs_chk['file_batchno']) && !empty($rs_chk['platform_upload'])){
                    //update upld_salesfile
                    $arr_upld_sales_upd = array();
                    $arr_upld_sales_upd['paydate'] = $curent_date;
                    PDO_UpdateRecord($link,'upld_salesfile',$arr_upld_sales_upd," ordernum = ?",array($orderNumber),false);  
                }

                $response["noMatchFile2"][$orderNumber]["success"] = true;
                $response["noMatchFile2"][$orderNumber]["ordernum"] = $orderNumber;
                $response["noMatchFile2"][$orderNumber]["message"] = " Order Number: <b>".$orderNumber."</b> set to paid.";
            }
        }    
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
