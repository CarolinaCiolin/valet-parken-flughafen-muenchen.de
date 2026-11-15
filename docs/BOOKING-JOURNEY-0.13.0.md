# Buchungskern 0.13.0 – Suche, Buchungsstrecke und Partnerportal

Nur für die getrennte Testinstallation auf **valet-parkservice.com**. Die Münchner Affiliate-Seite benötigt dieses Pluginupdate nicht. Dieses Paket wurde lokal geprüft, noch nicht durch den Agenten auf Hostinger installiert.

## Bestehende Testinstallation aktualisieren

1. Dateien und Datenbank der Testinstallation sichern. Partner-, Preis- und Bestandsdaten bleiben beim Update erhalten; ein Backup ist für die Rückkehr auf eine ältere Datenbankversion erforderlich.
2. `pakete/vpm-booking-core.zip` herunterladen. In WordPress unter **Plugins → Neues Plugin hinzufügen → Plugin hochladen** wählen und das bestehende Plugin ersetzen. Kein Theme-/Inhaltsimport erforderlich.
3. In der Buchungsverwaltung die Version **0.13.0** kontrollieren. Die eigenen Tabellen werden auf Schema **12** ergänzt. Keine Testdaten werden automatisch importiert oder veröffentlicht.
4. Unter Partnerverwaltung die bestehenden Angebote prüfen: Für **überdacht, Parkhaus und Tiefgarage** die maximale Fahrzeughöhe in Metern speichern. Diese Angebote werden in der neuen Buchungsstrecke erst mit Höhenangabe angezeigt.
5. Unter Beständen Lade-Teilbestände und gegebenenfalls Behindertenstellplätze hinterlegen. Ein Lade-Teilbestand wird durch die Buchung eines Ladepakets benötigt; die frühere Kundenoption „Ladeplatz benötigt“ entfällt.
6. Suche, Partnerportal und Support nach der unten stehenden Checkliste testen. Testzahlung und Testversand bleiben ohne bewusste Einrichtung ausgeschaltet.

## Einbettung und Navigation

Die Einbettung enthält **immer nur die Suche**. Die weiteren Schritte öffnen auf einer eigenen Buchungsseite `/?vpm_booking=1`, auf Desktop und Mobil gleichermaßen nacheinander. Kein vollständiger Checkout innerhalb einer redaktionellen Seite.

```text
[vpm_booking_form layout="compact"]
[vpm_booking_form layout="standard"]
[vpm_booking_form airport="MUC" service="valet" layout="compact"]
[vpm_booking_form airport="MUC" partner="123" layout="standard"]
```

`compact` ist die kleine Einbettung, `standard` der große Sucheinstieg für den Hero. Flughafen und Service sind Vorauswahlen und bleiben änderbar. Ohne Flughafenattribut bleibt die Auswahl leer. `partner` enthält die tatsächliche interne Partner-ID dieser Installation; `123` ist nur ein Beispiel. Die Anbieterauswahl wird auf den gewählten Flughafen begrenzt. Alte `flow`-/`airport_mode`-Attribute werden an bestehenden Blöcken toleriert, erzwingen aber keinen eingebetteten Checkout oder gesperrten Flughafen mehr. Gutenberg bietet dieselben Größen im Block **VPM Parkplatzsuche**, Elementor verwendet den Shortcode.

Schritte: Reise → Angebote → Extras → Angaben → Prüfen → Eingangsbestätigung beziehungsweise freigeschaltete Stripe-Testzahlung. Desktop zeigt zusätzlich eine laufende Zusammenfassung. Mobil folgt der Weiter-Button auf die Extras. Stornorichtlinien lassen sich aufklappen. Die abschließende Übersicht enthält Reisedaten, Auswahl, Kundendaten, Fahrzeug und Preise sowie Bearbeiten-Schaltflächen.

