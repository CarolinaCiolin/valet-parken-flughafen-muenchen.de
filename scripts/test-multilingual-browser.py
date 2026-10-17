"""Local-only review: English fixture pages must be published by guarded PHP first."""
from pathlib import Path
from playwright.sync_api import sync_playwright
import json
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088';report=[]
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 for path in ['/','/preise/','/valet-parken-sicherheit/','/ueber-uns/','/en/','/en/prices/','/en/valet-parking-safety/','/en/about-us/']:
  en=path.startswith('/en/');requests=[];page.on('request',lambda r:requests.append(r.url))
  for width in [1440,1024,768,390]:
   page.set_viewport_size({'width':width,'height':1000});r=page.goto(base+path,wait_until='networkidle');assert r.status==200,(path,r.status)
   assert page.locator('html').get_attribute('lang')==('en-GB' if en else 'de-DE'),path
   assert page.locator('h1').count()==1
   nav=page.locator('nav[aria-label="'+('Main navigation' if en else 'Hauptnavigation')+'"]')
   assert nav.get_by_role('link',name='Prices' if en else 'Preise',exact=True,include_hidden=True).count()==1,('Missing original or English menu',path,width)
   assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(path,width)
   assert page.locator('.vpm-language-switcher a').count()==2,(path,width)
   for image in page.locator('main img').all():
    if image.is_visible():image.scroll_into_view_if_needed();image.evaluate('(i)=>i.decode()')
   for a in page.locator('a[href*="parkos.de"],a[href*="parkvia.com"]').all():assert 'sponsored' in (a.get_attribute('rel')or''),(path,a.get_attribute('href'))
   assert not any('tm.tradetracker.net' in x for x in requests),'Unexpected tracking request'
   if path in ['/','/en/']:
    assert page.locator('.vpm-provider-card').count()==6
    assert page.locator('.vpm-provider-art').count()==6
    assert page.locator('.vpm-provider-card figcaption').count()==0
    assert page.locator('.vpm-provider-card').first.locator('.vpm-provider-button').count()==1
    if en:assert page.locator('.vpm-provider-platform').first.inner_text()=='Book with Parkos:'
    note=page.locator('.vpm-booking-note');assert note.count()==1
    gap=note.bounding_box()['y']-page.locator('.vpm-provider-grid').bounding_box()['y']-page.locator('.vpm-provider-grid').bounding_box()['height'];assert gap>=23
    if width==390:assert page.locator('.vpm-hero-image img').evaluate('(i)=>i.currentSrc.startsWith("data:")')
   if en:
    assert page.locator('main').inner_text().find('Verfügbarkeit prüfen')<0
    if width in [1440,390]:page.screenshot(path=str(root/'zwischenstand/englisch'/((path.strip('/').replace('/','-') or 'home')+'-'+str(width)+'.png')),full_page=True)
   report.append({'path':path,'width':width,'status':'passed'})
 page.set_viewport_size({'width':1440,'height':1000});page.goto(base+'/en/');page.get_by_role('navigation',name='Main navigation').get_by_role('link',name='Prices',exact=True).click();assert page.url==base+'/en/prices/'
 page.locator('.vpm-language-switcher').get_by_role('link',name='DE',exact=True).click();assert page.url==base+'/preise/'
 page.goto(base+'/en/');page.set_viewport_size({'width':390,'height':900});page.locator('.vpm-menu-toggle').click();assert page.locator('.vpm-menu-toggle').get_attribute('aria-expanded')=='true'
 assert not errors,errors
 b.close()
(root/'docs/MULTILINGUAL-BROWSER-PRUEFUNG.json').write_text(json.dumps({'checks':report,'navigation':'English menu, paired language switch, mobile toggle passed','tracking':'No TradeTracker requests observed; integration disabled'},indent=2)+'\n')
print('32 route/viewport combinations passed; images, language pairs, cards, spacing, sponsored links, menus, no tracking requests.')
