<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\MessageResource;
use App\Http\Resources\MessageCollection;

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
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all messages for the authenticated user.
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
                ->paginate(20);

            return $this->successResponse([
                'messages' => MessageResource::collection($messages->items()),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'total' => $messages->total(),
                'per_page' => $messages->perPage()
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving messages: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve messages.', $e->getMessage(), 500);
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
            $userId = Auth::id();
            
            // Get the latest message with each user
            $conversations = DB::table('messages')
                ->select(
                    DB::raw('CASE 
                        WHEN sender_id = ' . $userId . ' THEN receiver_id 
                        ELSE sender_id 
                    END as user_id'),
                    DB::raw('MAX(created_at) as last_message_time')
                )
                ->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->groupBy(DB::raw('CASE WHEN sender_id = ' . $userId . ' THEN receiver_id ELSE sender_id END'))
                ->orderBy('last_message_time', 'desc')
                ->get();
            
            $result = [];
            
            foreach ($conversations as $conversation) {
                $otherUser = User::find($conversation->user_id);
                
                // Count unread messages from this user
                $unreadCount = Message::where('sender_id', $conversation->user_id)
                    ->where('receiver_id', $userId)
                    ->whereNull('read_at')
                    ->count();
                
                // Get the last message
                $lastMessage = Message::where(function($query) use ($userId, $conversation) {
                        $query->where('sender_id', $userId)
                              ->where('receiver_id', $conversation->user_id);
                    })
                    ->orWhere(function($query) use ($userId, $conversation) {
                        $query->where('sender_id', $conversation->user_id)
                              ->where('receiver_id', $userId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($otherUser) {
                    $result[] = [
                        'user' => [
                            'id' => $otherUser->id,
                            'name' => $otherUser->name,
                            'avatar' => $otherUser->profile_image ?? null
                        ],
                        'unread_count' => $unreadCount,
                        'last_message_time' => $conversation->last_message_time,
                        'last_message' => $lastMessage ? [
                            'id' => $lastMessage->id,
                            'content' => $lastMessage->content,
                            'is_from_me' => $lastMessage->sender_id == $userId,
                            'created_at' => $lastMessage->created_at->toDateTimeString()
                        ] : null
                    ];
                }
            }
            
            return $this->successResponse($result, 'Conversations retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving conversations: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve conversations.', $e->getMessage(), 500);
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
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
            // Mark messages as read when conversation is accessed
            Message::where('sender_id', $userId)
                  ->where('receiver_id', $authUser->id)
                  ->whereNull('read_at')
                  ->update(['read_at' => Carbon::now()]);
            
            return $this->successResponse([
                'messages' => MessageResource::collection($messages->items()),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'total' => $messages->total(),
                'user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->profile_image ?? null
                ]
            ], 'Conversation retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving conversation: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->notFoundResponse('User not found');
            }
            return $this->errorResponse('Failed to retrieve conversation.', $e->getMessage(), 500);
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
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }
            
            $sender = Auth::user();
            
            // Verify receiver exists
            try {
                $receiver = User::findOrFail($receiverId);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return $this->notFoundResponse('Recipient user not found');
            }
            
            $message = new Message();
            $message->sender_id = $sender->id;
            $message->receiver_id = $receiverId;
            $message->content = $request->content;
            $message->read_at = null;
            $message->save();
            
            // Load relationships for the resource
            $message->load(['sender', 'receiver']);
            
            return $this->successResponse(new MessageResource($message), 'Message sent successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return $this->errorResponse('Failed to send message.', $e->getMessage(), 500);
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
            
            try {
                $message = Message::where('id', $messageId)
                                ->where('receiver_id', $user->id)
                                ->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return $this->notFoundResponse('Message not found or you do not have permission to mark it as read');
            }
            
            $message->read_at = Carbon::now();
            $message->save();
            
            // Load relationships for the resource
            $message->load(['sender', 'receiver']);
            
            return $this->successResponse(new MessageResource($message), 'Message marked as read');
        } catch (\Exception $e) {
            Log::error('Error marking message as read: ' . $e->getMessage());
            return $this->errorResponse('Failed to mark message as read.', $e->getMessage(), 500);
        }
    }

    /**
     * Delete a message.
     *
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($messageId)
    {
        try {
            $user = Auth::user();
            
            try {
                $message = Message::findOrFail($messageId);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return $this->notFoundResponse('Message not found');
            }
            
            // Check if user is the sender
            if ($message->sender_id !== $user->id) {
                return $this->forbiddenResponse('You cannot delete messages you did not send');
            }
            
            $message->delete();
            
            return $this->successResponse(null, 'Message deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting message: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete message.', $e->getMessage(), 500);
        }
    }

    /**
     * Get unread message count.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            
            $count = Message::where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->count();
            
            return $this->successResponse(['count' => $count], 'Unread message count retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving unread count: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve unread count.', $e->getMessage(), 500);
        }
    }

    /**
     * Check if user can access a conversation.
     * 
     * @param User $user
     * @param int $otherUserId
     * @return bool
     */
    private function canAccessConversation(User $user, $otherUserId)
    {
        // Admins can access any conversation
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Regular users can only access their own conversations
        return true; // By default, users can access their conversations
                     // You can add more rules here as needed
    }
} 