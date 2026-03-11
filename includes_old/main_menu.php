
    <style>

    /*Sidebar*/
    #sidebar-wrapper{
        background-color:#212529;
    }

    /* .td_bl ul::-webkit-scrollbar { 
    width: 0 !important;
    display: none; 
    } */

    
    .sidebar-wrapper ::-webkit-scrollbar {
        /*width: 0;  /* Remove scrollbar space */
        background: transparent;  /* Optional: just make scrollbar invisible */
    }

    .td_bl{
        width:0px;
        margin:0px;
        padding: 0px;
        z-index:2;
        position:absolute;
        height:calc(100vh - 85px);
        overflow:hidden;
        overflow-y:scroll;
        background-color:#212529;
        transition:0.5s;
    }


    .td_bl::-webkit-scrollbar {
        width: 0; 
    }




    
    /*Change the width of the sidebar to dispalyed*/

    .td_bl.menuDisplayed{

        transition:0.5s;
        width:300px;
        
    }

    .nav-item{
        white-space: nowrap;
        border-bottom:none;
        width:inherit;
    }
    .nav-link{
        border-bottom:none;
    }

    /* .td_br.menuDisplayed{
        overflow-y:hidden;
        transition:0.5s;
        padding-left:250px; 
    } */
    .td_br{
        transition:0.5s;
    }

    /*Sidebar styling */
    .flex-column{
        padding:0;
        list-style:none;
    }
    .flex-column li{
        text-indent:10px;
        /* //margin-left:10px; */
        line-height:30px;

    }
    .flex-column li a{
        font-family:arial;
        font-size:15px;
        display:block;
        text-decoration:none;
        color:#ddd;
        border-bottom:none;
    }

    .flex-column li a:hover{
        color:#212529;
        background:#f8f9fa;
    }

    .nav-txt{
        margin-left:20px;
    }
    .nav-link:focus, .nav-link:hover{
        color:white;
    }

    .dropdown-toggle:after { content: none }

    .ul_menu{
        padding-left:0;
    }
    .ul_menu1{
        box-shadow: 0px 4px 4px 0px #212529 inset;
        background-color:#394047;
    }
    .ul_menu2{
        box-shadow: 0px 4px 4px #394047 inset;
        background-color:#4f5963;
    }

    </style>

    <div id="sidebar-wrapper" style="border:0">
        <nav class="sidebar card" style="background-color:#212529">
            <ul class="nav flex-column">

                    <?php 

                        $order_menu="AND menidx!='x' AND usercode ='".$_SESSION['usercode']."' ORDER BY menidx";

                        if($_SESSION["userdesc"] == "admin"){
                            $order_menu2="AND menidx!='x' ORDER BY menidx";
                            $select_db_menu='SELECT * FROM menus WHERE true '.$order_menu2;
                        }else{
                            $select_db_menu='SELECT * FROM user_menus  where true '.$order_menu;
                        }
                        $stmt_menu	= $link->prepare($select_db_menu);
                        $stmt_menu->execute();
                        while($row_menu = $stmt_menu->fetch()):
                    ?>

                        <?php if($row_menu["is_removed"]== 1):?>
                            <?php continue; ?>
                        <?php endif;?>
                        
                        <?php if(!empty($row_menu["mensub"])):?>
                            <li class="nav-item has-submenu">
                                <a href="#" class="nav-link">
                                    <i class="<?php echo $row_menu["menlogo"] ?>" id="nav-logo"> 
                                    </i><span  style='-webkit-box-decoration-break: clone;' class="nav-txt"><?php echo $row_menu["mencap"]; ?></span>
                                </a>

                                <ul class="submenu collapse ul_menu ul_menu1" style="list-style-type: none;">

                                    <?php
                                        $order_menu2 = "ORDER BY mennum";
                                        $mengrp =  $row_menu["mensub"];
                                        if($_SESSION["userdesc"] == "admin"){
                                            $select_db_menu2="SELECT * FROM menus where mengrp='".$mengrp."' ".$order_menu2;
                                        }else{
                                            $select_db_menu2="SELECT * FROM user_menus where mengrp='".$mengrp."' and usercode='".$_SESSION['usercode']."' ".$order_menu2;
                                        }

                                        $stmt_menu2	= $link->prepare($select_db_menu2);
                                        $stmt_menu2->execute();
                                        while($row_menu2 = $stmt_menu2->fetch()):
                                    ?>

                                        
                                        <?php if(!empty($row_menu2["mensub"])):?>
                                            <li class="nav-item has-submenu">
                                                <a href="#" class="nav-link">
                                                    <i class="<?php echo $row_menu2["menlogo"] ?>" id="nav-logo"> 
                                                    </i><span style='-webkit-box-decoration-break: clone;' class="nav-txt"><?php echo $row_menu2["mencap"]; ?></span>
                                                </a>

                                                <ul class="submenu collapse ul_menu ul_menu2" style="list-style-type: none;">

                                                    <?php
                                                        $order_menu3 = "ORDER BY mennum";
                                                        if($_SESSION["userdesc"] == "admin"){
                                                            $select_db_menu3 = "SELECT * FROM menus where mengrp='".$row_menu2["mensub"]."' AND is_removed!='1' ".$order_menu3;
                                                        }else{
                                                            $select_db_menu3="SELECT * FROM user_menus where mengrp='".$row_menu2["mensub"]."' and usercode='".$_SESSION['usercode']."' AND is_removed!='1' ".$order_menu3;
                                                        }

                                                        // $select_db_menu3 = "SELECT * FROM menus where mengrp='".$row_menu2["mensub"]."'";
                                                        $stmt_menu3	= $link->prepare($select_db_menu3);
                                                        $stmt_menu3->execute();
                                                        while($row_menu3 = $stmt_menu3->fetch()):?>
                                                            <li>
                                                                <a href="<?php echo $row_menu3["menprogram"]; ?>" class="nav-link">
                                                                    <i class="<?php echo $row_menu3["menlogo"] ?>" id="nav-logo"> 
                                                                    </i>
                                                                    <span class="nav-txt" style='-webkit-box-decoration-break: clone;'>
                                                                        <?php echo $row_menu3["mencap"]; ?>
                                                                    </span>
                                                                </a>
                                                            </li>

                                                    <?php endwhile;?>
                                                </ul>
                                         
                                            </li>

                                        <?php else:?>
                                            <li>
                                                <a href="<?php echo $row_menu2["menprogram"]; ?>" class="nav-link">
                                                    <i class="<?php echo $row_menu2["menlogo"] ?>" id="nav-logo"> 
                                                    </i>
                                                    <span class="nav-txt"  style='-webkit-box-decoration-break: clone;'>
                                                        <?php echo $row_menu2["mencap"]; ?>
                                                    </span>
                                                </a>
                                            </li>
                                        <?php endif;?>

                                    <?php endwhile; ?>
                                </ul>
                            </li>
                            
                        <?php elseif(empty($row_menu["mensub"])):?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $row_menu["menprogram"];?>">
                                    <i class="<?php echo $row_menu["menlogo"] ?>" id="nav-logo"> 
                                    </i><span class="nav-txt" style='-webkit-box-decoration-break: clone;'><?php echo $row_menu["mencap"]; ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>

            </ul>
        </nav>
    </div>








