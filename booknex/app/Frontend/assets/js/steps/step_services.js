(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booking_panel_js.on('click', '.booknetic_service_card', booknetic.throttle(function(e)
        {
            // If view more button is clicked inside services card
            if ( $(e.target).is( ".booknetic_view_more_service_notes_button" ) ) {
                $( this ).find( '.booknetic_service_card_description_wrapped, .booknetic_view_more_service_notes_button' ).css( 'display', 'none' );
                $( this ).find( '.booknetic_service_card_description_fulltext, .booknetic_view_less_service_notes_button' ).css( 'display', 'inline' );
                booknetic.handleScroll();
                return
            } else if ( $(e.target).is( '.booknetic_view_less_service_notes_button' ) ) {
                $( this ).find( '.booknetic_service_card_description_wrapped, .booknetic_view_more_service_notes_button' ).css( 'display', 'inline' );
                $( this ).find( '.booknetic_service_card_description_fulltext, .booknetic_view_less_service_notes_button' ).css( 'display', 'none' );
                booknetic.handleScroll();
                return
            }

            $(this).parents('.bkntc_service_list').find('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');
            $(this).addClass('booknetic_service_card_selected');

            booknetic.stepManager.goForward();
        }));
    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'service' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

    bookneticHooks.addAction('loaded_step', function( booknetic, new_step_id )
    {
        if( new_step_id !== 'service' )
            return;

        let accordion = booknetic.panel_js.find(".bkntc_service_list .booknetic_category_accordion");

        if ( accordion.attr('data-accordion') == 'on' )
        {
            accordion.toggleClass('active');
            accordion.find('>div').not(':first-child').addClass('booknetic_category_accordion_hidden');
            accordion.attr('data-accordion', 'off');
        }
    });

    bookneticHooks.addFilter('step_validation_service' , function ( result , booknetic )
    {
        if( !( booknetic.getSelected.service() > 0 ) )
        {
            return {
                status: false,
                errorMsg: booknetic.__('select_service')
            };
        }

        return result
    });

})(jQuery);