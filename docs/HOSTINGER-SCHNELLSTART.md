# VPM auf Hostinger einrichten

Das Gesamtpaket zuerst auf dem Computer entpacken. **Nur die drei einzelnen ZIPs** in WordPress hochladen; das Gesamtpaket ist kein WordPress-Plugin.

1. In Hostinger eine Sicherung von Dateien und Datenbank erstellen. Die Einrichtung zuerst in einer Staging-Kopie durchführen. Suchmaschinen-Sichtbarkeit dort deaktiviert lassen.
2. `vpm-hybrid.zip` unter **Design → Themes → Hinzufügen → Theme hochladen** installieren und aktivieren.
3. `vpm-providers.zip` und danach `vpm-content-drafts.zip` unter **Plugins → Installieren → Plugin hochladen** installieren und aktivieren.
4. Unter **Werkzeuge → VPM Inhaltsentwürfe** die neuen Entwürfe importieren. Vier Seiten und sechs fehlende Anbieter werden angelegt. Bestehende Inhalte bleiben erhalten. Neue Anbieter erhalten das gemeinsame KI-Symbolbild. Bei vorhandenen Anbietern ohne Bild kann anschließend **Fehlende Symbolbilder ergänzen** genutzt werden.
5. Unter **Einstellungen → Allgemein** Website-Titel „Valet-Parken Flughafen München“, Sprache Deutsch und Zeitzone Berlin einstellen. Unter **Permalinks** „Beitragsname“ wählen. In Staging die vier importierten Seiten und Anbieter prüfen und veröffentlichen, damit Navigation und Anbieterkarten vollständig getestet werden können. Die produktive Veröffentlichung folgt erst später.
6. Unter **Einstellungen → Lesen** die importierte „Startseite“ als statische Startseite zuweisen. Sie erhält damit `/`; die übrigen Adressen sind `/preise/`, `/valet-parken-sicherheit/` und `/ueber-uns/`.
7. Unter **Design → Menüs** die folgenden beiden Menüs erstellen und ihren Theme-Positionen zuweisen. Die drei Inhaltsseiten als Seiten-Menüeinträge hinzufügen, damit WordPress ihre Adressen aktuell hält.

| Hauptnavigation | Ziel |
| --- | --- |
| Anbieter | Individueller Link: `/#anbieter` |
| Preise | Seite „Valet Parken München: Preise im Überblick“ |
| Sicherheit | Seite „Ist Valet-Parken sicher?“ |
| Über uns | Seite „Über uns“ |

| Footernavigation | Ziel |
| --- | --- |
| Preise | Preisseite |
| Sicherheit | Sicherheitsseite |
| Über uns | Über-uns-Seite |

Bei einer Staging-Installation in einem Unterverzeichnis den Anbieter-Link mit dem tatsächlichen Startseitenpfad verwenden. Ein Ratgeber und eine separate Vergleichsseite sind nicht Bestandteil der gelieferten Inhalte und werden nicht verlinkt.

8. Unter **Design → Customizer → Header-CTA** den Text „Verfügbarkeit prüfen“ und den allgemeinen ParkVia-Link aus `ALLGEMEINER-PARTNERLINK.txt` eintragen. Unter **Footer-Hinweis** diesen freigegebenen Text aus der Über-uns-Vorlage eintragen:

> Einige Verlinkungen auf dieser Website sind Affiliate-Links. Wenn Sie darüber eine Buchung abschließen, können wir vom jeweiligen Buchungspartner eine Provision erhalten. Der Preis erhöht sich für Sie dadurch nicht.

9. Rank Math aktivieren und unter **Werkzeuge → VPM Inhaltsentwürfe → Leere Rank-Math-Felder ergänzen** die gelieferten SEO-Angaben in leere Felder übernehmen. Alternativ die Angaben aus `SEO-METADATEN.md` manuell eintragen. Für „Über uns“ wurde keine gesonderte Beschreibung geliefert.
10. Auf Desktop und Handy Menüs, Anbieterlinks, Tabellen und FAQs prüfen. KI-Bildkennzeichnung sichtbar lassen. Rechtstexte nach Einrichtung der weiteren Plugins ergänzen; erst danach die Live-Umstellung separat vornehmen.

Elementor ist optional. Die importierten Seiten sind mit Gutenberg bearbeitbar. Für neue Elementor-Layouts lässt sich `[vpm_providers]` im Shortcode-Widget verwenden. Ein Editorwechsel konvertiert die Seiten nicht automatisch. Elementor Pro wurde nicht praktisch geprüft. Weitere Hinweise stehen in `INSTALLATION.md`.

Nach erfolgreichem Import kann das Plugin „VPM Inhaltsentwürfe“ deaktiviert werden. „VPM Anbieter“ bleibt für die Karten aktiv. Ein erneuter Import aktualisiert keine vorhandenen Seiten oder Anbieter.

Für die spätere Pflege von Bildern, Anbieterangaben und Partnerlinks stehen `PFLEGE.md` und `LINK-INVENTAR.csv` bereit. Das Symbolbild behauptet keine tatsächliche Anbieteraufnahme.

Die gewünschte Kombination aus Rank Math, HFCM, LiteSpeed Cache und Complianz wird in `PLUGIN-KOMPATIBILITAET.md` behandelt. Diese Fremdplugins separat aus den offiziellen WordPress-Angeboten installieren; das Gesamtpaket enthält nur die drei VPM-Pakete.
