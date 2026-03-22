# LRD Program - Project Notes

## Inventory Adjustment Warehouse Fields (2026-03-22)

- `trn_invadjfile2.php`
  - Added a read-only `User` field below remarks.
  - In add mode, it displays `users.userdesc` from the current session user.
  - In edit mode, it displays only the saved `tranfile1.usercode -> users.userdesc`; if `tranfile1.usercode` is blank, the field stays blank.
  - Added `Warehouse`, `Warehouse Floor`, and `Warehouse Staff` selectors to both add and edit inventory-adjustment line-item modals.
  - The warehouse-floor dropdown now depends on the selected warehouse and displays `warehouse_floor.floor_no`.

- `trn_invadjfile2_ajax.php`
  - New inventory-adjustment header saves now store the current session `usercode` in `tranfile1.usercode`.
  - Edit saves no longer overwrite an existing record's stored `tranfile1.usercode`.
  - Inventory-adjustment line saves and edits now persist:
    - `tranfile2.warcde`
    - `tranfile2.warehouse_floor_id`
    - `tranfile2.warehouse_staff_id`
  - Edit payloads now return those warehouse-related fields so the edit modal can preload them correctly.

## Warehouse Code Schema Update (2026-03-22)

- `mf_warehouse.php`
  - The warehouse master page now uses `warehouse.warcde` as its record code field.
  - The Floors action now posts `warcde` to the floor page.

- `mf_warehouse_floor.php`
  - Warehouse floor context now loads from `warcde` instead of `warehouse_id`.
  - Warehouse name lookup and floor-table filtering now use `warehouse_floor.warcde`.

- `pager/pager_ajax.class.php`
  - Warehouse delete cleanup now removes related `warehouse_floor` and `warehouse_stock_movement` rows by `warcde`.
  - New `warehouse_floor` records now inherit the active warehouse context through `warcde`.

- `pager/pager_ajax.pager.php`
  - Custom button placeholders now pull any referenced fields into the pager row payload even if those fields are not visible in the table.
  - This keeps actions like `goWarehouseFloors('{warcde}')` working without forcing `warcde` to appear as a displayed column.

## Warehouse Transactions View-Only Update (2026-03-22)

- `mf_warehouse_transaction.php`
  - The transaction-details modal on the latest-transactions page is now view-only.
  - The `Edit Transaction` footer action was removed from the modal.
  - If an edit querystring is passed into this shared page, it now falls back to a warning that editing is disabled instead of opening edit mode.
  - Displayed movement dates in the latest-transactions cards and modal now use this format:
    - `MM/DD/YYYY HH:MMPM`
    - example: `03/18/2026 10:06PM`
  - For `REMOVE STOCK` and `TRANSFER STOCK`, client-side save validation now also compares entered quantity against the fetched current available stock:
    - the save button is disabled when quantity is greater than available stock
    - an `Insufficient stock` alert is shown only when save is pressed and the entered quantity exceeds available stock
    - backend stock validation remains active as the final safeguard
  - Latest-transaction display fields were refined:
    - `Remarks` is now rendered as its own info block in the card grid
    - `Warehouse Staff` uses the full `warehouse_staff` name
    - `User` now resolves from `users.userdesc` instead of showing the raw saved `usercode`
    - The latest-transactions list query was corrected to always load `transaction_userdesc`, preventing undefined-index notices in the card renderer
  - The transaction search modal was narrowed to the approved fields:
    - item search now checks only `itemfile.itmdsc`
    - `Quantity` and `Stock Effect` were removed from search and sorting
    - user search/sorting now use `users.userdesc`

## Customer Sales Report (2026-03-21)

- `customer_sales_pdf.php`
  - Replaced the copied unpaid-route page with a dedicated customer-sales report filter form.
  - Export actions now point to `customer_sales_rep.php`.
  - The page keeps only the filters confirmed for this report:
    - `Date To`
    - `Order By` (`ASC` / `DESC`) for total online quantity sold in the last 30 days

