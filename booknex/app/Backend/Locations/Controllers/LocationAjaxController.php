<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\DTOs\Request\LocationRequest;
use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;
use BookneticApp\Backend\Locations\Exceptions\InvalidImageFormatException;
use BookneticApp\Backend\Locations\Exceptions\LocationLimitExceededException;
use BookneticApp\Backend\Locations\Exceptions\LocationNotFoundException;
use BookneticApp\Backend\Locations\Exceptions\NameRequiredException;
use BookneticApp\Backend\Locations\Services\LocationService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\TabUI;

class LocationAjaxController extends Controller {
	private LocationService $service;

	public function __construct() {
		$this->service = new LocationService();
	}

	/**
	 * @throws CapabilitiesException
	 * @throws LocationNotFoundException
	 */
	public function add_new() {
		$id = Post::int( 'id' );

		if ( $id > 0 ) {
			Capabilities::must( 'locations_edit' );

			$location = $this->service->get( $id );
		} else {
			Capabilities::must( 'locations_add' );

			try {
				$this->service->ensureLimitNotExceeded();
			} catch ( LocationLimitExceededException $e ) {
				$view = Helper::renderView( 'Base.view.modal.permission_denied', [
					'text' => $e->getMessage()
				] );

				return $this->response( true, [ 'html' => $view ] );
			};

			$location = LocationResponse::createEmpty();
		}

		TabUI::get( 'locations_add_new' )
		     ->item( 'details' )
		     ->setTitle( bkntc__( 'Location Details' ) )
		     ->addView( __DIR__ . '/view/tab/add_new_location_details.php' )
		     ->setPriority( 1 );

		return $this->modalView( 'add_new', $location );
	}

	/**
	 * @throws CapabilitiesException
	 * @throws InvalidImageFormatException
	 * @throws NameRequiredException
	 * @throws LocationLimitExceededException
	 */
	public function create() {
		Capabilities::must( 'locations_add' );

		$request = $this->prepareSaveRequestDTO();

		$id = $this->service->create( $request );

		return $this->response( true, [
			'id' => $id
		] );
	}

	/**
	 * @throws CapabilitiesException
	 * @throws NameRequiredException
	 * @throws LocationNotFoundException|InvalidImageFormatException
	 */
	public function update() {
		Capabilities::must( 'locations_edit' );

		$id      = Post::int( 'id' );
		$request = $this->prepareSaveRequestDTO();

		$id = $this->service->update( $id, $request );

		return $this->response( true, [
			'id' => $id
		] );
	}

	/**
	 * @throws NameRequiredException
	 */
	public function prepareSaveRequestDTO(): LocationRequest {
		$name              = Post::string( 'location_name' );
		$address           = Post::string( 'address' );
		$phone             = Post::string( 'phone' );
		$note              = Post::string( 'note' );
		$latitude          = Post::string( 'latitude' );
		$longitude         = Post::string( 'longitude' );
		$addressComponents = Post::string( 'address_components' );

		$request = new LocationRequest();

		$request->setName( $name )
		        ->setAddress( $address )
		        ->setPhone( $phone )
		        ->setNote( $note )
		        ->setLatitude( $latitude )
		        ->setLongitude( $longitude )
		        ->setAddressComponents( $addressComponents );

		return $request;
	}

	/**
	 * @throws CapabilitiesException
	 * @throws LocationNotFoundException
	 */
	public function toggleVisibility() {
		Capabilities::must( 'locations_edit' );

		$id = Post::int( 'id' );

		$this->service->toggleVisibility( $id );

		return $this->response( true );
	}
}