# Buchungskern 0.3.0: Verwaltung und Kundenzugang

Entwicklungsstand vom 8. November 2026. Separates Plugin für die spätere Dachmarke. Keine automatische Änderung oder Installation auf der Münchner Produktivwebsite. [Vorbereitung 0.2.0](BOOKING-COMPAT-0.2.0.md) und [Sandbox-Zahlungsmodell](BOOKING-CORE-0.1.0.md) gelten weiterhin, soweit hier nicht erweitert.

## Neu: Verwaltung ohne eigene API-Aufrufe

Unter **Werkzeuge → VPM Buchungsverwaltung**:

- Namen der 15 Flughäfen auf Deutsch/Englisch und Standortseiten-IDs über normale Formularfelder ändern. Die Auswahl speichert keine neuen Inhalte und veröffentlicht keine Seiten. Weitere Flughäfen/Zeitzonen weiterhin über den erweiterten Katalog unter VPM Buchungskern.
- Partner anlegen und bearbeiten: Name, Flughafen, Provision in Basispunkten, optionale Stripe-Account-ID und Freigabe für neue Anfragen. 100 Basispunkte entsprechen 1 %. Neue Partner sind in dieser Oberfläche zunächst pausiert und brauchen noch keine Zahlungszuordnung. Keine erfundenen Zugangsdaten nötig.
- Pausierte Partner werden aus neuen öffentlichen Angeboten ausgeschlossen und können keine neuen Anfragen erhalten. Bestehende Anfragen und ihre gespeicherten Finanzdaten bleiben erhalten. Freigabe allein liefert keine Verfügbarkeit; dafür braucht es weiterhin einen echten Adapter.
- Gutscheine mit Typ, Wert, Gesamtbudget, Einlösegrenzen, Flughafen-/Partnerbindung und Gültigkeit anlegen. Beträge in Cent, Prozentwerte in Basispunkten, Gültigkeit ausdrücklich UTC. Bestehende Einlösungen werden nicht überschrieben. Keine Funktion zum Löschen finanzieller Nachweise.
- Buchungen/Anfragen nach exakter Referenz oder E-Mail suchen; seitenweise je 25 Einträge, getrennte Anfrage- und Zahlungsstatus sowie erstatteter Betrag.
- Zugangslink über die konfigurierte E-Mail-Automatisierung anfordern oder vorhandene Zugänge widerrufen. Ohne konfigurierte Automatisierung wird kein Versand behauptet. In der Verwaltung wird kein Bearer-Link als Klartext ausgegeben.
- Eingegangene Stornowünsche als geprüft markieren. Das führt **keinen** Storno beim Partner und **keine** Erstattung aus.
- Änderungsprotokoll mit Zeitpunkt, WordPress-Nutzer-ID, Aktion und Objekt-ID. Keine Tokens, freien Kundentexte oder Schlüssel im Protokoll. Kein manipulationssicheres Archiv.

Zugriff nur für WordPress-Administratoren mit `manage_options`; Speichern nur per POST mit WordPress-Nonce. Das ist eine Betreiberverwaltung, noch kein rollengetrenntes Partnerportal. Listen für Partner/Gutscheine/Stornowünsche/Protokoll zeigen jeweils die letzten 100 Einträge; nur die Buchungsliste besitzt bereits eine vollständige Seitennavigation.

## Neu: Kundenzugang ohne Benutzerkonto

Neutrale Zugangsseite: `/?vpm_booking_manage=1`, Englisch zusätzlich `&locale=en`. Sie lässt sich später aus eigenen Buchungsbestätigungen oder dem Seitenaufbau verlinken. Nach einer öffentlichen Anfrage wird deren Referenz jetzt auch in der Formularrückmeldung angezeigt.

Der Kunde kann Referenz und E-Mail eingeben. Die Antwort bleibt unabhängig vom Treffer identisch. Bei passender Zuordnung und eingerichteter Automatisierung entsteht das transaktionale Ereignis **`booking.access_requested`**, mit `access_url` und `access_expires_at` zusätzlich zu den bisherigen sicheren Buchungsfeldern. E-Mail-Inhalte und tatsächlicher Versanddienst bleiben separat einzurichten. Keine Marketingeinwilligung für den rein transaktionalen Zugriff verlangen.

- Zufälliger 256-Bit-Zugang, gültig 30 Minuten; ein neuer Link widerruft vorherige Links derselben Buchung.
- Token nur als Hash in der Zugangstabelle. Versandpayload liegt verschlüsselt in der bestehenden Outbox; Empfänger und E-Mail-System haben naturgemäß Zugriff auf den zugestellten Link und müssen diesen vertraulich behandeln.
- Token im URL-Fragment, nicht im HTTP-Abfrageparameter. Browser entfernt das Fragment aus der Adresszeile und hält den Token nur im Speicher dieses Tabs. Keine Cookies, kein Local-/Session-Storage und keine Analytics-Skripte auf dieser separaten Seite.
- Nach Neuladen ist der Zugang im Tab nicht mehr vorhanden: ursprünglichen E-Mail-Link erneut öffnen oder neuen Link anfordern. Kein dauerhaftes Kundenkonto und keine dauerhafte Sitzung.
- Statusdaten werden per JSON-POST abgerufen; GET liefert keine Kundenauskunft. Private Antworten mit `no-store`, `noindex` und `no-referrer`; zusätzliche CSP und Schutz gegen Einbettung.
- Ansicht zeigt Referenz, Flughafen, gespeicherte Reisezeiten, Anfrage-/Zahlungsstatus, Betrag, Erstattung und Angebotshinweise. Keine Stripe-Konten, Provisionen oder vollständige Kundene-Mail.
- Stornowunsch erst nach bewusst gesetztem Bestätigungsfeld und POST. Wiederholte Aufrufe erzeugen keinen zweiten Vorgang. Ein Wunsch oder die Kennzeichnung „geprüft“ verändern weder Buchungsstatus noch Geldbewegungen.
- Bestehende WordPress-Datenauskunft enthält nun auch Änderungswünsche. Die Löschprüfung widerruft Kundenzugänge; Buchungs-/Einwilligungsnachweise bleiben bis zur geprüften Aufbewahrungsentscheidung erhalten.

