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

# Ensure .env is the file we're using (bun scripts reference .env)
if [ "$ENV_FILE" != ".env" ]; then
    echo "Copying $ENV_FILE to .env for bun scripts..."
    cp "$ENV_FILE" .env
fi

# Install bun dependencies (needed because volume mount overwrites node_modules)
echo "Installing bun dependencies..."
bun install --quiet
cd frontend && bun install --quiet && cd ..

echo ""
echo "=========================================="
echo "  Development server starting..."
echo "=========================================="
echo "  Vite dev:    http://localhost:5173"
echo "  PHP backend: http://localhost:8010"
echo "  Serving PHP: build/ directory"
echo "=========================================="
echo ""

# Run bun dev:docker script which will:
# 1. Copy PHP files to build/ and run deploy.pl
# 2. Start PHP server serving from build/
# 3. Start SvelteKit Vite dev server (proxies PHP calls to localhost:8010)
exec bun run dev:docker
