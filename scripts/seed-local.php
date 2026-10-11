<?php
/** Local visual and integration fixture. Not shipped in either installable ZIP. */
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) ) { throw new RuntimeException( 'Local-only setup guard.' ); }
require '/var/www/html/wp-load.php';
if ( '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Not a localhost test installation.' ); }
if ( get_option( 'vpm_local_fixture_loaded' ) ) {
    echo 'Lokale Vorschau bereits eingerichtet; vorhandene Inhalte bleiben unverändert.' . PHP_EOL;
    return;
}
$fixture = json_decode( file_get_contents( '/tmp/vpm-fixture.json' ), true, 512, JSON_THROW_ON_ERROR );
require __DIR__ . '/vpm-hero-media-local.php';
$fixture['content'] = str_replace( '__VPM_HERO_IMAGE__', vpm_local_hero_block( vpm_local_hero_attachment() ), $fixture['content'] );
foreach ( $fixture['providers'] as $order => $data ) {
    $existing = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => 'any', 'meta_key' => '_vpm_fixture_id', 'meta_value' => $data['id'], 'numberposts' => 1 ) );
    $id = wp_insert_post( array( 'ID' => $existing ? $existing[0]->ID : 0, 'post_type' => 'vpm_provider', 'post_title' => $data['name'], 'post_status' => 'publish', 'menu_order' => $order ), true );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
    update_post_meta( $id, '_vpm_fixture_id', $data['id'] );
    foreach ( $data['meta'] as $key => $value ) { update_post_meta( $id, '_vpm_' . $key, $value ); }
}
$existing = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'name' => 'vpm-local-preview', 'numberposts' => 1 ) );
$page_id = wp_insert_post( array( 'ID' => $existing ? $existing[0]->ID : 0, 'post_type' => 'page', 'post_name' => 'vpm-local-preview', 'post_title' => 'Valet-Parken Flughafen München', 'post_status' => 'publish', 'post_content' => $fixture['content'] ), true );
if ( is_wp_error( $page_id ) ) { throw new RuntimeException( $page_id->get_error_message() ); }
update_post_meta( $page_id, '_wp_page_template', 'templates/full-width.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );
$menu_id = wp_get_nav_menu_object( 'VPM lokale Testnavigation' );
$menu_id = $menu_id ? $menu_id->term_id : wp_create_nav_menu( 'VPM lokale Testnavigation' );
if ( ! wp_get_nav_menu_items( $menu_id ) ) {
    wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Anbieter', 'menu-item-url' => home_url( '/#anbieter' ), 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
}
set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_id ) );
set_theme_mod( 'vpm_cta_label', 'Verfügbarkeit prüfen' );
set_theme_mod( 'vpm_cta_url', $fixture['general_url'] );
flush_rewrite_rules();
update_option( 'vpm_local_fixture_loaded', true );
echo 'Lokale Vorschau und sechs reale Anbieter geladen. Keine Live-Seite wurde angelegt.' . PHP_EOL;
