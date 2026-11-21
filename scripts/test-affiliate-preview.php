<?php
// Loopback-only integration: explicit drafts, exact links, approved variants and preserved source content.
define('WP_ADMIN',true);require '/var/www/html/wp-load.php';
if(wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(2);
wp_set_current_user(1);$checks=[];$created=[];$source=vpm_affiliate_source_page('preise');$original=$source->post_content;$profile=get_option('vpm_site_profile',null);
function apcheck($v,$label){global $checks;if(!$v)throw new Exception($label);$checks[]=$label;}
function aphrefs($html){preg_match_all('~href="([^"]+)"~',$html,$m);return array_map(fn($s)=>html_entity_decode($s,ENT_QUOTES,'UTF-8'),$m[1]);}
try{
 $update=json_decode(file_get_contents(WP_PLUGIN_DIR.'/vpm-content-drafts/updates/affiliate-2026-11.json'),true);
 $content=str_replace('89,99 €','100,49 €',$original);wp_update_post(['ID'=>$source->ID,'post_content'=>$content]);
 $before=[];foreach(['startseite','preise','valet-parken-sicherheit']as $slug)$before[$slug]=get_post_field('post_content',vpm_affiliate_source_page($slug)->ID);
 $r=vpm_prepare_affiliate_preview();if(is_wp_error($r))throw new Exception($r->get_error_message());$created=array_merge(array_values($r['pages']),$r['providers']);
 foreach($before as $slug=>$c)apcheck(get_post_field('post_content',vpm_affiliate_source_page($slug)->ID)===$c,'Source '.$slug.' unchanged by draft builder');
 apcheck(count($r['providers'])===13&&count($r['pages'])===3,'Thirteen provider copies and three pages prepared');
 foreach($created as $id)apcheck(get_post_status($id)==='draft','Prepared object '.$id.' stays draft');
 $by_name=[];foreach($r['providers']as $id)$by_name[get_the_title($id)]=$id;
 apcheck(get_post_meta($by_name['Park Drive Fly'],'_vpm_price',true)==='ab 100,49 € für 8 Tage','Current source price wins without rounding cents');
 foreach(get_posts(['post_type'=>'vpm_provider','post_status'=>['publish','draft'],'numberposts'=>-1,'lang'=>'','suppress_filters'=>true])as $existing){
  if(get_post_meta($existing->ID,'_vpm_affiliate_preview',true)||!isset($by_name[$existing->post_title]))continue;
  if(function_exists('pll_get_post_language')&&pll_get_post_language($existing->ID,'slug')==='en')continue;
  foreach(['parkos_url','parkvia_url','parkenamflughafen_url']as $field)apcheck(get_post_meta($existing->ID,'_vpm_'.$field,true)===get_post_meta($by_name[$existing->post_title],'_vpm_'.$field,true),'Existing provider URL unchanged: '.$existing->post_title.' / '.$field);
 }
 $parkos=[];$shared=0;foreach($update['providers']as $p){
  $id=$by_name[$p['name']]??0;apcheck((bool)$id,'Provider copy exists: '.$p['name']);$platform=$p['platform']??'parkenamflughafen';$url=vpm_affiliate_offer_url($p,$update);
  apcheck(get_post_meta($id,'_vpm_'.$platform.'_url',true)===$url,'Exact approved tracking URL: '.$p['name']);
  if($platform==='parkos'){$parkos[]=$p;apcheck(get_post_meta($id,'_vpm_rating',true)===$p['rating'],'Correct valet-only rating and visible count: '.$p['name']);apcheck(get_post_meta($id,'_vpm_features',true)===implode("\n",$p['features']),'Only approved features: '.$p['name']);}
  else{++$shared;apcheck(get_post_meta($id,'_vpm_rating',true)==='','No invented rating: '.$p['name']);}
 }
 apcheck(count($parkos)===4&&$shared===3,'Four new Parkos cards plus three previously prepared offers');
 $prices=get_post_field('post_content',$r['pages']['preise']);preg_match('~<tbody>(.*?)</tbody>~s',$prices,$b);preg_match_all('~<tr>.*?</tr>~s',$b[1],$rows);$cents=array_map(fn($row)=>vpm_affiliate_price_number(vpm_affiliate_price_cells($row)[1]),$rows[0]);$sorted=$cents;sort($sorted);
 apcheck($cents===$sorted&&count($cents)===14,'All fourteen price rows including airport sorted by minimum price');
 foreach($parkos as $p){$row=current(array_filter($rows[0],fn($row)=>str_contains(html_entity_decode($row),$p['name'])));apcheck((bool)$row,'Price row exists: '.$p['name']);$cells=vpm_affiliate_price_cells($row);apcheck(vpm_affiliate_price_number($cells[1])===$p['cents'],'Start and table minimum price agree: '.$p['name']);apcheck(in_array($p['affiliate_url'],aphrefs($row),true),'Table link exact: '.$p['name']);}
 apcheck(str_contains($prices,'90 € außen / 105 € überdacht'),'0049 outside and covered prices remain distinct');
 $safety=get_post_field('post_content',$r['pages']['valet-parken-sicherheit']);foreach($parkos as $p){
  $title=esc_html($p['name']);preg_match('~<h3[^>]*>'.preg_quote($title,'~').'</h3>(.*?)(?=<h3|$)~s',$safety,$m);apcheck(!empty($m[1]),'Safety section exists: '.$p['name']);$block=html_entity_decode($m[1]);
  foreach($p['safety']as $item)apcheck(str_contains($block,$item),'Approved safety item: '.$p['name'].' / '.$item);
  apcheck(in_array($p['affiliate_url'],aphrefs($m[1]),true),'Safety link exact: '.$p['name']);
  if(in_array($p['id'],['parkxpress-ek','0049-richtig-parken'],true))apcheck(!str_contains($block,'Videoüberwachung'),'No unsupported surveillance for '.$p['name']);
  if($p['id']==='ben-parkt-valet-park-service')apcheck(!preg_match('/Übergabeprotokoll|Fotodokumentation|Fahrzeugcheck/u',$block),'Ben-Parkt anecdotal check not turned into standard service');
 }
 foreach(['preise'=>$prices,'valet-parken-sicherheit'=>$safety,'startseite'=>get_post_field('post_content',$r['pages']['startseite'])]as $slug=>$copy)foreach(aphrefs($before[$slug])as $url)apcheck(in_array($url,aphrefs($copy),true),'Existing link preserved on '.$slug);
 $start=get_post_field('post_content',$r['pages']['startseite']);apcheck(in_array($update['general_parkos_url'],aphrefs($start),true),'Additional general Parkos CTA uses its exact supplied link');
 $old_preview=$GLOBALS['wp_query']->is_preview;$GLOBALS['wp_query']->is_preview=true;$cards=vpm_render_provider_cards(['ids'=>implode(',',$r['providers'])]);$GLOBALS['wp_query']->is_preview=$old_preview;
 apcheck(preg_match_all('/class="vpm-provider-card(?: vpm-provider-card-compact)?"/',$cards)===13,'All thirteen draft cards actually render');
 foreach($parkos as $p){apcheck(in_array($p['affiliate_url'],aphrefs($cards),true),'Rendered card link exact: '.$p['name']);apcheck(str_contains($cards,esc_html($p['rating'])),'Rendered rating visible: '.$p['name']);}
 apcheck(!str_contains($cards,'Link zur Außenvariante; überdachte Variante bei Parkos auswählen.'),'0049 home card excludes variant note');
 apcheck(vpm_affiliate_price_label(17490)==='174,90 €'&&vpm_affiliate_price_label(17400)==='174 €','Whole euros and cents stay correct');
 apcheck(vpm_affiliate_refresh_count('9 Anbieter, 10 bis 15 Minuten',13)==='13 Anbieter, 10 bis 15 Minuten','Update totals without changing unrelated timings');
 wp_set_current_user(0);apcheck(is_wp_error(vpm_prepare_affiliate_preview()),'Anonymous caller cannot prepare drafts');wp_set_current_user(1);update_option('vpm_site_profile',vpm_profile_preset('umbrella'));apcheck(is_wp_error(vpm_prepare_affiliate_preview()),'Munich offers stay blocked for umbrella profile');
 file_put_contents('/tmp/vpm-affiliate-preview-report.json',wp_json_encode(['result'=>'passed','count'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo count($checks)." affiliate preview checks passed\n";
}finally{wp_set_current_user(1);wp_update_post(['ID'=>$source->ID,'post_content'=>$original]);foreach(array_reverse($created)as $id)wp_delete_post($id,true);if($profile===null)delete_option('vpm_site_profile');else update_option('vpm_site_profile',$profile);}
