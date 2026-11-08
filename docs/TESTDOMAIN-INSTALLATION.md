# Testinstallation vorbereiten

Aktueller Buchungskern: [0.11.0 – Branding und Dashboards](BOOKING-BRANDING-0.11.0.md). Testziel valet-parkservice.com; freigegebener Testmailempfänger/-absender kontakt@valet-parken-flughafen-muenchen.de (Hostinger). Noch keine Installation/SMTP-Konfiguration durch den Agenten. SSH wurde bisher mit Connection refused abgewiesen; WordPress-Upload bleibt möglich.

Eigenständige Testinstallation für den Buchungskern, nicht die bestehende Münchner Affiliate-Seite. Diese Anleitung und das Paket erstellen noch keine Domain, Installation, Postfächer oder Zahlungsdienstverbindung.

## Noch benötigte Angaben

1. Exakte Testdomain/Subdomain und Hoster. Eigene WordPress-Installation und HTTPS bereits vorhanden? Falls nicht, ist dort eine leere separate Installation einzurichten. Keine vorhandene Website überschreiben.
2. Sicherer Zugang: bestehender Hostinger-SSH-Zugang mit einem für diese Arbeitsumgebung verfügbaren Schlüssel und Berechtigung für die Testinstallation oder Hostinger-Zugangseinladung über den vorgesehenen sicheren Weg. Die alten SSH-Details allein beweisen nicht, dass der passende private Schlüssel aktuell verfügbar ist. Kein Passwort/private Schlüssel im Chat. Ohne direkten Zugang bleiben Uploads durch den Betreiber möglich.
3. Eigene Empfängeradresse für Testmails und vorhandene Absenderadresse/Postfach auf einer kontrollierten Domain. Zusätzlich Postfachanbieter und unterstützte SMTP-Konfiguration. Keine Mailpasswörter/API-Schlüssel im Chat; diese werden erst in einer sicheren privaten Konfiguration hinterlegt.
4. Für einen späteren echten Stripe-Sandboxtest: eigenes Stripe-Testkonto/Testschlüssel und gegebenenfalls Test-Connect-Zuordnung. Für die derzeit vorhandene private Zahlungssimulation nicht nötig. Öffentlicher Stripe-Checkout bleibt ein gesonderter Ausbau.

## Installation

Das Downloadpaket `vpm-testdomain-installation.zip` ist ein Container zum Entpacken, kein direkt installierbares WordPress-Plugin. Darin:

- `vpm-hybrid.zip`: unter Design → Themes installieren und aktivieren.
- `vpm-providers.zip`: redaktionelle Anbieterkomponente, optional für spätere Karten.
- `vpm-booking-core.zip`: Buchungskern als eigenes Plugin installieren/aktivieren.
- Anleitung, Prüfsummen und CLI-Prüfung.

Das Münchner Inhaltsentwürfe-Plugin ist absichtlich nicht enthalten. Keine echten Münchner Texte, Affiliate-Links, Tracking-IDs oder Rechtstexte importieren. Profil für Test/Dachmarke bewusst wählen, keine Bestandswebsite umschalten. Ein vorhandenes globales SMTP- oder Newsletterplugin nicht ungeprüft aus einer produktiven Installation übernehmen.

Auf der Testinstallation:

