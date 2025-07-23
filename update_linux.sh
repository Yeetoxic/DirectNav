#!/bin/bash
echo "=== Updating DirectNav from GitHub ZIP ==="
cd "$(dirname "$0")"

curl -L -o update_tmp.zip https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip
unzip -qo update_tmp.zip -d update_tmp

# === Update core files ===
echo "• Updating core files..."
cp update_tmp/DirectNav-main/README.md ./README.md
cp update_tmp/DirectNav-main/docker-compose.yml ./docker-compose.yml
cp update_tmp/DirectNav-main/update_windows.bat ./update_windows.bat
cp update_tmp/DirectNav-main/update_linux.sh ./update_linux.sh
cp update_tmp/DirectNav-main/setup_windows.bat ./setup_windows.bat
cp update_tmp/DirectNav-main/setup_linux.sh ./setup_linux.sh
cp -r update_tmp/DirectNav-main/docker/* ./docker/

# === Update app core ===
echo "• Updating /app core files..."
cp update_tmp/DirectNav-main/app/index.php ./app/index.php
cp -r update_tmp/DirectNav-main/app/zDirectNav/* ./app/zDirectNav/

# Cleanup
rm -rf update_tmp update_tmp.zip

# Rebuild Docker
docker compose down
docker compose up --build -d

echo "✅ Update complete!"
