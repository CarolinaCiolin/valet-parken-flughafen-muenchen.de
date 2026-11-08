"""Local admin forms, private customer access and cancellation review. No mail/payment transport."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(v):return 'json_decode('+json.dumps(json.dumps(v))+',true)'
assert php('global $wpdb;$n=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$n+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t");echo $n;')=='0'
settings=json.loads(php('echo json_encode(["airports"=>get_option("vpm_booking_airports",null),"automation"=>get_option("vpm_booking_automation_enabled",null)]);'))
checks=[];errors=[];private_requests=[]
def check(v,label):
 assert v,label
 checks.append(label)
try:
 fixture=json.loads(php('$p=VPM\\Booking\\Operations::partner(["airport"=>"FRA","name"=>"Lokaler Testpartner","commission_bps"=>"2000","active"=>"1"]);$b=VPM\\Booking\\Core::create(["partner_id"=>(int)$p["id"],"base_cents"=>10000,"email"=>"browser-operations@example.invalid","offer_terms"=>"Techniktest – keine reale Buchung."],"browser-operations-fixture");$link=VPM\\Booking\\Customer::issue($b);echo json_encode(["booking"=>$b["public_id"],"link"=>$link["url"]]);'))
 with sync_playwright() as p:
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
  ctx=browser.new_context();page=ctx.new_page();page.on('pageerror',lambda e:errors.append(str(e)))
  page.on('request',lambda r:private_requests.append({'url':r.url,'referer':r.headers.get('referer','')}))
  response=page.goto(fixture['link'],wait_until='networkidle')
  page.wait_for_selector('.vpm-customer-details:not([hidden])')
  check(response.status==200 and 'noindex' in response.headers.get('x-robots-tag',''),'Customer landing excluded from index')
  check(response.headers.get('referrer-policy')=='no-referrer' and 'no-store' in response.headers.get('cache-control',''),'Customer landing private/no-referrer')
  check(response.headers.get('x-frame-options')=='DENY','Customer landing cannot be embedded by another website')
  check('#' not in page.url,'Bearer fragment removed immediately from browser address')
  check(page.evaluate('localStorage.length===0 && sessionStorage.length===0'),'No bearer token persisted in browser storage')
  check(page.locator('.vpm-customer-details').inner_text().find(fixture['booking'])>=0,'Customer frontend loads only authorized booking')
  check(page.locator('script[src],link[href]').count()==3,'Only local form CSS, branding CSS and customer script loaded')
  for width in [1440,390]:
   page.set_viewport_size({'width':width,'height':1000})
   check(page.evaluate('document.documentElement.scrollWidth<=innerWidth+1'),'Customer UI has no horizontal overflow '+str(width))
  token=fixture['link'].split('#token=')[1]
  check(all(token not in x['url'] and token not in x['referer'] for x in private_requests),'Token absent from every network URL and referrer')
  page.locator('.vpm-customer-cancel button').click()
  check(php('global $wpdb;echo $wpdb->get_var("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("changes"));')=='0','Unchecked cancellation acknowledgement cannot submit')
  page.locator('[name="acknowledged"]').check();page.locator('.vpm-customer-cancel button').click()
  page.get_by_text('Ihr Stornowunsch wurde zur Prüfung übermittelt. Das ist noch keine Stornobestätigung oder Erstattung.',exact=True).wait_for()
  check(php('echo VPM\\Booking\\Core::booking('+lit(fixture['booking'])+')["state"];')=='requested','Customer cancellation UI does not execute financial cancellation')
  screenshot=ROOT/'zwischenstand/buchungskern';screenshot.mkdir(parents=True,exist_ok=True)
  page.set_viewport_size({'width':1200,'height':1000});page.screenshot(path=str(screenshot/'kundenzugang-test.png'),full_page=True)
  page.reload(wait_until='networkidle')
  check(page.locator('.vpm-customer-details:not([hidden])').count()==0,'Reload without fragment requires fresh link and never exposes cached booking')
  result=page.request.post(BASE+'/wp-json/vpm-booking/v1/customer/view',data={'token':'a'*64})
  check(result.status==403 and 'no-store' in result.headers.get('cache-control',''),'Invalid access denied with private response')
  result=page.request.get(BASE+'/wp-json/vpm-booking/v1/customer/view')
  check(result.status in [404,405],'Customer secret cannot be submitted via GET URL')
  page.goto(BASE+'/wp-admin/tools.php?page=vpm-booking-operations',wait_until='domcontentloaded')
  check('wp-login.php' in page.url,'Anonymous visitors cannot open operations admin')
  page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((ROOT/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click();page.wait_for_load_state('domcontentloaded')
  page.goto(BASE+'/wp-admin/tools.php?page=vpm-booking-operations',wait_until='domcontentloaded',timeout=60000)
  check(page.locator('form:has(input[name="vpm_operation"][value="airports"]) input[name$="[name_de]"]').count()==15,'Admin edits airport names in real form fields')
  form=page.locator('form:has(input[name="vpm_operation"][value="airports"])')
  form.locator('input[name="airports[0][name_de]"]').fill('Frankfurt Testname');form.get_by_role('button',name='Flughafenauswahl speichern').click();page.wait_for_load_state('domcontentloaded')
  check(php('echo VPM\\Booking\\Airports::all()[0]["name_de"];')=='Frankfurt Testname','Authenticated nonce-protected airport save works')
  bad=page.request.post(BASE+'/wp-admin/tools.php?page=vpm-booking-operations',form={'vpm_operation':'airports','_wpnonce':'invalid'})
  check(bad.status==403,'Admin rejects invalid CSRF nonce')
  partner=page.locator('form:has(input[name="vpm_operation"][value="partner"])')
  partner.locator('[name="name"]').fill('Vorbereiteter Testpartner');partner.locator('[name="commission_bps"]').fill('1800');partner.get_by_role('button').click();page.wait_for_load_state('domcontentloaded')
  check(php('global $wpdb;$p=$wpdb->get_row("SELECT * FROM ".VPM\\Booking\\DB::table("partners")." ORDER BY id DESC LIMIT 1",ARRAY_A);echo $p["active"].":".$p["stripe_account"];')=='0:','Partner admin allows inactive preparation without fake payment account')
  search=page.locator('form[method="get"]:has([name="search"])');search.locator('[name="search"]').fill(fixture['booking']);search.get_by_role('button',name='Suchen').click();page.wait_for_load_state('domcontentloaded')
  check(page.get_by_text('browser-operations@example.invalid',exact=False).count()>=1,'Admin booking search finds correct record')
  search=page.locator('form[method="get"]:has([name="search"])');search.locator('[name="search"]').fill("literal%s'@example.invalid");search.get_by_role('button',name='Suchen').click();page.wait_for_load_state('domcontentloaded')
  check(page.get_by_role('heading',name='VPM Buchungsverwaltung',exact=True).count()==1 and 'WordPress database error' not in page.locator('body').inner_text(),'Search safely handles literal percent and quote characters')
  review=page.locator('form:has(input[name="vpm_operation"][value="review"])').first;review.get_by_role('button').click();page.wait_for_load_state('domcontentloaded')
  check(php('global $wpdb;echo $wpdb->get_var("SELECT state FROM ".VPM\\Booking\\DB::table("changes")." LIMIT 1");')=='reviewed','Admin review acknowledges request without executing partner cancellation')
  page.set_viewport_size({'width':1440,'height':1100});page.goto(BASE+'/wp-admin/tools.php?page=vpm-booking-operations',wait_until='domcontentloaded');page.screenshot(path=str(screenshot/'verwaltung-flughaefen-test.png'))
  check(not errors,'No browser JavaScript errors in customer and admin flow')
  browser.close()
 report={'count':len(checks),'checks':checks,'limitations':['Local WordPress only; test data shown in screenshots','No live partner, email delivery, Stripe or Hostinger tested']}
 (ROOT/'docs/BOOKING-OPERATIONS-BROWSER-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
 print(str(len(checks))+' operations/customer browser checks passed.')
finally:
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$wpdb->query("DELETE FROM $t");$s='+lit(settings)+';foreach(["airports"=>"vpm_booking_airports","automation"=>"vpm_booking_automation_enabled"]as $from=>$to){if($s[$from]===null)delete_option($to);else update_option($to,$s[$from]);}')
