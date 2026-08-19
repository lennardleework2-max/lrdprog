<?php
// Suppress PHP error display to prevent corrupting JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set JSON content type header
header('Content-Type: application/json');

session_start();

// BEAVER METHOD: Debug flag - set to false after debugging
$DEBUG_MODE = true;
$debug_steps = array();

function addDebugStep(&$steps, $message) {
    global $DEBUG_MODE;
    if ($DEBUG_MODE) {
        $steps[] = $message;
    }
}

/**
 * Clean uploaded ordernum by removing leading/trailing single or double quotes.
 * Does NOT remove quotes inside the value.
 *
 * @param string $ordernum The raw ordernum value
 * @return string The cleaned ordernum
 */
function cleanOrdernum($ordernum) {
    $ordernum = trim((string)$ordernum);
    $ordernum = trim($ordernum, "'\"");
    $ordernum = trim($ordernum);
    return $ordernum;
}

$response = array(
    "status" => 1,
    "errorMsg" => "",
    "total_processed" => 0,
    "total_success" => 0,
    "total_no_match" => 0,
    "total_skipped" => 0,
    "results" => array()
);

addDebugStep($debug_steps, "STEP 1: Script started");

// Check required files exist
addDebugStep($debug_steps, "STEP 2: Checking required files");

try {
    require_once("resources/db_init.php");
    addDebugStep($debug_steps, "STEP 2a: db_init.php loaded");
} catch (Exception $e) {
    addDebugStep($debug_steps, "FAILED at db_init.php: " . $e->getMessage());
}

try {
    require_once("resources/connect4.php");
    addDebugStep($debug_steps, "STEP 2b: connect4.php loaded");
} catch (Exception $e) {
    addDebugStep($debug_steps, "FAILED at connect4.php: " . $e->getMessage());
}

try {
    require_once("resources/lx2.pdodb.php");
    addDebugStep($debug_steps, "STEP 2c: lx2.pdodb.php loaded");
} catch (Exception $e) {
    addDebugStep($debug_steps, "FAILED at lx2.pdodb.php: " . $e->getMessage());
}

try {
    require_once("resources/stdfunc100.php");
    addDebugStep($debug_steps, "STEP 2d: stdfunc100.php loaded");
} catch (Exception $e) {
    addDebugStep($debug_steps, "FAILED at stdfunc100.php: " . $e->getMessage());
}

try {
    require 'vendor/autoload.php';
    addDebugStep($debug_steps, "STEP 2e: vendor/autoload.php loaded");
} catch (Exception $e) {
    addDebugStep($debug_steps, "FAILED at vendor/autoload.php: " . $e->getMessage());
}

use PhpOffice\PhpSpreadsheet\IOFactory;

addDebugStep($debug_steps, "STEP 3: All requires completed");

// Check session authentication
addDebugStep($debug_steps, "STEP 4: Checking session");
if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    $response["status"] = 0;
    $response["errorMsg"] = "Session expired. Please login again.";
    if ($DEBUG_MODE) $response["debug_steps"] = $debug_steps;
    echo json_encode($response);
    exit;
}
addDebugStep($debug_steps, "STEP 4: Session valid - user: " . $_SESSION['userdesc']);

// Check delete permission
addDebugStep($debug_steps, "STEP 5: Checking permissions");
$filename = 'sales_del_upload.php';
$has_delete_permission = false;

if(isset($link) && $link){
    addDebugStep($debug_steps, "STEP 5a: Database link exists");
    $select_db_crud = "SELECT * FROM user_menus WHERE usercode = ? AND menprogram = ?";
    $stmt_crud = $link->prepare($select_db_crud);
    $stmt_crud->execute(array($_SESSION['usercode'], $filename));
    $rs_crud = $stmt_crud->fetch();

    if(!empty($rs_crud) && $rs_crud["delete"] == 1){
        $has_delete_permission = true;
    } else if(isset($_SESSION['userdesc']) && $_SESSION['userdesc'] == "admin"){
        $has_delete_permission = true;
    }
} else {
    addDebugStep($debug_steps, "STEP 5a: WARNING - Database link is null!");
}

if(!$has_delete_permission){
    $response["status"] = 0;
    $response["errorMsg"] = "You do not have permission to delete records.";
    if ($DEBUG_MODE) $response["debug_steps"] = $debug_steps;
    echo json_encode($response);
    exit;
}
addDebugStep($debug_steps, "STEP 5: Permission granted");

