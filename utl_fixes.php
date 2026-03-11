<?php
require "includes/main_header.php";
$trncde = "PUR";
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
                    <div class="container-fluid w-100 h-100 d-flex justify-content-center">
                        <div class="row h-100 w-100 justify-content-center align-items-center">
                            <table style='background-color:white;width:40%;margin:30px' id='docnum_table'>

                                <tr>
                                    <td colspan='2' class='text-center pt-3'>
                                        <h3>FIXES</h3>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" class='d-flex justify-content-center align-items-center' style='flex-direction:row'>
                                        <div class="px-2">
                                            <label for="">Date From:</label>
                                            <input type="text" class="form-control date_picker" name="date_from" id="date_from" autocomplete="off" readonly>           
                                        </div>
                          
                                        <div class="px-2">
                                            <label for="">Date To:</label>
                                            <input type="text" class="form-control date_picker" name="date_to" id="date_to" autocomplete="off" readonly>
                                      
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" style='display:flex;align-items:top'>

                                        <div class="container m-3">
                                            <div class="row d-flex justify-content-center align-items-top">
                                                <div class="col-12">
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Update Buyer from Order By" onclick="exp_t1()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Fill salesman_id of tranfile1" onclick="fix_02()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Fill routes of tranfile1" onclick="fix_03()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Fill empty salesman in sal and srt assign to -None" onclick="fix_04()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Fix route from empty to -" onclick="fix_05()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-primary" value="Fix com_pay assign a value based on smn " onclick="fix_06()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-warning" value="REVERT to Original 5-digit DOCNUM" onclick="fix_07()">
                                                    </br>
                                                    </br>
                                                    <input type="button" name="flexRadioDefault" id="flexRadioDefault1" class="btn btn-danger" value="Migrate DOCNUM: SAL/SAM to SAL-9digit format" onclick="fix_08()">
                                                </div>
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

            function exp_t1(){

                $("#txt_output_type").val("tab");
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "utl_fix_orderby.php";
                document.forms.myforms.submit();
            }

            function fix_02(){
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "utl_fix_salesman.php";
                document.forms.myforms.submit();
            }

            function fix_03(){
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "utl_fix_routes.php";
                document.forms.myforms.submit();
            }

            function fix_04(){
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "utl_fix_srt_salesman.php";
                document.forms.myforms.submit();
            }

            function fix_05(){
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "utl_fix_route_empty.php";
                document.forms.myforms.submit();
            }

            function fix_06(){
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "utl_fix_compay_smn.php";
                document.forms.myforms.submit();
            }

            function fix_07(){
                if(!confirm('WARNING: This will REVERT all DOCNUM to 5-digit suffix format.\n\nExamples:\n- SAL-000000001 -> SAL-00001\n- SAL-00000003452 -> SAL-03452\n- SAM-000117451 -> SAM-17451\n\nRule: if numeric part has more than 5 digits, keep only the last 5 digits.\n\nProceed with revert?')){
                    return;
                }
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "revert_to_original_docnum.php";
                document.forms.myforms.submit();
            }

            function fix_08(){
                if(!confirm('WARNING: This will migrate all SAL/SAM document numbers to the new 9-digit format.\n\nMake sure you have reverted to 5-digit format first!\n\nProceed with migration?')){
                    return;
                }
                document.forms.myforms.target = "_blank";
                document.forms.myforms.method = "post";
                document.forms.myforms.action = "migrate_docnum_sal.php";
                document.forms.myforms.submit();
            }

    </script>

<?php 
require "includes/main_footer.php";
?>
