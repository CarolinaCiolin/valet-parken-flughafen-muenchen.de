# Flughafenparktool 0.8.0 – Services, Bestände und Preise

Stand: 8. November 2026. Erweiterung des optionalen Buchungskerns für die Dachmarke, nicht der Münchner Affiliate-Buchungslinks. Entwickelt und lokal geprüft; keine Installation auf Hostinger, kein tatsächlicher Mailversand und kein öffentlicher Zahlungscheckout.

## Partnerportal

`/?vpm_partner_portal=1` erfordert einen zugeordneten WordPress-Zugang. Navigation: Übersicht; Buchungen/ausstehende Vorgänge; Stornos/Klärungsfälle und Kundenanfragen; Stellplätze/Kapazitäten; Angebote/Extras; Preisverwaltung; Servicegebühren/Zeiten; Standort/Anreise; Benachrichtigungen; Statistiken; Anbindungen/Zugänge. Dies ist zunächst eine gegliederte Oberfläche mit Sprungnavigation, noch kein fertiges SaaS-Dashboard mit Kalender, Suche, Antworteditor und frei verwaltbaren Mitarbeiterkonten. Lesende Benutzer bekommen nur ihre vorhandenen Ansichtsbereiche verlinkt. Mandantenprüfung erfolgt weiterhin auf dem Server.

Einrichtung:

1. Partner und Benutzerzuordnung durch die Plattformverwaltung anlegen. Eigenes Angebot pro physischem Stellplatzpool und Stellplatzart anlegen: Außenparkplatz, überdacht, Parkhaus oder Tiefgarage. Extras wie Waschen/Laden werden gesondert freigegeben und bepreist.
2. Unter Preisverwaltung Grundtarif anlegen. Neue Tarife starten ohne verfügbare Plätze; erst anschließend unter Stellplätze/Kapazitäten den echten Bestand und seinen Lade-Teilbestand eintragen.
3. Unter Servicegebühren/Zeiten nur tatsächlich angebotene Services aktivieren: Valet, Shuttle oder Parken mit Eigenanreise. Beide Transferarten verwenden denselben Stellplatzbestand des Angebots. Keine duplizierten Angebote für denselben Pool anlegen.
4. Gebühren, Wochenzeiten und Nachtzuschläge einstellen; Kontaktdaten und Anfahrtshinweise ergänzen. Eigenverwaltung erst bewusst über Anbindungen aktivieren. Bei externer Verwaltung ist das führende Herstellersystem für Verfügbarkeit, Gebühren und Preise zuständig.

## Gemeinsamer Bestand und Ladeplätze

Gesamtkapazität beispielsweise 20 überdachte Plätze, davon 4 mit Ladesäule. Ladeplätze sind in den 20 enthalten. Jede Reservierung beansprucht einen Gesamtplatz; eine Anfrage mit notwendigem Ladeplatz zusätzlich einen Ladeplatz. Auch wenn noch Gesamtplätze frei sind, darf kein fünfter Ladeplatz verkauft werden. Das tatsächlich gebuchte Ladepaket kann zusätzlich kostenpflichtig sein; ein Ladesäulenplatz allein verspricht keine enthaltene Strommenge oder Ladeleistung.

Der gesamte Parkplatzzeitraum wird abgeglichen. Die maximale gleichzeitige Belegung innerhalb dieses Zeitraums entscheidet, nicht die Anzahl aller Buchungen, die ihn irgendwie berühren. Direkt aneinander anschließende Aufenthalte überschneiden sich nicht. Datenbanksperren und eine erneute Prüfung beim Anlegen verhindern den Doppelverkauf des letzten Platzes. Valet und Shuttle konkurrieren um denselben Pool. Ein bestätigter Storno gibt die Belegung frei. Ein bloßer Kunden-Stornowunsch noch nicht.

Vorhandene interne Reservierungen haben technische Ablaufzeiten, die Verfügbarkeitsprüfung ignoriert abgelaufene Holds. Es gibt keine neue Kundenzusage über eine bestimmte Reservierungsdauer. Bestandsreduzierungen unter die bereits reservierte Spitzenbelegung werden abgelehnt. Änderungen am Tarif überschreiben den Bestand nicht. Der manuelle Pool erfasst keine Verkäufe fremder Portale ohne echten Systemabgleich. Zeitweilige Betriebssperren, wechselnde Tageskapazitäten und Sperrkontingente sind noch kein eigener Kalendereditor.

## Flugzeiten und Servicezeiten

Die neuen Formulare fragen standardmäßig geplanten Abflug und geplante Landung am Rückreisetag ab. Der Server berechnet:

- Beginn der Parkplatzbelegung und Serviceprüfung = Abflug minus **3 verstrichene Stunden**.
- Ende der Parkplatzbelegung und Serviceprüfung = Landung plus **30 Minuten**.
- Ortszeit und Sommer-/Winterzeit des Flughafens, einschließlich Tageswechsel. Nicht existierende Ortszeiten werden abgelehnt, doppelte Ortszeiten benötigen die vorhandene explizite Offsetauswahl.

