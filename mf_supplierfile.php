<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";

// Preload all used suppcde from tranfile1 and purchasesorderfile1
$supplier_in_use_recids = array();

$select_used_suppcde = "SELECT DISTINCT suppcde FROM tranfile1 WHERE suppcde IS NOT NULL AND suppcde <> ''
                        UNION
                        SELECT DISTINCT suppcde FROM purchasesorderfile1 WHERE suppcde IS NOT NULL AND suppcde <> ''";
$stmt_used_suppcde = $link->prepare($select_used_suppcde);
$stmt_used_suppcde->execute();
$used_suppcde_list = array();
while($row_used = $stmt_used_suppcde->fetch(PDO::FETCH_ASSOC)){
    $used_suppcde_list[] = trim((string)$row_used['suppcde']);
}

// Get recids of suppliers that are in use
if(!empty($used_suppcde_list)){
    $placeholders = implode(',', array_fill(0, count($used_suppcde_list), '?'));
    $select_supplier_recids = "SELECT recid, suppcde FROM supplierfile WHERE suppcde IN ($placeholders)";
    $stmt_supplier_recids = $link->prepare($select_supplier_recids);
    $stmt_supplier_recids->execute($used_suppcde_list);
    while($row_supplier = $stmt_supplier_recids->fetch(PDO::FETCH_ASSOC)){
        $supplier_in_use_recids[] = (string)$row_supplier['recid'];
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
                           
                            $table1 = new pager("Supplier" , "supplierfile"  ,$link);
                            
                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;

                            $table1->customize_function_name = 'supplierAction';
                            //$table1->display_only = "Y";

                            $table1->field_code = "suppcde";
                            $table1->field_code_init = "SUP-00001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "suppdsc";
                            $table1->table_order_by["type"] = "ASC";

                            //FIELDS  DISPLAY
                            $table1->field_type_dis["suppdsc"] = "text";
                            $table1->field_name_dis["suppdsc"] = "suppdsc";
                            $table1->field_header_dis["suppdsc"] = "Supplier";
        

                            //FIELDS  CRUD(create,read,update,delete)
                            $table1->field_type_crud["suppdsc"] = "text";
                            $table1->field_name_crud["suppdsc"] = "suppdsc";
                            $table1->field_header_crud["suppdsc"] = "Supplier";
                            $table1->field_is_required["suppdsc"] = "Y"; 
                            $table1->field_is_unique["suppdsc"] = "Y";

                        

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
                            $table1->ua_field1  = "suppdsc";
                            $table1->ua_field2  = "suppcde";

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
<script src="pager/pager_js.class.js"></script>
<script>
var supplierInUseRecids = <?php echo json_encode($supplier_in_use_recids); ?>;

function supplierAction(event, recid, custom_param){
    if(event === "delete" && supplierInUseRecids.indexOf(String(recid)) !== -1){
        alert("Cannot be deleted, supplier in use");
        return;
    }
    ajaxFunc(event, recid, custom_param);
}

function applySupplierDeleteState(scopeSelector){
    var scope = document.querySelector(scopeSelector);
    if(!scope){
        return;
    }

    supplierInUseRecids.forEach(function(recid){
        var deleteMenus = scope.querySelectorAll("[aria-labelledby='dropdownMenuButton1-" + recid + "']");
        deleteMenus.forEach(function(menu){
            menu.querySelectorAll("li").forEach(function(item){
                var itemText = item.textContent || "";
                if(itemText.indexOf("Delete") !== -1){
                    item.style.opacity = "0.5";
                }
            });
        });
    });
}

document.addEventListener("DOMContentLoaded", function(){
    applySupplierDeleteState("#tbody_main");
    applySupplierDeleteState("#tbody_main_mobile");

    ["tbody_main", "tbody_main_mobile"].forEach(function(targetId){
        var target = document.getElementById(targetId);
        if(!target){
            return;
        }

        var observer = new MutationObserver(function(){
            applySupplierDeleteState("#" + targetId);
        });

        observer.observe(target, { childList: true, subtree: true });
    });
});
</script>
<?php
require "includes/main_footer.php";
?>

