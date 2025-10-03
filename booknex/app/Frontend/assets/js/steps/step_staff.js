(function($)
{

    bookneticHooks.addFilter('step_validation_staff' , function ( result , booknetic )
    {
        if( !( booknetic.getSelected.staff() > 0 || booknetic.getSelected.staff() == -1 ) )
        {
            return {
                status: false,
                errorMsg: booknetic.__('select_staff')
            };
        }

        return result
    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'staff' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

})(jQuery);