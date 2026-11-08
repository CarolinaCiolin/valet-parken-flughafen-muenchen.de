<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Stripe {
    public static function ready(): bool { return (bool) preg_match( '/^(sk|rk)_test_[a-zA-Z0-9_]+$/D', Config::secret( 'VPM_BOOKING_STRIPE_TEST_SECRET' ) ); }
    public static function api( string $path, array $body, string $key, string $method = 'POST' ): array {
        Config::require( self::ready(), 'stripe_test_key_required_live_disabled' );
        $r = wp_remote_request( 'https://api.stripe.com/v1/' . $path, array( 'method' => $method, 'timeout' => 15, 'redirection' => 0, 'headers' => array( 'Authorization' => 'Bearer ' . Config::secret( 'VPM_BOOKING_STRIPE_TEST_SECRET' ), 'Stripe-Version' => '2024-06-20', 'Idempotency-Key' => $key ), 'body' => $body ) );
        Config::require( ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) >= 200 && wp_remote_retrieve_response_code( $r ) < 300, 'stripe_request_failed_retry_same_key' );
        $data = json_decode( wp_remote_retrieve_body( $r ), true ); Config::require( is_array( $data ) && ! empty( $data['id'] ), 'stripe_invalid_response' ); return $data;
    }
    public static function checkout( string $public ): array {
        Config::require( self::ready(), 'stripe_test_key_required_live_disabled' );
        $b = DB::tx( function() use ( $public ) { $b = Core::booking( $public, true ); Config::require( (bool) preg_match( '/^acct_[a-zA-Z0-9]{5,70}$/D', Core::snapshot( $b )['stripe_account'] ), 'partner_payment_account_missing' ); Config::require( ( (!Flow::managed($b) && $b['state'] === 'confirmed') || Flow::payment_ready($b) ) && in_array( $b['payment_state'], array( 'unpaid', 'creating', 'pending' ), true ), 'booking_not_ready_for_checkout' ); if ( ! $b['stripe_session'] ) { DB::update( 'bookings', array( 'payment_state' => 'creating' ), array( 'id' => $b['id'] ) ); } return $b; } );
        if ( ! empty( $b['stripe_session'] ) ) { $old = self::api( 'checkout/sessions/' . $b['stripe_session'], array(), 'vpm-session-' . $public, 'GET' ); Config::require( ( $old['status'] ?? '' ) === 'open' && ( $old['id'] ?? '' ) === $b['stripe_session'] && wp_parse_url( $old['url'] ?? '', PHP_URL_HOST ) === 'checkout.stripe.com' && wp_parse_url( $old['url'] ?? '', PHP_URL_SCHEME ) === 'https', 'checkout_no_longer_open' ); return array( 'session_id' => $old['id'], 'url' => $old['url'], 'mode' => 'test' ); }
        $q = Core::snapshot( $b ); Config::require( (bool) preg_match( '/^acct_[a-zA-Z0-9]{5,70}$/D', $q['stripe_account'] ), 'partner_payment_account_missing' ); $home = set_url_scheme( home_url( '/' ), 'https' );
        $session = self::api( 'checkout/sessions', array( 'mode' => 'payment', 'client_reference_id' => $public, 'customer_email' => $b['customer_email'], 'success_url' => add_query_arg( 'vpm_booking_payment', 'returned', $home ), 'cancel_url' => add_query_arg( 'vpm_booking_payment', 'cancelled', $home ), 'metadata' => array( 'booking_id' => $public ), 'line_items' => array( array( 'price_data' => array( 'currency' => 'eur', 'unit_amount' => $q['charged'], 'product_data' => array( 'name' => 'Airport parking – ' . $q['airport'] ) ), 'quantity' => 1 ) ), 'payment_intent_data' => array( 'application_fee_amount' => $q['platform'], 'transfer_data' => array( 'destination' => $q['stripe_account'] ), 'metadata' => array( 'booking_id' => $public ) ) ), 'vpm-checkout-' . $public );
        Config::require( (bool) preg_match( '/^cs_test_/', $session['id'] ) && isset( $session['url'] ) && 'https' === wp_parse_url( $session['url'], PHP_URL_SCHEME ) && 'checkout.stripe.com' === wp_parse_url( $session['url'], PHP_URL_HOST ), 'invalid_checkout_session' );
        DB::tx( function() use ( $public, $session ) { $current = Core::booking( $public, true ); Config::require( ! $current['stripe_session'] || $current['stripe_session'] === $session['id'], 'checkout_idempotency_conflict' ); DB::update( 'bookings', array( 'stripe_session' => $session['id'], 'payment_state' => 'pending' ), array( 'id' => $current['id'] ) ); } );
        return array( 'session_id' => $session['id'], 'url' => $session['url'], 'mode' => 'test' );
    }
    public static function signature( string $body, string $header ): void {
        $secret = Config::secret( 'VPM_BOOKING_STRIPE_WEBHOOK_SECRET' ); Config::require( str_starts_with( $secret, 'whsec_' ), 'stripe_webhook_not_configured' );
        $timestamp = 0; $signatures = array();
        foreach ( explode( ',', $header ) as $item ) { $pair = explode( '=', trim( $item ), 2 ); if ( count( $pair ) !== 2 ) { continue; } if ( $pair[0] === 't' && ctype_digit( $pair[1] ) ) { $timestamp = (int) $pair[1]; } if ( $pair[0] === 'v1' ) { $signatures[] = $pair[1]; } }
        Config::require( $timestamp > 0 && abs( time() - $timestamp ) <= 300, 'stripe_signature_expired' );
        $expected = hash_hmac( 'sha256', $timestamp . '.' . $body, $secret ); $valid = false; foreach ( $signatures as $signature ) { $valid = $valid || hash_equals( $expected, $signature ); } Config::require( $valid, 'stripe_signature_invalid' );
    }
    public static function webhook( string $body, string $signature ): array {
        self::signature( $body, $signature ); $event = json_decode( $body, true );
        Config::require( is_array( $event ) && false === ( $event['livemode'] ?? null ) && empty( $event['account'] ) && preg_match( '/^evt_[a-zA-Z0-9_]{1,130}$/D', $event['id'] ?? '' ), 'stripe_live_or_invalid_event' );
        return DB::tx( function() use ( $event ) {
            global $wpdb; $event_key = 'stripe:' . $event['id'];
            if ( $wpdb->get_var( $wpdb->prepare( 'SELECT event_id FROM ' . DB::table( 'events' ) . ' WHERE event_id=%s', $event_key ) ) ) { return array( 'received' => true, 'duplicate' => true ); }
            $o = $event['data']['object'] ?? array(); $type = $event['type'] ?? '';
            if ( in_array( $type, array( 'checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.expired' ), true ) ) {
                $b = Core::booking( (string) ( $o['metadata']['booking_id'] ?? '' ), true );
                Config::require( $b['stripe_session'] && $b['stripe_session'] === ( $o['id'] ?? '' ) && ( $o['client_reference_id'] ?? '' ) === $b['public_id'], 'stripe_session_mapping_not_ready' );
                $q = Core::snapshot( $b ); Config::require( ( $o['currency'] ?? '' ) === 'eur' && ( $o['amount_total'] ?? null ) === $q['charged'], 'stripe_amount_mismatch' );
                if ( 'checkout.session.expired' === $type && in_array( $b['payment_state'], array( 'pending', 'creating' ), true ) ) { DB::update( 'bookings', array( 'payment_state' => 'expired' ), array( 'id' => $b['id'] ) ); Core::coupon_state( $b, 'released' ); }
                elseif ( 'paid' === ( $o['payment_status'] ?? '' ) && ! in_array( $b['payment_state'], array( 'paid', 'partially_refunded', 'refunded' ), true ) ) {
                    $pi = is_array( $o['payment_intent'] ?? null ) ? $o['payment_intent']['id'] : ( $o['payment_intent'] ?? '' ); Config::require( is_string( $pi ) && (bool) preg_match( '/^pi_[a-zA-Z0-9_]+$/D', $pi ), 'invalid_payment_intent' );
                    DB::update( 'bookings', array( 'payment_state' => 'paid', 'stripe_pi' => $pi ), array( 'id' => $b['id'] ) ); $b['payment_state'] = 'paid'; $b['stripe_pi'] = $pi;
                    Flow::enqueue($b,'confirm');
                    Finance::journal( $b, 'paid:' . $b['public_id'], Finance::paid_entries( $q ) );
                    if ( $b['state'] !== 'cancelled' ) { Core::coupon_state( $b, 'consumed' ); }
                    Automation::booking_event( $b, 'payment.received' ); Automation::booking_event( $b, 'invoice.requested' );
                    if ( $b['state'] === 'confirmed' && !Flow::managed($b) ) { Automation::booking_event( $b, 'booking.confirmed' ); }
                    if ( $b['state'] === 'cancelled' ) { Core::refund_row( $b, $q['charged'], 'late-cancelled-payment' ); }
                }
            } elseif ( in_array( $type, array( 'refund.created', 'refund.updated' ), true ) ) { self::apply_refund( $o ); }
            DB::insert( 'events', array( 'event_id' => $event_key, 'source' => 'stripe', 'created_at' => time() ) ); return array( 'received' => true );
        } );
    }
    // Caller owns transaction; always lock booking before refund, matching local cancellation lock order.
    private static function apply_refund( array $o ): void {
        global $wpdb; $reference = (string) ( $o['metadata']['booking_id'] ?? '' );
        if ( ! $reference ) { Automation::event( 'external-refund:' . sanitize_key( $o['id'] ?? '' ), 'reconciliation.required', 'transactional', array( 'reason' => 'unmapped_external_refund', 'stripe_refund_id' => sanitize_text_field( $o['id'] ?? '' ), 'payment_intent' => sanitize_text_field( $o['payment_intent'] ?? '' ) ) ); return; }
        $b = Core::booking( $reference, true ); $r = DB::row( 'refunds', 'public_id', (string) ( $o['metadata']['refund_id'] ?? '' ), true );
        Config::require( $r && (int) $r['booking_id'] === (int) $b['id'] && (int) $r['cents'] === ( $o['amount'] ?? null ) && ( $o['currency'] ?? '' ) === 'eur' && ( $o['payment_intent'] ?? '' ) === $b['stripe_pi'], 'refund_mapping_mismatch' );
        Config::require( preg_match( '/^re_[a-zA-Z0-9_]+$/D', $o['id'] ?? '' ) && ( ! $r['stripe_refund'] || $r['stripe_refund'] === $o['id'] ), 'refund_id_mismatch' );
        if ( $r['status'] === 'succeeded' ) { return; }
        if ( $o['status'] === 'succeeded' ) {
            $q = Core::snapshot( $b ); $after = (int) $b['refunded_cents'] + (int) $r['cents'];
            Finance::journal( $b, 'refund:' . $r['public_id'], Finance::refund_entries( $q, (int) $b['refunded_cents'], $after ) );
            DB::update( 'refunds', array( 'status' => 'succeeded', 'stripe_refund' => $o['id'], 'error_code' => '' ), array( 'id' => $r['id'] ) );
            DB::update( 'bookings', array( 'refunded_cents' => $after, 'payment_state' => $after === $q['charged'] ? 'refunded' : 'partially_refunded' ), array( 'id' => $b['id'] ) );
            Automation::booking_event( $b, 'invoice.adjustment_requested', array( 'refund_id' => $r['public_id'], 'amount_cents' => (int) $r['cents'] ), 'refund:' . $r['id'] );
            Automation::booking_event( $b, 'refund.succeeded', array( 'refund_id' => $r['public_id'], 'amount_cents' => (int) $r['cents'] ), 'refund:' . $r['id'] );
        } elseif ( in_array( $o['status'] ?? '', array( 'failed', 'canceled' ), true ) ) { DB::update( 'refunds', array( 'status' => 'failed', 'stripe_refund' => $o['id'], 'error_code' => 'stripe_refund_failed' ), array( 'id' => $r['id'] ) ); Automation::booking_event( $b, 'refund.failed', array( 'refund_id' => $r['public_id'] ), 'refund:' . $r['id'] ); }
        else { DB::update( 'refunds', array( 'status' => 'submitted', 'stripe_refund' => $o['id'] ), array( 'id' => $r['id'] ) ); }
    }
    public static function execute_refund( int $id ): void {
        $r = DB::row( 'refunds', 'id', $id ); if ( ! $r || $r['status'] !== 'pending' ) { return; }
        $b = DB::row( 'bookings', 'id', $r['booking_id'] );
        try {
            $response = self::api( 'refunds', array( 'payment_intent' => $b['stripe_pi'], 'amount' => (int) $r['cents'], 'reverse_transfer' => 'true', 'refund_application_fee' => 'true', 'metadata' => array( 'booking_id' => $b['public_id'], 'refund_id' => $r['public_id'] ) ), 'vpm-refund-' . $r['public_id'] );
            DB::tx( function() use ( $response ) { self::apply_refund( $response ); } );
        } catch ( \Throwable $e ) { DB::tx( function() use ( $b, $id ) { DB::row( 'bookings', 'id', $b['id'], true ); $current = DB::row( 'refunds', 'id', $id, true ); if ( $current['status'] !== 'pending' ) { return; } $attempts = (int) $current['attempts'] + 1; DB::update( 'refunds', array( 'attempts' => $attempts, 'status' => $attempts >= 8 ? 'attention' : 'pending', 'error_code' => 'stripe_refund_retry_required', 'next_attempt' => time() + min( 3600, 30 * ( 2 ** min( $attempts, 6 ) ) ) ), array( 'id' => $id ) ); } ); }
    }
    public static function retry_refunds(): void { if ( ! self::ready() ) { return; } global $wpdb; foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . DB::table( 'refunds' ) . " WHERE status='pending' AND next_attempt<=%d ORDER BY id LIMIT 10", time() ) ) as $id ) { self::execute_refund( (int) $id ); } }
}
