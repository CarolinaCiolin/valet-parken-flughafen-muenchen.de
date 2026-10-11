<?php
/** Explicit opt-in provider imagery; never replaces existing featured images. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_provider_symbol_attachment() {
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_vpm_asset_key', 'meta_value' => 'provider-symbol-ai-v1', 'numberposts' => 1 ) );
    if ( $existing ) { return $existing[0]->ID; }
    $source = __DIR__ . '/provider-symbol.webp';
    if ( ! is_readable( $source ) ) { return new WP_Error( 'image_missing', 'Das Symbolbild fehlt im Paket.' ); }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $temporary = wp_tempnam( 'vpm-provider-symbol.webp' );
    if ( ! $temporary || ! copy( $source, $temporary ) ) { return new WP_Error( 'image_copy', 'Symbolbild konnte nicht vorbereitet werden.' ); }
    $id = media_handle_sideload( array( 'name' => 'vpm-provider-symbol.webp', 'tmp_name' => $temporary ), 0, 'Valet-Schlüsselübergabe – KI-Symbolbild' );
    if ( is_wp_error( $id ) ) { @unlink( $temporary ); return $id; }
    update_post_meta( $id, '_vpm_asset_key', 'provider-symbol-ai-v1' );
    update_post_meta( $id, '_wp_attachment_image_alt', 'KI-Symbolbild einer Reisenden bei der Schlüsselübergabe an einen Valet-Mitarbeiter' );
    wp_update_post( array( 'ID' => $id, 'post_excerpt' => 'KI-Symbolbild · keine Anbieteraufnahme' ) );
    return $id;
}
function vpm_add_provider_symbol_images( $ids = null ) {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    if ( ! post_type_exists( 'vpm_provider' ) ) { return new WP_Error( 'missing', 'Bitte zuerst VPM Anbieter aktivieren.' ); }
    if ( null === $ids ) { $ids = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1, 'fields' => 'ids' ) ); }
    $missing = array();
    foreach ( wp_parse_id_list( $ids ) as $id ) {
        if ( 'vpm_provider' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) && ! has_post_thumbnail( $id ) ) { $missing[] = $id; }
    }
    if ( ! $missing ) { return 0; }
    $image_id = vpm_provider_symbol_attachment();
    if ( is_wp_error( $image_id ) ) { return $image_id; }
    $count = 0;
    foreach ( $missing as $id ) {
        if ( set_post_thumbnail( $id, $image_id ) ) {
            update_post_meta( $id, '_vpm_image_caption', 'KI-Symbolbild · keine Anbieteraufnahme' );
            ++$count;
        }
    }
    return $count;
}
