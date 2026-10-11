<?php
/** Synthetic fixture for local plugin tests only; no real tracking or legal declarations. */
require '/var/www/html/wp-load.php';
if ( '1' !== getenv( 'VPM_LOCAL_SETUP' ) || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Local-only guard.' ); }
wp_set_current_user( 1 );
if ( 'disable' === ( $argv[1] ?? '' ) ) {
    $backup = get_option( 'vpm_local_consent_backup' );
    if ( is_array( $backup ) ) {
        update_option( 'cmplz_options', $backup['options'] );
        if ( false === $backup['completed'] ) { delete_option( 'cmplz_wizard_completed_once' ); }
        else { update_option( 'cmplz_wizard_completed_once', $backup['completed'] ); }
        delete_option( 'vpm_local_consent_backup' );
    }
    delete_option( 'vpm_local_compatibility_fixture' );
    echo 'Synthetic consent fixture disabled.' . PHP_EOL;
    return;
}
if ( ! class_exists( 'COMPLIANZ' ) || ! class_exists( 'RankMath' ) ) { throw new RuntimeException( 'Activate the pinned local plugin snapshots first.' ); }
if ( false === get_option( 'vpm_local_consent_backup' ) ) {
    update_option( 'vpm_local_consent_backup', array( 'options' => get_option( 'cmplz_options', array() ), 'completed' => get_option( 'cmplz_wizard_completed_once', false ) ) );
}
$options = array_merge( get_option( 'cmplz_options', array() ), array( 'regions' => array( 'eu' ), 'compile_statistics' => 'yes', 'enable_cookie_banner' => 'yes' ) );
update_option( 'cmplz_options', $options );
update_option( 'cmplz_wizard_completed_once', true );
update_option( 'vpm_local_compatibility_fixture', true );
cmplz_install_cookiebanner_table( true );
if ( ! cmplz_get_cookiebanners() ) { $banner = new CMPLZ_COOKIEBANNER(); $banner->save(); }
foreach ( cmplz_get_cookiebanners() as $row ) { $banner = new CMPLZ_COOKIEBANNER( $row->ID ); $banner->save(); }
echo 'Synthetic consent fixture enabled, without real tracking.' . PHP_EOL;
