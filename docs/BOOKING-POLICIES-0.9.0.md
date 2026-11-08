# Buchungskern 0.9.0 – Partnerbedingungen und Plattformaktionen

Stand: 8. November 2026. Optionales Entwicklungspaket für die Dachmarke. Keine Installation auf Hostinger, keine tatsächliche Mailzustellung, keine Live-Zahlung. [Grundlage 0.8.0](BOOKING-COMMERCE-0.8.0.md).

## Partnerbedingungen

Partnerportal → **Storno, Verspätung & Puffer**. Nur schreibberechtigte zugeordnete Partner/Mitarbeiter. Gleiche Konfiguration über den authentifizierten Konfigurationsendpunkt mit `portal_action=conditions`.

- Bestandspuffer vor Fahrzeugabgabe und nach Fahrzeugabholung, jeweils 0–1440 Minuten. Puffer belegen Kapazität, erhöhen aber nicht automatisch den Parkpreis oder verschieben die zugesagte Übergabezeit.
- Mindestvorlauf für neue Anfragen sowie Mindest-/Höchstaufenthalt in angefangenen 24 Stunden.
- Einstellbare Stornostaffeln mit Stunden vor Übergabe und Prozent- oder Festgebühr. Im Portal direkte Prozentwerte/Euro eingeben, beispielsweise `50` oder `12,50`. API ohne `ui_units` weiterhin Ganzzahlwerte: Prozent in Basispunkten, Geld in Cent.
- Höchste passende Stundenschwelle entscheidet, Grenzen einschließlich. Beispiel: mindestens 48 Stunden vorher kostenlos; mindestens 24 Stunden vorher 50 %; darunter 100 %. Abschließende 0-Stunden-Stufe erforderlich; sie gilt auch bei Stornowünschen nach dem Übergabezeitpunkt. Bis zu zwölf Stufen, eindeutige Schwellen.
- Optionale separate Regel für tatsächliches Nichterscheinen, zum Beispiel 100 %. Kein automatischer No-show anhand einer Uhrzeit. Nur berechtigter Mitarbeiter nach expliziter Bestätigung und frühestens nach dem Übergabetermin; gemeldete spätere Ankunft wird berücksichtigt.
- Kulanzminuten und Betrag je angefangener weiterer Verspätungsstunde, zunächst als Berechnung zur Partnerprüfung.

**Bestätigte Betreiberentscheidung: Gebührenbasis ist der tatsächlich bezahlte Betrag nach Plattformgutschein.** Beispiel: 100 € Angebot, 10 € Gutschein, 90 € bezahlt → 50 % Stornogebühr = 45 €, Erstattungsziel = 45 €. Festgebühr höchstens der bezahlte Betrag. Unbezahlte Anfragen haben keine einbehaltene Gebühr. Keine zusätzliche Abbuchung; bei bereits erfolgten Erstattungen werden nur noch offene Erstattungsbeträge angefordert. Eine anderweitig bereits erfolgte höhere Erstattung wird nicht rückgängig gemacht.

Regeln werden mit dem Serverangebot und anschließend der Buchung gespeichert. Partneränderungen verändern bestehende Buchungen nicht. Bestehende Buchungen/alte Angebote ohne Regelwerk behalten die bisherige Logik ohne neue Gebühren/Puffer. Der konkrete Übergabetermin aus dem Buchungssnapshot ist die Stornofristbasis, ersatzweise der vorhandene Parkplatzbeginn.

Der Eingang des Kunden-Stornowunsches friert Gebühr und Erstattungsziel ein, auch wenn der Partner später bearbeitet. Wunsch bleibt Wunsch: Ausführung über vorhandenen Partnerbestätigungsprozess. Bei manueller Verwaltung bestätigt die ausdrücklich ausgelöste Partneraktion den Ablauf. Bei Herstelleranbindung muss der Adapter die Gebühr ausdrücklich passend bestätigen (`fee_cents`); abweichende/fehlende Bestätigung führt zur Klärung statt automatischer Erstattung nach widersprüchlichen Beträgen. Externe Partnerbedingungen müssen mit dem Herstellervertrag übereinstimmen.

Bedingungen sind vor der Anfrage am Angebot einsehbar, im Buchungssnapshot und im Mailvertrag enthalten. Der geschützte Kundenzugang zeigt Gebühren-/Erstattungsberechnung vor dem Stornowunsch. Konfigurierte Regeln sind noch keine juristisch geprüften AGB. Rechnung, Korrekturbeleg und operativer Storno bleiben Partneraufgaben.

