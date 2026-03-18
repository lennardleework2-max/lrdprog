# Latest

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
- Updated transfer/remove stock preview behavior:
  - stock now shows directly below quantity
  - quantity max is synchronized to current available source-floor stock
  - stock preview supports edit-mode exclusion of the current movement row(s)
- Fixed item lookup on the stock-movement form:
  - search button now reliably opens `#itemSearchModal`
  - modal search results allow selecting an item back into the form

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
