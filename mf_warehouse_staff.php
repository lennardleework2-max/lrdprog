<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if(isset($_POST['warehouse_staff_add_action']) && $_POST['warehouse_staff_add_action'] === '1'){
    session_start();
    require "resources/db_init.php";
    require "resources/connect4.php";
    require_once("resources/lx2.pdodb.php");
    require "resources/stdfunc100.php";

    $xret = array();
    $xret["status"] = 1;
    $xret["msg"] = "";

    $fname = trim((string)(isset($_POST['fname']) ? $_POST['fname'] : ''));
    $lname = trim((string)(isset($_POST['lname']) ? $_POST['lname'] : ''));
    $role = trim((string)(isset($_POST['role']) ? $_POST['role'] : ''));

    if($fname === ''){
        $xret["status"] = 0;
        $xret["msg"] = "<b>First Name</b> cannot be empty.";
    }

    if($lname === ''){
        if($xret["status"] == 0){
            $xret["msg"] .= "</br>";
        }
        $xret["status"] = 0;
        $xret["msg"] .= "<b>Last Name</b> cannot be empty.";
    }

    if($xret["status"] == 1){
        $warehouse_staff_id = "WSTF-0001";
        $select_last_code = "SELECT warehouse_staff_id FROM warehouse_staff ORDER BY warehouse_staff_id DESC LIMIT 1";
        $stmt_last_code = $link->prepare($select_last_code);
        $stmt_last_code->execute();
        $rs_last_code = $stmt_last_code->fetch();

        if($rs_last_code && !empty($rs_last_code['warehouse_staff_id'])){
            $warehouse_staff_id = lNexts($rs_last_code['warehouse_staff_id']);
        }

        $arr_record_data = array();
        $arr_record_data['fname'] = $fname;
        $arr_record_data['lname'] = $lname;
        $arr_record_data['role'] = ($role === '') ? NULL : $role;
        $arr_record_data['warehouse_staff_id'] = $warehouse_staff_id;

        PDO_InsertRecord($link, 'warehouse_staff', $arr_record_data, false);

        $username_session = useractivitylog_get_session_username();
        $username_full_name = useractivitylog_get_session_fullname($link);
        $full_name = trim($fname . ' ' . $lname);
        $xremarks = $username_session . " added warehouse staff '" . useractivitylog_escape_value($full_name) . "'";

        PDO_UserActivityLog($link, $username_session, '', date("Y-m-d H:i:s"), 'Warehouse Staff', 'add', $username_full_name, $xremarks, 0, '', '', '', '', $username_session, $warehouse_staff_id, '');
    }

    header('Content-Type: application/json');
    echo json_encode($xret);
    exit;
}

require "includes/main_header.php";
require "pager/pager_main.class.php";

$used_warehouse_staff_ids = array();
$select_used_warehouse_staff = "SELECT DISTINCT warehouse_staff_id
                                FROM tranfile2
                                WHERE warehouse_staff_id IS NOT NULL
                                AND warehouse_staff_id <> ''";
$stmt_used_warehouse_staff = $link->prepare($select_used_warehouse_staff);
$stmt_used_warehouse_staff->execute();
while($rs_used_warehouse_staff = $stmt_used_warehouse_staff->fetch()){
    $used_warehouse_staff_ids[] = $rs_used_warehouse_staff["warehouse_staff_id"];
}

$warehouse_staff_in_use_recids = array();
$select_warehouse_staff = "SELECT recid, warehouse_staff_id FROM warehouse_staff";
$stmt_warehouse_staff = $link->prepare($select_warehouse_staff);
$stmt_warehouse_staff->execute();
while($rs_warehouse_staff = $stmt_warehouse_staff->fetch()){
    if(in_array($rs_warehouse_staff["warehouse_staff_id"], $used_warehouse_staff_ids, true)){
        $warehouse_staff_in_use_recids[(string)$rs_warehouse_staff["recid"]] = true;
    }
}

