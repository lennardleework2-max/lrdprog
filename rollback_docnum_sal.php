<?php
/**
 * ROLLBACK DOCNUM Migration Script
 *
 * Restores docnum from old_docnum2 and clears old_docnum2 so migration can run fresh
 *
 * IMPORTANT: Only use this if the migration went wrong!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

require_once("resources/db_init.php");
require_once("resources/connect4.php");

echo "<!DOCTYPE html><html><head><title>DOCNUM Rollback</title></head><body>";
echo "<h1>DOCNUM Rollback - Restore from old_docnum2</h1>";
echo "<pre>";

try {
    // Start transaction
    $link->beginTransaction();

    echo "Starting rollback...\n\n";

    // ========================================================================
    // STEP 1: Check if old_docnum2 has data
    // ========================================================================
    echo "STEP 1: Checking old_docnum2 data...\n";
    echo str_repeat("-", 80) . "\n";

    $check_sql = "SELECT COUNT(*) as cnt FROM tranfile1 WHERE old_docnum2 IS NOT NULL AND old_docnum2 != ''";
    $stmt_check = $link->prepare($check_sql);
    $stmt_check->execute();
    $check_result = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if($check_result['cnt'] == 0) {
        echo "ERROR: No backup data found in old_docnum2 column!\n";
        echo "Cannot rollback. Please manually fix the docnum values.\n";
        $link->rollBack();
        exit;
    }

    echo "Found {$check_result['cnt']} records with backup data\n\n";

    // ========================================================================
    // STEP 2: Rollback tranfile1
    // ========================================================================
    echo "STEP 2: Rolling back tranfile1...\n";
    echo str_repeat("-", 80) . "\n";

    $update_sql = "UPDATE tranfile1 SET docnum = old_docnum2, old_docnum2 = NULL WHERE old_docnum2 IS NOT NULL AND old_docnum2 != ''";
    $stmt_update = $link->prepare($update_sql);
    $stmt_update->execute();
    $affected1 = $stmt_update->rowCount();

    echo "Restored $affected1 records in tranfile1 and cleared old_docnum2\n\n";

    // ========================================================================
    // STEP 3: Rollback tranfile2
    // ========================================================================
    echo "STEP 3: Rolling back tranfile2...\n";
    echo str_repeat("-", 80) . "\n";

    $update_sql2 = "UPDATE tranfile2 SET docnum = old_docnum2, old_docnum2 = NULL WHERE old_docnum2 IS NOT NULL AND old_docnum2 != ''";
    $stmt_update2 = $link->prepare($update_sql2);
    $stmt_update2->execute();
    $affected2 = $stmt_update2->rowCount();

    echo "Restored $affected2 records in tranfile2 and cleared old_docnum2\n\n";

    // ========================================================================
    // STEP 4: Rollback upld_salesfile
    // ========================================================================
    echo "STEP 4: Rolling back upld_salesfile...\n";
    echo str_repeat("-", 80) . "\n";

    $update_sql3 = "UPDATE upld_salesfile SET docnum = old_docnum2, old_docnum2 = NULL WHERE old_docnum2 IS NOT NULL AND old_docnum2 != ''";
    $stmt_update3 = $link->prepare($update_sql3);
    $stmt_update3->execute();
    $affected3 = $stmt_update3->rowCount();

    echo "Restored $affected3 records in upld_salesfile and cleared old_docnum2\n\n";

    // ========================================================================
    // COMMIT
    // ========================================================================
    $link->commit();

    echo str_repeat("=", 80) . "\n";
    echo "ROLLBACK COMPLETED SUCCESSFULLY!\n";
    echo str_repeat("=", 80) . "\n";
    echo "\nSummary:\n";
    echo "  - tranfile1: $affected1 records restored\n";
    echo "  - tranfile2: $affected2 records restored\n";
    echo "  - upld_salesfile: $affected3 records restored\n";
    echo "\nAll docnum values have been restored to their original values.\n";
    echo "old_docnum2 columns have been cleared.\n";
    echo "\nYou can now run the migration again with the fixed logic.\n";

} catch(Exception $e) {
    // Rollback on error
    $link->rollBack();

    echo "\n\n";
    echo str_repeat("!", 80) . "\n";
    echo "ERROR: Rollback failed!\n";
    echo str_repeat("!", 80) . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
    echo "\nAll changes have been rolled back.\n";
}

echo "</pre>";
echo "</body></html>";

?>
