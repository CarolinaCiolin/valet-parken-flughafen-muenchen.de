# Anbieter und Inhalte pflegen

## Bilder

Das Paket enthält sechs unterschiedliche, fotorealistische KI-Symbolbilder für die Anbieterübersicht. Sie zeigen allgemeine Valet-Szenen, keine tatsächlichen Anbieter, Mitarbeiter oder Stellplätze. Die sichtbare Kennzeichnung lautet „KI-Symbolbild · keine Anbieteraufnahme“. Die Motive belegen keine Eigenschaften eines bestimmten Anbieters.

Neue Anbieterentwürfe erhalten eines dieser Bilder beim Import. Bereits vorhandene Anbieter bleiben unverändert. Für vorhandene Anbieter ohne Bild gibt es unter **Werkzeuge → VPM Inhaltsentwürfe** den separaten Button **Fehlende Symbolbilder ergänzen**. Er ersetzt keine vorhandenen Beitragsbilder und veröffentlicht nichts. Wiederholtes Ausführen legt keine weiteren Kopien an.

Über **Verwaltete KI-Symbolbilder aktualisieren** lassen sich ausschließlich die bisherigen mitgelieferten KI-Motive ersetzen. Eigene Bilder bleiben geschützt; der Veröffentlichungsstatus ändert sich nicht.

Ein Bild kann jederzeit unter **Anbieter → Beitragsbild** ausgetauscht werden. Alt-Text in der Mediathek und Bildkennzeichnung im Anbieterpanel aktualisieren. Beim mitgelieferten KI-Symbolbild erhält das Anbieterplugin die Kennzeichnung automatisch. Das Hero-Bild bleibt ein separat austauschbarer Gutenberg-Bildblock.

## Anbieterangaben, Preise und Bewertungen

Name, Merkmale, Bewertungen und Buchungslinks der Karten werden unter **Anbieter** gepflegt. Kontextbezogene Sicherheitsangaben und Links stehen zusätzlich in der Sicherheitsseite; Preise und Stellplatzarten in der Preistabelle. Änderungen dort müssen bewusst im jeweiligen Seiteneditor vorgenommen werden. Diese Texte werden nicht automatisch aus Anbieterfeldern synchronisiert.

- Eine Änderung zunächst mit der aktuellen Angebotsseite des jeweiligen Buchungspartners abgleichen.
- Bei Bewertungen Quelle und Skala beibehalten; Anzahl der Bewertungen nur ändern, wenn belegt.
- Preise sind Ab-Preise für acht Tage. Änderungen mit Zeitraum, Stellplatzart und bekannten Zuschlägen im eigenen Prüfvermerk dokumentieren. Der verbindliche Gesamtpreis wird beim Partner angezeigt.
- Der interne Prüfstand im Anbieterpanel erscheint nicht öffentlich. Dort Quelle und tatsächliches Prüfdatum eintragen; kein automatisch behauptetes Prüfdatum verwenden.
- Bei vorübergehend unpassenden Anbieterangaben die Karte über **In Übersichten anzeigen** deaktivieren. Anschließend auch Preistabelle und Sicherheitsabschnitt prüfen.

## Affiliate-Links

`LINK-INVENTAR.csv` enthält sämtliche vorbereiteten Links mit ihrem Verwendungskontext. Die vollständige URL ist maßgeblich; Trackingparameter nicht aus den separat dargestellten Spalten neu zusammensetzen. Park Drive Fly hat zwei getrennte Plattformlinks. Die Sicherheitsseite enthält bestätigte, eigene `vpm-sicherheit-…`-Referenzen.

Der Export enthält keine Behauptung, dass externe Partnerseiten erreichbar sind. Die lokale Prüfung kann interne URLs und Auszeichnungen kontrollieren; die endgültigen Partnerziele müssen im Browser bei der Hostinger-Abnahme geöffnet werden. Beim Ändern von Partnerlinks die Kennzeichnung als Partnerlink und `sponsored nofollow` erhalten.

## Änderungen und Updates

Vor Theme- oder Plugin-Updates eine Hostinger-Sicherung erstellen und die Änderungen zuerst in Staging testen. Nach Anpassungen die Startseite auf Handy und Desktop öffnen, Menüs und FAQs bedienen und beide Buchungslinks bei Anbietern mit zwei Plattformen prüfen. Der Entwurfsimport ist für die Ersteinrichtung gedacht und aktualisiert keine bereits importierten Inhalte.

Rechtstexte und die Bestandsaufnahme der weiteren Plugins folgen nach deren Einrichtung. Das Projekt enthält weiterhin kein Datumsformular, eigenes Buchungssystem und kein eigenes Besuchstracking.

## Designbilder auf den Unterseiten

Die zusätzlichen Bilder auf Preise, Sicherheit und Über uns liegen im Theme unter `assets/images/`. Die Darstellung ergänzt das Theme anhand des Seitenslugs, ohne gespeicherte Texte zu verändern. Derzeit werden diese Bilder im Theme gepflegt. Der Hero bleibt ein Gutenberg-Bildblock, Anbieterbilder bleiben normale Beitragsbilder. Eine eigene Elementor-Seite kann unabhängig davon ihre eigenen Bildwidgets verwenden.
