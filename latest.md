# Latest

## 2026-03-22
- Updated warehouse master pages for the new warehouse-code schema:
  - `mf_warehouse.php` now uses `warcde`
  - `mf_warehouse_floor.php` now loads and filters floors by `warcde`
  - the pager AJAX handler now carries warehouse-floor context and delete cleanup by `warcde`
  - the pager row loader now also includes custom-button placeholder fields like `warcde`, so the Floors action receives the correct value
- Updated `mf_warehouse_transaction.php` latest-transactions UI:
  - transaction details modal is now view-only and no longer shows `Edit Transaction`
  - edit-mode access through this page is blocked and shows a disabled-edit warning instead
  - movement dates on the cards and in the modal now use `MM/DD/YYYY HH:MMPM` format, for example `03/18/2026 10:06PM`
- Updated `mf_warehouse_transaction.php` stock-movement form validation:
  - for `REMOVE STOCK` and `TRANSFER STOCK`, the save button now disables when quantity is higher than current available stock
  - users get an `Insufficient stock` alert only when they press save with an amount beyond available stock
  - server-side stock validation still blocks invalid saves as a fallback
- Updated `mf_warehouse_transaction.php` transaction card display:
  - `Remarks` now shows as its own info block in the same grid layout
  - `User` now shows `users.userdesc`
  - `Warehouse Staff` display remains the full staff name from `warehouse_staff`
  - fixed the latest-transactions query so the card renderer always receives `transaction_userdesc`
- Updated `mf_warehouse_transaction.php` search modal:
  - item search now uses only item description
  - quantity and stock-effect search/sort options were removed
  - user search/sort now use `users.userdesc`

## 2026-03-21
- Reworked `customer_sales_pdf.php` into the actual `Customer Sales Report` filter page with:
  - `Date To` as the report anchor date
  - `ASC/DESC` sorting for total online quantity sold in the last 30 days
  - export buttons that now post to the new `customer_sales_rep.php` backend for both PDF and XLSX
- Added `customer_sales_rep.php`:
  - landscape PDF/XLSX output patterned after `top_sales_item_pdf.php`
  - columns: `Item`, `Tiktok Qty Sold Last 30 Days`, `Lazada Qty Sold Last 30 Days`, `Total Online Qty Sold Last 30 Days`, `RYU Qty Sold Last 30 Days`, `30 Days Inventory Ratio`, `Current Total Inventory Valuation`
  - item loop sourced from `itemfile`
  - online-platform sales sourced from `tranfile1` + `tranfile2` + `customerfile` with `trncde='SAL'`
  - total online quantity defined as `Tiktok + Lazada + Shopee`
  - current stock based on `SUM(tranfile2.stkqty)` on or before the report end date
  - valuation based on current stock multiplied by the latest purchase `untprc` on or before the report end date

## 2026-03-18
- Updated `mf_warehouse_transaction.php` warehouse-transaction UI:
  - removed the old top list-page header section
  - removed the entry-page Back to Transactions button
  - moved transaction-type selection to the top of the source warehouse/floor area
  - relabeled entry selections to `Source Warehouse` / `Source Floor`
  - shows `Destination Warehouse` / `Destination Floor` only when `TRANSFER STOCK` is selected
  - removed the duplicate `Selected Floor` field from the lower form
  - changed the cancel action button to red
- Added dynamic warehouse-floor cascading on the stock-movement entry form:
  - changing source warehouse rebuilds source floors
  - changing destination warehouse rebuilds destination floors
  - floor dropdown labels now show the linked `warehouse_floor.floor_no` value for the selected warehouse
- Updated transfer/remove stock preview behavior:
  - stock now shows directly below quantity
  - quantity max is synchronized to current available source-floor stock
  - stock preview supports edit-mode exclusion of the current movement row(s)
- Fixed item lookup on the stock-movement form:
  - search button now opens `#itemSearchModal` with the same Select2 searchable item-picker pattern used in `inventory_balance.php`
  - item selection updates the form and refreshes source-floor stock preview
