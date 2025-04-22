#!/bin/bash

# Deployment script for Real Estate API
# For server: http://localhost:8000/api

echo "Starting deployment..."
echo "======================="

# Navigate to project directory
cd "$(dirname "$0")"

# Check git status
echo "Checking git status..."
git status

# Pull latest changes
echo "Pulling latest changes..."
git pull

# Install or update dependencies
echo "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Update .env file if needed
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
else
    echo ".env file already exists."
fi

# Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Create storage link if needed
if [ ! -d "public/storage" ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# Optimize
echo "Optimizing..."
php artisan optimize

echo "Deployment completed successfully!"
echo "API is accessible at: http://localhost:8000/api" 