#!/bin/bash

# Script to create FullInstall.zip for MyClub
# Does not include the app/models/database/migrators folder (new installation)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MYCLUB_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
WEBSITE_DIR="$MYCLUB_DIR/WebSite"
OUTPUT_ZIP="$MYCLUB_DIR/dev/FullInstall.zip"

if [ ! -d "$WEBSITE_DIR" ]; then
    echo "Error: folder $WEBSITE_DIR does not exist"
    exit 1
fi

[ ! -d "$WEBSITE_DIR/app" ] && echo "Warning: folder $WEBSITE_DIR/app does not exist"
[ ! -d "$WEBSITE_DIR/vendor" ] && echo "Warning: folder $WEBSITE_DIR/vendor does not exist"

if [ -f "$OUTPUT_ZIP" ]; then
    echo "Removing old archive..."
    rm "$OUTPUT_ZIP"
fi

echo "Creating FullInstall.zip..."

cd "$WEBSITE_DIR" || exit 1

# Root files (excluding composer.json / composer.lock)
find . -maxdepth 1 -type f \
    ! -name "composer.json" \
    ! -name "composer.lock" \
    -print | zip "$OUTPUT_ZIP" -@

# app + vendor, excluding migrators and system files
zip -r "$OUTPUT_ZIP" app vendor \
    -x "*/.DS_Store" \
       "*/Thumbs.db" \
       "app/models/database/migrators/*" \
       "app/models/database/migrators"

# Empty var directory structure
if [ -d "var" ]; then
    find var -type d -print | zip "$OUTPUT_ZIP" -@
fi

# businessCard.html file
if [ -f "data/statics/html/businessCard.html" ]; then
    zip "$OUTPUT_ZIP" "data/statics/html/businessCard.html"
fi

if [ $? -eq 0 ]; then
    echo "Archive successfully created: $OUTPUT_ZIP"
    echo "Size: $(du -h "$OUTPUT_ZIP" | cut -f1)"
else
    echo "Error during archive creation"
    exit 1
fi