<?php
/**
 * Standalone CLI test: User Access screen renders the whole menu tree (4 levels) with one recursive renderer,
 * keeping the field names utl_user_access_ajax_save.php reads.
 * Uses in-memory rows only - never touches the DB, session or $_POST.
 * Run: php tests/test_user_access_tree.php
 */

$root = __DIR__ . '/..';
require $root . '/includes/menu_tree.php';
require $root . '/utl_user_access_lib.php';

$failures = 0;
function check($label, $ok)
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label\n";
    if (!$ok) {
        $failures++;
    }
}

function r($mencap, $menprogram, $menidx, $mennum, $mensub, $mengrp, $has_crud = '', $is_disabled = '', $is_removed = '')
{
    return compact('mencap', 'menprogram', 'menidx', 'mennum', 'mensub', 'mengrp', 'has_crud', 'is_disabled', 'is_removed') + array('menlogo' => '');
}

// Sales > Sales Invoices > Sales Reports > page (4 levels)
$rows = array(
    r('Sales',                   '',                         '2.22', 0, 'SAL-MAIN', '1'),
    r('Sales Invoices',          '',                         'x',    2, 'SAL-INV',  'SAL-MAIN'),
    r('Sales Transaction',       'trn_salesfile1.php',       'x',    1, '',         'SAL-INV', 'Y'),
    r('Sales Reports',           '',                         'x',    3, 'SAL-REP',  'SAL-INV'),
    r('Sales by Item',           'sales_item_rep.php',       'x',    1, '',         'SAL-REP', 'N'),
    r('Sales Order',             '',                         'x',    1, 'SOR-MAIN', 'SAL-MAIN'),
    r('Sales Order Transaction', 'trn_salesorderfile1.php',  'x',    1, '',         'SOR-MAIN', 'Y'),
    r('Sales Payments',          'trn_salespayments.php',    'x',    6, '',         'SAL-INV', 'Y', '', '1'),
    r('Purchasing',              '',                         '2.10', 0, 'PUR-MAIN', '1'),
    r('Purchases',               '',                         'x',    2, 'PUR-TRN',  'PUR-MAIN'),
    r('Purchases Transaction',   'trn_purchasefile1.php',    'x',    1, '',         'PUR-TRN', 'Y'),
    r('Master Files',            '',                         '1.00', 0, 'MASTERFILE-MAIN', '1'),
    r('Items',                   'mf_itemfile.php',          'x',    1, '',         'MASTERFILE-MAIN', 'Y'),
    r('Locked',                  'locked.php',               '9.00', 0, '',         '1', 'N', '1'),
);
$tree = menu_build_tree($rows, null, '');

$user_rows = array(
    'Sales'             => array('add' => '0', 'edit' => '0', 'view' => '0', 'delete' => '0', 'export' => '0', 'hide_price' => '0', 'hide_cost_price' => '0', 'hide_distri_price' => '0'),
    'Sales Transaction' => array('add' => '1', 'edit' => '0', 'view' => '1', 'delete' => '0', 'export' => '1', 'hide_price' => '0', 'hide_cost_price' => '1', 'hide_distri_price' => '0'),
    'Purchases Transaction' => array('add' => '0', 'edit' => '0', 'view' => '1', 'delete' => '0', 'export' => '0', 'hide_price' => '1', 'hide_cost_price' => '0', 'hide_distri_price' => '0'),
);
$html = ua_render_nodes($tree['modules'], $user_rows);

function input_tag($html, $name, $type = 'checkbox')
{
    // the input with this name and type (hidden ones are rendered first)
    preg_match("/<input type='" . $type . "'[^>]*name='" . preg_quote($name, '/') . "'[^>]*>/", $html, $m);
    return isset($m[0]) ? $m[0] : '';
}

// --- tree / nesting
check('4th level page is rendered', strpos($html, "name='menu[Sales by Item][value]'") !== false);
check('nodes nest 4 deep', preg_match("/ua_node.*ua_children.*ua_node.*ua_children.*ua_node.*ua_children.*ua_node/s", $html) === 1);
check('groups are bold', strpos($html, '<b>Sales Invoices</b>') !== false);
check('removed page not listed', strpos($html, 'Sales Payments') === false);
check('modules in menidx order', strpos($html, 'Master Files') < strpos($html, 'Purchasing') && strpos($html, 'Purchasing') < strpos($html, "<b>Sales</b>"));

