"""Loopback-only local/global widgets, parking filters, addon prices and admin templates."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json,datetime
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(v):return 'json_decode('+json.dumps(json.dumps(v))+',true)'
assert php('global $wpdb;$n=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$n+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t");echo $n;')=='0'
settings=json.loads(php('$v=[];foreach(["vpm_booking_frontend_enabled","vpm_booking_products","vpm_booking_product_fixture"]as $k)$v[$k]=get_option($k,null);echo json_encode($v);'))
fixture='/var/www/html/wp-content/mu-plugins/vpm-booking-product-fixture.php';local=ROOT/'.runtime/product-fixture.php';ids=[];checks=[];errors=[]
assert subprocess.run(['docker','exec','vpm-local-wp','test','-e',fixture]).returncode!=0
def check(v,label):
 assert v,label
 checks.append(label)
try:
 partners=json.loads(php('$a=[];foreach(["Testpartner Außen","Testpartner Tiefgarage"]as $name){$p=VPM\\Booking\\Core::partner(["airport"=>"STR","name"=>$name,"stripe_account"=>"acct_productbrowser","commission_bps"=>2000]);$a[]=(int)$p["id"];}update_option("vpm_booking_product_fixture",$a);update_option("vpm_booking_frontend_enabled",true);echo json_encode($a);'))
 local.write_text('''<?php
add_filter('vpm_booking_quote_offers',function($old,$context){if($context['airport']!=='STR')return [];$ids=get_option('vpm_booking_product_fixture');return [['partner_id'=>(int)$ids[0],'base_cents'=>10000,'currency'=>'eur','terms'=>'Techniktest – keine reale Verfügbarkeit.','product_name'=>['de'=>'Außenstellplatz mit Valet','en'=>'Outdoor valet parking'],'parking_type'=>'outdoor','attributes'=>['Fahrzeugübergabe am Terminal','24/7 Betrieb'],'addons'=>[['id'=>'wash','label'=>['de'=>'Fahrzeugwäsche','en'=>'Car wash'],'description'=>'Festes Waschpaket','unit_amount_cents'=>1500,'max_quantity'=>1],['id'=>'charging','label'=>['de'=>'Ladepaket','en'=>'Charging package'],'description'=>'Je Einheit ein vereinbartes 10-kWh-Paket','unit_amount_cents'=>700,'max_quantity'=>3]]],['partner_id'=>(int)$ids[1],'base_cents'=>18000,'currency'=>'eur','terms'=>'Techniktest – keine reale Verfügbarkeit.','product_name'=>'Tiefgaragenstellplatz','parking_type'=>'underground','attributes'=>['Überdachter Stellplatz'],'addons'=>[]]];},10,2);
''')
 subprocess.run(['docker','cp',str(local),'vpm-local-wp:'+fixture],check=True);subprocess.run(['docker','exec','vpm-local-wp','chmod','644',fixture],check=True)
 content='<!-- wp:vpm-booking/form {"airport":"STR","locale":"de"} /-->[vpm_booking_form locale="en"]'
 page_id=int(php('echo wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Techniktest – Flughafenbindung und Zusatzleistungen","post_content"=>'+lit(content)+']);'));ids.append(page_id)
 with sync_playwright()as p:
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=browser.new_page(viewport={'width':1440,'height':1000});page.on('pageerror',lambda e:errors.append(str(e)))
  page.goto(BASE+'/?page_id='+str(page_id),wait_until='networkidle')
  if page.locator('.cmplz-deny').count(): page.locator('.cmplz-deny').first.click()
  fixed=page.locator('.vpm-booking-widget[data-airport-mode="fixed"]');general=page.locator('.vpm-booking-widget[data-airport-mode="select"]')
  check(fixed.evaluate('(el)=>getComputedStyle(el).borderTopStyle==="solid"'),'Widget stylesheet is actually applied, not just enqueued')
  check(fixed.locator('select[name="airport"]').count()==0 and fixed.locator('.vpm-booking-airport-name').inner_text()=='Stuttgart (STR)','Fixed Stuttgart block needs no airport selection')
  check(general.locator('select[name="airport"] option[value]:not([value=""])').count()==15,'Global shortcode provides 15 selectable airports')
  general.locator('select[name="airport"]').select_option('MUC');check(fixed.locator('input[name="airport"]').input_value()=='STR','Global selection cannot change local airport widget')
  fixed.locator('[name="departure"]').fill((datetime.date.today()+datetime.timedelta(days=10)).isoformat()+'T10:00');fixed.locator('[name="return"]').fill((datetime.date.today()+datetime.timedelta(days=18)).isoformat()+'T12:00')
  fixed.locator('.vpm-booking-search button').click();fixed.locator('.vpm-booking-offer').first.wait_for()
  check(fixed.locator('.vpm-booking-offer').count()==2,'Available partners rendered as separate offer cards')
  check(fixed.locator('.vpm-addon:checked').count()==0,'No optional extra selected by default')
  check(fixed.locator('.vpm-parking-filter option[value="underground"]').count()==1,'Filter contains available underground parking')
  fixed.locator('.vpm-parking-filter').select_option('underground');check(fixed.locator('.vpm-booking-offer:visible').count()==1 and 'Tiefgaragen' in fixed.locator('.vpm-booking-offer:visible').inner_text(),'Parking filter shows correct variant')
  fixed.locator('.vpm-parking-filter').select_option('');card=fixed.locator('.vpm-booking-offer').first
  card.locator('.vpm-addon[value="wash"]').check();card.locator('.vpm-addon[value="charging"]').check();card.locator('.vpm-addon-row:has(.vpm-addon[value="charging"]) .vpm-addon-quantity').fill('2');card.locator('.vpm-addon-quantity:visible').dispatch_event('change')
  check('129,00' in card.locator('.vpm-offer-total').inner_text(),'Offer total includes wash and two individually priced charging units')
  card.locator('.vpm-booking-choose').click();check('129,00' in fixed.locator('.vpm-booking-summary').inner_text(),'Selected offer shows full breakdown before enquiry')
  for width in [1440,768,390]:
   page.set_viewport_size({'width':width,'height':1000});check(page.evaluate('document.documentElement.scrollWidth<=innerWidth+1'),'No horizontal overflow with extras '+str(width))
  directory=ROOT/'zwischenstand/buchungskern';directory.mkdir(exist_ok=True)
  page.set_viewport_size({'width':1440,'height':1000});fixed.screenshot(path=str(directory/'angebote-stuttgart-test.png'))
  page.set_viewport_size({'width':390,'height':1000});fixed.screenshot(path=str(directory/'angebote-stuttgart-mobil-test.png'))
  fixed.locator('[name="email"]').fill('products-browser@example.invalid')
  with page.expect_response(lambda r:'/public/requests' in r.url)as pending:fixed.locator('.vpm-booking-request button[type="submit"]').click()
  value=pending.value.json();check(value['amount_cents']==12900,'Submitted enquiry uses authoritative combined price')
  snapshot=json.loads(php('echo json_encode(VPM\\Booking\\Core::snapshot(VPM\\Booking\\Core::booking('+lit(value['reference'])+')));'))
  check(snapshot['airport']=='STR' and len(snapshot['offer_selection']['addons'])==2,'Persisted enquiry keeps fixed airport and selected extras')
  # Real WordPress form for partner-specific product and extra price configuration.
  page.goto(BASE+'/wp-login.php',wait_until='domcontentloaded');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((ROOT/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click();page.wait_for_load_state('domcontentloaded')
  page.goto(BASE+'/wp-admin/tools.php?page=vpm-booking-operations',wait_until='domcontentloaded',timeout=60000)
  form=page.locator('form:has(input[name="vpm_operation"][value="product"])');form.locator('[name="partner_id"]').select_option(str(partners[0]));form.locator('[name="name_de"]').fill('Testvorlage Außen');form.locator('[name="name_en"]').fill('Outdoor test template');form.locator('[name="parking_type"]').select_option('outdoor')
  form.locator('details').first.locator('summary').click();form.locator('[name="addons[wash][enabled]"]').check();form.locator('[name="addons[wash][unit_amount_cents]"]').fill('1500');form.get_by_role('button',name='Angebotsvorlage speichern').click();page.wait_for_load_state('domcontentloaded')
  catalogue=json.loads(php('echo json_encode(VPM\\Booking\\Products::catalogue());'))
  check(len(catalogue)==1 and list(catalogue.values())[0]['offer']['addons'][0]['unit_amount_cents']==1500,'Admin saves partner template and individual wash price through real form')
  check(not errors,'No JavaScript errors in local/global/product flow')
  browser.close()
 report={'count':len(checks),'checks':checks,'limitations':['Local test adapters only, no live availability or payment','Package prices/quantities, not automatic metered fuel or energy billing']};(ROOT/'docs/BOOKING-PRODUCTS-BROWSER-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n');print(str(len(checks))+' local/global product browser checks passed.')
finally:
 subprocess.run(['docker','exec','vpm-local-wp','rm','-f',fixture],check=True);local.unlink(missing_ok=True)
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$wpdb->query("DELETE FROM $t");foreach('+lit(ids)+'as $id)wp_delete_post($id,true);foreach('+lit(settings)+'as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}')
