<?php
/**
 * Title: Einstieg mit austauschbarem Bild
 * Slug: vpm/hero
 * Categories: vpm
 */
?>
<!-- wp:group {"align":"full","className":"vpm-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull vpm-hero"><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide">
<!-- wp:column {"width":"55%"} --><div class="wp-block-column" style="flex-basis:55%">
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">[Freigegebene H1 einsetzen]</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>[Freigegebenen Einleitungstext unverändert einsetzen]</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">[Freigegebener CTA]</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"45%"} --><div class="wp-block-column" style="flex-basis:45%"><!-- wp:image {"sizeSlug":"full","className":"vpm-hero-image"} --><figure class="wp-block-image size-full vpm-hero-image"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/valet-hero.webp' ) ); ?>" alt="Reisende mit Koffer im Gespräch mit einem Valet-Mitarbeiter vor einer Flughafenarchitektur"/><figcaption class="wp-element-caption">KI-generierte Illustration</figcaption></figure><!-- /wp:image --></div><!-- /wp:column -->
</div><!-- /wp:columns --></div><!-- /wp:group -->
