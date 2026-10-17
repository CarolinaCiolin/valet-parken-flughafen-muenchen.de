"""Real local JSON-LD/context/icon tests, temporary multilingual fixtures, full cleanup."""
from pathlib import Path
from playwright.sync_api import sync_playwright
from bs4 import BeautifulSoup
import subprocess,json,urllib.request,urllib.parse
root=Path(__file__).resolve().parents[1];base='http://127.0.0.1:8088'
def php(code):return subprocess.check_output(['docker','exec','vpm-local-wp','php','-r','define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);wp_set_current_user(1);'+code],text=True)
state=json.loads(php('$ids=[];foreach(get_posts(["post_type"=>["page","vpm_provider"],"post_status"=>"any","numberposts"=>-1,"lang"=>"en"])as $p)$ids[$p->ID]=$p->post_status;$de=vpm_original_page("ratgeber");$ids[$de]=get_post_status($de);echo json_encode(["statuses"=>$ids,"plugins"=>get_option("active_plugins"),"icon"=>get_option("site_icon",0)]);'))
created=[];report=[]
def graph(path):
 with urllib.request.urlopen(base+path)as r:assert r.status==200;s=BeautifulSoup(r.read(),'html.parser')
 scripts=s.select('script[type="application/ld+json"]');assert len(scripts)==1,(path,len(scripts))
 data=json.loads(scripts[0].string or scripts[0].get_text());nodes=data['@graph'];ids=[n['@id']for n in nodes];assert len(ids)==len(set(ids)),('Duplicate entities',path)
 return s,nodes
