# Buchungskern 0.6.0: Partnerportal, Anbindung und mehrstufige Demo

Entwicklungsstand vom 8. November 2026. Eigenes WordPress-Plugin, getrennt vom bestehenden Theme. Keine Installation auf Hostinger, keine DNS-/Subdomainänderung und keine Live-Zahlungen durch diese Veröffentlichung. [Stand 0.5.0](BOOKING-VOUCHERS-0.5.0.md) bleibt für Gutscheine/Newsletter maßgeblich.

## Partnerportal: sinnvoller gemeinsamer Backend-Bereich

Adresse auf der zukünftigen Installation: `/?vpm_partner_portal=1`. Zugang über die normale WordPress-Anmeldung. Der Bereich rendert eine eigene Oberfläche, ohne Theme-Header, Werbeskripte, Analytics oder externe Fonts. Private/no-store-Antworten, noindex/no-referrer, CSP und Schutz gegen fremde Einbettung. Alle Schreibformulare haben benutzergebundene WordPress-Nonces. Jeder Zugriff prüft die Partnerzuordnung auf dem Server, unabhängig von URL und ausgeblendeten Bedienelementen.

Eine getrennte Subdomain kann später als Hostingprojekt eingerichtet werden. Eine bloße DNS-Zuordnung ersetzt jedoch weder die Mandantenprüfung noch eine korrekt konfigurierte Anmeldung. WordPress-Canonical-URLs, REST-Ursprung, HTTPS und Cookies müssen zusammenpassen. Diese Version ist für denselben Ursprung wie WordPress geprüft, **nicht** für eine bereits funktionierende zweite Subdomain. Keine pauschale Freigabe von Cookies für alle Subdomains. Für den Produktivbetrieb sind zusätzliche Anmeldesicherung/MFA, Rate-Limits, Rollenprüfung und Hostinger-Test notwendig.

### Rollen und Einrichtungshilfe

Unter Werkzeuge → VPM Buchungsverwaltung ordnet die Plattform vorhandene WordPress-Benutzer einem Partner zu. Neue Rollen `VPM Partner` und `VPM Einrichtungsteam` haben lediglich die normale WordPress-Leseberechtigung, keine allgemeinen Verwaltungsrechte.

| Zuordnung | Rechte im zugeordneten Partner |
| --- | --- |
| owner | Buchungen und eigene Kennzahlen lesen; Verbindung, Angebote und Tarife bearbeiten; Stornoabläufe anfordern |
| manager | gleiche operative Bearbeitung im zugeordneten Partner |
| viewer | ausschließlich lesen |
| support | Einrichtung und operative Bearbeitung nur bei ausdrücklich zugeordneten Partnern |
| Plattformadministrator | alle Partner, Gesamtkennzahlen, Provisionen, Gutscheine und Zugangszuordnung |

Ein Partnerbenutzer kann weder sich selbst weitere Partner zuweisen noch globale Buchungs-/Gutscheinrouten benutzen. Mehrere zugeordnete Partner erscheinen in einer Auswahl. Zugangsänderungen wirken bei der nächsten Serverprüfung. Wer einem Partner volle WordPress-Administratorrechte gibt, gewährt ihm naturgemäß Plattformrechte; diese Rolle deshalb nicht für Partner verwenden. Teamzuordnung/Einladungen sind bislang Plattformaufgabe, keine freie Registrierung oder selbstständige Einladung weiterer Mitarbeiter.

Der Partner kann **Einrichtung durch das Plattformteam anfragen**. Die Plattform sieht den Wunsch und kann einem eigenen Mitarbeiter eine Support-Zuordnung geben. Eine Einrichtungsgebühr kann separat vereinbart werden. Automatische Gebührenberechnung, Rechnungsstellung, Zahlung oder ein SaaS-Abonnement sind noch nicht implementiert.

### Was der Partner verwaltet

