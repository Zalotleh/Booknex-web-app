<?php

namespace BookneticApp\Backend\Services;

use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\ExtraCategory;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Translation;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\UI\TabUI;
use Exception;

class Ajax extends \BookneticApp\Providers\Core\Controller {

	public function add_new() {
		$sid        = Helper::_post( 'id', '0', 'integer' );
		$categoryId = Helper::_post( 'category_id', '0', 'integer' );

		$serviceStuff = [];

        $extras = [];
        $serviceExtraLimitations = ['min' => 0, 'max' => 0];
        $serviceExtraMinLimitEnabled = false;
        $serviceExtraMaxLimitEnabled = false;

		if ( $sid > 0 ) {
			Capabilities::must( 'services_edit' );

			$serviceInfo = Service::get( $sid );

			if ( ! $serviceInfo ) {
				return $this->response( false, 'Selected Service not found!' );
			}

			$categoryId = $serviceInfo[ 'category_id' ];

			$getServiceStuff = ServiceStaff::where( 'service_id', $sid )->fetchAll();
			foreach ( $getServiceStuff as $staffInf ) {
				$serviceStuff[ (string) $staffInf[ 'staff_id' ] ] = $staffInf;
			}

			$specialDays = SpecialDay::where( 'service_id', $sid )->fetchAll();
            $extras      = ServiceExtra::where( 'service_id', $sid )->fetchAll();

			$customPaymentMethods        = (string)$serviceInfo->getData( 'custom_payment_methods' );
			$customPaymentMethods        = json_decode( $customPaymentMethods );
			$customPaymentMethodsEnabled = true;

            $onlyVisibleToStaff          = $serviceInfo->getData( 'only_visible_to_staff' );

            $serviceExtraLimitationsData = $serviceInfo->getData('service_extra_limitations', '');

            if (!empty($serviceExtraLimitationsData)) {
                $serviceExtraLimitations = json_decode($serviceExtraLimitationsData, true);
            }

            $serviceExtraMinLimitEnabled = !empty($serviceExtraLimitations['min']);
            $serviceExtraMaxLimitEnabled = !empty($serviceExtraLimitations['max']);

        } else {
			Capabilities::must( 'services_add' );
			$allowedLimit = Capabilities::getLimit( 'services_allowed_max_number' );

			if ( $allowedLimit > - 1 && Service::count() >= $allowedLimit ) {
				$view = Helper::renderView( 'Base.view.modal.permission_denied', [
					'text' => bkntc__( 'You can\'t add more than %d Service. Please upgrade your plan to add more Service.', [ $allowedLimit ] )
				] );

				return $this->response( true, [ 'html' => $view ] );
			}

			$serviceInfo        = new Collection();
			$specialDays        = [];
			$onlyVisibleToStaff = false;
		}

		$timesheet = DB::DB()->get_row(
			DB::DB()->prepare( 'SELECT service_id, timesheet FROM ' . DB::table( 'timesheet' ) . ' WHERE ((service_id IS NULL AND staff_id IS NULL) OR (service_id=%d)) ' . DB::tenantFilter() . ' ORDER BY service_id DESC LIMIT 0,1', [ $sid ] ),
			ARRAY_A
		);

		$categories      = ServiceCategory::fetchAll();
		$staff           = Staff::fetchAll();
		$services        = Service::fetchAll();
		$extraCategories = ExtraCategory::select( [ 'id', 'name' ] )->fetchAll();

		TabUI::get( 'services_add' )
		     ->item( 'details' )
		     ->setTitle( bkntc__( 'SERVICE DETAILS' ) )
		     ->addView( __DIR__ . '/view/tab/add_new_service_details.php', [], 1 )
		     ->setPriority( 1 );

        TabUI::get( 'services_add' )
             ->item( 'staff' )
             ->setTitle( bkntc__( 'STAFF' ) )
             ->addView( __DIR__ . '/view/tab/add_new_staff.php' )
             ->setPriority( 2 );

		TabUI::get( 'services_add' )
		     ->item( 'timesheet' )
		     ->setTitle( bkntc__( 'TIME SHEET' ) )
		     ->addView( __DIR__ . '/view/tab/add_new_timesheet.php' )
		     ->setPriority( 3 );

		TabUI::get( 'services_add' )
		     ->item( 'extras' )
		     ->setTitle( bkntc__( 'EXTRAS' ) )
		     ->addView( __DIR__ . '/view/tab/add_new_extras.php' )
		     ->setPriority( 4 );

		TabUI::get( 'services_add' )
		     ->item( 'settings' )
		     ->setTitle( bkntc__( 'SETTINGS' ) )
		     ->addView( __DIR__ . '/view/tab/add_new_settings.php' )
		     ->setPriority( 5 );

		$timeS = empty( $timesheet[ 'timesheet' ] ) ? [
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ],
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ],
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ],
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ],
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ],
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ],
			[ "day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" => [] ]
		] : json_decode( $timesheet[ 'timesheet' ], true );

		if ( empty( $customPaymentMethods ) ) {
			$customPaymentMethods        = PaymentGatewayService::getEnabledGatewayNames();
			$customPaymentMethodsEnabled = false;
		}

		return $this->modalView( 'add_new', [
			'id'                             => $sid,
			'only_visible_to_staff'          => $onlyVisibleToStaff,
			'custom_payment_methods_enabled' => $customPaymentMethodsEnabled,
			'custom_payment_methods'         => $customPaymentMethods,
            'service_extra_min_limit_enabled' => $serviceExtraMinLimitEnabled,
            'service_extra_max_limit_enabled' => $serviceExtraMaxLimitEnabled,
            'service_extra_limitations'      => $serviceExtraLimitations,
			'service'                        => $serviceInfo,
			'categories'                     => $categories,
			'staff'                          => $staff,
			'service_staff'                  => $serviceStuff,
			'services'                       => $services,
			'category'                       => $categoryId,
			'extra_categories'               => $extraCategories,
			'special_days'                   => $specialDays,
			'extras'                         => $extras,
			'timesheet'                      => $timeS,
			'has_specific_timesheet'         => isset( $timesheet[ 'timesheet' ] ) && $timesheet[ 'service_id' ] > 0,
			'bring_people'                   => Service::getData( $sid, "bring_people", 1 )
		] );
	}

	/**
	 * @throws CapabilitiesException|Exception
	 */
	public function save_service() {
		$serviceObject = new ServiceObject();

		$serviceObject->hasCapability();
		$serviceObject->checkAllowedServiceLimit();
		$serviceObject->validate();
        $serviceObject->handleServiceExtraLimitations();
		$serviceObject->checkIfRecurringEnabled();
		$serviceObject->initData();
		$serviceObject->parseWeeklySchedule();
		$serviceObject->parseStaffList();
		$serviceObject->handleImage();
		$serviceObject->handleCustomPaymentMethods();
		$serviceObject->applyFilters();
		$serviceObject->save();
		$serviceObject->saveOptions();
		$serviceObject->saveSettings();
		$serviceObject->saveWeeklySchedule();
		$serviceObject->saveSpecialDays();
		$serviceObject->saveServiceStaff();
		$serviceObject->saveServiceExtras();
		$serviceObject->saveOrderOption();
		$serviceObject->saveTranslations();

		return $this->response( true, [ 'id' => $serviceObject->getId() ] );
	}

	public function remove_image() {
		$id = Helper::_post( 'id', '0', 'int' );

		$service = Service::whereId( $id )->select( 'image' )->fetch();

		if ( empty( $service[ 'image' ] ) ) {
			return $this->response( true );
		}

		$filePath = Helper::uploadedFile( $service[ 'image' ], 'Services' );

		if ( is_file( $filePath ) && is_writable( $filePath ) ) {
			unlink( $filePath );
		}

		Service::whereId( $id )->update( [ 'image' => null ] );

		return $this->response( true );
	}

	public function service_delete() {
		Capabilities::must( 'services_delete' );

		$id = Helper::_post( 'id', '0', 'integer' );

		if ( ! ( $id > 0 ) ) {
			return $this->response( false );
		}

		Controller::_delete( [ $id ] );

		return $this->response( true );
	}

	public function hide_service() {
		Capabilities::must( 'services_edit' );

		$service_id = Helper::_post( 'service_id', '', 'int' );

		if ( ! ( $service_id > 0 ) ) {
			return $this->response( false );
		}

		$service = Service::get( $service_id );

		if ( ! $service ) {
			return $this->response( false );
		}

		$new_status = $service[ 'is_active' ] == 1 ? 0 : 1;

		Service::where( 'id', $service_id )->update( [ 'is_active' => $new_status ] );

		return $this->response( true );
	}


	public function add_new_category() {
		Capabilities::must( 'services_add_category' );

		$categories = ServiceCategory::fetchAll();

		return $this->modalView( 'add_new_category', [
			'categories' => $categories,
		] );
	}

	public function category_delete() {
		Capabilities::must( 'services_delete_category' );

		$id = Helper::_post( 'id', '0', 'integer' );

		if ( ! ( $id > 0 ) ) {
			return $this->response( false );
		}

		// fetch all categories
		$allCategories  = ServiceCategory::fetchAll();
		$categoriesTree = [];
		foreach ( $allCategories as $category ) {
			$parentId = $category[ 'parent_id' ];
			$categId  = $category[ 'id' ];

			$categoriesTree[ $parentId ][ $categId ] = $category[ 'name' ];
		}

		// collect all sub categories
		$subCategories = [];

		self::getSubCategs( $id, $categoriesTree, $subCategories );

		if ( ! empty( $subCategories ) ) {
			return $this->response( false, bkntc__( 'Firstly remove sub categories!' ) );
		}

		// raw query used to prevent user role manager ( or other addons/functionalities ) filter allowing to delete categories
		// even though other staffs have services in given category
		$servicesTable = DB::table( 'services' );
		$services      = DB::DB()->get_results( DB::DB()->prepare( "SELECT * FROM {$servicesTable} WHERE category_id = %d", $id ), ARRAY_A );

		if ( $services ) {
			return $this->response( false, bkntc__( 'There are some services in this category.' ) );
		}

		ServiceCategory::where( 'id', $id )->delete();

		return $this->response( true );
	}

	private static function getSubCategs( $id, &$categories, &$subCategories ) {
		if ( isset( $categories[ $id ] ) ) {
			foreach ( $categories[ $id ] as $cId => $cName ) {
				$subCategories[] = (int) $cId;
				self::getSubCategs( $cId, $categories, $subCategories );
			}
		}
	}

	public function category_save() {
		$id = Helper::_post( 'id', 0, 'integer' );

		if ( $id > 0 ) {
			Capabilities::must( 'services_edit_category' );
		} else {
			Capabilities::must( 'services_add_category' );
		}

		$name   = Helper::_post( 'name', '', 'string' );
		$parent = Helper::_post( 'parent_id', '0', 'integer' );

		if ( empty( $name ) || ( $id == 0 && is_null( $parent ) ) ) {
			return $this->response( false );
		}

		if ( $id > 0 ) {
			ServiceCategory::where( 'id', $id )->update( [ 'name' => $name ] );
		} else {
			$checkIfNameExist = ServiceCategory::withoutGlobalScope('my_service_categories')->where( 'name', $name )->where( 'parent_id', $parent )->where( 'id', '!=', $id )->fetch();

			if ( $checkIfNameExist ) {
				return $this->response( false, bkntc__( 'This category is already exist! Please choose an other name.' ) );
			}

			ServiceCategory::insert( [
				'name'      => $name,
				'parent_id' => $parent
			] );

			$id = DB::lastInsertedId();

			$servicesOrder = json_decode( Helper::getOption( "services_order", '[]' ), true );
			if ( ! empty ( $servicesOrder ) && is_array( $servicesOrder ) ) {
				if ( $parent == 0 ) {
					$servicesOrder[ $id ] = [];
				} else {
					$newServiceOrder = [];
					foreach ( $servicesOrder as $k => $items ) {
						$newServiceOrder[ $k ] = $items;
						if ( $k == $parent ) {
							$newServiceOrder[ $id ] = [];
						}
					}
					$servicesOrder = $newServiceOrder;
				}
				Helper::setOption( "services_order", json_encode( $servicesOrder ) );
			}
		}

		ServiceCategory::handleTranslation( $id );

		return $this->response( true, [ 'id' => $id ] );
	}

	public function save_extra() {
		$id = Helper::_post( 'id', '0', 'int' );

		if ( $id > 0 ) {
			Capabilities::must( 'services_edit_extra' );
		} else {
			Capabilities::must( 'services_add_extra' );
		}

		$service_id    = Helper::_post( 'service_id', '0', 'int' );
		$name          = Helper::_post( 'name', '', 'string' );
		$duration      = Helper::_post( 'duration', '0', 'int' );
		$hide_duration = Helper::_post( 'hide_duration', '0', 'int', [ '1', '0' ] );
		$price         = Helper::_post( 'price', null, 'price' );
		$hide_price    = Helper::_post( 'hide_price', '0', 'int', [ '1', '0' ] );
		$min_quantity  = Helper::_post( 'min_quantity', '0', 'int' );
		$max_quantity  = Helper::_post( 'max_quantity', '0', 'int' );
		$category_id   = Helper::_post( 'category_id', null, 'int' );
		$extra_notes   = Helper::_post( 'extra_notes', null, 'string' );

		$allowedLimit = Capabilities::getLimit( 'service_extras_allowed_max_number' );

		if ( $allowedLimit > - 1 && ServiceExtra::count() >= $allowedLimit && $id == 0 ) {
			return $this->response( false, bkntc__( 'You can\'t add more than %d Service Extras. Please upgrade your plan to add more Service Extra.', [ $allowedLimit ] ) );
		}

		if ( empty( $name ) || is_null( $price ) ) {
			return $this->response( false, bkntc__( 'Please fill in all required fields correctly!' ) );
		}

		if ( ! ( $min_quantity >= 0 ) ) {
			return $this->response( false, bkntc__( 'Min quantity can not be less than zero!' ) );
		}

		if ( ! ( $max_quantity > 0 ) ) {
			return $this->response( false, bkntc__( 'Max quantity can not be zero!' ) );
		}

		if ( ! ( $max_quantity >= $min_quantity ) ) {
			return $this->response( false, bkntc__( 'Max quantity can not be less than Min quantity!' ) );
		}

		$price = Math::floor( $price );

		$image = '';

		if ( isset( $_FILES[ 'image' ] ) && is_string( $_FILES[ 'image' ][ 'tmp_name' ] ) ) {
			$path_info = pathinfo( $_FILES[ "image" ][ "name" ] );
			$extension = strtolower( $path_info[ 'extension' ] );

			if ( ! in_array( $extension, [ 'jpg', 'jpeg', 'png' ] ) ) {
				return $this->response( false, bkntc__( 'Only JPG and PNG images allowed!' ) );
			}

			$image     = md5( base64_encode( rand( 1, 9999999 ) . microtime( true ) ) ) . '.' . $extension;
			$file_name = Helper::uploadedFile( $image, 'Services' );

			move_uploaded_file( $_FILES[ 'image' ][ 'tmp_name' ], $file_name );
		}

		if ( empty( $extra_notes ) ) {
			$extra_notes = null;
		}

		$sqlData = [
			'name'          => $name,
			'price'         => $price,
			'hide_price'    => $hide_price,
			'duration'      => $duration,
			'hide_duration' => $hide_duration,
			'min_quantity'  => $min_quantity,
			'max_quantity'  => $max_quantity,
			'image'         => $image,
			'service_id'    => $service_id > 0 ? $service_id : null,
			'category_id'   => $category_id,
			'notes'         => $extra_notes,
		];

		if ( $id > 0 ) {
			if ( empty( $image ) ) {
				unset( $sqlData[ 'image' ] );
			} else {
				$getOldInf = ServiceExtra::get( $id );

				if ( ! empty( $getOldInf[ 'image' ] ) ) {
					$filePath = Helper::uploadedFile( $getOldInf[ 'image' ], 'Services' );

					if ( is_file( $filePath ) && is_writable( $filePath ) ) {
						unlink( $filePath );
					}
				}
			}

			ServiceExtra::where( 'id', $id )->update( $sqlData );
		} else {
			$sqlData[ 'is_active' ] = 1;

			ServiceExtra::insert( $sqlData );
			$id = DB::lastInsertedId();
		}

		ServiceExtra::handleTranslation( $id );

		return $this->response( true, [
			'id'       => $id,
			'price'    => Helper::price( $price ),
			'duration' => ! $duration ? '-' : Helper::secFormat( $duration * 60 )
		] );
	}

	public function delete_extra() {
		Capabilities::must( 'services_delete_extra' );

		$id = Helper::_post( 'id', '0', 'int' );

		if ( ! ( $id > 0 ) ) {
			return $this->response( false );
		}

		// check if appointment exist
		$checkAppointments = AppointmentExtra::where( 'extra_id', $id )->fetch();
		if ( $checkAppointments ) {
			return $this->response( false, bkntc__( 'This extra is using some Appointments (ID: %d). Firstly remove them!', [ (int) $checkAppointments[ 'appointment_id' ] ] ) );
		}

		ServiceExtra::where( 'id', $id )->delete();

		Translation::where( [
			'table_name' => 'service_extras',
			'row_id'     => $id
		] )->delete(); // TODO: Umumi Translator traitine cixmalidir

		return $this->response( true );
	}

	public function copy_extras() {
		Capabilities::must( 'services_add' );

		$val     = Helper::_post( 'val', '', 'int' );
		$extraId = Helper::_post( 'extraId', '', 'int' );

		$extra = ServiceExtra::where( 'id', $extraId )->fetch();

		$extraTranslations = Translation::where( [
			'row_id'      => $extraId,
			'table_name'  => 'service_extras',
			'column_name' => 'name'
		] )->fetchAll();

		//todo: our orWhere() function needs to be compatible with array insertions, first combinator can be 'OR' but our QueryBuilder works without parentheses so this can create unexpected results.
		//polyfill
		$extraTranslations = array_merge( $extraTranslations, Translation::where( [
			'row_id'      => $extraId,
			'table_name'  => 'service_extras',
			'column_name' => 'notes'
		] )->fetchAll() );

		$sqlData = [
			'name'          => $extra[ 'name' ],
			'price'         => $extra[ 'price' ],
			'hide_price'    => $extra[ 'hide_price' ],
			'duration'      => $extra[ 'duration' ],
			'hide_duration' => $extra[ 'hide_duration' ],
			'is_active'     => $extra[ 'is_active' ],
			'min_quantity'  => $extra[ 'min_quantity' ],
			'max_quantity'  => $extra[ 'max_quantity' ],
			'image'         => $extra[ 'image' ],
			'notes'         => $extra[ 'notes' ]
		];

		if ( $val == 1 ) {
			$category = Service::select( [ 'category_id' ] )->where( 'id', $extra[ 'service_id' ] )->fetch();
			$services = Service::select( [ 'id' ] )->where( 'category_id', $category[ 'category_id' ] )->fetchAll();

			if ( is_null( $category ) || is_null( $services ) ) {
				return $this->response( false, bkntc__( 'There is no category or service attached with this extra!' ) );
			}

		} else {
			$services = Service::fetchAll();
		}

		foreach ( $services as $service ) {
			$inserted = ServiceExtra::where( 'service_id', $service[ 'id' ] )->where( 'name', $sqlData[ 'name' ] )->fetchAll();

			if ( empty( $inserted ) ) {
				$sqlData[ 'service_id' ] = $service[ 'id' ];
				ServiceExtra::insert( $sqlData );
				$id = ServiceExtra::lastId();
				foreach ( $extraTranslations as $translation ) {
					Translation::insert( [
						'table_name'  => 'service_extras',
						'column_name' => $translation[ 'column_name' ],
						'row_id'      => $id,
						'locale'      => $translation[ 'locale' ],
						'value'       => $translation[ 'value' ]
					] );
				}
			}
		}

		return $this->response( true, [ 'msg' => bkntc__( 'Success!' ) ] );
	}

	public function hide_extra() {
		Capabilities::must( 'services_edit_extra' );

		$id     = Helper::_post( 'id', '0', 'int' );
		$status = Helper::_post( 'status', '1', 'string', [ '0', '1' ] );

		if ( ! ( $id > 0 ) ) {
			return $this->response( false );
		}

		ServiceExtra::where( 'id', $id )->update( [ 'is_active' => $status ] );

		return $this->response( true );
	}

	public function get_extra_data() {
		Capabilities::must( 'services' );

		$id = Helper::_post( 'id', '0', 'int' );

		if ( ! ( $id > 0 ) ) {
			return $this->response( false );
		}

		$extraInf = ServiceExtra::get( $id );

		if ( ! $extraInf ) {
			return $this->response( false, bkntc__( 'Requested Service Extra not found!' ) );
		}

		return $this->response( true, [
			'id'            => $id,
			'name'          => htmlspecialchars( $extraInf[ 'name' ] ),
			'price'         => Helper::price( Math::floor( $extraInf[ 'price' ] ), false ),
			'hide_price'    => (int) $extraInf[ 'hide_price' ],
			'duration'      => (int) $extraInf[ 'duration' ],
			'duration_txt'  => Helper::secFormat( (int) $extraInf[ 'duration' ] * 60 ),
			'hide_duration' => (int) $extraInf[ 'hide_duration' ],
			'image'         => Helper::profileImage( $extraInf[ 'image' ], 'Services' ),
			'min_quantity'  => null ? 0 : (int) $extraInf[ 'min_quantity' ],
			'max_quantity'  => (int) $extraInf[ 'max_quantity' ],
			'category_id'   => (int) $extraInf[ 'category_id' ],
			'notes'         => htmlspecialchars( $extraInf[ 'notes' ] ),
		] );
	}

	public function get_available_times_all() {
		$search = Helper::_post( 'q', '', 'string' );

		$timeslotLength = Helper::getOption( 'timeslot_length', 5 );

		$tEnd       = Date::epoch( '00:00:00', '+1 days' );
		$timeCursor = Date::epoch( '00:00:00' );
		$data       = [];
		while ( $timeCursor <= $tEnd ) {
			$timeId   = Date::timeSQL( $timeCursor );
			$timeText = Date::time( $timeCursor );

			if ( $timeCursor == $tEnd && $timeId = "00:00" ) {
				$timeText = "24:00";
				$timeId   = "24:00";
			}

			$timeCursor += $timeslotLength * 60;

			// search...
			if ( ! empty( $search ) && strpos( $timeText, $search ) === false ) {
				continue;
			}

			$data[] = [
				'id'   => $timeId,
				'text' => $timeText
			];
		}

		return $this->response( true, [ 'results' => $data ] );
	}

	public function get_times_with_format() {
		$search       = Helper::_post( 'q', '', 'string' );
		$exclude_zero = Helper::_post( 'exclude_zero', '', 'string' );

		$include_defaults = Helper::_post( 'include_defaults', false, 'boolean' );

		$timeslotLength = Helper::getOption( 'timeslot_length', 5 );

		// $tEnd = 7 * 24 * 3600;
		$tEnd       = 31 * 24 * 3600;
		$timeCursor = 0;
		$data       = [];

		if ( $include_defaults ) {
			$data[] = [
				'id'   => 0,
				'text' => bkntc__( 'Default' )
			];

			$data[] = [
				'id'   => - 1,
				'text' => bkntc__( 'Slot length as service duration' )
			];
		}

		while ( $timeCursor <= $tEnd ) {
			if ( $exclude_zero == 'true' && $timeCursor <= 0 ) {
				$timeCursor += $timeslotLength * 60;
				continue;
			}

			$timeText = Helper::secFormat( $timeCursor );

			// search...
			if ( ! ( ! empty( $search ) && strpos( $timeText, $search ) === false ) ) {
				$data[] = [
					'id'   => $timeCursor / 60,
					'text' => $timeText
				];
			}

			if ( $timeCursor >= 24 * 3600 ) {
				$timeCursor += 24 * 3600;
			} else {
				$timeCursor += $timeslotLength * 60;
			}
		}

		return $this->response( true, [ 'results' => $data ] );
	}

	public function save_services_order() {
		$activeCategory = Helper::_post( "active_category", "0", "int" );
		$servicesOrder  = Helper::_post( "service_order", "", "string" );
		$categoryOrder  = Helper::_post( "category_order", "", "string" );

		$servicesOrder = json_decode( $servicesOrder );
		$categoryOrder = json_decode( $categoryOrder );
		if ( ! is_array( $servicesOrder ) || ! is_array( $categoryOrder ) || $activeCategory === 0 ) {
			return $this->response( false );
		}

		$prev_option = json_decode( Helper::getOption( "services_order", "[]" ), true );
		$new_data    = [];

		foreach ( $categoryOrder as $category_id ) {
			if ( (int) $category_id === $activeCategory ) {
				$new_data[ $category_id ] = $servicesOrder;
			} else if ( isset( $prev_option[ $category_id ] ) ) {
				$new_data[ $category_id ] = $prev_option[ $category_id ];
			} else {
				$services   = Service::select( [ 'id' ] )->where( "category_id", $category_id )->fetchAll();
				$serviceIds = array_map( function ( $item ) {
					return $item->id;
				}, $services );

				$new_data[ $category_id ] = $serviceIds;
			}
		}

		Helper::setOption( "services_order", json_encode( $new_data ) );

		return $this->response( true );
	}

	public function get_services_order() {
		$serviceCategory = Helper::_post( "id", "0", "int" );
		$servicesOrder   = json_decode( Helper::getOption( "services_order", '[]' ), true );
		$services        = [];

		if ( ! empty( $servicesOrder ) && is_array( $servicesOrder ) ) {
			$allServices  = Helper::assocByKey( Service::where( "category_id", $serviceCategory )->fetchAll(), 'id' );
			$currentOrder = isset( $servicesOrder[ $serviceCategory ] ) ? $servicesOrder[ $serviceCategory ] : [];
			foreach ( $currentOrder as $value ) {
				if ( isset( $allServices[ $value ] ) ) {
					$services[] = $allServices[ $value ];
				}
			}
		} else {
			$services = Service::where( "category_id", $serviceCategory )->fetchAll();
		}

		return $this->response( true, [
			"services" => $services
		] );
	}

	public function reset_order() {
		Helper::deleteOption( 'services_order' );

		return $this->response( true, [
			'message' => bkntc__( 'Success' ),
		] );
	}

	public function add_new_extra_category() {
		$name = Helper::_post( 'name', '', 'string' );

		if ( empty( $name ) ) {
			return $this->response( false );
		}

		$checkIfNameExist = ExtraCategory::where( 'name', $name )->fetch();

		if ( $checkIfNameExist ) {
			return $this->response( false, bkntc__( 'This category is already exist! Please choose an other name.' ) );
		}

		ExtraCategory::insert( [
			'name' => $name,
		] );

		$id = DB::lastInsertedId();

		return $this->response( true, [ 'id' => $id ] );
	}

	public function delete_extra_category() {
		$id = Helper::_post( 'id', 0, 'integer' );

		$extraCategory = ExtraCategory::where( 'id', $id )->fetch();

		if ( ! $extraCategory ) {
			return $this->response( false, bkntc__( 'Extra category not found.' ) );
		}

		$services = ServiceExtra::where( 'category_id', $id )->fetch();

		if ( $services ) {
			return $this->response( false, bkntc__( 'Firstly delete extra services.' ) );
		}

		ExtraCategory::where( 'id', $id )->delete();

		return $this->response( true );
	}

	public function get_extra_for_create_modal() {
		return $this->response( true, [
			'image'            => Helper::profileImage( '', 'Services' ),
			'extra_categories' => ExtraCategory::select( [ 'id', 'name' ] )->fetchAll()
		] );
	}

	public function delete_extras() {
		$extrasStr = Helper::_post( 'extras', '', 'string' );

		if ( ! $extrasStr ) {
			return $this->response( true );
		}

		$extrasArr = json_decode( $extrasStr, true );

		if ( ! $extrasArr ) {
			return $this->response( true );
		}

		ServiceExtra::where( 'id', $extrasArr )
		            ->where( 'service_id', 'is', null )
		            ->delete();

		return $this->response( true );
	}

	public function dismiss_custom_duration_promotion() {
		Helper::setOption( 'custom_duration_hide_promotional_content', 1 );

		return $this->response( true );
	}
}