try:
 created=json.loads(php('foreach('+json.dumps([int(x)for x in state['statuses']])+ 'as $id)wp_update_post(["ID"=>$id,"post_status"=>"publish"]);$out=[];foreach(["de","en"]as $lang){$id=wp_insert_post(["post_type"=>"post","post_status"=>"publish","post_title"=>"Schema article ".$lang,"post_name"=>"vpm-schema-article-".$lang,"post_content"=>"<!-- wp:paragraph --><p>Temporary local schema article.</p><!-- /wp:paragraph -->"]);pll_set_post_language($id,$lang);$out[]=["id"=>$id,"url"=>get_permalink($id),"kind"=>"post"];$legal=wp_insert_post(["post_type"=>"page","post_status"=>"publish","post_title"=>"Schema legal fixture ".$lang,"post_name"=>"vpm-legal-schema-".$lang,"post_content"=>"<p>Temporary local legal fixture.</p>"]);pll_set_post_language($legal,$lang);update_post_meta($legal,"_vpm_legal_draft","1");$out[]=["id"=>$legal,"url"=>get_permalink($legal),"kind"=>"legal"];}flush_rewrite_rules(false);echo json_encode($out);'))
 paths=['/','/preise/','/valet-parken-sicherheit/','/ueber-uns/','/ratgeber/','/en/','/en/prices/','/en/valet-parking-safety/','/en/about-us/','/en/valet-parking-guide/']
 for path in paths:
  s,nodes=graph(path);en=path.startswith('/en/');locale='en-GB'if en else'de-DE';types=[n['@type']for n in nodes]
  assert types.count('WebSite')==1 and types.count('Organization')==1
  assert not set(types)&{'Product','Offer','AggregateRating','Review','LocalBusiness','ParkingFacility','Person'}
  page=next(n for n in nodes if n['@id']==base+path+'#webpage');assert page['inLanguage']==locale
  home=path in ['/','/en/']
  crumbs=s.select('.vpm-page-context nav ol li')
  if home:
   assert not crumbs and 'BreadcrumbList'not in types
   listing=next(n for n in nodes if n['@type']=='ItemList');assert int(listing['numberOfItems'])==6
   for item in listing['itemListElement']:assert s.find(id=urllib.parse.urlsplit(item['url']).fragment),item
  else:
   assert len(crumbs)==2
   bc=next(n for n in nodes if n['@type']=='BreadcrumbList');assert len(bc['itemListElement'])==2
   assert bc['itemListElement'][0]['item']==base+('/en/'if en else'/')
   assert s.select_one('.vpm-page-role a')['href']==base+('/en/'if en else'/')
   assert [x.get_text(' ',strip=True)for x in crumbs]==[x['name']for x in bc['itemListElement']]
  if home:
   for entry in listing['itemListElement']:
    service=entry['item'];assert service['@type']=='Service' and 'offers'not in service
    card=s.find(id=urllib.parse.urlsplit(entry['url']).fragment)
    assert service['name']==card.select_one('h3').get_text(strip=True)
    assert service['description']=='\n'.join(x.get_text(strip=True)for x in card.select('.vpm-provider-features li'))
  if path in ['/preise/','/en/prices/']:
   listing=next(n for n in nodes if n['@type']=='ItemList');assert int(listing['numberOfItems'])==7
   rows=s.select('table tbody tr');assert len(rows)==7
   for entry,row,expected in zip(listing['itemListElement'],rows,['89.99','105','145','147.99','160.99','190','335']):
    service=entry['item'];cells=row.select('td');offer=service['offers'];spec=offer['priceSpecification']
    assert service['@type']=='Service' and service['name']==cells[0].get_text(strip=True)
    assert service['provider']['name']==service['name']
    assert service['description']==cells[2].get_text(strip=True)
    assert offer['url']==base+path and cells[3].select_one('a')['href'].startswith('https://')
    assert str(spec['minPrice'])==expected and spec['priceCurrency']=='EUR' and 'price'not in offer
    assert int(spec['referenceQuantity']['value'])==8 and spec['referenceQuantity']['unitCode']=='DAY'
    assert not set(offer)&{'availability','priceValidUntil','seller'}
  if 'about-us'in path or 'ueber-uns'in path:assert page['@type']=='AboutPage'
  faqs=s.select('details summary');faqnodes=[n for n in nodes if n['@type']=='FAQPage']
  assert len(faqnodes)==(1 if faqs else 0)
  if faqs:assert [x['name']for x in faqnodes[0]['mainEntity']]==[x.get_text(strip=True)for x in faqs]
  if 'ratgeber'in path or 'valet-parking-guide'in path:
   listing=next(n for n in nodes if n['@type']=='ItemList');assert int(listing['numberOfItems'])==1
   assert listing['itemListElement'][0]['name']=='Schema article '+('en'if en else'de')
  report.append({'path':path,'types':types,'breadcrumbs':len(crumbs),'result':'passed'})
 for item in created:
  path=urllib.parse.urlsplit(item['url']).path
  if item['kind']=='legal':
   s=BeautifulSoup(urllib.request.urlopen(item['url']).read(),'html.parser');assert not s.select('script[type="application/ld+json"]');assert not s.select('.vpm-page-context');continue
  s,nodes=graph(path);assert any(n['@type']=='BlogPosting'for n in nodes)
  bc=next(n for n in nodes if n['@type']=='BreadcrumbList');assert len(bc['itemListElement'])==3
  assert '/ratgeber/'in bc['itemListElement'][1]['item'] or '/en/valet-parking-guide/'in bc['itemListElement'][1]['item']
 # Fallback without Rank Math, then restore exact original active list.
 php('require_once ABSPATH."wp-admin/includes/plugin.php";deactivate_plugins("seo-by-rank-math/rank-math.php",true);')
 _,fallback=graph('/preise/');assert any(n['@type']=='BreadcrumbList'for n in fallback)
 php('update_option("active_plugins",json_decode('+json.dumps(json.dumps(state['plugins']))+',true));')
 # Test native icon setup, generated sizes and preserving a chosen icon.
 print(php('$original=get_option("site_icon",0);delete_option("site_icon");$r=vpm_prepare_site_icon();if(is_wp_error($r))throw new Exception($r->get_error_message());$id=(int)get_option("site_icon");if(!$id)throw new Exception("Icon missing");$meta=wp_get_attachment_metadata($id);foreach([32,180,192,270]as $size)if(empty($meta["sizes"]["vpm-site-icon-".$size]))throw new Exception("Missing native icon size ".$size);vpm_prepare_site_icon();if((int)get_option("site_icon")!==$id)throw new Exception("Existing icon overwritten");echo "Native icon setup and sizes passed; existing icon retained.\n";').strip())
 with sync_playwright()as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page()
  for path in paths:
   for width in [1440,768,390]:
    page.set_viewport_size({'width':width,'height':1000});r=page.goto(base+path,wait_until='networkidle');assert r.status==200
    assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(path,width)
    icons=page.locator('link[rel="icon"]').evaluate_all('(els)=>els.map(x=>x.href)');assert len(icons)==2,icons
    if path in ['/preise/','/en/prices/']and width in[1440,390]:page.screenshot(path=str(root/'zwischenstand/schema'/(('en'if path.startswith('/en/')else'de')+'-'+str(width)+'.png')))
  b.close()
 (root/'docs/SCHEMA-KONTEXT-PRUEFUNG.json').write_text(json.dumps({'pages':report,'checks':['Exactly one graph with unique entity IDs and no duplicate Rank Math entities','Breadcrumbs match visible navigation in both languages','Provider ItemList targets exist in page; Service names/features match visible cards, no hidden homepage prices', 'Seven price-page Services in both languages: visible amounts, minimum EUR prices for eight days, local comparison URL and unchanged visible booking links, no invented availability or dates','FAQ questions match actual rendered details','Published guide ItemList matches native query language','BlogPosting and Home > Guide > Article breadcrumbs','No schema or context UI on synthetic legal pages','Fallback graph works without Rank Math','Native icon import sizes 32/180/192/270, repeat preserves existing icon','30 viewport checks without horizontal overflow'],'limitations':['Local WordPress integration, not Hostinger deployment','No Google Rich Results Test or Schema Markup Validator service access','FAQ rich-result eligibility not guaranteed']},ensure_ascii=False,indent=2)+'\n')
 print('Schema, visible context, fallback, legal exclusions, multilingual guide/articles, icon and 30 layouts passed.')
finally:
 php('update_option("active_plugins",json_decode('+json.dumps(json.dumps(state['plugins']))+',true));update_option("site_icon",'+str(int(state['icon']or 0))+');foreach(json_decode('+json.dumps(json.dumps(state['statuses']))+',true)as $id=>$status)wp_update_post(["ID"=>$id,"post_status"=>$status]);foreach('+json.dumps([x['id']for x in created])+'as $id)wp_delete_post($id,true);')
