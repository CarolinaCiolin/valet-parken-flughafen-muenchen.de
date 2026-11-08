# Dachmarke: Buchungskern 0.2.0 und Flughafenauswahl

Stand 8. November 2026. Separates Entwicklungsmodul für die Dachmarke, kein erforderliches Update der Münchner Affiliate-Seite. Keine Live-Zahlung, keine automatische Veröffentlichung und keine erfundenen Partnerangebote. Vorgänger und Zahlungsgrenzen: [0.1.0](BOOKING-CORE-0.1.0.md), [E-Mail-Vertrag](BOOKING-EMAIL-CONTRACT.md).

## Was umgesetzt ist

- Drei native Gutenberg-Blöcke: **VPM Flughafenauswahl**, **VPM Buchungsanfrage**, **VPM Newsletter-Anmeldung**. Flughafen und Sprache im Block einstellbar. Dynamisch gerendert, keine fest kopierten Formulare.
- Elementor-Free-Einbettung über das vorhandene Shortcode-Widget. Shortcodes ebenso in Gutenberg nutzbar. Mehrere Instanzen mit eigenen IDs, gemeinsame schlanke CSS-/JS-Dateien, keine externen Fonts oder Frontend-Bibliotheken. Nachgeladenes Elementor-DOM wird berücksichtigt.
- Vorauswahl mit `airport="FRA"`; Oberfläche `locale="de"`, `"en"` oder `"auto"` (Polylang/WordPress-Sprache). Keine IP-Erkennung oder Suchmaschinen-Sonderbehandlung. Vorschau sperrt Anfragen.
- Eigene, bearbeitbare Flughafenauswahl mit 15 Startwerten. Keine Standortseiten bei Aktivierung angelegt. Zielseiten müssen veröffentlicht sein; eine fehlende EN-Übersetzung führt nicht zur deutschen Ersatzseite.
- Anbieteradapter liefert serverseitige Angebote, zehn Minuten gültige Angebotsreferenz, anschließend Anfrage mit E-Mail und optionalem Gutschein. Clientpreise und Statusmanipulation werden zurückgewiesen. Lokale Flughafenzeit, übersprungene und doppelte Uhrzeiten bei Zeitumstellung berücksichtigt.
- Nur **Anfrage**, kein öffentliches zahlungspflichtiges Buchen. Die Oberfläche startet noch keinen Stripe-Checkout und reserviert keine Partnerkapazität. Vor einem verbindlichen Checkout ist eine erneute Kapazitäts-/Preisprüfung mit dem führenden Partnersystem nötig. Technische Gutscheinreservierung ist noch kein Partnerstellplatz.
- WordPress-Datenauskunft, kontrollierter Löschworkflow, Site-Health-Prüfung für Hosting und private REST-Antworten ergänzt. Aufbewahrungsfristen bleiben zu klären; Finanz-/Einwilligungsnachweise werden nicht ungeprüft gelöscht. Marketing wird bei Löschanfrage gesperrt.

## Einbettung

ZIP `pakete/vpm-booking-core.zip` unter Plugins → Installieren → Plugin hochladen. Bestehende 0.1.0 bestätigen; Schema ergänzt eine Angebotstabelle. Zunächst Testinstallation und Sicherung verwenden. Kein Inhaltsimport und keine Theme-Umschaltung nötig.

Unter Werkzeuge → VPM Buchungskern stehen Einbettung, bewusste Freigabe der öffentlichen Anfragen, Messereignisse, Flughafenkatalog und Hostingstatus. **Anfragen und Analyse sind standardmäßig aus.** Flughafenauswahl funktioniert unabhängig davon.

```text
[vpm_airport_selector]
[vpm_booking_form airport="FRA" locale="de"]
[vpm_booking_form locale="en"]
[vpm_booking_newsletter]
```

Für die Dachmarken-Startseite den Auswahlblock einmal einfügen: Er erhält den Anker `#flughaefen`, auf den das bestehende Dachmarken-Profil bereits zeigt. Weitere Auswahlblöcke bekommen eindeutige Anker. Nicht auf der bestehenden Münchner Affiliate-Startseite zusätzlich ein Buchungsformular einsetzen.

## Flughäfen und Standortseiten

