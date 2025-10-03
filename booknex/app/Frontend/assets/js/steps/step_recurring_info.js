(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booking_panel_js.on('change', '.booknetic_day_of_week_checkbox', function ()
        {
            var activeFirstDay = booking_panel_js.find(".booknetic_times_days_of_week_area .booknetic_active_day").attr('data-day');

            var dayNum	= $(this).attr('id').replace('booknetic_day_of_week_checkbox_', ''),
                dayDIv	= booking_panel_js.find(".booknetic_times_days_of_week_area > [data-day='" + dayNum + "']");

            if( $(this).is(':checked') )
            {
                dayDIv.removeClass('booknetic_hidden').hide().slideDown(200, function ()
                {
                    booknetic.handleScroll();
                }).addClass('booknetic_active_day');

                if( booknetic.dateBasedService )
                {
                    dayDIv.find('.booknetic_wd_input_time').append('<option>00:00</option>').val('00:00');
                }
            }
            else
            {
                dayDIv.slideUp(200, function ()
                {
                    booknetic.handleScroll();
                }).removeClass('booknetic_active_day');
            }

            booking_panel_js.find(".booknetic_times_days_of_week_area .booknetic_active_day .booknetic_copy_time_to_all").fadeOut( activeFirstDay > dayNum ? 100 : 0 );
            booking_panel_js.find(".booknetic_times_days_of_week_area .booknetic_active_day .booknetic_copy_time_to_all:first").fadeIn( activeFirstDay > dayNum ? 100 : 0 );

            if( booking_panel_js.find('.booknetic_day_of_week_checkbox:checked').length > 0 && !booknetic.dateBasedService )
            {
                booking_panel_js.find('.booknetic_times_days_of_week_area').slideDown(200);
            }
            else
            {
                booking_panel_js.find('.booknetic_times_days_of_week_area').slideUp(200);
            }

            booknetic.calcRecurringTimes();
        }).on('click', '.booknetic_date_edit_btn', function()
        {
            if( $(this).attr('data-type') === '1')
            {
                let date_format = $(this).attr('data-date-format');
                let date = $(this).attr('data-date');
                let _this = $(this);
                let textElement = $(this).closest('tr').find('td:eq(1) span.date_text');
                let input = $(this).closest('tr').find('td:eq(1) .booknetic_recurring_info_edit_date');
                textElement.hide();
                let recurringStartDate 	= booknetic.convertDate(booknetic.cartArr[booknetic.cartCurrentIndex]['recurring_start_date'],'Y-m-d',date_format)
                let recurringEndDate 	= booknetic.convertDate(booknetic.cartArr[booknetic.cartCurrentIndex]['recurring_end_date'],'Y-m-d',date_format)

                input.flatpickr(
                    {
                        altInput: true,
                        altFormat: date_format,
                        dateFormat: date_format,
                        monthSelectorType: 'static',
                        locale: {
                            firstDayOfWeek: BookneticData.week_starts_on === 'sunday' ? 0 : 1
                        },
                        minDate: recurringStartDate,
                        maxDate: recurringEndDate,
                        defaultDate: date,
                        onMonthChange :  (selectedDates, dateStr, instance)=>{
                            booknetic.loadAvailableDate(instance , booknetic.ajaxParameters() );
                        },
                        onOpen : (selectedDates, dateStr, instance)=>{
                            booknetic.loadAvailableDate(instance , booknetic.ajaxParameters() );
                        },
                        onChange: function(selectedDates, dateStr, instance) {
                            _this.closest('tr').find('td:eq(1)').attr('data-date',booknetic.convertDate(
                                dateStr,date_format,'Y-m-d'
                            ));
                            textElement.text(dateStr);
                            textElement.show();
                            _this.parent().find('.booknetic_recurring_info_edit_date').hide();
                            _this.prev('.booknetic_data_has_error').remove();
                            booknetic.stepManager.saveData();
                        },
                    } );
                return ;
            }
            var tr		= $(this).closest('tr'),
                timeTd	= tr.children('td[data-time]'),
                time	= timeTd.data('time'),
                date1	= tr.children('td[data-date]').data('date');

            timeTd.children('.booknetic_time_span').html('<select class="form-control booknetic_time_select"></select>').css({'float': 'right', 'margin-right': '25px', 'width': '120px'}).parent('td').css({'padding-top': '7px', 'padding-bottom': '14px'});

            booknetic.select2Ajax( timeTd.find('.booknetic_time_select'), 'get_available_times', function()
            {
                return booknetic.formDataToObject( booknetic.ajaxParameters({date: date1}) );
            });

            $(this).parent().prev().children('.booknetic_data_has_error').remove();
            $(this).remove();

            booknetic.handleScroll();

        }).on('click', '.booknetic_copy_time_to_all', function ()
        {
            var time = $(this).closest('.booknetic_active_day').find('.booknetic_wd_input_time').select2('data')[0];

            if( time )
            {
                var	timeId		= time['id'],
                    timeText	= time['text'];

                booking_panel_js.find(".booknetic_active_day:not(:first)").each(function ()
                {
                    $(this).find(".booknetic_wd_input_time").append( $('<option></option>').val( timeId ).text( timeText ) ).val( timeId ).trigger('change');
                });
            }

        }).on('keyup', '#booknetic_recurring_times', function()
        {
            var serviceData = booknetic.serviceData;

            if( !serviceData )
                return;

            var repeatType	=	serviceData['repeat_type'],
                start		=	booknetic.getSelected.recurringStartDate(),
                times		=	$(this).val();

            if( start == '' || times == '' || times <= 0 )
                return;

            var frequency = (repeatType == 'daily') ? booking_panel_js.find('#booknetic_daily_recurring_frequency').val() : 1;

            if( !( frequency >= 1 ) )
            {
                frequency = 1;
                if( repeatType == 'daily' )
                {
                    booking_panel_js.find('#booknetic_daily_recurring_frequency').val('1');
                }
            }

            var activeDays = {};
            if( repeatType == 'weekly' )
            {
                booking_panel_js.find(".booknetic_times_days_of_week_area > .booknetic_active_day").each(function()
                {
                    activeDays[ $(this).data('day') ] = true;
                });

                if( $.isEmptyObject( activeDays ) )
                {
                    return;
                }
            }
            else if( repeatType == 'monthly' )
            {
                var monthlyRecurringType = booking_panel_js.find("#booknetic_monthly_recurring_type").val();
                var monthlyDayOfWeek = booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").val();

                var selectedDays = booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").select2('val');

                if( monthlyRecurringType == 'specific_day'){
                    monthlyDayOfWeek = '';
                }else {
                    selectedDays = [];
                }

                if( selectedDays )
                {
                    for( var i = 0; i < selectedDays.length; i++ )
                    {
                        activeDays[ selectedDays[i] ] = true;
                    }
                }

                if( monthlyDayOfWeek.length > 0)
                {
                    activeDays[ monthlyDayOfWeek ] = monthlyDayOfWeek;
                }
            }

            var c_times = 0;
            var cursor = booknetic.getDateWithUTC( start );

            while( (!$.isEmptyObject( activeDays ) || repeatType == 'daily') && c_times < times )
            {
                var weekNum = cursor.getDay();
                var dayNumber = parseInt( cursor.getDate() );
                weekNum = weekNum > 0 ? weekNum : 7;
                var dateFormat = cursor.getFullYear() + '-' + booknetic.zeroPad( cursor.getMonth() + 1 ) + '-' + booknetic.zeroPad( cursor.getDate() );

                if( repeatType == 'monthly' )
                {
                    if( ( monthlyRecurringType == 'specific_day' && typeof activeDays[ dayNumber ] != 'undefined' ) || booknetic.getMonthWeekInfo(cursor, monthlyRecurringType, monthlyDayOfWeek) )
                    {
                        if
                        (
                            // if is not off day for staff or service
                            !( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
                            // if is not holiday for staff or service
                            typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
                        )
                        {
                            c_times++;
                        }
                    }
                }
                else if
                (
                    // if weekly repeat type then only selected days of week...
                    ( typeof activeDays[ weekNum ] != 'undefined' || repeatType == 'daily' ) &&
                    // if is not off day for staff or service
                    !( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
                    // if is not holiday for staff or service
                    typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
                )
                {
                    c_times++;
                }

                cursor = new Date( cursor.getTime() + 1000 * 24 * 3600 * frequency );
            }

            cursor = new Date( cursor.getTime() - 1000 * 24 * 3600 * frequency );
            var end = cursor.getFullYear() + '-' + booknetic.zeroPad( cursor.getMonth() + 1 ) + '-' + booknetic.zeroPad( cursor.getDate() );

            if( !isNaN( cursor.getFullYear() ) )
            {
                booking_panel_js.find('#booknetic_recurring_end').val( booknetic.convertDate( end, 'Y-m-d' ) );
            }
        }).on('keyup', '#booknetic_daily_recurring_frequency', booknetic.calcRecurringTimes
        ).on('change', '#booknetic_monthly_recurring_type, #booknetic_monthly_recurring_day_of_week, #booknetic_monthly_recurring_day_of_month', booknetic.calcRecurringTimes
        ).on('change', '#booknetic_monthly_recurring_type', function ()
        {
            if( $(this).val() == 'specific_day' )
            {
                booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").next('.select2').show();
                booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").next('.select2').hide();
            }
            else
            {
                booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").next('.select2').hide();
                booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").next('.select2').show();
            }
        }).on('change', '#booknetic_recurring_start, #booknetic_recurring_end', function ()
        {
            booknetic.calcRecurringTimes();

            const recurringProps = {
                'recurring_start_date' : booknetic.getSelected.recurringStartDate(),
                'recurring_end_date' : booknetic.getSelected.recurringEndDate(),
                'recurring_times' : booknetic.getSelected.recurringTimesArr(),
                'appointments' : booknetic.getSelected.recurringTimesArrFinish(),
            }

            if (recurringProps['recurring_start_date'] === '' || (!$('#booknetic_recurring_end').is(':disabled') && recurringProps['recurring_start_date'] === '')) {
                return;
            }

            const params = booknetic.ajaxParameters(undefined, false);
            const newCartArr = Object.assign({}, booknetic.cartArr);

            newCartArr[ booknetic.cartCurrentIndex ] = {
                ...newCartArr[ booknetic.cartCurrentIndex ], ...recurringProps
            }

            params.set('cart', JSON.stringify(newCartArr) );

            booknetic.ajax('get_day_offs', params , function( result )
            {
                booknetic.globalDayOffs = result['day_offs'];
                booknetic.globalTimesheet = result['timesheet'];

                result['disabled_days_of_week'].forEach(function( value, key )
                {
                    booking_panel_js.find('#booknetic_day_of_week_checkbox_' + (parseInt(key)+1)).attr('disabled', value);
                });

                booknetic.calcRecurringTimes();
            });
        });

    });

    bookneticHooks.addFilter('step_validation_recurring_info' , function ( result , booknetic )
    {
        if( booknetic.getSelected.recurringTimesArrFinish() === false )
        {
            return {
                status: false,
                errorMsg: booknetic.__('select_available_time')
            };
        }

        if( booknetic.getSelected.recurringDateValidate() === false)
        {
            return {
                status: false,
                errorMsg: booknetic.__('select_available_date')
            };
        }

        return result
    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'recurring_info' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

})(jQuery);