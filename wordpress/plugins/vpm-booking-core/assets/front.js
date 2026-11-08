(() => {
  if (window.__vpmBookingFrontLoaded) return;
  window.__vpmBookingFrontLoaded = true;
  const mounted = new WeakSet(), selected = new WeakMap(), offers = new WeakMap(), viewed = new WeakSet();
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
    const response = await fetch(endpoint.href, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', ...(root.dataset.restNonce ? {'X-WP-Nonce':root.dataset.restNonce} : {})}, body: JSON.stringify(data)});
    const value = await response.json(); if (!response.ok) throw value; return value;
  };
  const parkingLabel = (root, type) => ({outdoor: ['Außenstellplatz', 'Outdoor parking'], covered: ['Überdachter Stellplatz', 'Covered parking'], garage: ['Parkhaus', 'Parking garage'], underground: ['Tiefgarage', 'Underground parking'], unspecified: ['Stellplatzart nicht angegeben', 'Parking type not specified']}[type] || ['', ''])[root.dataset.locale === 'en' ? 1 : 0];
  const collect = (card, validate = false) => [...card.querySelectorAll('.vpm-addon:checked')].map(input => {
    const quantity = input.closest('.vpm-addon-row').querySelector('.vpm-addon-quantity');
    if (validate && !quantity.reportValidity()) throw {code: 'invalid_addon_selection'};
    return {id: input.value, quantity: Number(quantity.value)};
  });
  const total = (offer, chosen) => offer.amount_cents + chosen.reduce((sum, item) => sum + (offer.addons.find(a => a.id === item.id)?.unit_amount_cents || 0) * item.quantity, 0);
  const summary = root => {
    const selection = selected.get(root); if (!selection) return;
    const {offer, card} = selection, chosen = collect(card), box = root.querySelector('.vpm-booking-summary'); box.replaceChildren();
    const title = document.createElement('strong'); title.textContent = offer.partner + (offer.product_name ? ' · ' + offer.product_name : ''); box.append(title);
    const list = document.createElement('ul'); const base = document.createElement('li'); base.textContent = parkingLabel(root, offer.parking_type) + ': ' + money(root, offer.amount_cents, offer.currency); list.append(base);
    chosen.forEach(item => {const addon = offer.addons.find(a => a.id === item.id); const row = document.createElement('li'); row.textContent = item.quantity + ' × ' + addon.label + ': ' + money(root, addon.unit_amount_cents * item.quantity, offer.currency); list.append(row);}); box.append(list);
    const sum = document.createElement('p'); sum.className = 'vpm-booking-total'; sum.textContent = (root.dataset.locale === 'en' ? 'Total before voucher: ' : 'Gesamt vor Gutschein: ') + money(root, total(offer, chosen), offer.currency); box.append(sum);
  };
  const clearOffers = root => {root.__vpmCouponInput = ''; selected.delete(root); root.querySelector('.vpm-booking-offers')?.replaceChildren(); const request = root.querySelector('.vpm-booking-request'); if (request) request.hidden = true; const filters = root.querySelector('.vpm-booking-filters'); if (filters) filters.hidden = true;};
  const couponMessage = (root, code) => {
    const en = root.dataset.locale === 'en';
    const messages = {
      coupon_expired: ['Leider ist der Gutschein bereits abgelaufen.', 'Unfortunately, this voucher has expired.'],
      coupon_redeemed: ['Leider ist der Gutschein bereits eingelöst.', 'Unfortunately, this voucher has already been redeemed.'],
      coupon_in_progress: ['Dieser Gutschein wird bereits für eine andere Anfrage verwendet.', 'This voucher is already attached to another enquiry.'],
      coupon_wrong_customer: ['Dieser Gutschein gehört zu einer anderen E-Mail-Adresse.', 'This voucher belongs to a different email address.'],
      coupon_unknown: ['Dieser Gutscheincode ist nicht bekannt.', 'This voucher code was not found.'],
      coupon_not_started: ['Dieser Gutschein ist noch nicht gültig.', 'This voucher is not valid yet.']
    }; return messages[code]?.[en ? 1 : 0] || (en ? 'This voucher is not available for this offer.' : 'Der Gutschein ist für dieses Angebot nicht verfügbar.');
  };
  const resetCoupon = root => {const box = root.querySelector('.vpm-coupon-status'); if (box) box.textContent = ''; summary(root);};
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
    if (code.startsWith('coupon_') || code.startsWith('subsidy_')) {root.__vpmCouponInput = ''; resetCoupon(root); const box = root.querySelector('.vpm-coupon-status'); if (box) box.textContent = couponMessage(root, code);}
    const message = code.includes('rate_limited') ? msg.limit : code.startsWith('coupon_') || code.startsWith('subsidy_') ? msg.coupon : code.includes('not_enabled') || code.includes('not_configured') ? msg.unavailable : code.includes('departure') || code.includes('return') || code.includes('travel') || code.includes('offset') ? msg.date : msg.failed;
    status(root, code.startsWith('coupon_') ? couponMessage(root, code) : message, true); analytics(root, 'vpm_booking_error', root.querySelector('[name="airport"]')?.value || '', code);
  };
  document.addEventListener('submit', async event => {
    const form = event.target, root = form.closest('.vpm-booking-surface'); if (!root) return;
    if (!form.matches('.vpm-airport-form,.vpm-booking-search,.vpm-booking-request') && !root.classList.contains('vpm-booking-newsletter')) return;
    event.preventDefault(); if (root.dataset.preview === '1' || !form.reportValidity()) return;
    if(root.dataset.flow==='steps'&&form.classList.contains('vpm-booking-request')&&root.dataset.step!=='5')return;
    const button = form.querySelector('button[type="submit"]'); if (button?.disabled) return;
    if (form.classList.contains('vpm-airport-form')) {
      const code = form.elements.airport.value; let airports = []; try {airports = JSON.parse(root.dataset.airports || '[]');} catch (_) {}
      const airport = airports.find(a => a.code === code); if (!airport) return;
      if (airport.url) {const next = new URL(airport.url, location.origin); if (next.origin === location.origin) location.assign(next.href); return;}
      const widget = [...document.querySelectorAll('.vpm-booking-widget[data-airport-mode="select"]')].find(x => x.querySelector('[name="airport"] option[value="' + code + '"]'));
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
        const result = await post(root, 'public/offers', {...data, locale: root.dataset.locale}); const container = root.querySelector('.vpm-booking-offers');
        result.offers.forEach(offer => {
          const card = document.createElement('article'); card.className = 'vpm-booking-offer';
          const title = document.createElement('h3'); title.textContent = offer.partner;
          const price = document.createElement('p'); price.className = 'vpm-booking-price'; price.textContent = money(root, offer.amount_cents, offer.currency);
          const terms = document.createElement('p'); terms.textContent = offer.terms;
          const choose = document.createElement('button'); choose.type = 'button'; choose.className = 'vpm-booking-choose'; choose.textContent = text(root).choose; choose.dataset.quoteId = offer.quote_id; choose.setAttribute('aria-pressed', 'false');
          const product = document.createElement('p'); product.textContent = [offer.product_name, parkingLabel(root, offer.parking_type)].filter(Boolean).join(' · ');
          const attributes = document.createElement('ul'); (offer.attributes || []).forEach(value => {const item = document.createElement('li'); item.textContent = value; attributes.append(item);});
          card.dataset.parkingType = offer.parking_type || 'unspecified'; offers.set(card, offer); card.append(title, product, price, attributes, terms);
          if (offer.addons?.length) {
            const group = document.createElement('fieldset'); group.className = 'vpm-booking-addons'; const legend = document.createElement('legend'); legend.textContent = root.dataset.locale === 'en' ? 'Optional extras' : 'Optionale Zusatzleistungen'; group.append(legend);
            offer.addons.forEach(addon => {
              const row = document.createElement('div'); row.className = 'vpm-addon-row'; const label = document.createElement('label'), checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.className = 'vpm-addon'; checkbox.value = addon.id;
              label.append(checkbox, document.createTextNode(addon.label + ' · ' + money(root, addon.unit_amount_cents, offer.currency) + (addon.max_quantity > 1 ? (root.dataset.locale === 'en' ? ' / unit' : ' / Einheit') : '')));
              const note = document.createElement('small'); note.textContent = addon.description || '';
              const quantity = document.createElement('input'); quantity.type = 'number'; quantity.className = 'vpm-addon-quantity'; quantity.value = '1'; quantity.min = '1'; quantity.max = String(addon.max_quantity); quantity.step = '1'; quantity.disabled = true; quantity.hidden = addon.max_quantity === 1; quantity.setAttribute('aria-label', (root.dataset.locale === 'en' ? 'Quantity: ' : 'Menge: ') + addon.label);
              row.append(label, note, quantity); group.append(row);
            }); card.append(group);
          }
          const sum = document.createElement('p'); sum.className = 'vpm-offer-total'; sum.textContent = (root.dataset.locale === 'en' ? 'Total: ' : 'Gesamt: ') + money(root, offer.amount_cents, offer.currency); card.append(sum, choose); container.append(card);
        });
        const filters = root.querySelector('.vpm-booking-filters'), filter = filters?.querySelector('select');
        if (filter) {while (filter.options.length > 1) filter.remove(1); filter.value = ''; [...new Set(result.offers.map(o => o.parking_type || 'unspecified'))].forEach(type => {const option = document.createElement('option'); option.value = type; option.textContent = parkingLabel(root, type); filter.append(option);}); filters.hidden = result.offers.length < 2;}
        if(root.dataset.flow==='steps') wizardStep(root,2);
        status(root, result.offers.length ? text(root).timezone + ': ' + result.timezone : text(root).empty);
      } else if (form.classList.contains('vpm-booking-request')) {
        const selection = selected.get(root); if (!selection) throw {code: 'quote_expired'};
        const result = await post(root, 'public/requests', {quote_id: selection.offer.quote_id, addons: collect(selection.card, true), airport_scope: root.querySelector('[name="airport_scope"]')?.value || '', email: data.email, coupon: data.coupon || '', locale: root.dataset.locale});
        const reference = /^[a-f0-9-]{36}$/.test(result.reference || '') ? ' ' + (root.dataset.locale === 'en' ? 'Reference: ' : 'Referenz: ') + result.reference : '';
        status(root, text(root).requested + ' ' + money(root, result.amount_cents, result.currency) + reference);
        analytics(root, 'vpm_booking_request', root.querySelector('[name="airport"]').value);
        clearOffers(root); form.reset(); if(root.dataset.flow==='steps'){root.__demoReference=result.reference;wizardStep(root,6);}
      } else {
        const checkbox = form.querySelector('[name="marketing_agreed"]');
        await post(root, 'marketing/request', {email: data.email, agreed: checkbox?.checked === true}); status(root, text(root).newsletter); form.reset();
      }
    } catch (error) {handleError(root, error); if(root.dataset.flow==='steps'&&form.classList.contains('vpm-booking-request'))wizardStep(root,error?.code==='quote_expired'?1:4);}
    finally {button.disabled = false;}
  });
  document.addEventListener('click', async event => {
    const apply = event.target.closest('.vpm-coupon-apply');
    if (apply) {
      const root = apply.closest('.vpm-booking-widget'), selection = selected.get(root); if (!selection || root.dataset.preview === '1' || apply.disabled) return;
      const email = root.querySelector('[name="email"]'); if (!email.reportValidity()) return;
      const form = root.querySelector('.vpm-booking-request'), box = root.querySelector('.vpm-coupon-status');
      let addons; try {addons = collect(selection.card, true);} catch (_) {box.textContent = text(root).failed; return;}
      const data = {quote_id: selection.offer.quote_id, addons, airport_scope: root.querySelector('[name="airport_scope"]')?.value || '', email: email.value, coupon: form.elements.coupon.value, locale: root.dataset.locale};
      const fingerprint = JSON.stringify(data); root.__vpmCouponInput = fingerprint; apply.disabled = true; summary(root);
      try {
        const result = await post(root, 'public/price', data); if (root.__vpmCouponInput !== fingerprint) return;
        status(root, ''); box.textContent = root.dataset.locale === 'en' ? 'Voucher checked.' : 'Gutschein geprüft.';
        showPrice(root,result);
      } catch (error) {if (root.__vpmCouponInput === fingerprint) box.textContent = couponMessage(root, error?.code || '');}
      finally {apply.disabled = false;}
      return;
    }
    const button = event.target.closest('.vpm-booking-choose'); if (!button) return;
    const root = button.closest('.vpm-booking-widget'); if (!root || root.dataset.preview === '1') return;
    root.__vpmCouponInput = ''; const couponStatus = root.querySelector('.vpm-coupon-status'); if (couponStatus) couponStatus.textContent = '';
    const card = button.closest('.vpm-booking-offer'); selected.set(root, {offer: offers.get(card), card}); summary(root);
    root.querySelectorAll('.vpm-booking-choose').forEach(b => {const active = b === button; b.setAttribute('aria-pressed', active ? 'true' : 'false'); b.textContent = active ? text(root).selected : text(root).choose;});
    const form = root.querySelector('.vpm-booking-request'); form.hidden = false; form.querySelector('button[type="submit"]').disabled = false; form.querySelector('[name="email"]').focus();
    if(root.dataset.flow==='steps')wizardStep(root,3);
    analytics(root, 'vpm_booking_offer_select', root.querySelector('[name="airport"]').value);
  });
  document.addEventListener('input', event => {const root = event.target.closest('.vpm-booking-widget'); if (root && event.target.matches('[name="email"],[name="coupon"],.vpm-addon-quantity')) {root.__vpmCouponInput = ''; resetCoupon(root);}});
  document.addEventListener('change', event => {
    const root = event.target.closest('.vpm-booking-widget'); if (!root || root.dataset.preview === '1') return;
    if (event.target.matches('.vpm-parking-filter')) {
      root.querySelectorAll('.vpm-booking-offer').forEach(card => {card.hidden = !!event.target.value && card.dataset.parkingType !== event.target.value;});
      selected.delete(root); root.querySelector('.vpm-booking-request').hidden = true; root.querySelectorAll('.vpm-booking-choose').forEach(b => {b.setAttribute('aria-pressed', 'false'); b.textContent = text(root).choose;}); return;
    }
    if (event.target.matches('.vpm-addon,.vpm-addon-quantity')) {
      root.__vpmCouponInput = ''; resetCoupon(root);
      const card = event.target.closest('.vpm-booking-offer'), offer = offers.get(card), row = event.target.closest('.vpm-addon-row');
      row.querySelector('.vpm-addon-quantity').disabled = !row.querySelector('.vpm-addon').checked;
      const chosen = collect(card); card.querySelector('.vpm-offer-total').textContent = (root.dataset.locale === 'en' ? 'Total: ' : 'Gesamt: ') + money(root, total(offer, chosen), offer.currency); summary(root); return;
    }
    if (!event.target.closest('.vpm-booking-search')) return;
    clearOffers(root); const group = event.target.closest('div')?.querySelector('.vpm-time-offset');
    if (event.target.name === 'airport') root.querySelectorAll('.vpm-time-offset').forEach(node => {node.hidden = true; node.querySelector('select').required = false; node.querySelector('select').value = '';});
    if (event.target.type === 'datetime-local' && group) {group.hidden = true; group.querySelector('select').required = false; group.querySelector('select').value = '';}
  });
  const showPrice = (root, result) => {
    summary(root); const box = root.querySelector('.vpm-booking-summary');
    if(!result.discount_cents){box.querySelector('.vpm-booking-total').textContent=(root.dataset.locale==='en'?'Total: ':'Gesamt: ')+money(root,result.amount_cents,result.currency);return;}
    if(result.discount_cents){const p=document.createElement('p');p.className='vpm-coupon-discount';p.textContent=(root.dataset.locale==='en'?'Discount: −':'Rabatt: −')+money(root,result.discount_cents,result.currency);box.append(p);}
    const p=document.createElement('p');p.className='vpm-booking-total';p.textContent=(root.dataset.locale==='en'?'Total: ':'Gesamt: ')+money(root,result.amount_cents,result.currency);box.append(p);
  };
  const wizardStep = (root, step) => {
    root.__wizardRevision=(root.__wizardRevision||0)+1;root.dataset.step=String(step); const labels=root.dataset.locale==='en'?['Travel dates','Parking offer','Optional extras','Your details','Review','Enquiry / test payment']:['Reisedaten','Parkplatzangebot','Optionale Extras','Ihre Angaben','Preisübersicht','Anfrage / Testzahlung'];
    const heading=root.querySelector('.vpm-wizard-heading');if(heading)heading.textContent=step+'. '+labels[step-1];
    root.querySelector('.vpm-booking-search').hidden=step!==1;root.querySelector('.vpm-booking-offers').hidden=![2,3].includes(step);
    root.querySelector('.vpm-booking-filters').hidden=step!==2||root.querySelector('.vpm-parking-filter').options.length<3;
    const chosen=selected.get(root);root.querySelectorAll('.vpm-booking-offer').forEach(card=>{card.hidden=step===3&&card!==chosen?.card;card.querySelectorAll('.vpm-booking-addons').forEach(a=>a.hidden=step!==3);card.querySelector('.vpm-booking-choose').hidden=step!==2;});
    const form=root.querySelector('.vpm-booking-request');form.hidden=![4,5].includes(step);form.querySelector('.vpm-booking-customer-fields').hidden=step!==4;form.querySelector('.vpm-booking-summary').hidden=step!==5;form.querySelector('button[type="submit"]').hidden=step!==5;
    root.querySelector('.vpm-wizard-back').hidden=step===1||step===6;root.querySelector('.vpm-wizard-next').hidden=![3,4].includes(step);root.querySelector('.vpm-wizard-demo').hidden=step!==6||root.dataset.demoReady!=='1';
    if(step===4)form.querySelector('[name="email"]').focus();
  };
  const wizard = root => {
    if(root.dataset.flow!=='steps')return;
    const heading=document.createElement('h2');heading.className='vpm-wizard-heading';heading.setAttribute('aria-live','polite');root.prepend(heading);
    const nav=document.createElement('div');nav.className='vpm-wizard-nav';
    const back=document.createElement('button');back.type='button';back.className='vpm-wizard-back';back.textContent=root.dataset.locale==='en'?'Back':'Zurück';
    const next=document.createElement('button');next.type='button';next.className='vpm-wizard-next';next.textContent=root.dataset.locale==='en'?'Continue':'Weiter';
    const demo=document.createElement('button');demo.type='button';demo.className='vpm-wizard-demo';demo.textContent=root.dataset.locale==='en'?'Simulate test payment (no charge)':'Testzahlung simulieren (keine Belastung)';nav.append(back,next,demo);root.append(nav);
    back.addEventListener('click',()=>wizardStep(root,Math.max(1,Number(root.dataset.step)-1)));
    next.addEventListener('click',async()=>{
      if(root.dataset.step==='3'){wizardStep(root,4);return;}
      const form=root.querySelector('.vpm-booking-request'),selection=selected.get(root);if(!form.elements.email.reportValidity()||!selection)return;next.disabled=true;
      try{const data={quote_id:selection.offer.quote_id,addons:collect(selection.card,true),airport_scope:root.querySelector('[name="airport_scope"]')?.value||'',email:form.elements.email.value,coupon:form.elements.coupon.value,locale:root.dataset.locale};const revision=root.__wizardRevision,fingerprint=JSON.stringify(data);root.__vpmCouponInput=fingerprint;const result=await post(root,'public/price',data);if(root.__wizardRevision!==revision||root.__vpmCouponInput!==fingerprint)return;showPrice(root,result);status(root,'');wizardStep(root,5);}catch(error){handleError(root,error);}finally{next.disabled=false;}
    });
    demo.addEventListener('click',async()=>{demo.disabled=true;try{const result=await post(root,'demo/complete',{reference:root.__demoReference});status(root,result.simulation?(root.dataset.locale==='en'?'Demo completed. No real booking or payment.':'Demo abgeschlossen. Keine echte Buchung oder Zahlung.'):'');}catch(error){handleError(root,error);}finally{demo.disabled=false;}});
    wizardStep(root,1);
  };
  const view = root => {
    if (!viewed.has(root) && root.getBoundingClientRect().bottom > 0 && root.getBoundingClientRect().top < innerHeight && statisticsConsent()) {analytics(root, 'vpm_booking_view'); viewed.add(root);}
  };
  const init = () => document.querySelectorAll('.vpm-booking-surface').forEach(root => {
    if (mounted.has(root)) return; mounted.add(root); wizard(root);
    if (root.dataset.preview !== '1') {root.querySelector('.vpm-airport-form button')?.removeAttribute('disabled'); if (root.dataset.enabled === '1') root.querySelector('.vpm-booking-search button')?.removeAttribute('disabled'); view(root);}
  });
  init(); let scheduled = false;
  new MutationObserver(() => {if (!scheduled) {scheduled = true; requestAnimationFrame(() => {scheduled = false; init();});}}).observe(document.body, {childList: true, subtree: true});
  document.addEventListener('cmplz_status_change', () => document.querySelectorAll('.vpm-booking-widget').forEach(view));
})();
