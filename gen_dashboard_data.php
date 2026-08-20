<?php
/**
 * =============================================================================
 * BEAVER METHOD DEBUG SYSTEM - GEN_DASHBOARD_DATA.PHP (AJAX ENDPOINT)
 * =============================================================================
 * Purpose: Identify where the live dashboard AJAX request gets stuck
 *
 * HOW TO ENABLE: Same as gen_dashboard.php - see that file for details
 * LOG LOCATION: Same as gen_dashboard.php
 * =============================================================================
 */

// === BEAVER CONFIG (same as gen_dashboard.php) ===
if (!defined('BEAVER_DEBUG_ENABLED')) {
    define('BEAVER_DEBUG_ENABLED', false);
}
if (!defined('BEAVER_DEBUG_SECRET')) {
    define('BEAVER_DEBUG_SECRET', 'beaver_diag_2024');
}
if (!defined('BEAVER_LOG_TO_FILE')) {
    define('BEAVER_LOG_TO_FILE', true);
}
if (!defined('BEAVER_LOG_DIR')) {
    define('BEAVER_LOG_DIR', __DIR__ . '/beaver_debug_logs');
}

$BEAVER_DATA_REQUEST_ID = 'BVR_DATA_' . substr(md5(uniqid(mt_rand(), true)), 0, 10);
$BEAVER_DATA_START_TIME = microtime(true);
$BEAVER_DATA_START_MEMORY = memory_get_usage(true);
$BEAVER_DATA_CHECKPOINT_COUNT = 0;
$BEAVER_DATA_ENABLED = false;

if (BEAVER_DEBUG_ENABLED) {
    $BEAVER_DATA_ENABLED = true;
} elseif (isset($_GET['beaver_debug']) && $_GET['beaver_debug'] === BEAVER_DEBUG_SECRET) {
    $BEAVER_DATA_ENABLED = true;
} elseif (file_exists(__DIR__ . '/beaver_debug_enabled.flag')) {
    $BEAVER_DATA_ENABLED = true;
}

/**
 * Beaver logging for data endpoint
 */
function beaver_data_log($checkpoint_name, $extra_data = array()) {
    global $BEAVER_DATA_REQUEST_ID, $BEAVER_DATA_START_TIME, $BEAVER_DATA_START_MEMORY, $BEAVER_DATA_CHECKPOINT_COUNT, $BEAVER_DATA_ENABLED;

    if (!$BEAVER_DATA_ENABLED) {
        return;
    }

    $BEAVER_DATA_CHECKPOINT_COUNT++;
    $elapsed_ms = round((microtime(true) - $BEAVER_DATA_START_TIME) * 1000, 2);
    $current_memory = memory_get_usage(true);
    $memory_mb = round($current_memory / 1024 / 1024, 2);
    $peak_memory_mb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

    $log_entry = sprintf(
        "[BEAVER_DATA] [%s] [#%03d] [+%sms] [Mem: %sMB / Peak: %sMB] %s",
        $BEAVER_DATA_REQUEST_ID,
        $BEAVER_DATA_CHECKPOINT_COUNT,
        str_pad($elapsed_ms, 8, ' ', STR_PAD_LEFT),
        str_pad($memory_mb, 6, ' ', STR_PAD_LEFT),
        str_pad($peak_memory_mb, 6, ' ', STR_PAD_LEFT),
        $checkpoint_name
    );

    if (!empty($extra_data)) {
        $safe_extra = array();
        foreach ($extra_data as $key => $value) {
            $lower_key = strtolower($key);
            if (strpos($lower_key, 'password') !== false ||
                strpos($lower_key, 'token') !== false ||
                strpos($lower_key, 'secret') !== false) {
                $safe_extra[$key] = '[REDACTED]';
            } else {
                $str_val = is_array($value) ? json_encode($value) : (string)$value;
                $safe_extra[$key] = strlen($str_val) > 100 ? substr($str_val, 0, 100) . '...' : $str_val;
            }
        }
        $log_entry .= ' | ' . json_encode($safe_extra);
    }

    error_log($log_entry);

    if (BEAVER_LOG_TO_FILE) {
        $log_dir = BEAVER_LOG_DIR;
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0750, true);
            @file_put_contents($log_dir . '/.htaccess', "Deny from all\n");
        }
        if (is_writable($log_dir) || is_writable(dirname($log_dir))) {
            $log_file = $log_dir . '/beaver_data_' . date('Ymd') . '.log';
            @file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $log_entry . "\n", FILE_APPEND | LOCK_EX);
        }
    }
}

/**
 * Beaver query timing helper
 */
function beaver_data_query($label, $start, $rows = null, $error = null) {
    $duration = round((microtime(true) - $start) * 1000, 2);
    $extra = array('ms' => $duration);
    if ($rows !== null) $extra['rows'] = $rows;
    if ($error !== null) $extra['err'] = substr($error, 0, 150);
    $status = $error ? 'FAIL' : 'OK';
    beaver_data_log("QUERY [{$status}]: {$label}", $extra);
}

beaver_data_log("=== GEN_DASHBOARD_DATA.PHP START ===");

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

beaver_data_log("CHECKPOINT: Session check starting");

if(function_exists('session_status')){
    if(session_status() !== PHP_SESSION_ACTIVE){
        session_start();
    }
}elseif(!isset($_SESSION)){
    session_start();
}

