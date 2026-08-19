<?php
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

function mf_uom_in_use($link, $unmcde)
{
    $unmcde = trim((string)$unmcde);
    if($unmcde === ''){
        return false;
    }

    $sql = "SELECT 1 FROM itemunitfile WHERE unmcde = ? LIMIT 1";
    $stmt = $link->prepare($sql);
    $stmt->execute([$unmcde]);
    return (bool)$stmt->fetch();
}

// Validation action for save operations
if($action === 'validate_save'){
    $unmdsc = isset($_POST['unmdsc']) ? trim($_POST['unmdsc']) : '';
    $recid = isset($_POST['recid']) ? trim($_POST['recid']) : '';

    // Validate required fields
    if($unmdsc === ''){
        echo json_encode(['valid' => false, 'message' => 'Unit of Measure name is required.']);
        exit;
    }

    // Check UOM name uniqueness
    if($recid !== ''){
        // Edit mode: exclude current record
        $sql = "SELECT COUNT(*) as cnt FROM itemunitmeasurefile WHERE unmdsc = ? AND recid != ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$unmdsc, $recid]);
    } else {
        // Insert mode: check all records
        $sql = "SELECT COUNT(*) as cnt FROM itemunitmeasurefile WHERE unmdsc = ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$unmdsc]);
    }

    $row = $stmt->fetch();
    if($row && $row['cnt'] > 0){
        echo json_encode(['valid' => false, 'message' => 'This Unit of Measure name already exists.']);
        exit;
    }

    // Allow rename only when the UOM code is not referenced anywhere.
    if($recid !== ''){
        $sql = "SELECT unmcde, unmdsc FROM itemunitmeasurefile WHERE recid = ?";
        $stmt = $link->prepare($sql);
        $stmt->execute([$recid]);
        $existingRecord = $stmt->fetch();

        if($existingRecord){
            $existingName = trim($existingRecord['unmdsc']);
            $newName = trim($unmdsc);

            if($existingName !== $newName && mf_uom_in_use($link, $existingRecord['unmcde'])){
                echo json_encode(['valid' => false, 'message' => 'Unit of measure in use, cannot modify']);
                exit;
            }
        }
    }

    // All validations passed
    echo json_encode(['valid' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
