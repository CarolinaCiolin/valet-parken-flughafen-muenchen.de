<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_is_english() { return function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' ); }
function vpm_text( $de, $en ) { return vpm_is_english() ? $en : $de; }
function vpm_language_home() { return function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ); }
add_filter( 'pll_get_post_types', function ( $types, $settings ) { $types['vpm_provider'] = 'vpm_provider'; return $types; }, 10, 2 );
