# VPM WordPress-Grundgerüst

Schritt 2 des Projekts: leichtes Hybrid-Theme und eigenständiges Anbieter-Plugin. Die fertigen redaktionellen Seiten werden in einem anschließenden, separat geprüften Schritt übertragen. Kein Hostinger-Zugriff, keine Live-Veröffentlichung und keine automatische Übernahme bestehender Einstellungen.

## Pakete

- `wordpress/themes/vpm-hybrid`: klassisches PHP-Theme mit `theme.json`, Gutenberg-Patterns, voller Seitenbreite und Elementor-Theme-Locations.
- `wordpress/plugins/vpm-providers`: bearbeitbare Anbieter und getrennte Parkos-/ParkVia-Links. Ausgabe über `[vpm_providers]` oder `[vpm_providers ids="12,34"]` in einem Gutenberg-Shortcode-Block oder Elementor-Shortcode-Widget.
- `scripts`: ausschließlich lokale Testinstallation und Testdaten, niemals bei Hostinger hochladen.
- `archive/first-static-draft`: ursprünglicher, überholter HTML-Entwurf; keine Grundlage der WordPress-Website.

Installation und Bearbeitung: [docs/INSTALLATION.md](docs/INSTALLATION.md). Datenschutzinventar: [docs/DATENSCHUTZ-INVENTAR.md](docs/DATENSCHUTZ-INVENTAR.md).

## Lokale Entwicklung

Voraussetzungen: Docker, Bash, Python 3, curl, unzip, sha256sum; für Browserprüfungen zusätzlich Python Playwright und Chromium. Jeder Cloud-Task ist bereits isoliert: bestehenden Checkout verwenden, kein zusätzliches Git-Worktree anlegen.

```sh
bash scripts/setup-local.sh
```

Startet nur die eigenen Testcontainer `vpm-local-db` und `vpm-local-wp`, installiert die Pakete und ein offizielles Elementor-Testpaket 3.33.0, lädt sechs Anbieter aus der gelieferten Startseitenfassung und prüft die Integration. Docker-Images und Elementor-Archiv sind mit Digests festgelegt. Testzugänge werden lokal erzeugt, unter `.runtime` gespeichert und weder versioniert noch in ZIPs aufgenommen. Prozesse müssen nach einem Neustart erneut gestartet werden.

Die lokale Testinstallation ist ausschließlich auf Loopback-Port 8088 gebunden und hat `blog_public=0`. Keine lokale URL als öffentliche Vorschau ausgeben. Eigene Container lassen sich mit `docker stop vpm-local-wp vpm-local-db` stoppen und mit dem Setup-Skript neu starten; das Skript löscht ihre Daten nicht. Nach der ersten Einrichtung werden die Testinhalte nicht erneut überschrieben. Die Integrationstests erwarten den ursprünglichen Testbestand; spätere bewusste Änderungen können daher zu Testabweichungen führen.

Die Pakete erzeugen bei Aktivierung keine Seiten, Anbieter oder öffentlichen Angebote. Provider-Testdaten und die technische Vorschau werden nur von den ausdrücklich als lokal gekennzeichneten Skripten geladen.

## Noch offen

Vollständige Seitentexte, bestätigte Änderungen an CTA-Texten für Buchung beim Partner, finale URL-Zuordnung, finale Bildauswahl, Rechtstexte und SEO-Plugin-Auswahl. Das KI-Heromotiv ist für die vorläufige Verwendung freigegeben. Elementor Pro Theme Builder ist vorbereitet, aber mangels Pro-Lizenz nicht praktisch geprüft. Keine Zusage für jede Elementor-/WordPress-Version oder jedes zusätzliche Plugin.

Vier Inhaltsentwürfe sind jetzt vorbereitet: Startseite, Preise, Sicherheit und Über uns. Optionales drittes Paket `dist/vpm-content-drafts.zip` ermöglicht ihren manuellen Import unter Werkzeuge. Es veröffentlicht und überschreibt nichts. Anleitung und offene Textstellen: `docs/INSTALLATION.md`. Aktuelle Screenshots liegen unter `dist/startseite-1440.png`, `preise-1440.png`, `sicherheit-1440.png`, `ueber-uns-1440.png` (jeweils auch 390 Pixel).

## Fertiges Übergabepaket

`dist/vpm-hostinger-uebergabe.zip` bündelt die drei installierbaren WordPress-ZIPs, Hostinger-Schnellstart, SEO-Angaben und Vorschauen. Navigation, Footer und Tastaturzugang sind in Theme 0.3.4 geprüft. Erst das Gesamtpaket entpacken, anschließend die einzelnen ZIPs in Staging installieren. SEO-Tags und Rechtstexte sind nicht automatisch eingerichtet. Der Konfigurationsentwurf für Installations- und Startanweisungen ist aktualisiert; seine Veröffentlichung und eine neue Umgebung wurden nicht geprüft.

Anbieterbilder: sechs unterschiedliche, gekennzeichnete KI-Symbolbilder. VPM Anbieter 0.2.1 und Inhaltsentwürfe 0.2.1 unterstützen austauschbare Mediathek-Bilder. Eigene Beitragsbilder werden nicht überschrieben. Die Aktualisierung bisheriger verwalteter KI-Motive wird manuell ausgelöst. Pflegeanleitung und Linkinventar sind Bestandteil des Übergabepakets.

Plugin-Vorbereitung: `docs/PLUGIN-KOMPATIBILITAET.md` beschreibt die geprüfte Kombination und ihre Grenzen. Optionaler lokaler Testaufbau über `bash scripts/setup-plugin-compatibility-local.sh`. VPM Anbieter 0.2.1 berücksichtigt Cache-Invalidierung; Inhaltsentwürfe 0.2.1 ergänzt auf ausdrücklichen Admin-Klick leere Rank-Math-SEO-Felder. Fremdplugins werden nicht mitgeliefert.

Speed-Optimierung: Theme 0.3.4 und VPM Anbieter 0.2.1, kleinere responsive Bildvarianten, kein später mobiler Menü-Layoutsprung. Vergleich unter `docs/SPEED-UND-VORSCHAU.md`. `python3 scripts/build-offline-preview.py` erstellt eine statische, klickbare Vorschau mit lokalen Bildern und Styles; kein WordPress-Backend und keine final konfigurierte Cookie-Verwaltung.

Design: Mockup-Wortmarke im Header, Bildübergang im Hero, konsistente Ausrichtung in `vpm-section-inner`, Bilder auf allen Unterseiten und responsive Preistabelle. Seiteninhalte und Links sind unverändert. Screenshots und klickbare Vorschau unter `zwischenstand/`.

Ratgeber-Entwurf: [Pflege und Veröffentlichung](docs/RATGEBER.md). [Rendering, Kontraste und Imagify-Prüfumfang](docs/RENDERING-KONTRASTE-IMAGIFY.md). Installierbare eigene WordPress-Pakete liegen zusätzlich unter [pakete/](pakete/).
