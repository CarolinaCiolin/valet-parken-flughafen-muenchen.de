<?php
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
$GLOBALS['vpm_provider_cache_dirty'] = false;
$hits = 0;
add_action( 'litespeed_purge_all', function() use ( &$hits ) { ++$hits; } );
$published = get_posts( array( 'post_type' => 'vpm_provider', 'numberposts' => 1 ) )[0];
$id = wp_insert_post( array( 'post_type' => 'vpm_provider', 'post_status' => 'draft', 'post_title' => 'LOCAL cache fixture' ) );
try {
    update_post_meta( $id, '_vpm_features', 'Draft test' );
    if ( ! empty( $GLOBALS['vpm_provider_cache_dirty'] ) ) { throw new RuntimeException( 'Draft change purged public cache.' ); }
    do_action( 'updated_post_meta', 0, $published->ID, '_vpm_features' );
    do_action( 'updated_post_meta', 0, $published->ID, '_vpm_parkos_url' );
    vpm_flush_provider_cache(); vpm_flush_provider_cache();
    if ( 1 !== $hits ) { throw new RuntimeException( 'Field updates not coalesced.' ); }
    do_action( 'transition_post_status', 'draft', 'publish', $published );
    vpm_flush_provider_cache();
    if ( 2 !== $hits ) { throw new RuntimeException( 'Unpublishing did not invalidate cards.' ); }
    do_action( 'updated_post_meta', 0, get_option( 'page_on_front' ), '_vpm_features' );
    vpm_flush_provider_cache();
    if ( 2 !== $hits ) { throw new RuntimeException( 'Unrelated page changed provider cache.' ); }
    echo 'Provider cache invalidation: published fields, batched purge, unpublishing, drafts and unrelated pages checked.' . PHP_EOL;
} finally { wp_delete_post( $id, true ); }
