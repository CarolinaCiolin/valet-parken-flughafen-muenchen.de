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
<a class="vpm-skip" href="#main">Zum Inhalt springen</a>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) : ?>
<header class="vpm-header">
    <div class="vpm-shell vpm-header-inner">
        <div class="vpm-brand">
            <?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
            <?php endif; ?>
        </div>
        <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <button class="vpm-menu-toggle" aria-expanded="false" aria-controls="vpm-menu">Menü <span aria-hidden="true">☰</span></button>
        <nav id="vpm-menu" class="vpm-navigation" aria-label="Hauptnavigation">
            <?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'vpm-menu', 'fallback_cb' => false, 'depth' => 2 ) ); ?>
        </nav>
        <?php endif; ?>
        <?php $cta_url = get_theme_mod( 'vpm_cta_url' ); $cta_label = get_theme_mod( 'vpm_cta_label' ); if ( $cta_url && $cta_label ) : ?>
        <a class="vpm-button vpm-header-cta" href="<?php echo esc_url( $cta_url ); ?>" rel="sponsored nofollow"><?php echo esc_html( $cta_label ); ?></a>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
