<?php
/** Explicit image import. Custom featured images are never replaced. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_provider_symbol_scene( $id ) {
    $scenes = array( 'arrival', 'garage', 'handover', 'family', 'inspection', 'return' );
    return $scenes[ absint( get_post_field( 'menu_order', $id ) ) % count( $scenes ) ];
}
function vpm_provider_symbol_attachment( $scene = 'arrival' ) {
    if ( ! in_array( $scene, array( 'arrival', 'garage', 'handover', 'family', 'inspection', 'return' ), true ) ) { return new WP_Error( 'image_scene', 'Unbekanntes Symbolbild.' ); }
    $key = 'provider-symbol-ai-v2-' . $scene;
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_vpm_asset_key', 'meta_value' => $key, 'numberposts' => 1 ) );
    if ( $existing ) { return $existing[0]->ID; }
    $source = __DIR__ . '/images/' . $scene . '.webp';
    if ( ! is_readable( $source ) ) { return new WP_Error( 'image_missing', 'Das Symbolbild fehlt im Paket.' ); }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $temporary = wp_tempnam( 'vpm-provider-' . $scene . '.webp' );
    if ( ! $temporary || ! copy( $source, $temporary ) ) { return new WP_Error( 'image_copy', 'Symbolbild konnte nicht vorbereitet werden.' ); }
    $id = media_handle_sideload( array( 'name' => 'vpm-provider-' . $scene . '.webp', 'tmp_name' => $temporary ), 0, 'Valet-Parking – KI-Symbolbild' );
    if ( is_wp_error( $id ) ) { @unlink( $temporary ); return $id; }
    update_post_meta( $id, '_vpm_asset_key', $key );
    $alt = array(
        'arrival' => 'KI-Symbolbild eines dunklen Fahrzeugs bei der Ankunft am Flughafenterminal',
        'garage' => 'KI-Symbolbild eines silbernen Fahrzeugs in einer überdachten Parkhalle',
        'handover' => 'KI-Symbolbild einer Valet-Mitarbeiterin bei der Schlüsselübergabe',
        'family' => 'KI-Symbolbild einer Familie mit Gepäck neben einem Fahrzeug am Flughafen',
        'inspection' => 'KI-Symbolbild eines Valet-Mitarbeiters bei der Fahrzeugkontrolle mit einem Tablet',
        'return' => 'KI-Symbolbild einer Reisenden bei der Rückgabe ihres Fahrzeugschlüssels',
    );
    update_post_meta( $id, '_wp_attachment_image_alt', $alt[ $scene ] );
    wp_update_post( array( 'ID' => $id, 'post_excerpt' => 'KI-Symbolbild · keine Anbieteraufnahme' ) );
    return $id;
}
function vpm_add_provider_symbol_images( $ids = null, $refresh_managed = false ) {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    if ( ! post_type_exists( 'vpm_provider' ) ) { return new WP_Error( 'missing', 'Bitte zuerst VPM Anbieter aktivieren.' ); }
    if ( null === $ids ) { $ids = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1, 'fields' => 'ids' ) ); }
    $count = 0;
    foreach ( wp_parse_id_list( $ids ) as $id ) {
        if ( 'vpm_provider' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) { continue; }
        $key = get_post_meta( get_post_thumbnail_id( $id ), '_vpm_asset_key', true );
        $scene = vpm_provider_symbol_scene( $id );
        if ( has_post_thumbnail( $id ) && ( ! $refresh_managed || ! str_starts_with( (string) $key, 'provider-symbol-ai-' ) || $key === 'provider-symbol-ai-v2-' . $scene ) ) { continue; }
        $image_id = vpm_provider_symbol_attachment( $scene );
        if ( is_wp_error( $image_id ) ) { return $image_id; }
        if ( set_post_thumbnail( $id, $image_id ) ) {
            update_post_meta( $id, '_vpm_image_caption', 'KI-Symbolbild · keine Anbieteraufnahme' );
            ++$count;
        }
    }
    return $count;
}
