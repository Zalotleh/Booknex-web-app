<?php

namespace BookneticApp\Frontend\Controller;

use BookneticApp\Backend\Appointments\Helpers\AppointmentChangeStatus;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests as Request;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Backend\Customers\Helpers\CustomerService;
use BookneticApp\Frontend;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\ExtraCategory;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Translation;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\FrontendAjax;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\StringUtil;
use BookneticApp\Providers\Request\Post;
use Exception;

class Ajax extends FrontendAjax
{
    private $categories;
    private $appointmentRequests;

	public function __construct()
	{
	}

    public function get_data()
    {
        $this->appointmentRequests = Request::load();

        try
        {
            $postStepVerification = apply_filters( 'bkntc_post_step_verification', true, $this->appointmentRequests, $this->appointmentRequests->getPreviousStep() );
            $preStepVerification = apply_filters( 'bkntc_pre_step_verification', true, $this->appointmentRequests, $this->appointmentRequests->getCurrentStep() );
        }
        catch ( Exception $e )
        {
            return $this->response( false, $e->getMessage() );
        }

        if ( ! $postStepVerification )
        {
            return $this->response( false, \bkntc__( 'Post step verification failure!' ) );
        }

        if ( ! $preStepVerification )
        {
            return $this->response( false, \bkntc__( 'Pre step verification failure!' ) );
        }

        $currentStep = $this->appointmentRequests->getCurrentStep();

	    return $this->$currentStep();
    }

	public function location()
	{
		$appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

 		$locations = $appointmentObj->getAvailableLocations()->withTranslations()->fetchAll();

        return $this->view('booking_panel.locations', [
			'locations' => apply_filters( 'location_list', $locations )
		]);
	}

    public function get_booking_panel()
    {
        $atts = [
            'location'   => Helper::_post('location' , '' , 'int'),
            'staff'      => Helper::_post('staff' , '' , 'int'),
            'service'    => Helper::_post('service' , '' , 'int'),
            'category'   => Helper::_post('category' , '' , 'int'),
            'theme'      => Helper::_post('theme' , '' , 'int'),
        ];

        $shortcode = "booknetic";

        foreach ( $atts as $key => $value )
        {
            if( empty( $value ) )
                continue;

            $shortcode .= " $key=$value";
        }

        return do_shortcode( "[$shortcode]" );
	}

	public function staff()
	{
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

		$staffList      = Staff::where('is_active', 1)->withTranslations()->orderBy('id');

        if( $appointmentObj->serviceCategoryId > 0 )
        {
            $categoriesFiltr = Helper::getAllSubCategories( $appointmentObj->serviceCategoryId );

            //todo:// buralar boş yerə request-i yavaşladır. Bunları tək query-idə cəmləmək olar, bu qədər fetch və for işlətməkdənsə

            $services = Service::select(['id'])->where('category_id' , 'in' ,array_values($categoriesFiltr))->fetchAll();

            $servicesIdList = array_map(function ($service){
                return $service->id;
            },$services);

            $servicesStaffList = ServiceStaff::select(['staff_id'])->where('service_id' , 'in' ,$servicesIdList)->fetchAll();

            $filterStaffIdList = array_map(function ($serviceStaff){
                return $serviceStaff->staff_id;
            },$servicesStaffList);

            $staffList->where('id' ,'in' , $filterStaffIdList);
        }

		if( $appointmentObj->locationId > 0 )
		{
			$staffList->whereFindInSet( 'locations', $appointmentObj->locationId );
		}

		if( $appointmentObj->serviceId > 0 )
		{
			$subQuery = ServiceStaff::where('service_id', $appointmentObj->serviceId)
				->where( 'staff_id', DB::field( 'id', 'staff' ) )
				->select('count(0)');

			$staffList->where( $subQuery, '>', 0 );
		}

		$staffList = $staffList->fetchAll();

        CalendarService::setIncludeCart( true );
        CalendarService::setSkipCurrentRequest( true );

		if( $appointmentObj->getTimeslotsCount() > 0 )
		{
			$onlyAvailableStaffList = [];

			foreach ( $staffList as $staffInf )
			{
				$appointmentObj->staffId   = $staffInf->id;
				$appointmentObj->timeslots = null;
				$staffIsOkay               = true;

				foreach ( $appointmentObj->getAllTimeslots() as $timeslot )
				{
					if( ! $timeslot->isBookable() )
					{
						$staffIsOkay = false;
						break;
					}
				}

				if( $staffIsOkay )
					$onlyAvailableStaffList[] = $staffInf;

				$appointmentObj->staffId = null;
				$appointmentObj->timeslots = null;

			}

			$staffList = $onlyAvailableStaffList;
		}

        $staffList = array_map(function ($staff){
            $staff['name']          = htmlspecialchars($staff['name']);
            $staff['email']         = htmlspecialchars($staff['email']);
            $staff['phone_number']  = htmlspecialchars($staff['phone_number']);
            $staff['profession']    = htmlspecialchars($staff['profession']);
            return $staff;
        } , $staffList);

        return $this->view('booking_panel.staff', [
			'staff' => apply_filters( 'staff_list', $staffList )
		]);
	}

