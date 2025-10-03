(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booking_panel_js.on('click', '.booknetic_card', function()
        {
            $(this).parent().children('.booknetic_card_selected').removeClass('booknetic_card_selected');
            $(this).addClass('booknetic_card_selected');

            booknetic.stepManager.goForward();
        });

    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'location' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

    bookneticHooks.addFilter('step_validation_location' , function ( result , booknetic )
    {
        if( !( booknetic.getSelected.location() > 0 ) )
        {
            return {
                status: false,
                errorMsg: booknetic.__('select_location')
            };
        }

        return result
    });

})(jQuery);