"""Loopback-only booking UI tests. Adapter and CMP API are explicit test doubles."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json,datetime
ROOT=Path(__file__).resolve().parents[1]
BASE='http://127.0.0.1:8088'
def php(code):
 return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def literal(value):return 'json_decode('+json.dumps(json.dumps(value))+',true)'
options=['vpm_booking_frontend_enabled','vpm_booking_analytics_enabled','vpm_booking_analytics_target','vpm_booking_compat_fixture']
saved=json.loads(php('$out=[];foreach('+literal(options)+'as $k)$out[$k]=get_option($k,null);$out["theme"]=get_stylesheet();echo json_encode($out);'))
fixture='/var/www/html/wp-content/mu-plugins/vpm-booking-compat-fixture.php'
assert subprocess.run(['docker','exec','vpm-local-wp','test','-e',fixture]).returncode!=0
assert php('global $wpdb;$total=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $table)$total+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table");echo $total;')=='0'
ids=[];checks=[];errors=[]
def check(condition,name):
 assert condition,name
 checks.append(name)
try:
 local=ROOT/'.runtime/booking-compat-fixture.php'
 local.write_text('''<?php
if(!defined('ABSPATH'))exit;
add_filter('vpm_booking_quote_offers',function($offers,$context){$id=(int)get_option('vpm_booking_compat_fixture');return $context['airport']==='FRA'?[['partner_id'=>$id,'base_cents'=>10000,'currency'=>'eur','terms'=>'Lokaler Techniktest – keine reale Verfügbarkeit.']]:[];},10,2);
''')
 subprocess.run(['docker','cp',str(local),'vpm-local-wp:'+fixture],check=True)
 subprocess.run(['docker','exec','vpm-local-wp','chmod','644',fixture],check=True)
 php('$p=VPM\\Booking\\Core::partner(["airport"=>"FRA","name"=>"Lokaler Testanbieter","stripe_account"=>"acct_browserfixture","commission_bps"=>2000]);update_option("vpm_booking_compat_fixture",$p["id"]);update_option("vpm_booking_frontend_enabled",true);update_option("vpm_booking_analytics_enabled",true);update_option("vpm_booking_analytics_target","dom");')
 content='<!-- wp:vpm-booking/airport-selector {"locale":"de"} /--><!-- wp:vpm-booking/form {"airport":"FRA","locale":"de"} /-->[vpm_booking_form airport="FRA" locale="en"]'
 page_id=int(php('echo wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Techniktest – keine Buchung","post_content"=>'+literal(content)+']);'))
 ids.append(page_id)
 elementor_data=[{'id':'vpmtesta','elType':'container','isInner':False,'settings':{},'elements':[{'id':'vpmtestb','elType':'widget','widgetType':'shortcode','settings':{'shortcode':'[vpm_airport_selector locale="en"] [vpm_booking_form airport="FRA" locale="en"]'},'elements':[]}]}]
 elementor_id=int(php('echo wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Elementor Techniktest"]);'))
 ids.append(elementor_id)
 php('update_post_meta('+str(elementor_id)+',"_elementor_edit_mode","builder");update_post_meta('+str(elementor_id)+',"_elementor_version","3.33.0");update_post_meta('+str(elementor_id)+',"_elementor_data",wp_slash(wp_json_encode('+literal(elementor_data)+')));')
 url=BASE+'/?page_id='+str(page_id)
 departure=(datetime.date.today()+datetime.timedelta(days=10)).isoformat()+'T10:00'
 returning=(datetime.date.today()+datetime.timedelta(days=18)).isoformat()+'T12:00'
 with sync_playwright() as p:
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
  context=browser.new_context()
  # Stable CMP contract fixture: no actual consent cookie is written or granted.
  context.add_init_script('window.__consented=false;Object.defineProperty(window,"cmplz_has_consent",{get:()=>()=>window.__consented,set:()=>{},configurable:false});window.__events=[];document.addEventListener("vpm-booking:analytics",e=>window.__events.push(e.detail));')
  page=context.new_page();page.on('pageerror',lambda e:errors.append(str(e)))
  for theme in ['vpm-hybrid','twentytwentyfive']:
   php('switch_theme('+literal(theme)+');')
   for width in [1440,768,390]:
    page.set_viewport_size({'width':width,'height':1000});response=page.goto(url,wait_until='networkidle')
    check(response.status==200,'HTTP 200 '+theme+' '+str(width))
    check(page.locator('.vpm-booking-widget').count()==2,'Gutenberg and shortcode coexist '+theme+' '+str(width))
    check(page.locator('.vpm-airport-selector select option[value]:not([value=""])').count()==15,'15 airport choices '+theme+' '+str(width))
    check(page.evaluate('document.documentElement.scrollWidth<=innerWidth+1'),'No horizontal overflow '+theme+' '+str(width))
    check(page.locator('script[src*="vpm-booking-core/assets/front.js"]').count()==1,'One shared script '+theme+' '+str(width))
  widget=page.locator('.vpm-booking-widget').first
  widget.locator('[name="departure"]').fill(departure);widget.locator('[name="return"]').fill(returning)
  with page.expect_response(lambda r:'/public/offers' in r.url) as pending:
   widget.locator('.vpm-booking-search button').click()
  response=pending.value
  assert response.status==200,(response.status,response.text())
  check(response.status==200 and 'no-store' in response.headers.get('cache-control',''),'Real REST offers are private/no-store')
  check('noindex' in response.headers.get('x-robots-tag',''),'REST offers excluded from index')
  page.wait_for_selector('.vpm-booking-choose')
  check(page.evaluate('window.__events.length')==0,'No analytics before statistics consent')
  page.evaluate('window.__consented=true')
  widget.locator('.vpm-booking-choose').click()
  widget.locator('[name="email"]').fill('compat-browser@example.invalid')
  with page.expect_response(lambda r:'/public/requests' in r.url) as pending:
   widget.locator('.vpm-booking-request button[type="submit"]').click()
  result=pending.value.json()
  check(result['state']=='requested' and result['payment_state']=='unpaid' and result['amount_cents']==10000,'Real UI submits trusted-price unpaid enquiry')
  page.wait_for_function('document.querySelector(".vpm-booking-widget .vpm-booking-status").textContent.includes("übermittelt")')
  events=page.evaluate('window.__events')
  check(any(e['event']=='vpm_booking_request' for e in events),'Consented request event uses selected bridge')
  check(all(set(e)<=set(['event','booking_flow','locale','airport','error_code']) for e in events),'Telemetry uses only explicit non-personal field allowlist')
  check('compat-browser' not in json.dumps(events) and departure not in json.dumps(events) and result['reference'] not in json.dumps(events),'No email, travel date or booking reference in telemetry')
  page.evaluate('window.__consented=false');before=page.evaluate('window.__events.length')
  widget.locator('[name="departure"]').fill(departure);widget.locator('[name="return"]').fill(returning)
  widget.locator('.vpm-booking-search button').click();page.wait_for_selector('.vpm-booking-choose')
  check(page.evaluate('window.__events.length')==before,'Consent withdrawal suppresses subsequent events')
  page.evaluate('window.__consented=true;Object.defineProperty(navigator,"globalPrivacyControl",{value:true,configurable:true})')
  widget.locator('.vpm-booking-choose').click()
  check(page.evaluate('window.__events.length')==before,'Global Privacy Control suppresses events even with statistics API true')
  page.goto(url+'&elementor-preview='+str(page_id),wait_until='networkidle')
  check(page.locator('.vpm-booking-widget[data-preview="1"]').count()==2 and page.locator('.vpm-booking-search fieldset:disabled').count()==2,'Editor preview disables booking submissions')
  page.goto(BASE+'/?page_id='+str(elementor_id),wait_until='networkidle')
  check(page.locator('.elementor-widget-shortcode .vpm-booking-widget').count()==1,'Actual Elementor Free shortcode widget renders booking form')
  check(page.locator('.elementor-widget-shortcode .vpm-airport-selector select option[value]:not([value=""])').count()==15,'Actual Elementor Free shortcode widget renders 15 airports')
  # Test the actual WordPress block editor rather than registration in PHP alone.
  page.goto(BASE+'/wp-login.php',wait_until='domcontentloaded')
  page.locator('#user_login').fill('vpm_local_admin')
  page.locator('#user_pass').fill((ROOT/'.runtime/admin-password').read_text().strip())
  page.locator('#wp-submit').click();page.wait_for_load_state('networkidle')
  page.goto(BASE+'/wp-admin/post.php?post='+str(page_id)+'&action=edit',wait_until='domcontentloaded',timeout=60000)
  page.wait_for_function('window.wp && wp.data && wp.data.select("core/block-editor").getBlocks().length>0',timeout=45000)
  check(page.evaluate('["form","airport-selector","newsletter"].every(k=>!!wp.blocks.getBlockType("vpm-booking/"+k))'),'Actual Gutenberg editor registers all three blocks')
  check(page.evaluate('wp.data.select("core/block-editor").getBlocks().filter(b=>b.name.startsWith("vpm-booking/")).every(b=>b.isValid!==false)'),'Actual Gutenberg dynamic blocks are valid')
  page.evaluate('let b=wp.data.select("core/block-editor").getBlocks().find(b=>b.name==="vpm-booking/form");wp.data.dispatch("core/block-editor").updateBlockAttributes(b.clientId,{airport:"MUC",locale:"en"});')
  page.wait_for_timeout(1000)
  check(page.evaluate('wp.data.select("core/block-editor").getBlocks().find(b=>b.name==="vpm-booking/form").attributes.airport==="MUC"'),'Gutenberg attributes editable independently')
  check(not errors,'No browser JavaScript errors')
  browser.close()
 print(json.dumps({'checks':checks,'count':len(checks),'limitations':['CMP API test double; actual Complianz wizard/banner integration not end-to-end verified here','Shortcode rendering and Gutenberg editor tested; Elementor editor canvas not tested','No real partner, payment, email or GA endpoint contacted']},ensure_ascii=False,indent=2))
 (ROOT/'docs/BOOKING-COMPAT-BROWSER-PRUEFUNG.json').write_text(json.dumps({'checks':checks,'count':len(checks),'limitations':['CMP contract simulated; not full Complianz browser integration','Elementor shortcodes supported; editor canvas not tested','No external provider or production host verified']},ensure_ascii=False,indent=2)+'\n')
finally:
 subprocess.run(['docker','exec','vpm-local-wp','rm','-f',fixture],check=True)
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $table)$wpdb->query("DELETE FROM $table");foreach('+literal(ids)+'as $id)wp_delete_post($id,true);$s='+literal(saved)+';switch_theme($s["theme"]);unset($s["theme"]);foreach($s as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}')
 local.unlink(missing_ok=True)
