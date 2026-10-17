"""Test explicit publication with local synthetic legal content; restore all statuses."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(s):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+s],text=True)
snapshot=json.loads(php('foreach(["impressum","datenschutz"]as $s)if(get_page_by_path($s))throw new Exception("Existing legal data: test refused");$out=[];foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","lang"=>"en","numberposts"=>-1])as $p)$out[$p->ID]=$p->post_status;echo json_encode($out);'))
legal=[]
try:
 print(php('wp_set_current_user(0);if(!is_wp_error(vpm_publish_english())||!is_wp_error(vpm_publish_legal()))throw new Exception("Permission check failed");wp_set_current_user(1);$id=pll_get_post(get_option("page_on_front"),"en");$content=get_post_field("post_content",$id);try{wp_update_post(["ID"=>$id,"post_content"=>""]);$r=vpm_publish_english();if(!is_wp_error($r))throw new Exception("Empty page published");foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","lang"=>"en","numberposts"=>-1])as $p)if($p->post_status!=="draft")throw new Exception("Partial preflight publication");}finally{wp_update_post(["ID"=>$id,"post_content"=>$content]);}$r=vpm_publish_english();if(is_wp_error($r)||!str_starts_with($r,"10 "))throw new Exception("English publication failed");$r=vpm_publish_english();if(is_wp_error($r)||!str_starts_with($r,"0 "))throw new Exception("Repeated English publication");$guide=pll_get_post(vpm_original_page("ratgeber"),"en");if(get_post_status($guide)!=="draft"||get_post_status(vpm_original_page("ratgeber"))!=="draft")throw new Exception("Guide unexpectedly published");if(!is_wp_error(vpm_publish_legal()))throw new Exception("Missing legal preflight");echo "Permissions, empty-page preflight, 10 English publications, repeat and guide privacy passed.\n";').strip())
 legal=json.loads(php('$ids=[];foreach(["impressum","datenschutz"]as $s){$id=wp_insert_post(["post_type"=>"page","post_status"=>"draft","post_title"=>$s,"post_name"=>$s,"post_content"=>"<p>Temporary publication test.</p>"]);update_post_meta($id,"_vpm_legal_draft","1");pll_set_post_language($id,"de");$ids[]=$id;}echo json_encode($ids);'))
 print(php('$r=vpm_publish_legal();if(is_wp_error($r)||!str_starts_with($r,"2 "))throw new Exception("Legal publication failed");$r=vpm_publish_legal();if(is_wp_error($r)||!str_starts_with($r,"0 "))throw new Exception("Legal repeat failed");echo "Two synthetic legal pages published, repeat safe.\n";').strip())
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
  page.goto(base+'/en/',wait_until='networkidle');assert page.locator('h1').inner_text()=='Munich Airport Valet Parking';assert page.locator('.vpm-provider-card').count()==6
  assert page.get_by_role('navigation',name='Main navigation',exact=True).get_by_role('link',name='Prices',exact=True).count()==1
  page.goto(base+'/',wait_until='networkidle')
  for name in ['Impressum','Datenschutz']:assert page.locator('footer').get_by_role('link',name=name,exact=True).count()==1
  assert page.request.get(base+'/en/valet-parking-guide/').status==404
  b.close()
 print('Public English homepage/menu/cards and automatic German legal footer verified; guide stays private.')
finally:
 php('foreach(json_decode('+json.dumps(json.dumps(snapshot))+',true)as $id=>$status)wp_update_post(["ID"=>$id,"post_status"=>$status]);foreach('+json.dumps(legal)+'as $id)wp_delete_post($id,true);')
