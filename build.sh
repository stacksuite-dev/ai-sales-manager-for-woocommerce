#!/bin/bash
#
# WordPress Plugin Build Script
# Creates a distributable ZIP file for AI Sales Manager for WooCommerce
#

set -e

# Configuration
PLUGIN_SLUG="ai-sales-manager-for-woocommerce"
PLUGIN_VERSION=$(grep -m1 "Version:" ai-sales-manager-for-woocommerce.php | sed 's/.*Version: *//' | tr -d ' ')
BUILD_DIR="./build"
DIST_DIR="./dist"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  AI Sales Manager Build Script${NC}"
echo -e "${GREEN}  Version: ${PLUGIN_VERSION}${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Step 1: Clean previous builds
echo -e "${YELLOW}[1/5]${NC} Cleaning previous builds..."
rm -rf "${BUILD_DIR}"
rm -rf "${DIST_DIR}"
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"
mkdir -p "${DIST_DIR}"

# Step 2: Copy plugin files
echo -e "${YELLOW}[2/5]${NC} Copying plugin files..."

# Files and directories to include
INCLUDE_FILES=(
    "ai-sales-manager-for-woocommerce.php"
    "uninstall.php"
    "readme.txt"
    "license.txt"
    "assets"
    "includes"
    "languages"
    "templates"
)

for item in "${INCLUDE_FILES[@]}"; do
    if [ -e "$item" ]; then
        cp -r "$item" "${BUILD_DIR}/${PLUGIN_SLUG}/"
        echo "  ✓ $item"
    else
        echo -e "  ${YELLOW}⚠ $item (not found, skipping)${NC}"
    fi
done

# Step 3: Remove development files from build
echo -e "${YELLOW}[3/5]${NC} Removing development files..."

# Patterns to exclude from the build
find "${BUILD_DIR}" -name "*.map" -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name "*.scss" -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name "*.less" -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name ".DS_Store" -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name "Thumbs.db" -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name ".gitkeep" -type f -delete 2>/dev/null || true
find "${BUILD_DIR}" -name ".gitignore" -type f -delete 2>/dev/null || true

echo "  ✓ Removed source maps, SCSS, and OS files"

# Step 4: Validate build
echo -e "${YELLOW}[4/5]${NC} Validating build..."

# Check main plugin file exists
if [ ! -f "${BUILD_DIR}/${PLUGIN_SLUG}/ai-sales-manager-for-woocommerce.php" ]; then
    echo -e "${RED}ERROR: Main plugin file missing!${NC}"
    exit 1
fi

# Check for PHP syntax errors
PHP_ERRORS=0
while IFS= read -r -d '' file; do
    if ! php -l "$file" > /dev/null 2>&1; then
        echo -e "${RED}  ✗ PHP syntax error in: $file${NC}"
        PHP_ERRORS=$((PHP_ERRORS + 1))
    fi
done < <(find "${BUILD_DIR}" -name "*.php" -print0)

if [ $PHP_ERRORS -gt 0 ]; then
    echo -e "${RED}ERROR: Found $PHP_ERRORS PHP syntax errors!${NC}"
    exit 1
fi

echo "  ✓ All PHP files valid"

# Check readme.txt exists
if [ ! -f "${BUILD_DIR}/${PLUGIN_SLUG}/readme.txt" ]; then
    echo -e "${YELLOW}  ⚠ readme.txt missing (required for WordPress.org)${NC}"
else
    echo "  ✓ readme.txt present"
fi

# Check language file exists
if [ ! -f "${BUILD_DIR}/${PLUGIN_SLUG}/languages/${PLUGIN_SLUG}.pot" ]; then
    echo -e "${YELLOW}  ⚠ POT file missing (recommended for i18n)${NC}"
else
    echo "  ✓ Language template present"
fi

# Step 5: Create ZIP file
echo -e "${YELLOW}[5/5]${NC} Creating distribution ZIP..."

cd "${BUILD_DIR}"
zip -r "../${DIST_DIR}/${PLUGIN_SLUG}-${PLUGIN_VERSION}.zip" "${PLUGIN_SLUG}" -x "*.git*"
cd ..

# Calculate ZIP size
ZIP_SIZE=$(du -h "${DIST_DIR}/${PLUGIN_SLUG}-${PLUGIN_VERSION}.zip" | cut -f1)

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Build Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "  Plugin:  ${PLUGIN_SLUG}"
echo -e "  Version: ${PLUGIN_VERSION}"
echo -e "  Output:  ${DIST_DIR}/${PLUGIN_SLUG}-${PLUGIN_VERSION}.zip"
echo -e "  Size:    ${ZIP_SIZE}"
echo ""

# List contents
echo "ZIP Contents:"
unzip -l "${DIST_DIR}/${PLUGIN_SLUG}-${PLUGIN_VERSION}.zip" | tail -n +4 | head -n -2 | awk '{print "  " $4}'

echo ""
echo -e "${GREEN}Ready for distribution!${NC}"
