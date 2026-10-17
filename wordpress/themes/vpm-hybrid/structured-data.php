<?php
/** One shared JSON-LD graph through Rank Math, or a fallback if its output is absent. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_schema_questions( $content ) {
    if ( ! class_exists( 'DOMDocument' ) || ! str_contains( $content, '<details' ) ) { return array(); }
    $doc = new DOMDocument(); $previous = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="UTF-8">' . $content ); libxml_clear_errors(); libxml_use_internal_errors( $previous ); $questions = array();
    foreach ( $doc->getElementsByTagName( 'details' ) as $details ) {
        $summary = $details->getElementsByTagName( 'summary' )->item( 0 ); if ( ! $summary ) { continue; }
        $parts = array();
        foreach ( $details->childNodes as $node ) { if ( $node !== $summary && ! ( $node instanceof DOMComment ) ) { $parts[] = trim( $node->textContent ); } }
        $answer = trim( preg_replace( '/\s+/u', ' ', implode( ' ', $parts ) ) );
        if ( $answer ) { $questions[] = array( '@type' => 'Question', 'name' => trim( $summary->textContent ), 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $answer ) ); }
    }
    return $questions;
}
function vpm_schema_provider_items( $url ) {
    $lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : false;
    $args = array( 'post_type' => 'vpm_provider', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ), 'no_found_rows' => true, 'meta_query' => array( array( 'key' => '_vpm_active', 'value' => '1' ) ) );
    if ( $lang ) { $args['lang'] = 'de' === $lang ? '' : $lang; }
    $query = new WP_Query( $args ); $items = array();
    foreach ( $query->posts as $provider ) {
        if ( 'de' === $lang && function_exists( 'pll_get_post_language' ) && ! in_array( pll_get_post_language( $provider->ID ), array( false, 'de', '' ), true ) ) { continue; }
        $anchor = function_exists( 'vpm_provider_anchor' ) ? vpm_provider_anchor( $provider->ID ) : '';
        if ( ! $anchor ) { continue; }
        $items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => get_the_title( $provider->ID ), 'url' => $url . '#' . $anchor, 'item' => array( '@type' => 'Service', '@id' => $url . '#' . $anchor . '-service', 'name' => get_the_title( $provider->ID ), 'url' => $url . '#' . $anchor, 'serviceType' => vpm_theme_text( 'Valet-Parken am Flughafen München', 'Valet parking at Munich Airport' ), 'provider' => array( '@type' => 'Organization', 'name' => get_the_title( $provider->ID ) ), 'description' => wp_strip_all_tags( get_post_meta( $provider->ID, '_vpm_features', true ) ) ) );
    }
    return $items;
}
/** Read the visible comparison table, never a second hard-coded price catalogue. */
function vpm_schema_price_services( $content, $url, $locale ) {
    if ( ! class_exists( 'DOMDocument' ) ) { return array(); }
    $doc = new DOMDocument(); $previous = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="UTF-8">' . $content ); libxml_clear_errors(); libxml_use_internal_errors( $previous );
    $services = array();
    foreach ( $doc->getElementsByTagName( 'table' ) as $table ) {
        $headers = $table->getElementsByTagName( 'th' );
        if ( $headers->length < 4 || ! preg_match( '/8\s*(?:Tage|days)/iu', $headers->item( 1 )->textContent ) || ! preg_match( '/\bab\b|\bfrom\b/iu', $headers->item( 1 )->textContent ) ) { continue; }
        foreach ( $table->getElementsByTagName( 'tr' ) as $row ) {
            $cells = $row->getElementsByTagName( 'td' ); if ( $cells->length !== 4 ) { continue; }
            $name = trim( $cells->item( 0 )->textContent ); $price_text = trim( $cells->item( 1 )->textContent );
            if ( ! preg_match( '/(?:€\s*([0-9]+(?:[.,][0-9]{1,2})?)|([0-9]+(?:[.,][0-9]{1,2})?)\s*€)/u', $price_text, $match ) ) { continue; }
            $price = str_replace( ',', '.', ! empty( $match[1] ) ? $match[1] : $match[2] );
            $link = $cells->item( 3 )->getElementsByTagName( 'a' )->item( 0 );
            if ( ! $name || ! $link ) { continue; } $booking = esc_url_raw( $link->getAttribute( 'href' ), array( 'https' ) ); if ( ! $booking ) { continue; }
            $english = str_starts_with( $locale, 'en' );
            $description = $english ? 'Starting total for 8 days including parking and valet service. Binding price depends on travel dates, handover times and parking type; check with the booking partner.' : 'Ab-Gesamtpreis für 8 Tage inklusive Parkplatz und Valet-Service. Verbindlicher Preis abhängig von Reisedaten, Übergabezeiten und Stellplatzart; beim Buchungspartner prüfen.';
            $services[] = array( '@type' => 'Service', '@id' => $url . '#service-' . sanitize_title( $name ), 'name' => $name, 'serviceType' => $english ? 'Valet parking at Munich Airport' : 'Valet-Parken am Flughafen München', 'provider' => array( '@type' => 'Organization', 'name' => $name ), 'areaServed' => array( '@type' => 'Place', 'name' => $english ? 'Munich Airport' : 'Flughafen München' ), 'description' => trim( $cells->item( 2 )->textContent ), 'offers' => array( '@type' => 'Offer', 'url' => $url, 'description' => $description, 'priceSpecification' => array( '@type' => 'UnitPriceSpecification', 'minPrice' => $price, 'priceCurrency' => 'EUR', 'description' => $description, 'referenceQuantity' => array( '@type' => 'QuantitativeValue', 'value' => 8, 'unitCode' => 'DAY', 'unitText' => $english ? 'days' : 'Tage' ) ) ) );
        }
        break;
    }
    return $services;
}
function vpm_schema_find_query( $blocks ) {
    foreach ( $blocks as $block ) { if ( 'core/query' === $block['blockName'] ) { return $block; } $nested = vpm_schema_find_query( $block['innerBlocks'] ?? array() ); if ( $nested ) { return $nested; } }
    return null;
}
function vpm_schema_guide_items( $id ) {
    $query = vpm_schema_find_query( parse_blocks( get_post_field( 'post_content', $id ) ) );
    if ( ! $query || ! function_exists( 'build_query_vars_from_query_block' ) ) { return array(); }
    $attrs = $query['attrs']; $query_id = absint( $attrs['queryId'] ?? 0 );
    $page = max( 1, absint( $_GET[ 'query-' . $query_id . '-page' ] ?? 1 ) );
    $block = new WP_Block( array( 'blockName' => 'core/post-template', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ), array( 'query' => $attrs['query'] ?? array(), 'queryId' => $query_id ) );
    $args = build_query_vars_from_query_block( $block, $page ); $args['post_status'] = 'publish';
    if ( function_exists( 'pll_current_language' ) && pll_current_language() ) { $args['lang'] = pll_current_language( 'slug' ); }
    $posts = new WP_Query( $args ); $items = array();
    foreach ( $posts->posts as $post ) { $items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => get_the_title( $post->ID ), 'url' => get_permalink( $post->ID ) ); }
    return $items;
}
function vpm_schema_graph() {
    if ( ! is_singular( array( 'page', 'post' ) ) || is_preview() || vpm_is_legal_page() || 'publish' !== get_post_status( get_queried_object_id() ) ) { return array(); }
    $id = get_queried_object_id(); $url = get_permalink( $id ); $key = vpm_layout_key( $id );
    $root = trailingslashit( (string) get_option( 'home' ) ); $home = vpm_theme_home(); $locale = str_replace( '_', '-', get_locale() );
    $publisher = $root . '#organization'; $website = $root . '#website'; $page_id = $url . '#webpage';
    $locales = function_exists( 'pll_languages_list' ) ? pll_languages_list( array( 'fields' => 'locale', 'hide_empty' => true ) ) : array( $locale );
    $graph = array(
        'vpmPublisher' => array( '@type' => 'Organization', '@id' => $publisher, 'name' => get_bloginfo( 'name' ), 'url' => $root, 'logo' => array( '@type' => 'ImageObject', 'url' => get_site_icon_url( 512, get_theme_file_uri( 'assets/site-icon.png' ) ) ) ),
        'vpmWebsite' => array( '@type' => 'WebSite', '@id' => $website, 'url' => $root, 'name' => get_bloginfo( 'name' ), 'publisher' => array( '@id' => $publisher ), 'inLanguage' => array_values( array_unique( array_map( function( $s ) { return str_replace( '_', '-', $s ); }, $locales ?: array( $locale ) ) ) ) ),
    );
    $type = 'ueber-uns' === $key ? 'AboutPage' : ( in_array( $key, array( 'startseite', 'ratgeber' ), true ) ? 'CollectionPage' : 'WebPage' );
    $context = vpm_page_context(); $parent = $context['parent'] ?? null;
    $page = array( '@type' => $type, '@id' => $page_id, 'url' => $url, 'name' => get_the_title( $id ), 'inLanguage' => $locale, 'isPartOf' => array( array( '@id' => $website ) ), 'publisher' => array( '@id' => $publisher ) );
    if ( $parent && $parent !== $url ) { $page['isPartOf'][] = array( '@id' => $parent . '#webpage' ); }
    $description = get_post_meta( $id, 'rank_math_description', true );
    if ( $description && ! str_contains( $description, '%' ) ) { $page['description'] = wp_strip_all_tags( $description ); }
    if ( $context ) {
        $items = array(); foreach ( $context['crumbs'] as $i => $crumb ) { $items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['url'] ); }
        $graph['vpmBreadcrumbs'] = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items );
        $page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
        $page['about'] = array( '@type' => 'Thing', 'name' => $context['role'] );
    }
    if ( 'startseite' === $key ) {
        $items = vpm_schema_provider_items( $url );
        if ( $items ) { $graph['vpmProviders'] = array( '@type' => 'ItemList', '@id' => $url . '#providers', 'name' => vpm_theme_text( 'Verfügbare Valet-Angebote', 'Available valet parking offers' ), 'numberOfItems' => count( $items ), 'itemListElement' => $items ); $page['mainEntity'] = array( '@id' => $url . '#providers' ); }
    }
    if ( 'preise' === $key ) {
        $services = vpm_schema_price_services( get_post_field( 'post_content', $id ), $url, $locale );
        if ( $services ) {
            $items = array(); foreach ( $services as $service ) { $items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'item' => $service ); }
            $graph['vpmPrices'] = array( '@type' => 'ItemList', '@id' => $url . '#price-comparison', 'numberOfItems' => count( $items ), 'itemListElement' => $items );
            $page['mainEntity'] = array( '@id' => $url . '#price-comparison' );
        }
    }
    if ( 'ratgeber' === $key ) {
        $items = vpm_schema_guide_items( $id );
        if ( $items ) { $graph['vpmArticles'] = array( '@type' => 'ItemList', '@id' => $url . '#articles', 'numberOfItems' => count( $items ), 'itemListElement' => $items ); $page['mainEntity'] = array( '@id' => $url . '#articles' ); }
    }
    $questions = vpm_schema_questions( get_post_field( 'post_content', $id ) );
    if ( $questions ) { $graph['vpmFAQ'] = array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'url' => $url, 'inLanguage' => $locale, 'isPartOf' => array( '@id' => $page_id ), 'mainEntity' => $questions ); $page['hasPart'] = array( '@id' => $url . '#faq' ); }
    if ( is_singular( 'post' ) ) {
        $graph['vpmArticle'] = array( '@type' => 'BlogPosting', '@id' => $url . '#article', 'headline' => get_the_title( $id ), 'mainEntityOfPage' => array( '@id' => $page_id ), 'isPartOf' => array( '@id' => ( $parent ? $parent . '#webpage' : $website ) ), 'publisher' => array( '@id' => $publisher ), 'datePublished' => get_post_time( DATE_W3C, true, $id ), 'dateModified' => get_post_modified_time( DATE_W3C, true, $id ), 'inLanguage' => $locale );
        $page['mainEntity'] = array( '@id' => $url . '#article' );
    }
    $graph['vpmPage'] = $page;
    return $graph;
}
add_filter( 'rank_math/json_ld', function( $data ) {
    if ( ! is_singular( array( 'page', 'post' ) ) ) { return $data; }
    $GLOBALS['vpm_schema_handled'] = true;
    return vpm_schema_graph();
}, 1000 );
add_action( 'wp_head', function() {
    if ( ! empty( $GLOBALS['vpm_schema_handled'] ) ) { return; }
    $graph = vpm_schema_graph(); if ( ! $graph ) { return; }
    echo '<script type="application/ld+json" class="vpm-schema">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array_values( $graph ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
}, 99 );
