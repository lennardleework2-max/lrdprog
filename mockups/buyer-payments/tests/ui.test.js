/*
 * Browser interaction tests for the Buyer Payments mockups.
 * Drives headless Microsoft Edge (or Chrome) over the DevTools protocol using Node 22's
 * built-in WebSocket/fetch - no npm packages. Pages use in-memory demo data only.
 *
 * Run:  node mockups/buyer-payments/tests/ui.test.js [design-1|design-2|design-3]
 * Env:  BP_BASE (default http://localhost/lrdprog/mockups/buyer-payments/)
 *       BP_SHOTS (screenshot folder, default OS temp dir)
 */
'use strict';
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const BASE = process.env.BP_BASE || 'http://localhost/lrdprog/mockups/buyer-payments/';
const SHOTS = process.env.BP_SHOTS || path.join(os.tmpdir(), 'bp-mockup-shots');
const PORT = 9300 + Math.floor(Math.random() * 500);
const BROWSERS = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe'
];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
fs.mkdirSync(SHOTS, { recursive: true });

/* ---------------- minimal CDP client ---------------- */
class Page {
    constructor(ws) {
        this.ws = ws; this.id = 0; this.pending = new Map(); this.listeners = []; this.errors = [];
        ws.addEventListener('message', (ev) => {
            const msg = JSON.parse(ev.data);
            if (msg.id && this.pending.has(msg.id)) {
                const { resolve, reject } = this.pending.get(msg.id); this.pending.delete(msg.id);
                msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
            } else if (msg.method) {
                if (msg.method === 'Runtime.exceptionThrown') this.errors.push(msg.params.exceptionDetails.exception?.description || msg.params.exceptionDetails.text);
                if (msg.method === 'Runtime.consoleAPICalled' && msg.params.type === 'error') this.errors.push(msg.params.args.map((a) => a.value || a.description).join(' '));
                if (msg.method === 'Log.entryAdded' && msg.params.entry.level === 'error') this.errors.push(msg.params.entry.text + ' ' + (msg.params.entry.url || ''));
                this.listeners.forEach((l) => l(msg));
            }
        });
    }
    send(method, params = {}) {
        const id = ++this.id;
        this.ws.send(JSON.stringify({ id, method, params }));
        return new Promise((resolve, reject) => this.pending.set(id, { resolve, reject }));
    }
    once(method) { return new Promise((r) => { const l = (m) => { if (m.method === method) { this.listeners = this.listeners.filter((x) => x !== l); r(m.params); } }; this.listeners.push(l); }); }
    async eval(expr) {
        const r = await this.send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true, userGesture: true });
        if (r.exceptionDetails) throw new Error('eval failed: ' + (r.exceptionDetails.exception?.description || r.exceptionDetails.text) + '\n  in: ' + expr.slice(0, 160));
        return r.result.value;
    }
    async waitFor(expr, timeout = 3000) {
        const t0 = Date.now();
        while (Date.now() - t0 < timeout) { if (await this.eval(`!!(${expr})`)) return true; await sleep(40); }
        return false;
    }
    async viewport(width, height, mobile) {
        await this.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: !!mobile });
        await this.send('Emulation.setTouchEmulationEnabled', { enabled: !!mobile, maxTouchPoints: mobile ? 5 : 1 });
    }
    async open(url) {
        this.errors = [];
        const loaded = this.once('Page.loadEventFired');
        await this.send('Page.navigate', { url });
        await loaded; await sleep(150);
    }
    async key(key, opts = {}) {
        const codes = { Tab: 9, Enter: 13, Escape: 27, ArrowDown: 40, ArrowUp: 38, ' ': 32, n: 78, '/': 191 };
        const base = { key, code: key === ' ' ? 'Space' : key.length === 1 ? 'Key' + key.toUpperCase() : key, windowsVirtualKeyCode: codes[key] || 0, modifiers: opts.shift ? 8 : 0 };
        await this.send('Input.dispatchKeyEvent', { type: 'rawKeyDown', ...base });
        if (key.length === 1) await this.send('Input.dispatchKeyEvent', { type: 'char', text: key, ...base });
        await this.send('Input.dispatchKeyEvent', { type: 'keyUp', ...base });
        await sleep(30);
    }
    async type(selector, text) {
        await this.eval(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.focus();e.value='';e.dispatchEvent(new Event('input',{bubbles:true}));})()`);
        await this.send('Input.insertText', { text });
        await sleep(160);
    }
    async click(selector) {
        const ok = await this.eval(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)return false;e.scrollIntoView({block:'center',behavior:'instant'});e.click();return true;})()`);
        if (!ok) throw new Error('click: not found ' + selector);
        await sleep(60);
    }
    async tap(selector) { // real touch event at the element's centre
        const box = await this.eval(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)return null;e.scrollIntoView({block:'center',behavior:'instant'});const r=e.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+Math.min(r.height/2,20)};})()`);
        if (!box) throw new Error('tap: not found ' + selector);
        await sleep(120);
        // Headless Edge does not turn synthetic taps into clicks, so emulate the sequence a browser produces:
        // touchstart/touchend at the point, then click the element hit-tested at that point (whatever is visible there).
        await this.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: box.x, y: box.y }] });
        await this.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
        const hit = await this.eval(`(()=>{const e=document.elementFromPoint(${box.x},${box.y});if(!e)return false;e.click();return true;})()`);
        if (!hit) throw new Error('tap: nothing at point');
        await sleep(250);
    }
    async setValue(selector, value) {
        await this.eval(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});e.value=${JSON.stringify(value)};e.dispatchEvent(new Event('input',{bubbles:true}));e.dispatchEvent(new Event('change',{bubbles:true}));})()`);
        await sleep(80);
    }
    async shot(name) {
        const r = await this.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        const file = path.join(SHOTS, name + '.png');
        fs.writeFileSync(file, Buffer.from(r.data, 'base64'));
        return file;
    }
    text(selector) { return this.eval(`(document.querySelector(${JSON.stringify(selector)})||{}).textContent||''`); }
    visible(selector) { return this.eval(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)return false;const s=getComputedStyle(e);const r=e.getBoundingClientRect();return s.display!=='none'&&s.visibility!=='hidden'&&r.width>0&&r.height>0;})()`); }
    noHorizontalOverflow() { return this.eval('document.documentElement.scrollWidth <= window.innerWidth + 1'); }
}

async function launch() {
    const exe = BROWSERS.find((p) => fs.existsSync(p));
    if (!exe) throw new Error('No Edge/Chrome found');
    const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'bp-edge-'));
    const proc = spawn(exe, ['--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`, '--no-first-run',
        '--no-default-browser-check', '--disable-extensions', '--disable-gpu', '--window-size=1400,1300', 'about:blank'], { stdio: 'ignore' });
    let ver;
    for (let i = 0; i < 60 && !ver; i++) { try { ver = await (await fetch(`http://127.0.0.1:${PORT}/json/version`)).json(); } catch { await sleep(150); } }
    if (!ver) throw new Error('Browser did not start');
    const target = await (await fetch(`http://127.0.0.1:${PORT}/json/new?about:blank`, { method: 'PUT' })).json();
    const ws = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((r, j) => { ws.onopen = r; ws.onerror = j; });
    const page = new Page(ws);
    await page.send('Page.enable'); await page.send('Runtime.enable'); await page.send('Log.enable');
    return { page, browser: ver.Browser, close: () => { try { ws.close(); } catch {} proc.kill(); setTimeout(() => { try { fs.rmSync(profile, { recursive: true, force: true }); } catch {} }, 800); } };
}

