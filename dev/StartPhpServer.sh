#!/bin/bash

# Development server startup script

# Colors for messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Determine script path
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WEBSITE_DIR="$PROJECT_ROOT/WebSite"

echo -e "${GREEN}🚀 Starting development server${NC}"

# Check that WebSite folder exists
if [ ! -d "$WEBSITE_DIR" ]; then
    echo -e "${RED}❌ Error: WebSite folder does not exist at $WEBSITE_DIR${NC}"
    exit 1
fi

# Move to WebSite folder
cd "$WEBSITE_DIR" || exit 1
echo -e "${YELLOW}📂 Working directory: $WEBSITE_DIR${NC}"

# Clear Latte cache
echo -e "${YELLOW}🧹 Clearing Latte cache...${NC}"
if [ -d "var/latte/temp" ]; then
    rm -rf var/latte/temp/*
    echo -e "${GREEN}✓ Latte cache cleared${NC}"
else
    echo -e "${YELLOW}⚠ var/latte/temp folder does not exist${NC}"
fi

# Clear Tracy logs
echo -e "${YELLOW}🧹 Clearing Tracy logs...${NC}"
if [ -d "var/tracy/log" ]; then
    rm -rf var/tracy/log/*
    echo -e "${GREEN}✓ Tracy logs cleared${NC}"
else
    echo -e "${YELLOW}⚠ var/tracy/log folder does not exist${NC}"
fi

# Configure permissions for SQLite
echo -e "${YELLOW}🔒 Configuring SQLite permissions...${NC}"

# Create data folder if it doesn't exist
mkdir -p data

# Take ownership of data files (in case nginx created them)
if ls data/*.sqlite 1> /dev/null 2>&1; then
    sudo chown $USER:www-data data/*.sqlite 2>/dev/null || true
fi

# Permissions on data folder (rwxrwxr-x with setgid)
chmod 2775 data 2>/dev/null
echo -e "${GREEN}✓ Permissions on data/ configured${NC}"

# Permissions on SQLite files if they exist
if ls data/*.sqlite 1> /dev/null 2>&1; then
    chmod 664 data/*.sqlite 2>/dev/null
    echo -e "${GREEN}✓ Permissions on .sqlite files configured${NC}"
fi

# Create backup folder if it doesn't exist
mkdir -p backup
chmod 2775 backup 2>/dev/null

# Create var/ structure if necessary
mkdir -p var/latte/temp
mkdir -p var/tracy/log

# Take ownership of var files (in case nginx created them)
sudo chown -R $USER:www-data var 2>/dev/null || true

chmod -R 2775 var 2>/dev/null
echo -e "${GREEN}✓ var/ structure configured${NC}"

# Start PHP server
echo -e "${GREEN}🌐 Starting PHP server on localhost:8000${NC}"
echo -e "${YELLOW}📝 Router: ../dev/router.php${NC}"
echo -e "${YELLOW}🛑 Press Ctrl+C to stop the server${NC}"
echo ""

php -S localhost:8000 ../dev/router.php