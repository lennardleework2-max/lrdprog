<?php

session_start();

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=UTF-8');

function unpaid_all_ajax_json_response($status, $data = array(), $msg = '')
{
    echo json_encode(array(
        'status' => $status,
        'data' => $data,
        'msg' => $msg
    ));
    exit;
}

function unpaid_all_ajax_validate_route_id($route_id)
{
    $route_id = trim((string)$route_id);
    if ($route_id === '' || strtolower($route_id) === 'all') {
        return '';
    }
    if (strlen($route_id) > 100 || !preg_match('/^[A-Za-z0-9._-]+$/', $route_id)) {
        return false;
    }
    return $route_id;
}

// if (
//     !isset($_SESSION['userdesc']) ||
//     !isset($_SESSION['password']) ||
//     !isset($_SESSION['usercode'])
// ) {
//     unpaid_all_ajax_json_response(0, array(), 'Access denied.');
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unpaid_all_ajax_json_response(0, array(), 'Invalid request method.');
}

$event_action = isset($_POST['event_action']) ? trim((string)$_POST['event_action']) : '';

if ($event_action === 'get_buyers') {
    try {
        $route_id_raw = isset($_POST['route_id']) ? $_POST['route_id'] : '';
        $route_id = unpaid_all_ajax_validate_route_id($route_id_raw);

        if ($route_id === false) {
            unpaid_all_ajax_json_response(0, array(), 'Invalid route selection.');
        }

        // Get buyers that have unpaid records matching the filters
        // Using the same filter logic from the report file
        $select_buyers = "SELECT DISTINCT
                              mf_buyers.buyer_id,
                              mf_buyers.buyer_name
                          FROM tranfile1
                          INNER JOIN mf_routes
                              ON tranfile1.route_id = mf_routes.route_id
                          INNER JOIN mf_buyers
                              ON tranfile1.buyer_id = mf_buyers.buyer_id
                          WHERE (tranfile1.paydate IS NULL OR tranfile1.paydate = '')
                            AND tranfile1.trncde = ?
                            AND (mf_routes.route_desc IS NULL OR mf_routes.route_desc <> '-')
                            AND tranfile1.buyer_id IS NOT NULL
                            AND TRIM(tranfile1.buyer_id) <> ''";

        $params = array('SAL');

        // Filter by route if specified
        if ($route_id !== '') {
            $select_buyers .= " AND tranfile1.route_id = ?";
            $params[] = $route_id;
        }

        $select_buyers .= " ORDER BY mf_buyers.buyer_name ASC";

        $stmt_buyers = $link->prepare($select_buyers);
        $stmt_buyers->execute($params);

        $buyers = array();
        while ($rs_buyer = $stmt_buyers->fetch()) {
            $buyer_id = isset($rs_buyer['buyer_id']) ? trim((string)$rs_buyer['buyer_id']) : '';
            $buyer_name = isset($rs_buyer['buyer_name']) ? trim((string)$rs_buyer['buyer_name']) : '';

            if ($buyer_id !== '') {
                $buyers[] = array(
                    'id' => htmlspecialchars($buyer_id, ENT_QUOTES, 'UTF-8'),
                    'text' => htmlspecialchars($buyer_name !== '' ? $buyer_name : $buyer_id, ENT_QUOTES, 'UTF-8')
                );
            }
        }

        unpaid_all_ajax_json_response(1, $buyers, '');

    } catch (Exception $e) {
        error_log('Error in unpaid_route_customer_pdf_all_ajax: ' . $e->getMessage());
        unpaid_all_ajax_json_response(0, array(), 'An error occurred while loading buyers.');
    }
} else {
    unpaid_all_ajax_json_response(0, array(), 'Invalid action.');
}

?>
