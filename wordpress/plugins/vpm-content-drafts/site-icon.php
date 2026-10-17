<?php
/** Native WordPress site icon. Explicit action, preserves a working custom icon. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_prepare_site_icon() {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    if ( has_site_icon() ) { return 'Ein Website-Icon ist bereits eingerichtet und bleibt unverändert.'; }
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => '_vpm_asset_key', 'meta_value' => 'site-icon-v1', 'lang' => '', 'suppress_filters' => true ) );
    if ( $existing && is_file( get_attached_file( $existing[0]->ID ) ) ) { $id = $existing[0]->ID; }
    else {
        require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
        $temp = wp_tempnam( 'vpm-site-icon.png' );
        if ( ! $temp || ! copy( __DIR__ . '/site-icon.png', $temp ) ) { return new WP_Error( 'icon', 'Website-Icon konnte nicht vorbereitet werden.' ); }
        $sizes = function( $sizes ) { foreach ( array( 32, 180, 192, 270 ) as $size ) { $sizes[ 'vpm-site-icon-' . $size ] = array( 'width' => $size, 'height' => $size, 'crop' => true ); } return $sizes; };
        add_filter( 'intermediate_image_sizes_advanced', $sizes );
        try { $id = media_handle_sideload( array( 'name' => 'vpm-site-icon.png', 'tmp_name' => $temp ), 0, 'VP – Website-Icon' ); }
        finally { remove_filter( 'intermediate_image_sizes_advanced', $sizes ); }
        if ( is_wp_error( $id ) ) { @unlink( $temp ); return $id; }
        update_post_meta( $id, '_vpm_asset_key', 'site-icon-v1' ); update_post_meta( $id, '_wp_attachment_context', 'site-icon' ); update_post_meta( $id, '_wp_attachment_image_alt', 'VP – Valet Parken München' );
    }
    update_option( 'site_icon', $id ); do_action( 'litespeed_purge_all' );
    return 'VP-Website-Icon in WordPress eingerichtet. Es gilt für beide Sprachen.';
}
function vpm_site_icon_admin() {
    if ( isset( $_POST['vpm_site_icon'] ) ) { check_admin_referer( 'vpm_site_icon' ); $r = vpm_prepare_site_icon(); echo '<div class="notice ' . ( is_wp_error( $r ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( is_wp_error( $r ) ? $r->get_error_message() : $r ) . '</p></div>'; }
    echo '<h2>Website-Icon</h2><p>Richtet das vorbereitete VP-Monogramm als natives WordPress-Website-Icon ein. Ein bereits eingerichtetes Icon wird nicht überschrieben. Ohne eigenes Icon liefert das Theme bereits eine kleine Favicon-Datei aus.</p><form method="post">'; wp_nonce_field( 'vpm_site_icon' ); submit_button( 'VP-Website-Icon einrichten', 'secondary', 'vpm_site_icon' ); echo '</form>';
}
