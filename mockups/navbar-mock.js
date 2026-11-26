/* Shared script for the Navbar mockups: page catalogue, tree rendering, search, pins. */

// Page names and icons taken from the `menus` table (removed pages left out).
const P = {
    dash:['Dashboard','fas fa-chart-line'],
    items:['Items','fas fa-box'], uom:['Unit of Measures Masterfile','fas fa-ruler'], upuom:['UPLOAD default UOM','fas fa-industry'],
    shop:['Shop Name','fas fa-user-tag'], plat:['Shop Platforms','fas fa-store'], buyer:['Buyers Profile','far fa-user-circle'],
    decl:['Declaration Form Default','far fa-address-card'], sman:['Salesman','fas fa-male'], route:['Routes','fas fa-male'],
    supp:['Supplier','fas fa-industry'], pdef:['Purchases/ Purchases Order Defaults','far fa-map'],

    po_trn:['Purchases Order Transaction','fas fa-receipt'], po_up:['Purchases Order Upload','fas fa-upload'],
    po_uph:['Purchases Order Upload History','fas fa-history'], pur_trn:['Purchases Transaction','fas fa-receipt'],

    so_trn:['Sales Order Transaction','fas fa-receipt'], sal_trn:['Sales Transaction','fas fa-receipt'],
    ret_trn:['Sales Return Transaction','fas fa-receipt'], payout:['Sales Payout','fas fa-comments-dollar'],
    s_up:['Sales Upload','fas fa-file-import'], s_view:['Sales View File Upload','fas fa-eye'], s_hist:['Sales History File Upload','far fa-folder-open'],
    s_paid:['Sales Paid Upload','fas fa-file-import'], s_del:['Sales Delete Upload','fas fa-folder-minus'], s_delh:['Sales Delete Upload History','fas fa-history'],

    st_trn:['Stock Transfer Transactions','fas fa-cubes'], whse:['Warehouse/s','fas fa-building'], wstaff:['Warehouse Staff','far fa-user'],
    adj_trn:['Inven. Adjust. Transaction','fas fa-receipt'],
    exp_trn:['Expenses Transaction','fas fa-receipt'], exp_type:['Expense Types','far fa-money-bill-alt'],

    inv_bal:['Inventory Balance','fas fa-balance-scale'], sc:['Stock Card','fas fa-cubes'], sc2:['Stock Card (With Filter)','fas fa-cubes'],
    sc3:['Stock Card (With Filter and Price)','fas fa-cubes'], val:['Stock Valuation','fas fa-balance-scale'], crit:['Critical PDF','fas fa-exclamation-triangle'],
    slow:['Slow Moving Report','fas fa-people-carry'], cogs:['COGS Report','fas fa-sign-out-alt'], bal:['Balance Tracker','far fa-file-alt'],

    por1:['Purchases Order by Doc. Num','fas fa-list-ol'], por2:['Purchases Order by Tran. Date','fas fa-calendar-alt'],
    por3:['Purchases Order by Tran. Date (With Item)','fas fa-calendar-alt'], por4:['Purchases Order by Item','fas fa-box'], por5:['Purchase Order Matching','fas fa-share'],
    pur1:['Purchases by Doc. Num','fas fa-list-ol'], pur2:['Purchases by Tran. Date','fas fa-calendar-alt'],
    pur3:['Purchases by Tran. Date (Item)','fas fa-calendar-alt'], pur4:['Purchases by Item','fas fa-box'], pur5:['Purchases Matching','fas fa-share'],
    sor1:['Sales Order by Ordered Date','fas fa-calendar-alt'], sor2:['Sales Order by Ordered Date (With Item)','fas fa-calendar-alt'],
    sor3:['Sales Order by Item (Upload Date)','fas fa-box'], sor4:['Sales Order by Item (Ordered Date)','fas fa-box'], sor5:['Sales Order Matching','fas fa-share'],
    sal1:['Sales by Doc. Num','fas fa-list-ol'], sal2:['Sales by Tran. Date','fas fa-calendar-alt'], sal3:['Sales by Tran. Date (Item)','fas fa-calendar-alt'],
    sal4:['Sales by Item','fas fa-box'], sal5:['Sales Matching','fas fa-share'], sal6:['Sales by Salesman','fas fa-box'],
    sal7:['Top Sales Report','fas fa-box'], sal8:['Top Sales Report W/O Cost','fas fa-box'], sal9:['Unpaid Route Customer PDF','fas fa-route'],
    sal10:['Unpaid Route Customer PDF All','fas fa-map-marked-alt'], sal11:['Customer Sales PDF','far fa-address-book'],
    sal12:['Payout Comparison Report','fas fa-receipt'], sal13:['Sales ROAS Report','fas fa-hand-holding-usd'],
    cost1:['Costing by Tran. Date','fas fa-calendar-alt'], cost2:['Costing by Item','fas fa-box'],
    ret1:['Sales Ret. by Doc. Num','fas fa-list-ol'], ret2:['Sales Ret. by Tran. Date','fas fa-calendar-alt'],
    ret3:['Sales Ret. by Tran. Date (Item)','fas fa-calendar-alt'], ret4:['Sales Ret. by Item','fas fa-box'],
    adj1:['Inven. Adjust. by Doc. Num','fas fa-list-ol'], adj2:['Inven. Adjust. by Tran. Date','fas fa-calendar-alt'],
    adj3:['Inven. Adjust. by Tran. Date (item)','fas fa-calendar-alt'], adj4:['Inven. Adjust. by Item','fas fa-box'],
    exp1:['Expenses by Doc. Num','fas fa-list-ol'], exp2:['Expenses by Tran. Date','fas fa-calendar-alt'], exp3:['Expenses by Expense Type','fas fa-money-check-alt'],

    users:['Users','fas fa-users-cog'], pass:['Password','fas fa-key'], log:['User Activity Log','fas fa-file-contract'],
    fixes:['Fixes','fas fa-wrench'], attend:['View Attendance','fas fa-calendar-alt']
};

