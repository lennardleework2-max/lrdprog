<?php

require "includes/main_header.php";
$trncde = "SAL";

?>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        @media only screen and (max-width: 768px) {
            #docnum_table{
                width:350px!important;
            }
        }
        /* Ensure datepicker appears above Select2 dropdown */
        .ui-datepicker {
            z-index: 9999 !important;
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

                                    <td colspan="2" class="text-center py-2">

                                        <h3>Date</h3>

                                    </td>

                                </tr>



                                <tr style='height:12.5%'>

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

                                <tr style='height:auto'>

                                    <td colspan="1">

                                        <div class="w-100 h-100 d-flex justify-content-end align-items-top pe-1 mt-3">

                                            <div style="width:80%">

                                                <label for="">Date From: (Paydate )</label>
                                                <input type="text" class="form-control date_picker" name="paydate_date_from" id="paydate_date_from" autocomplete="off" readonly>

                                            </div>

                                        </div>

                                    </td>

                                    <td colspan="1">

                                        <div class="w-100 h-100 d-flex justify-content-start align-items-top ps-1 mt-3">

                                            <div style="width:80%">

                                                <label for="">Date To:(Paydate)</label>
                                                <input type="text" class="form-control date_picker" name="paydate_date_to" id="paydate_date_to" autocomplete="off" readonly>

                                            </div>
                                        </div>
                                    </td>

                                </tr>

                                <tr style='height:auto'>

                                    <td colspan="1">

                                        <div class="w-100 h-100 d-flex justify-content-end align-items-top pe-1 mt-3">

                                            <div style="width:80%">

                                                <label for="">Paid Date From: (Salesman)</label>
                                                <input type="text" class="form-control date_picker" name="paydate_smn_date_from" id="paydate_smn_date_from" autocomplete="off" readonly>

                                            </div>

                                        </div>

                                    </td>

                                    <td colspan="1">

                                        <div class="w-100 h-100 d-flex justify-content-start align-items-top ps-1 mt-3">

                                            <div style="width:80%">

                                                <label for="">Paid Date To:(Salesman)</label>
                                                <input type="text" class="form-control date_picker" name="paydate_smn_date_to" id="paydate_smn_date_to" autocomplete="off" readonly>

                                            </div>
                                        </div>
                                    </td>

                                </tr>


                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Salesman :</label>

                                                <select class="form-select" id="smn_search" name="smn_search" autocomplete="off" style="width:100%">
                                                    <option value="">-- Select Salesman --</option>
                                                    <?php
                                                        $select_db_salesman="SELECT * FROM mf_salesman ORDER BY salesman_name";
                                                        $stmt_salesman	= $link->prepare($select_db_salesman);
                                                        $stmt_salesman->execute();
                                                        $rs_salesman_all = $stmt_salesman->fetchAll();

                                                        // "All" sits right below "-None" (or first when there is no "-None" salesman)
                                                        $has_none_salesman = in_array('-None', array_column($rs_salesman_all, 'salesman_name'), true);
                                                        if(!$has_none_salesman){
                                                            echo "<option value='all'>All</option>";
                                                        }

                                                        foreach($rs_salesman_all as $rs_salesman){
                                                            echo "<option value='".$rs_salesman['salesman_id']."'>".$rs_salesman['salesman_name']."</option>";
                                                            if($rs_salesman['salesman_name'] === '-None'){
                                                                echo "<option value='all'>All</option>";
                                                            }
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

                                                <label for="">Shop Name :</label>

                                                <select class="form-select" id="cus_search" name="cus_search" autocomplete="off" style="width:100%">
                                                    <option value="">-- Select Shop Name --</option>
                                                    <?php
                                                        $stmt_customer = $link->prepare("SELECT cuscde, cusdsc FROM customerfile ORDER BY cusdsc");
                                                        $stmt_customer->execute();
                                                        while($rs_customer = $stmt_customer->fetch()){
                                                            echo "<option value='".htmlspecialchars($rs_customer['cuscde'], ENT_QUOTES)."'>".htmlspecialchars($rs_customer['cusdsc'], ENT_QUOTES)."</option>";
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

                                                <label for="">Order By <b>Route: </b></label>

                                                <select class="form-select" id="orderby_route" name="orderby_route" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>
                                    

                                    </td>


                                </tr>

                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By <b>Buyer: </b></label>

                                                <select class="form-select" id="orderby_buyer" name="orderby_buyer" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>
                                    

                                    </td>


                                </tr>

                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By <b>Order Num. : </b></label>

                                                <select class="form-select" id="orderby_ordernum" name="orderby_ordernum" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>
                                    

                                    </td>


                                </tr>


                                <tr style='height:12.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">

                                            <div class="m-2" style='width:80%'>

                                                <label for="">Order By: <b>Paydate (sales)</b></label>

                                                <select class="form-select" id="orderby_paydate_sales" name="orderby_paydate_sales" autocomplete="off">
                                                    <option value="-">-</option>
                                                    <option value="ASC">ASC</option>
                                                    <option value="DESC">DESC</option>
                                                </select>

                                            </div>

                                        </div>


                                    </td>


                                </tr>

                                <tr style='height:7.5%'>

                                    <td colspan="2">

                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <input type='checkbox' name='chk_paid' id='chk_paid' checked> (Sales) Fully Paid &nbsp;&nbsp;&nbsp;
                                                <input type='checkbox' name='chk_partial_paid' id='chk_partial_paid' checked> (Sales) Partially Paid &nbsp;&nbsp;&nbsp;
                                                <input type='checkbox' name='chk_unpaid' id='chk_unpaid' checked> Unpaid
                                            </div>
                                        </div>

                                    </td>

                                </tr>

                                <tr style='height:7.5%'>

                                    <td colspan="2">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-top">
                                            <div class="m-2" style='width:80%'>
                                                <input type='checkbox' name='chk_paid_smn' id='chk_paid_smn' checked> (Salesman)Paid &nbsp &nbsp &nbsp
                                                <input type='checkbox' name='chk_unpaid_smn' id='chk_unpaid_smn' checked> Unpaid
                                            </div>
                                        </div>

                                    </td>

                                </tr>

                                <tr>

                                    <td colspan="2" class="py-2">



                                        <div class="row d-flex justify-content-center align-items-center">

                                            <div class="col-6 d-flex justify-content-center my-2">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>
                                        
                                            <div class="col-6 d-flex justify-content-center my-2">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1"  class="btn btn-primary" value="Export as xls" onclick="exp_txt()">
                                            </div>
                                        </div>

                                        <!-- keep in sync with SALES_SALESMAN_MAX_ROWS in sales_salesman_pdf.php -->
                                        <div class="text-center text-danger small">Exports are limited to 15,000 records.</div>
                                        <div id="row_limit_alert" class="alert alert-danger py-2 mx-3 mt-2 mb-0 text-center small" style="display:none"></div>

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

            // Asks the server how many records match the filters; false (with a red alert) when over the limit.
            // Synchronous on purpose: the export opens in a new tab, which browsers only allow during the click.
            function within_row_limit(){
                var ok = true;
                $("#row_limit_alert").hide();
                $.ajax({
                    url: "sales_salesman_pdf.php",
                    type: "POST",
                    data: $("#myforms").serialize() + "&count_only=1",
                    dataType: "json",
                    async: false,
                    success: function(res){
                        if(res.message){
                            $("#row_limit_alert").text(res.message).show();
                            ok = false;
                        }
                    }
                    // on a failed check, let the export run and the server decide
                });
                return ok;
            }

            function exp_pdf(){

                if(!within_row_limit()){
                    return;
                }

                $("#txt_output_type").val("");


                document.forms.myforms.target = "_blank";

                document.forms.myforms.method = "post";

                //document.forms.myforms.action = "trndate_rep_sales.php";

                document.forms.myforms.action = "sales_salesman_pdf.php";

                //document.forms.myforms.action = "var_dump.php";

                document.forms.myforms.submit();

            }


            function exp_txt(){

                if(!within_row_limit()){
                    return;
                }

                $("#txt_output_type").val("tab");



                document.forms.myforms.target = "_blank";

                document.forms.myforms.method = "post";

                document.forms.myforms.action = "sales_salesman_pdf.php";

                document.forms.myforms.submit();

            }

            $(document).ready(function(){
                // Initialize Select2 with search functionality
                $('#smn_search').select2({
                    theme: 'bootstrap-5',
                    placeholder: '-- Select Salesman --',
                    allowClear: true,
                    width: '100%'
                });

                $('#cus_search').select2({
                    theme: 'bootstrap-5',
                    placeholder: '-- Select Shop Name --',
                    allowClear: true,
                    width: '100%'
                });
            });

    </script>



<?php 

require "includes/main_footer.php";

?>