- `customer_sales_rep.php`
  - New landscape PDF/XLSX export file modeled on `top_sales_item_pdf.php`.
  - Report rows are driven by `itemfile`; the displayed item column uses `itemfile.itmdsc`.
  - The report window is anchored to `date_to`; if blank, it falls back to the current Philippine date.
  - Platform quantities come from `tranfile1` + `tranfile2` sales rows (`trncde='SAL'`) joined to `customerfile`:
    - `Tiktok`
    - `Lazada`
    - `Shopee`
    - `RYU`
  - `Total Online Qty Sold` is computed as `Tiktok + Lazada + Shopee`.
  - `30 Days Inventory Ratio` is computed as:
    - current stock = `SUM(tranfile2.stkqty)` on or before the report end date
    - divided by total `SUM(tranfile2.itmqty)` sold in the same report window for `trncde='SAL'`
  - `Current Total Inventory Valuation` is computed as:
    - current stock
    - multiplied by the latest purchase cost (`tranfile2.untprc`) from the latest `PUR` transaction for the same item on or before the report end date

## Warehouse Transactions UX Updates (2026-03-18)

- `mf_warehouse_transaction.php`
  - The stock-movement entry page now treats warehouse selection as part of the main form instead of a separate pre-selection step.
  - Transaction type and movement date are now in a separate top section.
  - Source warehouse/floor stay in the main section, and `TRANSFER STOCK` exposes an additional destination warehouse/floor row below them.
  - The old duplicate `Selected Floor` display was removed because source/destination context is already shown at the top of the form.
  - Warehouse floor dropdowns are rebuilt from `warehouse_floor` using the selected `warehouse_id`, and dropdown labels use `floor_no`.
  - Item search now uses the same Select2 searchable picker pattern as `inventory_balance.php`, inside the item modal opened from the search icon.
  - Current source-floor stock is displayed below quantity as a small light-gray note for remove/transfer flows and also sets the input `max` value.

- `mf_warehouse_transaction_ajax.php`
  - `stock_preview` now accepts optional `exclude_recids[]` so edit-mode stock previews can ignore the movement row(s) currently being edited.
  - Stock-preview responses now also return `available_stock` for client-side quantity-limit enforcement.

### Entry Form Final Requirements Applied (2026-03-18)

- Section 1 now uses exactly three columns:
  - Transaction Type
  - Source Warehouse
  - Source Floor
- For `TRANSFER STOCK`, a second row appears below section 1:
  - empty first column
  - Destination Warehouse
  - Destination Floor
- Section 2 now starts with Movement Date as the first column.
- Item selection now follows `stock_card.php` pattern directly:
  - inline Select2 searchable dropdown (`#open_item_search`)
  - no item modal flow for create/edit entry.
- Warehouse-floor onchange behavior is enforced from FK relationship:
  - selecting a warehouse rebuilds floor options from `warehouse_floor` where `warehouse_id` matches
  - floor option labels use `floor_no` only.
- Save blocking rules:
  - required: source warehouse, source floor, item, movement date, warehouse staff, quantity
  - transfer-required: destination warehouse and destination floor
  - enforced client-side and server-side.

## Database Tables

### `useractivitylogfile` Table

Tracks user activity and audit logs in the system. Keeps only the **last 100 records**.

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `recid` | bigint(20) unsigned | AUTO_INCREMENT | Primary Key |
| `usrcde` | varchar(15) | NULL | User code |
| `usrname` | varchar(20) | NULL | Username |
| `usrnam` | varchar(20) | NULL | Username (alternate) |
| `usrdte` | date | NULL | User date |
| `usrtim` | varchar(15) | NULL | User time |
| `trndte` | datetime | NULL | Transaction date/time |
| `module` | varchar(100) | NULL | Module name (ALL CAPS) |
| `activity` | varchar(100) | NULL | Activity performed |
| `empcode` | varchar(50) | NULL | Employee code |
| `fullname` | varchar(100) | NULL | Full name |
| `remarks` | varchar(150) | NULL | Remarks |
| `linenum` | int(11) | 0 | Line number |
| `parameter` | varchar(50) | NULL | Parameter |
| `trncde` | varchar(3) | NULL | Transaction code |
| `trndsc` | varchar(50) | NULL | Transaction description |
| `compname` | varchar(30) | NULL | Company name |
| `docnum` | varchar | NULL | Document number of the affected record |
| `upload_filename` | varchar | NULL | Filename when uploading files |

