<?php
session_start();

require_once("resources/db_init.php");
require "resources/connect4.php";

date_default_timezone_set('Asia/Manila');

function h_ajax($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$response = array(
    "status" => 1,
    "html" => "",
    "message" => "",
    "available_stock" => null
);

if (!isset($_POST['event_action'])) {
    $response["status"] = 0;
    $response["message"] = "Missing event action.";
    echo json_encode($response);
    return;
}

if ($_POST['event_action'] === 'search_item') {
    $search_item = isset($_POST['search_item']) ? trim((string)$_POST['search_item']) : '';
    $response["html"] .= "<table class='table table-sm table-striped'>";
    $response["html"] .= "<thead><tr><th>Item</th><th>Code</th><th class='text-center'>Action</th></tr></thead><tbody>";

    $sql = "SELECT itmcde, itmdsc
            FROM itemfile
            WHERE (? = '' OR itmdsc LIKE ? OR itmcde LIKE ?)
            ORDER BY itmdsc ASC
            LIMIT 50";
    $stmt = $link->prepare($sql);
    $like = '%' . $search_item . '%';
    $stmt->execute(array($search_item, $like, $like));

    $has_rows = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_rows = true;
        $label = $row['itmdsc'] . ' [' . $row['itmcde'] . ']';
        $response["html"] .= "<tr>";
        $response["html"] .= "<td>" . h_ajax($row['itmdsc']) . "</td>";
        $response["html"] .= "<td>" . h_ajax($row['itmcde']) . "</td>";
        $response["html"] .= "<td class='text-center'>";
        $response["html"] .= '<button type="button" class="btn btn-sm btn-primary" onclick=\'selectWarehouseTransactionItem('
            . json_encode($row['itmcde']) . ', '
            . json_encode($label)
            . ')\'>' . "Select</button>";
        $response["html"] .= "</td>";
        $response["html"] .= "</tr>";
    }

    if (!$has_rows) {
        $response["html"] .= "<tr><td colspan='3' class='text-center text-muted'>No items found.</td></tr>";
    }

    $response["html"] .= "</tbody></table>";
    echo json_encode($response);
    return;
}

if ($_POST['event_action'] === 'stock_preview') {
    $floor_id = isset($_POST['floor_id']) ? trim((string)$_POST['floor_id']) : '';
    $itmcde = isset($_POST['itmcde']) ? trim((string)$_POST['itmcde']) : '';
    $movement_date = isset($_POST['movement_date']) ? trim((string)$_POST['movement_date']) : '';
    $exclude_recids = array();

    if (isset($_POST['exclude_recids']) && is_array($_POST['exclude_recids'])) {
        foreach ($_POST['exclude_recids'] as $exclude_recid) {
            $exclude_recid = trim((string)$exclude_recid);
            if ($exclude_recid !== '' && ctype_digit($exclude_recid)) {
                $exclude_recids[] = $exclude_recid;
            }
        }
    }

    if ($floor_id === '' || $itmcde === '' || $movement_date === '') {
        $response["status"] = 0;
        $response["message"] = "Missing stock preview data.";
        echo json_encode($response);
        return;
    }

    $movement_date_db = date('Y-m-d H:i:s', strtotime($movement_date));

    $stmt_floor = $link->prepare("SELECT floor_name, floor_no FROM warehouse_floor WHERE warehouse_floor_id = ? LIMIT 1");
    $stmt_floor->execute(array($floor_id));
    $floor_row = $stmt_floor->fetch(PDO::FETCH_ASSOC);

    $stmt_item = $link->prepare("SELECT itmdsc FROM itemfile WHERE itmcde = ? LIMIT 1");
    $stmt_item->execute(array($itmcde));
    $item_row = $stmt_item->fetch(PDO::FETCH_ASSOC);

    $sql_stock = "SELECT COALESCE(SUM(stkqty), 0) AS current_stock
                  FROM warehouse_stock_movement
                  WHERE floor_id = ?
                    AND itmcde = ?
                    AND movement_date <= ?";
    $stock_params = array($floor_id, $itmcde, $movement_date_db);

    if (!empty($exclude_recids)) {
        $sql_stock .= " AND recid NOT IN (" . implode(',', array_fill(0, count($exclude_recids), '?')) . ")";
        foreach ($exclude_recids as $exclude_recid) {
            $stock_params[] = $exclude_recid;
        }
    }

    $stmt_stock = $link->prepare($sql_stock);
    $stmt_stock->execute($stock_params);
    $stock_row = $stmt_stock->fetch(PDO::FETCH_ASSOC);

    $floor_label = '';
    if (!empty($floor_row)) {
        $parts = array();
        if (trim((string)$floor_row['floor_name']) !== '') {
            $parts[] = trim((string)$floor_row['floor_name']);
        }
        if (trim((string)$floor_row['floor_no']) !== '') {
            $parts[] = 'Floor ' . trim((string)$floor_row['floor_no']);
        }
        $floor_label = implode(' / ', $parts);
    }

    $current_stock = !empty($stock_row) ? (float)$stock_row['current_stock'] : 0;
    $stock_class = ($current_stock >= 0) ? 'text-success' : 'text-danger';
    $response["available_stock"] = $current_stock;

    $response["html"] = ""
        . "<div class='small text-muted mb-1'>"
        . "As of " . h_ajax(date('Y-m-d H:i', strtotime($movement_date_db)))
        . "</div>"
        . "<div><strong>" . h_ajax(!empty($item_row) ? (string)$item_row['itmdsc'] : $itmcde) . "</strong></div>"
        . "<div class='small text-muted'>" . h_ajax($floor_label) . "</div>"
        . "<div class='" . $stock_class . " fw-bold mt-2'>"
        . "Current Stock: " . number_format($current_stock, 2)
        . "</div>"
        . "<div class='small text-muted mt-1'>"
        . "Maximum allowed quantity: " . number_format(max($current_stock, 0), 2)
        . "</div>";

    echo json_encode($response);
    return;
}

$response["status"] = 0;
$response["message"] = "Unsupported event action.";
echo json_encode($response);
