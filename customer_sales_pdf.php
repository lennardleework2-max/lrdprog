<?php
require "includes/main_header.php";
$trncde = "SAL";
?>

    <style>
        @media only screen and (max-width: 768px) {
            #docnum_table {
                width: 350px !important;
            }
        }
    </style>

    <form name="myforms" id="myforms" method="post" target="_self" style="height:calc(100vh - 85px)">
        <table class="big_table">
            <tr colspan="1">
                <td colspan="1" class="td_bl">
                    <?php require "includes/main_menu.php"; ?>
                </td>

                <td colspan="1" class="td_br" id="td_br">
                    <div class="container-fluid w-100 h-100">
                        <div class="row h-100 w-100 justify-content-center align-items-center">
                            <table style="height:auto;background-color:white;width:40%" id="docnum_table">
                                <tr>
                                    <td colspan="2" class="text-center py-4">
                                        <h3>Customer Sales Report</h3>
                                    </td>
                                </tr>

                                <tr style="height:20%">
                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style="width:80%">
                                                <label for="date_to">Date To:</label>
                                                <input type="text" class="form-control date_picker" name="date_to" id="date_to" autocomplete="off" readonly>
                                                <small class="text-muted">Report uses the latest 30-day window ending on this date. If blank, the current Philippine date is used.</small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr style="height:20%">
                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style="width:80%">
                                                <label for="sort_field_select">Sort Column:</label>
                                                <select class="form-select" id="sort_field_select" name="sort_field_select" autocomplete="off">
                                                    <option value="total_online_qty">Total Online Qty Sold Last 30 Days</option>
                                                    <option value="item">Item</option>
                                                    <option value="tiktok_qty">Tiktok Qty Sold Last 30 Days</option>
                                                    <option value="lazada_qty">Lazada Qty Sold Last 30 Days</option>
                                                    <option value="shopee_qty">Shopee Qty Sold Last 30 Days</option>
                                                    <option value="ryu_qty">RYU Qty Sold Last 30 Days</option>
                                                    <option value="inventory_ratio">30 Days Inventory Ratio</option>
                                                    <option value="current_total_stock">Current Total Stock</option>
                                                    <option value="current_inventory_valuation">Current Total Inventory Valuation</option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr style="height:20%">
                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style="width:80%">
                                                <label for="orderby_select">Sort Direction:</label>
                                                <select class="form-select" id="orderby_select" name="orderby_select" autocomplete="off">
                                                    <option value="DESC">Descending</option>
                                                    <option value="ASC">Ascending</option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" class="py-4">
                                        <div class="row d-flex justify-content-center align-items-top">
                                            <div class="col-6 d-flex justify-content-center">
                                                <input type="button" id="btn_export_pdf" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>

                                            <div class="col-6 d-flex justify-content-center">
                                                <input type="button" id="btn_export_xlsx" class="btn btn-primary" value="Export to XLSX" onclick="exp_xlsx()">
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
        function set_report_action() {
            document.forms.myforms.action = "customer_sales_rep.php";
        }

        function exp_pdf() {
            $("#txt_output_type").val("");
            $("#tab_file_type").val("xlsx");

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            set_report_action();
            document.forms.myforms.submit();
        }

        function exp_xlsx() {
            $("#txt_output_type").val("tab");
            $("#tab_file_type").val("xlsx");

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            set_report_action();
            document.forms.myforms.submit();
        }
    </script>

<?php
require "includes/main_footer.php";
?>
