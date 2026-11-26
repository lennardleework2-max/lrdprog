/*
 * Buyer Payments mockup - shared, DOM-free logic (PHASE 1 UI MOCKUP ONLY).
 * Fictitious in-memory data. Nothing is saved, sent, or logged anywhere.
 * Money is stored in integer centavos to avoid floating-point drift.
 * Works in the browser (window.BP) and in Node (module.exports) for tests.
 */
(function (root) {
    'use strict';

    var METHODS = {
        cash:   { label: 'Cash',                  icon: 'bi-cash-stack' },
        gcash:  { label: 'GCash',                 icon: 'bi-phone' },
        cheque: { label: 'Cheque',                icon: 'bi-journal-text' },
        bank:   { label: 'Bank Transfer/Deposit', icon: 'bi-bank' }
    };
    var CHEQUE_STATUSES = ['Hold', 'For Deposit', 'Cleared', 'Bounced', 'Closed Account'];
    var ON_HAND  = { 'Hold': 1, 'For Deposit': 1 };
    var RETURNED = { 'Bounced': 1, 'Closed Account': 1 };
    var MAX_CENTAVOS = 99999999999; // 999,999,999.99
    var LIMITS = { ref: 50, bank: 60, chqNo: 30, remarks: 200 };

    /* ---------- formatting ---------- */

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // 1234567 -> "12,345.67"
    function formatAmount(centavos) {
        var c = Math.abs(Math.round(centavos || 0));
        var whole = String(Math.floor(c / 100)).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        var cents = String(c % 100);
        return (centavos < 0 ? '-' : '') + whole + '.' + (cents.length < 2 ? '0' + cents : cents);
    }

    function formatPeso(centavos) {
        var s = formatAmount(centavos);
        return s.charAt(0) === '-' ? '-₱' + s.slice(1) : '₱' + s;
    }

    // "2026-09-25" -> "09/25/2026" (LRD display convention)
    function formatDate(iso) {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '-';
        return iso.slice(5, 7) + '/' + iso.slice(8, 10) + '/' + iso.slice(0, 4);
    }

    function todayIso() {
        var d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function isValidIsoDate(iso) {
        if (typeof iso !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return false;
        var y = +iso.slice(0, 4), m = +iso.slice(5, 7), d = +iso.slice(8, 10);
        var dt = new Date(y, m - 1, d);
        return y >= 2000 && y <= 2099 && dt.getMonth() === m - 1 && dt.getDate() === d;
    }

    // "12,500.5" / "₱ 12500.50" -> 1250050 ; invalid -> null
    function parseAmount(raw) {
        if (raw == null) return null;
        var s = String(raw).replace(/₱/g, '').replace(/[\s,]/g, '');
        if (!/^\d{1,9}(\.\d{1,2})?$/.test(s)) return null;
        var parts = s.split('.');
        var cents = parts[1] ? (parts[1] + '0').slice(0, 2) : '00';
        return parseInt(parts[0], 10) * 100 + parseInt(cents, 10);
    }

    function cleanText(v, max) {
        // trim, drop control characters, cap length
        return String(v == null ? '' : v).replace(/[\u0000-\u001F\u007F]/g, ' ').trim().slice(0, max);
    }

    /* ---------- balances ---------- */

    function isCollected(p) { return p.method === 'cheque' ? p.status === 'Cleared' : p.status === 'Received'; }
    function isOnHand(p)    { return p.method === 'cheque' && !!ON_HAND[p.status]; }
    function isReturned(p)  { return p.method === 'cheque' && !!RETURNED[p.status]; }

    function paymentState(p) {
        if (isCollected(p)) return 'collected';
        if (isOnHand(p)) return 'onhand';
        if (isReturned(p)) return 'returned';
        return 'other';
    }

    function summarize(buyer) {
        var s = { sales: 0, collected: 0, onHand: 0, returned: 0,
                  salesCount: buyer.sales.length, collectedCount: 0, onHandCount: 0, returnedCount: 0,
                  lastCollection: '' };
        buyer.sales.forEach(function (d) { s.sales += d.amount; });
        buyer.payments.forEach(function (p) {
            if (isCollected(p)) {
                s.collected += p.amount; s.collectedCount++;
                var when = p.method === 'cheque' ? (p.clearedOn || p.chqDate || p.date) : p.date;
                if (when > s.lastCollection) s.lastCollection = when;
            } else if (isOnHand(p)) { s.onHand += p.amount; s.onHandCount++; }
            else if (isReturned(p)) { s.returned += p.amount; s.returnedCount++; }
        });
        s.outstanding = s.sales - s.collected;          // collected only
        s.net = s.sales - s.collected - s.onHand;       // less cheques on hand
        s.status = s.outstanding <= 0 ? 'Paid' : (s.collected === 0 ? 'Unpaid' : 'Partial');
        s.credit = s.outstanding < 0 ? -s.outstanding : 0;
        s.pctCollected = s.sales > 0 ? Math.min(100, Math.round(s.collected * 100 / s.sales)) : 100;
        return s;
    }

    function portfolio(buyers) {
        var t = { sales: 0, collected: 0, onHand: 0, returned: 0, outstanding: 0, net: 0,
                  Paid: 0, Partial: 0, Unpaid: 0, buyers: buyers.length };
        buyers.forEach(function (b) {
            var s = summarize(b);
            t.sales += s.sales; t.collected += s.collected; t.onHand += s.onHand; t.returned += s.returned;
            t.outstanding += Math.max(0, s.outstanding); t.net += Math.max(0, s.net); t[s.status]++;
        });
        return t;
    }

    /*
     * Debit/Credit running ledger, following the client's PDF:
     *  - sales are debits
     *  - received payments / cleared cheques are credits (reduce Net and Outstanding)
     *  - cheques on hand are credits that reduce Net only
     *  - a returned cheque is a credit (on deposit) followed by an "added back" debit
     */
    function buildLedger(buyer) {
        var rows = [], seq = 0;
        buyer.sales.forEach(function (d) {
            rows.push({ date: d.date, seq: seq++, order: 0, type: 'Sale', kind: 'sale',
                        particulars: d.drNo + (d.remarks ? ' · ' + d.remarks : ''), debit: d.amount, credit: 0, affectsOutstanding: true });
        });
        buyer.payments.forEach(function (p) {
            var label = methodLabel(p);
            if (p.method !== 'cheque') {
                rows.push({ date: p.date, seq: seq++, order: 1, type: 'Payment', kind: 'collected', particulars: label,
                            debit: 0, credit: p.amount, affectsOutstanding: true, paymentId: p.id });
            } else if (p.status === 'Cleared') {
                rows.push({ date: p.clearedOn || p.chqDate || p.date, seq: seq++, order: 1, type: 'Payment', kind: 'collected',
                            particulars: label + ' (cleared)', debit: 0, credit: p.amount, affectsOutstanding: true, paymentId: p.id });
            } else if (ON_HAND[p.status]) {
                rows.push({ date: p.date, seq: seq++, order: 1, type: 'Cheque', kind: 'onhand',
                            particulars: label + ' [' + p.status + ']', debit: 0, credit: p.amount, affectsOutstanding: false, paymentId: p.id });
            } else if (RETURNED[p.status]) {
                var dep = p.depositedOn || p.chqDate || p.date;
                rows.push({ date: dep, seq: seq++, order: 1, type: 'Payment', kind: 'collected',
                            particulars: label + ' (deposited)', debit: 0, credit: p.amount, affectsOutstanding: true, paymentId: p.id });
                rows.push({ date: p.returnedOn && p.returnedOn >= dep ? p.returnedOn : dep, seq: seq++, order: 2, type: 'Returned', kind: 'returned',
                            particulars: 'Returned chq - ' + p.status + ' (added back)', debit: p.amount, credit: 0, affectsOutstanding: true, paymentId: p.id });
            }
        });
        rows.sort(function (a, b) {
            return a.date < b.date ? -1 : a.date > b.date ? 1 : (a.order - b.order) || (a.seq - b.seq);
        });
        var net = 0, out = 0, totDebit = 0, totCredit = 0;
        rows.forEach(function (r) {
            net += r.debit - r.credit;
            out += r.debit - (r.affectsOutstanding ? r.credit : 0);
            totDebit += r.debit; totCredit += r.credit;
            r.net = net; r.outstanding = out;
        });
        return { rows: rows, totals: { debit: totDebit, credit: totCredit, net: net, outstanding: out } };
    }

    function methodLabel(p) {
        if (p.method === 'cheque') return 'Cheque - ' + (p.bank || '?') + ' · Chq #' + (p.chqNo || '?');
        var base = METHODS[p.method] ? METHODS[p.method].label : p.method;
        return p.ref ? base + ' · Ref ' + p.ref : base;
    }

    /* ---------- validation ---------- */

    function validatePayment(input, buyer, today) {
        input = input || {};
        today = today || todayIso();
        var errors = {}, v = {};

        if (!buyer) errors.buyer = 'Select a buyer.';

        v.date = cleanText(input.date, 10);
        if (!v.date) errors.date = 'Payment date is required.';
        else if (!isValidIsoDate(v.date)) errors.date = 'Enter a valid date.';
        else if (v.date > today) errors.date = 'Payment date cannot be in the future.';

        var rawAmt = cleanText(input.amount, 30);
        v.amount = parseAmount(rawAmt);
        if (!rawAmt) errors.amount = 'Amount is required.';
        else if (/^-/.test(rawAmt)) errors.amount = 'Amount cannot be negative.';
        else if (v.amount === null) errors.amount = 'Use numbers only, up to 2 decimals (e.g. 12,500.00).';
        else if (v.amount === 0) errors.amount = 'Amount must be greater than zero.';
        else if (v.amount > MAX_CENTAVOS) errors.amount = 'Amount is too large.';

        v.method = cleanText(input.method, 10);
        if (!METHODS[v.method]) errors.method = 'Choose a payment method.';

        v.ref = cleanText(input.ref, LIMITS.ref);
        v.remarks = cleanText(input.remarks, LIMITS.remarks);
        if (String(input.remarks || '').trim().length > LIMITS.remarks) errors.remarks = 'Remarks can be up to ' + LIMITS.remarks + ' characters.';

        if ((v.method === 'gcash' || v.method === 'bank') && !v.ref) {
            errors.ref = v.method === 'gcash' ? 'GCash reference number is required.' : 'Bank reference / deposit slip no. is required.';
        }

        if (v.method === 'cheque') {
            v.bank = cleanText(input.bank, LIMITS.bank);
            v.chqNo = cleanText(input.chqNo, LIMITS.chqNo);
            v.chqDate = cleanText(input.chqDate, 10);
            v.status = cleanText(input.status, 20);
            if (!v.bank) errors.bank = 'Bank name is required.';
            if (!v.chqNo) errors.chqNo = 'Cheque number is required.';
            if (!v.chqDate) errors.chqDate = 'Cheque date is required.';
            else if (!isValidIsoDate(v.chqDate)) errors.chqDate = 'Enter a valid cheque date.';
            if (CHEQUE_STATUSES.indexOf(v.status) < 0) errors.status = 'Choose a cheque status.';
            if (buyer && v.bank && v.chqNo && buyer.payments.some(function (p) {
                return p.method === 'cheque' && p.bank.toLowerCase() === v.bank.toLowerCase() && p.chqNo.toLowerCase() === v.chqNo.toLowerCase();
            })) errors.chqNo = 'This cheque (' + v.bank + ' #' + v.chqNo + ') is already recorded for this buyer.';
        } else {
            v.status = 'Received';
        }

        var ok = Object.keys(errors).length === 0;
        var warnDuplicate = ok && v.method !== 'cheque' && buyer.payments.some(function (p) {
            return p.method === v.method && p.date === v.date && p.amount === v.amount && (p.ref || '') === v.ref;
        });
        return { ok: ok, errors: errors, value: v, warnDuplicate: warnDuplicate };
    }

    /* ---------- in-memory store ---------- */

    var store = { buyers: [], activity: [], nextId: 1 };

    function reset() {
        store.buyers = seedBuyers();
        store.activity = [];
        store.nextId = 1000;
        return store;
    }

    function getBuyer(id) {
        for (var i = 0; i < store.buyers.length; i++) if (store.buyers[i].id === id) return store.buyers[i];
        return null;
    }

    function findPayment(buyer, paymentId) {
        for (var i = 0; buyer && i < buyer.payments.length; i++) if (buyer.payments[i].id === paymentId) return buyer.payments[i];
        return null;
    }

    function log(kind, buyer, text) {
        store.activity.unshift({ at: Date.now(), kind: kind, buyerId: buyer ? buyer.id : null, buyerName: buyer ? buyer.name : '', text: text });
        if (store.activity.length > 200) store.activity.length = 200;
    }

    // opts.allowDuplicate = true after the user confirms a possible duplicate
    function addPayment(buyerId, input, opts) {
        var buyer = getBuyer(buyerId);
        var res = validatePayment(input, buyer, opts && opts.today);
        if (!res.ok) return res;
        if (res.warnDuplicate && !(opts && opts.allowDuplicate)) { res.ok = false; res.needsConfirm = true; return res; }
        var v = res.value;
        var p = { id: 'p' + (store.nextId++), date: v.date, method: v.method, amount: v.amount,
                  ref: v.ref, remarks: v.remarks, status: v.status, bank: '', chqNo: '', chqDate: '' };
        if (v.method === 'cheque') { p.bank = v.bank; p.chqNo = v.chqNo; p.chqDate = v.chqDate; applyStatusDates(p, v.status, v.date); }
        buyer.payments.push(p);
        log('payment', buyer, formatPeso(p.amount) + ' ' + METHODS[p.method].label + (p.method === 'cheque' ? ' [' + p.status + ']' : '') + ' recorded');
        res.payment = p;
        return res;
    }

    function applyStatusDates(p, status, when) {
        if (status === 'Cleared') { p.clearedOn = when; p.depositedOn = p.depositedOn || when; }
        if (RETURNED[status]) { p.depositedOn = p.depositedOn || when; p.returnedOn = when; }
    }

    function setChequeStatus(buyerId, paymentId, status, when) {
        var buyer = getBuyer(buyerId), p = findPayment(buyer, paymentId);
        if (!p || p.method !== 'cheque') return { ok: false, error: 'Cheque not found.' };
        if (CHEQUE_STATUSES.indexOf(status) < 0) return { ok: false, error: 'Unknown cheque status.' };
        if (p.status === status) return { ok: true, changed: false, payment: p };
        var from = p.status;
        p.status = status;
        delete p.clearedOn; delete p.returnedOn;
        if (ON_HAND[status]) delete p.depositedOn;
        applyStatusDates(p, status, when || todayIso());
        log(RETURNED[status] ? 'returned' : status === 'Cleared' ? 'cleared' : 'status', buyer,
            'Chq #' + p.chqNo + ' (' + formatPeso(p.amount) + '): ' + from + ' → ' + status);
        return { ok: true, changed: true, from: from, payment: p };
    }

    /* ---------- CSV (PREVIEW ONLY - no file is produced) ---------- */

    function csvCell(v) {
        var s = String(v == null ? '' : v);
        if (/^[=+\-@\t\r]/.test(s)) s = "'" + s;          // block spreadsheet formula injection
        return /[",\n\r]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }

    function toCsv(buyer) {
        var led = buildLedger(buyer);
        var lines = [['Date', 'Type', 'Particulars', 'Debit', 'Credit', 'Net (less chq on hand)', 'Outstanding (collected only)']];
        led.rows.forEach(function (r) {
            lines.push([formatDate(r.date), r.type, r.particulars, r.debit ? formatAmount(r.debit) : '',
                        r.credit ? formatAmount(r.credit) : '', formatAmount(r.net), formatAmount(r.outstanding)]);
        });
        lines.push(['', '', 'TOTAL', formatAmount(led.totals.debit), formatAmount(led.totals.credit),
                    formatAmount(led.totals.net), formatAmount(led.totals.outstanding)]);
        return lines.map(function (row) { return row.map(csvCell).join(','); }).join('\r\n');
    }

    /* ---------- sample data (fictitious) ---------- */

    function P(o) { return o; }
    function peso(n) { return Math.round(n * 100); }

    function seedBuyers() {
        var b = [];
        // Reference scenario from the client PDF ("Customer Ledger - Customer A")
        b.push({ id: 'b1', name: 'Customer A', account: 'DBT-000001', contact: '-', route: 'North',
            sales: [
                { id: 's1', drNo: 'DR #1', date: '2026-09-25', remarks: '', amount: peso(100000) },
                { id: 's2', drNo: 'DR #2', date: '2026-09-28', remarks: '', amount: peso(5000) },
                { id: 's3', drNo: 'DR #3', date: '2026-10-05', remarks: '', amount: peso(10000) }
            ],
            payments: [
                P({ id: 'p1', date: '2026-10-01', method: 'bank', amount: peso(50000), ref: '', remarks: '', status: 'Received' }),
                P({ id: 'p2', date: '2026-10-10', method: 'bank', amount: peso(20000), ref: '', remarks: '', status: 'Received' }),
                P({ id: 'p3', date: '2026-10-11', method: 'bank', amount: peso(10000), ref: '', remarks: '', status: 'Received' }),
                P({ id: 'p4', date: '2026-10-20', method: 'cheque', amount: peso(15000), ref: '', remarks: '', status: 'Closed Account',
                    bank: 'BDO', chqNo: '0001001', chqDate: '2026-10-20', depositedOn: '2026-10-21', returnedOn: '2026-10-23' }),
                P({ id: 'p5', date: '2026-11-05', method: 'cheque', amount: peso(10000), ref: '', remarks: '', status: 'Hold',
                    bank: 'Metrobank', chqNo: '0002002', chqDate: '2026-11-15' }),
                P({ id: 'p6', date: '2026-11-08', method: 'cheque', amount: peso(10000), ref: '', remarks: '', status: 'For Deposit',
                    bank: 'BPI', chqNo: '0003003', chqDate: '2026-11-10' })
            ] });
        b.push({ id: 'b2', name: 'Dela Cruz Sari-Sari Store', account: 'DBT-000002', contact: '0917 555 0102', route: 'South',
            sales: [
                { id: 's4', drNo: 'DR #1042', date: '2026-09-12', remarks: 'Rice & canned goods', amount: peso(12500) },
                { id: 's5', drNo: 'DR #1077', date: '2026-10-02', remarks: '', amount: peso(8750.5) },
                { id: 's6', drNo: 'DR #1103', date: '2026-10-19', remarks: '', amount: peso(4200) }
            ],
            payments: [
                P({ id: 'p7', date: '2026-09-20', method: 'cash', amount: peso(10000), ref: 'OR-5521', remarks: '', status: 'Received' }),
                P({ id: 'p8', date: '2026-10-08', method: 'gcash', amount: peso(8000), ref: '1009 882 341', remarks: '', status: 'Received' }),
                P({ id: 'p9', date: '2026-10-25', method: 'cheque', amount: peso(7450.5), ref: '', remarks: 'Final payment', status: 'Cleared',
                    bank: 'Landbank', chqNo: '7781204', chqDate: '2026-10-25', depositedOn: '2026-10-26', clearedOn: '2026-10-28' })
            ] });
        b.push({ id: 'b3', name: 'Mabuhay Grocery Mart', account: 'DBT-000003', contact: '0928 555 0177', route: 'Central',
            sales: [
                { id: 's7', drNo: 'DR #1050', date: '2026-09-15', remarks: '', amount: peso(48000) },
                { id: 's8', drNo: 'DR #1121', date: '2026-10-28', remarks: 'Holiday stock', amount: peso(22500) }
            ],
            payments: [
                P({ id: 'p10', date: '2026-10-01', method: 'gcash', amount: peso(15000), ref: '2210 554 908', remarks: '', status: 'Received' }),
                P({ id: 'p11', date: '2026-11-03', method: 'bank', amount: peso(20000), ref: 'BDO-DEP-88412', remarks: 'Deposit slip received', status: 'Received' })
            ] });
        b.push({ id: 'b4', name: 'Tindahan ni Aling Rosa', account: 'DBT-000004', contact: '-', route: 'South',
            sales: [ { id: 's9', drNo: 'DR #1133', date: '2026-11-02', remarks: '', amount: peso(18300.75) } ],
            payments: [] });
        b.push({ id: 'b5', name: 'Golden Harvest Trading', account: 'DBT-000005', contact: '0999 555 0145', route: 'North',
            sales: [
                { id: 's10', drNo: 'DR #1060', date: '2026-09-18', remarks: '', amount: peso(35000) },
                { id: 's11', drNo: 'DR #1098', date: '2026-10-15', remarks: '', amount: peso(25000) }
            ],
            payments: [
                P({ id: 'p12', date: '2026-09-30', method: 'cash', amount: peso(10000), ref: '', remarks: '', status: 'Received' }),
                P({ id: 'p13', date: '2026-10-16', method: 'cheque', amount: peso(25000), ref: '', remarks: 'Insufficient funds', status: 'Bounced',
                    bank: 'Security Bank', chqNo: '0045512', chqDate: '2026-10-20', depositedOn: '2026-10-20', returnedOn: '2026-10-24' }),
                P({ id: 'p14', date: '2026-11-10', method: 'cheque', amount: peso(25000), ref: '', remarks: 'Replacement cheque', status: 'Hold',
                    bank: 'Security Bank', chqNo: '0045588', chqDate: '2026-11-30' })
            ] });
        b.push({ id: 'b6', name: 'Sta. Lucía Agri-Supply & Trading Corporation – Northern Luzon Distribution Branch', account: 'DBT-000006',
            contact: '(078) 555 0190 loc. 214', route: 'North',
            sales: [
                { id: 's12', drNo: 'DR #1011', date: '2026-09-05', remarks: 'Fertilizer bulk order, 400 bags', amount: peso(145000) },
                { id: 's13', drNo: 'DR #1089', date: '2026-10-11', remarks: '', amount: peso(100000) }
            ],
            payments: [
                P({ id: 'p15', date: '2026-10-05', method: 'bank', amount: peso(100000), ref: 'INSTAPAY-20261005-000981223', remarks: '', status: 'Received' }),
                P({ id: 'p16', date: '2026-11-12', method: 'cheque', amount: peso(50000), ref: '', remarks: 'Post-dated, per agreement with purchasing',
                    status: 'For Deposit', bank: 'Bank of the Philippine Islands – Ayala Ave. Corporate Center Branch', chqNo: '0004567890-A-2026-PDC', chqDate: '2026-11-20' })
            ] });
        b.push({ id: 'b7', name: 'O\'Brien "Quick" Mart — Ñiño\'s ₱ Store', account: 'DBT-000007', contact: '-', route: 'Central',
            sales: [ { id: 's14', drNo: 'DR #1110', date: '2026-10-21', remarks: '', amount: peso(9999.99) } ],
            payments: [
                P({ id: 'p17', date: '2026-10-30', method: 'cash', amount: peso(9999.99), ref: '', remarks: 'Paid in full — "salamat" & thanks <b>not bold</b>', status: 'Received' })
            ] });
        b.push({ id: 'b8', name: 'Bayanihan Hardware Supply', account: 'DBT-000008', contact: '0916 555 0133', route: 'Central',
            sales: [ { id: 's15', drNo: 'DR #1115', date: '2026-10-24', remarks: '', amount: peso(32000) } ],
            payments: [
                P({ id: 'p18', date: '2026-11-14', method: 'cheque', amount: peso(32000), ref: '', remarks: '', status: 'Hold',
                    bank: 'China Bank', chqNo: '3300871', chqDate: '2026-11-28' })
            ] });
        b.push({ id: 'b9', name: 'Pacific Rim Distributors', account: 'DBT-000009', contact: '0918 555 0166', route: 'South',
            sales: [ { id: 's16', drNo: 'DR #1070', date: '2026-09-29', remarks: '', amount: peso(15000) } ],
            payments: [
                P({ id: 'p19', date: '2026-10-06', method: 'bank', amount: peso(15500), ref: 'MBTC-DEP-55120', remarks: 'Overpaid by 500', status: 'Received' })
            ] });
        b.push({ id: 'b10', name: 'Kapitan Tiago Food Hub', account: 'DBT-000010', contact: '-', route: 'North',
            sales: [ { id: 's17', drNo: 'DR #1081', date: '2026-10-07', remarks: '', amount: peso(40000) } ],
            payments: [
                P({ id: 'p20', date: '2026-10-12', method: 'cheque', amount: peso(15000), ref: '', remarks: '', status: 'Closed Account',
                    bank: 'PNB', chqNo: '1200459', chqDate: '2026-10-12', depositedOn: '2026-10-13', returnedOn: '2026-10-16' }),
                P({ id: 'p21', date: '2026-10-29', method: 'cash', amount: peso(5000), ref: '', remarks: '', status: 'Received' })
            ] });
        b.push({ id: 'b11', name: 'Lim & Sons Wholesale', account: 'DBT-000011', contact: '0920 555 0111', route: 'Central',
            sales: [
                { id: 's18', drNo: 'DR #1125', date: '2026-10-30', remarks: '', amount: peso(41250) },
                { id: 's19', drNo: 'DR #1137', date: '2026-11-06', remarks: '', amount: peso(28700) },
                { id: 's20', drNo: 'DR #1142', date: '2026-11-11', remarks: '', amount: peso(18500) }
            ],
            payments: [] });
        b.push({ id: 'b12', name: 'Señora Elena\'s Bakeshop', account: 'DBT-000012', contact: '0935 555 0150', route: 'South',
            sales: [ { id: 's21', drNo: 'DR #1095', date: '2026-10-14', remarks: '', amount: peso(6480) } ],
            payments: [
                P({ id: 'p22', date: '2026-10-16', method: 'gcash', amount: peso(6480), ref: '4410 229 017', remarks: '', status: 'Received' })
            ] });
        return b;
    }

    // Deterministic stress-test buyers (fictitious)
    function makeBulkBuyers(n, startIndex) {
        var out = [], seed = 7;
        function rnd() { seed = (seed * 16807) % 2147483647; return seed / 2147483647; }
        var stores = ['Store', 'Mart', 'Trading', 'Grocery', 'Supply', 'Enterprises', 'Food Hub', 'Wholesale'];
        var places = ['Bulacan', 'Pampanga', 'Cavite', 'Laguna', 'Batangas', 'Quezon', 'Rizal', 'Tarlac', 'Iloilo', 'Cebu'];
        var banks = ['BDO', 'BPI', 'Metrobank', 'Landbank', 'PNB', 'RCBC'];
        startIndex = startIndex || 100;
        for (var i = 0; i < n; i++) {
            var idx = startIndex + i, sales = [], pays = [];
            var nSales = 1 + Math.floor(rnd() * 4);
            for (var s = 0; s < nSales; s++) {
                sales.push({ id: 'bs' + idx + '_' + s, drNo: 'DR #' + (5000 + idx * 4 + s), date: '2026-' + pad(9 + Math.floor(rnd() * 3)) + '-' + pad(1 + Math.floor(rnd() * 28)),
                             remarks: '', amount: Math.round(1000 + rnd() * 90000) * 100 });
            }
            var nPays = Math.floor(rnd() * 4);
            for (var k = 0; k < nPays; k++) {
                var m = ['cash', 'gcash', 'cheque', 'bank'][Math.floor(rnd() * 4)];
                var p = { id: 'bp' + idx + '_' + k, date: '2026-' + pad(9 + Math.floor(rnd() * 3)) + '-' + pad(1 + Math.floor(rnd() * 18)), method: m,
                          amount: Math.round(500 + rnd() * 30000) * 100, ref: m === 'gcash' || m === 'bank' ? 'REF-' + idx + k : '', remarks: '', status: 'Received',
                          bank: '', chqNo: '', chqDate: '' };
                if (m === 'cheque') { p.bank = banks[Math.floor(rnd() * banks.length)]; p.chqNo = String(100000 + idx * 10 + k); p.chqDate = p.date;
                                      p.status = CHEQUE_STATUSES[Math.floor(rnd() * CHEQUE_STATUSES.length)]; applyStatusDates(p, p.status, p.date); }
                pays.push(p);
            }
            out.push({ id: 'bulk' + idx, name: places[idx % places.length] + ' ' + stores[(idx * 7) % stores.length] + ' #' + idx,
                       account: 'DBT-' + String(100000 + idx).slice(1).padStart(6, '0'), contact: '-', route: ['North', 'South', 'Central'][idx % 3], sales: sales, payments: pays });
        }
        return out;
    }

    function loadBulk(n) {
        var start = 100 + store.buyers.length;
        store.buyers = store.buyers.concat(makeBulkBuyers(n, start));
        log('system', null, n + ' sample buyers loaded for stress testing');
        return store.buyers.length;
    }

    function searchBuyers(query, list) {
        var q = String(query || '').trim().toLowerCase();
        list = list || store.buyers;
        if (!q) return list.slice();
        var qn = fold(q);
        return list.filter(function (b) { return fold(b.name.toLowerCase()).indexOf(qn) >= 0 || b.account.toLowerCase().indexOf(q) >= 0; });
    }
    // accent-insensitive search ("nino" finds "Niño")
    function fold(s) { return s.normalize ? s.normalize('NFD').replace(/[̀-ͯ]/g, '') : s; }

    reset();

    var BP = {
        METHODS: METHODS, CHEQUE_STATUSES: CHEQUE_STATUSES, LIMITS: LIMITS, store: store,
        escapeHtml: escapeHtml, formatAmount: formatAmount, formatPeso: formatPeso, formatDate: formatDate, todayIso: todayIso,
        parseAmount: parseAmount, isValidIsoDate: isValidIsoDate,
        summarize: summarize, portfolio: portfolio, buildLedger: buildLedger, methodLabel: methodLabel, paymentState: paymentState,
        isCollected: isCollected, isOnHand: isOnHand, isReturned: isReturned,
        validatePayment: validatePayment, addPayment: addPayment, setChequeStatus: setChequeStatus,
        reset: reset, getBuyer: getBuyer, findPayment: findPayment, searchBuyers: searchBuyers,
        makeBulkBuyers: makeBulkBuyers, loadBulk: loadBulk, toCsv: toCsv, log: log
    };
    if (typeof module !== 'undefined' && module.exports) module.exports = BP;
    else root.BP = BP;
})(this);
