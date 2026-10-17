<?php
/** Explicit, repeatable multilingual preparation. Never runs on activation. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_english_requirements() {
    if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Keine Berechtigung.' ); }
    if ( ! function_exists( 'PLL' ) || ! function_exists( 'pll_set_post_language' ) ) { return new WP_Error( 'polylang', 'Bitte Polylang zuerst installieren und aktivieren.' ); }
    if ( ! post_type_exists( 'vpm_provider' ) ) { return new WP_Error( 'providers', 'VPM Anbieter muss aktiv sein.' ); }
    return true;
}
function vpm_prepare_languages() {
    $ready = vpm_english_requirements(); if ( is_wp_error( $ready ) ) { return $ready; }
    $default = pll_default_language();
    if ( $default && 'de' !== $default ) { return new WP_Error( 'language', 'Bestehende Standardsprache ist nicht Deutsch. Keine Einstellungen geändert.' ); }
    foreach ( array( array( 'slug' => 'de', 'locale' => 'de_DE', 'name' => 'Deutsch', 'flag' => 'de' ), array( 'slug' => 'en', 'locale' => 'en_GB', 'name' => 'English', 'flag' => 'gb' ) ) as $lang ) {
        if ( ! PLL()->model->get_language( $lang['slug'] ) ) {
            $added = PLL()->model->add_language( $lang );
            if ( is_wp_error( $added ) ) { return $added; }
        }
    }
    $options = PLL()->options;
    foreach ( array( 'default_lang' => 'de', 'force_lang' => 1, 'hide_default' => true, 'rewrite' => true, 'redirect_lang' => true, 'browser' => false, 'media_support' => false ) as $key => $value ) { $options[ $key ] = $value; }
    $options->save();
    // Assign only content without an existing language. Do not reassign translations.
    $ids = get_posts( array( 'post_type' => array( 'page', 'post', 'vpm_provider' ), 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'lang' => '', 'suppress_filters' => true ) );
    foreach ( $ids as $id ) { if ( ! pll_get_post_language( $id ) ) { pll_set_post_language( $id, 'de' ); } }
    foreach ( wp_get_nav_menus() as $menu ) { if ( ! pll_get_term_language( $menu->term_id ) ) { pll_set_term_language( $menu->term_id, 'de' ); } }
    // Polylang needs its own location map; retain the existing German menu IDs.
    $theme = get_stylesheet(); $raw_mods = get_option( 'theme_mods_' . $theme, array() );
    $nav = $options['nav_menus'];
    foreach ( ( $raw_mods['nav_menu_locations'] ?? array() ) as $location => $menu_id ) {
        if ( ! str_contains( $location, '___' ) && $menu_id && empty( $nav[ $theme ][ $location ]['de'] ) && 'de' === pll_get_term_language( $menu_id ) ) { $nav[ $theme ][ $location ]['de'] = $menu_id; }
    }
    $options['nav_menus'] = $nav; $options->save();
    flush_rewrite_rules( false );
    return 'Deutsch und Englisch vorbereitet; deutsche Inhalte und bestehende Menüs behalten. Browser-Weiterleitung ausgeschaltet. Jetzt englische Entwürfe importieren.';
}
function vpm_original_page( $slug ) {
    $posts = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'lang' => '', 'suppress_filters' => true ) );
    foreach ( $posts as $p ) { if ( ( get_post_meta( $p->ID, '_vpm_import_slug', true ) === $slug || $p->post_name === $slug ) && 'de' === pll_get_post_language( $p->ID ) ) { return $p->ID; } }
    return 0;
}
function vpm_import_english() {
    $ready = vpm_english_requirements(); if ( is_wp_error( $ready ) ) { return $ready; }
    if ( ! PLL()->model->get_language( 'en' ) || ! PLL()->model->get_language( 'de' ) ) { return new WP_Error( 'languages', 'Bitte zuerst die Sprachvorbereitung ausführen.' ); }
    $data = json_decode( file_get_contents( __DIR__ . '/content-en.json' ), true );
    $created = 0; $page_ids = array();
    foreach ( $data['pages'] as $page ) {
        $source = vpm_original_page( $page['source_slug'] );
        if ( ! $source ) { return new WP_Error( 'source', 'Deutsche Originalseite fehlt: ' . $page['source_slug'] ); }
        $existing = pll_get_post( $source, 'en' );
        if ( $existing ) { $page_ids[ $page['slug'] ] = $existing; continue; }
        // An unpaired previous import can be safely reattached, not duplicated.
        $previous = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_vpm_en_source', 'meta_value' => $source, 'numberposts' => 1, 'lang' => '', 'suppress_filters' => true ) );
        if ( $previous ) { $id = $previous[0]->ID; }
        else {
            $content = $page['content'];
            if ( str_contains( $content, '__VPM_HERO_IMAGE__' ) ) {
                $source_content = get_post_field( 'post_content', $source );
                if ( preg_match( '~<!-- wp:image\b.*?vpm-hero-image.*?<!-- /wp:image -->~s', $source_content, $image ) ) { $content = str_replace( '__VPM_HERO_IMAGE__', $image[0], $content ); }
                else { return new WP_Error( 'hero', 'Hero-Bild der deutschen Startseite nicht gefunden.' ); }
            }
            $content = str_replace( array( 'KI-generierte Illustration', 'Reisende und Valet-Mitarbeiter bei der Fahrzeugübergabe vor einem Flughafenterminal' ), array( 'AI-generated illustration', 'Traveller handing over a vehicle to a valet attendant outside an airport terminal' ), $content );
            $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_name' => $page['slug'], 'post_title' => $page['title'], 'post_content' => $content ), true );
            if ( is_wp_error( $id ) ) { return $id; }
            update_post_meta( $id, '_wp_page_template', 'templates/full-width.php' );
            update_post_meta( $id, '_vpm_en_source', $source );
            update_post_meta( $id, '_vpm_layout_slug', $page['source_slug'] );
            update_post_meta( $id, 'rank_math_title', $page['seo_title'] );
            if ( $page['seo_description'] ) { update_post_meta( $id, 'rank_math_description', $page['seo_description'] ); }
            if ( 'home' === $page['slug'] ) { update_post_meta( $id, 'rank_math_focus_keyword', 'valet parking munich airport,munich airport valet parking' ); }
            ++$created;
        }
        pll_set_post_language( $id, 'en' ); pll_save_post_translations( array( 'de' => $source, 'en' => $id ) );
        $page_ids[ $page['slug'] ] = $id;
    }
    foreach ( $data['providers'] as $provider ) {
        $originals = get_posts( array( 'post_type' => 'vpm_provider', 'post_status' => 'any', 'title' => $provider['source_name'], 'numberposts' => -1, 'lang' => '', 'suppress_filters' => true ) );
        $source = 0;
        foreach ( $originals as $original ) { if ( 'de' === pll_get_post_language( $original->ID ) ) { $source = $original->ID; break; } }
        if ( ! $source || pll_get_post( $source, 'en' ) ) { continue; }
        $id = wp_insert_post( array( 'post_type' => 'vpm_provider', 'post_status' => 'draft', 'post_title' => $provider['name'], 'menu_order' => get_post_field( 'menu_order', $source ) ), true );
        if ( is_wp_error( $id ) ) { return $id; }
        foreach ( $provider['meta'] as $key => $value ) {
            // Keep the actual live account links, not stale links from source files.
            if ( str_ends_with( $key, '_url' ) ) { $value = get_post_meta( $source, '_vpm_' . $key, true ); }
            update_post_meta( $id, '_vpm_' . $key, $value );
        }
        update_post_meta( $id, '_vpm_en_source', $source );
        pll_set_post_language( $id, 'en' ); pll_save_post_translations( array( 'de' => $source, 'en' => $id ) ); ++$created;
    }
    // Prepare English menus without editing the original German menus.
    $nav = PLL()->options['nav_menus']; $theme = get_stylesheet();
    foreach ( array( 'primary', 'footer' ) as $location ) {
        if ( ! empty( $nav[ $theme ][ $location ]['en'] ) ) { continue; }
        $menu = wp_create_nav_menu( 'VPM English ' . $location );
        if ( is_wp_error( $menu ) ) { continue; }
        pll_set_term_language( $menu, 'en' );
        if ( 'primary' === $location ) { wp_update_nav_menu_item( $menu, 0, array( 'menu-item-title' => 'Providers', 'menu-item-url' => home_url( '/en/#anbieter' ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) ); }
        foreach ( array( 'prices' => 'Prices', 'valet-parking-safety' => 'Safety', 'about-us' => 'About us' ) as $slug => $label ) {
            wp_update_nav_menu_item( $menu, 0, array( 'menu-item-title' => $label, 'menu-item-object-id' => $page_ids[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
        }
        $nav[ $theme ][ $location ]['en'] = $menu;
    }
    PLL()->options['nav_menus'] = $nav; PLL()->options->save();
    flush_rewrite_rules( false );
    return $created . ' englische Seiten-/Anbieterentwürfe angelegt; vorhandene Übersetzungen unverändert. Englische Menüs vorbereitet. Keine Entwürfe veröffentlicht.';
}
function vpm_english_admin() {
    foreach ( array( 'vpm_languages' => 'vpm_prepare_languages', 'vpm_english' => 'vpm_import_english' ) as $action => $callback ) {
        if ( isset( $_POST[ $action ] ) ) { check_admin_referer( $action ); $result = $callback(); echo '<div class="notice ' . ( is_wp_error( $result ) ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( is_wp_error( $result ) ? $result->get_error_message() : $result ) . '</p></div>'; }
    }
    echo '<h2>Deutsch / Englisch mit Polylang</h2><p>Erst Polylang installieren. Sprachvorbereitung weist bisher sprachlose Inhalte Deutsch zu und richtet /en/ ohne Browser-Weiterleitung ein. Danach Übersetzungen importieren. Vorhandene Übersetzungen werden nicht überschrieben; neue Inhalte bleiben Entwurf.</p>';
    foreach ( array( 'vpm_languages' => '1. Deutsch und Englisch vorbereiten', 'vpm_english' => '2. Englische Entwürfe importieren' ) as $action => $label ) { echo '<form method="post">'; wp_nonce_field( $action ); submit_button( $label, 'secondary', $action ); echo '</form>'; }
}
