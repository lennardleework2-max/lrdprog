<?php
// Navbar tree: layout comes from `menus`, visibility from the user's `user_menus` rows.
// Level 1 = module (top bar, any row with menidx != 'x'), levels 2-4 = sub-menus / folders / pages (sidebar).

function menu_load_tree($link, $usercode, $is_admin, $current_file)
{
    $rows = $link->query("SELECT mencap, menprogram, menlogo, menidx, mennum, mensub, mengrp, is_removed, is_disabled, has_crud FROM menus")->fetchAll(PDO::FETCH_ASSOC);

    $allowed = null;
    if (!$is_admin) {
        $stmt = $link->prepare("SELECT mencap FROM user_menus WHERE usercode = ?");
        $stmt->execute(array($usercode));
        $allowed = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    }

    return menu_build_tree($rows, $allowed, $current_file);
}

// $allowed: null = admin (sees everything), otherwise array of permitted mencap => true.
// Returns ['modules' => [...nodes], 'active' => index of the module holding $current_file, or -1].
function menu_build_tree(array $rows, $allowed, $current_file)
{
    $by_grp = array();
    foreach ($rows as $row) {
        if ((string)$row['is_removed'] === '1') {
            continue;
        }
        $by_grp[$row['mengrp']][] = $row;
    }

    // same rule as the old menu: any row with menidx != 'x' is top level (Utilities/Reports use mengrp '3', not '1')
    $top = array();
    foreach ($rows as $row) {
        if ((string)$row['is_removed'] !== '1' && $row['menidx'] !== 'x') {
            $top[] = $row;
        }
    }
    usort($top, function ($a, $b) { return strcmp((string)$a['menidx'], (string)$b['menidx']); });

    $modules = array();
    $active = -1;
    foreach ($top as $row) {
        $node = menu_build_node($row, $by_grp, $allowed, $current_file, 1);
        if ($node === null) {
            continue;
        }
        if ($node['active']) {
            $active = count($modules);
        }
        $modules[] = $node;
    }

    return array('modules' => $modules, 'active' => $active);
}

// A row with mensub is a group (children have mengrp = its mensub); hidden when nothing inside is visible.
function menu_build_node($row, $by_grp, $allowed, $current_file, $depth)
{
    $node = array(
        'mencap'     => $row['mencap'],
        'menlogo'    => $row['menlogo'],
        'menprogram' => $row['menprogram'],
        'has_crud'    => isset($row['has_crud']) ? $row['has_crud'] : '',
        'is_disabled' => isset($row['is_disabled']) ? $row['is_disabled'] : '',
        'children'   => array(),
        'active'     => false,
    );

    if ((string)$row['mensub'] === '') {
        if ((string)$row['menprogram'] === '' || ($allowed !== null && !isset($allowed[$row['mencap']]))) {
            return null;
        }
        $node['active'] = $row['menprogram'] === $current_file;
        return $node;
    }

    if ($depth >= 4 || !isset($by_grp[$row['mensub']])) {
        return null;
    }

    $children = $by_grp[$row['mensub']];
    usort($children, function ($a, $b) { return (float)$a['mennum'] <=> (float)$b['mennum']; });

    foreach ($children as $child) {
        $child_node = menu_build_node($child, $by_grp, $allowed, $current_file, $depth + 1);
        if ($child_node === null) {
            continue;
        }
        $node['active'] = $node['active'] || $child_node['active'];
        $node['children'][] = $child_node;
    }

    return $node['children'] ? $node : null;
}

// First page of a module, used as the top-bar link.
function menu_first_page($node)
{
    if (!$node['children']) {
        return $node['menprogram'];
    }
    foreach ($node['children'] as $child) {
        $page = menu_first_page($child);
        if ($page !== '') {
            return $page;
        }
    }
    return '';
}

// Every page with where it lives, for the top-bar search.
function menu_search_index($tree)
{
    $pages = array();
    $walk = function ($node, $path) use (&$walk, &$pages) {
        if (!$node['children']) {
            $pages[] = array('title' => $node['mencap'], 'href' => $node['menprogram'], 'path' => implode(' › ', $path));
            return;
        }
        foreach ($node['children'] as $child) {
            $walk($child, array_merge($path, array($node['mencap'])));
        }
    };
    foreach ($tree['modules'] as $module) {
        $walk($module, array());
    }
    return $pages;
}

// Collapsible sidebar group (sub-menu or folder); markup matches the footer's accordion JS.
function menu_group_html($node, $depth)
{
    $html = "<li class='nav-item has-submenu'>"
          . "<a href='#' class='nav-link'>"
          . "<i class='" . htmlspecialchars($node['menlogo'], ENT_QUOTES) . "' style='margin-left:" . ($depth * 8) . "px'></i>"
          . "<span class='nav-txt'>" . htmlspecialchars($node['mencap']) . "</span>"
          . "</a>"
          . "<ul class='submenu collapse ul_menu " . ($depth > 0 ? "ul_menu2" : "ul_menu1") . ($node['active'] ? " show" : "") . "' style='list-style-type: none'>";

    foreach ($node['children'] as $child) {
        $html .= $child['children'] ? menu_group_html($child, $depth + 1) : menu_leaf_html($child, $depth + 1);
    }

    return $html . "</ul></li>";
}

function menu_leaf_html($node, $depth)
{
    return "<li class='nav-item menu_leaf" . ($node['active'] ? " active" : "") . "'>"
         . "<a href='" . htmlspecialchars($node['menprogram'], ENT_QUOTES) . "' class='nav-link'>"
         . "<i class='" . htmlspecialchars($node['menlogo'], ENT_QUOTES) . "' style='margin-left:" . ($depth * 8) . "px'></i>"
         . "<span class='nav-txt'>" . htmlspecialchars($node['mencap']) . "</span>"
         . "</a></li>";
}
