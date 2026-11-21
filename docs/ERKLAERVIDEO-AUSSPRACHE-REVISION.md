# Gezielte Aussprache- und Logokorrektur

Nach erneutem Nutzerfeedback wird Parkservice ohne die getrennte Aussprachehilfe Park-Sörwiss im vollständigen ersten Satz neu erzeugt. Der bereits akzeptierte erste Titel bleibt erhalten. Der freigegebene Sprachmittelteil von 8,84 bis 41,40 Sekunden bleibt samplegenau erhalten.

Die Domainendung wird ausdrücklich als zwei Buchstabennamen D und E gesprochen. Diese stammen aus eindeutigen Alphabetbeispielen der gleichen synthetischen Referenzstimme. Die Wörter „wie Dora“, „wie Emil“ und der erklärende Kontext werden herausgeschnitten; zwischen D und E liegen 200 Millisekunden Pause. Die öffentliche Schreibweise der Adresse, Sprechertext und Untertitel bleiben unverändert.

Das kleine transparente Wasserzeichen oben entfällt vollständig. Das bestehende große, deckende Logo im Schlussbild bleibt. Die übrige Illustration, Bewegung, Schrift und Szeneinteilung bleiben erhalten. Kein neues Anbieterbild und kein Live- oder YouTube-Deployment.

Prüfungen: endpunkt-korrektur.json dokumentiert Quellsegmente, Buchstaben und PCM-Integrität. test-video-media.py prüft echte getrennte Audioclips einschließlich stiller Lücke, Mischung, Lautheit und unveränderte Untertitel. test-video-motion.py prüft das entfernte Wasserzeichen und erhaltene Animation. Automatische Transkription und technische Prüfungen ersetzen die subjektive Hörbeurteilung nicht.

Gezielter Schluss-Schnitt: Die bisherige Aufnahme der gesprochenen Webadresse und des anschließenden Satzes bleibt erhalten. Nur das alte einzelne Buchstabengeräusch im ruhigen Fenster 47,745–48,110 s wird durch die neuen D-/E-Clips ersetzt. Dadurch wird die bereits abgestimmte Aussprache und Geschwindigkeit der übrigen Adresse nicht nochmals geändert. Quelle und Zeitmarken sind im Projekt enthalten.

Verarbeitung: Die erste automatische Verarbeitung scheiterte an einem fehlenden Punkt in der Erkennung einer unnötig neu erzeugten Schlussaufnahme. Dieser Schritt entfällt jetzt vollständig; nur intro und letters werden benötigt. Die gesicherten Aufnahmen werden lokal ohne erneute Modellinitialisierung zusammengesetzt. Beide Buchstaben wurden zusätzlich im dekodierten tatsächlichen MP4-Ton gegen die Quellspur geprüft. Alle übrigen Teile der alten URL und des Schlusses sind PCM-identisch erhalten.
