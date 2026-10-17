"""Exercise admin-only frontend inspection against missing, visible and CSS-hidden banners."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import json,subprocess
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(x):return 'json_decode('+json.dumps(json.dumps(x))+',true)'
keys=['cmplz_options','cmplz_wizard_completed_once']
state=json.loads(php('$out=[];foreach('+lit(keys)+'as $k)$out[$k]=["exists"=>get_option($k,"__absent__")!=="__absent__","value"=>get_option($k)];global $wpdb;$out["banners"]=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}cmplz_cookiebanners",ARRAY_A);echo json_encode($out);'))
results=[]
try:
 opts=state['cmplz_options']['value'].copy();opts.update(dict(regions=['eu'],compile_statistics='no',enable_cookie_banner='yes',uses_ad_cookies='no',uses_firstparty_marketing_cookies='no',uses_thirdparty_services='no',uses_social_media='no',consent_for_anonymous_stats='no',respect_dnt='no'))
 php('$b=cmplz_get_cookiebanner();$b->disable_cookiebanner=false;$b->save();')
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
  page.goto(base+'/wp-admin/tools.php?page=vpm-cookie-diagnostics');assert page.locator('#vpm-consent-start').count()==0
  page.goto(base+'/');assert page.locator('script[src*="consent-diagnostics.js"]').count()==0
  page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**',wait_until='domcontentloaded')
  for required in [False,True]:
   opts['uses_thirdparty_services']='yes'if required else'no'
   php('update_option("cmplz_options",'+lit(opts)+');update_option("cmplz_wizard_completed_once",true);')
   page.goto(base+'/wp-admin/tools.php?page=vpm-cookie-diagnostics',wait_until='networkidle');assert page.locator('#vpm-consent-start').count()==1
   page.locator('#vpm-consent-start').click()
   page.wait_for_function('document.getElementById("vpm-consent-output").value.startsWith("{")')
   report=json.loads(page.locator('#vpm-consent-output').input_value())
   assert report['Banner-Funktion geladen']==required,report
   assert bool(report['Banner'])==required,report
   assert report['Do Not Track']==False and report['Global Privacy Control']==False
   if required:
    assert report['Banner'][0]['display']!='none'and report['Banner'][0]['visibility']=='visible'
    frame=page.locator('#vpm-consent-frame').element_handle().content_frame()
    before=frame.evaluate('document.cookie')
    frame.add_style_tag(content='.cmplz-cookiebanner {display:none!important}')
    page.locator('#vpm-consent-refresh').click();hidden=json.loads(page.locator('#vpm-consent-output').input_value());assert hidden['Banner'][0]['display']=='none'
    assert frame.evaluate('document.cookie')==before,'Read-only refresh changed cookies'
    frame.evaluate('document.cookie="cmplz_diag_test=SENSITIVE_VALUE_DO_NOT_REPORT;path=/"')
    page.locator('#vpm-consent-refresh').click();assert 'SENSITIVE_VALUE_DO_NOT_REPORT'not in page.locator('#vpm-consent-output').input_value()
   # Direct test runs without an iframe or admin cookie, so admin/frontend domain mismatch is irrelevant.
   public=b.new_context();direct=public.new_page()
   response=direct.goto(base+'/?vpm_cookie_diagnostics=1',wait_until='networkidle')
   assert response.headers.get('x-robots-tag')=='noindex, nofollow, noarchive'
   assert 'no-cache'in response.headers.get('cache-control','')
   assert direct.locator('#vpm-consent-frame').count()==0
   assert direct.locator('script[data-no-optimize="1"][src*="consent-diagnostics.js"]').count()==1
   direct.wait_for_function('document.getElementById("vpm-consent-output").value.startsWith("{")')
   direct_report=json.loads(direct.locator('#vpm-consent-output').input_value())
   assert direct_report['Banner-Funktion geladen']==required
   server=direct_report['Serverstatus dieses Frontend-Aufrufs']
   assert server['Assistent abgeschlossen (Frontend)']==True
   assert server['Banner erforderlich (Frontend)']==required
   assert server['Banner-HTML-Hook registriert']==required
   assert server['Banner-Asset-Hook registriert']==required
   assert server['Ohne-Banner-Hook registriert']!=(required)
   assert direct_report['Hinweis'].startswith('Direkt auf der Website')
   direct.goto(base+'/',wait_until='networkidle');assert direct.locator('#vpm-consent-output, script[src*="consent-diagnostics.js"]').count()==0
   public.close()
   results.append({'consent_required':required,'report_matches_frontend':True,'direct_anonymous_report_matches':True})
  b.close()
 (root/'docs/COOKIE-BROWSER-DIAGNOSE-PRUEFUNG.json').write_text(json.dumps({'checks':results,'additional_checks':['PHP diagnostic UI requires authenticated administration; optional frontend diagnostic contains only local public DOM/browser state','Diagnostic script absent on ordinary frontend requests','CSS-hidden banner detected','Read-only inspection does not alter consent cookies','Cookie names only; sentinel value absent from report'],'limitations':['Local WordPress; production browser report still needed','Iframe uses authenticated session; direct test can be anonymous; test parameter may bypass visitor cache','No consent forced; no claim to identify live cause yet','Direct test does not use an iframe or require a shared admin domain']},ensure_ascii=False,indent=2)+'\n')
 print('Browser diagnostics passed: protected admin report, direct anonymous report with matching PHP hook status, absent/loaded/CSS-hidden banner, no consent changes or cookie values.')
finally:
 php('foreach('+lit({k:v for k,v in state.items()if k!='banners'})+'as $k=>$v){if($v["exists"])update_option($k,$v["value"]);else delete_option($k);}global $wpdb;$wpdb->query("DELETE FROM {$wpdb->prefix}cmplz_cookiebanners");foreach('+lit(state['banners'])+'as $row)$wpdb->insert($wpdb->prefix."cmplz_cookiebanners",$row);')
