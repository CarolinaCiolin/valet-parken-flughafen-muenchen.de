<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Customer {
    public static function revoke( int $id ): void { global $wpdb; DB::query( $wpdb->prepare( 'UPDATE ' . DB::table( 'access' ) . ' SET revoked_at=%d WHERE booking_id=%d AND revoked_at=0', time(), $id ) ); }
    public static function issue( array $booking ): array {
        return DB::tx( function() use ( $booking ) {
            $b = Core::booking( $booking['public_id'], true ); self::revoke( (int) $b['id'] );
            $token = bin2hex( random_bytes( 32 ) ); $expires = time() + 30 * MINUTE_IN_SECONDS;
            DB::insert( 'access', array( 'booking_id' => $b['id'], 'token_hash' => hash( 'sha256', $token ), 'expires_at' => $expires ) );
            Operations::audit( 'customer_access.issued', 'booking', (int) $b['id'] );
            return array( 'url' => add_query_arg( array( 'vpm_booking_manage' => '1', 'locale' => $b['locale'] ), home_url( '/' ) ) . '#token=' . $token, 'expires_at' => $expires );
        } );
    }
    public static function send( array $b ): void {
        Config::require( Automation::configured(), 'automation_not_ready' );
        // Bookings are locked before access rows; transactional outbox and token rotation commit together.
        DB::tx( function() use ( $b ) {
            $b = Core::booking( $b['public_id'], true ); self::revoke( (int) $b['id'] );
            $token = bin2hex( random_bytes( 32 ) ); $expires = time() + 30 * MINUTE_IN_SECONDS;
            $id = DB::insert( 'access', array( 'booking_id' => $b['id'], 'token_hash' => hash( 'sha256', $token ), 'expires_at' => $expires ) );
            Automation::booking_event( $b, 'booking.access_requested', array( 'access_url' => add_query_arg( array( 'vpm_booking_manage' => '1', 'locale' => $b['locale'] ), home_url( '/' ) ) . '#token=' . $token, 'access_expires_at' => $expires ), 'access:' . $id );
        } );
    }
    public static function lookup( string $token ): array {
        Config::require( (bool) preg_match( '/^[a-f0-9]{64}$/D', $token ), 'access_unavailable' );
        $row = DB::row( 'access', 'token_hash', hash( 'sha256', $token ) );
        Config::require( $row && ! (int) $row['revoked_at'] && (int) $row['expires_at'] > time(), 'access_unavailable' );
        return $row;
    }
    public static function view( string $token ): array {
        $a = self::lookup( $token ); $b = DB::row( 'bookings', 'id', $a['booking_id'] ); Config::require( (bool) $b, 'access_unavailable' ); $q = Core::snapshot( $b );
        global $wpdb; $change = $wpdb->get_var( $wpdb->prepare( 'SELECT state FROM ' . DB::table( 'changes' ) . " WHERE booking_id=%d AND kind='cancellation'", $b['id'] ) );
        return array( 'reference' => $b['public_id'], 'state' => $b['state'], 'payment_state' => $b['payment_state'], 'amount_cents' => $q['charged'], 'discount_cents' => $q['discount'], 'refunded_cents' => (int) $b['refunded_cents'], 'currency' => 'eur', 'airport' => $q['airport'], 'travel' => $q['travel'] ?? array(), 'offer_terms' => $q['offer_terms'] ?? '', 'offer_selection' => $q['offer_selection'] ?? array(), 'cancellation_request' => $change ?: '', 'expires_at' => (int) $a['expires_at'] );
    }
    public static function cancellation( string $token ): array {
        $access = self::lookup( $token );
        return DB::tx( function() use ( $access, $token ) {
            $b = DB::row( 'bookings', 'id', $access['booking_id'], true ); self::lookup( $token ); Config::require( $b && $b['state'] !== 'cancelled', 'booking_already_cancelled' );
            global $wpdb; $old = (array) $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'changes' ) . " WHERE booking_id=%d AND kind='cancellation'", $b['id'] ), ARRAY_A );
            if ( ! $old ) { $id = DB::insert( 'changes', array( 'booking_id' => $b['id'], 'kind' => 'cancellation', 'state' => 'pending', 'created_at' => time() ) ); Operations::audit( 'cancellation.requested', 'booking', (int) $b['id'] ); Automation::booking_event( $b, 'booking.cancellation_requested', array(), 'change:' . $id ); }
            return array( 'accepted' => true, 'state' => $old['state'] ?? 'pending' );
        } );
    }
    public static function access_request( array $d ): array {
        // Always the same public acknowledgement: no confirmation that an email or reference exists.
        global $wpdb; $email = is_string( $d['email'] ?? null ) ? strtolower( trim( $d['email'] ) ) : ''; $ref = is_string( $d['reference'] ?? null ) ? $d['reference'] : '';
        if ( is_email( $email ) && preg_match( '/^[a-f0-9-]{36}$/D', $ref ) && Automation::configured() ) {
            $bucket = 'access:' . hash_hmac( 'sha256', $email . ':' . $ref . ':' . intdiv( time(), 900 ), wp_salt( 'nonce' ) );
            DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'rate_limits' ) . ' (bucket,hits,expires_at) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1', $bucket, time() + 900 ) );
            if ( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT hits FROM ' . DB::table( 'rate_limits' ) . ' WHERE bucket=%s', $bucket ) ) <= 3 ) {
                $b = DB::row( 'bookings', 'public_id', $ref ); if ( $b && hash_equals( $b['customer_email'], $email ) ) { self::send( $b ); }
            }
        }
        return array( 'accepted' => true );
    }
    public static function register(): void {
        add_action( 'rest_api_init', function() {
            foreach ( array( 'access' => 'access_request', 'view' => 'view', 'cancellation' => 'cancellation' ) as $route => $method ) {
                register_rest_route( 'vpm-booking/v1', '/customer/' . $route, array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( $r ) use ( $method ) {
                    try { PublicApi::limit(); Config::require( strlen( $r->get_body() ) < 4096, 'payload_too_large' ); $d = $r->get_json_params(); Config::require( is_array( $d ), 'json_object_required' );
                        if ( $method === 'access_request' ) { return self::access_request( $d ); }
                        Config::require( is_string( $d['token'] ?? null ), 'access_unavailable' ); return self::$method( $d['token'] );
                    } catch ( \Throwable $e ) { $rate = $e->getMessage() === 'frontend_rate_limited'; return new \WP_Error( $rate ? 'frontend_rate_limited' : 'access_unavailable', $rate ? 'frontend_rate_limited' : 'access_unavailable', array( 'status' => $rate ? 429 : 403 ) ); }
                } ) );
            }
        } );
        add_action( 'template_redirect', array( self::class, 'landing' ), 1 );
        add_action( 'vpm_booking_worker', function() { global $wpdb; $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . DB::table( 'access' ) . ' WHERE expires_at<%d LIMIT 500', time() - DAY_IN_SECONDS ) ); }, 20 );
    }
    public static function landing(): void {
        if ( ! isset( $_GET['vpm_booking_manage'] ) ) { return; }
        nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow, noarchive' ); header( 'Referrer-Policy: no-referrer' ); header( 'X-Frame-Options: DENY' ); header( "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'" );
        $en = isset( $_GET['locale'] ) && $_GET['locale'] === 'en'; $t = function( $de, $english ) use ( $en ) { return esc_html( $en ? $english : $de ); }; $url = plugins_url( '', dirname( __DIR__ ) . '/vpm-booking-core.php' );
        echo '<!doctype html><html lang="' . ( $en ? 'en' : 'de' ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . $t( 'Ihre Buchungsanfrage', 'Your booking enquiry' ) . '</title><link rel="stylesheet" href="' . esc_url( $url . '/assets/front.css?ver=0.5.0' ) . '"></head><body><main class="vpm-booking-surface vpm-customer" data-api="' . esc_url( rest_url( 'vpm-booking/v1/customer/' ) ) . '" data-locale="' . ( $en ? 'en' : 'de' ) . '"><h1>' . $t( 'Ihre Buchungsanfrage', 'Your booking enquiry' ) . '</h1><p>' . $t( 'Eine Anfrage ist noch keine bestätigte Buchung. Maßgeblich sind die angezeigten Statusangaben.', 'An enquiry is not yet a confirmed booking. Please check the status shown below.' ) . '</p><p class="vpm-customer-status" role="status" aria-live="polite"></p><section class="vpm-customer-details" hidden></section><form class="vpm-customer-cancel" hidden><p><label><input type="checkbox" name="acknowledged" required> ' . $t( 'Ich möchte einen Stornowunsch zur Prüfung übermitteln. Dadurch wird noch kein Storno oder eine Erstattung ausgeführt.', 'I wish to submit a cancellation request for review. This does not execute a cancellation or refund.' ) . '</label></p><button type="submit">' . $t( 'Stornowunsch übermitteln', 'Submit cancellation request' ) . '</button></form><form class="vpm-customer-access"><h2>' . $t( 'Neuen Zugangslink anfordern', 'Request a new access link' ) . '</h2><p><label>' . $t( 'Buchungsreferenz', 'Booking reference' ) . '<input name="reference" required maxlength="36" autocomplete="off"></label></p><p><label>' . $t( 'E-Mail-Adresse', 'Email address' ) . '<input name="email" type="email" required autocomplete="email"></label></p><button type="submit">' . $t( 'Zugangslink anfordern', 'Request access link' ) . '</button></form></main><script src="' . esc_url( $url . '/assets/customer.js?ver=0.5.0' ) . '" defer></script></body></html>'; exit;
    }
}
