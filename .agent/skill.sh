#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="$(basename "$0")"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AGENT_DIR="$SCRIPT_DIR"
INSTALL_SCRIPT="$AGENT_DIR/skills/install.sh"
TARGET_ROOT="$AGENT_DIR"
if [[ "$(basename "$AGENT_DIR")" == ".agent" && -d "$(dirname "$AGENT_DIR")/.git" ]]; then
  TARGET_ROOT="$(dirname "$AGENT_DIR")"
fi

usage() {
  cat <<USAGE
${SCRIPT_NAME} - Wrapper for the skills.sh CLI (https://skills.sh)

Usage:
  ./${SCRIPT_NAME} <skills-command> [args...]

Shortcuts:
  help                 Show this help and skills.sh links
  sources              Show common skills pages
  find <query>         Alias for: npx skills find <query>
  add <ref>            Alias for: npx skills add <ref>
  remove [skills]      Alias for: npx skills remove [skills]
  check [path]         Alias for: npx skills check [path]
  update [path]        Alias for: npx skills update [path]

Examples:
  ./${SCRIPT_NAME} find laravel
  ./${SCRIPT_NAME} add openai/skills/site-reliability-engineer
  ./${SCRIPT_NAME} remove coding-standards
  ./${SCRIPT_NAME} check
  ./${SCRIPT_NAME} update
  ./${SCRIPT_NAME} list
USAGE
}

print_sources() {
  cat <<SOURCES
1) Skills.sh - https://skills.sh/
2) Skills Catalog - https://skills.sh/skills
3) OpenAI Skills (GitHub) - https://github.com/openai/skills
4) Awesome Agent Skills (GitHub) - https://github.com/heilcheng/awesome-agent-skills
5) agent-skills.md - https://agent-skills.md
SOURCES
}

need_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || {
    echo "[ERR ] missing command: $cmd" >&2
    exit 1
  }
}

run_skills() {
  [[ -x "$INSTALL_SCRIPT" ]] || {
    echo "[ERR ] missing executable: $INSTALL_SCRIPT" >&2
    exit 1
  }
  "$INSTALL_SCRIPT" "$@"

  # Always refresh shared skills and root symlink on any invocation.
  sync_skills
}

sync_skills() {
  local link_path="$TARGET_ROOT/skills"
  local link_target=".agent/skills/.shared"
  local shared_dir="$AGENT_DIR/skills/.shared"
  local src_agents="$AGENT_DIR/skills/.agents/skills"
  local src_custom="$AGENT_DIR/skills/.custom"
  local src_custom_nested="$AGENT_DIR/skills/.custom/skills"

  need_cmd rsync
  mkdir -p "$shared_dir"

  sync_skill_dir() {
    local src_base="$1"
    local src
    local name

    [[ -d "$src_base" ]] || return 0

    for src in "$src_base"/*; do
      [[ -d "$src" ]] || continue
      name="$(basename "$src")"
      [[ "$name" == .* ]] && continue
      [[ -f "$src/SKILL.md" ]] || continue
      local dst="$shared_dir/$name"
      local existed=0
      [[ -d "$dst" ]] && existed=1
      mkdir -p "$dst"
      if (( existed == 1 )); then
        rsync -a --ignore-existing "$src/" "$dst/"
      else
        rsync -a "$src/" "$dst/"
      fi
    done
  }

  sync_skill_dir "$src_agents"
  sync_skill_dir "$src_custom"
  sync_skill_dir "$src_custom_nested"

  if [[ -e "$link_path" && ! -L "$link_path" ]]; then
    echo "[WARN] skip skill symlink: $link_path exists and is not a symlink" >&2
    return 0
  fi

  ln -sfn "$link_target" "$link_path"
}

main() {
  local cmd="${1:-help}"
  shift || true
  # Always refresh shared skills and root symlink on any invocation.
  sync_skills

  case "$cmd" in
    help|-h|--help)
      usage
      echo
      print_sources
      ;;
    sources)
      print_sources
      ;;
    find)
      run_skills find "$@"
      ;;
    add)
      run_skills add "$@"
      ;;
    remove|rm)
      run_skills remove "$@"
      ;;
    check)
      run_skills check "$@"
      ;;
    update)
      run_skills update "$@"
      ;;
    *)
      # Pass-through mode for all native skills CLI commands.
      run_skills "$cmd" "$@"
      ;;
  esac
}

main "$@"
