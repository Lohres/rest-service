---
name: architecture-review
description: Review or extend system architecture context for implementation planning. Use when tasks touch service boundaries, data flows, integration points, or non-functional constraints.
---

## Ziel

Architektur-Auswirkungen frueh sichtbar machen und dokumentieren.

## Eingaben

- betroffener Scope (Service, Modul, API)
- Architekturkontext (falls vorhanden)
- nicht-funktionale Anforderungen (z. B. Sicherheit, Performance)

## Workflow

1. `./.agent/templates/architecture-context.md` als Struktur nutzen.
2. Bestehende Datenfluesse und Abhaengigkeiten erfassen.
3. Risiko- und Impact-Punkte benennen.
4. Offene Entscheidungen explizit dokumentieren.

## Regeln

- Annahmen klar markieren.
- Entscheidungen mit Trade-offs festhalten.
- Integrationsgrenzen immer sichtbar dokumentieren.

## Output

- kompakte Architekturzusammenfassung
- Liste der Risiken
- offene Fragen/Entscheidungen
