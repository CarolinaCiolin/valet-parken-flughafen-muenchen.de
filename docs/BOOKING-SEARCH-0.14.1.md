# Buchungssuche 0.14.1 – Alternativen und konkrete Suchprüfung

Update nur des Buchungskern-Plugins auf der getrennten Testinstallation. Datenbankschema bleibt 13; keine Preise, Bestände, Freigaben oder Zahlungsoptionen werden automatisch geändert.

## Anlass und belegter Stand

Gemeldet wurde: München, Außenstellplatz, Valet, Abflug 12.12.2026 10:00, Rücklandung 20.12.2026 10:00, keine Anbietervorauswahl. Trotz hinterlegter 20 Plätze erscheinen keine Angebote, teilweise auch nach Auswahl einer Alternative.

Diese Reise bedeutet in Flughafenzeit **12.12.2026 07:00 bis 20.12.2026 10:30**, ohne zusätzliche Partnerpuffer. Das sind neun angefangene 24-Stunden-Zeiträume. Im lokalen Test liefert ein freigegebenes manuelles Außenangebot mit 20 Plätzen, Valet 24/7 und zulässiger Aufenthaltsdauer ein Angebot für exakt diese Daten.

Ein separater Fehler wurde lokal reproduziert: Alternativen wurden vor zusätzlichen Journey-Prüfungen erstellt. So konnte beispielsweise ein Anbieter ohne benötigte Stripe-Zuordnung als verfügbar erscheinen, obwohl die nächste Suche ihn ausschloss. Auch das Wegfiltern eines angefragten Garagenangebots ohne Höhenlimit konnte gültige Alternativen unterdrücken. Diese Inkonsistenzen sind behoben. Angebote und Alternativen durchlaufen jetzt dieselben Prüfungen, bevor sie nach Stellplatzart und Anbieter zugeordnet werden.

**Die tatsächliche Live-Ursache ist damit noch nicht bewiesen.** Der Betreiber hat bestätigt, dass die Gast-Testkasse ausgeschaltet ist; deren Zahlungszuordnung erklärt seinen Fall deshalb nicht. Der direkte Live-Abruf war aus der Arbeitsumgebung durch einen vorgeschalteten Netzwerkproxy blockiert. Es wurden keine Hostinger-Einstellungen geändert.

## Update und Prüfung des gemeldeten Falls

1. Nach Sicherung `pakete/vpm-booking-core.zip` als Ersatz des bestehenden Plugins hochladen, Version **0.14.1**.
2. Im [Partnerportal](https://valet-parkservice.com/?vpm_partner_login=1) den betroffenen Partner öffnen, dann **Preisverwaltung → Beispielreise durchrechnen**.
3. Das Außenstellplatzangebot wählen, **Valet**, Abflug **12.12.2026 10:00**, Rücklandung **20.12.2026 10:00** eingeben.
4. **„Suchprüfung für diesen Zeitraum“** anklicken. Grüne und rote Punkte zeigen die einzelnen Prüfungen. Den Text aus **„Prüfbericht zum Kopieren“** zur Diagnose weitergeben.

Der private Bericht prüft öffentliche Anfragefreigabe, Partnerfreigabe, Verbindung, Serviceaktivierung, Abgabe-/Rückgabezeiten, Aufenthaltsregeln, Verspätungskonflikt, gespeicherten Tarif, Verkaufsstatus, Grundpreis, Belegung im gesamten Zeitraum samt Puffern, gegebenenfalls Höhenlimit und die für den Suchmodus erforderliche Zahlungszuordnung. Ein pausierter Tarif wird von vorhandenem physischem Bestand getrennt angezeigt. Der Bericht zeigt Partner und Stellplatzart, jedoch keine Schlüssel, Stripe-Kontonummern, Kundenadressen oder Buchungsdetails anderer Kunden. Andere Partner bleiben durch die Mandantenprüfung geschützt.

Die Prüfung reserviert nichts, verändert nichts und ruft kein externes Herstellersystem auf. Sie ist für eigene manuell verwaltete Angebote vorgesehen. Eine Bestandszahl allein bestätigt keine Buchbarkeit: Servicezeiten, Freigaben oder Aufenthaltsregeln können das Angebot weiterhin ausschließen. Nicht pauschal Regeln oder Kapazitäten ändern, bevor der tatsächliche Ausschluss bekannt ist.

## Weitere Korrektur

Nach erfolgreicher Suche beziehungsweise Auswahl einer Alternative werden die Reisedaten und Filter in der Browseradresse aktualisiert. Die laufende Auswahl zeigt zusätzlich Stellplatzart, Service und gegebenenfalls bevorzugten Anbieter. Es werden ausschließlich Suchparameter übernommen, keine Kontakt-, Fahrzeug- oder Zahlungsdaten.

## Lokale Prüfungen

`scripts/test-booking-search.php`: gemeldeter Zeitraum mit 20 Plätzen, Alternative tatsächlich buchbar, kein falsches Versprechen bei Zahlungs-/Höhenausschluss, fehlendes Höhenlimit unterdrückt keine gültigen Alternativen, Valet-Schließzeit 07:00 getrennt von vorhandenen Plätzen, neun Abrechnungszeiträume, Pausierung unabhängig von Bestand und fremde Partnerdiagnose gesperrt. Bericht `BOOKING-SEARCH-PRUEFUNG.json`.

Erweiterte Browserprüfung: Auswahl einer Außenalternative, korrekte nächste Anfrage, sichtbare Kriterien und URL, private Suchprüfung ohne Kundendaten. Zusätzlich bestehende Finanz-, Gutschein-, Bestands-, Mandanten- und Workflowtests in isolierter WordPress-CI. Gesamtbericht `BOOKING-SEARCH-CI-PRUEFUNG.json`.
