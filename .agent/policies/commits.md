# Commit Message Policy

## Format

`type(scope): summary`

- `type`: `feat|fix|refactor|docs|test|chore|ci|build|perf`
- `scope`: optional, kleinschreibung (`api`, `auth`, `ui`)
- `summary`: kurz, imperativ, max 72 zeichen, mit prefix nach dem type "ki -", ggf. mit Ticket-ID aus Branchname am Anfang der summary wenn vorhanden, wenn keine Ticket-ID im Branchnamen ist frage nach einer und speicher diese im Projekt-Kontext für folgende Commits für dich ab wenn eine genannt wird, sonst lasse die Ticket-ID weg, Ticket-ID Format: X-*, X steht für eine beliebige Buchtstabenfolge, * steht für eine Zahl

## Beispiele

Ohne Ticket-ID:
- `feat(auth): ki - add token refresh endpoint`
- `fix(api): ki - handle empty pagination cursor`
- `docs(readme): ki - add local setup steps`

Mit Ticket-ID:
- `feat(auth): DS-43 ki - add token refresh endpoint`
- `fix(api): HK-432 ki - handle empty pagination cursor`
- `docs(readme): SCHMITT-678 ki - add local setup steps`

## Nicht erlaubt

- `WIP`, `tmp`, `misc` als Summary
- Leere oder nichtssagende Commit-Messages
