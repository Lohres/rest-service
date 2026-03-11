#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AGENT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SKILLS_CMD=(npx skills)

need_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || {
    echo "[ERR ] missing command: $cmd" >&2
    exit 1
  }
}

need_cmd npx

has_yes=0
for arg in "$@"; do
  if [[ "$arg" == "--yes" || "$arg" == "-y" ]]; then
    has_yes=1
    break
  fi
done

if (( has_yes == 1 )); then
  (cd "$SCRIPT_DIR" && "${SKILLS_CMD[@]}" "$@")
else
  (cd "$SCRIPT_DIR" && "${SKILLS_CMD[@]}" "$@" --yes)
fi
