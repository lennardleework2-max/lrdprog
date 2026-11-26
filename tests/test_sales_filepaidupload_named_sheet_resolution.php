<?php
/**
 * Standalone CLI test for the TikTok/Lazada named-tab resolution added to
 * sales_filepaidupload_ajax.php (matches the exact configured tab name - "Order details" for
 * TikTok, "Transaction Overview" for Lazada - case-insensitively and trimmed, instead of
 * trusting the workbook's saved "active tab" bookmark).
 * Run: php tests/test_sales_filepaidupload_named_sheet_resolution.php
 */

// --- Copy of the matching rule used in paid_upload_resolve_sheet_by_name(), adapted to search
//     an in-memory list of sheet names instead of reading xl/workbook.xml from a real file. ---
function resolve_sheet_by_name_from_list($sheetNames, $targetName)
{
    foreach ($sheetNames as $sheetName) {
        if (strcasecmp(trim($sheetName), $targetName) === 0) {
            return $sheetName;
        }
    }
    return null;
}

$failures = 0;

function check($label, $condition)
{
    global $failures;
    if ($condition) {
        echo "PASS: $label\n";
    } else {
        echo "FAIL: $label\n";
        $failures++;
    }
}

// --- Real tab layouts from the sample workbooks in uploads_sales_test/paid/ ---
$tiktokTabs = ['Order details', 'Reports', 'Withdrawal records', 'Fees explanation'];
$lazadaTabs = ['Transaction Overview'];
$shopeeTabs = ['Summary', 'Income', 'Service Fee Details', 'Adjustment'];

check(
    'TikTok resolves to "Order details" among several tabs',
    resolve_sheet_by_name_from_list($tiktokTabs, 'Order details') === 'Order details'
);
check(
    'Lazada resolves to "Transaction Overview" (only tab)',
    resolve_sheet_by_name_from_list($lazadaTabs, 'Transaction Overview') === 'Transaction Overview'
);

// --- Case-insensitivity and whitespace tolerance (strcasecmp is case-insensitive on both
//     sides, so an all-caps search target still matches the real-cased tab name) ---
check(
    'matches regardless of target case ("ORDER DETAILS")',
    resolve_sheet_by_name_from_list($tiktokTabs, 'ORDER DETAILS') === 'Order details'
);
check(
    'matches when the sheet name itself has different case ("order details" in file)',
    resolve_sheet_by_name_from_list(['order details', 'Reports'], 'Order details') === 'order details'
);
check(
    'matches when the sheet name has extra whitespace (" Order details ")',
    resolve_sheet_by_name_from_list([' Order details ', 'Reports'], 'Order details') === ' Order details '
);

// --- Not found cases ---
check(
    'TikTok target not present in a Shopee-style tab list returns null',
    resolve_sheet_by_name_from_list($shopeeTabs, 'Order details') === null
);
check(
    'Lazada target not present in a TikTok-style tab list returns null',
    resolve_sheet_by_name_from_list($tiktokTabs, 'Transaction Overview') === null
);
check(
    'empty tab list returns null',
    resolve_sheet_by_name_from_list([], 'Order details') === null
);

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
