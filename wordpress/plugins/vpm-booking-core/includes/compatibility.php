<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Compatibility {
    public static function private_request(): bool {
        $uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' ); $route = $_GET['rest_route'] ?? '';
        return str_contains( $uri, '/wp-json/vpm-booking/v1/' ) || ( is_string( $route ) && str_starts_with( $route, '/vpm-booking/v1/' ) ) || isset( $_GET['vpm_booking_consent'] ) || isset( $_GET['vpm_booking_newsletter'] ) || isset( $_GET['vpm_booking_payment'] ) || isset( $_GET['vpm_booking_manage'] );
    }
    public static function cache(): void {
        if ( self::private_request() ) { if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); } do_action( 'litespeed_control_set_nocache', 'VPM dynamic booking endpoint' ); }
    }
    public static function rest_headers( $response, $server, $request ) {
        if ( str_starts_with( $request->get_route(), '/vpm-booking/v1/' ) && $response instanceof \WP_HTTP_Response ) { $response->header( 'Cache-Control', 'no-store, private, max-age=0' ); $response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' ); $response->header( 'Referrer-Policy', 'no-referrer' ); }
        return $response;
    }
    public static function hosting(): array {
        global $wpdb;
        $https = wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME ) === 'https' && wp_parse_url( get_option( 'siteurl' ), PHP_URL_SCHEME ) === 'https';
        $status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name=%s', DB::table( 'bookings' ) ), ARRAY_A );
        return array( 'WordPress' => get_bloginfo( 'version' ), 'PHP' => PHP_VERSION, '64-Bit PHP' => PHP_INT_SIZE >= 8, 'OpenSSL' => function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ), 'InnoDB' => $status && strcasecmp( $status['Engine'], 'InnoDB' ) === 0, 'Beide Website-Adressen HTTPS' => $https, 'WordPress-Cron per Konstante deaktiviert' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON, 'Letzter Worker (Unixzeit)' => (int) get_option( 'vpm_booking_last_worker', 0 ), 'Frontend-Anfragen aktiviert' => PublicApi::enabled(), 'Anbieteradapter vorhanden' => (bool) has_filter( 'vpm_booking_quote_offers' ) );
    }
    public static function health(): array {
        $s = self::hosting(); $good = $s['64-Bit PHP'] && $s['OpenSSL'] && $s['InnoDB'] && $s['Beide Website-Adressen HTTPS'];
        return array( 'label' => $good ? 'Buchungskern: technische Hosting-Voraussetzungen vorhanden' : 'Buchungskern: Hosting-Voraussetzungen prüfen', 'status' => $good ? 'good' : 'recommended', 'badge' => array( 'label' => 'VPM Buchungskern', 'color' => 'blue' ), 'description' => '<p>Geprüft werden PHP, Verschlüsselung, InnoDB und HTTPS-Adressen. Externe Stripe-/Mail-Erreichbarkeit, Servercron und Partner-Anbindung sind dadurch nicht bestätigt.</p>', 'actions' => '<p><a href="' . esc_url( admin_url( 'tools.php?page=vpm-booking-core' ) ) . '">Buchungskern prüfen</a></p>', 'test' => 'vpm_booking_hosting' );
    }
    public static function exporters( array $exporters ): array { $exporters['vpm-booking'] = array( 'exporter_friendly_name' => 'VPM Buchungen und E-Mail-Einstellungen', 'callback' => array( self::class, 'export' ) ); return $exporters; }
    public static function export( string $email, int $page = 1 ): array {
        global $wpdb; $email = strtolower( trim( $email ) ); $offset = max( 0, $page - 1 ) * 20; $items = array();
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'bookings' ) . ' WHERE customer_email=%s ORDER BY id LIMIT 20 OFFSET %d', $email, $offset ), ARRAY_A );
        foreach ( $rows as $b ) { $q = Core::snapshot( $b ); $changes = $wpdb->get_results( $wpdb->prepare( 'SELECT kind,state,created_at,reviewed_at FROM ' . DB::table( 'changes' ) . ' WHERE booking_id=%d', $b['id'] ), ARRAY_A ); $data = array( array( 'name' => 'E-Mail', 'value' => $b['customer_email'] ), array( 'name' => 'Buchungsreferenz', 'value' => $b['public_id'] ), array( 'name' => 'Status', 'value' => $b['state'] . ' / ' . $b['payment_state'] ), array( 'name' => 'Kundenzahlung (Cent)', 'value' => (string) $q['charged'] ), array( 'name' => 'Erstattet (Cent)', 'value' => (string) $b['refunded_cents'] ), array( 'name' => 'Reisedaten', 'value' => wp_json_encode( $q['travel'] ?? array() ) ), array( 'name' => 'Gewählte Leistungen', 'value' => wp_json_encode( $q['offer_selection'] ?? array() ) ), array( 'name' => 'Änderungswünsche', 'value' => wp_json_encode( $changes ) ) ); $items[] = array( 'group_id' => 'vpm-bookings', 'group_label' => 'Buchungen', 'item_id' => 'booking-' . $b['id'], 'data' => $data ); }
        if ( $page === 1 ) { $c = DB::row( 'contacts', 'email', $email ); if ( $c ) { $items[] = array( 'group_id' => 'vpm-consent', 'group_label' => 'E-Mail-Einstellungen', 'item_id' => 'contact-' . $c['id'], 'data' => array( array( 'name' => 'Status', 'value' => $c['state'] ), array( 'name' => 'Einwilligungsversion', 'value' => $c['consent_version'] ), array( 'name' => 'Einwilligungstext', 'value' => $c['consent_text'] ) ) ); } }
        if ( $page === 1 ) { $hash = hash_hmac( 'sha256', $email, wp_salt( 'auth' ) ); foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT id,code,starts_at,ends_at,used_uses FROM ' . DB::table( 'coupons' ) . ' WHERE customer_hash=%s', $hash ), ARRAY_A ) as $v ) { $items[] = array( 'group_id' => 'vpm-vouchers', 'group_label' => 'Persönliche Gutscheine', 'item_id' => 'voucher-' . $v['id'], 'data' => array( array( 'name' => 'Code', 'value' => $v['code'] ), array( 'name' => 'Beginn UTC', 'value' => gmdate( 'c', (int) $v['starts_at'] ) ), array( 'name' => 'Ende UTC', 'value' => gmdate( 'c', (int) $v['ends_at'] ) ), array( 'name' => 'Einlösungen', 'value' => (string) $v['used_uses'] ) ) ); } }
        return array( 'data' => $items, 'done' => count( $rows ) < 20 );
    }
    public static function erasers( array $erasers ): array { $erasers['vpm-booking'] = array( 'eraser_friendly_name' => 'VPM Buchungen und E-Mail-Einstellungen', 'callback' => array( self::class, 'erase' ) ); return $erasers; }
    public static function erase( string $email, int $page = 1 ): array {
        global $wpdb; $email = strtolower( trim( $email ) ); $contact = DB::row( 'contacts', 'email', $email ); $bookings = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . DB::table( 'bookings' ) . ' WHERE customer_email=%s', $email ) );
        if ( $contact && $contact['state'] !== 'unsubscribed' ) { DB::tx( function() use ( $email ) { Consent::revoke_remote( $email ); $c = DB::row( 'contacts', 'email', $email ); Automation::event( 'privacy-revoke:' . $c['id'] . ':' . $c['revision'], 'marketing.revoked', 'transactional', array( 'recipient' => $email, 'consent_version' => $c['consent_version'], 'consent_revision' => (int) $c['revision'] ), (int) $c['id'] ); } ); }
        foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . DB::table( 'bookings' ) . ' WHERE customer_email=%s', $email ) ) as $id ) { Customer::revoke( (int) $id ); }
        return array( 'items_removed' => false, 'items_retained' => (bool) ( $contact || $bookings ), 'messages' => ( $contact || $bookings ) ? array( 'Marketing ist gesperrt. Buchungs-/Einwilligungsnachweise benötigen vor Löschung eine geprüfte Aufbewahrungsentscheidung; keine automatisierte Löschfrist konfiguriert.' ) : array(), 'done' => true );
    }
    public static function register(): void {
        add_action( 'init', array( self::class, 'cache' ), 0 );
        add_filter( 'rest_post_dispatch', array( self::class, 'rest_headers' ), 10, 3 );
        add_filter( 'script_loader_tag', function( $tag, $handle ) { return $handle === 'vpm-booking-front' ? str_replace( '<script ', '<script data-no-optimize="1" data-cfasync="false" ', $tag ) : $tag; }, 20, 2 );
        add_filter( 'site_status_tests', function( $tests ) { $tests['direct']['vpm_booking_hosting'] = array( 'label' => 'VPM Buchungskern', 'test' => array( self::class, 'health' ) ); return $tests; } );
        add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'exporters' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'erasers' ) );
        add_filter( 'wp_robots', function( $r ) { if ( self::private_request() ) { $r['noindex'] = true; $r['nofollow'] = true; unset( $r['index'] ); } return $r; } );
        add_action( 'template_redirect', function() { if ( self::private_request() ) { nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow, noarchive' ); header( 'Referrer-Policy: no-referrer' ); } }, 0 );
    }
    public static function admin(): void { echo '<h2>Hosting und Laufzeit</h2><table class="widefat striped"><tbody>'; foreach ( self::hosting() as $label => $value ) { echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( is_bool( $value ) ? ( $value ? 'ja' : 'nein' ) : (string) $value ) . '</td></tr>'; } echo '</tbody></table><p>Keine Hostinger-Liveprüfung. Ein deaktivierter WordPress-Cron kann durch echten Servercron ersetzt sein; dessen Einrichtung muss separat nachgewiesen werden. Keine Node-/Docker-Laufzeit auf dem Hosting erforderlich.</p>'; }
}
