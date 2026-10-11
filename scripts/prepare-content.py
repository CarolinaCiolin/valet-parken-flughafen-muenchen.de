"""Transfer supplied PDF blocks and hyperlinks into editable Gutenberg drafts."""
import fitz,re,json,html
from pathlib import Path
root=Path(__file__).resolve().parent.parent
fixture=json.loads((root/'scripts/fixture.json').read_text())
out=root/'wordpress/plugins/vpm-content-drafts';out.mkdir(exist_ok=True)
changes=[]
def norm(t):return re.sub(r'\s+',' ',t.replace('\u200b','')).strip()
def esc(t):return html.escape(t,quote=True)
def link(t,u):
 if 'valet-parken-flughafen-muenchen.de' in u:
  u=u.replace('http://','https://').replace('https://valet-parken-flughafen-muenchen.de','') or '/'
  u=u.replace('/valet-parken-vergleich/','/ueber-uns/').replace('/valet-parken-kosten/','/preise/')
 rel=' rel="sponsored nofollow"' if 'parkvia.com' in u or 'parkos.de' in u else ''
 return '<a href="'+esc(u)+'"'+rel+'>'+esc(t)+'</a>'
def paragraph(t):return '<!-- wp:paragraph --><p>'+t+'</p><!-- /wp:paragraph -->'
def heading(t,n):return '<!-- wp:heading '+json.dumps({'level':n})+' --><h'+str(n)+' class="wp-block-heading">'+esc(t)+'</h'+str(n)+'><!-- /wp:heading -->'
def table(headers,rows):
 return '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr>'+''.join('<th>'+esc(x)+'</th>' for x in headers)+'</tr></thead><tbody>'+''.join('<tr>'+''.join('<td>'+x+'</td>' for x in row)+'</tr>' for row in rows)+'</tbody></table></figure><!-- /wp:table -->'
def blocks(p):
 result=[]
 for page in fitz.open(p):
  for b in page.get_text('dict')['blocks']:
   if 'lines' not in b:continue
   spans=[s for l in b['lines'] for s in l['spans']]; t=norm(' '.join(s['text'] for s in spans))
   if not t:continue
   result.append((t,max(s['size'] for s in spans),page,b))
 return result
