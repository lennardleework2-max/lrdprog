# Latest

## 2026-03-12
- Restored `trn_salesfile2_ajax.php` from the latest intact/lint-valid copy inside the corrupted file.
- Fixed `com_pay` save path in `trn_salesfile2_ajax.php`:
  - Initialize/sanitize `txt_com_pay` from POST.
  - Fallback compute from `sel_salesman_id` + `trntot` when `txt_com_pay` is missing.
  - Persist `com_pay` during `tranfile1` total updates after item add/edit/delete flows.
- Fixed UI overwrite behavior in `trn_salesfile2.php`:
  - On page load, auto-compute commission only when `#txt_com_pay` is empty.
  - After table refresh/`trntot` update, auto-compute only when `#txt_com_pay` is empty.

