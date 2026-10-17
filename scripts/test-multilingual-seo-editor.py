from pathlib import Path
import subprocess,json,urllib.request
from bs4 import BeautifulSoup
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','require "/var/www/html/wp-load.php";'+code],text=True)
original=php('echo get_option("blog_public");').strip()
try:
 php('update_option("blog_public",1);')
 for path in ['/','/preise/','/valet-parken-sicherheit/','/ueber-uns/','/en/','/en/prices/','/en/valet-parking-safety/','/en/about-us/']:
  s=BeautifulSoup(urllib.request.urlopen(base+path).read(),'html.parser');can=s.select('link[rel=canonical]');assert len(can)==1 and can[0]['href']==base+path,(path,[c.get('href')for c in can])
  alts=s.select('link[rel=alternate][hreflang]');assert len(alts)==2,(path,alts)
  if path.startswith('/en/'):
   assert s.select_one('meta[name=description]')
  print(path,'canonical + hreflang OK')
finally:php('update_option("blog_public",'+original+');')
with sync_playwright()as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
 page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**',wait_until='domcontentloaded')
 page.goto(base+'/wp-admin/post-new.php?post_type=page',wait_until='domcontentloaded');page.wait_for_function('Boolean(window.wp?.apiFetch&&wp.blocks?.parse&&wp.blocks.getBlockTypes().length>20)')
 result=page.evaluate('''async()=>{const titles=['Munich Airport Valet Parking','Munich Airport Valet Parking Prices','Is Valet Parking Safe?','About Us','Valet Parking Guide'];const pages=(await wp.apiFetch({path:'/wp/v2/pages?context=edit&status=publish,draft&per_page=100'})).filter(p=>titles.includes(p.title.rendered));return pages.map(p=>{const blocks=wp.blocks.parse(p.content.raw);const bad=[];function walk(bs){for(const b of bs){if(b.isValid===false)bad.push(b.name);walk(b.innerBlocks||[])}}walk(blocks);return{id:p.id,title:p.title.rendered,bad,blocks:blocks.length}})}''')
 assert len(result)==5,result
 assert all(not x['bad'] for x in result),result
 print('5 English Gutenberg pages valid:',json.dumps(result))
 (root/'docs/MULTILINGUAL-SEO-EDITOR-PRUEFUNG.json').write_text(json.dumps({'canonical_and_hreflang':'Eight public local review routes: one self canonical and two language alternates each','editor':result,'note':'Temporarily blog_public=1 only on localhost for SEO check, then restored. English review publication only in local fixture.'},indent=2)+'\n')
 b.close()
