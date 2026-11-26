<?php
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Legacy action for backwards compatibility
if($action === 'check_unique'){
    $itmcde = isset($_POST['itmcde']) ? trim($_POST['itmcde']) : '';
    $unmcde = isset($_POST['unmcde']) ? trim($_POST['unmcde']) : '';
    $recid = isset($_POST['recid']) ? trim($_POST['recid']) : '';

    if($itmcde === '' || $unmcde === ''){
        echo json_encode(['exists' => false]);
        exit;
    }

    // Check if this unmcde already exists for this item (excluding current record if editing)
    if($recid !== ''){
        $sql = "SELECT COUNT(*) as cnt FROM itemunitfile WHERE itmcde = ? AND unmcde = ? AND recid != ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$itmcde, $unmcde, $recid]);
    } else {
        $sql = "SELECT COUNT(*) as cnt FROM itemunitfile WHERE itmcde = ? AND unmcde = ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$itmcde, $unmcde]);
    }

    $row = $stmt->fetch();
    $exists = ($row && $row['cnt'] > 0);

    echo json_encode(['exists' => $exists]);
    exit;
}

// Combined validation action for save operations
if($action === 'validate_save'){
    $itmcde = isset($_POST['itmcde']) ? trim($_POST['itmcde']) : '';
    $unmcde = isset($_POST['unmcde']) ? trim($_POST['unmcde']) : '';
    $conversion = isset($_POST['conversion']) ? trim($_POST['conversion']) : '';
    $recid = isset($_POST['recid']) ? trim($_POST['recid']) : '';

    // Validate required fields
    if($itmcde === '' || $unmcde === ''){
        echo json_encode(['valid' => false, 'message' => 'Item code and Unit of Measure are required.']);
        exit;
    }

    // Check UOM uniqueness per item
    if($recid !== ''){
        // Edit mode: exclude current record
        $sql = "SELECT COUNT(*) as cnt FROM itemunitfile WHERE itmcde = ? AND unmcde = ? AND recid != ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$itmcde, $unmcde, $recid]);
    } else {
        // Insert mode: check all records for this item
        $sql = "SELECT COUNT(*) as cnt FROM itemunitfile WHERE itmcde = ? AND unmcde = ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$itmcde, $unmcde]);
    }

    $row = $stmt->fetch();
    if($row && $row['cnt'] > 0){
        echo json_encode(['valid' => false, 'message' => 'This Unit of Measure already exists for this item.']);
        exit;
    }

    // All validations passed
    echo json_encode(['valid' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
