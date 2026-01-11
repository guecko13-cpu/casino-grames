#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${1:-http://localhost}

paths=(
  "/"
  "/login"
  "/register"
  "/install"
  "/api/health"
)

for path in "${paths[@]}"; do
  url="${BASE_URL}${path}"
  status=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  if [[ "$status" != "200" ]]; then
    echo "[FAIL] $url -> $status"
    exit 1
  fi
  echo "[OK] $url -> $status"
done

echo "Smoke tests passed."