1. Beide WordPress-Adressen auf dieselbe HTTPS-Testdomain setzen, bevor Sprach-/Buchungsrouten geprüft werden.
2. Domainzugriff schützen; zusätzlich Suchmaschinenindexierung ausschließen. `noindex` ersetzt keinen Zugriffsschutz. Frontend und API müssen passend geschützt bleiben. Spätere externe Webhooks benötigen eine gezielt erreichbare, signaturgeprüfte Route.
3. Demomodus ausschließlich auf dieser Installation gemäß `wp-config-test.example.php` aktivieren. Datei nicht als Ersatz für wp-config.php hochladen. Der Demomodus schaltet weder SMTP noch einen öffentlichen Zahlungscheckout ein und ist kein Schutz für reale Daten.
4. Partnerrollen mit reinen Partnerrechten anlegen; keine WordPress-Administratoren für Partner. Eigener Plattformadministrator. Das Portal zunächst unter der Testdomain mit `?vpm_partner_portal=1` testen. Eine Partner-Subdomain anschließend mit TLS/Anmeldung/Weiterleitung separat prüfen; DNS allein genügt nicht.
5. Synthetische Partner A/B/C anlegen: überdacht mit Lade-Teilbestand/Valet; Außenstellplätze/Shuttle; ausgebuchte Tiefgarage. Richtige Flughafenzeitzone, erfundene Preise, klar markierte Testadressen, keine fremden Telefonnummern. Kontakte zunächst `example.invalid`.
6. Private Testseite mit `[vpm_booking_form airport="MUC" flow="steps"]` anlegen. Für einen Valet-Einstieg zusätzlich `service="valet"`. Eine private Seite regulär als Administrator öffnen; die WordPress-Editorvorschau deaktiviert Buchungsaktionen bewusst. Öffentliche Anfragen in der Buchungsverwaltung ausdrücklich freigeben, erst nachdem Testbestand und Zugriffsschutz geprüft wurden.
7. Buchung, Extras, Gutschein, Preisänderung, ausgebucht, Servicezeiten, Stornowunsch, Partnerfreigabe und Nachrichtenfreigabe durchspielen. Simulation nur in der privaten Administratordemo. Partneransicht getrennt mit eigenem Partnerlogin prüfen.

## Mailtest ohne Klaviyo

Für einen ersten SMTP-Test reichen ein vorhandenes Absenderpostfach und ein eigenes Empfängerpostfach. Vor jeder tatsächlichen Zustellung muss ein serverseitiger Empfängerfilter alle Ausgänge auf freigegebene Testpostfächer begrenzen. Nicht an die erfundenen Partner-/Kundenadressen versenden und keine produktiven Newsletterlisten verbinden.

Die bestehende Outbox benötigt noch einen konkreten Versandadapter. Ein SMTP-Plugin allein verarbeitet diese Outbox nicht automatisch. Vorbereitet sind Nachrichtendaten, Freigaben und signierte Ereignisse; noch nicht vorhanden ist eine fertige allgemeine SMTP-/Klaviyo-Zustellstrecke. Der Adapter kann nach Auswahl des Postfachanbieters gezielt eingerichtet werden. Der erste Test umfasst WordPress-Testmail, danach Buchungsbestätigung mit Anfahrt, Partnerbenachrichtigung ohne Kundenadresse, freigegebene Antwort und Stornobenachrichtigung.

Vor dem ersten Test prüfen: erlaubte Empfänger; Absender/Reply-To unter Plattformkontrolle; SPF/DKIM und gegebenenfalls DMARC; keine Kundendaten in Partnerkopfzeilen; keine ungeprüfte Weiterleitung zitierter Originalmails. Eingehende Antworten/Relay-MX sind ein eigener anschließender Schritt. Wer nur den Ausgang testet, hat noch kein funktionierendes Zweiweg-Postfach.

Ein lokales Mail-Testpostfach ohne Außenversand, etwa Mailpit, ist eine zusätzliche Entwicklungsoption. Es ist kein hier eingerichteter Hostinger-Dienst. Klaviyo folgt erst nach funktionierendem Buchungs-/Mailtest wie vom Betreiber gewünscht.

## Read-only-Prüfung

```bash
php preflight.php /ABSOLUTER/PFAD/ZUR/TESTINSTALLATION/wp-load.php
```

Nur über SSH/CLI ausführen, kein öffentliches Diagnose-URL-Skript. Ausgabe enthält Versionen, Domainkonfiguration und Zeilenzahlen eigener Tabellen, keine Zugangsdaten oder Kundenadressen. Zugangsschutz und tatsächliche Zustellung werden ausdrücklich nicht als geprüft behauptet. Änderungen/Installation erst auf die bestätigte Testdomain begrenzen.

## Abschlusskriterien

Frontend erreichbar und geschützt; richtige Domain/HTTPS; private Demo; eigene Partnerdaten getrennt; keine echten Kundenadressen beim Partner; Nachrichten erst nach Freigabe; keine Live-Zahlung; Testmails ausschließlich an erlaubte eigene Postfächer; Fehler/Backups/Servercron geprüft. Erst danach Klaviyo und weitere echte Anbindungen vorbereiten. Testdaten nicht als Produktionsbuchungen übernehmen.
