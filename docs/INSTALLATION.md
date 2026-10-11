# Installation und Bearbeitung

## Zunächst auf einer Staging-Installation

1. In Hostinger eine vollständige Sicherung von Dateien und Datenbank erstellen und einen Wiederherstellungsweg bestätigen. Das Projekt löscht keine vorhandenen Inhalte.
2. Eine Staging-Kopie oder separate Testinstallation verwenden. Nicht direkt die produktive Website umstellen. Staging zusätzlich per Passwortschutz vor fremden Zugriffen schützen.
3. Unter Einstellungen → Lesen die Sichtbarkeit für Suchmaschinen deaktivieren. Diese Option ist kein Zugriffsschutz.
4. Unter Design → Themes → Theme hinzufügen → Theme hochladen `vpm-hybrid.zip` installieren und in Staging aktivieren.
5. Unter Plugins → Installieren → Plugin hochladen `vpm-providers.zip` installieren und in Staging aktivieren.
6. Falls Elementor genutzt werden soll, ein offizielles Elementor-Paket separat installieren oder die vorhandene Installation nutzen. Das Grundgerüst erfordert kein Elementor Pro.

Die Theme- und Anbieter-ZIPs importieren keine Texte und ändern keine bestehende Seite, Navigation oder WordPress-Einstellung. Theme-Aktivierung verändert die Darstellung der Staging-Website, nicht die gespeicherten Inhalte. Bei Deaktivierung oder Löschung des Anbieter-Plugins bleiben die Anbieterdaten erhalten; der Shortcode benötigt weiterhin das Plugin für die Ausgabe.

## Gutenberg

Eine neue Seite als Entwurf erstellen. Für eine selbst im Inhalt gesetzte H1 die Vorlage **Volle Breite – Titel im Editor** verwenden. Die Standardvorlage zeigt den Seitentitel bereits als H1; dort keine zusätzliche H1 im Inhalt setzen.

Unter Patterns → Valet Portal stehen Einstieg, Anbieterübersicht, CTA, Tabelle und FAQ bereit. Die eckigen Platzhalter sind ausschließlich Bearbeitungshilfen und müssen vor Veröffentlichung durch freigegebene Inhalte ersetzt werden. Die Einstiegsvorlage enthält das freigegebene KI-Motiv mit sichtbarer Bildunterschrift. Das Bild lässt sich im nativen Bildblock über „Ersetzen“ austauschen. Bei Verwendung eines neuen KI-Bildes die Kennzeichnung „KI-generierte Illustration“ erhalten und einen passenden Alt-Text pflegen. Für ein theme-unabhängiges Bild das mitgelieferte Motiv aus `assets/images/valet-hero.webp` in die WordPress-Mediathek hochladen und im Bildblock auswählen; die lokale Vorschau verwendet bereits eine Mediathek-Kopie.

FAQs sind native Details-Blöcke, Tabellen native Tabellen-Blöcke. Farben und Schriftgrößen stehen im Block-Editor bereit. Es werden keine externen Fonts geladen.

## Anbieter

Unter **Anbieter → Hinzufügen** Namen und gegebenenfalls Beschreibung eintragen. Im Gutenberg-Reiter für den Beitrag die Seitenleiste **Anbieterangaben und Buchungslinks** öffnen:

- Merkmale: eine freigegebene Aussage pro Zeile.
- Bewertungs- und Preisangaben: vollständigen freigegebenen Text einschließlich Quelle beziehungsweise Zeitraum übernehmen; keine automatische Aktualisierung.
- Parkos und ParkVia: jeweils vollständigen zugeordneten Affiliate-Link und exakten CTA-Text eintragen. Tracking nicht verändern.
- Bild über Beitragsbild wählen; Alt-Text in der Mediathek, Bildkennzeichnung im Anbieterpanel pflegen.
- **In Übersichten anzeigen** aktivieren und Anbieter erst nach Prüfung veröffentlichen. Inaktive Anbieter und Entwürfe erscheinen nicht.
- Reihenfolge wird manuell über Zahlen bestimmt; keine automatische Preis- oder Bewertungssortierung.

Die Linkfelder sind getrennt nach Plattform. Die allgemeine Übersicht und unterschiedliche kontextbezogene Links auf Inhaltsseiten werden nicht automatisch aus Anbieterlinks abgeleitet. Seitenspezifisch freigegebene Links müssen im jeweiligen Gutenberg-/Elementor-Inhalt exakt gesetzt werden.

## Elementor

Für eine Seite entweder Gutenberg oder Elementor nutzen. Ein Wechsel konvertiert das bestehende Layout nicht zuverlässig.

Für das Layout mit Theme-Header und -Footer **Volle Breite – Titel im Editor** verwenden, dann „Mit Elementor bearbeiten“. Alternativ kann Elementors eigene Canvas-Vorlage Header und Footer ausblenden. Eine H1 im Elementor-Inhalt setzen, nicht zusätzlich durch die Standard-Themevorlage.

Für Anbieter das Elementor-Widget **Shortcode** einsetzen und `[vpm_providers]` eingeben. Die Anbieter werden im WordPress-Menü Anbieter gepflegt, nicht direkt im Widget. Einzelne Anbieter können mit IDs gewählt werden.

