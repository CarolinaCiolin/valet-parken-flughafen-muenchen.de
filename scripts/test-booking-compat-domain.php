<?php
// Domain/WordPress tests: no real partner API, payment or mail calls.
define( 'WP_ADMIN', true ); define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
define( 'VPM_BOOKING_AUTOMATION_URL', 'https://example.org/fixture' ); define( 'VPM_BOOKING_AUTOMATION_SECRET', 'fixture-only-automation-secret-at-least-32' );
require '/var/www/html/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== '127.0.0.1' ) { exit( 2 ); }
use VPM\Booking\DB; use VPM\Booking\Core; use VPM\Booking\Config; use VPM\Booking\Airports; use VPM\Booking\PublicApi; use VPM\Booking\LocalTime; use VPM\Booking\Frontend; use VPM\Booking\Compatibility; use VPM\Booking\Consent;
global $wpdb; $tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'vpm_booking_%' ) );
foreach ( $tables as $table ) { if ( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ) { throw new Exception( 'Nonempty booking tables refused' ); } }
$options = array(); foreach ( array( 'vpm_booking_frontend_enabled', 'vpm_booking_airports', 'vpm_booking_automation_enabled', 'vpm_booking_consent_text', 'vpm_booking_consent_version' ) as $k ) { $options[$k] = get_option( $k, null ); }
$ids = array(); $checks = array();
function check_compat( $ok, $name ) { global $checks; if ( ! $ok ) { throw new Exception( $name ); } $checks[] = $name; }
function reject_compat( callable $f, string $code ) { try { $f(); } catch ( Throwable $e ) { check_compat( $e->getMessage() === $code, 'reject:' . $code ); return; } throw new Exception( 'Expected ' . $code ); }
add_filter( 'pre_http_request', function() { return new WP_Error( 'fixture_block' ); } ); add_filter( 'pre_wp_mail', '__return_true' );
try {
    check_compat( count( Airports::all() ) === 15 && count( array_unique( array_column( Airports::all(), 'code' ) ) ) === 15, '15 unique editable DACH starter airports' );
    check_compat( Airports::get( 'BSL' )['location_country'] === 'FR' && Airports::get( 'BSL' )['region'] === 'CH', 'Basel is not falsely located in Switzerland' );
    check_compat( count( Airports::validate( Airports::all() ) ) === 15, 'Default catalogue validates' );
    $bad = Airports::all(); $bad[] = $bad[0]; reject_compat( function() use ( $bad ) { Airports::validate( $bad ); }, 'invalid_airport_record' );
    $a = Frontend::selector( array( 'locale' => 'de' ) ) . Frontend::selector( array( 'locale' => 'en' ) ); preg_match_all( '/\bid="([^"]+)"/', $a, $matches ); check_compat( count( $matches[1] ) === count( array_unique( $matches[1] ) ), 'Repeated selectors use unique IDs and anchors' );
    foreach ( array( 'form', 'airport-selector', 'newsletter' ) as $block ) { check_compat( WP_Block_Type_Registry::get_instance()->is_registered( 'vpm-booking/' . $block ), 'Native Gutenberg block:' . $block ); }
    reject_compat( function() { PublicApi::instant( '2027-03-28T02:30', 'Europe/Berlin', 'departure' ); }, 'nonexistent_departure' );
    reject_compat( function() { PublicApi::instant( '2027-10-31T02:30', 'Europe/Berlin', 'departure' ); }, 'ambiguous_local_time' );
    check_compat( PublicApi::instant( '2027-10-31T02:30', 'Europe/Berlin', 'departure', '+01:00' ) - PublicApi::instant( '2027-10-31T02:30', 'Europe/Berlin', 'departure', '+02:00' ) === 3600, 'Both autumn occurrences explicitly selectable' );
    $p = Core::partner( array( 'airport' => 'FRA', 'name' => 'Adapter fixture', 'stripe_account' => 'acct_compatfixture', 'commission_bps' => 2000 ) );
    $zone = new DateTimeZone( 'Europe/Berlin' ); $data = array( 'airport' => 'FRA', 'departure' => ( new DateTimeImmutable( 'tomorrow 10:00', $zone ) )->format( 'Y-m-d\TH:i' ), 'return' => ( new DateTimeImmutable( '+8 days 12:00', $zone ) )->format( 'Y-m-d\TH:i' ) );
    update_option( 'vpm_booking_frontend_enabled', false ); reject_compat( function() use ( $data ) { PublicApi::offers( $data ); }, 'frontend_not_enabled' );
    update_option( 'vpm_booking_frontend_enabled', true ); reject_compat( function() use ( $data ) { PublicApi::offers( $data ); }, 'booking_adapter_not_configured' );
    $adapter = function( $offers, $context ) use ( $p ) { return array( array( 'partner_id' => (int) $p['id'], 'base_cents' => 10000, 'currency' => 'eur', 'terms' => 'Local fixture terms' ) ); };
    add_filter( 'vpm_booking_quote_offers', $adapter, 10, 2 );
    $offers = PublicApi::offers( $data ); check_compat( count( $offers['offers'] ) === 1 && $offers['offers'][0]['amount_cents'] === 10000, 'Server adapter creates expiring stored offer' );
    check_compat( ! str_contains( wp_json_encode( $offers ), 'acct_' ) && ! str_contains( wp_json_encode( $offers ), 'commission' ), 'Public offer exposes no Stripe account or commission' );
    $input = array( 'quote_id' => $offers['offers'][0]['quote_id'], 'email' => 'compat-fixture@example.invalid', 'locale' => 'en' );
    reject_compat( function() use ( $input ) { PublicApi::request( array_merge( $input, array( 'base_cents' => 1 ) ) ); }, 'client_amount_or_state_forbidden' );
    $received = PublicApi::request( $input ); $again = PublicApi::request( $input ); check_compat( $received['reference'] === $again['reference'] && $received['state'] === 'requested' && $received['payment_state'] === 'unpaid', 'Public request is idempotent and never a paid confirmation' );
    $b = Core::booking( $received['reference'] ); $q = Core::snapshot( $b ); check_compat( $q['travel']['timezone'] === 'Europe/Berlin' && $q['travel']['airport'] === 'FRA' && $q['offer_terms'] === 'Local fixture terms', 'Travel timezone and trusted offer snapshot persisted' );
    $export = Compatibility::export( 'compat-fixture@example.invalid' ); check_compat( count( $export['data'] ) === 1 && ! str_contains( wp_json_encode( $export ), 'acct_' ) && ! str_contains( wp_json_encode( $export ), 'commission' ), 'WordPress export excludes settlement secrets' );
    DB::update( 'quotes', array( 'expires_at' => time() - 1 ), array( 'public_id' => $input['quote_id'] ) ); reject_compat( function() use ( $input ) { PublicApi::request( $input ); }, 'quote_expired' );
    $draft = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Airport target fixture', 'post_name' => 'airport-target-fixture' ) ); $ids[] = $draft; if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $draft, 'de' ); }
    $list = Airports::all(); $list[0]['page_id'] = $draft; update_option( 'vpm_booking_airports', $list ); check_compat( Airports::public_list( 'de' )[0]['url'] === '', 'Draft airport page never linked' );
    wp_update_post( array( 'ID' => $draft, 'post_status' => 'publish' ) ); check_compat( Airports::public_list( 'de' )[0]['url'] !== '', 'Published airport target linked' );
    check_compat( Airports::public_list( 'en' )[0]['url'] === '', 'Missing English translation never silently links German page' );
    update_option( 'vpm_booking_automation_enabled', true ); update_option( 'vpm_booking_consent_text', 'Compatibility fixture consent.' ); update_option( 'vpm_booking_consent_version', 'compat-fixture' ); Consent::request( 'compat-fixture@example.invalid', true, '127.0.0.1' );
    $erased = Compatibility::erase( 'compat-fixture@example.invalid' ); check_compat( $erased['items_retained'] && DB::row( 'contacts', 'email', 'compat-fixture@example.invalid' )['state'] === 'unsubscribed', 'WordPress erasure request stops marketing and retains records for review' );
    $health = Compatibility::health(); check_compat( $health['test'] === 'vpm_booking_hosting' && Compatibility::hosting()['InnoDB'], 'Hosting Site Health check registered and InnoDB verified' );
    file_put_contents( '/tmp/vpm-booking-compat-domain-report.json', wp_json_encode( array( 'checks' => $checks, 'count' => count( $checks ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
    echo count( $checks ) . " compatibility/domain checks passed.\n";
} finally {
    foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
    foreach ( $tables as $table ) { $wpdb->query( "DELETE FROM $table" ); }
    foreach ( $options as $k => $v ) { if ( null === $v ) { delete_option( $k ); } else { update_option( $k, $v ); } }
}
