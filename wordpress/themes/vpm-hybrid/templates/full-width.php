<?php
/** Template Name: Volle Breite – Titel im Editor */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" tabindex="-1" class="vpm-full-width">
<?php while ( have_posts() ) : the_post(); ?>
    <article <?php post_class(); ?>><?php vpm_render_page_context(); ?><div class="vpm-content entry-content"><?php the_content(); ?></div></article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
