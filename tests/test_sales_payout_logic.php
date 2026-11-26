<?php
/**
 * Standalone CLI test for the core matching/pricing logic in sales_payout_ajax.php.
 * No database connection, no $_POST, no real file upload - mirrors the style of
 * tests/test_sales_filepaidupload_batching.php (reimplements the logic against
 * in-memory fake data instead of hitting the real DB).
 * Run: php tests/test_sales_payout_logic.php
 */

// --- Reimplementation of sales_payout_ajax.php's grouping + divide-by-duplicates step ---
function payout_group_and_divide($uploadedRows)
{
    $grouped = [];
    foreach ($uploadedRows as $r) {
        $grouped[$r['ordernum']][] = $r['price'];
    }

    $dividedPrice = [];
    foreach ($grouped as $ordernum => $prices) {
        $dividedPrice[$ordernum] = round(array_sum($prices) / count($prices), 2);
    }

    return [$grouped, $dividedPrice];
}

// --- Reimplementation of sales_payout_ajax.php's batch tranfile1 match + docnum price map step ---
// Mirrors NULLIF(TRIM(untprc_before_payout), '') IS NULL: NULL and blank both mean "not paid out yet".
function payout_before_is_empty($value)
{
    return $value === null || trim((string)$value) === '';
}

// Docnums whose tranfile2 lines already have a non-empty untprc_before_payout are locked (paid out once).
function payout_find_already_paid_docnums($fakeTranfile2)
{
    $paid = [];
    foreach ($fakeTranfile2 as $row) {
        if (!payout_before_is_empty($row['untprc_before_payout'] ?? null)) {
            $paid[$row['docnum']] = true;
        }
    }
    return $paid;
}

// Exact copy of payout_match_orders_to_docnums() in sales_payout_ajax.php
// (exact match first, trimmed fallback; earliest trndte, then lowest recid).
function payout_match_orders_to_docnums($orderNumbers, $dbRows)
{
    usort($dbRows, function ($a, $b) {
        $cmp = strcmp((string)$a['trndte'], (string)$b['trndte']);
        return $cmp !== 0 ? $cmp : ((int)$a['recid'] - (int)$b['recid']);
    });

    $exact = [];
    $trimmed = [];
    foreach ($dbRows as $row) {
        $key = (string)$row['ordernum'];
        if (!isset($exact[$key])) {
            $exact[$key] = $row['docnum'];
        }
        if (!isset($trimmed[trim($key)])) {
            $trimmed[trim($key)] = $row['docnum'];
        }
    }

    $map = [];
    foreach ($orderNumbers as $ordernum) {
        $ordernum = (string)$ordernum;
        if (isset($exact[$ordernum])) {
            $map[$ordernum] = $exact[$ordernum];
        } elseif (isset($trimmed[trim($ordernum)])) {
            $map[$ordernum] = $trimmed[trim($ordernum)];
        }
    }
    return $map;
}

// Fake tranfile1 rows default trndte/recid so older scenarios stay short.
function payout_pick_docnum_per_order($orderNumbers, $fakeTranfile1)
{
    $rows = [];
    foreach ($fakeTranfile1 as $i => $r) {
        $rows[] = $r + ['trndte' => '', 'recid' => $i + 1];
    }
    return payout_match_orders_to_docnums($orderNumbers, $rows);
}

// Mirrors: SELECT docnum, SUM(itmqty) qty FROM tranfile2 ... GROUP BY docnum
function payout_docnum_qty($fakeTranfile2)
{
    $qty = [];
    foreach ($fakeTranfile2 as $r) {
        $qty[$r['docnum']] = ($qty[$r['docnum']] ?? 0) + (float)$r['itmqty'];
    }
    return $qty;
}

