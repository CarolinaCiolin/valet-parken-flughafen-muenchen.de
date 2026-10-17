<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" tabindex="-1" class="vpm-page">
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) : ?>
<?php while ( have_posts() ) : the_post(); ?>
    <article <?php post_class(); ?>>

        <header class="vpm-page-heading vpm-shell"><h1><?php the_title(); ?></h1></header>
        <?php if ( has_post_thumbnail() ) : ?><div class="vpm-featured vpm-shell"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
        <div class="vpm-content entry-content"><?php the_content(); ?></div>
        <?php wp_link_pages(); ?>
    </article>
<?php endwhile; ?>
<?php endif; ?>
</main>
<?php get_footer(); ?>