**Source:** `sql_init.sql` (lines 81-101)

---

## User Activity Log Implementation

### Activity Types Tracked

| Activity | Description |
|----------|-------------|
| `add` | User added a new record |
| `edit` | User edited an existing record |
| `delete` | User deleted a record |
| `upload` | User uploaded a file |
| `export_pdf` | User exported data to PDF |
| `export_txt` | User exported data to TXT |

### Module Name Format
- Module names are stored in **ALL CAPS**
- Derived from the menu name (e.g., "SALES TRANSACTIONS", "EMPLOYEE FILE")

### Record Limit
- System keeps only the **last 100 records**
- Oldest records are automatically deleted when limit is exceeded

### Files Modified

#### Core Files

1. **resources/lx2.pdodb.php**
   - Updated `PDO_UserActivityLog()` function
   - Added `$docnum` and `$upload_filename` parameters
   - Changed max record limit to 100

2. **pager/pager_ajax.class.php**
   - Updated delete, insert, and edit operations
   - Now passes module name (CAPS) and docnum to activity log
   - Activity values changed to: 'add', 'edit', 'delete'
   - Uses `$_POST['fieldcode']` to determine the correct unique key column for each table
     - e.g., `itmcde` for itemfile, `cuscde` for customerfile, `docnum` for transactions
   - For INSERT: uses `$fieldcode_innit` (the generated code value like "ITM-0001")

3. **sales_fileupload_ajax.php**
   - Added activity logging for file uploads
   - Logs upload_filename field

#### PDF Export Files (All modules)

4. **prog_pdf.php** - Main pager PDF/TXT export (all modules using pager)
5. **sales_tranfile2_pdf.php** - Sales Transaction
6. **invadj_tranfile2_pdf.php** - Inventory Adjustment
7. **salesret_tranfile2_pdf.php** - Sales Return
8. **purchase_tranfile2_pdf.php** - Purchase
9. **salesorder_tranfile2_pdf.php** - Sales Order
10. **purchasesorder_tranfile2_pdf.php** - Purchase Order
11. **tranfile1_pdf.php** - Transaction
12. **tranfile2_pdf.php** - Transaction Details
13. **top_sales_platform_pdf.php** - Top Sales Platform
14. **top_sales_item_pdf.php** - Top Sales Item
15. **top_sales_salesman_pdf.php** - Top Sales Salesman
16. **sales_salesman_pdf.php** - Sales Salesman
17. **stock_valuation_pdf.php** - Stock Valuation
18. **sales_fileupload_pdf.php** - Sales File Upload
19. **sales_filepaidupload_pdf.php** - Sales File Paid Upload

---

## Partial Payment Feature

### Database Tables

**partial_payment** table:
| Column | Type | Description |
|--------|------|-------------|
| recid | int(11) | Auto Increment Primary Key |
| docnum | varchar(25) | Links to tranfile1.docnum |
| amount | decimal(12,2) | Payment amount |
| check_number | varchar(...) | Check number (optional) |
| date/date_paid | date | Date of payment (schema-compatible) |

**tranfile1 compatibility notes:**
- `is_partial_payment` is optional and not required in current deployments.

### Files Created/Modified

1. **trn_salesfile2.php**
   - Added "Add Partial Payment" button before Pay Date row
   - Added partial payment modal for add/edit
   - Added JavaScript functions for CRUD operations
   - Displays list of partial payments with total

2. **partial_payment_ajax.php** (NEW)
   - Handles all partial payment CRUD operations
   - Actions: getList, getTotalPaid, getOne, insert, update, delete
   - Validates that total payments don't exceed transaction total
   - No dependency on `tranfile1.is_partial_payment`; partial-payment totals are derived from `partial_payment` records.

