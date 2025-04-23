<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ApartmentController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\SavedSearchController;
use App\Http\Controllers\API\CommentReportController;
use App\Http\Controllers\API\GoogleMapsController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\DatabaseMigrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// API Health Check Routes
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now()->toDateTimeString(),
        'server' => 'localhost',
        'environment' => app()->environment(),
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0',
        'environment' => 'local',
        'server_time' => now()->toDateTimeString(),
        'api_url' => 'http://localhost:8000/api',
        'server' => 'localhost',
    ]);
});

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
Route::get('/roles', [AuthController::class, 'getRoles']);

// Adding direct auth routes (without /api prefix) to handle both prefixed and non-prefixed requests
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Social Login Routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/auth/facebook', [AuthController::class, 'redirectToFacebook'])->name('login.facebook');
Route::get('/auth/facebook/callback', [AuthController::class, 'handleFacebookCallback']);

// Public Apartment Routes
Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{apartment}', [ApartmentController::class, 'show']);
Route::get('/featured-apartments', [ApartmentController::class, 'featured']);
Route::get('/search-apartments', [ApartmentController::class, 'search']);

// Comment routes
Route::get('apartments/{apartment}/comments', [CommentController::class, 'index']);
Route::get('apartments/{apartment}/comments/{comment}', [CommentController::class, 'show']);
Route::get('apartments/{apartment}/rating', [CommentController::class, 'getAverageRating']);

