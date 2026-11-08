<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_theme_english() { return function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' ); }
function vpm_theme_text( $de, $en ) { return vpm_theme_english() ? $en : $de; }
function vpm_theme_home() { return function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ); }
function vpm_theme_url( $path ) {
    $roles = array( '/preise/' => 'prices', '/ueber-uns/' => 'about', '/valet-parken-sicherheit/' => 'safety', '/ratgeber/' => 'guide' );
    return home_url( isset( $roles[ $path ] ) ? vpm_profile_path( $roles[ $path ] ) : $path );
}
function vpm_language_switcher() {
    if ( ! function_exists( 'pll_the_languages' ) ) { return; }
    $items = pll_the_languages( array( 'raw' => true, 'hide_if_empty' => true, 'hide_if_no_translation' => true ) );
    if ( count( $items ) < 2 ) { return; }
    echo '<nav class="vpm-language-switcher" aria-label="' . esc_attr( vpm_theme_text( 'Sprache wählen', 'Select language' ) ) . '">';
    foreach ( $items as $item ) {
        echo '<a href="' . esc_url( $item['url'] ) . '" hreflang="' . esc_attr( str_replace( '_', '-', $item['locale'] ) ) . '" lang="' . esc_attr( $item['slug'] ) . '"' . ( $item['current_lang'] ? ' aria-current="true"' : '' ) . '>' . esc_html( strtoupper( $item['slug'] ) ) . '</a>';
    }
    echo '</nav>';
}
