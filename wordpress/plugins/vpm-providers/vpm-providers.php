<?php
/**
 * Plugin Name: VPM Anbieter
 * Description: Theme-unabhängige Anbieter mit getrennten Plattformlinks; nutzbar in Gutenberg und Elementor per Shortcode.
 * Version: 0.2.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Text Domain: vpm-providers
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/cache.php';
add_action( 'init', function () { add_image_size( 'vpm-provider-card', 480, 0, false ); } );

function vpm_provider_fields() {
    return array(
        'active' => array( 'label' => 'In Übersichten anzeigen', 'type' => 'boolean' ),
        'features' => array( 'label' => 'Freigegebene Merkmale (eine Zeile je Merkmal)', 'type' => 'string' ),
        'rating' => array( 'label' => 'Freigegebene Bewertungsangabe einschließlich Quelle', 'type' => 'string' ),
        'price' => array( 'label' => 'Freigegebene Ab-Preisangabe einschließlich Zeitraum', 'type' => 'string' ),
        'parkos_url' => array( 'label' => 'Parkos: vollständiger Affiliate-Link', 'type' => 'string' ),
        'parkos_label' => array( 'label' => 'Parkos: freigegebener CTA-Text', 'type' => 'string' ),
        'parkvia_url' => array( 'label' => 'ParkVia: vollständiger Affiliate-Link', 'type' => 'string' ),
        'parkvia_label' => array( 'label' => 'ParkVia: freigegebener CTA-Text', 'type' => 'string' ),
        'image_caption' => array( 'label' => 'Bildkennzeichnung / Quellenangabe (bei KI: KI-generierte Illustration)', 'type' => 'string' ),
        'source_date' => array( 'label' => 'Interner Prüfstand / Quelle (nicht öffentlich)', 'type' => 'string' ),
    );
}

function vpm_sanitize_provider_value( $value, $key ) {
    if ( 'active' === $key ) { return (bool) $value; }
    if ( str_ends_with( $key, '_url' ) ) { return esc_url_raw( $value, array( 'https' ) ); }
    return 'features' === $key ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
}

add_action( 'init', function () {
    register_post_type( 'vpm_provider', array(
        'labels' => array( 'name' => 'Anbieter', 'singular_name' => 'Anbieter', 'add_new_item' => 'Anbieter hinzufügen', 'edit_item' => 'Anbieter bearbeiten' ),
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-car',
        'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
        'map_meta_cap' => true,
        'capability_type' => 'post',
    ) );
    foreach ( vpm_provider_fields() as $key => $field ) {
        register_post_meta( 'vpm_provider', '_vpm_' . $key, array(
            'type' => $field['type'], 'single' => true, 'show_in_rest' => true,
            'default' => 'boolean' === $field['type'] ? false : '',
            'sanitize_callback' => function ( $value ) use ( $key ) { return vpm_sanitize_provider_value( $value, $key ); },
            'auth_callback' => function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
        ) );
    }
} );

add_action( 'add_meta_boxes', function () {
    if ( use_block_editor_for_post_type( 'vpm_provider' ) ) { return; }
    add_meta_box( 'vpm-details', 'Anbieterangaben und Buchungslinks', 'vpm_provider_metabox', 'vpm_provider', 'normal', 'high' );
} );

add_action( 'enqueue_block_editor_assets', function () {
    $screen = get_current_screen();
    if ( ! $screen || 'vpm_provider' !== $screen->post_type ) { return; }
    wp_enqueue_script( 'vpm-provider-editor', plugins_url( 'assets/editor.js', __FILE__ ), array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' ), '0.2.0', true );
    wp_localize_script( 'vpm-provider-editor', 'vpmProviderFields', vpm_provider_fields() );
} );

// Provider metadata is an editorial data source, not a public API/feed.
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
    if ( preg_match( '#^/wp/v2/vpm_provider(?:/|$)#', $request->get_route() ) && ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error( 'vpm_rest_forbidden', 'Anbieterangaben benötigen einen angemeldeten Redaktionszugang.', array( 'status' => rest_authorization_required_code() ) );
    }
    return $result;
}, 10, 3 );

function vpm_provider_metabox( $post ) {
    wp_nonce_field( 'vpm_save_provider', 'vpm_provider_nonce' );
    echo '<p>Nur redaktionell freigegebene Angaben eintragen. Es findet keine Live-Abfrage von Preisen oder Verfügbarkeiten statt. Reihenfolge unter „Attribute“ festlegen.</p>';
    foreach ( vpm_provider_fields() as $key => $field ) {
        $value = get_post_meta( $post->ID, '_vpm_' . $key, true );
        $id = 'vpm_' . $key;
        echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br>';
        if ( 'active' === $key ) {
            echo '<input id="' . esc_attr( $id ) . '" type="checkbox" name="vpm[active]" value="1" ' . checked( $value, true, false ) . '>';
        } elseif ( 'features' === $key ) {
            echo '<textarea id="' . esc_attr( $id ) . '" name="vpm[features]" rows="5" class="widefat">' . esc_textarea( $value ) . '</textarea>';
        } else {
            $type = str_ends_with( $key, '_url' ) ? 'url' : 'text';
            echo '<input id="' . esc_attr( $id ) . '" type="' . $type . '" name="vpm[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="widefat">';
        }
        echo '</p>';
    }
}

add_action( 'save_post_vpm_provider', function ( $post_id ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
    if ( ! isset( $_POST['vpm_provider_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vpm_provider_nonce'] ) ), 'vpm_save_provider' ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    $values = isset( $_POST['vpm'] ) && is_array( $_POST['vpm'] ) ? wp_unslash( $_POST['vpm'] ) : array();
    foreach ( vpm_provider_fields() as $key => $field ) {
        $raw = $values[ $key ] ?? ( 'boolean' === $field['type'] ? false : '' );
        if ( is_array( $raw ) ) { $raw = ''; }
        update_post_meta( $post_id, '_vpm_' . $key, vpm_sanitize_provider_value( $raw, $key ) );
    }
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'vpm-provider-cards', plugins_url( 'assets/cards.css', __FILE__ ), array(), '0.2.0' );
} );

function vpm_render_provider_cards( $atts = array() ) {
    $atts = shortcode_atts( array( 'ids' => '' ), $atts, 'vpm_providers' );
    $args = array( 'post_type' => 'vpm_provider', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ), 'no_found_rows' => true, 'meta_query' => array( array( 'key' => '_vpm_active', 'value' => '1' ) ) );
    if ( '' !== trim( $atts['ids'] ) ) {
        $ids = wp_parse_id_list( $atts['ids'] );
        if ( ! $ids ) { return ''; }
        $args['post__in'] = $ids;
        $args['orderby'] = 'post__in';
    }
    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) { return ''; }
    ob_start();
    echo '<div class="vpm-provider-grid">';
    foreach ( $query->posts as $provider ) {
        $id = $provider->ID;
        echo '<article class="vpm-provider-card">';
        if ( has_post_thumbnail( $id ) ) {
            echo '<figure class="vpm-provider-image">' . get_the_post_thumbnail( $id, 'vpm-provider-card', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 600px) calc(100vw - 56px), (max-width: 1000px) calc((100vw - 100px) / 2), (max-width: 1264px) calc((100vw - 132px) / 3), 364px' ) );
            $caption = get_post_meta( $id, '_vpm_image_caption', true );
            if ( str_starts_with( (string) get_post_meta( get_post_thumbnail_id( $id ), '_vpm_asset_key', true ), 'provider-symbol-ai-' ) ) { $caption = 'KI-Symbolbild · keine Anbieteraufnahme'; }
            if ( $caption ) { echo '<figcaption>' . esc_html( $caption ) . '</figcaption>'; }
            echo '</figure>';
        }
        echo '<div class="vpm-provider-body"><h3>' . esc_html( get_the_title( $id ) ) . '</h3>';
        $rating = get_post_meta( $id, '_vpm_rating', true );
        if ( $rating ) { echo '<p class="vpm-provider-rating">' . esc_html( $rating ) . '</p>'; }
        if ( $provider->post_content ) {
            // Render Gutenberg content without changing the surrounding page's global post.
            echo '<div class="vpm-provider-description">' . wp_kses_post( do_blocks( $provider->post_content ) ) . '</div>';
        }
        $features = array_filter( array_map( 'trim', explode( "\n", get_post_meta( $id, '_vpm_features', true ) ) ) );
        if ( $features ) {
            echo '<ul class="vpm-provider-features">';
            foreach ( $features as $feature ) { echo '<li>' . esc_html( $feature ) . '</li>'; }
            echo '</ul>';
        }
        $price = get_post_meta( $id, '_vpm_price', true );
        if ( $price ) { echo '<p class="vpm-provider-price">' . esc_html( $price ) . '</p>'; }
        echo '<div class="vpm-provider-links">';
        foreach ( array( 'parkos' => 'Parkos', 'parkvia' => 'ParkVia' ) as $key => $platform ) {
            $url = get_post_meta( $id, '_vpm_' . $key . '_url', true );
            $label = get_post_meta( $id, '_vpm_' . $key . '_label', true );
            if ( $url && $label ) {
                echo '<div><span class="vpm-provider-platform">' . esc_html( 'Buchbar über ' . $platform . ':' ) . '</span><a class="vpm-provider-button" href="' . esc_url( $url, array( 'https' ) ) . '" rel="sponsored nofollow">' . esc_html( $label ) . '<span class="screen-reader-text"> – ' . esc_html( get_the_title( $id ) . ' / ' . $platform ) . '</span></a></div>';
            }
        }
        echo '</div></div></article>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'vpm_providers', 'vpm_render_provider_cards' );

// No activation imports, public provider URLs, analytics, cookies or external requests.
// Data is deliberately retained when the plugin is deactivated or deleted.
