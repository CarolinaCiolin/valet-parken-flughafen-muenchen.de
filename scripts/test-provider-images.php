<?php
/** Guarded test: never touches external installations. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
wp_set_current_user( 0 );
if ( ! is_wp_error( vpm_add_provider_symbol_images() ) ) { throw new RuntimeException( 'Missing capability protection.' ); }
wp_set_current_user( 1 );
$before = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => 'any', 'numberposts' => 100 ) );
if ( 0 !== vpm_add_provider_symbol_images() ) { throw new RuntimeException( 'Repeat image assignment was not idempotent.' ); }
foreach ( $before as $provider ) {
    if ( $provider->post_status !== get_post_status( $provider->ID ) ) { throw new RuntimeException( 'Publication status changed.' ); }
}
$test_id = wp_insert_post( array( 'post_type' => 'vpm_provider', 'post_status' => 'draft', 'post_title' => 'LOCAL image preservation test' ) );
try {
    $hero = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_vpm_asset_key', 'meta_value' => 'hero-ai-v1', 'numberposts' => 1 ) );
    set_post_thumbnail( $test_id, $hero[0]->ID );
    if ( 0 !== vpm_add_provider_symbol_images( array( $test_id ) ) || $hero[0]->ID !== get_post_thumbnail_id( $test_id ) ) { throw new RuntimeException( 'Existing image overwritten.' ); }
    delete_post_thumbnail( $test_id );
    if ( 1 !== vpm_add_provider_symbol_images( array( $test_id ) ) || 'draft' !== get_post_status( $test_id ) ) { throw new RuntimeException( 'Missing image not added as expected.' ); }
} finally { wp_delete_post( $test_id, true ); }
$provider_id = $before[0]->ID;
$caption = get_post_meta( $provider_id, '_vpm_image_caption', true );
try {
    update_post_meta( $provider_id, '_vpm_image_caption', '' );
    $html = do_shortcode( '[vpm_providers ids="' . $provider_id . '"]' );
    if ( ! str_contains( $html, '<figcaption>KI-Symbolbild · keine Anbieteraufnahme</figcaption>' ) ) { throw new RuntimeException( 'AI label lost.' ); }
} finally { update_post_meta( $provider_id, '_vpm_image_caption', $caption ); }
echo 'Bildimport: Berechtigung, Wiederholung, Bildschutz, Entwurfsstatus und KI-Kennzeichnung geprüft.' . PHP_EOL;
