# Vorschau – neue Münchner Angebote und Video

Nur auf dem Preview-Branch; keine Live-Installation erfolgt. Zum Ansehen **[Webvorschau herunterladen](vpm-affiliate-webvorschau.zip)**, vollständig entpacken und index.html öffnen.

- [YouTube-Uploadpaket](vpm-youtube-upload.zip): fertiges Full-HD-Video, Untertitel, Thumbnail, Titel und Beschreibung.
- [Bearbeitbare Videoprojektdateien](vpm-erklaervideo-projekt.zip): Python/JSON, Audio, Fonts und Hinweise.
- [WordPress-Entwurfspaket](vpm-affiliate-entwurfspaket.zip): VPM Anbieter 0.3.4 und Inhaltsentwürfe 0.4.3; nicht zum Ansehen erforderlich. Kein automatisches Veröffentlichen.

[Umfang und Prüfungen](../docs/AFFILIATE-VIDEO-VORSCHAU.md)

---

# Gemeinsamer Flughafenparken-Kern

## Neue Projekte

**[flughafenparken-starter.zip](flughafenparken-starter.zip)** – Theme 0.6.0 und Anbieterplugin 0.3.4 mit Anleitung. Erst entpacken, dann die zwei darin enthaltenen ZIPs einzeln in WordPress installieren. Enthält keinen Münchner Contentimport, keine aktive Affiliate-/Tracking-Konfiguration für neue Projekte und keine Zugangsdaten. Technische Profile für Frankfurt und valet-parkservice.com, noch keine fertigen Websites.

[Standardablauf](../docs/STANDARDTHEME.md) · [Standorte](../docs/STANDORTE.md) · [Projektregeln](../AGENTS.md)

## Einzelpakete / bestehende Münchner Website

- [VPM Hybrid 0.6.0](vpm-hybrid.zip) – standortkonfigurierbares Theme mit allen bisherigen Funktionen.
- [VPM Anbieter 0.3.4](vpm-providers.zip) – vorhandene Anbieterkomponente, Münchner Motive auf anderen Profilen nicht automatisch zugeordnet.
- [VPM Inhaltsentwürfe 0.4.3 – nur München](vpm-content-drafts.zip) – vorhandene Inhalte/Import-/Veröffentlichungsfunktionen, auf anderen Standortprofilen gesperrt.

[Änderungen und Installation](../docs/UPDATE-0.6.0.md) · [Prüfsummen](SHA256SUMS.txt)

ZIPs über Download raw file herunterladen. Für ein bewusstes München-Update die einzelnen Theme-/Plugin-ZIPs als Ersatz installieren, nicht entpacken, kein erneuter Inhaltsimport. Zunächst Sicherung anlegen. Die Profilvorlagen auf der bestehenden Website nicht auf andere Standorte umstellen. Ein GitHub-Update ist keine automatische Installation auf Hostinger.

## Separat: Münchner Rechtstexte und Icon

[VPM-Rechtstext-Entwuerfe.json](VPM-Rechtstext-Entwuerfe.json) enthält mit ausdrücklicher Betreiberfreigabe öffentlich vorgesehene Münchner Betreiber-/Kontaktangaben. Kein allgemeines Rechtstextpaket für andere Projekte. Unter Werkzeuge → VPM Inhaltsentwürfe → Private Rechtstext-Entwürfe als JSON importieren; bestehende Seiten werden übersprungen. Die bisherigen offenen Datenschutz-Prüfpunkte bleiben bestehen. Keine englischen Rechtstexte enthalten.

[Helles VPM-Icon als 512-Pixel-PNG](../zwischenstand/website-icon/vpm-website-icon-512.png) für das Münchner Branding. Andere Marken erhalten ein eigenes WordPress-Logo und Website-Icon; die Beispielprofile übernehmen dieses Icon nicht automatisch.

## Optionaler Buchungskern – Dachmarke, nur Entwicklung

