"""Verify English guide lists English articles only; remove temporary articles in finally."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(s):
 return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+s],text=True)
fixture=json.loads(php('$ids=[];foreach(["de","en"]as $l){$id=wp_insert_post(["post_type"=>"post","post_status"=>"publish","post_title"=>"VPM language fixture ".$l,"post_content"=>"<p>Temporary local language test.</p>"]);pll_set_post_language($id,$l);$ids[]=$id;}$guide=get_posts(["post_type"=>"page","post_status"=>"draft","meta_key"=>"_vpm_layout_slug","meta_value"=>"ratgeber","lang"=>"","suppress_filters"=>true,"numberposts"=>1]);echo json_encode(["ids"=>$ids,"guide"=>$guide[0]->ID]);'))
try:
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
  assert page.request.get(base+'/en/valet-parking-guide/').status==404
  page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**',wait_until='domcontentloaded')
  page.goto(base+'/en/?page_id='+str(fixture['guide'])+'&preview=true',wait_until='networkidle')
  assert page.locator('html').get_attribute('lang')=='en-GB'
  assert page.locator('h1').inner_text()=='Valet Parking Guide'
  titles=page.locator('.wp-block-post-title').all_text_contents();assert 'VPM language fixture en' in titles and 'VPM language fixture de' not in titles,titles
  b.close()
 print('English guide: draft private; native query includes English article and excludes German article.')
finally:
 php('foreach('+json.dumps(fixture['ids'])+'as $id)wp_delete_post($id,true);')
