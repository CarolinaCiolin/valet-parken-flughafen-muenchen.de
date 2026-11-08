<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Consent {
    public static function audit( array $c, string $action ): void { DB::insert( 'consent_log', array( 'contact_id' => $c['id'], 'action' => $action, 'consent_version' => $c['consent_version'], 'consent_hash' => $c['consent_hash'], 'consent_text' => $c['consent_text'], 'revision' => $c['revision'], 'created_at' => time() ) ); }
    public static function unsubscribe_url( array $c ): string { $data = Config::open( $c['unsubscribe_payload'] ); return add_query_arg( array( 'vpm_booking_consent' => 'unsubscribe', 'token' => $data['token'] ), set_url_scheme( home_url( '/' ), 'https' ) ); }
    public static function ready(): bool { return Automation::configured() && (bool) trim( get_option( 'vpm_booking_consent_text', '' ) ) && (bool) trim( get_option( 'vpm_booking_consent_version', '' ) ); }
    public static function rate_limit( string $email, string $ip ): void {
        global $wpdb;
        foreach ( array( array( 'mail:' . $email, 900, 1 ), array( 'ip:' . $ip, 3600, 10 ) ) as $rule ) {
            $bucket = hash_hmac( 'sha256', $rule[0] . ':' . intdiv( time(), $rule[1] ), wp_salt( 'nonce' ) );
            DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'rate_limits' ) . ' (bucket,hits,expires_at) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1', $bucket, time() + $rule[1] ) );
            $hits = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT hits FROM ' . DB::table( 'rate_limits' ) . ' WHERE bucket=%s', $bucket ) ); Config::require( $hits <= $rule[2], 'newsletter_rate_limited' );
        }
    }
    public static function request( string $email, bool $agreed, string $ip ): array {
        Config::require( self::ready(), 'newsletter_not_configured' ); Config::require( $agreed, 'explicit_marketing_consent_required' ); $email = Config::email( $email ); self::rate_limit( $email, $ip );
        DB::tx( function() use ( $email ) {
            $old = DB::row( 'contacts', 'email', $email, true ); if ( $old && $old['state'] === 'confirmed' ) { return; }
            $token = bin2hex( random_bytes( 32 ) ); $unsubscribe = bin2hex( random_bytes( 32 ) ); $version = (string) get_option( 'vpm_booking_consent_version' );
            $data = array( 'email' => $email, 'state' => 'pending', 'consent_version' => $version, 'consent_hash' => hash( 'sha256', get_option( 'vpm_booking_consent_text' ) ), 'consent_text' => get_option( 'vpm_booking_consent_text' ), 'revision' => $old ? (int) $old['revision'] + 1 : 1, 'unsubscribe_payload' => Config::seal( array( 'token' => $unsubscribe ) ), 'requested_at' => time(), 'confirmed_at' => 0, 'revoked_at' => 0, 'token_hash' => hash( 'sha256', $token ), 'expires_at' => time() + DAY_IN_SECONDS, 'unsubscribe_hash' => hash( 'sha256', $unsubscribe ) );
            if ( $old ) { $id = (int) $old['id']; DB::update( 'contacts', $data, array( 'id' => $id ) ); } else { $id = DB::insert( 'contacts', $data ); }
            $data['id'] = $id; self::audit( $data, 'requested' );
            $url = set_url_scheme( home_url( '/' ), 'https' );
            Automation::event( 'consent:' . $id . ':' . $data['token_hash'], 'marketing.confirmation_requested', 'consent', array( 'recipient' => $email, 'confirmation_url' => add_query_arg( array( 'vpm_booking_consent' => 'confirm', 'token' => $token ), $url ), 'unsubscribe_url' => add_query_arg( array( 'vpm_booking_consent' => 'unsubscribe', 'token' => $unsubscribe ), $url ), 'consent_version' => $version, 'consent_revision' => $data['revision'] ), $id );
        } );
        return array( 'accepted' => true, 'message' => 'Falls eine Bestätigung erforderlich ist, erhältst du einen Bestätigungslink.' );
    }
    public static function act( string $token, string $action ): array {
        Config::require( preg_match( '/^[a-f0-9]{64}$/D', $token ) && in_array( $action, array( 'confirm', 'unsubscribe' ), true ), 'invalid_consent_link' );
        return DB::tx( function() use ( $token, $action ) {
            $hash = hash( 'sha256', $token ); $c = DB::row( 'contacts', $action === 'confirm' ? 'token_hash' : 'unsubscribe_hash', $hash, true ); Config::require( ! empty( $c ), 'invalid_consent_link' );
            if ( $action === 'unsubscribe' ) {
                if ( $c['state'] !== 'unsubscribed' ) { DB::update( 'contacts', array( 'state' => 'unsubscribed', 'revoked_at' => time(), 'revision' => (int) $c['revision'] + 1 ), array( 'id' => $c['id'] ) ); $c['revision'] = (int) $c['revision'] + 1; self::audit( $c, 'revoked' ); Automation::event( 'unsubscribe:' . $c['id'] . ':' . $c['token_hash'], 'marketing.revoked', 'transactional', array( 'recipient' => $c['email'], 'consent_version' => $c['consent_version'], 'consent_revision' => $c['revision'], 'unsubscribe_url' => self::unsubscribe_url( $c ) ), (int) $c['id'] ); }
            } else {
                Config::require( (int) $c['expires_at'] >= time() && in_array( $c['state'], array( 'pending', 'confirmed' ), true ), 'consent_link_expired_or_revoked' );
                if ( $c['state'] === 'pending' ) { DB::update( 'contacts', array( 'state' => 'confirmed', 'confirmed_at' => time(), 'revision' => (int) $c['revision'] + 1 ), array( 'id' => $c['id'] ) ); $c['revision'] = (int) $c['revision'] + 1; self::audit( $c, 'confirmed' ); Automation::event( 'confirmed:' . $c['id'] . ':' . $c['token_hash'], 'marketing.confirmed', 'transactional', array( 'recipient' => $c['email'], 'consent_version' => $c['consent_version'], 'consent_revision' => $c['revision'], 'unsubscribe_url' => self::unsubscribe_url( $c ) ), (int) $c['id'] ); }
            }
            return array( 'state' => $action === 'confirm' ? 'confirmed' : 'unsubscribed' );
        } );
    }
    public static function revoke_remote( string $email ): void { $c = DB::row( 'contacts', 'email', Config::email( $email ), true ); if ( ! $c || $c['state'] === 'unsubscribed' ) { return; } $c['revision'] = (int) $c['revision'] + 1; DB::update( 'contacts', array( 'state' => 'unsubscribed', 'revoked_at' => time(), 'revision' => $c['revision'] ), array( 'id' => $c['id'] ) ); self::audit( $c, 'revoked_remote' ); }
    public static function form(): string {
        if ( ! self::ready() ) { return '<p>Die Newsletter-Anmeldung ist noch nicht eingerichtet.</p>'; }
        $action = esc_url( add_query_arg( 'vpm_booking_newsletter', 'submit', home_url( '/' ) ) );
        return '<form method="post" action="' . $action . '"><label>E-Mail <input type="email" name="email" autocomplete="email" required maxlength="254"></label><label><input type="checkbox" name="marketing_agreed" value="1" required> ' . esc_html( get_option( 'vpm_booking_consent_text' ) ) . '</label>' . wp_nonce_field( 'vpm_booking_newsletter', '_wpnonce', true, false ) . '<button type="submit">Anmeldung anfordern</button></form>';
    }
    public static function landing(): void {
        if ( ! isset( $_GET['vpm_booking_consent'] ) && ! isset( $_GET['vpm_booking_newsletter'] ) ) { return; }
        nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow, noarchive' ); header( 'Referrer-Policy: no-referrer' ); header( 'Content-Type: text/html; charset=UTF-8' ); $message = ''; $form = '';
        try {
            if ( isset( $_GET['vpm_booking_newsletter'] ) ) {
                Config::require( $_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'vpm_booking_newsletter' ), 'invalid_signup_request' );
                $r = self::request( sanitize_text_field( wp_unslash( $_POST['email'] ?? '' ) ), ( $_POST['marketing_agreed'] ?? '' ) === '1', $_SERVER['REMOTE_ADDR'] ?? '' ); $message = $r['message'];
            } else {
                $action = sanitize_key( $_GET['vpm_booking_consent'] ); $token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
                Config::require( in_array( $action, array( 'confirm', 'unsubscribe' ), true ) && preg_match( '/^[a-f0-9]{64}$/D', $token ), 'invalid_consent_link' );
                if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) { Config::require( wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'vpm_booking_consent' ), 'invalid_consent_request' ); self::act( $token, $action ); $message = $action === 'confirm' ? 'Anmeldung bestätigt.' : 'Abmeldung gespeichert.'; }
                else { $message = $action === 'confirm' ? 'Bitte bestätige deine Anmeldung mit dem Button.' : 'Bitte bestätige deine Abmeldung mit dem Button.'; $form = '<form method="post">' . wp_nonce_field( 'vpm_booking_consent', '_wpnonce', true, false ) . '<button type="submit">' . ( $action === 'confirm' ? 'Anmeldung bestätigen' : 'Abmelden' ) . '</button></form>'; }
            }
        } catch ( \Throwable $e ) { status_header( 400 ); $message = 'Der Vorgang konnte nicht verarbeitet werden. Bitte prüfe den Link oder versuche es später erneut.'; }
        echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>E-Mail-Einstellungen</title><body><main><h1>E-Mail-Einstellungen</h1><p>' . esc_html( $message ) . '</p>' . $form . '</main></body></html>'; exit;
    }
}
