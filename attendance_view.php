<?php
/**
 * attendance_view.php - View-only Attendance/Timekeeping Page
 *
 * This page is a PHP implementation of the Node.js timekeeping screen.
 * It displays timekeeping records from the timekeeping_trn table with
 * search/filter functionality and XLSX export.
 *
 * VIEW-ONLY: No add, edit, delete, save, or update functionality.
 */

// Handle XLSX export BEFORE any output
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once 'vendor/autoload.php';

    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $dateFrom = isset($_GET['dateFrom']) ? trim($_GET['dateFrom']) : '';
    $dateTo = isset($_GET['dateTo']) ? trim($_GET['dateTo']) : '';

    // Build query
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

    $sql = "SELECT
        t.*,
        CONCAT(e.fname, ' ', e.lname) AS employee_name
    FROM timekeeping_trn t
    LEFT JOIN employee_mf e ON t.emp_id = e.emp_id
    $whereClause
    ORDER BY t.trn_date DESC, t.emp_id ASC";

    $stmt = $link->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function to format time
    function formatTimeForExport($time) {
        if (!$time) return '';
        $parts = explode(':', $time);
        $hour = (int)$parts[0];
        $minutes = isset($parts[1]) ? $parts[1] : '00';
        $ampm = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12;
        if ($displayHour == 0) $displayHour = 12;
        return $displayHour . ':' . $minutes . ' ' . $ampm;
    }

    // Helper function to get status
    function getStatusExport($row) {
        if ($row['is_absent'] === 'Yes') return 'Absent';
        if ($row['is_complete_inout'] === 'in_missing') return 'Missing In';
        if ($row['is_complete_inout'] === 'out_missing') return 'Missing Out';
        if ($row['is_late'] === 'Yes') return 'Late';
        return 'On Time';
    }

    // Helper function to get approved OT status
    function getApprovedOTExport($row) {
        if ($row['overtime_min'] == 0) return 'No OT';
        return $row['is_approved_ot'] === 'Yes' ? 'Approved' : 'Not Approved';
    }

    // Create spreadsheet using fully qualified class names
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Timekeeping');

    // Headers - exact order as specified
    $headers = ['Name', 'Date', 'Day', 'Time In', 'Time Out', 'Hours', 'Status', 'Net OT', 'Total Pay', 'Approved OT'];
    $sheet->fromArray($headers, null, 'A1');

    // Style headers
    $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Data rows
    $rowNum = 2;
    $grandTotalPay = 0;

    foreach ($rows as $row) {
        $totalPay = (float)($row['total_day_pay'] ?? 0);
        $grandTotalPay += $totalPay;

        $sheet->setCellValue('A' . $rowNum, $row['employee_name'] ?? '');
        $sheet->setCellValue('B' . $rowNum, $row['trn_date'] ? date('m/d/Y', strtotime($row['trn_date'])) : '');
        $sheet->setCellValue('C' . $rowNum, $row['day_of_week'] ? substr($row['day_of_week'], 0, 3) : '');
        $sheet->setCellValue('D' . $rowNum, formatTimeForExport($row['time_log_in']));
        $sheet->setCellValue('E' . $rowNum, formatTimeForExport($row['time_log_out']));
        $sheet->setCellValue('F' . $rowNum, number_format((float)($row['total_hours'] ?? 0), 1) . 'h');
        $sheet->setCellValue('G' . $rowNum, getStatusExport($row));
        $sheet->setCellValue('H' . $rowNum, (int)($row['net_overtime_min'] ?? 0));
        $sheet->setCellValue('I' . $rowNum, $totalPay);
        $sheet->setCellValue('J' . $rowNum, getApprovedOTExport($row));

        $rowNum++;
    }

    // Grand total row - only for Total Pay column
    $sheet->setCellValue('A' . $rowNum, 'Grand Total');
    $sheet->setCellValue('I' . $rowNum, $grandTotalPay);
    $sheet->getStyle('A' . $rowNum . ':J' . $rowNum)->getFont()->setBold(true);

    // Format Total Pay column as currency
    $sheet->getStyle('I2:I' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

    // Column widths
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(12);
    $sheet->getColumnDimension('C')->setWidth(8);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(10);
    $sheet->getColumnDimension('G')->setWidth(14);
    $sheet->getColumnDimension('H')->setWidth(10);
    $sheet->getColumnDimension('I')->setWidth(14);
    $sheet->getColumnDimension('J')->setWidth(14);

    // Freeze header row
    $sheet->freezePane('A2');

    // Clear output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'attendance_view_' . date('Ymd_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require "includes/main_header.php";
$trncde = "ATT"; // Attendance transaction code
?>

<style>
    .attendance-view-page {
        --av-border: #dbe4f0;
        --av-border-strong: #cfd9e6;
        --av-text: #0f172a;
        --av-muted: #64748b;
        --av-surface: #ffffff;
        --av-primary: #3b82f6;
        --av-primary-dark: #2563eb;
        --av-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .attendance-view-page,
    .attendance-view-page .form-control,
    .attendance-view-page .btn {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    .attendance-view-page .page-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 18px;
    }

    .attendance-view-page .page-header-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: rgba(59, 130, 246, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .attendance-view-page .page-header-icon i {
        font-size: 20px;
        color: var(--av-primary);
    }

    .attendance-view-page .page-header-text h2 {
        margin: 0;
        font-size: 22px;
        line-height: 1.2;
        font-weight: 700;
        color: var(--av-text);
    }

    .attendance-view-page .page-header-text p {
        margin: 2px 0 0;
        font-size: 13px;
        color: var(--av-muted);
    }

    .attendance-view-page .attendance-records-card {
        background: var(--av-surface);
        border: 1px solid var(--av-border);
        border-radius: 16px;
        box-shadow: var(--av-shadow);
        overflow: hidden;
    }

    .attendance-view-page .attendance-card-header {
        padding: 18px 18px 14px;
        border-bottom: 1px solid var(--av-border);
    }

    .attendance-view-page .attendance-card-topbar,
    .attendance-view-page .attendance-filters-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .attendance-view-page .attendance-filters-row {
        margin-top: 14px;
        flex-wrap: wrap;
    }

    .attendance-view-page .attendance-card-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--av-text);
    }

    .attendance-view-page .attendance-search-shell {
        position: relative;
        width: min(100%, 290px);
        flex-shrink: 0;
    }

    .attendance-view-page .attendance-search-shell i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--av-muted);
        font-size: 13px;
        pointer-events: none;
    }

    .attendance-view-page .attendance-control {
        height: 38px;
        border: 1px solid var(--av-border-strong);
        border-radius: 10px;
        padding: 8px 12px;
        color: var(--av-text);
        box-shadow: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .attendance-view-page .attendance-search-input {
        padding-left: 34px;
    }

    .attendance-view-page .attendance-control:focus {
        border-color: rgba(59, 130, 246, 0.55);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .attendance-view-page .attendance-date-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .attendance-view-page .search-export-controls {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        margin-left: auto;
    }

    .attendance-view-page .attendance-filter-label,
    .attendance-view-page .attendance-date-separator {
        font-size: 13px;
        color: var(--av-muted);
        margin: 0;
    }

    .attendance-view-page .attendance-date-inputs {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .attendance-view-page .attendance-date-input {
        width: 150px;
    }

    .attendance-view-page .attendance-clear-btn {
        border: 0;
        background: transparent;
        padding: 0;
        font-size: 12px;
        color: var(--av-muted);
        text-decoration: underline;
    }

    .attendance-view-page .attendance-clear-btn:hover {
        color: #dc3545;
    }

    .attendance-view-page .attendance-export-btn {
        height: 38px;
        border-radius: 10px;
        padding: 0 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #1f9d62;
        border: 1px solid #1f9d62;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        box-shadow: none;
    }

    .attendance-view-page .attendance-export-btn:hover,
    .attendance-view-page .attendance-export-btn:focus {
        background: #188553;
        border-color: #188553;
        color: #fff;
    }

    .attendance-view-page .attendance-card-body {
        padding: 0;
    }

    .attendance-view-page .attendance-table-wrap,
    .attendance-view-page #records_table,
    .attendance-view-page #mobile_records_list {
        transition: none !important;
        animation: none !important;
    }

    .attendance-view-page #loading_indicator {
        display: none !important;
    }

    .attendance-view-page .attendance-empty-state {
        padding: 48px 18px;
        text-align: center;
        color: var(--av-muted);
        font-size: 14px;
    }

    .attendance-view-page .table-responsive {
        margin: 0;
    }

    .attendance-view-page .data_table {
        width: 100%;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        color: var(--av-text);
    }

    .attendance-view-page .data_table thead th {
        background: transparent;
        border-bottom: 1px solid var(--av-border);
        padding: 14px 14px;
        font-size: 12.5px;
        font-weight: 500;
        color: #475569;
        white-space: nowrap;
        text-align: left;
    }

    .attendance-view-page .data_table tbody td {
        border-bottom: 1px solid var(--av-border);
        padding: 16px 14px;
        font-size: 13px;
        color: var(--av-text);
        vertical-align: middle;
        background: #fff;
    }

    .attendance-view-page .data_table tbody tr:last-child td {
        border-bottom: 0;
    }

    .attendance-view-page .data_table tbody tr.row-absent td {
        background: rgba(248, 113, 113, 0.12);
    }

    .attendance-view-page .data_table tbody tr.row-incomplete td {
        background: rgba(254, 240, 138, 0.55);
    }

    .attendance-view-page .attendance-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 22px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        line-height: 1;
        font-weight: 500;
        white-space: nowrap;
    }

    .attendance-view-page .badge-success {
        background: #dcfce7;
        color: #15803d;
    }

    .attendance-view-page .badge-warning {
        background: #fef3c7;
        color: #b45309;
    }

    .attendance-view-page .badge-danger {
        background: #fee2e2;
        color: #b91c1c;
    }

    .attendance-view-page .badge-info {
        background: #dbeafe;
        color: #0369a1;
    }

    .attendance-view-page .badge-secondary {
        background: #f1f5f9;
        color: #475569;
    }

    .attendance-view-page .badge-orange {
        background: #ffedd5;
        color: #ea580c;
    }

    .attendance-view-page .badge-purple {
        background: #f3e8ff;
        color: #7c3aed;
    }

    .attendance-view-page .attendance-text-strong {
        font-weight: 600;
    }

    .attendance-view-page .btn-view {
        border: 0;
        background: transparent;
        color: #475569;
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .attendance-view-page .btn-view:hover {
        background: rgba(59, 130, 246, 0.08);
        color: var(--av-primary-dark);
    }

    .attendance-view-page .pagination-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 18px 18px;
        border-top: 1px solid var(--av-border);
    }

    .attendance-view-page .records-info {
        margin: 0;
        font-size: 13px;
        color: var(--av-muted);
    }

    .attendance-view-page .pagination {
        gap: 6px;
    }

    .attendance-view-page .page-item .page-link {
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border-radius: 8px;
        border: 1px solid var(--av-border);
        color: #334155;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: none;
        background: #fff;
    }

    .attendance-view-page .page-item.active .page-link {
        background: var(--av-primary);
        border-color: var(--av-primary);
        color: #fff;
    }

    .attendance-view-page .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f8fafc;
        border-color: var(--av-border);
    }

    .attendance-view-page .attendance-mobile-list {
        display: none;
        padding: 12px;
        gap: 12px;
    }

    .attendance-view-page .attendance-mobile-card {
        background: #fff;
        border: 1px solid var(--av-border);
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    .attendance-view-page .attendance-mobile-card.row-absent {
        background: rgba(248, 113, 113, 0.12);
        border-color: rgba(248, 113, 113, 0.25);
    }

    .attendance-view-page .attendance-mobile-card.row-incomplete {
        background: rgba(254, 240, 138, 0.42);
        border-color: rgba(245, 158, 11, 0.22);
    }

    .attendance-view-page .attendance-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }

    .attendance-view-page .attendance-mobile-name {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--av-text);
    }

    .attendance-view-page .attendance-mobile-subtext {
        margin: 3px 0 0;
        font-size: 12px;
        color: var(--av-muted);
    }

    .attendance-view-page .attendance-mobile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 10px;
        margin-bottom: 12px;
    }

    .attendance-view-page .attendance-mobile-item {
        font-size: 12px;
        color: var(--av-muted);
    }

    .attendance-view-page .attendance-mobile-item strong {
        display: block;
        font-size: 13px;
        color: var(--av-text);
        margin-top: 2px;
    }

    .attendance-view-page .attendance-mobile-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .attendance-view-page .attendance-mobile-badges {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .attendance-view-page .attendance-mobile-pay {
        font-size: 15px;
        font-weight: 700;
        color: var(--av-text);
        white-space: nowrap;
    }

    .attendance-view-modal .modal-dialog {
        max-width: 840px;
    }

    .attendance-view-modal .modal-content {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.24);
        overflow: hidden;
    }

    .attendance-view-modal .modal-header {
        padding: 22px 24px 0;
        border-bottom: 0;
        align-items: flex-start;
    }

    .attendance-view-modal .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--av-text);
    }

    .attendance-view-modal .modal-body {
        padding: 18px 24px 22px;
    }

    .attendance-view-modal .modal-footer {
        padding: 0 24px 24px;
        border-top: 0;
        justify-content: flex-end;
    }

    .attendance-view-modal .btn-close {
        opacity: 0.7;
        box-shadow: none;
    }

    .attendance-view-modal .attendance-modal-field label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 500;
        color: var(--av-text);
    }

    .attendance-view-modal .attendance-modal-control,
    .attendance-view-modal .attendance-modal-control.form-control {
        height: 44px;
        border-radius: 10px;
        border: 1px solid var(--av-border);
        background: #f8fafc !important;
        color: var(--av-text) !important;
        cursor: not-allowed;
        opacity: 1;
    }

    .attendance-view-modal textarea.attendance-modal-control {
        height: auto;
        min-height: 96px;
        padding-top: 10px;
        resize: vertical;
    }

    .attendance-view-modal .attendance-modal-help {
        margin-top: 6px;
        font-size: 12px;
        color: var(--av-muted);
    }

    .attendance-view-modal .attendance-modal-section {
        border: 1px solid var(--av-border);
        border-radius: 12px;
        padding: 11px 14px;
        background: #fff;
    }

    .attendance-view-modal .attendance-modal-section-danger {
        border-color: rgba(248, 113, 113, 0.45);
        background: rgba(254, 242, 242, 0.7);
    }

    .attendance-view-modal .attendance-modal-section-primary {
        border-color: rgba(59, 130, 246, 0.35);
        background: rgba(239, 246, 255, 0.9);
    }

    .attendance-view-modal .attendance-modal-section .form-check {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 24px;
    }

    .attendance-view-modal .attendance-modal-section .form-check-input {
        margin: 0;
        cursor: not-allowed;
    }

    .attendance-view-modal .attendance-modal-section .form-check-label {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
    }

    .attendance-view-modal .attendance-modal-section-danger .form-check-label {
        color: #ef4444;
    }

    .attendance-view-modal .attendance-modal-section-primary .form-check-label {
        color: #2563eb;
    }

    .attendance-view-modal .attendance-modal-currency {
        position: relative;
    }

    .attendance-view-modal .attendance-modal-currency span {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 13px;
        color: var(--av-muted);
    }

    .attendance-view-modal .attendance-modal-currency .attendance-modal-control {
        padding-left: 28px;
    }

    .attendance-view-modal #view_total_deductions {
        background: #fef2f2 !important;
        border-color: #fecaca;
    }

    .attendance-view-modal #view_total_day_pay {
        background: #eff6ff !important;
        border-color: #bfdbfe;
        font-weight: 600;
    }

    .attendance-view-modal .attendance-modal-button {
        height: 38px;
        padding: 0 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
    }

    @media (max-width: 991.98px) {
        .attendance-view-page .attendance-card-topbar,
        .attendance-view-page .attendance-filters-row {
            align-items: stretch;
            flex-direction: column;
        }

        .attendance-view-page .attendance-search-shell,
        .attendance-view-page .attendance-export-btn {
            width: 100%;
        }

        .attendance-view-page .attendance-export-wrap {
            width: 100%;
        }

        .attendance-view-page .attendance-export-btn {
            justify-content: center;
        }
    }

    @media (max-width: 767.98px) {
        .attendance-view-page .page-header {
            align-items: flex-start;
        }

        .attendance-view-page .page-header-text h2 {
            font-size: 20px;
        }

        .attendance-view-page .attendance-card-header {
            padding: 16px 14px 12px;
        }

        .attendance-view-page .attendance-date-input {
            width: 100%;
        }

        .attendance-view-page .attendance-date-inputs {
            display: grid;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .attendance-view-page .attendance-date-separator {
            display: none;
        }

        .attendance-view-page .attendance-desktop-table {
            display: none;
        }

        .attendance-view-page .attendance-mobile-list {
            display: grid;
        }

        .attendance-view-page .pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
            padding: 14px;
        }

        .attendance-view-page .pagination {
            justify-content: center;
            flex-wrap: wrap;
        }

        .attendance-view-page .records-info {
            text-align: center;
        }

        .attendance-view-modal .modal-dialog {
            margin: 0.75rem;
        }

        .attendance-view-modal .modal-header,
        .attendance-view-modal .modal-body,
        .attendance-view-modal .modal-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        .attendance-view-modal .modal-footer {
            padding-bottom: 16px;
        }
    }

    @media (min-width: 992px) {
        .attendance-view-page .search-export-controls {
            flex-wrap: nowrap;
        }

        .attendance-view-page .search-export-controls .attendance-search-shell,
        .attendance-view-page .search-export-controls .attendance-export-wrap,
        .attendance-view-page .search-export-controls .attendance-export-btn {
            width: auto;
            margin: 0;
        }
    }

    @media (max-width: 576px) {
        .attendance-view-page .search-export-controls {
            width: 100%;
            max-width: 100%;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-items: stretch;
            margin-left: 0;
            margin-right: 0;
            min-width: 0;
        }

        .attendance-view-page .search-export-controls .attendance-search-shell {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            flex: 1 1 100%;
        }

        .attendance-view-page .search-export-controls .attendance-search-input {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .attendance-view-page .search-export-controls .attendance-export-wrap,
        .attendance-view-page .search-export-controls .attendance-export-btn {
            max-width: 100%;
        }
    }
</style>

<form name='myforms' id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)">
    <table class='big_table'>
        <tr colspan=1>
            <td colspan=1 class='td_bl'>
                <?php require 'includes/main_menu.php'; ?>
            </td>

            <td colspan=1 class="td_br" id="td_br">
                <div class="container-fluid pt-3 main_br_div attendance-view-page" style="max-width: 1400px;">

                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="page-header-icon">
                            <i class="fa fa-calendar-check"></i>
                        </div>
                        <div class="page-header-text">
                            <h2>Timekeeping</h2>
                            <p>Track attendance & hours</p>
                        </div>
                    </div>

                    <div class="attendance-records-card">
                        <div class="attendance-card-header">
                            <div class="attendance-card-topbar">
                                <h5 class="attendance-card-title">Records</h5>
                            </div>
                            <div class="attendance-filters-row">
                                <div class="attendance-date-group">
                                    <span class="attendance-filter-label">Date:</span>
                                    <div class="attendance-date-inputs">
                                        <input type="date" class="form-control attendance-control attendance-date-input" id="date_from">
                                        <span class="attendance-date-separator">-</span>
                                        <input type="date" class="form-control attendance-control attendance-date-input" id="date_to">
                                    </div>
                                    <button type="button" class="attendance-clear-btn" id="btn_clear_dates" style="display: none;">Clear</button>
                                </div>
                                <div class="search-export-controls">
                                    <div class="attendance-search-shell">
                                        <i class="fa fa-search"></i>
                                        <input type="text" class="form-control attendance-control attendance-search-input" id="search_input" placeholder="Search...">
                                    </div>
                                    <div class="attendance-export-wrap">
                                        <button type="button" class="btn attendance-export-btn" id="btn_export_xlsx">
                                            <i class="fa fa-file-excel"></i>
                                            <span>Export XLSX</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="attendance-card-body">
                            <div id="loading_indicator"></div>
                            <div id="no_records" class="attendance-empty-state" style="display: none;">
                                <span>No records found</span>
                            </div>
                            <div class="attendance-table-wrap">
                                <div class="table-responsive attendance-desktop-table">
                                    <table class="data_table attendance-table" id="records_table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Date</th>
                                                <th>Day</th>
                                                <th>Time In</th>
                                                <th>Time Out</th>
                                                <th>Hours</th>
                                                <th>Status</th>
                                                <th>Net OT</th>
                                                <th>Total Pay</th>
                                                <th>Approved OT</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="records_tbody">
                                            <!-- Records will be loaded here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                                <div id="mobile_records_list" class="attendance-mobile-list">
                                    <!-- Mobile records will be loaded here via AJAX -->
                                </div>
                            </div>

                            <div class="pagination-wrapper">
                                <p class="records-info" id="records_info">0-0 of 0</p>
                                <nav>
                                    <ul class="pagination mb-0" id="pagination_list">
                                        <!-- Pagination will be generated here -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>

                </div>
            </td>
        </tr>
    </table>