// Groups shared by the options (existing report groups keep their names).
const MASTER = ['items','uom','upuom','shop','plat','buyer','decl','sman','route','supp','pdef'];
const UPLOADS = ['s_up','s_view','s_hist','s_paid','s_del','s_delh'];
const R = {
    inv:  {g:'Inventory Reports',      i:'fas fa-file-pdf', c:['inv_bal','sc','sc2','sc3','val','crit','slow','cogs','bal']},
    por:  {g:'Purchases Order Reports',i:'fas fa-file-pdf', c:['por1','por2','por3','por4','por5']},
    pur:  {g:'Purchases Reports',      i:'fas fa-file-pdf', c:['pur1','pur2','pur3','pur4','pur5']},
    sor:  {g:'Sales Order Reports',    i:'fas fa-file-pdf', c:['sor1','sor2','sor3','sor4','sor5']},
    sal:  {g:'Sales Reports',          i:'fas fa-file-pdf', c:['sal1','sal2','sal3','sal4','sal5','sal6','sal7','sal8','sal9','sal10','sal11','sal12','sal13']},
    cost: {g:'Costing Reports',        i:'fas fa-file-pdf', c:['cost1','cost2']},
    ret:  {g:'Sales Return Reports',   i:'fas fa-file-pdf', c:['ret1','ret2','ret3','ret4']},
    adj:  {g:'Inven. Adjust. Reports', i:'fas fa-file-pdf', c:['adj1','adj2','adj3','adj4']},
    exp:  {g:'Expenses Reports',       i:'fas fa-file-pdf', c:['exp1','exp2','exp3']}
};

const PATHS = {};   // page key -> group names above it
let CURRENT = null;

const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
const leafCount = n => typeof n === 'string' ? 1 : n.c.reduce((s, x) => s + leafCount(x), 0);

function indexPaths(nodes, path){
    nodes.forEach(n => typeof n === 'string' ? (PATHS[n] = PATHS[n] || path) : indexPaths(n.c, [...path, n.g]));
}

function nodeHtml(n, d, opt){
    if (typeof n === 'string'){
        const [t, i] = P[n];
        const pin = opt.pins ? `<button class="pin" data-pin="${n}" title="Pin / unpin"><i class="fas fa-star"></i></button>` : '';
        const sub = opt.sub ? `<small>${esc(PATHS[n].join(' › '))}</small>` : '';
        return `<li class="nav_leaf" data-k="${n}"><a href="#" style="--d:${d}"><i class="${i}"></i><span data-t="${esc(t)}">${esc(t)}</span>${sub}${pin}</a></li>`;
    }
    return `<li class="nav_group${n.open ? ' open' : ''}"><a href="#" class="nav_head" style="--d:${d}"><i class="${n.i}"></i><span>${esc(n.g)}</span>`
         + `<em class="cnt">${leafCount(n)}</em><i class="fas fa-chevron-right caret"></i></a>`
         + `<ul>${n.c.map(x => nodeHtml(x, d + 1, opt)).join('')}</ul></li>`;
}

