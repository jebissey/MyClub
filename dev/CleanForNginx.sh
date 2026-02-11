#!/bin/bash

# Script to fix permissions for nginx (www-data user)

# Colors for messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Determine script path
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WEBSITE_DIR="$PROJECT_ROOT/WebSite"

echo -e "${GREEN}🔧 Fixing permissions for nginx${NC}"

# Check that WebSite folder exists
if [ ! -d "$WEBSITE_DIR" ]; then
    echo -e "${RED}❌ Error: WebSite folder does not exist at $WEBSITE_DIR${NC}"
    exit 1
fi

# Move to WebSite folder
cd "$WEBSITE_DIR" || exit 1
echo -e "${YELLOW}📂 Working directory: $WEBSITE_DIR${NC}"

# Check if www-data group exists
if ! getent group www-data > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: www-data group does not exist${NC}"
    echo -e "${YELLOW}This script is intended for systems with nginx/apache (www-data)${NC}"
    exit 1
fi

# Create necessary folders
echo -e "${YELLOW}📁 Creating necessary folders...${NC}"
mkdir -p data
mkdir -p backup
mkdir -p var/latte/temp
mkdir -p var/tracy/log
mkdir -p var/tracy/sessions

# Clear caches
echo -e "${YELLOW}🧹 Clearing caches...${NC}"
rm -rf var/latte/temp/* 2>/dev/null
rm -rf var/tracy/log/* 2>/dev/null
echo -e "${GREEN}✓ Caches cleared${NC}"

# Fix ownership - set group to www-data
echo -e "${YELLOW}👥 Setting group ownership to www-data...${NC}"
sudo chgrp -R www-data data 2>/dev/null
sudo chgrp -R www-data backup 2>/dev/null
sudo chgrp -R www-data var 2>/dev/null
echo -e "${GREEN}✓ Group ownership set${NC}"

# Fix directory permissions (rwxrwxr-x with setgid)
echo -e "${YELLOW}🔒 Setting directory permissions...${NC}"
sudo chmod 2775 data 2>/dev/null
sudo chmod 2775 backup 2>/dev/null
sudo find var -type d -exec chmod 2775 {} \; 2>/dev/null
echo -e "${GREEN}✓ Directory permissions set (2775)${NC}"

# Fix file permissions (rw-rw-r--)
echo -e "${YELLOW}🔒 Setting file permissions...${NC}"
sudo find data -type f -exec chmod 664 {} \; 2>/dev/null
sudo find var -type f -exec chmod 664 {} \; 2>/dev/null
echo -e "${GREEN}✓ File permissions set (664)${NC}"

# Add current user to www-data group if not already member
if ! groups | grep -q www-data; then
    echo -e "${YELLOW}👤 Adding current user to www-data group...${NC}"
    sudo usermod -a -G www-data $USER
    echo -e "${GREEN}✓ User added to www-data group${NC}"
    echo -e "${YELLOW}⚠  You need to log out and log back in for group changes to take effect${NC}"
else
    echo -e "${GREEN}✓ User is already in www-data group${NC}"
fi

echo ""
echo -e "${GREEN}✅ Permissions fixed successfully!${NC}"
echo -e "${YELLOW}Summary:${NC}"
echo -e "  - Folders: ${GREEN}2775${NC} (rwxrwxr-x with setgid)"
echo -e "  - Files: ${GREEN}664${NC} (rw-rw-r--)"
echo -e "  - Group: ${GREEN}www-data${NC}"
echo ""
echo -e "${YELLOW}Now both you and nginx (www-data) can read/write to these folders${NC}"