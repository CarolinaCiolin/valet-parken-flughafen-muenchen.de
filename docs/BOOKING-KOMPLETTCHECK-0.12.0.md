# Komplettcheck Buchung und Partnerverwaltung – Version 0.12.0

Stand: 15. November 2026. Geprüft wurden der Code von 0.12.0, lokale Darstellungen und gezielte lokale Fehlertests mit Phantasiedaten. Dies ist keine Live-Abnahme der Hostinger-Installation. Plugin-Code und Pakete wurden bei diesem Audit nicht geändert. Nachweise der vier gezielt überprüften Lücken: [BOOKING-AUDIT-0.12.0-REPRO.json](BOOKING-AUDIT-0.12.0-REPRO.json).

P0 = funktional zuerst korrigieren; P1 = nächste wesentliche Bedienungsrunde; P2 = Ausbau nach dem Kernablauf; P3 = spätere Option. Die Liste unterscheidet fehlende Funktionen von der Weiterentwicklung bereits vorhandener Grundlagen.

## Vier konkret geprüfte Lücken

| ID | Priorität | Befund | Erforderliche Korrektur |
| --- | --- | --- | --- |
| F01 | P0 | Nach einer MUC-Suche wurde deren Antwort lokal zurückgehalten. Währenddessen Flughafen auf STR geändert. Nach Freigabe der Antwort erschienen dennoch MUC-Angebote. | Jede Antwort an ihre Suchparameter/Revision binden; alte Antworten verwerfen oder abbrechen. Auswahl nach Änderung ungültig machen. |
| F02 | P0 | Die abschließende Preisübersicht zeigt Partner und Beträge, aber keine vollständige Kontrolle von Flughafen, Flugzeiten, Person/Firma und Fahrzeug. | Vollständige Zusammenfassung mit gezielten Bearbeitungslinks vor Absenden/Zahlung. |
| F03 | P0 | Der Kunde erhält VPM-… als Buchungsnummer. Die Zugangsanforderung akzeptiert hingegen nur die interne UUID. Lokal erzeugte die lesbare Nummer keinen Zugang, dieselbe Anfrage mit UUID erzeugte einen. | Eine einheitliche lesbare Buchungsnummer für Formular, Support, E-Mail und Kundenzugang. Nummer bleibt Identifikation, kein Zugangsgeheimnis. |
| F04 | P0 | Bei einer unbezahlten manuellen Anfrage wurde die technische Platzreservierung testweise ablaufen gelassen. Nach Worker-Lauf blieb die Anfrage requested und der Gutschein reserved. | Ablauf von Anfrage, Bestand und Gutschein konsistent behandeln; ausstehende Zahlungen vorher sicher abgleichen. Kein öffentliches Reservierungszeitversprechen. |

Die bisherigen Tests deckten diese konkreten Randfälle nicht ab. Korrekturen brauchen eigene Regressionstests. Das ist keine Aussage, dass die bereits geprüften Journal-, Berechtigungs- oder Reservierungssperren generell nicht funktionieren.

## Frontend: Einstieg und Suche

