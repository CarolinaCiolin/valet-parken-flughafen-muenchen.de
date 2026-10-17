# Englische Sprachversion – Update 0.4.0

Lokal mit Polylang 3.8.9 umgesetzt und geprüft; auf Hostinger noch nicht installiert. Schritt-für-Schritt-Anleitung: [UPDATE-0.4.0.md](UPDATE-0.4.0.md).

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

## Umsetzung

- Deutsch de_DE als Standard ohne /de/; Englisch en_GB unter /en/.
- Fünf englische Seiten und sechs Anbieter als Entwürfe, jeweils mit dem deutschen Original gepaart.
- Eigene englische Haupt- und Footermenüs. Deutsche Menü-IDs bleiben erhalten.
- Sprachschalter auf der passenden veröffentlichten Seite, übersetzte Header-/Footer-Texte, Anbietermerkmale, Buttons und Bildhinweise.
- Keywordgerechte englische Startseite mit SEO-Titel, Description und Fokuskeywords. Weitere Seiten hochwertig übersetzt; deutsche Texte bleiben unverändert.
- Selbstreferenzierende Canonicals und Polylang-hreflang de/en lokal geprüft. Keine manuell duplizierten SEO-Tags.
- Keine Browser-/IP-Weiterleitung und keine Suchmaschinen-Sonderbehandlung. Ein Sprachhinweis beziehungsweise eine automatische Erstauswahl ist noch nicht implementiert.
- Die englischen Anbieterentwürfe übernehmen zunächst die derzeitigen deutschen Buchungslinks. Englische Affiliate-Ziele und Vergütungsfreigaben müssen ergänzt werden; vorhandene Kennungen werden nicht blind verändert.

## Import

VPM-Pakete aktualisieren und offizielles Polylang aktivieren. Unter Werkzeuge → VPM Inhaltsentwürfe erst „1. Deutsch und Englisch vorbereiten“, danach „2. Englische Entwürfe importieren“. Kein Aktivierungsimport, keine automatische Veröffentlichung, kein Überschreiben vorhandener Übersetzungen. Spätere Änderungen an deutschen Texten werden nicht automatisch in englische Texte gespiegelt.

Die Datei translations/polylang-plan.json dokumentiert die Spezifikation; sie ist keine eigenständig in Polylang importierbare Datei. Aktuelle Daten liegen in wordpress/plugins/vpm-content-drafts/content-en.json. translations/en enthält die lesbaren Gutenberg-HTML-Fassungen.

Vor Veröffentlichung: englische Partnerlinks, Rechtstexte und Consent-Texte ergänzen; Menüs, Indexierung, Sitemap und echte Hostinger-Cache-Auslieferung prüfen. Der Ratgeber bleibt vorerst in beiden Sprachen Entwurf. Beim späteren Erstellen von Beiträgen die passende Polylang-Sprache auswählen; die jeweilige Ratgeberseite verwendet die native Beitragsabfrage.

## Prüfungen

- MULTILINGUAL-BROWSER-PRUEFUNG.json: acht Seiten × vier Breiten, Bilder, Original-/Übersetzungsmenüs, Sprachwechsel, Layout, Karten, Abstand und sponsored.
- MULTILINGUAL-SEO-EDITOR-PRUEFUNG.json: Canonicals, hreflang, gültige Gutenberg-Blöcke aller fünf englischen Seiten.
- Import: Wiederholbarkeit, Schutz bestehender Originale, Entwurfsstatus, Sprachpaare, Berechtigungen und privater Rechtstext-Import lokal geprüft.
- Testaufbau optional mit scripts/setup-polylang-local.sh, anschließend guarded local fixture workflow scripts/test-multilingual-local.sh. Keine Fremdplugin-Quellpakete auf Hostinger hochladen.
