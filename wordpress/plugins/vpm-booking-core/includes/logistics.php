<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Logistics {
    public static function get( int $partner ): array { global $wpdb; $raw=$wpdb->get_var($wpdb->prepare('SELECT payload FROM '.DB::table('logistics').' WHERE partner_id=%d',$partner));return $raw?(json_decode($raw,true)?:array()):array(); }
    private static function text($value,int $limit,bool $multiline=false):string { Config::require(is_string($value)&&strlen($value)<=$limit,'invalid_logistics_text');return $multiline?sanitize_textarea_field($value):sanitize_text_field($value); }
    public static function validate(array $d):array {
        $phone=self::text($d['support_phone']??'',60);$tel=preg_replace('/[^+0-9]/','',$phone);Config::require($phone===''||preg_match('/^\+?[0-9]{5,20}$/D',$tel),'invalid_support_phone');
        $billing=trim(self::text($d['billing_email']??'',254));Config::require($billing===''||is_email($billing),'invalid_billing_email');
        $booking=trim(self::text($d['booking_email']??'',254));Config::require($booking===''||is_email($booking),'invalid_booking_email');
        $out=array('support_phone'=>$phone,'billing_email'=>strtolower($billing),'booking_email'=>strtolower($booking),'points'=>array());
        foreach(array('parking','handover','return')as $key){$p=is_array($d['points'][$key]??null)?$d['points'][$key]:array();$point=array();
            foreach(array('de','en')as $lang){$point['name_'.$lang]=self::text($p['name_'.$lang]??'',180);$point['instructions_'.$lang]=self::text($p['instructions_'.$lang]??'',4000,true);}
            $point['maps_url']=self::maps_url($p['maps_url']??'');$point['address']=self::text($p['address']??'',500);$lat=trim(self::text($p['latitude']??'',40));$lng=trim(self::text($p['longitude']??'',40));Config::require(($lat==='')===($lng===''),'coordinate_pair_required');
            foreach(array('latitude'=>$lat,'longitude'=>$lng)as $coordinate=>$value){if($value===''){$point[$coordinate]=null;continue;}Config::require(preg_match('/^-?\d{1,3}(?:[.,]\d{1,8})?$/D',$value),'invalid_coordinate');$number=(float)str_replace(',','.',$value);Config::require(abs($number)<=($coordinate==='latitude'?90:180),'invalid_coordinate');$point[$coordinate]=$number;}
            $out['points'][$key]=$point;
        }return $out;
    }
    public static function save(int $partner,array $d):void { Tenants::authorize($partner,true);$data=self::validate($d);global $wpdb;DB::query($wpdb->prepare('INSERT INTO '.DB::table('logistics').' (partner_id,payload,updated_at) VALUES (%d,%s,%d) ON DUPLICATE KEY UPDATE payload=VALUES(payload),updated_at=VALUES(updated_at)',$partner,wp_json_encode($data),time()));Operations::audit('partner.logistics_saved','partner',$partner); }
    public static function maps_url($value):string {
        Config::require(is_string($value)&&strlen($value)<=2000,'invalid_maps_url');$value=trim($value);if($value==='')return ''; $host=strtolower((string)wp_parse_url($value,PHP_URL_HOST));$path=(string)wp_parse_url($value,PHP_URL_PATH);
        Config::require(wp_parse_url($value,PHP_URL_SCHEME)==='https'&&!wp_parse_url($value,PHP_URL_USER)&&!wp_parse_url($value,PHP_URL_PASS)&&!wp_parse_url($value,PHP_URL_PORT)&&((in_array($host,array('www.google.com','google.com','www.google.de','google.de'),true)&&($path==='/maps'||str_starts_with($path,'/maps/')))||$host==='maps.google.com'||$host==='maps.app.goo.gl'||($host==='goo.gl'&&str_starts_with($path,'/maps/'))),'invalid_maps_url');return esc_url_raw($value);
    }
    public static function directions(array $point):string {
        if(!empty($point['maps_url']))return self::maps_url($point['maps_url']);
        if(isset($point['latitude'],$point['longitude']))$destination=$point['latitude'].','.$point['longitude'];else $destination=$point['address']??'';
        return $destination!==''?'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($destination):'';
    }
    public static function snapshot(array $partner):array {
        $profile=self::get((int)$partner['id']);$out=array('partner_name'=>$partner['name'],'support_phone'=>$profile['support_phone']??'','billing_email'=>$profile['billing_email']??'','booking_email'=>$profile['booking_email']??'','document_issuer'=>'partner','cancellation_responsible'=>'partner','sender_role'=>'platform','handover_minutes'=>Commerce::get((int)$partner['id'])['handover_minutes']??180,'points'=>array());
        foreach($profile['points']??array()as $key=>$point){if($point['address']===''&&$point['latitude']===null&&empty($point['maps_url'])&&empty($point['instructions_de'])&&empty($point['instructions_en']))continue;$point['directions_url']=self::directions($point);$out['points'][$key]=$point;}return $out;
    }
    public static function appointment(array $data,array $travel,string $service,int $partner):array {
        if(($travel['time_basis']??'')!=='flight')return $data;$preferred=$travel['flight_departure_utc']-($data['handover_minutes']??180)*MINUTE_IN_SECONDS;$policy=Commerce::get($partner)['services'][$service]??array();
        // A shorter preferred lead must not move the appointment outside service hours.
        $data['confirmed_handover_utc']=Commerce::open($policy['hours']??array(),$preferred,$travel['timezone'])?$preferred:$travel['departure_utc'];return $data;
    }
    public static function customer_data(array $b):array {
        $q=Core::snapshot($b);$data=$q['partner_service']??array();if(($q['travel']['time_basis']??'')==='flight'){$data['handover_utc']=$data['confirmed_handover_utc']??($q['travel']['flight_departure_utc']-($data['handover_minutes']??180)*MINUTE_IN_SECONDS);$data['collection_utc']=$q['travel']['flight_return_utc']+30*MINUTE_IN_SECONDS;$data['timezone']=$q['travel']['timezone'];}unset($data['billing_email'],$data['booking_email']);if($b['state']!=='confirmed'||!in_array($b['payment_state'],array('paid','partially_refunded','refunded'),true))$data['points']=array();return $data;
    }
    public static function partner_event(array $b,string $topic,array $extra=array(),string $suffix=''):void {
        $q=Core::snapshot($b);if(!empty($q['sandbox_demo']))return;$service=$q['partner_service']??array();$billing=in_array($topic,array('partner.invoice_required','partner.credit_note_required'),true);$email=$billing?($service['billing_email']??''):($service['booking_email']??$service['billing_email']??'');if(!$email&&!$billing)$email=$service['billing_email']??'';
        if(!$email){Automation::event('partner-contact-missing:'.$b['public_id'].':'.($billing?'billing':'booking'),'operations.partner_contact_missing','transactional',array('booking_id'=>$b['public_id'],'partner_id'=>(int)$b['partner_id'],'contact_kind'=>$billing?'billing':'booking'));return;}
        Automation::event($b['public_id'].':'.$topic.':'.$suffix,$topic,'transactional',array_merge(array('recipient'=>$email,'customer_email'=>$b['customer_email'],'booking_id'=>$b['public_id'],'partner_id'=>(int)$b['partner_id'],'sender_role'=>'platform','document_issuer'=>'partner','amounts'=>array_intersect_key($q,array_flip(array('base','discount','charged','currency')))),$extra));
    }
    public static function refresh(string $reference):void {
        $b=Core::booking($reference);Tenants::authorize((int)$b['partner_id'],true);
        DB::tx(function()use($reference){$b=Core::booking($reference,true);Config::require($b['state']==='confirmed'&&in_array($b['payment_state'],array('paid','partially_refunded'),true),'booking_not_confirmed');$q=Core::snapshot($b);$partner=DB::row('partners','id',$b['partner_id']);$new=self::snapshot($partner);if(isset($q['partner_service']['confirmed_handover_utc']))$new['confirmed_handover_utc']=$q['partner_service']['confirmed_handover_utc'];Config::require($new['support_phone']!==''&&!empty($new['points']['handover']['directions_url']),'partner_instructions_incomplete');
            if(wp_json_encode($q['partner_service']??array())===wp_json_encode($new))return;
            $oldPublic=$q['partner_service']??array();$newPublic=$new;unset($oldPublic['billing_email'],$oldPublic['booking_email'],$newPublic['billing_email'],$newPublic['booking_email']);$visible=wp_json_encode($oldPublic)!==wp_json_encode($newPublic);
            $q['partner_service']=$new;$q['service_revision']=(int)($q['service_revision']??0)+1;DB::update('bookings',array('snapshot'=>wp_json_encode($q)),array('id'=>$b['id']));$b['snapshot']=wp_json_encode($q);Operations::audit($visible?'booking.instructions_updated':'booking.partner_contact_updated','booking',(int)$b['id']);if($visible)Automation::booking_event($b,'booking.instructions_updated',array('service_revision'=>$q['service_revision']),'revision:'.$q['service_revision']);
        });
    }
    public static function document_required(array $b):void { if($b['state']==='confirmed'&&$b['payment_state']==='paid')self::partner_event($b,'partner.invoice_required'); }
}
