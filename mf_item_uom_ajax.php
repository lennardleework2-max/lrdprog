<?php
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

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

echo json_encode(['error' => 'Invalid action']);
?>
