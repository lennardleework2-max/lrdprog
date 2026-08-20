<?php
/**
 * =============================================================================
 * BEAVER METHOD DEBUG SYSTEM - TEMPORARY DIAGNOSTIC TRACING
 * =============================================================================
 * Purpose: Identify where the live gen_dashboard.php gets stuck loading
 *
 * HOW TO ENABLE:
 *   Option 1: Set $_GET['beaver_debug'] = 'YOUR_SECRET_KEY' in URL
 *   Option 2: Create a file named 'beaver_debug_enabled.flag' in this directory
 *   Option 3: Set BEAVER_DEBUG_ENABLED constant to true below
 *
 * LOG LOCATION:
 *   - Primary: PHP error_log (check your php.ini for error_log path)
 *   - On XAMPP Windows: Usually xampp/php/logs/php_error_log or xampp/apache/logs/error.log
 *   - On Linux: Usually /var/log/apache2/error.log or /var/log/php/error.log
 *   - Also writes to: beaver_debug_logs/beaver_YYYYMMDD.log (if directory is writable)
 *
 * HOW TO READ LOGS:
 *   grep "BEAVER" /path/to/error.log | grep "REQ_ID_HERE"
 *
 * TO REMOVE: Delete everything between "BEAVER METHOD DEBUG SYSTEM" comments
 * =============================================================================
 */

// === BEAVER CONFIG START ===
define('BEAVER_DEBUG_ENABLED', false);  // Set to true to force enable
define('BEAVER_DEBUG_SECRET', 'beaver_diag_2024');  // Secret key for URL activation
define('BEAVER_LOG_TO_FILE', true);  // Also log to dedicated file
define('BEAVER_LOG_DIR', __DIR__ . '/beaver_debug_logs');
// === BEAVER CONFIG END ===

// Generate unique request ID for this page load
$BEAVER_REQUEST_ID = 'BVR_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
$BEAVER_START_TIME = microtime(true);
$BEAVER_START_MEMORY = memory_get_usage(true);
$BEAVER_CHECKPOINT_COUNT = 0;
$BEAVER_ENABLED = false;

// Determine if debug mode should be active
if (BEAVER_DEBUG_ENABLED) {
    $BEAVER_ENABLED = true;
} elseif (isset($_GET['beaver_debug']) && $_GET['beaver_debug'] === BEAVER_DEBUG_SECRET) {
    $BEAVER_ENABLED = true;
} elseif (file_exists(__DIR__ . '/beaver_debug_enabled.flag')) {
    $BEAVER_ENABLED = true;
}

/**
 * Beaver Method logging function
 * Logs checkpoint with timing, memory, and context - NEVER outputs to browser
 */
function beaver_log($checkpoint_name, $extra_data = array()) {
    global $BEAVER_REQUEST_ID, $BEAVER_START_TIME, $BEAVER_START_MEMORY, $BEAVER_CHECKPOINT_COUNT, $BEAVER_ENABLED;

    if (!$BEAVER_ENABLED) {
        return;
    }

    $BEAVER_CHECKPOINT_COUNT++;
    $elapsed_ms = round((microtime(true) - $BEAVER_START_TIME) * 1000, 2);
    $current_memory = memory_get_usage(true);
    $memory_mb = round($current_memory / 1024 / 1024, 2);
    $memory_delta_kb = round(($current_memory - $BEAVER_START_MEMORY) / 1024, 2);
    $peak_memory_mb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

    $log_entry = sprintf(
        "[BEAVER] [%s] [#%03d] [+%sms] [Mem: %sMB / Peak: %sMB / Delta: %sKB] %s",
        $BEAVER_REQUEST_ID,
        $BEAVER_CHECKPOINT_COUNT,
        str_pad($elapsed_ms, 8, ' ', STR_PAD_LEFT),
        str_pad($memory_mb, 6, ' ', STR_PAD_LEFT),
        str_pad($peak_memory_mb, 6, ' ', STR_PAD_LEFT),
        str_pad($memory_delta_kb, 8, ' ', STR_PAD_LEFT),
        $checkpoint_name
    );

    // Add extra data if provided (sanitized)
    if (!empty($extra_data)) {
        $safe_extra = array();
        foreach ($extra_data as $key => $value) {
            // Sanitize: don't log sensitive fields
            $lower_key = strtolower($key);
            if (strpos($lower_key, 'password') !== false ||
                strpos($lower_key, 'token') !== false ||
                strpos($lower_key, 'secret') !== false ||
                strpos($lower_key, 'credential') !== false) {
                $safe_extra[$key] = '[REDACTED]';
            } else {
                // Truncate long values
                $str_val = is_array($value) ? json_encode($value) : (string)$value;
                $safe_extra[$key] = strlen($str_val) > 100 ? substr($str_val, 0, 100) . '...' : $str_val;
            }
        }
        $log_entry .= ' | Data: ' . json_encode($safe_extra);
    }

    // Log to PHP error log (primary)
    error_log($log_entry);

    // Also log to dedicated file if enabled
    if (BEAVER_LOG_TO_FILE) {
        beaver_log_to_file($log_entry);
    }
}

/**
 * Log to dedicated file (protected from web access)
 */
function beaver_log_to_file($message) {
    $log_dir = BEAVER_LOG_DIR;

    // Create log directory if it doesn't exist
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0750, true);
        // Create .htaccess to prevent web access
        @file_put_contents($log_dir . '/.htaccess', "Deny from all\n");
    }

    if (is_writable($log_dir) || is_writable(dirname($log_dir))) {
        $log_file = $log_dir . '/beaver_' . date('Ymd') . '.log';
        @file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
    }
}

/**
 * Log SQL query execution with timing
 */
function beaver_log_query($query_label, $start_time, $row_count = null, $error = null) {
    $duration_ms = round((microtime(true) - $start_time) * 1000, 2);
    $extra = array('duration_ms' => $duration_ms);

    if ($row_count !== null) {
        $extra['rows'] = $row_count;
    }

    if ($error !== null) {
        $extra['error'] = substr($error, 0, 200);  // Truncate error message
    }

    $status = $error ? 'FAILED' : 'OK';
    beaver_log("QUERY [{$status}]: {$query_label}", $extra);
}

/**
 * Check database structure (READ-ONLY)
 * Only runs SHOW TABLES, SHOW COLUMNS, SHOW INDEX - no modifications
 */