	public function service()
	{
        $appointmentRequests = Request::load();

        $queryParamsFilter = '';
        $qParams = $appointmentRequests->queryParams;
        $serviceId = 0;

        if ( end( $appointmentRequests->appointments )->serviceId )
        {
            $serviceId = end( $appointmentRequests->appointments )->serviceId;
        }
        else if ( ! empty( $qParams ) && ! empty( $qParams[ 'show_service' ] ) && ! empty( $qParams[ 'service' ] ) )
        {
            $serviceId = $qParams[ 'service' ];
        }

        if ( $serviceId )
        {
            $queryParamsFilter .= ' tb1.id = ' . $serviceId . ' AND ';
        }

        $appointmentObj = $appointmentRequests->currentRequest();

		$queryAttrs = [ $appointmentObj->staffId ];
		if( $appointmentObj->serviceCategoryId > 0 )
        {
            $categoriesFiltr = Helper::getAllSubCategories( $appointmentObj->serviceCategoryId );
        }

		$locationFilter = '';
		if( $appointmentObj->locationId > 0 && !( $appointmentObj->staffId > 0 ) )
		{
			$locationFilter = " AND tb1.`id` IN (SELECT `service_id` FROM `".DB::table('service_staff')."` WHERE `staff_id` IN (SELECT `id` FROM `".DB::table('staff')."` WHERE FIND_IN_SET('{$appointmentObj->locationId}', IFNULL(`locations`, ''))))";
		}

        //todo:// bunu query builder-ə keçirək
		$services = DB::DB()->get_results(
			DB::DB()->prepare( "
				SELECT
					tb1.*,
					IFNULL(tb2.price, tb1.price) AS real_price,
					(SELECT `data_value` FROM `" . DB::table('data') . "` WHERE `table_name`='services' AND `data_key`='only_visible_to_staff' AND `row_id`=tb1.id ) AS only_visible_to_staff
				FROM `" . DB::table('services') . "` tb1 " .
                ( $appointmentObj->staffId > 0 ? 'INNER' : 'LEFT' )." JOIN `" . DB::table('service_staff') . "` tb2 ON tb2.service_id=tb1.id AND tb2.staff_id=%d
				WHERE " .
                $queryParamsFilter
                . " tb1.`is_active`=1 AND (SELECT count(0) FROM `" . DB::table('service_staff') . "` WHERE service_id=tb1.id)>0 ".DB::tenantFilter()." ".$locationFilter."
				" . ( $appointmentObj->serviceCategoryId > 0 && !empty( $categoriesFiltr ) ? "AND tb1.category_id IN (". implode(',', $categoriesFiltr) . ")" : "" ) . "
				ORDER BY tb1.category_id, tb1.id", $queryAttrs ),
			ARRAY_A
		);

        if ( empty( $services ) )
            return $this->view('booking_panel.services', [ 'services' => $services ] );

        //todo:// Burda raw query isletdiyimiz ucun modelin default behaviour-u ile translate ede bilmirik
        $categoryIds = [];
        $serviceIds = array_map( function ($service) use ( &$categoryIds ) {
            $categoryIds[] = $service['category_id'];
            return $service['id'];
        }, $services );

        $categoryTranslations = Translation::where( 'row_id', 'in', $categoryIds )
            ->where( 'table_name', 'service_categories' )
            ->where( 'locale', Helper::getLocaleForFrontend() )
            ->fetchAll();
        $servicesOrder = json_decode(Helper::getOption( "services_order", '[]' ), true);
        $orderedServices = [];
        if ( ! empty( $servicesOrder ) ) {
            $serviceIds = [];
            foreach ( $servicesOrder as $v ) {
                $serviceIds = array_merge( $serviceIds, $v );
            }
            foreach ( $serviceIds as $item ) {
                foreach ( $services as $k => $service ) {
                    if ( $service["id"] == $item ) {
                        $orderedServices[] = $service;
                        unset( $services[$k] );
                    }
                }
            }
        } else {
            $allCategories = Helper::assocByKey(ServiceCategory::fetchAll(), 'id');
            $categories = $this->flatToTree( $allCategories );
            $categoriesIds = $this->pluckChildren( $categories );

            foreach ($categoriesIds as $categoryId) {
                foreach ( $services as $k => $service ) {
                    if ($service["category_id"] == $categoryId) {
                        $orderedServices[] = $service;
                        unset( $services[$k] );
                    }
                }
            }
        }
        $services = array_merge( $orderedServices, $services );

        $serviceTranslations = Translation::where( 'row_id', 'in', $serviceIds )
            ->where( 'table_name', 'services' )
            ->where( 'locale', Helper::getLocaleForFrontend() )
            ->fetchAll();
        // END

		$onlyVisibleToStaff = [];

		foreach ( $services as $k => &$service )
		{
			if ( isset($service['only_visible_to_staff']) && (int) $service['only_visible_to_staff'] === 1 )
			{
				$onlyVisibleToStaff[] = $k;
				continue;
			}

            $categoryDetails = $this->__getServiceCategoryName( $service['category_id']);

            $services[$k]['category_name'] =  $this->findServiceTranslation( $categoryTranslations, $service[ 'category_id' ], 'name', $categoryDetails['name'] );
			$services[$k]['category_parent_id'] = $categoryDetails['parent_id'];
            $services[$k]['name'] = htmlspecialchars( $this->findServiceTranslation( $serviceTranslations, $service[ 'id' ], 'name', $service['name'] ) );
            $note = htmlspecialchars( $this->findServiceTranslation( $serviceTranslations, $service[ 'id' ], 'note', $service['notes'] ) );

			$services[$k]['notes'] = $note;

			$wrappedNote = StringUtil::cutText( $note, 180 );
			$wrappedNoteLines = explode("\n", $wrappedNote);
			$hasManyLines = is_array($wrappedNoteLines) && count($wrappedNoteLines) > 2;

			if($hasManyLines)
			{
				$wrappedNote = implode("\n", [$wrappedNoteLines[0], $wrappedNoteLines[1]]);
			}

			$shouldWrap = (mb_strlen( $note ) > 180) || $hasManyLines;

			$services[$k]['wrapped_note'] = $wrappedNote;
			$services[$k]['should_wrap'] = $shouldWrap;
		}

		foreach ( $onlyVisibleToStaff as $k )
		{
			unset( $services[$k] );
		}

        return $this->view('booking_panel.services', [
			'services' => $services
		]);
	}

    private function findServiceTranslation( $data, $id, $columnName, $defaultValue = '' )
    {
        if ( ! is_array( $data ) )
            return $defaultValue;

        foreach ( $data as $item )
        {
            if ( ! isset( $item [ 'row_id' ] ) || ! isset( $item[ 'column_name' ] ) )
                continue;

            if ( $item[ 'row_id' ] !== $id || $item[ 'column_name' ] !== $columnName )
                continue;

            return $item[ 'value' ];
        }

        return $defaultValue;
    }

    private function pluckChildren( $data ): array
    {
        $idSet = [];

        foreach ( $data as $item )
        {
            $idSet[] = $item[ 'id' ];

            if ( empty( $item[ 'child' ] ) )
                continue;

            $idSet = array_merge( $idSet, $this->pluckChildren( $item[ 'child' ] ) );
        }

        return $idSet;
    }

    private function flatToTree( $elements, $parentId = 0 ): array
    {
        $branch = [];

        foreach ( $elements as $element )
        {
            if ( $element[ 'parent_id' ] != $parentId )
                continue;

            $element[ 'child' ] = $this->flatToTree( $elements, $element[ 'id' ] );

            $branch[] = $element;
        }

        return $branch;
    }

	public function service_extras()
	{
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

		$extras	= ServiceExtra::withTranslations()
            ->where( 'is_active', 1 )
            ->where( 'max_quantity', '>', 0 )
            ->orderBy( '-category_id DESC' );

        if ( Helper::getOption( 'show_all_service_extras', 'off' )=='off' )
        {
            $extras = $extras->where( 'service_id', $appointmentObj->serviceId );
        }

        $extraLimitationsEnabled = false;
        $extraLimitations = json_decode(
            Service::getData($appointmentObj->serviceId, 'service_extra_limitations', "[]"),
            true
        );

        if (! empty($extraLimitations) && count($extraLimitations) === 2) {
            $extraLimitationsEnabled = true;
        } else {
            $extraLimitations = [];
        }

        $extras = $extras->fetchAll();

        $extraCategories = array_column( $extras, 'category_id' );

        $categoryLastExtras = [];

        if ( ! empty( $extraCategories ) )
        {
            $extraCategories = ExtraCategory::select([ 'id', 'name' ])->where( 'id', 'IN' , $extraCategories )->fetchAll();

            $extraCategories = array_combine( array_column( $extraCategories, 'id' ), $extraCategories );

            foreach ( array_reverse( $extras ) as $extra )
            {
                if ( ! is_null( $extra->category_id ) && ! in_array( $extra->category_id, $categoryLastExtras ) )
                {
                    $categoryLastExtras[ $extra->id ] = $extra->category_id;
                }
            }

        }

		foreach ( $extras as &$extra )
		{
			$wrappedNote = StringUtil::cutText( (string) $extra['notes'], 180 );
			$wrappedNoteLines = explode("\n", $wrappedNote);
			$hasManyLines = is_array($wrappedNoteLines) && count($wrappedNoteLines) > 2;

			if($hasManyLines)
			{
				$wrappedNote = implode("\n", [$wrappedNoteLines[0], $wrappedNoteLines[1]]);
			}

			$shouldWrap = (mb_strlen( $extra[ 'notes' ] ) > 180) || $hasManyLines;

			$extra['wrapped_note'] = $wrappedNote;
			$extra['should_wrap'] = $shouldWrap;
		}

		$components = [];

		if ( ! empty( $extras ) ) {
			$extras = apply_filters( 'bkntc_booking_panel_render_service_extras_info', $extras );
			$components[] = $this->view( 'booking_panel.components.service_extras', [
				'extras'                => $extras,
				'service_name'          =>  htmlspecialchars( $appointmentObj->serviceInf->name ),
				'extra_categories'	    =>	$extraCategories,
				'category_last_extras'  => $categoryLastExtras,
			] )[ 'html' ];
		}

        return $this->view('booking_panel.extras', [
			'components'		    =>	apply_filters( 'bkntc_extras_step_components', $components ),
            'extra_limitations' => [
                'enabled' => $extraLimitationsEnabled,
                'limitations' => $extraLimitations
            ]
		]);
	}

    /**
     * @throws Exception
     */
    public function date_time()
	{
        $info = Helper::_post( 'info', '', 'string' );

        $appointmentRequests = $this->appointmentRequests;
        $appointmentObj = $appointmentRequests->currentRequest();

		if( ! $appointmentObj->serviceInf )
		{
			return $this->response( false, bkntc__('Please fill in all required fields correctly!') );
		}

		$month			     = Helper::_post('month', null, 'int', [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 ]);
		$year			     = Helper::_post('year', Date::format('Y'), 'int');

        $initialCalendarLoad = false;
        $defaultStartMonth = Helper::getOption('booking_panel_default_start_month');

        if( $month === null )
        {
            $initialCalendarLoad = true;
            $month = empty( $defaultStartMonth ) ? Date::format('m') : $defaultStartMonth;
        }

        if ( ! empty ( $defaultStartMonth ) && $initialCalendarLoad )
            $year = Helper::getAdjustedYearByGivenDefaultStartMonth( $month, $year );

		$date_start		= Date::dateSQL( $year . '-' . $month . '-01' );
		$date_end		= Date::format('Y-m-t', $year . '-' . $month . '-01' );

        $decodedInfo = Helper::decodeInfo( $info );

        if ( $decodedInfo && isset( $decodedInfo[ 'limited_booking_days' ] ) )
        {
            $limitedBookingDays = $decodedInfo[ 'limited_booking_days' ];
        }
        else
        {
            $defaultOption = Helper::getOption( 'available_days_for_booking', '365' );

            $serviceOptionEnabled = Service::getData( $appointmentObj->serviceId, 'enable_limited_booking_days', 0 );

            if ( empty( $serviceOptionEnabled ) ) {
                $limitedBookingDays = $defaultOption;
            } else {
                $limitedBookingDays = Service::getData( $appointmentObj->serviceId, 'available_days_for_booking', $defaultOption );
            }
        }

        if ( $limitedBookingDays > -1 ) {
            $limitEndDate = Date::epoch( $limitedBookingDays > 0 ? '+' . $limitedBookingDays . ' days' : Date::format('d.m.Y 23:59:59'));

            if( Date::epoch( $date_end ) > $limitEndDate )
            {
                $date_end = Date::dateSQL( $limitEndDate );
            }
        }

        $recurringStartDate = '';

		if( $appointmentObj->isRecurring() )
		{
			$service_type = 'recurring_' . ( in_array( $appointmentObj->serviceInf->repeat_type, ['daily', 'weekly', 'monthly'] ) ? $appointmentObj->serviceInf->repeat_type : 'daily' );
            $calendarData = null;

            $startDateInt = ( new CalendarService( $date_start, $date_end ) )->setDefaultsFrom( $appointmentObj )->getFirstAvailableDay();

            $minTimePriorBooking = Date::epoch( '+' .  Helper::getMinTimeRequiredPriorBooking( $appointmentObj->serviceInf->id ) . ' minutes' );

            if ( $minTimePriorBooking > $startDateInt )
            {
                $startDateInt = $minTimePriorBooking;
            }

            $recurringStartDate = Date::datee( $startDateInt );
        }
		else
		{
			$service_type = 'non_recurring';

			$calendarData = new CalendarService( $date_start, $date_end );

            CalendarService::setIncludeCart( true );
            CalendarService::setSkipCurrentRequest( true );

            $calendarData = $calendarData->setDefaultsFrom( $appointmentObj )->getCalendar();

			$calendarData['hide_available_slots'] = Helper::getOption('hide_available_slots', 'off');
		}

        return $this->view('booking_panel.date_time_' . $service_type, [
            'sid'                   => $appointmentObj->serviceInf->id,
			'date_based'	        =>	$appointmentObj->serviceInf->duration >= 1440,
			'service_max_capacity'	=>  (int) $appointmentObj->serviceInf->max_capacity > 0 ? (int) $appointmentObj->serviceInf->max_capacity : 1,
            'recurring_start_date'  => $recurringStartDate,
			'service_info'          => [
                'repeat_frequency'	=>	htmlspecialchars( $appointmentObj->serviceInf->repeat_frequency ),
            ]
		], [
			'data'			    =>	$calendarData,
			'service_type'	    =>	$service_type,
			'time_show_format'  =>  Helper::getOption('time_view_type_in_front', '1'),
            'calendar_start_month'  => (int)$month,
            'calendar_start_year'   => (int)$year,
			'service_info'	    =>	[
				'date_based'		=>	$appointmentObj->isDateBasedService(),
				'repeat_type'		=>	htmlspecialchars( (string)$appointmentObj->serviceInf->repeat_type ),
				'repeat_frequency'	=>	htmlspecialchars( (string)$appointmentObj->serviceInf->repeat_frequency ),
				'full_period_type'	=>	htmlspecialchars( (string)$appointmentObj->serviceInf->full_period_type ),
				'full_period_value'	=>	(int)$appointmentObj->serviceInf->full_period_value
			]
		]);
	}

	public function recurring_info()
	{
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

        //todo:// bu nə deməkdi, şair?
        if( $appointmentObj->staffId == 0 )
            $appointmentObj->staffId = -1;

		if( ! $appointmentObj->isRecurring() )
		{
			return $this->response(false, bkntc__('Please select service'));
		}

		try {
			$appointmentObj->validateRecurringData();
		}
		catch ( Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

		$recurringAppointments = AppointmentService::getRecurringDates( $appointmentObj );

		if( ! count( $recurringAppointments ) )
		{
			return $this->response(false , bkntc__('Please choose dates' ));
		}

		return $this->view('booking_panel.recurring_information', [
			'appointmentObj'    => $appointmentObj,
			'appointments'      => $recurringAppointments
		]);
	}

	public function information()
	{
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

//		if( $appointmentObj->serviceId <= 0 )
//		{
//			$checkAllFormsIsTheSame = DB::DB()->get_results('SELECT * FROM `'.DB::table('forms').'` WHERE (SELECT count(0) FROM `'.DB::table('services').'` WHERE FIND_IN_SET(`id`, `service_ids`) AND `is_active`=1)<(SELECT count(0) FROM `'.DB::table('services').'` WHERE `is_active`=1)' . DB::tenantFilter(), ARRAY_A);
//
//            if( !$checkAllFormsIsTheSame )
//			{
//				$firstRandomService = Service::where('is_active', '1')->limit(1)->fetch();
//				$appointmentObj->serviceId = $firstRandomService->id;
//			}
//		}

		// Logged in user data
        $customerId = 0;
		$name		= '';
		$surname	= '';
		$email		= '';
		$phone 		= '';

        $disableNameInput       = false;
        $disableSurnameInput    = false;
        $disableEmailInput      = false;
        $disablePhoneInput      = false;

        $hideNameInput       = false;
        $hideSurnameInput    = false;
        $hideEmailInput      = false;
        $hidePhoneInput      = false;

        $canChangeEmailInput    = true;

        $identifier = Helper::getOption('customer_identifier', 'email');

        $appointmentCount = count($appointmentRequests->appointments);

        /**
         * Eger Multi-booking edirse, onceki bookingde daxil etdiyi customere baxir, eger movcud customer olubsa,
         * cari formda da identifier input bashga hamisini hide edir ve information alertini chixardir ki, customer bizim DB-de var;
         * ve customer-id`ni data`da save edir.
         */
        if ( $appointmentCount > 1 )
        {
            $lastAppointment = $appointmentRequests->appointments[$appointmentCount-2];
            $customerId = $lastAppointment->customerId;

            /**
             * Bu yoxlama security issue chixmasin deyedir. Eks halda adam random tanishinin emailini yazar,
             * Multi booking etdikde hemen userin ad, soyad ve nomresini gore biler. Gore bilmesin deye bosh gonderirik,
             * zaten backend uchun dolu/bosh olmasinin ehemiyyeti yoxdu, chunki customer-id istifade edilir bu casede.
             */
            if ( ! ($customerId > 0) ) {
                $name       = isset($lastAppointment->customerData['first_name']) ? $lastAppointment->customerData['first_name'] : '';
                $surname    = isset($lastAppointment->customerData['last_name']) ? $lastAppointment->customerData['last_name'] : '';
            }

            if ( ! ($customerId > 0) || $identifier === 'email' ) {
                $email      = isset($lastAppointment->customerData['email']) ? $lastAppointment->customerData['email'] : '';
            }

            if ( ! ($customerId > 0) || $identifier === 'phone' ) {
                $phone      = isset($lastAppointment->customerData['phone']) ? $lastAppointment->customerData['phone'] : '';
            }

            $hideNameInput       = $customerId > 0;
            $hideSurnameInput    = $customerId > 0;
            $hideEmailInput      = $customerId > 0 && $identifier !== 'email';
            $hidePhoneInput      = $customerId > 0 && $identifier !== 'phone';

            if( $customerId > 0 && $identifier === 'email' )
                $disableEmailInput = true;

            if( $customerId > 0 && $identifier === 'phone' )
                $disablePhoneInput = true;
        }
        else if( is_user_logged_in() )
		{
            /**
             * Eger logged in olmush movcud customerdirse, bu halda datalarina (ad, soyad, email, nomre) baxir,
             * boshdularsa inputu achig saxlayir ki, editleye bilsin user,
             * yox eger bosh deyilse disable edir. Bosh olmayan datani backend update etmeyecek.
             * Meselen deyek ki, soyadi var DB-de. Input disabled gelecek. Adam inspectden disabled`i silib soyadi deyishse bele, backend updatelemeyecek;
             *
             * Burda user zaten login olub deye inputlari hide etmeye gerek yoxdu. Yuxaridaki case`de hide edirik, chunki,
             * security issue chixir eks halda ki, adam emaili yazir deyek, multi booking edib hemen userin ad, soyad ve nomresini gore biler.
             */
            $checkCustomerExists = Customer::where('user_id', get_current_user_id())->fetch();

            if ( $checkCustomerExists )
            {
                $name		= $checkCustomerExists->first_name;
                $surname	= $checkCustomerExists->last_name;
                $email		= $checkCustomerExists->email;
                $phone		= $checkCustomerExists->phone_number;

                $disableNameInput       = !empty($name);
                $disableSurnameInput    = !empty($surname);
                $disableEmailInput      = true;
                $disablePhoneInput      = !empty($phone);
            }
        }

		$emailIsRequired = Helper::getOption('set_email_as_required', 'on');
		$phoneIsRequired = Helper::getOption('set_phone_as_required', 'off');

		$howManyPeopleCanBring = false;

		foreach ( $appointmentObj->getAllTimeslots() AS $appointments )
		{
            if ( ! Service::getData( $appointmentObj->serviceId, "bring_people",1 ) )
                break;

			$timeslotInf = $appointments->getInfo()['info'];

            if( empty( $timeslotInf ) )
                continue;

			$availableSpaces = $timeslotInf['max_capacity'] - $timeslotInf['weight'] - 1;

			if( $howManyPeopleCanBring === false || $availableSpaces < $howManyPeopleCanBring )
			{
				$howManyPeopleCanBring = $availableSpaces;
			}
		}

        $parameters = [
            'service'                       => $appointmentObj->serviceId,

            'customer_identifier'           => $identifier,

            'customer_id'                   => $customerId,
            'name'				            => $name,
            'surname'			            => $surname,
            'email'				            => $email,
            'phone'				            => $phone,

            'disable_name_input'            => $disableNameInput,
            'disable_surname_input'         => $disableSurnameInput,
            'disable_email_input'           => $disableEmailInput,
            'disable_phone_input'           => $disablePhoneInput,

            'hide_name_input'               => $hideNameInput,
            'hide_surname_input'            => $hideSurnameInput,
            'hide_email_input'              => $hideEmailInput,
            'hide_phone_input'              => $hidePhoneInput,

            'can_change_email_input'        => $canChangeEmailInput,

            'email_is_required'	            => $emailIsRequired,
            'phone_is_required'	            => $phoneIsRequired,

            'default_phone_country_code'    => Helper::getOption('default_phone_country_code', ''),

            'show_only_name'                => Helper::getOption('separate_first_and_last_name', 'on') == 'off',

            'how_many_people_can_bring'     => $howManyPeopleCanBring
        ];

        /* Facebook Login button */
        $facebookLoginEnable = Helper::getOption('facebook_login_enable', 'off', false);
        $facebookAppId = Helper::getOption('facebook_app_id', '', false);
        $facebookAppSecret = Helper::getOption('facebook_app_secret', '', false);

        $showFacebookLoginButton = $facebookLoginEnable == 'on' && !empty( $facebookAppId ) && !empty( $facebookAppSecret );
        $facebookLoginButtonUrl = site_url() . '/?' . Helper::getSlugName() . '_action=facebook_login';

        $parameters['show_facebook_login_button'] = $showFacebookLoginButton;
        $parameters['facebook_login_button_url'] = $facebookLoginButtonUrl;
        /* end */

        /* Google Login button */
        $googleLoginEnable = Helper::getOption('google_login_enable', 'off', false);
        $googleAppId = Helper::getOption('google_login_app_id', '', false);
        $googleAppSecret = Helper::getOption('google_login_app_secret', '', false);

        $showGoogleLoginButton = $googleLoginEnable == 'on' && !empty( $googleAppId ) && !empty( $googleAppSecret );
        $googleLoginButtonUrl = site_url() . '/?' . Helper::getSlugName() . '_action=google_login';

        $parameters['show_google_login_button'] = $showGoogleLoginButton;
        $parameters['google_login_button_url'] = $googleLoginButtonUrl;
        /* end */

        $parameters = apply_filters('bkntc_booking_panel_information_step_parameters', $parameters);

        return $this->view('booking_panel.information', $parameters);
	}

    public function cart()
    {
        $currentIndex = Helper::_post( 'current' , 0 ,'int' );

        $appointmentRequests = Request::load();
        $appointmentObj = $appointmentRequests->currentRequest();

        return $this->view( 'booking_panel.cart', [ 'current_index' => $currentIndex ] );
    }

	public function confirm_details()
	{
        $appointmentRequests = Request::load();

        if( ! $appointmentRequests->validate() )
        {
            return $this->response(false, ['errors'=>$appointmentRequests->getErrors()]);
        }

        $appointmentObj = $appointmentRequests->currentRequest();

		$hide_confirm_step      = Helper::getOption('hide_confirm_details_step', 'off') == 'on';
		$hide_price_section	    = Helper::getOption('hide_price_section', 'off');
		$hideMethodSelecting    = $appointmentRequests->getSubTotal(true) <= 0 || Helper::getOption('disable_payment_options', 'off') == 'on';

        $arr = [
            PaymentGatewayService::getInstalledGatewayNames()
        ];

        foreach ( Request::appointments() as $appointmentRequestData )
        {
            $serviceCustomPaymentMethods = $appointmentRequestData->serviceInf->getData( 'custom_payment_methods' );
            $serviceCustomPaymentMethods = json_decode( $serviceCustomPaymentMethods ?? '[]' ,true );
            $arr[] = empty( $serviceCustomPaymentMethods ) ? PaymentGatewayService::getEnabledGatewayNames() : $serviceCustomPaymentMethods;
        }

        //todo: i don't remember why i check this variables with isset, because they aren't defined in this function,
        //      but it appears that it was defiend before, or got here from some buffer ( ob_start )
        if ( ! isset( $showDepositLabel ) ) $showDepositLabel = false;
        if ( ! isset( $depositPrice ) ) $depositPrice = 0;

        foreach ( $appointmentRequests->appointments AS $appointment )
        {
            if ( $appointment->hasDeposit() )
            {
                $showDepositLabel = true;
                $depositPrice += $appointment->getDepositPrice(true);
            }
            else
            {
                $depositPrice += $appointment->getSubTotal();
            }
        }

        $allowedPaymentMethods = call_user_func_array('array_intersect' , $arr);

        $hideMethodSelecting = apply_filters('bkntc_hide_method_selecting',$hideMethodSelecting , $appointmentRequests);

        return $this->view('booking_panel.confirm_details', [
			'appointmentData'           =>  $appointmentObj,
            'custom_payment_methods'    =>  $allowedPaymentMethods,
            'appointment_requests'      =>  $appointmentRequests,
			'hide_confirm_step'		    =>	$hide_confirm_step,
            'hide_payments'			    =>	$hideMethodSelecting,
            'hide_price_section'        =>  $hide_price_section == 'on',
            'has_deposit'               =>  $showDepositLabel,
            'deposit_price'             =>  $depositPrice,
            'has_duplicate_booking'     =>  $appointmentObj->hasDuplicateBooking()
		], [
            'has_deposit'               =>  $appointmentObj->hasDeposit()
        ] );
	}

    public function update_prices()
    {
        $appointmentRequests = Request::load();

        return $this->response( true, [
            'sum_price'             =>  $appointmentRequests->getSubTotal( true ),
            'sum_price_txt'         =>  Helper::price( $appointmentRequests->getSubTotal( true ) ),
            'prices_html'           =>  $appointmentRequests->getPricesHTML( true ),
        ] );
    }

    /**
     * @throws Exception
     */
    public function confirm()
	{
		if( ! Capabilities::tenantCan( 'receive_appointments' ) )
			return $this->response( false );

		try
		{
			AjaxHelper::validateGoogleReCaptcha();
		}
		catch ( Exception $e )
		{
			return $this->response( false, $e->getMessage() );
		}

        $ar = Request::load();

        if( ! $ar->validate() )
        {
            return $this->response( false, $ar->getFirstError() );
        }

        foreach ( Request::appointments() as $appointment )
        {
            if( $appointment->isRecurring() && empty( $appointment->recurringAppointmentsList ) )
            {
                return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
            }
        }

		do_action( 'bkntc_booking_step_confirmation_validation' );

		$paymentGateway = PaymentGatewayService::find( $ar->paymentMethod );

		if ( ( ! $paymentGateway || ! $paymentGateway->isEnabled( $ar ) ) && $ar->paymentMethod !== 'local' )
		{
			return $this->response( false, bkntc__( 'Payment method is not supported' ) );
		}

        if ( $ar->paymentMethod === 'local' && ! $paymentGateway->isEnabled( $ar ) )
        {
            return $this->response( false, bkntc__( 'Payment method is not supported' ) );
        }

        foreach ( Request::appointments() as $appointment )
        {
            $appointment->createOrUpdateCustomerData();
        }

		AppointmentService::createAppointment();

		$payment = $paymentGateway->doPayment( $ar );

		$responseStatus = is_bool( $payment->status ) ? $payment->status : false;
		$responseData   = is_array( $payment->data ) ? $payment->data : [];

        $firstAppointment = $ar->appointments[ 0 ];

		$responseData[ 'id' ]                  = $firstAppointment->getFirstAppointmentId();
		$responseData[ 'google_calendar_url' ] = AjaxHelper::addToGoogleCalendarURL( $firstAppointment );
        $responseData[ 'icalendar_url' ]       = AjaxHelper::addToiCalendarURL( $firstAppointment );
		$responseData[ 'payment_id' ]          = Request::self()->paymentId;
		$responseData[ 'payable_today' ]       = $ar->getPayableToday();
		$responseData[ 'sub_total' ]           = $ar->getSubTotal(true);
        $responseData[ 'customer_id' ]         = $firstAppointment->customerId;

        if( array_key_exists( 'remote_payment_id', $responseData ) )
        {
            Appointment::setData( $responseData[ 'id' ], 'remote_payment_id', $responseData[ 'remote_payment_id' ] );
        }

        if ( $firstAppointment->createdAt != null )
        {
            $timeLimit = Helper::getOption('max_time_limit_for_payment', 10);
            $responseData["expires_at"] = $timeLimit * 60;
        }

		return $this->response( $responseStatus, $responseData );
	}

    public function check_customer_exist()
    {
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

        if( $appointmentObj->customerId > 0 )
            $existingCustomerId = $appointmentObj->customerId;
        else
            $existingCustomerId = CustomerService::checkIfCustomerExists( $appointmentObj->customerDataObj );

        return $this->response( true, [
            'customer_id'    =>  $existingCustomerId
        ] );
    }

	public function delete_unpaid_appointment()
	{
		$paymentId                    = Helper::_post('payment_id', '', 'string');
        $appointmentList = Appointment::where('payment_id' , $paymentId )->where('payment_status' ,'<>','paid')->fetchAll();

		if( empty($appointmentList) )
		{
			return $this->response( true );
		}

        foreach ($appointmentList as $appointment)
        {
            AppointmentService::deleteAppointment( $appointment->id );
        }

		return $this->response( true );
	}

    // doit: bu evvel backendin ajaxin simulyasiya edirdi, baxaq umumi helpere cixaraq sonda
	public function get_available_times_all()
	{
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

        $search		= Helper::_post('q', '', 'string');
        $dayOfWeek	= Helper::_post('day_number', 1, 'int');

        if( $dayOfWeek != -1 )
        {
            $dayOfWeek -= 1;
        }

        $calendarServ = new CalendarService();

        $calendarServ->setStaffId( $appointmentObj->staffId )
            ->setServiceInf( $appointmentObj->serviceInf )
            ->setLocationId( $appointmentObj->locationId );

        return $this->response(true, [
            'results' => $calendarServ->getCalendarByDayOfWeek( $dayOfWeek, $search )
        ]);
	}

    public function get_recurring_available_dates()
    {
        $appointmentInf = Request::load()->currentRequest();

        if( Helper::isSaaSVersion() )
        {
            Permission::setTenantId( $appointmentInf->tenant_id );
        }

        $startDate = $appointmentInf->recurringStartDate;
        $endDate = $appointmentInf->recurringEndDate;

        $calendarData = new CalendarService( $startDate , $endDate );
        $calendarData->setStaffId( $appointmentInf->staffId )
            ->setLocationId( $appointmentInf->locationId )
            ->setServiceId( $appointmentInf->serviceId )
            ->setServiceExtras( $appointmentInf->getServiceExtras() )
            ->setShowExistingTimeSlots( true );
        $calendarData = $calendarData->getCalendar();

        $availableDates = array_keys( array_filter($calendarData['dates'], function ($item)
        {
            return ! empty($item);
        }));

        $availableDates = array_map(function ($availableDate){
            return Date::convertDateFormat($availableDate);
        },$availableDates);

        $appointments  =json_decode($appointmentInf->getData('appointments','[]','str'),1);

        $appointments = array_map(function ($arr){
            return Date::convertDateFormat($arr[0]);
        },$appointments);

        $availableDates = array_filter($availableDates,function ($date) use($appointments)
        {
           return ! in_array($date,$appointments);
        });

        $availableDates = array_values($availableDates);

        return $this->response(true, [ 'available_dates' => $availableDates ] );
    }

    public function get_available_times()
	{
		$ajax = new \BookneticApp\Backend\Appointments\Ajax();
        return $ajax->get_available_times( false );
	}

    // doit: bu evvel backendin ajaxin simulyasiya edirdi, baxaq umumi helpere cixaraq sonda
	public function get_day_offs()
	{
        $appointmentRequests = Request::load();

        $appointmentObj = $appointmentRequests->currentRequest();

        if(
            ! Date::isValid( $appointmentObj->recurringStartDate )
            || ! Date::isValid( $appointmentObj->recurringEndDate )
            || $appointmentObj->serviceId <= 0
        )
        {
            return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
        }

        $calendarService = new CalendarService( $appointmentObj->recurringStartDate, $appointmentObj->recurringEndDate );
        $calendarService->setDefaultsFrom( $appointmentObj );

        return $this->response( true, $calendarService->getDayOffs() );
	}

    public function change_status()
    {
        $token = Helper::_post('bkntc_token', 0, 'string');

        $response = AppointmentChangeStatus::validateToken($token);
        if ( $response !== true) return $this->response(false, $response);

        $tokenParts = explode('.', $token);
        $header = json_decode( base64_decode( $tokenParts[0] ), true );
        $payload = json_decode( base64_decode( $tokenParts[1] ), true );


        $id = $header['id'];
        $status = $payload['changeTo'];

        if ( ! array_key_exists($status, Helper::getAppointmentStatuses()) )
            return $this->response(false, [ 'error_msg' => bkntc__('Something went wrong.') ] );

        AppointmentService::setStatus($id, $status);

        return $this->response( true );
    }

    /**
     * @throws Exception
     */
    public function isPaymentDone()
    {
        $id = Post::int('id');

        if (empty($id))
            return $this->response(false, [ 'error_msg' => bkntc__('Invalid appointment id.') ] );

        $appointment = Appointment::get($id);

        if (empty($appointment))
            return $this->response(false, [ 'error_msg' => bkntc__('Appointment not found.') ] );

        $isDone = $appointment['payment_status'] === 'paid';

        return $this->response(true, [
            'isDone' => $isDone
        ]);
    }


	private function __getServiceCategoryName( $categId ): array
    {
		if( is_null( $this->categories ) )
		{
			$this->categories = ServiceCategory::fetchAll();
		}

		$categNames   = [];
        $categParents = 0;
		$attempts = 0;
		while( $categId > 0 && $attempts < 10 )
		{
			$attempts++;
			foreach ( $this->categories AS $category )
			{
				if( $category['id'] == $categId )
				{
					$categNames[] = $category['name'];
                    if ( $attempts == 1 ) $categParents = $category['parent_id'];
					$categId = $category['parent_id'];
					break;
				}
			}
		}

		return [
            'name'      => implode(' > ', array_reverse($categNames)),
            'parent_id' => $categParents,
        ];
	}
}