- B01 P1: Klarer Buchungsablauf mit sichtbarer Fortschrittsanzeige. Bestehende Schrittüberschrift um Übersicht und gezieltes Zurückgehen ergänzen. Browser-Zurück darf nicht überraschend die Seite verlassen.
- B02 P1: Kurzformular nur für den Einstieg; Ergebnisse und spätere Schritte bekommen ausreichend Breite. Die vorhandene kompakte Variante begrenzt derzeit den gesamten Ablauf auf dieselbe schmale Fläche.
- B03 P1: Flughafen, Stellplatzart und Service als Hauptauswahl. Bevorzugten Anbieter als optionale, weniger dominante Auswahl behandeln. Vorauswahlen sichtbar und änderbar, Änderungen mit klarer Rückmeldung.
- B04 P1: Suchfähige Flughafenauswahl mit Ort/IATA. Standorte ohne buchbare Angebote verständlich markieren; nicht erst nach vollständiger Eingabe unerklärlich leere Ergebnisse zeigen.
- B05 P1: Services passend zum Flughafen/Partner anzeigen oder ihre Nichtverfügbarkeit erklären. Ein vorgewählter Valet-Service darf bei einem Flughafenwechsel nicht unbemerkt unpassend bleiben.
- B06 P1: Stellplatzarten mit konsistenten Icons und kurzen Erklärungen. Außen, überdacht, Parkhaus und Tiefgarage sind Auswahlkategorien, keine Garantie zusätzlicher Eigenschaften.
- B07 P1: Ladeplatz und Ladeleistung unterscheiden. Benötigter physischer Ladeplatz und buchbares Ladepaket müssen fachlich zusammenpassen; keine widersprüchlichen Kombinationen.
- B08 P1: Datum und Uhrzeit klar gliedern, manuelles Tippen und Kalender gleichwertig unterstützen. Automatisches showPicker bei jedem Klick darf die Korrektur einzelner Zeichen nicht behindern.
- B09 P1: Rückreise vor Abflug, fehlende Zeiten und zu kurzfristige Abgabe direkt am betroffenen Feld erklären. Browser-/Tastaturtests für Safari, Firefox, iOS und Android ergänzen.
- B10 P1: Flugzeit, kalkulatorischen Parkzeitraum und verbindliche Übergabezeit sauber unterscheiden. Individuelle Übergabezeit bleibt wie vereinbart in der Bestätigung; Berechnung der Parkdauer trotzdem nachvollziehbar darstellen.
- B11 P1: Ladezustand mit verständlichem Text und sicherer Wiederholung nach Netzwerkfehler/Timeout. Parameter während der Suche kontrolliert ändern können; F01 beheben.
- B12 P1: Einheitlichen Suchfilter statt zweier überlappender Stellplatzfilter vor und nach der Suche. Aktive Kriterien als kompakte Übersicht zeigen, einzeln änderbar.
- B13 P2: Wiederaufnahme je Seite/Widget und Ursprung der Vorauswahl isolieren. Der bisherige Schlüssel nutzt Pfad und Widgetindex; querybasierte WordPress-Seiten können denselben Pfad teilen. Keine fremde Suche in einer anderen Einbettung übernehmen.
- B14 P2: Bei Wiederaufnahme nicht nur frische Ergebnisse zeigen, sondern gültige Auswahl/Extras nach Möglichkeit erhalten. Kundendaten und Zugangstoken nicht ungeschützt im Browser speichern.

## Frontend: Ergebnisse, Extras und Gestaltung

- B15 P1: Einheitliche Angebotskarten mit Anbieter/optional echtem Logo, Stellplatzart, Service, wesentlichen Attributen, Gesamtpreis und klarer Auswahlaktion. Doppelte Angaben aus Angebotsname, Kategorie und Merkmalen reduzieren.
- B16 P1: Preisvergleich standardmäßig nachvollziehbar sortieren, mindestens Gesamtpreis auf-/absteigend. Ergebniszahl und gewählte Kriterien sichtbar machen.
- B17 P1: Kennzeichnen, welche Servicegebühren/Zuschläge bereits enthalten sind und welche Extras optional hinzukommen. Nach Auswahl denselben Gesamtpreis an allen Stellen verwenden.
- B18 P1: Stornoflexibilität und wichtige Einschränkungen vor Auswahl kurz zeigen; Detailregeln aufklappbar. Keine unbelegten Qualitätssiegel oder erfundenen Bewertungen.
- B19 P1: Leere Ergebnisse mit Ursache erklären, soweit bekannt: Stellplatzart, Servicezeit, Vorlauf, Aufenthaltsdauer oder Wunschpartner. Verfügbare Alternativen nach Nähe zur ursprünglichen Auswahl ordnen.
- B20 P1: Alternativen über die bisherige Stellplatz-/Anbieteränderung hinaus: anderer verfügbarer Service bei gleichem Flughafen und Zeitraum, ausdrücklich vom Kunden auszuwählen. Keine automatische Änderung von Flughafen oder Zeiten.
- B21 P1: Extras im bereits vorhandenen eigenen Schritt als hochwertige auswählbare Kacheln gestalten: Name, konkrete Leistung, Preis/Einheit, optional Icon und ausgewählt/nicht ausgewählt. Keine Vorauswahl kostenpflichtiger Extras.
- B22 P1: Laufende Buchungszusammenfassung neben dem Ablauf, mobil kompakt erreichbar. Ersparnis und Gesamtsumme aktualisieren, Änderungen nachvollziehbar machen.
- B23 P2: Abhängigkeiten/Ausschlüsse bei Extras, verfügbare Mengen und Fahrzeugvoraussetzungen. Maximalmenge je Buchung ist keine zeitabhängige Leistungskapazität.
- B24 P2: Mehrere vergleichbare Extras/Pakete, Reihenfolge und bevorzugte Darstellung durch Partner konfigurierbar. Empfehlungen ausschließlich auf tatsächlichen Regeln/Daten, nicht erfundene „andere buchten“-Behauptungen.
- B25 P1: Einheitliche Abstände, Typografie, Flächen und Buttonrollen. Hauptaktion klar von Zurück, Kalender, Details und Entfernen unterscheiden; Rot für destruktive Aktionen gezielt verwenden.
- B26 P1: Mobile Ansicht eigenständig gestalten: zusammengefasste Suchkriterien, gut erreichbare Weiter-Aktion, ausreichend Platz bei Bildschirmtastatur, keine überlangen Karten durch verschachtelte Erklärungen.
- B27 P1: Barrierefreie Fokusführung bei Schrittwechseln und Fehlern; Fehlerübersicht mit Links zum Feld. Nicht nur Farbe als Zustandsinformation.
- B28 P1: DE/EN vollständig und konsistent übersetzen. Aktuell sind einzelne Firmenfelder zweisprachig beschriftet; System-/Statusbegriffe sind noch nicht überall passende Nutzertexte.

