(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booknetic.deleteCartItem = function (itemIndex , itemLine )
        {
            itemIndex = Number.parseInt(itemIndex);
            if( booknetic.cartArr.length == 1 && itemIndex == 0)
            {
                $("#booknetic_start_new_booking_btn").trigger('click' );
                return;
            }

            if(booknetic.cartCurrentIndex != itemIndex)
            {
                itemLine.remove();
                booknetic.cartArr.splice(itemIndex,1);
                booknetic.cartCurrentIndex--;
                booknetic.cartHTMLBody.splice(itemIndex,1);
                booknetic.cartHTMLSideBar.splice(itemIndex,1);
                $("div.booknetic-cart .booknetic-cart-col").each( function (index){
                    $(this).attr('data-index', index);
                })
                this.showCartIcon();
            }else
            {

                let hasPrev = booknetic.cartArr[itemIndex-1] != undefined;
                let hasNext = booknetic.cartArr[itemIndex+1] != undefined;
                let currentBody = booking_panel_js[0].querySelector('.booknetic_appointment_container_body .booknetic_need_copy');

                if( hasPrev )
                {
                    currentBody.parentNode.insertBefore( booknetic.cartHTMLBody[itemIndex-1] , currentBody);

                    booking_panel_js[0].querySelectorAll('.booknetic_appointment_steps_body .booknetic_appointment_step_element.need_copy').forEach((current)=>{
                        let id = current.getAttribute('data-step-id');
                        current.parentNode.insertBefore(booknetic.cartHTMLSideBar[itemIndex-1][id] , current);
                        current.parentNode.removeChild(current);
                    });

                    booknetic.cartCurrentIndex = itemIndex-1;
                }
                else if( hasNext )
                {
                    currentBody.parentNode.insertBefore( booknetic.cartHTMLBody[itemIndex+1] , currentBody);
                    booking_panel_js[0].querySelectorAll('.booknetic_appointment_steps_body .booknetic_appointment_step_element.need_copy').forEach((current)=>{
                        let id = current.getAttribute('data-step-id');
                        current.parentNode.insertBefore(booknetic.cartHTMLSideBar[itemIndex+1][id] , current);
                        current.parentNode.removeChild(current);
                    });
                    booknetic.cartCurrentIndex = itemIndex;
                }
                currentBody.parentNode.removeChild(currentBody);
                booknetic.cartArr.splice( itemIndex,1);
                booknetic.cartHTMLBody.splice( itemIndex ,1);
                booknetic.cartHTMLSideBar.splice( itemIndex ,1);

                itemLine.remove();
                $("div.booknetic-cart .booknetic-cart-col").each( function (index){
                    $(this).attr('data-index', index);
                });

                booknetic.cartPrevStep = undefined;

            }
            booknetic.stepManager.saveData();
            booknetic.stepManager.loadStep('cart');
        };

        booknetic.showCartIcon = function ()
        {
            let cartContainer = booking_panel_js.find('.booknetic_appointment_container_header_cart');
            cartContainer.find('span').text(booknetic.cartArr.length);

            if (booknetic.cartArr.length > 0 ) {
                cartContainer.fadeIn();
            } else {
                cartContainer.fadeOut();
            }
        };

        booking_panel_js.on('click', '.booknetic-cart-item-btns',function(e)
        {
            e.stopPropagation();
        }).on('mouseenter', '.booknetic-cart-item-info' , function ()
        {
            $(this).parents('.booknetic-cart-item-body-row').addClass('show');
        }).on('mouseleave', '.booknetic-cart-item-info' , function ()
        {
            $(this).parents('.booknetic-cart-item-body-row').removeClass('show');
        }).on('click', '.booknetic-appointment-container-cart-btn' , function ()
        {
            let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
                current_step_id = current_step_el.data('step-id'),
                next_step_el	= booking_panel_js.find('.booknetic_appointment_steps_body div[data-step-id="cart"]'),
                next_step_id    = 'cart';
            booknetic.toast( false );
            booknetic.stepManager.loadStep( next_step_id );
            // current_step_el.addClass('booknetic_selected_step');

            if( booknetic.isMobileView() )
            {
                $('html,body').animate({scrollTop: parseInt(booking_panel_js.offset().top) - 100}, 1000);
            }
            if(current_step_id != 'cart')
            {
                booknetic.cartPrevStep = current_step_id;
            }
        }).on('hover', '.booknetic-cart-item-info', function()
        {
            $(this).closest('.booknetic-cart-item-body-row').toggleClass('show');
        }).on('click', '.booknetic-cart-item-error-close', function ()
        {
            $(this).closest('.booknetic-cart-item-error').removeClass('show');
        }).on('click', '.booking-again' , function ()
        {
            booknetic.cartCurrentIndex++;
            booknetic.stepManager.saveData();
            $("#booknetic_start_new_booking_btn").trigger('click');
        }).on('click' ,'.booknetic-cart-item-remove' , function ()
        {
            let itemLine  = $(this).parents('div.booknetic-cart-col');
            let itemIndex = itemLine.attr('data-index');
            booknetic.deleteCartItem( itemIndex , itemLine );

        }).on('click','.booknetic-cart-item-edit',function ()
        {
            let itemLine  = $(this).parents('div.booknetic-cart-col');
            let itemIndex = Number.parseInt(itemLine.attr('data-index'));

            let currentBody = booking_panel_js[0].querySelector('.booknetic_appointment_container_body .booknetic_need_copy');

            currentBody.parentNode.insertBefore( booknetic.cartHTMLBody[itemIndex] , currentBody);

            currentBody.parentNode.removeChild(currentBody);

            // booknetic.cartHTMLBody[booknetic.cartCurrentIndex] = currentBody.cloneNode(true);
            booknetic.cartHTMLBody[booknetic.cartCurrentIndex] = $(currentBody).clone(true,true).get(0);

            booking_panel_js[0].querySelectorAll('.booknetic_appointment_steps_body .booknetic_appointment_step_element.need_copy').forEach((current)=>
            {
                let id = current.getAttribute('data-step-id');

                if(booknetic.cartHTMLSideBar[booknetic.cartCurrentIndex] == undefined)
                {
                    booknetic.cartHTMLSideBar[booknetic.cartCurrentIndex] = {};
                }
                booknetic.cartHTMLSideBar[booknetic.cartCurrentIndex][id] = $(current).clone(true,true).get(0);

                current.parentNode.insertBefore(booknetic.cartHTMLSideBar[itemIndex][id] , current)
                current.parentNode.removeChild(current);
            });

            var start_step = booking_panel_js.find(".booknetic_appointment_step_element:not(.booknetic_menu_hidden):eq(0)");

            booknetic.cartCurrentIndex = itemIndex;
            booknetic.stepManager.loadStep(start_step.attr('data-step-id'));
        });

    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        let booking_panel_js = booknetic.panel_js;

        if( new_step_id === 'cart' )
        {
            booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
        }

        if( new_step_id !=='cart' )
        {
            booknetic.cartPrevStep = undefined;
        }

        /**
         * CSS Height sorunu var deye bele yazilib kod, .booknetic_need_copy ichinde olan step deilse height 100% verenede css qarishir,
         * auto verir ve bu container avtomatik 0px olur heighti, ichinde olan steplerde ise 100% verir, butun alani kaplayir;
         * Gelecekde daha seliqeli usul dushunmek olar;
         */
        if( booking_panel_js.find(`.booknetic_need_copy [data-step-id="${new_step_id}"]`).length === 0 )
        {
            booking_panel_js.find('.booknetic_need_copy').css('height','auto');
        }
        else
        {
            booking_panel_js.find('.booknetic_need_copy').css('height','100%');
        }

        if( old_step_id === 'cart' )
        {
            let cartHtmlLastIndex = booking_panel_js.find('.booknetic-cart .booknetic-cart-col').last().attr('data-index');

            if( booknetic.cartArr.length-1 > cartHtmlLastIndex )
            {
                booknetic.cartArr = [];
                booknetic.cartCurrentIndex--;
            }
        }
    });

    bookneticHooks.addAction('loaded_step', function( booknetic, new_step_id, old_step_id, ajaxData )
    {
        let booking_panel_js = booknetic.panel_js;

        if( new_step_id === 'cart' )
        {
            booking_panel_js.find('.booknetic_appointment_container_body div[data-step-id="cart"]').css('display', 'flex');
            booknetic.showCartIcon();
        }

        booknetic.cartErrors.error = []
    });

    bookneticHooks.addAction('step_loaded_with_error', function( booknetic, new_step_id, old_step_id, ajaxData )
    {
        let booking_panel_js = booknetic.panel_js;

        //todo: cart needs to be added to getSelected obj
        if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="cart"]').hasClass('booknetic_menu_hidden') )
        {
            if ( ajaxData.hasOwnProperty('errors') )
            {
                booknetic.toast(ajaxData.errors[0].message);
            }
            else
            {
                booknetic.toast(ajaxData.error_msg);
            }
        }

        if (ajaxData != undefined && typeof ajaxData['errors'] != 'undefined')
        {
            let errors = ajaxData['errors'];
            errors.filter(function (value,index)
            {
                return typeof value['cart_item'] != 'undefined';
            })
            booknetic.cartErrors.error = errors;
        }
        else
        {
            booknetic.cartErrors.error = [];
        }
    });



})(jQuery);