Header und Footer können über das Theme genutzt werden. Dafür unter Design → Menüs Haupt- und Footernavigation zuweisen. Einen allgemeinen Header-CTA unter Design → Customizer → Header-CTA eintragen. Ohne freigegebenen Text und URL erscheint kein Button. Für individuelle Header und Footer ist die Elementor-Pro-Theme-Builder-Integration vorbereitet; sie ist noch nicht praktisch geprüft.

## Veröffentlichung

Erst nach Einzelabnahme der Seiten, vollständiger Linkprüfung, Bildfreigabe und Rechtstexten. Aktuelle Preise und Verfügbarkeit werden ausschließlich beim Buchungspartner ermittelt. Kein Buchungsformular, Datumssuche, API, Tracking, Karten oder Newsletter sind Bestandteil dieser Pakete.

## Vier Seitenentwürfe importieren

Zusätzlich `dist/vpm-content-drafts.zip` als Plugin installieren und aktivieren. Unter **Werkzeuge → VPM Inhaltsentwürfe → Neue Entwürfe importieren** werden Startseite, Preise, Sicherheit und Über uns sowie fehlende Anbieter als **Entwürfe** angelegt. VPM Anbieter muss aktiv sein. Vorhandene Seiten mit derselben Adresse und Anbieter mit demselben Namen werden übersprungen. Wiederholter Import ersetzt keine Inhalte. Die Startseite, Menüs und Indexierung werden nicht umgestellt. Das Hero-Bild wird in die Mediathek übernommen und bleibt im Bildblock austauschbar.

Die Entwürfe verwenden native Gutenberg-Blöcke. Bei Elementor bleiben Theme und Anbieter-Shortcode kompatibel; die Gutenberg-Seiten werden nicht automatisch zu Elementor-Widgets konvertiert. Entscheiden Sie pro Seite für einen Editor.

Datumseingabe, Buchungsmodul-Platzhalter und entsprechende Eingabeaufforderungen sind gemäß Freigabe entfernt. Buchung und Reisedateneingabe erfolgen beim Partner. Interne Vergleiche verweisen auf `/ueber-uns/`, Preise auf `/preise/`; es gibt keine separate Vergleichsseite. Sicherheitslinks aus der PDF bleiben erhalten; der allgemeine Anbieter-CTA verweist auf das allgemeine ParkVia-Angebot.

Die Angabe „mehr als 15“ wurde gemäß Nutzerfreigabe entfernt; bei Valet Service Clean steht „24/7 Betrieb“. Rechtstexte werden später nach Einrichtung der Plugins ergänzt. SEO-Metadaten können bei aktivem Rank Math über den separaten Button „Leere Rank-Math-Felder ergänzen“ übernommen werden. Das Importplugin schreibt dafür Rank-Math-Felder und legt keine zusätzlichen SEO-Tags an.

Nach Import Seiten und Anbieter im Backend prüfen. Erst auf einer gesicherten Staging-Installation Anbieter veröffentlichen, um die Karten zu sehen; Entwürfe erscheinen nicht im öffentlichen Shortcode. Danach Menü, Startseitenzuordnung und Rechtstexte bewusst einrichten. Das Importplugin kann anschließend deaktiviert werden; Inhalte und Medien bleiben erhalten.

## Navigation und Footer

Die fertig abgestimmte Menüstruktur, der allgemeine Header-CTA und der freigegebene Affiliate-Hinweis stehen in `HOSTINGER-SCHNELLSTART.md`. Der Footer-Hinweis ist unter Design → Customizer → Footer-Hinweis bearbeitbar. Es gibt keine Links zu fehlenden Ratgeber- oder Rechtstextseiten. SEO-Angaben sind in `SEO-METADATEN.md` und als JSON vorbereitet.

## Anbieter-Symbolbilder

Neue importierte Anbieter verwenden sechs unterschiedliche, deutlich gekennzeichnete KI-Symbolbilder. Über Werkzeuge → VPM Inhaltsentwürfe → Fehlende Symbolbilder ergänzen können vorhandene Anbieter ohne Beitragsbild ergänzt werden. Bestehende Bilder und Veröffentlichungsstatus bleiben erhalten. Details in `PFLEGE.md`. Die Bilder bleiben in der Mediathek erhalten, wenn das Importplugin deaktiviert wird. **Verwaltete KI-Symbolbilder aktualisieren** ersetzt auf ausdrücklichen Klick nur bisherige mitgelieferte KI-Motive. Eigene Bilder werden geschützt.

## Gewünschte Fremdplugins

Rank Math, LiteSpeed Cache und Complianz wurden gemeinsam anhand von Hersteller-Quellständen lokal geprüft. HFCM ist auf Theme-Hook-Ebene vorbereitet; sein tatsächliches Paket war nicht erreichbar. Einstellungen, Testgrenzen und Hostinger-Abnahme stehen in `PLUGIN-KOMPATIBILITAET.md`.

## Speed und Vorschau

Responsive Bildvarianten und Menü-Ladeverhalten sind optimiert. Bei vorhandenen älteren Bildern nach dem Update Bildgrößen regenerieren; neue Importe erzeugen sie automatisch. `SPEED-UND-VORSCHAU.md` dokumentiert den lokalen Vergleich und den separat notwendigen Hostinger-Test. Die klickbare HTML-Vorschau ist zusätzlich im Gesamtpaket enthalten.

Der Header-Button führt im Theme ab 0.3.1 immer zur Startseite mit `#anbieter`. Ein früher gespeicherter externer Header-Link wird nicht mehr verwendet.
