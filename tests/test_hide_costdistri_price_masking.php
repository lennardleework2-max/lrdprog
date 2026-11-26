<?php
/**
 * Standalone CLI test for the independent Cost Price / Distributor Price raw-price
 * masking logic in resources/stdfunc100.php (stdfunc_get_price_restrictions,
 * stdfunc_is_salesprice_restricted), and the validate_untprc() logic shared by
 * trn_salesfile2_ajax.php / trn_salesorderfile2_ajax.php.
 * No database connection, no $_POST, no $_SESSION - reimplements the pure decision
 * logic against in-memory fake data, mirroring tests/test_sales_payout_logic.php.
 * Run: php tests/test_hide_costdistri_price_masking.php
 */

// --- Reimplementation of resources/stdfunc100.php's stdfunc_get_price_restrictions() ---
function get_price_restrictions_check($isAdmin, $usercode, $mencap, $fakeUserMenusRow)
{
    if ($isAdmin) {
        return [false, false];
    }
    if (trim((string)$usercode) === '') {
        return [false, false];
    }
    if ($fakeUserMenusRow === null) {
        return [false, false];
    }
    $hideCost = isset($fakeUserMenusRow['hide_cost_price']) && (string)$fakeUserMenusRow['hide_cost_price'] === '1';
    $hideDistri = isset($fakeUserMenusRow['hide_distri_price']) && (string)$fakeUserMenusRow['hide_distri_price'] === '1';
    return [$hideCost, $hideDistri];
}

// --- Reimplementation of resources/stdfunc100.php's stdfunc_is_salesprice_restricted() ---
function is_salesprice_restricted($salesprice_id, $hideCostPrice, $hideDistriPrice)
{
    if ($salesprice_id === 'SID-00002' && $hideCostPrice) {
        return true;
    }
    if ($salesprice_id === 'SID-00001' && $hideDistriPrice) {
        return true;
    }
    return false;
}

