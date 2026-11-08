"""Integration: unchanged Munich default, explicit airport/hub profiles, neutral fresh site and safe imports."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json,tarfile,io,sys
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(c):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+c],text=True)
def lit(x):return 'json_decode('+json.dumps(json.dumps(x))+',true)'
state=json.loads(php('$p=[];foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","numberposts"=>-1,"lang"=>"","suppress_filters"=>true])as $post)$p[$post->ID]=$post->post_status;echo json_encode(["profile"=>get_option("vpm_site_profile",null),"front"=>get_option("page_on_front"),"blogname"=>get_option("blogname"),"statuses"=>$p]);'))
def content_digest():return php('global $wpdb;echo hash("sha256",wp_json_encode($wpdb->get_results($wpdb->prepare("SELECT ID,post_title,post_content,post_name,post_status FROM {$wpdb->posts} WHERE post_type IN (%s,%s) ORDER BY ID","page","vpm_provider"),ARRAY_A)));')
before=content_digest();fixtures={};report=[]
baseline=root/'.runtime/profile-baseline';baseline.mkdir(exist_ok=True)
archive=subprocess.check_output(['git','archive','HEAD','wordpress/themes/vpm-hybrid'],cwd=root)
with tarfile.open(fileobj=io.BytesIO(archive))as t:t.extractall(baseline,filter='data')
def deploy(path):
 subprocess.run(['docker','cp',str(path)+'/.','vpm-local-wp:/var/www/html/wp-content/themes/vpm-hybrid/'],check=True)
 subprocess.run(['docker','exec','vpm-local-wp','sh','-c','find /var/www/html/wp-content/themes/vpm-hybrid -type f -exec chmod 644 {} +; find /var/www/html/wp-content/themes/vpm-hybrid -type d -exec chmod 755 {} +'],check=True)
def snapshot(page,path):
 r=page.goto(base+path,wait_until='networkidle');assert r.status==200,(path,r.status)
 return page.evaluate('''()=>({main:document.querySelector('main').innerText,brand:[...document.querySelectorAll('.vpm-wordmark')].map(x=>x.textContent),cta:document.querySelector('.vpm-header-cta').outerHTML,cards:[...document.querySelectorAll('.vpm-provider-card')].map(x=>x.innerText),links:[...document.querySelectorAll('main a')].map(x=>[x.textContent,x.getAttribute('href'),x.rel]),schema:JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent)})''')
try:
 # Validate profiles and reject offsite routes, malformed legacy switch and invented umbrella airport.
 print(php('foreach(["munich","frankfurt","umbrella"]as $name)if(is_wp_error(vpm_validate_profile(vpm_profile_preset($name))))throw new Exception("Bad preset");$p=vpm_profile_preset("frankfurt");$p["paths"]["en"]["prices"]="https://evil.example/prices/";if(!is_wp_error(vpm_validate_profile($p)))throw new Exception("External path accepted");$p=vpm_profile_preset("frankfurt");$p["legacy_munich"]="false";if(!is_wp_error(vpm_validate_profile($p)))throw new Exception("String legacy accepted");$p=vpm_profile_preset("umbrella");$p["airport_de"]="Flughafen München";if(!is_wp_error(vpm_validate_profile($p)))throw new Exception("Hub forced airport accepted");echo "Profile validation passed.\n";').strip())
 php('delete_option("vpm_site_profile");foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","numberposts"=>-1,"lang"=>"en"])as $p)if($p->post_name!=="valet-parking-guide")wp_update_post(["ID"=>$p->ID,"post_status"=>"publish"]);')
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page(viewport={'width':1440,'height':1000})
  paths=['/','/preise/','/valet-parken-sicherheit/','/ueber-uns/','/en/','/en/prices/','/en/valet-parking-safety/','/en/about-us/']
  deploy(baseline/'wordpress/themes/vpm-hybrid');old={path:snapshot(page,path)for path in paths}
  deploy(root/'wordpress/themes/vpm-hybrid')
  for path in paths:
   current=snapshot(page,path);assert current==old[path],('Munich changed',path,[k for k in old[path]if old[path][k]!=current[k]])
  assert json.loads(php('echo json_encode(get_option("vpm_site_profile",null));'))is None
  report.append({'case':'Munich implicit compatibility','pages':8,'unchanged':['visible copy','brand','CTA','provider cards','main links/affiliate rel','entire JSON-LD graph'],'result':'passed'})
  # Isolated new-site content; do not repurpose existing Munich offers under a new airport.
  fixtures=json.loads(php('$ids=[];foreach(["de","en"]as $lang){foreach(["home","prices"]as $role){$id=wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Profile test ".$role." ".$lang,"post_name"=>"profile-test-".$role."-".$lang,"post_content"=>$role==="home"?\'<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Airport profile fixture</h1><!-- /wp:heading --><!-- wp:heading --><h2 class="wp-block-heading" id="anbieter">Providers</h2><!-- /wp:heading --><!-- wp:shortcode -->[vpm_providers]<!-- /wp:shortcode --><!-- wp:heading --><h2 class="wp-block-heading" id="flughaefen">Airports</h2><!-- /wp:heading -->\':\'<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Price fixture</h1><!-- /wp:heading --><table><thead><tr><th>Offer</th><th>Total for 8 days from</th><th>Parking type</th><th>Booking</th></tr></thead><tbody><tr><td>Local test provider</td><td>€123</td><td>Outdoor parking</td><td><a href="https://example.org/booking/" rel="sponsored nofollow">Check</a></td></tr></tbody></table>\']);pll_set_post_language($id,$lang);update_post_meta($id,"_vpm_layout_slug",$role==="home"?"startseite":"preise");$ids[$role][$lang]=$id;}}foreach($ids as $pair)pll_save_post_translations($pair);$id=wp_insert_post(["post_type"=>"vpm_provider","post_status"=>"publish","post_title"=>"Local test provider"]);pll_set_post_language($id,"de");update_post_meta($id,"_vpm_active",true);update_post_meta($id,"_vpm_features","Visible local test feature");$ids["provider"]=[$id];echo json_encode($ids);'))
  php('foreach('+lit([int(i)for i in state['statuses']])+'as $id)if(get_post_type($id)==="vpm_provider")wp_update_post(["ID"=>$id,"post_status"=>"draft"]);update_option("page_on_front",'+str(fixtures['home']['de'])+');flush_rewrite_rules(false);')
  for name in ['frankfurt','umbrella']:
   php('update_option("vpm_site_profile",vpm_profile_preset("'+name+'"));update_option("blogname","'+('Frankfurt Airport Parking'if name=='frankfurt'else'Valet Parkservice')+'");')
   if name=='umbrella':php('foreach('+lit(list(fixtures['home'].values()))+'as $id)wp_update_post(["ID"=>$id,"post_content"=>str_replace("[vpm_providers]","",get_post_field("post_content",$id))]);')
   for path in ['/','/en/']:
    for width in [1440,390]:
     page.set_viewport_size({'width':width,'height':1000});r=page.goto(base+path,wait_until='networkidle');assert r.status==200
     assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(name,path,width)
     assert page.locator('.vpm-wordmark-name').first.inner_text()==('VALET PARKEN'if name=='frankfurt'else'VALET')
     assert page.locator('.vpm-wordmark-city').first.inner_text()==('FRANKFURT'if name=='frankfurt'else'PARKSERVICE.COM')
     assert page.locator('.vpm-header-cta').get_attribute('href').endswith('#'+('anbieter'if name=='frankfurt'else'flughaefen'))
     assert page.locator('link[rel="icon"][href*="favicon-64.png"],.vpm-provider-art,.vpm-editorial-image').count()==0
     graph=json.loads(page.locator('script[type="application/ld+json"]').first.text_content())['@graph']
     assert 'logo'not in next(n for n in graph if n['@type']=='Organization')
     assert any(n['@type']=='ItemList'for n in graph)==bool(page.locator('.vpm-provider-card').count())
     data=page.locator('script[type="application/ld+json"]').first.text_content();assert 'Munich'not in data and 'München'not in data
   enprice=php('echo get_permalink('+str(fixtures['prices']['en'])+');')
   page.goto(enprice,wait_until='networkidle');nodes=json.loads(page.locator('script[type="application/ld+json"]').first.text_content())['@graph']
   listing=next(n for n in nodes if n['@type']=='ItemList');service=listing['itemListElement'][0]['item']
   assert service['serviceType']==('Valet parking at Frankfurt Airport'if name=='frankfurt'else'Airport parking')
   assert ('areaServed'in service)==(name=='frankfurt')
   if name=='frankfurt':assert service['areaServed']['name']=='Frankfurt Airport'
   assert 'including parking and valet'not in service['offers']['description']
   assert php('echo is_wp_error(vpm_import_content_drafts())&&is_wp_error(vpm_import_english())?"blocked":"bad";')=='blocked'
   report.append({'case':name,'languages':['de','en'],'viewports':[1440,390],'checks':['wordmark/CTA','no overflow','no Munich fallback images/icon/schema','own service and optional airport schema','Munich import blocked'],'result':'passed'})
  # Simulate a fresh installation: no saved profile and no identified Munich front page.
  php('delete_option("vpm_site_profile");update_option("blogname","Airport Parking");')
  page.goto(base+'/',wait_until='networkidle');assert page.locator('.vpm-wordmark-name').first.inner_text()=='Airport Parking';assert page.locator('.vpm-wordmark-city').count()==0
  assert php('echo vpm_site_profile()["id"];')=='unconfigured'
  assert page.locator('.vpm-header-cta').count()==0
  report.append({'case':'fresh neutral default','result':'passed'})
  page.goto(base+'/wp-admin/themes.php?page=vpm-site-profile');assert page.locator('#vpm-profile-json').count()==0
  page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**',wait_until='domcontentloaded')
  page.goto(base+'/wp-admin/themes.php?page=vpm-site-profile',wait_until='networkidle');page.locator('#vpm-preset').select_option('frankfurt');page.locator('input[name="vpm_profile_preset"]').click();page.wait_for_load_state('networkidle');assert page.locator('.notice-success').count()==1
  assert php('echo vpm_site_profile()["id"];')=='frankfurt'
  valid=php('echo wp_json_encode(get_option("vpm_site_profile"));')
  page.locator('#vpm-profile-json').fill('{');page.locator('input[name="vpm_profile_save"]').click();page.wait_for_load_state('networkidle');assert page.locator('.notice-error').count()==1
  assert php('echo wp_json_encode(get_option("vpm_site_profile"));')==valid
  denied=page.request.post(base+'/wp-admin/themes.php?page=vpm-site-profile',form={'vpm_profile_save':'1','_wpnonce':'invalid','profile_json':'{}'});assert denied.status==403
  assert php('echo wp_json_encode(get_option("vpm_site_profile"));')==valid
  report.append({'case':'admin settings','checks':['anonymous denied','explicit preset saved','malformed JSON does not alter valid profile','invalid nonce denied'],'result':'passed'})
  b.close()
finally:
 deploy(root/'wordpress/themes/vpm-hybrid')
 php('update_option("page_on_front",'+lit(state['front'])+');update_option("blogname",'+lit(state['blogname'])+');'+('delete_option("vpm_site_profile");'if state['profile']is None else'update_option("vpm_site_profile",'+lit(state['profile'])+');')+'foreach('+lit(state['statuses'])+'as $id=>$status)wp_update_post(["ID"=>$id,"post_status"=>$status]);foreach('+lit([i for values in fixtures.values()for i in (values.values()if isinstance(values,dict)else values)])+'as $id)wp_delete_post($id,true);flush_rewrite_rules(false);')
assert content_digest()==before,'Original post content/status/slug changed'
subprocess.run(['docker','cp',str(root/'scripts/test-profile-routing.php'),'vpm-local-wp:/tmp/vpm-test-profile-routing.php'],check=True)
subprocess.run(['docker','exec','vpm-local-wp','php','/tmp/vpm-test-profile-routing.php'],check=True)
subprocess.run([sys.executable,str(root/'scripts/test-profile-tracking.py')],check=True)
report.append({'case':'custom page roles and price duration','checks':['draft DE/EN custom routes','explicit layout metadata has precedence','visible seven/eight/fourteen-day prices','malformed paths rejected','other profiles require their own marketing-gated CleanLinking ID'],'result':'passed'})
(root/'docs/STANDORT-PROFILE-PRUEFUNG.json').write_text(json.dumps({'profiles':report,'original_content_unchanged':True,'limitations':['Local integration, no Hostinger deployment','Frankfurt and umbrella remain configuration templates without production content','English/German only; no general content importer or multi-airport data sync','Current live Polylang 3.9.1 reported by operator, not independently installed in this test']},ensure_ascii=False,indent=2)+'\n')
print('Site profiles passed: 8-page Munich comparison, Frankfurt/hub bilingual responsive/schema, neutral fresh default, validation, guarded imports and preserved content.')
