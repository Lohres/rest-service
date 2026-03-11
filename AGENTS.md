# Agent Rules (Core)

Diese Datei definiert die verbindlichen Basisregeln fuer agentische Arbeit in diesem Projekt.
Ziel ist reproduzierbares, sicheres und nachvollziehbares Arbeiten ueber verschiedene Modelle und Agent-Clients hinweg.

## Prioritaet von Regeln

1. Direkte Nutzeranforderung
2. Sicherheits- und Compliance-Regeln
3. Projektregeln aus `./.agent/project/` (falls vorhanden)
4. Core-Regeln aus dieser Datei und `./.agent/policies/`

Bei Konflikten zwischen Projekt- und Core-Regeln gewinnt die projektspezifische Regel.
Bei Konflikten zwischen direkter Nutzeranforderung und Security/Compliance gewinnen Security/Compliance-Regeln.

## Arbeitsprinzipien

- Kleine, nachvollziehbare Aenderungen statt grosser ungepruefter Umbauten.
- Vor Datei-Aenderungen Kontext lesen und Auswirkungen verstehen.
- Vor Abschluss immer mindestens Basis-Checks ausfuehren.
- Entscheidungen, Annahmen und Grenzen transparent benennen.
- Keine Secrets in Logs, Commits oder Beispiel-Konfigurationen.

## Erstlauf-Verhalten (Pflichtablauf)

Der Erstlauf ist nur abgeschlossen, wenn alle Pflichtschritte in Reihenfolge erledigt sind.

1. `./.agent/project/AGENTS.project.md` aus Projektkontext vorbefuellen.
2. `./.agent/project/architecture-context.md` aus Projektkontext vorbefuellen.
3. `./.agent/project/runbook.md` aus Projektkontext vorbefuellen.
4. Nutzer explizit um Kontrolle/Freigabe der Entwuerfe bitten.
5. Nur nach expliziter Erlaubnis die projektseitige `AGENTS.md` im Zielprojekt sinnvoll ergaenzen (falls fachlich sinnvoll).
6. Nacharbeiten aus Nutzerfeedback umsetzen.
7. Sinnvolle Skills fuer das Projekt mit `./.agent/skill.sh` heraussuchen und vorschlagen; erst nach Bestaetigung durch den Nutzer installieren.
8. Zum Abschluss einmal die installierten Skills als Liste ausgeben.
9. Direkt danach darauf hinweisen, dass weitere Skills mit `.agent/skill.sh` hinzugefuegt werden koennen.

## Erstlauf-Abschlusskriterien

Der Agent darf den Erstlauf erst als `abgeschlossen` markieren, wenn alle Punkte erfuellt sind:
- alle drei `.agent/project/*`-Dateien sind befuellt
- eine explizite Kontrollanfrage wurde gestellt
- Feedback wurde eingearbeitet
- installierte Skills wurden einmal gelistet
- Hinweis auf `.agent/skill.sh` zum weiteren Hinzufuegen wurde gegeben
- fuer die projektseitige `AGENTS.md` wurde entweder eine explizite Erlaubnis umgesetzt oder eine explizite Ablehnung dokumentiert

Ohne Rueckmeldung bzw. Freigabe keine weitreichenden Annahmen als final behandeln.

## Git und Commits

- Branch-Namen nach Policy `./.agent/policies/git.md`.
- Commit-Messages nach Policy `./.agent/policies/commits.md`.
- Kein direkter Commit auf `main` oder `master` in Team-Workflows.

## Skill-Nutzung

- Alle Skill-Befehle ausschliesslich ueber `./.agent/skill.sh` ausfuehren.
- Skills fuer die Bearbeitung auch direkt aus dem Root-Symlink `./skills/` lesen (zeigt auf `./.agent/skills/.shared/`).
- Sobald es um Skills geht oder nach Skills gefragt wird, die Projekt-Skills immer frisch aus `./skills/` einlesen (nicht aus veraltetem Kontext antworten).
- Bei der Frage nach verfuegbaren Skills immer mit einer exakten, aktuellen Namensliste der gefundenen Skills antworten.

## Guardrails (Nicht erlaubt)

- Keine Umgehung von Security-/Compliance-Regeln zugunsten von Tempo.
- Keine Installation oder Nutzung unklarer Skill-Quellen ohne Nutzerfreigabe.
- Keine stillschweigende Interpretation fehlender Freigaben als Zustimmung.
- Keine destruktiven Aktionen ohne explizite Nutzeranweisung.

## Referenzen

- Skills: `./.agent/skills/.shared/` (Root-Symlink: `./skills`)
- Policies: `./.agent/policies/`
- Templates: `./.agent/templates/`
- Skripte: `./.agent/scripts/`

## Projektzusatz: lohres/rest-service

Diese projektspezifischen Hinweise konkretisieren die Core-Regeln fuer dieses Repository.

- Technischer Scope:
  Die Library mappt HTTP-Requests auf attributbasierte Endpoint-Methoden und liefert strukturierte JSON-Responses.
- Kritische Dateien:
  `src/RestService.php`, `src/AuthService.php`, `src/Response.php`, `src/Attributes/*`, `src/Enums/*`.
- Endpoint-Vertrag:
  Endpoint-Klassen liegen im konfigurierten Namespace/Dateipfad; Routen werden aus `#[Method]` + `#[Url]` erzeugt.
- Auth-Vertrag:
  `#[Auth(true)]` erzwingt Bearer-Token-Pruefung via projektspezifischer `AuthService`-Implementierung.
- CORS-Hinweis:
  CORS-Entscheidung basiert auf `LOHRES_ALLOWED_ORIGINS` in der Host-Anwendung; Verhalten nur mit Abstimmung aendern.
- Aenderungsgrenzen:
  Keine Breaking Changes an oeffentlichen Klassen/APIs ohne explizite Nutzerfreigabe und klare Migrationshinweise.
- Mindest-Verifikation bei Aenderungen:
  `bash .agent/scripts/validate-agent.sh` sowie ein kurzer Laufzeitcheck im Host-Projekt (Routing, Auth, Fehlerresponse).
