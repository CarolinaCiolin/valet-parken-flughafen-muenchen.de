"""Local-only Complianz gate, browser and cookie-policy footer integration checks."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(c):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+c],text=True)
def literal(x):return 'json_decode('+json.dumps(json.dumps(x))+',true)'
keys=['cmplz_options','cmplz_wizard_completed_once','cmplz_transients','cmplz_cookie-statement_custom_page']
state=json.loads(php('$s=[];foreach('+literal(keys)+'as $k)$s[$k]=["exists"=>get_option($k,"__absent__")!=="__absent__","value"=>get_option($k)];global $wpdb;$s["banners"]=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}cmplz_cookiebanners",ARRAY_A);echo json_encode($s);'))
created=[];menuitem=0;checks=[]
try:
 opts=state['cmplz_options']['value'].copy();opts.update(dict(regions=['eu'],compile_statistics='no',enable_cookie_banner='yes',uses_ad_cookies='no',uses_firstparty_marketing_cookies='no',uses_thirdparty_services='no',uses_social_media='no',consent_for_anonymous_stats='no',respect_dnt='no'))
 def config(completed,required):
  opts['uses_thirdparty_services']='yes'if required else'no'
  php('update_option("cmplz_options",'+literal(opts)+');update_option("cmplz_wizard_completed_once",'+('true'if completed else'false')+');')
 php('$b=cmplz_get_cookiebanner();$b->disable_cookiebanner=false;$b->save();')
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
  for completed,required,expected in [(False,True,False),(True,False,False),(True,True,True)]:
   config(completed,required)
   for width in [1440,390]:
    context=b.new_context(viewport={'width':width,'height':1000});page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    page.goto(base+'/',wait_until='networkidle');page.wait_for_timeout(500)
    visible=page.locator('.cmplz-cookiebanner:visible').count()>0
    assert visible==expected,(completed,required,width,visible)
    assert not errors,errors
    if expected:
     page.locator('.cmplz-cookiebanner:visible .cmplz-deny').click();assert page.locator('.cmplz-cookiebanner:visible').count()==0
     page.reload(wait_until='networkidle');assert page.locator('.cmplz-cookiebanner:visible').count()==0
    context.close()
   checks.append({'wizard_completed':completed,'consent_required':required,'banner_visible':expected,'widths':[1440,390]})
  # A cookie policy outside the active menus still receives a published, language-aware footer link.
  config(True,False);opts['cookie-statement']='custom'
  created=json.loads(php('$ids=[];foreach(["de","en"]as $lang){$id=wp_insert_post(["post_type"=>"page","post_status"=>"draft","post_title"=>"Cookie fixture ".$lang,"post_name"=>"cookie-fixture-".$lang,"post_content"=>"<p>Temporary cookie-policy fixture.</p>"]);pll_set_post_language($id,$lang);$ids[$lang]=$id;}pll_save_post_translations($ids);echo json_encode($ids);'))
  php('update_option("cmplz_options",'+literal(opts)+');update_option("cmplz_cookie-statement_custom_page",'+str(created['de'])+');')
  page=b.new_page();page.goto(base+'/',wait_until='networkidle');assert page.locator('footer').get_by_role('link',name='Cookie-Richtlinie',exact=True).count()==0
  php('foreach('+literal(list(created.values()))+'as $id)wp_update_post(["ID"=>$id,"post_status"=>"publish"]);flush_rewrite_rules(false);')
  enurl=php('echo get_permalink('+str(created['en'])+');')
  for url,label in [(base+'/','Cookie-Richtlinie'),(enurl,'Cookie policy')]:
   page.goto(url,wait_until='networkidle');a=page.locator('footer').get_by_role('link',name=label,exact=True);assert a.count()==1
   assert ('/en/'in a.get_attribute('href'))==(label=='Cookie policy')
  menuitem=int(php('$m=get_option("polylang")["nav_menus"][get_stylesheet()]["footer"]["de"];echo wp_update_nav_menu_item($m,0,["menu-item-object-id"=>'+str(created['de'])+',"menu-item-object"=>"page","menu-item-type"=>"post_type","menu-item-title"=>"Cookie-Richtlinie","menu-item-status"=>"publish"]);'))
  page.goto(base+'/',wait_until='networkidle');assert page.locator('footer').get_by_role('link',name='Cookie-Richtlinie',exact=True).count()==1
  # Generated Gutenberg/shortcode policy lookup, with the menu copy removed.
  php('wp_delete_post('+str(menuitem)+',true);wp_update_post(["ID"=>'+str(created["de"])+',"post_content"=>'+literal('[cmplz-document type="cookie-statement" region="eu"]')+']);cmplz_delete_transient("cmplz_shortcode_cookie-statement-eu");')
  menuitem=0;opts['cookie-statement']='generated';php('update_option("cmplz_options",'+literal(opts)+');')
  page.goto(base+'/',wait_until='networkidle');assert page.locator('footer').get_by_role('link',name='Cookie-Richtlinie',exact=True).count()==1
  b.close()
 version=php('require_once ABSPATH."wp-admin/includes/plugin.php";echo get_plugin_data(WP_PLUGIN_DIR."/complianz-gdpr/complianz-gpdr.php")["Version"];')
 (root/'docs/COMPLIANZ-PRUEFUNG.json').write_text(json.dumps({'complianz_version':version,'banner_checks':checks,'footer_checks':['Draft policy hidden','Published policy appears even outside active footer menu','English translation linked','Manual menu entry not duplicated','Generated Complianz shortcode policy resolved through official document API'],'active_plugins':json.loads(php('echo json_encode(get_option("active_plugins"));')),'limitations':['Local integration, not a production diagnosis','Local Complianz 7.5.5; user reported 7.5.6','Live LiteSpeed optimization settings unavailable','No tracking service added: consent-required configuration is a temporary test only']},ensure_ascii=False,indent=2)+'\n')
 print('Complianz passed: wizard/requirement gates, visible banner and rejection retained, no JS errors at desktop/mobile; localized published cookie-policy footer links and deduplication.')
finally:
 ids=list(created.values())if isinstance(created,dict)else[]
 php('foreach('+literal(ids+([menuitem]if menuitem else[]))+'as $id)wp_delete_post($id,true);')
 php('foreach('+literal({k:v for k,v in state.items()if k!='banners'})+'as $k=>$s){if($s["exists"])update_option($k,$s["value"]);else delete_option($k);}global $wpdb;$wpdb->query("DELETE FROM {$wpdb->prefix}cmplz_cookiebanners");foreach('+literal(state['banners'])+'as $row)$wpdb->insert($wpdb->prefix."cmplz_cookiebanners",$row);')
