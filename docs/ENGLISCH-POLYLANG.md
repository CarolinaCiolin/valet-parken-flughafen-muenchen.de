# Englische Sprachversion – vorbereitet, noch nicht aktiviert

## Keywordbasis

Quelle: vom Betreiber gelieferte CSV Valet-Parken-München_saved-keywords_2026-10-14.csv, 1.156 Zeilen. Die Messwerte stammen durchgehend aus der Datenbank DE. Keine Aussage über weltweite Suchvolumina oder internationale Rankings.

| Keyword aus der Datei | Volumen DE | Verwendung |
| --- | --- | --- |
| valet parking munich airport | 20 | Primärer Suchbegriff für die englische Startseite; natürlich in Einleitung und SEO-Titel |
| munich airport valet parking | 0 | Natürliche H1-Variante und semantische Ergänzung |
| park drive fly munich airport | 20 | Nur im passenden Anbieter-Kontext |
| park drive fly meet and greet munich airport | 0 | Anbieter-Kontext, wenn sprachlich passend; keine erzwungene exakte Wiederholung |
| park drive fly munich airport parking | 0 | Gleiches Anbieter-Thema, kein Anlass für eine separate Seite |

„Meet and greet“ als verständliche englische Erklärung des Valet-Ablaufs einsetzen. „Munich airport p81 parking garage münchen-flughafen“ ist keine passende Zielphrase für die Valet-Startseite. Deutsch-englisch gemischte Keywords nicht als eigene englische Suchbegriffe behandeln.

Vorschlag SEO-Titel: Munich Airport Valet Parking | Compare Providers
Vorschlag H1: Munich Airport Valet Parking
Vorschlag Description: Compare valet parking providers at Munich Airport, explore parking options and book with Parkos or ParkVia. Check current prices with the booking partner.

Das sind Vorschläge, keine bereits eingespielten Metadaten. Keine erfundenen Preise, Bewertungen, Leistungszusagen oder Keywordblöcke. Deutscher Quelltext bleibt unverändert. Englische weitere Seiten hochwertig übersetzen.

## Konfiguration

Die maschinenlesbare Spezifikation steht unter translations/polylang-plan.json. Sie ist ausdrücklich noch kein Polylang-Import und ändert keine Website.

- Polylang kostenlos zunächst separat aus dem offiziellen WordPress-Verzeichnis installieren.
- Deutsch de_DE als Standardsprache, Englisch en_GB als zweite Sprache.
- Sprachverzeichnisse; deutsche Standardsprache ohne /de/, englische Startseite /en/.
- Bestehenden deutschen Seiten, Beiträgen und Menüs zunächst Deutsch zuweisen. Keine automatische Massenveröffentlichung.
- Englische Seiten als Entwürfe erzeugen und mit Originalen paaren. Valet-Ratgeber bleibt auch nach Sprachvorbereitung Entwurf.
- Eigene englische Haupt- und Footermenüs, übersetzte Buttons, Footerhinweis und Sprachschalter.
- Anbieter sind ein eigener Post-Type mit Metadaten. Übersetzbare Felder und Auswahl der passenden Sprache separat implementieren und testen; Affiliate-URLs nicht blind umschreiben. Ein reines Übersetzen der Seiten genügt nicht.
- Darstellungscode enthält deutsche Slug-/Textzuordnungen: diese vor englischer Veröffentlichung sprachfähig machen. Andernfalls reicht Polylang allein für identisches Layout nicht aus.
- Canonical jeder veröffentlichten Seite auf sich selbst; gegenseitige hreflang-Ausgabe aus der finalen Polylang-/Rank-Math-Konfiguration prüfen. Keine zusätzlichen manuell duplizierten Tags.
- LiteSpeed-Sprachcache, Sitemap, Sprachschalter, Consent-Texte, englische Anbieter, lokale Bilder, responsive Layouts und Entwurfszugriff vor Freigabe testen.
- Keine IP-Weiterleitung und keine Sonderbehandlung von Suchmaschinen. Browserhinweis oder begrenzte Erstauswahl sind noch nicht implementiert; beide Sprach-URLs stets zugänglich lassen.

## Noch auszuführen

Polylang ist nicht auf Hostinger aktiviert oder lokal praktisch verifiziert. Keine vollständigen englischen Seiten oder Sprachimporte erstellt. Erst nach der technischen Anpassung und Prüfung ein installierbares Update liefern. Das nächste Update muss die bereits freigegebenen Grafiken, Abstände und privaten Rechtstextentwürfe berücksichtigen. Privatdaten nicht in dieses Repository übernehmen.