### Features
- Add/Edit/Delete partial payments
- Shows total amount, already paid, and remaining
- Validation: total partial payments cannot exceed trntot
- Date defaults to today
- No tranfile1 flag updates are required for partial-payment tracking.

### Fixes Applied (2026-02-23)
- Added live remaining recalculation in modal via `updateRemainingAmount()` while typing amount
- Added hidden baseline values (`pp_trntot_hidden`, `pp_already_paid_hidden`) so edit/add mode computes remaining correctly
- Switched partial payment AJAX calls in `trn_salesfile2.php` to `dataType: "json"` with safer error handling
- Changed `docnum_1` update from `attr('value', ...)` to `.val(...)` so new values are used by subsequent AJAX calls
- Added `loadPartialPayments()` refresh after sales detail save actions
- Hardened `partial_payment_ajax.php` with:
  - centralized response helper
  - trimmed docnum handling
  - date validation
  - transactional insert/update/delete
  - consistent JSON responses for getList/getTotalPaid/getOne/insert/update/delete
- Added date-column compatibility in `partial_payment_ajax.php`:
  - auto-detects `partial_payment.date` (new) or `partial_payment.date_paid` (old)
  - keeps API response key as `date_paid` so frontend JS remains unchanged
- Added `check_number` support in partial payment CRUD:
  - modal field in `trn_salesfile2.php`
  - list view now shows Check #
  - insert/update/get/list in `partial_payment_ajax.php`
  - backend auto-fallback when `check_number` column is not present (older DBs)
 - Added `check_number` column to active `partial_payment` table in current environment (`lrdfiles`)
 - Added backend validation message when `check_number` is submitted but column is missing in active DB

---

## dr_rep_sales.php (Delivery Receipt PDF)

Added signature fields to the **red original copy only** (not duplicate):
- `Packed By:`
- `Prepared By:`
- `Approved By:`

Position: Right side of the black box (Bank/Account info), vertically stacked and aligned.

---

## Sales Salesman Report - Partial Payment Filter (2026-02-25)

### Changes Made

#### sales_salesman_rep.php
- Renamed checkbox "(Sales)Paid" to "(Sales) Fully Paid"
- Added new checkbox "(Sales) Partially Paid"

#### sales_salesman_pdf.php
1. **New Filter Variable:**
   - Added `$inc_partial_paid` to track partially paid checkbox state

2. **Filter Logic:**
   - Fully Paid: Records with `paydate IS NOT NULL`
   - Partially Paid: Records with at least 1 entry in `partial_payment` table (uses EXISTS subquery)
   - Unpaid: Records with `paydate IS NULL`
   - A record can appear under BOTH Fully Paid AND Partially Paid if it qualifies for both
   - Uses OR logic to combine selected filters

3. **PDF/XLS Output - Partial Payment Sub-rows:**
   - For each partially paid record, displays sub-rows below the main data:
     ```
     Check Number: [check_number or "None"]    [date_paid]    [amount]
     (repeat for each partial payment)
     Remaining Balance:                                        [trntot - total_paid]
     ```
   - Sub-rows are indented (position 45 vs 25)
   - Amount column aligned with Total column
   - Works for both PDF and XLS output formats

4. **Header Update:**
   - Report header now shows "(Include/Exclude Fully Paid)", "(Include/Exclude Partially Paid)", "(Include/Exclude Unpaid)"

### Database Dependencies
- Uses `partial_payment` table (docnum, amount, date_paid/date, check_number)
- Auto-detects date column name (date vs date_paid)
- Auto-detects check_number column existence

---

## DOCNUM Migration - SAL/SAM Format Standardization (2026-02-25)

### Problem
Document numbers were overlapping because the format ran out of space:
- Original format: `SAL-00001` to `SAL-99999` (5 digits)
- When limit reached, system started generating `SAM-00001`

### Solution
Migrate all docnum values to 9-digit format:
- `SAL-00001` Ã¢â€ â€™ `SAL-000000001` (add 4 zeros before existing 5 digits)
- `SAM-17451` Ã¢â€ â€™ `SAL-000117451` (change SAMÃ¢â€ â€™SAL, add "0001" prefix)

