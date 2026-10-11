<?php
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) ) { throw new RuntimeException( 'Local-only setup guard.' ); }
require '/var/www/html/wp-load.php';
if ( '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Not a localhost test installation.' ); }
require __DIR__ . '/vpm-hero-media-local.php';
$pages = get_posts( array( 'post_type' => 'page', 'name' => 'vpm-local-preview', 'post_status' => 'any', 'numberposts' => 1 ) );
if ( ! $pages ) { throw new RuntimeException( 'Preview not found.' ); }
$id = vpm_local_hero_attachment();
$content = $pages[0]->post_content;
$placeholder = '<!-- wp:paragraph {"className":"vpm-placeholder"} --><p class="vpm-placeholder">Lokaler Layouttest: Bildmotiv noch nicht freigegeben.</p><!-- /wp:paragraph -->';
if ( str_contains( $content, $placeholder ) ) {
    $content = str_replace( $placeholder, vpm_local_hero_block( $id ), $content );
    $content = str_replace( array( '"width":"65%"', 'flex-basis:65%', '"width":"35%"', 'flex-basis:35%' ), array( '"width":"52%"', 'flex-basis:52%', '"width":"48%"', 'flex-basis:48%' ), $content );
    $result = wp_update_post( array( 'ID' => $pages[0]->ID, 'post_content' => $content ), true );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    echo 'KI-Motiv im nativen Gutenberg-Bildblock eingefügt; Kennzeichnung sichtbar.' . PHP_EOL;
} else {
    echo 'Kein ursprünglicher Platzhalter vorhanden; bestehender Inhalt bleibt unverändert.' . PHP_EOL;
}
