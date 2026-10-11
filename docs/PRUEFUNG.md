# Prüfergebnis des technischen Grundgerüsts

Prüfung am 11. Oktober 2026 in der lokalen Cloud-Testinstallation, nicht auf Hostinger.

- WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4.13.
- Offizielles kostenloses Elementor 3.33.0 aus dem Elementor-GitHub-Release; Archiv-Checksumme im Setup-Skript festgelegt.
- 24 PHP-/WordPress-Integrationsprüfungen bestanden: Registrierung, aktive/inaktive Anbieter, Entwurfsstatus, Auswahl per ID, unveränderte Affiliate-URLs, `sponsored nofollow`, URL-Sanitierung, REST-Zugriffsschutz, Nonce-/Berechtigungsprüfung und Erhalt der Daten bei Themewechsel.
- Beide ZIPs über den echten WordPress Theme-/Plugin-Upgrader lokal installiert.
- Gutenberg-Vorschau und echte Elementor-Seite mit Shortcode-Widget funktionieren.
- Browserprüfung bei 1440, 768, 390 und 320 Pixeln: kein horizontaler Überlauf, eine H1, sechs Anbieter. Mobile Navigation einschließlich Escape geprüft.
- Gutenberg-Blöcke sind gültig. Anbieterfelder in der nativen Gutenberg-Seitenleiste bearbeitet, gespeichert, neu geladen und auf ursprüngliche Testwerte zurückgesetzt.
- Keine Browser-JavaScript-Fehler in den geprüften Abläufen. Frontend-Styles und Fonts werden lokal geladen.
- Lokaler Indexierungsschutz (`noindex, nofollow`) und erfolgreicher funktionaler HTTP-Aufruf geprüft.
- Eigene Testcontainer gestoppt und neu gestartet; Setup erneut ausgeführt. Wiederholung überschreibt initialisierte Testinhalte nicht und erzeugt keine doppelten Anbieter.

## Grenzen

Das ist eine technische Vorschau mit dem gelieferten Einstieg und sechs Anbieter-Datensätzen, keine vollständig übertragene Startseite. Ein vorläufig freigegebenes KI-Heromotiv ist jetzt im nativen Bildblock mit sichtbarer Kennzeichnung eingebunden. Vollständige Inhalte und alle seitenbezogenen Linkzuordnungen folgen nach Abnahme des Grundgerüsts.

Elementor Pro ist nicht installiert; die Theme-Builder-Locations sind vorbereitet, aber nicht praktisch getestet. Die Hostinger-Installation und dortige WordPress-/PHP-/Plugin-Versionen sind ungeprüft. Die Erreichbarkeit der Partnerseiten ist aus dieser Cloud weiterhin nicht bestätigt. Es wurde keine neue Cloud-Umgebung nach Veröffentlichung geprüft.

ZIPs enthalten keine Testzugänge, SSH-Schlüssel, Testskripte, Testdaten oder WordPress-Datenbank. Das Theme allein garantiert keine Core-Web-Vitals-Werte für die spätere Website; diese werden mit den tatsächlichen Bildern, Seitentexten und Plugins geprüft.

## Inhaltsblock: vier Seiten

Startseite, Preise, Sicherheitsseite und Über uns aus den gelieferten PDFs als Gutenberg-Blöcke vorbereitet. Tabellen rekonstruiert (Startseite zehn Vergleichszeilen, Preise sieben Anbieterzeilen); Partnerlinks aus PDF-Annotationen übernommen. Acht Homepage- und drei Sicherheits-FAQs als native aufklappbare Blöcke. Nutzerfreigabe: kein Datumseingabemodul und keine dazugehörigen Aufforderungen. Entfernte Sätze dokumentiert in `REDAKTIONELLE-UEBERNAHME.json`.

Importfunktion lokal mit Administratorkontext geprüft: vier Entwürfe angelegt; vorhandene sechs Anbieter übersprungen; Wiederholung legt keine Duplikate an. Ausschließlich für lokale Browser-Vorschauen werden Testkopien veröffentlicht; das ausgelieferte Importplugin veröffentlicht nichts. Hostinger blieb unberührt.

Ergebnis: alle vier Seiten bei 1440, 768, 390 und 320 Pixeln ohne horizontalen Seitenüberlauf; Tabellen innerhalb eigener horizontaler Scrollbereiche. Genau eine H1 pro Seite; sämtliche importierten Blöcke vom echten Gutenberg-Editor als gültig erkannt. Zusätzlich sechs neue Anbieter als Entwürfe getestet und lokale Testdaten wieder entfernt. Aktualisiertes Theme 0.1.2 behebt die Inhaltsbreite des Full-Width-Templates.

Nach Nutzerfreigabe wurde die Mengenangabe „mehr als 15“ entfernt und „24/ Betrieb“ zu „24/7 Betrieb“ korrigiert. Lokale Vorschau, Anbieterquelle und Importpaket sind aktualisiert. Rechtstexte werden nach Einrichtung der Plugins bearbeitet.