pages=[]
for filename,slug in [('VPM _ Startseiten-Copy.pdf','startseite'),('VPM _ Preise.pdf','preise'),('VPM _ Wie sicher ist Valet-Parking.pdf','valet-parken-sicherheit'),('VPM _ Über uns.pdf','ueber-uns')]:
 p=next(Path('/workspace/attachments').rglob(filename)); bs=blocks(p); start=next(i for i,b in enumerate(bs) if b[1]>=18 and b[0]!=' '); bs=bs[start:];title=bs[0][0]; rendered=[]; i=0; faq=False
 while i<len(bs):
  t,size,page,b=bs[i];i+=1
  if t.startswith('[Buchungsmodul:'):
   changes.append({'page':slug,'removed':t});continue
  for sentence in ['Geben Sie einfach Ihre Reisedaten an und finden Sie die besten Valet-Angebote am Flughafen München.','Vergleichen Sie die Preise und berechnen Sie Ihr Angebot für Ihre Reisedaten.','Geben Sie Ihre Reisedaten und Übergabezeiten ein, um die verfügbaren Angebote und Ihren Gesamtpreis zu vergleichen.']:
   if sentence in t:changes.append({'page':slug,'removed':sentence});t=t.replace(sentence,'').strip()
  if 'mehr als 15 privaten Anbietern' in t:
   changes.append({'page':slug,'approved_correction':'Anbieterzahl entfernt'})
   t=t.replace('mehr als 15 privaten Anbietern','privaten Anbietern')
  if not t:continue
  if slug=='startseite' and t=='Verfügbare Valet-Angebote':
   rendered.append('<!-- wp:group {"align":"wide","anchor":"anbieter","className":"vpm-provider-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide vpm-provider-section" id="anbieter">'+heading(t,2)+'<!-- wp:shortcode -->[vpm_providers]<!-- /wp:shortcode --></div><!-- /wp:group -->')
   while i<len(bs) and not bs[i][0].startswith('Die Reservierung erfolgt'):i+=1
   continue
  if slug=='startseite' and t=='Angebot Valet-Service des':
   while i<len(bs) and not bs[i][0].startswith('Suchen Sie nach günstigen'):i+=1
   labels=[('Fahrzeugübergabe am Flughafen','✓','✓'),('Keine Parkplatzsuche und kein Shuttle','✓','✓'),('Rückgabe am Terminal im regulären Ablauf','Zusatzleistung','✓'),('Mehrere Angebote zur Auswahl','✕','✓'),('Außenstellplatz verfügbar','✕','✓'),('Überdachter Stellplatz verfügbar','✓','✓'),('Preise mehrerer Angebote vergleichen','✕','✓'),('E-Laden verfügbar','✓','✓'),('Bewertungen mehrerer Services vergleichen','✕','✓'),('Parkplatz auf dem Flughafengelände','✓','✕')]
   rendered.append(table(['Angebot','Valet-Service des Flughafens','Privater Valet-Service'],[[esc(x) for x in row] for row in labels]));continue
  if slug=='preise' and t.startswith('Valet-Angebot Gesamtpreis'):
   links=[l['uri'] for pg in fitz.open(p) for l in pg.get_links()][:7]
   rows=[('Park Drive Fly','89,99 €','Außenstellplatz'),('Parkservice Flieger','105 €','Außenstellplatz oder Garage'),('Valet Service Clean','145 €','Außenstellplatz'),('Parkking Munich','147,99 €','Außenstellplatz'),('ParkandGo MUC','160,99 €','Außenstellplatz'),('Parkservice-Unertl','190 €','Außenstellplatz'),('Flughafen München','335 € im Standardtarif','Terminalparkhaus')]
   rendered.append(table(['Valet-Angebot','Gesamtpreis inkl Valet-Gebühr für 8 Tage ab','Unterbringung','Buchung'],[[esc(x) for x in row]+[link('Angebot ansehen' if j==6 else 'Verfügbarkeit prüfen',links[j])] for j,row in enumerate(rows)]))
   while i<len(bs) and not bs[i][0].startswith('Die Preise gelten'):i+=1
   continue
  if size>=14:
   n=1 if size>=18 else 2 if size>=16 else 3
   if n==2:faq='Häufige Fragen' in t
   if faq and n==3 and i<len(bs) and bs[i][1]<14:
    answer=bs[i][0];i+=1
    rendered.append('<!-- wp:details --><details class="wp-block-details"><summary>'+esc(t)+'</summary>'+paragraph(esc(answer))+'</details><!-- /wp:details -->');continue
   rendered.append(heading(t,n));continue
  if '●' in t:
   items=[norm(x) for x in t.split('●') if norm(x)]
   rendered.append('<!-- wp:list --><ul class="wp-block-list">'+''.join('<!-- wp:list-item --><li>'+esc(x)+'</li><!-- /wp:list-item -->' for x in items)+'</ul><!-- /wp:list -->');continue
  # Preserve inline link text using annotation-overlapping source words.
  words=page.get_text('words'); segments=[]
  for l in page.get_links():
   if fitz.Rect(b['bbox']).intersects(l['from']):
    label=norm(' '.join(w[4] for w in words if fitz.Rect(w[:4]).intersects(l['from'])))
    if label and label in t:segments.append((label,l['uri']))
  rich=esc(t)
  for label,u in segments:
   if slug=='valet-parken-sicherheit' and t=='Verfügbarkeit prüfen' and 'vpm-sicherheit-parkandgo' in u and rendered and 'Angebotsseiten geschildert' in rendered[-1]:
    u=fixture['general_url'];changes.append({'page':slug,'link_correction':'Allgemeiner Anbieter-CTA → allgemeines ParkVia-Angebot'})
   rich=rich.replace(esc(label),link(label,u),1)
  rendered.append(paragraph(rich))
 content='\n'.join(rendered)
 if slug=='startseite':
  # Hero uses native blocks; source title and introductory text remain unchanged.
  intro=rendered[:4];rest=rendered[4:]
  content='<!-- wp:columns {"align":"wide","className":"vpm-hero"} --><div class="wp-block-columns alignwide vpm-hero"><!-- wp:column {"width":"52%"} --><div class="wp-block-column" style="flex-basis:52%">'+'\n'.join(intro)+'</div><!-- /wp:column --><!-- wp:column {"width":"48%"} --><div class="wp-block-column" style="flex-basis:48%">__VPM_HERO_IMAGE__</div><!-- /wp:column --></div><!-- /wp:columns -->\n'+'\n'.join(rest)
 pages.append({'slug':slug,'title':title,'content':content})
for provider in fixture['providers']:
 provider['meta']['source_date']='Vom Nutzer bereitgestellte Startseiten-Copy; Prüfdatum nicht angegeben'
existing_file = out/'content.json'
if existing_file.exists():
 existing_pages = json.loads(existing_file.read_text()).get('pages', [])
 generated_slugs = {page['slug'] for page in pages}
 pages.extend(page for page in existing_pages if page['slug'] not in generated_slugs)
(out/'content.json').write_text(json.dumps({'pages':pages,'providers':fixture['providers'],'general_url':fixture['general_url']},ensure_ascii=False,indent=2))
(root/'docs/REDAKTIONELLE-UEBERNAHME.json').write_text(json.dumps(changes,ensure_ascii=False,indent=2))
print([(p['slug'],len(p['content'])) for p in pages])