- Eigene Anfragen/Buchungen und erfasste Zahlungscheckout-Status, paginiert zu 25 Einträgen.
- Operativ erforderliche E-Mail, Reisedaten und gebuchte Extras eigener Buchungen. Keine Zahlungs-Session-URLs, Plattformprovisionen, fremden Kontaktdaten oder Newsletterlisten.
- Eigene Angebotsvorlagen mit Stellplatzart, Merkmalen und separat bepreisten Extras.
- Eigene manuelle Tarife: Preis für Aufenthalt oder je angefangene 24 Stunden, Kapazität und bewusste Freigabe.
- Wahl zwischen pausiert, manueller Eigenverwaltung und installiertem Systemadapter.
- API-Schlüssel ersetzen/löschen; verschlüsselte Speicherung, keine Rückanzeige. Ein gespeicherter Schlüssel ist noch keine bestätigte funktionierende Verbindung.
- Klärungsfälle einsehen und nach Behebung erneut prüfen lassen. Stornoabläufe anfordern; bei verwalteten Buchungen erst Partnerbestätigung verarbeiten, dann stornieren und gegebenenfalls Erstattung anfordern.

Die Plattform muss neue Partner weiterhin freigeben, Provision und Stripe-Zuordnung festlegen. Partner können ihre eigene Provision oder Plattformgutscheine nicht verändern. Das Portal ist eine belastbar getestete Grundlage, noch kein vollständiges Parkplatz-Betriebssystem mit Fahrzeugannahme, Kennzeichen-/Flugdatenprozess, Schichtplanung oder Saisonkalender.

## Manuelle Preise und Kapazitäten

Jede Angebotsvorlage bildet zunächst einen **eigenständigen Stellplatzpool**. Ein Außenstellplatz- und ein Tiefgaragenprodukt können getrennte Kapazitäten haben. Dieselben physischen Stellplätze nicht zusätzlich mehreren unabhängigen Pools oder einem externen Verkaufssystem zuordnen. Gemeinsame Pools, zeitabhängige Preise, Sperrtage, Auf-/Abbaupuffer und eigene Zusatzleistungs-Kapazitäten sind noch nicht enthalten.

Verfügbare Plätze werden aus überlappenden belegten Zeiträumen berechnet; angrenzende Zeiträume ohne Überlappung sind zulässig. Bei der Anfrage wird unter Datenbanksperre erneut geprüft und eine interne kurzfristige Belegung gespeichert. Zwei gleichzeitige Anfragen auf den letzten Platz können nicht beide erfolgreich sein; der Test nutzt zwei unabhängige Datenbankverbindungen. Eine Vorschau ist noch keine Belegung.

Die interne vorläufige Belegung läuft nach zehn Minuten aus, ohne öffentliches Zeitversprechen. Nach bestätigter Zahlung wird eine Belegung nur dann verbindlich bestätigt, wenn der Nachweis noch gültig ist. Fehlt er, entsteht ein Klärungsfall – keine erfundene Buchungsbestätigung. Bestätigte Belegungen bleiben für den Reisezeitraum bestehen. Bestätigter Storno gibt sie frei. Ein Wechsel von Betriebsart/Adapter wird bei laufenden verwalteten Reisen blockiert, damit bestehende Anbindungen nicht unbemerkt verschwinden.

Kapazitätsreduktion erfolgt zunächst konservativ anhand aller aktiven zukünftigen Belegungen, nicht einer grafischen Spitzenlastberechnung. Eine unveränderte Kapazität lässt weiterhin Preisänderungen/Pausierung zu. Bei externen Verkäufen ist das führende Partnersystem anzubinden; die manuelle Eigenverwaltung kann fremde Buchungen nicht automatisch kennen.

## Einheitliche Adapterschnittstelle

`VPM\Booking\PartnerAdapter` definiert `offers`, `hold`, `confirm`, `cancel`, `change` und `status`. Registrierung durch vertrauenswürdigen Servercode über `vpm_booking_adapters`, beispielsweise Adapter-ID `anbieter_system`. Es gibt kein frei eingebbares HTTP-Ziel, das Partner als allgemeine Anfrageweiterleitung missbrauchen könnten.

- `offers(partner, context, credentials)`: Angebote im bisherigen Preis-/Produktformat. Ein Ausfall liefert keine erfundenen Angebote und wird als generischer Verbindungsfehler dokumentiert.
- `hold(booking, credentials, idempotency_key)`: dauerhafter Rückgabewert mit `reference` und zukünftiger `expires_at` (Integer, maximal 24 Stunden). Der Nachweis wird verschlüsselt gespeichert. Erst danach ist ein administrativer Stripe-Sandbox-Checkout grundsätzlich zulässig.
- `confirm(...)`: nach verifiziertem Zahlungsstatus; `acknowledged: true` erforderlich. Erst danach wird die Buchung bestätigt und ein entsprechendes E-Mail-Ereignis erzeugt.
- `cancel(...)`: Bestätigung des Partners erforderlich, bevor das System den Storno und eine gegebenenfalls erforderliche Erstattung ausführt.
- `change(...)` und `status(...)`: Vertrag vorbereitet; eine vollständige Änderungs-/Neupreis- und periodische Statusabgleich-Oberfläche ist noch nicht implementiert.

