<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_prepare_rank_math_metadata() {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    if ( ! class_exists( 'RankMath' ) ) { return new WP_Error( 'missing_seo', 'Bitte zuerst Rank Math aktivieren.' ); }
    $data = json_decode( file_get_contents( __DIR__ . '/seo-metadata.json' ), true );
    $changed = 0;
    foreach ( $data as $item ) {
        $slug = '/' === $item['url'] ? 'startseite' : trim( $item['url'], '/' );
        $pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_vpm_import_slug', 'meta_value' => $slug, 'numberposts' => 1 ) );
        if ( ! $pages ) { continue; }
        $id = $pages[0]->ID;
        if ( ! current_user_can( 'edit_post', $id ) ) { continue; }
        foreach ( array( 'title' => 'rank_math_title', 'description' => 'rank_math_description' ) as $field => $key ) {
            if ( ! empty( $item[ $field ] ) && '' === (string) get_post_meta( $id, $key, true ) ) {
                update_post_meta( $id, $key, sanitize_text_field( $item[ $field ] ) );
                ++$changed;
            }
        }
    }
    return $changed;
}
