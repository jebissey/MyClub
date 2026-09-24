#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEBSITE_DIR="$SCRIPT_DIR/../../WebSite"
OUTPUT_FILE="$SCRIPT_DIR/LinesByFiles.txt"

(
  echo "=== Details by file ===";
  find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \( -name "*.php" -o -name "*.latte" -o -name "*.js" \) -type f -exec wc -l {} \; | grep -v total | sort -k2;

  echo -e "\n=== Total by file type ===";

  echo "PHP files:";
  find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o -name "*.php" -type f -exec wc -l {} + | awk 'END {print $1}';

  echo "Latte files:";
  find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o -name "*.latte" -type f -exec wc -l {} + | awk 'END {print $1}';

  echo "JavaScript files:";
  find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o -name "*.js" -type f -exec wc -l {} + | awk 'END {print $1}';

  echo -e "\n=== Grand total ===";
  find "$WEBSITE_DIR" -path "*/vendor" -prune -o -path "*/var" -prune -o \( -name "*.php" -o -name "*.latte" -o -name "*.js" \) -type f -exec wc -l {} + | tail -1 | sed 's/total/Total all files/';
) > "$OUTPUT_FILE"