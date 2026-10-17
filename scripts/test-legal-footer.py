"""Local fixtures: legal drafts hidden, published localized links visible, no duplicates."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
base='http://127.0.0.1:8088'
def php(code):
 return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
f=json.loads(php('foreach(["impressum","datenschutz"]as $s)if(get_page_by_path($s))throw new Exception("Existing legal pages: fixture refused");$out=[];foreach(["impressum","datenschutz"]as $s){foreach(["de","en"]as $l){$id=wp_insert_post(["post_type"=>"page","post_status"=>"draft","post_title"=>"Legal footer test ".$s." ".$l,"post_name"=>$l==="de"?$s:$s."-english-check","post_content"=>"<p>Temporary local test.</p>"]);pll_set_post_language($id,$l);$out[$s][$l]=$id;}pll_save_post_translations($out[$s]);}echo json_encode($out);'))
menuitem=0
try:
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
  page.goto(base+'/',wait_until='networkidle');assert page.locator('footer a').filter(has_text='Impressum').count()==0
  ids=[v for pair in f.values()for v in pair.values()]
  php('foreach('+json.dumps(ids)+'as $id)wp_update_post(["ID"=>$id,"post_status"=>"publish"]);flush_rewrite_rules(false);')
  enurl=php('echo get_permalink('+str(f['impressum']['en'])+');').strip()
  for url,en in [(base+'/',False),(enurl,True)]:
   for width in [1440,390]:
    page.set_viewport_size({'width':width,'height':900});page.goto(url,wait_until='networkidle')
    assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
    for label in (['Legal notice','Privacy policy']if en else ['Impressum','Datenschutz']):
     a=page.locator('footer').get_by_role('link',name=label,exact=True);assert a.count()==1;assert ('/en/'in a.get_attribute('href'))==en
  menuitem=int(php('$m=get_option("theme_mods_".get_stylesheet())["nav_menu_locations"]["footer"];echo wp_update_nav_menu_item($m,0,["menu-item-object-id"=>'+str(f['impressum']['de'])+',"menu-item-object"=>"page","menu-item-type"=>"post_type","menu-item-title"=>"Impressum","menu-item-status"=>"publish"]);').strip())
  page.goto(base+'/',wait_until='networkidle');assert page.locator('footer').get_by_role('link',name='Impressum',exact=True).count()==1
  b.close()
 print('Legal footer passed: drafts hidden, published DE/EN targets correct, desktop/mobile no overflow, existing menu link not duplicated.')
finally:
 ids=[v for pair in f.values()for v in pair.values()]+([menuitem]if menuitem else[])
 php('foreach('+json.dumps(ids)+'as $id)wp_delete_post($id,true);')
