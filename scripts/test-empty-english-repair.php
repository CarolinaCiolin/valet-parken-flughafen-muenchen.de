<?php
if(getenv('VPM_LOCAL_SETUP')!=='1')exit(2);define('WP_ADMIN',true);require '/var/www/html/wp-load.php';if(wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(3);wp_set_current_user(1);
$source=(int)get_option('page_on_front');$id=pll_get_post($source,'en');if(!$id)throw new Exception('English fixture missing');
$backup=get_post($id,ARRAY_A);$meta=get_post_meta($id);$de=get_post_field('post_content',$source);
try{
 wp_update_post(['ID'=>$id,'post_name'=>'startseite-english-placeholder','post_title'=>'Valet-Parken Flughafen München – English','post_content'=>'','post_status'=>'publish']);
 $r=vpm_import_english();if(is_wp_error($r)||get_post_field('post_content',$id)!=='')throw new Exception('Normal import unexpectedly overwrote placeholder');
 $r=vpm_repair_empty_english();if(is_wp_error($r))throw new Exception($r->get_error_message());
 if(pll_get_post($source,'en')!==$id||get_post_field('post_title',$id)!=='Munich Airport Valet Parking'||get_post_status($id)!=='draft'||!str_contains(get_post_field('post_content',$id),'vpm_providers'))throw new Exception('Repair failed');
 if(get_post_field('post_name',$id)!=='home')throw new Exception('English slug not corrected');
 if(get_post_meta($id,'_vpm_layout_slug',true)!=='startseite'||get_post_meta($id,'_wp_page_template',true)!=='templates/full-width.php')throw new Exception('Layout missing');
 $content=get_post_field('post_content',$id);vpm_repair_empty_english();if(get_post_field('post_content',$id)!==$content)throw new Exception('Repeated repair overwrote content');
 wp_update_post(['ID'=>$id,'post_content'=>'<!-- wp:paragraph --><p>My own English copy.</p><!-- /wp:paragraph -->']);vpm_repair_empty_english();if(!str_contains(get_post_field('post_content',$id),'My own English copy.'))throw new Exception('Custom copy overwritten');
 wp_update_post(['ID'=>$id,'post_content'=>'']);update_post_meta($id,'_elementor_data','[{"id":"local-fixture"}]');vpm_repair_empty_english();if(get_post_field('post_content',$id)!=='')throw new Exception('Elementor content overwritten');
 if(get_post_field('post_content',$source)!==$de)throw new Exception('German content changed');
 echo "PASS: existing published empty translation repaired in place as draft with full content/layout; repeat, own copy, Elementor and German content protected.\n";
}finally{
 wp_update_post($backup);foreach(array_keys(get_post_meta($id))as $k)delete_post_meta($id,$k);foreach($meta as $k=>$values)foreach($values as $v)add_post_meta($id,$k,maybe_unserialize($v));
}
