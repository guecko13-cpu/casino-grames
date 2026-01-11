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
)

for path in "${!expected[@]}"; do
  url="${BASE_URL}${path}"
  status=$(curl -s -o /dev/null -w "%{http_code}" -I "$url")
  if [[ "$status" != "${expected[$path]}" ]]; then
    echo "[FAIL] $url -> $status (expected ${expected[$path]})"
    exit 1
  fi
  echo "[OK] $url -> $status"
done

echo "Smoke tests passed."