beaver_data_log("CHECKPOINT: Session started", array('session_id' => session_id() ? 'active' : 'none'));

header('Content-Type: application/json; charset=utf-8');

function dashboard_respond($payload, $status_code = 200)
{
    global $BEAVER_DATA_START_TIME, $BEAVER_DATA_CHECKPOINT_COUNT;
    beaver_data_log("=== RESPONSE SENT ===", array(
        'status' => $status_code,
        'success' => isset($payload['success']) ? ($payload['success'] ? 'true' : 'false') : 'unknown',
        'total_ms' => round((microtime(true) - $BEAVER_DATA_START_TIME) * 1000, 2),
        'checkpoints' => $BEAVER_DATA_CHECKPOINT_COUNT
    ));
    http_response_code($status_code);
    echo json_encode($payload);
    exit;
}

if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    beaver_data_log("AUTH FAILED: Session expired");
    dashboard_respond(array(
        'success' => false,
        'message' => 'Session expired.'
    ), 401);
}

beaver_data_log("CHECKPOINT: Auth passed, loading DB includes");
$beaver_db_start = microtime(true);

require_once "resources/db_init.php";
require_once "resources/connect4.php";

beaver_data_log("CHECKPOINT: DB includes loaded", array(
    'db_include_ms' => round((microtime(true) - $beaver_db_start) * 1000, 2),
    'link_exists' => isset($link) ? 'yes' : 'no'
));

function dashboard_valid_date($value)
{
    $value = trim((string)$value);

    if($value === ''){
        return '';
    }

    $formats = array('Y-m-d', 'm/d/Y');

    foreach($formats as $format){
        $date = DateTime::createFromFormat($format, $value);
        if($date && $date->format($format) === $value){
            return $date->format('Y-m-d');
        }
    }

    return '';
}

function dashboard_format_date($value, $empty_text = 'No sales yet')
{
    if(empty($value)){
        return $empty_text;
    }

    $timestamp = strtotime($value);
    if($timestamp === false){
        return $empty_text;
    }

    return date('m/d/Y', $timestamp);
}

function dashboard_is_valid_trend_year($value)
{
    $value = trim((string)$value);

    if(!preg_match('/^\d{4}$/', $value)){
        return false;
    }

    $year_number = (int)$value;
    $current_year = (int)date('Y');

    return $year_number >= 1900 && $year_number <= ($current_year + 10);
}

