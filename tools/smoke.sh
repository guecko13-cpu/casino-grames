#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${1:-http://localhost}

declare -A expected=(
  ["/"]="200"
  ["/login"]="200"
  ["/register"]="200"
  ["/install"]="200"
  ["/about"]="200"
  ["/api/health"]="200"
  ["/api/wallet"]="401"
  ["/api/history"]="401"
  ["/api/bonus/daily"]="401"
  ["/lobby"]="200"
  ["/profile"]="200"
  ["/wallet"]="200"
  ["/history"]="200"
  ["/admin"]="200"
)

for path in "${!expected[@]}"; do
  url="${BASE_URL}${path}"
  method="GET"
  if [[ "$path" == "/api/bonus/daily" ]]; then
    method="POST"
  fi
  status=$(curl -s -o /dev/null -w "%{http_code}" -I -X "$method" "$url")
  if [[ "$status" != "${expected[$path]}" ]]; then
    echo "[FAIL] $url -> $status (expected ${expected[$path]})"
    exit 1
  fi
  echo "[OK] $url -> $status"
done

echo "Smoke tests passed."
