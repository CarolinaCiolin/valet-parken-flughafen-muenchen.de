<?php
namespace VPM\Booking;
if (!defined('ABSPATH')) exit;
/** UI boundary: decimal euros never change the internal/API integer-cent contract. */
final class Experience {
    public static function money(int $cents): string {return esc_html(number_format($cents/100,2,',','.').' €');}
    public static function euros($value): int {
        Config::require(is_string($value) && preg_match('/^\d{1,6}(?:[,.]\d{1,2})?$/D',trim($value)), 'invalid_euro_amount');
        $parts=preg_split('/[,.]/',trim($value));
        return Config::integer((int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'),0,100000000);
    }
    public static function normalize(array $d): array {
        foreach($d as $key=>$value) {
            if(is_array($value)) $d[$key]=self::normalize($value);
            elseif(str_ends_with((string)$key,'_cents')) $d[$key]=(string)self::euros($value);
        }
        return $d;
    }
    public static function errors(string $code): string {
        return array('invalid_product_label'=>'Bitte die Angebotsnamen auf Deutsch und Englisch ausfüllen.',
            'parking_height_required'=>'Bitte eine maximale Fahrzeughöhe von mindestens 0,50 m für dieses Stellplatzangebot angeben.',
            'invalid_capacity_overlap'=>'Die Teilbestände passen nicht in den Gesamtbestand. Gemeinsam genutzte barrierearme Ladeplätze korrekt angeben.',
            'extra_capacity_unavailable'=>'Für dieses Extra ist die Tageskapazität ausgeschöpft. Bitte ein anderes Datum oder Angebot wählen.',
            'mail_sender_required'=>'Bitte ein eigenes Testpostfach und einen gültigen Absender unter Branding hinterlegen.',
            'invalid_integer'=>'Bitte eine gültige ganze Zahl ab 0 eingeben.',
            'invalid_euro_amount'=>'Bitte einen Eurobetrag eingeben, z. B. 12,50. Keine Tausendertrennzeichen.',
            'capacity_below_reserved_total'=>'Der neue Bestand ist kleiner als die bereits reservierten Plätze. Bestehende Reservierungen zuerst prüfen.',
            'rate_not_owned'=>'Für dieses Stellplatzangebot fehlt ein gespeicherter Grundtarif. Bitte zuerst einen Tarif anlegen.',
            'invalid_service_time'=>'Bitte gültige Uhrzeiten für Beginn und Ende angeben.',
            'invalid_service_hours'=>'Bitte die Öffnungszeiten je Wochentag vollständig angeben.',
            'charging_exceeds_capacity'=>'Die Anzahl der Ladeplätze darf die Gesamtzahl der Plätze nicht übersteigen.',
            'portal_forbidden'=>'Für diese Aktion fehlt die Berechtigung.',
            'cancellation_confirmation_required'=>'Bitte den Storno in der Buchungsübersicht prüfen und bestätigen.',
            'cancellation_amount_changed'=>'Die Stornoberechnung hat sich geändert. Bitte die aktuellen Beträge erneut prüfen.',
            'invalid_pricing'=>'Bitte eine Preisberechnung auswählen.',
            'product_not_owned'=>'Dieses Angebot gehört nicht zum ausgewählten Partner.',
            'database_operation_failed'=>'Die Datenbank hat die Änderung abgelehnt. Fehlerkennung: database_operation_failed.',
            'database_update_failed'=>'Die Änderung konnte nicht in der Datenbank gespeichert werden. Fehlerkennung: database_update_failed.')[$code]??'Die Änderung konnte nicht gespeichert werden. Bitte die Angaben prüfen. Fehlerkennung: portal_save_failed.';
    }
    public static function customer(array $d,string $service='parking',int $limit=0): array {
        $kind=$d['kind']??'private';Config::require(in_array($kind,array('private','business'),true),'invalid_customer_kind');$out=array('kind'=>$kind);
        foreach(array('first_name','last_name','phone','plate','make','company','legal_form','vat_id','street','postcode','city','country','traveller')as $key){$v=$d[$key]??'';Config::require(is_string($v)&&strlen($v)<=180,'invalid_customer_details');$out[$key]=sanitize_text_field(trim($v));}
        foreach(array('first_name','last_name','phone','plate','make')as $key)Config::require($out[$key]!=='','missing_customer_'.$key);
        Config::require((bool)preg_match('/^[+0-9 ()\/.-]{6,40}$/D',$out['phone']),'invalid_customer_phone');$out['plate']=mb_strtoupper($out['plate']);
        if($kind==='business')foreach(array('company','legal_form','street','postcode','city','country')as $key)Config::require($out[$key]!=='','missing_customer_'.$key);
        else foreach(array('company','legal_form','vat_id','street','postcode','city','country')as $key)unset($out[$key]);
        $out['height_cm']=Commerce::number($d['height_cm']??0,0,500);$out['passengers']=$service==='shuttle'?Commerce::number($d['passengers']??'',1,$limit?:99):0;
        return $out;
    }
    public static function customer_data(array $b): array {
        $sealed=Core::snapshot($b)['customer_details']??'';return $sealed?Broker::sanitize(Config::open($sealed),$b['customer_email']):array();
    }
    public static function billing(array $c): string {
        if(($c['kind']??'')!=='business')return '';$html='<details><summary>Firmen- und Rechnungsdaten</summary>';foreach(array('company','legal_form','vat_id','street','postcode','city','country','traveller')as $key)if(!empty($c[$key]))$html.='<p>'.esc_html($c[$key]).'</p>';return $html.'</details>';
    }
    public static function number(array $b): string {return 'VPM-'.str_pad((string)$b['id'],7,'0',STR_PAD_LEFT);}
    public static function cancel_preview(array $b): void {
        Tenants::authorize((int)$b['partner_id'],true);$q=Core::snapshot($b);$c=self::customer_data($b);$cause=($GLOBALS['vpm_cancel_cause']??'cancellation');$decision=$cause==='provider'?Conditions::assessment($b,'provider'):Conditions::decision($b);$travel=$q['travel']??array();
        echo '<section class="vpm-cancel-preview"><h2>Stornierung prüfen</h2><p>'.($cause==='provider'?'Absage durch den Anbieter: keine Stornogebühr für den Kunden.':'Kundenstorno nach gespeicherten Bedingungen.').'</p><p><strong>'.esc_html(self::number($b)).' · '.esc_html(trim(($c['first_name']??'').' '.($c['last_name']??''))).' · '.esc_html($c['plate']??'').'</strong></p>';
        if($travel)echo '<p>'.esc_html(wp_date('d.m.Y H:i',$travel['departure_utc'],new \DateTimeZone($travel['timezone']))).' – '.esc_html(wp_date('d.m.Y H:i',$travel['return_utc'],new \DateTimeZone($travel['timezone']))).'</p>';
        foreach(array('basis_cents'=>'Bezahlt nach Gutschein','fee_cents'=>'Stornogebühr','refund_total_cents'=>'Erstattungsbetrag gesamt')as $key=>$label)echo '<p>'.esc_html($label).': <strong>'.esc_html(number_format($decision[$key]/100,2,',','.')).' €</strong></p>';
        echo '<p>Es gelten die bei Buchung gespeicherten Bedingungen. Bereits veranlasste Erstattungen werden berücksichtigt. Bei unbezahlten Anfragen wird kein Geld erstattet. Eine externe Anbindung muss den Storno bestätigen; ein Hintergrundauftrag ist noch kein abgeschlossener Vorgang.</p><form method="post">';wp_nonce_field('vpm_partner_portal');echo '<input type="hidden" name="portal_action" value="cancel"><input type="hidden" name="partner" value="'.(int)$b['partner_id'].'"><input type="hidden" name="reference" value="'.esc_attr($b['public_id']).'"><input type="hidden" name="cause" value="'.esc_attr($cause).'"><input type="hidden" name="expected_fee" value="'.$decision['fee_cents'].'"><input type="hidden" name="expected_refund" value="'.$decision['refund_total_cents'].'"><label><input type="checkbox" name="acknowledged" value="1" required> Stornierung und berechneten Erstattungsbetrag geprüft.</label><button>Storno verbindlich ausführen lassen</button></form></section>';
    }
    public static function partner_admin(int $id): void {
        Config::require(current_user_can('manage_options'),'permission_denied');$p=DB::row('partners','id',$id);if(!$p){echo '<p>Partner nicht gefunden.</p>';return;}
        $portal=add_query_arg(array('vpm_partner_portal'=>1,'partner'=>$id),home_url('/'));
        echo '<div class="wrap vpm-admin"><p><a href="'.esc_url(admin_url('tools.php?page=vpm-booking-operations#partners')).'">Zur Partnerübersicht</a></p><h1>'.esc_html($p['name']).' · '.esc_html($p['airport']).'</h1><p>Verwaltung ausschließlich für diesen Partner.</p>';
        if(isset($_GET['saved']))echo '<div class="notice notice-success"><p>Änderungen gespeichert.</p></div>';
        if(isset($GLOBALS['vpm_operation_error']))echo '<div class="notice notice-error"><p>'.esc_html(self::errors($GLOBALS['vpm_operation_error'])).'</p></div>';
        echo '<nav class="vpm-admin-nav">';foreach(array('overview'=>'Partnerübersicht','inventory'=>'Stellplätze','prices'=>'Parkpreise','service-times'=>'Services & Zeiten','products'=>'Angebote & Extras','bookings'=>'Buchungen','requests'=>'Stornos','statistics'=>'Statistiken')as $section=>$label)echo '<a href="'.esc_url($portal.'#'.$section).'">'.esc_html($label).'</a> · ';echo '</nav><h2>Stammdaten und Freigabe</h2><form method="post">';wp_nonce_field('vpm_booking_operations');
        echo '<input type="hidden" name="vpm_operation" value="partner"><input type="hidden" name="id" value="'.$id.'"><p><label>Partnername <input name="name" required value="'.esc_attr($p['name']).'"></label></p><p><label>Flughafen <select name="airport">';foreach(Airports::all()as $airport)echo '<option value="'.esc_attr($airport['code']).'" '.selected($p['airport'],$airport['code'],false).'>'.esc_html($airport['name_de'].' ('.$airport['code'].')').'</option>';echo '</select></label></p><p><label>Provision (100 = 1 %) <input type="number" min="0" max="10000" name="commission_bps" value="'.(int)$p['commission_bps'].'"></label></p><p><label>Stripe-Account-ID (optional) <input name="stripe_account" value="'.esc_attr($p['stripe_account']).'"></label></p><p><label><input type="checkbox" name="active" value="1" '.checked((int)$p['active'],1,false).'> Für neue Anfragen freigeben</label></p>';submit_button('Partner aktualisieren');echo '</form><h2>Partnerzugang zuordnen</h2><form method="post">';wp_nonce_field('vpm_booking_operations');echo '<input type="hidden" name="vpm_operation" value="membership"><input type="hidden" name="partner_id" value="'.$id.'"><label>WordPress-Benutzer-ID <input type="number" name="user_id" min="1" required></label><label> Rolle <select name="role"><option value="owner">Partnerinhaber</option><option value="manager">Partnermitarbeiter</option><option value="viewer">Nur Lesen</option><option value="support">Einrichtungsteam</option><option value="remove">Zugriff entziehen</option></select></label>';submit_button('Zugang zuordnen');echo '</form></div>';
    }
    public static function partner_user($user): bool {
        if(!$user instanceof \WP_User || !$user->exists() || user_can($user,'manage_options'))return false;
        global $wpdb;return (bool)$wpdb->get_var($wpdb->prepare('SELECT 1 FROM '.DB::table('memberships').' WHERE user_id=%d LIMIT 1',$user->ID));
    }
    public static function login_url(): string {return add_query_arg('vpm_partner_login',1,home_url('/'));}
    public static function register(): void {
        add_filter('login_redirect',function($redirect,$requested,$user){return self::partner_user($user)?add_query_arg('vpm_partner_portal',1,home_url('/')):$redirect;},20,3);
        add_filter('show_admin_bar',function($show){return self::partner_user(wp_get_current_user())?false:$show;});
        add_action('admin_init',function(){if(self::partner_user(wp_get_current_user())&&!wp_doing_ajax()){wp_safe_redirect(add_query_arg('vpm_partner_portal',1,home_url('/')));exit;}});
        add_action('template_redirect',function(){
            if(!isset($_GET['vpm_partner_login']))return;
            nocache_headers();header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: no-referrer');header('X-Frame-Options: DENY');
            if(is_user_logged_in()){wp_safe_redirect(add_query_arg('vpm_partner_portal',1,home_url('/')));exit;}
            echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Partnerportal – Login</title><link rel="stylesheet" href="'.esc_url(plugins_url('assets/portal.css',dirname(__DIR__).'/vpm-booking-core.php')).'"></head><body><main class="vpm-portal-login"><h1>Partnerportal – Login</h1>';
            wp_login_form(array('redirect'=>add_query_arg('vpm_partner_portal',1,home_url('/')),'label_username'=>'Benutzername oder E-Mail','label_password'=>'Passwort','label_log_in'=>'Im Partnerportal anmelden'));
            echo '<p><a href="'.esc_url(wp_lostpassword_url(self::login_url())).'">Passwort vergessen?</a></p></main></body></html>';exit;
        },0);
    }
}
