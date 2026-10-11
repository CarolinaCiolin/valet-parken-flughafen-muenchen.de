<?php
/** Presentation only: original text nodes are retained; approved navigation changes are scoped to the homepage. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function vpm_editorial_figure( $doc, $scene, $above_fold = false ) {
    $path = 'assets/images/' . $scene . '.webp';
    if ( ! is_readable( get_theme_file_path( $path ) ) ) { return null; }
    $figure = $doc->createElement( 'figure' );
    $figure->setAttribute( 'class', 'vpm-editorial-image' );
    $image = $doc->createElement( 'img' );
    $image->setAttribute( 'src', get_theme_file_uri( $path ) );
    $sources = array();
    foreach ( array( 480, 800, 1200 ) as $width ) {
        $file = 'assets/images/' . $scene . ( 1200 === $width ? '' : '-' . $width ) . '.webp';
        $sources[] = get_theme_file_uri( $file ) . ' ' . $width . 'w';
    }
    $image->setAttribute( 'srcset', implode( ', ', $sources ) );
    $image->setAttribute( 'sizes', '(max-width: 760px) calc(100vw - 40px), (max-width: 1240px) calc((100vw - 104px) / 2), 560px' );
    $image->setAttribute( 'width', '1200' ); $image->setAttribute( 'height', '675' );
    $image->setAttribute( 'alt', 'KI-Symbolbild einer Valet-Parking-Szene am Flughafen' );
    $image->setAttribute( 'loading', $above_fold ? 'eager' : 'lazy' ); $image->setAttribute( 'decoding', 'async' );
    $figure->appendChild( $image );
    $figure->appendChild( $doc->createElement( 'figcaption', 'KI-Symbolbild · keine Anbieteraufnahme' ) );
    return $figure;
}

add_filter( 'the_content', function ( $html ) {
    if ( ! is_page() || ! in_the_loop() || ! is_main_query() || false === strpos( $html, 'wp-block-heading' ) || ! class_exists( 'DOMDocument' ) ) { return $html; }
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="UTF-8"><div id="vpm-render-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors(); libxml_use_internal_errors( $previous );
    $root = $doc->getElementById( 'vpm-render-root' );
    if ( ! $root ) { return $html; }
    $nodes = iterator_to_array( $root->childNodes );
    $section = null; $inner = null; $grid = null; $card = null; $index = 0; $article_note = null;
    $slug = get_post_field( 'post_name', get_the_ID() );
    $scene = array( 'preise' => 'arrival', 'valet-parken-sicherheit' => 'inspection', 'ueber-uns' => 'handover' )[ $slug ] ?? null;
    $intro = null; $intro_copy = null;
    if ( $scene ) {
        $intro = $doc->createElement( 'div' ); $intro->setAttribute( 'class', 'alignfull vpm-subpage-intro' );
        $intro_copy = $doc->createElement( 'div' ); $intro_copy->setAttribute( 'class', 'vpm-intro-copy' ); $intro->appendChild( $intro_copy );
        $root->insertBefore( $intro, $root->firstChild );
        $figure = vpm_editorial_figure( $doc, $scene, true ); if ( $figure ) { $intro->appendChild( $figure ); }
    }
    foreach ( $nodes as $node ) {
        if ( 'valet-parken-sicherheit' === $slug && $node instanceof DOMElement && 'p' === $node->tagName && str_starts_with( trim( $node->textContent ), 'Die Angaben beruhen auf den veröffentlichten Angebotsbeschreibungen' ) ) {
            $article_note = $node;
            continue;
        }
        if ( $node instanceof DOMElement && 'h2' === $node->tagName ) {
            $section = $doc->createElement( 'section' );
            $section->setAttribute( 'class', 'alignfull vpm-editorial-section vpm-section-' . ++$index );
            $root->insertBefore( $section, $node );
            $inner = $doc->createElement( 'div' ); $inner->setAttribute( 'class', 'vpm-section-inner' ); $section->appendChild( $inner );
            $grid = null; $card = null;
            if ( 'startseite' === $slug && str_starts_with( trim( $node->textContent ), 'Das sind wir:' ) ) { $section->setAttribute( 'class', $section->getAttribute( 'class' ) . ' vpm-about-panel' ); }
        }
        if ( $section ) {
            if ( $node instanceof DOMElement && 'h3' === $node->tagName ) {
                if ( ! $grid ) {
                    $grid = $doc->createElement( 'div' );
                    $grid->setAttribute( 'class', preg_match( '/^\d\s/', trim( $node->textContent ) ) ? 'vpm-editorial-grid vpm-process-grid' : 'vpm-editorial-grid' );
                    $inner->appendChild( $grid );
                }
                $card = $doc->createElement( 'div' ); $card->setAttribute( 'class', 'vpm-editorial-card' ); $grid->appendChild( $card );
            }
            ( $card ?: $inner )->appendChild( $node );
        } elseif ( $intro_copy ) {
            $intro_copy->appendChild( $node );
        } elseif ( $node instanceof DOMElement && 'ul' === $node->tagName ) {
            $node->setAttribute( 'class', $node->getAttribute( 'class' ) . ' alignwide vpm-benefits' );
        }
    }
    foreach ( iterator_to_array( $root->getElementsByTagName( 'section' ) ) as $area ) {
        if ( str_contains( $area->getAttribute( 'class' ), 'vpm-about-panel' ) ) {
            $figure = vpm_editorial_figure( $doc, 'return' );
            if ( $figure ) { $area->appendChild( $figure ); }
        }
        $secondary_scene = null;
        $area_class = $area->getAttribute( 'class' );
        if ( 'preise' === $slug && str_contains( $area_class, 'vpm-section-2' ) ) { $secondary_scene = 'handover'; }
        if ( 'valet-parken-sicherheit' === $slug && str_contains( $area_class, 'vpm-section-3' ) ) { $secondary_scene = 'arrival'; }
        if ( 'ueber-uns' === $slug && str_contains( $area_class, 'vpm-section-3' ) ) { $secondary_scene = 'return'; }
        if ( $secondary_scene ) {
            $figure = vpm_editorial_figure( $doc, $secondary_scene );
            if ( $figure ) { $area->setAttribute( 'class', $area_class . ' vpm-visual-panel' ); $area->appendChild( $figure ); }
        }
    }
    foreach ( $root->getElementsByTagName( 'p' ) as $paragraph ) {
        $elements = array();
        foreach ( $paragraph->childNodes as $child ) { if ( $child instanceof DOMElement ) { $elements[] = $child; } }
        if ( 1 === count( $elements ) && 'a' === $elements[0]->tagName && trim( $paragraph->textContent ) === trim( $elements[0]->textContent ) ) {
            $paragraph->setAttribute( 'class', $paragraph->getAttribute( 'class' ) . ' vpm-action' );
        }
    }
    // Approved homepage navigation: preserve copy and stored Gutenberg blocks.
    if ( 'startseite' === $slug ) {
        $provider_area = null; $price_paragraph = null;
        foreach ( $root->getElementsByTagName( 'div' ) as $element ) {
            if ( 'anbieter' === $element->getAttribute( 'id' ) ) { $provider_area = $element; break; }
        }
        foreach ( $root->getElementsByTagName( 'a' ) as $link ) {
            if ( 'Münchener Anbieter vergleichen' === trim( $link->textContent ) ) {
                $link->setAttribute( 'href', home_url( '/preise/' ) );
            }
            if ( 'Detaillierte Preisinformationen' === trim( $link->textContent ) && 'p' === $link->parentNode->nodeName ) {
                $price_paragraph = $link->parentNode;
            }
        }
        if ( $provider_area && $price_paragraph ) {
            foreach ( $provider_area->getElementsByTagName( 'div' ) as $element ) {
                if ( str_contains( $element->getAttribute( 'class' ), 'vpm-provider-grid' ) ) {
                    $price_paragraph->setAttribute( 'class', $price_paragraph->getAttribute( 'class' ) . ' vpm-price-navigation' );
                    $element->parentNode->insertBefore( $price_paragraph, $element );
                    break;
                }
            }
            $ancestor = $provider_area->parentNode;
            while ( $ancestor instanceof DOMElement && 'section' !== $ancestor->tagName ) { $ancestor = $ancestor->parentNode; }
            if ( $ancestor instanceof DOMElement ) { $ancestor->setAttribute( 'class', $ancestor->getAttribute( 'class' ) . ' vpm-offers-section' ); }
        }
    }
    $layout_xpath = new DOMXPath( $doc );
    foreach ( $layout_xpath->query( './/div[contains(concat(" ",normalize-space(@class)," ")," vpm-editorial-grid ")]', $root ) as $layout ) {
        $cards = $layout_xpath->query( './div[contains(concat(" ",normalize-space(@class)," ")," vpm-editorial-card ")]', $layout );
        if ( 5 === $cards->length ) { $layout->setAttribute( 'class', $layout->getAttribute( 'class' ) . ' vpm-grid-five' ); }
        if ( str_contains( $layout->getAttribute( 'class' ), 'vpm-process-grid' ) ) {
            foreach ( $cards as $step ) {
                $heading = $step->getElementsByTagName( 'h3' )->item( 0 );
                if ( $heading && 1 === $heading->childNodes->length && preg_match( '/^(\d+)(\s+)(.+)$/u', $heading->textContent, $matches ) ) {
                    $number = $doc->createElement( 'span', $matches[1] ); $number->setAttribute( 'class', 'vpm-step-number' );
                    $title = $doc->createElement( 'span' ); $title->setAttribute( 'class', 'vpm-step-title' );
                    $title->appendChild( $doc->createTextNode( $matches[3] ) );
                    $heading->textContent = ''; $heading->appendChild( $number );
                    $heading->appendChild( $doc->createTextNode( $matches[2] ) ); $heading->appendChild( $title );
                }
                // This CTA applies to the entire process, rather than the last step.
                if ( 'startseite' === $slug ) {
                    foreach ( iterator_to_array( $step->getElementsByTagName( 'a' ) ) as $link ) {
                        if ( 'Verfügbare Valet-Angebote ansehen' === trim( $link->textContent ) && 'p' === $link->parentNode->nodeName ) {
                            $layout->parentNode->appendChild( $link->parentNode );
                        }
                    }
                }
            }
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
    if ( $article_note ) {
        $note = $doc->createElement( 'aside' ); $note->setAttribute( 'class', 'alignwide vpm-article-note' );
        $note->appendChild( $article_note ); $root->appendChild( $note );
    }
    // A media-conditioned picture avoids any hero-image request on mobile.
    $xpath = new DOMXPath( $doc );
    foreach ( $xpath->query( './/figure[contains(concat(" ",normalize-space(@class)," ")," vpm-hero-image ")]/img', $root ) as $image ) {
        $picture = $doc->createElement( 'picture' ); $source = $doc->createElement( 'source' );
        $source->setAttribute( 'media', '(min-width: 782px)' );
        $source->setAttribute( 'srcset', $image->getAttribute( 'srcset' ) ?: $image->getAttribute( 'src' ) );
        if ( $image->hasAttribute( 'sizes' ) ) { $source->setAttribute( 'sizes', $image->getAttribute( 'sizes' ) ); }
        $image->parentNode->insertBefore( $picture, $image ); $picture->appendChild( $source ); $picture->appendChild( $image );
        $image->setAttribute( 'src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
        $image->removeAttribute( 'srcset' ); $image->removeAttribute( 'sizes' );
    }
    $result = '';
    foreach ( $root->childNodes as $node ) { $result .= $doc->saveHTML( $node ); }
    return $result;
}, 30 );
