# Update 0.5.0 – Schema, Breadcrumbs, Seitenkontext und Website-Icon

## Pakete

- VPM Hybrid 0.5.0
- VPM Anbieter 0.3.2
- VPM Inhaltsentwürfe 0.4.0

Dieses Update enthält auch die bisherigen Sprach-, Anbieter-, Reparatur-, Footer- und Veröffentlichungsfunktionen. Kein neuer Inhaltsimport erforderlich. Vorhandene Texte und Affiliate-URLs werden nicht umgeschrieben.

## Installation auf Hostinger

1. Dateien und Datenbank sichern.
2. Alle drei aktuellen ZIPs aus pakete herunterladen, nicht entpacken.
3. Theme-ZIP unter Design → Themes → Theme hochladen als Ersatz installieren.
4. Die beiden Plugin-ZIPs unter Plugins → Installieren → Plugin hochladen als Ersatz installieren und aktiv halten.
5. Unter Werkzeuge → VPM Inhaltsentwürfe optional „VP-Website-Icon einrichten“ anklicken. Ein vorhandenes eigenes Icon bleibt erhalten. Ohne gewähltes WordPress-Icon liefert das Theme bereits ein kleines VP-Favicon aus. Zum bewussten Austausch eines vorhandenen Icons die beigefügte Grafik in den WordPress-Website-Einstellungen auswählen.
6. LiteSpeed Cache leeren. Auf Deutsch und Englisch Unterseiten, Breadcrumbs und das Browser-Icon kontrollieren. Browser können Favicons besonders lange zwischenspeichern.

Es wird bei Aktivierung oder Aktualisierung nichts automatisch veröffentlicht. Ratgeber und Rechtstext-Status bleiben unverändert. Analytics und CleanLinking bleiben unverändert ausgeschaltet. Bestehende deutsche und englische Menüs bleiben erhalten.

## Markup-Zuordnung

| Inhalt | Schema |
| --- | --- |
| Portal insgesamt | Organization als Herausgeber, WebSite |
| Deutsche und englische Startseite | CollectionPage, ItemList mit den tatsächlich ausgegebenen aktiven veröffentlichten Anbietern |
| Preise | WebPage, BreadcrumbList |
| Sicherheit | WebPage, BreadcrumbList; FAQPage für die vorhandenen Fragen/Antworten |
| Über uns | AboutPage, BreadcrumbList |
| Veröffentlichter Valet-Ratgeber | CollectionPage, BreadcrumbList; ItemList der tatsächlich passenden Beitragsabfrage, sofern Beiträge vorhanden sind |
| Veröffentlichte einzelne Blogbeiträge | WebPage und BlogPosting, BreadcrumbList |
| Tatsächlich vorhandene FAQ-Abschnitte, auch Startseite | FAQPage als Teil des jeweiligen Seiteninhalts; Fragen/Antworten aus den sichtbaren aufklappbaren Inhaltsblöcken |
| Impressum und Datenschutz, einschließlich Übersetzungen | Kein VPM-/Rank-Math-JSON-LD auf diesen Seiten |

Keine fingierten Bewertungen, Prüfsiegel, AggregateRating, Review-, Product- oder Parkplatzbetreiber-/LocalBusiness-Auszeichnung. Das Portal wird nicht als Betreiber der vermittelten Parkplätze dargestellt. Flüchtige Ab-Preise werden nicht als verbindliche Offer-Preise ausgegeben. Keine SearchAction für eine nicht angebotene Besuchersuche.

## Breadcrumbs und Einordnung

Unterseiten erhalten vor dem Inhalt eine schmale Breadcrumb-Navigation und die zusätzliche Themenzeile mit Link zur übergeordneten Seite und dem Schwerpunkt der aktuellen Seite. Beispiele: Startseite → Preise; Home → Safety. Blogartikel werden dem veröffentlichten sprachlich passenden Ratgeber zugeordnet: Home → Valet Parking Guide → Article. Solange der Ratgeber noch Entwurf ist, wird keine nicht erreichbare Ratgeber-Adresse verlinkt.

HTML-Breadcrumbs und BreadcrumbList beruhen auf derselben Hierarchie. Bestehende Überschriften und Absätze bleiben unverändert; nur die ausdrücklich gewünschte ergänzende Kontextzeile kommt hinzu. Auf der Startseite und den Rechtstexten wird keine zusätzliche Kontextleiste gezeigt.

## Rank Math / HFCM

Das Theme stellt seinen Graph über rank_math/json_ld bereit und ersetzt damit auf Inhaltsseiten die dort sonst erzeugten Standardentitäten, darunter unpassende Article-/Benutzernamen-Daten auf normalen Seiten. Auf Rechtstexten wird die JSON-LD-Ausgabe unterdrückt. Ist Rank Maths Schema-Ausgabe nicht verfügbar, gibt das Theme denselben Graph selbst aus. Die Ausgabe erfolgt höchstens einmal. Titel, Descriptions, Canonicals und Sitemap bleiben weiter bei Rank Math.

Keinen zusätzlichen parallelen Organization-, WebSite-, Breadcrumb- oder FAQ-Graph über HFCM einfügen. Bereits manuell eingebaute andere Drittanbieter-Schema-Ausgaben kann das Theme nicht generell erkennen. Bei einem später selbst gestalteten Elementor-Single-Template die sichtbare Breadcrumb-Leiste im Layout berücksichtigen; die Standardtemplates sind vorbereitet.

FAQPage ist keine Zusage eines Google-FAQ-Rich-Results. Google beschränkt diese Darstellung stark, insbesondere auf bekannte Behörden- und Gesundheitsangebote. Korrekte strukturierte Daten ersetzen weder gute Inhalte noch eine Indexierungsfreigabe.

## Website-Icon

VP-Monogramm in Dunkelblau, Creme und Gold, ohne fotorealistische Darstellung. Theme-Fallback: 64×64 PNG mit 3.711 Bytes. Für WordPress wird ein 512×512 PNG mit 133.546 Bytes mitgeliefert; der explizite Import legt native Größen für 32, 180, 192 und 270 Pixel an. Das Icon gilt für beide Sprachen. Keine externen Bildabrufe, kein zusätzliches Tracking.

## Prüfung

Details in SCHEMA-KONTEXT-PRUEFUNG.json. Lokal getestet: zehn veröffentlichte deutsche/englische Seiten, zwei temporäre Blogartikel, zwei synthetische Rechtstext-Seiten, eine Schema-Ausgabe mit eindeutigen IDs, korrekte Typen und Sprachangaben, vorhandene Anbieter-Anker, FAQ-Fragen identisch zu den Details, sprachlich passende Ratgeberbeiträge, Blog-/Ratgeberhierarchie, keine Schemas auf Rechtstexten, Fallback ohne Rank Math, native Icon-Größen und Erhalt eines vorhandenen Icons. 30 Kombinationen aus Seiten und Breiten ohne horizontalen Überlauf.

Die vorübergehend veröffentlichten lokalen Entwürfe sind anschließend wieder zurückgesetzt; synthetische Artikel/Rechtstexte wurden entfernt. Keine Veröffentlichung oder Installation auf Hostinger erfolgt. Kein externer Google Rich Results Test / Schema Markup Validator ausgeführt; dies ist eine lokale Struktur- und Integrationsprüfung, keine Google-Freigabe.
