#!/bin/bash
# Remove set -e to prevent exit on warnings
# set -e 

cd /var/www/html

echo "Starting Entrypoint..."

# Check if vendor folder exists
if [ ! -d "vendor" ]; then
    echo "Vendor folder not found. Attempting to install dependencies..."
    
    if [ -f "composer.json" ]; then
        # Use --ignore-platform-reqs to prevent failure if extensions aren't detected by composer immediately
        composer install --no-interaction --prefer-dist --ignore-platform-reqs
    else
        echo "composer.json not found in $(pwd)"
    fi
else
    echo "Vendor folder exists. Skipping install."
fi

echo "Starting Cron..."
service cron start

echo "Starting Apache..."
exec apache2-foreground
