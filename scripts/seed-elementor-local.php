<?php
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) ) { throw new RuntimeException( 'Local-only setup guard.' ); }
require '/var/www/html/wp-load.php';
if ( '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Not a localhost test installation.' ); }
if ( get_option( 'vpm_local_elementor_fixture_loaded' ) ) {
    echo 'Elementor-Testseite bereits eingerichtet; Inhalt bleibt unverändert.' . PHP_EOL;
    return;
}
$existing = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'name' => 'elementor-local-test', 'numberposts' => 1 ) );
$id = wp_insert_post( array( 'ID' => $existing ? $existing[0]->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Elementor-Kompatibilitätstest', 'post_name' => 'elementor-local-test' ), true );
if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
update_post_meta( $id, '_wp_page_template', 'templates/full-width.php' );
update_post_meta( $id, '_elementor_edit_mode', 'builder' );
update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
$data = array( array( 'id' => 'vpmsec01', 'elType' => 'section', 'settings' => array(), 'elements' => array( array( 'id' => 'vpmcol01', 'elType' => 'column', 'settings' => array( '_column_size' => 100 ), 'elements' => array(
    array( 'id' => 'vpmhead1', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'Valet-Parken Flughafen München', 'header_size' => 'h1' ), 'elements' => array() ),
    array( 'id' => 'vpmhead2', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'Verfügbare Valet-Angebote', 'header_size' => 'h2' ), 'elements' => array() ),
    array( 'id' => 'vpmlinks', 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => array( 'shortcode' => '[vpm_providers]' ), 'elements' => array() ),
) ) ) ) );
update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
\Elementor\Plugin::$instance->files_manager->clear_cache();
flush_rewrite_rules();
update_option( 'vpm_local_elementor_fixture_loaded', true );
echo 'Elementor-Testseite ID ' . $id . PHP_EOL;
