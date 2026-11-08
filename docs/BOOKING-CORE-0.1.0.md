# VPM Buchungskern 0.1.0 – technischer Sandbox-Unterbau

Stand 8. November 2026. Separates WordPress-Plugin vpm-booking-core für die geplante Dachmarke. Nicht Bestandteil des Flughafenparken-Themes und nicht automatisch im allgemeinen Starter installiert. Bestehende Münchner Seiten und Anbieter werden nicht verändert. Keine echte Zahlung, E-Mail oder Kundenbuchung durch die Entwicklung/Tests ausgelöst.

## Tatsächlich implementiert

- Eigene leere InnoDB-Tabellen für Partner, Buchungen, Coupons/Reservierungen, Journal, Erstattungen, verarbeitete Ereignisse, verschlüsselte Versandqueue, Kontakte/Einwilligungsverlauf, Dokumentreferenzen und Zustellmeldungen.
- Interne Partnerkonfiguration: Flughafen-IATA, Stripe-Konto-ID und Provisionssatz in Basispunkten. Serverseitige Backend-Buchung mit unveränderlichem Preissnapshot und separatem operativem/Zahlungsstatus.
- Coupon-Gültigkeit, feste oder prozentuale Rabatte, Flughafen-/Partnerbindung, Budget, Gesamt-/Kundenlimit. Zeilenlocks und Transaktionen schützen die Einlösung. Geldwerte ausschließlich ganzzahlige Centbeträge.
- Stripe Hosted Checkout mit Connect-Destination und Application Fee als Plattformanteil nach eigenem Rabatt, ausschließlich Testschlüssel. Stabile Idempotenzschlüssel, validierte Test-Checkout-URL und Wiederabruf bestehender Session.
- Signierte Stripe-Webhooks (Rohkörper-HMAC, 5-Minuten-Zeitfenster), Betrags-/Währungs-/Referenzprüfung, Replay-Schutz, keine zweite Verbuchung bei doppelten/überlappenden Zahlungsereignissen. Nur livemode=false akzeptiert.
- Zahlungsjournal mit ausgeglichenen Buchungsgruppen: Kundenzahlung, Partneranspruch, Provision und eigene Rabattaufwendung. Voll-/Teilerstattungen mit kumulativer Centaufteilung und expliziter Transfer-/Gebühren-Rückabwicklungsanforderung an Stripe. Keine erfundenen PSP-Gebühren oder Banksalden.
- Bestätigte Stornierung stellt einen Erstattungsauftrag ein; Worker führt ihn aus. Temporäre Fehler werden mit demselben Schlüssel wiederholt, nach acht Versuchen steht attention statt stiller Erfolg. Bereits vorgemerkte Erstattungen blockieren erneutes Übererstatten.
- Ereignisse für E-Mail-Automatisierung einschließlich Bestätigung, Zahlungsbeleg, Rechnungsanforderung, Storno-/Korrekturbeleg und Dokumentbereitstellung. Signierter ausgehender HTTPS-Webhook, Wiederholungen, Worker-Lease, verschlüsselte Queue; 2xx-Annahme heißt dispatched, nicht E-Mail zugestellt.
- Newsletter-Anmeldung mit nicht vorgewählter Zustimmung, begrenzten Anfragen, individuellen Links, bestätigendem POST-Button, Widerruf und Einwilligungsverlauf. Reiner GET/Linkscan aktiviert nichts. Wartende Werbeereignisse werden nach Widerruf unterdrückt.
- Authentifizierte Verwaltungs-REST-Schnittstelle, separate signierte öffentliche Webhooks, technische Statusansicht unter Werkzeuge → VPM Buchungskern.

## Zahlungsbeispiel und Geschäftsannahmen

Bei 100 EUR Grundpreis und 20 EUR vereinbarter Provision bleiben dem Partner 80 EUR. Ein eigener Rabatt von 10 EUR reduziert Kundenzahlung auf 90 EUR und technische Application Fee auf 10 EUR. Das Journal hält ursprüngliche Provision 20 und Rabattaufwand 10 getrennt fest. PSP-Gebühren sind darin noch nicht bekannt; das Journal ist kein Abbild des verfügbaren Stripe-/Bankguthabens und keine fertige Finanzbuchhaltung.

