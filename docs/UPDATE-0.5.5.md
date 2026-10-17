# Update 0.5.5 – tatsächliche Banner-Ausgabe im Browser untersuchen

Die bisherige PHP-Diagnose bestätigt Einstellungen, nicht die Ausgabe oder Ausführung im Browser. Nach weiterhin fehlendem Banner gibt es daher keine gesicherte Live-Ursache. Keine weitere Konfigurationsänderung auf Verdacht.

## Neu

Unter Werkzeuge → VPM Cookie-Diagnose steht „Startseite im Browser prüfen“. Nach Anklicken lädt eine eingebettete Ansicht die eigene Startseite mit Testparameter. Nach dem Laden zeigt der Bericht:

- Banner-Container und tatsächliche Banner-Elemente samt CSS-Klassen, display, visibility, opacity, Abmessungen und z-index.
- Complianz-Konfiguration, Banner-Funktion, Einwilligungstyp, Region und aktiven Banner-ID-Wert.
- Complianz-Skripte im HTML, Skripttypen und sichtbare LiteSpeed-Verzögerungsattribute sowie vom Browser aufgezeichnete Complianz-Ressourcen.
- DNT/GPC-Signale und ausschließlich die Namen vorhandener Complianz-Cookies, keine Cookie-Werte oder beliebige Speicherinhalte.
- Hinweis, falls auf der Startseite bereits ein Richtlinien-Verwaltungsblock steht, der die Banneranzeige beeinflussen kann.

Der Bericht wird nur lokal im Admin-Browser angezeigt; keine Übermittlung an einen Diagnosedienst. Die Diagnose selbst setzt keine Einwilligung, löscht keine Cookies und erzwingt keine Banneranzeige. Der normale eingebettete Seitenaufruf kann sich hinsichtlich Cookies wie jeder Website-Aufruf verhalten. Die Prüfung ist für Administratoren zugänglich; das Prüfskript wird auf öffentlichen Seiten nicht geladen.

## Vorgehen

1. Theme-ZIP als Ersatz hochladen und Cache leeren.
2. Werkzeuge → VPM Cookie-Diagnose → Startseite im Browser prüfen.
3. Bericht kopieren und zur Auswertung bereitstellen. Bei verzögertem Skriptstart kann nach Interaktion mit der eingebetteten Seite erneut ausgelesen werden.

Der Test läuft in der angemeldeten Sitzung mit Testparameter. Er kann den anonymen Seitencache umgehen und ist kein Nachweis für dessen Inhalt. Falls Startseite und Administration unterschiedliche Domains verwenden oder eine Sicherheitsrichtlinie das Einbetten verhindert, wird das als Grenze gemeldet. Vergangene JavaScript-Fehler lassen sich mit diesem Test nicht vollständig erfassen. Der direkte Live-Zugriff aus der Cloud ist weiterhin durch einen CONNECT-Verbindungsfilter blockiert.

Tests: COOKIE-BROWSER-DIAGNOSE-PRUEFUNG.json. Kein Inhaltsimport, keine Plugin-Updates oder Banner-/Tracking-Aktivierung durch dieses Update. Alle bisherigen Theme-Änderungen bleiben enthalten.
