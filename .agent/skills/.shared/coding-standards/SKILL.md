---
name: coding-standards
description: Enforce repository coding standards and safe change patterns. Use when implementing or reviewing code changes, especially for consistency, readability, and maintainability checks.
---

## Ziel

Code aendern mit Fokus auf Lesbarkeit, Konsistenz und geringe Regressionen.

## Eingaben

- Ziel der Aenderung
- betroffene Dateien/Module
- bestehende Team-Standards aus Policies

## Workflow

1. Betroffene Regeln in `./.agent/policies/` lesen.
2. Kleinsten sinnvollen Change planen.
3. Aenderung implementieren und lokalen Stil einhalten.
4. Relevante Checks/Tests ausfuehren.
5. Diff auf Klarheit und Seiteneffekte pruefen.

## Regeln

- Keine unnoetige Komplexitaet einfuehren.
- Bevorzugt bestehende Muster im Projekt fortsetzen.
- Kommentare vorerst nur dort, wo der Code ohne Kontext schwer verstaendlich ist.
- Vollständige Dokumentation erst am Ende nach Rücksprache.

## Output

- Kurze Aenderungsbeschreibung
- betroffene Dateien
- ausgefuehrte Checks
