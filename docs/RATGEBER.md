# Valet-Ratgeber – vorbereiteter Entwurf

„Valet-Ratgeber“ ist eine normale WordPress-Seite mit Slug `ratgeber`, gespeichert als **Entwurf**. Auf Hostinger wird sie über den aktualisierten VPM-Inhaltsentwürfe-Importer angelegt; bestehende Seiten, Blogbeiträge, Startseiten-/Beitragsseiten-Zuordnung und Menüs werden dabei nicht verändert. In der lokalen Testinstallation existiert der Entwurf bereits.

## Später Beiträge hinzufügen

1. Aktualisiertes Theme und Plugin **VPM Inhaltsentwürfe** installieren. Unter **Werkzeuge → VPM Inhaltsentwürfe → Neue Entwürfe importieren** wird der zusätzliche Entwurf angelegt. Bestehende Inhalte werden übersprungen.
2. Unter **Seiten** den Entwurf „Valet-Ratgeber“ prüfen. Die nativen Gutenberg-Blöcke lassen sich bearbeiten; neun Beiträge je Übersichtsseite sind voreingestellt.
3. Beiträge unter **Beiträge → Erstellen** schreiben, optional Beitragsbild und Auszug ergänzen und veröffentlichen. Sie erscheinen automatisch in der Übersicht, neueste zuerst. Beitragsentwürfe erscheinen nicht. Vorinstallierte WordPress-Beispielbeiträge vor dem Launch entfernen oder auf Entwurf stellen.
4. Die Ratgeberseite bei Bedarf veröffentlichen und anschließend im Menü verlinken. Bis dahin bleibt sie für Besucher unerreichbar.

**Nicht als „Beitragsseite“ unter Einstellungen → Lesen zuweisen:** Das würde die native Seitenabfrage durch WordPress' Blogindex ersetzen und ihren Gutenberg-Inhalt ignorieren. Die Seite funktioniert als normale Seite mit Query-Loop.

## Vorbereitung und Prüfung

Native Beitragsabfrage, Beitragsbild, Datum, verlinkter Titel, Auszug mit Beitragslink und Pagination. Drei Spalten am Desktop, zwei auf Tablets, eine auf dem Handy. Ohne veröffentlichte Beiträge erscheint ein kurzer Leerzustand. Für die Artikelseiten ist eine begrenzte Lesebreite vorbereitet. Kein zusätzlicher Blog-Plugin-Zwang, keine erfundenen Artikel, keine automatischen Veröffentlichungen oder Menülinks.

Geprüft: Entwurf nicht öffentlich, gültige Gutenberg-Blöcke, erneuter Import ohne Überschreiben, automatische Aufnahme neuer Beiträge, 9+2-Pagination mit elf temporären Testbeiträgen, Ausschluss eines weiteren Entwurfs, funktionierende Artikellinks und vier Bildschirmbreiten. Alle zwölf Testbeiträge anschließend entfernt. Screenshots zeigen den tatsächlichen leeren Entwurf.

Die vorhandene Elementor-Kompatibilität bleibt bestehen; die neue Übersicht wurde konkret mit dem nativen Gutenberg-Query-Loop geprüft. Wer später eine Elementor-Übersicht gestaltet, kann dessen eigene Beitragsdarstellung nutzen; diese ist nicht automatisch identisch mit der vorbereiteten Gutenberg-Seite.
