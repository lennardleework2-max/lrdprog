<?php
require "includes/main_header.php";

if ((int)$view_crud !== 1) {
    echo "<div class='p-4'>You do not have access to this page.</div>";
    require "includes/main_footer.php";
    exit;
}

$routes = array();
$stmt_routes = $link->prepare("SELECT route_id, route_desc FROM mf_routes WHERE (route_desc IS NULL OR route_desc <> '-') ORDER BY route_desc, route_id");
$stmt_routes->execute();
while ($rs_route = $stmt_routes->fetch()) {
    $routes[] = $rs_route;
}
?>
<link rel="stylesheet" href="css/trn_salespayments.css?v=<?php echo filemtime(__DIR__ . '/css/trn_salespayments.css'); ?>">

<table class='big_table'>
    <tr colspan=1>
        <td colspan=1 class='td_bl'>
            <?php include 'includes/main_menu.php'; ?>
        </td>

        <td colspan=1 class="td_br" id="td_br" style="vertical-align:top;width:100%;max-width:0">
            <div class="cp" id="cp">
                <div class="cp_inner">

                    <div class="cp_head">
                        <div>
                            <h1>Customer Payments</h1>
                            <p>Select a customer to see their records, payments and checks on hand.</p>
                        </div>
                    </div>

                    <div class="cp_split">

                        <!-- CUSTOMER LIST -->
                        <div class="cp_card cp_list">
                            <div class="cp_list_tools">
                                <div class="cp_search mb-2"><i class="bi bi-search"></i><input type="text" class="form-control" id="cp_search" placeholder="Search customer" autocomplete="off"></div>
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm" id="cp_route">
                                        <option value="">All routes</option>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?php echo htmlspecialchars($route['route_id'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($route['route_desc'] !== '' ? $route['route_desc'] : $route['route_id'], ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="form-select form-select-sm" id="cp_filter" style="max-width:140px">
                                        <option value="balance">With balance</option>
                                        <option value="paid">Fully paid</option>
                                        <option value="all">All</option>
                                    </select>
                                </div>
                            </div>
                            <div id="cp_customers"></div>
                            <div class="cp_pager" id="cp_customers_pager"></div>
                            <div class="cp_list_total"><span>Total balance</span><span class="num amt_due" id="cp_customers_total">0.00</span></div>
                        </div>

                        <!-- ACCOUNT DETAIL -->
                        <div class="cp_detail" id="cp_detail">
                            <button type="button" class="btn btn-light cp_back mb-3" id="cp_back"><i class="bi bi-arrow-left me-1"></i> Customers</button>

                            <div id="cp_no_customer" class="cp_card cp_empty"><i class="bi bi-person-lines-fill"></i>Select a customer to view their account.</div>

                            <div id="cp_account" style="display:none">
                                <div class="cp_card cp_account_head">
                                    <div class="who">
                                        <span class="cp_avatar lg" id="cp_acc_avatar"></span>
                                        <div style="min-width:0">
                                            <div class="cp_account_name" id="cp_acc_name"></div>
                                            <div class="small_muted" id="cp_acc_meta"></div>
                                        </div>
                                    </div>
                                    <div class="actions">
                                        <?php if ((int)$add_crud === 1): ?>
                                            <button type="button" class="btn btn-primary" id="cp_btn_add"><i class="bi bi-plus-lg me-1"></i> Record Payment</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="cp_kpis">
                                    <div class="cp_card cp_kpi"><div class="label">Total sales</div><div class="value" id="cp_k_sales"></div><div class="hint" id="cp_k_sales_hint"></div></div>
                                    <div class="cp_card cp_kpi"><div class="label"><span class="cp_dot" style="background:#15803d"></span>Paid</div><div class="value" id="cp_k_paid"></div><div class="hint">incl. checks received</div></div>
                                    <div class="cp_card cp_kpi"><div class="label"><span class="cp_dot" style="background:#b45309"></span>Checks on hand</div><div class="value" id="cp_k_checks"></div><div class="hint" id="cp_k_checks_hint"></div></div>
                                    <div class="cp_card cp_kpi alert"><div class="label"><span class="cp_dot" style="background:#b91c1c"></span>Balance</div><div class="value" id="cp_k_balance"></div><div class="hint" id="cp_k_balance_hint"></div></div>
                                </div>

                                <div class="cp_card">
                                    <div class="cp_tabs">
                                        <a href="#" class="active" data-tab="drs">Records<span class="count" id="cp_c_drs">0</span></a>
                                        <a href="#" data-tab="payments">Payment history<span class="count" id="cp_c_payments">0</span></a>
                                        <a href="#" data-tab="checks">Checks on hand<span class="count" id="cp_c_checks">0</span></a>
                                    </div>

                                    <div class="cp_tab" id="cp_tab_drs">
                                        <div class="cp_tab_tools">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="cp_include_paid">
                                                <label class="form-check-label small_muted" for="cp_include_paid">Include fully paid records</label>
                                            </div>
                                        </div>
                                        <table class="cp_tbl">
                                            <thead><tr><th>Doc #</th><th>Order #</th><th>Date</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Balance</th><th>Age</th><th></th></tr></thead>
                                            <tbody id="cp_drs_body"></tbody>
                                        </table>
                                        <div class="cp_pager" id="cp_drs_pager"></div>
                                    </div>

                                    <div class="cp_tab" id="cp_tab_payments" style="display:none">
                                        <table class="cp_tbl">
                                            <thead><tr><th>Date</th><th>Doc #</th><th>Method</th><th class="num">Amount</th><th>Status</th><th></th></tr></thead>
                                            <tbody id="cp_payments_body"></tbody>
                                        </table>
                                        <div class="cp_pager" id="cp_payments_pager"></div>
                                    </div>

                                    <div class="cp_tab" id="cp_tab_checks" style="display:none">
                                        <table class="cp_tbl">
                                            <thead><tr><th>Check #</th><th>Doc #</th><th>Received</th><th>Check date</th><th class="num">Amount</th><th></th></tr></thead>
                                            <tbody id="cp_checks_body"></tbody>
                                        </table>
                                        <div class="cp_pager" id="cp_checks_pager"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- RECORD / EDIT PAYMENT -->
<div class="modal fade cp_modal" id="cp_pay_modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title" id="cp_pay_title">Record Payment</h5><div class="small_muted" id="cp_pay_sub"></div></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cp_pay_id" value="0">
                <p class="cp_section">Payment details</p>
                <div class="mb-3">
                    <label class="cp_label" for="cp_pay_docnum">Record</label>
                    <select class="form-select" id="cp_pay_docnum"></select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6"><label class="cp_label" for="cp_pay_date">Date received</label><input type="text" class="form-control date_picker" id="cp_pay_date" autocomplete="off"></div>
                    <div class="col-6"><label class="cp_label" for="cp_pay_amount">Amount</label><input type="text" class="form-control text-end" id="cp_pay_amount" inputmode="decimal" autocomplete="off" placeholder="0.00"></div>
                </div>
                <label class="cp_label">Method</label>
                <div class="cp_types mb-3">
                    <input type="radio" name="cp_pay_type" id="cp_t_cash" value="CASH"><label for="cp_t_cash"><i class="bi bi-cash-stack"></i>Cash</label>
                    <input type="radio" name="cp_pay_type" id="cp_t_check" value="CHECK"><label for="cp_t_check"><i class="bi bi-journal-check"></i>Check</label>
                    <input type="radio" name="cp_pay_type" id="cp_t_bank" value="BANK"><label for="cp_t_bank"><i class="bi bi-bank"></i>Bank transfer</label>
                </div>
                <div class="cp_check_box row g-3 mx-0 mb-3" id="cp_ref_box">
                    <div class="col-12 col-sm-6" id="cp_ref_col"><label class="cp_label" for="cp_pay_ref" id="cp_ref_label">Check number</label><input type="text" class="form-control" id="cp_pay_ref" autocomplete="off" maxlength="100"></div>
                    <div class="col-12 col-sm-6" id="cp_check_date_col"><label class="cp_label" for="cp_pay_check_date">Check date (deposit on)</label><input type="text" class="form-control date_picker" id="cp_pay_check_date" autocomplete="off"></div>
                </div>
                <div class="mb-4"><label class="cp_label" for="cp_pay_remarks">Remarks <span class="small_muted">(optional, e.g. bank name or collector)</span></label><input type="text" class="form-control" id="cp_pay_remarks" maxlength="255" autocomplete="off"></div>

                <div class="cp_alloc_sum">
                    <div><small>Record balance</small><b id="cp_s_available">0.00</b></div>
                    <div><small>This payment</small><b id="cp_s_amount">0.00</b></div>
                    <div><small>Balance after</small><b class="amt_due" id="cp_s_balance">0.00</b></div>
                </div>
                <div class="cp_error" id="cp_pay_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="cp_pay_save">Save payment</button>
            </div>
        </div>
    </div>
</div>

<!-- MARK CHECK DEPOSITED -->
<div class="modal fade cp_modal" id="cp_dep_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Mark as deposited</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="cp_dep_id">
                <div class="small_muted mb-3" id="cp_dep_info"></div>
                <label class="cp_label" for="cp_dep_date">Deposit date</label>
                <input type="text" class="form-control date_picker" id="cp_dep_date" autocomplete="off">
                <div class="cp_error" id="cp_dep_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="cp_dep_save">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- CONFIRM DELETE -->
<div class="modal fade cp_modal" id="cp_del_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Delete payment?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="cp_del_id">
                <div id="cp_del_info"></div>
                <div class="small_muted mt-2">The record will show this amount as unpaid again.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="cp_del_save">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="cp_toast" id="cp_toast"></div>

<script>
var CP = {
    perm: {add: <?php echo (int)$add_crud; ?>, edit: <?php echo (int)$edit_crud; ?>, del: <?php echo (int)$delete_crud; ?>},
    size: 10,
    buyer: null,
    summary: null,
    tab: 'drs',
    page: {customers: 1, drs: 1, payments: 1, checks: 1},
    records: [],
    amountTouched: false
};

function cpEsc(value){
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function cpMoney(value){
    return Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function cpNum(value){
    var n = parseFloat(String(value == null ? '' : value).replace(/,/g, ''));
    return isNaN(n) ? 0 : Math.round(n * 100) / 100;
}

var CP_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
function cpDate(ymd){
    if(!ymd){ return ''; }
    var p = String(ymd).substr(0, 10).split('-');
    if(p.length !== 3){ return ymd; }
    return CP_MONTHS[parseInt(p[1], 10) - 1] + ' ' + p[2] + ', ' + p[0];
}

function cpToInput(ymd){
    if(!ymd){ return ''; }
    var p = String(ymd).substr(0, 10).split('-');
    return p.length === 3 ? p[1] + '/' + p[2] + '/' + p[0] : '';
}

function cpToday(){
    var d = new Date();
    return ('0' + (d.getMonth() + 1)).slice(-2) + '/' + ('0' + d.getDate()).slice(-2) + '/' + d.getFullYear();
}

function cpInitials(name){
    var parts = String(name || '').replace(/[^A-Za-z0-9 ]/g, ' ').trim().split(/\s+/);
    return ((parts[0] || '?').charAt(0) + (parts.length > 1 ? parts[1].charAt(0) : '')).toUpperCase();
}

function cpIsWide(){
    return window.matchMedia('(min-width: 992px)').matches;
}

function cpToast(message, isError){
    var el = $('#cp_toast');
    el.toggleClass('error', !!isError).text(message).stop(true, true).fadeIn(150);
    clearTimeout(CP.toastTimer);
    CP.toastTimer = setTimeout(function(){ el.fadeOut(200); }, isError ? 5000 : 2500);
}

function cpApi(action, data){
    return $.ajax({
        url: 'trn_salespayments_ajax.php',
        type: 'post',
        dataType: 'json',
        global: false,
        data: $.extend({event_action: action}, data || {})
    }).then(function(res){
        if(!res || res.status != 1){
            return $.Deferred().reject((res && res.msg) || 'Something went wrong.').promise();
        }
        return res;
    }, function(){
        return $.Deferred().reject('Unable to reach the server. Please try again.').promise();
    });
}

function cpRenderPager(el, data, onPage){
    var pages = Math.max(1, Math.ceil(data.total / data.size));
    var from = data.total === 0 ? 0 : (data.page - 1) * data.size + 1;
    var to = Math.min(data.total, data.page * data.size);
    if(data.total <= data.size){
        el.html(data.total > 0 ? '<span>' + data.total + ' record' + (data.total === 1 ? '' : 's') + '</span>' : '');
        return;
    }
    var start = Math.max(1, Math.min(data.page - 1, pages - 2));
    var end = Math.min(pages, start + 2);
    var html = '<span>' + from + '&ndash;' + to + ' of ' + data.total + '</span><ul class="pagination">';
    html += '<li class="page-item ' + (data.page <= 1 ? 'disabled' : '') + '"><a class="page-link" title="First page" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>';
    html += '<li class="page-item ' + (data.page <= 1 ? 'disabled' : '') + '"><a class="page-link" title="Previous page" data-page="' + (data.page - 1) + '"><i class="bi bi-chevron-left"></i></a></li>';
    for(var p = start; p <= end; p++){
        html += '<li class="page-item ' + (p === data.page ? 'active' : '') + '"><a class="page-link" data-page="' + p + '">' + p + '</a></li>';
    }
    html += '<li class="page-item ' + (data.page >= pages ? 'disabled' : '') + '"><a class="page-link" title="Next page" data-page="' + (data.page + 1) + '"><i class="bi bi-chevron-right"></i></a></li>';
    html += '<li class="page-item ' + (data.page >= pages ? 'disabled' : '') + '"><a class="page-link" title="Last page (' + pages + ')" data-page="' + pages + '"><i class="bi bi-chevron-double-right"></i></a></li></ul>';
    el.html(html);
    el.find('.page-item:not(.disabled):not(.active) .page-link').on('click', function(){
        onPage(parseInt($(this).data('page'), 10));
    });
}

/* ---------- customers ---------- */
function cpLoadCustomers(){
    var box = $('#cp_customers').addClass('cp_loading');
    cpApi('customers', {
        search: $('#cp_search').val(),
        route_id: $('#cp_route').val(),
        filter: $('#cp_filter').val(),
        page: CP.page.customers,
        size: CP.size
    }).done(function(res){
        var html = '';
        res.rows.forEach(function(row){
            html += '<div class="cp_cust ' + (row.buyer_id === CP.buyer ? 'on' : '') + '" data-id="' + cpEsc(row.buyer_id) + '">' +
                '<span class="cp_avatar">' + cpEsc(cpInitials(row.buyer_name)) + '</span>' +
                '<div class="meta"><div class="name">' + cpEsc(row.buyer_name) + '</div>' +
                '<div class="small_muted">' + (row.open_drs > 0 ? row.open_drs + ' open record' + (row.open_drs === 1 ? '' : 's') : 'No open records') + '</div></div>' +
                '<div class="num ' + (row.balance > 0 ? 'amt_due' : 'amt_ok') + '">' + cpMoney(row.balance) + '</div></div>';
        });
        box.html(html || '<div class="cp_empty"><i class="bi bi-search"></i>No customers found.</div>');
        $('#cp_customers_total').text(cpMoney(res.total_balance));
        cpRenderPager($('#cp_customers_pager'), res, function(p){ CP.page.customers = p; cpLoadCustomers(); });

        if(!CP.buyer && res.rows.length && cpIsWide()){
            cpSelectCustomer(res.rows[0].buyer_id);
        }
    }).fail(function(msg){
        box.html('<div class="cp_empty"><i class="bi bi-exclamation-circle"></i>' + cpEsc(msg) + '</div>');
    }).always(function(){ box.removeClass('cp_loading'); });
}

function cpSelectCustomer(buyerId){
    CP.buyer = buyerId;
    CP.page.drs = CP.page.payments = CP.page.checks = 1;
    $('#cp_customers .cp_cust').removeClass('on').filter(function(){ return $(this).data('id') == buyerId; }).addClass('on');
    $('#cp').addClass('show_detail');
    $('#cp_no_customer').hide();
    $('#cp_account').show();
    cpLoadAccount();
    if(!cpIsWide()){ window.scrollTo(0, 0); }
}

/* ---------- account ---------- */
function cpLoadAccount(){
    $('#cp_account').addClass('cp_loading');
    cpApi('account', {buyer_id: CP.buyer}).done(function(res){
        var s = CP.summary = res.summary;
        $('#cp_acc_avatar').text(cpInitials(s.buyer_name));
        $('#cp_acc_name').text(s.buyer_name);
        var meta = [];
        if(s.route){ meta.push('<i class="bi bi-geo-alt me-1"></i>' + cpEsc(s.route)); }
        if(s.salesman){ meta.push('<i class="bi bi-person me-1"></i>' + cpEsc(s.salesman)); }
        $('#cp_acc_meta').html(meta.join('<span class="mx-2">&middot;</span>') || cpEsc(s.buyer_id));
        $('#cp_k_sales').text('₱' + cpMoney(s.total_sales));
        $('#cp_k_sales_hint').text(s.dr_count + ' record' + (s.dr_count === 1 ? '' : 's'));
        $('#cp_k_paid').text('₱' + cpMoney(s.paid));
        $('#cp_k_checks').text('₱' + cpMoney(s.checks_amount));
        $('#cp_k_checks_hint').text(s.checks_count + ' check' + (s.checks_count === 1 ? '' : 's') + ' not yet deposited');
        $('#cp_k_balance').text('₱' + cpMoney(s.balance));
        $('#cp_k_balance_hint').text(s.oldest_days === null ? 'nothing outstanding' : 'oldest open record: ' + s.oldest_days + ' days');
        $('#cp_c_drs').text(s.open_drs);
        $('#cp_c_checks').text(s.checks_count);
        cpLoadTab(true);
    }).fail(function(msg){ cpToast(msg, true); })
      .always(function(){ $('#cp_account').removeClass('cp_loading'); });
}

function cpLoadTab(allTabs){
    if(allTabs){
        cpLoadDrs(); cpLoadPayments(); cpLoadChecks();
        return;
    }
    if(CP.tab === 'drs'){ cpLoadDrs(); }
    if(CP.tab === 'payments'){ cpLoadPayments(); }
    if(CP.tab === 'checks'){ cpLoadChecks(); }
}

function cpAgePill(days){
    if(days === null){ return '<span class="cp_pill ok">Paid</span>'; }
    return '<span class="cp_pill ' + (days > 30 ? 'due' : 'neutral') + '">' + days + ' day' + (days === 1 ? '' : 's') + '</span>';
}

function cpLoadDrs(){
    var body = $('#cp_drs_body').addClass('cp_loading');
    var openOnly = $('#cp_include_paid').is(':checked') ? '0' : '1';
    cpApi('drs', {buyer_id: CP.buyer, open_only: openOnly, page: CP.page.drs, size: CP.size}).done(function(res){
        var html = '';
        res.rows.forEach(function(r){
            html += '<tr>' +
                '<td data-label="Doc #" class="name nowrap">' + cpEsc(r.docnum) + '</td>' +
                '<td data-label="Order #" class="nowrap">' + (r.ordernum ? cpEsc(r.ordernum) : '<span class="muted">&mdash;</span>') + '</td>' +
                '<td data-label="Date" class="nowrap">' + cpDate(r.trndte) + '</td>' +
                '<td data-label="Amount" class="num">' + cpMoney(r.trntot) + '</td>' +
                '<td data-label="Paid" class="num">' + (r.paid > 0 ? cpMoney(r.paid) : '<span class="muted">&mdash;</span>') + '</td>' +
                '<td data-label="Balance" class="num ' + (r.balance > 0 ? 'amt_due' : 'amt_ok') + '">' + cpMoney(r.balance) + '</td>' +
                '<td data-label="Age">' + cpAgePill(r.age_days) + '</td>' +
                '<td class="cp_actions text-end">' + (CP.perm.add && r.balance > 0 ? '<button type="button" class="btn btn-sm btn-light" data-pay="' + cpEsc(r.docnum) + '">Record payment</button>' : '') + '</td></tr>';
        });
        body.html(html || '<tr><td colspan="8" class="cp_empty"><i class="bi bi-check2-circle"></i>' + (openOnly === '1' ? 'No open records &mdash; this customer is fully paid.' : 'No records found.') + '</td></tr>');
        cpRenderPager($('#cp_drs_pager'), res, function(p){ CP.page.drs = p; cpLoadDrs(); });
    }).fail(function(msg){ cpToast(msg, true); })
      .always(function(){ body.removeClass('cp_loading'); });
}

// pay_type is empty for payments entered from the Sales screen
function cpMethodText(r){
    var labels = {CHECK: 'Check', BANK: 'Bank transfer', CASH: 'Cash'};
    var label = labels[r.pay_type] || 'Sales screen entry';
    return label + (r.check_number ? ' <span class="small_muted">&middot; ' + cpEsc(r.check_number) + '</span>' : '');
}

function cpDocCell(r){
    return '<span class="nowrap">' + cpEsc(r.docnum) + '</span>' + (r.ordernum ? '<div class="small_muted">' + cpEsc(r.ordernum) + '</div>' : '');
}

function cpStatusPill(r){
    if(!r.pay_type){ return '<span class="cp_pill neutral">Recorded</span>'; }
    if(r.pay_type !== 'CHECK'){ return '<span class="cp_pill ok">Cleared</span>'; }
    if(r.deposited_date){ return '<span class="cp_pill ok">Deposited ' + cpDate(r.deposited_date) + '</span>'; }
    return '<span class="cp_pill wait">On hand &middot; ' + cpDate(r.check_date) + '</span>';
}

function cpLoadPayments(){
    var body = $('#cp_payments_body').addClass('cp_loading');
    cpApi('payments', {buyer_id: CP.buyer, page: CP.page.payments, size: CP.size}).done(function(res){
        CP.paymentRows = {};
        var html = '';
        res.rows.forEach(function(r){
            CP.paymentRows[r.recid] = r;
            var actions = '';
            if(CP.perm.edit){ actions += '<button type="button" class="btn-icon" title="Edit" data-edit="' + r.recid + '"><i class="bi bi-pencil"></i></button>'; }
            if(CP.perm.del){ actions += '<button type="button" class="btn-icon danger" title="Delete" data-del="' + r.recid + '"><i class="bi bi-trash"></i></button>'; }
            html += '<tr>' +
                '<td data-label="Date" class="nowrap">' + cpDate(r.pay_date) + '</td>' +
                '<td data-label="Doc #">' + cpDocCell(r) + '</td>' +
                '<td data-label="Method">' + cpMethodText(r) + (r.remarks ? '<div class="small_muted">' + cpEsc(r.remarks) + '</div>' : '') + '</td>' +
                '<td data-label="Amount" class="num fw-semibold">' + cpMoney(r.amount) + '</td>' +
                '<td data-label="Status">' + cpStatusPill(r) + '</td>' +
                '<td class="cp_actions text-end text-nowrap">' + actions + '</td></tr>';
        });
        body.html(html || '<tr><td colspan="6" class="cp_empty"><i class="bi bi-receipt"></i>No payments recorded yet.</td></tr>');
        $('#cp_c_payments').text(res.total);
        cpRenderPager($('#cp_payments_pager'), res, function(p){ CP.page.payments = p; cpLoadPayments(); });
    }).fail(function(msg){ cpToast(msg, true); })
      .always(function(){ body.removeClass('cp_loading'); });
}

function cpLoadChecks(){
    var body = $('#cp_checks_body').addClass('cp_loading');
    cpApi('checks', {buyer_id: CP.buyer, page: CP.page.checks, size: CP.size}).done(function(res){
        CP.checkRows = {};
        var html = '';
        res.rows.forEach(function(r){
            CP.checkRows[r.recid] = r;
            html += '<tr>' +
                '<td data-label="Check #" class="name">' + cpEsc(r.check_number) + '</td>' +
                '<td data-label="Doc #">' + cpDocCell(r) + '</td>' +
                '<td data-label="Received" class="nowrap">' + cpDate(r.pay_date) + '</td>' +
                '<td data-label="Check date" class="nowrap">' + cpDate(r.check_date) + '</td>' +
                '<td data-label="Amount" class="num fw-semibold">' + cpMoney(r.amount) + '</td>' +
                '<td class="cp_actions text-end">' + (CP.perm.edit ? '<button type="button" class="btn btn-sm btn-soft-success" data-dep="' + r.recid + '"><i class="bi bi-check2 me-1"></i>Mark deposited</button>' : '') + '</td></tr>';
        });
        body.html(html || '<tr><td colspan="6" class="cp_empty"><i class="bi bi-journal-check"></i>No checks waiting to be deposited.</td></tr>');
        $('#cp_c_checks').text(res.total);
        cpRenderPager($('#cp_checks_pager'), res, function(p){ CP.page.checks = p; cpLoadChecks(); });
    }).fail(function(msg){ cpToast(msg, true); })
      .always(function(){ body.removeClass('cp_loading'); });
}

function cpRefreshAfterChange(){
    cpLoadAccount();
    cpLoadCustomers();
}

/* ---------- record / edit payment ---------- */
function cpPayType(){
    return $('input[name=cp_pay_type]:checked').val() || 'CASH';
}

function cpToggleTypeFields(){
    var type = cpPayType();
    $('#cp_ref_box').toggle(type !== 'CASH');
    $('#cp_check_date_col').toggle(type === 'CHECK');
    $('#cp_ref_label').text(type === 'CHECK' ? 'Check number' : 'Reference number (optional)');
}

// docnum: preselect this record (from the Records tab "Record payment" button)
function cpOpenPayment(paymentId, docnum){
    paymentId = paymentId || 0;
    cpApi('open_drs', {buyer_id: CP.buyer, payment_id: paymentId}).done(function(res){
        var p = res.payment;
        CP.records = res.drs;
        CP.amountTouched = !!p;

        var options = CP.records.length ? '<option value="">Select a record&hellip;</option>' : '<option value="">No open records</option>';
        CP.records.forEach(function(r){
            options += '<option value="' + cpEsc(r.docnum) + '">' + cpEsc(r.docnum) + (r.ordernum ? ' · ' + cpEsc(r.ordernum) : '') +
                ' · ' + cpDate(r.trndte) + ' — balance ₱' + cpMoney(r.available) + '</option>';
        });
        $('#cp_pay_docnum').html(options).val(p ? p.docnum : (docnum || (CP.records.length === 1 ? CP.records[0].docnum : '')));

        $('#cp_pay_id').val(paymentId);
        $('#cp_pay_title').text(p ? 'Edit Payment' : 'Record Payment');
        $('#cp_pay_sub').text(CP.summary.buyer_name + ' · Balance ₱' + cpMoney(CP.summary.balance));
        $('#cp_pay_date').val(p ? cpToInput(p.date_paid) : cpToday());
        $('#cp_pay_amount').val(p ? cpMoney(p.amount) : '');
        $('input[name=cp_pay_type][value=' + (p ? (p.pay_type || (p.check_number ? 'CHECK' : 'CASH')) : 'CASH') + ']').prop('checked', true);
        $('#cp_pay_ref').val(p ? (p.check_number || '') : '');
        $('#cp_pay_check_date').val(p ? cpToInput(p.check_date) : '');
        $('#cp_pay_remarks').val(p ? (p.remarks || '') : '');
        $('#cp_pay_error').hide();
        cpToggleTypeFields();
        cpOnRecordChange();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cp_pay_modal')).show();
    }).fail(function(msg){ cpToast(msg, true); });
}

function cpSelectedRecord(){
    var docnum = $('#cp_pay_docnum').val();
    return CP.records.filter(function(r){ return r.docnum === docnum; })[0] || null;
}

// new payment: suggest paying the record's full balance until the user types an amount
function cpOnRecordChange(){
    var r = cpSelectedRecord();
    if(r && !CP.amountTouched){ $('#cp_pay_amount').val(cpMoney(r.available)); }
    cpUpdatePaySummary();
}

function cpUpdatePaySummary(){
    var r = cpSelectedRecord();
    var available = r ? r.available : 0;
    var amount = cpNum($('#cp_pay_amount').val());
    var after = Math.round((available - amount) * 100) / 100;
    $('#cp_s_available').text('₱' + cpMoney(available));
    $('#cp_s_amount').text('₱' + cpMoney(amount));
    $('#cp_s_balance').text(after < 0 ? 'Over by ₱' + cpMoney(-after) : '₱' + cpMoney(after))
        .toggleClass('amt_ok', after === 0).toggleClass('amt_due', after !== 0);
}

function cpSavePayment(){
    var btn = $('#cp_pay_save').prop('disabled', true);
    cpApi('save', {
        payment_id: $('#cp_pay_id').val(),
        buyer_id: CP.buyer,
        docnum: $('#cp_pay_docnum').val(),
        pay_date: $('#cp_pay_date').val(),
        amount: cpNum($('#cp_pay_amount').val()),
        pay_type: cpPayType(),
        check_number: cpPayType() === 'CASH' ? '' : $('#cp_pay_ref').val(),
        check_date: cpPayType() === 'CHECK' ? $('#cp_pay_check_date').val() : '',
        remarks: $('#cp_pay_remarks').val()
    }).done(function(res){
        bootstrap.Modal.getInstance(document.getElementById('cp_pay_modal')).hide();
        cpToast(res.msg);
        cpRefreshAfterChange();
    }).fail(function(msg){
        $('#cp_pay_error').text(msg).show();
    }).always(function(){ btn.prop('disabled', false); });
}

/* ---------- events ---------- */
$(function(){
    var searchTimer;
    $('#cp_search').on('input', function(){
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function(){ CP.page.customers = 1; cpLoadCustomers(); }, 300);
    });
    $('#cp_route, #cp_filter').on('change', function(){ CP.page.customers = 1; cpLoadCustomers(); });

    $('#cp_customers').on('click', '.cp_cust', function(){ cpSelectCustomer(String($(this).data('id'))); });
    $('#cp_back').on('click', function(){ $('#cp').removeClass('show_detail'); window.scrollTo(0, 0); });

    $('.cp_tabs a').on('click', function(e){
        e.preventDefault();
        CP.tab = $(this).data('tab');
        $('.cp_tabs a').removeClass('active');
        $(this).addClass('active');
        $('.cp_tab').hide();
        $('#cp_tab_' + CP.tab).show();
    });
    $('#cp_include_paid').on('change', function(){ CP.page.drs = 1; cpLoadDrs(); });

    $('#cp_btn_add').on('click', function(){ cpOpenPayment(0); });
    $('#cp_payments_body').on('click', '[data-edit]', function(){ cpOpenPayment($(this).data('edit')); });

    $('#cp_payments_body').on('click', '[data-del]', function(){
        var r = CP.paymentRows[$(this).data('del')];
        $('#cp_del_id').val(r.recid);
        $('#cp_del_info').html(cpDate(r.pay_date) + ' &middot; ' + cpMethodText(r) + '<div class="fs-5 fw-semibold mt-1">₱' + cpMoney(r.amount) + '</div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cp_del_modal')).show();
    });
    $('#cp_del_save').on('click', function(){
        var btn = $(this).prop('disabled', true);
        cpApi('delete', {payment_id: $('#cp_del_id').val()}).done(function(res){
            bootstrap.Modal.getInstance(document.getElementById('cp_del_modal')).hide();
            cpToast(res.msg);
            cpRefreshAfterChange();
        }).fail(function(msg){ cpToast(msg, true); })
          .always(function(){ btn.prop('disabled', false); });
    });

    $('#cp_checks_body').on('click', '[data-dep]', function(){
        var r = CP.checkRows[$(this).data('dep')];
        $('#cp_dep_id').val(r.recid);
        $('#cp_dep_info').html('Check <b>' + cpEsc(r.check_number) + '</b> &middot; ₱' + cpMoney(r.amount) + '<br>Check date: ' + cpDate(r.check_date));
        $('#cp_dep_date').val(cpToday());
        $('#cp_dep_error').hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cp_dep_modal')).show();
    });
    $('#cp_dep_save').on('click', function(){
        var btn = $(this).prop('disabled', true);
        cpApi('deposit', {payment_id: $('#cp_dep_id').val(), deposited_date: $('#cp_dep_date').val()}).done(function(res){
            bootstrap.Modal.getInstance(document.getElementById('cp_dep_modal')).hide();
            cpToast(res.msg);
            cpRefreshAfterChange();
        }).fail(function(msg){ $('#cp_dep_error').text(msg).show(); })
          .always(function(){ btn.prop('disabled', false); });
    });

    $('input[name=cp_pay_type]').on('change', cpToggleTypeFields);
    $('#cp_pay_docnum').on('change', cpOnRecordChange);
    $('#cp_pay_amount').on('input', function(){
        CP.amountTouched = true;
        cpUpdatePaySummary();
    }).on('blur', function(){
        var n = cpNum($(this).val());
        $(this).val(n > 0 ? cpMoney(n) : '');
    });
    $('#cp_pay_save').on('click', cpSavePayment);
    $('#cp_drs_body').on('click', '[data-pay]', function(){ cpOpenPayment(0, String($(this).data('pay'))); });

    // leaving the phone/tablet account view when the screen grows to desktop
    $(window).on('resize', function(){
        if(cpIsWide() && !CP.buyer){ $('#cp').removeClass('show_detail'); }
    });

    cpLoadCustomers();
});
</script>

<?php
require "includes/main_footer.php";
?>