function treeHtml(nodes, opt = {}){
    return `<ul class="nav_tree">${nodes.map(n => nodeHtml(n, 0, opt)).join('')}</ul>`;
}

function markActive(){
    document.querySelectorAll('.nav_leaf').forEach(li => li.classList.toggle('active', li.dataset.k === CURRENT));
}

function showPage(k){
    CURRENT = k;
    markActive();
    document.getElementById('crumb').innerHTML = [...(PATHS[k] || []), `<b>${esc(P[k][0])}</b>`]
        .map(x => x.startsWith('<b>') ? x : esc(x)).join(' <i class="fas fa-chevron-right"></i> ');
    document.getElementById('page_title').textContent = P[k][0];
    document.body.classList.remove('nav_open');
    document.dispatchEvent(new CustomEvent('mock:open', {detail:k}));
}

// Group toggle (accordion at top level) and page click, delegated on a container.
function bindTree(root, onLeaf = showPage){
    root.addEventListener('click', e => {
        const a = e.target.closest('a');
        if (!a || !root.contains(a)) return;
        e.preventDefault();
        const li = a.parentElement;
        if (!a.classList.contains('nav_head')) return onLeaf(li.dataset.k);
        const opening = !li.classList.contains('open');
        if (opening && li.parentElement.classList.contains('nav_tree') && !root.dataset.q){
            li.parentElement.querySelectorAll(':scope > .nav_group.open').forEach(x => x.classList.remove('open'));
        }
        li.classList.toggle('open', opening);
    });
}

// Hide pages that don't match (title or group path), open groups with hits, highlight the match.
function filterTree(root, q){
    q = q.trim().toLowerCase();
    root.dataset.q = q;
    let hits = 0;
    root.querySelectorAll('.nav_leaf').forEach(li => {
        const s = li.querySelector('span'), t = s.dataset.t, at = t.toLowerCase().indexOf(q);
        const hit = !q || at >= 0 || (PATHS[li.dataset.k] || []).join(' ').toLowerCase().includes(q);
        li.hidden = !hit;
        if (q && hit) hits++;
        s.innerHTML = q && at >= 0 ? esc(t.slice(0, at)) + '<mark>' + esc(t.slice(at, at + q.length)) + '</mark>' + esc(t.slice(at + q.length)) : esc(t);
    });
    root.querySelectorAll('.nav_group').forEach(g => {
        const any = !!g.querySelector('.nav_leaf:not([hidden])');
        g.hidden = !!q && !any;
        g.classList.toggle('open', q ? any : !!g.querySelector('.nav_leaf.active'));
    });
    return hits;
}

function bindSearch(input, onInput, onEnter){
    input.addEventListener('input', () => {
        input.closest('.side').classList.toggle('searching', !!input.value.trim());
        onInput(input.value);
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') onEnter();
        if (e.key === 'Escape'){ input.value = ''; input.dispatchEvent(new Event('input')); }
    });
}

// Options 1 and 3: one sidebar tree with search.
function initSidebar({tree, pins = false}){
    const root = document.getElementById('nav_root'), empty = document.getElementById('nav_empty');
    const search = document.querySelector('.side_search input');
    indexPaths(tree, []);
    root.innerHTML = treeHtml(tree, {pins});
    bindTree(root);
    bindSearch(search,
        q => { const n = filterTree(root, q); empty.hidden = !q.trim() || n > 0; },
        () => { const li = root.querySelector('.nav_leaf:not([hidden])'); if (li) showPage(li.dataset.k); });
}