</form>

<!-- View Modal -->
<div class="modal fade attendance-view-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">View Timekeeping Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Employee</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_employee_name" disabled>
                            <div class="attendance-modal-help" id="view_employee_meta">Employee ID: -</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Date</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_trn_date" disabled>
                            <div class="attendance-modal-help" id="view_date_meta">Day: -</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="attendance-modal-section attendance-modal-section-danger">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="view_is_absent_toggle" disabled>
                                <label class="form-check-label" for="view_is_absent_toggle">Mark as Absent (Skip time inputs)</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Time In</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_time_log_in" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Time Out</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_time_log_out" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Total Hours</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_total_hours" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Late (minutes)</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_late_min" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Overtime (minutes)</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_overtime_min" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Undertime Minutes</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_undertime_min" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Net Overtime Minutes</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_net_overtime_min" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Pay Multiplier</label>
                            <input type="text" class="form-control attendance-modal-control" id="view_pay_multiplier" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Set Bonus (from Employee Profile)</label>
                            <div class="attendance-modal-currency">
                                <span>&#8369;</span>
                                <input type="text" class="form-control attendance-modal-control" id="view_set_bonus" disabled>
                            </div>
                            <div class="attendance-modal-help">Fixed daily bonus from employee profile (not editable)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Other Bonus</label>
                            <div class="attendance-modal-currency">
                                <span>&#8369;</span>
                                <input type="text" class="form-control attendance-modal-control" id="view_custom_add_pay" disabled>
                            </div>
                            <div class="attendance-modal-help">Additional pay (e.g. allowance, incentive)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Total Deductions</label>
                            <div class="attendance-modal-currency">
                                <span>&#8369;</span>
                                <input type="text" class="form-control attendance-modal-control" id="view_total_deductions" disabled>
                            </div>
                            <div class="attendance-modal-help">Deductions (e.g. cash advances, SSS, loans)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="attendance-modal-field">
                            <label>Total Day Pay (Auto-calculated)</label>
                            <div class="attendance-modal-currency">
                                <span>&#8369;</span>
                                <input type="text" class="form-control attendance-modal-control" id="view_total_day_pay" disabled>
                            </div>
                            <div class="attendance-modal-help">Auto-calculated total day pay</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="attendance-modal-section attendance-modal-section-primary">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="view_is_approved_ot_toggle" disabled>
                                <label class="form-check-label" for="view_is_approved_ot_toggle">Approve Overtime</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="attendance-modal-field">
                            <label>Remarks</label>
                            <textarea class="form-control attendance-modal-control" id="view_remarks" rows="3" disabled readonly></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary attendance-modal-button" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let currentPage = 1;
