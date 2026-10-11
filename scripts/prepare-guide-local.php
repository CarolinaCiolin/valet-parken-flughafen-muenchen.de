<?php
/** Prepare the guide review and suppress only the untouched WordPress sample in local previews. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
$sample = get_post( 1 );
if ( $sample && 'post' === $sample->post_type && 'hello-world' === $sample->post_name && 'Hello world!' === $sample->post_title && str_contains( $sample->post_content, 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!' ) ) {
    wp_update_post( array( 'ID' => 1, 'post_status' => 'draft' ) );
}
$guide = get_page_by_path( 'ratgeber' );
if ( ! $guide ) { throw new RuntimeException( 'Expected guide page.' ); }
echo 'Valet-Ratgeber: ' . $guide->post_status . ', ID ' . $guide->ID . '; keine Veröffentlichung oder Menüänderung.' . PHP_EOL;