Beide Zeitpunkte müssen im angebotenen Servicezeitfenster liegen. Ansonsten erscheint dieser Service des Partners nicht. Andere verfügbare Services desselben Partners können weiterhin angeboten werden. Keine eingetragenen Wochenzeiten bedeutet durchgehend verfügbar; deaktivierter Service bleibt deaktiviert. Sobald Wochenzeiten existieren, sind nicht eingetragene Wochentage geschlossen. Mehrere Tagesintervalle mit Komma; `22:00-06:00` läuft in den nächsten Tag, `00:00-00:00` bedeutet den gesamten betreffenden Tag. Öffnungs-/Schließgrenzen werden einschließlich geprüft; Nachtzuschlag-Endgrenzen ausschließlich.

Die genauere Übergabezeit für die Bestätigung lässt sich mit 120–180 Minuten Vorlauf einstellen (Standard 180). Würde der kürzere Vorlauf außerhalb der angebotenen Zeiten liegen, bleibt es beim geprüften Dreistundenzeitpunkt. Dieser konkrete Zeitpunkt wird je Buchung gespeichert. Eine spätere Änderung von Kontakten/Anfahrt verschiebt einen bestehenden Termin nicht stillschweigend. Der Server stellt Termin, Zeitzone, Hinweise und Maps-Link der Bestätigungsmail zur Verfügung; im Checkout gibt es dafür keinen zusätzlichen Anfahrts-Schritt.

Bestehende API-Integrationen ohne `time_basis` behalten direkte Parkplatzzeiten. Neue Formulare senden ausdrücklich `time_basis=flight`; mit `time_basis=parking` werden direkte Abgabe-/Rückgabezeiten eingegeben. Nicht gleichzeitig schon umgerechnete Parkplatzzeiten als Flugzeiten einspeisen.

## Preise und Rabatte

Beträge in ganzen EUR-Cent, als Kundengesamtbeträge einschließlich gegebenenfalls anfallender Steuern zu pflegen; keine Steuerberechnung/Rechnungserstellung durch diese Erweiterung.

1. Grundpreis je angefangene 24 Stunden oder fest pro Aufenthalt.
2. Saison-Tagespreise ersetzen den Tagespreis des jeweiligen 24-Stunden-Abschnitts, dessen Beginn in der Ortszeit des Flughafens liegt. Von-/Bis-Datum einschließlich. Überlappende Saisons werden abgelehnt. Dies ist kein Kalendertage-/Mitternachtstarif.
3. Festpreis für eine genaue Anzahl angefangener 24 Stunden hat Vorrang vor Tages-/Saisonsumme.
4. Aufenthaltsrabatte: beispielsweise 10 % auf die Aufenthaltstage 8–14 oder 5 % auf den gesamten Parkpreis ab 14 Tagen. Falls mehrere Regeln passen, gilt der höchste Rabattbetrag, keine Addition. Tagesabschnittsrabatte gelten nur für Tagespreise; bei exaktem Aufenthaltsfestpreis oder pauschalem Aufenthaltspreis werden sie nicht angewandt. Prozentbeträge werden auf Cent gerundet. Für jede zusätzliche Regel erscheint nach dem Speichern eine weitere Eingabezeile, maximal 30 pro Gruppe.
5. Valet-/Shuttlegebühr einmal pro Buchung oder je Übergabe und Rückgabe (zweimal derselbe Betrag). Nachtzuschläge getrennt für Anreise und Abreise, bezogen auf die vorsichtigen Prüfzeitpunkte −3 h / +30 min. Nachtintervalle können über Mitternacht laufen.
6. Zusatzleistungen werden im vorhandenen Extras-Schritt hinzugefügt. Plattformgutscheine werden danach separat geprüft und weiterhin aus der Plattformprovision finanziert. Die vom Partner konfigurierten Aufenthaltsrabatte sind dagegen Bestandteil seines Angebotspreises und verändern dessen Berechnungsbasis.

Angebote zeigen die Preisbestandteile aus der serverseitigen Berechnung. Der gewählte Service und die Preisaufteilung werden mit der Anfrage gespeichert. Ein gültiges Angebot hat weiterhin zehn Minuten Preisgültigkeit; die Kapazität und aktuelle Servicefreigabe werden beim Anlegen erneut geprüft. Preisänderungen schreiben vorhandene Buchungspreise nicht um. Herstellerangebote werden nicht nachträglich mit den lokalen Gebühren/Rabatten doppelt bepreist.

## Einbettung und Schnittstellen

```text
[vpm_booking_form airport="STR" service="valet" flow="steps"]
[vpm_booking_form service="any" flow="steps"]
[vpm_booking_form airport="STR" service="shuttle" time_basis="flight"]
```

