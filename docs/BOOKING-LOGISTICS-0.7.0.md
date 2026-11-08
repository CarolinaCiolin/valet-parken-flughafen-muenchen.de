# Buchungskern 0.7.0: Partnerzuständigkeit, Anfahrt und Kommunikation

Stand 8. November 2026. Neue Betreiberentscheidung: **Der ausführende Partner verantwortet Rechnungen, Belege, Stornos und operative Buchungsfragen. Kundenkommunikation wird über die Plattform-Versandstrecke organisiert.** Keine eigene Rechnungsnummerierung/PDF-Erstellung durch die Plattform. [Portal und Buchungsablauf 0.6.0](BOOKING-PLATFORM-0.6.0.md) gelten weiterhin; keine Livefreischaltung oder Installation auf Hostinger.

## Partnerangaben im Portal

Unter `/?vpm_partner_portal=1` → Standorte, Übergabe und Buchungssupport. Bearbeitung nur für zugeordnete Inhaber/Mitarbeiter/Support, Viewer liest nur. Angaben:

- Telefonnummer für Kundenfragen zur Buchung und zur Rechnung.
- Interne Partner-E-Mail für Buchungs-/Stornoanfragen.
- Interne Partner-E-Mail für Rechnungen/Belege.
- Drei getrennte Orte: Parkplatz/Stellplatzadresse, Schlüsselübergabe vor Abreise, Fahrzeugrückgabe nach der Reise.
- Je Ort: Bezeichnung DE/EN, vollständige Adresse beziehungsweise Terminal/Treffpunkt, optionales GPS-Koordinatenpaar, mehrzeilige Anfahrts-/Übergabehinweise DE/EN.

Dadurch wird eine Parkplatzadresse nicht versehentlich zum Abgabeort am Flughafen erklärt. Hinweise wie Terminal, Ebene, Treffpunkt, Zufahrt oder notwendiger Anruf kommen vom Partner; es werden keine solchen Angaben erfunden. Fehlende englische Hinweise fallen auf den vorhandenen deutschen Text zurück, keine automatische Übersetzung. Im Portal werden fehlende zentrale Kontakt-/Übergabeangaben als zu ergänzen ausgewiesen. Bestehende Demo-/Legacyangebote werden dadurch nicht automatisch gesperrt; vollständige Daten sind vor einer echten Kundenfreigabe zu kontrollieren.

## Karten und Pins

Ein Pin wird zunächst über Breiten-/Längengrad eingegeben; kein interaktiver Karteneditor und kein kostenpflichtiger Geocodingdienst. Koordinaten werden paarweise und innerhalb gültiger Grenzen geprüft, Dezimalkomma oder Dezimalpunkt akzeptiert. Ohne Pin wird die Adresse als Routenziel verwendet.

Es entstehen feste Google-Maps-Routenlinks, keine frei vom Partner eingebbaren Skript-/Weiterleitungsadressen. Keine eingebettete Karte, kein Kartenbild, keine Places-API und kein Kartenaufruf beim Laden der Seite. Erst ein bewusster Klick öffnet Maps mit dem Ziel. Die Wahl des Startpunkts und Navigation übernimmt Maps. Links übertragen keine Buchungsreferenz oder Kunden-E-Mail. Telefonkontakt öffnet die gewählte Partnernummer nach Kundenklick.

## Buchung und Kundeninformationen

Kontakt- und Ortsangaben werden bei der Anfrage in deren Snapshot übernommen. Ein späteres Bearbeiten des Partnerprofils schreibt vorhandene Buchungen nicht stillschweigend um. Nach bestätigter und bezahlter Buchung enthält das Ereignis `booking.confirmed` die Angaben für die Bestätigungsmail: Partner, Supportnummer, getrennte Ziele, Anweisungen und Routenlinks.

Der geschützte Kundenzugang zeigt diese Fahrhinweise ebenfalls. Bei noch unbestätigten/unbezahlten Anfragen werden keine endgültigen Fahrhinweise ausgegeben; der Supportkontakt kann bereits angezeigt werden. Interne Buchungs-/Rechnungs-E-Mails bleiben aus der Kundenansicht entfernt. Kunden können dort weiter einen Stornowunsch übermitteln.

Bei geänderten Treffpunkten kann ein berechtigter Partner/Supportmitarbeiter für eine konkrete bestätigte Buchung **Aktuelle Fahrhinweise an Kunden übermitteln** wählen. Das übernimmt bewusst die neuen Daten, protokolliert eine Revision und erzeugt `booking.instructions_updated`. Preise, Rabatt und andere Finanzdaten bleiben unverändert. Identische Wiederholung erzeugt keine zweite Mitteilung. Keine automatische Sammelbenachrichtigung aller bestehenden Buchungen; bei vielen betroffenen Reisenden muss die Bearbeitung vorerst organisiert werden.