### Migration Script
**File:** `migrate_docnum_sal.php`

**Process:**
1. Updates `tranfile1` first (parent table with unique docnum)
2. Updates `tranfile2` to match tranfile1 foreign key
3. Updates `upld_salesfile` to match tranfile1 foreign key

**Transformation Logic (IDEMPOTENT):**
- For SAL prefix: if already 9 digits, skips (no change)
- For SAL prefix: if more than 9 digits, keeps only the last 9
- For SAL prefix: if fewer than 9 digits, left-pads to 9 digits
- For SAM prefix: uses last 5 digits, adds `100000`, converts to `SAL`, then pads to 9 digits
- Can be run multiple times safely - won't keep adding digits

### Migration Rule Hardening (2026-03-03)
- `migrate_docnum_sal.php` now enforces strict idempotence for `SAL-#########`:
  - if `SAL` numeric suffix is already exactly 9 digits, it is not changed.
- Migration output is now always capped at 9 numeric digits:
  - `SAL` with more than 9 digits keeps only the last 9.
  - `SAL` with fewer than 9 digits is left-padded to 9.
- `SAM` conversion remains `+100000`, but uses the last 5 digits as the SAM base before conversion to `SAL`.
- Re-running the migrate button repeatedly will not keep growing digits.

**Safety:**
- Uses database transactions (rollback on error)
- Temporarily disables `FOREIGN_KEY_CHECKS` during migration so parent/child `docnum` updates can be applied in one run, then restores it
- Saves original docnum to `old_docnum2` column in all 3 tables
- Only affects records where `trncde='SAL'` (for tranfile1/tranfile2) or all SAL/SAM records (for upld_salesfile)
- Idempotent: safe to run multiple times

**Tables Affected:**
- `tranfile1` - parent table (unique key: docnum)
- `tranfile2` - child table (foreign key: docnum Ã¢â€ â€™ tranfile1.docnum)
- `upld_salesfile` - related table (foreign key: docnum Ã¢â€ â€™ tranfile1.docnum)

**How to Run:**
1. **BACKUP DATABASE FIRST!**
2. **Step 1**: Click yellow "REVERT to Original 5-digit DOCNUM" button
   - This normalizes SAL/SAM docnum to 5-digit suffix format
   - SAL-000000001 -> SAL-00001
   - SAL-00000003452 -> SAL-03452
   - SAM-000117451 -> SAM-17451
3. **Step 2**: Click red "Migrate DOCNUM: SAL/SAM to SAL-9digit format" button
   - This converts to proper 9-digit format
   - SAL-00001 -> SAL-000000001
   - SAM-17451 -> SAL-000117451
4. Review output to verify transformations

### Revert Rule Update (2026-03-03)
- Yellow button (`REVERT to Original 5-digit DOCNUM`) now normalizes by suffix length.
- Keeps the existing prefix (`SAL` or `SAM`).
- If numeric suffix is more than 5 digits, keeps only the last 5 digits.
- If numeric suffix is exactly 5 digits, leaves it unchanged.
- If numeric suffix is less than 5 digits, pads with leading zeroes to 5 digits.
- Examples:
  - `SAL-000000001` -> `SAL-00001`
  - `SAL-00000003452` -> `SAL-03452`
  - `SAM-000117451` -> `SAM-17451`

**Revert Script:**
- File: `revert_to_original_docnum.php`
- Button: "REVERT to Original 5-digit DOCNUM" (yellow button in Fixes menu)
- Normalizes all SAL/SAM docnum values to a 5-digit suffix format
- Clears old_docnum2 column
- Prepares data for proper migration

**Column Added:**
- `old_docnum2` VARCHAR - stores original docnum before migration (cleared during revert)

---

*Last updated: 2026-03-14*


## Schema Compatibility Updates (2026-03-10)
- `trn_salesfile2_ajax.php`: removed all active `waybill_number` / `waybill_num1` checks because `tranfile1` in this deployment has no `waybill_number` column.
- `trn_salesfile2_ajax.php`: prevented empty `PDO_UpdateRecord` payload for `tranfile2` during save/edit flow to avoid SQL like `UPDATE tranfile2 SET WHERE recid = ?`.
- `partial_payment_ajax.php`: removed dependency on `tranfile1.is_partial_payment` column.

