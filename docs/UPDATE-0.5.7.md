# Update 0.5.7 – fehlende Complianz-Banner-Hooks abgleichen

## Konkreter öffentlicher Befund

Der Betreiberbericht vom 17.10.2026 zeigt im selben öffentlichen Aufruf: Assistent abgeschlossen, Banner erforderlich, keine Vorschau, DNT/GPC aus; gleichzeitig Banner-/Asset-Hooks nicht registriert, aber inline_cookie_script_no_warning registriert. Banner-HTML und Banner-JavaScript fehlen. Dies ist ein Widerspruch zwischen der aktuellen Plugin-Entscheidung und den früh registrierten Ausgabe-Hooks. Ein früh abweichender Entscheidungsstand kann diesen Zustand erzeugen; welche konkrete Live-Integration ihn auslöst, ist noch nicht nachgewiesen.

## Gezielter Kompatibilitätsfix

Das Theme gleicht die Hooks nach dem Aufbau der Seitenabfrage (wp) und nochmals vor dem Laden der Frontend-Skripte ab. Es greift ausschließlich bei folgendem Zustand ein:

- Complianz aktiv und benötigte Plugin-Methoden verfügbar.
- Assistent abgeschlossen, keine Administration, WordPress-Vorschau oder Seiteneditor-Vorschau.
- Complianz selbst meldet jetzt Banner erforderlich.
- Banner nicht deaktiviert, Aktivierungsoption nicht „no“.
- Beide Banner-/Asset-Hooks fehlen und der konkrete Ohne-Banner-Hook ist vorhanden.

Dann wird der Ohne-Banner-Hook entfernt und die reguläre Banner-Ausgabe über die eigenen öffentlichen Complianz-Methoden registriert: Assets, CSS, HTML, Skriptattribute und die zugehörige Plugin-Skriptsteuerung. Kein zweites Banner, keine Kopie der Plugin-Ausgabe, keine direkten Cookie- oder Zustimmungsänderungen. Keine Einstellungen oder Affiliate-Links werden geändert; Analytics und CleanLinking werden nicht aktiviert. DNT/GPC und gespeicherte Auswahl verarbeitet weiterhin Complianz selbst.

Die Diagnose zeigt zusätzlich „VPM-Spätinitialisierung angewendet“. Bei normal initialisiertem Plugin greift der Fix nicht ein. Fehlt der konkrete Ohne-Banner-Hook, werden absichtlich entfernte Hooks nicht pauschal wieder eingesetzt.

## Installation und Live-Prüfung

1. Nur das aktuelle vpm-hybrid.zip als Theme-Ersatz installieren. Kein Inhaltsimport oder Plugin-Update nötig.
2. Website ohne Diagnoseparameter in einem frischen Browserfenster öffnen und Banner prüfen.
3. Falls weiter kein Banner: /?vpm_cookie_diagnostics=1 öffnen und den neuen Bericht kopieren. Erwartung für den bisher beobachteten Fehler: angewendet=true, Banner-HTML-/Asset-Hooks=true, Ohne-Banner-Hook=false, Complianz-Konfiguration und Banner-Funktion vorhanden.

## Tests und Grenzen

Der exakte Hook-Widerspruch wurde mit einer temporären, ausschließlich lokalen MU-Testfixture nachgestellt: bei früher Initialisierung meldet der Plugin-Filter noch keinen Bedarf, nach init jedoch den tatsächlichen Bedarf. Ohne Fix fehlt die Ausgabe; mit Fix wird ein einzelner Banner und ein einzelnes Skript geladen. Zusätzlich normale Initialisierung, kein Bannerbedarf, unvollständiger Assistent, explizit deaktivierter Banner, DNT und Erhalt einer Ablehnung nach Neuladen getestet. Fixture und Einstellungen werden anschließend zurückgesetzt. Details: COMPLIANZ-LIFECYCLE-PRUEFUNG.json.

Lokale Plugin-Quellen: Complianz 7.5.5. Betreiberbericht: 7.5.6. Der Fix verwendet die im vorhandenen Plugin verfügbaren Methoden; keine Herstellerdatei wird geändert. Dies ist ein getesteter Kompatibilitätsfix für den beobachteten Zustand, keine bereits bestätigte Behebung auf Hostinger und kein Nachweis der auslösenden Drittintegration.
