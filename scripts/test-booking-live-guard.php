<?php
// Fake deliberately live-shaped key: must be rejected before any Stripe request.
define( 'VPM_BOOKING_STRIPE_TEST_SECRET', 'sk_live_fixture_only_not_a_real_key' );
define( 'WP_ADMIN', true ); require '/var/www/html/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== '127.0.0.1' ) { exit( 2 ); }
$calls = 0; add_filter( 'pre_http_request', function( $pre, $args, $url ) use ( &$calls ) { if ( str_starts_with( $url, 'https://api.stripe.com/' ) ) { ++$calls; } return new WP_Error( 'test_blocked' ); }, 10, 3 );
if ( VPM\Booking\Stripe::ready() ) { throw new Exception( 'Live key accepted' ); }
try { VPM\Booking\Stripe::api( 'checkout/sessions', array(), 'fixture-live-guard' ); throw new Exception( 'Live call accepted' ); }
catch ( RuntimeException $e ) { if ( $e->getMessage() !== 'stripe_test_key_required_live_disabled' ) { throw $e; } }
if ( $calls !== 0 ) { throw new Exception( 'Network called' ); }
echo "Live-key guard passed before any Stripe network request.\n";
