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
                                        <h3>Inventory Balance</h3>
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
                                                <label for="">Item:</label>
                                                <select class="form-select" id="item" name="item" autocomplete="off" style="width:100%">
                                                    <option value="">-- Select Item --</option>
                                                    <?php
                                                        $select_db_itemfile="SELECT * FROM itemfile ORDER BY itmdsc";
                                                        $stmt_itemfile	= $link->prepare($select_db_itemfile);
                                                        $stmt_itemfile->execute();

                                                        while($rs_itemfile = $stmt_itemfile->fetch()){
                                                            echo "<option value=\"".$rs_itemfile['itmcde']."\">".$rs_itemfile['itmdsc']."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                    <div class="w-100 d-flex justify-content-center" id="item_total">
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

            $("#item").change(function(){
                var end = this.value;
                if(end == ""){

                    $("#item_total").html("");

                    $(".btns_item").html(`
                    <div class='col-4'>\
                        <input type='button' name='flexRadioDefault' id='flexRadioDefault1' class='btn btn-primary' value='Export to PDF' onclick='exp_pdf()'>\
                    </div>\
                    
                    <div class='col-4'>\
                        <input type='button' name='flexRadioDefault' id='flexRadioDefault1'  class='btn btn-primary' value='Export to TXT' onclick='exp_txt()'>\
                    </div>
                        
                    `);
                }else{

                    $(".btns_item").html(`<div class='col-8 d-flex justify-content-center'>\
                    <input type='button' name='flexRadioDefault' id='flexRadioDefault1'  class='btn btn-primary' value='Display Balance' onclick='exp_txt()'>\
                    </div>`); 
                }

            });

            function exp_pdf(){

                
                if($("#item").val() == ""){

                    $("#txt_output_type").val("");

                    document.forms.myforms.target = "_blank";
                    document.forms.myforms.method = "post";
                    document.forms.myforms.action = "inventory_balance_rep.php";
                    //document.forms.myforms.action = "var_dump.php";
                    document.forms.myforms.submit();

                }else{
                    var date_filter = $("#date_search").val();
                    var item_filter = $("#item").val();
                    xdata = "date_search="+date_filter+"&item="+item_filter;

                    jQuery.ajax({    

                        data:xdata,
                        dataType:"json",
                        type:"post",
                        url:"inventory_balance_ajax.php", 

                        success: function(xdata2){  

                            if(xdata2["itm_total"] == null){
                                xdata2["itm_total"] = 0;
                            }
                            
                            $("#item_total").html("Balance: <b>"+xdata2["itm_total"]+"</b>");
                        }   
                    })
                }


            }

            function exp_txt(){

                if($("#item").val() == ""){
                    $("#txt_output_type").val("tab");
                    document.forms.myforms.target = "_blank";
                    document.forms.myforms.method = "post";
                    document.forms.myforms.action = "inventory_balance_rep.php";
                    document.forms.myforms.submit();
                }else{
                    var date_filter = $("#date_search").val();
                    var item_filter = $("#item").val();
                    xdata = "date_search="+date_filter+"&item="+item_filter;

                    jQuery.ajax({    

                        data:xdata,
                        dataType:"json",
                        type:"post",
                        url:"inventory_balance_ajax.php", 

                        success: function(xdata2){  

                            if(xdata2["itm_total"] == null){
                                xdata2["itm_total"] = 0;
                            }
                            $("#item_total").html("Balance: <b>"+xdata2["itm_total"]+"</b>");
                        }
                    })
                }


            }

            $(document).ready(function(){

                    var d = new Date();
                    var month = d.getMonth()+1;
                    var day = d.getDate();

                    var output = (month<10 ? '0' : '') + month + '/' + (day<10 ? '0' : '') + day + '/' +  d.getFullYear();
                    $('#date_search').val(output);

                    // Initialize Select2 with search functionality
                    $('#item').select2({
                        theme: 'bootstrap-5',
                        placeholder: '-- Select Item --',
                        allowClear: true,
                        width: '100%'
                    });
            });
    </script>

<?php 
require "includes/main_footer.php";
?>