$from_date = isset($_GET['from_date']) ? dashboard_valid_date($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? dashboard_valid_date($_GET['to_date']) : '';
$trend_year = isset($_GET['trend_year']) ? trim((string)$_GET['trend_year']) : date('Y');

if(!dashboard_is_valid_trend_year($trend_year)){
    $trend_year = date('Y');
}

if($from_date !== '' && $to_date !== '' && $from_date > $to_date){
    $date_swap = $from_date;
    $from_date = $to_date;
    $to_date = $date_swap;
}

$sales_filter_sql = '';
$sales_filter_params = array();
$range_label = 'All transaction dates.';

if($from_date !== ''){
    $sales_filter_sql .= ' AND tranfile1.trndte >= ?';
    $sales_filter_params[] = $from_date;
}

if($to_date !== ''){
    $sales_filter_sql .= ' AND tranfile1.trndte <= ?';
    $sales_filter_params[] = $to_date;
}

if($from_date !== '' && $to_date !== ''){
    $range_label = 'Filtered from ' . date('m/d/Y', strtotime($from_date)) . ' to ' . date('m/d/Y', strtotime($to_date)) . '.';
}elseif($from_date !== ''){
    $range_label = 'Filtered from ' . date('m/d/Y', strtotime($from_date)) . ' onward.';
}elseif($to_date !== ''){
    $range_label = 'Filtered up to ' . date('m/d/Y', strtotime($to_date)) . '.';
}

$expense_filter_sql = '';
$expense_filter_params = array();

if($from_date !== ''){
    $expense_filter_sql .= ' AND trndte >= ?';
    $expense_filter_params[] = $from_date;
}

if($to_date !== ''){
    $expense_filter_sql .= ' AND trndte <= ?';
    $expense_filter_params[] = $to_date;
}

$stock_subquery = "
    SELECT
        itmcde,
        COALESCE(SUM(stkqty), 0) AS current_stock
    FROM tranfile2
    GROUP BY itmcde
";

$latest_purchase_cost_subquery = "
    SELECT
        purchase_rows.itmcde,
        purchase_rows.untprc
    FROM (
        SELECT
            tranfile2.itmcde,
            tranfile2.untprc,
            tranfile1.trndte,
            tranfile2.recid
        FROM tranfile1
        INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
        WHERE tranfile1.trncde = 'PUR'
          AND tranfile2.unmcde = ?
    ) AS purchase_rows
    LEFT JOIN (
        SELECT
            tranfile2.itmcde,
            tranfile1.trndte,
            tranfile2.recid
        FROM tranfile1
        INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
        WHERE tranfile1.trncde = 'PUR'
          AND tranfile2.unmcde = ?
    ) AS newer_rows
        ON newer_rows.itmcde = purchase_rows.itmcde
       AND (
            newer_rows.trndte > purchase_rows.trndte
            OR (
                newer_rows.trndte = purchase_rows.trndte
                AND newer_rows.recid > purchase_rows.recid
            )
       )
    WHERE newer_rows.itmcde IS NULL
";

beaver_data_log("CHECKPOINT: Entering try block - starting queries");

try{
    // Query 1: PCS Unit Code
    beaver_data_log("QUERY_START: pcs_unmcde lookup");
    $q_start = microtime(true);
    $pcs_stmt = $link->prepare("SELECT unmcde FROM itemunitmeasurefile WHERE LOWER(TRIM(unmdsc)) = 'pcs' ORDER BY recid ASC LIMIT 1");
    $pcs_stmt->execute();
    $pcs_row = $pcs_stmt->fetch(PDO::FETCH_ASSOC);
    $pcs_unmcde = $pcs_row ? trim((string)$pcs_row['unmcde']) : '';
    beaver_data_query("pcs_unmcde lookup", $q_start, 1);

    $stock_valuation_total = 0;
    if($pcs_unmcde !== ''){
        // Query 2: Stock Valuation (complex - may be slow)
        beaver_data_log("QUERY_START: stock_valuation (COMPLEX QUERY - may be slow)");
        $q_start = microtime(true);
        $stock_valuation_sql = "
            SELECT
                COALESCE(SUM(COALESCE(stock.current_stock, 0) * COALESCE(latest_cost.untprc, 0)), 0) AS total_stock_valuation
            FROM itemfile
            LEFT JOIN (
                {$stock_subquery}
            ) AS stock
                ON stock.itmcde = itemfile.itmcde
            LEFT JOIN (
                {$latest_purchase_cost_subquery}
            ) AS latest_cost
                ON latest_cost.itmcde = itemfile.itmcde
        ";
        $stock_valuation_stmt = $link->prepare($stock_valuation_sql);
        $stock_valuation_stmt->execute(array($pcs_unmcde, $pcs_unmcde));
        $stock_valuation_row = $stock_valuation_stmt->fetch(PDO::FETCH_ASSOC);
        $stock_valuation_total = $stock_valuation_row ? (float)$stock_valuation_row['total_stock_valuation'] : 0;
        beaver_data_query("stock_valuation", $q_start, 1);
    } else {
        beaver_data_log("SKIP: stock_valuation (no pcs_unmcde found)");
    }

    // Query 3: Net Profit (aggregate on tranfile1+2 - may be slow)
    beaver_data_log("QUERY_START: net_profit aggregate");
    $q_start = microtime(true);
    $net_profit_sql = "
        SELECT
            COALESCE(SUM(CASE WHEN tranfile1.trncde = 'SAL' THEN tranfile2.extprc ELSE 0 END), 0) AS total_sal,
            COALESCE(SUM(CASE WHEN tranfile1.trncde = 'PUR' THEN tranfile2.extprc ELSE 0 END), 0) AS total_pur,
            COALESCE(SUM(CASE WHEN tranfile1.trncde = 'SRT' THEN tranfile2.extprc ELSE 0 END), 0) AS total_srt,
            COALESCE(SUM(CASE WHEN tranfile1.trncde = 'ADJ' THEN tranfile2.extprc ELSE 0 END), 0) AS total_adj
        FROM tranfile1
        INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
        WHERE tranfile1.trncde IN ('SAL', 'PUR', 'SRT', 'ADJ')
        {$sales_filter_sql}
    ";
    $net_profit_stmt = $link->prepare($net_profit_sql);
    $net_profit_stmt->execute($sales_filter_params);
    $net_profit_row = $net_profit_stmt->fetch(PDO::FETCH_ASSOC);
    beaver_data_query("net_profit aggregate", $q_start, 1);

    // Query 4: Total Expenses
    beaver_data_log("QUERY_START: expenses total");
    $q_start = microtime(true);
    $expense_sql = "
        SELECT COALESCE(SUM(trntot), 0) AS total_expenses
        FROM expensefile1
        WHERE 1 = 1
        {$expense_filter_sql}
    ";
    $expense_stmt = $link->prepare($expense_sql);
    $expense_stmt->execute($expense_filter_params);
    $expense_row = $expense_stmt->fetch(PDO::FETCH_ASSOC);
    beaver_data_query("expenses total", $q_start, 1);

    $total_sal = $net_profit_row ? (float)$net_profit_row['total_sal'] : 0;
    $total_pur = $net_profit_row ? (float)$net_profit_row['total_pur'] : 0;
    $total_srt = $net_profit_row ? (float)$net_profit_row['total_srt'] : 0;
    $total_adj = $net_profit_row ? (float)$net_profit_row['total_adj'] : 0;
    $total_expenses = $expense_row ? (float)$expense_row['total_expenses'] : 0;

    $net_profit_value =
        $total_sal
        - $total_pur
        + $total_srt
        + $total_adj
        - $total_expenses;

    // Query 5: Total SKUs
    beaver_data_log("QUERY_START: sku_count");
    $q_start = microtime(true);
    $sku_stmt = $link->prepare("SELECT COUNT(*) AS total_skus FROM itemfile");
    $sku_stmt->execute();
    $sku_row = $sku_stmt->fetch(PDO::FETCH_ASSOC);
    $total_skus = $sku_row ? (int)$sku_row['total_skus'] : 0;
    beaver_data_query("sku_count", $q_start, 1);

    $best_selling_base_sql = "
        SELECT
            tranfile2.itmcde,
            COALESCE(itemfile.itmdsc, tranfile2.itmcde) AS itmdsc,
            COALESCE(SUM(tranfile2.extprc), 0) AS total_sales,
            COALESCE(SUM(tranfile2.stkqty) * -1, 0) AS quantity_sold
        FROM tranfile1
        INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
        LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde
        WHERE tranfile1.trncde = 'SAL'
        {$sales_filter_sql}
        GROUP BY tranfile2.itmcde, itemfile.itmdsc
    ";

    // Query 6: Best selling by sales
    beaver_data_log("QUERY_START: best_selling_by_sales");
    $q_start = microtime(true);
    $best_selling_by_sales_stmt = $link->prepare("
        SELECT *
        FROM (
            {$best_selling_base_sql}
        ) AS ranked_items
        ORDER BY total_sales DESC, quantity_sold DESC, itmdsc ASC
        LIMIT 1
    ");
    $best_selling_by_sales_stmt->execute($sales_filter_params);
    $best_selling_by_sales = $best_selling_by_sales_stmt->fetch(PDO::FETCH_ASSOC);
    beaver_data_query("best_selling_by_sales", $q_start, 1);

    // Query 7: Best selling by quantity
    beaver_data_log("QUERY_START: best_selling_by_quantity");
    $q_start = microtime(true);
    $best_selling_by_quantity_stmt = $link->prepare("
        SELECT *
        FROM (
            {$best_selling_base_sql}
        ) AS ranked_items
        ORDER BY quantity_sold DESC, total_sales DESC, itmdsc ASC
        LIMIT 1
    ");
    $best_selling_by_quantity_stmt->execute($sales_filter_params);
    $best_selling_by_quantity = $best_selling_by_quantity_stmt->fetch(PDO::FETCH_ASSOC);
    beaver_data_query("best_selling_by_quantity", $q_start, 1);

    $best_selling_items = array(
        'by_sales' => null,
        'by_quantity' => null
    );

    if($best_selling_by_sales){
        $best_selling_by_sales['total_sales'] = (float)$best_selling_by_sales['total_sales'];
        $best_selling_by_sales['quantity_sold'] = (float)$best_selling_by_sales['quantity_sold'];
        $best_selling_items['by_sales'] = $best_selling_by_sales;
    }

    if($best_selling_by_quantity){
        $best_selling_by_quantity['total_sales'] = (float)$best_selling_by_quantity['total_sales'];
        $best_selling_by_quantity['quantity_sold'] = (float)$best_selling_by_quantity['quantity_sold'];
        $best_selling_items['by_quantity'] = $best_selling_by_quantity;
    }

    // Query 8: Slow moving items (COMPLEX - multiple subqueries)
    beaver_data_log("QUERY_START: slow_moving_items (COMPLEX QUERY)");
    $q_start = microtime(true);
    $slow_moving_sql = "
        SELECT
            itemfile.itmdsc,
            stock.current_stock,
            last_sales.last_sale_date,
            last_purchases.last_purchase_date
        FROM itemfile
        INNER JOIN (
            {$stock_subquery}
        ) AS stock
            ON stock.itmcde = itemfile.itmcde
           AND stock.current_stock > 0
        INNER JOIN (
            SELECT
                tranfile2.itmcde,
                MAX(tranfile1.trndte) AS last_purchase_date
            FROM tranfile1
            INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            WHERE tranfile1.trncde = 'PUR'
            GROUP BY tranfile2.itmcde
        ) AS last_purchases
            ON last_purchases.itmcde = itemfile.itmcde
        LEFT JOIN (
            SELECT
                tranfile2.itmcde,
                MAX(tranfile1.trndte) AS last_sale_date
            FROM tranfile1
            INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            WHERE tranfile1.trncde = 'SAL'
            GROUP BY tranfile2.itmcde
        ) AS last_sales
            ON last_sales.itmcde = itemfile.itmcde
        ORDER BY
            CASE WHEN last_sales.last_sale_date IS NULL THEN 0 ELSE 1 END ASC,
            last_sales.last_sale_date ASC,
            stock.current_stock DESC
        LIMIT 3
    ";
    $slow_moving_stmt = $link->prepare($slow_moving_sql);
    $slow_moving_stmt->execute();
    $slow_moving_items = $slow_moving_stmt->fetchAll(PDO::FETCH_ASSOC);
    beaver_data_query("slow_moving_items", $q_start, count($slow_moving_items));

    foreach($slow_moving_items as $slow_index => $slow_item){
        $slow_moving_items[$slow_index]['current_stock'] = (float)$slow_item['current_stock'];
        $slow_moving_items[$slow_index]['last_sale_date_display'] = dashboard_format_date($slow_item['last_sale_date']);
        $slow_moving_items[$slow_index]['last_purchase_date_display'] = dashboard_format_date($slow_item['last_purchase_date'], 'No purchase yet');
    }

    // Query 9: Best salesman (filtered period)
    beaver_data_log("QUERY_START: best_salesman");
    $q_start = microtime(true);
    $best_salesman_sql = "
        SELECT
            tranfile1.salesman_id,
            COALESCE(mf_salesman.salesman_name, tranfile1.salesman_id) AS salesman_name,
            COALESCE(mf_salesman.commission, 0) AS commission_rate,
            COALESCE(SUM(tranfile2.extprc), 0) AS total_sales
        FROM tranfile1
        INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
        LEFT JOIN mf_salesman ON mf_salesman.salesman_id = tranfile1.salesman_id
        WHERE tranfile1.trncde = 'SAL'
          AND COALESCE(TRIM(tranfile1.salesman_id), '') <> ''
          AND tranfile1.salesman_id NOT IN (
              SELECT excluded_salesman.salesman_id
              FROM mf_salesman AS excluded_salesman
              WHERE TRIM(COALESCE(excluded_salesman.salesman_name, '')) = '-None'
                AND COALESCE(TRIM(excluded_salesman.salesman_id), '') <> ''
          )
        {$sales_filter_sql}
        GROUP BY tranfile1.salesman_id, mf_salesman.salesman_name, mf_salesman.commission
        ORDER BY total_sales DESC, salesman_name ASC
        LIMIT 1
    ";
    $best_salesman_stmt = $link->prepare($best_salesman_sql);
    $best_salesman_stmt->execute($sales_filter_params);
    $best_salesman = $best_salesman_stmt->fetch(PDO::FETCH_ASSOC);
    beaver_data_query("best_salesman", $q_start, 1);

    if($best_salesman){
        $best_salesman['commission_rate'] = (float)$best_salesman['commission_rate'];
        $best_salesman['total_sales'] = (float)$best_salesman['total_sales'];
        $best_salesman['total_commission'] = $best_salesman['total_sales'] * ($best_salesman['commission_rate'] / 100);
    }

    // Best Salesman Comparison: previous month vs current month-to-date
    // Uses same period logic as expenses
    // COGS calculation matches trndate_rep_sales_cost.php logic

    function dashboard_get_best_salesman_with_cogs($link, $start_date, $end_date, $pcs_unmcde) {
        // NOTE: This function runs multiple queries and has a loop for COGS calculation
        // If this takes long, check query timings below
        beaver_data_log("FUNC_START: dashboard_get_best_salesman_with_cogs", array('start' => $start_date, 'end' => $end_date));
        $func_start = microtime(true);

        // Get best salesman for the period
        $q_start = microtime(true);
        $salesman_sql = "
            SELECT
                tranfile1.salesman_id,
                COALESCE(mf_salesman.salesman_name, tranfile1.salesman_id) AS salesman_name,
                COALESCE(mf_salesman.commission, 0) AS commission_rate,
                COALESCE(SUM(tranfile2.extprc), 0) AS total_sales
            FROM tranfile1
            INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            LEFT JOIN mf_salesman ON mf_salesman.salesman_id = tranfile1.salesman_id
            WHERE tranfile1.trncde = 'SAL'
              AND COALESCE(TRIM(tranfile1.salesman_id), '') <> ''
              AND tranfile1.salesman_id NOT IN (
                  SELECT excluded_salesman.salesman_id
                  FROM mf_salesman AS excluded_salesman
                  WHERE TRIM(COALESCE(excluded_salesman.salesman_name, '')) = '-None'
                    AND COALESCE(TRIM(excluded_salesman.salesman_id), '') <> ''
              )
              AND tranfile1.trndte >= ?
              AND tranfile1.trndte <= ?
            GROUP BY tranfile1.salesman_id, mf_salesman.salesman_name, mf_salesman.commission
            ORDER BY total_sales DESC, salesman_name ASC
            LIMIT 1
        ";
        $salesman_stmt = $link->prepare($salesman_sql);
        $salesman_stmt->execute(array($start_date, $end_date));
        $best_salesman = $salesman_stmt->fetch(PDO::FETCH_ASSOC);
        beaver_data_query("func:salesman_lookup [{$start_date} to {$end_date}]", $q_start, 1);

        if(!$best_salesman) {
            beaver_data_log("FUNC_END: dashboard_get_best_salesman_with_cogs - no salesman found");
            return null;
        }

        $salesman_id = $best_salesman['salesman_id'];
        $total_sales = (float)$best_salesman['total_sales'];
        $commission_rate = (float)$best_salesman['commission_rate'];
        $total_commission = $total_sales * ($commission_rate / 100);

        // Get all sales items for this salesman in this period to calculate COGS
        // COGS calculation follows trndate_rep_sales_cost.php logic
        $q_start = microtime(true);
        $sales_items_sql = "
            SELECT
                tranfile2.recid,
                tranfile2.itmcde,
                tranfile2.unmcde,
                tranfile2.itmqty,
                tranfile2.extprc,
                tranfile1.trndte
            FROM tranfile1
            INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
            WHERE tranfile1.trncde = 'SAL'
              AND tranfile1.salesman_id = ?
              AND tranfile1.trndte >= ?
              AND tranfile1.trndte <= ?
        ";
        $sales_items_stmt = $link->prepare($sales_items_sql);
        $sales_items_stmt->execute(array($salesman_id, $start_date, $end_date));
        $sales_items = $sales_items_stmt->fetchAll(PDO::FETCH_ASSOC);
        beaver_data_query("func:sales_items [{$start_date} to {$end_date}]", $q_start, count($sales_items));

        // Collect unique item codes
        $unique_items = array();
        foreach($sales_items as $item) {
            if(!empty($item['itmcde'])) {
                $unique_items[$item['itmcde']] = true;
            }
        }

        // Batch load purchase costs (matching trndate_rep_sales_cost.php logic)
        $cost_cache = array();
        if(!empty($unique_items)) {
            $item_list = array_keys($unique_items);
            $placeholders = implode(',', array_fill(0, count($item_list), '?'));
            $cost_query_params = $item_list;

            // Get the latest cost for each item by date (matching trndate_rep_sales_cost.php)
            beaver_data_log("QUERY_START: func:cost_cache (may be slow with many items)", array('item_count' => count($item_list)));
            $q_start = microtime(true);
            $cost_query = "
                SELECT t2.itmcde, t2.unmcde, t2.untprc, t2.recid, t1.trndte
                FROM tranfile2 t2
                INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
                WHERE t2.itmcde IN ($placeholders)
                  AND t1.trncde = 'PUR'
                  AND t2.stkqty > 0
                ORDER BY t1.trndte DESC, t2.recid DESC
            ";
            $cost_stmt = $link->prepare($cost_query);
            $cost_stmt->execute($cost_query_params);

            $cost_row_count = 0;
            while($cost_row = $cost_stmt->fetch(PDO::FETCH_ASSOC)) {
                $cost_key = (string)$cost_row['itmcde'] . '|' . ($cost_row['unmcde'] === null ? '__NULL__' : (string)$cost_row['unmcde']);
                if(!isset($cost_cache[$cost_key])) {
                    $cost_cache[$cost_key] = array();
                }
                $cost_cache[$cost_key][] = $cost_row;
                $cost_row_count++;
            }
            beaver_data_query("func:cost_cache", $q_start, $cost_row_count);
        }

        // Calculate total COGS
        beaver_data_log("LOOP_START: COGS calculation", array('items' => count($sales_items)));
        $cogs_loop_start = microtime(true);
        $total_cogs = 0;
        foreach($sales_items as $item) {
            $itmcde = $item['itmcde'];
            $unmcde = $item['unmcde'];
            $itmqty = abs((float)$item['itmqty']);
            $trndte = $item['trndte'];
            $sal_recid = $item['recid'];

            // Lookup unit cost from cache (matching trndate_rep_sales_cost.php logic)
            $cost_key = (string)$itmcde . '|' . ($unmcde === null ? '__NULL__' : (string)$unmcde);
            $unit_cost = 0;

            if(isset($cost_cache[$cost_key]) && !empty($cost_cache[$cost_key])) {
                $costs = $cost_cache[$cost_key];

                // Find cost where trndte <= sale date
                if(!empty($trndte)) {
                    foreach($costs as $c) {
                        if(!empty($c['trndte']) && $c['trndte'] <= $trndte) {
                            $unit_cost = (float)$c['untprc'];
                            break;
                        }
                    }
                }

                // Fallback: find cost where recid < sale recid
                if($unit_cost == 0 && !empty($sal_recid)) {
                    foreach($costs as $c) {
                        if($c['recid'] < $sal_recid) {
                            $unit_cost = (float)$c['untprc'];
                            break;
                        }
                    }
                }

                // Last fallback: return latest cost
                if($unit_cost == 0 && !empty($costs)) {
                    $unit_cost = (float)$costs[0]['untprc'];
                }
            }

            $total_cogs += $unit_cost * $itmqty;
        }
        beaver_data_log("LOOP_END: COGS calculation", array('cogs_loop_ms' => round((microtime(true) - $cogs_loop_start) * 1000, 2)));

        $net_profit = $total_sales - $total_cogs - $total_commission;

        beaver_data_log("FUNC_END: dashboard_get_best_salesman_with_cogs", array(
            'func_total_ms' => round((microtime(true) - $func_start) * 1000, 2)
        ));

        return array(
            'salesman_id' => $salesman_id,
            'salesman_name' => $best_salesman['salesman_name'],
            'total_sales' => $total_sales,
            'cogs' => $total_cogs,
            'commission_rate' => $commission_rate,
            'total_commission' => $total_commission,
            'net_profit' => $net_profit
        );
    }

    // Query 10: Critical inventory
    beaver_data_log("QUERY_START: critical_inventory");
    $q_start = microtime(true);
    $critical_inventory_sql = "
        SELECT
            itemfile.itmdsc,
            COALESCE(stock.current_stock, 0) AS current_stock,
            COALESCE(itemfile.critical_qty, 0) AS critical_qty,
            (COALESCE(stock.current_stock, 0) - COALESCE(itemfile.critical_qty, 0)) AS stock_gap
        FROM itemfile
        LEFT JOIN (
            {$stock_subquery}
        ) AS stock
            ON stock.itmcde = itemfile.itmcde
        ORDER BY stock_gap ASC, itemfile.itmdsc ASC
        LIMIT 5
    ";
    $critical_inventory_stmt = $link->prepare($critical_inventory_sql);
    $critical_inventory_stmt->execute();
    $critical_inventory_items = $critical_inventory_stmt->fetchAll(PDO::FETCH_ASSOC);
    beaver_data_query("critical_inventory", $q_start, count($critical_inventory_items));

    foreach($critical_inventory_items as $critical_index => $critical_item){
        $critical_inventory_items[$critical_index]['current_stock'] = (float)$critical_item['current_stock'];
        $critical_inventory_items[$critical_index]['critical_qty'] = (float)$critical_item['critical_qty'];
    }

    // Query 11: Sales trend by month
    beaver_data_log("QUERY_START: sales_trend");
    $q_start = microtime(true);
    $sales_trend_sql = "
        SELECT
            MONTH(tranfile1.trndte) AS month_number,
            COALESCE(SUM(tranfile2.extprc), 0) AS total_sales
        FROM tranfile1
        INNER JOIN tranfile2 ON tranfile1.docnum = tranfile2.docnum
        WHERE tranfile1.trncde = 'SAL'
          AND tranfile1.trndte IS NOT NULL
          AND YEAR(tranfile1.trndte) = ?
        GROUP BY MONTH(tranfile1.trndte)
        ORDER BY MONTH(tranfile1.trndte) ASC
    ";
    $sales_trend_stmt = $link->prepare($sales_trend_sql);
    $sales_trend_stmt->execute(array($trend_year));
    $sales_trend_rows = $sales_trend_stmt->fetchAll(PDO::FETCH_ASSOC);
    beaver_data_query("sales_trend", $q_start, count($sales_trend_rows));

    $sales_by_month = array(
        1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0,
        7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0
    );

    foreach($sales_trend_rows as $trend_row){
        $month_number = (int)$trend_row['month_number'];
        if(isset($sales_by_month[$month_number])){
            $sales_by_month[$month_number] = (float)$trend_row['total_sales'];
        }
    }

    $trend_labels = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $trend_values = array_values($sales_by_month);
    $trend_total = array_sum($trend_values);
    $minimum_trend_year = 1900;
    $maximum_trend_year = (int)date('Y') + 10;

    // Query 12: Available years
    beaver_data_log("QUERY_START: year_options");
    $q_start = microtime(true);
    $year_options_stmt = $link->prepare("
        SELECT DISTINCT YEAR(trndte) AS trend_year
        FROM tranfile1
        WHERE trncde = 'SAL'
          AND trndte IS NOT NULL
          AND YEAR(trndte) BETWEEN ? AND ?
        ORDER BY trend_year DESC
    ");
    $year_options_stmt->execute(array($minimum_trend_year, $maximum_trend_year));
    $available_years = array();

    while($year_row = $year_options_stmt->fetch(PDO::FETCH_ASSOC)){
        if(dashboard_is_valid_trend_year($year_row['trend_year'])){
            $available_years[] = (string)$year_row['trend_year'];
        }
    }
    beaver_data_query("year_options", $q_start, count($available_years));

    if(empty($available_years)){
        $available_years[] = (string)$trend_year;
    }

    // Expenses breakdown by expense type for previous month and current month-to-date
    // Basis date: use to_date if valid, otherwise use today
    $expenses_basis_date = ($to_date !== '') ? $to_date : date('Y-m-d');
    $basis_datetime = new DateTime($expenses_basis_date);

    // Current/selected month-to-date: first day of basis month to basis date
    $current_month_start = (clone $basis_datetime)->modify('first day of this month')->format('Y-m-d');
    $current_month_end = $basis_datetime->format('Y-m-d');

    // Previous full month: first day to last day of the month before basis month
    $prev_month_datetime = (clone $basis_datetime)->modify('first day of last month');
    $prev_month_start = $prev_month_datetime->format('Y-m-d');
    $prev_month_end = $prev_month_datetime->modify('last day of this month')->format('Y-m-d');

    beaver_data_log("CHECKPOINT: Starting expense breakdown queries");

    // Query 13: Previous month expenses by type
    beaver_data_log("QUERY_START: prev_month_expenses");
    $q_start = microtime(true);
    $prev_month_expenses_sql = "
        SELECT
            expensetypefile.expense_dsc,
            COALESCE(SUM(expensefile1.trntot), 0) AS total_amount
        FROM expensefile1
        INNER JOIN expensetypefile ON expensetypefile.expense_cde = expensefile1.expense_cde
        WHERE expensefile1.trndte >= ?
          AND expensefile1.trndte <= ?
        GROUP BY expensetypefile.expense_dsc
        ORDER BY total_amount DESC, expensetypefile.expense_dsc ASC
    ";
    $prev_month_expenses_stmt = $link->prepare($prev_month_expenses_sql);
    $prev_month_expenses_stmt->execute(array($prev_month_start, $prev_month_end));
    $prev_month_expenses_rows = $prev_month_expenses_stmt->fetchAll(PDO::FETCH_ASSOC);
    beaver_data_query("prev_month_expenses", $q_start, count($prev_month_expenses_rows));

    $prev_month_expenses = array();
    $prev_month_total = 0;
    foreach($prev_month_expenses_rows as $row){
        $amount = (float)$row['total_amount'];
        $prev_month_expenses[] = array(
            'expense_dsc' => $row['expense_dsc'],
            'total_amount' => $amount
        );
        $prev_month_total += $amount;
    }

    // Query 14: Current month expenses by type
    beaver_data_log("QUERY_START: current_month_expenses");
    $q_start = microtime(true);
    $current_month_expenses_sql = "
        SELECT
            expensetypefile.expense_dsc,
            COALESCE(SUM(expensefile1.trntot), 0) AS total_amount
        FROM expensefile1
        INNER JOIN expensetypefile ON expensetypefile.expense_cde = expensefile1.expense_cde
        WHERE expensefile1.trndte >= ?
          AND expensefile1.trndte <= ?
        GROUP BY expensetypefile.expense_dsc
        ORDER BY total_amount DESC, expensetypefile.expense_dsc ASC
    ";
    $current_month_expenses_stmt = $link->prepare($current_month_expenses_sql);
    $current_month_expenses_stmt->execute(array($current_month_start, $current_month_end));
    $current_month_expenses_rows = $current_month_expenses_stmt->fetchAll(PDO::FETCH_ASSOC);
    beaver_data_query("current_month_expenses", $q_start, count($current_month_expenses_rows));

    $current_month_expenses = array();
    $current_month_total = 0;
    foreach($current_month_expenses_rows as $row){
        $amount = (float)$row['total_amount'];
        $current_month_expenses[] = array(
            'expense_dsc' => $row['expense_dsc'],
            'total_amount' => $amount
        );
        $current_month_total += $amount;
    }

    // Format date ranges for display
    $prev_month_start_display = date('F d', strtotime($prev_month_start));
    $prev_month_end_display = date('F d, Y', strtotime($prev_month_end));
    $current_month_start_display = date('F d', strtotime($current_month_start));
    $current_month_end_display = date('F d, Y', strtotime($current_month_end));

    // Format dates for URL parameters (mm/dd/yyyy format)
    $prev_month_start_url = date('m/d/Y', strtotime($prev_month_start));
    $prev_month_end_url = date('m/d/Y', strtotime($prev_month_end));
    $current_month_start_url = date('m/d/Y', strtotime($current_month_start));
    $current_month_end_url = date('m/d/Y', strtotime($current_month_end));

    // Best salesman comparison (calls function with multiple queries each)
    beaver_data_log("CHECKPOINT: Starting best_salesman_comparison calls (2 function calls, each with multiple queries)");

    // Get best salesman for previous month
    $best_salesman_prev_month = dashboard_get_best_salesman_with_cogs($link, $prev_month_start, $prev_month_end, $pcs_unmcde);

    // Get best salesman for current month-to-date
    $best_salesman_current_month = dashboard_get_best_salesman_with_cogs($link, $current_month_start, $current_month_end, $pcs_unmcde);

    beaver_data_log("CHECKPOINT: All queries complete, preparing response");

    $best_salesman_comparison = array(
        'prev_month' => array(
            'start_date' => $prev_month_start,
            'end_date' => $prev_month_end,
            'start_date_url' => $prev_month_start_url,
            'end_date_url' => $prev_month_end_url,
            'date_range_display' => $prev_month_start_display . ' - ' . $prev_month_end_display,
            'salesman' => $best_salesman_prev_month
        ),
        'current_month' => array(
            'start_date' => $current_month_start,
            'end_date' => $current_month_end,
            'start_date_url' => $current_month_start_url,
            'end_date_url' => $current_month_end_url,
            'date_range_display' => $current_month_start_display . ' - ' . $current_month_end_display,
            'salesman' => $best_salesman_current_month
        )
    );

    $expenses_breakdown = array(
        'prev_month' => array(
            'start_date' => $prev_month_start,
            'end_date' => $prev_month_end,
            'start_date_url' => $prev_month_start_url,
            'end_date_url' => $prev_month_end_url,
            'date_range_display' => $prev_month_start_display . ' - ' . $prev_month_end_display,
            'expenses' => $prev_month_expenses,
            'total' => (float)$prev_month_total
        ),
        'current_month' => array(
            'start_date' => $current_month_start,
            'end_date' => $current_month_end,
            'start_date_url' => $current_month_start_url,
            'end_date_url' => $current_month_end_url,
            'date_range_display' => $current_month_start_display . ' - ' . $current_month_end_display,
            'expenses' => $current_month_expenses,
            'total' => (float)$current_month_total
        )
    );

    dashboard_respond(array(
        'success' => true,
        'filters' => array(
            'from_date' => $from_date,
            'to_date' => $to_date,
            'from_date_display' => dashboard_format_date($from_date, ''),
            'to_date_display' => dashboard_format_date($to_date, ''),
            'trend_year' => (string)$trend_year,
            'range_label' => $range_label
        ),
        'metrics' => array(
            'current_stock_valuation' => (float)$stock_valuation_total,
            'net_profit' => (float)$net_profit_value,
            'net_profit_breakdown' => array(
                'sales_amount' => (float)$total_sal,
                'purchases' => (float)$total_pur,
                'sales_return' => (float)$total_srt,
                'adjustments' => (float)$total_adj,
                'expenses' => (float)$total_expenses
            ),
            'total_skus' => (int)$total_skus
        ),
        'best_selling_items' => $best_selling_items,
        'slow_moving_items' => $slow_moving_items,
        'best_salesman' => $best_salesman ?: null,
        'critical_inventory_items' => $critical_inventory_items,
        'sales_trend' => array(
            'labels' => $trend_labels,
            'values' => $trend_values,
            'total' => (float)$trend_total
        ),
        'available_years' => $available_years,
        'expenses_breakdown' => $expenses_breakdown,
        'best_salesman_comparison' => $best_salesman_comparison
    ));
}catch(Exception $exception){
    beaver_data_log("EXCEPTION CAUGHT", array(
        'message' => substr($exception->getMessage(), 0, 200),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ));
    dashboard_respond(array(
        'success' => false,
        'message' => 'Dashboard query failed.'
    ), 500);
}

beaver_data_log("=== GEN_DASHBOARD_DATA.PHP END (should not reach here if dashboard_respond was called) ===");
