#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

fail() {
  echo "ERROR: $1" >&2
  exit 1
}

required_files=(
  "$ROOT_DIR/.agent/AGENTS.md"
  "$ROOT_DIR/AGENTS.md"
  "$ROOT_DIR/.agent/policies/git.md"
  "$ROOT_DIR/.agent/policies/commits.md"
  "$ROOT_DIR/.agent/policies/code-review.md"
  "$ROOT_DIR/.agent/policies/security.md"
  "$ROOT_DIR/.agent/scripts/init-agent.sh"
  "$ROOT_DIR/.agent/scripts/check-commit-msg.sh"
)

for f in "${required_files[@]}"; do
  [[ -f "$f" ]] || fail "missing required file: $f"
done

resolve_skill_file() {
  local skill_name="$1"
  local candidates=(
    "$ROOT_DIR/.agent/skills/$skill_name/SKILL.md"
    "$ROOT_DIR/.agent/skills/.custom/$skill_name/SKILL.md"
    "$ROOT_DIR/.agent/skills/.shared/$skill_name/SKILL.md"
  )
  local c
  for c in "${candidates[@]}"; do
    if [[ -f "$c" ]]; then
      printf '%s\n' "$c"
      return 0
    fi
  done
  return 1
}

required_skill_names=(
  "coding-standards"
  "architecture-review"
)

for name in "${required_skill_names[@]}"; do
  skill="$(resolve_skill_file "$name")" || fail "missing skill file for: $name"
  [[ -f "$skill" ]] || fail "missing skill file: $skill"
  head -n 20 "$skill" | grep -q '^name:' || fail "skill missing frontmatter name: $skill"
  head -n 20 "$skill" | grep -q '^description:' || fail "skill missing frontmatter description: $skill"
done

echo "validation successful"
