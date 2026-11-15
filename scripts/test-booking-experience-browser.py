"""Loopback integration: assigned partner, real POSTs, euros, hours and search alternatives."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess,json,secrets,datetime
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088'
def php(code):
 return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(v):return 'json_decode('+json.dumps(json.dumps(v))+',true)'
assert php('global $wpdb;$n=0;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$n+=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t");echo $n;')=='0'
checks=[];errors=[];options={};data={};password=secrets.token_hex(20)
def check(v,label):
 assert v,label
 checks.append(label)
def login(page,user,pw):
 page.goto(BASE+'/?vpm_partner_login=1',wait_until='networkidle');page.locator('#user_login').fill(user);page.locator('#user_pass').fill(pw)
 with page.expect_navigation(wait_until='networkidle'):page.locator('#wp-submit').click()
try:
 options=json.loads(php('echo json_encode(["vpm_booking_products"=>get_option("vpm_booking_products",null),"vpm_booking_frontend_enabled"=>get_option("vpm_booking_frontend_enabled",null)]);'))
 code=r'''
 update_option('vpm_booking_frontend_enabled',true);
 $a=VPM\Booking\Core::partner(['airport'=>'MUC','name'=>'TEST Parkservice Müller','commission_bps'=>2000]);
 $b=VPM\Booking\Core::partner(['airport'=>'MUC','name'=>'TEST Partner B geheim','commission_bps'=>2000]);
 $c=VPM\Booking\Core::partner(['airport'=>'STR','name'=>'TEST Stuttgart','commission_bps'=>2000]);
 VPM\Booking\Adapters::configure((int)$a['id'],['mode'=>'manual']);$keys=[];
 foreach(['outdoor','covered','garage','underground']as $type){VPM\Booking\Products::save_template(['partner_id'=>(string)$a['id'],'name_de'=>'TEST '.$type,'name_en'=>'TEST '.$type,'parking_type'=>$type]);$key='';foreach(VPM\Booking\Products::catalogue()as $id=>$item)if($item['offer']['parking_type']===$type)$key=$id;$keys[$type]=$key;VPM\Booking\Adapters::save_rate((int)$a['id'],['product_id'=>$key,'price_cents'=>'1050','pricing'=>'24h','capacity'=>'20','charging_capacity'=>'2','enabled'=>'1']);}
 $keys['first']=array_key_first(VPM\Booking\Products::catalogue());VPM\Booking\Commerce::save((int)$a['id'],['services'=>['valet'=>['enabled'=>1,'fee_cents'=>2000]]]);
 $u=wp_insert_user(['user_login'=>'vpm_experience_'.bin2hex(random_bytes(4)),'user_pass'=>PASSWORD,'user_email'=>'test-owner@example.invalid','role'=>'subscriber']);VPM\Booking\Tenants::assign((int)$a['id'],$u,'owner');
 $page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Experience demo','post_content'=>'[vpm_booking_form airport="MUC" service="valet" flow="steps" layout="compact"]']);
 echo json_encode(['partner'=>(int)$a['id'],'other'=>(int)$b['id'],'stuttgart'=>(int)$c['id'],'user'=>$u,'login'=>get_userdata($u)->user_login,'page'=>$page,'keys'=>$keys]);
 '''.replace('PASSWORD',lit(password))
 data=json.loads(php(code))
 with sync_playwright()as pw:
  browser=pw.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);owner=browser.new_page(viewport={'width':1440,'height':1100});owner.on('pageerror',lambda e:errors.append(str(e)))
  login(owner,data['login'],password);check('vpm_partner_portal=1'in owner.url,'Partner login redirects straight to portal');check(owner.locator('#wpadminbar').count()==0,'Partner portal has no WordPress admin bar');check(owner.locator('.vpm-account').inner_text().find(data['login'])>=0,'Account name visible');check(owner.locator('[name="partner"] option').count()==0,'Single-partner owner cannot select foreign partner')
  owner.goto(BASE+'/wp-admin/',wait_until='networkidle');check('vpm_partner_portal=1'in owner.url,'Direct dashboard navigation returns assigned partner to portal')
  portal=BASE+'/?vpm_partner_portal=1&partner='+str(data['partner'])
  owner.goto(portal+'#inventory',wait_until='networkidle');owner.locator('#inventory details > summary').filter(has_text='Bestand ändern').first.click();form=owner.locator('#inventory form').first
  form.locator('[name="capacity"]').fill('0');form.locator('[name="charging_capacity"]').fill('0')
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button',name='Kapazitäten speichern').click()
  check(owner.url.endswith('#inventory')and owner.locator('.vpm-save-success').is_visible(),'0/0 saves successfully and stays in inventory with confirmation');check('0 Plätze'in owner.locator('#inventory').inner_text(),'Saved zero inventory visible')
  owner.locator('#inventory details > summary').filter(has_text='Bestand ändern').first.click();form=owner.locator('#inventory form').first;form.locator('[name="capacity"]').fill('0');form.locator('[name="charging_capacity"]').fill('2')
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button',name='Kapazitäten speichern').click()
  check('Ladeplätze darf'in owner.locator('[role="alert"]').inner_text(),'Invalid charging subset has specific server error');check(owner.locator('.vpm-portal-nav').is_visible()and owner.locator('#inventory').is_visible(),'Validation failure retains partner and current section');check(owner.locator('#inventory [name="charging_capacity"]').first.input_value()=='2','Rejected input retained for correction')
  # Restore capacity via direct owned service call to avoid manipulating hidden failed forms.
  php('VPM\\Booking\\Adapters::save_capacity('+str(data['partner'])+','+lit({'product_id':data['keys']['first'],'capacity':'20','charging_capacity':'2'})+');')
  owner.goto(portal+'#prices',wait_until='networkidle');check('10,50 €'in owner.locator('#prices').inner_text(),'Price list shows saved euro tariffs');owner.locator('#prices details > summary').filter(has_text='Parkpreis ändern').first.click();form=owner.locator('#prices form').first;check(form.locator('[name="price_cents"]').input_value()=='10,50','Euro tariff editor prefilled accurately: '+repr(form.locator('[name="price_cents"]').input_value()));form.locator('[name="price_cents"]').fill('12,75')
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button',name='Tarif speichern').click()
  check('12,75 €'in owner.locator('#prices').inner_text(),'Decimal-comma price survives real POST and reload')
  owner.goto(portal+'#service-times',wait_until='networkidle');form=owner.locator('#service-times form');group=form.locator('fieldset').filter(has=owner.locator('legend:text-is("Valet")')).first
  check(group.get_by_label('Rund um die Uhr (24/7)',exact=False).is_checked(),'Empty legacy schedule becomes explicit 24/7 control');group.locator('[name="services[valet][fee_cents]"]').fill('25,50')
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button',name='Services und Zeiten speichern').click()
  check(owner.locator('[name="services[valet][fee_cents]"]').input_value()=='25,50','Valet euro fee saved through real form');check(owner.locator('#service-times input[type="time"]').count()>20,'Service schedule offers time inputs and midnight ranges')
  form=owner.locator('#service-times form');group=form.locator('fieldset').filter(has=owner.locator('legend:text-is("Valet")')).first;group.get_by_label('Rund um die Uhr (24/7)',exact=False).uncheck();monday=group.locator('.vpm-schedule-days > fieldset').first;monday.locator('input[type="checkbox"]').check();monday.locator('input[type="time"]').nth(0).fill('22:00');monday.locator('input[type="time"]').nth(1).fill('06:00');group.get_by_role('button',name='Montagszeiten für alle Tage übernehmen').click()
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button',name='Services und Zeiten speichern').click()
  check(php('echo count(VPM\\Booking\\Commerce::get('+str(data['partner'])+')["services"]["valet"]["hours"]);')=='7','Time controls and copy-to-all save seven overnight schedules')
  form=owner.locator('#service-times form');group=form.locator('fieldset').filter(has=owner.locator('legend:text-is("Valet")')).first;group.get_by_label('Rund um die Uhr (24/7)',exact=False).check()
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button',name='Services und Zeiten speichern').click()
  check(php('echo count(VPM\\Booking\\Commerce::get('+str(data['partner'])+')["services"]["valet"]["hours"]);')=='0','24/7 switch clears restrictions and preserves service availability')
  owner.goto(portal+'#products',wait_until='networkidle');check(owner.locator('.vpm-saved-products article').count()==4,'Saved parking types listed before editor');check(not owner.locator('.vpm-product-editor').get_attribute('open'),'Add-product editor collapsed initially')
  owner.goto(BASE+'/?vpm_partner_portal=1&partner='+str(data['other']),wait_until='networkidle');check('Kein freigegebener Partnerzugang'in owner.locator('body').inner_text()and owner.locator('.vpm-portal-nav').count()==0,'Forged foreign GET cannot open partner management')
  # Public booking search and refresh contain travel fields only, no customer information.
  visitor=browser.new_page(viewport={'width':1280,'height':1100});visitor.on('pageerror',lambda e:errors.append(str(e)));visitor.goto(BASE+'/?page_id='+str(data['page']),wait_until='networkidle');root=visitor.locator('.vpm-booking-widget');check(root.locator('[name="airport"]').input_value()=='MUC'and root.locator('[name="airport"] option').count()>10,'MUC preselection remains editable');check(root.locator('[name="preferred_partner"] option').count()==3,'Provider list includes only current-airport active partners');root.locator('[name="airport"]').select_option('STR');check(root.locator('[name="preferred_partner"] option').count()==2,'Airport change refreshes scoped provider list');root.locator('[name="airport"]').select_option('MUC')
  start=(datetime.date.today()+datetime.timedelta(days=12)).isoformat();end=(datetime.date.today()+datetime.timedelta(days=19)).isoformat();root.locator('[name="departure"]').fill(start+'T12:00');root.locator('[name="return"]').fill(end+'T12:00');root.locator('[name="parking_type"]').select_option('covered');root.locator('.vpm-booking-search button[type="submit"]').click();visitor.locator('.vpm-booking-offer').wait_for();check(visitor.locator('.vpm-booking-offer').count()==1,'Search returns selected parking type only')
  visitor.reload(wait_until='networkidle');visitor.locator('.vpm-booking-offer').wait_for();check(root.locator('[name="parking_type"]').input_value()=='covered','Reload restores search and fetches fresh offers')
  # Preferred provider without a tariff produces real alternatives, not silent substitution.
  root.locator('.vpm-wizard-back').click();root.locator('[name="preferred_partner"]').select_option(str(data['other']));root.locator('.vpm-booking-search button[type="submit"]').click();visitor.locator('.vpm-booking-alternatives').wait_for();check(visitor.locator('.vpm-booking-offer').count()==0,'No offer from unavailable preferred provider');check(visitor.locator('.vpm-booking-alternatives button').count()==4,'Alternative buttons represent four actually available types');visitor.locator('.vpm-booking-alternatives button').first.click();visitor.locator('.vpm-booking-offer').wait_for();check(root.locator('[name="preferred_partner"]').input_value()==str(data['partner']),'Alternative choice explicitly changes provider selection')
  visitor.set_viewport_size({'width':390,'height':844});visitor.reload(wait_until='networkidle');visitor.locator('.vpm-booking-offer').wait_for();check(visitor.evaluate('document.documentElement.scrollWidth<=innerWidth+1'),'Compact booking view fits mobile width')
  root.locator('.vpm-booking-choose').first.click();root.locator('.vpm-wizard-next').click();check(root.locator('[name="customer_first_name"]').is_visible(),'Checkout collects customer and vehicle identity');root.locator('[name="customer_kind"]').select_option('business');check(root.locator('[name="customer_company"]').is_visible(),'Business selection opens billing fields');root.locator('[name="customer_kind"]').select_option('private');check(not root.locator('[name="customer_company"]').is_visible(),'Private customer hides irrelevant company fields')
  for key,value in {'first_name':'Max','last_name':'Mustermann','phone':'+49 000 000000','plate':'TEST 123','make':'Testfahrzeug'}.items():root.locator('[name="customer_'+key+'"]').fill(value)
  root.locator('[name="email"]').fill('customer@example.invalid');root.locator('.vpm-wizard-next').click();visitor.wait_for_function("document.querySelector('.vpm-booking-widget').dataset.step==='5'");root.locator('.vpm-booking-request button[type="submit"]').click();visitor.wait_for_function("document.querySelector('.vpm-booking-widget').dataset.step==='6'");check('VPM-'in root.locator('.vpm-booking-status').inner_text(),'Request completes with readable booking number')
  owner.goto(portal+'#bookings',wait_until='networkidle');check('Mustermann'in owner.locator('#bookings').inner_text()and'TEST 123'in owner.locator('#bookings').inner_text(),'Partner sees name and vehicle on new booking');check('customer@example.invalid'not in owner.content(),'Partner HTML does not expose customer email');form=owner.locator('#bookings form:has(input[value="cancel"])')
  with owner.expect_navigation(wait_until='networkidle'):form.get_by_role('button').click()
  check(owner.locator('.vpm-cancel-preview').is_visible(),'First cancellation click opens server-rendered review step');check(php('global $wpdb;echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".VPM\\Booking\\DB::table("fulfilment")." WHERE action=%s","cancel"));')=='0','Cancellation preview creates no cancellation job');owner.locator('.vpm-cancel-preview [name="acknowledged"]').check()
  with owner.expect_navigation(wait_until='networkidle'):owner.locator('.vpm-cancel-preview button').click()
  php('VPM\\Booking\\Flow::run();');owner.goto(portal+'#requests',wait_until='networkidle');check('storniert / unpaid'in owner.locator('#requests').inner_text(),'Completed cancellation remains visible in partner history')
  admin=browser.new_page();login(admin,'vpm_local_admin',(ROOT/'.runtime/admin-password').read_text().strip());admin.goto(BASE+'/wp-admin/tools.php?page=vpm-booking-operations',wait_until='networkidle');coupon=admin.locator('form:has(input[name="vpm_operation"][value="coupon"])');coupon.locator('[name="code"]').fill('EURO10');coupon.locator('[name="kind"]').select_option('percent');coupon.locator('[name="value"]').fill('10,00');coupon.locator('[name="budget_cents"]').fill('100,00')
  with admin.expect_navigation(wait_until='networkidle'):coupon.get_by_role('button',name='Gutschein anlegen').click()
  check(json.loads(php('global $wpdb;echo json_encode($wpdb->get_row("SELECT value,budget_cents FROM ".VPM\\Booking\\DB::table("coupons")." ORDER BY id DESC LIMIT 1",ARRAY_A));'))=={'value':'1000','budget_cents':'10000'},'Admin coupon percent and euro budget converted exactly')
  admin.goto(BASE+'/wp-admin/tools.php?page=vpm-booking-operations&partner='+str(data['partner']),wait_until='networkidle');check('ausschließlich für diesen Partner'in admin.locator('body').inner_text()and'TEST Partner B geheim'not in admin.locator('body').inner_text(),'Platform partner click opens scoped partner administration')
  output=ROOT/'zwischenstand/buchungskern';owner.goto(portal+'#prices',wait_until='networkidle');owner.screenshot(path=str(output/'parkpreise-0.12-test.png'));visitor.reload(wait_until='networkidle');visitor.locator('.vpm-booking-offer').wait_for();visitor.screenshot(path=str(output/'buchungssuche-0.12-test.png'))
  check(not errors,'No JavaScript runtime errors');browser.close()
 (ROOT/'docs/BOOKING-EXPERIENCE-BROWSER-PRUEFUNG.json').write_text(json.dumps({'count':len(checks),'checks':checks},ensure_ascii=False,indent=2)+'\n');print(len(checks),'experience browser checks passed')
finally:
 php('global $wpdb;foreach($wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->prefix."vpm_booking_%"))as $t)$wpdb->query("DELETE FROM $t");'+('wp_delete_post('+str(data['page'])+',true);require_once ABSPATH."wp-admin/includes/user.php";wp_delete_user('+str(data['user'])+');'if data else '')+'foreach('+lit(options)+'as $k=>$v){if($v===null)delete_option($k);else update_option($k,$v);}')
