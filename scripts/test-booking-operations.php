<?php
// Loopback only. All payment/automation transport intercepted; no real email or charge.
define( 'WP_ADMIN', true ); define( 'VPM_BOOKING_STRIPE_TEST_SECRET', 'sk_test_operationsfixture' ); define( 'VPM_BOOKING_STRIPE_WEBHOOK_SECRET', 'whsec_operationsfixture' ); define( 'VPM_BOOKING_AUTOMATION_URL', 'https://example.org/fixture' ); define( 'VPM_BOOKING_AUTOMATION_SECRET', 'operations-fixture-secret-at-least-32-bytes' );
require '/var/www/html/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== '127.0.0.1' ) { exit( 2 ); }
use VPM\Booking\DB; use VPM\Booking\Core; use VPM\Booking\Operations; use VPM\Booking\Airports; use VPM\Booking\PublicApi; use VPM\Booking\Customer; use VPM\Booking\Stripe; use VPM\Booking\Compatibility; use VPM\Booking\Config;
global $wpdb; $tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'vpm_booking_%' ) ); foreach ( $tables as $table ) { if ( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ) { throw new Exception( 'Nonempty tables refused' ); } }
$settings = array(); foreach ( array( 'vpm_booking_airports', 'vpm_booking_frontend_enabled', 'vpm_booking_automation_enabled' ) as $k ) { $settings[$k] = get_option( $k, null ); }
$checks = array(); $network = 0;
function yes_op( $v, $label ) { global $checks; if ( ! $v ) { throw new Exception( $label ); } $checks[] = $label; }
function no_op( $f, $code ) { try { $f(); } catch ( Throwable $e ) { yes_op( $e->getMessage() === $code, 'reject:' . $code ); return; } throw new Exception( 'Expected:' . $code ); }
function token_op( $link ) { parse_str( wp_parse_url( $link['url'], PHP_URL_FRAGMENT ), $f ); return $f['token']; }
add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$network ) {
    ++$network; $body = $args['body'] ?? array();
    if ( $url === 'https://api.stripe.com/v1/checkout/sessions' ) { $data = array( 'id' => 'cs_test_operations', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_operations' ); }
    elseif ( $url === 'https://api.stripe.com/v1/refunds' ) { $data = array_merge( $body, array( 'id' => 're_operations', 'currency' => 'eur', 'status' => 'succeeded' ) ); }
    else { return new WP_Error( 'unexpected_network' ); }
    return array( 'response' => array( 'code' => 200 ), 'headers' => array(), 'body' => wp_json_encode( $data ) );
}, 999, 3 ); add_filter( 'pre_wp_mail', '__return_true' );
try {
    wp_set_current_user( 0 ); no_op( function() { Operations::handle( array( 'vpm_operation' => 'airports' ) ); }, 'permission_denied' ); wp_set_current_user( 1 );
    $rows = array(); foreach ( Airports::all() as $a ) { $rows[] = array( 'name_de' => $a['name_de'], 'name_en' => $a['name_en'], 'page_id' => '0' ); } $rows[0]['name_de'] = '<script>bad</script>Frankfurt'; Operations::save_airports( $rows );
    yes_op( ! str_contains( Airports::all()[0]['name_de'], '<' ) && count( Airports::all() ) === 15, 'Structured airport save sanitizes names and preserves catalogue' );
    $bad = $rows; $bad[0]['page_id'] = '-1'; no_op( function() use ( $bad ) { Operations::save_airports( $bad ); }, 'invalid_integer' );
    $p = Operations::partner( array( 'airport' => 'FRA', 'name' => 'Testpartner', 'commission_bps' => '2000', 'stripe_account' => '' ) ); yes_op( $p['stripe_account'] === '' && (int) $p['active'] === 0, 'Partner can be prepared without Stripe credentials or activation' );
    no_op( function() use ( $p ) { Core::create( array( 'partner_id' => (int) $p['id'], 'base_cents' => 10000, 'email' => 'fixture@example.invalid' ), 'paused-fixture-key' ); }, 'partner_inactive' );
    $p = Operations::partner( array( 'id' => (string) $p['id'], 'airport' => 'FRA', 'name' => 'Testpartner', 'commission_bps' => '2000', 'stripe_account' => 'acct_operationsfixture', 'active' => '1' ) );
    Operations::handle( array( 'vpm_operation' => 'coupon', 'code' => 'OPS10', 'kind' => 'fixed', 'value' => '1000', 'budget_cents' => '20000', 'max_uses' => '10', 'max_per_customer' => '1', 'starts_at' => gmdate( 'Y-m-d\TH:i', time() - 60 ), 'ends_at' => gmdate( 'Y-m-d\TH:i', time() + DAY_IN_SECONDS ), 'partner_id' => (string) $p['id'], 'airport' => 'FRA' ) );
    yes_op( (bool) DB::row( 'coupons', 'code', 'OPS10' ), 'Admin coupon dates and numeric fields create valid scoped coupon' );
    update_option( 'vpm_booking_frontend_enabled', true ); add_filter( 'vpm_booking_quote_offers', function() use ( $p ) { return array( array( 'partner_id' => (int) $p['id'], 'base_cents' => 10000, 'currency' => 'eur', 'terms' => 'Local test terms' ) ); } );
    $offer = PublicApi::offers( array( 'airport' => 'FRA', 'departure' => gmdate( 'Y-m-d', time() + 10 * DAY_IN_SECONDS ) . 'T10:00', 'return' => gmdate( 'Y-m-d', time() + 18 * DAY_IN_SECONDS ) . 'T12:00' ) )['offers'][0];
    $received = PublicApi::request( array( 'quote_id' => $offer['quote_id'], 'email' => 'operations@example.invalid', 'coupon' => 'OPS10' ) ); $b = Core::booking( $received['reference'] );
    yes_op( $received['amount_cents'] === 9000 && $b['state'] === 'requested', 'Offer to discounted public enquiry succeeds' );
    $link = Customer::issue( $b ); $token = token_op( $link ); $view = Customer::view( $token );
    yes_op( strlen( $token ) === 64 && ! str_contains( wp_parse_url( $link['url'], PHP_URL_QUERY ), $token ), '256-bit token travels in URL fragment, not request query' );
    yes_op( ! str_contains( wp_json_encode( $wpdb->get_results( 'SELECT * FROM ' . DB::table( 'access' ), ARRAY_A ) ), $token ), 'Access database stores only token hash' );
    yes_op( ! str_contains( wp_json_encode( $view ), 'acct_' ) && ! str_contains( wp_json_encode( $view ), 'commission' ) && ! str_contains( wp_json_encode( $view ), 'operations@' ), 'Customer projection excludes internal account, commission and email' );
    no_op( function() { Customer::view( str_repeat( 'a', 64 ) ); }, 'access_unavailable' );
    $second = Customer::issue( $b ); no_op( function() use ( $token ) { Customer::view( $token ); }, 'access_unavailable' ); $token = token_op( $second );
    yes_op( Customer::view( $token )['reference'] === $b['public_id'], 'Rotated link works and old link is revoked' );
    Core::confirm( $b['public_id'] ); $session = Stripe::checkout( $b['public_id'] );
    yes_op( $session['mode'] === 'test' && Customer::view( $token )['payment_state'] === 'pending', 'Admin-confirmed enquiry proceeds to intercepted Stripe test checkout' );
    $event = array( 'id' => 'evt_operationspaid', 'livemode' => false, 'type' => 'checkout.session.completed', 'data' => array( 'object' => array( 'id' => $session['session_id'], 'metadata' => array( 'booking_id' => $b['public_id'] ), 'client_reference_id' => $b['public_id'], 'currency' => 'eur', 'amount_total' => 9000, 'payment_status' => 'paid', 'payment_intent' => 'pi_operations' ) ) );
    $body = wp_json_encode( $event ); $time = time(); Stripe::webhook( $body, 't=' . $time . ',v1=' . hash_hmac( 'sha256', $time . '.' . $body, VPM_BOOKING_STRIPE_WEBHOOK_SECRET ) );
    yes_op( Customer::view( $token )['payment_state'] === 'paid', 'Signed paid webhook appears in customer status' );
    $one = Customer::cancellation( $token ); $two = Customer::cancellation( $token ); yes_op( $one === $two && (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DB::table( 'changes' ) ) === 1, 'Repeated cancellation request creates one review case' );
    $view = Customer::view( $token ); yes_op( $view['state'] === 'confirmed' && $view['payment_state'] === 'paid' && $view['refunded_cents'] === 0, 'Cancellation request never auto-cancels or refunds' );
    $change_id = (int) $wpdb->get_var( 'SELECT id FROM ' . DB::table( 'changes' ) ); Operations::handle( array( 'vpm_operation' => 'review', 'id' => (string) $change_id ) ); yes_op( Customer::view( $token )['cancellation_request'] === 'reviewed' && Core::booking( $b['public_id'] )['state'] === 'confirmed', 'Review acknowledgement leaves financial state unchanged' );
    $export = Compatibility::export( 'operations@example.invalid' ); yes_op( str_contains( wp_json_encode( $export ), 'reviewed' ) && ! str_contains( wp_json_encode( $export ), $token ), 'Privacy export includes review request but never access secret' );
    Core::cancel( $b['public_id'] ); $refund_id = (int) $wpdb->get_var( 'SELECT id FROM ' . DB::table( 'refunds' ) ); Stripe::execute_refund( $refund_id );
    yes_op( Customer::view( $token )['payment_state'] === 'refunded' && Customer::view( $token )['refunded_cents'] === 9000, 'Explicit operator cancellation and intercepted refund reconcile customer status' );
    update_option( 'vpm_booking_automation_enabled', true );
    yes_op( Customer::access_request( array( 'reference' => $b['public_id'], 'email' => 'operations@example.invalid' ) ) === Customer::access_request( array( 'reference' => $b['public_id'], 'email' => 'wrong@example.invalid' ) ), 'Access request never enumerates matching email/reference pairs' );
    no_op( function() use ( $token ) { Customer::view( $token ); }, 'access_unavailable' );
    $out = $wpdb->get_row( 'SELECT * FROM ' . DB::table( 'outbox' ) . " WHERE topic='booking.access_requested'", ARRAY_A ); $payload = Config::open( $out['payload'] ); $token = token_op( array( 'url' => $payload['access_url'] ) ); yes_op( Customer::view( $token )['reference'] === $b['public_id'], 'Access link is queued in encrypted transactional outbox' );
    Compatibility::erase( 'operations@example.invalid' ); no_op( function() use ( $token ) { Customer::view( $token ); }, 'access_unavailable' );
    $fresh = Customer::issue( $b ); $token = token_op( $fresh ); DB::query( $wpdb->prepare( 'UPDATE ' . DB::table( 'access' ) . ' SET expires_at=%d', time() - 1 ) ); no_op( function() use ( $token ) { Customer::view( $token ); }, 'access_unavailable' );
    $missing = Core::partner( array( 'airport' => 'FRA', 'name' => 'No payment account', 'stripe_account' => '', 'commission_bps' => 2000 ) ); $b2 = Core::create( array( 'partner_id' => (int) $missing['id'], 'base_cents' => 10000, 'email' => 'no-account@example.invalid' ), 'missing-account-fixture' ); Core::confirm( $b2['public_id'] ); no_op( function() use ( $b2 ) { Stripe::checkout( $b2['public_id'] ); }, 'partner_payment_account_missing' ); yes_op( Core::booking( $b2['public_id'] )['payment_state'] === 'unpaid', 'Missing account fails before payment state changes' );
    update_option( 'vpm_booking_db_version', 2 ); DB::install(); yes_op( Core::booking( $b['public_id'] )['payment_state'] === 'refunded', 'Schema migration preserves existing booking and refund state' );
    yes_op( ! str_contains( wp_json_encode( $wpdb->get_results( 'SELECT * FROM ' . DB::table( 'audit' ), ARRAY_A ) ), $token ), 'Audit contains no bearer token' );
    yes_op( $network === 2, 'Exactly two intercepted Stripe calls; no real outbound transport' );
    file_put_contents( '/tmp/vpm-booking-operations-report.json', wp_json_encode( array( 'count' => count( $checks ), 'checks' => $checks ), JSON_PRETTY_PRINT ) ); echo count( $checks ) . " operations/customer flow checks passed.\n";
} finally { foreach ( $tables as $table ) { $wpdb->query( "DELETE FROM $table" ); } foreach ( $settings as $k => $v ) { if ( $v === null ) { delete_option( $k ); } else { update_option( $k, $v ); } } }
