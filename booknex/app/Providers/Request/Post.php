<?php

namespace BookneticApp\Providers\Request;


use BookneticApp\Providers\Helpers\Helper;

//That's why generics...
class Post implements Request {
	public static function string( string $key, string $default = '', array $whiteList = [] ): string {
		if ( empty( $_POST[ $key ] ) ) {
			return $default;
		}

		$field = $_POST[ $key ];

		if ( ! is_string( $field ) ) {
			return $default;
		}

		$field = trim( stripslashes_deep( $field ) );

		if ( ! empty( $whiteList ) && ! in_array( $field, $whiteList ) ) {
			return $default;
		}

		return $field;
	}

	public static function int( string $key, int $default = 0, array $whiteList = [] ): int {
		if ( !isset( $_POST[ $key ] ) ) {
			return $default;
		}

		$field = $_POST[ $key ];

		if ( ! is_numeric( $field ) ) {
			return $default;
		}

		if ( ! empty( $whiteList ) && ! in_array( $field, $whiteList ) ) {
			return $default;
		}

		return (int) $field;
	}

	public static function bool( string $key, bool $default = false ): bool {
		return ! empty( $_POST[ $key ] ) ?: $default;
	}

	public static function boolString( string $key, $default = false ): bool {
		return ! empty( $_POST[ $key ] ) && $_POST[ $key ] !== 'false' ?: $default;
	}

	public static function array( string $key, array $default = [], array $whiteList = [] ): array {
		if ( empty( $_POST[ $key ] ) ) {
			return $default;
		}

		$field = $_POST[ $key ];

		if ( ! is_array( $field ) ) {
			return $default;
		}

		$field = stripslashes_deep( $field );

		if ( ! empty( $whiteList ) && ! in_array( $field, $whiteList ) ) {
			return $default;
		}

		return $field;
	}

	public static function json( string $key, array $default = [] ): array {
		return (array) self::any( $key, 'json', $default );
	}

	public static function float( string $key, float $default = 0.00 ): float {
		return (float) self::any( $key, 'float', $default );
	}

	public static function email( string $key, string $default = '' ): string {
		return (string) self::any( $key, 'email', $default );
	}

	public static function any( $key, $type, $default = null, $whitelist = [] ) {
		$res = $_POST[ $key ] ?? $default;

		if ( $res === $default ) {
			return $default;
		}

		if ( $type == 'num' || $type == 'int' || $type == 'integer' ) {
			$res = is_numeric( $res ) ? (int) $res : $default;
		} else if ( $type == 'str' || $type == 'string' ) {
			$res = is_string( $res ) ? trim( stripslashes_deep( (string) $res ) ) : $default;
		} else if ( $type == 'arr' || $type == 'array' ) {
			$res = is_array( $res ) ? stripslashes_deep( (array) $res ) : $default;
		} else if ( $type == 'float' ) {
			$res = is_numeric( $res ) ? (float) $res : $default;
		} else if ( $type == 'email' ) {
			$res = is_string( $res ) && filter_var( $res, FILTER_VALIDATE_EMAIL ) !== false ? trim( (string) $res ) : $default;
		} else if ( $type == 'json' ) {
			$res = json_decode( trim( stripslashes_deep( $res ) ), true );

			$res = is_array( $res ) ? $res : $default;
		} else if ( $type == 'price' ) {
			$price = Helper::deFormatPrice( $res );

			$res = ! is_null( $price ) ? $price : $default;
		}

		if ( ! empty( $whiteList ) && ! in_array( $res, $whiteList ) ) {
			return $default;
		}

		return $res;
	}
}