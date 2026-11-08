# Kommunikation und sichere Tests – Buchungskern 0.10.0

Stand: 8. November 2026. Keine Hostinger-Installation, keine echte Mailzustellung, keine Klaviyo-Verbindung. Erweiterung der [Partnerbedingungen/Aktionen](BOOKING-POLICIES-0.9.0.md).

## Verbindliches Kontaktmodell

Echte Kunden-E-Mail-Adressen bleiben bei der Plattform. Partner bekommen Buchungsreferenz und eine zufällige Vermittlungsadresse pro Buchung. Partnerrollen erhalten keine Newsletterlisten oder Adress-Exporte. Telefonnummern können später für notwendige operative Kontakte gezielt freigegeben werden; die vollständige Kundendatenerfassung einschließlich Telefonnummer ist noch ein eigener Ausbau.

Implementiert:

- `Tenants::bookings` ersetzt die echte E-Mail serverseitig für Partner/Mitarbeiter mit Mandantenzugang. Dadurch sind auch die vorhandenen Partner-REST-Antworten geschützt. Plattformadministratoren sehen weiterhin die echten Daten. Partner dürfen niemals Administratorrechte oder globale Buchungsverwaltungsrechte erhalten.
- `partner.*`-Benachrichtigungen enthalten `customer_contact` als Vermittlungsadresse und keine echte `customer_email`. Normale und URL-kodierte bekannte Kundenadressen werden in Freitexten entfernt; weitere offensichtliche Mailadressen werden maskiert. Interne Empfängeradresse des Partners bleibt erhalten.
- Herstelleraufrufe im vorhandenen Worker bekommen über `Broker::adapter` eine eingeschränkte Datenansicht mit Alias statt Kundenadresse. Interne Anfrage-Hashes, Stripe-Zahlungskennungen und persönliche Gutscheincodes werden nicht übergeben. Zukünftige Adapter müssen diese gemeinsame Ausgabe verwenden, auch bei Änderungen/Statusabgleich.
- Partner können im Portal Nachrichten zur Buchung einreichen. Sie werden verschlüsselt gespeichert und erzeugen zunächst ausschließlich eine Plattform-Prüfaufgabe. Kein ungeprüfter Kundenversand.
- Plattformverwaltung unter Werkzeuge → VPM Buchungsverwaltung → Kommunikation prüft, bearbeitet und genehmigt/ablehnt. Erst eine Freigabe erzeugt `booking.partner_message_approved` mit echtem Empfänger ausschließlich in der privaten Plattform-Outbox. Wiederholung erzeugt keine zweite Nachricht. Direkte URLs/Mailadressen sind in diesem Freitextpfad blockiert; notwendige Karten-/Buchungslinks kommen aus gesonderten kontrollierten Systemvorlagen. Inhaltliche Prüfung bleibt notwendig, weil Aufforderungen zur Umgehung auch ohne URL formuliert werden können.
- Direkte URLs und E-Mail-Adressen in neuen/geänderten Angebotsbeschreibungen durch Partner werden abgelehnt. Das ersetzt keine inhaltliche Prüfung gegen verschleierte Werbung.
- Partneränderungen an Anfahrt/Kontakten gehen in eine Freigabewarteschlange. Bis zur Prüfung bleiben die bisherigen Daten aktiv. Nach Freigabe gelten neue Angaben für neue Buchungen; bestehende Snapshots bleiben unverändert. Damit können Anfahrtshinweise nicht als ungeprüfter Werbe-/Umgehungsmailkanal verwendet werden.
- Strukturierte Meldungen wie Stornowunsch und bestätigter Storno bleiben automatisch erzeugte Versandereignisse. Eine Nachricht allein verändert keinen Buchungs-/Erstattungsstatus.

## Vermittlungsadressen sind noch keine eingerichtete Mailzustellung

