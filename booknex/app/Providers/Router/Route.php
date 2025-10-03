<?php

namespace BookneticApp\Providers\Router;

use WP_REST_Request;

class Route {
	const API_VER = 'v1';
	const MODULE = 'booknetic';

	public static function get( $route, $fn, $args = [] ) {
		self::addRoute( 'GET', $route, $fn, $args );
	}

	private static function addRoute( $method, $route, $fn, $args ): void {
		add_action( 'rest_api_init', fn() => register_rest_route( self::getNamespace(), $route, [
			'methods'             => $method,
			'callback'            => function ( WP_REST_Request $request ) use ( $fn ) {
				try {
					$restRequest = new RestRequest( $request );
					$res         = $fn( $restRequest );

					return is_array( $res ) ? $res : [ 'error_msg' => bkntc__( 'Error' ) ];
				} catch ( \Exception $e ) {
					return [ 'error_msg' => $e->getMessage() ];
				}
			},
            'permission_callback' => '__return_true',
			'args'                => $args
		] ) );
	}

	private static function getNamespace(): string {
		return sprintf( '%s/%s', self::MODULE, self::API_VER );
	}

	public static function post( $route, $fn, $args = [] ) {
		self::addRoute( 'POST', $route, $fn, $args );
	}

	public static function put( $route, $fn, $args = [] ) {
		self::addRoute( 'PUT', $route, $fn, $args );
	}

	public static function delete( $route, $fn, $args = [] ) {
		self::addRoute( 'DELETE', $route, $fn, $args );
	}
}