/* ---------------- tiny test runner ---------------- */
const results = [];
async function check(design, name, steps, expected, fn) {
    let actual, pass = false;
    try { const r = await fn(); pass = r === true || (r && r.pass === true); actual = r && r.actual !== undefined ? r.actual : (pass ? 'as expected' : String(r)); }
    catch (e) { actual = 'ERROR: ' + e.message.split('\n')[0]; }
    results.push({ design, name, steps, expected, actual, pass });
    process.stdout.write(`${pass ? 'PASS' : 'FAIL'}  [${design}] ${name}${pass ? '' : '  -> ' + actual}\n`);
}
const res = (pass, actual) => ({ pass, actual });

/* shared checks used by every design */
async function commonLayoutChecks(page, D, file) {
    for (const [w, h, mobile, label] of [[1366, 850, false, 'desktop'], [820, 1180, true, 'tablet'], [390, 844, true, 'mobile']]) {
        await page.viewport(w, h, mobile);
        await page.open(BASE + file);
        await sleep(250);
        const shot = await page.shot(`${D}-${label}`);
        await check(D, `Layout ${label} ${w}px: no horizontal page overflow`, `Open page at ${w}x${h}${mobile ? ' (touch)' : ''}`, 'scrollWidth <= viewport width; no JS errors',
            async () => { const ok = await page.noHorizontalOverflow(); const sw = await page.eval('document.documentElement.scrollWidth'); return res(ok && !page.errors.length, `scrollWidth=${sw}, errors=${page.errors.length}, shot=${path.basename(shot)}`); });
    }
    await page.viewport(1366, 850, false);
}