let itemsPerPage = 10;
let totalRecords = 0;
let searchTimeout = null;
let allRecords = []; // Store all records for modal view

// Format time to 12-hour AM/PM format
function formatTime(time) {
    if (!time) return '';
    const parts = time.split(':');
    let hour = parseInt(parts[0], 10);
    const minutes = parts[1] || '00';
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    if (hour === 0) hour = 12;
    return hour + ':' + minutes + ' ' + ampm;
}

function formatCurrency(amount) {
    return '&#8369;' + parseFloat(amount || 0).toFixed(2);
}

// Get status text and badge class
function getStatusMeta(record) {
    if (record.is_absent === 'Yes') {
        return { label: 'Absent', className: 'badge-danger' };
    }
    if (record.is_complete_inout === 'in_missing') {
        return { label: 'Missing In', className: 'badge-orange' };
    }
    if (record.is_complete_inout === 'out_missing') {
        return { label: 'Missing Out', className: 'badge-purple' };
    }
    if (record.is_late === 'Yes') {
        return { label: 'Late', className: 'badge-warning' };
    }
    return { label: 'On Time', className: 'badge-success' };
}

function getStatusBadge(record) {
    const status = getStatusMeta(record);
    return '<span class="attendance-badge ' + status.className + '">' + status.label + '</span>';
}

