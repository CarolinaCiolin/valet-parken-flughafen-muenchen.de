# Dachmarke – eigene Buchungsplattform und späteres Anbietertool

Planungsstand 8. November 2026. Gilt für die geplante Dachmarke valet-parkservice.com, nicht als Änderung der bestehenden Affiliate-Seite München. Keine Buchungssoftware oder Zahlungsanbindung implementiert/aktiviert. Der bestehende Theme-Kern bleibt die Inhalts-/Darstellungsebene.

## Anforderungen des Betreibers

- Standortseiten für die relevanten Flughäfen, zunächst zwei bis drei ausgewählte ausführende Partner pro Flughafen.
- Eigene Buchungsoberfläche, keine zugekaufte externe Buchungsplattform.
- Anbieter verantworten ihre Kapazitäten und operative Stornobearbeitung; möglichst keine doppelte Systempflege.
- Eigene Rabattcodes/Aktionen auf Kosten der Plattform, ohne den vereinbarten Partnererlös ungefragt zu kürzen.
- Automatische Provisionsverrechnung und Verrechnung von Stornos, kein nachträgliches Einsammeln offener Provisionen als Regelprozess.
- Optional später ein eigenes mandantenfähiges Betriebswerkzeug als Hauptsystem der Partner, zusätzlich mit SaaS-Tarif. Das ist eine spätere Produktausbaustufe, keine bereits beschlossene Tarifhöhe.

## Systeme als Recherchekandidaten, keine Marktanteilsrangliste

| Kandidat | Einordnung | Für die Partnerauswahl zu klären |
| --- | --- | --- |
| AeroParker, https://www.aeroparker.com/ | Online-Buchung/Prebooking im Flughafen-/Parkbereich | Gibt es für den konkreten Partner freigegebenen Zugriff auf Angebote, Verfügbarkeiten, Reservierungen und Stornos? |
| Rezcomm, https://www.rezcomm.com/ | Flughafen-E-Commerce einschließlich Parkbuchungen | Deckt die konkrete Installation die benötigten Buchungs-/Storno-Funktionen ab und darf ein externer Vertriebskanal angebunden werden? |
| SKIDATA, https://www.skidata.com/ | Zutritt, Tickets und Parkraumbewirtschaftung | Gibt es zusätzlich einen nutzbaren Buchungs-/Reservierungsdienst, nicht nur Schranken-/Zutrittsdaten? |
| Scheidt & Bachmann entervo, https://www.scheidt-bachmann.de/ | Parkraumbewirtschaftung/Parkhausbetrieb | Welcher externe Buchungsdienst ist angebunden und wer stellt dessen Schnittstelle bereit? |

Diese Beispiele sind relevante technische Produktfamilien im Flughafen-/Parkumfeld, keine belegte Häufigkeitsrangfolge bei kleinen deutschen Valet-Anbietern. Ein bekannter Herstellername bedeutet weder automatisch öffentliche API noch kostenfreien Zugang. Vertrags-/Schnittstellenrechte und Entgelte pro Installation prüfen. Parkos-/ParkVia-Vertriebspartnerschaft beweist keinen Zugriff auf das interne Betriebssystem eines Parkplatzanbieters. Individuelle Buchungssoftware, WordPress-Lösungen oder rein manuelle Abläufe sind ebenfalls möglich.

Aktuelle Hersteller-/Zahlungsdokumentation konnte aus dieser Cloud wegen CONNECT-403 nicht abgerufen werden. Keine aktuellen API-Endpunkte, unterstützten Vertragsmodelle, Gebühren oder Herstellerfreigaben als verifiziert behandeln. Bei Partnern zuerst konkrete Systemnamen, Version/Modell und technischen Ansprechpartner erheben.

## Integrationsstrategie

Eine Plattform, mehrere Adapter. Pro Partner explizit eine Betriebsart:

1. API-Anbindung: Preis/Verfügbarkeit aus dem vorhandenen System, verbindliche Reservierung und Buchung/Storno im selben System. Partner arbeitet weiter dort.
2. Anfragebetrieb: verbindliche Bestätigung durch Partner; keine unbestätigte Anfrage als sofort sichere Buchung verkaufen. Keine automatische Zahlungserfassung vor gesicherter Ausführung.
3. Eigenes Betriebssystem: Partner pflegt Kapazität, Leistungen und operative Vorgänge in unserem späteren Tool; andere Vertriebswege brauchen ebenfalls Synchronisierung, sonst bleibt Doppelpflege.

Die Anbindung wird nicht allein anhand des Herstellernamens aktiviert. Eine API, die nur alte Buchungen lesen kann, genügt nicht. Pflichtfähigkeiten getrennt erfassen: quote, availability, hold/reserve, confirm, cancel, status, optional amend und Webhooks. Bei fehlendem Hold muss der Anbieter eine sichere alternative atomare Verfügbarkeitsprüfung/Buchung gewährleisten. Sonst Anfragebetrieb.

