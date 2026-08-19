<?php
    //var_dump($_POST);

    session_start();
    require_once("resources/db_init.php") ;
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
        $session_key = 'pregenerated_report_' . $token;
        if(!isset($_SESSION[$session_key])){
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Report Expired</title></head><body>';
            echo '<h3>Report Expired</h3><p>The report has expired or is no longer available. Please generate a new report.</p>';
            echo '<p><a href="inventory_balance.php">Go back to Inventory Balance</a></p>';
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
            echo '<p><a href="inventory_balance.php">Go back to Inventory Balance</a></p>';
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
            header('Content-Disposition: attachment; filename="inventory_balance_' . date('Y-m-d_H-i-s') . '.xls"');
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="inventory_balance_' . date('Y-m-d_H-i-s') . '.pdf"');
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

        // Backend validation: require item selection
        if(!isset($_POST['item']) || trim($_POST['item']) === ''){
            echo json_encode(array('success' => false, 'error' => 'Please select an item before exporting.'));
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
        $temp_file = $temp_dir . '/inv_balance_' . $token . $file_extension;

        // Generate the report (same logic as below, but capture output)
        try {
            $report_content = generate_inventory_balance_report($link, $_POST, $_SESSION, $is_tab_export);

            // Save to temp file
            if(file_put_contents($temp_file, $report_content) === false){
                echo json_encode(array('success' => false, 'error' => 'Failed to save report. Please try again.'));
                exit;
            }

            // Store reference in session
            $_SESSION['pregenerated_report_' . $token] = array(
                'file_path' => $temp_file,
                'is_tab' => $is_tab_export,
                'created_at' => time()
            );

            // Clean up old pre-generated reports (older than 10 minutes)
            foreach($_SESSION as $key => $value){
                if(strpos($key, 'pregenerated_report_') === 0 && isset($value['created_at'])){
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

    // Backend validation: require item selection
    if(!isset($_POST['item']) || trim($_POST['item']) === ''){
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>Export Error</title></head><body>';
        echo '<h3>Export Error</h3><p>Please select an item before exporting.</p>';
        echo '<p><a href="javascript:window.close();">Close this window</a></p>';
        echo '</body></html>';
        exit;
    }

    ob_start();

    $xreport_title = "List of items";
    $is_tab_export = (isset($_POST['txt_output_type']) && $_POST['txt_output_type'] == 'tab');

    if ($is_tab_export)
	{
		$pdf = new tab_ezpdf('Letter','portrait');
	}
	else
	{
		$pdf = new Cezpdf('Letter','portrait');
	}

    $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
	$pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);

    date_default_timezone_set('Asia/Manila');
    $date_printed = date("F j, Y h:i:s A");

    $xtop = 750;
    $xleft = 25;
    $line_right = 585;
    $col_item = 25;
    $col_warehouse = 185;
    $col_uom = 370;
    $col_balance = 500;
    $item_max_width = 145;
    $warehouse_max_width = 170;
    $uom_max_width = 115;

    /**header**/
    $fields_count = 0;
    $fields = '';

        $progname_hidden ='';
        if(isset($_POST['trncde_hidden']) && $_POST['trncde_hidden'] == 'SAL'){
            $progname_hidden = "Sales";
        }
        else if(isset($_POST['trncde_hidden']) && $_POST['trncde_hidden'] == 'SRT'){
            $progname_hidden = "Sales Return";
        }
        else if(isset($_POST['trncde_hidden']) && $_POST['trncde_hidden'] == 'PUR'){
            $progname_hidden = "Purchases";
        }

		$xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Inventory Balance</b>", 15, 'left' );
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".$_SESSION['userdesc']." (Summarized)</b>", 9, 'left' );
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop -= 20;

		$pdf->setLineStyle(.5);
		$pdf->line($xleft, $xtop+10, $line_right, $xtop+10);
        $pdf->line($xleft, $xtop-3, $line_right, $xtop-3);

        $xfields_heaeder_counter = 0;

        $pdf->ezPlaceData($col_item,$xtop,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",10,'left');
        $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",10,'left');
        $pdf->ezPlaceData($col_balance,$xtop,"<b>Balance</b>",10,'right');

        $xleft = 25;
		$xtop -= 15;

		$pdf->restoreState();
		$pdf->closeObject();
		$pdf->addObject($xheader,'all');

	/***header**/

    $xfilter = '';
    $xfilter2 = '';
    $params = array();

    if(isset($_POST['date_search']) && !empty($_POST['date_search'])){
        $date_sql = date("Y-m-d", strtotime($_POST['date_search']));
        $xfilter2 .= " AND tranfile1.trndte <= ?";
        $params[] = $date_sql;
    }

    if(isset($_POST['item']) && !empty($_POST['item'])){
        $xfilter .= " AND tranfile2.itmcde = ?";
        $params[] = $_POST['item'];
    }

    // FIX 1: Wrap in a subquery so the CASE/SUM expression is computed only once.
    //         The old HAVING clause duplicated the entire SUM(CASE...) block, meaning
    //         MySQL evaluated it twice per row. The outer WHERE filters on the alias instead.
    //
    // FIX 2: CASE WHEN tranfile1.trncde = 'SAL' rewritten as CASE tranfile1.trncde WHEN 'SAL'
    //         (simple CASE) — marginally faster since MySQL compares one value, not re-evaluates
    //         a boolean expression per branch.
    $select_db = "
        SELECT *
        FROM (
            SELECT
                tranfile2.itmcde,
                COALESCE(itemfile.itmdsc, tranfile2.itmcde)                          AS item_display,
                tranfile2.warcde,
                tranfile2.warehouse_floor_id,
                COALESCE(warehouse.warehouse_name, '')                               AS warehouse_name,
                COALESCE(warehouse_floor.floor_no, '')                               AS floor_no,
                tranfile2.unmcde,
                COALESCE(itemunitmeasurefile.unmdsc, tranfile2.unmcde)               AS uom_desc,
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
            LEFT JOIN tranfile1           ON tranfile1.docnum           = tranfile2.docnum
            LEFT JOIN itemfile            ON tranfile2.itmcde           = itemfile.itmcde
            LEFT JOIN warehouse           ON tranfile2.warcde           = warehouse.warcde
            LEFT JOIN warehouse_floor     ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
            LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde           = itemunitmeasurefile.unmcde
            LEFT JOIN itemunitfile        ON tranfile2.itmcde           = itemunitfile.itmcde
                                         AND tranfile2.unmcde           = itemunitfile.unmcde
            WHERE TRUE {$xfilter2}{$xfilter}
            GROUP BY
                tranfile2.itmcde,
                itemfile.itmdsc,
                tranfile2.warcde,
                tranfile2.warehouse_floor_id,
                warehouse.warehouse_name,
                warehouse_floor.floor_no,
                tranfile2.unmcde,
                itemunitmeasurefile.unmdsc,
                itemunitfile.conversion
        ) AS sub
        WHERE uom_balance <> 0
        ORDER BY item_display ASC, warehouse_name ASC, floor_no ASC, uom_balance DESC
    ";

    $stmt_main = $link->prepare($select_db);
    $stmt_main->execute($params);

    $current_item_code = '';
    $current_location_key = '';
    $has_data = false;
    $floor_subtotal_pcs = 0;
    $item_total_pcs = 0;
    $grand_total_pcs = 0;

    // Fetch all rows first to handle end-of-group logic
    $all_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);
    $row_count = count($all_rows);

    for($row_index = 0; $row_index < $row_count; $row_index++){
        $rs_main = $all_rows[$row_index];
        $has_data = true;

        // Peek at next row to detect group changes
        $next_row = ($row_index + 1 < $row_count) ? $all_rows[$row_index + 1] : null;
        $is_last_row_for_floor = ($next_row === null) || (
            ($next_row['itmcde'] !== $rs_main['itmcde']) ||
            (($next_row['warcde'] . '|' . $next_row['warehouse_floor_id']) !== 
             ($rs_main['warcde'] . '|' . $rs_main['warehouse_floor_id']))
        );
        $is_last_row_for_item = ($next_row === null) || ($next_row['itmcde'] !== $rs_main['itmcde']);

        $show_item_name = ($current_item_code !== $rs_main['itmcde']);
        if($show_item_name){
            if($current_item_code !== ''){
                if($is_tab_export){
                    $pdf->ezPlaceData($col_item,$xtop,"",9,"left");
                    $pdf->ezPlaceData($col_warehouse,$xtop,"",9,"left");
                    $pdf->ezPlaceData($col_uom,$xtop,"",9,"left");
                    $pdf->ezPlaceData($col_balance,$xtop,"",9,"right");
                    $xtop -= 15;
                } else {
                    $xtop -= 10;
                }
            }

            $current_item_code = $rs_main['itmcde'];
            $current_location_key = '';
        }

        $location_key = $rs_main['warcde'] . '|' . $rs_main['warehouse_floor_id'];
        $show_location = ($current_location_key !== $location_key);
        if($show_location){
            $current_location_key = $location_key;
        }

        // Build UOM display with conversion label
        $uom_desc = trim((string)$rs_main['uom_desc']);
        $conversion = (float)$rs_main['conversion'];
        if($conversion <= 0) $conversion = 1;

        $is_pcs = (strtolower($uom_desc) === 'pcs');
        if($is_pcs){
            $uom_display = 'pcs';
        } else {
            $conv_int = (int)$conversion;
            $uom_display = $uom_desc . '(' . $conv_int . 'pcs)';
        }

        $uom_balance = (float)$rs_main['uom_balance'];
        $balance_in_pcs = $uom_balance * $conversion;

        // Accumulate totals
        $floor_subtotal_pcs += $balance_in_pcs;
        $item_total_pcs += $balance_in_pcs;
        $grand_total_pcs += $balance_in_pcs;

        $item_display      = $show_item_name  ? $rs_main['item_display']                                                           : '';
        $warehouse_display = $show_location   ? build_warehouse_display($rs_main['warehouse_name'], $rs_main['floor_no']) : '';

        // FIX 3: wrap_report_text() has an internal static cache so the same item/warehouse
        //         string is not measured character-by-character more than once.
        $item_lines      = wrap_report_text($item_display,      $item_max_width,      9);
        $warehouse_lines = wrap_report_text($warehouse_display, $warehouse_max_width, 9);
        $uom_lines       = wrap_report_text($uom_display,       $uom_max_width,       9);
        $line_count  = max(count($item_lines), count($warehouse_lines), count($uom_lines), 1);
        $row_height  = 15 + ((max(1, $line_count) - 1) * 10);

        if(($xtop - $row_height) <= 60){
            $pdf->ezNewPage();
            $xtop = 685;
        }

        $row_y = $xtop;
        foreach($item_lines as $item_line_index => $item_line_text){
            if($item_line_text !== '' || ($is_tab_export && $item_line_index === 0)){
                $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), $item_line_text, 9, "left");
            }
        }

        foreach($warehouse_lines as $warehouse_line_index => $warehouse_line_text){
            if($warehouse_line_text !== '' || ($is_tab_export && $warehouse_line_index === 0)){
                $pdf->ezPlaceData($col_warehouse, $row_y - ($warehouse_line_index * 10), $warehouse_line_text, 9, "left");
            }
        }

        foreach($uom_lines as $uom_line_index => $uom_line_text){
            if($uom_line_text !== '' || ($is_tab_export && $uom_line_index === 0)){
                $pdf->ezPlaceData($col_uom, $row_y - ($uom_line_index * 10), $uom_line_text, 9, "left");
            }
        }

        $pdf->ezPlaceData($col_balance, $row_y, format_report_balance($uom_balance), 9, "right");
        $xtop -= $row_height;

        // Print floor subtotal if this is the last row for this floor
        if($is_last_row_for_floor && $floor_subtotal_pcs != 0){
            print_floor_subtotal($xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $floor_subtotal_pcs, $is_tab_export);
            $xtop -= 18;
            $floor_subtotal_pcs = 0;
        }

        // Print item total if this is the last row for this item
        if($is_last_row_for_item){
            print_item_total($xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $item_total_pcs, $is_tab_export, $line_right);
            $xtop -= 24;
            $item_total_pcs = 0;
        }
    }

    if(!$has_data){
        $pdf->ezPlaceData($col_item, $xtop, "No data found.", 9, "left");
        $xtop -= 24;
    } else {
        // Print grand total
        if(($xtop - 25) <= 60){
            $pdf->ezNewPage();
            $xtop = 685;
        }

        $pdf->setLineStyle(.5);
        $pdf->line(25, $xtop - 5, $line_right, $xtop - 5);
        $xtop -= 18;

        if($is_tab_export){
            $pdf->ezPlaceData($col_item, $xtop, "", 9, "left");
            $pdf->ezPlaceData($col_warehouse, $xtop, "", 9, "left");
        }
        $pdf->ezPlaceData($col_uom, $xtop, "<b>Grand total(pcs)</b>", 9, "left");
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($grand_total_pcs) . "</b>", 9, "right");
    }

    function print_floor_subtotal(&$xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $floor_subtotal_pcs, $is_tab_export)
    {
        global $pdf;

        if(($xtop - 18) <= 60){
            $pdf->ezNewPage();
            $xtop = 685;
        }

        if($is_tab_export){
            $pdf->ezPlaceData($col_item, $xtop, "", 9, "left");
            $pdf->ezPlaceData($col_warehouse, $xtop, "", 9, "left");
        }
        $pdf->ezPlaceData($col_uom, $xtop, "Subtotal(pcs):", 9, "left");
        $pdf->ezPlaceData($col_balance, $xtop, format_report_balance($floor_subtotal_pcs), 9, "right");
    }

    function print_item_total(&$xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $item_total_pcs, $is_tab_export, $line_right)
    {
        global $pdf;

        if(($xtop - 20) <= 60){
            $pdf->ezNewPage();
            $xtop = 685;
        }

        if($is_tab_export){
            $pdf->ezPlaceData($col_item, $xtop, "", 9, "left");
        }
        $pdf->ezPlaceData($col_warehouse, $xtop, "<b>Total(pcs)</b>", 9, "left");
        $pdf->ezPlaceData($col_uom, $xtop, "", 9, "left");
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . format_report_balance($item_total_pcs) . "</b>", 9, "right");

        // Separator line after item total
        $pdf->setLineStyle(.3);
        $pdf->line(25, $xtop - 8, $line_right, $xtop - 8);
    }

	$pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);
	$pdf->ezStream();
    ob_end_flush();

    // ========================================================================
    // FUNCTION: Generate Inventory Balance Report (for pre-generation flow)
    // ========================================================================
    function generate_inventory_balance_report($link, $post_data, $session_data, $is_tab_export) {
        // Initialize global array for tab_ezpdf (XLS mode)
        global $glo_arr, $glo_top;
        $glo_arr = array();
        $glo_top = 0;

        ob_start();

        $xreport_title = "List of items";

        if ($is_tab_export) {
            $pdf = new tab_ezpdf('Letter','portrait');
        } else {
            $pdf = new Cezpdf('Letter','portrait');
        }

        $pdf->selectFont("ezpdfclass/fonts/Helvetica.afm");
        $pdf->ezStartPageNumbers(500,15,8,'right','Page {PAGENUM}  of  {TOTALPAGENUM}',1);

        date_default_timezone_set('Asia/Manila');
        $date_printed = date("F j, Y h:i:s A");

        $xtop = 750;
        $xleft = 25;
        $line_right = 585;
        $col_item = 25;
        $col_warehouse = 185;
        $col_uom = 370;
        $col_balance = 500;
        $item_max_width = 145;
        $warehouse_max_width = 170;
        $uom_max_width = 115;

        $progname_hidden ='';
        if(isset($post_data['trncde_hidden']) && $post_data['trncde_hidden'] == 'SAL'){
            $progname_hidden = "Sales";
        }
        else if(isset($post_data['trncde_hidden']) && $post_data['trncde_hidden'] == 'SRT'){
            $progname_hidden = "Sales Return";
        }
        else if(isset($post_data['trncde_hidden']) && $post_data['trncde_hidden'] == 'PUR'){
            $progname_hidden = "Purchases";
        }

        $xheader = $pdf->openObject();
        $pdf->saveState();
        $pdf->ezPlaceData($xleft, $xtop,"<b>Inventory Balance</b>", 15, 'left' );
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop,"<b>Pdf Report by: ".($session_data['userdesc'] ?? '')." (Summarized)</b>", 9, 'left' );
        $xtop -= 15;
        $pdf->ezPlaceData($xleft, $xtop, 'Date Printed : '.$date_printed, 10, 'left' );
        $xtop -= 20;

        $pdf->setLineStyle(.5);
        $pdf->line($xleft, $xtop+10, $line_right, $xtop+10);
        $pdf->line($xleft, $xtop-3, $line_right, $xtop-3);

        $pdf->ezPlaceData($col_item,$xtop,"<b>Item</b>",10,'left');
        $pdf->ezPlaceData($col_warehouse,$xtop,"<b>Warehouse</b>",10,'left');
        $pdf->ezPlaceData($col_uom,$xtop,"<b>UOM</b>",10,'left');
        $pdf->ezPlaceData($col_balance,$xtop,"<b>Balance</b>",10,'right');

        $xleft = 25;
        $xtop -= 15;

        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($xheader,'all');

        $xfilter = '';
        $xfilter2 = '';
        $params = array();

        if(isset($post_data['date_search']) && !empty($post_data['date_search'])){
            $date_sql = date("Y-m-d", strtotime($post_data['date_search']));
            $xfilter2 .= " AND tranfile1.trndte <= ?";
            $params[] = $date_sql;
        }

        if(isset($post_data['item']) && !empty($post_data['item'])){
            $xfilter .= " AND tranfile2.itmcde = ?";
            $params[] = $post_data['item'];
        }

        $select_db = "
            SELECT *
            FROM (
                SELECT
                    tranfile2.itmcde,
                    COALESCE(itemfile.itmdsc, tranfile2.itmcde)                          AS item_display,
                    tranfile2.warcde,
                    tranfile2.warehouse_floor_id,
                    COALESCE(warehouse.warehouse_name, '')                               AS warehouse_name,
                    COALESCE(warehouse_floor.floor_no, '')                               AS floor_no,
                    tranfile2.unmcde,
                    COALESCE(itemunitmeasurefile.unmdsc, tranfile2.unmcde)               AS uom_desc,
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
                LEFT JOIN tranfile1           ON tranfile1.docnum           = tranfile2.docnum
                LEFT JOIN itemfile            ON tranfile2.itmcde           = itemfile.itmcde
                LEFT JOIN warehouse           ON tranfile2.warcde           = warehouse.warcde
                LEFT JOIN warehouse_floor     ON tranfile2.warehouse_floor_id = warehouse_floor.warehouse_floor_id
                LEFT JOIN itemunitmeasurefile ON tranfile2.unmcde           = itemunitmeasurefile.unmcde
                LEFT JOIN itemunitfile        ON tranfile2.itmcde           = itemunitfile.itmcde
                                             AND tranfile2.unmcde           = itemunitfile.unmcde
                WHERE TRUE {$xfilter2}{$xfilter}
                GROUP BY
                    tranfile2.itmcde,
                    itemfile.itmdsc,
                    tranfile2.warcde,
                    tranfile2.warehouse_floor_id,
                    warehouse.warehouse_name,
                    warehouse_floor.floor_no,
                    tranfile2.unmcde,
                    itemunitmeasurefile.unmdsc,
                    itemunitfile.conversion
            ) AS sub
            WHERE uom_balance <> 0
            ORDER BY item_display ASC, warehouse_name ASC, floor_no ASC, uom_balance DESC
        ";

        $stmt_main = $link->prepare($select_db);
        $stmt_main->execute($params);

        $current_item_code = '';
        $current_location_key = '';
        $has_data = false;
        $floor_subtotal_pcs = 0;
        $item_total_pcs = 0;
        $grand_total_pcs = 0;

        $all_rows = $stmt_main->fetchAll(PDO::FETCH_ASSOC);
        $row_count = count($all_rows);

        for($row_index = 0; $row_index < $row_count; $row_index++){
            $rs_main = $all_rows[$row_index];
            $has_data = true;

            $next_row = ($row_index + 1 < $row_count) ? $all_rows[$row_index + 1] : null;
            $is_last_row_for_floor = ($next_row === null) || (
                ($next_row['itmcde'] !== $rs_main['itmcde']) ||
                (($next_row['warcde'] . '|' . $next_row['warehouse_floor_id']) !==
                 ($rs_main['warcde'] . '|' . $rs_main['warehouse_floor_id']))
            );
            $is_last_row_for_item = ($next_row === null) || ($next_row['itmcde'] !== $rs_main['itmcde']);

            $show_item_name = ($current_item_code !== $rs_main['itmcde']);
            if($show_item_name){
                if($current_item_code !== ''){
                    if($is_tab_export){
                        $pdf->ezPlaceData($col_item,$xtop,"",9,"left");
                        $pdf->ezPlaceData($col_warehouse,$xtop,"",9,"left");
                        $pdf->ezPlaceData($col_uom,$xtop,"",9,"left");
                        $pdf->ezPlaceData($col_balance,$xtop,"",9,"right");
                        $xtop -= 15;
                    } else {
                        $xtop -= 10;
                    }
                }

                $current_item_code = $rs_main['itmcde'];
                $current_location_key = '';
            }

            $location_key = $rs_main['warcde'] . '|' . $rs_main['warehouse_floor_id'];
            $show_location = ($current_location_key !== $location_key);
            if($show_location){
                $current_location_key = $location_key;
            }

            $uom_desc = trim((string)$rs_main['uom_desc']);
            $conversion = (float)$rs_main['conversion'];
            if($conversion <= 0) $conversion = 1;

            $is_pcs = (strtolower($uom_desc) === 'pcs');
            if($is_pcs){
                $uom_display = 'pcs';
            } else {
                $conv_int = (int)$conversion;
                $uom_display = $uom_desc . '(' . $conv_int . 'pcs)';
            }

            $uom_balance = (float)$rs_main['uom_balance'];
            $balance_in_pcs = $uom_balance * $conversion;

            $floor_subtotal_pcs += $balance_in_pcs;
            $item_total_pcs += $balance_in_pcs;
            $grand_total_pcs += $balance_in_pcs;

            $item_display      = $show_item_name  ? $rs_main['item_display'] : '';
            $warehouse_display = $show_location   ? _pregen_build_warehouse_display($rs_main['warehouse_name'], $rs_main['floor_no']) : '';

            $item_lines      = _pregen_wrap_report_text($pdf, $item_display,      $item_max_width,      9, $is_tab_export);
            $warehouse_lines = _pregen_wrap_report_text($pdf, $warehouse_display, $warehouse_max_width, 9, $is_tab_export);
            $uom_lines       = _pregen_wrap_report_text($pdf, $uom_display,       $uom_max_width,       9, $is_tab_export);
            $line_count  = max(count($item_lines), count($warehouse_lines), count($uom_lines), 1);
            $row_height  = 15 + ((max(1, $line_count) - 1) * 10);

            if(($xtop - $row_height) <= 60){
                $pdf->ezNewPage();
                $xtop = 685;
            }

            $row_y = $xtop;
            foreach($item_lines as $item_line_index => $item_line_text){
                if($item_line_text !== '' || ($is_tab_export && $item_line_index === 0)){
                    $pdf->ezPlaceData($col_item, $row_y - ($item_line_index * 10), $item_line_text, 9, "left");
                }
            }

            foreach($warehouse_lines as $warehouse_line_index => $warehouse_line_text){
                if($warehouse_line_text !== '' || ($is_tab_export && $warehouse_line_index === 0)){
                    $pdf->ezPlaceData($col_warehouse, $row_y - ($warehouse_line_index * 10), $warehouse_line_text, 9, "left");
                }
            }

            foreach($uom_lines as $uom_line_index => $uom_line_text){
                if($uom_line_text !== '' || ($is_tab_export && $uom_line_index === 0)){
                    $pdf->ezPlaceData($col_uom, $row_y - ($uom_line_index * 10), $uom_line_text, 9, "left");
                }
            }

            $pdf->ezPlaceData($col_balance, $row_y, _pregen_format_report_balance($uom_balance), 9, "right");
            $xtop -= $row_height;

            if($is_last_row_for_floor && $floor_subtotal_pcs != 0){
                _pregen_print_floor_subtotal($pdf, $xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $floor_subtotal_pcs, $is_tab_export);
                $xtop -= 18;
                $floor_subtotal_pcs = 0;
            }

            if($is_last_row_for_item){
                _pregen_print_item_total($pdf, $xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $item_total_pcs, $is_tab_export, $line_right);
                $xtop -= 24;
                $item_total_pcs = 0;
            }
        }

        if(!$has_data){
            $pdf->ezPlaceData($col_item, $xtop, "No data found.", 9, "left");
            $xtop -= 24;
        } else {
            if(($xtop - 25) <= 60){
                $pdf->ezNewPage();
                $xtop = 685;
            }

            $pdf->setLineStyle(.5);
            $pdf->line(25, $xtop - 5, $line_right, $xtop - 5);
            $xtop -= 18;

            if($is_tab_export){
                $pdf->ezPlaceData($col_item, $xtop, "", 9, "left");
                $pdf->ezPlaceData($col_warehouse, $xtop, "", 9, "left");
            }
            $pdf->ezPlaceData($col_uom, $xtop, "<b>Grand total(pcs)</b>", 9, "left");
            $pdf->ezPlaceData($col_balance, $xtop, "<b>" . _pregen_format_report_balance($grand_total_pcs) . "</b>", 9, "right");
        }

        $pdf->addText(30,15,8,"Date Printed : ".date("F j, Y, g:i A"),$angle=0,$wordspaceadjust=1);

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
    function _pregen_print_floor_subtotal(&$pdf, &$xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $floor_subtotal_pcs, $is_tab_export) {
        if(($xtop - 18) <= 60){
            $pdf->ezNewPage();
            $xtop = 685;
        }

        if($is_tab_export){
            $pdf->ezPlaceData($col_item, $xtop, "", 9, "left");
            $pdf->ezPlaceData($col_warehouse, $xtop, "", 9, "left");
        }
        $pdf->ezPlaceData($col_uom, $xtop, "Subtotal(pcs):", 9, "left");
        $pdf->ezPlaceData($col_balance, $xtop, _pregen_format_report_balance($floor_subtotal_pcs), 9, "right");
    }

    function _pregen_print_item_total(&$pdf, &$xtop, $col_item, $col_warehouse, $col_uom, $col_balance, $item_total_pcs, $is_tab_export, $line_right) {
        if(($xtop - 20) <= 60){
            $pdf->ezNewPage();
            $xtop = 685;
        }

        if($is_tab_export){
            $pdf->ezPlaceData($col_item, $xtop, "", 9, "left");
        }
        $pdf->ezPlaceData($col_warehouse, $xtop, "<b>Total(pcs)</b>", 9, "left");
        $pdf->ezPlaceData($col_uom, $xtop, "", 9, "left");
        $pdf->ezPlaceData($col_balance, $xtop, "<b>" . _pregen_format_report_balance($item_total_pcs) . "</b>", 9, "right");

        $pdf->setLineStyle(.3);
        $pdf->line(25, $xtop - 8, $line_right, $xtop - 8);
    }

    function _pregen_wrap_report_text($pdf, $string, $max_wid, $fsize, $is_tab_export) {
        static $cache = [];

        $string = trim((string)$string);
        if($string === ''){
            return array('');
        }

        $cache_key = $max_wid . '|' . $fsize . '|' . $string;
        if(isset($cache[$cache_key])){
            return $cache[$cache_key];
        }

        if($is_tab_export || get_class($pdf) == 'tab_ezpdf'){
            return $cache[$cache_key] = array(_pregen_xls_safe_text($pdf, $string));
        }

        $max_wid -= 5;
        if($pdf->getTextWidth($fsize, $string) <= $max_wid){
            return $cache[$cache_key] = array($string);
        }

        $wrapped_lines = array();
        $remaining = $string;

        while($remaining !== ''){
            if($pdf->getTextWidth($fsize, $remaining) <= $max_wid){
                $wrapped_lines[] = $remaining;
                break;
            }

            $line = _pregen_fit_text_to_width($pdf, $remaining, $max_wid, $fsize);
            if($line === ''){
                $line = substr($remaining, 0, 1);
            }

            $last_space = strrpos($line, ' ');
            if($last_space !== false && $last_space > 0){
                $candidate_line = rtrim(substr($line, 0, $last_space));
                if($candidate_line !== ''){
                    $line = $candidate_line;
                }
            }

            $wrapped_lines[] = rtrim($line);
            $remaining = ltrim(substr($remaining, strlen($line)));
        }

        if(empty($wrapped_lines)){
            $wrapped_lines[] = $string;
        }

        return $cache[$cache_key] = $wrapped_lines;
    }

    function _pregen_fit_text_to_width($pdf, $string, $max_wid, $fsize) {
        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr = "";
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $max_wid){
                break;
            }
            $xxstr = $xxstr.$value;
        }

        return rtrim($xxstr);
    }

    function _pregen_build_warehouse_display($warehouse_name, $floor_no) {
        $warehouse_name = trim((string)$warehouse_name);
        $floor_no = trim((string)$floor_no);

        $warehouse_display = $warehouse_name;
        if($floor_no !== ''){
            $warehouse_display .= ($warehouse_display !== '' ? ' ' : '') . $floor_no . ' floor';
        }

        return trim($warehouse_display);
    }

    function _pregen_format_report_balance($value) {
        $formatted_balance = number_format((float)$value, 4, '.', ',');
        $formatted_balance = rtrim(rtrim($formatted_balance, '0'), '.');

        if($formatted_balance === '-0'){
            $formatted_balance = '0';
        }

        return $formatted_balance;
    }

    function _pregen_xls_safe_text($pdf, $string) {
        static $cache = [];

        $string = (string)$string;
        if(get_class($pdf) != 'tab_ezpdf'){
            return $string;
        }

        if(isset($cache[$string])){
            return $cache[$string];
        }

        if(function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        if(function_exists('iconv')){
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if($converted !== false && $converted !== ''){
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

    // FIX 3: Static cache added — the same string (item name, warehouse name, UOM) often
    //         repeats across many rows. Without caching, getTextWidth() loops character-by-
    //         character every time for the same input. The cache makes subsequent calls instant.
    function wrap_report_text($string, $max_wid, $fsize)
    {
        global $pdf;
        static $cache = [];

        $string = trim((string)$string);
        if($string === ''){
            return array('');
        }

        $cache_key = $max_wid . '|' . $fsize . '|' . $string;
        if(isset($cache[$cache_key])){
            return $cache[$cache_key];
        }

        if(get_class($pdf) == 'tab_ezpdf'){
            return $cache[$cache_key] = array(xls_safe_text($string));
        }

        $max_wid -= 5;
        if($pdf->getTextWidth($fsize, $string) <= $max_wid){
            return $cache[$cache_key] = array($string);
        }

        $wrapped_lines = array();
        $remaining = $string;

        while($remaining !== ''){
            if($pdf->getTextWidth($fsize, $remaining) <= $max_wid){
                $wrapped_lines[] = $remaining;
                break;
            }

            $line = fit_text_to_width($remaining, $max_wid, $fsize);
            if($line === ''){
                $line = substr($remaining, 0, 1);
            }

            $last_space = strrpos($line, ' ');
            if($last_space !== false && $last_space > 0){
                $candidate_line = rtrim(substr($line, 0, $last_space));
                if($candidate_line !== ''){
                    $line = $candidate_line;
                }
            }

            $wrapped_lines[] = rtrim($line);
            $remaining = ltrim(substr($remaining, strlen($line)));
        }

        if(empty($wrapped_lines)){
            $wrapped_lines[] = $string;
        }

        return $cache[$cache_key] = $wrapped_lines;
    }

    function fit_text_to_width($string,$max_wid,$fsize)
    {
        global $pdf;

        $string = (string)$string;
        if($string === ''){
            return '';
        }

        $xarr_str = str_split($string);
        $xxstr = "";
        foreach ($xarr_str as $value) {
            $xstr_wid = $pdf->getTextWidth($fsize,$xxstr.$value);
            if($xstr_wid > $max_wid)
            {
                break;
            }
            $xxstr = $xxstr.$value;
        }

        return rtrim($xxstr);
    }

    function build_warehouse_display($warehouse_name, $floor_no)
    {
        $warehouse_name = trim((string)$warehouse_name);
        $floor_no = trim((string)$floor_no);

        $warehouse_display = $warehouse_name;
        if($floor_no !== ''){
            $warehouse_display .= ($warehouse_display !== '' ? ' ' : '') . $floor_no . ' floor';
        }

        return trim($warehouse_display);
    }

    function format_report_balance($value)
    {
        $formatted_balance = number_format((float)$value, 4, '.', ',');
        $formatted_balance = rtrim(rtrim($formatted_balance, '0'), '.');

        if($formatted_balance === '-0'){
            $formatted_balance = '0';
        }

        return $formatted_balance;
    }

    // FIX 4: Static cache added — xls_safe_text() runs iconv + multiple regex passes.
    //         Item names and warehouse names repeat constantly so caching eliminates
    //         redundant encoding work for the same strings.
    function xls_safe_text($string)
    {
        global $pdf;
        static $cache = [];

        $string = (string)$string;
        if(get_class($pdf) != 'tab_ezpdf'){
            return $string;
        }

        if(isset($cache[$string])){
            return $cache[$string];
        }

        // Try to fix encoding issues first
        if(function_exists('mb_check_encoding') && !mb_check_encoding($string, 'UTF-8')){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        // Transliterate to ASCII to prevent layout-breaking chars in XLS
        if(function_exists('iconv')){
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            if($converted !== false && $converted !== ''){
                $string = $converted;
            } else {
                $string = preg_replace('/[^\x20-\x7E]/', '', $string);
            }
        } else {
            $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        }

        // Remove tabs, line breaks, and control chars that break TSV format
        $string = str_replace(array("\t", "\r", "\n", "\0"), ' ', $string);
        $string = preg_replace('/[\x00-\x1F\x7F]/', ' ', $string);
        $string = preg_replace('/\s{2,}/', ' ', $string);

        return $cache[$string] = trim($string);
    }
?>