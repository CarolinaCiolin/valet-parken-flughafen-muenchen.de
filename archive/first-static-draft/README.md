# Valet Parken Flughafen München

Erster responsiver Website-Entwurf mit HTML, CSS und JavaScript. Keine Paketinstallation erforderlich. Node.js wird nur für die JavaScript-Syntaxprüfung benötigt.

## Lokal starten

Im Repository-Verzeichnis:

```sh
python3 -m http.server 8000 --bind 0.0.0.0
```

## Prüfen

```sh
node --check assets/app.js
curl --fail http://127.0.0.1:8000/
```

## Vor Veröffentlichung

Betreibername, Kontakt, Preise, tatsächliche Leistungen, Parkstandort und Versicherungsbedingungen bestätigen. Impressum und Datenschutzerklärung ergänzen. Der sichtbare Entwurfshinweis kennzeichnet noch offene Angaben.

Das Formular erstellt ausschließlich lokal einen Anfragetext. Es übermittelt keine Daten und ermöglicht noch keine Buchung. Für den Versand muss ein geeigneter Kontaktweg oder ein Backend ergänzt werden.
