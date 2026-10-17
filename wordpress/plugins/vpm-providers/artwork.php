<?php
/** Approved decorative illustrations. Custom uploaded provider photos are retained. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_provider_artwork( $id ) {
    $thumbnail = get_post_thumbnail_id( $id );
    $key = (string) get_post_meta( $thumbnail, '_vpm_asset_key', true );
    if ( $thumbnail && ! str_starts_with( $key, 'provider-symbol-ai-' ) ) { return ''; }
    $names = array( 'Park Drive Fly', 'ParkandGo MUC Valetservice', 'Parkking Munich Meet and Greet', 'Valet Service Clean', 'Parkservice Flieger', 'Parkservice-Unertl' );
    $index = array_search( get_the_title( $id ), $names, true );
    if ( false === $index ) { return ''; }
    return '<div class="vpm-provider-art vpm-provider-art-' . (int) $index . '" aria-hidden="true"><img src="' . esc_url( plugins_url( 'assets/provider-illustrations.webp', __FILE__ ) ) . '" width="2160" height="728" alt="" loading="lazy" decoding="async"></div>';
}
