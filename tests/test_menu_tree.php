<?php
/**
 * Standalone CLI test: navbar tree built from `menus` rows (layout) and the user's mencaps (visibility).
 * Uses in-memory rows only - never touches the DB, session or $_POST.
 * Run: php tests/test_menu_tree.php
 */

require __DIR__ . '/../includes/menu_tree.php';

$failures = 0;
function check($label, $ok)
{
    global $failures;
    echo ($ok ? "PASS" : "FAIL") . " - $label\n";
    if (!$ok) {
        $failures++;
    }
}

function r($mencap, $menprogram, $menidx, $mennum, $mensub, $mengrp, $is_removed = '')
{
    return compact('mencap', 'menprogram', 'menidx', 'mennum', 'mensub', 'mengrp', 'is_removed') + array('menlogo' => 'fas fa-box');
}

function caps($nodes)
{
    return array_map(function ($n) { return $n['mencap']; }, $nodes);
}

// Shaped like the planned layout: Module > (page | group > page)
$rows = array(
    r('Dashboard',          'gen_dashboard.php',     '.01',  null, '',          '1'),
    r('Purchasing',         '',                      '2.10', 0,    'PUR-MAIN',  '1'),
    r('Purchases Transaction', 'trn_purchasefile1.php', 'x', 2,    '',          'PUR-MAIN'),
    r('Purchases Order Transaction', 'trn_purchasesorderfile1.php', 'x', 1, '', 'PUR-MAIN'),
    r('Purchases Reports',  '',                      'x',    9,    'PUR-REP',   'PUR-MAIN'),
    r('Purchases by Item',  'purchase_item_rep.php', 'x',    3,    '',          'PUR-REP'),
    r('Purchases by Doc. Num', 'purchase_docnum_rep.php', 'x', 1,  '',          'PUR-REP'),
    r('Sales',              '',                      '2.22', 0,    'SAL-MAIN',  '1'),
    r('Sales Transaction',  'trn_salesfile1.php',    'x',    2,    '',          'SAL-MAIN'),
    r('Sales Payments',     'trn_salespayments.php', 'x',    6.01, '',          'SAL-MAIN', '1'),
    r('Master Files',       '',                      '1.00', 0,    'MASTERFILE-MAIN', '1'),
    r('Items',              'mf_itemfile.php',       'x',    1,    '',          'MASTERFILE-MAIN'),
    r('Old Group',          '',                      '2.20', 0,    'POR-MAIN',  '1', '1'),
    r('Under Removed',      'old.php',               'x',    1,    '',          'POR-MAIN'),
    r('Orphan Page',        'orphan.php',            'x',    1,    '',          'NO-PARENT'),
    r('Hidden Top Page',    'hidden.php',            'x',    1,    '',          '1'),
    r('Empty Link',         '',                      'x',    5,    '',          'SAL-MAIN'),
    r('Deep Group',         '',                      'x',    9,    'DEEP',      'PUR-REP'),
    r('Level 4 Page',       'deep.php',              'x',    1,    '',          'DEEP'),
    r('Level 4 Group',      '',                      'x',    2,    'DEEPER',    'DEEP'),
    r('Too Deep Page',      'deeper.php',            'x',    1,    '',          'DEEPER'),
    r('Admin',              '',                      '3.01', 1,    'UTL',       '3'),
    r('Users',              'utl_users.php',         'x',    1,    '',          'UTL'),
);

// --- admin sees everything that isn't removed, in menidx / mennum order
$admin = menu_build_tree($rows, null, 'trn_salesfile1.php');
check('admin modules sorted by menidx', caps($admin['modules']) === array('Dashboard', 'Master Files', 'Purchasing', 'Sales', 'Admin'));
check('module with mengrp 3 (Utilities/Admin) is still a module', caps($admin['modules'][4]['children']) === array('Users'));
$purchasing = $admin['modules'][2];
check('children sorted by mennum', caps($purchasing['children']) === array('Purchases Order Transaction', 'Purchases Transaction', 'Purchases Reports'));
check('group pages sorted by mennum', caps($purchasing['children'][2]['children']) === array('Purchases by Doc. Num', 'Purchases by Item', 'Deep Group'));
check('removed module and its pages hidden', !in_array('Old Group', caps($admin['modules']), true));
check('removed page hidden', !in_array('Sales Payments', caps($admin['modules'][3]['children']), true));
check('page without menprogram hidden', !in_array('Empty Link', caps($admin['modules'][3]['children']), true));
check('menidx x at top level not a module', !in_array('Hidden Top Page', caps($admin['modules']), true));
$deep = $purchasing['children'][2]['children'][2];
check('4th level page kept', $deep['mencap'] === 'Deep Group' && caps($deep['children']) === array('Level 4 Page'));
check('depth capped at 4 levels', !in_array('Level 4 Group', caps($deep['children']), true));
check('node carries has_crud / is_disabled', array_key_exists('has_crud', $deep) && array_key_exists('is_disabled', $deep));
check('top-level page is a module without children', $admin['modules'][0]['children'] === array() && menu_first_page($admin['modules'][0]) === 'gen_dashboard.php');

