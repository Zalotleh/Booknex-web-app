<?php

namespace BookneticApp\Backend\Locations\Services;

use BookneticApp\Backend\Locations\DTOs\Request\LocationRequest;
use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;
use BookneticApp\Backend\Locations\Exceptions\InvalidImageFormatException;
use BookneticApp\Backend\Locations\Exceptions\InvalidLocationIdException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasAppointmentsException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasStaffMembersException;
use BookneticApp\Backend\Locations\Exceptions\LocationLimitExceededException;
use BookneticApp\Backend\Locations\Exceptions\LocationNotFoundException;
use BookneticApp\Backend\Locations\Mappers\LocationMapper;
use BookneticApp\Backend\Locations\Repositories\LocationAppointmentRepository;
use BookneticApp\Backend\Locations\Repositories\LocationRepository;
use BookneticApp\Backend\Locations\Repositories\LocationStaffRepository;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;

class LocationService {

	private LocationRepository $repository;

	private LocationStaffRepository $staffRepository;

	private LocationAppointmentRepository $appointmentRepository;

	public function __construct() {
		$this->repository            = new LocationRepository();
		$this->staffRepository       = new LocationStaffRepository();
		$this->appointmentRepository = new LocationAppointmentRepository();
	}

	/**
	 * @param int[] $ids
	 *
	 * @throws LocationHasAppointmentsException
	 * @throws LocationHasStaffMembersException
	 */
	public function deleteAll( array $ids ): void {
		$staffCount = $this->staffRepository->getStaffCount( $ids );

		if ( $staffCount > 0 ) {
			throw new LocationHasStaffMembersException();
		}

		$appointmentsCount = $this->appointmentRepository->getAppointmentCount( $ids );

		if ( $appointmentsCount > 0 ) {
			throw new LocationHasAppointmentsException();
		}

		$locations = $this->repository->getAll( $ids );

		foreach ( $locations as $location ) {
			if ( ! empty( $location->image ) ) {
				$this->cleanUpOldImage( $location->image );
			}
		}

		$this->staffRepository->deleteLocations( $ids );
		$this->repository->deleteAll( $ids );
	}

	/**
	 * @param int[] $ids
	 */
	public function enable( array $ids ) {
		$this->repository->updateAll( $ids, [
			'is_active' => 1
		] );
	}

	/**
	 * @param int[] $ids
	 */
	public function disable( array $ids ) {
		$this->repository->updateAll( $ids, [
			'is_active' => 0
		] );
	}

	/**
	 * @param int $id
	 *
	 * @return LocationResponse
	 * @throws LocationNotFoundException
	 */
	public function get( int $id ): LocationResponse {
		$location = $this->repository->get( $id );

		if ( empty( $location ) ) {
			throw new LocationNotFoundException( $id );
		}

		return LocationMapper::toResponse( $location );
	}

	/**
	 * @throws LocationLimitExceededException
	 */
	public function ensureLimitNotExceeded(): void {
		$locationCount = $this->repository->count();
		$allowedLimit  = Capabilities::getLimit( 'locations_allowed_max_number' );

		if ( $allowedLimit > - 1 && $locationCount >= $allowedLimit ) {
			throw new LocationLimitExceededException( $allowedLimit );
		}
	}

	/**
	 * @throws LocationLimitExceededException
	 * @throws InvalidImageFormatException
	 */
	public function create( LocationRequest $request ): int {
		$this->ensureLimitNotExceeded();

		$image = $this->handleImageUpload();

		$data = [
			'name'               => $request->getName(),
			'address'            => $request->getAddress(),
			'phone_number'       => $request->getPhone(),
			'notes'              => $request->getNote(),
			'image'              => $image,
			'latitude'           => $request->getLatitude(),
			'longitude'          => $request->getLongitude(),
			'address_components' => $request->getAddressComponents(),
			'is_active'          => 1
		];

		return $this->repository->create( $data );
	}

	/**
	 * @throws InvalidImageFormatException
	 * @throws LocationNotFoundException
	 */
	public function update( int $id, LocationRequest $request ): int {
		$location = $this->repository->get( $id );

		if ( empty( $location ) ) {
			throw new LocationNotFoundException( $id );
		}

		$image = $this->handleImageUpload();

		$data = [
			'name'               => $request->getName(),
			'address'            => $request->getAddress(),
			'phone_number'       => $request->getPhone(),
			'notes'              => $request->getNote(),
			'latitude'           => $request->getLatitude(),
			'longitude'          => $request->getLongitude(),
			'address_components' => $request->getAddressComponents()
		];

		if ( ! empty( $image ) ) {
			$data['image'] = $image;

			//clean up old image
			if ( ! empty( $location->image ) ) {
				$this->cleanUpOldImage( $location->image );
			}
		}

		$this->repository->update( $id, $data );

		return $id;
	}

	/**
	 * @throws InvalidImageFormatException
	 */
	private function handleImageUpload(): string {
		if ( ! isset( $_FILES['image'] ) || ! is_string( $_FILES['image']['tmp_name'] ) ) {
			return '';
		}

		$pathInfo          = pathinfo( $_FILES["image"]["name"] );
		$extension         = strtolower( $pathInfo['extension'] );
		$allowedExtensions = [ 'jpg', 'jpeg', 'png' ];

		if ( ! in_array( $extension, $allowedExtensions ) ) {
			throw new InvalidImageFormatException();
		}

		$image    = md5( base64_encode( rand( 1, 9999999 ) . microtime( true ) ) ) . '.' . $extension;
		$fileName = Helper::uploadedFile( $image, 'Locations' );

		move_uploaded_file( $_FILES['image']['tmp_name'], $fileName );

		return $image;
	}

	private function cleanUpOldImage( string $image ) {
		$filePath = Helper::uploadedFile( $image, 'Locations' );

		if ( is_file( $filePath ) && is_writable( $filePath ) ) {
			unlink( $filePath );
		}
	}

	/**
	 * @param int $id
	 *
	 * @throws InvalidLocationIdException
	 * @throws LocationNotFoundException
	 */
	public function toggleVisibility( int $id ) {
		if ( ! ( $id > 0 ) ) {
			throw new InvalidLocationIdException();
		}

		$location = $this->repository->get( $id );

		if ( ! $location ) {
			throw new LocationNotFoundException( $id );
		}

		$newStatus = $location->is_active == 1 ? 0 : 1;

		$this->repository->update( $id, [ 'is_active' => $newStatus ] );
	}
}