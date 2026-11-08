"""Local voucher preview, expired/redeemed messages and final server-funded discount."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(v):return 'json_decode('+json.dumps(json.dumps(v))+',true)'
assert php('global $wpdb;$n=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$n+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t");echo $n;')=='0'
options=json.loads(php('$a=[];foreach(["vpm_booking_frontend_enabled","vpm_booking_voucher_fixture"]as $k)$a[$k]=get_option($k,null);echo json_encode($a);'))
fixture='/var/www/html/wp-content/mu-plugins/vpm-booking-voucher-fixture.php';local=ROOT/'.runtime/voucher-fixture.php';ids=[];checks=[];errors=[]
assert subprocess.run(['docker','exec','vpm-local-wp','test','-e',fixture]).returncode!=0
def check(value,label):
 assert value,label
 checks.append(label)
try:
 php('$p=VPM\\Booking\\Core::partner(["airport"=>"FRA","name"=>"Lokaler Gutschein-Testpartner","stripe_account"=>"acct_voucherbrowser","commission_bps"=>2000]);update_option("vpm_booking_voucher_fixture",(int)$p["id"]);update_option("vpm_booking_frontend_enabled",true);foreach(["VALID5","EXPIRED5","REDEEMED5"]as $code)VPM\\Booking\\Core::coupon(["code"=>$code,"kind"=>"percent","value"=>500,"starts_at"=>time()-172800,"ends_at"=>$code==="EXPIRED5"?time()-86400:time()+86400,"max_uses"=>1,"budget_cents"=>10000]);$b=VPM\\Booking\\Core::create(["partner_id"=>(int)$p["id"],"base_cents"=>10000,"email"=>"voucher-browser@example.invalid","coupon"=>"REDEEMED5"],"used-browser-voucher");VPM\\Booking\\DB::tx(function()use($b){VPM\\Booking\\Core::coupon_state(VPM\\Booking\\Core::booking($b["public_id"],true),"consumed");});')
 local.write_text("<?php add_filter('vpm_booking_quote_offers',function(){return [['partner_id'=>(int)get_option('vpm_booking_voucher_fixture'),'base_cents'=>10000,'currency'=>'eur','terms'=>'Techniktest – keine echte Buchung.']];});")
 subprocess.run(['docker','cp',str(local),'vpm-local-wp:'+fixture],check=True);subprocess.run(['docker','exec','vpm-local-wp','chmod','644',fixture],check=True)
 page_id=int(php('echo wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Gutschein-Techniktest","post_content"=>\'[vpm_booking_form airport="FRA"]\']);'));ids.append(page_id)
 import datetime
 with sync_playwright()as p:
  browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=browser.new_page(viewport={'width':1440,'height':1100});page.on('pageerror',lambda e:errors.append(str(e)))
  page.goto(BASE+'/?page_id='+str(page_id),wait_until='networkidle')
  if page.locator('.cmplz-deny').count():page.locator('.cmplz-deny').first.click()
  widget=page.locator('.vpm-booking-widget');widget.locator('[name="departure"]').fill((datetime.date.today()+datetime.timedelta(days=10)).isoformat()+'T10:00');widget.locator('[name="return"]').fill((datetime.date.today()+datetime.timedelta(days=18)).isoformat()+'T12:00');widget.locator('.vpm-booking-search button').click();widget.locator('.vpm-booking-choose').click()
  widget.locator('[name="email"]').fill('voucher-browser@example.invalid')
  for code,message in [('EXPIRED5','Leider ist der Gutschein bereits abgelaufen.'),('REDEEMED5','Leider ist der Gutschein bereits eingelöst.')]:
   widget.locator('[name="coupon"]').fill(code);widget.locator('.vpm-coupon-apply').click();widget.locator('.vpm-coupon-status').filter(has_text=message).wait_for();check(widget.locator('.vpm-coupon-status').inner_text()==message,code+' displays precise German error')
  widget.locator('[name="coupon"]').fill('EXPIRED5')
  with page.expect_response(lambda r:'/public/requests' in r.url)as rejected:widget.locator('.vpm-booking-request button[type="submit"]').click()
  widget.locator('.vpm-coupon-status').filter(has_text='Leider ist der Gutschein bereits abgelaufen.').wait_for();check(rejected.value.status==400,'Final submission rechecks expiry and shows field-level error')
  widget.locator('[name="coupon"]').fill('VALID5');widget.locator('.vpm-coupon-apply').click();widget.locator('.vpm-coupon-discount').wait_for();check('5,00' in widget.locator('.vpm-coupon-discount').inner_text() and '95,00' in widget.locator('.vpm-booking-summary .vpm-booking-total').last.inner_text(),'Discount and reduced total appear before submission')
  check('abgelaufen' not in widget.locator('.vpm-booking-status').inner_text(),'Successful voucher preview clears previous submission error')
  check(php('echo VPM\\Booking\\DB::row("coupons","code","VALID5")["held_uses"];')=='0','Preview does not spend voucher')
  widget.locator('[name="email"]').fill('changed@example.invalid');check(widget.locator('.vpm-coupon-discount').count()==0,'Changing email removes stale discounted preview')
  widget.locator('[name="email"]').fill('voucher-browser@example.invalid');widget.locator('.vpm-coupon-apply').click();widget.locator('.vpm-coupon-discount').wait_for()
  directory=ROOT/'zwischenstand/buchungskern';widget.screenshot(path=str(directory/'gutschein-preisuebersicht-test.png'))
  with page.expect_response(lambda r:'/public/requests' in r.url)as pending:widget.locator('.vpm-booking-request button[type="submit"]').click()
  data=pending.value.json();check(data['amount_cents']==9500 and data['discount_cents']==500,'Final enquiry revalidates and stores discounted total')
  q=json.loads(php('echo json_encode(VPM\\Booking\\Core::snapshot(VPM\\Booking\\Core::booking('+lit(data['reference'])+')));'));check(q['partner']==8000 and q['platform']==1500,'Discount charged exclusively to platform commission')
  check(not errors,'No browser JavaScript errors')
  browser.close()
 report={'count':len(checks),'checks':checks,'limitations':['Local fixture codes only; no production payment or email sent']};(ROOT/'docs/BOOKING-VOUCHERS-BROWSER-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n');print(str(len(checks))+' voucher browser checks passed.')
finally:
 subprocess.run(['docker','exec','vpm-local-wp','rm','-f',fixture],check=True);local.unlink(missing_ok=True)
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$wpdb->query("DELETE FROM $t");foreach('+lit(ids)+'as $id)wp_delete_post($id,true);foreach('+lit(options)+'as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}')