// Mirrors the per-order loop: unit price = order payout / total itmqty of the chosen docnum.
function payout_match_and_build_docnum_prices($orderNumbers, $dividedPrice, $fakeTranfile1, $fakeTranfile2, $alreadyPaidDocnums = [])
{
    $orderToDocnum = payout_pick_docnum_per_order($orderNumbers, $fakeTranfile1);
    $docnumQty = payout_docnum_qty($fakeTranfile2);

    $unmatched = [];
    $alreadyUploaded = [];
    $zeroQty = [];
    $docnumToPrice = [];

    foreach ($orderNumbers as $ordernum) {
        if (!isset($orderToDocnum[$ordernum])) {
            $unmatched[] = $ordernum;
            continue;
        }
        $docnum = $orderToDocnum[$ordernum];
        if (isset($alreadyPaidDocnums[$docnum])) {
            $alreadyUploaded[] = $ordernum;
            continue;
        }
        $totalQty = $docnumQty[$docnum] ?? 0;
        if ($totalQty <= 0) {
            $zeroQty[] = $ordernum;
            continue;
        }
        $docnumToPrice[$docnum] = round($dividedPrice[$ordernum] / $totalQty, 2);
    }

    return [$unmatched, $docnumToPrice, $alreadyUploaded, $zeroQty];
}

// --- Reimplementation of sales_payout_ajax.php's tranfile2.extprc / tranfile1.trntot resync ---
// Mirrors the SQL exactly: extprc = ROUND(untprc * itmqty, 2), trntot = ROUND(SUM(extprc), 2).
function payout_recompute_extprc_and_trntot($fakeTranfile2, $docnumToPrice)
{
    // tranfile2: untprc_before_payout = old untprc (only where still NULL), untprc = divided price
    foreach ($fakeTranfile2 as &$row) {
        if (isset($docnumToPrice[$row['docnum']]) && payout_before_is_empty($row['untprc_before_payout'] ?? null)) {
            $row['untprc_before_payout'] = $row['untprc'];
            $row['untprc'] = $docnumToPrice[$row['docnum']];
        }
    }
    unset($row);

    // extprc = ROUND(untprc * itmqty, 2) for every line of the (already filtered) docnums
    foreach ($fakeTranfile2 as &$row) {
        if (isset($docnumToPrice[$row['docnum']])) {
            $row['extprc'] = round($row['untprc'] * $row['itmqty'], 2);
        }
    }
    unset($row);

    // tranfile1.trntot = ROUND(SUM(tranfile2.extprc), 2) for the same docnum
    $trntotByDocnum = [];
    foreach ($fakeTranfile2 as $row) {
        if (!isset($trntotByDocnum[$row['docnum']])) {
            $trntotByDocnum[$row['docnum']] = 0;
        }
        $trntotByDocnum[$row['docnum']] += $row['extprc'];
    }
    foreach ($trntotByDocnum as $docnum => $total) {
        $trntotByDocnum[$docnum] = round($total, 2);
    }

    return [$fakeTranfile2, $trntotByDocnum];
}

// --- Platform whitelist check, mirrors the top of sales_payout_ajax.php ---
function payout_is_allowed_platform($platform_name)
{
    return in_array(strtoupper(trim((string)$platform_name)), ['TIKTOK', 'LAZADA', 'SHOPEE'], true);
}

