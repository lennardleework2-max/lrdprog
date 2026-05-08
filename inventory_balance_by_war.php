<?php
require "includes/main_header.php";
// $trncde = "SAL";
?>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
                                        <h3>Inventory Balance </br> By Warehouse</h3>
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
                                                <label for="">Warehouse:</label>
                                                <select class="form-select" id="warehouse" name="warehouse" autocomplete="off" style="width:100%">
                                                    <option value="">-- All Warehouses --</option>
                                                    <?php
                                                        $select_db_warehouse="SELECT * FROM warehouse ORDER BY warcde";
                                                        $stmt_warehouse = $link->prepare($select_db_warehouse);
                                                        $stmt_warehouse->execute();

                                                        while($rs_warehouse = $stmt_warehouse->fetch()){
                                                            echo "<option value=\"".$rs_warehouse['warcde']."\">".$rs_warehouse['warehouse_name']."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">

                                        <div class="row d-flex justify-content-center align-items-top btns_item">
                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Export to PDF" onclick="exp_pdf()">
                                            </div>
                                            
                                            <div class="col-4">
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1"  class="btn btn-primary" value="Export to XLS" onclick="exp_txt()">
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
            function exp_pdf(){
                $("#txt_output_type").val("");
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "inventory_balance_by_war_rep.php";
                document.forms.myforms.submit();
            }

            function exp_txt(){
                $("#txt_output_type").val("tab");
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "inventory_balance_by_war_rep.php";
                document.forms.myforms.submit();
            }

            $(document).ready(function(){
                    var d = new Date();
                    var month = d.getMonth()+1;
                    var day = d.getDate();

                    var output = (month<10 ? '0' : '') + month + '/' + (day<10 ? '0' : '') + day + '/' +  d.getFullYear();
                    $('#date_search').val(output);

                    // Initialize Select2 with search functionality
                    $('#warehouse').select2({
                        theme: 'bootstrap-5',
                        placeholder: '-- All Warehouses --',
                        allowClear: true,
                        width: '100%'
                    });
            });
    </script>

<?php 
require "includes/main_footer.php";
?>

