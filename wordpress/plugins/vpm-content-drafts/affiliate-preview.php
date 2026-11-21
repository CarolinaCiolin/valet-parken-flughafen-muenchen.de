<?php
/** Explicit Munich-only review copies. Never edits/publishes a live page on activation. */
if (!defined('ABSPATH')) exit;
function vpm_affiliate_price_cells($row) {
    preg_match_all('~<td\b[^>]*>(.*?)</td>~is',$row,$m);return array_map(function($v){return trim(html_entity_decode(wp_strip_all_tags($v),ENT_QUOTES,'UTF-8'));},$m[1]);
}
function vpm_affiliate_price_number($value) {
    if(!preg_match('/(\d[\d.]*)(?:,(\d{1,2}))?\s*€/u',$value,$m))return null;
    return (int)str_replace('.','',$m[1])*100+(int)str_pad($m[2]??'',2,'0');
}
function vpm_affiliate_price_label($cents) { return number_format($cents/100,$cents%100?2:0,',','').' €'; }
function vpm_affiliate_price_table($content,$update) {
    if(!preg_match('~<tbody\b[^>]*>(.*?)</tbody>~is',$content,$body,PREG_OFFSET_CAPTURE))return new WP_Error('price_table','Keine eindeutig lesbare Preistabelle gefunden.');
    preg_match_all('~<tr\b[^>]*>.*?</tr>~is',$body[1][0],$matches);$rows=$matches[0];$prices=array();$names=array();
    foreach($rows as $row){$cells=vpm_affiliate_price_cells($row);if(count($cells)<3||vpm_affiliate_price_number($cells[1])===null)return new WP_Error('price_row','Eine Preiszeile benötigt redaktionelle Prüfung.');$prices[$cells[0]]=$cells[1];$names[]=$cells[0];}
    foreach($update['providers']as $p){if(in_array($p['name'],$names,true))continue;$amount=vpm_affiliate_price_label($p['cents']);$rows[]='<tr><td>'.esc_html($p['name']).'</td><td>'.$amount.'</td><td>'.esc_html($p['parking']).'</td><td><a href="'.esc_url($update['affiliate_url']).'" rel="sponsored nofollow">Verfügbarkeit prüfen</a></td></tr>';$prices[$p['name']]=$amount;}
    usort($rows,function($a,$b){return vpm_affiliate_price_number(vpm_affiliate_price_cells($a)[1])<=>vpm_affiliate_price_number(vpm_affiliate_price_cells($b)[1]);});
    return array('content'=>substr_replace($content,implode('',$rows),$body[1][1],strlen($body[1][0])),'prices'=>$prices);
}
function vpm_affiliate_safety_block($provider,$url) {
    $html='<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">'.esc_html($provider['name']).'</h3><!-- /wp:heading -->';
    $html.='<!-- wp:list --><ul class="wp-block-list">';foreach($provider['safety']as $item)$html.='<!-- wp:list-item --><li>'.esc_html($item).'</li><!-- /wp:list-item -->';
    return $html.'</ul><!-- /wp:list --><!-- wp:paragraph --><p><a href="'.esc_url($url).'" rel="sponsored nofollow">Verfügbarkeit prüfen</a></p><!-- /wp:paragraph -->';
}
function vpm_affiliate_source_page($slug) {
    $posts=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private'),'numberposts'=>-1,'lang'=>'','suppress_filters'=>true));
    foreach($posts as $p)if(!get_post_meta($p->ID,'_vpm_affiliate_preview',true)&&($p->post_name===$slug||get_post_meta($p->ID,'_vpm_import_slug',true)===$slug))return $p;return null;
}
function vpm_prepare_affiliate_preview() {
    if(!current_user_can('manage_options'))return new WP_Error('forbidden','Keine Berechtigung.');
    if(function_exists('vpm_legacy_munich')&&!vpm_legacy_munich())return new WP_Error('site_profile','Dieses Angebotspaket ist ausschließlich für München.');
    if(!post_type_exists('vpm_provider'))return new WP_Error('providers_missing','Zuerst VPM Anbieter aktivieren.');
    $update=json_decode(file_get_contents(__DIR__.'/updates/affiliate-2026-11.json'),true);$sources=array();foreach(array('startseite','preise','valet-parken-sicherheit')as $slug){$p=vpm_affiliate_source_page($slug);if(!$p)return new WP_Error('source_missing','Quellseite fehlt: '.$slug);$sources[$slug]=$p;}
    $table=vpm_affiliate_price_table($sources['preise']->post_content,$update);if(is_wp_error($table))return $table;
    if(!preg_match('~\[vpm_providers\b[^\]]*\]~',$sources['startseite']->post_content))return new WP_Error('cards_missing','Die vorhandene Anbieterkomponente fehlt auf der Startseite.');
    $safety=$sources['valet-parken-sicherheit']->post_content;$marker='<p>Die Angaben beruhen auf den veröffentlichten Angebotsbeschreibungen';$at=strpos($safety,$marker);if($at===false)return new WP_Error('safety_layout','Sicherheitsabschnitt benötigt redaktionelle Zuordnung.');$block_at=strrpos(substr($safety,0,$at),'<!-- wp:paragraph');if($block_at!==false)$at=$block_at;$blocks='';foreach($update['providers']as $provider)if(!str_contains($safety,$provider['name']))$blocks.=vpm_affiliate_safety_block($provider,$update['affiliate_url']);$safety=substr_replace($safety,$blocks,$at,0);
    $result=array('pages'=>array(),'providers'=>array());$created=array();$batch=wp_generate_uuid4();
    $create=function($post,$meta=array())use(&$created,$batch){$id=wp_insert_post($post,true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());$created[]=$id;update_post_meta($id,'_vpm_affiliate_preview',$batch);foreach($meta as $k=>$v)update_post_meta($id,$k,$v);if(function_exists('pll_set_post_language'))pll_set_post_language($id,'de');return $id;};
    try{
        $originals=get_posts(array('post_type'=>'vpm_provider','post_status'=>array('publish','draft'),'numberposts'=>-1,'orderby'=>array('menu_order'=>'ASC','ID'=>'ASC'),'lang'=>'','suppress_filters'=>true));$by_name=array();
        foreach($originals as $p){if(get_post_meta($p->ID,'_vpm_affiliate_preview',true))continue;if(function_exists('pll_get_post_language')&&pll_get_post_language($p->ID,'slug')==='en')continue;$by_name[$p->post_title]=$p;}
        foreach($by_name as $name=>$p){$meta=array();foreach(vpm_provider_fields()as $key=>$unused)$meta['_vpm_'.$key]=get_post_meta($p->ID,'_vpm_'.$key,true);$thumbnail=get_post_thumbnail_id($p->ID);if($thumbnail)$meta['_thumbnail_id']=$thumbnail;foreach($update['price_aliases']as $alias=>$full)if($full===$name&&isset($table['prices'][$alias])){$amount=vpm_affiliate_price_number($table['prices'][$alias]);$meta['_vpm_price']='ab '.vpm_affiliate_price_label($amount).' für 8 Tage';}
            if(isset($table['prices'][$name]))$meta['_vpm_price']='ab '.vpm_affiliate_price_label(vpm_affiliate_price_number($table['prices'][$name])).' für 8 Tage';$result['providers'][]=$create(array('post_type'=>'vpm_provider','post_status'=>'draft','post_title'=>$name,'post_content'=>$p->post_content,'menu_order'=>$p->menu_order),$meta);
        }
        foreach($update['providers']as $i=>$p){if(isset($by_name[$p['name']]))continue;$amount=vpm_affiliate_price_number($table['prices'][$p['name']]);$result['providers'][]=$create(array('post_type'=>'vpm_provider','post_status'=>'draft','post_title'=>$p['name'],'menu_order'=>100+$i),array('_vpm_active'=>true,'_vpm_features'=>implode("\n",$p['features']),'_vpm_price'=>'ab '.vpm_affiliate_price_label($amount).' für 8 Tage','_vpm_parkenamflughafen_url'=>$update['affiliate_url'],'_vpm_parkenamflughafen_label'=>'Verfügbarkeit prüfen','_vpm_source_date'=>'Freigegebener Nutzerprompt; Preisbeispiel '.$update['price_period']));}
        $content=array('startseite'=>preg_replace('~\[vpm_providers\b[^\]]*\]~','[vpm_providers ids="'.implode(',',$result['providers']).'"]',$sources['startseite']->post_content),'preise'=>$table['content'],'valet-parken-sicherheit'=>$safety);
        foreach($sources as $slug=>$p){$id=$create(array('post_type'=>'page','post_status'=>'draft','post_title'=>$p->post_title.' – Vorschau','post_name'=>'vorschau-partner-'.$slug,'post_content'=>$content[$slug]),array('_vpm_layout_slug'=>$slug,'_wp_page_template'=>get_post_meta($p->ID,'_wp_page_template',true)));$result['pages'][$slug]=$id;}
        return $result;
    }catch(Throwable $e){foreach(array_reverse($created)as $id)wp_delete_post($id,true);return new WP_Error('preview_failed','Vorschau konnte nicht vollständig vorbereitet werden. Originalinhalte blieben erhalten.');}
}
add_action('admin_menu',function(){add_management_page('VPM Partner-Vorschau','VPM Partner-Vorschau','manage_options','vpm-affiliate-preview',function(){if(!current_user_can('manage_options'))return;echo '<div class="wrap"><h1>Partner-Vorschau November 2026</h1><p>Erzeugt getrennte Entwürfe von Startseite, Preisvergleich, Sicherheitsvergleich und Anbieterkarten. Vorhandene Seiten und Anbieter werden nicht geändert oder veröffentlicht. Bestehende Preise werden aus der aktuellen Preistabelle übernommen. Kein Video-Platzhalter.</p>';if(isset($_POST['vpm_affiliate_preview'])){check_admin_referer('vpm_affiliate_preview');$r=vpm_prepare_affiliate_preview();if(is_wp_error($r))echo '<div class="notice notice-error"><p>'.esc_html($r->get_error_message()).'</p></div>';else{echo '<div class="notice notice-success"><p>Entwürfe erstellt.</p></div><ul>';foreach($r['pages']as $slug=>$id)echo '<li><a href="'.esc_url(get_preview_post_link($id)).'">'.esc_html($slug).' ansehen</a> · <a href="'.esc_url(get_edit_post_link($id)).'">bearbeiten</a></li>';echo '</ul>';}}echo '<form method="post">';wp_nonce_field('vpm_affiliate_preview');echo '<button class="button button-primary" name="vpm_affiliate_preview" value="1">Getrennte Vorschau erstellen</button></form></div>';});});
