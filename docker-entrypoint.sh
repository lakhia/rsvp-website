#!/bin/bash
set -e

echo "Starting RSVP application..."

# Check if env file is specified, default to .env
ENV_FILE="${ENV_FILE:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "Error: Environment file '$ENV_FILE' not found!"
    echo "Please copy .env.example to .env and customize it"
    exit 1
fi

echo "Using environment file: $ENV_FILE"

# Install npm dependencies (needed because volume mount overwrites node_modules)
echo "Installing npm dependencies..."
npm install --quiet

# Run the build process
echo "Building application..."
npm run build

# Run deploy.pl with the env file
echo "Running deploy.pl with environment..."
perl deploy.pl "$ENV_FILE"

echo "Build and Configuration complete!"

# Start Apache
echo "Starting Apache..."
exec apache2-foreground