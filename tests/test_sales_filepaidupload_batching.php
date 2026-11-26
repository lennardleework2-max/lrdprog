<?php
/**
 * Standalone CLI test for the batched lookup/write logic added to sales_filepaidupload_ajax.php.
 * No database connection, no $_POST, no real file upload.
 * Run: php tests/test_sales_filepaidupload_batching.php
 *
 * Verifies that the new "collect order numbers -> one batch lookup -> build response
 * from an in-memory map -> one batch write" approach produces the same match/no-match
 * decisions and the same set of records to update as the old "one SELECT + one UPDATE
 * per row" approach it replaced.
 */

// --- Reference implementation: the OLD per-row behavior, reimplemented against an
//     in-memory fake table instead of a live PDO connection. ---
function old_per_row_process($sheetOrderNumbers, $fakeTranfile1, $xfilterPlatform)
{
    $noMatchFile2 = [];
    $recids = [];
    $ordernumsSync = [];

    foreach ($sheetOrderNumbers as $orderNumber) {
        if (empty($orderNumber)) {
            continue;
        }

        // CHECK IF IT IS MATCHED (mirrors "SELECT * FROM tranfile1 WHERE true {$xfilter} AND ordernum = ?", first row wins)
        $rs_checker = null;
        foreach ($fakeTranfile1 as $row) {
            if ($xfilterPlatform !== null && $row['platform_upload'] !== $xfilterPlatform) {
                continue;
            }
            if ($row['ordernum'] === $orderNumber) {
                $rs_checker = $row;
                break;
            }
        }

        if (empty($rs_checker)) {
            $noMatchFile2[$orderNumber] = ['success' => false, 'ordernum' => $orderNumber];
            continue;
        }

        $recids[] = $rs_checker['recid'];
        if (!empty($rs_checker['file_batchno']) && !empty($rs_checker['platform_upload'])) {
            $ordernumsSync[] = $orderNumber;
        }
        $noMatchFile2[$orderNumber] = ['success' => true, 'ordernum' => $orderNumber];
    }

    return [$noMatchFile2, $recids, $ordernumsSync];
}

// --- Copy of the NEW batched logic added to sales_filepaidupload_ajax.php, adapted to
//     read from an in-memory fake table instead of running a real SQL query. ---
function fake_fetch_tranfile1_by_ordernums($fakeTranfile1, $orderNumbers, $xfilterPlatform)
{
    $lookup = [];
    $uniqueOrderNumbers = array_values(array_unique($orderNumbers));

    foreach ($fakeTranfile1 as $row) {
        if ($xfilterPlatform !== null && $row['platform_upload'] !== $xfilterPlatform) {
            continue;
        }
        if (!in_array($row['ordernum'], $uniqueOrderNumbers, true)) {
            continue;
        }
        // keep the first matching row per ordernum, same as the old per-row ->fetch()
        if (!array_key_exists($row['ordernum'], $lookup)) {
            $lookup[$row['ordernum']] = $row;
        }
    }

    return $lookup;
}

function batched_process($sheetOrderNumbers, $fakeTranfile1, $xfilterPlatform)
{
    $orderNumbersInFile = [];
    foreach ($sheetOrderNumbers as $orderNumber) {
        if (empty($orderNumber)) {
            continue;
        }
        $orderNumbersInFile[] = $orderNumber;
    }

    $lookup = fake_fetch_tranfile1_by_ordernums($fakeTranfile1, $orderNumbersInFile, $xfilterPlatform);

    $noMatchFile2 = [];
    $recids_to_pay = [];
    $ordernums_to_sync = [];

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
        if (!empty($rs_checker['file_batchno']) && !empty($rs_checker['platform_upload'])) {
            $ordernums_to_sync[$orderNumber] = $orderNumber;
        }
        $noMatchFile2[$orderNumber] = ['success' => true, 'ordernum' => $orderNumber];
    }

    return [$noMatchFile2, array_values($recids_to_pay), array_values($ordernums_to_sync)];
}

