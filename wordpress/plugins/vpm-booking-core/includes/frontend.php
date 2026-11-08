<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Frontend {
    public static function locale( array $a ): string { $fallback = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : substr( get_locale(), 0, 2 ); $chosen = $a['locale'] ?? 'auto'; return ( $chosen === 'auto' || $chosen === '' ? $fallback : $chosen ) === 'en' ? 'en' : 'de'; }
    public static function text( string $locale, string $de, string $en ): string { return $locale === 'en' ? $en : $de; }
    public static function preview(): bool { return is_admin() || is_preview() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) || isset( $_GET['elementor-preview'] ); }
    public static function register(): void {
        $url = plugins_url( '', dirname( __DIR__ ) . '/vpm-booking-core.php' );
        wp_register_style( 'vpm-booking-front', $url . '/assets/front.css', array(), '0.7.0' );
        wp_register_script( 'vpm-booking-front', $url . '/assets/front.js', array(), '0.7.0', array( 'strategy' => 'defer', 'in_footer' => true ) );
        wp_register_script( 'vpm-booking-block-editor', $url . '/assets/block-editor.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ), '0.7.0', true );
        foreach ( array( 'booking-form' => 'form', 'airport-selector' => 'selector', 'newsletter' => 'newsletter' ) as $folder => $method ) { register_block_type( dirname( __DIR__ ) . '/blocks/' . $folder, array( 'render_callback' => function( $a ) use ( $method ) { return self::$method( $a ); } ) ); }
        add_shortcode( 'vpm_booking_form', array( self::class, 'form' ) );
        add_shortcode( 'vpm_airport_selector', array( self::class, 'selector' ) );
        add_shortcode( 'vpm_booking_newsletter', array( self::class, 'newsletter' ) );
    }
    public static function assets(): string {
        wp_enqueue_style( 'vpm-booking-front' ); wp_enqueue_script( 'vpm-booking-front' );
        if ( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) && ! wp_style_is( 'vpm-booking-front', 'done' ) ) { ob_start(); wp_print_styles( 'vpm-booking-front' ); return (string) ob_get_clean(); }
        return '';
    }
    public static function detect_assets(): void {
        $post = get_queried_object(); if ( ! $post instanceof \WP_Post ) { return; }
        $content = $post->post_content . ' ' . (string) get_post_meta( $post->ID, '_elementor_data', true );
        if ( preg_match( '/vpm_booking_form|vpm_airport_selector|vpm_booking_newsletter|wp:vpm-booking\//', $content ) ) { wp_enqueue_style( 'vpm-booking-front' ); wp_enqueue_script( 'vpm-booking-front' ); }
    }
    private static function options( string $locale, string $selected = '' ): string {
        $groups = array( 'DE' => self::text( $locale, 'Deutschland', 'Germany' ), 'AT' => self::text( $locale, 'Österreich', 'Austria' ), 'CH' => self::text( $locale, 'Schweiz und Grenzregion', 'Switzerland and border region' ) );
        $html = '<option value="">' . esc_html( self::text( $locale, 'Flughafen auswählen', 'Choose an airport' ) ) . '</option>';
        foreach ( $groups as $code => $label ) { $html .= '<optgroup label="' . esc_attr( $label ) . '">'; foreach ( Airports::public_list( $locale ) as $a ) { if ( $a['region'] === $code ) { $html .= '<option value="' . esc_attr( $a['code'] ) . '" ' . selected( $selected, $a['code'], false ) . '>' . esc_html( $a['name'] . ' (' . $a['code'] . ')' ) . '</option>'; } } $html .= '</optgroup>'; }
        return $html;
    }
    private static function data( string $locale ): string {
        return ' data-locale="' . esc_attr( $locale ) . '" data-api="' . esc_url( rest_url( 'vpm-booking/v1/' ) ) . '" data-preview="' . ( self::preview() ? '1' : '0' ) . '" data-analytics="' . ( get_option( 'vpm_booking_analytics_enabled', false ) ? '1' : '0' ) . '" data-analytics-target="' . esc_attr( get_option( 'vpm_booking_analytics_target', 'dataLayer' ) ) . '"';
    }
    public static function selector( $atts = array() ): string {
        $a = is_array( $atts ) ? $atts : array(); $locale = self::locale( $a ); $id = wp_unique_id( 'vpm-airport-' );
        static $anchors = array(); $anchor = sanitize_title( $a['anchor'] ?? 'flughaefen' ); $anchors[ $anchor ] = ( $anchors[ $anchor ] ?? 0 ) + 1; if ( $anchors[ $anchor ] > 1 ) { $anchor .= '-' . $anchors[ $anchor ]; }
        return self::assets() . '<section id="' . esc_attr( $anchor ) . '" class="vpm-booking-surface vpm-airport-selector"' . self::data( $locale ) . ' data-airports="' . esc_attr( wp_json_encode( Airports::public_list( $locale ) ) ) . '"><form class="vpm-airport-form"><label for="' . esc_attr( $id ) . '">' . esc_html( self::text( $locale, 'Ihr Abflughafen', 'Your departure airport' ) ) . '</label><div class="vpm-airport-controls"><select id="' . esc_attr( $id ) . '" name="airport" required>' . self::options( $locale ) . '</select><button type="submit" disabled>' . esc_html( self::text( $locale, 'Flughafen auswählen', 'Choose airport' ) ) . '</button></div></form><p class="vpm-booking-status" aria-live="polite"></p></section>';
    }
    public static function form( $atts = array() ): string {
        $a = is_array( $atts ) ? $atts : array(); $locale = self::locale( $a ); $airport = strtoupper( sanitize_text_field( $a['airport'] ?? '' ) );
        $mode = $a['airport_mode'] ?? ''; $fixed = $mode === 'fixed' || ( $mode !== 'select' && $airport !== '' );
        try { $airport_data = $airport ? Airports::get( $airport ) : null; if ( $fixed && ! $airport_data ) { throw new \RuntimeException(); } } catch ( \Throwable $e ) { return '<p role="alert">' . esc_html( self::text( $locale, 'Bitte den Flughafen dieses Formulars prüfen.', 'Please check the airport configured for this form.' ) ) . '</p>'; }
        $flow = ($a['flow']??'inline')==='steps'?'steps':'inline'; $demo = defined('VPM_BOOKING_DEMO_MODE') && VPM_BOOKING_DEMO_MODE === true && current_user_can('manage_options'); if($demo){if(!defined('DONOTCACHEPAGE'))define('DONOTCACHEPAGE',true);do_action('litespeed_control_set_nocache','Private booking demo');if(!headers_sent())nocache_headers();}
        $id = wp_unique_id( 'vpm-booking-' ); $t = function( $de, $en ) use ( $locale ) { return esc_html( self::text( $locale, $de, $en ) ); };
        $html = self::assets() . '<section class="vpm-booking-surface vpm-booking-widget"' . self::data( $locale ) . ' data-flow="' . $flow . '" data-demo-ready="' . ($demo?'1':'0') . '" data-rest-nonce="' . ($demo?esc_attr(wp_create_nonce('wp_rest')):'') . '" data-airport-mode="' . ( $fixed ? 'fixed' : 'select' ) . '" data-enabled="' . ( PublicApi::enabled() ? '1' : '0' ) . '">';
        $html .= '<form class="vpm-booking-search" method="post"><fieldset ' . ( self::preview() ? 'disabled' : '' ) . '><legend>' . $t( 'Parkangebot anfragen', 'Request airport parking' ) . '</legend><div class="vpm-booking-fields"><div>';
        if ( $fixed ) { $html .= '<span class="vpm-booking-field-label">' . $t( 'Flughafen', 'Airport' ) . '</span><strong class="vpm-booking-airport-name">' . esc_html( $airport_data['name_' . $locale] . ' (' . $airport . ')' ) . '</strong><input type="hidden" name="airport" value="' . esc_attr( $airport ) . '"><input type="hidden" name="airport_scope" value="' . esc_attr( Products::scope( $airport ) ) . '">'; }
        else { $html .= '<label for="' . $id . '-airport">' . $t( 'Flughafen', 'Airport' ) . '</label><select id="' . $id . '-airport" name="airport" required>' . self::options( $locale, $airport ) . '</select>'; }
        $html .= '</div>';
        foreach ( array( 'departure' => array( 'Fahrzeug abgeben', 'Vehicle drop-off' ), 'return' => array( 'Fahrzeug zurückerhalten', 'Vehicle collection' ) ) as $field => $label ) {
            $html .= '<div><label for="' . $id . '-' . $field . '">' . $t( $label[0], $label[1] ) . '</label><input type="datetime-local" id="' . $id . '-' . $field . '" name="' . $field . '" required><div class="vpm-time-offset" data-field="' . $field . '" hidden><label for="' . $id . '-' . $field . '-offset">' . $t( 'Uhrzeit bei Zeitumstellung', 'Time during clock change' ) . '</label><select id="' . $id . '-' . $field . '-offset" name="' . $field . '_offset"></select></div></div>';
        }
        $html .= '</div><p class="vpm-booking-timezone">' . $t( 'Alle Uhrzeiten gelten am ausgewählten Flughafen.', 'All times are local to the selected airport.' ) . '</p><button type="submit" disabled>' . $t( 'Angebote prüfen', 'Check offers' ) . '</button></fieldset></form><p class="vpm-booking-status" role="status" aria-live="polite">' . ( PublicApi::enabled() ? '' : $t( 'Die Online-Anfrage wird vorbereitet.', 'Online enquiries are being prepared.' ) ) . '</p><div class="vpm-booking-filters" hidden><label for="' . $id . '-parking">' . $t( 'Stellplatzart filtern', 'Filter parking type' ) . '</label><select id="' . $id . '-parking" class="vpm-parking-filter"><option value="">' . $t( 'Alle verfügbaren Stellplatzarten', 'All available parking types' ) . '</option></select></div><div class="vpm-booking-offers"></div>';
        $html .= '<form class="vpm-booking-request" method="post" hidden><fieldset><legend>' . $t( 'Ihre Anfrage', 'Your enquiry' ) . '</legend><div class="vpm-booking-summary" aria-live="polite"></div><div class="vpm-booking-customer-fields"><label for="' . $id . '-email">' . $t( 'E-Mail-Adresse', 'Email address' ) . '</label><input id="' . $id . '-email" type="email" name="email" autocomplete="email" maxlength="254" required><label for="' . $id . '-coupon">' . $t( 'Gutscheincode (optional)', 'Voucher code (optional)' ) . '</label><input id="' . $id . '-coupon" name="coupon" maxlength="40" autocomplete="off"><button class="vpm-coupon-apply" type="button">' . $t( 'Gutschein prüfen', 'Apply voucher' ) . '</button><p class="vpm-coupon-status" role="status" aria-live="polite"></p></div><p class="vpm-booking-disclaimer">' . $t( 'Die Anfrage ist noch keine bestätigte Buchung. Eine Zahlung wird hier nicht ausgelöst.', 'This enquiry is not a confirmed booking. No payment is taken here.' ) . '</p>';
        $privacy = get_privacy_policy_url(); if ( $privacy ) { $html .= '<p><a href="' . esc_url( $privacy ) . '">' . $t( 'Datenschutzhinweise', 'Privacy information' ) . '</a></p>'; }
        $html .= '<button type="submit" disabled>' . $t( 'Anfrage senden', 'Send enquiry' ) . '</button></fieldset></form><noscript><p>' . $t( 'Bitte aktivieren Sie JavaScript für die Online-Anfrage.', 'Please enable JavaScript for online enquiries.' ) . '</p></noscript></section>';
        return $html;
    }
    public static function newsletter( $a = array() ): string {
        $locale = self::locale( is_array( $a ) ? $a : array() ); $form = Consent::form();
        if ( $locale === 'en' ) { $form = str_replace( array( 'Die Newsletter-Anmeldung ist noch nicht eingerichtet.', 'Anmeldung anfordern', '<label>E-Mail ' ), array( 'Newsletter signup is not yet configured.', 'Request signup', '<label>Email ' ), $form ); }
        if ( self::preview() ) { $form = '<fieldset disabled>' . $form . '</fieldset>'; }
        return self::assets() . '<div class="vpm-booking-surface vpm-booking-newsletter"' . self::data( $locale ) . '>' . $form . '</div>';
    }
    public static function admin_save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        if ( isset( $_POST['vpm_booking_compat_settings'] ) ) {
            check_admin_referer( 'vpm_booking_compat_settings' );
            update_option( 'vpm_booking_frontend_enabled', isset( $_POST['frontend_enabled'] ), false );
            update_option( 'vpm_booking_analytics_enabled', isset( $_POST['analytics_enabled'] ), false );
            $target = sanitize_key( is_string( $_POST['analytics_target'] ?? null ) ? $_POST['analytics_target'] : '' ); update_option( 'vpm_booking_analytics_target', in_array( $target, array( 'gtag', 'dataLayer', 'datalayer', 'dom' ), true ) ? ( $target === 'datalayer' ? 'dataLayer' : $target ) : 'dataLayer', false );
        }
        if ( isset( $_POST['vpm_booking_airports_settings'] ) ) {
            check_admin_referer( 'vpm_booking_airports_settings' );
            try { $json = is_string( $_POST['airports_json'] ?? null ) ? wp_unslash( $_POST['airports_json'] ) : ''; update_option( 'vpm_booking_airports', Airports::validate( json_decode( $json, true ) ), false ); }
            catch ( \Throwable $e ) { $GLOBALS['vpm_airport_error'] = true; }
        }
    }
    public static function admin(): void {
        echo '<h2>Einbettung und Messereignisse</h2><p>Gutenberg-Blöcke unter „VPM“ oder Elementor-Shortcode-Widget: <code>[vpm_airport_selector]</code>, <code>[vpm_booking_form airport="FRA"]</code>, <code>[vpm_booking_newsletter]</code>. Buchungsanfragen benötigen einen installierten Anbieteradapter; ohne Adapter werden keine Angebote erfunden.</p><form method="post">'; wp_nonce_field( 'vpm_booking_compat_settings' );
        echo '<p><label><input type="checkbox" name="frontend_enabled" ' . checked( PublicApi::enabled(), true, false ) . '> Öffentliche Angebots-/Anfrageschnittstelle bewusst aktivieren (keine Sofortbuchung/Zahlung)</label></p><p><label><input type="checkbox" name="analytics_enabled" ' . checked( get_option( 'vpm_booking_analytics_enabled', false ), true, false ) . '> Statistik-Ereignisse vorbereiten; Versand nur bei bestätigter Complianz-Statistikzustimmung</label></p><label>Vorhandene Messanbindung <select name="analytics_target">';
        foreach ( array( 'dataLayer' => 'dataLayer (z. B. GTM)', 'gtag' => 'vorhandenes gtag (z. B. HFCM)', 'dom' => 'DOM-Ereignis für eigenen Adapter' ) as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( get_option( 'vpm_booking_analytics_target', 'dataLayer' ), $key, false ) . '>' . esc_html( $label ) . '</option>'; }
        echo '</select></label>'; submit_button( 'Einbettung speichern', 'secondary', 'vpm_booking_compat_settings' ); echo '</form><h2>Flughafenauswahl</h2><p>15 bearbeitbare Startwerte für Deutschland, Österreich und Schweiz/Grenzregion. Keine Rangliste und keine Zusage vorhandener Partner. page_id verweist nur auf eigene WordPress-Seiten; fehlende/unveröffentlichte Übersetzungen werden nicht als Link ausgegeben. Basel/EuroAirport liegt in Frankreich.</p>';
        if ( ! empty( $GLOBALS['vpm_airport_error'] ) ) { echo '<p role="alert">Ungültiger Flughafenkatalog; bisherige Werte bleiben erhalten.</p>'; }
        echo '<form method="post">'; wp_nonce_field( 'vpm_booking_airports_settings' ); echo '<textarea name="airports_json" class="large-text code" rows="20">' . esc_textarea( wp_json_encode( Airports::all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . '</textarea>'; submit_button( 'Flughäfen speichern', 'secondary', 'vpm_booking_airports_settings' ); echo '</form>';
    }
}
