# Klickbare Vorschau über GitHub Pages

Die vier statischen Seiten und ihre Bilder liegen in `preview/`. Es werden keine WordPress-Zugangsdaten oder Backend-Dateien veröffentlicht. Partnerlinks führen zu den Buchungspartnern. Die Vorschau enthält keine fertig konfigurierte Cookie-Verwaltung und keine Rechtstexte.

Nach dem Hochladen der Projektdateien:

1. Im Repository **Settings → Pages → Source → GitHub Actions** auswählen.
2. Unter **Actions → Website-Vorschau → Run workflow** den Zweig mit diesen Dateien auswählen und starten.
3. Nach erfolgreichem Durchlauf den Link in der Deployment-Ausgabe öffnen.

Das Hochladen allein veröffentlicht die Vorschau nicht: Der Workflow startet ausschließlich manuell. Bei privaten Repositories kann GitHub Pages einen kostenpflichtigen Tarif erfordern. Eine veröffentlichte Pages-Seite kann öffentlich erreichbar sein; die Repository-Sichtbarkeit schützt sie nicht automatisch.

Aktualisierung der statischen Vorschau bei laufender lokaler WordPress-Installation:

```bash
python scripts/build-offline-preview.py
cp -R dist/vpm-vorschau/. preview/
```
