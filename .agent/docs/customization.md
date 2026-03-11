# Anpassung des Setups

## Was bleibt stabil (Core)

Diese Dateien sollten selten geaendert werden:
- `AGENTS.md`
- `.agent/policies/*.md`
- `.agent/scripts/*.sh`

## Was wird projektspezifisch angepasst

Bearbeite vor allem:
- `.agent/project/AGENTS.project.md`
- `.agent/project/architecture-context.md`
- `.agent/project/runbook.md`

Hier hinterlegst du Architekturwissen, kritische Komponenten, Teamprozesse und technische Grenzen.

## Eigene Skills ergaenzen

1. Neuen Ordner anlegen: `.agent/skills/<dein-skill>/`
2. `SKILL.md` erstellen:
   - `name:`
   - `description:`
3. Optional: eigene `references/` oder `scripts/` hinzufuegen.

Alternativ Skills ueber das Helferskript verwalten:
- `.agent/skill.sh sources` (zeigt nummerierte Uebersicht gängiger Skill-Seiten)
- `.agent/skill.sh find <query>`
- `.agent/skill.sh add <ref>`
- `.agent/skill.sh remove <name>`
- `.agent/skill.sh check`
- `.agent/skill.sh update`

Hinweis:
- `skill.sh` ist ein Wrapper fuer die skills.sh CLI (`npx skills ...`).
- Nicht explizit gemappte Befehle werden 1:1 an `npx skills` durchgereicht.
- Aufrufe laufen automatisch mit `--yes`.
- Skills werden immer in `.agent/skills` installiert (Befehle laufen immer im `.agent`-Verzeichnis).
- Die Ausfuehrung passiert ueber `.agent/skills/install.sh`.

## Commit-Regeln anpassen

- Policy-Text: `.agent/policies/commits.md`
- Technische Pruefung: `.agent/scripts/check-commit-msg.sh` (Regex)

Wenn du Typen oder Format aenderst, immer Policy und Hook gemeinsam aktualisieren.

## Empfohlener Team-Workflow

1. Regelanpassung als eigene PR
2. `validate-agent.sh` in CI als Pflicht-Check
3. Aenderungen kurz in PR begruenden (warum, erwarteter Effekt)

## Erststart-Kommunikation

Am Ende des Erstlaufs soll der Agent:
- die installierten Skills einmal als Liste ausgeben
- und danach explizit darauf hinweisen, dass weitere Skills mit `.agent/skill.sh` hinzugefuegt werden koennen
