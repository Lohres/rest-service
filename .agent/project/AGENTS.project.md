# Project Agent Rules

Diese Datei erweitert `AGENTS.md` um projektspezifischen Kontext.

## Projektkontext

- Domain:
  PHP-Library fuer leichtgewichtiges REST-Routing in lohres Projekten (ohne Fullstack-Framework).
- Hauptziele:
  HTTP-Requests auf Endpoint-Klassen mappen, einheitliche JSON-Responses liefern, optional Token-Auth pruefen.
- Kritische Komponenten:
  `RestService` (Routing/Dispatch), Attribut-basierte Endpoint-Metadaten (`Method`, `Url`, `Auth`), `Response`,
  Cache-Datei `rest-service-map.cache`.

## Architekturhinweise

- Services/Module:
  - `RestService`: Initialisierung, Input-Parsing, CORS, Auth-Pruefung, Endpoint-Aufruf, Fehlerbehandlung.
  - `AuthService` (abstract): projektseitige Token-Validierung via `checkToken(string $token): void`.
  - `Response`: Standard-JSON-Struktur (`success`, `debug`, `content`).
  - Enums/Attributes: HTTP-Codes, Request-Methoden, Mapping-Attribute.
- Externe Schnittstellen:
  - Laufzeit: PHP Superglobals (`$_SERVER`, `$_POST`), HTTP Header, `php://input`.
  - Logging: optional `monolog/monolog` Logger.
  - Konsumentenseite: Endpoint-Klassen im konfigurierten Namespace und Dateipfad.
- Technische Grenzen:
  - Nur Laufzeit in Web-Kontext (`PHP_SAPI !== cli`) vorgesehen.
  - Endpoint-Aufruf erfolgt statisch (`$class::$method()`), Endpoint-Methoden muessen damit kompatibel sein.
  - CORS-Logik erwartet Konstante `LOHRES_ALLOWED_ORIGINS` in der Host-Anwendung.

## Projektspezifische Regeln

- Namenskonventionen:
  PSR-4 Namespace `Lohres\\RestService\\`, `declare(strict_types=1)`, Dateinamen entsprechen Klassen.
- Testanforderungen:
  Aktuell kein dediziertes Test-Setup im Repository; mindestens Syntax- und Integrationscheck in Host-Projekt ausfuehren.
- Freigabeprozess:
  Aenderungen an Routing/Auth/CORS nur mit nachvollziehbarer Verifikationsbeschreibung und Nutzerfreigabe fuer
  verhaltensrelevante Anpassungen.

## Risiken

- Bekannte Risiken:
  - Falsche oder veraltete Map-Cache-Datei kann Routingfehler erzeugen.
  - Fehlende `Authorization` Header oder inkonsistente Auth-Implementierung fuehren zu 403.
  - Direkte Nutzung von Superglobals erschwert isolierte Tests.
- Nicht aendern ohne Abstimmung:
  - Oeffentliche API-Surface der Klassen in `src/`.
  - Semantik der Attribute `Method`, `Url`, `Auth`.
  - Verhalten der Fehlerantwortstruktur in `Response`.
