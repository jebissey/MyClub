#!/bin/bash
# exportDB.sh - Export a SQLite database to a .sql file using the sqlite3 CLI
# Usage: ./exportDB.sh [file.sqlite]
# Default: tests.sqlite -> tests.sqlite.sql

DB_FILE="${1:-tests.sqlite}"
OUT_FILE="${DB_FILE}.sql"

if [ ! -f "$DB_FILE" ]; then
    echo "Error: file '$DB_FILE' not found."
    exit 1
fi

    sqlite3 "$DB_FILE" .dump \
        | sed -E 's/^INSERT INTO ([A-Za-z_][A-Za-z0-9_]*) VALUES/INSERT INTO "\1" VALUES/' \
        > "$OUT_FILE"

if [ $? -eq 0 ]; then
    echo "Export successful: $OUT_FILE"
else
    echo "Error during export."
    exit 1
fi