// --- Test data ---
// ORD-A:   matches, has file_batchno + platform_upload -> should sync to upld_salesfile
// ORD-B:   not in the table -> no match
// ORD-C:   matches, but file_batchno is empty -> should NOT sync
// ORD-DUP: exists twice under different platforms, to test "first match wins" + the
//          manual-checkbox (no platform filter) vs platform-filtered scenarios
$fakeTranfile1 = [
    ['recid' => 1, 'ordernum' => 'ORD-A',   'platform_upload' => 'TIKTOK', 'file_batchno' => 'BATCH1'],
    ['recid' => 2, 'ordernum' => 'ORD-C',   'platform_upload' => 'TIKTOK', 'file_batchno' => ''],
    ['recid' => 3, 'ordernum' => 'ORD-DUP', 'platform_upload' => 'TIKTOK', 'file_batchno' => 'BATCH1'],
    ['recid' => 4, 'ordernum' => 'ORD-DUP', 'platform_upload' => 'LAZADA', 'file_batchno' => 'BATCH2'],
];

// ORD-A appears twice in the sheet (duplicate row) to make sure batching still
// de-duplicates the write set the same way repeated per-row updates were idempotent.
$sheetOrderNumbers = ['ORD-A', 'ORD-B', 'ORD-C', 'ORD-A', 'ORD-DUP', ''];

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

function assertSameNoMatch($label, $old, $new)
{
    check($label, $old == $new);
}

function assertSameSet($label, $old, $new)
{
    $oldSet = $old;
    sort($oldSet);
    $oldSet = array_values(array_unique($oldSet));
    $newSet = $new;
    sort($newSet);
    check($label, $oldSet == $newSet);
}

// Scenario 1: manual checkbox checked -> no platform filter (xfilterPlatform = null)
[$oldNoMatch, $oldRecids, $oldSync] = old_per_row_process($sheetOrderNumbers, $fakeTranfile1, null);
[$newNoMatch, $newRecids, $newSync] = batched_process($sheetOrderNumbers, $fakeTranfile1, null);

assertSameNoMatch('no-filter: noMatchFile2 response matches', $oldNoMatch, $newNoMatch);
assertSameSet('no-filter: recids to mark paid match', $oldRecids, $newRecids);
assertSameSet('no-filter: ordernums to sync to upld_salesfile match', $oldSync, $newSync);
check('no-filter: ORD-A marked success', $newNoMatch['ORD-A']['success'] === true);
check('no-filter: ORD-B marked not found', $newNoMatch['ORD-B']['success'] === false);
check('no-filter: ORD-C marked success but not synced (empty file_batchno)', $newNoMatch['ORD-C']['success'] === true && !in_array('ORD-C', $newSync, true));
check('no-filter: ORD-DUP resolves to the first row (recid 3, TIKTOK)', in_array(3, $newRecids, true) && !in_array(4, $newRecids, true));

// Scenario 2: platform filter active (xfilterPlatform = 'LAZADA') -> ORD-DUP should resolve to recid 4 instead
[$oldNoMatch2, $oldRecids2, $oldSync2] = old_per_row_process($sheetOrderNumbers, $fakeTranfile1, 'LAZADA');
[$newNoMatch2, $newRecids2, $newSync2] = batched_process($sheetOrderNumbers, $fakeTranfile1, 'LAZADA');

assertSameNoMatch('LAZADA-filter: noMatchFile2 response matches', $oldNoMatch2, $newNoMatch2);
assertSameSet('LAZADA-filter: recids to mark paid match', $oldRecids2, $newRecids2);
assertSameSet('LAZADA-filter: ordernums to sync to upld_salesfile match', $oldSync2, $newSync2);
check('LAZADA-filter: ORD-DUP resolves to recid 4 under the platform filter', in_array(4, $newRecids2, true) && !in_array(3, $newRecids2, true));
check('LAZADA-filter: ORD-A no longer matches (it is a TIKTOK row)', $newNoMatch2['ORD-A']['success'] === false);

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
