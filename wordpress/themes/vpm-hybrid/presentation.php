<?php
/** Presentation only: retain every existing text node and link. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_filter( 'the_content', function ( $html ) {
    if ( ! is_page() || ! in_the_loop() || ! is_main_query() || false === strpos( $html, 'wp-block-heading' ) || ! class_exists( 'DOMDocument' ) ) { return $html; }
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="UTF-8"><div id="vpm-render-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors(); libxml_use_internal_errors( $previous );
    $root = $doc->getElementById( 'vpm-render-root' );
    if ( ! $root ) { return $html; }
    $nodes = iterator_to_array( $root->childNodes );
    $section = null; $grid = null; $card = null; $index = 0;
    foreach ( $nodes as $node ) {
        if ( $node instanceof DOMElement && 'h2' === $node->tagName ) {
            $section = $doc->createElement( 'section' );
            $section->setAttribute( 'class', 'alignfull vpm-editorial-section vpm-section-' . ++$index );
            $root->insertBefore( $section, $node );
            $grid = null; $card = null;
        }
        if ( $section ) {
            if ( $node instanceof DOMElement && 'h3' === $node->tagName ) {
                if ( ! $grid ) { $grid = $doc->createElement( 'div' ); $grid->setAttribute( 'class', 'vpm-editorial-grid' ); $section->appendChild( $grid ); }
                $card = $doc->createElement( 'div' ); $card->setAttribute( 'class', 'vpm-editorial-card' ); $grid->appendChild( $card );
            }
            ( $card ?: $section )->appendChild( $node );
        } elseif ( $node instanceof DOMElement && 'ul' === $node->tagName ) {
            $node->setAttribute( 'class', $node->getAttribute( 'class' ) . ' alignwide vpm-benefits' );
        }
    }
    foreach ( $root->getElementsByTagName( 'p' ) as $paragraph ) {
        $elements = array();
        foreach ( $paragraph->childNodes as $child ) { if ( $child instanceof DOMElement ) { $elements[] = $child; } }
        if ( 1 === count( $elements ) && 'a' === $elements[0]->tagName && trim( $paragraph->textContent ) === trim( $elements[0]->textContent ) ) {
            $paragraph->setAttribute( 'class', $paragraph->getAttribute( 'class' ) . ' vpm-action' );
        }
    }
    foreach ( $root->getElementsByTagName( 'table' ) as $table ) {
        $table->setAttribute( 'role', 'table' );
        $labels = array();
        foreach ( $table->getElementsByTagName( 'th' ) as $header ) {
            $header->setAttribute( 'scope', 'col' ); $labels[] = trim( $header->textContent );
        }
        foreach ( $table->getElementsByTagName( 'tr' ) as $row ) {
            $row->setAttribute( 'role', 'row' ); $column = 0;
            foreach ( $row->childNodes as $cell ) {
                if ( $cell instanceof DOMElement && 'td' === $cell->tagName ) {
                    $cell->setAttribute( 'role', 'cell' );
                    $cell->setAttribute( 'data-label', $labels[ $column ] ?? '' ); ++$column;
                }
            }
        }
    }
    $result = '';
    foreach ( $root->childNodes as $node ) { $result .= $doc->saveHTML( $node ); }
    return $result;
}, 30 );
