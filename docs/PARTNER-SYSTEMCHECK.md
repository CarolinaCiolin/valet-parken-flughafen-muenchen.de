# Technischer Systemcheck pro Parkplatzpartner

Vor einer Anbindung ausfüllen; keine Passwörter/API-Schlüssel im öffentlichen Repository speichern.

- Flughafen, Anbieter und tatsächlicher Vertragspartner für die Parkleistung.
- Name/Version des aktuell führenden Buchungs-/Verwaltungssystems und technischer Ansprechpartner.
- Eigene Direktbuchungen und weitere Vertriebskanäle: Wo liegt die einzige verbindliche Kapazitätsquelle?
- Dokumentierte API? Zugriff für unseren Vertriebskanal erlaubt? Gebühren/Zertifizierung/Vertragsfreigabe?
- Angebote/Preise/Zeitzone/Steuern/Zuschläge/Stornobedingungen abrufbar?
- Verfügbarkeit live? Temporärer Hold mit Ablauf? Alternativ sichere atomare Buchung?
- Neue Buchung anlegen, Status lesen, Änderung/Storno, Teilstorno? Idempotenz und Herstellerreferenz?
- Signierte Webhooks für Bestätigung/Storno/Preis-/Kapazitätsänderung? Sandbox/Testzugang?
- Wann und unter welchen Bedingungen bestätigt/storniert der Partner? Reaktionsfrist im Anfragebetrieb?
- Vereinbarte Provisionsbasis und Partnerbetrag; von uns finanzierte Rabatte zulässig? Umgang mit Zusatzleistungen, Steuern, Gebühren und Teilstornos?
- Partner-Onboarding bei einem geeigneten Marktplatz-Zahlungsdienst möglich? Auszahlungszeitpunkt, Reserve, Rückabwicklung und Verrechnung vereinbart?
- Würde der Anbieter später unser Betriebssystem als Haupttool nutzen? Welche Funktionen und vorhandenen Vertriebskanäle müssen es ersetzen/anbinden?

Ergebnis je Partner: API-fähig / Anfragebetrieb / potenzieller Nutzer unseres eigenen Systems. Nicht anhand eines bloßen Herstellernamens als sofortbuchbar freigeben.
