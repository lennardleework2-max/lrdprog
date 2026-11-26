/* Buyer Payments mockup - small shared UI helpers (PHASE 1 UI MOCKUP ONLY). Requires bp-core.js. */
(function () {
    'use strict';
    var esc = BP.escapeHtml;

    var BUYER_BADGE = {
        Paid:    ['s-paid',    'bi-check-circle-fill'],
        Partial: ['s-partial', 'bi-circle-half'],
        Unpaid:  ['s-unpaid',  'bi-exclamation-circle-fill']
    };
    var PAY_BADGE = {
        'Received':       ['s-received', 'bi-check2'],
        'Cleared':        ['s-cleared',  'bi-check2-circle'],
        'Hold':           ['s-hold',     'bi-pause-circle'],
        'For Deposit':    ['s-deposit',  'bi-box-arrow-in-down'],
        'Bounced':        ['s-returned', 'bi-x-octagon'],
        'Closed Account': ['s-returned', 'bi-slash-circle']
    };

    function badge(map, label) {
        var m = map[label] || ['s-received', 'bi-dot'];
        return '<span class="badge_s ' + m[0] + '"><i class="bi ' + m[1] + '" aria-hidden="true"></i>' + esc(label) + '</span>';
    }
    function buyerBadge(summary) {
        var html = badge(BUYER_BADGE, summary.status);
        if (summary.credit) html += ' <span class="badge_s s-credit"><i class="bi bi-plus-circle" aria-hidden="true"></i>Credit ' + BP.formatPeso(summary.credit) + '</span>';
        return html;
    }
    function payBadge(p) { return badge(PAY_BADGE, p.status); }
    function amountClass(p) {
        var s = BP.paymentState(p);
        return s === 'onhand' ? 'amt-onhand' : s === 'returned' ? 'amt-returned' : '';
    }
    function methodIcon(m) { return '<i class="bi ' + (BP.METHODS[m] ? BP.METHODS[m].icon : 'bi-dot') + '" aria-hidden="true"></i>'; }
    function payDetail(p) {
        if (p.method === 'cheque') return esc(p.bank) + ' · Chq #' + esc(p.chqNo);
        return p.ref ? 'Ref ' + esc(p.ref) : '-';
    }

    /* ---------- toast ---------- */
    var toastBox;
    function toast(msg, kind) {
        if (!toastBox) {
            toastBox = document.createElement('div');
            toastBox.className = 'toast_box';
            toastBox.setAttribute('role', 'status');
            toastBox.setAttribute('aria-live', 'polite');
            document.body.appendChild(toastBox);
        }
        var icons = { ok: 'bi-check-circle-fill', warn: 'bi-exclamation-triangle-fill', err: 'bi-x-circle-fill' };
        var el = document.createElement('div');
        el.className = 'toast_x ' + (kind || 'ok');
        el.innerHTML = '<i class="bi ' + icons[kind || 'ok'] + '" aria-hidden="true"></i><div class="wrap-any"></div>';
        el.lastChild.textContent = msg;
        toastBox.appendChild(el);
        setTimeout(function () { el.classList.add('out'); setTimeout(function () { el.remove(); }, 220); }, 3200);
    }

    /* ---------- confirm dialog (promise) ---------- */
    function confirmBox(message, okLabel, danger) {
        return new Promise(function (resolve) {
            var prev = document.activeElement;
            var wrap = document.createElement('div');
            wrap.className = 'cfm_backdrop';
            wrap.innerHTML =
                '<div class="cfm" role="alertdialog" aria-modal="true" aria-labelledby="cfm_msg">' +
                '<p id="cfm_msg" class="wrap-any"></p>' +
                '<div class="cfm_actions"><button type="button" class="btn btn-ghost" data-r="0">Cancel</button>' +
                '<button type="button" class="btn ' + (danger ? 'btn-danger' : 'btn-brand') + '" data-r="1"></button></div></div>';
            wrap.querySelector('p').textContent = message;
            wrap.querySelector('[data-r="1"]').textContent = okLabel || 'Confirm';
            function done(v) {
                wrap.remove(); document.removeEventListener('keydown', onKey, true);
                if (prev && prev.focus) prev.focus();
                resolve(v);
            }
            function onKey(e) {
                if (e.key === 'Escape') { e.stopPropagation(); e.preventDefault(); done(false); }
                if (e.key === 'Tab') { // keep focus inside the two buttons
                    var b = wrap.querySelectorAll('button'), first = b[0], last = b[b.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            }
            wrap.addEventListener('click', function (e) {
                var r = e.target.closest('[data-r]');
                if (r) done(r.getAttribute('data-r') === '1');
                else if (e.target === wrap) done(false);
            });
            document.addEventListener('keydown', onKey, true);
            // Mount inside an open Bootstrap modal/offcanvas: they pull focus back into themselves otherwise.
            (document.querySelector('.modal.show, .offcanvas.show') || document.body).appendChild(wrap);
            wrap.querySelector('[data-r="1"]').focus();
        });
    }

    /* ---------- payment form helpers ---------- */

    // Shared conditional fields. Each design supplies its own buyer + method selector around them.
    function paymentFieldsHtml(id) {
        var statuses = BP.CHEQUE_STATUSES.map(function (s) { return '<option value="' + esc(s) + '">' + esc(s) + '</option>'; }).join('');
        return '' +
        '<div class="row g-3">' +
          f('date', 'col-6', '<label class="form-label" for="' + id + '_date">Payment Date <span class="req">*</span></label>' +
              '<input type="date" class="form-control" id="' + id + '_date" name="date" required>') +
          f('amount', 'col-6', '<label class="form-label" for="' + id + '_amount">Amount <span class="req">*</span></label>' +
              '<div class="peso_prefix"><input type="text" inputmode="decimal" autocomplete="off" class="form-control amount_input" id="' + id + '_amount" name="amount" placeholder="0.00" required></div>') +
          f('ref', 'col-12', '<label class="form-label" for="' + id + '_ref"><span class="lbl_ref">Reference No.</span> <span class="req ref_req">*</span></label>' +
              '<input type="text" class="form-control" id="' + id + '_ref" name="ref" maxlength="' + BP.LIMITS.ref + '" autocomplete="off">' +
              '<div class="hint ref_hint"></div>', 'cash gcash bank') +
          f('bank', 'col-12 col-sm-6', '<label class="form-label" for="' + id + '_bank">Bank Name <span class="req">*</span></label>' +
              '<input type="text" class="form-control" id="' + id + '_bank" name="bank" maxlength="' + BP.LIMITS.bank + '" list="bp_banks" autocomplete="off">', 'cheque') +
          f('chqNo', 'col-12 col-sm-6', '<label class="form-label" for="' + id + '_chqNo">Cheque No. <span class="req">*</span></label>' +
              '<input type="text" class="form-control" id="' + id + '_chqNo" name="chqNo" maxlength="' + BP.LIMITS.chqNo + '" autocomplete="off">', 'cheque') +
          f('chqDate', 'col-6', '<label class="form-label" for="' + id + '_chqDate">Cheque Date <span class="req">*</span></label>' +
              '<input type="date" class="form-control" id="' + id + '_chqDate" name="chqDate">', 'cheque') +
          f('status', 'col-6', '<label class="form-label" for="' + id + '_status">Cheque Status <span class="req">*</span></label>' +
              '<select class="form-select" id="' + id + '_status" name="status">' + statuses + '</select>', 'cheque') +
          f('remarks', 'col-12', '<label class="form-label" for="' + id + '_remarks">Remarks <span class="muted fw-normal">(optional)</span></label>' +
              '<textarea class="form-control" id="' + id + '_remarks" name="remarks" rows="2" maxlength="' + BP.LIMITS.remarks + '"></textarea>') +
        '</div>' +
        '<datalist id="bp_banks"><option value="BDO"><option value="BPI"><option value="Metrobank"><option value="Landbank"><option value="PNB"><option value="Security Bank"><option value="China Bank"><option value="RCBC"><option value="UnionBank"></datalist>';
        function f(name, cls, inner, forMethods) {
            return '<div class="' + cls + '" data-field="' + name + '"' + (forMethods ? ' data-for="' + forMethods + '"' : '') + '>' +
                   inner + '<div class="field_err" id="' + id + '_' + name + '_err" aria-live="polite"></div></div>';
        }
    }

    // Shows only the fields for the chosen method and adjusts the reference label.
    function setMethod(form, method) {
        form.classList.remove('method-cash', 'method-gcash', 'method-cheque', 'method-bank');
        if (method) form.classList.add('method-' + method);
        var lbl = form.querySelector('.lbl_ref'), req = form.querySelector('.ref_req'), hint = form.querySelector('.ref_hint');
        if (lbl) {
            lbl.textContent = method === 'gcash' ? 'GCash Reference No.' : method === 'bank' ? 'Bank Reference / Deposit Slip No.' : 'Official Receipt / Reference No.';
            req.style.display = method === 'cash' ? 'none' : '';
            hint.textContent = method === 'cash' ? 'Optional for cash.' : method === 'gcash' ? 'The 13-digit number from the GCash receipt.' : '';
        }
        clearErrors(form);
    }

    function readForm(form) {
        var out = {};
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.name || el.disabled) return;
            if ((el.type === 'radio' || el.type === 'checkbox') && !el.checked) return;
            out[el.name] = el.value;
        });
        return out;
    }

    function clearErrors(form) {
        Array.prototype.forEach.call(form.querySelectorAll('.has-err'), function (el) { el.classList.remove('has-err'); });
        Array.prototype.forEach.call(form.querySelectorAll('[aria-invalid]'), function (el) { el.removeAttribute('aria-invalid'); });
    }

    // Marks fields with errors and focuses the first one. Returns number of errors shown.
    function showErrors(form, errors) {
        clearErrors(form);
        var first = null, n = 0;
        Object.keys(errors).forEach(function (name) {
            var wrap = form.querySelector('[data-field="' + name + '"]');
            if (!wrap) return;
            wrap.classList.add('has-err');
            var msg = wrap.querySelector('.field_err');
            if (msg) msg.textContent = errors[name];
            var input = wrap.querySelector('input,select,textarea,button');
            if (input) { input.setAttribute('aria-invalid', 'true'); if (msg) input.setAttribute('aria-describedby', msg.id); }
            if (!first) first = input;
            n++;
        });
        if (first && first.focus) first.focus();
        return n;
    }

    function resetPaymentForm(form, method) {
        form.reset();
        var d = form.querySelector('[name="date"]'); if (d) { d.value = BP.todayIso(); d.max = BP.todayIso(); }
        var c = form.querySelector('[name="chqDate"]'); if (c) c.value = BP.todayIso();
        setMethod(form, method);
    }

    // Formats the amount box as the user leaves it: "12500.5" -> "12,500.50"
    function bindAmountFormat(form) {
        var a = form.querySelector('[name="amount"]');
        if (!a) return;
        a.addEventListener('blur', function () {
            var c = BP.parseAmount(a.value);
            if (c !== null && c > 0) a.value = BP.formatAmount(c);
        });
    }

    // Saves a payment; asks before saving a possible duplicate. Resolves with the core result.
    function submitPayment(form, buyerId) {
        var input = readForm(form);
        var res = BP.addPayment(buyerId, input);
        if (res.needsConfirm) {
            return confirmBox('A ' + BP.METHODS[res.value.method].label + ' payment of ' + BP.formatPeso(res.value.amount) +
                ' on ' + BP.formatDate(res.value.date) + ' with the same reference already exists. Save it again anyway?', 'Save anyway')
                .then(function (yes) {
                    if (!yes) return { ok: false, cancelled: true, errors: {} };
                    return BP.addPayment(buyerId, input, { allowDuplicate: true });
                });
        }
        if (!res.ok) showErrors(form, res.errors);
        return Promise.resolve(res);
    }

    // Bootstrap 5.0 drops or garbles show() while a hide transition is still running
    // (e.g. closing one buyer and quickly opening the next). Queue it until hidden instead.
    function bsSafe(el, instance, kind) {
        var hiding = false, pending = null;
        el.addEventListener('hide.bs.' + kind, function () { hiding = true; });
        el.addEventListener('hidden.bs.' + kind, function () {
            hiding = false;
            if (pending) { var fn = pending; pending = null; fn(); }
        });
        return {
            show: function (before) {
                var go = function () { if (before) before(); instance.show(); };
                if (hiding) pending = go; else go();
            },
            hide: function () { pending = null; instance.hide(); }
        };
    }

    function relTime(ts) {
        var s = Math.round((Date.now() - ts) / 1000);
        if (s < 10) return 'just now';
        if (s < 60) return s + 's ago';
        if (s < 3600) return Math.floor(s / 60) + ' min ago';
        return Math.floor(s / 3600) + ' h ago';
    }

    function confirmReset() {
        return confirmBox('Reset all demo data? Every payment and cheque change made in this preview will be discarded.', 'Reset demo data', true);
    }

    window.UI = {
        esc: esc, buyerBadge: buyerBadge, payBadge: payBadge, amountClass: amountClass, methodIcon: methodIcon, payDetail: payDetail,
        toast: toast, confirmBox: confirmBox, confirmReset: confirmReset,
        paymentFieldsHtml: paymentFieldsHtml, setMethod: setMethod, readForm: readForm, showErrors: showErrors, clearErrors: clearErrors,
        resetPaymentForm: resetPaymentForm, bindAmountFormat: bindAmountFormat, submitPayment: submitPayment, relTime: relTime, bsSafe: bsSafe
    };
})();