Auf Valet-Landingpages `service="valet"` setzen, auf allgemeinen Seiten `service="any"`. Die Vorauswahl ist im Frontend änderbar; kein erzwungener Kauf des Valet-Service. Ein Partner ohne freigegebenes Valet-Angebot wird dafür nicht ausgegeben. Gutenberg bietet Servicevorauswahl und Flug-/Parkplatzzeitmodus ebenfalls in den Blockeinstellungen, Elementor über das vorhandene Shortcode-Widget. Unkonfigurierte Formulare zeigen alle Services; keine automatische Erkennung anhand fremder Domains oder Seitenüberschriften.

Öffentliche Angebots-API: zusätzlich `service` (`any`, `valet`, `shuttle`, `parking`), `charging_required` und `time_basis`. Bei Flugmodus enthält der Adapterkontext sowohl ursprüngliche Flugzeiten als auch berechnete Parkplatzzeiten; ein Herstelleradapter muss dieselben Zeitbegriffe verwenden. Manuelle Servicevarianten teilen die `rate_id`. Externe Adapter geben den konkreten `service` und bei angefragtem Ladeplatz ausdrücklich `charging_space: true` zurück und garantieren Ladeplatzverfügbarkeit sowie vollständigen Preis einschließlich Gebühren selbst. Lokale Servicezeiten filtern auch externe Angebote, wenn konfiguriert; sie ersetzen keinen Herstelleradapter.

Authentifizierter POST `/wp-json/vpm-booking/v1/portal/{partner_id}/configuration` für die gleichen Konfigurationen wie das Portal: `portal_action=services|capacity|rate|logistics`, mit den jeweiligen Formularfeldern. WordPress-REST-Authentifizierung und Partner-Schreibrecht erforderlich, bei Cookie-Login zusätzlich REST-Nonce; technische Konten können später eigene eingeschränkte WordPress-Zugänge verwenden. Keine öffentliche Konfigurationsschnittstelle, keine API-Schlüssel im Frontend. Der konkrete Herstelleradapter bleibt weiterhin offen.

## Anfahrt und Benachrichtigungen

Pro Parkplatz-/Übergabe-/Rückgabeort ist jetzt ein eigener HTTPS-Google-Maps-Link hinterlegbar, auch ohne Koordinaten. Zulässig sind definierte Google-Maps-Hosts einschließlich `maps.app.goo.gl`; ähnliche Angreiferdomains und fremde URLs werden abgelehnt. Ohne direkten Link wird weiterhin aus Pin oder Adresse ein Routenlink gebildet. Kein URL-Abruf, keine Kartenressource oder Google-Einbettung beim Seitenladen. Partnerhinweise gehen in die Bestätigungsmail-Daten und können ergänzend im geschützten Kundenzugang stehen.

Getrennte, idempotente Partnerereignisse: `partner.booking_requested` (ausdrücklich unbezahlt), `partner.payment.received`, `partner.booking.confirmed`, `partner.cancellation_requested`, `partner.booking.cancelled`, `partner.customer_inquiry`. Empfänger ist die hinterlegte interne Buchungs-E-Mail, ersatzweise Rechnungs-E-Mail. Fehlende Adresse erzeugt eine Klärungsaufgabe. Rechnungs-/Belegaufgaben bleiben beim Partner. Kundennachrichten werden verschlüsselt gespeichert und im eigenen Portal angezeigt. Wiederholtes Absenden mit derselben Vorgangskennung erzeugt keine doppelte Anfrage. Der Partner kann eine Anfrage nach Bearbeitung abschließen; ein im Portal verfasster E-Mail-Antwortdialog ist noch offen.

„Benachrichtigung vorbereitet“ bedeutet: dauerhaftes verschlüsseltes Versandereignis in der vorhandenen Outbox. Tatsächlicher Versand setzt den zentralen Mailadapter, geprüfte Absenderdomain und Vorlagen voraus. Absender bleiben bei der Plattform; der Partner erhält keine Möglichkeit, einen fremden From-Absender einzuschleusen. Keine Testmail wurde real versendet.

## Prüfungen und verbleibende Grenzen

`scripts/test-booking-commerce-local.sh`: vorherige Regressionstests plus neue Backend- und Browserprüfungen; nur lokale Installation mit leeren eigenen Buchungstabellen. `scripts/check-booking-ci.sh` prüft zusätzlich eine frische isolierte Installation ohne Fremdpluginfixtures. Ergebnisse: [Backend](BOOKING-COMMERCE-PRUEFUNG.json), [Browser](BOOKING-COMMERCE-BROWSER-PRUEFUNG.json). Sämtliche Angebote, Kontakte und Screenshots sind lokale Testdaten.

Noch offen: reale Herstelleradapter; öffentliche Stripe-Zahlung und konkrete Händlerrolle; tatsächlicher Mailversand; finale Mailtexte; Privat-/Firmenkundendaten; Belegdatei-Upload; fertiges Partnerdashboard/Kalender; Infrastruktur/Subdomain/Hostinger-Freigabe. Für den Betrieb müssen Partnerdaten, Preise und Services echt eingerichtet und End-to-End geprüft werden. GitHub-Push installiert nichts auf Hostinger.
