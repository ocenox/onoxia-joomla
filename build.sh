#!/usr/bin/env bash
#
# Build script for ONOXIA Joomla Package
# Creates: plg_system_onoxia.zip, com_onoxia.zip, pkg_onoxia.zip
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$SCRIPT_DIR/plugins/system/onoxia"
COMPONENT_DIR="$SCRIPT_DIR/components/com_onoxia"
LANG_DIR="$SCRIPT_DIR/language"
DIST_DIR="$SCRIPT_DIR/dist"

# Read version from plugin manifest
VERSION=$(grep -oP '<version>\K[^<]+' "$PLUGIN_DIR/onoxia.xml")
if [ -z "$VERSION" ]; then
    echo "ERROR: Could not read version from onoxia.xml" >&2
    exit 1
fi

echo "Building ONOXIA Joomla Package v${VERSION}"
echo "=========================================="

# Clean dist
rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

# 1. Build plugin ZIP
echo ""
echo "[1/3] Building plg_system_onoxia-${VERSION}.zip ..."
cd "$PLUGIN_DIR"
find . -type f \
    ! -path './.git/*' \
    ! -name '.gitkeep' \
    ! -name '.DS_Store' \
    | sort \
    | zip -X -@ "$DIST_DIR/plg_system_onoxia-${VERSION}.zip" > /dev/null
echo "      $(stat -c%s "$DIST_DIR/plg_system_onoxia-${VERSION}.zip" 2>/dev/null || stat -f%z "$DIST_DIR/plg_system_onoxia-${VERSION}.zip") bytes"

# 2. Build component ZIP
echo "[2/3] Building com_onoxia-${VERSION}.zip ..."
cd "$COMPONENT_DIR"
find . -type f \
    ! -path './.git/*' \
    ! -name '.gitkeep' \
    ! -name '.DS_Store' \
    | sort \
    | zip -X -@ "$DIST_DIR/com_onoxia-${VERSION}.zip" > /dev/null
echo "      $(stat -c%s "$DIST_DIR/com_onoxia-${VERSION}.zip" 2>/dev/null || stat -f%z "$DIST_DIR/com_onoxia-${VERSION}.zip") bytes"

# 3. Build package ZIP (bundles plugin + component + manifest + languages)
echo "[3/3] Building pkg_onoxia-${VERSION}.zip ..."
cd "$DIST_DIR"
cp "$SCRIPT_DIR/pkg_onoxia.xml" .
cp "plg_system_onoxia-${VERSION}.zip" plg_system_onoxia.zip
cp "com_onoxia-${VERSION}.zip" com_onoxia.zip
cp -r "$LANG_DIR" language
find language -type f | sort | zip -X "pkg_onoxia-${VERSION}.zip" \
    pkg_onoxia.xml \
    plg_system_onoxia.zip \
    com_onoxia.zip -@ > /dev/null
rm -f pkg_onoxia.xml plg_system_onoxia.zip com_onoxia.zip
rm -rf language
echo "      $(stat -c%s "$DIST_DIR/pkg_onoxia-${VERSION}.zip" 2>/dev/null || stat -f%z "$DIST_DIR/pkg_onoxia-${VERSION}.zip") bytes"

cd "$SCRIPT_DIR"

# Summary
echo ""
echo "Done! Files in dist/:"
ls -lh "$DIST_DIR"/*.zip
echo ""
echo "Install pkg_onoxia-${VERSION}.zip in Joomla to get both plugin + admin component."
