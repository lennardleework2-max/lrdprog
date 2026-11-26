<?php
/**
 * Standalone CLI test for the Shopee "Income - N" sheet-name filter used in
 * sales_payout_ajax.php (regex: /^Income(\s*-\s*\d+)?$/i), plus the TikTok "Order details" sheet pick.
 * Run: php tests/test_sales_payout_shopee_sheets.php
 */

function payout_is_shopee_income_sheet($sheetName)
{
    return preg_match('/^Income(\s*-\s*\d+)?$/i', trim($sheetName)) === 1;
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

// Accepted - confirmed real sheet names from uploads_sales_test/payout/shopee_Income_1...xlsx
check('accepts "Income - 1"', payout_is_shopee_income_sheet('Income - 1') === true);
check('accepts "Income - 2"', payout_is_shopee_income_sheet('Income - 2') === true);
// Spacing/case variants that should still be treated as the same pattern
check('accepts "income-3" (no spaces, lowercase)', payout_is_shopee_income_sheet('income-3') === true);
check('accepts "INCOME -   12" (extra spaces, uppercase, multi-digit)', payout_is_shopee_income_sheet('INCOME -   12') === true);
check('accepts with surrounding whitespace', payout_is_shopee_income_sheet('  Income - 1  ') === true);

// Rejected - other real sheet names from the same sample workbook, and near-miss variants
check('rejects "Summary"', payout_is_shopee_income_sheet('Summary') === false);
check('rejects "Adjustment"', payout_is_shopee_income_sheet('Adjustment') === false);
check('rejects "Service Fee Details - 1"', payout_is_shopee_income_sheet('Service Fee Details - 1') === false);
check('accepts "Income" (single-sheet export)', payout_is_shopee_income_sheet('Income') === true);
check('rejects "Income -" with no number', payout_is_shopee_income_sheet('Income -') === false);
check('rejects "Income - 1 (copy)" (trailing text)', payout_is_shopee_income_sheet('Income - 1 (copy)') === false);
check('rejects "My Income - 1" (leading text)', payout_is_shopee_income_sheet('My Income - 1') === false);
check('rejects empty string', payout_is_shopee_income_sheet('') === false);
check('rejects "Income Order Overview" (Lazada sheet)', payout_is_shopee_income_sheet('Income Order Overview') === false);
check('rejects "Service Fee Details"', payout_is_shopee_income_sheet('Service Fee Details') === false);

// --- TikTok sheet pick: mirrors sales_payout_ajax.php ("Order details" first, else active tab) ---
function payout_pick_tiktok_sheet($sheetNames, $activeSheetName)
{
    foreach ($sheetNames as $sn) {
        if (strcasecmp(trim($sn), 'Order details') === 0) {
            return $sn;
        }
    }
    return $activeSheetName;
}

$tiktokSheets = ['Order details', 'Reports', 'Withdrawal records', 'Fee explanation'];
check('tiktok: "Order details" chosen even when active tab is "Reports"', payout_pick_tiktok_sheet($tiktokSheets, 'Reports') === 'Order details');
check('tiktok: case-insensitive "ORDER DETAILS"', payout_pick_tiktok_sheet(['Reports', 'ORDER DETAILS'], 'Reports') === 'ORDER DETAILS');
check('tiktok: falls back to active tab when no "Order details" sheet', payout_pick_tiktok_sheet(['Sheet1', 'Sheet2'], 'Sheet2') === 'Sheet2');

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
