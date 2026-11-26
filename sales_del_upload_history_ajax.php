<?php
session_start();

header('Content-Type: application/json');

// Check session
if(!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])){
    echo json_encode(array("status" => 0, "msg" => "Session expired"));
    exit;
}

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

$xret = array();
$xret["status"] = 1;
$xret["msg"] = "";
$xret["html"] = "";
$xret["html_mobile"] = "";
$xret["totalrec"] = 0;
$xret["maxpage"] = 0;
$xret["xpageno"] = 0;

// Build WHERE clause for search filters
$xfilter = "";
$params = array();

// Filter by filename
if(isset($_POST['filename_search_h']) && trim($_POST['filename_search_h']) !== ''){
    $xfilter .= " AND file_name LIKE ?";
    $params[] = '%' . trim($_POST['filename_search_h']) . '%';
}

// Filter by date range
if(isset($_POST['from_search_h']) && trim($_POST['from_search_h']) !== '' &&
   isset($_POST['to_search_h']) && trim($_POST['to_search_h']) !== ''){
    $from_date = date("Y-m-d", strtotime(trim($_POST['from_search_h'])));
    $to_date = date("Y-m-d", strtotime(trim($_POST['to_search_h'])));
    $xfilter .= " AND DATE(date_uploaded) >= ? AND DATE(date_uploaded) <= ?";
    $params[] = $from_date;
    $params[] = $to_date;
} else if(isset($_POST['from_search_h']) && trim($_POST['from_search_h']) !== ''){
    $from_date = date("Y-m-d", strtotime(trim($_POST['from_search_h'])));
    $xfilter .= " AND DATE(date_uploaded) >= ?";
    $params[] = $from_date;
} else if(isset($_POST['to_search_h']) && trim($_POST['to_search_h']) !== ''){
    $to_date = date("Y-m-d", strtotime(trim($_POST['to_search_h'])));
    $xfilter .= " AND DATE(date_uploaded) <= ?";
    $params[] = $to_date;
}

// Count total unique filenames
$select_count = "SELECT COUNT(DISTINCT file_name) as rec_count FROM sales_upld_delete_history WHERE 1=1 " . $xfilter;
$stmt_count = $link->prepare($select_count);
$stmt_count->execute($params);
$rs_count = $stmt_count->fetch();

$xtotalrec = (int)$rs_count['rec_count'];
$xret['totalrec'] = $xtotalrec;

// Pagination settings
$xlimit = 15;
$maxpage = ceil($xtotalrec / $xlimit);
$xret["maxpage"] = $maxpage;

// Get page number
$xpageno = isset($_POST['pageno']) && is_numeric($_POST['pageno']) ? (int)$_POST['pageno'] : 1;

// Handle no records
if($xtotalrec == 0){
    $xret["html"] = "<tr><td colspan='3' class='text-center py-4'><div class='display-6'>No Records Found <i class='fas fa-search ms-2'></i></div></td></tr>";
    $xret["html_mobile"] = "<tr><td colspan='2' class='text-center py-4'>No Records Found</td></tr>";
    $xret["maxpage"] = 0;
    $xret["xpageno"] = '';
    echo json_encode($xret);
    exit;
}

// Calculate offset based on event
$event_action = isset($_POST["event_action"]) ? $_POST["event_action"] : "first_p";

if($xpageno == 0 || $xpageno == 1 || empty($xpageno) || $event_action == "search"){
    $xpageno = 1;
    $xoffset = 0;
}

if($event_action == "next_p"){
    if($xpageno < $maxpage){
        $xpageno++;
    }
    $xoffset = ($xpageno * $xlimit) - $xlimit;
} else if($event_action == "previous_p"){
    if($xpageno == 1){
        $xoffset = 0;
    } else {
        $xpageno--;
        $xoffset = ($xpageno * $xlimit) - $xlimit;
    }
} else if($event_action == "first_p"){
    $xpageno = 1;
    $xoffset = 0;
} else if($event_action == "last_p"){
    $xpageno = $maxpage;
    $xoffset = ($xpageno * $xlimit) - $xlimit;
} else if($event_action == "same"){
    if($xpageno > $maxpage){
        $xpageno = $maxpage;
    }
    $xoffset = ($xpageno * $xlimit) - $xlimit;
} else {
    $xoffset = ($xpageno * $xlimit) - $xlimit;
}

$xret["xpageno"] = $xpageno;

// Query grouped by file_name with latest date_uploaded
$select_data = "SELECT file_name, MAX(date_uploaded) as date_uploaded, COUNT(*) as record_count
                FROM sales_upld_delete_history
                WHERE 1=1 " . $xfilter . "
                GROUP BY file_name
                ORDER BY MAX(date_uploaded) DESC
                LIMIT " . (int)$xlimit . " OFFSET " . (int)$xoffset;

$stmt_data = $link->prepare($select_data);
$stmt_data->execute($params);

$row_count = 0;
while($row = $stmt_data->fetch()){
    $file_name = htmlspecialchars($row['file_name'], ENT_QUOTES);
    $date_uploaded = htmlspecialchars($row['date_uploaded'], ENT_QUOTES);
    $record_count = (int)$row['record_count'];

    // Format date for display
    $date_display = '';
    if(!empty($row['date_uploaded'])){
        $date_display = date("m/d/Y h:i:s A", strtotime($row['date_uploaded']));
    }

    // Escape file_name for JavaScript (handle quotes and special chars)
    $file_name_js = addslashes($row['file_name']);

    // Desktop row
    $xret["html"] .= "<tr class='tr_striped'>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:16px;'>" . $file_name . "</td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:16px;'>" . htmlspecialchars($date_display, ENT_QUOTES) . "</td>";
    $xret["html"] .= "<td class='text-center action-col' style='padding:0.5rem;'>";
    $xret["html"] .= "<button type='button' class='btn btn-primary btn-sm fw-bold' style='white-space:nowrap;' onclick=\"viewDetails('" . $file_name_js . "')\">";
    $xret["html"] .= "<i class='fas fa-eye me-1'></i>View Details (" . $record_count . ")";
    $xret["html"] .= "</button>";
    $xret["html"] .= "</td>";
    $xret["html"] .= "</tr>";

    // Mobile row
    $top_border = $row_count > 0 ? 'border-top:2px solid #dee2e6;' : '';

    $xret["html_mobile"] .= "<tr style='" . $top_border . "'>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Filename</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>" . $file_name . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Date & Time</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>" . htmlspecialchars($date_display, ENT_QUOTES) . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Action</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>";
    $xret["html_mobile"] .= "<button type='button' class='btn btn-primary btn-sm fw-bold' onclick=\"viewDetails('" . $file_name_js . "')\">";
    $xret["html_mobile"] .= "<i class='fas fa-eye me-1'></i>View (" . $record_count . ")";
    $xret["html_mobile"] .= "</button>";
    $xret["html_mobile"] .= "</td>";
    $xret["html_mobile"] .= "</tr>";

    $row_count++;
}

echo json_encode($xret);
?>