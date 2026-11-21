# Valet-Parken München – 2D-Erklärvideo

Fertiger Entwurf: `valet-parken-muenchen-erklaervideo.mp4` (58,52 Sekunden, 1920 × 1080, H.264/AAC, 25 Bilder/Sekunde). Deutsche männliche synthetische Sprecherstimme, Modell de_DE-thorsten-high. Der freigegebene Sprechertext bleibt unverändert. Ruhige originale Hintergrundmusik.

## YouTube-Upload

1. MP4 hochladen.
2. Titel und Beschreibung aus den beiden youtube-*.txt-Dateien übernehmen.
3. `thumbnail-youtube.png` als Thumbnail und `untertitel-de.srt` als deutsche Untertitel hochladen.
4. Die tatsächliche YouTube-URL anschließend für die Website verwenden. Es wurde kein Video auf YouTube veröffentlicht und kein leerer YouTube-Player in WordPress eingebaut.

## Bearbeitbare Quellen

`projekt/render.py`, `projekt/design.json`, Schriftdateien und Sprachzeiten sind die bearbeitbaren Python-/JSON-Projektdateien. Kein Premiere-/CapCut-Projekt. Erneutes Rendern mit Python 3, Pillow, NumPy und installiertem FFmpeg:

    python3 projekt/render.py

Sprecher-Audio und Untertitel liegen daneben. Sprach-Neuerzeugung: im Repository `scripts/build-video-speech.py` (Piper; Modelldownload erforderlich). Nur Bilder exportieren: `python3 projekt/render.py --stills`.

## Herkunft und Nutzung

Animation und Musik wurden für dieses Projekt erzeugt, ohne fremdes Film- oder Musikmaterial. Die Terminalzeichnung ist schematisch und zeigt keinen bestätigten Übergabepunkt. Thumbnail KI-generiert als abstrakte 2D-Illustration. Thorsten-Sprachmodell: siehe `projekt/STIMMENMODELL-LIZENZ.txt` (CC0-Datensatz); Schriftlizenzen: `projekt/fonts/LIZENZ.txt`. Keine erfundenen Anbieterbewertungen oder Sicherheitsgarantien im Video. Eine menschenähnliche synthetische Stimme ist kein echter eingesprochener Sprecher.