Hold-/Buchungsreferenz wird beim Folgeaufruf in `booking.partner_hold` mitgegeben. Adapter bekommen nur serverseitig entschlüsselte Konfiguration. Sie müssen begrenzte HTTP-Zeiten und die übergebenen stabilen Idempotenzschlüssel beachten. Partner-Webhookauthentifizierung und konkrete API-/Feldzuordnung bleiben herstellerspezifische Integrationsarbeit. Es wird kein fertig funktionierender Herstelleradapter mitgeliefert; die Vertragsprüfung verwendet ausschließlich einen lokalen Testtreiber.

Die Warteschlange hat Wiederholungen, begrenzte Versuche, sichtbare Fehler und Aufgabenzuordnung. Pro Buchung verhindert eine zeitlich begrenzte Worker-Sperre parallele Partneroperationen; eine abgelaufene Worker-Sperre ersetzt nicht die verpflichtende Idempotenz im Partnersystem. Fehlertexte werden auf bekannte Codes begrenzt, keine API-Schlüssel aus Herstellerfehlern in das Portal übernommen. Nach Klärung kann ein berechtigter Benutzer erneut prüfen lassen.

Bei dauerhaftem Problem entsteht `operations.attention_required` in der verschlüsselten Outbox. Der Empfänger/Alarmkanal ist später im Automatisierungsdienst zuzuordnen; noch keine garantierte Benachrichtigung. Keine blinde automatische Rückzahlung bei unbekanntem Partnerzustand. Der Betreiber muss den Fall prüfen und einen belegten Storno-/Erstattungsweg wählen.

## Plattformübersicht und Kennzahlen

Plattformadministrator sieht global und je Partner Anfragen, Kunden-E-Mails, gestartete Zahlungscheckouts, bezahlte Vorgänge sowie Provision und Rabattkosten aus dem Buchungsjournal. Erstattungsbuchungen werden berücksichtigt. Global zusätzlich bestätigte Newsletter-Abos. Eine Newsletter-Zuordnung zu einzelnen Partnern wird nicht erfunden.

Partner sehen eigene operative Kennzahlen und den Partneranspruch laut Journal. Das ist **kein** nachgewiesener Bank-/Stripe-Auszahlungsabgleich. Plattformprovision abzüglich Rabatt ist **vor Zahlungsgebühren, Steuern und weiteren Betriebskosten** ausgewiesen.

Die Conversion-Kennzahl ist ausdrücklich der Zahlungserfolg innerhalb erfasster Stripe-Checkouts, inklusive später erstatteter ursprünglich erfolgreicher Zahlungen. Ohne Nenner steht „Noch keine Daten“. Keine Besucher-Conversion und kein vollständiger Verlauf anonymer abgebrochener Warenkörbe; dafür bleibt eine korrekt konfigurierte zustimmungsabhängige Analytics-Anbindung erforderlich. Demos werden aus den operativen Kennzahlen ausgeschlossen.

## Mehrstufiger Ablauf

```text
[vpm_booking_form airport="STR" flow="steps"]
[vpm_booking_form flow="steps"]
```

Gutenberg besitzt dafür die Einstellung „Mehrstufiger Demo-Ablauf“, Elementor nutzt den Shortcode. Bestehende Einbettungen ohne `flow` behalten zunächst das bisherige Anfrageformular (`inline`). Die Schritte:

1. Reisedaten.
2. Parkplatzangebot; Extras noch nicht in dieser Auswahl anzeigen.
3. Separater Extras-Schritt, keine Vorauswahl.
4. E-Mail und optionaler Gutschein.
5. Erneut serverseitig geprüfte Preisübersicht inklusive Extras/Rabatt.
6. Anfragebestätigung beziehungsweise ausdrücklich gekennzeichnete Testsimulation.

