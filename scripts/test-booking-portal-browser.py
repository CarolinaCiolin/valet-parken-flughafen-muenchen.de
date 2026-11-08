"""Loopback partner login/tenant scope and authenticated six-step demo; no external payment."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json,datetime
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(v):return 'json_decode('+json.dumps(json.dumps(v))+',true)'
assert php('global $wpdb;$n=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$n+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t");echo $n;')=='0'
settings=json.loads(php('$a=[];foreach(["vpm_booking_frontend_enabled","vpm_booking_products"]as $k)$a[$k]=get_option($k,null);echo json_encode($a);'))
fixture='/var/www/html/wp-content/mu-plugins/vpm-booking-portal-demo.php';local=ROOT/'.runtime/portal-demo.php';ids=[];users=[];checks=[];errors=[]
assert subprocess.run(['docker','exec','vpm-local-wp','test','-e',fixture]).returncode!=0
def check(v,label):
 assert v,label
 checks.append(label)
try:
 local.write_text("<?php define('VPM_BOOKING_DEMO_MODE',true);")
 subprocess.run(['docker','cp',str(local),'vpm-local-wp:'+fixture],check=True);subprocess.run(['docker','exec','vpm-local-wp','chmod','644',fixture],check=True)
 data=json.loads(php('$a=VPM\\Booking\\Core::partner(["airport"=>"STR","name"=>"Demo Stuttgart Partner A","stripe_account"=>"acct_portalalpha","commission_bps"=>2000]);$b=VPM\\Booking\\Core::partner(["airport"=>"FRA","name"=>"Vertraulicher Partner B","stripe_account"=>"acct_portalbeta","commission_bps"=>2000]);$u=wp_insert_user(["user_login"=>"vpm_portal_fixture_".bin2hex(random_bytes(4)),"user_pass"=>"local-fixture-password-only","user_email"=>"portal-owner@example.invalid","role"=>"vpm_partner"]);VPM\\Booking\\Tenants::assign((int)$a["id"],$u,"owner");$owner=get_userdata($u);VPM\\Booking\\Portal::handle((int)$a["id"],["portal_action"=>"product","name_de"=>"Außenparken – Technikdemo","name_en"=>"Outdoor demo","parking_type"=>"outdoor","addons"=>["wash"=>["enabled"=>"1","label_de"=>"Fahrzeugwäsche","label_en"=>"Car wash","description_de"=>"Definiertes Waschpaket – nur Testdaten","description_en"=>"Test package","unit_amount_cents"=>"7000","max_quantity"=>"1"]]]);$catalog=VPM\\Booking\\Products::catalogue();$product=array_key_last($catalog);VPM\\Booking\\Adapters::configure((int)$a["id"],["mode"=>"manual"]);VPM\\Booking\\Adapters::save_rate((int)$a["id"],["product_id"=>$product,"price_cents"=>"10000","capacity"=>"2","pricing"=>"stay","enabled"=>"1"]);VPM\\Booking\\Core::create(["partner_id"=>(int)$a["id"],"base_cents"=>10000,"email"=>"alpha-only@example.invalid"],"portal-alpha-fixture");VPM\\Booking\\Core::create(["partner_id"=>(int)$b["id"],"base_cents"=>10000,"email"=>"beta-secret@example.invalid"],"portal-beta-fixture");update_option("vpm_booking_frontend_enabled",true);echo json_encode(["partner"=>(int)$a["id"],"other"=>(int)$b["id"],"user"=>$u,"login"=>$owner->user_login]);'))
 users.append(data['user'])
 page_id=int(php('echo wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Mehrstufige Buchungsdemo – keine echte Buchung","post_content"=>\'[vpm_booking_form airport="STR" flow="steps"]\']);'));ids.append(page_id)
 with sync_playwright()as p:
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);ctx=browser.new_context(viewport={'width':1440,'height':1000});page=ctx.new_page();page.on('pageerror',lambda e:errors.append(str(e)))
  page.goto(BASE+'/?vpm_partner_portal=1',wait_until='domcontentloaded');check('wp-login.php'in page.url,'Portal requires real WordPress login')
  page.locator('#user_login').fill(data['login']);page.locator('#user_pass').fill('local-fixture-password-only');page.locator('#wp-submit').click();page.wait_for_load_state('domcontentloaded');page.goto(BASE+'/?vpm_partner_portal=1&partner='+str(data['partner']),wait_until='networkidle')
  text=page.locator('body').inner_text();check('alpha-only@example.invalid'in text and 'beta-secret@example.invalid'not in text and 'Vertraulicher Partner B'not in text,'Partner portal displays own data only')
  check('Plattformgesamtübersicht'not in text,'Owner cannot see platform-wide metrics')
  check('noindex'in page.request.get(BASE+'/?vpm_partner_portal=1').headers.get('x-robots-tag',''),'Portal is excluded from index')
  nonce=page.locator('input[name="_wpnonce"]').first.input_value();bad=page.request.post(BASE+'/?vpm_partner_portal=1',form={'_wpnonce':nonce,'portal_action':'connection','partner':str(data['other']),'mode':'manual'})
  check(bad.status==403 and 'beta-secret'not in bad.text(),'Forged cross-tenant POST rejected with 403')
  page.goto(BASE+'/?vpm_partner_portal=1&partner='+str(data['other']),wait_until='networkidle');check('beta-secret'not in page.locator('body').inner_text(),'Direct foreign partner query reveals no booking data')
  page.goto(BASE+'/?vpm_partner_portal=1&partner='+str(data['partner']),wait_until='networkidle')
  for width in [1440,390]:
   page.set_viewport_size({'width':width,'height':1000});check(page.evaluate('document.documentElement.scrollWidth<=innerWidth+1'),'Portal responsive without page overflow '+str(width))
  directory=ROOT/'zwischenstand/buchungskern';page.set_viewport_size({'width':1440,'height':1100});page.screenshot(path=str(directory/'partnerportal-test.png'))
  # New authenticated context for platform demo, preserving owner role separation.
  admin=browser.new_context(viewport={'width':1440,'height':1000});demo=admin.new_page();demo.on('pageerror',lambda e:errors.append(str(e)))
  demo.goto(BASE+'/wp-login.php',wait_until='domcontentloaded');demo.locator('#user_login').fill('vpm_local_admin');demo.locator('#user_pass').fill((ROOT/'.runtime/admin-password').read_text().strip());demo.locator('#wp-submit').click();demo.wait_for_load_state('domcontentloaded')
  demo.goto(BASE+'/?page_id='+str(page_id),wait_until='networkidle')
  if demo.locator('.cmplz-deny').count():demo.locator('.cmplz-deny').first.click()
  widget=demo.locator('.vpm-booking-widget');check(widget.get_attribute('data-step')=='1','Wizard starts with travel dates')
  widget.locator('[name="departure"]').fill((datetime.date.today()+datetime.timedelta(days=10)).isoformat()+'T10:00');widget.locator('[name="return"]').fill((datetime.date.today()+datetime.timedelta(days=18)).isoformat()+'T12:00');widget.locator('.vpm-booking-search button').click();widget.locator('.vpm-booking-choose:visible').wait_for()
  check(widget.get_attribute('data-step')=='2'and widget.locator('.vpm-booking-addons:visible').count()==0,'Offer selection does not expose extras prematurely')
  widget.locator('.vpm-booking-choose').click();check(widget.get_attribute('data-step')=='3'and widget.locator('.vpm-addon:checked').count()==0,'Separate extras step defaults to no extras')
  widget.locator('.vpm-addon[value="wash"]').check();widget.locator('.vpm-wizard-next').click();check(widget.get_attribute('data-step')=='4','Customer details follow extras')
  widget.locator('[name="email"]').fill('demo-customer@example.invalid')
  before=php('global $wpdb;echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("bookings"));');widget.locator('[name="email"]').press('Enter');check(php('global $wpdb;echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("bookings"));')==before,'Enter in details step cannot bypass review or create booking')
  widget.locator('.vpm-wizard-next').click();widget.locator('.vpm-booking-summary:visible').wait_for()
  check(widget.get_attribute('data-step')=='5'and '170,00'in widget.locator('.vpm-booking-summary').inner_text(),'Review includes parking and selected 70-euro extra')
  widget.screenshot(path=str(directory/'checkout-schritt-preisuebersicht-test.png'))
  widget.locator('.vpm-booking-request button[type="submit"]').click();widget.locator('.vpm-wizard-demo:visible').wait_for();check(widget.get_attribute('data-step')=='6','Enquiry reaches explicit demo-payment step')
  widget.locator('.vpm-wizard-demo').click();widget.locator('.vpm-booking-status').filter(has_text='Demo abgeschlossen. Keine echte Buchung oder Zahlung.').wait_for();check(True,'Demo payment never presents itself as a real booking')
  count=php('global $wpdb;echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("ledger"));');check(count=='0','Demo creates no real payment journal')
  demo.goto(BASE+'/?vpm_partner_portal=1&partner='+str(data['partner']),wait_until='networkidle');check('Plattformgesamtübersicht'in demo.locator('body').inner_text(),'Platform administrator sees combined dashboard')
  check(not errors,'No JavaScript errors in portal and multi-step demo')
  browser.close()
 report={'count':len(checks),'checks':checks,'limitations':['Local role/login and simulation only; no production subdomain, provider API or Stripe payment tested']};(ROOT/'docs/BOOKING-PORTAL-BROWSER-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n');print(str(len(checks))+' portal/wizard browser checks passed.')
finally:
 subprocess.run(['docker','exec','vpm-local-wp','rm','-f',fixture],check=True);local.unlink(missing_ok=True)
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$wpdb->query("DELETE FROM $t");foreach('+lit(ids)+'as $id)wp_delete_post($id,true);foreach('+lit(settings)+'as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}require_once ABSPATH."wp-admin/includes/user.php";foreach('+lit(users)+'as $id)wp_delete_user($id);')