// --- field names and ticked state
check('hidden 0 posted before each checkbox', strpos($html, "<input type='hidden' value='0' name='menu[Sales Order][value]'><input type='checkbox' value='1' name='menu[Sales Order][value]'") !== false);
check('menu with user row is ticked', strpos(input_tag($html, 'menu[Sales][value]'), ' checked') !== false);
check('menu without user row is not ticked', strpos(input_tag($html, 'menu[Sales Order][value]'), ' checked') === false);
check('main checkbox uses ua_main', strpos(input_tag($html, 'menu[Sales][value]'), 'ua_main') !== false);

// --- CRUD only for has_crud = Y, ticked from the user's flags
check('CRUD boxes for has_crud Y page', input_tag($html, 'menu[Sales Transaction][add]') !== '');
check('no CRUD boxes for has_crud N page', input_tag($html, 'menu[Sales by Item][add]') === '');
check('no CRUD boxes for groups', input_tag($html, 'menu[Sales Invoices][add]') === '');
check('add flag ticked', strpos(input_tag($html, 'menu[Sales Transaction][add]'), ' checked') !== false);
check('edit flag not ticked', strpos(input_tag($html, 'menu[Sales Transaction][edit]'), ' checked') === false);
check('CRUD box carries data-crud', strpos(input_tag($html, 'menu[Sales Transaction][view]'), "data-crud='view'") !== false);
check('all five CRUD boxes', count(array_filter(array('add', 'edit', 'view', 'delete', 'export'), function ($k) use ($html) { return input_tag($html, "menu[Sales Transaction][$k]") !== ''; })) === 5);

// --- price hiding boxes at any depth (Sales Transaction is level 3 now)
check('hide cost price on level-3 Sales Transaction', strpos(input_tag($html, 'menu[Sales Transaction][hide_cost_price]'), ' checked') !== false);
check('hide distributor price on Sales Transaction', input_tag($html, 'menu[Sales Transaction][hide_distri_price]') !== '');
check('hide cost price on Sales Order Transaction', input_tag($html, 'menu[Sales Order Transaction][hide_cost_price]') !== '');
check('hide cost price on Items (now under Master Files)', input_tag($html, 'menu[Items][hide_cost_price]') !== '');
check('hide price on Purchases Transaction, ticked', strpos(input_tag($html, 'menu[Purchases Transaction][hide_price]'), ' checked') !== false);
check('no hide price on Sales Transaction', input_tag($html, 'menu[Sales Transaction][hide_price]') === '');
check('hide boxes do not use ua_crud (never cascade)', strpos(input_tag($html, 'menu[Items][hide_cost_price]'), 'ua_crud') === false);

// --- disabled menus stay granted
check('disabled menu posts hidden 1', strpos($html, "<input type='hidden' value='1' name='menu[Locked][value]'>") !== false);
check('disabled menu checkbox is disabled and ticked', strpos(input_tag($html, 'menu[Locked][value]'), ' checked disabled') !== false);

// --- escaping
$esc = ua_render_nodes(array(array('mencap' => "A'B", 'menlogo' => '', 'menprogram' => 'a.php', 'has_crud' => '', 'is_disabled' => '', 'children' => array(), 'active' => false)), array());
check('caption with quote is escaped in field name', strpos($esc, "name='menu[A&#039;B][value]'") !== false);

// --- wiring
$ajax = file_get_contents($root . '/utl_user_access_ajax.php');
$page = file_get_contents($root . '/utl_user_access.php');
check('ajax builds tree and uses the renderer', strpos($ajax, 'menu_load_tree($link') !== false && strpos($ajax, 'ua_render_nodes(') !== false);
check('ajax has no per-level loops left', strpos($ajax, 'select_db_submenu') === false);
check('old per-level JS removed', strpos($page, 'function checkall_sub_sub') === false && strpos($page, 'function checksub_sub') === false && strpos($page, 'function checkcrud_sub') === false);
check('generic JS handles ua_main and ua_crud', strpos($page, "'#main_chk_div .ua_main'") !== false && strpos($page, "'#main_chk_div .ua_crud'") !== false);
check('page still saves through the same endpoint', strpos($page, 'url:"utl_user_access_ajax_save.php"') !== false);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
