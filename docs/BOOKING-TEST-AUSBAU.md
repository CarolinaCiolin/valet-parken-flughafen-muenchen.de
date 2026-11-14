# Ausbau nach dem betreuten Test auf valet-parkservice.com

Anforderungsstand: 15. November 2026. Grundlage ist das Betreiberfeedback zum Buchungskern 0.11.0. Dieses Dokument ist eine Umsetzungsvorgabe, keine Behauptung bereits implementierter Funktionen. Die Münchner Affiliate-Website wird nicht umgebaut.

## Beobachtungen und Fehler

- Angebotssuche, Extras, Anfrageanlage, reservierter Gutschein, unveränderter Partneranspruch und Gutscheinfreigabe nach unbezahltem Storno wurden durch den Betreiber durchgespielt. Keine echte Zahlung oder Mailzustellung getestet.
- Der Storno wurde durch den Fünf-Minuten-Worker abgeschlossen. Sichtbare Buchungshistorie und verständliche Partnerbearbeitung fehlen.
- Speichern von 0 Gesamtplätzen und 0 Ladeplätzen führt beim Testpartner zur pauschalen Meldung „Zugriff oder Änderung nicht möglich“ und zum Verlust der Partneransicht. Ursache der Ablehnung ist noch nicht bewiesen. Nicht vorschnell als fehlende Mitgliedschaft oder aktiven Bestand erklären. Ungültige Eingaben dürfen die autorisierte Partnerauswahl nicht entfernen. Technische Ursache gezielt reproduzieren und sichere fachliche Meldungen ausgeben.
- Negative Zahlen sind in Eingabefeldern möglich; serverseitige Ablehnung allein ist keine gute Bedienung. Gesamtbestand >= 0; 0 <= Ladebestand <= Gesamtbestand. Bestehende Reservierungen bei Bestandsänderungen berücksichtigen und Konflikte erklären.
- Doppelte Darstellung in einem Screenshot war laut Betreiber ein Fehler der Screenshot-Erweiterung, kein bestätigter Darstellungsfehler. 8 Euro waren bewusst gewählter Testpreis.
- Die echte Kundenadresse im Plattformkonto und der Alias im Partnerkonto sind beabsichtigt. Eine fremde Partnerauswahl wurde mangels eindeutig identifizierter Sitzung nicht als Sicherheitslücke nachgewiesen; getrennte Konten und zwei Partner müssen dies prüfen.

## Verwaltung und Navigation

- Plattform: Dashboard, Partner, Flughäfen, Buchungen, Kommunikation, Gutscheine/Aktionen, Statistiken und Einstellungen klar trennen.
- Nach Anlegen eines Partners eine gespeicherte Partnerübersicht zeigen. Beim Öffnen eines Partners alle Verwaltungsaktionen eindeutig auf diesen Partner begrenzen. Name und Flughafen dauerhaft sichtbar; globale Aktionen separat.
- Flughäfen zentral bearbeiten; Aktionen/Gutscheine können auf Flughäfen eingegrenzt werden. Bestehende Zuordnungen nicht durch Umbenennung verlieren.
- Partnerportal: Übersicht, Buchungen, Nachrichten, Stellplatzbestand, Parkpreise, Services und Zeiten, Extras, Stornos, Standort/Anreise, Statistiken, Anbindung und Kontoeinstellungen.
- Gespeicherte Datensätze zuerst auflisten. Darunter „Hinzufügen“; erst danach Bearbeitungsformular öffnen. Für vorhandene Angebote und Tarife ausdrücklich „Bearbeiten“. Gespeicherte Werte, Status und Wirkung erkennbar machen.
- Nach Speichern im aktuellen Partner und Abschnitt bleiben; eindeutige Erfolgsmeldung „Änderungen gespeichert“. Bei Fehlern Eingaben erhalten und konkrete sichere Fehlermeldung anzeigen. Dashboard nur über ausdrückliche Navigation.
- Angemeldetes Konto und Rolle anzeigen. Administratormodus und Partneransicht unterscheidbar halten.

## Inventar, Parkpreise und kombinierbare Leistungen

