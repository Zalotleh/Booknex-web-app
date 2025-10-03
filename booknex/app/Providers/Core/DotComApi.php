<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Providers\Helpers\Helper;
use Exception;

class DotComApi
{
    const REGULAR_URL = 'https://www.booknetic.com/api/api.php';
    const SAAS_URL = 'https://www.booknetic.com/api/saas/api.php';

    public static function safeGet( string $act, array $args = [], bool $saas = false ): array
    {
        try {
            return self::get( $act, $args, $saas );
        } catch ( Exception $e ) {
            return [];
        }
    }

    /**
     * @throws Exception
     */
    public static function get( string $act, array $args = [], bool $saas = false ): array
    {
        $params = self::getParams();

        $params[ 'act' ] = $act;

        if ( ! empty( $args ) ) {
            $params = array_merge( $params, $args );
        }

        $url = ( $saas ? self::SAAS_URL : self::REGULAR_URL ) . '?' . http_build_query( $params );

        $result = wp_remote_get( $url );

        if ( is_wp_error( $result ) ) {
            throw new Exception( $result->get_error_message() );
        }

        if ( empty( $result[ 'response' ][ 'code' ] ) ) {
            throw new Exception( bkntc__( 'Server returned with an empty status code.' ) );
        }

        if ( $result[ 'response' ][ 'code' ] != 200 ) {
            throw new Exception( bkntc__( 'Server returned with an invalid status code %s', $result[ 'response' ][ 'code' ] ) );
        }

        if ( empty( $result[ 'body' ] ) ) {
            $response = [];
        } else {
            $response = json_decode( $result[ 'body' ], true ) ?? [];
        }

        return $response;
    }

    private static function getParams(): array
    {
        return [
            'purchase_code' => Helper::getOption( 'purchase_code', '', false ),
            'domain' => site_url(),
            'version' => Helper::getVersion(),
            'php_version' => phpversion(),
            'staging' => Helper::getOption( 'bkntc_staging', false, false )
        ];
    }
}