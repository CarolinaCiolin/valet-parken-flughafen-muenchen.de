Aktuell ab 0.7.0: [Partnerzuständigkeit und neue Dokument-/Fahrhinweis-Ereignisse](BOOKING-LOGISTICS-0.7.0.md). Die dortige Zuordnung ersetzt die hier historisch genannten generischen invoice.requested/invoice.adjustment_requested-Ereignisse.

# Schnittstelle für E-Mail-Automatisierung und Belege

## Einrichtung erst nach Wahl des Dienstes

Das Modul stellt eine dienstunabhängige Ereignisschnittstelle bereit. Noch kein fertiger Brevo-/Klaviyo-/SMTP-Adapter, kein Versandkonto, keine fremde kostenpflichtige Buchungssoftware. Der gewählte Empfänger muss JSON-Ereignisse annehmen oder einen eigenen Adapter davor erhalten. Absenderdomain/Versandfreigaben und Inhalte später beim tatsächlichen E-Mail-Dienst einrichten.

Sicher außerhalb von GitHub konfigurieren:

- VPM_BOOKING_AUTOMATION_URL: eigener externer HTTPS-Empfangsendpunkt.
- VPM_BOOKING_AUTOMATION_SECRET: mindestens 32 Zeichen, nicht im Frontend oder an Kunden ausgeben.

Unter Werkzeuge → VPM Buchungskern bewusst aktivieren. Für Newsletter außerdem ausdrücklichen Einwilligungstext und Versionskennung eintragen. Ohne diese Konfiguration bleibt der Anmeldebaustein gesperrt. Es gibt keine vorgeschriebenen Marketingtexte oder eingespielten Kontakte.

## Ausgehend: mindestens einmal, idempotent empfangen

HTTP POST, Content-Type application/json, kein automatisches Folgen von Weiterleitungen. Header:

- X-VPM-Event-ID: stabiler fachlicher Ereignisschlüssel.
- X-VPM-Timestamp: Unixzeit in Sekunden.
- X-VPM-Signature: v1= gefolgt von HMAC-SHA256 über timestamp + "." + unveränderten JSON-Rohkörper, mit dem gemeinsamen Secret.

Der Empfänger prüft Signatur und Zeitfenster, dedupliziert anhand event_id und quittiert erst nach dauerhafter Annahme mit 2xx. Bei erneuter Zustellung desselben Schlüssels keine zweite E-Mail senden. Ein 2xx ist keine tatsächliche E-Mail-Zustellbestätigung; bounced/failed/delivered werden getrennt gemeldet. Netzwerkfehler können trotz erfolgter Annahme einen Retry verursachen.

Envelope: event_id, topic, purpose, created_at, payload. Payload kann personenbezogene Daten und individuelle Bestätigungs-/Abmeldelinks enthalten. Nicht öffentlich protokollieren oder unverändert in Fehlerberichte kopieren. Outbox ist lokal verschlüsselt; HTTPS bleibt für den Versand erforderlich.

| Topic | Zweck / spätere Aktion |
| --- | --- |
| booking.requested | Eingang einer Backend-Anfrage, noch keine verbindliche Kundenbestätigung |
| booking.confirmed | operativ bestätigt und Zahlung verifiziert; Bestätigungsnachricht |
| booking.cancelled | operative Stornierung, Erstattungsstatus separat beachten |
| payment.received | Zahlungseingang/Belegereignis, keine automatisch gültige Steuerrechnung |
| invoice.requested | tatsächlichen Rechnungssteller/Belegsystem um Erstellung bitten |
| invoice.adjustment_requested | Storno-/Korrekturbeleg zum Refund anfordern |
| refund.requested | Erstattungsauftrag vorhanden, noch nicht als ausgeführt darstellen |
| refund.succeeded | bestätigte Erstattung |
| refund.failed | fachlicher/technischer Ausnahmefall, nicht als Erstattungserfolg senden |
| document.available | bestätigtes Dokument abrufbar, Referenz/Art/URL vorhanden |
| marketing.confirmation_requested | einmalige DOI-Anfrage, keine Werbeeinwilligung |
| marketing.confirmed | bestätigte Newsletter-Zustimmung synchronisieren |
| marketing.revoked | Sperrung/Widerruf synchronisieren |
| marketing.message.requested | nur für bestätigte Kontakte eingestelltes Aktionsereignis |
| reconciliation.required | interner Ausnahmefall; kein automatischer Kundenbeleg |