## Buchungsfluss – zunächst ein Partner pro Buchung

- Standort und Reisedaten erfassen; Zeiten mit Flughafen-Zeitzone und Sommerzeit eindeutig speichern, intern UTC plus ursprüngliche Zeitzone.
- Angebote/Preis/Regeln serverseitig vom ausgewählten Partner abrufen. Rabatt und Partnerauszahlung daraus berechnen; keine ungesicherte Anzeige als Verfügbarkeitsgarantie.
- Kapazität verbindlich halten; Zahlung autorisieren, Buchung beim Partner bestätigen, anschließend Zahlung erfassen. Tatsächliche Reihenfolge hängt von den Fähigkeiten der Partner- und Zahlungs-API ab.
- Zwischen externen Systemen keine atomare Gesamttransaktion unterstellen. Für abgelaufenen Hold, erfolgreiche Zahlung bei fehlgeschlagener Buchung sowie gesicherte Buchung bei fehlgeschlagener Zahlung explizite Ausgleichs-/Abbruchschritte und eine Ausnahme-Warteschlange vorsehen.
- Erst den bestätigten Gesamtzustand als Buchung bestätigen. Doppelte Requests/Webhooks über Idempotenzschlüssel abfangen, Signaturen prüfen und Anbieterzugehörigkeit beachten.
- Kapazitätspflege erfolgt im führenden System des Anbieters. Lokale Zwischenspeicherung ist keine zweite unabhängige Kontingentquelle.

## Zahlung und automatische Provision

Kandidat ist ein Marktplatz-Zahlungsdienst, beispielsweise Stripe Connect. Relevante Dokumentations-Einstiege: https://docs.stripe.com/connect/destination-charges und https://docs.stripe.com/connect/charges-transfers . Aktuelle Details vor Implementierung prüfen; noch keine Anbieterentscheidung.

Eigene Buchungssoftware spart nicht die Zahlungsabwicklung: Der Zahlungsdienst übernimmt Zahlungsverkehr, Partner-Onboarding/KYC und zulässige Auszahlungen. Transaktions- und mögliche Plattform-/Kontogebühren bleiben. Keine eigenen unregulierten Treuhandkonten oder unbegrenzt gehaltene Kundengelder als Architektur annehmen. Zulässiger Auszahlungsaufschub hängt von Land, Vertragsmodell und Zahlungsdienst ab.

Zu prüfen: Anbieter als Vertragspartner für die Parkleistung, Plattform als Vermittler, Zahlung über verbundene Partnerkonten mit definierter Plattformvergütung. Wer rechtlich Zahlungsempfänger, Händler und Erstattungs-/Chargeback-Verantwortlicher ist, folgt nicht automatisch aus einem technischen Connect-Kontotyp. Vertragsmodell und Zahlungsaufbau gemeinsam prüfen.

Konstruktionsziel: Partner bekommt seinen vereinbarten Betrag, Plattformanteil wird bei der Zahlungsabwicklung einbehalten/verrechnet. Auszahlungen möglichst nach Leistungserbringung oder einem geeigneten vereinbarten Freigabezeitpunkt, soweit der Zahlungsdienst dies erlaubt. Zahlungsfreigabe nicht mit tatsächlichem Bankeingang verwechseln.

## Plattformfinanzierter Gutschein – vereinfachtes Beispiel

Reines Verteilungsbeispiel, keine Aussage über Umsatzsteuer/Rechnungsstellung; Zahlungsgebühren noch nicht abgezogen.

| Position | Betrag |
| --- | ---: |
| Regulärer Angebotspreis | 100 EUR |
| Vereinbarte Plattformprovision (hier 20 % des regulären Preises) | 20 EUR |
| Vereinbarter Partnerbetrag | 80 EUR |
| Von der Plattform finanzierter Rabatt | 10 EUR |
| Kundenzahlung | 90 EUR |
| Partnerbetrag bleibt | 80 EUR |
| Verbleibender Plattformanteil vor Gebühren | 10 EUR |

Der Rabatt wird nicht ungefragt vom Partnerbetrag abgezogen. Die Provisionsbasis, Mindestvergütung, Zusatzleistungen und Stornogebühren müssen vertraglich eindeutig sein. Rabatt nicht als negativen Zahlungsbetrag/Application Fee implementieren. Übersteigt die Subvention den verfügbaren Plattformanteil, braucht es tatsächlich finanzierte Deckung aus Plattformmitteln über einen vom Zahlungsdienst unterstützten Weg. Keine automatische Gutschrift aus nicht vorhandenem Guthaben unterstellen.