Datumseingabe mit getrennten Tag-/Monat-/Jahrfeldern, vierstelligem Jahr, manueller Uhrzeit und Kalender-/Uhrschaltfläche. Backspace am Anfang des Jahres führt zurück zum Monat. Serverprüfung von Datum, Zukunftsgrenzen, Flughafenzeit und Zeitumstellung bleibt verbindlich. Geänderte Suchkriterien verwerfen ältere Suchantworten. Nach einem Reload werden aus der URL die Reisedaten erneut geprüft; persönliche Angaben werden nicht in die URL geschrieben oder dort wiederhergestellt.

## Bestände, Höhen und Extras

- Valet und Shuttle nutzen denselben physischen Angebotsbestand.
- Behindertenstellplätze und Ladeplätze sind Teilbestände; die Schnittmenge wird ausdrücklich erfasst. Das verhindert eine doppelte Vergabe kombinierter Plätze. Die Angabe allein ist keine Zertifizierung einer barrierefreien Anlage.
- Für ein Ladepaket wird der passende Teilbestand beim Absenden atomar geprüft und reserviert. Ohne Paket kein Ladeplatzbedarf. Fahrzeughöhe wird für überdachte Angebote abgefragt und gegen die hinterlegte Grenze geprüft.
- **Extra-Kapazitäten** begrenzen optional die Zahl der Pakete pro Partner und Anreise- oder Rückreisetag. Ohne Aktivierung gilt keine zusätzliche Tagesgrenze. Das ist eine Tagesmenge, kein minutengenauer Werkstatt-/Ladesäulenkalender. Bei externer Anbindung muss der Herstelleradapter die tatsächlichen Leistungen bestätigen.
- **Verkaufsfreigabe** pausiert ein Angebot, ohne dessen physischen Bestand herunterzusetzen.
- Die Belegungsübersicht bietet Startdatum, Wochen-/31-Tage-Navigation, Stellplatzart, belegte/reservierte Anzahl, freie Anzahl und Auslastung in Prozent. Sie zeigt die Spitzenbelegung pro Tag, keine Garantie einer beliebigen Ankunftszeit.
- Unbezahlte abgelaufene manuelle Anfragen werden geschlossen; zugehörige Gutscheinreservierungen werden freigegeben. Ungeklärte Zahlungen behalten ihren Bestand, bis ein verifizierter Zahlungsstatus vorliegt.

## Partnerportal und Support ohne Kundenkonto

Partnerlogin: `https://valet-parkservice.com/?vpm_partner_login=1`

Das Portal öffnet nach dem Login den zugeordneten persönlichen Bereich. Dashboard mit Einrichtungsfortschritt und Aufgaben; Nachrichten, Buchungen, Bestände, Angebote, Preise und Bedingungen liegen in eigenen Menüpunkten. Speichern bestätigt die Änderung und lässt den Bereich beziehungsweise Angebotseditor offen. Der Fortschritt ist ein Vollständigkeitscheck, keine Freigabe einer echten Zahlungs-/Herstelleranbindung.

**Kein Kundenkonto und kein Kundenverwaltungsportal.** Der öffentliche Support liegt unter `/?vpm_support=1`. Der Link aus Eingangsbestätigung/E-Mail füllt die lesbare VPM-Buchungsnummer vor. Kunde gibt Buchungs-E-Mail, Anlass und Nachricht an: Anbieter kontaktieren, Storno anfragen oder Plattformbetreiber kontaktieren. Falsche Nummer/E-Mail und richtige Zuordnung erzeugen dieselbe neutrale öffentliche Antwort; es werden keine Buchungsdaten offengelegt. Ein Kundenwunsch storniert nicht unmittelbar.

Partner sieht den zugeordneten Nachrichtenverlauf und antwortet im Portal. Die Antwort wird standardmäßig direkt in die Kunden-Versandwarteschlange gestellt, ohne einzelne Plattformfreigabe. Plattformadministratoren können den Verlauf sehen. Kundenadressen bleiben für Partner verborgen. Ein Plattformanliegen ist für Partner nicht lesbar oder abschließbar. „Zum Versand vorgemerkt“ ist noch keine nachgewiesene Zustellung.

