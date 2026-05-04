<?php
require "includes/main_header.php";
$trncde = "SAL";
?>

    <style>
        @media only screen and (max-width: 768px) {
            #docnum_table{
                width:350px!important;
            }
        }
    </style>

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
                            <table style='height:80%;background-color:white;width:40%' id='docnum_table'>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <h3>Top Sales Report</h3>
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td colspan="1">
                                        <div class="w-100 h-100 d-flex justify-content-end align-items-top pe-1">
                                            <div style="width:80%">
                                                <label for="">Date From:</label>
                                                <input type="text" class="form-control date_picker" name="date_from" id="date_from" autocomplete="off" readonly>
                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="1">
                                        <div class="w-100 h-100 d-flex justify-content-start align-items-top ps-1">
                                            <div style="width:80%">
                                                <label for="">Date To:</label>
                                                <input type="text" class="form-control date_picker" name="date_to" id="date_to" autocomplete="off" readonly>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <select class="form-select" id="sel_rep_type" name="sel_rep_type" autocomplete="off">
                                                    <option value='item'>Top Sales by Item</option>
                                                    <option value='salesman'>Top Sales by Salesman</option>
                                                    <option value='platform'>Top Sales by Platform</option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="sort_mode_wrap" style='height:20%'>
                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <label class="mb-1 d-block">Item Sorting:</label>

                                                <div class="form-check mb-1">
                                                    <input class="form-check-input" type="radio" name="sort_mode" id="sort_mode_current_stock_qty" value="current_stock_qty" checked>
                                                    <label class="form-check-label" for="sort_mode_current_stock_qty">Current Stock/Qty</label>
                                                </div>
                                                <select class="form-select" id="current_stock_qty_sort" name="current_stock_qty_sort" autocomplete="off">
                                                    <option value="asc">Ascending</option>
                                                    <option value="desc">Descending</option>
                                                </select>

                                                <div class="form-check mt-3 mb-1">
                                                    <input class="form-check-input" type="radio" name="sort_mode" id="sort_mode_sales_30_qty" value="sales_30_qty">
                                                    <label class="form-check-label" for="sort_mode_sales_30_qty">Top Sales (Past 30 Days)</label>
                                                </div>
                                                <select class="form-select" id="sales_30_qty_sort" name="sales_30_qty_sort" autocomplete="off">
                                                    <option value="asc">Ascending</option>
                                                    <option value="desc">Descending</option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">

                                        <div class="row d-flex justify-content-center align-items-top">
                                            <div class="col-6 d-flex justify-content-center">
                                                <input type="button" name="flexRadioDefault" id="btn_export_pdf" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>

                                            <div class="col-6 d-flex justify-content-center">
                                                <input type="button" name="flexRadioDefault" id="btn_export_xlsx" class="btn btn-primary" value="Export to XLSX" onclick="exp_xlsx()">
                                            </div>
                                        </div>

                                        <!-- TEMPORARY DEBUG: Preview with SQL Debug button - Remove after fixing rolling sales -->
                                        <div class="row d-flex justify-content-center align-items-top mt-3" id="debug_btn_wrap">
                                            <div class="col-12 d-flex justify-content-center">
                                                <input type="button" id="btn_sql_debug" class="btn btn-warning" value="Preview with SQL Debug (Top 50 Items)" onclick="preview_sql_debug()">
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
        <input type="hidden" name="tab_file_type" id="tab_file_type" value="xlsx">
    </form>

    <!-- TEMPORARY DEBUG: SQL Debug Preview Container - Remove after fixing rolling sales -->
    <div id="sql_debug_container" class="container-fluid mt-3" style="display:none;">
        <div class="card">
            <div class="card-header bg-warning">
                <strong>SQL Debug Preview</strong>
                <button type="button" class="btn btn-sm btn-secondary float-end" onclick="$('#sql_debug_container').hide();">Close</button>
            </div>
            <div class="card-body">
                <div id="debug_date_info" class="alert alert-info mb-3"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="sql_debug_table">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:120px;">Item Code</th>
                                <th>Item Description</th>
                                <th style="width:100px;">Sales Qty 30D</th>
                                <th style="width:100px;">Sales Qty 60D</th>
                                <th style="width:100px;">Sales Qty 90D</th>
                                <th style="width:100px;">SQL</th>
                            </tr>
                        </thead>
                        <tbody id="sql_debug_tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <script>
            function set_report_action(){
                const choice = $('#sel_rep_type').val();

                if (choice === 'item'){
                    document.forms.myforms.action = 'top_sales_item_pdf.php';
                }
                else if (choice === 'salesman'){
                    document.forms.myforms.action = 'top_sales_salesman_pdf.php';
                }
                else if (choice === 'platform'){
                    document.forms.myforms.action = 'top_sales_platform_pdf.php';
                }
            }

            function toggle_sort_mode_inputs(){
                const is_item = $('#sel_rep_type').val() === 'item';
                const mode = $('input[name="sort_mode"]:checked').val();

                $('#current_stock_qty_sort').prop('disabled', !is_item || mode !== 'current_stock_qty');
                $('#sales_30_qty_sort').prop('disabled', !is_item || mode !== 'sales_30_qty');
            }

            function toggle_item_sort_filter(){
                const is_item = $('#sel_rep_type').val() === 'item';
                $('#sort_mode_wrap').toggle(is_item);
                $('input[name="sort_mode"]').prop('disabled', !is_item);
                toggle_sort_mode_inputs();
                // Show debug button only for item report
                $('#debug_btn_wrap').toggle(is_item);
            }

            function exp_pdf(){
                $('#txt_output_type').val('');
                $('#tab_file_type').val('xlsx');

                document.forms.myforms.target = '_blank';
                document.forms.myforms.method = 'post';
                set_report_action();
                document.forms.myforms.submit();
            }

            function exp_xlsx(){
                $('#txt_output_type').val('tab');
                $('#tab_file_type').val('xlsx');

                document.forms.myforms.target = '_blank';
                document.forms.myforms.method = 'post';
                set_report_action();
                document.forms.myforms.submit();
            }

            $(document).ready(function(){
                toggle_item_sort_filter();
                $('#sel_rep_type').on('change', toggle_item_sort_filter);
                $('input[name="sort_mode"]').on('change', toggle_sort_mode_inputs);
            });

            // TEMPORARY DEBUG: Preview with SQL Debug function - Remove after fixing rolling sales
            function preview_sql_debug(){
                if($('#sel_rep_type').val() !== 'item'){
                    alert('SQL Debug Preview is only available for "Top Sales by Item" report type.');
                    return;
                }

                $('#btn_sql_debug').prop('disabled', true).val('Loading...');

                $.ajax({
                    url: 'top_sales_item_rep_debug_ajax.php',
                    type: 'POST',
                    data: {
                        date_from: $('#date_from').val(),
                        date_to: $('#date_to').val()
                    },
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            display_sql_debug(response);
                        } else {
                            alert('Error loading debug data');
                        }
                    },
                    error: function(xhr, status, error){
                        alert('AJAX Error: ' + error);
                    },
                    complete: function(){
                        $('#btn_sql_debug').prop('disabled', false).val('Preview with SQL Debug (Top 50 Items)');
                    }
                });
            }

            function display_sql_debug(data){
                // Show date info
                var dateInfo = '<strong>Rolling Sales Date Ranges:</strong><br>';
                dateInfo += 'Today: ' + data.today_date + '<br>';
                dateInfo += '30D: ' + data.past_30_start + ' to ' + data.today_date + '<br>';
                dateInfo += '60D: ' + data.past_60_start + ' to ' + data.today_date + '<br>';
                dateInfo += '90D: ' + data.past_90_start + ' to ' + data.today_date;
                $('#debug_date_info').html(dateInfo);

                // Build table rows
                var tbody = '';
                $.each(data.items, function(index, item){
                    var rowId = 'sql_row_' + index;
                    var sqlContent = escapeHtml(build_sql_display(item));

                    tbody += '<tr>';
                    tbody += '<td>' + (index + 1) + '</td>';
                    tbody += '<td>' + escapeHtml(item.itmcde) + '</td>';
                    tbody += '<td>' + escapeHtml(item.itmdsc || '') + '</td>';
                    tbody += '<td class="text-end">' + formatNumber(item.sales_30d) + '</td>';
                    tbody += '<td class="text-end">' + formatNumber(item.sales_60d) + '</td>';
                    tbody += '<td class="text-end">' + formatNumber(item.sales_90d) + '</td>';
                    tbody += '<td><button class="btn btn-sm btn-info" onclick="toggle_sql_row(\'' + rowId + '\')">Show SQL</button></td>';
                    tbody += '</tr>';

                    // SQL detail row (hidden by default)
                    tbody += '<tr id="' + rowId + '" style="display:none;">';
                    tbody += '<td colspan="7" class="bg-light">';
                    tbody += '<pre style="white-space:pre-wrap; font-size:12px; margin:0; max-height:400px; overflow:auto;">' + sqlContent + '</pre>';
                    tbody += '</td>';
                    tbody += '</tr>';
                });

                $('#sql_debug_tbody').html(tbody);
                $('#sql_debug_container').show();

                // Scroll to debug container
                $('html, body').animate({
                    scrollTop: $('#sql_debug_container').offset().top - 20
                }, 500);
            }

            function build_sql_display(item){
                var sql = '--SQL--\n\n';

                sql += 'Sales Qty 30D SQL:\n';
                sql += item.sql_30d + '\n\n';

                sql += 'Sales Qty 60D SQL:\n';
                sql += item.sql_60d + '\n\n';

                sql += 'Sales Qty 90D SQL:\n';
                sql += item.sql_90d + '\n\n';

                sql += '--SQL--';

                return sql;
            }

            function toggle_sql_row(rowId){
                var row = $('#' + rowId);
                if(row.is(':visible')){
                    row.hide();
                } else {
                    row.show();
                }
            }

            function escapeHtml(text){
                if(!text) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }

            function formatNumber(num){
                if(!num) return '0.00';
                return parseFloat(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
    </script>

<?php 
require "includes/main_footer.php";
?>
