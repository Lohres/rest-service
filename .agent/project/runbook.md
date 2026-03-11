# Runbook

## Zweck

Inbetriebnahme und Basis-Debugging der `lohres/rest-service` Library im Host-Projekt.

## Voraussetzungen

- Zugriff:
  Zugriff auf Host-Projekt mit Composer-Autoload und Endpoint-Klassen.
- Umgebungsvariablen:
  Keine direkten ENV-Pflichten in dieser Library; benoetigt aber:
  - Konfiguration fuer `cachePath`, `filePath`, `namespace` (optional `replace`).
  - Konstante `LOHRES_ALLOWED_ORIGINS` in Host-Konfiguration.
- Tools:
  - PHP 8.4
  - Composer
  - Optional: Monolog Logger

## Ablauf

1. Library via Composer autoloaden und `RestService` mit Konfiguration instanziieren.
2. Sicherstellen, dass `filePath` auf Endpoint-Dateien zeigt und Namespace passt.
3. `init()` im Web-Kontext aufrufen; fuer initiales Mapping wird Cache-Datei erzeugt.
4. Request gegen endpointpfad `<endpoint>/<route>` senden; bei geschuetzten Endpoints `Authorization: Bearer <token>` setzen.

## Verifikation

- Erwartetes Ergebnis:
  JSON-Response mit konsistenter Struktur (`success`, optional `debug`, `content`) und passendem HTTP-Code.
- Health-Checks:
  - Cache-Datei `rest-service-map.cache` wurde im `cachePath` erstellt.
  - Endpoint-Aufruf wird auf erwartete Methode aufgeloest.
  - CORS-Header sind entsprechend `LOHRES_ALLOWED_ORIGINS` gesetzt.
  - Bei fehlendem/ungueltigem Token liefert Auth-Endpoint einen 403/Fehlerresponse.

## Rollback

- Rueckgaengig-Schritte:
  - Neue Library-Aenderungen im Host-Projekt revertieren.
  - Cache-Datei loeschen, um Mapping bei Neustart neu zu erzeugen.
  - Auf letzte stabile Package-Version zurueckgehen.
- Sicherheitspruefung danach:
  - Pruefen, dass geschuetzte Endpoints weiterhin nur mit gueltigem Bearer-Token erreichbar sind.
  - CORS-Whitelist gegen erwartete Origins validieren.
