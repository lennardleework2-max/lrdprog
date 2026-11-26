<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    // ========================================================================
    // PRE-GENERATION FLOW: Handle token-based retrieval of pre-generated report
    // ========================================================================
    if(isset($_GET['token']) && !empty($_GET['token'])){
        $token = $_GET['token'];

        // Validate token format (alphanumeric only)
        if(!preg_match('/^[a-zA-Z0-9]+$/', $token)){
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Invalid Request</title></head><body>';
            echo '<h3>Error</h3><p>Invalid report token.</p>';
            echo '<p><a href="javascript:window.close();">Close this window</a></p>';
            echo '</body></html>';
            exit;
        }

        // Check if token exists in session
        $session_key = 'pregenerated_war_report_' . $token;
        if(!isset($_SESSION[$session_key])){
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Report Expired</title></head><body>';
            echo '<h3>Report Expired</h3><p>The report has expired or is no longer available. Please generate a new report.</p>';
            echo '<p><a href="inventory_balance_by_war.php">Go back to Inventory Balance by Warehouse</a></p>';
            echo '</body></html>';
            exit;
        }

        $report_data = $_SESSION[$session_key];
        $file_path = $report_data['file_path'];
        $is_tab = $report_data['is_tab'];

        // Verify file exists
        if(!file_exists($file_path)){
            unset($_SESSION[$session_key]);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Report Not Found</title></head><body>';
            echo '<h3>Report Not Found</h3><p>The report file could not be found. Please generate a new report.</p>';
            echo '<p><a href="inventory_balance_by_war.php">Go back to Inventory Balance by Warehouse</a></p>';
            echo '</body></html>';
            exit;
        }

        // Serve the pre-generated file
        $content = file_get_contents($file_path);

        // Clean up: delete temp file and session data
        @unlink($file_path);
        unset($_SESSION[$session_key]);

        // Output the content with appropriate headers
        if($is_tab){
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="inventory_balance_by_warehouse_' . date('Y-m-d_H-i-s') . '.xls"');
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="inventory_balance_by_warehouse_' . date('Y-m-d_H-i-s') . '.pdf"');
        }
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $content;
        exit;
    }

    // ========================================================================
    // PRE-GENERATION FLOW: Handle AJAX pre-generation request
    // ========================================================================
    $is_pregenerate = (isset($_POST['pregenerate']) && $_POST['pregenerate'] === '1');

    if($is_pregenerate){
        header('Content-Type: application/json; charset=utf-8');

        // Backend validation: require both warehouse and floor selection
        $validation_errors = array();
        if(!isset($_POST['warehouse']) || trim($_POST['warehouse']) === ''){
            $validation_errors[] = 'Please select a warehouse.';
        }
        if(!isset($_POST['floor']) || trim($_POST['floor']) === ''){
            $validation_errors[] = 'Please select a floor.';
        }
        if(!empty($validation_errors)){
            echo json_encode(array('success' => false, 'error' => implode(' ', $validation_errors)));
            exit;
        }

        // Generate a unique token
        $token = bin2hex(random_bytes(16));
        $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

        // Create temp directory if not exists
        $temp_dir = sys_get_temp_dir() . '/lrdprog_reports';
        if(!is_dir($temp_dir)){
            @mkdir($temp_dir, 0755, true);
        }

        $file_extension = $is_tab_export ? '.xls' : '.pdf';
        $temp_file = $temp_dir . '/inv_balance_war_' . $token . $file_extension;

        // Generate the report
        try {
            $report_content = generate_inventory_balance_by_war_report($link, $_POST, $_SESSION, $is_tab_export);

            // Save to temp file
            if(file_put_contents($temp_file, $report_content) === false){
                echo json_encode(array('success' => false, 'error' => 'Failed to save report. Please try again.'));
                exit;
            }

            // Store reference in session
            $_SESSION['pregenerated_war_report_' . $token] = array(
                'file_path' => $temp_file,
                'is_tab' => $is_tab_export,
                'created_at' => time()
            );

            // Clean up old pre-generated reports (older than 10 minutes)
            foreach($_SESSION as $key => $value){
                if(strpos($key, 'pregenerated_war_report_') === 0 && isset($value['created_at'])){
                    if(time() - $value['created_at'] > 600){
                        if(isset($value['file_path']) && file_exists($value['file_path'])){
                            @unlink($value['file_path']);
                        }
                        unset($_SESSION[$key]);
                    }
                }
            }

            echo json_encode(array('success' => true, 'token' => $token));
            exit;

        } catch(Exception $e) {
            echo json_encode(array('success' => false, 'error' => 'An error occurred while generating the report. Please try again.'));
            exit;
        }
    }

    // ========================================================================
    // DIRECT GENERATION FLOW (legacy fallback)
    // ========================================================================

    // Backend validation: require both warehouse and floor selection
    $validation_errors = array();
    if(!isset($_POST['warehouse']) || trim($_POST['warehouse']) === ''){
        $validation_errors[] = 'Please select a warehouse.';
    }
    if(!isset($_POST['floor']) || trim($_POST['floor']) === ''){
        $validation_errors[] = 'Please select a floor.';
    }
    if(!empty($validation_errors)){
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>Export Error</title></head><body>';
        echo '<h3>Export Error</h3><ul>';
        foreach($validation_errors as $err){
            echo '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        echo '</ul>';
        echo '<p><a href="javascript:window.close();">Close this window</a></p>';
        echo '</body></html>';
        exit;
    }

    ob_start();

    $xreport_title = "Inventory Balance by Warehouse";
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    if ($is_tab_export) {
        $pdf = new tab_ezpdf('Letter', 'portrait');
    } else {
        $pdf = new Cezpdf('Letter', 'portrait');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $xtop = 750;
    $xleft = 25;
    $line_right = 585;
    $col_floor = 25;
    $col_item = 80;
    $col_qty = 280;
    $col_uom = 350;
    $col_balance = 500;
    $floor_max_width = 45;
    $item_max_width = 185;
    $uom_max_width = 130;

    // Build filters with prepared statements
    $xfilter = '';
    $params = array();

    // Date filter
    $display_date = '';
    if (isset($_POST['date_search']) && !empty($_POST['date_search'])) {
        $date_sql = date("Y-m-d", strtotime($_POST['date_search']));
        $xfilter .= " AND tranfile1.trndte <= ?";
        $params[] = $date_sql;
        $display_date = date("m/d/Y", strtotime($date_sql));
    }

    // Warehouse filter
    $display_warehouse = '';
    if (isset($_POST['warehouse']) && !empty($_POST['warehouse'])) {
        $xfilter .= " AND tranfile2.warcde = ?";
        $params[] = $_POST['warehouse'];
        $stmt_war_name = $link->prepare("SELECT warehouse_name FROM warehouse WHERE warcde = ?");
        $stmt_war_name->execute(array($_POST['warehouse']));
        $rs_war_name = $stmt_war_name->fetch();
        if ($rs_war_name) {
            $display_warehouse = $rs_war_name['warehouse_name'];
        }
    }

    // Floor filter
    $display_floor = '';
    if (isset($_POST['floor']) && !empty($_POST['floor'])) {
        $xfilter .= " AND tranfile2.warehouse_floor_id = ?";
        $params[] = $_POST['floor'];
        $stmt_floor_name = $link->prepare("SELECT floor_no, floor_name FROM warehouse_floor WHERE warehouse_floor_id = ?");
        $stmt_floor_name->execute(array($_POST['floor']));
        $rs_floor_name = $stmt_floor_name->fetch();
        if ($rs_floor_name) {
            $display_floor = trim((string)($rs_floor_name['floor_no'] !== '' ? $rs_floor_name['floor_no'] : $rs_floor_name['floor_name']));
        }
    }

    // Build header
    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Inventory Balance by Warehouse</b>"), 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Report by: " . htmlspecialchars($_SESSION['userdesc'] ?? '', ENT_QUOTES, 'UTF-8') . "</b>"), 9, 'left');
    $xtop -= 15;
    if (!empty($display_date)) {
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("As of Date: " . $display_date), 10, 'left');
        $xtop -= 15;
    }
    if (!empty($display_warehouse)) {
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("Warehouse: " . htmlspecialchars($display_warehouse, ENT_QUOTES, 'UTF-8')), 10, 'left');
        $xtop -= 15;
    }
    if (!empty($display_floor)) {
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("Floor: " . htmlspecialchars($display_floor, ENT_QUOTES, 'UTF-8')), 10, 'left');
        $xtop -= 15;
    }
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Date Printed: ' . $date_printed), 10, 'left');
    $xtop -= 15;

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    // Calculate content start position after page header
    $content_start_top = $xtop;

    // FIX 1: Wrap in a subquery so the SUM(CASE...) is computed only once.
    //         The original HAVING block duplicated the entire expression, causing MySQL
    //         to evaluate it twice per grouped row. The outer WHERE filters on the alias.
    //
    // FIX 2: Switched to simple CASE syntax (CASE tranfile1.trncde WHEN 'SAL' ...)
    //         which is marginally faster than searched CASE (CASE WHEN col = val ...).
    $select_db = "
        SELECT *
        FROM (
            SELECT
                warehouse.warcde,
                warehouse.warehouse_name,
                warehouse_floor.warehouse_floor_id,
                warehouse_floor.floor_no,
                tranfile2.itmcde,
                COALESCE(itemfile.itmdsc, tranfile2.itmcde)                         AS item_display,
                tranfile2.unmcde,
                COALESCE(itemunitmeasurefile.unmdsc, tranfile2.unmcde)              AS uom_desc,
                CASE
                    WHEN LOWER(TRIM(COALESCE(itemunitmeasurefile.unmdsc, tranfile2.unmcde))) = 'pcs'
                        THEN 1
                    ELSE COALESCE(itemunitfile.conversion, 1)
                END AS conversion,
                SUM(
                    CASE tranfile1.trncde
                        WHEN 'SAL' THEN -tranfile2.itmqty
                        WHEN 'PUR' THEN  tranfile2.itmqty
                        WHEN 'SRT' THEN  tranfile2.itmqty
                        WHEN 'ADJ' THEN  tranfile2.itmqty
                        WHEN 'STT' THEN
                            CASE
                                WHEN tranfile2.stkqty < 0 THEN -tranfile2.itmqty
                                WHEN tranfile2.stkqty > 0 THEN  tranfile2.itmqty
                                ELSE 0
                            END
                        ELSE 0
                    END
                ) AS uom_balance
            FROM tranfile2
            LEFT JOIN tranfile1           ON tranfile1.docnum             = tranfile2.docnum
            LEFT JOIN warehouse           ON tranfile2.warcde             = warehouse.warcde
            LEFT JOIN warehouse_floor     ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            LEFT JOIN itemfile            ON tranfile2.itmcde             = itemfile.itmcde
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde             = itemunitmeasurefile.unmcde
            LEFT JOIN itemunitfile        ON tranfile2.itmcde             = itemunitfile.itmcde
                                         AND tranfile2.unmcde             = itemunitfile.unmcde
            WHERE tranfile2.warcde IS NOT NULL
                AND tranfile2.warcde != '' {$xfilter}
            GROUP BY
                warehouse.warcde,
                warehouse.warehouse_name,
                warehouse_floor.warehouse_floor_id,
                warehouse_floor.floor_no,
                tranfile2.itmcde,
                itemfile.itmdsc,
                tranfile2.unmcde,
                itemunitmeasurefile.unmdsc,
                itemunitfile.conversion
        ) AS sub
        WHERE uom_balance <> 0
        ORDER BY
            warehouse_name ASC,
            floor_no ASC,
            itmcde ASC,
            uom_balance DESC
    ";

    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute($params);
    $all_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    // Organize data into hierarchical structure: warehouse → floor → item → UOM rows
    // Then sort items by subtotal (highest to lowest) within each floor
    $warehouses = array();
    foreach ($all_rows as $row) {
        $warcde   = $row['warcde'];
        $floor_id = $row['warehouse_floor_id'] ?? '';
        $itmcde   = $row['itmcde'];

        if (!isset($warehouses[$warcde])) {
            $warehouses[$warcde] = array(
                'warehouse_name' => $row['warehouse_name'],
                'floors'         => array()
            );
        }

        if (!isset($warehouses[$warcde]['floors'][$floor_id])) {
            $warehouses[$warcde]['floors'][$floor_id] = array(
                'floor_no' => $row['floor_no'],
                'items'    => array()
            );
        }

        if (!isset($warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde])) {
            $warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde] = array(
                'item_display'  => $row['item_display'],
                'uom_rows'      => array(),
                'subtotal_pcs'  => 0
            );
        }

        $uom_desc    = trim((string)$row['uom_desc']);
        $conversion  = (float)$row['conversion'];
        if ($conversion <= 0) $conversion = 1;
        $uom_balance    = (float)$row['uom_balance'];
        $balance_in_pcs = $uom_balance * $conversion;

        $warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde]['uom_rows'][] = array(
            'uom_desc'       => $uom_desc,
            'conversion'     => $conversion,
            'uom_balance'    => $uom_balance,
            'balance_in_pcs' => $balance_in_pcs
        );

        $warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde]['subtotal_pcs'] += $balance_in_pcs;
    }

    // Sort items by subtotal_pcs descending within each floor
    foreach ($warehouses as $warcde => &$warehouse_data) {
        foreach ($warehouse_data['floors'] as $floor_id => &$floor_data) {
            uasort($floor_data['items'], function($a, $b) {
                $diff = $b['subtotal_pcs'] - $a['subtotal_pcs'];
                if ($diff != 0) {
                    return ($diff > 0) ? 1 : -1;
                }
                return strcasecmp($a['item_display'], $b['item_display']);
            });
        }
    }
    unset($warehouse_data, $floor_data);

    // Render the sorted data
    $has_data        = false;
    $grand_total_pcs = 0;

    foreach ($warehouses as $warcde => $warehouse_data) {
        $warehouse_name      = $warehouse_data['warehouse_name'];
        $warehouse_total_pcs = 0;
        $first_floor_in_warehouse = true;

        foreach ($warehouse_data['floors'] as $floor_id => $floor_data) {
            $floor_no         = $floor_data['floor_no'];
            $floor_total_pcs  = 0;
            $first_item_in_floor = true;

            foreach ($floor_data['items'] as $itmcde => $item_data) {
                $item_display_name = $item_data['item_display'];
                $item_subtotal_pcs = $item_data['subtotal_pcs'];
                $first_uom_in_item = true;

                foreach ($item_data['uom_rows'] as $uom_row) {
                    $has_data = true;

                    // Print warehouse header on first data row of warehouse
                    if ($first_floor_in_warehouse && $first_item_in_floor && $first_uom_in_item) {
                        if (($xtop - 50) <= 60) {
                            $pdf->ezNewPage();
                            $xtop = $content_start_top;
                        }

                        $pdf->ezPlaceData($col_floor, $xtop, xls_safe_text("<b>" . htmlspecialchars($warehouse_name, ENT_QUOTES, 'UTF-8') . "</b>"), 11, 'left');
                        $xtop -= 15;

                        $pdf->setLineStyle(.5);
                        $pdf->line($xleft, $xtop + 5, $line_right, $xtop + 5);

                        $pdf->ezPlaceData($col_floor, $xtop - 5, xls_safe_text("<b>Floor</b>"), 10, 'left');
                        $pdf->ezPlaceData($col_item,  $xtop - 5, xls_safe_text("<b>Item</b>"),  10, 'left');
                        $pdf->ezPlaceData($col_qty,   $xtop - 5, xls_safe_text("<b>Qty</b>"),   10, 'right');
                        $pdf->ezPlaceData($col_uom,   $xtop - 5, xls_safe_text("<b>UOM</b>"),   10, 'left');
                        $pdf->ezPlaceData($col_balance, $xtop - 5, xls_safe_text("<b>Balance</b>"), 10, 'right');

                        $pdf->line($xleft, $xtop - 18, $line_right, $xtop - 18);
                        $xtop -= 30;

                        $first_floor_in_warehouse = false;
                    }

                    // Build UOM display with conversion label
                    $uom_desc    = $uom_row['uom_desc'];
                    $conversion  = $uom_row['conversion'];
                    $uom_balance    = $uom_row['uom_balance'];
                    $balance_in_pcs = $uom_row['balance_in_pcs'];

                    $is_pcs = (strtolower($uom_desc) === 'pcs');
                    if ($is_pcs) {
                        $uom_display = 'pcs';
                    } else {
                        $conv_int    = (int)$conversion;
                        $uom_display = $uom_desc . '(' . $conv_int . 'pcs)';
                    }

                    $floor_display = ($first_item_in_floor && $first_uom_in_item)
                        ? (!empty($floor_no) ? $floor_no : '(No Floor)')
                        : '';
                    $item_display  = $first_uom_in_item ? $item_display_name : '';

                    // FIX 3: wrap_report_text() and xls_safe_text() now have internal static
                    //         caches. Item names, floor labels, and UOM strings repeat across
                    //         every row under the same item/floor — caching eliminates redundant
                    //         getTextWidth() character loops and iconv/regex passes.
                    $floor_lines = wrap_report_text($floor_display, $floor_max_width, 9);
                    $item_lines  = wrap_report_text($item_display,  $item_max_width,  9);
                    $uom_lines   = wrap_report_text($uom_display,   $uom_max_width,   9);
                    $line_count  = max(count($floor_lines), count($item_lines), count($uom_lines), 1);
                    $row_height  = 15 + ((max(1, $line_count) - 1) * 10);

                    if (($xtop - $row_height) <= 60) {
                        $pdf->ezNewPage();
                        $xtop = $content_start_top;
                    }

                    $row_y = $xtop;

                    // Floor column
                    foreach ($floor_lines as $floor_line_index => $floor_line_text) {
                        if ($floor_line_text !== '' || ($is_tab_export && $floor_line_index === 0)) {
                            $pdf->ezPlaceData($col_floor, $row_y - ($floor_line_index * 10), xls_safe_text($floor_line_text), 9, 'left');
                        }
                    }
                    if (empty($floor_lines) || $floor_lines[0] === '') {
                        if ($is_tab_export) {
                            $pdf->ezPlaceData($col_floor, $row_y, '', 9, 'left');
                        }
                    }

                    // Item column
                    foreach ($item_lines as $item_line_index => $item_line_text) {
                        if ($item_line_text !== '' || ($is_tab_export && $item_line_index === 0)) {
                            $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), xls_safe_text($item_line_text), 9, 'left');
                        }
                    }
                    if (empty($item_lines) || $item_lines[0] === '') {
                        if ($is_tab_export) {
                            $pdf->ezPlaceData($col_item, $row_y, '', 9, 'left');
                        }
                    }

                    // Qty column
                    $pdf->ezPlaceData($col_qty, $row_y, format_report_balance($uom_balance), 9, 'right');

                    // UOM column
                    foreach ($uom_lines as $uom_line_index => $uom_line_text) {
                        if ($uom_line_text !== '' || ($is_tab_export && $uom_line_index === 0)) {
                            $pdf->ezPlaceData($col_uom, $row_y - ($uom_line_index * 10), xls_safe_text($uom_line_text), 9, 'left');
                        }
                    }

                    // Balance column (in pcs)
                    $pdf->ezPlaceData($col_balance, $row_y, format_report_balance($balance_in_pcs), 9, 'right');

                    $xtop -= $row_height;
                    $first_uom_in_item = false;
                }

                // Print item subtotal after all UOM rows for this item
                if ($item_subtotal_pcs != 0) {
                    print_item_subtotal($xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $item_subtotal_pcs, $is_tab_export, $content_start_top);
                    $xtop -= 18;
                }

                $floor_total_pcs += $item_subtotal_pcs;
                $first_item_in_floor = false;
            }

            // Print floor total after all items in this floor
            if ($floor_total_pcs != 0) {
                print_floor_total($xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $floor_total_pcs, $is_tab_export, $content_start_top);
                $xtop -= 22;
            }

            $warehouse_total_pcs += $floor_total_pcs;
        }

        // Print warehouse total after all floors in this warehouse
        if ($warehouse_total_pcs != 0) {
            print_warehouse_total($xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $warehouse_total_pcs, $warehouse_name, $is_tab_export, $line_right, $content_start_top);
            $xtop -= 35;
        }

        $grand_total_pcs += $warehouse_total_pcs;
    }

    // Print grand total
    if ($has_data) {
        if (($xtop - 25) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        $pdf->setLineStyle(.5);
        $pdf->line($xleft, $xtop - 5, $line_right, $xtop - 5);
        $xtop -= 18;

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor,   $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_item,    $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,     $xtop, '', 9, 'right');
        }
        $pdf->ezPlaceData($col_uom,     $xtop, xls_safe_text("<b>Grand Total(pcs):</b>"), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($grand_total_pcs) . "</b>", 9, 'right');
    } else {
        $pdf->ezPlaceData($col_floor, $xtop, xls_safe_text("No data found."), 9, 'left');
    }

    $pdf->addText(30, 15, 8, "Date Printed: " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
    $pdf->ezStream();
    ob_end_flush();

    function print_item_subtotal(&$xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $item_subtotal_pcs, $is_tab_export, $content_start_top)
    {
        global $pdf;

        if (($xtop - 18) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_item,  $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,   $xtop, '', 9, 'right');
        }
        $pdf->ezPlaceData($col_uom,     $xtop, xls_safe_text("<b>Sub-total(pcs):</b>"), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($item_subtotal_pcs) . "</b>", 9, 'right');
    }

    function print_floor_total(&$xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $floor_total_pcs, $is_tab_export, $content_start_top)
    {
        global $pdf;

        if (($xtop - 20) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_item,  $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,   $xtop, '', 9, 'right');
        }
        $pdf->ezPlaceData($col_uom,     $xtop, xls_safe_text("<b>Total(pcs):</b>"), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($floor_total_pcs) . "</b>", 9, 'right');
    }

    function print_warehouse_total(&$xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $warehouse_total_pcs, $warehouse_name, $is_tab_export, $line_right, $content_start_top)
    {
        global $pdf;

        if (($xtop - 25) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        $pdf->setLineStyle(.3);
        $pdf->line(25, $xtop - 5, $line_right, $xtop - 5);
        $xtop -= 18;

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,   $xtop, '', 9, 'right');
            $pdf->ezPlaceData($col_uom,   $xtop, '', 9, 'left');
        }
        $pdf->ezPlaceData($col_item,    $xtop, xls_safe_text("<b>Total(" . htmlspecialchars($warehouse_name, ENT_QUOTES, 'UTF-8') . ")(pcs):</b>"), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($warehouse_total_pcs) . "</b>", 9, 'right');
    }

    // FIX 3a: Static cache added to wrap_report_text().
    //          Floor labels, item names, and UOM strings repeat across every row of an
    //          item group. Without caching, getTextWidth() loops character-by-character
    //          on the same input every time. Cache makes subsequent calls O(1).
    function wrap_report_text($string, $max_wid, $fsize)
    {
        global $pdf;
        static $cache = [];

        $string = trim((string)$string);
        if ($string === '') {
            return array('');
        }

        $cache_key = $max_wid . '|' . $fsize . '|' . $string;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        if (get_class($pdf) == 'tab_ezpdf') {
            return $cache[$cache_key] = array(xls_safe_text($string));
        }

        $max_wid -= 5;
        if ($pdf->getTextWidth($fsize, $string) <= $max_wid) {
            return $cache[$cache_key] = array($string);
        }

        $wrapped_lines = array();
        $remaining     = $string;

        while ($remaining !== '') {
            if ($pdf->getTextWidth($fsize, $remaining) <= $max_wid) {
                $wrapped_lines[] = $remaining;
                break;
            }

            $line = fit_text_to_width($remaining, $max_wid, $fsize);
            if ($line === '') {
                $line = substr($remaining, 0, 1);
            }

            $last_space = strrpos($line, ' ');
            if ($last_space !== false && $last_space > 0) {
                $candidate_line = rtrim(substr($line, 0, $last_space));
                if ($candidate_line !== '') {
                    $line = $candidate_line;
                }
            }

            $wrapped_lines[] = rtrim($line);
            $remaining       = ltrim(substr($remaining, strlen($line)));
        }

        if (empty($wrapped_lines)) {
            $wrapped_lines[] = $string;
        }

        return $cache[$cache_key] = $wrapped_lines;
    }

    function fit_text_to_width($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr    = "";
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
            if ($xstr_wid > $max_wid) {
                break;
            }
            $xxstr = $xxstr . $value;
        }

        return rtrim($xxstr);
    }

    function format_report_balance($value)
    {
        $formatted_balance = number_format((float)$value, 4, '.', ',');
        $formatted_balance = rtrim(rtrim($formatted_balance, '0'), '.');

        if ($formatted_balance === '-0') {
            $formatted_balance = '0';
        }

        return $formatted_balance;
    }

    // FIX 3b: Static cache added to xls_safe_text().
    //          iconv + multiple regex passes are expensive. The same warehouse names,
    //          item names, and UOM strings are passed in repeatedly — caching makes
    //          all repeat calls instant.
    function xls_safe_text($string)
    {
        global $pdf;
        static $cache = [];

        $string = (string)$string;
        if (get_class($pdf) != 'tab_ezpdf') {
            return $string;
        }

        if (isset($cache[$string])) {
            return $cache[$string];
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if ($converted !== false && $converted !== '') {
                $string = $converted;
            } else {
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        } else {
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return $cache[$string] = trim($string);
    }

    // ========================================================================
    // FUNCTION: Generate Inventory Balance by Warehouse Report (for pre-generation flow)
    // ========================================================================
    function generate_inventory_balance_by_war_report($link, $post_data, $session_data, $is_tab_export) {
        // Initialize global array for tab_ezpdf (XLS mode)
        global $glo_arr, $glo_top;
        $glo_arr = array();
        $glo_top = 0;

        ob_start();

        $xreport_title = "Inventory Balance by Warehouse";

        if ($is_tab_export) {
            $pdf = new tab_ezpdf('Letter', 'portrait');
        } else {
            $pdf = new Cezpdf('Letter', 'portrait');
        }

        $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
        $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);

        date_default_timezone_set('Asia/Manila');
        $date_printed = date("F j, Y h:i:s A");

        $xtop = 750;
        $xleft = 25;
        $line_right = 585;
        $col_floor = 25;
        $col_item = 80;
        $col_qty = 280;
        $col_uom = 350;
        $col_balance = 500;
        $floor_max_width = 45;
        $item_max_width = 185;
        $uom_max_width = 130;

        // Build filters with prepared statements
        $xfilter = '';
        $params = array();

        // Date filter
        $display_date = '';
        if (isset($post_data['date_search']) && !empty($post_data['date_search'])) {
            $date_sql = date("Y-m-d", strtotime($post_data['date_search']));
            $xfilter .= " AND tranfile1.trndte <= ?";
            $params[] = $date_sql;
            $display_date = date("m/d/Y", strtotime($date_sql));
        }

        // Warehouse filter
        $display_warehouse = '';
        if (isset($post_data['warehouse']) && !empty($post_data['warehouse'])) {
            $xfilter .= " AND tranfile2.warcde = ?";
            $params[] = $post_data['warehouse'];
            $stmt_war_name = $link->prepare("SELECT warehouse_name FROM warehouse WHERE warcde = ?");
            $stmt_war_name->execute(array($post_data['warehouse']));
            $rs_war_name = $stmt_war_name->fetch();
            if ($rs_war_name) {
                $display_warehouse = $rs_war_name['warehouse_name'];
            }
        }

        // Floor filter
        $display_floor = '';
        if (isset($post_data['floor']) && !empty($post_data['floor'])) {
            $xfilter .= " AND tranfile2.warehouse_floor_id = ?";
            $params[] = $post_data['floor'];
            $stmt_floor_name = $link->prepare("SELECT floor_no, floor_name FROM warehouse_floor WHERE warehouse_floor_id = ?");
            $stmt_floor_name->execute(array($post_data['floor']));
            $rs_floor_name = $stmt_floor_name->fetch();
            if ($rs_floor_name) {
                $display_floor = trim((string)($rs_floor_name['floor_no'] !== '' ? $rs_floor_name['floor_no'] : $rs_floor_name['floor_name']));
            }
        }

        // Build header
        $xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop, _war_pregen_xls_safe_text($pdf, "<b>Inventory Balance by Warehouse</b>", $is_tab_export), 15, 'left');
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop, _war_pregen_xls_safe_text($pdf, "<b>Report by: " . htmlspecialchars($session_data['userdesc'] ?? '', ENT_QUOTES, 'UTF-8') . "</b>", $is_tab_export), 9, 'left');
        $xtop -= 15;
        if (!empty($display_date)) {
            $pdf->ezPlaceData($xleft, $xtop, _war_pregen_xls_safe_text($pdf, "As of Date: " . $display_date, $is_tab_export), 10, 'left');
            $xtop -= 15;
        }
        if (!empty($display_warehouse)) {
            $pdf->ezPlaceData($xleft, $xtop, _war_pregen_xls_safe_text($pdf, "Warehouse: " . htmlspecialchars($display_warehouse, ENT_QUOTES, 'UTF-8'), $is_tab_export), 10, 'left');
            $xtop -= 15;
        }
        if (!empty($display_floor)) {
            $pdf->ezPlaceData($xleft, $xtop, _war_pregen_xls_safe_text($pdf, "Floor: " . htmlspecialchars($display_floor, ENT_QUOTES, 'UTF-8'), $is_tab_export), 10, 'left');
            $xtop -= 15;
        }
        $pdf->ezPlaceData($xleft, $xtop, _war_pregen_xls_safe_text($pdf, 'Date Printed: ' . $date_printed, $is_tab_export), 10, 'left');
        $xtop -= 15;

        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($xheader, 'all');

        $content_start_top = $xtop;

        $select_db = "
            SELECT *
            FROM (
                SELECT
                    warehouse.warcde,
                    warehouse.warehouse_name,
                    warehouse_floor.warehouse_floor_id,
                    warehouse_floor.floor_no,
                    tranfile2.itmcde,
                    COALESCE(itemfile.itmdsc, tranfile2.itmcde)                         AS item_display,
                    tranfile2.unmcde,
                    COALESCE(itemunitmeasurefile.unmdsc, tranfile2.unmcde)              AS uom_desc,
                    CASE
                        WHEN LOWER(TRIM(COALESCE(itemunitmeasurefile.unmdsc, tranfile2.unmcde))) = 'pcs'
                            THEN 1
                        ELSE COALESCE(itemunitfile.conversion, 1)
                    END AS conversion,
                    SUM(
                        CASE tranfile1.trncde
                            WHEN 'SAL' THEN -tranfile2.itmqty
                            WHEN 'PUR' THEN  tranfile2.itmqty
                            WHEN 'SRT' THEN  tranfile2.itmqty
                            WHEN 'ADJ' THEN  tranfile2.itmqty
                            WHEN 'STT' THEN
                                CASE
                                    WHEN tranfile2.stkqty < 0 THEN -tranfile2.itmqty
                                    WHEN tranfile2.stkqty > 0 THEN  tranfile2.itmqty
                                    ELSE 0
                                END
                            ELSE 0
                        END
                    ) AS uom_balance
                FROM tranfile2
                LEFT JOIN tranfile1           ON tranfile1.docnum             = tranfile2.docnum
                LEFT JOIN warehouse           ON tranfile2.warcde             = warehouse.warcde
                LEFT JOIN warehouse_floor     ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
                LEFT JOIN itemfile            ON tranfile2.itmcde             = itemfile.itmcde
                LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde             = itemunitmeasurefile.unmcde
                LEFT JOIN itemunitfile        ON tranfile2.itmcde             = itemunitfile.itmcde
                                             AND tranfile2.unmcde             = itemunitfile.unmcde
                WHERE tranfile2.warcde IS NOT NULL
                    AND tranfile2.warcde != '' {$xfilter}
                GROUP BY
                    warehouse.warcde,
                    warehouse.warehouse_name,
                    warehouse_floor.warehouse_floor_id,
                    warehouse_floor.floor_no,
                    tranfile2.itmcde,
                    itemfile.itmdsc,
                    tranfile2.unmcde,
                    itemunitmeasurefile.unmdsc,
                    itemunitfile.conversion
            ) AS sub
            WHERE uom_balance <> 0
            ORDER BY
                warehouse_name ASC,
                floor_no ASC,
                itmcde ASC,
                uom_balance DESC
        ";

        $stmt_main = $link->prepare($select_db);
        $stmt_main->execute($params);
        $all_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

        // Organize data into hierarchical structure
        $warehouses = array();
        foreach ($all_rows as $row) {
            $warcde   = $row['warcde'];
            $floor_id = $row['warehouse_floor_id'] ?? '';
            $itmcde   = $row['itmcde'];

            if (!isset($warehouses[$warcde])) {
                $warehouses[$warcde] = array(
                    'warehouse_name' => $row['warehouse_name'],
                    'floors'         => array()
                );
            }

            if (!isset($warehouses[$warcde]['floors'][$floor_id])) {
                $warehouses[$warcde]['floors'][$floor_id] = array(
                    'floor_no' => $row['floor_no'],
                    'items'    => array()
                );
            }

            if (!isset($warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde])) {
                $warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde] = array(
                    'item_display'  => $row['item_display'],
                    'uom_rows'      => array(),
                    'subtotal_pcs'  => 0
                );
            }

            $uom_desc    = trim((string)$row['uom_desc']);
            $conversion  = (float)$row['conversion'];
            if ($conversion <= 0) $conversion = 1;
            $uom_balance    = (float)$row['uom_balance'];
            $balance_in_pcs = $uom_balance * $conversion;

            $warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde]['uom_rows'][] = array(
                'uom_desc'       => $uom_desc,
                'conversion'     => $conversion,
                'uom_balance'    => $uom_balance,
                'balance_in_pcs' => $balance_in_pcs
            );

            $warehouses[$warcde]['floors'][$floor_id]['items'][$itmcde]['subtotal_pcs'] += $balance_in_pcs;
        }

        // Sort items by subtotal_pcs descending within each floor
        foreach ($warehouses as $warcde => &$warehouse_data) {
            foreach ($warehouse_data['floors'] as $floor_id => &$floor_data) {
                uasort($floor_data['items'], function($a, $b) {
                    $diff = $b['subtotal_pcs'] - $a['subtotal_pcs'];
                    if ($diff != 0) {
                        return ($diff > 0) ? 1 : -1;
                    }
                    return strcasecmp($a['item_display'], $b['item_display']);
                });
            }
        }
        unset($warehouse_data, $floor_data);

        // Render the sorted data
        $has_data        = false;
        $grand_total_pcs = 0;

        foreach ($warehouses as $warcde => $warehouse_data) {
            $warehouse_name      = $warehouse_data['warehouse_name'];
            $warehouse_total_pcs = 0;
            $first_floor_in_warehouse = true;

            foreach ($warehouse_data['floors'] as $floor_id => $floor_data) {
                $floor_no         = $floor_data['floor_no'];
                $floor_total_pcs  = 0;
                $first_item_in_floor = true;

                foreach ($floor_data['items'] as $itmcde => $item_data) {
                    $item_display_name = $item_data['item_display'];
                    $item_subtotal_pcs = $item_data['subtotal_pcs'];
                    $first_uom_in_item = true;

                    foreach ($item_data['uom_rows'] as $uom_row) {
                        $has_data = true;

                        if ($first_floor_in_warehouse && $first_item_in_floor && $first_uom_in_item) {
                            if (($xtop - 50) <= 60) {
                                $pdf->ezNewPage();
                                $xtop = $content_start_top;
                            }

                            $pdf->ezPlaceData($col_floor, $xtop, _war_pregen_xls_safe_text($pdf, "<b>" . htmlspecialchars($warehouse_name, ENT_QUOTES, 'UTF-8') . "</b>", $is_tab_export), 11, 'left');
                            $xtop -= 15;

                            $pdf->setLineStyle(.5);
                            $pdf->line($xleft, $xtop + 5, $line_right, $xtop + 5);

                            $pdf->ezPlaceData($col_floor, $xtop - 5, _war_pregen_xls_safe_text($pdf, "<b>Floor</b>", $is_tab_export), 10, 'left');
                            $pdf->ezPlaceData($col_item,  $xtop - 5, _war_pregen_xls_safe_text($pdf, "<b>Item</b>", $is_tab_export),  10, 'left');
                            $pdf->ezPlaceData($col_qty,   $xtop - 5, _war_pregen_xls_safe_text($pdf, "<b>Qty</b>", $is_tab_export),   10, 'right');
                            $pdf->ezPlaceData($col_uom,   $xtop - 5, _war_pregen_xls_safe_text($pdf, "<b>UOM</b>", $is_tab_export),   10, 'left');
                            $pdf->ezPlaceData($col_balance, $xtop - 5, _war_pregen_xls_safe_text($pdf, "<b>Balance</b>", $is_tab_export), 10, 'right');

                            $pdf->line($xleft, $xtop - 18, $line_right, $xtop - 18);
                            $xtop -= 30;

                            $first_floor_in_warehouse = false;
                        }

                        $uom_desc    = $uom_row['uom_desc'];
                        $conversion  = $uom_row['conversion'];
                        $uom_balance    = $uom_row['uom_balance'];
                        $balance_in_pcs = $uom_row['balance_in_pcs'];

                        $is_pcs = (strtolower($uom_desc) === 'pcs');
                        if ($is_pcs) {
                            $uom_display = 'pcs';
                        } else {
                            $conv_int    = (int)$conversion;
                            $uom_display = $uom_desc . '(' . $conv_int . 'pcs)';
                        }

                        $floor_display = ($first_item_in_floor && $first_uom_in_item)
                            ? (!empty($floor_no) ? $floor_no : '(No Floor)')
                            : '';
                        $item_display  = $first_uom_in_item ? $item_display_name : '';

                        $floor_lines = _war_pregen_wrap_report_text($pdf, $floor_display, $floor_max_width, 9, $is_tab_export);
                        $item_lines  = _war_pregen_wrap_report_text($pdf, $item_display,  $item_max_width,  9, $is_tab_export);
                        $uom_lines   = _war_pregen_wrap_report_text($pdf, $uom_display,   $uom_max_width,   9, $is_tab_export);
                        $line_count  = max(count($floor_lines), count($item_lines), count($uom_lines), 1);
                        $row_height  = 15 + ((max(1, $line_count) - 1) * 10);

                        if (($xtop - $row_height) <= 60) {
                            $pdf->ezNewPage();
                            $xtop = $content_start_top;
                        }

                        $row_y = $xtop;

                        foreach ($floor_lines as $floor_line_index => $floor_line_text) {
                            if ($floor_line_text !== '' || ($is_tab_export && $floor_line_index === 0)) {
                                $pdf->ezPlaceData($col_floor, $row_y - ($floor_line_index * 10), _war_pregen_xls_safe_text($pdf, $floor_line_text, $is_tab_export), 9, 'left');
                            }
                        }
                        if (empty($floor_lines) || $floor_lines[0] === '') {
                            if ($is_tab_export) {
                                $pdf->ezPlaceData($col_floor, $row_y, '', 9, 'left');
                            }
                        }

                        foreach ($item_lines as $item_line_index => $item_line_text) {
                            if ($item_line_text !== '' || ($is_tab_export && $item_line_index === 0)) {
                                $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), _war_pregen_xls_safe_text($pdf, $item_line_text, $is_tab_export), 9, 'left');
                            }
                        }
                        if (empty($item_lines) || $item_lines[0] === '') {
                            if ($is_tab_export) {
                                $pdf->ezPlaceData($col_item, $row_y, '', 9, 'left');
                            }
                        }

                        $pdf->ezPlaceData($col_qty, $row_y, _war_pregen_format_report_balance($uom_balance), 9, 'right');

                        foreach ($uom_lines as $uom_line_index => $uom_line_text) {
                            if ($uom_line_text !== '' || ($is_tab_export && $uom_line_index === 0)) {
                                $pdf->ezPlaceData($col_uom, $row_y - ($uom_line_index * 10), _war_pregen_xls_safe_text($pdf, $uom_line_text, $is_tab_export), 9, 'left');
                            }
                        }

                        $pdf->ezPlaceData($col_balance, $row_y, _war_pregen_format_report_balance($balance_in_pcs), 9, 'right');

                        $xtop -= $row_height;
                        $first_uom_in_item = false;
                    }

                    if ($item_subtotal_pcs != 0) {
                        _war_pregen_print_item_subtotal($pdf, $xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $item_subtotal_pcs, $is_tab_export, $content_start_top);
                        $xtop -= 18;
                    }

                    $floor_total_pcs += $item_subtotal_pcs;
                    $first_item_in_floor = false;
                }

                if ($floor_total_pcs != 0) {
                    _war_pregen_print_floor_total($pdf, $xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $floor_total_pcs, $is_tab_export, $content_start_top);
                    $xtop -= 22;
                }

                $warehouse_total_pcs += $floor_total_pcs;
            }

            if ($warehouse_total_pcs != 0) {
                _war_pregen_print_warehouse_total($pdf, $xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $warehouse_total_pcs, $warehouse_name, $is_tab_export, $line_right, $content_start_top);
                $xtop -= 35;
            }

            $grand_total_pcs += $warehouse_total_pcs;
        }

        if ($has_data) {
            if (($xtop - 25) <= 60) {
                $pdf->ezNewPage();
                $xtop = $content_start_top;
            }

            $pdf->setLineStyle(.5);
            $pdf->line($xleft, $xtop - 5, $line_right, $xtop - 5);
            $xtop -= 18;

            if ($is_tab_export) {
                $pdf->ezPlaceData($col_floor,   $xtop, '', 9, 'left');
                $pdf->ezPlaceData($col_item,    $xtop, '', 9, 'left');
                $pdf->ezPlaceData($col_qty,     $xtop, '', 9, 'right');
            }
            $pdf->ezPlaceData($col_uom,     $xtop, _war_pregen_xls_safe_text($pdf, "<b>Grand Total(pcs):</b>", $is_tab_export), 9, 'left');
            $pdf->ezPlaceData($col_balance, $xtop, "<b>" . _war_pregen_format_report_balance($grand_total_pcs) . "</b>", 9, 'right');
        } else {
            $pdf->ezPlaceData($col_floor, $xtop, _war_pregen_xls_safe_text($pdf, "No data found.", $is_tab_export), 9, 'left');
        }

        $pdf->addText(30, 15, 8, "Date Printed: " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);

        // Return content based on export type
        if ($is_tab_export) {
            // For XLS: generate tab-delimited content from global array
            global $glo_arr;
            ksort($glo_arr);

            foreach ($glo_arr as $key => $value) {
                ksort($glo_arr[$key]);
            }

            $xchunk3 = '';
            $xline = "\r\n";
            $xtab = chr(9);

            foreach ($glo_arr as $key => $value) {
                foreach ($glo_arr[$key] as $key2 => $value2) {
                    $xchunk3 .= $glo_arr[$key][$key2] . $xtab;
                }
                $xchunk3 .= $xline;
            }

            // Remove HTML bold/italic tags
            $xchunk3 = str_replace("<b>", "", $xchunk3);
            $xchunk3 = str_replace("</b>", "", $xchunk3);
            $xchunk3 = str_replace("<i>", "", $xchunk3);
            $xchunk3 = str_replace("</i>", "", $xchunk3);

            ob_end_clean();
            return $xchunk3;
        } else {
            // For PDF: return PDF binary content
            ob_end_clean();
            return $pdf->ezOutput();
        }
    }

    // Helper functions for pre-generation (prefixed to avoid conflicts)
    function _war_pregen_print_item_subtotal(&$pdf, &$xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $item_subtotal_pcs, $is_tab_export, $content_start_top) {
        if (($xtop - 18) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_item,  $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,   $xtop, '', 9, 'right');
        }
        $pdf->ezPlaceData($col_uom,     $xtop, _war_pregen_xls_safe_text($pdf, "<b>Sub-total(pcs):</b>", $is_tab_export), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . _war_pregen_format_report_balance($item_subtotal_pcs) . "</b>", 9, 'right');
    }

    function _war_pregen_print_floor_total(&$pdf, &$xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $floor_total_pcs, $is_tab_export, $content_start_top) {
        if (($xtop - 20) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_item,  $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,   $xtop, '', 9, 'right');
        }
        $pdf->ezPlaceData($col_uom,     $xtop, _war_pregen_xls_safe_text($pdf, "<b>Total(pcs):</b>", $is_tab_export), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . _war_pregen_format_report_balance($floor_total_pcs) . "</b>", 9, 'right');
    }

    function _war_pregen_print_warehouse_total(&$pdf, &$xtop, $col_floor, $col_item, $col_qty, $col_uom, $col_balance, $warehouse_total_pcs, $warehouse_name, $is_tab_export, $line_right, $content_start_top) {
        if (($xtop - 25) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        $pdf->setLineStyle(.3);
        $pdf->line(25, $xtop - 5, $line_right, $xtop - 5);
        $xtop -= 18;

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            $pdf->ezPlaceData($col_qty,   $xtop, '', 9, 'right');
            $pdf->ezPlaceData($col_uom,   $xtop, '', 9, 'left');
        }
        $pdf->ezPlaceData($col_item,    $xtop, _war_pregen_xls_safe_text($pdf, "<b>Total(" . htmlspecialchars($warehouse_name, ENT_QUOTES, 'UTF-8') . ")(pcs):</b>", $is_tab_export), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . _war_pregen_format_report_balance($warehouse_total_pcs) . "</b>", 9, 'right');
    }

    function _war_pregen_wrap_report_text($pdf, $string, $max_wid, $fsize, $is_tab_export) {
        static $cache = [];

        $string = trim((string)$string);
        if ($string === '') {
            return array('');
        }

        $cache_key = $max_wid . '|' . $fsize . '|' . $string;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        if ($is_tab_export || get_class($pdf) == 'tab_ezpdf') {
            return $cache[$cache_key] = array(_war_pregen_xls_safe_text($pdf, $string, $is_tab_export));
        }

        $max_wid -= 5;
        if ($pdf->getTextWidth($fsize, $string) <= $max_wid) {
            return $cache[$cache_key] = array($string);
        }

        $wrapped_lines = array();
        $remaining     = $string;

        while ($remaining !== '') {
            if ($pdf->getTextWidth($fsize, $remaining) <= $max_wid) {
                $wrapped_lines[] = $remaining;
                break;
            }

            $line = _war_pregen_fit_text_to_width($pdf, $remaining, $max_wid, $fsize);
            if ($line === '') {
                $line = substr($remaining, 0, 1);
            }

            $last_space = strrpos($line, ' ');
            if ($last_space !== false && $last_space > 0) {
                $candidate_line = rtrim(substr($line, 0, $last_space));
                if ($candidate_line !== '') {
                    $line = $candidate_line;
                }
            }

            $wrapped_lines[] = rtrim($line);
            $remaining       = ltrim(substr($remaining, strlen($line)));
        }

        if (empty($wrapped_lines)) {
            $wrapped_lines[] = $string;
        }

        return $cache[$cache_key] = $wrapped_lines;
    }

    function _war_pregen_fit_text_to_width($pdf, $string, $max_wid, $fsize) {
        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr    = "";
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
            if ($xstr_wid > $max_wid) {
                break;
            }
            $xxstr = $xxstr . $value;
        }

        return rtrim($xxstr);
    }

    function _war_pregen_format_report_balance($value) {
        $formatted_balance = number_format((float)$value, 4, '.', ',');
        $formatted_balance = rtrim(rtrim($formatted_balance, '0'), '.');

        if ($formatted_balance === '-0') {
            $formatted_balance = '0';
        }

        return $formatted_balance;
    }

    function _war_pregen_xls_safe_text($pdf, $string, $is_tab_export) {
        static $cache = [];

        $string = (string)$string;
        if (!$is_tab_export && get_class($pdf) != 'tab_ezpdf') {
            return $string;
        }

        if (isset($cache[$string])) {
            return $cache[$string];
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if ($converted !== false && $converted !== '') {
                $string = $converted;
            } else {
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        } else {
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return $cache[$string] = trim($string);
    }
?>