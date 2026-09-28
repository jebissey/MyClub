#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEBSITE_DIR="$SCRIPT_DIR/../../WebSite"
DEV_DIR="$SCRIPT_DIR/.."

OUTPUT_WEBSITE="$SCRIPT_DIR/LinesByFilesInWebSite.txt"
OUTPUT_DEV="$SCRIPT_DIR/LinesByFileInDev.txt"

# ---------- WebSite ----------
(
echo "=== Details by file ==="
find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  \( -name "*.php" -o -name "*.latte" -o -name "*.js" \) -type f -exec wc -l {} \; \
  | grep -v total | sort -k2

echo -e "\n=== Total by file type ==="

echo "PHP files:"
find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.php" -type f -exec wc -l {} + | awk 'END {print $1}'

echo "Latte files:"
find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.latte" -type f -exec wc -l {} + | awk 'END {print $1}'

echo "JavaScript files:"
find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.js" -type f -exec wc -l {} + | awk 'END {print $1}'

echo -e "\n=== Grand total ==="
find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  \( -name "*.php" -o -name "*.latte" -o -name "*.js" \) -type f -exec wc -l {} + \
  | tail -1 | sed 's/total/Total all files/'
) > "$OUTPUT_WEBSITE"

# ---------- Dev ----------
(
echo "=== Details by file ==="
find "$DEV_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  \( -name "*.php" -o -name "*.latte" -o -name "*.js" -o -name "*.ts" \) -type f -exec wc -l {} \; \
  | grep -v total | sort -k2

echo -e "\n=== Total by file type ==="

echo "PHP files:"
find "$DEV_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.php" -type f -exec wc -l {} + | awk 'END {print $1}'

echo "Latte files:"
find "$DEV_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.latte" -type f -exec wc -l {} + | awk 'END {print $1}'

echo "JavaScript files:"
find "$DEV_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.js" -type f -exec wc -l {} + | awk 'END {print $1}'

echo "TypeScript files:"
find "$DEV_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  -name "*.ts" -type f -exec wc -l {} + | awk 'END {print $1}'

echo -e "\n=== Grand total ==="
find "$DEV_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \
  \( -name "*.php" -o -name "*.latte" -o -name "*.js" -o -name "*.ts" \) -type f -exec wc -l {} + \
  | tail -1 | sed 's/total/Total all files/'
) > "$OUTPUT_DEV"

echo "Rapports générés :"
echo "  - $OUTPUT_WEBSITE"
echo "  - $OUTPUT_DEV"