Das sind Versanddaten/-ereignisse, noch keine zugestellten E-Mails. Vor produktiver Nutzung sind Absenderdomain, Versanddienst, Zustellbarkeit und Mailvorlagen einzurichten. `sender_role: platform` legt den Kommunikationsweg im Vertrag fest; ein tatsächlicher SMTP-From-/Reply-To-Absender wird weiterhin in der zentralen Versandkonfiguration bestimmt, nicht durch den Partner. Auch eventuelle automatische Stripe-E-Mails sind beim späteren Kontosetup abzustimmen.

## Dokumente und Stornos

Die bisherigen generischen Ereignisse `invoice.requested` und `invoice.adjustment_requested` werden ab 0.7.0 durch Partneraufgaben ersetzt:

| Ereignis | Empfänger / Bedeutung |
| --- | --- |
| `partner.invoice_required` | interne Rechnungs-E-Mail des Partners; nach operativer Bestätigung und Zahlung, einmal pro Buchung |
| `partner.credit_note_required` | interne Rechnungs-E-Mail; nach verarbeitetem Refund, gegebenenfalls Korrektur-/Stornobeleg durch Partner |
| `partner.cancellation_requested` | interne Buchungs-E-Mail, ersatzweise Rechnungs-E-Mail; Kunde hat einen Stornowunsch eingereicht |
| `booking.instructions_updated` | Kunden-E-Mail über die Plattform-Versandstrecke; neue bestätigte Fahrhinweise |
| `operations.partner_contact_missing` | Plattform-Klärungsereignis bei fehlendem zuständigen Partnerkontakt; keine behauptete Zustellung |

Partneraufgaben enthalten `recipient`, `customer_email`, Buchungs-/Partnerreferenz, kundenrelevante Beträge sowie `sender_role: platform` und `document_issuer: partner`. Der bekannte signierte `document.available`-Rückkanal kann später vom Partner bereitgestellte Dokumente zuordnen und den Kundenversand anstoßen. Die Plattform erzeugt dabei keine eigene steuerliche Rechnung. Ein direkter PDF-Upload im Partnerportal und ein konkreter Rechnungssoftware-Adapter sind **noch nicht** implementiert.

Offene Kunden-Stornowünsche erscheinen nun beim zuständigen Partner im Portal. Ein Wunsch ist keine Stornobestätigung. Bei verwalteten Buchungen wartet der technische Ablauf weiterhin auf die Partnerbestätigung; erst dann wird storniert und gegebenenfalls die Zahlungserstattung angefordert. Bestätigter Storno schließt den Vorgang in der Kundenansicht ab. Die fachliche Zuständigkeit des Partners ersetzt nicht die notwendige technische Ausführung einer Erstattung im Zahlungsdienst.

Die Entscheidung über den Rechnungsaussteller ist jetzt Partner. Vertrags-/Steuer-/Stripe-Kontomodell und konkrete Regeln müssen vor Livebetrieb trotzdem konsistent eingerichtet werden. Der zuletzt besprochene Privat-/Firmenkunden-Datenschritt ist noch nicht umgesetzt: vollständige Firmen-/Rechnungsdaten sind folglich noch nicht automatisch vorhanden. Partneraufgabe bedeutet keine bereits vollständige Rechnungsautomatisierung.

## Speicherung, Update und Prüfungen

Schema 7 ergänzt die geschützte Partner-Logistik. Bestehende Buchungen ohne diese Informationen werden nicht automatisch umgeschrieben. Neue Snapshots übernehmen die zum Zeitpunkt ihrer Erstellung bekannten Daten. Keine Änderungen an Münchner Affiliate-Texten, URLs oder Tracking-IDs.

```bash
VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-logistics-local.sh
```

Nur lokale Loopback-Testinstallation mit leeren eigenen Buchungstabellen. [Backendprüfungen](BOOKING-LOGISTICS-PRUEFUNG.json) · [Browserprüfungen](BOOKING-LOGISTICS-BROWSER-PRUEFUNG.json). Testadressen/-kontakte sind keine realen Angebote; keine echten Nachrichten, Zahlungen oder Kartenabrufe. Zusätzlich bestehende Prüfungen und isoliertes CI/Backup.

Vorschauen: [Partnerformular](../zwischenstand/buchungskern/partnerportal-uebergabe-test.png), [Kundenansicht mit Routen](../zwischenstand/buchungskern/kundenansicht-anfahrt-test.png). Installierbares Paket `pakete/vpm-booking-core.zip`, Version 0.7.0. Vor Update sichern und zunächst testen; kein Hostinger-Upload durch GitHub-Push.
