#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${1:-http://localhost}

declare -A expected=(
  ["/"]="200"
  ["/api/health"]="200"
  ["/api/auth/login"]="200"
  ["/api/credits/balance"]="200"
)

for path in "${!expected[@]}"; do
  url="${BASE_URL}${path}"
  method="GET"
  if [[ "$path" == "/api/auth/login" ]]; then
    method="POST"
  fi
  status=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$url")
  if [[ "$status" != "${expected[$path]}" ]]; then
    echo "[FAIL] $url -> $status (expected ${expected[$path]})"
    exit 1
  fi
  echo "[OK] $url -> $status"
done

echo "Smoke tests passed."