## Verspätungen

Bei bestätigten bezahlten Buchungen im Partnerportal: **Verspätung / Nichterscheinen**. Anreise oder Rückreise wählen, neuen voraussichtlichen Servicezeitpunkt in Flughafen-Ortszeit eintragen. Gemeint ist die Fahrzeugabgabe/-abholung, nicht eine erneut umzurechnende Flugzeit. Bei doppelter Ortszeit kann ein UTC-Offset angegeben werden. Keine automatische Flugüberwachung und keine selbsttätige Verschiebung aller Buchungen.

- Anreiseverspätung wird erfasst, ohne den reservierten Zeitraum zu verkürzen. Sie verhindert eine verfrühte No-show-Behandlung.
- Bei späterer Abholung wird der manuelle Bestand einschließlich Rückgabepuffer unter Datenbanksperre verlängert, soweit verfügbar. Preis und bezahlter Betrag bleiben unverändert; Stundenbetrag ist eine kumulierte Schätzung ab ursprünglichem Zeitpunkt nach Kulanz, keine zusätzliche Belastung pro Meldung.
- Kapazitätskonflikt wird erfasst und zur Klärung markiert. Neue Angebote dieses Partners bleiben gesperrt. Nach tatsächlicher Kapazitätskorrektur erneut prüfen; erst wenn die Verlängerung abgedeckt ist, lässt sich der Konflikt abschließen.
- Zeitpunkte außerhalb des angebotenen Servicefensters sowie externe Bestände benötigen Partnerprüfung. Die Oberfläche verlangt eine ausdrückliche Bestätigung der Klärung; sie ersetzt keinen Nachweis des Herstellersystems. Kein automatischer externer `change`-Aufruf implementiert.
- Wiederholung mit identischer Vorgangskennung erzeugt keine doppelte Meldung. Eine spätere Meldung darf die bisher gemeldete Verspätung nicht stillschweigend verkürzen. Partner- und Kundenereignis `*.delay_recorded` enthalten Zeitpunkt, Anreise/Rückreise, Kapazitätsstatus und Kostenschätzung. Übermittlung erfolgt erst über den später anzuschließenden Versanddienst.

## Gutscheinvorlagen – ausschließlich Plattformverwaltung

Werkzeuge → **VPM Buchungsverwaltung → Gutscheinvorlagen & Aktionen**. Partner dürfen keine Codes ausstellen, Aktionen anlegen oder Prozente ändern. Beide Vorlagen unabhängig ein-/ausschaltbar und mit frei einstellbarem Prozentsatz:

1. **Treuegutschein:** erste bezahlte, operativ bestätigte Buchung plus bestätigter Newsletter. Persönlicher Code für die nächste Buchung. Die bekannte Erstbuchungsprüfung und Aktivierungsgrenze bleiben erhalten; rückwirkende Ausstellung für alte Erstbuchungen erfolgt nicht.
2. **Newsletter-Willkommen:** bestätigte Newsletter-Anmeldung nach Aktivierung, ohne erforderliche Vorbuchung. Code kann bereits für die erste Buchung verwendet werden. Unbestätigte Anmeldung reicht nicht.

Einmal je normalisierter E-Mail und Vorlagentyp; persönliche Bindung, einmalige Einlösung, zwei Kalenderjahre ab Ausgabe. Änderung des Prozentsatzes wirkt nur auf künftig ausgestellte Codes. Bereits erworbene persönliche Codes bleiben auch nach Newsletter-Abmeldung gültig; Marketingversand wird nach Abmeldung unterdrückt. Die Ausstellung läuft über den Worker, kein sofortiger Versand ohne Mailanbindung.

Für gleichzeitige Anmeldung bei der Buchungsanfrage erscheint ein **nicht vorausgewähltes** Newsletter-Kästchen, sobald Versand und freigegebener Einwilligungstext eingerichtet sind. Bestätigung weiterhin per DOI. Newsletterprobleme machen eine bereits erfasste Buchungsanfrage nicht fälschlich zum Buchungsfehler; der Rückgabestatus unterscheidet Anmeldung angefordert, bereits offen/bestätigt oder separat erneut versuchen. Alternativ zuerst das vorhandene Newsletterformular nutzen, Code nach DOI erhalten, dann im Buchungsformular einlösen.

## Zeitliche Aktionen

