<?php
require "includes/main_header.php";
?>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap">

    <style>

        .data_table{
            border-collapse:collapse;
            width:100%;
        }

        .roas-report{
            --roas-bg:#faf6f1;
            --roas-surface:#ffffff;
            --roas-surface-2:#f4efe8;
            --roas-border:#e6dccf;
            --roas-ink:#2a221c;
            --roas-ink-muted:#7a6c5d;
            --roas-accent:#9a3b34;
            --roas-accent-ink:#ffffff;
            --roas-accent-soft:#f1ddd7;
            --roas-accent-soft-strong:#e7c9c1;
            --roas-good:#2f6b43;
            --roas-good-soft:#e3efe4;
            --roas-warn:#93611a;
            --roas-warn-soft:#f5ead2;
            --roas-locked-bg:#f4f1ea;

            background:var(--roas-bg);
            color:var(--roas-ink);
            font-family:'Inter',system-ui,sans-serif;
            padding:22px;
            border-radius:14px;
        }

        .roas-report h3{
            font-family:'Fraunces',Georgia,serif;
            font-weight:600;
            color:var(--roas-accent);
            margin-bottom:2px;
        }

        .roas-report .roas-subtitle{
            color:var(--roas-ink-muted);
            font-size:.85rem;
            margin-bottom:18px;
        }

        .roas-report .roas-toolbar{
            background:var(--roas-surface);
            border:1px solid var(--roas-border);
            border-radius:12px;
            padding:16px 18px;
            box-shadow:0 1px 2px rgba(42,34,28,.06), 0 8px 20px -14px rgba(42,34,28,.25);
        }

        .roas-report .form-label{
            font-size:.72rem;
            font-weight:600;
            letter-spacing:.03em;
            text-transform:uppercase;
            color:var(--roas-ink-muted);
        }

        .roas-report .form-control{
            font-family:'Inter',sans-serif;
            border-color:var(--roas-border);
            color:var(--roas-ink);
        }

        .roas-report .form-control:focus{
            border-color:var(--roas-accent);
            box-shadow:0 0 0 .2rem rgba(154,59,52,.15);
        }

        .roas-report .btn-primary{
            background-color:var(--roas-accent);
            border-color:var(--roas-accent);
            color:var(--roas-accent-ink);
            font-weight:600;
        }

        .roas-report .btn-primary:hover,
        .roas-report .btn-primary:focus{
            background-color:#832f29;
            border-color:#832f29;
            color:var(--roas-accent-ink);
        }

        .roas-report .btn-outline-secondary{
            border-color:var(--roas-border);
            color:var(--roas-ink);
            font-weight:600;
            background:var(--roas-surface);
        }

        .roas-report .btn-outline-secondary:hover{
            background:var(--roas-surface-2);
            color:var(--roas-ink);
            border-color:var(--roas-border);
        }

        .roas-report .table-responsive{
            margin-top:20px;
            border-radius:12px;
            border:1px solid var(--roas-border);
            overflow:hidden;
        }

        .roas-report table{
            margin-bottom:0;
            background:var(--roas-surface);
        }

        .roas-report thead th{
            background:var(--roas-accent);
            color:var(--roas-accent-ink);
            font-size:.72rem;
            text-transform:uppercase;
            letter-spacing:.05em;
            font-weight:700;
            border-color:var(--roas-accent);
            vertical-align:middle;
        }

        .roas-report tbody tr:nth-child(even){
            background-color:var(--roas-accent-soft);
        }

        .roas-report tbody tr:nth-child(odd){
            background-color:var(--roas-surface);
        }

        .roas-report tbody td{
            border-color:var(--roas-border);
            vertical-align:middle;
        }

        .roas-report tfoot td{
            background:var(--roas-accent-soft-strong) !important;
            border-top:2px solid var(--roas-accent);
            font-weight:700;
        }

        .roas-report .roas-cell-input{
            border:1px solid transparent;
            background:transparent;
            color:var(--roas-ink);
        }

        .roas-report .roas-cell-input:hover{
            border-color:var(--roas-border);
        }

        .roas-report .roas-cell-input:focus{
            border-color:var(--roas-accent);
            background:var(--roas-surface);
            box-shadow:0 0 0 .15rem rgba(154,59,52,.15);
        }

        .roas-report .roas-locked{
            background:var(--roas-locked-bg) !important;
            color:var(--roas-ink-muted);
            border:1px solid transparent;
        }

        .roas-report .roas-pill{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:3.2em;
            padding:3px 10px;
            border-radius:999px;
            font-weight:700;
            font-size:.82rem;
        }

        .roas-report .roas-pill.roas-good{
            background:var(--roas-good-soft);
            color:var(--roas-good);
        }

        .roas-report .roas-pill.roas-warn{
            background:var(--roas-warn-soft);
            color:var(--roas-warn);
        }

        .roas-report .roas-pill.roas-flat{
            background:var(--roas-surface-2);
            color:var(--roas-ink-muted);
        }

    </style>

    <form name='myforms' id="myforms" method="post" target="_self">
        <table class='big_table'>
            <tr colspan=1>

                <td colspan=1 class='td_bl'>
                    <?php
                        require 'includes/main_menu.php';
                    ?>
                </td>

                <td colspan=1 class="td_br" id="td_br">

                    <div class="container-fluid pt-3 main_br_div">

                        <div class="roas-report">

                            <h3>Sales ROAS Report</h3>
                            <div class="roas-subtitle">Pick a date range, load the shops, then fill in Ads Credit, Valid Order Sales and Ads Spend before exporting.</div>

                            <div class="roas-toolbar row g-3 align-items-end">
                                <div class="col-auto">
                                    <label for="date_from" class="form-label mb-0">Date From</label>
                                    <input type="text" class="form-control date_picker" name="date_from" id="date_from" autocomplete="off" readonly>
                                </div>

                                <div class="col-auto">
                                    <label for="date_to" class="form-label mb-0">Date To</label>
                                    <input type="text" class="form-control date_picker" name="date_to" id="date_to" autocomplete="off" readonly>
                                </div>

                                <div class="col-auto">
                                    <button type="button" class="btn btn-primary" onclick="load_roas_report()">Load Report</button>
                                </div>

                                <div class="col-auto ms-auto">
                                    <button type="button" class="btn btn-outline-secondary" onclick="exp_txt()">Export to XLS <i class="fas fa-file-excel"></i></button>
                                    <button type="button" class="btn btn-primary" onclick="exp_pdf()">Export to PDF <i class="fas fa-file-pdf"></i></button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="data_table table table-borderless table-sm align-middle" id="roas_table">
                                    <thead>
                                        <tr>
                                            <th>Shop</th>
                                            <th class="text-end">Online Sales</th>
                                            <th class="text-end">Ads Credit</th>
                                            <th class="text-end">Valid Order Sales</th>
                                            <th class="text-end">Ads Spend</th>
                                            <th class="text-end">ROAS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="roas_table_body">
                                        <tr id="roas_empty_row">
                                            <td colspan="6" class="text-center text-muted py-4">Select a date range and click "Load Report" to begin.</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>Total</td>
                                            <td class="text-end" id="total_online">0.00</td>
                                            <td class="text-end" id="total_credit">0.00</td>
                                            <td class="text-end" id="total_valid">0.00</td>
                                            <td class="text-end" id="total_spend">0.00</td>
                                            <td class="text-end" id="total_roas">-</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div>

                    </div>
                </td>

            </tr>
        </table>

        <input type="hidden" name="txt_output_type" id="txt_output_type" value="">
    </form>

