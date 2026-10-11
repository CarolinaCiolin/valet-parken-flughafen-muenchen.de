const form = document.querySelector('#inquiry');
const departure = form.elements.departure;
const arrival = form.elements.return;
const today = new Date();
const localDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
departure.min = localDate;
arrival.min = localDate;
departure.addEventListener('change', () => {
  arrival.min = departure.value || localDate;
  arrival.setCustomValidity('');
});
arrival.addEventListener('input', () => arrival.setCustomValidity(''));
form.addEventListener('submit', (event) => {
  event.preventDefault();
  if (arrival.value < departure.value) {
    arrival.setCustomValidity('Die Rückkehr muss am oder nach dem Abreisetag liegen.');
    arrival.reportValidity();
    return;
  }
  const formatDate = (value) => new Intl.DateTimeFormat('de-DE').format(new Date(`${value}T12:00:00`));
  document.querySelector('#inquiry-text').value = `Guten Tag,\n\nich möchte Valet Parken am Flughafen München anfragen.\nAbreise: ${formatDate(departure.value)}\nRückkehr: ${formatDate(arrival.value)}\n${form.elements.message.value.trim()}\n\nBitte teilen Sie mir Verfügbarkeit, Gesamtpreis und die Übergabedetails mit.`;
  document.querySelector('#result').hidden = false;
  document.querySelector('#inquiry-text').focus();
});
