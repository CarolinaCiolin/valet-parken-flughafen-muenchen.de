# Buchungskern 0.12.0 – Ausbau nach dem betreuten Test

Nur das Plugin `vpm-booking-core.zip` auf der separaten Testinstallation valet-parkservice.com aktualisieren. Bestehende Partner, Buchungen, Tarife und Mitgliedschaften bleiben erhalten. Das Theme und die Münchner Affiliate-Seite werden nicht geändert. Datenbankschema bleibt 11; zusätzliche Kundendaten werden verschlüsselt im bestehenden Buchungssnapshot hinterlegt.

## Installieren und wieder testen

1. Sicherung der Testinstallation erstellen. In WordPress Plugins → Neues Plugin hinzufügen → Plugin hochladen → `vpm-booking-core.zip`, vorhandene Version ersetzen.
2. Pluginversion 0.12.0 prüfen. Bereits gesetzter Demomodus und die bewusste Frontend-Freigabe bleiben bestehen.
3. Im Partnerkonto die Kapazität speichern und den angezeigten Bestand sowie die Bestätigung prüfen. Bei Ablehnung erscheint eine konkrete Fehlerkennung; diese mitteilen, nicht mehrfach blind absenden. 0 Gesamtplätze und 0 Ladeplätze sind zulässig, soweit keine Reservierungen entgegenstehen. Ladebestand darf den Gesamtbestand nicht übersteigen.
4. Parkpreise in Euro eingeben, z. B. `12,50`. Nach dem Speichern den gespeicherten Wert prüfen. Altdaten sind weiterhin interne Centwerte und werden nicht umgerechnet oder überschrieben. Alte API-Clients senden weiterhin Integer-Centwerte; nur ausdrücklich markierte UI-Formulare werden konvertiert.
5. Vier Stellplatzarten als getrennte physische Angebote anlegen und bepreisen. Zusätzliche Services und Extras werden dazu kombiniert; Valet und Shuttle belegen denselben Pool.
6. Unter Servicegebühren & Zeiten: 24/7 oder Tageszeiten einstellen. Montag kann für alle Tage übernommen werden. Zeitspannen über Mitternacht sind möglich. Shuttle-Personengrenze und Dokumentenhinweis bei Bedarf konfigurieren. 0 bei Personengrenze bedeutet noch keine partnerseitige Grenze; technische Obergrenze 99, keine Zusage verfügbarer Shuttle-Sitzplätze.
7. Suche nach Flughafen, Stellplatzart und optional Anbieter prüfen, einschließlich ausgebuchter Arten und bevorzugtem Anbieter ohne Angebot. Alternativen werden aus tatsächlich verfügbaren Angeboten desselben Flughafens und Services erzeugt. Keine automatische Umbuchung oder Änderung von Reisedaten.
8. Privat- und Geschäftskundenschritt testen. Name, Telefon, Kennzeichen und Fahrzeugmarke sind erforderlich; Firma mit Anschrift und Rechtsform, USt-ID optional. Abweichender Reisender ist optional. Shuttle fragt Personen ab. Es wird kein Dokument hochgeladen und keine Rechnung durch die Plattform erstellt.
9. Partnerbuchung mit lesbarer Nummer `VPM-…`, Kundennamen, Fahrzeug und Relayalias prüfen. Eigene Kunden-Mailadresse bleibt nur dem Plattformadministrator zugänglich.
10. Storno zuerst prüfen, dann bewusst bestätigen. Preview legt keinen Stornoauftrag an. Der Hintergrundworker verarbeitet weiterhin im Fünf-Minuten-Takt. Ergebnis steht zusätzlich unter abgeschlossenen Stornos. Bezahlte Vorgänge verwenden die vorhandene Rückerstattungslogik; öffentliche Stripe-Zahlung und echte Erstattung wurden hier nicht eingerichtet oder live getestet.

## Shortcodes und Karteneinstieg

Änderbare Münchner Vorauswahl:

```text
[vpm_booking_form airport="MUC" service="valet" flow="steps"]
```

Allgemeine Auswahl ohne voreingestellten Flughafen:

```text
[vpm_booking_form flow="steps"]
```

Kompakt für eine Spalte neben Hero-Text:

```text
[vpm_booking_form airport="MUC" service="valet" flow="steps" layout="compact"]
```

Bevorzugter Anbieter, weiterhin änderbar:

```text
[vpm_booking_form airport="MUC" partner="1" flow="steps"]
```

`partner` ist die eigene Partner-ID, kein Name und keine Affiliate-ID. Alternativ können Karten auf die tatsächlich angelegte Buchungsseite mit `vpm_airport=MUC&vpm_partner=1` verlinken. Der Parameter wird gegen freigegebene Partner des gewählten Flughafens geprüft. Vorhandene redaktionelle Affiliate-Karten werden nicht automatisch umgestellt.

Ein explizites `airport_mode="fixed"` bleibt als bewusst gewählte Integrationsoption erhalten. Ohne diese Option ist eine IATA-Angabe jetzt nur eine Vorauswahl. Gutenberg enthält die neuen Einstellfelder, Elementor verwendet das Shortcode-Widget.

