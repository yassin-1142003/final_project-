# Real Estate API

A complete RESTful API for real estate property management with features for authentication, property listings, favorites, comments, reporting, and saved searches.

## Features

### Authentication
- User registration with role-based access
- Login/logout with token-based authentication
- Profile management
- Password reset
- Email verification

### Property Management
- List, create, update, and delete apartments
- Image upload support
- Advanced search functionality
- Featured properties
- User's property listings

### User Interactions
- Comments with ratings
- Comment moderation and reporting
- Favorites system
- Saved searches with notification preferences

## Technology Stack

- **Backend:** Laravel
- **Authentication:** Laravel Sanctum
- **Database:** MySQL
- **Storage:** Local file storage
- **API Documentation:** Postman Collection

## Getting Started

### Prerequisites
- PHP >= 8.0
- Composer
- MySQL
- Node.js & NPM (for frontend, if applicable)

### Installation

1. Clone the repository
```bash
git clone https://github.com/your-username/real-estate-api.git
cd real-estate-api
```

2. Install dependencies
```bash
composer install
```

3. Configure environment variables
```bash
cp .env.example .env
# Edit .env file with your database credentials
```

4. Generate application key
```bash
php artisan key:generate
```

5. Run migrations and seed the database
```bash
php artisan migrate:fresh --seed
```

6. Create storage link
```bash
php artisan storage:link
```

7. Start the development server
```bash
php artisan serve
```

The API will be available at http://127.0.0.1:8000

## API Documentation

### Postman Collection

Import the following files into Postman:
- `postman/Real Estate API.postman_collection.json`
- `postman/Real Estate API.postman_environment.json`

The collection includes:
- Authentication endpoints
- Property management
- Comments and reporting
- Favorites management
- Saved searches

### Main Endpoints

#### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get token
- `POST /api/logout` - Logout
- `GET /api/user` - Get user profile

#### Properties
- `GET /api/apartments` - List all apartments
- `POST /api/apartments` - Create a new apartment
- `GET /api/apartments/{id}` - Get a specific apartment
- `PUT /api/apartments/{id}` - Update an apartment
- `DELETE /api/apartments/{id}` - Delete an apartment
- `GET /api/search-apartments` - Search apartments
- `GET /api/featured-apartments` - Get featured apartments
- `GET /api/user/apartments` - Get user's apartments

#### Comments
- `GET /api/apartments/{id}/comments` - Get comments for an apartment
- `POST /api/apartments/{id}/comments` - Add a comment
- `PUT /api/apartments/{id}/comments/{id}` - Update a comment
- `DELETE /api/apartments/{id}/comments/{id}` - Delete a comment
- `POST /api/apartments/{id}/comments/{id}/report` - Report a comment

#### Admin
- `GET /api/admin/comments/pending` - Get pending comments
- `PUT /api/admin/comments/{id}/approve` - Approve a comment
- `GET /api/admin/reports` - Get comment reports
- `PUT /api/admin/reports/{id}/resolve` - Resolve a report

#### Favorites
- `GET /api/favorites` - Get user's favorites
- `POST /api/favorites` - Add to favorites
- `DELETE /api/favorites/{id}` - Remove from favorites
- `GET /api/favorites/{id}/check` - Check favorite status
- `POST /api/favorites/{id}/toggle` - Toggle favorite status

#### Saved Searches
- `GET /api/saved-searches` - Get user's saved searches
- `POST /api/saved-searches` - Create a saved search
- `GET /api/saved-searches/{id}` - Get a saved search
- `PUT /api/saved-searches/{id}` - Update a saved search
- `DELETE /api/saved-searches/{id}` - Delete a saved search

## Database Structure

The database includes the following main tables:
- `users` - User accounts
- `roles` - User roles
- `apartments` - Property listings
- `images` - Property images
- `comments` - User comments and ratings
- `comment_reports` - Reported comments
- `favorites` - User's favorite properties
- `saved_searches` - User's saved search criteria

## Documentation

Additional documentation is available in the repository:
- `README_COMMENT_REPORTING.md` - Comment reporting system documentation
- `README_SAVED_SEARCHES.md` - Saved searches system documentation
- `API_UPDATES_SUMMARY.md` - Summary of API updates
- `IMPLEMENTATION_CHECKLIST.md` - Implementation checklist

## License

This project is licensed under the MIT License - see the LICENSE file for details

## Acknowledgements

- Laravel Team for the amazing framework
- All contributors to this project 