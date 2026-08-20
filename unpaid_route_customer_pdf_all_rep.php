<?php

session_start();

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("ezpdfclass/class/class.ezpdf.php");
require_once("resources/func_pdf2tab.php");

date_default_timezone_set('Asia/Manila');

// function unpaid_route_customer_pdf_all_deny_access()
// {
//     if (!headers_sent()) {
//         header('Location: index.php');
//     }
//     exit;
// }

function unpaid_route_customer_pdf_all_fail_request($message)
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

function unpaid_route_customer_pdf_all_has_export_access($link, $usercode, $userdesc, $permission_filename)
{
    if ($userdesc === 'admin') {
        return true;
    }

    $select_db_crud = "SELECT view, export FROM user_menus WHERE usercode=? AND menprogram=? LIMIT 1";
    $stmt_crud = $link->prepare($select_db_crud);
    $stmt_crud->execute(array($usercode, $permission_filename));
    $rs_crud = $stmt_crud->fetch();

    if (empty($rs_crud)) {
        return false;
    }

    return ((int)$rs_crud['view'] === 1 && (int)$rs_crud['export'] === 1);
}

function unpaid_route_customer_pdf_all_pdf_text($value)
{
    $value = trim((string)$value);
    $value = preg_replace('/[\r\n\t]+/', ' ', $value);
    return $value;
}

function unpaid_route_customer_pdf_all_amount($value)
{
    return number_format((float)$value, 2);
}