// Option 2: modules in a top bar, sidebar shows the active module; search covers every module.
function initModules({mods, start}){
    const bar = document.getElementById('modbar'), root = document.getElementById('nav_root');
    const empty = document.getElementById('nav_empty'), search = document.querySelector('.side_search input');
    const modOf = {};
    let cur = start;
    mods.forEach((m, mi) => {
        indexPaths(m.c, [m.m]);
        Object.keys(PATHS).forEach(k => { if (!(k in modOf)) modOf[k] = mi; });
    });
    bar.innerHTML = mods.map((m, mi) => `<a href="#" data-mi="${mi}"><i class="${m.i}"></i>${esc(m.m)}</a>`).join('');

    const label = t => `<div class="side_label">${esc(t)}</div>`;
    const draw = () => {
        const m = mods[cur];
        bar.querySelectorAll('a').forEach(a => a.classList.toggle('on', +a.dataset.mi === cur));
        const leaves = m.c.filter(x => typeof x === 'string');
        let h = `<div class="mod_title"><i class="${m.i}"></i>${esc(m.m)}</div>`, last = '';
        if (leaves.length) h += label(m.main || 'Transactions') + treeHtml(leaves);
        m.c.filter(x => typeof x !== 'string').forEach(g => {
            if (g.sec !== last){ h += label(g.sec); last = g.sec; }
            h += g.flat ? treeHtml(g.c) : treeHtml([g]);
        });
        root.innerHTML = h;
        root.dataset.q = '';
        markActive();
    };
    const open = k => {
        if (search.value){ search.value = ''; search.closest('.side').classList.remove('searching'); }
        cur = modOf[k]; draw(); showPage(k);
    };

    bar.addEventListener('click', e => {
        const a = e.target.closest('a'); if (!a) return;
        e.preventDefault();
        cur = +a.dataset.mi; search.value = ''; draw();
        const first = root.querySelector('.nav_leaf'); if (first) showPage(first.dataset.k);
    });
    bindTree(root, open);
    bindSearch(search,
        q => {
            q = q.trim().toLowerCase();
            if (!q){ empty.hidden = true; return draw(); }
            const hits = Object.keys(modOf).filter(k => (P[k][0] + ' ' + PATHS[k].join(' ')).toLowerCase().includes(q));
            root.innerHTML = label(`${hits.length} result${hits.length === 1 ? '' : 's'} in all modules`) + treeHtml(hits, {sub:true});
            root.dataset.q = q;
            empty.hidden = hits.length > 0;
            markActive();
        },
        () => { const li = root.querySelector('.nav_leaf'); if (li) open(li.dataset.k); });
    draw();
}

// Option 3: Pinned + Recent lists (stored per browser in the mockup; pins would be user_menus.is_pinned).
function initQuick({defaults}){
    const load = (k, d) => { try { return JSON.parse(localStorage.getItem(k)) || d; } catch (e) { return d; } };
    const save = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} };
    const pinsEl = document.getElementById('nav_pins'), recentEl = document.getElementById('nav_recent');
    let pins = load('mock_nav_pins', defaults), recent = load('mock_nav_recent', []);

    const draw = () => {
        pinsEl.innerHTML = pins.length ? treeHtml(pins, {pins:true}) : '<div class="side_hint">Hover a page and click ★ to pin it.</div>';
        recentEl.innerHTML = recent.length ? treeHtml(recent) : '<div class="side_hint">Pages you open appear here.</div>';
        document.querySelectorAll('.pin').forEach(b => b.classList.toggle('on', pins.includes(b.dataset.pin)));
        markActive();
    };
    // capture phase so the star doesn't also open the page
    document.addEventListener('click', e => {
        const b = e.target.closest('.pin'); if (!b) return;
        e.preventDefault(); e.stopPropagation();
        const k = b.dataset.pin;
        pins = pins.includes(k) ? pins.filter(x => x !== k) : [...pins, k];
        save('mock_nav_pins', pins); draw();
    }, true);
    document.addEventListener('mock:open', e => {
        recent = [e.detail, ...recent.filter(x => x !== e.detail)].slice(0, 5);
        save('mock_nav_recent', recent); draw();
    });
    bindTree(pinsEl); bindTree(recentEl);
    draw();
}

// Ctrl+K or "/" focuses search; hamburger / backdrop toggles the mobile drawer.
document.addEventListener('keydown', e => {
    const input = document.querySelector('.side_search input');
    if (((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') || (e.key === '/' && document.activeElement !== input)){
        e.preventDefault();
        document.body.classList.add('nav_open');
        input.focus(); input.select();
    }
});
document.addEventListener('click', e => {
    if (e.target.closest('[data-nav-toggle]')) document.body.classList.toggle('nav_open');
});
