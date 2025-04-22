<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class MessageController extends Controller
{
    /**
     * Get all messages for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $messages = Message::where('sender_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Get all conversations for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversations()
    {
        $userId = Auth::id();

        $conversations = User::whereHas('sentMessages', function (Builder $query) use ($userId) {
                $query->where('receiver_id', $userId);
            })
            ->orWhereHas('receivedMessages', function (Builder $query) use ($userId) {
                $query->where('sender_id', $userId);
            })
            ->with(['sentMessages' => function ($query) use ($userId) {
                $query->where('receiver_id', $userId)
                    ->latest()
                    ->limit(1);
            }, 'receivedMessages' => function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->latest()
                    ->limit(1);
            }])
            ->get()
            ->map(function ($user) use ($userId) {
                $lastMessage = $user->sentMessages->first() ?? $user->receivedMessages->first();
                $unreadCount = Message::where('sender_id', $user->id)
                    ->where('receiver_id', $userId)
                    ->where('is_read', false)
                    ->count();

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ],
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'updated_at' => $lastMessage ? $lastMessage->created_at : null,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Get conversation with a specific user.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversation($userId)
    {
        $authUserId = Auth::id();
        
        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $messages = Message::where(function ($query) use ($authUserId, $userId) {
                $query->where('sender_id', $authUserId)
                    ->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($authUserId, $userId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $authUserId);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark all unread messages as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', $authUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages,
                'user' => $user
            ]
        ]);
    }

    /**
     * Send a new message.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $receiverId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $receiverId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if receiver exists
        $receiver = User::find($receiverId);
        if (!$receiver) {
            return response()->json([
                'success' => false,
                'message' => 'Receiver not found'
            ], 404);
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $receiverId,
            'content' => $request->content,
            'is_read' => false
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json([
            'success' => true,
            'data' => $message
        ], 201);
    }

    /**
     * Mark a message as read.
     *
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($messageId)
    {
        $message = Message::find($messageId);
        
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        // Check if user is the receiver
        if ($message->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $message->is_read = true;
        $message->save();

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Delete a message.
     *
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($messageId)
    {
        $message = Message::find($messageId);
        
        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        // Check if user is the sender or receiver
        if ($message->sender_id !== Auth::id() && $message->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Get unread message count.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        $unreadCount = Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount
            ]
        ]);
    }
} 