?>

    <style>

        .data_table{
            border-collapse:collapse;
            width:100%;
        }

        .data_table tbody tr:nth-child(even){
            background-color:#f5f5f5;
        }

    </style>

    <script>
        function john(event2, id){
            alert(event2+","+id);
        }
    </script>

    <form name='myforms' id="myforms" method="post" target="_self"> 

        <table class='big_table'> 

            <tr colspan=1>

                <td colspan=1 class='td_bl'>
                                            
                    <?php
                        include 'includes/main_menu.php';
                    ?>
                </td>
 
                <td colspan=1 class="td_br" id="td_br">

                    <div class="container-fluid pt-2 main_br_div">

                        <?php
                           
                            $table1 = new pager("Warehouse Staff" , "warehouse_staff"  ,$link);
                            
                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;
                            //$table1->customize_function_name = 'sample_func';
                            //$table1->display_only = "Y";

                            $table1->field_code = "warehouse_staff_id";
                            $table1->field_code_init = "WSTF-0001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "fname";
                            $table1->table_order_by["type"] = "ASC";

                            //FIELDS  DISPLAY
                            $table1->field_type_dis["fname"] = "text";
                            $table1->field_name_dis["fname"] = "fname";
                            $table1->field_header_dis["fname"] = "First Name";

                            $table1->field_type_dis["lname"] = "text";
                            $table1->field_name_dis["lname"] = "lname";
                            $table1->field_header_dis["lname"] = "Last Name";

                            $table1->field_type_dis["role"] = "text";
                            $table1->field_name_dis["role"] = "role";
                            $table1->field_header_dis["role"] = "Role";
        

                            //FIELDS  CRUD(create,read,update,delete)
                            $table1->field_type_crud["fname"] = "text";
                            $table1->field_name_crud["fname"] = "fname";
                            $table1->field_header_crud["fname"] = "First Name";
                            $table1->field_is_required["fname"] = "Y"; 
                            $table1->field_is_unique["fname"] = "N";

                            $table1->field_type_crud["lname"] = "text";
                            $table1->field_name_crud["lname"] = "lname";
                            $table1->field_header_crud["lname"] = "Last Name";
                            $table1->field_is_required["lname"] = "Y"; 
                            $table1->field_is_unique["lname"] = "N";

                            
                            $table1->field_type_crud["role"] = "text";
                            $table1->field_name_crud["role"] = "role";
                            $table1->field_header_crud["role"] = "Role";
                            $table1->field_is_required["role"] = "N"; 
                            $table1->field_is_unique["role"] = "N";

                            //$table1->field_is_required["critical_qty"] = "Y"; 
                            //$table1->field_is_unique["critical_qty"] = "N";

                            //pager
                            $table1->show_pager = "Y";
                            $table1->pager_xlimit = 20;

                            //export
                            $table1->show_export = "Y";

                            //search
                             $table1->show_search = "Y";

                            //alert
                            $table1->alert_del = "Y";
                            $table1->alert_del_logo_dir = $logo_dir;
                            
                            $table1->alert_del_logo_w = $logo_width;
                            $table1->alert_del_logo_h = $logo_height;

                            //user activity log
                            $table1->ua_field1  = "lname";
                            $table1->ua_field2  = "warehouse_staff_id";

                            //CRUD
                            $table1->display_table();
                        ?>

                    </div>
                </td>

            </tr>
        </table>


    </form>

    
    <?php 
        // displays modal outside form to avoid confusion
        $table1->display_modal();
    ?>



