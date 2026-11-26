<?php
// Sales ROAS Report - AJAX: every configured platform shop (mf_platform_shop) plus its
// auto-calculated Online Sales for the given date range. Online Sales = SUM(tranfile2.extprc)
// for SAL transactions (tranfile1.trncde), joined by tranfile2.platform_shop_id, filtered on
// tranfile1.trndte (not tranfile2.trndte, which can be empty - see CLAUDE.md stock convention).
// Ads Credit / Valid Order Sales / Ads Spend are filled in by the user on the report page itself
// and are not calculated here.

function sales_roas_report_fetch_shops($link, $date_from, $date_to)
{
    $sql = "SELECT mf_platform_shop.platform_shop_id,
                   mf_platform_shop.platform_shop_title,
                   mf_platform.platform_desc,
                   COALESCE(SUM(CASE WHEN tranfile1.trncde = 'SAL' THEN tranfile2.extprc ELSE 0 END), 0) AS online_sales
            FROM mf_platform_shop
            LEFT JOIN mf_platform ON mf_platform.platform_id = mf_platform_shop.platform_id
            LEFT JOIN tranfile2 ON tranfile2.platform_shop_id = mf_platform_shop.platform_shop_id
            LEFT JOIN tranfile1 ON tranfile1.docnum = tranfile2.docnum";

    // date bounds must live in the tranfile1 JOIN condition, not a WHERE clause - a WHERE here
    // would drop shops with zero matching sales instead of showing them with online_sales = 0
    $params = array();
    if ($date_from !== '') {
        $sql .= " AND tranfile1.trndte >= ?";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $sql .= " AND tranfile1.trndte <= ?";
        $params[] = $date_to;
    }

    $sql .= " GROUP BY mf_platform_shop.platform_shop_id, mf_platform_shop.platform_shop_title, mf_platform.platform_desc
              ORDER BY mf_platform.platform_desc ASC, mf_platform_shop.platform_shop_title ASC";

    $stmt = $link->prepare($sql);
    $stmt->execute($params);

    $rows = array();
    while ($rs = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array(
            'platform_shop_id' => $rs['platform_shop_id'],
            'shop_title' => $rs['platform_shop_title'],
            'platform_desc' => $rs['platform_desc'],
            'online_sales' => round((float)$rs['online_sales'], 2),
        );
    }

    return $rows;
}

// lets tests/test_sales_roas_report.php load the function above without a DB, session or output
if (defined('SALES_ROAS_REPORT_LIB_ONLY')) {
    return;
}

session_start();

if (!isset($_SESSION['userdesc']) || !isset($_SESSION['password'])) {
    echo json_encode(array('status' => 0, 'errorMsg' => 'Your session has expired. Please log in again.'));
    exit;
}

require_once("resources/db_init.php");
require_once("resources/connect4.php");
require_once("resources/lx2.pdodb.php");
require_once("resources/stdfunc100.php");

// date picker sends MM/DD/YYYY
$date_from = !empty($_POST['date_from']) ? date("Y-m-d", strtotime($_POST['date_from'])) : '';
$date_to = !empty($_POST['date_to']) ? date("Y-m-d", strtotime($_POST['date_to'])) : '';

$shops = sales_roas_report_fetch_shops($link, $date_from, $date_to);

echo json_encode(array('status' => 1, 'shops' => $shops));
