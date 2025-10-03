<?php

namespace BookneticApp\Backend\Locations\DTOs\Request;

use BookneticApp\Backend\Locations\Exceptions\NameRequiredException;
use Exception;

class LocationRequest {
	private string $name;
	private string $address;
	private string $phone;
	private string $note;
	private string $latitude;
	private string $longitude;
	private string $addressComponents;

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @throws NameRequiredException
	 */
	public function setName( string $name ): LocationRequest {
		if ( empty( $name ) ) {
			throw new NameRequiredException();
		}

		$this->name = $name;

		return $this;
	}

	public function getAddress(): string {
		return $this->address;
	}

	public function setAddress( string $address ): LocationRequest {
		$this->address = $address;

		return $this;
	}

	public function getPhone(): string {
		return $this->phone;
	}

	public function setPhone( string $phone ): LocationRequest {
		$this->phone = $phone;

		return $this;
	}

	public function getNote(): string {
		return $this->note;
	}

	public function setNote( string $note ): LocationRequest {
		$this->note = $note;

		return $this;
	}

	public function getLatitude(): string {
		return $this->latitude;
	}

	public function setLatitude( string $latitude ): LocationRequest {
		$this->latitude = $latitude;

		return $this;
	}

	public function getLongitude(): string {
		return $this->longitude;
	}

	public function setLongitude( string $longitude ): LocationRequest {
		$this->longitude = $longitude;

		return $this;
	}

	public function getAddressComponents(): string {
		return $this->addressComponents;
	}

	public function setAddressComponents( string $addressComponents ): LocationRequest {
		$this->addressComponents = $addressComponents;

		return $this;
	}
}