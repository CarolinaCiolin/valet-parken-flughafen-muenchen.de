<?php
/** Only configures our isolated local review installation. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
if ( get_option( 'vpm_local_handover_loaded' ) ) { echo 'Lokale Abnahme bereits eingerichtet; Einstellungen bleiben erhalten.' . PHP_EOL; return; }
$data = json_decode( file_get_contents( '/var/www/html/wp-content/plugins/vpm-content-drafts/content.json' ), true );
foreach ( $data['pages'] as $page ) {
    $existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
    if ( ! $existing || 'publish' !== $existing->post_status ) { throw new RuntimeException( 'Prepare the local review pages first.' ); }
    $pages[ $page['slug'] ] = $existing->ID;
}
$menu_locations = get_theme_mod( 'nav_menu_locations', array() );
foreach ( array( 'primary' => array( 'Anbieter' => '/#anbieter', 'Preise' => '/preise/', 'Sicherheit' => '/valet-parken-sicherheit/', 'Über uns' => '/ueber-uns/' ), 'footer' => array( 'Preise' => '/preise/', 'Sicherheit' => '/valet-parken-sicherheit/', 'Über uns' => '/ueber-uns/' ) ) as $location => $links ) {
    $name = 'VPM lokale Abnahme ' . $location;
    $menu = wp_get_nav_menu_object( $name );
    $id = $menu ? $menu->term_id : wp_create_nav_menu( $name );
    foreach ( wp_get_nav_menu_items( $id ) ?: array() as $item ) { wp_delete_post( $item->ID, true ); }
    foreach ( $links as $label => $path ) {
        $slug = trim( $path, '/' );
        $args = array( 'menu-item-title' => $label, 'menu-item-status' => 'publish' );
        if ( isset( $pages[ $slug ] ) ) { $args += array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $pages[ $slug ] ); }
        else { $args += array( 'menu-item-type' => 'custom', 'menu-item-url' => home_url( $path ) ); }
        wp_update_nav_menu_item( $id, 0, $args );
    }
    $menu_locations[ $location ] = $id;
}
set_theme_mod( 'nav_menu_locations', $menu_locations );
set_theme_mod( 'vpm_footer_note', 'Einige Verlinkungen auf dieser Website sind Affiliate-Links. Wenn Sie darüber eine Buchung abschließen, können wir vom jeweiligen Buchungspartner eine Provision erhalten. Der Preis erhöht sich für Sie dadurch nicht.' );
set_theme_mod( 'vpm_cta_label', 'Verfügbarkeit prüfen' );
set_theme_mod( 'vpm_cta_url', $data['general_url'] );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pages['startseite'] );
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();
update_option( 'vpm_local_handover_loaded', true );
echo 'Lokale Abnahme: Startseite, Navigation und Footer eingerichtet.' . PHP_EOL;
