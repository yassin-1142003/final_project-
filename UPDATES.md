# Local Development Configuration Updates

## Overview
This document outlines the changes made to convert the application from production settings (mughtarib.abaadre.com) to local development settings (localhost:8000). These changes ensure that developers can run and test the application on their local machines without needing to modify any code.

## Files Updated

### Configuration Files
- **vercel.json**: Updated `APP_URL` from `https://mughtarib.abaadre.com` to `http://localhost:8000`
- **config/cors.php**: Updated `allowed_origins` to include `http://localhost:8000`, `http://localhost:3000`, `http://127.0.0.1:8000`, and `http://127.0.0.1:3000`
- **config/database.php**: Changed default database name from `mughtarib_realestate` to `realestate`

### Documentation
- **README.md**: Updated all references to production domain to use localhost:8000
- **API_DOCUMENTATION.md**: Updated base URL for API documentation
- **TEAM_ACCESS_GUIDE.md**: Updated database connection details and setup instructions
- **POSTMAN_LOCAL_SETUP.md**: Updated Postman collection references and database name

### Deployment Scripts
- **deploy.sh**: Modified script to reference local development server
- **fix_db_and_setup_team.bat**: Updated email addresses to use example.com and changed API URL

### Test Scripts
- **tests/api_route_test.php**: Updated API base URL for testing
- **tests/db_migration_test.php**: Simplified database testing script and updated connection details

### Seeders
- **database/seeders/TeamUsersSeeder.php**: Updated email addresses to use example.com instead of localhost

### Other Files
- **public/robots.txt**: Updated sitemap URL and refined crawling rules

## Postman Collection Updates
- Updated the **app/postman/Real Estate API.postman_collection.json** file:
  - Added proper URL formatting for all request endpoints
  - Added test scripts to automatically store IDs in environment variables
  - Updated payment method and transaction endpoints to use environment variables
  - Fixed JSON request bodies to use environment variables where appropriate

- Updated the **app/postman/Real Estate API.postman_environment.json** file:
  - Added variables for `transaction_id` and `payment_method_id`
  - Ensured base URL is set to `http://localhost:8000/api`
  - Updated default email to `admin@example.com`

## Local Development Setup

To set up the application for local development:

1. Clone the repository
2. Create a MySQL database named `realestate`
3. Copy `.env.example` to `.env` and update database credentials
4. Run `composer install`
5. Run `php artisan key:generate`
6. Run `php artisan migrate --seed`
7. Run `php artisan storage:link`
8. Start the server with `php artisan serve`

The API will be accessible at `http://localhost:8000/api`

## Testing the API

Import the Postman collection and environment files from the `app/postman` directory to test all API endpoints locally.

## Notes
- All production domain references have been replaced with localhost equivalents
- Default email addresses now use the example.com domain
- Database name is set to 'realestate' for simplicity
- Environment variables are properly configured for local development