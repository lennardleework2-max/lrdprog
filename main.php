<?php
require "includes/main_header.php";
?>
    <form name='myforms' id="myforms" method="post" target="_self"> 
        <table class='big_table'> 
            <tr colspan=1>
                <td colspan=1 class='td_bl'>
                    <?php
                        require 'includes/main_menu.php';
                    ?>
                </td>

                <td colspan=1 class="td_br" id="td_br">
                    <?php
                        //$field1 = new Pager("Page name" , "tablename" ,"link");
                    ?>
                </td>

            </tr>
        </table>
    </form>

<?php 
require "includes/main_footer.php";
?>

