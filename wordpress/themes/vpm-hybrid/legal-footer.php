<?php
/** Show legal pages only after publication; preserve the editor's existing menu. */
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
    return $links;
}
