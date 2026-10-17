"""Check real local WordPress links against German counterparts; preserve draft statuses."""
from pathlib import Path
from urllib.request import urlopen
from urllib.parse import urljoin,urlsplit,unquote
import subprocess,json
from bs4 import BeautifulSoup
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
paths={'/':'/en/','/preise/':'/en/prices/','/valet-parken-sicherheit/':'/en/valet-parking-safety/','/ueber-uns/':'/en/about-us/'}
def php(s):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+s],text=True)
statuses=json.loads(php('$out=[];foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","numberposts"=>-1,"lang"=>"en"])as $p)$out[$p->ID]=$p->post_status;echo json_encode($out);'))
cache={};checks=[]
def doc(path):
 if path not in cache:
  with urlopen(base+path)as r:
   assert r.status==200,(path,r.status)
   assert urlsplit(r.url).path==path,('Unexpected redirect',path,r.url)
   cache[path]=BeautifulSoup(r.read(),'html.parser')
 return cache[path]
def local(a,page):
 u=urlsplit(urljoin(base+page,a['href']))
 return u if u.netloc==urlsplit(base).netloc else None
try:
 php('foreach('+json.dumps([int(x)for x in statuses])+'as $id){if(get_post_meta($id,"_vpm_layout_slug",true)!=="ratgeber")wp_update_post(["ID"=>$id,"post_status"=>"publish"]);}flush_rewrite_rules(false);')
 for de,en in paths.items():
  d,e=doc(de),doc(en)
  source=[];translated=[]
  for page,s,out in [(de,d,source),(en,e,translated)]:
   for a in s.select('main a[href]'):
    u=local(a,page)
    if u:out.append((u.path,u.fragment))
  expected=[(paths.get(p,p),f)for p,f in source]
  assert translated==expected,('Different target from German counterpart',en,expected,translated)
  for a in e.select('a[href]'):
   u=local(a,en)
   if not u:continue
   switch=a.find_parent('nav',class_='vpm-language-switcher') is not None
   target=doc(u.path)
   if u.fragment:assert target.find(id=unquote(u.fragment)),('Missing anchor',en,u.path,u.fragment)
   if not switch:assert u.path.startswith('/en/'),('Unexpected German destination',en,a.get_text(),u.path)
   if switch and a.get_text(strip=True)=='DE':assert u.path==de,('Wrong DE counterpart',en,u.path,de)
   if switch and a.get_text(strip=True)=='EN':assert u.path==en
   checks.append({'source':en,'label':a.get_text(' ',strip=True),'target':u.path+('#'+u.fragment if u.fragment else ''),'language_switch':switch})
  print(en,'all internal targets and anchors valid; content links match German counterparts')
 # Offline-export file links and fragments.
 for f in (root/'preview').glob('en-*.html'):
  for a in BeautifulSoup(f.read_text(),'html.parser').select('a[href]'):
   u=urlsplit(a['href'])
   if u.scheme or u.netloc:continue
   dest=(f.parent/u.path)if u.path else f
   assert dest.is_file(),('Missing preview file',f.name,a['href'])
   if u.fragment:assert BeautifulSoup(dest.read_text(),'html.parser').find(id=unquote(u.fragment)),('Missing preview anchor',f.name,a['href'])
 report={'result':'passed','live_local_links_checked':len(checks),'content_targets':'English internal content targets match mapped German counterparts in order','anchors':'All local fragments resolve to existing element IDs','language':'English targets except intentional DE switch to matching page','offline_export':'All English preview local files and anchors exist','links':checks,'scope':'Local WordPress review fixture and static preview; no Hostinger access. Original draft statuses restored in finally.'}
 (root/'docs/ENGLISCHE-INTERNE-LINKS-PRUEFUNG.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
 print(len(checks),'English local links checked; static preview targets also passed.')
finally:
 php('foreach(json_decode('+json.dumps(json.dumps(statuses))+',true)as $id=>$status)wp_update_post(["ID"=>$id,"post_status"=>$status]);')
