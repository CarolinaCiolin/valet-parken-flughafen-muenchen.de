# Illustrations-, Bewegungs- und Aussprache-Revision

## Nutzerauftrag

Den bereits verbesserten Stimmklang beibehalten, Valet bewusst wie das englische Wallet aussprechen, die einfache Animation deutlich hochwertiger gestalten und die bestehende Wortmarke als Wasserzeichen einbinden. Das ist eine Aussprachevorgabe; veröffentlichte Texte und URLs werden nicht in Wallet umgeschrieben.

## Ergebnis und Quellen

Fünf neue, klar illustrierte 2D-Assets: schematisches Terminal, markenfreies Fahrzeug, Schlüsselübergabe mit gezeichneten Erwachsenen, Mitarbeiter mit Tablet sowie Außen-/überdachte Parkflächen. Gleiche Farbwelt und Figuren in den Szenen. Keine Bilder realer Partnerflächen und kein bestätigter Flughafen-Treffpunkt behauptet. Kein neues Anbieter- oder Flughafenlogo erfunden.

Motion-Quelle `media/valet-erklaervideo/projekt/motion.py`: separate Fahrzeug-/Radbewegung, Parallax-/Kamerabewegung, Fokusmarken/Prüfscan/Checkliste, Flugroute, Marken-Wipes und CTA. Gezeichnete Figuren als Ebenen, keine vollständige Character-Rig-Animation. Das Wasserzeichen nutzt die vorhandene Website-Wortmarke VALET PARKEN / MÜNCHEN in hell/dunkel, auch bei Szenenwechseln sichtbar. Im Schlussbild zusätzlich groß.

Neue lokal eingebettete Schrift Manrope, OFL-Lizenz im Projekt. Bildherkunft/Hashes: `projekt/illustrationen/QUELLEN.json`. Stimme: Qwen3-TTS Base, ausschließlich eigener freigegebener synthetischer Referenzsprecher, keine reale Stimme geklont. Englische Aussprachevorgabe und deutlich artikulierte Webadresse nur im Audioinput. Sichtbarer Sprechertext, Untertiteltext und Ziel-URL unverändert. Hörbeurteilung bleibt subjektiv; ASR allein beweist keine ideale Aussprache.

Laufzeit weiter rund 53,5 Sekunden; angeglichene ruhige Sprechgeschwindigkeit, keine leere verlängerte Schlussstrecke. Hintergrundmusik bleibt bei ungefähr −26 LUFS, Mischung und Untertitel werden mitgeprüft. MP4, MP3-Hörprobe, SRT/VTT, Thumbnail, Offlinevorschau und Downloadpakete aktualisiert.

Prüfungen: `scripts/test-video-motion.py`, `scripts/test-video-media.py`, zugehörige JSON-Berichte in docs und Browser-Wiedergabe. Nur Vorschauzweig; keine Hostinger- oder YouTube-Veröffentlichung und keine WordPress-Codeänderung.
