/* Design 1 - Buyer Payment Dashboard (PHASE 1 UI MOCKUP ONLY; in-memory data) */
(function () {
    'use strict';
    const esc = UI.esc, peso = BP.formatPeso;
    const $ = (s, r) => (r || document).querySelector(s);
    const PAGE = 50;
    const state = { q: '', status: 'All', sort: 'outstanding', limit: PAGE, current: null, payBuyer: null };

    const drawer = UI.bsSafe($('#drawer'), bootstrap.Offcanvas.getOrCreateInstance($('#drawer')), 'offcanvas');
    const payModal = UI.bsSafe($('#payModal'), bootstrap.Modal.getOrCreateInstance($('#payModal')), 'modal');
    const csvModal = bootstrap.Modal.getOrCreateInstance($('#csvModal'));
    const form = $('#payForm');

    $('#asof').textContent = BP.formatDate(BP.todayIso());
    $('#pm_fields').innerHTML = UI.paymentFieldsHtml('pm');
    UI.bindAmountFormat(form);

    /* ---------- list ---------- */
    function rowsData() {
        let list = BP.searchBuyers(state.q).map((b) => ({ b, s: BP.summarize(b) }));
        const counts = { All: list.length, Paid: 0, Partial: 0, Unpaid: 0 };
        list.forEach((x) => counts[x.s.status]++);
        if (state.status !== 'All') list = list.filter((x) => x.s.status === state.status);
        const by = {
            outstanding: (a, b) => b.s.outstanding - a.s.outstanding,
            name: (a, b) => a.b.name.localeCompare(b.b.name),
            recent: (a, b) => (b.s.lastCollection || '').localeCompare(a.s.lastCollection || ''),
            pct: (a, b) => a.s.pctCollected - b.s.pctCollected
        }[state.sort];
        list.sort(by);
        return { list, counts };
    }

    function renderKpis() {
        const p = BP.portfolio(BP.store.buyers);
        const k = (cls, icon, label, value, sub) =>
            `<div class="kpi ${cls}"><div class="k"><span class="ic"><i class="bi ${icon}" aria-hidden="true"></i></span>${label}</div>` +
            `<div class="v amt">${peso(value)}</div><div class="s">${sub}</div></div>`;
        $('#kpis').innerHTML =
            k('k-sales', 'bi-receipt', 'Total Sales', p.sales, `${p.buyers} buyers`) +
            k('k-col', 'bi-check2-circle', 'Collected / Cleared', p.collected, 'Cash, GCash, bank &amp; cleared cheques') +
            k('k-hand', 'bi-hourglass-split', 'Cheques on Hand', p.onHand, 'Hold &amp; For Deposit — not yet collected') +
            k('k-ret', 'bi-arrow-return-left', 'Returned Cheques', p.returned, 'Bounced / Closed Account — still owed') +
            k('k-out', 'bi-exclamation-diamond', 'Outstanding', p.outstanding, `Net after cheques on hand: <b class="amt">${peso(p.net)}</b>`);
    }

    function renderMix() {
        const p = BP.portfolio(BP.store.buyers);
        const seg = (st, icon) => p[st] ? `<button type="button" class="${st}" style="flex-grow:${p[st]}" data-status="${st}" aria-pressed="${state.status === st}" title="${st}: ${p[st]} buyers"><i class="bi ${icon}" aria-hidden="true"></i>${st} · ${p[st]}</button>` : '';
        $('#mix_bar').innerHTML = seg('Paid', 'bi-check-circle-fill') + seg('Partial', 'bi-circle-half') + seg('Unpaid', 'bi-exclamation-circle-fill');
    }

    function renderTable() {
        const { list, counts } = rowsData();
        $('#seg').innerHTML = ['All', 'Paid', 'Partial', 'Unpaid'].map((s) =>
            `<button type="button" data-status="${s}" aria-pressed="${state.status === s}">${s}<span class="cnt">${counts[s]}</span></button>`).join('');
        if (!list.length) {
            $('#rows').innerHTML = `<tr><td colspan="8"><div class="empty_state"><i class="bi bi-search"></i>No buyers match ${state.q ? '“' + esc(state.q) + '”' : 'this filter'}.<br><button type="button" class="btn btn-ghost btn-sm mt-2" data-clear>Clear search &amp; filter</button></div></td></tr>`;
            $('#more').innerHTML = '';
            return;
        }
        $('#rows').innerHTML = list.slice(0, state.limit).map(({ b, s }) => `
            <tr tabindex="0" data-id="${esc(b.id)}" aria-label="Open ${esc(b.name)}">
                <td class="td_main" data-label="Buyer"><div class="b_name wrap-any">${esc(b.name)}</div><div class="b_acct">${esc(b.account)}${s.lastCollection ? ' · last paid ' + BP.formatDate(s.lastCollection) : ' · no collection yet'}</div></td>
                <td class="td_status" data-label="Status">${UI.buyerBadge(s)}</td>
                <td class="r amt" data-label="Total Sales">${peso(s.sales)}</td>
                <td class="r amt amt-collected" data-label="Collected">${peso(s.collected)}</td>
                <td class="r amt hide-md ${s.onHand ? 'amt-onhand' : 'muted'}" data-label="Chq on Hand">${s.onHand ? peso(s.onHand) : '—'}</td>
                <td class="r amt fw-bold ${s.outstanding > 0 ? 'amt-due' : ''}" data-label="Outstanding">${peso(Math.max(0, s.outstanding))}</td>
                <td class="col_meter hide-md" data-label="Collected %"><div class="meter" aria-hidden="true"><span class="m-col" style="width:${s.pctCollected}%"></span>${s.onHand && s.sales ? `<span class="m-hand" style="width:${Math.min(100 - s.pctCollected, Math.round(s.onHand * 100 / s.sales))}%"></span>` : ''}</div><small>${s.pctCollected}% collected</small></td>
                <td class="r td_act"><button type="button" class="btn btn-sm btn-outline-success fw-bold" data-pay="${esc(b.id)}"><i class="bi bi-plus-lg"></i> Pay</button></td>
            </tr>`).join('');
        $('#more').innerHTML = list.length > state.limit
            ? `<div class="more_row"><button type="button" class="btn btn-ghost" id="btn_more">Show more (${list.length - state.limit} remaining)</button></div>`
            : `<div class="more_row muted small">${list.length} buyer${list.length === 1 ? '' : 's'}</div>`;
    }

    function renderAll() { renderKpis(); renderMix(); renderTable(); if (state.current) renderDrawer(); }

    /* ---------- drawer ---------- */
    function renderDrawer() {
        const b = BP.getBuyer(state.current);
        if (!b) return;
        const s = BP.summarize(b);
        $('#d_acct').textContent = b.account + ' · Contact: ' + (b.contact || '-');
        $('#d_title').textContent = b.name;
        $('#d_badge').innerHTML = UI.buyerBadge(s);
        const tile = (k, v, cls, sub) => `<div class="d_tile"><div class="k">${k}</div><div class="v amt ${cls || ''}">${peso(v)}</div>${sub ? `<div class="hint">${sub}</div>` : ''}</div>`;
        const pays = b.payments.slice().sort((x, y) => y.date.localeCompare(x.date));
        const payHtml = pays.length ? pays.map((p) => `
            <div class="pay_item" data-pid="${esc(p.id)}">
                <span class="ic" title="${esc(BP.METHODS[p.method].label)}">${UI.methodIcon(p.method)}</span>
                <div class="t wrap-any">${esc(BP.METHODS[p.method].label)} <span class="m">· ${BP.formatDate(p.date)}</span>
                    <div class="m wrap-any">${UI.payDetail(p)}${p.method === 'cheque' ? ' · dated ' + BP.formatDate(p.chqDate) : ''}${p.remarks ? ' · ' + esc(p.remarks) : ''}</div></div>
                <div class="a"><div class="amt ${UI.amountClass(p)}">${peso(p.amount)}</div>${UI.payBadge(p)}</div>
                ${p.method === 'cheque' ? `<label class="visually-hidden" for="cs_${esc(p.id)}">Cheque status</label><select class="form-select form-select-sm" id="cs_${esc(p.id)}" data-chq="${esc(p.id)}">${BP.CHEQUE_STATUSES.map((st) => `<option ${st === p.status ? 'selected' : ''}>${esc(st)}</option>`).join('')}</select>` : ''}
            </div>`).join('') : '<div class="empty_state"><i class="bi bi-wallet2"></i>No payments recorded yet.</div>';
        const drHtml = b.sales.map((d) => `<div class="dr_item"><span class="wrap-any">${esc(d.drNo)} <span class="muted">· ${BP.formatDate(d.date)}${d.remarks ? ' · ' + esc(d.remarks) : ''}</span></span><b class="amt">${peso(d.amount)}</b></div>`).join('');
        $('#d_body').innerHTML = `
            <div class="d_tiles">
                ${tile(`Total Sales (${s.salesCount})`, s.sales)}
                ${tile(`Collected / Cleared (${s.collectedCount})`, s.collected, 'amt-collected')}
                ${tile(`Cheques on Hand (${s.onHandCount})`, s.onHand, 'amt-onhand')}
                ${tile(`Returned Cheques (${s.returnedCount})`, s.returned, s.returned ? 'text-danger' : '', 'Not deducted from balance')}
            </div>
            <div class="d_big">
                ${tile('Net Balance', Math.max(0, s.net), s.net > 0 ? 'amt-due' : '', 'less cheques on hand')}
                ${tile('Outstanding', Math.max(0, s.outstanding), s.outstanding > 0 ? 'amt-due' : '', 'collected only')}
            </div>
            <div class="meter mt-3" aria-hidden="true"><span class="m-col" style="width:${s.pctCollected}%"></span></div>
            <div class="formula">${peso(s.sales)} sales − ${peso(s.collected)} collected = <b>${peso(s.outstanding)}</b> outstanding · ${s.pctCollected}% collected</div>
            <div class="d_sec">Payment history</div>${payHtml}
            <div class="d_sec">Sales (DR) · ${peso(s.sales)}</div>${drHtml}`;
    }

    function openDrawer(id) { drawer.show(() => { state.current = id; renderDrawer(); }); }

    /* ---------- add payment ---------- */
    function pickBuyer(id) {
        state.payBuyer = id;
        const b = BP.getBuyer(id);
        const input = $('#pm_buyer');
        input.value = b ? b.name : '';
        closeCombo();
        if (b) {
            const s = BP.summarize(b);
            $('#pm_picked').innerHTML = `<div class="picked"><span class="wrap-any"><b>${esc(b.account)}</b> · ${UI.buyerBadge(s)}</span><span class="amt">Outstanding <b class="amt-due">${peso(Math.max(0, s.outstanding))}</b></span></div>`;
            const wrap = form.querySelector('[data-field="buyer"]'); wrap.classList.remove('has-err');
        } else $('#pm_picked').innerHTML = '';
        updateAfter();
    }

    function openPay(buyerId) {
        payModal.show(() => {
            UI.resetPaymentForm(form, 'cash');
            $('#pm_method').value = 'cash';
            pickBuyer(buyerId || null);
        });
    }

    // live "after this payment" preview
    function updateAfter() {
        const b = BP.getBuyer(state.payBuyer), el = $('#pm_after');
        const amt = BP.parseAmount(form.elements.amount.value);
        if (!b) { el.innerHTML = '<i class="bi bi-info-circle"></i> Choose a buyer to see the balance after this payment.'; return; }
        const s = BP.summarize(b), method = $('#pm_method').value, st = form.elements.status.value;
        if (!amt) { el.innerHTML = `<i class="bi bi-info-circle"></i> Current outstanding: <b class="amt">${peso(s.outstanding)}</b>`; return; }
        if (method === 'cheque' && st !== 'Cleared') {
            el.innerHTML = /Bounced|Closed/.test(st)
                ? `<i class="bi bi-exclamation-triangle"></i> A returned cheque does <b>not</b> reduce the balance. Outstanding stays <b class="amt">${peso(s.outstanding)}</b>.`
                : `<i class="bi bi-hourglass-split"></i> Cheque on hand — not collected yet. Outstanding stays <b class="amt">${peso(s.outstanding)}</b>; net balance becomes <b class="amt">${peso(s.net - amt)}</b>.`;
        } else {
            const after = s.outstanding - amt;
            el.innerHTML = `<i class="bi bi-arrow-right-circle"></i> Outstanding after this payment: <b class="amt">${peso(Math.max(0, after))}</b>${after < 0 ? ` <span class="badge_s s-credit">Credit ${peso(-after)}</span>` : ''} → <b>${after <= 0 ? 'Paid' : 'Partial'}</b>`;
        }
    }

    /* buyer combobox */
    let comboItems = [], comboIdx = -1;
    function renderCombo() {
        const q = $('#pm_buyer').value;
        comboItems = BP.searchBuyers(q).slice(0, 8);
        comboIdx = comboItems.length ? 0 : -1;
        $('#pm_list').innerHTML = comboItems.length ? comboItems.map((b, i) => {
            const s = BP.summarize(b);
            return `<div role="option" id="opt_${i}" data-id="${esc(b.id)}" aria-selected="${i === comboIdx}"><span class="wrap-any">${esc(b.name)}</span><span class="amt muted">${peso(Math.max(0, s.outstanding))}</span></div>`;
        }).join('') : '<div class="p-2 muted">No buyer found.</div>';
        $('#pm_list').classList.add('open');
        $('#pm_buyer').setAttribute('aria-expanded', 'true');
        syncComboActive();
    }
    function syncComboActive() {
        $('#pm_list').querySelectorAll('[role="option"]').forEach((o, i) => o.setAttribute('aria-selected', i === comboIdx));
        const input = $('#pm_buyer');
        if (comboIdx >= 0) { input.setAttribute('aria-activedescendant', 'opt_' + comboIdx); const o = $('#opt_' + comboIdx); if (o) o.scrollIntoView({ block: 'nearest' }); }
        else input.removeAttribute('aria-activedescendant');
    }
    function closeCombo() { $('#pm_list').classList.remove('open'); $('#pm_buyer').setAttribute('aria-expanded', 'false'); }

    $('#pm_buyer').addEventListener('input', () => { state.payBuyer = null; $('#pm_picked').innerHTML = ''; renderCombo(); updateAfter(); });
    $('#pm_buyer').addEventListener('focus', renderCombo);
    $('#pm_buyer').addEventListener('keydown', (e) => {
        const open = $('#pm_list').classList.contains('open');
        if (e.key === 'ArrowDown') { e.preventDefault(); if (!open) renderCombo(); else { comboIdx = Math.min(comboItems.length - 1, comboIdx + 1); syncComboActive(); } }
        else if (e.key === 'ArrowUp') { e.preventDefault(); comboIdx = Math.max(0, comboIdx - 1); syncComboActive(); }
        else if (e.key === 'Enter' && open) { e.preventDefault(); if (comboItems[comboIdx]) pickBuyer(comboItems[comboIdx].id); }
        else if (e.key === 'Escape' && open) { e.preventDefault(); e.stopPropagation(); closeCombo(); }
    });
    $('#pm_list').addEventListener('mousedown', (e) => e.preventDefault()); // keep focus in input
    $('#pm_list').addEventListener('click', (e) => { const o = e.target.closest('[data-id]'); if (o) pickBuyer(o.dataset.id); });
    $('#pm_buyer').addEventListener('blur', () => setTimeout(closeCombo, 120));

    $('#pm_method').addEventListener('change', (e) => { UI.setMethod(form, e.target.value); updateAfter(); });
    form.addEventListener('input', (e) => { if (e.target.name === 'amount' || e.target.name === 'status') updateAfter(); });
    form.addEventListener('change', (e) => { if (e.target.name === 'status') updateAfter(); });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!state.payBuyer) { UI.showErrors(form, Object.assign({ buyer: 'Select a buyer from the list.' }, BP.validatePayment(UI.readForm(form), {payments: []}).errors)); return; }
        const btn = $('#pm_save'); btn.disabled = true;
        UI.submitPayment(form, state.payBuyer).then((res) => {
            btn.disabled = false;
            if (!res.ok) return;
            const b = BP.getBuyer(state.payBuyer);
            payModal.hide();
            renderAll();
            UI.toast(`${peso(res.payment.amount)} ${BP.METHODS[res.payment.method].label} saved for ${b.name}` + (res.payment.method === 'cheque' ? ` (cheque: ${res.payment.status})` : ''), 'ok');
            const row = document.querySelector(`#rows tr[data-id="${CSS.escape(b.id)}"]`);
            if (row) row.classList.add('flash');
        });
    });

    /* ---------- events ---------- */
    let qTimer;
    $('#q').addEventListener('input', (e) => { clearTimeout(qTimer); qTimer = setTimeout(() => { state.q = e.target.value; state.limit = PAGE; renderTable(); }, 120); });
    $('#sort').addEventListener('change', (e) => { state.sort = e.target.value; renderTable(); });
    function setStatus(st) { state.status = state.status === st && st !== 'All' ? 'All' : st; state.limit = PAGE; renderMix(); renderTable(); }
    $('#seg').addEventListener('click', (e) => { const b = e.target.closest('[data-status]'); if (b) setStatus(b.dataset.status); });
    $('#mix_bar').addEventListener('click', (e) => { const b = e.target.closest('[data-status]'); if (b) setStatus(b.dataset.status); });

    $('#rows').addEventListener('click', (e) => {
        if (e.target.closest('[data-clear]')) { state.q = ''; $('#q').value = ''; state.status = 'All'; renderMix(); renderTable(); return; }
        const pay = e.target.closest('[data-pay]');
        if (pay) { e.stopPropagation(); openPay(pay.dataset.pay); return; }
        const tr = e.target.closest('tr[data-id]');
        if (tr) openDrawer(tr.dataset.id);
    });
    $('#rows').addEventListener('keydown', (e) => {
        const tr = e.target.closest('tr[data-id]');
        if (tr && e.target === tr && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openDrawer(tr.dataset.id); }
    });
    $('#more').addEventListener('click', (e) => { if (e.target.closest('#btn_more')) { state.limit += PAGE; renderTable(); } });

    $('#d_body').addEventListener('change', (e) => {
        const sel = e.target.closest('[data-chq]');
        if (!sel) return;
        const b = BP.getBuyer(state.current), p = BP.findPayment(b, sel.dataset.chq), to = sel.value;
        if (!p) return;
        const apply = () => {
            BP.setChequeStatus(b.id, p.id, to);
            renderAll();
            UI.toast(`Chq #${p.chqNo} is now ${to}.` + (/Bounced|Closed/.test(to) ? ' Amount added back to outstanding.' : to === 'Cleared' ? ' Counted as collected.' : ' Not counted as collected.'), /Bounced|Closed/.test(to) ? 'warn' : 'ok');
        };
        if (/Bounced|Closed/.test(to)) {
            UI.confirmBox(`Mark ${p.bank} cheque #${p.chqNo} (${peso(p.amount)}) as ${to}? It will not count as a collection.`, 'Mark as ' + to, true)
                .then((yes) => { if (yes) apply(); else sel.value = p.status; });
        } else apply();
    });

    $('#btn_add').addEventListener('click', () => openPay(null));
    $('#d_add').addEventListener('click', () => { const id = state.current; drawer.hide(); openPay(id); });
    $('#d_print').addEventListener('click', () => UI.toast('Print Ledger is a preview-only action in this mockup. Design 2 shows the full print layout.', 'warn'));
    $('#d_csv').addEventListener('click', () => { $('#csv_text').textContent = BP.toCsv(BP.getBuyer(state.current)); csvModal.show(); });
    $('#btn_back').addEventListener('click', () => UI.toast('Back is not wired in the mockup.', 'warn'));
    $('#btn_bulk').addEventListener('click', () => { const n = BP.loadBulk(300); renderAll(); UI.toast(`Loaded 300 sample buyers (${n} total). Showing ${PAGE} at a time.`, 'ok'); });
    $('#btn_reset').addEventListener('click', () => UI.confirmReset().then((yes) => {
        if (!yes) return;
        BP.reset(); state.q = ''; $('#q').value = ''; state.status = 'All'; state.limit = PAGE;
        if (state.current && !BP.getBuyer(state.current)) { state.current = null; drawer.hide(); }
        renderAll(); UI.toast('Demo data reset.', 'ok');
    }));
    $('#payModal').addEventListener('shown.bs.modal', () => { (state.payBuyer ? form.elements.amount : $('#pm_buyer')).focus(); });

    renderAll();
})();