Die erste Version lässt Rabatte oberhalb der Provision sowie Zahlungen unter 50 Cent nicht zu. Größere eigene Subventionen und kostenlose Buchungen benötigen einen eigenen finanzierten Ablauf. Verbrauchte Coupons werden bei Storno nicht automatisch erneut freigegeben; der finanzielle Rabattaufwand wird zwar rückgebucht, die konservativen Einlösungs-/Budgetzähler bleiben verbraucht. Reservierte, noch nicht bezahlte Codes werden bei bestätigtem Abbruch/Storno bzw. Sessionablauf freigegeben.

Operative Bestätigung kommt aktuell nur aus dem geschützten Backend. Eine Kunden-Buchungsbestätigung wird erst nach operativer Bestätigung UND signiert bestätigter Zahlung als Ereignis erzeugt. Aktuell vollständige Stornoerstattung als Testannahme; konkrete Regeln/Fristen/Gebühren und Partnerverträge müssen vor einem Produktivablauf feststehen. Partneransprüche bei bereits ausgezahltem Geld sind nicht durch Software garantiert einziehbar.

Ein nicht zuordenbarer manueller Stripe-Refund wird als reconciliation.required gemeldet, nicht mit geratenen Journalzeilen verarbeitet. Ein vollständiger externer Zahlungsabgleich, Auszahlungsschedules, Chargeback-Verarbeitung, Steuer-/Gebührenverbuchung und Nachforderung sind noch nicht implementiert.

## Stripe-Konfiguration – später sicher hinterlegen

Konstanten in sicherem wp-config.php oder gleichnamige Server-Umgebungsvariablen, niemals im Chat/Repository:

- VPM_BOOKING_STRIPE_TEST_SECRET: Stripe-Testschlüssel sk_test_… oder passender eingeschränkter Testschlüssel.
- VPM_BOOKING_STRIPE_WEBHOOK_SECRET: eigenes whsec_… für den Test-Endpunkt /wp-json/vpm-booking/v1/stripe/webhook.

API-Version im Adapter: 2024-06-20 als bewusst festes, noch gegen einen echten Stripe-Testaccount zu validierendes Vertragsformat. Zugriff auf aktuelle Herstellerdokumentation war aus der Cloud blockiert. Es wurden echte WordPress-HTTP-Aufrufe mit abgefangenen Testantworten geprüft, keine erfolgreiche Kommunikation mit api.stripe.com behauptet.

Benötigte Ereignisse: checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.expired, refund.created, refund.updated. Live-Schlüssel und Live-Ereignisse werden ausdrücklich abgelehnt. Connect-Konten werden noch nicht angelegt/onboarded. Konto-/Händlerrolle, Gebühren-/Dispute-Verantwortung und Auszahlungskontrolle vor einem zukünftigen Live-Modus bestätigen. Live-Modus wird nicht durch einen Einstellungswechsel dieser Version freigeschaltet.

Die Checkout-Rückkehr geht derzeit auf die Website mit einem neutralen Rückkehrparameter und verändert keinen Buchungsstatus. Eine sichere kundenbezogene Statusoberfläche und der eigentliche öffentliche Buchungsablauf sind noch zu entwickeln. Kundenpreise niemals ungeprüft in die Verwaltungs-API durchreichen: Betrag/Verfügbarkeit müssen aus einem zukünftigen serverseitigen Angebot/Partneradapter stammen.

## REST-Verwaltung

Namespace /wp-json/vpm-booking/v1. Nur manage_options oder die gezielt vergebene Plattform-Berechtigung manage_vpm_bookings. Keine Anbieter-/Kundenrollen und keine mandantenbeschränkten Partnerlogins implementiert. Diese zentrale Berechtigung nicht an Parkplatzpartner vergeben. WordPress-Cookieauth erfordert normalen REST-Nonce; externe vertrauenswürdige Integration später z. B. mit einem gesonderten HTTPS-Application-Password und minimaler Berechtigung, nicht mit einem veröffentlichten Adminzugang.

| Methode/Pfad | Funktion |
| --- | --- |
| POST /partners | airport, name, stripe_account, commission_bps |
| POST /coupons | code, kind=fixed/percent, value (Cent/Basispunkte), starts_at/ends_at (Unix UTC), max_uses, max_per_customer, budget_cents, optional airport/partner_id |
| POST /bookings | partner_id, email, base_cents, optional coupon, locale=de/en; Idempotency-Key erforderlich |
| GET /bookings/{uuid} | geschützte Buchungsdaten |
| POST /bookings/{uuid}/confirm | operative Bestätigung aus vertrauenswürdigem Backend |
| POST /bookings/{uuid}/checkout | Stripe-Test-Checkout starten/abrufen |
| POST /bookings/{uuid}/cancel | bestätigte operative Stornierung; ggf. Erstattungsauftrag |
| POST /bookings/{uuid}/refund | amount_cents und Idempotency-Key |
| POST /marketing/campaigns | campaign_key; Ereignisse nur für bestätigte Kontakte |
| POST /worker | fällige Erstattungs-/Automatisierungsaufträge verarbeiten |

