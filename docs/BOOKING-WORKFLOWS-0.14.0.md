# Buchungskern 0.14.0 – weniger Rückfragen und betreute Ausnahmefälle

Weiterentwicklung für die getrennte Testinstallation **valet-parkservice.com**. Kein erforderliches Update der Münchner Affiliate-Seite. Noch kein Upload auf Hostinger, kein echter Versand und keine Zahlung durch den Agenten.

## Update

Nach Sicherung von Dateien und Datenbank unter **Plugins → Neues Plugin hinzufügen → Plugin hochladen** die Datei `pakete/vpm-booking-core.zip` hochladen und das bestehende Plugin ersetzen. Version **0.14.0**, Datenbankschema **13**. Eigene Tabelle `cases` wird ergänzt; vorhandene Buchungen und Preissnapshots bleiben erhalten. Für ein Downgrade die Sicherung verwenden. Kein Inhaltsimport und keine automatische Erzeugung von Testpartnern.

Die bestehende Einrichtung aus [0.13.0](BOOKING-JOURNEY-0.13.0.md) gilt weiter: Suche klein/groß, eigene mehrstufige Buchungsseite, kein Kundenkonto, Ladebedarf nur durch Ladepaket, Partnerrechte und Kundendatenabschirmung.

## Geführte Einrichtung und Preisprüfung

Das Partnerdashboard zeigt zum Fortschritt den nächsten noch offenen Einrichtungsschritt und konkrete Links zu problematischen Einstellungen. Geprüft werden unter anderem:

- kein aktivierter Service;
- fehlende Fahrzeughöhenbegrenzung für überdachte Angebote;
- fehlender Parkpreis oder physischer Bestand;
- angebotene Ladepakete ohne Lade-Teilbestand;
- pausiertes Angebot, ausstehende Plattformfreigabe oder ungeklärte Verspätung.

Die Preis-/Bestandsprüfung im Fortschritt betrachtet nun alle eigenen Angebote; ein einzelner korrekt eingestellter Tarif verdeckt keine weiteren fehlenden Tarife mehr. Der Fortschritt ersetzt weiterhin keine Prüfung einer echten Hersteller- oder Zahlungsanbindung und ist keine automatische Veröffentlichung.

Oben in **Preisverwaltung** steht eine getrennte **Beispielreise**. Partner wählt eigenes Angebot, Service, Abflug und Rücklandung. Der Server berechnet mit der vorhandenen Preislogik die angefangenen 24 Stunden, Parkpreis, Aufenthaltsrabatt, Servicegebühr und getrennte Nachtzuschläge. Zusätzlich werden Servicezeiten, Bestand und Aufenthaltsbedingungen geprüft. Berechneter Parkplatzzeitraum und Flughafenzeit werden angezeigt.

Die Vorschau speichert keine Buchung, erzeugt keine Zahlungsanforderung und reserviert keinen Stellplatz. Extras und plattformfinanzierte Gutscheine sind ausdrücklich nicht enthalten. Für externe Systeme muss der Herstelleradapter die Preise liefern; die lokale Vorschau wird dort nicht angeboten. Ein gültiger Vorschaupreis allein bestätigt noch keine vollständige Konfiguration oder spätere Verfügbarkeit.

## Support nach Anlass

Öffentlicher Support weiterhin unter `/?vpm_support=1`, mit Buchungsnummer, Buchungs-E-Mail und neutraler öffentlicher Bestätigung. Keine Buchungsdaten werden öffentlich anhand einer Nummer abgefragt oder ausgegeben.

Nach der zuständigen Stelle folgt ein Unteranlass:

| Stelle | Unteranlässe |
| --- | --- |
| Anbieter | Anfahrt/Treffpunkt, Verspätung, Übergabe/Abholung, Rechnung/Beleg, sonstige Anbieterfrage |
| Storno | Stornowunsch |
| Plattform | Zahlung/Erstattung, Gutschein, Technik, sonstige Plattformfrage |

