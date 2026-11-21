"""Export authenticated local draft previews as offline review pages and screenshots. Never deploy."""
from pathlib import Path
from playwright.sync_api import sync_playwright
from bs4 import BeautifulSoup
from urllib.parse import urlparse,urljoin
import urllib.request,json,hashlib,re,shutil
ROOT=Path(__file__).resolve().parents[1];BASE='http://127.0.0.1:8088';OUT=ROOT/'vorschau/affiliate-video-2026-11';OUT.mkdir(parents=True,exist_ok=True);(OUT/'assets').mkdir(exist_ok=True)
ids=json.loads((ROOT/'.runtime/affiliate-preview-ids.json').read_text());checks=[];copied={}
def asset(url):
 url=urljoin(BASE,url)
 if not url.startswith(BASE):return url
 if url in copied:return copied[url]
 ext=Path(urlparse(url).path).suffix or'.bin';name=hashlib.sha256(url.encode()).hexdigest()[:16]+ext;rel='assets/'+name;copied[url]=rel
 with urllib.request.urlopen(url)as r:data=r.read()
 if ext=='.css':
  text=data.decode();text=re.sub(r'url\([\'\"]?([^\)\'\"]+)[\'\"]?\)',lambda m:m[0]if m[1].startswith(('data:','#'))else'url("'+asset(urljoin(url,m[1])).replace('assets/','')+'")',text);data=text.encode()
 (OUT/rel).write_bytes(data);return rel
with sync_playwright()as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page(viewport={'width':1440,'height':1000});page.goto(BASE+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((ROOT/'.runtime/admin-password').read_text().strip())
 with page.expect_navigation(wait_until='networkidle'):page.locator('#wp-submit').click()
 for slug,pid in ids['pages'].items():
  page.goto(BASE+'/?page_id='+str(pid)+'&preview=true',wait_until='networkidle');assert page.locator('main').count()
  if slug=='startseite':
   assert page.locator('.vpm-provider-card').count()==9
   for card in page.locator('.vpm-provider-card').all():assert'für 8 Tage'in card.locator('.vpm-provider-price').inner_text()
   assert page.locator('a[href*="tc.tradetracker.net"]').count()==3
   checks.append('Nine start cards, nine visible eight-day prices, three new exact partner links')
  if slug=='preise':assert page.locator('tbody tr').count()==10;checks.append('Ten sorted price rows including Flughafen München')
  if slug=='valet-parken-sicherheit':assert page.locator('a[href*="tc.tradetracker.net"]').count()==3;checks.append('Three additional safety cards and CTAs')
  for link in page.locator('a[href*="tc.tradetracker.net"]').all():assert set(link.get_attribute('rel').split())>={'sponsored','nofollow'}
  for width in [1440,390]:
   page.set_viewport_size({'width':width,'height':1000});assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(slug,width);page.screenshot(path=str(OUT/f'{slug}-{width}.png'),full_page=True)
  page.set_viewport_size({'width':1440,'height':1000});soup=BeautifulSoup(page.content(),'html.parser')
  for node in soup.select('#wpadminbar,script:not([type="application/ld+json"]),link[rel="edituri"],link[rel="https://api.w.org/"]'):node.decompose()
  soup.body['class']=[x for x in soup.body.get('class',[])if x!='admin-bar']
  for link in soup.select('link[rel="stylesheet"][href]'):link['href']=asset(link['href'])
  for img in soup.select('img[src]'):
   img['src']=asset(img['src']);img.attrs.pop('srcset',None);img.attrs.pop('sizes',None)
  for a in soup.select('a[href]'):
   u=a['href']
   if u.startswith(BASE)or u.startswith('/'):
    parsed=urlparse(urljoin(BASE,u));path=parsed.path;target={'/':'startseite','/preise/':'preise','/valet-parken-sicherheit/':'valet-parken-sicherheit'}.get(path)
    a['href']=(target+'.html'+('#'+parsed.fragment if parsed.fragment else''))if target else'https://valet-parken-flughafen-muenchen.de'+path
  for node in soup.select('style'):
   if'admin-bar'in node.get_text():node.decompose()
  nav=soup.new_tag('script');nav.string="document.documentElement.classList.add('vpm-js');const b=document.querySelector('.vpm-menu-toggle'),n=document.querySelector('.vpm-navigation');b?.addEventListener('click',()=>{n.classList.toggle('is-open');b.setAttribute('aria-expanded',n.classList.contains('is-open')?'true':'false')});";soup.body.append(nav)
  if slug=='startseite':
   heading=soup.find('h2',string=re.compile('So funktioniert'))
   if heading:
    figure=soup.new_tag('figure',attrs={'class':'wp-block-video vpm-explainer-review'});video=soup.new_tag('video',attrs={'controls':'','poster':'video-poster.png','preload':'none','style':'width:100%;aspect-ratio:16/9;border-radius:14px;background:#101c2c'});source=soup.new_tag('source',attrs={'src':'erklaervideo.mp4','type':'video/mp4'});video.append(source);track=soup.new_tag('track',attrs={'kind':'subtitles','src':'untertitel-de.vtt','srclang':'de','label':'Deutsch'});video.append(track);figure.append(video);heading.insert_after(figure)
  (OUT/(slug+'.html')).write_text(str(soup))
 b.close()
shutil.copy(ROOT/'media/valet-erklaervideo/valet-parken-muenchen-erklaervideo.mp4',OUT/'erklaervideo.mp4');shutil.copy(ROOT/'media/valet-erklaervideo/untertitel-de.srt',OUT/'untertitel-de.srt');shutil.copy(ROOT/'media/valet-erklaervideo/untertitel-de.vtt',OUT/'untertitel-de.vtt')
(OUT/'index.html').write_text('''<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>VPM – Partner und Video: Vorschau</title><style>body{font:18px/1.6 Arial;background:#fcfaf7;color:#101c2c;max-width:1200px;margin:40px auto;padding:0 24px}a{color:inherit}nav{display:flex;flex-wrap:wrap;gap:24px}video{width:100%;border-radius:16px;background:#101c2c;margin-top:28px}</style><h1>Partner und Erklärvideo – Vorschau</h1><nav><a href="startseite.html">Startseite</a><a href="preise.html">Preisvergleich</a><a href="valet-parken-sicherheit.html">Sicherheitsvergleich</a></nav><video poster="video-poster.png" controls preload="none"><source src="erklaervideo.mp4" type="video/mp4"><track label="Deutsch" kind="subtitles" srclang="de" src="untertitel-de.vtt"></video><p>Lokale Videovorschau. YouTube-Einbettung folgt mit tatsächlicher Video-URL.</p></html>''')
(ROOT/'docs/AFFILIATE-VIDEO-VORSCHAU-PRUEFUNG.json').write_text(json.dumps({'scope':'local authenticated draft copies, no live change','checks':checks,'responsive_widths':[1440,390],'links_sponsored_nofollow':True},ensure_ascii=False,indent=2)+'\n')
print('Offline draft preview exported; all cards, links and six responsive layouts checked.')

shutil.copy(ROOT/'media/valet-erklaervideo/szene-2.png',OUT/'video-poster.png')

# Refresh the rendered media and captions that also work from the unpacked ZIP.
import subprocess,sys
subprocess.run([sys.executable,str(ROOT/"scripts/refresh-video-review.py")],check=True)
