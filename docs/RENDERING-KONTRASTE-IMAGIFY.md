# Rendering, Kontraste und Imagify

Stand: 11. Oktober 2026 · Theme 0.3.4 · Inhaltsentwürfe 0.2.1. Lokale WordPress-Prüfung; keine Änderung auf Hostinger.

## Renderblockierende Ressourcen

Die vier öffentlichen Seiten laden drei regulär renderblockierende CSS-Dateien: Theme, Anbieterkomponente und Complianz-Cookieblocker. Ihre zusammen übertragene Größe liegt lokal bei rund 8 KiB. Das Navigationsscript ist `defer`; es gibt in den geprüften Aufrufen kein synchron geladenes externes Script und keine externen Schriftdateien. Kleine Inline-Scripts, darunter das Setzen des Menüstatus vor dem ersten Bildaufbau, bleiben bewusst bestehen.

Die CSS-Dateien werden zum korrekten ersten Seitenaufbau benötigt. Eine Umstellung auf nachgeladenes CSS wurde nicht vorgenommen: Sie könnte ungestaltete Inhalte und Layoutsprünge erzeugen. Die Anbieter-CSS bleibt für die Verwendung des Shortcodes auch in Elementor verfügbar. Neu: Ratgeber-/Artikel-CSS wird nur auf Beiträgen und Seiten mit nativer Query-Abfrage geladen, nicht auf den vier bisherigen Seiten. Cookie-/Tracking-Scripts sollen nach ihrer tatsächlichen Einrichtung erneut geprüft werden; Consent-Code wird nicht pauschal verzögert.

Erfasst wurden echte Chromium-Ressourceneinträge mit `renderBlockingStatus`, Scriptattribute und CSS-Farben. Rohdaten: [RENDER-KONTRAST-AUDIT.json](RENDER-KONTRAST-AUDIT.json). Die lokalen Requestzeiten sagen nichts über Hostinger aus.

## Schrift- und Iconkontraste

361 Textelemente auf den vier Desktop-Seiten geprüft, einschließlich der Goldverläufe an Buttons. Kleinster erfasster Schriftkontrast: **5,17:1**; Anforderung für normale Schrift: 4,5:1. Icon-Strichfarben: mindestens **4,86:1** auf hellen Flächen; navyfarbene Pfeile auf dem dunkelsten Gold-Endpunkt: **5,27:1**. Der goldene USP-Kreisrand erreicht auf Creme **3,02:1**. Für funktionale Icons und Bediengrenzen gilt 3:1.

Korrigiert wurden die zuvor zu hellen grauen Umrandungen sekundärer Buttons und die Menübutton-Umrandung. Die sekundäre Hero-Buttonfläche und die Hero-Bildkennzeichnung haben nun einen festen hellen Hintergrund. Damit hängt deren Kontrast nicht von der Fotografie darunter ab. Kleine USP-Iconstriche sind etwas kräftiger. Rein dekorative Goldlinien und Kartenrahmen müssen keine funktionalen Kontrastwerte erfüllen; lesbare Beschriftungen und Fokusmarkierungen bleiben erhalten.

Die Prüfung umfasst CSS-Farbkombinationen und die visuelle Kontrolle der aktuellen Bilder. Sie ist kein vollständiges WCAG-Zertifikat und kein flächendeckender Pixeltest beliebiger späterer Fotos. Inhaltstexte und Linkziele wurden nicht verändert.

## Imagify-Kompatibilität

Geprüfter Hersteller-Quellstand: Imagify 2.3.4, Commit in [IMAGIFY-QUELLSTAND.json](IMAGIFY-QUELLSTAND.json).

- Hero und Anbieterbilder lagen bereits in der Mediathek. Auch die zusätzlichen Unterseitenbilder werden jetzt bevorzugt über WordPress-Mediathek und `wp_get_attachment_image()` ausgegeben. Bildmetadaten, responsive Größen, Alt-Texte und feste Dimensionen stehen Image-Plugins damit regulär zur Verfügung. Ohne importierte Mediathek-Motive bleiben die bisherigen Theme-WebPs als Fallback verfügbar.
- Der echte Imagify-Picture-Formatter wurde mit vorhandenen realen WebP-URLs als synthetischen Formatdaten ausgeführt. Die erzeugte Ausgabe bestand Browserprüfungen auf vier Seiten und vier Breiten: Bilder laden, keine verschachtelten Pictures, Dimensionen/Alt-Texte bleiben erhalten und kein mobiler Hero-Bildrequest.
- Imagifys tatsächlicher `process_content()`-Code lässt das bereits vorhandene Hero-`picture` unverändert. Der mobile Ladeausschluss bleibt bestehen. In Imagifys Picture-Modus wird dieses bestehende Hero-Picture dadurch nicht automatisch mit zusätzlichen AVIF-Sources ergänzt.

**Prüfgrenze:** Das WordPress-Installationsarchiv war aus dieser Umgebung mit HTTP 403 nicht erreichbar. Der Hersteller-Quellstand enthält keine vollständigen installierbaren Vendor-Abhängigkeiten. Imagify wurde deshalb nicht als vollständiges Plugin aktiviert. Es wurden keine Komprimierung, API-Verbindung, Quota, echte Formatkonvertierung oder Hostinger-Rewrite-Regeln getestet. Die Formatdaten im Browsertest waren ausdrücklich synthetisch, keine behauptete Imagify-Optimierung.

Nach Installation auf Staging: API verbinden, Original-Backups behalten, ein einzelnes Mediathek-Bild samt Größen optimieren und Desktop/Mobil prüfen. Erst danach weitere Bilder optimieren. Bei Imagify als Bildoptimierer nicht gleichzeitig LiteSpeeds separate Bildoptimierung/Formatersetzung einschalten; der LiteSpeed-Seitencache kann weiterhin genutzt werden. Die mitgelieferten Theme-Fallbackbilder außerhalb der Mediathek benötigen gegebenenfalls Imagifys Custom-Folder-Funktion. Die echte Serverkonfiguration bleibt zu prüfen.

Die Quell-/Ausgabeprüfung ist reproduzierbar über `bash scripts/setup-imagify-source-check-local.sh` nach dem regulären lokalen Setup. Sie installiert Imagify nicht.
