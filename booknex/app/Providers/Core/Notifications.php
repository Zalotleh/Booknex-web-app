<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\NotificationHelper;

class Notifications {
	private static array $addons = [];

	private static array $notifications = [];

	public static function init() {
		add_action( 'bkntc_cronjob', [ self::class, 'cron' ] );
	}

	public static function cron() {
		$lastRunnedOn  = Helper::getOption( 'notifications_cron_last_runned_on', 0, ! Helper::isSaaSVersion() );
		$sixHoursLater = $lastRunnedOn + 6 * 60 * 60;

		if ( $sixHoursLater > time() ) {
			return;
		}

		self::doAction();

		Helper::setOption( 'notifications_cron_last_runned_on', time(), ! Helper::isSaaSVersion() );
	}

	private static function doAction(): void {
		self::fetchAddons();

		if ( empty( self::$addons ) ) {
			return;
		}

		self::fetchNotifications();

		foreach ( self::$addons as $addon ) {
			if ( ! empty( self::$notifications[ $addon[ 'slug' ] ] ) ) {
				continue;
			}

			self::$notifications[ $addon[ 'slug' ] ] = [
				'name'    => $addon[ 'name' ],
				'slug'    => $addon[ 'slug' ],
				'visible' => true
			];
		}

		NotificationHelper::save( self::$notifications );
	}

	private static function fetchAddons(): void {
		$response = BoostoreHelper::getAllAddons();
		$addons   = BoostoreHelper::getUnownendAddons( $response[ 'items' ] ?? [] );

		self::$addons = array_filter( $addons, fn( $a ) => $a[ 'is_new' ] == '1' );
	}

	private static function fetchNotifications() {
		$slugs         = array_column( self::$addons, 'slug' );
		$notifications = NotificationHelper::getAll();

		if ( ! empty( $notifications ) ) {
			$notifications = array_filter( $notifications, fn( $n ) => in_array( $n[ 'slug' ], $slugs ) );
		}

		self::$notifications = $notifications;
	}
}