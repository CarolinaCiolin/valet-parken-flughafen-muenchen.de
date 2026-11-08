<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Airports {
    public static function all(): array {
        $saved = get_option( 'vpm_booking_airports', null );
        return is_array( $saved ) ? $saved : ( json_decode( file_get_contents( dirname( __DIR__ ) . '/data/airports.json' ), true ) ?: array() );
    }
    public static function get( string $code ): array { foreach ( self::all() as $a ) { if ( $a['code'] === $code ) { return $a; } } throw new \RuntimeException( 'unknown_airport' ); }
    public static function validate( $input ): array {
        Config::require( is_array( $input ) && count( $input ) > 0 && count( $input ) <= 100, 'invalid_airport_catalogue' ); $out = array(); $seen = array();
        foreach ( $input as $a ) {
            Config::require( is_array( $a ) && preg_match( '/^[A-Z]{3}$/D', $a['code'] ?? '' ) && ! isset( $seen[ $a['code'] ] ) && in_array( $a['region'] ?? '', array( 'DE', 'AT', 'CH' ), true ) && in_array( $a['location_country'] ?? '', array( 'DE', 'AT', 'CH', 'FR' ), true ), 'invalid_airport_record' );
            $row = array_intersect_key( $a, array_flip( array( 'code', 'region', 'location_country', 'timezone' ) ) );
            Config::require( in_array( $a['timezone'] ?? '', \DateTimeZone::listIdentifiers(), true ), 'invalid_airport_timezone' );
            foreach ( array( 'name_de', 'name_en' ) as $key ) { Config::require( is_string( $a[ $key ] ?? null ), 'invalid_airport_name' ); $row[ $key ] = sanitize_text_field( $a[ $key ] ); Config::require( $row[ $key ] !== '' && strlen( $row[ $key ] ) <= 180, 'invalid_airport_name' ); }
            $row['page_id'] = Config::integer( $a['page_id'] ?? 0, 0 );
            if ( $row['page_id'] ) { Config::require( get_post_type( $row['page_id'] ) === 'page', 'airport_target_must_be_page' ); }
            $seen[ $a['code'] ] = true; $out[] = $row;
        }
        return $out;
    }
    public static function public_list( string $locale ): array {
        $out = array();
        foreach ( self::all() as $a ) {
            $id = (int) $a['page_id'];
            if ( $id && function_exists( 'pll_get_post' ) ) { $id = (int) pll_get_post( $id, $locale ); }
            $url = $id && get_post_status( $id ) === 'publish' && get_post_field( 'post_password', $id ) === '' ? get_permalink( $id ) : '';
            $out[] = array( 'code' => $a['code'], 'name' => $a[ 'name_' . $locale ], 'region' => $a['region'], 'timezone' => $a['timezone'], 'url' => $url );
        }
        return $out;
    }
}
