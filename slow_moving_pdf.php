<?php

require "includes/main_header.php";

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
                                        <h3>Slow Moving Report</h3>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" class="py-4">
                                        <div class="row d-flex justify-content-center align-items-center">
                                            <div class="col-6 d-flex justify-content-center">
                                                <input type="button" id="btn_export_pdf" class="btn btn-primary" value="Export as PDF" onclick="exp_pdf()">
                                            </div>

                                            <div class="col-6 d-flex justify-content-center">
                                                <input type="button" id="btn_export_xlsx" class="btn btn-primary" value="Export as XLSX" onclick="exp_xlsx()">
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

        <input type="hidden" name="txt_output_type" id="txt_output_type">
        <input type="hidden" name="tab_file_type" id="tab_file_type" value="xlsx">
    </form>

    <script>
        function exp_pdf() {
            $("#txt_output_type").val("");
            $("#tab_file_type").val("xlsx");

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "slow_moving_pdf_rep.php";
            document.forms.myforms.submit();
        }

        function exp_xlsx() {
            $("#txt_output_type").val("tab");
            $("#tab_file_type").val("xlsx");

            document.forms.myforms.target = "_blank";
            document.forms.myforms.method = "post";
            document.forms.myforms.action = "slow_moving_pdf_rep.php";
            document.forms.myforms.submit();
        }
    </script>

<?php
require "includes/main_footer.php";
?>