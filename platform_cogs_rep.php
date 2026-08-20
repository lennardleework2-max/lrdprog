<?php
    /**
     * Platform COGS Report - PDF and XLS output
     * Based on critical_pdf_rep.php header style and sales_trndate_rep.php report behavior
     *
     * COGS CALCULATION LOGIC:
     * ======================
     * For sales transactions (trncde = 'SAL'), tranfile2.stkqty is ALWAYS NEGATIVE.
     * To get the correct sold quantity, we multiply stkqty by -1:
     *   sold_qty = stkqty * -1
     *
     * Then COGS is calculated as:
     *   line_cogs = latest_purchase_cost * sold_qty
     *   platform_cogs = SUM(line_cogs)
     *
     * Example:
     *   Sales stkqty = -100
     *   sold_qty = -100 * -1 = 100
     *   latest_purchase_cost = 10
     *   line_cogs = 10 * 100 = 1,000
     *
     * =============================================================================
     * MANUAL SQL TEST QUERIES FOR SHOPEE (CUS-00010)
     * =============================================================================
     *
     * 1. Test Shopee COGS WITH date filters (replace YYYY-MM-DD with actual dates):
     *
     * SELECT
     *     x.Platform,
     *     SUM(COALESCE(x.latest_purchase_cost, 0) * COALESCE(x.sold_qty, 0)) AS COGS
     * FROM (
     *     SELECT
     *         cf.cusdsc AS Platform,
     *         s.itmcde,
     *         s.sold_qty,
     *         (
     *             SELECT p2.untprc
     *             FROM tranfile1 p1
     *             LEFT JOIN tranfile2 p2 ON p1.docnum = p2.docnum
     *             WHERE p1.trncde = 'PUR'
     *               AND p2.itmcde = s.itmcde
     *               AND p2.unmcde = (
     *                   SELECT ium.unmcde FROM itemunitmeasurefile ium
     *                   WHERE LOWER(ium.unmdsc) = 'pcs' LIMIT 1
     *               )
     *               AND p1.trndte <= CURDATE()
     *             ORDER BY p1.trndte DESC, p1.docnum DESC
     *             LIMIT 1
     *         ) AS latest_purchase_cost
     *     FROM customerfile cf
     *     LEFT JOIN (
     *         SELECT
     *             t1.cuscde,
     *             t2.itmcde,
     *             (COALESCE(t2.stkqty, 0) * -1) AS sold_qty
     *         FROM tranfile1 t1
     *         LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
     *         WHERE t1.trncde = 'SAL'
     *           AND t1.cuscde = 'CUS-00010'
     *           AND t1.trndte >= 'YYYY-MM-DD'
     *           AND t1.trndte <= 'YYYY-MM-DD'
     *     ) s ON cf.cuscde = s.cuscde
     *     WHERE cf.cuscde = 'CUS-00010'
     * ) x
     * GROUP BY x.Platform;
     *
     * 2. Test Shopee COGS WITHOUT date filters:
     *
     * SELECT
     *     x.Platform,
     *     SUM(COALESCE(x.latest_purchase_cost, 0) * COALESCE(x.sold_qty, 0)) AS COGS
     * FROM (
     *     SELECT
     *         cf.cusdsc AS Platform,
     *         s.itmcde,
     *         s.sold_qty,
     *         (
     *             SELECT p2.untprc
     *             FROM tranfile1 p1
     *             LEFT JOIN tranfile2 p2 ON p1.docnum = p2.docnum
     *             WHERE p1.trncde = 'PUR'
     *               AND p2.itmcde = s.itmcde
     *               AND p2.unmcde = (
     *                   SELECT ium.unmcde FROM itemunitmeasurefile ium
     *                   WHERE LOWER(ium.unmdsc) = 'pcs' LIMIT 1
     *               )
     *               AND p1.trndte <= CURDATE()
     *             ORDER BY p1.trndte DESC, p1.docnum DESC
     *             LIMIT 1
     *         ) AS latest_purchase_cost
     *     FROM customerfile cf
     *     LEFT JOIN (
     *         SELECT
     *             t1.cuscde,
     *             t2.itmcde,
     *             (COALESCE(t2.stkqty, 0) * -1) AS sold_qty
     *         FROM tranfile1 t1
     *         LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
     *         WHERE t1.trncde = 'SAL'
     *           AND t1.cuscde = 'CUS-00010'
     *     ) s ON cf.cuscde = s.cuscde
     *     WHERE cf.cuscde = 'CUS-00010'
     * ) x
     * GROUP BY x.Platform;
     *
     * 3. Detailed/Debug SQL to inspect each Shopee sales line (replace YYYY-MM-DD):
     *
     * SELECT
     *     cf.cusdsc AS Platform,
     *     s.docnum AS SalesDocnum,
     *     s.trndte AS SalesDate,
     *     s.itmcde AS ItemCode,
     *     s.raw_stkqty AS RawSalesStkQty,
     *     s.sold_qty AS PositiveSoldQty,
     *     COALESCE((
     *         SELECT p2.untprc
     *         FROM tranfile1 p1
     *         LEFT JOIN tranfile2 p2 ON p1.docnum = p2.docnum
     *         WHERE p1.trncde = 'PUR'
     *           AND p2.itmcde = s.itmcde
     *           AND p2.unmcde = (
     *               SELECT ium.unmcde FROM itemunitmeasurefile ium
     *               WHERE LOWER(ium.unmdsc) = 'pcs' LIMIT 1
     *           )
     *           AND p1.trndte <= CURDATE()
     *         ORDER BY p1.trndte DESC, p1.docnum DESC
     *         LIMIT 1
     *     ), 0) AS LatestPurchaseCost,
     *     COALESCE((
     *         SELECT p2.untprc
     *         FROM tranfile1 p1
     *         LEFT JOIN tranfile2 p2 ON p1.docnum = p2.docnum
     *         WHERE p1.trncde = 'PUR'
     *           AND p2.itmcde = s.itmcde
     *           AND p2.unmcde = (
     *               SELECT ium.unmcde FROM itemunitmeasurefile ium
     *               WHERE LOWER(ium.unmdsc) = 'pcs' LIMIT 1
     *           )
     *           AND p1.trndte <= CURDATE()
     *         ORDER BY p1.trndte DESC, p1.docnum DESC
     *         LIMIT 1
     *     ), 0) * COALESCE(s.sold_qty, 0) AS LineCOGS
     * FROM customerfile cf
     * LEFT JOIN (
     *     SELECT
     *         t1.cuscde,
     *         t1.docnum,
     *         t1.trndte,
     *         t2.itmcde,
     *         t2.stkqty AS raw_stkqty,
     *         (COALESCE(t2.stkqty, 0) * -1) AS sold_qty
     *     FROM tranfile1 t1
     *     LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
     *     WHERE t1.trncde = 'SAL'
     *       AND t1.cuscde = 'CUS-00010'
     *       AND t1.trndte >= 'YYYY-MM-DD'
     *       AND t1.trndte <= 'YYYY-MM-DD'
     * ) s ON cf.cuscde = s.cuscde
     * WHERE cf.cuscde = 'CUS-00010'
     * ORDER BY s.trndte, s.docnum, s.itmcde;
     *
     * =============================================================================
     */

    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    ob_start();

    // Validate session
    if (!isset($_SESSION['userdesc'])) {
        die('Unauthorized access');
    }

    $xreport_title = "Platform COGS Report";

    if (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') {
        $pdf = new tab_ezpdf('Letter', 'landscape');
    } else {
        $pdf = new Cezpdf('Letter', 'landscape');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
    $today_date = date("Y-m-d");

    $xtop = 580;
    $xleft = 25;

    // Process date filters for display
    $date_from_display = '';
    $date_to_display = '';
    $date_from_sql = null;
    $date_to_sql = null;

    if (isset($_POST['date_from']) && !empty($_POST['date_from'])) {
        $date_from_sql = date("Y-m-d", strtotime($_POST['date_from']));
        $date_from_display = date("m/d/Y", strtotime($_POST['date_from']));
    }

    if (isset($_POST['date_to']) && !empty($_POST['date_to'])) {
        $date_to_sql = date("Y-m-d", strtotime($_POST['date_to']));
        $date_to_display = date("m/d/Y", strtotime($_POST['date_to']));
    }

    // Build date filter display string
    $date_filter_display = '';
    if (!empty($date_from_display) && !empty($date_to_display)) {
        $date_filter_display = 'Date Filter: ' . $date_from_display . ' - ' . $date_to_display;
    } elseif (!empty($date_from_display)) {
        $date_filter_display = 'Date Filter: From ' . $date_from_display;
    } elseif (!empty($date_to_display)) {
        $date_filter_display = 'Date Filter: To ' . $date_to_display;
    } else {
        $date_filter_display = 'Date Filter: All Dates';
    }

    // Process platform filter
    $platform_filter = null;
    $platform_filter_display = 'Platform: All';
    if (isset($_POST['platform_filter']) && !empty($_POST['platform_filter'])) {
        // Validate that the cuscde exists in customerfile
        $validate_stmt = $link->prepare("SELECT cuscde, cusdsc FROM customerfile WHERE cuscde = ? LIMIT 1");
        $validate_stmt->execute(array($_POST['platform_filter']));
        $validate_result = $validate_stmt->fetch(PDO::FETCH_ASSOC);
        if ($validate_result) {
            $platform_filter = $validate_result['cuscde'];
            $platform_filter_display = 'Platform: ' . htmlspecialchars($validate_result['cusdsc']);
        }
    }

    /** Header **/
    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Platform COGS Report</b>"), 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Pdf Report by: " . htmlspecialchars($_SESSION['userdesc']) . " (Summarized)</b>"), 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text($date_filter_display), 10, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text($platform_filter_display), 10, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Date Printed : ' . $date_printed), 10, 'left');
    $xtop -= 20;

    $pdf->setLineStyle(.5);
    $pdf->line($xleft, $xtop + 10, 770, $xtop + 10);
    $pdf->line($xleft, $xtop - 3, 770, $xtop - 3);

    // Column headers
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Platform</b>"), 10, 'left');
    $pdf->ezPlaceData(400, $xtop, xls_safe_text("<b>COGS</b>"), 10, 'right');

    $xleft = 25;
    $xtop -= 15;

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');
    /** End Header **/

    // Get the unmcde for 'pcs' unit measure (cached once, not per row)
    $pcs_unmcde = null;
    $stmt_pcs = $link->prepare("SELECT unmcde FROM itemunitmeasurefile WHERE LOWER(unmdsc) = 'pcs' LIMIT 1");
    $stmt_pcs->execute();
    $rs_pcs = $stmt_pcs->fetch(PDO::FETCH_ASSOC);
    if ($rs_pcs) {
        $pcs_unmcde = $rs_pcs['unmcde'];
    }

    // Pre-fetch all purchase costs for optimization
    // Get latest purchase cost per item (pcs unit) up to today
    // Order by trndte DESC, docnum DESC to get the most recent cost
    $cost_cache = array();
    $cost_query = "SELECT t2.itmcde, t2.untprc, t1.trndte, t1.docnum
        FROM tranfile2 t2
        INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
        WHERE t1.trncde = 'PUR'
        AND t1.trndte <= ?
        AND t2.stkqty > 0";

    $cost_params = array($today_date);

    // Add pcs filter if we found the pcs unit code
    if ($pcs_unmcde !== null) {
        $cost_query .= " AND t2.unmcde = ?";
        $cost_params[] = $pcs_unmcde;
    }

    $cost_query .= " ORDER BY t1.trndte DESC, t1.docnum DESC";

    $stmt_cost = $link->prepare($cost_query);
    $stmt_cost->execute($cost_params);

    // Store only the latest cost per item (first occurrence due to ORDER BY)
    while ($cost_row = $stmt_cost->fetch(PDO::FETCH_ASSOC)) {
        $itmcde = $cost_row['itmcde'];
        if (!isset($cost_cache[$itmcde])) {
            $cost_cache[$itmcde] = floatval($cost_row['untprc']);
        }
    }

    // Get customers/platforms (filtered or all)
    $customer_params = array();
    if ($platform_filter !== null) {
        // Single platform selected
        $select_customers = "SELECT cuscde, cusdsc FROM customerfile WHERE cuscde = ? ORDER BY cusdsc ASC";
        $customer_params[] = $platform_filter;
    } else {
        // All platforms
        $select_customers = "SELECT cuscde, cusdsc FROM customerfile ORDER BY cusdsc ASC";
    }
    $stmt_customers = $link->prepare($select_customers);
    $stmt_customers->execute($customer_params);
    $all_customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

    // Build sales filter based on date inputs and platform filter
    $sales_filter = "";
    $sales_params = array();

    // Add platform filter if a specific platform is selected
    if ($platform_filter !== null) {
        $sales_filter .= " AND t1.cuscde = ?";
        $sales_params[] = $platform_filter;
    }

    if ($date_from_sql !== null) {
        $sales_filter .= " AND t1.trndte >= ?";
        $sales_params[] = $date_from_sql;
    }

    if ($date_to_sql !== null) {
        $sales_filter .= " AND t1.trndte <= ?";
        $sales_params[] = $date_to_sql;
    }

    // Pre-fetch sales data grouped by customer (filtered or all)
    // Note: stkqty for SAL transactions is NEGATIVE
    $sales_query = "SELECT t1.cuscde, t2.itmcde, t2.stkqty
        FROM tranfile1 t1
        INNER JOIN tranfile2 t2 ON t1.docnum = t2.docnum
        WHERE t1.trncde = 'SAL'" . $sales_filter . "
        ORDER BY t1.cuscde ASC";

    $stmt_sales = $link->prepare($sales_query);
    $stmt_sales->execute($sales_params);

    // Group sales by customer
    $sales_by_customer = array();
    while ($sales_row = $stmt_sales->fetch(PDO::FETCH_ASSOC)) {
        $cuscde = $sales_row['cuscde'];
        if (!isset($sales_by_customer[$cuscde])) {
            $sales_by_customer[$cuscde] = array();
        }
        $sales_by_customer[$cuscde][] = array(
            'itmcde' => $sales_row['itmcde'],
            'stkqty' => floatval($sales_row['stkqty'])
        );
    }

    // Calculate COGS for each platform and render
    $grand_total_cogs = 0;

    foreach ($all_customers as $customer) {
        $cuscde = $customer['cuscde'];
        $cusdsc = $customer['cusdsc'];

        $platform_cogs = 0;

        // Calculate COGS if customer has sales
        if (isset($sales_by_customer[$cuscde])) {
            foreach ($sales_by_customer[$cuscde] as $sale) {
                $itmcde = $sale['itmcde'];

                // IMPORTANT: SAL stkqty is always NEGATIVE
                // Convert to positive by multiplying by -1
                // Example: stkqty = -100, sold_qty = -100 * -1 = 100
                $sold_qty = $sale['stkqty'] * -1;

                // Get latest purchase cost from cache
                // If no purchase cost found, treat as 0
                $unit_cost = isset($cost_cache[$itmcde]) ? $cost_cache[$itmcde] : 0;

                // Calculate COGS for this line item
                // line_cogs = latest_purchase_cost * sold_qty
                $line_cogs = $unit_cost * $sold_qty;

                $platform_cogs += $line_cogs;
            }
        }

        // Display platform row (show all platforms, even with 0 COGS)
        $xleft = 25;

        // Truncate long platform names for PDF display
        $display_cusdsc = $cusdsc;
        if (get_class($pdf) != 'tab_ezpdf') {
            $display_cusdsc = trim_str($cusdsc, 350, 9);
        } else {
            $display_cusdsc = xls_safe_text($cusdsc);
        }

        $pdf->ezPlaceData($xleft, $xtop, $display_cusdsc, 9, "left");
        $pdf->ezPlaceData(400, $xtop, number_format($platform_cogs, 2), 9, "right");

        $grand_total_cogs += $platform_cogs;

        $xtop -= 15;

        if ($xtop <= 60) {
            $pdf->ezNewPage();
            $xtop = 515;
        }
    }

    // Grand Total
    $pdf->line(25, $xtop, 770, $xtop);
    $xtop -= 15;
    $xleft = 25;

    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Grand Total:</b>"), 9, "left");
    $pdf->ezPlaceData(400, $xtop, "<b>" . number_format($grand_total_cogs, 2) . "</b>", 9, "right");

    $pdf->line(25, $xtop - 5, 770, $xtop - 5);
    $pdf->addText(30, 15, 8, "Date Printed : " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
    $pdf->ezStream();
    ob_end_flush();

    /**
     * Utility function to trim strings for PDF display
     */
    function trim_str($string, $max_wid, $fsize)
    {
        global $pdf;
        if (get_class($pdf) == 'tab_ezpdf') {
            return $string;
        }
        $string = (string)$string;
        if (empty($string)) {
            return $string;
        }
        $xarr_str = str_split($string);
        $max_wid -= 5;
        $xxstr = "";
        $xcut = false;
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
            if ($xstr_wid > $max_wid) {
                $xcut = true;
                break;
            }
            $xxstr = $xxstr . $value;
        }
        if ($xcut) {
            $xxstr = $xxstr . '...';
        }
        return $xxstr;
    }

    /**
     * XLS-safe text encoding: sanitizes text for tab-separated XLS output
     * Handles mojibake, special chars, and non-ASCII that can break Excel layout
     */
    function xls_safe_text($string)
    {
        global $pdf;

        $string = (string)$string;
        if (get_class($pdf) != 'tab_ezpdf') {
            return $string;
        }

        // Try to fix encoding issues first
        if (function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        // Transliterate to ASCII to prevent layout-breaking chars in XLS
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if ($converted !== false && $converted !== '') {
                $string = $converted;
            } else {
                // Fallback: strip all non-printable-ASCII
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        } else {
            // No iconv available: strip all non-printable-ASCII
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        // Remove tabs, line breaks, and control chars that break TSV format
        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return trim($string);
    }
?>
