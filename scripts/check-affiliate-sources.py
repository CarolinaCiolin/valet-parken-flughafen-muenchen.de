"""Read only the user-approved public research URLs; no site changes or bookings."""
from pathlib import Path
from urllib.request import Request,urlopen
from urllib.error import HTTPError
import json,datetime,concurrent.futures
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1];data=json.loads((ROOT/'wordpress/plugins/vpm-content-drafts/updates/affiliate-2026-11.json').read_text())
urls=['https://valet-parken-flughafen-muenchen.de/preise/','https://parkenamflughafen.de/parken-flughafen-muenchen/valet-parken','https://www.airparks.de/muenchen-flughafen-parken/airparks-parkplatz-schwaig-valet-parken.html','https://www.parken-und-fliegen.de/flughafen-parken/muenchen/parkprofi-parkhaus-valet-muenchen.html','https://www.parken-und-fliegen.de/flughafen-parken/muenchen/valet24muc-parkplatz-valet.html',data['affiliate_url']]
def fetch(url):
 try:
  with urlopen(Request(url,headers={'User-Agent':'Mozilla/5.0','Accept-Language':'de-DE,de;q=0.9'}),timeout=35)as r:html=r.read().decode('utf8','replace');status=r.status;final=r.url
  soup=BeautifulSoup(html,'html.parser');result={'url':url,'status':status,'final_url':final}
  if url==urls[0]:
   result['visible_price_rows']=[[cell.get_text(' ',strip=True)for cell in row.select('td')[:3]]for row in soup.select('tbody tr')if row.select('td')]
  else:
   text=soup.get_text(' ',strip=True).lower();result['found_terms']=[s for s in ['airparks','parkprofi','valet24muc','eingezäunt','beleuchtet','videoüberwachung','kontrollgänge','schlüssel','kilometerstand','tankfüllung','vorschäden']if s in text]
  return result
 except HTTPError as e:return {'url':url,'status':e.code,'error':'Remote HTTP error; content not verified'}
 except Exception as e:return {'url':url,'status':None,'error':type(e).__name__}
rows=list(concurrent.futures.ThreadPoolExecutor(max_workers=6).map(fetch,urls));path=ROOT/'docs/AFFILIATE-QUELLEN-PRUEFUNG.json';path.write_text(json.dumps({'checked_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'scope':'public read-only URL verification; no bookings or site changes','results':rows},ensure_ascii=False,indent=2)+'\n');print([(x['url'].split('?')[0],x['status'])for x in rows])
