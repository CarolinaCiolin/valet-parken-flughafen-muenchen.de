<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
interface PartnerAdapter {
    public function offers( array $partner, array $context, array $credentials ): array;
    public function hold( array $booking, array $credentials, string $key ): array;
    public function confirm( array $booking, array $credentials, string $key ): array;
    public function cancel( array $booking, array $credentials, string $key ): array;
    public function change( array $booking, array $change, array $credentials, string $key ): array;
    public function status( array $booking, array $credentials ): array;
}
final class Adapters {
    public static function has_source(string $airport):bool { global $wpdb,$wp_filter; $hook=$wp_filter['vpm_booking_quote_offers']??null; if($hook)foreach($hook->callbacks as $callbacks)foreach($callbacks as $entry)if($entry['function']!==array(self::class,'offers'))return true;return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.DB::table('connections').' c INNER JOIN '.DB::table('partners').' p ON p.id=c.partner_id WHERE p.airport=%s AND p.active=1 AND c.mode IN (%s,%s)',$airport,'manual','external'))>0; }
    public static function registry(): array { $drivers = apply_filters( 'vpm_booking_adapters', array() ); $out = array(); foreach ( is_array( $drivers ) ? $drivers : array() as $id => $driver ) { if ( is_string( $id ) && preg_match( '/^[a-z0-9_-]{1,50}$/D', $id ) && $driver instanceof PartnerAdapter ) { $out[$id] = $driver; } } return $out; }
    public static function connection( int $partner ): array { global $wpdb; return (array) $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'connections' ) . ' WHERE partner_id=%d', $partner ), ARRAY_A ); }
    public static function configure( int $partner, array $d ): void {
        Tenants::authorize( $partner, true ); $mode = $d['mode'] ?? ''; Config::require( in_array( $mode, array( 'disabled', 'manual', 'external' ), true ), 'invalid_connection_mode' );
        $adapter = $mode === 'external' ? (string) ( $d['adapter'] ?? '' ) : ''; Config::require( $mode !== 'external' || isset( self::registry()[$adapter] ), 'adapter_not_installed' );
        $old = self::connection( $partner ); $secret = $d['api_key'] ?? ''; Config::require( is_string( $secret ) && strlen( $secret ) <= 4000, 'invalid_credentials' );
        global $wpdb; if($old&&($old['mode']!==$mode||$old['adapter']!==$adapter)) { $open=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.DB::table('bookings')." WHERE partner_id=%d AND state<>'cancelled' AND JSON_EXTRACT(snapshot,'$.fulfilment.mode') IS NOT NULL AND CAST(JSON_EXTRACT(snapshot,'$.travel.return_utc') AS UNSIGNED)>%d",$partner,time()));Config::require($open===0,'connection_has_active_bookings'); }
        $credentials = $mode === 'external' && $old && $old['adapter'] === $adapter && $secret === '' ? $old['credentials'] : Config::seal( $secret !== '' ? array( 'api_key' => $secret ) : array() );
        if ( ! empty( $d['clear_credentials'] ) ) { $credentials = Config::seal( array() ); }
        global $wpdb; DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'connections' ) . ' (partner_id,mode,adapter,credentials,assistance,updated_at) VALUES (%d,%s,%s,%s,%d,%d) ON DUPLICATE KEY UPDATE mode=VALUES(mode),adapter=VALUES(adapter),credentials=VALUES(credentials),assistance=VALUES(assistance),updated_at=VALUES(updated_at)', $partner, $mode, $adapter, $credentials, empty( $d['assistance'] ) ? 0 : 1, time() ) );
        Operations::audit( 'partner.connection_saved', 'partner', $partner );
    }
    public static function save_rate( int $partner, array $d ): void {
        Tenants::authorize( $partner, true ); $id = (string) ( $d['product_id'] ?? '' ); $catalogue = Products::catalogue(); Config::require( isset( $catalogue[$id] ) && $catalogue[$id]['partner_id'] === $partner, 'product_not_owned' );
        $price = Commerce::number( $d['price_cents'] ?? '', 50 ); $capacity = Commerce::number( $d['capacity'] ?? 0, 0, 100000 ); $charging = Commerce::number($d['charging_capacity']??0,0,$capacity); $rules=Commerce::rules($d); $pricing = $d['pricing'] ?? ''; Config::require( in_array( $pricing, array( 'stay', '24h' ), true ), 'invalid_pricing' );
        global $wpdb; DB::tx( function() use ( $wpdb, $partner, $id, $price, $capacity, $charging, $rules, $pricing, $d ) {
            // Lock partner to serialize creation of a previously absent rate; capacity acquisition locks rate too.
            DB::row( 'partners', 'id', $partner, true ); $old = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'rates' ) . ' WHERE partner_id=%d AND product_id=%s FOR UPDATE', $partner, $id ), ARRAY_A );
            if($old&&!array_key_exists('capacity',$d)){$capacity=(int)$old['capacity'];$charging=(int)$old['charging_capacity'];}
            if($old){$usage=self::usage($old,array('departure_utc'=>time(),'return_utc'=>PHP_INT_MAX),true);Config::require($capacity >= $usage['total'] && $charging >= $usage['charging'],'capacity_below_reserved_total');}
            DB::query( $wpdb->prepare( 'INSERT INTO ' . DB::table( 'rates' ) . ' (partner_id,product_id,price_cents,pricing,capacity,charging_capacity,rules,enabled) VALUES (%d,%s,%d,%s,%d,%d,%s,%d) ON DUPLICATE KEY UPDATE price_cents=VALUES(price_cents),pricing=VALUES(pricing),capacity=VALUES(capacity),charging_capacity=VALUES(charging_capacity),rules=VALUES(rules),enabled=VALUES(enabled)', $partner, $id, $price, $pricing, $capacity, $charging, wp_json_encode($rules), empty( $d['enabled'] ) ? 0 : 1 ) ); Operations::audit( 'partner.rate_saved', 'partner', $partner );
        } );
    }
    public static function save_capacity(int $partner,array $d):void {
        Tenants::authorize($partner,true);global $wpdb;DB::tx(function()use($partner,$d,$wpdb){$rate=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.DB::table('rates').' WHERE partner_id=%d AND product_id=%s FOR UPDATE',$partner,(string)($d['product_id']??'')),ARRAY_A);Config::require((bool)$rate,'rate_not_owned');$capacity=Commerce::number($d['capacity']??'',0,100000);$charging=Commerce::number($d['charging_capacity']??0,0,$capacity);$usage=self::usage($rate,array('departure_utc'=>time(),'return_utc'=>PHP_INT_MAX),true);Config::require($capacity>=$usage['total']&&$charging>=$usage['charging'],'capacity_below_reserved_total');DB::update('rates',array('capacity'=>$capacity,'charging_capacity'=>$charging),array('id'=>$rate['id']));Operations::audit('partner.capacity_saved','partner',$partner);});
    }
    public static function usage(array $rate,array $context,bool $lock=false):array {
        global $wpdb;$rows=$wpdb->get_results($wpdb->prepare('SELECT starts_at,ends_at,charging FROM '.DB::table('allocations')." WHERE rate_id=%d AND starts_at<%d AND ends_at>%d AND (state='confirmed' OR (state='held' AND expires_at>%d))",$rate['id'],$context['return_utc'],$context['departure_utc'],time()).($lock?' FOR UPDATE':''),ARRAY_A);
        $events=array();foreach($rows as $r){$a=max((int)$r['starts_at'],$context['departure_utc']);$z=min((int)$r['ends_at'],$context['return_utc']);$events[$a]['total']=($events[$a]['total']??0)+1;$events[$z]['total']=($events[$z]['total']??0)-1;if($r['charging']){$events[$a]['charging']=($events[$a]['charging']??0)+1;$events[$z]['charging']=($events[$z]['charging']??0)-1;}}
        ksort($events,SORT_NUMERIC);$now=array('total'=>0,'charging'=>0);$peak=$now;foreach($events as $e)foreach($now as $k=>$v){$now[$k]+=$e[$k]??0;$peak[$k]=max($peak[$k],$now[$k]);}return $peak;
    }
    public static function available(array $rate,array $context,bool $lock=false):bool {
        $context=Conditions::occupancy($context,Conditions::get((int)$rate['partner_id']));$used=self::usage($rate,$context,$lock);return (bool)$rate['enabled']&&$used['total']<(int)$rate['capacity']&&(empty($context['charging_required'])||$used['charging']<(int)($rate['charging_capacity']??0));
    }
    public static function offers( array $existing, array $context ): array {
        global $wpdb; $partners = $wpdb->get_results( $wpdb->prepare( 'SELECT p.* FROM ' . DB::table( 'partners' ) . ' p INNER JOIN ' . DB::table( 'connections' ) . " c ON c.partner_id=p.id WHERE p.airport=%s AND p.active=1 AND c.mode IN ('manual','external')", $context['airport'] ), ARRAY_A );
        foreach ( $partners as $p ) {
            if(Conditions::blocked((int)$p['id'])||!Conditions::eligible($context,Conditions::get((int)$p['id'])))continue;
            $connection = self::connection( (int) $p['id'] );
            if ( $connection['mode'] === 'manual' ) {
                foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'rates' ) . ' WHERE partner_id=%d AND enabled=1', $p['id'] ), ARRAY_A ) as $rate ) {
                    if ( ! self::available( $rate, $context ) ) { continue; } $catalogue = Products::catalogue(); $template = $catalogue[$rate['product_id']] ?? null; if ( ! $template || $template['partner_id'] !== (int) $p['id'] ) { continue; }
                    foreach(Commerce::decorate($rate,$context,(int)$p['id'])as $variant){
                        if(count($existing)>=100)continue;
                        $existing[]=array('partner_id'=>(int)$p['id'],'base_cents'=>$variant['base_cents'],'currency'=>'eur','terms'=>'Parkpreis einschließlich ausgewähltem Service und ausgewiesenen Zuschlägen.','catalog_product_id'=>$rate['product_id'],'available_addon_ids'=>array_column($template['offer']['addons'],'id'),'service'=>$variant['service'],'price_breakdown'=>$variant['breakdown'],'_source'=>array('mode'=>'manual','rate_id'=>(int)$rate['id'],'service'=>$variant['service'],'charging'=>!empty($context['charging_required'])));
                    }
                }
            } else {
                try { $drivers = self::registry(); Config::require( isset( $drivers[$connection['adapter']] ), 'adapter_not_installed' );
                $items = $drivers[$connection['adapter']]->offers( $p, $context, Config::open( $connection['credentials'] ) ); Config::require( count( $items ) <= 20, 'invalid_adapter_response' );
                foreach ( $items as $item ) { Config::require( (int) ( $item['partner_id'] ?? 0 ) === (int) $p['id'], 'adapter_partner_mismatch' ); $service=$item['service']??'parking';if(!empty($context['charging_required'])&&($item['charging_space']??false)!==true)continue;if(!isset(Commerce::services((int)$p['id'],$context)[$service]))continue; $item['service']=$service;$item['_source'] = array( 'mode' => 'external', 'adapter' => $connection['adapter'], 'service'=>$service ); if(count($existing)<100)$existing[] = $item; } $wpdb->update(DB::table('connections'),array('last_error'=>''),array('partner_id'=>$p['id']));
                } catch(\Throwable $e) { $wpdb->update(DB::table('connections'),array('last_error'=>'provider_unavailable'),array('partner_id'=>$p['id'])); }
            }
        } return $existing;
    }
}
