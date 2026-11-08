(() => {
  const root = document.querySelector('.vpm-customer'); if (!root) return;
  const en = root.dataset.locale === 'en', t = (de, english) => en ? english : de;
  const status = message => {root.querySelector('.vpm-customer-status').textContent = message;};
  let token = new URLSearchParams(location.hash.slice(1)).get('token') || '';
  // Keep the bearer token only in this tab's memory, never localStorage, cookies or analytics.
  if (location.hash) history.replaceState(null, '', location.pathname + location.search);
  const post = async (route, data) => {
    const endpoint = new URL(root.dataset.api, location.origin);
    if (endpoint.searchParams.has('rest_route')) endpoint.searchParams.set('rest_route', endpoint.searchParams.get('rest_route').replace(/\/$/, '') + '/' + route);
    else endpoint.pathname = endpoint.pathname.replace(/\/$/, '') + '/' + route;
    const r = await fetch(endpoint, {method: 'POST', credentials: 'omit', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)});
    const value = await r.json(); if (!r.ok) throw value; return value;
  };
  const fail = () => status(t('Der Zugang ist abgelaufen oder derzeit nicht verfügbar. Bitte fordern Sie bei Bedarf einen neuen Link an.', 'Access has expired or is unavailable. You can request a new link below.'));
  const labels = {
    requested: ['angefragt', 'requested'], confirmed: ['operativ bestätigt', 'operationally confirmed'], cancelled: ['storniert', 'cancelled'],
    unpaid: ['unbezahlt', 'unpaid'], creating: ['Zahlung wird vorbereitet', 'payment being prepared'], pending: ['ausstehend', 'pending'], paid: ['bezahlt', 'paid'], expired: ['abgelaufen', 'expired'], partially_refunded: ['teilweise erstattet', 'partially refunded'], refunded: ['erstattet', 'refunded'], reviewed: ['geprüft', 'reviewed'], completed: ['bearbeitet', 'completed']
  };
  const state = value => labels[value] ? t(...labels[value]) : value;
  const render = value => {
    root.querySelector('.vpm-customer-inquiry').hidden=false;
    const details = root.querySelector('.vpm-customer-details'); details.replaceChildren(); details.hidden = false;
    const row = (label, text) => {const p = document.createElement('p'), strong = document.createElement('strong'); strong.textContent = label + ': '; p.append(strong, document.createTextNode(text)); details.append(p);};
    const money = cents => new Intl.NumberFormat(en ? 'en-GB' : 'de-DE', {style: 'currency', currency: value.currency.toUpperCase()}).format(cents / 100);
    root.querySelector('.vpm-customer-access [name="reference"]').value = value.reference;
    row(t('Referenz', 'Reference'), value.reference); row(t('Flughafen', 'Airport'), value.airport);
    row(t('Anfragestatus', 'Enquiry status'), state(value.state)); row(t('Zahlungsstatus', 'Payment status'), state(value.payment_state));
    row(t('Betrag', 'Amount'), money(value.amount_cents)); if (value.discount_cents > 0) row(t('Gutscheinrabatt', 'Voucher discount'), '−' + money(value.discount_cents)); row(t('Erstattet', 'Refunded'), money(value.refunded_cents));
    if (value.travel?.timezone) {
      const format = timestamp => new Intl.DateTimeFormat(en ? 'en-GB' : 'de-DE', {dateStyle: 'medium', timeStyle: 'short', timeZone: value.travel.timezone}).format(timestamp * 1000);
      row(t('Abgabe', 'Drop-off'), format(value.travel.departure_utc)); row(t('Rückgabe', 'Collection'), format(value.travel.return_utc)); row(t('Zeitzone', 'Timezone'), value.travel.timezone);
    }
    if (value.offer_selection?.addons) { value.offer_selection.addons.forEach(item => row(item.quantity + ' × ' + item.label, money(item.amount_cents))); }
    const service = value.partner_service || {};
    if (service.partner_name) row(t('Ihr ausführender Partner', 'Your service provider'), service.partner_name);
    if (service.support_phone) {const p=document.createElement('p'),a=document.createElement('a');a.href='tel:'+service.support_phone.replace(/[^+0-9]/g,'');a.textContent=service.support_phone;p.append(document.createTextNode(t('Fragen, Rückmeldungen oder Rechnung: ', 'Questions, feedback or invoices: ')),a);details.append(p);}
    const pointLabels={parking:t('Parkplatzadresse','Parking address'),handover:value.offer_selection?.service==='valet'?t('Schlüsselübergabe bei Abreise','Key handover at departure'):t('Anreise / Treffpunkt','Arrival / meeting point'),return:t('Fahrzeugrückgabe nach der Reise','Vehicle collection on return')};
    Object.entries(service.points || {}).forEach(([key, point])=>{
      if(!pointLabels[key])return;const section=document.createElement('section'),h=document.createElement('h2');h.textContent=pointLabels[key];section.append(h);
      const name=document.createElement('p');name.textContent=point['name_'+(en?'en':'de')]||point.name_de||'';section.append(name);
      const address=document.createElement('p');address.textContent=point.address||'';section.append(address);
      const note=document.createElement('p');note.className='vpm-handover-note';note.textContent=point['instructions_'+(en?'en':'de')]||point.instructions_de||'';section.append(note);
      if(point.directions_url && (()=>{try{const u=new URL(point.directions_url);return u.protocol==='https:'&&['www.google.com','google.com','www.google.de','google.de','maps.google.com','maps.app.goo.gl','goo.gl'].includes(u.hostname);}catch(_){return false;}})()){const link=document.createElement('a');link.href=point.directions_url;link.target='_blank';link.rel='noopener noreferrer';link.textContent=t('Route in Google Maps öffnen','Open route in Google Maps');section.append(link);}
      details.append(section);
    });
    if (value.offer_terms) row(t('Angebotshinweise', 'Offer information'), value.offer_terms);
    if(value.cancellation_assessment&&value.state!=='cancelled'){row(t('Stornogebühr bei jetzigem Antrag','Cancellation fee if requested now'),money(value.cancellation_assessment.fee_cents));row(t('Erstattungsziel nach bestätigtem Storno','Refund target after confirmed cancellation'),money(value.cancellation_assessment.refund_total_cents));}
    if (value.cancellation_request) row(t('Stornowunsch', 'Cancellation request'), state(value.cancellation_request));
    root.querySelector('.vpm-customer-cancel').hidden = value.state === 'cancelled' || !!value.cancellation_request;
    status(t('Status geladen. Dieser Zugang ist zeitlich begrenzt.', 'Status loaded. This access expires after a limited time.'));
  };
  if (/^[a-f0-9]{64}$/.test(token)) post('view', {token}).then(render).catch(fail);
  root.addEventListener('submit', async event => {
    event.preventDefault(); const form = event.target; if (!form.reportValidity()) return;
    const button = form.querySelector('button'); if (button.disabled) return; button.disabled = true;
    try {
      if (form.classList.contains('vpm-customer-access')) {
        await post('access', Object.fromEntries(new FormData(form))); form.reset();
        status(t('Falls die Angaben zugeordnet werden können und der Versand eingerichtet ist, erhalten Sie einen Zugangslink per E-Mail.', 'If the details match and delivery is configured, an access link will be sent by email.'));
      } else if(form.classList.contains('vpm-customer-inquiry')){form.__key=form.__key||crypto.randomUUID();await post('inquiry',{token,message:form.elements.message.value,request_key:form.__key});form.reset();form.__key='';status(t('Ihre Anfrage wurde für den Partner erfasst.','Your enquiry has been recorded for the provider.'));
      } else if (form.classList.contains('vpm-customer-cancel') && form.elements.acknowledged.checked) {
        await post('cancellation', {token}); form.hidden = true;
        status(t('Ihr Stornowunsch wurde zur Prüfung übermittelt. Das ist noch keine Stornobestätigung oder Erstattung.', 'Your cancellation request was submitted for review. This is not a cancellation confirmation or refund.'));
      }
    } catch (_) {fail();} finally {button.disabled = false;}
  });
})();
