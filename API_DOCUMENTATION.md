# Real Estate Listings API Documentation

## Table of Contents

1. [API Route Summary](#api-route-summary)
2. [Base URL](#base-url)
3. [Authentication](#authentication)
4. [User Endpoints](#user-endpoints)
5. [Listings Endpoints](#listings-endpoints)
6. [Comment Endpoints](#comment-endpoints)
7. [Admin Endpoints](#admin-endpoints)
8. [Payment Methods](#payment-methods)
9. [Booking Endpoints](#booking-endpoints)
10. [Database Migration API Endpoints](#database-migration-api-endpoints)
11. [Recent API Updates](#recent-updates)
12. [Role-Based API Access](#role-based-access)
13. [Postman Testing Guide](#postman-guide)
14. [Full Route List](#full-route-list)
15. [Saved Searches](#saved-searches)
16. [Comment Reporting](#comment-reporting)

## API Route Summary

| Section | Available Endpoints |
|---------|-------------------|
| Health Check | GET `/test`, GET `/health` |
| Authentication | POST `/register`, POST `/login`, POST `/logout`, GET `/user`, POST `/refresh`, POST `/forgot-password`, POST `/reset-password` |
| Apartments | GET `/apartments`, GET `/apartments/{apartment}`, GET `/featured-apartments`, GET `/search-apartments`, POST `/apartments`, PUT `/apartments/{apartment}`, DELETE `/apartments/{apartment}` |
| Comments | GET `/apartments/{apartment}/comments`, POST `/apartments/{apartment}/comments`, PUT `/apartments/{apartment}/comments/{comment}`, DELETE `/apartments/{apartment}/comments/{comment}` |
| Favorites | GET `/favorites`, POST `/favorites`, DELETE `/favorites/{apartment}`, GET `/favorites/{apartment}/check`, POST `/favorites/{listing}/toggle` |
| Saved Searches | GET `/saved-searches`, POST `/saved-searches`, GET `/saved-searches/{id}`, PUT `/saved-searches/{id}`, DELETE `/saved-searches/{id}` |
| Admin | GET `/admin/comments/pending`, PUT `/admin/comments/{comment}/approve`, GET `/admin/reports`, PUT `/admin/reports/{id}/resolve` |
| Database Management | POST `/admin/database/migrate`, POST `/admin/database/migrate/rollback`, GET `/admin/database/migrate/status`, POST `/admin/database/migrate/reset`, POST `/admin/database/seed` |

For a complete list of all available API endpoints with their authentication requirements, see the [Full Route List](#full-route-list) section at the end of this document.

## Base URL

The base URL for all API endpoints is:

```
http://localhost:8000/api
```

## Authentication

Most endpoints require authentication using a Bearer token. To authenticate, include the token in the Authorization header:

```
Authorization: Bearer {your_token}
```

You can obtain a token by registering or logging in.

## Error Handling

The API returns appropriate HTTP status codes:

- `200 OK` - The request was successful
- `201 Created` - A resource was successfully created
- `400 Bad Request` - The request was malformed
- `401 Unauthorized` - Authentication failed or token expired
- `403 Forbidden` - The authenticated user doesn't have permission
- `404 Not Found` - The requested resource doesn't exist
- `422 Unprocessable Entity` - Validation errors

Error responses include a message and sometimes validation errors:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

## Authentication Endpoints

### Register

Creates a new user account.

- **URL**: `/register`
- **Method**: `POST`
- **Auth required**: No
- **Permissions**: None

**Request Body**:

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password",
    "role_id": 3,
    "phone": "1234567890"
}
```

**Response**: `201 Created`

```json
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role_id": 3,
        "phone": "1234567890",
        "created_at": "2023-01-01T00:00:00.000000Z"
    },
    "token": "your_auth_token"
}
```

### Login

Authenticates a user and provides a token.

- **URL**: `/login`
- **Method**: `POST`
- **Auth required**: No
- **Permissions**: None

**Request Body**:

```json
{
    "email": "john@example.com",
    "password": "password"
}
```

**Response**: `200 OK`

```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role_id": 3
    },
    "token": "your_auth_token"
}
```

### Logout

Invalidates the current token.

- **URL**: `/logout`
- **Method**: `POST`
- **Auth required**: Yes
- **Permissions**: None

**Response**: `200 OK`

```json
{
    "message": "Successfully logged out"
}
```

## User Endpoints

### Get User Profile

Retrieves the authenticated user's profile.

- **URL**: `/user`
- **Method**: `GET`
- **Auth required**: Yes
- **Permissions**: None

**Response**: `200 OK`

```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role_id": 3,
        "phone": "1234567890",
        "profile_image": null,
        "role": {
            "id": 3,
            "name": "user",
            "description": "Can browse and search for properties"
        }
    }
}
```

### Update User Profile

Updates the authenticated user's profile information.

- **URL**: `/user`
- **Method**: `PUT`
- **Auth required**: Yes
- **Permissions**: None

**Request Body**:

```json
{
    "name": "John Smith",
    "phone": "9876543210"
}
```

**Response**: `200 OK`

```json
{
    "message": "Profile updated successfully",
    "data": {
        "id": 1,
        "name": "John Smith",
        "email": "john@example.com",
        "role_id": 3,
        "phone": "9876543210"
    }
}
```

## Listings Endpoints

### Get Listings

Retrieves a list of active property listings with pagination.

- **URL**: `/listings`
- **Method**: `GET`
- **Auth required**: No
- **Permissions**: None

**Query Parameters**:

- `city` - Filter by city
- `property_type` - Filter by property type (apartment, house, villa, land, commercial, other)
- `listing_type` - Filter by listing type (rent, sale)
- `min_price` - Minimum price
- `max_price` - Maximum price
- `bedrooms` - Number of bedrooms
- `bathrooms` - Number of bathrooms
- `sort_by` - Field to sort by (price, created_at, bedrooms, bathrooms, area)
- `sort_order` - Sort direction (asc, desc)
- `per_page` - Results per page (default: 10)

**Response**: `200 OK`

```json
{
    "data": [
        {
            "id": 1,
            "title": "Luxury Apartment",
            "description": "A beautiful apartment in the city center",
            "price": "250000.00",
            "address": "123 Main St",
            "city": "New York",
            "property_type": "apartment",
            "listing_type": "sale",
            "bedrooms": 2,
            "bathrooms": 1,
            "area": "120.00",
            "is_featured": true,
            "images": [
                {
                    "id": 1,
                    "image_path": "listings/1/image1.jpg",
                    "is_primary": true
                }
            ],
            "user": {
                "id": 2,
                "name": "Property Owner"
            },
            "ad_type": {
                "id": 3,
                "name": "Featured"
            }
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 10,
        "current_page": 1,
        "last_page": 5
    }
}
```

### Get Featured Listings

Retrieves a list of featured property listings.

- **URL**: `/listings/featured`
- **Method**: `GET`
- **Auth required**: No
- **Permissions**: None

**Response**: `200 OK`

```json
{
    "data": [
        {
            "id": 1,
            "title": "Luxury Apartment",
            "description": "A beautiful apartment in the city center",
            "price": "250000.00",
            "is_featured": true
        }
    ]
}
```

## Admin Endpoints

### Get All Users (Admin)

Retrieves a list of all users.

- **URL**: `/admin/users`
- **Method**: `GET`
- **Auth required**: Yes
- **Permissions**: Admin only

**Response**: `200 OK`

```json
{
    "data": [
        {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com",
            "role_id": 1,
            "is_active": true
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 15,
        "current_page": 1,
        "last_page": 4
    }
}
```

## Comment Endpoints

### Get Listing Comments
**GET** `/listings/{listing_id}/comments`

Response:
```json
{
    "data": [
        {
            "id": 1,
            "user_id": 3,
            "listing_id": 1,
            "content": "This property looks amazing! Is it still available?",
            "created_at": "2023-06-15T14:30:00.000000Z",
            "user": {
                "id": 3,
                "name": "Jane Smith"
            }
        }
    ],
    "meta": {
        "total": 5,
        "per_page": 15,
        "current_page": 1,
        "last_page": 1
    }
}
```

### Get Single Comment
**GET** `/listings/{listing_id}/comments/{comment_id}`

Response:
```json
{
    "data": {
        "id": 1,
        "user_id": 3,
        "listing_id": 1,
        "content": "This property looks amazing! Is it still available?",
        "created_at": "2023-06-15T14:30:00.000000Z",
        "user": {
            "id": 3,
            "name": "Jane Smith"
        }
    }
}
```

### Add Comment (Authenticated)
**POST** `/listings/{listing_id}/comments`

Request Body:
```json
{
    "content": "I'm interested in this property. Please contact me."
}
```

Response:
```json
{
    "message": "Comment added successfully",
    "data": {
        "id": 2,
        "user_id": 3,
        "listing_id": 1,
        "content": "I'm interested in this property. Please contact me.",
        "created_at": "2023-06-16T10:45:00.000000Z",
        "user": {
            "id": 3,
            "name": "Jane Smith"
        }
    }
}
```

### Update Comment (Owner of comment)
**PUT** `/listings/{listing_id}/comments/{comment_id}`

Request Body:
```json
{
    "content": "Updated comment text."
}
```

Response:
```json
{
    "message": "Comment updated successfully",
    "data": {
        "id": 2,
        "content": "Updated comment text.",
        "created_at": "2023-06-16T10:45:00.000000Z",
        "updated_at": "2023-06-16T11:00:00.000000Z",
        "user": {
            "id": 3,
            "name": "Jane Smith"
        }
    }
}
```

### Delete Comment (Owner of comment)
**DELETE** `/listings/{listing_id}/comments/{comment_id}`

Response:
```json
{
    "message": "Comment deleted successfully"
}
```

### Get Comments on My Listings (Property owner)
**GET** `/my-listings-comments`

Response:
```json
{
    "data": [
        {
            "id": 1,
            "content": "This property looks amazing! Is it still available?",
            "created_at": "2023-06-15T14:30:00.000000Z",
            "user": {
                "id": 3,
                "name": "Jane Smith"
            },
            "listing": {
                "id": 1,
                "title": "Luxury Apartment"
            }
        }
    ],
    "meta": {
        "total": 8,
        "per_page": 15,
        "current_page": 1,
        "last_page": 1
    }
}
```

## Payment Methods

### Get Available Payment Methods
**GET** `/payment-methods`

Response:
```json
{
    "data": [
        {
            "id": "stripe",
            "name": "Credit/Debit Card",
            "description": "Pay securely with your credit or debit card",
            "auto_approved": true
        },
        {
            "id": "vodafone_cash",
            "name": "Vodafone Cash",
            "description": "Pay using Vodafone Cash mobile wallet",
            "auto_approved": false
        },
        {
            "id": "bank_transfer",
            "name": "Bank Transfer",
            "description": "Pay via bank transfer",
            "auto_approved": false
        },
        {
            "id": "paypal",
            "name": "PayPal",
            "description": "Pay securely with PayPal",
            "auto_approved": true
        }
    ]
}
```

### Create Payment Intent
**POST** `/payments/create-intent`

Request Body:
```json
{
    "listing_id": 1,
    "payment_method": "vodafone_cash"
}
```

Response:
```json
{
    "payment_intent": {
        "id": "vc_123456789",
        "amount": 150.00,
        "currency": "egp",
        "instructions": "Send payment to Vodafone Cash wallet: 01XXXXXXXXX with reference: 1",
        "note": "After sending payment, please upload the receipt/screenshot as proof"
    },
    "listing": {
        "id": 1,
        "title": "Luxury Apartment",
        "ad_type": {
            "id": 2,
            "name": "Premium",
            "price": "150.00"
        }
    }
}
```

### Confirm Payment
**POST** `/payments/confirm`

Request Body:
```json
{
    "payment_id": "vc_123456789",
    "listing_id": 1,
    "payment_method": "vodafone_cash",
    "receipt_image": "[image file]"
}
```

Response:
```json
{
    "message": "Payment received and awaiting verification",
    "payment": {
        "id": 1,
        "payment_id": "vc_123456789",
        "payment_method": "vodafone_cash",
        "amount": "150.00",
        "status": "pending",
        "notes": "Awaiting manual verification"
    },
    "listing": {
        "id": 1,
        "title": "Luxury Apartment",
        "is_paid": false
    }
}
```

## Booking Endpoints

### Get User Bookings

Retrieves all bookings made by the authenticated user.

- **URL**: `/bookings`
- **Method**: `GET`
- **Auth required**: Yes
- **Permissions**: None

**Query Parameters**:
- `status` - Filter by status (pending, confirmed, cancelled, completed)
- `per_page` - Results per page (default: 10)

**Response**: `200 OK`

```json
{
    "data": [
        {
            "id": 1,
            "booking_date": "2023-06-15T14:30:00.000000Z",
            "check_in": "2023-07-01T12:00:00.000000Z",
            "check_out": "2023-07-05T10:00:00.000000Z",
            "total_price": "500.00",
            "status": "confirmed",
            "is_paid": true,
            "notes": "Special requests: early check-in if possible",
            "listing": {
                "id": 3,
                "title": "Beach Front Villa",
                "address": "123 Ocean Drive",
                "city": "Miami",
                "images": [
                    {
                        "id": 1,
                        "image_path": "listings/3/image1.jpg",
                        "is_primary": true
                    }
                ]
            }
        }
    ],
    "meta": {
        "total": 5,
        "per_page": 10,
        "current_page": 1,
        "last_page": 1
    }
}
```

### Get Single Booking

Retrieves details of a specific booking.

- **URL**: `/bookings/{booking_id}`
- **Method**: `GET`
- **Auth required**: Yes
- **Permissions**: Owner of booking or property owner

**Response**: `200 OK`

```json
{
    "data": {
        "id": 1,
        "booking_date": "2023-06-15T14:30:00.000000Z",
        "check_in": "2023-07-01T12:00:00.000000Z",
        "check_out": "2023-07-05T10:00:00.000000Z",
        "total_price": "500.00",
        "status": "confirmed",
        "is_paid": true,
        "notes": "Special requests: early check-in if possible",
        "listing": {
            "id": 3,
            "title": "Beach Front Villa",
            "address": "123 Ocean Drive",
            "city": "Miami",
            "property_type": "villa",
            "listing_type": "rent",
            "bedrooms": 3,
            "bathrooms": 2,
            "area": "200.00",
            "images": [
                {
                    "id": 1,
                    "image_path": "listings/3/image1.jpg",
                    "is_primary": true
                }
            ]
        },
        "user": {
            "id": 5,
            "name": "Jane Smith",
            "email": "jane@example.com",
            "phone": "9876543210"
        }
    }
}
```

### Create Booking

Creates a new booking for a property.

- **URL**: `/listings/{listing_id}/bookings`
- **Method**: `POST`
- **Auth required**: Yes
- **Permissions**: None

**Request Body**:

```json
{
    "check_in": "2023-08-10T14:00:00",
    "check_out": "2023-08-15T10:00:00",
    "notes": "We will arrive late in the evening"
}
```

**Response**: `201 Created`

```json
{
    "message": "Booking created successfully",
    "data": {
        "id": 2,
        "booking_date": "2023-06-20T15:45:00.000000Z",
        "check_in": "2023-08-10T14:00:00.000000Z",
        "check_out": "2023-08-15T10:00:00.000000Z",
        "total_price": "750.00",
        "status": "pending",
        "is_paid": false,
        "notes": "We will arrive late in the evening",
        "listing": {
            "id": 5,
            "title": "Mountain Cabin"
        }
    }
}
```

### Update Booking Status

Updates the status of a booking (for property owners or admins).

- **URL**: `/bookings/{booking_id}/status`
- **Method**: `PUT`
- **Auth required**: Yes
- **Permissions**: Property owner or admin

**Request Body**:

```json
{
    "status": "confirmed"
}
```

**Response**: `200 OK`

```json
{
    "message": "Booking status updated successfully",
    "data": {
        "id": 2,
        "status": "confirmed",
        "updated_at": "2023-06-21T09:30:00.000000Z"
    }
}
```

### Cancel Booking

Allows a user to cancel their booking.

- **URL**: `/bookings/{booking_id}/cancel`
- **Method**: `PUT`
- **Auth required**: Yes
- **Permissions**: Owner of booking

**Response**: `200 OK`

```json
{
    "message": "Booking cancelled successfully",
    "data": {
        "id": 2,
        "status": "cancelled",
        "updated_at": "2023-06-21T10:15:00.000000Z"
    }
}
```

### Get Property Bookings (Property Owner)

Retrieves all bookings for properties owned by the authenticated user.

- **URL**: `/my-property-bookings`
- **Method**: `GET`
- **Auth required**: Yes
- **Permissions**: Property owner

**Query Parameters**:
- `status` - Filter by status (pending, confirmed, cancelled, completed)
- `property_id` - Filter by specific property ID
- `per_page` - Results per page (default: 10)

**Response**: `200 OK`

```json
{
    "data": [
        {
            "id": 3,
            "booking_date": "2023-06-18T11:20:00.000000Z",
            "check_in": "2023-09-05T15:00:00.000000Z",
            "check_out": "2023-09-10T11:00:00.000000Z",
            "total_price": "600.00",
            "status": "pending",
            "is_paid": false,
            "listing": {
                "id": 8,
                "title": "Downtown Apartment"
            },
            "user": {
                "id": 7,
                "name": "Robert Johnson",
                "email": "robert@example.com",
                "phone": "5551234567"
            }
        }
    ],
    "meta": {
        "total": 12,
        "per_page": 10,
        "current_page": 1,
        "last_page": 2
    }
}
```

## Getting Started

1. Clone the repository
2. Install dependencies with `composer install`
3. Configure your `.env` file
4. Run migrations and seeders: `php artisan migrate --seed`
5. Start the server: `php artisan serve`
6. Test the API endpoints using Postman or any API client

For detailed environment setup, check the `env-instructions.txt` file in the project root.

## Postman Collection

A Postman collection is included in the project root (`real-estate-api.postman_collection.json`). Import this file into Postman to quickly test all API endpoints.

## Database Migration API Endpoints

These endpoints are only accessible to users with admin privileges and are designed to help manage database migrations programmatically.

### Run Migrations

```
POST /api/admin/database/migrate
```

Runs database migrations.

**Request Parameters:**
- `fresh` (boolean, optional) - Run migrations after dropping all tables
- `seed` (boolean, optional) - Seed the database after migrations
- `force` (boolean, optional) - Force the operation to run in production
- `step` (integer, optional) - The number of migrations to run

**Example Response:**
```json
{
    "success": true,
    "message": "Migrations completed successfully",
    "details": "Migration output details..."
}
```

### Rollback Migrations

```
POST /api/admin/database/migrate/rollback
```

Rolls back the last database migration.

**Request Parameters:**
- `step` (integer, optional) - The number of migrations to rollback
- `force` (boolean, optional) - Force the operation to run in production

**Example Response:**
```json
{
    "success": true,
    "message": "Rollback completed successfully",
    "details": "Rollback output details..."
}
```

### Get Migration Status

```
GET /api/admin/database/migrate/status
```

Returns the status of all migrations.

**Example Response:**
```json
{
    "success": true,
    "migrations": [
        {
            "migration": "2014_10_12_000000_create_users_table",
            "ran": true
        },
        {
            "migration": "2023_09_01_000001_create_messages_table",
            "ran": true
        }
    ],
    "raw_output": "Full output from the migrate:status command..."
}
```

### Reset Migrations

```
POST /api/admin/database/migrate/reset
```

Resets all migrations (rolls back all migrations).

**Example Response:**
```json
{
    "success": true,
    "message": "Migration reset completed successfully",
    "details": "Reset output details..."
}
```

### Run Database Seeders

```
POST /api/admin/database/seed
```

Runs database seeders.

**Request Parameters:**
- `class` (string, optional) - The class name of the seeder to run (defaults to DatabaseSeeder)

**Example Response:**
```json
{
    "success": true,
    "message": "Database seeding completed successfully",
    "details": "Seeding output details..."
}
```

## Full Route List {#full-route-list}

A comprehensive list of all API endpoints available on the system.

### Health Check Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| GET    | /test    | Simple API test endpoint | No |
| GET    | /health  | Returns API health status with version and timestamp | No |

### Authentication Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| POST   | /register | Register a new user | No |
| POST   | /login    | Login and get authentication token | No |
| POST   | /logout   | Logout and invalidate token | Yes |
| GET    | /user     | Get authenticated user profile | Yes |
| POST   | /refresh  | Refresh authentication token | Yes |
| POST   | /forgot-password | Request password reset link | No |
| POST   | /reset-password  | Reset password with token | No |
| POST   | /verify-email/{id}/{hash} | Verify email address | No |
| POST   | /resend-verification-email | Resend verification email | No |

### Apartment Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| GET    | /apartments | Get list of all apartments | No |
| GET    | /apartments/{apartment} | Get single apartment details | No |
| GET    | /featured-apartments | Get featured apartments | No |
| GET    | /search-apartments | Search apartments with filters | No |
| POST   | /apartments | Create new apartment | Yes (Owner) |
| PUT    | /apartments/{apartment} | Update apartment | Yes (Owner) |
| DELETE | /apartments/{apartment} | Delete apartment | Yes (Owner) |
| GET    | /user/apartments | Get apartments owned by authenticated user | Yes |

### Comments Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| GET    | /apartments/{apartment}/comments | Get comments for an apartment | No |
| GET    | /apartments/{apartment}/comments/{comment} | Get single comment | No |
| GET    | /apartments/{apartment}/rating | Get average rating for apartment | No |
| POST   | /apartments/{apartment}/comments | Create new comment | Yes |
| PUT    | /apartments/{apartment}/comments/{comment} | Update comment | Yes (Owner) |
| DELETE | /apartments/{apartment}/comments/{comment} | Delete comment | Yes (Owner) |
| POST   | /apartments/{apartment}/comments/{comment}/report | Report comment | Yes |

### Favorites Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| GET    | /favorites | Get user's favorite apartments | Yes |
| POST   | /favorites | Add apartment to favorites | Yes |
| DELETE | /favorites/{apartment} | Remove apartment from favorites | Yes |
| GET    | /favorites/{apartment}/check | Check if apartment is in favorites | Yes |
| POST   | /favorites/{listing}/toggle | Toggle favorite status | Yes |

### Saved Search Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| GET    | /saved-searches | Get user's saved searches | Yes |
| POST   | /saved-searches | Create new saved search | Yes |
| GET    | /saved-searches/{id} | Get saved search details | Yes |
| PUT    | /saved-searches/{id} | Update saved search | Yes |
| DELETE | /saved-searches/{id} | Delete saved search | Yes |

### Admin Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| GET    | /admin/comments/pending | Get pending comments | Yes (Admin) |
| PUT    | /admin/comments/{comment}/approve | Approve comment | Yes (Admin) |
| GET    | /admin/reports | Get comment reports | Yes (Admin) |
| PUT    | /admin/reports/{id}/resolve | Resolve comment report | Yes (Admin) |

### Admin Database Management Endpoints

| Method | Endpoint | Description | Authentication Required |
|--------|----------|-------------|------------------------|
| POST   | /admin/database/migrate | Run database migrations | Yes (Admin) |
| POST   | /admin/database/migrate/rollback | Rollback last migration batch | Yes (Admin) |
| GET    | /admin/database/migrate/status | Get migration status | Yes (Admin) |
| POST   | /admin/database/migrate/reset | Reset all migrations | Yes (Admin) |
| POST   | /admin/database/seed | Run database seeders | Yes (Admin) |

## Recent API Updates {#recent-updates}

### Recently Implemented Features

#### 1. Comment Reporting System

We have successfully implemented a complete comment reporting system for the Real Estate API. This allows users to report inappropriate comments, and administrators to manage these reports. Here's a summary of the changes made:

- **CommentReport Model**: Created a new model with relationships to both the `Comment` and `User` models.
- **SoftDeletes**: Added soft delete capability to both `Comment` and `CommentReport` models.
- **Controllers**: Updated CommentController and created CommentReportController
- **Routes**: Added new endpoints for reporting and resolving comments
- **Middleware**: Created AdminMiddleware for role-based access control

#### 2. Saved Searches System

We have also implemented a saved searches system that allows users to save their property search criteria for future use. Key components include:

- **SavedSearch Model**: Created a model with JSON storage for search filters
- **Controller Methods**: Implemented CRUD operations for saved searches
- **Notification Support**: Added fields for notification preferences and frequency
- **Routes**: Added endpoints for managing saved searches
- **Postman Collection**: Updated with requests for testing saved searches

### Next Steps

1. **Enhanced Notifications**:
   - Implement a notification system for saved searches
   - Add email notifications for comment reports and resolutions

2. **User Experience Improvements**:
   - Add more filtering options to apartment searches
   - Implement property comparisons

3. **Performance Optimizations**:
   - Add caching for frequently accessed data
   - Optimize database queries for large result sets

## Role-Based API Access {#role-based-access}

This section outlines the specific actions and permissions available to each user role in the Real Estate API.

### User Roles

The system includes three main user roles:

1. **Admin** (role_id: 1) - Full system access with moderation capabilities
2. **Property Owner** (role_id: 2) - Can create and manage apartment listings
3. **Regular User** (role_id: 3) - Can browse apartments, leave comments, and manage favorites

### Admin Capabilities

Admins have full access to the system and special moderation privileges:

#### Apartment Management
- View all apartments (GET `/apartments`)
- Create new apartments (POST `/apartments`)
- View individual apartments (GET `/apartments/{id}`)
- Update any apartment (PUT `/apartments/{id}`)
- Delete any apartment (DELETE `/apartments/{id}`)

#### Comment Moderation
- View all comments for any apartment (GET `/apartments/{id}/comments`)
- View pending comments awaiting approval (GET `/admin/comments/pending`)
- Approve comments (PUT `/admin/comments/{id}/approve`)
- Delete any comment (DELETE `/apartments/{id}/comments/{id}`)

#### Comment Report Management
- View all comment reports (GET `/admin/reports`)
- Filter reports by status (GET `/admin/reports?status=pending`)
- Resolve reports by approving, rejecting, or deleting the reported comment (PUT `/admin/reports/{id}/resolve`)

#### User Management
- Admins can manage their own profile (GET, PUT `/profile`)

### Property Owner Capabilities

Property owners can manage their own listings:

#### Apartment Management
- View all apartments (GET `/apartments`)
- Create new apartments (POST `/apartments`)
- View individual apartments (GET `/apartments/{id}`)
- Update their own apartments (PUT `/apartments/{id}`)
- Delete their own apartments (DELETE `/apartments/{id}`)

#### Comment Interaction
- View comments on their apartments (GET `/apartments/{id}/comments`)
- View average ratings on their apartments (GET `/apartments/{id}/rating`)
- Report inappropriate comments on their apartments (POST `/apartments/{id}/comments/{id}/report`)

#### Favorites Management
- Add apartments to favorites (POST `/favorites`)
- View their favorited apartments (GET `/favorites`)
- Remove apartments from favorites (DELETE `/favorites/{id}`)
- Check if an apartment is in their favorites (GET `/favorites/{id}/check`)
- Toggle favorite status (POST `/favorites/{id}/toggle`)

### Regular User Capabilities

Regular users can browse and interact with listings:

#### Apartment Browsing
- View all apartments (GET `/apartments`)
- View featured apartments (GET `/featured-apartments`)
- View individual apartments (GET `/apartments/{id}`)
- Search and filter apartments (GET `/apartments?search=term&min_price=value&max_price=value&location=value&bedrooms=value&bathrooms=value&min_area=value&max_area=value`)
- Sort apartments (GET `/apartments?sort_by=field&sort_direction=asc`)

#### Comment Management
- View comments on apartments (GET `/apartments/{id}/comments`)
- View average ratings (GET `/apartments/{id}/rating`)
- Create comments and ratings (POST `/apartments/{id}/comments`)
- Update their own comments (PUT `/apartments/{id}/comments/{id}`)
- Delete their own comments (DELETE `/apartments/{id}/comments/{id}`)
- Report inappropriate comments (POST `/apartments/{id}/comments/{id}/report`)

#### Favorites Management
- Add apartments to favorites (POST `/favorites`)
- View their favorited apartments (GET `/favorites`)
- Remove apartments from favorites (DELETE `/favorites/{id}`)
- Check if an apartment is in their favorites (GET `/favorites/{id}/check`)
- Toggle favorite status (POST `/favorites/{id}/toggle`)

### API Endpoints By Role

#### Public Endpoints (No Authentication Required)
- `GET /api/health` - Health check
- `GET /api/test` - API test
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/forgot-password` - Request password reset
- `POST /api/reset-password` - Reset password
- `POST /api/verify-email/{id}/{hash}` - Verify email address
- `GET /api/apartments` - List apartments
- `GET /api/apartments/{id}` - View apartment details
- `GET /api/featured-apartments` - View featured apartments
- `GET /api/search-apartments` - Search apartments
- `GET /api/apartments/{id}/comments` - View comments on an apartment
- `GET /api/apartments/{id}/rating` - View average rating for an apartment

#### Regular User & Owner Endpoints (Authentication Required)
- `POST /api/logout` - Logout
- `POST /api/refresh` - Refresh token
- `GET /api/user` - Get user profile
- `POST /api/resend-verification-email` - Resend verification email
- `GET /api/favorites` - List favorites
- `POST /api/favorites` - Add to favorites
- `DELETE /api/favorites/{id}` - Remove from favorites
- `GET /api/favorites/{id}/check` - Check favorite status
- `POST /api/favorites/{id}/toggle` - Toggle favorite status
- `POST /api/apartments/{id}/comments` - Create comment
- `PUT /api/apartments/{id}/comments/{id}` - Update own comment
- `DELETE /api/apartments/{id}/comments/{id}` - Delete own comment
- `POST /api/apartments/{id}/comments/{id}/report` - Report comment
- `GET /api/saved-searches` - List saved searches
- `POST /api/saved-searches` - Create saved search
- `GET /api/saved-searches/{id}` - View saved search details
- `PUT /api/saved-searches/{id}` - Update saved search
- `DELETE /api/saved-searches/{id}` - Delete saved search

#### Owner-Only Endpoints (Authentication + Owner Role Required)
- `POST /api/apartments` - Create apartment
- `PUT /api/apartments/{id}` - Update own apartment
- `DELETE /api/apartments/{id}` - Delete own apartment
- `GET /api/user/apartments` - View own apartments

#### Administrator Endpoints (Authentication + Admin Role Required)
- `GET /api/admin/comments/pending` - View pending comments
- `PUT /api/admin/comments/{id}/approve` - Approve comment
- `GET /api/admin/reports` - View comment reports
- `PUT /api/admin/reports/{id}/resolve` - Resolve comment report

## Postman Testing Guide {#postman-guide}

This section provides guidance on how to test the API endpoints using Postman.

### Setup

1. Download and install [Postman](https://www.postman.com/downloads/)
2. Import the API collection from `app/postman/Real Estate API.postman_collection.json`
3. Import the environment file from `app/postman/Real Estate API.postman_environment.json`
4. Select the environment from the dropdown in the top-right corner

### Authentication

#### Register a New User

- **Request**:
  - Method: `POST`
  - URL: `{{baseUrl}}/register`
  - Body (JSON):
    ```json
    {
        "name": "John Doe",
        "email": "john@example.com",
        "password": "password123",
        "password_confirmation": "password123",
        "role_id": 2,  // 1: Admin, 2: Property Owner, 3: Regular User
        "phone": "1234567890"
    }
    ```
- **Response**: Status 201 Created with user data and token

#### Login

- **Request**:
  - Method: `POST`
  - URL: `{{baseUrl}}/login`
  - Body (JSON):
    ```json
    {
        "email": "john@example.com",
        "password": "password123"
    }
    ```
- **Response**: Status 200 OK with user data and token
- **Tip**: Copy the token from the response and set it to your `token` environment variable

#### Get User Profile

- **Request**:
  - Method: `GET`
  - URL: `{{baseUrl}}/profile`
  - Headers:
    - `Authorization`: `Bearer {{token}}`
- **Response**: Status 200 OK with user profile data

### Working with Apartments

#### List All Apartments

- **Request**:
  - Method: `GET`
  - URL: `{{baseUrl}}/apartments`
  - Query Parameters (optional):
    - `search`: Search term for apartments
    - `min_price`: Minimum price filter
    - `max_price`: Maximum price filter
    - `location`: Location filter
    - `bedrooms`: Number of bedrooms filter
    - `bathrooms`: Number of bathrooms filter
    - `min_area`: Minimum area filter
    - `max_area`: Maximum area filter
    - `sort_by`: Field to sort by (e.g., 'price', 'created_at')
    - `sort_direction`: 'asc' or 'desc'
    - `page`: Page number for pagination
- **Response**: Status 200 OK with paginated apartment listings

#### Create Apartment (Property Owner or Admin)

- **Request**:
  - Method: `POST`
  - URL: `{{baseUrl}}/apartments`
  - Headers:
    - `Authorization`: `Bearer {{token}}`
  - Body (Form-data):
    - `title`: "Beautiful Apartment"
    - `description`: "A spacious apartment with great views"
    - `price`: 1500
    - `location`: "New York"
    - `bedrooms`: 2
    - `bathrooms`: 1
    - `area`: 90
    - `images[]`: [File uploads] (multiple files allowed)
- **Response**: Status 201 Created with apartment data

### Working with Comments

#### Create Comment (Authenticated Users)

- **Request**:
  - Method: `POST`
  - URL: `{{baseUrl}}/apartments/{apartment_id}/comments`
  - Headers:
    - `Authorization`: `Bearer {{token}}`
  - Body (JSON):
    ```json
    {
        "content": "This is a great apartment!",
        "rating": 4
    }
    ```
- **Response**: Status 201 Created with comment data

#### Report Comment

- **Request**:
  - Method: `POST`
  - URL: `{{baseUrl}}/apartments/{apartment_id}/comments/{comment_id}/report`
  - Headers:
    - `Authorization`: `Bearer {{token}}`
  - Body (JSON):
    ```json
    {
        "reason": "This comment contains inappropriate content"
    }
    ```
- **Response**: Status 201 Created with report confirmation

### Testing Admin Functions

#### View Pending Comments

- **Request**:
  - Method: `GET`
  - URL: `{{baseUrl}}/admin/comments/pending`
  - Headers:
    - `Authorization`: `Bearer {{token}}` (must be admin token)
- **Response**: Status 200 OK with paginated list of pending comments

#### Approve Comment

- **Request**:
  - Method: `PUT`
  - URL: `{{baseUrl}}/admin/comments/{comment_id}/approve`
  - Headers:
    - `Authorization`: `Bearer {{token}}` (must be admin token)
- **Response**: Status 200 OK with updated comment data

#### View Comment Reports

- **Request**:
  - Method: `GET`
  - URL: `{{baseUrl}}/admin/reports`
  - Headers:
    - `Authorization`: `Bearer {{token}}` (must be admin token)
  - Query Parameters (optional):
    - `status`: Filter by status (pending, resolved, rejected)
- **Response**: Status 200 OK with paginated list of comment reports

### Database Migration Testing

#### Run Migrations

- **Request**:
  - Method: `POST`
  - URL: `{{baseUrl}}/admin/database/migrate`
  - Headers:
    - `Authorization`: `Bearer {{token}}` (must be admin token)
  - Body (JSON):
    ```json
    {
        "force": true,
        "seed": false,
        "fresh": false
    }
    ```
- **Response**: Status 200 OK with migration details

#### Get Migration Status

- **Request**:
  - Method: `GET`
  - URL: `{{baseUrl}}/admin/database/migrate/status`
  - Headers:
    - `Authorization`: `Bearer {{token}}` (must be admin token)
- **Response**: Status 200 OK with migration status information

### Environment Variables

The Postman environment includes these key variables:

- `base_url`: The API base URL (typically http://localhost:8000/api)
- `token`: Your authentication token (set after login)
- `email`: Default test email address
- `password`: Default test password
- `apartment_id`: Currently selected apartment ID
- `comment_id`: Currently selected comment ID
- `report_id`: Currently selected report ID

## Saved Searches {#saved-searches}

The saved searches system allows users to save their search criteria for properties, enabling them to quickly access their favorite search filters later. Users can also opt to receive notifications about new properties that match their saved criteria.

### Saved Searches Endpoints

#### List Saved Searches

```
GET /api/saved-searches
```

Returns a list of the authenticated user's saved searches.

**Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "name": "Downtown Apartments",
      "filters": {
        "location": "downtown",
        "min_price": 200000,
        "max_price": 500000,
        "bedrooms": 2,
        "bathrooms": 2
      },
      "is_notifiable": true,
      "notification_frequency": "weekly",
      "last_notified_at": "2023-09-15T12:00:00Z",
      "created_at": "2023-09-01T10:30:00Z",
      "updated_at": "2023-09-01T10:30:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Create Saved Search

```
POST /api/saved-searches
```

Creates a new saved search with the specified criteria.

**Request Parameters:**
```json
{
  "name": "Downtown Apartments",
  "filters": {
    "location": "downtown",
    "min_price": 200000,
    "max_price": 500000,
    "bedrooms": 2,
    "bathrooms": 2
  },
  "is_notifiable": true,
  "notification_frequency": "weekly"
}
```

**Response Example:**
```json
{
  "message": "Saved search created successfully",
  "data": {
    "id": 1,
    "user_id": 5,
    "name": "Downtown Apartments",
    "filters": {
      "location": "downtown",
      "min_price": 200000,
      "max_price": 500000,
      "bedrooms": 2,
      "bathrooms": 2
    },
    "is_notifiable": true,
    "notification_frequency": "weekly",
    "created_at": "2023-09-01T10:30:00Z",
    "updated_at": "2023-09-01T10:30:00Z"
  }
}
```

#### Get Saved Search

```
GET /api/saved-searches/{id}
```

Returns details of a specific saved search.

**Response Example:**
```json
{
  "data": {
    "id": 1,
    "user_id": 5,
    "name": "Downtown Apartments",
    "filters": {
      "location": "downtown",
      "min_price": 200000,
      "max_price": 500000,
      "bedrooms": 2,
      "bathrooms": 2
    },
    "is_notifiable": true,
    "notification_frequency": "weekly",
    "last_notified_at": "2023-09-15T12:00:00Z",
    "created_at": "2023-09-01T10:30:00Z",
    "updated_at": "2023-09-01T10:30:00Z"
  }
}
```

#### Update Saved Search

```
PUT /api/saved-searches/{id}
```

Updates an existing saved search.

**Request Parameters:**
```json
{
  "name": "Updated Downtown Search",
  "filters": {
    "location": "downtown",
    "min_price": 250000,
    "max_price": 600000,
    "bedrooms": 3
  },
  "is_notifiable": false
}
```

**Response Example:**
```json
{
  "message": "Saved search updated successfully",
  "data": {
    "id": 1,
    "user_id": 5,
    "name": "Updated Downtown Search",
    "filters": {
      "location": "downtown",
      "min_price": 250000,
      "max_price": 600000,
      "bedrooms": 3
    },
    "is_notifiable": false,
    "notification_frequency": "weekly",
    "last_notified_at": "2023-09-15T12:00:00Z",
    "created_at": "2023-09-01T10:30:00Z",
    "updated_at": "2023-09-16T14:20:00Z"
  }
}
```

#### Delete Saved Search

```
DELETE /api/saved-searches/{id}
```

Deletes a saved search.

**Response Example:**
```json
{
  "message": "Saved search deleted successfully"
}
```

## Comment Reporting {#comment-reporting}

The comment reporting system allows users to report inappropriate comments. Administrators can then review these reports and take appropriate action (approve, reject, or delete).

### User Endpoints

#### Report a Comment

```
POST /api/apartments/{apartment}/comments/{comment}/report
```

Reports a comment as inappropriate.

**Request Parameters:**
```json
{
  "reason": "This comment contains inappropriate content"
}
```

**Response Example:**
```json
{
  "message": "Comment reported successfully",
  "data": {
    "id": 1,
    "comment_id": 5,
    "user_id": 10,
    "reason": "This comment contains inappropriate content",
    "status": "pending",
    "created_at": "2023-09-20T08:45:00Z",
    "updated_at": "2023-09-20T08:45:00Z"
  }
}
```

### Admin Endpoints

#### List Reports

```
GET /api/admin/reports
```

Returns a list of comment reports, filterable by status.

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `resolved`, or `rejected`). Default is `pending`.

**Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "comment_id": 5,
      "user_id": 10,
      "reason": "This comment contains inappropriate content",
      "status": "pending",
      "created_at": "2023-09-20T08:45:00Z",
      "updated_at": "2023-09-20T08:45:00Z",
      "comment": {
        "id": 5,
        "content": "Example comment content",
        "user_id": 12,
        "apartment_id": 3,
        "created_at": "2023-09-18T16:30:00Z"
      },
      "reporter": {
        "id": 10,
        "name": "Reporting User"
      }
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

#### Resolve a Report

```
PUT /api/admin/reports/{id}/resolve
```

Resolves a comment report with the specified action.

**Request Parameters:**
```json
{
  "resolution_notes": "This comment violates our community guidelines",
  "action": "delete" // Options: approve, reject, delete
}
```

**Response Example:**
```json
{
  "message": "Report resolved successfully",
  "data": {
    "id": 1,
    "comment_id": 5,
    "user_id": 10,
    "reason": "This comment contains inappropriate content",
    "status": "resolved",
    "resolution_notes": "This comment violates our community guidelines",
    "action_taken": "delete",
    "resolved_by": 1,
    "resolved_at": "2023-09-21T10:15:00Z",
    "created_at": "2023-09-20T08:45:00Z",
    "updated_at": "2023-09-21T10:15:00Z"
  }
}
``` 