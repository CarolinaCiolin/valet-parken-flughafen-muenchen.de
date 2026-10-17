# Gemeinsames WordPress-Update 0.4.0

## Enthalten

- VPM Hybrid 0.4.1: Abstand zum Reservierungshinweis, Sprachschalter und englische Darstellung.
- VPM Anbieter 0.3.1: sechs freigegebene illustrative Anbieter-Motive ohne den bisherigen Foto-KI-Hinweis; eigene Beitragsbilder werden nicht ersetzt. Die fotorealistischen redaktionellen Bilder behalten ihren Hinweis. Ein WebP-Bildbogen (206.638 Bytes) wird für alle sechs Illustrationen verwendet und per CSS dargestellt; kein externer Bildserver.
- VPM Inhaltsentwürfe 0.3.1: Sprachvorbereitung, fünf englische Seitenentwürfe und sechs englische Anbieterentwürfe, englische Menüs und SEO-Angaben. Zusätzlich separater Import privater Rechtstext-JSON-Dateien als Entwürfe.
- CleanLinking bleibt standardmäßig deaktiviert. Keine TradeTracker-Aufrufe durch das Update. Partnerfreigabe und Website-Zuordnung sind noch offen.

## Vor Upload

Dateien und Datenbank sichern. Die drei ZIPs herunterladen, nicht entpacken. Polylang kostenlos aus dem offiziellen WordPress-Verzeichnis installieren und aktivieren, wenn die englische Vorbereitung ausgeführt werden soll. Keine fremde Polylang-Quellkopie aus dieser lokalen Testumgebung hochladen.

## Aktualisierung auf Hostinger

1. Unter Design → Themes → Theme hochladen vpm-hybrid.zip hochladen und die bestehende Version ersetzen. Theme bleibt VPM Hybrid.
2. Unter Plugins → Installieren → Plugin hochladen vpm-providers.zip und vpm-content-drafts.zip hochladen, die jeweiligen bestehenden Versionen ersetzen und aktivieren.
3. LiteSpeed Cache leeren. Die deutschen Inhalte, Menüeinträge, Anbieterlinks und eigenen Bilder bleiben erhalten. Verwaltete bisherige Anbieterfotos werden nur in der Kartendarstellung durch Illustrationen ersetzt; Mediathek-Dateien bleiben für redaktionelle Bilder bestehen.
4. Werkzeuge → VPM Inhaltsentwürfe → „1. Deutsch und Englisch vorbereiten“. Deutsch bleibt ohne /de/, Englisch erhält /en/. Bestehende sprachlose Seiten, Beiträge, Anbieter und Menüs werden Deutsch zugewiesen. Es wird keine Browser- oder IP-Weiterleitung eingeschaltet.
5. Auf derselben Werkzeugseite „2. Englische Entwürfe importieren“ ausführen. Neue Seiten und Anbieter bleiben unveröffentlicht. Wiederholung überspringt bestehende Übersetzungen. Nicht den alten Inhaltsimport für ein Überschreiben vorhandener Seiten verwenden; dieser überschreibt grundsätzlich nichts.
6. Englische Seiten und Anbieter in der Entwurfsvorschau prüfen. Noch nicht veröffentlichen, wenn englische Affiliate-Ziele, Consent-Texte und Rechtstexte fehlen. Valet Parking Guide bleibt Entwurf. Deutscher Valet-Ratgeber ebenfalls.
7. Für die Rechtstexte den separaten privaten JSON-Download verwenden und unter „Private Rechtstext-Entwürfe“ hochladen. Betreiberangaben sind absichtlich nicht in den öffentlichen ZIPs enthalten. Import erstellt ausschließlich fehlende Entwürfe. Keine automatische Footer-Verlinkung unfertiger Rechtstexte.

## Englische Inhalte

Hauptkeyword: valet parking munich airport. Natürliche H1-Variante: Munich Airport Valet Parking. Auswertung der Nutzerdatei in ENGLISCH-POLYLANG.md; keine internationalen Suchvolumina aus der Datenbank DE ableiten.

Es werden vorhandene deutsche Partnerlinks zunächst als funktionale Platzhalter übernommen; deren Vergütungsfreigabe und Zuordnung sind noch unbestätigt. Neue deutsche und englische Affiliate-Links nach Freigabe gesammelt ersetzen. Alle Partnerlinks behalten sponsored nofollow. Die englischen Seiten sind keine automatischen Spiegel: spätere redaktionelle Änderungen an deutschen Texten überschreiben englische Fassungen nicht.

## Lokale Prüfung und Grenzen

Polylang 3.8.9 Hersteller-Quellstand 8a308ebe034672d41fd5dc711107a123806db02f mit offiziellen Composer-Klassen-/Loader-Buildschritten lokal geprüft. Rank Math, LiteSpeed und Complianz aus den bestehenden Testständen aktiv. Hersteller-Admin-Buildassets sind nicht vollständig gebaut; die lokal geprüfte Sprachkonfiguration verwendet die echten PHP-APIs, nicht einen simulierten Polylang-Ersatz. Auf Hostinger das reguläre offizielle Plugin verwenden.

