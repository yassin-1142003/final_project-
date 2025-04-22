<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\SavedSearch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Check if notification belongs to user
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Check if notification belongs to user
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get notification settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $settings = NotificationSetting::where('user_id', Auth::id())->first();
        
        if (!$settings) {
            // Create default settings if none exist
            $settings = NotificationSetting::create([
                'user_id' => Auth::id(),
                'email_notifications' => true,
                'push_notifications' => true,
                'new_message_notification' => true,
                'new_property_notification' => true,
                'saved_search_notification' => true,
                'booking_update_notification' => true,
                'system_notification' => true
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update notification settings.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'new_message_notification' => 'boolean',
            'new_property_notification' => 'boolean',
            'saved_search_notification' => 'boolean',
            'booking_update_notification' => 'boolean',
            'system_notification' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $settings = NotificationSetting::where('user_id', Auth::id())->first();
        
        if (!$settings) {
            // Create settings if none exist
            $settings = new NotificationSetting();
            $settings->user_id = Auth::id();
        }

        // Update only provided fields
        if ($request->has('email_notifications')) {
            $settings->email_notifications = $request->email_notifications;
        }
        
        if ($request->has('push_notifications')) {
            $settings->push_notifications = $request->push_notifications;
        }
        
        if ($request->has('new_message_notification')) {
            $settings->new_message_notification = $request->new_message_notification;
        }
        
        if ($request->has('new_property_notification')) {
            $settings->new_property_notification = $request->new_property_notification;
        }
        
        if ($request->has('saved_search_notification')) {
            $settings->saved_search_notification = $request->saved_search_notification;
        }
        
        if ($request->has('booking_update_notification')) {
            $settings->booking_update_notification = $request->booking_update_notification;
        }
        
        if ($request->has('system_notification')) {
            $settings->system_notification = $request->system_notification;
        }
        
        $settings->save();

        return response()->json([
            'success' => true,
            'data' => $settings,
            'message' => 'Notification settings updated successfully'
        ]);
    }

    /**
     * Subscribe to saved search notifications.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribeToSavedSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'saved_search_id' => 'required|exists:saved_searches,id',
            'email_frequency' => 'required|in:daily,weekly,immediate',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $savedSearch = SavedSearch::find($request->saved_search_id);
        
        // Check if saved search belongs to user
        if ($savedSearch->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }
        
        $savedSearch->notification_enabled = true;
        $savedSearch->notification_frequency = $request->email_frequency;
        $savedSearch->save();

        return response()->json([
            'success' => true,
            'data' => $savedSearch,
            'message' => 'Successfully subscribed to saved search notifications'
        ]);
    }

    /**
     * Unsubscribe from saved search notifications.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsubscribeFromSavedSearch($id)
    {
        $savedSearch = SavedSearch::find($id);
        
        if (!$savedSearch) {
            return response()->json([
                'success' => false,
                'message' => 'Saved search not found'
            ], 404);
        }
        
        // Check if saved search belongs to user
        if ($savedSearch->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }
        
        $savedSearch->notification_enabled = false;
        $savedSearch->save();

        return response()->json([
            'success' => true,
            'message' => 'Successfully unsubscribed from saved search notifications'
        ]);
    }
} 