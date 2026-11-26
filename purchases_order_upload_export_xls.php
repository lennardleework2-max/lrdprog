<?php
// Start output buffering early to catch any stray output
ob_start();

session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");

// Security check
if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body>';
    echo '<h3>Access Denied</h3><p>Please log in to export.</p>';
    echo '</body></html>';
    exit;
}

// Parse export data from hidden field
function po_upload_get_export_data_xls() {
    if(isset($_POST['hiddenExportData']) && trim((string)$_POST['hiddenExportData']) !== ''){
        $decoded = json_decode($_POST['hiddenExportData'], true);
        if(is_array($decoded)){
            return $decoded;
        }
    }
    return null;
}

$exportData = po_upload_get_export_data_xls();

if(!$exportData || empty($exportData['items'])){
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>No Data</title></head><body>';
    echo '<h3>No Data</h3><p>No upload data available for export. Please perform an upload first.</p>';
    echo '<p><a href="purchases_order_upload.php">Go back</a></p>';
    echo '</body></html>';
    exit;
}

// Log export activity
$username_session = isset($_SESSION['userdesc']) ? $_SESSION['userdesc'] : '';
$username_full_name = '';
if(isset($_SESSION['recid'])){
    $select_db_session_user = 'SELECT * FROM users WHERE recid = ?';
    $stmt_session_user = $link->prepare($select_db_session_user);
    $stmt_session_user->execute(array($_SESSION['recid']));
    $rs_session_user = $stmt_session_user->fetch();
    if($rs_session_user){
        $username_full_name = $rs_session_user["full_name"];
    }
}

$xtrndte_log = date("Y-m-d H:i:s");
$xprog_module_log = 'PURCHASES ORDER UPLOAD';
$xactivity_log = 'export_xls';
$xremarks_log = "Exported XLS from Purchases Order Upload (Doc: " . $exportData['docnum'] . ")";
PDO_UserActivityLog($link, $username_session, '', $xtrndte_log, $xprog_module_log, $xactivity_log, $username_full_name, $xremarks_log, 0, '', '', '', '', $username_session, '', '');

date_default_timezone_set('Asia/Manila');
$date_printed = date("F j, Y h:i:s A");

// Parse upload date
$date_uploaded_display = date('F j, Y');
if(isset($exportData['date_uploaded']) && !empty($exportData['date_uploaded'])){
    $date_uploaded_display = date('F j, Y', strtotime($exportData['date_uploaded']));
}

// Format number with 2 decimal places
function po_xls_format_number($num) {
    if($num === null || $num === ''){
        return '0.00';
    }
    return number_format((float)$num, 2, '.', ',');
}

// Clean and escape text for HTML output
function po_xls_clean_text($str) {
    $str = (string)$str;
    // Replace tabs and newlines with space
    $str = str_replace(array("\t", "\r\n", "\r", "\n"), ' ', $str);
    // Remove control characters
    $str = preg_replace('/[\x00-\x1F\x7F]/', ' ', $str);
    // Collapse multiple spaces
    $str = preg_replace('/\s+/', ' ', $str);
    // HTML escape for security
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Clean any buffered output before sending headers
ob_end_clean();

// Set headers for XLS download (using HTML table format for cross-platform compatibility)
$filename = 'purchases_upload_' . date('Y-m-d_H-i-s') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Output UTF-8 BOM for Excel compatibility with special characters
echo "\xEF\xBB\xBF";

// Use HTML table format - more reliable across different environments than TSV
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if gte mso 9]>
<xml>
<x:ExcelWorkbook>
<x:ExcelWorksheets>
<x:ExcelWorksheet>
<x:Name>Purchases Upload</x:Name>
<x:WorksheetOptions>
<x:DisplayGridlines/>
</x:WorksheetOptions>
</x:ExcelWorksheet>
</x:ExcelWorksheets>
</x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
td { mso-number-format:\@; }
.number { mso-number-format:"#,##0.00"; }
</style>
</head>
<body>
<table border="0" cellpadding="2" cellspacing="0">
<tr><td colspan="4"><b>Purchases Upload</b></td></tr>
<tr><td colspan="4">Report by: <?php echo htmlspecialchars($_SESSION['userdesc'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
<tr><td colspan="4">Date Printed: <?php echo htmlspecialchars($date_printed, ENT_QUOTES, 'UTF-8'); ?></td></tr>
<tr><td colspan="4">Date Uploaded: <?php echo htmlspecialchars($date_uploaded_display, ENT_QUOTES, 'UTF-8'); ?></td></tr>
<tr><td colspan="4"></td></tr>
<tr><td>Ordernum:</td><td colspan="3"><?php echo htmlspecialchars($exportData['ordernum'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
<tr><td>Docnum:</td><td colspan="3"><?php echo htmlspecialchars($exportData['docnum'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
<?php
$remarks_display = isset($exportData['remarks']) && trim($exportData['remarks']) !== '' ? $exportData['remarks'] : '-';
?>
<tr><td>Remarks:</td><td colspan="3"><?php echo po_xls_clean_text($remarks_display); ?></td></tr>
<tr><td>Supplier:</td><td colspan="3"><?php echo htmlspecialchars($exportData['suppdsc'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
<tr><td colspan="4"></td></tr>
<tr>
<td style="background-color:#f0f0f0; font-weight:bold;">Item</td>
<td style="background-color:#f0f0f0; font-weight:bold; text-align:right;">Qty</td>
<td style="background-color:#f0f0f0; font-weight:bold; text-align:right;">Price</td>
<td style="background-color:#f0f0f0; font-weight:bold; text-align:right;">Total</td>
</tr>
<?php
$items = $exportData['items'];
foreach($items as $item){
    $itmdsc = isset($item['itmdsc']) ? po_xls_clean_text($item['itmdsc']) : '';
    $itmqty = isset($item['itmqty']) ? floatval($item['itmqty']) : 0;
    $untprc = isset($item['untprc']) ? floatval($item['untprc']) : 0;
    $extprc = isset($item['extprc']) ? floatval($item['extprc']) : ($itmqty * $untprc);
?>
<tr>
<td><?php echo $itmdsc; ?></td>
<td class="number" style="text-align:right;"><?php echo po_xls_format_number($itmqty); ?></td>
<td class="number" style="text-align:right;"><?php echo po_xls_format_number($untprc); ?></td>
<td class="number" style="text-align:right;"><?php echo po_xls_format_number($extprc); ?></td>
</tr>
<?php } ?>
<tr><td colspan="4"></td></tr>
<tr style="font-weight:bold;">
<td></td>
<td></td>
<td style="text-align:right;">Total:</td>
<td class="number" style="text-align:right;"><?php echo po_xls_format_number($exportData['trntot']); ?></td>
</tr>
</table>
</body>
</html>
