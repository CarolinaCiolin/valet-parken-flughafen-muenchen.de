<?php
/** Read-only troubleshooting; never enables tracking or overrides consent settings. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function vpm_consent_diagnostics() {
    $active = function_exists( 'cmplz_get_option' ) && class_exists( 'COMPLIANZ' );
    $report = array( 'Theme' => wp_get_theme()->get( 'Version' ), 'Complianz aktiv' => $active ? 'ja' : 'nein', 'Assistent einmal abgeschlossen' => get_option( 'cmplz_wizard_completed_once' ) ? 'ja' : 'nein' );
    if ( $active ) {
        $report['Complianz-Version'] = defined( 'CMPLZ_VERSION' ) ? CMPLZ_VERSION : 'unbekannt';
        $report['Complianz benötigt laut Konfiguration Banner'] = COMPLIANZ::$banner_loader->site_needs_cookie_warning() ? 'ja' : 'nein';
        foreach ( array( 'enable_cookie_banner', 'regions', 'compile_statistics', 'uses_ad_cookies', 'uses_firstparty_marketing_cookies', 'uses_thirdparty_services', 'uses_social_media', 'respect_dnt' ) as $key ) {
            $value = cmplz_get_option( $key ); $report[ $key ] = is_array( $value ) ? implode( ', ', array_map( 'sanitize_text_field', $value ) ) : (string) $value;
        }
        if ( function_exists( 'cmplz_get_cookiebanner' ) ) { $banner = cmplz_get_cookiebanner(); $report['Banner separat deaktiviert'] = $banner->disable_cookiebanner ? 'ja' : 'nein'; }
        $report['Cookie-Richtlinie (aktuelle Admin-Sprache)'] = cmplz_get_document_url( 'cookie-statement', 'eu' );
        $report['Ausgeschlossene Seiten konfiguriert'] = get_option( 'cmplz_excluded_posts_array' ) ? 'ja' : 'nein';
    }
    $locations = get_nav_menu_locations(); $report['Footer-Menü-ID (aktuelle Admin-Sprache)'] = (string) ( $locations['footer'] ?? 0 );
    if ( function_exists( 'pll_languages_list' ) ) {
        $options = get_option( 'polylang', array() );
        foreach ( pll_languages_list() as $lang ) { $report[ 'Footer-Menü-ID ' . $lang ] = (string) ( $options['nav_menus'][ get_stylesheet() ]['footer'][ $lang ] ?? 0 ); }
    }
    return $report;
}
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( 'tools_page_vpm-cookie-diagnostics' !== $hook || ! current_user_can( 'manage_options' ) ) { return; }
    wp_enqueue_script( 'vpm-consent-diagnostics', get_theme_file_uri( 'assets/consent-diagnostics.js' ), array(), wp_get_theme()->get( 'Version' ), true );
} );
add_action( 'admin_menu', function() {
    add_management_page( 'VPM Cookie-Diagnose', 'VPM Cookie-Diagnose', 'manage_options', 'vpm-cookie-diagnostics', function() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        echo '<div class="wrap"><h1>VPM Cookie-Diagnose</h1><p>Nur lesend: verändert keine Einstellungen, Menüs oder Einwilligungen. Diese Angaben helfen, Konfiguration und Sprachmenüs zu unterscheiden. Sie beweisen noch nicht, dass der Banner im Browser sichtbar ist.</p><table class="widefat striped"><tbody>';
        foreach ( vpm_consent_diagnostics() as $label => $value ) { echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>'; }
        echo '</tbody></table><h2>Startseite im Browser prüfen</h2><p>Dieser Test lädt die Startseite in einer eingebetteten Ansicht und liest ihren tatsächlichen Zustand. Die normale Startseite kann dabei wie bei jedem Seitenaufruf Cookies setzen; der Test selbst löscht keine Cookies, erteilt keine Einwilligung und ruft keine Banner-Erzwingung auf. Die angemeldete Sitzung und der Testparameter können den anonymen Seitencache umgehen. Das Ergebnis gilt zunächst für diesen Browser und diese Sitzung.</p><p><button type="button" class="button button-primary" id="vpm-consent-start" data-url="' . esc_url( trailingslashit( get_option( 'home' ) ) ) . '">Startseite im Browser prüfen</button> <button type="button" class="button" id="vpm-consent-refresh" disabled>Erneut auslesen</button> <button type="button" class="button" id="vpm-consent-copy" disabled>Bericht kopieren</button></p><textarea id="vpm-consent-output" class="large-text code" rows="18" readonly aria-label="Browser-Diagnosebericht"></textarea><iframe id="vpm-consent-frame" title="Startseite für Cookie-Diagnose" style="width:100%;height:720px;border:1px solid #ccc" hidden></iframe>';
        echo '<p>Complianz benötigt einen abgeschlossenen Assistenten und einen laut Konfiguration erforderlichen Banner. Aktivierung allein reicht nicht. Keine ungenutzten Dienste angeben, um einen Banner zu erzwingen. Bei erteilter Auswahl, Do Not Track / Global Privacy Control, Vorschau oder Seitenausschlüssen kann die Anzeige zusätzlich abweichen.</p><p>Menüs: Cookie-Richtlinie dem tatsächlich zugewiesenen deutschen beziehungsweise englischen Footermenü zuordnen. Danach LiteSpeed-Cache leeren. Zum Testen bei fehlender Anzeige JS-Verzögerung und JS-Zusammenfassung in LiteSpeed vorübergehend ausschalten; vor dem Test bestehende Einstellungen notieren.</p></div>';
    } );
} );
