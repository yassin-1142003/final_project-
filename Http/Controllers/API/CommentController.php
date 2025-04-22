<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Display a listing of the comments for a listing.
     */
    public function index(Request $request, Listing $listing)
    {
        $query = $listing->comments()
            ->with('user')
            ->where('is_approved', true);
        
        // Apply sorting and filtering
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'newest':
                    $query->latest();
                    break;
                case 'oldest':
                    $query->oldest();
                    break;
                case 'helpful':
                    $query->orderByDesc('helpful_count');
                    break;
                case 'rating':
                    $query->orderByDesc('rating');
                    break;
                default:
                    $query->latest();
            }
        } else {
            // Default sort order: pinned first, then most recent
            $query->orderByDesc('is_pinned')->latest();
        }
        
        // Filter by rating if provided
        if ($request->has('rating') && $request->rating > 0) {
            $query->where('rating', $request->rating);
        }
        
        // Filter by featured status
        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }
        
        $comments = $query->paginate(15);

        return response()->json([
            'data' => $comments,
            'meta' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, Listing $listing)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = new Comment([
            'user_id' => $request->user()->id,
            'listing_id' => $listing->id,
            'content' => $request->content,
            'rating' => $request->rating,
            'is_approved' => true, // Auto-approve for now, can be changed to require approval
            'status' => 'active',
        ]);

        $listing->comments()->save($comment);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Display the specified comment.
     */
    public function show(Listing $listing, Comment $comment)
    {
        // Ensure the comment belongs to the listing
        if ($comment->listing_id !== $listing->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        return response()->json([
            'data' => $comment->load('user'),
        ]);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Listing $listing, Comment $comment)
    {
        // Ensure the comment belongs to the listing
        if ($comment->listing_id !== $listing->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Ensure the user owns this comment or is an admin
        if ($comment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment->update([
            'content' => $request->content,
            'rating' => $request->rating ?? $comment->rating,
        ]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => $comment->fresh()->load('user'),
        ]);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, Listing $listing, Comment $comment)
    {
        // Ensure the comment belongs to the listing
        if ($comment->listing_id !== $listing->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Ensure the user owns this comment or is an admin
        if ($comment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    /**
     * Vote on a comment (helpful or not helpful)
     */
    public function vote(Request $request, Listing $listing, Comment $comment)
    {
        // Ensure the comment belongs to the listing
        if ($comment->listing_id !== $listing->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'is_helpful' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if user has already voted
        $existingVote = $comment->getUserVote($request->user()->id);
        
        DB::beginTransaction();
        
        try {
            if ($existingVote) {
                // If vote type is changing, update counts
                if ($existingVote->is_helpful !== $request->is_helpful) {
                    // Decrease old count
                    if ($existingVote->is_helpful) {
                        $comment->decrement('helpful_count');
                    } else {
                        $comment->decrement('unhelpful_count');
                    }
                    
                    // Increase new count
                    if ($request->is_helpful) {
                        $comment->increment('helpful_count');
                    } else {
                        $comment->increment('unhelpful_count');
                    }
                    
                    // Update vote
                    $existingVote->update([
                        'is_helpful' => $request->is_helpful
                    ]);
                }
                
                $message = 'Vote updated successfully';
            } else {
                // Create new vote
                CommentVote::create([
                    'comment_id' => $comment->id,
                    'user_id' => $request->user()->id,
                    'is_helpful' => $request->is_helpful
                ]);
                
                // Update comment counts
                if ($request->is_helpful) {
                    $comment->increment('helpful_count');
                } else {
                    $comment->increment('unhelpful_count');
                }
                
                $message = 'Vote added successfully';
            }
            
            DB::commit();
            
            return response()->json([
                'message' => $message,
                'data' => [
                    'helpful_count' => $comment->helpful_count,
                    'unhelpful_count' => $comment->unhelpful_count
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to register vote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the average rating for a listing.
     */
    public function getAverageRating(Listing $listing)
    {
        $avgRating = $listing->comments()
            ->where('is_approved', true)
            ->whereNotNull('rating')
            ->avg('rating');
        
        $reviewCount = $listing->comments()
            ->where('is_approved', true)
            ->whereNotNull('rating')
            ->count();
        
        return response()->json([
            'data' => [
                'average_rating' => round($avgRating, 1),
                'review_count' => $reviewCount
            ]
        ]);
    }

    /**
     * Get all comments for listings owned by the authenticated user.
     */
    public function myListingsComments(Request $request)
    {
        $comments = Comment::whereHas('listing', function($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with(['user', 'listing'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $comments,
            'meta' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }
    
    /**
     * Pin a comment (Admin & listing owner only)
     */
    public function pinComment(Request $request, Listing $listing, Comment $comment)
    {
        // Ensure the comment belongs to the listing
        if ($comment->listing_id !== $listing->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        
        // Check if user is admin or listing owner
        $user = $request->user();
        $isListingOwner = $listing->user_id === $user->id;
        
        if (!$user->isAdmin() && !$isListingOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $comment->update([
            'is_pinned' => !$comment->is_pinned
        ]);
        
        $status = $comment->is_pinned ? 'pinned' : 'unpinned';
        
        return response()->json([
            'message' => "Comment {$status} successfully",
            'data' => $comment->fresh()
        ]);
    }
    
    /**
     * Feature a comment (Admin only)
     */
    public function featureComment(Request $request, Listing $listing, Comment $comment)
    {
        // Ensure the comment belongs to the listing
        if ($comment->listing_id !== $listing->id) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        
        // Admin only
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $comment->update([
            'is_featured' => !$comment->is_featured
        ]);
        
        $status = $comment->is_featured ? 'featured' : 'unfeatured';
        
        return response()->json([
            'message' => "Comment {$status} successfully",
            'data' => $comment->fresh()
        ]);
    }
} 