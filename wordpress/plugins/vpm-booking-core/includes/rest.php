<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Rest {
    private static function respond( callable $fn ) { try { return new \WP_REST_Response( $fn(), 200 ); } catch ( \Throwable $e ) { $code = preg_match( '/^[a-z0-9_]+$/D', $e->getMessage() ) ? $e->getMessage() : 'operation_failed'; return new \WP_Error( $code, $code, array( 'status' => str_contains( $code, 'signature' ) ? 403 : ( str_contains( $code, 'database' ) || str_contains( $code, 'not_ready' ) || str_contains( $code, 'retry' ) ? 503 : 400 ) ) ); } }
    private static function data( \WP_REST_Request $r ): array { $d = $r->get_json_params(); Config::require( is_array( $d ), 'json_object_required' ); return $d; }
    public static function register(): void {
        $admin = function() { return current_user_can( 'manage_options' ) || current_user_can( 'manage_vpm_bookings' ); };
        $routes = array(
            '/partners' => function( $r ) { return Core::partner( self::data( $r ) ); },
            '/coupons' => function( $r ) { return Core::coupon( self::data( $r ) ); },
            '/bookings' => function( $r ) { $d = self::data( $r ); return Core::create( $d, (string) $r->get_header( 'Idempotency-Key' ) ); },
            '/bookings/(?P<id>[a-f0-9-]{36})/confirm' => function( $r ) { return Core::confirm( $r['id'] ); },
            '/bookings/(?P<id>[a-f0-9-]{36})/checkout' => function( $r ) { return Stripe::checkout( $r['id'] ); },
            '/bookings/(?P<id>[a-f0-9-]{36})/cancel' => function( $r ) { return Core::cancel( $r['id'] ); },
            '/bookings/(?P<id>[a-f0-9-]{36})/refund' => function( $r ) { $d = self::data( $r ); return Core::refund( $r['id'], Config::integer( $d['amount_cents'] ?? null, 1 ), (string) $r->get_header( 'Idempotency-Key' ) ); },
            '/marketing/campaigns' => function( $r ) { $d = self::data( $r ); return array( 'queued_contacts' => Automation::campaign( (string) ( $d['campaign_key'] ?? '' ) ) ); },
            '/worker' => function() { Stripe::retry_refunds(); Automation::run(); return array( 'processed' => true ); },
        );
        foreach ( $routes as $path => $callback ) { register_rest_route( 'vpm-booking/v1', $path, array( 'methods' => 'POST', 'permission_callback' => $admin, 'callback' => function( $r ) use ( $callback ) { return self::respond( function() use ( $callback, $r ) { return $callback( $r ); } ); } ) ); }
        register_rest_route( 'vpm-booking/v1', '/bookings/(?P<id>[a-f0-9-]{36})', array( 'methods' => 'GET', 'permission_callback' => $admin, 'callback' => function( $r ) { return self::respond( function() use ( $r ) { return Core::booking( $r['id'] ); } ); } ) );
        register_rest_route( 'vpm-booking/v1', '/stripe/webhook', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( $r ) { return self::respond( function() use ( $r ) { Config::require( strlen( $r->get_body() ) <= 262144, 'payload_too_large' ); return Stripe::webhook( $r->get_body(), $r->get_header( 'stripe-signature' ) ); } ); } ) );
        register_rest_route( 'vpm-booking/v1', '/automation/events', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( $r ) { return self::respond( function() use ( $r ) { Config::require( strlen( $r->get_body() ) <= 65536, 'payload_too_large' ); return Automation::callback( $r->get_body(), $r->get_header( 'x-vpm-timestamp' ), $r->get_header( 'x-vpm-signature' ) ); } ); } ) );
        register_rest_route( 'vpm-booking/v1', '/marketing/request', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( $r ) { return self::respond( function() use ( $r ) { $d = self::data( $r ); return Consent::request( (string) ( $d['email'] ?? '' ), ( $d['agreed'] ?? false ) === true, $_SERVER['REMOTE_ADDR'] ?? '' ); } ); } ) );
        register_rest_route( 'vpm-booking/v1', '/marketing/unsubscribe', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function( $r ) { return self::respond( function() use ( $r ) { return Consent::act( (string) $r->get_param( 'token' ), 'unsubscribe' ); } ); } ) );
    }
    public static function admin_menu(): void {
        $hook = add_management_page( 'VPM Buchungskern', 'VPM Buchungskern', 'manage_options', 'vpm-booking-core', array( self::class, 'admin' ) );
        add_action( 'load-' . $hook, function() {
            if ( current_user_can( 'manage_options' ) && isset( $_POST['vpm_booking_settings'] ) ) {
                check_admin_referer( 'vpm_booking_settings' );
                update_option( 'vpm_booking_automation_enabled', isset( $_POST['automation_enabled'] ), false );
                update_option( 'vpm_booking_consent_text', sanitize_textarea_field( wp_unslash( is_string( $_POST['consent_text'] ?? null ) ? $_POST['consent_text'] : '' ) ), false );
                update_option( 'vpm_booking_consent_version', sanitize_text_field( wp_unslash( is_string( $_POST['consent_version'] ?? null ) ? $_POST['consent_version'] : '' ) ), false );
            }
        } );
    }
    public static function admin(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; } global $wpdb;
        echo '<div class="wrap"><h1>VPM Buchungskern – Sandbox</h1><p>Technischer Backend-Baustein. Keine Live-Zahlungen, keine Anbieter-Verfügbarkeit, kein öffentlicher Buchungscheckout und keine Rechnungserstellung eingerichtet. Rückkehr vom Zahlungsdienst ist noch keine Buchungsbestätigung; maßgeblich ist der verifizierte Webhook.</p><table class="widefat striped"><tbody>';
        $rows = array( 'Payload-Verschlüsselung verfügbar' => function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ), 'Stripe-Testschlüssel hinterlegt (Formatprüfung)' => Stripe::ready(), 'Stripe-Webhook-Secret vorhanden' => str_starts_with( Config::secret( 'VPM_BOOKING_STRIPE_WEBHOOK_SECRET' ), 'whsec_' ), 'Automatisierungsverbindung eingerichtet/aktiviert' => Automation::configured(), 'Newsletter-Einwilligungstext/-Version vorhanden' => (bool) get_option( 'vpm_booking_consent_text' ) && (bool) get_option( 'vpm_booking_consent_version' ) );
        foreach ( $rows as $label => $value ) { echo '<tr><th>' . esc_html( $label ) . '</th><td>' . ( $value ? 'ja' : 'nein' ) . '</td></tr>'; }
        foreach ( array( 'bookings', 'outbox', 'refunds', 'contacts' ) as $table ) { echo '<tr><th>' . esc_html( $table ) . '</th><td>' . (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( $table ) ) . '</td></tr>'; }
        echo '</tbody></table><h2>Automatisierung vorbereiten</h2><p>Zugangsdaten ausschließlich über sichere Serverkonfiguration hinterlegen. Ereignisversand bleibt bis zur bewussten Aktivierung aus. HTTP-Annahme bedeutet noch keine tatsächliche E-Mail-Zustellung. Inhalte und zulässige Einwilligungstexte vor Nutzung festlegen.</p><form method="post">'; wp_nonce_field( 'vpm_booking_settings' );
        echo '<p><label><input type="checkbox" name="automation_enabled" value="1" ' . checked( get_option( 'vpm_booking_automation_enabled', false ), true, false ) . '> Konfigurierten Automatisierungs-Endpunkt aktivieren</label></p><p><label>Newsletter-Einwilligungstext<br><textarea name="consent_text" rows="5" class="large-text">' . esc_textarea( get_option( 'vpm_booking_consent_text', '' ) ) . '</textarea></label></p><p><label>Version des Einwilligungstextes <input name="consent_version" value="' . esc_attr( get_option( 'vpm_booking_consent_version', '' ) ) . '"></label></p>';
        submit_button( 'Vorbereitung speichern', 'primary', 'vpm_booking_settings' ); echo '</form><p>Optionaler späterer Newsletter-Baustein: <code>[vpm_booking_newsletter]</code>. Er bleibt ohne vollständige Konfiguration gesperrt. Der Einwilligungsbutton wird erst nach Öffnen eines individuellen Bestätigungslinks angezeigt.</p></div>';
    }
}
