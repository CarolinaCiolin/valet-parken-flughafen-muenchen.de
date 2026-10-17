<?php
/** Explicit admin publication. Never executed on plugin activation or import. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_publish_checked_posts( $ids ) {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    foreach ( $ids as $id ) {
        $post = get_post( $id );
        if ( ! $post || ! current_user_can( 'edit_post', $id ) || ! in_array( $post->post_status, array( 'draft', 'pending', 'private', 'future', 'publish' ), true ) || $post->post_password ) { return new WP_Error( 'publication', 'Eine Zielseite fehlt, ist geschützt oder kann nicht veröffentlicht werden. Keine Veröffentlichung begonnen.' ); }
    }
    $count = 0;
    foreach ( $ids as $id ) {
        if ( 'publish' === get_post_status( $id ) ) { continue; }
        $result = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
        if ( is_wp_error( $result ) ) { return $result; }
        ++$count;
    }
    flush_rewrite_rules( false ); do_action( 'litespeed_purge_all' );
    return $count;
}
function vpm_publish_english() {
    $ready = vpm_english_requirements(); if ( is_wp_error( $ready ) ) { return $ready; }
    $ids = array();
    foreach ( array( 'startseite', 'preise', 'valet-parken-sicherheit', 'ueber-uns' ) as $slug ) {
        $source = vpm_original_page( $slug ); $id = $source ? pll_get_post( $source, 'en' ) : 0;
        if ( ! $id || 'page' !== get_post_type( $id ) || vpm_english_page_empty( $id ) ) { return new WP_Error( 'missing_translation', 'Englische Seite fehlt oder ist leer: ' . $slug . '. Bitte zuerst importieren beziehungsweise leere Seiten vervollständigen. Keine Veröffentlichung begonnen.' ); }
        $ids[] = $id;
    }
    $data = json_decode( file_get_contents( __DIR__ . '/content-en.json' ), true );
    foreach ( $data['providers'] as $provider ) {
        $originals = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => 'any', 'title' => $provider['source_name'], 'numberposts' => -1, 'lang' => '', 'suppress_filters' => true ) );
        $id = 0;
        foreach ( $originals as $original ) { if ( 'de' === pll_get_post_language( $original->ID ) ) { $id = pll_get_post( $original->ID, 'en' ); break; } }
        if ( ! $id || ! get_post_meta( $id, '_vpm_features', true ) ) { return new WP_Error( 'missing_provider', 'Englischer Anbieter fehlt oder ist unvollständig: ' . $provider['name'] . '. Bitte zuerst importieren. Keine Veröffentlichung begonnen.' ); }
        $ids[] = $id;
    }
    $nav = PLL()->options['nav_menus']; $theme = get_stylesheet();
    if ( empty( $nav[ $theme ]['primary']['en'] ) || empty( $nav[ $theme ]['footer']['en'] ) ) { return new WP_Error( 'menus', 'Bitte zuerst die englischen Entwürfe und Menüs importieren. Keine Veröffentlichung begonnen.' ); }
    $result = vpm_publish_checked_posts( $ids );
    if ( is_wp_error( $result ) ) { return $result; }
    return $result . ' englische Seiten/Anbieter veröffentlicht. Englisches Menü und Footer sind zugeordnet. Beide Ratgeber und die globale Suchmaschinen-Sichtbarkeit bleiben unverändert.';
}
function vpm_publish_legal() {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    $ids = array();
    foreach ( array( 'impressum', 'datenschutz' ) as $slug ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page || ! get_post_meta( $page->ID, '_vpm_legal_draft', true ) || ! trim( wp_strip_all_tags( $page->post_content ) ) ) { return new WP_Error( 'legal', 'Bitte zuerst die private Rechtstext-Datei für Impressum und Datenschutz importieren. Keine Veröffentlichung begonnen.' ); }
        $ids[] = $page->ID;
    }
    $result = vpm_publish_checked_posts( $ids );
    if ( is_wp_error( $result ) ) { return $result; }
    return $result . ' Rechtstexte veröffentlicht. VPM Hybrid zeigt veröffentlichte Rechtstexte automatisch im deutschen Footer. Englische Fassungen sind davon getrennt.';
}
function vpm_publication_admin() {
    foreach ( array( 'vpm_publish_english' => 'vpm_publish_english', 'vpm_publish_legal' => 'vpm_publish_legal' ) as $action => $callback ) {
        if ( isset( $_POST[ $action ] ) ) { check_admin_referer( $action ); $r = $callback(); echo '<div class="notice ' . ( is_wp_error( $r ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( is_wp_error( $r ) ? $r->get_error_message() : $r ) . '</p></div>'; }
    }
    echo '<h2>Vorbereitete Inhalte veröffentlichen</h2><p>Diese Aktionen veröffentlichen Inhalte auf dieser Website. Englisch: vier Inhaltsseiten und sechs Anbieter, einschließlich vorhandener Buchungslinks. Rechtstexte: die beiden separat importierten deutschen Seiten. Die Ratgeber bleiben unverändert; Tracking wird nicht aktiviert.</p>';
    foreach ( array( 'vpm_publish_english' => 'Englische Seiten und Anbieter veröffentlichen', 'vpm_publish_legal' => 'Importiertes Impressum und Datenschutz veröffentlichen' ) as $action => $label ) { echo '<form method="post">'; wp_nonce_field( $action ); submit_button( $label, 'secondary', $action ); echo '</form>'; }
    if ( ! get_option( 'blog_public' ) ) { echo '<p><strong>Suchmaschinen-Sichtbarkeit ist derzeit deaktiviert.</strong> Für die gewünschte Indexierung unter Einstellungen → Lesen ändern. Diese Einstellung gilt für alle Sprachen und wird hier nicht automatisch geändert.</p>'; }
}
