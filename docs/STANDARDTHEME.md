# Wiederverwendbare Basis für Flughafenparken

## Gemeinsamer Kern

VPM Hybrid 0.6.0 ist die erste standortkonfigurierbare Basis. Bestehender Theme-Slug bleibt vpm-hybrid, Anbieter-CPT und Shortcode bleiben unverändert. Theme und Anbieterplugin lassen sich in getrennten WordPress-Installationen wiederverwenden. WordPress-Multisite, eine eigene Buchungssoftware oder ein zentraler Datenabgleich sind dafür nicht erforderlich und werden nicht durch dieses Update eingerichtet.

Enthalten: responsive Grundgestaltung, Gutenberg-/Elementor-Anbindung, vorhandene Blockvorlagen, Wortmarke oder eigenes WordPress-Logo, lokalisierter Footer, Service-/Seiten-/FAQ-/Breadcrumb-Schema sowie die bisherigen gezielten Kompatibilitätsfunktionen. Das Schema verwendet den konfigurierten Service und bei einem Flughafenprofil dessen Flughafen. Ein Dachmarkenprofil behauptet keinen einzelnen Flughafen. Die Ab-Preise bleiben aus sichtbaren EUR-Tabellen abgeleitet, einschließlich des dort genannten Tageszeitraums, kein Datenklon aus München. Andere Währungen und zusätzliche Buchungsplattformen benötigen einen eigenen Ausbau.

## Zentral konfigurierbares Profil

Unter Design → Flughafenparken-Profil eine Vorlage bewusst übernehmen oder das eigene JSON bearbeiten. Eine solche Änderung speichert nur die technische Option vpm_site_profile. Sie erstellt/überschreibt/veröffentlicht keine Seiten oder Anbieter und ändert weder Permalinks, Polylang-Zuordnung, WordPress-Adressen noch Menüs.

Felder: Marke/Hauptzeile/Unterzeile, Einzelstandort oder Dachmarke, deutsche/englische Service- und Flughafenbezeichnungen, CTA-Text/-Anker, lokale Seitenpfade für Preise/Sicherheit/Über uns/Ratgeber. Pfade sind local-only und müssen zu vorhandenen Seiten passen. Übersetzungen und Hauptmenüs weiterhin über WordPress/Polylang zuordnen. Wenn weitere Sprachen benötigt werden, ist das ein eigener Ausbau; diese Version deckt Deutsch/Englisch ab.

Das Profil wird nur bei explizitem Speichern dauerhaft gesetzt. Ohne Profil bleibt eine erkannte Münchner Bestandsinstallation kompatibel; eine neue fremde Installation bleibt neutral mit ihrem WordPress-Seitentitel. Bestehende Customizer-CTA-/Footertexte und ausgewählte Logos/Icons haben weiterhin Vorrang.

Münchner Besonderheiten bleiben auf das Münchner Profil beschränkt: Park-Drive-Fly-Einzelbutton, die bisherige Text-basierte Linkkorrektur, die automatisch eingefügten Münchner Redaktionsbilder, die bekannten abstrakten Anbietermotive und das gebündelte VPM-Favicon. Andere Projekte bekommen diese Elemente nicht ungefragt. Eigene Bilder/Grafiken pro Projekt über Gutenberg/Elementor/Mediathek verwenden.

## Neue Website anlegen

1. Separate WordPress-Installation, SSL und beide WordPress-URLs auf der vorgesehenen HTTPS-Domain einrichten. Sicherung vor Konfigurationsänderungen.
2. Aktuelle, überprüfte Theme-/Anbieter-ZIPs installieren; eigenes Logo und Website-Icon auswählen. Das Münchner Inhaltsentwürfe-Plugin nicht als Starter für andere Flughäfen verwenden: es ist ein standortspezifisches Inhaltspaket und blockiert seine Importoberfläche bei fremdem Profil.
3. Gewünschtes Profil bewusst speichern, Domains/Marke/Pfade/Anker prüfen. Die Profilvorlagen enthalten keine Partnerlinks oder Betreiber-/Tracking-Zugangsdaten.
4. Eigene Seiten auf Grundlage der vorhandenen Blockvorlagen als Entwürfe bauen. Eigene freigegebene Standorttexte, Preise, Bilder und Anbieter ergänzen. Neue Inhalte werden in dieser Version redaktionell angelegt; ein allgemeiner Contentpaket-Importer ist noch nicht enthalten.
5. Falls zweisprachig: aktuelle sichere Polylang-Version installieren, DE/EN-Verzeichnisstruktur und echte Seitenpaare/Menüs einrichten. Der alte Import des englischen München-Pakets ist kein allgemeiner Übersetzer und für andere Profile blockiert.
6. Anbieterplugin: eigene Anbieter als Entwurf anlegen, Merkmale/Preise/Plattformlinks prüfen. Parkos/ParkVia sind die derzeit vorhandenen Buchungsfelder. Beliebige weitere Partnerdatenquellen, zentrale Anbieterbestände und eigenes Booking sind spätere Erweiterungen.
7. Affiliate-Domainfreigabe und Tracking-ID-Zuordnung pro Domain klären. CleanLinking bleibt standardmäßig aus; neue Projekte benötigen vor einer späteren bewussten Aktivierung eine eigene bestätigte vpm_cleanlinking_site_id. Die alte Münchner ID ist nur ein begrenzter Bestandsfallback im Münchner Profil. Kein pauschales Ersetzen der Münchner URL-Kennungen. Rechtstexte und Cookie-Einstellungen für die tatsächlichen Funktionen der jeweiligen Website erstellen.
8. Vor Veröffentlichung: vollständige Link-/Ankerprüfung, eigene Inhalte/Bilder, Smartphone/Tablet/Desktop, Banner und Einwilligung, englische Startseite ohne Schleife, Canonicals/hreflang/Sitemap und Schema prüfen. Keine Erreichbarkeit oder Google-Ergebnisdarstellung allein aus lokalem Test ableiten.

## Bestandswebsite München aktualisieren

Aktuelle drei ZIPs als Ersatz installieren, kein erneuter Inhaltsimport. Bereits bestehende Münchner Seiten, Anbieter, Metadaten, Menüs und Sprachen bleiben erhalten. Ein Profilwechsel ist für den Bestand nicht erforderlich. Nicht das Frankfurt-/Dachmarkenprofil auf der Münchner Produktivseite übernehmen.

Die Version ist vorbereitet und lokal zu prüfen; ein GitHub-Push installiert nichts auf Hostinger. Tests stehen unter STANDORT-PROFILE-PRUEFUNG.json. Pluginstände unterscheiden: Betreiber zuletzt Polylang 3.9.1 als funktionierend und ohne Hostinger-Warnung bestätigt; ältere lokale Fixtures sind keine neue Produktionsfreigabe.

## Wissensbasis

AGENTS.md enthält Regeln und wichtige Entscheidungen; STANDORTE.md ordnet Domains/Profile zu. Ein neuer Chat mit Zugriff auf dieses Repository kann den Stand einlesen. Zugriff und Auswahl des richtigen Repositorys müssen im jeweiligen Chat verfügbar sein. Updates pro Website zunächst prüfen und bewusst einspielen, keine automatische Verteilung auf alle Domains.
