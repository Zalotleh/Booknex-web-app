<?php

namespace BookneticApp\Providers\Router;

use Exception;
use WP_REST_Request;

class RestRequest {
	const TYPE_INTEGER = 'integer';
	const TYPE_STRING = 'string';
	const TYPE_ARRAY = 'array';
	const TYPE_FLOAT = 'array';
	const TYPE_BOOL = 'bool';
	private WP_REST_Request $request;

	public function __construct( WP_REST_Request $request ) {
		$this->request = $request;
	}

	/**
	 * @throws Exception
	 */
	public function require( $key, $checkType, $errorMessage = [], $whitelist = [] ) {
		$value = self::param( $key, '', $checkType, $whitelist );

		if ( empty( $value ) ) {
			throw new Exception( $errorMessage );
		}

		return $value;
	}

	public function param( $key, $default = null, $dataType = null, $whitelist = [] ) {
		return $this->checkTypeAndGet( $key, $default, $dataType, $whitelist );
	}

	private function checkTypeAndGet( $key, $default = null, $dataType = null, $whitelist = [] ) {
		$res = $this->request->get_param( $key ) ?? $default;

		if ( ! empty( $dataType ) ) {
			switch ( $dataType ) {
				case self::TYPE_BOOL:
					$res = is_bool( $res ) ? $res : $default;
					break;
				case self::TYPE_INTEGER:
					$res = is_numeric( $res ) ? (int) $res : $default;
					break;
				case self::TYPE_STRING:
					$res = is_string( $res ) ? $res : $default;
					break;
				case self::TYPE_ARRAY:
					$res = is_array( $res ) ? $res : $default;
					break;
				case self::TYPE_FLOAT:
					$res = is_numeric( $res ) ? (float) $res : $default;
					break;
			}
		}

		if ( ! empty( $whitelist ) && ! in_array( $res, $whitelist ) && $dataType !== self::TYPE_ARRAY ) {
			return $default;
		}

		if ( ! empty( $whitelist ) && $dataType === self::TYPE_ARRAY ) {
			$res = array_intersect( $whitelist, $res );

			return array_values( $res );
		}

		return $res;
	}

	public function getRequest(): WP_REST_Request {
		return $this->request;
	}
}