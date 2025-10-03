(function($)
{

    bookneticHooks.addAction('loaded_step', function( booknetic, new_step_id, old_step_id, ajaxData )
    {
        let booking_panel_js = booknetic.panel_js;

        if( booknetic.getSelected.serviceIsRecurring() )
        {
            booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="recurring_info"].booknetic_menu_hidden').slideDown(300, function ()
            {
                $(this).removeClass('booknetic_menu_hidden');
                booknetic.stepManager.refreshStepNumbers();
            });
        }
        else
        {
            booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="recurring_info"]:not(.booknetic_menu_hidden)').slideUp(300, function ()
            {
                $(this).addClass('booknetic_menu_hidden');
                booknetic.stepManager.refreshStepNumbers();
            });
        }
    });

})(jQuery);