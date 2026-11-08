# Buchungskern 0.4.0: lokale Formulare und bepreiste Zusatzleistungen

Entwicklungsmodul für die Dachmarke, Stand 8. November 2026. [Verwaltung/Kundenzugang](BOOKING-OPERATIONS-0.3.0.md) und [Anbindungsgrenzen](BOOKING-COMPAT-0.2.0.md) bleiben gültig. Kein Update der Münchner Texte oder Affiliate-Buchungsweise; keine Installation auf Hostinger durch GitHub-Push.

## Zwei Formularvarianten

**Lokale Standortseite Stuttgart:**

```text
[vpm_booking_form airport="STR"]
```

Stuttgart steht fest, als lesbarer Flughafenname, ohne Dropdown oder zusätzliche Auswahl. Derselbe Shortcode funktioniert für alle vorhandenen IATA-Codes, etwa FRA, MUC, ZRH oder VIE. Ungültige Codes erzeugen einen Konfigurationshinweis und niemals unbemerkt ein allgemeines Formular.

**Startseite, Ratgeber, allgemeine Buchungsseite:**

```text
[vpm_booking_form]
```

Der Besucher kann den Flughafen aus dem Katalog auswählen. Optional nur vorauswählen, aber weiterhin Änderungen erlauben:

```text
[vpm_booking_form airport="STR" airport_mode="select"]
```

Englisch mit `locale="en"`, sonst automatische Polylang-/WordPress-Sprache. In Gutenberg besitzt der Block **VPM Buchungsanfrage** die Einstellungen Flughafen, feste/freie Auswahl und Sprache. Elementor Free: dasselbe über das Shortcode-Widget. Mehrere lokale und allgemeine Formulare auf einer Seite beeinflussen ihre Auswahl nicht gegenseitig. Die separate Flughafenauswahl `[vpm_airport_selector]` befüllt nur frei auswählbare Formulare oder öffnet die zugeordnete Standortseite.

Ein gesetzter Flughafen war in 0.3.0 nur vorausgewählt; ab 0.4.0 bedeutet er standardmäßig **fest**. Wer das bisherige Verhalten möchte, setzt ausdrücklich `airport_mode="select"`. Ein fester Formularbereich übergibt eine signierte Flughafenbindung, die beim Angebot und bei der Anfrage geprüft wird. Dies ist eine Konsistenzprüfung zwischen Formular/Angebot, keine Zugriffsberechtigung: Die allgemeine öffentliche Schnittstelle bleibt für andere Flughäfen nutzbar.

## Angebotsauswahl

- Verfügbare Partner und ihre einzelnen Angebote als Karten, mit Preis und Produktname.
- Stellplatzarten: Außenstellplatz, überdachter Stellplatz, Parkhaus, Tiefgarage; fehlende Angaben werden als solche kenntlich gemacht.
- Filter nach tatsächlich gelieferten Stellplatzarten. Die Auswahl einer anderen Filterart setzt eine bisherige Angebotsauswahl zurück.
- Freie Serviceattribute je Angebot, z. B. Übergabeort oder enthaltene Leistungen. Keine Sicherheits-/Serviceversprechen automatisch ergänzen.
- Optional wählbare Zusatzleistungen mit eigenem Einzelpreis, Beschreibung und maximaler Menge. Keine Option ist vorausgewählt.
- Sofortige Darstellung des Gesamtbetrags sowie eine Aufschlüsselung vor der Anfrage. Ein Gutschein wird weiterhin serverseitig beim Absenden geprüft; der vorherige Gesamtbetrag ist ausdrücklich „vor Gutschein“.
- Gewählte Leistungen werden mit Preis und Menge im Buchungs-Snapshot, in der Kundenauskunft, im WordPress-Datenexport und im transaktionalen E-Mail-Ereignis gespeichert. Partnerpreise später zu ändern überschreibt keine angenommenen Angebote/Buchungen.

Die Oberfläche bleibt eine **Anfrage**, kein öffentlicher zahlungspflichtiger Checkout. Sie zeigt nur Angebote, die ein installierter Serveradapter liefert. Keine Testpartner/-preise oder künstliche Verfügbarkeit werden im Paket mitgeliefert.

## Preise im Plugin konfigurieren

Unter **Werkzeuge → VPM Buchungsverwaltung → Angebotsvorlagen und Zusatzleistungen**:

1. Einen vorbereiteten Partner wählen.
2. Angebotsname DE/EN, Stellplatzart und Serviceattribute eintragen.
3. Fahrzeugwäsche, Ladepaket, Betankungspaket und bis zu zwei eigene Extras konfigurieren. Je Extra: Bezeichnung DE/EN, Leistungsumfang/Einheit DE/EN, Preis in Cent, maximale Menge.
4. Vorlage speichern; ihre ID wird in der Tabelle für die spätere Anbindung angezeigt. Pro Partner können mehrere Vorlagen entstehen, beispielsweise Außenstellplatz und Tiefgarage.

Das Speichern ist **keine Verfügbarkeitsbestätigung**. Der Adapter muss eine Vorlage und die in diesem Zeitraum tatsächlich lieferbaren Extras explizit auswählen. Ohne diese Auswahl werden auch konfigurierte Zusatzleistungen nicht angeboten. Grundpreise und verfügbare Stellplatzvarianten kommen weiterhin aus der Partneranbindung. Ein Import-/Preisabgleich mit realen Partnersystemen ist noch nicht implementiert.