function unpaid_route_customer_pdf_all_use_font($pdf, $is_bold = false)
{
    if ($is_bold) {
        $pdf->selectFont("ezpdfclass/fonts/Helvetica-Bold.afm");
        return;
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
}

function unpaid_route_customer_pdf_all_add_text_left($pdf, $x, $y, $text, $size, $is_bold = false)
{
    unpaid_route_customer_pdf_all_use_font($pdf, $is_bold);
    $pdf->addText($x, $y, $size, unpaid_route_customer_pdf_all_pdf_text($text));
}

function unpaid_route_customer_pdf_all_add_text_center($pdf, $x, $y, $width, $text, $size, $is_bold = false)
{
    unpaid_route_customer_pdf_all_use_font($pdf, $is_bold);
    $text = unpaid_route_customer_pdf_all_pdf_text($text);
    $text_width = $pdf->getTextWidth($size, $text);
    $text_x = $x + (($width - $text_width) / 2);

    if ($text_x < ($x + 3)) {
        $text_x = $x + 3;
    }

    $pdf->addText($text_x, $y, $size, $text);
}

function unpaid_route_customer_pdf_all_add_text_right($pdf, $x, $y, $width, $text, $size, $is_bold = false)
{
    unpaid_route_customer_pdf_all_use_font($pdf, $is_bold);
    $text = unpaid_route_customer_pdf_all_pdf_text($text);
    $text_width = $pdf->getTextWidth($size, $text);
    $text_x = ($x + $width) - $text_width - 6;

    if ($text_x < ($x + 3)) {
        $text_x = $x + 3;
    }

    $pdf->addText($text_x, $y, $size, $text);
}

function unpaid_route_customer_pdf_all_render_page_header($pdf, $buyer_name, $date_printed, $route_filter_name = 'All Routes')
{
    $left = 30;
    $right = 582;
    $top = 742;

    $pdf->setColor(0, 0, 0);
    unpaid_route_customer_pdf_all_add_text_left($pdf, $left, $top, 'Unpaid Sales Route', 15, true);
    unpaid_route_customer_pdf_all_add_text_left($pdf, $left, $top - 18, 'Route Filter : ' . $route_filter_name, 10, false);
    unpaid_route_customer_pdf_all_add_text_left($pdf, $left, $top - 32, 'Customer : ' . $buyer_name, 10, false);
    unpaid_route_customer_pdf_all_add_text_left($pdf, $left, $top - 46, 'Date Printed : ' . $date_printed, 9, false);
    $pdf->setLineStyle(.5);
    $pdf->line($left, $top - 54, $right, $top - 54);

    return $top - 72;
}

function unpaid_route_customer_pdf_all_draw_table_header($pdf, $x, $y, $customer_width, $dr_width, $amount_width, $row_height)
{
    $amount_x = $x + $customer_width + $dr_width;
    $row_y = $y - $row_height;

    $pdf->setColor(0.96, 0.82, 0.82, 'fill');
    $pdf->filledRectangle($x, $row_y, $customer_width + $dr_width + $amount_width, $row_height);
    $pdf->setColor(0, 0, 0, 'stroke');
    $pdf->rectangle($x, $row_y, $customer_width + $dr_width + $amount_width, $row_height);
    $pdf->line($x + $customer_width, $row_y, $x + $customer_width, $row_y + $row_height);
    $pdf->line($amount_x, $row_y, $amount_x, $row_y + $row_height);

    $text_y = $row_y + 8;
    unpaid_route_customer_pdf_all_add_text_center($pdf, $x, $text_y, $customer_width, 'CUSTOMER', 10, true);
    unpaid_route_customer_pdf_all_add_text_center($pdf, $x + $customer_width, $text_y, $dr_width, 'DR #', 10, true);
    unpaid_route_customer_pdf_all_add_text_center($pdf, $amount_x, $text_y, $amount_width, 'AMOUNT', 10, true);

    return $row_y;
}

function unpaid_route_customer_pdf_all_draw_detail_row($pdf, $x, $y, $customer_width, $dr_width, $amount_width, $row_height, $customer_name, $order_num, $amount)
{
    $amount_x = $x + $customer_width + $dr_width;
    $row_y = $y - $row_height;

    $pdf->setColor(0, 0, 0, 'stroke');
    $pdf->rectangle($x, $row_y, $customer_width + $dr_width + $amount_width, $row_height);
    $pdf->line($x + $customer_width, $row_y, $x + $customer_width, $row_y + $row_height);
    $pdf->line($amount_x, $row_y, $amount_x, $row_y + $row_height);

    $text_y = $row_y + 8;
    unpaid_route_customer_pdf_all_add_text_center($pdf, $x, $text_y, $customer_width, $customer_name, 10, false);
    unpaid_route_customer_pdf_all_add_text_center($pdf, $x + $customer_width, $text_y, $dr_width, $order_num, 10, false);
    unpaid_route_customer_pdf_all_add_text_right($pdf, $amount_x, $text_y, $amount_width, unpaid_route_customer_pdf_all_amount($amount), 10, false);

    return $row_y;
}

function unpaid_route_customer_pdf_all_draw_total_row($pdf, $x, $y, $label_width, $amount_width, $row_height, $label, $amount, $fill_color)
{
    $row_y = $y - $row_height;

    $pdf->setColor($fill_color[0], $fill_color[1], $fill_color[2], 'fill');
    $pdf->filledRectangle($x, $row_y, $label_width + $amount_width, $row_height);
    $pdf->setColor(0, 0, 0, 'stroke');
    $pdf->rectangle($x, $row_y, $label_width + $amount_width, $row_height);
    $pdf->line($x + $label_width, $row_y, $x + $label_width, $row_y + $row_height);

    $text_y = $row_y + 8;
    unpaid_route_customer_pdf_all_add_text_center($pdf, $x, $text_y, $label_width, $label, 10, true);
    unpaid_route_customer_pdf_all_add_text_right($pdf, $x + $label_width, $text_y, $amount_width, unpaid_route_customer_pdf_all_amount($amount), 10, true);

    return $row_y;
}

function unpaid_route_customer_pdf_all_group_route_rows_by_customer($route_rows)
{
    $grouped_rows = array();
    $current_group_index = null;
    $row_sequence = 0;
    $distinct_groupable_buyers = array();

    foreach ($route_rows as $route_row) {
        $row_sequence++;
        $route_id = isset($route_row['route_id']) ? (string)$route_row['route_id'] : '';
        $buyer_id = isset($route_row['buyer_id']) ? trim((string)$route_row['buyer_id']) : '';
        $buyer_name = isset($route_row['buyer_name']) ? (string)$route_row['buyer_name'] : '';
        $has_groupable_buyer_id = ($buyer_id !== '');
        // Blank/null buyer IDs must never merge unrelated rows into one customer subtotal.
        $buyer_group_key = $has_groupable_buyer_id
            ? ('buyer:' . $buyer_id)
            : ('row:' . $route_id . '|' . $row_sequence);

        if ($has_groupable_buyer_id) {
            $distinct_groupable_buyers[$buyer_id] = true;
        }

        $start_new_group = ($current_group_index === null);

        if (!$start_new_group) {
            $current_group = $grouped_rows[$current_group_index];
            if ($current_group['route_id'] !== $route_id) {
                $start_new_group = true;
            } elseif ($current_group['buyer_group_key'] !== $buyer_group_key) {
                $start_new_group = true;
            }
        }

        if ($start_new_group) {
            $grouped_rows[] = array(
                'route_id' => $route_id,
                'buyer_id' => $buyer_id,
                'buyer_name' => $buyer_name,
                'buyer_group_key' => $buyer_group_key,
                'has_groupable_buyer_id' => $has_groupable_buyer_id,
                'rows' => array(),
                'row_count' => 0,
                'subtotal' => 0,
                'show_subtotal' => false,
            );
            $current_group_index = count($grouped_rows) - 1;
        }

        if ($grouped_rows[$current_group_index]['buyer_name'] === '' && $buyer_name !== '') {
            $grouped_rows[$current_group_index]['buyer_name'] = $buyer_name;
        }

        $grouped_rows[$current_group_index]['rows'][] = $route_row;
        $grouped_rows[$current_group_index]['row_count']++;
        $grouped_rows[$current_group_index]['subtotal'] += isset($route_row['trntot']) ? (float)$route_row['trntot'] : 0;
    }

    $show_customer_subtotals = (count($distinct_groupable_buyers) >= 2);
    if ($show_customer_subtotals) {
        foreach ($grouped_rows as $group_index => $grouped_row) {
            $grouped_rows[$group_index]['show_subtotal'] = !empty($grouped_row['has_groupable_buyer_id']);
        }
    }

    return $grouped_rows;
}

function unpaid_route_customer_pdf_all_route_label($route_desc, $route_id)
{
    $route_desc = unpaid_route_customer_pdf_all_pdf_text($route_desc);

    if ($route_desc === '') {
        return 'ROUTE ' . unpaid_route_customer_pdf_all_pdf_text($route_id);
    }

    return strtoupper($route_desc);
}

function unpaid_route_customer_pdf_all_is_all_buyers($buyer_value)
{
    $buyer_value = strtolower(trim((string)$buyer_value));
    return ($buyer_value === '' || $buyer_value === 'all' || $buyer_value === 'null');
}

function unpaid_route_customer_pdf_all_is_all_routes($route_value)
{
    $route_value = strtolower(trim((string)$route_value));
    return ($route_value === '' || $route_value === 'all' || $route_value === 'null');
}

function unpaid_route_customer_pdf_all_validate_id($id, $max_length = 100)
{
    $id = trim((string)$id);
    if ($id === '' || strlen($id) > $max_length) {
        return false;
    }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $id)) {
        return false;
    }
    return $id;
}

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     unpaid_route_customer_pdf_all_deny_access();
// }

