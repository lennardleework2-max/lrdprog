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
                                        <h3>Docnum</h3>
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td class="d-flex justify-content-center align-items-center">
                                        <div class="m-2" style="width:80%">
                                            <label for="">Docnum From:</label>
                                            <input type="text" class="form-control" name="doc_from" id="doc_from" autocomplete="off">
                                        </div>
                                        
                                    </td>
                                </tr>

                                <tr style='height:20%'>
                                    <td class="d-flex justify-content-center align-items-top">
                                        <div class="m-2" style="width:80%">
                                            <label for="">Docnum To:</label>
                                            <input type="text" class="form-control" name="doc_to" id="doc_to" autocomplete="off">
                                        </div>
                                        
                                    </td>
                                </tr>


                                <tr style="height:50px">
                                    <td colspan="2"  class="d-flex justify-content-center align-items-top">
                                            <div class="m-2">
                                                <label for="">Unpaid Only:</label>
                                                <input class="form-check-input" type="radio" name="radio_amount" value="unpaid" id="radio_unpaid">
                                            </div>

                                            <div class="m-2">
                                                <label for="">Paid:</label>
                                                <input class="form-check-input" type="radio" name="radio_amount" value="paid" id="radio_paid">
                                            </div>            

                                            <div class="m-2">
                                                <label for="">All:</label>
                                                <input class="form-check-input" type="radio" name="radio_amount" value="all_amount" id="all_amount" checked>
                                            </div>          

                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">

                                        <div class="row d-flex justify-content-center align-items-center">
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
        <input type="hidden" name="txt_output_type" id="txt_output_type">
    </form>


    <script>
            function exp_pdf(){

                $("#txt_output_type").val("");

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "docnum_rep_sales.php";
                document.forms.myforms.submit();
            }

            function exp_txt(){

                $("#txt_output_type").val("tab");

                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "docnum_rep_sales.php";
                document.forms.myforms.submit();
            }
    </script>

<?php 
require "includes/main_footer.php";
?>

