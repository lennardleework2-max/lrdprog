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
                            <table style='height:70%;background-color:white;width:40%' id='docnum_table'>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <h3>Platform COGS Report</h3>
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
                                                <label for="">Platform:</label>
                                                <select class="form-select" id="platform_filter" name="platform_filter" autocomplete="off">
                                                    <option value="">All</option>
                                                    <?php
                                                        $select_db_customerfile = "SELECT cuscde, cusdsc FROM customerfile ORDER BY cusdsc";
                                                        $stmt_customerfile = $link->prepare($select_db_customerfile);
                                                        $stmt_customerfile->execute();

                                                        while($rs_customerfile = $stmt_customerfile->fetch(PDO::FETCH_ASSOC)){
                                                            echo "<option value='" . htmlspecialchars($rs_customerfile['cuscde'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($rs_customerfile['cusdsc'], ENT_QUOTES, 'UTF-8') . "</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <div class="row d-flex justify-content-center align-items-center pt-3">
                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary w-100" value="Export to PDF" onclick="exp_pdf()">
                                            </div>

                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault2" class="btn btn-primary w-100" value="Export to XLS" onclick="exp_txt()">
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
        <input type="hidden" name="trncde_hidden" id="trncde_hidden" value="<?php echo htmlspecialchars($trncde); ?>">
        <input type="hidden" name="txt_output_type" id="txt_output_type">
    </form>


    <script>
            function exp_pdf(){
                $("#txt_output_type").val("");

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "platform_cogs_rep.php";
                document.forms.myforms.submit();
            }

            function exp_txt(){
                $("#txt_output_type").val("tab");

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "platform_cogs_rep.php";
                document.forms.myforms.submit();
            }
    </script>

<?php
require "includes/main_footer.php";
?>
