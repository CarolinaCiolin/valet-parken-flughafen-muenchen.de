# Ladeverhalten und Vorschau

## Bereits umgesetzt

Theme 0.1.4 setzt den mobilen Menüstatus vor dem ersten Bildaufbau. Dadurch entfällt der nachträgliche Sprung beim Einklappen der Navigation. Ohne JavaScript bleiben die Menülinks zugänglich. Das Navigationsscript wird mit `defer` geladen.

Hero und Anbieterkarten erhalten 480-Pixel-Bildvarianten und passende responsive `sizes`-Angaben. Die Bildgrößen folgen der tatsächlichen Layoutbreite. Seit dem Designstand 0.3.0 liegt das Hero-Bild auf Desktop hinter dem rechten Teil des großflächigen Heros. Kartenbilder bleiben lazy geladen, haben feste Bilddimensionen und werden asynchron decodiert. Native WebP-Bilder, lokale Systemschrift und sechs unterschiedliche Anbieterbilder werden lokal und verzögert geladen.

## Lokaler Vergleich

Drei unabhängige Läufe je Stand, Median. Chromium: 390 × 844 Pixel, DPR 1, Browsercache ausgeschaltet, 150 ms simulierte Latenz, 1,6 Mbit/s Download und CPU vierfach verlangsamt. WordPress läuft weiterhin auf lokalem Apache ohne echten LiteSpeed-Seitencache. Es handelt sich um einen Labortest, keine Hostinger- oder Besuchermessung.

| Messwert | Vorher | Nachher |
| --- | ---: | ---: |
| Größter sichtbarer Inhalt (LCP) | 0.848 s | 0.692 s |
| Layoutverschiebung (CLS) | 0.200 | 0.000 |
| Übertragene Daten beim initialen Aufruf | 109783 Bytes | 72715 Bytes |

Die initial übertragene Datenmenge sinkt in diesem Szenario um 33.8 %. Der beobachtete Layoutsprung wurde beseitigt. Unterschiede einzelner Ladezeiten schwanken; diese drei Läufe sind keine belastbare Vorhersage für Hostinger. Der Labortest misst keine echten Nutzerinteraktionen und liefert keinen INP-Feldwert.

Neue Bildvarianten werden bei der ersten Einrichtung über die Mediathek erzeugt. Falls bereits vor dem Update importierte Bilder genutzt werden, deren Bildgrößen in WordPress regenerieren; das Original bleibt erhalten. Im lokalen Test wurden die Varianten der beiden mitgelieferten Illustrationen neu erzeugt.

## Was nach dem Umzug folgt

Auf Hostinger Serverantwortzeit, echten LiteSpeed-Cache-HIT, Kompression, Browsercache-Header, tatsächliche Plugin-/Consent-Konfiguration und sitemapbasiertes Preloading prüfen. Danach einen mobilen PageSpeed-/Lighthouse-Labortest durchführen und später Feldwerte beobachten. JS-Verzögerung, Kombinierung und zusätzliche Bilddienste wurden nicht eingeschaltet.

## Klickbare Vorschau ohne WordPress

`vpm-klickbare-vorschau.zip` vollständig entpacken und `index.html` im Browser öffnen. Menülinks führen zu den vier enthaltenen HTML-Seiten, FAQs lassen sich aufklappen, Bilder und Styles liegen lokal bei. Die Partnerlinks führen bei Klick weiterhin online zum jeweiligen Buchungspartner. Die Vorschau enthält weder Backend noch endgültige Cookie-Konfiguration.

Die Dateien wurden durch einen statischen lokalen Server auf Layout, Bilder, Navigation, FAQs und mobiles Menü geprüft. Der Cloud-Browser blockiert `file://` durch seine Administratorrichtlinie; direktes Öffnen per Doppelklick konnte daher hier nicht ausgeführt werden. Der Export benötigt keine Fetch-API, Module oder WordPress-Verbindung und verwendet gewöhnliche relative Dateien für das Öffnen auf einem normalen Rechner.

Die Chat-Umgebung bietet keinen freigegebenen Live-Preview-Port. Deshalb werden Screenshots direkt im Chat gezeigt und die klickbare Vorschau als ZIP bereitgestellt. Kein localhost-Link wird als benutzbare Nutzervorschau ausgegeben.

## Aktueller Designstand 0.3.0

Die oben genannten Vorher-/Nachher-Werte dokumentieren den früheren Speed-Durchgang. Mit neuer Gestaltung, Wortmarke und unterschiedlichen Bildern: drei mobile Labordurchläufe, medianer LCP 888 ms, CLS jeweils 0, initiale Übertragung 95.438 Bytes. Weiterhin keine Hostinger- oder Felddaten.
