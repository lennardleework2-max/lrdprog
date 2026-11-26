/* Design 3 - Collections Workspace (PHASE 1 UI MOCKUP ONLY; in-memory data) */
(function () {
    'use strict';
    const esc = UI.esc, peso = BP.formatPeso;
    const $ = (s) => document.querySelector(s);
    const state = { q: '', f: 'due', sel: null, method: 'cash' };
    const METHOD_KEYS = ['cash', 'gcash', 'cheque', 'bank'];

    function renderToday() {
        const p = BP.portfolio(BP.store.buyers);
        const st = (k, v, cls) => `<div class="st"><div class="k">${k}</div><div class="v amt ${cls}">${v}</div></div>`;
        $('#today').innerHTML = st('Outstanding', peso(p.outstanding), 'amt-due') + st('Collected', peso(p.collected), 'amt-collected') +
            st('Cheques on hand', peso(p.onHand), 'amt-onhand') + st('Returned', peso(p.returned), 'text-danger') +
            st('Logged this session', BP.store.activity.length + ' actions', '');
    }

    function renderCards() {
        let list = BP.searchBuyers(state.q).map((b) => ({ b, s: BP.summarize(b) }));
        const f = state.f;
        list = list.filter(({ s }) => f === 'due' ? s.outstanding > 0 : f === 'onhand' ? s.onHand > 0 : f === 'returned' ? s.returned > 0 : s.status === f);
        list.sort((a, b) => b.s.outstanding - a.s.outstanding);
        $('#cards').innerHTML = list.length ? list.map(({ b, s }) => `
            <button type="button" class="bcard" data-id="${esc(b.id)}" aria-pressed="${b.id === state.sel}">
                <span class="n">${esc(b.name)}</span>
                <span class="o amt ${s.outstanding > 0 ? 'amt-due' : 'amt-collected'}">${peso(Math.max(0, s.outstanding))}</span>
                <span class="meter" aria-hidden="true"><span class="m-col" style="width:${s.pctCollected}%"></span></span>
                <span class="flags">${UI.buyerBadge(s)}${s.onHand ? `<span class="badge_s s-hold"><i class="bi bi-hourglass-split"></i>${peso(s.onHand)} on hand</span>` : ''}${s.returned ? `<span class="badge_s s-returned"><i class="bi bi-arrow-return-left"></i>returned</span>` : ''}</span>
            </button>`).join('') : '<div class="empty_state"><i class="bi bi-inbox"></i>Nothing in this queue.</div>';
    }

    function renderPanel() {
        const b = BP.getBuyer(state.sel);
        if (!b) { $('#qp').innerHTML = '<div class="qp_empty"><i class="bi bi-hand-index"></i>Pick a buyer card to record a payment.</div>'; return; }
        const s = BP.summarize(b);
        const recent = b.payments.slice().sort((x, y) => y.date.localeCompare(x.date)).slice(0, 4);
        $('#qp').innerHTML = `
            <div class="qp_head">
                <div class="d-flex justify-content-between align-items-start gap-2"><small class="muted">${esc(b.account)}</small>
                    <button type="button" class="btn-close close_m" id="qp_close" aria-label="Close"></button></div>
                <h3>${esc(b.name)}</h3>${UI.buyerBadge(s)}
                <div class="qp_nums">
                    <div><small>Outstanding</small><b class="amt amt-due">${peso(Math.max(0, s.outstanding))}</b></div>
                    <div><small>On hand</small><b class="amt amt-onhand">${peso(s.onHand)}</b></div>
                    <div><small>Net</small><b class="amt">${peso(Math.max(0, s.net))}</b></div>
                </div>
            </div>
            <form id="qpForm" novalidate>
                <div class="mseg" role="radiogroup" aria-label="Payment method">${METHOD_KEYS.map((m) =>
                    `<button type="button" role="radio" data-m="${m}" aria-checked="${m === state.method}">${UI.methodIcon(m)}${m === 'bank' ? 'Bank' : esc(BP.METHODS[m].label)}</button>`).join('')}</div>
                <input type="hidden" name="method" value="${state.method}">
                <div class="quick">${s.outstanding > 0 ? `<button type="button" data-fill="${s.outstanding}">Full ${peso(s.outstanding)}</button><button type="button" data-fill="${Math.round(s.outstanding / 2)}">50%</button>` : ''}</div>
                ${UI.paymentFieldsHtml('qp')}
                <button type="submit" class="btn btn-pay w-100 mt-3"><i class="bi bi-check2"></i> Save payment</button>
            </form>
            <div class="px-3 pb-3"><div class="small fw-bold muted mb-1">RECENT</div>${recent.length ? recent.map((p) =>
                `<div class="d-flex justify-content-between gap-2 small py-1 border-bottom"><span class="wrap-any">${BP.formatDate(p.date)} · ${esc(BP.METHODS[p.method].label)} ${UI.payBadge(p)}</span><b class="amt ${UI.amountClass(p)}">${peso(p.amount)}</b></div>`).join('') : '<div class="small muted">No payments yet.</div>'}</div>`;
        const form = $('#qpForm');
        UI.resetPaymentForm(form, state.method);
        UI.bindAmountFormat(form);
    }

    function renderBoard() {
        const cols = [['Hold', ['Hold'], '#a65b00'], ['For Deposit', ['For Deposit'], '#1d5fbf'], ['Cleared', ['Cleared'], '#157a43'], ['Returned', ['Bounced', 'Closed Account'], '#b42318']];
        const all = [];
        BP.store.buyers.forEach((b) => b.payments.forEach((p) => { if (p.method === 'cheque') all.push({ b, p }); }));
        $('#board').innerHTML = cols.map(([title, sts, c]) => {
            const items = all.filter((x) => sts.includes(x.p.status));
            const total = items.reduce((n, x) => n + x.p.amount, 0);
            return `<div class="col_b"><h4><span>${title} (${items.length})</span><span class="amt">${peso(total)}</span></h4>${items.map(({ b, p }) => `
                <div class="chq" style="--c:${c}"><div class="who">${esc(b.name)}</div><div class="ref">${esc(p.bank)} · #${esc(p.chqNo)} · dated ${BP.formatDate(p.chqDate)}</div>
                <div class="d-flex justify-content-between align-items-center mt-1"><b class="amt ${UI.amountClass(p)}">${peso(p.amount)}</b>${UI.payBadge(p)}</div>
                <div class="acts">${actions(p).map(([to, label, cls]) => `<button type="button" class="btn btn-sm ${cls}" data-b="${esc(b.id)}" data-p="${esc(p.id)}" data-to="${to}">${label}</button>`).join('')}</div></div>`).join('') || '<div class="small muted p-2">None</div>'}</div>`;
        }).join('');
    }
    function actions(p) {
        if (p.status === 'Hold') return [['For Deposit', 'Mark for deposit', 'btn-outline-primary']];
        if (p.status === 'For Deposit') return [['Cleared', 'Cleared', 'btn-outline-success'], ['Bounced', 'Bounced', 'btn-outline-danger'], ['Closed Account', 'Closed acct', 'btn-outline-danger']];
        return [['Hold', 'Undo → Hold', 'btn-ghost']];
    }

    function renderActivity() {
        const color = { payment: '#157a43', cleared: '#157a43', returned: '#b42318', status: '#1d5fbf', system: '#94a3b8' };
        $('#tl').innerHTML = BP.store.activity.length ? BP.store.activity.map((a) =>
            `<li style="--c:${color[a.kind] || '#94a3b8'}"><div class="t">${UI.relTime(a.at)}${a.buyerName ? ' · ' + esc(a.buyerName) : ''}</div><div>${esc(a.text)}</div></li>`).join('')
            : '<li><div class="muted">No activity yet. Record a payment or move a cheque to see it here.</div></li>';
    }

    function renderAll() { renderToday(); renderCards(); renderPanel(); renderBoard(); renderActivity(); }

    function setView(v) {
        document.querySelectorAll('[role="tab"][data-view]').forEach((t) => t.setAttribute('aria-selected', t.dataset.view === v));
        ['queue', 'cheques', 'activity'].forEach((x) => { $('#v-' + x).hidden = x !== v; });
        if (v === 'activity') renderActivity();
    }

    /* events */
    document.addEventListener('click', (e) => {
        const t = e.target.closest('[role="tab"][data-view]'); if (t) setView(t.dataset.view);
    });
    $('#q').addEventListener('input', (e) => { state.q = e.target.value; renderCards(); });
    $('#chips').addEventListener('click', (e) => {
        const c = e.target.closest('[data-f]'); if (!c) return;
        state.f = c.dataset.f;
        document.querySelectorAll('#chips .chip').forEach((x) => x.setAttribute('aria-pressed', x === c));
        renderCards();
    });
    $('#cards').addEventListener('click', (e) => {
        const c = e.target.closest('.bcard'); if (!c) return;
        state.sel = c.dataset.id; renderCards(); renderPanel();
        $('#qp').classList.add('open');
        setTimeout(() => { const a = $('#qp_amount'); if (a) a.focus({ preventScroll: true }); }, 80);
    });
    $('#qp').addEventListener('click', (e) => {
        if (e.target.closest('#qp_close')) { $('#qp').classList.remove('open'); return; }
        const m = e.target.closest('[data-m]');
        if (m) {
            state.method = m.dataset.m;
            const form = $('#qpForm');
            form.elements.method.value = state.method;
            form.querySelectorAll('[data-m]').forEach((x) => x.setAttribute('aria-checked', x === m));
            UI.setMethod(form, state.method);
        }
        const f = e.target.closest('[data-fill]');
        if (f) $('#qp_amount').value = BP.formatAmount(+f.dataset.fill);
    });
    $('#qp').addEventListener('submit', (e) => {
        e.preventDefault();
        UI.submitPayment($('#qpForm'), state.sel).then((res) => {
            if (!res.ok) return;
            UI.toast(`${peso(res.payment.amount)} saved for ${BP.getBuyer(state.sel).name}.`, 'ok');
            renderAll();
        });
    });
    $('#board').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-to]'); if (!btn) return;
        const { b, p, to } = btn.dataset;
        const go = () => { BP.setChequeStatus(b, p, to); renderAll(); UI.toast(`Cheque moved to ${to}.`, /Bounced|Closed/.test(to) ? 'warn' : 'ok'); };
        if (/Bounced|Closed/.test(to)) UI.confirmBox(`Mark this cheque as ${to}? It will not count as collected.`, 'Mark as ' + to, true).then((y) => y && go());
        else go();
    });
    $('#btn_reset').addEventListener('click', () => UI.confirmReset().then((y) => {
        if (!y) return; BP.reset(); state.sel = null; renderAll(); UI.toast('Demo data reset.', 'ok');
    }));
    document.addEventListener('keydown', (e) => {
        if (e.target.matches('input,textarea,select')) { if (e.key === 'Escape') e.target.blur(); return; }
        if (e.key === '/') { e.preventDefault(); setView('queue'); $('#q').focus(); }
        if (e.key === 'Escape') $('#qp').classList.remove('open');
    });

    renderAll();
})();