POST /stripe/webhook und /automation/events brauchen keine WordPress-Anmeldung, aber ihre jeweilige gültige Signatur. Öffentliche Newsletter-Endpunkte können keine Buchungen/Preise ändern und keine Kontaktdaten auslesen. Weitere Vertragsdetails in BOOKING-EMAIL-CONTRACT.md.

## Worker, Betrieb und Daten

WordPress-Cron alle fünf Minuten als Vorbereitung; er ist verkehrsabhängig, keine garantierte Ausführung alle fünf Minuten. Später einen echten Servercron einrichten. Der automatische Worker verschickt ohne explizite Automatisierungs-Konfiguration nichts. Refund-Worker bleibt ohne Testschlüssel ebenfalls aus. Provider-Annahme und E-Mail-Zustellung sind unterschiedliche Statuswerte.

Geld-/Coupon-Zähler und Journaländerungen erfolgen in InnoDB-Transaktionen. MySQL-Deadlocks dürfen einen Request abbrechen; der aufrufende Client muss mit demselben Idempotenzschlüssel wiederholen. REST liefert keine unredigierten Zahlungsdienstantworten oder Datenbankfehlertexte aus.

Outbox-Payloads und gespeichertes Abmeldetoken sind AES-256-GCM-verschlüsselt, Schlüssel aus dem stabilen WordPress-secure_auth-Salt abgeleitet. Kontakte/Buchungen enthalten hingegen reguläre private Datenbankfelder, nicht eine vollständig verschlüsselte Datenbank. Vor Änderung/Rotation des secure_auth-Salts müssen wartende Payloads migriert oder neu erzeugt werden; kein fertiges Schlüsselrotationswerkzeug in 0.1.0. Datenbankzugang/Backups und Debug-/HTTP-Logs schützen; tokenhaltige Bestätigungslinks nicht an Dritte weitergeben.

Anfragebegrenzung speichert nur zeitlich begrenzte HMAC-Buckets, keine Roh-IP. Der WordPress-Server kann unabhängig davon normale Zugriffslogs führen. Fachliche Aufbewahrung/Löschung, Datenexport und DSGVO-Prozesse müssen vor echten Kunden ergänzt werden. Deaktivierung stoppt den Cron; Tabellen werden nicht automatisch gelöscht. Keine ungewollte Vernichtung von Finanz-/Einwilligungsnachweisen.

Dynamische Checkout-/Webhook-/Consent-Endpunkte vor Produktivbetrieb aus Seiten-/Proxy-Caches ausschließen. Consent-Landingpages senden no-cache/noindex/no-referrer und laden keine externen Assets. Technische Buttonbeschriftung derzeit Deutsch, Buchungsereignisse tragen locale=de/en. Texte/Übersetzungen und Einwilligungsfassung sind noch redaktionell bereitzustellen.

## Nicht fertig bzw. nicht aktiviert

Kein Kunden-Checkout auf der Website, keine Live-Zahlungen, kein vollständiges Partnerportal/Kapazitätssystem, keine echte Anbieter-API, kein SaaS-Abonnement, kein Einzug aus Partnerbankkonten, keine rechtskonforme Rechnung/PDF-Erzeugung, kein ausgewählter/verbundener E-Mail-Versanddienst. Keine Steuer-/Rechnungsstellerentscheidung durch diese Software. Nicht auf der laufenden Münchner Affiliate-Seite installieren, um diese zu aktualisieren.

## Prüfung und Paket

BOOKING-CORE-PRUEFUNG.json enthält den lokalen Teststand. Hinzu kommen Browserprüfung für tatsächliche GET/POST-Bestätigung und Widerruf sowie ein Gleichzeitigkeitstest mit zwei separaten PHP-/MySQL-Verbindungen. Echte Zahlungs-/Mailnetzwerke werden nicht angesprochen; Testdaten werden wieder entfernt.

Reproduktion in der vorhandenen lokalen WordPress-Umgebung: scripts/test-booking-local.sh. Separates ZIP vpm-booking-core.zip unter pakete; nur als Entwicklungs-/Testmodul installieren. PHP 8.1+, 64-Bit, OpenSSL und InnoDB erforderlich. Die übrigen Theme-/Plugin-Pakete installieren dieses Modul nicht automatisch.
