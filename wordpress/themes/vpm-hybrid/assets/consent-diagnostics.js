/* Admin-only same-origin frontend inspection. No consent changes, no external reporting. */
(() => {
  const start = document.getElementById('vpm-consent-start');
  const output = document.getElementById('vpm-consent-output');
  const frame = document.getElementById('vpm-consent-frame');
  const again = document.getElementById('vpm-consent-refresh');
  const copy = document.getElementById('vpm-consent-copy');
  if (!start || !output || !frame) return;
  const inspect = () => {
    try {
      const w = frame.contentWindow, doc = w.document;
      if (doc.readyState === 'loading') { output.value = 'Startseite lädt noch. Bitte kurz warten und erneut auslesen.'; return; }
      const config = w.complianz;
      const report = {
        Hinweis: 'Aktueller Browser, angemeldete Sitzung, Startseite mit Testparameter. Kein Nachweis für anonymen Seitencache. Keine Cookies gelöscht, keine Einwilligung erteilt oder zurückgesetzt.',
        Zeitpunkt: new Date().toISOString(),
        Seite: w.location.origin + w.location.pathname,
        'Dokument geladen': doc.readyState,
        'Do Not Track': w.navigator.doNotTrack === '1',
        'Global Privacy Control': w.navigator.globalPrivacyControl === true,
        'Complianz-Konfiguration im Browser vorhanden': !!config,
        'Banner-Funktion geladen': typeof w.show_cookie_banner === 'function',
        'Banner-Container vorhanden': !!doc.getElementById('cmplz-cookiebanner-container'),
        'Richtlinien-Verwaltung auf dieser Seite': !!doc.querySelector('#cmplz-manage-consent-container, .cmplz-dropdown-cookiepolicy'),
      };
      if (config) {
        ['consenttype', 'region', 'user_banner_id', 'disable_cookiebanner', 'do_not_track_enabled', 'geoip'].forEach(key => { report[key] = config[key] ?? null; });
      }
      report.Banner = Array.from(doc.querySelectorAll('.cmplz-cookiebanner')).map(el => {
        const style = w.getComputedStyle(el), bounds = el.getBoundingClientRect();
        return {Klassen: el.className, display: style.display, visibility: style.visibility, opacity: style.opacity,
          'z-index': style.zIndex, Breite: Math.round(bounds.width), Hoehe: Math.round(bounds.height),
          'Im sichtbaren Bereich': bounds.bottom > 0 && bounds.top < w.innerHeight && bounds.right > 0 && bounds.left < w.innerWidth};
      });
      report['Complianz-Skripte im HTML'] = Array.from(doc.scripts).filter(el => /complianz|cmplz/i.test([el.id, el.src, el.getAttribute('data-src'), el.getAttribute('data-litespeed-src')].join(' '))).map(el => ({
        ID: el.id, Typ: el.type || 'text/javascript', Quelle: (el.src || el.getAttribute('data-src') || el.getAttribute('data-litespeed-src') || '').split('?')[0],
        'LiteSpeed verzögert': el.type === 'litespeed/javascript' || el.hasAttribute('data-litespeed-src'),
      }));
      report['Complianz-Ressourcen geladen'] = w.performance.getEntriesByType('resource').filter(el => /complianz|cmplz/i.test(el.name)).map(el => ({Quelle: el.name.split('?')[0], Typ: el.initiatorType, Status: el.responseStatus ?? null}));
      // Report cookie names only, never values, identifiers or arbitrary storage contents.
      report['Vorhandene Complianz-Cookie-Namen'] = doc.cookie.split(';').map(part => part.split('=')[0].trim()).filter(name => /^cmplz[_-]/.test(name));
      output.value = JSON.stringify(report, null, 2);
      again.disabled = false; copy.disabled = false;
    } catch (error) {
      output.value = 'Die eingebettete Seite konnte nicht gelesen werden: ' + error.message + '\nMögliche Ursache: Weiterleitung auf eine andere Domain oder Einbettung durch Sicherheitsrichtlinie blockiert. Dann bitte den Quelltext der Startseite als Datei bereitstellen.';
    }
  };
  frame.addEventListener('load', () => { setTimeout(inspect, 1500); });
  start.addEventListener('click', () => {
    output.value = 'Startseite wird im aktuellen Browser geladen …';
    const url = new URL(start.dataset.url, location.origin);
    if (url.origin !== location.origin) { output.value = 'Frontend und WordPress-Verwaltung haben unterschiedliche Domains. Eine eingebettete Diagnose ist so nicht möglich.'; return; }
    url.searchParams.set('vpm_cookie_probe', Date.now().toString());
    frame.hidden = false; frame.src = url.href;
  });
  again.addEventListener('click', inspect);
  copy.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(output.value); copy.textContent = 'Bericht kopiert'; }
    catch (_) { output.focus(); output.select(); copy.textContent = 'Bitte mit Strg/Cmd+C kopieren'; }
  });
})();
