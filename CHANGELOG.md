# Changelog

## 2026-03-03
- Updated revert behavior for DOCNUM normalization in `revert_to_original_docnum.php`.
- New rule: for `SAL-`/`SAM-` values with numeric suffix longer than 5 digits, keep only the last 5 digits.
- Kept existing `SAL`/`SAM` prefix during revert; 5-digit values stay unchanged, shorter suffixes are left-padded to 5 digits.
- Updated revert confirmation copy in `utl_fixes.php` to show the new examples and rule.
- Documented the rule update in `PROJECT_NOTES.md`.
- Hardened `migrate_docnum_sal.php` idempotence: `SAL` values already in 9-digit format are skipped.
- Enforced 9-digit maximum output during migration (`SAL` trims to last 9 when needed).
- Kept `SAM` conversion behavior (`+100000`) while using the last 5 digits as SAM base for stability.
- Enabled exception-based DB error handling in migration so failed updates rollback reliably.
- Fixed foreign-key migration failure (`SQLSTATE[23000] 1451`) by temporarily disabling and restoring `FOREIGN_KEY_CHECKS` during coordinated parent/child docnum remap.

## 2026-02-11
- Fixed `Sales Costing(Date)` PDF column overflow where `Name/Item` text could overlap the `Qty` column.
- Updated column widths in `trndate_rep_sales_cost.php` so all columns fit inside the printable area (`x=25` to `x=770`).
- Matched text trim/wrap limits to the actual `Shop Name/Item` column width to prevent overdraw into `Qty`.
- Tightened `Shop Name/Item` wrap width with extra right padding so second-line wrap starts earlier and leaves clearer space before `Qty`.
