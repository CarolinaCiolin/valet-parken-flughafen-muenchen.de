"""Reproduce the public late-init mismatch using a temporary local-only MU fixture."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import json,subprocess
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
def lit(x):return 'json_decode('+json.dumps(json.dumps(x))+',true)'
fixture='/var/www/html/wp-content/mu-plugins/vpm-consent-lifecycle-test.php'
assert subprocess.run(['docker','exec','vpm-local-wp','test','-e',fixture]).returncode!=0,'Existing fixture refused'
keys=['cmplz_options','cmplz_wizard_completed_once','vpm_consent_lifecycle_fixture']
state=json.loads(php('$s=[];foreach('+lit(keys)+'as $k)$s[$k]=["exists"=>get_option($k,"__absent__")!=="__absent__","value"=>get_option($k)];global $wpdb;$s["banners"]=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}cmplz_cookiebanners",ARRAY_A);echo json_encode($s);'))
tmp=root/'.runtime/consent-lifecycle-mu.php';tmp.write_text('''<?php
if(wp_parse_url(get_option('home'),PHP_URL_HOST)!=='127.0.0.1')return;
add_filter('cmplz_site_needs_cookiewarning',function($required){$f=get_option('vpm_consent_lifecycle_fixture',[]);return !empty($f['race'])&&!did_action('init')?false:$required;},9999);
add_action('after_setup_theme',function(){if(!empty(get_option('vpm_consent_lifecycle_fixture',[])['disable_repair'])){remove_action('wp','vpm_reconcile_consent_hooks',PHP_INT_MAX);remove_action('wp_enqueue_scripts','vpm_reconcile_consent_hooks',PHP_INT_MAX-100);}});
''')
results=[]
try:
 subprocess.run(['docker','exec','vpm-local-wp','mkdir','-p','/var/www/html/wp-content/mu-plugins'],check=True)
 subprocess.run(['docker','cp',str(tmp),'vpm-local-wp:'+fixture],check=True);subprocess.run(['docker','exec','vpm-local-wp','chmod','644',fixture],check=True)
 opts=state['cmplz_options']['value'].copy();opts.update(dict(regions=['eu'],compile_statistics='no',enable_cookie_banner='yes',uses_ad_cookies='no',uses_firstparty_marketing_cookies='no',uses_thirdparty_services='no',uses_social_media='no',consent_for_anonymous_stats='no',respect_dnt='yes'))
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
  cases=[('reproduced without repair',True,True,True,True,False,False,False),('late repair',True,False,True,True,False,False,True),('normal initialization',False,False,True,True,False,False,True),('no banner required',True,False,False,True,False,False,False),('wizard incomplete',True,False,True,False,False,False,False),('banner disabled',True,False,True,True,True,False,False),('DNT respected',True,False,True,True,False,True,False)]
  for name,race,disable_repair,required,complete,disabled,dnt,visible in cases:
   opts['uses_thirdparty_services']='yes'if required else'no'
   opts['enable_cookie_banner']='no'if disabled else'yes'
   php('update_option("cmplz_options",'+lit(opts)+');update_option("cmplz_wizard_completed_once",'+lit(complete)+');update_option("vpm_consent_lifecycle_fixture",'+lit(dict(race=race,disable_repair=disable_repair))+');$banner=cmplz_get_cookiebanner();$banner->disable_cookiebanner='+lit(disabled)+';$banner->save();')
   context=b.new_context(viewport={'width':390,'height':900})
   if dnt:context.add_init_script("Object.defineProperty(navigator,'doNotTrack',{get:()=> '1'});")
   page=context.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
   page.goto(base+'/?vpm_cookie_diagnostics=1',wait_until='networkidle');page.wait_for_function('document.getElementById("vpm-consent-output").value.startsWith("{")')
   report=json.loads(page.locator('#vpm-consent-output').input_value());server=report['Serverstatus dieses Frontend-Aufrufs']
   assert bool(page.locator('.cmplz-cookiebanner:visible').count())==visible,(name,report)
   applied=race and not disable_repair and required and complete and not disabled
   assert server['VPM-Spätinitialisierung angewendet']==applied,(name,server)
   if applied or not race:
    assert server['Banner-HTML-Hook registriert'] and server['Banner-Asset-Hook registriert'],(name,server)
    assert not server['Ohne-Banner-Hook registriert'],(name,server)
    assert page.locator('.cmplz-cookiebanner').count()==1
    assert page.locator('script[src*="cookiebanner/js/complianz"]').count()==1
   else:assert server['Ohne-Banner-Hook registriert'] and not server['Banner-HTML-Hook registriert'],(name,server)
   assert not errors,(name,errors)
   if name=='late repair':
    page.locator('.cmplz-cookiebanner:visible .cmplz-deny').click();page.reload(wait_until='networkidle');assert not page.locator('.cmplz-cookiebanner:visible').count(),'Rejection lost'
   results.append({'case':name,'visible':visible,'repair_applied':applied,'result':'passed'})
   context.close()
  b.close()
 (root/'docs/COMPLIANZ-LIFECYCLE-PRUEFUNG.json').write_text(json.dumps({'cases':results,'method':'Temporary MU fixture makes the constructor requirement false until init, reproducing the observed hook mismatch; does not claim to identify which live integration causes the timing difference.','checks':['Single script and single banner, no duplicate initialization','No-warning output removed only in repaired state','Rejected choice retained after reload','No requirement, incomplete wizard, explicit disabled banner and DNT honored','No JavaScript errors at mobile viewport'],'limitations':['Local Complianz 7.5.5; public diagnostic shows 7.5.6','Fix not deployed to Hostinger by this test']},ensure_ascii=False,indent=2)+'\n')
 print('Lifecycle tests passed: reproduced missing hooks, targeted recovery, normal/no requirement/incomplete/disabled/DNT, single output and retained rejection.')
finally:
 subprocess.run(['docker','exec','vpm-local-wp','rm','-f',fixture],check=True)
 php('foreach('+lit({k:v for k,v in state.items()if k!='banners'})+'as $k=>$v){if($v["exists"])update_option($k,$v["value"]);else delete_option($k);}global $wpdb;$wpdb->query("DELETE FROM {$wpdb->prefix}cmplz_cookiebanners");foreach('+lit(state['banners'])+'as $row)$wpdb->insert($wpdb->prefix."cmplz_cookiebanners",$row);')
 tmp.unlink(missing_ok=True)