Kundenstorno, Anbieterabsage und tatsächlich manuell markiertes Nichterscheinen sind getrennte Vorgänge. Anbieterabsage berechnet keine Kundenstornogebühr. Vor Ausführung gibt es eine Betragsvorschau und ausdrückliche Bestätigung. Ein bereits laufender Stornovorgang wird nicht stillschweigend umklassifiziert. Herstellerbestätigung und Stripe-Erstattung bleiben getrennte Hintergrundschritte; ausstehend ist nicht abgeschlossen. Der tatsächliche bezahlte Betrag nach Gutschein bleibt die Gebührenbasis. Partner ist weiterhin für Rechnungen und Belege verantwortlich.

## Stripe-Testzahlung für Gäste

Öffentliche Gast-Testkasse unter **Kommunikation → Stripe-Gastzahlung im Testmodus** nur bewusst aktivieren, wenn private Testschlüssel, Webhook und Partner-Testkonto korrekt eingerichtet sind. Bestehende private Konfigurationsnamen:

```text
VPM_BOOKING_STRIPE_TEST_SECRET
VPM_BOOKING_STRIPE_WEBHOOK_SECRET
```

Schlüssel gehören in die geschützte Serverkonfiguration, weder in GitHub noch in den Chat. Der signaturgeprüfte Webhook ist die REST-Route `/vpm-booking/v1/stripe/webhook`; die tatsächliche REST-Basis der Installation beachten. Nur Stripe-Testschlüssel, keine Live-Zahlungen. Jeder angebotene Partner benötigt ein passendes Stripe-Testkonto für die vorgesehene Zahlungsaufteilung.

Die Gastzahlung verwendet eine kurzlebige, nur für diese Zahlung gültige Berechtigung; gespeichert wird deren Hash. Betrag und Empfänger stammen vom gespeicherten Serverangebot. Erneutes Starten verwendet dieselbe offene Checkout-Session. Die Session-Erstellung nutzt einen stabilen Ablaufzeitpunkt für idempotente Wiederholungen. Die Rückkehrseite übernimmt keine Zahlungsbehauptung aus der URL; Erfolg kommt ausschließlich aus signiertem Webhook oder authentifiziertem Stripe-Abgleich.

Die Rückkehrseite lädt keine allgemeinen WordPress-/Tracking-Skripte, entfernt das Fragment aus der Browseradresse und liefert keine persönlichen Buchungsdaten aus. Normale Buchungs-/Supportseiten behalten `wp_head`/`wp_footer` für WordPress, Complianz und HFCM. Der Worker gleicht ältere bekannte offene Stripe-Sessions ab. Ein erst nach Freigabe des Bestands verifiziert eingehender Betrag löst eine vollständige Erstattung statt einer ungesicherten Bestätigung aus. Unklare Session-Erstellung/Zahlungsanbieter-Ausfälle benötigen gegebenenfalls eine manuelle Klärung; es wird kein Erfolg erfunden.

Die lokalen Prüfungen ersetzen keinen Test mit dem echten Stripe-Testkonto, Connect-Zuordnung, erreichbar zugestelltem Webhook und Servercron auf Hostinger. Karten-Fingerprints zur Gutschein-Missbrauchserkennung sind noch nicht an diesen Checkout angeschlossen; rohe Kartennummern werden niemals erhoben.

## Test-E-Mail ohne Klaviyo

Neu: optionaler Outbox-Adapter über WordPress `wp_mail`, unter **Kommunikation → Geschützter Testversand**. Als einziges freigegebenes echtes Testpostfach derzeit **kontakt@valet-parken-flughafen-muenchen.de** verwenden. Alle von diesem Adapter bearbeiteten Nachrichten werden auf die eingestellte eigene Testadresse umgeleitet, auch Partner- und Kundenereignisse. Ohne Aktivierung kein Versand. Ein gleichzeitig konfigurierter Automations-Webhook sperrt diesen zweiten Transport.

