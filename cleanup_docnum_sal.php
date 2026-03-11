<?php
/**
 * CLEANUP DOCNUM - Fix any SAL/SAM docnum to exactly 9 digits
 *
 * This script will fix docnum regardless of current format:
 * - SAL-00001 → SAL-000000001
 * - SAL-0000000000054 → SAL-000000054
 * - SAL-000000000000001324 → SAL-000001324
 * - SAM-17451 → SAL-000117451
 *
 * IMPORTANT: This ignores old_docnum2 and just fixes the current docnum
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

require_once("resources/db_init.php");
require_once("resources/connect4.php");

echo "<!DOCTYPE html><html><head><title>DOCNUM Cleanup</title></head><body>";
echo "<h1>DOCNUM Cleanup - Fix to 9 digits</h1>";
echo "<pre>";

try {
    // Start transaction
    $link->beginTransaction();

    echo "Starting cleanup...\n\n";

    // ========================================================================
    // STEP 1: Fix tranfile1 (parent table)
    // ========================================================================
    echo "STEP 1: Fixing tranfile1...\n";
    echo str_repeat("-", 80) . "\n";

    // Get all SAL/SAM records from tranfile1
    $select_tranfile1 = "SELECT recid, docnum, trncde FROM tranfile1
                         WHERE trncde='SAL' AND (docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%')
                         ORDER BY recid";
    $stmt_tranfile1 = $link->prepare($select_tranfile1);
    $stmt_tranfile1->execute();

    $tranfile1_updates = 0;
    $tranfile1_mapping = array(); // old_docnum => new_docnum

    while($row = $stmt_tranfile1->fetch(PDO::FETCH_ASSOC)) {
        $old_docnum = $row['docnum'];
        $new_docnum = cleanup_docnum($old_docnum);

        if($old_docnum !== $new_docnum) {
            // Update the record (clear old_docnum2 since we're fixing it fresh)
            $update_sql = "UPDATE tranfile1 SET docnum=?, old_docnum2=NULL WHERE recid=?";
            $stmt_update = $link->prepare($update_sql);
            $stmt_update->execute(array($new_docnum, $row['recid']));

            $tranfile1_mapping[$old_docnum] = $new_docnum;
            $tranfile1_updates++;

            echo sprintf("  [%d] %s → %s\n", $row['recid'], $old_docnum, $new_docnum);
        }
    }

    echo "\nFixed $tranfile1_updates records in tranfile1\n\n";

    // ========================================================================
    // STEP 2: Fix tranfile2 (child table - foreign key to tranfile1)
    // ========================================================================
    echo "STEP 2: Fixing tranfile2...\n";
    echo str_repeat("-", 80) . "\n";

    $tranfile2_updates = 0;

    foreach($tranfile1_mapping as $old_docnum => $new_docnum) {
        // Update all tranfile2 records with this docnum
        $update_tf2 = "UPDATE tranfile2 SET docnum=?, old_docnum2=NULL WHERE docnum=?";
        $stmt_update_tf2 = $link->prepare($update_tf2);
        $stmt_update_tf2->execute(array($new_docnum, $old_docnum));

        $affected = $stmt_update_tf2->rowCount();
        if($affected > 0) {
            $tranfile2_updates += $affected;
            echo sprintf("  %s → %s (%d detail records)\n", $old_docnum, $new_docnum, $affected);
        }
    }

    echo "\nFixed $tranfile2_updates records in tranfile2\n\n";

    // ========================================================================
    // STEP 3: Fix upld_salesfile (all records)
    // ========================================================================
    echo "STEP 3: Fixing upld_salesfile...\n";
    echo str_repeat("-", 80) . "\n";

    // Get all SAL/SAM records from upld_salesfile
    $select_upld = "SELECT recid, docnum FROM upld_salesfile
                    WHERE docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%'
                    ORDER BY recid";
    $stmt_upld = $link->prepare($select_upld);
    $stmt_upld->execute();

    $upld_updates = 0;

    while($row_upld = $stmt_upld->fetch(PDO::FETCH_ASSOC)) {
        $old_docnum = $row_upld['docnum'];
        $new_docnum = cleanup_docnum($old_docnum);

        if($old_docnum !== $new_docnum) {
            // Update the record
            $update_upld = "UPDATE upld_salesfile SET docnum=?, old_docnum2=NULL WHERE recid=?";
            $stmt_update_upld = $link->prepare($update_upld);
            $stmt_update_upld->execute(array($new_docnum, $row_upld['recid']));

            $upld_updates++;

            echo sprintf("  [%d] %s → %s\n", $row_upld['recid'], $old_docnum, $new_docnum);
        }
    }

    echo "\nFixed $upld_updates records in upld_salesfile\n\n";

    // ========================================================================
    // COMMIT
    // ========================================================================
    $link->commit();

    echo str_repeat("=", 80) . "\n";
    echo "CLEANUP COMPLETED SUCCESSFULLY!\n";
    echo str_repeat("=", 80) . "\n";
    echo "\nSummary:\n";
    echo "  - tranfile1: $tranfile1_updates records fixed\n";
    echo "  - tranfile2: $tranfile2_updates records fixed\n";
    echo "  - upld_salesfile: $upld_updates records fixed\n";
    echo "\nAll docnum values now have exactly 9 digits.\n";
    echo "old_docnum2 columns have been cleared.\n";

} catch(Exception $e) {
    // Rollback on error
    $link->rollBack();

    echo "\n\n";
    echo str_repeat("!", 80) . "\n";
    echo "ERROR: Cleanup failed!\n";
    echo str_repeat("!", 80) . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
    echo "\nAll changes have been rolled back.\n";
}

echo "</pre>";
echo "</body></html>";

/**
 * Cleanup docnum to exactly 9 digits (works with any format)
 *
 * @param string $old_docnum Original docnum
 * @return string New docnum with exactly 9 digits
 */
function cleanup_docnum($old_docnum) {
    // Extract prefix and number
    $parts = explode('-', $old_docnum);
    if(count($parts) !== 2) {
        return $old_docnum; // Return as-is if format is unexpected
    }

    $prefix = $parts[0];
    $number_str = $parts[1];

    // Convert to integer to remove all leading zeros
    $number = intval($number_str);

    // Safety: if number exceeds 9 digits, take last 9 digits
    if($number > 999999999) {
        $number = $number % 1000000000;
    }

    if($prefix === 'SAL') {
        // SAL → exactly 9 digits
        $new_number = str_pad($number, 9, '0', STR_PAD_LEFT);
        return 'SAL-' . $new_number;

    } else if($prefix === 'SAM') {
        // SAM → SAL with 0001 prefix
        $new_number_value = 100000 + $number;

        // Safety: ensure doesn't exceed 9 digits
        if($new_number_value > 999999999) {
            $new_number_value = $new_number_value % 1000000000;
        }

        $new_number = str_pad($new_number_value, 9, '0', STR_PAD_LEFT);
        return 'SAL-' . $new_number;

    } else {
        // Unknown prefix, return as-is
        return $old_docnum;
    }
}

?>
