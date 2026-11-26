<?php 
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require "includes/main_header.php";
require "pager/pager_main.class.php";

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

        function goItemUnitOfMeasure(itmcde){
            if(!itmcde){
                return;
            }
            var form = document.createElement("form");
            form.method = "POST";
            form.action = "mf_item_uom.php";

            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "itmcde";
            input.value = itmcde;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
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
                           
                            $table1 = new pager("Items" , "itemfile"  ,$link);
                            
                            $table1->add_crud = $add_crud;
                            $table1->edit_crud = $edit_crud;
                            $table1->delete_crud = $delete_crud;
                            $table1->view_crud = $view_crud;
                            $table1->export_crud = $export_crud;
                            //$table1->customize_function_name = 'sample_func';
                            //$table1->display_only = "Y";

                            $table1->field_code = "itmcde";
                            $table1->field_code_init = "ITM-0001";

                            //ORDER (table ORDER BY)
                            $table1->table_order_by["field"] = "itmdsc";
                            $table1->table_order_by["type"] = "ASC";


                            //FIELDS  DISPLAY
                            $table1->field_type_dis["itemdsc"] = "text";
                            $table1->field_name_dis["itemdsc"] = "itmdsc";
                            $table1->field_header_dis["itemdsc"] = "Items";

                            $table1->field_type_dis["critical_qty"] = "number";
                            $table1->field_name_dis["critical_qty"] = "critical_qty";
                            $table1->field_header_dis["critical_qty"] = "Critical Quantity";

                            $table1->field_type_dis["tiktok_itm_sku"] = "text";
                            $table1->field_name_dis["tiktok_itm_sku"] = "tiktok_itm_sku";
                            $table1->field_header_dis["tiktok_itm_sku"] = "Tiktok Item SKU";

                            $table1->field_type_dis["lazada_itm_sku"] = "text";
                            $table1->field_name_dis["lazada_itm_sku"] = "lazada_itm_sku";
                            $table1->field_header_dis["lazada_itm_sku"] = "Lazada Item SKU";

                            $table1->field_type_dis["shopee_itm_sku"] = "text";
                            $table1->field_name_dis["shopee_itm_sku"] = "shopee_itm_sku";
                            $table1->field_header_dis["shopee_itm_sku"] = "Shopee Item SKU";

                            //FIELDS  CRUD(create,read,update,delete)
                            $table1->field_type_crud["itemdsc"] = "text";
                            $table1->field_name_crud["itemdsc"] = "itmdsc";
                            $table1->field_header_crud["itemdsc"] = "Items";
                            $table1->field_is_required["itemdsc"] = "Y"; 
                            $table1->field_is_unique["itemdsc"] = "Y";

                            $table1->field_type_crud["critical_qty"] = "number";
                            $table1->field_name_crud["critical_qty"] = "critical_qty";
                            $table1->field_header_crud["critical_qty"] = "Critical Quantity";
                            $table1->field_is_required["critical_qty"] = "Y"; 
                            $table1->field_is_unique["critical_qty"] = "N";

                            $table1->field_type_crud["tiktok_itm_sku"] = "text";
                            $table1->field_name_crud["tiktok_itm_sku"] = "tiktok_itm_sku";
                            $table1->field_header_crud["tiktok_itm_sku"] = "Tiktok Item SKU";
                            $table1->field_is_required["tiktok_itm_sku"] = "N"; 
                            $table1->field_is_unique["tiktok_itm_sku"] = "Y";

                            $table1->field_type_crud["lazada_itm_sku"] = "text";
                            $table1->field_name_crud["lazada_itm_sku"] = "lazada_itm_sku";
                            $table1->field_header_crud["lazada_itm_sku"] = "Lazada Item SKU";
                            $table1->field_is_required["lazada_itm_sku"] = "N"; 
                            $table1->field_is_unique["lazada_itm_sku"] = "N";

                            $table1->field_type_crud["shopee_itm_sku"] = "text";
                            $table1->field_name_crud["shopee_itm_sku"] = "shopee_itm_sku";
                            $table1->field_header_crud["shopee_itm_sku"] = "Shopee Item SKU";
                            $table1->field_is_required["shopee_itm_sku"] = "N";
                            $table1->field_is_unique["shopee_itm_sku"] = "N";

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
                            $table1->ua_field1  = "itmdsc";
                            $table1->ua_field2  = "itmcde";

                            // Custom button for Unit of Measure
                            $table1->btn_header[0] = "Unit of Measure";
                            $table1->btn_logo[0] = "<i class='fas fa-ruler'></i>";
                            $table1->btn_function[0] = "goItemUnitOfMeasure('{itmcde}')";
                            $table1->btn_color[0] = "#17a2b8";

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

        $batch_price_types = array();
        if(!$hide_distri_price_crud){ $batch_price_types["distributor"] = "Distributor Price"; }
        if(!$hide_cost_price_crud){ $batch_price_types["cost"] = "Cost Price"; }
        $batch_price_types["wholesale"] = "Wholesale Price";
        $batch_price_types["retail"] = "Retail Price";
    ?>

    <?php if((int)$edit_crud == 1): ?>
    <div class="modal fade" id="batchPriceModal" tabindex="-1" aria-labelledby="batchPriceTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="batchPriceTitle">Batch Price Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div id="batchPriceForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input batch_price_mode" type="radio" name="batch_price_mode" id="batch_mode_fixed" value="fixed" checked>
                                <label class="form-check-label" for="batch_mode_fixed">Set fixed price</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input batch_price_mode" type="radio" name="batch_price_mode" id="batch_mode_percent" value="percent">
                                <label class="form-check-label" for="batch_mode_percent">Adjust by %</label>
                            </div>
                        </div>
                        <div class="mb-3">Tick the prices to update:</div>
                        <?php foreach($batch_price_types as $type => $label): ?>
                        <div class="row align-items-center mb-2">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input batch_price_chk" type="checkbox" id="batch_chk_<?php echo $type; ?>" data-type="<?php echo $type; ?>" data-label="<?php echo $label; ?>">
                                    <label class="form-check-label" for="batch_chk_<?php echo $type; ?>"><?php echo $label; ?></label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text batch_price_unit">₱</span>
                                    <input type="number" min="0" step="0.01" class="form-control batch_price_input" id="batch_price_<?php echo $type; ?>" placeholder="0.00" disabled>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="small text-muted" id="batchPricePercentHint" style="display:none">Use a negative % to decrease (e.g. -10). Items with no price are skipped.</div>
                        <div class="alert alert-warning py-2 mt-3 mb-0">Ticked prices will be applied to <b>ALL items</b>.</div>
                        <div class="text-danger small mt-2" id="batchPriceError"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="batchPriceReview()">Apply <i class="fas fa-check"></i></button>
                    </div>
                </div>

                <div id="batchPriceConfirm" style="display:none">
                    <div class="modal-body">
                        <div>You are about to update <b>ALL items</b>:</div>
                        <ul class="my-2" id="batchPriceSummary"></ul>
                        <div class="alert alert-warning py-2 mb-0" id="batchPriceConfirmNote"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="batchPriceStep('form')">Back</button>
                        <button type="button" class="btn btn-success" id="batchPriceSaveBtn" onclick="batchPriceSave()">Yes, update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


   

<!-- PAGER JS -->
<script src="pager/pager_js.class.js"> </script>
<script>
    $(document).ready(function(){

        var salesPriceFields = [
            <?php if(!$hide_distri_price_crud): ?>
            {name: "salesprice_distributor", label: "Distributor Price"},
            <?php endif; ?>
            <?php if(!$hide_cost_price_crud): ?>
            {name: "salesprice_cost",        label: "Cost Price"},
            <?php endif; ?>
            {name: "salesprice_wholesale",   label: "Wholesale Price"},
            {name: "salesprice_retail",      label: "Retail Price"}
        ];

        var salesPriceHtml = "<div class='row m-3'><div class='col-12'><h6 class='mb-0 mt-2'>Sales Price List</h6></div></div>";

        salesPriceFields.forEach(function(field){
            salesPriceHtml += "<div class='row m-3' id='crudModal_values'>";
            salesPriceHtml +=     "<div class='col-12'>";
            salesPriceHtml +=         "<label> " + field.label + "</label>";
            salesPriceHtml +=         "<input type='text' name='" + field.name + "_crudModal' id='" + field.name + "_crudModal' data-value='" + field.label + "_crudModal' data-is-required='N_crudModal' data-is-unique='N_crudModal' data-field-type='text' class='form-control' autocomplete='off'>";
            salesPriceHtml +=     "</div>";
            salesPriceHtml += "</div>";
            salesPriceHtml += "<input type='hidden' name='field_type_hidden' class='field_type_hidden' value='text' hidden_id_crud='" + field.name + "'>";
        });

        $("#critical_qty_crudModal").closest(".row.m-3").after(salesPriceHtml);

        // Batch Price button: placed after Add Record / Print
        if($("#batchPriceModal").length){
            var batchBtn = "<button type='button' class='btn btn-warning m-2 fw-bold' onclick='batchPriceOpen()'>Batch Price <i class='fas fa-tags' style='margin-left: 3px'></i></button>";
            var addBtn = $("button[value='Add Record']");
            if(addBtn.length){
                var printMenu = addBtn.nextAll("ul.dropdown-menu").first();
                (printMenu.length ? printMenu : addBtn).after(batchBtn);
            }else{
                $("#td_br h2").first().parent().after("<div class='col-auto mx-0 px-0'>" + batchBtn + "</div>");
            }
        }

        $(".batch_price_chk").on("change", function(){
            var input = $("#batch_price_" + $(this).data("type"));
            input.prop("disabled", !this.checked);
            if(this.checked){ input.focus(); }
        });

        $(".batch_price_mode").on("change", batchPriceSetMode);
    });

    function batchPriceMode(){
        return $("#batch_mode_percent").is(":checked") ? "percent" : "fixed";
    }

    function batchPriceSetMode(){
        var percent = batchPriceMode() === "percent";
        $(".batch_price_unit").text(percent ? "%" : "₱");
        $(".batch_price_input").val("")
            .attr("min", percent ? null : "0")
            .attr("placeholder", percent ? "e.g. 10 or -10" : "0.00");
        $("#batchPricePercentHint").toggle(percent);
        $("#batchPriceError").text("");
    }

    function batchPriceStep(step){
        $("#batchPriceForm").toggle(step === "form");
        $("#batchPriceConfirm").toggle(step === "confirm");
    }

    function batchPriceOpen(){
        $(".batch_price_chk").prop("checked", false);
        $(".batch_price_input").prop("disabled", true);
        $("#batch_mode_fixed").prop("checked", true);
        batchPriceSetMode();
        batchPriceStep("form");
        $("#batchPriceModal").modal("show");
    }

    function batchPriceCollect(){
        var mode = batchPriceMode();
        var prices = {};
        var error = "";
        $(".batch_price_chk:checked").each(function(){
            var type = $(this).data("type");
            var val = $.trim($("#batch_price_" + type).val());
            if(mode === "percent"){
                if(val === "" || isNaN(val) || Number(val) === 0 || Number(val) < -100){
                    error = "Enter a percentage (not 0, at least -100) for " + $(this).data("label") + ".";
                    return false;
                }
            }else if(val === "" || isNaN(val) || Number(val) < 0){
                error = "Enter a price of 0 or more for " + $(this).data("label") + ".";
                return false;
            }
            prices[type] = val;
        });
        if(!error && $.isEmptyObject(prices)){
            error = "Tick at least one price to update.";
        }
        return {mode: mode, prices: prices, error: error};
    }

    function batchPriceReview(){
        var data = batchPriceCollect();
        $("#batchPriceError").text(data.error);
        if(data.error){ return; }

        var html = "";
        $.each(data.prices, function(type, val){
            var num = Number(val);
            var shown = data.mode === "percent"
                ? (num > 0 ? "+" : "") + num + "% (" + (num > 0 ? "increase" : "decrease") + ")"
                : "&#8369;" + num.toLocaleString("en-PH", {minimumFractionDigits: 2, maximumFractionDigits: 2});
            html += "<li>" + $("#batch_chk_" + type).data("label") + " &rarr; <b>" + shown + "</b></li>";
        });
        $("#batchPriceSummary").html(html);
        $("#batchPriceConfirmNote").text(data.mode === "percent"
            ? "Existing prices will be changed by this %, rounded to 2 decimals. Items with no price are left unchanged."
            : "Existing prices will be replaced. Items with no price yet will get this price.");
        batchPriceStep("confirm");
    }

    function batchPriceSave(){
        var data = batchPriceCollect();
        var saveBtn = $("#batchPriceSaveBtn").prop("disabled", true);

        $.ajax({
            url: "mf_itemfile_batchprice_ajax.php",
            type: "POST",
            dataType: "json",
            data: {mode: data.mode, prices: data.prices},
            success: function(res){
                if(res.success){
                    $("#batchPriceModal").modal("hide");
                    alert(res.message);
                }else{
                    batchPriceStep("form");
                    $("#batchPriceError").text(res.message);
                }
            },
            error: function(xhr){
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "The prices could not be saved.";
                batchPriceStep("form");
                $("#batchPriceError").text(msg);
            },
            complete: function(){
                saveBtn.prop("disabled", false);
            }
        });
    }
</script>
<?php
require "includes/main_footer.php";
?>
