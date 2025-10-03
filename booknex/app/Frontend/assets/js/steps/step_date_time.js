(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booking_panel_js.on('click', '.booknetic_calendar_days:not(.booknetic_calendar_empty_day)[data-date]', function()
        {
            var date = $(this).data('date');

            booking_panel_js.find(".booknetic_times_list").empty();

            var times = date in booknetic.calendarDateTimes['dates'] ? booknetic.calendarDateTimes['dates'][ date ] : [];
            var time_show_format = booknetic.time_show_format == 2 ? 2 : 1;

            for( var i = 0; i < times.length; i++ )
            {
                var time_badge = '';
                if( times[i]['weight'] > 0 && !( 'hide_available_slots' in booknetic.calendarDateTimes && booknetic.calendarDateTimes['hide_available_slots'] == 'on' ) )
                {
                    time_badge = '<div class="booknetic_time_group_num">' + times[i]['weight'] + ' / ' + times[i]['max_capacity'] + '</div>';
                }

                let html = '<div class="booknetic_time_element" data-time="' + times[i]['start_time'] + '" data-endtime="' + times[i]['end_time'] + '" data-date-original="' + times[i]['date'] + '"><div>' + times[i]['start_time_format'] + '</div>' + (time_show_format == 1 ? '<div>' + times[i]['end_time_format'] + '</div>' : '') + time_badge + '</div>';
                var res = bookneticHooks.doFilter('bkntc_date_time_load' , html ,times[i] ,booknetic);

                booking_panel_js.find(".booknetic_times_list").append(res);
            }

            booking_panel_js.find(".booknetic_times_list").scrollTop(0);
            // booking_panel_js.find(".booknetic_times_list").getNiceScroll().resize();

            booking_panel_js.find(".booknetic_calendar_selected_day").removeClass('booknetic_calendar_selected_day');

            $(this).addClass('booknetic_calendar_selected_day');

            booking_panel_js.find(".booknetic_times_title").text( $(this).data('date-format') );

            if( booknetic.dateBasedService )
            {
                booking_panel_js.find(".booknetic_times_list > [data-time]:eq(0)").trigger('click');
            }
            else if( booknetic.isMobileView() )
            {
                $('html,body').animate({scrollTop: parseInt(booking_panel_js.find('.booknetic_time_div').offset().top) - 100}, 1000);
            }
        }).on('click', '.booknetic_prev_month', function ()
        {
            var month = booknetic.calendarMonth - 1;
            var year = booknetic.calendarYear;

            if( month < 0 )
            {
                month = 11;
                year--;
            }

            booknetic.nonRecurringCalendar( year, month, true, true );
        }).on('click', '.booknetic_next_month', function ()
        {
            var month = booknetic.calendarMonth + 1;
            var year = booknetic.calendarYear;

            if( month > 11 )
            {
                month = 0;
                year++;
            }

            booknetic.nonRecurringCalendar( year, month, true, true );
        }).on('click', '.booknetic_times_list > div', function ()
        {
            booking_panel_js.find('.booknetic_selected_time').removeClass('booknetic_selected_time');
            $(this).addClass('booknetic_selected_time');

            if( booking_panel_js.find('#booknetic_bring_someone_section').length == 0 )
            {
                booknetic.stepManager.goForward();
            }
        }).on('change', '#booknetic_bring_someone_checkbox', function(event)
        {
            if( $(this).is(':checked') )
            {
                booking_panel_js.find('.booknetic_number_of_brought_customers').removeClass('d-none');
            }
            else
            {
                booking_panel_js.find('.booknetic_number_of_brought_customers').addClass('d-none');
            }

            booknetic.handleScroll();
        });

    });

    bookneticHooks.addFilter('step_validation_date_time' , function ( result , booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        if( booknetic.getSelected.serviceIsRecurring() )
        {
            var service_repeat_type = booknetic.serviceData['repeat_type'];

            if( service_repeat_type == 'weekly' )
            {
                if( booking_panel_js.find('.booknetic_times_days_of_week_area > .booknetic_active_day').length == 0 )
                {
                    return {
                        status: false,
                        errorMsg: booknetic.__('select_week_days')
                    };
                }
                else
                {
                    var timeNotSelected = false;
                    booking_panel_js.find('.booknetic_times_days_of_week_area > .booknetic_active_day').each(function ()
                    {
                        if( $(this).find('.booknetic_wd_input_time').val() == null )
                        {
                            timeNotSelected = true;
                            return;
                        }
                    });

                    if( timeNotSelected )
                    {
                        return {
                            status: false,
                            errorMsg: booknetic.__('date_time_is_wrong')
                        };
                    }
                }
            }
            else if( service_repeat_type == 'monthly' )
            {

            }

            if( booknetic.getSelected.recurringStartDate() == '' )
            {
                return {
                    status: false,
                    errorMsg: booknetic.__('select_start_date')
                };
            }

            if( booknetic.getSelected.recurringEndDate() == '' )
            {
                return {
                    status: false,
                    errorMsg: booknetic.__('select_end_date')
                };
            }

        }
        else
        {
            if( booknetic.getSelected.date_in_customer_timezone() == '')
            {
                return {
                    status: false,
                    errorMsg: booknetic.__('select_date')
                };
            }

            if( booknetic.getSelected.time() == '')
            {
                return {
                    status: false,
                    errorMsg: booknetic.__('select_time')
                };
            }
        }

        return result
    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'date_time' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

    bookneticHooks.addAction('loaded_step', function( booknetic, new_step_id, old_step_id, ajaxData )
    {
        if( new_step_id !== 'date_time' )
            return;

        let booking_panel_js = booknetic.panel_js;

        booknetic.serviceData = null;
        booknetic.dateBasedService   = ajaxData['service_info']['date_based'];
        booknetic.serviceMaxCapacity = ajaxData['service_info']['max_capacity'];

        if( ajaxData['service_type'] === 'non_recurring' )
        {
            booknetic.calendarDateTimes = ajaxData['data'];
            booknetic.time_show_format = ajaxData['time_show_format'];

            let calendarStartYear = ajaxData['calendar_start_year'];
            let calendarStartMonth = (typeof ajaxData['calendar_start_month'] === 'undefined' ? undefined : ajaxData['calendar_start_month'] -1 );

            if ( booknetic.checkIfNoDatesAvailable( ajaxData ) )
            {
                let date = new Date( calendarStartYear, calendarStartMonth + 1 );

                booknetic.nonRecurringCalendar(date.getFullYear(), date.getMonth(), true, true );
            }
            else
            {
                booknetic.nonRecurringCalendar(calendarStartYear, calendarStartMonth, false );
            }

            booknetic.addGroupAppointmentsCounterForBookneticCalendarDays();
        }
        else
        {
            booknetic.serviceData = ajaxData['service_info'];
            booknetic.initRecurringElements();
        }
    });

})(jQuery);