## Frontend: Kundendaten, Abschluss und Betreuung

- B29 P1: Bereits vorhandene Privat-/Firmenfelder in Kontakt, Fahrzeug und Rechnungsdaten gliedern. Nur aktuell relevante Pflichtfelder anzeigen; Autofill und passende Eingabetypen ergänzen.
- B30 P1: Telefonnummer mit Ländervorwahl normalisieren; Länderwahl und passende, internationale Adressformate. USt-ID optional und sachgerecht erklären.
- B31 P2: Mietwagen/noch unbekanntes Kennzeichen als partnerabhängige Option mit späterer Ergänzung. Modell, Farbe und Fahrzeugabmessungen nur, wenn operativ benötigt.
- B32 P1: Technische Fahrzeugbeschränkungen strukturiert erfassen und bei Angebot/Buchung prüfen, z. B. maximale Höhe einer Tiefgarage. Keine pauschalen Eigenschaften aus der Stellplatzart ableiten.
- B33 P2: Flugnummer/Terminal als optionale Betriebsangaben; automatische Flugüberwachung ist ein separater Dienst und nicht vorhanden. Kein zusätzlicher Pflichtaufwand ohne Nutzen.
- B34 P0: F02: komplette Abschlusskontrolle, einschließlich Kontakt, Fahrzeug, Flughafen, Service, Flugzeiten, Leistung, Rabatt und Preisbestandteilen. Korrekturen gezielt ermöglichen, ohne alles neu auszufüllen.
- B35 P1: Gutscheinfeld zunächst unaufdringlich unter „Gutschein vorhanden?“. Nach Prüfung Status, Ersparnis und Entfernen/Ändern klar zeigen. Unterschied reserviert/eingelöst/abgelaufen verständlich halten.
- B36 P0/P1: F04: abgebrochene unbezahlte Vorgänge samt Gutscheinen sauber abschließen. Erweiterter Missbrauchsabgleich mit berechtigter Newsletteradresse und weiteren Merkmalen bleibt offen; gleichlautende Namen/Firmen allein nicht pauschal sperren.
- B37 P1: Öffentlichen Stripe-Checkout einbauen, mit tatsächlich verfügbaren Zahlarten, Abbruch/Wiederaufnahme, Fehlerbehandlung und belastbarer Bestätigung. Ein hinterlegter Schlüssel oder privater Demoknopf ersetzt diesen Ablauf nicht.
- B38 P1: Bei verbindlichem Abschluss Vertragspartner, Leistungsumfang, Bedingungen und zahlungspflichtige Handlung eindeutig machen. Aktuelle Anfragephase weiter korrekt benennen.
- B39 P1: Echte Erfolgsseite mit lesbarer Nummer, Status, nächstem Schritt und sicheren Betreuungsoptionen. Keine Buchungsbestätigung behaupten, solange Partnerbestätigung/Zahlung ausstehen.
- B40 P0/P1: F03: Kundenzugang auf lesbare Nummer abstimmen; Zugangslink an hinterlegte Mail, neutrale öffentliche Rückmeldung. Ablauf/Neuanforderung verständlich erklären.
- B41 P1: Öffentliches Supportfenster mit Anlass und Nachricht, sichere Zuordnung und Schutz vor Auskunft über fremde Buchungen. Storno als eigener, nachvollziehbarer Vorgang.
- B42 P1: Echter beidseitiger Nachrichtenverlauf für Kunde und Partner sowie tatsächliche Zustellung. Aktuelle Outbox und direkter Modus sind erst die Grundlage.
- B43 P2: Änderungen von Reisezeit, Fahrzeug oder Extras nach Buchung als kontrollierter Prozess mit erneuter Kapazitäts-/Preisprüfung. Zusatzzahlung nur ausdrücklich, Erstattung nachvollziehbar; keine stille Änderung.

