"""Local-only integration checks of publisher source snapshots, not production setup."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import json,subprocess,xml.etree.ElementTree as ET,atexit
root=Path(__file__).resolve().parent.parent
base='http://127.0.0.1:8088'
seo=json.loads((root/'docs/SEO-METADATEN.json').read_text())
def fixture(action):
 subprocess.run(['docker','exec','-e','VPM_LOCAL_SETUP=1','vpm-local-wp','php','/tmp/vpm-plugin-compatibility-local.php',action],check=True)
atexit.register(fixture,'disable')
fixture('enable')
results=[]
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 for action in ['deny','accept']:
  ctx=b.new_context(viewport={'width':390,'height':900});page=ctx.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
  page.goto(base,wait_until='networkidle')
  page.locator('.cmplz-cookiebanner').wait_for(state='visible',timeout=15000)
  assert page.evaluate('window.vpmSyntheticConsentRuns||0')==0
  assert page.locator('.vpm-provider-card').count()==6
  assert page.locator('meta[name=vpm-hook-check]').count()==1
  assert page.locator('#vpm-body-hook-check').count()==1 and page.locator('#vpm-footer-hook-check').count()==1
  assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
  page.screenshot(path=str(root/'dist'/f'complianz-test-{action}-390.png'),full_page=False)
  page.locator('.cmplz-'+action).first.click()
  if action=='accept':page.wait_for_function('window.vpmSyntheticConsentRuns===1')
  else:assert page.evaluate('window.vpmSyntheticConsentRuns||0')==0
  page.reload(wait_until='networkidle')
  expected=1 if action=='accept' else 0
  assert page.evaluate('window.vpmSyntheticConsentRuns||0')==expected
  assert not errors,errors
  results.append(action+': synthetic statistics script correctly gated and choice retained')
  print(results[-1],flush=True);ctx.close()
 ctx=b.new_context();page=ctx.new_page()
 for item in seo[:3]:
  page.goto(base+item['url'],wait_until='networkidle')
  assert page.title()==item['title'],(page.title(),item['title'])
  assert page.locator('meta[name=description]').count()==1
  assert page.locator('meta[name=description]').get_attribute('content')==item['description']
  assert page.locator('link[rel=canonical]').count()<=1
  assert page.locator('h1').count()==1
  assert 'noindex' in page.locator('meta[name=robots]').get_attribute('content')
  page.locator('.cmplz-deny').first.click() if page.locator('.cmplz-deny').first.is_visible() else None
  for width in [1440,768,390,320]:
   page.set_viewport_size({'width':width,'height':1000});assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
  print(item['url'],'SEO and four responsive widths OK',flush=True)
 ctx.close();b.close()
# Temporarily allow only the isolated local site to expose a real sitemap, then always restore noindex.
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','require "/var/www/html/wp-load.php";'+code],text=True)
original=php('echo get_option("blog_public");').strip()
try:
 php('update_option("blog_public",1);delete_transient("rank_math_sitemap_index");flush_rewrite_rules();')
 with sync_playwright() as play:
  browser=play.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=browser.new_page()
  for item in seo[:3]:
   page.goto(base+item['url'],wait_until='networkidle')
   assert page.locator('link[rel=canonical]').count()==1
   assert page.locator('link[rel=canonical]').get_attribute('href')==base+item['url']
   assert page.title()==item['title']
  browser.close()
 from urllib.request import urlopen
 with urlopen(base+'/sitemap_index.xml') as r:index=ET.fromstring(r.read())
 ns={'s':'http://www.sitemaps.org/schemas/sitemap/0.9'}
 locations=[x.text for x in index.findall('.//s:loc',ns)]
 assert any('page-sitemap.xml' in x for x in locations),locations
 assert not any('vpm_provider' in x for x in locations),locations
 page_map=next(x for x in locations if 'page-sitemap.xml' in x)
 with urlopen(page_map) as r:urls=[x.text for x in ET.fromstring(r.read()).findall('.//s:loc',ns)]
 for path in ['/','/preise/','/valet-parken-sicherheit/','/ueber-uns/']:assert base+path in urls,(path,urls)
 print('Rank Math XML sitemap: four review pages, no provider archives OK',flush=True)
 results.append('Rank Math XML sitemap includes four published pages and excludes provider archives')
finally:php('update_option("blog_public",'+('1' if original=='1' else '0')+');delete_transient("rank_math_sitemap_index");')
(root/'dist/plugin-compatibility.json').write_text(json.dumps({'results':results,'sources':json.loads((root/'.runtime/compatibility-plugins/sources.json').read_text()),'limitations':['HFCM package unavailable: hooks only tested with local fixture','Apache container does not validate LiteSpeed cache HIT, crawler or sitemap preloading','No production legal setup or real analytics tested']},indent=2))
