# Buchungskern 0.5.0: Gutscheinprüfung und optionale Treueaktion

Stand 8. November 2026. Separates Entwicklungsmodul für die Dachmarke, kein automatisch installiertes Hostinger-Update. Bestehender Umfang: [0.4.0](BOOKING-PRODUCTS-0.4.0.md). Stripe bleibt Sandbox; kein öffentlicher Live-Bezahlcheckout oder echter Partneradapter hinzugefügt.

## Eingabe und Preisübersicht

Im Anfrageformular steht beim Gutscheincode jetzt **Gutschein prüfen**. E-Mail und gewähltes Angebot einschließlich Extras werden serverseitig geprüft. Danach zeigt die Übersicht den Ausgangsbetrag, Rabatt und Gesamtbetrag nach Rabatt. Diese Vorprüfung verbraucht oder reserviert den Gutschein nicht. Ändert der Besucher E-Mail, Code oder Zusatzleistungen, verschwindet die alte Rabattvorschau. Beim tatsächlichen Absenden erfolgt die komplette Prüfung erneut unter Datenbanksperre.

Neue öffentliche JSON-POST-Route `/wp-json/vpm-booking/v1/public/price`: dieselben Angebots-/Flughafen-/Extra-Angaben wie bei der Anfrage, dazu E-Mail und Gutschein. Antwort ausschließlich Ausgangsbetrag, Rabatt, Endbetrag und EUR-Währung. Keine Partnerkonten oder Provisionen, private/no-store-Antworten und bestehendes Rate-Limit. Serverpreise sind maßgeblich, nie vom Browser übermittelte Rabattbeträge.

Fehlermeldungen DE/EN, unter anderem:

- `coupon_expired`: „Leider ist der Gutschein bereits abgelaufen.“
- `coupon_redeemed`: „Leider ist der Gutschein bereits eingelöst.“
- `coupon_in_progress`: „Dieser Gutschein wird bereits für eine andere Anfrage verwendet.“
- `coupon_unknown`: „Dieser Gutscheincode ist nicht bekannt.“
- `coupon_not_started`: „Dieser Gutschein ist noch nicht gültig.“
- `coupon_wrong_customer`: „Dieser Gutschein gehört zu einer anderen E-Mail-Adresse.“

Zuerst wird der Zeitraum geprüft, danach persönliche Zuordnung, Angebot und Einlösungen. Ein bereits eingelöster und inzwischen auch abgelaufener Code wird deshalb als abgelaufen gemeldet. Ablauf ist sekundengenau exklusiv: ab `ends_at` ungültig.

Eine offene Anfrage hält die Gutscheinnutzung vorläufig; erst der bestätigte Zahlungsablauf verbraucht sie. Abbruch/Storno beziehungsweise das verarbeitete Ablaufen einer Checkout-Sitzung können die noch nicht verbrauchte Nutzung freigeben. Für bloß offene Anfragen ohne Checkout gibt es bislang keine automatische Freigabefrist; diese müssen noch operativ geklärt werden. Das ist **keine Stellplatzreservierung** und es wird dafür kein Zeitversprechen angezeigt. Bereits verbrauchte Gutscheine werden bei Erstattung nicht automatisch erneut ausgestellt.

## Rabatt zulasten der Plattform

Das bestehende Modell bleibt unverändert: Partneranteil vor Gutschein bleibt erhalten, der Rabatt reduziert ausschließlich die Plattformprovision.

Technisches Beispiel, kein echtes Angebot: 100 € Gesamtbetrag, 20 € Provision, 5 € Gutschein → Kunde 95 €, Partner 80 €, Plattform 15 € vor Zahlungsgebühren. Reicht die Provision für einen Gutschein nicht aus, wird er für dieses Angebot zurückgewiesen; kein negativer Plattformanteil und kein stiller Abzug beim Partner. Zahlungsgebühren und tatsächliche Stripe-Auszahlungen sind damit nicht als live abgeglichen bestätigt.

Die REST-Ausgabe von Gutscheinen verlangt jetzt ausdrücklich `manage_options`. Die allgemeine Buchungsberechtigung `manage_vpm_bookings` reicht dafür nicht mehr. Verwaltungsformulare bleiben ebenfalls Administratoren vorbehalten. Kein Gutscheinportal für Partner. Wer einem Partner volle WordPress-Administratorrechte gibt, gewährt ihm naturgemäß weiterhin Plattformadministration; das ist keine geeignete Partnerrolle.

## Optionale persönliche Treueaktion

