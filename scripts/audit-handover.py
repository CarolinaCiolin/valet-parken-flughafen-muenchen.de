"""Functional audit of the isolated review site; no partner requests or publishing."""
import json
from pathlib import Path
from urllib.parse import urlparse,urldefrag
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent
base='http://127.0.0.1:8088'
report={'scope':'Lokale Prüfung, keine Aussage zur Geschwindigkeit auf Hostinger oder Partner-Erreichbarkeit.','pages':[]}
def luminance(color):
 c=[int(color[i:i+2],16)/255 for i in (1,3,5)]
 return sum(x*w for x,w in zip([x/12.92 if x<=.04045 else ((x+.055)/1.055)**2.4 for x in c],[.2126,.7152,.0722]))
def contrast(a,b):
 x,y=sorted([luminance(a),luminance(b)])
 return round((y+.05)/(x+.05),2)
report['contrast']={'body_on_cream':contrast('#292d33','#f6f3ec'),'button_navy_on_gold':contrast('#101c2c','#b99a5b'),'caption_on_cream':contrast('#565960','#f6f3ec')}
assert all(v>=4.5 for v in report['contrast'].values())
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=browser.new_page(viewport={'width':1440,'height':1000})
 for slug,path in [('startseite','/'),('preise','/preise/'),('sicherheit','/valet-parken-sicherheit/'),('ueber-uns','/ueber-uns/')]:
  responses=[];errors=[]
  def collect(r):responses.append(r)
  def onerror(e):errors.append(str(e))
  page.on('response',collect);page.on('pageerror',onerror)
  response=page.goto(base+path,wait_until='networkidle');assert response.status==200
  assert page.locator('main').count()==1 and page.locator('h1').count()==1
  assert page.locator('nav[aria-label="Hauptnavigation"] a').count()==4
  assert page.locator('footer nav a').count()==3
  assert page.locator('.vpm-footer-note').inner_text().startswith('Einige Verlinkungen')
  assert page.locator('html').get_attribute('lang').startswith('de')
  assert 'noindex' in page.locator('meta[name=robots]').get_attribute('content')
  assert not errors,errors
  assert page.locator('.vpm-wordmark-name').first.inner_text()=='VALET PARKEN'
  assert page.locator('.vpm-section-inner').evaluate_all('(sections)=>sections.every(s=>{const h=s.querySelector(":scope > h2"),p=s.querySelector(":scope > p");return !h||!p||Math.abs(h.getBoundingClientRect().left-p.getBoundingClientRect().left)<1})')
  if slug=='startseite':
   sources=page.locator('.vpm-provider-image img').evaluate_all('(imgs)=>imgs.map(i=>i.src)')
   assert len(set(sources))==6,'Provider illustrations must be distinct'
  else:assert page.locator('.vpm-editorial-image').count()>=2,'Missing subpage imagery'
  assert all(r.url.startswith((base,'blob:'+base)) for r in responses),[r.url for r in responses if not r.url.startswith(base)]
  assert all(r.status<400 for r in responses),[(r.url,r.status) for r in responses if r.status>=400]
  links=page.locator('a[href]').evaluate_all('(els)=>els.map(e=>({url:e.href,label:e.textContent.trim(),rel:e.rel}))')
  assert all(l['label'] for l in links)
  for l in links:
   if any(host in l['url'] for host in ['parkos.de','parkvia.com']):assert 'sponsored' in l['rel'] and 'nofollow' in l['rel']
   if l['url'].startswith(base):
    address,fragment=urldefrag(l['url'])
    assert page.request.get(address).status==200,address
    if fragment:
     assert page.request.get(address).text().find('id="'+fragment+'"')>=0,fragment
  for image in page.locator('main img').all():
   image.scroll_into_view_if_needed()
   image.evaluate('(img)=>new Promise((resolve,reject)=>{if(img.complete){img.naturalWidth?resolve():reject(new Error(img.src));return;}img.addEventListener("load",resolve,{once:true});img.addEventListener("error",()=>reject(new Error(img.src)),{once:true});})')
  page.evaluate('scrollTo(0,0)')
  imgs=page.locator('main img').evaluate_all('(els)=>els.map(e=>({src:e.currentSrc,loaded:e.complete&&e.naturalWidth>0,alt:e.alt,width:e.getAttribute("width"),height:e.getAttribute("height"),srcset:e.srcset||e.closest("picture")?.querySelector("source")?.srcset,loading:e.loading,priority:e.fetchPriority}))')
  for img in imgs:assert img['loaded'] and img['alt'] and img['width'] and img['height'] and img['srcset'],img
  for width in [1440,768,390,320]:
   page.set_viewport_size({'width':width,'height':1000});assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(slug,width)
   if width<900:
    for cell in page.locator('main table td').all():
     assert cell.get_attribute('data-label'),(slug,'missing table label')
     assert cell.evaluate('(e)=>getComputedStyle(e).display')=='grid' or cell.evaluate('(e)=>e.cellIndex')==0
   for image in page.locator('main img').all():
    if image.is_visible():
     image.scroll_into_view_if_needed();image.evaluate('(i)=>i.decode()')
   page.evaluate('scrollTo(0,0)')
   if width in [1440,390]:page.screenshot(path=str(root/'dist'/f'{slug}-{width}.png'),full_page=True)
  page.keyboard.press('Tab');assert page.evaluate('document.activeElement.classList.contains("vpm-skip")')
  page.keyboard.press('Enter');assert page.evaluate('document.activeElement.id==="main"')
  page.locator('.vpm-menu-toggle').focus();page.keyboard.press('Enter');assert page.locator('.vpm-menu-toggle').get_attribute('aria-expanded')=='true'
  page.keyboard.press('Escape');assert page.locator('.vpm-menu-toggle').get_attribute('aria-expanded')=='false'
  faq=page.locator('main details')
  if faq.count():
   faq.first.locator('summary').focus();page.keyboard.press('Enter');assert faq.first.get_attribute('open') is not None;page.keyboard.press('Enter')
  stats=page.evaluate('({durationMs:Math.round(performance.getEntriesByType("navigation")[0].duration),transferBytes:performance.getEntriesByType("resource").reduce((s,r)=>s+r.transferSize,0)+performance.getEntriesByType("navigation")[0].transferSize})')
  report['pages'].append(dict(page=slug,resources=len(responses),images=imgs,faq_count=faq.count(),**stats))
  page.remove_listener('response',collect);page.remove_listener('pageerror',onerror)
  print(slug,': Links, Bildattribute, Tastatur, vier Breiten und lokale Ressourcen OK',flush=True)
 browser.close()
(root/'dist/handover-audit.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
print(report['contrast'])
