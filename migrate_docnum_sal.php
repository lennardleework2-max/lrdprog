<?php
/**
 * DOCNUM Migration Script - SAL/SAM to SAL format
 *
 * Transforms:
 * - SAL-00001 → SAL-000000001 (adds 4 zeros)
 * - SAM-17451 → SAL-000117451 (changes SAM to SAL, adds 0001 prefix)
 *
 * Tables affected: tranfile1, tranfile2, upld_salesfile
 *
 * IMPORTANT: Make sure to backup your database before running this script!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // No time limit

require_once("resources/db_init.php");
require_once("resources/connect4.php");

echo "<!DOCTYPE html><html><head><title>DOCNUM Migration</title></head><body>";
echo "<h1>DOCNUM Migration - SAL/SAM Format</h1>";
echo "<pre>";

$fk_checks_disabled = false;

try {
    // Ensure DB errors throw exceptions so transaction rollback is reliable.
    $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $link->beginTransaction();

    echo "Starting migration...\n\n";

    // tranfile2/upld_salesfile reference tranfile1.docnum without ON UPDATE CASCADE.
    // Disable FK checks for this controlled bulk remap, then restore afterwards.
    $link->exec("SET FOREIGN_KEY_CHECKS=0");
    $fk_checks_disabled = true;
    echo "DEBUG: FOREIGN_KEY_CHECKS temporarily disabled for migration\n";

    // ========================================================================
    // STEP 1: Update tranfile1 (parent table)
    // ========================================================================
    echo "STEP 1: Updating tranfile1...\n";
    echo str_repeat("-", 80) . "\n";

    // First, let's check what records exist
    $check_sql = "SELECT COUNT(*) as cnt FROM tranfile1 WHERE trncde='SAL'";
    $stmt_check = $link->prepare($check_sql);
    $stmt_check->execute();
    $check_result = $stmt_check->fetch(PDO::FETCH_ASSOC);
    echo "DEBUG: Found {$check_result['cnt']} records with trncde='SAL'\n";

    $check_sql2 = "SELECT COUNT(*) as cnt FROM tranfile1 WHERE docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%'";
    $stmt_check2 = $link->prepare($check_sql2);
    $stmt_check2->execute();
    $check_result2 = $stmt_check2->fetch(PDO::FETCH_ASSOC);
    echo "DEBUG: Found {$check_result2['cnt']} records with docnum starting with SAL- or SAM-\n";

    // Get all SAL/SAM records from tranfile1
    $select_tranfile1 = "SELECT recid, docnum, trncde FROM tranfile1
                         WHERE trncde='SAL' AND (docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%')
                         ORDER BY docnum";
    $stmt_tranfile1 = $link->prepare($select_tranfile1);
    $stmt_tranfile1->execute();

    $tranfile1_updates = 0;
    $tranfile1_mapping = array(); // old_docnum => new_docnum
    $total_found = 0;

    while($row = $stmt_tranfile1->fetch(PDO::FETCH_ASSOC)) {
        $total_found++;
        $old_docnum = $row['docnum'];
        $new_docnum = transform_docnum($old_docnum);

        echo "DEBUG: Processing recid={$row['recid']}, docnum={$old_docnum}, trncde={$row['trncde']}, new_docnum={$new_docnum}\n";

        if($old_docnum !== $new_docnum) {
            // Update the record
            $update_sql = "UPDATE tranfile1 SET old_docnum2=?, docnum=? WHERE recid=?";
            $stmt_update = $link->prepare($update_sql);
            $stmt_update->execute(array($old_docnum, $new_docnum, $row['recid']));

            $tranfile1_mapping[$old_docnum] = $new_docnum;
            $tranfile1_updates++;

            echo sprintf("  [%d] %s → %s\n", $row['recid'], $old_docnum, $new_docnum);
        } else {
            echo "DEBUG: Skipping - old and new are the same\n";
        }
    }

    echo "\nDEBUG: Total records found: $total_found\n";
    echo "Updated $tranfile1_updates records in tranfile1\n\n";

    // ========================================================================
    // STEP 2: Update tranfile2 (child table - foreign key to tranfile1)
    // ========================================================================
    echo "STEP 2: Updating tranfile2...\n";
    echo str_repeat("-", 80) . "\n";

    $tranfile2_updates = 0;

    foreach($tranfile1_mapping as $old_docnum => $new_docnum) {
        // Get all tranfile2 records with this docnum
        $select_tf2 = "SELECT recid, docnum FROM tranfile2 WHERE docnum=?";
        $stmt_tf2 = $link->prepare($select_tf2);
        $stmt_tf2->execute(array($old_docnum));

        while($row2 = $stmt_tf2->fetch(PDO::FETCH_ASSOC)) {
            // Update tranfile2 record
            $update_tf2 = "UPDATE tranfile2 SET old_docnum2=?, docnum=? WHERE recid=?";
            $stmt_update_tf2 = $link->prepare($update_tf2);
            $stmt_update_tf2->execute(array($old_docnum, $new_docnum, $row2['recid']));
            $tranfile2_updates++;
        }

        $count = $stmt_tf2->rowCount();
        if($count > 0) {
            echo sprintf("  %s → %s (%d detail records)\n", $old_docnum, $new_docnum, $count);
        }
    }

    echo "\nUpdated $tranfile2_updates records in tranfile2\n\n";

    // ========================================================================
    // STEP 3: Update upld_salesfile (all records)
    // ========================================================================
    echo "STEP 3: Updating upld_salesfile...\n";
    echo str_repeat("-", 80) . "\n";

    // Get all SAL/SAM records from upld_salesfile
    $select_upld = "SELECT recid, docnum FROM upld_salesfile
                    WHERE docnum LIKE 'SAL-%' OR docnum LIKE 'SAM-%'
                    ORDER BY docnum";
    $stmt_upld = $link->prepare($select_upld);
    $stmt_upld->execute();

    $upld_updates = 0;

    while($row_upld = $stmt_upld->fetch(PDO::FETCH_ASSOC)) {
        $old_docnum = $row_upld['docnum'];
        $new_docnum = transform_docnum($old_docnum);

        if($old_docnum !== $new_docnum) {
            // Update the record
            $update_upld = "UPDATE upld_salesfile SET old_docnum2=?, docnum=? WHERE recid=?";
            $stmt_update_upld = $link->prepare($update_upld);
            $stmt_update_upld->execute(array($old_docnum, $new_docnum, $row_upld['recid']));
            $upld_updates++;
            echo sprintf("  [%d] %s -> %s\n", $row_upld['recid'], $old_docnum, $new_docnum);
        }
    }

    echo "\nUpdated $upld_updates records in upld_salesfile\n\n";

    // ========================================================================
    // COMMIT
    // ========================================================================
    $link->exec("SET FOREIGN_KEY_CHECKS=1");
    $fk_checks_disabled = false;
    echo "DEBUG: FOREIGN_KEY_CHECKS restored\n";

    $link->commit();

    echo str_repeat("=", 80) . "\n";
    echo "MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo str_repeat("=", 80) . "\n";
    echo "\nSummary:\n";
    echo "  - tranfile1: $tranfile1_updates records updated\n";
    echo "  - tranfile2: $tranfile2_updates records updated\n";
    echo "  - upld_salesfile: $upld_updates records updated\n";
    echo "\nAll old docnum values saved to old_docnum2 column.\n";

} catch(Exception $e) {
    // Rollback on error
    if($link->inTransaction()) {
        $link->rollBack();
    }
    if($fk_checks_disabled) {
        try {
            $link->exec("SET FOREIGN_KEY_CHECKS=1");
            $fk_checks_disabled = false;
        } catch(Exception $restore_ex) {
            // Ignore restore errors; connection close will reset session settings.
        }
    }

    echo "\n\n";
    echo str_repeat("!", 80) . "\n";
    echo "ERROR: Migration failed!\n";
    echo str_repeat("!", 80) . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
    echo "\nAll changes have been rolled back.\n";
}

echo "</pre>";
echo "</body></html>";

/**
 * Transform docnum to exactly 9-digit format (IDEMPOTENT - safe to run multiple times)
 *
 * @param string $old_docnum Original docnum
 * @return string New docnum with exactly 9 digits
 */
