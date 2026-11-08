=== VPM Buchungskern ===
Contributors: vpm
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later

Separater Sandbox-Backend-Baustein fuer die geplante Dachmarke.

== Umfang ==
Stripe-Connect-Destination-Charge-Vorbereitung im Testmodus, signierte Webhooks,
Gutscheine und transaktionales centgenaues Journal, Erstattungsauftraege,
verschluesselte E-Mail-Ereignisqueue und ausdrueckliche Newsletter-Bestaetigung.
Keine Live-Zahlungen; keine echten Partner- oder E-Mail-Zugaenge enthalten.

== Installation ==
Nur in einer Entwicklungs-/Testinstallation installieren. Der bestehende
Muenchner Affiliate-Auftritt braucht dieses Plugin nicht. Aktivierung legt
leere eigene InnoDB-Tabellen und einen Worker an, aber keine Seiten, Anbieter,
Kunden oder E-Mail-Texte. Versand bleibt ohne ausdrueckliche Konfiguration aus.
OpenSSL und 64-Bit PHP erforderlich.

== Dokumentation ==
Siehe docs/BOOKING-CORE-0.1.0.md und docs/BOOKING-EMAIL-CONTRACT.md im Repository.
Noch kein oeffentlicher Buchungscheckout, keine Anbieter-Kapazitaetsanbindung,
keine rechtskonforme Rechnungserzeugung und keine Produktivfreigabe.
