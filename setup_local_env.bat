@echo off
echo Setting up local environment...

REM Update .env file
echo Updating .env file for local development...
(
echo APP_NAME="Real Estate API"
echo APP_ENV=local
echo APP_KEY=base64:wNRZKSaSF42JLzxUQm16ZQnpHMqRxZcXYCJpBwXwZ0w=
echo APP_DEBUG=true
echo APP_URL=http://localhost:8000
echo.
echo LOG_CHANNEL=stack
echo LOG_DEPRECATIONS_CHANNEL=null
echo LOG_LEVEL=debug
echo.
echo DB_CONNECTION=mysql
echo DB_HOST=127.0.0.1
echo DB_PORT=3306
echo DB_DATABASE=mydb
echo DB_USERNAME=mydb_admin
echo DB_PASSWORD=password
echo.
echo BROADCAST_DRIVER=log
echo CACHE_DRIVER=file
echo FILESYSTEM_DISK=local
echo QUEUE_CONNECTION=sync
echo SESSION_DRIVER=file
echo SESSION_LIFETIME=120
echo.
echo MAIL_MAILER=smtp
echo MAIL_HOST=mailpit
echo MAIL_PORT=1025
echo MAIL_USERNAME=null
echo MAIL_PASSWORD=null
echo MAIL_ENCRYPTION=null
echo MAIL_FROM_ADDRESS="hello@example.com"
echo MAIL_FROM_NAME="${APP_NAME}"
echo.
echo # Google and Facebook auth
echo GOOGLE_CLIENT_ID=
echo GOOGLE_CLIENT_SECRET=
echo GOOGLE_REDIRECT=http://localhost:8000/api/auth/google/callback
echo.
echo FACEBOOK_CLIENT_ID=
echo FACEBOOK_CLIENT_SECRET=
echo FACEBOOK_REDIRECT=http://localhost:8000/api/auth/facebook/callback
echo.
echo # Google Maps API Key
echo GOOGLE_MAPS_API_KEY=
) > .env

echo .env file created successfully.

REM Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

REM Run migrations
echo Running database migrations...
php artisan migrate:fresh --seed

echo Setup completed successfully!
echo You can now run the application with: php artisan serve 