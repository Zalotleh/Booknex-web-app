<?php

namespace BookneticApp\Backend\Locations\Mappers;

use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;
use BookneticApp\Models\Location;
use BookneticApp\Providers\DB\Collection;

class LocationMapper {

	/**
	 * @param Location|Collection $location
	 *
	 * @return LocationResponse
	 */
	public static function toResponse( Collection $location ): LocationResponse {

		$dto = new LocationResponse();

		$dto->setId( $location->id )
		    ->setName( $location->name )
		    ->setImage( $location->image )
		    ->setAddress( $location->address )
		    ->setPhoneNumber( $location->phone_number )
		    ->setNotes( $location->notes )
		    ->setLatitude( $location->latitude )
		    ->setLongitude( $location->longitude )
		    ->setIsActive( ! ! $location->is_active );

		return $dto;
	}
}