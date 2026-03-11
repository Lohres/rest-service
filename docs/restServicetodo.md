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
3. Cache-Strategie verbessern:
   - Invalidation bei Endpoint-Aenderungen
   - Option fuer erzwungenen Rebuild und Cache-Disable in Dev
4. Endpoint-Aufruf robuster gestalten:
   - Statischen Aufruf (`$class::$method()`) validieren
   - Klarere Fehlermeldung, wenn Methode nicht statisch/inkompatibel ist
5. Input-Parsing erweitern:
   - Weitere Content-Types unterstuetzen
   - Defensive Behandlung leerer/ungueltiger JSON-Payloads
6. Fehlerbehandlung konsolidieren:
   - `die()`/`exit()` minimieren und zentrale Response-Strategie verwenden
   - Einheitliche Fehlerstruktur fuer alle Ausnahmefaelle
7. Logging-Hygiene verbessern:
   - Sensible Daten (z. B. Tokens) nie im Klartext loggen
   - Log-Level und Kontext klar standardisieren
8. API-Dokumentation ergaenzen:
   - Erwartetes Endpoint-Pattern mit Attributbeispielen dokumentieren
   - Contract fuer `Response` und `AuthService` klar beschreiben
9. Konfigurationsvalidierung vertiefen:
   - Schreib-/Leserechte fuer `cachePath` explizit pruefen
   - Fehlende oder inkonsistente Konfigurationswerte frueher abfangen
10. CI-Checks aufsetzen:
    - Syntax/Lint, statische Analyse (z. B. PHPStan), Unit-Tests
    - Commit-/PR-Gates fuer regressionssichere Aenderungen
