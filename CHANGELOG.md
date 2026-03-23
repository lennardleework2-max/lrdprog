# Changelog

## 2026-03-23
- Reworked `stock_transfer_transaction_file2.php` into a stock-transfer-specific entry page:
  - the line-item modal now uses `Warehouse From`, `Warehouse Floor From`, `Warehouse To`, `Warehouse Floor To`, and one `Warehouse Staff`
  - removed the inventory-adjustment `Price`, `Amount`, and header `Total` behavior from the stock-transfer screen
  - saves now create paired `tranfile2` rows per transfer line: negative stock from the source location and positive stock to the destination location
  - transfer header saves now keep `tranfile1.trntot` at `0`, while transfer detail saves keep `tranfile2.untprc` and `tranfile2.extprc` at `0`
  - added current-stock display and save-time insufficient-quantity blocking based on the selected source warehouse, source floor, item, and transaction date

## 2026-03-22
- Updated `customer_sales_rep.php` XLSX export generation:
  - replaced the old tab-delimited pseudo-`xlsx` stream with a real `PhpSpreadsheet` `.xlsx` writer
  - kept the PDF export path unchanged
  - clears output buffers before streaming so stricter clients such as iOS can open the file without corruption errors
- Updated `customer_sales_pdf.php` and `customer_sales_rep.php` customer-sales columns:
  - added `Current Total Stock` before `Current Total Inventory Valuation`
  - the new column uses `SUM(tranfile2.stkqty)` per item across joined `tranfile1` + `tranfile2` rows, matching the provided SQL logic
  - the new column is included in both PDF and XLSX exports and is available in the sort dropdown
  - tightened the PDF item-text wrap width further so long item descriptions break earlier and no longer overlap the Tiktok column
- Added `stock_transfer_transaction.php` by mirroring the inventory-adjustment list page for stock transfers:
  - uses the same UI and flow as `trn_invadjfile1.php`
  - filters records with `trncde='STT'`
  - now opens `stock_transfer_transaction_file2.php` for add/edit
- Split the shared entry screen into wrappers:
  - `trn_invadjfile2.php` is inventory-adjustment-only again
  - `stock_transfer_transaction_file2.php` is the separate stock-transfer detail page entry point
  - `trn_invadjfile2_shared.php` holds the shared implementation used by both wrappers
- Updated `trn_invadjfile2.php` and `trn_invadjfile2_ajax.php` for the inventory-adjustment warehouse metadata flow:
  - added `Warehouse`, dependent `Warehouse Floor`, and `Warehouse Staff` fields to the add/edit line-item modals
  - warehouse-floor options now refresh from the selected warehouse and display `warehouse_floor.floor_no`
  - line-item saves now write `tranfile2.warcde`, `tranfile2.warehouse_floor_id`, and `tranfile2.warehouse_staff_id`
  - the inventory-adjustment line-items table now shows `Warehouse` and `Warehouse Floor`
  - added a read-only `User` field below remarks; add mode shows the current session user's `userdesc`, while edit mode shows only the saved `tranfile1.usercode -> users.userdesc`
  - new header saves now persist the current session `usercode` into `tranfile1.usercode` without overwriting existing edit records
- Updated `mf_warehouse.php`, `mf_warehouse_floor.php`, and `pager/pager_ajax.class.php` for the warehouse schema rename:
  - warehouse pages now use `warehouse.warcde` instead of `warehouse_id`
  - the Warehouse -> Floors redirect now posts `warcde`
  - warehouse-floor filtering and context injection now use `warehouse_floor.warcde`
  - warehouse delete cleanup now removes matching floors and stock-movement rows by `warcde`
- Updated `pager/pager_ajax.pager.php` so custom action buttons can still receive placeholder fields like `{warcde}` even when those fields are not shown as visible table columns
- Updated `mf_warehouse_transaction.php` latest-transactions view to make transaction details view-only:
  - removed the `Edit Transaction` action from the details modal
  - blocked edit-mode entry handling in this shared page and now shows a warning that editing is disabled
- Updated warehouse-transaction datetime display formatting in `mf_warehouse_transaction.php` from `YYYY-MM-DD HH:MM` to `MM/DD/YYYY HH:MMPM` (example: `03/18/2026 10:06PM`) in both the transaction cards and the details modal.
- Updated `mf_warehouse_transaction.php` create/edit form behavior for `REMOVE STOCK` and `TRANSFER STOCK`:
  - save is now disabled immediately when entered quantity is greater than the current available stock
  - users now get an `Insufficient stock` alert only when they press save and quantity exceeds available stock
  - existing backend insufficient-stock validation remains in place as the server-side safeguard
- Updated `mf_warehouse_transaction.php` latest-transaction cards/details display:
  - `Remarks` now appears as its own card block instead of a separate text area below the grid
  - `Warehouse Staff` continues to show the full staff name from `warehouse_staff.fname + " " + warehouse_staff.lname`
  - `User` now shows `users.userdesc` instead of `warehouse_stock_movement.usercode`
  - fixed the latest-transactions list query so `users.userdesc` is loaded consistently and no longer throws an undefined `transaction_userdesc` notice
