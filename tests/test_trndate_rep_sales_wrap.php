<?php
/**
 * Standalone CLI test for the buyer_name wrapping logic added to trndate_rep_sales.php.
 * No database connection, no $_POST, no real report execution.
 * Run: php tests/test_trndate_rep_sales_wrap.php
 */

require_once __DIR__ . '/../ezpdfclass/class/class.ezpdf.php';

$pdf = new Cezpdf('Letter', 'landscape');
$pdf->selectFont(__DIR__ . '/../ezpdfclass/fonts/Helvetica.afm');

// --- Copied verbatim from trndate_rep_sales.php so this test exercises the same logic ---

function trim_str($string, $max_wid, $fsize)
{
    global $pdf;
    if (get_class($pdf) == 'tab_ezpdf') {
        return $string;
    }
    $xarr_str = str_split($string);
    $max_wid -= 5;
    $xxstr = "";
    $xcut = false;
    foreach ($xarr_str as $value) {
        $xstr_wid = $pdf->getTextWidth($fsize, $xxstr . $value);
        if ($xstr_wid > $max_wid) {
            $xcut = true;
            break;
        }
        $xxstr = $xxstr . $value;
    }
    if ($xcut) {
        $xxstr = $xxstr . '...';
    }
    return $xxstr;
}

function wrap_text($string, $max_wid, $fsize) {
    global $pdf;

    if (empty($string)) {
        return array('');
    }

    $text_width = $pdf->getTextWidth($fsize, $string);
    if ($text_width <= $max_wid) {
        return array($string);
    }

    $words = explode(' ', $string);
    $lines = array();
    $current_line = '';

    foreach ($words as $word) {
        $test_line = ($current_line == '') ? $word : $current_line . ' ' . $word;
        $test_width = $pdf->getTextWidth($fsize, $test_line);

        if ($test_width <= $max_wid) {
            $current_line = $test_line;
        } else {
            if ($current_line != '') {
                $lines[] = $current_line;
            }
            $current_line = $word;
        }
    }

    if ($current_line != '') {
        $lines[] = $current_line;
    }

    return $lines;
}

function wrap_text_limited($string, $max_wid, $fsize, $max_lines = 3) {
    global $pdf;

    if (empty($string)) {
        return array('');
    }

    $wrapped_lines = array();
    $base_lines = wrap_text($string, $max_wid, $fsize);

    foreach ($base_lines as $base_line) {
        if ($pdf->getTextWidth($fsize, $base_line) <= $max_wid) {
            $wrapped_lines[] = $base_line;
            continue;
        }

        $remaining = $base_line;
        while ($remaining !== '') {
            $chunk = '';
            $chars = str_split($remaining);

            foreach ($chars as $char) {
                if ($chunk !== '' && $pdf->getTextWidth($fsize, $chunk . $char) > $max_wid) {
                    break;
                }
                $chunk .= $char;
            }

            if ($chunk === '') {
                $chunk = substr($remaining, 0, 1);
            }

            $wrapped_lines[] = rtrim($chunk);
            $remaining = ltrim(substr($remaining, strlen($chunk)));
        }
    }

    if (count($wrapped_lines) > $max_lines) {
        $overflow_text = implode(' ', array_slice($wrapped_lines, $max_lines - 1));
        $wrapped_lines = array_slice($wrapped_lines, 0, $max_lines - 1);
        $wrapped_lines[] = trim_str($overflow_text, $max_wid, $fsize);
    }

    return empty($wrapped_lines) ? array('') : $wrapped_lines;
}

function sanitize_tab_text($string) {
    $string = (string)$string;
    $string = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $string);
    $string = preg_replace('/[ ]{2,}/', ' ', $string);

    return trim($string);
}

// --- Test harness ---

$failures = 0;
$assertions = 0;

function check($label, $condition) {
    global $failures, $assertions;
    $assertions++;
    if (!$condition) {
        echo "FAIL: $label\n";
        $failures++;
    } else {
        echo "PASS: $label\n";
    }
}

// 1. Normal short buyer name -> single line
$lines = wrap_text_limited('Cristina Dela Cruz', 100, 9, 3);
check('normal buyer name yields exactly 1 line', count($lines) === 1);
check('normal buyer name text preserved', $lines[0] === 'Cristina Dela Cruz');

// 2. Very long buyer name -> wraps but respects max_lines cap
$long_name = 'Maria Cristina Fernandez-Delacruz-Villanueva Santos Reyes De Los Santos Guevarra Buenaventura Corporation International Trading Enterprise';
$lines = wrap_text_limited($long_name, 100, 9, 3);
check('long buyer name wraps to more than 1 line', count($lines) > 1);
check('long buyer name respects max_lines=3 cap', count($lines) <= 3);
foreach ($lines as $i => $l) {
    check("long buyer name line $i is not empty", trim($l) !== '');
}

// 3. Empty / null buyer name
check('empty string returns single blank line', wrap_text_limited('', 100, 9, 3) === array(''));
check('null-cast (missing buyer row) returns single blank line', wrap_text_limited((string)null, 100, 9, 3) === array(''));

// 4. Special characters: apostrophe, quotes, peso sign, accented characters
$special = "O'Brien & \"Sons\" Trading ₱ Café Niño Distribución";
$lines = wrap_text_limited($special, 60, 9, 3);
check('special-character name does not throw and returns lines', is_array($lines) && count($lines) >= 1);
check('special-character name lines are non-empty', trim(implode('', $lines)) !== '');

// 5. Missing buyer row simulated via trim((string)null) upstream, then wrapped
$buyer_name_from_null_join = trim((string)null);
check('missing buyer record trims to empty string', $buyer_name_from_null_join === '');
check('empty buyer_name from missing join wraps to single blank line', wrap_text_limited($buyer_name_from_null_join, 100, 9, 3) === array(''));

// 6. Ship To / Payment Details wrapped with max_lines = 2
$long_shipto = '123 Some Very Long Street Name, Building 4B, Barangay Sample, Quezon City, Metro Manila, Philippines 1100';
$ship_lines = wrap_text_limited($long_shipto, 100, 9, 2);
check('long ship-to respects max_lines=2 cap', count($ship_lines) <= 2);

$long_paydetails = 'Cash on delivery with partial advance payment via bank transfer and remaining balance due upon receipt of goods';
$pay_lines = wrap_text_limited($long_paydetails, 95, 9, 2);
check('long payment details respects max_lines=2 cap', count($pay_lines) <= 2);

// 7. sanitize_tab_text strips newlines/tabs so XLS/tab rows never get split
$dirty = "Buyer\tName\r\nWith\nBreaks   And   Spaces";
$clean = sanitize_tab_text($dirty);
check('sanitize_tab_text removes tabs/newlines', strpos($clean, "\t") === false && strpos($clean, "\n") === false && strpos($clean, "\r") === false);
check('sanitize_tab_text collapses repeated spaces', strpos($clean, '   ') === false);

echo "\n$assertions assertions, $failures failed.\n";
exit($failures > 0 ? 1 : 0);