Passende Hinweise helfen vor dem Absenden: Anfahrt in der Bestätigungsmail nachsehen, für Verspätung Flugnummer/neue Zeit/Reiserichtung nennen, Rechnung beim ausführenden Anbieter anfordern. Zahlungsfragen fragen ausdrücklich nicht nach Kartennummern oder Zugangsdaten. Hinweise und Kategorien sind deutsch/englisch verfügbar. Der Server validiert die Zuordnung unabhängig von JavaScript; ältere Clients ohne Kategorie bleiben kompatibel.

Eine Nachricht ändert weder Reisezeit noch Buchung oder Preis. Ein Stornowunsch löst keine unmittelbare Erstattung aus. Der Partner beantwortet die Anfrage im bestehenden Portal; die Antwort geht direkt in die private Kunden-Versandwarteschlange. Keine einzelne Plattformfreigabe erforderlich. Plattformanliegen bleiben vor dem Partner verborgen.

## Bearbeitungsziele und Erinnerungen

Unter **Werkzeuge → VPM Buchungsverwaltung → Automatisierung & Klärungsfälle**:

- Vorgeschlagene interne Ziele: Erinnerung nach **4 Stunden**, Eskalation nach **24 Stunden**, jeweils änderbar.
- Bei zum Eingang naher Anreise beziehungsweise Rückgabe innerhalb 24 Stunden verkürzt sich das Ziel auf höchstens **1 Stunde / 4 Stunden**.
- Es sind verstrichene Stunden, keine Geschäftszeiten oder gegenüber Kunden zugesagten Antwortfristen. Partner sehen das interne Antwortziel in Flughafenzeit.
- **Partner-Erinnerungen sind zunächst ausgeschaltet.** Bewusst aktivieren, wenn der eingeschränkte Testversand eingerichtet ist. Aktivierung erzeugt zunächst nur Versandereignisse; ein Worker und Transport müssen tatsächlich laufen.
- Pro Anliegen und Stufe wird höchstens ein Ereignis erzeugt. Wiederholte Worker-Läufe erzeugen keine wiederholten Erinnerungsereignisse derselben Stufe.
- Offene Stornowünsche haben eine eigene Erinnerung und Eskalation. Eine beantwortete Nachricht blendet einen noch nicht ausgeführten Storno nicht aus.

Es gibt keine automatische Absage wegen einer verstrichenen Frist und keine automatische zusätzliche Abbuchung. Erstattungen und Herstellerbestätigungen behalten ihre geprüften Abläufe. Keine neue Abbruchwerbung.

## Zentraler Ausnahmebereich

Der Worker erfasst und aktualisiert Fälle für:

1. überfällige Partnerantworten;
2. offene Plattformanliegen;
3. überfällige Stornobearbeitung;
4. fehlgeschlagene Partneraufträge;
5. Versandereignisse nach ausgeschöpften Wiederholungen;
6. unklare Zahlungserstellung beziehungsweise bezahlte Vorgänge ohne Partnerbestätigung;
7. fehlgeschlagene oder klärungsbedürftige Erstattungen.

Der Bereich zeigt lesbare Buchungsnummer, Erfassungszeit, passende Prüfanweisung und, soweit zugeordnet, den Link in den betreffenden Partnerbereich. **„Prüfung übernommen“** protokolliert die Übernahme, lässt den Fall aber offen. Sobald die erfasste Ursache behoben ist, schließt der nächste Abgleich den Fall automatisch. Ein Problem, das erneut auftritt, wird wieder geöffnet. Der Abgleich selbst verändert keine Buchungs-, Zahlungs-, Erstattungs- oder Bestandswerte.

