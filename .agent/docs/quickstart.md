# Quickstart

## Ziel

In wenigen Schritten ein konsistentes Agent-Setup im Projekt aktivieren.

## Ein-Befehl-Setup

```bash
.agent/setup.sh
```

Das Skript fuehrt immer diese fuenf Schritte in Reihenfolge aus:

1. Projektdateien aus Templates erstellen (`init-agent.sh`)
2. Root-`AGENTS.md` einmalig erstellen oder ergaenzen
3. Commit-Policy-Hook installieren (`check-commit-msg.sh`)
4. `.agent` und `.custom` Skills nach `.agent/skills/.shared` synchronisieren und Root-Symlink `skills -> .agent/skills/.shared` setzen
5. Setup validieren (`validate-agent.sh`)

## Nutzung bei Einbindung als `.agent`

Wenn das Repo in ein anderes Projekt unter `.agent` geclont wurde, starte:

```bash
<pfad-zum-zielprojekt>/.agent/setup.sh
```

## Ergebnis

- Core-Regeln in `.agent/AGENTS.md`
- Root-Regeln in `AGENTS.md` (einmalig erstellt/ergaenzt)
- Projektspezifische Platzhalter in `.agent/project/`
- Commit-Message-Validierung ueber Git-Hook

## Skills (optional)

```bash
.agent/skill.sh sources
.agent/skill.sh find laravel
.agent/skill.sh add <ref>
.agent/skill.sh remove <name>
```

## Erstlauf-Hinweis (Pflicht)

Beim ersten Lesen der Agent-Regeln muss der Agent den Pflichtablauf aus `AGENTS.md` vollstaendig ausfuehren und abschliessen:

1. alle drei Dateien in `.agent/project/*` aus Projektkontext vorbefuellen,
2. explizit Kontrolle/Freigabe einholen,
3. Feedback umsetzen,
4. und die projektseitige `AGENTS.md` nur bei expliziter Erlaubnis sinnvoll ergaenzen.
5. Zum Schluss installierte Skills als Liste ausgeben und auf `.agent/skill.sh` zum weiteren Hinzufuegen hinweisen.

Erst danach darf der Erstlauf als abgeschlossen gemeldet werden.
