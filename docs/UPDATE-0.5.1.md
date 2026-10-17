# Update 0.5.1 – Services und bestehende Ab-Preise im Schema

Nur VPM Hybrid wird auf 0.5.1 aktualisiert. Anbieter 0.3.2 und Inhaltsentwürfe 0.4.0 bleiben unverändert. Alle Funktionen aus UPDATE-0.5.0.md sind enthalten.

## Ergänzungen auf Deutsch und Englisch

- Startseite: Die sechs sichtbaren Anbieter werden innerhalb der vorhandenen ItemList als Service ausgezeichnet. Name, Anbieter und Leistungsbeschreibung stammen direkt aus den veröffentlichten Anbieterkarten. Keine Preise im Startseiten-Markup, solange dort keine Preise sichtbar sind.
- Preisseite: ItemList mit sieben Services und zugeordneten Offers. Die Werte stammen unmittelbar aus der vorhandenen Acht-Tage-Ab-Preistabelle: 89,99 / 105 / 145 / 147,99 / 160,99 / 190 / 335 EUR. Keine zweite manuell zu pflegende Preisliste.
- UnitPriceSpecification mit minPrice, priceCurrency EUR und referenceQuantity von acht Tagen (DAY). Beschreibung: Gesamtpreis ab, inklusive Parkplatz und Valet-Service; endgültiger Preis abhängig von Reisedaten, Übergabezeiten und Stellplatzart beim Buchungspartner.
- Jeder Service nennt den tatsächlichen Anbieter aus der jeweiligen Tabellenzeile; das Affiliate-Portal wird nicht zum Parkplatzbetreiber oder Verkäufer erklärt. Die Offer-URL verweist auf die sprachlich passende eigene Vergleichsseite. Sichtbare Buchungslinks und Tracking-IDs bleiben unverändert.
- Keine erfundenen Mittelwerte, Verfügbarkeit, Preisgültigkeitsdaten oder Bewertungen. Einzelne Zusatzgebühren und Amex-Sonderkonditionen werden nicht als vollständiger Acht-Tage-Paketpreis ausgegeben.

Die sichtbaren Texte, Tabellen, Karten und Links bleiben unverändert. Services und Offers sind strukturierte Beschreibungen dieser Inhalte; eine besondere Google-Darstellung wird dadurch nicht zugesichert. Ein Product-Schema wird nicht künstlich ergänzt.

## Installation

Aktuelles vpm-hybrid.zip unter Design → Themes → Theme hochladen als Ersatz installieren. Danach LiteSpeed Cache leeren. Kein Inhaltsimport nötig. Wer das vorherige Schema-/Icon-Update noch nicht installiert hat, nimmt zusätzlich die beiden aktuellen Plugin-ZIPs und folgt UPDATE-0.5.0.md.

## Prüfung

Lokale WordPress-Integration mit und ohne Rank Math, beide Sprachen. Vergleich der Service-Namen und Merkmale mit den sichtbaren Karten sowie aller sieben Preisangaben, Stellplatzarten und Acht-Tage-Einheiten mit den Tabellen. Bestehende Breadcrumb-, FAQ-, Ratgeber-, Rechtsseiten-, Icon- und Responsive-Prüfungen sind im selben Lauf enthalten; Ergebnis: SCHEMA-KONTEXT-PRUEFUNG.json. Keine Installation auf Hostinger und kein externer Google-Validierungstest.
