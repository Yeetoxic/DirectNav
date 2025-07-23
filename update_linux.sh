#!/bin/bash
set -e
shopt -s globstar nullglob

TMP_DIR="update_tmp"
ZIP_FILE="main.zip"
ZIP_URL="https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip"
LOG_FILE="update_log.txt"
SELF="$(realpath "$0")"
DIR="$(dirname "$SELF")"
NEW_UPDATER="$DIR/update_new.sh"
POST_SCRIPT="$DIR/run_after_update.sh"

echo "=== DirectNav Auto-Updater ==="
cd "$DIR"

# Cleanup previous run
rm -rf "$TMP_DIR" "$ZIP_FILE" "$LOG_FILE" "$NEW_UPDATER" "$POST_SCRIPT"

# Shut down Docker
echo "Stopping Docker containers..."
docker compose down || true

# Download and extract
echo "Downloading latest DirectNav ZIP..."
curl -L "$ZIP_URL" -o "$ZIP_FILE"

echo "Extracting ZIP..."
unzip -qq "$ZIP_FILE" -d "$TMP_DIR"

SRC="$TMP_DIR/DirectNav-main"
echo "Update Summary:" > "$LOG_FILE"
echo "------------------------------" >> "$LOG_FILE"

# Safe file copy function
copy_file() {
    local src="$1"
    local dst="$2"
    if [ ! -f "$src" ]; then return; fi

    if [ -f "$dst" ]; then
        if ! cmp -s "$src" "$dst"; then
            cp "$src" "$dst"
            echo "[REPLACED] $dst" >> "$LOG_FILE"
        fi
    else
        mkdir -p "$(dirname "$dst")"
        cp "$src" "$dst"
        echo "[ADDED] $dst" >> "$LOG_FILE"
    fi
}

# Replace safe root-level files
for file in README.md docker-compose.yml setup_linux.sh setup_windows.bat update_windows.bat; do
    copy_file "$SRC/$file" "$DIR/$file"
done

# Save updater for delayed replacement
cp "$SRC/update_linux.sh" "$NEW_UPDATER"
echo "[REPLACED] update_linux.sh" >> "$LOG_FILE"

# Copy docker/
echo "Updating docker/..."
for file in "$SRC/docker"/**/*; do
    [ -f "$file" ] || continue
    rel="${file#$SRC/}"
    copy_file "$file" "$DIR/$rel"
done

# Copy app/index.php and app/zDirectNav/
echo "Updating app/zDirectNav/..."
copy_file "$SRC/app/index.php" "$DIR/app/index.php"

for file in "$SRC/app/zDirectNav"/**/*; do
    [ -f "$file" ] || continue
    rel="${file#$SRC/}"
    copy_file "$file" "$DIR/$rel"
done

# Show summary
echo "------------------------------"
cat "$LOG_FILE"
echo "------------------------------"
echo
read -rp "Press ENTER to run setup and finalize update..."

# Write the post-run shell script
cat > "$POST_SCRIPT" <<EOF
#!/bin/bash
sleep 1
bash "$DIR/setup_linux.sh" &
sleep 2
if [ -f "$NEW_UPDATER" ]; then
    mv "$NEW_UPDATER" "$SELF"
fi
rm -rf "$DIR/$TMP_DIR" "$DIR/$ZIP_FILE" "$DIR/$POST_SCRIPT"
EOF

chmod +x "$POST_SCRIPT"
"$POST_SCRIPT" &
exit 0
