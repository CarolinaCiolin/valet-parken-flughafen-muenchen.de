# Audiorevision – 21.11.2026

Nutzerfeedback: Hintergrundmusik kaum hörbar; Piper-Stimme zu roboterhaft, englische Begriffe falsch ausgesprochen und Sätze zu abgehackt; Schluss um ungefähr fünf Sekunden kürzen.

- Piper ersetzt durch eine zusammenhängend generierte deutsche Männerstimme mit Qwen3-TTS VoiceDesign (Apache-2.0, keine Stimme einer realen Person geklont).
- Aussprachehilfen ausschließlich für die Audioerzeugung; freigegebener Sprechertext und Untertitel unverändert. Automatische Transkription erkennt unter anderem Shuttletransfer, Parkservice und Check-in. Sie ersetzt keine subjektive Hörprobe.
- Musik von ungefähr −47 LUFS in der alten Mischung auf ungefähr −26 LUFS angehoben. Stereo bleibt erhalten; Limiter mit kompensierter Verzögerung. Mischung etwa −16,6 LUFS, ohne Übersteuerung.
- Video von 58,52 auf 53,52 Sekunden gekürzt. Sprecheraufnahme vollständig enthalten. Schluss-CTA länger als sechs Sekunden sichtbar.
- Untertitel und Szenen auf die neue Aufnahme abgestimmt; MP4, SRT/VTT, GIF-Ausschnitt, Projekt- und Uploadpakete aktualisiert. Separate MP3-Hörprobe der Stimme.

Prüfung: `scripts/test-video-media.py`, Ergebnis `ERKLAERVIDEO-PRUEFUNG.json`. Musik im tatsächlichen MP4 durch Abgleich mit dem Musiksignal geprüft, nicht nur anhand einer vorhandenen Quelldatei. Sprachprüfung und Timings: `media/valet-erklaervideo/projekt/sprechpruefung.json` und `sprachzeiten.json`.

Nur Vorschauzweig. Keine Änderungen an Live-Website, WordPress-Konfiguration oder YouTube-Veröffentlichung.