## Sales Commission Payment Save Fix (2026-03-12)
- `trn_salesfile2_ajax.php`: restored safe handling of `txt_com_pay` (initialize + sanitize posted value, numeric formatting, fallback compute from `sel_salesman_id` + transaction total).
- `trn_salesfile2_ajax.php`: during `tranfile1` total recalculation after line-item add/edit/delete, also updates `com_pay` when provided so commission payment is persisted consistently.
- `trn_salesfile2.php`: prevented unintended UI overwrite of saved/manual `txt_com_pay` by running `fetchSalesmanDetails()` only when `#txt_com_pay` is empty (on page load and after total refresh).

## Pager Template SQL Field Parsing Fix (2026-03-12)
- `pager/pager_ajax.pager.php`: fixed field-name parsing used to build dynamic `SELECT` lists for shared pager modules.
- Root cause: legacy `remove_xfields(..., "fname")` stripping could erase real columns containing `fname` (e.g., `fname`), producing invalid SQL like `SELECT ,lname,...`.
- Added explicit parser for field names in the format `fields[<column>_displayData][fname]`, with fallback to legacy parsing for compatibility.
- Query field-list builders now include only non-empty parsed fields and ensure `recid` is included once.

## Warehouse Master File (2026-03-14)
- Added new page: `mf_warehouse.php`.
- Implemented CRUD for `warehouse`:
  - Auto code field: `warehouse_id` (`WHS-0000001` seed)
  - Editable fields: `warehouse_name`, `location`
- Added separate page: `mf_warehouse_floor.php` for `warehouse_floor` CRUD:
  - Auto code field: `warehouse_floor_id` (`WHFID-0000001` seed)
  - Editable fields: `warehouse_id`, `floor_name`, `floor_no`
- `mf_warehouse.php` remains warehouse-only, with a row-level `Floors` action in the Action dropdown.
- `mf_warehouse_floor.php` is opened via selected warehouse and scoped by `warehouse_id` context.
- Warehouse-to-floor navigation uses `POST` and no longer passes `warehouse_id` in querystring.
- Floor CRUD no longer shows a warehouse selector; `warehouse_id` is enforced from selected warehouse context.
- Floor list hides `warehouse_floor_id` and searches only user-relevant floor fields.

## Pager Enhancements for Warehouse Flow (2026-03-14)
- Added optional fixed filter support in pager:
  - `table_filter_field`
  - `table_filter_value`
- Added row-placeholder support for custom action button functions in pager AJAX rendering.
  - Example placeholder usage: `{warehouse_id}` in custom button function strings.
- Escaped custom button hidden-input attributes in `pager/pager_main.class.php` (`btn-header`, `btn-color`, `btn-logo`, `btn-function`) to avoid raw broken-attribute text appearing under the pager.
- Fixed filter context is now passed to CRUD AJAX save calls as well, so inserts keep enforced parent context without needing visible parent dropdown fields.
- Added session-backed floor context hardening:
  - `mf_warehouse_floor.php` stores selected `warehouse_id` into `$_SESSION['warehouse_floor_context_id']`
  - `pager/pager_ajax.class.php` uses this session context as fallback during `warehouse_floor` insert when `warehouse_id` is absent from payload
  - if no context is available, insert is blocked with a clear validation message instead of a foreign key crash

## Warehouse Delete Cascade Fix (2026-03-14)
- `mf_warehouse.php` now uses pager delete confirmation (`alert_del = "Y"`) before warehouse deletion.
- `pager/pager_ajax.class.php` delete flow for `warehouse` now uses a transaction and deletes in this order:
  - `warehouse_stock_movement` rows joined through `warehouse_floor.floor_id`
  - `warehouse_floor` rows by `warehouse_id`
  - target `warehouse` row
- Added graceful delete-failure response (`status=0` JSON message) so FK-related issues no longer show raw PDO fatal output.
