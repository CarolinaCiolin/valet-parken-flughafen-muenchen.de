<?php
// Loopback only; isolated synthetic partners. No external mail or payments.
define('WP_ADMIN',true);require '/var/www/html/wp-load.php';if(wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(2);
use VPM\Booking\{DB,Core,Tenants,Portal,Products,Adapters,PublicApi,Experience,Frontend,Broker,Config,Flow,Operations,Promotions,Vouchers};
global $wpdb;$tables=$wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->prefix.'vpm_booking_%'));foreach($tables as $table)if($wpdb->get_var("SELECT COUNT(*) FROM $table"))throw new Exception('Nonempty tables refused');
$old=[];foreach(['vpm_booking_products','vpm_booking_frontend_enabled','vpm_booking_direct_messages']as $k)$old[$k]=get_option($k,null);$users=[];$checks=[];
function xok($value,$label){global $checks;if(!$value)throw new Exception($label);$checks[]=$label;}
function xreject($fn,$code){try{$fn();}catch(Throwable $e){xok($e->getMessage()===$code,'Reject '.$code);return;}throw new Exception('Expected '.$code);}
add_filter('pre_http_request',fn()=>new WP_Error('test_external_blocked'));add_filter('pre_wp_mail','__return_true');
try {
 wp_set_current_user(1);$p=Core::partner(['airport'=>'MUC','name'=>'Experience A','commission_bps'=>2000,'stripe_account'=>'']);$q=Core::partner(['airport'=>'STR','name'=>'Experience B','commission_bps'=>2000,'stripe_account'=>'']);
 $uid=wp_insert_user(['user_login'=>'experience_'.bin2hex(random_bytes(4)),'user_pass'=>bin2hex(random_bytes(20)),'user_email'=>'experience@example.invalid','role'=>'subscriber']);$users[]=$uid;Tenants::assign((int)$p['id'],$uid,'owner');wp_set_current_user($uid);
 xok(Experience::partner_user(wp_get_current_user()),'Assigned subscriber recognized as partner; admin excluded');xok(count(Tenants::partners())===1,'Only assigned partner listed');
 xreject(fn()=>Portal::handle((int)$q['id'],['portal_action'=>'connection','mode'=>'manual']),'portal_forbidden');
 xok(!str_contains(Experience::errors('vendor error secret-123'),'secret-123'),'Unknown backend exception does not disclose adapter secrets');
 xok(Experience::euros('12,50')===1250&&Experience::euros('0')===0&&Experience::euros('0.09')===9,'Exact decimal euro conversion without floating point');xreject(fn()=>Experience::euros('1.234,50'),'invalid_euro_amount');xreject(fn()=>Experience::euros('-1'),'invalid_euro_amount');
 Portal::handle((int)$p['id'],['portal_action'=>'product','ui_euros'=>'1','name_de'=>'Experience Außen','name_en'=>'Outdoor demo','parking_type'=>'outdoor','addons'=>['wash'=>['enabled'=>'1','label_de'=>'Wäsche','label_en'=>'Wash','unit_amount_cents'=>'70,00','max_quantity'=>'1']]]);$product=array_key_last(Products::catalogue());
 Portal::handle((int)$p['id'],['portal_action'=>'connection','mode'=>'manual']);Portal::handle((int)$p['id'],['portal_action'=>'rate','ui_euros'=>'1','product_id'=>$product,'price_cents'=>'10,50','pricing'=>'24h','enabled'=>'1']);
 Portal::handle((int)$p['id'],['portal_action'=>'capacity','product_id'=>$product,'capacity'=>'20','charging_capacity'=>'2']);
 Portal::handle((int)$p['id'],['portal_action'=>'capacity','product_id'=>$product,'capacity'=>'0','charging_capacity'=>'0']);
 $rate=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.DB::table('rates').' WHERE partner_id=%d',$p['id']),ARRAY_A);xok((int)$rate['capacity']===0&&(int)$rate['charging_capacity']===0,'Owner saves 0/0 on an unreserved pool');xok((int)$rate['price_cents']===1050,'Euro tariff stored as exact integer cents');
 xreject(fn()=>Portal::handle((int)$p['id'],['portal_action'=>'capacity','product_id'=>$product,'capacity'=>'-1','charging_capacity'=>'0']),'invalid_integer');xreject(fn()=>Portal::handle((int)$p['id'],['portal_action'=>'capacity','product_id'=>$product,'capacity'=>'1','charging_capacity'=>'2']),'charging_exceeds_capacity');
 Portal::handle((int)$p['id'],['portal_action'=>'capacity','product_id'=>$product,'capacity'=>'20','charging_capacity'=>'2']);
 Portal::handle((int)$p['id'],['portal_action'=>'services','ui_euros'=>'1','handover_minutes'=>'180','services'=>['valet'=>['enabled'=>'1','fee_cents'=>'20,00','fee_basis'=>'booking','night_arrival_cents'=>'0','night_return_cents'=>'0','night_start'=>'22:00','night_end'=>'06:00']]]);
 update_option('vpm_booking_frontend_enabled',true);$d=['airport'=>'MUC','departure'=>gmdate('Y-m-d',time()+10*DAY_IN_SECONDS).'T12:00','return'=>gmdate('Y-m-d',time()+17*DAY_IN_SECONDS).'T12:00','service'=>'valet'];
 $all=PublicApi::offers($d);xok(count($all['offers'])===1&&$all['offers'][0]['amount_cents']===9350,'Manual parking price plus valet fee calculated');xok($all['offers'][0]['addons'][0]['unit_amount_cents']===7000,'Euro extra price preserved');
 $none=PublicApi::offers($d+['parking_type'=>'underground']);xok(count($none['offers'])===0&&count($none['alternatives'])===1&&$none['alternatives'][0]['parking_type']==='outdoor','Unavailable type returns genuinely available alternative');
 $none=PublicApi::offers($d+['preferred_partner'=>(string)$q['id']]);xok(count($none['offers'])===0&&count($none['alternatives'])===1,'Unavailable preferred partner does not silently change provider');
 xreject(fn()=>PublicApi::offers($d+['parking_type'=>'fake']),'invalid_parking_type');
 // Rendering with WP_ADMIN true deliberately disables interaction; inspect semantic markup only.
 $markup=Frontend::form(['airport'=>'MUC','partner'=>(string)$p['id'],'layout'=>'compact']);xok(str_contains($markup,'name="airport" required')&&!str_contains($markup,'name="airport_scope"'),'Airport shortcode defaults to editable selection');xok(str_contains($markup,'name="preferred_partner"')&&str_contains($markup,'name="parking_type"'),'Provider and parking type search controls render');xok(str_contains($markup,'data-layout="compact"'),'Compact embedding available');
 xok(str_contains(Frontend::legacy_form(['airport'=>'MUC','airport_mode'=>'fixed']),'name="airport_scope"'),'Explicit fixed scope retained for intentional integrations');
 $general=Frontend::form(['locale'=>'de']);xok(str_contains($general,'value="">Flughafen auswählen'),'General form provides an empty airport choice');
 $customer=['kind'=>'private','first_name'=>'Max','last_name'=>'Mustermann','phone'=>'+49 000 000000','plate'=>'TEST 123','make'=>'Testfahrzeug'];
 xreject(fn()=>PublicApi::request_v2(['quote_id'=>$all['offers'][0]['quote_id'],'email'=>'customer@example.invalid']),'missing_customer_details');
 xreject(fn()=>Experience::customer($customer+['company'=>''], 'shuttle',4),'invalid_integer');$too_many=$customer+['passengers'=>'5'];xreject(fn()=>Experience::customer($too_many,'shuttle',4),'invalid_integer');
 $business=$customer;$business['kind']='business';xreject(fn()=>Experience::customer($business),'missing_customer_company');
 $input=['quote_id'=>$all['offers'][0]['quote_id'],'email'=>'customer@example.invalid','customer'=>$customer];$created=PublicApi::request_v2($input);$booking=Core::booking($created['reference']);
 xok(str_starts_with($created['booking_number'],'VPM-'),'New request returns readable booking number');xok(!str_contains($booking['snapshot'],'Mustermann')&&Experience::customer_data($booking)['last_name']==='Mustermann','Customer details encrypted at rest and recoverable only through authorized views');
 $view=Tenants::bookings((int)$p['id'])[0];xok($view['customer']['plate']==='TEST 123'&&$view['email']!=='customer@example.invalid','Partner sees vehicle identification and relay alias');
 update_option('vpm_booking_direct_messages',true);Broker::submit((int)$p['id'],$booking['public_id'],'Testnachricht ohne Einzelfreigabe','experience-direct-1');Broker::submit((int)$p['id'],$booking['public_id'],'Testnachricht ohne Einzelfreigabe','experience-direct-1');
 xok((int)$wpdb->get_var('SELECT COUNT(*) FROM '.DB::table('messages')." WHERE state='queued'")===1,'Direct partner messages queued idempotently without individual approval');
 xok(!str_contains(wp_json_encode(Broker::adapter($booking)),'customer@example.invalid'),'Adapter retains customer privacy with new identity fields');
 xreject(fn()=>Portal::handle((int)$p['id'],['portal_action'=>'cancel','reference'=>$booking['public_id']]),'cancellation_confirmation_required');
 Portal::handle((int)$p['id'],['portal_action'=>'cancel','reference'=>$booking['public_id'],'acknowledged'=>'1','expected_fee'=>'0','expected_refund'=>'0']);Flow::run();xok(Core::booking($booking['public_id'])['state']==='cancelled','Confirmed cancellation runs through worker and releases unpaid booking');
 wp_set_current_user(1);$campaign=Promotions::create(['name'=>'MUC Testaktion','code'=>'MUCSCOPE','airport'=>'MUC','kind'=>'percent','value'=>1000,'starts_at'=>time()-60,'ends_at'=>time()+DAY_IN_SECONDS,'budget_cents'=>10000,'max_uses'=>10,'max_per_customer'=>1,'enabled'=>1]);xok($campaign['coupon']['airport']==='MUC','Advanced action retains airport scope');xreject(fn()=>Vouchers::evaluate($campaign['coupon'],$q,10000,'scope@example.invalid'),'coupon_wrong_offer');
 wp_set_current_user(1);xok(!Experience::partner_user(wp_get_current_user()),'Platform administrator not redirected as partner');
 file_put_contents('/tmp/vpm-booking-experience-report.json',json_encode(['checks'=>$checks,'count'=>count($checks)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo count($checks)." experience checks passed\n";
} finally {
 wp_set_current_user(1);foreach(array_reverse($tables)as $table)$wpdb->query("DELETE FROM $table");foreach($old as $key=>$value){if($value===null)delete_option($key);else update_option($key,$value);}require_once ABSPATH.'wp-admin/includes/user.php';foreach($users as $uid)wp_delete_user($uid);
}