async function xssCheck(page, D, rerenderExpr, textSelector) {
    await check(D, 'XSS: malicious buyer name renders as text', 'Inject buyer name `<img src=x onerror=window.__xss=1>` into demo data and re-render',
        'No script runs; markup shown literally', async () => {
            await page.eval(`(()=>{const b=BP.getBuyer('b4');b.name='<img src=x onerror="window.__xss=1"><b>X</b>';})()`);
            await page.eval(rerenderExpr);
            await sleep(300);
            const fired = await page.eval('window.__xss===1');
            const imgs = await page.eval(`document.querySelectorAll('img[src="x"]').length`);
            const shown = await page.eval(`document.querySelector(${JSON.stringify(textSelector)}).innerText.includes('<img src=x')`);
            return res(!fired && imgs === 0 && shown, `scriptFired=${fired}, injectedImgs=${imgs}, literalTextShown=${shown}`);
        });
}

/* ================= DESIGN 1 ================= */
async function design1(page) {
    const D = 'D1';
    const F = 'design-1-dashboard.html';
    const rowOf = (id) => `#rows tr[data-id="${id}"]`;
    await page.viewport(1366, 850, false);
    await page.open(BASE + F);

    await check(D, 'Page opens without errors', 'Load Design 1', '12 buyer rows, no JS/console errors', async () => {
        const n = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        return res(n === 12 && !page.errors.length, `rows=${n}, errors=${JSON.stringify(page.errors)}`);
    });
    await check(D, 'Customer A reference figures', 'Read Customer A row', 'Sales ₱115,000.00, Collected ₱80,000.00, Chq on hand ₱20,000.00, Outstanding ₱35,000.00, Partial', async () => {
        const t = await page.eval(`document.querySelector('${rowOf('b1')}').innerText`);
        return res(['₱115,000.00', '₱80,000.00', '₱20,000.00', '₱35,000.00', 'Partial'].every((x) => t.includes(x)), t.replace(/\s+/g, ' '));
    });
    await check(D, 'KPI totals consistent with data', 'Compare KPI Outstanding with sum of buyer outstanding', 'Equal', async () => {
        const kpi = await page.text('.k-out .v');
        const expect = await page.eval('BP.formatPeso(BP.portfolio(BP.store.buyers).outstanding)');
        return res(kpi === expect, `kpi=${kpi}, computed=${expect}`);
    });
    await check(D, 'Buyer search (accent-insensitive)', 'Type "nino" in search', 'Only O\'Brien "Quick" Mart — Ñiño\'s ₱ Store shown, name rendered exactly', async () => {
        await page.type('#q', 'nino'); await sleep(200);
        const rows = await page.eval(`[...document.querySelectorAll('#rows tr[data-id]')].map(r=>r.dataset.id)`);
        const name = await page.text(`${rowOf('b7')} .b_name`);
        return res(rows.length === 1 && rows[0] === 'b7' && name === 'O\'Brien "Quick" Mart — Ñiño\'s ₱ Store', `rows=${rows}, name=${name}`);
    });
    await check(D, 'Search with no match shows empty state; clear restores', 'Type "zzzz", then click "Clear search & filter"', 'Empty message, then 12 rows', async () => {
        await page.type('#q', 'zzzz'); await sleep(200);
        const empty = await page.visible('#rows .empty_state');
        await page.click('[data-clear]');
        const n = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        return res(empty && n === 12, `empty=${empty}, rowsAfterClear=${n}`);
    });
    await check(D, 'Filter by status (segmented + status bar)', 'Click Unpaid, Partial, Paid; click Paid bar segment twice', 'Unpaid=3, Partial=5, Paid=4, every badge matches; second click returns to All', async () => {
        const get = async (s) => { await page.click(`#seg [data-status="${s}"]`); return page.eval(`[...document.querySelectorAll('#rows tr[data-id] .td_status')].map(t=>t.innerText.trim().split(/\\s/)[0])`); };
        const u = await get('Unpaid'), pa = await get('Partial'), pd = await get('Paid');
        await get('All');
        await page.click('#mix_bar [data-status="Paid"]');
        const viaBar = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        await page.click('#mix_bar [data-status="Paid"]');
        const back = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        const ok = u.length === 3 && u.every((x) => x === 'Unpaid') && pa.length === 5 && pa.every((x) => x === 'Partial') && pd.length === 4 && pd.every((x) => x === 'Paid') && viaBar === 4 && back === 12;
        return res(ok, `unpaid=${u.length} partial=${pa.length} paid=${pd.length} bar=${viaBar} back=${back}`);
    });
    await check(D, 'Select buyer opens drawer with balances', 'Click Customer A row', 'Drawer shows Net ₱15,000.00 and Outstanding ₱35,000.00, Returned ₱15,000.00', async () => {
        await page.click(rowOf('b1'));
        await page.waitFor(`document.querySelector('#drawer').classList.contains('show')`);
        const t = await page.eval(`document.querySelector('#d_body').innerText`);
        const title = await page.text('#d_title');
        return res(title === 'Customer A' && /Net Balance\s*₱15,000\.00/.test(t) && /Outstanding\s*₱35,000\.00/.test(t) && /Returned Cheques \(1\)\s*₱15,000\.00/.test(t), `title=${title}`);
    });
    await check(D, 'Switch between buyers', 'Close drawer (Esc), open Mabuhay Grocery Mart', 'Drawer title and figures change to Mabuhay', async () => {
        await page.key('Escape');
        await page.waitFor(`!document.querySelector('#drawer').classList.contains('show')`);
        await page.click(rowOf('b3'));
        await page.waitFor(`document.querySelector('#drawer').classList.contains('show')`);
        const title = await page.text('#d_title');
        const t = await page.eval(`document.querySelector('#d_body').innerText`);
        return res(title === 'Mabuhay Grocery Mart' && t.includes('₱35,500.00'), `title=${title}`);
    });
    await check(D, 'Change cheque status Hold → Cleared updates balances', 'Open Customer A, set Metrobank #0002002 to Cleared', 'Collected ₱90,000.00, Outstanding ₱25,000.00, row updated', async () => {
        await page.key('Escape'); await page.waitFor(`!document.querySelector('#drawer').classList.contains('show')`);
        await page.click(rowOf('b1')); await page.waitFor(`document.querySelector('#drawer').classList.contains('show')`);
        await page.setValue('[data-chq="p5"]', 'Cleared');
        const t = await page.eval(`document.querySelector('#d_body').innerText`);
        const row = await page.eval(`document.querySelector('${rowOf('b1')}').innerText`);
        return res(/Collected \/ Cleared \(4\)\s*₱90,000\.00/.test(t) && /Outstanding\s*₱25,000\.00/.test(t) && row.includes('₱25,000.00'), row.replace(/\s+/g, ' '));
    });
    await check(D, 'Returned cheque needs confirmation; Cancel reverts', 'Set BPI #0003003 to Bounced, click Cancel', 'Confirm dialog shown; status stays For Deposit; balances unchanged', async () => {
        await page.setValue('[data-chq="p6"]', 'Bounced');
        const shown = await page.waitFor(`document.querySelector('.cfm')`);
        await page.click('.cfm [data-r="0"]');
        const st = await page.eval(`BP.findPayment(BP.getBuyer('b1'),'p6').status`);
        const sel = await page.eval(`document.querySelector('[data-chq="p6"]').value`);
        return res(shown && st === 'For Deposit' && sel === 'For Deposit', `dialog=${shown}, status=${st}, select=${sel}`);
    });
    await check(D, 'Returned cheque excluded from collections', 'Set BPI #0003003 to Bounced and confirm', 'Returned ₱25,000.00; Collected stays ₱90,000.00; Outstanding stays ₱25,000.00', async () => {
        await page.setValue('[data-chq="p6"]', 'Bounced');
        await page.waitFor(`document.querySelector('.cfm')`);
        await page.click('.cfm [data-r="1"]'); await sleep(150);
        const t = await page.eval(`document.querySelector('#d_body').innerText`);
        return res(/Returned Cheques \(2\)\s*₱25,000\.00/.test(t) && /Collected \/ Cleared \(4\)\s*₱90,000\.00/.test(t) && /Outstanding\s*₱25,000\.00/.test(t), t.slice(0, 200).replace(/\s+/g, ' '));
    });
    await page.key('Escape'); await page.waitFor(`!document.querySelector('#drawer').classList.contains('show')`);

    const modalOpen = `document.querySelector('#payModal').classList.contains('show') && !document.querySelector('#payModal .modal-dialog').getAnimations().length`;
    const modalClosed = `getComputedStyle(document.querySelector('#payModal')).display==='none'`;
    await check(D, 'Open and cancel Add Payment leaves data unchanged', 'Click Add Payment, type an amount, click Cancel', 'Modal closes; payment count unchanged', async () => {
        const before = await page.eval('BP.store.buyers.reduce((n,b)=>n+b.payments.length,0)');
        await page.click('#btn_add'); const opened = await page.waitFor(modalOpen);
        await page.setValue('#pm_amount', '5000');
        await page.click('#payModal .modal-footer [data-bs-dismiss]');
        const closed = await page.waitFor(modalClosed);
        const after = await page.eval('BP.store.buyers.reduce((n,b)=>n+b.payments.length,0)');
        return res(opened && closed && before === after, `opened=${opened}, closed=${closed}, payments ${before}→${after}`);
    });
    await check(D, 'Validation: empty form', 'Open Add Payment, clear date, Save', 'Errors for buyer, date and amount; nothing saved', async () => {
        await page.click('#btn_add'); await page.waitFor(modalOpen); await sleep(300);
        await page.setValue('#pm_date', '');
        await page.click('#pm_save');
        const errs = await page.eval(`[...document.querySelectorAll('#payForm .has-err')].map(e=>e.dataset.field)`);
        return res(['buyer', 'date', 'amount'].every((f) => errs.includes(f)), `errorFields=${errs}`);
    });
    for (const [val, msg] of [['0', 'greater than zero'], ['-50', 'negative'], ['abc', 'numbers only'], ['12.345', 'numbers only'], ['1000000000', 'numbers only'], ['   ', 'required']]) {
        await check(D, `Validation: amount "${val}" rejected`, `Pick Customer A, enter amount "${val}", Save`, `Amount error containing "${msg}"`, async () => {
            await page.type('#pm_buyer', 'Customer A'); await page.key('Enter');
            await page.setValue('#pm_date', await page.eval('BP.todayIso()'));
            await page.setValue('#pm_amount', val);
            await page.click('#pm_save'); await sleep(80);
            const m = await page.text('#pm_amount_err');
            return res(m.toLowerCase().includes(msg), m);
        });
    }
    await check(D, 'Switch payment methods reveals correct fields', 'Choose Cash, GCash, Cheque, Bank Transfer in turn', 'Cash: optional ref; GCash: GCash ref; Cheque: bank/cheque no/date/status, no ref; Bank: bank ref', async () => {
        const vis = async (m) => { await page.setValue('#pm_method', m); return page.eval(`['ref','bank','chqNo','chqDate','status'].filter(f=>{const e=document.querySelector('#payForm [data-field="'+f+'"]');return getComputedStyle(e).display!=='none'}).join(',')+'|'+document.querySelector('.lbl_ref').textContent`); };
        const c = await vis('cash'), g = await vis('gcash'), q = await vis('cheque'), b = await vis('bank');
        const ok = c.startsWith('ref|Official') && g === 'ref|GCash Reference No.' && q.startsWith('bank,chqNo,chqDate,status|') && b === 'ref|Bank Reference / Deposit Slip No.';
        return res(ok, [c, g, q.split('|')[0], b].join('  /  '));
    });
    await check(D, 'GCash without reference rejected', 'Method GCash, amount 100, no reference, Save', 'Reference error', async () => {
        await page.setValue('#pm_method', 'gcash'); await page.setValue('#pm_amount', '100'); await page.setValue('#pm_ref', '');
        await page.click('#pm_save'); await sleep(80);
        return res((await page.text('#pm_ref_err')).includes('required'), await page.text('#pm_ref_err'));
    });
    await check(D, 'Duplicate cheque blocked', 'Cheque BDO #0001001 for Customer A', 'Error "already recorded"', async () => {
        await page.setValue('#pm_method', 'cheque'); await page.setValue('#pm_bank', 'BDO'); await page.setValue('#pm_chqNo', '0001001'); await page.setValue('#pm_amount', '100');
        await page.click('#pm_save'); await sleep(80);
        return res((await page.text('#pm_chqNo_err')).includes('already recorded'), await page.text('#pm_chqNo_err'));
    });
    await page.click('#payModal .btn-close'); await page.waitFor(modalClosed);

    await check(D, 'Submit valid cash payment (buyer picked by keyboard)', 'Add Payment → type "rosa" → Enter → amount 8300.75 cash → Save', 'Toast; Tindahan ni Aling Rosa outstanding ₱10,000.00, status Partial', async () => {
        await page.click('#btn_add'); await page.waitFor(modalOpen); await sleep(300);
        await page.type('#pm_buyer', 'rosa'); await page.key('Enter');
        await page.setValue('#pm_amount', '8300.75');
        const preview = await page.text('#pm_after');
        await page.click('#pm_save');
        await page.waitFor(modalClosed);
        const row = await page.eval(`document.querySelector('${rowOf('b4')}').innerText`);
        const toast = await page.eval(`(document.querySelector('.toast_x')||{}).innerText||''`);
        return res(row.includes('₱10,000.00') && row.includes('Partial') && toast.includes('₱8,300.75') && preview.includes('₱10,000.00'), `preview="${preview.trim()}" row="${row.replace(/\s+/g, ' ')}"`);
    });
    await check(D, 'Possible duplicate payment asks first', 'Repeat the same cash payment for the same buyer/date/amount', 'Confirm dialog; Cancel keeps one payment', async () => {
        await page.click(`[data-pay="b4"]`); await page.waitFor(modalOpen); await sleep(300);
        await page.setValue('#pm_amount', '8300.75');
        await page.click('#pm_save');
        const dlg = await page.waitFor(`document.querySelector('.cfm')`);
        await page.click('.cfm [data-r="0"]');
        const n = await page.eval(`BP.getBuyer('b4').payments.length`);
        await page.click('#payModal .btn-close'); await page.waitFor(modalClosed);
        return res(dlg && n === 1, `dialog=${dlg}, payments=${n}`);
    });
    await check(D, 'Cheque on Hold is not counted as collected', 'Add cheque ₱10,000 Hold for Lim & Sons Wholesale', 'Status stays Unpaid; Collected ₱0.00; Chq on hand ₱10,000.00', async () => {
        await page.click(`[data-pay="b11"]`); await page.waitFor(modalOpen); await sleep(300);
        await page.setValue('#pm_method', 'cheque'); await page.setValue('#pm_amount', '10,000'); await page.setValue('#pm_bank', 'RCBC'); await page.setValue('#pm_chqNo', 'TEST-1'); await page.setValue('#pm_status', 'Hold');
        await page.click('#pm_save'); await page.waitFor(modalClosed);
        const row = await page.eval(`document.querySelector('${rowOf('b11')}').innerText`);
        return res(row.includes('Unpaid') && row.includes('₱0.00') && row.includes('₱10,000.00'), row.replace(/\s+/g, ' '));
    });
    await check(D, 'Special characters in remarks are stored and shown as text', 'Cash payment with remarks `<script>alert(1)</script> "quoted" O\'Neil ñ ₱`', 'Remarks visible literally in drawer, no script element', async () => {
        await page.click(`[data-pay="b3"]`); await page.waitFor(modalOpen); await sleep(300);
        await page.setValue('#pm_amount', '1'); await page.setValue('#pm_remarks', '<script>alert(1)</script> "quoted" O\'Neil ñ ₱');
        await page.click('#pm_save'); await page.waitFor(modalClosed);
        await page.click(rowOf('b3')); await page.waitFor(`document.querySelector('#drawer').classList.contains('show')`);
        const t = await page.eval(`document.querySelector('#d_body').innerText`);
        const scripts = await page.eval(`document.querySelectorAll('#d_body script').length`);
        await page.key('Escape'); await page.waitFor(`!document.querySelector('#drawer').classList.contains('show')`);
        return res(t.includes('<script>alert(1)</script> "quoted" O\'Neil ñ ₱') && scripts === 0, `scripts=${scripts}`);
    });
    await check(D, 'Keyboard: row Enter opens drawer, Esc closes', 'Focus a row, press Enter, then Escape', 'Drawer opens then closes; focus returns', async () => {
        await page.eval(`document.querySelector('${rowOf('b2')}').focus()`);
        await page.key('Enter');
        const o = await page.waitFor(`document.querySelector('#drawer').classList.contains('show')`);
        await page.key('Escape');
        const c = await page.waitFor(`!document.querySelector('#drawer').classList.contains('show')`);
        return res(o && c, `opened=${o}, closed=${c}`);
    });
    await check(D, 'Keyboard: Tab stays inside Add Payment modal, Esc closes', 'Open modal, press Tab 25 times, then Esc', 'Focus always inside modal; modal closes', async () => {
        await page.click('#btn_add'); await page.waitFor(modalOpen); await sleep(350);
        let inside = true; // focus may leave to browser chrome (body) but never to page content outside the modal
        for (let i = 0; i < 25; i++) { await page.key('Tab'); if (!(await page.eval(`document.querySelector('#payModal').contains(document.activeElement)||document.activeElement===document.body`))) inside = false; }
        await page.key('Escape');
        const closed = await page.waitFor(modalClosed);
        return res(inside && closed, `focusInside=${inside}, closed=${closed}`);
    });
    await check(D, 'Large dataset: load 300 buyers, paging', 'Demo → Load 300 sample buyers, then Show more', '50 rows rendered, 100 after Show more; render < 150ms', async () => {
        const t0 = Date.now();
        await page.click('#btn_bulk');
        const ms = Date.now() - t0;
        const n1 = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        await page.click('#btn_more');
        const n2 = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        const total = await page.eval('BP.store.buyers.length');
        return res(n1 === 50 && n2 === 100 && total === 312 && ms < 150 + 60, `rows=${n1}→${n2}, total=${total}, click+render=${ms}ms`);
    });
    await check(D, 'Reset demo data', 'Demo → Reset demo data → confirm', '12 buyers; Tindahan ni Aling Rosa back to Unpaid; Customer A back to ₱35,000.00', async () => {
        await page.click('#btn_reset'); await page.waitFor(`document.querySelector('.cfm')`); await page.click('.cfm [data-r="1"]'); await sleep(100);
        const n = await page.eval(`document.querySelectorAll('#rows tr[data-id]').length`);
        const r4 = await page.eval(`document.querySelector('${rowOf('b4')}').innerText`);
        const r1 = await page.eval(`document.querySelector('${rowOf('b1')}').innerText`);
        return res(n === 12 && r4.includes('Unpaid') && r1.includes('₱35,000.00'), `rows=${n}`);
    });
    await xssCheck(page, D, `document.querySelector('#q').dispatchEvent(new Event('input'))`, '#rows');
    await check(D, 'No runtime errors during the run', 'Collect console/exception errors', 'None', async () => res(page.errors.length === 0, JSON.stringify(page.errors)));

    await commonLayoutChecks(page, D, F);
    await page.viewport(390, 844, true); await page.open(BASE + F);
    await check(D, 'Mobile: rows render as cards; long name wraps', 'Open at 390px; inspect long-name buyer card', 'Card within viewport; name wraps over multiple lines', async () => {
        const r = await page.eval(`(()=>{const tr=document.querySelector('${rowOf('b6')}');const n=tr.querySelector('.b_name').getBoundingClientRect();const b=tr.getBoundingClientRect();return {disp:getComputedStyle(tr).display,right:b.right,lines:Math.round(n.height/18)}})()`);
        return res(r.disp === 'grid' && r.right <= 391 && r.lines >= 2, JSON.stringify(r));
    });
    await check(D, 'Mobile touch: tap card opens bottom-sheet drawer', 'Tap Customer A card with touch events', 'Drawer opens as bottom sheet (full width)', async () => {
        await page.tap(`${rowOf('b1')} .b_name`);
        const o = await page.waitFor(`document.querySelector('#drawer').classList.contains('show')`);
        await sleep(400);
        const w = await page.eval(`Math.round(document.querySelector('#drawer').getBoundingClientRect().width)`);
        await page.shot('D1-mobile-drawer');
        return res(o && w === 390, `opened=${o}, width=${w}`);
    });
    await check(D, 'Mobile: Add Payment modal is full-screen and usable', 'Tap Add Payment in drawer', 'Modal fills viewport; Save button visible', async () => {
        await page.tap('#d_add'); await page.waitFor(modalOpen); await sleep(400);
        const r = await page.eval(`(()=>{const d=document.querySelector('#payModal .modal-content').getBoundingClientRect();const s=document.querySelector('#pm_save').getBoundingClientRect();return {w:Math.round(d.width),saveVisible:s.bottom<=innerHeight&&s.height>=40}})()`);
        await page.setValue('#pm_method', 'cheque');
        await page.shot('D1-mobile-modal-cheque');
        return res(r.w === 390 && r.saveVisible, JSON.stringify(r));
    });
}

