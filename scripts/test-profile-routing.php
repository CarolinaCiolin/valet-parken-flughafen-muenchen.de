<?php
// Local-only role routing and visible table-duration tests; restore all temporary state.
require '/var/www/html/wp-load.php';
if ( '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { exit( 2 ); }
$original = get_option( 'vpm_site_profile', null ); $ids = array();
try {
    $profile = vpm_profile_preset( 'frankfurt' );
    foreach ( array( 'de', 'en' ) as $lang ) {
        $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Routing fixture', 'post_name' => 'custom-costs-fixture-' . $lang ) );
        $ids[] = $id;
        if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $id, $lang ); }
        $profile['paths'][ $lang ]['prices'] = ( 'en' === $lang ? '/en/' : '/' ) . 'custom-costs-fixture-' . $lang . '/';
    }
    update_option( 'vpm_site_profile', $profile );
    if ( function_exists( 'vpm_cleanlinking_site_id' ) && ! get_option( 'vpm_cleanlinking_site_id' ) && 0 !== vpm_cleanlinking_site_id() ) { throw new Exception( 'Munich CleanLinking ID leaked to Frankfurt' ); }
    foreach ( $ids as $id ) {
        if ( 'preise' !== vpm_layout_key( $id ) ) { throw new Exception( 'Custom price path not classified' ); }
        update_post_meta( $id, '_vpm_layout_slug', 'ueber-uns' );
        if ( 'ueber-uns' !== vpm_layout_key( $id ) ) { throw new Exception( 'Explicit metadata lost precedence' ); }
    }
    foreach ( array( 7, 8, 14 ) as $days ) {
        $html = '<table><tr><th>Offer</th><th>Total for ' . $days . ' days from</th><th>Parking</th><th>Booking</th></tr><tr><td>Fixture provider</td><td>€123</td><td>Outdoor parking</td><td><a href="https://example.org/">Book</a></td></tr></table>';
        $services = vpm_schema_price_services( $html, 'https://example.org/prices/', 'en-GB' );
        if ( count( $services ) !== 1 || $services[0]['offers']['priceSpecification']['referenceQuantity']['value'] !== $days ) { throw new Exception( 'Visible duration lost' ); }
        if ( str_contains( $services[0]['offers']['description'], 'including parking and valet' ) ) { throw new Exception( 'Invented inclusion' ); }
    }
    $bad = $profile; $bad['paths'] = 'not an object';
    if ( ! is_wp_error( vpm_validate_profile( $bad ) ) ) { throw new Exception( 'Bad path shape accepted' ); }
    echo "Custom DE/EN price routes, metadata precedence, seven/eight/fourteen-day visible prices and malformed-path rejection passed.\n";
} finally {
    foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
    if ( null === $original ) { delete_option( 'vpm_site_profile' ); } else { update_option( 'vpm_site_profile', $original ); }
}