Jeder, der den aktiven E-Mail-Link besitzt, kann während dessen Gültigkeit die betreffende Ansicht öffnen und einen Stornowunsch senden. Keine Identitätsprüfung per Ausweis oder MFA. Rate-Limits begrenzen Anfragen; vollständiger Bot-/Missbrauchsschutz und produktive Proxy-Konfiguration bleiben separat zu prüfen.

## Zahlung und Datenbankupdate

Das Plugin bleibt strikt im Stripe-Testmodus. Ein fehlendes Partner-Zahlungskonto wird jetzt vor Änderung des Zahlungsstatus zurückgewiesen. Partnerdaten lassen sich vorher vorbereiten; eine Konto-ID ist keine Verifikation durch Stripe. Bereits gespeicherte Buchungs-Snapshots werden bei späteren Partneränderungen bewusst nicht umgeschrieben. Eine alte Anfrage ohne Zahlungszuordnung wird deshalb durch Eintragen einer neuen Konto-ID nicht nachträglich zahlbar.

Schema 3 ergänzt Partnerfreigabe sowie Tabellen für Zugangshashes, Stornowünsche und Protokoll. Bestehende Partner aus 0.2.0 behalten ihre bisherige Freigabe; neue Partner über die Verwaltungsoberfläche sind standardmäßig pausiert. Migration erhält vorhandene Buchungen und Erstattungsstände. Bei Aktivierung werden keine Testpartner, Seiten, Gutscheine oder Buchungen erzeugt.

Das Update baut **keinen öffentlichen Bezahlcheckout**, keinen Herstelleradapter, keine Kapazitätsreservierung, keine automatischen Partnerstornos, keinen Rechnungsgenerator und keine Live-Zahlungsfreischaltung ein. Die bestehenden geschützten administrativen Sandbox-Routen können den Test-Zahlungsablauf bedienen; sie ersetzen keinen bestätigten Partnervertrag oder die erforderliche letzte Verfügbarkeitsprüfung.

## Installation und Prüfung

1. Testinstallation sichern. `pakete/vpm-booking-core.zip` unter Plugins → Installieren hochladen; vorhandene Version bewusst ersetzen.
2. Aktivieren, Werkzeuge → VPM Buchungsverwaltung öffnen. Theme und Münchner Inhaltsimport müssen dafür nicht aktualisiert oder erneut ausgeführt werden.
3. Flughäfen/Partner vorbereiten. Keine Freigabe als tatsächlich buchbares Angebot ohne funktionierenden Adapter. Automatisierung und Analyse bleiben gesondert zu aktivieren.
4. Vor einem späteren Produktionsbetrieb echte E-Mail-Zustellung, HTTPS, Proxy/Rate-Limit, Servercron, Rollen, Datenschutzfristen und Partnerablauf testen.

Reproduzierbarer **lokaler** Test, bestehende Loopback-Installation und leere eigene Buchungstabellen erforderlich:

```bash
VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-operations-local.sh
```

Enthält die bisherigen Finanz-, Einwilligungs-, Parallelitäts- und Kompatibilitätstests sowie den neuen Ablauf: Angebot → Gutschein → Anfrage → administrative Bestätigung → abgefangener Stripe-Testcheckout → signiertes Test-Zahlungsereignis → Kundenzugang → Stornowunsch → Prüfung → ausdrücklicher Teststorno → abgefangene Erstattung. Kein echter externer Zahlungs- oder E-Mail-Aufruf.

[30 neue Ablaufprüfungen](BOOKING-OPERATIONS-PRUEFUNG.json) · [24 neue Browserprüfungen](BOOKING-OPERATIONS-BROWSER-PRUEFUNG.json). Zusätzlich die bisherigen 66 Backend-, 25 Kompatibilitäts- und 46 Browserprüfpunkte. Die Prüfumgebung und Grenzen aus 0.2.0 bleiben bestehen; kein vollständiger Hostinger-/Stripe-/Hersteller-Livetest.

Vorschauen mit ausdrücklich lokalen Testdaten: [Flughafenverwaltung](../zwischenstand/buchungskern/verwaltung-flughaefen-test.png) und [Kundenzugang](../zwischenstand/buchungskern/kundenzugang-test.png). Keine finalen Produkttexte oder echten Buchungen.