Optionaler Serverwert `VPM_BOOKING_RELAY_DOMAIN`, später beispielsweise `messages.valet-parkservice.com`. Eine Adresse enthält einen zufälligen, nicht vom Kunden abgeleiteten Bezeichner. Standard derzeit ausschließlich `relay.example.invalid` als eindeutiger Testplatzhalter. Bestehende Aliasadressen bleiben stabil, auch wenn später eine Domain konfiguriert wird; Testdaten daher nicht als echten Buchungsbestand übernehmen.

DNS/MX, Absenderauthentifizierung, ein Versandadapter und Empfang/Antwortverarbeitung sind noch nicht eingerichtet. Eine eingegebene Domain richtet keinen Mailserver ein. Die Domainoption ist keine Aussage zur Zustellbarkeit. Produktionsfreigabe erst nach geprüftem Ende-zu-Ende-Routing; niemals als Ausweichlösung doch echte Kundenadressen an Hersteller senden.

Für die erste Version ist der kontrollierte Weg: Kunde stellt Frage über geschützten Buchungszugang → Partner sieht bereinigte Anfrage im Portal → Partner antwortet im Portal → Plattform prüft → Versanddienst sendet im Plattformnamen an den Kunden. Für Antworten auf echte E-Mails ist zusätzlich ein Empfangsdienst notwendig: Er muss Absender authentifizieren, Signaturen/zitierte Kopfzeilen/Anhänge kontrollieren, die Buchung sicher zuordnen und unbekannte beziehungsweise verdächtige Nachrichten zurückhalten. Kein ungeprüftes Catch-all-Forwarding und kein offenes Mailrelay. Das ist noch nicht implementiert.

Die Rechnungszuständigkeit bleibt beim Partner. Die private Plattform kann Belege an den echten Empfänger zustellen; der Partner braucht dafür keinen Zugriff auf dessen Mailadresse. Rechnungs-/Herstellersysteme müssen Aliasempfänger unterstützen. Rechnungsdateien, notwendige Rechnungsdaten und deren Übermittlung sind gesondert zu prüfen, sobald die tatsächliche Anbindung feststeht.

## Klaviyo

Die vorhandenen DOI-/Widerrufs-, Welcome-/Treuegutschein- und Aktionsereignisse bieten eine Grundlage für eine Klaviyo-Anbindung. Dafür ist ein konkreter serverseitiger Adapter beziehungsweise eine eigene Vermittlung nötig: Die bestehende signierte Outbox ist ein eigener Ereignisvertrag, kein direkt konfigurierter Klaviyo-API-Client. Kontofunktionen, aktuelle API-Anforderungen und gegebenenfalls Freigaben für transaktionale Nachrichten sind vor Einrichtung zu verifizieren.

Empfohlene Aufteilung:

- Klaviyo für bestätigte Newsletterkontakte, Aktionen, persönliche Rabattcodes und Marketingautomatisierung. Widerrufe in beide Richtungen synchronisieren; Partner bekommen keinen Zugang zu diesem Konto oder diesen Listen.
- Buchungsbestätigung, operative Partnerkommunikation, Storno und Belege über einen zuverlässigen transaktionalen Versandweg unter der Plattformdomain. Er darf nicht von einer Newsletter-Einwilligung abhängen. Klaviyo dafür nur verwenden, wenn die konkrete transaktionale Funktion des Kontos geprüft und eingerichtet ist.
- SMTP über vorhandenes Hosting oder ein separater API-Versandweg sind mögliche Umsetzungen; keine konkrete Dienstleistung wurde gebucht oder verbunden. Keine Zugangsdaten im öffentlichen Repository.

## Testweg ohne echte Kunden/Partner

Separate zugriffsgeschützte WordPress-Installation auf temporärer Domain, HTTPS und keine Indexierung. Kein Kopieren von Testbuchungen als echten Produktionsbestand. Eigene Partnerzugänge mit reinen Partnerrechten und ein eigener Plattformadministrator.

