<?php
// Only fake credentials and local data. Every external HTTP call is intercepted below.
define( 'VPM_BOOKING_STRIPE_TEST_SECRET', 'sk_test_vpm_fixture_only' );
define( 'VPM_BOOKING_STRIPE_WEBHOOK_SECRET', 'whsec_vpm_fixture_only_not_a_real_secret' );
define( 'VPM_BOOKING_AUTOMATION_URL', 'https://example.org/vpm-automation-test' );
define( 'VPM_BOOKING_AUTOMATION_SECRET', 'vpm-fixture-secret-at-least-32-bytes-no-real-service' );
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
define( 'WP_ADMIN', true ); require '/var/www/html/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== '127.0.0.1' ) { exit( 2 ); }
use VPM\Booking\DB; use VPM\Booking\Core; use VPM\Booking\Config; use VPM\Booking\Finance; use VPM\Booking\Stripe; use VPM\Booking\Automation; use VPM\Booking\Consent;
global $wpdb; $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}vpm_booking_%'" );
foreach ( $tables as $table ) { if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ) { throw new Exception( 'Nonempty booking test tables: refused' ); } }
$settings = array(); foreach ( array( 'vpm_booking_automation_enabled', 'vpm_booking_consent_text', 'vpm_booking_consent_version' ) as $key ) { $settings[$key] = get_option( $key, null ); }
add_filter( 'pre_wp_mail', '__return_true' );
$checks = array(); $sessions = array(); $sent = array(); $refund_fail = false; $automation_fail = false; $http_count = 0;
function ok( $condition, $name ) { global $checks; if ( ! $condition ) { throw new Exception( $name ); } $checks[] = $name; }
function fails( callable $f, string $code ) { try { $f(); } catch ( Throwable $e ) { ok( $e->getMessage() === $code, 'rejected:' . $code ); return; } throw new Exception( 'Expected rejection: ' . $code ); }
function response( array $data, int $code = 200 ): array { return array( 'response' => array( 'code' => $code ), 'headers' => array(), 'body' => wp_json_encode( $data ) ); }
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$sessions, &$sent, &$refund_fail, &$automation_fail, &$http_count ) {
    ++$http_count; $body = $args['body'] ?? array(); if ( is_string( $body ) ) { parse_str( $body, $body ); }
    if ( str_starts_with( $url, 'https://api.stripe.com/v1/checkout/sessions/' ) ) { $id = basename( $url ); return response( $sessions[$id] ); }
    if ( $url === 'https://api.stripe.com/v1/checkout/sessions' ) {
        $id = 'cs_test_' . str_replace( '-', '', $body['metadata']['booking_id'] );
        $sessions[$id] = array_merge( $body, array( 'id' => $id, 'url' => 'https://checkout.stripe.com/c/pay/' . $id, 'status' => 'open', 'currency' => 'eur', 'amount_total' => $body['line_items'][0]['price_data']['unit_amount'], 'payment_intent' => 'pi_' . substr( $id, 8 ), 'payment_status' => 'unpaid' ) );
        ok( $body['payment_intent_data']['application_fee_amount'] === 1000 && $body['payment_intent_data']['transfer_data']['destination'] === 'acct_fixturepartner', 'Checkout routes net platform margin and partner account' );
        ok( ! empty( $args['headers']['Idempotency-Key'] ) && $args['redirection'] === 0, 'Stripe idempotency and no redirects' ); return response( $sessions[$id] );
    }
    if ( $url === 'https://api.stripe.com/v1/refunds' ) {
        if ( $refund_fail ) { return new WP_Error( 'fixture_failure', 'deliberate test transport failure' ); }
        ok( $body['reverse_transfer'] === 'true' && $body['refund_application_fee'] === 'true', 'Refund explicitly requests transfer/fee reversal' );
        return response( array_merge( $body, array( 'id' => 're_' . str_replace( '-', '', $body['metadata']['refund_id'] ), 'currency' => 'eur', 'status' => 'succeeded' ) ) );
    }
    if ( $url === VPM_BOOKING_AUTOMATION_URL ) {
        if ( $automation_fail ) { return response( array(), 503 ); }
        $data = json_decode( $args['body'], true ); $expected = 'v1=' . hash_hmac( 'sha256', $args['headers']['X-VPM-Timestamp'] . '.' . $args['body'], VPM_BOOKING_AUTOMATION_SECRET );
        ok( hash_equals( $expected, $args['headers']['X-VPM-Signature'] ), 'Automation signed event' ); $sent[] = $data; return response( array( 'accepted' => true ), 202 );
    }
    return new WP_Error( 'test_network_blocked', 'Unrelated network request blocked by test harness' );
}, 10, 3 );
function event( array $o, string $type, string $id, array $extra = array() ): array { $e = array_merge( array( 'id' => 'evt_' . $id, 'type' => $type, 'livemode' => false, 'data' => array( 'object' => $o ) ), $extra ); $body = wp_json_encode( $e ); return Stripe::webhook( $body, 't=' . time() . ',v1=' . hash_hmac( 'sha256', time() . '.' . $body, VPM_BOOKING_STRIPE_WEBHOOK_SECRET ) ); }
try {
    wp_set_current_user( 1 );
    $p = Core::partner( array( 'airport' => 'FRA', 'name' => 'Local fixture partner', 'stripe_account' => 'acct_fixturepartner', 'commission_bps' => 2000 ) );
    $c = Core::coupon( array( 'code' => 'TEST10', 'kind' => 'fixed', 'value' => 1000, 'max_uses' => 1, 'max_per_customer' => 1, 'budget_cents' => 1000, 'starts_at' => time() - 60, 'ends_at' => time() + 3600, 'airport' => 'FRA' ) );
    $input = array( 'partner_id' => (int) $p['id'], 'email' => 'booking-fixture@example.invalid', 'base_cents' => 10000, 'coupon' => 'TEST10' );
    $b = Core::create( $input, 'booking-fixture-01' ); $again = Core::create( $input, 'booking-fixture-01' ); ok( $b['id'] === $again['id'], 'Idempotent booking creation' );
    $changed = $input; $changed['base_cents'] = 12000; fails( function() use ( $changed ) { Core::create( $changed, 'booking-fixture-01' ); }, 'idempotency_conflict' );
    $other = $input; $other['email'] = 'other@example.invalid'; fails( function() use ( $other ) { Core::create( $other, 'booking-fixture-02' ); }, 'coupon_in_progress' );
    $plain = $input; unset( $plain['coupon'] ); $plain['email'] = 'second@example.invalid'; $second = Core::create( $plain, 'BOOKING-fixture-01' ); ok( $second['id'] !== $b['id'], 'Multiple unpaid bookings and case-sensitive request keys supported' );
    $q = Core::snapshot( $b ); ok( $q['charged'] === 9000 && $q['partner'] === 8000 && $q['commission'] === 2000 && $q['platform'] === 1000, '100 EUR price, 10 EUR coupon, partner 80, platform margin 10' );
    fails( function() { Finance::quote( 10000, 2000, 2100 ); }, 'subsidy_exceeds_commission_requires_funded_model' );
    fails( function() use ( $b ) { Stripe::checkout( $b['public_id'] ); }, 'booking_not_ready_for_checkout' );
    Core::confirm( $b['public_id'] ); $checkout = Stripe::checkout( $b['public_id'] ); ok( Stripe::checkout( $b['public_id'] )['session_id'] === $checkout['session_id'], 'Existing Checkout retrieved rather than duplicated' );
    $o = $sessions[$checkout['session_id']]; $o['payment_status'] = 'paid';
    fails( function() { Stripe::webhook( '{}', 't=' . time() . ',v1=incorrect' ); }, 'stripe_signature_invalid' );
    fails( function() { Stripe::webhook( '{}', 't=1,v1=incorrect' ); }, 'stripe_signature_expired' );
    fails( function() use ( $o ) { event( $o, 'checkout.session.completed', 'live_reject', array( 'livemode' => true ) ); }, 'stripe_live_or_invalid_event' );
    $wrong = $o; $wrong['amount_total'] = 1; fails( function() use ( $wrong ) { event( $wrong, 'checkout.session.completed', 'wrong_amount' ); }, 'stripe_amount_mismatch' );
    event( $o, 'checkout.session.completed', 'payment_01' ); event( $o, 'checkout.session.completed', 'payment_01' ); event( $o, 'checkout.session.async_payment_succeeded', 'payment_02' );
    $paid = Core::booking( $b['public_id'] ); ok( $paid['payment_state'] === 'paid', 'Signed test webhook marks paid' );
    ok( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'ledger' ) ) === 4, 'Duplicate/different payment events do not duplicate journal' );
    ok( (int) $wpdb->get_var( 'SELECT SUM(cents) FROM ' . DB::table( 'ledger' ) ) === 0, 'Paid journal balanced' );
    event( $o, 'checkout.session.expired', 'expired_after_paid' ); ok( Core::booking( $b['public_id'] )['payment_state'] === 'paid', 'Out-of-order expiry does not regress paid status' );
    $refund = Core::refund( $b['public_id'], 4500, 'refund-fixture-01' ); ok( Core::refund( $b['public_id'], 4500, 'refund-fixture-01' )['id'] === $refund['id'], 'Refund request idempotent' );
    fails( function() use ( $b ) { Core::refund( $b['public_id'], 5000, 'refund-fixture-overspend' ); }, 'refund_exceeds_payment' );
    $refund_fail = true; Stripe::execute_refund( (int) $refund['id'] ); ok( DB::row( 'refunds', 'id', $refund['id'] )['status'] === 'pending', 'Transport failure leaves refund retryable' );
    $refund_fail = false; Stripe::execute_refund( (int) $refund['id'] ); $partial = Core::booking( $b['public_id'] ); ok( $partial['payment_state'] === 'partially_refunded' && (int) $partial['refunded_cents'] === 4500, 'Partial refund applied after PSP success' );
    Stripe::execute_refund( (int) $refund['id'] ); ok( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'ledger' ) ) === 8, 'Repeated refund execution does not duplicate journal' );
    Core::cancel( $b['public_id'] ); Core::cancel( $b['public_id'] ); Stripe::retry_refunds();
    ok( Core::booking( $b['public_id'] )['payment_state'] === 'refunded', 'Confirmed cancellation schedules remaining refund' );
    $totals = $wpdb->get_results( 'SELECT account,SUM(cents) AS total FROM ' . DB::table( 'ledger' ) . ' GROUP BY account', ARRAY_A ); foreach ( $totals as $t ) { ok( (int) $t['total'] === 0, 'Full refund reverses:' . $t['account'] ); }
    // Cumulative cent allocation is exact, including odd values and repeated tiny refunds.
    for ( $base = 51; $base < 1000; $base += 13 ) { $x = Finance::quote( $base, 3333, 0 ); $sum = array_fill_keys( array_keys( Finance::paid_entries( $x ) ), 0 ); for ( $r = 1; $r <= $base; ++$r ) { $lines = Finance::refund_entries( $x, $r - 1, $r ); if ( array_sum( $lines ) !== 0 ) { throw new Exception( 'Rounding unbalanced' ); } foreach ( $lines as $k => $v ) { $sum[$k] += $v; } } foreach ( Finance::paid_entries( $x ) as $k => $v ) { if ( $sum[$k] !== -$v ) { throw new Exception( 'Cumulative rounding drift' ); } } }
    ok( true, 'Thousands of partial-cent allocations balance and reverse exactly' );
    event( array( 'id' => 're_external_fixture', 'payment_intent' => $paid['stripe_pi'], 'currency' => 'eur', 'amount' => 1, 'status' => 'succeeded' ), 'refund.updated', 'external_refund' ); ok( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'outbox' ) . " WHERE topic='reconciliation.required'" ) === 1, 'Unmapped external refunds create a reconciliation event, not a guessed journal' );
    $before = count( $sent ); Automation::run(); ok( count( $sent ) === $before, 'Automation disabled by default' );
    update_option( 'vpm_booking_automation_enabled', true ); update_option( 'vpm_booking_consent_text', 'Fixture consent text, not for publication.' ); update_option( 'vpm_booking_consent_version', 'fixture-v1' );
    fails( function() { Consent::request( 'newsletter@example.invalid', false, '127.0.0.1' ); }, 'explicit_marketing_consent_required' );
    Consent::request( 'newsletter@example.invalid', true, '127.0.0.1' );
    $contact = DB::row( 'contacts', 'email', 'newsletter@example.invalid' ); ok( $contact['state'] === 'pending', 'Signup alone does not grant marketing consent' );
    ok( strpos( Consent::form(), 'checked' ) === false, 'Consent checkbox not preselected' );
    $pending = (array) $wpdb->get_row( 'SELECT * FROM ' . DB::table( 'outbox' ) . " WHERE topic='marketing.confirmation_requested'", ARRAY_A ); $payload = Config::open( $pending['payload'] ); parse_str( wp_parse_url( $payload['confirmation_url'], PHP_URL_QUERY ), $confirm ); parse_str( wp_parse_url( $payload['unsubscribe_url'], PHP_URL_QUERY ), $unsub );
    ok( ! str_contains( $pending['payload'], $confirm['token'] ) && $contact['token_hash'] === hash( 'sha256', $confirm['token'] ), 'DOI token hashed in contact and encrypted in outbox' );
    $automation_fail = true; Automation::run(); ok( (int) $wpdb->get_var( 'SELECT MAX(attempts) FROM ' . DB::table( 'outbox' ) ) > 0, 'Failed automation requests remain retryable' );
    $automation_fail = false; DB::query( 'UPDATE ' . DB::table( 'outbox' ) . ' SET next_attempt=0' ); Automation::run(); $sent_count = count( $sent ); Automation::run(); ok( count( $sent ) === $sent_count, 'Acknowledged outbox events not resent' );
    ok( count( array_filter( $sent, function( $e ) { return $e['topic'] === 'payment.received' && $e['purpose'] === 'transactional'; } ) ) === 1, 'Transactional receipt event independent of newsletter consent' );
    Consent::act( $confirm['token'], 'confirm' ); Consent::act( $confirm['token'], 'confirm' ); ok( DB::row( 'contacts', 'id', $contact['id'] )['state'] === 'confirmed', 'Explicit DOI confirmation activates once' );
    ok( Automation::campaign( 'campaign-fixture-01' ) === 1 && Automation::campaign( 'campaign-fixture-01' ) === 0, 'Campaign enqueue is idempotent' ); Consent::act( $unsub['token'], 'unsubscribe' ); Automation::run();
    ok( ! array_filter( $sent, function( $e ) { return $e['topic'] === 'marketing.message.requested'; } ), 'Queued campaign suppressed after unsubscribe' );
    fails( function() use ( $confirm ) { Consent::act( $confirm['token'], 'confirm' ); }, 'consent_link_expired_or_revoked' );
    ok( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'consent_log' ) ) === 3, 'Consent request, confirmation and revocation audit retained' );
    fails( function() { Consent::request( 'newsletter@example.invalid', true, '127.0.0.1' ); }, 'newsletter_rate_limited' );
    Consent::request( 'remote-unsub@example.invalid', true, '127.0.0.2' ); $remote = DB::row( 'contacts', 'email', 'remote-unsub@example.invalid' ); $newrow = (array) $wpdb->get_row( $wpdb->prepare( 'SELECT payload FROM ' . DB::table( 'outbox' ) . ' WHERE contact_id=%d', $remote['id'] ), ARRAY_A ); $remote_payload = Config::open( $newrow['payload'] ); parse_str( wp_parse_url( $remote_payload['confirmation_url'], PHP_URL_QUERY ), $remote_token ); Consent::act( $remote_token['token'], 'confirm' );
    $remote_body = wp_json_encode( array( 'event_id' => 'remote-unsubscribe-fixture', 'topic' => 'marketing.unsubscribed', 'email' => 'remote-unsub@example.invalid' ) ); $remote_time = (string) time(); Automation::callback( $remote_body, $remote_time, 'v1=' . hash_hmac( 'sha256', $remote_time . '.' . $remote_body, VPM_BOOKING_AUTOMATION_SECRET ) ); ok( DB::row( 'contacts', 'id', $remote['id'] )['state'] === 'unsubscribed', 'Signed provider unsubscribe also suppresses local marketing' );
    $body = wp_json_encode( array( 'event_id' => 'invoice-fixture-event', 'topic' => 'document.available', 'booking_id' => $b['public_id'], 'reference' => 'fixture-invoice-01', 'kind' => 'invoice', 'url' => 'https://example.org/private-invoice' ) ); $timestamp = (string) time(); $sig = 'v1=' . hash_hmac( 'sha256', $timestamp . '.' . $body, VPM_BOOKING_AUTOMATION_SECRET );
    Automation::callback( $body, $timestamp, $sig ); Automation::callback( $body, $timestamp, $sig ); ok( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'documents' ) ) === 1, 'Signed document callback idempotent' );
    fails( function() use ( $body, $timestamp ) { Automation::callback( $body, $timestamp, 'v1=bad' ); }, 'automation_signature_invalid' );
    wp_set_current_user( 0 ); $request = new WP_REST_Request( 'GET', '/vpm-booking/v1/bookings/' . $b['public_id'] ); $denied = rest_do_request( $request ); ok( in_array( $denied->get_status(), array( 401, 403 ), true ), 'Public cannot read customer booking data' );
    $denied = rest_do_request( new WP_REST_Request( 'POST', '/vpm-booking/v1/partners' ) ); ok( in_array( $denied->get_status(), array( 401, 403 ), true ), 'Public cannot manage partners or amounts' );
    $path = '/tmp/vpm-booking-core-report.json'; file_put_contents( $path, wp_json_encode( array( 'checks' => $checks, 'count' => count( $checks ), 'external_http' => 'All intercepted by local fake transport; no real Stripe/email request', 'limitations' => array( 'Sandbox-only backend, no live money or partner capacity API', 'Stripe API contract and account model require real sandbox validation before live design', 'No compliant tax invoice generated; document/issuer interface only' ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
    echo count( $checks ) . " booking/payment/email/consent checks passed; no real external requests.\n";
} finally {
    foreach ( $tables as $table ) { $wpdb->query( "DELETE FROM $table" ); }
    foreach ( $settings as $key => $value ) { if ( null === $value ) { delete_option( $key ); } else { update_option( $key, $value ); } }
}