DE: Frankfurt FRA, München MUC, Berlin Brandenburg BER, Düsseldorf DUS, Hamburg HAM, Köln/Bonn CGN, Stuttgart STR, Hannover HAJ, Nürnberg NUE, Dortmund DTM.

AT: Wien VIE, Salzburg SZG, Innsbruck INN. Schweiz/Grenzregion: Zürich ZRH und Basel–Mulhouse–Freiburg BSL. EuroAirport liegt tatsächlich in Frankreich; das ist im Katalog entsprechend hinterlegt. Auswahl deutschsprachig orientiert, keine belegte Verkehrsrangliste oder Aussage über vorhandene Partner.

Im Verwaltungsbereich den JSON-Katalog bearbeiten: `name_de`, `name_en`, IATA-`code`, `region`, tatsächliches `location_country`, IANA-`timezone` und `page_id`. `page_id: 0` bedeutet noch keine Zielseite. Später die ID der eigenen deutschen Standortseite eintragen; Polylang verknüpft ihre englische Übersetzung. Keine frei eingegebenen externen Weiterleitungs-URLs.

Ohne Zielseite übergibt die Auswahl den Flughafen an ein vorhandenes Anfrageformular. Gibt es auch dieses nicht, erscheint ein Vorbereitungshinweis. Keine Weiterleitung auf leere oder erfundene Standortseiten. Bearbeitung erzeugt keine Buchbarkeit.

## Anbieteradapter

Bewusst kein Standardpreis und kein Testanbieter im installierbaren Plugin. Ein eigener serverseitiger Adapter registriert den Filter `vpm_booking_quote_offers` mit zwei Argumenten: bisherige Angebotsliste und Kontext `{airport,timezone,departure_utc,return_utc}`. Kontextzeiten sind Unixzeitpunkte, Zeitzone ist separat gespeichert.

Rückgabe maximal 20 Angebote mit `partner_id` (bereits im Buchungskern angelegter Partner desselben Flughafens), `base_cents` (Integer, mindestens 50), `currency: "eur"`, `terms` (Klartext, höchstens 1200 Bytes). Keine Kundendaten im Angebotsaufruf. Die Anfrageschnittstelle übernimmt nur gespeicherte serverseitige Angebote, niemals eine Preiskalkulation aus dem Browser.

Öffentliche JSON-POST-Routen: `/wp-json/vpm-booking/v1/public/offers` und `/public/requests`. Anfrage nutzt `quote_id`, `email`, optional `coupon`, `locale`. Opaque Referenzen, getrennte Rollen und bestehende Sandbox-Webhooks bleiben bestehen. Rate-Limit 30 Aufrufe pro IP/Minute; bei Reverse-Proxy nur vertrauenswürdig ermittelte Client-IP verwenden, keine ungeprüften Forwarded-Header. Kein vollständiger Bot-/Missbrauchsschutz. Keine öffentliche Kundenauskunft über Referenz allein.

**Noch nicht enthalten:** fertiger Herstelleradapter, Partnerkapazitäten, verbindliche Partnerbestätigung, öffentliches Stripe-Bezahlen, vollständiges Kunden-/Partnerportal, CHF-Abrechnung oder automatische Rechnungserzeugung. EUR ist die einzige implementierte Währung; Schweizer Flughafen im Auswahlfeld bedeutet keine CHF-Unterstützung. Das neue öffentliche Anfragefrontend ersetzt keinen Partnervertrag.

## Münchner Pluginumfeld und Grenzen

