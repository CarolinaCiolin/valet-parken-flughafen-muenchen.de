# Technisches Inventar – Grundgerüst

Dies ist eine technische Dokumentation für die spätere Datenschutzerklärung, kein Rechtstext.

| Bestandteil | Stand |
| --- | --- |
| Eigenes Theme | VPM Hybrid 0.1.4 |
| Eigenes Plugin | VPM Anbieter 0.1.3 |
| Lokale Testinstallation | WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4.13 |
| Kompatibilitätstest | Offizielles Elementor 3.33.0, nur kostenlose Funktionen |
| Schriften | Systemschriften; keine Font-Dateien oder externen Fontanbieter |
| Bilder | Fotorealistische KI-Illustration eines generischen Valet-Ablaufs; lokale WebP-Datei, sichtbar gekennzeichnet; keine externen Bildabrufe |
| Affiliate-Links | Direkte Navigation zu Parkos/ParkVia nach Klick; `rel="sponsored nofollow"`; kein vorzeitiges Laden der Partnerseiten |
| Eigene Cookies | Theme und Anbieter-Plugin setzen keine Cookies |
| Eigene externe Requests | Theme und Anbieter-Plugin führen keine Partner-, API- oder Trackingabfragen aus |
| Anbieter-Daten | WordPress-Datenbank; keine Übertragung an Parkplattformen beim Anzeigen |
| Öffentliche Provider-REST-API | Für nicht angemeldete Nutzer und Nutzer ohne Redaktionsberechtigung gesperrt |
| Formulare / Nutzerkonten | Keine neuen öffentlichen Formulare oder Konten |
| Analytics / Pixel / Consent | Nicht eingebunden |

WordPress und Elementor haben eigene Administratorfunktionen, Login-Cookies, Updateprüfungen und gegebenenfalls externe Requests. Elementor-Google-Fonts werden vom Theme im Frontend standardmäßig deaktiviert. Individuell hinzugefügte Widgets, eingebettete Medien, externe Stylesheets oder weitere Plugins müssen gesondert geprüft werden.

Der Hostinger-Server kann IP-Adressen, Zugriffsprotokolle, Backups und andere Hostingdaten verarbeiten. Die tatsächlichen Hostinger-Einstellungen sowie vorhandene Plugins wurden mangels Cloud-SSH-Verbindung noch nicht geprüft. Dieses Inventar umfasst deshalb nur das lokal entwickelte Grundgerüst und ist vor dem Launch um die reale Installation zu ergänzen.

Das optionale Importplugin VPM Inhaltsentwürfe 0.1.3 legt lokal gespeicherte Inhaltsentwürfe und die Mediathek-Bilder an. Es sendet keine Daten an externe Dienste. Der Footer-Hinweis wird als Theme-Einstellung gespeichert. Vor dem späteren Einsatz weiterer Plugins deren Datenverarbeitung gesondert erfassen.

Die Anbieterübersicht verwendet ein gemeinsames KI-Symbolbild mit ausdrücklicher Kennzeichnung als keine Anbieteraufnahme. Es wird lokal gespeichert, responsiv ausgeliefert und verzögert geladen. Bilder können im WordPress-Backend ausgetauscht werden; das Ergänzen fehlender Bilder erfordert eine ausdrücklich ausgelöste Administratoraktion.

Die lokale Plugin-Prüfung umfasst Rank Math, LiteSpeed Cache und Complianz als Hersteller-Quellstände. Das lokale Consent-Testfixture überträgt keine Nutzerdaten und bleibt außerhalb der installierbaren Pakete. Auf Hostinger müssen die tatsächlichen Konfigurationen, Accounts, eingebundenen Scripts und gegebenenfalls Online-Dienste dieser Plugins neu erfasst werden. HFCM war lokal nicht installiert.
