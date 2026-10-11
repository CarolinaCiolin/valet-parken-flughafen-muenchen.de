<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main" tabindex="-1" class="vpm-shell vpm-posts">
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'archive' ) ) : ?>
    <h1><?php if ( is_home() ) { $page = get_option( 'page_for_posts' ); echo esc_html( $page ? get_the_title( $page ) : get_bloginfo( 'name' ) ); } else { the_archive_title(); } ?></h1>
    <?php while ( have_posts() ) : the_post(); ?>
    <article <?php post_class( 'vpm-post-card' ); ?>>
        <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'large' ); ?></a><?php endif; ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php the_excerpt(); ?>
    </article>
    <?php endwhile; the_posts_pagination(); ?>
<?php endif; ?>
</main>
<?php get_footer(); ?>
