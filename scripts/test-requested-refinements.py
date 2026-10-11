"""Actual frontend behavior: requests, destinations, placement and button widths."""
from pathlib import Path
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent
base='http://127.0.0.1:8088'
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 for width in [390,768,1440]:
  ctx=b.new_context(viewport={'width':width,'height':900});page=ctx.new_page();requests=[];page.on('request',lambda r:requests.append(r.url))
  page.goto(base+'/',wait_until='networkidle')
  assert abs(page.locator('.vpm-hero').bounding_box()['width']-width)<1
  assert page.locator('.vpm-process-grid .vpm-action').count()==0
  assert page.locator('.vpm-step-number').all_text_contents()==['1','2','3','4','5']
  hero=page.locator('.vpm-hero-image img')
  if width<782:
   assert not hero.is_visible()
   assert not any('vpm-valet-hero' in u for u in requests),requests
   assert hero.evaluate('(i)=>i.currentSrc.startsWith("data:")')
   print(width,'kein Hero-Bild angefordert; kompakte USP-Höhe',round(page.locator('.vpm-benefits').bounding_box()['height']),flush=True)
  else:
   assert hero.is_visible() and hero.evaluate('(i)=>i.naturalWidth>1')
   assert any('vpm-valet-hero' in u for u in requests)
  card=page.locator('.vpm-provider-card').filter(has=page.get_by_role('heading',name='Park Drive Fly',exact=True))
  assert card.locator('.vpm-provider-button').count()==1
  assert 'parkos.de' in card.locator('.vpm-provider-button').get_attribute('href')
  assert page.locator('.vpm-header-cta').get_attribute('href')==base+'/#anbieter'
  assert page.get_by_role('link',name='Münchener Anbieter vergleichen',exact=True).get_attribute('href')==base+'/preise/'
  price_link=page.get_by_role('link',name='Detaillierte Preisinformationen',exact=True)
  assert price_link.count()==1
  assert price_link.locator('xpath=..').get_attribute('class').endswith('vpm-price-navigation')
  assert price_link.bounding_box()['y'] < page.locator('.vpm-provider-card').first.bounding_box()['y']
  assert page.locator('.vpm-header-cta').is_visible()==(width>760)
  if width==390:assert page.locator('.vpm-header').bounding_box()['height']<100
  if width==1440:page.screenshot(path=str(root/'dist/startseite-erster-bildschirm.png'))
  if width==390:page.screenshot(path=str(root/'dist/startseite-mobil-einstieg.png'))
  ctx.close()
 page=b.new_page(viewport={'width':1440,'height':1000})
 page.goto(base+'/preise/',wait_until='networkidle')
 buttons=page.locator('table tbody td:last-child a').all();widths=[x.bounding_box()['width'] for x in buttons]
 assert len(widths)==7 and max(widths)-min(widths)<1,widths
 page.locator('table').screenshot(path=str(root/'dist/preistabelle-desktop.png'))
 page.locator('.vpm-header-cta').click();page.wait_for_url(base+'/#anbieter');assert page.locator('#anbieter').is_visible()
 print('Park Drive Fly: nur Parkos; Header-Ziel überprüft; Tabellenbuttons gleich breit.',flush=True)
 page.goto(base+'/valet-parken-sicherheit/',wait_until='networkidle')
 note=page.locator('.vpm-article-note');assert note.count()==1
 assert note.locator('p').inner_text().startswith('Die Angaben beruhen auf den veröffentlichten Angebotsbeschreibungen')
 assert page.locator('.vpm-editorial-card').filter(has_text='Die Angaben beruhen auf den veröffentlichten Angebotsbeschreibungen').count()==0
 assert page.locator('main .vpm-content > :last-child').get_attribute('class')=='alignwide vpm-article-note'
 for path,title,name in [('/valet-parken-sicherheit/','Darf der Valet-Service das Fahrzeug umparken?','sicherheit-umparken'),('/ueber-uns/','Wie wir uns finanzieren','ueber-uns-finanzierung')]:
  page.goto(base+path,wait_until='networkidle');section=page.locator('section').filter(has=page.get_by_role('heading',name=title,exact=True))
  image=section.locator('img');assert image.count()==1;image.scroll_into_view_if_needed();image.evaluate('(i)=>i.decode()')
  assert image.evaluate('(i)=>i.naturalWidth>1')
  section.screenshot(path=str(root/'dist'/f'{name}.png'))
  print(title,': Bild geladen, Detail-Screenshot erstellt.',flush=True)
 page.locator('.vpm-subpage-intro img').scroll_into_view_if_needed();page.locator('.vpm-subpage-intro img').evaluate('(i)=>i.decode()');page.locator('.vpm-subpage-intro').screenshot(path=str(root/'dist/ueber-uns-einstieg.png'))
 b.close()
