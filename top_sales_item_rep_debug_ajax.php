<?php
/**
 * TEMPORARY DEBUG FILE - Remove after fixing rolling sales issue
 * This file returns report data with SQL statements for debugging
 */
session_start();
require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");

header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

// Rolling date calculations - same as top_sales_item_pdf.php
$today_date = date("Y-m-d");
$past_30_start = date("Y-m-d", strtotime("-30 days"));
$past_60_start = date("Y-m-d", strtotime("-2 months"));
$past_90_start = date("Y-m-d", strtotime("-3 months"));

// Date filters from POST
$date_from_sql = '';
$date_to_sql = '';
$xfilter = '';

if(isset($_POST['date_from']) && !empty($_POST['date_from'])){
    $date_from_sql = date("Y-m-d", strtotime($_POST['date_from']));
    $xfilter .= " AND main_tranfile1.trndte>='".$date_from_sql."'";
}

if(isset($_POST['date_to']) && !empty($_POST['date_to'])){
    $date_to_sql = date("Y-m-d", strtotime($_POST['date_to']));
    $xfilter .= " AND main_tranfile1.trndte<='".$date_to_sql."'";
}

// Get items with sales data
$select_items = "SELECT DISTINCT main_tranfile2.itmcde, itemfile.itmdsc
    FROM tranfile2 main_tranfile2
    LEFT JOIN tranfile1 main_tranfile1 ON main_tranfile2.docnum = main_tranfile1.docnum
    LEFT JOIN itemfile ON main_tranfile2.itmcde = itemfile.itmcde
    WHERE main_tranfile1.trncde = 'SAL' ".$xfilter."
    ORDER BY itemfile.itmdsc
    LIMIT 50";

$stmt = $link->prepare($select_items);
$stmt->execute();

$results = array();

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $itmcde = $row['itmcde'];
    $itmdsc = $row['itmdsc'];

    // Build the debug SQL statements for this item
    $sql_30d = "SELECT SUM(t2.stkqty) * -1 AS total
FROM tranfile1 t1
LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
WHERE t2.itmcde = '".$itmcde."'
  AND t1.trncde = 'SAL'
  AND t1.trndte >= '".$past_30_start."'
  AND t1.trndte <= '".$today_date."'";

    $sql_60d = "SELECT SUM(t2.stkqty) * -1 AS total
FROM tranfile1 t1
LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
WHERE t2.itmcde = '".$itmcde."'
  AND t1.trncde = 'SAL'
  AND t1.trndte >= '".$past_60_start."'
  AND t1.trndte <= '".$today_date."'";

    $sql_90d = "SELECT SUM(t2.stkqty) * -1 AS total
FROM tranfile1 t1
LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
WHERE t2.itmcde = '".$itmcde."'
  AND t1.trncde = 'SAL'
  AND t1.trndte >= '".$past_90_start."'
  AND t1.trndte <= '".$today_date."'";

    // Execute the queries to get actual values
    $stmt_30 = $link->prepare($sql_30d);
    $stmt_30->execute();
    $rs_30 = $stmt_30->fetch(PDO::FETCH_ASSOC);
    $sales_30d = $rs_30 ? floatval($rs_30['total']) : 0;

    $stmt_60 = $link->prepare($sql_60d);
    $stmt_60->execute();
    $rs_60 = $stmt_60->fetch(PDO::FETCH_ASSOC);
    $sales_60d = $rs_60 ? floatval($rs_60['total']) : 0;

    $stmt_90 = $link->prepare($sql_90d);
    $stmt_90->execute();
    $rs_90 = $stmt_90->fetch(PDO::FETCH_ASSOC);
    $sales_90d = $rs_90 ? floatval($rs_90['total']) : 0;

    $results[] = array(
        'itmcde' => $itmcde,
        'itmdsc' => $itmdsc,
        'sales_30d' => $sales_30d,
        'sales_60d' => $sales_60d,
        'sales_90d' => $sales_90d,
        'sql_30d' => $sql_30d,
        'sql_60d' => $sql_60d,
        'sql_90d' => $sql_90d,
        'date_info' => array(
            'today' => $today_date,
            'past_30_start' => $past_30_start,
            'past_60_start' => $past_60_start,
            'past_90_start' => $past_90_start
        )
    );
}

echo json_encode(array(
    'success' => true,
    'today_date' => $today_date,
    'past_30_start' => $past_30_start,
    'past_60_start' => $past_60_start,
    'past_90_start' => $past_90_start,
    'items' => $results
));
?>
