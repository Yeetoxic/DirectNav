#!/bin/bash

# Exit on error
set -e

echo "🔧 Starting Docker container for DirectNav..."

# Make sure docker volume target exists
mkdir -p ./app

# Launch the container
docker-compose up -d --build

echo "✅ Done! App should be available at http://localhost:9000"