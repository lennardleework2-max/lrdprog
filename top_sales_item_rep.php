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
    </script>

<?php 
require "includes/main_footer.php";
?>

