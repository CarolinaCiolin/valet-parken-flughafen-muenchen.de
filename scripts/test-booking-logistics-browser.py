"""Local partner forms and private customer route links, no external maps/mail traffic."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def php(c):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+c],text=True)
def lit(v):return 'json_decode('+json.dumps(json.dumps(v))+',true)'
assert php('global $wpdb;$n=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$n+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t");echo $n;')=='0'
checks=[];errors=[];requests=[]
def check(v,label):
 assert v,label
 checks.append(label)
try:
 partner=int(php('$p=VPM\\Booking\\Core::partner(["airport"=>"STR","name"=>"Standort- und Übergabe-Demo","stripe_account"=>"acct_locationbrowser","commission_bps"=>2000]);echo $p["id"];'))
 with sync_playwright()as p:
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=browser.new_page(viewport={'width':1440,'height':1100});page.on('pageerror',lambda e:errors.append(str(e)))
  page.goto(BASE+'/wp-login.php',wait_until='domcontentloaded');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((ROOT/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click();page.wait_for_load_state('domcontentloaded');page.goto(BASE+'/?vpm_partner_portal=1&partner='+str(partner),wait_until='networkidle')
  form=page.locator('form:has(input[name="portal_action"][value="logistics"])')
  form.locator('[name="support_phone"]').fill('+49 000 1234567');form.locator('[name="booking_email"]').fill('operations@example.invalid');form.locator('[name="billing_email"]').fill('billing@example.invalid')
  for code,label in [('parking','Parkplatz – Testadresse'),('handover','Schlüsselübergabe – Testterminal'),('return','Rückgabe – Testtreffpunkt')]:
   form.locator('[name="points['+code+'][name_de]"]').fill(label);form.locator('[name="points['+code+'][name_en]"]').fill('Test '+code);form.locator('[name="points['+code+'][address]"]').fill('Testadresse '+code);form.locator('[name="points['+code+'][latitude]"]').fill('48.69');form.locator('[name="points['+code+'][longitude]"]').fill('9.19');form.locator('[name="points['+code+'][instructions_de]"]').fill('Nur lokale Testdaten. Bitte zum angegebenen Treffpunkt fahren.');form.locator('[name="points['+code+'][instructions_en]"]').fill('Local test instructions only.')
  form.get_by_role('button',name='Standorte und Kontakte speichern').click();page.wait_for_load_state('networkidle')
  saved=json.loads(php('echo json_encode(VPM\\Booking\\Logistics::get('+str(partner)+'));'));check(len(saved['points'])==3 and saved['support_phone']=='+49 000 1234567','Partner form saves three places and support contact')
  form=page.locator('form:has(input[name="portal_action"][value="logistics"])');directory=ROOT/'zwischenstand/buchungskern';form.screenshot(path=str(directory/'partnerportal-uebergabe-test.png'))
  data=json.loads(php('$b=VPM\\Booking\\Core::create(["partner_id"=>'+str(partner)+',"base_cents"=>10000,"email"=>"location-browser@example.invalid"],"location-browser-fixture");VPM\\Booking\\Core::confirm($b["public_id"]);VPM\\Booking\\DB::update("bookings",["payment_state"=>"paid","stripe_pi"=>"pi_browserfixture"],["id"=>$b["id"]]);VPM\\Booking\\Core::confirm($b["public_id"]);echo json_encode(["reference"=>$b["public_id"],"link"=>VPM\\Booking\\Customer::issue(VPM\\Booking\\Core::booking($b["public_id"]))["url"]]);'))
  customer=browser.new_page(viewport={'width':1200,'height':1000});customer.on('request',lambda r:requests.append(r.url));customer.on('pageerror',lambda e:errors.append(str(e)));customer.goto(data['link'],wait_until='networkidle');customer.locator('.vpm-customer-details:not([hidden])').wait_for()
  check(customer.locator('.vpm-customer-details a[href^="https://www.google.com/maps/dir/"]').count()==3,'Customer sees three distinct route buttons')
  check(customer.locator('a[href="tel:+490001234567"]').count()==1,'Customer can call designated partner for booking questions')
  check('billing@example.invalid'not in customer.locator('body').inner_text() and 'operations@example.invalid'not in customer.locator('body').inner_text(),'Internal partner email addresses not exposed to customer')
  check(all('google.com'not in url for url in requests),'No Maps image, script or iframe loads before explicit route click')
  check(customer.locator('iframe').count()==0,'No embedded map requiring third-party loading')
  customer.screenshot(path=str(directory/'kundenansicht-anfahrt-test.png'),full_page=True)
  for width in [1440,390]:
   customer.set_viewport_size({'width':width,'height':1000});check(customer.evaluate('document.documentElement.scrollWidth<=innerWidth+1'),'Customer travel instructions responsive '+str(width))
  token=data['link'].split('#token=')[1];result=customer.request.post(BASE+'/wp-json/vpm-booking/v1/customer/cancellation',data={'token':token});check(result.status==200,'Customer can submit cancellation wish without executing cancellation')
  page.goto(BASE+'/?vpm_partner_portal=1&partner='+str(partner),wait_until='networkidle');check(page.get_by_role('heading',name='Offene Stornowünsche').count()==1 and page.get_by_role('button',name='Storno prüfen und ausführen lassen').count()==1,'Partner sees actionable cancellation wish in own portal')
  check(not errors,'No JavaScript errors in portal/customer travel flow');browser.close()
 report={'count':len(checks),'checks':checks,'limitations':['Local test addresses and payment state only','No actual route lookup, mail delivery or partner document generation']};(ROOT/'docs/BOOKING-LOGISTICS-BROWSER-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n');print(str(len(checks))+' logistics/customer browser checks passed.')
finally:
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$wpdb->query("DELETE FROM $t");')
