"""Local-only browser checks. No external previews or production login."""
from pathlib import Path
import json
from urllib.request import urlopen
from playwright.sync_api import sync_playwright

BASE = 'http://127.0.0.1:8088'
ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'dist'
OUT.mkdir(exist_ok=True)
def page_id(slug):
    with urlopen(BASE + '/wp-json/wp/v2/pages?slug=' + slug) as response:
        pages = json.load(response)
    assert len(pages) == 1, slug
    return pages[0]['id']

GUTENBERG_ID = page_id('vpm-local-preview')
ELEMENTOR_ID = page_id('elementor-local-test')

with sync_playwright() as p:
    browser = p.chromium.launch(executable_path='/usr/bin/chromium', args=['--no-sandbox'])
    page = browser.new_page(viewport={'width': 1440, 'height': 1000})
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    for url, filename in [(BASE, 'grundgeruest'), (BASE + f'/?page_id={ELEMENTOR_ID}', 'elementor-kompatibilitaet')]:
        page.goto(url, wait_until='domcontentloaded')
        page.locator('.vpm-provider-card').first.wait_for()
        assert page.locator('h1').count() == 1
        assert page.locator('.vpm-provider-card').count() == 6
        assert 'noindex' in page.locator('meta[name=robots]').get_attribute('content')
        for width in [1440, 768, 390, 320]:
            page.set_viewport_size({'width': width, 'height': 1000})
            assert not page.evaluate('document.documentElement.scrollWidth > innerWidth'), width
            if width in [1440, 390]:
                page.screenshot(path=str(OUT / f'{filename}-{width}.png'), full_page=True)
        if 'elementor' in filename:
            assert page.locator('.elementor-widget-shortcode').count() == 1
        remote_css = page.locator('link[rel=stylesheet]').evaluate_all('(els)=>els.map(e=>e.href)')
        assert all(url.startswith(BASE) for url in remote_css), remote_css
        print(filename + ': eine H1, sechs Anbieter, vier Breiten, noindex, lokale Styles: OK')
    page.locator('.vpm-menu-toggle').click()
    assert page.locator('.vpm-menu-toggle').get_attribute('aria-expanded') == 'true'
    page.keyboard.press('Escape')
    assert page.locator('.vpm-menu-toggle').get_attribute('aria-expanded') == 'false'
    page.set_viewport_size({'width': 1440, 'height': 1100})
    page.goto(BASE + '/wp-login.php', wait_until='domcontentloaded')
    page.locator('#user_login').fill('vpm_local_admin')
    page.locator('#user_pass').fill((ROOT / '.runtime/admin-password').read_text().strip())
    page.locator('#wp-submit').click(no_wait_after=True)
    page.wait_for_url('**/wp-admin/**', wait_until='domcontentloaded')

    def editor(post_id):
        page.goto(BASE + f'/wp-admin/post.php?post={post_id}&action=edit', wait_until='domcontentloaded')
        page.wait_for_function(f"window.wp && wp.data && wp.data.select('core/editor')?.getCurrentPostId()==={post_id}", timeout=45000)
        page.evaluate("wp.data.dispatch('core/preferences').set('core/edit-post','welcomeGuide',false); wp.data.dispatch('core/interface').enableComplementaryArea('core/edit-post','edit-post/document')")

    editor(GUTENBERG_ID)
    assert page.evaluate("(() => {const valid=blocks=>blocks.every(b=>b.isValid!==false && valid(b.innerBlocks||[])); return valid(wp.data.select('core/block-editor').getBlocks());})()")
    page.screenshot(path=str(OUT / 'gutenberg-backend.png'), full_page=True)
    provider_id = page.evaluate("wp.apiFetch({path:'/wp/v2/vpm_provider?context=edit&per_page=100'}).then(posts=>posts.find(post=>post.title.raw==='Parkservice-Unertl').id)")
    editor(provider_id)
    panel = page.get_by_role('button', name='Anbieterangaben und Buchungslinks')
    panel.wait_for()
    if panel.get_attribute('aria-expanded') != 'true':
        panel.click()
    page.get_by_label('Parkos: vollständiger Affiliate-Link', exact=True).wait_for()
    assert page.get_by_label('ParkVia: vollständiger Affiliate-Link', exact=True).count() == 1
    field = page.get_by_label('Interner Prüfstand / Quelle (nicht öffentlich)', exact=True)
    original = field.input_value()
    field.fill('Lokaler Backend-Speichertest')
    page.evaluate("wp.data.dispatch('core/editor').savePost()")
    page.wait_for_function("!wp.data.select('core/editor').isSavingPost() && !wp.data.select('core/editor').isEditedPostDirty()", timeout=20000)
    editor(provider_id)
    panel = page.get_by_role('button', name='Anbieterangaben und Buchungslinks')
    panel.wait_for()
    if panel.get_attribute('aria-expanded') != 'true':
        panel.click()
    field = page.get_by_label('Interner Prüfstand / Quelle (nicht öffentlich)', exact=True)
    field.wait_for()
    assert field.input_value() == 'Lokaler Backend-Speichertest'
    field.fill(original)
    page.evaluate("wp.data.dispatch('core/editor').savePost()")
    page.wait_for_function("!wp.data.select('core/editor').isSavingPost() && !wp.data.select('core/editor').isEditedPostDirty()", timeout=20000)
    page.locator('.interface-interface-skeleton__sidebar').evaluate('(el)=>el.scrollTop=430')
    page.screenshot(path=str(OUT / 'anbieter-backend.png'), full_page=True)
    assert not errors, errors
    print('Navigation, Gutenberg-Blöcke und gespeicherte Anbieterfelder: OK. Keine Browser-JS-Fehler.')
    browser.close()
