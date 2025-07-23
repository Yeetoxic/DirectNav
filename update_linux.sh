#!/bin/bash

echo "🔄 Updating DirectNav..."

# Move to script directory
cd "$(dirname "$0")" || exit 1

# Check for git
if ! command -v git &> /dev/null; then
    echo "❌ Git is not installed. Please install Git to continue."
    exit 1
fi

# Exclude /app from being touched
EXCLUDE_PATH="app"

echo "📦 Backing up any uncommitted changes (except /app)..."
git status --porcelain | grep -v "^ M $EXCLUDE_PATH" | grep '^ M ' > /dev/null && git stash push -m "Auto-stash before update"

echo "⬇️ Pulling latest changes from GitHub..."
git pull origin main || git pull origin master

echo "🔁 Reapplying stashed changes (if any)..."
git stash pop || echo "✅ No local changes to reapply."

# Optional Docker rebuild
if [ -f docker-compose.yml ]; then
    echo "🐳 Rebuilding Docker containers..."
    docker compose down
    docker compose up -d --build
fi

echo "✅ DirectNav update complete. Your /app directory was left untouched."