Absender unter Branding konfigurieren, Postfachtransport gegebenenfalls über ein SMTP-Plugin mit geschützten Zugangsdaten einrichten. Erst WordPress-Testmail, dann Buchungsereignis, Partneranfrage, Portalantwort und Storno prüfen. Die technischen Vorlagen enthalten Buchungsnummer, Reise-/Fahrzeugdaten, Preis, bei bestätigter Buchung Anfahrt/Maps und Supportlink. Die erste native Vorlagenfassung ist deutsch; englische Redaktion und Zustellprüfung folgen vor englischem Produktivversand. Keine Steuerrechnung wird erzeugt.

„Übergeben“ bedeutet nur von `wp_mail` angenommen. SPF/DKIM, Spamordner und tatsächlichen Eingang kontrollieren. Der Adapter wurde lokal ausschließlich mit abgefangenen Mails geprüft, nicht mit echtem SMTP. Er ist kein allgemeiner Produktivversand und kein eingerichtetes Klaviyo. Antworten auf freie E-Mails werden noch nicht automatisch importiert: Kunden verwenden das Supportformular, Partner antworten im Portal.

## Testcheckliste nach dem Upload

1. Kleinen/großen Einstieg öffnen, Flughafen ändern, lokale Anbieter-Vorauswahl und ausgebuchte Alternativen testen.
2. Überdachtes Angebot mit Höhenangabe suchen. Zu hohes Fahrzeug muss blockieren. Ladepaket buchen und Teilbestand prüfen.
3. Extras mobil vollständig durchscrollen, Gesamtpreis und Gutschein prüfen, Privat/Firma sowie Fahrzeugangaben erfassen. Vor Absenden per Stift ändern.
4. Lesbare VPM-Nummer und Supportlink kontrollieren. Support mit falscher E-Mail bleibt neutral; richtige Anfrage erscheint beim zugeordneten Partner.
5. Mit eigenem Partnerlogin antworten. Fremde Partner, Kundenadressen und Plattformgutscheine bleiben unzugänglich.
6. Preis/Bestand/Zeiten speichern: Bestätigung erscheint, aktueller Bereich bleibt offen. Belegung nach Art/Woche/31 Tagen prüfen.
7. Kundenstorno und Anbieterabsage getrennt testen, Betrag vor Bestätigung prüfen. Keine offenen Hintergrundaufträge als abgeschlossen behandeln.
8. Anschließend separat den eingeschränkten Mailversand und gegebenenfalls Stripe-Testcheckout samt Webhook/Worker auf Hostinger testen.

## Lokal belegt und noch offen

Automatische Tests: isoliertes WordPress mit Schemaaufbau, bestehende Finanz-/Gutschein-/Mandanten-/Bedingungstests, neue Journey-/Mail-/Testkassenfälle, gleichzeitiger Zugriff auf den letzten Stellplatz, Datenbanksicherung/-wiederherstellung in Wegwerf-CI sowie Desktop-/Mobil-Browserablauf. Prüfberichte: `BOOKING-JOURNEY-PRUEFUNG.json`, `BOOKING-JOURNEY-BROWSER-PRUEFUNG.json` und `BOOKING-JOURNEY-CI-PRUEFUNG.json`. Bestehende Standortprofile und Schema-/Rechtstextausnahmen wurden separat geprüft.

Offen bleiben echte Herstelleradapter, tatsächliche Stripe-/Mailzustellung auf Hostinger, Produktiv-Zahlungsfreigabe, Klaviyo, automatischer Import eingehender Mailantworten sowie weitere Ausbaustufen aus dem Komplettcheck (z. B. minutengenaue Extra-Termine, erweiterte Auswertungen, Mehrstandortverwaltung eines einzelnen Partners). Das Update behauptet keine vollständige Umsetzung aller 100 Auditpunkte.
