**Leere englische Startseite:** Inhaltsentwürfe 0.3.1 aktualisieren, dann Werkzeuge → VPM Inhaltsentwürfe → „3. Leere englische Seiten vervollständigen“. Repariert nur leere, bereits zugeordnete englische Seiten; diese bleiben/werden Entwurf.

**Korrektur für fehlende Anbieterkarten nach Polylang-Aktivierung:** VPM Anbieter 0.3.1 zeigt auf Deutsch auch noch nicht zugeordnete Bestandsanbieter. Nur vpm-providers.zip aktualisieren, danach Cache leeren. Unter Werkzeuge → VPM Inhaltsentwürfe weiterhin „1. Deutsch und Englisch vorbereiten“ ausführen.

# Gemeinsames Update 0.4.0

Drei ZIPs direkt in WordPress hochladen, nicht entpacken. Vorher Dateien und Datenbank sichern.

- [VPM Hybrid 0.4.1](vpm-hybrid.zip) – Theme, Abstände und englische Darstellung.
- [VPM Anbieter 0.3.1](vpm-providers.zip) – neue illustrative Karten und Sprachunterstützung; CleanLinking bleibt aus.
- [VPM Inhaltsentwürfe 0.3.1](vpm-content-drafts.zip) – englische Entwürfe, Polylang-Vorbereitung und privater Rechtstext-Upload.

Je ZIP öffnen und „Download raw file“ wählen. Theme unter Design → Themes → Theme hochladen, Plugins unter Plugins → Installieren → Plugin hochladen. Die bestehende Version ersetzen, anschließend LiteSpeed Cache leeren.

**[Schritt-für-Schritt-Anleitung](../docs/UPDATE-0.4.0.md)**

Für die Sprachvorbereitung zuerst das offizielle kostenlose Polylang installieren und aktivieren. Dann Werkzeuge → VPM Inhaltsentwürfe → „1. Deutsch und Englisch vorbereiten“ und danach „2. Englische Entwürfe importieren“. Alle neuen Seiten und Anbieter bleiben Entwürfe. Originale werden nicht überschrieben. Keine automatische Browser-/IP-Weiterleitung.

Betreiberdaten stehen nicht im öffentlichen Paket. Die separate private Rechtstext-JSON wird auf derselben Werkzeugseite hochgeladen und ausschließlich als Entwürfe importiert. Englische Partnerlinks und Freigaben sind noch offen; englische Seiten vorerst unveröffentlicht lassen.

Fremdplugins werden nicht mitgeliefert. [SHA-256-Prüfsummen](SHA256SUMS.txt) gehören genau zu diesen drei ZIPs.
