// Unit tests for bp-core.js (in-memory mock data only; no DB, no network).
// Run: node --test mockups/buyer-payments/tests/core.test.js
const test = require('node:test');
const assert = require('node:assert/strict');
const BP = require('../bp-core.js');

const TODAY = '2026-11-19';
const peso = (n) => Math.round(n * 100);
const fresh = () => { BP.reset(); return BP; };

test('Customer A matches the client PDF figures', () => {
    fresh();
    const s = BP.summarize(BP.getBuyer('b1'));
    assert.equal(s.sales, peso(115000));
    assert.equal(s.collected, peso(80000));
    assert.equal(s.onHand, peso(20000));
    assert.equal(s.returned, peso(15000));
    assert.equal(s.net, peso(15000));
    assert.equal(s.outstanding, peso(35000));
    assert.equal(s.status, 'Partial');
    assert.equal(s.lastCollection, '2026-10-11');
    assert.deepEqual([s.salesCount, s.collectedCount, s.onHandCount, s.returnedCount], [3, 3, 2, 1]);
});

test('Customer A running ledger matches PDF totals and order', () => {
    fresh();
    const led = BP.buildLedger(BP.getBuyer('b1'));
    assert.equal(led.totals.debit, peso(130000));
    assert.equal(led.totals.credit, peso(115000));
    assert.equal(led.totals.net, peso(15000));
    assert.equal(led.totals.outstanding, peso(35000));
    const dates = led.rows.map((r) => r.date);
    assert.deepEqual(dates, ['2026-09-25', '2026-09-28', '2026-10-01', '2026-10-05', '2026-10-10', '2026-10-11',
        '2026-10-21', '2026-10-23', '2026-11-05', '2026-11-08']);
    const nets = led.rows.map((r) => r.net / 100);
    assert.deepEqual(nets, [100000, 105000, 55000, 65000, 45000, 35000, 20000, 35000, 25000, 15000]);
    const outs = led.rows.map((r) => r.outstanding / 100);
    assert.deepEqual(outs, [100000, 105000, 55000, 65000, 45000, 35000, 20000, 35000, 35000, 35000]);
});

test('Paid / Partial / Unpaid status follows collected amounts only', () => {
    fresh();
    const st = (id) => BP.summarize(BP.getBuyer(id)).status;
    assert.equal(st('b2'), 'Paid');
    assert.equal(st('b3'), 'Partial');
    assert.equal(st('b4'), 'Unpaid');
    assert.equal(st('b8'), 'Unpaid', 'cheque on hold must not count as collected');
    const s9 = BP.summarize(BP.getBuyer('b9'));
    assert.equal(s9.status, 'Paid');
    assert.equal(s9.credit, peso(500));
});

test('Hold -> Cleared moves the amount from on-hand to collected', () => {
    fresh();
    const r = BP.setChequeStatus('b1', 'p5', 'Cleared', TODAY);
    assert.ok(r.ok && r.changed);
    const s = BP.summarize(BP.getBuyer('b1'));
    assert.equal(s.collected, peso(90000));
    assert.equal(s.onHand, peso(10000));
    assert.equal(s.outstanding, peso(25000));
    assert.equal(s.net, peso(15000), 'net unchanged when an on-hand cheque clears');
});

test('Cleared -> Bounced removes it from collected and does not reduce outstanding', () => {
    fresh();
    BP.setChequeStatus('b1', 'p5', 'Cleared', TODAY);
    BP.setChequeStatus('b1', 'p5', 'Bounced', TODAY);
    const s = BP.summarize(BP.getBuyer('b1'));
    assert.equal(s.collected, peso(80000));
    assert.equal(s.returned, peso(25000));
    assert.equal(s.outstanding, peso(35000));
    assert.equal(s.net, peso(25000));
    const led = BP.buildLedger(BP.getBuyer('b1'));
    assert.equal(led.totals.net, s.net);
    assert.equal(led.totals.outstanding, s.outstanding);
});