// Validate file upload
addDebugStep($debug_steps, "STEP 6: Checking file upload");
addDebugStep($debug_steps, "STEP 6a: FILES keys: " . implode(', ', array_keys($_FILES)));

if(!isset($_FILES['xfile'])){
    addDebugStep($debug_steps, "STEP 6b: FAILED - xfile not in FILES");
    $response["status"] = 0;
    $response["errorMsg"] = "No file uploaded.";
    if ($DEBUG_MODE) $response["debug_steps"] = $debug_steps;
    echo json_encode($response);
    exit;
}

addDebugStep($debug_steps, "STEP 6b: xfile exists, error code: " . $_FILES['xfile']['error']);

if($_FILES['xfile']['error'] !== 0){
    $response["status"] = 0;
    $response["errorMsg"] = "File upload error. Code: " . $_FILES['xfile']['error'];
    if ($DEBUG_MODE) $response["debug_steps"] = $debug_steps;
    echo json_encode($response);
    exit;
}

$filePath = $_FILES['xfile']['tmp_name'];
$originalFileName = $_FILES['xfile']['name'];
$fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

addDebugStep($debug_steps, "STEP 6c: File name: " . $originalFileName);
addDebugStep($debug_steps, "STEP 6d: Extension: " . $fileExtension);
addDebugStep($debug_steps, "STEP 6e: Temp path: " . $filePath);

// Validate file extension
if(!in_array($fileExtension, array('xls', 'xlsx'))){
    $response["status"] = 0;
    $response["errorMsg"] = "Invalid file type. Only XLS and XLSX files are allowed.";
    if ($DEBUG_MODE) $response["debug_steps"] = $debug_steps;
    echo json_encode($response);
    exit;
}
addDebugStep($debug_steps, "STEP 6f: Extension valid");

// Check temp file exists
if(!file_exists($filePath)){
    addDebugStep($debug_steps, "STEP 6g: FAILED - Temp file does not exist");
    $response["status"] = 0;
    $response["errorMsg"] = "Uploaded file could not be found.";
    if ($DEBUG_MODE) $response["debug_steps"] = $debug_steps;
    echo json_encode($response);
    exit;
}
addDebugStep($debug_steps, "STEP 6g: Temp file exists");

// Get current user code
$current_usercode = isset($_POST['usercode']) && trim((string)$_POST['usercode']) !== ''
    ? trim((string)$_POST['usercode'])
    : (isset($_SESSION['usercode']) ? trim((string)$_SESSION['usercode']) : '');

addDebugStep($debug_steps, "STEP 7: Usercode: " . $current_usercode);

// Set timezone
date_default_timezone_set('Asia/Manila');
$current_datetime = date('Y-m-d H:i:s');

// Generate file_name for history: [original_filename]-[date_time]
$file_name_for_history = $originalFileName . '-' . date('Y-m-d_H_i_s');

addDebugStep($debug_steps, "STEP 8: Starting Excel processing");

