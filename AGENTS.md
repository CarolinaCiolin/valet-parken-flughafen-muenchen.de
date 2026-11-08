# Flughafenparken – Projektregeln und Einstieg

Dieses Repository enthält den gemeinsamen Flughafenparken-Kern und die bestehende Münchner Website. Zuerst docs/STANDARDTHEME.md und docs/STANDORTE.md lesen; aktuelle Pakete und Versionen stehen in pakete/README.md. Nicht automatisch davon ausgehen, dass ein neuer Auftrag München betrifft.

## Architektur

- Theme-Slug vpm-hybrid sowie CPT vpm_provider, Shortcode vpm_providers und bestehende Metaschlüssel erhalten. Keine Umbenennung auf Produktivseiten bei einem normalen Update.
- Standortdaten in Option vpm_site_profile, Vorlagen in wordpress/themes/vpm-hybrid/profiles. Keine automatische Standortumschaltung anhand einer fremden neuen Domain. Unkonfigurierte neue Installationen bleiben neutral.
- München: kompatibler Standard für bekannte Münchner Bestandsinstallation; WordPress-Inhalte, IDs, Menüs, Sprachen, Partnerlinks und Bilder erhalten. Legacy-Ausnahmen niemals auf Frankfurt oder die Dachmarke übertragen.
- Frankfurt und valet-parkservice.com sind technische Profilvorlagen, keine fertigen Websites und keine freigegebenen Inhalte. Dachmarke nicht pauschal als Münchner/Frankfurter Service auszeichnen.
- Theme für Darstellung, Anbieterplugin für redaktionelle Anbieterdaten. Der bestehende Inhaltsimport enthält ausschließlich München; nicht für andere Standorte einsetzen. Kein Live-Inhalt wird bei Aktivierung oder Update erzeugt/veröffentlicht.
- Seitenrollen/Pfade pro Profil; vorhandene _vpm_layout_slug/_vpm_import_slug-Metadaten haben Vorrang. Neue Pfade müssen zu tatsächlich angelegten veröffentlichten Seiten und Polylang-Paaren passen.

## Verbindliche Nutzerentscheidungen

- Bestehende freigegebene Texte/Überschriften nicht ungefragt umschreiben. Eigene Texte und Angebote pro Standort, keine bloße Stadtnamen-Ersetzung oder identische lokale SEO-Kopien.
- Keine Datums-/Uhrzeiteingabe und kein fremdes Buchungswidget. Buchung beim Partner. Eigene Partner/Buchungssystem sind ein späterer eigener Auftrag.
- Keine erfundenen Preise, Bewertungen, Verfügbarkeit, Sicherheitsgarantien oder „mehr als 15 Anbieter“. Ab-Preise nur aus sichtbaren freigegebenen Angaben mit Zeitraum. Schema nicht als Preis-Livefeed darstellen.
- Affiliate-Links rel="sponsored nofollow". IDs/Tracking-Parameter nicht selbst neu zuordnen. Neue Domains benötigen eigene bestätigte Affiliate-Zuordnung und freigegebene Links.
- Abstrakte Anbietergrafiken sind freigegeben. Fotorealistische KI-Motive weiterhin kennzeichnen. Andere Standorte erhalten nicht automatisch Münchner Motive oder VPM-Monogramm.
- Breadcrumbs nur als Markup, keine sichtbare Breadcrumb-/Themenleiste. Kein JSON-LD auf Rechtstexten. Portal nicht als Parkplatzbetreiber auszeichnen.
- Gutenberg und Elementor unterstützen. Keine externe Schrift oder neue Besucheranalyse ungefragt aktivieren.

## Technischer Verlauf, nicht als Herstellerzusage lesen

- Produktiv laut Betreiber zuletzt WordPress-Adressen beide HTTPS; Polylang 3.9.1, Hostinger-Warnung nach Update verschwunden und /en/ wieder funktionsfähig. Ursache der damaligen Schleife nicht endgültig bewiesen. Kein prophylaktischer Redirect-Hack oder Downgrade.
- Lokale ältere Pluginfixtures sind keine Empfehlung für neue Produktivinstallationen. Vor neuem Rollout aktuelle Sicherheitsstände prüfen, keine bekannte betroffene Polylang-Version installieren.
- Complianz: beobachtet wurden Bannerbedarf=true, aber fehlende Banner-/Asset-Hooks bei registriertem Ohne-Banner-Hook. Eng begrenzter Fix in consent-compatibility.php, vom Betreiber als funktionierend bestätigt. Keine Zustimmung erzwingen. Diagnose ist kein allgemeiner Beweis für jede Pluginversion.
- Direktzugriff auf Live-Domain war aus Cloud häufig mit CONNECT-403 blockiert. Nicht als Website-403 darstellen. Lokale Tests, Benutzerberichte und echte Live-Prüfung auseinanderhalten.

## Arbeiten und Prüfen

- Bestehenden Checkout benutzen; kein Worktree ohne Auftrag. Keine Subagents ohne ausdrücklichen Auftrag.
- Vor Änderungen git status prüfen. Zugangsdaten, .runtime, access-vpm und lokale Testfixtures nicht veröffentlichen. Betreiber hat die konkrete Rechtstext-JSON in pakete ausdrücklich für öffentliche Bereitstellung freigegeben; nicht automatisch sonstige private Begleitdateien veröffentlichen.
- Für UI-/Profiländerungen scripts/test-site-profiles.py (enthält test-profile-routing.php), für Schemaänderungen scripts/test-schema-context.py ausführen. Weitere gezielte Tests nach Änderungsumfang. Testfixtures/Sprachstatus/Profile nach lokalen Tests zurücksetzen.
- Docker-Testcontainer vpm-local-wp/vpm-local-db; Wiederstart docker start vpm-local-db und danach vpm-local-wp. Localhost 8088 ist nur intern, nicht als Nutzervorschau ausgeben. Keine Setup-Seeds über bestehenden redaktionellen Testbestand laufen lassen.
- Neue Cloud ohne lokale Container: README.md und Setup-Skripte prüfen; Secrets nur auf Vorhandensein prüfen, nicht ausgeben. Bei neuer Installation Sicherheits-Pins vor Nutzung alter optionaler Fixtures prüfen.
- python3 scripts/package.py baut installierbare ZIPs; ZIPs in pakete und SHA256SUMS aktualisieren. Pakete sind öffentlich, keine Secrets hinein. Quellcode und Anleitung pro Version zusammen speichern.
- Nutzer kann GitHub-Links/Downloads nutzen; Chat-Bildvorschauen funktionierten teilweise nicht. Nach Push commitgebundene GitHub-Links bevorzugen. Kein direkter Hostinger-Upload behaupten, wenn nur Pakete gebaut wurden.

## Wiederaufnahme in neuem Chat

Bei verfügbarem GitHub-Zugriff dieses Repository öffnen, diese Datei und docs/STANDORTE.md lesen. Ziel-Domain, gewähltes Profil, letzte installierte Version und freigegebene Inhalte feststellen. GitHub ist die dauerhafte Quelle; kein automatisches chatübergreifendes Gedächtnis oder Zugriff ohne Verbindung versprechen.
