<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\ApiResponses;

class MessageController extends Controller
{
    use ApiResponses;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the user's messages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $messages = Message::where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->with(['sender', 'receiver'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                            'avatar' => $message->sender->avatar
                        ],
                        'receiver' => [
                            'id' => $message->receiver->id,
                            'name' => $message->receiver->name,
                            'avatar' => $message->receiver->avatar
                        ],
                        'content' => $message->content,
                        'read_at' => $message->read_at,
                        'created_at' => $message->created_at,
                        'updated_at' => $message->updated_at
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all conversations for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversations()
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // Get unique users that the authenticated user has exchanged messages with
            $conversations = DB::select("
                SELECT DISTINCT
                    CASE
                        WHEN m.sender_id = {$userId} THEN m.receiver_id
                        ELSE m.sender_id
                    END as user_id,
                    (SELECT COUNT(*) FROM messages 
                     WHERE receiver_id = {$userId} 
                     AND sender_id = CASE WHEN m.sender_id = {$userId} THEN m.receiver_id ELSE m.sender_id END
                     AND read_at IS NULL) as unread_count,
                    (SELECT created_at FROM messages 
                     WHERE (sender_id = {$userId} AND receiver_id = CASE WHEN m.sender_id = {$userId} THEN m.receiver_id ELSE m.sender_id END)
                     OR (receiver_id = {$userId} AND sender_id = CASE WHEN m.sender_id = {$userId} THEN m.receiver_id ELSE m.sender_id END)
                     ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM messages m
                WHERE m.sender_id = {$userId} OR m.receiver_id = {$userId}
                ORDER BY last_message_time DESC
            ");
            
            $userIds = array_map(function($conv) {
                return $conv->user_id;
            }, $conversations);
            
            // Fetch user details
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            
            $result = array_map(function($conv) use ($users) {
                $user = $users[$conv->user_id] ?? null;
                if (!$user) return null;
                
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->avatar
                    ],
                    'unread_count' => $conv->unread_count,
                    'last_message_time' => $conv->last_message_time
                ];
            }, $conversations);
            
            // Filter out null values (if any users weren't found)
            $result = array_filter($result);
            
            return response()->json([
                'success' => true,
                'data' => array_values($result)
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving conversations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation with a specific user.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversation($userId)
    {
        try {
            $authUser = Auth::user();
            
            // Validate that the other user exists
            $otherUser = User::findOrFail($userId);
            
            // Get messages between these two users
            $messages = Message::where(function($query) use ($authUser, $userId) {
                $query->where('sender_id', $authUser->id)
                      ->where('receiver_id', $userId);
            })->orWhere(function($query) use ($authUser, $userId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $authUser->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();
            
            // Mark messages as read when conversation is accessed
            Message::where('sender_id', $userId)
                  ->where('receiver_id', $authUser->id)
                  ->whereNull('read_at')
                  ->update(['read_at' => Carbon::now()]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages,
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar' => $otherUser->avatar
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message to a user.
     *
     * @param Request $request
     * @param int $receiverId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $receiverId)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:1000',
            ]);
            
            $sender = Auth::user();
            
            // Verify receiver exists
            $receiver = User::findOrFail($receiverId);
            
            $message = new Message();
            $message->sender_id = $sender->id;
            $message->receiver_id = $receiverId;
            $message->content = $request->content;
            $message->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a message as read.
     *
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($messageId)
    {
        try {
            $user = Auth::user();
            
            $message = Message::where('id', $messageId)
                             ->where('receiver_id', $user->id)
                             ->firstOrFail();
            
            $message->read_at = Carbon::now();
            $message->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Message marked as read'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking message as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark message as read.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a message.
     *
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($messageId)
    {
        try {
            $user = Auth::user();
            
            $message = Message::where('id', $messageId)
                             ->where(function($query) use ($user) {
                                 $query->where('sender_id', $user->id)
                                       ->orWhere('receiver_id', $user->id);
                             })
                             ->firstOrFail();
            
            $message->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the count of unread messages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            
            $unreadCount = Message::where('receiver_id', $user->id)
                                 ->whereNull('read_at')
                                 ->count();
            
            // Get conversations with unread messages
            $unreadConversations = DB::table('messages as m')
                ->join('users as u', 'u.id', '=', 'm.sender_id')
                ->select(
                    'm.sender_id as user_id',
                    'u.name',
                    'u.avatar',
                    DB::raw('COUNT(*) as count')
                )
                ->where('m.receiver_id', $user->id)
                ->whereNull('m.read_at')
                ->groupBy('m.sender_id', 'u.name', 'u.avatar')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_unread' => $unreadCount,
                    'unread_conversations' => $unreadConversations
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread message count.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 