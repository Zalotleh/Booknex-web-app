<?php

namespace BookneticApp\Backend\Services;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\Data;
use BookneticApp\Models\Holiday;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;

class Controller extends \BookneticApp\Providers\Core\Controller {

	public static function _delete( $deletedIds ) {
		Capabilities::must( 'services_delete' );

		//todo:// is_array-i sonra sil və bu method-a parameter type ver
		if ( empty( $deletedIds ) || ! is_array( $deletedIds ) ) {
			Helper::response( false, bkntc__( 'No service is selected' ) );

			return;
		}

		// check if appointment exist
		$appointmentCount = Appointment::where( 'service_id', $deletedIds )->count();

		if ( $appointmentCount > 0 ) {
			Helper::response( false, bkntc__( 'This service has some appointments scheduled. Please remove them first' ) );

			return;
		}

		ServiceExtra::where( 'service_id', $deletedIds )->delete();
		Holiday::where( 'service_id', $deletedIds )->delete();
		SpecialDay::where( 'service_id', $deletedIds )->delete();
		Timesheet::where( 'service_id', $deletedIds )->delete();
		Data::where( 'table_name', 'services' )->where( 'row_id', $deletedIds )->delete();
		Service::where( 'id', $deletedIds )->delete();
		ServiceStaff::where( 'service_id', $deletedIds )->delete();
	}

	public function index() {
		Capabilities::must( 'services' );

		$view = Helper::_get( 'view', Session::get( 'service_module_view', 'org' ), 'string', [ 'list', 'org' ] );

		if ( $view == 'org' ) {
			Session::set( 'service_module_view', 'org' );

			// collect services by category
			$servicesAll   = Service::fetchAll();
			$services      = [];
			$servicesCount = 0;
			foreach ( $servicesAll as $serviceInf ) {
				$id      = (int) $serviceInf[ 'id' ];
				$categId = (int) $serviceInf[ 'category_id' ];

				if ( ! isset( $services[ $categId ] ) ) {
					$services[ $categId ] = [];
				}

				$services[ $categId ][ $id ] = $serviceInf;
				$servicesCount ++;
			}

			// collect categories tree
			$categories = ServiceCategory::fetchAll();

			$categoriesTree = [];
			foreach ( $categories as $category ) {
				$parentId = $category[ 'parent_id' ];
				$categId  = $category[ 'id' ];

				if ( ! isset( $categoriesTree[ $parentId ] ) ) {
					$categoriesTree[ $parentId ] = [];
				}

				$categoriesTree[ $parentId ][ $categId ] = [
					'type'  => 'category',
					'name'  => $category[ 'name' ],
					'class' => isset( $services[ $categId ] ) ? 'horizontal' : 'vertical'
				];

				if ( isset( $services[ $categId ] ) ) {
					foreach ( $services[ $categId ] as $serviceId => $serviceInff ) {
						$categoriesTree[ $categId ][ $serviceId ] = [
							'type'      => 'service',
							'name'      => $serviceInff[ 'name' ],
							'is_active' => $serviceInff[ 'is_active' ],
							'class'     => 'vertical'
						];
					}
				}
			}

			$staff       = [];
			$getAllStaff = DB::DB()->get_results( "SELECT id, service_id, (SELECT `profile_image` FROM `" . DB::table( 'staff' ) . "` WHERE id=staff_id) AS profile_image FROM `" . DB::table( 'service_staff' ) . "` WHERE service_id IN (SELECT id FROM `" . DB::table( 'services' ) . "`" . DB::tenantFilter( 'WHERE' ) . ")", ARRAY_A );
			foreach ( $getAllStaff as $sStafInf ) {
				if ( ! isset( $staff[ (int) $sStafInf[ 'service_id' ] ] ) ) {
					$staff[ (int) $sStafInf[ 'service_id' ] ] = [];
				}

				$staff[ (int) $sStafInf[ 'service_id' ] ][] = $sStafInf;
			}

			$this->view( 'index', [
				'categories'                  => $categoriesTree,
				'services'                    => $services,
				'staff'                       => $staff,
				'number_of_services'          => $servicesCount,
				'can_do_dynamic_translations' => Capabilities::tenantCan( 'dynamic_translations' )
			] );
		} else {
			Session::set( 'service_module_view', 'list' );

			$dataTable = new DataTableUI( Service::leftJoin( 'category', 'name' ) );

			$dataTable->setIdFieldForQuery( Service::getField( 'id' ) );

			$dataTable->setTitle( bkntc__( 'Services' ) );
			$dataTable->addNewBtn( bkntc__( 'ADD SERVICE' ) );

			$dataTable->addAction( 'enable', bkntc__( 'Enable' ), function ( $ids ) {
				Service::where( 'id', 'in', $ids )->update( [ 'is_active' => 1 ] );
			}, AbstractDataTableUI::ACTION_FLAG_BULK );
			$dataTable->addAction( 'disable', bkntc__( 'Disable' ), function ( $ids ) {
				Service::where( 'id', 'in', $ids )->update( [ 'is_active' => 0 ] );
			}, AbstractDataTableUI::ACTION_FLAG_BULK );

			$dataTable->addAction( 'edit', bkntc__( 'Edit' ) );
			$dataTable->addAction( 'delete', bkntc__( 'Delete' ), [
				static::class,
				'_delete'
			], AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK );
			$dataTable->addAction( 'share', bkntc__( 'Share' ) );

			$dataTable->searchBy( [
				Service::getField( 'name' ),
				ServiceCategory::getField( 'name' ),
				Service::getField( 'price' )
			] );

			$dataTable->addColumns( bkntc__( 'ID' ), 'id' );
			$dataTable->addColumns( bkntc__( 'NAME' ), 'name' );
			$dataTable->addColumns( bkntc__( 'CATEGORY' ), 'category_name' );
			$dataTable->addColumns( bkntc__( 'PRICE' ), function ( $service ) {
				return Helper::price( $service[ 'price' ] );
			}, [ 'order_by_field' => 'price' ] );
			$dataTable->addColumns( bkntc__( 'DURATION' ), function ( $service ) {
				return Helper::secFormat( $service[ 'duration' ] * 60 );
			}, [ 'order_by_field' => 'duration' ] );

			$table = $dataTable->renderHTML();

			add_filter( 'bkntc_localization', function ( $localization ) {
				$localization[ 'link_copied' ] = bkntc__( 'Link copied!' );

				return $localization;
			} );

			$this->view( 'index-list', [ 'table' => $table ] );
		}
	}

	public function edit_order() {
		$servicesOrder = json_decode( Helper::getOption( "services_order", '[]' ), true );
		$allCategories = Helper::assocByKey( ServiceCategory::fetchAll(), 'id' );

		$categories = [];

		if ( ! empty( $servicesOrder ) && is_array( $servicesOrder ) ) {
			foreach ( $servicesOrder as $key => $value ) {
				if ( isset( $allCategories[ $key ] ) ) {
					$categories[ $key ] = $allCategories[ $key ];
				}
			}
		} else {
			$categories = $allCategories;
		}

		$categories = $this->flatToTree( $categories );

		$this->view( 'edit-order', [ 'categories' => $categories ] );
	}

	private function flatToTree( $elements, $parentId = 0 ) {

		$branch = array();

		foreach ( $elements as $element ) {
			if ( $element[ 'parent_id' ] == $parentId ) {

				$children = $this->flatToTree( $elements, $element[ 'id' ] );
				if ( $children ) {
					$element[ 'child' ] = $children;
				}
				$branch[] = $element;
			}
		}

		return $branch;
	}
}