## Übergabeprüfung, Theme 0.1.3

Vier Hauptmenü- und drei Footerlinks auf allen vier Seiten geprüft. Alle internen URLs und Sprungziele erreichbar. Hauptinhalt über Skip-Link fokussierbar; mobiles Menü über Enter/Escape und FAQs über Enter bedienbar. Deutsche HTML-Sprachkennzeichnung. Vier Bildschirmbreiten ohne Seitenüberlauf. Alle geladenen Seitenressourcen stammen vom lokalen Server; Affiliate-Zielseiten wurden dabei nicht aufgerufen. Hero-Bild mit Alt-Text, Dimensionen und responsivem srcset geladen; KI-Kennzeichnung sichtbar. Textkontraste: Fließtext 12,49:1, Navy auf Gold 6,40:1, Bildunterschrift 6,33:1. Diese ausgewählten Prüfungen sind keine vollständige WCAG-Zertifizierung.

24 Anbieter-Integrationsprüfungen und die echten Gutenberg-/Elementor-Browserprüfungen bestanden. Wiederholter Setup-Lauf bewahrt bestehende Inhalte und Einstellungen. Deutsches Core-Sprachpaket im Container nicht herunterladbar (Proxy-403); HTML-Sprache über lokale WordPress-Konfiguration gesetzt, Core-Oberfläche kann teilweise Englisch bleiben. Für Hostinger Deutsch über die regulären Einstellungen installieren. Elementor Pro und Hostinger-Geschwindigkeit bleiben ungeprüft.

Lokale Momentaufnahme (kein Hostinger-Benchmark):

| Seite | Navigation inkl. Laden | Übertragene Bytes | Ressourcen |
| --- | ---: | ---: | ---: |
| startseite | 161 ms | 135543 | 6 |
| preise | 167 ms | 10284 | 4 |
| sicherheit | 112 ms | 11634 | 4 |
| ueber-uns | 112 ms | 9239 | 4 |

## Anbieter-Symbolbilder

VPM Anbieter 0.1.1, Inhaltsentwürfe 0.1.2: ein gemeinsames KI-Symbolbild mit sichtbarer Kennzeichnung, ohne Zuordnung zu einem tatsächlichen Anbieterstellplatz. WebP-Quelldatei 71.836 Bytes bei 1200 Pixeln Breite. WordPress erstellt responsive Bildvarianten; Kartenbilder werden lazy geladen. In der lokalen Startseitenprüfung sieben geladene Bilder (Hero plus sechs Karten) und rund 179 KB übertragene Ressourcen. Eine gemeinsame Mediendatei vermeidet sechs separate Bilddownloads.

Geprüft: Bildzuweisung nur durch Administratoren, Wiederholung ohne Duplikate, vorhandene Bilder geschützt, Entwurfsstatus unverändert und KI-Kennzeichnung auch bei geleertem Caption-Feld für das mitgelieferte Symbolbild erhalten. 24 Integrationsprüfungen und die erneute Gutenberg-/Elementor-Frontend-/Backendprüfung bestanden. Alle vier Seiten weiterhin ohne horizontales Überlaufen bei vier Breiten; interne Links, Tastaturbedienung, Alt-Texte, Bilddimensionen und srcset geprüft. Linkinventar umfasst 42 verwendete Links; externe Partnerziele wurden nicht als erreichbar bestätigt.

## Gewünschte Plugin-Kombination

Rank Math 1.0.280, LiteSpeed Cache 7.9.1 und Complianz 7.5.5 als gleichzeitige Hersteller-Quellstandsinstallation geprüft; exakte Stände in `PLUGIN-QUELLSTAENDE.json`. Rank Math benötigte das reguläre Neubauen seines generierten Autoloaders und das Überspringen der optionalen Kontoregistrierung. SEO-Titel/Beschreibungen stimmen mit der Vorlage überein. Im indexierbaren lokalen Test genau eine Canonical pro geprüfter Seite; XML-Sitemap enthält die vier Seiten und keine VPM-Anbieterarchive. Indexierung nach dem Test wieder deaktiviert.

Complianz-Test mit synthetischem Statistik-Zähler: vor Zustimmung und nach Ablehnung keine Ausführung; nach Zustimmung einmal pro Seitenaufruf; beide Entscheidungen bleiben beim Neuladen erhalten. Das Testfixture wird anschließend deaktiviert und die ursprünglichen Complianz-Optionen wiederhergestellt. Es wurden weder echte Trackingdienste noch Betreiber-/Rechtstexte konfiguriert.

VPM Anbieter 0.1.2 bündelt Änderungen veröffentlichter Anbieter in eine Cache-Purge-Anforderung am Request-Ende; Statuswechsel, relevante Meta-Felder, Bildänderungen und Löschen werden berücksichtigt. Entwurfsänderungen und andere Inhaltstypen lösen keine Anbieter-Purge-Anforderung aus. Auslösung und Zusammenfassung lokal geprüft; ein tatsächlicher Cache-HIT bleibt auf Apache ungeprüft.

