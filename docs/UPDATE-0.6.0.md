# Update 0.6.0 – wiederverwendbarer Flughafenparken-Kern

Pakete: VPM Hybrid 0.6.0, VPM Anbieter 0.3.3, VPM Inhaltsentwürfe (München) 0.4.2. Technischer Ausbau für die spätere Wiederverwendung; keine automatische Installation auf Hostinger.

## Neu

- Profilverwaltung unter Design → Flughafenparken-Profil: Wortmarke, Flughafen oder Dachmarke, Services in DE/EN, Seitenpfade und Header-CTA.
- Vorlagen für München, Frankfurt und valet-parkservice.com. Neue Installationen ohne Profil bleiben neutral. Bestehendes München wird ohne Datenmigration kompatibel erkannt, auch über Polylang-Sprachaufrufe.
- Header/Footer und Service-Markup aus dem Profil. Keine Münchner Bilder, VPM-Fallback-Icons oder Anbieter-Ausnahmen auf anderen Profilen. Eine Dachmarke wird nicht pauschal einem einzelnen Flughafen zugeordnet.
- Vorhandene Inhalte und IDs, Texte, Anbieter, Partnerlinks, Menü-/Sprachzuordnungen und Veröffentlichungsstatus bleiben erhalten. Profilwechsel schreibt keine Inhalte um.
- Der bestehende München-Importer und sein englisches Paket sind für andere Profile gesperrt. Neue Projekte brauchen eigene Inhalte; ein allgemeiner Contentpaket-Import ist in dieser Version noch nicht enthalten.
- AGENTS.md, STANDARDTHEME.md und STANDORTE.md als dauerhafte GitHub-Projektbasis. Kein automatisches chatübergreifendes Gedächtnis zugesichert.

## Für neue Projekte

flughafenparken-starter.zip herunterladen und entpacken. Enthält nur Theme und Anbieterplugin als separate installierbare ZIPs sowie Anleitung/Prüfsummen. Nicht das Gesamt-Starter-ZIP als WordPress-Theme hochladen. Das München-Inhaltspaket ist bewusst nicht enthalten. Den in AGENTS.md beschriebenen Ablauf und die Sicherheitsstände der Fremdplugins vor einer neuen Installation prüfen.

Eigene WordPress-Installation, HTTPS-Adressen, bewusst ausgewähltes Profil, redaktionelle Seiten/Anbieter und Medien, sichere Polylang-Konfiguration und echte Sprachpaare, eigene Partnerfreigaben und Rechtstexte pro Website. Frankfurt/Dachmarke sind keine fertigen oder veröffentlichten Websites. Auch ein zentrales Anbieterportal oder eine Flughafen-Datenbank wird damit noch nicht eingerichtet.

## Für die bestehende Münchner Website

Kein sofortiger Rollout erforderlich, wenn zunächst nur das Standardtheme für zukünftige Projekte vorbereitet werden soll. Für ein später bewusst gewähltes Update: Sicherung erstellen, die drei einzelnen ZIPs als Ersatz installieren; kein erneuter Inhaltsimport und keine Umstellung auf Frankfurt oder Dachmarke. Die Profiloption muss beim bestehenden München nicht erst manuell angelegt werden. Bestehende ausgewählte Logos/Icons bleiben erhalten. Native Site-Icons haben Vorrang vor Theme-Fallbacks.

## Prüfung

Details: STANDORT-PROFILE-PRUEFUNG.json. Münchner Frontend wird bei unverändertem Bestand direkt mit dem vorherigen Theme-Commit verglichen; neue Profile in lokaler WordPress-Installation mit temporären eigenständigen Testinhalten geprüft. Kein Hostinger-Deployment, keine Freigabe beliebiger Drittplugins/Sprachen oder fertiger SEO-Inhalte durch diese Tests. Die Sicherheits- und Updateentscheidung zu Polylang 3.9.1 beruht auf dem letzten Betreiberbericht, nicht auf einer neuen Herstellerprüfung.
