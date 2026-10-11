<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) : ?>
<footer class="vpm-footer"><div class="vpm-shell vpm-footer-inner">
    <a class="vpm-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
    <?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => 'nav', 'container_aria_label' => 'Footernavigation', 'fallback_cb' => false, 'menu_class' => 'vpm-menu' ) ); ?>
</div>
<?php $note = get_theme_mod( 'vpm_footer_note' ); if ( $note ) : ?>
<p class="vpm-shell vpm-footer-note"><?php echo esc_html( $note ); ?></p>
<?php endif; ?>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body></html>
