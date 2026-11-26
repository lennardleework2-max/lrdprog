<?php
// Top module bar + page search. Shared by main_header.php and the pages that build their own header.
if(!isset($menu_tree)){
    require_once("includes/menu_tree.php");
    $menu_tree = menu_load_tree($link, $_SESSION['usercode'], $userdesc == "admin", isset($permission_filename) ? $permission_filename : $filename);
}
?>
        <?php if(!empty($menu_tree['modules'])):?>
        <style>
        .module_bar{
            display:flex;
            overflow-x:auto;
            scrollbar-width:none;
            background-color:#2b3035;
        }
        .module_tab{
            display:flex;
            align-items:center;
            gap:8px;
            padding:8px 16px;
            white-space:nowrap;
            font-family:arial;
            font-size:14px;
            color:#ced4da;
            text-decoration:none;
            border-bottom:3px solid transparent;
        }
        .module_tab:hover{
            color:#fff;
            background-color:#343a40;
        }
        .module_tab.on{
            color:#fff;
            background-color:#212529;
            border-bottom-color:#4dabf7;
        }
        .module_wrap{
            display:flex;
            align-items:center;
            background-color:#2b3035;
        }
        .module_wrap .module_bar{
            flex:1;
            min-width:0;
        }
        .module_search{
            position:relative;
            width:280px;
            flex:none;
            padding:4px 10px;
        }
        .module_search input{
            width:100%;
            background-color:#343a40;
            border:1px solid #495057;
            border-radius:6px;
            color:#fff;
            padding:4px 10px;
            font-size:14px;
            outline:none;
        }
        .module_search input:focus{
            border-color:#4dabf7;
        }
        .module_results{
            position:absolute;
            top:100%;
            right:10px;
            left:10px;
            z-index:1050;
            max-height:60vh;
            overflow-y:auto;
            background-color:#fff;
            border:1px solid #dee2e6;
            border-radius:6px;
            box-shadow:0 6px 16px rgba(0,0,0,.2);
        }
        .module_result{
            display:block;
            padding:6px 10px;
            color:#212529;
            text-decoration:none;
            font-size:14px;
            border-bottom:1px solid #f1f3f5;
        }
        .module_result small{
            display:block;
            color:#6c757d;
            font-size:12px;
        }
        .module_result.on, .module_result:hover{
            background-color:#e7f1ff;
            color:#212529;
        }
        .module_noresult{
            padding:8px 10px;
            color:#6c757d;
            font-size:14px;
        }
        @media (max-width:768px){
            .module_wrap{
                flex-wrap:wrap;
            }
            .module_search{
                width:100%;
            }
        }
        </style>

        <div class="container-fluid">
            <div class="row">
            <div class="col-12 px-0 module_wrap">
                <nav class="module_bar">
                    <?php foreach($menu_tree['modules'] as $mi => $module):?>
                        <a href="<?php echo htmlspecialchars(menu_first_page($module), ENT_QUOTES); ?>"
                           class="module_tab<?php echo $mi === $menu_tree['active'] ? ' on' : ''; ?>"
                           data-module="<?php echo $mi; ?>"
                           data-direct="<?php echo $module['children'] ? 0 : 1; ?>">
                            <i class="<?php echo htmlspecialchars($module['menlogo'], ENT_QUOTES); ?>"></i><?php echo htmlspecialchars($module['mencap']); ?>
                        </a>
                    <?php endforeach;?>
                </nav>

                <div class="module_search">
                    <input type="search" id="page_search" placeholder="Search all pages... (Ctrl+K)" autocomplete="off">
                    <div class="module_results" id="page_search_results" hidden></div>
                </div>
            </div>
            </div>
        </div>

        <script>
        // top-bar search over every page the user can open
        (function(){
            var pages   = <?php echo json_encode(menu_search_index($menu_tree), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            var input   = document.getElementById('page_search');
            var results = document.getElementById('page_search_results');
            var picked  = 0;

            function esc(s){
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;');
            }

            function render(){
                var q = input.value.trim().toLowerCase();
                if(q === ''){
                    results.hidden = true;
                    return;
                }

                // title matches first, then matches on the sub-menu path
                var hits = pages.filter(function(p){
                    return (p.title + ' ' + p.path).toLowerCase().indexOf(q) !== -1;
                }).sort(function(a, b){
                    return (a.title.toLowerCase().indexOf(q) === -1) - (b.title.toLowerCase().indexOf(q) === -1);
                }).slice(0, 12);

                picked = 0;
                results.innerHTML = hits.length ? hits.map(function(p, i){
                    return "<a href='" + esc(p.href) + "' class='module_result" + (i === 0 ? " on" : "") + "'>" + esc(p.title) + "<small>" + esc(p.path) + "</small></a>";
                }).join('') : "<div class='module_noresult'>No pages found</div>";
                results.hidden = false;
            }

            function move(step){
                var items = results.querySelectorAll('.module_result');
                if(!items.length){
                    return;
                }
                items[picked].classList.remove('on');
                picked = (picked + step + items.length) % items.length;
                items[picked].classList.add('on');
                items[picked].scrollIntoView({block: 'nearest'});
            }

            input.addEventListener('input', render);
            input.addEventListener('focus', render);
            input.addEventListener('keydown', function(e){
                if(e.key === 'ArrowDown'){
                    e.preventDefault();
                    move(1);
                }
                if(e.key === 'ArrowUp'){
                    e.preventDefault();
                    move(-1);
                }
                if(e.key === 'Enter'){
                    e.preventDefault();
                    var on = results.querySelector('.module_result.on');
                    if(on){
                        window.location.href = on.getAttribute('href');
                    }
                }
                if(e.key === 'Escape'){
                    input.value = '';
                    results.hidden = true;
                    input.blur();
                }
            });
            document.addEventListener('click', function(e){
                if(!e.target.closest('.module_search')){
                    results.hidden = true;
                }
            });
            document.addEventListener('keydown', function(e){
                if((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k'){
                    e.preventDefault();
                    input.focus();
                    input.select();
                }
            });
        })();
        </script>
        <?php endif;?>
