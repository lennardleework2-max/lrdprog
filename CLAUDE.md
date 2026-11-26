# LRDPROG Project Documentation

## Stock Transfer Transaction (stock_transfer_transaction_file2.php)

### Current Stock Calculation

**Rule**: Current stock ALWAYS uses **today's date** (not the transaction date) when calculating available stock.

**Rationale**: The "Current Stock" display shows the actual real-time stock available at a warehouse floor. Using the transaction date would show historical stock levels, which is misleading when checking available inventory.

**Implementation**:
- JavaScript `getTodayDate()` function generates today's date in `MM/DD/YYYY` format
- The `refresh()` function sends today's date to the server for stock calculation
- PHP `stt_curstock()` function calculates stock based on:
  - `warehouse_floor_id` (not warcde - floor_id already references the warehouse)
  - `tranfile1.trndte <= today` (uses tranfile1.trndte because tranfile2.trndte can be empty)
  - `itmcde` (item code)

**SQL Query**:
```sql
SELECT COALESCE(SUM(t2.stkqty), 0) current_stock
FROM tranfile1 t1
LEFT JOIN tranfile2 t2 ON t1.docnum = t2.docnum
WHERE t2.warehouse_floor_id = ?
  AND t1.trndte <= ?
  AND t2.itmcde = ?
```

### UOM Dropdown Filtering

UOM dropdowns in transaction pages filter by selected item's valid UOMs from `itemunitfile`:
- Display format: `unmdsc (conversion pcs)` (e.g., "box (10 pcs)")
- "pcs" is always included even if not in itemunitfile
- First visible option is auto-selected after item selection

### Files with UOM Dropdown Filtering
- trn_purchasesorderfile2.php
- trn_purchasefile2.php
- trn_salesorderfile2.php
- trn_salesfile2.php
- trn_salesretfile2.php
- stock_transfer_transaction_file2.php
- trn_invadjfile2_shared.php

## Purchase/Sales Order Matching

### Date Constraint
When matching Purchase Orders to Purchases (or Sales Orders to Sales):
- The source document date must be <= the transaction date
- Example: PO dated 04/20/2026 won't appear when creating a Purchase dated 04/16/2026

### UOM Lock Rule
If a record already has a matched document (PO/SO), the UOM field is disabled and cannot be changed.

## Purchase Transaction PO Matching (trn_purchasefile2.php)

### Hidden Fields for PO Matching
Two hidden fields store matched PO information:
- `multi_itm_select`: Primary field storing comma-separated PO line recids (e.g., "123,456")
- `po_add_hidden`: Backup field inside the ADD modal, also stores PO line recids

### ADD Mode Flow
1. User opens ADD modal → fields cleared (`multi_itm_select`, `po_add_hidden`, `po_add`)
2. User clicks "Search" → PO modal opens with matching POs
3. User checks checkbox → `select_multi_itm("selectData_add")` called
4. AJAX callback updates both `multi_itm_select` and `po_add_hidden`
5. User clicks "Done" → returns to ADD modal
6. User clicks "Save" → `salesfile2('insert')` sends data including `multi_itm_select`
7. PHP handler updates `purchasesorderfile2.tranfile2_recid` for matching

### EDIT Mode Flow
1. User opens EDIT modal → `multi_itm_select` loaded from server with existing matches
2. User can modify PO selection via checkbox
3. PHP compares original vs new selection to add/remove matches

### Backend Matching Logic (trn_purchasefile2_ajax.php)

**CRITICAL**: The `lastInsertId()` must be called immediately after the tranfile2 INSERT,
before any other INSERT operations (like activity logging). Otherwise, `lastInsertId()`
returns the wrong ID.

```php
// 1. Insert tranfile2
PDO_InsertRecord($link, 'tranfile2', $arr_record, false);

// 2. Get the new recid IMMEDIATELY (before activity log or any other INSERT)
$recid_latest_match = $link->lastInsertId();

// 3. Activity logging (does its own INSERT - would corrupt lastInsertId if called before)
PDO_UserActivityLog(...);

// 4. Get PO recids from POST (with fallback)
$multi_itm_select_value = $_POST['multi_itm_select'] ?: $_POST['po_add_hidden'];

// 5. Link selected PO lines to the new tranfile2 record
foreach (explode(',', $multi_itm_select_value) as $po2_recid) {
    $arr['tranfile2_recid'] = $recid_latest_match;
    PDO_UpdateRecord($link, 'purchasesorderfile2', $arr, "recid = ?", [trim($po2_recid)]);
}
```

### Key Fields
| Field | Location | Purpose |
|-------|----------|---------|
| `multi_itm_select` | Outside modals | Primary storage for selected PO recids |
| `po_add_hidden` | Inside ADD modal | Backup storage, serialized with form |
| `po_add` | Inside ADD modal | Display field showing PO docnums |
| `po_edit_hidden` | Inside EDIT modal | Hidden field for EDIT mode |
| `recid_po_hidden` | Outside modals | Single PO recid (legacy) |
