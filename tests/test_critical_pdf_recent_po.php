<?php
/**
 * Standalone CLI test for the "Recent PO Number" column on critical_pdf_rep.php.
 * Runs the same query against an in-memory SQLite DB (no real database) and
 * checks that the latest purchase order (purchasesorderfile1.trndte DESC,
 * purchasesorderfile2.recid DESC) for the item wins.
 * Run: php tests/test_critical_pdf_recent_po.php
 */

// Same SQL as $recent_po_stmt in critical_pdf_rep.php
$recent_po_sql = 'SELECT purchasesorderfile1.ordernum FROM purchasesorderfile2 INNER JOIN purchasesorderfile1 ON purchasesorderfile1.docnum=purchasesorderfile2.docnum WHERE purchasesorderfile2.itmcde=? ORDER BY purchasesorderfile1.trndte DESC, purchasesorderfile2.recid DESC LIMIT 1';

$link = new PDO('sqlite::memory:');
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$link->exec('CREATE TABLE purchasesorderfile1 (docnum TEXT, trndte TEXT, ordernum TEXT)');
$link->exec('CREATE TABLE purchasesorderfile2 (recid INTEGER PRIMARY KEY, docnum TEXT, itmcde TEXT)');

$headers = [
    ['PO-1', '2026-09-01', 'ORD-OLD'],
    ['PO-2', '2026-09-10', 'ORD-SAME-DATE-A'],
    ['PO-3', '2026-09-10', 'ORD-SAME-DATE-B'],
    ['PO-4', '2026-09-20', 'ORD-NEWEST-OTHER-ITEM'],
    ['PO-5', '2026-08-01', 'ORD-ITEM2'],
];
$lines = [
    [1, 'PO-1', 'ITEM1'],
    [5, 'PO-2', 'ITEM1'],
    [3, 'PO-3', 'ITEM1'], // same date as PO-2 but lower recid -> loses
    [9, 'PO-4', 'ITEM9'], // newest PO overall, but a different item
    [7, 'PO-5', 'ITEM2'],
];
$h = $link->prepare('INSERT INTO purchasesorderfile1 VALUES (?,?,?)');
foreach ($headers as $row) $h->execute($row);
$l = $link->prepare('INSERT INTO purchasesorderfile2 VALUES (?,?,?)');
foreach ($lines as $row) $l->execute($row);

$stmt = $link->prepare($recent_po_sql);
function recent_po($stmt, $itmcde) { $stmt->execute([$itmcde]); return (string)$stmt->fetchColumn(); }

$failures = 0;
function check($label, $condition)
{
    global $failures;
    echo ($condition ? 'PASS: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) $failures++;
}

check('Latest trndte wins; ties broken by highest purchasesorderfile2.recid', recent_po($stmt, 'ITEM1') === 'ORD-SAME-DATE-A');
check('Newer POs for other items are ignored', recent_po($stmt, 'ITEM1') !== 'ORD-NEWEST-OTHER-ITEM');
check('Each item gets its own most recent PO', recent_po($stmt, 'ITEM2') === 'ORD-ITEM2');
check('Item with no purchase orders returns an empty string', recent_po($stmt, 'ITEM3') === '');

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
