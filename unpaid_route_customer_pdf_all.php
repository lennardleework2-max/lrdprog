<?php

require "includes/main_header.php";
$trncde = "SAL";

?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
                            <table style='height:auto;background-color:white;width:40%' id='docnum_table'>
                                <tr>

                                    <td colspan="2" class="text-center py-4">
                                        <h3>Unpaid Sales Route</h3>
                                    </td>

                                </tr>

                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="route_id">Route :</label>

                                                <select class="form-select" id="route_id" name="route_id" autocomplete="off" style="width:100%">
                                                    <option value="all" selected>All Routes</option>
                                                    <?php
                                                        $select_db_routes = "SELECT route_id, route_desc FROM mf_routes WHERE (route_desc IS NULL OR route_desc <> '-') ORDER BY route_desc ASC, route_id ASC";
                                                        $stmt_routes = $link->prepare($select_db_routes);
                                                        $stmt_routes->execute();

                                                        while($rs_routes = $stmt_routes->fetch()){
                                                            $route_id_opt = isset($rs_routes['route_id']) ? htmlspecialchars((string)$rs_routes['route_id'], ENT_QUOTES, 'UTF-8') : '';
                                                            $route_desc_opt = isset($rs_routes['route_desc']) ? htmlspecialchars((string)$rs_routes['route_desc'], ENT_QUOTES, 'UTF-8') : '';
                                                            $route_display = $route_desc_opt !== '' ? $route_desc_opt : $route_id_opt;
                                                            echo "<option value=\"".$route_id_opt."\">".$route_display."</option>";
                                                        }
                                                    ?>
                                                </select>

                                            </div>

                                        </div>

                                    </td>
                                </tr>

                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="buyer_id">Buyer :</label>

                                                <select class="form-select" id="buyer_id" name="buyer_id[]" autocomplete="off" style="width:100%" multiple>
                                                </select>
                                                <small class="text-muted">Select one or more buyers, or leave empty for all</small>

                                            </div>

                                        </div>

                                    </td>
                                </tr>

                                <tr>

                                    <td colspan="2" class="py-4">

                                        <div class="row d-flex justify-content-center align-items-center">

                                            <div class="col-6 d-flex justify-content-center">

                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">

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

        <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php echo htmlspecialchars($trncde, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="txt_output_type" id="txt_output_type">
        <input type="hidden" name="export_crud_hidden" id="export_crud_hidden" value="<?php echo (int)$export_crud; ?>">

    </form>





    <script>

            function exp_pdf(){

                // if($("#export_crud_hidden").val() !== "1"){
                //     alert("You do not have export permission.");
                //     return;
                // }

                $("#txt_output_type").val("");

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "unpaid_route_customer_pdf_all_rep.php";
                document.forms.myforms.submit();

            }

            function loadBuyers(route_id) {
                var xdata = "event_action=get_buyers&route_id=" + encodeURIComponent(route_id);

                jQuery.ajax({
                    data: xdata,
                    dataType: "json",
                    type: "post",
                    url: "unpaid_route_customer_pdf_all_ajax.php",
                    success: function(response) {
                        // Clear existing options
                        $('#buyer_id').empty();

                        if (response.status === 1 && response.data && response.data.length > 0) {
                            // Add options from AJAX response
                            $.each(response.data, function(index, buyer) {
                                var option = new Option(buyer.text, buyer.id, false, false);
                                $('#buyer_id').append(option);
                            });
                        }

                        // Trigger change to update Select2 display
                        $('#buyer_id').trigger('change');
                    },
                    error: function() {
                        console.log('Error loading buyers');
                    }
                });
            }

            $(document).ready(function(){
                // Initialize route dropdown with Select2
                $('#route_id').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select Route',
                    allowClear: false,
                    width: '100%'
                });

                // Initialize buyer dropdown with Select2 multi-select
                $('#buyer_id').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'All Buyers (leave empty for all)',
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });

                // Load buyers when route changes
                $('#route_id').on('change', function() {
                    var route_id = $(this).val() || 'all';
                    loadBuyers(route_id);
                });

                // Load initial buyers for "All Routes"
                loadBuyers('all');
            });

    </script>



<?php

require "includes/main_footer.php";

?>
