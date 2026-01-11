#!/usr/bin/env bash
set -euo pipefail

if [[ -f package.json ]]; then
  if command -v npm >/dev/null 2>&1; then
    if rg -q "\"eslint\"" package.json; then
      npm run -s lint
    fi
    if rg -q "\"prettier\"" package.json; then
      npm run -s format:check || npm run -s prettier:check || true
    fi
  fi
fi

if [[ -x tools/lint_php.sh ]]; then
  ./tools/lint_php.sh
fi
