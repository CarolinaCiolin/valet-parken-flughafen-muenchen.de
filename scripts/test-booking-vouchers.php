<?php
// Local-only: coupon time boundaries, commission funding, opt-in loyalty and role isolation.
define('WP_ADMIN',true);define('VPM_BOOKING_AUTOMATION_URL','https://example.org/voucher-fixture');define('VPM_BOOKING_AUTOMATION_SECRET','voucher-fixture-secret-at-least-32-bytes');require '/var/www/html/wp-load.php';
if(wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(2);
use VPM\Booking\DB;use VPM\Booking\Core;use VPM\Booking\Vouchers;use VPM\Booking\Loyalty;use VPM\Booking\PublicApi;use VPM\Booking\Consent;use VPM\Booking\Config;use VPM\Booking\Finance;
global $wpdb;$tables=$wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->prefix.'vpm_booking_%'));foreach($tables as $t)if($wpdb->get_var("SELECT COUNT(*) FROM $t"))throw new Exception('Nonempty tables refused');
$options=[];foreach(['vpm_booking_frontend_enabled','vpm_booking_loyalty_enabled','vpm_booking_loyalty_starts','vpm_booking_loyalty_cursor','vpm_booking_automation_enabled','vpm_booking_consent_text','vpm_booking_consent_version']as $k)$options[$k]=get_option($k,null);
$checks=[];function check_v($v,$name){global $checks;if(!$v)throw new Exception($name);$checks[]=$name;}function reject_v($fn,$code){try{$fn();}catch(Throwable $e){check_v($e->getMessage()===$code,'reject:'.$code);return;}throw new Exception('Expected '.$code);}
add_filter('pre_http_request',function(){return new WP_Error('blocked_fixture');});add_filter('pre_wp_mail','__return_true');wp_set_current_user(1);
try{
 $p=Core::partner(['airport'=>'FRA','name'=>'Voucher test partner','stripe_account'=>'acct_vouchertest','commission_bps'=>2000]);$start=1577836800;$end=(new DateTimeImmutable('@'.$start))->add(new DateInterval('P2Y'))->getTimestamp();
 $coupon=Core::coupon(['code'=>'TWOYEARS','kind'=>'percent','value'=>500,'starts_at'=>$start,'ends_at'=>$end,'max_uses'=>1,'budget_cents'=>5000000]);
 check_v(Vouchers::evaluate($coupon,$p,10000,'voucher@example.invalid',$start+intdiv($end-$start,4)*3)===500,'Two-calendar-year coupon remains valid after 1.5 years');
 reject_v(function()use($coupon,$p,$end){Vouchers::evaluate($coupon,$p,10000,'voucher@example.invalid',$end+180*DAY_IN_SECONDS);},'coupon_expired');
 reject_v(function()use($coupon,$p,$end){Vouchers::evaluate($coupon,$p,10000,'voucher@example.invalid',$end);},'coupon_expired');
 reject_v(function()use($coupon,$p,$start){Vouchers::evaluate($coupon,$p,10000,'voucher@example.invalid',$start-1);},'coupon_not_started');
 update_option('vpm_booking_automation_enabled',true);update_option('vpm_booking_consent_text','Local fixture consent');update_option('vpm_booking_consent_version','voucher-fixture');
 Consent::request('voucher@example.invalid',true,'127.0.0.1');$c=DB::row('contacts','email','voucher@example.invalid');
 $out=$wpdb->get_row('SELECT payload FROM '.DB::table('outbox')." WHERE topic='marketing.confirmation_requested'",ARRAY_A);$payload=Config::open($out['payload']);parse_str(wp_parse_url($payload['confirmation_url'],PHP_URL_QUERY),$query);Consent::act($query['token'],'confirm');
 $b=Core::create(['partner_id'=>(int)$p['id'],'base_cents'=>10000,'email'=>'voucher@example.invalid'],'voucher-first-fixture');Core::confirm($b['public_id']);
 update_option('vpm_booking_loyalty_enabled',true);update_option('vpm_booking_loyalty_starts',time()-60);
 check_v(Loyalty::grant((int)$c['id'])===null,'Confirmed newsletter plus unpaid enquiry cannot earn voucher');
 DB::update('bookings',['payment_state'=>'paid','stripe_pi'=>'pi_voucherfixture'],['id'=>$b['id']]);
 update_option('vpm_booking_loyalty_enabled',false);check_v(Loyalty::grant((int)$c['id'])===null,'Loyalty campaign is explicitly opt-in for operator');update_option('vpm_booking_loyalty_enabled',true);
 $earned=Loyalty::grant((int)$c['id']);check_v($earned&&$earned['kind']==='percent'&&(int)$earned['value']===500,'First paid confirmed booking plus DOI earns 5-percent personal code');
 check_v((int)$earned['ends_at']===(new DateTimeImmutable('@'.$earned['starts_at']))->add(new DateInterval('P2Y'))->getTimestamp(),'Award lasts two calendar years from issuance');
 check_v(Loyalty::grant((int)$c['id'])===null&&(int)$wpdb->get_var('SELECT COUNT(*) FROM '.DB::table('loyalty'))===1,'Repeated worker/grant never issues second voucher');
 reject_v(function()use($earned,$p){Vouchers::evaluate($earned,$p,10000,'other@example.invalid');},'coupon_wrong_customer');
 $event=$wpdb->get_row('SELECT * FROM '.DB::table('outbox')." WHERE topic='loyalty.coupon_issued'",ARRAY_A);check_v($event['purpose']==='marketing'&&!str_contains($event['payload'],$earned['code']),'Voucher delivery uses encrypted consent-gated marketing outbox');
 update_option('vpm_booking_frontend_enabled',true);add_filter('vpm_booking_quote_offers',function()use($p){return [['partner_id'=>(int)$p['id'],'base_cents'=>10000,'currency'=>'eur','terms'=>'Fixture']];});
 $data=['airport'=>'FRA','departure'=>gmdate('Y-m-d',time()+10*DAY_IN_SECONDS).'T10:00','return'=>gmdate('Y-m-d',time()+18*DAY_IN_SECONDS).'T12:00'];$offer=PublicApi::offers($data)['offers'][0];$input=['quote_id'=>$offer['quote_id'],'email'=>'voucher@example.invalid','coupon'=>$earned['code']];
 $preview=PublicApi::preview($input);check_v($preview['amount_cents']===9500&&$preview['discount_cents']===500,'Read-only preview exposes correct discounted total');
 $fresh=DB::row('coupons','id',$earned['id']);check_v((int)$fresh['held_uses']===0&&(int)$fresh['used_uses']===0,'Preview never consumes or reserves voucher');
 check_v(!str_contains(wp_json_encode($preview),'commission')&&!str_contains(wp_json_encode($preview),'acct_'),'Public price preview hides commission and account');
 $received=PublicApi::request($input);$second=Core::booking($received['reference']);$q=Core::snapshot($second);check_v($q['charged']===9500&&$q['partner']===8000&&$q['platform']===1500,'Five-percent discount reduces platform commission only; partner remains unchanged');
 reject_v(function()use($input){PublicApi::preview($input);},'coupon_in_progress');
 DB::tx(function()use($second){Core::coupon_state(Core::booking($second['public_id'],true),'consumed');});
 reject_v(function()use($input){PublicApi::preview($input);},'coupon_redeemed');
 Consent::revoke_remote('voucher@example.invalid');$unused=Core::coupon(['code'=>'STILLVALID','kind'=>'percent','value'=>500,'starts_at'=>time()-1,'ends_at'=>time()+DAY_IN_SECONDS,'max_uses'=>1,'budget_cents'=>50000,'customer_hash'=>$earned['customer_hash']]);
 check_v(Vouchers::evaluate($unused,$p,10000,'voucher@example.invalid')===500,'Newsletter unsubscribe does not revoke an already issued valid benefit');
 reject_v(function(){Finance::quote(10000,400,500);},'subsidy_exceeds_commission_requires_funded_model');
 // Cookie-auth capability test via REST: booking operator is not a platform coupon administrator.
 $old_caps=get_user_meta(1,$wpdb->prefix.'capabilities',true);update_user_meta(1,$wpdb->prefix.'capabilities',['subscriber'=>true,'manage_vpm_bookings'=>true]);wp_set_current_user(0);wp_set_current_user(1);
 try{ $r=new WP_REST_Request('POST','/vpm-booking/v1/coupons');$r->set_header('Content-Type','application/json');$r->set_body('{}');$result=rest_do_request($r);check_v($result->get_status()===403,'Booking-management capability alone cannot issue platform coupons'); }
 finally{update_user_meta(1,$wpdb->prefix.'capabilities',$old_caps);wp_set_current_user(0);wp_set_current_user(1);}
 file_put_contents('/tmp/vpm-booking-vouchers-report.json',wp_json_encode(['count'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT));echo count($checks)." voucher/loyalty checks passed.\n";
}finally{foreach($tables as $t)$wpdb->query("DELETE FROM $t");foreach($options as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}}
