<?php
/** Creates review pages only in the guarded local environment. Never distributed. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$result = activate_plugin( 'vpm-content-drafts/vpm-content-drafts.php' );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
wp_set_current_user( 1 );
$result = vpm_import_content_drafts();
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
foreach ( $result['pages'] as $id ) {
    if ( 'ratgeber' !== get_post_field( 'post_name', $id ) ) { wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) ); }
}
$images = vpm_add_provider_symbol_images();
if ( is_wp_error( $images ) ) { throw new RuntimeException( $images->get_error_message() ); }
echo 'Fehlende lokale Symbolbilder ergänzt: ' . $images . PHP_EOL;
echo 'Neue lokale Testseiten: ' . count( $result['pages'] ) . '; vorhandene Inhalte bewahrt.' . PHP_EOL;
