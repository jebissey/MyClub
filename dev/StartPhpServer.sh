#!/bin/bash

# Development server startup script

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WEBSITE_DIR="$PROJECT_ROOT/WebSite"

echo -e "${GREEN}🚀 Starting development server${NC}"

if [ ! -d "$WEBSITE_DIR" ]; then
    echo -e "${RED}❌ Error: WebSite folder does not exist at $WEBSITE_DIR${NC}"
    exit 1
fi

cd "$WEBSITE_DIR" || exit 1
echo -e "${YELLOW}📂 Working directory: $WEBSITE_DIR${NC}"

# ------------------------------------------------------------------
# Clear caches
# ------------------------------------------------------------------
echo -e "${YELLOW}🧹 Clearing Latte cache...${NC}"
rm -rf var/latte/temp/* 2>/dev/null && echo -e "${GREEN}✓ Latte cache cleared${NC}" || echo -e "${YELLOW}⚠ var/latte/temp missing${NC}"

echo -e "${YELLOW}🧹 Clearing Tracy logs...${NC}"
rm -rf var/tracy/log/* 2>/dev/null && echo -e "${GREEN}✓ Tracy logs cleared${NC}" || echo -e "${YELLOW}⚠ var/tracy/log missing${NC}"

# ------------------------------------------------------------------
# Create folders
# ------------------------------------------------------------------
mkdir -p data backup var/latte/temp var/tracy/log var/tracy/sessions app/images

# ------------------------------------------------------------------
# Fix ownership & permissions for SQLite (critical part)
# ------------------------------------------------------------------
echo -e "${YELLOW}🔒 Configuring SQLite permissions...${NC}"

# Take ownership (in case nginx created the files)
sudo chown -R "$USER:www-data" data backup var app/images 2>/dev/null || true

# Directories: rwxrwxr-x + setgid
chmod 2775 data backup
find var -type d -exec chmod 2775 {} \; 2>/dev/null
chmod 2775 app/images 2>/dev/null

# Files inside data/ (including both .sqlite)
if ls data/*.sqlite 1> /dev/null 2>&1; then
    chmod 664 data/*.sqlite
    # Extra safety: make sure the current user can write
    chown "$USER:www-data" data/*.sqlite 2>/dev/null || true
fi

# Also fix any journal / wal files that might exist
chmod 664 data/*.sqlite-* 2>/dev/null || true

echo -e "${GREEN}✓ Permissions on data/ and SQLite files configured${NC}"

# ------------------------------------------------------------------
# Final check
# ------------------------------------------------------------------
echo -e "${YELLOW}🔍 Checking write access...${NC}"
for db in data/MyClub.sqlite data/LogMyClub.sqlite; do
    if [ -f "$db" ]; then
        if [ -w "$db" ]; then
            echo -e "  ${GREEN}✓ $db is writable${NC}"
        else
            echo -e "  ${RED}✗ $db is NOT writable !${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠ $db does not exist yet (will be created on first request)${NC}"
    fi
done

# ------------------------------------------------------------------
# Start PHP built-in server
# ------------------------------------------------------------------
echo -e "${GREEN}🌐 Starting PHP server on localhost:8000${NC}"
echo -e "${YELLOW}📝 Router: ../dev/router.php${NC}"
echo -e "${YELLOW}🛑 Press Ctrl+C to stop the server${NC}"
echo ""

php -S localhost:8000 ../dev/router.php