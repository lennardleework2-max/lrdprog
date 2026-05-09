<?php
    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

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
    $col_item = 90;
    $col_balance = 500;
    $item_max_width = 390;

    // Date filter
    $xfilter_date = '';
    $display_date = '';
    if (isset($_POST['date_search']) && !empty($_POST['date_search'])) {
        $date_sql = date("Y-m-d", strtotime($_POST['date_search']));
        $xfilter_date = " AND tranfile1.trndte <= '" . $date_sql . "'";
        $display_date = date("m/d/Y", strtotime($date_sql));
    }

    // Warehouse filter
    $xfilter_warehouse = '';
    $display_warehouse = '';
    if (isset($_POST['warehouse']) && !empty($_POST['warehouse'])) {
        $xfilter_warehouse = " AND tranfile2.warcde = '" . $_POST['warehouse'] . "'";
        // Get warehouse name for display
        $stmt_war_name = $link->prepare("SELECT warehouse_name FROM warehouse WHERE warcde = ?");
        $stmt_war_name->execute(array($_POST['warehouse']));
        $rs_war_name = $stmt_war_name->fetch();
        if ($rs_war_name) {
            $display_warehouse = $rs_war_name['warehouse_name'];
        }
    }

    // Build header
    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Inventory Balance by Warehouse</b>"), 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("<b>Report by: " . $_SESSION['userdesc'] . "</b>"), 9, 'left');
    $xtop -= 15;
    if (!empty($display_date)) {
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("As of Date: " . $display_date), 10, 'left');
        $xtop -= 15;
    }
    if (!empty($display_warehouse)) {
        $pdf->ezPlaceData($xleft, $xtop, xls_safe_text("Warehouse: " . $display_warehouse), 10, 'left');
        $xtop -= 15;
    }
    $pdf->ezPlaceData($xleft, $xtop, xls_safe_text('Date Printed: ' . $date_printed), 10, 'left');
    $xtop -= 15;

    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');

    // Calculate content start position after page header
    $content_start_top = $xtop;

    // Query inventory data grouped by warehouse, floor, item
    $select_db = "SELECT
            warehouse.warcde,
            warehouse.warehouse_name,
            warehouse_floor.warehouse_floor_id,
            warehouse_floor.floor_no,
            tranfile2.itmcde,
            COALESCE(itemfile.itmdsc, tranfile2.itmcde) AS item_display,
            SUM(tranfile2.stkqty) AS balance
        FROM tranfile2
        LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
        LEFT JOIN warehouse ON tranfile2.warcde = warehouse.warcde
        LEFT JOIN warehouse_floor ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
        LEFT JOIN itemfile ON tranfile2.itmcde = itemfile.itmcde
        WHERE tranfile2.warcde IS NOT NULL
            AND tranfile2.warcde != '' " . $xfilter_date . $xfilter_warehouse . "
        GROUP BY
            warehouse.warcde,
            warehouse.warehouse_name,
            warehouse_floor.warehouse_floor_id,
            warehouse_floor.floor_no,
            tranfile2.itmcde,
            itemfile.itmdsc
        ORDER BY
            warehouse.warehouse_name ASC,
            warehouse_floor.floor_no ASC,
            SUM(tranfile2.stkqty) DESC,
            item_display ASC";

    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute();
    $all_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    // Organize data by warehouse -> floor -> items
    $warehouses = array();
    foreach ($all_rows as $row) {
        $warcde = $row['warcde'];
        $floor_id = $row['warehouse_floor_id'];

        if (!isset($warehouses[$warcde])) {
            $warehouses[$warcde] = array(
                'warehouse_name' => $row['warehouse_name'],
                'floors' => array()
            );
        }

        if (!isset($warehouses[$warcde]['floors'][$floor_id])) {
            $warehouses[$warcde]['floors'][$floor_id] = array(
                'floor_no' => $row['floor_no'],
                'items' => array()
            );
        }

        $warehouses[$warcde]['floors'][$floor_id]['items'][] = array(
            'itmcde' => $row['itmcde'],
            'item_display' => $row['item_display'],
            'balance' => (float)$row['balance']
        );
    }

    $grand_total = 0;
    $has_data = false;

    foreach ($warehouses as $warcde => $warehouse_data) {
        $warehouse_name = $warehouse_data['warehouse_name'];
        $warehouse_total = 0;
        $first_floor_in_warehouse = true;

        // Check if warehouse has any stock
        $warehouse_has_stock = false;
        foreach ($warehouse_data['floors'] as $floor_data) {
            foreach ($floor_data['items'] as $item) {
                if ($item['balance'] != 0) {
                    $warehouse_has_stock = true;
                    break 2;
                }
            }
        }

        if (!$warehouse_has_stock) {
            continue;
        }

        // Print warehouse header with column headers
        // Need space for: warehouse name (15) + line + column headers (15) + line spacing (5) = ~50
        if (($xtop - 50) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        // Warehouse name
        $pdf->ezPlaceData($col_floor, $xtop, xls_safe_text("<b>" . $warehouse_name . "</b>"), 11, 'left');
        $xtop -= 15;

        // Separator line above column headers
        $pdf->setLineStyle(.5);
        $pdf->line($xleft, $xtop + 5, $line_right, $xtop + 5);

        // Column headers
        $pdf->ezPlaceData($col_floor, $xtop - 5, xls_safe_text("<b>Floor</b>"), 10, 'left');
        $pdf->ezPlaceData($col_item, $xtop - 5, xls_safe_text("<b>Item</b>"), 10, 'left');
        $pdf->ezPlaceData($col_balance, $xtop - 5, xls_safe_text("<b>Balance</b>"), 10, 'right');

        // Separator line below column headers
        $pdf->line($xleft, $xtop - 18, $line_right, $xtop - 18);
        $xtop -= 30;

        foreach ($warehouse_data['floors'] as $floor_id => $floor_data) {
            $floor_no = $floor_data['floor_no'];
            $floor_subtotal = 0;
            $first_item_in_floor = true;

            foreach ($floor_data['items'] as $item) {
                $balance = $item['balance'];

                // Skip zero balance items
                if ($balance == 0) {
                    continue;
                }

                $has_data = true;

                // Calculate row height for item text wrapping
                $item_display = $item['item_display'];
                $item_lines = wrap_report_text($item_display, $item_max_width, 9);
                $line_count = max(count($item_lines), 1);
                $row_height = 15 + (($line_count - 1) * 10);

                if (($xtop - $row_height) <= 60) {
                    $pdf->ezNewPage();
                    $xtop = $content_start_top;
                }

                // Floor column - only show on first item of floor
                if ($first_item_in_floor) {
                    $floor_display = !empty($floor_no) ? $floor_no : '(No Floor)';
                    $pdf->ezPlaceData($col_floor, $xtop, xls_safe_text($floor_display), 9, 'left');
                    $first_item_in_floor = false;
                } else {
                    if ($is_tab_export) {
                        $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
                    }
                }

                // Item column with text wrapping
                $row_y = $xtop;
                foreach ($item_lines as $item_line_index => $item_line_text) {
                    if ($item_line_text !== '' || ($is_tab_export && $item_line_index === 0)) {
                        $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), xls_safe_text($item_line_text), 9, 'left');
                    }
                }

                // Balance column
                $pdf->ezPlaceData($col_balance, $xtop, format_report_balance($balance), 9, 'right');

                $floor_subtotal += $balance;
                $xtop -= $row_height;
            }

            // Print floor subtotal if floor has items
            if ($floor_subtotal != 0) {
                if (($xtop - 18) <= 60) {
                    $pdf->ezNewPage();
                    $xtop = $content_start_top;
                }

                if ($is_tab_export) {
                    $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
                }
                $pdf->ezPlaceData($col_item, $xtop, xls_safe_text("<b>Sub-Total:</b>"), 9, 'left');
                $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($floor_subtotal) . "</b>", 9, 'right');
                $xtop -= 20;

                $warehouse_total += $floor_subtotal;
            }
        }

        // Print warehouse total
        if ($warehouse_total != 0) {
            if (($xtop - 18) <= 60) {
                $pdf->ezNewPage();
                $xtop = $content_start_top;
            }

            if ($is_tab_export) {
                $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
            }
            $pdf->ezPlaceData($col_item, $xtop, xls_safe_text("<b>Total (" . $warehouse_name . "):</b>"), 9, 'left');
            $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($warehouse_total) . "</b>", 9, 'right');
            $xtop -= 25;

            // XLS: Add blank row between warehouses
            if ($is_tab_export) {
                $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
                $pdf->ezPlaceData($col_item, $xtop, '', 9, 'left');
                $pdf->ezPlaceData($col_balance, $xtop, '', 9, 'right');
                $xtop -= 15;
            }

            $grand_total += $warehouse_total;
        }
    }

    // Print grand total
    if ($has_data) {
        if (($xtop - 25) <= 60) {
            $pdf->ezNewPage();
            $xtop = $content_start_top;
        }

        $pdf->line($xleft, $xtop - 5, $line_right, $xtop - 5);
        $xtop -= 18;

        if ($is_tab_export) {
            $pdf->ezPlaceData($col_floor, $xtop, '', 9, 'left');
        }
        $pdf->ezPlaceData($col_item, $xtop, xls_safe_text("<b>Grand Total:</b>"), 9, 'left');
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($grand_total) . "</b>", 9, 'right');
    } else {
        $pdf->ezPlaceData($col_floor, $xtop, xls_safe_text("No data found."), 9, 'left');
    }

    $pdf->addText(30, 15, 8, "Date Printed: " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
    $pdf->ezStream();
    ob_end_flush();

    // Text wrapping for PDF (no wrapping for XLS)
    function wrap_report_text($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = trim((string)$string);
        if ($string === '') {
            return array('');
        }

        if (get_class($pdf) == 'tab_ezpdf') {
            return array(xls_safe_text($string));
        }

        $max_wid -= 5;
        if ($pdf->getTextWidth($fsize, $string) <= $max_wid) {
            return array($string);
        }

        $wrapped_lines = array();
        $remaining = $string;

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
            $remaining = ltrim(substr($remaining, strlen($line)));
        }

        if (empty($wrapped_lines)) {
            $wrapped_lines[] = $string;
        }

        return $wrapped_lines;
    }

    function fit_text_to_width($string, $max_wid, $fsize)
    {
        global $pdf;

        $string = (string)$string;
        if ($string === '') {
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr = "";
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

    // XLS-safe text encoding
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
