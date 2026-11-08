# Buchungskern 0.11.0 – Branding und Dashboards

Stand: 9. November 2026. Vorbereitung für den Test auf valet-parkservice.com; keine Installation auf Hostinger und keine tatsächlich versendete Mail. Bestehende Funktionen/Grenzen aus [0.10.0](BOOKING-BROKER-0.10.0.md) gelten weiterhin.

## Website-Branding

Werkzeuge → **VPM Buchungsverwaltung → Branding & Absender**. Nur Plattformadministratoren, keine Partner oder reinen Buchungsmitarbeiter.

- Markenname im Buchungstool und geschützten Kundenzugang.
- Haupt-/Buttonfarbe, Hintergrund, Karten-/Formularflächen, Schrift- und Fehlerfarbe.
- Frontendlogo aus der WordPress-Mediathek: PNG/JPEG/WebP.
- Separates E-Mail-Logo: PNG/JPEG. Keine frei eingebbaren externen Bildadressen oder SVG-Dateien.
- Geplanter Absendername, Absenderadresse und Plattform-Antwortadresse.

Schrift und Fehlerfarbe benötigen mindestens 4,5:1 Kontrast zu beiden Hintergrundflächen. Buttonschrift wird automatisch weiß oder schwarz. Ungültige Farben/Bildtypen/Anhänge/Mailadressen werden abgelehnt; bisherige Einstellungen bleiben erhalten. Ohne Logo erscheint der Markenname.

Einstellungen gelten für diese WordPress-Installation. Mehrere eigenständige Websites können denselben Code und unterschiedliche Marken verwenden. Keine automatische Domainumschaltung, kein Multisite-Branding und keine Änderung bestehender Theme-Standortprofile. Das gesamte Theme-Logo/Farbdesign wird nicht überschrieben.

Frontendfarben kommen aus einer kleinen CSS-Ressource derselben Domain. Keine externe Schrift/API, keine Inline-CSS-Ausnahme im geschützten Kundenzugang. Die öffentliche Ressource enthält keine Absenderadressen, ist noindex und erhält eine Einstellungskennung für Aktualisierung nach Änderungen.

## E-Mail-Branding ansehen

Unter den Einstellungen lässt sich **E-Mail-Design ansehen (Vorschau)** öffnen. Es zeigt Logo, Marke und Farben an einem gekennzeichneten Beispieldesign ohne echte Buchungsdaten und ohne Versand.

Neue Outbox-Ereignisse enthalten einen branding-Snapshot mit Marken-/Logo-/Farbangaben und geplanter Absenderkonfiguration. Spätere Änderungen schreiben vorhandene Ereignisse nicht um. Ein Versandadapter muss diese Angaben in die finalen Vorlagen übernehmen, Header validieren und Empfängerregeln beachten. sender_verified=false ist keine Domainfreigabe.

**Weder SMTP, SPF/DKIM noch ein wirklicher From-/Reply-To-Absender werden eingerichtet.** Klaviyo bleibt unverbunden. Die Vorschau ist keine zugesandte Mail und kein vollständiger Satz finaler Buchungs-/Storno-/Newslettervorlagen.

## Feste interne Gestaltung

Verwaltung und Partnerportal verwenden unabhängig von Websitefarben:

| Rolle | Farbe |
| --- | --- |
| Hauptaktionen | Blau #0057B8 |
| Schrift / Navigation | Dunkelblau #14283F |
| Hintergrund | #F3F6FA |
| Formulare / Karten | Weiß #FFFFFF |
| Kritische offene Aufgaben | Rot #C62828 |

Rot ist kein allgemeiner Aktionsakzent. Kritische Aufgaben haben beschriftete Karten/Zähler. Tastaturfokus und aktive Navigation sind sichtbar.

## Partnerportal

