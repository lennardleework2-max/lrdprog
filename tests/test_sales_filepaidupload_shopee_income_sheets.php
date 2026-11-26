<?php
/**
 * Standalone CLI test for the Shopee multi-sheet "Income" handling added to
 * sales_filepaidupload_ajax.php (matches any sheet name containing "income",
 * case-insensitive - i.e. SQL `%Income%` - not just the workbook's active tab).
 * Run: php tests/test_sales_filepaidupload_shopee_income_sheets.php
 */

// --- Copy of the sheet-name matching rule used in paid_upload_resolve_income_sheet_names(). ---
function paid_upload_is_income_sheet($sheetName)
{
    return stripos($sheetName, 'income') !== false;
}

// --- Copy of the batched lookup logic, reused unchanged from sales_filepaidupload_ajax.php's
//     fetch_tranfile1_by_ordernums(), against an in-memory fake table. ---
function fake_fetch_tranfile1_by_ordernums($fakeTranfile1, $orderNumbers)
{
    $lookup = [];
    $uniqueOrderNumbers = array_values(array_unique($orderNumbers));

    foreach ($fakeTranfile1 as $row) {
        if (!in_array($row['ordernum'], $uniqueOrderNumbers, true)) {
            continue;
        }
        if (!array_key_exists($row['ordernum'], $lookup)) {
            $lookup[$row['ordernum']] = $row;
        }
    }

    return $lookup;
}

// --- Copy of the shopee multi-sheet processing loop, adapted to read fake in-memory
//     "sheets" (arrays of order numbers) instead of PhpSpreadsheet objects. ---
function process_shopee_income_sheets($sheets, $fakeTranfile1)
{
    $orderNumbersInFile = [];
    foreach ($sheets as $sheetOrderNumbers) {
        foreach ($sheetOrderNumbers as $orderNumber) {
            if (empty($orderNumber)) {
                continue;
            }
            $orderNumbersInFile[] = $orderNumber;
        }
    }

    $lookup = fake_fetch_tranfile1_by_ordernums($fakeTranfile1, $orderNumbersInFile);

    $noMatchFile2 = [];
    $recids_to_pay = [];

    foreach ($sheets as $sheetOrderNumbers) {
        foreach ($sheetOrderNumbers as $orderNumber) {
            if (empty($orderNumber)) {
                continue;
            }

            $rs_checker = isset($lookup[$orderNumber]) ? $lookup[$orderNumber] : null;

            if (empty($rs_checker)) {
                $noMatchFile2[$orderNumber] = ['success' => false, 'ordernum' => $orderNumber];
                continue;
            }

            $recids_to_pay[$rs_checker['recid']] = $rs_checker['recid'];
            $noMatchFile2[$orderNumber] = ['success' => true, 'ordernum' => $orderNumber];
        }
    }

    return [$noMatchFile2, array_values($recids_to_pay)];
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

// --- Part 1: sheet-name matching, against real tab names from the sample workbook
//     uploads_sales_test/paid/shopee_paid_all_Income.released.ph.20250616_20250622.xlsx
//     (Summary, Income, Service Fee Details, Adjustment) plus a few variants. ---
check('accepts "Income"', paid_upload_is_income_sheet('Income') === true);
check('accepts "INCOME" (uppercase)', paid_upload_is_income_sheet('INCOME') === true);
check('accepts "Income - 1" (payout-style naming)', paid_upload_is_income_sheet('Income - 1') === true);
check('accepts "Weekly Income Summary" (substring anywhere)', paid_upload_is_income_sheet('Weekly Income Summary') === true);
check('rejects "Summary"', paid_upload_is_income_sheet('Summary') === false);
check('rejects "Service Fee Details"', paid_upload_is_income_sheet('Service Fee Details') === false);
check('rejects "Adjustment"', paid_upload_is_income_sheet('Adjustment') === false);
check('rejects empty string', paid_upload_is_income_sheet('') === false);

// --- Part 2: merging order numbers collected across multiple "Income"-named sheets
//     into one batch lookup/response, the way a workbook whose active tab is NOT the
//     Income tab (the original bug) now still gets processed correctly. ---
$fakeTranfile1 = [
    ['recid' => 1, 'ordernum' => 'ORD-1'],
    ['recid' => 2, 'ordernum' => 'ORD-2'],
    ['recid' => 3, 'ordernum' => 'ORD-3'],
];

// Simulates two sheets both named with "income" in them (e.g. "Income" and "Income - 2"),
// with ORD-2 appearing in both (should dedupe) and ORD-4 not existing in tranfile1.
$sheets = [
    ['ORD-1', 'ORD-2', ''],
    ['ORD-2', 'ORD-3', 'ORD-4'],
];

[$noMatch, $recids] = process_shopee_income_sheets($sheets, $fakeTranfile1);

check('order numbers from every income sheet are matched', count($noMatch) === 4);
check('ORD-1 (sheet 1 only) matched', $noMatch['ORD-1']['success'] === true);
check('ORD-2 (present in both sheets) matched once', $noMatch['ORD-2']['success'] === true);
check('ORD-3 (sheet 2 only) matched', $noMatch['ORD-3']['success'] === true);
check('ORD-4 (not in tranfile1) reported as not found', $noMatch['ORD-4']['success'] === false);

sort($recids);
check('recids to mark paid are deduped across sheets (1, 2, 3 - no duplicate for ORD-2)', $recids === [1, 2, 3]);

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
