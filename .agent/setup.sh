#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AGENT_DIR="$SCRIPT_DIR"
TOOLS_DIR="$AGENT_DIR/scripts"

INIT_SCRIPT="$TOOLS_DIR/init-agent.sh"
VALIDATE_SCRIPT="$TOOLS_DIR/validate-agent.sh"
COMMIT_HOOK_SCRIPT="$TOOLS_DIR/check-commit-msg.sh"
SOURCE_AGENTS_FILE="$AGENT_DIR/AGENTS.md"
STATE_DIR="$AGENT_DIR/project/.state"
ONCE_MARKER="$STATE_DIR/setup-agents-once.done"
MERGE_BEGIN="# BEGIN DIA-AGENT-SETUP (managed)"
MERGE_END="# END DIA-AGENT-SETUP (managed)"

[[ -x "$INIT_SCRIPT" ]] || { echo "missing executable: $INIT_SCRIPT" >&2; exit 1; }
[[ -x "$VALIDATE_SCRIPT" ]] || { echo "missing executable: $VALIDATE_SCRIPT" >&2; exit 1; }
[[ -x "$COMMIT_HOOK_SCRIPT" ]] || { echo "missing executable: $COMMIT_HOOK_SCRIPT" >&2; exit 1; }
[[ -f "$SOURCE_AGENTS_FILE" ]] || { echo "missing source file: $SOURCE_AGENTS_FILE" >&2; exit 1; }

TARGET_ROOT="$AGENT_DIR"
if [[ "$(basename "$AGENT_DIR")" == ".agent" && -d "$(dirname "$AGENT_DIR")/.git" ]]; then
  TARGET_ROOT="$(dirname "$AGENT_DIR")"
fi

ensure_project_agents_once() {
  local target_agents="$TARGET_ROOT/AGENTS.md"

  mkdir -p "$STATE_DIR"

  if [[ -f "$ONCE_MARKER" ]]; then
    echo "skip: AGENTS root sync already completed once"
    return 0
  fi

  if [[ ! -f "$target_agents" ]]; then
    cp "$SOURCE_AGENTS_FILE" "$target_agents"
    echo "created: $target_agents"
    touch "$ONCE_MARKER"
    return 0
  fi

  if grep -q "$MERGE_BEGIN" "$target_agents"; then
    echo "skip: managed AGENTS block already present"
    touch "$ONCE_MARKER"
    return 0
  fi

  {
    echo
    echo "$MERGE_BEGIN"
    cat "$SOURCE_AGENTS_FILE"
    echo "$MERGE_END"
  } >> "$target_agents"

  echo "updated: $target_agents (appended managed block)"
  touch "$ONCE_MARKER"
}

ensure_skills_symlink() {
  local link_path="$TARGET_ROOT/skills"
  local link_target=".agent/skills/.shared"
  local shared_dir="$AGENT_DIR/skills/.shared"
  local src_agents="$AGENT_DIR/skills/.agents/skills"
  local src_custom="$AGENT_DIR/skills/.custom"
  local src_custom_nested="$AGENT_DIR/skills/.custom/skills"

  mkdir -p "$shared_dir"

  sync_skill_dir() {
    local src_base="$1"
    local src
    local name

    [[ -d "$src_base" ]] || return 0

    for src in "$src_base"/*; do
      [[ -d "$src" ]] || continue
      name="$(basename "$src")"
      # Ignore hidden/internal folders.
      [[ "$name" == .* ]] && continue
      [[ -f "$src/SKILL.md" ]] || continue
      local dst="$shared_dir/$name"
      local existed=0
      [[ -d "$dst" ]] && existed=1
      mkdir -p "$shared_dir/$name"
      if (( existed == 1 )); then
        # Preserve local modifications in .shared and only add missing files.
        rsync -a --ignore-existing "$src/" "$dst/"
        echo "synced (preserve local changes): $src -> $dst"
      else
        rsync -a "$src/" "$dst/"
        echo "synced: $src -> $dst"
      fi
    done
  }

  sync_skill_dir "$src_agents"
  sync_skill_dir "$src_custom"
  sync_skill_dir "$src_custom_nested"

  if [[ -e "$link_path" && ! -L "$link_path" ]]; then
    echo "skip: $link_path exists and is not a symlink"
    return 0
  fi

  ln -sfn "$link_target" "$link_path"
  echo "linked: $link_path -> $link_target"
}

echo "[1/5] init project agent files"
"$INIT_SCRIPT"
chmod +x "$AGENT_DIR/skill.sh"

echo "[2/5] sync root AGENTS.md (once)"
ensure_project_agents_once

echo "[3/5] install commit-msg hook"
mkdir -p "$TARGET_ROOT/.git/hooks"
ln -sf "$COMMIT_HOOK_SCRIPT" "$TARGET_ROOT/.git/hooks/commit-msg"

echo "[4/5] link skills directory"
ensure_skills_symlink

echo "[5/5] validate setup"
"$VALIDATE_SCRIPT"

echo "done: setup completed"