try {
    addDebugStep($debug_steps, "STEP 9: Loading spreadsheet with IOFactory");

    // Load the spreadsheet
    $spreadsheet = IOFactory::load($filePath);
    addDebugStep($debug_steps, "STEP 9a: Spreadsheet loaded successfully");

    $sheet = $spreadsheet->getActiveSheet();
    addDebugStep($debug_steps, "STEP 9b: Active sheet retrieved");

    $highestRow = $sheet->getHighestRow();
    addDebugStep($debug_steps, "STEP 9c: Highest row: " . $highestRow);

    $total_processed = 0;
    $total_success = 0;
    $total_no_match = 0;
    $total_skipped = 0;
    $results = array();

    addDebugStep($debug_steps, "STEP 10: Starting row iteration from row 2");

    // Process rows starting from row 2 (row 1 is header)
    for($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++){
        addDebugStep($debug_steps, "STEP 10a: Processing row " . $rowIndex);

        // Get value from Column A
        $cell = $sheet->getCell('A' . $rowIndex);
        $ordernum_raw = $cell->getValue();

        // Handle RichText values
        if($ordernum_raw instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText){
            $ordernum_raw = $ordernum_raw->getPlainText();
        }

        // Clean the value: trim whitespace and remove leading/trailing quotes
        $ordernum = cleanOrdernum($ordernum_raw);

        addDebugStep($debug_steps, "STEP 10b: Row $rowIndex ordernum: '$ordernum'");

        // Skip blank rows
        if($ordernum === ''){
            $total_skipped++;
            addDebugStep($debug_steps, "STEP 10c: Row $rowIndex skipped (blank)");
            continue;
        }

        $total_processed++;
        addDebugStep($debug_steps, "STEP 10d: Row $rowIndex processing ordernum: $ordernum");

        // Check if ordernum exists in tranfile1 with trncde = 'SAL'
        // SAFETY: Only match records where ordernum is NOT NULL and NOT empty/whitespace-only
        $select_tranfile1 = "SELECT docnum, ordernum FROM tranfile1
            WHERE ordernum = ?
            AND trncde = 'SAL'
            AND ordernum IS NOT NULL
            AND TRIM(ordernum) <> ''
            LIMIT 1";
        $stmt_tranfile1 = $link->prepare($select_tranfile1);
        $stmt_tranfile1->execute(array($ordernum));
        $rs_tranfile1 = $stmt_tranfile1->fetch();

        // Additional PHP validation: verify matched ordernum is valid before deletion
        $matched_ordernum_valid = false;
        if($rs_tranfile1 && !empty($rs_tranfile1['docnum'])){
            $matched_ordernum_db = isset($rs_tranfile1['ordernum']) ? $rs_tranfile1['ordernum'] : null;
            // Verify the matched ordernum is not null, empty, or whitespace-only
            if($matched_ordernum_db !== null && trim((string)$matched_ordernum_db) !== ''){
                $matched_ordernum_valid = true;
            }
        }

        if($rs_tranfile1 && !empty($rs_tranfile1['docnum']) && $matched_ordernum_valid){
            // Found a valid matching SAL transaction with non-empty ordernum
            $matched_docnum = trim((string)$rs_tranfile1['docnum']);
            addDebugStep($debug_steps, "STEP 10e: Found match - docnum: $matched_docnum");

            // Additional safety: verify docnum is also valid
            if($matched_docnum === ''){
                // Invalid docnum - treat as no match
                $arr_history = array();
                $arr_history['file_name'] = $file_name_for_history;
                $arr_history['ordernum'] = $ordernum;
                $arr_history['matched_salnum'] = null;
                $arr_history['date_uploaded'] = $current_datetime;
                $arr_history['usercode'] = $current_usercode;
                $arr_history['status'] = 'NO MATCHED ORDERNUM';
                PDO_InsertRecord($link, 'sales_upld_delete_history', $arr_history, false);

                $total_no_match++;
                $results[] = array(
                    'ordernum' => $ordernum,
                    'matched_salnum' => null,
                    'status' => 'NO MATCHED ORDERNUM'
                );
                continue;
            }

            // Begin transaction for delete + history insert
            $link->beginTransaction();
            addDebugStep($debug_steps, "STEP 10f: Transaction started");

            try {
                // Delete from tranfile2 first (child records)
                $delete_tranfile2 = "DELETE FROM tranfile2 WHERE docnum = ?";
                $stmt_del2 = $link->prepare($delete_tranfile2);
                $stmt_del2->execute(array($matched_docnum));
                addDebugStep($debug_steps, "STEP 10g: Deleted from tranfile2");

                // Delete from tranfile1 (parent record)
                // SAFETY: Include ordernum validation in delete query as well
                $delete_tranfile1 = "DELETE FROM tranfile1
                    WHERE docnum = ?
                    AND trncde = 'SAL'
                    AND ordernum IS NOT NULL
                    AND TRIM(ordernum) <> ''";
                $stmt_del1 = $link->prepare($delete_tranfile1);
                $stmt_del1->execute(array($matched_docnum));
                addDebugStep($debug_steps, "STEP 10h: Deleted from tranfile1");

                // Insert into sales_upld_delete_history with SUCCESS status
                $arr_history = array();
                $arr_history['file_name'] = $file_name_for_history;
                $arr_history['ordernum'] = $ordernum;
                $arr_history['matched_salnum'] = $matched_docnum;
                $arr_history['date_uploaded'] = $current_datetime;
                $arr_history['usercode'] = $current_usercode;
                $arr_history['status'] = 'SUCCESS';
                PDO_InsertRecord($link, 'sales_upld_delete_history', $arr_history, false);
                addDebugStep($debug_steps, "STEP 10i: Inserted history with SUCCESS");

                // Commit transaction
                $link->commit();
                addDebugStep($debug_steps, "STEP 10j: Transaction committed");

                $total_success++;
                $results[] = array(
                    'ordernum' => $ordernum,
                    'matched_salnum' => $matched_docnum,
                    'status' => 'SUCCESS'
                );

            } catch(Exception $e) {
                // Rollback on error
                $link->rollBack();
                addDebugStep($debug_steps, "STEP 10k: Transaction rolled back - " . $e->getMessage());

                // Log error but don't expose details
                error_log("sales_del_upload_ajax: Error deleting docnum " . $matched_docnum . ": " . $e->getMessage());

                // Still count as no match since delete failed
                $total_no_match++;
                $results[] = array(
                    'ordernum' => $ordernum,
                    'matched_salnum' => null,
                    'status' => 'NO MATCHED ORDERNUM'
                );
            }

        } else {
            addDebugStep($debug_steps, "STEP 10e: No match found for ordernum: $ordernum");

            // No matching SAL transaction found
            // Insert into sales_upld_delete_history with NO MATCHED ORDERNUM status
            $arr_history = array();
            $arr_history['file_name'] = $file_name_for_history;
            $arr_history['ordernum'] = $ordernum;
            $arr_history['matched_salnum'] = null;
            $arr_history['date_uploaded'] = $current_datetime;
            $arr_history['usercode'] = $current_usercode;
            $arr_history['status'] = 'NO MATCHED ORDERNUM';
            PDO_InsertRecord($link, 'sales_upld_delete_history', $arr_history, false);
            addDebugStep($debug_steps, "STEP 10f: Inserted history with NO MATCHED ORDERNUM");

            $total_no_match++;
            $results[] = array(
                'ordernum' => $ordernum,
                'matched_salnum' => null,
                'status' => 'NO MATCHED ORDERNUM'
            );
        }
    }

    addDebugStep($debug_steps, "STEP 11: Row processing complete");
    addDebugStep($debug_steps, "STEP 11a: Processed: $total_processed, Success: $total_success, No Match: $total_no_match, Skipped: $total_skipped");

    // Log user activity
    $username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
    $username_full_name = '';
    if(isset($_SESSION['recid'])){
        $select_db_session_user = 'SELECT * FROM users WHERE recid = ?';
        $stmt_session_user = $link->prepare($select_db_session_user);
        $stmt_session_user->execute(array($_SESSION['recid']));
        $rs_session_user = $stmt_session_user->fetch();
        if($rs_session_user){
            $username_full_name = $rs_session_user["full_name"];
        }
    }

    $xprog_module = "SALES DELETE UPLOAD";
    $xactivity = "delete";
    $xremarks = "Delete upload file: " . $originalFileName . " (Success: " . $total_success . ", No Match: " . $total_no_match . ", Skipped: " . $total_skipped . ")";
    PDO_UserActivityLog($link, $username_session, '', $current_datetime, $xprog_module, $xactivity, $username_full_name, $xremarks, 0, '', '', '', '', $username_session, '', $file_name_for_history);

    addDebugStep($debug_steps, "STEP 12: Activity logged");

    // Sort results: unsuccessful first, then successful
    $failed_results = array();
    $success_results = array();
    foreach($results as $result) {
        if($result['status'] === 'SUCCESS') {
            $success_results[] = $result;
        } else {
            $failed_results[] = $result;
        }
    }
    $sorted_results = array_merge($failed_results, $success_results);

    // Set response data
    $response["total_processed"] = $total_processed;
    $response["total_success"] = $total_success;
    $response["total_no_match"] = $total_no_match;
    $response["total_skipped"] = $total_skipped;
    $response["results"] = $sorted_results;

    addDebugStep($debug_steps, "STEP 13: Response prepared - SUCCESS");

} catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    $response["status"] = 0;
    $response["errorMsg"] = "Error reading file. Please ensure the file is a valid Excel file.";
    addDebugStep($debug_steps, "FAILED at Excel reading: " . $e->getMessage());
    error_log("sales_del_upload_ajax: " . $e->getMessage());
} catch(Exception $e) {
    $response["status"] = 0;
    $response["errorMsg"] = "An error occurred while processing the file.";
    addDebugStep($debug_steps, "FAILED with Exception: " . $e->getMessage());
    error_log("sales_del_upload_ajax: " . $e->getMessage());
} catch(Throwable $e) {
    // Catch fatal errors too
    $response["status"] = 0;
    $response["errorMsg"] = "A fatal error occurred while processing the file.";
    addDebugStep($debug_steps, "FAILED with Throwable: " . $e->getMessage());
    error_log("sales_del_upload_ajax FATAL: " . $e->getMessage());
}

// Add debug steps to response if in debug mode
if ($DEBUG_MODE) {
    $response["debug_steps"] = $debug_steps;
}

echo json_encode($response);
?>