## Partnerverwaltung: Einstieg, Navigation und Tagesgeschäft

- P01 P1: Geführte Ersteinrichtung: Stammdaten → Standort → Bestände → Preise → Services/Extras → Anfahrt → Test → Freigabe. Pflichtlücken und nächster Schritt klar sichtbar.
- P02 P1: Bereits eingegrenzte Partnerverwaltung zu einer vollständigen Partnerakte ausbauen. Stammdaten, Angebote, Buchungen, Kommunikation, Anbindung und Aufgaben an einem Ort, ohne Wechsel zwischen mehreren Werkzeugeseiten.
- P03 P1: Menü fachlich gruppieren: Tagesgeschäft (Übersicht, Buchungen, Nachrichten, Stornos), Angebot (Bestände, Parkpreise, Services, Extras), Betrieb (Anfahrt, Regeln, Anbindung, Team). Keine zwölf gleichgewichtigen langen Navigationspunkte.
- P04 P1: Kontextkopf mit Partner, Flughafen, Konto/Rolle und Abmelden auch bei Scrollen erreichbar. Auf Mobilgeräten klare Navigation statt ausschließlich langem Auswahlfeld.
- P05 P1: Dashboard auf heutige Übergaben, Rückgaben, unbeantwortete Nachrichten, Stornos und Handlungsbedarf fokussieren. Statistiken darunter, technische Informationen separat.
- P06 P1: Dashboardlisten ebenfalls auf lesbare Nummer, Namen, Fahrzeug und direkte Buchungsdetailansicht umstellen. Aktuell stehen dort teilweise noch UUIDs und allgemeine Bereichslinks.
- P07 P1: Zentrale Buchungsdetailansicht: Kunde/Firma, Fahrzeug, Flug-/Übergabeinformationen, Leistungen, Preis, Zahlungsstatus, Nachrichten und Historie. Kontextbezogene Hauptaktionen statt vieler Formulare in einer Tabellenzelle.
- P08 P1: Buchungssuche nach Nummer, Name, Kennzeichen und Telefon; Filter nach Datum, Anreise/Abreise, Stellplatzart, Service und Status. Sortierung und korrekte Seitennavigation.
- P09 P1: Fachliche deutsche Statusanzeigen mit getrennten Dimensionen Anfrage/Buchung, Zahlung und Erstattung. requested/unpaid/pending nicht als Erklärung dem Partner überlassen.
- P10 P1: Unbezahlte, abgelaufene, stornierte und bestätigte Vorgänge sinnvoll trennen. Keine unbezahlte Anfrage als Auftrag behandeln; alte Anfragen nicht unbegrenzt als aktuelle Aufgabe zählen.
- P11 P1: Mobile Buchungskarten oder gut bedienbare Detailansichten statt horizontaler Großtabellen. Telefonnummer antippbar, Tageslisten im Betrieb schnell nutzbar.
- P12 P2: Druck-/CSV-Tageslisten mit den operativ erforderlichen Daten und Rollenprüfung. Keine echten Kunden-Mailadressen über Exporte ausgeben.

