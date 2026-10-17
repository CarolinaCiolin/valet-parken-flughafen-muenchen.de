<?php
if('1'!==getenv('VPM_LOCAL_SETUP'))exit(2);
define('WP_ADMIN',true);require '/var/www/html/wp-load.php';if(wp_parse_url(home_url(),PHP_URL_HOST)!=='127.0.0.1')exit(3);wp_set_current_user(1);
if(($argv[1]??'')==='restore'){
 if(is_readable('/tmp/vpm-english-review-ids.json')){foreach(json_decode(file_get_contents('/tmp/vpm-english-review-ids.json'),true)as $id)wp_update_post(['ID'=>$id,'post_status'=>'draft']);unlink('/tmp/vpm-english-review-ids.json');}
 echo "Local English review pages restored to drafts.\n";return;
}
$locations=get_option('theme_mods_'.get_stylesheet())['nav_menu_locations']??[];foreach($locations as $key=>$id){if($id&&!str_contains($key,'___')&&(PLL()->options['nav_menus'][get_stylesheet()][$key]['de']??0)!==$id)throw new Exception('German menu mapping changed');}
$before=[];foreach(get_posts(['post_type'=>['page','vpm_provider'],'post_status'=>'any','numberposts'=>-1,'lang'=>'de']) as $p)$before[$p->ID]=hash('sha256',$p->post_content.serialize(get_post_meta($p->ID)));
$r=vpm_import_english();if(is_wp_error($r))throw new Exception($r->get_error_message());if(!str_starts_with($r,'0 '))throw new Exception('Repeat import duplicated items');
foreach($before as $id=>$hash)if($hash!==hash('sha256',get_post_field('post_content',$id).serialize(get_post_meta($id))))throw new Exception('Original modified');
$en=get_posts(['post_type'=>['page','vpm_provider'],'post_status'=>'any','numberposts'=>-1,'lang'=>'en']);if(count($en)!==11)throw new Exception('English count mismatch');
foreach($en as $p){if($p->post_status!=='draft')throw new Exception('English unexpectedly published');if(!pll_get_post($p->ID,'de'))throw new Exception('Missing pair');}
wp_set_current_user(0);$r=vpm_import_english();if(!is_wp_error($r))throw new Exception('Anonymous import permitted');wp_set_current_user(1);
$legal=['pages'=>[['slug'=>'impressum','title'=>'Test legal fixture','content'=>'<p>Fixture</p><script>alert(1)</script>']]];
$r=vpm_import_legal_drafts($legal);if(is_wp_error($r)||$r!==1)throw new Exception('Legal import');$p=get_page_by_path('impressum');if($p->post_status!=='draft'||str_contains($p->post_content,'<script'))throw new Exception('Unsafe legal import');$r=vpm_import_legal_drafts($legal);if($r!==0)throw new Exception('Duplicate legal');wp_delete_post($p->ID,true);
$ids=[];foreach($en as $p){if(get_post_meta($p->ID,'_vpm_layout_slug',true)==='ratgeber')continue;wp_update_post(['ID'=>$p->ID,'post_status'=>'publish']);$ids[]=$p->ID;}
file_put_contents('/tmp/vpm-english-review-ids.json',json_encode($ids));flush_rewrite_rules(false);
echo "PASS: import repeat, 11 drafts, pairs, unchanged originals, permissions, legal draft/sanitizing/repeat. Published only in local test for browser checks.\n";