// --- active module / page by filename
check('active module found from current file', $admin['active'] === 3);
check('active page flagged', $admin['modules'][3]['children'][0]['active'] === true);
$on_report = menu_build_tree($rows, null, 'purchase_item_rep.php');
check('active page inside a group marks module and group', $on_report['active'] === 2 && $on_report['modules'][2]['children'][2]['active'] === true);
check('unknown page has no active module', menu_build_tree($rows, null, 'utl_fixes.php')['active'] === -1);

// --- limited user: only allowed pages; groups shown without their own user_menus row
$user = menu_build_tree($rows, array('Purchases by Item' => true, 'Items' => true, 'Under Removed' => true, 'Orphan Page' => true), 'mf_itemfile.php');
check('user sees only modules with allowed pages', caps($user['modules']) === array('Master Files', 'Purchasing'));
check('group shown via allowed child only', caps($user['modules'][1]['children']) === array('Purchases Reports'));
check('group lists only allowed pages', caps($user['modules'][1]['children'][0]['children']) === array('Purchases by Item'));
check('module link is its first allowed page', menu_first_page($user['modules'][1]) === 'purchase_item_rep.php');
check('active index follows filtered modules', $user['active'] === 0);
check('user with no rows sees nothing', menu_build_tree($rows, array(), 'x.php')['modules'] === array());

// --- search index: every visible page with its path
$index = menu_search_index($admin);
$by_title = array();
foreach ($index as $page) {
    $by_title[$page['title']] = $page;
}
check('search index lists every visible page', count($index) === 9);
check('search index keeps href', $by_title['Purchases by Item']['href'] === 'purchase_item_rep.php');
check('search index path shows module and folders', $by_title['Level 4 Page']['path'] === 'Purchasing › Purchases Reports › Deep Group');
check('top-level page has empty path', $by_title['Dashboard']['path'] === '');
check('search index for limited user has only allowed pages', array_map(function ($p) { return $p['title']; }, menu_search_index($user)) === array('Items', 'Purchases by Item'));

// --- recursive group html: nested folders, active one open
$group_html = menu_group_html($on_report['modules'][2]['children'][2], 0);
check('group html nests sub-folder', substr_count($group_html, "class='nav-item has-submenu'") === 2);
check('group html uses ul_menu1 then ul_menu2', strpos($group_html, 'ul_menu1 show') !== false && strpos($group_html, 'ul_menu2') !== false);
check('group holding active page is open, others closed', strpos($group_html, "ul_menu2 show") === false);
check('group html lists pages', strpos($group_html, 'purchase_item_rep.php') !== false && strpos($group_html, 'deep.php') !== false);

// --- leaf html is escaped and keeps the sidebar markup the footer JS expects
$html = menu_leaf_html(array('mencap' => 'A & <B>', 'menprogram' => "x.php?a='1'", 'menlogo' => 'fas fa-box', 'active' => true), 1);
check('leaf html escapes caption', strpos($html, 'A &amp; &lt;B&gt;') !== false);
check('leaf html escapes link', strpos($html, "x.php?a=&#039;1&#039;") !== false);
check('leaf html marks active', strpos($html, "menu_leaf active") !== false);
check('leaf html uses nav-link', strpos($html, "class='nav-link'") !== false);

// --- wiring: header loads the tree once, sidebar renders from it
$root = __DIR__ . '/..';
$header = file_get_contents($root . '/includes/main_header.php');
$menu = file_get_contents($root . '/includes/main_menu.php');
check('header loads tree with permission filename', strpos($header, 'menu_load_tree($link, $_SESSION[\'usercode\'], $userdesc == "admin", $permission_filename)') !== false);
check('header renders module tabs', strpos($header, 'module_tab') !== false);
check('sidebar renders from $menu_tree', strpos($menu, '$menu_tree[\'modules\']') !== false);
check('sidebar no longer queries user_menus layout', strpos($menu, 'FROM user_menus') === false && strpos($menu, 'FROM menus') === false);
check('sidebar renders groups recursively', strpos($menu, 'menu_group_html($group, 0)') !== false);
check('sidebar content has fixed width (no squeeze while closing)', preg_match('/#sidebar-wrapper\{[^}]*width:300px/', $menu) === 1);
check('sidebar items keep stable wrapping', preg_match('/#sidebar-wrapper \.nav-item\{\s*white-space:normal !important;/', $menu) === 1);
check('sidebar scrollbar hidden in Firefox', strpos($menu, 'scrollbar-width:none;') !== false);
check('search moved to top bar', strpos($header, 'menu_search_index($menu_tree)') !== false && strpos($menu, 'menu_search') === false);

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