## Partnerverwaltung: Inventar, Preise, Services und Extras

- P13 P1: Zentrale Matrix der vier Stellplatzarten: angeboten, Gesamtbestand, enthaltene Ladeplätze, Tarifstatus und Verkauf aktiv/pausiert. Nicht für jeden einfachen Bestand den Zusammenhang mehrerer Reiter rekonstruieren müssen.
- P14 P1: Physischen Bestand vom Verkaufskontingent trennen. „Keine neuen Buchungen“ ist eine Verkaufssperre und darf nicht erfordern, belegte physische Plätze auf 0 zu reduzieren.
- P15 P1: Vorhandene 7-Tage-Spitzenbelegung zum navigierbaren Wochen-/Monatskalender ausbauen, mit frei wählbarem Zeitraum und nachvollziehbaren Einzelbelegungen.
- P16 P1: Zeitbezogene Sperrkontingente für Wartung, anderweitige Belegung oder Betriebsferien. Keine fingierten Kundenbuchungen/Umsätze für solche Sperren erzeugen.
- P17 P1/P2: Physischen Pool, Verkaufstarif und Leistungsbündel klar trennen. Mehrere Tarife oder Servicevarianten desselben Bestands dürfen keinen zusätzlichen physischen Bestand erzeugen.
- P18 P1: Bei Bestandsänderungen aktuelle Reservierungen und Wirkung im gewählten Zeitraum vorher anzeigen. Bei Ablehnung konkrete Ursache und Handlungsmöglichkeit am Feld nennen. Hostinger-0/0-Fall mit aktualisierter Version nachprüfen.
- P19 P1: Gespeicherte Preise als kompakte Tabelle: Stellplatzart, Berechnungsmodell, Grundpreis, Gültigkeit, Status. Einheitliche Bearbeitung pro Datensatz.
- P20 P1: Preisassistent mit konkreten Beispielen für Tagespreis, Aufenthaltspauschale und Dauerpaket. Unwirksame oder widersprüchliche Regeln erklären; saisonale Regeln bei ungeeigneter Preisbasis nicht sinnlos anbieten.
- P21 P1: Preisvorschau mit echten Beispielreisedaten und serverseitiger Aufschlüsselung: berechnete Tage, Parkpreis, Dauerregel, Service, Nachtzuschläge, Extras. Kein abweichender zweiter Preisrechner im Browser.
- P22 P1: Saisonkalender und Dauerregeln visuell bearbeiten; Überschneidungen und Priorität vor dem Speichern zeigen. Langzeitrabatte mit verständlichem „ab Tag/ab Dauer/auf gesamten Parkpreis“ statt technischer Felder.
- P23 P1: Alle Prozent-/Zeiteingaben ebenfalls in Alltagsgrößen darstellen, z. B. 20 % Provision statt Basispunkte und „3 Stunden“ statt ausschließlich Minuten. Interne Einheiten bleiben unverändert.
- P24 P1: Services als einfache Karten aktiviert/deaktiviert, Gebühr, einmal/hin-und-zurück und Öffnungszeiten. Nicht aktive Services einklappen, Wirkung auf die Buchbarkeit zeigen.
- P25 P1: Bestehende Wochenzeiten um Ausnahmen, Feiertage, Betriebsferien und Datumsüberschreibungen erweitern. Nachtschicht über Mitternacht eindeutig darstellen und prüfen.
- P26 P2: Beispiel-Flugzeit eingeben und sehen, ob der Partner angeboten würde und welche Zuschläge gelten. Bestehende -3h/+30min-Regel beibehalten; konkrete Übergabezeit weiter in Bestätigung.
- P27 P1: Extras eigenständig verwalten und dann geeigneten Stellplatzarten/Services zuordnen. Nicht dieselbe Wäschebeschreibung und Bepreisung je Stellplatzangebot neu erfassen müssen.
- P28 P2: Extras nach Einheit/Paket sowie tatsächlicher Verfügbarkeit, Mengen, Abhängigkeiten und gegebenenfalls Tageskapazität steuern. Unterschied inklusive/kostenpflichtig klar zeigen.
- P29 P1: Änderungen an Angeboten/Regeln für bestehende Buchungen sichtbar abgrenzen. Neue Version gilt für neue Angebote, keine stillen nachträglichen Preis-/Bedingungsänderungen.

