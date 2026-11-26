<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// BEAVER DEBUG: Set up error handling to capture PHP errors as JSON
// This prevents raw PHP errors from breaking JSON parsing on the frontend
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't expose file paths in production - just log them server-side
    error_log("PO Upload Error: [$errno] $errstr in $errfile on line $errline");

    // Only output JSON for fatal-ish errors during BEAVER debugging
    if (defined('BEAVER_DEBUG_ENABLED') && BEAVER_DEBUG_ENABLED) {
        $errorResponse = array(
            'status' => 0,
            'errorMsg' => 'PHP Error: ' . $errstr,
            'debug_step' => 'PHP_ERROR',
            'debug_info' => array(
                'errno' => $errno,
                'line' => $errline
            )
        );
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
        exit;
    }
    return false; // Let PHP handle the error normally
});

require_once("resources/db_init.php");
require "resources/connect4.php";
require "resources/stdfunc100.php";
require "resources/lx2.pdodb.php";
require 'vendor/autoload.php';

// Ensure UTF-8 for Chinese characters
$link->exec("SET NAMES utf8mb4");

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

$response = array(
    "status" => 1,
    "errorMsg" => "",
    "docnum" => "",
    "ordernum" => "",
    "suppdsc" => "",
    "trntot" => 0,
    "item_count" => 0,
    "items" => array(),
    "unmatched_items" => array(),
    "missing_prices" => array(),
    "debug_step" => "",      // BEAVER DEBUG: Current step marker
    "debug_info" => array()  // BEAVER DEBUG: Safe debug info (no sensitive data)
);

// BEAVER DEBUG: Set to true to enable debug info in responses
define('BEAVER_DEBUG_ENABLED', true);

function beaver_set_debug(&$response, $step, $info = array()) {
    if (!BEAVER_DEBUG_ENABLED) return;
    $response['debug_step'] = $step;
    if (!empty($info)) {
        $response['debug_info'] = $info;
    }
}

// BEAVER DEBUG STEP 1: Request received
beaver_set_debug($response, 'STEP-1: Request received', array(
    'event_action' => isset($_POST['event_action']) ? $_POST['event_action'] : '(not set)',
    'has_file' => isset($_FILES['xfile']),
    'suppdsc' => isset($_POST['suppdsc']) ? $_POST['suppdsc'] : '(not set)'
));

// Security check
if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    beaver_set_debug($response, 'STEP-1a: Session check FAILED');
    $response["status"] = 0;
    $response["errorMsg"] = "Session expired. Please log in again.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$current_usercode = isset($_POST['usercode']) && trim((string)$_POST['usercode']) !== ''
    ? trim((string)$_POST['usercode'])
    : (isset($_SESSION['usercode']) ? trim((string)$_SESSION['usercode']) : '');

$event_action = isset($_POST['event_action']) ? trim($_POST['event_action']) : '';

// Validate required inputs
$ordernum = isset($_POST['ordernum']) ? trim($_POST['ordernum']) : '';
$suppcde = isset($_POST['suppcde']) ? trim($_POST['suppcde']) : '';
$suppdsc = isset($_POST['suppdsc']) ? trim($_POST['suppdsc']) : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
$manual_prices = array();

if(isset($_POST['manual_prices']) && $_POST['manual_prices'] !== ''){
    $decoded = json_decode($_POST['manual_prices'], true);
    if(is_array($decoded)){
        $manual_prices = $decoded;
    }
}

