"""Inspect actual public resources and CSS colors; no claim of a full WCAG audit."""
import json,re
from pathlib import Path
from urllib.parse import unquote
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent
base='http://127.0.0.1:8088'
def rgb(s):
 if s.startswith('#'):return [int(s[i:i+2],16)/255 for i in [1,3,5]]
 return [float(v)/255 for v in re.findall(r'[\d.]+',s)[:3]]
def lum(c):return sum((x/12.92 if x<=.04045 else ((x+.055)/1.055)**2.4)*w for x,w in zip(c,[.2126,.7152,.0722]))
def ratio(a,b):x,y=sorted([lum(rgb(a)),lum(rgb(b))]);return (y+.05)/(x+.05)
report={'scope':'Vier öffentliche lokale Seiten. Erfasste CSS-Farben inklusive Gradient-Endpunkte und SVG-Striche; dekorative Linien ausgenommen. Kein vollständiges Barrierefreiheitszertifikat, kein Hostinger-Waterfall.','pages':[],'icons':[]}
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 for path in ['/','/preise/','/valet-parken-sicherheit/','/ueber-uns/']:
  page=browser.new_page(viewport={'width':1440,'height':1000});page.goto(base+path,wait_until='networkidle')
  resources=page.evaluate("performance.getEntriesByType('resource').map(r=>({url:r.name,type:r.initiatorType,blocking:r.renderBlockingStatus,bytes:r.transferSize,duration:r.duration}))")
  scripts=page.locator('script[src]').evaluate_all('es=>es.map(e=>({url:e.src,defer:e.defer,async:e.async}))')
  assert all(s['defer'] or s['async'] for s in scripts),scripts
  assert all(r['url'].startswith(base) for r in resources),resources
  texts=page.evaluate('''()=>[...document.querySelectorAll('header *,main *,footer *')].filter(e=>e.getClientRects().length&&[...e.childNodes].some(n=>n.nodeType===3&&n.textContent.trim())).map(e=>{const s=getComputedStyle(e);let backgrounds=[];let p=e;while(p){const b=getComputedStyle(p);if(b.backgroundImage.startsWith('linear-gradient')&&b.backgroundImage.match(/rgba?\\([^)]*\\)/g)){backgrounds=b.backgroundImage.match(/rgba?\\([^)]*\\)/g);break;}if(b.backgroundColor!=='rgba(0, 0, 0, 0)'){backgrounds=[b.backgroundColor];break;}p=p.parentElement;}return{label:e.textContent.trim().slice(0,90),color:s.color,backgrounds,size:parseFloat(s.fontSize),weight:parseInt(s.fontWeight)||400}})''')
  failures=[];minimum=100
  for t in texts:
   threshold=3 if t['size']>=24 or (t['size']>=18.66 and t['weight']>=700) else 4.5
   values=[ratio(t['color'],bg) for bg in t['backgrounds']];minimum=min(minimum,*values)
   if any(v<threshold for v in values):failures.append(dict(t,ratios=values,required=threshold))
  assert not failures,failures
  report['pages'].append({'path':path,'text_elements':len(texts),'minimum_text_contrast':round(minimum,2),'blocking_resources':[r for r in resources if r['blocking']=='blocking'],'external_scripts':scripts})
  if path=='/':
   icon_styles=page.locator('.vpm-benefits li,.vpm-provider-features li,.vpm-header-cta,.vpm-action a').evaluate_all("es=>es.map(e=>({label:e.textContent.trim(),svg:getComputedStyle(e,'::before').backgroundImage,after:getComputedStyle(e,'::after').backgroundImage}))")
   for item in icon_styles:
    svg=unquote(item['svg']+' '+item['after']);colors=set(re.findall(r"stroke=['\"](#[a-fA-F0-9]{6})",svg))
    for color in colors:
     backgrounds=['#b99a5b','#ad8949'] if color=='#101c2c' else ['#ffffff','#f6f3ec']
     minimum=min(ratio(color,bg) for bg in backgrounds);assert minimum>=3,(item,color,minimum)
     report['icons'].append({'label':item['label'],'stroke':color,'minimum_contrast':round(minimum,2)})
   assert page.locator('.vpm-hero-image figcaption').evaluate("e=>getComputedStyle(e).backgroundColor")=='rgb(252, 250, 247)'
   assert ratio('#777c82','#f6f3ec')>=3
   assert ratio('#aa8747','#f6f3ec')>=3
  print(path,len(texts),'Texte geprüft; kein blockierendes externes JS; minimale Schriftkontraste',round(minimum,2),flush=True)
  page.close()
 browser.close()
(root/'dist/render-contrast-audit.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
