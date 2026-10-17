<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) : ?>
<footer class="vpm-footer"><div class="vpm-shell vpm-footer-inner">
    <a class="vpm-brand vpm-wordmark" href="<?php echo esc_url( vpm_theme_home() ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><span class="vpm-wordmark-name">VALET PARKEN</span><span class="vpm-wordmark-city">MÜNCHEN</span></a>
    <div class="vpm-footer-navigation">
    <?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => 'nav', 'container_aria_label' => vpm_theme_text( 'Footernavigation', 'Footer navigation' ), 'fallback_cb' => false, 'menu_class' => 'vpm-menu' ) ); ?>
    <?php $legal_pages = vpm_footer_legal_pages(); if ( $legal_pages ) : ?>
    <nav aria-label="<?php echo esc_attr( vpm_theme_text( 'Rechtliche Hinweise', 'Legal information' ) ); ?>"><ul class="vpm-menu">
    <?php foreach ( $legal_pages as $legal ) : ?><li><a href="<?php echo esc_url( $legal['url'] ); ?>"><?php echo esc_html( $legal['label'] ); ?></a></li><?php endforeach; ?>
    </ul></nav>
    <?php endif; ?>
    </div>
</div>
<?php $note = vpm_theme_english() ? 'Some links on this website are affiliate links. If you make a booking through them, we may receive a commission from the booking partner. This does not increase the price you pay.' : get_theme_mod( 'vpm_footer_note' ); if ( $note ) : ?>
<p class="vpm-shell vpm-footer-note"><?php echo esc_html( $note ); ?></p>
<?php endif; ?>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body></html>
