<?php
require "includes/main_header.php";
?>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        #ui-datepicker-div{
            z-index: 2000 !important;
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
                            <table style='height:80%;background-color:white;width:40%'>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <h3>Payout Comparison</h3>
                                        <small class="text-muted">Note: this report only shows records that have payout data.</small>
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
                                                    <span id="itemSelectionCount">All items</span>
                                                    <button type="button" class="btn btn-link" id="clearItemSelection">Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">

                                        <div class="row d-flex justify-content-center align-items-top">
                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>

                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault2"  class="btn btn-primary" value="Export to XLS" onclick="exp_txt()">
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
        <input type="hidden" name="txt_output_type" id="txt_output_type" value="">
    </form>


    <script>
            function exp_pdf(){

                $("#txt_output_type").val("");
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "payout_comparison_rep.php";
                document.forms.myforms.submit();
            }

            function exp_txt(){

                $("#txt_output_type").val("tab");
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "payout_comparison_rep.php";
                document.forms.myforms.submit();
            }

            // No items selected = report covers all items.
            function updateItemSelectionCount(){
                var count = ($('#item').val() || []).length;
                $('#itemSelectionCount').text(count === 0 ? 'All items' : count + (count === 1 ? ' item selected' : ' items selected'));
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
                var $option = $('<span class="inventory-item-option"><input type="checkbox" tabindex="-1"><span></span></span>');
                $option.find('input').prop('checked', selectedValues.indexOf(String(item.id)) !== -1);
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
                $('#item').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'All items',
                    closeOnSelect: false,
                    width: '100%',
                    templateResult: formatItemOption
                });

                $('#item').on('change', updateItemSelectionCount);
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