| Testpartner | Angebot | Geprüfter Fall |
| --- | --- | --- |
| DEMO Anbieter A, München | 20 überdachte Plätze, davon 4 Ladeplätze; Valet | normale Buchung, Extras und Rabatt |
| DEMO Anbieter B, München | Außenstellplätze; Shuttle 06:00–22:00 | außerhalb Servicezeit ausgeschlossen |
| DEMO Anbieter C, München | Tiefgarage, zunächst keine freie Kapazität | ausgebucht und bewusste Alternativwahl |

Namen/Preise/Adressen ausschließlich klar gekennzeichnete Phantasiedaten. Kundenadressen unter `example.invalid`, keine erfundenen erreichbaren Telefonnummern. Automatische lokale Tests legen ähnliche synthetische Datensätze an und entfernen sie anschließend; dieses Update installiert keinen dauerhaften Demo-Datenbestand. Über die vorhandenen Verwaltungsformulare lässt sich der interaktive Testbestand bewusst anlegen.

Zahlung in zwei Stufen:

1. Vorhandene private serverseitig freigegebene Demo-Zahlung und abgefangene Zahlungs-HTTP-Aufrufe. Kein Geldfluss und keine echten Kartendaten. Fehler/Erstattungen lassen sich in den lokalen Tests simulieren.
2. Nach Fertigstellung des öffentlichen Checkouts Stripe-Testmodus mit offiziellen Testdaten, Test-Connect-Zuordnung und signierten Test-Webhooks. Erfolgreiche Zahlung, Ablehnung, Abbruch, doppelte Ereignisse, ausstehende Partnerbestätigung und Erstattung prüfen. Der echte öffentliche Zahlungsabschluss ist weiterhin offen.

Mail ebenfalls zweistufig:

1. Lokales Mail-Testpostfach ohne Außenversand, beispielsweise Mailpit, für die spätere Testtransport-Anbindung. Alle Empfänger bleiben fiktiv; Bestätigungen, Antworten und Stornos müssen im Testpostfach sichtbar werden. Mailpit wurde hier noch nicht installiert/angebunden. Bisher prüfen die Tests verschlüsselte Versandereignisse und Freigaben, keine tatsächlich zugestellten E-Mails.
2. Anschließend nur erlaubte eigene Testpostfächer für echte Zustelltests. Einen serverseitigen Testempfängerfilter einrichten, damit keine Nachricht versehentlich an fremde Adressen geht. Absender, Reply-To, Kopfzeilen, Rückläufer und Antworten prüfen; keine echten Newsletterverteiler verwenden.

Prüffälle: Partner sieht Adresse weder im HTML noch REST; Hersteller erhält nur Alias; Kunde tippt E-Mail in Nachricht; Partner versucht externen Buchungslink; Freigabe/Ablehnung; doppelte Einreichung/Freigabe; Partnerstorno mit festem Systemtext; unbekannte Antwortadresse; Kontaktänderung ohne Freigabe. Testberichte: [Backend](BOOKING-BROKER-PRUEFUNG.json), [Browser](BOOKING-BROKER-BROWSER-PRUEFUNG.json). Lokal `scripts/test-booking-broker-local.sh` mit leeren eigenen Tabellen, zusätzlich isoliertes CI.

## Grenzen

Das erschwert Adressübernahme und unkontrollierten Mailversand. Vollständiger Umgehungsschutz ist bei erlaubtem Telefonkontakt und persönlicher Fahrzeugübergabe nicht technisch erzwingbar. Partnerregeln, Protokollierung und Prüfung auffälliger Stornos bleiben relevant. Auch Stripe-/Zahlungsdienstansichten und Exporte, öffentliche Produkt-/Freitextbeschreibungen, Rechnungsdateien und zukünftige Herstellerfelder müssen vor Livebetrieb auf Umgehung und Datenabfluss geprüft werden; dafür gibt es noch keine vollständige Inhaltsfreigabe oder automatische Erkennung. Neue Mailstrecken müssen Rückläufer, Spam, Anhänge und Zustellfehler behandeln. Kein voll eingerichtetes Postfachsystem, kein Sicherheitszertifikat und keine Livefreigabe durch dieses Update.