// --- Reimplementation of sales_validate_untprc() / salesorder_validate_untprc() ---
// (both ajax files carry byte-identical logic; one reimplementation covers both)
function validate_untprc($postedUntprc, $postedExtprc, $hideCostPrice, $hideDistriPrice, $fakeExistingRow)
{
    $postedUntprc = trim((string)$postedUntprc);
    $postedExtprc = trim((string)$postedExtprc);
    if (($hideCostPrice || $hideDistriPrice) && $postedUntprc === '__RESTRICTED__') {
        if ($fakeExistingRow !== null && is_salesprice_restricted($fakeExistingRow['salesprice_id'], $hideCostPrice, $hideDistriPrice)) {
            return [$fakeExistingRow['untprc'], $fakeExistingRow['extprc']];
        }
        return [0, 0];
    }
    return [$postedUntprc, $postedExtprc];
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
// Scenario 1: permission check (mirrors stdfunc_get_price_restrictions)
// ---------------------------------------------------------------------------
check('admin bypasses restriction even with both flags set',
    get_price_restrictions_check(true, 'admin', 'Sales Transaction', ['hide_cost_price' => '1', 'hide_distri_price' => '1']) === [false, false]);
check('empty usercode is never restricted',
    get_price_restrictions_check(false, '', 'Sales Transaction', ['hide_cost_price' => '1', 'hide_distri_price' => '1']) === [false, false]);
check('no matching user_menus row -> not restricted',
    get_price_restrictions_check(false, 'user1', 'Sales Transaction', null) === [false, false]);
check('both flags off -> not restricted',
    get_price_restrictions_check(false, 'user1', 'Sales Transaction', ['hide_cost_price' => '0', 'hide_distri_price' => '0']) === [false, false]);
check('hide_cost_price on (string "1") only -> cost restricted, distributor not',
    get_price_restrictions_check(false, 'user1', 'Sales Transaction', ['hide_cost_price' => '1', 'hide_distri_price' => '0']) === [true, false]);
check('hide_distri_price on (string "1") only -> distributor restricted, cost not',
    get_price_restrictions_check(false, 'user1', 'Sales Transaction', ['hide_cost_price' => '0', 'hide_distri_price' => '1']) === [false, true]);
check('both flags on (int 1) -> both restricted (loose string compare)',
    get_price_restrictions_check(false, 'user1', 'Sales Transaction', ['hide_cost_price' => 1, 'hide_distri_price' => 1]) === [true, true]);

// ---------------------------------------------------------------------------
// Scenario 2: independent restriction - hiding Cost Price must not affect
// Distributor Price, and vice versa
// ---------------------------------------------------------------------------
check('cost-only restriction: Cost (SID-00002) is restricted', is_salesprice_restricted('SID-00002', true, false) === true);
check('cost-only restriction: Distributor (SID-00001) is NOT restricted', is_salesprice_restricted('SID-00001', true, false) === false);
check('distributor-only restriction: Distributor (SID-00001) is restricted', is_salesprice_restricted('SID-00001', false, true) === true);
check('distributor-only restriction: Cost (SID-00002) is NOT restricted', is_salesprice_restricted('SID-00002', false, true) === false);
check('both restricted: Cost and Distributor both restricted', is_salesprice_restricted('SID-00002', true, true) === true && is_salesprice_restricted('SID-00001', true, true) === true);
check('neither restricted: Wholesale (SID-00003) is never restricted', is_salesprice_restricted('SID-00003', true, true) === false);
check('neither restricted: Retail (SID-00004) is never restricted', is_salesprice_restricted('SID-00004', true, true) === false);

// ---------------------------------------------------------------------------
// Scenario 3: editing an existing Cost/Distributor line with the sentinel preserves the
// original stored price instead of overwriting it with the placeholder
// ---------------------------------------------------------------------------
$existingCostLine = ['untprc' => 150.00, 'extprc' => 300.00, 'salesprice_id' => 'SID-00002'];
[$untprc1, $extprc1] = validate_untprc('__RESTRICTED__', '__RESTRICTED__', true, false, $existingCostLine);
check('cost-restricted edit of Cost line with sentinel preserves original untprc', $untprc1 === 150.00);
check('cost-restricted edit of Cost line with sentinel preserves original extprc', $extprc1 === 300.00);

$existingDistributorLine = ['untprc' => 90.00, 'extprc' => 180.00, 'salesprice_id' => 'SID-00001'];
[$untprc2, $extprc2] = validate_untprc('__RESTRICTED__', '__RESTRICTED__', false, true, $existingDistributorLine);
check('distri-restricted edit of Distributor line with sentinel preserves original untprc', $untprc2 === 90.00);
check('distri-restricted edit of Distributor line with sentinel preserves original extprc', $extprc2 === 180.00);

// ---------------------------------------------------------------------------
// Scenario 4: a user with only Cost restricted editing a Distributor line must see
// the real price, not a masked/zeroed one (independence check via the validate path)
// ---------------------------------------------------------------------------
[$untprc4a, $extprc4a] = validate_untprc('85.00', '170.00', true, false, $existingDistributorLine);
check('cost-only restricted user editing a Distributor line uses posted price as-is (untprc)', $untprc4a === '85.00');
check('cost-only restricted user editing a Distributor line uses posted price as-is (extprc)', $extprc4a === '170.00');

// ---------------------------------------------------------------------------
// Scenario 5: restricted user editing a Wholesale/Retail line is unaffected (normal path)
// ---------------------------------------------------------------------------
[$untprc5, $extprc5] = validate_untprc('75.50', '151.00', true, true, ['untprc' => 75.50, 'extprc' => 151.00, 'salesprice_id' => 'SID-00003']);
check('restricted edit of a non-restricted (Wholesale) line uses the posted price as-is', $untprc5 === '75.50');
check('restricted edit of a non-restricted (Wholesale) line uses the posted amount as-is', $extprc5 === '151.00');

// ---------------------------------------------------------------------------
// Scenario 6: unrestricted users are never intercepted, even if a sentinel-shaped value
// is somehow posted
// ---------------------------------------------------------------------------
[$untprc6, $extprc6] = validate_untprc('__RESTRICTED__', '__RESTRICTED__', false, false, $existingCostLine);
check('not restricted: sentinel-shaped value passes through untouched', $untprc6 === '__RESTRICTED__');

// ---------------------------------------------------------------------------
// Scenario 7: sentinel posted but no matching existing line (e.g. a brand-new/insert
// line, which should never legitimately carry the sentinel) -> safe zero fallback,
// never stores the literal token as a price
// ---------------------------------------------------------------------------
[$untprc7, $extprc7] = validate_untprc('__RESTRICTED__', '__RESTRICTED__', true, true, null);
check('restricted + sentinel + no existing row falls back to 0', $untprc7 === 0 && $extprc7 === 0);

// ---------------------------------------------------------------------------
// Scenario 8: sentinel posted for a line whose current stored type is NOT restricted
// under the active flags (stale client state) -> do not trust the sentinel blindly,
// fall back to 0
// ---------------------------------------------------------------------------
[$untprc8, $extprc8] = validate_untprc('__RESTRICTED__', '__RESTRICTED__', true, true, ['untprc' => 40.00, 'extprc' => 80.00, 'salesprice_id' => 'SID-00004']);
check('restricted + sentinel + existing row is Retail (not a restricted type) falls back to 0', $untprc8 === 0 && $extprc8 === 0);

[$untprc9, $extprc9] = validate_untprc('__RESTRICTED__', '__RESTRICTED__', true, false, $existingDistributorLine);
check('cost-only restricted + sentinel on a Distributor line (stale client) falls back to 0', $untprc9 === 0 && $extprc9 === 0);

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
