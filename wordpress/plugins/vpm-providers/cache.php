<?php
/** Provider cards can appear on any page: invalidate cached output when data changes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_queue_provider_cache_purge( $id ) {
    if ( 'vpm_provider' !== get_post_type( $id ) || wp_is_post_revision( $id ) ) { return; }
    $GLOBALS['vpm_provider_cache_dirty'] = true;
}
add_action( 'save_post_vpm_provider', function( $id ) {
    if ( 'publish' === get_post_status( $id ) ) { vpm_queue_provider_cache_purge( $id ); }
} );
add_action( 'transition_post_status', function( $new, $old, $post ) {
    if ( 'vpm_provider' === $post->post_type && ( 'publish' === $old || 'publish' === $new ) ) { vpm_queue_provider_cache_purge( $post->ID ); }
}, 10, 3 );
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
    add_action( $hook, function( $meta_id, $id, $key ) {
        if ( 'publish' === get_post_status( $id ) && ( str_starts_with( $key, '_vpm_' ) || '_thumbnail_id' === $key ) ) { vpm_queue_provider_cache_purge( $id ); }
    }, 10, 3 );
}
add_action( 'before_delete_post', function( $id ) {
    if ( 'publish' === get_post_status( $id ) ) { vpm_queue_provider_cache_purge( $id ); }
} );
function vpm_flush_provider_cache() {
    if ( empty( $GLOBALS['vpm_provider_cache_dirty'] ) ) { return; }
    $GLOBALS['vpm_provider_cache_dirty'] = false;
    // Coalesce multiple field changes into one purge at the end of the request.
    do_action( 'litespeed_purge_all' );
    if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); }
}
add_action( 'shutdown', 'vpm_flush_provider_cache', 5 );
