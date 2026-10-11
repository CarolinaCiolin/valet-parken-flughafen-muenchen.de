"""Export the prepared site links for manual affiliate maintenance."""
import csv,json
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse,parse_qs
root=Path(__file__).resolve().parent.parent
data=json.loads((root/'wordpress/plugins/vpm-content-drafts/content.json').read_text())
rows=[]
class Links(HTMLParser):
 def __init__(self,page):super().__init__();self.page=page;self.current=None
 def handle_starttag(self,tag,attrs):
  if tag=='a':self.current={'page':self.page,'url':dict(attrs).get('href',''),'label':''}
 def handle_data(self,text):
  if self.current is not None:self.current['label']+=text
 def handle_endtag(self,tag):
  if tag=='a' and self.current is not None:rows.append(self.current);self.current=None
for page in data['pages']:Links(page['slug']).feed(page['content'])
for provider in data['providers']:
 for platform in ['parkos','parkvia']:
  url=provider['meta'].get(platform+'_url')
  if url:rows.append(dict(page='Anbieterkarte: '+provider['name'],url=url,label=provider['meta'][platform+'_label']))
rows.append(dict(page='Header-CTA',url=data['general_url'],label='Verfügbarkeit prüfen'))
with (root/'docs/LINK-INVENTAR.csv').open('w',encoding='utf-8-sig',newline='') as f:
 writer=csv.writer(f,delimiter=';');writer.writerow(['Verwendung','Linktext','Vollständiger Link','Tracking tt','Ziel r','Typ','Prüfung'])
 for row in rows:
  query=parse_qs(urlparse(row['url']).query)
  affiliate=any(host in urlparse(row['url']).netloc for host in ['parkos.de','parkvia.com'])
  writer.writerow([row['page'],row['label'],row['url'],query.get('tt',[''])[0],query.get('r',[''])[0],'Affiliate' if affiliate else 'Intern' if row['url'].startswith('/') else 'Extern','Intern lokal geprüft' if row['url'].startswith('/') else 'Aus Vorlage übernommen; externe Erreichbarkeit nicht geprüft'])
print(len(rows),'Links exportiert; vollständige URLs unverändert.')
