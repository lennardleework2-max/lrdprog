
        <div class="onloading_element"><!-- Place at bottom of page --></div>
        
        <!-- BOOSTRAP JS -->
        <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- CUSTOM SCRIPTS -->
        <script>

            $body = $("body");
            $(document).on({
                ajaxStart: function() {
                     $body.addClass("loading");    
                },
                ajaxStop: function() {
                     $body.removeClass("loading"); 

                     var tababble = document.querySelector(".tabbable");

                     if(tababble != null){
                        $(".tabbable").blur();
                     }

                }    
            });


            //get crudModal
            var crudModal = document.getElementById('crudModal');

            if(crudModal !== null){
                //make sure error message is gone
                crudModal.addEventListener('hidden.bs.modal', function (event) {
                    $(".error_msg").html("");
                })
            }

            function checkNumbers(xthis, before_dec, after_dec,xtype)
            {
                var numbers = xthis.value.split('.');
                var full_str = xthis.value;
                var preDecimal = numbers[0];
                var postDecimal = numbers[1];
                var field_id = xthis.id;

                var full_str_complete = $("#"+field_id).val();

                if(full_str.includes('.') && after_dec == -1){

                    new_num = full_str.substring(0, full_str.length - 1);
                    $("#"+field_id).val(new_num);
                    return;
                }

                if(postDecimal == null){

                    if (preDecimal.length > before_dec)
                    {
                        new_num = full_str.substring(0, full_str.length - 1);
                        $("#"+field_id).val(new_num)
                    } 
                }else{

                    if (preDecimal.length > before_dec || postDecimal.length > after_dec)
                    {
                        new_num = full_str.substring(0, full_str.length - 1);
                        $("#"+field_id).val(new_num);

                    } 
                    
                }
                
            }

            function invalidChars(evt){
                var ASCIICode = (evt.which) ? evt.which : evt.keyCode
                console.log(ASCIICode);
                if (ASCIICode == 101 || ASCIICode == 69 || ASCIICode == 65 || ASCIICode == 45 || ASCIICode == 43)
                    return false;
                return true;
                
            }

            function check_enter(evt) {

                var ASCIICode = (evt.which) ? evt.which : evt.keyCode
                if(ASCIICode == 13){
                    page_click("search");
                }
            }

            function onlyNumberKey(evt) {

                // Only ASCII character in that range allowed
                var ASCIICode = (evt.which) ? evt.which : evt.keyCode
                if (ASCIICode > 31 && (ASCIICode < 48 || ASCIICode > 57))
                    return false;
                return true;
            }

            var doc = document;
            var providers = doc.getElementsByClassName("tabbable");

            for (var i = 0; i < providers.length; i++) {
                providers[i].onclick = function() {
                console.log(this.innerHTML);
                };
            }




            //if screen is small do not make other data be pushed when opening menu
            if (window.matchMedia('(max-width: 576px)').matches)
            {
                $(".pagination").removeClass("pagination-lg")
                // $(".td_br").removeClass();

            }
        //to get the today working jquery
        var old_goToToday = $.datepicker._gotoToday
            $.datepicker._gotoToday = function(id) {
            old_goToToday.call(this,id)
            this._selectDate(id)
        }

        $( ".date_picker" ).datepicker({
            showAnim: "blind",
            changeMonth: true,
            changeYear: true,
            yearRange: "-100:+2",
            showOn: 'focus',
            showButtonPanel: true,
            closeText: 'Clear', // Text to show for "close" button
            beforeShow: function(el, dp) {

                $(el).parent().append($('#ui-datepicker-div'));
                $('#ui-datepicker-div').hide();
            },
            onClose: function () {
                $(this).blur();
                var event = arguments.callee.caller.caller.arguments[0];
                var event_checker = false;
                if(event){
                    event_checker = event.hasOwnProperty('delegateTarget');
                }
                if(event_checker == true){
                    if ($(event.delegateTarget).hasClass('ui-datepicker-close')) {
                    $(this).val('');
                    }
                }

            }
        });

        $('.modal').on('shown.bs.modal', function (e) {

            $("#ui-datepicker-div").addClass("initialD");
        
            $(".date_picker").datepicker({
                showAnim: "blind",
                changeMonth: true,
                changeYear: true,
                yearRange: "-100:+2",
                showOn: 'focus',
                showButtonPanel: true,
                closeText: 'Clear', // Text to show for "close" button
                beforeShow: function(el, dp) {

                    $(el).parent().append($('#ui-datepicker-div'));
                    $('#ui-datepicker-div').hide();

                } ,
                onClose: function () {
                    $(this).blur();
                    var event = arguments.callee.caller.caller.arguments[0];
                    var event_checker = false;
                    if(event){
                        event_checker = event.hasOwnProperty('delegateTarget');
                    }
                    if(event_checker == true){
                        if ($(event.delegateTarget).hasClass('ui-datepicker-close')) {
                        $(this).val('');
                        }
                    }

                },
            }); 

           

        })

        $('.modal').on('hidden.bs.modal', function (e) {

            $("#ui-datepicker-div").removeClass("initialD");
            $(".modal #ui-datepicker-div").remove();
            $(".modal .date_picker").datepicker( "destroy" );

        }) 

            


            $("[date_picker_target]").click(function(){

                var target_val = $(this).attr("date_picker_target");
                $("[date_picker_recipient="+target_val+"]").val('');
            });

            
            //related to menu toggle javascript
            $(".menu-toggle").click(function(){
                $(".arrow_toggle").toggleClass("open");
                $(".td_bl").toggleClass("menuDisplayed");

                if($(".arrow_toggle.open")[0]){

                    $(".td_br").css("opacity","0.5");
                    // $(".td_br").css("pointer-events","none");
                    $(".td_br").css("pointer-events","none");
                    if ($(document).height() > $(window).height()) { 
                        
                    }else{
                        $('body').css('overflow-y','hidden');
                    } 

                    $(".menu-toggle").attr("disabled", true);
                    setTimeout(
                    function() 
                    {
                        $(".td_br").css("pointer-events","initial");
                        $(".nav-item").css("white-space","normal");
                        $(".menu-toggle").attr("disabled", false);
                    }, 500);


                    $(".menu-toggle").hover(function() {
                        $(".arrow-toggle").css("opacity","0.5");
                        $(".arrow-toggle").css("cursor","pointer");
                    });

  


                    //$(".nav-item").css("white-space","normal");
                }else{


                    $(".td_br").css("opacity","1");
                    // $(".td_br").css("pointer-events","initial");

                    $(".nav-item").css("white-space","nowrap");
                    $(".menu-toggle").attr("disabled", true);
                    setTimeout(
                    function() 
                    {
           
                        $(".menu-toggle").attr("disabled", false);
                    }, 500);

                    $(".menu-toggle").hover(function() {
                        $(".arrow-toggle").css("opacity","0.5");
                        $(".arrow-toggle").css("cursor","pointer");
                    });
         
 
                }
                // $(".td_br").toggleClass("menuDisplayed");
            });

            $(".td_br").click(function(){
            
                // $(".arrow_toggle").toggleClass("open");


                if (document.querySelector('.arrow_toggle.open') !== null) {


          
                    // $(".td_br").css("pointer-events","initial");

                 

                    $(".menu-toggle").attr("disabled", true);
                    // $(".td_br").css("pointer-events","none");
                    setTimeout(
                    function() 
                    {
           
                        $(".menu-toggle").attr("disabled", false);
                        // $(".td_br").css("pointer-events","initial");

                    }, 500);

                    $(".td_br").css("opacity","1");
                    $(".nav-item").css("white-space","nowrap");
                    
                    $(".td_bl").removeClass("menuDisplayed");
                    $(".arrow_toggle").removeClass("open");

                    $(".menu-toggle").hover(function() {
                        $(".arrow-toggle").css("opacity","0.5");
                        $(".arrow-toggle").css("cursor","pointer");
                    });


                }

            });



            document.addEventListener("DOMContentLoaded", function(){
                document.querySelectorAll('.sidebar .nav-link').forEach(function(element){
                    
                    element.addEventListener('click', function (e) {

                    let nextEl = element.nextElementSibling;
                    let parentEl  = element.parentElement;	

                        if(nextEl) {
                            e.preventDefault();	
                            let mycollapse = new bootstrap.Collapse(nextEl);
                            
                            if(nextEl.classList.contains('show')){
                            mycollapse.hide();
                            } else {
                                mycollapse.show();
                                // find other submenus with class=show
                                var opened_submenu = parentEl.parentElement.querySelector('.submenu.show');
                                // if it exists, then close all of them
                                if(opened_submenu){
                                new bootstrap.Collapse(opened_submenu);
                                }
                            }
                        }
                    }); // addEventListener
                }) // forEach
            });

        </script>

    </body>
</html>