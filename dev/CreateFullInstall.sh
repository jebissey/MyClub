#!/bin/bash
# Script to create FullInstall.zip for MyClub
# Includes:
#   - All files in WebSite root (including .htaccess)
#   - Folders WebSite/app and WebSite/vendor with all contents
#   - WebSite/var folder structure only (no files)
# Excludes:
#   - Hidden system folders (.git, .DS_Store, etc.)

# Define paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MYCLUB_DIR="$(dirname "$SCRIPT_DIR")"
WEBSITE_DIR="$MYCLUB_DIR/WebSite"
OUTPUT_ZIP="$MYCLUB_DIR/dev/FullInstall.zip"

# Check required directories
if [ ! -d "$WEBSITE_DIR" ]; then
    echo "Error: folder $WEBSITE_DIR does not exist"
    exit 1
fi

# Warnings for optional folders
[ ! -d "$WEBSITE_DIR/app" ] && echo "Warning: folder $WEBSITE_DIR/app does not exist"
[ ! -d "$WEBSITE_DIR/vendor" ] && echo "Warning: folder $WEBSITE_DIR/vendor does not exist"

# Remove old archive
if [ -f "$OUTPUT_ZIP" ]; then
    echo "Removing old archive..."
    rm "$OUTPUT_ZIP"
fi

echo "Creating FullInstall.zip..."

cd "$WEBSITE_DIR" || exit 1

# 1️⃣ Add root files (including .htaccess)
find . -maxdepth 1 -type f ! -name ".DS_Store" ! -name "*.tmp" -print | zip "$OUTPUT_ZIP" -@

# 2️⃣ Add app and vendor folders with all contents
zip -r "$OUTPUT_ZIP" app vendor -x "*/.git*" "*/.DS_Store" "*/Thumbs.db"

# 3️⃣ Add var folder structure only (no files)
if [ -d "var" ]; then
    find var -type d -print | zip "$OUTPUT_ZIP" -@
fi

# ✅ Summary
if [ $? -eq 0 ]; then
    echo "Archive successfully created: $OUTPUT_ZIP"
    echo "Size: $(du -h "$OUTPUT_ZIP" | cut -f1)"
else
    echo "Error during archive creation"
    exit 1
fi

