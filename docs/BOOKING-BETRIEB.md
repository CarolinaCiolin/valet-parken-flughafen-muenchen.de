# Betriebsvorbereitung des Buchungskerns

Entwicklungsstand, kein bereits eingerichteter Hostinger-Betrieb. Vor Produktion sichere aktuelle WordPress-/Pluginstände prüfen; ältere lokale Testfixtures nicht als Empfehlung installieren.

## Vor Freigabe

- HTTPS für Frontend, Anmeldung und REST, beide WordPress-Adressen passend. Dieselbe Herkunft für Portal und Anmeldung verwenden, solange eine getrennte Subdomain nicht ausdrücklich getestet ist.
- Eigene Partnerrollen verwenden, keine WordPress-Administratoren für externe Partner. Benutzerzuordnungen und Entzug mit mindestens zwei verschiedenen Partnern testen. MFA/Anmeldeschutz vor produktiver Freigabe einrichten; durch dieses Plugin noch nicht implementiert.
- Betriebsart je Partner bewusst wählen, keine doppelte manuelle Kapazität neben einem fremden führenden Verkaufssystem. Poolzuordnung und Preise prüfen.
- Keine Live-Zahlungen freischalten: derzeit akzeptiert der Kern nur Testschlüssel. Demo-Konstante ausschließlich auf getrennter Testinstallation, niemals im produktiven Geschäftsbetrieb.
- PHP 8.1+, OpenSSL, 64 Bit, InnoDB; getestete Umgebung PHP 8.3. JSON-Abfragen für verwaltete Buchungen benötigen eine entsprechende MySQL-/MariaDB-Version. Hostinger-Kompatibilität erst mit tatsächlicher Installation bestätigen.

## Worker und Servercron

Ereignis: `vpm_booking_worker`, vorgesehen alle fünf Minuten. Partneraufgaben, Erstattungen, Nachrichten und Treueaktion laufen in begrenzten Durchläufen. Bei vielen Kontakten/Jobs sind zusätzliche Worker-Läufe und ein Lasttest nötig. Eine geplante WordPress-Aufgabe ist kein Nachweis, dass sie regelmäßig ausgeführt wird.

Auf dem tatsächlichen Hosting zunächst Verfügbarkeit von WP-CLI und den richtigen Installationspfad prüfen. Ein geplanter Serveraufruf kann im WordPress-Verzeichnis `wp cron event run vpm_booking_worker` ausführen. Kein pauschaler fertiger Hostinger-Pfad aus diesem Repository ableitbar. Vor Abschalten des besucherabhängigen WP-Cron erst echte Serverausführung und Zeitstempel prüfen. Keine Administrator-Anmeldedaten in öffentlich erreichbare Cron-URLs schreiben.

Im Plattformportal letzter Worker-Zeitpunkt sowie Aufgaben mit Klärungsbedarf prüfen. `attention` ist kein Erfolg: Bei Zahlung ohne Partnerbestätigung zuerst Status und Idempotenz im Partnersystem klären. Kein blindes wiederholtes Buchen, Stornieren oder Erstatten. Erneute Prüfung nur nach Klärung anfordern. Alarmereignisse benötigen einen eingerichteten Automatisierungsempfänger; die Portalansicht allein sendet keinen Alarm.

## Sicherung und Wiederherstellung

Produktionssicherung muss Datenbank **und** relevante WordPress-Dateien/Uploadbestand enthalten. `wp-config.php`, WordPress-Salts und serverseitige Stripe-/Webhook-/Automatisierungsgeheimnisse geschützt separat sichern. Ohne dieselben Verschlüsselungsgrundlagen können API-Zugänge und Outbox-Payloads nicht wiederhergestellt werden. Keine solchen Sicherungen in das öffentliche Repository oder normale GitHub-Artefakte laden.

Aufbewahrung, Verschlüsselung des Backups, Zugriffsschutz und externer Sicherungsort sind vor Produktion festzulegen. Hostinger-Sicherung und Wiederherstellung auf einer separaten Installation tatsächlich erproben; nicht erstmalig bei einem Ausfall. SQL allein ist noch kein kompletter Website-Restore.

Der lokale/CI-Test `scripts/check-booking-ci.sh` verwendet ausschließlich eine frisch erzeugte Wegwerfdatenbank, simulierte Kundendaten und eine private temporäre SQL-Datei. Er prüft Snapshot und Journal nach Wiederherstellung und entfernt die Testcontainer/temporären Geheimnisse. Das beweist keinen Hostinger-Restore, keine externe Backupverschlüsselung und keinen Wiederanlauf mit rotierten Schlüsseln.

## Regelmäßige Kontrollen

Fehlgeschlagene Partneraufrufe, verspätete Zahlungen, Erstattungen, ausbleibende E-Mails, offene Stornowünsche, verwaiste vorläufige Belegungen und fehlende Newsletter-Zustellung prüfen. Plattformjournal regelmäßig mit dem Zahlungsdienst abstimmen; dessen tatsächliche Auszahlungen, Gebühren und Streitfälle sind noch nicht vollständig abgeglichen. Keine Umsätze aus Demo-Simulationen übernehmen.