Erfassung erfolgt automatisch über den bestehenden Fünf-Minuten-Worker oder manuell über **Klärungsfälle jetzt abgleichen**. Begrenzte Batches mit fortlaufendem Cursor verhindern, dass alte offene Vorgänge neuere Anfragen dauerhaft verdrängen. Bei größerem Rückstau dauert eine vollständige Runde mehrere Läufe. Die Oberfläche zeigt den letzten Worker-Lauf; für einen verlässlichen Betrieb muss auf Hostinger der Servercron eingerichtet und geprüft werden. Ein stillstehender Worker kann selbst keine E-Mail-Benachrichtigung versenden.

„Versand übergeben“ ist weiterhin kein Nachweis des Eingangs im Postfach. Der native Testadapter leitet Nachrichten ausschließlich an das eigene eingestellte Testpostfach weiter. Aktuell freigegeben: **kontakt@valet-parken-flughafen-muenchen.de**. Ohne aktivierten Transport verbleiben Ereignisse in der Warteschlange. Der tatsächliche SMTP-/Klaviyo-/Stripe-/Herstellerbetrieb wurde hier nicht eingerichtet.

## Nach dem Upload testen

1. Partnerlogin öffnen: nächster Einrichtungsschritt und Hinweise prüfen. Fehlende Höhenangabe, Ladebestand oder Preis ergänzen und direkt im Bereich weiterarbeiten.
2. Unter Preise eine Beispielreise rechnen. Uhrzeit in das Nachtfenster legen, Service ändern, Preisbestandteile kontrollieren. Bestand darf sich dadurch nicht ändern.
3. Support über einen Buchungslink öffnen. Rechnung, Verspätung und Plattformzahlung auswählen und die jeweiligen Hinweise prüfen.
4. Mit passender Buchungsnummer/E-Mail eine synthetische Anfrage senden. Als Partner beantworten; Kundenadresse bleibt verborgen. Plattformanliegen nur als Administrator öffnen.
5. In der Plattformverwaltung den Abgleich starten. Plattformanliegen erscheint; Übernahme bleibt im gleichen Abschnitt und löst den Fall nicht versehentlich.
6. Für echte Testmails erst den geschützten Einzelpostfach-Testversand einrichten und danach Erinnerungen bewusst aktivieren. Keine fremden Testempfänger verwenden.

Automatisierte Fristprüfungen arbeiten lokal mit zurückgesetzten Zeitstempeln; für einen kurzen Oberflächentest keine Produktivtabellen bearbeiten. Die bestehende Worker-/Retry-Logik ist keine Auslieferungsgarantie bei einem fehlerhaften Servercron oder nicht erreichbarem externen Dienst.

## Prüfungen und verbleibende Arbeit

Neue Backendprüfung mit drei synthetischen Partnern: `scripts/test-booking-workflows.php`, Bericht `BOOKING-WORKFLOWS-PRUEFUNG.json`, Gesamtbericht `BOOKING-WORKFLOWS-CI-PRUEFUNG.json`. Sie prüft Zuordnung, Preisvorschau, Einrichtungsfehler, Erinnerungen, Eskalationen, Datenschutz, unabhängige Stornofälle, Zahlungs-/Versand-/Erstattungsprobleme und einen Rückstau mit mehr als 200 offenen Anliegen. Hinzu kommen die bestehenden Finanz-/Bestands-/Gutscheinprüfungen, die isolierte WordPress-CI, Upgradeprüfung und der erweiterte Desktop-/Mobil-Browserablauf.

Noch ausstehend: tatsächliche Einrichtung/Zustellung auf Hostinger, Herstellerschnittstellen, Stripe-Testkonto samt Webhook, längerer Betrieb mit realistisch gepflegten Partnerdaten und unabhängige Nutzertests. Die Bearbeitungsziele sind Startwerte. Pausen-/Geschäftszeiten für Supportteams, echte E-Mail-Eingangsverarbeitung, freie Umbuchung nach Abschluss und weitergehende Auswertungen sind damit noch nicht umgesetzt. Die native Mailvorlage ist weiterhin zunächst deutsch; die englische Redaktion bleibt vor englischem Produktivversand offen.
