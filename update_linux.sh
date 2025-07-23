#!/usr/bin/env bash
set -euo pipefail

# =========================
# Config
# =========================
TMP_DIR="update_tmp"
ZIP_URL="https://github.com/Yeetoxic/DirectNav/archive/refs/heads/main.zip"
ZIP_FILE="main.zip"
ROOT_DIR="DirectNav-main"
LOG_FILE="update_log.txt"
NEW_UPDATER="update_linux.sh"   # if you ever self-update this file

# -------- helper ----------
log() { echo "$@" | tee -a "$LOG_FILE" >/dev/null; }
abs_path() { readlink -f "$1"; }

# Clean last run
rm -rf "$TMP_DIR" "$ZIP_FILE"
mkdir -p "$TMP_DIR"
: > "$LOG_FILE"

echo "=== DirectNav Auto-Updater (Linux) ==="

echo "Stopping Docker containers..."
docker compose down || true

echo "Downloading latest DirectNav ZIP..."
curl -L "$ZIP_URL" -o "$ZIP_FILE"

echo "Extracting ZIP..."
unzip -q -o "$ZIP_FILE" -d "$TMP_DIR"

ROOT="$TMP_DIR/$ROOT_DIR"
if [[ ! -d "$ROOT/app" ]]; then
  echo "[ERROR] Bad ROOT path: $ROOT" | tee -a "$LOG_FILE"
  ls -la "$TMP_DIR"
  exit 1
fi

log "Update Summary:"
log "------------------------------"

# ---------------- 1) Mirror everything EXCEPT app/ ----------------
echo "Mirroring core files (excluding app/)..."
# rsync will copy everything but skip app/*
# Also skip updating this updater while it's running (optional)
rsync -a --delete \
  --exclude "app/" \
  --exclude "$NEW_UPDATER" \
  "$ROOT/" "./" >/dev/null

# If updater changed, copy it aside (optional)
if [[ -f "$ROOT/$NEW_UPDATER" ]]; then
  cp -f "$ROOT/$NEW_UPDATER" "${NEW_UPDATER}.new"
  log "[REPLACED] $NEW_UPDATER"
fi

# ---------------- 2) Copy ONLY repo app/ files ----------------
echo "Updating app/ (repo files only)..."

while IFS= read -r -d '' src; do
  rel="${src#$ROOT/}"                       # relative path under repo root
  dst="./$rel"
  dst_dir="$(dirname "$dst")"

  if [[ ! -f "$src" ]]; then
    log "[MISS] $src"
    continue
  fi

  if [[ ! -d "$dst_dir" ]]; then
    mkdir -p "$dst_dir"
  fi

  if [[ -f "$dst" ]]; then
    if cmp -s "$src" "$dst"; then
      # unchanged
      :
    else
      cp -f "$src" "$dst"
      log "[REPLACED] $rel"
    fi
  else
    cp -f "$src" "$dst"
    log "[ADDED] $rel"
  fi
done < <(find "$ROOT/app" -type f -print0)

log "------------------------------"
cat "$LOG_FILE"
log "------------------------------"

echo
read -rp "Press ENTER to run setup and finalize update..." _

# Run your setup afterwards (async)
( ./setup_linux.sh & ) >/dev/null 2>&1 || true

# Optional: if new updater exists, swap it in
if [[ -f "${NEW_UPDATER}.new" ]]; then
  mv -f "${NEW_UPDATER}.new" "$NEW_UPDATER"
fi

# Cleanup
rm -rf "$TMP_DIR" "$ZIP_FILE"

echo "Done!"
