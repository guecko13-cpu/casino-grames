#!/usr/bin/env bash
set -euo pipefail

files=$(rg --files -g "*.php")

if [[ -z "$files" ]]; then
  echo "No PHP files found."
  exit 0
fi

for file in $files; do
  php -l "$file"
done

exit 0
