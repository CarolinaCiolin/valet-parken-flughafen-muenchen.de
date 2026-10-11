"""Validate exported files through a static-only local server (file:// restricted in cloud Chromium)."""
from playwright.sync_api import sync_playwright
base='http://127.0.0.1:8091/'
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page(viewport={'width':1440,'height':1050});errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 for name in ['index.html','preise.html','sicherheit.html','ueber-uns.html']:
  response=page.goto(base+name,wait_until='networkidle');assert response.status==200
  assert page.locator('h1').count()==1
  for width in [1440,390]:
   page.set_viewport_size({'width':width,'height':1050});assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
  page.locator('footer').scroll_into_view_if_needed()
  assert page.locator('main img').evaluate_all('(els)=>els.every(i=>i.complete&&i.naturalWidth>0)')
  print(name,'static images/layout OK',flush=True)
 page.goto(base+'index.html');page.set_viewport_size({'width':1440,'height':1050});page.locator('nav[aria-label="Hauptnavigation"]').get_by_role('link',name='Preise',exact=True).click();assert page.url.endswith('preise.html')
 page.goto(base+'index.html');page.locator('details summary').first.click();assert page.locator('details').first.get_attribute('open') is not None
 page.set_viewport_size({'width':390,'height':844});page.locator('.vpm-menu-toggle').click();assert page.locator('.vpm-menu-toggle').get_attribute('aria-expanded')=='true'
 assert not errors,errors
 print('Static navigation, FAQ and mobile menu OK');b.close()
