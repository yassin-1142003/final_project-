@echo off
echo Starting Real Estate API server...
echo ======================================

set HOST=0.0.0.0
set PORT=8000

echo API will be accessible at: http://%HOST%:%PORT%
echo To stop the server, press Ctrl+C
echo.

echo Setting environment to production...
set APP_ENV=production
set APP_DEBUG=false

echo Running PHP server...
cd %~dp0
php -d display_errors=0 -d variables_order=EGPCS -S %HOST%:%PORT% -t public

echo Server stopped. 