<?php

namespace BookneticApp\Providers\Helpers;

class StringUtil {

	public static function snakeCaseToCamel( $snakeCaseString ): string {
		return lcfirst( str_replace( ' ', '', ucwords( str_replace( '_', ' ', $snakeCaseString ) ) ) );
	}

	public static function cutText( $text, $n = 35 ) {
		// null ola bilir ve PHP deprecated error qaytarir mb funksiyasi (php8)
		if ( empty( $text ) ) {
			return '';
		}

		return mb_strlen( $text, 'UTF-8' ) > $n ? mb_substr( $text, 0, $n, 'UTF-8' ) . '...' : $text;
	}

	/**
	 * Converts camelCase to kebab-case
	 *
	 * @param $camelString
	 *
	 * @return string
 */
	public static function camelToKebab( $camelString ): string {
		preg_match_all( '/([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)/', $camelString, $parts );

		foreach ( $parts[0] as &$part ) {
			$part = ( $part === strtoupper( $part ) ? strtolower( $part ) : lcfirst( $part ) );
		}

		return implode( '-', $parts[0] );
	}}