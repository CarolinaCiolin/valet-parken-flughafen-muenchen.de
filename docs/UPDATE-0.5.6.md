# Update 0.5.6 – direkter Cookie-Test und öffentlicher PHP-Aufruf

## Befund aus dem bereitgestellten Quelltext

Die vom Betreiber hochgeladene MHTML-Datei enthält den gespeicherten Quelltext der öffentlichen deutschen Startseite mit Theme 0.5.5. Der Quelltext wurde lokal aus den line-content-Zellen extrahiert; die Originaldatei bleibt außerhalb dieses Repositorys.

- Allgemeine Complianz-Cookieblocker-CSS und data-cmplz am body sind vorhanden.
- Kein cmplz-cookiebanner-container, kein Banner-HTML, keine Banner-Konfiguration und kein Complianz-Banner-JavaScript im ausgelieferten HTML.
- Ein leeres script data-category="functional" steht im Footer. Das entspricht der Ausgabe des in den untersuchten lokalen Complianz-Quellen vorhandenen inline_cookie_script_no_warning-Zweigs.
- Footer-Hooks liefern andere Skripte aus; der Footer fehlt nicht generell.
- Der Quelltext endet mit „Page cached by LiteSpeed Cache 7.9.1 on 2026-10-17 21:35:21“.

Das spricht für fehlende serverseitige Banner-Ausgabe oder eine entsprechend gecachte Fassung, nicht lediglich eine CSS-Verdeckung oder die DNT-Entscheidung nach geladenem Banner-JavaScript. Ob der Cache einen älteren Stand bewahrt oder der öffentliche PHP-Aufruf anders entscheidet als die Administration, ist damit noch nicht entschieden. Keine konkrete Live-Ursache oder Behebung behauptet.

## Direkter Test ohne gemeinsame Administrationsdomain

Die eingebettete Prüfung aus 0.5.5 scheitert bei unterschiedlichen Domains von Verwaltung und Website. Der neue Test braucht weder iframe noch Anmeldung auf der Frontend-Domain:

1. vpm-hybrid.zip als Theme-Ersatz hochladen und LiteSpeed-Cache leeren.
2. https://valet-parken-flughafen-muenchen.de/?vpm_cookie_diagnostics=1 öffnen, alternativ „Direkt auf der Website prüfen“ in Werkzeuge → VPM Cookie-Diagnose.
3. Unten im Bereich Cookie-Browser-Diagnose „Bericht kopieren“ und zur Auswertung bereitstellen.

Die Ausgabe enthält zusätzlich den serverseitigen Status genau dieses öffentlichen Aufrufs: Theme-Version, Erstellungszeit UTC, Assistent abgeschlossen, Banner erforderlich, registrierte Banner-/Asset-/Ohne-Banner-Hooks sowie Vorschauflags. Diese ausgewählten technischen Statuswerte enthalten keine Zugangsdaten, Betreiberadresse oder vollständigen Plugin-Einstellungen. Sie stehen auch ohne funktionierendes Diagnose-JavaScript lesbar auf der Seite. Die Browserdaten werden wie bisher nur im eigenen Browser ausgegeben; Cookie-Namen statt Cookie-Werten. Keine Einwilligung wird zurückgesetzt, erteilt oder erzwungen. Die normale Seitenverarbeitung kann sich ansonsten wie bei jedem Websitebesuch verhalten.

Nur exakt vpm_cookie_diagnostics=1 bindet das Diagnosefeld und -Skript ein. Normale Besucher erhalten diese Ausgabe nicht. Testaufrufe setzen DONOTCACHEPAGE und senden no-cache sowie noindex-Header. Das Diagnoseskript erhält data-no-optimize=1; die eigentlichen Complianz-Skripte bleiben unverändert. Ein vorgelagerter Cache kann WordPress trotzdem zuvor bedienen. Fehlt selbst das Diagnosefeld, muss dies statt eines Browser-Berichts gemeldet werden. Der Testparameter kann normalen Besuchercache umgehen; er beweist deshalb nicht dessen unveränderten Inhalt.

## Prüfung und Umfang

Lokale WordPress-/Browser-Prüfung in COOKIE-BROWSER-DIAGNOSE-PRUEFUNG.json: fehlender vs. geladener vs. per CSS versteckter Banner, direkte Prüfung ohne Anmeldung, keine iframe-Abhängigkeit, passender serverseitiger Hook-Status, no-cache/noindex-Header, keine Änderung von Einwilligungscookies beim Auslesen, keine Cookie-Werte im Bericht. Tests mit lokalen Complianz-7.5.5-Quellen, nicht Live-7.5.6.

Dieses Update behebt den Domainfehler des Diagnosewerkzeugs. Die eigentliche Live-Ursache bleibt bis zur Auswertung offen. Kein Inhaltsimport und kein Plugin-Update nötig. Alle bisherigen Theme-Funktionen bleiben enthalten.