test('Ledger totals agree with summary for every seeded buyer, also after changes', () => {
    fresh();
    BP.setChequeStatus('b6', 'p16', 'Cleared', TODAY);
    BP.setChequeStatus('b5', 'p14', 'Closed Account', TODAY);
    for (const b of BP.store.buyers) {
        const s = BP.summarize(b), t = BP.buildLedger(b).totals;
        assert.equal(t.net, s.net, b.name + ' net');
        assert.equal(t.outstanding, s.outstanding, b.name + ' outstanding');
    }
});

test('Adding a valid cash payment updates balances', () => {
    fresh();
    const r = BP.addPayment('b4', { date: '2026-11-18', amount: '8,300.75', method: 'cash', ref: '', remarks: '' }, { today: TODAY });
    assert.ok(r.ok, JSON.stringify(r.errors));
    const s = BP.summarize(BP.getBuyer('b4'));
    assert.equal(s.collected, peso(8300.75));
    assert.equal(s.outstanding, peso(10000));
    assert.equal(s.status, 'Partial');
    assert.equal(BP.store.activity.length, 1);
});

test('Adding a cheque on Hold does not count as collected', () => {
    fresh();
    const r = BP.addPayment('b11', { date: TODAY, amount: '10000', method: 'cheque', bank: 'RCBC', chqNo: '555', chqDate: '2026-12-01', status: 'Hold' }, { today: TODAY });
    assert.ok(r.ok, JSON.stringify(r.errors));
    const s = BP.summarize(BP.getBuyer('b11'));
    assert.equal(s.collected, 0);
    assert.equal(s.onHand, peso(10000));
    assert.equal(s.status, 'Unpaid');
});

test('Invalid amounts are rejected', () => {
    fresh();
    const b = BP.getBuyer('b1');
    const bad = ['', '   ', '0', '0.00', '-50', 'abc', '12.345', '1e5', '1000000000', 'NaN', '1.2.3', null, undefined];
    for (const amount of bad) {
        const r = BP.validatePayment({ date: TODAY, amount, method: 'cash' }, b, TODAY);
        assert.equal(r.ok, false, 'should reject ' + JSON.stringify(amount));
        assert.ok(r.errors.amount, 'amount error for ' + JSON.stringify(amount));
    }
    assert.equal(BP.parseAmount('999,999,999.99'), 99999999999);
    assert.equal(BP.parseAmount('₱ 1,234.5'), 123450);
});

test('Required fields per method', () => {
    fresh();
    const b = BP.getBuyer('b1');
    let r = BP.validatePayment({}, null, TODAY);
    assert.ok(r.errors.buyer && r.errors.date && r.errors.amount && r.errors.method);
    r = BP.validatePayment({ date: TODAY, amount: '100', method: 'gcash' }, b, TODAY);
    assert.ok(r.errors.ref);
    r = BP.validatePayment({ date: TODAY, amount: '100', method: 'bank' }, b, TODAY);
    assert.ok(r.errors.ref);
    r = BP.validatePayment({ date: TODAY, amount: '100', method: 'cheque' }, b, TODAY);
    assert.ok(r.errors.bank && r.errors.chqNo && r.errors.chqDate && r.errors.status);
    r = BP.validatePayment({ date: TODAY, amount: '100', method: 'cash' }, b, TODAY);
    assert.ok(r.ok, 'cash with no ref/remarks (optional) is valid');
    r = BP.validatePayment({ date: '2026-02-30', amount: '100', method: 'cash' }, b, TODAY);
    assert.ok(r.errors.date);
    r = BP.validatePayment({ date: '2026-12-25', amount: '100', method: 'cash' }, b, TODAY);
    assert.ok(r.errors.date, 'future payment date rejected');
    r = BP.validatePayment({ date: TODAY, amount: '100', method: 'cash', remarks: 'x'.repeat(201) }, b, TODAY);
    assert.ok(r.errors.remarks);
});