## Partnerverwaltung: Storno, Kommunikation und Betrieb

- P30 P1: Stornowunsch als sichtbarer Vorgang mit Eingangszeitpunkt, Kunde, Buchung, Grund und gespeicherter Regel. Bereits vorhandene Vorschau um vollständige Ereignishistorie und Fortschritt ergänzen.
- P31 P1: Gebührenregel als verständliche Staffel mit Beispiel darstellen. Normales Kundenstorno, partnerseitige Absage und Nichterscheinen fachlich unterscheiden; eine Anbieterabsage darf nicht blind als gebührenpflichtiges Kundenstorno behandelt werden.
- P32 P2: Rollenabhängige Kulanz/abweichende Erstattung mit Begründung, Höchstbetrag und klarer Kostenzuordnung. Keine unkontrollierte neue Abbuchung.
- P33 P1: Verarbeitung und Erstattung getrennt anzeigen: angefordert, Partnerstorno bestätigt, Erstattung angestoßen, abgeschlossen oder Fehler. Sichere automatische Statusaktualisierung statt wiederholter manueller Neuladungen.
- P34 P1: Nachrichtenpostfach mit Buchungsbezug, Verlauf, ungelesen/offen/beantwortet und direktem Antwortfeld. Alias im Alltag durch „Über Plattform kontaktieren“ ersetzen.
- P35 P1: Versandstatus differenzieren: gespeichert, wartend, an Versanddienst übergeben, tatsächlich zugestellt/fehlgeschlagen soweit nachweisbar. Antworten sicher zuordnen, Kundendaten in Kopfzeilen/Weiterleitungen schützen.
- P36 P2: Benachrichtigungen pro Ereignis und Mitarbeiter einstellen, mit sicheren Standardwerten. Buchung/Zahlung/Storno/Nachricht nicht zu einer undeutlichen Sammelmeldung machen.
- P37 P1: Anfahrtsdaten mit Vorschau der späteren Kundeninformationen; Adresse, Terminal/Treffpunkt, Route und Hinweise verständlich gruppieren. Bestehende Freigabe und eingefrorene Buchungsangaben erhalten.
- P38 P2: Betriebsänderungen oder Ersatztreffpunkte gezielt an betroffene Buchungen kommunizieren, mit Zeitraum, Vorschau und Protokoll; keine stillen Massenänderungen.
- P39 P1: Teamzuordnung über Benutzerauswahl/Einladung statt WordPress- und Partner-IDs. Bestehende Rechte sichtbar auflisten, bearbeiten und entziehen.
- P40 P1/P2: Mitarbeiterrechte für Preise, Bestand, Kommunikation und Storno/Erstattung differenzieren. Gesperrte, ausstehende und entzogene Zugänge verständlich behandeln.
- P41 P1: Partnerlogin einschließlich Fehler, Passwortzurücksetzen, abgelaufener Sitzung und Rückkehr zum Vorgang vollständig gestalten. Bisher sind erfolgreicher Einstieg und Dashboard-Sperre umgesetzt, nicht jeder Loginrandfall als eigene Portaloberfläche.
- P42 P1: Anbindung als klare Betriebsart mit Status, letztem erfolgreichen Abgleich und Datenhoheit je Bereich. Aktuell ist die Architektur vorhanden; konkrete Herstelleradapter fehlen.
- P43 P1: Manuelle Änderungen bei führendem Fremdsystem verhindern oder als explizite Ausnahme führen. Keine zwei konkurrierenden Wahrheiten für Preise und Bestand.
- P44 P2: Statistiken mit Zeitraum, Partner/Standort, Buchung/Zahlung/Storno, Export und verständlichen Beträgen. Erwarteter Anspruch, Journal und tatsächlich ausgezahlte Beträge getrennt darstellen.
- P45 P2: Mehrere Flughäfen/Standorte eines Unternehmens unter einem Partnerkonto vorbereiten, mit getrennten Beständen, Tarifen, Zeiten und Berechtigungen. Aktuell ist ein Partnerdatensatz einem Flughafen zugeordnet.