| Komponente | Vorbereitung / Prüfung |
| --- | --- |
| WordPress / Gutenberg | Native serverseitige Blöcke, Inspector-Attribute, Shortcodes; lokaler Editor und Frontend geprüft. PHP 8.1+, WP 6.6+ deklariert; nicht jede Version getestet. |
| Elementor Free | Shortcode-Ausgabe und dynamisches DOM unterstützt. Kein Pro-Widget oder eigener Theme Builder nötig. Echter Elementor-Editor noch separat zu prüfen. |
| VPM Hybrid / Standardtheme | Oberflächen mit gekapseltem CSS, responsive 1440/768/390 px geprüft; keine Änderung des Münchner Themes. |
| Complianz | Messereignisse brauchen explizit `cmplz_has_consent('statistics') === true`; fehlende API, Ablehnung, GPC und DNT blockieren sie. Widerruf wird bei jedem Event neu geprüft. CMP-Vertrag im neuen Browserlauf simuliert, nicht als Volltest aller Complianz-Einstellungen ausgeben. Bestehende Theme-Bannerkorrektur unverändert. |
| HFCM / Google Analytics | Lädt selbst kein Google-Skript. Nutzt genau eine gewählte bestehende Brücke: `dataLayer`, vorhandenes `gtag` oder DOM-Ereignis. HFCM-Plugin selbst in diesem Lauf nicht verfügbar und nicht getestet. GA-Snippet separat in Complianz korrekt kategorisieren; HFCM allein ist keine Einwilligungssperre. |
| LiteSpeed / Hosting | Private API-, DOI- und Zahlungsrückkehr-Antworten `no-store`, `noindex`, `no-referrer`; `DONOTCACHEPAGE` und LiteSpeed-Nocache-Hook. Nur eigenes Formularskript als nicht zu optimieren markiert. Keine pauschalen Änderungen an Cache-Einstellungen. Server-/CDN-Konfiguration und echte Hostinger-Laufzeit nicht geprüft. |
| Rank Math / Polylang | Keine zusätzlichen Canonicals, Sitemaps oder JSON-LD durch Buchungsmodul; keine Redirectfilter. Standortziele sprachabhängig. Vorhandenes Schema nicht duplizieren. Lokale ältere Pluginfixtures sind keine Empfehlung; produktiv aktuellen sicheren Stand beibehalten. |
| Imagify | Formular/Selector verwenden keine Rasterbilder und verändern keine Mediathek-/Bildfilter. Deshalb keine konkurrierende Bildoptimierung. Keine neue Imagify-API-/Kompressionsprüfung dieses Moduls. |
| E-Mail / Newsletter | Bestehende signierte Outbox und Double-Opt-in weiterverwendet, explizites nicht vorangekreuztes Marketingfeld. Versanddienst und Texte separat konfigurieren; Einwilligungstext wird nicht automatisch übersetzt. Vorschau sendet keine Anfragen. |
| Datenschutz / Hosting | WP-Auskunft/Löschprüfung, InnoDB/OpenSSL/64-Bit/HTTPS-Status, letzter Worker und Adapterstatus. Kein Node/Composer/Docker auf Hostinger erforderlich. Echte Servercron-Einrichtung und externe Webhooks separat testen. |

Messereignisse: `vpm_booking_view`, `vpm_booking_search`, `vpm_booking_offer_select`, `vpm_booking_request`, `vpm_booking_error`. Ausschließlich `booking_flow`, `locale`, optional Flughafen und technischer Fehlercode. Keine E-Mail, Daten/Uhrzeiten, Gutscheine, Angebots-/Buchungsreferenzen, Partnerkonten oder Provisionen. Kein Replay von Aktionen vor Zustimmung. Anfrageereignis nicht als bezahlten Umsatz markieren.

## Reproduzierbare Prüfung

Prüflauf: WordPress 7.1.3, PHP 8.3.35, Elementor Free 3.33.0, VPM Hybrid 0.6.0 und Twenty Twenty-Five. Mit aktiv im lokalen Bestand: Complianz 7.5.5, LiteSpeed 7.9.1, Rank Math 1.0.280 und ältere Polylang-Fixture 3.8.9. Diese Fixture wird nicht ausgeliefert und darf nicht als empfohlener Sicherheitsstand verstanden werden; Betreiber meldet produktiv Polylang 3.9.1 und Complianz 7.5.6. Keine komplette Versionsmatrix.

Nur bestehende lokale Loopback-Testinstallation, eigene Buchungstabellen müssen leer sein. Testadapter und Testkundendaten werden entfernt; keine echten Preise, Partner, Zahlungen, E-Mails oder GA-Aufrufe.

```bash
VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-compat-local.sh
```

Ergebnisse: [Backend](BOOKING-CORE-PRUEFUNG.json), [Erweiterung](BOOKING-COMPAT-DOMAIN-PRUEFUNG.json), [Browser](BOOKING-COMPAT-BROWSER-PRUEFUNG.json). Prüfung ist keine Freigabe eines fertigen Live-Buchungssystems.