32 Kombinationen aus acht deutschen/englischen Seiten und vier Bildschirmbreiten geprüft: Layout ohne horizontalen Überlauf, Bilder geladen, Sprachzuordnung, Menüs, Sprachwechsel, sechs Anbieter, reservierter Abstand, sponsored-Links und kein TradeTracker-Aufruf. Details in MULTILINGUAL-BROWSER-PRUEFUNG.json. Weitere SEO-/Editorprüfung wird im Prüfbericht dokumentiert.

Die englischen Seiten wurden ausschließlich in der lokalen Testinstallation zeitweise veröffentlicht, um echte URLs und hreflang zu prüfen; der Import auf Hostinger legt sie als Entwürfe an. Es wurde nichts auf Hostinger installiert oder aktiviert. LiteSpeed-HIT auf echtem Server, reale Partnerweiterleitungen/Provisionen, englische Consent-Texte und tatsächliche Tracking-Einwilligung bleiben vor Freigabe zu prüfen.

Zusätzliche Prüfung: alle fünf englischen Gutenberg-Seiten ohne ungültige Blöcke; pro veröffentlichter deutscher/englischer Testseite genau ein selbstreferenzierendes Canonical und zwei wechselseitige Sprachalternativen. Die ursprünglichen deutschen Menüzuordnungen werden ausdrücklich in Polylangs Sprachmenü-Zuordnung übernommen. Ein neuer anonymer lokaler Besuch setzt `pll_language`; der Standardwert der Herstellerquelle für die Laufzeit ist ein Jahr. Dies ist in der privaten Datenschutz-Arbeitsfassung ergänzt; der frühere leere Hostinger-Scan stammt aus der Zeit vor Polylang.

Die englische Ratgeber-Entwurfsvorschau wurde zusätzlich mit einem englischen und einem deutschen Testbeitrag geprüft: nur der englische Beitrag erscheint in der englischen Übersicht. Beide Testbeiträge sind wieder gelöscht. Für einen statischen Export beider Sprachen `python3 scripts/build-offline-preview.py --english` während der temporär veröffentlichten lokalen Review-Fixtures ausführen; der normale Aufruf exportiert nur die vier deutschen Seiten und benötigt keine öffentlichen englischen Entwürfe.

Nach Rücksetzen der lokalen Englisch-Fixtures auf Entwurf wurde geprüft: öffentlich bleiben nur die deutschen Karten sichtbar; die englische Preisseite ist nicht öffentlich erreichbar, und der öffentliche Sprachschalter zeigt keine unfertigen englischen Seiten. In der angemeldeten englischen Seitenvorschau werden die sechs englischen Anbieterentwürfe trotzdem angezeigt. Der Park-Drive-Fly-Button bleibt auch dort auf Parkos beschränkt. In WordPress bei Bedarf die Seiten-/Anbieterliste auf Sprache Englisch filtern.

Footer-Ergänzung in Theme 0.4.1: Impressum und Datenschutz werden nach Veröffentlichung automatisch verlinkt. Entwürfe, fehlende Seiten und passwortgeschützte Seiten bleiben unverlinkt. Bereits manuell im Footer angelegte Ziele werden nicht doppelt ausgegeben. Englische Links erscheinen erst mit veröffentlichten, korrekt in Polylang zugeordneten englischen Rechtstexten. Entwurfsseiten werden nicht automatisch veröffentlicht. Die geschützte lokale Prüfung mit temporären deutschen/englischen Seiten bestätigt Ziele, Entwurfs-Schutz, fehlende Duplikate und responsive Darstellung.

Korrektur Anbieter 0.3.1: Die Sprachfilterung setzt keine bereits abgeschlossene Sprachvorbereitung mehr voraus. Deutsche Seiten zeigen aktive veröffentlichte deutsche und noch sprachlose Bestandsanbieter; englische Anbieter werden ausgeschlossen. Englische Seiten bleiben strikt auf Englisch beschränkt. Lokal nachgestellt und geprüft mit sechs vollständig sprachlosen Bestandsanbietern, gemischter Zuordnung sowie englischer Ausgabe; alle ursprünglichen Sprachen und Entwurfszustände anschließend wiederhergestellt. Ein Neuimport oder Löschen ist nicht erforderlich.

Korrektur Inhaltsentwürfe 0.3.1: Polylang kann bereits leere Übersetzungen angelegt haben, die der normale Import bewusst überspringt. Neuer separater Button „3. Leere englische Seiten vervollständigen“ ergänzt nur solche Platzhalter mit vollständigem englischem Inhalt, Titel, kanonischem englischem Slug, Seitenlayout und SEO-Metadaten, ohne neue Duplikate anzulegen. Reparierte Seiten werden zur Vorschau auf Entwurf gesetzt, auch wenn der leere Platzhalter schon veröffentlicht war. Nichtleere Inhalte und vorhandene Elementor-Layouts bleiben geschützt. Danach LiteSpeed-Cache leeren und die Vorschau derselben englischen Seite öffnen.
