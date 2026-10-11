<?php
/** Shared image helper, only loaded by guarded local fixture scripts. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'VPM_LOCAL_SETUP' ) ) { exit; }
function vpm_local_hero_attachment() {
    $found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_vpm_asset_key', 'meta_value' => 'hero-ai-v1', 'numberposts' => 1 ) );
    if ( $found ) { return $found[0]->ID; }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $source = get_theme_file_path( 'assets/images/valet-hero.webp' );
    if ( ! is_readable( $source ) ) { throw new RuntimeException( 'Hero asset missing.' ); }
    $upload = wp_upload_bits( 'vpm-valet-hero-ai.webp', null, file_get_contents( $source ) );
    if ( $upload['error'] ) { throw new RuntimeException( $upload['error'] ); }
    $id = wp_insert_attachment( array( 'post_title' => 'Valet-Parking – KI-generierte Illustration', 'post_excerpt' => 'KI-generierte Illustration', 'post_mime_type' => 'image/webp', 'post_status' => 'inherit' ), $upload['file'], 0, true );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
    update_post_meta( $id, '_vpm_asset_key', 'hero-ai-v1' );
    update_post_meta( $id, '_wp_attachment_image_alt', 'Reisende mit Koffer im Gespräch mit einem Valet-Mitarbeiter vor einer Flughafenarchitektur' );
    wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
    return $id;
}
function vpm_local_hero_block( $id ) {
    $image = wp_get_attachment_image_src( $id, 'full' );
    return '<!-- wp:image ' . wp_json_encode( array( 'id' => $id, 'sizeSlug' => 'full', 'className' => 'vpm-hero-image' ) ) . ' --><figure class="wp-block-image size-full vpm-hero-image"><img src="' . esc_url( $image[0] ) . '" alt="' . esc_attr( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) . '" class="wp-image-' . $id . '"/><figcaption class="wp-element-caption">KI-generierte Illustration</figcaption></figure><!-- /wp:image -->';
}