Unter **Werkzeuge → VPM Buchungsverwaltung → Optionale Treueaktion**. Standardmäßig deaktiviert, bewusste Aktivierung durch Plattformadministrator erforderlich. Abschalten stoppt weitere Vergaben; vorhandene Codes bleiben gültig. Erneutes Aktivieren setzt einen neuen Aktivierungszeitpunkt, erzeugt aber keinen zweiten Code für bereits bedachte Kontakte. Die vorbereitete Regel:

1. Newsletter-Anmeldung mit bestätigtem Double-Opt-in.
2. Erste bezahlte, operativ bestätigte Buchung derselben normalisierten E-Mail-Adresse; vorangegangene bezahlte/refundierte Buchungen werden berücksichtigt.
3. Erste Buchung muss ab Aktivierungszeitpunkt angelegt worden sein; keine automatische Massenvergabe für historische Erstbuchungen.
4. Einmaliger persönlicher Code für **5 % auf eine weitere Buchung**.
5. Gültig **zwei Kalenderjahre ab Ausstellung**, berechnet mit Kalenderintervall in UTC, nicht pauschal 730 Tage. Die technische Datumsnormalisierung gilt auch für Schalttage.
6. Einmalige Einlösung; Code ist an die E-Mail-Adresse gebunden. Keine Kopplung an einen später weiterbestehenden Newsletter: Abmeldung allein entzieht einen bereits ausgestellten Vorteil nicht.

Damit ist ein unbenutzter Code nach 1,5 Jahren weiterhin gültig; nach 2,5 Jahren abgelaufen. Eine erfolgreiche Einlösung während der Gültigkeit verhindert eine weitere Einlösung. Maßgeblich ist die normalisierte E-Mail, keine verifizierte natürliche Person: Alias-Adressen oder neue Identitäten werden nicht umfassend erkannt.

Technische Erstellung erfolgt durch den vorhandenen Worker, in begrenzten Durchläufen von 20 bestätigten Kontakten mit fortgesetztem Cursor. Vergabe kann deshalb verzögert erfolgen. Wiederholung erzeugt dank Sperren und eindeutiger Vergaberegel keinen zweiten Code. Nicht allein bei Newsletter-Anmeldung oder unbezahlter Anfrage vergeben. Zum Zeitpunkt der Vergabe bereits stornierte oder erstattete Erstbuchungen qualifizieren nicht. Spätere Stornos nach erfolgter Vergabe lösen noch keinen automatischen Entzug aus; eine Rückabwicklungsregel ist vor Livebetrieb separat festzulegen.

Versandereignis **`loyalty.coupon_issued`**, Zweck `marketing`, verschlüsselte Outbox, mit:

- `recipient`, `coupon_code`, `discount_bps: 500`, `expires_at`
- `consent_revision`, `consent_version`, `unsubscribe_url`

Bestehende Versandprüfung unterdrückt Marketing ohne weiterhin gültige Newsletter-Zustimmung. Mailvorlage/Versanddienst kommen später; ein Ereignis in der Warteschlange ist noch keine zugestellte Mail. Keine Abbruch-Erinnerungsmails neu eingebaut oder aktiviert. Reservierungscountdown und Aussage „Parkplatz 30 Minuten reserviert“ werden nicht angezeigt; eine tatsächliche Partnerreservierung benötigt weiterhin einen konkreten Adapter.

Schema 5 ergänzt persönliche Gutscheinzuteilung und einmalige Treuevergaben; bestehende Gutscheine erhalten keine persönliche Beschränkung. Persönliche Gutscheine werden in der geschützten WordPress-Datenauskunft berücksichtigt. Keine neue automatische Löschung von Finanz-/Einwilligungsnachweisen.

## Prüfung und Paket

Nur lokale Loopback-Installation mit leeren eigenen Buchungstabellen:

```bash
VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-vouchers-local.sh
```

Enthält bisherige Prüfungen sowie neue [Gutschein-/Treueprüfungen](BOOKING-VOUCHERS-PRUEFUNG.json) und [Browserprüfungen](BOOKING-VOUCHERS-BROWSER-PRUEFUNG.json). Zeitgrenzen werden mit einer Testuhr geprüft; keine echten Jahre abgewartet, keine realen Zahlungen/Mails ausgelöst. [Vorschau mit Testdaten](../zwischenstand/buchungskern/gutschein-preisuebersicht-test.png).

Installierbare ZIP: `pakete/vpm-booking-core.zip`, Version 0.5.0. Vor Austausch sichern und zunächst testen. Kein erneuter Münchner Inhaltsimport nötig.
