#!/bin/bash
# exportDBs.sh - Git-friendly export of SQLite databases

set -euo pipefail

DBS=(
    "MyClub.sqlite"
    "LogMyClub.sqlite"
)

for DB_FILE in "${DBS[@]}"; do
    if [ ! -f "$DB_FILE" ]; then
        echo "Warning: '$DB_FILE' not found, skipped."
        continue
    fi

    OUT_FILE="${DB_FILE}.sql"

    echo "Exporting $DB_FILE..."

    {
        echo "PRAGMA foreign_keys=OFF;"
        echo "BEGIN TRANSACTION;"
        echo

        #######################################################################
        # Tables
        #######################################################################
        sqlite3 "$DB_FILE" "
            SELECT sql || ';'
            FROM sqlite_schema
            WHERE type='table'
              AND name NOT LIKE 'sqlite_%'
            ORDER BY name;
        "

        echo

        #######################################################################
        # Data
        #######################################################################
        while IFS= read -r TABLE; do
            echo "-- $TABLE"
            sqlite3 "$DB_FILE" ".mode insert \"$TABLE\"" \
                               "SELECT * FROM \"$TABLE\";"
            echo
        done < <(
            sqlite3 "$DB_FILE" "
                SELECT name
                FROM sqlite_schema
                WHERE type='table'
                  AND name NOT LIKE 'sqlite_%'
                ORDER BY name;
            "
        )

        #######################################################################
        # Indexes
        #######################################################################
        sqlite3 "$DB_FILE" "
            SELECT sql || ';'
            FROM sqlite_schema
            WHERE type='index'
              AND sql IS NOT NULL
            ORDER BY name;
        "

        echo

        #######################################################################
        # Triggers
        #######################################################################
        sqlite3 "$DB_FILE" "
            SELECT sql || ';'
            FROM sqlite_schema
            WHERE type='trigger'
            ORDER BY name;
        "

        echo

        #######################################################################
        # Views
        #######################################################################
        sqlite3 "$DB_FILE" "
            SELECT sql || ';'
            FROM sqlite_schema
            WHERE type='view'
            ORDER BY name;
        "

        echo
        echo "COMMIT;"
    } > "$OUT_FILE"

    echo "  -> $OUT_FILE"
done

echo "All exports completed successfully."