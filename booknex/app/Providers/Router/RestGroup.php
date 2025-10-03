<?php

namespace BookneticApp\Providers\Router;

class RestGroup {
	private string $prefix;

	public function __construct( string $prefix ) {
		$this->prefix = $prefix;
	}

	public function get( $route, $fn, $args = [] ) {
		Route::get( sprintf( '%s/%s', $this->prefix, $route ), $fn, $args );
	}

	public function post( $route, $fn, $args = [] ) {
		Route::post( sprintf( '%s/%s', $this->prefix, $route ), $fn, $args );
	}

	public function put( $route, $fn, $args = [] ) {
		Route::put( sprintf( '%s/%s', $this->prefix, $route ), $fn, $args );
	}

	public function delete( $route, $fn, $args = [] ) {
		Route::delete( sprintf( '%s/%s', $this->prefix, $route ), $fn, $args );
	}
}