// Validate order number
if($ordernum === ''){
    $response["status"] = 0;
    $response["errorMsg"] = "Order number is required.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate supplier
if($suppcde === ''){
    $response["status"] = 0;
    $response["errorMsg"] = "Supplier is required.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Check for allowed suppliers
$allowed_supplier_names = array(
    'Motor Supplier 1',
    'Motor Supplier 2',
    'Motor Supplier 3',
    'Motor Supplier 5'
);
$placeholders = implode(',', array_fill(0, count($allowed_supplier_names), '?'));
$stmt_check_supplier = $link->prepare("SELECT suppcde, suppdsc FROM supplierfile WHERE suppcde = ? AND suppdsc IN (".$placeholders.")");
$params = array_merge(array($suppcde), $allowed_supplier_names);
$stmt_check_supplier->execute($params);
$rs_check_supplier = $stmt_check_supplier->fetch();
if(!$rs_check_supplier){
    $response["status"] = 0;
    $response["errorMsg"] = "Invalid supplier selected.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
$suppdsc = $rs_check_supplier['suppdsc'];

// Check ordernum uniqueness in purchasesorderfile1
$stmt_check_po = $link->prepare("SELECT docnum FROM purchasesorderfile1 WHERE ordernum = ? LIMIT 1");
$stmt_check_po->execute(array($ordernum));
$rs_check_po = $stmt_check_po->fetch();
if($rs_check_po){
    $response["status"] = 0;
    $response["errorMsg"] = "Order number '".$ordernum."' already exists in Purchase Orders (Doc: ".$rs_check_po['docnum'].").";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Check ordernum uniqueness in tranfile1 where trncde = 'PUR'
$stmt_check_pur = $link->prepare("SELECT docnum FROM tranfile1 WHERE ordernum = ? AND trncde = 'PUR' LIMIT 1");
$stmt_check_pur->execute(array($ordernum));
$rs_check_pur = $stmt_check_pur->fetch();
if($rs_check_pur){
    $response["status"] = 0;
    $response["errorMsg"] = "Order number '".$ordernum."' already exists in Purchases (Doc: ".$rs_check_pur['docnum'].").";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// BEAVER DEBUG STEP 2: Validation passed, checking file
beaver_set_debug($response, 'STEP-2: Validation checks passed');

// Check file upload
if(!isset($_FILES['xfile']) || $_FILES['xfile']['error'] !== 0){
    beaver_set_debug($response, 'STEP-2a: File upload check FAILED', array(
        'file_isset' => isset($_FILES['xfile']),
        'file_error' => isset($_FILES['xfile']) ? $_FILES['xfile']['error'] : 'N/A'
    ));
    $response["status"] = 0;
    $response["errorMsg"] = "Please select a valid file to upload.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$filePath = $_FILES['xfile']['tmp_name'];
$fileName = $_FILES['xfile']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if(!in_array($fileExtension, array('xls', 'xlsx'))){
    $response["status"] = 0;
    $response["errorMsg"] = "Please upload an XLS or XLSX file.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// BEAVER DEBUG STEP 2b: Loading spreadsheet
beaver_set_debug($response, 'STEP-2b: Loading spreadsheet', array(
    'file_extension' => $fileExtension,
    'file_path_exists' => file_exists($filePath),
    'file_size' => filesize($filePath)
));

// Load spreadsheet
$reader = null;
try {
    if($fileExtension === 'xlsx'){
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    } else {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
    }
    $spreadsheet = $reader->load($filePath);
    beaver_set_debug($response, 'STEP-2c: Spreadsheet loaded OK', array(
        'sheet_count' => $spreadsheet->getSheetCount(),
        'active_sheet_title' => $spreadsheet->getActiveSheet()->getTitle()
    ));
} catch(\Exception $e) {
    beaver_set_debug($response, 'STEP-2d: Spreadsheet load FAILED', array(
        'error' => $e->getMessage()
    ));
    $response["status"] = 0;
    $response["errorMsg"] = "Error loading file: " . $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$sheet = $spreadsheet->getActiveSheet();

// Get unmcde for 'pcs'
$default_unmcde = '';
$stmt_uom = $link->prepare("SELECT unmcde FROM itemunitmeasurefile WHERE LOWER(unmdsc) = 'pcs' LIMIT 1");
$stmt_uom->execute();
$rs_uom = $stmt_uom->fetch();
if($rs_uom && !empty($rs_uom['unmcde'])){
    $default_unmcde = $rs_uom['unmcde'];
} else {
    $response["status"] = 0;
    $response["errorMsg"] = "Unit of Measure 'pcs' not found. Please add it before uploading.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper function to get cell value as string (for text cells like item descriptions)
function getCellValueAsString($cell) {
    if($cell instanceof \PhpOffice\PhpSpreadsheet\Cell\Cell){
        $value = $cell->getValue();
        if($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText){
            return $value->getPlainText();
        }
        return $value;
    }
    return $cell;
}

// Helper function to get numeric cell value (handles formulas by using calculated value)
// Returns array with: 'value' => numeric value, 'raw' => raw cell content, 'is_formula' => bool, 'formula' => formula text if any
function getNumericCellValue($cell) {
    $result = array(
        'value' => null,
        'raw' => null,
        'is_formula' => false,
        'formula' => null,
        'calculated' => null,
        'success' => false
    );

    if(!($cell instanceof \PhpOffice\PhpSpreadsheet\Cell\Cell)){
        $result['raw'] = $cell;
        if(is_numeric($cell)){
            $result['value'] = floatval($cell);
            $result['success'] = true;
        }
        return $result;
    }

    // Get raw value
    $rawValue = $cell->getValue();
    $result['raw'] = $rawValue;

    // Check if cell contains a formula
    if(is_string($rawValue) && strlen($rawValue) > 0 && $rawValue[0] === '='){
        $result['is_formula'] = true;
        $result['formula'] = $rawValue;

        // Get calculated value for formula cells
        try {
            $calculatedValue = $cell->getCalculatedValue();
            $result['calculated'] = $calculatedValue;

            if(is_numeric($calculatedValue)){
                $result['value'] = floatval($calculatedValue);
                $result['success'] = true;
            }
        } catch(\Exception $e) {
            // Formula calculation failed - leave value as null
            $result['calculated'] = 'ERROR: ' . $e->getMessage();
        }
    } else {
        // Not a formula - try to get numeric value directly
        if(is_numeric($rawValue)){
            $result['value'] = floatval($rawValue);
            $result['success'] = true;
        } else if(is_string($rawValue)){
            // Try to parse as number after removing commas
            $cleanedValue = trim(str_replace(',', '', $rawValue));
            if(is_numeric($cleanedValue)){
                $result['value'] = floatval($cleanedValue);
                $result['success'] = true;
            }
        }
    }

    return $result;
}

// BEAVER Debug logging function (server-side only, no sensitive data)
function beaver_log($message, $context = array()) {
    // Only log if debug mode is enabled via environment or constant
    // Change to true temporarily when debugging, then set back to false
    $debug_enabled = false; // Set to true to enable debug logging
    if (!$debug_enabled) {
        return;
    }

    $log_file = __DIR__ . '/logs/po_upload_debug.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $log_entry = "[{$timestamp}] {$message}{$context_str}\n";

    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Helper function to normalize item description for matching
function normalizeItemDescription($desc) {
    $desc = trim($desc);
    // Remove leading/trailing single quotes
    $desc = preg_replace("/^'+|'+$/", '', $desc);
    // Remove leading/trailing double quotes
    $desc = preg_replace('/^"+|"+$/', '', $desc);
    // Normalize various whitespace characters
    // Replace non-breaking spaces (UTF-8: \xC2\xA0, Unicode: \u00A0)
    $desc = preg_replace('/\xC2\xA0/', ' ', $desc);
    // Replace multiple spaces with single space
    $desc = preg_replace('/\s+/', ' ', $desc);
    // Normalize different line break styles to single newline
    $desc = str_replace(array("\r\n", "\r"), "\n", $desc);
    // Remove hidden/control characters except newline
    $desc = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $desc);
    // Trim again after all normalization
    $desc = trim($desc);
    return $desc;
}

// Helper function to detect if string contains Chinese characters
function containsChinese($str) {
    return preg_match('/[\x{4e00}-\x{9fff}]/u', $str);
}

// Helper function to get string debug info (length, has newlines, has chinese, etc.)
function getStringDebugInfo($str) {
    return array(
        'length' => mb_strlen($str, 'UTF-8'),
        'has_newlines' => (strpos($str, "\n") !== false || strpos($str, "\r") !== false),
        'has_chinese' => containsChinese($str),
        'first_50_chars' => mb_substr($str, 0, 50, 'UTF-8')
    );
}

// Column indexes for item description and quantity
$itemDescIndex = -1;
$qtyIndex = -1;
$headerRowNumber = -1;
$maxRowsToSearch = min(20, $sheet->getHighestRow()); // Search first 20 rows max for header

// BEAVER DEBUG STEP 3: Starting header detection
beaver_set_debug($response, 'STEP-3: Header detection starting', array(
    'suppdsc' => $suppdsc,
    'max_rows_to_search' => $maxRowsToSearch,
    'total_rows' => $sheet->getHighestRow()
));

// Check if this is Motor Supplier 2 format
if($suppdsc === 'Motor Supplier 2'){
    // Motor Supplier 2 format:
    // - Find header row where column B contains "ITEM"
    // - Column B (index 2) = item description
    // - Column G (index 7) = quantity

    // BEAVER DEBUG: Log Motor Supplier 2 branch
    beaver_set_debug($response, 'STEP-3a: Motor Supplier 2 branch', array(
        'searching_for' => 'ITEM in column B'
    ));

    // BEAVER DEBUG: Collect first rows for inspection
    $debugFirstRows = array();
    for($debugRow = 1; $debugRow <= min(10, $maxRowsToSearch); $debugRow++){
        $colAVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(1, $debugRow)));
        $colBVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(2, $debugRow)));
        $colCVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(3, $debugRow)));
        $colGVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(7, $debugRow)));
        $debugFirstRows[] = array(
            'row' => $debugRow,
            'A' => mb_substr($colAVal, 0, 30),
            'B' => mb_substr($colBVal, 0, 30),
            'C' => mb_substr($colCVal, 0, 30),
            'G' => mb_substr($colGVal, 0, 30)
        );
    }
    beaver_set_debug($response, 'STEP-3b: First 10 rows (A,B,C,G columns)', $debugFirstRows);

    for($searchRow = 1; $searchRow <= $maxRowsToSearch; $searchRow++){
        // Check column B (index 2) for "ITEM"
        $colBValue = strtolower(trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(2, $searchRow))));

        if($colBValue === 'item'){
            $itemDescIndex = 2; // Column B
            $qtyIndex = 7;      // Column G
            $headerRowNumber = $searchRow;
            beaver_set_debug($response, 'STEP-3c: Header found', array(
                'header_row' => $headerRowNumber,
                'item_col' => $itemDescIndex,
                'qty_col' => $qtyIndex
            ));
            break;
        }
    }

    if($headerRowNumber === -1){
        beaver_set_debug($response, 'STEP-3d: Header NOT FOUND - Motor Supplier 2', array(
            'searched_rows' => $maxRowsToSearch,
            'first_rows_data' => $debugFirstRows
        ));
        $response["status"] = 0;
        $response["errorMsg"] = "Header row with 'ITEM' in column B not found. Please ensure the Motor Supplier 2 file has 'ITEM' in column B as a header.";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
} else if($suppdsc === 'Motor Supplier 3'){
    // Motor Supplier 3 format:
    // - Find header row where column D contains "Product Name"
    // - Column D (index 4) = item description (Product Name)
    // - Column H (index 8) = quantity (Order Qty)

    // BEAVER DEBUG: Log Motor Supplier 3 branch
    beaver_set_debug($response, 'STEP-3a: Motor Supplier 3 branch', array(
        'searching_for' => 'Product Name in column D'
    ));

    // BEAVER DEBUG: Collect first rows for inspection
    $debugFirstRows = array();
    for($debugRow = 1; $debugRow <= min(10, $maxRowsToSearch); $debugRow++){
        $colAVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(1, $debugRow)));
        $colDVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(4, $debugRow)));
        $colHVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(8, $debugRow)));
        $debugFirstRows[] = array(
            'row' => $debugRow,
            'A' => mb_substr($colAVal, 0, 30),
            'D' => mb_substr($colDVal, 0, 30),
            'H' => mb_substr($colHVal, 0, 30)
        );
    }
    beaver_set_debug($response, 'STEP-3b: First 10 rows (A,D,H columns)', $debugFirstRows);

    for($searchRow = 1; $searchRow <= $maxRowsToSearch; $searchRow++){
        // Check column D (index 4) for "Product Name" (case-insensitive)
        $colDValue = strtolower(trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(4, $searchRow))));

        if($colDValue === 'product name'){
            $itemDescIndex = 4; // Column D
            $qtyIndex = 8;      // Column H
            $headerRowNumber = $searchRow;
            beaver_set_debug($response, 'STEP-3c: Header found', array(
                'header_row' => $headerRowNumber,
                'item_col' => $itemDescIndex,
                'qty_col' => $qtyIndex
            ));
            break;
        }
    }

    if($headerRowNumber === -1){
        beaver_set_debug($response, 'STEP-3d: Header NOT FOUND - Motor Supplier 3', array(
            'searched_rows' => $maxRowsToSearch,
            'first_rows_data' => $debugFirstRows
        ));
        $response["status"] = 0;
        $response["errorMsg"] = "Header row with 'Product Name' in column D not found. Please ensure the Motor Supplier 3 file has 'Product Name' in column D as a header.";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
} else if($suppdsc === 'Motor Supplier 5'){
    // Motor Supplier 5 format:
    // - Find header row where column D contains "ITEM" and column E contains "QTY"
    // - Column D (index 4) = item description (ITEM)
    // - Column E (index 5) = quantity (QTY)

    // BEAVER DEBUG: Log Motor Supplier 5 branch
    beaver_set_debug($response, 'STEP-3a: Motor Supplier 5 branch', array(
        'searching_for' => 'ITEM in column D and QTY in column E'
    ));

    // BEAVER DEBUG: Collect first rows for inspection
    $debugFirstRows = array();
    for($debugRow = 1; $debugRow <= min(10, $maxRowsToSearch); $debugRow++){
        $colAVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(1, $debugRow)));
        $colDVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(4, $debugRow)));
        $colEVal = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(5, $debugRow)));
        $debugFirstRows[] = array(
            'row' => $debugRow,
            'A' => mb_substr($colAVal, 0, 30),
            'D' => mb_substr($colDVal, 0, 30),
            'E' => mb_substr($colEVal, 0, 30)
        );
    }
    beaver_set_debug($response, 'STEP-3b: First 10 rows (A,D,E columns)', $debugFirstRows);

    for($searchRow = 1; $searchRow <= $maxRowsToSearch; $searchRow++){
        // Check column D (index 4) for "ITEM" and column E (index 5) for "QTY" (case-insensitive)
        $colDValue = strtolower(trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(4, $searchRow))));
        $colEValue = strtolower(trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(5, $searchRow))));

        if($colDValue === 'item' && $colEValue === 'qty'){
            $itemDescIndex = 4; // Column D
            $qtyIndex = 5;      // Column E
            $headerRowNumber = $searchRow;
            beaver_set_debug($response, 'STEP-3c: Header found', array(
                'header_row' => $headerRowNumber,
                'item_col' => $itemDescIndex,
                'qty_col' => $qtyIndex
            ));
            break;
        }
    }

    if($headerRowNumber === -1){
        beaver_set_debug($response, 'STEP-3d: Header NOT FOUND - Motor Supplier 5', array(
            'searched_rows' => $maxRowsToSearch,
            'first_rows_data' => $debugFirstRows
        ));
        $response["status"] = 0;
        $response["errorMsg"] = "Header row with 'ITEM' in column D and 'QTY' in column E not found. Please ensure the Motor Supplier 5 file has 'ITEM' in column D and 'QTY' in column E as headers.";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    // Motor Supplier 1 format (and others):
    // Search all rows to find the header row containing "MARKS" and "QTY" (case-insensitive)
    $marksIndex = -1;
    $tempQtyIndex = -1;

    for($searchRow = 1; $searchRow <= $maxRowsToSearch; $searchRow++){
        $tempMarksIndex = -1;
        $tempQtyIndexRow = -1;

        $rowIterator = $sheet->getRowIterator($searchRow, $searchRow);
        foreach($rowIterator as $row){
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);

            foreach($cellIterator as $cell){
                $cellValue = strtolower(trim((string)getCellValueAsString($cell)));
                $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());

                if($cellValue === 'marks'){
                    $tempMarksIndex = $colIndex;
                }
                if($cellValue === 'qty'){
                    $tempQtyIndexRow = $colIndex;
                }
            }
        }

        // If both MARKS and QTY are found in this row, this is the header row
        if($tempMarksIndex !== -1 && $tempQtyIndexRow !== -1){
            $marksIndex = $tempMarksIndex;
            $tempQtyIndex = $tempQtyIndexRow;
            $headerRowNumber = $searchRow;
            break;
        }
    }

    if($marksIndex === -1){
        $response["status"] = 0;
        $response["errorMsg"] = "Column 'MARKS' not found in the uploaded file. Please ensure the file has a MARKS column header.";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if($tempQtyIndex === -1){
        $response["status"] = 0;
        $response["errorMsg"] = "Column 'QTY' not found in the uploaded file. Please ensure the file has a QTY column header.";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Set the unified column index variables
    $itemDescIndex = $marksIndex;
    $qtyIndex = $tempQtyIndex;
}

// Data rows start after the header row
$dataStartRow = $headerRowNumber + 1;

// Load all items from itemfile for matching
$itemMap = array();
$stmt_items = $link->prepare("SELECT itmcde, itmdsc FROM itemfile");
$stmt_items->execute();
while($rs_item = $stmt_items->fetch()){
    $normalizedDesc = strtolower(normalizeItemDescription($rs_item['itmdsc']));
    $itemMap[$normalizedDesc] = array(
        'itmcde' => $rs_item['itmcde'],
        'itmdsc' => $rs_item['itmdsc']
    );
}

// BEAVER: B - Baseline - Log upload basics
beaver_log('BEAVER [B] Baseline', array(
    'supplier' => $suppdsc,
    'file_name' => $fileName,
    'file_extension' => $fileExtension,
    'header_row' => $headerRowNumber,
    'item_desc_col_index' => $itemDescIndex,
    'qty_col_index' => $qtyIndex,
    'data_start_row' => $dataStartRow,
    'total_rows' => $sheet->getHighestRow()
));

// Parse file rows (start from the row after the header)
$parsedItems = array();
$unmatchedItems = array();
$allReadItems = array(); // Track ALL items read from file for debugging
$skippedReasons = array(); // Track why items were skipped

foreach($sheet->getRowIterator($dataStartRow) as $row){
    $rowIndex = $row->getRowIndex();

    // Check if column A contains "TOTAL:" - stop processing (case-insensitive, trimmed)
    $colAValue = strtoupper(trim((string)getCellValueAsString($sheet->getCellByColumnAndRow(1, $rowIndex))));
    if($colAValue === 'TOTAL:' || $colAValue === 'TOTAL'){
        beaver_log('BEAVER [E] Extract - Stop at TOTAL row', array('row' => $rowIndex));
        break;
    }

    // Get item description value from the detected column
    $itemDescValue = trim((string)getCellValueAsString($sheet->getCellByColumnAndRow($itemDescIndex, $rowIndex)));

    // Get raw qty value for empty row check (we'll get calculated value later if item matches)
    $qtyRawForCheck = getCellValueAsString($sheet->getCellByColumnAndRow($qtyIndex, $rowIndex));

    // Skip empty rows (both item description and qty are empty)
    if($itemDescValue === '' && (empty($qtyRawForCheck) || trim((string)$qtyRawForCheck) === '')){
        continue;
    }

    // Skip if item description is empty
    if($itemDescValue === ''){
        continue;
    }

    // BEAVER: E - Extract - Log raw item value
    beaver_log('BEAVER [E] Extract - Raw item', array(
        'row' => $rowIndex,
        'raw_item_desc' => $itemDescValue,
        'debug_info' => getStringDebugInfo($itemDescValue)
    ));

    // Track all items read (raw value for display)
    if(!in_array($itemDescValue, $allReadItems)){
        $allReadItems[] = $itemDescValue;
    }

    // Normalize the item description value for case-insensitive matching
    $normalizedItemDesc = strtolower(normalizeItemDescription($itemDescValue));

    // BEAVER: A - Analyze - Log normalized value
    beaver_log('BEAVER [A] Analyze - Normalized', array(
        'row' => $rowIndex,
        'normalized' => $normalizedItemDesc,
        'normalized_length' => mb_strlen($normalizedItemDesc, 'UTF-8')
    ));

    // Skip if the normalized value is the header itself (safety check)
    if($normalizedItemDesc === 'marks' || $normalizedItemDesc === 'qty' || $normalizedItemDesc === 'item'){
        $skippedReasons[$itemDescValue] = 'Header row value';
        beaver_log('BEAVER [E] Explain - Skipped header value', array('row' => $rowIndex, 'value' => $itemDescValue));
        continue;
    }

    // STEP 1: Check item match FIRST (before quantity validation)
    // BEAVER: V - Verify - Attempt DB match
    if(!isset($itemMap[$normalizedItemDesc])){
        // Item not matched - add to unmatched list with "no match" reason only
        // Do NOT validate quantity for unmatched items
        if(!in_array($itemDescValue, $unmatchedItems)){
            $unmatchedItems[] = $itemDescValue;
            $skippedReasons[$itemDescValue] = 'No database match found';
        }
        beaver_log('BEAVER [E] Explain - NO MATCH', array(
            'row' => $rowIndex,
            'raw' => $itemDescValue,
            'normalized' => $normalizedItemDesc,
            'reason' => 'No matching itmdsc in itemfile (quantity not checked for unmatched items)'
        ));
        continue; // Skip to next row - no quantity validation needed
    }

    // STEP 2: Item matched - now validate quantity using calculated value for formulas
    $matchedItem = $itemMap[$normalizedItemDesc];

    // Get quantity cell and extract numeric value (handles formulas)
    $qtyCell = $sheet->getCellByColumnAndRow($qtyIndex, $rowIndex);
    $qtyResult = getNumericCellValue($qtyCell);

    // BEAVER: Log quantity extraction details
    beaver_log('BEAVER [A] Analyze - Quantity extraction', array(
        'row' => $rowIndex,
        'item' => $itemDescValue,
        'qty_raw' => $qtyResult['raw'],
        'qty_is_formula' => $qtyResult['is_formula'],
        'qty_formula' => $qtyResult['formula'],
        'qty_calculated' => $qtyResult['calculated'],
        'qty_final_value' => $qtyResult['value'],
        'qty_success' => $qtyResult['success']
    ));

    // Validate quantity - must be numeric and greater than zero
    if(!$qtyResult['success'] || $qtyResult['value'] === null || $qtyResult['value'] <= 0){
        // Item matched but quantity is invalid - show quantity error
        $qtyDisplayValue = $qtyResult['is_formula'] ? '[formula]' : (string)$qtyResult['raw'];
        if($qtyResult['is_formula'] && $qtyResult['calculated'] !== null){
            $qtyDisplayValue = 'formula result: ' . (is_numeric($qtyResult['calculated']) ? $qtyResult['calculated'] : 'non-numeric');
        }
        $skippedReasons[$itemDescValue] = 'Invalid quantity (' . $qtyDisplayValue . ')';
        beaver_log('BEAVER [E] Explain - Matched item, invalid qty', array(
            'row' => $rowIndex,
            'item' => $itemDescValue,
            'matched_itmcde' => $matchedItem['itmcde'],
            'qty_result' => $qtyResult,
            'reason' => 'Quantity must be a positive number'
        ));
        // Add to unmatched list so user sees this row needs fixing
        if(!in_array($itemDescValue, $unmatchedItems)){
            $unmatchedItems[] = $itemDescValue;
        }
        continue;
    }

    // STEP 3: Item matched and quantity is valid - add to parsed items
    $qty = $qtyResult['value'];
    $parsedItems[] = array(
        'itmcde' => $matchedItem['itmcde'],
        'itmdsc' => $matchedItem['itmdsc'],
        'itmqty' => $qty,
        'raw_item_desc' => $itemDescValue
    );
    beaver_log('BEAVER [V] Verify - MATCHED with valid qty', array(
        'row' => $rowIndex,
        'raw' => $itemDescValue,
        'matched_itmcde' => $matchedItem['itmcde'],
        'matched_itmdsc' => $matchedItem['itmdsc'],
        'final_qty' => $qty,
        'qty_was_formula' => $qtyResult['is_formula']
    ));
}

// BEAVER: R - Report - Final totals
beaver_log('BEAVER [R] Report - Final totals', array(
    'total_items_read' => count($allReadItems),
    'matched_items' => count($parsedItems),
    'unmatched_items' => count($unmatchedItems),
    'skipped_reasons_count' => count($skippedReasons)
));

// BEAVER DEBUG STEP 4: Data parsing complete
beaver_set_debug($response, 'STEP-4: Data parsing complete', array(
    'total_items_read' => count($allReadItems),
    'matched_items_count' => count($parsedItems),
    'unmatched_items_count' => count($unmatchedItems),
    'data_start_row' => $dataStartRow,
    'item_desc_col' => $itemDescIndex,
    'qty_col' => $qtyIndex,
    'first_5_read_items' => array_slice($allReadItems, 0, 5),
    'first_5_unmatched' => array_slice($unmatchedItems, 0, 5)
));

// Check if any items were parsed
if(empty($parsedItems) && empty($unmatchedItems)){
    // If we read items from the file but all were filtered out (empty normalized values, etc.)
    if(!empty($allReadItems)){
        // Show all items that were read but couldn't be processed
        $response["status"] = 2; // Use unmatched modal instead of generic alert
        $response["unmatched_items"] = $allReadItems;
        $response["skipped_reasons"] = $skippedReasons;
        $response["errorMsg"] = "All " . count($allReadItems) . " item(s) in the file could not be matched or processed.";
        beaver_log('BEAVER [R] Report - All items failed', array(
            'all_read_items' => $allReadItems,
            'skipped_reasons' => $skippedReasons
        ));
    } else {
        // Truly no items found - file is empty or format is wrong
        $response["status"] = 0;
        $response["errorMsg"] = "No items found in the uploaded file. Please ensure the file has data rows below the MARKS/QTY header row.";
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// If there are unmatched items, return error with detailed list
if(!empty($unmatchedItems)){
    $response["status"] = 2; // Status 2 = unmatched items
    $response["unmatched_items"] = $unmatchedItems;
    $response["skipped_reasons"] = isset($skippedReasons) ? $skippedReasons : array();
    $response["errorMsg"] = count($unmatchedItems) . " item(s) could not be matched.";
    beaver_log('BEAVER [R] Report - Unmatched items returned', array(
        'unmatched_count' => count($unmatchedItems),
        'matched_count' => count($parsedItems)
    ));
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Look up latest prices for each item
$itemPrices = array();
$missingPrices = array();

// First, get unique item codes and aggregate quantities for display
$itemSummary = array();
foreach($parsedItems as $item){
    $itmcde = $item['itmcde'];
    if(!isset($itemSummary[$itmcde])){
        $itemSummary[$itmcde] = array(
            'itmcde' => $itmcde,
            'itmdsc' => $item['itmdsc'],
            'total_qty' => 0
        );
    }
    $itemSummary[$itmcde]['total_qty'] += floatval($item['itmqty']);
}

foreach($itemSummary as $itmcde => $summary){
    // Check if manual price was provided
    if(isset($manual_prices[$itmcde])){
        // Validate manual price server-side
        $manual_price_value = $manual_prices[$itmcde];
        if(!is_numeric($manual_price_value) || floatval($manual_price_value) < 0){
            $response["status"] = 0;
            $response["errorMsg"] = "Invalid price for item: " . $summary['itmdsc'];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $itemPrices[$itmcde] = floatval($manual_price_value);
        continue;
    }

    // Look up latest price from purchasesorderfile2
    $stmt_price = $link->prepare("
        SELECT po2.untprc
        FROM purchasesorderfile1 po1
        LEFT JOIN purchasesorderfile2 po2 ON po1.docnum = po2.docnum
        WHERE po2.itmcde = ? AND po2.unmcde = ?
        ORDER BY po1.trndte DESC, po2.recid DESC
        LIMIT 1
    ");
    $stmt_price->execute(array($itmcde, $default_unmcde));
    $rs_price = $stmt_price->fetch();

    if($rs_price && isset($rs_price['untprc']) && floatval($rs_price['untprc']) > 0){
        $itemPrices[$itmcde] = floatval($rs_price['untprc']);
    } else {
        // No price found - add to missing prices with aggregated quantity for display
        $missingPrices[$itmcde] = array(
            'itmcde' => $itmcde,
            'itmdsc' => $summary['itmdsc'],
            'itmqty' => $summary['total_qty']
        );
    }
}

// If there are missing prices and this is validate_upload action, return for user input
if(!empty($missingPrices) && $event_action === 'validate_upload'){
    $response["status"] = 3; // Status 3 = missing prices
    $response["missing_prices"] = array_values($missingPrices);
    $response["errorMsg"] = count($missingPrices) . " item(s) have no previous price.";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// At this point, all validations passed and we have all prices
// Proceed with insert

date_default_timezone_set('Asia/Manila');
$current_datetime = date("Y-m-d H:i:s");
$current_date = date("Y-m-d");

// Generate docnum
$stmt_docnum = $link->prepare("SELECT docnum FROM purchasesorderfile1 WHERE trncde='POR' ORDER BY docnum DESC LIMIT 1");
$stmt_docnum->execute();
$rs_docnum = $stmt_docnum->fetch();

if(empty($rs_docnum)){
    $docnum = "POR-00001";
} else {
    $docnum = Lnexts($rs_docnum['docnum']);
}

// Generate file batch number for history
$file_batchno = 'PO_UPLOAD_' . $fileName . '_' . $current_datetime;

// Start transaction-like behavior using try-catch with manual rollback
$insert_success = true;
$error_message = '';

try {
    // Insert into purchasesorderfile1
    // Note: purchasesorderfile1 uses suppcde only (suppdsc is looked up from supplierfile when needed)
    $arr_header = array();
    $arr_header['docnum'] = $docnum;
    $arr_header['trndte'] = $current_date;
    $arr_header['suppcde'] = $suppcde;
    $arr_header['ordernum'] = $ordernum;
    $arr_header['remarks'] = $remarks;
    $arr_header['usercode'] = $current_usercode;
    $arr_header['trncde'] = 'POR';
    $arr_header['trntot'] = 0; // Will update after detail rows

    PDO_InsertRecord($link, 'purchasesorderfile1', $arr_header, false);

    // Insert detail rows
    $trntot = 0;
    $inserted_items = array();

    foreach($parsedItems as $item){
        $itmcde = $item['itmcde'];
        $itmqty = $item['itmqty'];

        // Get price (either from lookup or manual input)
        $untprc = isset($itemPrices[$itmcde]) ? $itemPrices[$itmcde] : 0;

        // Check manual prices again
        if($untprc == 0 && isset($manual_prices[$itmcde])){
            $untprc = floatval($manual_prices[$itmcde]);
        }

        $extprc = $itmqty * $untprc;
        $trntot += $extprc;

        $arr_detail = array();
        $arr_detail['docnum'] = $docnum;
        $arr_detail['trncde'] = 'POR';
        $arr_detail['itmcde'] = $itmcde;
        $arr_detail['itmqty'] = $itmqty;
        $arr_detail['unmcde'] = $default_unmcde;
        $arr_detail['untprc'] = $untprc;
        $arr_detail['extprc'] = $extprc;

        PDO_InsertRecord($link, 'purchasesorderfile2', $arr_detail, false);

        $inserted_items[] = array(
            'itmcde' => $itmcde,
            'itmdsc' => $item['itmdsc'],
            'itmqty' => $itmqty,
            'untprc' => $untprc,
            'extprc' => $extprc
        );

        // Insert into po_upld_history
        $clean_filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileName . '_' . date('Y-m-d_H_i_s'));

        $arr_history = array();
        $arr_history['file_name'] = $clean_filename;
        $arr_history['docnum'] = $docnum;
        $arr_history['date_uploaded'] = $current_datetime;
        $arr_history['ordernum'] = $ordernum;
        $arr_history['itmcde'] = $itmcde;
        $arr_history['itmqty'] = $itmqty;
        $arr_history['untprc'] = $untprc;
        $arr_history['extprc'] = $extprc;
        $arr_history['status'] = 'success';
        $arr_history['usercode'] = $current_usercode;

        PDO_InsertRecord($link, 'po_upld_history', $arr_history, false);
    }

    // Update trntot in header
    $arr_update = array();
    $arr_update['trntot'] = $trntot;
    PDO_UpdateRecord($link, 'purchasesorderfile1', $arr_update, "docnum = ?", array($docnum));

    // Log activity
    $username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
    $username_full_name = '';
    if(isset($_SESSION['recid'])){
        $stmt_user = $link->prepare("SELECT full_name FROM users WHERE recid = ? LIMIT 1");
        $stmt_user->execute(array($_SESSION['recid']));
        $rs_user = $stmt_user->fetch();
        if($rs_user){
            $username_full_name = $rs_user['full_name'];
        }
    }

    $xtrndte = date("Y-m-d H:i:s");
    $xprog_module = "PURCHASE ORDER FILE UPLOAD";
    $xactivity = "upload";
    $xremarks = "Uploaded PO file: " . $fileName . " (Order#: " . $ordernum . ", Doc#: " . $docnum . ")";
    PDO_UserActivityLog($link, $username_session, '', $xtrndte, $xprog_module, $xactivity, $username_full_name, $xremarks, 0, '', 'POR', '', '', $username_session, $docnum, $fileName);

} catch(Exception $e){
    $insert_success = false;
    $error_message = $e->getMessage();

    // Rollback - delete any inserted records using parameterized queries
    $stmt_rollback1 = $link->prepare("DELETE FROM purchasesorderfile1 WHERE docnum = ?");
    $stmt_rollback1->execute(array($docnum));

    $stmt_rollback2 = $link->prepare("DELETE FROM purchasesorderfile2 WHERE docnum = ?");
    $stmt_rollback2->execute(array($docnum));

    $stmt_rollback3 = $link->prepare("DELETE FROM po_upld_history WHERE docnum = ?");
    $stmt_rollback3->execute(array($docnum));
}

if(!$insert_success){
    $response["status"] = 0;
    $response["errorMsg"] = "Error during upload: " . $error_message;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Success response
$response["status"] = 1;
$response["docnum"] = $docnum;
$response["ordernum"] = $ordernum;
$response["suppdsc"] = $suppdsc;
$response["remarks"] = $remarks;
$response["trntot"] = $trntot;
$response["item_count"] = count($inserted_items);
$response["items"] = $inserted_items;

// BEAVER DEBUG STEP 5: Success
beaver_set_debug($response, 'STEP-5: Upload successful', array(
    'docnum' => $docnum,
    'item_count' => count($inserted_items),
    'trntot' => $trntot
));

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
