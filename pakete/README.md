# Aktuelles Update – Schema, Ab-Preise und Website-Icon

Vorher Dateien und Datenbank sichern. ZIPs nicht entpacken, in WordPress als Ersatz für die bestehenden Pakete hochladen.

- [VPM Hybrid 0.5.5](vpm-hybrid.zip) – Complianz-Cookie-Richtlinie im Footer und lesende PHP- und Browser-Cookie-Diagnose; deutscher Affiliate-Hinweis auch bei leerem Hinweisfeld; Service- und Ab-Preis-Schema, Breadcrumb-Markup ohne sichtbare Themenzeile, Favicon und alle bisherigen Theme-Funktionen.
- [VPM Anbieter 0.3.2](vpm-providers.zip) – stabile Anbieter-Anker für das Schema; vorhandene Grafiken, Links und Sprachkorrektur bleiben erhalten.
- [VPM Inhaltsentwürfe 0.4.0](vpm-content-drafts.zip) – natives Website-Icon; bisherige Sprach-, Reparatur-, Rechtstext- und Veröffentlichungsfunktionen enthalten.

**[Aktuelle Ergänzungen und Installation](../docs/UPDATE-0.5.5.md)** · [Schema-/Icon-Grundupdate](../docs/UPDATE-0.5.0.md) · [Prüfsummen](SHA256SUMS.txt)

Theme unter Design → Themes → Theme hochladen, Plugins unter Plugins → Installieren → Plugin hochladen. Danach LiteSpeed Cache leeren. Kein erneuter Textimport nötig. Optional unter Werkzeuge → VPM Inhaltsentwürfe „VP-Website-Icon einrichten“ ausführen. Bestehende eigene Icons bleiben erhalten.

Bei der Aktualisierung wird nichts automatisch veröffentlicht. Ratgeber und Rechtstexte behalten ihren Status. Englisch-Veröffentlichung und Reparatur leerer Platzhalter sind weiterhin über getrennte Buttons verfügbar. Tracking wird nicht aktiviert. Betreiberangaben gehören weiterhin nur in die separate private Rechtstext-Importdatei.

## Rechtstexte – separat importieren

[VPM-Rechtstext-Entwuerfe.json](VPM-Rechtstext-Entwuerfe.json) herunterladen (Download raw file). Diese Datei enthält mit ausdrücklicher Freigabe des Betreibers die öffentlich vorgesehenen Betreiber- und Kontaktangaben. Die privaten Begleitunterlagen werden nicht mit veröffentlicht.

Unter Werkzeuge → VPM Inhaltsentwürfe → Private Rechtstext-Entwürfe die JSON auswählen und importieren. Legt fehlendes Impressum und Datenschutz als deutsche Entwürfe an; bestehende Seiten werden übersprungen. Anschließend Inhalte durchsehen und über „Importiertes Impressum und Datenschutz veröffentlichen“ veröffentlichen. Kein normaler WordPress-Import und kein Theme-/Plugin-Upload dieser JSON.

Datenschutz-Arbeitsfassung zum besprochenen technischen Stand: Hosting-Speicherfristen und Vertragsdetails zur Datenverarbeitung sind noch nicht abschließend geklärt. Google Analytics, Klaviyo und CleanLinking werden als nicht aktiv behandelt; vor deren Aktivierung muss der Text angepasst werden. Englische Rechtstexte sind nicht enthalten.