<script>

    // number of shop rows currently loaded - also guards Export from firing on an empty table
    var roas_row_count = 0;

    function fmt_money(xnum){
        xnum = isNaN(xnum) ? 0 : xnum;
        return xnum.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    // ROAS = Valid Order Sales / Ads Spend - blank/zero spend has no meaningful ratio
    function fmt_roas(xvalid, xspend){
        if(!xspend || xspend <= 0){
            return '-';
        }
        return (xvalid/xspend).toFixed(1);
    }

    function roas_pill_class(xvalid, xspend){
        if(!xspend || xspend <= 0){
            return 'roas-flat';
        }
        var xratio = xvalid/xspend;
        if(xratio >= 5){
            return 'roas-good';
        }
        if(xratio > 0){
            return 'roas-warn';
        }
        return 'roas-flat';
    }

    function escape_roas_html(xstr){
        return String(xstr == null ? '' : xstr)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function recompute_roas_row(idx){
        var xvalid = parseFloat($('#valid_'+idx).val()) || 0;
        var xspend = parseFloat($('#spend_'+idx).val()) || 0;
        $('#roas_'+idx)
            .text(fmt_roas(xvalid, xspend))
            .attr('class', 'roas-pill ' + roas_pill_class(xvalid, xspend));
        recompute_roas_totals();
    }

    function recompute_roas_totals(){
        var total_online = 0, total_credit = 0, total_valid = 0, total_spend = 0;

        for(var i = 0; i < roas_row_count; i++){
            total_online += parseFloat($('#online_'+i).val()) || 0;
            total_credit += parseFloat($('#credit_'+i).val()) || 0;
            total_valid += parseFloat($('#valid_'+i).val()) || 0;
            total_spend += parseFloat($('#spend_'+i).val()) || 0;
        }

        $('#total_online').text(fmt_money(total_online));
        $('#total_credit').text(fmt_money(total_credit));
        $('#total_valid').text(fmt_money(total_valid));
        $('#total_spend').text(fmt_money(total_spend));
        $('#total_roas').text(fmt_roas(total_valid, total_spend));
    }

    function load_roas_report(){

        var date_from = $('#date_from').val();
        var date_to = $('#date_to').val();

        jQuery.ajax({
            data: {date_from: date_from, date_to: date_to},
            type: 'post',
            dataType: 'json',
            url: 'sales_roas_report_ajax.php',
            success: function(xret){

                if(xret['status'] == 0){
                    alert(xret['errorMsg']);
                    return;
                }

                var shops = xret['shops'] || [];
                var xhtml = '';
                roas_row_count = shops.length;

                if(shops.length === 0){
                    xhtml = '<tr><td colspan="6" class="text-center text-muted py-4">No platform shops found.</td></tr>';
                }

                for(var i = 0; i < shops.length; i++){
                    var shop = shops[i];
                    var shop_title = escape_roas_html(shop['shop_title']);
                    var online_sales = parseFloat(shop['online_sales']) || 0;

                    xhtml += '<tr>' +
                        '<td>'+shop_title+'<input type="hidden" name="shop_title[]" value="'+shop_title+'"></td>' +
                        '<td class="text-end"><input type="text" class="form-control form-control-sm text-end roas-cell-input roas-locked" id="online_'+i+'" name="online_sales[]" value="'+online_sales.toFixed(2)+'" readonly></td>' +
                        '<td><input type="number" step="0.01" class="form-control form-control-sm text-end roas-cell-input" id="credit_'+i+'" name="ads_credit[]" placeholder="0.00" oninput="recompute_roas_totals()"></td>' +
                        '<td><input type="number" step="0.01" class="form-control form-control-sm text-end roas-cell-input" id="valid_'+i+'" name="valid_order_sales[]" placeholder="0.00" oninput="recompute_roas_row('+i+')"></td>' +
                        '<td><input type="number" step="0.01" class="form-control form-control-sm text-end roas-cell-input" id="spend_'+i+'" name="ads_spend[]" placeholder="0.00" oninput="recompute_roas_row('+i+')"></td>' +
                        '<td class="text-end"><span class="roas-pill roas-flat" id="roas_'+i+'">-</span></td>' +
                        '</tr>';
                }

                $('#roas_table_body').html(xhtml);
                recompute_roas_totals();
            },
            error: function(){
                alert('Unable to load the report. Please try again.');
            }
        });
    }

    function exp_pdf(){
        if(roas_row_count === 0){
            alert('Please load a report first.');
            return;
        }
        $('#txt_output_type').val('');
        document.forms.myforms.target = '_blank';
        document.forms.myforms.method = 'post';
        document.forms.myforms.action = 'sales_roas_report_rep.php';
        document.forms.myforms.submit();
    }

    function exp_txt(){
        if(roas_row_count === 0){
            alert('Please load a report first.');
            return;
        }
        $('#txt_output_type').val('tab');
        document.forms.myforms.target = '_blank';
        document.forms.myforms.method = 'post';
        document.forms.myforms.action = 'sales_roas_report_rep.php';
        document.forms.myforms.submit();
    }

</script>

<?php
require "includes/main_footer.php";
?>
