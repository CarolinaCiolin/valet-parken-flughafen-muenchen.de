<?php
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) ) { throw new RuntimeException( 'Local-only test guard.' ); }
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$checks = 0;
function vpm_expect( $condition, $message ) {
    global $checks;
    if ( ! $condition ) { throw new RuntimeException( 'FAIL: ' . $message ); }
    $checks++;
    echo 'PASS: ' . $message . PHP_EOL;
}
vpm_expect( get_stylesheet() === 'vpm-hybrid', 'Hybrid-Theme aktiv' );
vpm_expect( ! wp_is_block_theme(), 'Klassische Templates für Elementor verfügbar' );
vpm_expect( isset( wp_get_theme()->get_page_templates()['templates/full-width.php'] ), 'Volle-Breite-Vorlage registriert' );
vpm_expect( get_option( 'blog_public' ) === '0', 'Lokale Testinstallation vor Indexierung geschützt' );
vpm_expect( is_plugin_active( 'elementor/elementor.php' ), 'Echtes Elementor aktiviert: ' . ELEMENTOR_VERSION );
vpm_expect( count( WP_Block_Patterns_Registry::get_instance()->get_all_registered() ) > 0, 'Block-Patterns registriert' );
$providers = get_posts( array( 'post_type' => 'vpm_provider', 'numberposts' => 100, 'post_status' => 'publish' ) );
vpm_expect( count( $providers ) === 6, 'Sechs reale Anbieter aus Testfixture' );
$html = do_shortcode( '[vpm_providers]' );
vpm_expect( substr_count( $html, '<article class="vpm-provider-card">' ) === 6, 'Sechs aktive Karten ausgegeben' );
vpm_expect( substr_count( $html, 'rel="sponsored nofollow"' ) === 7, 'Sieben Buchungslinks korrekt gekennzeichnet' );
$fixture = json_decode( file_get_contents( '/tmp/vpm-fixture.json' ), true );
foreach ( $fixture['providers'] as $provider ) {
    foreach ( $provider['meta'] as $key => $value ) {
        if ( str_ends_with( $key, '_url' ) ) { vpm_expect( str_contains( $html, esc_url( $value ) ), 'Unveränderter Link: ' . $provider['name'] . '/' . $key ); }
    }
}
$id = $providers[0]->ID;
update_post_meta( $id, '_vpm_active', false );
vpm_expect( substr_count( do_shortcode( '[vpm_providers]' ), '<article class="vpm-provider-card">' ) === 5, 'Inaktiver Anbieter ausgeblendet' );
update_post_meta( $id, '_vpm_active', true );
wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
vpm_expect( substr_count( do_shortcode( '[vpm_providers]' ), '<article class="vpm-provider-card">' ) === 5, 'Entwürfe ausgeblendet' );
wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
vpm_expect( substr_count( do_shortcode( '[vpm_providers ids="' . $id . '"]' ), '<article class="vpm-provider-card">' ) === 1, 'Gezielte Auswahl per Shortcode-ID' );
vpm_expect( vpm_sanitize_provider_value( 'javascript:alert(1)', 'parkos_url' ) === '', 'Unsichere URL-Protokolle zurückgewiesen' );
wp_set_current_user( 0 );
$request = new WP_REST_Request( 'GET', '/wp/v2/vpm_provider' );
$response = rest_get_server()->dispatch( $request );
vpm_expect( $response->get_status() === 401, 'Anbieter-REST-Daten für nicht angemeldete Besucher gesperrt' );
$_POST = array( 'vpm' => array( 'features' => 'UNAUTHORIZED' ) );
$before = get_post_meta( $id, '_vpm_features', true );
do_action( 'save_post_vpm_provider', $id );
vpm_expect( get_post_meta( $id, '_vpm_features', true ) === $before, 'Speichern ohne Nonce und Berechtigung verhindert' );
$old_theme = get_stylesheet();
switch_theme( 'twentytwentyfive' );
vpm_expect( get_post_meta( $id, '_vpm_features', true ) === $before, 'Anbieterinformationen bleiben bei Themewechsel erhalten' );
switch_theme( $old_theme );
vpm_expect( count( get_posts( array( 'post_type' => 'vpm_provider', 'numberposts' => 100 ) ) ) === 6, 'Themewechsel erhält alle Anbieter' );
echo $checks . ' Integrationsprüfungen bestanden.' . PHP_EOL;
