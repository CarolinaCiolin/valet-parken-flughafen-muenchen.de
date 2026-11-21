<?php
/** Approved decorative illustrations. Custom uploaded provider photos are retained. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_provider_artwork( $id ) {
    $thumbnail = get_post_thumbnail_id( $id );
    $key = (string) get_post_meta( $thumbnail, '_vpm_asset_key', true );
    if ( $thumbnail && ! str_starts_with( $key, 'provider-symbol-ai-' ) ) { return ''; }
    if ( function_exists( 'vpm_legacy_munich' ) && ! vpm_legacy_munich() ) { return ''; }
    $names = array( 'Park Drive Fly', 'ParkandGo MUC Valetservice', 'Parkking Munich Meet and Greet', 'Valet Service Clean', 'Parkservice Flieger', 'Parkservice-Unertl' );
    $index = array_search( get_the_title( $id ), $names, true );
    if ( false === $index ) {
        $new = array( 'Airparks Parkplatz München Valet' => 'airparks', 'Parkprofi Parkhaus Valet München' => 'parkprofi', 'valet24muc Parkplatz Valet' => 'valet24muc' );
        $slug = $new[get_the_title($id)] ?? '';
        return $slug ? '<div class="vpm-provider-art vpm-provider-art-single" aria-hidden="true"><img src="'.esc_url(plugins_url('assets/provider-'.$slug.'.svg',__FILE__)).'" width="720" height="350" alt="" loading="lazy" decoding="async"></div>' : '';
    }
    return '<div class="vpm-provider-art vpm-provider-art-' . (int) $index . '" aria-hidden="true"><img src="' . esc_url( plugins_url( 'assets/provider-illustrations.webp', __FILE__ ) ) . '" width="2160" height="728" alt="" loading="lazy" decoding="async"></div>';
}
