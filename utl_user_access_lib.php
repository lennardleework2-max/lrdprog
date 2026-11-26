<?php
// User Access screen: one recursive renderer for the menu tree (any depth, built by menu_build_tree()).
// Field names are what utl_user_access_ajax_save.php reads: menu[mencap][value|add|edit|view|delete|export|hide_*].
// Each hidden input is posted first so an unticked checkbox still sends its 0.

function ua_render_nodes(array $nodes, array $user_rows, $depth = 0)
{
    $html = "";
    foreach ($nodes as $node) {
        $html .= ua_render_node($node, $user_rows, $depth);
    }
    return $html;
}

function ua_render_node($node, $user_rows, $depth)
{
    $cap    = $node['mencap'];
    $name   = "menu[" . htmlspecialchars($cap, ENT_QUOTES) . "]";
    $row    = isset($user_rows[$cap]) ? $user_rows[$cap] : null;
    $indent = $depth * 1.5;

    // disabled menus stay granted: the hidden 1 is posted, the (disabled) checkbox is not
    $disabled = (string)$node['is_disabled'] === '1';
    $hidden   = $disabled ? 1 : 0;
    $value    = $disabled ? 0 : 1;
    $checked  = ($row !== null || $disabled) ? " checked" : "";
    $label    = $node['children'] ? "<b>" . htmlspecialchars($cap) . "</b>" : htmlspecialchars($cap);

    $html = "<div class='ua_node'>"
          . "<div class='form-group my-2 ua_row' style='margin-left:" . ($indent + 0.5) . "rem'>"
          . "<div class='form-check'>"
          . "<input type='hidden' value='" . $hidden . "' name='" . $name . "[value]'>"
          . "<input type='checkbox' value='" . $value . "' name='" . $name . "[value]' class='form-check-input ua_main'" . $checked . ($disabled ? " disabled" : "") . ">"
          . "<label class='form-check-label'>" . $label . "</label>"
          . "</div>"
          . "</div>";

    if (!$node['children'] && $node['has_crud'] === 'Y') {
        $html .= ua_crud_html($cap, $name, $row, $hidden, $value, $indent + 2);
    }

    if ($node['children']) {
        $html .= "<div class='ua_children'>" . ua_render_nodes($node['children'], $user_rows, $depth + 1) . "</div>";
    }

    return $html . "</div>";
}

function ua_crud_html($cap, $name, $row, $hidden, $value, $indent)
{
    $cruds = array(
        'add'    => array('Add', '#00b33c'),
        'edit'   => array('Edit', '#008ae6'),
        'view'   => array('View', '#7733ff'),
        'delete' => array('Delete', '#ff3333'),
        'export' => array('Export', '#ff6600'),
    );

    $html = "<div class='d-flex flex-wrap ua_cruds' style='margin-left:" . $indent . "rem;column-gap:1.5rem'>";

    foreach ($cruds as $key => $crud) {
        $on = ($row !== null && $row[$key] == 1) ? " checked" : "";
        $html .= ua_check_html($name . "[" . $key . "]", $hidden, $value, "ua_crud", $on, $crud[0], $crud[1], " data-crud='" . $key . "'");
    }

    // price-hiding options for the pages that show prices
    if ($cap === "Purchases Transaction") {
        $on = ($row !== null && $row['hide_price'] == 1) ? " checked" : "";
        $html .= ua_check_html($name . "[hide_price]", $hidden, $value, "ua_hide", $on, "Hide Price", "#003300");
    }
    if (in_array($cap, array("Items", "Sales Transaction", "Sales Order Transaction"), true)) {
        $on = ($row !== null && (string)$row['hide_cost_price'] === '1') ? " checked" : "";
        $html .= ua_check_html($name . "[hide_cost_price]", 0, 1, "ua_hide", $on, "Hide Cost Price", "#6c3f00");
        $on = ($row !== null && (string)$row['hide_distri_price'] === '1') ? " checked" : "";
        $html .= ua_check_html($name . "[hide_distri_price]", 0, 1, "ua_hide", $on, "Hide Distributor Price", "#6c3f00");
    }

    return $html . "</div>";
}

function ua_check_html($field, $hidden, $value, $class, $checked, $text, $color, $attrs = "")
{
    return "<div class='form-check text-nowrap'>"
         . "<input type='hidden' value='" . $hidden . "' name='" . $field . "'>"
         . "<input type='checkbox' value='" . $value . "' name='" . $field . "' class='form-check-input " . $class . "'" . $attrs . $checked . ">"
         . "<label class='form-check-label' style='font-size:16px;color:" . $color . ";text-shadow:0 0 0 " . $color . ";'>" . $text . "</label>"
         . "</div>";
}
