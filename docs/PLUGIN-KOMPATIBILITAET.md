# Plugin-Kompatibilität und Einstellungen für Hostinger

## Was konkret geprüft wurde

Lokale Installation: WordPress 7.1.3, PHP 8.3.35, VPM Hybrid 0.1.3, VPM Anbieter 0.1.2, VPM Inhaltsentwürfe 0.1.3 und Elementor Free 3.33.0. Die drei Fremdplugins wurden gleichzeitig aktiviert. WordPress.org-Downloads sind über den Cloud-Proxy blockiert; deshalb stammen die Testdateien aus den Hersteller-Repositories. Es handelt sich um Quellstandsprüfungen, nicht um einen Test der später über Hostinger installierten ZIPs. Exakte Git-Stände stehen in `PLUGIN-QUELLSTAENDE.json`. Nicht erreichbare externe Server-APIs werden in der isolierten Backend-Prüfung durch ein lokales MU-Testplugin sofort als nicht verfügbar beantwortet. Online-Funktionen, Kontoverbindungen und Cookie-Scans sind dadurch nicht geprüft. Gutenberg-Anbieterfelder ließen sich mit allen drei Plugins weiterhin speichern und erneut lesen; es gab dabei keine Browser-JavaScriptfehler.

| Plugin | Lokaler Teststand | Ergebnis | Noch auf Hostinger prüfen |
| --- | --- | --- | --- |
| Rank Math | 1.0.280, Hersteller-Quellstand | SEO-Titel/Beschreibungen, Canonicals bei indexierbarem Teststand und echte XML-Sitemap geprüft | Installierte stabile Version und endgültige Einstellungen |
| HFCM | Kein verifiziertes Paket verfügbar | Benötigte Theme-Hooks `wp_head`, `wp_body_open`, `wp_footer` mit lokalem Testcode geprüft | Tatsächliche Plugin-Version und jedes eingesetzte Snippet |
| LiteSpeed Cache | 7.9.1, Hersteller-Quellstand | Gleichzeitig aktiviert; Anbieter-Cacheinvalidierung über offizielle Purge-Schnittstelle geprüft | Cache-HIT, Server-Freischaltung des Crawlers und tatsächliches Preloading |
| Complianz Free | 7.5.5, Hersteller-Quellstand | Sichtbares Banner; lokales Statistik-Testscript vor Zustimmung/bei Ablehnung gesperrt, nach Zustimmung einmal ausgeführt; Auswahl beim Neuladen erhalten | Tatsächliche Dienste, Cookies, Texte und jedes produktive Script |

Der Rank-Math-Git-Stand enthält einen veralteten generierten Autoloader. Für den Test wurde er mit Composer 2.8.12 über `dump-autoload --no-dev --no-scripts --no-plugins --optimize` neu generiert; keine Schutzprüfung deaktiviert und kein Plugin-Code gepatcht. Auf Hostinger das reguläre stabile WordPress-Plugin installieren, nicht diese Entwicklungsdateien verwenden. Die Fremdplugins sind **nicht im Übergabepaket enthalten**.

## Rank Math

1. Rank Math Free aktivieren und den Einrichtungsassistenten durchführen. Die Kontoverbindung kann für die hier geprüften SEO-Grundfunktionen übersprungen werden.
2. XML-Sitemap für Seiten aktivieren. Das Ziel nach der Live-Einrichtung lautet `https://valet-parken-flughafen-muenchen.de/sitemap_index.xml`.
3. VPM-Anbieter bleiben interne Datensätze ohne öffentliche Einzeladressen. Sie gehören nicht als eigene Seiten in die Sitemap. Bei einer Website mit vier statischen Seiten nicht benötigte Autoren-, Schlagwort- oder Kategoriearchive deaktivieren beziehungsweise gezielt konfigurieren.
4. Unter **Werkzeuge → VPM Inhaltsentwürfe → Leere Rank-Math-Felder ergänzen** die gelieferten SEO-Titel und Beschreibungen übernehmen. Dies ergänzt nur leere Felder der importierten Seiten, ersetzt keine vorhandenen Angaben und verändert keine Indexierung. Für „Über uns“ wurde keine separate Beschreibung geliefert.
5. Keine zusätzliche SEO-Ausgabe über HFCM einbauen. Das Theme überlässt Rank Math Titel und Meta-Tags. Staging bleibt noindex; Rank Math kann dort die Canonical-Ausgabe unterdrücken. Indexierbare lokale Tests ergaben genau eine korrekte Canonical pro geprüfter Seite.
6. Analytics-Verbindungen, Content AI, externe Sofortindexierung und ähnliche Zusatzfunktionen erst bei tatsächlichem Bedarf konfigurieren. Keine fiktiven Betreiber- oder Bewertungsdaten als Schema eintragen.

## LiteSpeed Cache: konservativer Start

