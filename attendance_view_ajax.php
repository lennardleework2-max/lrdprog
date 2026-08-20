<?php
/**
 * attendance_view_ajax.php - AJAX Handler for Attendance View Page
 *
 * Handles data fetching for the attendance_view.php page.
 * VIEW-ONLY: No add, edit, delete, save, or update operations.
 */

header('Content-Type: application/json');

session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");

// Check if user is logged in
if (!isset($_SESSION['recid'])) {
    error_log("[BEAVER-ERROR] User not authenticated - no session recid");
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'list':
        listRecords();
        break;

    case 'get':
        getRecord();
        break;

    default:
        error_log("[BEAVER-ERROR] Invalid action requested: " . $action);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * List timekeeping records with pagination and filters
 */
function listRecords() {
    global $link;

    error_log("[BEAVER-001] Start load records request");
    error_log("[BEAVER-002] Raw request parameters: " . json_encode($_GET));

    // Parse and sanitize pagination parameters
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $limitRaw = isset($_GET['limit']) ? $_GET['limit'] : 10;

    error_log("[BEAVER-003] Raw pagination - page: " . var_export($page, true) . ", limit: " . var_export($limitRaw, true));

    // Strictly cast to integers for safety
    $page = max(1, (int) $page);
    $limit = max(1, min(100, (int) $limitRaw));
    $offset = ($page - 1) * $limit;

    error_log("[BEAVER-004] Sanitized pagination - page: $page, limit: $limit, offset: $offset");

    // Parse search/filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $dateFrom = isset($_GET['dateFrom']) ? trim($_GET['dateFrom']) : '';
    $dateTo = isset($_GET['dateTo']) ? trim($_GET['dateTo']) : '';

    error_log("[BEAVER-005] Search/filter params - search: '$search', dateFrom: '$dateFrom', dateTo: '$dateTo'");

    // Build WHERE conditions
    $conditions = [];
    $params = [];

    if ($search !== '') {
        $conditions[] = '(t.emp_id LIKE ? OR e.fname LIKE ? OR e.lname LIKE ? OR CONCAT(e.fname, " ", e.lname) LIKE ?)';
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if ($dateFrom !== '') {
        $conditions[] = 'DATE(t.trn_date) >= ?';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $conditions[] = 'DATE(t.trn_date) <= ?';
        $params[] = $dateTo;
    }

    $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    error_log("[BEAVER-006] WHERE clause built: " . ($whereClause ?: '(none)'));
    error_log("[BEAVER-007] SQL params (for WHERE): " . json_encode($params));

    try {
        // Get total count
        $countSql = "SELECT COUNT(*) as total
            FROM timekeeping_trn t
            LEFT JOIN employee_mf e ON t.emp_id = e.emp_id
            $whereClause";

        error_log("[BEAVER-008] Count SQL: " . preg_replace('/\s+/', ' ', $countSql));

        $stmtCount = $link->prepare($countSql);
        $stmtCount->execute($params);
        $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $total = (int)$countResult['total'];

        error_log("[BEAVER-009] Total count result: $total");

        // Get paginated results
        // FIX: Do NOT use placeholders for LIMIT/OFFSET with PDO execute() array
        // PDO binds all array values as strings, causing: LIMIT '10' OFFSET '0' (invalid SQL)
        // Instead, safely interpolate the integer-casted values directly
        $sql = "SELECT
            t.*,
            CONCAT(e.fname, ' ', e.lname) AS employee_name,
            e.set_bonus
        FROM timekeeping_trn t
        LEFT JOIN employee_mf e ON t.emp_id = e.emp_id
        $whereClause
        ORDER BY t.trn_date DESC, t.emp_id ASC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        error_log("[BEAVER-010] Final SQL: " . preg_replace('/\s+/', ' ', $sql));
        error_log("[BEAVER-011] Executing SQL query with params: " . json_encode($params));

        $stmt = $link->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rowCount = count($rows);
        error_log("[BEAVER-012] SQL executed successfully - fetched $rowCount rows");

        $response = [
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $total > 0 ? ceil($total / $limit) : 1
        ];

        error_log("[BEAVER-013] Response JSON prepared - success: true, rows: $rowCount, total: $total");

        echo json_encode($response);

    } catch (PDOException $e) {
        error_log("[BEAVER-ERROR] PDOException in listRecords");
        error_log("[BEAVER-ERROR] Message: " . $e->getMessage());
        error_log("[BEAVER-ERROR] Code: " . $e->getCode());
        error_log("[BEAVER-ERROR] File: " . $e->getFile() . " Line: " . $e->getLine());
        error_log("[BEAVER-ERROR] SQL State: " . (isset($e->errorInfo[0]) ? $e->errorInfo[0] : 'N/A'));

        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get a single timekeeping record by recid
 */
function getRecord() {
    global $link;

    error_log("[BEAVER-GET-001] Start get single record request");

    $recid = isset($_GET['recid']) ? (int) $_GET['recid'] : 0;

    error_log("[BEAVER-GET-002] Requested recid: $recid");

    if ($recid <= 0) {
        error_log("[BEAVER-GET-ERROR] Invalid record ID: $recid");
        echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
        return;
    }

    try {
        $sql = "SELECT
            t.*,
            CONCAT(e.fname, ' ', e.lname) AS employee_name,
            e.set_bonus,
            e.basic_salary
        FROM timekeeping_trn t
        LEFT JOIN employee_mf e ON t.emp_id = e.emp_id
        WHERE t.recid = ?
        LIMIT 1";

        error_log("[BEAVER-GET-003] SQL: " . preg_replace('/\s+/', ' ', $sql));

        $stmt = $link->prepare($sql);
        $stmt->execute([$recid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            error_log("[BEAVER-GET-004] Record found for recid: $recid");
            echo json_encode([
                'success' => true,
                'data' => $row
            ]);
        } else {
            error_log("[BEAVER-GET-005] Record not found for recid: $recid");
            echo json_encode([
                'success' => false,
                'message' => 'Record not found'
            ]);
        }

    } catch (PDOException $e) {
        error_log("[BEAVER-GET-ERROR] PDOException in getRecord");
        error_log("[BEAVER-GET-ERROR] Message: " . $e->getMessage());
        error_log("[BEAVER-GET-ERROR] File: " . $e->getFile() . " Line: " . $e->getLine());

        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>