// Get approved OT badge
function getApprovedOTMeta(record) {
    if (parseInt(record.overtime_min, 10) === 0) {
        return { label: 'No OT', className: 'badge-secondary' };
    }
    if (record.is_approved_ot === 'Yes') {
        return { label: 'Approved', className: 'badge-info' };
    }
    return { label: 'Not Approved', className: 'badge-orange' };
}

function getApprovedOTBadge(record) {
    const approvedOT = getApprovedOTMeta(record);
    return '<span class="attendance-badge ' + approvedOT.className + '">' + approvedOT.label + '</span>';
}

// Format date for display
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US');
}

// Get row class based on status
function getRowClass(record) {
    if (record.is_absent === 'Yes') {
        return 'row-absent';
    }
    if (!record.time_log_in || !record.time_log_out) {
        return 'row-incomplete';
    }
    return '';
}

// Load records via AJAX
function loadRecords() {
    const search = $('#search_input').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();
    $('#no_records').hide();

    $.ajax({
        url: 'attendance_view_ajax.php',
        type: 'GET',
        data: {
            action: 'list',
            page: currentPage,
            limit: itemsPerPage,
            search: search,
            dateFrom: dateFrom,
            dateTo: dateTo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                allRecords = response.data || [];
                totalRecords = response.total || 0;
                renderTable(allRecords);
                renderPagination();
                updateRecordsInfo();

                if (allRecords.length === 0) {
                    $('#no_records').show();
                    $('#records_table').css('display', 'none');
                    $('#mobile_records_list').css('display', 'none');
                } else {
                    $('#no_records').hide();
                    $('#records_table').removeAttr('style');
                    $('#mobile_records_list').removeAttr('style');
                }
            } else {
                alert('Error loading records: ' + (response.message || 'Unknown error'));
                $('#no_records').show();
                $('#records_table').css('display', 'none');
                $('#mobile_records_list').css('display', 'none');
            }
        },
        error: function() {
            $('#no_records').show();
            $('#records_table').css('display', 'none');
            $('#mobile_records_list').css('display', 'none');
        }
    });
}

