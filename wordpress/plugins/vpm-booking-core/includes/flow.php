<?php
namespace VPM\Booking;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Flow {
    public static function managed( array $b ): bool { return ! empty( Core::snapshot( $b )['fulfilment'] ); }
    public static function enqueue( array $b, string $action ): void {
        if ( ! self::managed( $b ) ) { return; } Config::require( in_array( $action, array( 'hold', 'confirm', 'cancel' ), true ), 'invalid_fulfilment_action' ); global $wpdb;
        DB::query( $wpdb->prepare( 'INSERT IGNORE INTO ' . DB::table( 'fulfilment' ) . ' (booking_id,action,status,created_at) VALUES (%d,%s,%s,%d)', $b['id'], $action, 'pending', time() ) );
    }
    public static function create( array $input, string $key, array $source ): array {
        if ( ! $source ) { return Core::create( $input, $key ); }
        Config::require( in_array( $source['mode'] ?? '', array( 'manual', 'external' ), true ), 'invalid_offer_source' ); $input['fulfilment'] = $source;
        return DB::tx( function() use ( $input, $key, $source ) {
            global $wpdb; $connection = Adapters::connection( $input['partner_id'] ); Config::require( $connection && $connection['mode'] === $source['mode'], 'connection_changed_recheck' );
            $rate = null; if ( $source['mode'] === 'manual' ) { $rate = DB::row( 'rates', 'id', Config::integer( $source['rate_id'] ?? null, 1 ), true ); Config::require( $rate && (int) $rate['partner_id'] === $input['partner_id'], 'rate_not_owned' ); }
            $old = DB::row( 'bookings', 'request_key', $key, true ); if ( $old ) { return Core::create( $input, $key ); }
            if(isset($source['service']))Config::require(isset(Commerce::services($input['partner_id'],$input['travel'])[$source['service']]),'service_unavailable');
            Config::require(!Conditions::blocked($input['partner_id']),'partner_delay_needs_review');Config::require(Conditions::eligible($input['travel'],$input['booking_rules']??Conditions::get($input['partner_id'])),'booking_conditions_unavailable');$occupancy=Conditions::occupancy($input['travel'],$input['booking_rules']??Conditions::get($input['partner_id']));
            if ( $rate ) { Config::require( Adapters::available( $rate, $occupancy, true ), 'capacity_unavailable' ); }
            $b = Core::create( $input, $key );
            if ( $rate ) { DB::insert( 'allocations', array( 'booking_id' => $b['id'], 'rate_id' => $rate['id'], 'charging'=>empty($input['travel']['charging_required'])?0:1, 'starts_at' => $occupancy['departure_utc'], 'ends_at' => $occupancy['return_utc'], 'expires_at' => time() + 600, 'state' => 'held' ) ); }
            else { self::enqueue( $b, 'hold' ); } return $b;
        } );
    }
    private static function proof(int $booking,string $action):array { global $wpdb; $row=$wpdb->get_row($wpdb->prepare('SELECT result FROM '.DB::table('fulfilment').' WHERE booking_id=%d AND action=%s',$booking,$action),ARRAY_A);return !empty($row['result'])?Config::open($row['result']):array(); }
    public static function confirmation_ready(array $b):bool { if(!self::managed($b)||$b['payment_state']!=='paid')return false; global $wpdb; $source=Core::snapshot($b)['fulfilment']; if($source['mode']==='external')return (self::proof((int)$b['id'],'confirm')['acknowledged']??false)===true; return $wpdb->get_var($wpdb->prepare('SELECT state FROM '.DB::table('allocations').' WHERE booking_id=%d',$b['id']))==='confirmed'; }
    public static function payment_ready( array $b ): bool {
        if ( ! self::managed( $b ) || $b['state'] === 'cancelled' ) { return false; } global $wpdb; $source = Core::snapshot( $b )['fulfilment'];
        if ( $source['mode'] === 'manual' ) { $a = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'allocations' ) . ' WHERE booking_id=%d', $b['id'] ), ARRAY_A ); return $a && $a['state'] === 'held' && (int) $a['expires_at'] > time(); }
        $proof=self::proof((int)$b['id'],'hold');return !empty($proof['reference'])&&(int)($proof['expires_at']??0)>time();
    }
    public static function cancel_request( string $reference ): array { $b = DB::tx(function()use($reference){$b=Core::booking($reference,true);if($b['state']==='cancelled')return $b;return isset(Core::snapshot($b)['cancellation_decision'])?$b:Conditions::freeze($b);}); if ( ! self::managed( $b ) ) { return Core::cancel( $reference ); } self::enqueue( $b, 'cancel' ); return array( 'reference' => $reference, 'cancellation' => 'pending_partner_acknowledgement' ); }
    public static function run(): void {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . DB::table( 'fulfilment' ) . " WHERE (status='pending' OR (status='processing' AND locked_until<%d)) AND next_attempt<=%d ORDER BY id LIMIT 20", time(), time() ) );
        foreach ( $ids as $id ) {
            $candidate=DB::row('fulfilment','id',(int)$id);if(!$candidate)continue; $lease=bin2hex(random_bytes(16));
            DB::query($wpdb->prepare('INSERT INTO '.DB::table('operation_locks').' (booking_id,lease,expires_at) VALUES (%d,%s,%d) ON DUPLICATE KEY UPDATE lease=IF(expires_at<%d,VALUES(lease),lease),expires_at=IF(expires_at<%d,VALUES(expires_at),expires_at)',$candidate['booking_id'],$lease,time()+120,time(),time()));
            if($wpdb->get_var($wpdb->prepare('SELECT lease FROM '.DB::table('operation_locks').' WHERE booking_id=%d',$candidate['booking_id']))!==$lease)continue;
            $claimed = DB::query( $wpdb->prepare( 'UPDATE ' . DB::table( 'fulfilment' ) . " SET status='processing',locked_until=%d,locked_by=%s WHERE id=%d AND (status='pending' OR (status='processing' AND locked_until<%d))", time()+120, $lease, $id, time() ) ); if ( ! $claimed ) { $wpdb->delete(DB::table('operation_locks'),array('booking_id'=>$candidate['booking_id'],'lease'=>$lease)); continue; }
            $job = DB::row( 'fulfilment', 'id', $id ); $b = DB::row( 'bookings', 'id', $job['booking_id'] );
            try {
                $source = Core::snapshot( $b )['fulfilment'];
                if(($job['action']==='confirm'&&$b['state']==='confirmed'&&self::confirmation_ready($b))||($job['action']==='cancel'&&$b['state']==='cancelled'&&($source['mode']==='manual'||(self::proof((int)$b['id'],'cancel')['acknowledged']??false)===true))){DB::update('fulfilment',array('status'=>'succeeded','locked_until'=>0,'error_code'=>''),array('id'=>$id,'locked_by'=>$lease));continue;}
                if ( $source['mode'] === 'manual' ) {
                    $rate = DB::row( 'rates', 'id', (int) $source['rate_id'] ); Config::require( (bool) $rate, 'manual_rate_missing' );
                    DB::tx( function() use ( $b, $source, $job, $wpdb ) {
                        DB::row( 'rates', 'id', (int) $source['rate_id'], true ); $current = Core::booking( $b['public_id'], true ); $a = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'allocations' ) . ' WHERE booking_id=%d', $b['id'] ), ARRAY_A );
                        if ( $job['action'] === 'confirm' ) { Config::require( $current['payment_state'] === 'paid' && $current['state'] !== 'cancelled' && $a && ( $a['state'] === 'confirmed' || ( $a['state'] === 'held' && (int)$a['expires_at']>time() ) ), 'paid_capacity_needs_review' ); DB::update( 'allocations', array( 'state' => 'confirmed' ), array( 'booking_id' => $b['id'] ) ); Core::confirm( $b['public_id'] ); }
                        elseif ( $job['action'] === 'cancel' ) { DB::update( 'allocations', array( 'state' => 'released' ), array( 'booking_id' => $b['id'] ) ); Core::cancel( $b['public_id'] ); }
                        else { throw new \RuntimeException( 'manual_hold_already_created' ); }
                    } );
                } else {
                    $connection = Adapters::connection( (int)$b['partner_id'] ); $drivers = Adapters::registry(); Config::require( $connection && $connection['adapter'] === $source['adapter'] && isset( $drivers[$source['adapter']] ), 'adapter_configuration_changed' );
                    $driver=$drivers[$source['adapter']]; $credentials=Config::open($connection['credentials']); $request=Broker::adapter($b); $request['partner_hold']=self::proof((int)$b['id'],'hold');
                    if($job['action']==='hold') {
                        Config::require($b['state']!=='cancelled','booking_cancelled');$result=$driver->hold($request,$credentials,'vpm-hold-'.$b['public_id']);
                        Config::require(is_string($result['reference']??null)&&preg_match('/^[a-zA-Z0-9_.:-]{1,150}$/D',$result['reference'])&&is_int($result['expires_at']??null)&&$result['expires_at']>time()&&$result['expires_at']<=time()+DAY_IN_SECONDS,'partner_hold_proof_invalid');
                        $result=array('reference'=>$result['reference'],'expires_at'=>$result['expires_at']);
                    } elseif($job['action']==='confirm') {
                        Config::require($b['payment_state']==='paid'&&$b['state']!=='cancelled'&&self::payment_ready($b),'paid_capacity_needs_review');
                        $result=$driver->confirm($request,$credentials,'vpm-confirm-'.$b['public_id']);Config::require(($result['acknowledged']??false)===true,'partner_acknowledgement_missing');$result=array('acknowledged'=>true);
                    } else {
                        Config::require(!empty($request['partner_hold']['reference']),'partner_hold_not_ready');$result=$driver->cancel($request,$credentials,'vpm-cancel-'.$b['public_id']);$rules=Core::snapshot($b)['booking_rules']??array();if(!empty($rules['cancellation_enabled'])||!empty($rules['no_show_enabled']))Config::require(($result['fee_cents']??null)===Conditions::decision($b)['fee_cents'],'partner_cancellation_fee_mismatch');Config::require(($result['acknowledged']??false)===true,'partner_acknowledgement_missing');$result=array('acknowledged'=>true);
                    }
                    DB::tx(function()use($wpdb,$b,$result,$id,$lease,$job){
                        $current=Core::booking($b['public_id'],true);$active=DB::row('fulfilment','id',(int)$id,true);Config::require($active['locked_by']===$lease,'worker_lease_lost');
                        DB::update('fulfilment',array('result'=>Config::seal($result)),array('id'=>$id,'locked_by'=>$lease));
                        if($job['action']==='confirm') { Config::require($current['state']!=='cancelled','booking_cancelled');Core::confirm($b['public_id']); }
                        elseif($job['action']==='cancel')Core::cancel($b['public_id']);
                        elseif($current['state']==='cancelled')self::enqueue($current,'cancel');
                    });
                }
                DB::update( 'fulfilment', array( 'status'=>'succeeded','locked_until'=>0,'error_code'=>'' ), array('id'=>$id,'locked_by'=>$lease) );
            } catch ( \Throwable $e ) {
                $attempts=(int)$job['attempts']+1; $fatal=in_array($e->getMessage(),array('paid_capacity_needs_review','partner_hold_proof_invalid','adapter_configuration_changed','partner_cancellation_fee_mismatch'),true);
                if($fatal||$attempts>=8)Automation::event('attention:'.$id.':'.$attempts,'operations.attention_required','transactional',array('booking_id'=>$b['public_id'],'partner_id'=>(int)$b['partner_id'],'action'=>$job['action']));
                DB::update('fulfilment',array('status'=>$fatal||$attempts>=8?'attention':'pending','attempts'=>$attempts,'locked_until'=>0,'next_attempt'=>time()+min(3600,30*(2**min($attempts,6))),'error_code'=>in_array($e->getMessage(),array('paid_capacity_needs_review','partner_hold_proof_invalid','adapter_configuration_changed','partner_acknowledgement_missing','manual_rate_missing','partner_cancellation_fee_mismatch'),true)?$e->getMessage():'partner_operation_failed'),array('id'=>$id,'locked_by'=>$lease));
            } finally { $wpdb->delete(DB::table('operation_locks'),array('booking_id'=>$candidate['booking_id'],'lease'=>$lease)); }
        }
    }
    public static function retry(int $id):void { $job=DB::row('fulfilment','id',$id); Config::require((bool)$job,'job_not_found'); $b=DB::row('bookings','id',$job['booking_id']); Tenants::authorize((int)$b['partner_id'],true); Config::require($job['status']==='attention','job_not_retryable'); DB::update('fulfilment',array('status'=>'pending','next_attempt'=>0,'locked_until'=>0,'error_code'=>''),array('id'=>$id,'status'=>'attention')); Operations::audit('fulfilment.retry_requested','booking',(int)$b['id']); }
    public static function demo_complete( string $reference ): array {
        Config::require( defined('VPM_BOOKING_DEMO_MODE') && VPM_BOOKING_DEMO_MODE === true, 'demo_disabled' ); $b=Core::booking($reference); Tenants::authorize((int)$b['partner_id']);
        Config::require( !empty(Core::snapshot($b)['sandbox_demo']), 'not_a_demo_booking' ); global $wpdb;
        DB::query($wpdb->prepare('INSERT INTO '.DB::table('demo').' (booking_id,state,created_at) VALUES (%d,%s,%d) ON DUPLICATE KEY UPDATE state=VALUES(state)',$b['id'],'simulation_completed',time()));
        return array('reference'=>$reference,'simulation'=>true,'payment'=>'not_charged','booking'=>'not_confirmed');
    }
}
