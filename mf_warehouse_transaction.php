<?php
ob_start();
require "includes/main_header.php";
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php

$is_entry_page = !empty($warehouse_transaction_entry_page);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qty_display($value)
{
    return number_format((float)$value, 2);
}

function qty_input($value)
{
    return number_format((float)$value, 2, '.', '');
}

function datetime_input_value($value)
{
    if (empty($value)) {
        return date('Y-m-d\TH:i');
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return date('Y-m-d\TH:i');
    }

    return date('Y-m-d\TH:i', $timestamp);
}

function datetime_db_value($value)
{
    if (trim((string)$value) === '') {
        return '';
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function display_movement_datetime($value)
{
    if (trim((string)$value) === '') {
        return '-';
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return '-';
    }

    return date('m/d/Y h:iA', $timestamp);
}

function floor_label($row)
{
    $parts = array();

    if (isset($row['floor_name']) && trim((string)$row['floor_name']) !== '') {
        $parts[] = trim((string)$row['floor_name']);
    }

    if (isset($row['floor_no']) && trim((string)$row['floor_no']) !== '') {
        $parts[] = 'Floor ' . trim((string)$row['floor_no']);
    }

    return !empty($parts) ? implode(' / ', $parts) : '';
}

function item_label($row)
{
    return $row['itmdsc'] . ' [' . $row['itmcde'] . ']';
}

function floor_dropdown_label($row)
{
    $parts = array();

    if (isset($row['floor_name']) && trim((string)$row['floor_name']) !== '') {
        $parts[] = trim((string)$row['floor_name']);
    }

    if (isset($row['floor_no']) && trim((string)$row['floor_no']) !== '') {
        $parts[] = trim((string)$row['floor_no']);
    }

    return !empty($parts) ? implode(' / ', $parts) : '-';
}

function warehouse_id_from_floor($floor_map, $floor_id)
{
    if ($floor_id !== '' && isset($floor_map[$floor_id])) {
        return (string)$floor_map[$floor_id]['warehouse_id'];
    }

    return '';
}

function next_movement_id($link)
{
    $seed = 'MVNT-000000001';
    $stmt = $link->prepare("SELECT movement_id FROM warehouse_stock_movement ORDER BY movement_id DESC LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($row) || empty($row['movement_id'])) {
        return $seed;
    }

    return LNexts($row['movement_id']);
}

function stock_as_of($link, $floor_id, $itmcde, $movement_date, $exclude_recids = array())
{
    if ($floor_id === '' || $itmcde === '' || $movement_date === '') {
        return 0;
    }

    $sql = "SELECT COALESCE(SUM(stkqty), 0) AS total_stock
            FROM warehouse_stock_movement
            WHERE floor_id = ?
              AND itmcde = ?
              AND movement_date <= ?";

    $params = array($floor_id, $itmcde, $movement_date);

    if (!empty($exclude_recids)) {
        $sql .= " AND recid NOT IN (" . implode(',', array_fill(0, count($exclude_recids), '?')) . ")";
        foreach ($exclude_recids as $recid) {
            $params[] = $recid;
        }
    }

    $stmt = $link->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($row) ? (float)$row['total_stock'] : 0;
}

function load_movement_row($link, $recid, $warehouse_id, $floor_id)
{
    $sql = "SELECT wsm.*,
                   itemfile.itmdsc,
                   src.floor_name,
                   src.floor_no,
                   rel.floor_name AS related_floor_name,
                   rel.floor_no AS related_floor_no,
                   staff.fname,
                   staff.lname,
                   usr.userdesc AS transaction_userdesc
            FROM warehouse_stock_movement wsm
            INNER JOIN warehouse_floor src
                ON src.warehouse_floor_id = wsm.floor_id
            LEFT JOIN warehouse_floor rel
                ON rel.warehouse_floor_id = wsm.related_floor_id
            LEFT JOIN itemfile
                ON itemfile.itmcde = wsm.itmcde
            LEFT JOIN warehouse_staff staff
                ON staff.warehouse_staff_id = wsm.warehouse_staff_id
            LEFT JOIN users usr
                ON usr.usercode = wsm.usercode
            WHERE wsm.recid = ?
              AND src.warehouse_id = ?
              AND wsm.floor_id = ?";
    $stmt = $link->prepare($sql);
    $stmt->execute(array($recid, $warehouse_id, $floor_id));

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function load_movement_row_any($link, $recid)
{
    $sql = "SELECT wsm.*,
                   itemfile.itmdsc,
                   src.floor_name,
                   src.floor_no,
                   rel.floor_name AS related_floor_name,
                   rel.floor_no AS related_floor_no,
                   staff.fname,
                   staff.lname,
                   usr.userdesc AS transaction_userdesc
            FROM warehouse_stock_movement wsm
            INNER JOIN warehouse_floor src
                ON src.warehouse_floor_id = wsm.floor_id
            LEFT JOIN warehouse_floor rel
                ON rel.warehouse_floor_id = wsm.related_floor_id
            LEFT JOIN itemfile
                ON itemfile.itmcde = wsm.itmcde
            LEFT JOIN warehouse_staff staff
                ON staff.warehouse_staff_id = wsm.warehouse_staff_id
            LEFT JOIN users usr
                ON usr.usercode = wsm.usercode
            WHERE wsm.recid = ?";
    $stmt = $link->prepare($sql);
    $stmt->execute(array($recid));

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function find_transfer_pair($link, $row)
{
    if (
        empty($row) ||
        ($row['movement_type'] !== 'TRANSFER_OUT' && $row['movement_type'] !== 'TRANSFER_IN') ||
        empty($row['related_floor_id'])
    ) {
        return false;
    }

    $pair_type = ($row['movement_type'] === 'TRANSFER_OUT') ? 'TRANSFER_IN' : 'TRANSFER_OUT';

    $sql = "SELECT wsm.*,
                   itemfile.itmdsc,
                   src.floor_name,
                   src.floor_no,
                   src.warehouse_id AS source_warehouse_id,
                   src_wh.warehouse_name AS source_warehouse_name,
                   rel.floor_name AS related_floor_name,
                   rel.floor_no AS related_floor_no,
                   rel.warehouse_id AS related_warehouse_id,
                   rel_wh.warehouse_name AS related_warehouse_name,
                   staff.fname,
                   staff.lname,
                   usr.userdesc AS transaction_userdesc
            FROM warehouse_stock_movement wsm
            INNER JOIN warehouse_floor src
                ON src.warehouse_floor_id = wsm.floor_id
            INNER JOIN warehouse src_wh
                ON src_wh.warehouse_id = src.warehouse_id
            LEFT JOIN warehouse_floor rel
                ON rel.warehouse_floor_id = wsm.related_floor_id
            LEFT JOIN warehouse rel_wh
                ON rel_wh.warehouse_id = rel.warehouse_id
            LEFT JOIN itemfile
                ON itemfile.itmcde = wsm.itmcde
            LEFT JOIN warehouse_staff staff
                ON staff.warehouse_staff_id = wsm.warehouse_staff_id
            LEFT JOIN users usr
                ON usr.usercode = wsm.usercode
            WHERE wsm.recid <> ?
              AND wsm.movement_type = ?
              AND wsm.floor_id = ?
              AND wsm.related_floor_id = ?
              AND wsm.itmcde = ?
              AND wsm.qty = ?
              AND ABS(wsm.stkqty) = ABS(?)
              AND wsm.movement_date = ?
              AND wsm.warehouse_staff_id <=> ?
              AND wsm.usercode <=> ?
              AND wsm.remarks <=> ?
            ORDER BY wsm.recid DESC
            LIMIT 1";
    $stmt = $link->prepare($sql);
    $stmt->execute(array(
        $row['recid'],
        $pair_type,
        $row['related_floor_id'],
        $row['floor_id'],
        $row['itmcde'],
        $row['qty'],
        $row['stkqty'],
        $row['movement_date'],
        $row['warehouse_staff_id'],
        $row['usercode'],
        $row['remarks']
    ));

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function build_transaction_display_row($row, $pair_row = false)
{
    $display = $row;
    $display['display_recid'] = $row['recid'];
    $display['display_movement_id'] = $row['movement_id'];
    $display['display_movement_type'] = $row['movement_type'];
    $display['display_source_warehouse_id'] = $row['source_warehouse_id'];
    $display['display_source_warehouse_name'] = $row['source_warehouse_name'];
    $display['display_source_floor_name'] = $row['floor_name'];
    $display['display_source_floor_no'] = $row['floor_no'];
    $display['display_related_warehouse_name'] = $row['related_warehouse_name'];
    $display['display_related_floor_name'] = $row['related_floor_name'];
    $display['display_related_floor_no'] = $row['related_floor_no'];
    $display['display_stkqty_label'] = qty_display($row['stkqty']);
    $display['display_stkqty_class'] = ((float)$row['stkqty'] >= 0) ? 'text-success' : 'text-danger';
    $display['edit_warehouse_id'] = $row['source_warehouse_id'];
    $display['edit_floor_id'] = $row['floor_id'];

    if ($row['movement_type'] !== 'TRANSFER_OUT' && $row['movement_type'] !== 'TRANSFER_IN') {
        return $display;
    }

    $transfer_out = false;
    $transfer_in = false;

    if ($row['movement_type'] === 'TRANSFER_OUT') {
        $transfer_out = $row;
    } else {
        $transfer_in = $row;
    }

    if (!empty($pair_row)) {
        if ($pair_row['movement_type'] === 'TRANSFER_OUT') {
            $transfer_out = $pair_row;
        } elseif ($pair_row['movement_type'] === 'TRANSFER_IN') {
            $transfer_in = $pair_row;
        }
    }

    if ($transfer_out) {
        $display['display_recid'] = $transfer_out['recid'];
        $display['edit_warehouse_id'] = $transfer_out['source_warehouse_id'];
        $display['edit_floor_id'] = $transfer_out['floor_id'];
        $display['display_source_warehouse_id'] = $transfer_out['source_warehouse_id'];
        $display['display_source_warehouse_name'] = $transfer_out['source_warehouse_name'];
        $display['display_source_floor_name'] = $transfer_out['floor_name'];
        $display['display_source_floor_no'] = $transfer_out['floor_no'];
    } else {
        $display['display_source_warehouse_id'] = $row['related_warehouse_id'];
        $display['display_source_warehouse_name'] = $row['related_warehouse_name'];
        $display['display_source_floor_name'] = $row['related_floor_name'];
        $display['display_source_floor_no'] = $row['related_floor_no'];
    }

    if ($transfer_in) {
        $display['display_related_warehouse_name'] = $transfer_in['source_warehouse_name'];
        $display['display_related_floor_name'] = $transfer_in['floor_name'];
        $display['display_related_floor_no'] = $transfer_in['floor_no'];
    } else {
        $display['display_related_warehouse_name'] = $row['source_warehouse_name'];
        $display['display_related_floor_name'] = $row['floor_name'];
        $display['display_related_floor_no'] = $row['floor_no'];
    }

    if ($transfer_out && $transfer_in) {
        $display['display_movement_id'] = $transfer_out['movement_id'] . ' / ' . $transfer_in['movement_id'];
    }

    $display['display_movement_type'] = 'TRANSFER';
    $display['display_stkqty_label'] = 'OUT: -' . qty_display($row['qty']) . ' / IN: +' . qty_display($row['qty']);
    $display['display_stkqty_class'] = '';

    return $display;
}

function blank_form_state($selected_floor_id)
{
    return array(
        'movement_action' => 'ADD',
        'movement_date' => date('Y-m-d\TH:i'),
        'itmcde' => '',
        'item_label' => '',
        'qty' => '',
        'warehouse_staff_id' => '',
        'remarks' => '',
        'source_floor_id' => $selected_floor_id,
        'destination_floor_id' => ''
    );
}

function form_state_from_row($row)
{
    $source_floor_id = $row['floor_id'];
    $destination_floor_id = '';

    if ($row['movement_type'] === 'TRANSFER_OUT') {
        $destination_floor_id = $row['related_floor_id'];
    } elseif ($row['movement_type'] === 'TRANSFER_IN') {
        $source_floor_id = $row['related_floor_id'];
        $destination_floor_id = $row['floor_id'];
    }

    return array(
        'movement_action' => ($row['movement_type'] === 'ADJUST_OUT') ? 'REMOVE' : (($row['movement_type'] === 'ADJUST_IN') ? 'ADD' : 'TRANSFER'),
        'movement_date' => datetime_input_value($row['movement_date']),
        'itmcde' => $row['itmcde'],
        'item_label' => item_label($row),
        'qty' => qty_input($row['qty']),
        'warehouse_staff_id' => (string)$row['warehouse_staff_id'],
        'remarks' => (string)$row['remarks'],
        'source_floor_id' => $source_floor_id,
        'destination_floor_id' => $destination_floor_id
    );
}

$flash = array();
if (isset($_SESSION['warehouse_transaction_flash'])) {
    $flash = $_SESSION['warehouse_transaction_flash'];
    unset($_SESSION['warehouse_transaction_flash']);
}

$warehouse_id = '';
$floor_id = '';

if ($is_entry_page) {
    if (isset($_POST['warehouse_id'])) {
        $warehouse_id = trim((string)$_POST['warehouse_id']);
    } elseif (isset($_GET['warehouse_id'])) {
        $warehouse_id = trim((string)$_GET['warehouse_id']);
    } elseif (isset($_SESSION['warehouse_transaction_context_id'])) {
        $warehouse_id = trim((string)$_SESSION['warehouse_transaction_context_id']);
    }

    if (isset($_POST['source_floor_id'])) {
        $floor_id = trim((string)$_POST['source_floor_id']);
    } elseif (isset($_POST['floor_id'])) {
        $floor_id = trim((string)$_POST['floor_id']);
    } elseif (isset($_GET['floor_id'])) {
        $floor_id = trim((string)$_GET['floor_id']);
    } elseif (isset($_SESSION['warehouse_transaction_context_floor_id'])) {
        $floor_id = trim((string)$_SESSION['warehouse_transaction_context_floor_id']);
    }
}

$warehouse_options = array();
$warehouse_by_id = array();
$floor_map = array();
$floors_by_warehouse = array();
$item_by_id = array();
$staff_by_id = array();

$stmt_warehouse = $link->prepare("SELECT warehouse_id, warehouse_name, location FROM warehouse ORDER BY warehouse_name ASC");
$stmt_warehouse->execute();
while ($row = $stmt_warehouse->fetch(PDO::FETCH_ASSOC)) {
    $warehouse_options[] = $row;
    $warehouse_by_id[$row['warehouse_id']] = $row;
}

$stmt_floor = $link->prepare("SELECT warehouse_floor_id, warehouse_id, floor_name, floor_no FROM warehouse_floor ORDER BY warehouse_id ASC, floor_no ASC, floor_name ASC");
$stmt_floor->execute();
while ($row = $stmt_floor->fetch(PDO::FETCH_ASSOC)) {
    $floor_map[$row['warehouse_floor_id']] = $row;
    if (!isset($floors_by_warehouse[$row['warehouse_id']])) {
        $floors_by_warehouse[$row['warehouse_id']] = array();
    }
    $floors_by_warehouse[$row['warehouse_id']][] = $row;
}

$stmt_item = $link->prepare("SELECT itmcde, itmdsc FROM itemfile ORDER BY itmdsc ASC");
$stmt_item->execute();
while ($row = $stmt_item->fetch(PDO::FETCH_ASSOC)) {
    $item_by_id[$row['itmcde']] = $row;
}

$stmt_staff = $link->prepare("SELECT warehouse_staff_id, fname, lname, role FROM warehouse_staff ORDER BY lname ASC, fname ASC");
$stmt_staff->execute();
while ($row = $stmt_staff->fetch(PDO::FETCH_ASSOC)) {
    $staff_by_id[$row['warehouse_staff_id']] = $row;
}

if ($is_entry_page) {
    if ($warehouse_id !== '' && !isset($warehouse_by_id[$warehouse_id])) {
        $warehouse_id = '';
    }

    if ($floor_id !== '' && (!isset($floor_map[$floor_id]) || $floor_map[$floor_id]['warehouse_id'] !== $warehouse_id)) {
        $floor_id = '';
    }

    if ($warehouse_id !== '') {
        $_SESSION['warehouse_transaction_context_id'] = $warehouse_id;
    } else {
        unset($_SESSION['warehouse_transaction_context_id']);
    }

    if ($floor_id !== '') {
        $_SESSION['warehouse_transaction_context_floor_id'] = $floor_id;
    } else {
        unset($_SESSION['warehouse_transaction_context_floor_id']);
    }
} else {
    unset($_SESSION['warehouse_transaction_context_id']);
    unset($_SESSION['warehouse_transaction_context_floor_id']);
}

$selected_warehouse = ($warehouse_id !== '' && isset($warehouse_by_id[$warehouse_id])) ? $warehouse_by_id[$warehouse_id] : false;
$selected_floor = ($floor_id !== '' && isset($floor_map[$floor_id])) ? $floor_map[$floor_id] : false;
$has_context = ($selected_warehouse !== false && $selected_floor !== false);
$current_ts = date('Y-m-d H:i:s');

$errors = array();
$success_message = '';
$page_warning = '';
$edit_row = false;
$edit_transfer_pair = false;
$edit_recid = '';
$mode = isset($_GET['mode']) ? trim((string)$_GET['mode']) : ($is_entry_page ? 'add' : 'view');
$form_state = blank_form_state($floor_id);

if (isset($_GET['edit'])) {
    $edit_recid = trim((string)$_GET['edit']);
}

if ($edit_recid !== '') {
    $page_warning = 'Editing warehouse transactions is disabled. Transactions can only be viewed or deleted.';
    $edit_recid = '';
    $edit_row = false;
    $edit_transfer_pair = false;
    $mode = $is_entry_page ? 'add' : 'view';
}

if ($mode !== 'add' && $mode !== 'edit') {
    $mode = 'view';
}

if (isset($_POST['form_action']) && ($_POST['form_action'] === 'create' || $_POST['form_action'] === 'update')) {
    $mode = ($_POST['form_action'] === 'update') ? 'edit' : 'add';
    $edit_recid = isset($_POST['recid']) ? trim((string)$_POST['recid']) : '';
    $submitted_form_token = isset($_POST['submission_token']) ? trim((string)$_POST['submission_token']) : '';
    $session_form_token = isset($_SESSION['warehouse_transaction_form_token']) ? trim((string)$_SESSION['warehouse_transaction_form_token']) : '';

    if ($submitted_form_token === '' || $session_form_token === '' || !hash_equals($session_form_token, $submitted_form_token)) {
        $errors[] = 'This transaction form was already submitted. Reload the page and try again.';
    } else {
        unset($_SESSION['warehouse_transaction_form_token']);
    }

    if ($mode === 'edit' && $edit_recid !== '') {
        $edit_row = $has_context ? load_movement_row($link, $edit_recid, $warehouse_id, $floor_id) : load_movement_row_any($link, $edit_recid);
        if ($edit_row) {
            $edit_transfer_pair = find_transfer_pair($link, $edit_row);
        }
    }

    $form_state = array(
        'movement_action' => isset($_POST['movement_action']) ? trim((string)$_POST['movement_action']) : 'ADD',
        'movement_date' => isset($_POST['movement_date']) ? trim((string)$_POST['movement_date']) : date('Y-m-d\TH:i'),
        'itmcde' => isset($_POST['itmcde']) ? trim((string)$_POST['itmcde']) : '',
        'item_label' => isset($_POST['item_label']) ? trim((string)$_POST['item_label']) : '',
        'qty' => isset($_POST['qty']) ? trim((string)$_POST['qty']) : '',
        'warehouse_staff_id' => isset($_POST['warehouse_staff_id']) ? trim((string)$_POST['warehouse_staff_id']) : '',
        'remarks' => isset($_POST['remarks']) ? trim((string)$_POST['remarks']) : '',
        'source_floor_id' => isset($_POST['source_floor_id']) ? trim((string)$_POST['source_floor_id']) : (isset($_POST['floor_id']) ? trim((string)$_POST['floor_id']) : $floor_id),
        'destination_floor_id' => isset($_POST['destination_floor_id']) ? trim((string)$_POST['destination_floor_id']) : ''
    );

    $movement_action = strtoupper($form_state['movement_action']);
    $movement_date_db = datetime_db_value($form_state['movement_date']);
    $qty_value = (float)$form_state['qty'];
    $warehouse_staff_id = ($form_state['warehouse_staff_id'] !== '') ? $form_state['warehouse_staff_id'] : null;
    $remarks = ($form_state['remarks'] !== '') ? $form_state['remarks'] : null;
    $usercode = isset($_SESSION['usercode']) ? trim((string)$_SESSION['usercode']) : '';

    if ((int)$add_crud !== 1 && $_POST['form_action'] === 'create') {
        $errors[] = 'You do not have permission to create transactions.';
    }

    if ((int)$edit_crud !== 1 && $_POST['form_action'] === 'update') {
        $errors[] = 'You do not have permission to edit transactions.';
    }

    if ($movement_action !== 'ADD' && $movement_action !== 'REMOVE' && $movement_action !== 'TRANSFER') {
        $errors[] = 'Invalid transaction type.';
    }

    if ($movement_date_db === '') {
        $errors[] = 'Movement date is required.';
    }

    if ($form_state['itmcde'] === '' || !isset($item_by_id[$form_state['itmcde']])) {
        $errors[] = 'Select an item before saving.';
    } else {
        $form_state['item_label'] = item_label($item_by_id[$form_state['itmcde']]);
    }

    if ($form_state['qty'] === '' || !is_numeric($form_state['qty']) || $qty_value <= 0) {
        $errors[] = 'Quantity must be greater than zero.';
    }

    if ($warehouse_staff_id === null) {
        $errors[] = 'Warehouse staff is required.';
    } elseif (!isset($staff_by_id[$warehouse_staff_id])) {
        $errors[] = 'Select a valid warehouse staff member.';
    }

    if ($usercode === '') {
        $errors[] = 'Logged-in usercode is missing from the current session.';
    }

    $source_floor_id = $form_state['source_floor_id'];
    $destination_floor_id = $form_state['destination_floor_id'];
    $destination_warehouse_id = isset($_POST['destination_warehouse_id']) ? trim((string)$_POST['destination_warehouse_id']) : warehouse_id_from_floor($floor_map, $destination_floor_id);

    if ($warehouse_id === '' || !isset($warehouse_by_id[$warehouse_id])) {
        $errors[] = 'Select a valid source warehouse.';
    }

    if ($source_floor_id === '' || !isset($floor_map[$source_floor_id])) {
        $errors[] = 'Select a valid source floor.';
    } elseif ($warehouse_id !== '' && $floor_map[$source_floor_id]['warehouse_id'] !== $warehouse_id) {
        $errors[] = 'Source floor does not belong to the selected source warehouse.';
    }

    $floor_id = $source_floor_id;
    $selected_warehouse = ($warehouse_id !== '' && isset($warehouse_by_id[$warehouse_id])) ? $warehouse_by_id[$warehouse_id] : false;
    $selected_floor = ($floor_id !== '' && isset($floor_map[$floor_id])) ? $floor_map[$floor_id] : false;
    $has_context = ($selected_warehouse !== false && $selected_floor !== false);

    if ($_POST['form_action'] === 'create') {
        if ($movement_action === 'ADD' || $movement_action === 'REMOVE') {
            $source_floor_id = $floor_id;
            $form_state['source_floor_id'] = $floor_id;
        }

        if ($movement_action === 'TRANSFER') {
            $source_floor_id = ($source_floor_id !== '') ? $source_floor_id : $floor_id;
            $form_state['source_floor_id'] = $source_floor_id;
        }
    }

    if ($movement_action === 'TRANSFER') {
        if ($destination_warehouse_id === '' || !isset($warehouse_by_id[$destination_warehouse_id])) {
            $errors[] = 'Select a valid destination warehouse.';
        }

        if ($source_floor_id === '' || !isset($floor_map[$source_floor_id])) {
            $errors[] = 'Source floor is required.';
        }

        if ($destination_floor_id === '' || !isset($floor_map[$destination_floor_id])) {
            $errors[] = 'Destination floor is required for transfer.';
        } elseif ($destination_warehouse_id !== '' && $floor_map[$destination_floor_id]['warehouse_id'] !== $destination_warehouse_id) {
            $errors[] = 'Destination floor does not belong to the selected destination warehouse.';
        }

        if ($source_floor_id !== '' && $destination_floor_id !== '' && $source_floor_id === $destination_floor_id) {
            $errors[] = 'Source floor and destination floor must be different.';
        }
    }

    if (empty($errors)) {
        if ($movement_action === 'REMOVE') {
            $exclude_recids = array();
            if (!empty($edit_row)) {
                $exclude_recids[] = $edit_row['recid'];
            }

            $available_stock = stock_as_of($link, $source_floor_id, $form_state['itmcde'], $movement_date_db, $exclude_recids);
            if ($available_stock < $qty_value) {
                $errors[] = 'Not enough stock on the selected floor. Available stock: ' . qty_display($available_stock);
            }
        }

        if ($movement_action === 'TRANSFER') {
            $exclude_recids = array();
            if (!empty($edit_row)) {
                $exclude_recids[] = $edit_row['recid'];
            }
            if (!empty($edit_transfer_pair)) {
                $exclude_recids[] = $edit_transfer_pair['recid'];
            }

            $available_stock = stock_as_of($link, $source_floor_id, $form_state['itmcde'], $movement_date_db, $exclude_recids);
            if ($available_stock < $qty_value) {
                $errors[] = 'Not enough stock on the selected source floor. Available stock: ' . qty_display($available_stock);
            }
        }
    }

    if (empty($errors)) {
        try {
            $link->beginTransaction();

            if ($_POST['form_action'] === 'create') {
                if ($movement_action === 'ADD') {
                    $movement_id = next_movement_id($link);
                    $stmt = $link->prepare(
                        "INSERT INTO warehouse_stock_movement
                         (movement_id, movement_date, floor_id, itmcde, qty, stkqty, movement_type, related_floor_id, warehouse_staff_id, usercode, remarks)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute(array(
                        $movement_id,
                        $movement_date_db,
                        $source_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value,
                        'ADJUST_IN',
                        null,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks
                    ));

                    $success_message = 'Transaction saved: ' . $movement_id;
                } elseif ($movement_action === 'REMOVE') {
                    $movement_id = next_movement_id($link);
                    $stmt = $link->prepare(
                        "INSERT INTO warehouse_stock_movement
                         (movement_id, movement_date, floor_id, itmcde, qty, stkqty, movement_type, related_floor_id, warehouse_staff_id, usercode, remarks)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute(array(
                        $movement_id,
                        $movement_date_db,
                        $source_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value * -1,
                        'ADJUST_OUT',
                        null,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks
                    ));

                    $success_message = 'Transaction saved: ' . $movement_id;
                } else {
                    $movement_out = next_movement_id($link);
                    $movement_in = LNexts($movement_out);
                    $stmt = $link->prepare(
                        "INSERT INTO warehouse_stock_movement
                         (movement_id, movement_date, floor_id, itmcde, qty, stkqty, movement_type, related_floor_id, warehouse_staff_id, usercode, remarks)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute(array(
                        $movement_out,
                        $movement_date_db,
                        $source_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value * -1,
                        'TRANSFER_OUT',
                        $destination_floor_id,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks
                    ));

                    $stmt->execute(array(
                        $movement_in,
                        $movement_date_db,
                        $destination_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value,
                        'TRANSFER_IN',
                        $source_floor_id,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks
                    ));

                    $success_message = 'Transfer saved: ' . $movement_out . ' / ' . $movement_in;
                }
            } else {
                if (!$edit_row) {
                    throw new Exception('Transaction not found.');
                }

                if ($edit_row['movement_type'] === 'ADJUST_IN') {
                    $stmt = $link->prepare(
                        "UPDATE warehouse_stock_movement
                         SET movement_date = ?, itmcde = ?, qty = ?, stkqty = ?, warehouse_staff_id = ?, usercode = ?, remarks = ?
                         WHERE recid = ?"
                    );
                    $stmt->execute(array(
                        $movement_date_db,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks,
                        $edit_row['recid']
                    ));
                    $success_message = 'Transaction updated: ' . $edit_row['movement_id'];
                } elseif ($edit_row['movement_type'] === 'ADJUST_OUT') {
                    $stmt = $link->prepare(
                        "UPDATE warehouse_stock_movement
                         SET movement_date = ?, itmcde = ?, qty = ?, stkqty = ?, warehouse_staff_id = ?, usercode = ?, remarks = ?
                         WHERE recid = ?"
                    );
                    $stmt->execute(array(
                        $movement_date_db,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value * -1,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks,
                        $edit_row['recid']
                    ));
                    $success_message = 'Transaction updated: ' . $edit_row['movement_id'];
                } elseif (!empty($edit_transfer_pair)) {
                    $transfer_out = ($edit_row['movement_type'] === 'TRANSFER_OUT') ? $edit_row : $edit_transfer_pair;
                    $transfer_in = ($edit_row['movement_type'] === 'TRANSFER_IN') ? $edit_row : $edit_transfer_pair;

                    $stmt = $link->prepare(
                        "UPDATE warehouse_stock_movement
                         SET movement_date = ?, floor_id = ?, itmcde = ?, qty = ?, stkqty = ?, movement_type = ?, related_floor_id = ?,
                             warehouse_staff_id = ?, usercode = ?, remarks = ?
                         WHERE recid = ?"
                    );

                    $stmt->execute(array(
                        $movement_date_db,
                        $source_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value * -1,
                        'TRANSFER_OUT',
                        $destination_floor_id,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks,
                        $transfer_out['recid']
                    ));

                    $stmt->execute(array(
                        $movement_date_db,
                        $destination_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $qty_value,
                        'TRANSFER_IN',
                        $source_floor_id,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks,
                        $transfer_in['recid']
                    ));

                    $success_message = 'Transfer updated: ' . $transfer_out['movement_id'] . ' / ' . $transfer_in['movement_id'];
                } else {
                    $stmt = $link->prepare(
                        "UPDATE warehouse_stock_movement
                         SET movement_date = ?, floor_id = ?, itmcde = ?, qty = ?, stkqty = ?, related_floor_id = ?,
                             warehouse_staff_id = ?, usercode = ?, remarks = ?
                         WHERE recid = ?"
                    );

                    $update_floor_id = ($edit_row['movement_type'] === 'TRANSFER_OUT') ? $source_floor_id : $destination_floor_id;
                    $update_related_floor_id = ($edit_row['movement_type'] === 'TRANSFER_OUT') ? $destination_floor_id : $source_floor_id;
                    $update_stkqty = ($edit_row['movement_type'] === 'TRANSFER_OUT') ? ($qty_value * -1) : $qty_value;

                    $stmt->execute(array(
                        $movement_date_db,
                        $update_floor_id,
                        $form_state['itmcde'],
                        $qty_value,
                        $update_stkqty,
                        $update_related_floor_id,
                        $warehouse_staff_id,
                        $usercode,
                        $remarks,
                        $edit_row['recid']
                    ));

                    $success_message = 'Transfer updated: ' . $edit_row['movement_id'];
                    $page_warning = 'Matching transfer pair was not found, so only one row was updated.';
                }
            }

            $link->commit();

            $_SESSION['warehouse_transaction_flash'] = array(
                'type' => ($page_warning !== '') ? 'warning' : 'success',
                'message' => $success_message . (($page_warning !== '') ? ' ' . $page_warning : '')
            );

            ob_end_clean();
            header("Location: mf_warehouse_transaction.php");
            exit;
        } catch (Exception $e) {
            if ($link->inTransaction()) {
                $link->rollBack();
            }

            $errors[] = 'Unable to save the transaction. ' . $e->getMessage();
        }
    }
}

if (isset($_POST['form_action']) && $_POST['form_action'] === 'delete') {
    if ((int)$delete_crud !== 1) {
        $errors[] = 'You do not have permission to delete transactions.';
    } else {
        $delete_recid = isset($_POST['recid']) ? trim((string)$_POST['recid']) : '';
        $delete_row = $has_context ? load_movement_row($link, $delete_recid, $warehouse_id, $floor_id) : load_movement_row_any($link, $delete_recid);

        if (!$delete_row) {
            $errors[] = 'The selected transaction could not be found.';
        } else {
            try {
                $link->beginTransaction();
                $delete_ids = array($delete_row['movement_id']);
                $stmt_delete = $link->prepare("DELETE FROM warehouse_stock_movement WHERE recid = ?");

                if ($delete_row['movement_type'] === 'TRANSFER_OUT' || $delete_row['movement_type'] === 'TRANSFER_IN') {
                    $pair_row = find_transfer_pair($link, $delete_row);
                    $stmt_delete->execute(array($delete_row['recid']));

                    if (!empty($pair_row)) {
                        $stmt_delete->execute(array($pair_row['recid']));
                        $delete_ids[] = $pair_row['movement_id'];
                    }
                } else {
                    $stmt_delete->execute(array($delete_row['recid']));
                }

                $link->commit();
                $_SESSION['warehouse_transaction_flash'] = array(
                    'type' => 'success',
                    'message' => 'Deleted transaction(s): ' . implode(', ', $delete_ids)
                );

                ob_end_clean();
                header("Location: mf_warehouse_transaction.php");
                exit;
            } catch (Exception $e) {
                if ($link->inTransaction()) {
                    $link->rollBack();
                }

                $errors[] = 'Unable to delete the transaction. ' . $e->getMessage();
            }
        }
    }
}

$search_filters = array(
    'movement_date_from' => isset($_GET['search_date_from']) ? trim((string)$_GET['search_date_from']) : '',
    'movement_date_to' => isset($_GET['search_date_to']) ? trim((string)$_GET['search_date_to']) : '',
    'warehouse_id' => isset($_GET['search_warehouse_id']) ? trim((string)$_GET['search_warehouse_id']) : '',
    'floor_id' => isset($_GET['search_floor_id']) ? trim((string)$_GET['search_floor_id']) : '',
    'item' => isset($_GET['search_item']) ? trim((string)$_GET['search_item']) : '',
    'qty' => isset($_GET['search_qty']) ? trim((string)$_GET['search_qty']) : '',
    'stkqty' => isset($_GET['search_stkqty']) ? trim((string)$_GET['search_stkqty']) : '',
    'movement_type' => isset($_GET['search_movement_type']) ? trim((string)$_GET['search_movement_type']) : '',
    'warehouse_staff_id' => isset($_GET['search_warehouse_staff_id']) ? trim((string)$_GET['search_warehouse_staff_id']) : '',
    'usercode' => isset($_GET['search_usercode']) ? trim((string)$_GET['search_usercode']) : '',
    'remarks' => isset($_GET['search_remarks']) ? trim((string)$_GET['search_remarks']) : '',
    'sort1_field' => isset($_GET['sortby_1_field']) ? trim((string)$_GET['sortby_1_field']) : 'movement_date',
    'sort1_order' => isset($_GET['sortby_1_order']) ? trim((string)$_GET['sortby_1_order']) : 'Desc',
    'sort2_field' => isset($_GET['sortby_2_field']) ? trim((string)$_GET['sortby_2_field']) : 'none',
    'sort2_order' => isset($_GET['sortby_2_order']) ? trim((string)$_GET['sortby_2_order']) : 'Desc'
);

if ($search_filters['warehouse_id'] !== '' && !isset($warehouse_by_id[$search_filters['warehouse_id']])) {
    $search_filters['warehouse_id'] = '';
}

if ($search_filters['floor_id'] !== '' && !isset($floor_map[$search_filters['floor_id']])) {
    $search_filters['floor_id'] = '';
}

if ($search_filters['warehouse_staff_id'] !== '' && !isset($staff_by_id[$search_filters['warehouse_staff_id']])) {
    $search_filters['warehouse_staff_id'] = '';
}

if ($search_filters['movement_type'] !== '' && !in_array($search_filters['movement_type'], array('ADJUST_IN', 'ADJUST_OUT', 'TRANSFER_IN', 'TRANSFER_OUT'), true)) {
    $search_filters['movement_type'] = '';
}

$search_active = false;
foreach ($search_filters as $key => $value) {
    if (strpos($key, 'sort') === 0) {
        continue;
    }

    if ($value !== '') {
        $search_active = true;
        break;
    }
}

$transactions = array();
if (!$is_entry_page) {
    $where_parts = array();
    $params = array();

    if ($search_filters['movement_date_from'] !== '') {
        $where_parts[] = "DATE(wsm.movement_date) >= ?";
        $params[] = $search_filters['movement_date_from'];
    }

    if ($search_filters['movement_date_to'] !== '') {
        $where_parts[] = "DATE(wsm.movement_date) <= ?";
        $params[] = $search_filters['movement_date_to'];
    }

    if ($search_filters['warehouse_id'] !== '') {
        $where_parts[] = "(src.warehouse_id = ? OR rel.warehouse_id = ?)";
        $params[] = $search_filters['warehouse_id'];
        $params[] = $search_filters['warehouse_id'];
    }

    if ($search_filters['floor_id'] !== '') {
        $where_parts[] = "(wsm.floor_id = ? OR wsm.related_floor_id = ?)";
        $params[] = $search_filters['floor_id'];
        $params[] = $search_filters['floor_id'];
    }

    if ($search_filters['item'] !== '') {
        $where_parts[] = "(wsm.itmcde LIKE ? OR itemfile.itmdsc LIKE ?)";
        $params[] = '%' . $search_filters['item'] . '%';
        $params[] = '%' . $search_filters['item'] . '%';
    }

    if ($search_filters['qty'] !== '' && is_numeric($search_filters['qty'])) {
        $where_parts[] = "wsm.qty = ?";
        $params[] = (float)$search_filters['qty'];
    }

    if ($search_filters['stkqty'] !== '' && is_numeric($search_filters['stkqty'])) {
        $where_parts[] = "wsm.stkqty = ?";
        $params[] = (float)$search_filters['stkqty'];
    }

    if ($search_filters['movement_type'] !== '') {
        $where_parts[] = "wsm.movement_type = ?";
        $params[] = $search_filters['movement_type'];
    }

    if ($search_filters['warehouse_staff_id'] !== '') {
        $where_parts[] = "wsm.warehouse_staff_id = ?";
        $params[] = $search_filters['warehouse_staff_id'];
    }

    if ($search_filters['usercode'] !== '') {
        $where_parts[] = "COALESCE(wsm.usercode, '') LIKE ?";
        $params[] = '%' . $search_filters['usercode'] . '%';
    }

    if ($search_filters['remarks'] !== '') {
        $where_parts[] = "COALESCE(wsm.remarks, '') LIKE ?";
        $params[] = '%' . $search_filters['remarks'] . '%';
    }

    $sort_map = array(
        'movement_date' => 'wsm.movement_date',
        'movement_type' => 'wsm.movement_type',
        'item' => 'itemfile.itmdsc',
        'qty' => 'wsm.qty',
        'stkqty' => 'wsm.stkqty',
        'usercode' => 'wsm.usercode',
        'remarks' => 'wsm.remarks',
        'staff' => 'staff.lname',
        'floor' => 'src.floor_name'
    );
    $sort_order_1 = strtoupper($search_filters['sort1_order']) === 'ASC' ? 'ASC' : 'DESC';
    $sort_order_2 = strtoupper($search_filters['sort2_order']) === 'ASC' ? 'ASC' : 'DESC';
    $order_by = array();

    if (isset($sort_map[$search_filters['sort1_field']])) {
        $order_by[] = $sort_map[$search_filters['sort1_field']] . ' ' . $sort_order_1;
    }

    if ($search_filters['sort2_field'] !== 'none' && isset($sort_map[$search_filters['sort2_field']])) {
        $order_by[] = $sort_map[$search_filters['sort2_field']] . ' ' . $sort_order_2;
    }

    $order_by[] = 'wsm.movement_date DESC';
    $order_by[] = 'wsm.recid DESC';

    $where_sql = !empty($where_parts) ? ' WHERE ' . implode(' AND ', $where_parts) : '';

    $sql = "SELECT wsm.*,
                   itemfile.itmdsc,
                   src.floor_name,
                   src.floor_no,
                   src.warehouse_id AS source_warehouse_id,
                   src_wh.warehouse_name AS source_warehouse_name,
                   rel.floor_name AS related_floor_name,
                   rel.floor_no AS related_floor_no,
                   rel.warehouse_id AS related_warehouse_id,
                   rel_wh.warehouse_name AS related_warehouse_name,
                   staff.fname,
                   staff.lname,
                   usr.userdesc AS transaction_userdesc
            FROM warehouse_stock_movement wsm
            INNER JOIN warehouse_floor src
                ON src.warehouse_floor_id = wsm.floor_id
            INNER JOIN warehouse src_wh
                ON src_wh.warehouse_id = src.warehouse_id
            LEFT JOIN warehouse_floor rel
                ON rel.warehouse_floor_id = wsm.related_floor_id
            LEFT JOIN warehouse rel_wh
                ON rel_wh.warehouse_id = rel.warehouse_id
            LEFT JOIN itemfile
                ON itemfile.itmcde = wsm.itmcde
            LEFT JOIN warehouse_staff staff
                ON staff.warehouse_staff_id = wsm.warehouse_staff_id
            LEFT JOIN users usr
                ON usr.usercode = wsm.usercode"
        . $where_sql .
        " ORDER BY " . implode(', ', array_unique($order_by)) . "
          LIMIT 150";
    $stmt_transactions = $link->prepare($sql);
    $stmt_transactions->execute($params);
    $seen_transfer_pairs = array();
    while ($row = $stmt_transactions->fetch(PDO::FETCH_ASSOC)) {
        $pair_row = false;

        if ($row['movement_type'] === 'TRANSFER_OUT' || $row['movement_type'] === 'TRANSFER_IN') {
            $pair_row = find_transfer_pair($link, $row);
            if (!empty($pair_row)) {
                $pair_key = ((int)$row['recid'] < (int)$pair_row['recid'])
                    ? ((int)$row['recid'] . ':' . (int)$pair_row['recid'])
                    : ((int)$pair_row['recid'] . ':' . (int)$row['recid']);

                if (isset($seen_transfer_pairs[$pair_key])) {
                    continue;
                }

                $seen_transfer_pairs[$pair_key] = true;
            }
        }

        $transactions[] = build_transaction_display_row($row, $pair_row);
    }
}

$source_floors = ($warehouse_id !== '' && isset($floors_by_warehouse[$warehouse_id])) ? $floors_by_warehouse[$warehouse_id] : array();
$destination_warehouse_id_form = isset($_POST['destination_warehouse_id']) ? trim((string)$_POST['destination_warehouse_id']) : warehouse_id_from_floor($floor_map, $form_state['destination_floor_id']);
$destination_floors = ($destination_warehouse_id_form !== '' && isset($floors_by_warehouse[$destination_warehouse_id_form])) ? $floors_by_warehouse[$destination_warehouse_id_form] : array();
$show_form = $is_entry_page;
$form_submission_token = '';

if ($show_form) {
    $_SESSION['warehouse_transaction_form_token'] = bin2hex(random_bytes(32));
    $form_submission_token = $_SESSION['warehouse_transaction_form_token'];
}
?>

<style>
    #td_br {
        vertical-align: top;
    }

    .context-card,
    .form-card,
    .list-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .selector-note {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .toolbar-subtitle {
        color: #64748b;
        font-size: 0.95rem;
    }

    .main_br_div {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }

    .transaction-summary-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.1rem;
        background: #ffffff;
        height: 100%;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
    }

    .transaction-summary-card .badge {
        letter-spacing: 0.02em;
    }

    .transaction-id {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }

    .transaction-item {
        color: #1e293b;
        font-weight: 600;
    }

    .transaction-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .transaction-meta-block {
        border-radius: 12px;
        background: #f8fafc;
        padding: 0.7rem 0.85rem;
    }

    .transaction-meta-label {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.2rem;
    }

    .transaction-meta-value {
        color: #0f172a;
        font-weight: 600;
        word-break: break-word;
    }

    .transaction-remarks {
        min-height: 3rem;
        color: #475569;
        margin-top: 1rem;
        white-space: pre-wrap;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: #f8fafc;
        padding: 2rem 1.25rem;
        text-align: center;
        color: #64748b;
    }

    .search-trigger {
        min-width: 48px;
    }

    .stock-indicator {
        font-size: 0.82rem;
        color: #9ca3af;
        padding-top: 0.35rem;
    }

    .top-form-section {
        margin-bottom: 1rem;
    }

    .view-detail {
        border-bottom: 1px solid #e2e8f0;
        padding: 0.8rem 0;
    }

    .view-detail:first-child {
        padding-top: 0;
    }

    .view-detail:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .view-detail-label {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.2rem;
    }

    .view-detail-value {
        color: #0f172a;
        font-weight: 600;
        word-break: break-word;
        white-space: pre-wrap;
    }

    @media (max-width: 767.98px) {
        .main_br_div {
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
        }

        .transaction-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<table class="big_table">
    <tr colspan="1">
        <td colspan="1" class="td_bl">
            <?php include 'includes/main_menu.php'; ?>
        </td>

        <td colspan="1" class="td_br" id="td_br">
            <div class="container-fluid mt-2 main_br_div">
                <?php if ($is_entry_page): ?>
                    <div class="mb-3">
                        <h4 class="mb-0"><?php echo ($mode === 'edit') ? 'Edit Stock Movement' : 'Create Stock Movement'; ?></h4>
                        <div class="toolbar-subtitle">
                            Select the transaction type, source warehouse, and floor first, then complete the stock movement details.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flash)): ?>
                    <div class="alert alert-<?php echo ($flash['type'] === 'warning') ? 'warning' : 'success'; ?>">
                        <?php echo h($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo h($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($errors) && $page_warning !== ''): ?>
                    <div class="alert alert-warning">
                        <?php echo h($page_warning); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$is_entry_page): ?>
                    <div class="list-card p-3 p-md-4 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-1">Latest Transactions</h5>
                                <div class="small text-muted">
                                    Showing the latest warehouse stock movements across all warehouses and floors.
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ((int)$add_crud === 1): ?>
                                    <a href="mf_warehouse_transaction_entry.php" class="btn btn-success">Create Stock Movement</a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#transactionSearchModal">
                                    <i class="fas fa-search"></i>
                                    <span>Search</span>
                                </button>
                            </div>
                        </div>

                        <?php if ($search_active): ?>
                            <div class="alert alert-light border mt-3 mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <span>Filters applied to the latest transactions list.</span>
                                <a href="mf_warehouse_transaction.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <?php if (empty($transactions)): ?>
                                <div class="empty-state">
                                    No transactions matched the selected filters.
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($transactions as $transaction): ?>
                                        <?php
                                            $source_label = trim($transaction['display_source_warehouse_name'] . ' / ' . floor_label(array(
                                                'floor_name' => $transaction['display_source_floor_name'],
                                                'floor_no' => $transaction['display_source_floor_no']
                                            )), ' /');
                                            $related_label = '';
                                            if (!empty($transaction['display_related_floor_name']) || !empty($transaction['display_related_floor_no']) || !empty($transaction['display_related_warehouse_name'])) {
                                                $related_label = trim($transaction['display_related_warehouse_name'] . ' / ' . floor_label(array(
                                                    'floor_name' => $transaction['display_related_floor_name'],
                                                    'floor_no' => $transaction['display_related_floor_no']
                                                )), ' /');
                                            }
                                            $staff_label = trim((string)$transaction['fname'] . ' ' . (string)$transaction['lname']);
                                            $user_label = trim((string)($transaction['transaction_userdesc'] ?? ''));
                                            $view_payload = array(
                                                'movement_id' => $transaction['display_movement_id'],
                                                'movement_date' => display_movement_datetime($transaction['movement_date']),
                                                'movement_type' => $transaction['display_movement_type'],
                                                'item_label' => $transaction['itmdsc'],
                                                'qty' => qty_display($transaction['qty']),
                                                'stkqty' => $transaction['display_stkqty_label'],
                                                'source_floor' => $source_label,
                                                'related_floor' => $related_label,
                                                'staff' => $staff_label,
                                                'usercode' => $user_label,
                                                'remarks' => (string)$transaction['remarks']
                                            );
                                        ?>
                                        <div class="col-12">
                                            <div class="transaction-summary-card">
                                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                                    <div class="small text-muted"><?php echo h(display_movement_datetime($transaction['movement_date'])); ?></div>
                                                    <span class="badge bg-secondary"><?php echo h($transaction['display_movement_type']); ?></span>
                                                </div>
                                                <div class="transaction-id"><?php echo h($transaction['display_movement_id']); ?></div>
                                                <div class="transaction-item mt-2"><?php echo h($transaction['itmdsc']); ?></div>

                                                <div class="transaction-meta">
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">Warehouse Floor</span>
                                                        <span class="transaction-meta-value"><?php echo h($source_label !== '' ? $source_label : '-'); ?></span>
                                                    </div>
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">Related Floor</span>
                                                        <span class="transaction-meta-value"><?php echo h($related_label !== '' ? $related_label : '-'); ?></span>
                                                    </div>
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">Quantity</span>
                                                        <span class="transaction-meta-value"><?php echo h(qty_display($transaction['qty'])); ?></span>
                                                    </div>
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">Stock Effect</span>
                                                        <span class="transaction-meta-value <?php echo h($transaction['display_stkqty_class']); ?>"><?php echo h($transaction['display_stkqty_label']); ?></span>
                                                    </div>
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">Warehouse Staff</span>
                                                        <span class="transaction-meta-value"><?php echo h($staff_label !== '' ? $staff_label : '-'); ?></span>
                                                    </div>
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">User</span>
                                                        <span class="transaction-meta-value"><?php echo h($user_label !== '' ? $user_label : '-'); ?></span>
                                                    </div>
                                                    <div class="transaction-meta-block">
                                                        <span class="transaction-meta-label">Remarks</span>
                                                        <span class="transaction-meta-value"><?php echo h(trim((string)$transaction['remarks']) !== '' ? $transaction['remarks'] : '-'); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-wrap gap-2 mt-3">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-sm"
                                                        data-transaction="<?php echo h(json_encode($view_payload)); ?>"
                                                        onclick="openTransactionView(JSON.parse(this.dataset.transaction))">
                                                        View
                                                    </button>
                                                    <form method="post" onsubmit="return confirm('Delete this transaction? Transfer rows will delete the matching pair when found.');">
                                                        <input type="hidden" name="form_action" value="delete">
                                                        <input type="hidden" name="recid" value="<?php echo h($transaction['display_recid']); ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" <?php echo ((int)$delete_crud !== 1) ? 'disabled' : ''; ?>>Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($show_form): ?>
                    <form method="post" id="movement_form">
                        <input type="hidden" name="form_action" value="<?php echo ($mode === 'edit') ? 'update' : 'create'; ?>">
                        <input type="hidden" name="submission_token" value="<?php echo h($form_submission_token); ?>">
                        <?php if ($mode === 'edit' && !empty($edit_row)): ?>
                            <input type="hidden" name="recid" value="<?php echo h($edit_row['recid']); ?>">
                        <?php endif; ?>

                        <div class="context-card p-3 p-md-4 top-form-section">
                            <div class="mb-3">
                                <h5 class="mb-0"><?php echo ($mode === 'edit') ? 'Edit Transaction' : 'Create Stock Movement'; ?></h5>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold">Transaction Type</label>
                                    <?php if ($mode === 'edit'): ?>
                                        <input type="hidden" id="movement_action" name="movement_action" value="<?php echo h($form_state['movement_action']); ?>">
                                        <div class="form-control bg-light"><?php echo h($form_state['movement_action']); ?></div>
                                    <?php else: ?>
                                        <select class="form-select" id="movement_action" name="movement_action" required onchange="toggleTransferRowVisibility(this.value);">
                                            <option value="ADD" <?php echo ($form_state['movement_action'] === 'ADD') ? 'selected' : ''; ?>>ADD STOCK</option>
                                            <option value="REMOVE" <?php echo ($form_state['movement_action'] === 'REMOVE') ? 'selected' : ''; ?>>REMOVE STOCK</option>
                                            <option value="TRANSFER" <?php echo ($form_state['movement_action'] === 'TRANSFER') ? 'selected' : ''; ?>>TRANSFER STOCK</option>
                                        </select>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="warehouse_id">Source Warehouse</label>
                                    <select class="form-select" id="warehouse_id" name="warehouse_id" required onchange="handleSourceWarehouseChange(this.value);">
                                        <option value="">Select Source Warehouse</option>
                                        <?php foreach ($warehouse_options as $warehouse_option): ?>
                                            <option value="<?php echo h($warehouse_option['warehouse_id']); ?>" <?php echo ($warehouse_id === $warehouse_option['warehouse_id']) ? 'selected' : ''; ?>>
                                                <?php echo h($warehouse_option['warehouse_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="floor_id">Source Floor</label>
                                    <select class="form-select" id="floor_id" name="source_floor_id" required>
                                        <option value="">Select Source Floor</option>
                                        <?php foreach ($source_floors as $floor_option): ?>
                                            <option value="<?php echo h($floor_option['warehouse_floor_id']); ?>" <?php echo ($floor_id === $floor_option['warehouse_floor_id']) ? 'selected' : ''; ?>>
                                                <?php echo h(floor_dropdown_label($floor_option)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-1" id="transfer_row_wrap" style="<?php echo ($form_state['movement_action'] === 'TRANSFER') ? '' : 'display:none;'; ?>">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold invisible d-block" aria-hidden="true">Transaction Type</label>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="destination_warehouse_id">Destination Warehouse</label>
                                    <select class="form-select" id="destination_warehouse_id" name="destination_warehouse_id" onchange="handleDestinationWarehouseChange(this.value);">
                                        <option value="">Select Destination Warehouse</option>
                                        <?php foreach ($warehouse_options as $warehouse_option): ?>
                                            <option value="<?php echo h($warehouse_option['warehouse_id']); ?>" <?php echo ($destination_warehouse_id_form === $warehouse_option['warehouse_id']) ? 'selected' : ''; ?>>
                                                <?php echo h($warehouse_option['warehouse_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="destination_floor_id">Destination Floor</label>
                                    <select class="form-select" id="destination_floor_id" name="destination_floor_id">
                                        <option value="">Select Destination Floor</option>
                                        <?php foreach ($destination_floors as $floor_option): ?>
                                            <option value="<?php echo h($floor_option['warehouse_floor_id']); ?>" <?php echo ($form_state['destination_floor_id'] === $floor_option['warehouse_floor_id']) ? 'selected' : ''; ?>>
                                                <?php echo h(floor_dropdown_label($floor_option)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-card p-3 p-md-4 mb-3">
                            <div class="row g-3">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="movement_date">Movement Date</label>
                                    <input type="datetime-local" class="form-control" id="movement_date" name="movement_date" value="<?php echo h($form_state['movement_date']); ?>" required>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="open_item_search">Item</label>
                                    <select class="form-select" id="open_item_search" name="itmcde" autocomplete="off" style="width:100%" required>
                                        <option value="">-- Select Item --</option>
                                        <?php foreach ($item_by_id as $item_row): ?>
                                            <option value="<?php echo h($item_row['itmcde']); ?>" <?php echo ($form_state['itmcde'] === $item_row['itmcde']) ? 'selected' : ''; ?>>
                                                <?php echo h($item_row['itmdsc']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" for="qty">Quantity</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="qty" name="qty" value="<?php echo h($form_state['qty']); ?>" required onkeypress="return invalidChars(event);">
                                    <div class="stock-indicator" id="transfer_stock_wrap" style="display:none;">
                                        <div id="transfer_stock_box">Current Available Stock: -</div>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label fw-bold" for="warehouse_staff_id">Warehouse Staff</label>
                                    <select class="form-select" id="warehouse_staff_id" name="warehouse_staff_id" required>
                                        <option value="">Select Staff</option>
                                        <?php foreach ($staff_by_id as $staff_id => $staff_row): ?>
                                            <?php $staff_label = trim($staff_row['fname'] . ' ' . $staff_row['lname']) . ((trim((string)$staff_row['role']) !== '') ? ' - ' . $staff_row['role'] : ''); ?>
                                            <option value="<?php echo h($staff_id); ?>" <?php echo ($form_state['warehouse_staff_id'] === $staff_id) ? 'selected' : ''; ?>>
                                                <?php echo h($staff_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold" for="remarks">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo h($form_state['remarks']); ?></textarea>
                                </div>

                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-success" id="save_transaction_btn">
                                        <?php echo ($mode === 'edit') ? 'Update Transaction' : 'Save Transaction'; ?>
                                    </button>
                                    <a href="mf_warehouse_transaction.php" class="btn btn-danger">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </td>
    </tr>
</table>

<div class="modal fade" id="transactionSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="get">
                <div class="modal-header">
                    <h5 class="modal-title">Search Transactions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row m-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_date_from">From Date</label>
                            <input type="date" class="form-control" id="search_date_from" name="search_date_from" value="<?php echo h($search_filters['movement_date_from']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_date_to">To Date</label>
                            <input type="date" class="form-control" id="search_date_to" name="search_date_to" value="<?php echo h($search_filters['movement_date_to']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_movement_type">Movement Type</label>
                            <select class="form-select" id="search_movement_type" name="search_movement_type">
                                <option value="">All</option>
                                <option value="ADJUST_IN" <?php echo ($search_filters['movement_type'] === 'ADJUST_IN') ? 'selected' : ''; ?>>ADJUST_IN</option>
                                <option value="ADJUST_OUT" <?php echo ($search_filters['movement_type'] === 'ADJUST_OUT') ? 'selected' : ''; ?>>ADJUST_OUT</option>
                                <option value="TRANSFER_IN" <?php echo ($search_filters['movement_type'] === 'TRANSFER_IN') ? 'selected' : ''; ?>>TRANSFER_IN</option>
                                <option value="TRANSFER_OUT" <?php echo ($search_filters['movement_type'] === 'TRANSFER_OUT') ? 'selected' : ''; ?>>TRANSFER_OUT</option>
                            </select>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_warehouse_id">Warehouse</label>
                            <select class="form-select" id="search_warehouse_id" name="search_warehouse_id">
                                <option value="">All Warehouses</option>
                                <?php foreach ($warehouse_options as $warehouse_option): ?>
                                    <option value="<?php echo h($warehouse_option['warehouse_id']); ?>" <?php echo ($search_filters['warehouse_id'] === $warehouse_option['warehouse_id']) ? 'selected' : ''; ?>>
                                        <?php echo h($warehouse_option['warehouse_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_floor_id">Warehouse Floor</label>
                            <select class="form-select" id="search_floor_id" name="search_floor_id">
                                <option value="">All Floors</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_warehouse_staff_id">Warehouse Staff</label>
                            <select class="form-select" id="search_warehouse_staff_id" name="search_warehouse_staff_id">
                                <option value="">All Staff</option>
                                <?php foreach ($staff_by_id as $staff_id => $staff_row): ?>
                                    <?php $staff_filter_label = trim($staff_row['fname'] . ' ' . $staff_row['lname']) . ((trim((string)$staff_row['role']) !== '') ? ' - ' . $staff_row['role'] : ''); ?>
                                    <option value="<?php echo h($staff_id); ?>" <?php echo ($search_filters['warehouse_staff_id'] === (string)$staff_id) ? 'selected' : ''; ?>>
                                        <?php echo h($staff_filter_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_item">Item</label>
                            <input type="text" class="form-control" id="search_item" name="search_item" value="<?php echo h($search_filters['item']); ?>" autocomplete="off" placeholder="Item code or description">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_qty">Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="search_qty" name="search_qty" value="<?php echo h($search_filters['qty']); ?>" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_stkqty">Stock Effect</label>
                            <input type="number" step="0.01" class="form-control" id="search_stkqty" name="search_stkqty" value="<?php echo h($search_filters['stkqty']); ?>" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="search_usercode">User Code</label>
                            <input type="text" class="form-control" id="search_usercode" name="search_usercode" value="<?php echo h($search_filters['usercode']); ?>" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="search_remarks">Remarks</label>
                            <input type="text" class="form-control" id="search_remarks" name="search_remarks" value="<?php echo h($search_filters['remarks']); ?>" autocomplete="off">
                        </div>
                    </div>

                    <div class="row m-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Sort By</label>
                            <select name="sortby_1_order" id="sortby_1_order" class="form-select">
                                <option value="Asc" <?php echo ($search_filters['sort1_order'] === 'Asc') ? 'selected' : ''; ?>>Ascending</option>
                                <option value="Desc" <?php echo ($search_filters['sort1_order'] === 'Desc') ? 'selected' : ''; ?>>Descending</option>
                            </select>
                            <select name="sortby_1_field" id="sortby_1_field" class="form-select mt-2">
                                <option value="movement_date" <?php echo ($search_filters['sort1_field'] === 'movement_date') ? 'selected' : ''; ?>>Movement Date</option>
                                <option value="movement_type" <?php echo ($search_filters['sort1_field'] === 'movement_type') ? 'selected' : ''; ?>>Movement Type</option>
                                <option value="item" <?php echo ($search_filters['sort1_field'] === 'item') ? 'selected' : ''; ?>>Item</option>
                                <option value="floor" <?php echo ($search_filters['sort1_field'] === 'floor') ? 'selected' : ''; ?>>Warehouse Floor</option>
                                <option value="qty" <?php echo ($search_filters['sort1_field'] === 'qty') ? 'selected' : ''; ?>>Quantity</option>
                                <option value="stkqty" <?php echo ($search_filters['sort1_field'] === 'stkqty') ? 'selected' : ''; ?>>Stock Effect</option>
                                <option value="staff" <?php echo ($search_filters['sort1_field'] === 'staff') ? 'selected' : ''; ?>>Warehouse Staff</option>
                                <option value="usercode" <?php echo ($search_filters['sort1_field'] === 'usercode') ? 'selected' : ''; ?>>User Code</option>
                                <option value="remarks" <?php echo ($search_filters['sort1_field'] === 'remarks') ? 'selected' : ''; ?>>Remarks</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Sort By</label>
                            <select name="sortby_2_order" id="sortby_2_order" class="form-select">
                                <option value="Asc" <?php echo ($search_filters['sort2_order'] === 'Asc') ? 'selected' : ''; ?>>Ascending</option>
                                <option value="Desc" <?php echo ($search_filters['sort2_order'] === 'Desc') ? 'selected' : ''; ?>>Descending</option>
                            </select>
                            <select name="sortby_2_field" id="sortby_2_field" class="form-select mt-2">
                                <option value="none" <?php echo ($search_filters['sort2_field'] === 'none') ? 'selected' : ''; ?>>None</option>
                                <option value="movement_date" <?php echo ($search_filters['sort2_field'] === 'movement_date') ? 'selected' : ''; ?>>Movement Date</option>
                                <option value="movement_type" <?php echo ($search_filters['sort2_field'] === 'movement_type') ? 'selected' : ''; ?>>Movement Type</option>
                                <option value="item" <?php echo ($search_filters['sort2_field'] === 'item') ? 'selected' : ''; ?>>Item</option>
                                <option value="floor" <?php echo ($search_filters['sort2_field'] === 'floor') ? 'selected' : ''; ?>>Warehouse Floor</option>
                                <option value="qty" <?php echo ($search_filters['sort2_field'] === 'qty') ? 'selected' : ''; ?>>Quantity</option>
                                <option value="stkqty" <?php echo ($search_filters['sort2_field'] === 'stkqty') ? 'selected' : ''; ?>>Stock Effect</option>
                                <option value="staff" <?php echo ($search_filters['sort2_field'] === 'staff') ? 'selected' : ''; ?>>Warehouse Staff</option>
                                <option value="usercode" <?php echo ($search_filters['sort2_field'] === 'usercode') ? 'selected' : ''; ?>>User Code</option>
                                <option value="remarks" <?php echo ($search_filters['sort2_field'] === 'remarks') ? 'selected' : ''; ?>>Remarks</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="mf_warehouse_transaction.php" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transactionViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Movement ID</span>
                            <div class="view-detail-value" id="view_movement_id">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Movement Date</span>
                            <div class="view-detail-value" id="view_movement_date">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Movement Type</span>
                            <div class="view-detail-value" id="view_movement_type">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Item</span>
                            <div class="view-detail-value" id="view_item_label">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Quantity</span>
                            <div class="view-detail-value" id="view_qty">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Stock Effect</span>
                            <div class="view-detail-value" id="view_stkqty">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Warehouse Floor</span>
                            <div class="view-detail-value" id="view_source_floor">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Related Floor</span>
                            <div class="view-detail-value" id="view_related_floor">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">Warehouse Staff</span>
                            <div class="view-detail-value" id="view_staff">-</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="view-detail">
                            <span class="view-detail-label">User</span>
                            <div class="view-detail-value" id="view_usercode">-</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="view-detail">
                            <span class="view-detail-label">Remarks</span>
                            <div class="view-detail-value" id="view_remarks">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleTransferRowVisibility(action) {
        var transferRow = document.getElementById('transfer_row_wrap');
        if (!transferRow) {
            return;
        }

        transferRow.style.display = (action === 'TRANSFER') ? '' : 'none';
    }

    (function () {
        var floorsByWarehouse = <?php echo json_encode($floors_by_warehouse); ?>;
        var warehouseNames = <?php echo json_encode(array_map(function ($warehouse_row) { return $warehouse_row['warehouse_name']; }, $warehouse_by_id)); ?>;
        var sourceWarehouseId = <?php echo json_encode($warehouse_id); ?>;
        var sourceFloorId = <?php echo json_encode($floor_id); ?>;
        var destinationWarehouseId = <?php echo json_encode($destination_warehouse_id_form); ?>;
        var destinationFloorId = <?php echo json_encode($form_state['destination_floor_id']); ?>;
        var editRecid = <?php echo json_encode(!empty($edit_row) ? (string)$edit_row['recid'] : ''); ?>;
        var editPairRecid = <?php echo json_encode(!empty($edit_transfer_pair) ? (string)$edit_transfer_pair['recid'] : ''); ?>;
        var warehouseSelect = document.getElementById('warehouse_id');
        var floorSelect = document.getElementById('floor_id');
        var destinationWarehouseSelect = document.getElementById('destination_warehouse_id');
        var destinationFloorSelect = document.getElementById('destination_floor_id');
        var searchWarehouseSelect = document.getElementById('search_warehouse_id');
        var searchFloorSelect = document.getElementById('search_floor_id');
        var searchSelectedWarehouseId = <?php echo json_encode($search_filters['warehouse_id']); ?>;
        var searchSelectedFloorId = <?php echo json_encode($search_filters['floor_id']); ?>;
        var mode = <?php echo json_encode($mode); ?>;
        var movementForm = document.getElementById('movement_form');
        var movementAction = document.getElementById('movement_action');
        var movementDate = document.getElementById('movement_date');
        var itemSelect = document.getElementById('open_item_search');
        var quantityInput = document.getElementById('qty');
        var staffSelect = document.getElementById('warehouse_staff_id');
        var transferRowWrap = document.getElementById('transfer_row_wrap');
        var transferStockWrap = document.getElementById('transfer_stock_wrap');
        var transferStockBox = document.getElementById('transfer_stock_box');
        var submitButton = movementForm ? movementForm.querySelector('button[type="submit"]') : null;
        var transactionSearchModalEl = document.getElementById('transactionSearchModal');
        var transactionViewModalEl = document.getElementById('transactionViewModal');
        var transactionViewModal = null;
        var currentAvailableStock = null;
        var insufficientStockActive = false;

        function shouldShowStockPreview() {
            return movementAction && (movementAction.value === 'REMOVE' || movementAction.value === 'TRANSFER');
        }

        function setViewField(id, value) {
            var el = document.getElementById(id);
            if (el) {
                el.textContent = value && String(value).trim() !== '' ? value : '-';
            }
        }

        function getFloorsForWarehouse(warehouseId) {
            var key = String(warehouseId || '');
            if (key !== '' && Object.prototype.hasOwnProperty.call(floorsByWarehouse, key)) {
                return floorsByWarehouse[key];
            }

            var floorList = [];
            Object.keys(floorsByWarehouse).forEach(function (warehouseKey) {
                if (String(warehouseKey) === key) {
                    floorList = floorsByWarehouse[warehouseKey];
                }
            });

            return floorList;
        }

        function optionLabel(floor, includeWarehouseName) {
            var parts = [];

            if (includeWarehouseName && warehouseNames[floor.warehouse_id]) {
                parts.push(warehouseNames[floor.warehouse_id]);
            }
            if (floor.floor_name) {
                parts.push(String(floor.floor_name));
            }
            if (floor.floor_no) {
                parts.push(String(floor.floor_no));
            }

            return parts.join(' / ');
        }

        function populateFloorOptions(selectEl, floorList, floorIdToSelect, placeholderText) {
            if (!selectEl) {
                return;
            }

            selectEl.innerHTML = '<option value="">' + placeholderText + '</option>';

            if (!floorList || !floorList.length) {
                return;
            }

            floorList.forEach(function (floor) {
                var option = document.createElement('option');
                option.value = floor.warehouse_floor_id;
                option.textContent = optionLabel(floor, false);
                if (floorIdToSelect && floorIdToSelect === floor.warehouse_floor_id) {
                    option.selected = true;
                }
                selectEl.appendChild(option);
            });
        }

        function rebuildFloorOptions(selectEl, warehouseId, floorIdToSelect, placeholderText) {
            if (!warehouseId) {
                populateFloorOptions(selectEl, [], '', placeholderText);
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'mf_warehouse_transaction_ajax.php',
                dataType: 'json',
                data: {
                    event_action: 'load_warehouse_floors',
                    warehouse_id: warehouseId
                },
                success: function (response) {
                    var floorList = [];

                    if (response && response.status === 1 && Array.isArray(response.floors)) {
                        floorList = response.floors;
                        floorsByWarehouse[String(warehouseId)] = floorList;
                    } else {
                        floorList = getFloorsForWarehouse(warehouseId);
                    }

                    populateFloorOptions(selectEl, floorList, floorIdToSelect, placeholderText);
                },
                error: function () {
                    populateFloorOptions(selectEl, getFloorsForWarehouse(warehouseId), floorIdToSelect, placeholderText);
                }
            });
        }

        window.handleSourceWarehouseChange = function (warehouseId) {
            rebuildFloorOptions(floorSelect, warehouseId, '', 'Select Source Floor');

            if (floorSelect) {
                floorSelect.value = '';
            }

            if (movementAction && movementAction.value === 'TRANSFER' && destinationWarehouseSelect && !destinationWarehouseSelect.value) {
                destinationWarehouseSelect.value = warehouseId;
                rebuildFloorOptions(destinationFloorSelect, warehouseId, '', 'Select Destination Floor');
                if (destinationFloorSelect) {
                    destinationFloorSelect.value = '';
                }
            }

            fetchTransferStock();
            updateSaveButtonState();
        };

        window.handleDestinationWarehouseChange = function (warehouseId) {
            rebuildFloorOptions(destinationFloorSelect, warehouseId, '', 'Select Destination Floor');

            if (destinationFloorSelect) {
                destinationFloorSelect.value = '';
            }

            updateSaveButtonState();
        };

        function rebuildSearchFloorOptions(warehouseId, floorIdToSelect) {
            if (!searchFloorSelect) {
                return;
            }

            searchFloorSelect.innerHTML = '<option value="">All Floors</option>';

            if (warehouseId) {
                var filteredFloors = getFloorsForWarehouse(warehouseId);
                if (!filteredFloors.length) {
                    return;
                }

                filteredFloors.forEach(function (floor) {
                    var option = document.createElement('option');
                    option.value = floor.warehouse_floor_id;
                    option.textContent = optionLabel(floor, false);
                    if (floorIdToSelect && floorIdToSelect === floor.warehouse_floor_id) {
                        option.selected = true;
                    }
                    searchFloorSelect.appendChild(option);
                });
                return;
            }

            Object.keys(floorsByWarehouse).forEach(function (warehouseIdKey) {
                floorsByWarehouse[warehouseIdKey].forEach(function (floor) {
                    var option = document.createElement('option');
                    option.value = floor.warehouse_floor_id;
                    option.textContent = optionLabel(floor, true);
                    if (floorIdToSelect && floorIdToSelect === floor.warehouse_floor_id) {
                        option.selected = true;
                    }
                    searchFloorSelect.appendChild(option);
                });
            });
        }

        function validateRequiredFields() {
            if (!movementForm) {
                return true;
            }

            if (!movementDate || !movementDate.value) return false;
            if (!warehouseSelect || !warehouseSelect.value) return false;
            if (!floorSelect || !floorSelect.value) return false;
            if (!itemSelect || !itemSelect.value) return false;
            if (!staffSelect || !staffSelect.value) return false;
            if (!quantityInput || !quantityInput.value || parseFloat(quantityInput.value) <= 0) return false;

            if (movementAction && movementAction.value === 'TRANSFER') {
                if (!destinationWarehouseSelect || !destinationWarehouseSelect.value) return false;
                if (!destinationFloorSelect || !destinationFloorSelect.value) return false;
            }

            return true;
        }

        function updateQuantityLimit(availableStock) {
            currentAvailableStock = (typeof availableStock === 'number' && isFinite(availableStock)) ? availableStock : null;

            if (!quantityInput) {
                return;
            }

            if (shouldShowStockPreview() && typeof availableStock === 'number' && isFinite(availableStock) && availableStock >= 0) {
                quantityInput.setAttribute('max', String(availableStock));
                return;
            }

            quantityInput.removeAttribute('max');
        }

        function hasInsufficientStock() {
            if (!shouldShowStockPreview()) {
                return false;
            }

            if (!quantityInput || quantityInput.value === '') {
                return false;
            }

            if (typeof currentAvailableStock !== 'number' || !isFinite(currentAvailableStock)) {
                return false;
            }

            return parseFloat(quantityInput.value) > currentAvailableStock;
        }

        function syncInsufficientStockState(showAlert) {
            var insufficientStock = hasInsufficientStock();

            if (quantityInput) {
                quantityInput.setCustomValidity(insufficientStock ? 'Insufficient stock.' : '');
            }

            if (insufficientStock && showAlert && !insufficientStockActive) {
                alert('Insufficient stock. Quantity cannot be greater than the current available stock.');
            }

            insufficientStockActive = insufficientStock;
            return insufficientStock;
        }

        function updateSaveButtonState(showAlert) {
            var insufficientStock = syncInsufficientStockState(!!showAlert);

            if (submitButton) {
                submitButton.disabled = !validateRequiredFields() || insufficientStock;
            }
        }

        function updateTransferUI() {
            if (!movementAction) {
                return;
            }

            var currentAction = movementAction.value;

            if (transferRowWrap) {
                toggleTransferRowVisibility(currentAction);
            }
            if (destinationWarehouseSelect) {
                destinationWarehouseSelect.required = (currentAction === 'TRANSFER');
            }
            if (destinationFloorSelect) {
                destinationFloorSelect.required = (currentAction === 'TRANSFER');
            }
            if (transferStockWrap) {
                transferStockWrap.style.display = shouldShowStockPreview() ? '' : 'none';
            }

            if (currentAction === 'TRANSFER' && destinationWarehouseSelect && !destinationWarehouseSelect.value && warehouseSelect && warehouseSelect.value) {
                destinationWarehouseSelect.value = warehouseSelect.value;
                rebuildFloorOptions(destinationFloorSelect, destinationWarehouseSelect.value, destinationFloorId, 'Select Destination Floor');
            }

            if (shouldShowStockPreview()) {
                fetchTransferStock();
            } else {
                updateQuantityLimit(null);
                syncInsufficientStockState(false);
                if (transferStockBox) {
                    transferStockBox.textContent = 'Current Available Stock: -';
                }
            }

            updateSaveButtonState();
        }

        function fetchTransferStock() {
            if (!transferStockBox || !shouldShowStockPreview()) {
                updateQuantityLimit(null);
                syncInsufficientStockState(false);
                return;
            }

            var floorIdForStock = floorSelect ? floorSelect.value : '';
            var movementDateVal = movementDate ? movementDate.value : '';
            var itemCodeVal = itemSelect ? itemSelect.value : '';
            var excludeRecids = [];

            if (mode === 'edit' && editRecid) {
                excludeRecids.push(editRecid);
            }
            if (mode === 'edit' && movementAction && movementAction.value === 'TRANSFER' && editPairRecid) {
                excludeRecids.push(editPairRecid);
            }

            if (!floorIdForStock || !movementDateVal || !itemCodeVal) {
                updateQuantityLimit(null);
                syncInsufficientStockState(false);
                transferStockBox.textContent = 'Current Available Stock: -';
                updateSaveButtonState(false);
                return;
            }

            transferStockBox.textContent = 'Current Available Stock: loading...';

            $.ajax({
                type: 'POST',
                url: 'mf_warehouse_transaction_ajax.php',
                dataType: 'json',
                traditional: true,
                data: {
                    event_action: 'stock_preview',
                    floor_id: floorIdForStock,
                    itmcde: itemCodeVal,
                    movement_date: movementDateVal,
                    exclude_recids: excludeRecids
                },
                success: function (response) {
                    if (response && response.status === 1) {
                        transferStockBox.textContent = response.message || ('Current Available Stock: ' + parseFloat(response.available_stock || 0).toFixed(2));
                        updateQuantityLimit(parseFloat(response.available_stock));
                        updateSaveButtonState(false);
                    } else {
                        updateQuantityLimit(null);
                        syncInsufficientStockState(false);
                        transferStockBox.textContent = 'Current Available Stock: -';
                        updateSaveButtonState(false);
                    }
                },
                error: function () {
                    updateQuantityLimit(null);
                    syncInsufficientStockState(false);
                    transferStockBox.textContent = 'Current Available Stock: -';
                    updateSaveButtonState(false);
                }
            });
        }

        window.openTransactionView = function (data) {
            setViewField('view_movement_id', data.movement_id);
            setViewField('view_movement_date', data.movement_date);
            setViewField('view_movement_type', data.movement_type);
            setViewField('view_item_label', data.item_label);
            setViewField('view_qty', data.qty);
            setViewField('view_stkqty', data.stkqty);
            setViewField('view_source_floor', data.source_floor);
            setViewField('view_related_floor', data.related_floor);
            setViewField('view_staff', data.staff);
            setViewField('view_usercode', data.usercode);
            setViewField('view_remarks', data.remarks);

            if (!transactionViewModal && transactionViewModalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                transactionViewModal = new window.bootstrap.Modal(transactionViewModalEl);
            }

            if (transactionViewModal) {
                transactionViewModal.show();
            }
        };

        if (warehouseSelect) {
            warehouseSelect.addEventListener('change', function () {
                window.handleSourceWarehouseChange(this.value);
            });
        }

        if (floorSelect) {
            floorSelect.addEventListener('change', function () {
                fetchTransferStock();
                updateSaveButtonState();
            });
        }

        if (destinationWarehouseSelect) {
            destinationWarehouseSelect.addEventListener('change', function () {
                window.handleDestinationWarehouseChange(this.value);
            });
        }

        if (destinationFloorSelect) {
            destinationFloorSelect.addEventListener('change', updateSaveButtonState);
        }

        if (searchWarehouseSelect) {
            searchWarehouseSelect.addEventListener('change', function () {
                rebuildSearchFloorOptions(this.value, '');
            });
        }

        if (movementAction) {
            movementAction.addEventListener('change', updateTransferUI);
        }

        if (movementDate) {
            movementDate.addEventListener('change', function () {
                fetchTransferStock();
                updateSaveButtonState();
            });
        }

        if (quantityInput) {
            quantityInput.addEventListener('input', function () {
                updateSaveButtonState(false);
            });
        }

        if (staffSelect) {
            staffSelect.addEventListener('change', updateSaveButtonState);
        }

        if (transactionSearchModalEl) {
            transactionSearchModalEl.addEventListener('shown.bs.modal', function () {
                $('#search_date_from').trigger('focus');
            });
        }

        $('#open_item_search').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Select Item --',
            allowClear: true,
            width: '100%'
        });

        $('#open_item_search').on('change', function () {
            fetchTransferStock();
            updateSaveButtonState();
        });

        rebuildFloorOptions(floorSelect, warehouseSelect ? warehouseSelect.value : sourceWarehouseId, sourceFloorId, 'Select Source Floor');
        rebuildFloorOptions(destinationFloorSelect, destinationWarehouseSelect ? destinationWarehouseSelect.value : destinationWarehouseId, destinationFloorId, 'Select Destination Floor');
        rebuildSearchFloorOptions(searchSelectedWarehouseId, searchSelectedFloorId);
        updateTransferUI();

        if (mode === 'edit' && shouldShowStockPreview()) {
            fetchTransferStock();
        } else if (!shouldShowStockPreview()) {
            updateQuantityLimit(null);
        }

        if (movementForm) {
            movementForm.addEventListener('submit', function (event) {
                if (!validateRequiredFields()) {
                    event.preventDefault();
                    alert('Please complete all required fields before saving.');
                    return;
                }

                if (syncInsufficientStockState(true)) {
                    event.preventDefault();
                    updateSaveButtonState(false);
                    return;
                }

                if (movementForm.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                movementForm.dataset.submitting = '1';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = (mode === 'edit') ? 'Updating...' : 'Saving...';
                }
            });
        }

        updateSaveButtonState();
    })();
</script>

<?php require "includes/main_footer.php"; ?>

