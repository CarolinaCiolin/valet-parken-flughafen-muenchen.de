<?php
/** Reconcile the observed Complianz no-banner branch after late initialization. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_reconcile_consent_hooks() {
    if ( is_admin() || is_preview() || ! class_exists( 'COMPLIANZ' ) || ! isset( COMPLIANZ::$banner_loader ) || ! get_option( 'cmplz_wizard_completed_once' ) ) { return; }
    if ( ! function_exists( 'cmplz_is_pagebuilder_preview' ) || cmplz_is_pagebuilder_preview() ) { return; }
    $loader = COMPLIANZ::$banner_loader;
    foreach ( array( 'site_needs_cookie_warning', 'enqueue_assets', 'cookiebanner_css', 'cookiebanner_html', 'add_asyncdefer_attribute', 'dynamic_gtm_enqueue', 'inline_cookie_script_no_warning' ) as $method ) {
        if ( ! is_callable( array( $loader, $method ) ) ) { return; }
    }
    // Only repair the exact observed contradictory branch, never arbitrary removed hooks.
    if ( false === has_action( 'wp_print_footer_scripts', array( $loader, 'inline_cookie_script_no_warning' ) ) || false !== has_action( 'wp_footer', array( $loader, 'cookiebanner_html' ) ) || false !== has_action( 'wp_enqueue_scripts', array( $loader, 'enqueue_assets' ) ) ) { return; }
    if ( ! $loader->site_needs_cookie_warning() || ! function_exists( 'cmplz_get_cookiebanner' ) || ! function_exists( 'cmplz_get_option' ) ) { return; }
    $banner = cmplz_get_cookiebanner();
    if ( ! $banner || $banner->disable_cookiebanner || 'no' === cmplz_get_option( 'enable_cookie_banner' ) ) { return; }
    remove_action( 'wp_print_footer_scripts', array( $loader, 'inline_cookie_script_no_warning' ), 10 );
    add_action( 'wp_enqueue_scripts', array( $loader, 'enqueue_assets' ), PHP_INT_MAX - 50 );
    add_filter( 'script_loader_tag', array( $loader, 'add_asyncdefer_attribute' ), 10, 2 );
    add_action( 'wp_head', array( $loader, 'cookiebanner_css' ) );
    add_action( 'wp_footer', array( $loader, 'cookiebanner_html' ) );
    $loader->dynamic_gtm_enqueue();
    $GLOBALS['vpm_consent_hooks_reconciled'] = true;
}
add_action( 'wp', 'vpm_reconcile_consent_hooks', PHP_INT_MAX );
// wp_head calls wp_enqueue_scripts before head styles; Complianz's own enqueue runs later.
add_action( 'wp_enqueue_scripts', 'vpm_reconcile_consent_hooks', PHP_INT_MAX - 100 );
