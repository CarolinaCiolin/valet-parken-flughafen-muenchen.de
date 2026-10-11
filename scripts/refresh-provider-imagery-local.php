<?php
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
wp_set_current_user( 1 );
$result = vpm_add_provider_symbol_images( null, true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
echo (int) $result . ' verwaltete Symbolbilder aktualisiert.' . PHP_EOL;
