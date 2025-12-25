#!/bin/bash
set -e

echo "Starting RSVP application in DEVELOPMENT MODE..."
echo "Build-based dev with live reload and auto-rebuild"

# Check if env file is specified, default to .env
ENV_FILE="${ENV_FILE:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "Error: Environment file '$ENV_FILE' not found!"
    echo "Please copy .env.example to .env and customize it"
    exit 1
fi

echo "Using environment file: $ENV_FILE"

# Ensure .env is the file we're using (npm scripts reference .env)
if [ "$ENV_FILE" != ".env" ]; then
    echo "Copying $ENV_FILE to .env for npm scripts..."
    cp "$ENV_FILE" .env
fi

# Install npm dependencies (needed because volume mount overwrites node_modules)
echo "Installing npm dependencies..."
npm install --quiet

echo ""
echo "=========================================="
echo "  Development server starting..."
echo "=========================================="
echo "  Browser-sync: http://localhost:3000"
echo "  PHP backend:  http://localhost:8010"
echo "  Serving from: build/ (auto-rebuild on changes)"
echo "  Watching:     app/ directory for changes"
echo "=========================================="
echo ""
echo "Initial build and deploy in progress..."
echo ""

# Run npm dev:docker script which will:
# 1. Build to build/ directory
# 2. Run deploy.pl on build/ to substitute env vars
# 3. Start PHP server serving from build/
# 4. Start browser-sync watching build/
# 5. Watch app/ for changes and rebuild + redeploy
exec npm run dev:docker
