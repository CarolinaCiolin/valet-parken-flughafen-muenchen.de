<?php
/** Show published legal and Complianz cookie pages without duplicate menu entries. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_footer_legal_pages() {
    $locations = get_nav_menu_locations();
    $menu = ! empty( $locations['footer'] ) ? wp_get_nav_menu_items( $locations['footer'] ) : array();
    $existing = array();
    foreach ( $menu ?: array() as $item ) { $existing[] = untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) ); }
    $links = array();
    foreach ( array( 'impressum' => array( 'Impressum', 'Legal notice' ), 'datenschutz' => array( 'Datenschutz', 'Privacy policy' ) ) as $slug => $labels ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page ) { continue; }
        $id = $page->ID;
        if ( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' ) && pll_current_language() ) {
            $id = pll_get_post( $id, pll_current_language( 'slug' ) );
        }
        if ( ! $id || 'publish' !== get_post_status( $id ) || '' !== (string) get_post_field( 'post_password', $id ) ) { continue; }
        $url = get_permalink( $id );
        if ( in_array( untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) ), $existing, true ) ) { continue; }
        $links[] = array( 'url' => $url, 'label' => vpm_theme_text( $labels[0], $labels[1] ) );
    }
    // Complianz adds links to a selected menu, which need not be the active Polylang footer.
    // Use its actual configured document ID, never guess a cookie-policy slug.
    if ( function_exists( 'cmplz_get_option' ) && class_exists( 'COMPLIANZ' ) && isset( COMPLIANZ::$document ) ) {
        $mode = cmplz_get_option( 'cookie-statement' );
        $id = 'custom' === $mode ? (int) get_option( 'cmplz_cookie-statement_custom_page' ) : ( 'generated' === $mode ? (int) COMPLIANZ::$document->get_shortcode_page_id( 'cookie-statement', 'eu' ) : 0 );
        if ( $id && function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) && pll_current_language() ) { $id = pll_get_post( $id, pll_current_language( 'slug' ) ) ?: $id; }
        if ( $id && 'publish' === get_post_status( $id ) && '' === (string) get_post_field( 'post_password', $id ) ) {
            $url = get_permalink( $id ); $path = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
            if ( ! in_array( $path, $existing, true ) ) {
                $german = vpm_theme_english() && function_exists( 'pll_get_post_language' ) && 'en' !== pll_get_post_language( $id );
                $links[] = array( 'url' => $url, 'label' => vpm_theme_text( 'Cookie-Richtlinie', $german ? 'Cookie policy (German)' : 'Cookie policy' ) );
            }
        }
    }
    return $links;
}