- Vier Stellplatzarten: Tiefgarage, Außenstellplatz, überdachter Stellplatz, Parkhaus. Je Art eigener Preis, Bestand und enthaltene Ladeplätze; nicht angebotene Arten deaktivierbar.
- Gespeicherte Bestände mit Belegung/freien Plätzen für einen wählbaren Zeitraum darstellen; Wochenübersicht vorbereiten. Belegung aus tatsächlichen überschneidenden Reservierungen ableiten, nicht pauschal Buchungen summieren.
- Stellplatzart und Parkpreis getrennt von Valet/Shuttle und Extras verwalten. Valet und Shuttle belegen denselben physischen Pool. Nicht denselben Stellplatz für unterschiedliche Leistungen doppelt zählen.
- Vorgeschlagene Bedeutung „kombinierbare Angebote“: eine Stellplatzart + angebotener Service + mehrere verfügbare Extras. Mehrfahrzeugbuchungen sind dadurch noch nicht vereinbart; Betreiberklärung dazu läuft separat.
- Euroeingaben mit zwei Nachkommastellen und deutschem Dezimalkomma; intern weiter ganzzahlige Centbeträge. Kein float-basiertes Geldrechnen. Alle Geldfelder einschließlich Service, Nachtzuschlag, Extras und Regeln beachten. Bestehende API-Centwerte nicht stillschweigend umdeuten.
- Manuelle Eingabe von Parkpreis und Valet-Gebühr tatsächlich durch die Oberfläche testen; Funktionen sind im Backend vorhanden, Bedienbarkeit ist nicht bestätigt.
- Grundtarife erklären: „Preis je angefangene 24 Stunden“ multipliziert den Parkpreis mit den benötigten Zeitabschnitten; „Pauschalpreis für den gesamten Aufenthalt“ berechnet den Grundpreis einmal.
- Saisonpreise als abweichende Tagespreise im Zeitraum erklären. Dauerpakete als Gesamtpreis für genau N berechnete Aufenthaltstage erklären, z. B. 8 Tage für 80 Euro. Langzeitrabatte als separate prozentuale Regel ab einer Aufenthaltsdauer erklären. Vorrang und Nichtkombination verständlich darstellen, mit berechnetem Beispiel.

## Suche und Einbettung

- Flughafenangabe im normalen Shortcode ist eine änderbare Vorauswahl, keine Sperre. Ohne Flughafenparameter zunächst „Flughafen auswählen“, insbesondere Startseite, Blog und allgemeine Buchungsseite.
- Bestehende explizite Festlegung nur bei bewusstem Bedarf als separate Option behandeln; serverseitige Scope-Prüfung passend zur gewählten Betriebsart erhalten.
- Suchfelder: Flughafen, Stellplatzart (einschließlich „Alle Arten“), Service, Anbieter optional, Reisedaten. Anbieter nur des ausgewählten Flughafens. Standard „Alle Anbieter vergleichen“. Bei Flughafenwechsel unpassende Anbieterwahl zurücksetzen.
- Karten-CTA kann Flughafen und Anbieter vorauswählen; beide bleiben änderbar. Parameter serverseitig prüfen, keine fremden Partner/Flughäfen oder nicht freigegebenen Angebote einschleusen.
- Mehrere passende Anbieter auflisten; ein Anbieter genügt. Bei keinem Treffer tatsächlich verfügbare Alternativen am selben Flughafen und für denselben Zeitraum anbieten. Änderungen von Stellplatzart, Service oder Anbieter klar benennen und vom Nutzer auswählen lassen. Kein stiller Flughafenwechsel.
- Kalender beim Klick ins Datumsfeld öffnen; manuelle Datumseingabe erhalten. Uhrzeit manuell plus erreichbare Uhrzeitauswahl. Tastatur und mobile Browser prüfen.
- Kompakte Hero-Einbettung als Option neben einer Überschrift; ausführlicher Checkout erhält ausreichend Platz. Bestehende Gutenberg-/Elementor-Einbettung erhalten.
- Suche nach Neuladen wiederherstellbar machen; Preise und abgelaufene Angebote erneut serverseitig abrufen. Keine Zusage alter Verfügbarkeit. Keine Kunden-, Zahlungs- oder Zugangstoken ungeschützt in URLs/Browserstorage ablegen.

## Servicezeiten

- 24/7-Schalter. Sonst Wochentag geöffnet/geschlossen, getrennte Zeitfelder Von/Bis mit Uhrzeitauswahl, zusätzliche Intervalle und „Für alle Tage übernehmen“.
- Nachtfelder „Nachtzuschlag gilt ab“ und „Nachtzuschlag gilt bis“; Zuschläge für Abgabe und Rückgabe getrennt.
- Übernachtintervalle verständlich erklären. Bestehende Zeitprofile verlustfrei bearbeiten; keine stillen Öffnungszeitänderungen bei Update.
- Fachliche Vorgaben bleiben: Abflug minus drei Stunden und Landung plus 30 Minuten müssen in den Servicezeiten liegen. Keine Zeiten/24-7 bedeutet ohne Zeitbeschränkung; deaktivierter Service wird nicht angeboten. Flughafenzeitzone und Sommerzeit beachten.

## Kunden und Buchungsidentifikation

- Privat/Firma, Name, Telefon, Fahrzeugkennzeichen und Marke. Geschäftskunde mit Firmenname, Anschrift, Rechtsform, Umsatzsteuer-ID soweit zutreffend und gegebenenfalls abweichendem Reisenden/Ansprechpartner. Pflichtfelder sachgerecht, kein pauschaler Zwang zu einer USt-ID bei jedem Unternehmen.
- Shuttle mit Personenanzahl, geprüft gegen zulässige Partnergrenzen.
- Partnerabhängiger Hinweis auf Fahrzeugschein und gültigen Ausweis bei Übergabe; kein Dokumentenupload vereinbart.
- Lesbare Buchungsnummer zusätzlich zur internen UUID. Buchungsname, Kennzeichen, Übergabe/Rückkehr und Nummer überall bei Bearbeitungsaktionen anzeigen. Nummer ist kein Zugangsgeheimnis.

