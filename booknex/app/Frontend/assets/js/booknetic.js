var bookneticPaymentStatus;

(function($)
{
	"use strict";

	if( typeof window.bookneticInitBookingPage !== 'undefined' )
		return;

	window.bookneticHooks = {

		hooks: {},

		addFilter: function ( key, fn, fn_id )
		{
			key = key.toLowerCase();

			if ( ! this.hooks.hasOwnProperty( key ) )
			{
				this.hooks[ key ] = {};
			}

			if (fn === null && this.hooks[key].hasOwnProperty(fn_id)) {
				delete this.hooks[key][fn_id];
				return 0;
			}

			if (fn_id === undefined || fn_id === null) {
				while(true) {
					fn_id = Math.random().toString(36).substring(2, 15);
					if (!this.hooks[key].hasOwnProperty(fn_id))
						break;
				}
			}

			this.hooks[ key ][ fn_id ] = fn;
			return fn_id;
		},

		doFilter: function ( key, params, ...extra )
		{
			key = key.toLowerCase();

			if (!this.hooks.hasOwnProperty( key ) ) {
				return params;
			}

			for (let fn_id in this.hooks[key]) {
				let fn = this.hooks[key][fn_id];

				if ( typeof params === 'undefined' ) {
					params = fn( ...extra );
				} else {
					params = fn( params, ...extra );
				}
			}

			return params;
		},

		addAction: function ( key, fn, fn_id )
		{
			return this.addFilter( key, fn, fn_id );
		},

		doAction: function ( key, ...params )
		{
			this.doFilter( key, undefined, ...params );
		}
	};

	function __( key )
	{
		return key in BookneticData.localization ? BookneticData.localization[ key ] : key;
	}

	$.fn.handleScrollBooknetic = function()
	{
		if ( !this.hasClass('nice-scrollbar-primary') && ! window.matchMedia('(max-width: 1000px)').matches )
		{
			this.addClass( 'nice-scrollbar-primary' );
		}

		if( window.matchMedia('(max-width: 1000px)').matches && this.hasClass('nice-scrollbar-primary') )
		{
			booking_panel_js.find(".booknetic_appointment_container_body").removeClass('nice-scrollbar-primary')

			if ( $( '#country-listbox' ).length )
			{
				$( '#country-listbox' ).removeClass('nice-scrollbar-primary')
			}

			// return;
		}
	}

	let index = 0;
	window.bookneticInitBookingPage = function ( booking_panel_JS_element )
	{
		index++;
		let booking_panel_js = $(booking_panel_JS_element);

		if( booking_panel_js.data('booknetic_has_been_initiated') === true )
			return;

		booking_panel_js.data('booknetic_has_been_initiated', true);

		let booknetic = {

			google_recaptcha_token: null,
			google_recaptcha_action: 'booknetic_booking_panel_' + index,

			cartArr : [],
			cartHTMLBody : [],
			cartHTMLSideBar : [],
			cartCurrentIndex:0,
			cartErrors : {
				a:[],
				callbacks: [(arr)=>{
					if( arr.length > 0 )
					{
						let itemIds = [];

						arr.forEach((value)=>{
							if( itemIds.indexOf(value['cart_item']) === -1)
								itemIds.push(value['cart_item']);
						});


						booking_panel_js.find('.booknetic-cart-item-error .booknetic-cart-item-error-body').remove();
						booking_panel_js.find('.booknetic-cart-item-error').removeClass('show');

						arr.forEach((value)=>{
							if(value['cart_item']!==undefined)
							{
								booking_panel_js.find('div.booknetic-cart div[data-index='+ value['cart_item'] +'] .booknetic-cart-item-error').addClass('show');
								booking_panel_js.find('div.booknetic-cart div[data-index='+ value['cart_item'] +'] .booknetic-cart-item-error').append(`
										<div class="booknetic-cart-item-error-body">${value['message']}</div>
									`);
							}
						})


					}
					else
					{
						booking_panel_js.find('.booknetic-cart-item-error .booknetic-cart-item-error-body').remove();
						booking_panel_js.find('.booknetic-cart-item-error').removeClass('show');
					}
				}],
				get error()
				{
					return this.a;
				},
				set error(arr)
				{
					this.a = arr;
					for (let i = 0; i < this.callbacks.length; i++) {
						this.callbacks[i](arr );
					}
				}
			},
			__,

			panel_js : booking_panel_js,

			options: {
				'templates': {
					'loader': '<div class="booknetic_loading_layout"></div>'
				}
			},

			localization: {
				month_names: [ __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December') ],
				day_of_week: [ __('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun') ] ,
			},

			calendarDateTimes: {},
			time_show_format: 1,
			calendarYear: null,
			calendarMonth: null,

			paymentWindow: null,
			paymentStatus: null,
			appointmentId: null, // doit: bu failed payment olan appointmenti silmek ucundu, bunu payment_id ederik
			ajaxResultConfirmStep: null,
			paymentId: null,
			dateBasedService: false,
			serviceData: null,

			globalDayOffs: {},
			globalTimesheet: {},


			loading: function ( onOff )
			{
				if( typeof onOff === 'undefined' || onOff )
				{
					booking_panel_js.find('#booknetic_progress').removeClass('booknetic_progress_done').show();
					$({property: 0}).animate({property: 100}, {
						duration: 1000,
						step: function()
						{
							var _percent = Math.round(this.property);
							if( !booking_panel_js.find('#booknetic_progress').hasClass('booknetic_progress_done') )
							{
								booking_panel_js.find('#booknetic_progress').css('width',  _percent+"%");
							}
						}
					});

					booking_panel_js.append( this.options.templates.loader );
				}
				else if( ! booking_panel_js.find('#booknetic_progress').hasClass('booknetic_progress_done') )
				{
					booking_panel_js.find('#booknetic_progress').addClass('booknetic_progress_done').css('width', 0);

					// IOS bug...
					setTimeout(function ()
					{
						booking_panel_js.find('.booknetic_loading_layout').remove();
					}, 0);
				}
			},

			htmlspecialchars_decode: function (string, quote_style)
			{
				var optTemp = 0,
					i = 0,
					noquotes = false;
				if(typeof quote_style==='undefined')
				{
					quote_style = 2;
				}
				string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
				var OPTS = {
					'ENT_NOQUOTES': 0,
					'ENT_HTML_QUOTE_SINGLE': 1,
					'ENT_HTML_QUOTE_DOUBLE': 2,
					'ENT_COMPAT': 2,
					'ENT_QUOTES': 3,
					'ENT_IGNORE': 4
				};
				if(quote_style===0)
				{
					noquotes = true;
				}
				if(typeof quote_style !== 'number')
				{
					quote_style = [].concat(quote_style);
					for (i = 0; i < quote_style.length; i++){
						if(OPTS[quote_style[i]]===0){
							noquotes = true;
						} else if(OPTS[quote_style[i]]){
							optTemp = optTemp | OPTS[quote_style[i]];
						}
					}
					quote_style = optTemp;
				}
				if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
				{
					string = string.replace(/&#0*39;/g, "'");
				}
				if(!noquotes){
					string = string.replace(/&quot;/g, '"');
				}
				string = string.replace(/&amp;/g, '&');
				return string;
			},

			htmlspecialchars: function ( string, quote_style, charset, double_encode )
			{
				var optTemp = 0,
					i = 0,
					noquotes = false;
				if(typeof quote_style==='undefined' || quote_style===null)
				{
					quote_style = 2;
				}
				string = typeof string != 'string' ? '' : string;

				string = string.toString();
				if(double_encode !== false){
					string = string.replace(/&/g, '&amp;');
				}
				string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
				var OPTS = {
					'ENT_NOQUOTES': 0,
					'ENT_HTML_QUOTE_SINGLE': 1,
					'ENT_HTML_QUOTE_DOUBLE': 2,
					'ENT_COMPAT': 2,
					'ENT_QUOTES': 3,
					'ENT_IGNORE': 4
				};
				if(quote_style===0)
				{
					noquotes = true;
				}
				if(typeof quote_style !== 'number')
				{
					quote_style = [].concat(quote_style);
					for (i = 0; i < quote_style.length; i++)
					{
						if(OPTS[quote_style[i]]===0)
						{
							noquotes = true;
						}
						else if(OPTS[quote_style[i]])
						{
							optTemp = optTemp | OPTS[quote_style[i]];
						}
					}
					quote_style = optTemp;
				}
				if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
				{
					string = string.replace(/'/g, '&#039;');
				}
				if(!noquotes)
				{
					string = string.replace(/"/g, '&quot;');
				}
				return string;
			},

			sanitizeHTML: function (node){
				node = node.replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gi, '$1');
				node = node.replace(/&lt;script\b[^&gt;]*&gt;([\s\S]*?)&lt;\/script&gt;/gi, '$1');
				return node;
			},

			throttle: function(func, wait = 500) {
				let processing = false;

				return function (e) {
					if (processing) return;

					processing = true;

					func.call(this, e);

					setTimeout(() => {
						processing = false;
					}, wait);
				}
			},

			formDataToObject: function ( formData )
			{
				var object = {};

				formData.forEach(function(value, key)
				{
					object[key] = value;
				});

				return object;
			},

			ajaxResultCheck: function ( res )
			{

				if( typeof res != 'object' )
				{
					try
					{
						res = JSON.parse(res);
					}
					catch(e)
					{
						this.toast( 'Error!' );
						return false;
					}
				}

				if( typeof res['status'] == 'undefined' )
				{
					this.toast( 'Error!' );
					return false;
				}

				if( res['status'] === 'error' )
				{
					if( typeof res['errors'] != 'undefined' && res['errors'].length > 0)
					{
						return false;
					}
					this.toast( typeof res['error_msg'] == 'undefined' ? 'Error!' : res['error_msg'] );
					return false;
				}

				if( res['status'] === 'ok' )
					return true;

				// else

				this.toast( 'Error!' );
				return false;
			},

			ajax: function ( action , params , func , loading, fnOnError, async_param )
			{
				// helper: best-effort JSON parse
				const tryParse = (x) => { try { return JSON.parse(x); } catch { return x; } };

				// backward-compatible defaults
				params      = typeof params      === 'undefined' ? {}            : params;
				func        = typeof func        === 'function'   ? func          : function(){};
				loading     = loading !== false;
				fnOnError   = typeof fnOnError   === 'function'   ? fnOnError     : function(){};
				async_param = typeof async_param === 'undefined' ? true          : async_param;

				const endLoader = () => { if ( loading ) booknetic.loading( 0 ); };

				/* -------------------------------------------------------------
				   Resolve backend / frontend identifiers
				------------------------------------------------------------- */
				const { backend, frontend } = ( action instanceof Object )
					? { backend: action[ 'backend_action' ], frontend: action[ 'frontend_action' ] }
					: { backend: action, frontend: action };

				/* -------------------------------------------------------------
				   Prepare payload
				------------------------------------------------------------- */
				const tenantId   = BookneticData.tenant_id || booking_panel_js.data( 'tenant_id' );
				const isFormData = params instanceof FormData;

				if ( isFormData )
				{
					params.append( 'action',    'bkntc_' + backend );
					params.append( 'tenant_id', tenantId );
				}
				else
				{
					params = { action: 'bkntc_' + backend, tenant_id: tenantId, ...params };
				}

				/* -------------------------------------------------------------
				   Hooks (before) and loader
				------------------------------------------------------------- */
				bookneticHooks.doAction( 'ajax_before_' + frontend, params, booknetic );
				params = bookneticHooks.doFilter( 'ajax', params, booknetic );
				params = bookneticHooks.doFilter( 'ajax_' + frontend, params, booknetic );

				if ( loading )
				{
					booknetic.loading( true );
				}

				/* -------------------------------------------------------------
				   Execute request
				------------------------------------------------------------- */
				$.ajax( {
					url:    BookneticData.ajax_url,
					method: 'POST',
					data:   params,
					async:  async_param,
					...( isFormData ? { processData: false, contentType: false } : {} )
				} ).done( function ( rawResult )
				{
					endLoader();

					const result = tryParse( rawResult );
					const ok     = booknetic.ajaxResultCheck( result, fnOnError );

					if ( ok )
					{
						func( result );
						bookneticHooks.doAction( 'ajax_after_' + frontend + '_success', booknetic, params, result );
					}
					else
					{
						fnOnError( result );
						bookneticHooks.doAction( 'ajax_after_' + frontend + '_error', booknetic, params, result );
					}
				} ).fail( function ( jqXHR )
				{
					endLoader();
					booknetic.toast( jqXHR.status + ' error!' );
					fnOnError();
					bookneticHooks.doAction( 'ajax_after_' + frontend + '_error', booknetic, params );
				} );
			},

			ajaxAsync: function ( action , params , loading, async_param )
			{
				return new Promise( function ( resolve, reject )
				{
					booknetic.ajax(
						action,
						params,
						function( result )
						{
							resolve( result );
						},
						loading,
						function ( error )
						{
							reject( error );
						},
						async_param
					);
				});
			},

			select2Ajax: function ( select, action, parameters )
			{
				var params = {};
				params['action'] = 'bkntc_' + action;
				params['tenant_id'] = BookneticData.tenant_id || booking_panel_js.data('tenant_id');

				select.select2({
					theme: 'bootstrap',
					placeholder: __('select'),
					language: {
						searching: function() {
							return __('searching');
						}
					},
					allowClear: true,
					ajax: {
						url: BookneticData.ajax_url,
						dataType: 'json',
						type: "POST",
						data: function ( q )
						{
							var sendParams = params;
							sendParams['q'] = q['term'];

							if( typeof parameters == 'function' )
							{
								var additionalParameters = parameters( $(this) );

								for (let key in additionalParameters)
								{
									sendParams[key] = additionalParameters[key];
								}
							}
							else if( typeof parameters == 'object' )
							{
								for (let key in parameters)
								{
									sendParams[key] = parameters[key];
								}
							}

							return sendParams;
						},
						processResults: function ( result )
						{
							if( booknetic.ajaxResultCheck( result ) )
							{
								try
								{
									result = JSON.parse(result);
								}
								catch(e)
								{

								}

								return result;
							}
						}
					}
				});
			},

			zeroPad: function(n, p)
			{
				p = p > 0 ? p : 2;

				n = String(n);
				while (n.length < p)
					n = '0' + n;

				return n;
			},

			toast: function( title )
			{
				if( title === false )
				{
					booking_panel_js.find('.booknetic_warning_message').fadeOut(200);
					return;
				}

				booking_panel_js.find('.booknetic_warning_message').text( booknetic.htmlspecialchars_decode( title, 'ENT_QUOTES' ) ).fadeIn(300);
				setTimeout(function ()
				{
					booking_panel_js.find('.booknetic_warning_message').fadeOut(200);
				}, 5000);
			},

			nonRecurringCalendar: function ( year , month, load_dates_from_backend, load_calendar )
			{
				load_calendar = !!load_calendar;

				const now = new Date();

				year  = year ?? now.getFullYear();
				month = month ?? now.getMonth();

				booknetic.loadDefaultDate( year, month, load_calendar );

				if ( load_dates_from_backend )
					booknetic.loadDateFromBackend( year, month, load_calendar );
			},

			loadDefaultDate: function( year, month, load )
			{
				booknetic.calendarYear = year;
				booknetic.calendarMonth = month;

				booknetic.displayCalendar( load );
				booknetic.displayBringPeopleSelect();
			},

			loadDateFromBackend: function( year, month, load )
			{
				booknetic.ajax( 'get_data', booknetic.ajaxParameters( {
					current_step: 'date_time',
					year:   year,
					month:  month + 1,
					info: booking_panel_js.data( 'info' )
				} ), function ( result )
				{
					booknetic.calendarDateTimes = result[ 'data' ];
					booknetic.time_show_format = result[ 'time_show_format' ];

					booknetic.calendarYear = result[ 'calendar_start_year' ];
					booknetic.calendarMonth = result[ 'calendar_start_month' ] - 1;

					booknetic.displayCalendar( load );
					booknetic.displayBringPeopleSelect();

					booknetic.addGroupAppointmentsCounterForBookneticCalendarDays();
				} , load );
			},

			displayBringPeopleSelect: function()
			{
				var select = booking_panel_js.find('.booknetic_number_of_brought_customers select');

				var options = '';

				for(var i = 1; i < booknetic.serviceMaxCapacity; i++ )
				{
					options += '<option value="' + i + '"> + ' + i + '</option>'
				}

				select.html( options );

			},

			displayCalendar: function( loader )
			{
				var _year = booknetic.calendarYear;
				var _month = booknetic.calendarMonth;

				var htmlContent		= "",
					febNumberOfDays	= "",
					counter			= 1,
					dateNow			= new Date(_year , _month ),
					month			= dateNow.getMonth()+1,
					year			= dateNow.getFullYear(),
					currentDate		= new Date();

				if (month == 2)
				{
					febNumberOfDays = ( (year%100!=0) && (year%4==0) || (year%400==0)) ? '29' : '28';
				}

				var monthNames	= booknetic.localization.month_names;
				var dayPerMonth	= [null, '31', febNumberOfDays ,'31','30','31','30','31','31','30','31','30','31']

				var nextDate	= new Date(month +'/01/'+year);
				var weekdays	= nextDate.getDay();

				let weekdays2;
				let week_start_n;
				let week_end_n;

				if( BookneticData.week_starts_on === 'monday' )
				{
					weekdays2	= weekdays == 0 ? 7 : weekdays;
					week_start_n = 1;
					week_end_n = 7;
				}
				else
				{
					weekdays2	= weekdays;
					week_start_n = 0;
					week_end_n = 6;
				}

				var numOfDays	= dayPerMonth[month];

				for( let w = week_start_n; w < weekdays2; w++ )
				{
					htmlContent += "<div class=\"booknetic_td booknetic_empty_day\"></div>";
				}

				while (counter <= numOfDays)
				{
					if (weekdays2 > week_end_n)
					{
						weekdays2 = week_start_n;
						htmlContent += "</div><div class=\"booknetic_calendar_rows\">";
					}
					var date_formatted = year + '-' + booknetic.zeroPad(month) + '-' + booknetic.zeroPad(counter);
					let date_format_view;

					if( BookneticData.date_format === 'Y-m-d' )
					{
						date_format_view = year + '-' + booknetic.zeroPad(month) + '-' + booknetic.zeroPad(counter);
					}
					else if( BookneticData.date_format === 'd-m-Y' )
					{
						date_format_view = booknetic.zeroPad(counter) + '-' + booknetic.zeroPad(month) + '-' + year;
					}
					else if( BookneticData.date_format === 'm/d/Y' )
					{
						date_format_view = booknetic.zeroPad(month) + '/' + booknetic.zeroPad(counter) + '/' + year;
					}
					else if( BookneticData.date_format === 'd/m/Y' )
					{
						date_format_view = booknetic.zeroPad(counter) + '/' + booknetic.zeroPad(month) + '/' + year;
					}
					else if( BookneticData.date_format === 'd.m.Y' )
					{
						date_format_view = booknetic.zeroPad(counter) + '.' + booknetic.zeroPad(month) + '.' + year;
					}

					var addClass = '';
					if( !(date_formatted in booknetic.calendarDateTimes['dates']) || booknetic.calendarDateTimes['dates'][ date_formatted ].length == 0 )
					{
						addClass = ' booknetic_calendar_empty_day';
					}

					var loadLine = booknetic.drawLoadLine( date_formatted );

					htmlContent +="<div class=\"booknetic_td booknetic_calendar_days"+addClass+"\" data-date=\"" + date_formatted + "\" data-date-format=\"" + date_format_view + "\"><div>"+counter+"<span>" + loadLine + "</span></div></div>";

					weekdays2++;
					counter++;
				}

				for( let w = weekdays2; w <= week_end_n; w++ )
				{
					htmlContent += "<div class=\"booknetic_td booknetic_empty_day\"></div>";
				}

				var calendarBody = "<div class=\"booknetic_calendar\">";

				calendarBody += "<div class=\"booknetic_calendar_rows booknetic_week_names\">";

				for( var w = 0; w < booknetic.localization.day_of_week.length; w++ )
				{
					if( w > week_end_n || w < week_start_n )
						continue;

					calendarBody += "<div class=\"booknetic_td\">" + booknetic.localization.day_of_week[ w ] + "</div>";
				}

				calendarBody += "</div>";

				calendarBody += "<div class=\"booknetic_calendar_rows\">";
				calendarBody += htmlContent;
				calendarBody += "</div></div>";

				booking_panel_js.find("#booknetic_calendar_area").html( calendarBody );

				booking_panel_js.find("#booknetic_calendar_area .days[data-count]:first").trigger('click');

				booking_panel_js.find(".booknetic_month_name").text( monthNames[ _month ] + ' ' + _year );
				booking_panel_js.find('.booknetic_times_list').empty();
				booking_panel_js.find('.booknetic_times_title').text(__('Select date'));

				if( !loader )
				{
					booking_panel_js.find(".booknetic_preloader_card3_box").hide();

					booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="date_time"]').fadeIn(200, function()
					{
						booking_panel_js.find(".booknetic_appointment_container_body").scrollTop(0);
						booknetic.handleScroll();
					});
				}
			},

			drawLoadLine: function( date )
			{
				let zoom = function (input, outputSize) {
					if (input.length === outputSize) {
						return input;
					}
					const ratio = outputSize / input.length;

					const output = new Array(outputSize);

					for (let i = 0; i < outputSize; i++) {
						let value = false;

						const from = i / ratio;
						const inc = Math.max(1, 1 / ratio);

						for (let j = Math.floor(from); j < Math.floor(from + inc); j++) {
							value = value || input[j];
						}

						output[i] = value;
					}

					return output;
				};

				var fills = date in booknetic.calendarDateTimes['fills'] ? booknetic.calendarDateTimes['fills'][ date ] : [0];
				var data = date in booknetic.calendarDateTimes['dates'] ? booknetic.calendarDateTimes['dates'][ date ] : [];
				if (data.length === 1 && booknetic.dateBasedService && !( 'hide_available_slots' in booknetic.calendarDateTimes && booknetic.calendarDateTimes['hide_available_slots'] === 'on' )) {
					fills = [];
					for (let i = 0; i < data[0].max_capacity; i++) {
						fills.push(data[0].max_capacity - data[0].weight - i > 0 ? 1 : 0);
					}
				}

				var day_schedule = zoom(fills, 17);

				var line = '';
				for( var j = 0; j < day_schedule.length; j++ )
				{
					var isFree = day_schedule[j];
					line += '<i '+ (isFree?'a':'b') + '></i>';
				}

				return line;
			},

			timeToMin: function(str)
			{
				str = str.split(':');

				return parseInt(str[0]) * 60 + parseInt(str[1]);
			},

			timeZoneOffset: function()
			{
				if( BookneticData.client_time_zone === 'off' )
					return  '-';

				if ( window.Intl && typeof window.Intl === 'object' )
				{
					return Intl.DateTimeFormat().resolvedOptions().timeZone;
				}
				else
				{
					return new Date().getTimezoneOffset();
				}
			},

			datePickerFormat: function()
			{
				if( BookneticData.date_format === 'd-m-Y' )
				{
					return 'dd-mm-yyyy';
				}
				else if( BookneticData.date_format === 'm/d/Y' )
				{
					return 'mm/dd/yyyy';
				}
				else if( BookneticData.date_format === 'd/m/Y' )
				{
					return 'dd/mm/yyyy';
				}
				else if( BookneticData.date_format === 'd.m.Y' )
				{
					return 'dd.mm.yyyy';
				}

				return 'yyyy-mm-dd';
			},

			convertDate: function( date, from, to )
			{
				if( date == '' )
					return date;
				if( typeof to === 'undefined' )
				{
					to = booknetic.datePickerFormat();
				}

				to = to.replace('yyyy', 'Y').replace('dd', 'd').replace('mm', 'm');
				from = from.replace('yyyy', 'Y').replace('dd', 'd').replace('mm', 'm');

				var delimetr = from.indexOf('-') > -1 ? '-' : ( from.indexOf('.') > -1 ? '.' : '/' );
				var delimetr_to = to.indexOf('-') > -1 ? '-' : ( to.indexOf('.') > -1 ? '.' : '/' );
				var date_split = date.split(delimetr);
				var date_from_split = from.split(delimetr);
				var date_to_split = to.split(delimetr_to);

				var parts = {'m':0, 'd':0, 'Y':0};

				date_from_split.forEach(function( val, i )
				{
					parts[ val ] = i;
				});

				var new_date = '';
				date_to_split.forEach(function( val, j )
				{
					new_date += (new_date == '' ? '' : delimetr_to) + date_split[ parts[ val ] ];
				});

				return new_date;
			},

			getSelected: {

				location: function()
				{
					if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="location"]').hasClass('booknetic_menu_hidden') )
					{
						var val = booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="location"]').data('value');
					}
					else
					{
						val = booking_panel_js.find(".booknetic_appointment_container_body [data-step-id=\"location\"] .booknetic_card_selected").data('id');
					}

					return val ? val : '';
				},

				staff: function()
				{
					if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="staff"]').hasClass('booknetic_menu_hidden') )
					{
						var val = booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="staff"]').data('value');
					}
					else
					{
						val = booking_panel_js.find(".booknetic_appointment_container_body [data-step-id=\"staff\"] .booknetic_card_selected").data('id');
					}

					return val ? val : '';
				},

				service: function()
				{
					const serviceId = booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').data('value');

					if( serviceId )
					{
						var val = serviceId;
					}
					else
					{
						val = booking_panel_js.find(".booknetic_appointment_container_body [data-step-id=\"service\"] .booknetic_service_card_selected").data('id');
					}

					return val ? val : '';
				},

				serviceCategory: function()
				{
					return booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').data('service-category');
				},

				serviceIsRecurring: function()
				{
					let val;

					if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="service"]').hasClass('booknetic_menu_hidden') )
					{
						val = booking_panel_js.find( '.booknetic_appointment_step_element[data-step-id="service"]' ).data( 'is-recurring' );
					}
					else
					{
						val = booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="service"] .booknetic_service_card_selected').data('is-recurring');
					}

					return val == '1';
				},

				serviceExtras: function()
				{
					var extras = [];

					booking_panel_js.find(".booknetic_appointment_container_body [data-step-id=\"service_extras\"]  .booknetic_service_extra_card_selected").each(function()
					{
						var extra_id	 = $(this).data('id'),
							quantity    = parseInt( $(this).find('.booknetic_service_extra_quantity_input').val() );

						if( quantity > 0  )
						{
							extras.push({
								extra: extra_id,
								quantity: quantity,
							});
						}
					});

					return extras;
				},

				date: function()
				{
					if( booknetic.getSelected.serviceIsRecurring() )
						return '';

					var val = booking_panel_js.find(".booknetic_selected_time").data('date-original');
					return val ? val : '';
				},

				date_in_customer_timezone: function()
				{
					if( booknetic.getSelected.serviceIsRecurring() )
						return '';

					var val = booking_panel_js.find(".booknetic_calendar_selected_day").data('date');
					return val ? val : '';
				},

				time: function()
				{
					if( booknetic.getSelected.serviceIsRecurring() )
						return booknetic.getSelected.recurringTime();

					var val = booking_panel_js.find(".booknetic_selected_time").data('time');
					return val ? val : '';
				},

				brought_people_count: function()
				{
					if( ! booking_panel_js.find('#booknetic_bring_someone_checkbox ').is(':checked') )
						return 0;

					let broughtPeopleInput = booking_panel_js.find('.booknetic_number_of_brought_customers_quantity_input');
					let val = Number( broughtPeopleInput.val() );
					let max = Number( broughtPeopleInput.data( 'max-quantity' ) );

					val = Number.isInteger( val ) ? val : 0;

					return val > max ? max : val;
				},

				dateTime: function()
				{
					if( booknetic.getSelected.serviceIsRecurring() )
						return booknetic.getSelected.recurringTime();

					var val = booking_panel_js.find(".booknetic_selected_time").data('full-date-time-start');
					return val ? val : '';
				},

				formData: function ()
				{
					var data = { data: {email: "", first_name: "", last_name: "", phone: ""} };

					booking_panel_js.find('input[name]#bkntc_input_name, input[name]#bkntc_input_surname, input[name]#bkntc_input_email, input[name]#bkntc_input_phone').each(function()
					{
						var name	= $(this).attr('name'),
							value	= name == 'phone' && typeof intlTelInputUtils != 'undefined' ? $(this).data('iti').getNumber(intlTelInputUtils.numberFormat.E164) : $(this).val();

						if ( name === 'email' )
							value = value.trim()

						data['data'][name] = value;
					});

					return data;
				},

				customerId: function ()
				{
					let customerId = booking_panel_js.find('.bkntc_input_identifier_input').data('customer-id');

					return customerId || 0;
				},

				paymentMethod: function ()
				{
					if( booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="confirm_details"]').hasClass('booknetic_menu_hidden') )
						return 'local';

					return booking_panel_js.find('.booknetic_payment_method.booknetic_payment_method_selected').attr('data-payment-type');
				},

				paymentDepositFullAmount: function ()
				{
					return booking_panel_js.find('input[name="input_deposit"][type="radio"]:checked').val() == '0' ? true : false;
				},

				recurringStartDate: function()
				{
					var val = booking_panel_js.find("#booknetic_recurring_start").val();

					if( val == '' || val == undefined )
						return '';

					return booknetic.convertDate( val, booknetic.datePickerFormat(), 'Y-m-d' );
				},

				recurringEndDate: function()
				{
					var val = booking_panel_js.find("#booknetic_recurring_end").val();

					if( val == '' || val == undefined )
						return '';

					return booknetic.convertDate( val, booknetic.datePickerFormat(), 'Y-m-d' );
				},

				recurringTimesArr: function()
				{
					if( !booknetic.serviceData )
						return JSON.stringify( {} );

					var repeatType		=	booknetic.serviceData['repeat_type'],
						recurringTimes	=	{};

					if( repeatType == 'weekly' )
					{
						booking_panel_js.find(".booknetic_times_days_of_week_area > .booknetic_active_day").each(function()
						{
							var dayNum = $(this).data('day');
							var time = $(this).find('.booknetic_wd_input_time').val();

							recurringTimes[ dayNum ] = time;
						});

						recurringTimes = JSON.stringify( recurringTimes );
					}
					else if( repeatType == 'daily' )
					{
						recurringTimes = booking_panel_js.find("#booknetic_daily_recurring_frequency").val();
					}
					else if( repeatType == 'monthly' )
					{
						recurringTimes = booking_panel_js.find("#booknetic_monthly_recurring_type").val();
						if( recurringTimes == 'specific_day' )
						{
							recurringTimes += ':' + ( booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").val() == null ? '' : booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").val().join(',') );
						}
						else
						{
							recurringTimes += ':' + booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").val();
						}
					}

					return recurringTimes;
				},

				recurringTimesArrFinish: function()
				{
					var recurringDates = [];
					var hasTimeError = false;

					booking_panel_js.find("#booknetic_recurring_dates > tr").each(function()
					{
						var sDate = $(this).find('[data-date]').attr('data-date');
						var sTime = $(this).find('[data-time]').attr('data-time');
						if($(this).find('[data-service-type]').attr('data-service-type') === 'datebased')
						{
							sTime = '00:00';
						}
						// if tried to change the time
						if( $(this).find('.booknetic_time_select').length )
						{
							sTime = $(this).find('.booknetic_time_select').val();
							if( sTime == '' )
							{
								hasTimeError = true;
							}
						}
						else if( $(this).find('.booknetic_data_has_error').length > 0 )
						{
							hasTimeError = ! booknetic.dateBasedService;
						}

						recurringDates.push([ sDate, sTime ]);
					});

					if( hasTimeError )
					{
						return false;
					}

					return JSON.stringify( recurringDates );
				},

				recurringDateValidate: function()
				{
					let dateError = true;
					booking_panel_js.find("#booknetic_recurring_dates > tr").each(function()
					{
						if( $(this).find('td[data-date] span.booknetic_data_has_error').length > 0 )
						{
							dateError =  false;
						}
					});
					return dateError;
				},

				recurringTime: function ()
				{
					if( !booknetic.serviceData )
						return  '';

					var repeatType	=	booknetic.serviceData['repeat_type'],
						time		=	'';

					if( repeatType == 'daily' )
					{
						time = booking_panel_js.find("#booknetic_daily_time").val();
					}
					else if( repeatType == 'monthly' )
					{
						time = booking_panel_js.find("#booknetic_monthly_time").val();
					}

					return time;
				}

			},

			ajaxParameters: function ( defaultData = undefined , bool = true )
			{
				var data = new FormData();

				data.append( 'payment_method', booknetic.getSelected.paymentMethod() );
				data.append( 'deposit_full_amount', booknetic.getSelected.paymentDepositFullAmount() ? 1 : 0 );
				data.append( 'client_time_zone', booknetic.timeZoneOffset() );

				data.append( 'google_recaptcha_token', booknetic.google_recaptcha_token );
				data.append( 'google_recaptcha_action', booknetic.google_recaptcha_action );

				if( typeof defaultData != 'undefined' )
				{
					for ( var key in defaultData )
					{
						data.append( key, defaultData[key] );
					}
				}

				if ( bool )
				{
					this.stepManager.saveData();
				}

				data.append( 'cart', JSON.stringify(booknetic.cartArr) );
				data.append( 'current', booknetic.cartCurrentIndex );
				data.append( 'query_params', booknetic.getURLQueryParams() );

				return bookneticHooks.doFilter( 'appointment_ajax_data', data, booknetic );
			},

			getURLQueryParams: function()
			{
				const queryString = window.location.search;
				const searchParams = new URLSearchParams( queryString );

				let query_params = {};

				searchParams.forEach( ( value, key ) => {
					query_params[ key ] = value;
				} );

				return JSON.stringify( query_params );
			},

			calcRecurringTimes: function()
			{
				booknetic.serviceFixPeriodEndDate();

				var fullPeriod			=	booknetic.serviceData['full_period_value'];
				var repeatType			=	booknetic.serviceData['repeat_type'];
				var startDate			=	booknetic.getSelected.recurringStartDate();
				var endDate				=	booknetic.getSelected.recurringEndDate();

				if( startDate == '' || endDate == '' )
					return;

				endDate		= booknetic.getDateWithUTC( endDate );

				var cursor	= booknetic.getDateWithUTC( startDate ),
					numberOfAppointments = 0,
					frequency = ( repeatType === 'daily' ) ? booking_panel_js.find( '#booknetic_daily_recurring_frequency' ).val() : 1;

				if( !( frequency >= 1 ) )
				{
					frequency = 1;
					if( repeatType === 'daily' )
					{
						booking_panel_js.find('#booknetic_daily_recurring_frequency').val('1');
					}
				}

				var activeDays = {};
				if( repeatType === 'weekly' )
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
				else if( repeatType === 'monthly' )
				{
					var monthlyRecurringType = booking_panel_js.find("#booknetic_monthly_recurring_type").val();
					var monthlyDayOfWeek = booking_panel_js.find("#booknetic_monthly_recurring_day_of_week").val();

					var selectedDays = booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").select2('val');

					if( selectedDays )
					{
						for( var i = 0; i < selectedDays.length; i++ )
						{
							activeDays[ selectedDays[i] ] = true;
						}
					}
				}

				while( cursor <= endDate )
				{
					var weekNum = cursor.getDay();
					//todo://why did we use parseInt here and on many other places? busy with other tasks rn
					// test it out in the future.
					var dayNumber = parseInt( cursor.getDate() );
					weekNum = weekNum > 0 ? weekNum : 7;
					var dateFormat = cursor.getFullYear() + '-' + booknetic.zeroPad( cursor.getMonth() + 1 ) + '-' + booknetic.zeroPad( cursor.getDate() );

					if( repeatType === 'monthly' )
					{
						if( ( monthlyRecurringType === 'specific_day' && typeof activeDays[ dayNumber ] != 'undefined' ) || booknetic.getMonthWeekInfo( cursor, monthlyRecurringType, monthlyDayOfWeek ) )
						{
							if(
								// if is not off day for staff or service
								!( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
								// if is not holiday for staff or service
								typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
							)
							{
								numberOfAppointments++;
							}
						}
					}
					else if(
						// if weekly repeat type then only selected days of the week...
						( typeof activeDays[ weekNum ] != 'undefined' || repeatType === 'daily' ) &&
						// if is not off day for staff or service
						!( typeof booknetic.globalTimesheet[ weekNum-1 ] != 'undefined' && booknetic.globalTimesheet[ weekNum-1 ]['day_off'] ) &&
						// if is not holiday for staff or service
						typeof booknetic.globalDayOffs[ dateFormat ] == 'undefined'
					)
					{
						numberOfAppointments++;
					}

					cursor = new Date( cursor.getTime() + 1000 * 24 * 3600 * frequency );
				}

				booking_panel_js.find('#booknetic_recurring_times').val( numberOfAppointments );

			},

			initRecurringElements: function( )
			{
				booknetic.select2Ajax( booking_panel_js.find(".booknetic_wd_input_time, #booknetic_daily_time, #booknetic_monthly_time"), 'get_available_times_all', function( select )
				{
					var dayNumber = ( select.attr( 'id' ) === 'booknetic_daily_time' || select.attr( 'id' ) === 'booknetic_monthly_time' ) ? -1 : select.attr( 'id' ).replace( 'booknetic_time_wd_', '' );

					return booknetic.formDataToObject( booknetic.ajaxParameters({day_number: dayNumber}) );
				});

				booking_panel_js.find("#booknetic_monthly_recurring_day_of_month").select2({
					theme: 'bootstrap',
					placeholder: __('select'),
					allowClear: true,
					maximumSelectionLength: booknetic.serviceData[ 'repeat_frequency' ],
					closeOnSelect: false,
				}).on( 'select2:select', function( e )
				{
					// https://github.com/select2/select2/issues/3514

					if (
						$( this ).select2( "data" ).length >=
						$( this ).data( "select2" ).results.data.maximumSelectionLength
					)
					{
						$( this ).select2( "close" );
					}
				});

				booking_panel_js.find("#booknetic_monthly_recurring_type, #booknetic_monthly_recurring_day_of_week").select2({
					theme: 'bootstrap',
					placeholder: __('select'),
					minimumResultsForSearch: -1
				});

				booking_panel_js.find('#booknetic_monthly_recurring_type').trigger('change');

				booknetic.initDatepicker( booking_panel_js.find("#booknetic_recurring_start") );
				booknetic.initDatepicker( booking_panel_js.find("#booknetic_recurring_end") );

				booknetic.serviceFixPeriodEndDate();
				booknetic.serviceFixFrequency();
				booking_panel_js.find("#booknetic_recurring_start").trigger('change');
			},

			loadAvailableDate: function(instance ,data)
			{
				booknetic.ajax( 'get_recurring_available_dates', data, function ( result )
				{
					instance.set('enable',result['available_dates']);
				});
			},

			serviceFixPeriodEndDate: function()
			{
				let startDate, endDate;
				let serviceData = booknetic.serviceData;

				if( serviceData && serviceData['full_period_value'] > 0 )
				{
					booking_panel_js.find("#booknetic_recurring_end").attr('disabled', true);
					booking_panel_js.find("#booknetic_recurring_times").attr('disabled', true);

					startDate = booknetic.getSelected.recurringStartDate();

					if( serviceData[ 'full_period_type' ] === 'month' )
					{
						endDate = new Date( startDate + "T00:00:00" );
						endDate.setMonth( endDate.getMonth() + parseInt( serviceData['full_period_value'] ) );
						endDate.setDate( endDate.getDate() - 1 );

						booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
					}
					else if( serviceData[ 'full_period_type' ] === 'week' )
					{

						endDate = new Date( startDate + "T00:00:00" );
						endDate.setDate( endDate.getDate() + parseInt( serviceData['full_period_value'] ) * 7 - 1 );

						booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
					}
					else if( serviceData[ 'full_period_type' ] === 'day' )
					{
						endDate = new Date( startDate + "T00:00:00" );
						endDate.setDate( endDate.getDate() + parseInt( serviceData['full_period_value'] ) - 1 );

						booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
					}
					else if( serviceData[ 'full_period_type' ] === 'time' )
					{
						if( booknetic.getSelected.recurringEndDate() == '' )
						{
							startDate = new Date( booknetic.getSelected.recurringStartDate() );
							endDate = new Date( startDate.setMonth( startDate.getMonth() + 1 ) );

							booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
						}

						booking_panel_js.find("#booknetic_recurring_times").val( serviceData['full_period_value'] ).trigger('keyup');
					}
				}
				else
				{
					booking_panel_js.find("#booknetic_recurring_end").attr('disabled', false);
					booking_panel_js.find("#booknetic_recurring_times").attr('disabled', false);

					if( booknetic.getSelected.recurringEndDate() == '' )
					{
						startDate = new Date( booknetic.getSelected.recurringStartDate() );
						endDate = new Date( startDate.setMonth( startDate.getMonth() + 1 ) );

						booking_panel_js.find("#booknetic_recurring_end").val( booknetic.convertDate( endDate.getFullYear() + '-' + booknetic.zeroPad( endDate.getMonth() + 1 ) + '-' + booknetic.zeroPad( endDate.getDate() ), 'Y-m-d' ) );
					}
				}
			},

			serviceFixFrequency: function()
			{
				var serviceData = booknetic.serviceData;

				if( serviceData && serviceData[ 'repeat_frequency' ] > 0 && serviceData[ 'repeat_type' ] === 'daily' )
				{
					booking_panel_js.find("#booknetic_daily_recurring_frequency").val( serviceData['repeat_frequency'] ).attr('disabled', true);
				}
				else
				{
					booking_panel_js.find("#booknetic_daily_recurring_frequency").attr('disabled', false);
				}
			},

			getMonthWeekInfo: function( date, type, dayOfWeek )
			{
				var jsDate = new Date( date ),
					weekd = jsDate.getDay();
				weekd = weekd === 0 ? 7 : weekd;

				if( weekd != dayOfWeek )
				{
					return false;
				}

				const month = jsDate.getMonth() + 1,
					year = jsDate.getFullYear();

				if( type === 'last' )
				{
					var nextWeek = new Date(jsDate.getTime());
					nextWeek.setDate( nextWeek.getDate() + 7 );

					return nextWeek.getMonth() + 1 !== month;
				}

				const firstDayOfMonth = new Date(year + '-' + booknetic.zeroPad(month) + '-01'),
					firstWeekDay =  firstDayOfMonth.getDay() === 0 ? 7 : firstDayOfMonth.getDay();

				const dif = (dayOfWeek >= firstWeekDay ? dayOfWeek : parseInt(dayOfWeek) + 7) - firstWeekDay;
				const days = jsDate.getDate() - dif,
					dNumber = parseInt(days / 7) + 1;

				return type == dNumber;
			},

			confirmAppointment: async function ()
			{
				let result;

				try {
					result  = await booknetic.ajaxAsync( { backend_action: 'get_data', frontend_action: 'confirm' },
						booknetic.ajaxParameters( {
							current_step: 'confirm',
							previous_step: booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step").data('step-id'),
							info: booking_panel_js.data( 'info' )
						}), true
					);
				} catch (e) {
					booknetic.refreshGoogleReCaptchaToken();
					if (typeof e['id'] == 'undefined')
						return;

					booknetic.ajaxResultConfirmStep = e;
					booknetic.appointmentId = e['id'];
					booknetic.paymentId = typeof e['payment_id'] != 'undefined' ? e['payment_id'] : null;
					return;
				}

				booknetic.refreshGoogleReCaptchaToken();

				booknetic.ajaxResultConfirmStep = result;
				booknetic.appointmentId = result['id'];
				booknetic.paymentId   = result['payment_id'];

				if ( booknetic.paymentWindow !== null && result["expires_at"] !== undefined && result["expires_at"] < 24*60*60 )
					setTimeout(() => booknetic.paymentWindow.close, result["expires_at"] * 1000 )

				if( booknetic.getSelected.paymentMethod() === 'local' )
				{
					booknetic.paymentFinished( true );
					booknetic.showFinishStep();
				} else {
					booknetic.startPolling(result.id)
				}

				booking_panel_js.find('#booknetic_add_to_google_calendar_btn').data('url', result['google_calendar_url'] );
				booking_panel_js.find('#booknetic_add_to_icalendar_btn').attr('href', encodeURI( result['icalendar_url'] ) );
			},

			waitPaymentFinish: function()
			{
				if( booknetic.paymentWindow.closed )
				{
					if ( booknetic.paymentStatusListener )
						clearInterval( booknetic.paymentStatusListener );

					booknetic.loading(0);

					booknetic.showFinishStep();

					return;
				}

				setTimeout( booknetic.waitPaymentFinish, 1000 );
			},

			paymentFinished: function ( status )
			{
				booknetic.paymentStatus = status;
				booking_panel_js.find(".booknetic_appointment_finished_code").text( booknetic.zeroPad( booknetic.appointmentId, 4 ) );

				if( booknetic.paymentWindow && !booknetic.paymentWindow.closed )
				{
					if ( booknetic.paymentStatusListener )
						clearInterval( booknetic.paymentStatusListener );

					booknetic.paymentWindow.close();
				}

				bookneticHooks.doAction( 'payment_completed_deprecated', booknetic );
				booknetic.stopPolling()
			},

			showFinishStep: function ()
			{
				if ( BookneticData.settings.redirect_users_on_confirm === true && booknetic.paymentStatus === true )
				{
					window.location.href = BookneticData.settings.redirect_users_on_confirm_url;
					return;
				}

				if( booknetic.paymentStatus === true )
				{
					booking_panel_js.find('.booknetic_appointment_container').fadeOut(95);

					if ( booking_panel_js.find('.booknetic_appointment_steps').css( 'display' ) === 'none' )
					{
						booking_panel_js.find('.booknetic_appointment_finished').fadeIn(100).css('display', 'flex');
					} else {
						booking_panel_js.find('.booknetic_appointment_steps').fadeOut(100, function ()
						{
							booking_panel_js.find('.booknetic_appointment_finished').fadeIn(100).css('display', 'flex');
						});
					}

					bookneticHooks.doAction( 'booking_finished_successfully', booknetic );
				}
				else
				{
					booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="confirm_details"]').fadeOut( 150, function()
					{
						booking_panel_js.find('.booknetic_appointment_container_body > .booknetic_appointment_finished_with_error').removeClass('booknetic_hidden').hide().fadeIn( 150 );
					});

					booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').fadeOut( 150, function()
					{
						booking_panel_js.find('.booknetic_try_again_btn').removeClass('booknetic_hidden').hide().fadeIn( 150 );
					});

					booking_panel_js.find('.booknetic_appointment_container_header_cart').fadeOut( 150 );

					booking_panel_js.find('.booknetic_prev_step').css('opacity', '0').attr('disabled', true);

					bookneticHooks.doAction( 'payment_error', booknetic );
				}

				if ( booknetic.isMobileView() )
				{
					$('html,body').animate({scrollTop: parseInt(booking_panel_js.offset().top) - 100}, 1000);
				}
			},

			fadeInAnimate: function(el, fn)
			{
				let sec = 150;
				let delay = 50;
				let count = booking_panel_js.find(el).length;

				if( booking_panel_js.find(el).length === 0 )
					fn();

				booking_panel_js.find(el).hide().each(function (i)
				{
					(function( i, t, isLast, fn )
					{
						setTimeout( function ()
						{
							t.fadeIn( (i > 6 ? 6 : i) * sec, ( isLast ? fn : null ));
						}, (i > 6 ? 6 : i) * delay );
					})( i, $(this), (count-1===i), fn );
				});
			},

			fadeOutAnimate: function(el, sec, delay)
			{
				sec = sec > 0 ? sec : 150;
				delay = delay > 0 ? delay : 50;

				booking_panel_js.find(el).each(function (i)
				{
					(function( i, t )
					{
						setTimeout( function ()
						{
							t.fadeOut( (i > 6 ? 6 : i) * sec );
						}, (i > 6 ? 6 : i) * delay );
					})( i, $(this) );
				});
			},


			_bookneticScroll: false,
			handleScroll: function ()
			{
				if( !booknetic._bookneticScroll && !booknetic.isMobileView() )
				{
					booking_panel_js.find(".booknetic_appointment_container_body").addClass('nice-scrollbar-primary');

					booknetic._bookneticScroll = true;

					return;
				}

				if( booknetic.isMobileView() && booknetic._bookneticScroll )
				{
					booknetic._bookneticScroll = false;

					booking_panel_js.find(".booknetic_appointment_container_body").removeClass('nice-scrollbar-primary');

					if ( $( '#country-listbox' ).length )
					{
						$( '#country-listbox' ).removeClass('nice-scrollbar-primary');
					}
				}
			},

			getDateWithUTC: function ( date )//if the client timezone is negative
			{
				date = new Date( date );
				let offset  = date.getTimezoneOffset();

				if ( offset > 0 ) // if offset a positive number, client's timezone is negative
					date.setTime( date.getTime() + ( offset * 60 * 1000 ) ); //get UTC time

				return date;
			},

			initDatepicker: function ( el )
			{
				bookneticdatepicker( el[0], {
					formatter: function (input, date, instance)
					{
						var val = date.getFullYear() + '-' + booknetic.zeroPad( date.getMonth() + 1 ) + '-' + booknetic.zeroPad( date.getDate() );
						input.value = booknetic.convertDate( val, 'Y-m-d' );
					},
					startDay: BookneticData.week_starts_on == 'sunday' ? 0 : 1,
					customDays: [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')],
					customMonths: [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')],
					onSelect: function( input )
					{
						$(input.el).trigger('change');
					},
					minDate: el[0].getAttribute( "data-apply-min" ) ? this.getDateWithUTC( booknetic.convertDate( el[0].value, booknetic.datePickerFormat(), 'Y-m-d' ) ) : undefined
				});
			},

			refreshGoogleReCaptchaToken: function ()
			{
				if( 'google_recaptcha_site_key' in BookneticData )
				{
					grecaptcha.execute( BookneticData['google_recaptcha_site_key'], { action: booknetic.google_recaptcha_action }).then(function (token)
					{
						booknetic.google_recaptcha_token = token;
					});
				}
			},

			isMobileView: function ()
			{
				return window.matchMedia('(max-width: 1000px)').matches;
			},

			stepManager: {

				stepValidation: function ( step )
				{
					let result = bookneticHooks.doFilter( `step_validation_${step}`, {
						status: true,
						errorMsg: ''
					}, booknetic );

					if( result.status )
						bookneticHooks.doAction(`step_end_${step}` , booknetic);

					return result;
				},

				loadStep: function( step )
				{
					if( ! bookneticHooks.doFilter( `load_step_${step}` , booknetic ) )
						return false;

					var current_step_el	= booking_panel_js.find('.booknetic_appointment_step_element.booknetic_active_step');
					var next_step_el	= booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="'+step+'"]');

					while( next_step_el.hasClass('booknetic_menu_hidden') )
						next_step_el = next_step_el.next();

					booknetic.stepManager.disableAction();

					var next_step_id    = next_step_el.data('step-id');
					var current_step_id = '';

					if( current_step_el.length > 0 )
					{
						current_step_el.removeClass('booknetic_active_step');
						current_step_id	= current_step_el.data('step-id');
						booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + current_step_id + '"]').hide();
					}

					next_step_el.addClass('booknetic_active_step');
					booking_panel_js.find(".booknetic_appointment_container_header_text").text( next_step_el.data('title') );

					booknetic.stepManager.updateGoNextBtn( next_step_el );

					bookneticHooks.doAction('before_step_loading', booknetic, next_step_id, current_step_id);

					booknetic.stepManager.saveData();
					booknetic.stepManager.updateBookingPanelFooter();

					return true;
				},

				loadStandartSteps: function ( next_step_id, current_step_id )
				{
					if( ! booknetic.stepManager.needToReloadCache( next_step_id ) )
					{
						booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + next_step_id + '"]').show();

						booknetic.fadeInAnimate('.booknetic_appointment_container_body [data-step-id="' + next_step_id + '"] .booknetic_fade', function()
						{
							booking_panel_js.find(".booknetic_appointment_container_body").scrollTop(0);
							booknetic.handleScroll();
							booknetic.stepManager.enableActions();
						});
					}
					else
					{
						var next_step_el	= booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="'+next_step_id+'"]');
						var loader  = booking_panel_js.find('.booknetic_preloader_' + next_step_el.data('loader') + '_box');

						booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + next_step_id + '"]').empty();

						bookneticHooks.doAction('start_step_loading', booknetic, next_step_id, current_step_id);

						loader.removeClass('booknetic_hidden').show();
						let step
						const bookingPanelParams = {
							current_step: next_step_id,
							previous_step: booknetic.stepManager.getPrevStep().data( 'step-id' ),
							info: booking_panel_js.data( 'info' ),
						}
						if (next_step_id === "location" && (step = booking_panel_js.data('steps').find(s => s.id === "location"))?.options) {
							bookingPanelParams.location_filter = JSON.stringify(step.options);
						}

						booknetic.ajax( 'get_data', booknetic.ajaxParameters({...bookingPanelParams}), function ( result )
						{
							loader.hide();
							booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + next_step_id + '"]').show().html( booknetic.htmlspecialchars_decode( booknetic.sanitizeHTML(result['html']) ) );

							booknetic.fadeInAnimate('.booknetic_appointment_container_body [data-step-id="' + next_step_id + '"] .booknetic_fade', function ()
							{
								booking_panel_js.find(".booknetic_appointment_container_body").scrollTop(0);
								booknetic.handleScroll();
							});

							booknetic.stepManager.enableActions();

							bookneticHooks.doAction( 'loaded_step', booknetic, next_step_id, current_step_id, result );
							bookneticHooks.doAction( `loaded_step_${next_step_id}`, booknetic, result );

						}, false , function ( result )
						{
							loader.hide();
							booknetic.stepManager.goBack();
							bookneticHooks.doAction('step_loaded_with_error', booknetic, next_step_id, current_step_id, result);
						});
					}
				},

				disableAction: function ()
				{
					booking_panel_js.find(".booknetic_next_step, .booknetic_prev_step").attr('disabled', true);
				},

				enableActions: function ()
				{
					booking_panel_js.find(".booknetic_next_step, .booknetic_prev_step").attr('disabled', false);
				},

				updateBookingPanelFooter: function ()
				{
					booknetic.stepManager.updateGoBackButton();
				},

				updateGoNextBtn: function ( step_el )
				{
					var next_step_el	= step_el.next('.booknetic_appointment_step_element');

					while( next_step_el.hasClass('booknetic_menu_hidden') )
						next_step_el = next_step_el.next();

					if ( next_step_el.length === 0 ) {
						booking_panel_js.find('.booknetic_confirm_booking_btn').show();
						booking_panel_js.find('.booknetic_next_step_btn').hide();
					}
					else
					{
						booking_panel_js.find('.booknetic_confirm_booking_btn').hide();
						booking_panel_js.find('.booknetic_next_step_btn').show();
					}
				},

				updateGoBackButton: function ()
				{
					const stepElement = '.booknetic_appointment_step_element';
					const activeElement = '.booknetic_active_step';
					const menuHidden = '.booknetic_menu_hidden';

					// hide the BACK button for the first step
					if (  booking_panel_js.find(stepElement ).not( menuHidden ).first().is( booking_panel_js.find( activeElement ) ) ) {
						booking_panel_js.find( '.booknetic_prev_step' ).css( 'opacity', 0 ).css( 'pointer-events', 'none' );
						return;
					}

					booking_panel_js.find( '.booknetic_prev_step' ).css( 'opacity', 1 ).css( 'pointer-events', 'auto' );
				},

				saveData: ()=>
				{
					let obj = {};

					if(booknetic.cartArr[ booknetic.cartCurrentIndex ] !== undefined)
					{
						obj = booknetic.cartArr[ booknetic.cartCurrentIndex ];
					}

					obj['location'] =  booknetic.getSelected.location();
					obj['staff'] =  booknetic.getSelected.staff();
					obj['service_category'] =  booknetic.getSelected.serviceCategory();
					obj['service'] =  booknetic.getSelected.service();
					obj['service_extras'] =  booknetic.getSelected.serviceExtras();

					obj['date'] =  booknetic.getSelected.date();
					obj['time'] =  booknetic.getSelected.time();
					obj['brought_people_count'] =  booknetic.getSelected.brought_people_count();

					obj['recurring_start_date'] =  booknetic.getSelected.recurringStartDate();
					obj['recurring_end_date'] =  booknetic.getSelected.recurringEndDate();
					obj['recurring_times'] =  booknetic.getSelected.recurringTimesArr();
					obj['appointments'] =  booknetic.getSelected.recurringTimesArrFinish();

					obj['customer_id'] = booknetic.getSelected.customerId();
					obj['customer_data'] = booknetic.getSelected.formData()['data'];

					booknetic.cartArr[ booknetic.cartCurrentIndex ] = bookneticHooks.doFilter('bkntc_cart' , obj , booknetic );
				},

				goForward: function ()
				{
					let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
						current_step_id = current_step_el.data('step-id'),
						next_step_el	= booknetic.stepManager.getNextStep(),
						next_step_id    = next_step_el.data('step-id'),
						validate_step   = booknetic.stepManager.stepValidation( current_step_id );

					if( validate_step.status == false )
					{
						booknetic.toast( validate_step.errorMsg );
						return;
					}

					if( next_step_el.length == 0 )
					{
						booknetic.confirmAppointment();
						return;
					}

					current_step_el.addClass('booknetic_selected_step');

					let oldData = JSON.stringify( booknetic.cartArr[ booknetic.cartCurrentIndex ] );
					booknetic.stepManager.saveData();
					let newData = JSON.stringify( booknetic.cartArr[ booknetic.cartCurrentIndex ] );

					if( oldData !== newData )
					{
						let startToEmpty = next_step_el;
						while( startToEmpty.length > 0 )
						{
							booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="'+startToEmpty.data('step-id')+'"]').empty();
							startToEmpty = startToEmpty.next();
						}
					}

					booknetic.toast( false );

					if( booknetic.stepManager.loadStep( next_step_id ) && booknetic.isMobileView() )
					{
						$('html,body').animate({scrollTop: parseInt(booking_panel_js.offset().top) - 100}, 1000);
					}
				},

				goBack: function ()
				{
					let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
						prev_step_el    = booknetic.stepManager.getPrevStep();

					if (current_step_el.data('step-id') === "date_time") {
						booknetic.current_month_is_empty = undefined
					}

					current_step_el.removeClass('booknetic_selected_step').nextAll('.booknetic_appointment_step_element').removeClass('booknetic_selected_step');

					if( prev_step_el.length > 0 )
					{
						if (prev_step_el.data( 'step-id' ) === 'service_extras' && BookneticData[ 'skip_extras_step_if_need' ] === 'on' && prev_step_el.css('display') === 'none')
						{
							prev_step_el.css('display', 'block');
							prev_step_el.removeClass('booknetic_selected_step');

							do{
								prev_step_el = prev_step_el.prev();
							}
							while(prev_step_el.hasClass('booknetic_menu_hidden'));
						}

						current_step_el.removeClass('booknetic_active_step');
						prev_step_el.addClass('booknetic_active_step');

						booknetic.stepManager.disableAction();
						booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + current_step_el.data('step-id') + '"]').fadeOut(200, function()
						{
							booknetic.stepManager.enableActions();
							booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + prev_step_el.data('step-id') + '"]').fadeIn(200, function ()
							{
								booknetic.handleScroll();
							});
						});

						booking_panel_js.find(".booknetic_appointment_container_header_text").text( prev_step_el.data('title') );
					}

					booking_panel_js.find('.booknetic_confirm_booking_btn').hide();
					booking_panel_js.find('.booknetic_next_step_btn').show();

					booknetic.stepManager.updateBookingPanelFooter();
				},

				getNextStep: function ()
				{
					let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
						next_step_el	= current_step_el.next('.booknetic_appointment_step_element');

					while( next_step_el.hasClass('booknetic_menu_hidden') )
						next_step_el = next_step_el.next();

					return next_step_el;
				},

				getPrevStep: function ()
				{
					if( booknetic.cartPrevStep != undefined)
					{
						let x = booknetic.cartPrevStep;
						booknetic.cartPrevStep = undefined;
						return booking_panel_js.find(".booknetic_appointment_steps_body div[data-step-id=" + x + "]");
					}
					let current_step_el	= booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step"),
						prev_step_el    = current_step_el.prev('.booknetic_appointment_step_element');

					while( prev_step_el.hasClass('booknetic_menu_hidden') )
						prev_step_el = prev_step_el.prev();

					return prev_step_el;
				},

				getCurrentStep: function ()
				{
					return booking_panel_js.find(".booknetic_appointment_step_element.booknetic_active_step");
				},

				refreshStepNumbers: function ()
				{
					var index = 1;

					booking_panel_js.find('.booknetic_appointment_steps_body > .booknetic_appointment_step_element').each(function()
					{
						if( $(this).css('display') != 'none' )
						{
							$(this).find('.booknetic_badge').text( index );
							index++;
						}
					});
				},

				needToReloadCache: function( stepId )
				{
					if( stepId == 'confirm_details' || stepId=='cart' )
						return true;

					if( booking_panel_js.find('.booknetic_appointment_container_body [data-step-id="' + stepId + '"] > *').length > 0 )
					{
						return false;
					}

					return true;
				},

			},

			addGroupAppointmentsCounterForBookneticCalendarDays: function ()
			{
				const dates = booknetic.calendarDateTimes['dates'];

				for ( const date in dates )
				{
					if ( dates[ date ].length !== 1 )
					{
						continue;
					}

					let max_capacity = dates[ date ][ 0 ].max_capacity;
					let weight = dates[ date ][ 0 ].weight;

					if (
						weight == 0 ||
						'hide_available_slots' in booknetic.calendarDateTimes &&
						booknetic.calendarDateTimes['hide_available_slots'] == 'on'
					)
					{
						continue;
					}

					booking_panel_js.find( `.booknetic_calendar_days[data-date=${date}] > div` ).append( `<div class="booknetic_time_group_num booknetic_date_group_num">${weight} / ${max_capacity}</div>` );
				}
			},

			checkIfNoDatesAvailable: function ( backendResult )
			{
				return backendResult[ 'data' ][ 'dates' ].length === 0 || Object.values( backendResult[ 'data' ][ 'dates' ] ).every( d => d.length === 0 );
			},

			intervalId: 0,

			startPolling: (appointmentId) => {
				const intervalId = setInterval(() => {
					booknetic.ajaxAsync( 'isPaymentDone', {id: appointmentId}, false)
						.then(result => {
							if (!result.isDone) {
								return;
							}

							booknetic.paymentFinished(true);
						})
						.catch(_ => clearInterval(intervalId));
				}, 3000)

				setTimeout(() => clearInterval(intervalId), 1000 * 60 * 10);

				this.intervalId = intervalId;
			},

			stopPolling: () => this.intervalId && clearInterval(this.intervalId),
		};

		// steplerle bagli basic eventler
		booking_panel_js.on('click', '.booknetic_next_step', function()
		{
			booknetic.stepManager.goForward();
		}).on('click', '.booknetic_prev_step', function()
		{
			booknetic.stepManager.goBack();
		});

		booking_panel_js.on('click', '#booknetic_finish_btn', function ()
		{
			let page = window.location;

			// check if iframe
			if ( window.location !== window.parent.location )
			{
				page = window.parent.location;
			}

			if( ! $(this).data('redirect-url') )
			{
				page.reload();
			}
			else
			{
				page.href = $(this).data('redirect-url');
			}
		}).on('click' ,'.bkntc_again_booking' , function ()
		{
			let currentBody = booking_panel_js.find('.booknetic_need_copy');
			booknetic.cartHTMLBody[booknetic.cartCurrentIndex] = currentBody.clone(true,true).get(0);

			booking_panel_js.find('.booknetic_appointment_steps_body .booknetic_appointment_step_element.need_copy').each(function()
			{
				let id = $(this).attr('data-step-id');
				if(booknetic.cartHTMLSideBar[booknetic.cartCurrentIndex] == undefined)
				{
					booknetic.cartHTMLSideBar[booknetic.cartCurrentIndex] = {};
				}
				booknetic.cartHTMLSideBar[booknetic.cartCurrentIndex][id] = $(this).clone(true,true).get(0);
			});

			booknetic.cartCurrentIndex = booknetic.cartArr.length;
			booknetic.cartArr[booknetic.cartCurrentIndex] = {};

			booking_panel_js.find("#booknetic_start_new_booking_btn").trigger('click' , true);

		}).on('click', '#booknetic_start_new_booking_btn', function ( e , param )
		{
			if( param === undefined )
			{
				booking_panel_js.find('.booknetic_appointment_container_header_cart').fadeOut();

				booknetic.cartHTMLBody 	   = [];
				booknetic.cartHTMLSideBar  = [];
				booknetic.cartArr 		   = [];
				booknetic.cartCurrentIndex = 0;
			}
			booking_panel_js.find('.booknetic_appointment_finished').fadeOut(100, function()
			{
				const appointment_step_style = booking_panel_js.find('.booknetic_appointment_steps').attr( 'style' );

				// in case if booking_panel_js.find('.booknetic_appointment_steps').fadeOut() called
				if ( appointment_step_style && appointment_step_style.search( 'display: none;' ) !== -1 )
					booking_panel_js.find('.booknetic_appointment_steps').fadeIn(100);

				booking_panel_js.find('.booknetic_appointment_container').fadeIn(100);
			});

			booking_panel_js.find(".booknetic_selected_step").removeClass('booknetic_selected_step');
			booking_panel_js.find(".booknetic_active_step").removeClass('booknetic_active_step');

			booknetic.current_month_is_empty 	= undefined
			booknetic.calendarDateTimes			= {};
			booknetic.time_show_format			= 1;
			booknetic.calendarYear				= null;
			booknetic.calendarMonth				= null;
			booknetic.paymentWindow				= null;
			booknetic.paymentStatus				= null;
			booknetic.appointmentId				= null;
			booknetic.paymentId			    	= null;

			var start_step = booking_panel_js.find(".booknetic_appointment_step_element:not(.booknetic_menu_hidden):eq(0)");
			start_step.addClass('booknetic_active_step');
			booking_panel_js.find('.booknetic_appointment_container_body  [data-step-id] ').empty();
			booknetic.stepManager.loadStep( start_step.data('step-id') );

			booking_panel_js.find('.booknetic_appointment_container_body  [data-step-id]').hide();
			booking_panel_js.find('.booknetic_appointment_container_body  [data-step-id="' + start_step.data('step-id') + '"]').show();

			booking_panel_js.find('.booknetic_card_selected').removeClass('booknetic_card_selected');
			booking_panel_js.find('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');
			booking_panel_js.find('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');

			booking_panel_js.find(".booknetic_calendar_selected_day").data('date', null);
			booking_panel_js.find(".booknetic_selected_time").data('time', null);

			booknetic.handleScroll();

		}).on('click', '#booknetic_add_to_google_calendar_btn', function ()
		{
			window.open( $(this).data('url') );
		}).on('click', '.booknetic_try_again_btn', function ()
		{
			booknetic.ajax('delete_unpaid_appointment', booknetic.ajaxParameters({ payment_id: booknetic.paymentId }), function ()
			{
				booknetic.paymentId   = null;

				booking_panel_js.find('.booknetic_appointment_finished_with_error').fadeOut(150, function ()
				{
					booking_panel_js.find('.booknetic_appointment_container_body  [data-step-id="confirm_details"]').fadeIn(150, function ()
					{
						booknetic.handleScroll();
					});
				});

				booking_panel_js.find('.booknetic_try_again_btn').fadeOut(150, function ()
				{
					booking_panel_js.find('.booknetic_next_step.booknetic_confirm_booking_btn').fadeIn(150);
					booking_panel_js.find('.booknetic_prev_step').css('opacity', '1').attr('disabled', false);
				});

				if( ! booking_panel_js.find('.booknetic_appointment_step_element[data-step-id="cart"]').hasClass('booknetic_menu_hidden') )
				{
					booking_panel_js.find('.booknetic_appointment_container_header_cart').fadeIn( 150 );
				}

			});
		});

		//doit staff stepi (locationdan ayirmag)

		$( window ).resize(function ()
		{
			booknetic.handleScroll();
		});

		let bookneticStepsArray = booking_panel_js.data('steps');
		for( let step of bookneticStepsArray )
		{
			let classAttr = 'booknetic_appointment_step_element';
			if( step.id === 'cart' || step.id === 'confirm_details' )
				classAttr += ' need_copy';

			if( step.hidden )
				classAttr += ' booknetic_menu_hidden';

			let attrs = step.attrs ?? '';

			attrs += ` data-step-id="${step.id}"`;
			attrs += ` data-loader="${step.loader}"`;
			attrs += ` data-title="${step.head_title}"`;

			if( step.value != "" )
				attrs += ` data-value="${step.value}"`;

			booking_panel_js.find('.booknetic_appointment_steps_body').append(`
					<div class="${classAttr}" ${attrs}>
	                    <span class="booknetic_badge"></span>
	                    <span class="booknetic_step_title"> ${step.title}</span>
	                </div>
				`);
		}

		var first_step_id = booking_panel_js.find('.booknetic_appointment_steps_body > .booknetic_appointment_step_element:not(.booknetic_menu_hidden)').eq(0).data('step-id');
		booknetic.stepManager.loadStep( first_step_id );

		booknetic.handleScroll();

		booknetic.fadeInAnimate('.booknetic_appointment_step_element:not(.booknetic_menu_hidden)', function ()
		{
			booknetic.stepManager.refreshStepNumbers();
		});

		booking_panel_js.find(".booknetic_appointment_steps_footer").fadeIn(200);

		if( 'google_recaptcha_site_key' in BookneticData )
		{
			grecaptcha.ready(function ()
			{
				booknetic.refreshGoogleReCaptchaToken();
			});
		}

		bookneticHooks.doAction('booking_panel_loaded', booknetic);

		return booknetic;
	};

	$(document).ready( function()
	{
		if ( $( 'html' ).attr( 'dir' ) === 'rtl' )
		{
			$( 'body' ).addClass( 'rtl' );// doit bu nedi bele arashdirmag lazimdi... .rtl classi chox tehlukelidi. booknetic_rtl etmek olar meselen ve ya bashga n qeder metod var, bu neye lazimdi umumiyyetle bele?
		}

		/**
		 * doit bu duzelmelidi. hem alqoritmde xeta var, meselen Cart stepi load olanda headerda icon show olur. geri qayidib nese deyishiklik eledik meselen Servisi deyishdik.
		 * data deyishir deye cart iconu sifirlanmalidi. chunki butun stepleri tamamlamalidi, date&time sechmelidi, formu tezeden doldurmalidi, chunki service deyishende form inputlarda deyishe biler.
		 * Amma bu stepleri doldurmadan hemen show olmush icona clickledikde birbasha tulluyur cart stepine. Ordanda next bassan error verir ki, meselen formu doldur ve ya date&time sech ve s.
		 * Elave olarag $('.*') kimi select yolverilmezdir. booknetic.panel_js.find('.*') || booking_panel_js.find('.*') istifade olunmalidir.
		 * Cunki 1 pagede eyni anda bir neche booking panel load ola biler. biri digerinin iconlariynan oynamasin deye bu metod hokmen her yerde istifade edilmelidi.
		 */
		$("body").click(function(e)
		{
			if( $(e.target).parent().hasClass('booknetic-cart-item-more') )
			{
				let a = $(e.target).parents('.booknetic-cart-item-header').find('.booknetic-cart-item-btns').first();
				let b = a.hasClass('show');
				$(".booknetic-cart-item-btns").removeClass("show");
				if(!b)
				{
					a.addClass('show')
				}
				else
				{
					a.removeClass('show');
				}
			}
			else
			{
				$(".booknetic-cart-item-btns").removeClass("show");
			}
		});
	});

})(jQuery);

// local payment gateway
(function($)
{
	"use strict";

	$(document).ready(function()
	{
		let paymentData;

		bookneticHooks.addAction( 'before_processing_payment', function ( payment_method, data )
		{
			if( payment_method !== 'local' )
				return;

			paymentData = data;
		});

		bookneticHooks.addAction( 'after_processing_payment', function( payment_method, process_status, data )
		{
			if( payment_method !== 'local' )
				return;

			if( ! process_status )
			{
				return;
			}

			bookneticHooks.doAction('payment_completed', true, paymentData);
		});
	});

})(jQuery);