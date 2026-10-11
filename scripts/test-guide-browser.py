"""Draft privacy, Gutenberg validity and native automatic post query, with temporary fixtures."""
from pathlib import Path
import subprocess,json
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parent.parent;base='http://127.0.0.1:8088'
def fixture(action):subprocess.run(['docker','exec','-e','VPM_LOCAL_SETUP=1','vpm-local-wp','php','/tmp/vpm-test-guide-local.php',action],check=True)
try:
 with sync_playwright() as p:
  b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);page=b.new_page(viewport={'width':1440,'height':1000})
  assert page.request.get(base+'/ratgeber/').status==404,'Draft must not be public'
  page.goto(base+'/wp-login.php');page.locator('#user_login').fill('vpm_local_admin');page.locator('#user_pass').fill((root/'.runtime/admin-password').read_text().strip());page.locator('#wp-submit').click(no_wait_after=True);page.wait_for_url('**/wp-admin/**',wait_until='domcontentloaded')
  # Obtain the draft through the authenticated REST route and a fresh editor nonce.
  page.goto(base+'/wp-admin/post-new.php?post_type=page',wait_until='domcontentloaded');page.wait_for_function("Boolean(window.wp&&wp.apiFetch)")
  guides=page.evaluate("wp.apiFetch({path:'/wp/v2/pages?context=edit&slug=ratgeber&status=draft'})")
  assert len(guides)==1;id=guides[0]['id'];preview=base+f'/?page_id={id}&preview=true'
  page.goto(base+f'/wp-admin/post.php?post={id}&action=edit',wait_until='domcontentloaded');page.wait_for_function(f"window.wp&&wp.data?.select('core/editor')?.getCurrentPostId()==={id}")
  assert page.evaluate("(()=>{const valid=bs=>bs.every(b=>b.isValid!==false&&valid(b.innerBlocks||[]));return valid(wp.data.select('core/block-editor').getBlocks())})()")
  assert page.evaluate("wp.data.select('core/editor').getCurrentPost().status")=='draft'
  page.goto(preview,wait_until='networkidle');assert page.locator('h1').inner_text()=='Valet-Ratgeber';assert page.locator('.vpm-guide-grid .wp-block-post').count()==0;assert page.get_by_text('Noch keine Beiträge veröffentlicht.',exact=True).is_visible()
  page.add_style_tag(content='#wpadminbar{display:none}html{margin-top:0!important}')
  page.screenshot(path=str(root/'dist/ratgeber-entwurf-desktop.png'),full_page=True)
  page.set_viewport_size({'width':390,'height':900});page.screenshot(path=str(root/'dist/ratgeber-entwurf-mobil.png'),full_page=True)
  fixture('create');page.set_viewport_size({'width':1440,'height':1000});page.goto(preview,wait_until='networkidle')
  titles=page.locator('.vpm-guide-grid .wp-block-post-title').all_text_contents();assert len(titles)==9 and 'Lokaler Testbeitrag 12' not in titles and titles[0]=='Lokaler Testbeitrag 11',titles
  for width in [1440,768,390,320]:
   page.set_viewport_size({'width':width,'height':1000});assert page.evaluate('document.documentElement.scrollWidth<=innerWidth')
   columns=page.locator('.vpm-guide-grid').evaluate('(e)=>getComputedStyle(e).gridTemplateColumns.split(" ").length')
   assert columns==(3 if width>1000 else 2 if width>760 else 1),(width,columns)
   for img in page.locator('.vpm-guide-grid img').all():img.scroll_into_view_if_needed();img.evaluate('(i)=>i.decode()')
  page.set_viewport_size({'width':1440,'height':1000});page.locator('.wp-block-query-pagination-next').click();assert page.locator('.vpm-guide-grid .wp-block-post').count()==2
  page.locator('.wp-block-query-pagination-previous').click();assert page.locator('.vpm-guide-grid .wp-block-post').count()==9
  page.locator('.wp-block-post-title a').first.click();assert page.locator('h1').inner_text()=='Lokaler Testbeitrag 11'
  assert 'Technischer Test der Beitragsübersicht.' in page.locator('main').inner_text()
  page.close();b.close()
 print('Ratgeber: Entwurf privat, Gutenberg gültig, neue Beiträge automatisch, 9+2 Pagination, Entwürfe ausgeblendet, Artikellinks und vier Breiten OK.',flush=True)
finally:fixture('cleanup')
