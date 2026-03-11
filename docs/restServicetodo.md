# REST Service TODO

## Verbesserungs-Vorschlaege

1. [x] Test-Setup eingefuehrt (PHPUnit) und Kernpfade automatisiert getestet:
   - Routing-Mapping (`generateMap`, `parseUrl`)
   - Auth-Pfade (`#[Auth(true)]`, fehlender/ungueltiger Bearer-Token)
   - Fehlerantworten und HTTP-Statuscodes
   - Status: umgesetzt via `phpunit.xml`, `tests/` Fixtures und 6 Passing Tests
2. [x] CORS-Konfiguration flexibler gemacht:
   - Erlaubte Methoden und Header nicht hart kodieren
   - Werte aus Konfiguration statt globaler Konstante beziehen
   - Status: umgesetzt ueber neue Config-Keys in `RestService` mit Rueckfall auf Defaults/Legacy-Konstante
3. [x] Cache-Strategie verbessert:
   - Invalidation bei Endpoint-Aenderungen
   - Option fuer erzwungenen Rebuild und Cache-Disable in Dev
   - Status: umgesetzt ueber signaturbasierten Cache, `cacheForceRebuild` und `cacheEnabled`
4. [x] Endpoint-Aufruf robuster gestaltet:
   - Statischen Aufruf (`$class::$method()`) validieren
   - Klarere Fehlermeldung, wenn Methode nicht statisch/inkompatibel ist
   - Status: umgesetzt mit Target-Validierung (`public static`) und Rueckgabetyppruefung auf `Response`
5. [x] Input-Parsing erweitert:
   - Weitere Content-Types unterstuetzen
   - Defensive Behandlung leerer/ungueltiger JSON-Payloads
   - Status: umgesetzt fuer `application/json` und `application/*+json` mit sicherem Fallback auf leeres Array
6. [x] Fehlerbehandlung konsolidiert:
   - `die()`/`exit()` minimieren und zentrale Response-Strategie verwenden
   - Einheitliche Fehlerstruktur fuer alle Ausnahmefaelle
   - Status: umgesetzt ueber zentralen Exception-Flow in `init()` mit normalisierter JSON-Fehlerresponse
7. [x] Logging-Hygiene verbessert:
   - Sensible Daten (z. B. Tokens) nie im Klartext loggen
   - Log-Level und Kontext klar standardisieren
   - Status: umgesetzt ueber Maskierung sensibler Werte und einheitlichen Exception-Logkontext
8. [x] API-Dokumentation ergaenzt:
   - Erwartetes Endpoint-Pattern mit Attributbeispielen dokumentieren
   - Contract fuer `Response` und `AuthService` klar beschreiben
   - Status: umgesetzt in `README.md` mit Endpoint-Muster und klaren Contract-Abschnitten
9. Konfigurationsvalidierung vertiefen:
   - Schreib-/Leserechte fuer `cachePath` explizit pruefen
   - Fehlende oder inkonsistente Konfigurationswerte frueher abfangen
10. CI-Checks aufsetzen:
    - Syntax/Lint, statische Analyse (z. B. PHPStan), Unit-Tests
    - Commit-/PR-Gates fuer regressionssichere Aenderungen
