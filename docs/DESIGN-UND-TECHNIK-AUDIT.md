# Design-, Technik- und Ladezeitprüfung

Stand: 11. Oktober 2026 · Theme 0.3.3. Geprüft wurden die vier lokalen WordPress-Seiten und die aktualisierte statische Vorschau. Keine Veröffentlichung auf Hostinger.

## Gefundene und behobene Fehler

| Befund | Korrektur |
| --- | --- |
| Mobiler Hero bei 390 Pixeln nur 350 Pixel breit: ungleichmäßiger rechter Rand, zusätzliche Textumbrüche | Hero über die volle Bildschirmbreite, Innenabstand passend zu den übrigen Abschnitten; erste Karte bei 390 Pixeln rund 93 Pixel früher. Mobiles Hero-Bild bleibt ohne Netzwerkrequest |
| Listen in Sicherheitskarten erbten 20 Pixel, Absätze daneben 15 Pixel | Einheitliche, lesbare Listentypografie; Text unverändert |
| Buchungsbuttons in gleich hohen Sicherheitskarten standen unterschiedlich hoch | Buttons am unteren Kartenrand ausgerichtet |
| Allgemeiner Ablauf-CTA befand sich innerhalb von Schritt fünf | CTA steht hinter dem gesamten Ablauf, außerhalb der fünf Schritte |
| Raster mit fünf Elementen ließ eine unbesetzte dritte Spalte in der letzten Reihe | Ausgewogenes 3+2-Raster am Desktop, 2+2+1 auf Tablets, eine Spalte am Handy; vorhandene Schrittnummern als goldene Marken gestaltet |
| Header zwischen 761 und 1000 Pixeln war zweizeilig und 162 Pixel hoch | Aufklappbares Tabletmenü; bei 768 Pixeln sinkt der Header von 162 auf 91 Pixel. Desktop-CTA bleibt sichtbar, mobile Ausblendung bleibt erhalten |
| Ohne JavaScript stand ein funktionsloser Menübutton neben den sichtbaren Links | Ohne JavaScript bleiben die Links sichtbar, der Menübutton ist ausgeblendet |
| Oberes Bild auf den Unterseiten wurde verzögert entdeckt | Einstiegsbild lädt regulär, weitere Bilder bleiben lazy; responsive WebP-Varianten bleiben erhalten |

## Technische Prüfung

- Alle vier Seiten: gültige interne Ziele, eine H1, lokale Styles/Bilder, keine Browser-JS-Fehler oder fehlgeschlagenen Ressourcen.
- 13 Bildschirmbreiten von 320 bis 1440 Pixeln: kein horizontales Überlaufen; Menü öffnen/schließen, Escape und Desktopnavigation geprüft.
- Frische Aufrufe bei 390 und 768 Pixeln: kein Hero-Bildrequest. Desktop: echtes Hero-Bild geladen.
- Bestehende Preisbuttons weiterhin gleich breit, Park Drive Fly weiterhin nur mit Parkos-Button auf der Startseite.
- Text- und Linkabgleich aller vier Seiten vor/nach der Änderung: identisch. Gespeicherte Blockinhalte bleiben unverändert.
- Gutenberg-/Elementor-Frontend und Speichern der Anbieterfelder geprüft; native Blöcke aller vier Inhaltsseiten geprüft.
- Tastaturzugang, FAQs, Navigation ohne JavaScript und Bilddimensionen geprüft. Die Prüfung ist kein vollständiges Barrierefreiheitszertifikat.

## Ladezeit

Drei simulierte Läufe je Profil, Median. Handy: 390 Pixel, Desktop: 1440 Pixel; beide mit 150 ms Latenz, 1,6 Mbit/s, CPU vierfach verlangsamt und deaktiviertem Browsercache.

| Seite | LCP Handy | LCP Desktop | CLS in allen Läufen |
| --- | ---: | ---: | ---: |
| Startseite | 0,912 s | 1,328 s | 0,000 |
| Preise | 0,640 s | 0,956 s | 0,000 |
| Sicherheit | 0,632 s | 0,788 s | 0,000 |
| Über uns | 0,640 s | 0,696 s | 0,000 |

Preise am Desktop: medianer LCP vorher 1,412 s, nachher 0,956 s. Das früher entdeckte Einstiegsbild verbessert diesen lokalen Vergleich. Die Startseite mobil zeigt keine belastbare Beschleunigung: 0,756 s vorher und 0,912 s nachher; die initiale Übertragung steigt geringfügig von 88.003 auf 88.524 Bytes. Das ist kein Nachweis einer realen Verschlechterung oder Verbesserung. Alle Messwerte sind Labordaten, keine Garantie für Hostinger oder echte Besucher. Rohwerte: [SPEED-AUDIT.json](SPEED-AUDIT.json).

Auf Hostinger bleiben Serverantwortzeit, echter LiteSpeed-Cache-HIT, Kompression, endgültige Consent-/Tracking-Konfiguration und sitemapbasiertes Preloading zu prüfen. Partner-Verfügbarkeit und Affiliate-Weiterleitungen wurden nicht durch Testbuchungen geprüft. Rechtstexte bleiben wie vereinbart zurückgestellt.

## Verbleibende Conversion-Hürden und Gestaltungsvorschläge

Diese Punkte sind Empfehlungen aus der Prüfung, noch nicht umgesetzt. Texte und Linkziele wurden dafür nicht verändert.

1. **Vergleichsziel vereinheitlichen:** Der Button „Valet-Angebote ausführlich vergleichen“ im Preis-Unterabschnitt der Startseite führt weiterhin zu Über uns. Empfehlung: ebenfalls zur Preisseite führen. Das Ziel existiert; es ist eine Erwartungsfrage, kein kaputter Link.
2. **Mobilen Vergleich kompakter machen:** Die Vergleichstabelle auf der Startseite wird zu zehn einzelnen Karten. Dadurch entsteht viel Scrollstrecke. Empfehlung: dort eine kompakte Vergleichsmatrix mit fixierter erster Spalte und erkennbarem horizontalem Scrollbereich prüfen. Die genehmigte Preistabelle bleibt davon unabhängig.
3. **Lange Unterseiten leichter erschließen:** Eine kleine Inhaltsnavigation mit den bereits vorhandenen Abschnittsüberschriften kann auf Preise und Sicherheit direkt zu relevanten Abschnitten führen. Keine neuen Zwischenüberschriften oder Umschreibungen nötig.
4. **Anbieter ohne Umweg erreichbar halten:** Trotz kompakterem Hero liegen die Karten mobil unter dem ersten Bildschirm. Die vorhandenen Buchungsbuttons springen direkt zu den Anbietern. Ein dauerhaft sichtbarer mobiler Buchungsbutton wäre ein weiterer Versuch, sollte aber gegen verdeckten Inhalt und das spätere Cookiebanner geprüft werden.

Die Website bleibt durch die unveränderte Textmenge eine umfangreiche Informationsseite. Gestaltung kann die Orientierung verbessern; eine gemessene Conversion-Steigerung wurde nicht behauptet. Priorität haben das Vergleichsziel und der kompaktere mobile Vergleich.
