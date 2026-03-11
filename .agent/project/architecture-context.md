# Architecture Context

## Systemueberblick

- Zweck des Systems:
  Bereitstellung eines generischen REST-Dispatchers fuer lohres PHP-Projekte auf Basis von PHP-Attributen.
- Hauptkomponenten:
  - `RestService` als Entry-Point fuer Initialisierung und HTTP-Verarbeitung.
  - Endpoint-Klassen im konfigurierten Verzeichnis/Namespace.
  - Mapping-Cache (`rest-service-map.cache`) zur schnelleren Zielauflosung.
  - Optionaler Logger und optionaler `AuthService`.

## Datenfluss

1. Eingang:
   HTTP Request trifft auf Host-Anwendung; `RestService->init()` wird aufgerufen.
2. Verarbeitung:
   `parseInput()` normalisiert JSON-Body, `cors()` setzt Header, Routing wird ueber Map + `REQUEST_URI` aufgeloest;
   optional erfolgt Auth-Pruefung per `#[Auth(true)]`.
3. Speicherung/Ausgabe:
   JSON-Response via `Response` Objekt; Mapping wird bei Bedarf in Cache-Datei geschrieben/gelesen.

## Integrationen

- Externe APIs:
  Keine direkten externen APIs innerhalb der Library.
- Messaging/Queues:
  Keine.
- Datenbanken:
  Keine direkte DB-Anbindung innerhalb der Library.

## Nicht-funktionale Anforderungen

- Sicherheit:
  Auth wird endpointbasiert via Attribut aktiviert; Header-Token wird als Bearer erwartet.
- Performance:
  Reflection-basierte Endpoint-Erkennung wird durch Dateicache der Route-Map reduziert.
- Verfuegbarkeit:
  Fehler werden als strukturierte JSON-Responses mit HTTP-Status zurueckgegeben.

## Entscheidungen und Trade-offs

- Entscheidung:
  Attribut-basiertes Endpoint-Mapping statt zentraler statischer Routenliste.
- Alternative:
  Konfigurationsdatei oder expliziter Router mit manueller Registrierung.
- Begruendung:
  Weniger Boilerplate in Endpoints, direkte Kopplung von Route und Handler im Code.
