<?php
/**
 * Plugin Name: VPM Buchungskern
 * Description: Partnerportal, manuelle Kapazitäten, Adaptervertrag und mehrstufige Demo mit Sandbox-Zahlungsgrundlage. Keine Live-Zahlungen.
 * Version: 0.10.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
foreach ( array( 'db', 'finance', 'vouchers', 'core', 'stripe', 'automation', 'consent', 'airports', 'products', 'public-api', 'frontend', 'compatibility', 'operations', 'customer', 'tenants', 'adapters', 'flow', 'portal', 'logistics', 'commerce', 'conditions', 'promotions', 'broker', 'rest' ) as $file ) { require_once __DIR__ . '/includes/' . $file . '.php'; }
add_action( 'plugins_loaded', function() { if ( (int) get_option( 'vpm_booking_db_version', 0 ) < 10 ) { \VPM\Booking\DB::install(); } } );
\VPM\Booking\Compatibility::register();
add_action( 'init', array( 'VPM\\Booking\\Frontend', 'register' ), 20 );
add_action( 'wp_enqueue_scripts', array( 'VPM\\Booking\\Frontend', 'detect_assets' ) );
add_action( 'load-tools_page_vpm-booking-core', array( 'VPM\\Booking\\Frontend', 'admin_save' ), 1 );
register_activation_hook( __FILE__, array( 'VPM\\Booking\\DB', 'install' ) );
register_deactivation_hook( __FILE__, function() { wp_clear_scheduled_hook( 'vpm_booking_worker' ); } );
add_filter( 'cron_schedules', function( $s ) { $s['vpm_booking_five_minutes'] = array( 'interval' => 300, 'display' => 'VPM Booking – 5 Minuten' ); return $s; } );
add_action( 'vpm_booking_worker', function() { \VPM\Booking\Stripe::retry_refunds(); \VPM\Booking\Automation::run(); update_option( 'vpm_booking_last_worker', time(), false ); global $wpdb; $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . \VPM\Booking\DB::table( 'quotes' ) . ' WHERE expires_at<%d LIMIT 500', time() - DAY_IN_SECONDS ) ); $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . \VPM\Booking\DB::table( 'rate_limits' ) . ' WHERE expires_at<%d LIMIT 500', time() ) ); } );
add_action( 'rest_api_init', array( 'VPM\\Booking\\Rest', 'register' ) );
add_action( 'rest_api_init', array( 'VPM\\Booking\\PublicApi', 'register' ) );
add_action( 'init', function() { add_shortcode( 'vpm_booking_newsletter', array( 'VPM\\Booking\\Consent', 'form' ) ); } );
add_action( 'template_redirect', array( 'VPM\\Booking\\Consent', 'landing' ), 0 );
add_action( 'admin_menu', array( 'VPM\\Booking\\Rest', 'admin_menu' ) );

\VPM\Booking\Operations::register();
\VPM\Booking\Customer::register();

add_action( 'vpm_booking_worker', array( 'VPM\\Booking\\Loyalty', 'run' ), 30 );

\VPM\Booking\Portal::register();
add_filter( 'vpm_booking_quote_offers', array( 'VPM\\Booking\\Adapters', 'offers' ), 30, 2 );
add_action( 'vpm_booking_worker', array( 'VPM\\Booking\\Flow', 'run' ), 5 );

add_action('vpm_booking_worker',array('VPM\\Booking\\Promotions','run'),25);
