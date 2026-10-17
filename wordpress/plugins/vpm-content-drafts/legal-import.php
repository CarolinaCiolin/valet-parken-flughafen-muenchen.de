<?php
/** Operator uploads the private legal JSON; it is never shipped in the public repo. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_import_legal_drafts( $data ) {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    if ( ! is_array( $data ) || ! is_array( $data['pages'] ?? null ) || empty( $data['pages'] ) || count( $data['pages'] ) > 2 ) { return new WP_Error( 'format', 'Ungültige Rechtstext-Datei.' ); }
    $count = 0;
    foreach ( $data['pages'] as $page ) {
        if ( ! isset( $page['slug'], $page['title'], $page['content'] ) || ! in_array( $page['slug'], array( 'impressum', 'datenschutz' ), true ) || ! is_string( $page['content'] ) || ! is_string( $page['title'] ) ) { return new WP_Error( 'format', 'Nur Impressum und Datenschutz sind zulässig.' ); }
    }
    foreach ( $data['pages'] as $page ) {
        if ( get_page_by_path( $page['slug'], OBJECT, 'page' ) ) { continue; }
        $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_name' => $page['slug'], 'post_title' => sanitize_text_field( $page['title'] ), 'post_content' => wp_kses_post( $page['content'] ) ), true );
        if ( is_wp_error( $id ) ) { return $id; }
        update_post_meta( $id, '_vpm_legal_draft', '1' );
        if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $id, 'de' ); }
        ++$count;
    }
    return $count;
}
function vpm_legal_admin() {
    if ( isset( $_POST['vpm_legal'] ) ) {
        check_admin_referer( 'vpm_legal' );
        $file = $_FILES['vpm_legal_file'] ?? array();
        if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) || ! is_uploaded_file( $file['tmp_name'] ) || filesize( $file['tmp_name'] ) > 250000 ) { $result = new WP_Error( 'upload', 'Bitte eine private Rechtstext-JSON bis 250 KB auswählen.' ); }
        else { $result = vpm_import_legal_drafts( json_decode( file_get_contents( $file['tmp_name'] ), true ) ); }
        echo '<div class="notice ' . ( is_wp_error( $result ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( is_wp_error( $result ) ? $result->get_error_message() : $result . ' Rechtstext-Entwürfe angelegt; bestehende Seiten unverändert.' ) . '</p></div>';
    }
    echo '<h2>Private Rechtstext-Entwürfe</h2><p>Die separat bereitgestellte JSON-Datei auswählen. Betreiberangaben sind nicht im öffentlichen Plugin enthalten. Legt nur fehlende Seiten als Entwürfe an; keine Veröffentlichung oder Menüänderung.</p><form method="post" enctype="multipart/form-data">';
    wp_nonce_field( 'vpm_legal' ); echo '<input type="file" name="vpm_legal_file" accept=".json,application/json" required>'; submit_button( 'Rechtstext-Entwürfe importieren', 'secondary', 'vpm_legal' ); echo '</form>';
}
