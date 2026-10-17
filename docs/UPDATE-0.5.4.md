# Update 0.5.4 – Complianz-Footer und Cookie-Diagnose

## Ergebnis der lokalen Untersuchung

Das Theme ruft wp_head, wp_body_open und wp_footer auf. Es entfernt keine Complianz-Skripte, versteckt keine Complianz-Banner und verändert keine Complianz-Einwilligungsentscheidung. Mit den lokalen aktiven Plugins (Polylang, Complianz, Elementor, LiteSpeed Cache, Rank Math und den beiden VPM-Plugins) konnte der Banner angezeigt und bedient werden. Ein genereller Theme-Konflikt wurde dabei nicht reproduziert.

Complianz unterscheidet zwischen „Banner aktiviert“ und „Banner erforderlich“. In der untersuchten Version werden Banner-HTML und -Skripte nur geladen, wenn der Assistent mindestens einmal abgeschlossen und laut Dienste-/Cookie-Konfiguration ein Banner erforderlich ist. Ohne Statistik-/Marketingdienste kann der Banner daher trotz Aktivierung ausbleiben. Das ist eine mögliche Ursache, keine Aussage über die unbekannten Live-Einstellungen. Weitere mögliche Ursachen: separate Banner-Deaktivierung, vorhandene Auswahl, DNT/GPC, Seitenausschlüsse, Vorschau sowie die konkrete JS-Verzögerung/Optimierung auf Hostinger.

Die lokalen Quellen sind Complianz 7.5.5; laut früherem Screenshot ist auf Hostinger 7.5.6 installiert. Keine vollständige Prüfung der Live-Konfiguration oder von HFCM/Imagify zur Laufzeit. Im eigenen Code kein allgemeiner Skriptentzug für diese Plugins.

## Änderungen

- Der Footer berücksichtigt zusätzlich die tatsächlich von Complianz konfigurierte Cookie-Richtlinie (generierte oder eigene WordPress-Seite), sofern veröffentlicht und nicht passwortgeschützt. Dadurch ist ihre Sichtbarkeit nicht allein vom im Assistenten ausgewählten Sprachmenü abhängig. Bereits vorhandene Links im aktiven Footer werden nicht doppelt ausgegeben.
- Vorhandene englische Übersetzung bevorzugen. Falls sie fehlt, bleibt die deutsche veröffentlichte Richtlinie erreichbar und wird im englischen Footer ausdrücklich „Cookie policy (German)“ genannt. Keine erfundene Übersetzung oder Platzhalter-URL. Eine externe URL statt einer WordPress-Seite muss weiterhin über das Menü eingebunden werden.
- Neue Ansicht unter Werkzeuge → VPM Cookie-Diagnose, nur für Administratoren. Zeigt ausgewählte technische Statuswerte: Assistent, Bannerbedarf, Dienste, DNT-Einstellung, Cookie-Seitenadresse und Sprachmenü-IDs. Keine API-Schlüssel, keine vollständigen Options- oder Cookie-Daten. Nur lesend, kein Tracking aktiviert, kein Banner erzwungen.

## Installation und nächste Diagnose

1. vpm-hybrid.zip als Theme-Ersatz hochladen, LiteSpeed-Cache leeren. Kein Inhaltsimport oder Plugin-Update nötig.
2. Werkzeuge → VPM Cookie-Diagnose öffnen und die Tabelle kopieren oder als Screenshot schicken.
3. Bei „Assistent einmal abgeschlossen: nein“ den Complianz-Assistenten vollständig abschließen.
4. Bei „Banner benötigt: nein“ prüfen, ob die Angaben zu den tatsächlich eingesetzten Diensten stimmen. Keine ungenutzten Dienste eintragen, nur um ein Banner zu erhalten.
5. Wenn erforderlich, abgeschlossen und nicht deaktiviert: in einem frischen Browserprofil ohne Werbeblocker testen; DNT/GPC kann auch in Privatfenstern aktiv sein. Bei weiterem Fehler vorhandene LiteSpeed-Einstellungen notieren, JS-Verzögerung und JS-Zusammenfassung testweise ausschalten, Cache leeren, erneut testen. Nach der Diagnose die Einstellungen gezielt wiederherstellen.

Direktzugriff auf die Live-Seite war aus dieser Cloud durch einen CONNECT-403-Filter blockiert. Das Update ist deshalb keine Bestätigung der konkreten Live-Ursache. Testdetails: COMPLIANZ-PRUEFUNG.json.
