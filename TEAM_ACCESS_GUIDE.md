# Local Development Guide

## API Access
The API is accessible at: `http://localhost:8000/api`

## Database Connection Details
- **Host:** 127.0.0.1
- **Database:** realestate
- **Username:** root
- **Password:** (empty)
- **Port:** 3306

## Postman Setup
1. Import the Postman collection from `app/postman` directory
2. Use the local environment settings:
   - base_url: http://localhost:8000/api

## Available API Endpoints
- `POST /register` - Register a new user
- `POST /login` - Login a user
- `GET /user` - Get user details (requires authentication)
- `POST /logout` - Logout (requires authentication)

## Local Development Setup
1. Start your MySQL server
2. Create a database named `realestate`
3. Run migrations: `php artisan migrate`
4. Seed the database: `php artisan db:seed`
5. Start the development server: `php artisan serve`

## Troubleshooting
If you encounter connection issues:
1. Check that your request includes the proper headers:
   - `Accept: application/json`
   - `Content-Type: application/json`
2. For authenticated requests, include the bearer token:
   - `Authorization: Bearer YOUR_TOKEN`
3. Make sure your MySQL service is running 