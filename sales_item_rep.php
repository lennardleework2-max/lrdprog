<?php
require "includes/main_header.php";
$trncde = "SAL";
?>
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
                                        <h3>Item</h3>
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
                                                <label for="">Item:</label>
                                                <select class="form-select" id="item" name="item" autocomplete="off">

                                                    <?php
                                                        $select_db_itemfile="SELECT * FROM itemfile ORDER BY itmdsc";
                                                        $stmt_itemfile	= $link->prepare($select_db_itemfile);
                                                        $stmt_itemfile->execute();

                                                            echo "<option></option>";
                                                        while($rs_itemfile = $stmt_itemfile->fetch()){

                                                            echo "<option value='".$rs_itemfile['itmcde']."'>".$rs_itemfile['itmdsc']."</option>";
                                                        }
                                                    ?>
                                                </select>
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
                                                <input type="button" name="flexRadioDefault" id="flexRadioDefault1"  class="btn btn-primary" value="Export to TXT" onclick="exp_txt()">
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
    </form>


    <script>
            function exp_pdf(){

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "item_rep_sales.php";
                //document.forms.myforms.action = "var_dump.php";
                document.forms.myforms.submit();
            }

            function exp_txt(){

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "item_txt_sales.php";
                document.forms.myforms.submit();
            }
    </script>

<?php 
require "includes/main_footer.php";
?>

