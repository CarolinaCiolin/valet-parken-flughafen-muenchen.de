<?php
/** Theme integration, without content or provider storage. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'vpm-hero-small', 480, 0, false );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/site.css' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
    register_nav_menus( array( 'primary' => 'Hauptnavigation', 'footer' => 'Footernavigation' ) );
} );

// Establish the collapsed mobile menu before the first paint; without JS the menu remains accessible.
add_action( 'wp_head', function () { echo '<script>document.documentElement.classList.add("vpm-js");</script>'; }, 0 );

add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'vpm-hybrid', get_theme_file_uri( 'assets/site.css' ), array(), $version );
    wp_enqueue_script( 'vpm-navigation', get_theme_file_uri( 'assets/navigation.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

add_action( 'init', function () {
    register_block_pattern_category( 'vpm', array( 'label' => 'Valet Portal' ) );
} );

add_action( 'elementor/theme/register_locations', function ( $manager ) {
    $manager->register_all_core_location();
} );

// Keep the frontend free of remote font providers by default.
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

function vpm_customizer( $customizer ) {
    $customizer->add_section( 'vpm_header', array( 'title' => 'Header-CTA' ) );
    $customizer->add_setting( 'vpm_cta_label', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $customizer->add_setting( 'vpm_cta_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $customizer->add_control( 'vpm_cta_label', array( 'section' => 'vpm_header', 'label' => 'Freigegebener Buttontext', 'type' => 'text' ) );
    $customizer->add_section( 'vpm_footer', array( 'title' => 'Footer-Hinweis' ) );
    $customizer->add_setting( 'vpm_footer_note', array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $customizer->add_control( 'vpm_footer_note', array( 'section' => 'vpm_footer', 'label' => 'Freigegebener Hinweistext', 'type' => 'textarea' ) );
    $customizer->add_control( 'vpm_cta_url', array( 'section' => 'vpm_header', 'label' => 'Zugeordneter Affiliate-Link (vollständig)', 'type' => 'url' ) );
}
add_action( 'customize_register', 'vpm_customizer' );

// A desktop hero column does not need a full-width image download.
add_filter( 'wp_calculate_image_sizes', function ( $sizes, $size, $src, $meta, $id ) {
    if ( 'hero-ai-v1' === get_post_meta( $id, '_vpm_asset_key', true ) ) {
        return '(max-width: 781px) calc(100vw - 36px), (max-width: 1264px) calc((100vw - 96px) * 0.48), 576px';
    }
    return $sizes;
}, 10, 5 );
