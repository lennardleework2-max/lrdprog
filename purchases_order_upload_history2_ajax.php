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

// Filter by item description
if(isset($_POST['itmdsc_search_h']) && trim($_POST['itmdsc_search_h']) !== ''){
    $xfilter .= " AND (i.itmdsc LIKE ? OR h.itmcde LIKE ?)";
    $search_term = '%' . trim($_POST['itmdsc_search_h']) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

// Count total records
$select_count = "SELECT COUNT(*) as rec_count
                 FROM po_upld_history h
                 LEFT JOIN itemfile i ON h.itmcde = i.itmcde
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

// Query with LEFT JOIN to itemfile table to get itmdsc
$select_data = "SELECT h.recid, h.itmcde, h.itmqty, h.untprc, h.extprc,
                       COALESCE(i.itmdsc, h.itmcde) as itmdsc
                FROM po_upld_history h
                LEFT JOIN itemfile i ON h.itmcde = i.itmcde
                WHERE 1=1 " . $xfilter . "
                ORDER BY h.recid DESC
                LIMIT " . (int)$xlimit . " OFFSET " . (int)$xoffset;

$stmt_data = $link->prepare($select_data);
$stmt_data->execute($params);

$row_count = 0;
while($row = $stmt_data->fetch()){
    $itmdsc = htmlspecialchars($row['itmdsc'] ?? '', ENT_QUOTES);
    $itmqty = (float)($row['itmqty'] ?? 0);
    $untprc = (float)($row['untprc'] ?? 0);
    $extprc = (float)($row['extprc'] ?? 0);

    // Format numbers for display
    $itmqty_display = number_format($itmqty, 2);
    $untprc_display = number_format($untprc, 2);
    $extprc_display = number_format($extprc, 2);

    // Desktop row
    $xret["html"] .= "<tr class='tr_striped'>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;'>" . $itmdsc . "</td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;' class='text-end'>" . $untprc_display . "</td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;' class='text-end'>" . $itmqty_display . "</td>";
    $xret["html"] .= "<td style='padding:0.5rem;font-size:15px;' class='text-end'>" . $extprc_display . "</td>";
    $xret["html"] .= "</tr>";

    // Mobile row
    $top_border = $row_count > 0 ? 'border-top:2px solid #dee2e6;' : '';

    $xret["html_mobile"] .= "<tr style='" . $top_border . "'>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;width:40%;'>Item Description</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;'>" . $itmdsc . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Unit Price</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;text-align:right;'>" . $untprc_display . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Item Quantity</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;text-align:right;'>" . $itmqty_display . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $xret["html_mobile"] .= "<tr>";
    $xret["html_mobile"] .= "<td style='font-weight:bold;padding:0.5rem;'>Extended Price</td>";
    $xret["html_mobile"] .= "<td style='padding:0.5rem;text-align:right;'>" . $extprc_display . "</td>";
    $xret["html_mobile"] .= "</tr>";

    $row_count++;
}

echo json_encode($xret);
?>
