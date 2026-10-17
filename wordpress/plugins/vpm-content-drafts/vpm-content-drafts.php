<?php
/**
 * Plugin Name: VPM Inhaltsentwürfe
 * Description: Einmaliger, manuell ausgelöster Import der vorbereiteten Seiten und Anbieter. Überschreibt keine Inhalte.
 * Version: 0.3.2
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/provider-images.php';
require_once __DIR__ . '/seo-metadata.php';
require_once __DIR__ . '/english.php';
require_once __DIR__ . '/legal-import.php';
require_once __DIR__ . '/publish.php';
function vpm_import_content_drafts() {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    $data = json_decode( file_get_contents( __DIR__ . '/content.json' ), true );
    if ( ! post_type_exists( 'vpm_provider' ) ) { return new WP_Error( 'missing', 'Bitte zuerst VPM Anbieter aktivieren.' ); }
    $result = array( 'pages' => array(), 'providers' => array(), 'skipped' => array() );
    foreach ( $data['providers'] as $order => $provider ) {
        $existing = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => 'any', 'title' => $provider['name'], 'numberposts' => 1 ) );
        if ( $existing ) { $result['skipped'][] = $provider['name']; continue; }
        $id = wp_insert_post( array( 'post_type' => 'vpm_provider', 'post_status' => 'draft', 'post_title' => $provider['name'], 'menu_order' => $order ), true );
        if ( is_wp_error( $id ) ) { return $id; }
        foreach ( $provider['meta'] as $key => $value ) { update_post_meta( $id, '_vpm_' . $key, $value ); }
        $result['providers'][] = $id;
    }
    $imagery = vpm_add_provider_symbol_images( $result['providers'] );
    if ( is_wp_error( $imagery ) ) { return $imagery; }
    $attachment = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_vpm_asset_key', 'meta_value' => 'hero-ai-v1', 'numberposts' => 1 ) );
    if ( $attachment ) { $image_id = $attachment[0]->ID; }
    else {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $temp = wp_tempnam( 'vpm-valet-hero.webp' );
        if ( ! copy( __DIR__ . '/valet-hero.webp', $temp ) ) { return new WP_Error( 'image', 'Bild konnte nicht vorbereitet werden.' ); }
        $image_id = media_handle_sideload( array( 'name' => 'vpm-valet-hero.webp', 'tmp_name' => $temp ), 0, 'KI-generierte Illustration' );
        if ( is_wp_error( $image_id ) ) { @unlink( $temp ); return $image_id; }
        update_post_meta( $image_id, '_vpm_asset_key', 'hero-ai-v1' );
        update_post_meta( $image_id, '_wp_attachment_image_alt', 'Reisende und Valet-Mitarbeiter bei der Fahrzeugübergabe vor einem Flughafenterminal' );
        wp_update_post( array( 'ID' => $image_id, 'post_excerpt' => 'KI-generierte Illustration' ) );
    }
    $image = '<!-- wp:image ' . wp_json_encode( array( 'id' => $image_id, 'sizeSlug' => 'large', 'linkDestination' => 'none', 'className' => 'vpm-hero-image' ) ) . ' --><figure class="wp-block-image size-large vpm-hero-image"><img src="' . esc_url( wp_get_attachment_image_url( $image_id, 'large' ) ) . '" alt="' . esc_attr( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) . '" class="wp-image-' . (int) $image_id . '"/><figcaption class="wp-element-caption">KI-generierte Illustration</figcaption></figure><!-- /wp:image -->';
    foreach ( $data['pages'] as $page ) {
        $existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
        if ( $existing || get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_vpm_import_slug', 'meta_value' => $page['slug'], 'numberposts' => 1 ) ) ) { $result['skipped'][] = $page['title']; continue; }
        $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_name' => $page['slug'], 'post_title' => $page['title'], 'post_content' => str_replace( '__VPM_HERO_IMAGE__', $image, $page['content'] ) ), true );
        if ( is_wp_error( $id ) ) { return $id; }
        update_post_meta( $id, '_wp_page_template', 'templates/full-width.php' );
        update_post_meta( $id, '_vpm_import_slug', $page['slug'] );
        $result['pages'][] = $id;
    }
    return $result;
}
add_action( 'admin_menu', function() {
    add_management_page( 'VPM Inhaltsentwürfe', 'VPM Inhaltsentwürfe', 'manage_options', 'vpm-content-drafts', function() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        echo '<div class="wrap"><h1>VPM Inhaltsentwürfe</h1><p>Legt Startseite, Preise, Sicherheit, Über uns und Valet-Ratgeber als Entwürfe an. Vorhandene Seiten und Anbieter werden übersprungen. Es wird nichts veröffentlicht, keine Startseite und kein Menü umgestellt.</p><p>Anbieter ebenfalls als Entwürfe: vor Veröffentlichung Leistungen prüfen. Anbieterzahl gemäß Freigabe entfernt; „24/7 Betrieb“ korrigiert. Rechtstexte folgen nach Einrichtung der Plugins.</p>';
        if ( isset( $_POST['vpm_import'] ) ) {
            check_admin_referer( 'vpm_import_drafts' );
            $result = vpm_import_content_drafts();
            if ( is_wp_error( $result ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>'; }
            else { echo '<div class="notice notice-success"><p>' . count( $result['pages'] ) . ' Seitenentwürfe, ' . count( $result['providers'] ) . ' Anbieterentwürfe angelegt; ' . count( $result['skipped'] ) . ' vorhandene Inhalte übersprungen.</p></div>'; }
        }
        if ( isset( $_POST['vpm_add_symbol_images'] ) ) {
            check_admin_referer( 'vpm_provider_symbol_images' );
            $result = vpm_add_provider_symbol_images();
            if ( is_wp_error( $result ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>'; }
            else { echo '<div class="notice notice-success"><p>' . (int) $result . ' fehlende Anbieterbilder ergänzt.</p></div>'; }
        }
        if ( isset( $_POST['vpm_refresh_symbol_images'] ) ) {
            check_admin_referer( 'vpm_refresh_provider_symbols' );
            $result = vpm_add_provider_symbol_images( null, true );
            if ( is_wp_error( $result ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>'; }
            else { echo '<div class="notice notice-success"><p>' . (int) $result . ' verwaltete Symbolbilder aktualisiert.</p></div>'; }
        }
        if ( isset( $_POST['vpm_rank_math_metadata'] ) ) {
            check_admin_referer( 'vpm_rank_math_metadata' );
            $result = vpm_prepare_rank_math_metadata();
            if ( is_wp_error( $result ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>'; }
            else { echo '<div class="notice notice-success"><p>' . (int) $result . ' leere Rank-Math-Felder ergänzt. Vorhandene SEO-Angaben bleiben erhalten.</p></div>'; }
        }
        vpm_english_admin();
        vpm_legal_admin();
        vpm_publication_admin();
        echo '<h2>Rank-Math-Angaben</h2><p>Übernimmt die gelieferten SEO-Titel und Beschreibungen in leere Felder der importierten Seiten. Ändert weder Indexierung noch Sitemap-Einstellungen.</p><form method="post">';
        wp_nonce_field( 'vpm_rank_math_metadata' ); submit_button( 'Leere Rank-Math-Felder ergänzen', 'secondary', 'vpm_rank_math_metadata' ); echo '</form>';
        echo '<h2>Symbolbilder ergänzen</h2><p>Sechs unterschiedliche KI-Symbolbilder für Valet-Parking. Sie zeigen keine tatsächlichen Anbieter oder Stellplätze. Ergänzt nur Anbieter ohne Beitragsbild; vorhandene Bilder, Texte und Veröffentlichungsstatus bleiben erhalten.</p><form method="post">';
        wp_nonce_field( 'vpm_provider_symbol_images' ); submit_button( 'Fehlende Symbolbilder ergänzen', 'secondary', 'vpm_add_symbol_images' ); echo '</form>';
        echo '<p>Optional: Ersetzt die bisherigen verwalteten KI-Symbolbilder durch unterschiedliche Motive. Eigene Bilder und Veröffentlichungsstatus bleiben erhalten.</p><form method="post">';
        wp_nonce_field( 'vpm_refresh_provider_symbols' ); submit_button( 'Verwaltete KI-Symbolbilder aktualisieren', 'secondary', 'vpm_refresh_symbol_images' ); echo '</form>';
        echo '<form method="post">'; wp_nonce_field( 'vpm_import_drafts' ); submit_button( 'Neue Entwürfe importieren', 'primary', 'vpm_import' ); echo '</form></div>';
    } );
} );
