<?php
/** Site-specific presentation settings; never copies or publishes content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_profile_preset( $name ) {
    if ( ! in_array( $name, array( 'munich', 'frankfurt', 'umbrella' ), true ) ) { return array(); }
    static $profiles = array();
    if ( ! isset( $profiles[ $name ] ) ) { $profiles[ $name ] = json_decode( file_get_contents( get_theme_file_path( 'profiles/' . $name . '.json' ) ), true ) ?: array(); }
    return $profiles[ $name ];
}
function vpm_is_munich_installation() {
    $host = strtolower( (string) wp_parse_url( get_option( 'home' ), PHP_URL_HOST ) );
    if ( in_array( $host, array( 'valet-parken-flughafen-muenchen.de', 'www.valet-parken-flughafen-muenchen.de' ), true ) ) { return true; }
    // Preserve existing installations copied to development/staging without changing DB options.
    $front_id = (int) get_option( 'page_on_front' );
    if ( function_exists( 'pll_get_post' ) ) { $front_id = pll_get_post( $front_id, 'de' ) ?: $front_id; }
    $front = get_post( $front_id );
    return $front && 'startseite' === get_post_meta( $front->ID, '_vpm_import_slug', true ) && str_contains( $front->post_content, 'Flughafen München' );
}
function vpm_site_profile() {
    $profile = get_option( 'vpm_site_profile', array() );
    if ( is_array( $profile ) && ! empty( $profile['version'] ) ) { return $profile; }
    if ( vpm_is_munich_installation() ) { return vpm_profile_preset( 'munich' ); }
    $neutral = vpm_profile_preset( 'umbrella' ); $neutral['id'] = 'unconfigured';
    $neutral['brand_title'] = get_bloginfo( 'name' ); $neutral['brand_subtitle'] = '';
    return $neutral;
}
function vpm_legacy_munich() { return ! empty( vpm_site_profile()['legacy_munich'] ); }
function vpm_profile_text( $key ) {
    $p = vpm_site_profile(); return (string) ( $p[ $key . ( vpm_theme_english() ? '_en' : '_de' ) ] ?? '' );
}
function vpm_profile_path( $role, $lang = '' ) {
    $p = vpm_site_profile(); $lang = $lang ?: ( vpm_theme_english() ? 'en' : 'de' );
    return (string) ( $p['paths'][ $lang ][ $role ] ?? '' );
}
function vpm_profile_brand() {
    $p = vpm_site_profile();
    echo '<span class="vpm-wordmark-name">' . esc_html( $p['brand_title'] ) . '</span>';
    if ( ! empty( $p['brand_subtitle'] ) ) { echo '<span class="vpm-wordmark-city">' . esc_html( $p['brand_subtitle'] ) . '</span>'; }
}
function vpm_validate_profile( $input ) {
    if ( ! is_array( $input ) || 1 !== ( $input['version'] ?? 0 ) || ! in_array( $input['kind'] ?? '', array( 'airport', 'umbrella' ), true ) ) { return new WP_Error( 'profile', 'Ungültiges Profilformat.' ); }
    if ( ! is_bool( $input['legacy_munich'] ?? false ) ) { return new WP_Error( 'profile', 'legacy_munich muss true oder false sein.' ); }
    $result = array( 'version' => 1, 'kind' => $input['kind'], 'legacy_munich' => ! empty( $input['legacy_munich'] ) );
    foreach ( array( 'id', 'brand_title', 'brand_subtitle', 'airport_de', 'airport_en', 'service_de', 'service_en', 'cta_de', 'cta_en', 'cta_anchor' ) as $key ) {
        if ( isset( $input[ $key ] ) && ! is_string( $input[ $key ] ) ) { return new WP_Error( 'profile', 'Profilfelder müssen Text enthalten.' ); }
        $result[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
    }
    if ( ! preg_match( '/^[a-z][a-z0-9_-]*$/D', $result['cta_anchor'] ) || ! $result['cta_de'] || ! $result['cta_en'] ) { return new WP_Error( 'profile', 'Gültigen CTA-Anker und deutsche/englische Buttontexte angeben.' ); }
    if ( $result['legacy_munich'] && ( 'munich' !== $result['id'] || 'airport' !== $result['kind'] ) ) { return new WP_Error( 'profile', 'Münchner Bestandsschutz ist nur im München-Profil zulässig.' ); }
    if ( 'umbrella' === $result['kind'] && ( $result['airport_de'] || $result['airport_en'] ) ) { return new WP_Error( 'profile', 'Ein Dachmarken-Profil darf nicht pauschal einen einzelnen Flughafen behaupten.' ); }
    if ( ! $result['brand_title'] || ! $result['service_de'] || ! $result['service_en'] || ( 'airport' === $result['kind'] && ( ! $result['airport_de'] || ! $result['airport_en'] ) ) ) { return new WP_Error( 'profile', 'Marke, Servicebezeichnungen und beim Standortprofil beide Flughafenbezeichnungen sind erforderlich.' ); }
    if ( ! is_array( $input['paths'] ?? null ) ) { return new WP_Error( 'profile', 'Seitenpfade müssen als Objekt vorliegen.' ); }
    foreach ( array( 'de', 'en' ) as $lang ) {
        if ( ! is_array( $input['paths'][ $lang ] ?? null ) ) { return new WP_Error( 'profile', 'Seitenpfade für beide Sprachen angeben.' ); }
        foreach ( array( 'prices', 'safety', 'about', 'guide' ) as $role ) {
            $path = $input['paths'][ $lang ][ $role ] ?? '';
            if ( ! is_string( $path ) || ! preg_match( '~^/(?!/)[a-zA-Z0-9/_-]+/$~D', $path ) ) { return new WP_Error( 'profile', 'Seitenpfade müssen lokale Pfade mit abschließendem / sein, z. B. /preise/.' ); }
            $result['paths'][ $lang ][ $role ] = $path;
        }
    }
    return $result;
}
add_action( 'admin_menu', function() {
    $hook = add_theme_page( 'Flughafenparken-Profil', 'Flughafenparken-Profil', 'manage_options', 'vpm-site-profile', function() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $error = ''; $saved = false;
        if ( isset( $_POST['vpm_profile_save'] ) || isset( $_POST['vpm_profile_preset'] ) ) {
            check_admin_referer( 'vpm_site_profile' );
            $preset = isset( $_POST['preset'] ) && is_string( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '';
            $json = isset( $_POST['profile_json'] ) && is_string( $_POST['profile_json'] ) ? wp_unslash( $_POST['profile_json'] ) : '';
            $input = isset( $_POST['vpm_profile_preset'] ) ? vpm_profile_preset( $preset ) : json_decode( $json, true );
            $validated = vpm_validate_profile( $input );
            if ( is_wp_error( $validated ) ) { $error = $validated->get_error_message(); }
            else { update_option( 'vpm_site_profile', $validated, false ); do_action( 'litespeed_purge_all' ); $saved = true; }
        }
        echo '<div class="wrap"><h1>Flughafenparken-Profil</h1><p>Gemeinsames Theme, eigene Standortdaten. Änderungen betreffen Wortmarke, Service-Markup und technische Pfade. Bestehende Texte, Anbieter, Links, Menüs, Startseiten und Veröffentlichungsstatus werden nicht umgeschrieben. Die Beispielprofile erstellen keine fertige Website.</p>';
        if ( $error ) { echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>'; }
        if ( $saved ) { echo '<div class="notice notice-success"><p>Profil gespeichert. Inhalte und Sprachverknüpfungen unverändert.</p></div>'; }
        echo '<form method="post">'; wp_nonce_field( 'vpm_site_profile' );
        echo '<label for="vpm-preset">Vorlage bewusst übernehmen: </label><select id="vpm-preset" name="preset"><option value="munich">München – bestehender Standort</option><option value="frankfurt">Frankfurt – technische Vorlage</option><option value="umbrella">valet-parkservice.com – Dachmarken-Vorlage</option></select>';
        submit_button( 'Vorlagenprofil übernehmen', 'secondary', 'vpm_profile_preset' ); echo '</form><hr><form method="post">'; wp_nonce_field( 'vpm_site_profile' );
        echo '<label for="vpm-profile-json">Eigenes Profil bearbeiten (JSON):</label><textarea id="vpm-profile-json" name="profile_json" class="large-text code" rows="26">' . esc_textarea( wp_json_encode( vpm_site_profile(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</textarea>';
        submit_button( 'Eigenes Profil speichern', 'primary', 'vpm_profile_save' ); echo '</form><p>WordPress-Seitentitel, Logo und Website-Icon über die normalen WordPress-Einstellungen pflegen. Für neue Projekte separate Inhalte/Anbieter und eigene Affiliate-Kennungen verwenden.</p></div>';
    } );
    add_action( 'load-' . $hook, function() {
        if ( current_user_can( 'manage_options' ) && ( isset( $_POST['vpm_profile_save'] ) || isset( $_POST['vpm_profile_preset'] ) ) ) { check_admin_referer( 'vpm_site_profile' ); }
    } );
} );
