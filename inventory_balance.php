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

        .item-selection-summary{
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.35rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .item-selection-summary button{
            padding: 0;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .inventory-item-option{
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .inventory-item-option input{
            pointer-events: none;
        }

        .inventory-select-actions{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice{
            max-width: 100%;
        }

        .balance-table{
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }

        .balance-table thead th{
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6c757d;
            padding: 0 0.6rem 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .balance-table thead th.text-end{
            text-align: right;
        }

        .balance-table tbody td{
            padding: 0.55rem 0.6rem;
            border-bottom: 1px solid #eef0f2;
            vertical-align: middle;
        }

        .balance-table tbody tr:last-child td{
            border-bottom: none;
        }

        .balance-table tbody tr:nth-child(odd){
            background: #fafbfc;
        }

        .balance-table .item-name{
            font-weight: 600;
            color: #212529;
            display: block;
        }

        .balance-table .item-code{
            font-family: monospace;
            font-size: 0.72rem;
            color: #6c757d;
            background: #f1f3f5;
            padding: 0.05rem 0.35rem;
            border-radius: 0.25rem;
            display: inline-block;
            margin-top: 0.15rem;
        }

        .balance-table .balance-cell{
            text-align: right;
            white-space: nowrap;
        }

        .balance-table .balance-value{
            font-weight: 600;
        }

        .balance-table .balance-unit{
            font-size: 0.7rem;
            color: #6c757d;
            margin-left: 0.2rem;
        }

        .balance-table .pos{ color: #198754; }
        .balance-table .neg{ color: #dc3545; }
        .balance-table .zero{ color: #6c757d; }
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
                                        <h3>Inventory Balance</h3>
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
                                                <label for="item">Items:</label>
                                                <select class="form-select" id="item" name="item[]" multiple autocomplete="off" style="width:100%">
                                                    <?php
                                                        $select_db_itemfile="SELECT * FROM itemfile ORDER BY itmdsc";
                                                        $stmt_itemfile	= $link->prepare($select_db_itemfile);
                                                        $stmt_itemfile->execute();

                                                        while($rs_itemfile = $stmt_itemfile->fetch()){
                                                            echo "<option value=\"".htmlspecialchars($rs_itemfile['itmcde'], ENT_QUOTES, 'UTF-8')."\">".htmlspecialchars($rs_itemfile['itmdsc'], ENT_QUOTES, 'UTF-8')."</option>";
                                                        }
                                                    ?>
                                                </select>
                                                <div class="item-selection-summary">
                                                    <span id="itemSelectionCount">0 items selected</span>
                                                    <button type="button" class="btn btn-link" id="clearItemSelection">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                    <div class="w-100 d-flex justify-content-center" id="item_total">
                                    </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">

                                        <div class="row d-flex justify-content-center align-items-top btns_item">
                                            <div class="col-4 ms-3">
                                                <input type="button" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>
                                            <div class="col-4">
                                                <input type="button" class="btn btn-primary" value="Export to XLS" onclick="exp_txt()">
                                            </div>
                                            <div class="col-4">
                                                <input type="button" class="btn btn-success" value="Display Balance" onclick="display_balance()">
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
                $.each($('#item').val() || [], function(index, itemCode){
                    formData.append('item[]', itemCode);
                });
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

            $("#item").change(function(){
                // Clear displayed balance when item changes
                $("#item_total").html("");
                updateItemSelectionCount();
            });

            $("#date_search").change(function(){
                $("#item_total").html("");
            });

            function validateItemSelection(){
                var item_filter = $("#item").val();
                if(!item_filter || item_filter.length === 0){
                    alert("Please select at least one item.");
                    return false;
                }
                return true;
            }

            function exp_pdf(){
                if(!validateItemSelection()) return;
                triggerExport("", "inventory_balance_rep.php");
            }

            function exp_txt(){
                if(!validateItemSelection()) return;
                triggerExport("tab", "inventory_balance_rep.php");
            }

            function display_balance(){
                var date_filter = $("#date_search").val();
                var item_filter = $("#item").val();

                if(!item_filter || item_filter.length === 0){
                    $("#item_total").html("");
                    alert("Please select at least one item.");
                    return;
                }

                jQuery.ajax({
                    data: {
                        date_search: date_filter,
                        item: item_filter
                    },
                    dataType: "json",
                    type: "post",
                    url: "inventory_balance_ajax.php",
                    success: function(xdata2){
                        var items = xdata2["items"] || [];
                        if(items.length === 0){
                            $("#item_total").html("");
                            return;
                        }

                        var escapeHtml = function(text){
                            return $('<div>').text(text).html();
                        };

                        var rows = $.map(items, function(itemRow){
                            var balance = itemRow.balance;
                            if(balance == null || balance === ''){
                                balance = 0;
                            }
                            balance = parseFloat(balance);
                            var signClass = balance > 0 ? 'pos' : (balance < 0 ? 'neg' : 'zero');
                            var formatted = balance.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 4});

                            return "<tr>"
                                + "<td><span class='item-name'>" + escapeHtml(itemRow.itmdsc) + "</span>"
                                + "<span class='item-code'>" + escapeHtml(itemRow.itmcde) + "</span></td>"
                                + "<td class='balance-cell'><span class='balance-value " + signClass + "'>" + formatted + "</span><span class='balance-unit'>PCS</span></td>"
                                + "</tr>";
                        });

                        var table = "<table class='balance-table'>"
                            + "<thead><tr><th>Item</th><th class='text-end'>Balance</th></tr></thead>"
                            + "<tbody>" + rows.join("") + "</tbody>"
                            + "</table>";

                        $("#item_total").html(table);
                    }
                });
            }

            function updateItemSelectionCount(){
                var count = ($('#item').val() || []).length;
                $('#itemSelectionCount').text(count + (count === 1 ? ' item selected' : ' items selected'));
            }

            function syncItemOptionCheckboxes(){
                $('.select2-results__option[aria-selected]').each(function(){
                    $(this).find('.inventory-item-option input').prop('checked', $(this).attr('aria-selected') === 'true');
                });
            }

            function formatItemOption(item){
                if(!item.id){
                    return item.text;
                }

                var selectedValues = $('#item').val() || [];
                var isSelected = selectedValues.indexOf(String(item.id)) !== -1;
                var $option = $('<span class="inventory-item-option"><input type="checkbox" tabindex="-1"><span></span></span>');
                $option.find('input').prop('checked', isSelected);
                $option.find('span').text(item.text);
                return $option;
            }

            function addSelectActions(){
                var $results = $('.select2-results');
                if($results.find('.inventory-select-actions').length){
                    return;
                }

                var $actions = $('<div class="inventory-select-actions"><button type="button" class="btn btn-sm btn-outline-primary select-visible-items">Select visible</button><button type="button" class="btn btn-sm btn-link clear-visible-selection">Clear</button></div>');
                $actions.on('mousedown', function(event){
                    event.preventDefault();
                });
                $results.prepend($actions);
            }

            function selectVisibleItems(){
                var searchTerm = ($('.select2-search__field').val() || '').toLowerCase();
                var selectedValues = $('#item').val() || [];

                $('#item option').each(function(){
                    if($(this).text().toLowerCase().indexOf(searchTerm) !== -1){
                        var value = String($(this).val());
                        if(selectedValues.indexOf(value) === -1){
                            selectedValues.push(value);
                        }
                    }
                });

                $('#item').val(selectedValues).trigger('change');
                $('#item').select2('close');
            }

            $(document).ready(function(){

                    var d = new Date();
                    var month = d.getMonth()+1;
                    var day = d.getDate();

                    var output = (month<10 ? '0' : '') + month + '/' + (day<10 ? '0' : '') + day + '/' +  d.getFullYear();
                    $('#date_search').val(output);

                    // Initialize Select2 with search functionality
                    $('#item').select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Select items...',
                        closeOnSelect: false,
                        width: '100%',
                        templateResult: formatItemOption
                    });

                    $('#item').on('select2:open', addSelectActions);
                    $('#item').on('select2:select select2:unselect', function(){
                        setTimeout(syncItemOptionCheckboxes, 0);
                    });
                    $(document).on('click', '.select-visible-items', selectVisibleItems);
                    $(document).on('click', '.clear-visible-selection', function(){
                        $('#item').val(null).trigger('change');
                        $('#item').select2('close');
                    });
                    $('#clearItemSelection').on('click', function(){
                        $('#item').val(null).trigger('change');
                    });
                    updateItemSelectionCount();
            });
    </script>

<?php 
require "includes/main_footer.php";
?>
