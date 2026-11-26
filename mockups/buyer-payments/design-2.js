/* Design 2 - Customer Ledger Workspace (PHASE 1 UI MOCKUP ONLY; in-memory data) */
(function () {
    'use strict';
    const esc = UI.esc, peso = BP.formatPeso, amt = BP.formatAmount, d = BP.formatDate;
    const $ = (s, r) => (r || document).querySelector(s);
    const PAGE = 60;
    const state = { current: null, q: '', st: 'All', lg: 'all', tab: 'ledger', limit: PAGE, lastAdded: null };
    const form = $('#payForm');
    const csvModal = bootstrap.Modal.getOrCreateInstance($('#csvModal'));

    $('#pf_fields').innerHTML = UI.paymentFieldsHtml('pf');
    UI.bindAmountFormat(form);

    /* ---------- buyer index ---------- */
    function renderIndex() {
        let list = BP.searchBuyers(state.q).map((b) => ({ b, s: BP.summarize(b) }));
        if (state.st !== 'All') list = list.filter((x) => x.s.status === state.st);
        list.sort((a, b) => a.b.name.localeCompare(b.b.name));
        $('#idx_count').textContent = list.length + ' shown';
        if (!list.length) { $('#idx_list').innerHTML = '<div class="empty_state"><i class="bi bi-search"></i>No buyers match.</div>'; return; }
        $('#idx_list').innerHTML = list.slice(0, state.limit).map(({ b, s }) => `
            <div role="listitem"><button type="button" class="idx_item" data-id="${esc(b.id)}" aria-current="${b.id === state.current}" title="${esc(b.name)}">
                <span class="dot ${s.status}" aria-hidden="true"></span>
                <span class="n">${esc(b.name)}</span>
                <span class="o amt ${s.outstanding > 0 ? 'amt-due' : 'amt-collected'}">${peso(Math.max(0, s.outstanding))}<small>${s.status}</small></span>
                <span class="a">${esc(b.account)}</span>
            </button></div>`).join('') +
            (list.length > state.limit ? `<div class="p-2"><button type="button" class="btn btn-ghost w-100" id="idx_more">Show more (${list.length - state.limit})</button></div>` : '');
    }

    /* ---------- statement ---------- */
    function renderStatement() {
        const b = BP.getBuyer(state.current);
        if (!b) return;
        const s = BP.summarize(b);
        document.title = 'Ledger · ' + b.name + ' (Mockup)';
        $('#sw_name').textContent = b.name;
        $('#a_name').textContent = b.name;
        $('#a_acct').textContent = b.account;
        $('#a_asof').textContent = d(BP.todayIso());
        $('#a_contact').textContent = b.contact || '-';
        $('#a_last').textContent = d(s.lastCollection);
        $('#a_status').innerHTML = UI.buyerBadge(s);
        $('#addp_for').textContent = b.name;

        const big = (label, value, formula) => `<div class="t"><div class="k">${label}</div>
            <div class="v amt ${value <= 0 ? 'zero' : ''}">${peso(Math.max(0, value))}${value < 0 ? ` <span class="badge_s s-credit">Credit ${peso(-value)}</span>` : ''}</div><div class="f">${formula}</div></div>`;
        $('#balances').innerHTML = `
            <div class="bal">
                <div class="t sales"><div class="k">Total DR (${s.salesCount})</div><div class="v amt">${peso(s.sales)}</div></div>
                <div class="t col"><div class="k"><i class="bi bi-check2-circle"></i> Collected / Cleared (${s.collectedCount})</div><div class="v amt">${peso(s.collected)}</div></div>
                <div class="t hand"><div class="k"><i class="bi bi-hourglass-split"></i> Cheques on Hand (${s.onHandCount})</div><div class="v amt">${peso(s.onHand)}</div></div>
                <div class="t ret"><div class="k"><i class="bi bi-arrow-return-left"></i> Returned Cheques (${s.returnedCount})</div><div class="v amt">${peso(s.returned)}</div></div>
            </div>
            <div class="bal2">
                ${big('Net Balance <span class="fw-normal muted">(less Cheques on Hand)</span>', s.net, `Total DR ${amt(s.sales)} − (Cheques on Hand ${amt(s.onHand)} + Collected ${amt(s.collected)}) = <b>${amt(s.net)}</b>`)}
                ${big('Outstanding Balance <span class="fw-normal muted">(Collected only)</span>', s.outstanding, `Total DR ${amt(s.sales)} − Collected ${amt(s.collected)} = <b>${amt(s.outstanding)}</b>`)}
            </div>
            <p class="note"><i class="bi bi-info-circle"></i> Returned cheques (Closed Account / Bounced) are not counted in either balance.</p>`;

        const cheques = b.payments.filter((p) => p.method === 'cheque');
        $('#c_dr').textContent = b.sales.length;
        $('#c_pay').textContent = b.payments.length;
        $('#c_chq').textContent = cheques.length;
        renderLedger(b);
        renderDrs(b, s);
        renderPays(b, s);
        renderCheques(b, cheques);
    }

    function renderLedger(b) {
        const led = BP.buildLedger(b);
        const show = (r) => state.lg === 'all' || (state.lg === 'debit' && r.debit) || (state.lg === 'credit' && r.credit) ||
            (state.lg === 'cheque' && r.paymentId && BP.findPayment(b, r.paymentId).method === 'cheque');
        const rows = led.rows.filter(show);
        $('#ledger').innerHTML = `
            <thead><tr><th class="sticky1">Date</th><th>Type</th><th>Particulars</th><th class="r">Debit</th><th class="r">Credit</th>
                <th class="r">Net<br><small class="fw-normal text-lowercase">less chq on hand</small></th><th class="r">Outstanding<br><small class="fw-normal text-lowercase">collected only</small></th></tr></thead>
            <tbody>${rows.length ? rows.map((r) => `
                <tr class="k-${r.kind}${r.paymentId && r.paymentId === state.lastAdded ? ' new' : ''}">
                    <td class="sticky1">${d(r.date)}</td><td>${esc(r.type)}</td><td class="part">${esc(r.particulars)}</td>
                    <td class="r amt dr">${r.debit ? amt(r.debit) : ''}</td><td class="r amt cr">${r.credit ? amt(r.credit) : ''}</td>
                    <td class="r amt net">${amt(r.net)}</td><td class="r amt out">${amt(r.outstanding)}</td></tr>`).join('')
                : '<tr><td colspan="7"><div class="empty_state"><i class="bi bi-journal"></i>No entries for this filter.</div></td></tr>'}</tbody>
            <tfoot><tr><td class="sticky1"></td><td></td><td>TOTAL</td><td class="r amt">${amt(led.totals.debit)}</td><td class="r amt">${amt(led.totals.credit)}</td>
                <td class="r amt net">${amt(led.totals.net)}</td><td class="r amt out">${amt(led.totals.outstanding)}</td></tr></tfoot>`;
    }

    function renderDrs(b, s) {
        $('#drs').innerHTML = `<thead><tr><th>#</th><th>DR No.</th><th>Date</th><th>Remarks</th><th class="r">Debit Amount</th></tr></thead>
            <tbody>${b.sales.map((x, i) => `<tr><td>${i + 1}</td><td class="text-nowrap">${esc(x.drNo)}</td><td>${d(x.date)}</td><td class="wrap-any">${esc(x.remarks || '-')}</td><td class="r amt">${amt(x.amount)}</td></tr>`).join('')}</tbody>
            <tfoot><tr><td colspan="4" class="text-end">TOTAL DEBIT</td><td class="r amt">${amt(s.sales)}</td></tr></tfoot>`;
    }

    function renderPays(b, s) {
        const pays = b.payments.slice().sort((x, y) => x.date.localeCompare(y.date));
        $('#pays').innerHTML = `<thead><tr><th>#</th><th>Date</th><th>Method</th><th>Bank / Cheque / Ref</th><th>Chq Date</th><th>Status</th><th class="r">Amount</th><th>Remarks</th></tr></thead>
            <tbody>${pays.length ? pays.map((p, i) => `<tr class="${p.id === state.lastAdded ? 'flash' : ''}"><td>${i + 1}</td><td>${d(p.date)}</td>
                <td class="text-nowrap">${UI.methodIcon(p.method)} ${esc(BP.METHODS[p.method].label)}</td><td class="wrap-any" style="min-width:160px">${UI.payDetail(p)}</td>
                <td>${p.method === 'cheque' ? d(p.chqDate) : '-'}</td><td>${UI.payBadge(p)}</td>
                <td class="r amt ${UI.amountClass(p)}">${amt(p.amount)}</td><td class="wrap-any" style="min-width:140px">${esc(p.remarks || '-')}</td></tr>`).join('')
                : '<tr><td colspan="8"><div class="empty_state"><i class="bi bi-wallet2"></i>No payments yet. Use <b>Add Payment</b> to record the first one.</div></td></tr>'}</tbody>
            <tfoot><tr><td colspan="6" class="text-end">COLLECTED</td><td class="r amt">${amt(s.collected)}</td><td></td></tr>
                <tr><td colspan="6" class="text-end">CHQ ON HAND</td><td class="r amt amt-onhand">${amt(s.onHand)}</td><td></td></tr>
                <tr><td colspan="6" class="text-end">RETURNED (not counted)</td><td class="r amt amt-returned">${amt(s.returned)}</td><td></td></tr></tfoot>`;
    }

    function daysTo(iso) {
        const t = new Date(BP.todayIso() + 'T00:00:00'), c = new Date(iso + 'T00:00:00');
        return Math.round((c - t) / 86400000);
    }

    function renderCheques(b, cheques) {
        const by = {}; BP.CHEQUE_STATUSES.forEach((st) => { by[st] = 0; });
        cheques.forEach((p) => { by[p.status] += p.amount; });
        $('#chq_sum').innerHTML = BP.CHEQUE_STATUSES.map((st) => `<span>${UI.payBadge({ status: st })} <b class="amt">${peso(by[st])}</b></span>`).join('');
        $('#chqs').innerHTML = `<thead><tr><th>Received</th><th>Bank · Cheque No.</th><th>Chq Date</th><th class="r">Amount</th><th>Status</th><th>Change status</th></tr></thead>
            <tbody>${cheques.length ? cheques.map((p) => {
                const dt = daysTo(p.chqDate), onHand = BP.isOnHand(p);
                const due = onHand ? (dt > 0 ? `<div class="hint">matures in ${dt} day${dt === 1 ? '' : 's'}</div>` : `<div class="hint text-success fw-bold">ready to deposit</div>`) : '';
                return `<tr><td>${d(p.date)}</td><td class="wrap-any" style="min-width:180px">${esc(p.bank)} · #${esc(p.chqNo)}</td><td>${d(p.chqDate)}${due}</td>
                    <td class="r amt ${UI.amountClass(p)}">${amt(p.amount)}</td><td>${UI.payBadge(p)}</td>
                    <td><label class="visually-hidden" for="cs_${esc(p.id)}">Status of cheque ${esc(p.chqNo)}</label>
                    <select class="form-select form-select-sm" style="min-width:150px;min-height:38px;font-size:14px" id="cs_${esc(p.id)}" data-chq="${esc(p.id)}">${BP.CHEQUE_STATUSES.map((st) => `<option ${st === p.status ? 'selected' : ''}>${esc(st)}</option>`).join('')}</select></td></tr>`;
            }).join('') : '<tr><td colspan="6"><div class="empty_state"><i class="bi bi-journal-text"></i>No cheques for this buyer.</div></td></tr>'}</tbody>`;
    }

    function renderAll() { renderIndex(); renderStatement(); }

    /* ---------- navigation ---------- */
    function select(id, fromHash) {
        if (!BP.getBuyer(id)) id = BP.store.buyers[0].id;
        if (id !== state.current) { state.lastAdded = null; closePanel(); }
        state.current = id;
        if (!fromHash && location.hash !== '#' + id) history.pushState(null, '', '#' + id);
        renderAll();
        closeIndex();
    }
    window.addEventListener('popstate', () => select(location.hash.slice(1), true));

    function setTab(name, focus) {
        state.tab = name;
        document.querySelectorAll('#tabs [role="tab"]').forEach((t) => {
            const on = t.id === 'tab-' + name;
            t.setAttribute('aria-selected', on); t.tabIndex = on ? 0 : -1;
            $('#' + t.getAttribute('aria-controls')).hidden = !on;
            if (on && focus) t.focus();
        });
    }
    $('#tabs').addEventListener('click', (e) => { const t = e.target.closest('[role="tab"]'); if (t) setTab(t.id.slice(4)); });
    $('#tabs').addEventListener('keydown', (e) => {
        const tabs = [...document.querySelectorAll('#tabs [role="tab"]')], i = tabs.indexOf(document.activeElement);
        if (i < 0) return;
        const to = { ArrowRight: (i + 1) % tabs.length, ArrowLeft: (i - 1 + tabs.length) % tabs.length, Home: 0, End: tabs.length - 1 }[e.key];
        if (to !== undefined) { e.preventDefault(); setTab(tabs[to].id.slice(4), true); }
    });

    /* mobile buyer index (slide-over) */
    function openIndex() { $('#idx').classList.add('open'); $('#idx_backdrop').classList.add('open'); $('#sw_open').setAttribute('aria-expanded', 'true'); setTimeout(() => $('#idx_q').focus(), 60); }
    function closeIndex() {
        if (!$('#idx').classList.contains('open')) return;
        $('#idx').classList.remove('open'); $('#idx_backdrop').classList.remove('open'); $('#sw_open').setAttribute('aria-expanded', 'false'); $('#sw_open').focus();
    }
    $('#sw_open').addEventListener('click', openIndex);
    $('#idx_close').addEventListener('click', closeIndex);
    $('#idx_backdrop').addEventListener('click', closeIndex);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && $('#idx').classList.contains('open') && !document.querySelector('.cfm')) closeIndex(); });

    let qTimer;
    $('#idx_q').addEventListener('input', (e) => { clearTimeout(qTimer); qTimer = setTimeout(() => { state.q = e.target.value; state.limit = PAGE; renderIndex(); }, 120); });
    $('#idx_q').addEventListener('keydown', (e) => { if (e.key === 'ArrowDown') { e.preventDefault(); const f = $('#idx_list .idx_item'); if (f) f.focus(); } });
    $('#idx_filters').addEventListener('click', (e) => {
        const c = e.target.closest('[data-st]'); if (!c) return;
        state.st = c.dataset.st; state.limit = PAGE;
        document.querySelectorAll('#idx_filters .chip').forEach((x) => x.setAttribute('aria-pressed', x === c));
        renderIndex();
    });
    $('#idx_list').addEventListener('click', (e) => {
        if (e.target.closest('#idx_more')) { state.limit += PAGE; renderIndex(); return; }
        const it = e.target.closest('.idx_item'); if (it) select(it.dataset.id);
    });
    $('#idx_list').addEventListener('keydown', (e) => { // arrow keys move through the buyer list
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        const items = [...document.querySelectorAll('#idx_list .idx_item')], i = items.indexOf(document.activeElement);
        if (i < 0) return;
        e.preventDefault();
        const n = items[i + (e.key === 'ArrowDown' ? 1 : -1)];
        if (n) n.focus(); else if (e.key === 'ArrowUp') $('#idx_q').focus();
    });

    $('.lg_tools').addEventListener('click', (e) => {
        const c = e.target.closest('[data-lg]'); if (!c) return;
        state.lg = c.dataset.lg;
        document.querySelectorAll('.lg_tools .chip').forEach((x) => x.setAttribute('aria-pressed', x === c));
        renderLedger(BP.getBuyer(state.current));
    });

    /* ---------- inline add payment ---------- */
    function openPanel() {
        setTab('pay');
        UI.resetPaymentForm(form, 'cash');
        $('#m_cash').checked = true;
        $('#addp').classList.add('open'); $('#addp').setAttribute('aria-hidden', 'false');
        $('#addp').scrollIntoView({ block: 'nearest', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        setTimeout(() => $('#pf_amount').focus({ preventScroll: true }), 60);
    }
    function closePanel() {
        $('#addp').classList.remove('open'); $('#addp').setAttribute('aria-hidden', 'true');
        UI.clearErrors(form);
    }
    $('#btn_add').addEventListener('click', openPanel);
    $('#pf_cancel').addEventListener('click', () => { closePanel(); form.reset(); $('#btn_add').focus(); });
    form.addEventListener('keydown', (e) => { if (e.key === 'Escape') { e.preventDefault(); closePanel(); $('#btn_add').focus(); } });
    form.addEventListener('change', (e) => { if (e.target.name === 'method') UI.setMethod(form, e.target.value); });
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const btn = $('#pf_save'); btn.disabled = true;
        UI.submitPayment(form, state.current).then((res) => {
            btn.disabled = false;
            if (!res.ok) return;
            state.lastAdded = res.payment.id;
            closePanel();
            renderAll();
            const st = BP.paymentState(res.payment);
            UI.toast(`${peso(res.payment.amount)} ${BP.METHODS[res.payment.method].label} recorded.` +
                (st === 'collected' ? ' Counted as collected.' : st === 'onhand' ? ' Cheque on hand — not yet collected.' : ' Returned cheque — not counted.'), st === 'returned' ? 'warn' : 'ok');
        });
    });

    /* cheque status */
    $('#chqs').addEventListener('change', (e) => {
        const sel = e.target.closest('[data-chq]'); if (!sel) return;
        const b = BP.getBuyer(state.current), p = BP.findPayment(b, sel.dataset.chq), to = sel.value;
        if (!p) return;
        const apply = () => {
            BP.setChequeStatus(b.id, p.id, to);
            renderAll();
            const again = document.getElementById('cs_' + p.id); if (again) again.focus();
            UI.toast(`Chq #${p.chqNo}: ${to}.` + (/Bounced|Closed/.test(to) ? ' Added back to the balance (ledger shows the add-back).' : to === 'Cleared' ? ' Now counted as collected.' : ' Still on hand.'), /Bounced|Closed/.test(to) ? 'warn' : 'ok');
        };
        if (/Bounced|Closed/.test(to)) UI.confirmBox(`Mark ${p.bank} cheque #${p.chqNo} (${peso(p.amount)}) as ${to}?`, 'Mark as ' + to, true).then((y) => { if (y) apply(); else sel.value = p.status; });
        else apply();
    });

    /* ---------- header actions ---------- */
    $('#btn_print').addEventListener('click', () => {
        const prev = state.lg;
        state.lg = 'all'; renderLedger(BP.getBuyer(state.current));
        UI.toast('Print preview — opens the browser print dialog. Not a production PDF.', 'warn');
        setTimeout(() => {
            window.print();
            state.lg = prev; renderLedger(BP.getBuyer(state.current));
        }, 50);
    });
    $('#btn_csv').addEventListener('click', () => { $('#csv_text').textContent = BP.toCsv(BP.getBuyer(state.current)); csvModal.show(); });
    $('#btn_back').addEventListener('click', () => UI.toast('Back is not wired in the mockup.', 'warn'));
    $('#btn_bulk').addEventListener('click', () => { BP.loadBulk(300); renderIndex(); UI.toast(`Loaded 300 sample buyers (${BP.store.buyers.length} total).`, 'ok'); });
    $('#btn_reset').addEventListener('click', () => UI.confirmReset().then((y) => {
        if (!y) return;
        BP.reset(); state.q = ''; $('#idx_q').value = ''; state.st = 'All'; state.limit = PAGE; state.lastAdded = null;
        document.querySelectorAll('#idx_filters .chip').forEach((x) => x.setAttribute('aria-pressed', x.dataset.st === 'All'));
        closePanel();
        select(BP.getBuyer(state.current) ? state.current : 'b1', true);
        UI.toast('Demo data reset.', 'ok');
    }));

    select(location.hash.slice(1) || 'b1', true);
    setTab('ledger');
})();
