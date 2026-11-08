"""Local DOI GET/POST separation, revocation and public REST privacy, no email provider called."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(c):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+c],text=True)
def lit(x):return 'json_decode('+json.dumps(json.dumps(x))+',true)'
settings=json.loads(php('$out=[];foreach(["vpm_booking_automation_enabled","vpm_booking_consent_text","vpm_booking_consent_version"]as $k)$out[$k]=get_option($k,null);echo json_encode($out);'))
assert php('global $wpdb;echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("contacts"));')=='0'
try:
 # Configuration applies only to this CLI process, never the web server.
 code='define("VPM_BOOKING_AUTOMATION_URL","https://example.org/fixture");define("VPM_BOOKING_AUTOMATION_SECRET","fixture-only-secret-at-least-thirty-two-bytes");update_option("vpm_booking_automation_enabled",true);update_option("vpm_booking_consent_text","Fixture consent; not published.");update_option("vpm_booking_consent_version","browser-fixture-v1");VPM\\Booking\\Consent::request("browser-fixture@example.invalid",true,"127.0.0.1");global $wpdb;$row=$wpdb->get_row("SELECT payload FROM ".VPM\\Booking\\DB::table("outbox")." WHERE topic=\'marketing.confirmation_requested\'",ARRAY_A);echo json_encode(VPM\\Booking\\Config::open($row["payload"]));'
 payload=json.loads(php(code))
 def contact_state():return php('echo VPM\\Booking\\DB::row("contacts","email","browser-fixture@example.invalid")["state"];')
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
  url=payload['confirmation_url'].replace('https://127.0.0.1:8088','http://127.0.0.1:8088')
  r=page.goto(url,wait_until='networkidle');assert r.status==200 and contact_state()=='pending',(r.status,contact_state(),page.locator('body').inner_text()[:500])
  assert r.headers.get('referrer-policy')=='no-referrer'and 'noindex'in r.headers.get('x-robots-tag','')
  assert page.locator('script[src],img[src]').count()==0
  page.get_by_role('button',name='Anmeldung bestätigen').click();page.wait_for_load_state('networkidle');assert contact_state()=='confirmed'
  unsub=payload['unsubscribe_url'].replace('https://127.0.0.1:8088','http://127.0.0.1:8088');page.goto(unsub,wait_until='networkidle');assert contact_state()=='confirmed'
  page.get_by_role('button',name='Abmelden',exact=True).click();page.wait_for_load_state('networkidle');assert contact_state()=='unsubscribed'
  denied=page.request.get(base+'/wp-json/vpm-booking/v1/bookings/00000000-0000-0000-0000-000000000000');assert denied.status in[401,403]
  denied=page.request.post(base+'/wp-json/vpm-booking/v1/stripe/webhook',data='{}',headers={'Content-Type':'application/json','Stripe-Signature':'t=1,v1=bad'});assert denied.status in[400,403]
  page.goto(base+'/',wait_until='networkidle');assert page.locator('script[src*="stripe"],iframe[src*="stripe"]').count()==0
  b.close()
 print('Browser consent tests passed: GET never subscribes/unsubscribes, explicit POST buttons work, referrer/noindex protection, no tracking assets, private booking API denied.')
finally:
 php('global $wpdb;foreach(["contacts","consent_log","outbox","rate_limits"]as $name)$wpdb->query("DELETE FROM ".VPM\\Booking\\DB::table($name));foreach('+lit(settings)+'as $key=>$value){if($value===null)delete_option($key);else update_option($key,$value);}')
