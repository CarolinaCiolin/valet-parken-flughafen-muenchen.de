<?php
namespace VPM\Booking;
if (!defined('ABSPATH')) exit;
/** Operational deadlines are internal targets, never promises to customers or automatic cancellation. */
final class Workflows {
    public static function settings(): array {
        return array_merge(array('enabled'=>false,'remind_hours'=>4,'escalate_hours'=>24), (array)get_option('vpm_booking_workflows',array()));
    }
    public static function save(array $d):void {
        Config::require(current_user_can('manage_options'),'permission_denied');
        $r=Commerce::number($d['remind_hours']??4,1,168);$e=Commerce::number($d['escalate_hours']??24,$r+1,336);
        update_option('vpm_booking_workflows',array('enabled'=>!empty($d['enabled']),'remind_hours'=>$r,'escalate_hours'=>$e),false);
        Operations::audit('workflow.settings_saved','platform',0);
    }
    public static function categories():array {
        return array(
            'provider'=>array('arrival'=>array('Anfahrt & Treffpunkt','Directions and meeting point'),'delay'=>array('Verspätung','Flight delay'),'handover'=>array('Übergabe & Abholung','Drop-off and collection'),'invoice'=>array('Rechnung oder Beleg','Invoice or receipt'),'other'=>array('Sonstige Frage zum Anbieter','Other provider question')),
            'cancel'=>array('cancel'=>array('Buchung stornieren','Request cancellation')),
            'platform'=>array('payment'=>array('Zahlung oder Erstattung','Payment or refund'),'voucher'=>array('Gutschein','Voucher'),'technical'=>array('Technisches Problem','Technical problem'),'other'=>array('Sonstige Plattformfrage','Other platform question'))
        );
    }
    public static function category(string $reason,string $category):string {
        $all=self::categories();Config::require(isset($all[$reason]),'invalid_support_request');
        $category=$category?:($reason==='cancel'?'cancel':'other');Config::require(isset($all[$reason][$category]),'invalid_support_category');return $category;
    }
    public static function deadline(array $inquiry,array $booking):array {
        $s=self::settings();$travel=Core::snapshot($booking)['travel']??array();$created=(int)$inquiry['created_at'];$arrival=(int)($travel['departure_utc']??0);if($arrival<$created)$arrival=(int)($travel['return_utc']??0);
        $urgent=$arrival>0&&$arrival>=$created&&$arrival-$created<=DAY_IN_SECONDS;
        return array('remind'=>$created+min((int)$s['remind_hours'],$urgent?1:168)*HOUR_IN_SECONDS,'escalate'=>$created+min((int)$s['escalate_hours'],$urgent?4:336)*HOUR_IN_SECONDS,'urgent'=>$urgent);
    }
    public static function problem(string $kind,int $id,int $now):array {
        global $wpdb;Config::require(in_array($kind,array('inquiry','job','mail','refund','payment','change'),true),'invalid_case_source');$row=DB::row($kind==='inquiry'?'inquiries':($kind==='job'?'fulfilment':($kind==='mail'?'outbox':($kind==='refund'?'refunds':($kind==='change'?'changes':'bookings')))),'id',$id);
        Config::require(!$wpdb->last_error,'case_source_unavailable');if(!$row)return array();$booking=$kind==='payment'?$row:(!empty($row['booking_id'])?DB::row('bookings','id',(int)$row['booking_id']):array());
        if($kind==='mail'&&!$booking){try{$payload=Config::open($row['payload']);if(is_string($payload['booking_id']??null))$booking=Core::booking($payload['booking_id']);}catch(\Throwable $e){}}
        $base=array('partner_id'=>(int)($booking['partner_id']??0),'booking_id'=>(int)($booking['id']??0),'severity'=>'high');
        if($kind==='inquiry'){
            if($row['state']!=='pending')return array();$deadline=self::deadline($row,$booking);
            if(($row['audience']??'provider')==='platform')return $base+array('summary'=>'Kundenanliegen an die Plattform');
            if($now<$deadline['escalate'])return array();return $base+array('summary'=>'Partnerantwort überfällig');
        }
        if($kind==='change'){if($row['state']==='completed')return array();$deadline=self::deadline($row,$booking);return $now>=$deadline['escalate']?$base+array('summary'=>'Stornobearbeitung überfällig'):array();}
        if($kind==='job'&&$row['status']==='attention')return $base+array('summary'=>'Partnerprozess benötigt Klärung');
        if($kind==='refund'&&in_array($row['status'],array('attention','failed'),true))return $base+array('summary'=>'Erstattung benötigt Klärung');
        if($kind==='mail'&&$row['status']==='attention')return $base+array('summary'=>'Versand nach Wiederholungen fehlgeschlagen');
        if($kind==='payment'){
            if($row['payment_state']==='creating'&&(int)$row['created_at']<$now-1800)return $base+array('summary'=>'Zahlungserstellung unklar – Abgleich erforderlich');
            if($row['payment_state']==='paid'&&!in_array($row['state'],array('confirmed','cancelled','expired'),true)&&(int)$row['created_at']<$now-900)return $base+array('summary'=>'Zahlung erfasst, Partnerbestätigung ausstehend');
        }
        return array();
    }
    private static function sync(string $kind,int $id,int $now):void {
        global $wpdb;$fingerprint=$kind.':'.$id;$issue=self::problem($kind,$id,$now);
        if(!$issue){DB::query($wpdb->prepare('UPDATE '.DB::table('cases')." SET state='resolved',resolved_at=%d WHERE fingerprint=%s AND state='open'",$now,$fingerprint));return;}
        DB::query($wpdb->prepare('INSERT INTO '.DB::table('cases').' (fingerprint,source_kind,source_id,partner_id,booking_id,severity,summary,state,first_seen,last_seen) VALUES (%s,%s,%d,%d,%d,%s,%s,%s,%d,%d) ON DUPLICATE KEY UPDATE last_seen=VALUES(last_seen),summary=VALUES(summary),acknowledged_by=IF(state=\'resolved\',0,acknowledged_by),first_seen=IF(state=\'resolved\',VALUES(first_seen),first_seen),state=\'open\',resolved_at=0',$fingerprint,$kind,$id,$issue['partner_id'],$issue['booking_id'],$issue['severity'],$issue['summary'],'open',$now,$now));
    }
    private static function batch(string $kind,string $table,string $condition):array {
        global $wpdb;$cursors=(array)get_option('vpm_booking_workflow_cursors',array());$after=(int)($cursors[$kind]??0);
        $ids=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.DB::table($table).' WHERE ('.$condition.') AND id>%d ORDER BY id LIMIT 200',$after));
        if(!$ids&&$after)$ids=$wpdb->get_col('SELECT id FROM '.DB::table($table).' WHERE '.$condition.' ORDER BY id LIMIT 200');
        $cursors[$kind]=$ids?(int)end($ids):0;update_option('vpm_booking_workflow_cursors',$cursors,false);return $ids;
    }
    public static function run():void {
        global $wpdb;$now=time();$s=self::settings();
        $pending=array_map(fn($id)=>DB::row('inquiries','id',(int)$id),self::batch('inquiry','inquiries',"state='pending'"));
        foreach($pending as $i){$b=DB::row('bookings','id',(int)$i['booking_id']);if(!$b)continue;$deadline=self::deadline($i,$b);
            if($s['enabled']&&($i['audience']??'provider')==='provider'&&$now>=$deadline['remind']){
                $stage=$now>=$deadline['escalate']?'escalated':'reminder';$body=Config::open($i['payload']);$extra=array('inquiry_id'=>(int)$i['id'],'message'=>Broker::redact((string)$body['message'],$b['customer_email']),'category'=>$body['category']??'other','deadline_utc'=>$deadline['escalate'],'portal_url'=>add_query_arg(array('vpm_partner_portal'=>1,'partner'=>(int)$b['partner_id']),home_url('/')).'#messages');
                Logistics::partner_event($b,'partner.support_'.$stage,$extra,'inquiry:'.$i['id']);
            }
            self::sync('inquiry',(int)$i['id'],$now);
        }
        foreach(array('change'=>array('changes',"state<>'completed' AND kind='cancellation'"),'job'=>array('fulfilment',"status='attention'"),'mail'=>array('outbox',"status='attention'"),'refund'=>array('refunds',"status IN ('attention','failed')"),'payment'=>array('bookings',"payment_state IN ('creating','paid') AND state NOT IN ('confirmed','cancelled')"))as $kind=>$query){$ids=self::batch($kind,$query[0],$query[1]);foreach($ids as $id){self::sync($kind,(int)$id,$now);if($kind==='change'&&$s['enabled']){$change=DB::row('changes','id',(int)$id);$b=DB::row('bookings','id',(int)$change['booking_id']);if($b){$due=self::deadline($change,$b);if($now>=$due['remind'])Logistics::partner_event($b,'partner.cancellation_reminder',array('message'=>'Ein Stornowunsch ist noch nicht abgeschlossen. Bitte im Partnerportal prüfen.','portal_url'=>add_query_arg(array('vpm_partner_portal'=>1,'partner'=>(int)$b['partner_id']),home_url('/')).'#requests'),'change:'.$id);}}}}
        foreach($wpdb->get_results('SELECT source_kind,source_id FROM '.DB::table('cases')." WHERE state='open' ORDER BY last_seen LIMIT 500",ARRAY_A)as $case)self::sync($case['source_kind'],(int)$case['source_id'],$now);
        update_option('vpm_booking_last_workflow_scan',$now,false);
    }
    public static function acknowledge(int $id):void {
        Config::require(current_user_can('manage_options'),'permission_denied');$c=DB::row('cases','id',$id);Config::require($c&&$c['state']==='open','case_not_open');DB::update('cases',array('acknowledged_by'=>get_current_user_id()),array('id'=>$id));Operations::audit('case.acknowledged','case',$id);
    }
    public static function readiness_issues(int $id):array {
        Tenants::authorize($id);global $wpdb;$issues=array();$c=Adapters::connection($id);$profile=Commerce::get($id);$services=$profile['services']??array();
        if(!$services)$issues[]=array('target'=>'service-times','message'=>'Mindestens einen angebotenen Service aktivieren. Leere Wochenzeiten bedeuten danach 24/7.');
        if(Conditions::blocked($id))$issues[]=array('target'=>'requests','message'=>'Eine ungeklärte Verspätung sperrt neue Angebote. Den Konflikt zuerst bearbeiten.');
        $p=DB::row('partners','id',$id);if(empty($p['active']))$issues[]=array('target'=>'overview','message'=>'Die Plattform hat diesen Partner noch nicht für neue Anfragen freigegeben.');
        foreach(Products::catalogue()as $key=>$product){if($product['partner_id']!==$id)continue;$offer=$product['offer'];$name=Products::label($offer['product_name']);
            if(in_array($offer['parking_type'],array('covered','garage','underground'),true)&&empty($offer['max_height_cm']))$issues[]=array('target'=>'products','message'=>$name.': maximale Fahrzeughöhe ergänzen.');
            if(($c['mode']??'')!=='manual')continue;$r=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.DB::table('rates').' WHERE partner_id=%d AND product_id=%s',$id,$key),ARRAY_A);
            if(!$r){$issues[]=array('target'=>'prices','message'=>$name.': noch kein Parkpreis gespeichert.');continue;}
            if(!$r['enabled'])$issues[]=array('target'=>'inventory','message'=>$name.': Verkauf ist pausiert. Bestehende Buchungen bleiben erhalten.');
            if((int)$r['capacity']===0)$issues[]=array('target'=>'inventory','message'=>$name.': kein physischer Bestand hinterlegt.');
            if(in_array('charging',array_column($offer['addons'],'id'),true)&&(int)$r['charging_capacity']===0)$issues[]=array('target'=>'inventory','message'=>$name.': Ladepaket angeboten, aber kein Lade-Teilbestand hinterlegt.');
        }
        return $issues;
    }
    public static function readiness(int $id):void {
        $issues=self::readiness_issues($id);echo '<div class="vpm-readiness-issues"><h3>Was braucht noch Aufmerksamkeit?</h3>';if(!$issues)echo '<p>Keine der geprüften Konfigurationslücken gefunden. Prüfen Sie jetzt eine Beispielreise in der Preisvorschau.</p>';foreach($issues as $i)echo '<p><a href="#'.esc_attr($i['target']).'">'.esc_html($i['message']).' →</a></p>';echo '</div>';
    }
    public static function price_preview(int $id,array $d):array {
        Tenants::authorize($id);Config::require((Adapters::connection($id)['mode']??'')==='manual','preview_manual_only');$key=(string)($d['product_id']??'');$product=Products::catalogue()[$key]??array();Config::require($product&&$product['partner_id']===$id,'product_not_owned');
        $p=DB::row('partners','id',$id);$context=PublicApi::context(array_merge($d,array('airport'=>$p['airport'],'time_basis'=>'flight','charging_required'=>false,'accessible_required'=>false)));Config::require(in_array($context['service'],array('parking','valet','shuttle'),true),'invalid_service');
        global $wpdb;$r=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.DB::table('rates').' WHERE partner_id=%d AND product_id=%s',$id,$key),ARRAY_A);Config::require((bool)$r,'rate_not_owned');$service=Commerce::get($id)['services'][$context['service']]??null;Config::require((bool)$service,'service_unavailable');$price=Commerce::price($r,$context,$service);$hours=Commerce::open($service['hours'],$context['departure_utc'],$context['timezone'])&&Commerce::open($service['hours'],$context['return_utc'],$context['timezone']);
        return array('breakdown'=>$price,'within_hours'=>$hours,'capacity_available'=>Adapters::available($r,Conditions::occupancy($context,Conditions::get($id))),'conditions_available'=>Conditions::eligible($context,Conditions::get($id)),'timezone'=>$context['timezone'],'parking_start'=>wp_date('d.m.Y H:i',$context['departure_utc'],new \DateTimeZone($context['timezone'])),'parking_end'=>wp_date('d.m.Y H:i',$context['return_utc'],new \DateTimeZone($context['timezone'])),'mode'=>'preview_no_reservation');
    }
    public static function preview_ui(int $id):void {
        if((Adapters::connection($id)['mode']??'')!=='manual')return;echo '<div class="vpm-price-preview" data-api="'.esc_url(rest_url('vpm-booking/v1/portal/'.$id.'/price-preview')).'" data-nonce="'.esc_attr(wp_create_nonce('wp_rest')).'"><h3>Beispielreise durchrechnen</h3><p>Nur Vorschau: speichert keine Buchung und reserviert keinen Stellplatz. Parkpreis, Service und Nachtzuschläge; Extras und Plattformgutscheine sind nicht enthalten.</p><form><label>Stellplatzangebot<select name="product_id" required>';foreach(Products::catalogue()as $key=>$p)if($p['partner_id']===$id)echo '<option value="'.esc_attr($key).'">'.esc_html(Products::label($p['offer']['product_name'])).'</option>';echo '</select></label><label>Service<select name="service">';foreach(Commerce::get($id)['services']??array()as $service=>$v)echo '<option value="'.esc_attr($service).'">'.esc_html(array('parking'=>'Eigenanreise','valet'=>'Valet','shuttle'=>'Shuttle')[$service]).'</option>';echo '</select></label><label>Geplanter Abflug<input type="datetime-local" name="departure" required></label><label>Geplante Rücklandung<input type="datetime-local" name="return" required></label><button>Preisvorschau berechnen</button></form><div role="status" aria-live="polite"></div></div>';
    }
    public static function admin():void {
        Config::require(current_user_can('manage_options'),'permission_denied');global $wpdb;$s=self::settings();echo '<section id="automation"><h2>Automatisierung & Klärungsfälle</h2><p>Fristen sind interne Bearbeitungsziele. Kein automatischer Kundenstorno, keine Zusatzabbuchung und keine Abbruchwerbung. Hintergrundaufgaben benötigen einen regelmäßig laufenden Worker.</p><form method="post">';wp_nonce_field('vpm_booking_operations');echo '<input type="hidden" name="vpm_operation" value="workflow_settings"><label><input type="checkbox" name="enabled" value="1" '.checked($s['enabled'],true,false).'> Partner-Erinnerungen in die Versandwarteschlange stellen</label><p><label>Erinnerung nach Stunden <input type="number" min="1" max="168" name="remind_hours" value="'.(int)$s['remind_hours'].'"></label> <label>Eskalation nach Stunden <input type="number" min="2" max="336" name="escalate_hours" value="'.(int)$s['escalate_hours'].'"></label></p><p>Anreise innerhalb 24 Stunden: verkürzt auf höchstens 1 Stunde / 4 Stunden. Kein Versand ohne eingerichteten Transport. Pro Anliegen und Stufe nur ein Ereignis.</p><button class="button">Automatisierung speichern</button></form><form method="post">';wp_nonce_field('vpm_booking_operations');echo '<input type="hidden" name="vpm_operation" value="workflow_scan"><button class="button">Klärungsfälle jetzt abgleichen</button></form>';
        $last=(int)get_option('vpm_booking_last_worker',0);echo '<p>'.esc_html($last?'Letzter Worker: '.wp_date('d.m.Y H:i',$last):'Noch kein Worker-Lauf erfasst.').($last&&$last<time()-900?' Hintergrundverarbeitung seit mehr als 15 Minuten nicht gemeldet.':'').'</p>';
        $total=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.DB::table('cases')." WHERE state='open'");$pages=max(1,(int)ceil($total/50));$page=min($pages,max(1,absint(is_scalar($_GET['case_page']??1)?($_GET['case_page']??1):1)));$cases=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.DB::table('cases')." WHERE state='open' ORDER BY first_seen,id LIMIT 50 OFFSET %d",($page-1)*50),ARRAY_A);if(!$cases)echo '<p>Keine offenen erfassten Klärungsfälle. Ein Abgleich muss zuvor gelaufen sein.</p>';foreach($cases as $c){$b=$c['booking_id']?DB::row('bookings','id',(int)$c['booking_id']):array();echo '<article class="notice notice-warning vpm-workflow-case"><h3>'.esc_html($c['summary']).'</h3><p>'.esc_html($b?Experience::number($b):'Vorgang '.$c['source_kind'].' #'.$c['source_id']).' · '.esc_html(wp_date('d.m.Y H:i',(int)$c['first_seen'])).'</p>';echo '<p>'.esc_html(array('inquiry'=>'Anliegen lesen und zuständig beantworten.','change'=>'Stornobedingungen und Erstattungsbetrag prüfen; erst danach ausführen.','payment'=>'Stripe-Teststatus abgleichen; keine zweite Zahlung anlegen und keinen Bestand ungesichert freigeben.','refund'=>'Stripe-Erstattungsstatus prüfen; keine zweite Erstattung anlegen.','mail'=>'Transport und Versandprotokoll prüfen. Annahme ist kein Zustellbeweis.','job'=>'Bestand beziehungsweise Anbindung prüfen und den bestehenden Partnerauftrag bearbeiten.')[$c['source_kind']]??'Ursache prüfen.').'</p>';if($c['partner_id'])echo '<a href="'.esc_url(add_query_arg(array('vpm_partner_portal'=>1,'partner'=>(int)$c['partner_id']),home_url('/')).'#'.($c['source_kind']==='inquiry'?'messages':'requests')).'">Partnerfall öffnen</a>';if(!$c['acknowledged_by']){echo '<form method="post">';wp_nonce_field('vpm_booking_operations');echo '<input type="hidden" name="vpm_operation" value="workflow_ack"><input type="hidden" name="id" value="'.(int)$c['id'].'"><button class="button">Prüfung übernommen</button></form>';}else echo '<p>Prüfung übernommen. Bleibt offen, bis die Ursache behoben ist.</p>';echo '</article>';}echo '<p>Seite '.$page.' von '.$pages.' · '.$total.' offene Fälle</p>';if($page>1)echo '<a href="'.esc_url(add_query_arg(array('page'=>'vpm-booking-operations','case_page'=>$page-1),admin_url('tools.php')).'#automation').'">← Zurück</a> ';if($page<$pages)echo '<a href="'.esc_url(add_query_arg(array('page'=>'vpm-booking-operations','case_page'=>$page+1),admin_url('tools.php')).'#automation').'">Weitere Fälle →</a>';echo '</section>';
    }
}
