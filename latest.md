# Latest

## 2026-03-14
- Added new page `mf_warehouse.php` with CRUD support for:
  - `warehouse` table only
- Added new page `mf_warehouse_floor.php` for CRUD of `warehouse_floor`.
- Removed the in-page tab switch and changed the flow to:
  - click `Edit Floors` in `mf_warehouse.php`
  - navigate to `mf_warehouse_floor.php` for floor CRUD
- Added `Back to Warehouse` button in `mf_warehouse_floor.php`.
- Kept search, export, pager, and user-activity behavior aligned with existing master-file pages.

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