- Updated the `mf_warehouse_transaction.php` search modal and sorting options:
  - item search now matches only `itemfile.itmdsc`
  - removed `Quantity` and `Stock Effect` from both search filters and sort options
  - user search and sorting now use `users.userdesc`
  - item sort labels now use `Item Description`

## 2026-03-21
- Replaced the copied draft UI in `customer_sales_pdf.php` with a dedicated `Customer Sales Report` filter page.
- Added `customer_sales_rep.php` as a new PDF/XLSX export backend for Customer Sales, matching the `top_sales_item_pdf.php` landscape report style.
- The new Customer Sales export now:
  - loops through `itemfile` and displays `itemfile.itmdsc`
  - anchors the rolling report window to `date_to` or the current Philippine date when `date_to` is blank
  - calculates last-window sales quantities for `Tiktok`, `Lazada`, `Shopee` (used in total online), and `RYU` from `tranfile1`/`tranfile2` sales data (`trncde='SAL'`)
  - computes `Total Online Qty Sold` as `Tiktok + Lazada + Shopee`
  - computes `30 Days Inventory Ratio` as `current total stock / qty sold in the same last-window sales period`
  - computes `Current Total Inventory Valuation` as `current stock * latest purchase cost`
  - sorts the report by `Total Online Qty Sold (Last 30 Days)` using the `ASC/DESC` filter from `customer_sales_pdf.php`

## 2026-03-18
- Updated `mf_warehouse_transaction.php` to simplify the list-page chrome by removing the old top Warehouse Transactions header/action strip.
- Reworked the stock-movement entry layout in `mf_warehouse_transaction.php`:
  - split the entry screen into a top transaction-type/date section and a lower warehouse-details section
  - removed the separate warehouse-selection card
  - renamed warehouse/floor selectors to `Source Warehouse` and `Source Floor`
  - added a transfer-only destination row below the source warehouse/floor row
  - removed the duplicate lower `Selected Floor` display
  - removed the Back to Transactions button from the entry page
  - changed the cancel button styling to red
- Added cascading warehouse/floor behavior on the entry form so floor dropdowns refresh immediately from `warehouse_floor` by selected `warehouse_id`; floor option labels now use `floor_no`.
- Replaced the custom AJAX item-search table in `mf_warehouse_transaction.php` with the same Select2 searchable item picker pattern used in `inventory_balance.php`, inside the item modal.
- Updated quantity stock guidance in `mf_warehouse_transaction.php` + `mf_warehouse_transaction_ajax.php`:
  - current available stock now renders as a smaller light-gray note below quantity for remove/transfer flows
  - quantity `max` is set from available source-floor stock
  - stock preview excludes the edited movement row(s) during edit mode for more accurate transfer/remove limits
- Applied final entry-page structure and validation updates:
  - section 1 fixed to three columns: transaction type, source warehouse, source floor
  - movement date moved to section 2 as first column
  - transfer destination controls moved to a second row (`empty | destination warehouse | destination floor`)
  - removed modal-based item search and switched to inline Select2 searchable dropdown using `#open_item_search`, matching `stock_card.php` behavior
  - fixed warehouse-floor onchange refresh to rebuild floor options from `warehouse_floor` by selected `warehouse_id` and show `floor_no` labels
  - added required-field save blocking on client side and backend for warehouse, floor, item, movement date, staff, and transfer destination fields.

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
- Changed warehouse `Floors` action navigation from `GET` to `POST`.
- Updated `mf_warehouse_floor.php` to read selected `warehouse_id` from `POST` context.
- Removed warehouse selector field from floor CRUD modal; warehouse is now enforced by fixed page context.
- Removed `warehouse_floor_id` from floor display/search fields in `mf_warehouse_floor.php`.
- Updated `pager/pager_js.class.js` + `pager/pager_ajax.class.php` so fixed table filter values are also applied during insert.
- Added floor-insert backend safeguard in `pager/pager_ajax.class.php`:
  - if `warehouse_id` is missing in insert payload for `warehouse_floor`, fallback to session context (`warehouse_floor_context_id`)
  - if still missing, return user-facing validation message instead of throwing DB FK exception
- Updated `mf_warehouse_floor.php` to persist selected warehouse context in session for floor CRUD continuity.
- Enabled warehouse delete confirmation in `mf_warehouse.php` by turning on pager alert delete modal.
- Updated delete flow in `pager/pager_ajax.class.php` for `warehouse` to run in transaction and delete related data in order:
  - `warehouse_stock_movement` rows tied to the warehouse floors
  - `warehouse_floor` rows tied to the warehouse
  - `warehouse` row
- Added safe JSON error handling for delete failures to prevent PDO fatal output in the UI.

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
