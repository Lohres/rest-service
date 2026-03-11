# Git Policy

## Branching

- Feature: `feature/<ticket-id>-<kurzbeschreibung>`
- Fix: `fix/<ticket-id>-<kurzbeschreibung>`
- Chore: `chore/<ticket-id>-<kurzbeschreibung>`
- Hotfix: `hotfix/<ticket-id>-<kurzbeschreibung>`

Nur Ticket-ID groß schreiben, sonst nutze kleinschreibung und bindestriche.

## Merge-Regeln

- Kein direkter Push auf `main`, `dev`, `master`, `development`
- Kein force push.
- Branch vor letztem Push mit Zielbranch synchronisieren (rebase).
- Merge nur in GitHub verboten.

## Historie

- Kleine, logisch getrennte Commits.
- Keine irrelevanten Datei-Aenderungen im selben Commit.
