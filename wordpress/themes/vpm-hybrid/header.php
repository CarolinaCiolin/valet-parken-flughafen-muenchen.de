<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="vpm-skip" href="#main"><?php echo esc_html( vpm_theme_text( 'Zum Inhalt springen', 'Skip to content' ) ); ?></a>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) : ?>
<header class="vpm-header">
    <div class="vpm-shell vpm-header-inner">
        <div class="vpm-brand">
            <?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
                <a class="vpm-wordmark" href="<?php echo esc_url( vpm_theme_home() ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><span class="vpm-wordmark-name">VALET PARKEN</span><span class="vpm-wordmark-city">MÜNCHEN</span></a>
            <?php endif; ?>
        </div>
        <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <button type="button" class="vpm-menu-toggle" aria-expanded="false" aria-controls="vpm-menu"><?php echo esc_html( vpm_theme_text( 'Menü', 'Menu' ) ); ?> <span aria-hidden="true">☰</span></button>
        <nav id="vpm-menu" class="vpm-navigation" aria-label="<?php echo esc_attr( vpm_theme_text( 'Hauptnavigation', 'Main navigation' ) ); ?>">
            <?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'vpm-menu', 'fallback_cb' => false, 'depth' => 2 ) ); ?>
        </nav>
        <?php endif; ?>
        <?php vpm_language_switcher(); ?>
        <?php $cta_url = vpm_theme_home() . '#anbieter'; $cta_label = vpm_theme_english() ? 'Check availability' : get_theme_mod( 'vpm_cta_label', 'Verfügbarkeit prüfen' ); if ( $cta_label ) : ?>
        <a class="vpm-button vpm-header-cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