// --- Invalid-price row filtering, mirrors the pass-2 read loop ---
function payout_filter_valid_rows($rawRows)
{
    $valid = [];
    $invalidCount = 0;
    foreach ($rawRows as $r) {
        $ordernum = trim((string)$r['ordernum']);
        if ($ordernum === '') {
            continue;
        }
        $rawPrice = $r['price'];
        if (is_string($rawPrice)) {
            $rawPrice = trim($rawPrice);
        }
        if ($rawPrice === null || $rawPrice === '' || !is_numeric($rawPrice)) {
            $invalidCount++;
            continue;
        }
        $valid[] = ['ordernum' => $ordernum, 'price' => (float)$rawPrice];
    }
    return [$valid, $invalidCount];
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

// ---------------------------------------------------------------------------
// Scenario 1: single uploaded row per order number -> price unchanged
// ---------------------------------------------------------------------------
[$grouped1, $divided1] = payout_group_and_divide([
    ['ordernum' => 'ORD-1', 'price' => 500.0],
]);
check('single row: divided price equals the raw price', $divided1['ORD-1'] === 500.0);

// ---------------------------------------------------------------------------
// Scenario 2: spec example - 1000 total across 3 duplicate rows -> 333.33 each
// ---------------------------------------------------------------------------
[$grouped2, $divided2] = payout_group_and_divide([
    ['ordernum' => 'ORD-2', 'price' => 1000.0],
    ['ordernum' => 'ORD-2', 'price' => 1000.0],
    ['ordernum' => 'ORD-2', 'price' => 1000.0],
]);
check('3 identical duplicate rows summing to 3000: divided to 1000.00 each', $divided2['ORD-2'] === 1000.0);

// Interpreting the spec literally (uploaded price 1000 appearing 3 times = total 1000, not 3000)
[$grouped2b, $divided2b] = payout_group_and_divide([
    ['ordernum' => 'ORD-2B', 'price' => 333.34],
    ['ordernum' => 'ORD-2B', 'price' => 333.33],
    ['ordernum' => 'ORD-2B', 'price' => 333.33],
]);
check('3 rows summing to 1000.00: divided to 333.33 each (spec example)', $divided2b['ORD-2B'] === 333.33);

// ---------------------------------------------------------------------------
// Scenario 3: real-world shape - duplicate order number with different (incl. negative) amounts
// (mirrors the actual Lazada sample data, e.g. order 1107602424000089: -59.11 and 322.34)
// ---------------------------------------------------------------------------
[$grouped3, $divided3] = payout_group_and_divide([
    ['ordernum' => 'ORD-3', 'price' => -59.11],
    ['ordernum' => 'ORD-3', 'price' => 322.34],
]);
check('2 unequal-amount duplicate rows: divided = round(sum/2, 2)', $divided3['ORD-3'] === round((-59.11 + 322.34) / 2, 2));

// ---------------------------------------------------------------------------
// Scenario 4: multiple tranfile1 rows share the same order number -> only the earliest
// trndte is used; same trndte -> lowest recid
// ---------------------------------------------------------------------------
$fakeTranfile1_multi = [
    ['recid' => 5, 'trndte' => '2026-11-10', 'ordernum' => 'ORD-4', 'docnum' => 'SAL-00002'],
    ['recid' => 9, 'trndte' => '2026-11-01', 'ordernum' => 'ORD-4', 'docnum' => 'SAL-00001'],
    ['recid' => 7, 'trndte' => '2026-11-05', 'ordernum' => 'ORD-4T', 'docnum' => 'SAL-00004'],
    ['recid' => 3, 'trndte' => '2026-11-05', 'ordernum' => 'ORD-4T', 'docnum' => 'SAL-00003'],
];
$fakeTranfile2_multi = [
    ['docnum' => 'SAL-00001', 'itmqty' => 3],
    ['docnum' => 'SAL-00002', 'itmqty' => 1],
    ['docnum' => 'SAL-00003', 'itmqty' => 2],
    ['docnum' => 'SAL-00004', 'itmqty' => 1],
];
[$grouped4, $divided4] = payout_group_and_divide([
    ['ordernum' => 'ORD-4', 'price' => 900.0],
    ['ordernum' => 'ORD-4T', 'price' => 100.0],
]);
[$unmatched4, $docnumToPrice4] = payout_match_and_build_docnum_prices(['ORD-4', 'ORD-4T'], $divided4, $fakeTranfile1_multi, $fakeTranfile2_multi);

check('multi-match: no unmatched orders', empty($unmatched4));
check('multi-match: only the earliest-trndte docnum is updated', array_keys($docnumToPrice4) === ['SAL-00001', 'SAL-00003']);
check('multi-match: earliest docnum gets 900 / qty 3 = 300', $docnumToPrice4['SAL-00001'] === 300.0);
check('multi-match: same trndte -> lowest recid (SAL-00003) gets 100 / qty 2 = 50', $docnumToPrice4['SAL-00003'] === 50.0);

// ---------------------------------------------------------------------------
// Scenario 5: unmatched order numbers do not block matched ones from updating
// ---------------------------------------------------------------------------
$fakeTranfile1_mixed = [
    ['ordernum' => 'ORD-5A', 'docnum' => 'SAL-00010'],
];
[$grouped5, $divided5] = payout_group_and_divide([
    ['ordernum' => 'ORD-5A', 'price' => 100.0],
    ['ordernum' => 'ORD-5B', 'price' => 200.0],
]);
[$unmatched5, $docnumToPrice5] = payout_match_and_build_docnum_prices(['ORD-5A', 'ORD-5B'], $divided5, $fakeTranfile1_mixed, [['docnum' => 'SAL-00010', 'itmqty' => 1]]);

check('mixed match: ORD-5B (no tranfile1 row) is unmatched', $unmatched5 === ['ORD-5B']);
check('mixed match: ORD-5A still updates despite ORD-5B being unmatched', $docnumToPrice5['SAL-00010'] === 100.0);

// ---------------------------------------------------------------------------
// Scenario 6: invalid / non-numeric price values are skipped, not fatal
// ---------------------------------------------------------------------------
[$validRows6, $invalidCount6] = payout_filter_valid_rows([
    ['ordernum' => 'ORD-6A', 'price' => '150.50'],
    ['ordernum' => 'ORD-6B', 'price' => 'N/A'],
    ['ordernum' => 'ORD-6C', 'price' => ''],
    ['ordernum' => '',       'price' => '999'],
    ['ordernum' => 'ORD-6D', 'price' => -25.0],
]);
check('invalid price rows: exactly 2 invalid rows skipped (N/A, empty price)', $invalidCount6 === 2);
check('invalid price rows: valid rows still collected (numeric string + negative number)', count($validRows6) === 2);
check('invalid price rows: blank-ordernum row dropped without counting as invalid', !in_array('', array_column($validRows6, 'ordernum'), true));

// ---------------------------------------------------------------------------
// Scenario 8: tranfile2.extprc and tranfile1.trntot resync after a payout price update
// ---------------------------------------------------------------------------
// SAL-00020 has 2 line items (itmqty 3 and 2) under one docnum -> both get the same
// divided untprc, extprc recalculates per line, trntot = sum of both extprc.
// SAL-00030 has 1 line item, untouched by this payout run (not in docnumToPrice) -> unchanged.
$fakeTranfile2_8 = [
    ['docnum' => 'SAL-00020', 'itmqty' => 3, 'untprc' => 100.0, 'extprc' => 300.0],
    ['docnum' => 'SAL-00020', 'itmqty' => 2, 'untprc' => 100.0, 'extprc' => 200.0],
    ['docnum' => 'SAL-00030', 'itmqty' => 5, 'untprc' => 50.0,  'extprc' => 250.0],
];
[$updatedTranfile2_8, $trntot8] = payout_recompute_extprc_and_trntot($fakeTranfile2_8, ['SAL-00020' => 40.0]);

check('trntot resync: both SAL-00020 lines get untprc = 40.00', $updatedTranfile2_8[0]['untprc'] === 40.0 && $updatedTranfile2_8[1]['untprc'] === 40.0);
check('trntot resync: extprc recalculated as untprc * itmqty per line (120, 80)', $updatedTranfile2_8[0]['extprc'] === 120.0 && $updatedTranfile2_8[1]['extprc'] === 80.0);
check('trntot resync: SAL-00020 trntot = sum of its updated extprc (120 + 80 = 200)', $trntot8['SAL-00020'] === 200.0);
check('trntot resync: SAL-00030 (not touched this run) keeps its original untprc/extprc', $updatedTranfile2_8[2]['untprc'] === 50.0 && $updatedTranfile2_8[2]['extprc'] === 250.0);
check('trntot resync: SAL-00030 trntot unaffected (still just its own 250)', $trntot8['SAL-00030'] === 250.0);

// ---------------------------------------------------------------------------
// Scenario 9: rounding precision - fractional itmqty naturally produces more than 2
// decimals in untprc * itmqty; extprc and trntot must both come out rounded to 2dp,
// not carrying the extra floating-point precision through.
// ---------------------------------------------------------------------------
// untprc = 33.333 (already an odd value on its own), itmqty = 1.5 -> raw product = 49.9995
$fakeTranfile2_9 = [
    ['docnum' => 'SAL-00040', 'itmqty' => 1.5, 'untprc' => 0, 'extprc' => 0],
];
[$updatedTranfile2_9, $trntot9] = payout_recompute_extprc_and_trntot($fakeTranfile2_9, ['SAL-00040' => 33.333]);

check('rounding: untprc * itmqty (33.333 * 1.5 = 49.9995) rounds extprc to 50.00, not 49.9995', $updatedTranfile2_9[0]['extprc'] === 50.0);
check('rounding: trntot rounds to 2dp too (matches the single rounded extprc)', $trntot9['SAL-00040'] === 50.0);
check('rounding: extprc string representation has at most 2 decimal places', preg_match('/^\d+(\.\d{1,2})?$/', (string)$updatedTranfile2_9[0]['extprc']) === 1);

// ---------------------------------------------------------------------------
// Scenario 10: old price is saved before the override, and a docnum can be paid out only once
// ---------------------------------------------------------------------------
$fakeTranfile1_10 = [
    ['ordernum' => 'ORD-10', 'docnum' => 'SAL-00050'],
];
$fakeTranfile2_10 = [
    ['docnum' => 'SAL-00050', 'itmqty' => 2, 'untprc' => 100.0, 'extprc' => 200.0],
    ['docnum' => 'SAL-00050', 'itmqty' => 1, 'untprc' => 100.0, 'extprc' => 100.0],
];

// first upload: payout 120 over total qty 3 -> 40 per unit
[$g10a, $d10a] = payout_group_and_divide([['ordernum' => 'ORD-10', 'price' => 120.0]]);
[$un10a, $map10a, $already10a] = payout_match_and_build_docnum_prices(['ORD-10'], $d10a, $fakeTranfile1_10, $fakeTranfile2_10, payout_find_already_paid_docnums($fakeTranfile2_10));
[$t2_10a, $trntot10a] = payout_recompute_extprc_and_trntot($fakeTranfile2_10, $map10a);

check('first upload: order is not skipped', empty($already10a) && $map10a['SAL-00050'] === 40.0);
check('first upload: untprc_before_payout = old price 100 on every line', $t2_10a[0]['untprc_before_payout'] === 100.0 && $t2_10a[1]['untprc_before_payout'] === 100.0);
check('first upload: untprc = 120 / 3 = 40 on every line', $t2_10a[0]['untprc'] === 40.0 && $t2_10a[1]['untprc'] === 40.0);
check('first upload: extprc/trntot resync (80 + 40 = 120)', $t2_10a[0]['extprc'] === 80.0 && $t2_10a[1]['extprc'] === 40.0 && $trntot10a['SAL-00050'] === 120.0);

// second upload with a different price (130): nothing changes
[$g10b, $d10b] = payout_group_and_divide([['ordernum' => 'ORD-10', 'price' => 130.0]]);
[$un10b, $map10b, $already10b] = payout_match_and_build_docnum_prices(['ORD-10'], $d10b, $fakeTranfile1_10, $t2_10a, payout_find_already_paid_docnums($t2_10a));
[$t2_10b, $trntot10b] = payout_recompute_extprc_and_trntot($t2_10a, $map10b);

check('second upload: order reported as already uploaded, not unmatched', $already10b === ['ORD-10'] && empty($un10b));
check('second upload: nothing queued for update', empty($map10b));
check('second upload: untprc_before_payout still 100', $t2_10b[0]['untprc_before_payout'] === 100.0 && $t2_10b[1]['untprc_before_payout'] === 100.0);
check('second upload: untprc still 40', $t2_10b[0]['untprc'] === 40.0 && $t2_10b[1]['untprc'] === 40.0);
check('second upload: extprc/trntot unchanged', $t2_10b === $t2_10a && $trntot10b['SAL-00050'] === 120.0);

// ---------------------------------------------------------------------------
// Scenario 11: spec example - payout 1000, item A 100 x 2 + item B 50 x 3 -> 1000 / 5 = 200
// ---------------------------------------------------------------------------
$fakeTranfile1_11 = [['ordernum' => '1', 'docnum' => 'SAL-00060']];
$fakeTranfile2_11 = [
    ['docnum' => 'SAL-00060', 'itmqty' => 2, 'untprc' => 100.0, 'extprc' => 200.0],
    ['docnum' => 'SAL-00060', 'itmqty' => 3, 'untprc' => 50.0,  'extprc' => 150.0],
];
[$g11, $d11] = payout_group_and_divide([['ordernum' => '1', 'price' => 1000.0]]);
[$un11, $map11] = payout_match_and_build_docnum_prices(['1'], $d11, $fakeTranfile1_11, $fakeTranfile2_11);
[$t2_11, $trntot11] = payout_recompute_extprc_and_trntot($fakeTranfile2_11, $map11);

check('spec example: unit price = 1000 / 5 = 200', $map11['SAL-00060'] === 200.0);
check('spec example: both lines untprc = 200', $t2_11[0]['untprc'] === 200.0 && $t2_11[1]['untprc'] === 200.0);
check('spec example: untprc_before_payout = 100 and 50', $t2_11[0]['untprc_before_payout'] === 100.0 && $t2_11[1]['untprc_before_payout'] === 50.0);
check('spec example: trntot = 400 + 600 = 1000', $trntot11['SAL-00060'] === 1000.0);

// ---------------------------------------------------------------------------
// Scenario 14: trailing spaces - exact match first, trimmed only as fallback
// ---------------------------------------------------------------------------
$fakeTranfile1_14 = [
    ['recid' => 1, 'trndte' => '2026-11-01', 'ordernum' => 'RYU GIVEAWAY ', 'docnum' => 'SAL-00090'],
    ['recid' => 2, 'trndte' => '2026-11-01', 'ordernum' => 'ORD-14', 'docnum' => 'SAL-00091'],
    ['recid' => 3, 'trndte' => '2026-11-01', 'ordernum' => 'ORD-14 ', 'docnum' => 'SAL-00092'],
];
$map14 = payout_match_orders_to_docnums(['RYU GIVEAWAY ', 'RYU GIVEAWAY', 'ORD-14 ', 'ORD-14', ' ORD-14  ', 'NOPE '], $fakeTranfile1_14);

check('spaces: file "RYU GIVEAWAY " matches DB "RYU GIVEAWAY " exactly', ($map14['RYU GIVEAWAY '] ?? null) === 'SAL-00090');
check('spaces: file "RYU GIVEAWAY" falls back to trimmed match', ($map14['RYU GIVEAWAY'] ?? null) === 'SAL-00090');
check('spaces: "ORD-14 " prefers exact "ORD-14 " over trimmed "ORD-14"', ($map14['ORD-14 '] ?? null) === 'SAL-00092');
check('spaces: "ORD-14" prefers exact "ORD-14"', ($map14['ORD-14'] ?? null) === 'SAL-00091');
check('spaces: " ORD-14  " with no exact match falls back to trimmed (earliest/lowest recid)', ($map14[' ORD-14  '] ?? null) === 'SAL-00091');
check('spaces: no match at all stays unmatched', !isset($map14['NOPE ']));

// ---------------------------------------------------------------------------
// Scenario 13: rounding (1000 / 3 = 333.33, total drifts to 999.99) and qty 0 skip
// ---------------------------------------------------------------------------
$fakeTranfile1_13 = [
    ['ordernum' => 'ORD-13A', 'docnum' => 'SAL-00080'],
    ['ordernum' => 'ORD-13B', 'docnum' => 'SAL-00081'],
];
$fakeTranfile2_13 = [
    ['docnum' => 'SAL-00080', 'itmqty' => 3, 'untprc' => 10.0, 'extprc' => 30.0],
    ['docnum' => 'SAL-00081', 'itmqty' => 0, 'untprc' => 10.0, 'extprc' => 0.0],
];
[$g13, $d13] = payout_group_and_divide([['ordernum' => 'ORD-13A', 'price' => 1000.0], ['ordernum' => 'ORD-13B', 'price' => 500.0]]);
[$un13, $map13, $already13, $zero13] = payout_match_and_build_docnum_prices(['ORD-13A', 'ORD-13B'], $d13, $fakeTranfile1_13, $fakeTranfile2_13);
[, $trntot13] = payout_recompute_extprc_and_trntot($fakeTranfile2_13, $map13);

check('rounding: 1000 / 3 = 333.33', $map13['SAL-00080'] === 333.33);
check('rounding: trntot = 999.99 (accepted cents drift)', $trntot13['SAL-00080'] === 999.99);
check('qty 0: order skipped, nothing queued for its docnum', $zero13 === ['ORD-13B'] && !isset($map13['SAL-00081']));

// ---------------------------------------------------------------------------
// Scenario 12: live column defaulted to '' (not NULL) -> blank must count as NOT paid out
// ---------------------------------------------------------------------------
$fakeTranfile1_12 = [['ordernum' => 'ORD-12', 'docnum' => 'SAL-00070']];
$fakeTranfile2_12 = [
    ['docnum' => 'SAL-00070', 'itmqty' => 2, 'untprc' => 100.0, 'extprc' => 200.0, 'untprc_before_payout' => ''],
    ['docnum' => 'SAL-00070', 'itmqty' => 1, 'untprc' => 100.0, 'extprc' => 100.0, 'untprc_before_payout' => '  '],
];
$paid12 = payout_find_already_paid_docnums($fakeTranfile2_12);
[$g12, $d12] = payout_group_and_divide([['ordernum' => 'ORD-12', 'price' => 90.0]]);
[$un12, $map12, $already12] = payout_match_and_build_docnum_prices(['ORD-12'], $d12, $fakeTranfile1_12, $fakeTranfile2_12, $paid12);
[$t2_12] = payout_recompute_extprc_and_trntot($fakeTranfile2_12, $map12);

check('blank default: docnum not treated as already paid', empty($paid12) && empty($already12));
check('blank default: old price saved to untprc_before_payout', $t2_12[0]['untprc_before_payout'] === 100.0 && $t2_12[1]['untprc_before_payout'] === 100.0);
check('blank default: untprc set to 90 / 3 = 30', $t2_12[0]['untprc'] === 30.0 && $t2_12[1]['untprc'] === 30.0);

// ---------------------------------------------------------------------------
// Scenario 7: platform whitelist
// ---------------------------------------------------------------------------
check('platform whitelist: TIKTOK allowed', payout_is_allowed_platform('TIKTOK') === true);
check('platform whitelist: lowercase tiktok allowed (normalized)', payout_is_allowed_platform('tiktok') === true);
check('platform whitelist: SHOPEE allowed', payout_is_allowed_platform('SHOPEE') === true);
check('platform whitelist: LAZADA allowed', payout_is_allowed_platform('LAZADA') === true);
check('platform whitelist: unknown platform rejected', payout_is_allowed_platform('AMAZON') === false);
check('platform whitelist: empty string rejected', payout_is_allowed_platform('') === false);
check('platform whitelist: arbitrary injected column-name-like value rejected', payout_is_allowed_platform('untprc') === false);

// ---------------------------------------------------------------------------
// Scenario 8: Lazada/TikTok original logic - payout as-is (no qty division),
// written to EVERY matching document that is not already uploaded.
// ---------------------------------------------------------------------------
// Mirrors the non-Shopee branch of sales_payout_ajax.php's per-order loop.
function payout_build_prices_all_docnums($orderNumbers, $dividedPrice, $fakeTranfile1, $alreadyPaidDocnums = [])
{
    $orderToDocnums = [];
    foreach ($fakeTranfile1 as $r) {
        if (in_array($r['ordernum'], $orderNumbers, true)) {
            $orderToDocnums[$r['ordernum']][] = $r['docnum'];
        }
    }

    $unmatched = [];
    $alreadyUploaded = [];
    $docnumToPrice = [];
    foreach ($orderNumbers as $ordernum) {
        if (!isset($orderToDocnums[$ordernum])) {
            $unmatched[] = $ordernum;
            continue;
        }
        $docnums = array_filter(array_unique($orderToDocnums[$ordernum]), function ($d) use ($alreadyPaidDocnums) {
            return !isset($alreadyPaidDocnums[$d]);
        });
        if (empty($docnums)) {
            $alreadyUploaded[] = $ordernum;
            continue;
        }
        foreach ($docnums as $docnum) {
            $docnumToPrice[$docnum] = $dividedPrice[$ordernum];
        }
    }
    return [$unmatched, $docnumToPrice, $alreadyUploaded];
}

// 8a: two file rows (100, 50) -> 75 on every line, no qty division
[, $d8] = payout_group_and_divide([
    ['ordernum' => '1', 'price' => 100.0],
    ['ordernum' => '1', 'price' => 50.0],
]);
$t1_8 = [['ordernum' => '1', 'docnum' => 'SI-1']];
$t2_8 = [
    ['docnum' => 'SI-1', 'itmqty' => 2, 'untprc' => 40.0, 'untprc_before_payout' => null],
    ['docnum' => 'SI-1', 'itmqty' => 1, 'untprc' => 60.0, 'untprc_before_payout' => null],
];
[$un8, $map8] = payout_build_prices_all_docnums(['1'], $d8, $t1_8);
[$t2_8r, $tot8] = payout_recompute_extprc_and_trntot($t2_8, $map8);
check('lazada/tiktok: (100+50)/2 = 75', $d8['1'] === 75.0);
check('lazada/tiktok: every line untprc = 75 (no qty division)', $t2_8r[0]['untprc'] === 75.0 && $t2_8r[1]['untprc'] === 75.0);
check('lazada/tiktok: trntot = 75 x 3 qty = 225', (float)$tot8['SI-1'] === 225.0);

// 8b: ordernum in two documents -> both get 75
$t1_8b = [['ordernum' => '1', 'docnum' => 'SI-3'], ['ordernum' => '1', 'docnum' => 'SI-7']];
[, $map8b] = payout_build_prices_all_docnums(['1'], $d8, $t1_8b);
check('lazada/tiktok: all matching documents updated', $map8b === ['SI-3' => 75.0, 'SI-7' => 75.0]);

// 8c: one of the two already uploaded -> only the other one updated
[, $map8c, $already8c] = payout_build_prices_all_docnums(['1'], $d8, $t1_8b, ['SI-3' => true]);
check('lazada/tiktok: already-uploaded document skipped, other updated', $map8c === ['SI-7' => 75.0] && empty($already8c));

// 8d: both already uploaded -> order counted as already uploaded
[, $map8d, $already8d] = payout_build_prices_all_docnums(['1'], $d8, $t1_8b, ['SI-3' => true, 'SI-7' => true]);
check('lazada/tiktok: all documents uploaded -> already uploaded', empty($map8d) && $already8d === ['1']);

// 8e: ordernum with spaces in the file is trimmed (non-Shopee read step) and matches
$fileOrdernum = trim('  1  ');
[$un8e, $map8e] = payout_build_prices_all_docnums([$fileOrdernum], ['1' => 75.0], $t1_8);
check('lazada/tiktok: spaced ordernum trimmed and matched', empty($un8e) && $map8e === ['SI-1' => 75.0]);

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