- Aufgabenübersicht: Anreisen/Abholungen heute, offene Anfragen, Stornowünsche, Kundenfragen, Klärungsfälle und eigene Nachrichten in Plattformprüfung.
- Heute richtet sich nach der Flughafen-Zeitzone. Nur bestätigte bezahlte beziehungsweise teilweise erstattete Buchungen zählen als Tagesfahrten.
- Tageslisten zeigen höchstens acht nächste Vorgänge je Richtung und keine Kunden-Mailadresse. Gemeldete spätere Abholung wird berücksichtigt.
- Datenschutz/Mandantentrennung bleiben serverseitig. Menüausblendung ist keine Berechtigungsprüfung.
- Desktop: Seitenmenü, jeweils ein sichtbarer Bereich. Mobil: kompakte Bereichsauswahl.
- Speichern erhält den passenden Abschnitt. Bereiche sind per URL-Fragment erreichbar. Ohne JavaScript bleibt eine einfache Seite mit allen vorhandenen Bereichen.
- Bei genau einer Partnerzuordnung kompakter Standortkopf; sonst Auswahl.

Statistiken bleiben Anfragen-/Zahlungs-/Journalwerte. Keine neue Besucheranalyse, keine vollständige Besucherconversion und kein bestätigter Stripe-Auszahlungsabgleich. Partner bekommen keine Plattformnewsletterliste.

Bestehende Zuordnungsungenauigkeit korrigiert: Eine Nachricht aus einer Stornozeile bezieht sich auf deren Buchung statt auf die letzte Buchung der allgemeinen Liste.

## Plattformverwaltung

Einheitliches blaues Layout mit Aufgabenübersicht für Nachrichten-/Profilfreigaben, fehlgeschlagene Partner-/Versandprozesse und direkte Wege zu Branding, Aktionen und Partnerdaten. Formulare/Rechte bleiben erhalten. Nach Speichern öffnet sich wieder der zugehörige Abschnitt.

Globale Verwaltung bleibt unter WordPress → Werkzeuge; noch keine vollständig eigenständige SaaS-Oberfläche. Partner arbeiten im separaten Portal. Partner-Subdomain weiterhin nicht eingerichtet.

## Ladezeiten und Prüfungen

Schema 11 ergänzt Partner-/Statusindizes für Buchungen. Keine Preis-/Daten-/Zahlungsänderung. Tagesfilter lesen Snapshotdaten; für sehr große Bestände wären weitere Messungen und gegebenenfalls materialisierte Zeitfelder sinnvoll.

[Browserbericht](BOOKING-BRANDING-BROWSER-PRUEFUNG.json): lokale TTFB, DOM-/Ladezeit und Ressourcenanzahl für Portal/Buchungsformular. Docker ohne echte Hersteller oder produktive Last. **Keine Hostinger-Ladezeit oder Core-Web-Vitals-Freigabe.**

[Backendbericht](BOOKING-BRANDING-PRUEFUNG.json): Rechte, Kontraste, Medien, Snapshots, Tageszuordnung und Indizes. Browser: echter Medienwähler, Speichern, Logo/Farben, Mailvorschau, Bereiche, responsive Darstellung und getrennte Partner-/Plattformsitzungen. Bestehende Prüfungen ebenfalls:

    VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-branding-local.sh

Nur lokal mit leeren eigenen Tabellen. Testlogos, Beträge, Identitäten und simulierte Zahlungszustände sind keine realen Buchungen.

## Testdomain danach

Aktuelles [Testdomain-Paket](../pakete/vpm-testdomain-installation.zip) entpacken. Bei bestehender Installation nur den Buchungskern ersetzen, keine Inhalte erneut importieren. Vorher sichern.

1. Zugriffsschutz und noindex prüfen.
2. Branding/Absender hinterlegen, Frontend-/Mailvorschau ansehen.
3. Phantasiepartner, Angebote, Zeiten und Kapazitäten bewusst anlegen.
4. Private Testseite regulär als Administrator öffnen; die Editorvorschau deaktiviert Buchungsaktionen.
5. Partnerzugang, Plattformverwaltung und Kundenablauf getrennt durchspielen.
6. Hostinger-Ladezeiten und fehlerhafte Vorgänge prüfen.
7. Danach echten Testmailadapter mit Empfängerbegrenzung auf die eigene freigegebene Adresse einrichten.

Anbietervorauswahl über Karten, vollständige Privat-/Firmenkundendaten, öffentlicher Stripe-Checkout, tatsächlicher Mailversand, Herstelleradapter und Klaviyo bleiben offen. Keine automatische Testdatenerzeugung auf Hostinger. [Installation](TESTDOMAIN-INSTALLATION.md).
