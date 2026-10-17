<?php
/** Page hierarchy for structured data only; no visible breadcrumb output. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_layout_key( $id = 0 ) {
    $id = $id ?: get_queried_object_id();
    return get_post_meta( $id, '_vpm_layout_slug', true ) ?: ( get_post_meta( $id, '_vpm_import_slug', true ) ?: get_post_field( 'post_name', $id ) );
}
function vpm_is_legal_page( $id = 0 ) {
    $id = $id ?: get_queried_object_id();
    $original = function_exists( 'pll_get_post' ) ? ( pll_get_post( $id, 'de' ) ?: $id ) : $id;
    $slugs = array( 'impressum', 'datenschutz', 'datenschutzerklaerung', 'privacy-policy', 'privacy-notice', 'legal-notice', 'imprint' );
    return (bool) get_post_meta( $original, '_vpm_legal_draft', true ) || in_array( get_post_field( 'post_name', $original ), $slugs, true ) || $id === (int) get_option( 'wp_page_for_privacy_policy' );
}
function vpm_guide_page() {
    $page = get_page_by_path( 'ratgeber', OBJECT, 'page' );
    if ( ! $page ) { return 0; }
    $id = function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) ? pll_get_post( $page->ID, pll_current_language( 'slug' ) ) : $page->ID;
    return $id && 'publish' === get_post_status( $id ) ? $id : 0;
}
function vpm_page_context() {
    if ( ! is_singular( array( 'page', 'post' ) ) || vpm_is_legal_page() ) { return array(); }
    $key = vpm_layout_key();
    if ( is_front_page() || 'startseite' === $key ) { return array(); }
    $home = vpm_theme_home();
    $crumbs = array( array( 'name' => vpm_theme_text( 'Startseite', 'Home' ), 'url' => $home ) );
    $roles = array(
        'preise' => array( 'Preise und Zusatzkosten', 'Prices and additional charges' ),
        'valet-parken-sicherheit' => array( 'Sicherheit und Fahrzeugübergabe', 'Safety and vehicle handover' ),
        'ueber-uns' => array( 'Über das Portal und seine Finanzierung', 'About the website and its funding' ),
        'ratgeber' => array( 'Ratgeber und Reiseinformationen', 'Guides and travel information' ),
    );
    $guide = is_singular( 'post' ) ? vpm_guide_page() : 0;
    if ( $guide ) { $crumbs[] = array( 'name' => get_the_title( $guide ), 'url' => get_permalink( $guide ) ); }
    $crumbs[] = array( 'name' => get_the_title( get_queried_object_id() ), 'url' => get_permalink( get_queried_object_id() ) );
    $role = $roles[ $key ] ?? ( is_singular( 'post' ) ? array( 'Ratgeberartikel', 'Guide article' ) : array( 'Informationen zum Valet-Parken', 'Valet parking information' ) );
    return array( 'crumbs' => $crumbs, 'parent' => $guide ? get_permalink( $guide ) : $home, 'parent_name' => $guide ? get_the_title( $guide ) : vpm_theme_text( 'Valet-Parken am Flughafen München', 'Munich Airport Valet Parking' ), 'role' => vpm_theme_text( $role[0], $role[1] ) );
}
