# Codex Client App mit DIA Agent Setup einrichten

## Übersicht

Diese Seite beschreibt ein standardisiertes Setup der Codex Client App mit diesem Repository als Agent-Framework. Schwerpunkt sind:
- `AGENTS.md` als Steuerung fuer Regeln, Reihenfolgen und Sicherheitsgrenzen
- Skills ueber `.agent/skill.sh` als wiederverwendbare Erweiterungen
- Das Zusammenspiel beider Ebenen in einem fiktiven Projektablauf
- Risiken bei unkontrollierter Agent-Nutzung (inkl. absolute DONTs)
- Quickstart fuer ein lauffaehiges Setup in wenigen Minuten

### Schnellstart (unter 5 Minuten)

```bash
cd <pfad-zum-zielprojekt>
.agent/setup.sh
```

Skill-Management und Skill-Quellen:
- .agent/skill.sh verwendet [skills.sh](https://skills.sh/)

Das Agent-Setup macht und gewaehrleistet:
- erstellt die Projekt-Startdateien unter `.agent/project/` aus Templates
- erstellt/ergaenzt die Root-`AGENTS.md` als zentralen Regel-Einstiegspunkt
- installiert Commit-Message-Validierung via Git-Hook (Policy-Absicherung)
- synchronisiert und verlinkt Skills fuer konsistente Agent-Faehigkeiten
- fuehrt eine Setup-Validierung aus, damit Fehlkonfigurationen frueh auffallen
- stellt einen reproduzierbaren Erstlauf-Prozess mit Freigabepunkten sicher
- trennt klar zwischen Regeln (`AGENTS.md`) und Ausfuehrung (Skills)
- ermoeglicht damit modell- und agentunabhaengige Nutzung im Team

## Kernprinzip: Regeln und Werkzeuge sind entkoppelt

Die Loesung trennt **Policy** von **Ausfuehrung**:
- `AGENTS.md` definiert, *was* der Agent tun darf/soll (Prozess, Grenzen, Prioritaeten)
- Skills definieren, *wie* bestimmte Aufgaben effizient ausgefuehrt werden

Wichtig fuer Architektur und Betrieb:
- Der **Agent-Prompt ist austauschbar**
- Das **verwendete Modell ist austauschbar**
- Der Ansatz bleibt stabil, solange die Regeln (`AGENTS.md`, Policies, Projektkontext) und die Skill-Schnittstellen konsistent bleiben

Damit ist das Setup nicht vendor- oder modellhart verdrahtet, sondern ueber Regeln, Skripte und Projektkontext reproduzierbar.

## Relevante Bausteine im Repo

- `.agent/AGENTS.md`: Core-Regeln und Pflichtablauf
- `.agent/project/AGENTS.project.md`: projektspezifische Agent-Regeln
- `.agent/project/architecture-context.md`: Architekturwissen
- `.agent/project/runbook.md`: Betriebs- und Fehlerbehebungswissen
- `.agent/policies/*.md`: Policies (z. B. Git, Commits, Security)
- `.agent/setup.sh`: initialisiert, synchronisiert und validiert das Setup
- `.agent/skill.sh`: installiert/verwaltet Skills
- `AGENTS.md` (Repo-Root): Einstieg fuer Agenten im Zielprojekt

## Zusammenspiel von `AGENTS.md` und Skills

### 1) Steuerung ueber `AGENTS.md`

`AGENTS.md` gibt dem Agenten einen stabilen Arbeitsrahmen:
- Prioritaet von Nutzerauftrag, Security/Compliance, Projektregeln, Core-Regeln
- Erstlauf-Pflichtschritte (Projektkontext befuellen, Freigabe einholen, Feedback einarbeiten)
- Abschlusskriterien, damit unvollstaendige Setups nicht als "fertig" gelten

### 2) Ausfuehrung ueber Skills

Skills kapseln wiederkehrende Muster (z. B. Analyse, Setup, Generatoren) und werden mit `.agent/skill.sh` verwaltet:

```bash
.agent/skill.sh sources
.agent/skill.sh find <query>
.agent/skill.sh add <ref>
.agent/skill.sh remove <name>
.agent/skill.sh check
.agent/skill.sh update
```

Damit bleibt `AGENTS.md` schlank (Regeln), waehrend Skills operatives Know-how versionierbar liefern.

## Risiken bei AGENTS-Nutzung

Wenn `AGENTS.md` zu breit, zu unscharf oder ohne Review gepflegt wird, entstehen reale Risiken (Sicherheitsluecken, ungewollte Aenderungen, Compliance-Verletzungen).

### Absolute DONTs

- **DONT:** Secrets, Tokens, Passwoerter oder personenbezogene Daten in `AGENTS.md`, Skills, Logs oder Beispielen speichern.
- **DONT:** Ungepruefte destructive Befehle erlauben (z. B. pauschales `rm -rf`, `git reset --hard`).
- **DONT:** Regeln ohne Team-Review direkt auf produktionsnahe Workflows anwenden.
- **DONT:** Unklare Prioritaeten definieren (Fuehrt zu widerspruechlichem Agentverhalten).
- **DONT:** Erstlauf ohne explizite Freigabe als abgeschlossen behandeln.
- **DONT:** Skills aus unbekannten Quellen unvalidiert installieren.
- **NEVER EVER:** Agenten in Produktion ausfuehren.
- **NEVER EVER:** Agenten mit Vollzugriff auf Projekt-Repository ausfuehren.

## Setup

### Voraussetzungen

- Lokales Repository ist ausgecheckt
- Shell-Zugriff im Projektroot
- Node/NPM verfuegbar (fuer Skill-Management)

### Schritt 1: Agent-Framework initialisieren

```bash
cd <pfad-zum-zielprojekt>
.agent/setup.sh
```

Das Setup erledigt typischerweise:
1. Projektdateien aus Templates erzeugen
2. Root-`AGENTS.md` erstellen/ergaenzen
3. Commit-Policy-Hook installieren
4. Skills synchronisieren/verlinken/vorschlaege
5. Setup validieren

### Schritt 2: Agent Erstlauf starten
1. Projekt im Codex GUI einbinden
2. Folgenden Befehl im Chat-Fenster eintragen, damit der Agent das Projekt analysiert und alles vorbereitet, dabei den Anweisungen/Fragen folgen:
   *Befehl* -> `Lese die AGENTS.md ein und befolge die Anweisungen!`

### Schritt 3: Skills pruefen und erweitern

```bash
.agent/skill.sh sources
.agent/skill.sh find laravel
.agent/skill.sh add <ref>
.agent/skill.sh check
```

### Schritt 4: Erstlauf fachlich abschliessen

1. `.agent/project/AGENTS.project.md` befuellen
2. `.agent/project/architecture-context.md` befuellen
3. `.agent/project/runbook.md` befuellen
4. Freigabe durch Projektverantwortliche einholen
5. Feedback einarbeiten
6. Installierte Skills einmal listen und auf `.agent/skill.sh` fuer weitere Skills hinweisen

## Kurzfazit

Mit diesem Repo entsteht ein modellunabhaengiger Agent-Betriebsrahmen:
Regeln in `AGENTS.md`, Ausfuehrungswissen in Skills, projektspezifischer Kontext in `.agent/project/*`. Genau diese Trennung macht den Agenten und das darunterliegende Modell jederzeit austauschbar, ohne die Arbeitsweise des Teams neu zu erfinden.
