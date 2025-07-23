#!/bin/bash

set -e

echo "=== Updating DirectNav from GitHub ZIP ==="

TMP_DIR="update_tmp"
ZIP_URL="https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip"
ZIP_FILE="main.zip"

rm -rf "$TMP_DIR" "$ZIP_FILE"
mkdir -p "$TMP_DIR"

# Download the latest version
curl -L "$ZIP_URL" -o "$ZIP_FILE"

# Extract it
unzip "$ZIP_FILE" -d "$TMP_DIR"

# Copy updated files, excluding /app contents
cp -r "$TMP_DIR"/DirectNav-main/docker/* ./docker/
cp "$TMP_DIR"/DirectNav-main/docker-compose.yml ./
cp "$TMP_DIR"/DirectNav-main/README.md ./
cp "$TMP_DIR"/DirectNav-main/update_linux.sh ./
cp "$TMP_DIR"/DirectNav-main/update_windows.bat ./

# Safely update zDirectNav only (contents)
mkdir -p ./app/zDirectNav
cp -r "$TMP_DIR"/DirectNav-main/app/zDirectNav/* ./app/zDirectNav/

# Clean up
rm -rf "$TMP_DIR" "$ZIP_FILE"

# Rebuild docker
echo "=== Rebuilding Docker containers ==="
docker compose down && docker compose up --build -d

echo "✓ Update complete"
