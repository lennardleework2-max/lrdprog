
    <style>

    /*Sidebar*/
    #sidebar-wrapper{
        background-color:#212529;
        width:300px; /* fixed: the panel slides/clips instead of squeezing its text */
    }

    /* stable wrapping inside the sidebar; ignores the footer's inline white-space toggling */
    #sidebar-wrapper .nav-item{
        white-space:normal !important;
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
        height:calc(100vh - 122px);
        overflow:hidden;
        overflow-y:scroll;
        background-color:#212529;
        transition:0.5s;
        scrollbar-width:none;
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

    .menu_panel_title{
        color:#fff;
        font-family:arial;
        font-weight:bold;
        font-size:15px;
        padding:10px 10px 8px;
        border-bottom:1px solid #394047;
    }
    .menu_panel_title i{
        margin-right:10px;
    }
    .menu_divider{
        border-top:1px solid #394047;
        margin:4px 0;
    }
    .flex-column li.menu_leaf.active > a{
        background:#394047;
        color:#fff;
        box-shadow:inset 3px 0 0 #4dabf7;
    }

    </style>

    <?php
        if(!isset($menu_tree)){
            $menu_tree = array('modules' => array(), 'active' => -1);
        }
        $menu_shown = max(0, $menu_tree['active']);
    ?>

    <div id="sidebar-wrapper" style="border:0">
        <nav class="sidebar card" style="background-color:#212529">
            <?php foreach($menu_tree['modules'] as $mi => $module):?>
                <div class="menu_panel" data-module="<?php echo $mi; ?>" <?php echo $mi === $menu_shown ? '' : 'hidden'; ?>>
                    <div class="menu_panel_title">
                        <i class="<?php echo htmlspecialchars($module['menlogo'], ENT_QUOTES); ?>"></i><?php echo htmlspecialchars($module['mencap']); ?>
                    </div>

                    <ul class="nav flex-column">
                        <?php
                            // a module with no children is itself a page (e.g. Dashboard)
                            $menu_pages  = $module['children'] ? array() : array($module);
                            $menu_groups = array();
                            foreach($module['children'] as $child){
                                if($child['children']){
                                    $menu_groups[] = $child;
                                }else{
                                    $menu_pages[] = $child;
                                }
                            }

                            foreach($menu_pages as $page){
                                echo menu_leaf_html($page, 0);
                            }
                            if($menu_pages && $menu_groups){
                                echo "<li class='menu_divider'></li>";
                            }
                            foreach($menu_groups as $group){
                                echo menu_group_html($group, 0);
                            }
                        ?>
                    </ul>
                </div>
            <?php endforeach;?>
        </nav>
    </div>

    <script>
    // top-bar module tabs: show that module in the sidebar instead of leaving the page
    document.querySelectorAll('.module_tab').forEach(function(tab){
        tab.addEventListener('click', function(e){
            if(tab.dataset.direct === '1'){
                return;
            }
            e.preventDefault();

            document.querySelectorAll('.menu_panel').forEach(function(panel){
                panel.hidden = panel.dataset.module !== tab.dataset.module;
            });
            document.querySelectorAll('.module_tab').forEach(function(other){
                other.classList.toggle('on', other === tab);
            });

            if(!document.querySelector('.td_bl.menuDisplayed')){
                $('.menu-toggle').trigger('click');
            }
        });
    });
    </script>
