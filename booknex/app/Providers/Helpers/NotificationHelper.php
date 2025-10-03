<?php

namespace BookneticApp\Providers\Helpers;

class NotificationHelper {
	public static function getAll(): array {
        $notifications = Helper::getOption( 'new_addon_notifications', '', ! Helper::isSaaSVersion() );
		$notifications = json_decode( $notifications, true );

		return $notifications ?? [];
	}

	public static function getVisible(): array {
		return array_filter( self::getAll(), fn( $n ) => $n[ 'visible' ] );
	}

	public static function save( array $notifications ): void {
        Helper::setOption( 'new_addon_notifications', json_encode( $notifications ), ! Helper::isSaaSVersion() );
	}
}