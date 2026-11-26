<?php
require "includes/main_header.php";
?>
<link rel="stylesheet" href="css/trn_buyerpayments.css?v=<?php echo filemtime(__DIR__ . '/css/trn_buyerpayments.css'); ?>">

<table class='big_table'>
    <tr colspan=1>
        <td colspan=1 class='td_bl'>
            <?php require 'includes/main_menu.php'; ?>
        </td>

        <td colspan=1 class="td_br">
        <?php if ($view_crud != 1): ?>
            <div class="container-fluid mt-4"><div class="alert alert-warning">You do not have access to Shop Payments.</div></div>
        <?php else: ?>
            <div class="bp_page">
                <div class="bp_head">
                    <div>
                        <h2>Shop Payments</h2>
                        <div class="sub">Collections per RYU shop &middot; as of <b><?php echo date('m/d/Y'); ?></b></div>
                    </div>
                    <div class="actions">
                        <?php if ($add_crud == 1): ?>
                        <button type="button" class="btn btn-success fw-bold" id="bp_btn_add"><i class="fas fa-plus"></i> Add Payment</button>
                        <?php endif; ?>
                    </div>
                </div>

                <section class="bp_kpis" id="bp_kpis" aria-label="Collection summary"></section>

                <section class="bp_board">
                    <div class="bp_toolbar">
                        <div class="bp_search"><i class="fas fa-search" aria-hidden="true"></i>
                            <label for="bp_q" class="visually-hidden">Search shops</label>
                            <input type="search" id="bp_q" class="form-control" placeholder="Search shop name or ID" autocomplete="off">
                        </div>
                        <div class="bp_seg" role="group" aria-label="Filter by status" id="bp_seg"></div>
                        <label for="bp_sort" class="visually-hidden">Sort</label>
                        <select id="bp_sort" class="form-select bp_sort">
                            <option value="outstanding">Highest outstanding</option>
                            <option value="name">Shop name A-Z</option>
                            <option value="onhand">Most cheques on hand</option>
                        </select>
                    </div>
                    <div style="overflow-x:auto">
                        <table class="bp_tbl">
                            <thead><tr>
                                <th>Shop</th><th>Status</th><th class="r">Total Sales</th><th class="r">Collected</th>
                                <th class="r bp_hide_md">Chq on Hand</th><th class="r">Outstanding</th><th class="bp_hide_md">Collected %</th><th></th>
                            </tr></thead>
                            <tbody id="bp_rows"><tr><td colspan="8"><div class="bp_empty">Loading&hellip;</div></td></tr></tbody>
                        </table>
                    </div>

                </section>

                <!-- pagination: same markup as trn_purchasesorderfile1.php (styled by css/main.css via #pager / #previous_pager / #next_pager / #txt_pager_pageno) -->
                <nav aria-label='Page navigation' id='pager' style='font-size:14px'>
                    <ul class='pagination'>
                        <li class='page-item' onclick="bp_page('first_p')">
                            <span class='page-link' aria-label='Previous' style='display:flex;justify-content:center;align-items:center;width:60px;height:3em;width:3em'>
                                <span aria-hidden='true'>&laquo;</span>
                            </span>
                        </li>

                        <li class='page-item' onclick="bp_page('previous_p')">
                            <span class='page-link' id='previous_pager' style='display:flex;align-items:center;justify-content:center;height:3em;width:7em'>
                                Previous
                            </span>
                        </li>

                        <input type='text' style='width:60px;text-align:center;font-weight:bold' name='txt_pager_pageno' id='txt_pager_pageno' disabled>

                        <li class='page-item' style='height:3em;' onclick="bp_page('next_p')">
                            <span class='page-link' id='next_pager' style='display:flex;justify-content:center;align-items:center;height:3em;width:7em'>
                            Next
                            </span>
                        </li>

                        <li class='page-item' onclick="bp_page('last_p')">
                            <span class='page-link' aria-label='Next' style='display:flex;justify-content:center;align-items:center;height:3em;width:3em;'>
                                <span aria-hidden='true' >&raquo;</span>
                            </span>
                        </li>
                    </ul>
                </nav>
                <input type='hidden' name='txt_pager_totalrec' id='txt_pager_totalrec'>
                <input type='hidden' name='txt_pager_maxpage' id='txt_pager_maxpage'>
            </div>
        <?php endif; ?>
        </td>
    </tr>
</table>

<?php if ($view_crud == 1): ?>
<!-- Shop pop-up: balances + tabs (sales records / Payments) -->
<div class="modal fade" id="bp_bmodal" tabindex="-1" aria-labelledby="bp_b_title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="wrap-any">
                    <h5 class="modal-title fw-bold wrap-any" id="bp_b_title"></h5>
                    <div class="small muted"><span id="bp_b_acct"></span> <span id="bp_b_badge"></span></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bp_tiles" id="bp_b_tiles"></div>
                <ul class="nav nav-tabs bp_tabs mt-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="bp_tab_pays" data-bs-toggle="tab" data-bs-target="#bp_pane_pays" type="button" role="tab" aria-controls="bp_pane_pays" aria-selected="true">Payments <span class="badge bg-secondary" id="bp_b_paycount">0</span></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bp_tab_drs" data-bs-toggle="tab" data-bs-target="#bp_pane_drs" type="button" role="tab" aria-controls="bp_pane_drs" aria-selected="false">Sales records <span class="badge bg-secondary" id="bp_b_drcount">0</span></button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="bp_pane_pays" role="tabpanel" aria-labelledby="bp_tab_pays">
                        <div class="bp_dr_tools">
                            <div class="bp_seg" role="group" aria-label="Filter payments" id="bp_py_seg"></div>
                            <div class="bp_search"><i class="fas fa-search" aria-hidden="true"></i>
                                <label for="bp_py_q" class="visually-hidden">Search reference, bank, cheque no. or remarks</label>
                                <input type="search" id="bp_py_q" class="form-control" placeholder="Search ref, bank, cheque no. or remarks" autocomplete="off">
                            </div>
                        </div>
                        <div class="bp_py_list">
                            <table class="bp_tbl bp_py_tbl">
                                <thead><tr><th>Date</th><th>Method</th><th>Details</th><th class="r">Amount</th><th>Status</th><th class="r">Actions</th></tr></thead>
                                <tbody id="bp_b_pays"></tbody>
                                <tfoot id="bp_py_foot"></tfoot>
                            </table>
                        </div>
                        <nav aria-label="Payment pages" class="bp_pv_pager bp_py_pager mt-2">
                            <ul class="pagination">
                                <li class="page-item" data-py-page="first_p"><span class="page-link bp_end"><span aria-hidden="true">&laquo;</span><span class="visually-hidden">First page</span></span></li>
                                <li class="page-item" data-py-page="previous_p"><span class="page-link bp_prev">Previous</span></li>
                                <input type="text" class="bp_pgno" id="bp_py_pageno" aria-label="Payment page number" disabled>
                                <li class="page-item" data-py-page="next_p"><span class="page-link bp_next">Next</span></li>
                                <li class="page-item" data-py-page="last_p"><span class="page-link bp_end"><span aria-hidden="true">&raquo;</span><span class="visually-hidden">Last page</span></span></li>
                            </ul>
                        </nav>
                        <div class="small muted" id="bp_py_info"></div>
                    </div>
                    <div class="tab-pane fade" id="bp_pane_drs" role="tabpanel" aria-labelledby="bp_tab_drs">
                        <div class="bp_dr_tools">
                            <div class="bp_seg" role="group" aria-label="Show sales records" id="bp_dr_seg"></div>
                            <div class="bp_search"><i class="fas fa-search" aria-hidden="true"></i>
                                <label for="bp_dr_q" class="visually-hidden">Search DocNum or Order No.</label>
                                <input type="search" id="bp_dr_q" class="form-control" placeholder="Search DocNum or Order No." autocomplete="off">
                            </div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="bp_tbl bp_dr_tbl">
                                <thead><tr><th>Sales record</th><th>Date</th><th class="r">Amount</th><th>Status</th></tr></thead>
                                <tbody id="bp_b_drs"></tbody>
                                <tfoot id="bp_b_drfoot"></tfoot>
                            </table>
                        </div>
                        <!-- same look as the main pager (classes instead of ids, which must stay unique) -->
                        <nav aria-label="Sales record pages" class="bp_dr_pager mt-2">
                            <ul class="pagination">
                                <li class="page-item" data-dr-page="first_p"><span class="page-link" style="display:flex;justify-content:center;align-items:center;height:3em;width:3em"><span aria-hidden="true">&laquo;</span></span></li>
                                <li class="page-item" data-dr-page="previous_p"><span class="page-link bp_prev" style="display:flex;align-items:center;justify-content:center;height:3em;width:7em">Previous</span></li>
                                <input type="text" class="bp_pgno" style="width:60px;text-align:center;font-weight:bold" id="bp_dr_pageno" aria-label="Sales record page number" disabled>
                                <li class="page-item" style="height:3em;" data-dr-page="next_p"><span class="page-link bp_next" style="display:flex;justify-content:center;align-items:center;height:3em;width:7em">Next</span></li>
                                <li class="page-item" data-dr-page="last_p"><span class="page-link" style="display:flex;justify-content:center;align-items:center;height:3em;width:3em;"><span aria-hidden="true">&raquo;</span></span></li>
                            </ul>
                        </nav>
                        <div class="small muted" id="bp_dr_info"></div>
                        <div class="small muted mt-1"><i class="bi bi-info-circle"></i> Payments pay off the oldest sales records first.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($add_crud == 1): ?>
                <button type="button" class="btn btn-success fw-bold" id="bp_b_add"><i class="fas fa-plus"></i> Add Payment</button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    <!-- cheque status date (History tab). Lives inside the shop pop-up so Bootstrap's focus trap lets it be used. -->
    <div class="bp_dlg" id="bp_dlg" hidden>
        <div class="bp_dlg_box" role="dialog" aria-modal="true" aria-labelledby="bp_dlg_title">
            <h6 class="fw-bold mb-1" id="bp_dlg_title"></h6>
            <div class="small muted mb-3" id="bp_dlg_note"></div>
            <label class="form-label" for="bp_dlg_date" id="bp_dlg_lbl"></label>
            <input type="date" class="form-control" id="bp_dlg_date">
            <div class="text-danger small mt-1" id="bp_dlg_err" aria-live="polite"></div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" id="bp_dlg_cancel">Cancel</button>
                <button type="button" class="btn btn-success fw-bold" id="bp_dlg_save"><i class="fas fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add / edit payment -->
<div class="modal fade" id="bp_modal" tabindex="-1" aria-labelledby="bp_m_title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
        <form class="modal-content" id="bp_form" novalidate autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="bp_m_title">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="recid" id="bp_f_recid" value="0">
                <input type="hidden" name="cuscde" id="bp_f_shop_id">
                <div class="row g-4">
                    <div class="col-lg-5">
                <div class="mb-3" data-field="shop">
                    <label class="form-label" for="bp_f_shop">Shop <span class="req">*</span></label>
                    <div class="bp_combo">
                        <input type="text" class="form-control" id="bp_f_shop" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="bp_f_list" placeholder="Type to search RYU shops&hellip;">
                        <div class="bp_combo_list" id="bp_f_list" role="listbox" aria-label="Shops"></div>
                    </div>
                    <div id="bp_f_picked"></div>
                    <div class="ferr"></div>
                </div>
                <div class="row g-3">
                    <div class="col-12" data-field="method">
                        <label class="form-label" for="bp_f_method">Payment Method <span class="req">*</span></label>
                        <select class="form-select" name="method" id="bp_f_method">
                            <option value="CASH">Cash</option><option value="GCASH">GCash</option>
                            <option value="CHEQUE">Cheque</option><option value="BANK">Bank Transfer / Deposit</option>
                        </select>
                        <div class="ferr"></div>
                    </div>
                    <div class="col-6" data-field="pay_date">
                        <label class="form-label" for="bp_f_date">Payment Date <span class="req">*</span></label>
                        <input type="date" class="form-control" name="pay_date" id="bp_f_date" max="<?php echo date('Y-m-d'); ?>">
                        <div class="ferr"></div>
                    </div>
                    <div class="col-6" data-field="amount">
                        <label class="form-label" for="bp_f_amount">Amount (&#8369;) <span class="req">*</span></label>
                        <input type="text" inputmode="decimal" class="form-control amount_input" name="amount" id="bp_f_amount" placeholder="0.00">
                        <div class="ferr"></div>
                    </div>
                    <div class="col-12" data-field="reference_no" data-for="CASH GCASH BANK">
                        <label class="form-label" for="bp_f_ref"><span id="bp_f_ref_lbl">Reference No.</span> <span class="req" id="bp_f_ref_req">*</span></label>
                        <input type="text" class="form-control" name="reference_no" id="bp_f_ref" maxlength="50">
                        <div class="ferr"></div>
                    </div>
                    <div class="col-12 col-sm-6" data-field="bank_name" data-for="CHEQUE">
                        <label class="form-label" for="bp_f_bank">Bank Name <span class="req">*</span></label>
                        <input type="text" class="form-control" name="bank_name" id="bp_f_bank" maxlength="60" list="bp_banks">
                        <datalist id="bp_banks"><option value="BDO"><option value="BPI"><option value="Metrobank"><option value="Landbank"><option value="PNB"><option value="Security Bank"><option value="China Bank"><option value="RCBC"><option value="UnionBank"></datalist>
                        <div class="ferr"></div>
                    </div>
                    <div class="col-12 col-sm-6" data-field="cheque_no" data-for="CHEQUE">
                        <label class="form-label" for="bp_f_chqno">Cheque No. <span class="req">*</span></label>
                        <input type="text" class="form-control" name="cheque_no" id="bp_f_chqno" maxlength="30">
                        <div class="ferr"></div>
                    </div>
                    <div class="col-6" data-field="cheque_date" data-for="CHEQUE">
                        <label class="form-label" for="bp_f_chqdate">Cheque Date <span class="req">*</span></label>
                        <input type="date" class="form-control" name="cheque_date" id="bp_f_chqdate">
                        <div class="ferr"></div>
                    </div>
                    <div class="col-6" data-field="status" data-for="CHEQUE">
                        <label class="form-label" for="bp_f_status">Cheque Status <span class="req">*</span></label>
                        <select class="form-select" name="status" id="bp_f_status">
                            <option value="HOLD">Hold</option><option value="FOR_DEPOSIT">Date Deposited</option><option value="CLEARED">Cleared</option>
                            <option value="BOUNCED">Bounced</option><option value="CLOSED_ACCOUNT">Closed Account</option>
                        </select>
                        <div class="ferr"></div>
                    </div>
                    <!-- deposit / cleared / returned date: shown for every cheque status except Hold -->
                    <div class="col-6 bp_off" data-field="status_date" data-for="CHEQUE" id="bp_f_sd_wrap">
                        <label class="form-label" for="bp_f_sdate"><span id="bp_f_sd_lbl">Date</span> <span class="req">*</span></label>
                        <input type="date" class="form-control" name="status_date" id="bp_f_sdate" max="<?php echo date('Y-m-d'); ?>">
                        <div class="ferr"></div>
                    </div>
                    <div class="col-12" data-field="remarks">
                        <label class="form-label" for="bp_f_remarks">Remarks <span class="muted fw-normal">(optional)</span></label>
                        <textarea class="form-control" name="remarks" id="bp_f_remarks" rows="2" maxlength="200"></textarea>
                        <div class="ferr"></div>
                    </div>
                </div>
                        <div class="text-danger fw-bold mt-2" id="bp_f_msg"></div>
                    </div>
                    <div class="col-lg-7">
                        <div class="bp_preview" id="bp_preview">
                            <div class="bp_empty" id="bp_pv_empty"><i class="bi bi-shop"></i> Choose a shop to see its sales records.</div>
                            <div id="bp_pv_body" style="display:none">
                                <div class="bp_pv_head"><b>Sales records</b><span>Owed <b class="amt amt-due" id="bp_pv_owed"></b></span></div>
                                <div id="bp_pv_note" aria-live="polite"></div>
                                <div class="bp_pv_tools">
                                    <div class="bp_seg" role="group" aria-label="Filter sales records" id="bp_pv_seg"></div>
                                    <div class="bp_search"><i class="fas fa-search" aria-hidden="true"></i>
                                        <label for="bp_pv_q" class="visually-hidden">Search DocNum or Order No.</label>
                                        <input type="search" id="bp_pv_q" class="form-control" placeholder="Search DocNum or Order No." autocomplete="off">
                                    </div>
                                </div>
                                <div class="bp_pv_list">
                                    <table class="bp_tbl">
                                        <thead><tr><th>Sales record</th><th>Date</th><th class="r">Amount</th><th>Status</th></tr></thead>
                                        <tbody id="bp_pv_rows"></tbody>
                                        <tfoot id="bp_pv_foot"></tfoot>
                                    </table>
                                </div>
                                <nav aria-label="Sales record pages" class="bp_pv_pager mt-2">
                                    <ul class="pagination">
                                        <li class="page-item" data-pv-page="first_p"><span class="page-link bp_end"><span aria-hidden="true">&laquo;</span><span class="visually-hidden">First page</span></span></li>
                                        <li class="page-item" data-pv-page="previous_p"><span class="page-link bp_prev">Previous</span></li>
                                        <input type="text" class="bp_pgno" id="bp_pv_pageno" aria-label="Page number" disabled>
                                        <li class="page-item" data-pv-page="next_p"><span class="page-link bp_next">Next</span></li>
                                        <li class="page-item" data-pv-page="last_p"><span class="page-link bp_end"><span aria-hidden="true">&raquo;</span><span class="visually-hidden">Last page</span></span></li>
                                    </ul>
                                </nav>
                                <div class="small muted" id="bp_pv_info"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold" id="bp_f_save"><i class="fas fa-check"></i> Save Payment</button>
            </div>
        </form>
    </div>
</div>
<div class="bp_toasts" id="bp_toasts" role="status" aria-live="polite"></div>

<script>
var BP_PERM = { add: <?php echo (int)$add_crud; ?>, edit: <?php echo (int)$edit_crud; ?>, del: <?php echo (int)$delete_crud; ?> };

$(function () {
    var URL = 'trn_buyerpayments_ajax.php';
    var METHOD = { CASH: 'Cash', GCASH: 'GCash', CHEQUE: 'Cheque', BANK: 'Bank Transfer/Deposit' };
    var METHOD_ICON = { CASH: 'bi-cash-stack', GCASH: 'bi-phone', CHEQUE: 'bi-journal-text', BANK: 'bi-bank' };
    var STATUS = { RECEIVED: 'Received', HOLD: 'Hold', FOR_DEPOSIT: 'Date Deposited', CLEARED: 'Cleared', BOUNCED: 'Bounced', CLOSED_ACCOUNT: 'Closed Account' };
    var BADGE_ICON = { Paid: 'bi-check-circle-fill', Partial: 'bi-circle-half', Unpaid: 'bi-exclamation-circle-fill', RECEIVED: 'bi-check2',
        CLEARED: 'bi-check2-circle', HOLD: 'bi-pause-circle', FOR_DEPOSIT: 'bi-box-arrow-in-down', BOUNCED: 'bi-x-octagon', CLOSED_ACCOUNT: 'bi-slash-circle',
        paid: 'bi-check-circle-fill', partial: 'bi-circle-half', unpaid: 'bi-exclamation-circle', no_amount: 'bi-dash-circle' };
    var DR_LABEL = { paid: 'Paid', partial: 'Partly covered', unpaid: 'Unpaid', no_amount: 'No amount' };
    // cheque statuses that need a date (picked by the user: they may encode it late)
    var SD_FIELD = { FOR_DEPOSIT: 'Date Deposited', CLEARED: 'Date Cleared', BOUNCED: 'Date Returned', CLOSED_ACCOUNT: 'Date Returned' };
    function chqDates(p) { return { FOR_DEPOSIT: p.deposited_date, CLEARED: p.cleared_date, BOUNCED: p.returned_date, CLOSED_ACCOUNT: p.returned_date }; }
    var state = { search: '', status: 'All', sort: 'outstanding', current: null, pickedShop: null, dr: { filter: 'auto', q: '' }, py: { filter: 'All', q: '' } };

    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function amt(n) { return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function peso(n) { return (n < 0 ? '-' : '') + '₱' + amt(Math.abs(n)); }
    function mdy(iso) { return iso ? iso.slice(5, 7) + '/' + iso.slice(8, 10) + '/' + iso.slice(0, 4) : '-'; }
    function today() { var d = new Date(); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
    function badge(key, label) { return '<span class="bp_badge s-' + esc(key) + '"><i class="bi ' + (BADGE_ICON[key] || 'bi-dot') + '" aria-hidden="true"></i>' + esc(label || key) + '</span>'; }
    function post(data) { return $.ajax({ url: URL, type: 'post', dataType: 'json', data: data }); }
    function toast(msg, err) {
        var el = $('<div class="bp_toast"></div>').toggleClass('err', !!err).text(msg);
        $('#bp_toasts').append(el);
        setTimeout(function () { el.css('opacity', 0); setTimeout(function () { el.remove(); }, 250); }, 3500);
    }
    function netFail() { toast('Could not reach the server. Please check your connection and try again.', true); }

    // Bootstrap 5.0 ignores show() during a hide transition; queue it instead
    function bsSafe(el, inst, kind) {
        var hiding = false, pending = null;
        el.addEventListener('hide.bs.' + kind, function () { hiding = true; });
        el.addEventListener('hidden.bs.' + kind, function () { hiding = false; if (pending) { var f = pending; pending = null; f(); } });
        return { show: function (before) { var go = function () { if (before) before(); inst.show(); }; if (hiding) pending = go; else go(); },
                 hide: function () { pending = null; inst.hide(); } };
    }
    var shopEl = document.getElementById('bp_bmodal'), modalEl = document.getElementById('bp_modal');
    var bmodal = bsSafe(shopEl, bootstrap.Modal.getOrCreateInstance(shopEl), 'modal');
    var modal = bsSafe(modalEl, bootstrap.Modal.getOrCreateInstance(modalEl), 'modal');

    /* ------------------------------------------------ list */
    window.bp_page = function (pageAction) {
        post({ event_action: 'list', search: state.search, status: state.status, sort: state.sort,
               pageno: $('#txt_pager_pageno').val() || 1, page_action: pageAction }).done(function (r) {
            if (!r.status) { toast(r.msg, true); return; }
            $('#txt_pager_pageno').val(r.maxpage ? r.xpageno : 0);
            $('#txt_pager_totalrec').val(r.totalrec);
            $('#txt_pager_maxpage').val(r.maxpage);
            if (r.totals) renderTotals(r.totals);
            renderRows(r.rows);
        }).fail(netFail);
    };

    function renderTotals(t) {
        var k = function (cls, icon, label, v, sub) {
            return '<div class="bp_kpi ' + cls + '"><div class="k"><i class="fas ' + icon + '" aria-hidden="true"></i>' + label + '</div>' +
                '<div class="body"><div class="v amt">' + peso(v) + '</div><div class="s">' + sub + '</div></div></div>';
        };
        $('#bp_kpis').html(
            k('k-sales', 'fa-receipt', 'Total Sales', t.sales, t.shops + ' RYU shop' + (t.shops === 1 ? '' : 's')) +
            k('k-col', 'fa-check-circle', 'Collected', t.collected, 'Cash, GCash, bank, cleared cheques') +
            k('k-hand', 'fa-hourglass-half', 'Cheques on Hand', t.on_hand, 'Hold / Date Deposited, not yet collected') +
            k('k-ret', 'fa-undo', 'Returned Cheques', t.returned, 'Bounced / Closed Account, still owed') +
            // Outstanding: two equal amounts, holding cheques (Hold / Date Deposited) as unpaid on top, as paid below
            '<div class="bp_kpi k-out"><div class="k"><i class="fas fa-exclamation-circle" aria-hidden="true"></i>Outstanding</div>' +
                '<div class="body">' +
                    '<div class="v amt">' + peso(t.outstanding) + '</div><div class="s">Includes: holding cheques (as unpaid)</div>' +
                    '<hr class="bp_kpi_sep">' +
                    '<div class="v amt">' + peso(t.net) + '</div><div class="s">Includes: holding cheques (as paid)</div>' +
                '</div>' +
            '</div>');
        var counts = { All: t.shops, Paid: t.paid, Partial: t.partial, Unpaid: t.unpaid };
        $('#bp_seg').html(['All', 'Paid', 'Partial', 'Unpaid'].map(function (s) {
            return '<button type="button" data-status="' + s + '" aria-pressed="' + (state.status === s) + '">' + s + '<span class="cnt">' + counts[s] + '</span></button>';
        }).join(''));
    }

    function renderRows(rows) {
        if (!rows.length) {
            $('#bp_rows').html('<tr><td colspan="8"><div class="bp_empty"><i class="fas fa-search"></i> No shops match' + (state.search ? ' "' + esc(state.search) + '"' : ' this filter') + '.</div></td></tr>');
            return;
        }
        $('#bp_rows').html(rows.map(function (b) {
            var pct = b.sales > 0 ? Math.max(0, Math.min(100, Math.round(b.collected * 100 / b.sales))) : 100;
            var due = Math.max(0, b.outstanding);
            return '<tr data-id="' + esc(b.cuscde) + '">' +
                '<td class="c_main"><div class="bp_name wrap-any">' + esc(b.cusdsc) + '</div><div class="bp_acct">' + esc(b.cuscde) + ' · ' + b.dr_count + ' record' + (b.dr_count === 1 ? '' : 's') + (b.last_paid ? ' · last paid ' + mdy(b.last_paid) : '') + '</div></td>' +
                '<td class="c_status" data-label="Status">' + badge(b.status, b.status) + (b.outstanding < -0.004 ? ' ' + badge('credit', 'Credit ' + peso(-b.outstanding)) : '') + '</td>' +
                '<td class="r amt" data-label="Total Sales">' + peso(b.sales) + '</td>' +
                '<td class="r amt amt-col" data-label="Collected">' + peso(b.collected) + '</td>' +
                '<td class="r amt bp_hide_md ' + (b.on_hand ? 'amt-hand' : 'muted') + '" data-label="Chq on Hand">' + (b.on_hand ? peso(b.on_hand) : '—') + '</td>' +
                '<td class="r amt fw-bold ' + (due > 0 ? 'amt-due' : '') + '" data-label="Outstanding">' + peso(due) + '</td>' +
                '<td class="bp_hide_md" data-label="Collected %"><div class="bp_meter" aria-hidden="true"><span style="width:' + pct + '%"></span></div><small class="muted">' + pct + '%</small></td>' +
                '<td class="r c_act"><div class="bp_row_acts">' + (BP_PERM.add ? '<button type="button" class="btn btn-sm btn-outline-success fw-bold" data-pay="' + esc(b.cuscde) + '"><i class="fas fa-plus"></i> Pay</button>' : '') +
                '<button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-hist="' + esc(b.cuscde) + '"><i class="bi bi-clock-history"></i> History</button></div></td></tr>';
        }).join(''));
    }

    var qTimer;
    $('#bp_q').on('input', function () { var v = this.value; clearTimeout(qTimer); qTimer = setTimeout(function () { state.search = $.trim(v); bp_page('search'); }, 250); });
    $('#bp_sort').on('change', function () { state.sort = this.value; bp_page('first_p'); });
    $('#bp_seg').on('click', '[data-status]', function () {
        var s = $(this).data('status');
        state.status = (state.status === s && s !== 'All') ? 'All' : s;
        bp_page('search');
    });
    $('#bp_rows').on('click', '[data-pay]', function (e) { e.stopPropagation(); openPayment($(this).data('pay'), 0); })
                 .on('click', '[data-hist]', function (e) { e.stopPropagation(); openShop($(this).data('hist')); });

    /* ------------------------------------------------ shop pop-up (balances + sales records / Payments tabs) */
    function openShop(id) {
        loadShop(id, function () {
            bootstrap.Tab.getOrCreateInstance(document.getElementById('bp_tab_pays')).show();
            state.dr = { filter: 'auto', q: '' };
            state.py = { filter: 'All', q: '' };
            $('#bp_dr_q, #bp_py_q').val('');
            loadPays('first_p');
            loadDrs('first_p');
            bmodal.show();
        });
    }

    /* Payments tab: 10 per page from the server, newest first, filter + search */
    function loadPays(pageAction) {
        var id = state.current, seq = state.pySeq = (state.pySeq || 0) + 1;
        post({ event_action: 'payments', cuscde: id, filter: state.py.filter, q: state.py.q,
               pageno: $('#bp_py_pageno').val() || 1, page_action: pageAction }).done(function (r) {
            if (!r.status || seq !== state.pySeq) return;
            state.py.filter = r.filter;
            $('#bp_py_seg').html(['All', 'Collected', 'On hand', 'Returned'].map(function (f) {
                return '<button type="button" data-pyf="' + f + '" aria-pressed="' + (r.filter === f) + '">' + f + '<span class="cnt">' + r.counts[f] + '</span></button>';
            }).join(''));
            $('#bp_b_pays').html(r.rows.length ? r.rows.map(payRow).join('') :
                '<tr><td colspan="6"><div class="bp_empty">' + (r.counts.All ? 'No payments match.' : 'No payments recorded yet.') + '</div></td></tr>');
            $('#bp_py_foot').html(r.totalrec ? '<tr><td colspan="3" class="r">TOTAL (' + r.totalrec + ' payment' + (r.totalrec === 1 ? '' : 's') + ')</td><td class="r amt">' + amt(r.total_amount) + '</td><td colspan="2"></td></tr>' : '');
            $('#bp_py_pageno').val(r.maxpage ? r.xpageno : 0);
            $('.bp_py_pager').toggle(r.maxpage > 1);
            $('#bp_py_info').text(r.totalrec ? 'Showing ' + (r.offset + 1) + '-' + (r.offset + r.rows.length) + ' of ' + r.totalrec + ', newest first.' : '');
        }).fail(netFail);
    }
    function payRow(p) {
        var isChq = p.method === 'CHEQUE', st = p.status;
        var amtCls = (st === 'HOLD' || st === 'FOR_DEPOSIT') ? 'amt-hand' : (st === 'BOUNCED' || st === 'CLOSED_ACCOUNT') ? 'amt-ret' : '';
        var detail = isChq ? esc(p.bank_name) + ' · Chq #' + esc(p.cheque_no) + ' · dated ' + mdy(p.cheque_date) : (p.reference_no ? 'Ref ' + esc(p.reference_no) : '—');
        var status = badge(st, STATUS[st]);
        if (isChq && BP_PERM.edit) {
            status = '<label class="visually-hidden" for="bp_cs_' + p.recid + '">Cheque status</label><select class="form-select form-select-sm" id="bp_cs_' + p.recid + '" data-chq="' + p.recid + '" data-was="' + esc(st) + '"' +
                ' data-dates="' + esc(JSON.stringify(chqDates(p))) + '">' +
                ['HOLD', 'FOR_DEPOSIT', 'CLEARED', 'BOUNCED', 'CLOSED_ACCOUNT'].map(function (k) { return '<option value="' + k + '"' + (k === st ? ' selected' : '') + '>' + STATUS[k] + '</option>'; }).join('') + '</select>';
        }
        var sd = isChq ? chqDates(p)[st] : '';
        if (sd) status += '<div class="small muted mt-1">' + SD_FIELD[st] + ': ' + mdy(sd) + '</div>';
        var acts = (BP_PERM.edit ? '<button type="button" class="btn btn-sm btn-outline-dark" data-edit="' + p.recid + '"><i class="fas fa-pencil-alt"></i> Edit</button>' : '') +
                   (BP_PERM.del ? '<button type="button" class="btn btn-sm btn-outline-danger" data-del="' + p.recid + '"><i class="fas fa-trash-alt"></i> Delete</button>' : '');
        return '<tr><td class="py_date text-nowrap" data-label="Date">' + mdy(p.pay_date) + '</td>' +
            '<td class="py_method text-nowrap"><i class="bi ' + METHOD_ICON[p.method] + '" aria-hidden="true"></i> <b>' + esc(METHOD[p.method]) + '</b></td>' +
            '<td class="py_detail wrap-any">' + detail + (p.remarks ? '<div class="muted small wrap-any">' + esc(p.remarks) + '</div>' : '') + '</td>' +
            '<td class="py_amt r amt fw-bold ' + amtCls + '" data-label="Amount">' + peso(p.amount) + '</td>' +
            '<td class="py_status">' + status + '</td>' +
            '<td class="py_acts r">' + (acts ? '<div class="bp_row_acts">' + acts + '</div>' : '') + '</td></tr>';
    }
    $('#bp_py_seg').on('click', '[data-pyf]', function () { state.py.filter = $(this).data('pyf'); loadPays('search'); });
    var pyTimer;
    $('#bp_py_q').on('input', function () { var v = this.value; clearTimeout(pyTimer); pyTimer = setTimeout(function () { state.py.q = $.trim(v); loadPays('search'); }, 250); })
                 .on('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
    $('.bp_py_pager').on('click', '[data-py-page]', function () { loadPays($(this).data('py-page')); });

    /* DR tab: 20 per page from the server, filter + search */
    function loadDrs(pageAction) {
        var id = state.current, seq = state.drSeq = (state.drSeq || 0) + 1;
        post({ event_action: 'drs', cuscde: id, filter: state.dr.filter, q: state.dr.q,
               pageno: $('#bp_dr_pageno').val() || 1, page_action: pageAction }).done(function (r) {
            if (!r.status || seq !== state.drSeq) return;
            state.dr.filter = r.filter;
            $('#bp_dr_seg').html(['All', 'Unpaid', 'Paid'].map(function (f) {
                return '<button type="button" data-drf="' + f + '" aria-pressed="' + (r.filter === f) + '">' + f + '<span class="cnt">' + r.counts[f] + '</span></button>';
            }).join(''));
            $('#bp_b_drs').html(r.rows.length ? r.rows.map(function (d) {
                var label = d.state === 'partial' ? peso(d.applied) + ' paid' : DR_LABEL[d.state];
                var order = d.ordernum && d.ordernum !== d.docnum ? '<div class="muted small">' + esc(d.ordernum) + '</div>' : '';
                var cls = d.state === 'no_amount' ? '' : 'pv-' + d.state;      // pv-paid (green) / pv-partial (yellow) / pv-unpaid (red)
                return '<tr class="' + cls + '"><td class="pv_doc wrap-any"><b>' + esc(d.docnum) + '</b>' + order + '</td><td class="text-nowrap" data-label="Date">' + mdy(d.trndte) + '</td>' +
                    '<td class="r amt" data-label="Amount">' + amt(d.trntot) + '</td><td class="pv_st">' + badge(d.state, label) + '</td></tr>';
            }).join('') : '<tr><td colspan="4"><div class="bp_empty">' + (r.counts.All ? 'No sales records match.' : 'No sales records for this shop yet.') + '</div></td></tr>');
            $('#bp_b_drfoot').html(r.totalrec ? '<tr><td colspan="2" class="r fw-bold">TOTAL (' + r.totalrec + ' record' + (r.totalrec === 1 ? '' : 's') + ')</td><td class="r amt fw-bold">' + amt(r.total_amount) + '</td><td></td></tr>' : '');
            $('#bp_dr_pageno').val(r.maxpage ? r.xpageno : 0);
            var from = r.totalrec ? (r.xpageno - 1) * 20 + 1 : 0, to = Math.min(r.totalrec, r.xpageno * 20);
            $('#bp_dr_info').text(r.totalrec ? 'Showing ' + from + '-' + to + ' of ' + r.totalrec + ' ' + (r.filter === 'All' ? '' : r.filter.toLowerCase() + ' ') + 'records' : '');
            $('.bp_dr_pager').toggle(r.maxpage > 1);
        }).fail(netFail);
    }
    $('#bp_dr_seg').on('click', '[data-drf]', function () { state.dr.filter = $(this).data('drf'); loadDrs('search'); });
    var drTimer;
    $('#bp_dr_q').on('input', function () { var v = this.value; clearTimeout(drTimer); drTimer = setTimeout(function () { state.dr.q = $.trim(v); loadDrs('search'); }, 250); });
    $('.bp_dr_pager').on('click', '[data-dr-page]', function () { loadDrs($(this).data('dr-page')); });
    function loadShop(id, after) {
        post({ event_action: 'customer', cuscde: id }).done(function (r) {
            if (!r.status) { toast(r.msg, true); return; }
            state.current = id;
            renderShop(r);
            if (after) after();
        }).fail(netFail);
    }

    function renderShop(r) {
        var s = r.summary;
        $('#bp_b_title').text(r.cusdsc);
        $('#bp_b_acct').text(r.cuscde);
        $('#bp_b_badge').html(badge(s.status, s.status) + (s.credit > 0 ? ' ' + badge('credit', 'Credit ' + peso(s.credit)) : ''));
        var tile = function (k, v, cls, sub) {
            return '<div class="bp_tile"><div class="k">' + k + '</div><div class="v amt ' + (cls || '') + '">' + peso(v) + '</div>' + (sub ? '<div class="small muted">' + sub + '</div>' : '') + '</div>';
        };
        $('#bp_b_tiles').html(
            tile('Total Sales', s.sales) +
            tile('Collected', s.collected, 'amt-col') +
            tile('Outstanding', Math.max(0, s.outstanding), s.outstanding > 0 ? 'amt-due' : '') +
            tile('Cheques on Hand (' + s.on_hand_count + ')', s.on_hand, 'amt-hand') +
            tile('Returned Cheques (' + s.returned_count + ')', s.returned, s.returned ? 'amt-due' : '', 'Still owed'));

        $('#bp_b_drcount').text(s.dr_count);

        $('#bp_b_paycount').text(r.payment_count);     // the Payments tab itself is loaded page by page by loadPays()
    }

    // cheque status change: Hold saves at once; Date Deposited / Cleared / Bounced / Closed Account ask for the date first
    var dlg = { sel: null };
    function setStatus(sel, to, date, onFail) {
        return post({ event_action: 'set_status', recid: sel.data('chq'), status: to, status_date: date || '' }).done(function (r) {
            if (!r.status) { onFail(r.msg); return; }
            closeDlg(true);
            toast('Cheque is now ' + STATUS[to] + (date ? ' (' + mdy(date) + ')' : '') + '.');
            refreshAfterChange();
        }).fail(function () { onFail('Could not reach the server. Please try again.'); });
    }
    function closeDlg(saved) {
        if (dlg.sel && !saved) dlg.sel.val(dlg.sel.data('was'));     // cancelled: dropdown back to the old status
        dlg.sel = null;
        $('#bp_dlg').prop('hidden', true);
    }
    $('#bp_b_pays').on('change', '[data-chq]', function () {
        var sel = $(this), to = sel.val(), was = sel.data('was');
        if (to === was) return;
        if (!SD_FIELD[to]) { setStatus(sel, to, '', function (msg) { toast(msg, true); sel.val(was); }); return; }
        var t = today(), saved = (sel.data('dates') || {})[to];
        dlg.sel = sel;
        $('#bp_dlg_title').text('Cheque status: ' + STATUS[to]);
        $('#bp_dlg_note').text(to === 'BOUNCED' || to === 'CLOSED_ACCOUNT'
            ? 'It will not count as collected, and any sales records it paid will go back to unpaid.'
            : to === 'CLEARED' ? 'It will count as collected and pay the oldest unpaid sales records.' : 'Still on hand until it clears.');
        $('#bp_dlg_lbl').text(SD_FIELD[to]);
        $('#bp_dlg_date').attr('max', t).val(saved || t);
        $('#bp_dlg_err').text('');
        $('#bp_dlg').prop('hidden', false);
        $('#bp_dlg_date').trigger('focus');
    });
    $('#bp_dlg_save').on('click', function () {
        if (!dlg.sel) return;
        var btn = $(this).prop('disabled', true);
        setStatus(dlg.sel, dlg.sel.val(), $('#bp_dlg_date').val(), function (msg) { $('#bp_dlg_err').text(msg); })
            .always(function () { btn.prop('disabled', false); });
    });
    $('#bp_dlg_cancel').on('click', function () { closeDlg(false); });
    // Escape closes only this small box, not the shop pop-up behind it
    $('#bp_dlg').on('keydown', function (e) { if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); closeDlg(false); } });
    shopEl.addEventListener('hide.bs.modal', function () { if (dlg.sel) closeDlg(false); });

    $('#bp_b_pays').on('click', '[data-edit]', function () {
        state.backToShop = true;
        var recid = $(this).data('edit');
        bmodal.hide();
        openPayment(null, recid);
    }).on('click', '[data-del]', function () {
        if (!confirm('Delete this payment? Sales records it paid will go back to unpaid. This cannot be undone.')) return;
        post({ event_action: 'delete', recid: $(this).data('del') }).done(function (r) {
            if (!r.status) { toast(r.msg, true); return; }
            toast('Payment deleted.');
            refreshAfterChange();
        }).fail(netFail);
    });
    $('#bp_b_add').on('click', function () { state.backToShop = true; var id = state.current; bmodal.hide(); openPayment(id, 0); });

    // payment form opened from the shop pop-up: return to it (refreshed) when the form closes
    modalEl.addEventListener('hidden.bs.modal', function () {
        if (!state.backToShop) return;
        state.backToShop = false;
        loadShop(state.current, function () { loadPays('same'); loadDrs('same'); bmodal.show(); });
    });

    function refreshAfterChange() {
        bp_page('same');
        if (state.current && shopEl.classList.contains('show')) { loadShop(state.current); loadPays('same'); loadDrs('same'); }
    }

    /* ------------------------------------------------ payment form */
    var form = document.getElementById('bp_form');
    function setMethod(m) {
        $(modalEl).removeClass('m-CASH m-GCASH m-CHEQUE m-BANK').addClass('m-' + m);
        $('#bp_f_ref_lbl').text(m === 'GCASH' ? 'GCash Reference No.' : m === 'BANK' ? 'Bank Reference / Deposit Slip No.' : 'Official Receipt / Reference No.');
        $('#bp_f_ref_req').toggle(m !== 'CASH');
    }
    // cheque status date: hidden for Hold; label follows the status; keeps the saved date when editing, else today
    var formDates = {};
    function syncStatusDate() {
        var st = $('#bp_f_status').val(), need = !!SD_FIELD[st];
        $('#bp_f_sd_wrap').toggleClass('bp_off', !need);
        $('#bp_f_sdate').prop('disabled', !need);
        if (!need) return;
        $('#bp_f_sd_lbl').text(SD_FIELD[st]);
        $('#bp_f_sdate').val(formDates[st] || today());
    }
    $('#bp_f_status').on('change', syncStatusDate);
    function clearErrors() { $(form).find('.has-err').removeClass('has-err'); $(form).find('[aria-invalid]').removeAttr('aria-invalid'); $('#bp_f_msg').text(''); }
    function showErrors(errors) {
        clearErrors();
        var first = null;
        $.each(errors, function (field, msg) {
            var wrap = $(form).find('[data-field="' + field + '"]');
            wrap.addClass('has-err').find('.ferr').text(msg);
            var input = wrap.find('input:not([type=hidden]),select,textarea').first().attr('aria-invalid', 'true');
            if (!first) first = input;
        });
        if (first) first.trigger('focus');
    }
    function pickShop(id, name) {
        state.pickedShop = id;
        $('#bp_f_shop_id').val(id || '');
        $('#bp_f_shop').val(name || '');
        closeCombo();
        $('#bp_f_picked').empty();
        $(form).find('[data-field="shop"]').removeClass('has-err');
        state.pv = { filter: 'auto', q: '', page: 1 };     // new shop: start again on Unpaid, no search
        $('#bp_pv_q').val('');
        if (!id) { updatePreview(); return; }
        post({ event_action: 'customer', cuscde: id }).done(function (r) {
            if (!r.status || state.pickedShop !== id) return;
            var s = r.summary;
            $('#bp_f_picked').html('<div class="bp_picked"><span class="wrap-any"><b>' + esc(id) + '</b> ' + badge(s.status, s.status) + '</span><span>Outstanding <b class="amt amt-due">' + peso(Math.max(0, s.outstanding)) + '</b></span></div>');
        });
        updatePreview();
    }

    window.openPayment = openPayment;
    function openPayment(shopId, recid) {
        if (recid && !BP_PERM.edit) return;
        if (!recid && !BP_PERM.add) return;
        modal.show(function () {
            form.reset();
            clearErrors();
            $('#bp_f_recid').val(recid || 0);
            $('#bp_f_date').val(today());
            $('#bp_f_chqdate').val(today());
            $('#bp_f_method').val('CASH');
            setMethod('CASH');
            formDates = {};
            syncStatusDate();
            $('#bp_f_shop').prop('disabled', !!recid);
            $('#bp_m_title').text(recid ? 'Edit Payment' : 'Add Payment');
            state.pickedShop = null;
            if (recid) {
                post({ event_action: 'get_payment', recid: recid }).done(function (r) {
                    if (!r.status) { toast(r.msg, true); modal.hide(); return; }
                    var p = r.payment;
                    pickShop(p.cuscde, r.cusdsc);
                    $('#bp_f_method').val(p.method); setMethod(p.method);
                    $('#bp_f_date').val(p.pay_date);
                    $('#bp_f_amount').val(amt(p.amount));
                    $('#bp_f_ref').val(p.reference_no);
                    $('#bp_f_bank').val(p.bank_name);
                    $('#bp_f_chqno').val(p.cheque_no);
                    $('#bp_f_chqdate').val(p.cheque_date);
                    if (p.method === 'CHEQUE') { $('#bp_f_status').val(p.status); formDates = chqDates(p); syncStatusDate(); }
                    $('#bp_f_remarks').val(p.remarks);
                    updatePreview();
                }).fail(netFail);
            } else {
                pickShop(shopId || null, '');
                if (shopId) post({ event_action: 'customer', cuscde: shopId }).done(function (r) { if (r.status) $('#bp_f_shop').val(r.cusdsc); });
            }
        });
    }
    $('#bp_btn_add').on('click', function () { openPayment(null, 0); });
    modalEl.addEventListener('shown.bs.modal', function () { ($('#bp_f_shop_id').val() ? $('#bp_f_amount') : $('#bp_f_shop')).trigger('focus'); });

    $('#bp_f_method').on('change', function () { setMethod(this.value); clearErrors(); updatePreview(); });
    $('#bp_f_amount').on('blur', function () {
        var v = this.value.replace(/[\s,₱]/g, '');
        if (/^\d{1,9}(\.\d{1,2})?$/.test(v) && Number(v) > 0) this.value = amt(Number(v));
    });
    $(form).on('input change', '#bp_f_amount, #bp_f_status, #bp_f_date', function () { updatePreview(); });

    // sales records of the picked shop: All / Unpaid / Paid, search by DocNum or Order No., 10 per page.
    // With an amount, highlights the records this payment will pay (view only).
    var pvTimer, pvSeq = 0;
    function plural(n, word) { return n + ' ' + word + (n === 1 ? '' : 's'); }
    // partly paid record: amount − paid = balance left
    function balanceMath(total, paid) {
        return '<div class="small muted mt-1 text-nowrap">' + peso(total) + ' − ' + peso(paid) + ' = <b class="amt-due">' + peso(Math.round((total - paid) * 100) / 100) + ' left</b></div>';
    }
    function updatePreview(pageAction) {
        pageAction = pageAction || 'first_p';     // any form change goes back to page 1 (the records a payment pays first)
        if (!state.pv) state.pv = { filter: 'auto', q: '', page: 1 };
        clearTimeout(pvTimer);
        pvTimer = setTimeout(function () {
            var id = $('#bp_f_shop_id').val();
            $('#bp_pv_empty').toggle(!id);
            $('#bp_pv_body').toggle(!!id);
            if (!id) return;
            var seq = ++pvSeq, method = $('#bp_f_method').val(), st = $('#bp_f_status').val();
            post({ event_action: 'preview', cuscde: id, recid: $('#bp_f_recid').val(), amount: $('#bp_f_amount').val(), method: method, status: st, pay_date: $('#bp_f_date').val(),
                   pv_pageno: state.pv.page, pv_page_action: pageAction, pv_filter: state.pv.filter, pv_q: state.pv.q })
                .done(function (r) {
                    if (seq !== pvSeq || !r.status) return;
                    state.pv.page = r.xpageno;
                    state.pv.filter = r.filter;
                    var counts = r.has_amount && !(method === 'CHEQUE' && st !== 'CLEARED');   // money that pays records now
                    $('#bp_pv_owed').text(peso(r.open_owed));

                    var note;
                    if (!r.has_amount) note = '<i class="bi bi-info-circle"></i> Enter an amount to see which unpaid records it will pay (oldest first).';
                    else if (!counts) note = /BOUNCED|CLOSED/.test(st) ? '<i class="bi bi-exclamation-triangle"></i> A returned cheque does <b>not</b> pay any sales record.'
                                                                       : '<i class="bi bi-hourglass-split"></i> Cheque on hand: <b>no sales record will be marked paid until it is Cleared.</b>';
                    else {
                        var willPay = r.changes.filter(function (c) { return c.after === 'paid' && c.state !== 'paid'; }).length;
                        note = '<i class="bi bi-check-circle"></i> This payment will fully pay <b>' + plural(willPay, 'sales record') + '</b> (highlighted).' +
                               (r.credit_after > r.credit_before + 0.004 ? ' ' + peso(r.credit_after) + ' will be kept as credit.' : '');
                    }
                    $('#bp_pv_note').html('<div class="bp_pv_note' + (counts ? ' ok' : '') + '">' + note + '</div>');

                    $('#bp_pv_seg').html(['All', 'Unpaid', 'Paid'].map(function (f) {
                        return '<button type="button" data-pvf="' + f + '" aria-pressed="' + (r.filter === f) + '">' + f + '<span class="cnt">' + r.counts[f] + '</span></button>';
                    }).join(''));

                    $('#bp_pv_rows').html(r.rows.length ? r.rows.map(function (c) {
                        var tag;
                        // a record already partly paid by earlier payments needs only the rest: say how much was already paid
                        if (counts && c.state !== 'paid' && c.after === 'paid') tag = '<span class="bp_badge s-paid"><i class="bi bi-check-circle-fill"></i>Will be paid</span>' +
                            (c.state === 'partial' ? '<div class="small muted mt-1">' + peso(c.applied_before) + ' already paid, needs ' + peso(c.trntot - c.applied_before) + '</div>' : '');
                        else if (counts && c.after === 'partial' && Math.abs(c.applied - c.applied_before) > 0.004) tag = '<span class="bp_badge s-partial"><i class="bi bi-circle-half"></i>' + peso(c.applied) + ' paid after</span>' + balanceMath(c.trntot, c.applied);
                        else if (c.state === 'partial') tag = badge('partial', peso(c.applied_before) + ' paid') + balanceMath(c.trntot, c.applied_before);
                        else tag = badge(c.state, DR_LABEL[c.state]);
                        // row colour = status after this payment (current status when there is no amount / it doesn't pay yet)
                        var shown = counts ? c.after : c.state;
                        var cls = shown === 'no_amount' ? '' : 'pv-' + shown;     // pv-paid (green) / pv-partial (yellow) / pv-unpaid (red)
                        var order = c.ordernum && c.ordernum !== c.docnum ? '<div class="muted small">' + esc(c.ordernum) + '</div>' : '';
                        return '<tr class="' + cls + '"><td class="pv_doc wrap-any"><b>' + esc(c.docnum) + '</b>' + order + '</td>' +
                            '<td class="text-nowrap" data-label="Date">' + mdy(c.trndte) + '</td><td class="r amt" data-label="Amount">' + amt(c.trntot) + '</td>' +
                            '<td class="pv_st">' + tag + '</td></tr>';
                    }).join('') : '<tr><td colspan="4"><div class="bp_empty">' +
                        (!r.counts.All ? 'No sales records for this shop yet.' : 'No sales records match.') +
                        (r.has_amount && !r.open_count ? ' The whole amount will be kept as credit.' : '') + '</div></td></tr>');
                    $('#bp_pv_foot').html(r.totalrec ? '<tr><td colspan="2" class="r">TOTAL (' + plural(r.totalrec, 'record') + ')</td><td class="r amt">' + amt(r.total_amount) + '</td><td></td></tr>' : '');

                    $('#bp_pv_pageno').val(r.maxpage ? r.xpageno : 0);
                    $('.bp_pv_pager').toggle(r.maxpage > 1);
                    $('#bp_pv_info').text(r.totalrec ? 'Showing ' + (r.offset + 1) + '-' + (r.offset + r.rows.length) + ' of ' + r.totalrec + ', oldest first.' : '');
                });
        }, 250);
    }
    $('#bp_preview').on('click', '[data-pv-page]', function () { updatePreview($(this).data('pv-page')); });
    $('#bp_preview').on('click', '[data-pvf]', function () { state.pv.filter = $(this).data('pvf'); updatePreview('search'); });
    $('#bp_pv_q').on('input', function () { state.pv.q = $.trim(this.value); updatePreview('search'); })
                 .on('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });   // Enter must not save the payment

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!$('#bp_f_shop_id').val()) { showErrors({ shop: 'Select a RYU shop from the list.' }); return; }
        save(false);
    });
    function save(confirmDup) {
        var btn = $('#bp_f_save').prop('disabled', true);
        var data = $(form).serialize() + '&event_action=save' + (confirmDup ? '&confirm_dup=1' : '');
        post(data).done(function (r) {
            btn.prop('disabled', false);
            if (r.needs_confirm) { if (confirm(r.msg)) save(true); return; }
            if (!r.status) { if (r.errors) showErrors(r.errors); $('#bp_f_msg').text(r.msg); return; }
            var p = r.payment, edited = Number($('#bp_f_recid').val()) > 0;
            modal.hide();
            toast((edited ? 'Payment updated: ' : 'Payment saved: ') + peso(p.amount) + ' ' + METHOD[p.method] + (p.method === 'CHEQUE' ? ' (' + STATUS[p.status] + ')' : '') + '.');
            state.current = p.cuscde;
            bp_page('same');
        }).fail(function () { btn.prop('disabled', false); netFail(); });
    }

    /* shop search (combobox) */
    var comboItems = [], comboIdx = -1, comboTimer;
    function closeCombo() { $('#bp_f_list').removeClass('open'); $('#bp_f_shop').attr('aria-expanded', 'false'); }
    function renderCombo() {
        $('#bp_f_list').html(comboItems.length ? comboItems.map(function (b, i) {
            return '<div role="option" id="bp_opt_' + i + '" data-i="' + i + '" aria-selected="' + (i === comboIdx) + '"><span class="wrap-any">' + esc(b.cusdsc) + '</span><span class="muted small">' + esc(b.cuscde) + '</span></div>';
        }).join('') : '<div class="p-2 muted">No RYU shop found.</div>').addClass('open');
        $('#bp_f_shop').attr('aria-expanded', 'true');
        if (comboIdx >= 0) $('#bp_f_shop').attr('aria-activedescendant', 'bp_opt_' + comboIdx); else $('#bp_f_shop').removeAttr('aria-activedescendant');
    }
    $('#bp_f_shop').on('input', function () {
        var q = this.value;
        state.pickedShop = null; $('#bp_f_shop_id').val(''); $('#bp_f_picked').empty(); updatePreview();
        clearTimeout(comboTimer);
        comboTimer = setTimeout(function () {
            post({ event_action: 'customer_search', q: q }).done(function (r) {
                if (!r.status || $('#bp_f_shop').val() !== q) return;
                comboItems = r.rows; comboIdx = comboItems.length ? 0 : -1; renderCombo();
            });
        }, 200);
    }).on('keydown', function (e) {
        var open = $('#bp_f_list').hasClass('open');
        if (e.key === 'ArrowDown' && open) { e.preventDefault(); comboIdx = Math.min(comboItems.length - 1, comboIdx + 1); renderCombo(); }
        else if (e.key === 'ArrowUp' && open) { e.preventDefault(); comboIdx = Math.max(0, comboIdx - 1); renderCombo(); }
        else if (e.key === 'Enter' && open) { e.preventDefault(); if (comboItems[comboIdx]) pickShop(comboItems[comboIdx].cuscde, comboItems[comboIdx].cusdsc); }
        else if (e.key === 'Escape' && open) { e.preventDefault(); e.stopPropagation(); closeCombo(); }
    }).on('blur', function () { setTimeout(closeCombo, 150); });
    $('#bp_f_list').on('mousedown', function (e) { e.preventDefault(); })
        .on('click', '[data-i]', function () { var b = comboItems[$(this).data('i')]; pickShop(b.cuscde, b.cusdsc); });

    bp_page('first_p');
});
</script>
<?php endif; ?>

<?php
require "includes/main_footer.php";
?>
