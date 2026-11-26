<?php
require "includes/main_header.php";
?>
<link rel="stylesheet" href="css/trn_buyerpayments.css?v=<?php echo filemtime(__DIR__ . '/css/trn_buyerpayments.css'); ?>">
<style>
    .bb_card{max-width:640px;background:#fff;border:1px solid var(--bp-line);border-radius:14px;box-shadow:var(--bp-shadow);padding:24px 28px}
    .bb_card .form-label{font-weight:600;margin-bottom:4px}
    .bb_hint{color:var(--bp-muted);font-size:12px;margin-top:3px}
    .bb_row{margin-bottom:16px}
    .bb_seg{display:flex;gap:8px}
    .bb_seg label{flex:1;border:1px solid var(--bp-line);border-radius:8px;padding:9px 12px;cursor:pointer;display:flex;gap:8px;align-items:center;margin:0}
    .bb_seg label.on{border-color:var(--bp-brand);background:var(--bp-brand-soft);color:var(--bp-brand);font-weight:600}
    .bb_err{color:var(--bp-unpaid);font-size:13px;margin-top:4px}
</style>

<table class='big_table'>
    <tr colspan=1>
        <td colspan=1 class='td_bl'>
            <?php require 'includes/main_menu.php'; ?>
        </td>

        <td colspan=1 class="td_br">
        <?php if ($view_crud != 1): ?>
            <div class="container-fluid mt-4"><div class="alert alert-warning">You do not have access to Buyer Billing Statement.</div></div>
        <?php else: ?>
            <div class="bp_page">
                <div class="bp_head">
                    <div>
                        <h2>Buyer Billing Statement</h2>
                        <div class="sub">Export a PDF statement for a buyer: records, past payments and the amount still due.</div>
                    </div>
                </div>

                <form class="bb_card" id="bb_form" method="post" action="trn_buyerbilling_pdf.php" target="_blank" novalidate>
                    <input type="hidden" name="buyer_id" id="bb_buyer_id">

                    <div class="bb_row">
                        <label class="form-label" for="bb_buyer">Buyer <span class="req">*</span></label>
                        <div class="bp_combo">
                            <input type="text" class="form-control" id="bb_buyer" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="bb_list" placeholder="Type to search buyers&hellip;" autocomplete="off">
                            <div class="bp_combo_list" id="bb_list" role="listbox" aria-label="Buyers"></div>
                        </div>
                        <div id="bb_picked"></div>
                    </div>

                    <div class="row bb_row">
                        <div class="col-sm-4">
                            <label class="form-label" for="bb_as_of">As of <span class="req">*</span></label>
                            <input type="date" class="form-control" name="as_of" id="bb_as_of" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="bb_from">From</label>
                            <input type="date" class="form-control" name="from" id="bb_from">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="bb_to">To</label>
                            <input type="date" class="form-control" name="to" id="bb_to">
                        </div>
                        <div class="col-12 bb_hint">As of: totals count records and payments up to this date. From/To (optional): only limits the lists.</div>
                    </div>

                    <div class="bb_row">
                        <span class="form-label d-block">Records to show</span>
                        <div class="bb_seg">
                            <label class="on"><input type="radio" name="mode" value="unpaid" checked> Unpaid + Partial</label>
                            <label><input type="radio" name="mode" value="all"> All records</label>
                        </div>
                    </div>

                    <div class="bb_row">
                        <label class="form-label" for="bb_limit">Past payments</label>
                        <div class="d-flex align-items-center gap-2">
                            <span>Show last</span>
                            <input type="number" class="form-control" style="width:90px" name="limit" id="bb_limit" value="20" min="1" max="100" step="1">
                            <span>payments</span>
                        </div>
                        <div class="bb_hint">1 to 100</div>
                    </div>

                    <div class="bb_err" id="bb_msg" role="alert"></div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-light border" id="bb_clear">Clear</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        </td>
    </tr>
</table>

<?php if ($view_crud == 1): ?>
<script>
$(function () {
    var URL = 'trn_buyerbilling_ajax.php';
    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function peso(n) { return '₱' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function post(data) { return $.ajax({ url: URL, type: 'post', dataType: 'json', data: data }); }

    /* buyer search (same combobox as Add Payment in Buyer Payments) */
    var items = [], idx = -1, timer, picked = null;
    function closeList() { $('#bb_list').removeClass('open'); $('#bb_buyer').attr('aria-expanded', 'false'); }
    function renderList() {
        $('#bb_list').html(items.length ? items.map(function (b, i) {
            return '<div role="option" id="bb_opt_' + i + '" data-i="' + i + '" aria-selected="' + (i === idx) + '"><span class="wrap-any">' + esc(b.buyer_name) + '</span><span class="muted small">' + esc(b.buyer_id) + '</span></div>';
        }).join('') : '<div class="p-2 muted">No buyer found.</div>').addClass('open');
        $('#bb_buyer').attr('aria-expanded', 'true');
        if (idx >= 0) $('#bb_buyer').attr('aria-activedescendant', 'bb_opt_' + idx); else $('#bb_buyer').removeAttr('aria-activedescendant');
    }
    function pickBuyer(b) {
        picked = b.buyer_id;
        $('#bb_buyer_id').val(b.buyer_id);
        $('#bb_buyer').val(b.buyer_name);
        $('#bb_msg').text('');
        closeList();
        $('#bb_picked').empty();
        post({ event_action: 'buyer', buyer_id: b.buyer_id }).done(function (r) {
            if (!r.status || picked !== b.buyer_id) return;
            var x = r.buyer, bits = [x.buyer_address, x.buyer_contactnum].filter(function (v) { return v && $.trim(v) !== ''; });
            $('#bb_picked').html('<div class="bp_picked"><span class="wrap-any"><b>' + esc(x.buyer_name) + '</b>' + (bits.length ? ' &middot; ' + esc(bits.join(' · ')) : '') + '</span>' +
                '<span>Amount due today <b class="amt amt-due">' + peso(r.due) + '</b> &middot; ' + r.open_count + ' unpaid record' + (r.open_count === 1 ? '' : 's') + '</span></div>');
        });
    }
    $('#bb_buyer').on('input', function () {
        var q = this.value;
        picked = null; $('#bb_buyer_id').val(''); $('#bb_picked').empty();
        clearTimeout(timer);
        timer = setTimeout(function () {
            post({ event_action: 'buyer_search', q: q }).done(function (r) {
                if (!r.status) { $('#bb_msg').text(r.msg); return; }
                if ($('#bb_buyer').val() !== q) return;
                items = r.rows; idx = items.length ? 0 : -1; renderList();
            });
        }, 200);
    }).on('focus', function () {
        if (!picked) $(this).trigger('input');
    }).on('keydown', function (e) {
        var open = $('#bb_list').hasClass('open');
        if (e.key === 'ArrowDown' && open) { e.preventDefault(); idx = Math.min(items.length - 1, idx + 1); renderList(); }
        else if (e.key === 'ArrowUp' && open) { e.preventDefault(); idx = Math.max(0, idx - 1); renderList(); }
        else if (e.key === 'Enter' && open) { e.preventDefault(); if (items[idx]) pickBuyer(items[idx]); }
        else if (e.key === 'Escape' && open) { e.preventDefault(); closeList(); }
    }).on('blur', function () { setTimeout(closeList, 150); });
    $('#bb_list').on('mousedown', function (e) { e.preventDefault(); })
        .on('click', '[data-i]', function () { pickBuyer(items[$(this).data('i')]); });

    $('.bb_seg input').on('change', function () {
        $('.bb_seg label').removeClass('on');
        $(this).closest('label').addClass('on');
    });

    $('#bb_clear').on('click', function () {
        picked = null;
        $('#bb_buyer, #bb_buyer_id, #bb_from, #bb_to').val('');
        $('#bb_as_of').val('<?php echo date('Y-m-d'); ?>');
        $('#bb_limit').val(20);
        $('.bb_seg input[value="unpaid"]').prop('checked', true).trigger('change');
        $('#bb_picked, #bb_msg').empty();
    });

    $('#bb_form').on('submit', function (e) {
        var from = $('#bb_from').val(), to = $('#bb_to').val(), limit = Number($('#bb_limit').val()), msg = '';
        if (!$('#bb_buyer_id').val()) msg = 'Select a buyer from the list.';
        else if (!$('#bb_as_of').val()) msg = 'As of date is required.';
        else if (from && to && from > to) msg = 'From date must be on or before the To date.';
        else if (!(limit >= 1 && limit <= 100 && Math.floor(limit) === limit)) msg = 'Past payments must be a whole number from 1 to 100.';
        $('#bb_msg').text(msg);
        if (msg) e.preventDefault();
    });
});
</script>
<?php endif; ?>

<?php
require "includes/main_footer.php";
?>
