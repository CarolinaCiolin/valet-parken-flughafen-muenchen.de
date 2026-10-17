"""Check the normal post-import state: English stays private but admin previews include draft cards."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import subprocess
base='http://127.0.0.1:8088';root=Path(__file__).resolve().parents[1]
code='define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);echo pll_get_post(get_option("page_on_front"),"en");'
id=subprocess.check_output(['docker','exec','vpm-local-wp','php','-r',code],text=True).strip()
with sync_playwright()as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
 page.goto(base+'/');assert page.locator('.vpm-provider-card').count()==6;assert page.locator('.vpm-language-switcher').count()==0
 assert page.request.get(base+'/en/prices/').status==404
 page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**',wait_until='domcontentloaded')
 page.goto(base+'/en/?page_id='+id+'&preview=true',wait_until='networkidle');assert page.locator('h1').inner_text()=='Munich Airport Valet Parking';assert page.locator('.vpm-provider-card').count()==6
 assert page.locator('.vpm-provider-card').first.locator('.vpm-provider-button').count()==1
 b.close()
print('English drafts private; authenticated preview includes six draft cards, one Park Drive Fly button.')