## Storno und Gutscheine

- Partner kann nach Prüfung stornieren. Bestätigungsschritt zeigt verbindlich gespeicherte Buchungsbedingungen, bezahlten Betrag nach Gutschein, Gebühr und Erstattungsbetrag. Anschließend Kapazitätsfreigabe und idempotente Erstattung über die Zahlungsanbindung; bei externen Systemen deren Bestätigung berücksichtigen. Ausstehende/fehlgeschlagene Schritte sichtbar halten.
- Historie von Eingang, Entscheidung, Storno und Erstattung; Zeitpunkt des Kundenwunsches bestimmt Stornogebühr. Kein automatischer Preiswechsel durch später geänderte Bedingungen. Unbezahlte Anfrage: keine Erstattung.
- Gutscheine nur Plattform. Partneranspruch wird nicht durch unseren Rabatt vermindert.
- Grüne positive Meldung mit Symbol und tatsächlichem Euro-/Prozentvorteil; rote Fehler mit Symbol und verständlicher Erklärung. Status reserviert/eingelöst/freigegeben unterscheiden.
- Plattform sieht Code und Rabatt; Partner sieht Kundenbetrag und Plattformrabatt, nicht den persönlichen Code. Stand der Einlösung nachvollziehbar.
- Prüfen/Eintragen reserviert keinen Code. Anfrage kann reservieren; bei unbezahltem Storno/Abbruch beziehungsweise Ablauf wieder freigeben. Parallele Einlösung schützen.
- Newslettercodes an bestätigte berechtigte Adresse binden. Weitere normalisierte Kundenmerkmale als Missbrauchssignale vorbereiten. Gleicher Name/Firma allein darf nicht pauschal unterschiedliche berechtigte Kunden sperren. Keine Kartennummern speichern; soweit verfügbar Stripe-Fingerprint verwenden. Datenminimierung und Löschfristen berücksichtigen. Regeln vor Livebetrieb festlegen.

## Zugang, Support und Nachrichten

- „Partnerportal – Login“ mit direktem Einstieg nach Anmeldung; Passwort zurücksetzen/Abmelden. Für Partner weder WP-Dashboard noch Adminleiste, direkte Verwaltungsaufrufe serverseitig sperren. Plattformadmins behalten Verwaltung. Notwendige WordPress-/REST-Routen nicht pauschal blockieren.
- Andere Partner dürfen nur bei expliziter autorisierter Mitgliedschaft zugänglich sein. Test: zwei Partner, zwei Konten, direkte GET/POST/REST-Zugriffe gegen fremde IDs, keine Daten in HTML/JSON/Statistik/Fehlern.
- Partner-Kunden-Kommunikation über Plattform, Kundenadresse bleibt verborgen. Neue Vorgabe: kein genereller manueller Freigabezwang pro Nachricht; Verlauf und Monitoring auf Plattformseite. Missbrauchshinweise und abweichende Moderationsregeln ausdrücklich und nachvollziehbar gestalten. Eine Freigabesimulation oder Outbox ist kein tatsächlicher Versand.
- Öffentliches Supportfenster mit Buchungsnummer, Anlass und kurzer Nachricht. Keine Offenlegung von Buchungen allein anhand einer Nummer. Sicherer Link an hinterlegte Adresse für geschützten Verlauf, Rate-Limits und neutrale Eingangsbestätigung; kein Kundenkonto erforderlich.
- Automatische Zuordnung zum Partner, Antwort im Portal, Kundenzustellung über Plattform. Stornoanliegen erzeugt einen nachvollziehbaren Wunsch; freie Nachricht allein löst keine ungeprüfte Erstattung aus.
- Mailtransport, sichere eingehende Antworten/Relay, öffentlicher Stripe-Checkout und reale Erstattungen bleiben konkret einzurichten und separat mit Phantasiedaten/Sandbox zu testen. Klaviyo folgt nach funktionierendem technischen Mailtest. Keine Livekonten oder Drittzustellung in dieser Testphase.

## Abnahmetest nach Umsetzung

Bestehende Daten und API-Verträge erhalten. Mit getrennten Partnerkonten zwei Anbieter und vier Stellplatzarten einschließlich ausgebuchter Kombinationen durchspielen. Geldkonvertierung, Saison-/Dauer-/Rabattvorrang, gleichzeitige letzte Plätze, Servicezeiten/DST, erfolgreiche und fehlgeschlagene Speicherung, Suchwechsel, Wiederladen, Gutscheine und Storno/Erstattung gezielt prüfen. Desktop/Mobil, Tastatur, Gutenberg und Elementor Free berücksichtigen. Keine Hostinger-Funktion oder echte Zustellung nur aus lokalen Tests ableiten.