- Split the stock-movement entry page into a separate top section for transaction type/date and a lower warehouse-details section.
- `TRANSFER STOCK` now shows an additional destination row below the source warehouse/floor row.
- Finalized entry layout based on latest requirements:
  - top section now has exactly 3 columns: `Transaction Type | Source Warehouse | Source Floor`
  - movement date was moved to section 2 and is now the first column there
  - transfer-only row now appears below the top row as: `empty | Destination Warehouse | Destination Floor`
- Replaced item modal flow with a direct Select2 searchable item field (ID: `open_item_search`) using the same pattern as `stock_card.php` (`-- Select Item --`, searchable, clearable).
- Enforced floor filtering on warehouse change:
  - selecting a source warehouse immediately rebuilds source-floor options from `warehouse_floor` by matching `warehouse_id`
  - floor labels now show `floor_no` only
- Added hard save-blocking for required fields:
  - source warehouse, source floor, item, movement date, warehouse staff, quantity
  - plus destination warehouse/floor when transaction type is transfer
  - enforced in both client-side checks and backend validation.

## 2026-03-14
- Updated `mf_warehouse.php` to remove separate warehouse/floor tabs and keep a single Warehouse CRUD view.
- Added `Floors` action inside each warehouse row Action menu (with distinct icon/color) to open floor maintenance for that specific warehouse.
- Updated `mf_warehouse_floor.php` to require `warehouse_id` from the selected warehouse action, and scoped the floor list/CRUD to that warehouse only.
- Locked floor insert/edit warehouse selection to the current warehouse context (single-option dropdown), so floors are added under an existing selected warehouse.
- Updated ID seeds used by pager/LNexts generation:
  - `warehouse_id` seed: `WHS-0000001`
  - `warehouse_floor_id` seed: `WHFID-0000001`
- Extended pager internals to support:
  - fixed table filtering (`table_filter_field`, `table_filter_value`)
  - row-value placeholder rendering in custom action functions (e.g., `{warehouse_id}`)
- Fixed pager custom-button hidden attribute escaping so raw fragments like `btn-function='goWarehouseFloors('{warehouse_id}')'` no longer render below the table.
- Changed warehouse -> floors navigation to `POST` (no querystring `warehouse_id`).
- Removed `Warehouse` field from floor CRUD modal because warehouse context is already fixed from selected warehouse.
- Removed `warehouse_floor_id` from floor list display so users only see/search relevant floor fields.
- Added server-side fallback for floor inserts to enforce `warehouse_id` from selected floor context (`$_SESSION['warehouse_floor_context_id']`) when needed.
- Added explicit context-missing validation message for floor insert: user must reopen Floors from Warehouse action if context is missing.
- Enabled delete confirmation modal for warehouse records in `mf_warehouse.php`.
- Added transactional warehouse delete handling in `pager/pager_ajax.class.php`:
  - deletes `warehouse_stock_movement` rows linked by floor first
  - deletes `warehouse_floor` rows for the warehouse
  - deletes the warehouse record last
  - returns safe JSON validation messages on delete failure (no fatal crash)

## 2026-03-12
- Fixed shared pager SQL field parsing in `pager/pager_ajax.pager.php` to correctly handle columns named `fname` (and similar patterns) without producing malformed `SELECT ,...` queries.
- Replaced brittle `remove_xfields(..., "fname")` usage in the pager template with explicit parsing of `fields[<column>_displayData][fname]` input names, and now builds `SELECT` lists from non-empty validated field names.
- Restored `trn_salesfile2_ajax.php` from the latest intact/lint-valid copy inside the corrupted file.
- Fixed `com_pay` save path in `trn_salesfile2_ajax.php`:
  - Initialize/sanitize `txt_com_pay` from POST.
  - Fallback compute from `sel_salesman_id` + `trntot` when `txt_com_pay` is missing.
  - Persist `com_pay` during `tranfile1` total updates after item add/edit/delete flows.
- Fixed UI overwrite behavior in `trn_salesfile2.php`:
  - On page load, auto-compute commission only when `#txt_com_pay` is empty.
  - After table refresh/`trntot` update, auto-compute only when `#txt_com_pay` is empty.
