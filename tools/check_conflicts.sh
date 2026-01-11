#!/usr/bin/env bash
set -euo pipefail

if rg -n "^(<{7}|={7}|>{7})" .; then
  echo "Conflict markers detected."
  exit 1
fi

echo "No conflict markers detected."