test('Duplicate cheque is blocked; possible duplicate payment needs confirmation', () => {
    fresh();
    let r = BP.addPayment('b1', { date: TODAY, amount: '100', method: 'cheque', bank: 'bdo', chqNo: '0001001', chqDate: TODAY, status: 'Hold' }, { today: TODAY });
    assert.equal(r.ok, false);
    assert.ok(r.errors.chqNo);
    BP.addPayment('b4', { date: TODAY, amount: '100', method: 'cash' }, { today: TODAY });
    r = BP.addPayment('b4', { date: TODAY, amount: '100', method: 'cash' }, { today: TODAY });
    assert.equal(r.ok, false);
    assert.equal(r.needsConfirm, true);
    r = BP.addPayment('b4', { date: TODAY, amount: '100', method: 'cash' }, { today: TODAY, allowDuplicate: true });
    assert.equal(r.ok, true);
    assert.equal(BP.getBuyer('b4').payments.length, 2);
});

test('Unknown cheque status / payment is rejected', () => {
    fresh();
    assert.equal(BP.setChequeStatus('b1', 'p5', 'Lost').ok, false);
    assert.equal(BP.setChequeStatus('b1', 'p1', 'Cleared').ok, false, 'non-cheque payment');
    assert.equal(BP.setChequeStatus('b1', 'nope', 'Cleared').ok, false);
});

test('escapeHtml neutralises markup, quotes and keeps accents / peso sign', () => {
    assert.equal(BP.escapeHtml('<script>alert("x")</script>'), '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;');
    assert.equal(BP.escapeHtml("O'Brien & Ñiño ₱"), 'O&#39;Brien &amp; Ñiño ₱');
    assert.equal(BP.escapeHtml(null), '');
});

test('Formatting', () => {
    assert.equal(BP.formatPeso(11500000), '₱115,000.00');
    assert.equal(BP.formatPeso(-50000), '-₱500.00');
    assert.equal(BP.formatPeso(5), '₱0.05');
    assert.equal(BP.formatDate('2026-09-25'), '09/25/2026');
    assert.equal(BP.formatDate(''), '-');
});

test('Search is case- and accent-insensitive and matches account numbers', () => {
    fresh();
    assert.equal(BP.searchBuyers('nino')[0].id, 'b7');
    assert.equal(BP.searchBuyers('LUCIA')[0].id, 'b6');
    assert.equal(BP.searchBuyers('dbt-000003')[0].id, 'b3');
    assert.equal(BP.searchBuyers('zzz-no-match').length, 0);
    assert.equal(BP.searchBuyers('').length, BP.store.buyers.length);
});

test('CSV preview escapes quotes/commas and blocks formula injection', () => {
    fresh();
    const b = BP.getBuyer('b1');
    b.sales[0].remarks = '=HYPERLINK("x"), test';
    const csv = BP.toCsv(b);
    assert.match(csv, /"DR #1 · =HYPERLINK\(""x""\), test"/);
    const b2 = BP.getBuyer('b2');
    b2.sales[0].drNo = '=cmd';
    b2.sales[0].remarks = '';
    assert.match(BP.toCsv(b2), /,'=cmd,/);
});

test('Reset restores seed data and clears activity', () => {
    fresh();
    BP.addPayment('b4', { date: TODAY, amount: '1', method: 'cash' }, { today: TODAY });
    BP.loadBulk(50);
    BP.reset();
    assert.equal(BP.store.buyers.length, 12);
    assert.equal(BP.getBuyer('b4').payments.length, 0);
    assert.equal(BP.store.activity.length, 0);
    assert.equal(BP.summarize(BP.getBuyer('b1')).outstanding, peso(35000));
});

test('Large dataset (500 extra buyers) summarizes quickly and consistently', () => {
    fresh();
    BP.loadBulk(500);
    const t0 = performance.now();
    const p = BP.portfolio(BP.store.buyers);
    const ms = performance.now() - t0;
    assert.equal(p.buyers, 512);
    assert.equal(p.Paid + p.Partial + p.Unpaid, 512);
    assert.ok(ms < 100, 'portfolio took ' + ms.toFixed(1) + 'ms');
    for (const b of BP.store.buyers.slice(12, 60)) {
        const s = BP.summarize(b), t = BP.buildLedger(b).totals;
        assert.equal(t.net, s.net); assert.equal(t.outstanding, s.outstanding);
    }
});