[VPM Buchungskern 0.14.1](vpm-booking-core.zip): separater Entwicklungsbaustein mit gemeinsamen Valet-/Shuttle-Beständen, Lade-Teilbestand, Flug-/Servicezeiten, flexiblen Tarifen, Partnerbenachrichtigungen, Partner-Anfahrtsdaten, Supportkontakt, Dokumentenzuständigkeit, Partnerportal, manuellen Kapazitäten, mehrstufiger Demo, Adaptervertrag, Gutschein-Preisprüfung, optionaler persönlicher Treueaktion, lokalen/allgemeinen Formularen, Stellplatzarten, bepreisten Zusatzleistungen, Betreiberverwaltung, Support ohne Kundenkonto, Stornowünschen, Flughafenauswahl (15 Startwerte), Gutenberg-Blöcken, Elementor-Shortcodes, Anfragefrontend, Sandbox-Stripe-Connect, Gutschein-/Erstattungsjournal und E-Mail-Ereignissen mit DOI. Keine Live-Zahlungen; öffentlicher Gastcheckout nur im gesondert aktivierten Testmodus, keine Rechnungserzeugung. Nicht Bestandteil des allgemeinen Starters; für die laufende Münchner Affiliate-Seite nicht erforderlich. [Partnerkontakte und Anfahrt](../docs/BOOKING-LOGISTICS-0.7.0.md) · [Portal, Demo und Betrieb](../docs/BOOKING-PLATFORM-0.6.0.md) · [Gutscheine und Treueaktion](../docs/BOOKING-VOUCHERS-0.5.0.md) · [Lokale Formulare und Zusatzleistungen](../docs/BOOKING-PRODUCTS-0.4.0.md) · [Verwaltungsablauf](../docs/BOOKING-OPERATIONS-0.3.0.md) · [Kompatibilität und Grenzen](../docs/BOOKING-COMPAT-0.2.0.md).

Buchungskern 0.9.0: [Partnerbedingungen, Aktionen, Einrichtung und Grenzen](../docs/BOOKING-POLICIES-0.9.0.md). Optionales Entwicklungspaket für die Dachmarke; kein benötigtes Update der Münchner Affiliate-Seite.

Buchungskern 0.10.0: [Kontaktvermittlung, Plattformfreigabe und Testweg](../docs/BOOKING-BROKER-0.10.0.md). Alias-/Nachrichtenlogik vorbereitet; noch keine echte Mailweiterleitung oder Klaviyo-Anbindung.

## Separate Testdomain

[Testdomain-Installationspaket](vpm-testdomain-installation.zip) – erst entpacken; enthält Theme, Anbieterkomponente, Buchungskern sowie Anleitung und lesende CLI-Vorprüfung. Kein Münchner Inhaltsimport. [Voraussetzungen und Mailtest](../docs/TESTDOMAIN-INSTALLATION.md). Kein automatischer Hostinger-Upload und Test-Outbox-Adapter optional enthalten, echter SMTP-Zugang separat einzurichten.

Buchungskern 0.11.0: [Branding, Dashboards und Testcheckliste](../docs/BOOKING-BRANDING-0.11.0.md). Testdomain-Paket enthält die aktuelle Version; bei bestehender Installation Buchungskern aktualisieren, keine Inhalte erneut importieren.

Buchungskern 0.12.0: [Update und Testanleitung](../docs/BOOKING-EXPERIENCE-0.12.0.md). Bereits installierte Testdomain: nur vpm-booking-core.zip ersetzen, keinen Inhaltsimport durchführen.

**Vorversion: Buchungskern 0.13.0.** Bei bestehender Testinstallation nur [vpm-booking-core.zip](vpm-booking-core.zip) hochladen und ersetzen. [Update-/Testanleitung](../docs/BOOKING-JOURNEY-0.13.0.md). Neue Suche klein/groß mit eigener mehrstufiger Buchungsseite, Partnernachrichten, Belegung und optionale Testkasse/Testmail. Keine automatisch aktivierten Zahlungen oder Sendungen.

**Vorversion: 0.14.0.** [Buchungskern-ZIP](vpm-booking-core.zip) bei bestehender Testinstallation ersetzen. [Update und Automatisierung](../docs/BOOKING-WORKFLOWS-0.14.0.md): geführte Partnerhinweise, Beispielreisen, Supportkategorien und zentraler Ausnahmebereich. Keine automatisch aktivierten E-Mails oder Zahlungen.

**Aktuell: 0.14.1.** [Pluginupdate](vpm-booking-core.zip) mit einheitlicher Alternativenprüfung und privater Suchprüfung für konkrete Reisedaten. [Anleitung zum gemeldeten Suchproblem](../docs/BOOKING-SEARCH-0.14.1.md). Keine automatische Änderung von Beständen oder Einstellungen.