// Google Maps Routes - DISABLED: Controller not found
// Route::get('/map/nearby-places', [GoogleMapsController::class, 'getNearbyPlaces']);
// Route::get('/map/directions', [GoogleMapsController::class, 'getDirections']);
// Route::get('/map/geocode', [GoogleMapsController::class, 'geocodeAddress']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Protected Apartment Routes
    Route::post('/apartments', [ApartmentController::class, 'store']);
    Route::put('/apartments/{apartment}', [ApartmentController::class, 'update']);
    Route::delete('/apartments/{apartment}', [ApartmentController::class, 'destroy']);
    Route::get('/user/apartments', [ApartmentController::class, 'userApartments']);

    // Favorites Routes
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{apartment}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/{apartment}/check', [FavoriteController::class, 'check']);
    Route::post('/favorites/{listing}/toggle', [FavoriteController::class, 'toggle']);

    // Protected comment routes
    Route::post('apartments/{apartment}/comments', [CommentController::class, 'store']);
    Route::put('apartments/{apartment}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('apartments/{apartment}/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('apartments/{apartment}/comments/{comment}/report', [CommentController::class, 'reportComment']);
    Route::post('apartments/{apartment}/comments/{comment}/vote', [CommentController::class, 'vote']);
    Route::post('apartments/{apartment}/comments/{comment}/pin', [CommentController::class, 'pinComment']);
    
    // Admin comment management routes
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('comments/pending', [CommentController::class, 'pendingComments']);
        Route::put('comments/{comment}/approve', [CommentController::class, 'approveComment']);
        Route::post('apartments/{apartment}/comments/{comment}/feature', [CommentController::class, 'featureComment']);
        Route::get('reports', [CommentReportController::class, 'index']);
        Route::put('reports/{id}/resolve', [CommentReportController::class, 'resolve']);
        
        // Database Migration Routes - DISABLED: Controller not found
        // Route::prefix('database')->group(function () {
        //     Route::post('/migrate', [DatabaseMigrationController::class, 'runMigrations']);
        //     Route::post('/migrate/rollback', [DatabaseMigrationController::class, 'rollbackMigration']);
        //     Route::get('/migrate/status', [DatabaseMigrationController::class, 'getMigrationStatus']);
        //     Route::post('/migrate/reset', [DatabaseMigrationController::class, 'resetMigrations']);
        //     Route::post('/seed', [DatabaseMigrationController::class, 'runSeeder']);
        // });
    });

    // Saved Search Routes
    Route::prefix('saved-searches')->group(function () {
        Route::get('/', [SavedSearchController::class, 'index']);
        Route::post('/', [SavedSearchController::class, 'store']);
        Route::get('/{id}', [SavedSearchController::class, 'show']);
        Route::put('/{id}', [SavedSearchController::class, 'update']);
        Route::delete('/{id}', [SavedSearchController::class, 'destroy']);
    });
    
    // Google Maps User Routes - DISABLED: Controller not found
    // Route::post('/map/save-location', [GoogleMapsController::class, 'saveUserLocation']);
    // Route::get('/map/saved-locations', [GoogleMapsController::class, 'getUserSavedLocations']);
    
    // Messaging System Routes
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/conversations', [MessageController::class, 'getConversations']);
    Route::get('/conversations/{userId}', [MessageController::class, 'getConversation']);
    Route::post('/messages/{receiverId}', [MessageController::class, 'sendMessage']);
    Route::put('/messages/{messageId}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{messageId}', [MessageController::class, 'destroy']);
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
    
    // Booking/Appointment System Routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/apartments/{apartment}', [BookingController::class, 'bookAppointment']);
        Route::get('/apartments/{apartment}/availability', [BookingController::class, 'getAvailability']);
        Route::get('/{booking}', [BookingController::class, 'show']);
        Route::put('/{booking}', [BookingController::class, 'update']);
        Route::delete('/{booking}', [BookingController::class, 'cancel']);
        Route::put('/{booking}/confirm', [BookingController::class, 'confirmBooking']);
        Route::put('/{booking}/reschedule', [BookingController::class, 'reschedule']);
    });
    
    // Payment Integration Routes
    Route::prefix('payments')->group(function () {
        Route::post('/intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/process', [PaymentController::class, 'processPayment']);
        Route::get('/methods', [PaymentController::class, 'getPaymentMethods']);
        Route::post('/methods', [PaymentController::class, 'addPaymentMethod']);
        Route::delete('/methods/{id}', [PaymentController::class, 'removePaymentMethod']);
        Route::get('/transactions', [PaymentController::class, 'getTransactions']);
        Route::get('/transactions/{id}', [PaymentController::class, 'getTransaction']);
        Route::post('/transactions/{id}/refund', [PaymentController::class, 'requestRefund']);
        Route::get('/gateways', [PaymentController::class, 'getAvailableGateways']);
    });
    
    // Notifications Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::get('/settings', [NotificationController::class, 'getSettings']);
        Route::put('/settings', [NotificationController::class, 'updateSettings']);
        Route::post('/subscribe', [NotificationController::class, 'subscribeToSavedSearch']);
        Route::delete('/unsubscribe/{id}', [NotificationController::class, 'unsubscribeFromSavedSearch']);
    });
});

// Direct API routes for payment methods and other services
// Added to fix Postman compatibility issues - These routes match what's used in Postman
Route::middleware('auth:sanctum')->group(function () {
    // Direct routes for payment methods (no /api prefix)
    Route::get('/payments/methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('/payments/methods', [PaymentController::class, 'addPaymentMethod']);
    Route::delete('/payments/methods/{id}', [PaymentController::class, 'removePaymentMethod']);
    Route::get('/payments/gateways', [PaymentController::class, 'getAvailableGateways']);
    
    // Direct routes for saved searches (no /api prefix)
    Route::get('/saved-searches', [SavedSearchController::class, 'index']);
    Route::post('/saved-searches', [SavedSearchController::class, 'store']);
    Route::get('/saved-searches/{id}', [SavedSearchController::class, 'show']);
    Route::put('/saved-searches/{id}', [SavedSearchController::class, 'update']);
    Route::delete('/saved-searches/{id}', [SavedSearchController::class, 'destroy']);
    
    // Direct routes for favorites (no /api prefix)
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{apartment}', [FavoriteController::class, 'destroy']);
});