- Nur dieses eine Seiten-Cache-Plugin aktivieren. Cache für angemeldete Benutzer zunächst deaktiviert lassen.
- Zunächst keine CSS-/JS-Kombination, Minifizierung, JS-Verzögerung oder JS-Deferred-Optimierung aktivieren. Erst nach der normalen Funktionsprüfung einzeln zuschalten und erneut testen.
- Theme-Script `navigation.js`, Anbieter-Editor, Elementor und Complianz müssen ihre Funktionen behalten. Bei späterer Optimierung Consent-Scripts wie `complianz`, `cmplz` und `/complianz-gdpr/` von Verzögerung/Deferred-Verarbeitung ausnehmen. Das greift nur, wenn die jeweilige Optimierungsfunktion tatsächlich verwendet wird.
- Öffentlichen Cache zunächst mit überschaubarer TTL betreiben, beispielsweise 3600 Sekunden. Cache-Erneuerung aktualisiert keine redaktionell hinterlegten Preise oder Bewertungen.
- Die WordPress-Mediathek liefert bereits responsive WebP-Dateien. Bildoptimierung über QUIC.cloud, CDN, externe Fonts oder zusätzliche Lazy-Load-Funktionen sind für den vorbereiteten Stand nicht nötig und wurden nicht eingeschaltet.
- Nach Änderungen an veröffentlichten VPM-Anbietern oder deren Meta-/Bildfeldern fordert das Anbieterplugin über `litespeed_purge_all` eine Leerung des Seiten-Caches an. Mehrere Feldänderungen in einem Request werden zusammengefasst. Dadurch werden auch Seiten mit Anbieter-Shortcodes erneuert. Entwurfsänderungen lösen keine öffentliche Leerung aus. Bei WP Super Cache wird dessen `wp_cache_clear_cache()` verwendet, sofern verfügbar.

### Sitemap-Preload

In LiteSpeed Cache unter **Crawler** die vollständige Rank-Math-Sitemap als eigene Sitemap eintragen. Den Crawler erst aktivieren, wenn Hostinger die Serverfunktion freigeschaltet hat und die Sitemap erreichbar ist. Eine passwortgeschützte Staging-Website kann den Crawler am Zugriff hindern; das ist keine Plugin-Inkompatibilität.

Auf Hostinger mit einem abgemeldeten Browser beziehungsweise ohne Cookies dieselbe öffentliche Seite zweimal abrufen und den Cache-Status prüfen. Ein Plugin-Schalter oder die bloße Aktivierung belegt keinen Cache-HIT. Anschließend den Crawlerlauf prüfen und eine Anbieteränderung testen: Nach der Leerung müssen die neuen Angaben auch für abgemeldete Besucher erscheinen.

Der lokale Server ist Apache und kann **keinen echten LiteSpeed-Cache-HIT und kein Server-Preloading bestätigen**. Falls Hostinger den Crawler nicht erlaubt, bleibt WP Super Cache eine kostenlose Alternative mit Preload, aber ohne sitemapbasiertes Preloading. WP Super Cache wurde in diesem Block nicht installiert oder praktisch geprüft.

## HFCM und Complianz

HFCM zunächst ohne aktive externe Tracking-Snippets installieren. Theme-Hooks sind vorhanden und wurden mit lokalem Testcode überprüft. Der eigentliche HFCM-Editor und seine Ausgaberegeln bleiben bis zur Installation ungeprüft.

Ein zustimmungspflichtiges Script darf nicht sofort als gewöhnliches JavaScript ausgeführt werden. Ein unterstütztes Complianz-Muster für ein zugeordnetes Statistik-Script ist:

```html
<script type="text/plain" class="cmplz-script" data-category="statistics">
  /* Hier erst das tatsächlich benötigte Statistik-Script eintragen. */
</script>
```

Bei HFCM dafür einen Modus verwenden, der dieses vollständige HTML unverändert ausgibt. Prüfen, dass HFCM keine zusätzliche ausführbare Script-Hülle darum setzt. Die Kategorie nach dem tatsächlichen Dienst auswählen; ein Marketing-Script gehört nicht pauschal in Statistik. Nicht zusätzlich dasselbe Script über Rank Math, Elementor oder Complianz erneut einbinden.

Complianz anhand der **wirklich eingesetzten Dienste** konfigurieren. Der derzeitige Portalstand verwendet keine eigenen Statistik-/Marketingdienste. Ein Banner muss daher nicht durch erfundene Dienste erzwungen werden. Der lokale Banner-Test verwendete ausschließlich einen synthetischen Zähler im Browser, keine Datenübertragung, keinen echten Tracker und keine Betreiberangaben. Die Testkonfiguration wurde danach zurückgenommen.

Bei der Hostinger-Abnahme in einem frischen Browserprofil testen: vor Auswahl keine zustimmungspflichtigen Requests/Cookies; Ablehnen bleibt wirksam; Zustimmung startet jedes freigegebene Script einmal; Auswahl bleibt nach Neuladen erhalten. Den später eingerichteten Widerruf ebenfalls testen. Die abgeschlossenen lokalen Tests sind keine rechtliche Abnahme und keine Prüfung noch unbekannter Scripts.

## Reihenfolge der Einrichtung

VPM-Pakete und Elementor → Rank Math und SEO-Felder → Complianz mit tatsächlichem Dienste-Inventar → HFCM-Snippets mit Consent-Zuordnung → LiteSpeed Cache → abschließende Prüfung. Rechtstexte folgen anhand der Betreiberangaben und der endgültigen Plugin-/Script-Konfiguration.
