<?php
    /**
     * Platform COGS Report - PDF and XLS output
     *
     * Groups sales (tranfile2 where trncde = 'SAL') by:
     *   Platform (tranfile2.platform_upload, e.g. TIKTOK/SHOPEE/LAZADA)
     *     -> Shop (tranfile2.platform_shop_id -> mf_platform_shop.platform_shop_title,
     *              or "No Shop Assigned Yet" when platform_shop_id is blank)
     *       -> Item (Qty in pcs, Cost per pc, Total COGS)
     *
     * Everything is in pcs:
     *   Qty (pcs)  = itmqty x conversion (pcs = 1, other UOMs from itemunitfile).
     *              Lines whose UOM has no conversion are excluded and counted.
     *   Cost (pc)  = item's "Cost" price in mf_salesitemprice (per pc). If empty/0:
     *              latest PUR (tranfile1.trndte <= today) in pcs -> untprc, otherwise
     *              latest PUR in another UOM -> untprc / conversion, otherwise 0.
     *   Total COGS = Qty (pcs) x Cost (pc). Gross sales only, not netted against SALRET.
     */

    session_start();
    require_once("resources/db_init.php");
    require_once("resources/connect4.php");
    require_once("resources/lx2.pdodb.php");
    require_once('ezpdfclass/class/class.ezpdf.php');
    require_once('resources/func_pdf2tab.php');

    ob_start();

    if (!isset($_SESSION['userdesc'])) {
        die('Unauthorized access');
    }

    if (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab') {
        $pdf = new tab_ezpdf('Letter', 'landscape');
    } else {
        $pdf = new Cezpdf('Letter', 'landscape');
    }

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
    $pdf->ezStartPageNumbers(500, 15, 8, 'right', 'Page {PAGENUM}  of  {TOTALPAGENUM}', 1);
    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");
    $is_tab = (get_class($pdf) == 'tab_ezpdf');

    $xtop = 550;
    $C_LABEL = 25;
    $C_QTY   = 540;
    $C_COST  = 650;
    $C_TOTAL = 770;

    // ---- date filter ----
    $xfilter2 = '';
    $period_label = 'All Dates';
    if (isset($_POST['date_from']) && !empty($_POST['date_from']) &&
        isset($_POST['date_to']) && !empty($_POST['date_to'])) {
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte>='" . $_POST['date_from'] . "' AND tranfile1.trndte<='" . $_POST['date_to'] . "'";
        $period_label = date("m/d/Y", strtotime($_POST['date_from'])) . " - " . date("m/d/Y", strtotime($_POST['date_to']));
    } elseif (isset($_POST['date_from']) && !empty($_POST['date_from'])) {
        $_POST['date_from'] = date("Y-m-d", strtotime($_POST['date_from']));
        $xfilter2 .= " AND tranfile1.trndte>='" . $_POST['date_from'] . "'";
        $period_label = "From " . date("m/d/Y", strtotime($_POST['date_from']));
    } elseif (isset($_POST['date_to']) && !empty($_POST['date_to'])) {
        $_POST['date_to'] = date("Y-m-d", strtotime($_POST['date_to']));
        $xfilter2 .= " AND tranfile1.trndte<='" . $_POST['date_to'] . "'";
        $period_label = "Up to " . date("m/d/Y", strtotime($_POST['date_to']));
    }

    /** Header **/
    $xheader = $pdf->openObject();
    $pdf->saveState();
    $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("<b>Platform COGS Report</b>"), 15, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("<b>Pdf Report by: " . htmlspecialchars($_SESSION['userdesc']) . "</b>"), 9, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text('Period : ' . $period_label), 10, 'left');
    $xtop -= 15;
    $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text('Date Printed : ' . $date_printed), 10, 'left');
    $xtop -= 15;

    $pdf->setLineStyle(.5);
    $pdf->restoreState();
    $pdf->closeObject();
    $pdf->addObject($xheader, 'all');
    /** End Header **/

    // ---- every SAL line in range, with its shop title and item description ----
    $select_db = "SELECT tranfile2.itmcde, tranfile2.unmcde, tranfile2.itmqty,
            tranfile2.platform_upload, mps.platform_shop_title, itemfile.itmdsc
        FROM tranfile2
        INNER JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum
        LEFT JOIN mf_platform_shop mps ON mps.platform_shop_id = tranfile2.platform_shop_id
        LEFT JOIN itemfile ON itemfile.itmcde = tranfile2.itmcde
        WHERE tranfile1.trncde = 'SAL' " . $xfilter2;
    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute();
    $sal_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    // ---- batch-load pcs code, conversions and costs for the items sold ----
    $unique_items = array();
    foreach ($sal_rows as $sal_row) {
        if (!empty($sal_row['itmcde'])) {
            $unique_items[$sal_row['itmcde']] = true;
        }
    }
    $item_list = array_map('strval', array_keys($unique_items));

    $stmt_pcs = $link->prepare("SELECT unmcde FROM itemunitmeasurefile WHERE LOWER(TRIM(unmdsc)) = 'pcs' ORDER BY recid ASC LIMIT 1");
    $stmt_pcs->execute();
    $pcs_unmcde = (string)$stmt_pcs->fetchColumn();

    $conv_map = array();
    $cost_cache = array();
    $pur_rows = array();
    if (!empty($item_list)) {
        $placeholders = implode(',', array_fill(0, count($item_list), '?'));

        $stmt_conv = $link->prepare("SELECT itmcde, unmcde, conversion FROM itemunitfile WHERE itmcde IN ($placeholders)");
        $stmt_conv->execute($item_list);
        while ($conv_row = $stmt_conv->fetch(PDO::FETCH_ASSOC)) {
            $conv_map[$conv_row['itmcde'] . '|' . trim((string)$conv_row['unmcde'])] = (float)$conv_row['conversion'];
        }

        // Cost price (per pc) = item's "Cost" price in mf_salesitemprice
        $cost_query = "SELECT sip.itmcde, sip.price
            FROM mf_salesitemprice sip
            WHERE sip.salesprice_id = (SELECT salesprice_id FROM mf_salespricetype WHERE salesprice_desc = 'Cost' LIMIT 1)
            AND sip.itmcde IN ($placeholders)";
        $stmt_cost = $link->prepare($cost_query);
        $stmt_cost->execute($item_list);
        $cost_cache = $stmt_cost->fetchAll(PDO::FETCH_KEY_PAIR);

        // Fallback source: purchases (latest first) for items with no/zero Cost price
        $no_cost_items = array();
        foreach ($item_list as $itm) {
            if (empty($cost_cache[$itm]) || (float)$cost_cache[$itm] == 0) {
                $no_cost_items[] = $itm;
            }
        }
        if (!empty($no_cost_items)) {
            $pur_placeholders = implode(',', array_fill(0, count($no_cost_items), '?'));
            $pur_cost_query = "SELECT t2.itmcde, t2.unmcde, t2.untprc
                FROM tranfile2 t2
                INNER JOIN tranfile1 t1 ON t1.docnum = t2.docnum
                WHERE t1.trncde = 'PUR' AND t1.trndte <= ? AND t2.itmcde IN ($pur_placeholders)
                ORDER BY t1.trndte DESC, t2.recid DESC";
            $stmt_pur = $link->prepare($pur_cost_query);
            $stmt_pur->execute(array_merge(array(date('Y-m-d')), $no_cost_items));
            $pur_rows = $stmt_pur->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $unit_costs = platform_cogs_unit_costs($item_list, $cost_cache, $pur_rows, $pcs_unmcde, $conv_map);
    $grouped = platform_cogs_build_groups($sal_rows, $unit_costs, $pcs_unmcde, $conv_map);
    $groups = $grouped['groups'];
    ksort($groups);

    // ---- render ----
    if (empty($groups)) {

        $pdf->ezPlaceData($C_LABEL, $xtop - 15, xls_safe_text("No sales records found for the selected period."), 10, 'left');

    } else {

        platform_cogs_column_header($pdf, $xtop);
        $xtop -= 23;

        foreach ($groups as $platform => $platform_data) {

            $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("<b>" . $platform . "</b>"), 10, 'left');
            $xtop -= 15;

            foreach (platform_cogs_sort_shops($platform_data['shops']) as $shop_label => $shop_data) {

                $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("<b>    " . $shop_label . "</b>"), 9, 'left');
                $xtop -= 14;
                platform_cogs_page_break($pdf, $xtop);

                foreach ($shop_data['items'] as $item) {
                    $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("        " . $item['dsc']), 9, 'left');
                    $pdf->ezPlaceData($C_QTY,   $xtop, number_format($item['qty']), 9, 'right');
                    $pdf->ezPlaceData($C_COST,  $xtop, number_format($item['cost'], 2), 9, 'right');
                    $pdf->ezPlaceData($C_TOTAL, $xtop, number_format($item['total'], 2), 9, 'right');
                    $xtop -= 14;
                    platform_cogs_page_break($pdf, $xtop);
                }

                $pdf->line($C_QTY - 60, $xtop + 10, $C_TOTAL, $xtop + 10);
                $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("<b>    Subtotal COGS - " . $shop_label . "</b>"), 9, 'left');
                $pdf->ezPlaceData($C_QTY,   $xtop, "<b>" . number_format($shop_data['qty']) . "</b>", 9, 'right');
                $pdf->ezPlaceData($C_COST,  $xtop, "", 9, 'right');
                $pdf->ezPlaceData($C_TOTAL, $xtop, "<b>" . number_format($shop_data['total'], 2) . "</b>", 9, 'right');
                $xtop -= 18;

                if ($is_tab) {
                    $pdf->ezPlaceData($C_LABEL, $xtop, '', 9, 'left');
                    $xtop -= 14;
                }
                platform_cogs_page_break($pdf, $xtop);
            }

            $pdf->line(25, $xtop + 10, $C_TOTAL, $xtop + 10);
            $pdf->ezPlaceData($C_LABEL, $xtop, xls_safe_text("<b>Total COGS - " . $platform . "</b>"), 10, 'left');
            $pdf->ezPlaceData($C_QTY,   $xtop, "<b>" . number_format($platform_data['qty']) . "</b>", 10, 'right');
            $pdf->ezPlaceData($C_COST,  $xtop, "", 9, 'right');
            $pdf->ezPlaceData($C_TOTAL, $xtop, "<b>" . number_format($platform_data['total'], 2) . "</b>", 10, 'right');
            $pdf->line(25, $xtop - 5, $C_TOTAL, $xtop - 5);
            $xtop -= 26;

            if ($is_tab) {
                $pdf->ezPlaceData($C_LABEL, $xtop, '', 9, 'left');
                $xtop -= 14;
            }
            platform_cogs_page_break($pdf, $xtop);
        }

        $pdf->line(25, $xtop + 8, $C_TOTAL, $xtop + 8);
        $pdf->ezPlaceData($C_LABEL, $xtop - 6, xls_safe_text("<b>Grand Total COGS</b>"), 11, 'left');
        $pdf->ezPlaceData($C_QTY,   $xtop - 6, "<b>" . number_format($grouped['grand_qty']) . "</b>", 11, 'right');
        $pdf->ezPlaceData($C_COST,  $xtop - 6, "", 9, 'right');
        $pdf->ezPlaceData($C_TOTAL, $xtop - 6, "<b>" . number_format($grouped['grand_total'], 2) . "</b>", 11, 'right');
        $xtop -= 20;
    }

    if ($grouped['excluded_count'] > 0) {
        $pdf->ezPlaceData($C_LABEL, $xtop - 10, xls_safe_text("Note: Excluded " . $grouped['excluded_count'] . " sale line(s) with no UOM conversion to pcs."), 9, 'left');
    }

    $pdf->addText(30, 15, 8, "Date Printed : " . date("F j, Y, g:i A"), $angle = 0, $wordspaceadjust = 1);
    $pdf->ezStream();
    ob_end_flush();

    function platform_cogs_column_header($pdf, $xtop)
    {
        global $C_LABEL, $C_QTY, $C_COST, $C_TOTAL;
        $pdf->ezPlaceData($C_LABEL, $xtop - 9, xls_safe_text("<b>Platform / Shop / Item</b>"), 9, 'left');
        $pdf->ezPlaceData($C_QTY,   $xtop - 9, xls_safe_text("<b>Qty (pcs)</b>"), 9, 'right');
        $pdf->ezPlaceData($C_COST,  $xtop - 9, xls_safe_text("<b>Cost</b>"), 9, 'right');
        $pdf->ezPlaceData($C_TOTAL, $xtop - 9, xls_safe_text("<b>Total COGS</b>"), 9, 'right');
        $pdf->line(25, $xtop - 12, $C_TOTAL, $xtop - 12);
    }

    function platform_cogs_page_break($pdf, &$xtop)
    {
        if ($xtop > 60) {
            return;
        }
        $pdf->ezNewPage();
        $xtop = 490; // below the 4-line page header repeated on every page (550 -> 505)
        platform_cogs_column_header($pdf, $xtop);
        $xtop -= 23;
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

        return trim($string);
    }

    // Pcs per 1 unit of $unmcde for this item: 1 for pcs, itemunitfile conversion otherwise, null if unknown.
    function platform_cogs_is_pcs($unmcde, $pcs_unmcde)
    {
        $unmcde = trim((string)$unmcde);
        return $unmcde !== '' && ($unmcde === (string)$pcs_unmcde || strtolower($unmcde) === 'pcs');
    }

    function platform_cogs_line_conversion($itmcde, $unmcde, $pcs_unmcde, $conv_map)
    {
        $unmcde = trim((string)$unmcde);
        if (platform_cogs_is_pcs($unmcde, $pcs_unmcde)) {
            return 1.0;
        }
        $key = $itmcde . '|' . $unmcde;
        return (isset($conv_map[$key]) && (float)$conv_map[$key] > 0) ? (float)$conv_map[$key] : null;
    }

    // Cost per pc per item: Cost price; else latest pcs PUR; else latest other-UOM PUR / conversion; else 0.
    // $pur_rows must be sorted latest first (trndte DESC, recid DESC).
    function platform_cogs_unit_costs($item_list, $cost_cache, $pur_rows, $pcs_unmcde, $conv_map)
    {
        $costs = array();
        foreach ($item_list as $itm) {
            if (!empty($cost_cache[$itm]) && (float)$cost_cache[$itm] != 0) {
                $costs[(string)$itm] = (float)$cost_cache[$itm];
            }
        }

        $derived = array(); // latest non-pcs purchase converted to per-pc
        foreach ($pur_rows as $row) {
            $itm = (string)$row['itmcde'];
            if (isset($costs[$itm])) {
                continue;
            }
            if (platform_cogs_is_pcs($row['unmcde'], $pcs_unmcde)) {
                $costs[$itm] = (float)$row['untprc'];
                continue;
            }
            $conv = platform_cogs_line_conversion($itm, $row['unmcde'], $pcs_unmcde, $conv_map);
            if ($conv !== null && !isset($derived[$itm])) {
                $derived[$itm] = (float)$row['untprc'] / $conv;
            }
        }

        foreach ($item_list as $itm) {
            $itm = (string)$itm;
            if (!isset($costs[$itm])) {
                $costs[$itm] = isset($derived[$itm]) ? $derived[$itm] : 0;
            }
        }
        return $costs;
    }

    // Groups SAL rows into Platform -> Shop -> Item with pcs qty and COGS. Pure (no DB/PDF) so it's unit-testable.
    // Each $sal_row needs: itmcde, itmdsc, unmcde, itmqty, platform_upload, platform_shop_title
    function platform_cogs_build_groups($sal_rows, $unit_costs, $pcs_unmcde, $conv_map)
    {
        $groups = array();
        $grand_qty = 0;
        $grand_total = 0;
        $excluded_count = 0;

        foreach ($sal_rows as $row) {
            $itm = (string)$row['itmcde'];
            $conv = platform_cogs_line_conversion($itm, $row['unmcde'], $pcs_unmcde, $conv_map);
            if ($conv === null) {
                $excluded_count++;
                continue;
            }

            $platform = trim((string)$row['platform_upload']);
            if ($platform === '') {
                $platform = '(No Platform)';
            }
            $shop = !empty($row['platform_shop_title']) ? trim((string)$row['platform_shop_title']) : 'No Shop Assigned Yet';

            $qty = (float)$row['itmqty'] * $conv;
            $cost = isset($unit_costs[$itm]) ? (float)$unit_costs[$itm] : 0;
            $total = $qty * $cost;

            if (!isset($groups[$platform])) {
                $groups[$platform] = array('shops' => array(), 'qty' => 0, 'total' => 0);
            }
            if (!isset($groups[$platform]['shops'][$shop])) {
                $groups[$platform]['shops'][$shop] = array('items' => array(), 'qty' => 0, 'total' => 0);
            }
            $items = &$groups[$platform]['shops'][$shop]['items'];
            if (!isset($items[$itm])) {
                $dsc = trim((string)(isset($row['itmdsc']) ? $row['itmdsc'] : ''));
                $items[$itm] = array('dsc' => $dsc !== '' ? $dsc : $itm, 'qty' => 0, 'cost' => $cost, 'total' => 0);
            }
            $items[$itm]['qty'] += $qty;
            $items[$itm]['total'] += $total;
            unset($items);

            $groups[$platform]['shops'][$shop]['qty'] += $qty;
            $groups[$platform]['shops'][$shop]['total'] += $total;
            $groups[$platform]['qty'] += $qty;
            $groups[$platform]['total'] += $total;
            $grand_qty += $qty;
            $grand_total += $total;
        }

        foreach ($groups as $platform => $platform_data) {
            foreach ($platform_data['shops'] as $shop => $shop_data) {
                uasort($groups[$platform]['shops'][$shop]['items'], function ($a, $b) {
                    return strcasecmp($a['dsc'], $b['dsc']);
                });
            }
        }

        return array(
            'groups' => $groups,
            'grand_qty' => $grand_qty,
            'grand_total' => $grand_total,
            'excluded_count' => $excluded_count
        );
    }

    // Sorts one platform's shop map: real shops alphabetically, "No Shop Assigned Yet" always last.
    function platform_cogs_sort_shops($shops)
    {
        $unassigned = null;
        if (isset($shops['No Shop Assigned Yet'])) {
            $unassigned = $shops['No Shop Assigned Yet'];
            unset($shops['No Shop Assigned Yet']);
        }

        ksort($shops);

        if ($unassigned !== null) {
            $shops['No Shop Assigned Yet'] = $unassigned;
        }

        return $shops;
    }
?>
