<?php
// Loopback-only product/price/airport integrity tests; no live partner transport.
define( 'WP_ADMIN', true ); require '/var/www/html/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== '127.0.0.1' ) { exit(2); }
use VPM\Booking\DB; use VPM\Booking\Core; use VPM\Booking\Products; use VPM\Booking\PublicApi; use VPM\Booking\Frontend; use VPM\Booking\Customer; use VPM\Booking\Config;
global $wpdb; $tables=$wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->prefix.'vpm_booking_%')); foreach($tables as $t){if($wpdb->get_var("SELECT COUNT(*) FROM $t"))throw new Exception('Nonempty tables refused');}
$options=[];foreach(['vpm_booking_frontend_enabled','vpm_booking_products']as $k)$options[$k]=get_option($k,null);
$checks=[];function check_product($v,$label){global $checks;if(!$v)throw new Exception($label);$checks[]=$label;}function reject_product($fn,$code){try{$fn();}catch(Throwable $e){check_product($e->getMessage()===$code,'reject:'.$code);return;}throw new Exception('Expected '.$code);}
add_filter('pre_http_request',function(){return new WP_Error('fixture_block');});add_filter('pre_wp_mail','__return_true');wp_set_current_user(1);
try{
 $fixed=Frontend::form(['airport'=>'STR','locale'=>'de']);check_product(str_contains($fixed,'Stuttgart (STR)')&&!preg_match('/<select[^>]+name="airport"/',$fixed)&&str_contains($fixed,'name="airport_scope"'),'Local shortcode locks airport without airport dropdown');
 check_product(str_contains(Frontend::form([]),'name="airport" required>'),'General shortcode retains airport selection');
 check_product(str_contains(Frontend::form(['airport'=>'STR','airport_mode'=>'select']),'name="airport" required>'),'Explicit selection mode allows preselected airport');
 check_product(str_contains(Frontend::form(['airport'=>'XXX']),'role="alert"'),'Invalid local airport never silently becomes a global form');
 $p=Core::partner(['airport'=>'STR','name'=>'Local outdoor fixture','stripe_account'=>'acct_productfixture','commission_bps'=>2000]);$p2=Core::partner(['airport'=>'STR','name'=>'Local underground fixture','stripe_account'=>'acct_productfixture2','commission_bps'=>2000]);
 $template=['partner_id'=>(string)$p['id'],'name_de'=>'Außenparken','name_en'=>'Outdoor parking','parking_type'=>'outdoor','attributes_de'=>"Schlüsselübergabe am Terminal\nKostenlose Umbuchung laut Angebot",'attributes_en'=>"Terminal handover\nFree changes subject to offer",'addons'=>[]];
 foreach(['wash'=>[1500,1],'charging'=>[700,3],'refuelling'=>[3000,1]]as $id=>$v)$template['addons'][$id]=['enabled'=>'1','label_de'=>$id,'label_en'=>$id,'description_de'=>'Festes Testpaket','description_en'=>'Fixed test package','unit_amount_cents'=>(string)$v[0],'max_quantity'=>(string)$v[1]];
 Products::save_template($template);$catalog=Products::catalogue();$product_id=array_key_last($catalog);check_product(count($catalog[$product_id]['offer']['addons'])===3,'Partner template stores separately priced extras');
 reject_product(function()use($product_id,$p2){Products::enrich(['catalog_product_id'=>$product_id],(int)$p2['id']);},'catalog_product_partner_mismatch');
 $bad=$template;$bad['addons']['wash']['unit_amount_cents']='-1';reject_product(function()use($bad){Products::save_template($bad);},'invalid_integer');
 update_option('vpm_booking_frontend_enabled',true);
 $adapter=function()use($p,$p2,$product_id){return [['partner_id'=>(int)$p['id'],'base_cents'=>10000,'currency'=>'eur','terms'=>'Local test terms','catalog_product_id'=>$product_id,'available_addon_ids'=>['wash','charging']],['partner_id'=>(int)$p2['id'],'base_cents'=>18000,'currency'=>'eur','terms'=>'Local test terms','product_name'=>['de'=>'Tiefgaragenangebot','en'=>'Underground offer'],'parking_type'=>'underground','attributes'=>['24/7 Betrieb']]];};add_filter('vpm_booking_quote_offers',$adapter);
 $scope=Products::scope('STR');$context=['airport'=>'STR','airport_scope'=>$scope,'locale'=>'en','departure'=>gmdate('Y-m-d',time()+10*DAY_IN_SECONDS).'T10:00','return'=>gmdate('Y-m-d',time()+18*DAY_IN_SECONDS).'T12:00'];
 reject_product(function()use($context){PublicApi::offers(array_merge($context,['airport'=>'MUC']));},'airport_scope_mismatch');
 $offers=PublicApi::offers($context)['offers'];check_product(count($offers)===2&&$offers[0]['product_name']==='Outdoor parking'&&$offers[1]['parking_type']==='underground','Available partner products expose independent types/prices and translated names');
 check_product(array_column($offers[0]['addons'],'id')===['wash','charging'],'Only adapter-confirmed extras exposed; configured refuelling omitted');
 check_product($offers[0]['addons'][1]['unit_amount_cents']===700&&$offers[0]['addons'][1]['max_quantity']===3,'Unit price and quantity limit preserved');
 $input=['quote_id'=>$offers[0]['quote_id'],'airport_scope'=>$scope,'locale'=>'en','email'=>'product-fixture@example.invalid','addons'=>[['id'=>'wash','quantity'=>1],['id'=>'charging','quantity'=>2]]];
 reject_product(function()use($input){PublicApi::request(array_merge($input,['airport_scope'=>'']));},'airport_scope_mismatch');
 reject_product(function()use($input){PublicApi::request(array_merge($input,['addons'=>[['id'=>'refuelling','quantity'=>1]]]));},'addon_not_available');
 reject_product(function()use($input){PublicApi::request(array_merge($input,['addons'=>[['id'=>'charging','quantity'=>4]]]));},'invalid_integer');
 reject_product(function()use($input){PublicApi::request(array_merge($input,['addons'=>[['id'=>'wash','quantity'=>1,'unit_amount_cents'=>1]]]));},'invalid_addon_selection');
 reject_product(function()use($input){PublicApi::request(array_merge($input,['addons'=>[['id'=>'wash','quantity'=>1],['id'=>'wash','quantity'=>1]]]));},'invalid_addon_selection');
 reject_product(function()use($input){PublicApi::request(array_merge($input,['addons'=>[['id'=>'charging','quantity'=>1.5]]]));},'invalid_integer');
 // Freeze current quote even if template prices are edited later.
 $edited=$template;$edited['product_id']=$product_id;$edited['addons']['wash']['unit_amount_cents']='2500';Products::save_template($edited);
 $received=PublicApi::request($input);check_product($received['amount_cents']===12900,'Server calculates base plus wash plus two charging units from stored quote');
 $again=PublicApi::request(array_merge($input,['addons'=>array_reverse($input['addons'])]));check_product($received['reference']===$again['reference'],'Reordered addon selection is idempotent');
 reject_product(function()use($input){PublicApi::request(array_merge($input,['addons'=>[]]));},'idempotency_conflict');
 $b=Core::booking($received['reference']);$q=Core::snapshot($b);check_product($q['offer_selection']['parking_cents']===10000&&count($q['offer_selection']['addons'])===2&&$q['base']===12900,'Immutable financial snapshot includes complete service breakdown');
 $link=Customer::issue($b);parse_str(wp_parse_url($link['url'],PHP_URL_FRAGMENT),$f);check_product(Customer::view($f['token'])['offer_selection']['total_cents']===12900,'Customer view retains selected extras and total');
 $new=PublicApi::offers($context)['offers'][0];check_product($new['addons'][0]['unit_amount_cents']===2500,'New quote receives updated configured price');
 // Old 0.3 quotes/bookings remain retryable without a new selection fingerprint.
 $legacy_offer=PublicApi::offers(array_diff_key($context,['airport_scope'=>1]))['offers'][1];DB::query($wpdb->prepare('UPDATE '.DB::table('quotes').' SET details=NULL WHERE public_id=%s',$legacy_offer['quote_id']));$row=DB::row('quotes','public_id',$legacy_offer['quote_id']);$travel=json_decode($row['context'],true);
 $legacy=Core::create(['partner_id'=>(int)$p2['id'],'base_cents'=>18000,'email'=>'legacy-product@example.invalid','locale'=>'de','travel'=>$travel,'offer_terms'=>$row['terms']],'public-'.$row['public_id']);$retry=PublicApi::request(['quote_id'=>$row['public_id'],'email'=>'legacy-product@example.invalid']);check_product($legacy['public_id']===$retry['reference'],'Legacy pre-products quote preserves idempotent booking retry');
 update_option('vpm_booking_db_version',3);DB::install();check_product(Core::snapshot(Core::booking($b['public_id']))['offer_selection']['total_cents']===12900,'Schema migration preserves existing service snapshot');
 file_put_contents('/tmp/vpm-booking-products-report.json',wp_json_encode(['count'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT));echo count($checks)." product and fixed-airport checks passed.\n";
}finally{foreach($tables as $t)$wpdb->query("DELETE FROM $t");foreach($options as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}}
