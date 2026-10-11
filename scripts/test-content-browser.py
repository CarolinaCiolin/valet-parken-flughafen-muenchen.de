from pathlib import Path
from urllib.request import urlopen
import json
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent
base='http://127.0.0.1:8088'
def page_id(slug):
 with urlopen(base+'/wp-json/wp/v2/pages?slug='+slug) as response:
  posts=json.load(response)
 assert len(posts)==1,slug
 return posts[0]['id']
review_pages=[(page_id(source),label) for source,label in [('startseite','startseite'),('preise','preise'),('valet-parken-sicherheit','sicherheit'),('ueber-uns','ueber-uns')]]
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=browser.new_page()
 for id,slug in review_pages:
  page.goto(base+f'/?page_id={id}')
  assert page.locator('h1').count()==1
  assert page.locator('input[type=date],input[type=time]').count()==0
  assert 'Geben Sie Ihre Reisedaten' not in page.locator('body').inner_text()
  for width in [1440,768,390,320]:
   page.set_viewport_size({'width':width,'height':1000})
   assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(slug,width)
   if width in [1440,390]:page.screenshot(path=str(root/'dist'/f'{slug}-{width}.png'),full_page=True)
  if slug=='startseite':assert page.locator('.vpm-provider-card').count()==6
  if slug=='preise':assert page.locator('tbody tr').count()==7
  print(slug,'responsive OK')
 page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**', wait_until='domcontentloaded')
 for id,_ in review_pages:
  page.goto(base+f'/wp-admin/post.php?post={id}&action=edit');page.wait_for_function(f"window.wp && wp.data && wp.data.select('core/editor')?.getCurrentPostId()==={id}",timeout=45000)
  invalid=page.evaluate("(()=>{let bad=[];function walk(bs){for(const b of bs){if(b.isValid===false)bad.push({name:b.name,html:b.originalContent});walk(b.innerBlocks||[])}}walk(wp.data.select('core/block-editor').getBlocks());return bad})()")
  assert not invalid,(id,invalid)
  print(id,'valid Gutenberg blocks OK')
 browser.close()
