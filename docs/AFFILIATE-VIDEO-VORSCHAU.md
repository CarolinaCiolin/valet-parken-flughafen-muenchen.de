# Affiliate-Erweiterung und 2D-Video – Reviewstand 21.11.2026

Nur Branch `preview/affiliate-video-2026-11-21`, keine Bereitstellung und kein Merge nach main.

Drei freigegebene neue Angebote: Airparks Parkplatz München Valet (159 €), Parkprofi Parkhaus Valet München (174 €), valet24muc Parkplatz Valet (174 €), jeweils ab für acht Tage. Bestehende sechs Preise wurden mit der öffentlich sichtbaren Preisseite abgeglichen. Der gemeinsame freigegebene TradeTracker-Link leitet zur Münchner Valet-Auswahl; HTTP-Prüfung siehe AFFILIATE-QUELLEN-PRUEFUNG.json. Erreichbarkeit und Begriffstreffer sind kein unabhängiger Nachweis jeder einzelnen Leistung. Die Angebotsangaben stammen aus dem Nutzerbriefing. Preisbeispiel und Herkunft bleiben intern im Update-Manifest dokumentiert.

VPM Anbieter 0.3.4 und VPM Inhaltsentwürfe 0.4.3 unterstützen die Erweiterung. Neues Werkzeug „VPM Partner-Vorschau“ erzeugt ausdrücklich getrennte deutsche Entwürfe aus vorhandenen Seiten und Anbietern. Aktuelle vorhandene Tabellenpreise haben Vorrang. Aktivierung/Update verändert und veröffentlicht keine bestehenden Seiten. Kein Booking-Kern-Update für diese Aufgabe erforderlich. Kein Import auf Frankfurt/Dachmarke.

`pakete/vpm-affiliate-webvorschau.zip`: entpacken, index.html öffnen.
`pakete/vpm-youtube-upload.zip`: fertige MP4, Thumbnail, SRT, Titel/Beschreibung.
`pakete/vpm-erklaervideo-projekt.zip`: bearbeitbare Quellen, Audio und Nutzungsnotizen.
`pakete/vpm-affiliate-entwurfspaket.zip`: die beiden WordPress-Plugins und diese Anleitung; nur zur späteren gesonderten Installation. Kein Upload erforderlich, um die Offline-Vorschau anzusehen.

Video überarbeitet: 53,52 s / Full HD, zusammenhängende deutsche männliche Qwen3-TTS-Stimme, gezielte Aussprachehilfen für englische Begriffe, hörbarere Musik (ca. −26 LUFS) und fünf Sekunden kürzerer Schluss. Sechs animierte 2D-Szenen; freigegebener Sprechertext und Untertitel unverändert. Kein YouTube-Zugriff verfügbar: vollständiges Uploadpaket statt vorgetäuschter Veröffentlichung. Auf WordPress kein leerer Video-Player; die tatsächliche URL wird nach Upload benötigt.

Prüfungen: 26 Backend-Checks der Entwurfsfunktion, bestehende Standort-/Profiltests einschließlich Inhaltserhalt, Desktop/Mobil-Vorschau und Offline-Video. Grenzen: lokale WordPress-Prüfung und öffentliche Quellprüfung; keine Hostinger-Installation.

Audiorevision: scripts/build-video-speech-natural.py, align-video-narration.py und test-video-media.py. Neue Szenen-/Untertitelzeiten anhand der Sprachtranskription; keine nachträglichen Schnitte mitten im Sprechertext. Keine Änderungen an WordPress-Code oder bestehenden Affiliate-Angaben durch diese Audiorevision.
