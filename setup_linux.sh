#!/bin/bash

# Exit on error
set -e

echo "🔧 Starting Docker container for DirectNav..."

# Make sure docker volume target exists
mkdir -p ./app

# Launch the container
docker-compose up -d --build

echo
echo "✅ Done! App should be available at:"
echo "   🔓 http://localhost:9000 (for local development)"
echo "   🔒 https://localhost:9443 (self-signed cert warning may appear)"