Aktionsname, eindeutiger Code, Beginn/Ende, Prozent oder Festbetrag, Budget, Gesamtzahl Einlösungen und Einlösungen je E-Mail einstellen. In der Oberfläche Zeitangaben in **Europe/Berlin**, inklusive Sommer-/Winterzeit; intern Unix-Zeitpunkte. Bei doppelt vorkommenden Ortszeiten expliziten Offset angeben. Start einschließlich, Ende ausschließlich. Kampagnen pausierbar; Preis-/Regeländerung als neue Aktion, damit bereits erfasste Buchungen nachvollziehbar bleiben.

Rabattbasis gesamte Buchung oder ausschließlich Extras, optional eingegrenzt auf Waschen/Laden/Betankung/eigene Leistungen. Beispiel: 10 % auf ausgewählte Wäsche 70 € und Laden 20 € = 9 € Rabatt; Parkpreis und andere Extras bleiben unberührt. Server verwendet ausschließlich die ausgewählten Extras aus dem gespeicherten Angebot, keine vom Browser behaupteten Beträge.

Berechtigung:

- **Jeder Kunde:** höchste gültige passende öffentliche Aktion wird im Preischeck automatisch berücksichtigt, wenn kein ausdrücklicher Code vorliegt.
- **Nur Abonnenten:** Code erfordert die aktuell bestätigte Newsletter-E-Mail bei Einlösung. Diese zeitliche Abonnentenaktion unterscheidet sich vom bereits erworbenen persönlichen Willkommens-/Treuecode.

Keine Kombination mehrerer Plattformcodes/-aktionen. Expliziter Code hat Vorrang. Die bestehenden Partner-Aufenthaltsrabatte sind bereits im Angebot enthalten und davon getrennt. Der Partneranspruch wird nicht durch Plattformgutscheine gekürzt. Das bestehende Finanzmodell erlaubt nur Rabatte bis zur Plattformprovision; darüber ist ein gesondertes vorfinanziertes Zahlungsmodell nötig. Keine heimliche Rabattkürzung, keine Belastung des Partners.

Wenn eine Aktion zwischen Preisübersicht und Absenden endet, pausiert wird oder sich die berechtigte Preisgrundlage ändert, verhindert der neue erwartete Betrag im Formular eine höhere unbestätigte Buchung. Die Transaktion wird zurückgerollt; der Kunde muss die Preisübersicht erneut prüfen. Integrationen sollten den aus `/public/price` erhaltenen Betrag als `expected_amount_cents` mitsenden. Alte API-Clients ohne dieses Feld bleiben kompatibel, haben aber diesen zusätzlichen Prüfmechanismus nicht.

**Automatische Ankündigung:** optional ab Aktionsbeginn einmal je bestätigtem Abonnenten über die Marketing-Outbox. Auch eine Aktion für alle Kunden erlaubt keine Werbemail an Nichtabonnenten. Vor Versand werden Zustimmung/Revision und aktive Aktionslaufzeit erneut geprüft; abgemeldete Kontakte, pausierte oder abgelaufene Aktionen werden unterdrückt. Es werden Versandereignisse mit Name, Code, Laufzeit und Rabattumfang vorbereitet; tatsächliche Mailzustellung braucht weiterhin Absenderdomain, Versandadapter und Vorlagen.

## Prüfung und verbleibende Arbeiten

`scripts/test-booking-policies-local.sh` führt die bisherigen Tests und neue Bedingungen-/Aktionsprüfungen aus. Nur Loopback-Testinstallation mit leeren eigenen Tabellen. [Backendbericht](BOOKING-POLICIES-PRUEFUNG.json), [Browserbericht](BOOKING-POLICIES-BROWSER-PRUEFUNG.json). Isolierte Neuinstallation über `scripts/check-booking-ci.sh`; keine realen Zahlungen oder Nachrichten in Tests.

Noch nicht umgesetzt: reale Herstelleradapter/Flugstatusdaten, automatische externe Umbuchung, Zusatzabbuchung für Verspätung, Beleg-PDF-Upload, fertiger Live-Checkout, wirkliche Mailzustellung und Hostinger-Installation. Gutscheinreservierungen aus offenen unbezahlten Anfragen brauchen weiterhin Storno/Betriebsbearbeitung; ein allgemeiner automatischer Ablauf mit sicherem Abgleich offener Zahlungsversuche ist nicht Bestandteil dieses Updates. Anbietervorauswahl über Karten und die weiteren strukturierten Anbieterattribute bleiben ein eigener nächster Ausbau.
