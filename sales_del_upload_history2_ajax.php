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

// Get the filename filter (required)
$file_name_filter = '';
if(isset($_POST['file_name_filter']) && trim($_POST['file_name_filter']) !== ''){
    $file_name_filter = trim($_POST['file_name_filter']);
} else {
    $xret["html"] = "<tr><td colspan='4' class='text-center py-4'><div class='display-6'>No Filename Specified</div></td></tr>";
    $xret["html_mobile"] = "<tr><td colspan='2' class='text-center py-4'>No Filename Specified</td></tr>";
    echo json_encode($xret);
    exit;
}

// Build WHERE clause for search filters
$xfilter = " AND h.file_name = ?";
$params = array($file_name_filter);

// Filter by ordernum
if(isset($_POST['ordernum_search_h']) && trim($_POST['ordernum_search_h']) !== ''){
    $xfilter .= " AND h.ordernum LIKE ?";
    $params[] = '%' . trim($_POST['ordernum_search_h']) . '%';
}

// Filter by matched_salnum
if(isset($_POST['matched_salnum_search_h']) && trim($_POST['matched_salnum_search_h']) !== ''){
    $xfilter .= " AND h.matched_salnum LIKE ?";
    $params[] = '%' . trim($_POST['matched_salnum_search_h']) . '%';
}

// Filter by status
if(isset($_POST['status_search_h']) && trim($_POST['status_search_h']) !== ''){
    $xfilter .= " AND h.status = ?";
    $params[] = trim($_POST['status_search_h']);
}

// Filter by userdesc (join with users table)
if(isset($_POST['userdesc_search_h']) && trim($_POST['userdesc_search_h']) !== ''){
    $xfilter .= " AND u.userdesc LIKE ?";
    $params[] = '%' . trim($_POST['userdesc_search_h']) . '%';
}

// Count total records
$select_count = "SELECT COUNT(*) as rec_count
                 FROM sales_upld_delete_history h
                 LEFT JOIN users u ON h.usercode = u.usercode
                 WHERE 1=1 " . $xfilter;
$stmt_count = $link->prepare($select_count);
$stmt_count->execute($params);
$rs_count = $stmt_count->fetch();

$xtotalrec = (int)$rs_count['rec_count'];
$xret['totalrec'] = $xtotalrec;

// Pagination settings
$xlimit = 20;
$maxpage = ceil($xtotalrec / $xlimit);
$xret["maxpage"] = $maxpage;

// Get page number
$xpageno = isset($_POST['pageno']) && is_numeric($_POST['pageno']) ? (int)$_POST['pageno'] : 1;

// Handle no records
if($xtotalrec == 0){
    $xret["html"] = "<tr><td colspan='4' class='text-center py-4'><div class='display-6'>No Records Found <i class='fas fa-search ms-2'></i></div></td></tr>";
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

// Query with LEFT JOIN to users table to get userdesc
$select_data = "SELECT h.recid, h.ordernum, h.matched_salnum, h.status, h.usercode, h.date_uploaded,
                       COALESCE(u.userdesc, '') as userdesc
                FROM sales_upld_delete_history h
                LEFT JOIN users u ON h.usercode = u.usercode
                WHERE 1=1 " . $xfilter . "
                ORDER BY h.recid DESC
                LIMIT " . (int)$xlimit . " OFFSET " . (int)$xoffset;

$stmt_data = $link->prepare($select_data);
$stmt_data->execute($params);

$row_count = 0;
while($row = $stmt_data->fetch()){
    $ordernum = htmlspecialchars($row['ordernum'] ?? '', ENT_QUOTES);
    $matched_salnum = htmlspecialchars($row['matched_salnum'] ?? 'N/A', ENT_QUOTES);
    $status = htmlspecialchars($row['status'] ?? '', ENT_QUOTES);
    $userdesc = htmlspecialchars($row['userdesc'] ?? '', ENT_QUOTES);

    // Handle empty matched_salnum
    if(empty(trim($row['matched_salnum']))){
        $matched_salnum = 'N/A';
    }

    // Status badge styling
    $status_badge_class = 'bg-secondary';
    $status_display = $status;
    if($row['status'] === 'SUCCESS'){
        $status_badge_class = 'bg-success';
        $status_display = '<i class="fas fa-check-circle me-1"></i>Success';
    } else if($row['status'] === 'NO MATCHED ORDERNUM'){
        $status_badge_class = 'bg-danger';
        $status_display = '<i class="fas fa-times-circle me-1"></i>No Match';
    }

    // Desktop row
    $xret["html"] .= "<tr class='tr_striped'>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;'>" . $ordernum . "</td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;'>" . $matched_salnum . "</td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;'><span class='badge " . $status_badge_class . "'>" . $status_display . "</span></td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;'>" . $userdesc . "</td>";
    $xret["html"] .= "</tr>";

    // Mobile row
    $top_border = $row_count > 0 ? 'border-top:2px solid #dee2e6;' : '';

    $xret["html_mobile"] .= "<tr style='" . $top_border . "'>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;width:40%;'>Order Number</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>" . $ordernum . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Matched SAL#</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>" . $matched_salnum . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Status</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'><span class='badge " . $status_badge_class . "'>" . $status_display . "</span></td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>User</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>" . $userdesc . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $row_count++;
}

echo json_encode($xret);
?>