// if (
//     !isset($_SESSION['userdesc']) ||
//     !isset($_SESSION['password']) ||
//     !isset($_SESSION['usercode'])
// ) {
//     unpaid_route_customer_pdf_all_deny_access();
// }

try {
    // if (!unpaid_route_customer_pdf_all_has_export_access($link, $_SESSION['usercode'], $_SESSION['userdesc'], 'unpaid_route_customer_pdf.php')) {
    //     unpaid_route_customer_pdf_all_deny_access();
    // }

    // Handle route_id parameter
    $route_id_raw = isset($_POST['route_id']) ? trim((string)$_POST['route_id']) : '';
    $is_all_routes = unpaid_route_customer_pdf_all_is_all_routes($route_id_raw);
    $filter_route_id = '';
    $route_filter_name = 'All Routes';

    if (!$is_all_routes) {
        $filter_route_id = unpaid_route_customer_pdf_all_validate_id($route_id_raw);
        if ($filter_route_id === false) {
            unpaid_route_customer_pdf_all_fail_request('Invalid route selection.');
        }

        // Verify route exists and get name
        $select_route_check = "SELECT route_id, route_desc FROM mf_routes WHERE route_id = ? AND (route_desc IS NULL OR route_desc <> '-') LIMIT 1";
        $stmt_route_check = $link->prepare($select_route_check);
        $stmt_route_check->execute(array($filter_route_id));
        $rs_route_check = $stmt_route_check->fetch();

        if (empty($rs_route_check)) {
            unpaid_route_customer_pdf_all_fail_request('Route not found.');
        }

        $route_filter_name = unpaid_route_customer_pdf_all_pdf_text($rs_route_check['route_desc']);
        if ($route_filter_name === '') {
            $route_filter_name = unpaid_route_customer_pdf_all_pdf_text($filter_route_id);
        }
    }

    // Handle buyer_id parameter (can be array or single value)
    $buyer_ids_raw = isset($_POST['buyer_id']) ? $_POST['buyer_id'] : array();
    if (!is_array($buyer_ids_raw)) {
        $buyer_ids_raw = $buyer_ids_raw !== '' ? array($buyer_ids_raw) : array();
    }

    // Filter out empty and "all" values
    $buyer_ids_raw = array_filter($buyer_ids_raw, function($v) {
        return !unpaid_route_customer_pdf_all_is_all_buyers($v);
    });

    $is_all_buyers = empty($buyer_ids_raw);
    $buyer_ids = array();
    $buyer_names_list = array();
    $buyer_name = 'All';

    if (!$is_all_buyers) {
        // Validate each buyer_id
        foreach ($buyer_ids_raw as $bid) {
            $validated_bid = unpaid_route_customer_pdf_all_validate_id($bid);
            if ($validated_bid === false) {
                continue; // Skip invalid buyer IDs silently
            }
            $buyer_ids[] = $validated_bid;
        }

        if (empty($buyer_ids)) {
            $is_all_buyers = true;
            $buyer_name = 'All';
        } else {
            // Get buyer names for header display
            $placeholders = implode(',', array_fill(0, count($buyer_ids), '?'));
            $select_buyer_names = "SELECT buyer_id, buyer_name FROM mf_buyers WHERE buyer_id IN ($placeholders)";
            $stmt_buyer_names = $link->prepare($select_buyer_names);
            $stmt_buyer_names->execute($buyer_ids);

            while ($rs_bn = $stmt_buyer_names->fetch()) {
                $bn = isset($rs_bn['buyer_name']) ? trim((string)$rs_bn['buyer_name']) : '';
                if ($bn !== '') {
                    $buyer_names_list[] = unpaid_route_customer_pdf_all_pdf_text($bn);
                }
            }

            if (count($buyer_names_list) === 1) {
                $buyer_name = $buyer_names_list[0];
            } elseif (count($buyer_names_list) > 1) {
                $buyer_name = implode(', ', array_slice($buyer_names_list, 0, 3));
                if (count($buyer_names_list) > 3) {
                    $buyer_name .= ' (+' . (count($buyer_names_list) - 3) . ' more)';
                }
            } else {
                // Fallback if no names found
                $buyer_name = count($buyer_ids) . ' selected';
            }
        }
    }

    $date_printed = date("F j, Y h:i:s A");
    $route_print_date = date("n/j/Y");

    // Build routes query with optional route filter
    $select_routes = "SELECT route_id, route_desc
                      FROM mf_routes
                      WHERE (route_desc IS NULL OR route_desc <> '-')";
    $routes_params = array();

    if (!$is_all_routes) {
        $select_routes .= " AND route_id = ?";
        $routes_params[] = $filter_route_id;
    }

    $select_routes .= " ORDER BY route_desc ASC, route_id ASC";
    $stmt_routes = $link->prepare($select_routes);
    $stmt_routes->execute($routes_params);

    $routes = array();
    while ($rs_route = $stmt_routes->fetch()) {
        $route_key = isset($rs_route['route_id']) ? (string)$rs_route['route_id'] : '';
        if ($route_key === '') {
            continue;
        }

        $routes[$route_key] = array(
            'route_id' => $route_key,
            'route_desc' => isset($rs_route['route_desc']) ? $rs_route['route_desc'] : '',
        );
    }

    $select_data = "SELECT
                        tranfile1.route_id AS route_id,
                        tranfile1.buyer_id AS buyer_id,
                        mf_routes.route_desc,
                        buyer_lookup.buyer_name AS buyer_name,
                        tranfile1.ordernum,
                        tranfile1.trntot
                    FROM tranfile1
                    LEFT JOIN (
                        SELECT
                            buyer_id,
                            MAX(CASE
                                WHEN TRIM(COALESCE(buyer_name, '')) <> '' THEN buyer_name
                                ELSE ''
                            END) AS buyer_name
                        FROM mf_buyers
                        GROUP BY buyer_id
                    ) buyer_lookup
                        ON buyer_lookup.buyer_id = tranfile1.buyer_id
                    INNER JOIN mf_routes
                        ON tranfile1.route_id = mf_routes.route_id
                    WHERE (tranfile1.paydate IS NULL OR tranfile1.paydate = '')
                      AND tranfile1.trncde = ?";
    $select_params = array('SAL');

    $select_data .= "
                      AND (mf_routes.route_desc IS NULL OR mf_routes.route_desc <> '-')";

    // Add route filter if specific route selected
    if (!$is_all_routes) {
        $select_data .= "
                      AND tranfile1.route_id = ?";
        $select_params[] = $filter_route_id;
    }

    // Add buyer filter if specific buyers selected
    if (!$is_all_buyers && !empty($buyer_ids)) {
        $buyer_placeholders = implode(',', array_fill(0, count($buyer_ids), '?'));
        $select_data .= "
                      AND tranfile1.buyer_id IN ($buyer_placeholders)";
        foreach ($buyer_ids as $bid) {
            $select_params[] = $bid;
        }
    }

    $select_data .= "
                    ORDER BY mf_routes.route_desc ASC, tranfile1.route_id ASC, tranfile1.buyer_id ASC, tranfile1.trndte ASC, tranfile1.ordernum ASC, tranfile1.docnum ASC";
    $stmt_data = $link->prepare($select_data);
    $stmt_data->execute($select_params);

    $rows_by_route = array();
    $route_totals = array();
    $overall_total = 0;

    while ($rs_data = $stmt_data->fetch()) {
        $route_key = isset($rs_data['route_id']) ? (string)$rs_data['route_id'] : '';
        if ($route_key === '') {
            continue;
        }

        $buyer_id_row = isset($rs_data['buyer_id']) ? trim((string)$rs_data['buyer_id']) : '';
        $buyer_name_row = '';
        if (isset($rs_data['buyer_name']) && trim((string)$rs_data['buyer_name']) !== '') {
            $buyer_name_row = unpaid_route_customer_pdf_all_pdf_text($rs_data['buyer_name']);
        }

        $order_num_row = '';
        if (isset($rs_data['ordernum']) && trim((string)$rs_data['ordernum']) !== '') {
            $order_num_row = unpaid_route_customer_pdf_all_pdf_text($rs_data['ordernum']);
        }

        $amount = isset($rs_data['trntot']) ? (float)$rs_data['trntot'] : 0;

        // Skip placeholder-like rows with no real customer, no DR number, and zero amount.
        if ($buyer_name_row === '' && $order_num_row === '' && abs($amount) < 0.00001) {
            continue;
        }

        if (!isset($rows_by_route[$route_key])) {
            $rows_by_route[$route_key] = array();
            $route_totals[$route_key] = 0;
        }

        $route_totals[$route_key] += $amount;
        $overall_total += $amount;

        $rows_by_route[$route_key][] = array(
            'route_id' => $route_key,
            'buyer_id' => $buyer_id_row,
            'buyer_name' => $buyer_name_row,
            'ordernum' => $order_num_row,
            'trntot' => $amount,
        );
    }

    ob_start();

    $pdf = new Cezpdf('Letter', 'portrait');
    unpaid_route_customer_pdf_all_use_font($pdf, false);
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM} of {TOTALPAGENUM}', 1);

    $page_y = unpaid_route_customer_pdf_all_render_page_header($pdf, $buyer_name, $date_printed, $route_filter_name);
    $table_x = 30;
    $customer_width = 220;
    $dr_width = 160;
    $amount_width = 172;
    $row_height = 24;
    $bottom_limit = 55;
    $rendered_routes = 0;

    foreach ($routes as $route_id => $route_info) {
        if (!isset($rows_by_route[$route_id]) || empty($rows_by_route[$route_id])) {
            continue;
        }

        $route_rows = $rows_by_route[$route_id];
        $route_customer_groups = unpaid_route_customer_pdf_all_group_route_rows_by_customer($route_rows);
        $route_total = isset($route_totals[$route_id]) ? $route_totals[$route_id] : 0;
        $customer_group_count = count($route_customer_groups);
        $customer_group_index = 0;
        $customer_row_index = 0;
        $chunk_count = 0;

        while ($customer_group_index < $customer_group_count) {
            $minimum_needed = 60 + $row_height + $row_height + 12;
            if (($page_y - $minimum_needed) < $bottom_limit) {
                $pdf->ezNewPage();
                $page_y = unpaid_route_customer_pdf_all_render_page_header($pdf, $buyer_name, $date_printed, $route_filter_name);
            }

            $chunk_count++;
            $rendered_routes++;

            $route_label = unpaid_route_customer_pdf_all_route_label($route_info['route_desc'], $route_info['route_id']);
            if ($chunk_count > 1) {
                $route_label .= ' (CONT.)';
            }

            unpaid_route_customer_pdf_all_add_text_left($pdf, $table_x + 2, $page_y, $route_label, 12, true);
            unpaid_route_customer_pdf_all_add_text_left($pdf, $table_x + 2, $page_y - 16, $route_print_date, 10, false);
            $page_y -= 24;

            $page_y = unpaid_route_customer_pdf_all_draw_table_header($pdf, $table_x, $page_y, $customer_width, $dr_width, $amount_width, $row_height);
            $needs_new_page = false;

            while ($customer_group_index < $customer_group_count) {
                $current_group = $route_customer_groups[$customer_group_index];
                $current_group_row_count = count($current_group['rows']);

                while ($customer_row_index < $current_group_row_count) {
                    $is_last_row_in_customer = ($customer_row_index === ($current_group_row_count - 1));
                    $is_last_customer_in_route = ($customer_group_index === ($customer_group_count - 1));
                    $required_after_row = 10;

                    if ($is_last_row_in_customer && !empty($current_group['show_subtotal'])) {
                        $required_after_row += $row_height;
                    }

                    if ($is_last_row_in_customer && $is_last_customer_in_route) {
                        $required_after_row += $row_height;
                    }

                    if (($page_y - ($row_height + $required_after_row)) < $bottom_limit) {
                        $needs_new_page = true;
                        break 2;
                    }

                    $current_row = $current_group['rows'][$customer_row_index];
                    $customer_name = isset($current_row['buyer_name']) ? (string)$current_row['buyer_name'] : '';
                    if ($customer_name === '') {
                        $customer_name = $current_group['buyer_name'];
                    }

                    $page_y = unpaid_route_customer_pdf_all_draw_detail_row(
                        $pdf,
                        $table_x,
                        $page_y,
                        $customer_width,
                        $dr_width,
                        $amount_width,
                        $row_height,
                        $customer_name,
                        $current_row['ordernum'],
                        $current_row['trntot']
                    );

                    $customer_row_index++;
                }

                if ($customer_row_index >= $current_group_row_count) {
                    if (!empty($current_group['show_subtotal'])) {
                        $page_y = unpaid_route_customer_pdf_all_draw_total_row(
                            $pdf,
                            $table_x,
                            $page_y,
                            $customer_width + $dr_width,
                            $amount_width,
                            $row_height,
                            'CUSTOMER SUBTOTAL',
                            $current_group['subtotal'],
                            array(0.96, 0.96, 0.90)
                        );
                    }

                    $customer_group_index++;
                    $customer_row_index = 0;
                }
            }

            if (!$needs_new_page && $customer_group_index >= $customer_group_count) {
                $page_y = unpaid_route_customer_pdf_all_draw_total_row(
                    $pdf,
                    $table_x,
                    $page_y,
                    $customer_width + $dr_width,
                    $amount_width,
                    $row_height,
                    'ROUTE TOTAL',
                    $route_total,
                    array(1.00, 0.95, 0.80)
                );
                $page_y -= 18;
            } else {
                $pdf->ezNewPage();
                $page_y = unpaid_route_customer_pdf_all_render_page_header($pdf, $buyer_name, $date_printed, $route_filter_name);
            }
        }
    }

    if ($rendered_routes === 0) {
        $no_data_message = 'No unpaid sales transactions found';
        if (!$is_all_routes && !$is_all_buyers) {
            $no_data_message .= ' for the selected route and buyer(s).';
        } elseif (!$is_all_routes) {
            $no_data_message .= ' for the selected route.';
        } elseif (!$is_all_buyers) {
            $no_data_message .= ' for the selected buyer(s).';
        } else {
            $no_data_message .= '.';
        }
        unpaid_route_customer_pdf_all_add_text_left($pdf, $table_x + 2, $page_y, $no_data_message, 11, false);
    } else {
        if (($page_y - ($row_height + 10)) < $bottom_limit) {
            $pdf->ezNewPage();
            $page_y = unpaid_route_customer_pdf_all_render_page_header($pdf, $buyer_name, $date_printed, $route_filter_name);
        }

        $page_y = unpaid_route_customer_pdf_all_draw_total_row(
            $pdf,
            $table_x,
            $page_y,
            $customer_width + $dr_width,
            $amount_width,
            $row_height,
            'OVERALL TOTAL:',
            $overall_total,
            array(0.85, 0.93, 0.82)
        );
    }

    $pdf->ezStream();
    ob_end_flush();
} catch (Exception $e) {
    error_log('Unable to generate unpaid route customer PDF report.');
    unpaid_route_customer_pdf_all_fail_request('Unable to generate report.');
}

?>