// Render table rows
function renderTable(records) {
    const tbody = $('#records_tbody');
    const mobileList = $('#mobile_records_list');
    tbody.empty();
    mobileList.empty();

    records.forEach(function(record) {
        const rowClass = getRowClass(record);
        const totalPay = parseFloat(record.total_day_pay || 0).toFixed(2);
        const hours = parseFloat(record.total_hours || 0).toFixed(1);
        const dayAbbr = record.day_of_week ? record.day_of_week.substring(0, 3) : '';
        const statusBadge = getStatusBadge(record);
        const approvedOTBadge = getApprovedOTBadge(record);

        const row = `
            <tr class="${rowClass}" data-recid="${record.recid}">
                <td>${record.employee_name || ''}</td>
                <td>${formatDate(record.trn_date)}</td>
                <td>${dayAbbr}</td>
                <td>${formatTime(record.time_log_in)}</td>
                <td>${formatTime(record.time_log_out)}</td>
                <td>${hours}h</td>
                <td>${statusBadge}</td>
                <td>${record.net_overtime_min || 0}</td>
                <td class="attendance-text-strong">${formatCurrency(totalPay)}</td>
                <td>${approvedOTBadge}</td>
                <td class="text-end">
                    <button type="button" class="btn-view" onclick="viewRecord(${record.recid})" title="View">
                        <i class="fa fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);

        const mobileCard = `
            <div class="attendance-mobile-card ${rowClass}" data-recid="${record.recid}">
                <div class="attendance-mobile-header">
                    <div>
                        <p class="attendance-mobile-name">${record.employee_name || ''}</p>
                        <p class="attendance-mobile-subtext">${formatDate(record.trn_date)}${dayAbbr ? ' - ' + dayAbbr : ''}</p>
                    </div>
                    <button type="button" class="btn-view" onclick="viewRecord(${record.recid})" title="View">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="attendance-mobile-grid">
                    <div class="attendance-mobile-item">Time In<strong>${formatTime(record.time_log_in) || '-'}</strong></div>
                    <div class="attendance-mobile-item">Time Out<strong>${formatTime(record.time_log_out) || '-'}</strong></div>
                    <div class="attendance-mobile-item">Hours<strong>${hours}h</strong></div>
                    <div class="attendance-mobile-item">Net OT<strong>${record.net_overtime_min || 0}</strong></div>
                </div>
                <div class="attendance-mobile-footer">
                    <div class="attendance-mobile-badges">
                        ${statusBadge}
                        ${approvedOTBadge}
                    </div>
                    <div class="attendance-mobile-pay">${formatCurrency(totalPay)}</div>
                </div>
            </div>
        `;
        mobileList.append(mobileCard);
    });
}

// Render pagination
function renderPagination() {
    const totalPages = Math.ceil(totalRecords / itemsPerPage);
    const paginationList = $('#pagination_list');
    paginationList.empty();

    if (totalPages <= 1) return;

    // Previous button
    paginationList.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;" aria-label="Previous page">
                <i class="fa fa-chevron-left"></i>
            </a>
        </li>
    `);

    // Page numbers (show max 5)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);

    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationList.append(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
            </li>
        `);
    }

    // Next button
    paginationList.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;" aria-label="Next page">
                <i class="fa fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// Update records info
function updateRecordsInfo() {
    const start = totalRecords === 0 ? 0 : ((currentPage - 1) * itemsPerPage) + 1;
    const end = Math.min(currentPage * itemsPerPage, totalRecords);
    $('#records_info').text(`${start}-${end} of ${totalRecords}`);
}

// Go to specific page
function goToPage(page) {
    const totalPages = Math.ceil(totalRecords / itemsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadRecords();
}

// View record in modal
function viewRecord(recid) {
    const record = allRecords.find(r => parseInt(r.recid, 10) === parseInt(recid, 10));

    if (!record) {
        // If not found in current page, fetch from server
        $.ajax({
            url: 'attendance_view_ajax.php',
            type: 'GET',
            data: {
                action: 'get',
                recid: recid
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    populateModal(response.data);
                    $('#viewModal').modal('show');
                } else {
                    alert('Error loading record');
                }
            },
            error: function() {
                alert('Error loading record');
            }
        });
    } else {
        populateModal(record);
        $('#viewModal').modal('show');
    }
}

// Populate modal with record data
function populateModal(record) {
    $('#view_employee_name').val(record.employee_name || '');
    $('#view_employee_meta').text('Employee ID: ' + (record.emp_id || '-'));
    $('#view_trn_date').val(record.trn_date ? formatDate(record.trn_date) : '');
    $('#view_date_meta').text('Day: ' + (record.day_of_week || '-'));
    $('#view_time_log_in').val(formatTime(record.time_log_in) || '--:-- --');
    $('#view_time_log_out').val(formatTime(record.time_log_out) || '--:-- --');
    $('#view_total_hours').val(parseFloat(record.total_hours || 0).toFixed(2));
    $('#view_late_min').val(record.late_min || 0);
    $('#view_overtime_min').val(record.overtime_min || 0);
    $('#view_undertime_min').val(record.undertime_min || 0);
    $('#view_net_overtime_min').val(record.net_overtime_min || 0);
    $('#view_pay_multiplier').val(record.pay_multiplier || 1);
    $('#view_set_bonus').val(parseFloat(record.set_bonus || 0).toFixed(2));
    $('#view_custom_add_pay').val(parseFloat(record.custom_add_pay || 0).toFixed(2));
    $('#view_total_deductions').val(parseFloat(record.total_deductions || 0).toFixed(2));
    $('#view_total_day_pay').val(parseFloat(record.total_day_pay || 0).toFixed(2));
    $('#view_remarks').val(record.remarks || '');
    $('#view_is_absent_toggle').prop('checked', record.is_absent === 'Yes');
    $('#view_is_approved_ot_toggle').prop('checked', record.is_approved_ot === 'Yes');
}

// Export to XLSX
function exportXlsx() {
    const search = $('#search_input').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();

    const params = new URLSearchParams({
        export: 'xlsx',
        search: search,
        dateFrom: dateFrom,
        dateTo: dateTo
    });

    window.open('attendance_view.php?' + params.toString(), '_blank');
}

// Document ready
$(document).ready(function() {
    // Initial load
    loadRecords();
    updateClearButton();

    // Search input with debounce
    $('#search_input').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadRecords();
        }, 300);
    });

    // Date filters
    $('#date_from, #date_to').on('change', function() {
        currentPage = 1;
        loadRecords();
        updateClearButton();
    });

    // Clear dates button
    $('#btn_clear_dates').on('click', function() {
        $('#date_from').val('');
        $('#date_to').val('');
        currentPage = 1;
        loadRecords();
        updateClearButton();
    });

    // Export XLSX button
    $('#btn_export_xlsx').on('click', function() {
        exportXlsx();
    });
});

// Update clear button visibility
function updateClearButton() {
    if ($('#date_from').val() || $('#date_to').val()) {
        $('#btn_clear_dates').show();
    } else {
        $('#btn_clear_dates').hide();
    }
}
</script>

<?php
require "includes/main_footer.php";
?>