Die Verwaltung besitzt zunächst fünf Extra-Felder je Vorlage; ein eigener Serveradapter kann bis zu 15 Optionen je Angebot liefern. Bis zu 200 Vorlagen, je Vorlage maximal zwölf Serviceattribute. EUR ist weiterhin die einzige implementierte Abrechnungswährung.

## Lade- und Betankungsleistungen

Implementiert sind **klar bepreiste Einheiten/Pakete**. Beispiele: eine Fahrzeugwäsche, ein definiertes Ladepaket oder ein vereinbartes Betankungspaket. Die Mengenobergrenze legt der Server fest. Paketumfang und mögliche Voraussetzungen gehören in die Beschreibung.

Nicht enthalten sind automatische Erfassung tatsächlich geladener kWh/getankter Liter, nachträgliche variable Belastungen oder Integration einer Ladesäule/Tankstelle. Dafür wären Messdaten, Zahlungsfreigabe und konkrete Partnerregeln nötig. Solche Kosten nicht als bereits vollständig abgerechnet darstellen.

Das bestehende Finanzmodell berechnet die Partnerprovision auf den kombinierten Betrag aus Parken und ausgewählten Extras; Gutscheine und Erstattungen verwenden diesen Gesamtbetrag. Abweichende Provisionssätze je Zusatzleistung oder getrennte Leistungserbringer sind noch nicht implementiert und müssen vor Verwendung separat modelliert werden.

## Adaptervertrag

Bestehende einfache Angebote bleiben gültig. Zusätzlich möglich:

```json
{
  "partner_id": 123,
  "base_cents": 10000,
  "currency": "eur",
  "terms": "Freigegebene Angebotsbedingungen",
  "product_name": {"de": "Außenstellplatz", "en": "Outdoor parking"},
  "parking_type": "outdoor",
  "attributes": [{"de": "Übergabe am Terminal", "en": "Terminal handover"}],
  "addons": [
    {
      "id": "wash",
      "label": {"de": "Fahrzeugwäsche", "en": "Car wash"},
      "description": {"de": "Definiertes Waschpaket", "en": "Defined wash package"},
      "unit_amount_cents": 1500,
      "max_quantity": 1
    }
  ]
}
```

Diese Zahlen sind ausschließlich ein Vertragsbeispiel, keine veröffentlichten Angebote. `base_cents` ist der Parkpreis ohne optionale Extras für den angefragten Zeitraum. Alle Preise werden als Integer-Centbeträge übergeben. `parking_type` ist `outdoor`, `covered`, `garage`, `underground` oder `unspecified`. Labels/Attribute erlauben DE-/EN-Objekte oder einfache Strings; bei einfachen Strings wird keine Übersetzung erfunden.

Alternativ eine im Plugin gespeicherte Vorlage auswählen:

```json
{
  "partner_id": 123,
  "base_cents": 10000,
  "currency": "eur",
  "terms": "Freigegebene Angebotsbedingungen",
  "catalog_product_id": "product_012345abcdef",
  "available_addon_ids": ["wash", "charging"]
}
```

Vorlagen anderer Partner werden zurückgewiesen. `available_addon_ids` fehlt/ist leer: keine Zusatzleistungen anzeigen. Parkpreis kommt auch hier vom Adapter, Zusatzpreise aus der bewusst zugeordneten Konfiguration. Ein Angebot ist weiterhin zehn Minuten gültig. Datenbank-Schema 4 ergänzt die gespeicherten Produktdetails der Angebotstabelle; alte Angebote/Buchungen werden nicht umgeschrieben.

Öffentliche Anfrage übernimmt `addons: [{"id":"wash","quantity":1}]` und bei festen Formularen `airport_scope`. Die Preise stammen ausschließlich aus dem gespeicherten Angebot. Unbekannte/nicht verfügbare Extras, doppelte IDs, manipulierte Preisfelder innerhalb einer Auswahl, ungültige Mengen und zu hohe Gesamtbeträge werden zurückgewiesen. Unterschiedliche Optionen nach bereits erfolgreicher Anfrage brauchen ein neues Angebot; Wiederholungen mit gleicher Auswahl bleiben idempotent.

## Darstellung und Prüfung

Neben den neuen Ansichten ist ein Fehler beim frühen Stylesheet-Laden korrigiert: Die Erkennung von Formularen reiht CSS jetzt nur ein, statt die Ausgabe während des Head-Aufbaus abzufangen. Neue Browserprüfung kontrolliert deshalb auch tatsächlich angewendetes CSS. Responsive Darstellung, keine externen Schriftdateien und keine zusätzlichen Analytics-Parameter für gewählte Extras.

Lokal, bestehende Loopback-Installation und leere eigene Buchungstabellen vorausgesetzt:

```bash
VPM_TEST_PYTHON=/tmp/vpm-design-env/bin/python bash scripts/test-booking-products-local.sh
```

[Neue Backendprüfungen](BOOKING-PRODUCTS-PRUEFUNG.json) · [Neue Browserprüfungen](BOOKING-PRODUCTS-BROWSER-PRUEFUNG.json). Die bisherigen Prüfungen werden davor ausgeführt. Keine echten Partner-/Zahlungs-/E-Mail-Aufrufe. Screenshots enthalten ausschließlich lokale Testdaten: [Desktop](../zwischenstand/buchungskern/angebote-stuttgart-test.png), [Mobil](../zwischenstand/buchungskern/angebote-stuttgart-mobil-test.png).

Installierbares separates Paket: `pakete/vpm-booking-core.zip`, Version 0.4.0. Vor einem Update sichern und zunächst testen; GitHub-Paket ist nicht bereits auf Hostinger installiert.
