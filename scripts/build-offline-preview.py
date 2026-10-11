"""Export four read-only rendered pages with local assets for double-click preview."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import urlopen
from urllib.parse import urlparse,urldefrag
import html,hashlib,re,shutil
from zipfile import ZipFile,ZIP_DEFLATED
root=Path(__file__).resolve().parent.parent
out=root/'dist/vpm-vorschau';out.mkdir(parents=True,exist_ok=True);(out/'assets').mkdir(exist_ok=True)
base='http://127.0.0.1:8088'
paths={'/':'index.html','/preise/':'preise.html','/valet-parken-sicherheit/':'sicherheit.html','/ueber-uns/':'ueber-uns.html'}
cache={}
def asset(url):
 if url.startswith('data:'):return url
 if not url.startswith(base):raise RuntimeError('Unexpected external preview asset: '+url)
 if url in cache:return cache[url]
 name=Path(urlparse(url).path).name
 name=re.sub(r'[^a-zA-Z0-9._-]','-',name)
 filename='assets/'+hashlib.sha256(url.encode()).hexdigest()[:10]+'-'+name
 with urlopen(url) as response:content=response.read()
 (out/filename).write_bytes(content);cache[url]=filename;return filename
def rewrite_link(url):
 if url.startswith(base):url=url[len(base):] or '/'
 if url.startswith('/'):
  path,fragment=urldefrag(url);path=urlparse(path).path
  if path in paths:return paths[path]+('#'+fragment if fragment else '')
 return url
class Export(HTMLParser):
 def __init__(self):super().__init__(convert_charrefs=False);self.parts=[];self.script=False
 def handle_decl(self,d):self.parts.append('<!'+d+'>')
 def handle_starttag(self,tag,attrs):
  if tag=='script':self.script=True;return
  if self.script:return
  attrs=dict(attrs)
  if tag=='link':
   if attrs.get('rel')!='stylesheet':return
   attrs['href']=asset(attrs['href'])
  elif tag=='img':
   if 'src' in attrs:attrs['src']=asset(attrs['src'])
   if 'srcset' in attrs:
    attrs['srcset']=', '.join(asset(item.strip().split()[0])+' '+' '.join(item.strip().split()[1:]) for item in attrs['srcset'].split(','))
  elif tag=='a' and 'href' in attrs:attrs['href']=rewrite_link(attrs['href'])
  self.parts.append('<'+tag+''.join(' '+key+('="'+html.escape(value,quote=True)+'"' if value is not None else '') for key,value in attrs.items())+'>')
  if tag=='head':self.parts.append('<script>document.documentElement.classList.add("vpm-js");</script>')
 def handle_startendtag(self,tag,attrs):self.handle_starttag(tag,attrs)
 def handle_endtag(self,tag):
  if tag=='script':self.script=False;return
  if self.script:return
  if tag=='body':self.parts.append('<script src="assets/navigation.js" defer></script>')
  self.parts.append('</'+tag+'>')
 def handle_data(self,data):
  if not self.script:self.parts.append(data)
 def handle_entityref(self,name):
  if not self.script:self.parts.append('&'+name+';')
 def handle_charref(self,name):
  if not self.script:self.parts.append('&#'+name+';')
for path,name in paths.items():
 with urlopen(base+path) as response:source=response.read().decode()
 parser=Export();parser.feed(source);(out/name).write_text(''.join(parser.parts))
shutil.copyfile(root/'wordpress/themes/vpm-hybrid/assets/navigation.js',out/'assets/navigation.js')
(out/'SO-OEFFNEN.txt').write_text('VPM – klickbare Designvorschau\n\n1. Das ZIP vollständig entpacken.\n2. index.html mit Chrome, Firefox, Edge oder Safari öffnen (Doppelklick).\n3. Über das Menü Preise, Sicherheit und Über uns öffnen. FAQs lassen sich aufklappen.\n\nBilder und Styles funktionieren offline. Partnerlinks führen bei Klick online zum Buchungspartner. Diese Vorschau enthält keinen WordPress-Editor und keine final konfigurierte Cookie-Verwaltung. Die WordPress-Pakete sind separat im Hostinger-Übergabepaket enthalten.\n')
archive=root/'dist/vpm-klickbare-vorschau.zip'
with ZipFile(archive,'w',ZIP_DEFLATED) as zip:
 for file in sorted(out.rglob('*')):
  if file.is_file():zip.write(file,file.relative_to(out))
print(archive.name,archive.stat().st_size,'Bytes;',len(cache),'lokale Dateien')
