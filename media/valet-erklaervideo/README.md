# Valet-Parken München – 2D-Erklärvideo

Fertiger Entwurf: `valet-parken-muenchen-erklaervideo.mp4` (53,52 Sekunden, 1920 × 1080, H.264/AAC, 25 Bilder/Sekunde). Deutsche männliche synthetische Sprecherstimme, mit Qwen3-TTS Base an die bereits freigegebene synthetische Referenzstimme angeglichen. Der freigegebene Sprechertext bleibt unverändert. Originale Hintergrundmusik, in der überarbeiteten Fassung auf etwa −26 LUFS angehoben (Sprache etwa −16 LUFS). Das Ende wurde um fünf Sekunden gekürzt.

`sprecher-anhoeren.mp3` enthält die neue Sprecherfassung ohne Hintergrundmusik zum direkten Vergleich.

## YouTube-Upload

1. MP4 hochladen.
2. Titel und Beschreibung aus den beiden youtube-*.txt-Dateien übernehmen.
3. `thumbnail-youtube.png` als Thumbnail und `untertitel-de.srt` als deutsche Untertitel hochladen.
4. Die tatsächliche YouTube-URL anschließend für die Website verwenden. Es wurde kein Video auf YouTube veröffentlicht und kein leerer YouTube-Player in WordPress eingebaut.

## Bearbeitbare Quellen

`projekt/render.py`, `projekt/motion.py`, `projekt/design.json`, die Einzelillustrationen, Schriftdateien und Sprachzeiten sind die bearbeitbaren Python-/JSON-Projektdateien. Kein Premiere-/CapCut-Projekt. Erneutes Rendern mit Python 3, Pillow, NumPy und installiertem FFmpeg:

    python3 projekt/render.py

Sprecher-Audio und Untertitel liegen daneben. Gezielte Aussprachekorrekturen: im Repository `scripts/patch-video-speech-ends.py`; vollständige Sprach-Neuerzeugung nur bei Bedarf mit `scripts/build-video-speech-natural.py` (Qwen3-TTS, CPU-Torch und faster-whisper; Modelldownload erforderlich), anschließend `scripts/align-video-narration.py`. Der alte Piper-Generator bleibt nur als historische manuelle Variante erhalten. Nur Bilder exportieren: `python3 projekt/render.py --stills`.

## Herkunft und Nutzung

Animation und Musik wurden für dieses Projekt erzeugt, ohne fremdes Film- oder Musikmaterial. Die Terminalzeichnung ist schematisch und zeigt keinen bestätigten Übergabepunkt. Terminal, Fahrzeug und Figuren sind klar gezeichnete, KI-generierte 2D-Illustrationen. Das Thumbnail wurde an diese Bildsprache angepasst. Qwen-Sprachmodell: siehe `projekt/STIMMENMODELL-LIZENZ.txt` (Apache-2.0); Schriftlizenzen: `projekt/fonts/LIZENZ.txt` sowie `projekt/fonts/MANROPE-LIZENZ.txt`. Keine erfundenen Anbieterbewertungen oder Sicherheitsgarantien im Video. Zur Beibehaltung des Stimmklangs wird ausschließlich ein Ausschnitt unserer zuvor generierten, vom Nutzer freigegebenen synthetischen Stimme als Referenz verwendet (`projekt/narrator-reference.wav`). Es wurde keine reale Person geklont. Valet wird auf ausdrücklichen Nutzerwunsch wie das englische Wallet gesprochen; sichtbare Schreibweisen bleiben Valet. Die Aussprachehilfen in den Sprachquellen ändern weder den freigegebenen Sprechertext noch die Untertitel. Automatische Transkription prüft Inhalt und Zeitpunkte; die subjektive Natürlichkeit bleibt durch Anhören zu beurteilen.

## Illustrations- und Motionrevision

Neue detailreiche 2D-Bildsprache in Navy/Champagnergold/Creme mit räumlicher Kulisse, Fahrzeuganimation einschließlich Rädern, Kamerafahrten, Prüfscan/Checkliste, Flugroute und weich überblendeten Markenübergängen. Die Figuren sind als gezeichnete Ebenen eingebunden, nicht als vollständig skelettanimierte Charaktere. Das kleine transparente Wasserzeichen oben wurde auf Nutzerfeedback entfernt. Die bestehende Website-Wortmarke bleibt ausschließlich groß und deckend im Schlussbild. Quellen/Einzelillustrationen: `projekt/illustrationen/QUELLEN.json`. Neue Schrift Manrope lokal gebündelt (OFL), keine externen Fonts beim Abspielen.

Das separate Projekt-ZIP enthält Quellen, Einzelillustrationen, Fonts und Audio. Fertiger MP4-Film, Thumbnail und gerenderte Vorschauen liegen im gesonderten Upload-/Vorschaupaket und werden im Quellprojekt nicht nochmals mitgeliefert.

## Gezielte letzte Sprachkorrektur

Nur Einstieg und Abschluss wurden überarbeitet, die URL zusätzlich langsamer und mit kurzen Pausen gesprochen. Kurze Hörprobe: sprecher-korrektur-hoerprobe.mp3. Nach dem erneuten Aussprachefeedback wurde der erste vollständige Satz mit zusammenhängendem „Parkservice“ erneuert. Der weitere Mittelteil von 8,84 bis 41,40 Sekunden bleibt samplegenau erhalten. Die Endung wird mit zwei einzeln erzeugten Buchstabennamen „D“ und „E“ gesprochen. Die Animation wird lediglich ohne das kleine obere Wasserzeichen neu gerendert. Nachweise: projekt/endpunkt-korrektur.json und docs/ERKLAERVIDEO-ENDPUNKTE-MUX.json im Repository. Keine YouTube-Veröffentlichung erfolgt.

Für den fertigen Schluss wird die bisherige Aufnahme der Adresse beibehalten und ausschließlich die letzte Endung durch die einzeln erzeugten Buchstaben ersetzt (direkt in `scripts/patch-video-speech-ends.py`). Die zugehörige historische Quellaufnahme heißt `projekt/sprecher-vor-aussprachekorrektur.wav`; sie ist nicht die aktuelle Sprecherfassung. Gesicherte neue Rohaufnahmen können ohne erneute Stimmerzeugung mit `scripts/patch-video-speech-ends.py --assemble-only` verarbeitet werden.
