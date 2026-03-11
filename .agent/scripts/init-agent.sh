#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PROJECT_DIR="$ROOT_DIR/.agent/project"
TEMPLATE_DIR="$ROOT_DIR/.agent/templates"

mkdir -p "$PROJECT_DIR"

copy_if_missing() {
  local src="$1"
  local dst="$2"
  if [[ -f "$dst" ]]; then
    echo "skip: $dst exists"
  else
    cp "$src" "$dst"
    echo "created: $dst"
  fi
}

copy_if_missing "$TEMPLATE_DIR/AGENTS.project.md" "$PROJECT_DIR/AGENTS.project.md"
copy_if_missing "$TEMPLATE_DIR/architecture-context.md" "$PROJECT_DIR/architecture-context.md"
copy_if_missing "$TEMPLATE_DIR/runbook.md" "$PROJECT_DIR/runbook.md"

echo "agent init complete"