Zurücknavigation erhält die Auswahl im aktuellen Tab. Enter im Dateneingabeschritt darf die Preisübersicht nicht überspringen. Veraltete Preisantworten dürfen bei zwischenzeitlichen Eingaben/Schrittwechseln keine falsche Übersicht überschreiben. Noch kein dauerhaft gespeicherter Warenkorb, Fortsetzungslink für abgebrochene Checkouts oder Abbruchmail.

### Sichere Demonstration ohne echte Zahlung

Nur auf einer **getrennten Testinstallation** kann serverseitig `VPM_BOOKING_DEMO_MODE` auf `true` gesetzt werden. Ein angemeldeter Plattformadministrator erhält dann nach Anfrageabschluss den Button „Testzahlung simulieren (keine Belastung)“. Ohne diese Konstante oder ohne passende Anmeldung wird keine Simulation freigeschaltet. Kein Schlüssel und keine Aktivierung über öffentliche Formulare.

Die Simulation erzeugt keinen Stripe-Zahlungsstatus, keine Geldbuchung und keine gewöhnlichen Buchungs-E-Mails. Sie ist in einer eigenen Tabelle markiert und aus Geschäftskennzahlen ausgeschlossen. Die Quittung sagt ausdrücklich „Keine echte Buchung oder Zahlung“. Eine echte Stripe-Sandbox-Zahlung bleibt separat über die geschützten Backend-Routen möglich; ein freigegebener öffentlicher PSP-Zahlungscheckout ist noch nicht enthalten. Simulation deshalb nicht als erfolgreichen Stripe-End-to-End-Test oder Partnerbuchung verkaufen.

## Automatische Tests und Betrieb

[GitHub-Workflow](../.github/workflows/booking-core.yml): PHP-/Finanz-/Gutschein-/Adapter-/Mandantentests in frischem WordPress mit ausschließlich dem eigenen Plugin. Kein Rollout alter lokaler Fremdpluginfixtures. Lokal derselbe Aufbau mit `bash scripts/check-booking-ci.sh`, eigene kurzlebige Container und Datenbank; vorhandene gleichnamige Container werden nicht überschrieben. GitHub-Ausführungsstatus ist gesondert zu prüfen.

Der isolierte Aufbau testet auch SQL-Backup → Löschen der Testdaten → Wiederherstellung → identischen finanziellen Snapshot/Journal. Testbackup wird privat und nur vorübergehend gespeichert, nicht als GitHub-Artefakt veröffentlicht. Kein Hostinger-Wiederherstellungstest und kein vollständiges Produktions-Backupkonzept. Betriebsanleitung: [Runbook](BOOKING-BETRIEB.md).

Lokaler vollständiger Test im bestehenden Loopback-WordPress (eigene Buchungstabellen müssen leer sein):

```bash
VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-platform-local.sh
```

Berichte: [Plattform/Mandanten](BOOKING-PLATFORM-PRUEFUNG.json), [Adapter/Fehlerabläufe](BOOKING-ADAPTERS-PRUEFUNG.json), [Portal/Demo im Browser](BOOKING-PORTAL-BROWSER-PRUEFUNG.json), [isoliertes Backup](BOOKING-BACKUP-PRUEFUNG.json). Zusätzlich paralleler Kapazitätstest und bestehende Prüfungen. Screenshots zeigen nur Testdaten: [Portal](../zwischenstand/buchungskern/partnerportal-test.png), [Preisübersicht](../zwischenstand/buchungskern/checkout-schritt-preisuebersicht-test.png).

## Vor einem echten Buchungsstart noch erforderlich

Konkreter Partneradapter oder vollständig abgestimmter manueller Betrieb, belastbare Kapazitäts-/Änderungs-/Stornoregeln, finaler Kunden-/Fahrzeug-/Kontaktprozess, Zahlungs-/Vertragsrolle, Stripe-Connect-Onboarding, echte E-Mail-Zustellung, Rechtstexte/Datenschutzfristen, Anmeldesicherung, Hosting-/Cron-/Lasttest und Wiederherstellung auf dem tatsächlichen Hosting. Keine freie Partnerregistrierung, automatische Einrichtungskostenabrechnung oder fertiges SaaS-Abo. Portal aktuell deutschsprachig; keine vollständige Partnerportal-Übersetzung vorbereitet.