function transform_docnum($old_docnum) {
    // Extract prefix and number
    $parts = explode('-', $old_docnum, 2);
    if(count($parts) !== 2) {
        return $old_docnum; // Return as-is if format is unexpected
    }

    $prefix = strtoupper(trim($parts[0]));
    $number_str = trim($parts[1]);

    // Process only strict SAL/SAM + numeric suffix format.
    if(($prefix !== 'SAL' && $prefix !== 'SAM') || !preg_match('/^\d+$/', $number_str)) {
        return $old_docnum;
    }

    // IDEMPOTENT CHECK: If already exactly 9 digits and starts with SAL, keep it
    if($prefix === 'SAL' && strlen($number_str) === 9 && ctype_digit($number_str)) {
        return $old_docnum; // Already in correct format, don't change
    }

    if($prefix === 'SAL') {
        // SAL with any digit count → SAL with exactly 9 digits
        if(strlen($number_str) > 9) {
            $number_str = substr($number_str, -9);
        }
        $new_number = str_pad($number_str, 9, '0', STR_PAD_LEFT);
        return 'SAL-' . $new_number;

    } else if($prefix === 'SAM') {
        // SAM → SAL with 0001 prefix (add 100000 to 5-digit numbers)
        // Use last 5 digits as SAM base, then add 100000.
        $sam_base_5 = (int) substr(str_pad($number_str, 5, '0', STR_PAD_LEFT), -5);
        $new_number = str_pad((string)(100000 + $sam_base_5), 9, '0', STR_PAD_LEFT);
        return 'SAL-' . $new_number;

    } else {
        // Unknown prefix, return as-is
        return $old_docnum;
    }
}

?>
