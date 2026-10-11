<?php
/** Temporary blog fixtures, confined to localhost; cleanup leaves no published test posts. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
wp_set_current_user( 1 );
if ( 'cleanup' === ( $argv[1] ?? '' ) ) {
    $ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_vpm_local_guide_fixture', 'meta_value' => '1' ) );
    foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
    echo 'Temporary guide posts removed: ' . count( $ids ) . PHP_EOL; return;
}
$guide = get_page_by_path( 'ratgeber' );
if ( ! $guide || 'draft' !== $guide->post_status ) { throw new RuntimeException( 'Guide must remain a draft.' ); }
$before = $guide->post_content; $options = array( get_option( 'page_on_front' ), get_option( 'page_for_posts' ), get_nav_menu_locations() );
$result = vpm_import_content_drafts();
if ( is_wp_error( $result ) || $result['pages'] || get_post( $guide->ID )->post_content !== $before || $options !== array( get_option( 'page_on_front' ), get_option( 'page_for_posts' ), get_nav_menu_locations() ) ) { throw new RuntimeException( 'Repeat import must preserve pages and site configuration.' ); }
$images = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 2, 'fields' => 'ids', 'meta_key' => '_vpm_asset_key', 'meta_value' => 'provider-symbol-ai-v2-arrival' ) );
for ( $i = 1; $i <= 12; $i++ ) {
    $id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 12 === $i ? 'draft' : 'publish', 'post_title' => 'Lokaler Testbeitrag ' . sprintf( '%02d', $i ), 'post_content' => '<!-- wp:paragraph --><p>Technischer Test der Beitragsübersicht. Dies ist kein veröffentlichter Ratgeberartikel.</p><!-- /wp:paragraph -->', 'post_date' => '2025-01-' . sprintf( '%02d', $i ) . ' 12:00:00', 'meta_input' => array( '_vpm_local_guide_fixture' => '1' ) ), true );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
    if ( $i % 2 && $images ) { set_post_thumbnail( $id, $images[0] ); }
}
echo 'Guide draft ID ' . $guide->ID . '; 11 public local fixtures and one private draft fixture.' . PHP_EOL;
