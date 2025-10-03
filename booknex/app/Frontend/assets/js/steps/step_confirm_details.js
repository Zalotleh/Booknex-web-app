(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booking_panel_js.on('click', '.booknetic_payment_method', function ()
        {
            booking_panel_js.find(".booknetic_payment_method_selected").removeClass('booknetic_payment_method_selected');
            $(this).addClass('booknetic_payment_method_selected');

            if( $(this).data('payment-type') == 'local' )
            {
                // deposit payment deyishende Ajax atib price sectionu update edir. Local sechilende ise deposit payment disabled nezere alinir deye, burda onu sondurmek uchun click edir.
                booking_panel_js.find('input[name="input_deposit"][value="1"]').click();

                booking_panel_js.find(".booknetic_hide_on_local").removeClass('booknetic_hidden').fadeOut(100);
            }
            else
            {
                booking_panel_js.find(".booknetic_hide_on_local").removeClass('booknetic_hidden').fadeIn(100);
            }
        }).on( 'change', '.booknetic_deposit_radios', function()
        {
            var selectedButton = $( this ).find( 'input[name="input_deposit"]:checked' ).val();

            if ( selectedButton == 1 )
            {
                $('.booknetic_deposit_price.booknetic_hide_on_local' ).show();
                $('.booknetic_payment_methods_footer' ).css( 'background-color', '#F8D7DF' );
            }
            else
            {
                $( '.booknetic_deposit_price.booknetic_hide_on_local' ).hide();
                $( '.booknetic_payment_methods_footer' ).css( 'background-color', 'white' );
            }

            booknetic.ajax('update_prices', booknetic.ajaxParameters( { current_step: 'confirm_details' } ), function ( result )
            {
                booking_panel_js.find('.booknetic_prices_box').html(result['prices_html']);
                booking_panel_js.find('.booknetic_sum_price').text(result['sum_price_txt']);
                booking_panel_js.find('.booknetic_deposit_amount_txt').text(result['deposit_txt']);
            });
        });

    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'confirm_details' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

    bookneticHooks.addAction('loaded_step', function( booknetic, new_step_id, old_step_id, ajaxData )
    {
        if( new_step_id !== 'confirm_details' )
            return;

        let booking_panel_js = booknetic.panel_js;

        if ( ! booknetic.isMobileView() )
        {
            booking_panel_js.find( '.booknetic_portlet_content' ).handleScrollBooknetic();
        }
    });

})(jQuery);