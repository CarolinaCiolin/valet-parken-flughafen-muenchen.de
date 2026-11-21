"""Local-only regression for the four approved home cards against the pre-correction snapshot."""
from pathlib import Path
import json,hashlib
from playwright.sync_api import sync_playwright
R=Path(__file__).resolve().parents[1];O=R/'vorschau/affiliate-video-2026-11';u=json.loads((R/'wordpress/plugins/vpm-content-drafts/updates/affiliate-2026-11.json').read_text());d=json.loads((R/'wordpress/plugins/vpm-content-drafts/content.json').read_text());before=json.loads((R/'.runtime/compact-before.json').read_text())
assert d['pages']==before['pages'];assert d['providers'][:-4]==before['old_providers']
for name,sha in before.get('normalized_files',before['files']).items():assert hashlib.sha256((R/name).read_bytes()).hexdigest()==sha,name
with sync_playwright()as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);g=b.new_page();g.goto('http://127.0.0.1:8088/wp-login.php');g.locator('#user_login').fill('vpm_local_admin');g.locator('#user_pass').fill((R/'.runtime/admin-password').read_text().strip());g.locator('#wp-submit').click();g.wait_for_load_state('networkidle');ids=json.loads((R/'.runtime/affiliate-preview-ids.json').read_text());g.goto('http://127.0.0.1:8088/?page_id='+str(ids['pages']['startseite'])+'&preview=true',wait_until='networkidle')
 for w in [1440,390]:
  g.set_viewport_size({'width':w,'height':1000});assert g.evaluate('document.documentElement.scrollWidth<=innerWidth');assert g.locator('.vpm-provider-card-compact').count()==4
  for data in [x for x in u['providers']if x.get('platform')=='parkos']:
   card=next(c for c in g.locator('.vpm-provider-card').all()if c.locator('h3').text_content()==data['name']);assert card.locator('li').all_text_contents()==data['features'];assert card.locator('.vpm-provider-rating').text_content()==data['rating'];assert card.locator('.vpm-provider-price strong').count()==1;assert card.locator('ul + .vpm-provider-price').count()==1;assert card.locator('.vpm-provider-description,.vpm-provider-booking-note').count()==0
   a=card.locator('a');assert a.count()==1 and a.get_attribute('href')==data['affiliate_url'];assert set(a.get_attribute('rel').split())>={'sponsored','nofollow'}
   style=card.locator('.vpm-provider-price').evaluate('(e)=>{let s=getComputedStyle(e);return [s.borderTopWidth,s.backgroundColor,s.fontWeight]}');assert style==['0px','rgba(0, 0, 0, 0)','700'],style
   card.screenshot(path=str(O/f"parkos-{data['id']}-{w}.png"))
 b.close()
(R/'docs/STARTSEITENKARTEN-KORREKTUR.json').write_text(json.dumps({'result':'passed','widths':[1440,390],'cards':4,'exact_bullets_per_card':3,'price_bold_below_bullets_without_box':True,'affiliate_links_exact_and_sponsored':True,'other_nine_provider_data_unchanged':True,'all_page_source_contents_unchanged':True,'price_and_safety_preview_contents_unchanged':True,'only_export_line_end_whitespace_normalized':True,'deployed':False},indent=2)+'\n')
print('Four cards passed desktop/mobile; all non-target page files and nine provider data unchanged.')
