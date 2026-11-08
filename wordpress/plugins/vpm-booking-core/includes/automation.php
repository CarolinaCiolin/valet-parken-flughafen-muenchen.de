<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Automation {
    public static function configured(): bool { $url = Config::secret( 'VPM_BOOKING_AUTOMATION_URL' ); return (bool) get_option( 'vpm_booking_automation_enabled', false ) && 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) && strlen( Config::secret( 'VPM_BOOKING_AUTOMATION_SECRET' ) ) >= 32; }
    public static function event( string $key, string $topic, string $purpose, array $payload, int $contact = 0 ): bool {
        global $wpdb; Config::require( in_array( $purpose, array( 'transactional', 'consent', 'marketing' ), true ) && strlen( $key ) <= 150, 'invalid_outbox_event' );
        $sql = $wpdb->prepare( 'INSERT IGNORE INTO ' . DB::table( 'outbox' ) . ' (event_key,topic,purpose,contact_id,payload,status,created_at) VALUES (%s,%s,%s,%d,%s,%s,%d)', $key, $topic, $purpose, $contact, Config::seal( $payload ), 'pending', time() ); return DB::query( $sql ) > 0;
    }
    public static function booking_event( array $b, string $topic, array $extra = array(), string $suffix = '' ): void {
        if(!empty(Core::snapshot($b)['sandbox_demo']))return;
        self::event( $b['public_id'] . ':' . $topic . ':' . $suffix, $topic, 'transactional', array_merge( array( 'booking_id' => $b['public_id'], 'recipient' => $b['customer_email'], 'locale' => $b['locale'], 'amounts' => array_intersect_key( Core::snapshot( $b ), array_flip( array( 'base', 'discount', 'charged', 'currency' ) ) ), 'partner_id' => (int) $b['partner_id'], 'airport' => Core::snapshot( $b )['airport'], 'travel' => Core::snapshot( $b )['travel'] ?? array(), 'offer_terms' => Core::snapshot( $b )['offer_terms'] ?? '', 'offer_selection' => Core::snapshot( $b )['offer_selection'] ?? array(), 'sender_role' => 'platform', 'document_issuer' => 'partner', 'cancellation_responsible' => 'partner', 'partner_service' => Logistics::customer_data($b), 'booking_state' => $b['state'], 'payment_state' => $b['payment_state'] ), $extra ) );
    }
    public static function campaign( string $campaign ): int {
        global $wpdb; Config::key( $campaign ); $count = 0;
        foreach ( $wpdb->get_results( 'SELECT * FROM ' . DB::table( 'contacts' ) . " WHERE state='confirmed'", ARRAY_A ) as $c ) {
            $added = self::event( 'campaign:' . $campaign . ':' . $c['id'], 'marketing.message.requested', 'marketing', array( 'recipient' => $c['email'], 'campaign_key' => $campaign, 'consent_version' => $c['consent_version'], 'consent_revision' => (int) $c['revision'], 'unsubscribe_url' => Consent::unsubscribe_url( $c ) ), (int) $c['id'] ); if ( $added ) { ++$count; }
        }
        return $count;
    }
    public static function run(): void {
        if ( ! self::configured() ) { return; } global $wpdb;
        foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . DB::table( 'outbox' ) . " WHERE (status='pending' OR (status='processing' AND locked_until<%d)) AND next_attempt<=%d ORDER BY id LIMIT 20", time(), time() ) ) as $id ) {
            $lease = bin2hex( random_bytes( 16 ) );
            $claimed = DB::query( $wpdb->prepare( 'UPDATE ' . DB::table( 'outbox' ) . " SET status='processing',locked_until=%d,locked_by=%s WHERE id=%d AND (status='pending' OR (status='processing' AND locked_until<%d))", time() + 120, $lease, $id, time() ) );
            if ( ! $claimed ) { continue; } $row = DB::row( 'outbox', 'id', $id );
            if ( $row['purpose'] === 'marketing' || $row['purpose'] === 'consent' ) {
                $contact = DB::row( 'contacts', 'id', $row['contact_id'] );
                if ( ! $contact || ( $row['purpose'] === 'marketing' && $contact['state'] !== 'confirmed' ) || ( $row['purpose'] === 'consent' && $contact['state'] !== 'pending' ) ) { DB::update( 'outbox', array( 'status' => 'suppressed', 'locked_until' => 0 ), array( 'id' => $id, 'locked_by' => $lease ) ); continue; }
            }
            try {
                $payload = Config::open( $row['payload'] );
                if ( (int) $row['contact_id'] && $row['topic'] !== 'marketing.revoked' && isset( $payload['consent_revision'] ) ) { $now = DB::row( 'contacts', 'id', $row['contact_id'] ); if ( ! $now || (int) $now['revision'] !== (int) $payload['consent_revision'] ) { DB::update( 'outbox', array( 'status' => 'suppressed', 'locked_until' => 0 ), array( 'id' => $id, 'locked_by' => $lease ) ); continue; } }
                $body = wp_json_encode( array( 'event_id' => $row['event_key'], 'topic' => $row['topic'], 'purpose' => $row['purpose'], 'created_at' => (int) $row['created_at'], 'payload' => $payload ) ); $timestamp = (string) time();
                $r = wp_safe_remote_post( Config::secret( 'VPM_BOOKING_AUTOMATION_URL' ), array( 'timeout' => 15, 'redirection' => 0, 'headers' => array( 'Content-Type' => 'application/json', 'X-VPM-Event-ID' => $row['event_key'], 'X-VPM-Timestamp' => $timestamp, 'X-VPM-Signature' => 'v1=' . hash_hmac( 'sha256', $timestamp . '.' . $body, Config::secret( 'VPM_BOOKING_AUTOMATION_SECRET' ) ) ), 'body' => $body ) );
                Config::require( ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) >= 200 && wp_remote_retrieve_response_code( $r ) < 300, 'automation_delivery_failed' );
                DB::update( 'outbox', array( 'status' => 'dispatched', 'locked_until' => 0, 'error_code' => '' ), array( 'id' => $id, 'locked_by' => $lease ) );
            } catch ( \Throwable $e ) { $attempts = (int) $row['attempts'] + 1; DB::update( 'outbox', array( 'attempts' => $attempts, 'status' => $attempts >= 8 ? 'attention' : 'pending', 'locked_until' => 0, 'next_attempt' => time() + min( 86400, 30 * ( 2 ** $attempts ) ), 'error_code' => 'automation_delivery_retry_required' ), array( 'id' => $id, 'locked_by' => $lease ) ); }
        }
    }
    public static function callback( string $body, string $timestamp, string $signature ): array {
        $secret = Config::secret( 'VPM_BOOKING_AUTOMATION_SECRET' ); Config::require( strlen( $secret ) >= 32 && ctype_digit( $timestamp ) && abs( time() - (int) $timestamp ) <= 300 && hash_equals( 'v1=' . hash_hmac( 'sha256', $timestamp . '.' . $body, $secret ), $signature ), 'automation_signature_invalid' );
        $input = json_decode( $body, true ); Config::require( is_array( $input ) && preg_match( '/^[a-zA-Z0-9_.:-]{8,100}$/D', $input['event_id'] ?? '' ), 'invalid_callback' );
        return DB::tx( function() use ( $input ) {
            global $wpdb; $key = 'automation:' . $input['event_id'];
            if ( $wpdb->get_var( $wpdb->prepare( 'SELECT event_id FROM ' . DB::table( 'events' ) . ' WHERE event_id=%s', $key ) ) ) { return array( 'received' => true, 'duplicate' => true ); }
            if ( ( $input['topic'] ?? '' ) === 'document.available' ) {
                $b = Core::booking( $input['booking_id'] ?? '', true ); $url = (string) ( $input['url'] ?? '' ); $kind = $input['kind'] ?? ''; $reference = (string) ( $input['reference'] ?? '' );
                Config::require( in_array( $kind, array( 'invoice', 'receipt', 'credit_note' ), true ) && preg_match( '/^[a-zA-Z0-9_.:-]{1,100}$/D', $reference ) && 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) && filter_var( $url, FILTER_VALIDATE_URL ) && ! wp_parse_url( $url, PHP_URL_USER ), 'invalid_document' );
                $old = DB::row( 'documents', 'reference', $reference );
                if ( ! $old ) { DB::insert( 'documents', array( 'booking_id' => $b['id'], 'reference' => $reference, 'kind' => $kind, 'url' => $url, 'created_at' => time() ) ); Automation::booking_event( $b, 'document.available', array( 'document_reference' => $reference, 'document_kind' => $kind, 'document_issuer' => 'partner', 'document_url' => $url ), $reference ); }
                else { Config::require( (int) $old['booking_id'] === (int) $b['id'] && $old['url'] === $url && $old['kind'] === $kind, 'document_reference_conflict' ); }
            } elseif ( in_array( $input['topic'] ?? '', array( 'email.delivered', 'email.bounced', 'email.failed' ), true ) ) {
                $outgoing = (string) ( $input['outgoing_event'] ?? '' ); Config::require( strlen( $outgoing ) <= 150 && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . DB::table( 'outbox' ) . ' WHERE event_key=%s', $outgoing ) ), 'unknown_outgoing_event' );
                DB::insert( 'deliveries', array( 'event_id' => $input['event_id'], 'outgoing_event' => $outgoing, 'state' => $input['topic'], 'created_at' => time() ) );
            } elseif ( ( $input['topic'] ?? '' ) === 'marketing.unsubscribed' ) { Consent::revoke_remote( (string) ( $input['email'] ?? '' ) );
            } else { throw new \RuntimeException( 'unsupported_callback' ); }
            DB::insert( 'events', array( 'event_id' => $key, 'source' => 'automation', 'created_at' => time() ) ); return array( 'received' => true );
        } );
    }
}