function beaver_check_db_structure($link, $tables_to_check) {
    global $BEAVER_ENABLED;

    if (!$BEAVER_ENABLED) {
        return;
    }

    beaver_log("DB_STRUCTURE_CHECK: Starting read-only structure verification");

    // Get list of existing tables
    $existing_tables = array();
    try {
        $start = microtime(true);
        $stmt = $link->query("SHOW TABLES");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $table) {
            $existing_tables[$table] = true;
        }
        beaver_log_query("SHOW TABLES", $start, count($rows));
    } catch (Exception $e) {
        beaver_log("DB_STRUCTURE_CHECK: Failed to list tables", array('error' => $e->getMessage()));
        return;
    }

    // Check each required table
    foreach ($tables_to_check as $table => $columns) {
        if (!isset($existing_tables[$table])) {
            beaver_log("DB_STRUCTURE_CHECK: MISSING TABLE - {$table}");
            continue;
        }

        // Check columns exist
        try {
            $start = microtime(true);
            $stmt = $link->prepare("SHOW COLUMNS FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "`");
            $stmt->execute();
            $existing_cols = array();
            while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_cols[$col['Field']] = true;
            }

            $missing_cols = array();
            foreach ($columns as $col) {
                if (!isset($existing_cols[$col])) {
                    $missing_cols[] = $col;
                }
            }

            if (!empty($missing_cols)) {
                beaver_log("DB_STRUCTURE_CHECK: MISSING COLUMNS in {$table}", array('columns' => implode(', ', $missing_cols)));
            }
        } catch (Exception $e) {
            beaver_log("DB_STRUCTURE_CHECK: Failed to check columns for {$table}", array('error' => $e->getMessage()));
        }
    }

    beaver_log("DB_STRUCTURE_CHECK: Completed");
}

// === BEAVER CHECKPOINT: SCRIPT START ===
beaver_log("=== GEN_DASHBOARD.PHP SCRIPT START ===", array(
    'php_version' => PHP_VERSION,
    'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
    'request_method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown',
    'query_string' => isset($_SERVER['QUERY_STRING']) ? substr($_SERVER['QUERY_STRING'], 0, 100) : ''
));
// === BEAVER METHOD DEBUG SYSTEM - END OF HEADER ===

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

beaver_log("CHECKPOINT: Before main_header.php include");
$beaver_include_start = microtime(true);

require "includes/main_header.php";

beaver_log("CHECKPOINT: After main_header.php include", array(
    'include_duration_ms' => round((microtime(true) - $beaver_include_start) * 1000, 2),
    'session_active' => isset($_SESSION['userdesc']) ? 'yes' : 'no'
));

// Check critical database tables exist (READ-ONLY)
if ($BEAVER_ENABLED && isset($link)) {
    beaver_check_db_structure($link, array(
        'tranfile1' => array('docnum', 'trncde', 'trndte', 'salesman_id'),
        'tranfile2' => array('docnum', 'itmcde', 'extprc', 'stkqty', 'unmcde', 'untprc', 'recid'),
        'itemfile' => array('itmcde', 'itmdsc', 'critical_qty'),
        'itemunitmeasurefile' => array('unmcde', 'unmdsc'),
        'mf_salesman' => array('salesman_id', 'salesman_name', 'commission'),
        'expensefile1' => array('trndte', 'trntot', 'expense_cde'),
        'expensetypefile' => array('expense_cde', 'expense_dsc'),
        'syspar' => array('landing_page', 'system_name')
    ));
}

beaver_log("CHECKPOINT: Before dashboard helper functions");

function dashboard_display_date($value)
{
    $value = trim((string)$value);

    if($value === ''){
        return '';
    }

    $formats = array('Y-m-d', 'm/d/Y');

    foreach($formats as $format){
        $date = DateTime::createFromFormat($format, $value);
        if($date && $date->format($format) === $value){
            return $date->format('m/d/Y');
        }
    }

    return '';
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

$initial_from_date = isset($_GET['from_date']) ? dashboard_display_date($_GET['from_date']) : '';
$initial_to_date = isset($_GET['to_date']) ? dashboard_display_date($_GET['to_date']) : '';
$initial_trend_year = date('Y');

if(isset($_GET['trend_year']) && dashboard_is_valid_trend_year($_GET['trend_year'])){
    $initial_trend_year = $_GET['trend_year'];
}

beaver_log("CHECKPOINT: URL parameters processed", array(
    'from_date' => !empty($initial_from_date) ? 'set' : 'empty',
    'to_date' => !empty($initial_to_date) ? 'set' : 'empty',
    'trend_year' => $initial_trend_year
));

beaver_log("CHECKPOINT: Starting HTML output (page render begins)");
?>

<style>
    .dashboard-shell{
        padding-bottom: 1.5rem;
    }

    .dashboard-page{
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.95), rgba(243, 244, 246, 0.95) 45%, rgba(229, 231, 235, 0.95) 100%),
            linear-gradient(180deg, #f8f9fa 0%, #eef1f4 100%);
        border-radius: 1.2rem;
        padding: 1.25rem;
        min-height: calc(100vh - 130px);
    }

    .dashboard-toolbar,
    .dashboard-card{
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(33, 37, 41, 0.08);
        border-radius: 1.1rem;
        box-shadow: 0 16px 40px rgba(33, 37, 41, 0.08);
    }

    .dashboard-toolbar{
        padding: 1.1rem 1.15rem;
        margin-bottom: 1rem;
        position: relative;
        overflow: hidden;
    }

    .dashboard-toolbar:before{
        content: "";
        position: absolute;
        top: -35px;
        right: -35px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(33, 37, 41, 0.08) 0%, rgba(33, 37, 41, 0) 72%);
        pointer-events: none;
    }

    .dashboard-title-wrap{
        position: relative;
        z-index: 1;
    }

    .dashboard-eyebrow{
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: rgba(33, 37, 41, 0.06);
        color: #343a40;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
    }

    .dashboard-title{
        font-size: 2rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .dashboard-subtitle{
        color: #6b7280;
        font-size: 0.95rem;
        margin: 0.4rem 0 0;
        max-width: 38rem;
    }

    .dashboard-filter-label{
        font-size: 0.78rem;
        font-weight: 700;
        color: #4b5563;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .dashboard-filter-input{
        height: 2.95rem;
        border-radius: 0.85rem;
        border-color: rgba(33, 37, 41, 0.12);
        box-shadow: none;
        background: #ffffff;
        font-weight: 600;
        color: #111827;
    }

    .dashboard-filter-input:focus{
        border-color: rgba(33, 37, 41, 0.32);
        box-shadow: 0 0 0 0.12rem rgba(33, 37, 41, 0.08);
    }

    .dashboard-filter-actions{
        display: flex;
        gap: 0.6rem;
    }

    .dashboard-filter-actions .btn{
        height: 2.95rem;
        border-radius: 0.85rem;
        font-weight: 700;
    }

    .dashboard-grid-summary{
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .dashboard-grid-detail{
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .dashboard-grid-bottom{
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .dashboard-grid-expenses{
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .dashboard-expenses-card{
        padding: 1rem 1.05rem 1.15rem;
    }

    .dashboard-expenses-columns{
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }

    .dashboard-expenses-column{
        border: 1px solid rgba(33, 37, 41, 0.08);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(248, 249, 250, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
        padding: 0.9rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .dashboard-expenses-column:hover{
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(33, 37, 41, 0.12);
        border-color: rgba(33, 37, 41, 0.14);
        background: rgba(108, 117, 125, 0.08);
    }

    .dashboard-expenses-column:focus{
        outline: 2px solid rgba(33, 37, 41, 0.3);
        outline-offset: 2px;
    }

    .dashboard-expenses-total{
        margin-top: 0.75rem;
        padding-top: 0.65rem;
        border-top: 1px solid rgba(33, 37, 41, 0.12);
    }

    .dashboard-expenses-total .dashboard-stat-row{
        font-weight: 700;
    }

    .dashboard-expenses-total .dashboard-stat-row span{
        color: #111827;
    }

    .dashboard-expenses-column-header{
        font-size: 0.78rem;
        font-weight: 700;
        color: #4b5563;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.75rem;
        padding-bottom: 0.55rem;
        border-bottom: 1px solid rgba(33, 37, 41, 0.08);
    }

    .dashboard-card{
        padding: 1rem 1.05rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .dashboard-card:before{
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
    }

    .dashboard-card > *{
        position: relative;
        z-index: 1;
    }

    .dashboard-kpi-card{
        padding-top: 1.1rem;
        min-height: 196px;
    }

    .dashboard-card-head{
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.8rem;
        margin-bottom: 1rem;
    }

    .dashboard-card-title{
        font-size: 0.78rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.3rem;
    }

    .dashboard-card-tag{
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.55rem;
        border-radius: 999px;
        background: rgba(33, 37, 41, 0.06);
        color: #495057;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .dashboard-card-value{
        font-size: 2rem;
        line-height: 1.02;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.03em;
        word-break: break-word;
    }

    .dashboard-card-meta{
        font-size: 0.92rem;
        color: #6b7280;
        margin-top: 0.6rem;
        line-height: 1.45;
    }

    .dashboard-kpi-icon,
    .dashboard-section-icon{
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #212529 0%, #5c636a 100%);
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(33, 37, 41, 0.18);
        flex-shrink: 0;
    }

    .dashboard-kpi-icon i{
        font-size: 1.1rem;
    }

    .dashboard-section-header{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .dashboard-section-title-wrap{
        display: flex;
        align-items: center;
        gap: 0.8rem;
        min-width: 0;
    }

    .dashboard-section-title-group{
        min-width: 0;
    }

    .dashboard-section-title{
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.01em;
    }

    .dashboard-section-subtitle{
        color: #6b7280;
        font-size: 0.86rem;
        margin-top: 0.18rem;
    }

    .dashboard-section-icon{
        width: 2.7rem;
        height: 2.7rem;
        border-radius: 0.95rem;
    }

    .dashboard-section-icon i{
        font-size: 1rem;
    }

    .dashboard-empty{
        color: #6b7280;
        font-size: 0.92rem;
        margin: 0;
        line-height: 1.5;
    }

    .dashboard-stat-list{
        display: grid;
        gap: 0.7rem;
    }

    .dashboard-stat-row{
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        font-size: 0.94rem;
        color: #4b5563;
    }

    .dashboard-stat-row strong{
        color: #111827;
        text-align: right;
    }

    .dashboard-card-value.dashboard-amount-positive,
    .dashboard-stat-row strong.dashboard-amount-positive{
        color: #198754 !important;
    }

    .dashboard-card-value.dashboard-amount-negative,
    .dashboard-stat-row strong.dashboard-amount-negative{
        color: #dc3545 !important;
    }

    .dashboard-net-profit-breakdown{
        margin-top: 0.8rem;
        display: grid;
        gap: 0.45rem;
    }

    .dashboard-net-profit-heading{
        font-size: 0.78rem;
        font-weight: 700;
        color: #4b5563;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .dashboard-net-profit-breakdown .dashboard-stat-list{
        gap: 0.4rem;
    }

    .dashboard-net-profit-breakdown .dashboard-stat-row{
        font-size: 0.88rem;
        gap: 0.75rem;
    }

    .dashboard-net-profit-formula{
        margin-top: 0;
        font-size: 0.88rem;
        word-break: break-word;
    }

    .dashboard-net-profit-range{
        margin-top: 0.15rem;
        font-size: 0.82rem;
    }

    .dashboard-salesman-grid{
        display: grid;
        gap: 0.7rem;
    }

    .dashboard-mini-panel-grid{
        display: grid;
        gap: 0.8rem;
    }

    .dashboard-mini-panel{
        border: 1px solid rgba(33, 37, 41, 0.08);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(248, 249, 250, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
        padding: 0.85rem 0.9rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .dashboard-mini-panel-head{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
    }

    .dashboard-mini-panel-title{
        font-size: 0.8rem;
        font-weight: 700;
        color: #4b5563;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .dashboard-mini-panel-rank{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.7rem;
        background: rgba(33, 37, 41, 0.08);
        color: #111827;
    }

    .dashboard-mini-panel-name{
        font-size: 0.95rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.45;
        word-break: break-word;
        margin-bottom: 0.7rem;
    }

    .dashboard-mini-panel-meta{
        display: grid;
        gap: 0.45rem;
    }

    .dashboard-mini-panel-meta div{
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.9rem;
        color: #4b5563;
    }

    .dashboard-mini-panel-meta strong{
        color: #111827;
        text-align: right;
    }

    .dashboard-table-wrap{
        overflow-x: auto;
        margin: 0 -0.1rem;
        padding: 0 0.1rem;
    }

    .dashboard-table{
        width: 100%;
        margin-bottom: 0;
        color: #1f2937;
    }

    .dashboard-table thead th{
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        border-bottom-width: 1px;
        border-bottom-color: rgba(33, 37, 41, 0.1);
        white-space: nowrap;
        padding-top: 0.2rem;
        padding-bottom: 0.7rem;
    }

    .dashboard-table tbody td{
        font-size: 0.92rem;
        vertical-align: middle;
        border-bottom-color: rgba(33, 37, 41, 0.06);
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .dashboard-table tbody tr:last-child td{
        border-bottom: 0;
    }

    .dashboard-critical-note{
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #6b7280;
        font-size: 0.84rem;
        margin-top: 0.85rem;
    }

    .dashboard-card-link{
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .dashboard-card-link:hover{
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(33, 37, 41, 0.12);
        border-color: rgba(33, 37, 41, 0.14);
        background: rgba(108, 117, 125, 0.08);
    }

    .dashboard-chart-card{
        padding-bottom: 0.9rem;
    }

    .dashboard-trend-toolbar{
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .dashboard-trend-select{
        min-width: 120px;
        height: 2.55rem;
        border-radius: 0.8rem;
        border-color: rgba(33, 37, 41, 0.12);
        font-weight: 700;
        box-shadow: none;
    }

    .dashboard-trend-summary{
        display: flex;
        gap: 0.75rem;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.85rem;
        flex-wrap: wrap;
    }

    .dashboard-trend-total{
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.65rem 0.85rem;
        background: rgba(33, 37, 41, 0.05);
        border: 1px solid rgba(33, 37, 41, 0.08);
        border-radius: 0.95rem;
    }

    .dashboard-trend-total i{
        color: #212529;
        font-size: 0.95rem;
    }

    .dashboard-trend-total span{
        display: block;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6b7280;
    }

    .dashboard-trend-total strong{
        font-size: 1.12rem;
        color: #111827;
        letter-spacing: -0.02em;
    }

    .dashboard-chart-shell{
        background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
        border: 1px solid rgba(33, 37, 41, 0.07);
        border-radius: 1rem;
        padding: 0.8rem;
    }

    .dashboard-chart-area{
        position: relative;
    }

    .dashboard-chart{
        width: 100%;
        height: 220px;
        display: block;
    }

    .dashboard-chart-labels{
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 0.2rem;
        margin-top: 0.4rem;
        font-size: 0.7rem;
        color: #6b7280;
        text-align: center;
        user-select: none;
    }

    .dashboard-chart-tooltip{
        position: absolute;
        left: 0;
        top: 0;
        min-width: 120px;
        padding: 0.55rem 0.65rem;
        border-radius: 0.8rem;
        background: rgba(17, 24, 39, 0.96);
        color: #ffffff;
        box-shadow: 0 16px 28px rgba(17, 24, 39, 0.22);
        pointer-events: none;
        opacity: 0;
        transform: translate(-50%, -115%);
        transition: opacity 0.15s ease;
        z-index: 4;
    }

    .dashboard-chart-tooltip.active{
        opacity: 1;
    }

    .dashboard-chart-tooltip-label{
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.72);
        margin-bottom: 0.2rem;
    }

    .dashboard-chart-tooltip-value{
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .dashboard-loading{
        display: none;
        position: sticky;
        top: 0.5rem;
        z-index: 5;
        margin-bottom: 1rem;
        background: #ffffff;
        border: 1px solid rgba(33, 37, 41, 0.1);
        border-radius: 0.95rem;
        padding: 0.9rem 1rem;
        box-shadow: 0 12px 24px rgba(33, 37, 41, 0.08);
    }

    .dashboard-loading.active{
        display: block;
    }

    .dashboard-loading-label{
        display: flex;
        align-items: center;
        gap: 0.55rem;
        font-weight: 700;
        color: #212529;
    }

    .dashboard-loading-bar{
        height: 0.5rem;
        border-radius: 999px;
        overflow: hidden;
        background: #e5e7eb;
        margin-top: 0.55rem;
        position: relative;
    }

    .dashboard-loading-bar span{
        display: block;
        width: 0%;
        height: 100%;
        background: linear-gradient(90deg, #198754 0%, #20c997 100%);
        border-radius: 999px;
        transition: width 0.15s ease-out;
    }

    .dashboard-loading-percent{
        display: inline-block;
        min-width: 3rem;
        text-align: right;
        font-weight: 700;
        color: #198754;
        font-size: 0.95rem;
        margin-left: 0.5rem;
    }

    .dashboard-loading-message{
        margin-top: 0.5rem;
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 500;
        min-height: 1.2em;
        transition: opacity 0.3s ease;
    }

    @media (max-width: 1199.98px){
        .dashboard-grid-summary{
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-grid-summary > .dashboard-expenses-card{
            grid-column: span 2;
        }

        .dashboard-grid-detail{
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px){
        .dashboard-grid-summary{
            grid-template-columns: 1fr;
        }

        .dashboard-grid-summary > .dashboard-expenses-card{
            grid-column: span 1;
        }
    }

    @media (max-width: 767.98px){
        .dashboard-page{
            padding: 0.85rem;
            border-radius: 0.9rem;
        }

        .dashboard-toolbar{
            padding: 0.95rem;
        }

        .dashboard-title{
            font-size: 1.65rem;
        }

        .dashboard-grid-summary,
        .dashboard-grid-detail{
            grid-template-columns: 1fr;
        }

        .dashboard-expenses-columns{
            grid-template-columns: 1fr;
        }

        .dashboard-filter-actions{
            flex-direction: column;
        }

        .dashboard-filter-actions .btn{
            width: 100%;
        }

        .dashboard-card-value{
            font-size: 1.7rem;
        }

        .dashboard-chart{
            height: 205px;
        }

        .dashboard-section-header{
            align-items: flex-start;
        }

        .dashboard-trend-toolbar{
            width: 100%;
        }

        .dashboard-trend-select{
            width: 100%;
        }

        .dashboard-chart-labels{
            font-size: 0.64rem;
        }
    }
</style>

<form id="dashboardFilterForm" method="get" target="_self">
    <table class='big_table dashboard-shell'>
        <tr colspan="1">
            <td colspan="1" class='td_bl'>
                <?php include 'includes/main_menu.php'; ?>
            </td>

            <td colspan="1" class="td_br" id="td_br">
                <div class="container-fluid pt-2 main_br_div">
                    <div class="dashboard-page">
                        <div class="dashboard-toolbar">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-xl-5">
                                    <div class="dashboard-title-wrap">
                                        <div class="dashboard-eyebrow">
                                            <i class="fas fa-chart-pie"></i>
                                            Executive Dashboard
                                        </div>
                                        <h1 class="dashboard-title">Business Overview</h1>
                                        <p class="dashboard-subtitle">A clean operational snapshot of stock, sales performance, critical inventory, and month-by-month movement.</p>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-2">
                                    <label for="from_date" class="form-label dashboard-filter-label mb-2">From Date</label>
                                    <input
                                        type="text"
                                        class="form-control dashboard-filter-input date_picker"
                                        id="from_date"
                                        name="from_date"
                                        placeholder="mm/dd/yyyy"
                                        autocomplete="off"
                                        readonly
                                        value="<?php echo htmlspecialchars($initial_from_date, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                </div>

                                <div class="col-12 col-sm-6 col-xl-2">
                                    <label for="to_date" class="form-label dashboard-filter-label mb-2">To Date</label>
                                    <input
                                        type="text"
                                        class="form-control dashboard-filter-input date_picker"
                                        id="to_date"
                                        name="to_date"
                                        placeholder="mm/dd/yyyy"
                                        autocomplete="off"
                                        readonly
                                        value="<?php echo htmlspecialchars($initial_to_date, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                </div>

                                <div class="col-12 col-xl-3">
                                    <label class="form-label dashboard-filter-label mb-2 d-block">Actions</label>
                                    <div class="dashboard-filter-actions">
                                        <button type="submit" class="btn btn-dark">Apply Filters</button>
                                        <button type="button" class="btn btn-outline-secondary" id="dashboardClearBtn">Clear</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-loading" id="dashboardLoading">
                            <div class="dashboard-loading-label">
                                <i class="fas fa-sync-alt fa-spin"></i>
                                Loading dashboard data...
                                <span class="dashboard-loading-percent" id="dashboardLoadingPercent">0%</span>
                            </div>
                            <div class="dashboard-loading-bar"><span id="dashboardLoadingBarFill"></span></div>
                            <div class="dashboard-loading-message" id="dashboardLoadingMessage">Preparing your dashboard...</div>
                        </div>

                        <div class="alert alert-danger d-none" id="dashboardError"></div>

                        <div class="dashboard-grid-summary">
                            <div class="dashboard-card dashboard-kpi-card">
                                <div class="dashboard-card-head">
                                    <div>
                                        <div class="dashboard-card-title">Current Stock Valuation</div>
                                        <div class="dashboard-card-tag"><i class="fas fa-layer-group"></i>Inventory</div>
                                    </div>
                                    <div class="dashboard-kpi-icon"><i class="fas fa-coins"></i></div>
                                </div>
                                <div class="dashboard-card-value" id="currentStockValuationValue">0.00</div>

                                <div class="dashboard-card-head" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(33, 37, 41, 0.08);">
                                    <div>
                                        <div class="dashboard-card-title">Total SKUs</div>
                                        <div class="dashboard-card-tag"><i class="fas fa-th-large"></i>Catalog</div>
                                    </div>
                                    <div class="dashboard-kpi-icon"><i class="fas fa-boxes"></i></div>
                                </div>
                                <div class="dashboard-card-value" id="totalSkusValue">0</div>
                            </div>

                            <div class="dashboard-card dashboard-kpi-card">
                                <div class="dashboard-card-head">
                                    <div>
                                        <div class="dashboard-card-title">Net Profit</div>
                                        <div class="dashboard-card-tag"><i class="fas fa-wallet"></i>Performance</div>
                                    </div>
                                    <div class="dashboard-kpi-icon"><i class="fas fa-chart-line"></i></div>
                                </div>
                                <div class="dashboard-card-value" id="netProfitValue">&#8369;0.00</div>
                                <div class="dashboard-net-profit-breakdown">
                                    <div class="dashboard-net-profit-heading">Breakdown:</div>
                                    <div class="dashboard-stat-list">
                                        <div class="dashboard-stat-row"><span>Sales Amount</span><strong id="netProfitSalesAmount">&#8369;0.00</strong></div>
                                        <div class="dashboard-stat-row"><span>Purchases</span><strong id="netProfitPurchases">&#8369;0.00</strong></div>
                                        <div class="dashboard-stat-row"><span>Sales Return</span><strong id="netProfitSalesReturn">&#8369;0.00</strong></div>
                                        <div class="dashboard-stat-row"><span>Adjustments</span><strong id="netProfitAdjustments">&#8369;0.00</strong></div>
                                        <div class="dashboard-stat-row"><span>Expenses</span><strong id="netProfitExpenses">&#8369;0.00</strong></div>
                                    </div>
                                    <div class="dashboard-net-profit-heading">Calculation:</div>
                                    <div class="dashboard-card-meta dashboard-net-profit-formula" id="netProfitFormula">&#8369;0.00 - &#8369;0.00 + &#8369;0.00 + &#8369;0.00 - &#8369;0.00 = &#8369;0.00</div>
                                    <div class="dashboard-card-meta dashboard-net-profit-range" id="netProfitMeta">All transaction dates.</div>
                                </div>
                            </div>

                            <div class="dashboard-card dashboard-expenses-card">
                                <div class="dashboard-section-header">
                                    <div class="dashboard-section-title-wrap">
                                        <div class="dashboard-section-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                        <div class="dashboard-section-title-group">
                                            <h2 class="dashboard-section-title">Expenses</h2>
                                            <div class="dashboard-section-subtitle">Comparison of previous month vs current month-to-date expenses by type.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dashboard-expenses-columns" id="expensesColumnsBlock">
                                    <div class="dashboard-expenses-column" id="expensesPrevMonthCard" tabindex="0" role="link" aria-label="View previous month expenses">
                                        <div class="dashboard-expenses-column-header" id="expensesPrevMonthHeader">Previous Month</div>
                                        <div id="expensesPrevMonthBlock" class="dashboard-empty">No expenses found.</div>
                                    </div>
                                    <div class="dashboard-expenses-column" id="expensesCurrentMonthCard" tabindex="0" role="link" aria-label="View current month expenses">
                                        <div class="dashboard-expenses-column-header" id="expensesCurrentMonthHeader">Current Month</div>
                                        <div id="expensesCurrentMonthBlock" class="dashboard-empty">No expenses found.</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="dashboard-grid-expenses">
                            <div class="dashboard-card dashboard-expenses-card">
                                <div class="dashboard-section-header">
                                    <div class="dashboard-section-title-wrap">
                                        <div class="dashboard-section-icon"><i class="fas fa-award"></i></div>
                                        <div class="dashboard-section-title-group">
                                            <h2 class="dashboard-section-title">Best Salesman</h2>
                                            <div class="dashboard-section-subtitle">Comparison of previous month vs current month-to-date top salesman performance.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dashboard-expenses-columns" id="bestSalesmanColumnsBlock">
                                    <div class="dashboard-expenses-column" id="bestSalesmanPrevMonthCard" tabindex="0" role="link" aria-label="View previous month best salesman report">
                                        <div class="dashboard-expenses-column-header" id="bestSalesmanPrevMonthHeader">Previous Month</div>
                                        <div id="bestSalesmanPrevMonthBlock" class="dashboard-empty">No salesman sales found.</div>
                                    </div>
                                    <div class="dashboard-expenses-column" id="bestSalesmanCurrentMonthCard" tabindex="0" role="link" aria-label="View current month best salesman report">
                                        <div class="dashboard-expenses-column-header" id="bestSalesmanCurrentMonthHeader">Current Month</div>
                                        <div id="bestSalesmanCurrentMonthBlock" class="dashboard-empty">No salesman sales found.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-grid-detail">
                            <div class="dashboard-card">
                                <div class="dashboard-section-header">
                                    <div class="dashboard-section-title-wrap">
                                        <div class="dashboard-section-icon"><i class="fas fa-medal"></i></div>
                                        <div class="dashboard-section-title-group">
                                            <h2 class="dashboard-section-title">Best Selling Item</h2>
                                            <div class="dashboard-section-subtitle">Separate rankings by quantity sold and by total sales.</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="bestSellingItemBlock" class="dashboard-empty">No sales records found.</div>
                            </div>

                            <div class="dashboard-card dashboard-card-link" id="slowMovingCard" tabindex="0" role="link" aria-label="Open slow moving reports">
                                <div class="dashboard-section-header">
                                    <div class="dashboard-section-title-wrap">
                                        <div class="dashboard-section-icon"><i class="fas fa-hourglass-half"></i></div>
                                        <div class="dashboard-section-title-group">
                                            <h2 class="dashboard-section-title">Slow Moving Items</h2>
                                            <div class="dashboard-section-subtitle">Items with stock on hand and the oldest or missing recent sales.</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="slowMovingItemsBlock" class="dashboard-empty">No slow moving items found.</div>
                            </div>

                            <div class="dashboard-card dashboard-card-link" id="criticalInventoryCard" tabindex="0" role="link" aria-label="Open purchase order file">
                                <div class="dashboard-section-header">
                                    <div class="dashboard-section-title-wrap">
                                        <div class="dashboard-section-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                        <div class="dashboard-section-title-group">
                                            <h2 class="dashboard-section-title">Items with Critical Inventory</h2>
                                            <div class="dashboard-section-subtitle">Lowest stock gaps against defined critical quantity.</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="criticalInventoryBlock" class="dashboard-empty">No critical inventory records found.</div>
                            </div>
                        </div>

                        <div class="dashboard-grid-bottom">
                            <div class="dashboard-card dashboard-chart-card">
                                <div class="dashboard-section-header">
                                    <div class="dashboard-section-title-wrap">
                                        <div class="dashboard-section-icon"><i class="fas fa-chart-area"></i></div>
                                        <div class="dashboard-section-title-group">
                                            <h2 class="dashboard-section-title">Sales Trend by Month</h2>
                                            <div class="dashboard-section-subtitle" id="salesTrendMeta">Monthly sales totals for the selected year.</div>
                                        </div>
                                    </div>

                                    <div class="dashboard-trend-toolbar">
                                        <select class="form-select dashboard-trend-select" id="trend_year" name="trend_year" aria-label="Sales trend year">
                                            <option value="<?php echo htmlspecialchars($initial_trend_year, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($initial_trend_year, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="dashboard-trend-summary">
                                    <div class="dashboard-card-tag"><i class="fas fa-filter"></i>Chart filter is independent from From/To date</div>

                                    <div class="dashboard-trend-total">
                                        <i class="fas fa-signal"></i>
                                        <div>
                                            <span>Selected Year Total</span>
                                            <strong id="salesTrendTotal">0.00</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="dashboard-chart-shell">
                                    <div class="dashboard-chart-area" id="salesTrendChartArea">
                                        <svg class="dashboard-chart" id="salesTrendChart" viewBox="0 0 760 220" preserveAspectRatio="none"></svg>
                                        <div class="dashboard-chart-tooltip" id="salesTrendTooltip">
                                            <div class="dashboard-chart-tooltip-label" id="salesTrendTooltipLabel"></div>
                                            <div class="dashboard-chart-tooltip-value" id="salesTrendTooltipValue"></div>
                                        </div>
                                    </div>
                                    <div class="dashboard-chart-labels" id="salesTrendLabels"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</form>

<script>
    (function(){
        var filterForm = document.getElementById('dashboardFilterForm');
        var clearBtn = document.getElementById('dashboardClearBtn');
        var trendYearSelect = document.getElementById('trend_year');
        var fromDateInput = document.getElementById('from_date');
        var toDateInput = document.getElementById('to_date');
        var loadingBox = document.getElementById('dashboardLoading');
        var errorBox = document.getElementById('dashboardError');
        var criticalInventoryCard = document.getElementById('criticalInventoryCard');
        var criticalInventoryUrl = 'critical_pdf_rep.php?chk_critical_only=on';
        var slowMovingCard = document.getElementById('slowMovingCard');
        var slowMovingUrl = 'slow_moving_pdf_rep.php';
        var chartArea = document.getElementById('salesTrendChartArea');
        var tooltip = document.getElementById('salesTrendTooltip');
        var tooltipLabel = document.getElementById('salesTrendTooltipLabel');
        var tooltipValue = document.getElementById('salesTrendTooltipValue');
        var loadingTimer = null;
        var loadingProgressInterval = null;
        var loadingProgress = 0;
        var loadingBarFill = document.getElementById('dashboardLoadingBarFill');
        var loadingPercentText = document.getElementById('dashboardLoadingPercent');
        var loadingMessageElement = document.getElementById('dashboardLoadingMessage');
        var loadingMessageInterval = null;
        var loadingMessageIndex = 0;
        var loadingMessages = [
            'Preparing your dashboard...',
            'Loading the latest data...',
            'Keep hanging, we\'re almost there...',
            'Setting things up for you...',
            'Almost ready...',
            'Just a little more...'
        ];

        function escapeHtml(value){
            if(value === null || value === undefined){
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatNumber(value, decimals){
            var numericValue = Number(value || 0);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(numericValue);
        }

        function formatCurrency(value, decimals){
            var numericValue = Number(value || 0);
            var absoluteValue = formatNumber(Math.abs(numericValue), decimals);
            return (numericValue < 0 ? '(-)' : '(+)') + '\u20b1' + absoluteValue;
        }

        function formatMainNetProfit(value, decimals){
            var numericValue = Number(value || 0);
            return (numericValue < 0 ? '-' : '+') + '\u20b1' + formatNumber(Math.abs(numericValue), decimals);
        }

        function formatBreakdownCurrency(value, decimals, isPositive){
            return (isPositive ? '(+)' : '(-)') + '\u20b1' + formatNumber(Math.abs(Number(value || 0)), decimals);
        }

        function formatFormulaCurrency(value, decimals){
            return '\u20b1' + formatNumber(Math.abs(Number(value || 0)), decimals);
        }

        function applyAmountColor(element, isPositive){
            if(!element){
                return;
            }

            var amountClass = isPositive ? 'dashboard-amount-positive' : 'dashboard-amount-negative';
            var amountColor = isPositive ? '#198754' : '#dc3545';

            element.classList.remove('dashboard-amount-positive', 'dashboard-amount-negative');
            element.classList.add(amountClass);
            element.style.color = amountColor;
        }

        function formatDateInputValue(value){
            if(!value){
                return '';
            }

            if(/^\d{2}\/\d{2}\/\d{4}$/.test(value)){
                return value;
            }

            if(/^\d{4}-\d{2}-\d{2}$/.test(value)){
                var parts = value.split('-');
                return parts[1] + '/' + parts[2] + '/' + parts[0];
            }

            return '';
        }

        function renderStatRows(rows){
            if(!rows || !rows.length){
                return '<p class="dashboard-empty mb-0">No data found.</p>';
            }

            return '<div class="dashboard-stat-list">' + rows.join('') + '</div>';
        }

        function renderNetProfitBreakdown(metrics){
            var breakdown = (metrics && metrics.net_profit_breakdown) ? metrics.net_profit_breakdown : {};
            var salesAmount = Math.abs(Number(breakdown.sales_amount || 0));
            var purchases = Math.abs(Number(breakdown.purchases || 0));
            var salesReturn = Math.abs(Number(breakdown.sales_return || 0));
            var adjustments = Math.abs(Number(breakdown.adjustments || 0));
            var expenses = Math.abs(Number(breakdown.expenses || 0));
            var netProfit = Number(metrics && metrics.net_profit ? metrics.net_profit : 0);
            var salesAmountElement = document.getElementById('netProfitSalesAmount');
            var purchasesElement = document.getElementById('netProfitPurchases');
            var salesReturnElement = document.getElementById('netProfitSalesReturn');
            var adjustmentsElement = document.getElementById('netProfitAdjustments');
            var expensesElement = document.getElementById('netProfitExpenses');

            salesAmountElement.textContent = formatBreakdownCurrency(salesAmount, 2, true);
            purchasesElement.textContent = formatBreakdownCurrency(purchases, 2, false);
            salesReturnElement.textContent = formatBreakdownCurrency(salesReturn, 2, true);
            adjustmentsElement.textContent = formatBreakdownCurrency(adjustments, 2, true);
            expensesElement.textContent = formatBreakdownCurrency(expenses, 2, false);

            applyAmountColor(salesAmountElement, true);
            applyAmountColor(purchasesElement, false);
            applyAmountColor(salesReturnElement, true);
            applyAmountColor(adjustmentsElement, true);
            applyAmountColor(expensesElement, false);

            document.getElementById('netProfitFormula').textContent =
                formatFormulaCurrency(salesAmount, 2) +
                ' - ' + formatFormulaCurrency(purchases, 2) +
                ' + ' + formatFormulaCurrency(salesReturn, 2) +
                ' + ' + formatFormulaCurrency(adjustments, 2) +
                ' - ' + formatFormulaCurrency(expenses, 2) +
                ' = ' + formatMainNetProfit(netProfit, 2);
        }

        function renderBestSellingItem(items){
            var target = document.getElementById('bestSellingItemBlock');

            if(!items || (!items.by_sales && !items.by_quantity)){
                target.innerHTML = '<p class="dashboard-empty mb-0">No sales records found.</p>';
                return;
            }

            function renderRankPanel(panelTitle, iconClass, item){
                if(!item){
                    return '' +
                        '<div class="dashboard-mini-panel">' +
                            '<div class="dashboard-mini-panel-head">' +
                                '<div class="dashboard-mini-panel-title">' + escapeHtml(panelTitle) + '</div>' +
                                '<div class="dashboard-mini-panel-rank"><i class="' + escapeHtml(iconClass) + '"></i></div>' +
                            '</div>' +
                            '<p class="dashboard-empty mb-0">No sales records found.</p>' +
                        '</div>';
                }

                return '' +
                    '<div class="dashboard-mini-panel">' +
                        '<div class="dashboard-mini-panel-head">' +
                            '<div class="dashboard-mini-panel-title">' + escapeHtml(panelTitle) + '</div>' +
                            '<div class="dashboard-mini-panel-rank"><i class="' + escapeHtml(iconClass) + '"></i></div>' +
                        '</div>' +
                        '<div class="dashboard-mini-panel-name">' + escapeHtml(item.itmdsc) + '</div>' +
                        '<div class="dashboard-mini-panel-meta">' +
                            '<div><span>Total Sales</span><strong>' + formatNumber(item.total_sales, 2) + '</strong></div>' +
                            '<div><span>Total Qty</span><strong>' + formatNumber(item.quantity_sold, 2) + '</strong></div>' +
                        '</div>' +
                    '</div>';
            }

            target.innerHTML = '' +
                '<div class="dashboard-mini-panel-grid">' +
                    renderRankPanel('Top by Total Sales', 'fas fa-dollar-sign', items.by_sales) +
                    renderRankPanel('Top by Quantity Sold', 'fas fa-sort-amount-up', items.by_quantity) +
                '</div>';
        }

        // Store best salesman date ranges and salesman IDs for navigation
        var bestSalesmanPrevMonthData = { from: '', to: '', salesman_id: '' };
        var bestSalesmanCurrentMonthData = { from: '', to: '', salesman_id: '' };

        function renderBestSalesmanComparison(data){
            var prevMonthHeader = document.getElementById('bestSalesmanPrevMonthHeader');
            var currentMonthHeader = document.getElementById('bestSalesmanCurrentMonthHeader');
            var prevMonthBlock = document.getElementById('bestSalesmanPrevMonthBlock');
            var currentMonthBlock = document.getElementById('bestSalesmanCurrentMonthBlock');

            if(!data){
                prevMonthHeader.textContent = 'Previous Month';
                currentMonthHeader.textContent = 'Current Month';
                prevMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No salesman sales found.</p>';
                currentMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No salesman sales found.</p>';
                bestSalesmanPrevMonthData = { from: '', to: '', salesman_id: '' };
                bestSalesmanCurrentMonthData = { from: '', to: '', salesman_id: '' };
                return;
            }

            function renderSalesmanColumn(salesman){
                if(!salesman){
                    return '<p class="dashboard-empty mb-0">No salesman sales found.</p>';
                }

                var netProfitClass = Number(salesman.net_profit || 0) >= 0 ? 'dashboard-amount-positive' : 'dashboard-amount-negative';
                var commissionDisplay = '\u20b1' + formatNumber(salesman.total_commission, 2) + ' (' + formatNumber(salesman.commission_rate, 2) + '%)';

                return '' +
                    '<div class="dashboard-stat-list">' +
                        '<div class="dashboard-stat-row"><span>Salesman</span><strong>' + escapeHtml(salesman.salesman_name) + '</strong></div>' +
                        '<div class="dashboard-stat-row"><span>Total Sales</span><strong>\u20b1' + formatNumber(salesman.total_sales, 2) + '</strong></div>' +
                        '<div class="dashboard-stat-row"><span>Total Commission</span><strong>' + commissionDisplay + '</strong></div>' +
                        '<div class="dashboard-stat-row"><span>COGS</span><strong>\u20b1' + formatNumber(salesman.cogs, 2) + '</strong></div>' +
                    '</div>' +
                    '<div class="dashboard-expenses-total">' +
                        '<div class="dashboard-stat-list">' +
                            '<div class="dashboard-stat-row">' +
                                '<span>Net Profit</span>' +
                                '<strong class="' + netProfitClass + '">\u20b1' + formatNumber(salesman.net_profit, 2) + '</strong>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            }

            // Render previous month column
            if(data.prev_month){
                prevMonthHeader.textContent = escapeHtml(data.prev_month.date_range_display);
                bestSalesmanPrevMonthData = {
                    from: data.prev_month.start_date_url || '',
                    to: data.prev_month.end_date_url || '',
                    salesman_id: (data.prev_month.salesman && data.prev_month.salesman.salesman_id) || ''
                };
                prevMonthBlock.innerHTML = renderSalesmanColumn(data.prev_month.salesman);
            }else{
                prevMonthHeader.textContent = 'Previous Month';
                prevMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No salesman sales found.</p>';
                bestSalesmanPrevMonthData = { from: '', to: '', salesman_id: '' };
            }

            // Render current/selected month column
            if(data.current_month){
                currentMonthHeader.textContent = escapeHtml(data.current_month.date_range_display);
                bestSalesmanCurrentMonthData = {
                    from: data.current_month.start_date_url || '',
                    to: data.current_month.end_date_url || '',
                    salesman_id: (data.current_month.salesman && data.current_month.salesman.salesman_id) || ''
                };
                currentMonthBlock.innerHTML = renderSalesmanColumn(data.current_month.salesman);
            }else{
                currentMonthHeader.textContent = 'Current Month';
                currentMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No salesman sales found.</p>';
                bestSalesmanCurrentMonthData = { from: '', to: '', salesman_id: '' };
            }
        }

        function renderSlowMovingItems(items){
            var target = document.getElementById('slowMovingItemsBlock');

            if(!items || !items.length){
                target.innerHTML = '<p class="dashboard-empty mb-0">No slow moving items found.</p>';
                return;
            }

            var rows = items.map(function(item){
                return '' +
                    '<tr>' +
                        '<td>' + escapeHtml(item.itmdsc) + '</td>' +
                        '<td class="text-nowrap">' + escapeHtml(item.last_sale_date_display) + '</td>' +
                        '<td class="text-nowrap">' + escapeHtml(item.last_purchase_date_display) + '</td>' +
                        '<td class="text-end">' + formatNumber(item.current_stock, 2) + '</td>' +
                    '</tr>';
            }).join('');

            target.innerHTML = '' +
                '<div class="dashboard-table-wrap">' +
                    '<table class="table table-sm dashboard-table">' +
                        '<thead><tr><th>Item</th><th>Last Sale</th><th>Latest Purchase</th><th class="text-end">Current Stock</th></tr></thead>' +
                        '<tbody>' + rows + '</tbody>' +
                    '</table>' +
                '</div>';
        }

        function renderCriticalInventory(items){
            var target = document.getElementById('criticalInventoryBlock');

            if(!items || !items.length){
                target.innerHTML = '<p class="dashboard-empty mb-0">No critical inventory records found.</p>';
                return;
            }

            var rows = items.map(function(item){
                return '' +
                    '<tr>' +
                        '<td>' + escapeHtml(item.itmdsc) + '</td>' +
                        '<td class="text-end">' + formatNumber(item.current_stock, 2) + '</td>' +
                        '<td class="text-end">' + formatNumber(item.critical_qty, 2) + '</td>' +
                    '</tr>';
            }).join('');

            target.innerHTML = '' +
                '<div class="dashboard-table-wrap">' +
                    '<table class="table table-sm dashboard-table mb-0">' +
                        '<thead><tr><th>Item</th><th class="text-end">Current Stock</th><th class="text-end">Critical Qty</th></tr></thead>' +
                        '<tbody>' + rows + '</tbody>' +
                    '</table>' +
                '</div>' +
                '<div class="dashboard-critical-note"><i class="fas fa-external-link-alt"></i>Open purchase orders</div>';
        }

        // Store expenses date ranges for navigation
        var expensesPrevMonthDates = { from: '', to: '' };
        var expensesCurrentMonthDates = { from: '', to: '' };

        function renderExpensesBreakdown(data){
            var prevMonthHeader = document.getElementById('expensesPrevMonthHeader');
            var currentMonthHeader = document.getElementById('expensesCurrentMonthHeader');
            var prevMonthBlock = document.getElementById('expensesPrevMonthBlock');
            var currentMonthBlock = document.getElementById('expensesCurrentMonthBlock');

            if(!data){
                prevMonthHeader.textContent = 'Previous Month';
                currentMonthHeader.textContent = 'Current Month';
                prevMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No expenses found.</p>';
                currentMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No expenses found.</p>';
                expensesPrevMonthDates = { from: '', to: '' };
                expensesCurrentMonthDates = { from: '', to: '' };
                return;
            }

            // Render previous month column
            if(data.prev_month){
                prevMonthHeader.textContent = escapeHtml(data.prev_month.date_range_display);
                expensesPrevMonthDates = {
                    from: data.prev_month.start_date_url || '',
                    to: data.prev_month.end_date_url || ''
                };

                if(data.prev_month.expenses && data.prev_month.expenses.length){
                    var prevRows = data.prev_month.expenses.map(function(expense){
                        return '' +
                            '<div class="dashboard-stat-row">' +
                                '<span>' + escapeHtml(expense.expense_dsc) + '</span>' +
                                '<strong>\u20b1' + formatNumber(expense.total_amount, 2) + '</strong>' +
                            '</div>';
                    }).join('');
                    var prevTotal = '' +
                        '<div class="dashboard-expenses-total">' +
                            '<div class="dashboard-stat-list">' +
                                '<div class="dashboard-stat-row">' +
                                    '<span>Total</span>' +
                                    '<strong>\u20b1' + formatNumber(data.prev_month.total || 0, 2) + '</strong>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    prevMonthBlock.innerHTML = '<div class="dashboard-stat-list">' + prevRows + '</div>' + prevTotal;
                }else{
                    prevMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No expenses found.</p>';
                }
            }else{
                prevMonthHeader.textContent = 'Previous Month';
                prevMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No expenses found.</p>';
                expensesPrevMonthDates = { from: '', to: '' };
            }

            // Render current/selected month column
            if(data.current_month){
                currentMonthHeader.textContent = escapeHtml(data.current_month.date_range_display);
                expensesCurrentMonthDates = {
                    from: data.current_month.start_date_url || '',
                    to: data.current_month.end_date_url || ''
                };

                if(data.current_month.expenses && data.current_month.expenses.length){
                    var currentRows = data.current_month.expenses.map(function(expense){
                        return '' +
                            '<div class="dashboard-stat-row">' +
                                '<span>' + escapeHtml(expense.expense_dsc) + '</span>' +
                                '<strong>\u20b1' + formatNumber(expense.total_amount, 2) + '</strong>' +
                            '</div>';
                    }).join('');
                    var currentTotal = '' +
                        '<div class="dashboard-expenses-total">' +
                            '<div class="dashboard-stat-list">' +
                                '<div class="dashboard-stat-row">' +
                                    '<span>Total</span>' +
                                    '<strong>\u20b1' + formatNumber(data.current_month.total || 0, 2) + '</strong>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    currentMonthBlock.innerHTML = '<div class="dashboard-stat-list">' + currentRows + '</div>' + currentTotal;
                }else{
                    currentMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No expenses found.</p>';
                }
            }else{
                currentMonthHeader.textContent = 'Current Month';
                currentMonthBlock.innerHTML = '<p class="dashboard-empty mb-0">No expenses found.</p>';
                expensesCurrentMonthDates = { from: '', to: '' };
            }
        }

        function renderTrendLabels(labels){
            var target = document.getElementById('salesTrendLabels');
            target.innerHTML = labels.map(function(label){
                return '<div>' + escapeHtml(label.substring(0, 3)) + '</div>';
            }).join('');
        }

        function hideTrendTooltip(){
            tooltip.classList.remove('active');
        }

        function showTrendTooltip(monthLabel, value, x, y){
            tooltipLabel.textContent = monthLabel;
            tooltipValue.textContent = formatNumber(value, 2);
            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
            tooltip.classList.add('active');
        }

        function getTrendAxisConfig(maxValue){
            var safeMax = Number(maxValue || 0);
            var stepMultipliers = [1, 2, 2.5, 5, 10];
            var minimumTicks = 5;
            var maximumTicks = 8;
            var targetTicks = 6;
            var bestCandidate = null;
            var fallbackCandidate = null;
            var baseExponent;
            var exponent;
            var i;

            if(safeMax <= 0){
                safeMax = 1;
            }

            baseExponent = Math.floor(Math.log(safeMax) / Math.LN10);

            for(exponent = baseExponent - 2; exponent <= baseExponent + 2; exponent++){
                var power = Math.pow(10, exponent);

                for(i = 0; i < stepMultipliers.length; i++){
                    var step = stepMultipliers[i] * power;
                    var axisMax = step * Math.ceil(safeMax / step);
                    var tickCount = Math.round(axisMax / step) + 1;
                    var score = (Math.abs(tickCount - targetTicks) * 1000000) + axisMax;
                    var candidate = {
                        step: step,
                        axisMax: axisMax || step,
                        tickCount: tickCount,
                        decimals: step % 1 === 0 ? 0 : 2,
                        score: score
                    };

                    if(tickCount >= minimumTicks && tickCount <= maximumTicks){
                        if(!bestCandidate || candidate.score < bestCandidate.score){
                            bestCandidate = candidate;
                        }
                    }

                    if(!fallbackCandidate || candidate.score < fallbackCandidate.score){
                        fallbackCandidate = candidate;
                    }
                }
            }

            return bestCandidate || fallbackCandidate || {
                step: 1,
                axisMax: 1,
                tickCount: 2,
                decimals: 0
            };
        }

        function renderTrendChart(labels, values){
            var svg = document.getElementById('salesTrendChart');
            var width = 760;
            var height = 220;
            var paddingLeft = 72;
            var paddingRight = 20;
            var paddingTop = 18;
            var paddingBottom = 30;
            var usableWidth = width - paddingLeft - paddingRight;
            var usableHeight = height - paddingTop - paddingBottom;
            var maxValue = 0;
            var i;

            hideTrendTooltip();

            for(i = 0; i < values.length; i++){
                if(Number(values[i]) > maxValue){
                    maxValue = Number(values[i]);
                }
            }

            if(maxValue <= 0){
                maxValue = 1;
            }

            var axisConfig = getTrendAxisConfig(maxValue);
            var points = [];
            var gridLines = [];
            var yAxisLabels = [];
            var pointDots = [];
            var hitZones = [];
            var areaPoints = [];
            var stepX = labels.length > 1 ? usableWidth / (labels.length - 1) : usableWidth;
            var lastPointX = paddingLeft;

            for(i = 0; i < axisConfig.tickCount; i++){
                var gridY = paddingTop + ((usableHeight / (axisConfig.tickCount - 1)) * i);
                var tickValue = axisConfig.axisMax - (axisConfig.step * i);
                gridLines.push(
                    '<line x1="' + paddingLeft + '" y1="' + gridY + '" x2="' + (width - paddingRight) + '" y2="' + gridY + '" stroke="rgba(33,37,41,0.1)" stroke-width="1" />'
                );
                yAxisLabels.push(
                    '<text x="' + (paddingLeft - 8) + '" y="' + gridY + '" fill="#6b7280" font-size="11" font-weight="600" text-anchor="end" dominant-baseline="middle">' + escapeHtml(formatNumber(tickValue, axisConfig.decimals)) + '</text>'
                );
            }

            for(i = 0; i < values.length; i++){
                var x = paddingLeft + (stepX * i);
                var value = Number(values[i] || 0);
                var y = paddingTop + usableHeight - ((value / axisConfig.axisMax) * usableHeight);
                var zoneWidth = labels.length > 1 ? stepX : usableWidth;
                var zoneX = i === 0 ? paddingLeft : x - (zoneWidth / 2);

                if(i === labels.length - 1){
                    lastPointX = x;
                }

                points.push(x + ',' + y);
                areaPoints.push(x + ',' + y);
                pointDots.push('<circle cx="' + x + '" cy="' + y + '" r="4.5" fill="#212529" stroke="#ffffff" stroke-width="2.5" />');
                hitZones.push(
                    '<rect class="dashboard-chart-hit" data-label="' + escapeHtml(labels[i]) + '" data-value="' + value + '" data-x="' + x + '" data-y="' + y + '" x="' + zoneX + '" y="' + paddingTop + '" width="' + zoneWidth + '" height="' + usableHeight + '" fill="transparent"></rect>'
                );
            }

            var areaPath = '';
            if(areaPoints.length){
                areaPath = 'M' + paddingLeft + ',' + (paddingTop + usableHeight) + ' L' + areaPoints.join(' L') + ' L' + lastPointX + ',' + (paddingTop + usableHeight) + ' Z';
            }

            svg.innerHTML = '' +
                '<defs>' +
                    '<linearGradient id="dashboardAreaGradient" x1="0" y1="0" x2="0" y2="1">' +
                        '<stop offset="0%" stop-color="#212529" stop-opacity="0.18"></stop>' +
                        '<stop offset="100%" stop-color="#212529" stop-opacity="0.03"></stop>' +
                    '</linearGradient>' +
                '</defs>' +
                yAxisLabels.join('') +
                gridLines.join('') +
                '<path d="' + areaPath + '" fill="url(#dashboardAreaGradient)"></path>' +
                '<polyline fill="none" stroke="#212529" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="' + points.join(' ') + '"></polyline>' +
                pointDots.join('') +
                hitZones.join('');

            Array.prototype.forEach.call(svg.querySelectorAll('.dashboard-chart-hit'), function(zone){
                function handleMove(event){
                    var chartRect = chartArea.getBoundingClientRect();
                    var svgRect = svg.getBoundingClientRect();
                    var svgX = Number(zone.getAttribute('data-x'));
                    var svgY = Number(zone.getAttribute('data-y'));
                    var relativeX = (svgX / width) * svgRect.width;
                    var relativeY = (svgY / height) * svgRect.height;
                    showTrendTooltip(
                        zone.getAttribute('data-label'),
                        Number(zone.getAttribute('data-value') || 0),
                        relativeX + (svgRect.left - chartRect.left),
                        relativeY + (svgRect.top - chartRect.top)
                    );
                }

                zone.addEventListener('mousemove', handleMove);
                zone.addEventListener('mouseenter', handleMove);
                zone.addEventListener('mouseleave', hideTrendTooltip);
                zone.addEventListener('touchstart', handleMove, { passive: true });
                zone.addEventListener('touchend', hideTrendTooltip, { passive: true });
            });
        }

        function isValidTrendYear(yearValue){
            var yearText = String(yearValue || '').trim();
            var yearNumber = Number(yearText);
            var maximumTrendYear = <?php echo (int)date('Y') + 10; ?>;

            return /^\d{4}$/.test(yearText) && yearNumber >= 1900 && yearNumber <= maximumTrendYear;
        }

        function setYearOptions(years, selectedYear){
            var safeYears = Array.isArray(years) && years.length ? years.slice() : [selectedYear];
            var seen = {};
            var options = [];
            var selectedYearText;

            safeYears.forEach(function(yearValue){
                var yearText = String(yearValue).trim();
                if(isValidTrendYear(yearText) && !seen[yearText]){
                    seen[yearText] = true;
                    options.push(yearText);
                }
            });

            if(isValidTrendYear(selectedYear) && !seen[String(selectedYear)]){
                options.unshift(String(selectedYear));
            }

            if(!options.length){
                options.push(String(<?php echo (int)date('Y'); ?>));
            }

            selectedYearText = isValidTrendYear(selectedYear) ? String(selectedYear).trim() : options[0];

            trendYearSelect.innerHTML = options.map(function(yearValue){
                var selectedAttr = selectedYearText === String(yearValue) ? ' selected' : '';
                return '<option value="' + escapeHtml(yearValue) + '"' + selectedAttr + '>' + escapeHtml(yearValue) + '</option>';
            }).join('');
        }

        function updateUrl(){
            var params = new URLSearchParams();

            if(fromDateInput.value){
                params.set('from_date', fromDateInput.value);
            }

            if(toDateInput.value){
                params.set('to_date', toDateInput.value);
            }

            if(trendYearSelect.value){
                params.set('trend_year', trendYearSelect.value);
            }

            var nextUrl = 'gen_dashboard.php';
            var queryString = params.toString();

            if(queryString){
                nextUrl += '?' + queryString;
            }

            window.history.replaceState({}, '', nextUrl);
        }

        function setError(message){
            errorBox.textContent = message;
            errorBox.classList.remove('d-none');
        }

        function clearError(){
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }

        function updateLoadingProgress(percent){
            loadingProgress = Math.min(100, Math.max(0, percent));
            loadingBarFill.style.width = loadingProgress + '%';
            loadingPercentText.textContent = Math.round(loadingProgress) + '%';
        }

        function startLoadingProgress(){
            loadingProgress = 0;
            updateLoadingProgress(0);
            loadingBox.classList.add('active');

            if(loadingProgressInterval){
                clearInterval(loadingProgressInterval);
            }

            // Start message rotation
            loadingMessageIndex = 0;
            loadingMessageElement.textContent = loadingMessages[0];

            if(loadingMessageInterval){
                clearInterval(loadingMessageInterval);
            }

            loadingMessageInterval = setInterval(function(){
                loadingMessageIndex = (loadingMessageIndex + 1) % loadingMessages.length;
                loadingMessageElement.style.opacity = '0';
                setTimeout(function(){
                    loadingMessageElement.textContent = loadingMessages[loadingMessageIndex];
                    loadingMessageElement.style.opacity = '1';
                }, 150);
            }, 3000);

            // Smooth incremental progress distributed evenly across the loading duration
            // Using smaller increments and longer intervals to avoid pausing at 92%
            loadingProgressInterval = setInterval(function(){
                if(loadingProgress < 20){
                    // Initial progress (0-20%)
                    loadingProgress += 0.8;
                }else if(loadingProgress < 40){
                    // Early-mid progress (20-40%)
                    loadingProgress += 0.6;
                }else if(loadingProgress < 60){
                    // Mid progress (40-60%)
                    loadingProgress += 0.5;
                }else if(loadingProgress < 75){
                    // Late-mid progress (60-75%)
                    loadingProgress += 0.4;
                }else if(loadingProgress < 85){
                    // Late progress (75-85%)
                    loadingProgress += 0.3;
                }else if(loadingProgress < 95){
                    // Final stretch (85-95%) - keeps moving slowly
                    loadingProgress += 0.15;
                }else if(loadingProgress < 98){
                    // Near completion (95-98%) - very slow but still moving
                    loadingProgress += 0.08;
                }
                // Cap at 98% and wait for actual completion
                if(loadingProgress >= 98){
                    loadingProgress = 98;
                }
                updateLoadingProgress(loadingProgress);
            }, 100);
        }

        function completeLoadingProgress(){
            if(loadingProgressInterval){
                clearInterval(loadingProgressInterval);
                loadingProgressInterval = null;
            }

            if(loadingMessageInterval){
                clearInterval(loadingMessageInterval);
                loadingMessageInterval = null;
            }

            // Animate to 100% then hide
            updateLoadingProgress(100);
            loadingMessageElement.textContent = 'Done!';

            setTimeout(function(){
                loadingBox.classList.remove('active');
                loadingProgress = 0;
                updateLoadingProgress(0);
                loadingMessageElement.textContent = loadingMessages[0];
            }, 400);
        }

        function resetLoadingProgress(){
            if(loadingProgressInterval){
                clearInterval(loadingProgressInterval);
                loadingProgressInterval = null;
            }
            if(loadingMessageInterval){
                clearInterval(loadingMessageInterval);
                loadingMessageInterval = null;
            }
            loadingProgress = 0;
            updateLoadingProgress(0);
            loadingMessageElement.textContent = loadingMessages[0];
            loadingBox.classList.remove('active');
        }

        function loadDashboard(){
            clearTimeout(loadingTimer);
            clearError();
            hideTrendTooltip();
            resetLoadingProgress();

            // Show loading bar immediately
            startLoadingProgress();

            var params = new URLSearchParams();
            params.set('trend_year', trendYearSelect.value || '<?php echo htmlspecialchars($initial_trend_year, ENT_QUOTES, 'UTF-8'); ?>');

            if(fromDateInput.value){
                params.set('from_date', fromDateInput.value);
            }

            if(toDateInput.value){
                params.set('to_date', toDateInput.value);
            }

            updateUrl();

            fetch('gen_dashboard_data.php?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(function(response){
                if(!response.ok){
                    throw new Error('Unable to load dashboard data.');
                }

                return response.json();
            })
            .then(function(payload){
                if(!payload || payload.success !== true){
                    throw new Error((payload && payload.message) ? payload.message : 'Unable to load dashboard data.');
                }

                fromDateInput.value = payload.filters.from_date_display || '';
                toDateInput.value = payload.filters.to_date_display || '';

                document.getElementById('currentStockValuationValue').textContent = formatNumber(payload.metrics.current_stock_valuation, 2);
                document.getElementById('netProfitValue').textContent = formatMainNetProfit(payload.metrics.net_profit, 2);
                applyAmountColor(document.getElementById('netProfitValue'), Number(payload.metrics.net_profit || 0) >= 0);
                document.getElementById('totalSkusValue').textContent = formatNumber(payload.metrics.total_skus, 0);
                document.getElementById('netProfitMeta').textContent = payload.filters.range_label;
                renderNetProfitBreakdown(payload.metrics);
                document.getElementById('salesTrendMeta').textContent = 'Monthly sales totals for ' + payload.filters.trend_year + '.';
                document.getElementById('salesTrendTotal').textContent = formatNumber(payload.sales_trend.total, 2);

                renderBestSellingItem(payload.best_selling_items);
                renderBestSalesmanComparison(payload.best_salesman_comparison);
                renderSlowMovingItems(payload.slow_moving_items);
                renderCriticalInventory(payload.critical_inventory_items);
                renderExpensesBreakdown(payload.expenses_breakdown);
                renderTrendLabels(payload.sales_trend.labels);
                renderTrendChart(payload.sales_trend.labels, payload.sales_trend.values);
                setYearOptions(payload.available_years, payload.filters.trend_year);
            })
            .catch(function(error){
                setError(error.message || 'Unable to load dashboard data.');
            })
            .finally(function(){
                clearTimeout(loadingTimer);
                completeLoadingProgress();
            });
        }

        filterForm.addEventListener('submit', function(event){
            event.preventDefault();
            loadDashboard();
        });

        clearBtn.addEventListener('click', function(){
            fromDateInput.value = '';
            toDateInput.value = '';
            loadDashboard();
        });

        trendYearSelect.addEventListener('change', function(){
            loadDashboard();
        });

        criticalInventoryCard.addEventListener('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            window.open(criticalInventoryUrl, '_blank', 'noopener,noreferrer');
        });

        criticalInventoryCard.addEventListener('keydown', function(event){
            if(event.key === 'Enter' || event.key === ' '){
                event.preventDefault();
                window.open(criticalInventoryUrl, '_blank', 'noopener,noreferrer');
            }
        });

        slowMovingCard.addEventListener('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            window.open(slowMovingUrl, '_blank', 'noopener,noreferrer');
        });

        slowMovingCard.addEventListener('keydown', function(event){
            if(event.key === 'Enter' || event.key === ' '){
                event.preventDefault();
                window.open(slowMovingUrl, '_blank', 'noopener,noreferrer');
            }
        });

        // Expenses card navigation using POST
        var expensesPrevMonthCard = document.getElementById('expensesPrevMonthCard');
        var expensesCurrentMonthCard = document.getElementById('expensesCurrentMonthCard');

        function navigateToExpenses(fromDate, toDate){
            // Create a temporary form for POST navigation
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'expensefile1.php';
            form.target = '_blank';
            form.style.display = 'none';

            if(fromDate){
                var fromInput = document.createElement('input');
                fromInput.type = 'hidden';
                fromInput.name = 'dashboard_from_date';
                fromInput.value = fromDate;
                form.appendChild(fromInput);
            }

            if(toDate){
                var toInput = document.createElement('input');
                toInput.type = 'hidden';
                toInput.name = 'dashboard_to_date';
                toInput.value = toDate;
                form.appendChild(toInput);
            }

            // Add a marker to indicate this is from the dashboard
            var markerInput = document.createElement('input');
            markerInput.type = 'hidden';
            markerInput.name = 'from_dashboard';
            markerInput.value = '1';
            form.appendChild(markerInput);

            document.body.appendChild(form);
            form.submit();
        }

        expensesPrevMonthCard.addEventListener('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            navigateToExpenses(expensesPrevMonthDates.from, expensesPrevMonthDates.to);
        });

        expensesPrevMonthCard.addEventListener('keydown', function(event){
            if(event.key === 'Enter' || event.key === ' '){
                event.preventDefault();
                navigateToExpenses(expensesPrevMonthDates.from, expensesPrevMonthDates.to);
            }
        });

        expensesCurrentMonthCard.addEventListener('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            navigateToExpenses(expensesCurrentMonthDates.from, expensesCurrentMonthDates.to);
        });

        expensesCurrentMonthCard.addEventListener('keydown', function(event){
            if(event.key === 'Enter' || event.key === ' '){
                event.preventDefault();
                navigateToExpenses(expensesCurrentMonthDates.from, expensesCurrentMonthDates.to);
            }
        });

        // Best Salesman card navigation using POST
        var bestSalesmanPrevMonthCard = document.getElementById('bestSalesmanPrevMonthCard');
        var bestSalesmanCurrentMonthCard = document.getElementById('bestSalesmanCurrentMonthCard');
        var bestSalesmanReportUrl = 'trndate_rep_sales_cost.php';

        function navigateToBestSalesmanReport(fromDate, toDate, salesmanId){
            // Don't navigate if no salesman data
            if(!salesmanId){
                return;
            }

            // Create a temporary form for POST navigation
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = bestSalesmanReportUrl;
            form.target = '_blank';
            form.style.display = 'none';

            // Transaction code for sales
            var trncdeInput = document.createElement('input');
            trncdeInput.type = 'hidden';
            trncdeInput.name = 'trncde_hidden';
            trncdeInput.value = 'SAL';
            form.appendChild(trncdeInput);

            // Date from
            if(fromDate){
                var fromInput = document.createElement('input');
                fromInput.type = 'hidden';
                fromInput.name = 'date_from';
                fromInput.value = fromDate;
                form.appendChild(fromInput);
            }

            // Date to
            if(toDate){
                var toInput = document.createElement('input');
                toInput.type = 'hidden';
                toInput.name = 'date_to';
                toInput.value = toDate;
                form.appendChild(toInput);
            }

            // Salesman ID
            var salesmanInput = document.createElement('input');
            salesmanInput.type = 'hidden';
            salesmanInput.name = 'smn_search';
            salesmanInput.value = salesmanId;
            form.appendChild(salesmanInput);

            // Output type (PDF)
            var outputInput = document.createElement('input');
            outputInput.type = 'hidden';
            outputInput.name = 'txt_output_type';
            outputInput.value = 'pdf';
            form.appendChild(outputInput);

            // Add a marker to indicate this is from the dashboard
            var markerInput = document.createElement('input');
            markerInput.type = 'hidden';
            markerInput.name = 'from_dashboard';
            markerInput.value = '1';
            form.appendChild(markerInput);

            document.body.appendChild(form);
            form.submit();
        }

        bestSalesmanPrevMonthCard.addEventListener('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            navigateToBestSalesmanReport(
                bestSalesmanPrevMonthData.from,
                bestSalesmanPrevMonthData.to,
                bestSalesmanPrevMonthData.salesman_id
            );
        });

        bestSalesmanPrevMonthCard.addEventListener('keydown', function(event){
            if(event.key === 'Enter' || event.key === ' '){
                event.preventDefault();
                navigateToBestSalesmanReport(
                    bestSalesmanPrevMonthData.from,
                    bestSalesmanPrevMonthData.to,
                    bestSalesmanPrevMonthData.salesman_id
                );
            }
        });

        bestSalesmanCurrentMonthCard.addEventListener('click', function(event){
            event.preventDefault();
            event.stopPropagation();
            navigateToBestSalesmanReport(
                bestSalesmanCurrentMonthData.from,
                bestSalesmanCurrentMonthData.to,
                bestSalesmanCurrentMonthData.salesman_id
            );
        });

        bestSalesmanCurrentMonthCard.addEventListener('keydown', function(event){
            if(event.key === 'Enter' || event.key === ' '){
                event.preventDefault();
                navigateToBestSalesmanReport(
                    bestSalesmanCurrentMonthData.from,
                    bestSalesmanCurrentMonthData.to,
                    bestSalesmanCurrentMonthData.salesman_id
                );
            }
        });

        chartArea.addEventListener('mouseleave', hideTrendTooltip);

        window.jQuery(function(){
            var dashboardDateInputs = window.jQuery('#from_date, #to_date');
            var dashboardDatepickerOptions = {
                showAnim: 'blind',
                changeMonth: true,
                changeYear: true,
                yearRange: '-100:+2',
                showOn: 'focus',
                showButtonPanel: true,
                closeText: 'Clear',
                dateFormat: 'mm/dd/yy',
                beforeShow: function() {
                    window.jQuery('#ui-datepicker-div').appendTo('body').hide();
                },
                onClose: function () {
                    window.jQuery(this).blur();
                    var event = arguments.callee.caller && arguments.callee.caller.caller ? arguments.callee.caller.caller.arguments[0] : null;
                    var event_checker = false;
                    if(event){
                        event_checker = event.hasOwnProperty('delegateTarget');
                    }
                    if(event_checker === true){
                        if (window.jQuery(event.delegateTarget).hasClass('ui-datepicker-close')) {
                            window.jQuery(this).val('');
                        }
                    }
                }
            };

            dashboardDateInputs.each(function(){
                var input = window.jQuery(this);

                if(!input.hasClass('hasDatepicker') && typeof input.datepicker === 'function'){
                    input.datepicker(dashboardDatepickerOptions);
                }else if(input.hasClass('hasDatepicker')){
                    input.datepicker('option', dashboardDatepickerOptions);
                }
            });

            dashboardDateInputs.on('focus click', function(){
                window.jQuery(this).datepicker('show');
            });
        });

        loadDashboard();
    })();
</script>

<?php
beaver_log("CHECKPOINT: HTML/JS output complete, before main_footer.php");
$beaver_footer_start = microtime(true);
require "includes/main_footer.php";
beaver_log("CHECKPOINT: main_footer.php included", array(
    'footer_duration_ms' => round((microtime(true) - $beaver_footer_start) * 1000, 2)
));
beaver_log("=== GEN_DASHBOARD.PHP SCRIPT END ===", array(
    'total_duration_ms' => round((microtime(true) - $BEAVER_START_TIME) * 1000, 2),
    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    'checkpoints_logged' => $BEAVER_CHECKPOINT_COUNT
));
?>
