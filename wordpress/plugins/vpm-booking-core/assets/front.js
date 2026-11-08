(() => {
  if (window.__vpmBookingFrontLoaded) return;
  window.__vpmBookingFrontLoaded = true;
  const mounted = new WeakSet(), selected = new WeakMap(), viewed = new WeakSet();
  const messages = {
    de: {choose: 'Angebot auswählen', selected: 'Ausgewählt', empty: 'Für diesen Zeitraum liegen keine Angebote vor.', unavailable: 'Die Online-Anfrage ist derzeit noch nicht verfügbar.', failed: 'Die Anfrage konnte nicht verarbeitet werden. Bitte prüfen Sie Ihre Angaben und versuchen Sie es erneut.', date: 'Bitte prüfen Sie die Übergabezeiten. Nicht vorhandene Uhrzeiten bei Zeitumstellung sind nicht auswählbar.', offset: 'Diese Uhrzeit kommt bei der Zeitumstellung zweimal vor. Bitte wählen Sie die gewünschte Uhrzeit.', first: 'Erste Uhrzeit', second: 'Zweite Uhrzeit', requested: 'Ihre Anfrage wurde übermittelt. Sie ist noch keine bestätigte Buchung. Eine Zahlung wurde nicht ausgelöst.', planned: 'Die Standortseite für diesen Flughafen wird vorbereitet.', limit: 'Bitte versuchen Sie es in Kürze erneut.', coupon: 'Der Gutscheincode ist für dieses Angebot nicht verfügbar.', busy: 'Wird geprüft …', timezone: 'Lokale Flughafenzeit', newsletter: 'Bitte bestätigen Sie Ihre Anmeldung über den zugesandten Link, falls eine Bestätigung erforderlich ist.'},
    en: {choose: 'Select offer', selected: 'Selected', empty: 'No offers are available for this period.', unavailable: 'Online enquiries are not yet available.', failed: 'The enquiry could not be processed. Please check your details and try again.', date: 'Please check the handover times. Times skipped during a clock change cannot be selected.', offset: 'This time occurs twice during the clock change. Please select the intended occurrence.', first: 'First occurrence', second: 'Second occurrence', requested: 'Your enquiry has been submitted. This is not a confirmed booking. No payment has been taken.', planned: 'The location page for this airport is being prepared.', limit: 'Please try again shortly.', coupon: 'This voucher is not available for this offer.', busy: 'Checking …', timezone: 'Local airport time', newsletter: 'Please confirm your subscription using the emailed link if confirmation is required.'}
  };
  const text = root => messages[root.dataset.locale] || messages.de;
  const status = (root, value, bad = false) => {
    let node = root.querySelector('.vpm-booking-status');
    if (!node) {node = document.createElement('p'); node.className = 'vpm-booking-status'; node.setAttribute('aria-live', 'polite'); root.append(node);}
    node.textContent = value; node.classList.toggle('vpm-booking-error', bad);
  };
  const statisticsConsent = () => {
    try {return typeof window.cmplz_has_consent === 'function' && window.cmplz_has_consent('statistics') === true;}
    catch (_) {return false;}
  };
  const analytics = (root, event, airport = '', error = '') => {
    if (root.dataset.analytics !== '1' || root.dataset.preview === '1' || navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true) return;
    if (!statisticsConsent()) return;
    const allow = ['vpm_booking_view', 'vpm_booking_search', 'vpm_booking_offer_select', 'vpm_booking_request', 'vpm_booking_error'];
    if (!allow.includes(event)) return;
    const params = {booking_flow: 'enquiry', locale: root.dataset.locale === 'en' ? 'en' : 'de'};
    if (/^[A-Z]{3}$/.test(airport)) params.airport = airport;
    if (/^[a-z_]{1,60}$/.test(error)) params.error_code = error;
    // Optional analytics must never interrupt a customer enquiry.
    try {
      if (root.dataset.analyticsTarget === 'gtag') {if (typeof window.gtag === 'function') window.gtag('event', event, params);}
      else if (root.dataset.analyticsTarget === 'dom') document.dispatchEvent(new CustomEvent('vpm-booking:analytics', {detail: {event, ...params}}));
      else {window.dataLayer = window.dataLayer || []; window.dataLayer.push({event, ...params});}
    } catch (_) {}
  };
  const money = (root, cents, currency) => new Intl.NumberFormat(root.dataset.locale === 'en' ? 'en-GB' : 'de-DE', {style: 'currency', currency: currency.toUpperCase()}).format(cents / 100);
  const post = async (root, route, data) => {
    const endpoint = new URL(root.dataset.api, location.origin);
    if (endpoint.searchParams.has('rest_route')) endpoint.searchParams.set('rest_route', endpoint.searchParams.get('rest_route').replace(/\/$/, '') + '/' + route);
    else endpoint.pathname = endpoint.pathname.replace(/\/$/, '') + '/' + route;
    const response = await fetch(endpoint.href, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)});
    const value = await response.json(); if (!response.ok) throw value; return value;
  };
  const clearOffers = root => {selected.delete(root); root.querySelector('.vpm-booking-offers')?.replaceChildren(); const request = root.querySelector('.vpm-booking-request'); if (request) request.hidden = true;};
  const handleError = (root, error) => {
    const msg = text(root), code = typeof error?.code === 'string' ? error.code : '';
    if (code === 'ambiguous_local_time' && ['departure', 'return'].includes(error?.data?.field) && Array.isArray(error.data.offsets)) {
      const group = root.querySelector('.vpm-time-offset[data-field="' + error.data.field + '"]');
      if (group) {
        const select = group.querySelector('select'); select.replaceChildren();
        const placeholder = document.createElement('option'); placeholder.value = ''; placeholder.textContent = msg.offset; select.append(placeholder);
        error.data.offsets.forEach((offset, i) => {if (!/^[+-]\d{2}:\d{2}$/.test(offset)) return; const option = document.createElement('option'); option.value = offset; option.textContent = (i === 0 ? msg.first : msg.second) + ' (UTC' + offset + ')'; select.append(option);});
        group.hidden = false; select.required = true; select.focus();
      }
      status(root, msg.offset, true); return;
    }
    const message = code.includes('rate_limited') ? msg.limit : code.startsWith('coupon_') || code.startsWith('subsidy_') ? msg.coupon : code.includes('not_enabled') || code.includes('not_configured') ? msg.unavailable : code.includes('departure') || code.includes('return') || code.includes('travel') || code.includes('offset') ? msg.date : msg.failed;
    status(root, message, true); analytics(root, 'vpm_booking_error', root.querySelector('[name="airport"]')?.value || '', code);
  };
  document.addEventListener('submit', async event => {
    const form = event.target, root = form.closest('.vpm-booking-surface'); if (!root) return;
    if (!form.matches('.vpm-airport-form,.vpm-booking-search,.vpm-booking-request') && !root.classList.contains('vpm-booking-newsletter')) return;
    event.preventDefault(); if (root.dataset.preview === '1' || !form.reportValidity()) return;
    const button = form.querySelector('button[type="submit"]'); if (button?.disabled) return;
    if (form.classList.contains('vpm-airport-form')) {
      const code = form.elements.airport.value; let airports = []; try {airports = JSON.parse(root.dataset.airports || '[]');} catch (_) {}
      const airport = airports.find(a => a.code === code); if (!airport) return;
      if (airport.url) {const next = new URL(airport.url, location.origin); if (next.origin === location.origin) location.assign(next.href); return;}
      const widget = [...document.querySelectorAll('.vpm-booking-widget')].find(x => x.querySelector('[name="airport"] option[value="' + code + '"]'));
      if (widget) {const select = widget.querySelector('[name="airport"]'); select.value = code; select.dispatchEvent(new Event('change', {bubbles: true})); widget.scrollIntoView({behavior: 'smooth', block: 'center'}); select.focus({preventScroll: true});}
      else status(root, text(root).planned);
      return;
    }
    if (root.classList.contains('vpm-booking-widget') && root.dataset.enabled !== '1') {status(root, text(root).unavailable); return;}
    button.disabled = true; status(root, text(root).busy);
    try {
      const data = Object.fromEntries(new FormData(form));
      if (form.classList.contains('vpm-booking-search')) {
        clearOffers(root); analytics(root, 'vpm_booking_search', data.airport);
        const result = await post(root, 'public/offers', data); const container = root.querySelector('.vpm-booking-offers');
        result.offers.forEach(offer => {
          const card = document.createElement('article'); card.className = 'vpm-booking-offer';
          const title = document.createElement('h3'); title.textContent = offer.partner;
          const price = document.createElement('p'); price.className = 'vpm-booking-price'; price.textContent = money(root, offer.amount_cents, offer.currency);
          const terms = document.createElement('p'); terms.textContent = offer.terms;
          const choose = document.createElement('button'); choose.type = 'button'; choose.className = 'vpm-booking-choose'; choose.textContent = text(root).choose; choose.dataset.quoteId = offer.quote_id; choose.setAttribute('aria-pressed', 'false');
          card.append(title, price, terms, choose); container.append(card);
        });
        status(root, result.offers.length ? text(root).timezone + ': ' + result.timezone : text(root).empty);
      } else if (form.classList.contains('vpm-booking-request')) {
        const quote = selected.get(root); if (!quote) throw {code: 'quote_expired'};
        const result = await post(root, 'public/requests', {quote_id: quote, email: data.email, coupon: data.coupon || '', locale: root.dataset.locale});
        const reference = /^[a-f0-9-]{36}$/.test(result.reference || '') ? ' ' + (root.dataset.locale === 'en' ? 'Reference: ' : 'Referenz: ') + result.reference : '';
        status(root, text(root).requested + ' ' + money(root, result.amount_cents, result.currency) + reference);
        analytics(root, 'vpm_booking_request', root.querySelector('[name="airport"]').value);
        clearOffers(root); form.reset();
      } else {
        const checkbox = form.querySelector('[name="marketing_agreed"]');
        await post(root, 'marketing/request', {email: data.email, agreed: checkbox?.checked === true}); status(root, text(root).newsletter); form.reset();
      }
    } catch (error) {handleError(root, error);}
    finally {button.disabled = false;}
  });
  document.addEventListener('click', event => {
    const button = event.target.closest('.vpm-booking-choose'); if (!button) return;
    const root = button.closest('.vpm-booking-widget'); if (!root || root.dataset.preview === '1') return;
    selected.set(root, button.dataset.quoteId);
    root.querySelectorAll('.vpm-booking-choose').forEach(b => {const active = b === button; b.setAttribute('aria-pressed', active ? 'true' : 'false'); b.textContent = active ? text(root).selected : text(root).choose;});
    const form = root.querySelector('.vpm-booking-request'); form.hidden = false; form.querySelector('button').disabled = false; form.querySelector('[name="email"]').focus();
    analytics(root, 'vpm_booking_offer_select', root.querySelector('[name="airport"]').value);
  });
  document.addEventListener('change', event => {
    const root = event.target.closest('.vpm-booking-widget'); if (!root || !event.target.closest('.vpm-booking-search')) return;
    clearOffers(root); const group = event.target.closest('div')?.querySelector('.vpm-time-offset');
    if (event.target.name === 'airport') root.querySelectorAll('.vpm-time-offset').forEach(node => {node.hidden = true; node.querySelector('select').required = false; node.querySelector('select').value = '';});
    if (event.target.type === 'datetime-local' && group) {group.hidden = true; group.querySelector('select').required = false; group.querySelector('select').value = '';}
  });
  const view = root => {
    if (!viewed.has(root) && root.getBoundingClientRect().bottom > 0 && root.getBoundingClientRect().top < innerHeight && statisticsConsent()) {analytics(root, 'vpm_booking_view'); viewed.add(root);}
  };
  const init = () => document.querySelectorAll('.vpm-booking-surface').forEach(root => {
    if (mounted.has(root)) return; mounted.add(root);
    if (root.dataset.preview !== '1') {root.querySelector('.vpm-airport-form button')?.removeAttribute('disabled'); if (root.dataset.enabled === '1') root.querySelector('.vpm-booking-search button')?.removeAttribute('disabled'); view(root);}
  });
  init(); let scheduled = false;
  new MutationObserver(() => {if (!scheduled) {scheduled = true; requestAnimationFrame(() => {scheduled = false; init();});}}).observe(document.body, {childList: true, subtree: true});
  document.addEventListener('cmplz_status_change', () => document.querySelectorAll('.vpm-booking-widget').forEach(view));
})();