HFCM konnte mangels verifiziertem Installationspaket nicht installiert werden. Seine erforderlichen Theme-Hooks wurden mit separatem lokalem Testcode geprüft. Kein Anspruch auf einen vollständigen HFCM-Test, echten LiteSpeed-Servercache oder Crawler-Preload. Optional reproduzierbar über `scripts/setup-plugin-compatibility-local.sh` nach dem regulären lokalen Setup.

Die echte Gutenberg-/Elementor-Browserprüfung bestand auch bei gleichzeitig aktiven drei Fremdplugins, einschließlich Speichern und erneutem Lesen der Anbieterfelder. Nicht erreichbare externe Admin-APIs wurden ausschließlich in der lokalen Testinstallation durch ein MU-Testplugin sofort mit einem Nicht-verfügbar-Fehler beantwortet. Dieser Testhelper wird nicht ausgeliefert und bestätigt keine Online-Funktionen.

## Designüberarbeitung, 11. Oktober 2026

Theme 0.2.0 gestaltet vorhandene Abschnitte zur Ausgabe als Sektionen und Karten. Das gespeicherte Gutenberg-Dokument bleibt unverändert. Der Vergleich der sichtbaren Textinhalte aller vier Seiten mit der vorherigen Vorschau bestätigte identische Texte; externe Partnerlinks sind ebenfalls identisch. Bestehende alleinstehende Textlinks werden als Buttons dargestellt, einschließlich „Mehr über uns erfahren“.

Unter 900 Pixeln erscheinen Tabellenzeilen als Karten mit den unveränderten Spaltenüberschriften. Die Tabellen behalten Tabellen-, Zeilen- und Zellrollen sowie Spaltenüberschriften. Alle Zellen bleiben sichtbar; es gibt keine abgeschnittenen Spalten. Geprüft bei 320, 390, 768 und 1440 Pixeln. Native Blöcke aller vier Seiten sind im Gutenberg-Editor weiterhin gültig. Bilder werden vor der Vollbildaufnahme tatsächlich geladen.

## Mockup-Durchgang – Theme 0.3.0

Wortmarke mit goldenen Linien, großflächiger Hero mit Bildübergang, gemeinsames Seitenraster mit linksbündigen Überschriften und Absätzen, sechs unterschiedliche Anbieterbilder sowie zwei Bildbereiche je Unterseite. Die genehmigte Preistabellen-Darstellung bleibt erhalten.

Text- und Linkabgleich aller vier Seiten: unverändert; neue Bildkennzeichnungen werden separat behandelt. Anbieterbilder: sechs verschiedene Mediathek-IDs, Wiederholung ohne Änderungen, Schutz eigener Bilder auch bei ausdrücklicher Aktualisierung, Berechtigungen und KI-Kennzeichnung geprüft. Echte Anbieterbilder werden nicht behauptet. Gutenberg-/Elementor-Frontend sowie gespeicherte Anbieterfelder mit den bereits lokal aktiven Plugins geprüft; keine Browser-JS-Fehler.

## Konkrete Korrekturen – Theme 0.3.1 / Anbieter 0.2.1

Park Drive Fly zeigt auf der Startseite nur Parkos; sein ParkVia-Link bleibt gespeichert und steht an anderen vorgesehenen Stellen weiterhin bereit. Der Header-Button führt auf allen Seiten zur Startseite mit `#anbieter`; er führte vorher zu ParkVia. Alle sieben Preis-Tabellenbuttons sind im Browser gleich breit.

Ein `picture` mit Desktop-Media-Source und eingebetteter leerer Bildalternative verhindert Hero-Bildrequests auf mobilen Aufrufen. Frische Browserkontexte mit 390 und 768 Pixeln: kein Hero-Request; 1440 Pixel: echtes Bild geladen. USP-Text unverändert, Abstände und mobile Anordnung kompakter.

Der allgemeine Sicherheits-/Provisionshinweis steht am Ende des Sicherheitsartikels außerhalb der Clean-Karte. Bilder bei Umparken und Finanzierung tatsächlich geladen, mit eigenen Detail-Screenshots. Alle Bildvarianten werden nach jedem Wechsel der Screenshot-Breite dekodiert, bevor die Aufnahme erfolgt.

## Freigegebene Gestaltungsvorschläge – Theme 0.3.2

Alle vier Vorschläge umgesetzt. Vorher-/Nachher-Abgleich der Startseite bei 390 und 1440 Pixeln bestätigt identische Überschriften, Absätze und Listen sowie unveränderte Partnerlinks. Nur der ausdrücklich freigegebene sekundäre Hero-Link führt nun zu `/preise/`. Preislink genau einmal vor dem Kartenraster, mobiler Header unter 100 Pixeln; Desktop-Header-CTA weiterhin sichtbar mit Ziel `/#anbieter`. Erste Karte bei 390 Pixeln rund 87 Pixel früher, bei 1440 Pixeln rund 35 Pixel früher.
