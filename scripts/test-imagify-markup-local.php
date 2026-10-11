<?php
/** Source-level formatting check, not an activated Imagify/API test. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
$source = '/tmp/vpm-imagify-source/';
require $source . 'inc/classes/Dependencies/wp-media/event-manager/SubscriberInterface.php';
require $source . 'classes/Picture/Display.php';
$reflection = new ReflectionClass( Imagify\Picture\Display::class );
$display = $reflection->newInstanceWithoutConstructor();
$build = $reflection->getMethod( 'build_picture_tag' ); $build->setAccessible( true );
$home = file_get_contents( '/tmp/vpm-imagify-fixture/index.html' );
preg_match( '#<picture[^>]*>.*?</picture>#s', $home, $match );
if ( empty( $match[0] ) || $display->process_content( $match[0] ) !== $match[0] ) { throw new RuntimeException( 'Existing hero picture must remain unchanged.' ); }
echo 'Imagify 2.3.4: existing hero picture remains unchanged.' . PHP_EOL;
foreach ( array( 'index', 'preise', 'sicherheit', 'ueber-uns' ) as $page ) {
    $html = file_get_contents( '/tmp/vpm-imagify-fixture/' . $page . '.html' );
    // Isolate existing pictures first; never create nested pictures or mobile hero sources.
    $protected = array();
    $html = preg_replace_callback( '#<picture[^>]*>.*?</picture>#s', function ( $m ) use ( &$protected ) { $key = '<!--PROTECTED-' . count( $protected ) . '-->'; $protected[ $key ] = $m[0]; return $key; }, $html );
    $html = preg_replace_callback( '#<img\s[^>]+>#i', function ( $m ) use ( $display, $build ) {
        preg_match_all( '/([a-zA-Z0-9_-]+)="([^"]*)"/', $m[0], $matches, PREG_SET_ORDER ); $attrs = array();
        foreach ( $matches as $attr ) { $attrs[ $attr[1] ] = html_entity_decode( $attr[2], ENT_QUOTES, 'UTF-8' ); }
        if ( empty( $attrs['src'] ) || ! str_contains( $attrs['src'], '.webp' ) ) { return $m[0]; }
        $set = array();
        foreach ( explode( ',', $attrs['srcset'] ?? '' ) as $part ) {
            $parts = preg_split( '/\s+/', trim( $part ) );
            if ( count( $parts ) === 2 ) { $set[] = array( 'webp_url' => $parts[0], 'descriptor' => $parts[1] ); }
        }
        // Synthetic wrapper uses existing real WebPs: no conversion, API call or invented AVIF.
        return $build->invoke( $display, array( 'attributes' => $attrs, 'src_attribute' => 'src', 'srcset_attribute' => isset( $attrs['srcset'] ) ? 'srcset' : false, 'srcset' => $set, 'src' => array( 'webp_url' => $attrs['src'] ) ) );
    }, $html );
    $html = strtr( $html, $protected );
    file_put_contents( '/tmp/vpm-imagify-fixture/' . $page . '-picture.html', $html );
}
echo 'Real Imagify picture formatter produced four structural fixtures; no image optimization performed.' . PHP_EOL;
