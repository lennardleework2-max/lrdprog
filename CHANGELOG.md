# Changelog

## 2026-03-14
- Added `mf_warehouse.php` as a new master-file page for warehouse maintenance.
- Implemented CRUD for `warehouse` with fields: `warehouse_id` (auto code), `warehouse_name`, `location`.
- Added `mf_warehouse_floor.php` for `warehouse_floor` CRUD with fields: `warehouse_floor_id` (auto code), `warehouse_id`, `floor_name`, `floor_no`.
- Removed in-page warehouse/floor tabs and kept `mf_warehouse.php` as warehouse-only CRUD.
- Added row-level `Floors` action in warehouse Action menu, linking to floor maintenance for that specific `warehouse_id`.
- Scoped `mf_warehouse_floor.php` records to the selected warehouse context and locked CRUD warehouse selection to that warehouse.
- Updated warehouse code seeds for LNexts/pager generation:
  - `warehouse_id`: `WHS-0000001`
  - `warehouse_floor_id`: `WHFID-0000001`
- Added pager support for fixed table filtering (`table_filter_field`, `table_filter_value`) and row-placeholder resolution in custom action button functions.
- Fixed pager custom-button hidden-input attribute escaping in `pager/pager_main.class.php` to prevent leaked raw attribute text under the pager.

## 2026-03-12
- Fixed shared pager SQL field parsing in `pager/pager_ajax.pager.php` to prevent malformed `SELECT` clauses when a real column is named `fname`.
- Replaced `remove_xfields(..., "fname")` in pager field extraction with explicit `fields[<column>_displayData][fname]` parsing and non-empty field-list assembly for safer template-wide behavior.
- Recovered `trn_salesfile2_ajax.php` from corrupted state to a clean lint-valid file.
- Fixed undefined `txt_com_pay` handling in `trn_salesfile2_ajax.php` and normalized posted commission payment parsing.
- Ensured `com_pay` is persisted during `tranfile1` total updates triggered by add/edit/delete item flows.
- Prevented frontend commission-payment overwrite in `trn_salesfile2.php` by auto-fetching salesman commission only when `txt_com_pay` is empty.

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
