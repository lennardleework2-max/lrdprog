<?php
require "includes/main_header.php";
// $trncde = "SAL";
?>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        #ui-datepicker-div {
            z-index: 2000 !important;
        }

        .export-loading{
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: #ffffff;
            border: 1px solid rgba(33, 37, 41, 0.1);
            border-radius: 0.95rem;
            padding: 1.2rem 1.5rem;
            box-shadow: 0 12px 24px rgba(33, 37, 41, 0.15);
            min-width: 320px;
        }

        .export-loading.active{
            display: block;
        }

        .export-loading-overlay{
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9998;
        }

        .export-loading-overlay.active{
            display: block;
        }

        .export-loading-label{
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-weight: 700;
            color: #212529;
        }

        .export-loading-bar{
            height: 0.5rem;
            border-radius: 999px;
            overflow: hidden;
            background: #e5e7eb;
            margin-top: 0.55rem;
            position: relative;
        }

        .export-loading-bar span{
            display: block;
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #198754 0%, #20c997 100%);
            border-radius: 999px;
            transition: width 0.15s ease-out;
        }

        .export-loading-percent{
            display: inline-block;
            min-width: 3rem;
            text-align: right;
            font-weight: 700;
            color: #198754;
            font-size: 0.95rem;
            margin-left: 0.5rem;
        }

        .export-loading-message{
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            min-height: 1.2em;
            transition: opacity 0.3s ease;
        }
    </style>
    <!-- Export Loading Overlay and Box -->
    <div class="export-loading-overlay" id="exportLoadingOverlay"></div>
    <div class="export-loading" id="exportLoading">
        <div class="export-loading-label">
            <i class="fas fa-sync-alt fa-spin"></i>
            Generating export...
            <span class="export-loading-percent" id="exportLoadingPercent">0%</span>
        </div>
        <div class="export-loading-bar"><span id="exportLoadingBarFill"></span></div>
        <div class="export-loading-message" id="exportLoadingMessage">Preparing your report...</div>
    </div>

    <form name='myforms' id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)">
        <table class='big_table'> 
            <tr colspan=1>
                <td colspan=1 class='td_bl'>
                    <?php
                        require 'includes/main_menu.php';
                    ?>
                </td>

                <td colspan=1 class="td_br" id="td_br">
                    <div class="container-fluid w-100 h-100">
                        <div class="row h-100 w-100 justify-content-center align-items-center">
                            <table style='height:80%;background-color:white;width:40%'>
                                <tr>
                                    <td class="text-center">
                                        <h3>Inventory Balance </br> By Warehouse</h3>
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td>
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top ps-1">
                                            <div style="width:80%">
                                                <label for="">Date:</label>
                                                <input type="text" class="form-control date_picker" name="date_search" id="date_search" autocomplete="off" readonly>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td>
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <label for="">Warehouse:</label>
                                                <select class="form-select" id="warehouse" name="warehouse" autocomplete="off" style="width:100%">
                                                    <?php
                                                        $select_db_warehouse="SELECT * FROM warehouse ORDER BY warcde";
                                                        $stmt_warehouse = $link->prepare($select_db_warehouse);
                                                        $stmt_warehouse->execute();
                                                        $first_warehouse = true;
                                                        $default_warcde = '';

                                                        while($rs_warehouse = $stmt_warehouse->fetch()){
                                                            $selected = $first_warehouse ? ' selected' : '';
                                                            if($first_warehouse){
                                                                $default_warcde = $rs_warehouse['warcde'];
                                                            }
                                                            echo "<option value=\"".htmlspecialchars($rs_warehouse['warcde'], ENT_QUOTES, 'UTF-8')."\"".$selected.">".htmlspecialchars($rs_warehouse['warehouse_name'], ENT_QUOTES, 'UTF-8')."</option>";
                                                            $first_warehouse = false;
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td>
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <label for="">Floor:</label>
                                                <select class="form-select" id="floor" name="floor" autocomplete="off" style="width:100%">
                                                    <?php
                                                        // Build floor map by warehouse
                                                        $stmt_floor = $link->prepare("SELECT warehouse_floor_id, warcde, floor_no, floor_name FROM warehouse_floor ORDER BY floor_no ASC, floor_name ASC, warehouse_floor_id ASC");
                                                        $stmt_floor->execute();
                                                        $warehouse_floor_map = array();

                                                        while($rs_floor = $stmt_floor->fetch()){
                                                            $floor_warcde = $rs_floor['warcde'];
                                                            if(!isset($warehouse_floor_map[$floor_warcde])){
                                                                $warehouse_floor_map[$floor_warcde] = array();
                                                            }
                                                            $floor_label = trim((string)($rs_floor['floor_no'] !== '' ? $rs_floor['floor_no'] : $rs_floor['floor_name']));
                                                            if($floor_label === '') $floor_label = 'Floor ' . $rs_floor['warehouse_floor_id'];
                                                            $warehouse_floor_map[$floor_warcde][] = array(
                                                                'warehouse_floor_id' => $rs_floor['warehouse_floor_id'],
                                                                'floor_label' => $floor_label
                                                            );
                                                        }

                                                        // Show floors for default warehouse
                                                        $first_floor = true;
                                                        if($default_warcde !== '' && isset($warehouse_floor_map[$default_warcde])){
                                                            foreach($warehouse_floor_map[$default_warcde] as $default_floor_option){
                                                                $selected = $first_floor ? ' selected' : '';
                                                                echo "<option value=\"".htmlspecialchars($default_floor_option['warehouse_floor_id'], ENT_QUOTES, 'UTF-8')."\"".$selected.">".htmlspecialchars($default_floor_option['floor_label'], ENT_QUOTES, 'UTF-8')."</option>";
                                                                $first_floor = false;
                                                            }
                                                        }
                                                        if($first_floor){
                                                            echo "<option value=\"\">-- No floors available --</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    // Output floor map as JSON for JavaScript
                                    $floor_map_json = json_encode($warehouse_floor_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                ?>
                                <script>var warehouseFloorMap = <?php echo $floor_map_json; ?>;</script>

                                <tr>
                                    <td colspan="2">

                                        <div class="row d-flex justify-content-center align-items-top btns_item">
                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>
                                            
                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1"  class="btn btn-primary" value="Export to XLS" onclick="exp_txt()">
                                            </div>
                                        </div>


                                    </td>
                                </tr>
                            
                            
                            </table>
                        </div>
                    
                    </div>
                </td>

            </tr>
        </table>
        <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php echo $trncde; ?>">
        <input type="hidden" name="txt_output_type" id="txt_output_type">
    </form>


    <script>
            // Loading bar variables
            var exportLoadingBox = document.getElementById('exportLoading');
            var exportLoadingOverlay = document.getElementById('exportLoadingOverlay');
            var exportLoadingBarFill = document.getElementById('exportLoadingBarFill');
            var exportLoadingPercentText = document.getElementById('exportLoadingPercent');
            var exportLoadingMessageElement = document.getElementById('exportLoadingMessage');
            var exportLoadingProgressInterval = null;
            var exportLoadingProgress = 0;
            var exportLoadingMessageInterval = null;
            var exportLoadingMessageIndex = 0;
            var exportLoadingDelayTimer = null;
            var exportLoadingAutoHideTimer = null;
            var exportLoadingMessages = [
                'Preparing your report...',
                'Generating export file...',
                'Processing data...',
                'Almost ready...',
                'Just a moment...'
            ];

            function updateExportLoadingProgress(percent){
                exportLoadingProgress = Math.min(100, Math.max(0, percent));
                exportLoadingBarFill.style.width = exportLoadingProgress + '%';
                exportLoadingPercentText.textContent = Math.round(exportLoadingProgress) + '%';
            }

            function startExportLoadingProgress(){
                exportLoadingProgress = 0;
                updateExportLoadingProgress(0);
                exportLoadingBox.classList.add('active');
                exportLoadingOverlay.classList.add('active');

                if(exportLoadingProgressInterval){
                    clearInterval(exportLoadingProgressInterval);
                }

                exportLoadingMessageIndex = 0;
                exportLoadingMessageElement.textContent = exportLoadingMessages[0];

                if(exportLoadingMessageInterval){
                    clearInterval(exportLoadingMessageInterval);
                }

                exportLoadingMessageInterval = setInterval(function(){
                    exportLoadingMessageIndex = (exportLoadingMessageIndex + 1) % exportLoadingMessages.length;
                    exportLoadingMessageElement.style.opacity = '0';
                    setTimeout(function(){
                        exportLoadingMessageElement.textContent = exportLoadingMessages[exportLoadingMessageIndex];
                        exportLoadingMessageElement.style.opacity = '1';
                    }, 150);
                }, 3000);

                exportLoadingProgressInterval = setInterval(function(){
                    if(exportLoadingProgress < 20){
                        exportLoadingProgress += 0.8;
                    }else if(exportLoadingProgress < 40){
                        exportLoadingProgress += 0.6;
                    }else if(exportLoadingProgress < 60){
                        exportLoadingProgress += 0.5;
                    }else if(exportLoadingProgress < 75){
                        exportLoadingProgress += 0.4;
                    }else if(exportLoadingProgress < 85){
                        exportLoadingProgress += 0.3;
                    }else if(exportLoadingProgress < 95){
                        exportLoadingProgress += 0.15;
                    }else if(exportLoadingProgress < 98){
                        exportLoadingProgress += 0.08;
                    }
                    if(exportLoadingProgress >= 98){
                        exportLoadingProgress = 98;
                    }
                    updateExportLoadingProgress(exportLoadingProgress);
                }, 100);
            }

            function completeExportLoadingProgress(){
                if(exportLoadingProgressInterval){
                    clearInterval(exportLoadingProgressInterval);
                    exportLoadingProgressInterval = null;
                }

                if(exportLoadingMessageInterval){
                    clearInterval(exportLoadingMessageInterval);
                    exportLoadingMessageInterval = null;
                }

                if(exportLoadingDelayTimer){
                    clearTimeout(exportLoadingDelayTimer);
                    exportLoadingDelayTimer = null;
                }

                if(exportLoadingAutoHideTimer){
                    clearTimeout(exportLoadingAutoHideTimer);
                    exportLoadingAutoHideTimer = null;
                }

                updateExportLoadingProgress(100);
                exportLoadingMessageElement.textContent = 'Done!';

                setTimeout(function(){
                    exportLoadingBox.classList.remove('active');
                    exportLoadingOverlay.classList.remove('active');
                    exportLoadingProgress = 0;
                    updateExportLoadingProgress(0);
                    exportLoadingMessageElement.textContent = exportLoadingMessages[0];
                }, 400);
            }

            function resetExportLoadingProgress(){
                if(exportLoadingProgressInterval){
                    clearInterval(exportLoadingProgressInterval);
                    exportLoadingProgressInterval = null;
                }
                if(exportLoadingMessageInterval){
                    clearInterval(exportLoadingMessageInterval);
                    exportLoadingMessageInterval = null;
                }
                if(exportLoadingDelayTimer){
                    clearTimeout(exportLoadingDelayTimer);
                    exportLoadingDelayTimer = null;
                }
                if(exportLoadingAutoHideTimer){
                    clearTimeout(exportLoadingAutoHideTimer);
                    exportLoadingAutoHideTimer = null;
                }
                exportLoadingProgress = 0;
                updateExportLoadingProgress(0);
                exportLoadingMessageElement.textContent = exportLoadingMessages[0];
                exportLoadingBox.classList.remove('active');
                exportLoadingOverlay.classList.remove('active');
            }

            // Flag to prevent duplicate export requests
            var isExportInProgress = false;

            function triggerExport(outputType, targetAction){
                // Prevent duplicate clicks
                if(isExportInProgress){
                    return;
                }
                isExportInProgress = true;

                resetExportLoadingProgress();
                startExportLoadingProgress();
                exportLoadingMessageElement.textContent = 'Generating report...';

                // Prepare form data for AJAX
                var formData = new FormData();
                formData.append('pregenerate', '1');
                formData.append('txt_output_type', outputType);
                formData.append('date_search', $('#date_search').val());
                formData.append('warehouse', $('#warehouse').val());
                formData.append('floor', $('#floor').val());
                formData.append('trncde_hidden', $('#trncde_hidden').val());

                // Make AJAX request to pre-generate the report
                $.ajax({
                    url: targetAction,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    timeout: 300000, // 5 minute timeout for large reports
                    success: function(response){
                        if(response && response.success && response.token){
                            // Report generated successfully - open in new tab
                            exportLoadingMessageElement.textContent = 'Opening report...';
                            updateExportLoadingProgress(95);

                            // Open the report page with token
                            var reportUrl = targetAction + '?token=' + encodeURIComponent(response.token);
                            window.open(reportUrl, '_blank');

                            // Complete loading animation
                            setTimeout(function(){
                                completeExportLoadingProgress();
                                isExportInProgress = false;
                            }, 500);
                        } else {
                            // Error from server
                            var errorMsg = (response && response.error) ? response.error : 'Failed to generate report. Please try again.';
                            alert(errorMsg);
                            resetExportLoadingProgress();
                            isExportInProgress = false;
                        }
                    },
                    error: function(xhr, status, error){
                        var errorMsg = 'Failed to generate report. ';
                        if(status === 'timeout'){
                            errorMsg += 'The request timed out. Please try with a smaller date range or fewer filters.';
                        } else if(status === 'parsererror'){
                            errorMsg += 'Server returned an invalid response.';
                        } else {
                            errorMsg += 'Please check your connection and try again.';
                        }
                        alert(errorMsg);
                        resetExportLoadingProgress();
                        isExportInProgress = false;
                    }
                });
            }

            function validateWarehouseAndFloor(){
                var warehouse_val = $("#warehouse").val();
                var floor_val = $("#floor").val();

                if(!warehouse_val || warehouse_val === ""){
                    alert("Please select a warehouse before exporting.");
                    return false;
                }
                if(!floor_val || floor_val === ""){
                    alert("Please select a floor before exporting.");
                    return false;
                }
                return true;
            }

            function exp_pdf(){
                if(!validateWarehouseAndFloor()) return;
                triggerExport("", "inventory_balance_by_war_rep.php");
            }

            function exp_txt(){
                if(!validateWarehouseAndFloor()) return;
                triggerExport("tab", "inventory_balance_by_war_rep.php");
            }

            function updateFloorDropdown(warcde){
                var $floor = $('#floor');
                $floor.empty();

                if(warcde && warehouseFloorMap[warcde] && warehouseFloorMap[warcde].length > 0){
                    var floors = warehouseFloorMap[warcde];
                    for(var i = 0; i < floors.length; i++){
                        var floor = floors[i];
                        var selected = (i === 0) ? ' selected' : '';
                        $floor.append('<option value="' + escapeHtml(floor.warehouse_floor_id) + '"' + selected + '>' + escapeHtml(floor.floor_label) + '</option>');
                    }
                } else {
                    $floor.append('<option value="">-- No floors available --</option>');
                }

                // Refresh Select2
                $floor.trigger('change.select2');
            }

            function escapeHtml(text){
                if(text === null || text === undefined) return '';
                return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            $(document).ready(function(){
                    var d = new Date();
                    var month = d.getMonth()+1;
                    var day = d.getDate();

                    var output = (month<10 ? '0' : '') + month + '/' + (day<10 ? '0' : '') + day + '/' +  d.getFullYear();
                    $('#date_search').val(output);

                    // Initialize Select2 with search functionality for warehouse
                    $('#warehouse').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Select a warehouse...',
                        allowClear: false,
                        width: '100%'
                    });

                    // Initialize Select2 for floor
                    $('#floor').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Select a floor...',
                        allowClear: false,
                        width: '100%'
                    });

                    // Update floor dropdown when warehouse changes
                    $('#warehouse').on('change', function(){
                        var warcde = $(this).val();
                        updateFloorDropdown(warcde);
                    });
            });
    </script>

<?php 
require "includes/main_footer.php";
?>
