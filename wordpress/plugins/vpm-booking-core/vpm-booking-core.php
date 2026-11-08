<?php
/**
 * Plugin Name: VPM Buchungskern
 * Description: Separater Sandbox-Buchungskern mit Stripe-Connect-Vorbereitung, Gutscheinjournal und E-Mail-Ereignissen. Keine Live-Zahlungen.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
foreach ( array( 'db', 'finance', 'core', 'stripe', 'automation', 'consent', 'rest' ) as $file ) { require_once __DIR__ . '/includes/' . $file . '.php'; }
register_activation_hook( __FILE__, array( 'VPM\\Booking\\DB', 'install' ) );
register_deactivation_hook( __FILE__, function() { wp_clear_scheduled_hook( 'vpm_booking_worker' ); } );
add_filter( 'cron_schedules', function( $s ) { $s['vpm_booking_five_minutes'] = array( 'interval' => 300, 'display' => 'VPM Booking – 5 Minuten' ); return $s; } );
add_action( 'vpm_booking_worker', function() { \VPM\Booking\Stripe::retry_refunds(); \VPM\Booking\Automation::run(); } );
add_action( 'rest_api_init', array( 'VPM\\Booking\\Rest', 'register' ) );
add_action( 'init', function() { add_shortcode( 'vpm_booking_newsletter', array( 'VPM\\Booking\\Consent', 'form' ) ); } );
add_action( 'template_redirect', array( 'VPM\\Booking\\Consent', 'landing' ), 0 );
add_action( 'admin_menu', array( 'VPM\\Booking\\Rest', 'admin_menu' ) );
