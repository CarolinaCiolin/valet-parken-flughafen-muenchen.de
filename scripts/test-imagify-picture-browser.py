"""Browser test of HTML built by the real Imagify formatter with synthetic WebP data."""
from pathlib import Path
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent;base='http://127.0.0.1:8088'
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 for slug,path in [('index','/'),('preise','/preise/'),('sicherheit','/valet-parken-sicherheit/'),('ueber-uns','/ueber-uns/')]:
  for width in [1440,768,390,320]:
   ctx=browser.new_context(viewport={'width':width,'height':1000});page=ctx.new_page();requested=[];page.on('request',lambda r:requested.append(r.url));markup=(root/f'.runtime/imagify-fixture/{slug}-picture.html').read_text()
   page.route(base+path,lambda route:route.fulfill(status=200,content_type='text/html',body=markup));page.goto(base+path,wait_until='networkidle')
   assert page.locator('picture picture').count()==0
   assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
   for img in page.locator('main img').all():
    if img.is_visible():
     img.scroll_into_view_if_needed();img.evaluate('(i)=>i.decode()');assert img.evaluate('(i)=>i.naturalWidth>1');assert img.get_attribute('alt') and img.get_attribute('width') and img.get_attribute('height')
   if slug=='index' and width<782:assert not any('vpm-valet-hero' in r for r in requested)
   if slug!='index':assert page.locator('.vpm-editorial-image img').first.evaluate('(i)=>i.currentSrc.includes("/uploads/")')
   ctx.close()
  print(slug,'Imagify-Picture-Struktur: vier Breiten, Bilder/Attribute und kein mobiler Hero-Request OK.',flush=True)
 browser.close()
