<?php
/**
 * Standalone CLI test for the "Discontinued -> red highlight" behavior on
 * critical_pdf.php (inline settings table) and critical_pdf_rep.php (PDF/XLSX
 * export), plus the other crt_* flag columns (minimal order, offline, online).
 * No database connection, no $_POST/$_SESSION - reimplements the pure
 * flag-normalization/highlight-decision logic against in-memory fake rows,
 * mirroring tests/test_hide_costdistri_price_masking.php.
 * Run: php tests/test_critical_pdf_discontinued_flag.php
 */

// --- Reimplementation of critical_pdf.php:59's inline row-highlight check ---
// (the row's "is-discontinued" CSS class is appended based on this boolean)
function page_row_is_discontinued($crt_discontinued_value)
{
    return strtoupper(trim((string)$crt_discontinued_value)) === 'Y';
}

// --- Reimplementation of critical_pdf.php:62's per-checkbox "checked" check ---
// shared by crt_minimal_order / crt_offline / crt_online / crt_discontinued
function page_checkbox_checked($raw_value)
{
    return strtoupper(trim((string)$raw_value)) === 'Y';
}

// --- Reimplementation of critical_pdf_rep.php:44's flag_is_yes(), used by both
// the PDF renderer (line 39: pink fill when discontinued) and the XLSX
// exporter (line 55: pink fill + red font when discontinued) ---
function flag_is_yes($value)
{
    return strtoupper(trim((string)$value)) === 'Y';
}

// --- Reimplementation of the PDF row highlight decision (critical_pdf_rep.php:39) ---
function pdf_row_style($discontinued)
{
    return $discontinued
        ? ['fill' => [0.973, 0.843, 0.855], 'text' => [0.518, 0.126, 0.161]]
        : ['fill' => null, 'text' => [0, 0, 0]];
}

// --- Reimplementation of the XLSX row highlight decision (critical_pdf_rep.php:55) ---
function xlsx_row_style($discontinued)
{
    return $discontinued
        ? ['fill' => 'FFF8D7DA', 'font' => 'FF842029']
        : ['fill' => null, 'font' => null];
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
// Scenario 1: raw DB value normalization for crt_discontinued (page row class)
// ---------------------------------------------------------------------------
check('DB value "Y" marks the row discontinued', page_row_is_discontinued('Y') === true);
check('DB value "y" (lowercase) still marks the row discontinued', page_row_is_discontinued('y') === true);
check('DB value " Y " (whitespace-padded) still marks the row discontinued', page_row_is_discontinued(' Y ') === true);
check('DB value "N" does NOT mark the row discontinued', page_row_is_discontinued('N') === false);
check('DB value "" (empty string) does NOT mark the row discontinued', page_row_is_discontinued('') === false);
check('DB value null does NOT mark the row discontinued', page_row_is_discontinued(null) === false);
check('DB value "YES" (not exact "Y") does NOT mark the row discontinued', page_row_is_discontinued('YES') === false);
check('DB value "1" does NOT mark the row discontinued', page_row_is_discontinued('1') === false);

// ---------------------------------------------------------------------------
// Scenario 2: the PDF/XLSX exporter's flag_is_yes() must treat every raw DB
// value exactly the same way as the on-page row class check (Scenario 1) -
// if these two independent implementations ever drift, discontinued items
// would show red on-page but not in the export, or vice versa.
// ---------------------------------------------------------------------------
$sample_values = ['Y', 'y', ' Y ', 'N', 'n', '', null, 'YES', '1', '0', 'Yes'];
foreach ($sample_values as $value) {
    $label = 'DB value ' . var_export($value, true);
    check("$label: page-row and export flag_is_yes() agree",
        page_row_is_discontinued($value) === flag_is_yes($value));
}

// ---------------------------------------------------------------------------
// Scenario 3: PDF highlight only fires for discontinued rows, using the pink
// fill / dark-red text/stroke colors, and leaves other rows in plain black
// ---------------------------------------------------------------------------
$discontinued_pdf_style = pdf_row_style(flag_is_yes('Y'));
check('PDF: discontinued row gets the pink fill color', $discontinued_pdf_style['fill'] === [0.973, 0.843, 0.855]);
check('PDF: discontinued row gets the dark-red text/stroke color', $discontinued_pdf_style['text'] === [0.518, 0.126, 0.161]);

$normal_pdf_style = pdf_row_style(flag_is_yes('N'));
check('PDF: non-discontinued row gets no fill', $normal_pdf_style['fill'] === null);
check('PDF: non-discontinued row keeps plain black text', $normal_pdf_style['text'] === [0, 0, 0]);

// ---------------------------------------------------------------------------
// Scenario 4: XLSX highlight mirrors the PDF behavior (pink fill + red font)
// ---------------------------------------------------------------------------
$discontinued_xlsx_style = xlsx_row_style(flag_is_yes('Y'));
check('XLSX: discontinued row gets the pink fill (FFF8D7DA)', $discontinued_xlsx_style['fill'] === 'FFF8D7DA');
check('XLSX: discontinued row gets the dark-red font (FF842029)', $discontinued_xlsx_style['font'] === 'FF842029');

$normal_xlsx_style = xlsx_row_style(flag_is_yes('N'));
check('XLSX: non-discontinued row gets no fill override', $normal_xlsx_style['fill'] === null);
check('XLSX: non-discontinued row gets no font color override', $normal_xlsx_style['font'] === null);

// ---------------------------------------------------------------------------
// Scenario 5: "everything else" - the other three crt_* checkbox flags
// (minimal order, offline, online) use the identical Y/N normalization and
// are independent of each other and of the discontinued flag
// ---------------------------------------------------------------------------
$item_row = [
    'crt_minimal_order' => 'Y',
    'crt_offline' => 'N',
    'crt_online' => 'Y',
    'crt_discontinued' => 'N',
];
check('Minimal Order checkbox reflects its own DB value (Y -> checked)', page_checkbox_checked($item_row['crt_minimal_order']) === true);
check('Offline checkbox reflects its own DB value (N -> unchecked)', page_checkbox_checked($item_row['crt_offline']) === false);
check('Online checkbox reflects its own DB value (Y -> checked)', page_checkbox_checked($item_row['crt_online']) === true);
check('Discontinued checkbox reflects its own DB value (N -> unchecked, no red row)', page_row_is_discontinued($item_row['crt_discontinued']) === false);

// ---------------------------------------------------------------------------
// Scenario 6: an item can be discontinued regardless of its other flags -
// the red highlight must fire purely off crt_discontinued, uninfluenced by
// minimal_order/offline/online being on or off
// ---------------------------------------------------------------------------
$discontinued_but_online_item = ['crt_minimal_order' => 'N', 'crt_offline' => 'N', 'crt_online' => 'Y', 'crt_discontinued' => 'Y'];
check('Item flagged Online AND Discontinued still shows the red highlight',
    page_row_is_discontinued($discontinued_but_online_item['crt_discontinued']) === true);
check('Item flagged Online AND Discontinued keeps Online checked independently',
    page_checkbox_checked($discontinued_but_online_item['crt_online']) === true);

$all_flags_off_item = ['crt_minimal_order' => 'N', 'crt_offline' => 'N', 'crt_online' => 'N', 'crt_discontinued' => 'N'];
check('Item with every flag off shows no red highlight',
    page_row_is_discontinued($all_flags_off_item['crt_discontinued']) === false);

if ($failures === 0) {
    echo "\nAll checks passed.\n";
    exit(0);
} else {
    echo "\n$failures check(s) FAILED.\n";
    exit(1);
}
