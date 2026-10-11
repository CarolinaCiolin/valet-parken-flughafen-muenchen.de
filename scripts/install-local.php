<?php
/** Only for the isolated disposable Docker test installation. Never upload to a host. */
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) ) { throw new RuntimeException( 'Local-only setup guard.' ); }
define( 'WP_INSTALLING', true );
define( 'WP_HOME', 'http://127.0.0.1:8088' );
define( 'WP_SITEURL', 'http://127.0.0.1:8088' );
$_SERVER['HTTP_HOST'] = '127.0.0.1:8088';
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_blog_installed() ) {
    wp_install( 'Valet-Parken Flughafen München', 'vpm_local_admin', 'local@example.invalid', false, '', trim( file_get_contents( '/tmp/vpm-local-admin-password' ) ) );
}
update_option( 'blog_public', 0 );
update_option( 'home', 'http://127.0.0.1:8088' );
update_option( 'siteurl', 'http://127.0.0.1:8088' );
update_option( 'timezone_string', 'Europe/Berlin' );
// The isolated container cannot fetch WordPress language packs through this proxy.
// Declare the actual frontend language without fabricating translation files.
$config_path = ABSPATH . 'wp-config.php';
$config = file_get_contents( $config_path );
if ( ! defined( 'WPLANG' ) ) {
    $config = preg_replace( '/<\?php/', "<?php\ndefine( 'WPLANG', 'de_DE' );", $config, 1 );
    if ( false === file_put_contents( $config_path, $config ) ) { throw new RuntimeException( 'Local locale configuration failed.' ); }
}
update_option( 'WPLANG', 'de_DE' );
update_option( 'blogdescription', '' );
update_option( 'permalink_structure', '/%postname%/' );
switch_theme( 'vpm-hybrid' );
$result = activate_plugin( 'vpm-providers/vpm-providers.php' );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
echo 'WordPress ' . get_bloginfo( 'version' ) . ', PHP ' . PHP_VERSION . ', Theme und Anbieter-Plugin aktiviert; Indexierung deaktiviert.' . PHP_EOL;
