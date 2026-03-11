<?php
/**
 * REVERT TO ORIGINAL DOCNUM - Convert back to 5-digit format
 *
 * Reverts ALL SAL/SAM docnum to a normalized 5-digit suffix format:
 * - SAL-000000001 -> SAL-00001
 * - SAL-00000003452 -> SAL-03452
 * - SAM-000117451 -> SAM-17451
 *
 * Rule: when numeric suffix has more than 5 digits, keep only the last 5.
 * This prepares data for the proper transformation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

require_once("resources/db_init.php");
require_once("resources/connect4.php");

echo "<!DOCTYPE html><html><head><title>Revert to Original DOCNUM</title></head><body>";
echo "<h1>Revert to Original DOCNUM - Back to 5 digits</h1>";
echo "<pre>";

try {
    // Start transaction
    $link->beginTransaction();

    echo "Starting revert to original format...\n\n";

    // ========================================================================
    // STEP 1: Revert tranfile1 (parent table)
    // ========================================================================
    echo "STEP 1: Reverting tranfile1...\n";
    echo str_repeat("-", 80) . "\n";

    // Get all SAL/SAM records from tranfile1
    $select_tranfile1 = "SELECT recid, docnum, trncde FROM tranfile1
                         WHERE trncde='SAL' AND (docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%')
                         ORDER BY recid";
    $stmt_tranfile1 = $link->prepare($select_tranfile1);
    $stmt_tranfile1->execute();

    $tranfile1_updates = 0;
    $tranfile1_mapping = array(); // new_docnum => old_docnum (for updating child tables)

    while($row = $stmt_tranfile1->fetch(PDO::FETCH_ASSOC)) {
        $current_docnum = $row['docnum'];
        $original_docnum = revert_to_original($current_docnum);

        if($current_docnum !== $original_docnum) {
            // Update the record (clear old_docnum2)
            $update_sql = "UPDATE tranfile1 SET docnum=?, old_docnum2=NULL WHERE recid=?";
            $stmt_update = $link->prepare($update_sql);
            $stmt_update->execute(array($original_docnum, $row['recid']));

            $tranfile1_mapping[$current_docnum] = $original_docnum;
            $tranfile1_updates++;

            echo sprintf("  [%d] %s → %s\n", $row['recid'], $current_docnum, $original_docnum);
        }
    }

    echo "\nReverted $tranfile1_updates records in tranfile1\n\n";

    // ========================================================================
    // STEP 2: Revert tranfile2 (child table - foreign key to tranfile1)
    // ========================================================================
    echo "STEP 2: Reverting tranfile2...\n";
    echo str_repeat("-", 80) . "\n";

    $tranfile2_updates = 0;

    foreach($tranfile1_mapping as $current_docnum => $original_docnum) {
        // Update all tranfile2 records with this docnum
        $update_tf2 = "UPDATE tranfile2 SET docnum=?, old_docnum2=NULL WHERE docnum=?";
        $stmt_update_tf2 = $link->prepare($update_tf2);
        $stmt_update_tf2->execute(array($original_docnum, $current_docnum));

        $affected = $stmt_update_tf2->rowCount();
        if($affected > 0) {
            $tranfile2_updates += $affected;
            echo sprintf("  %s → %s (%d detail records)\n", $current_docnum, $original_docnum, $affected);
        }
    }

    echo "\nReverted $tranfile2_updates records in tranfile2\n\n";

    // ========================================================================
    // STEP 3: Revert upld_salesfile (all records)
    // ========================================================================
    echo "STEP 3: Reverting upld_salesfile...\n";
    echo str_repeat("-", 80) . "\n";

    // Get all SAL/SAM records from upld_salesfile
    $select_upld = "SELECT recid, docnum FROM upld_salesfile
                    WHERE docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%'
                    ORDER BY recid";
    $stmt_upld = $link->prepare($select_upld);
    $stmt_upld->execute();

    $upld_updates = 0;

    while($row_upld = $stmt_upld->fetch(PDO::FETCH_ASSOC)) {
        $current_docnum = $row_upld['docnum'];
        $original_docnum = revert_to_original($current_docnum);

        if($current_docnum !== $original_docnum) {
            // Update the record
            $update_upld = "UPDATE upld_salesfile SET docnum=?, old_docnum2=NULL WHERE recid=?";
            $stmt_update_upld = $link->prepare($update_upld);
            $stmt_update_upld->execute(array($original_docnum, $row_upld['recid']));

            $upld_updates++;

            echo sprintf("  [%d] %s → %s\n", $row_upld['recid'], $current_docnum, $original_docnum);
        }
    }

    echo "\nReverted $upld_updates records in upld_salesfile\n\n";

    // ========================================================================
    // COMMIT
    // ========================================================================
    $link->commit();

    echo str_repeat("=", 80) . "\n";
    echo "REVERT COMPLETED SUCCESSFULLY!\n";
    echo str_repeat("=", 80) . "\n";
    echo "\nSummary:\n";
    echo "  - tranfile1: $tranfile1_updates records reverted\n";
    echo "  - tranfile2: $tranfile2_updates records reverted\n";
    echo "  - upld_salesfile: $upld_updates records reverted\n";
    echo "\nAll docnum values are now normalized to 5-digit suffix format.\n";
    echo "Data is ready for proper transformation.\n";
    echo "\nYou can now run the migration script to convert to 9-digit format.\n";

} catch(Exception $e) {
    // Rollback on error
    $link->rollBack();

    echo "\n\n";
    echo str_repeat("!", 80) . "\n";
    echo "ERROR: Revert failed!\n";
    echo str_repeat("!", 80) . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
    echo "\nAll changes have been rolled back.\n";
}

echo "</pre>";
echo "</body></html>";

/**
 * Revert docnum to normalized 5-digit suffix format
 *
 * @param string $current_docnum Current docnum (any format)
 * @return string Normalized docnum with 5-digit numeric suffix
 */
function revert_to_original($current_docnum) {
    // Extract prefix and number
    $parts = explode('-', $current_docnum, 2);
    if(count($parts) !== 2) {
        return $current_docnum; // Return as-is if format is unexpected
    }

    $prefix = strtoupper(trim($parts[0]));
    $number_str = trim($parts[1]);

    // Only process SAL/SAM with a purely numeric suffix.
    if(($prefix !== 'SAL' && $prefix !== 'SAM') || !preg_match('/^\d+$/', $number_str)) {
        return $current_docnum;
    }

    // Main rule: keep only the last 5 digits when length is > 5.
    if(strlen($number_str) > 5) {
        $number_str = substr($number_str, -5);
    } elseif(strlen($number_str) < 5) {
        // Normalize short numeric suffixes to 5 digits.
        $number_str = str_pad($number_str, 5, '0', STR_PAD_LEFT);
    }

    return $prefix . '-' . $number_str;
}

?>
