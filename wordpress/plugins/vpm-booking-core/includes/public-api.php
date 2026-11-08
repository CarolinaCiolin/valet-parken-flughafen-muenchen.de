<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class LocalTime extends \RuntimeException {
    public function __construct( public string $field, public array $offsets ) { parent::__construct( 'ambiguous_local_time' ); }
}
final class PublicApi {
    public static function instant( string $input, string $timezone, string $field, string $offset = '' ): int {
        Config::require( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $input ) === 1, 'invalid_' . $field );
        $wall = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $input, new \DateTimeZone( 'UTC' ) );
        Config::require( $wall && $wall->format( 'Y-m-d\TH:i' ) === $input, 'invalid_' . $field );
        $zone = new \DateTimeZone( $timezone ); $offsets = array();
        foreach ( $zone->getTransitions( $wall->getTimestamp() - 172800, $wall->getTimestamp() + 172800 ) as $t ) { $offsets[ (int) $t['offset'] ] = true; }
        $candidates = array();
        foreach ( array_keys( $offsets ) as $seconds ) {
            $at = ( new \DateTimeImmutable( '@' . ( $wall->getTimestamp() - $seconds ) ) )->setTimezone( $zone );
            if ( $at->format( 'Y-m-d\TH:i' ) === $input ) { $candidates[ $at->format( 'P' ) ] = $at->getTimestamp(); }
        }
        Config::require( count( $candidates ) > 0, 'nonexistent_' . $field );
        if ( $offset ) { Config::require( isset( $candidates[ $offset ] ), 'invalid_time_offset' ); return $candidates[ $offset ]; }
        if ( count( $candidates ) > 1 ) { throw new LocalTime( $field, array_keys( $candidates ) ); }
        return (int) reset( $candidates );
    }
    public static function context( array $d ): array {
        $a = Airports::get( (string) ( $d['airport'] ?? '' ) );
        $start = self::instant( (string) ( $d['departure'] ?? '' ), $a['timezone'], 'departure', (string) ( $d['departure_offset'] ?? '' ) );
        $end = self::instant( (string) ( $d['return'] ?? '' ), $a['timezone'], 'return', (string) ( $d['return_offset'] ?? '' ) );
        Config::require( $start > time() && $start <= time() + 366 * DAY_IN_SECONDS && $end > $start && $end - $start <= 180 * DAY_IN_SECONDS, 'invalid_travel_period' );
        return array( 'airport' => $a['code'], 'timezone' => $a['timezone'], 'departure_utc' => $start, 'return_utc' => $end );
    }
    public static function enabled(): bool { return (bool) get_option( 'vpm_booking_frontend_enabled', false ); }
    public static function limit(): void {
        global $wpdb; $ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? 'local' );
        $bucket = 'front:' . hash_hmac( 'sha256', $ip . ':' . intdiv( time(), 60 ), wp_salt( 'nonce' ) );
        DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'rate_limits' ) . ' (bucket,hits,expires_at) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1', $bucket, time() + 120 ) );
        Config::require( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT hits FROM ' . DB::table( 'rate_limits' ) . ' WHERE bucket=%s', $bucket ) ) <= 30, 'frontend_rate_limited' );
    }
    public static function offers( array $data ): array {
        Config::require( self::enabled(), 'frontend_not_enabled' ); self::limit(); $context = self::context( $data ); $scope = (string) ( $data['airport_scope'] ?? '' ); Products::check_scope( $scope, $context['airport'] ); $locale = ( $data['locale'] ?? '' ) === 'en' ? 'en' : 'de';
        // Only installed, server-side adapters may supply prices. No fallback/test offers shipped.
        Config::require( Adapters::has_source($context['airport']), 'booking_adapter_not_configured' );
        $offers = apply_filters( 'vpm_booking_quote_offers', array(), $context ); Config::require( is_array( $offers ) && count( $offers ) <= 20, 'invalid_adapter_response' ); $out = array();
        foreach ( $offers as $offer ) {
            Config::require( is_array( $offer ) && ( $offer['currency'] ?? '' ) === 'eur', 'unsupported_offer_currency' );
            $partner = DB::row( 'partners', 'id', Config::integer( $offer['partner_id'] ?? null, 1 ) );
            Config::require( $partner && $partner['airport'] === $context['airport'], 'adapter_partner_mismatch' );
            if ( ! (int) $partner['active'] ) { continue; }
            $base = Config::integer( $offer['base_cents'] ?? null, 50 ); $terms = sanitize_text_field( $offer['terms'] ?? '' ); Config::require( strlen( $terms ) <= 1200, 'offer_terms_too_long' );
            $product = Products::normalize( Products::enrich( $offer, (int) $partner['id'] ) ); $details = array( 'product' => $product, 'airport_scope' => $scope, 'source' => $offer['_source'] ?? array() );
            $id = wp_generate_uuid4(); DB::insert( 'quotes', array( 'public_id' => $id, 'partner_id' => (int) $partner['id'], 'base_cents' => $base, 'context' => wp_json_encode( $context ), 'details' => wp_json_encode( $details ), 'terms' => $terms, 'expires_at' => time() + 600 ) );
            $out[] = array( 'quote_id' => $id, 'partner' => $partner['name'], 'amount_cents' => $base, 'currency' => 'eur', 'terms' => $terms ) + Products::public_data( $product, $locale );
        }
        return array( 'offers' => $out, 'timezone' => $context['timezone'] );
    }
    public static function request( array $data ): array {
        Config::require( self::enabled(), 'frontend_not_enabled' ); self::limit();
        Config::require( ! isset( $data['base_cents'] ) && ! isset( $data['partner_id'] ) && ! isset( $data['state'] ) && ! isset( $data['payment_state'] ), 'client_amount_or_state_forbidden' );
        list( $quote, $context, $details, $selection ) = self::priced_selection( $data );
        $b = Flow::create( array( 'partner_id' => (int) $quote['partner_id'], 'base_cents' => $selection['total_cents'], 'offer_selection' => isset( $details['product'] ) ? $selection : array(), 'email' => (string) ( $data['email'] ?? '' ), 'coupon' => (string) ( $data['coupon'] ?? '' ), 'sandbox_demo' => defined('VPM_BOOKING_DEMO_MODE') && VPM_BOOKING_DEMO_MODE === true && current_user_can('manage_options'), 'locale' => $data['locale'] ?? 'de', 'travel' => $context, 'offer_terms' => $quote['terms'] ), 'public-' . $quote['public_id'], $details['source'] ?? array() );
        $q = Core::snapshot( $b );
        return array( 'reference' => $b['public_id'], 'state' => $b['state'], 'payment_state' => $b['payment_state'], 'amount_cents' => $q['charged'], 'discount_cents' => $q['discount'], 'currency' => 'eur' );
    }
    private static function priced_selection( array $data ): array {
        $quote = DB::row( 'quotes', 'public_id', (string) ( $data['quote_id'] ?? '' ) ); Config::require( $quote && (int) $quote['expires_at'] >= time(), 'quote_expired' );
        $context = json_decode( $quote['context'], true ); Config::require( is_array( $context ) && $context['departure_utc'] > time(), 'quote_expired' );
        $details = json_decode( $quote['details'] ?? '', true ) ?: array(); $scope = (string) ( $details['airport_scope'] ?? '' ); Config::require( $scope === (string) ( $data['airport_scope'] ?? '' ), 'airport_scope_mismatch' ); Products::check_scope( $scope, $context['airport'] );
        $locale = ( $data['locale'] ?? '' ) === 'en' ? 'en' : 'de'; $product = $details['product'] ?? Products::normalize( array() ); $selection = Products::selection( (int) $quote['base_cents'], $product, $data['addons'] ?? array(), $locale );
        return array( $quote, $context, $details, $selection );
    }
    public static function preview( array $data ): array {
        Config::require( self::enabled(), 'frontend_not_enabled' ); self::limit();
        list( $quote, $context, $details, $selection ) = self::priced_selection( $data ); $partner = DB::row( 'partners', 'id', $quote['partner_id'] ); Config::require( $partner && (int) $partner['active'], 'partner_inactive' );
        $code = strtoupper( trim( (string) ( $data['coupon'] ?? '' ) ) ); $discount = $code === '' ? 0 : Vouchers::evaluate( DB::row( 'coupons', 'code', $code ), $partner, $selection['total_cents'], (string) ( $data['email'] ?? '' ) );
        $q = Finance::quote( $selection['total_cents'], (int) $partner['commission_bps'], $discount );
        return array( 'base_cents' => $q['base'], 'discount_cents' => $q['discount'], 'amount_cents' => $q['charged'], 'currency' => 'eur' );
    }
    public static function register(): void {
        foreach ( array( '/public/offers' => 'offers', '/public/requests' => 'request', '/public/price' => 'preview' ) as $path => $method ) {
            register_rest_route( 'vpm-booking/v1', $path, array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( $r ) use ( $method ) {
                try { Config::require( strlen( $r->get_body() ) < 8192, 'payload_too_large' ); $data = $r->get_json_params(); Config::require( is_array( $data ), 'json_object_required' ); return new \WP_REST_Response( self::$method( $data ), 200 ); }
                catch ( LocalTime $e ) { return new \WP_Error( 'ambiguous_local_time', 'ambiguous_local_time', array( 'status' => 422, 'field' => $e->field, 'offsets' => $e->offsets ) ); }
                catch ( \Throwable $e ) { $code = preg_match( '/^[a-z0-9_]+$/D', $e->getMessage() ) ? $e->getMessage() : 'request_failed'; return new \WP_Error( $code, $code, array( 'status' => str_contains( $code, 'rate_limited' ) ? 429 : 400 ) ); }
            } ) );
        }
    }
}