<!-- PAGER JS -->   
<script src="pager/pager_js.class.js"> </script>
<script>
(function(){
    var originalAjaxFunc = window.ajaxFunc;
    var lockedWarehouseStaffRecids = <?php echo json_encode($warehouse_staff_in_use_recids); ?>;
    var currentEditRecid = null;
    var currentCrudMode = '';

    function isWarehouseStaffInUse(recid){
        if(!recid){
            return false;
        }
        return lockedWarehouseStaffRecids[String(recid)] === true;
    }

    function styleLockedWarehouseStaffActions(){
        $('#tbody_main, #tbody_main_mobile').find("button.dropdown-toggle[id^='dropdownMenuButton1-']").each(function(){
            var $button = $(this);
            var recid = ($button.attr('id') || '').replace('dropdownMenuButton1-', '');
            var $dropdown = $button.closest('.dropdown');

            $dropdown.find('ul.main_action_dd > li').each(function(){
                var $item = $(this);
                var itemText = $.trim($item.text()).toLowerCase();
                var $link = $item.find('a.dropdown-item');

                if(itemText === 'delete'){
                    if(isWarehouseStaffInUse(recid)){
                        $item.css('opacity', '0.5');
                        $item.attr('data-warehouse-staff-in-use-action', 'delete');
                        $link.css({
                            opacity: '0.5',
                            pointerEvents: 'none'
                        });
                    }else{
                        $item.css('opacity', '');
                        $item.removeAttr('data-warehouse-staff-in-use-action');
                        $link.css({
                            opacity: '',
                            pointerEvents: ''
                        });
                    }
                }
            });
        });
    }

    function applyEditFieldRestrictions(){
        var $modal = $('#crudModal');
        if(!$modal.length){
            return;
        }

        var recid = currentEditRecid || $('#recid_hidden').val();
        var isInUse = isWarehouseStaffInUse(recid);

        $modal.find('input, select, textarea').not('[type="hidden"], [name="role"], [name="role_crudModal"]').each(function(){
            var $field = $(this);
            if(isInUse){
                $field.prop('disabled', true);
                $field.css('background-color', '#e9ecef');
            }else{
                $field.prop('disabled', false);
                $field.css('background-color', '');
            }
        });

        $modal.find('[name="role"], [name="role_crudModal"]').each(function(){
            var $field = $(this);
            $field.prop('disabled', false);
            $field.css('background-color', '');
        });
    }

    function resetAddFieldState(){
        var $modal = $('#crudModal');
        if(!$modal.length){
            return;
        }

        $modal.find('input, select, textarea').not('[type="hidden"]').each(function(){
            var $field = $(this);
            $field.prop('disabled', false);
            $field.prop('readonly', false);
            $field.css('background-color', '');
        });
    }

    function insertWarehouseStaffWithFullNameLog(){
        $.ajax({
            data:{
                warehouse_staff_add_action: '1',
                fname: $('#fname_crudModal').val(),
                lname: $('#lname_crudModal').val(),
                role: $('#role_crudModal').val()
            },
            dataType:"json",
            type:"post",
            url:"mf_warehouse_staff.php",
            success: function(xdata){
                if(xdata["status"] == 0){
                    $(".error_msg").html("<div class='alert alert-danger' role='alert'>"+xdata["msg"]+"</div>");
                }else if(xdata["status"] == 1){
                    $('#crudModal').modal('hide');
                    page_click("same");
                }
            },
            error: function (request) {
                alert(request.responseText);
            }
        });
    }

    window.ajaxFunc = function(event, recid, custom_param){
        if(event === 'openInsert'){
            currentCrudMode = 'add';
            currentEditRecid = null;
            resetAddFieldState();
        }

        if(event === 'getEdit'){
            currentCrudMode = 'edit';
            currentEditRecid = recid;
        }

        if(event === 'insert'){
            insertWarehouseStaffWithFullNameLog();
            return false;
        }

        if(event === 'delete' && isWarehouseStaffInUse(recid)){
            alert('Warehouse staff in use, cannot delete');
            return false;
        }

        return originalAjaxFunc(event, recid, custom_param);
    };

    $(document).ready(function(){
        document.addEventListener('click', function(e){
            var lockedAction = $(e.target).closest('#tbody_main .main_action_dd > li[data-warehouse-staff-in-use-action], #tbody_main_mobile .main_action_dd > li[data-warehouse-staff-in-use-action]');

            if(!lockedAction.length){
                return;
            }

            var lockedActionType = lockedAction.attr('data-warehouse-staff-in-use-action');
            if(lockedActionType === 'delete'){
                e.preventDefault();
                e.stopPropagation();
                if(typeof e.stopImmediatePropagation === 'function'){
                    e.stopImmediatePropagation();
                }
                alert('Warehouse staff in use, cannot delete');
                return false;
            }
        }, true);

        $(document).ajaxComplete(function(){
            styleLockedWarehouseStaffActions();
            if(currentCrudMode === 'edit'){
                setTimeout(applyEditFieldRestrictions, 100);
            }
        });

        $('#crudModal').on('shown.bs.modal', function(){
            if(currentCrudMode === 'add'){
                setTimeout(resetAddFieldState, 50);
            }else if(currentCrudMode === 'edit'){
                setTimeout(applyEditFieldRestrictions, 50);
            }
        });

        styleLockedWarehouseStaffActions();
    });
})();
</script>
<?php 
require "includes/main_footer.php";
?>
