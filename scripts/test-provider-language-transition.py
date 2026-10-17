import subprocess,json,urllib.request
from html.parser import HTMLParser
base='http://127.0.0.1:8088'
def php(s):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+s],text=True)
snapshot=json.loads(php('$out=["de"=>[],"en"=>[]];foreach(get_posts(["post_type"=>"vpm_provider","post_status"=>"publish","lang"=>"de","numberposts"=>-1])as $p)$out["de"][$p->ID]=pll_get_post_translations($p->ID);foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","lang"=>"en","numberposts"=>-1])as $p)$out["en"][$p->ID]=$p->post_status;echo json_encode($out);'))
def cards(path):
 with urllib.request.urlopen(base+path)as r:return r.read().decode().count('class="vpm-provider-card"')
try:
 ids=[int(x)for x in snapshot['de']];assert len(ids)==6
 php('foreach('+json.dumps(ids)+'as $id)wp_set_object_terms($id,[],"language");')
 assert cards('/')==6,'Unassigned German providers lost'
 php('pll_set_post_language('+str(ids[0])+',"de");')
 assert cards('/')==6,'Mixed assigned/unassigned providers lost'
 php('foreach('+json.dumps([int(x)for x in snapshot['en']])+'as $id){if(get_post_meta($id,"_vpm_layout_slug",true)!=="ratgeber")wp_update_post(["ID"=>$id,"post_status"=>"publish"]);}')
 assert cards('/en/')==6,'English includes German or loses translations'
 print('PASS: all unassigned = 6 German cards; mixed assignments = 6; English = exactly 6 English cards.')
finally:
 php('foreach(json_decode('+json.dumps(json.dumps(snapshot['de']))+',true)as $id=>$pairs){pll_set_post_language($id,"de");pll_save_post_translations($pairs);}foreach(json_decode('+json.dumps(json.dumps(snapshot['en']))+',true)as $id=>$status)wp_update_post(["ID"=>$id,"post_status"=>$status]);')
