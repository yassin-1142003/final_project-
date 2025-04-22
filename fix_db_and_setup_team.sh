#!/bin/bash

# Fix Database Connection and Team Access Setup
# For server: http://localhost/public/api

echo "Starting database fix and team setup..."
echo "======================================"

# Navigate to the project directory
cd $(dirname "$0")

# Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart the Laravel app
echo "Restarting Laravel application..."
php artisan down
php artisan up

# Test the database connection
echo "Testing database connection..."
php artisan db:monitor

# Run migrations if needed
echo "Running migrations..."
php artisan migrate --seed --force

# Seed team users
echo "Creating team user accounts..."
php artisan db:seed --class=TeamUsersSeeder --force

# Clear cache again
echo "Final cache clearing..."
php artisan config:cache
php artisan route:cache
php artisan optimize

echo "Setup completed!"
echo "Your team now has access with the following accounts:"
echo "======================================"
echo "Admin:    admin@localhost / Admin@123"
echo "Manager:  manager@localhost / Manager@123"
echo "User:     user@localhost / User@123"
echo "Editor:   editor@localhost / Editor@123"
echo "Support:  support@localhost / Support@123"
echo "======================================"
echo "API is accessible at: http://localhost/public/api" 