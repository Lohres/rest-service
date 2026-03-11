#!/usr/bin/env bash
set -euo pipefail

msg_file="${1:-}"
[[ -n "$msg_file" && -f "$msg_file" ]] || {
  echo "usage: $0 <commit-msg-file>" >&2
  exit 1
}

first_line="$(head -n 1 "$msg_file")"
format_regex='^(feat|fix|refactor|docs|test|chore|ci|build|perf)(\([a-z0-9_-]+\))?: (.+)$'

if [[ ! "$first_line" =~ $format_regex ]]; then
  echo "invalid commit message:" >&2
  echo "  $first_line" >&2
  echo "expected format: type(scope): summary" >&2
  echo "allowed types: feat|fix|refactor|docs|test|chore|ci|build|perf" >&2
  exit 1
fi

summary="${BASH_REMATCH[3]}"
summary_len=${#summary}

if (( summary_len > 72 )); then
  echo "message too long:" >&2
  echo "  $first_line" >&2
  echo "summary length: $summary_len (max: 72)" >&2
  echo "expected format: type(scope): summary" >&2
  exit 1
fi