purpose ist transactional, consent oder marketing. Transaktionsnachrichten dürfen nicht von einer Newsletter-Zustimmung abhängig gemacht oder ungefragt mit Werbekampagnen kombiniert werden. Ein Kauf aktiviert keinen Newsletter. Kontakte dürfen aufgrund marketing.confirmation_requested noch nicht als werblich erreichbar gelten.

Marketing-Payloads tragen consent_version und consent_revision. Empfänger muss pro Kontakt nur die neueste Revision als aktuellen Zustand anwenden; ältere Sync-Ereignisse dürfen eine neue Abmeldung nicht überschreiben. Vor Versand einer wartenden Werbenachricht prüft das Modul lokal noch einmal Zustimmung und Revision. Bereits an einen fremden Dienst übermittelte Kampagnen müssen auch dort nach Widerruf gesperrt werden; eine solche Drittanbieter-Sperre ist ohne dessen tatsächliche API-Anbindung nicht bewiesen.

## Individueller Einwilligungsbutton

[vpm_booking_newsletter] ist ein optionaler späterer WordPress-Baustein. Nicht vorausgewählte Checkbox, serverseitiger Text-/Versionssnapshot, Bestätigungslink mit 24 Stunden Laufzeit. Link-GET zeigt nur einen Button; erst explizites POST bestätigt. Dadurch aktiviert ein reiner E-Mail-Linkscan die Anmeldung nicht. Unsubscribe-Link zeigt entsprechend einen Abmeldebutton. Token stehen in Kontakttabellen als Hash, für notwendige Nachrichten als verschlüsseltes Material, nicht im öffentlichen Kontaktverzeichnis.

POST /wp-json/vpm-booking/v1/marketing/request nimmt JSON {email, agreed:true} an; Begrenzung pro Adresse/Zeitfenster und pro IP-HMAC-Bucket. POST /marketing/unsubscribe nimmt das individuelle Abmeldetoken an und kann für einen späteren geeigneten One-Click-Unsubscribe-Adapter genutzt werden. Beide Endpunkte sind keine Kontaktlese- oder Verwaltungs-API. Kein öffentliches „Anmelden ohne Bestätigung“.

## Eingehend: signierte Rückmeldungen

POST /wp-json/vpm-booking/v1/automation/events, dieselbe HMAC-Konstruktion über X-VPM-Timestamp / X-VPM-Signature und maximal 5 Minuten Zeitabweichung. Getrenntes eigenes event_id pro Rückmeldung erforderlich.

- document.available: booking_id (öffentliche Buchungs-UUID), reference (global eindeutige/mit Aussteller präfixierte Referenz), kind=invoice/receipt/credit_note, url (HTTPS). Idempotent; unterschiedliche Dokumente dürfen nicht dieselbe Referenz verwenden. Keine Datei wird dabei automatisch heruntergeladen. Rechnungssteller, Pflichtangaben, Steuer, Nummerierung und PDF-Erstellung verantwortet das spätere Belegsystem.
- marketing.unsubscribed: email des bestehenden Kontakts. Signierter Widerruf beim E-Mail-Anbieter sperrt auch lokal den Kontakt und wartende Werbeereignisse; keine Rückmeldungsschleife. Eine externe Anmeldemeldung kann dagegen nicht unsere ausdrückliche DOI-Bestätigung ersetzen.
- email.delivered / email.bounced / email.failed: outgoing_event (bekannter ursprünglicher event_id). Status wird als Rückmeldung gespeichert. Keine Kundeneinwilligung durch Zustellmeldung aktivieren.

Beide Webhooks benutzen Rohkörper-Signaturen. Ein beliebiger Browseraufruf oder eine manipulierte Rückkehr-URL kann keine Zahlung, Rechnung oder Einwilligung bestätigen. Schlüssel nicht an Parkplatzpartner oder Endkunden weitergeben; eine spätere Partner-Schnittstelle erhält eigene eingeschränkte Authentifizierung.