Couponregeln: Gültigkeit/Zeitzone, Flughafen/Partner/Produkt, Mindestwert, Maximalbetrag, Budget, erlaubte Kombinationen, Nutzungszahl insgesamt und pro Kunde sowie Reservierung/Freigabe bei Abbruch. Prüfung und Verbrauch serverseitig atomar; kein reines JavaScript oder WordPress-Transient als verlässlicher Zähler. Preis-/Rabatt-/Provisionsregel beim Kauf versioniert festhalten.

## Stornos und Erstattungen

Partner bzw. dessen führendes System entscheidet anhand der vereinbarten Regeln über operative Stornos. Unser Tool kann die Kundenanfrage übermitteln und Status anzeigen; Geldflüsse werden erst anhand bestätigter, berechtigter Ereignisse abgeglichen. Partner darf nicht beliebige Buchungen anderer Anbieter ändern.

- Vollstorno des Beispiels: Kunde erhält bei vollständig erstattungsfähiger Buchung die tatsächlich gezahlten 90 EUR zurück, nicht 100 EUR; Partneranspruch 80 EUR und Plattformanteil 10 EUR rückabwickeln.
- Teilstorno: Rabatt anteilig zuordnen; Preisanteil, Provisionsbasis, verbleibender Partneranspruch und tatsächlich erstatteter Kundenbetrag getrennt berechnen. Keine pauschale Provision prozentual vom falschen Zahlungsbetrag abziehen.
- Je nach Zahlungsmodell ist eine Kundenerstattung keine automatische Rückholung des Partnertransfers oder der Plattformgebühr. Application-Fee-Erstattung/Transfer-Reversal explizit und idempotent steuern. Fehler-/Retryzustände aufzeichnen.
- Wurde Partnergeld bereits ausgezahlt und lässt sich nicht zurückholen, sind Reserve, zulässige Verrechnung mit späteren Erlösen oder ein anderer vertraglich gedeckter Ausgleich nötig. Vollständige Vermeidung von Forderungen lässt sich nicht garantieren.
- Nicht erstattete PSP-Gebühren und Chargebacks gesondert zuordnen. Unklare oder fehlgeschlagene Vorgänge bleiben sichtbar in einer Ausnahme-Warteschlange statt als erledigt zu gelten.

Ein transaktionales Journal hält mindestens Angebotssnapshot, Kundenzahlung, Autorisierung/Erfassung, Provisionsbasis, Plattformsubvention, Partneranspruch, Transfer, Rückabwicklung und Gebühr fest. Zahlungsstatus und operativer Buchungsstatus sind getrennte Zustandsmaschinen. Regelmäßiger automatischer Abgleich mit PSP und Anbieter ergänzt Webhooks; ein fehlender Webhook darf keine offene Forderung verschwinden lassen.

## Späteres eigenes Anbietertool als SaaS

Als getrenntes Modul derselben Plattform planen: Mandanten-/Benutzerrechte, Kapazitäts-/Sperrtage, Buchungskalender, Fahrzeug-/Übergabeabläufe, Stornos, optionale Zustandsfotos und Datenexport. Vor Fotos/Fahrzeugdaten Löschfristen, Zugriff und Datenschutzrollen konkretisieren. Keine zusätzlichen Fahrzeugdaten sammeln, nur weil das spätere System es könnte.

Dann braucht ein Partner unser Portal nicht zusätzlich zu einem anderen Haupttool. Andere Buchungskanäle müssen trotzdem angebunden werden, falls sie parallel genutzt werden. Ein SaaS-Tarif ist ein separater Anspruch neben Vermittlungsprovision: eigene Vertrags-/Rechnungsposition, nicht unbemerkt vom Kundengutschein oder Parkpreis abziehen. Auch bei Tarifende bleiben bestehende Buchungen und Abrechnungsansprüche geordnet bearbeitbar/exportierbar.

Eigene Software führt zu Betriebsaufgaben: Support, Verfügbarkeit, Backups, Sicherheit, Zahlungsabgleich und Eskalationen. Automatisierung reduziert manuelle Routine, ersetzt aber nicht die Verantwortung für Ausnahmen.

## Nächster Umsetzungsschritt

Noch keine Live-Zahlungsintegration bauen. Für die ersten Partner System-/API-Fähigkeiten erheben und das Handels-/Zahlungsmodell bestätigen. Danach eigener technischer Booking-Core (Plugin oder separater Dienst), getrennt vom WordPress-Theme. Adapter, Preis-/Couponlogik und Zahlungsjournal können dann unabhängig von konkretem Anbieter aufgebaut und mit Testdaten geprüft werden. Vor Produktivstart Sandbox-Buchung, Doppelanfrage, Kapazitätskonflikt, Gutscheinbudget, Storno/Teilstorno, Transfer-/Erstattungsfehler und Mandantentrennung testen.