## Übergreifend

- G01 P1: Plattform-Freigabeprozess mit Bereitschaftscheck: mindestens ein korrekt eingerichtetes buchbares Angebot und vollständige erforderliche Betriebsdaten. Ein aktiver Partner allein erzeugt noch keine Verfügbarkeit.
- G02 P1: Einheitliche Formulare mit passenden Grenzen, Fehlern am Feld, Erhalt aller Eingabetypen und Warnung bei ungespeicherten Änderungen. Derzeit ist die Wiederherstellung nach Fehlern nicht für jede Checkbox/Select-/Textareakombination vollständig.
- G03 P1: Reduzierte Oberflächenlast: Versteckte Bereiche nicht alle mit umfangreichen Formularen, sieben Tagesabfragen je Bestand und vielen Datenbankabfragen laden. Bereichsbezogen und mandantengeprüft nachladen; auf Hostinger messen, keine lokalen Timings als Live-Speed ausgeben.
- G04 P1: Konkrete Tests für Browsernavigation, langsame/vertauschte Antworten, Netzabbruch, parallele Tabs, Sessionablauf und Tastaturbedienung. Bestehende Finanz-/Tenant-/Kapazitätstests beibehalten.
- G05 P1: Alte und neue Anfrage-API vor Produktivstart auf konsistente Pflichtdaten und Statusregeln bringen; Legacy-Kompatibilität bewusst beenden oder kontrolliert begrenzen.
- G06 P1: Stripe-/Mail-/Systemstatus mit verständlicher Handlung und letzten erfolgreichen Zeitpunkten. Kritische Fehler an die Plattform melden; keine Unixzeiten/technischen Codes als alleinige Bedienungsinformation.
- G07 P1/P2: Konfigurierbare Markenvarianten mit konsistenten Buttonrollen, Feldzuständen, Icons und responsiven Layouts; Branding-Vorschau für echte Formularschritte und Nachrichten statt nur Farbwerte.
- G08 P1/P2: DE/EN durchgängig, auch Validierung, Kundenzugang, Mails und datums-/sprachabhängige Texte. Kein neues automatisches Sprach-/IP-Redirect-Verhalten nebenher einführen.
- G09 P1: Wirkliche Mail-/Stripe-/Hersteller-End-to-End-Tests, Zugriffsschutz/Testmodus und laufender Hintergrunddienst auf Hostinger vor produktiver Nutzung. Keine echte Zustellung aus Outboxstatus ableiten.
- G10 P2: Datenschutzgerechte Datenhaltung/Löschung, Exportrechte, Protokollzugriff und Wartung der Geheimnisse auf reale Betriebsabläufe abstimmen. Keine unnötigen Ausweis-/Kartendaten speichern.
- G11 P2: Testdaten klar von späteren echten Vorgängen trennen und einen begrenzten, nachvollziehbaren Reset ermöglichen. Keine pauschale Löschfunktion für laufende Produktionsdaten.
- G12 P2: Conversion-Stufen gezielt messen, nur mit der bereits vorgesehenen Einwilligungslogik und ohne Kundendaten in Analytics. Reale Abbrüche messen, keine Verkaufsbehauptungen daraus erfinden.

## Empfohlene Reihenfolge

1. F01–F04 korrigieren, Abschlussübersicht und Kundenzugang verlässlich machen.
2. Frontend als vollständigen, gut geführten Ablauf gestalten: Einstieg → Vergleich → Extras → Kundendaten → Kontrolle → echte Zahlung/Bestätigung.
3. Partnerverwaltung auf Tagesgeschäft und Einrichtung ausrichten: Partnerakte, Buchungsdetails, Inventarmatrix, Preisvorschau, Nachrichten/Stornos.
4. Tatsächliche Versand-/Zahlungsstrecke und Betriebsstatus integrieren und auf der geschützten Testinstallation durchspielen.
5. Mehrstandorte, größere Kalenderfunktionen, Zusatzleistungs-Kapazitäten, feinere Auswertung und weitere Automatisierung.

Diese Reihenfolge verändert keine freigegebenen Münchner SEO-Texte und erfindet keine Anbieterleistungen, Preise, Bewertungen oder Kapazitäten.