Nach Neuladen wird die zuletzt aktiv abgeschickte Suche zehn Minuten lang aus dem Sitzungsspeicher wiederhergestellt und erneut beim Server angefragt. Nur Suchparameter, keine Kundendaten, Codes, Zahlungsdaten oder Kundenzugangstoken. Das speichert keine Preis- oder Verfügbarkeitszusage und übernimmt keine Buchung automatisch. Ein direkter Karteneinstieg hat Vorrang vor dieser Wiederherstellung.

## Partner und Plattform

Partnerlogin: `https://valet-parkservice.com/?vpm_partner_login=1`.

Zugeordnete Partnerbenutzer gelangen nach erfolgreichem Login direkt ins Portal. WordPress-Dashboard-Aufrufe werden zurückgeleitet; die Adminleiste wird ausgeblendet. Zugriffe auf fremde Partner werden weiter serverseitig in GET/POST/REST geprüft. Plattformadministratoren behalten die gesamte Verwaltung. Es wird noch keine Partner-Subdomain eingerichtet. Die Anmeldung verwendet WordPress-Authentifizierung und Passwortzurücksetzung; eine Hostinger-Prüfung von Login-Härtung, TLS und Serverkonfiguration ist kein Bestandteil lokaler Tests.

Ein Partnerklick in der Plattformverwaltung öffnet eine auf diesen Partner begrenzte Stammdaten-/Zugangsseite und Links zu seiner Verwaltung. Nach Anlegen und Aktualisieren bleibt man im Partnerkontext. Partnerkonto, Rolle und Dashboard-Link sind sichtbar.

Angebote und Preise erscheinen als gespeicherte Datensätze; Hinzufügen/Bearbeiten erfolgt in aufklappbaren Formularen. Beim Speichern bleibt der Abschnitt erhalten. Ungültige Eingaben entfernen nicht mehr pauschal die Partnerauswahl. Die Bestandsübersicht zeigt die nächsten sieben Tage mit täglicher Spitzenbelegung und mindestens freien Plätzen. Eine frei wählbare Wochen-/Zeitraumnavigation ist noch offen.

## Nachrichten

Unter Werkzeuge → VPM Buchungsverwaltung → Kommunikation kann die Plattform „Partnernachrichten ohne Einzelfreigabe über die Plattform übermitteln“ aktivieren. Partner können diese Einstellung nicht ändern. Bestand bleibt zunächst im bisherigen Freigabemodus, damit ein Update keine unbemerkte Versandänderung auslöst.

Im direkten Modus werden neue Nachrichten idempotent protokolliert und als privates Kundenereignis in die verschlüsselte Outbox gestellt. Die Plattform sieht ein Nachrichtenprotokoll. `queued` bedeutet nicht zugestellt. Ohne Versandadapter wird keine reale Mail versendet. Antworten/Inbound-Relay und das öffentliche Supportfenster mit sicherem Kundenzugang sind weiterhin offen. Die Aliasdomain `relay.example.invalid` ist ein Testplatzhalter, kein eingerichtetes Postfach.

## Gutscheine und Storno

Die UI zeigt positive Gutscheinprüfung grün mit tatsächlicher Ersparnis, Fehler rot. Die bestehende serverseitige Berechnung/Einmalsperre bleibt erhalten. Partner sehen Kundenbetrag und plattformfinanzierten Rabatt bei unverändertem Partneranspruch, keinen persönlichen Code. Erweiterte Missbrauchsprüfung anhand weiterer Merkmale oder Stripe-Fingerprints ist noch nicht implementiert.

Die neue Stornoprüfseite zeigt Nummer, Name/Fahrzeug, Zeitraum, bezahlten Betrag, Gebühr und Gesamterstattung. Bestätigung und erneute Betragsprüfung erfolgen serverseitig unter Buchungssperre. Geänderte Beträge führen zu einer Ablehnung, keine stillschweigende neue Belastung. Eine Stornovorschau verändert keine Buchung. Bereits stornierte Vorgänge haben keinen erneuten Stornobutton. Die Historie abgeschlossener Stornos ist eine Liste; eine vollständige Ereigniszeitleiste mit allen Zeitpunkten bleibt offen.

## Prüfung und Grenzen

Neue Tests: `scripts/test-booking-experience.php` und `scripts/test-booking-experience-browser.py`, lokal über `scripts/test-booking-experience-local.sh`. Der frische CI-Lauf enthält die Backend-Suite ebenfalls. Berichte: BOOKING-EXPERIENCE-PRUEFUNG.json und BOOKING-EXPERIENCE-BROWSER-PRUEFUNG.json. Tests laufen ausschließlich auf Loopback mit Phantasiedaten und bereinigen eigene Daten.

Die ursprüngliche Hostinger-Ablehnung bei 0/0 konnte lokal nicht reproduziert werden; gültiges 0/0 und konkrete Ablehnung eines unzulässigen Lade-Teilbestands sind nun mit echtem Partnerlogin und HTTP-Speicherung getestet. Ein Update ersetzt nicht den anschließenden Test auf Hostinger.

Der neue Frontend-Checkout nutzt `/public/requests-v2` mit serverseitig validierten Kundendaten. Der bisherige API-Endpunkt bleibt für Altintegrationen verfügbar und verlangt diese neuen Felder nicht nachträglich. Keine Aussage über öffentlichen Livebetrieb, echte SMTP-/Klaviyo-Zustellung, Herstelleradapter oder Live-Zahlungen.