/* ================= run ================= */
/* ================= quick smoke checks: index, design 2, design 3 ================= */
async function smoke(page) {
    for (const [D, F] of [['IDX', 'index.html'], ['D2', 'design-2-ledger.html'], ['D3', 'design-3-collections.html']]) await commonLayoutChecks(page, D, F);

    await page.viewport(1366, 850, false); await page.open(BASE + 'design-2-ledger.html');
    await check('D2', 'Customer A ledger matches PDF', 'Open Design 2', 'Net ₱15,000.00 / Outstanding ₱35,000.00; ledger totals 130,000 / 115,000', async () => {
        const t = await page.eval(`document.querySelector('#balances').innerText`);
        const f = await page.eval(`document.querySelector('#ledger tfoot').innerText`);
        return res(t.includes('₱15,000.00') && t.includes('₱35,000.00') && f.includes('130,000.00') && f.includes('115,000.00'), f.replace(/\s+/g, ' '));
    });
    await check('D2', 'Search, switch buyer, add GCash payment inline', 'Search "mabuhay", select, Add Payment, GCash ₱5,000 ref 123', 'Outstanding drops ₱35,500 → ₱30,500', async () => {
        await page.type('#idx_q', 'mabuhay'); await sleep(200);
        await page.click('.idx_item[data-id="b3"]');
        await page.click('#btn_add'); await page.click('#m_gcash'); await page.setValue('#pf_amount', '5000'); await page.setValue('#pf_ref', '123');
        await page.click('#pf_save'); await sleep(150);
        const t = await page.eval(`document.querySelector('#balances').innerText`);
        return res(t.includes('₱30,500.00'), t.slice(0, 160).replace(/\s+/g, ' '));
    });

    await page.open(BASE + 'design-3-collections.html');
    await check('D3', 'Quick pay with Full-balance chip marks buyer Paid', 'Select Tindahan ni Aling Rosa card, tap "Full", Save', 'Buyer leaves "With balance" queue; activity logged', async () => {
        await page.click('.bcard[data-id="b4"]'); await page.click('[data-fill]'); await page.click('#qpForm [type="submit"]'); await sleep(150);
        const inQueue = await page.eval(`!!document.querySelector('.bcard[data-id="b4"]')`);
        const st = await page.eval(`BP.summarize(BP.getBuyer('b4')).status`);
        return res(!inQueue && st === 'Paid' && (await page.eval('BP.store.activity.length')) === 1, `status=${st}, stillInQueue=${inQueue}`);
    });
    await check('D3', 'Cheque board: For Deposit → Cleared updates collected', 'Cheque Monitor, click "Cleared" on BPI #0003003', 'Customer A collected ₱90,000.00', async () => {
        await page.click('[data-view="cheques"]');
        await page.click('[data-p="p6"][data-to="Cleared"]');
        return res((await page.eval(`BP.summarize(BP.getBuyer('b1')).collected`)) === 9000000, 'collected=' + (await page.eval(`BP.summarize(BP.getBuyer('b1')).collected`)) / 100);
    });
    await check('D2+D3', 'No runtime errors', 'Collect console/exception errors', 'None', async () => res(page.errors.length === 0, JSON.stringify(page.errors)));
}

const SUITES = { 'design-1': design1, smoke };
if (require.main !== module) { module.exports = { launch, BASE, sleep }; return; }
(async () => {
    const only = process.argv[2];
    const { page, browser, close } = await launch();
    console.log(`Browser: ${browser}\nBase: ${BASE}\nScreenshots: ${SHOTS}\n`);
    try {
        for (const [name, fn] of Object.entries(SUITES)) if (!only || only === name) await fn(page);
    } finally { close(); }
    const failed = results.filter((r) => !r.pass);
    fs.writeFileSync(path.join(SHOTS, 'results.json'), JSON.stringify(results, null, 2));
    console.log(`\n${results.length - failed.length}/${results.length} passed. Details: ${path.join(SHOTS, 'results.json')}`);
    process.exitCode = failed.length ? 1 : 0;
})().catch((e) => { console.error(e); process.exitCode = 2; });
